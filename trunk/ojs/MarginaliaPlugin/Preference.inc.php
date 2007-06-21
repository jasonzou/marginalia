<?php

class Preference extends DataObject
{
	/**
	 * Constructor.
	 */
	function Preference( )
	{
		parent::DataObject( );
	}
	
	function getPreferenceName( )
	{
		return $this->getData( 'name' );
	}
	
	function setPreferenceName( $name )
	{
		$this->setData( 'name', $name );
	}

	function getPreferenceValue( )
	{
		return $this->getData( 'value' );
	}
	
	function setPreferenceValue( $value )
	{
		$this->setData( 'value', $value );
	}
}
?>
