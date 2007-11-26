<?php
	
/**
 * This isn't really the best use of a class, but I'm in a rush to fix a bug.
 * Objects of this class are (or should be considered) immutable.
 */
class AnnotationSummaryQuery
{
	var $url;			// The url GET parameter
	var $username;		// The user GET parameter
	var $searchQuery;	// The q GET parameter
	var $searchUser;	// The u GET parameter (i.e. the user to whom the annotations belong)
	var $searchOf;		// The search-of (i.e. the user to whom the annotated content belongs)
	var $sql;			// The result SQL query
	var $handler;		// URL handlers (implements much of this class's behavior)
	var $error;			// Any error encountered by the constructor
	
	/** Construct an immutable summary query */
	function AnnotationSummaryQuery( $url, $searchUser, $searchOf, $searchQuery )
	{
		global $CFG, $USER;
		
		$this->url = $url;
		$this->searchUser = $searchUser;
		$this->searchOf = $searchOf;
		$this->searchQuery = $searchQuery;
		
		if ( '' == $this->searchUser )
			$this->username = null;
		if ( '' == $this->searchOf )
			$this->searchOf = null;
		
		// All annotations for a course
		if ( preg_match( '/^.*\/course\/view\.php\?id=(\d+)/', $url, $matches ) )
		{
			$this->handler = new CourseAnnotationUrlHandler( $matches[ 1 ], $searchOf );
		}
		// All annotations far a single forum
		elseif ( preg_match( '/^.*\/mod\/forum\/view\.php\?id=(\d+)/', $url, $matches ) )
		{
			$f = (int) $matches[ 1 ];
			$this->handler = new ForumAnnotationUrlHandler( $f, $searchOf );
		}
		// Annotations for a single discussion
		elseif ( preg_match( '/^.*\/mod\/forum\/discuss\.php\?d=(\d+)/', $url, $matches ) )
		{
			$d = (int) $matches[ 1 ];
			$this->handler = new DiscussionAnnotationUrlHandler( $d, $searchOf );
		}
		
		// Annotations for a single post
		elseif ( preg_match( '/^.*\/mod\/forum\/permalink\.php\?p=(\d+)/', $url, $matches ) )
		{
			$postId = (int) $matches[ 1 ];
			$this->handler = new PostAnnotationUrHandler( $postId, $searchOf );
		}
		else
		{
			$this->error = "Bad handler URL";
			return null;
		}
	}

	function title( )
	{
		$this->handler->fetchMetadata( );
		return $this->handler->title;
	}
	
	function parentSummaryUrl( )
	{
		$this->handler->fetchMetadata( );
		return $this->handler->parentUrl;
	}
	
	function parentSummaryTitle( )
	{
		$this->handler->fetchMetadata( );
		return $this->handler->parentTitle;
	}
	
	/** Produce a natural language description of a query */
	function desc( $title )
	{
		global $USER;
		
		$this->handler->fetchMetadata( );
		
		$a->title = ( null == $title ) ? $this->handler->title : $title;
		
		// Access restrictions.  Need to look up actual user names in DB.
		if ( null == $this->searchUser )
			$a->who = 'anyone';
		elseif ( '*students' == $this->searchUser )
			$a->who = 'students';
		elseif ( '*teachers' == $this->searchUser )
			$a->who = 'teachers';
		else
			$a->who = $this->searchUser;
		
		$a->author = $this->searchOf;
		$a->search = $this->searchQuery;
		
		if ( null != $this->searchQuery && '' != $this->searchQuery )
			$s = ( null != $this->searchOf ) ? 'annotation_desc_authorsearch' : 'annotation_desc_search';
		else
			$s = ( null != $this->searchOf ) ? 'annotation_desc_author' : 'annotation_desc';
			
		return get_string( $s, ANNOTATION_STRINGS, $a );
		
		return $desc;
	}
	
