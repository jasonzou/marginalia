<?php

class AnnotationKeywordsDB
{
	function listKeywords( $userid )
	{
		global $CFG;
		$query = 'select name, description from '.$CFG->prefix.'annotation_keywords where userid='.(int)$userid.' order by name';
		$keywordSet = get_records_sql( $query );
		$keywords = array( );
		if ( $keywordSet )
		{
			$i = 0;
			foreach ( $keywordSet as $r )
				$keywords[ $i++ ] = AnnotationGlobals::recordToKeyword( $r );
		}
		return $keywords;
	}
	
	function getKeyword( $userid, $name )
	{
		global $CFG;
		$query = 'select * from '.$CFG->prefix.'annotation_keywords where userid='.(int)$userid
			." and name='".addslashes($name)."'";
		$resultSet = get_record_sql( $query );
		if ( $resultSet && count( $resultSet ) != 0 )
		{
			$keyword = AnnotationGlobals::recordToKeyword( $resultSet ); 
			return $keyword;
		}
		else
			return null;
	}
	
	function createKeyword( $userid, $keyword )
	{
		global $CFG;
		if ( preg_match( '/:/', $keyword->name ) || preg_match( '/^\s*$/', $keyword->name ) )
			return False;
		else
		{
			$record = AnnotationGlobals::keywordToRecord( $keyword );
			$record->userid = (int)$userid;
			$query = 'insert into '.$CFG->prefix.'annotation_keywords (userid,name,description ) values ('
				.(int)$userid
				.", '".addslashes($keyword->name)."', '".addslashes($keyword->description)."')";
			return execute_sql( $query, false );
		}
	}
	
	function updateKeyword( $userid, $keyword )
	{
		global $CFG;
		$query = 'update '.$CFG->prefix."annotation_keywords set description='"
			. $keyword->description . "' where userid=".(int)$userid." and name='".$keyword->name."'";
		return execute_sql( $query, false );
	}
	
	function deleteKeyword( $userid, $name )
	{
		global $CFG;
		$query = "delete from ".$CFG->prefix.'annotation_keywords where userid='
			. (int)$userid . " and name='" . addslashes( $name ) . "'";
		return execute_sql( $query, false );
	}
}

?>
