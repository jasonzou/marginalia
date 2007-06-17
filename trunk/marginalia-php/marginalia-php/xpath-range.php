<?
/*
 * xpath-range.php
 * representation of range in an HTML document as specified by two XPath expressions
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

class XPathRange
{
	function XPathRange( $startPoint=null, $endPoint=null )
	{
		$this->start = $startPoint;
		$this->end = $endPoint;
	}
	
	function fromString( $s )
	{
		if ( null != $this->start || null != $this->end )
			die( "Attempt to modify XPathRange" );
		$points = split( ';', $s );
		$this->start = new XPathPoint( $points[ 0 ] );
		$this->end = new XPathPoint( $points[ 1 ] );
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
		return $this->start->toString( ) . ';' . $this->end->toString( );
	}
}

class XPathPoint
{
	/**
	 * Two ways to call:
	 * - XPathPoint( '/p[2]/p[7]', 15, 3 )
	 * - XPathPoint( '/p[2]/p[7]/word(15,3)' )
	 */
	function XPathPoint( $xpathStr, $words=null, $chars=null )
	{
		if ( preg_match( '/^(.*)\/word\((\d+)\)\/char\((\d+)\)$/', $xpathStr, $matches ) )
		{
			if ( XPathPoint::isXPathSafe( $matches[ 1 ] ) )
				$this->path = $matches[ 1 ];
			$this->words = (int) $matches[ 2 ];
			$this->chars = (int) $matches[ 3 ];
		}
		else
		{
			if ( XPathPoint::isXPathSafe( $xpathStr ) )
				$this->path = $xpathStr;
			$this->words = $words;
			$this->chars = $chars;
		}
	}
	
	/**
	 * Get the xpath (specifying a particular element in the HTML document)
	 */
	function getPathStr( )
	{
		return $this->path;
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
		return $this->path . '/word(' . $this->words . ')/char(' . $this->chars . ')';
	}
	
	/**
	 * Check whether an untrested XPath expression is safe.  Calls to
	 * document(), for example, are dangerous.  This implementation
	 * only accepts a tiny subset of possible XPath expressions and
	 * may need to be extended.
	 */
	function isXPathSafe( $xpath )
	{
		$parts = split( '\/', $xpath );
		foreach ( $parts as $part )
		{
			if ( $part != '' && ! preg_match( '/^[a-zA-Z]+\[\d+\]$/', $part ) )
				return false;
		}
		return true;
	}
}

?>
