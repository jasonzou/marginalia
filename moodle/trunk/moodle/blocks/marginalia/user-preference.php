<?php

/*
 * user-preference.php
 * Gets and sets preferences.  Used by Marginalia.
 *
 * Marginalia has been developed with funding and support from
 * BC Campus, Simon Fraser University, and the Government of
 * Canada, the UNDESA Africa i-Parliaments Action Plan, and  
 * units and individuals within those organizations.  Many 
 * thanks to all of them.  See CREDITS.html for details.
 * Copyright (C) 2005-2007 Geoffrey Glass; the United Nations
 * http://www.geof.net/code/annotation
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 * $Id$
 */
 
    require_once("../../config.php");
    require_once( 'lib.php' );
	
    global $USER;
    
	$url = $_SERVER[ 'REQUEST_URI' ];
	
	$prefname = array_key_exists( 'name', $_GET ) ? $_GET[ 'name' ] : null;

	switch ( $_SERVER[ 'REQUEST_METHOD' ] )
	{
		case 'GET':
			$value = get_user_preferences( $prefname, null );
			header( 'Content-type: application/xml' );
			// should be utf-8
			echo "<?xml version='1.0'?>\n";
			echo "<preferences>\n";
			if ( null !== $value )
				echo " <setting url='$url' name='" . htmlspecialchars( $prefname ) . "'>" . htmlspecialchars( $value ) . "</setting>\n";
			else
				echo " <setting url='$url' name='" . htmlspecialchars( $prefname ) . "'/>\n";
			echo "</preferences>";
			break;
		
		case 'POST':
			// Check that it exists first so that malicious users can't fill the database
			// with meaningless preferences.
			if ( get_user_preferences( $prefname, null ) == null )
				header( 'HTTP/1.1 403 Forbidden' );
			else
			{
				$value = $_POST[ 'value' ];
				set_user_preference( $prefname, $value);
				header( 'HTTP/1.1 204 Preference Set' );
				$prefs = get_user_preferences( $prefname, null );
//				echo htmlspecialchars( $prefName ) . '=' . htmlspecialchars( $value );

				// Marginalia logging
				if ( AN_LOGGING )
				{
					$event = new object( );
					$event->userid = $USER->id;
					$event->service = 'preference';
					$event->action = 'set';
					$event->description = "$prefname=$value";
					$event->modified = time( );
					insert_record( AN_EVENTLOG_TABLE, $event, true );
				}
			}
			break;
		
		default:
			header( 'HTTP/1.1 400 Bad Request' );
	}

