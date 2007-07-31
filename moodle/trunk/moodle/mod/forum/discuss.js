/*
 * Annotation functions specific to discuss.php (the generic name is in case
 * non-annotation code ends up in here).
 *
 * I created this because of the pathetic state of IE.  It's understandable that Microsoft
 * would prefer to hobble a technology like the Web which presents such a challenge
 * to their desktop monopoly, but that's no excuse.  As for the folks who use IE...
 * well, the said the better.
 */

UGLY_ANNOTATION_SERVICE_URL = '/annotate.php';

function DiscussMarginalia( )
{
	this.url = null;
	this.moodleRoot = null;
	this.username = null;
	this.anuser = null;
	this.showAnnotations = true;
	this.enableSmartcopy = false;
}

DiscussMarginalia.prototype.onload = function( )
{
	// Check whether this page should have annotations enabled at all
	// The check is here rather in the PHP;  that minimizes the number of patches
	// that need to be applied to existing Moodle code.
	var actualUrl = '' + window.location;
	if ( actualUrl.match( /^.*\/mod\/forum\/discuss\.php\?d=(\d+)/ ) )
	{
		var annotationService = new RestAnnotationService( this.moodleRoot + '/annotation' );
		var preferences = new Preferences( new RestPreferenceService( this.moodleRoot + '/annotation/user-preference.php' ) );
		var keywordService = new RestKeywordService( this.moodleRoot + '/annotation/keywords.txt' );
		keywordService.init( );
		marginaliaInit( annotationService, this.username, this.anuser, this.moodleRoot,
			preferences, keywordService );
		window.marginalia.linkUi = null;
		window.marginalia.setFeature( AN_BLOCKMARKER_FEAT, false );
		
		var url = this.url;
		if ( this.showAnnotations )
		{
			window.marginalia.showAnnotations( url );
			fixControlMarginIE();
		}
		
		smartcopyInit();
		if ( self.enableSmartcopy )
			smartcopyOn( );
		
		var marginaliaDirect = new MarginaliaDirect( annotationService );
		marginaliaDirect.init( );
		initLogging();
	}
}



/*
 * I think we all know what a pain IE is.  It doesn't correctly set the height of the
 * margin button, neither does it obey :hover on buttons.  So, if the browser
 * is IE it's necessary to fix the buttons.
 */
function fixControlMarginIE( )
{
	if ( 'exploder' == domutil.detectBrowser( ) )
	{
		var controlMargins = domutil.hildrenByTagClass( document.documentElement, 'td', 'control-margin', null );
		for ( var i = 0;  i < controlMargins.length;  ++i )
		{
			var button = domutil.childByTagClass( controlMargins[ i ], 'button', null );
			button.style.height = '' + controlMargins[ i ].offsetHeight + 'px';
		}
	}
}


function changeAnnotationUser( userControl, url )
{
	var marginalia = window.marginalia;
	var user = userControl.value;
	marginalia.hideAnnotations( );
	if ( null == user || '' == user )
		marginalia.preferences.setPreference( 'show_annotations', 'false', null);
	else
	{
		// This is a hack - the showAllAnnotations call should accept the user name also
		marginalia.username = user;
		marginalia.showAnnotations( url );
		marginalia.preferences.setPreference( 'show_annotations', 'true', null);
		marginalia.preferences.setPreference( 'annotation_user', user, null );
	}
}

function myCreateAnnotation( event, postId )
{
	event.stopPropagation( );
	createAnnotation( postId, true );
}

function _skipContent( node )
{
	return _skipSmartcopy( node ) || _skipAnnotationLinks( node ) || _skipAnnotationActions( node );
}

