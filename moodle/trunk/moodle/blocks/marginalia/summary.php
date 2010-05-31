<?php

 // summary.php
 // Part of Marginalia annotation for Moodle
 // See www.geof.net/code/annotation/ for full source and documentation.

 // Display a summary of all annotations for the current user

require_once( "../../config.php" );
require_once( "marginalia-php/MarginaliaHelper.php" );
require_once( 'marginalia-php/Annotation.php' );
require_once( 'marginalia-php/Keyword.php' );
require_once( 'config.php' );
require_once( 'annotation_globals.php' );
require_once( "annotation_summary_query.php" );
require_once( "keywords_db.php" );
require_once( "lib.php" );

global $CFG;

if ($CFG->forcelogin) {
	require_login();
}

class annotation_summary_page
{
	var $mia_globals;
	var $first;
	var $maxrecords = 50;
	var $logger = null;
	
	function annotation_summary_page( $first=1 )
	{
		$this->mia_globals = new annotation_globals( );
		$this->first = $first;
		$this->logger = moodle_marginalia::get_logger();
	}
	
	function show_header( )
	{
		global $CFG, $USER;
		
		$swwwroot = htmlspecialchars( $CFG->wwwroot );
		

		$navlinks = array();
		if ( null != $this->course && $this->course->category)
		{
			$navlinks[ ] = array(
				'name' => $this->course->shortname,
				'link' => $CFG->wwwroot.'/course/view.php?id='.$this->course->id,
				'type' => 'title' );
		}
		$navlinks[] = array(
				'name' => get_string( 'summary_title', ANNOTATION_STRINGS ),
				'type' => 'title');
		$navigation = build_navigation( $navlinks );

		require_js( ANNOTATION_PATH.'/marginalia/3rd-party.js' );
		require_js( ANNOTATION_PATH.'/marginalia/3rd-party/jquery.js' );
		require_js( ANNOTATION_PATH.'/marginalia/log.js' );
		require_js( ANNOTATION_PATH.'/marginalia-config.js' );
		require_js( ANNOTATION_PATH.'/marginalia/domutil.js' );
		require_js( ANNOTATION_PATH.'/marginalia/prefs.js' );
		require_js( ANNOTATION_PATH.'/marginalia/rest-prefs.js' );
		require_js( ANNOTATION_PATH.'/marginalia/annotation.js' );
		require_js( ANNOTATION_PATH.'/marginalia/restutil.js' );
		require_js( ANNOTATION_PATH.'/marginalia/rest-annotate.js' );
		require_js( ANNOTATION_PATH.'/smartquote.js' );
		require_js( ANNOTATION_PATH.'/summary.js' );
		
		if ( $this->logger && $this->logger->is_active())
			require_js( ANNOTATION_PATH.'/rest-log.js' );

		$meta = "<link rel='stylesheet' type='text/css' href='".s( ANNOTATION_PATH )."/summary-styles.php'/>\n";
		
		if ( null != $this->course )  {
			print_header($this->course->shortname.': '.get_string( 'summary_title', ANNOTATION_STRINGS ), $this->course->fullname,
				$navigation, "", $meta, true, "", navmenu($this->course) );
		}
		else
			print_header(get_string( 'summary_title', ANNOTATION_STRINGS ), null, $navigation, "", $meta, true, "", null );
//		echo $tagsHtml;

		if( isloggedin() )
		{
			$sannotationpath = s( ANNOTATION_PATH );
			echo "<script language='JavaScript' type='text/javascript'>\n"
				. "var annotationService = new RestAnnotationService('$sannotationpath/annotate.php', { csrfCookie: 'MoodleSessionTest' } );\n"
				. "window.annotationSummary = new AnnotationSummary('$swwwroot', {"
				." \n annotationService: annotationService"
				.",\n userid: ".(int)$USER->id
				.",\n useLog: ".($this->logger && $this->logger->is_active() ? 'true' : 'false' )
				.",\n csrfCookie: 'MoodleSessionTest".$CFG->sessioncookie."'"
				."} );\n"
				. "window.preferences = new Preferences( new RestPreferenceService('$sannotationpath/user-preference.php' ) );\n"
				. "</script>\n";
		}
	}
	
