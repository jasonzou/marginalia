<?php

class PreferenceDAO extends DAO
{
	// var $preferenceDAO;

	/**
	 * Constructor.
	 */
	function PreferenceDAO()
	{
		parent::DAO();
	//	$this->preferenceDAO = &DAORegistry::getDAO( 'marginalia.PreferenceDAO' );
	}
	
	/**
	 * Retrieve preference by name for the current user.
	 * @param $name string
	 * @return Preference
	 */
	function &getPreference( $name )
	{
		$currentUser = Request::getUser();
		$returner = null;
		
		// Only fetch annotations visible to the current user
		if ( $currentUser )
		{
			$result = &$this->retrieve(
				'SELECT * FROM preferences WHERE name=? AND user=?',
				array(
					$name,
					$currentUser->getUserId()
				)
				);
	
			if ( $result->RecordCount() != 0 )
				$returner = &$this->_returnPreferenceFromRow( $result->GetRowAssoc( false ) );
	
			$result->Close( );
			unset( $result );
	
			return $returner;
		}
	}

	/**
	 * Internal function to return a Preference object from a row.
	 * @param $row array
	 * @return Preference
	 */
	function &_returnPreferenceFromRow( &$row )
	{
		$preference = &new Preference( );
		$this->_preferenceFromRow( $preference, $row );
		return $preference;
	}
	
	/**
	 * Internal function to fill in the passed preference object from the row.
	 * @param $preference Preference
	 * @param $row array input row
	 */
	function _preferenceFromRow( &$preference, &$row )
	{
		$preference->setPreferenceName( $row[ 'name' ] );
		$preference->setPreferenceValue( $row[ 'value' ] );

		HookRegistry::call( 'PreferenceDAO::_returnPreferenceFromRow', array( &$preference, &$row ) );
	}
	
	/**
	 * Insert a new Preference.
	 * @param $preference Preference
	 */	
	function insertPreference( &$preference )
	{
		$currentUser = Request::getUser();

		if ( $currentUser )
		{
			//$keyword->stampModified( );
			$this->update(
				'INSERT INTO preferences'
				.' (user, name, value)'
				.' VALUES '
				.' (?, ?, ?)',
				array(
					$currentUser->getUserId( ),
					$preference->getPreferenceName( ),
					$preference->getPreferenceValue( )
				)
			);
		}
	}
	
	/**
	 * Update an existing preference.
	 * @param $preference Preference
	 */
	function updatePreference( &$preference )
	{
		$currentUser = Request::getUser();
		
		if ( $currentUser )
		{
	//		$keyword->stampModified();
			$this->update(
				'UPDATE preferences'
				.' SET'
				.' value=?'
				.' WHERE user=? AND name=?',
				array(
					$preference->getPreferenceValue(),
					$currentUser->getUserId(),
					$preference->getPreferenceName()
					)
				);
		}
	}
	
	/**
	 * Delete a preference.
	 * @param $preference Preference
	 */
	function deletePreference( &$preference )
	{
		return $this->deletePreferenceByName( $preference->getPreferenceName( ) );
	}
	
	/**
	 * Delete an annotation by name.
	 * @param $name string
	 */
	function deleteKeywordByName( $name )
	{
		$currentUser = Request::getUser();
		
		if ( $currentUser )
		{
			$this->update(
				'DELETE FROM preferences WHERE user=? AND name=?',
				array( $currentUser->getUserId(), $name ) );
		}
	}
	
	/**
	 * Get all preferences
	 * @return array Preferences
	 */
	function &getPreferences(  )
	{
		$preferences = array();
		
		$result = &$this->retrieve(
			"SELECT * FROM preferences",
			array ( ) );

		while ( ! $result->EOF )
		{
			$preferences[ ] = &$this->_returnPreferencefromRow( $result->GetRowAssoc( false ) );
			$result->MoveNext( );
		}
		
		$result->Close( );
		unset( $result );

		return $preferences;
	}
}

?>
