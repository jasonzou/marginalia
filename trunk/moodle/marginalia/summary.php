<?PHP

 // summary.php
 // Part of Marginalia annotation for Moodle
 // See www.geof.net/code/annotation/ for full source and documentation.

 // Display a summary of all annotations for the current user

    require_once( "../config.php" );
	require_once( "lib.php" );

   if ($CFG->forcelogin) {
        require_login();
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

	$urlString = $_SERVER[ 'REQUEST_URI' ];
	
	if ( $_SERVER[ 'REQUEST_METHOD' ] != 'GET' )
	{
		header( 'HTTP/1.1 405 Method Not Allowed' );
		header( 'Allow: GET' );
	}
	else
	{
		$errorPage = array_key_exists( 'error', $_GET ) ? $_GET[ 'error' ] : null;
		$summaryUrl = $_GET[ 'url' ];
		$excludeFields = array_key_exists( 'exclude', $_GET ) ? $_GET[ 'exclude' ] : '';
		$excludeFields = split( ' ', $excludeFields );
		$possibleExcludeFields = array( 'quote', 'note', 'source', 'user', 'controls' );
		$nCols = 5 - count( array_intersect( $excludeFields, $possibleExcludeFields) );
		
		$searchQuery = array_key_exists( 'q', $_GET ) ? $_GET[ 'q' ] : null;
		$searchUser = array_key_exists( 'u', $_GET ) ? $_GET[ 'u' ] : null;
		$searchOf = array_key_exists( 'search-of', $_GET ) ? $_GET[ 'search-of' ] : null;

		$query = new AnnotationSummaryQuery( $summaryUrl, $searchUser, $searchOf, $searchQuery );
		if ( $query->error )
		{
			header( 'HTTP/1.1 400 Bad Request' );
			echo '<h1>400 Bad Request</h1>'.htmlspecialchars($query->error);
		}
		elseif ( ! is_safe_url( $summaryUrl ) )
		{
			header( 'HTTP/1.1 400 Bad Request' );
			echo '<h1>400 Bad Request</h1>Bad url parameter';
		}
		else
		{
			// Get the course.  This can't be passed as a GET parameter because this URL could be via the
			// Atom feed, and the Atom feed is generated exclusively by annotation code which doesn't know
			// that much about Moodle.  So the handler has to query it based on a discussion ID or the like.
			$course = null;
			$courseId = $query->handler->courseId;
			if ( null != $courseId )
			{
				if (! $course = get_record( "course", "id", $courseId )) {
					error( "Course ID is incorrect - discussion is faulty ");
				}
				// Ok, now this is probably very wrong.  If the user looks for annotations within a course,
				// it requires a login.  Without the course (i.e. in a more general search), it doesn't!
				// I would eleminate this, but I don't really know how Moodle security works. #geof#
				if ($course->category) {
					require_login($course->id);
				}
			}

			// Keep for debugging:
			//echo "<h2>Query</h2><pre>".$query->sql( 'a.id' )."</pre>";
			
			// Show header
			$navtail = get_string( 'summary_title', 'annotate' );
			$navmiddle = "";
			$meta
				= "<script language='JavaScript' type='text/javascript' src='summary.js'></script>\n"
				. "<script language='JavaScript' type='text/javascript' src='log.js'></script>\n"
				. "<script language='JavaScript' type='text/javascript' src='config.js'></script>\n"
				. "<script language='JavaScript' type='text/javascript' src='domutil.js'></script>\n"
				. "<script language='JavaScript' type='text/javascript' src='rest-annotate.js'></script>\n"
				. "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/rest-prefs.js'></script>\n"
				. "<script language='JavaScript' type='text/javascript'>\n"
				. "summaryInit('".htmlspecialchars($USER->username)."');\n"
				. "annotationInit('".htmlspecialchars($CFG->wwwroot)."', '".htmlspecialchars($USER->username)."',true);\n"
				. "preferenceInit('".htmlspecialchars($CFG->wwwroot)."');\n"
				. "</script>\n"
				. "<link rel='stylesheet' type='text/css' href='$CFG->wwwroot/annotation/summary-styles.php'/>\n";
	
			
			if (null != $course && $course->category)
			{
				print_header("$course->shortname: ".get_string( 'summary_title', 'annotate' ), "$course->fullname",
					"<A HREF=$CFG->wwwroot/course/view.php?id=$course->id>$course->shortname</A> -> $navtail",
					"", $meta, true, "", navmenu($course));
			}
			elseif ( null != $course )
			{
				print_header("$course->shortname: ".get_string( 'summary_title', 'annotate' ), "$course->fullname",
					"$navtail", "", $meta, true, "", navmenu($course));
			}
			else
			{
				print_header(get_string( 'summary_title', 'annotate' ), null, "$navtail", "", $meta, true, "", null);
			}
	
			// print search header
			//  * my annotations
			//  * shared annotations
			//  * instructor annotations
			//  * annotations of my work
			echo "<form id='annotation-search' method='get' action='summary.php'>\n";
			echo "<fieldset>\n";
			echo "<label for='search-of'>".get_string( 'prompt_find', 'annotate' )."</label>\n";
			if ( isguest() )
			{
				echo "<input type='hidden' name='search_of' id='search_of' value='' />\n";
				echo get_string( 'search_of_all', 'annotate' ).' ';
			}
			else
			{
				echo "<select name='search-of' id='search-of''>\n";
				echo " <option value=''".(''==$searchOf?"selected='selected'":'').'>'.get_string( 'search_of_all', 'annotate' )."</option>\n";
				echo " <option value='".htmlspecialchars($USER->username)."'".($searchOf==$USER->username?"selected='selected'":'').'>'.get_string( 'search_of_self', 'annotate')."</option>\n";
				echo "</select>\n";
			}
			echo "<label for='u'>".get_string( 'prompt_by', 'annotate' )."</label>\n";
			echo "<select name='u' id='u''>\n";
			echo " <option value='' ".(''==$searchBy?"selected='selected'":'').'>'.get_string( 'search_by_all', 'annotate' )."</option>\n";
			if ( ! isguest() )
				echo " <option value='".htmlspecialchars($USER->username)."' ".($searchBy==$USER->username?"selected='selected'":'')."'>".get_string( 'search_by_self', 'annotate')."</option>\n";
			echo " <option value='*teachers'".('*teachers'==$searchBy?"selected='selected'":'').'>'.get_string( 'search_by_teachers', 'annotate' )."</option>\n";
			echo " <option value='*students'".('*students'==$searchBy?"selected='selected'":'').'>'.get_string( 'search_by_students', 'annotate' )."</option>\n";
			echo "</select>\n";
			echo "<label for='search-text'>".get_string( 'search_text', 'annotate' )."</label>\n";
			echo "<input type='text' id='search-text' name='q' value='".htmlspecialchars($searchQuery)."'/>\n";
			echo "<input type='submit' value='".get_string( 'go' )."'/>\n";
			echo "<input type='hidden' name='url' value='".htmlspecialchars($summaryUrl)."'/>\n";
			echo "</fieldset>\n";
			echo "</form>";
			
			// If this page is an error, explain what it's about
			if ( 'range-mismatch' == $errorPage )
			{
				echo "<p class='error'><em class='range-error'>!</em>".get_string( 'summary_range_error', 'annotate' )."</p>\n";
			}
			
			echo "<p id='query'>".get_string( 'prompt_search_desc', 'annotate' ).' '.htmlspecialchars($query->desc(null)).":</p>\n";
			
			// Display individual annotations
			// Dunno if the range sorting is working
			$annotations = get_records_sql( $query->sql( 'section_type, section_name, quote_title, substring(a.range,0,locate(\'.\',a.range))' ) );
			$curSection = null;
			$curSectionType = null;
			$curUser = null;
			$curUrl = null;
			// make sure some records came back
			if ( null != $annotations )
			{
				echo "<table cellspacing='0' class='annotations'>";
				foreach ( $annotations as $annotation ) {
					// Display a heading for each new URL
					if ( $annotation->section_type != $curSectionType || $annotation->section_url != $curSection ) {
						if ( $curSection != null )
							echo "</tbody>\n";
						echo "<thead><tr><th colspan='$nCols'>";
							$a->section_type = htmlspecialchars( $annotation->section_type );
							echo '<h3>'.htmlspecialchars($annotation->section_type).'</h3>: '
								. "<a href='".htmlspecialchars($annotation->section_url)."' title='".get_string( 'prompt_section', 'annotate', $a )."'>" . htmlspecialchars( $annotation->section_name ) . "</a>";
						echo "</th></tr></thead><tbody>\n";
						$curSection = $annotation->section_url;
						$curSectionType = $annotation->section_type;
						$curUser = $annotation->userid;
						$curUrl = null;
					}
					
					// For each new url, display the title and author
					if ( $annotation->url != $curUrl ) { //|| $annotation->userid != $curUser ) {
						echo "<tr class='fragment first'>";
						if ( ! in_array( 'source', $excludeFields ) )
						{
							$url = $CFG->wwwroot.$annotation->url;
							echo "<th>";
							if ( is_safe_url( $url ) )
							{
								$a->row_type = htmlspecialchars( $annotation->row_type );
								$a->author = htmlspecialchars( $annotation->quote_author );
								echo "<a href='".htmlspecialchars($url)."' title='".get_string( 'prompt_row', 'annotate', $a)."'>";
								echo htmlspecialchars( $annotation->quote_title ) . '</a>';
							}
							echo "</th>\n";
						}
						$curUrl = $annotation->url;
						$curUser = $annotation->userid;
					}
					else
					{
						echo "<tr>";
						if ( ! in_array( 'source', $excludeFields ) )
							echo "<td class='fragment'></td>\n";
					}
					
					// Show the quoted text
					if ( ! in_array( 'quote', $excludeFields ) )
					{
						echo "<td class='quote'>";
						echo htmlspecialchars( $annotation->quote );
						echo "</td>\n";
					}
					
					// Show the note
					if ( ! in_array( 'note', $excludeFields ) )
						echo "<td class='note'>" . htmlspecialchars( $annotation->note ) . "&#160;</td>\n";
	
					// Show edit controls or the user who created the annotation
					if ( ! in_array( 'controls', $excludeFields ) || ! in_array( 'user', $excludeFields ) )
					{
						if ( ! in_array( 'controls', $excludeFields ) && $annotation->userid == $USER->username )
						{
							echo "<td class='controls'>";
							$AN_SUN_SYMBOL = '&#9675;';
							$AN_MOON_SYMBOL = '&#9670;';
							echo "<button class='share-button access-{$annotation->access}' onclick='shareAnnotationPublicPrivate(this,$annotation->id);'>"
								.('public' == $annotation->access ? $AN_SUN_SYMBOL : $AN_MOON_SYMBOL )."</button>";
							/* The following code supports additional access modes, but has been disabled
							 * for now:
							echo "<select onchange='shareAnnotation(this,$annotation->id)'>\n";
							echo "<option value='private'".('private'==$annotation->access?"selected='selected'":'').'>'.get_string( 'private', 'annotate' )."</option>\n";
							echo "<option value='author'".('author'==$annotation->access?"selected='selected'":'').'>'.get_string( 'author', 'annotate' )."</option>\n";
							echo "<option value='teacher'".('teacher'==$annotation->access?"selected='selected'":'').'>'.get_string( 'teacher', 'annotate' )."</option>\n";
							echo "<option value='author teacher'".('author teacher'==$annotation->access?"selected='selected'":'').'>'.get_string( 'author+teacher', 'annotate' )."</option>\n";
							echo "<option value='public'".('public'==$annotation->access?"selected='selected'":'').'>'.get_string( 'public', 'annotate' )."</option>\n";
							*/
							echo "</select>\n";
							echo "<button class='delete-button' onclick='deleteAnnotation($annotation->id);'>x</button>";
							echo "</td>\n";
						}
						else if ( ! in_array( 'user', $excludeFields ) )
						{
							echo "<td class='anuser'>";
							$url = $CFG->wwwroot.$annotation->url;
							//if ( $annotation->userid && $annotation->userid != $USER->username )
							//	$url .= "&anuser=".$annotation->userid;
							if ( is_safe_url( $url ) )
							{
								echo "<a onclick='setAnnotationUser(\"".htmlspecialchars($annotation->userid)."\")' href='".htmlspecialchars($url)."'>"
									.htmlspecialchars($annotation->note_author)."</a>";
							}
							echo "</td>\n";
						}
					}
					
					echo "</tr>\n";
				}
				if ( $curUrl != null )
					echo "</tbody>\n";
				echo "</table>\n";
				// print the page content
			
		//		echo "<p><a href='summary.php?course=$courseId'>Show all of my annotations for this course</a></p>\n";
			
			}
		}
		
		//$moodlePath = getMoodlePath( );
		
		// Show link to parent search
		if ( null != $query->parentSummaryTitle() )
		{
			$excludeFields = join( '+', $excludeFields );
			$turl = getAnnotationSummaryUrl( $query->parentSummaryUrl(), $searchUser, $searchOf, $searchQuery );
	
			echo "<p><a href='".htmlspecialchars("$turl&exclude=$excludeFields")."'>Show "
				. htmlspecialchars($query->desc($query->parentSummaryTitle()))."</a></p>\n";
		}
		
		// Provide a feed URL.  I don't know how to do authentication for the feed, so for now
		// if a login is required I won't include the feature.
		if ( ! ANNOTATION_REQUIRE_USER )
		{
			$turl = getAnnotationFeedUrl( $summaryUrl, $searchUser, $searchOf, $searchQuery, 'atom' );
			echo "<p class='feed' title='".get_string( 'atom_feed', 'annotate' )."'><a href='".htmlspecialchars($turl)."'><img border='0' alt='".get_string( 'atom_feed', 'annotate' )."' src='$CFG->wwwroot/annotation/images/atomicon.gif'/>"
				. '</a> '.get_string( 'atom_feed_desc', 'annotate' )."</p>\n";
		}
		
		echo '<p id="smartcopy-help"><span class="tip">'.get_string('tip', 'annotate').'</span> '
			.get_string( 'smartcopy_help', 'annotate' )."</p>\n";
		
		print_footer($course);

		$logUrl = $_SERVER[ 'REQUEST_URI' ];
		$urlParts = parse_url( $logUrl );
		$logUrl = array_key_exists( 'query', $urlParts ) ? $urlParts[ 'query' ] : null;
		add_to_log( null, 'annotation', 'summary', 'summary.php'.($logUrl?'?'.$logUrl:''), $query->desc(null) );
	}
	
function getAnnotationSummaryUrl( $url, $searchUser, $searchOf, $searchQuery )
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
}

?>
