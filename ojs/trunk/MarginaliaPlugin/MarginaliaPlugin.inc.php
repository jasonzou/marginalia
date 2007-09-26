<?php

/**
 * MarginaliaPlugin.inc.php
 *
 * Copyright (c) 2006 Geof Glass
 * Distributed under the GNU GPL v2, or a later version
 * For full terms see the file docs/COPYING.
 *
 * @package plugins
 *
 * Marginalia plugin to allow readers to annotate articles.
 *
 */
 
import('classes.plugins.GenericPlugin');

define('MARGINALIA_PATH', 'plugins/generic/MarginaliaPlugin/marginalia');
define('PLUGIN_PATH', 'plugins/generic/MarginaliaPlugin');

define( 'NS_PTR', 'http://www.geof.net/code/annotation/' );
define( 'NS_ATOM', 'http://www.w3.org/2005/Atom' );
define( 'NS_XHTML', 'http://www.w3.org/1999/xhtml' );

// If this is True, use the marginalia-all.js compiled file, rather than including
// many individual script files.  Should produce slightly faster initial page loads.
// False makes for easier debugging, however.
define( 'MARGINALIA_COMPILED_JS', False );

require_once( 'marginalia-php/embed.php' );


class MarginaliaPlugin extends GenericPlugin
{
	function register( $category, $path )
	{
		if ( ! Config::getVar( 'general', 'installed' ) )
			return false;
		
		if ( parent::register( $category, $path ) )
		{
			// DAORegistry::RegisterDAO( 'marginalia.AnnotationDAO', new AnnotationDAO() );
			
			$journal =& Request::getJournal();
			$journalId = $journal ? $journal->getJournalId() : 0;
			$isEnabled = $this->getSetting( $journalId, 'enabled' );

			$this->addLocaleData();
			
			if ( $this->isMarginaliaInstalled() && $isEnabled )
			{
				HookRegistry::register(
					'TemplateManager::display',
					array( &$this, 'injectMarginalia' ) );
				HookRegistry::register('LoadHandler', array(&$this, 'loadHandler'));
			}
			
			return true;
		}
		return false;
	}

	/**
	 * Get the filename of the ADODB schema for this plugin.
	 */
	function getInstallSchemaFile()
	{
		return $this->getPluginPath() . '/' . 'schema.xml';
	}

	/**
	 * Get the filename of the install data for this plugin.
	 */
	function getInstallDataFile()
	{
		return $this->getPluginPath() . '/' . 'data.xml';
	}
	
	function getEnabledTemplates()
	{
		// don't include article/view.tpl - it causes havoc when the frameset is
		// switched on for reading tools
		$enabledTemplates = array(
			'article/article.tpl'
		);
//		HookRegistry::call(
//			'TinyMCEPlugin::getDisableTemplates',
//			array( &$this, &$disableTemplates ) );
		return $enabledTemplates;
	}

	function loadHandler ( $hookName, $args )
	{
		$page =& $args[ 0 ];
		$op =& $args[ 1 ];
		$sourceFile =& $args[ 2 ];
		
		if ( 'marginalia' === $page )
		{
			$sourceFile = 'plugins/generic/MarginaliaPlugin/MarginaliaHandler.inc.php';
		}
		return false;
	}
	
	// Inject marginalia code into the display of a journal page
	function injectMarginalia( $hookName, $args )
	{
		$templateManager =& $args[ 0 ];
		$template =& $args[ 1 ];
		
		if ( in_array( $template, $this->getEnabledTemplates() ) )
		{
			$baseUrl = $templateManager->get_template_vars( 'baseUrl' );
			$additionalHeadData = $templateManager->get_template_vars( 'additionalHeadData' );

			$journal =& $templateManager->get_template_vars( 'currentJournal' ); "annovan"; //#geof# must fix
			$serviceUrl = $journal->getUrl( ) . '/marginalia';
			
			$user =& Request::getUser();
			if ( $user )
				$currentUser = "'".$user->getUsername()."'";
			else
				$currentUser = 'null';
			
			$head_html =
				"<link rel='stylesheet' type='text/css' href='".$baseUrl.'/'.PLUGIN_PATH.'/'."marginalia.css'/>\n"
				."<link rel='stylesheet' type='text/css' href='".$baseUrl.'/'.MARGINALIA_PATH.'/'."marginalia.css'/>\n"
				."<link rel='stylesheet' type='text/css' href='".$baseUrl.'/'.MARGINALIA_PATH.'/'."marginalia-direct.css'/>\n"
				."<script type='text/javascript' src='".$serviceUrl."/strings.js'></script>\n";

			if ( MARGINALIA_COMPILED_JS )
			{
				$head_html .= 
					"<script type='text/javascript' src='".$baseUrl.'/'.MARGINALIA_PATH."/marginalia-all.js'></script>\n"
					."<script type='text/javascript' src='".$baseUrl.'/'.PLUGIN_PATH."/ojs-annotate.js'></script>\n";
			}
			else
			{
				$marginaliaFiles = listMarginaliaJavascript( );
				foreach ( $marginaliaFiles as $name )
					$head_html .= "<script type='text/javascript' src='".$baseUrl.'/'.MARGINALIA_PATH.'/'.htmlspecialchars($name)."'></script>\n";
				$head_html .= "<script type='text/javascript' src='".$baseUrl.'/'.PLUGIN_PATH."/ojs-annotate.js'></script>\n";
			}
			$head_html .=
				"<script language='javascript' type='text/javascript'>\n"
				."  var serviceRoot = '".$serviceUrl."';\n"
				."	ojsAnnotationInit( serviceRoot, $currentUser );\n"
				."</script>\n";
			//."  var  serviceRoot = '".$baseUrl.'/index.php/'.$journalName.'/marginalia'."';\n"

			$templateManager->assign( 'additionalHeadData',
				$additionalHeadData."\n".$head_html );
		}
		return false;
	}
	
	
	function getName()
	{
		return 'MarginaliaPlugin';
	}

	
	function getDisplayName()
	{
		return Locale::translate( 'plugins.generic.marginalia.name' );
	}

	
	function getDescription()
	{
		if ( $this->isMarginaliaInstalled() )
			return Locale::translate( 'plugins.generic.marginalia.description' );
		else
			return Locale::translate( 'plugins.generic.marginalia.descriptionDisabled', array( 'marginaliaPath' => MARGINALIA_PATH ) );
	}

	
	function isMarginaliaInstalled()
	{
		return file_exists( MARGINALIA_PATH . '/annotation.js' );
	}

	
	function getManagementVerbs()
	{
		$journal =& Request::getJournal();
		$journalId = $journal ? $journal->getJournalId() : 0;
		$isEnabled = $this->getSetting( $journalId, 'enabled' );

		$verbs = array();
		if ( $this->isMarginaliaInstalled() )
		{
			$verbs[] = array(
				( $isEnabled ? 'disable' : 'enable' ),
				Locale::translate( $isEnabled ? 'manager.plugins.disable' : 'manager.plugins.enable' )
			);
		}
		return $verbs;
	}

	
	function manage( $verb, $args )
	{
		$journal =& Request::getJournal();
		$journalId = $journal ? $journal->getJournalId() : 0;
		$isEnabled = $this->getSetting( $journalId, 'enabled' );
		
		switch ($verb)
		{
			case 'enable':
				$this->updateSetting( $journalId, 'enabled', true );
				break;
			case 'disable':
				$this->updateSetting( $journalId, 'enabled', false );
				break;
		}
		return false;
	}
}
?>
