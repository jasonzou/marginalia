<?php

/*
 * annotate.php
 * handles annotation http requests
 *
 * Marginalia has been developed with funding and support from
 * BC Campus, Simon Fraser University, and the Government of
 * Canada, and units and individuals within those organizations.
 * Many thanks to all of them.  See CREDITS.html for details.
 * Copyright (C) 2005-2007 Geoffrey Glass www.geof.net
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
 */

require_once( "config.php" );
require_once( "annotation.php" );
require_once( "annotate-db.php" );
require_once( "block-range.php" );
require_once( "xpath-range.php" );

$annotationService = new AnnotationService( );
$annotationService->dispatch( );

class AnnotationService
{
	function parseAnnotationId( )
	{
		$urlString = $_SERVER[ 'REQUEST_URI' ];
		$pos = strpos( $urlString, $CFG->annotate_servicePath );
		if ( False == $pos )
			$id = ( array_key_exists( 'id', $_GET ) ? (int) $_GET[ 'id' ] : False );
		else
			$id = (int) substr( $urlString, $pos + strlen( $CFG->annotate_servicePath ) + 1 );
		if ( $id == '' || $id == 0 || !isnum( $id ) )
			return False;
		return $id;
	}

	
	function dispatch( )
	{
		$id = $this->parseAnnotationId( );
		switch( $_SERVER[ 'REQUEST_METHOD' ] )
		{
			// get a list of annotations
			case 'GET':
				if ( False === $id )
					AnnotationService::listAnnotations( );
				else
					AnnotationService::httpError( 404, 'Not Found', 'Can\'t address annotation by ID' );
				break;
			
			// create a new annotation
			case 'POST':
				AnnotationService::createAnnotation( );
				break;
			
			// update an existing annotation
			case 'PUT':
				if ( False === $id )
					AnnotationService::httpError( 400, 'Bad Request', 'No such annotation #'.(int)$id );
				else
					AnnotationService::updateAnnotation( $id );
				break;
			
			// delete an existing annotation
			case 'DELETE':
				if ( False === $id )
					AnnotationService::httpError( 400, 'Bad Request', 'No such annotation #'.(int)$id );
				else
					AnnotationService::deleteAnnotation( $id );
				break;
			
			default:
				header( "HTTP:/1.1 405 Method Not Allowed" );
				header( "Allow:  GET, POST, DELETE" );
				echo "<h1>405 Method Not Allowed</h1>Allow: GET, POST, DELETE";
		}
	}
	
	
	function listAnnotations()
	{
		global $CFG;
		
		$format = unfix_quotes( $_GET[ 'format' ] );
		$url = unfix_quotes( $_GET[ 'url' ] );
		$username = unfix_quotes( $_GET[ 'user' ] );
		$exclude = unfix_quotes( array_key_exists( 'exclude', $_GET ) ? $_GET[ 'exclude' ] : '' );
		// Can't sanitize $username - it might contain a single quote, e.g. for some French names starting with d',
		// or some romanization of other languages, e.g. the old romanization of Mandarin
		if ( $url == null || $url == '' || !sanitize( $url ) )
			AnnotationService::httpError( 400, 'Bad Request', 'Bad URL' );
		else if ( null == $format || 'atom' == $format )
		{
			$db = new AnnotationDB( );
			if ( ! $db->open( $CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->db ) )
				AnnotationService::httpError( 500, 'Internal Service Error', 'Unable to connect to database' );
			else
			{
				$annotations = &$db->listAnnotations( $url, $username );
				$db->release( );
				if ( null === $annotations )
					AnnotationService::httpError( 500, 'Internal Service Error', 'Failed to list annotations' );
				else
					AnnotationService::getAtom( $annotations, $exclude );
			}
		}
		else
			$this->httpError( 400, 'Bad Request', 'Unknown format' );
	}
	
	
	function createAnnotation()
	{
		global $CFG;
		
		$blockRange = new BlockRange( );
		$blockRange->fromString( $_POST[ 'block-range' ] );
		$xpathRange = new XPathRange( );
		$xpathRange->fromString( $_POST[ 'xpath-range' ] );
		
		// TODO: Scan XPath to make sure it's safe
		$note = unfix_quotes( $_POST[ 'note' ] );
		$access = unfix_quotes( $_POST[ 'access' ] );
		$quote = unfix_quotes( $_POST[ 'quote' ] );
		$quote_title = unfix_quotes( $_POST[ 'quote_title' ] );
		$quote_author = unfix_quotes( $_POST[ 'quote_author' ] );
		$url = unfix_quotes( $_POST[ 'url' ] );
		$link = unfix_quotes( $_POST[ 'link' ] );
		
		if ( ! isXPathSafe( $xpathRange->start->getPathStr() ) || ! isXPathSafe( $xpathRange->end->getPathStr( ) ) )
			AnnotationService::httpError( 400, 'Bad Request', 'Bad xpath' );
			
		if ( ! isnum( $offset ) || !isnum( $length ) || !sanitize( $date ) || !sanitize( $url ) )
			AnnotationService::httpError( 400, 'Bad Request', 'Bad URL' );
		elseif ( ! AnnotationService::isUrlSafe( $url ) || ! AnnotationService::isUrlSafe( $link ) )
			AnnotationService::httpError( 400, 'Bad Request', 'Forbidden URL scheme' );
		elseif ( $access != 'public' && $access != 'private' && $access != '' )
			AnnotationService::httpError( 400, 'Bad Request', 'Bad access value' );
		else
		{
			$db = new AnnotationDB( );
			if ( ! $db->open( $CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->db ) )
				AnnotationService::httpError( 500, 'Internal Service Error', 'Unable to connect to database' );
			else
			{
				$annotation = new Annotation( );
				$annotation->setUrl( $url );
				$annotation->setBlockRange( $blockRange );
				$annotation->setXPathRange( $xpathRange );
				$annotation->setNote( $note );
				$annotation->setAccess( $access );
				$annotation->setQuote( $quote );
				$annotation->setQuoteTitle( $quote_title );
				$annotation->setQuoteAuthor( $quote_author );
				$annotation->setLink( $link );
				$id = $db->createAnnotation( $annotation );
				$db->release( );
				if ( $id != 0 )
				{
					header( 'HTTP/1.1 201 Created' );
					header( "Location: $CFG->wwwroot$servicePath$id" );
				}
				else
					AnnotationService::httpError( 500, 'Internal Service Error', 'Create failed' );
			}
		}
	}
	
	
	function updateAnnotation( $id )
	{
		global $CFG;
		
		// Now for some joy.  PHP isn't clever enough to populate $_POST if the
		// Content-Type is application/x-www-form-urlencoded - it only does
		// that if the request method is POST.  Bleargh.
		// Plus, how do I ensure the charset is respected correctly?  Hmph.
		
		// Should fail if not Content-Type: application/x-www-form-urlencoded; charset: UTF-8
		$fp = fopen( 'php://input', 'rb' );
		$urlencoded = '';
		while ( $data = fread( $fp, 1024 ) )
			$urlencoded .= $data;
		parse_str( $urlencoded, $params );

		// If PHP ever decides to use magicquotes on these we're screwed
		$note = unfix_quotes( $params[ 'note' ] );
		$access = unfix_quotes( $params[ 'access' ] );
		$link = unfix_quotes( $params[ 'link' ] );
		if ( $access != 'public' && $access != 'private' && $access != '' )
			AnnotationService::httpError( 400, 'Bad Request', 'Bad access value' );
		elseif ( ! AnnotationService::isUrlSafe( $link ) )
			AnnotationService::httpError( 400, 'Bad Request', 'Forbidden URL scheme' );
		else
		{
			$db = new AnnotationDB( );
			if ( ! $db->open( $CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->db ) )
				AnnotationService::httpError( 500, 'Internal Service Error', 'Unable to connect to database' );
			else
			{
				if ( $db->updateAnnotation( $id, $note, $access, $link ) )
					header( 'HTTP/1.1 204 Updated' );
				else
					AnnotationService::httpError( 500, 'Internal Service Error', 'Update failed' );
				$db->release( );
			}
		}
	}

	
	function deleteAnnotation( $id )
	{
		global $CFG;
		
		$db = new AnnotationDB( );
		if ( ! $db->open( $CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->db ) )
			AnnotationService::httpError( 500, 'Internal Service Error', 'Unable to connect to database' );
		else
		{
			if ( $db->deleteAnnotation( $id ) )
				header( "HTTP/1.1 204 Deleted" );
			else
				AnnotationService::httpError( 500, 'Internal Service Error', 'Deleted failed' );
			$db->release( );
		}
	}


