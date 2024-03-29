<?php

define( 'AN_ACCESS_PUBLIC', 0xffff );
define( 'DEBUG_ANNOTATION_QUERY', false );	// will break GET operations by emitting query string

	
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
			'SELECT a.*'
			.', u.username AS userlogin'
			.", concat(u.first_name,' ',u.middle_name,' ',u.last_name) AS username"
			.' FROM annotations a'
			.' JOIN users u ON u.user_id=a.userid'
			.' WHERE id=? AND userid=?',
			array (
				$annotationId,
				$currentUser->getUserId()
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
		$annotation->setUserId( $row[ 'userlogin' ] );
		$annotation->setUserName( $row[ 'username' ] );
		$annotation->setUrl( $row[ 'url' ] );
		$annotation->setNote( $row[ 'note' ] );
		$annotation->setAction( $row[ 'action' ] );
		$annotation->setQuote( $row[ 'quote' ] );
		$annotation->setQuoteTitle( $row[ 'quote_title' ] );
		$annotation->setQuoteAuthorId( $row[ 'quote_author_id' ] );
		$annotation->setQuoteAuthorName( $row[ 'quote_author_name' ] );
		$annotation->setLink( $row[ 'link' ] );
		$annotation->setLinkTitle( $row[ 'link_title' ] );
		$annotation->setCreated( $row[ 'created' ] );
		$annotation->setModified( $row[ 'modified' ] );

		$access = $row[ 'access_perms' ];
		$annotation->setAccess( $access & AN_ACCESS_PUBLIC ? 'public' : 'private' );
		
		$start_line = $row[ 'start_line' ];
		$start_word = $row[ 'start_word' ];
		$start_char = $row[ 'start_char' ];
		$end_line = $row[ 'end_line' ];
		$end_word = $row[ 'end_word' ];
		$end_char = $row[ 'end_char' ];
		
		// Create the block range
		if ( $row[ 'start_block' ] )
		{
			$range = new SequenceRange( );
			$range->setStart( new SequencePoint( $row[ 'start_block' ], $start_line, $start_word, $start_char ) );
			$range->setEnd( new SequencePoint( $row[ 'end_block' ], $end_line, $end_word, $end_char ) );
			$annotation->setSequenceRange( $range );
		}
		// Create a block range using the out-of-date old format
		elseif ( array_key_exists( 'range', $row ) && $row[ 'range' ] )
		{
			$range = new SequenceRange( );
			$range->fromString( $row[ 'range' ] );
			$annotation->setSequenceRange( $range );
		}
		
		// Create the xpath range
		if ( $row[ 'start_xpath' ] )
		{
			$range = new XPathRange( );
			$range->setStart( new XPathPoint( $row[ 'start_xpath' ], $start_line, $start_word, $start_char ) );
			$range->setEnd( new XPathPoint( $row[ 'end_xpath' ], $end_line, $end_word, $end_char ) );
			$annotation->setXPathRange( $range );
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
			$sequenceRange = $annotation->getSequenceRange( );
			$xpathRange = $annotation->getXPathRange( );
			$sequenceStart = $sequenceRange->getStart( );
			$sequenceEnd = $sequenceRange->getEnd( );
			$xpathStart = $xpathRange->getStart( );
			$xpathEnd = $xpathRange->getEnd( );
			$access = 'public' == $annotation->getAccess( ) ? AN_ACCESS_PUBLIC : 0;
			
			$quoteAuthorName = $annotation->getQuoteAuthorName( );
			$quoteAuthorId = $annotation->getQuoteAuthorId( );
			if ( ! $quoteAuthorName && $quoteAuthorId)
			{
				$userdao = new UserDAO( );
				$tuser = $userdao->getUserByUsername( $quoteAuthorId );
				if ( $tuser )
					$quoteAuthorName = $tuser->getUsername( );
			}
			
			$this->update(
				sprintf(
					'INSERT INTO annotations'
					.' (userid, url, note, access_perms, action'
					.', quote, quote_title, quote_author_id, quote_author_name'
					.', link, link_title'
					.', start_xpath, start_block, start_line, start_word, start_char'
					.', end_xpath, end_block, end_line, end_word, end_char'
					.', created, modified)'
					.' VALUES '
					.' (?,?,?,?,?, ?,?,?,?,?,?,  ?,?,?,?,?, ?,?,?,?,?, %s, %s)',
					$this->datetimeToDB( $now ),
					$this->datetimeToDB( $now )
				),
				array(
					$currentUser->getUserId( ),
					$annotation->getUrl( ),
					$annotation->getNote( ),
					$access,
					$annotation->getAction( ),
					
					$annotation->getQuote( ),
					$annotation->getQuoteTitle( ),
					$quoteAuthorId,
					$quoteAuthorName,
					$annotation->getLink( ),
					$annotation->getLinkTitle( ),
					
					$xpathStart->getPathStr( ),
					$sequenceStart->getPaddedPathStr( ),
					$xpathStart->getLines( ),
					$xpathStart->getWords( ),
					$xpathStart->getChars( ),

					$xpathEnd->getPathStr( ),
					$sequenceEnd->getPaddedPathStr( ),
					$xpathEnd->getLines( ),
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
			$sequenceRange = $annotation->getSequenceRange( );
			$xpathRange = $annotation->getXPathRange( );
			$sequenceStart = $sequenceRange->getStart( );
			$sequenceEnd = $sequenceRange->getEnd( );
			$xpathStart = $xpathRange ? $xpathRange->getStart( ) : null;
			$xpathEnd = $xpathRange ? $xpathRange->getEnd( ) : null;
			$access = 'public' == $annotation->getAccess( ) ? AN_ACCESS_PUBLIC : 0;

			$quoteAuthorName = $annotation->getQuoteAuthorName( );
			$quoteAuthorId = $annotation->getQuoteAuthorId( );
			if ( ! $quoteAuthorName && $quoteAuthorId)
			{
				$userdao = new UserDAO( );
				$tuser = $userdao->getUserByUsername( $quoteAuthorId );
				if ( $tuser )
					$quoteAuthorName = $tuser->getUsername( );
			}
			
			$this->update(
				'UPDATE annotations'
				.' SET'
				.' url=?'
				.' , start_xpath=?'
				.' , start_block=?'
				.' , start_line=?'
				.' , start_word=?'
				.' , start_char=?'
				.' , end_xpath=?'
				.' , end_block=?'
				.' , end_line=?'
				.' , end_word=?'
				.' , end_char=?'
				.' , note=?'
				.' , access_perms=?'
				.' , action=?'
				.' , quote=?'
				.' , quote_title=?'
				.' , quote_author_id=?'
				.' , quote_author_name=?'
				.' , link=?'
				.' , link_title=?'
				.' , modified=?'
				.' WHERE id=?',
				array(
					$annotation->getUrl( ),
					$xpathStart ? $xpathStart->getPathStr( ) : null,
					$sequenceStart->getPaddedPathStr( ),
					$sequenceStart->getLines( ),
					$sequenceStart->getWords( ),
					$sequenceStart->getChars( ),
					$xpathEnd ? $xpathEnd->getPathStr( ) : null,
					$sequenceEnd->getPaddedPathStr( ),
					$sequenceEnd->getLines( ),
					$sequenceEnd->getWords( ),
					$sequenceEnd->getChars( ),
					$annotation->getNote( ),
					$access,
					$annotation->getAction( ),
					$annotation->getQuote( ),
					$annotation->getQuoteTitle( ),
					$quoteAuthorId,
					$quoteAuthorName,
					$annotation->getLink( ),
					$annotation->getLinkTitle( ),
					$this->datetimeToDB( Core::getCurrentDate() ),
					$annotation->getAnnotationId( )
					)
				);
			return True;
		}
		return False;
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
			return $this->update(
				'DELETE FROM annotations WHERE id=?', array( $annotationId ) );
		}
		return  false;
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
	 * @param $username string
	 * @param $block string
	 * @return array Annotations
	 */
	function &getVisibleAnnotationsByUrlUserBlock( $url, $username, $block, $all )
	{
		$annotations = array();
		$currentUser = Request::getUser();
		$query = 'SELECT a.*'
			.', u.username AS userlogin'
			.", concat(u.first_name,' ',u.middle_name,' ',u.last_name) AS username"
			.' FROM annotations a'
			.' JOIN users u ON u.user_id=a.userid'
			.' WHERE ';
		$queryParams = array();
		
		if ( $url )
		{
			array_push( $queryParams, $url );
			$query .= "a.url=?";
		}
		else
			$query .= '1=1';
		
		// Only fetch annotations visible to the current user
		$findUserId = 0;
		if ( $username )
		{
			$userdao = new UserDAO( );
			$tuser = $userdao->getUserByUsername( $username );
			if ( $tuser )
			{
				if ( $currentUser && ( $currentUser->getUsername() == $username || $all ) )
					$query .= " AND a.userid=?";
				elseif ( $username )
					$query .= ' AND a.access_perms&'.AN_ACCESS_PUBLIC.' AND a.userid=?';
				
				array_push( $queryParams, $tuser->getUserId( ) );
			}
			// If there's no such user, there can be no results
			else
				$query .=' AND 1=0';
		}
		elseif ( ! $all )
			$query .= ' AND a.access_perms&'.AN_ACCESS_PUBLIC;
			
		if ( $block )
		{
			// This implementation ignores the word and char fields of point
			$testBlockStr = $block->getPaddedPathStr( );
			$query .= " AND a.start_block <= ? AND a.end_block >= ?";
			array_push( $queryParams, $testBlockStr, $testBlockStr );
		}
		
		$query .= " ORDER BY a.start_block, a.start_line, a.start_word, a.start_char";
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
	 *
	function &getVisibleAnnotationsByUrlUserBlock( $url, $username, $block )
	{
		$annotations = array();
		$currentUser = Request::getUser();
		
//		echo "currentUser: $currentUserx , ".$currentUserx->getUsername()." ";
		// Only fetch annotations visible to the current user
		if ( $currentUser && $currentUser->getUsername() == $username )
		{
			$query = "SELECT * FROM annotations WHERE url=? AND userid=? ORDER BY start_block, start_line, start_word, start_char";
//			echo $query;
			$result = &$this->retrieve( $query, array ( $url, $username ) );
		}
		else
		{
			$query = "SELECT * FROM annotations WHERE url=? AND access='public' AND userid=? ORDER BY start_block, start_line, start_word, start_char";
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
	*/
	/**
	 * Get the ID of the last inserted annotation.
	 * @return int
	 */
	function getInsertAnnotationId()
	{
		return $this->getInsertId('annotations', 'id');
	}

}

