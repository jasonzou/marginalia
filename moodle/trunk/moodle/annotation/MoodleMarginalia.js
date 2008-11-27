/*
 * MoodleMarginalia.js
 * Annotation functions specific to moodle.php (the generic name is in case
 * non-annotation code ends up in here).
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

function MoodleMarginalia( url, moodleRoot, username, prefs, params )
{
	this.url = url;
	this.moodleRoot = moodleRoot;
	this.username = username;
	this.preferences = new Preferences( 
		new RestPreferenceService( this.moodleRoot + '/annotation/user-preference.php' ),
		prefs );
	this.anuser = prefs[ AN_USER_PREF ];
	this.showAnnotations = prefs[ AN_SHOWANNOTATIONS_PREF ];
	this.showAnnotations = this.showAnnotations == 'true';
	this.enableSmartcopy = prefs[ SMARTCOPY_PREF ] == 'true';
	this.splash = prefs[ AN_SPLASH_PREF ] == 'true' ? params[ 'splash' ] : null;
}

MoodleMarginalia.prototype.onload = function( )
{
//	initLogging();

	// Check whether this page should have annotations enabled at all
	// The check is here rather in the PHP;  that minimizes the number of patches
	// that need to be applied to existing Moodle code.
	var actualUrl = '' + window.location;
	if ( this.username && actualUrl.match( /^.*\/mod\/forum\/discuss\.php\?d=(\d+)/ ) )
	{
		var annotationService = new RestAnnotationService( this.moodleRoot + '/annotation/annotate.php', {
			csrfCookie: 'MoodleSessionTest' } );
		var keywordService = new RestKeywordService( this.moodleRoot + '/annotation/keywords.php');
		keywordService.init( null, true );
		window.marginalia = new Marginalia( annotationService, this.username, this.anuser == '*' ? '' : this.anuser, {
			preferences: this.preferences,
			keywordService: keywordService,
			baseUrl:  this.moodleRoot,
			showAccess:  true,
			showBlockMarkers:  false,
			showActions:  false,
			onkeyCreate:  true,
			editors: {
				link: null,
				'default':  Marginalia.newEditorFunc( FreeformNoteEditor )
			},
		} );
		
		this.cleanUpPostContent( );
		
		var url = this.url;
		if ( this.showAnnotations )
		{
			window.marginalia.showAnnotations( url );
			this.fixControlMarginIE();
		}
		
		// Enable smartquote buttons
		var posts = marginalia.listPosts( ).getAllPosts( );
		for ( var i = 0;  i < posts.length;  ++i )
		{
			var button = domutil.childByTagClass( posts[ i ].getElement( ), 'button', 'smartquote', marginalia._skipContent );
			if ( button )
			{
				var content = posts[ i ].getContentElement( );
				button.onclick = this.getOnSmartquoteHandler( marginalia, content );
			}
		}
		
//		var marginaliaDirect = new MarginaliaDirect( annotationService );
//		marginaliaDirect.init( );
		
		if ( this.showAnnotations && this.splash )
			this.showSplash( );
	}
};


/**
 * Return a function for handling a smartquote button click
 */
MoodleMarginalia.prototype.getOnSmartquoteHandler = function( marginalia, content )
{
	var moodleMarginalia = this;
	return function( ) { moodleMarginalia.onSmartquote( marginalia, content ); };
}

MoodleMarginalia.prototype.onSmartquote = function( marginalia, content )
{
	// Test for selection support (W3C or IE)
	if ( ( ! window.getSelection || null == window.getSelection().rangeCount )
		&& null == document.selection )
	{
		if ( warn )
			alert( getLocalized( 'browser support of W3C range required for smartquote' ) );
		return false;
	}
		
	var textRange0 = getPortableSelectionRange();
	if ( null == textRange0 )
	{
		if ( warn )
			alert( getLocalized( 'select text to quote' ) );
		return false;
	}
	
	// Strip off leading and trailing whitespace and preprocess so that
	// conversion to WordRange will go smoothly.
	var textRange = TextRange.fromW3C( textRange0 );
	textRange = textRange.shrinkwrap( marginalia.skipContent );
	if ( ! textRange )
	{
		// this happens if the shrinkwrapped range has no non-whitespace text in it
		if ( warn )
			alert( getLocalized( 'select text to quote' ) );
		return false;
	}
	
	var quote = getTextRangeContent( textRange, marginalia.skipContent );
	quote = quote.replace( /(\s|\u00a0)+/g, ' ' );
	
	var postInfo = PostPageInfo.getPostPageInfo( document );
	var post = postInfo.getPostMicro( textRange.startContainer );
	var leadIn = '';
	if ( post )
	{
		leadIn = '<p>' + ( post.getAuthor( ) ? domutil.htmlEncode( post.getAuthor( ) ) : 'Someone' )
			+ ( post.getUrl( ) ? ' <a href="' + domutil.htmlEncode( post.getUrl( ) ) + '">wrote</a>' : 'wrote' )
			+ ",</p>";
	}
	var pub = leadIn + '<blockquote><p>' + domutil.htmlEncode( quote ) + '</p></blockquote>';
	
	var bus = new CookieBus( 'smartquote' );
	bus.publish( pub );
}


