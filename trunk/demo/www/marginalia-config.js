/*
 * Annotation configuration settings
 * These are sample settings.  They may need to change for debugging,
 * or when integrating with different web applications.
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

ANNOTATION_LINKING = true;		// If true, include the linking feature
ANNOTATION_KEYWORDS = true;	// If true, include the keywords feature
ANNOTATION_ACCESS = true;		// If true, include the public/private feature
ANNOTATION_EXTERNAL_LINKING = true;	// If true, link editor accepts any http/https URL

ANNOTATION_ACCESS_DEFAULT = 'private';	// default access

// If this is true, uses paths like annotate/nnn
// if false, use paths like annotation/annotate.php?id=nnn
ANNOTATION_NICE_URLS = false;

NICE_ANNOTATION_SERVICE_URL = '/annotate';
UGLY_ANNOTATION_SERVICE_URL = '/annotate.php';

/* Logging Settings */
TRACING_ON = true;		// switch on to output trace() calls
LOGGING_ON = true;		// switch on to output logError() calls
INWINDOW_LOG = false;	// switch on to output to HTML document instead of/in addition to console

// Set these to true to view certain kinds of events
// Most of these are only useful for debugging specific areas of code.
// annotation-service, however, is particularly useful for most debugging
setTrace( 'annotation-service', true );	// XMLHttp calls to the annotation service
setTrace( 'word-range', false );			// Word Range calculations (e.g. converting from Text Range)
setTrace( 'find-quote', false );			// Check if quote matches current state of document
setTrace( 'node-walk', false );			// Used for going through nodes in document order
setTrace( 'show-highlight', false );		// Text highlighting calculations
setTrace( 'align-notes', false );			// Aligning margin notes with highlighting
setTrace( 'range-compare', false );		// Compare range positions
setTrace( 'range-string', false );			// Show conversions of word ranges to/from string
setTrace( 'list-annotations-xml', false );// Show the full Atom XML coming back from listAnnotations
setTrace( 'WordPointWalker', false );		// Show return values from WordPointWalker
setTrace( 'prefs', false );				// List fetched preferences
setTrace( 'keywords', false );				// List fetched keywords
setTrace( 'point-compare', false );			// Compare two WordPoints
