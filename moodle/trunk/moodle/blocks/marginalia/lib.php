<?php
/*
 * blocks/marginalia/lib.php
 *
 * Marginalia has been developed with funding and support from
 * BC Campus, Simon Fraser University, and the Government of
 * Canada, the UNDESA Africa i-Parliaments Action Plan, and  
 * units and individuals within those organizations.  Many 
 * thanks to all of them.  See CREDITS.html for details.
 * Copyright (C) 2005-2007 Geoffrey Glass; the United Nations
 * http://www.geof.net/code/annotation
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * $Id$
 */
 
require_once( $CFG->dirroot.'/blocks/marginalia/config.php' );

// The smartquote icon symbol(s)
define( 'AN_SMARTQUOTEICON', '\u275d' );	// \u275b\u275c: enclosed single qs, 267a: recycle

// The same thing as entities because - and this stuns the hell out of me every
// single time - PHP 5 *does not have native unicode support*!!!  Geez guys,
// I remember reading about unicode in Byte Magazine in what, the 1980s?
define( 'AN_SMARTQUOTEICON_HTML', '&#10077;' ); //'&#10075;&#10076;' );

// Icon for filtering on the summary page
define( 'AN_FILTERICON_HTML', '&#9754;' );  //&#9756;
	
define( 'ANNOTATION_STRINGS', 'block_marginalia' );

define( 'AN_SHEET_PREF', 'annotations.sheet' ); // 'annotations.user' );
define( 'AN_SHOWANNOTATIONS_PREF', 'annotations.show' );
define( 'AN_NOTEEDITMODE_PREF', 'annotations.note-edit-mode' );
define( 'AN_SPLASH_PREF', 'annotations.splash' );
//define( 'SMARTCOPY_PREF', 'smartcopy' );

define( 'AN_DBTABLE', 'marginalia' );
define( 'AN_READ_TABLE', 'marginalia_read' );

define( 'AN_SHEET_PRIVATE', 0x1 );
define( 'AN_SHEET_AUTHOR', 0x2 );
define( 'AN_SHEET_PUBLIC', 0xffff );

// Object types
define ( 'AN_OTYPE_POST', 1 );
define ( 'AN_OTYPE_ANNOTATION', 2 );
define ( 'AN_OTYPE_DISCUSSION', 3 );

// Needed by several annotation functions - if not set, PHP will throw errors into the output
// stream which causes AJAX problems.  Doing it this way in case moodle sets the TZ at some
// future point.  Leading @ suppresses warnings.  (Sigh... try..catch didn't work.  PHP is such a mess.)
@date_default_timezone_set( date_default_timezone_get( ) );

class moodle_marginalia
{
	static $singleton = null;
	var $logger = null;
	var $plugins = array( );

	// I would think Moodle might cache the capabilities to make has_capability fast, but it doesn't.
	var $viewfullnames = False;
	var $viewfullnames_set = False;

	
	public static function get_instance( )
	{
		if ( ! moodle_marginalia::$singleton )
			moodle_marginalia::$singleton = new moodle_marginalia( );
		return moodle_marginalia::$singleton;
	}
	
	public function moodle_marginalia( )
	{
		global $CFG;
		
		// Load up the logger, if available
		$logblock = get_record('block', 'name', 'marginalia_log');
		if ( $logblock )
		{
			require_once( $CFG->dirroot.'/blocks/marginalia_log/log.php' );
			$this->logger = new marginalia_log( );
			if ( $this->logger->is_active( ) )
				array_push( $this->plugins, $this->logger );
		}
	}

	function fullname($user)
	{
		// must be able to handle null user
		if ( ! $user )
			return 'NONE';
		if ( ! $this->viewfullnames_set )
		{
			$context = get_context_instance( CONTEXT_SYSTEM );
			$this->viewfullnames = has_capability( 'moodle/site:viewfullnames', $context );
			$this->viewfullnames_set = True;
		}
		return fullname( $user, $this->viewfullnames );
	}
	
