<?php

// When this is true, any access to annotations (including fetching the Atom feed) requires a valid user
// When false, anyone on the Web can retrieve public annotations via an Atom feed
define( 'ANNOTATION_REQUIRE_USER', false );

define( 'MAX_NOTE_LENGTH', 250 );
define( 'MAX_QUOTE_LENGTH', 1000 );

define( 'ANNOTATE_SERVICE_PATH', '/annotate' );

define( 'ANNOTATION_STRINGS', 'annotation' );

define( 'AN_USER_PREF', 'annotations.user' );
define( 'AN_SHOWANNOTATIONS_PREF', 'annotations.show' );
define( 'AN_NOTEEDITMODE_PREF', 'annotations.note-edit-mode' );
define( 'AN_SPLASH_PREF', 'annotations.splash' );
define( 'SMARTCOPY_PREF', 'smartcopy' );


class AnnotationGlobals
{
	function getHost( )
	{
		global $CFG;
		$urlParts = parse_url( $CFG->wwwroot );
		return $urlParts[ 'host' ];
	}
	
	function getServicePath( )
	{
		return AnnotationGlobals::getMoodlePath( ) + ANNOTATE_SERVICE_PATH;
	}
	
	/** Get the moodle path - that is, the path to moodle from the root of the server.  Typically this is 'moodle/'.
	 * REQUEST_URI starts with this. */
	function getMoodlePath( )
	{
		global $CFG;
		$urlParts = parse_url( $CFG->wwwroot );
		return $urlParts[ 'path' ];
	}
	
	/**
	 * Get the sever part of the moodle path.
	 * This is the absolute path, with the getMoodlePath( ) portion chopped off.
	 * Useful, because appending a REQUEST_URI to it produces an absolute URI. */
	function getMoodleServer( )
	{
		global $CFG;
		$urlParts = parse_url( $CFG->wwwroot );
		if ( $urlParts[ 'path' ] == '/' )
			return $CFG->wwwroot;
		else
			return substr( $CFG->wwwroot, 0, strpos( $CFG->wwwroot, $urlParts[ 'path' ] ) );
	}

	function getInstallDate( )
	{
		return date( '2005-07-20' );
	}
	
	function getFeedTagUri( )
	{
		return "tag:" . AnnotationGlobals::getHost() . ',' . date( '2005-07-20', AnnotationGlobals::getInstallDate() ) . ":annotation";
	}
	
	function recordToAnnotation( $r )
	{
		$annotation = new Annotation( );
		
		$annotation->setAnnotationId( $r->id );
		$annotation->setUserId( $r->userid );
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
		if ( $r->quote_author )
			$annotation->setQuoteAuthor( $r->quote_author );
		if ( $r->link )
			$annotation->setLink( $r->link );
		if ( $r->link_title )
			$annotation->setLinkTitle( $r->link_title );
		$annotation->setCreated( $r->created );
		
		if ( $r->start_block !== null )
		{
			$range = new SequenceRange( );
			$range->setStart( new SequencePoint( $r->start_block, $r->start_word, $r->start_char ) );
			$range->setEnd( new SequencePoint( $r->end_block, $r->end_word, $r->end_char ) );
			$annotation->setSequenceRange( $range );
		}
		// Older versions used a range string column.  Check and translate that field here:
		else if ( ! empty( $r->range ) )
		{
			$range = new SequenceRange( );
			$range->fromString( $r->range );
			$annotation->setSequenceRange( $range );
		}
		
		if ( $r->start_xpath !== null )
		{
			$range = new XPathRange( );
			$range->setStart( new XPathPoint( $r->start_xpath, $r->start_word, $r->start_char ) );
			$range->setEnd( new XpathPoint( $r->end_xpath, $r->end_word, $r->end_char ) );
			$annotation->setXPathRange( $range );
		}
		
		return $annotation;
	}
		
	function annotationToRecord( $annotation )
	{
		$id = $annotation->getAnnotationId( );
		if ( $id )
			$record->id = $id;
		$record->userid = addslashes( $annotation->getUserId( ) );
		$record->access = addslashes( $annotation->getAccess( ) );
		$record->url = addslashes( $annotation->getUrl( ) );
		$record->note = addslashes( $annotation->getNote( ) );
		$record->quote = addslashes( $annotation->getQuote( ) );
		$record->quote_title = addslashes( $annotation->getQuoteTitle( ) );
		$record->quote_author = addslashes( $annotation->getQuoteAuthor( ) );
		$record->link = addslashes( $annotation->getLink( ) );
		$record->link_title = addslashes( $annotation->getLinkTitle( ) );
		$record->created = date( 'Y-m-d H:m' );

		$sequenceRange = $annotation->getSequenceRange( );
		$sequenceStart = $sequenceRange->getStart( );
		$sequenceEnd = $sequenceRange->getEnd( );
		$xpathRange = $annotation->getXPathRange( );
		$xpathStart = $xpathRange->getStart( );
		$xpathEnd = $xpathRange->getEnd( );
		
		$record->start_block = addslashes( $sequenceStart->getPaddedPathStr( ) );
		$record->start_xpath = addslashes( $xpathStart->getPathStr( ) );
		$record->start_word = $xpathStart->getWords( );
		$record->start_char = $xpathStart->getChars( );
		
		$record->end_block = addslashes( $sequenceEnd->getPaddedPathStr( ) );
		$record->end_xpath = addslashes( $xpathEnd->getPathStr( ) );
		$record->end_word = $xpathEnd->getWords( );
		$record->end_char = $xpathEnd->getChars( );
		return $record;
	}
}
?>