	/**
	 * This takes a list of handlers, each of which corresponds to a particular type of
	 * query (e.g. discussion forum), along with search fields for performing a search.
	 * It returns the SQL query string.
	 *
	 * $searchAccess can be public, private, or empty.  Public annotations are available to
	 *  *everyone*, not just course members or Moodle users.
	 */
	function sql( $orderby )
	{
		global $CFG, $USER;
		
		// The query is a UNION of separate queries, one for each type of annotation
		// This is unfortunate:  with a common table structure, one for parent-child
		// URL relationships, another with URL properties (title and owner would
		// suffice), would forgo UNIONs and simplify this code.
		
		// Users can only see their own annotations or the public annotations of others
		// This is an awfully complex combination of conditions.  I'm wondering if that's
		// a design flaw.
		$access_cond = null;
		$desc_users = '';
		
		// this was originally intended to allow more than one handler to respond to a request.
		// That may still be necessary someday, but perhaps a compound handler would be the
		// best way to respond to it.  I eliminated the handler list because YAGNI.
		$handler = $this->handler;

		// Conditions under which someone else's annotation would be visible to this user
		$access_visible = "a.access='public'";
		if ( array_key_exists( 'username', $USER ) )
		{
			$access_visible .= " OR a.userid='".addslashes($USER->username)."'"
				. " OR a.access like '%author%' AND a.quote_author='".addslashes($USER->username)."'";
			$handler->fetchMetadata( );
			
			// Don't know how this should work due to changes between Moodle 1.6 and Moodle 1.8:
			//if ( $USER->teacher[ $handler->courseId ] )
			//	$access_visible .= " OR a.access like '%teacher%'";
		}
		
		// Filter annotations according to their owners
		if ( null == $this->searchUser )
			$access_cond = " ($access_visible) ";
		elseif ( '*students' == $this->searchUser )
		{
			$access_cond = " ($access_visible) AND a.userid in ("
				. "SELECT stu.username FROM mdl_user stu "
				. "INNER JOIN mdl_user_students AS sts ON stu.id=sts.userid "
				. "WHERE sts.course=".$handler->courseId.")";
		}
		elseif ( '*teachers' == $this->searchUser )
		{
			$access_cond = " ($access_visible) AND a.userid in ("
				. "SELECT teu.username FROM mdl_user AS teu "
				. "INNER JOIN mdl_user_teachers tet ON teu.id=tet.userid "
				. "WHERE tet.course=".$handler->courseId.")";
		}
		else
		{
			if ( ! array_key_exists( 'username', $USER ) || $USER->username != $this->searchUser )
				$access_cond = "($access_visible)";
			if ( $access_cond )
				$access_cond .= ' AND ';
			$access_cond .= "a.userid='".addslashes($this->searchUser)."'";
		}

	
		// These are the fields to use for a search;  specific annotations may add more fields
		$std_search_fields = array( 'a.note', 'a.quote', 'u.firstname', 'u.lastname' );
		
		$prefix = $CFG->prefix;
		
		// Do handler-specific stuff

		// Check whether the range column exists (for backwards compatibility)
		$range = '';
		if ( column_type( $CFG->prefix.'annotation', 'range' ) )
			$range = ', a.range AS range ';

		// These that follow are standard fields, for which no page type exceptions can apply
		$q_std_select = "SELECT a.id AS id, a.url AS url, a.userid AS userid, "
		. "a.start_block, a.start_xpath, a.start_word, a.start_char, "
		. "a.end_block, a.end_xpath, a.end_word, a.end_char, "
		. "a.link AS link, a.link_title AS link_title, a.action AS action, "
		. "a.access AS access, a.created, a.modified $range"
		. ",\n concat(u.firstname, ' ', u.lastname) AS note_author"
		. ",\n concat('$CFG->wwwroot/user/view.php?id=',u.id) AS note_author_url"
		. ",\n a.note note, a.quote, a.quote_title AS quote_title"
		. ",\n concat(qu.firstname, ' ', qu.lastname) AS quote_author"
		. ",\n concat('$CFG->wwwroot/user/view.php?id=',qu.id) AS quote_author_url";
		
		// Standard tables apply to all (but note the outer join of user, which if gone
		// should not steal the annotation from its owner):
		$q_std_from = "\nFROM {$prefix}annotation AS a"
			. "\n INNER JOIN {$prefix}user u ON u.username=a.userid"
			. "\n LEFT OUTER JOIN {$prefix}user qu on qu.username=a.quote_author";
		
		// This search is always limited by access
		$q_std_where = "\nWHERE ($access_cond)";

		// Searching limits also;  fields searched are not alone those of the annotation:
		// add to them also those a page of this type might use.
		if ( null != $this->searchQuery && '' != $this->searchQuery )
		{
			$search_cond = '';
			$add_search_fields = $handler->getSearchFields( );
			$search_cond = '';
			$queryWords = split( ' ', $this->searchQuery );
			foreach ( $queryWords as $word )
			{
				$sWord = addslashes( $word );
				foreach ( $std_search_fields as $field )
					$search_cond .= ( $search_cond == '' ) ? "$field LIKE '%$sWord%'" : " OR $field LIKE '%$sWord%'";
				foreach ( $add_search_fields as $field )
					$search_cond .= " OR $field LIKE '%$sWord%'";
			}
			$q_std_where .= "\n   AND ($search_cond)";
		}
		
		// The handler must construct the query, which might be a single SELECT or a UNION of multiple SELECTs
		$q = $handler->getSql( $q_std_select, $q_std_from, $q_std_where, $orderby );
		
		return $q;
	}
	
