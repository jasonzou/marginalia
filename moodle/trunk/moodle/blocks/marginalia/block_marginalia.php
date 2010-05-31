<?php

class block_marginalia extends block_base
{
	function init( )
	{
		$this->title = 'Marginalia Annotation'; //get_string('annotation', 'block_annotation');
		$this->version = 2010022302;
		$this->cron = 60 * 60 * 25.2;	// once a day is often enough, but make it a bit off to prevent sympathetic resonance
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
	
	function cron( )
	{
		global $CFG;
		
		// Delete annotations whose users no longer exist
		// this removes the need to touch admin/user.php
		// Other code should therefore be careful not to join on non-existent users
		$query = "DELETE FROM {$CFG->prefix}marginalia WHERE userid NOT IN (SELECT id FROM {$CFG->prefix}user)";
		execute_sql( $query, false );
		// This will catch all read records for non-existent users and annotations, though the latter should
		// already have been deleted with the annotation.
		$query = "DELETE FROM {$CFG->prefix}marginalia_read WHERE annotationid NOT IN (SELECT id FROM {$CFG->prefix}marginalia)";
		execute_sql( $query, false );
	}
}

