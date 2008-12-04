<?php
	
/**
 * This isn't really the best use of a class, but I'm in a rush to fix a bug.
 * Objects of this class are (or should be considered) immutable.
 */
class annotation_summary_query
{
	var $url;			// The url GET parameter
	var $username;		// The user GET parameter
	var $searchquery;	// The q GET parameter
	var $searchuserid;	// The u GET parameter (i.e. the user to whom the annotations belong)
	var $searchof;		// The search-of (i.e. the user to whom the annotated content belongs)
	var $sql;			// The result SQL query
	var $handler;		// URL handlers (implements much of this class's behavior)
	var $error;			// Any error encountered by the constructor
	
	/** Construct an immutable summary query */
	function annotation_summary_query( $url, $searchuserid, $searchof, $searchquery, $exactmatch=False, $all=False )
	{
		global $CFG, $USER;
		
		$this->url = $url;
		$this->searchuserid = $searchuserid;
		$this->searchof = $searchof;
		$this->searchquery = $searchquery;
		$this->exactmatch = $exactmatch;
		$this->accessall = $all;	// get access beyond normal privacy limitations (admin only)
		
		if ( '' == $this->searchuserid )
			$this->username = null;
		if ( '' == $this->searchof )
			$this->searchof = null;
		
		// A course or something *must* be specified
		if ( ! $url )  {
			$this->error = "Bad handler URL";
			return null;
		}
		// All annotations for a course
		elseif ( preg_match( '/^.*\/course\/view\.php\?id=(\d+)/', $url, $matches ) )  {
			$this->handler = new course_annotation_url_handler( $matches[ 1 ], $searchof );
		}
		// All annotations far a single forum
		elseif ( preg_match( '/^.*\/mod\/forum\/view\.php\?id=(\d+)/', $url, $matches ) )  {
			$f = (int) $matches[ 1 ];
			$this->handler = new forum_annotation_url_handler( $f, $searchof );
		}
		// Annotations for a single discussion
		elseif ( preg_match( '/^.*\/mod\/forum\/discuss\.php\?d=(\d+)/', $url, $matches ) )  {
			$d = (int) $matches[ 1 ];
			$this->handler = new discussion_annotation_url_handler( $d, $searchof );
		}
		
		// Annotations for a single post
		elseif ( preg_match( '/^.*\/mod\/forum\/permalink\.php\?p=(\d+)/', $url, $matches ) )  {
			$postid = (int) $matches[ 1 ];
			$this->handler = new post_annotatoin_url_handler( $postid, $searchof );
		}
		else  {
			$this->error = "Bad handler URL";
			return null;
		}
	}

	function title( )
	{
		$this->handler->fetch_metadata( );
		return $this->handler->title;
	}
	
	function parent_summary_url( )
	{
		$this->handler->fetch_metadata( );
		return $this->handler->parenturl;
	}
	
	function parent_summary_title( )
	{
		$this->handler->fetch_metadata( );
		return $this->handler->parenttitle;
	}
	
	/** Produce a natural language description of a query */
	function desc( $title )
	{
		global $USER;
		
		$this->handler->fetch_metadata( );
		
		$a->title = ( null == $title ) ? $this->handler->title : $title;
		
		// Access restrictions.  Need to look up actual user names in DB.
		if ( null == $this->searchuserid )
			$a->who = 'anyone';
		elseif ( '*students' == $this->searchuserid )
			$a->who = 'students';
		elseif ( '*teachers' == $this->searchuserid )
			$a->who = 'teachers';
		else
			$a->who = $this->searchuserid;
		
		$a->author = $this->searchof;
		$a->search = $this->searchquery;
		
		$a->match = get_string( $this->exactmatch ? 'matching' : 'containing', ANNOTATION_STRINGS );
		
		if ( null != $this->searchquery && '' != $this->searchquery )
			$s = ( null != $this->searchof ) ? 'annotation_desc_authorsearch' : 'annotation_desc_search';
		else
			$s = ( null != $this->searchof ) ? 'annotation_desc_author' : 'annotation_desc';
			
		return get_string( $s, ANNOTATION_STRINGS, $a );
		
		return $desc;
	}
	
