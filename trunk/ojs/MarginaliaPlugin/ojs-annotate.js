
AN_PERBLOCKANNOTATIONS_CLASS = 'per-block-annotations';

function ojsAnnotationInit( serviceRoot, currentUser )
{
	var annotationService = new RestAnnotationService( serviceRoot );
	var preferences = new Preferences( new RestPreferenceService( serviceRoot ) );
	var keywordService = new RestKeywordService( serviceRoot + '/keywords' );
	keywordService.init( );
	marginaliaInit( annotationService, currentUser, currentUser, null, preferences, keywordService );
	window.addEventListener( 'load', ojsAnnotationOnLoad, false );

	var marginaliaDirect = new MarginaliaDirect( annotationService );
	marginaliaDirect.init( );
}


function ojsAnnotationOnLoad()
{
	initLogging();

	var mainNode = document.getElementById( "main" );
	var contentNode = document.getElementById( "content" );

	// Don't do anything if the essential nodes cannot be found
	if ( mainNode && contentNode )
	{
		// Add classes expected by Marginalia
		addClass( mainNode, PM_POST_CLASS );
		addClass( contentNode, PM_CONTENT_CLASS );

		var titleNode = getChildByTagClass( mainNode, 'h2', null, null );
		if ( titleNode )
			addClass( titleNode, PM_TITLE_CLASS );
		
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
		var wrapper = document.createElement( "div" );
		wrapper.id = 'column-wrapper';
		wrapper.setAttribute( 'id', 'column-wrapper' );
		mainNode.appendChild( wrapper );
		
		// Add an annotation mode swich button
		var select = document.createElement( 'select' );
		select.id = 'annotation-mode';
		select.setAttribute( 'name', 'annotation-mode' );
		select.onchange = onAnnotationModeChange;
		var option = document.createElement( 'option' );
		option.setAttribute( 'value', 'mine' );
		option.setAttribute( 'selected', 'selected' );
		option.appendChild( document.createTextNode( 'My Annotations' ) );
		select.appendChild( option );
		option = document.createElement( 'option' );
		option.setAttribute( 'value', 'others' );
		option.appendChild( document.createTextNode( 'Other Annotations' ) );
		select.appendChild( option );
		wrapper.appendChild( select );
		
		wrapper.appendChild( contentNode.parentNode.removeChild( contentNode ) );
		
		// Create the margin notes area
		var notes = document.createElement( "div" );
		addClass( notes, "notes" );
		var buttonDiv = document.createElement( 'div' );
		addClass( buttonDiv, 'button-wrapper' );
		notes.appendChild( buttonDiv );
		var button = document.createElement( "button" );
		button.appendChild( document.createTextNode( ">" ) );
		addClass( button, "createAnnotation" );
		button.setAttribute( 'title', 'Click here to create an annotation.' );
		button.onclick = ojsCreateAnnotation;
		buttonDiv.appendChild( button);
		wrapper.appendChild( notes );
		
		var ol = document.createElement( "ol" );
		notes.appendChild( ol );
		ol.appendChild( document.createElement( "li" ) );
	
	//  Slow, but can be made to work in IE:
	//	makeBlockElementsLinkable( content, '' );
		
		var fragment = getNodeByFragmentPath( '' + window.location );
		if ( fragment )
		{
			scrollWindowToNode( fragment );
			addClass( fragment, AN_FLASH_CLASS );
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
	event = getEvent( event );
	stopPropagation( event );
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
		return hasClass( node, 'smart-copy' ) ? true : false;
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


/******** Functions for showing annotations for individual blocks ********/

function onAnnotationModeChange( event )
{
	var modeSelect = document.getElementById( 'annotation-mode' );
	if ( 'mine' == modeSelect.value )
		window.marginalia.showAnnotations( url, null );
	else
	{
		window.marginalia.hideAnnotations( );
		enablePerBlockAnnotations( );
	}
}


/**
 * Enable the ability for users to click on a block to retrieve its annotations
 */
function enablePerBlockAnnotations( )
{
	var postElements = getChildrenByTagClass( document.documentElement, null, PM_POST_CLASS, null, _skipPostContent );
	for ( var i = 0;  i < postElements.length;  ++i )
	{
		var post = getPostMicro( postElements[ i ] );
		var content = post.getContentElement( );
		addClass( content, AN_PERBLOCKANNOTATIONS_CLASS );
		content.addEventListener( 'click', _showAnnotationsForBlock, false );
	}
}

/**
 * Disable per-block annotations
 */
function disablePerBlockAnnotations( )
{
	var postElements = getChildrenByTagClass( document.documentElement, null, PM_POST_CLASS, null, _skipPostContent );
	for ( var i = 0;  i < postElements.length;  ++i )
	{
		var post = getPostMicro( postElements[ i ] );
		var content = post.getContentElement( );
		addClass( content, AN_PERBLOCKANNOTATIONS_CLASS );
		content.removeEventListener( 'click', _showAnnotationsForBlock, false );
	}
}

/**
 * When a user clicks in the content area, find the closest preceding block-level
 * element and shall all users' annotations for that block.
 */
function _showAnnotationsForBlock( event )
{
	window.marginalia.hideAnnotations( );
	event = getEvent( event );
	var target = getEventTarget( event );
	var walker = new DOMWalker( target );
	while ( ELEMENT_NODE != walker.node.nodeType || 'block' != htmlDisplayModel( target.tagName ) )
		walker.walk( _skipContent, WALK_REVERSE );
	var postNode = getParentByTagClass( walker.node, null, PM_POST_CLASS, false, null );
	if ( null != postNode )
	{
		var post = postNode.post;
		path = NodeToPath( post.contentElement, walker.node );
		window.marginalia.showAnnotations( post.url, path );
	}
}

