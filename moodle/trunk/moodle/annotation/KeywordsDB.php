<?php

class annotation_keywords_db
{
	function list_keywords( $userid )
	{
		global $CFG;
		// A keyword is a note that occurs more than once
		$query =
			'SELECT a.note AS name, \'\' AS description'
			. ' FROM mdl_annotation a'
			. ' JOIN ('
			. '  SELECT note, count(*) as m'
			. '  FROM mdl_annotation'
			. "  WHERE userid='$userid'"
			. '  GROUP BY note) AS b'
			. ' ON a.note = b.note'
			. ' AND b.m > 1'
			. ' GROUP BY a.note'
			. ' ORDER BY a.note';
		$keywordset = get_records_sql( $query );
		$keywords = array( );
		if ( $keywordset )  {
			$i = 0;
			foreach ( $keywordset as $r )
				$keywords[ $i++ ] = annotation_globals::record_to_keyword( $r );
		}
		return $keywords;
	}
}

?>