	function show( )
	{
		$this->errorpage = array_key_exists( 'error', $_GET ) ? $_GET[ 'error' ] : null;

		$summary = new annotation_summary_query( annotation_summary_query::map_params( $_GET ) );

		if ( null == $summary )  {
			header( 'HTTP/1.1 400 Bad Request' );
			echo '<h1>400 Bad Request</h1>';
		}
		elseif ( ! MarginaliaHelper::isUrlSafe( $summary->url ) )  {
			header( 'HTTP/1.1 400 Bad Request' );
			echo '<h1>400 Bad Request</h1>Bad url parameter';
		}
		else  {
			$sql = $summary->sql( );
			//echo "SQL: $sql\n";	// uncomment for debugging
			$annotations = get_records_sql( $sql, $this->first - 1, $this->maxrecords );
			
			$count_sql = $summary->count_sql( );
			$annotation_count = count_records_sql( $count_sql );
				
			$format = array_key_exists( 'format', $_GET ) ? $_GET[ 'format' ] : 'html';
			
			if ( 'atom' == $format )
				$this->show_atom( $summary, $annotations );
			else
				$this->show_html( $summary, $annotations, $annotation_count );
		}
	}

	function show_atom( $summary, $annotations )
	{
		global $CFG;
		
		$annotationobjs = array();
		foreach ( $annotations as $annotationrec )
			$annotationobjs[ ] = annotation_globals::record_to_annotation( $annotationrec );
		MarginaliaHelper::generateAnnotationFeed( $annotationobjs,
			annotation_globals::get_feed_tag_uri( ),
			MarginaliaHelper::getLastModified( $annotationobjs, annotation_globals::get_install_date( ) ),
			annotation_globals::get_service_path( ),
			annotation_globals::get_host( ),
			$summary->get_feed_url( 'atom' ),
			$CFG->wwwroot );
	}
	
