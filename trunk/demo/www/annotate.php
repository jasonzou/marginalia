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
 *
 * $Id$
 */

require_once( "config.php" );
require_once( "annotation.php" );
require_once( "annotate-db.php" );
require_once( "marginalia-php/block-range.php" );
require_once( "marginalia-php/xpath-range.php" );
require_once( "marginalia-php/helper.php" );

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
		// Can't sanitize $username - it might contain a single quote, e.g. for some French names starting with d',
		// or some romanization of other languages, e.g. the old romanization of Mandarin
		if ( $url == null || $url == '' )
			AnnotationService::httpError( 400, 'Bad Request', 'Bad URL' );
		else
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
				elseif ( null == $format || 'atom' == $format )
					AnnotationService::getAtom( $annotations );
				elseif ( 'overlap' == $format )
					AnnotationService::getOverlap( $annotations );
				elseif ( 'block-users' == $format )
					AnnotationService::getBlockUsers( $annotations, $url );
				else
					$this->httpError( 400, 'Bad Request', 'Unknown format' );
			}
		}
	}
	
	
	function createAnnotation()
	{
		global $CFG;
		
		$db = new AnnotationDB( );
		if ( ! $db->open( $CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->db ) )
			AnnotationService::httpError( 500, 'Internal Service Error', 'Unable to connect to database' );
		else
		{
			// Strip magicquotes if necessary
			$params = array();
			foreach ( array_keys( $_POST ) as $param )
				$params[ $param ] = unfix_quotes( $_POST[ $param ] );
			// Parse annotation values
			$annotation = new Annotation( );
			$error = MarginaliaHelper::annotationFromParams( $annotation, $params );
			if ( $error )
				AnnotationService::httpError( MarginaliaHelper::httpResultCodeForError( $error ), 'Error', $error );
			else
			{
				// Store to the database
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

		$db = new AnnotationDB( );
		if ( ! $db->open( $CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->db ) )
			AnnotationService::httpError( 500, 'Internal Service Error', 'Unable to connect to database' );
		else
		{
			// This is like a try...catch block, but since exceptions don't (?) exist in PHP4,
			// if something goes wrong, simply break out of the do...while without executing
			// remaining code in the block.
			do
			{
				$annotation = $db->getAnnotation( $id );
				if ( null === $annotation )
				{
					AnnotationService::httpError( 404, 'Not Found', 'No such annotation' );
					break;
				}

				// Set only the fields that were passed in
				$annotation->fromArray( $params );
				
				// Update the annotation in the database
				if ( $db->updateAnnotation( $annotation ) )
					header( 'HTTP/1.1 204 Updated' );
				else
					AnnotationService::httpError( 500, 'Internal Service Error', 'Update failed' );
			}
			while( 0 );

			$db->release( );
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

	/**
	 * Get the most recent date on which an annotation was modified
	 * Used for feed last modified dates
	 */
	function getLastModified( &$annotations )
	{
		global $CFG;
		
		// Get the last modification time of the feed
		$lastModified = $CFG->installDate;
		if ( $annotations )
		{
			foreach ( $annotations as $annotation )
			{
				$modified = $annotation->getModified( );
				if ( null != $modified && $modified > $lastModified )
					$lastModified = $modified;
			}
		}
		return $lastModified;
	}
	
	
	/**
	 * Emit an Atom document for a list of annotations
	 * The annotations should already be sorted
	 */
	function getAtom( &$annotations )
	{
		global $CFG;

		$feedLastModified = AnnotationService::getLastModified( $annotations );
		$feedTagUri = "tag:" . $CFG->host . ',' . date( '2005-07-20', $CFG->installDate ) . ":annotation";
		
		header( 'Content-Type: application/xml' );
		echo( '<?xml version="1.0" encoding="utf-8"?>' . "\n" );
		echo MarginaliaHelper::generateAnnotationFeed( $annotations, $feedTagUri, $feedLastModified, $CFG->annotate_servicePath, $tagHost );
	}

	
	function getOverlap( &$annotations )
	{
		$overlaps = MarginaliaHelper::calculateOverlaps( $annotations );
		header( 'Content-Type: text/plain' );
		echo MarginaliaHelper::generateOverlaps( $annotations );
	}

	function getBlockUsers( &$annotations, $url )
	{
		header( 'Content-Type: application/xml' );
		echo '<?xml version="1.0" encoding="utf-8"?>'."\n";
		echo MarginaliaHelper::generateBlockUsers( $annotations, $url );
	}
	
	function httpError( $code, $message, $description )
	{
		header( "HTTP/1.1 $code $message" );
		echo ( "<h1>$message</h1>\n$description" );
	}	
}


// It sure doesn't hurt to make sure that numbers are really numbers either.
function isnum( $field )
{
	return strspn( $field, '0123456789' ) == strlen( $field );
}

?>
