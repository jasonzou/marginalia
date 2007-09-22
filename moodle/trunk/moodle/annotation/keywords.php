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
		$keywords = AnnotationKeywordsDB::listKeywords( $USER->id );
		$logUrl = 'keywords.php';
		add_to_log( null, 'annotation', 'list', $logUrl );
		return $keywords;
	}
	
	function doGetKeyword( $name )
	{
		global $USER;
		return AnnotationKeywordsDB::getKeyword( $USER->id, $name );
	}
	
	function doCreateKeyword( $keyword )
	{
		global $USER;
		if ( null != $this->doGetKeyword( $keyword->name ) )
			return False;
		elseif ( AnnotationKeywordsDB::createKeyword( $USER->id, $keyword ) )
		{
			add_to_log( null, 'annotation', 'create', 'keywords.php', $keyword->name );
			return True;
		}
		else
			return False;
	}
	
	function doUpdateKeyword( $keyword )
	{
		global $USER;
		add_to_log( null, 'annotation', 'update', "keywords.php", $keyword->name );
		return AnnotationKeywordsDB::updateKeyword( $USER->id, $keyword );
	}
	
	function doDeleteKeyword( $name )
	{
		global $USER;
		AnnotationKeywordsDB::deleteKeyword( $USER->id, $name );
		add_to_log( null, 'annotation', 'delete', "keywords.php", $name );
		return True;
	}
}

$service = new MoodleKeywordService( isguest() ? null : $USER->username );
$service->dispatch( );

?>
