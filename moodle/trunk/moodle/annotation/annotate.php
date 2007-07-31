<?php // handles annotation actions

require_once( "../config.php" );
require_once( 'marginalia-php/Annotation.php' );
require_once( 'marginalia-php/AnnotationService.php' );
require_once( 'marginalia-php/MarginaliaHelper.php' );
require_once( 'AnnotationSummaryQuery.php' );

define( 'MAX_NOTE_LENGTH', 250 );
define( 'MAX_QUOTE_LENGTH', 1000 );

// When this is true, any access to annotations (including fetching the Atom feed) requires a valid user
// When false, anyone on the Web can retrieve public annotations via an Atom feed
define( 'ANNOTATION_REQUIRE_USER', false );

define( 'ANNOTATE_SERVICE_PATH', '/annotate' );

if ( $CFG->forcelogin || ANNOTATION_REQUIRE_USER )
   require_login();

   
class MoodleAnnotation extends Annotation
{
	function isActionValid( $action )
	{
		return null === $action || '' === $action;
	}
	
	function isAccessValid( $access )
	{
		return ! $access || 'public' == $access || 'private' == $access
			|| 'author' == $access || 'teacher' == $access
			|| 'author teacher' == $access;
	}	
}

class MoodleAnnotationService extends AnnotationService
{
	function MoodleAnnotationService( $wwwroot, $username, $tablePrefix )
	{
		$this->tablePrefix = $tablePrefix;
		$urlParts = parse_url( $wwwroot );
		$host = $urlParts[ 'host' ];
		$servicePath = $this->getMoodlePath( ) + ANNOTATE_SERVICE_PATH;
		AnnotationService::AnnotationService( $host, $servicePath, Date( '2007-07-26' ), $username );
	}
	
	/** Get the moodle path - that is, the path to moodle from the root of the server.  Typically this is 'moodle/'.
	 * REQUEST_URI starts with this. */
	function getMoodlePath( )
	{
		global $CFG;
		
		$urlParts = parse_url( $CFG->wwwroot );
		return $urlParts[ 'path' ];
	}
	
	/**
	 * Get the sever part of the moodle path.
	 * This is the absolute path, with the getMoodlePath( ) portion chopped off.
	 * Useful, because appending a REQUEST_URI to it produces an absolute URI. */
	function getMoodleServer( )
	{
		global $CFG;
		
		$urlParts = parse_url( $CFG->wwwroot );
		if ( $urlParts[ 'path' ] == '/' )
			return $CFG->wwwroot;
		else
			return substr( $CFG->wwwroot, 0, strpos( $CFG->wwwroot, $urlParts[ 'path' ] ) );
	}

	function doListAnnotations( $url, $username, $block )
	{
		$query = new AnnotationSummaryQuery( $url, $username, null, null );
		if ( $query->error )
		{
			$this->httpError( 400, 'Bad Request', 'Bad URL' );
			return null;
		}
		elseif ( isguest() && ANNOTATION_REQUIRE_USER )
		{
			$this->httpError( 403, 'Forbidden', 'Anonymous listing not allowed' );
			return null;
		}
		else
		{
			$querySql = $query->sql( 'section_type, section_name, quote_title' );
			$annotationSet = get_records_sql( $querySql );
			$annotations = Array( );
			if ( $annotationSet )
			{
				$i = 0;
				foreach ( $annotationSet as $r )
					$annotations[ $i++ ] = $this->recordToAnnotation( $r );
			}
			$format = $this->getQueryParam( 'format', 'atom' );
			$logUrl = 'annotate.php?format='.$format.($username ? '&user='.$username : '').'&url='.$url;
			add_to_log( $query->handler->courseId, 'annotation', 'list', $logUrl );
			return $annotations;
		}
	}
	
	function doGetAnnotation( $id )
	{
		global $CFG;
	
		// Caller should ensure that id is numeric
		$query = "SELECT a.id, a.userid, a.url,
			  a.start_block, a.start_xpath, a.start_word, a.start_char,
			  a.end_block, a.end_xpath, a.end_word, a.end_char,
			  a.note, a.access, a.quote, a.quote_title, a.quote_author,
			  a.link, a.link_title, a.action,
			  a.created, a.modified
			  FROM {$this->tablePrefix}annotation a
			WHERE a.id = $id";
		$resultSet = get_record_sql( $query );
		if ( $resultSet && count( $resultSet ) != 0 )
		{
			$annotation = $this->recordToAnnotation( $resultSet[ 0 ] );
			echo "Annotation: $annotation\n";
			return $annotation;
		}
		else
			return null;
	}
	
