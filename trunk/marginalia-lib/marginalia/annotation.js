/*
 * annotation.js
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
 
// namespaces
NS_PTR = 'http://www.geof.net/code/annotation/';
NS_ATOM = 'http://www.w3.org/2005/Atom';

// values for annotation.access
AN_PUBLIC_ACCESS = 'public';
AN_PRIVATE_ACCESS = 'private';

// values for annotation.editing (field is deleted when not editing)
AN_EDIT_NOTE_FREEFORM = 'note freeform';
AN_EDIT_NOTE_KEYWORDS = 'note keywords';
AN_EDIT_LINK = 'link';

// Range formats
BLOCK_RANGE = 'block';
XPATH_RANGE = 'xpath';
PREFERRED_RANGE = 'preferred';		// pick the best available range type
AUTHORITATIVE_RANGE = XPATH_RANGE;
ORDERED_RANGE = BLOCK_RANGE;


/* ************************ Annotation Class ************************ */
/*
 * This is a data-only class with (almost) no methods.  This is because all annotation
 * function either affect the display or hit the server, so more properly belong
 * to AnnotationService or PostMicro.
 * An annotation is based on a selection range relative to a contentElement.
 * The ID of a new range is 0, as it doesn't yet exist on the server.
 */
function Annotation( url )
{
	if ( url )
		this.setUrl( url );
	this.blockRange = null;
	this.xpathRange = null;
	this.id = 0;
	this.note = '';
	this.access = ANNOTATION_ACCESS_DEFAULT;
	this.action = '';
	this.quote = '';
	this.isLocal = false;
	// this.editing = null; -- deleted when not needed
	this.link = '';
	// Used to track which fields have changed:
	this.changes = new Object( );
	return this;
}

/**
 * Test whether any of the material fields (those serialized and stored in the database)
 * have changed
 */
Annotation.prototype.hasChanged = function( field )
{
	if ( field )
		return this.changes[ field ];
	else
	{
		for ( change in this.changes )
			return true;
		return false;
	}
}

Annotation.prototype.resetChanges = function( )
{
	this.changes = new Object( );
}

/* IMHO, getters and setters are usually not worth it for lightweight code as they
 * increase verbosity and harm readability.  In this case, I want to track which
 * fields have changed only the changed fields need be updated on the server - not
 * because that is more efficient (the benefit would be insignificant), but
 * because it makes debugging and tracking changes easier. */
Annotation.prototype.getUrl = function()
{ return this.url ? this.url : ''; }

Annotation.prototype.setUrl = function(url)
{
	if ( this.url != url )
	{
		this.url = url;
		this.changes[ 'url' ] = true;
	}
}

Annotation.prototype.getPreferredRangeType = function( )
{
	if ( this.xpathRange )
		return XPATH_RANGE;
	else if ( this.blockRange )
		return BLOCK_RANGE;
	else
		return null;
}

Annotation.prototype.getRange = function( format )
{
	if ( BLOCK_RANGE == format )
		return this.blockRange;
	else if ( XPATH_RANGE == format )
		return this.xpathRange;
	else
		throw "Annotation.setRange:  Unknown range format";
}

Annotation.prototype.setRange = function( format, range )
{
	if ( BLOCK_RANGE == format )
	{
		if ( this.blockRange == null && range != null || ! this.blockRange.equals( range ) )
		{
			this.blockRange = range;
			this.changes[ 'range/' + BLOCK_RANGE ] = true;
		}
	}
	else if ( 'xpath' == format )
	{
		if ( this.xpathRange == null && range != null || ! this.xpathRange.equals( range ) )
		{
			this.xpathRange = range;
			this.changes[ 'range/' + XPATH_RANGE ] = true;
		}
	}
	else
		throw "Annotation.setRange:  Unknown range format";
}

Annotation.prototype.getId = function( )
{ return this.id; }

Annotation.prototype.setId = function( id )
{
	if ( this.id != id )
	{
		this.id = id;
		this.changes[ 'id' ] = true;
	}
}

Annotation.prototype.getUserId = function( )
{ return this.userid ? this.userid : ''; }

Annotation.prototype.setUserId = function( userid )
{
	if ( this.userid != userid )
	{
		this.userid = userid;
		this.changes[ 'userid' ] = true;
	}
}