	/** A natural language description, with elements as links to more general queries */
	function desc_with_links( $title )
	{
		global $USER;
		
		$this->handler->fetch_metadata( );
		$title = ( null == $title ) ? $this->handler->title : $title;
		$a->title = $title;
		
		// Show link to parent search
		if ( null != $this->parent_summary_title( ) )  {
			$url = $this->get_summary_url( $this->parent_summary_url( ), $this->searchuserid, $this->searchof, $this->searchquery, $this->exactmatch );
			$a->title = '<a class="opt-link" href="'.s($url)
				. '" title="'.s( get_string( 'unzoom_url_hover', ANNOTATION_STRINGS ) ).'">'
				. '<span class="current">'.s($title).'</span>'
				. '<span class="alt">'.s($this->parent_summary_title( )).'</span></a>';
		}
		
		// Access restrictions.  Need to look up actual user names in DB.
		if ( ! $this->searchuserid )
			$a->who = 'anyone';
		else  {
			$url = $this->get_summary_url( $this->url, '', $this->searchof, $this->searchquery, $this->exactmatch );
			if ( '*students' == $this->searchuserid )
				$s = 'students';
			elseif ( '*teachers' == $this->searchuserid )
				$s = 'teachers';
			else
				$s = $this->searchuserid;
			$a->who = '<a class="opt-link" href="'.s($url)
				.'" title="'.s( get_string( 'unzoom_user_hover', ANNOTATION_STRINGS ) )
				.'"><span class="current">'.s($s).'</span><span class="alt">'
				.s( get_string( 'anyone', ANNOTATION_STRINGS ) ).'</a></a>';
		}
		
		if ( $this->searchof )  {
			$url = $this->get_summary_url( $this->url, $this->searchuserid, '', $this->searchquery, $this->exactmatch );
			$a->author = '<a class="opt-link" href="'.s($url)
				.'" title="'.s( get_string( 'unzoom_author_hover', ANNOTATION_STRINGS ) )
				.'"><span class="current">'.s($this->searchof).'</span><span class="alt">'
				.s( get_string( 'anyone', ANNOTATION_STRINGS ) ).'</span></a>';
		}
		else
			$a->author = null;
		
		$a->search = $this->searchquery;
		
		$url = $this->get_summary_url( $this->url, $this->searchuserid, '', $this->searchquery, ! $this->exactmatch );
		$hover = get_string( $this->exactmatch ? 'unzoom_match_hover' : 'zoom_match_hover', ANNOTATION_STRINGS );
		$m1 = get_string( $this->exactmatch ? 'matching' : 'containing', ANNOTATION_STRINGS );
		$m2 = get_string( $this->exactmatch ? 'containing' : 'matching', ANNOTATION_STRINGS );
		$a->match = '<a class="opt-link" href="'.s($url)
			.'" title="'.s( $hover )
			.'"><span class="current">'.s( $m1 )
			.'</span><span class="alt">'.s( $m2 ).'</span></a>';

		if ( null != $this->searchquery && '' != $this->searchquery )
			$s = ( null != $this->searchof ) ? 'annotation_desc_authorsearch' : 'annotation_desc_search';
		else
			$s = ( null != $this->searchof ) ? 'annotation_desc_author' : 'annotation_desc';
			
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
		$accesscond = null;
		$descusers = '';
		
		// this was originally intended to allow more than one handler to respond to a request.
		// That may still be necessary someday, but perhaps a compound handler would be the
		// best way to respond to it.  I eliminated the handler list because YAGNI.
		$handler = $this->handler;

		// Conditions under which someone else's annotation would be visible to this user
		$accessvisible = "a.access='public'";
		if ( array_key_exists( 'username', $USER ) )  {
			$accessvisible .= " OR a.userid='".addslashes($USER->username)."'"
				. " OR a.access like '%author%' AND a.quote_author='".addslashes($USER->username)."'";
			$handler->fetch_metadata( );
			
			// Don't know how this should work due to changes between Moodle 1.6 and Moodle 1.8:
			//if ( $USER->teacher[ $handler->courseId ] )
			//	$access_visible .= " OR a.access like '%teacher%'";
		}
		
		// Filter annotations according to their owners
		
		// Admin only (used especially for research): transcend usual privacy limitations
		if ( null == $this->searchuserid )
			$accesscond = " ($accessvisible) ";
		elseif ( '*students' == $this->searchuserid )  {
			$accesscond = " ($accessvisible) AND a.userid in ("
				. "SELECT stu.username FROM mdl_user stu "
				. "INNER JOIN mdl_user_students AS sts ON stu.id=sts.userid "
				. "WHERE sts.course=".$handler->courseId.")";
		}
		elseif ( '*teachers' == $this->searchuserid )  {
			$accesscond = " ($accessvisible) AND a.userid in ("
				. "SELECT teu.username FROM mdl_user teu "
				. "INNER JOIN mdl_user_teachers tet ON teu.id=tet.userid "
				. "WHERE tet.course=".$handler->courseId.")";
		}
		else  {
			if ( ! array_key_exists( 'username', $USER ) || $USER->username != $this->searchuserid )
				$accesscond = "($accessvisible)";
			if ( $accesscond )
				$accesscond .= ' AND ';
			$accesscond .= "a.userid='".addslashes($this->searchuserid)."'";
		}

	
		// These are the fields to use for a search;  specific annotations may add more fields
		$stdsearchfields = array( 'a.note', 'a.quote', 'u.firstname', 'u.lastname' );
		
		$prefix = $CFG->prefix;
		
		// Do handler-specific stuff

		// Check whether the range column exists (for backwards compatibility)
		$range = '';
/*		if ( column_type( 'annotation', 'range' ) )
			$range = ', a.range AS range ';
*/
		// These that follow are standard fields, for which no page type exceptions can apply
		$qstdselect = "SELECT a.id AS id, a.url AS url, a.userid AS userid, "
		. "a.start_block, a.start_xpath, a.start_line, a.start_word, a.start_char, "
		. "a.end_block, a.end_xpath, a.end_line, a.end_word, a.end_char, "
		. "a.link AS link, a.link_title AS link_title, a.action AS action, "
		. "a.access AS access, a.created, a.modified $range"
		. ",\n concat(u.firstname, ' ', u.lastname) AS username"
		. ",\n concat('$CFG->wwwroot/user/view.php?id=',u.id) AS note_author_url"
		. ",\n a.note note, a.quote, a.quote_title AS quote_title"
		. ",\n a.quote_author AS quote_author_id"
		. ",\n concat(qu.firstname, ' ', qu.lastname) AS quote_author_name"
		. ",\n concat('$CFG->wwwroot/user/view.php?id=',qu.id) AS quote_author_url";
		
		// Standard tables apply to all (but note the outer join of user, which if gone
		// should not steal the annotation from its owner):
		$qstdfrom = "\nFROM {$prefix}annotation a"
			. "\n INNER JOIN {$prefix}user u ON u.username=a.userid"
			. "\n LEFT OUTER JOIN {$prefix}user qu on qu.username=a.quote_author";
		
		// This search is always limited by access
		$qstdwhere = "\nWHERE ($accesscond)";

		// Searching limits also;  fields searched are not alone those of the annotation:
		// add to them also those a page of this type might use.
		if ( null != $this->searchquery && '' != $this->searchquery )  {
			if ( $this->exactmatch )
				$qstdwhere .= "\n   AND a.note='".addslashes($this->searchquery)."'";
			else  {
				$searchcond = '';
				$addsearchfields = $handler->get_search_fields( );
				$searchcond = '';
				$querywords = split( ' ', $this->searchquery );
				foreach ( $querywords as $word )
				{
					$sword = addslashes( $word );
					foreach ( $stdsearchfields as $field )
						$searchcond .= ( $searchcond == '' ) ? "$field LIKE '%$sword%'" : " OR $field LIKE '%$sword%'";
					foreach ( $addsearchfields as $field )
						$searchcond .= " OR $field LIKE '%$sword%'";
				}
				$qstdwhere .= "\n   AND ($searchcond)";
			}
		}
		
		// The handler must construct the query, which might be a single SELECT or a UNION of multiple SELECTs
		$q = $handler->get_sql( $qstdselect, $qstdfrom, $qstdwhere, $orderby );
		
		return $q;
	}
	
