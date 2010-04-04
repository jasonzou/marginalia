/*
 * Smartquote functions used in Moodle
 * built on CookieBus
 */

function Smartquote( wwwroot, selectors, extService )
{
	this.wwwroot = wwwroot;
	this.selectors = selectors;
	this.extService = extService;
}

/**
 * Enable all smartquote buttons on the page
 * Buttons are found in posts with selector button.smartquote
 */
Smartquote.prototype.enable = function( postPageInfo, skipContent, params )
{
	// Use passed-in value for speed if possible
	if ( ! postPageInfo )
		postPageInfo = PostPageInfo.getPostPageInfo( document, this.selectors );

	// Enable smartquote buttons
	var posts = postPageInfo.getAllPosts( );
	for ( var i = 0;  i < posts.length;  ++i )
	{
		var button = domutil.childByTagClass( posts[ i ].getElement( ), 'button', 'smartquote', skipContent );
		if ( button )
			this.enableButton( button, posts[ i ], skipContent );
	}
}

/**
 * Enable a specific smartquote button
 * Must be a separate function from the loop in enableSmartquote to deal
 * correctly with Javascript dynamic scoping and closures
 */
Smartquote.prototype.enableButton = function( button, post, skipContent )
{
	var smartquote = this;
	var content = post.getContentElement( );
	var postId = Smartquote.postIdFromUrl( post.getUrl( ) );
	var f = function( ) { smartquote.quotePostMicro( content, skipContent, postId ); };
	addEvent( button, 'click', f );
}
	
/**
 * Calculate a post ID based on its URL
 * must have implementations for each kind of quoteable url
 */
Smartquote.postIdFromUrl = function( url )
{
	var matches = url.match( /^.*\/mod\/forum\/permalink\.php\?p=(\d+)/ );
	if ( matches )
		return Number( matches[ 1 ] );
	else
		return 0;
},
	
/**
 * Get a quote (selected text) from a postMicro with a given ID
 * Returns the quote as HTML with metadata included.  Note, however, that
 * any HTML tags in the selected text are stripped, and whitespace is
 * collapsed.
 */
Smartquote.prototype.getPostMicroQuote = function( content, skipContent, postId )
{
	// Test for selection support (W3C or IE)
	if ( ( ! window.getSelection || null == window.getSelection().rangeCount )
		&& null == document.selection )
	{
		alert( getLocalized( 'browser support of W3C range required for smartquote' ) );
		return false;
	}
		
	var textRange0 = getPortableSelectionRange();
	if ( null == textRange0 )
	{
		alert( getLocalized( 'select text to quote' ) );
		return false;
	}
	
	// Strip off leading and trailing whitespace and preprocess so that
	// conversion to WordRange will go smoothly.
	var textRange = TextRange.fromW3C( textRange0 );
	
	// Don't need a skip handler unless we're running on a page with Marginalia
	textRange = textRange.shrinkwrap( skipContent );
	if ( ! textRange )
	{
		// this happens if the shrinkwrapped range has no non-whitespace text in it
		alert( getLocalized( 'select text to quote' ) );
		return false;
	}
	
	var quote = getTextRangeContent( textRange, skipContent );
	quote = quote.replace( /(\s|\u00a0)+/g, ' ' );
	
	var postInfo = PostPageInfo.getPostPageInfo( document, this.selectors );
	var post = postInfo.getPostByElement( textRange.startContainer );
	var leadIn = '';
	if ( post )
	{
		leadIn = '<p>' + ( post.getAuthorName( ) ? domutil.htmlEncode( post.getAuthorName( ) ) : 'Someone' )
			+ ( post.getUrl( ) ? ' <a href="' + domutil.htmlEncode( post.getUrl( ) ) + '">wrote</a>' : 'wrote' )
			+ ",</p>";
	}
	return leadIn + '<blockquote><p>' + domutil.htmlEncode( quote ) + '</p></blockquote>';
}
	
	
/**
 * Called when a quote button is clicked on a postMicro.  Extracts the
 * selected text, builds HTML with metadata, and publishes it on the
 * CookieBus.
 */
