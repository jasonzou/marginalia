<?php

// Where to find annotation source files, both as a URL and as a directory
define( 'ANNOTATION_PATH', $CFG->wwwroot.'/local/annotation' );
define( 'ANNOTATION_DIR', $CFG->dirroot.'/local/annotation' );

// Enable tag (keyword) autocomplete
define( 'AN_USEKEYWORDS', true );

// Enable smartquoting
define( 'AN_USESMARTQUOTE', true );

// Allow the admin user to update broken ranges
// (i.e. not to modify user data, which feature the UI should never provide,
// but the user has permission to perform administrative updates to it)
define( 'AN_ADMINUPDATE', true );

// Allow an admin user to view everyone's annotations, regardless of access level
// This is useful for research and for retrieving the annotations for backup
// without having to query the database directly.
define( 'AN_ADMINVIEWALL', true );

// Show summary column headings at the top of each section, rather than at
// the bottom of the whole table.
define( 'AN_SUMMARYHEADINGSTOP', true );


// The smartquote icon symbol(s)
define( 'AN_SMARTQUOTEICON', '\u275b\u275c' );	// 267a: recycle

// The same thing as entities because - and this stuns the hell out of me every
// single time - PHP 5 *does not have native unicode support*!!!  Geez guys,
// I remember reading about unicode in Byte Magazine in what, the 1980s?
define( 'AN_SMARTQUOTEICON_HTML', '&#10075;&#10076;' );

// Icon for filtering on the summary page
define( 'AN_FILTERICON_HTML', '&#9754;' );  //&#9756;
	
?>
