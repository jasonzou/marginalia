<?php

/*
 * annotate.php
 * handles annotation http requests
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

require_once( "marginalia-php/MarginaliaHelper.php" );
require_once( "marginalia-php/Annotation.php" );
require_once( "marginalia-php/AnnotationService.php" );
require_once( "config.php" );
require_once( "annotate-db.php" );

class DemoAnnotationService extends AnnotationService
{
	var $db;
	
	function DemoAnnotationService( )
	{
		global $CFG;
	
		$curuser = array_key_exists( 'curuser', $_GET ) ? $_GET[ 'curuser' ] : 'anonymous';		

		AnnotationService::AnnotationService( $CFG->host, $CFG->annotate_servicePath, $CFG->installDate, $curuser );
	}

	function beginRequest( )
	{
		global $CFG;

		$this->db = new AnnotationDB( );
		if ( ! $this->db->open( $CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->db ) )
		{
			$this->httpError( 500, 'Internal Service Error', 'Unable to connect to database' );
			return False;
		}
		return True;
	}
	
	function endRequest( )
	{
		$this->db->release( );
	}
		
	function doListAnnotations( $url, $username, $block )
	{
		return $this->db->listAnnotations( $url, $username, $block );
	}
	
	function doGetAnnotation( $id )
	{
		return $this->db->getAnnotation( $id );
	}
	
	function doCreateAnnotation( &$annotation )
	{
		// This is a hack to allow testing of multiuser features:
		$annotation->setUserId( array_key_exists( 'userid', $_POST ) ? $_POST[ 'userid' ] : 'anonymous' );
		return $this->db->createAnnotation( $annotation );
	}
	
	function doUpdateAnnotation( $annotation )
	{
		return $this->db->updateAnnotation( $annotation );
	}
	
	function doDeleteAnnotation( $id )
	{
		$this->db->deleteAnnotation( $id );
		return True;
	}
}

$annotationService = new DemoAnnotationService( );
$annotationService->dispatch( );

?>
