
Smartquote = {
	/**
	 * Return a function for handling a smartquote button click
	 */
	getOnSmartquoteHandler: function( content )
	{
		var moodleMarginalia = this;
		return function( ) { moodleMarginalia.onSmartquote( content ); };
	},

	quotePostMicro: function( content, skipContent )
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
		
		// Don't need a skip handler unless we're running on a page with Marginalia
		textRange = textRange.shrinkwrap( skipContent );
		if ( ! textRange )
		{
			// this happens if the shrinkwrapped range has no non-whitespace text in it
			if ( warn )
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
		var pub = leadIn + '<blockquote><p>' + domutil.htmlEncode( quote ) + '</p></blockquote>';
		
		var bus = new CookieBus( 'smartquote' );
		bus.publish( pub );
	},
	
	
	quoteAnnotation: function( annotation, loginUserId )
	{
		var quoteAuthor = annotation.getQuoteAuthorName( );
		var url = annotation.getUrl( );
		var quote = annotation.getQuote( );
		var note = annotation.getNote( );
		var noteAuthor = annotation.getUserName( );
		
		quote = quote.replace( /\s/g, ' ' );
		quote = quote.replace( /\u00a0/g, ' ' );

		var pub = '<p>' + ( quoteAuthor ? domutil.htmlEncode( quoteAuthor ) : 'Someone' )
			+ ( url ? '<a href="' + domutil.htmlEncode( url ) + '">wrote,</a>' : 'wrote' )
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
		bus.publish( pub );
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
		} );
	}
};

