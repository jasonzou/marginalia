
AN_PERBLOCKANNOTATIONS_CLASS = 'per-block-annotations';

function ojsAnnotationInit( serviceRoot, currentUser )
{
	addEvent( window, 'load', function() { ojsAnnotationOnLoad( serviceRoot, currentUser ); });
}


function ojsAnnotationOnLoad( serviceRoot, currentUser )
{
	var annotationService = new RestAnnotationService( serviceRoot );
	var preferences = new Preferences( new RestPreferenceService( serviceRoot ) );
	var keywordService = new RestKeywordService( serviceRoot + '/keywords' );
	keywordService.init( );
	marginaliaInit( annotationService, currentUser, currentUser, null, preferences, keywordService );
	window.marginalia.linkUi = new ClickToLinkUi( );

	var marginaliaDirect = new MarginaliaDirect( annotationService );
	marginaliaDirect.init( );

	initLogging();

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
	
		// Create a wrapper around the article content so we can create two columns -
		// one for the content, one for margin notes
		var wrapper = mainNode.appendChild( domutil.element( 'div', {
			id: 'column-wrapper',
			content: contentNode.parentNode.removeChild( contentNode )
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
			scrollWindowToNode( fragment );
			domutil.addClass( fragment, AN_FLASH_CLASS );
			fragment.flashcount = 4;
			setTimeout( _flashLinkTarget, 240 );
	//		addClass( fragment, 'link-target' );
		}
		window.marginalia.showAnnotations( url, null );
		window.marginalia.showMarginalia( );
	}
}

function ojsCreateAnnotation( event )
{
	event.stopPropagation( );
	createAnnotation( 'main', true );
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

function _skipSmartcopy( node )
{
	if ( ELEMENT_NODE == node.nodeType )
		return domutil.hasClass( node, 'smart-copy' ) ? true : false;
	return false;
}

function _skipContent( node )
{
	return _skipSmartcopy( node ) || _skipAnnotationLinks( node );
}

function getKeywordsPref( )
{
	return AN_EDIT_NOTE_KEYWORDS;
}

