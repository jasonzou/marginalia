<?php

/**
 * Get an annotations preference value;  if the preference doesn't exist, create it
 * so that the Javascript client will have permission to set it later (to prevent
 * client creation of random preferences, only existing preferences can be set)
 */
function getAnnotationsPref( $name, $default )
{
	$value = get_user_preferences( $name, null );
	if ( null == $value )
	{
		$value = $default;
		set_user_preference( $name, $default );
	}
	return $value;
}

/**
 * Gets all annotation preferences as an associative array and sets them to defaults
 * in the database if not already present.
 */
function getAllAnnotationsPrefs( )
{
	return array(
		AN_USERPREF => getAnnotationUsername( ),
		AN_SHOWANNOTATIONS_PREF => getAnnotationsPref( AN_SHOWANNOTATIONS_PREF, 'false' ),
		AN_NOTEEDITMODE_PREF => getAnnotationsPref( AN_NOTEEDITMODE_PREF, 'freeform' ),
		AN_SPLASH_PREF => getAnnotationsPref( AN_SPLASH_PREF, 'true' ),
		AN_SMARTCOPY_PREF => getAnnotationsPref( SMARTCOPY_PREF, 'false' )
	);
		
}

function getAnnotationUsername( )
{
	// Get the users whose annotations are to be shown
	$annotationUser = get_user_preferences( AN_USER_PREF, null );
	if ( null == $annotationUser )
	{
		$annotationUser = isguest() ? null : $USER->username;
		set_user_preference( AN_USER_PREF, $annotationUser );
	}
	return $annotationUser;
}

function getShowAnnotationsPref( )
{
	return getAnnotationsPref( AN_SHOWANNOTATIONS_PREF, 'false' );
}


function showMarginaliaHelp( $module )
{
	global $CFG;
	$helpTitle = 'Help with Annotations';
    $linkobject = '<span class="helplink"><img class="iconhelp" alt="'.$helpTitle.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
    echo link_to_popup_window ('http://localhost/moodle/help.php?module='.htmlspecialchars($module).'&amp;file=annotate.html&amp;forcelang=', 'popup',
                                     $linkobject, 400, 500, $helpTitle, 'none', true);
}

function showMarginaliaUserDropdown( $refUrl )
{
	global $USER;
	$summaryQuery = new AnnotationSummaryQuery( $refUrl, null, null, null );
	$userList = get_records_sql( $summaryQuery->listUsersSql( ) );
	$annotationUser = getAnnotationUsername( );
	$showAnnotationsPref = getShowAnnotationsPref( );
	
	echo "<select name='anuser' id='anuser' onchange='window.moodleMarginalia.changeAnnotationUser(this,\"$refUrl\");'>\n";
	echo " <option ".($showAnnotationsPref!='true'?"selected='selected' ":'')
		."value=''>".get_string('hide_annotations',ANNOTATION_STRINGS)."</option>\n";
	if ( ! isguest() )
	{
		echo " <option ".($showAnnotationsPref=='true'&&$USER->username==$annotationUser?"selected='selected' ":'')
			."value='".htmlspecialchars($USER->username)."'>".get_string('my_annotations',ANNOTATION_STRINGS)."</option>\n";
	}
	if ( $userList )
	{
		foreach ( $userList as $user )
		{
			if ( $user->username != $USER->username )
			{
				echo " <option ".($user->username==$annotationUser?"selected='selected' ":'')
					."value='".htmlspecialchars($user->username)."'>".htmlspecialchars($user->firstname.' '.$user->lastname)."</option>\n";
			}
		}
	}
	echo "</select>\n";	
}


function showMarginaliaSummaryLink( $refUrl, $username )
{
	global $CFG;
	$summaryUrl = $CFG->wwwroot."/annotation/summary.php?user=".urlencode($username)
		."&url=".urlencode( $refUrl );
	echo " <a id='annotation-summary-link' href='".htmlspecialchars($summaryUrl)."'"
		. " title='".htmlspecialchars(get_string('summary_link_title',ANNOTATION_STRINGS))
		."'>".htmlspecialchars(get_string('summary_link',ANNOTATION_STRINGS))."</a>\n";
}


/**
 * Return HTML for insertion in the head of a document to include Marginalia Javascript
 * and initialize Marginalia.  If necessary, also creates relevant user preferences 
 * (necessary for Marginalia to function correctly).
 */
function marginaliaHeaderHtml( $refUrl, $allowSmartcopy )
{
	global $CFG, $USER;
	
	$prefs = getAllAnnotationPrefs( );
	
	$showAnnotationsPref = $prefs[ AN_SHOWANNOTATIONS_PREF ];
	$annotationUser = $prefs[ AN_USER_PREF ];
	$showSplashPref = $prefs[ AN_SPLASH_PREF ];
	$allowSmartcopy = $prefs[ SMARTCOPY_PREF ];
	
	// Build a string of initial preference values for passing to Marginalia
	$first = true;
	$sPrefs = '';
	foreach ( array_keys( $prefs ) as $name )
	{
		$value = $prefs[ $name ];
		if ( $first )
			$first = false;
		else
			$sPrefs .= "\n, ";
		$sPrefs .= "'".htmlspecialchars( $name )."': '".htmlspecialchars( $prefs[ $name ] )."'";
	}
	$sPrefs = '{ '.$sPrefs.' }';;
	
	$meta = "<link rel='stylesheet' type='text/css' href='$CFG->wwwroot/annotation/marginalia/marginalia.css'/>\n"
		. "<link rel='stylesheet' type='text/css' href='$CFG->wwwroot/annotation/annotation-styles.php'/>\n";
	$anScripts = listMarginaliaJavascript( );
	for ( $i = 0;  $i < count( $anScripts );  ++$i )
		$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/marginalia/".$anScripts[$i]."'></script>\n";	
	$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/marginalia/smartcopy.js'></script>\n";	
	$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/marginalia-config.js'></script>\n";
	$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/marginalia-strings.js'></script>\n";
	$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/MoodleMarginalia.js'></script>\n";
	$meta .= "<script language='JavaScript' type='text/javascript'>\n";
	$meta .= "function myOnload() {\n";
	$meta .= " var moodleRoot = '".htmlspecialchars($CFG->wwwroot)."';\n";
	$meta .= " var url = '".htmlspecialchars($refUrl)."';\n";
	$meta .= ' var username = \''.htmlspecialchars($USER->username)."';\n";
	$meta .= ' moodleMarginalia = new MoodleMarginalia( url, moodleRoot, username, prefs );'."\n";
		. '  enableSmartcopy: '.('true'==$smartcopyPref?'true':'false')."\n"
		. '  , showAnnotations: '.('true'==$showAnnotationsPref?'true':'false')."\n"
		. '  , anuser: \''.htmlspecialchars($annotationUser)."'\n";
		. "  , prefs: $sPrefs\n";
		. ', {'."\n"
	if ( $showSplashPref == 'true' )
		$meta .= '  splash: \''.htmlspecialchars(get_string('splash',ANNOTATION_STRINGS)).'\'';
	$meta .= '  } );'."\n";
	$meta .= "/* showSplashPref=$showSplashPref */\n";
	$meta .= " moodleMarginalia.onload();\n";
	$meta .= "}\n";
	$meta .= "addEvent(window,'load',myOnload);\n";
	$meta .= "</script>\n";
	
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
function annotations_delete_user( $username )
{
	return delete_records( 'annotation', 'userid', $username );
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
 
?>
