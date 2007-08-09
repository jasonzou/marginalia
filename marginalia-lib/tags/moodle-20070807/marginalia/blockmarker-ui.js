/*
 * marginalia-blockmarkers.js
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

AN_MARKERS_CLASS = 'markers';		// markers column (usually on the left)
AN_MARKER_CLASS = 'marker';		// individual block marker
AN_USERCOUNT_CLASS = 'annotation-user-count';		// contains user count in block marker
AN_ANNOTATIONSFETCHED_CLASS = 'fetched';	// indicates a block's annotations have been fetched


/**
 * Show Per-Block User Counts
 */
Marginalia.prototype.showPerBlockUserCounts = function( url )
{
	this.annotationService.listBlocks( url, _showPerBlockUserCountsCallback );
}

function _showPerBlockUserCountsCallback( xmldoc )
{
	var rangeInfos = parseRangeInfoXml( xmldoc );
	var marginalia = window.marginalia;
	for ( var i = 0;  i < rangeInfos.length;  ++i )
	{
		var info = rangeInfos[ i ];
		
		// Only include markers for non-edit annotations by users other than the displayed one
		for ( var j = 0;  j < info.users.length;  ++j )
		{
			var user = info.users[ j ];
			if ( user.noteCount > 0 ) // && user.userid != marginalia.anuser )
			{
				var post = marginalia.listPosts( ).getPostByUrl( info.url );
				post.showPerBlockUserCount( marginalia, info );
				break;
			}
		}
	}
}

/**
 * Show a perBlockCount marker
 * Assumes that markers are being shown in order
 */
PostMicro.prototype.showPerBlockUserCount = function( marginalia, info )
{
	var node = info.resolveStart( this.contentElement );
	if ( node )
	{
		var resolver = new SequencePathResolver( node, info.sequenceRange.start.path );
		do
		{
			// TODO: Inefficient to create so many SequencePath objects
			var point = new SequencePoint( resolver.getPath() );
			if ( point.compare( info.sequenceRange.end ) > 0 )
				break;
			if ( ELEMENT_NODE == node.nodeType )
				this.showBlockMarker( marginalia, info, resolver.getNode(), point );
		}
		while ( resolver.next( ) );
	}
}

PostMicro.getBlockMarkerClickFcn = function( marginalia, markerElement, url, pointStr )
{
	return function() {
		marginalia.showBlockAnnotations( url, pointStr );
		domutil.addClass( markerElement, AN_ANNOTATIONSFETCHED_CLASS );
	};
}

PostMicro.prototype.showBlockMarker = function( marginalia, info, block, point )
{
	var markers = domutil.childByTagClass( this.element, null, AN_MARKERS_CLASS, marginalia.skipContent );
	if ( markers )
	{
		var countElement;
		var markerElement = block.markerElement;
		
		// Create the marker
		if ( ! markerElement )
		{
			block.blockMarkerUsers = [ ];

			countElement = domutil.element( 'span', {
				className: AN_USERCOUNT_CLASS,
				onclick: PostMicro.getBlockMarkerClickFcn( window.marginalia, markerElement, info.url, point.toString() )
			} );
			markerElement = domutil.element( 'div', {
				className: AN_MARKER_CLASS,
				blockElement: block,
				content:  countElement
			} );
			block.markerElement = markerElement;
			markers.appendChild( markerElement );
			
			this.positionBlockMarker( window.marginalia, markers, markerElement );
		}
		// The marker already exists - prepare to update it
		else
		{
			var countElement = domutil.childByTagClass( markerElement, 'span', null );
			while ( countElement.firstChild )
				countElement.removeChild( countElement.firstChild );
		}
		
		for ( var i = 0;  i < info.users.length;  ++i )
		{
			var user = info.users[ i ];
			// Don't include the currently-displayed user
			if ( user.noteCount > 0 ) // && user != marginalia.anusername )
				block.blockMarkerUsers[ block.blockMarkerUsers.length ] = user;
		}
		
		var userStr = '';
		for ( var i = 0;  i < block.blockMarkerUsers.length;  ++i )
		{
			var user = block.blockMarkerUsers[ i ];
			userStr += userStr ? ', ' + user.userid : user.userid;
		}
			
		countElement.setAttribute( 'title', userStr );
		countElement.appendChild( document.createTextNode( String( block.blockMarkerUsers.length ) ) );
	}
}


/**
 * Adjust alignment and height of a block marker
 */
PostMicro.prototype.positionBlockMarker = function( marginalia, markers, markerElement )
{
	var blockElement = markerElement.blockElement;
	var blockOffset = domutil.getElementYOffset( blockElement, this.element );
	var markersOffset = domutil.getElementYOffset( markers, this.element );
	var offset = blockOffset - markersOffset;
	markerElement.style.top = String( offset ) + 'px';

	var height;
	var nextBlock;
	// Walk forward to the next breaking element
	var walker = new DOMWalker( blockElement );
	while ( walker.walk( true, false ) )
	{
		if ( ELEMENT_NODE == walker.node.nodeType && ! walker.endTag && domutil.isBreakingElement( walker.node.tagName ) )
			break;
	}
	
	// Was one found?  If so, don't extend this far down.
	if ( walker.node && ELEMENT_NODE == walker.node.nodeType && domutil.isBreakingElement( walker.node.tagName ) )
	{
		var nextTop = domutil.getElementYOffset( walker.node, this.contentElement );
		var thisTop = domutil.getElementYOffset( blockElement, this.contentElement );
		height = nextTop - thisTop;
	}
	else
		height = blockElement.offsetHeight;

	markerElement.style.height = String( height ) + 'px';
}

/**
 * Adjust the alignment and height of all block markers
 */
PostMicro.prototype.repositionBlockMarkers = function( marginalia )
{
	var markers = domutil.childByTagClass( this.element, null, AN_MARKERS_CLASS, marginalia.skipContent );
	if ( markers )
	{
		var markerElements = domutil.childrenByTagClass( this.element, null, AN_MARKER_CLASS, null );
		for ( var i = 0;  i < markerElements.length;  ++i )
			this.positionBlockMarker( marginalia, markers, markerElements[ i ] );
	}
}