	/** Get query to list users with public annotations on this discussion */
	function listUsersSql( )
	{
		global $CFG;
		return "SELECT u.firstname, u.lastname, u.username "
			. "\nFROM {$CFG->prefix}user u "
			. "\nINNER JOIN {$CFG->prefix}annotation a ON a.userid=u.username "
			. $this->handler->getTables( )
			. "\nWHERE a.access='public'";
	}
	
	/** Generate a summary URL corresponding to this query */
	function getSummaryUrl( $url, $searchUser, $searchOf, $searchQuery )
	{
		global $CFG;
		$s = "{$CFG->wwwroot}/annotation/summary.php?url=".urlencode($url);
		if ( null != $searchQuery && '' != $searchQuery )
			$s .= '&q='.urlencode($searchQuery);
		if ( null != $searchUser && '' != $searchUser )
			$s .= '&user='.urlencode($searchUser);
		if ( null != $searchOf && '' != $searchOf )
			$s .= '&search-of='.urlencode($searchOf);
		return $s;
/*		global $CFG;
		$s = "{$CFG->wwwroot}/annotation/summary.php?url=".urlencode($this->url);
		if ( null != $this->searchQuery && '' != $this->searchQuery )
			$s .= '&q='.urlencode($this->searchQuery);
		if ( null != $this->searchUser && '' != $this->searchUser )
			$s .= '&user='.urlencode($this->searchUser);
		if ( null != $this->searchOf && '' != $this->searchOf )
			$s .= '&search-of='.urlencode($this->searchOf);
		return $s;
*/	}
	
	/** Generate a feed URL corresponding to this query */
	function getFeedUrl( $format )
	{
		return $this->getSummaryUrl( $this->url, $this->searchUser, $this->searchOf, $this->searchQuery )
			. '&format=atom';
	}
}


class AnnotationUrlHandler
{
	var $searchOf;
	
	function AnnotationUrlHandler( $searchOf )
	{
		$this->searchOf = $searchOf;
	}
	
	// This pulls together the query from the standard portions (which are passed in)
	// and from the handler-specific portions.  Some handlers may override this, e.g. in order
	// to construct a UNION.
	function getSql( $q_std_select, $q_std_from, $q_std_where, $orderby )
	{
		$q = $q_std_select
			. $this->getFields( )
			. $q_std_from
			. $this->getTables( )
			. $q_std_where
			. $this->getConds( );
		if ( $orderby )
			$q .= "\nORDER BY $orderby";
		return $q;
	}
			
	function getSearchFields( )
	{
		return array( );
	}
}


/*
 * Oh, for a language with proper lists...
 */
/*
 A course handler is a nice enough idea, but what does it mean?  Does it retrieve all annotations for
 that course, or shoud there actually be a way to get all discussion annotations for a course?  How
 does it know about all of the sub-level entities that can be annotated (forum posts etc.)?  For now,
 I think adding an optional courseId parameter to ForumAnnotationHandler may be a better option, though
 it will look like a bit of a hack.
*/
class CourseAnnotationUrlHandler extends AnnotationUrlHandler
{
	var $courseId;
	var $title;
	var $parentUrl;
	var $parentTitle;
	
