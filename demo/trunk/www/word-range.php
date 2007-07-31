<?
/*
 * word-range.php
 * representations for points in an HTML document and for ranges (defined by of points)
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

/** A range in an HTML document
 * Used for locating highlights
 * Immutable (fromString should be treated only as a constructor)
 */
class WordRange
{
	function WordRange( $startPoint=null, $endPoint=null )
	{
		$this->start = $startPoint;
		$this->end = $endPoint;
	}
	
	function fromString( $s )
	{
		if ( null != $this->start || null != $this->end )
			die( "Attempt to modify WordRange" );
		$points = split( ':', $s );
		$this->start = new WordPoint( $points[ 0 ] );
		$this->end = new WordPoint( $points[ 1 ] );
	}

	function getStart( )
	{
		return $this->start;
	}
	
	function getEnd( )
	{
		return $this->end;
	}
	
	function toString( )
	{
		return $this->start->toString( ) . ':' . $this->end->toString( );
	}
}


/** Represents a point in an annotated document
 *  Used for locating start and end of highlight ranges
 *  Immutable.
 */
class WordPoint
{
	/**
	 * Two ways to call:
	 * - WordPoint( '/2/7/1', 15, 3 )
	 * - WordPoint( '/2/7/1/15.3' )
	 */
	function WordPoint( $blockStr, $words=null, $chars=null )
	{
		$dot = strpos( $blockStr, '.' );
		$parts = split( '/', $blockStr );
		$n = count( $parts );
		
		// Transform the second call style (all one string)
		// into the correct parameters for the first
		if ( null === $words )
		{
			if ( false !== $dot )
			{
				$slash = strrpos( $blockStr, '/' );
				$words = (int) substr( $blockStr, $slash + 1, $dot - $slash );
				$chars = (int) substr( $blockStr, $dot + 1 );
				$blockStr = substr( $blockStr, 0, $slash );
				$n -= 1;
			}
		}
		
		// The blockStr may be padded with zeros.  Strip them.
		$this->path = array( );
		for ( $i = 1;  $i < $n;  ++$i )
			$this->path[] = (int) $parts[ $i ];
		$this->words = $words;
		$this->chars = $chars;
	}
	
	/**
	 * Get the block path as a string of slash-separated indices
	 */
	function getBlockStr( )
	{
		$s = '';
		for ( $i = 0;  $i < count( $this->path );  ++$i )
			$s .= '/' . $this->path[ $i ];
		return $s;
	}
	
	/**
	 * Get the block path a string of slash-separated indices, each one zero-padded to 4 places
	 * This is the storage format used in the database to allow string comparisons to order
	 * paths; it should not be used externally (use getBlockStr instead).
	 */
	function getPaddedBlockStr( )
	{
		$s = '';
		for ( $i = 0;  $i < count( $this->path );  ++$i )
			$s .= '/' . sprintf( '%04d', $this->path[ $i ] );
		return $s;
	}
	
	function getWords( )
	{
		return $this->words;
	}
	
	function getChars( )
	{
		return $this->chars;
	}
	
	function toString( )
	{
		return $this->getBlockStr( ) . '/' . $this->words . '.' . $this->chars;
	}
}

?>
