<?php

require_once( "marginalia-php/MarginaliaHelper.php" );
require_once( "marginalia-php/AnnotationService.php" );

define('HANDLER_CLASS', 'MarginaliaHandler');

error_reporting( E_ALL );

class MarginaliaHandler extends Handler
{
	function index( $args )
	{
		echo "MarginaliaHandler INDEX\n";
	}
	
	function stringsjs( $args )
	{
		getMarginaliaStringsJS( );
	}
	
	function keywords( $args )
	{
		$method = $_SERVER[ 'REQUEST_METHOD' ];
		switch ( $method )
		{
			case 'GET':
				$keywordsDao = new KeywordDAO( );
				$keywords =& $keywordsDao->getKeywords( );
				// There are two obvious formats for this data:
				// * an HTML ul
				// * an HTML select
				// I think a ul would be a bit better, but in practice a select
				// is very handy.  I may change it someday, however.
				header( 'Content-type: text/plain' );
				for ( $i = 0;  $i < count( $keywords );  ++$i )
				{
					$keyword = $keywords[ $i ];
					if ( $i > 0 )
						echo "\n";
					echo $keyword->getName( ) . ':' . $keyword->getDescription( );
				}
				break;
		}
	}
	
	function preference( $args )
	{
		// Preference methods automatically refer only to preferences
		// belonging to the current user.
		$method = $_SERVER[ 'REQUEST_METHOD' ];
		switch ( $method )
		{
			case 'GET':
				$name = getUserGetVar( 'name' );
				if ( $name )
				{
					$preferenceDao = new PreferenceDAO( );
					$preference =& $preferenceDao->getPreference( $name );
					if ( null == $preference )
					{
						header( 'HTTP/1.1 404 Not Found' );
						echo '<h1>404 Not Found</h1><p>No such preference</p>';
					}
					else
					{
						header( 'Content-type: text/plain' );
						echo $preference->getPreferenceName( ).':'.$preference->getPreferenceValue( );
					}
				}
				else
				{
					$preferenceDao = new PreferenceDAO( );
					$preferences =& $preferenceDao->getPreferences( );
					header( 'Content-type: text/plain' );
					$first = true;
					foreach ( $preferences as $pref )
					{
						if ( ! $first )
							echo "\n";
						else
							$first = false;
						echo $pref->getPreferenceName( ).':'.$pref->getPreferenceValue( );
					}
				}	
				break;
				
			case 'POST':
				$name = getUserGetVar( 'name' );
				$value = getUserPostVar( 'value' );
				if ( null == $name || null == $value )
				{
					header( 'HTTP/1.1 400 Bad Request' );
					echo "<h1>400 Bad Request</h1><p>Must specify name and vaule parameters</p>";
				}
				else
				{
					if ( 'annotations.show' == $name 
						|| 'annotations.show-user' == $name
						|| 'annotations.note-edit-mode' == $name)
					{
						$preference = new Preference( );
						$preference->setPreferenceName( $name );
						$preference->setPreferenceValue( $value );
						$preferenceDao = new PreferenceDAO( );
						if ( null == $preferenceDao->getPreference( $name ) )
							$preferenceDao->insertPreference( $preference );
						else
							$preferenceDao->updatePreference( $preference );
						header( 'HTTP/1.1 204 Preference Set' );
					}
					else
					{
						header( 'HTTP/1.1 404 Invalid Preference' );
						echo "<h1>404 Invalid Preference</h1><p>The preference name you specificed is not valid.</p>";
					}
				}
				break;
		}
	}
	
	function annotate( $args )
	{
		$annotationService = new OjsAnnotationService( );
		$annotationService->dispatch( );
	}
}


class OjsAnnotationService extends AnnotationService
{
	var $annotationDao;
	
	function OjsAnnotationService( )
	{
		$servicePath = Request::getRequestUrl();
		$host = Request::getServerHost();

		// Get install date.  Seems to produce 1969-12-31
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$versions =& $versionDao->getVersionHistory();
		$firstVersion = array_pop($versions);
		$installDate = $firstVersion->getDateInstalled();
		$installDate = strtotime( $installDate );

		$username = Request::getUser();
		if ( $username )
			$username = $username->getUsername( );

		AnnotationService::AnnotationService( $host, $servicePath, $installDate, $username );
	}

	function newAnnotation( )
	{
		return new Annotation( );
	}
	
	function beginRequest( )
	{
		$this->annotationDao = new AnnotationDao( );
		return True;
	}
	
	function endRequest( )
	{ ; }
		
	function doListAnnotations( $url, $username, $block )
	{
		// Fetch all visible annotations.  Requires block parameter.
//		if ( null != $block )
		return $this->annotationDao->getVisibleAnnotationsByUrlUserBlock( $url, $username, $block );
		// Fetch annotations for a particular user (or all users).
//		else
//			return $this->annotationDao->getVisibleAnnotationsByUrlUser( $url, $block, $username );
	}
	
	function doGetAnnotation( $id )
	{
		return $this->annotationDao->getAnnotation( $id );
	}
	
	function doCreateAnnotation( $annotation )
	{
		return $this->annotationDao->insertAnnotation( $annotation );
	}
	
	function doUpdateAnnotation( $annotation )
	{
		// remember, PUT is idempotent - the result of one update or multiple
		// identical updates should be the same
		// A user can only update his/her own annotations
		return $this->annotationDao->updateAnnotation( $annotation );
	}
	
	function doDeleteAnnotation( $id )
	{
		return $this->annotationDao->deleteAnnotationById( $id );
	}
}

	
/* I would ideally like to have these functions in the Request class, but I don't
 * want to require changes to the standard OJS install.  It's true I could just
 * use getUserVar, but then I would lose the specificity of using GET and POST
 * parameters as intended:  the former for identifying a resource or part of a
 * resource, the latter for specifying data and actions.  I'm a great believer in
 * fail-fast, so I don't want to discard the distinction.
 */
 
/**
 * Get the value of a GET variable.
 * @return mixed
 */
function getUserGetVar($key) {
	if ( isset( $_GET[ $key ] ) )
	{
		$var = $_GET[ $key ];
		Request::cleanUserVar( $var );
		return $var;
	}
	else
		return null;
}


/**
 * Get the value of a POST variable.
 * @return mixed
 */
function getUserPostVar($key) {
	if ( isset( $_POST[ $key ] ) )
	{
		$var = $_POST[ $key ];
		Request::cleanUserVar( $var );
		return $var;
	}
	else
		return null;
}


function getUserArrayVar( $key, $a )
{
	if ( isset( $a[ $key ] ) )
	{
		$var = $a[ $key ];
		Request::cleanUserVar( $var );
		return $var;
	}
	else
		return null;
}

/**
 * Check whether an untrusted URL is safe for insertion in a page
 * In particular, javascript: urls can be used for XSS attacks
 */
function is_safe_url( $url )
{
	$urlParts = parse_url( $url );
	$scheme = $urlParts[ 'scheme' ];
	if ( 'http' == $scheme || 'https' == $scheme || '' == $scheme )
		return true;
	else
		return false;
}

?>