	function getAtom( &$annotations, $exclude='' )
	{
		global $CFG;
		
		// Get the last modification time of the feed
		$feedLastModified = $CFG->installDate;
		if ( $annotations )
		{
			foreach ( $annotations as $annotation )
			{
				$modified = $annotation->getModified( );
				if ( null != $modified && $modified > $feedLastModified )
					$feedLastModified = $modified;
			}
		}
				
		$excludeFields = split( ' ', $exclude );
		
		$NS_PTR = 'http://www.geof.net/code/annotation/';
		$NS_ATOM = 'http://www.w3.org/2005/Atom';
		$NS_XHTML = 'http://www.w3.org/1999/xhtml';
		
		header( 'Content-Type: application/xml' );
		echo( '<?xml version="1.0" encoding="utf-8"?>' . "\n" );
		
		// About the feed ----
		echo "<feed xmlns:ptr='$NS_PTR' xmlns='$NS_ATOM' ptr:annotation-version='0.3'>\n";
		// This would be the link to the summary page:
		//echo( " <link rel='alternate' type='text/html' href='" . htmlspecialchars( "$CFG->wwwroot$url/annotations" ) . "'/>\n" );
		echo " <link rel='self' type='text/html' href=\"" . htmlspecialchars( "$CFG->annotate_servicePath" ) . "\"/>\n";
		echo " <updated>" . date( 'Ymd', $feedLastModified ) . 'T' . date( 'HiO', $feedLastModified ) . "</updated>\n";
		echo " <title>Annotations</title>";
		echo " <id>tag:" . $CFG->host . ',' . date( '2005-07-20', $CFG->installDate ) . ":annotation</id>\n";
		
		for ( $i = 0;  $i < count( $annotations );  ++$i )
		{
			$annotation = $annotations[ $i ];
			$blockRange = &$annotation->getBlockRange();
			$xpathRange = &$annotation->getXPathRange();
			echo " <entry>\n";
			// Emit range in two formats:  block for sorting, xpath for authority and speed
			echo "  <ptr:range format='block'>".$blockRange->toString( )."</ptr:range>\n";
			// Make 100% certain that the XPath expression is no safe (e.g. no document() calls)
			if ( $xpathRange && isXPathSafe( $xpathRange->start->getPathStr() ) && isXPathSafe( $xpathRange->end->getPathStr( ) ) )
				echo "  <ptr:range format='xpath'>".$xpathRange->toString( )."</ptr:range>\n";
			echo "  <ptr:access>$annotation->access</ptr:access>\n";
			// Annotation note as title
			echo "  <title>" . htmlspecialchars( $annotation->getNote() ) . "</title>\n";
			// Use double quotes for some attributes because it's easier than passing ENT_QUOTES to
			// each call to htmlspecialchars
			echo "  <link rel='self' type='application/xml' href=\"" . htmlspecialchars( "$CFG->annotate_servicePath/$annotation->id" ) . "\"/>\n";
			echo "  <link rel='alternate' type='text/html' title=\"" . htmlspecialchars( $annotation->getQuoteTitle() ) . "\" href=\"" . htmlspecialchars( $annotation->getUrl() ) . "\"/>\n";
			if ( $annotation->link )
				echo "  <link rel='related' type='text/html' title=\"" . htmlspecialchars( $annotation->getNote() ) . "\" href=\"" . htmlspecialchars( $annotation->getLink() ) . "\"/>\n";
			// Is this international-safe?  I could use htmlsecialchars on it, but that might not match the
			// restrictions on IRIs.  #GEOF#
			echo "  <id>tag:" . $CFG->host . ',' . date( 'Y-m-d', $annotation->getCreated() ) . ":".annotation/$annotation->getId()."</id>\n";
			echo "  <updated>" . date( 'Y-m-d', $annotation->getModified() ) . 'T' . date( 'H:i:O', $annotation->getModified() ) . "</updated>\n";
			// Selected text as summary
			echo "  <summary>" . htmlspecialchars( $annotation->getQuote() ) . "</summary>\n";
			// Author of the annotation
			echo "  <author>\n";
			echo "   <name>" . htmlspecialchars( $annotation->getUserId() ) . "</name>\n";
			echo "  </author>\n";
			// Contributor is the sources of the selected text
			echo "  <contributor>\n";
			echo "   <name>" . htmlspecialchars( $annotation->getQuoteAuthor() ) . "</name>\n";
			echo "  </contributor>\n";
			// Full annotation and selected text in HTML
			if ( ! in_array( 'content', $excludeFields ) )
			{
				echo "  <content type='xhtml'>\n";
				echo "   <div xmlns='$NS_XHTML'>\n";
				echo "    <p>" . htmlspecialchars( $annotation->getNote() ) . "</p>\n";
				echo "    <blockquote><p>" . htmlspecialchars( $annotation->getQuote() ) . "</p></blockquote>\n";
				echo "    <p><address>" . htmlspecialchars( $annotation->getQuoteAuthor() ) . "</address> ";
				echo "in <cite><a href=\"" . htmlspecialchars( $annotation->getUrl() ) . "\">" . htmlspecialchars( $annotation->getQuoteTitle() ) . "</a></cite></p>\n";
				echo "   </div>\n";
				echo "  </content>\n";
			}
			echo " </entry>\n";
		}
		echo "</feed>\n";
	}


