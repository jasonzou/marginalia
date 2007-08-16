<?php

    require_once("../config.php");
	
	$url = $_SERVER[ 'REQUEST_URI' ];
	
	$prefName = array_key_exists( 'name', $_GET ) ? $_GET[ 'name' ] : null;

	switch ( $_SERVER[ 'REQUEST_METHOD' ] )
	{
		case 'GET':
			header( 'Content-type: application/xml' );
			// should be utf-8
			echo "<?xml version='1.0'?>\n";
			echo "<preferences>\n";
			echo " <setting url='$url' name='" . htmlspecialchars( $prefName ) . "'>" . htmlspecialchars( get_user_preferences( $prefName, '' ) ) . "</setting>\n";
			echo "</preferences>";
			break;
		
		case 'POST':
			// Check that it exists first so that malicious users can't fill the database
			// with meaningless preferences.
			if ( get_user_preferences( $prefName, null ) == null )
				header( 'HTTP/1.1 403 Forbidden' );
			else
			{
				$value = $_POST[ 'value' ];
				set_user_preference( $prefName, $value);
				header( 'HTTP/1.1 204 Preference Set' );
				$prefs = get_user_preferences( $prefName, null );
			}
			break;
		
		default:
			header( 'HTTP/1.1 400 Bad Request' );
	}
?>
