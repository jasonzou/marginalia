<?php
	
class AnnotationDAO extends DAO
{
	// var $annotationDao;

	/**
	 * Constructor.
	 */
	function AnnotationDAO()
	{
		parent::DAO();
	//	$this->annotationDao = &DAORegistry::getDAO( 'marginalia.AnnotationDAO' );
	}
	
	/**
	 * Retrieve an annotation by ID.
	 * @param $annotationId int
	 * @return Annotation
	 */
	function &getAnnotation( $annotationId )
	{
		$currentUser = Request::getUser();

		// No security check!
		$result = &$this->retrieve(
			'SELECT * FROM annotations WHERE id=? AND userid=?',
			array (
				$annotationId,
				$currentUser->getUsername()
			)
			);

		$returner = null;
		if ( $result->RecordCount() != 0 )
			$returner = &$this->_returnAnnotationFromRow( $result->GetRowAssoc( false ) );

		$result->Close( );
		unset( $result );

		return $returner;
	}

	/**
	 * Internal function to return an Annotation object from a row.
	 * @param $row array
	 * @return Annotation
	 */
	function &_returnAnnotationFromRow( &$row )
	{
		$annotation = &new Annotation( );
		$this->_annotationFromRow( $annotation, $row );
		return $annotation;
	}
	
	/**
	 * Internal function to fill in the passed annotation object from the row.
	 * @param $annotation Annotation output annotation
	 * @param $row array input row
	 */
	function _annotationFromRow( &$annotation, &$row )
	{
		$annotation->setAnnotationId( $row[ 'id' ] );

		$annotation->setUserId( $row[ 'userid' ] );
		$annotation->setUrl( $row[ 'url' ] );
		$annotation->setNote( $row[ 'note' ] );
		$annotation->setAccess( $row[ 'access' ] );
		$annotation->setAction( $row[ 'action' ] );
		$annotation->setQuote( $row[ 'quote' ] );
		$annotation->setQuoteTitle( $row[ 'quote_title' ] );
		$annotation->setQuoteAuthor( $row[ 'quote_author' ] );
		$annotation->setLink( $row[ 'link' ] );
		$annotation->setCreated( $row[ 'created' ] );
		$annotation->setModified( $row[ 'modified' ] );
		
		$start_word = $row[ 'start_word' ];
		$start_char = $row[ 'start_char' ];
		$end_word = $row[ 'end_word' ];
		$end_char = $row[ 'end_char' ];
		
		// Create the block range
		if ( $row[ 'start_block' ] )
		{
			$startPoint = new BlockPoint( $row[ 'start_block' ], $start_word, $start_char );
			$endPoint = new BlockPoint( $row[ 'end_block' ], $end_word, $end_char );
			$annotation->setBlockRange( new BlockRange( $startPoint, $endPoint ) );
		}
		// Create a block range using the out-of-date old format
		elseif ( array_key_exists( 'range', $row ) && $row[ 'range' ] )
		{
			$blockRange = new BlockRange( );
			$blockRange->fromString( $row[ 'range' ] );
			$annotation->setBlockRange( $blockRange );
		}
		
		// Create the xpath range
		if ( $row[ 'start_xpath' ] )
		{
			$startPoint = new XPathPoint( $row[ 'start_xpath' ], $start_word, $start_char );
			$endPoint = new XPathPoint( $row[ 'end_xpath' ], $end_word, $end_char );
			$annotation->setXPathRange( new XPathRange( $startPoint, $endPoint ) );
		}

		HookRegistry::call( 'AnnotationDAO::_returnAnnotationFromRow', array( &$annotation, &$row ) );
	}
	
