<?php

/*
 * helper.php
 * shared helper functions for marginalia server implementations
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
 
// Errors
define( 'NO_ERROR', False );
define( 'XPATH_SECURITY_ERROR', 'xpath-security-error' );
define( 'URL_SCHEME_ERROR', 'url-error' );
define( 'ACCESS_VALUE_ERROR', 'access-value-error' );
define( 'ACTION_VALUE_ERROR', 'action-value-error' );

class MarginaliaHelper
{
	/**
	 * Set annotation fields based on parameters (e.g. from $_POST)
	 * For updates, first retrieve the stored annotation, then update it here.
	 * If this fails, the passed annotation object may have already been altered.
	 * Different implementations may have different annotation objects.  All should
	 * be compatible with this code if they have the appropriate getters and setters.
	 */
	function annotationFromParams( &$annotation, &$params )
	{
		// ID
		if ( array_key_exists( 'id', $params ) )
		{
			$id = $params[ 'id' ];
			$annotation->setId( $id );
		}
		
		// UserId
		if ( array_key_exists( 'userid', $params ) )
		{
			$userid = $params[ 'userid' ];
			$this->setUserId( $userid );
		}

		// Block Range
		if ( array_key_exists( 'block-range', $params ) )
		{
			$blockRange = new BlockRange( );
			$blockRange->fromString( $params[ 'block-range' ] );
			$annotation->setBlockRange( $blockRange );
		}
		
		// XPath Range
		if ( array_key_exists( 'xpath-range', $params ) )
		{
			$xpathRange = new XPathRange( );
			$xpathRange->fromString( $params[ 'xpath-range' ] );
			// TODO: Scan XPath to make sure it's safe
			if ( ! XPathPoint::isXPathSafe( $xpathRange->start->getPathStr() ) || ! XPathPoint::isXPathSafe( $xpathRange->end->getPathStr( ) ) )
				return XPATH_SECURITY_ERROR;
			$annotation->setXPathRange( $xpathRange );
		}
		
		// URL
		if ( array_key_exists( 'url', $params ) )
		{
			$url = $params[ 'url' ];
			if ( ! MarginaliaHelper::isUrlSafe( $url ) )
				return URL_SCHEME_ERROR;
			$annotation->setUrl( $url );
		}

		// Note
		if ( array_key_exists( 'note', $params ) )
		{
			$note = $params[ 'note' ];
			$annotation->setNote( $note );			
		}
		
		// Quote
		if ( array_key_exists( 'quote', $params ) )
		{
			$quote = $params[ 'quote' ];
			$annotation->setQuote( $quote );
		}
		
		// QuoteTitle
		if ( array_key_exists( 'quote_title', $params ) )
		{
			$quoteTitle = $params[ 'quote_title' ];
			$annotation->setQuoteTitle( $quoteTitle );
		}
		
		// QuoteAuthor
		if ( array_key_exists( 'quote_author', $params ) )
		{
			$quoteAuthor = $params[ 'quote_author' ];
			$annotation->setQuoteAuthor( $quote_author );
		}
		
		// Access
		if ( array_key_exists( 'access', $params ) )
		{
			if ( ! Annotation::isAccessValid( $access ) )
				return ACCESS_VALUE_ERROR;
			$access = $params[ 'access' ];
			$annotation->setAccess( $access );
		}
		
		// Action
		if ( array_key_exists( 'action', $params ) )
		{
			$action = $params[ 'action' ];
			if ( ! Annotation::isActionValid( $action ) ) 
				return ACTION_VALUE_ERROR;
			$annotation->setAction( $action );
		}
			
		// Link
		if ( array_key_exists( 'link', $params ) )
		{
			$link = $params[ 'link' ];
			if ( ! MarginaliaHelper::isUrlSafe( $link ) )
				return URL_SCHEME_ERROR;
			$annotation->setLink( $link );
		}
		
		// Created
		if ( array_key_exists( 'created', $params ) )
		{
			$created = $params[ 'created' ];
			// TODO: verify date format
			$this->setCreated( $created );
		}
			
		// Modified
		if ( array_key_exists( 'modified', $params ) )
		{
			$modified = $params[ 'modified' ];
			$this->setModified( $modified );
		}

		// Ok, I know in PHP it's traditional to return True for success,
		// but that requires the triple === which drives me nuts and is
		// easy to forget (and if ( f() ) won't work), so I'll go with the
		// old C / Unix tradition and return 0.
		return 0;
	}
	
	function generateAnnotationFeed( &$annotations, $feedTagUri, $feedLastModified, $servicePath, $tagHost )
	{
		$NS_PTR = 'http://www.geof.net/code/annotation/';
		$NS_ATOM = 'http://www.w3.org/2005/Atom';
		
		// About the feed ----
		echo "<feed xmlns:ptr='$NS_PTR' xmlns='$NS_ATOM' ptr:annotation-version='0.4'>\n";
		// This would be the link to the summary page:
		//echo( " <link rel='alternate' type='text/html' href='" . htmlspecialchars( "$CFG->wwwroot$url/annotations" ) . "'/>\n" );
		echo " <link rel='self' type='text/html' href=\"" . htmlspecialchars( $servicePath ) . "\"/>\n";
		echo " <updated>" . date( 'Ymd', $feedLastModified ) . 'T' . date( 'HiO', $feedLastModified ) . "</updated>\n";
		echo " <title>Annotations</title>";
		echo " <id>$feedTagUri</id>\n";
		
		for ( $i = 0;  $i < count( $annotations );  ++$i )
		{
			$annotation =& $annotations[ $i ];
			echo $annotation->toAtom( $tagHost, $servicePath );
		}
		echo "</feed>\n";
	}
	
	
	/**
	 * Convert an annotation to an Atom entry
	 * Logically this is part of the Annotation class, but different applications implement
	 * that differently (OJS in particular), so it's here, but called through Annotation->toAtom().
	 */
	function annotationToAtom( &$annotation, $tagHost, $servicePath )
	{
		$NS_XHTML = 'http://www.w3.org/1999/xhtml';
	
		$sUserId = htmlspecialchars( $annotation->getUserId() );
		$sNote = htmlspecialchars( $annotation->getNote() );
		$sQuote = htmlspecialchars( $annotation->getQuote() );
		$sUrl = htmlspecialchars( $annotation->getUrl() );
		$sLink = htmlspecialchars( $annotation->getLink() );
		$sQuoteTitle = htmlspecialchars( $annotation->getQuoteTitle() );
		$sQuoteAuthor = htmlspecialchars( $annotation->getQuoteAuthor() );
		$sAccess = htmlspecialchars( $annotation->getAccess() );
		$sAction = htmlspecialchars( $annotation->getAction() );
		
		// title for display to reader
		if ( 'edit' == $annotation->getAction() )
			$title = "Edit to \"$sQuoteTitle\"";
		elseif ( $sNote )
			$title = "Annotation of \"$sQuoteTitle\"";
		else
			$title = "Highlight of \"$sQuoteTitle\"";
			
	
		// title and summary for display to reader
		if ( $sNote && $sQuote )
			$summary = $sNote.": \"".$sQuote."\"";
		elseif ( $sNote )
			$summary = $sNote;
		else
			$summary = $sQuote;
		
		$blockRange = $annotation->getBlockRange( );
		$sBlockRange = htmlspecialchars( $blockRange->toString() );
		$xpathRange = $annotation->getXPathRange( );
		
		$s = " <entry>\n";
		// Emit range in two formats:  block for sorting, xpath for authority and speed
		$s .= "  <ptr:range format='block'>$sBlockRange</ptr:range>\n";
		// Make 100% certain that the XPath expression contains no unsafe calls (e.g. to document())
		if ( $xpathRange && XPathPoint::isXPathSafe( $xpathRange->start->getPathStr() ) && XPathPoint::isXPathSafe( $xpathRange->end->getPathStr( ) ) )
			$s .= "  <ptr:range format='xpath'>".htmlspecialchars($xpathRange->toString())."</ptr:range>\n";
		$s .= "  <ptr:access>$sAccess</ptr:access>\n"
			. "  <ptr:action>$sAction</ptr:action>\n"
			. "  <title>$title</title>\n";
		// Use double quotes for some attributes because it's easier than passing ENT_QUOTES to
		// each call to htmlspecialchars
		$s .= "  <link rel='self' type='application/xml' href=\"" . htmlspecialchars( "$servicePath/$annotation->id" ) . "\"/>\n"
			. "  <link rel='alternate' type='text/html' title=\"$sQuoteTitle\" href=\"$sUrl\"/>\n";
		if ( $annotation->link )
			$s .= "  <link rel='related' type='text/html' title=\"$sNote\" href=\"$sLink\"/>\n";
		// TODO: Is this international-safe?  I could use htmlsecialchars on it, but that might not match the
		// restrictions on IRIs.  #GEOF#
		$s .= "  <id>tag:$tagHost," . date( 'Y-m-d', $annotation->getCreated() ) . ":".annotation/$annotation->getId()."</id>\n"
			. "  <updated>" . date( 'Y-m-d', $annotation->getModified() ) . 'T' . date( 'H:i:O', $annotation->getModified() ) . "</updated>\n";
		// Selected text as summary
		//echo "  <summary>$summary</summary>\n";
		// Author of the annotation
		$s .= "  <author>\n"
			. "   <name>$sUserId</name>\n"
			. "  </author>\n";
		// Contributor is the sources of the selected text
		$s .= "  <contributor>\n"
			. "   <name>$sQuoteAuthor</name>\n"
			. "  </contributor>\n";
	
		// Content area
		if ( $sLink )
		{
			if ( $sNote )
				$sNote = "<a href=\"$sLink\">$note</a>";
			else
				$sNote = "<a href=\"$sLink\">...</a>";
		}
		
		if ( 'edit' == $annotation->getAction() )
		{
			if ( $sNote )
				$sNote = "<ins>$sNote</ins>";
			if ( $sQuote )
				$sQuote = "<del>$sQuote</del>";
		}
	
		$s .= "  <content type='xhtml'>\n"
			. "   <div xmlns='$NS_XHTML' class='annotation'>\n"
			. "<p><cite><a href=\"$sUrl\">$sQuoteTitle</a></cite> (<span class='quoteAuthor'>$sQuoteAuthor</span>):</p>\n"
			. "<blockquote cite=\"$sUrl\"><p>$sQuote</p></blockquote>\n"
			. "<p class='note'>$sNote</p>\n"
			. "   </div>\n"
			. "  </content>\n"
			. " </entry>\n";
			
		return $s;
	}

	/**
	 * Emit a document for a list of overlaps
	 * Format:
	 *   depth block-range xpath-range
	 *   1 /5/2/1.1;/5/2/2.3 /div[5]/p[2]/word(1)/char(1);/div[5]/p[2]/word(2)/char(3)
	 * Note that fields are separated by whitespace, but the xpath might in future contain
	 * space characters (that is why it must be last on the line)
	 */
	function getOverlap( &$annotations )
	{
		$overlaps = MarginaliaHelper::calculateOverlaps( $annotations );
		
		header( 'Content-Type: text/plain' );
		//echo "# depth blockRange xpathRange\n";
		for ( $i = 0;  $i < count( $overlaps );  ++$i )
		{
			$overlap = $overlaps[ $i ];
			echo $overlap->depth . ' ' . $overlap->blockRange->toString( );
			if ( $overlap->xpathRange->start && $overlap->xpathRange->end )
				echo ' ' . $overlap->xpathRange->toString( );
			echo "\n";
		}
	}
	
	
	/**
	 * Calculate overlapping regions of highlight.  Instead of listing individual highlights,
	 * this provides a sequence of non-overlapping ranges - each of which represents one or
	 * more overlapping portions of highlighted ranges, plus a depth to say how many.
	 * Inspired by the GPL3 annotation implementation, but not currently used anywhere.
	 */
	function calculateOverlaps( &$annotations )
	{
		// Create two arrays:  one of range starts, the other of range ends
		$starts = $annotations;
		$ends = $annotations;
		usort( $starts, 'annotationCompareStart' );
		usort( $ends, 'annotationCompareEnd' );
		
		// Create an array to store overlap ranges
		$overlap = null;
		$overlaps = array( );
		
/*		// Debug
		header( 'Content-Type: text/plain' );
		echo "Starts\n";
		for ( $i = 0;  $i < count( $starts ); ++$i )
		{
			$range = $starts[ $i ]->getXPathRange();
			if ( $range )
				echo $range->start->toString( ) . "\n";
		}
		echo "\nEnds\n";
		for ( $i = 0;  $i < count( $starts ); ++$i )
		{
			$range = $ends[ $i ]->getXPathRange();
			if ( $range )
				echo $range->end->toString( ). "\n";
		}
		echo "\n";
*/		
		$start_i = 0;
		$end_i = 0;
		$depth = 0;
		while ( $end_i < count( $ends ) )
		{
			$end =& $ends[ $end_i ];
			$endBlock =& $end->getBlockRange( );
			$endXPath =& $end->getXPathRange( );

			if ( $start_i < count( $starts ) )
			{
				$start =& $starts[ $start_i ];
				$startBlock =& $start->getBlockRange( );
				$startXPath =& $start->getXPathRange( );
				$comp = $startBlock->start->compare( $endBlock->end );
			}
			else
				$comp = 1;	// Only ends remain
			
			if ( 0 == $comp )
			{
				; // Do nothing:  one starts, one ends - it's a wash
			}
			else
			{
				if ( $comp < 0 )
				{
					$blockPoint =& $startBlock->start;
					if ( $startXPath)
						$xpathPoint =& $startXPath->start;
					else
						$xpathPoint = null;
					++$depth;
					++$start_i;
				}
				else // $comp > 0
				{
					$blockPoint = &$endBlock->end;
					if ( $endXPath )
						$xpathPoint = &$endXPath->end;
					else
						$xpathPoint = null;
					--$depth;
					++$end_i;
				}
					
				// Close any existing overlap
				if ( $overlap )
				{
					$overlap->blockRange->end = $blockPoint;
					$overlap->xpathRange->end = $xpathPoint;
					$overlaps[ ] = $overlap;
//					echo "Created overlap " . $overlap->depth . ' ' . $overlap->blockRange->toString( ) . "<br/>\n";
					$overlap = null;
				}
				
				// Begin any new overlap
				if ( $depth > 0 )
				{
					$overlap = new Overlap( $depth );
					$overlap->blockRange->start = $blockPoint;
					$overlap->xpathRange->start = $xpathPoint;
				}
			}
		}
		return $overlaps;
	}
	
	
	function httpResultCodeForError( $error )
	{
		switch ( $error )
		{
			case URL_SCHEME_ERROR:
			case XPATH_SECURITY_ERROR:
			case ACCESS_VALUE_ERROR:
			case ACTION_VALUE_ERROR:
				return 400;
			default:
				return 500;
		}
	}
	
	/**
	 * Check whether an untrusted URL is safe for insertion in a page
	 * In particular, javascript: urls can be used for XSS attacks
	 */
	function isUrlSafe( $url )
	{
		$urlParts = parse_url( $url );
		$scheme = $urlParts[ 'scheme' ];
		if ( 'http' == $scheme || 'https' == $scheme || '' == $scheme )
			return true;
		else
			return false;
	}
}

class Overlap
{
	function Overlap( $depth )
	{
		$this->blockRange = new BlockRange( );
		$this->xpathRange = new XPathRange( );
		$this->depth = $depth;
	}
}
		


// Useful for sorting by range start position:
function annotationCompareStart( $a1, $a2 )
{
	$a1Block = $a1->getBlockRange( );
	$a2Block = $a2->getBlockRange( );
	return $a1Block->start->compare( $a2Block->start );
}

// Useful for sorting by range end position:
function annotationCompareEnd( $a1, $a2 )
{
	$a1Block = $a1->getBlockRange( );
	$a2Block = $a2->getBlockRange( );
	return $a1Block->end->compare( $a2Block->end );
}

?>