	function show_html( $summary, $annotations, $annotation_count )
	{
		global $CFG, $USER;
		
		// Get the course.  This can't be passed as a GET parameter because this URL could be via the
		// Atom feed, and the Atom feed is generated exclusively by annotation code which doesn't know
		// that much about Moodle.  So the handler has to query it based on a discussion ID or the like.
		$this->course = null;
		$this->courseid = $summary->handler->courseid;
		if ( null != $this->courseid )  {
			if (! $this->course = get_record( "course", "id", $this->courseid ) )
				error( "Course ID is incorrect");

			// Ok, now this is probably very wrong.  If the user looks for annotations within a course,
			// it requires a login.  Without the course (i.e. in a more general search), it doesn't!
			// I would eleminate this, but I don't really know how Moodle security works. #geof#
			if ( $this->course->category )
				require_login( $this->course->id );
		}

		// Keep for debugging:
		// echo "<h2>Query</h2><pre>".$query->sql( 'a.id' )."</pre>";
		
		// Show header
		$swwwroot = htmlspecialchars( $CFG->wwwroot );
		
		$this->show_header( );

		$keywords = isloggedin() ? annotation_keywords_db::list_keywords( $USER->id ) : array( );
		$keywordhash = array( );
		for ( $i = 0;  $i < count( $keywords );  ++$i )  {
			$keyword = $keywords[ $i ];
			$keywordhash[ $keyword->name ] = true;
		}
		
		// print search header
		//  * my annotations
		//  * shared annotations
		//  * instructor annotations
		//  * annotations of my work
		echo "<form id='annotation-search' method='get' action='summary.php'>\n";
		echo "<fieldset>\n";
		echo "<label for=''>".get_string( 'prompt_find', ANNOTATION_STRINGS )."</label>\n";
		if ( $summary->ofuser )
			echo "<input type='hidden' name='search-of' id='search-of' value='".s($this->mia_globals->fullname($summary->ofuser))."'/>\n";
		if ( $summary->user )
			echo "<input type='hidden' name='u' id='u' value='".s($this->mia_globals->fullname($summary->user))."'/>\n";
		echo "<input type='text' id='search-text' name='q' value='".s($summary->text)."'/>\n";
		echo "<input type='submit' value='".get_string( 'go' )."'/>\n";
		echo "<input type='hidden' name='url' value='".s($summary->url)."'/>\n";
		helpbutton( 'annotation_summary', get_string( 'summary_help', ANNOTATION_STRINGS ), 'block_marginalia' );
		echo "</fieldset>\n";
		echo "</form>";
		
		// If this page is an error, explain what it's about
		if ( 'range-mismatch' == $this->errorpage ) {
			echo '<p class="error"><em class="range-error">!</em>'
				.get_string( 'summary_range_error', ANNOTATION_STRINGS )."</p>\n";
		}
		
		$a = new object( );
		$a->n = $annotations ? count( $annotations ) : 0;
		$a->m = $annotation_count;
		echo '<p id="query">'.get_string( 'prompt_search_desc', ANNOTATION_STRINGS, $a )
			.' '.$summary->desc_with_links(null).":</p>\n";
		
		$cursection = null;
		$cursectiontype = null;
		$curuser = null;
		$cururl = null;
		// make sure some records came back
		if ( null != $annotations )
		{
			// Convert $annotations to an indexable array (why isn't it?  for efficiency with large data sets?)
			$annotationa = array( );
			foreach ( $annotations as $annotation )
				$annotationa[ ] = $annotation;
				
			$ncols = 6;
			
			if ( AN_SUMMARY_ORDER_TIME == $summary->orderby )
				$ncols += 1;
	
			echo '<table cellspacing="0" class="annotations">'."\n";
			for ( $annotationi = 0;  $annotationi < count( $annotationa );  ++$annotationi )  {
				$annotation = $annotationa[ $annotationi ];
				
				// Display a heading for each new section URL
				if ( $annotation->section_type != $cursectiontype || $annotation->section_url != $cursection )  {
					if ( $cursection != null )
						echo "</tbody>\n";
					echo "<thead><tr><th colspan='$ncols'>";
					$a->section_type = htmlspecialchars( $annotation->section_type );
					echo '<h3>'.s( $annotation->section_type ).'</h3>: '
						. "<a href='".s( $annotation->section_url )
						."' title='".get_string( 'prompt_section', ANNOTATION_STRINGS, $a )."'>" 
						.s( $annotation->section_name ) . "</a>";
					if ( $annotation->section_url != $summary->url )  {
						$tsummary = $summary->derive( array( 'url' => $annotation->section_url ) );
						$turl = $tsummary->summary_url( );
						echo "<a class='zoom' title='".get_string( 'zoom_url_hover', ANNOTATION_STRINGS, $annotation )."' href='".s( $turl )."'>".AN_FILTERICON_HTML."</a>\n";
					}
					echo '</th></tr></thead>'."\n";

					if ( AN_SUMMARYHEADINGSTOP )
						$this->show_column_headings( $summary, 'top' );

					echo '<tbody>'."\n";
					$cursection = $annotation->section_url;
					$cursectiontype = $annotation->section_type;
					$curuser = $annotation->userid;
					$cururl = null;
				}
				
				// For each new url, display the title and author
				if ( $annotation->url != $cururl ) { //|| $annotation->userid != $curUser ) {
					$cururl = $annotation->url;
					$curuser = $annotation->userid;

					echo "<tr class='fragment first'>";
					// Figure out how many rows this source will span
					$nrows = 1;
					for ( $j = $annotationi + 1;  $j < count( $annotationa );  ++$j )  {
						if ( $annotationa[ $j ]->url != $cururl )
							break;
						$nrows += 1;
					}
					
					// Only prefix the URL with the site root if it doesn't already have a scheme
					// Only check for http and https schemes to prevent obscure attacks
					$url = $annotation->url;
					if ( ! ( str_startswith( $url, 'http://' ) || str_startswith( $url, 'https://' ) ) ) 
						$url = $CFG->wwwroot.$annotation->url;
					
					echo "<th rowspan='$nrows'>";
					$url = MarginaliaHelper::isUrlSafe( $url ) ? $url : '';
					$a->row_type = $annotation->row_type;
					$a->author = $this->mia_globals->fullname2( $annotation->quote_author_firstname, $annotation->quote_author_lastname );
					echo "<a class='url' href='".s($url)."' title='".get_string( 'prompt_row', ANNOTATION_STRINGS, $a)."'>";
					echo s( $annotation->quote_title ) . '</a>';

					echo "<br/>by <span class='quote-author'>".
						s( $a->author )."</span>\n";
					
					// Link to filter only annotations by this user
					if ( ! $summary->ofuser || $annotation->quote_author_username != $summary->ofuser->username )  {
						$tsummary = $summary->derive( array( 'ofuserid' => $annotation->quote_author_id ) );
						$turl = $tsummary->summary_url( );
						$a->fullname = $this->mia_globals->fullname2( $annotation->quote_author_firstname, $annotation->quote_author_lastname );
						echo $this->zoom_link( $tsummary->summary_url( ), get_string( 'zoom_author_hover', ANNOTATION_STRINGS, $a) );
					}
					echo "</th>\n";
				}
				else
					echo "<tr>";
				
					
				// Show the quoted text
				echo "<td class='quote'>";
				p( $annotation->quote );
				echo "</td>\n";
				
				
				// Show the note
				echo "<td class='note'>";
				if ( ! $annotation->note )
					echo '&#160;';
				else
					echo s( $annotation->note );

				if ( ! $summary->exactmatch && array_key_exists( $annotation->note, $keywordhash ) )  {
					$tsummary = $summary->derive( array( 'text' => $annotation->note, 'exactmatch' => true ) );
					echo ' '.$this->zoom_link( $tsummary->summary_url( ), get_string( 'zoom_match_hover', ANNOTATION_STRINGS ) );
				}
				echo "</td>\n";

				
				// Show annotation time (if requested)
				if ( AN_SUMMARY_ORDER_TIME == $summary->orderby )
					echo "<td class='modified'>".s(date('Y-m-d G:i', $annotation->modified))."</td>\n";
				
				// Show edit controls or the user who created the annotation
				echo "<td class='user".( isloggedin() && $annotation->userid == $USER->id ? ' isloginuser' : '')."'>\n";

				// Smartquote button
				if ( AN_USESMARTQUOTE )
				{
					// $SMARTQUOTE_SYMBOL = AN_SMARTQUOTEICON_PHP; //'&#9850;';
					$sqid = s( 'sq'.$annotation->id );
					$sqtitle = get_string( 'smartquote_annotation', ANNOTATION_STRINGS );
					echo "<button class='smartquote' id='$sqid' title='$sqtitle'>".AN_SMARTQUOTEICON_HTML."</button>\n";
				}
				
				// Controls for current user
				if ( isloggedin() && $annotation->userid == $USER->id )  {
					$delid = s( 'del'.$annotation->id );
					echo "<button class='delete-button' id='$delid'>x</button>\n";
				}
				
				// User name (or "me" for current user)
				$displayusername = s( $this->mia_globals->fullname2( $annotation->firstname, $annotation->lastname ) );
				$hiddenusername = '';
				$class = 'user-name';
				
				if ( isloggedin() && $annotation->userid == $USER->id )  {
					$hiddenusername = "<span class='user-name'>$displayusername</span>\n";
					$displayusername = get_string( 'me', ANNOTATION_STRINGS );
					$class = '';
				}
					
				$url = $CFG->wwwroot.$annotation->url;
				if ( MarginaliaHelper::isUrlSafe( $url ) )
					echo "<a class='$class' onclick='setAnnotationUser(\"".s($annotation->userid)."\")' href='".s($url)."'>"
						."$displayusername</a>\n";
				else
					echo "<span class='$class'>$displayusername</span>\n";
				echo $hiddenusername;

				// Link to filter only annotations by this user
				if ( ! $summary->user || $annotation->userid != $summary->user->username )  {
					$tsummary = $summary->derive( array( 'userid' => $annotation->userid) );
					$turl = $tsummary->summary_url( );
					$a->fullname = $this->mia_globals->fullname2( $annotation->firstname, $annotation->lastname );
					echo $this->zoom_link( $tsummary->summary_url( ), get_string( 'zoom_user_hover', ANNOTATION_STRINGS, $a) );
				}
				echo "</td>\n";
				
				echo "</tr>\n";
			}
			
			// Build scripts for individual buttons
			echo "<script type='text/javascript'>\n";
			for ( $annotationi = 0;  $annotationi < count( $annotationa );  ++$annotationi )  {
				$annotation = $annotationa[ $annotationi ];
				if ( AN_USESMARTQUOTE )
				{
					$sqid = s( 'sq'.$annotation->id );
					$tuserid = s( $annotation->userid );
					echo "  addEvent(document.getElementById('$sqid'),'click',function() {"
						."    window.annotationSummary.quote('$sqid','$tuserid'); } );";
				}
				if ( isloggedin() && $annotation->userid == $USER->id )
				{
					$delid = s( 'del'.$annotation->id );
					echo "  addEvent(document.getElementById('$delid'),'click',function() {"
						."    window.annotationSummary.deleteAnnotation('$delid',".(int)$annotation->id."); } );\n";
				}
			}
			echo "</script>\n";
			
			if ( $cururl != null )
				echo "</tbody>\n";
			
			if ( ! AN_SUMMARYHEADINGSTOP )
				$this->show_column_headings( $summary, '' );
			
			echo "</table>\n";
		}
	
		marginalia_summary_lib::show_result_pages( $this->first, $annotation_count, $this->maxrecords, $summary->summary_url('{first}') );
/*
		// Show the list of result pages
		$npages = ceil( $annotation_count / $this->maxrecords );
		if ( $npages > 1 )
		{
			$this_page = 1 + floor( ( $this->first - 1 ) / $this->maxrecords );
			echo "<ol class='result-pages'>\n";
			for ( $i = 1; $i <= $npages;  ++$i )
			{
				if ( $i == $this_page )
					echo "  <li>".$i."</li>\n";
				else
					echo "  <li><a href='".s($summary->summary_url('{first}')."'>".$i."</a></li>\n";
			}
			echo "</ol>\n";
		}
*/		
		//$moodlePath = getMoodlePath( );
		
		// Link for sorting by date or document order
		if ( $summary->orderby == AN_SUMMARY_ORDER_DOCUMENT )
		{
			$tsummary = $summary->derive( array( 'orderby' => AN_SUMMARY_ORDER_TIME ) );
			echo "<p><a href='".s($tsummary->summary_url())."'>".get_string( 'summary_sort_time', ANNOTATION_STRINGS )."</a></p>\n";
		}
		else
		{
			$tsummary = $summary->derive( array( 'orderby' => AN_SUMMARY_ORDER_DOCUMENT ) );
			echo "<p><a href='".s($tsummary->summary_url())."'>".get_string( 'summary_sort_document', ANNOTATION_STRINGS )."</a></p>\n";
		}
		
		// Provide a feed URL.  I don't know how to do authentication for the feed, so for now
		// if a login is required I won't include the feature.
		if ( ! ANNOTATION_REQUIRE_USER )  {
			$tsummary = $summary->derive( array( 'orderby' => AN_SUMMARY_ORDER_TIME ) );
			$turl = $tsummary->get_feed_url( 'atom' );
			echo "<p class='feed' title='".get_string( 'atom_feed', ANNOTATION_STRINGS )
				."'><a href='".s($turl)."'><img border='0' alt='"
				.get_string( 'atom_feed', ANNOTATION_STRINGS )."' src='".s( $CFG->wwwroot )."/pix/i/rss.gif'/>"
				. '</a> '.get_string( 'atom_feed_desc', ANNOTATION_STRINGS )."</p>\n";
		}
		
		print_footer($this->course);

		$logurl = $_SERVER[ 'REQUEST_URI' ];
		$urlparts = parse_url( $logurl );
		$logurl = array_key_exists( 'query', $urlparts ) ? $urlparts[ 'query' ] : null;
		add_to_log( null, 'annotation', 'summary', 'summary.php'.($logurl?'?'.$logurl:''), $summary->desc(null) );

		// Marginalia logging
		if ( $this->logger && $this->logger->is_active() )
			$this->logger->summarizeAnnotations($summary->summary_url(), $summary->desc());
	}
	