	function fullname2( $firstname, $lastname )
	{
		$u = new object();
		$u->firstname = $firstname;
		$u->lastname = $lastname;
		return $this->fullname($u);
	}
	
	function get_host( )
	{
		global $CFG;
		$urlparts = parse_url( $CFG->wwwroot );
		return $urlparts[ 'host' ];
	}
	
	function get_service_path( )
	{
		global $CFG;
		return $CFG->wwwroot . ANNOTATION_PATH . '/annotate.php';
//		return $this->getMoodlePath( ) . ANNOTATE_SERVICE_PATH;
	}
	
	function get_keyword_service_path( )
	{
		global $CFG;
		return $CFG->wwwroot . ANNOTATION_PATH . '/keywords.php';
	}
	
	/** Get the moodle path - that is, the path to moodle from the root of the server.  Typically this is 'moodle/'.
	 * REQUEST_URI starts with this. */
	function get_moodle_path( )
	{
		global $CFG;
		$urlparts = parse_url( $CFG->wwwroot );
		return $urlparts[ 'path' ];
	}
	
	/**
	 * Get the server part of the moodle path.
	 * This is the absolute path, with the getMoodlePath( ) portion chopped off.
	 * Useful, because appending a REQUEST_URI to it produces an absolute URI. */
	function get_moodle_server( )
	{
		global $CFG;
		$urlparts = parse_url( $CFG->wwwroot );
		if ( $urlparts[ 'path' ] == '/' )
			return $CFG->wwwroot;
		else
			return substr( $CFG->wwwroot, 0, strpos( $CFG->wwwroot, $urlparts[ 'path' ] ) );
	}

	function get_install_date( )
	{
		// Hardcoded because I'm not aware of Moodle recording an install date anywhere
		date_default_timezone_set( date_default_timezone_get( ) );
		return strtotime( '2005-07-20' );
	}
	
	function get_feed_tag_uri( )
	{
		return "tag:" . $this->get_host() . ',' . date( 'Y-m-d', $this->get_install_date() ) . ":annotation";
	}
	
	/**
	 * get sheet type for sheet string
	 */
	function sheet_type( $sheet_str )
	{
		if ( 'public' == $sheet_str )
			return AN_SHEET_PUBLIC;
		elseif ( 'author' == $sheet_str )
			return AN_SHEET_AUTHOR;
		else
			return AN_SHEET_PRIVATE;
	}
	
	/**
	 * get sheet string for type and group
	 */
	function sheet_str( $sheet_type )
	{
		if ( AN_SHEET_PUBLIC == $sheet_type )
			return 'public';
		elseif ( AN_SHEET_PRIVATE == $sheet_type )
			return 'private';
		elseif ( AN_SHEET_AUTHOR == $sheet_type )
			return 'author';
		return '';
	}

	
	/**
	 * Get the object type and ID for a url
	 */
	function oid_from_url( $url )
	{
		$o = new object( );
		$o->object_type = 0;
		if ( preg_match( '/^.*\/mod\/forum\/permalink\.php\?p=(\d+)/', $url, $matches ) )
		{
			$o->object_type = AN_OTYPE_POST;
			$o->object_id = (int) $matches[ 1 ];
			$o->object_type_name = 'forum_post';
		}
		elseif ( preg_match( '/^.*\/mod\/forum\/discuss\.php\?d=(\d+)/', $url, $matches ) )
		{
			$o->object_type = AN_OTYPE_DISCUSSION;
			$o->object_id = (int) $matches[ 1 ];
			$o->object_type_name = 'forum_discussion';
		}
		return $o->object_type ? $o : null;
	}

