<?php

require_once( $CFG->dirroot.'/local/annotation/config.php' );
require_once( ANNOTATION_DIR.'/annotation_globals.php' );

/**
 * Get an annotations preference value;  if the preference doesn't exist, create it
 * so that the Javascript client will have permission to set it later (to prevent
 * client creation of random preferences, only existing preferences can be set)
 */
function get_annotations_pref( $name, $default )
{
	$value = get_user_preferences( $name, null );
	if ( null == $value ) {
		$value = $default;
		set_user_preference( $name, $default );
	}
	return $value;
}

/**
 * Gets all annotation preferences as an associative array and sets them to defaults
 * in the database if not already present.
 */
function get_all_annotation_prefs( )
{
	return array(
		AN_USER_PREF => get_annotation_userid( ),
		AN_SHOWANNOTATIONS_PREF => get_annotations_pref( AN_SHOWANNOTATIONS_PREF, 'false' ),
		AN_NOTEEDITMODE_PREF => get_annotations_pref( AN_NOTEEDITMODE_PREF, 'freeform' ),
		AN_SPLASH_PREF => get_annotations_pref( AN_SPLASH_PREF, 'true' )
	);
		
}

function get_annotation_userid( )
{
	global $USER;
	// Get the users whose annotations are to be shown
	$annotationuser = get_user_preferences( AN_USER_PREF, null );
	if ( null == $annotationuser )  {
		$annotationuser = isguest() ? null : $USER->username;
		set_user_preference( AN_USER_PREF, $annotationuser );
	}
	return $annotationuser;
}

function get_show_annotations_pref( )
{
	return get_annotations_pref( AN_SHOWANNOTATIONS_PREF, 'false' );
}


function show_marginalia_help( $module )
{
	global $CFG;
	$helptitle = 'Help with Annotations';
    $linkobject = '<span class="helplink"><img class="iconhelp" alt="'.$helptitle.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
    echo link_to_popup_window ('/help.php?module='.s($module).'&amp;file=annotate.html&amp;forcelang=', 'popup',
                                     $linkobject, 400, 500, $helptitle, 'none', true);
}

function show_marginalia_user_dropdown( $refurl )
{
	global $USER;
	$summaryquery = new annotation_summary_query( $refurl, null, null, null );
	$userlist = get_records_sql( $summaryquery->list_users_sql( ) );
	$annotationuserid = get_annotation_userid( );
	$showannotationspref = get_show_annotations_pref( ) == 'true';
	
	echo "<select name='anuser' id='anuser' onchange='window.moodleMarginalia.changeAnnotationUser(this,\"$refurl\");'>\n";
	$selected = $showannotationspref ? '' : " selected='selected' ";
	echo " <option $selected value=''>".get_string('hide_annotations',ANNOTATION_STRINGS)."</option>\n";
	if ( ! isguest() )  {
		$selected = ( $showannotationspref && ( $USER->username == $annotationuserid ? "selected='selected' " : '' ) )
			? " selected='selected' " : '';
		echo " <option $selected"
			."value='".s($USER->username)."'>".get_string('my_annotations',ANNOTATION_STRINGS)."</option>\n";
	}
	if ( $userlist )  {
		foreach ( $userlist as $user )  {
			if ( $user->userid != $USER->username )  {
				$selected = ( $showAnnotationspref && ( $user->userid == $annotationuserid ? "selected='selected' ":'' ) )
					? " selected='selected' " : '';
				echo " <option $selected"
					."value='".s($user->userid)."'>".s($user->firstname.' '.$user->lastname)."</option>\n";
			}
		}
	}
	// Show item for all users
	if ( true )  {
		$selected = ( $showannotationspref && ( '*' == $annotationuserid ? "selected='selected' ":'' ) )
			? " selected='selected' " : '';
		echo " <option $selected value='*'>".get_string('all_annotations',ANNOTATION_STRINGS)."</option>\n";
	}
	echo "</select>\n";	
}


function show_marginalia_summary_link( $refurl, $userid )
{
	global $CFG, $course;
	$summaryurl = ANNOTATION_PATH.'/summary.php?user='.urlencode($userid)
		."&url=".urlencode( $refurl );
	echo " <a id='annotation-summary-link' href='".s($summaryurl)."'"
		. " title='".s(get_string('summary_link_title',ANNOTATION_STRINGS))
		."'>".s(get_string('summary_link',ANNOTATION_STRINGS))."</a>\n";

	echo "<a id='annotation-editkeywords-link' href='".ANNOTATION_PATH.'/tags.php?course='.$course->id."'"
		. " title='".s(get_string( 'edit_keywords_link', ANNOTATION_STRINGS ))
		."'>Tags</a>\n";
}


