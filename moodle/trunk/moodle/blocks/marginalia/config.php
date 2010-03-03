<?php

// Where to find annotation source files, both as a URL and as a directory
define( 'ANNOTATION_PATH', $CFG->wwwroot.'/blocks/marginalia' );
define( 'ANNOTATION_DIR', $CFG->dirroot.'/blocks/marginalia' );

// When this is true, any access to annotations (including fetching the Atom feed) requires a valid user
// When false, anyone on the Web can retrieve public annotations via an Atom feed
define( 'ANNOTATION_REQUIRE_USER', false );

define( 'MAX_NOTE_LENGTH', 250 );		// should not exceed 255 due to DB limitations
define( 'MAX_QUOTE_LENGTH', 1000 );

// Enable tag (keyword) autocomplete
define( 'AN_USEKEYWORDS', true );

// Enable smartquoting
define( 'AN_USESMARTQUOTE', true );

// Enable activity logging
// The activity log (activity_log.php) is useful for research purposes.  It provides
// a complete log of all marginalia activity, including every version of an annotation
// as it changes over time.  To view the log, grant the block/marginalia:view_log
// capability to a user, who will then see the Activity Log option in the annotation
// drop-down menu.
define( 'AN_USELOGGING', true );

// Allow the admin user to update broken ranges
// (i.e. not to modify user data, which feature the UI should never provide,
// but the user has permission to perform administrative updates to it)
define( 'AN_ADMINUPDATE', true );

// Allow an admin user to view everyone's annotations, regardless of access level
// This is useful for research and for retrieving the annotations for backup
// without having to query the database directly.  This capability is being
// replaced by the activity log.  See AN_USELOGGING above.
define( 'AN_ADMINVIEWALL', true );

// Show summary column headings at the top of each section, rather than at
// the bottom of the whole table.
define( 'AN_SUMMARYHEADINGSTOP', false );

// Replace \' with ' and \" with " in existing annotations when upgrading the db.
// The backslashes were introduced by a bug in the code, and resulted in
// broken annotations.  This has since been fixed.  This does introduce the
// possibility that backslashes might be removed when they shouldn't, so this
// can be switched off.  (I had thought I fixed this long ago Magicquotes how I 
// hate thee.)
define( 'AN_DBFIXBACKSLASHES', true );

?>