	/**
	 * Remember: This the Annotation class does not store Moodle user IDs, so
	 * you must be sure to query for username and quote_author_username if you
	 * want userid and quoteAuthorId set.
	 */
	function record_to_annotation( $r )
	{
		$annotation = new Annotation( );
		
		$annotation->setAnnotationId( $r->id );
		
		if ( array_key_exists( 'userid', $r ) )
			$annotation->setUserId( $r->userid );
		if ( array_key_exists( 'firstname', $r ) )
			$annotation->setUserName( $this->fullname2( $r->firstname, $r->lastname ) );
		
		if ( array_key_exists( 'sheet_type', $r ) )
			$annotation->setSheet( $this->sheet_str( $r->sheet_type ) );
		if ( array_key_exists( 'url', $r ) )
			$annotation->setUrl( $r->url );
		if ( array_key_exists( 'note', $r ) )
			$annotation->setNote( $r->note );
		if ( array_key_exists( 'quote', $r ) )
			$annotation->setQuote( $r->quote );
		if ( array_key_exists( 'quote_title', $r ) )
			$annotation->setQuoteTitle( $r->quote_title );
		if ( array_key_exists( 'quote_author_id', $r ) )
			$annotation->setQuoteAuthorId( $r->quote_author_id );
		elseif ( array_key_exists( 'quote_author', $r ) )	// to support old mdl_annotation table
			$annotation->setQuoteAuthorId( $r->quote_author );
		if ( array_key_exists( 'quote_author_firstname', $r ) )
			$annotation->setQuoteAuthorName( $this->fullname2( $r->quote_author_firstname, $r->quote_author_lastname ) );
		if ( array_key_exists( 'link', $r ) )
			$annotation->setLink( $r->link );
		if ( array_key_exists( 'link_title', $r ) )
			$annotation->setLinkTitle( $r->link_title );
		if ( array_key_exists( 'created', $r ) )
			$annotation->setCreated( (int) $r->created );
		if ( array_key_exists( 'modified', $r ) )
			$annotation->setModified( (int) $r->modified );
		if ( array_key_exists( 'lastread', $r ) )
			$annotation->setLastRead( (int) $r->lastread );
		
		$start_line = array_key_exists( 'start_line', $r ) ? $r->start_line : 0;
		$end_line = array_key_exists( 'end_line', $r ) ? $r->end_line : 0;
		// The second and subsequente lines of the test are to catch cases where everything is blank,
		// which can happen if the range is really old and uses the range field
		if ( array_key_exists( 'start_block', $r ) && $r->start_block !== null 
			&& ( ! array_key_exists( 'range', $r )
				|| ( $start_line || $end_line || $r->start_block || $r->end_block || $r->start_word || $r->end_word || $r->start_char || $r->end_char ) ) )
		{
			$range = new SequenceRange( );
			$range->setStart( new SequencePoint( $r->start_block, $start_line, $r->start_word, $r->start_char ) );
			$range->setEnd( new SequencePoint( $r->end_block, $end_line, $r->end_word, $r->end_char ) );
			$annotation->setSequenceRange( $range );
		}
		// Older versions used a range string column.  Check and translate that field here:
		else if ( array_key_exists( 'range', $r ) && $r->range !== null )  {
			$range = new SequenceRange( );
			$range->fromString( $r->range );
			$annotation->setSequenceRange( $range );
		}
		
		if ( array_key_exists( 'start_xpath', $r ) && $r->start_xpath !== null )  {
			$range = new XPathRange( );
			$range->setStart( new XPathPoint( $r->start_xpath, $start_line, $r->start_word, $r->start_char ) );
			$range->setEnd( new XpathPoint( $r->end_xpath, $end_line, $r->end_word, $r->end_char ) );
			$annotation->setXPathRange( $range );
		}
			
		return $annotation;
	}
		