Smartquote.prototype.quotePostMicro = function( content, skipContent, postId )
{
//		console.log( 'quote' );
	var pub = this.getPostMicroQuote( content, skipContent, postId );
	var bus = new CookieBus( 'smartquote' );
	if ( bus.getSubscriberCount( ) > 0 )
	{
//			console.log( 'publish: ' + pub );
		bus.publish( pub );
	
		if ( this.extService )
			this.extService.createEvent( 'smartquote', 'send', pub, 'forum_post', postId );
	}
	else if ( this.wwwroot && postId )
	{
		// The nbsp below inserts an annoying extra space - but that's better
		// than the editor's default behavior of adding any new text to the previous
		// blockquote.  Moodle needs a new editor (this one was discontinued).
		window.location = this.wwwroot + '/mod/forum/post.php?reply=' + postId
			+ '&message=' + restutil.encodeURIParameter( pub + '&nbsp;<p>');
	
		if ( this.extService )
			this.extService.createEvent( 'smartquote', 'new post', pub, 'forum_post', postId );
	}
}
	
	
Smartquote.prototype.quoteAnnotation = function( annotation, loginUserId, postId )
{
	var quoteAuthor = annotation.getQuoteAuthorName( );
	var url = annotation.getUrl( );
	var quote = annotation.getQuote( );
	var note = annotation.getNote( );
	var noteAuthor = annotation.getUserName( );
	
	quote = quote.replace( /\s/g, ' ' );
	quote = quote.replace( /\u00a0/g, ' ' );

	var pub = '<p>' + ( quoteAuthor ? domutil.htmlEncode( quoteAuthor ) : 'Someone' )
		+ ( url ? ' <a href="' + domutil.htmlEncode( url ) + '">wrote,</a>' : ' wrote' )
		+ '</p><blockquote><p>' + domutil.htmlEncode( quote ) + '</p></blockquote>';
	if ( loginUserId == annotation.getUserId( ) )
	{
		if ( annotation.getNote( ) )
			pub += '<p>' + domutil.htmlEncode( note ) + '</p>';
	}
	else
	{
		note = note.replace( /\s/g, ' ' );
		note = note.replace( /\u00a0/g, ' ' );
		if ( note )
		{
			pub += '<p>Via ' + domutil.htmlEncode( noteAuthor ) + ', who noted,</p>'
				+ '<blockquote><p>' + domutil.htmlEncode( note ) + '</p></blockquote>';
		}
		else
			pub += '<p>(Via an annotation by ' + domutil.htmlEncode( noteAuthor ) + '.)</p>';
	}
	
	var bus = new CookieBus( 'smartquote' );
	if ( bus.getSubscriberCount( ) > 0 )
	{
		bus.publish( pub );
		if ( this.extService )
			this.extService.createEvent( 'smartquote', 'send', quote, 'annotation', annotation.getId() );
	}
	else if ( this.wwwroot && postId )
	{
		// The nbsp below inserts an annoying extra space - but that's better
		// than the editor's default behavior of adding any new text to the previous
		// blockquote.  Moodle needs a new editor (this one was discontinued).
		window.location = this.wwwroot + '/mod/forum/post.php?reply=' + postId
			+ '&message=' + restutil.encodeURIParameter( pub + "&nbsp;<p>" );
	
		if ( this.extService )
			this.extService.createEvent( 'smartquote', 'new post', quote, 'annotation', annotation.getId() );
	}
}
	

function SmartquoteSubscriber( extService )
{
	this.extService = extService;
}

/**
 * Subscribe all HTMLAreas on the page to smartquote events
 */
SmartquoteSubscriber.prototype.subscribeAllHtmlAreas = function( object_type, object_id )
{
	for ( name in window )
	{
		var field = window[ name ];
		if ( field && HTMLArea && field.constructor == HTMLArea )
			this.subscribeHtmlArea( window[ name ] );
	}
}
	
/**
 * Subscribe an HTMLArea control to receive smartquote publish events
 */
SmartquoteSubscriber.prototype.subscribeHtmlArea = function( editor, object_type, object_id )
{
	var subscriber = this;
	// This code and these tests are very much specific to HTMLArea
	// The test for the range is necessary - otherwise if the user hasn't
	// clicked in the area, everything can blow up.
	var bus = new CookieBus( 'smartquote' );
	bus.subscribe( 1000, function( pub ) {
		var sel = editor._getSelection( );
		var range = editor._createRange( sel );
		// D'oh.  Default range is in HTMLDocument, which of course has
		// no parent (best way I could think to test for that).  HTMLArea
		// blows up when an insert is attempted then.
		if ( HTMLArea.is_ie )
		{
			if ( 'None' == sel.type && false )
			{
				var textRange = editor._doc.body.createTextRange( );
				textRange.select( );
			}
		}
		else
		{
			if ( ! range.startContainer.parentNode )
			{
				var textRange = editor._doc.createRange( );
				textRange.selectNode( editor._doc.body.lastChild );
				var selection = editor._iframe.contentWindow.getSelection();
				selection.addRange( textRange );
				selection.collapseToEnd( );
			}
		}
		editor.insertHTML( pub.value + ' ' + '<br/>');
		
		// Collapse range to the end of the document
		// Otherwise the editor ends up selecting the first paragraph of the
		// last paste, which will be stomped by subsequent pastes
		// Mozilla only (sorry IE - for now)
		if ( ! HTMLArea.is_ie )
		{
			var textRange = editor._doc.createRange( );
			textRange.selectNode( editor._doc.body.lastChild );
			var selection = editor._iframe.contentWindow.getSelection();
			selection.addRange( textRange );
			selection.collapseToEnd( );
		}
		
			
		if ( subscriber.extService )
			subscriber.extService.createEvent( 'smartquote', 'receive', pub.value, object_type, object_id );
	} );
	
	// Don't forget to unsubscribe if the window is unloaded
	addEvent( window, 'unload', function( ) { bus.unsubscribe( ); } );
	
	return bus;
}

