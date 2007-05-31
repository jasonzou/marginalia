<?PHP

/*
 * annotate-db.php
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

// Yeah, gotta love the mess that is PHP
function unfix_quotes( $value )
{
	return get_magic_quotes_gpc( ) != 1 ? $value : stripslashes( $value );
}


class AnnotationDB
{
	function open( $dbhost, $dbuser, $password, $dbname )
	{
		// I'm connecting directly to mysql for simplicity here.  Don't forget to set password correctly in mysql:
		// SET PASSWORD FOR 'some_user'@'some_host' = OLD_PASSWORD('newpwd');
		mysql_connect( $dbhost, $dbuser, $password );
		if ( ! @mysql_select_db( $dbname ) )
			return false;
		$CFG->dbopen += 1;
		return true;
	}
		
	function release( )
	{
		$CFG->dbopen -= 1;
		if ( 0 == $CFG->dbopen )
			mysql_close( );
	}

	function createAnnotation( &$annotation )
	{
		global $CFG, $USER;

		$link = $annotation->getLink( );
		$blockRange = $annotation->getBlockRange( );
		$xpathRange = $annotation->getXPathRange( );
		$blockStart = $blockRange->getStart( );
		$blockEnd = $blockRange->getEnd( );
		$xpathStart = $xpathRange->getStart( );
		$xpathEnd = $xpathRange->getEnd( );
		
		$sUser			= addslashes( $USER->username );
		$sUrl			= addslashes( $annotation->getUrl( ) );
		$sNote			= addslashes( $annotation->getNote( ) );
		$sAccess		= addslashes( $annotation->getAccess( ) );
		$sQuote			= addslashes( $annotation->getQuote( ) );
		$sQuote_title	= addslashes( $annotation->getQuoteTitle( ) );
		$sQuote_author	= addslashes( $annotation->getQuoteAuthor( ) );
		$sLink			= null == $link ? 'null' : "'".addslashes( $link )."'";
		
		// In a running application, all queries should be parameterized for security,
		// not concatenated together as I am doing here.
		$query = "insert into $CFG->dbannotation "
			. "(userid, url, note, access"
			. ", quote, quote_title, quote_author, link, created"
			. ", start_xpath, start_block, start_word, start_char"
			. ", end_xpath, end_block, end_word, end_char"
			. ") values ("
			. "'$sUser', '$sUrl', '$sNote', '$sAccess'"
			. ", '$sQuote', '$sQuote_title', '$sQuote_author', $sLink, now()"
			. ", '".$xpathStart->getPathStr()."', '".$blockStart->getPaddedPathStr()."', ".$blockStart->getWords().", ".$blockStart->getChars()
			. ", '".$xpathEnd->getPathStr()."', '".$blockEnd->getPaddedPathStr()."', ".$blockEnd->getWords().", ".$blockEnd->getChars()
			. ")";
	//	echo "\nQUERY: $query\n\n";
		mysql_query( $query );
		$r = 1 == mysql_affected_rows( ) ? mysql_insert_id( ) : 0;
		return $r;
	}
	
	function deleteAnnotation( $id )
	{
		global $CFG, $USER;
		
		$sUser = addslashes( $USER->username );
		$sId = (int) $id;
		// In a running application, all queries should be parameterized for security,
		// not concatenated together as I am doing here.
		$query = "delete from $CFG->dbannotation where id=$sId and userid='$sUser'";
		
		mysql_query( $query );
		$r = mysql_affected_rows( ) == 1 ? true : false;
		return $r;
	}
	
	function updateAnnotation( $id, $note, $access, $link )
	{
		global $CFG, $USER;
		
		$sId = (int) $id;
		$sNote = null === $note ? null : addslashes( $note );
		$sAccess = null === $access ? null : addslashes( $access );
		$sLink = null === $link ? null : addslashes( $link );
		$query = '';
		// TODO: Should add support for changing ranges (as when called from MarginaliaDirect)
		if ( null !== $note )	$query .= "note='$sNote'";
		if ( null !== $access )	$query = AnnotationDB::appendToUpdateStr( $query, "access='$sAccess'" );
		if ( null !== $link )	$query = AnnotationDB::appendToUpdateStr( $query, "link='$sLink'" );
		// In a running application, all queries should be parameterized for security,
		// not concatenated together as I am doing here.
		$query = "update $CFG->dbannotation set $query where id=$sId";
		
		mysql_query( $query );
		// (Somewhat) perversely, if fields are set to the values they already have (i.e. there is no actual change),
		// the number of affected rows is zero.
		$r = mysql_affected_rows( );
		$r = $r == 0 || $r == 1 ? true : false;
		return $r;
	}
	
	function appendToUpdateStr( $query, $assignment )
	{
		if ( null == $query || '' == $query )
			return $assignment;
		else
			return $query . ', ' . $assignment;
	}

	function getQueryCondition( $url, $userid )
	{
		// determine the filter for the select statement
		$sUrl = addslashes( $url );
		$cond .= strchr( $sUrl, '*' ) === false ? "where url='$sUrl' " : "where url like '" . str_replace( '*', '%', $sUrl ) . "'";
		if ( $username != null )
		{
			$sUser = addslashes( $userid );
			$cond .= " and $user='$sUser'";
		}
		return $cond;
	}
	
	// Get the last updated time for a particular annotation query
	function getFeedLastModified( $url, $userid )
	{
		global $CFG;
		
		// In a running application, all queries should be parameterized for security,
		// not concatenated together as I am doing here.
		$cond = AnnotationDB::getQueryCondition( $url, $userid );
		$query = "select max(modified) modified from $CFG->dbannotation $cond";
		$result = mysql_query( $query );
		if ( $result )
		{
			$row = mysql_fetch_assoc( $result );
			return $row ? strtotime( $row[ 'modified' ] ) : $CFG->installDate;
		}
		else
			return strtotime( $CFG->installDate );
	}
	
	function listAnnotations( $url, $userid )
	{
		global $CFG;
		
		$cond = AnnotationDB::getQueryCondition( $url, $userid );
		
		// Get the data rows
		$query = "select * from $CFG->dbannotation $cond";
		$query .= " order by url, start_block, start_word, start_char, end_block, end_word, end_char";
		$result = mysql_query( $query );

		$annotations = array( );
		if ( $result )
		{
			// Individual entries ----
			while( $row = mysql_fetch_assoc( $result ) )
			{
				$annotation = new Annotation( );
				$annotation->fromArray( $row );
				$blockRange = new BlockRange(
					new BlockPoint( $row[ 'start_block' ], $row[ 'start_word' ], $row[ 'start_char' ] ),
					new BlockPoint( $row[ 'end_block' ], $row[ 'end_word' ], $row[ 'end_char' ] ) );
				$annotation->setBlockRange( $blockRange );
				if ( $row[ 'start_xpath' ] != null )
				{
					$xpathRange = new XPathRange(
						new XPathPoint( $row[ 'start_xpath' ], $row[ 'start_word' ], $row[ 'start_char' ] ),
						new XPathPoint( $row[ 'end_xpath' ], $row[ 'end_word' ], $row[ 'end_char' ] ) );
					$annotation->setXPathRange( $xpathRange );
				}
				$annotations[ ] = $annotation;
			}
		}
		return $annotations;
	}
}

?>