	/** Get query to list users with public annotations on this discussion */
	function list_users_sql( )
	{
		global $CFG;
		return "SELECT u.firstname, u.lastname, u.username AS userid "
			. "\nFROM {$CFG->prefix}user u "
			. "\nINNER JOIN {$CFG->prefix}annotation a ON a.userid=u.username "
			. $this->handler->get_tables( )
			. "\nWHERE a.access='public'";
	}
	
	/** Generate a summary URL corresponding to this query */
	function get_summary_url( $url, $searchuserid, $searchof, $searchquery, $exactmatch=false )
	{
		global $CFG;
		$s = "{$CFG->wwwroot}/annotation/summary.php?url=".urlencode($url);
		if ( null != $searchquery && '' != $searchquery )
			$s .= '&q='.urlencode($searchquery);
		if ( null != $searchuserid && '' != $searchuserid )
			$s .= '&u='.urlencode($searchuserid);
		if ( null != $searchof && '' != $searchof )
			$s .= '&search-of='.urlencode($searchof);
		if ( $exactmatch )
			$s .= '&match=exact';
		return $s;
	}
	
	/** Generate a feed URL corresponding to this query */
	function get_feed_url( $format )
	{
		return $this->get_summary_url( $this->url, $this->searchuserid, $this->searchof, $this->searchquery )
			. '&format=atom';
	}
}


