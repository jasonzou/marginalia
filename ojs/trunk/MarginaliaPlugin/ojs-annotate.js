
ANNOTATION_ACCESS_DEFAULT = 'private';	// default access
AN_PERBLOCKANNOTATIONS_CLASS = 'per-block-annotations';

function ojsAnnotationInit( serviceRoot, currentUser )
{
	addEvent( window, 'load', function() { ojsAnnotationOnLoad( serviceRoot, currentUser ); });
}


function ojsAnnotationOnLoad( serviceRoot, currentUser )
{
	var annotationService = new RestAnnotationService( serviceRoot + '/annotate', false );
	var keywordService = new RestKeywordService( serviceRoot + '/keywords' );
	keywordService.init( );

	window.marginalia = new Marginalia( annotationService, currentUser, currentUser, {
		preferences: new Preferences( new RestPreferenceService( serviceRoot + '/preference', true ) ),
		keywordService: keywordService,
		baseUrl:  null,
		showAccess:  true,
		showBlockMarkers:  true,
		showActions:  false,
		onkeyCreate:  true,
		editors: {
			link:  Marginalia.newEditorFunc( ClickToLinkUi )
		}
	} );
	
	var marginaliaDirect = new MarginaliaDirect( annotationService );
	marginaliaDirect.init( );

// Uncomment this line to turn in-browser logging on:
//	initLogging();

	var mainNode = document.getElementById( "main" );
	var contentNode = document.getElementById( "content" );

	// Don't do anything if the essential nodes cannot be found
	if ( mainNode && contentNode )
	{
		// Add classes expected by Marginalia
		domutil.addClass( mainNode, PM_POST_CLASS );
		domutil.addClass( contentNode, PM_CONTENT_CLASS );

		var titleNode = domutil.childByTagClass( mainNode, 'h2', null, null );
		if ( titleNode )
			domutil.addClass( titleNode, PM_TITLE_CLASS );
		
		var breadcrumbNode = document.getElementById( "breadcrumb" );
		if ( breadcrumbNode )
		{
			node = breadcrumbNode.lastChild;
			while ( ELEMENT_NODE != node.nodeType || ( 'a' != node.tagName && 'A' != node.tagName ) )
				node = node.previousSibling;
			node.setAttribute( "rel", PM_URL_REL );
			var url = node.getAttribute( 'href' );
		}
		
		// Should also be inserting author information, but I'm not sure where
		// to get that (quote_author_id, quote_author_name).
	
		// Create a wrapper around the article content so we can create two columns -
		// one for the content, one for margin notes
		// I know tables are not approved of for layout, but I need the columns to
		// resize correctly.
		var wrapper = mainNode.appendChild( domutil.element( 'div', {
			id: 'column-wrapper',
			content: [
				domutil.element( 'div', { className: 'markers' } ),
				contentNode.parentNode.removeChild( contentNode )
			]
		} ) );
		
		// Create the margin notes area
		var notes = wrapper.appendChild( domutil.element( 'div', {
			className: 'notes',
			content:  [ 
				domutil.element( 'div', {
					className: 'button-wrapper',
					content:
						domutil.button ( {
							className: 'createAnnotation',
							title: 'Click here to create an annotation',
							onclick: ojsCreateAnnotation,
							content: '>'
						} ),
				} ),
				domutil.element( 'ol', {
					content:  domutil.element( 'li', { } )
				} )
			]
		} ) );
	
	//  Slow, but can be made to work in IE:
	//	makeBlockElementsLinkable( content, '' );
		
		var fragment = getNodeByFragmentPath( '' + window.location );
		if ( fragment )
		{
			domutil.scrollWindowToNode( fragment );
			domutil.addClass( fragment, AN_FLASH_CLASS );
			fragment.flashcount = 4;
			setTimeout( _flashLinkTarget, 240 );
	//		addClass( fragment, 'link-target' );
		}
		if ( currentUser )
			window.marginalia.showAnnotations( url, null );
	}
}

function ojsCreateAnnotation( event )
{
	event.stopPropagation( );
	createAnnotation( 'main', true );
}

function getNodeByFragmentPath( url )
{
	var postInfo = new PostPageInfo( document );
	var posts = postInfo.getAllPosts( );
	if ( 0 == posts.length )
		return;
	var post = posts[ 0 ];
	
	var content = post.getContentElement( );
	if ( -1 == url.indexOf( '#' ) )
		return null;
	var path = url.substring( url.indexOf( '#' ) + 1 );
	var node = domutil.blockPathToNode( content, path );
	return node;
}


/* Slow, but might be useful on IE
function makeBlockElementsLinkable( content, path )
{
	var i = 0;
	for ( var node = content.firstChild;  null != node;  node = node.nextSibling )
	{
		i += 1;
		if ( ELEMENT_NODE == node.nodeType
			&& 'block' == htmlDisplayModel( node.tagName )
			&& ! hasClass( node, 'linkable-icon' ) )
		{
			makeBlockElementsLinkable( node, path + '/' + i );
			addClass( node, 'linkable' );
			var indicator = document.createElement( 'span' );
			indicator.className = 'linkable-icon';
			indicator.setAttribute( 'class', 'linkable-icon' );
			indicator.appendChild( document.createTextNode( '\u00b6' ) );
			node.appendChild( indicator );
		}
	}
}
*/

function getKeywordsPref( )
{
	return AN_EDIT_NOTE_KEYWORDS;
}

