/*
 * Annotation functions specific to discuss.php (the generic name is in case
 * non-annotation code ends up in here).
 *
 * I created this because of the pathetic state of IE.  It's understandable that Microsoft
 * would prefer to hobble a technology like the Web which presents such a challenge
 * to their desktop monopoly, but that's no excuse.
 */

function DiscussMarginalia( url, moodleRoot, username, params )
{
	this.url = url;
	this.moodleRoot = moodleRoot;
	this.username = username;
	this.anuser = username;
	this.showAnnotations = true;
	this.enableSmartcopy = false;
	this.splash = null;
	
	for ( var name in params )
	{
		var value = params[ name ]; 
		switch ( name )
		{
			case 'anuser':
				this.anuser = value;
				break;
			case 'showAnnotations':
				this.showAnnotations = value;
				break;
			case 'enableSmartcopy':
				this.enableSmartcopy = value;
				break;
			case 'splash':
				this.splash = value;
				break;
		}
	}
}

DiscussMarginalia.prototype.onload = function( )
{
	// Check whether this page should have annotations enabled at all
	// The check is here rather in the PHP;  that minimizes the number of patches
	// that need to be applied to existing Moodle code.
	var actualUrl = '' + window.location;
	if ( actualUrl.match( /^.*\/mod\/forum\/discuss\.php\?d=(\d+)/ ) )
	{
		var annotationService = new RestAnnotationService( this.moodleRoot + '/annotation/annotate.php' );
		var preferences = new Preferences( new RestPreferenceService( this.moodleRoot + '/annotation/user-preference.php' ) );
		//var keywordService = new RestKeywordService( this.moodleRoot + '/annotation/keywords.txt' );
		//keywordService.init( );
		window.marginalia = new Marginalia( annotationService, this.username, this.anuser, {
			preferences: preferences,
			keywordService: null,
			linkUi:  null,
			baseUrl:  this.moodleRoot,
			showAccess:  true,
			showBlockMarkers:  false,
			showActions:  false,
			onkeyCreate:  true,
			skipContent: _skipSmartcopy
		} );
		
		var url = this.url;
		if ( this.showAnnotations )
		{
			window.marginalia.showAnnotations( url );
			this.fixControlMarginIE();
		}
		
		smartcopyInit();
		if ( this.enableSmartcopy )
			smartcopyOn( );
		
		var marginaliaDirect = new MarginaliaDirect( annotationService );
		marginaliaDirect.init( );
		initLogging();
		
		if ( this.splash )
			this.showSplash( );
	}
}


DiscussMarginalia.prototype.createAnnotation = function( event, postId )
{
	this.hideSplash( );
	delete this.splash;
	window.marginalia.preferences.setPreference( 'annotations.splash', 'false', null);
	clickCreateAnnotation( event, postId );
}

DiscussMarginalia.prototype.showSplash = function( )
{
	var noteMargins = cssQuery( '.hentry .notes div' );
	if ( noteMargins.length > 0 )
	{
		var margin = noteMargins[ 0 ];
		margin.appendChild( domutil.element( 'p', {
			className: 'splash',
			content: this.splash } ) );
	}
}

DiscussMarginalia.prototype.hideSplash = function( )
{
	var splash = cssQuery( '.hentry .notes div .splash' );
	splash.parentNode.removeChild( splash );
}


/*
 * This fixes the height of create annotation buttons.
 *
 * I used to have to do this for IE, and complained bitterly.  Now I have to do it for Firefox
 * and Safari too:  apparently you can't set a table cell child to be the full height of the cell.
 * Probably this is CSS compliant.  Why can't they make sane standards?
 *
 * Note:  if the annotated content changes length (e.g. because of many inserted links or edit
 * actions), the button won't resize to match.  Hmmm.
 */
DiscussMarginalia.prototype.fixControlMarginIE = function( )
{
	var controlMargins = domutil.childrenByTagClass( document.documentElement, 'td', 'control-margin', null );
	for ( var i = 0;  i < controlMargins.length;  ++i )
	{
		var button = domutil.childByTagClass( controlMargins[ i ], 'button', null );
		button.style.height = '' + controlMargins[ i ].offsetHeight + 'px';
	}
}


DiscussMarginalia.prototype.changeAnnotationUser = function( userControl, url )
{
	var marginalia = window.marginalia;
	var user = userControl.value;
	this.hideSplash( )
	marginalia.hideAnnotations( );
	if ( null == user || '' == user )
		marginalia.preferences.setPreference( 'show_annotations', 'false', null);
	else
	{
		marginalia.username = user;
		marginalia.showAnnotations( url );
		marginalia.preferences.setPreference( 'show_annotations', 'true', null);
		marginalia.preferences.setPreference( 'annotation_user', user, null );
		if ( this.splash && marginalia.username == marginalia.anuser )
			this.showSplash( );
	}
}