MoodleMarginalia.prototype.createAnnotation = function( event, postId )
{
	this.hideSplash( );
	delete this.splash;
	window.marginalia.preferences.setPreference( AN_SPLASH_PREF, 'false', null);
	clickCreateAnnotation( event, postId );
};

MoodleMarginalia.prototype.showSplash = function( )
{
	var noteMargins = cssQuery( '.hentry .notes div' );
	if ( noteMargins.length > 0 )
	{
		var margin = noteMargins[ 0 ];
		margin.appendChild( domutil.element( 'p', {
			className: 'splash',
			content: this.splash } ) );
	}
};

MoodleMarginalia.prototype.hideSplash = function( )
{
	var splash = cssQuery( '.hentry .notes div .splash' );
	if ( splash.length > 0 )
		splash[ 0 ].parentNode.removeChild( splash[ 0 ] );
};


/*
 * This fixes the height of create annotation buttons.
 *
 * I used to have to do this for IE, and complained bitterly.  Now I have to do it for Firefox
 * and Safari too:  apparently you can't set a table cell child to be the full height of the cell.
 * Probably this is CSS compliant.  Why can't they make sane standards?
 *
 * Note:  if the annotated content changes length (e.g. because of many inserted links or edit
 * actions), the button won't resize to match.  Hmmm.
 */
MoodleMarginalia.prototype.fixControlMarginIE = function( )
{
	var controlMargins = domutil.childrenByTagClass( document.documentElement, 'td', 'control-margin', null, PostMicro.skipPostContent );
	for ( var i = 0;  i < controlMargins.length;  ++i )
	{
		var button = domutil.childByTagClass( controlMargins[ i ], 'button', null );
		button.style.height = '' + controlMargins[ i ].offsetHeight + 'px';
	}
};

MoodleMarginalia.prototype.cleanUpPostContent = function( )
{
	var f = function( node ) {
		for ( var child = node.firstChild;  child;  child = child.nextSibling )
		{
			if ( child.nodeType == ELEMENT_NODE )
			{
				domutil.removeClass( child, PM_POST_CLASS );
				domutil.removeClass( child, PM_CONTENT_CLASS );
				domutil.removeClass( child, AN_NOTES_CLASS );
				child.removeAttribute( 'id' );
				if ( child.id )
					delete child.id;
				f( child );
			}
		}
	};
	var posts = marginalia.listPosts( ).getAllPosts( );
	for ( var i = 0;  i < posts.length;  ++i )
	{
		f ( posts[ i ].getContentElement( ) );
	}
}

MoodleMarginalia.prototype.changeAnnotationUser = function( userControl, url )
{
	var marginalia = window.marginalia;
	var user = userControl.value;
	this.hideSplash( )
	marginalia.hideAnnotations( );
	if ( null == user || '' == user )
		marginalia.preferences.setPreference( AN_SHOWANNOTATIONS_PREF, 'false', null);
	else
	{
		marginalia.anusername = user == '*' ? '' : user;
		marginalia.showAnnotations( url );
		marginalia.preferences.setPreference( AN_SHOWANNOTATIONS_PREF, 'true', null);
		marginalia.preferences.setPreference( AN_USER_PREF, user, null );
		if ( this.splash && ( marginalia.username == marginalia.anusername || '' == marginalia.anusername ) )
			this.showSplash( );
		this.fixControlMarginIE();
	}
};