	/**
	 * Insert a new Annotation.
	 * @param $annotation Annotation
	 */	
	function insertAnnotation( &$annotation )
	{
		$currentUser = Request::getUser();
		// Only a user can create his/her annotations
		if ( $currentUser && $currentUser->getUsername() == $annotation->getUserId() )
		{
			//$annotation->stampModified( );
			$now = Core::getCurrentDate();
			$blockRange = $annotation->getBlockRange( );
			$xpathRange = $annotation->getXPathRange( );
			$blockStart = $blockRange->getStart( );
			$blockEnd = $blockRange->getEnd( );
			$xpathStart = $xpathRange->getStart( );
			$xpathEnd = $xpathRange->getEnd( );
			$this->update(
				sprintf(
					'INSERT INTO annotations'
					.' (userid, url, note, access, action'
					.', quote, quote_title, quote_author, link'
					.', start_xpath, start_block, start_word, start_char'
					.', end_xpath, end_block, end_word, end_char'
					.', created, modified)'
					.' VALUES '
					.' (?,?,?,?,?, ?,?,?,?,  ?,?,?,?, ?,?,?,?, %s, %s)',
					$this->datetimeToDB( $now ),
					$this->datetimeToDB( $now )
				),
				array(
					$annotation->getUserId( ),
					$annotation->getUrl( ),
					$annotation->getNote( ),
					$annotation->getAccess( ),
					$annotation->getAction( ),
					
					$annotation->getQuote( ),
					$annotation->getQuoteTitle( ),
					$annotation->getQuoteAuthor( ),
					$annotation->getLink( ),
					
					$xpathStart->getPathStr( ),
					$blockStart->getPaddedPathStr( ),
					$xpathStart->getWords( ),
					$xpathStart->getChars( ),

					$xpathEnd->getPathStr( ),
					$blockEnd->getPaddedPathStr( ),
					$xpathEnd->getWords( ),
					$xpathEnd->getChars( )
				)
			);
			
			$annotation->setAnnotationId( $this->getInsertAnnotationId( ) );
			return $annotation->getAnnotationId();
		}
		else
			return 0;
	}
	
	/**
	 * Update an existing annotation.
	 * @param $annotation Annotation
	 */
	function updateAnnotation( &$annotation )
	{
		$currentUser = Request::getUser();
		
		// Only a user can update his/her own annotations
		if ( $currentUser && $currentUser->getUsername() == $annotation->getUserId() )
		{
	//		$annotation->stampModified();
			$blockRange = $annotation->getBlockRange( );
			$xpathRange = $annotation->getXPathRange( );
			$blockStart = $blockRange->getStart( );
			$blockEnd = $blockRange->getEnd( );
			$xpathStart = $xpathRange->getStart( );
			$xpathEnd = $xpathRange->getEnd( );
			$this->update(
				'UPDATE annotations'
				.' SET'
				.' url=?'
				.' , start_xpath=?'
				.' , start_block=?'
				.' , start_word=?'
				.' , start_char=?'
				.' , end_xpath=?'
				.' , end_block=?'
				.' , end_word=?'
				.' , end_char=?'
				.' , note=?'
				.' , access=?'
				.' , action=?'
				.' , quote=?'
				.' , quote_title=?'
				.' , quote_author=?'
				.' , link=?'
				.' , modified=?'
				.' WHERE id=?',
				array(
					$annotation->getUrl( ),
					$xpathStart->getPathStr( ),
					$blockStart->getPaddedPathStr( ),
					$xpathStart->getWords( ),
					$xpathStart->getChars( ),
					$xpathEnd->getPathStr( ),
					$blockEnd->getPaddedPathStr( ),
					$xpathEnd->getWords( ),
					$xpathEnd->getChars( ),
					$annotation->getNote( ),
					$annotation->getAccess( ),
					$annotation->getAction( ),
					$annotation->getQuote( ),
					$annotation->getQuoteTitle( ),
					$annotation->getQuoteAuthor( ),
					$annotation->getLink( ),
					$this->datetimeToDB( Core::getCurrentDate() ),
					$annotation->getAnnotationId( )
					)
				);
		}
	}
	
	/**
	 * Delete an annotation.
	 * @param $annotation Annotation
	 */
	function deleteAnnotation( &$annotation )
	{
		return $this->deleteAnnotationById( $annotation->getAnnotationId( ) );
	}
	
