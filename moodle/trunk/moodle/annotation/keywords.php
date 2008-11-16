<?php

/*
 * keywords.php
 * Handles annotation keyword requests
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

require_once( "../config.php" );
require_once( 'marginalia-php/Keyword.php' );
require_once( 'marginalia-php/KeywordService.php' );
require_once( 'marginalia-php/MarginaliaHelper.php' );
require_once( 'config.php' );
require_once( 'KeywordsDB.php' );
require_once( 'AnnotationGlobals.php' );

require_login();

class MoodleKeywordService extends KeywordService
{
	function MoodleKeywordService( $username )
	{
		global $CFG;
		KeywordService::KeywordService( 
			AnnotationGlobals::getHost(),
			AnnotationGlobals::getKeywordServicePath(),
			$username,
			$CFG->wwwroot );
		$this->tablePrefix = $CFG->prefix;
	}
	
	function doListKeywords( )
	{
		global $USER;
		$keywords = AnnotationKeywordsDB::listKeywords( $this->currentUserId );
		$logUrl = 'keywords.php';
		add_to_log( null, 'annotation', 'list', $logUrl );
		return $keywords;
	}
	
	/**
	 * Because keywords are automatically generated from margin notes,
	 * they cannot be created, updated, or deleted, nor is there any reason
	 * to fetch them individually.
	 */
	function doGetKeyword( $name )
	{
		header( 'HTTP/1.1 501 Not Implemented' );
		echo "Individual keywords cannot be fetched";
		return False;
	}
	
	function doCreateKeyword( $keyword )
	{
		header( 'HTTP/1.1 501 Not Implemented' );
		echo "Keywords are automatically generated";
		return False;
	}
	
	function doUpdateKeyword( $keyword )
	{
		header( 'HTTP/1.1 501 Not Implemented' );
		echo "Keywords are automatically generated";
		return False;
	}
	
	function doDeleteKeyword( $name )
	{
		header( 'HTTP/1.1 501 Not Implemented' );
		echo "Keywords are automatically generated";
		return False;
	}
}

if ( AN_EDITABLEKEYWORDS )
{
	$service = new MoodleKeywordService( isguest() ? null : $USER->username );
	$service->dispatch( );
}
else
{
	header( 'HTTP/1.1 501 Not Implemented' );
	echo "This Moodle installation does not support keywords";
}

?>