	function CourseAnnotationUrlHandler( $courseId, $searchOf )
	{
		$this->AnnotationUrlHandler( $searchOf );
		$this->courseId = $courseId;
		$this->title = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parentUrl, parentTitle, courseId.  Will used cached results in preference
	 *  to querying the database. */
	function fetchMetadata( )
	{
		global $CFG;
		
		if ( null != $this->title )
			return;
		$query = "SELECT fullname "
			. " FROM {$CFG->prefix}course WHERE id={$this->courseId}";
		$row = get_record_sql( $query );
		if ( False !== $row )
			$this->title = $row->fullname;
		else
			$this->title = get_string( 'unknown course', ANNOTATION_STRINGS );
		$this->parentUrl = null;
		$this->parentTitle = null; 
	}
	
	// Override the default implementation of getSql.  This must construct a UNION of multiple queries.
	
	function getSql( $q_std_select, $q_std_from, $q_std_where, $orderby )
	{
		global $CFG;
		$q = '';
		
		// Conditions
		$cond = "\n  AND a.object_type='post'";
		if ( $searchOf )
			$cond .= " AND p.userid='".addSlashes( $searchOf )."'";

		// First section:  discussion posts
		$q = $q_std_select
			 . ",\n 'forum' section_type, 'content' row_type"
			 . ",\n f.name section_name"
			 . ",\n concat('{$CFG->wwwroot}/mod/forum/view.php?id=',f.id) section_url"
			. $q_std_from
			 . "\n INNER JOIN {$CFG->prefix}forum_discussions d ON d.course=".$this->courseId.' '
			 . "\n INNER JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND a.object_type='post' AND p.id=a.object_id "
			 . "\n INNER JOIN {$CFG->prefix}forum f ON f.id=d.forum "
			. $q_std_where
			. $this->getConds( $searchOf );
		
		if ( $orderby )
			$q .= "\nORDER BY $orderby";
		
		// If further types of objects can be annotated, additional SELECT statements must be added here
		// as part of a UNION.		
		
		return $q;
	}

	function getConds( )
	{
		$cond = "\n  AND a.object_type='post'";
		if ( $this->searchOf )
			$cond .= " AND p.userid='".addSlashes( $this->searchOf )."'";
	}
}


class ForumAnnotationUrlHandler extends AnnotationUrlHandler
{
	var $f;
	var $title;
	var $parentUrl;
	var $parentTitle;
	var $courseId;
	
	function ForumAnnotationUrlHandler( $f, $searchOf )
	{
		$this->AnnotationUrlHandler( $searchOf );
		$this->f = $f;
		$this->title = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parentUrl, parentTitle, courseId.  Will used cached results in preference
	 *  to querying the database. */
	function fetchMetadata( )
	{
		global $CFG;
		
		if ( null != $this->title )
			return;
		else
		{
			$query = "SELECT id, name, course FROM {$CFG->prefix}forum WHERE id={$this->f}";
			$row = get_record_sql( $query );
			if ( False !== $row )
			{
				$a->name = $row->name;
				$this->title = get_string( 'forum_name', ANNOTATION_STRINGS, $a );
				$this->courseId = (int) $row->course;
			}
			else
			{
				$this->title = get_string( 'unknown_forum', ANNOTATION_STRINGS );
				$this->courseId = null;
			}
			$this->parentUrl = '/course/view.php?id='.$this->courseId;
			$this->parentTitle = get_string( 'whole_course', ANNOTATION_STRINGS ); 
		}
	}
	
	function getFields( )
	{
		global $CFG;
		return ",\n 'discussion' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url";
	}
	
	function getTables( )
	{
		global $CFG;
		if ( null == $this->f )
			return "\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
				. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON p.discussion=d.id";
		else
			return 	"\n JOIN {$CFG->prefix}forum_discussions d ON d.forum=".addslashes($this->f)
				. "\n JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND p.id=a.object_id";
	}
	
	function getConds( )
	{
		$cond = "\n  AND a.object_type='post'";
		if ( $this->searchOf )
			$cond .= " AND p.userid='".addSlashes( $this->searchOf )."'";
	}
	
	function getSearchFields( )
	{
		return array( 'd.name' );
	}
}


class DiscussionAnnotationUrlHandler extends AnnotationUrlHandler
{
	var $d;
	var $title;
	var $parentUrl;
	var $parentTitle;
	var $courseId;
	var $forumId;
	
