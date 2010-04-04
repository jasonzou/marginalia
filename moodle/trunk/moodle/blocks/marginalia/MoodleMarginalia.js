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

function MoodleMarginalia( annotationPath, url, moodleRoot, userId, prefs, params )
{
	this.annotationPath = annotationPath;
	this.url = url;
	this.moodleRoot = moodleRoot;
	this.loginUserId = userId;
	this.sessionCookie = params.sessionCookie;
	this.preferences = new Preferences( 
		new RestPreferenceService( this.annotationPath + '/user-preference.php' ),
		prefs );
	this.showAnnotations = prefs[ Marginalia.P_SHOWANNOTATIONS ];
	this.showAnnotations = this.showAnnotations == 'true';
	this.splash = prefs[ Marginalia.P_SPLASH ] == 'true' ? params[ 'splash' ] : null;
	this.useSmartquote = params.useSmartquote;
	this.allowAnyUserPatch = params.allowAnyUserPatch;
	this.smartquoteIcon = params.smartquoteIcon;
	this.handlers = params.handlers;
	this.course = params.course;
	this.smartquoteService = params.smartquoteService;

	this.selectors = {
		post: new Selector( 'table.forumpost', 'table.forumpost table.forumpost' ),
		post_content: new Selector( '.content .posting', '.content .content .posting' ),
		post_title: new Selector( '.subject', '.content .subject' ),
		post_author: new Selector( '.author a', '.content .author a' ),
		post_authorid: null,
		post_date: null,
		post_url: new Selector( 'a[rel="post"]', '.content .posting a[rel="post"]', '@href' ),
		mia_notes: new Selector( '.mia_margin', '.content .posting .mia_margin' )
	};

	this.sheet = prefs[ Marginalia.P_SHEET ];
	
	// Ensure the sheet drop-down relfects the actual sheet to be shown
	// This relies on preferences being saved correctly.  Otherwise, the user may
	// pick a different user, visit another page, click back and find that the 
	// sheet control shows the wrong thing.
	var sheetCtrl = document.getElementById( 'ansheet' );
	if ( sheetCtrl )
	{
		for ( var i = 0;  i < sheetCtrl.options.length;  ++i )
		{
			if ( sheetCtrl.options[ i ].value == this.sheet )
			{
				sheetCtrl.selectedIndex = i;
				break;
			}
		}
	}
}

MoodleMarginalia.prototype.onload = function( )
{
	initLogging();


	// Check whether this page should have annotations enabled at all
	// The check is here rather in the PHP;  that minimizes the number of patches
	// that need to be applied to existing Moodle code.
	var actualUrl = '' + window.location;
	if ( this.loginUserId && ( actualUrl.match( /^.*\/mod\/forum\/discuss\.php\?d=(\d+)/ )
		|| actualUrl.match( /^.*\/mod\/forum\/post\.php.*/ ) ) )
	{
		var keywordService = new RestKeywordService( this.annotationPath + '/keywords.php', true);
		keywordService.init( null );
		var moodleMarginalia = this;
		window.marginalia = new Marginalia( annotationService, this.loginUserId, this.sheet, {
			preferences: this.preferences,
			keywordService: keywordService,
			baseUrl:  this.moodleRoot,
			showBlockMarkers:  false,
			showActions:  false,
			onkeyCreate:  true,
			enableRecentFlag: true,
			allowAnyUserPatch: this.allowAnyUserPatch ? true : false,
			displayNote: function(m,a,e,p,i) { moodleMarginalia.displayNote(m,a,e,p,i); },
			editors: {
				link: null,
				'default':  Marginalia.newEditorFunc( YuiAutocompleteNoteEditor )
			},
			onMarginHeight: function( post ) { moodleMarginalia.fixControlMargin( post ); },
			selectors: this.selectors
		} );
		
		this.cleanUpPostContent( );
		
		// Display annotations
		var url = this.url;
		if ( this.showAnnotations )
			window.marginalia.showAnnotations( url );
		
		// Fix all control margins
		this.fixAllControlMargins( );
		
		// Enable smartquotes and quote logging
		if ( this.useSmartquote )
		{
			this.smartquote = new Smartquote( this.moodleRoot, this.selectors, this.smartquoteService );
			this.smartquote.enable( marginalia.listPosts( ), marginalia.skipContent );
		}
		
//		var marginaliaDirect = new MarginaliaDirect( annotationService );
//		marginaliaDirect.init( );
	
		if ( this.showAnnotations && this.splash )
		{
			var onclose = function() {
				window.marginalia.preferences.setPreference( Marginalia.P_SPLASH, 'false', null);
			};
			window.marginalia.showTip( this.splash, onclose );
		}
	}
};

