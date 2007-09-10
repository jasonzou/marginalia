/*
	The myCreateAnnotation function is essential because it must stop propagation of
	the button click that creates the annotation.  Otherwise, that click would be
	interpreted as a click outside the text edit for the annotation, in which case
	the annotation would be saved immediately without editing.  Note that this does
	*not* need to be called called when an annotation is created by typing Enter.
	
	The _skipContent callback must be defined for Marginalia to function (Marginalia
	callback function names begin with underscores).  It needs to be able to test an 
	HTML element to find out whether or not that node's contents should be included 
	when counting words and so on.  The _skipAnnotationLinks test should always be 
	performed if you're allowing annotations to include links;  _skipSmartcopy is 
	needed if Smartcopy is on.  If there are other reasons for other stuff in the
	content area (if, for example, you had actual annotation notes there), they 
	should be skipped too.
	
	marginaliaInit takes four parameters:
	1. The base URL of the application.  The annotation directory should be beneath 
	   this (where exactly depends on how you've set up your service calls in 
	   rest-annotate.js;  since this demo uses static-annotate.js instead it doesn't
	   really apply).  In the case of Moodle, this is the URL of the Moodle directory. 
	2. The name of the current user.  In Moodle this is the "username" field.
	3. The name of the user whose annotations are to be shown.
	4. The portion of the URL to strip.  If null, the database will store absolute URLs.
	   Otherwise, this can be the URL preceding the path (e.g. http://my.host.name), in
	   which case that portion of the URL is not stored (very useful if the host name
	   might change - otherwise, moving to a new server would require a search and replace
	   on annotated URLs in the database).  This should be more flexible and allow more 
	   of the path to be stripped;  for now those are the two choices.
	   
	The preferences object is required for load and storing user preferences, such as
	whether the user last created an annotation using a keywords drop-down or a text
	entry field.  The current implementation is a static dummy.
	  
	Keywords initialization is needed for the keywords drop-down.  It is called now to
	fetch the list of keywords so they will be ready when needed.
	  
	If you don't call smartcopyInit() and smartCopyOn(), smartcopy will not be enabled.
*/

// Needed when creating annotations:
ANNOTATION_ACCESS_DEFAULT = 'private';	// default access

function demoOnLoad( userid, serviceRoot, queryUrl )
{
	userid = userid ? userid : 'anonymous';
	var annotationService = new RestAnnotationService( serviceRoot + '/annotate.php', false );
	var keywordService = new RestKeywordService( serviceRoot + '/keywords.txt');
	keywordService.init( );
	window.marginalia = new Marginalia( annotationService, userid, userid, {
		preferences: new Preferences( new StaticPreferenceService( ) ),
		keywordService: keywordService,
		linkUi:  new ClickToLinkUi( true ),
		baseUrl:  null,
		showAccess:  true,
		showBlockMarkers:  true,
		showActions:  true,
		onkeyCreate:  true,
		warnDelete: false,
//		skipContent: _skipSmartcopy,
		showCaret: false,
		userInRequest: true,
		displayNote: bungeni.displayNote,
		editors: {
			'default':  Marginalia.newEditorFunc( BungeniNoteEditor ),
			freeform:  Marginalia.newEditorFunc( BungeniNoteEditor )
		}
	} );
	
	trackchanges.addEditShortcuts( );
//	smartcopyInit( );
//	smartcopyOn( );
	
	var marginaliaDirect = new MarginaliaDirect( annotationService );
	marginaliaDirect.init( );
	window.marginaliaQueryUrl = queryUrl;
	window.marginalia.showAnnotations( queryUrl );
}