	function annotation_to_record( $annotation )
	{
		$record = new object();
		
		$id = $annotation->getAnnotationId( );
		if ( $id )
			$record->id = $id;
		
		// Map username to id #
		$userid = $annotation->getUserId( );
		$user = get_record( 'user', 'id', (int) $userid );
		$record->userid = $user ? $user->id : null;

		$sheet = $annotation->getSheet( );
		$record->sheet_type = $this->sheet_type( $sheet );
			
		$record->url = addslashes( $annotation->getUrl( ) );
		$record->note = addslashes( $annotation->getNote( ) );
		$record->quote = addslashes( $annotation->getQuote( ) );
		$record->quote_title = addslashes( $annotation->getQuoteTitle( ) );
		
		// Map author username to id #
		$userid = $annotation->getQuoteAuthorId( );
		$user = get_record( 'user', 'id', (int) $userid );
		$record->quote_author_id = $user ? $user->id : null;
		
		$record->link = addslashes( $annotation->getLink( ) );
		$record->link_title = addslashes( $annotation->getLinkTitle( ) );
		$record->created = $annotation->getCreated( );
		$record->modified = $annotation->getModified( );

		$sequenceRange = $annotation->getSequenceRange( );
		$sequenceStart = $sequenceRange->getStart( );
		$sequenceEnd = $sequenceRange->getEnd( );
		$xpathRange = $annotation->getXPathRange( );
		if ( null !== $xpathRange )  {
			$xpathStart = $xpathRange->getStart( );
			$xpathEnd = $xpathRange->getEnd( );
		}
		
		$record->start_block = addslashes( $sequenceStart->getPaddedPathStr( ) );
		$record->start_xpath = null === $xpathRange ? null : addslashes( $xpathStart->getPathStr( ) );
		$record->start_line = $sequenceStart->getLines( );
		$record->start_word = $sequenceStart->getWords( ) ? $sequenceStart->getWords( ) : 0;
		$record->start_char = $sequenceStart->getChars( );
		
		$record->end_block = addslashes( $sequenceEnd->getPaddedPathStr( ) );
		$record->end_xpath = null === $xpathRange ? null : addslashes( $xpathEnd->getPathStr( ) );
		$record->end_line = $sequenceEnd->getLines( );
		$record->end_word = $sequenceEnd->getWords( ) ? $sequenceEnd->getWords( ) : 0;
		$record->end_char = $sequenceEnd->getChars( );
		return $record;
	}

	/**
	 * Get an annotations preference value;  if the preference doesn't exist, create it
	 * so that the Javascript client will have permission to set it later (to prevent
	 * client creation of random preferences, only existing preferences can be set)
	 */
	public function get_pref( $name, $default )
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
	public function get_sheet( )
	{
		return $this->get_pref( AN_SHEET_PREF, 'public' );
	}
	
	public function get_show_annotations_pref( )
	{
		return $this->get_pref( AN_SHOWANNOTATIONS_PREF, 'false' );
	}
	