Annotation.prototype.getNote = function( )
{ return this.note ? this.note : ''; }

Annotation.prototype.setNote = function( note )
{
	if ( this.note != note )
	{
		this.note = note;
		this.changes[ 'note' ] = true;
	}
}

Annotation.prototype.getQuote = function( )
{ return this.quote ? this.quote : ''; }

Annotation.prototype.setQuote = function( quote )
{
	if ( this.quote != quote )
	{
		this.quote = quote;
		this.changes[ 'quote' ] = true;
	}
}

Annotation.prototype.getAccess = function( )
{ return this.access ? this.access : ''; }

Annotation.prototype.setAccess = function( access )
{
	if ( this.access != access )
	{
		this.access = access;
		this.changes[ 'access' ] = true;
	}
}

Annotation.prototype.getAction = function( )
{ return this.action ? this.action : ''; }

Annotation.prototype.setAction = function( action )
{
	if ( this.action != action )
	{
		this.action = action;
		this.changes[ 'action' ] = true;
	}
}
 
Annotation.prototype.getLink = function( )
{ return this.link ? this.link : ''; }

Annotation.prototype.setLink = function( link )
{
	if ( this.link != link )
	{
		this.link = link;
		this.changes[ 'link' ] = true;
	}
}

Annotation.prototype.getQuoteAuthor = function( )
{ return this.quote_author ? this.quote_author : ''; }

Annotation.prototype.setQuoteAuthor = function( author )
{
	if ( this.quote_author != author )
	{
		this.quote_author = author;
		this.changes[ 'quote_author' ] = true;
	}
}

Annotation.prototype.getQuoteTitle = function( )
{ return this.quote_title ? this.quote_title : ''; }

Annotation.prototype.setQuoteTitle = function( title )
{
	if ( this.quote_title != title )
	{
		this.quote_title = title;
		this.changes[ 'quote_title' ] = true;
	}
}


Annotation.prototype.fieldsFromPost = function( post )
{
	this.setQuoteAuthor( post.author );
	this.setQuoteTitle( post.title );
}	


function compareAnnotationRanges( a1, a2 )
{
	// Note: don't use getters for efficiency.
	return a1.blockRange.compare( a2.blockRange );
}

function annotationFromTextRange( post, textRange )
{
	var range = textRangeToWordRange( textRange, post.contentElement, _skipContent );
	if ( null == range )
		return null;  // The range is probably invalid (e.g. whitespace only)
	var annotation = new Annotation( post.url );
	annotation.setRange( BLOCK_RANGE, textRange.toBlockRange( ) );
	annotation.setRange( XPATH_RANGE, textRange.toXPathRange( ) );
	// Can't just call toString() to grab the quote from the text range, because that would
	// include any smart copy text.
	annotation.setQuote( getTextRangeContent( textRange, _skipContent ) );
	//annotation.quote = textRange.toString( );
	return annotation;
}

/**
 * Destructor to prevent IE memory leaks
 */
Annotation.prototype.destruct = function( )
{
	this.blockRange = null;
	this.xpathRange = null;
}

/**
 * Handy representation for debugging
 */
Annotation.prototype.toString = function( )
{
	// Don't use getters for efficiency
	if ( this.xpathRange )
		return this.xpathRange.toString( );
	else
		return this.blockRange.toString( );
}

/**
 * Figure out whether note editing should be in keywords or freeform mode
 * If the note text is a keyword, default to keywords.  Otherwise, check
 * preferences.
 */
Annotation.prototype.defaultNoteEditMode = function( preferences )
{
	if ( ! ANNOTATION_KEYWORDS )
		return AN_EDIT_NOTE_FREEFORM;
	else if ( '' == this.note )
	{
		var pref = preferences.getPreference( PREF_NOTEEDIT_MODE );
		return pref ? pref : AN_EDIT_NOTE_KEYWORDS;
	}
	else
		return window.keywordService.isKeyword( this.note )
			? AN_EDIT_NOTE_KEYWORDS : AN_EDIT_NOTE_FREEFORM;
}


