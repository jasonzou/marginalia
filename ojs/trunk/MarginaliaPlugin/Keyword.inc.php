<?php

class Keyword extends DataObject
{
	/**
	 * Constructor.
	 */
	function Keyword( )
	{
		parent::DataObject( );
	}
	
	function getName( )
	{
		return $this->getData( 'name' );
	}
	
	function setName( $name )
	{
		$this->setData( 'name', $name );
	}
	
	function getDescription( )
	{
		return $this->getData( 'description' );
	}
	
	function setDescription( $desc )
	{
		$this->setData( 'description', $desc );
	}
}
?>