	/**
	 * Return HTML for insertion in the head of a document to include Marginalia Javascript
	 * and initialize Marginalia.  If necessary, also creates relevant user preferences 
	 * (necessary for Marginalia to function correctly).
	 */
	public function header_html( )
	{
		global $CFG, $USER;
		
		$anscripts = listMarginaliaJavascript( );
		for ( $i = 0;  $i < count( $anscripts );  ++$i )
			require_js( ANNOTATION_PATH.'/marginalia/'.$anscripts[ $i ] );
		require_js( array(
			ANNOTATION_PATH.'/marginalia-config.js',
			ANNOTATION_PATH.'/marginalia-strings.js',
			ANNOTATION_PATH.'/smartquote.js',
			ANNOTATION_PATH.'/MoodleMarginalia.js' ) );
		
		foreach ( $this->plugins as $plugin )
			$plugin->header_html( );
	
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
	public function init_html( $refurl, $subscribe=false )
	{
		global $CFG, $USER, $course;
		
		// Get all annotation preferences as an associative array and sets them to defaults
		// in the database if not already present.
		$prefs = array(
			AN_SHEET_PREF => $this->get_pref( AN_SHEET_PREF, 'public' ),
			AN_SHOWANNOTATIONS_PREF => $this->get_pref( AN_SHOWANNOTATIONS_PREF, 'false' ),
			AN_NOTEEDITMODE_PREF => $this->get_pref( AN_NOTEEDITMODE_PREF, 'freeform' ),
			AN_SPLASH_PREF => $this->get_pref( AN_SPLASH_PREF, 'true' )
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

		$sitecontext = get_context_instance(CONTEXT_SYSTEM);
		$allowAnyUserPatch = AN_ADMINUPDATE && (
			has_capability( 'moodle/legacy:admin', $sitecontext ) or has_capability( 'moodle/site:doanything', $sitecontext) );
		
		$plugin_handlers = '';
		foreach ( $this->plugins as $plugin )
		{
			$dropdowns = $plugin->dropdown_entries( $refurl );
			if ( $dropdowns )
			{
				foreach ( $dropdowns as $dropdown )
					$plugin_handlers .= "\n,  ".$dropdown->value.': '.$dropdown->action;
			}
		}
		
		$meta = "<script language='JavaScript' type='text/javascript' defer='defer'>\n"
			."function myOnload() {\n"
			." var moodleRoot = '".s($CFG->wwwroot)."';\n"
			." var annotationPath = '".s(ANNOTATION_PATH)."';\n"
			." var url = '".s($refurl)."';\n"
			.' var userId = \''.s($USER->id)."';\n"
			.' window.moodleMarginalia = new MoodleMarginalia( annotationPath, url, moodleRoot, userId, '.$sprefs.', {'
			." \n  useSmartquote: ".s(AN_USESMARTQUOTE)
			.",\n  useLog: ".($this->logger && $this->logger->is_active() ? 'true' : 'false')
			.",\n  course: ".(int)$course->id
			.",\n  allowAnyUserPatch: ".($allowAnyUserPatch ? 'true' : 'false' )
			.",\n  smartquoteIcon: '".AN_SMARTQUOTEICON."'"
			.",\n  sessionCookie: 'MoodleSessionTest".$CFG->sessioncookie."'"
			.",\n  handlers: {"
			." \n   summary: function(){ window.location = '".$summaryurl."'; }"
			.",\n   tags: function(){ window.location = '".$tagsurl."'; }"
//			.",\n   help: function(){ return openpopup('/help.php?module=block_marginalia&file=annotate.html'); }\n"
			.$plugin_handlers
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
		
		$sheet = $this->get_sheet( );
		$showannotationspref = $this->get_show_annotations_pref( ) == 'true';
		
		echo "<select name='ansheet' id='ansheet' onchange='window.moodleMarginalia.changeSheet(this,\"$refurl\");'>\n";

		$selected = $showannotationspref ? '' : " selected='selected' ";
		echo " <option $selected value=''>".get_string('sheet_none', ANNOTATION_STRINGS)."</option>\n";

		if ( ! isguest() )  {
			$selected = ( $showannotationspref && $sheet == AN_SHEET_PRIVATE ) ? "selected='selected' " : '';
			echo " <option $selected"
				."value='".$this->sheet_str(AN_SHEET_PRIVATE,null)."'>".get_string('sheet_private', ANNOTATION_STRINGS)."</option>\n";
		}
		// Show item for all users
		if ( true )  {
			$selected = ( $showannotationspref && $sheet == AN_SHEET_PUBLIC ) ? "selected='selected' " : '';
			echo " <option $selected value='".$this->sheet_str(AN_SHEET_PUBLIC,null)."'>".get_string('sheet_public', ANNOTATION_STRINGS)."</option>\n";
		}
		echo "  <option disabled='disabled'>——————————</option>\n";
		echo "  <option value='summary'>".get_string('summary_link',ANNOTATION_STRINGS)."...</option>\n";
		echo "  <option value='tags'>".get_string('edit_keywords_link',ANNOTATION_STRINGS)."...</option>\n";
		
		foreach ( $this->plugins as $plugin )
		{
			$dropdowns = $plugin->dropdown_entries( $refurl );
			if ( $dropdowns )
			{
				foreach ( $dropdowns as $dropdown )
					echo "<option value='".$dropdown->value."'>".s($dropdown->name)."</option>\n";
			}
		}

//		echo "  <option value='help'>".get_string('marginalia_help_link',ANNOTATION_STRINGS)."...</option>\n";
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
		$this->show_help( 'forum' );
		$this->show_sheet_dropdown( $refurl );
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
		if ( $perpage )	//0 => no list, because everything is shown
		{
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
}

