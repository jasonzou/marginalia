function _keypressReplaceNote( event )
{
	if ( event.keyCode == 13 )
	{
		event.stopPropagation( );
		_replaceNotes( );
		return false;
	}
	return true;
}

function _clearReplaceCount( event )
{
	var prompt = document.getElementById( 'replace-count-prompt' );
	prompt.style.display = 'none';
}

function _replaceNotes( event )
{
	var oldNote = document.getElementById( 'old-note' );
	var newNote = document.getElementById( 'new-note' );
	f = function( t ) {
		var prompt = document.getElementById( 'replace-count-prompt' );
		prompt.style.display = 'block';
		var count = document.getElementById( 'replace-count' );
		while ( count.firstChild )
			count.removeChild( count.firstChild );
		count.appendChild( document.createTextNode( t ) );
	}
	annotationService.bulkUpdate( oldNote.value, newNote.value, f );	
}