Annotation.prototype.fromAtom = function( entry, annotationUrlBase )
{
	var hOffset, hLength, text, url, id;
	var rangeStr = null;
	for ( var field = entry.firstChild;  field != null;  field = field.nextSibling )
	{
		if ( field.namespaceURI == NS_ATOM && getLocalName( field ) == 'link' )
		{
			var rel = field.getAttribute( 'rel' );
			var href = field.getAttribute( 'href' );
			// What is the role of this link element?  (there are several links in each entry)
			if ( 'self' == rel )
				this.id = href.substring( href.lastIndexOf( '/' ) + 1 );
			else if ( 'related' == rel )
				this.link = href;
			else if ( 'alternate' == rel )
			{
				if ( null != annotationUrlBase
					&& href.substring( 0, annotationUrlBase.length ) == annotationUrlBase )
				{
					href = href.substring( annotationUrlBase.length );
				}
				this.url = href;
				// Used to link to post here.  In future, this should instead be done outside
				// the annotation by looking up the url - as here.
				// this.post = findPostByUrl( href );
			}
		}
		else if ( NS_ATOM == field.namespaceURI && 'author' == getLocalName( field ) )
		{
			for ( var nameElement = field.firstChild;  null != nameElement;  nameElement = nameElement.nextSibling )
			{
				if ( NS_ATOM == nameElement.namespaceURI && 'name' == getLocalName( nameElement ) )
					this.userid = nameElement.firstChild ? nameElement.firstChild.nodeValue : null;
			}
		}
		else if ( field.namespaceURI == NS_ATOM && getLocalName( field ) == 'title' )
			this.note = null == field.firstChild ? '' : getNodeText( field );
		else if ( field.namespaceURI == NS_ATOM && getLocalName( field ) == 'summary' )
			this.quote = null == field.firstChild ? null : getNodeText( field );
		else if ( field.namespaceURI == NS_PTR && getLocalName( field ) == 'range' )
		{
			var format = field.getAttribute( 'format', '' );
			// These ranges may throw parse errors
			if ( 'block' == format )
				this.setRange( format, new BlockRange( getNodeText( field ) ) );
			else if ( 'xpath' == format )
				this.setRange( format, new XPathRange( getNodeText( field ) ) );
			// ignore unknown formats
		}
		else if ( field.namespaceURI == NS_PTR && getLocalName( field ) == 'access' )
			this.access = null == field.firstChild ? 'private' : getNodeText( field );
		else if ( field.namespaceURI == NS_PTR && getLocalName( field ) == 'action' )
			this.action = null == field.firstChild ? '' : getNodeText( field );
		else if ( field.namespaceURI == NS_ATOM && getLocalName( field ) == 'updated' )
			this.updated = getNodeText( field );
	}
	// This is here because annotations are only parsed from XML when being initialized.
	// In future who knows, this might not be the case - and the reset would have to
	// be moved elsewhere.
	this.resetChanges( );	
}

/**
 * Parse Atom containing annotation info and return an array of annotation objects
 */
function parseAnnotationXml( xmlDoc )
{
	var annotations = new Array( );
	
	if ( xmlDoc.documentElement.tagName == "error" )
	{
		logError( "parseAnnotationXML Error: " + xmlDoc.documentElement.textValue() );
		alert( getLocalized( 'corrupt XML from service' ) );
		return null;
	}
	else
	{
		for ( var i = 0;  i < xmlDoc.documentElement.childNodes.length;  ++i ) {
			child = xmlDoc.documentElement.childNodes[ i ];
			// obliged to use tagName here rather than localName due to IE
			if ( child.namespaceURI == NS_ATOM && getLocalName( child ) == 'entry' )
			{
				// An exception may be thrown if there's a format error, in which case we
				// don't want to parse or list the annotation - if we did, it might cause
				// the whole application to fail, making it impossible to view other annotations
				// or to fix the problem (e.g. through Marginalia Direct).
				try
				{
					var annotation = new Annotation( );
					annotation.fromAtom( child, window.annotationUrlBase );
					annotations[ annotations.length ] = annotation;
				}
				catch ( exception )
				{
					logError( "Annotation parse error:  " + exception );
				}
			}
		}
		annotations.sort( compareAnnotationRanges );
		return annotations;
	}
}