MoodleMarginalia.prototype.subscribeHtmlAreas = function( )
{
	if ( this.useSmartquote )
	{
		var subscriber = new SmartquoteSubscriber( this.smartquoteService );
		subscriber.subscribeAllHtmlAreas( );
	}
}


MoodleMarginalia.prototype.displayNote = function( marginalia, annotation, noteElement, params, isEditing )
{
	var moodleMarginalia = this;
	var wwwroot = this.moodleRoot;
	buttonParams = this.useSmartquote ?
		{
			className: 'quote',
			title: getLocalized( 'annotation quote button' ),
			content: this.smartquoteIcon,
			onclick: function( ) { 
				moodleMarginalia.smartquote.quoteAnnotation(
					annotation,
					marginalia.loginUserId,
					Smartquote.postIdFromUrl( annotation.getUrl( ) ) );
			}
		}
		: { };

	params.customButtons = [
		{
			owner: true,
			others: true,
			params: buttonParams
		}
	];
	return Marginalia.defaultDisplayNote( marginalia, annotation, noteElement, params, isEditing );
};

MoodleMarginalia.prototype.createAnnotation = function( event, postId )
{
	clickCreateAnnotation( event, postId );
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
MoodleMarginalia.prototype.fixControlMargin = function( post )
{
	var td = domutil.childByTagClass( post.getElement( ), 'td', 'margin-td', PostMicro.skipPostContent );
	var margin = domutil.childByTagClass( td, null, 'mia_margin' );
	margin.style.height = '';
	margin.style.height = '' + td.offsetHeight + 'px';
};

MoodleMarginalia.prototype.fixAllControlMargins = function( )
{
	var postInfo = window.marginalia.listPosts( );
	for ( var i = 0;  i < postInfo.posts.length;  ++i )
		this.fixControlMargin( postInfo.posts[ i ] );
}

MoodleMarginalia.prototype.cleanUpPostContent = function( )
{
	var f = function( node ) {
		for ( var child = node.firstChild;  child;  child = child.nextSibling )
		{
			if ( child.nodeType == ELEMENT_NODE )
			{
//				domutil.removeClass( child, PM_POST_CLASS );
//				domutil.removeClass( child, PM_CONTENT_CLASS );
//				domutil.removeClass( child, AN_NOTES_CLASS );
				// #geof# for now, simply clear all class names
				child.removeAttribute( 'class' );
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

MoodleMarginalia.prototype.changeSheet = function( sheetControl, url )
{
	var marginalia = window.marginalia;
	var sheet = sheetControl.value;
	
	// Check to see whether this is a special case with a named handler
	if ( this.handlers[ sheet ] )
		this.handlers[ sheet ]( this, marginalia );
	// This is simply a sheet name: go to that sheet
	else
	{
		marginalia.hideAnnotations( );
		if ( null == sheet || '' == sheet )
			marginalia.preferences.setPreference( Marginalia.P_SHOWANNOTATIONS, 'false', null);
		else
		{
			marginalia.sheet = sheet;
			marginalia.showAnnotations( url );
			marginalia.preferences.setPreference( Marginalia.P_SHOWANNOTATIONS, 'true', null);
			marginalia.preferences.setPreference( Marginalia.P_SHEET, sheet, null );
			this.fixAllControlMargins( );
		}
	}
};

