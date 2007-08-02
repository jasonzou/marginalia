/*
 * Annotation functions specific to discuss.php (the generic name is in case
 * non-annotation code ends up in here).
 *
 * I created this because of the pathetic state of IE.  It's understandable that Microsoft
 * would prefer to hobble a technology like the Web which presents such a challenge
 * to their desktop monopoly, but that's no excuse.
 */

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
 * This fixes the height of create annotation buttons.
 *
 * I used to have to do this for IE, and complained bitterly.  Now I have to do it for Firefox
 * and Safari too:  apparently you can't set a table cell child to be the full height of the cell.
 * Probably this is CSS compliant.  Why can't they make sane standards?
 *
 * Note:  if the annotated content changes length (e.g. because of many inserted links or edit
 * actions), the button won't resize to match.  Hmmm.
 */
function fixControlMarginIE( )
{
	var controlMargins = domutil.childrenByTagClass( document.documentElement, 'td', 'control-margin', null );
	for ( var i = 0;  i < controlMargins.length;  ++i )
	{
		var button = domutil.childByTagClass( controlMargins[ i ], 'button', null );
		button.style.height = '' + controlMargins[ i ].offsetHeight + 'px';
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

