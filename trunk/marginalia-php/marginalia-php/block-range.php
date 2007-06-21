<?
/*
 * block-range.php
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
 *
 * $Id$
 */

/** A range in an HTML document
 * Used for locating highlights
 * Immutable (fromString should be treated only as a constructor)
 */
class BlockRange
{
	function BlockRange( $startPoint=null, $endPoint=null )
	{
		$this->start = $startPoint;
		$this->end = $endPoint;
	}
	
	function fromString( $s )
	{
		$r = true;
		if ( null != $this->start || null != $this->end )
			die( "Attempt to modify BlockRange" );
		// Standard format, e.g. /2/3.1;/2/3.5
		if ( False !== strpos( $s, ';' ) )
		{
			$points = split( ';', $s );
			$this->start = new BlockPoint( $points[ 0 ] );
			$this->end = new BlockPoint( $points[ 1 ] );
		}
		// Old block format, e.g. /2 3.1 3.5
		elseif ( preg_match( '/^\s*(\/[\/0-9]*)\s+(\d+)\.(\d+)\s+(\d+)\.(\d+)\s*$/', $s, $matches ) )
		{
			$this->start = new BlockPoint( $matches[1], (int) $matches[2], (int) $matches[3] );
			$this->end = new BlockPoint( $matches[1], (int) $matches[4], (int) $matches[5] );
		}
		// Old word format, e.g. 7.1 7.5
		elseif ( preg_match( '/^\s*(\d+)\.(\d+)\s+(\d+)\.(\d+)\s*$/', $s, $matches ) )
		{
			$this->start = new BlockPoint( '/', (int) $matches[1], (int) $matches[2] );
			$this->end = new BlockPoint( '/', (int) $matches[3], (int) $matches[4] );
		}
		else
			$r = false;
		return $r;
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
//		echo "this->start=" . $this->start . ", this->end=" . $this->end . "\n";
		return $this->start->toString( ) . ';' . $this->end->toString( );
	}
}


/** Represents a point in an annotated document
 *  Used for locating start and end of highlight ranges
 *  Immutable.
 */
class BlockPoint
{
	/**
	 * Two ways to call:
	 * - BlockPoint( '/2/7/1', 15, 3 )
	 * - BlockPoint( '/2/7/1/15.3' )
	 */
	function BlockPoint( $blockStr, $words=null, $chars=null )
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
		$this->words = (int) $words;
		$this->chars = (int) $chars;
	}
	
	/**
	 * Compare location with another point.
	 * 0 - same point, -1 this one preceeds the other, 1 this one follows the other
	 */
	function compare( $point2 )
	{
		$len1 = count( $this->path );
		$len2 = count( $point2->path );
		for ( $i = 0;  $i  < min( $len1, $len2 );  $i += 1 )
		{
			if ( $this->path[ $i ] < $point2->path[ $i ] )
				return -1;
			elseif ( $this->path[ $i ] > $point2->path[ $i ] )
				return 1;
		}
		if ( $len1 < $len2 )
			return -1;
		elseif ( $len1 > $len2 )
			return 1;
		elseif ( $this->words < $point2->words )
			return -1;
		elseif ( $this->words > $point2->words )
			return 1;
		elseif ( $this->chars < $point2->chars )
			return -1;
		elseif ( $this->chars > $point2->chars )
			return 1;
		return 0;
	}
	
	/**
	 * Get the block path as a string of slash-separated indices
	 */
	function getPathStr( )
	{
		$s = '';
		for ( $i = 0;  $i < count( $this->path );  ++$i )
			$s .= '/' . $this->path[ $i ];
		return $s;
	}
	
	/**
	 * Get the block path a string of slash-separated indices, each one zero-padded to 4 places
	 * This is the storage format used in the database to allow string comparisons to order
	 * paths; it should not be used externally (use getPathStr instead).
	 */
	function getPaddedPathStr( )
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
		return $this->getPathStr( ) . '/' . $this->words . '.' . $this->chars;
	}
}

?>