class annotation_url_handler
{
	var $searchof;
	
	function annotation_url_handler( $searchof )
	{
		$this->searchof = $searchof;
	}
	
	// This pulls together the query from the standard portions (which are passed in)
	// and from the handler-specific portions.  Some handlers may override this, e.g. in order
	// to construct a UNION.
	function get_sql( $qstdselect, $qstdfrom, $qstdwhere, $orderby )
	{
		$q = $qstdselect
			. $this->get_fields( )
			. "\n" . $qstdfrom
			. $this->get_tables( )
			. "\n" . $qstdwhere
			. $this->get_conds( );
		if ( $orderby )
			$q .= "\nORDER BY $orderby";
		return $q;
	}
			
	function get_search_fields( )
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
class course_annotation_url_handler extends annotation_url_handler
{
	var $courseid;
	var $title;
	var $parenturl;
	var $parenttitle;
	
	function course_annotation_url_handler( $courseid, $searchof )
	{
		$this->annotation_url_handler( $searchof );
		$this->courseid = $courseid;
		$this->title = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parenturl, parenttitle, courseid.  Will used cached results in preference
	 *  to querying the database. */
	function fetch_metadata( )
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
		$this->parenturl = null;
		$this->parenttitle = null; 
	}
	
	// Override the default implementation of getSql.  This must construct a UNION of multiple queries.
	
