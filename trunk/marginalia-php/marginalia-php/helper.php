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
		// must be setAnnotationId, not setId, because of a conflict with a base class method in OJS
		if ( array_key_exists( 'id', $params ) )
		{
			$id = $params[ 'id' ];
			$annotation->setAnnotationId( $id );
		}
		
		// UserId
		if ( array_key_exists( 'userid', $params ) )
		{
			$userid = $params[ 'userid' ];
			$this->setUserId( $userid );
		}

		// Sequence Range
		if ( array_key_exists( 'sequence-range', $params ) )
		{
			$sequenceRange = new SequenceRange( );
			$sequenceRange->fromString( $params[ 'sequence-range' ] );
			$annotation->setSequenceRange( $sequenceRange );
		}
		
		// XPath Range
		if ( array_key_exists( 'xpath-range', $params ) )
		{
			$xpathRange = new XPathRange( );
			$xpathRange->fromString( $params[ 'xpath-range' ] );
			if ( ! XPathPoint::isXPathSafe( $xpathRange->start->getPathStr() ) || ! XPathPoint::isXPathSafe( $xpathRange->end->getPathStr( ) ) )
				return XPATH_SECURITY_ERROR;
			$annotation->setXPathRange( $xpathRange );
		}
		
		// URL
		if ( array_key_exists( 'url', $params ) )
		{
			$url = $params[ 'url' ];
			if ( ! $url || ! MarginaliaHelper::isUrlSafe( $url ) )
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
			$annotation->setQuoteAuthor( $quoteAuthor );
		}
		
		// Access
		if ( array_key_exists( 'access', $params ) )
		{
			$access = $params[ 'access' ];
			if ( ! Annotation::isAccessValid( $access ) )
				return ACCESS_VALUE_ERROR;
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
			if ( ! $link || ! MarginaliaHelper::isUrlSafe( $link ) )
				return URL_SCHEME_ERROR + '( ' + $link + ')';
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
		
		$sequenceRange = $annotation->getSequenceRange( );
		$sSequenceRange = htmlspecialchars( $sequenceRange->toString() );
		$xpathRange = $annotation->getXPathRange( );
		
		$s = " <entry>\n";
		// Emit range in two formats:  sequence for sorting, xpath for authority and speed
		$s .= "  <ptr:range format='sequence'>$sSequenceRange</ptr:range>\n";
		// Make 100% certain that the XPath expression contains no unsafe calls (e.g. to document())
		if ( $xpathRange && XPathPoint::isXPathSafe( $xpathRange->start->getPathStr() ) && XPathPoint::isXPathSafe( $xpathRange->end->getPathStr( ) ) )
			$s .= "  <ptr:range format='xpath'>".htmlspecialchars($xpathRange->toString())."</ptr:range>\n";
		$s .= "  <ptr:access>$sAccess</ptr:access>\n"
			. "  <ptr:action>$sAction</ptr:action>\n"
			. "  <title>$title</title>\n";
		// Use double quotes for some attributes because it's easier than passing ENT_QUOTES to
		// each call to htmlspecialchars
		$s .= "  <link rel='self' type='application/xml' href=\"" . htmlspecialchars( $servicePath.'/'.$annotation->getAnnotationId() ) . "\"/>\n"
			. "  <link rel='alternate' type='text/html' title=\"$sQuoteTitle\" href=\"$sUrl\"/>\n";
		if ( $annotation->getLink() )
			$s .= "  <link rel='related' type='text/html' title=\"$sNote\" href=\"$sLink\"/>\n";
		// TODO: Is this international-safe?  I could use htmlsecialchars on it, but that might not match the
		// restrictions on IRIs.  #GEOF#
		$s .= "  <id>tag:$tagHost," . date( 'Y-m-d', $annotation->getCreated() ) . ':annotation/'.$annotation->getAnnotationId()."</id>\n"
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
				$sNote = "<a href=\"$sLink\">$sNote</a>";
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
	 * Reduce the number of blocks as much as possible.
	 * Subsequent blocks with the same stand and end will be collapsed to a single block.
	 * This is very effective for annotations that don't cross block boundaries, and should significantly
	 * speed up the client display.  However, the client may still have to deal with overlapping blocks.
	 */
	function mergeBlocks( &$blocks )
	{
		// Make sure the blocks are sorted
		usort( $blocks, 'blockCompare' );
		
		$i = 0;
		while ( $i < count( $blocks ) - 1 )
		{
			$block =& $blocks[ $i ];
			$nextBlock =& $blocks[ $i + 1 ];
			
			// If ranges are the same, collapse the blocks
			if ( $block->sequenceRange->equals( $nextBlock->sequenceRange ) )
			{
				// Patch up xpaths if possible
				if ( ! $block->xpathRange->start && $nextBlock->xpathRange->start )
					$block->xpathRange->start = $nextBlock->xpathRange->start;
				if ( ! $block->xpathRange->end && $nextBlock->xpathRange->end )
					$block->xpathRange->end = $nextBlock->xpathRange->end;
					
				foreach ( $nextBlock->annotations as $annotation )
					$block->addAnnotation( $annotation );
				array_splice( $blocks, $i + 1, 1 );
			}
			else
				$i += 1;
		}
		return $blocks;
	}
	
	/**
	 * Produces a result looking like this:
	 *   geof fred john p[5]
	 * Indicating that geof, fred, and john have all annotated that particular block-level element.
	 * TODO: include block path, thusly:
	 *   geof fred john /5 p[5]
	 */
	function generateBlockInfo( &$blocks )
	{
		$s = "<block-info>\n";
		for ( $i = 0;  $i < count( $blocks );  ++$i )
		{
			$info = $blocks[ $i ];
			$s .= $info->toXml( );
		}
		return $s . '</block-info>';
	}
	

	/** Convert a list of annotations to a list of BlockInfo records
	 * These will have a 1-to-1 correspondence, they should then be merged
	 * using calculateBlockOverlaps */
	function annotationsToBlocks( &$annotations )
	{
		$blocks = array();
		foreach ( $annotations as $annotation )
		{
			$blocks[ count( $blocks ) ] = new BlockInfo(
				$annotation->getUrl( ), $annotation->getXPathRange( ), $annotation->getSequenceRange( ) );
			$blocks[ count( $blocks ) - 1 ]->annotations[ ] = $annotation;
		}
		return $blocks;
	}

	
	/** Calculate overlaps between BlockInfo records. */
	function calculateBlockOverlaps( &$blocks )
	{
		// Create two arrays:  one of range starts, the other of range ends
		$starts = $blocks;
		$ends = $blocks;
		usort( $starts, 'blockCompareStart' );
		usort( $ends, 'blockCompareEnd' );
		
		// Create an array to store overlap ranges
		$info = null;
		$overlaps = array( );
		$annotations = array( );
		
		$start_i = 0;
		$end_i = 0;
		$depth = 0;
		while ( $end_i < count( $ends ) )
		{
			$end =& $ends[ $end_i ];
			$endSequence =& $end->sequenceRange;
			$endXPath =& $end->xpathRange;

			if ( $start_i < count( $starts ) )
			{
				$start =& $starts[ $start_i ];
				$startSequence = $start->sequenceRange;
				$startXPath = $start->xpathRange;
				$comp = $startSequence->start->compare( $endSequence->end );
			}
			else
				$comp = 1;	// Only ends remain
			
			if ( $comp < 0 )
			{
				$sequencePoint =& $startSequence->start;
				if ( $startXPath)
					$xpathPoint =& $startXPath->start;
				else
					$xpathPoint = null;
				++$depth;
				++$start_i;
			
				// Add all the start block's annotations to the list
				foreach ( $start->annotations as $annotation )
				{
					$id = $annotation->getAnnotationId( );
					$annotations[ $id ] = $annotation;
					$annotationCounts[ $id ] = $annotationCounts[ $id ] ? $annotationCounts[ $id ] + 1 : 1;
				}
			}
			else // $comp >= 0
			{
				$sequencePoint = $endSequence->end;
				if ( $endXPath )
					$xpathPoint = $endXPath->end;
				else
					$xpathPoint = null;
				--$depth;
				++$end_i;
			}
				
			// Close any existing overlap
			if ( $info )
			{
				$info->sequenceRange->end = $sequencePoint;
				if ( $info->xpathRange && $xpathPoint )
					$info->xpathRange->end = $xpathPoint;
				else
					$info->xpathRange = null;
				$info->annotations = array_values( $annotations );
				$annotations = array( );
				$overlaps[ ] = $info;
				echo "Add Info<br/>";
				$info = null;
			}
			
			// Remove all the end block's annotations from the list (if applicable)
			if ( $comp >= 0 )
			{
				foreach ( $end->annotations as $annotation )
				{
					$id = $annotation->getAnnotationId( );
					if ( 1 == $annotationCounts[ $id ] )
					{
						unset( $annotations[ $id ] );
						unset( $annotationCounts[ $id ] );
					}
					else
						$annotationCounts[ $id ] -= 1;
				}
			}
			
			// Begin any new overlap
			if ( $depth > 0 )
			{
				$info = new BlockInfo( $end->url,
					new SequenceRange( $sequencePoint, null ),
					$xpathPoint ? new XPathRange( $xpathPoint, null ) : null );
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
		if ( False === $urlParts )
			return false;
		$scheme = $urlParts[ 'scheme' ];
		if ( 'http' == $scheme || 'https' == $scheme || '' == $scheme )
			return true;
		else
			return false;
	}
}

class BlockInfo
{
	function BlockInfo( $url, $xpathRange, $sequenceRange )
	{
		$this->url = $url;
		$this->sequenceRange = $sequenceRange;
		$this->xpathRange = $xpathRange;
		$this->annotations = array();
	}
	
	function addAnnotation( &$annotation )
	{
		$this->annotations[ $annotation->getAnnotationId( ) ] = $annotation;
	}
	
	function getAnnotations( )
	{
		return array_values( $this->annotations );
	}
	
	function makeBlockLevel( )
	{
		if ( $this->xpathRange )
			$this->xpathRange->makeBlockLevel( );
		if ( $this->sequenceRange )
			$this->sequenceRange->makeBlockLevel( );
	}
	
	function toXml( )
	{
		$s .= "\t<block url=\"".htmlspecialchars($this->url)."\">\n";
		
		if ( $this->xpathRange )
			$s .= "\t\t<range format=\"xpath\">".htmlspecialchars( $this->xpathRange->toString( ) )."</range>\n";

		if ( $this->sequenceRange )
			$s .= "\t\t<range format=\"sequence\">".htmlspecialchars( $this->sequenceRange->toString( ) )."</range>\n";
		
		$users = array();
		$annotations = array_values( $this->annotations );
		foreach ( $annotations as $annotation )
			$users[ $annotation->getUserId( ) ] = true;
		foreach ( array_keys( $users ) as $user )
			$s .= "\t\t<user>".htmlspecialchars( $user )."</user>\n";
		$s .= "\t</block>\n";
		return $s;
	}
}
	
class BlockPointIterator
{
	function BlockPointIterator( &$blocks )
	{
		$this->blocks =& $blocks;
		
		// Create two arrays:  one of range starts, the other of range ends
		$this->starts = $blocks;
		$this->ends = $blocks;
		usort( $this->starts, 'blockCompareStart' );
		usort( $this->ends, 'blockCompareEnd' );
		
		$this->start_i = 0;
		$this->end_i = 0;
		$this->comp = 0;
		
		// Current reference
		$this->sequencePoint = null;
		$this->xpathPoint = null;
		$this->block = null;
	}
	
	function hasMore( )
	{
		return $this->end_i < count( $this->ends );
	}
	
	function isStartPoint( )
	{
		return $this->comp < 0;
	}
	
	/**
	 * Treat an end/start pair as an end point, then iterate past and look at the start
	 * on next()
	 */
	function isEndPoint( )
	{
		return $this->comp >= 0;
	}
	
	function getSequencePoint( )
	{
		return $this->sequencePoint;
	}
	
	function getXPathPoint( )
	{
		return $this->xpathPoint;
	}
	
	function getBlock( )
	{
		return $this->block;
	}
	
	function next( )
	{
		if ( $this->end_i < count( $this->ends ) )
		{
			$end =& $this->ends[ $this->end_i ];
			$endSequence =& $end->sequenceRange;
			$endXPath =& $end->xpathRange;
			
			if ( $this->start_i < count( $this->starts ) )
			{
				$start =& $this->starts[ $this->start_i ];
				$startSequence =& $start->sequenceRange;
				$startXPath =& $start->xpathRange;
				//echo "start: ".$startSequence . "<br/>";
				$this->comp = $startSequence->start->compare( $endSequence->end );
			}
			else
				$this->comp = 1;	// Only ends remain
				
			//echo "comp: ".$this->comp."<br/>";
			if ( $this->comp >= 0 )
			{
				$this->block =& $end;
				
				if ( $endSequence )
					$this->sequencePoint =& $endSequence->end;
				else
					$this->sequencePoint = null;
					
				if ( $endXPath )
					$this->xpathPoint =& $endXPath->end;
				else
					$this->xpathPoint = null;
				
				++$this->end_i;
			}
			elseif ( $this->comp < 0 )
			{
				$this->block =& $start;
				
				if ( $startSequence )
					$this->sequencePoint =& $startSequence->start;
				else
					$this->sequencePoint = null;
				
				if ( $startXPath )
					$this->xpathPoint =& $startXPath->start;
				else
					$this->xpathPoint = null;
				
				++$this->start_i;
			}
			return True;
		}
		else
			return False;
	}
}	

// Compare two blocks (sort first on start, then on end)
function blockCompare( $b1, $b2 )
{
	return $b1->sequenceRange->compare( $b2->sequenceRange );
}

// Useful for sorting by range start position:
function blockCompareStart( $a1, $a2 )
{
	return $a1->sequenceRange->start->compare( $a2->sequenceRange->start );
}

// Useful for sorting by range end position:
function blockCompareEnd( $a1, $a2 )
{
	return $a1->sequenceRange->end->compare( $a2->sequenceRange->end );
}

?>
