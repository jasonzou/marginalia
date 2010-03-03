<?php

require_once( $CFG->dirroot.'/blocks/marginalia/config.php' );
require_once( ANNOTATION_DIR.'/annotation_globals.php' );

class moodle_marginalia
{
	/**
	 * Get an annotations preference value;  if the preference doesn't exist, create it
	 * so that the Javascript client will have permission to set it later (to prevent
	 * client creation of random preferences, only existing preferences can be set)
	 */
	public static function get_pref( $name, $default )
	{
		$value = get_user_preferences( $name, null );
		if ( null == $value ) {
			$value = $default;
			set_user_preference( $name, $default );
		}
		return $value;
	}
	
	/**
	 * Get the sheet whose annotations are to be shown
	 */
	public static function get_sheet( )
	{
		return moodle_marginalia::get_pref( AN_SHEET_PREF, 'public' );
	}
	
	public static function get_show_annotations_pref( )
	{
		return moodle_marginalia::get_pref( AN_SHOWANNOTATIONS_PREF, 'false' );
	}
	
	/**
	 * Return HTML for insertion in the head of a document to include Marginalia Javascript
	 * and initialize Marginalia.  If necessary, also creates relevant user preferences 
	 * (necessary for Marginalia to function correctly).
	 */
	public static function header_html( )
	{
		global $CFG, $USER;
		
		$anscripts = listMarginaliaJavascript( );
		for ( $i = 0;  $i < count( $anscripts );  ++$i )
			require_js( ANNOTATION_PATH.'/marginalia/'.$anscripts[ $i ] );
		require_js( array(
			ANNOTATION_PATH.'/marginalia-config.js',
			ANNOTATION_PATH.'/marginalia-strings.js',
			ANNOTATION_PATH.'/smartquote.js',
			ANNOTATION_PATH.'/rest-log.js',
			ANNOTATION_PATH.'/MoodleMarginalia.js' ) );
	
		// Bits of YUI
		require_js( array(
			$CFG->wwwroot.'/lib/yui/yahoo-dom-event/yahoo-dom-event.js',
	//		$CFG->wwwroot.'/lib/yui/datasource/datasource-min.js',
			$CFG->wwwroot.'/lib/yui/autocomplete/autocomplete-min.js' ) );
		
		$meta = "<link rel='stylesheet' type='text/css' href='".s($CFG->wwwroot)."/lib/yui/autocomplete/assets/skins/sam/autocomplete.css'/>\n"
			."<link rel='stylesheet' type='text/css' href='".s(ANNOTATION_PATH)."/marginalia/marginalia.css'/>\n"
			."<link rel='stylesheet' type='text/css' href='".s(ANNOTATION_PATH)."/annotation-styles.php'/>\n";
/*		Hack for attempt to get this working with the block:
		$meta = "<script type='text/javascript'>\n"
			."  domutil.loadStylesheet( '".s($CFG->wwwroot)."/lib/yui/autocomplete/assets/skins/sam/autocomplete.css');\n"
			."  domutil.loadStylesheet( '".ANNOTATION_PATH.'/marginalia/marginalia.css'."');\n"
			."  domutil.loadStylesheet( '".ANNOTATION_PATH.'/annotation-styles.php'."');\n"
			."</script>\n";
*/			
		return $meta;
	}
	