	function DiscussionAnnotationUrlHandler( $d, $searchOf )
	{
		$this->AnnotationUrlHandler( $searchOf );
		$this->d = $d;
		$this->title = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parentUrl, parentTitle, courseId.  Will used cached results in preference
	 *  to querying the database. */
	function fetchMetadata( )
	{
		global $CFG;
	
		if ( null != $this->title )
			return;
		if ( null == $this->d )
		{
			$this->title = get_string( 'all_discussions', ANNOTATION_STRINGS );
			$this->parentUrl = null;
			$this->parentTitle = null;
			$this->courseId = null;
		}
		else
		{
			$query = "SELECT d.id AS id, d.name AS name, d.course AS course, d.forum AS forum, f.name AS forum_name"
				. " FROM {$CFG->prefix}forum_discussions d "
				. " INNER JOIN {$CFG->prefix}forum f ON f.id=d.forum "
				. " WHERE d.id={$this->d}";
			$row = get_record_sql( $query );
			$forumName = 'unknown';
			if ( False !== $row )
			{
				$a->name = $row->name;
				$this->title = get_string( 'discussion_name', ANNOTATION_STRINGS, $a );
				$this->courseId = (int) $row->course;
				$this->forumId = (int) $row->forum;
				$forumName = $row->forum_name;
			}
			else
			{
				$this->title = get_string( 'unknown_discussion', ANNOTATION_STRINGS );
				$this->courseId = null;
				$this->forumId = null;
			}
			$this->parentUrl = '/mod/forum/view.php?id='.$this->forumId;
			$a->name = $forumName;
			$this->parentTitle = get_string( 'forum_name', ANNOTATION_STRINGS, $a );
		}
	}
	
	function getFields( )
	{
		global $CFG;
		return ",\n 'discussion' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url";
	}
	
	function getTables( )
	{
		global $CFG;
		if ( null == $this->d )
			return "\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
				. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON p.discussion=d.id";
		else
			return 	"\n JOIN {$CFG->prefix}forum_discussions d ON d.id=".addslashes($this->d)
				. "\n JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND p.id=a.object_id";
	}
	
	function getConds( )
	{
		$cond = "\n  AND a.object_type='post'";
		if ( $this->searchOf )
			$cond .= " AND p.userid='".addSlashes( $this->searchOf )."'";
	}
	
	function getSearchFields( )
	{
		return array( 'd.name' );
	}
}

class PostAnnotationUrlHandler extends AnnotationUrlHandler
{
	var $p;
	var $title;
	var $parentTitle;
	var $courseId;
	
	function PostAnnotationUrlHandler( $p, $searchOf )
	{
		$this->AnnotationUrlHandler( $searchOf );
		$this->p = $p;
		$this->title = null;
	}
	
	function fetchMetadata( )
	{
		global $CFG;
		
		if ( null != $this->title )
			return;
		
		$query = "SELECT p.subject pname, d.id did, d.name dname, d.course course"
			. " FROM {$CFG->prefix}forum_posts AS p"
			. " INNER JOIN {$CFG->prefix}forum_discussions d ON d.id=p.discussion"
			. " WHERE p.id=$p";
		$row = get_record_sql( $query );
		if ( False === $row )
		{
			$this->title = get_string( 'unknown_post', ANNOTATION_STRINGS );
			$this->parentUrl = null;
			$this->parentTitle = null;
			$this->courseId = null;
		}
		else
		{
			$a->name = $row->pname;
			$this->title = get_string( 'post_name', ANNOTATION_STRINGS, $a );
			$this->parentUrl = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$row->did;
			$a->name = $row->dname;
			$this->parentTitle = get_string( 'discussion_name', ANNOTATION_STRINGS, $a );
			$this->courseId = (int) $row->course;
		}
	}

	function getFields( )
	{
		global $CFG;
		return ",\n 'post' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url"
			. ",\n 'post' object_type"
			. ",\n p.id object_id";
	}
	
	function getTables( )
	{
		global $CFG;
		return 	"\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
			. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON d.id=p.discussion";
	}
	
	function getConds( )
	{
		$cond = "\n AND a.object_type='post'";
		if ( $this->searchOf )
			$cond .= " AND p.userid='".addSlashes( $this->searchOf )."'";
	}
}

?>
