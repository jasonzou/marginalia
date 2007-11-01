function keywordsOnload( )
{
//	var replaceButton = document.getElementById( 'replace' );

	window.keywordService = new RestKeywordService( serviceRoot + '/keywords.php' );
	keywordService.init( annotationKeywords );
	refreshKeywords( );
	
	window.annotationService = new RestAnnotationService( serviceRoot + '/annotate.php', {
		csrfCookie: 'MoodleSessionTest' } );
	
	addEvent( '#new-keyword-name', 'keypress', _keypressCreateKeyword );
	addEvent( '#new-keyword-desc', 'keypress', _keypressCreateKeyword );
	addEvent( '#new-keyword-button', 'click', _createKeyword );
 
	addEvent( '#replace input', 'change', _clearReplaceCount );
	addEvent( '#replace input', 'keypress', _keypressReplaceNote );
	addEvent( '#replace button', 'click', _replaceNotes );
}

function refreshKeywords( )
{
	var list = document.getElementById( 'keywords' );
	var tbody = domutil.childByTagClass( list, 'tbody' );
	var items = domutil.childrenByTagClass( tbody, 'tr', 'keyword' );
	for ( var i = 0;  i < items.length;  ++i )
		tbody.removeChild( items[ i ] );

	var createItem = domutil.childByTagClass( tbody, 'tr', 'create' );
	
	keywordService.keywords.sort( compareKeywords );
	var keywords = keywordService.keywords;
	for ( var i = 0;  i < keywords.length;  ++i )
	{
		var keyword = keywords[ i ];
		tbody.insertBefore( domutil.element( 'tr', {
			className: 'keyword',
			keyword: keyword,
			content: [
				domutil.element( 'td', {
					className: 'name',
					content: keyword.name
				} ),
				domutil.element( 'td', {
					className: 'description',
					content: domutil.element( 'input', {
						type: 'text',
						onblur: _saveKeyword,
						onkeypress: _keypressKeyword,
						value: keyword.description					
					} ),
				} ),
				domutil.element( 'td', {
					content: domutil.element( 'button', {
						className: 'delete',
						onclick: _deleteKeyword,
						content: 'x'
					} ),
				} )
			]
		} ), createItem );
	}
}

function _keypressKeyword( event )
{
	if ( event.keyCode == 13 )
	{
		event.stopPropagation( );
		_saveKeyword( event );
		return false;
	}
	return true;
}

function _keypressCreateKeyword( event )
{
	if ( event.keyCode == 13 )
	{
		event.stopPropagation( );
		_createKeyword( event );
		return false;
	}
	return true;
}

function _saveKeyword( event )
{
	var target = domutil.getEventTarget( event );
	var keyword = domutil.nestedFieldValue( target, 'keyword' );
	var input = domutil.childByTagClass( target, 'input' );
	keyword.description = input.value;
	keywordService.updateKeyword( keyword );
}

function compareKeywords( k1, k2 )
{
	if ( k1.name < k2.name )
		return -1;
	else if ( k1.name > k2.name )
		return 1;
	else
		return 0;
}

function _deleteKeyword( event )
{
	var target = domutil.getEventTarget( event );
	var keyword = domutil.nestedFieldValue( target, 'keyword' );
	delete keywordService.keywordHash[ keyword.name ];
	for ( var i = 0;  i < keywordService.keywords.length;  ++i )
	{
		if ( keywordService.keywords[ i ].name == keyword.name )
		{
			keywordService.keywords[ i ] = keywordService.keywords[ keywordService.keywords.length - 1 ];
			keywordService.keywords.pop();
			break;
		}
	}
	keywordService.deleteKeyword( keyword.name, refreshKeywords );
}

function _createKeyword( event )
{
	var nameNode = document.getElementById( 'new-keyword-name' );
	var descNode = document.getElementById( 'new-keyword-desc' );
	var name = nameNode.value;
	var description = descNode.value;
	nameNode.value = '';
	descNode.value = '';
	if ( name )
	{
		var keyword = new Keyword( name, description );
		keywordService.keywords[ keywordService.keywords.length ] = keyword;
		keywordService.keywordHash[ keyword.name ] = keyword;
		keywordService.createKeyword( keyword, refreshKeywords );
	}
}


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