	function show_column_headings( $summary, $className )
	{
		echo "<thead class='labels $className'>\n"
			."  <th>".get_string('summary_source_head', ANNOTATION_STRINGS)."</th>\n"
			."  <th>".get_string('summary_quote_head', ANNOTATION_STRINGS)."</th>\n"
			."  <th>".get_string('summary_note_head', ANNOTATION_STRINGS)."</th>\n";
		if ( AN_SUMMARY_ORDER_TIME == $summary->orderby )
			echo "  <th>".get_string('summary_time_head', ANNOTATION_STRINGS)."</th>\n";
		echo "  <th>".get_string('summary_user_head', ANNOTATION_STRINGS)."</th>\n"
			."</thead>\n";
	}
	
	function get_summary_link( $text, $title, $summary )
	{
		$turl = $summary->summary_url( );
		return '<a href="'.s( $turl ).'" title="'.s($title).'">'.s( $text ).'</a>';
	}
	
	function zoom_link( $url, $title )
	{
		return "<a class='zoom' title='".s($title)."' href='".s($url)."'>".AN_FILTERICON_HTML."</a>\n";
	}
}

/*
// Should probably add logging later
if ($cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
	add_to_log($course->id, "forum", "view discussion", "discuss.php?$logparameters", "$discussion->id", $cm->id);
} else {
	add_to_log($course->id, "forum", "view discussion", "discuss.php?$logparameters", "$discussion->id");
}

// Should add preference saving if multiple display modes
if ($mode) {
	set_user_preference("forum_displaymode", $mode);
}

$displaymode = get_user_preferences("forum_displaymode", $CFG->forum_displaymode);
*/

// substr_compare was crashing on my PHP version, so had to write my own :P
function str_startswith( $s1, $s2 )
{
	$s2len = strlen( $s2 );
	if ( strlen( $s1 ) < $s2len )
		return False;
	return substr( $s1, 0, $s2len ) === $s2;
}
	
$urlString = $_SERVER[ 'REQUEST_URI' ];

if ( $_SERVER[ 'REQUEST_METHOD' ] != 'GET' )  {
	header( 'HTTP/1.1 405 Method Not Allowed' );
	header( 'Allow: GET' );
	echo 'grr';
}
else  {
	$first = array_key_exists( 'first', $_GET ) ? (int)$_GET[ 'first' ] : 1;
	$summarypage = new annotation_summary_page( $first );
	$summarypage->show( );
}