	function get_sql( $qstdselect, $qstdfrom, $qstdwhere, $orderby )
	{
		global $CFG;
		$q = '';
		
		// Conditions
		$cond = "\n  AND a.object_type='post'";
		if ( $searchof )
			$cond .= " AND p.userid='".addSlashes( $searchof )."'";

		// First section:  discussion posts
		$q = $qstdselect
			 . ",\n 'forum' section_type, 'content' row_type"
			 . ",\n f.name section_name"
			 . ",\n concat('{$CFG->wwwroot}/mod/forum/view.php?id=',f.id) section_url"
			. $qstdfrom
			 . "\n INNER JOIN {$CFG->prefix}forum_discussions d ON d.course=".$this->courseid.' '
			 . "\n INNER JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND a.object_type='post' AND p.id=a.object_id "
			 . "\n INNER JOIN {$CFG->prefix}forum f ON f.id=d.forum "
			. $qstdwhere
			. $this->getConds( $searchof );
		
		if ( $orderby )
			$q .= "\nORDER BY $orderby";
		
		// If further types of objects can be annotated, additional SELECT statements must be added here
		// as part of a UNION.		
		
		return $q;
	}

	function get_conds( )
	{
		$cond = "\n  AND a.object_type='post'";
		if ( $this->searchof )
			$cond .= " AND a.quote_author='".addSlashes( $this->searchof )."'";
		return $cond;
	}
}


class forum_annotation_url_handler extends annotation_url_handler
{
	var $f;
	var $title;
	var $parenturl;
	var $parenttitle;
	var $courseid;
	
	function forum_annotation_url_handler( $f, $searchof )
	{
		$this->annotation_url_handler( $searchof );
		$this->f = $f;
		$this->title = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parentUrl, parentTitle, courseId.  Will used cached results in preference
	 *  to querying the database. */
	function fetch_metadata( )
	{
		global $CFG;
		
		if ( null != $this->title )
			return;
		else  {
			$query = "SELECT id, name, course FROM {$CFG->prefix}forum WHERE id={$this->f}";
			$row = get_record_sql( $query );
			if ( False !== $row )
			{
				$a->name = $row->name;
				$this->title = get_string( 'forum_name', ANNOTATION_STRINGS, $a );
				$this->courseid = (int) $row->course;
			}
			else
			{
				$this->title = get_string( 'unknown_forum', ANNOTATION_STRINGS );
				$this->courseid = null;
			}
			$this->parenturl = '/course/view.php?id='.$this->courseid;
			$this->parenttitle = get_string( 'whole_course', ANNOTATION_STRINGS ); 
		}
	}
	
	function get_fields( )
	{
		global $CFG;
		return ",\n 'discussion' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url";
	}
	
	function get_tables( )
	{
		global $CFG;
		if ( null == $this->f )
			return "\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
				. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON p.discussion=d.id";
		else
			return 	"\n JOIN {$CFG->prefix}forum_discussions d ON d.forum=".addslashes($this->f)
				. "\n JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND p.id=a.object_id";
	}
	
	function get_conds( )
	{
		$cond = "\n  AND a.object_type='post'";
		if ( $this->searchof )
			$cond .= " AND a.quote_author='".addSlashes( $this->searchof )."'";
		return $cond;
	}
	
	function get_search_fields( )
	{
		return array( 'd.name' );
	}
}


class discussion_annotation_url_handler extends annotation_url_handler
{
	var $d;
	var $title;
	var $parenturl;
	var $parenttitle;
	var $courseid;
	var $forumid;
	
	function discussion_annotation_url_handler( $d, $searchof )
	{
		$this->annotation_url_handler( $searchof );
		$this->d = $d;
		$this->title = null;
	}
	
