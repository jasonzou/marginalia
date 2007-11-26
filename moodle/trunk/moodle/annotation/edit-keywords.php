<?php

 // summary.php
 // Part of Marginalia annotation for Moodle
 // See www.geof.net/code/annotation/ for full source and documentation.

 // Display a summary of all annotations for the current user

require_once( "../config.php" );
require_once( "marginalia-php/MarginaliaHelper.php" );
require_once( 'marginalia-php/Keyword.php' );
require_once( 'config.php' );
require_once( 'AnnotationGlobals.php' );
require_once( 'KeywordsDB.php' );

global $CFG;

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
elseif ( ! AN_EDITABLEKEYWORDS )
{
	header( 'HTTP/1.1 501 Not Implemented' );
	echo "This Moodle installation does not support keywords";
}
else
{
	$errorPage = array_key_exists( 'error', $_GET ) ? $_GET[ 'error' ] : null;

	$keywords = AnnotationKeywordsDB::listKeywords( $USER->id );
	
	$meta
		= "<link type='text/css' rel='stylesheet' href='edit-keywords.css'/>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/3rd-party/cssQuery.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/3rd-party/cssQuery-standard.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/3rd-party.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/log.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia-config.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/domutil.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/rest-keywords.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='marginalia/rest-annotate.js'></script>\n"
		. "<script language='JavaScript' type='text/javascript' src='edit-keywords.js'></script>\n"
		. "<script language='Javascript' type='text/javascript'>\n"
		. " var serviceRoot = '".htmlspecialchars($CFG->wwwroot).'/annotation'."';\n"
		. " var annotationKeywords = [\n";
	if ( $keywords )
	{
		for ( $i = 0;  $i < count( $keywords );  ++$i )
		{
			$keyword = $keywords[ $i ];
			if ( $i > 0 )
				$meta .= ", ";
			$meta .= "new Keyword('".htmlspecialchars($keyword->name)."', '".htmlspecialchars($keyword->description)."')\n";
		}
	}
	$meta .= "];\n"
		. "addEvent( window, 'load', keywordsOnload );\n"
		. "</script>";
	
	$navtail = get_string( 'summary_title', ANNOTATION_STRINGS );
	print_header(get_string( 'edit_keywords_title', ANNOTATION_STRINGS ), null, "$navtail", "", $meta, true, "", null);
	
	// Keyword list will go here:
	echo "<table id='keywords'>\n";
	echo " <thead>\n";
	echo "  <tr><th>".get_string('keyword_column',ANNOTATION_STRINGS)."</th><th>".get_string('keyword_desc_column',ANNOTATION_STRINGS)."</th></tr>\n";
	echo " </thead>\n";
	echo " <tbody>\n";
	
	// Space to insert new keywords
	echo "  <tr class='create'>\n";
	echo "    <td class='name'><input type='text' id='new-keyword-name' name='new-keyword-name'/></td>\n";
	echo "    <td class='description'><input type='text' id='new-keyword-desc' name='new-keyword-desc'/></td>\n";
	echo "    <td><input id='new-keyword-button' type='submit' value='".get_string('create_keyword_button',ANNOTATION_STRINGS)."'/></td>\n";
	echo "  </tr>\n";
	echo " </tbody>\n";
	echo "</table>\n";
	
	echo "<p>Pages with annotations must be reloaded to reflect changes to tags.</p>";
	
	echo "<fieldset id='replace'>\n";
	echo " <legend>".get_string('note_replace_legend',ANNOTATION_STRINGS)."</legend>\n";
	echo " <label for='old-note'>".get_string('note_replace_old',ANNOTATION_STRINGS).":</label><input id='old-note' type='text'/>\n";
	echo " <label for='new-note'>".get_string('note_replace_new',ANNOTATION_STRINGS).":</label><input id='new-note' type='text'/>\n";
	echo " <button>".get_string('note_replace_button',ANNOTATION_STRINGS)."</button>\n";
	echo " <p id='replace-count-prompt'>".get_string('note_update_count',ANNOTATION_STRINGS)."<span id='replace-count'/></p>\n";
	echo "</fieldset>\n";
	
	print_footer(null);

	$logUrl = $_SERVER[ 'REQUEST_URI' ];
	$urlParts = parse_url( $logUrl );
	$logUrl = array_key_exists( 'query', $urlParts ) ? $urlParts[ 'query' ] : null;
	add_to_log( null, 'annotation', 'summary', 'edit-keywords.php' );
}

?>
