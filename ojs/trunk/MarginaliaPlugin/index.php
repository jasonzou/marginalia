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

require_once( 'marginalia-php/MarginaliaHelper.php' );
require_once( 'marginalia-php/SequenceRange.php' );
require_once( 'marginalia-php/XPathRange.php' );
require_once( 'MarginaliaPlugin.inc.php' );
require_once( 'Annotation.inc.php' );
require_once( 'AnnotationDAO.inc.php' );
require_once( 'Keyword.inc.php' );
require_once( 'KeywordDAO.inc.php' );
require_once( 'Preference.inc.php' );
require_once( 'PreferenceDAO.inc.php' );
require_once( 'MarginaliaStrings.inc.php' );
return new MarginaliaPlugin();

?> 
