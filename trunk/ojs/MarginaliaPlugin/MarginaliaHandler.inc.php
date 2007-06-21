<?php

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
		$method = $_SERVER[ 'REQUEST_METHOD' ];
		switch ( $method )
		{
			case 'GET':
				// Fetching annotations is permitted even by anonymous users.
				// The relevant DAO function filters the list according to permissions.
				$format = getUserGetVar( 'format ' );
				$url = getUserGetVar( 'url' );
				$username = getUserGetVar( 'user' );
				// TODO: Implement block and point parameters supporting xpath and block paths
				$point = null;
//				$point = getUserGetVar( 'point' );
//				$point = $point ? new WordPoint( $point ) : null;
				$format = null == $format ? 'atom' : $format;
				// Can't sanitize $username - it might contain a single quote, e.g. for some French names starting with d',
				// or some romanization of other languages, e.g. the old romanization of Mandarin
				if ( $url == null || $url == '' || !sanitize( $url ) )
				{
					header( 'HTTP/1.1 400 Bad Request' );
					echo "<h1>400 Bad Request</h1>Bad URL";
				}
				else if ( null == $format || 'atom' == $format )
				{
					// Fetch all visible annotations
					// this requires the block parameter
					if ( null != $point )
					{
						$annotationDao = new AnnotationDao( );
						$annotations =& $annotationDao->getVisibleAnnotationsByUrlPoint( $url, $point, $username );
						MarginaliaHandler::getAtom( $annotations );
					}
					// Fetch annotations for a particular user
					// this ignores the block parameter
					elseif ( null != $username )
					{
						//$annotationDao = &DAORegistry::getDAO( 'marginalia.AnnotationDao' );
						$annotationDao = new AnnotationDao( );
						$annotations =& $annotationDao->getVisibleAnnotationsByUrlUser( $url, $username );
						MarginaliaHandler::getAtom( $annotations );
					}
					else
					{
						header( 'HTTP/1.1 400 Bad Request' );
						echo "<h1>400 Bad Request</h1>Must specify user or point";
					}
				}
				else
				{
					header( 'HTTP/1.1 400 Bad Request' );
					echo "<h1>400 Bad Request</h1>Unknown format";
				}
				return true;
				break;
			
			// create a new annotation
			case 'POST':
				// Must be a logged-in user
				$user =& Request::getUser();
				if ( ! $user )
				{
					header( 'HTTP/1.1 403 Forbidden' );
					echo "<h1>403 Forbidden</h1>Must be logged in";
				}
				else
				{
					// Strip magicquotes if necessary
					$params = array();
					foreach ( array_keys( $_POST ) as $param )
						$params[ $param ] = unfix_quotes( $_POST[ $param ] );
		
					$annotation = new Annotation();
					$currentUser = Request::getUser();
					$annotation->setUserId( $currentUser->getUsername( ) );
					$error = MarginaliaHelper::annotationFromParams( $annotation, $params );
					
					if ( $error )
					{
						header( 'http/1.1 '.MarginaliaHelper::httpResultCodeForError( $error ) );
						echo '<h1>'.MarginaliaHelper::httpResultCodeForError( $error )."</h1>\n";
						echo "</p>".htmlspecialchars($error)."</p>";
					}
					else
					{
						$xpathRange = $annotation->getXPathRange( );
						$annotationDao = new AnnotationDao( );
						$id = $annotationDao->insertAnnotation( $annotation );
						if ( $id != 0 )
						{
							$servicePath = Request::getRequestUrl();
							header( 'HTTP/1.1 201 Created' );
							header( "Location: $servicePath/$id" );
						}
						else
						{
							header( 'HTTP/1.1 500 Internal server error' );
							echo "<h1>500 Internal Server Error</h1>Create failed";
						}
					}
				}
				break;
			
			// update an existing annotation
			case 'PUT':
				// Must be a logged-in user
				$user =& Request::getUser();
				if ( ! $user )
				{
					header( 'HTTP/1.1 403 Forbidden' );
					echo "<h1>403 Forbidden</h1>Must be logged in";
				}
				
				// The ID Is part of the URL identifying this annotation (ideally it
				// would not be in the query string, but what can you do)
				$params = array( );
				$id = getUserGetVar( 'id', $params );
				
				if ( null != $id )
					$id = (int) (0 + $id);
				if ( null == $id || '' == $id || 0 == $id )
				{
					header( 'HTTP/1.1 400 Bad Request' );
					echo "<h1>Bad Request</h1>No such annotation ".htmlspecialchars($id);
				}
				else
				{
					// Now for some joy.  PHP isn't clever enough to populate $_POST if the
					// Content-Type is application/x-www-form-urlencoded - it only does
					// that if the request method is POST.  Bleargh.
					// Plus, how do I ensure the charset is respected correctly?  Hmph.
					
					// Should fail if not Content-Type: application/x-www-form-urlencoded; charset: UTF-8
					$fp = fopen( 'php://input', 'rb' );
					$urlencoded = '';
					while ( $data = fread( $fp, 1024 ) )
						$urlencoded .= $data;
					parse_str( $urlencoded, $params );
					
					// remember, PUT is idempotent - the result of one update or multiple
					// identical updates should be the same
					$annotationDao = new AnnotationDao( );
					$annotation = $annotationDao->getAnnotation( $id );
					$error = MarginaliaHelper::annotationFromParams( $annotation, $params );
					
					if ( $error )
					{
						header( 'http/1.1 '.MarginaliaHelper::httpResultCodeForError( $error ) );
						echo '<h1>'.MarginaliaHelper::httpResultCodeForError( $error )."</h1>\n";
						echo "</p>$error</p>";
					}
					// A user can only update his/her own annotations
					elseif ( $annotation &&  $user->getUsername() == $annotation->getUserId() )
					{
						$annotationDao->updateAnnotation( $annotation );
						header( 'HTTP/1.1 204 Updated' );
					}
					else
					{
						header( 'HTTP/1.1 403 Forbidden' );
					}
				}
				break;
			
			case 'DELETE':
				// Must be logged in to create or update annotations
				$user =& Request::getUser();
				$id = getUserGetVar( 'id' );
				if ( ! $user )
				{
					header( 'HTTP/1.1 403 Forbidden' );
					echo "<h1>403 Forbidden</h1>Must be logged in";
				}
				elseif ( null != $id )
				{
					$id = (int) $id;
					if ( $id == 0 || $id == '' )
					{
						header( 'HTTP/1.1 400 Bad Request' );
						echo "<h1>400 Bad Request</h1>Bad ID";
					}
					else
					{
						$annotationDao = new AnnotationDao( );
						$annotation = $annotationDao->getAnnotation( $id );
						
						// A user can only delete his/her own annotations
						if ( $annotation && $user->getUsername() == $annotation->getUserId() )
						{
							$annotationDao->deleteAnnotationById( $id );
							header( "HTTP/1.1 204 Deleted" );
						}
						else
						{
							header( "HTTP/1.1 403 Forbidden" );
						}
					}
				}
				else 
				{
					header( 'HTTP/1.1 400 Bad Request' );
					echo "<h1>400 Bad Request</h1>Missing ID";
				}
				break;
		}
	}
	
	function getAtom( &$annotations )
	{
		$servicePath = Request::getRequestUrl();
		$host = Request::getServerHost();
		
		// Get install date.  Seems to produce 1969-12-31
		$versionDao =& DAORegistry::getDAO('VersionDAO');
		$versions =& $versionDao->getVersionHistory();
		$firstVersion = array_pop($versions);
		$installDate = $firstVersion->getDateInstalled();
		$installDate = strtotime( $installDate );
		
		// Calculate last update date
		$feedLastModified = $installDate;
		foreach ( $annotations as $annotation )
		{
			$modified = strtotime( $annotation->getModified() );
			if ( $modified > $feedLastModified )
				$feedLastModified = $modified;
		}
		
		header( 'Content-Type: application/xml' );
		echo( '<?xml version="1.0" encoding="utf-8"?>' . "\n" );
		
		// About the feed ----
		echo "<feed xmlns:ptr='".NS_PTR."' xmlns='".NS_ATOM."' ptr:annotation-version='0.4'>\n";
		// This would be the link to the summary page:
		echo " <link rel='self' type='text/html' href='" . htmlspecialchars( $servicePath ) . "'/>\n";
		echo " <updated>" . date( 'Y-m-d', $feedLastModified ) . 'T' . date( 'HiO', $feedLastModified ) . "</updated>\n";
		echo " <title>OJS Annotations</title>";
		echo " <id>tag:" . $host . ',' . date( 'Y-m-d', $installDate ) . ":marginalia/annotate</id>\n";
		
		foreach ( $annotations as $annotation )
		{
			echo $annotation->toAtom( $host, $servicePath );
		}
		echo "</feed>\n";
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

// Yeah, gotta love the mess that is PHP
function unfix_quotes( $value )
{
	return get_magic_quotes_gpc( ) != 1 ? $value : stripslashes( $value );
}

				
// Frankly, I don't trust PHP's magic quotes.  The most dangerous characters,
// quote ('), semicolon (;), and less-than (<) aren't valid for most parameters anyway, so I'll
// screen them out just to be sure.  #GEOF#
function sanitize( $field )
{
	if ( false !== String::strpos( $field, "'" ) )
		return false;
	elseif ( false !== String::strpos( $field, ';' ) )
		return false;
	elseif ( false !== String::strpos( $field, '<' )  )
		return false;
	return true;
}

?>
