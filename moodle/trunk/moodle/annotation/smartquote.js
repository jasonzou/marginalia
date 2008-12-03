
Smartquote = {
	enableSmartquote: function( wwwroot, postPageInfo, skipContent )
	{
		if ( ! postPageInfo )
			postPageInfo = PostPageInfo.getPostPageInfo( document );

		// Enable smartquote buttons
		var posts = postPageInfo.getAllPosts( );
		for ( var i = 0;  i < posts.length;  ++i )
		{
			var button = domutil.childByTagClass( posts[ i ].getElement( ), 'button', 'smartquote', skipContent );
			if ( button )
			{
				var content = posts[ i ].getContentElement( );
				var postId = Smartquote.postIdFromUrl( posts[ i ].getUrl( ) );
				button.onclick = function( ) { Smartquote.quotePostMicro( content, skipContent, wwwroot, postId ); };
			}
		}
	},
	
	/**
	 * Return a function for handling a smartquote button click
	 */
	getOnSmartquoteHandler: function( content )
	{
		var moodleMarginalia = this;
		return function( ) { moodleMarginalia.onSmartquote( content ); };
	},

	postIdFromUrl: function( url )
	{
		var matches = url.match( /^.*\/mod\/forum\/permalink\.php\?p=(\d+)/ );
		if ( matches )
			return Number( matches[ 1 ] );
		else
			return 0;
	},
	
	getPostMicroQuote: function( content, skipContent, wwwroot, postId )
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
		
		var postInfo = PostPageInfo.getPostPageInfo( document );
		var post = postInfo.getPostMicro( textRange.startContainer );
		var leadIn = '';
		if ( post )
		{
			leadIn = '<p>' + ( post.getAuthorName( ) ? domutil.htmlEncode( post.getAuthorName( ) ) : 'Someone' )
				+ ( post.getUrl( ) ? ' <a href="' + domutil.htmlEncode( post.getUrl( ) ) + '">wrote</a>' : 'wrote' )
				+ ",</p>";
		}
		return leadIn + '<blockquote><p>' + domutil.htmlEncode( quote ) + '</p></blockquote>';
	},
	
	quotePostMicro: function( content, skipContent, wwwroot, postId )
	{
		console.log( 'quote' );
		var pub = Smartquote.getPostMicroQuote( content, skipContent, wwwroot, postId );
		var bus = new CookieBus( 'smartquote' );
		if ( bus.getSubscriberCount( ) > 0 )
		{
			console.log( 'publish: ' + pub );
			bus.publish( pub );
		}
		else if ( wwwroot && postId )
		{
			window.location = wwwroot + '/mod/forum/post.php?reply=' + postId
				+ '&message=' + encodeURIParameter( pub );
		}
	},
	
	
	quoteAnnotation: function( annotation, loginUserId, wwwroot, postId )
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
			bus.publish( pub );
		else if ( wwwroot && postId )
		{
			window.location = wwwroot + '/mod/forum/post.php?reply=' + postId
				+ '&message=' + encodeURIParameter( pub );
		}
	},
	

	/**
	 * Subscribe an HTMLArea control to receive smartquote publish events
	 */
	subscribeHtmlArea: function( editor )
	{
		// This code and these tests are very much specific to HTMLArea
		// The test for the range is necessary - otherwise if the user hasn't
		// clicked in the area, everything can blow up.
		var bus = new CookieBus( 'smartquote' );
		bus.subscribe( 2000, function( pub ) {
			var sel = editor._getSelection( );
			var range = editor._createRange( sel );
			// D'oh.  Default range is in HTMLDocument, which of course has
			// no parent (best way I could think to test for that).  HTMLArea
			// blows up when an insert is attempted then.
			if ( ! range.startContainer.parentNode )
			{
				if ( HTMLArea.is_ie )
				{
					var textRange = editor._doc.body.createTextRange( );
					textRange.select( );
				}
				else
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
		} );
		return bus;
	}
};