bungeni = {
	editType:  function( annotation, isEditing )
	{
		if ( annotation.getAction() == 'edit' )
		{
			if ( isEditing || annotation.getNote() )
			{
				if ( annotation.getQuote() )
					return getLocalized( 'note replace label' );
				else
					return getLocalized( 'note insert label' );
			}
			else
				return getLocalized( 'note delete label' );
		}
		else
			return getLocalized( 'note note label' );	
	},
	
	standardNoteDisplay: function( marginalia, annotation, noteElement, params, isEditing )
	{

		noteElement.appendChild( domutil.element( 'span', {
			className: 'note-type',
			content: bungeni.editType( annotation, isEditing )
		} ) );
		
		if ( params.isCurrentUser )
		{
			var controls = domutil.element( 'div', { className: 'controls' } );
			noteElement.appendChild( controls );

			// add the link button
			if ( params.linkingEnabled )
			{
				controls.appendChild( domutil.button( {
					className:  AN_LINKBUTTON_CLASS,
					title:  getLocalized( 'annotation link button' ),
					content:  AN_LINK_EDIT_ICON
				} ) );
			}
	
			// add the access button
			if ( marginalia.showAccess )
			{
				controls.appendChild( domutil.button( {
					className:  AN_ACCESSBUTTON_CLASS,
					title:  getLocalized( annotation.getAccess() == AN_PUBLIC_ACCESS ? 'public annotation' : 'private annotation' ),
					content:  annotation.getAccess() == AN_PUBLIC_ACCESS ? AN_SUN_SYMBOL : AN_MOON_SYMBOL
				} ) );
			}
			
			// add the delete button
			controls.appendChild( domutil.button( {
				className:  AN_DELETEBUTTON_CLASS,
				title:  getLocalized( 'delete annotation button' ),
				content:  'x'
			} ) );
	
			marginalia.bindNoteBehaviors( annotation, controls, [
				[ 'button.annotation-link', { click: 'edit link' } ],
				[ 'button.annotation-access', { click: 'access' } ],
				[ 'button.annotation-delete', { click: 'delete' } ]
			] );
		}
	},
	
	displayNote: function( marginalia, annotation, noteElement, params )
	{
		bungeni.standardNoteDisplay( marginalia, annotation, noteElement, params, false );
		
		// add the text content
		var noteText = document.createElement( 'p' );
		var titleText = null;
	
		if ( ! params.quoteFound || ! annotation.getRange( SEQUENCE_RANGE ) )
			titleText = getLocalized( 'quote not found' ) + ': \n"' + annotation.getQuote() + '"';
		else if ( params.keyword )
			titleText = params.keyword.description;
		
		if ( titleText )
			noteText.setAttribute( 'title', titleText );
		
		// If this doesn't belong to the current user, add the name of the owning user
		if ( ! params.isCurrentUser )
		{
			domutil.addClass( noteElement, 'other-user' );
			noteText.appendChild( domutil.element( 'span', {
				className:  'username',
				content:  annotation.getUserId( ) + ': ' } ) );
		}
		noteText.appendChild( document.createTextNode( annotation.getNote() ) );
		noteElement.appendChild( noteText );
		
		// Return behavior mappings
		if ( params.isCurrentUser )
		{
			marginalia.bindNoteBehaviors( annotation, noteElement, [
				[ 'p', { click: 'edit' } ]
			] );
		}
	}
}
	

function BungeniNoteEditor( )
{
	this.editNode = null;
}

BungeniNoteEditor.prototype.bind = FreeformNoteEditor.prototype.bind;
BungeniNoteEditor.prototype.clear = FreeformNoteEditor.prototype.clear;
BungeniNoteEditor.prototype.save = FreeformNoteEditor.prototype.save;
BungeniNoteEditor.prototype.focus = FreeformNoteEditor.prototype.focus;

BungeniNoteEditor.prototype.show = function( )
{
	var postMicro = this.postMicro;
	var marginalia = this.marginalia;
	var annotation = this.annotation;
	var noteElement = this.noteElement;
	
	bungeni.standardNoteDisplay( marginalia, annotation, noteElement, {
		isCurrentUser: true,
		linkingEnabled: true,
	}, true );
	
	// Create the edit box
	this.editNode = document.createElement( "textarea" );
	this.editNode.rows = 3;
	this.editNode.appendChild( document.createTextNode( annotation.getNote() ) );

	// Set focus after making visible later (IE requirement; it would be OK to do it here for Gecko)
	this.editNode.annotationId = this.annotation.getId();
	addEvent( this.editNode, 'keypress', _editNoteKeypress );
	addEvent( this.editNode, 'keyup', _editChangedKeyup );
	
	this.noteElement.appendChild( this.editNode );
}

function initLogging( )
{
	var log = window.log = new ErrorLogger( false, true );

	// Set these to true to view certain kinds of events
	// Most of these are only useful for debugging specific areas of code.
	// annotation-service, however, is particularly useful for most debugging
	log.setTrace( 'annotation-service', true );	// XMLHttp calls to the annotation service
	log.setTrace( 'word-range', false );			// Word Range calculations (e.g. converting from Text Range)
	log.setTrace( 'xpath-range', false );			// Trace XPath ranges
	log.setTrace( 'find-quote', false );			// Check if quote matches current state of document
	log.setTrace( 'node-walk', false );			// Used for going through nodes in document order
	log.setTrace( 'show-highlight', false );		// Text highlighting calculations
	log.setTrace( 'align-notes', false );			// Aligning margin notes with highlighting
	log.setTrace( 'range-compare', false );		// Compare range positions
	log.setTrace( 'range-string', false );			// Show conversions of word ranges to/from string
	log.setTrace( 'list-annotations-xml', false );// Show the full Atom XML coming back from listAnnotations
	log.setTrace( 'WordPointWalker', false );		// Show return values from WordPointWalker
	log.setTrace( 'prefs', false );				// List fetched preferences
	log.setTrace( 'keywords', false );				// List fetched keywords
	log.setTrace( 'BlockPoint.compare', false );	// Compare two BlockPoints
	log.setTrace( 'range-timing', false );			// Calculate the speed of range calculations
	log.setTrace( 'highlight-timing', false );	// Calculate the speed of highlight display
	log.setTrace( 'actions', false );				// Insertion of action text
	log.setTrace( 'behavior', true );				// Behavior mappings
}
