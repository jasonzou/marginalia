<?php

class block_annotation extends block_base
{
	function init( )
	{
		$this->title = 'Marginalia'; //get_string('annotation', 'block_annotation');
		$this->version = 2008121000;
	}
	
	function get_content( )
	{
		global $USER;
		
		if ( $this->content === NULL )
		{
			//$refurl = moodle_marginalia::get_refurl( );
			
			$this->content = new stdClass;
			$this->content->text = '';
			$this->content->footer = '';
		}
		return $this->content;
	}
}

