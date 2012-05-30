
/*
 * Languages for annotation Javascript
 */

/*
 * Fetch a localized string
 * This is a function so that it can be replaced with another source of strings if desired
 * (e.g. in a database).  The application uses short English-language strings as keys, so
 * that if the language source is lacking the key can be returned instead.
 */
function getLocalized( s )
{
	return LocalizedAnnotationStrings[ s ];
}

LocalizedAnnotationStrings = {

	
	'public annotation' : 'This annotation is public.',
	
	'private annotation' : 'This annotation is private.',
		
	'delete annotation button' : 'Delete this annotation.',
	
	'annotation link button' : 'Link to another document.',
	
	'annotation link label' : 'Select a document to link to.',
	
	'delete annotation link button' : 'Remove this link.',
	
	'annotation expand edit button' : 'Click to display margin note editor',
	
	'annotation collapse edit button' : 'Click to display margin note drop-down list',

	'annotation quote button' : 'Quote this annotation in a discussion post.',
	
	
	
	'browser support of W3C range required for annotation creation' : 'Your browser does not support the W3C range standard, so you cannot create annotations.',
	
	'select text to annotate' : 'You must select some text to annotate.',
	
	'invalid selection' : 'Selection range is not valid.',
	
	'corrupt XML from service' : 'An attempt to retrieve annotations from the server returned corrupt XML data.',
	
	'note too long' : 'Please limit your margin note to 250 characters.',
	
	'quote too long' : 'The passage you have attempted to highlight is too long.  It may not exceed 1000 characters.',
	
	'zero length quote' : 'You must select some text to annotate.',
	
	'quote not found' : 'The highlighted passage could not be found',
	
	'create overlapping edits' : 'You may not create overlapping edits',

	'lang' : 'en'
};