	function doCreateAnnotation( $annotation )
	{
		if ( strlen( $annotation->getNote( ) ) > MAX_NOTE_LENGTH )
			$this->httpError( 400, 'Bad Request', 'Note too long' );
		elseif ( strlen( $annotation->getQuote( ) ) > MAX_QUOTE_LENGTH )
			$this->httpError( 400, 'Bad Request', 'Quote too long' );
		else
		{
			$record = $this->annotationToRecord( $annotation );
			
			// Figure out the object type and ID from the url
			// Doing this here avoids infecting the caller with application-specific mumbo-jumbo
			// The cost of doing it here is low because annotations are created one-by one.  In essence,
			// this is really caching derived fields in the database to make queries easier.  (If only
			// MySQL had added views before v5).
			if ( preg_match( '/^.*\/mod\/forum\/permalink\.php\?p=(\d+)/', $annotation->getUrl(), $matches ) )
			{
				$record->object_type = 'post';
				$record->object_id = (int) $matches[ 1 ];
			}
	
			// must preprocess fields
			echo "Insert record into {$this->tablePrefix}annotation\n";
			$id = insert_record( 'annotation', $record, true );
			echo "Record ID=$id\n";
			
			if ( $id )
			{
				$logUrl = 'annotate.php' . ( $urlQueryStr ? '?'.$urlQueryStr : '' );
				add_to_log( null, 'annotation', 'create', $logUrl, "$id" );
				return $id;
			}
		}
		return 0;
	}
	
	function doUpdateAnnotation( $annotation )
	{
		$record = $this->annotationToRecord( $annotation );
		$logUrl = 'annotate.php' . ( $urlQueryStr ? '?'.$urlQueryStr : '' );
		add_to_log( null, 'annotation', 'update', $logUrl, "$id" );
		return update_record( 'annotation', $record );
	}
	
	function doDeleteAnnotation( $id )
	{
		delete_records( 'annotation', 'id', $id );
		$logUrl = 'annotate.php' . ( $urlQueryStr ? '?'.$urlQueryStr : '' );
		add_to_log( null, 'annotation', 'delete', $logUrl, "$id" );
	}
	
	function recordToAnnotation( $r )
	{
		$annotation = new Annotation( );
		
		$annotation->setAnnotationId( $r->id );
		$annotation->setUserId( $r->userid );
		$annotation->setAccess( $r->access );
		$annotation->setUrl( $r->url );
		$annotation->setNote( $r->note );
		$annotation->setQuote( $r->quote );
		$annotation->setQuoteTitle( $r->quote_title );
		$annotation->setQuoteAuthor( $r->quote_author );
		$annotation->setLink( $r->link );
		$annotation->setLinkTitle( $r->link_title );
		$annotation->setCreated( $r->created );
		
		$range = new SequenceRange( );
		$range->setStart( new SequencePoint( $r->start_block, $r->start_word, $r->start_char ) );
		$range->setEnd( new SequencePoint( $r->end_block, $r->end_word, $r->end_char ) );
		$annotation->setSequenceRange( $range );
		
		$range = new XPathRange( );
		$range->setStart( new XPathPoint( $r->start_xpath, $r->start_word, $r->start_char ) );
		$range->setEnd( new XpathPoint( $r->end_xpath, $r->end_word, $r->end_char ) );
		$annotation->setXPathRange( $range );
		
		return $annotation;
	}
		
	function annotationToRecord( $annotation )
	{
		$id = $annotation->getAnnotationId( );
		if ( $id )
			$record->id = $id;
		$record->userid = $annotation->getUserId( );
		$record->access = $annotation->getAccess( );
		$record->url = $annotation->getUrl( );
		$record->note = $annotation->getNote( );
		$record->quote = $annotation->getQuote( );
		$record->quote_title = $annotation->getQuoteTitle( );
		$record->quote_author = $annotation->getQuoteAuthor( );
		$record->link = $annotation->getLink( );
		$record->link_title = $annotation->getLinkTitle( );
		$record->created = date( 'Y-m-d H:m' );

		$sequenceRange = $annotation->getSequenceRange( );
		$sequenceStart = $sequenceRange->getStart( );
		$sequenceEnd = $sequenceRange->getEnd( );
		$xpathRange = $annotation->getXPathRange( );
		$xpathStart = $xpathRange->getStart( );
		$xpathEnd = $xpathRange->getEnd( );
		
		$record->start_block = $sequenceStart->getPaddedPathStr( );
		$record->start_xpath = $xpathStart->getPathStr( );
		$record->start_word = $xpathStart->getWords( );
		$record->start_char = $xpathStart->getChars( );
		
		$record->end_block = $sequenceEnd->getPaddedPathStr( );
		$record->end_xpath = $xpathEnd->getPathStr( );
		$record->end_word = $xpathEnd->getWords( );
		$record->end_char = $xpathEnd->getChars( );
		return $record;
	}
}

$service = new MoodleAnnotationService( $CFG->wwwroot, isguest() ? null : $USER->username, $CFG->prefix );
$service->dispatch( );

?>
