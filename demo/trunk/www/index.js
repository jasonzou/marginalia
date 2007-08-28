/*
	The myCreateAnnotation function is essential because it must stop propagation of
	the button click that creates the annotation.  Otherwise, that click would be
	interpreted as a click outside the text edit for the annotation, in which case
	the annotation would be saved immediately without editing.  Note that this does
	*not* need to be called called when an annotation is created by typing Enter.
	
	The _skipContent callback must be defined for Marginalia to function (Marginalia
	callback function names begin with underscores).  It needs to be able to test an 
	HTML element to find out whether or not that node's contents should be included 
	when counting words and so on.  The _skipAnnotationLinks test should always be 
	performed if you're allowing annotations to include links;  _skipSmartcopy is 
	needed if Smartcopy is on.  If there are other reasons for other stuff in the
	content area (if, for example, you had actual annotation notes there), they 
	should be skipped too.
	
	marginaliaInit takes four parameters:
	1. The base URL of the application.  The annotation directory should be beneath 
	   this (where exactly depends on how you've set up your service calls in 
	   rest-annotate.js;  since this demo uses static-annotate.js instead it doesn't
	   really apply).  In the case of Moodle, this is the URL of the Moodle directory. 
	2. The name of the current user.  In Moodle this is the "username" field.
	3. The name of the user whose annotations are to be shown.
	4. The portion of the URL to strip.  If null, the database will store absolute URLs.
	   Otherwise, this can be the URL preceding the path (e.g. http://my.host.name), in
	   which case that portion of the URL is not stored (very useful if the host name
	   might change - otherwise, moving to a new server would require a search and replace
	   on annotated URLs in the database).  This should be more flexible and allow more 
	   of the path to be stripped;  for now those are the two choices.
	   
	The preferences object is required for load and storing user preferences, such as
	whether the user last created an annotation using a keywords drop-down or a text
	entry field.  The current implementation is a static dummy.
	  
	Keywords initialization is needed for the keywords drop-down.  It is called now to
	fetch the list of keywords so they will be ready when needed.
	  
	If you don't call smartcopyInit() and smartCopyOn(), smartcopy will not be enabled.
*/

function demoOnLoad( serviceRoot, queryUrl )
{
	var annotationService = new RestAnnotationService( serviceRoot + '/annotate.php', false );
	var keywordService = new RestKeywordService( serviceRoot + '/keywords.txt');
	keywordService.init( );
	window.marginalia = new Marginalia( annotationService, 'anonymous', 'anonymous', {
		preferences: new Preferences( new StaticPreferenceService( ) ),
		keywordService: new RestKeywordService( serviceRoot + '/keywords.txt' ),
		linkUi:  new ClickToLinkUi( true ),
		baseUrl:  null,
		showAccess:  true,
		showBlockMarkers:  true,
		showActions:  true,
		onkeyCreate:  true,
		skipContent: _skipSmartcopy
	} );
	
//	smartcopyInit( );
//	smartcopyOn( );
	
	var marginaliaDirect = new MarginaliaDirect( annotationService );
	marginaliaDirect.init( );
	window.marginalia.showAnnotations( queryUrl );
}

