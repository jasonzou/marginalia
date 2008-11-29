/*
 * Subscribe an HTMLArea control to receive smartquote publish events
 */
 
function subscribeSmartquoteHtmlArea( editor )
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