	/**
	 * Delete an annotation by ID.
	 * @param $annotationId int
	 */
	function deleteAnnotationById( $annotationId )
	{
		$currentUser = Request::getUser();
		
		// Must be logged in.  Sometimes an administrator may need to delete another
		// user's annotations (e.g. when deleting the user)
		if ( $currentUser )
		{
			$this->update(
				'DELETE FROM annotations WHERE id=?', array( $annotationId ) );
		}
	}
	
	function blockS( $blockStr )
	{
		// Break the block string into an array of block indices
		$blocks = explode( '/', $block );
		$nBlocks = count( $testBlocks );
	}
	
	/**
	 * Get all public annotations for a particular point in the text of a particular URL.
	 *
	 * @param $url string
	 * @param $block string
	 * @return array Annotations
	 */
	function &getVisibleAnnotationsByUrlPoint( $url, &$point, $username )
	{
		$annotations = array();
		$currentUser = Request::getUser();
		
		$testBlockStr = $point->getPaddedPathStr( );
		
		// Only fetch annotations visible to the current user
		if ( $currentUser && $currentUser->getUsername() == $username )
			$query = "SELECT * FROM annotations WHERE url=? AND userid=?";
		elseif ( $username )
			$query = "SELECT * FROM annotations WHERE url=? AND access='public' AND userid=?";
		else
			$query = "SELECT * FROM annotations WHERE url=? AND access='public'";
			
		if ( null === $point )
			$queryParams = array( $url, $username );
		else
		{
			// This implementation ignores the word and char fields of point
			$testBlockStr = $point->getPaddedPathStr( );
			$query .= " AND start_block <= ? AND end_block >= ?";
			$queryParams = array( $url, $username, $testBlockStr, $testBlockStr );
		}
		
		$query .= " ORDER BY start_block, start_word, start_char";
		$result = &$this->retrieve( $query, $queryParams );
		
		if ( DEBUG_ANNOTATION_QUERY )
		{
			echo "\n<p>" . htmlspecialchars( $query ) . "</p>\n";
			echo "<p>";
			for ( $i = 0;  $i < count( $queryParams );  ++$i )
				echo ( $i > 0 ? ' , ' : '' ) . $queryParams[ $i ];
			echo "</p>\n";
		}
			
		$annotations = array( );
		while ( ! $result->EOF )
		{
			$annotations[ ] = &$this->_returnAnnotationfromRow( $result->GetRowAssoc( false ) );
			$result->MoveNext( );
		}
		
		$result->Close( );
		unset( $result );

		return $annotations;
	}
	
	/**
	 * Get all annotations for a particular user for a particular url
	 * that are viewable to the current user.
	 *
	 * @param $url string
	 * @param $username string
	 * @return array Annotations
	 */
	function &getVisibleAnnotationsByUrlUser( $url, $username )
	{
		$annotations = array();
		$currentUser = Request::getUser();
		
		// Only fetch annotations visible to the current user
		if ( $currentUser && $currentUser->getUsername() == $username )
		{
			$query = "SELECT * FROM annotations WHERE url=? AND userid=? ORDER BY start_block, start_word, start_char";
//			echo $query;
			$result = &$this->retrieve( $query, array ( $url, $username ) );
		}
		else
		{
			$query = "SELECT * FROM annotations WHERE url=? AND access='public' AND userid=? ORDER BY start_block, start_word, start_char";
			$result = &$this->retrieve( $query, array ( $url, $username ) );
		}

		while ( ! $result->EOF )
		{
			$annotations[ ] = &$this->_returnAnnotationfromRow( $result->GetRowAssoc( false ) );
			$result->MoveNext( );
		}
		
		$result->Close( );
		unset( $result );

		return $annotations;
	}
	
	/**
	 * Get the ID of the last inserted annotation.
	 * @return int
	 */
	function getInsertAnnotationId()
	{
		return $this->getInsertId('annotations', 'id');
	}

}

?>