/**
 * Return HTML for insertion in the head of a document to include Marginalia Javascript
 * and initialize Marginalia.  If necessary, also creates relevant user preferences 
 * (necessary for Marginalia to function correctly).
 */
function marginalia_header_html( )
{
	global $CFG, $USER;
	
	$meta = "<link rel='stylesheet' type='text/css' href='".ANNOTATION_PATH."/marginalia/marginalia.css'/>\n"
		. "<link rel='stylesheet' type='text/css' href='".ANNOTATION_PATH."/annotation-styles.php'/>\n";
	$anscripts = listMarginaliaJavascript( );
	for ( $i = 0;  $i < count( $anscripts );  ++$i )
		require_js( ANNOTATION_PATH.'/marginalia/'.$anscripts[ $i ] );
	require_js( array(
		ANNOTATION_PATH.'/marginalia-config.js',
		ANNOTATION_PATH.'/marginalia-strings.js',
		ANNOTATION_PATH.'/smartquote.js',
		ANNOTATION_PATH.'/MoodleMarginalia.js' ) );

	// Bits of YUI
	$meta .= "<link type='text/css' rel='stylesheet' href='$CFG->wwwroot/lib/yui/autocomplete/assets/skins/sam/autocomplete.css'/>\n";
	require_js( array(
		$CFG->wwwroot.'/lib/yui/yahoo-dom-event/yahoo-dom-event.js',
//		$CFG->wwwroot.'/lib/yui/datasource/datasource-min.js',
		$CFG->wwwroot.'/lib/yui/autocomplete/autocomplete-min.js' ) );
	
	return $meta;
}


function marginalia_init_html( $refurl )
{
	global $CFG, $USER;
	
	$prefs = get_all_annotation_prefs( );
	
	$showannotationspref = $prefs[ AN_SHOWANNOTATIONS_PREF ];
	$annotationuser = $prefs[ AN_USER_PREF ];
	$showsplashpref = $prefs[ AN_SPLASH_PREF ];
	
	// Build a string of initial preference values for passing to Marginalia
	$first = true;
	$sprefs = '';
	foreach ( array_keys( $prefs ) as $name )
	{
		$value = $prefs[ $name ];
		if ( $first )
			$first = false;
		else
			$sprefs .= "\n, ";
		$sprefs .= "'".s( $name )."': '".s( $prefs[ $name ] )."'";
	}
	$sprefs = '{ '.$sprefs.' }';;
	
    $sitecontext = get_context_instance(CONTEXT_SYSTEM);
    $allowAnyUserPatch = AN_ADMINUPDATE && (
		has_capability( 'moodle/legacy:admin', $sitecontext ) or has_capability( 'moodle/site:doanything', $sitecontext) );
	
	$meta = "<script language='JavaScript' type='text/javascript'>\n"
		."function myOnload() {\n"
		." var moodleRoot = '".s($CFG->wwwroot)."';\n"
		." var url = '".s($refurl)."';\n"
		.' var userId = \''.s($USER->username)."';\n"
		.' moodleMarginalia = new MoodleMarginalia( url, moodleRoot, userId, '.$sprefs.', {'."\n";
	if ( $showsplashpref == 'true' )
		$meta .= '  splash: \''.s(get_string('splash',ANNOTATION_STRINGS)).'\'';
	$meta .= ' useSmartquote: '.s(AN_USESMARTQUOTE)
		.",\n".' allowAnyUserPatch: '.($allowAnyUserPatch ? 'true' : 'false' )
		.",\n smartquoteIcon: '".AN_SMARTQUOTEICON."'"
		.'  } );'."\n"
		." moodleMarginalia.onload();\n"
		."}\n"
		."addEvent(window,'load',myOnload);\n"
		."</script>\n";
	return $meta;	
}

/**
 * Deletes all annotations of a specific user
 * This is here rather than in the annotation code so that not everything will have to
 * include the annotation code.
 *
 * @param int $username the name of the user (note, the username, *not* the userid!)
 * @return boolean
 */
function annotations_delete_user( $userid )
{
	return delete_records( 'annotation', 'userid', $userid );
}

/**
 * Change a user name in the annotations table
 * The annotation engine is intended to be independent of Moodle internals, so it uses
 * username rather than user ID.  So annotations need to be updated when a user name changes.
 *
 * @param string $oldname the current name of the user in the annotation table
 * @param string $newname the new name of the user in the annotation table
 * @return boolean
 */
function annotations_update_username( $oldname, $newname )
{
	global $CFG, $db;
	
	$query = "UPDATE {$CFG->prefix}annotation SET userid='".addslashes($newname)
		."' WHERE userid='".addslashes($oldname)."'";
	if ( $db->Execute( $query ) )
		return true;
	else
		return false;
}