	/**
	 * Generate the content HTML.  This contains the Javascript necessary to
	 * initialized Marginalia.  It also require_js's a number of Javascript files.
	 */
	public static function init_html( $refurl, $subscribe=false )
	{
		global $CFG, $USER, $course;
		
		// Get all annotation preferences as an associative array and sets them to defaults
		// in the database if not already present.
		$prefs = array(
			AN_SHEET_PREF => moodle_marginalia::get_pref( AN_SHEET_PREF, 'public' ),
			AN_SHOWANNOTATIONS_PREF => moodle_marginalia::get_pref( AN_SHOWANNOTATIONS_PREF, 'false' ),
			AN_NOTEEDITMODE_PREF => moodle_marginalia::get_pref( AN_NOTEEDITMODE_PREF, 'freeform' ),
			AN_SPLASH_PREF => moodle_marginalia::get_pref( AN_SPLASH_PREF, 'true' )
		);
		
		$showannotationspref = $prefs[ AN_SHOWANNOTATIONS_PREF ];
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
		
		// URLs used by drop-down menu handlers
		$summaryurl = ANNOTATION_PATH.'/summary.php?user='.(int)$USER->id.'&url='.urlencode( $refurl );
		$tagsurl = ANNOTATION_PATH.'/tags.php?course='.(int)$course->id;
		$logurl = ANNOTATION_PATH.'/activity_log.php?course='.(int)$course->id.'&limit=100';

		$sitecontext = get_context_instance(CONTEXT_SYSTEM);
		$allowAnyUserPatch = AN_ADMINUPDATE && (
			has_capability( 'moodle/legacy:admin', $sitecontext ) or has_capability( 'moodle/site:doanything', $sitecontext) );
		
		$meta = "<script language='JavaScript' type='text/javascript' defer='defer'>\n"
			."function myOnload() {\n"
			." var moodleRoot = '".s($CFG->wwwroot)."';\n"
			." var annotationPath = '".s(ANNOTATION_PATH)."';\n"
			." var url = '".s($refurl)."';\n"
			.' var userId = \''.s($USER->id)."';\n"
			.' window.moodleMarginalia = new MoodleMarginalia( annotationPath, url, moodleRoot, userId, '.$sprefs.', {'
			." \n  useSmartquote: ".s(AN_USESMARTQUOTE)
			.",\n  useLog: ".s(AN_USELOGGING)
			.",\n  course: ".(int)$course->id
			.",\n  allowAnyUserPatch: ".($allowAnyUserPatch ? 'true' : 'false' )
			.",\n  smartquoteIcon: '".AN_SMARTQUOTEICON."'"
			.",\n  sessionCookie: 'MoodleSessionTest".$CFG->sessioncookie."'"
			.",\n  handlers: {"
			." \n   summary: function(){ window.location = '".$summaryurl."'; }"
			.",\n   tags: function(){ window.location = '".$tagsurl."'; }"
//			.",\n   help: function(){ return openpopup('/help.php?module=block_marginalia&file=annotate.html'); }\n"
			.",\n   log: function(){ window.location = '".$logurl."'; }"
			." \n}";
		if ( $showsplashpref == 'true' )
			$meta .= ",\n splash: '".get_string('splash',ANNOTATION_STRINGS)."'";
		$meta .= " \n} );\n"
			." window.moodleMarginalia.onload();\n";
		if ( $subscribe )
			$meta .= "window.moodleMarginalia.subscribeHtmlAreas(".(int)$course->id.");\n";
		$meta .= "}\n"
			."jQuery(window).load(myOnload);\n"
			."</script>\n";
		return $meta;
	}
	
	function show_help( )
	{
		global $CFG;
		
		helpbutton( 'annotate', get_string( 'annotation_help', ANNOTATION_STRINGS ), 'block_marginalia' );
		/*
		$helptitle = 'Help with Annotations';
		$linkobject = '<span class="helplink"><img class="iconhelp" alt="'.$helptitle.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
		echo link_to_popup_window ('/help.php?file=annotate.html&amp;forcelang=', 'popup',
										 $linkobject, 400, 500, $helptitle, 'none', true);
		 */
	}
	
