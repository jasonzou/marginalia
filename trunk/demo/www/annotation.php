<?php

/*
 * annotation.php
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

class Annotation
{
	function Annotation( )
	{
		$this->id = null;
		$this->url = null;
		$this->userId = null;
		$this->blockRange = null;
		$this->xpathRange = null;
		$this->note = null;
		$this->access = null;
		$this->quote = null;
		$this->quoteTitle = null;
		$this->quoteAuthor = null;
		$this->link = null;
		$this->created = null;
		$this->modified = null;
	}
	
	/** This method is intended to be called when an annotation is created via 
	 * a POST or PUT operation.  An associative array contains the values of
	 * various fields in string format.  If a field is not present in the array, 
	 * it will not be set.  The userid field cannot be set this way, because 
	 * that is session information (i.e. it must be the current user).
	 */
	function fromArray( $strings )
	{
		if ( array_key_exists( 'id', $strings ) )
			$this->setId( $strings[ 'id' ] );
		if ( array_key_exists( 'userid', $strings ) )
			$this->setUserId( $strings[ 'userid' ] );
		if ( array_key_exists( 'url', $strings ) )
			$this->setUrl( $strings[ 'url' ] );
		if ( array_key_exists( 'block-range', $strings ) )
		{
			$range = new BlockRange( );
			$range->fromString( $strings[ 'block-range' ] );
			$this->setBlockRange( $range );
		}
		if ( array_key_exists( 'xpath-range', $strings ) )
		{
			$range = new XPathRange( );
			$range->fromString( $strings[ 'xpath-range' ] );
			$this->setXPathRange( $range );
		}

		if ( array_key_exists( 'note', $strings ) )
			$this->setNote( $strings[ 'note' ] );
			
		if ( array_key_exists( 'access', $strings ) )
			$this->setAccess( $strings[ 'access' ] );
			
		if ( array_key_exists( 'quote', $strings ) )
			$this->setQuote( $strings[ 'quote' ] );
			
		if ( array_key_exists( 'quote_title', $strings ) )
			$this->setQuoteTitle( $strings[ 'quote_title' ] );
			
		if ( array_key_exists( 'quote_author', $strings ) )
			$this->setQuoteAuthor( $strings[ 'quote_author' ] );

		if ( array_key_exists( 'link', $strings ) )
			$this->setLink( $strings[ 'link' ] );
			
		if ( array_key_exists( 'created', $strings ) )
			$this->setCreated( $strings[ 'created' ] );
			
		if ( array_key_exists( 'modified', $strings ) )
			$this->setModified( $strings[ 'modified' ] );
	}

	function setId( $id )
	{ $this->id = $id; }
	
	function getId( )
	{ return $this->id; }
	
	function setUrl( $url )
	{ $this->url = $url; }
	
	function getUrl( )
	{ return $this->url; }
	
	function setUserId( $id )
	{ $this->userId = $id; }
	
	function getUserId( )
	{ return $this->userId; }
	
	function setBlockRange( &$range )
	{ $this->blockRange = $range; }
		
	function getBlockRange( )
	{ return $this->blockRange; }
	
	function setXPathRange( &$range )
	{ $this->xpathRange = $range; }
	
	function getXPathRange( )
	{ return $this->xpathRange; }
	
	function setNote( $note )
	{ $this->note = $note; }
	
	function getNote( )
	{ return $this->note; }
	
	function setAccess( $access )
	{ $this->access = $access; }
	
	function getAccess( )
	{ return $this->access; }
	
	function setQuote( $quote )
	{ $this->quote = $quote; }
	
	function getQuote( )
	{ return $this->quote; }
	
	function setQuoteTitle( $quoteTitle )
	{ $this->quoteTitle = $quoteTitle; }
	
	function getQuoteTitle( )
	{ return $this->quoteTitle; }
	
	function setQuoteAuthor( $quoteAuthor )
	{ $this->quoteAuthor = $quoteAuthor; }
	
	function getQuoteAuthor( )
	{ return $this->quoteAuthor; }
	
	function setLink( $link )
	{ $this->link = $link; }
	
	function getLink( )
	{ return $this->link; }
	
	function setCreated( $created )
	{ $this->created = strtotime( $created ); }
	
	function getCreated( )
	{ return $this->created; }
	
	function setModified( $modified )
	{ $this->modified = strtotime( $modified ); }
	
	function getModified( )
	{ return $this->modified; }
}

?>
