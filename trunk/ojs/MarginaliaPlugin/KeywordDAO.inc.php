<?php

class KeywordDAO extends DAO
{
	// var $keywordDAO;

	/**
	 * Constructor.
	 */
	function KeywordDAO()
	{
		parent::DAO();
	//	$this->keywordDao = &DAORegistry::getDAO( 'marginalia.KeywordDAO' );
	}
	
	/**
	 * Retrieve keyword by name.
	 * @param $name string
	 * @return Keyword
	 */
	function &getKeword( $name )
	{
		$result = &$this->retrieve(
			'SELECT * FROM keywords WHERE name=?',
			$name
			);

		$returner = null;
		if ( $result->RecordCount() != 0 )
			$returner = &$this->_returnKeywordFromRow( $result->GetRowAssoc( false ) );

		$result->Close( );
		unset( $result );

		return $returner;
	}

	/**
	 * Internal function to return a Keyword object from a row.
	 * @param $row array
	 * @return Keyword
	 */
	function &_returnKeywordFromRow( &$row )
	{
		$keyword = &new Keyword( );
		$this->_keywordFromRow( $keyword, $row );
		return $keyword;
	}
	
	/**
	 * Internal function to fill in the passed keyword object from the row.
	 * @param $keyword Keyword output annotation
	 * @param $row array input row
	 */
	function _keywordFromRow( &$keyword, &$row )
	{
		$keyword->setName( $row[ 'name' ] );
		$keyword->setDescription( $row[ 'description' ] );

		HookRegistry::call( 'KeywordDAO::_returnKeywordFromRow', array( &$keyword, &$row ) );
	}
	
	/**
	 * Insert a new Keyword.
	 * @param $keyword Keyword
	 */	
	function insertKeyword( &$keyword )
	{
		//$keyword->stampModified( );
		$this->update(
			'INSERT INTO keywords'
			.' (name, description)'
			.' VALUES '
			.' (?)',
			array(
				$keyword->getName( ),
				$keyword->getDescription( )
			)
		);
	}
	
	/**
	 * Update an existing keyword.
	 * @param $keyword Keyword
	 */
	function updateKeyword( &$keyword )
	{
//		$keyword->stampModified();
		$this->update(
			'UPDATE keywords'
			.' SET'
			.' name=?'
			.' description=?'
			.' WHERE name=?',
			array(
				$keyword->getName(),
				$keyword->getDescription(),
				$keyword->getName()
				)
			);
	}
	
	/**
	 * Delete a keyword.
	 * @param $keyword Keyword
	 */
	function deleteKeyword( &$keyword )
	{
		return $this->deleteKeywordByName( $keyword->getName( ) );
	}
	
	/**
	 * Delete an annotation by name.
	 * @param $name string
	 */
	function deleteKeywordByName( $name )
	{
		$this->update(
			'DELETE FROM keywords WHERE name=?', array( $name ) );
	}
	
	/**
	 * Get all keywords
	 * @return array Keywords
	 */
	function &getKeywords(  )
	{
		$keywords = array();
		
		$result = &$this->retrieve(
			"SELECT * FROM keywords ORDER BY name",
			array ( ) );

		while ( ! $result->EOF )
		{
			$keywords[ ] = &$this->_returnKeywordfromRow( $result->GetRowAssoc( false ) );
			$result->MoveNext( );
		}
		
		$result->Close( );
		unset( $result );

		return $keywords;
	}
}

?>
