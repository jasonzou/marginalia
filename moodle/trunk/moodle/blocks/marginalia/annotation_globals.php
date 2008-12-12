<?php

// The smartquote icon symbol(s)
define( 'AN_SMARTQUOTEICON', '\u275b\u275c' );	// 267a: recycle

// The same thing as entities because - and this stuns the hell out of me every
// single time - PHP 5 *does not have native unicode support*!!!  Geez guys,
// I remember reading about unicode in Byte Magazine in what, the 1980s?
define( 'AN_SMARTQUOTEICON_HTML', '&#10075;&#10076;' );

// Icon for filtering on the summary page
define( 'AN_FILTERICON_HTML', '&#9754;' );  //&#9756;
	
define( 'ANNOTATION_STRINGS', 'block_annotation' );

define( 'AN_USER_PREF', 'annotations.user' );
define( 'AN_SHOWANNOTATIONS_PREF', 'annotations.show' );
define( 'AN_NOTEEDITMODE_PREF', 'annotations.note-edit-mode' );
define( 'AN_SPLASH_PREF', 'annotations.splash' );
define( 'SMARTCOPY_PREF', 'smartcopy' );


class annotation_globals
{
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
//		return annotation_globals::getMoodlePath( ) . ANNOTATE_SERVICE_PATH;
	}
	
	FUNCTION get_keyword_service_path( )
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
	 * Get the sever part of the moodle path.
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
		return strtotime( '2005-07-20' );
	}
	
	function get_feed_tag_uri( )
	{
		return "tag:" . annotation_globals::get_host() . ',' . date( 'Y-m-d', annotation_globals::get_install_date() ) . ":annotation";
	}
	
	function record_to_annotation( $r )
	{
		$annotation = new Annotation( );
		
		$annotation->setAnnotationId( $r->id );
		$annotation->setUserId( $r->userid );
		$annotation->setUserName( $r->username );
		if ( $r->access )
			$annotation->setAccess( $r->access );
		if ( $r->url )
			$annotation->setUrl( $r->url );
		if ( $r->note )
			$annotation->setNote( $r->note );
		if ( $r->quote )
			$annotation->setQuote( $r->quote );
		if ( $r->quote_title )
			$annotation->setQuoteTitle( $r->quote_title );
		if ( $r->quote_author_id )
			$annotation->setQuoteAuthorId( $r->quote_author_id );
		if ( $r->quote_author_name )
			$annotation->setQuoteAuthorName( $r->quote_author_name );
		if ( $r->link )
			$annotation->setLink( $r->link );
		if ( $r->link_title )
			$annotation->setLinkTitle( $r->link_title );
		$annotation->setCreated( $r->created );
		$annotation->setModified( $r->modified );
		
		if ( $r->start_block !== null )  {
			$range = new SequenceRange( );
			$range->setStart( new SequencePoint( $r->start_block, $r->start_line, $r->start_word, $r->start_char ) );
			$range->setEnd( new SequencePoint( $r->end_block, $r->end_line, $r->end_word, $r->end_char ) );
			$annotation->setSequenceRange( $range );
		}
		// Older versions used a range string column.  Check and translate that field here:
		else if ( ! empty( $r->range ) )  {
			$range = new SequenceRange( );
			$range->fromString( $r->range );
			$annotation->setSequenceRange( $range );
		}
		
		if ( $r->start_xpath !== null )  {
			$range = new XPathRange( );
			$range->setStart( new XPathPoint( $r->start_xpath, $r->start_line, $r->start_word, $r->start_char ) );
			$range->setEnd( new XpathPoint( $r->end_xpath, $r->end_line, $r->end_word, $r->end_char ) );
			$annotation->setXPathRange( $range );
		}
		
		return $annotation;
	}
		
	function annotation_to_record( $annotation, $forupdate=False )
	{
		$id = $annotation->getAnnotationId( );
		if ( $id )
			$record->id = $id;
		$record->userid = addslashes( $annotation->getUserId( ) );
		$record->username = addslashes( $annotation->getUserName( ) );
		$record->access = addslashes( $annotation->getAccess( ) );
		$record->url = addslashes( $annotation->getUrl( ) );
		$record->note = addslashes( $annotation->getNote( ) );
		$record->quote = addslashes( $annotation->getQuote( ) );
		$record->quote_title = addslashes( $annotation->getQuoteTitle( ) );
		$record->quote_author = addslashes( $annotation->getQuoteAuthorId( ) );
		$record->link = addslashes( $annotation->getLink( ) );
		$record->link_title = addslashes( $annotation->getLinkTitle( ) );
		if ( ! $forupdate )
			$record->created = $annotation->getCreated( );

		$sequenceRange = $annotation->getSequenceRange( );
		$sequenceStart = $sequenceRange->getStart( );
		$sequenceEnd = $sequenceRange->getEnd( );
		$xpathRange = $annotation->getXPathRange( );
		$xpathStart = $xpathRange->getStart( );
		$xpathEnd = $xpathRange->getEnd( );
		
		$record->start_block = addslashes( $sequenceStart->getPaddedPathStr( ) );
		$record->start_xpath = addslashes( $xpathStart->getPathStr( ) );
		$record->start_line = $xpathStart->getLines( );
		$record->start_word = $xpathStart->getWords( );
		$record->start_char = $xpathStart->getChars( );
		
		$record->end_block = addslashes( $sequenceEnd->getPaddedPathStr( ) );
		$record->end_xpath = addslashes( $xpathEnd->getPathStr( ) );
		$record->end_line = $xpathEnd->getLines( );
		$record->end_word = $xpathEnd->getWords( );
		$record->end_char = $xpathEnd->getChars( );
		return $record;
	}

	function record_to_keyword( $r )
	{
		$keyword = new MarginaliaKeyword( );
		$keyword->name = $r->name;
		$keyword->description = $r->description;
		return $keyword;
	}
	
	function keyword_to_record( $keyword )
	{
		global $USER;
		$record->userid = $USER->id;
		$record->name = $keyword->name;
		$record->description = $keyword->description;
	}
}