	/**
	 * Check whether an untrusted URL is safe for insertion in a page
	 * In particular, javascript: urls can be used for XSS attacks
	 */
	function isUrlSafe( $url )
	{
		$urlParts = parse_url( $url );
		$scheme = $urlParts[ 'scheme' ];
		if ( 'http' == $scheme || 'https' == $scheme || '' == $scheme )
			return true;
		else
			return false;
	}
	

	function httpError( $code, $message, $description )
	{
		header( "HTTP/1.1 $code $message" );
		echo ( "<h1>$message</h1>\n$description" );
	}	
}

		

// Frankly, I don't trust PHP's magic quotes.  The most dangerous characters,
// quote ('), semicolon (;), and less-than (<) aren't valid for most parameters anyway, so I'll
// screen them out just to be sure.  #GEOF#
function sanitize( $field )
{
	if ( strchr( $field, "'" ) !== false )
		return false;
	elseif ( strchr( $field, ';' ) !== false )
		return false;
	elseif ( strchr( $field, '<' ) !== false )
		return false;
	return true;
}

// It sure doesn't hurt to make sure that numbers are really numbers either.
function isnum( $field )
{
	return strspn( $field, '0123456789' ) == strlen( $field );
}

function isXPathSafe( $xpath )
{
	$parts = split( $xpath, '/' );
	foreach ( $parts as $part )
	{
		if ( $part != '' && ! preg_match( '/^[a-zA-Z]+\[\d+\]$/', $part ) )
			return false;
	}
	return true;
}

?>