	/** Internal function to fetch title etc. setting the following fields:
	 *  title, parentUrl, parentTitle, courseId.  Will used cached results in preference
	 *  to querying the database. */
	function fetch_metadata( )
	{
		global $CFG;
	
		if ( null != $this->title )
			return;
		elseif ( null == $this->d )  {
			$this->title = get_string( 'all_discussions', ANNOTATION_STRINGS );
			$this->parenturl = null;
			$this->parenttitle = null;
			$this->courseid = null;
		}
		else  {
			$query = "SELECT d.id AS id, d.name AS name, d.course AS course, d.forum AS forum, f.name AS forum_name"
				. " FROM {$CFG->prefix}forum_discussions d "
				. " INNER JOIN {$CFG->prefix}forum f ON f.id=d.forum "
				. " WHERE d.id={$this->d}";
			$row = get_record_sql( $query );
			$forumname = 'unknown';
			if ( False !== $row )  {
				$a->name = $row->name;
				$this->title = get_string( 'discussion_name', ANNOTATION_STRINGS, $a );
				$this->courseid = (int) $row->course;
				$this->forumid = (int) $row->forum;
				$forumname = $row->forum_name;
			}
			else  {
				$this->title = get_string( 'unknown_discussion', ANNOTATION_STRINGS );
				$this->courseid = null;
				$this->forumid = null;
			}
			$this->parenturl = '/mod/forum/view.php?id='.$this->forumid;
			$a->name = $forumname;
			$this->parenttitle = get_string( 'forum_name', ANNOTATION_STRINGS, $a );
		}
	}
	
	function get_fields( )
	{
		global $CFG;
		return ",\n 'discussion' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url";
	}
	
	function get_tables( )
	{
		global $CFG;
		if ( null == $this->d )
			return "\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
				. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON p.discussion=d.id";
		else
			return 	"\n JOIN {$CFG->prefix}forum_discussions d ON d.id=".addslashes($this->d)
				. "\n JOIN {$CFG->prefix}forum_posts p ON p.discussion=d.id AND p.id=a.object_id";
	}
	
	function get_conds( )
	{
		$cond = "\n  AND a.object_type='post'";
		if ( $this->searchof )
			$cond .= " AND a.quote_author='".addSlashes( $this->searchof )."'";
		return $cond;
	}
	
	function get_search_fields( )
	{
		return array( 'd.name' );
	}
}

class post_annotation_url_handler extends annotation_url_handler
{
	var $p;
	var $title;
	var $parenttitle;
	var $courseid;
	
	function post_annotation_url_handler( $p, $searchof )
	{
		$this->annotation_url_handler( $searchof );
		$this->p = $p;
		$this->title = null;
	}
	
	function fetch_metadata( )
	{
		global $CFG;
		
		if ( null != $this->title )
			return;
		
		$query = "SELECT p.subject pname, d.id did, d.name dname, d.course course"
			. " FROM {$CFG->prefix}forum_posts AS p"
			. " INNER JOIN {$CFG->prefix}forum_discussions d ON d.id=p.discussion"
			. " WHERE p.id=$p";
		$row = get_record_sql( $query );
		if ( False === $row )  {
			$this->title = get_string( 'unknown_post', ANNOTATION_STRINGS );
			$this->parenturl = null;
			$this->parenttitle = null;
			$this->courseid = null;
		}
		else  {
			$a->name = $row->pname;
			$this->title = get_string( 'post_name', ANNOTATION_STRINGS, $a );
			$this->parenturl = $CFG->wwwroot.'/mod/forum/discuss.php?d='.$row->did;
			$a->name = $row->dname;
			$this->parenttitle = get_string( 'discussion_name', ANNOTATION_STRINGS, $a );
			$this->courseid = (int) $row->course;
		}
	}

	function get_fields( )
	{
		global $CFG;
		return ",\n 'post' section_type, 'post' row_type"
			. ",\n d.name section_name"
			. ",\n concat('{$CFG->wwwroot}/mod/forum/discuss.php?d=',d.id) section_url"
			. ",\n 'post' object_type"
			. ",\n p.id object_id";
	}
	
	function get_tables( )
	{
		global $CFG;
		return 	"\n LEFT OUTER JOIN {$CFG->prefix}forum_posts p ON p.id=a.object_id"
			. "\n LEFT OUTER JOIN {$CFG->prefix}forum_discussions d ON d.id=p.discussion";
	}
	
	function get_conds( )
	{
		$cond = "\n AND a.object_type='post'";
		if ( $this->searchof )
			$cond .= " AND a.quote_author='".addSlashes( $this->searchof )."'";
		return $cond;
	}
}
?>
