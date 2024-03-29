/*
 * XPathRange.js
 *
 * Serialization formats for word ranges (see ranges.js).
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
 
/**
 * XPath representation of a range
 *
 * XPath ranges are fast (assuming the document.evaluate function is implemented in the
 * browser to resolve XPath expressions), but unlike block ranges cannot be ordered.
 */
function XPathRange( str )
{
	if ( str )
		this.fromString( str );
	return this;
}

XPathRange.prototype.fromString = function( path )
{
	var parts = path.split( ';' );
	if ( null == parts || 2 != parts.length )
		throw "XPathRange parse error";
	this.start = new XPathPoint( parts[ 0 ] );
	this.end = new XPathPoint( parts[ 1 ] );
}

XPathRange.prototype.toString = function( )
{
	return this.start.toString( ) + ';' + this.end.toString( );
}

XPathRange.prototype.equals = function( range2 )
{
	return this.start.equals( range2.start ) && this.end.equals( range2.end );
}

XPathRange.prototype.makeBlockLevel = function( )
{
	this.start.makeBlockLevel( )
	this.end.makeBlockLevel( )
}

function XPathPoint( str )
{
	if ( str )
		this.fromString( str );
	return this;
}

XPathPoint.prototype.fromString = function( path, words, chars )
{
	if ( words )
	{
		this.path = path;
		this.words = words;
		this.chars = chars;
	}
	else
	{
		var parts = path.match( /^\s*(.*)\/word\((\d+)\)\/char\((\d+)\)\s*$/ );
		if ( parts )
		{
			this.path = parts[ 1 ];
			this.words = Number( parts[ 2 ] );
			this.chars = Number( parts[ 3 ] );
		}
		else
		{
			this.path = path;
			this.words = this.chars = 0;
		}
	}
}

XPathPoint.prototype.equals = function( point2 )
{
	return this.path == point2.path && this.words == point2.words && this.chars == point2.chars;
}

XPathPoint.prototype.toString = function( )
{
	if ( this.words )
		return this.path + '/word(' + this.words + ')/char(' + this.chars + ')';
	else
		return this.path;
}

XPathPoint.prototype.makeBlockLevel = function( )
{
	this.words = null;
	this.chars = null;
}

/**
 * Notice the lack of an fskip function.  None of the parent nodes of the current node
 * can be skippable for this to work.
 */
XPathPoint.prototype.pathFromNode = function( root, rel, idTest )
{
	var node = rel;
	var path = '';
	var foundId = false;
	outer: while ( null != node && root != node )
	{
		if ( foundId )
		{
			// If we found an ID, short-circuit to produce a path like .//div[@id='id1']/p[5]
			path = './/' + path;
			break;
		}
		else
		{
			// Check whether we can use this node's ID as a start point
			var id = node.getAttribute( 'id', null );
			if ( id && ( ! idTest || idTest( id ) ) && -1 == id.indexOf( "'" ) )
			{
				path = "*[@id='" + id + "']" + ( '' == path ? '' : '/' + path );
				foundId = true;
			}
			else
			{
				var count = 1;
				for ( var prev = node.previousSibling; prev && ! foundId;  prev = prev.previousSibling )
				{
					if ( ELEMENT_NODE == prev.nodeType && prev.tagName == node.tagName )
					{
						id = prev.getAttribute( 'id', null );
						if ( id && ( ! idTest || idTest( id ) ) && -1 == id.indexOf( "'" ) )
							foundId = true;
						else
							count += 1;
					}
				}
				if ( '' != path )
					path = '/' + path;
				
				if ( foundId )
				{
					{
						path = "*[@id='" + id + "']"
							+ '/following-sibling::html:' + node.tagName.toLowerCase( )
							+ '[' + String( count ) + ']'
							+ path;
					}
				}
				else
				{
					path = 'html:' + node.tagName.toLowerCase( ) + '[' + String( count ) + ']' + path;
					node = node.parentNode;
				}
			}
		}
	}
	this.path = path;
}

/*
 * This doesn't do much checking on the incoming xpath.
 * TODO: Figure out how to handle tag case name inconsistencies between HTML and XHTML
 */
XPathPoint.prototype.getReferenceElement = function( root )
{
	var rel;	// will be the result	
	var xpath = this.path;
	var myroot = root;
	
	var startTime = new Date( );
	trace( 'xpath-range', 'XPathPoint.getReferenceElement for path ' + xpath );

	// Screen out document(), as it is a security risk
	// I would prefer to use a whitelist, but full processing of the xpath
	// expression is expensive and complex.  I'm doing some of this on the
	// server, so unless someone can hijack the returned xpath expressions
	// this should never happen anyway.
	if ( xpath.match( /[^a-zA-Z_]document\s*\(/ ) )
		return null;
	else if ( xpath == '' )
		return root;
	
	// If this document is not XHTML, need to strip the html: prefixes
	if ( ! domutil.isXhtml( document ) )
	{
		xpath = xpath.replace( /(\/)html:/g, '$1' );
		xpath = xpath.replace( /(\/[a-z-]+::)html:/g, '$1' );
		xpath = xpath.replace( /^html:/, '' );
	}
	
	// Use XPath support if available (as non-Javascript it should run faster)
	if ( root.ownerDocument.evaluate )
	{
		rel = root.ownerDocument.evaluate( xpath, myroot, domutil.nsPrefixResolver, XPathResult.ANY_TYPE, null );
		rel = rel.iterateNext( );
	}
	// Internet Explorer's xpath support:
	else if ( root.selectSingleNode )
		rel = root.selectSingleNode( xpath );

	trace( 'range-timing', 'XPathPoint.getReferenceElement timing: ' + ( (new Date()) - startTime ) );
		
	// Ensure that the found node is a child of the root
	// This is necessary to reject xpath attempts to get at secure information
	// elsewhere in the page.  It could happen by accident if an ID is used in the xpath,
	// and that ID is used or moved to outside the root.
	if ( ! domutil.isElementDescendant( rel, root ) )
		return null;
	
	return rel;
}


