<?php 

/**
 * index.php
 *
 * Copyright (c) 2003-2006 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Wrapper for Marginalia plugin.
 *
 */

error_reporting( E_ALL );

define( 'DEBUG_ANNOTATION_QUERY', false );	// will break GET operations by emitting query string

require( 'marginalia-php/helper.php' );
require( 'marginalia-php/block-range.php' );
require( 'marginalia-php/xpath-range.php' );
require( 'MarginaliaPlugin.inc.php' );
require( 'Annotation.inc.php' );
require( 'AnnotationDAO.inc.php' );
require( 'Keyword.inc.php' );
require( 'KeywordDAO.inc.php' );
require( 'Preference.inc.php' );
require( 'PreferenceDAO.inc.php' );
require( 'MarginaliaStrings.inc.php' );
return new MarginaliaPlugin();

?> 
