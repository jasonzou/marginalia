/*
 * annotation.js
 *
 * Web Annotation is being developed for Moodle with funding from BC Campus 
 * and support from Simon Fraser University and SFU's Applied Communication
 * Technologies Group and the e-Learning Innovation Centre of the
 * Learning Instructional Development Centre at SFU
 * Copyright (C) 2005 Geoffrey Glass
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
 */

/*
 * Must be called before any other annotation functions
 */
AN_SUN_SYMBOL = '\u25cb'; //'\u263c';
AN_MOON_SYMBOL = '\u25c6'; //'\u2641';	

function AnnotationSummary( annotationService, username, urlsExcludeHost )
{
	this.annotationService = annotationService;
	this.username = username;
	this.urlsExcludeHost = urlsExcludeHost;
}

AnnotationSummary.prototype.deleteAnnotation = function( id )
{
	var f = function( xmldoc ) {
		window.location.reload( );
	};
	this.annotationService.deleteAnnotation( id, f );
}

AnnotationSummary.prototype.shareAnnotation = function( button, id )
{
	var annotation = new Object( );
	annotation.id = id;
	annotation.access = button.value;
	this.annotationService.updateAnnotation( annotation, null );
}

AnnotationSummary.prototype.shareAnnotationPublicPrivate = function( button, id )
{
	var annotation = new Object( );
	annotation.id = id;
	var oldAccess = domutil.hasClass( button, 'access-public' ) ? 'public' : 'private';
	annotation.access = ( 'public' == oldAccess ? 'private' : 'public' );
	this.annotationService.updateAnnotation( annotation, null );
	domutil.removeClass( button, 'access-' + oldAccess );
	while ( button.firstChild )
		button.removeChild( button.firstChild );
	button.appendChild( document.createTextNode( 'public' == annotation.access ? AN_SUN_SYMBOL : AN_MOON_SYMBOL ) );
	domutil.addClass( button, 'access-' + annotation.access );
}

AnnotationSummary.prototype.onSearchAnnotationsChange = function( )
{
	var searchElement  = document.getElementById( 'search-annotations' );
	var accessElement = document.getElementById( 'access' );
	var userElement = document.getElementById( 'user' );
	if ( 'my annotations' == searchElement.value )
	{
		userElement.value = this.username;
		accessElement.value = '';
	}
	else
	{
		userElement.value = '';
		accessElement.value = 'public';
	}
}

function setAnnotationUser( user )
{
	window.preferenceService.setPreference( 'show_annotations', 'true', null);
	window.preferenceService.setPreference( 'annotation_user', user, null );
}