	function show_sheet_dropdown( $refurl )
	{
		global $USER;
		
		$sheet = moodle_marginalia::get_sheet( );
		$showannotationspref = moodle_marginalia::get_show_annotations_pref( ) == 'true';
		
		echo "<select name='ansheet' id='ansheet' onchange='window.moodleMarginalia.changeSheet(this,\"$refurl\");'>\n";

		$selected = $showannotationspref ? '' : " selected='selected' ";
		echo " <option $selected value=''>".get_string('sheet_none', ANNOTATION_STRINGS)."</option>\n";

		if ( ! isguest() )  {
			$selected = ( $showannotationspref && $sheet == AN_SHEET_PRIVATE ) ? "selected='selected' " : '';
			echo " <option $selected"
				."value='".annotation_globals::sheet_str(AN_SHEET_PRIVATE,null)."'>".get_string('sheet_private', ANNOTATION_STRINGS)."</option>\n";
		}
		// Show item for all users
		if ( true )  {
			$selected = ( $showannotationspref && $sheet == AN_SHEET_PUBLIC ) ? "selected='selected' " : '';
			echo " <option $selected value='".annotation_globals::sheet_str(AN_SHEET_PUBLIC,null)."'>".get_string('sheet_public', ANNOTATION_STRINGS)."</option>\n";
		}
		echo "  <option disabled='disabled'>——————————</option>\n";
		echo "  <option value='summary'>".get_string('summary_link',ANNOTATION_STRINGS)."...</option>\n";
		echo "  <option value='tags'>".get_string('edit_keywords_link',ANNOTATION_STRINGS)."...</option>\n";
//		echo "  <option value='help'>".get_string('marginalia_help_link',ANNOTATION_STRINGS)."...</option>\n";
		
		$context = get_context_instance( CONTEXT_SYSTEM );
		if ( has_capability( 'block/marginalia:view_log', $context ) )
			echo "  <option value='log'>".get_string('log_link',ANNOTATION_STRINGS)."...</option>\n";
		echo "</select>\n";	
	}
	
	
	function summary_link_html( $refurl, $userid )
	{
		global $CFG, $course;
		$summaryurl = ANNOTATION_PATH.'/summary.php?user='.urlencode($userid)
			."&url=".urlencode( $refurl );
		return " <a id='annotation-summary-link' href='".s($summaryurl)."'"
			. " title='".get_string('summary_link_title',ANNOTATION_STRINGS)
			."'>".get_string('summary_link',ANNOTATION_STRINGS)."</a>\n"
			
			."<a id='annotation-editkeywords-link' href='".ANNOTATION_PATH.'/tags.php?course='.$course->id."'"
			. " title='".get_string( 'edit_keywords_link', ANNOTATION_STRINGS )
			."'>Tags</a>\n";
	}
	
	/**
	 * Show the header controls at the top of a page
	 * - which annotation set to show
	 * - help button
	 * - link to summary page
	 */
	function show_header_controls( $topic, $refurl, $user )
	{
		moodle_marginalia::show_help( 'forum' );
		moodle_marginalia::show_sheet_dropdown( $refurl );
//		echo moodle_marginalia::summary_link_html( $refurl, $user->username );
    }
    
	
	/**
	 * Deletes all annotations of a specific user
	 * This is here rather than in the annotation code so that not everything will have to
	 * include the annotation code.
	 *
	 * @param int $userid
	 * @return boolean
	 */
	function annotations_delete_user( $userid )
	{
		return delete_records( AN_DBTABLE, 'id', $userid );
	}
	
	/**
	 * Subscribe any HTMLAreas to quote events
	 * This should be placed *after* any script in the HTML that creates an HTMLArea, otherwise
	 * that object will not yet exist.  Also marke <script defer="defer">
	 */
	function subscribe_htmlareas( )
	{
		global $course;
//		echo "<script type='text/javascript' defer='defer'>"
		return	"window.moodleMarginalia.subscribeHtmlAreas(".(int)$course->id.");\n";
//			."\n</script>\n";
	}
		

}

class marginalia_summary_lib
{
	/**
	 * Pass in a url with {first} where the first item number should go
	 */
	static function show_result_pages( $first, $total, $perpage, $url )
	{
		// Show the list of result pages
		$npages = ceil( $total / $perpage );
		if ( $npages > 1 )
		{
			$this_page = 1 + floor( ( $first - 1 ) / $perpage );
			echo "<ol class='result-pages'>\n";
			for ( $i = 1; $i <= $npages;  ++$i )
			{
				if ( $i == $this_page )
					echo "  <li>".$i."</li>\n";
				else
				{
					$page = 1 + ($i - 1) * $perpage;
					$turl = str_replace( '{first}', $page, $url);
					echo "  <li><a href='".s($turl)."'>$i</a></li>\n";
				}
			}
			echo "</ol>\n";
		}
	}
}

