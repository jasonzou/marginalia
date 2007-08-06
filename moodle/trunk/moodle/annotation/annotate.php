<?php // handles annotation actions

require_once( "../config.php" );
require_once( 'marginalia-php/Annotation.php' );
require_once( 'marginalia-php/AnnotationService.php' );
require_once( 'marginalia-php/MarginaliaHelper.php' );
require_once( 'AnnotationGlobals.php' );
require_once( 'AnnotationSummaryQuery.php' );

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
	function MoodleAnnotationService( $username )
	{
		global $CFG;
		AnnotationService::AnnotationService( 
			AnnotationGlobals::getHost(),
			AnnotationGlobals::getServicePath(),
			AnnotationGlobals::getInstallDate(),
			$username,
			$CFG->wwwroot );
		$this->tablePrefix = $CFG->prefix;
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
					$annotations[ $i++ ] = AnnotationGlobals::recordToAnnotation( $r );
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
	
		// Check whether the range column exists (for backwards compatibility)
		$range = '';
		if ( ! column_type( $this->tablePrefix.'annotation', 'range' ) )
			$range = ', a.range AS range ';
		
		// Caller should ensure that id is numeric
		$query = "SELECT a.id, a.userid, a.url,
			  a.start_block, a.start_xpath, a.start_word, a.start_char,
			  a.end_block, a.end_xpath, a.end_word, a.end_char,
			  a.note, a.access, a.quote, a.quote_title, a.quote_author,
			  a.link, a.link_title, a.action,
			  a.created, a.modified $range
			  FROM {$this->tablePrefix}annotation AS a
			WHERE a.id = $id";
		$resultSet = get_record_sql( $query );
		if ( $resultSet && count( $resultSet ) != 0 )
		{
			$annotation = AnnotationGlobals::recordToAnnotation( $resultSet );
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
			$record = AnnotationGlobals::annotationToRecord( $annotation );
			
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
			$id = insert_record( 'annotation', $record, true );
			
			if ( $id )
			{
				// TODO: fill in queryStr for the log
				$urlQueryStr = '';
				$logUrl = 'annotate.php' . ( $urlQueryStr ? '?'.$urlQueryStr : '' );
				add_to_log( null, 'annotation', 'create', $logUrl, "$id" );
				return $id;
			}
		}
		return 0;
	}
	
	function doUpdateAnnotation( $annotation )
	{
		$urlQueryStr = '';
		$record = AnnotationGlobals::annotationToRecord( $annotation );
		$logUrl = 'annotate.php' . ( $urlQueryStr ? '?'.$urlQueryStr : '' );
		add_to_log( null, 'annotation', 'update', $logUrl, "{$annotation->id}" );
		return update_record( 'annotation', $record );
	}
	
	function doDeleteAnnotation( $id )
	{
		delete_records( 'annotation', 'id', $id );
		$logUrl = "annotate.php?id=$id";
		add_to_log( null, 'annotation', 'delete', $logUrl, "$id" );
		return True;
	}
}

$service = new MoodleAnnotationService( isguest() ? null : $USER->username );
$service->dispatch( );

?>
