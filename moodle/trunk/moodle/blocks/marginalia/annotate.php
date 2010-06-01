<?php // handles annotation actions

require_once( "../../config.php" );
require_once( 'config.php' );
require_once( 'marginalia-php/Annotation.php' );
require_once( 'marginalia-php/AnnotationService.php' );
require_once( 'marginalia-php/MarginaliaHelper.php' );
require_once( 'lib.php' );
require_once( 'annotation_summary_query.php' );

if ( $CFG->forcelogin || ANNOTATION_REQUIRE_USER )
   require_login();

 
class moodle_annotation extends Annotation
{
	function isActionValid( $action )
	{
		return null === $action || '' === $action;
	}
	
	function isSheetValid( $sheet )
	{
		return ! $sheet || 'public' == $sheet || 'private' == $sheet
			|| 'author' == $sheet;
	}	
}

class moodle_annotation_service extends AnnotationService
{
	var $extService = null;
	
	function moodle_annotation_service( $userid, $extService=null )
	{
		global $CFG;
		
		$this->extService = $extService;

		// Note: Cross-site request forgery protection requires cookies, so it will not be
		// activated if $CFG->usesid=true
		$csrfprotect = ! empty( $CFG->usesid ) && $CFG->usesid;
		
		$moodlemia = moodle_marginalia::get_instance( );
		AnnotationService::AnnotationService( 
			$moodlemia->get_host(),
			$moodlemia->get_service_path(),
			$moodlemia->get_install_date(),
			$userid,
			array(
				'baseUrl' => $CFG->wwwroot,
				'csrfCookie' => $csrfprotect ? null : 'MoodleSessionTest' . $CFG->sessioncookie,
				'csrfCookieValue' => $csrfprotect ? null : $_SESSION['SESSION']->session_test )
			);
		$this->tablePrefix = $CFG->prefix;
		
	}
	
	function doListAnnotations( $url, $sheet, $block, $all, $mark )
	{
		global $USER;
		
		$moodlemia = moodle_marginalia::get_instance( );
		$handler = annotation_summary_query::handler_for_url( $url );
		$sheet_type = $moodlemia->sheet_type( $sheet );
		$summary = new annotation_summary_query( array(
			'url' => $url
			,'sheet_type' => $sheet_type
			,'all' => $all ) );
		if ( $summary->error )  {
			$this->httpError( 400, 'Bad Request', 'Bad URL 1' );
			return null;
		}
		elseif ( !isloggedin( ) && ANNOTATION_REQUIRE_USER )  {
			$this->httpError( 403, 'Forbidden', 'Anonymous listing not allowed' );
			return null;
		}
		else
		{
			$querysql = $summary->sql( );
//			echo "QUERY: $querysql\n";
			$annotation_set = get_records_sql( $querysql );
			$annotations = Array( );
			$annotations_read = Array( );
			$annotations_unread = Array( );
			if ( $annotation_set )  {
				$i = 0;
				foreach ( $annotation_set as $r )
				{
					$annotations[ $i ] = $moodlemia->record_to_annotation( $r );
					$annotation = $annotations[ $i ];
					if ( $annotation->getLastRead( ) )
						$annotations_read[ ] = $annotation->id;
					else
						$annotations_unread[ ] = $annotation->id;
					$i++;
				}
			}
			
			// Record lastread (and possibly firstread)
			if ( 'read' == $mark )
			{
				$now = time( );
				
				if ( $annotations_read && count( $annotations_read ) )
				{
					$query = 'UPDATE '.$this->tablePrefix.AN_READ_TABLE
						."\n SET lastread=".(int)$now
						."\nWHERE userid=".(int)$USER->id
						."\n AND annotationid IN (".implode(',', $annotations_read).")";
					execute_sql( $query, false );
				}

				if ( $annotations_unread && count( $annotations_unread ) )
				{
					$query = 'INSERT INTO '.$this->tablePrefix.AN_READ_TABLE
						."\n (annotationid, userid, firstread, lastread)"
						."\nSELECT a.id, ".(int)$USER->id.', '.(int)$now.', '.(int)$now
						."\n FROM ".$this->tablePrefix.AN_DBTABLE.' a'
						."\n WHERE a.id IN (".implode(',', $annotations_unread).")";
					execute_sql( $query, false );
				}
			}
			
			if ( $this->extService )
			{
				$extService = $this->extService;
				$extService->listAnnotations( $url, $sheet, $block, $all, $mark );
			}
			
			$format = $this->getQueryParam( 'format', 'atom' );
			$logurl = 'annotate.php?format='.$format.'&url='.$url;
			add_to_log( $summary->handler->courseid, 'annotation', 'list', $logurl );
			return $annotations;
		}
	}
	
	function doGetAnnotation( $id, $mark )
	{
		global $CFG;
	
		$moodlemia = moodle_marginalia::get_instance( );
		
		// Check whether the range column exists (for backwards compatibility)
		$range = '';
/*		if ( column_type( $this->tablePrefix.'annotation', 'range' ) )
			$range = ', a.range AS range ';
*/		
		// Caller should ensure that id is numeric
		$query = "SELECT a.id, a.course, a.userid, a.url,
			  a.start_block, a.start_xpath, a.start_line, a.start_word, a.start_char,
			  a.end_block, a.end_xpath, a.end_line, a.end_word, a.end_char,
			  a.note, a.sheet_type, a.quote, a.quote_title, a.quote_author_id,
			  qu.id as quote_author_userid,
			  a.link, a.link_title, a.action,
			  a.created, a.modified $range
			  FROM {$this->tablePrefix}".AN_DBTABLE." a
			  JOIN {$this->tablePrefix}user u ON u.id=a.userid
			  JOIN {$this->tablePrefix}user qu ON qu.id=a.quote_author_id
			WHERE a.id = $id";
		$resultset = get_record_sql( $query );
		if ( $resultset && count( $resultset ) != 0 )  {
			$annotation = $moodlemia->record_to_annotation( $resultset );
			// Record lastread
			if ( 'read' == $mark )
			{
				$now = time( );
				$query = 'UPDATE '.$this->tablePrefix.AN_READ_TABLE
					."\n SET lastread=".(int)$now
					."\nWHERE annotationid=".(int)$id
					."\n AND userid=".(int)$USER->id;
				$success = execute_sql( $query );
				if ( ! $success )
				{
					$query = 'INSERT INTO '.$this->tablePrefix.AN_READ_TABLE
					."\n (annotationid, userid, lastread)\n VALUES ("
					."\n (".(int)$id.', '.(int)$USER->id.', '.(int)$now.', '.(int)$now.')';
					execute_sql( $query, false );
				}
			}
			return $annotation;
		}
		else
			return null;
	}
	
	function doCreateAnnotation( $annotation )
	{
		global $USER;
		
		if ( strlen( $annotation->getNote( ) ) > MAX_NOTE_LENGTH )
			$this->httpError( 400, 'Bad Request', 'Note too long' );
		elseif ( strlen( $annotation->getQuote( ) ) > MAX_QUOTE_LENGTH )
			$this->httpError( 400, 'Bad Request', 'Quote too long' );
		else
		{
			$moodlemia = moodle_marginalia::get_instance( );

			$time = time( );
			$annotation->setCreated( $time );
			$annotation->setModified( $time );
			$record = $moodlemia->annotation_to_record( $annotation );
			
			// Figure out the object type and ID from the url
			// Doing this here avoids infecting the caller with application-specific mumbo-jumbo
			// The cost of doing it here is low because annotations are created one-by one.  In essence,
			// this is really caching derived fields in the database to make queries easier.  (If only
			// MySQL had added views before v5).
			$oid = $moodlemia->oid_from_url( $annotation->getUrl( ) );
			if ( $oid )
			{
				$record->object_type = $oid->object_type;
				$record->object_id = $oid->object_id;
				// Find the post author
				$query = 'SELECT p.userid AS quote_author_id, p.subject AS quote_title, d.course as course'
					." FROM {$this->tablePrefix}forum_posts p "
					." JOIN {$this->tablePrefix}forum_discussions d ON p.discussion=d.id"
					." WHERE p.id=".(int)$record->object_id;
				$resultset = get_record_sql( $query );
				if ( $resultset && count ( $resultset ) != 0 )  {
					$record->quote_author_id = (int)$resultset->quote_author_id;
					$record->quote_title = $resultset->quote_title;
					$record->course = $resultset->course;
				}
				else  {
					$this->httpError( 400, 'Bad Request', 'No such forum post' );
					return 0;
				}
			}
			else
				echo "UNKNOWN URL ".$annotation->getUrl( )."\n";
	
			// must preprocess fields
			$id = insert_record( AN_DBTABLE, $record, true );
			
			if ( $id )  {
				// Record that this user has read the annotation.
				// This may be superfluous, as the read flag is not shown for the current user,
				// but for consistency it seems like a good idea.
				$record = new object( );
				$record->annotationid = $id;
				$record->userid = $USER->id;
				$record->firstread = $time;
				$record->lastread = $time;
				insert_record( AN_READ_TABLE, $record, true );

				if ( $this->extService )
				{
					$extService = $this->extService;
					$extService->createAnnotation( $annotation, $record );
				}
				
				// Moodle logging
				// TODO: fill in queryStr for the log
				$urlquerystr = '';
				$logurl = 'annotate.php' . ( $urlquerystr ? '?'.$urlquerystr : '' );
				add_to_log( null, 'annotation', 'create', $logurl, "$id" );
				return $id;
			}
		}
		return 0;
	}
	
	function doUpdateAnnotation( $annotation )
	{
		global $USER;
		
		$moodlemia = moodle_marginalia::get_instance( );
		$urlquerystr = '';
		$annotation->setModified( time( ) );
		$record = $moodlemia->annotation_to_record( $annotation );

		$r = update_record( AN_DBTABLE, $record );
		
		if ( $this->extService )
		{
			$extService = $this->extService;
			$extService->updateAnnotation( $annotation, $record );
		}
		
		// Moodle logging
		$logurl = 'annotate.php' . ( $urlquerystr ? '?'.$urlquerystr : '' );
		add_to_log( null, 'annotation', 'update', $logurl, "{$annotation->id}" );

		return $r;
	}
	
	function doBulkUpdate( $oldnote, $newnote )
	{
		global $CFG, $USER;
		
		$where = "userid='".addslashes($USER->id)."' AND note='".addslashes($oldnote)."'";

		// Count how many replacements will be made
		$query = 'SELECT count(id) AS n FROM '.$CFG->prefix.AN_DBTABLE." WHERE $where";
		$result = get_record_sql( $query );
		$n = (int)$result->n;
		
		if ( $n )  {
			// Do the replacements
			$query = 'UPDATE '.$CFG->prefix.AN_DBTABLE
				." set note='".addslashes($newnote)."',"
				." modified=".time( )
				." WHERE $where";
			execute_sql( $query, false );
		}
		
		if ( $this->extService )
		{
			$extService = $this->extService;
			$extService->bulkUpdateAnnotations( $oldnote, $newnote, $n );
		}

		header( 'Content-type: text/plain' );
		return $n;
	}
	
	function doDeleteAnnotation( $id )
	{
		global $USER;
		
		delete_records( AN_DBTABLE, 'id', $id );
		delete_records( AN_READ_TABLE, 'annotationid', $id );
		
		if ( $this->extService )
		{
			$extService = $this->extService;
			$extService->deleteAnnotation( $id );
		}

		$logurl = "annotate.php?id=$id";
		add_to_log( null, 'annotation', 'delete', $logurl, "$id" );
		return True;
	}

	function listBodyParams( )
	{
		return MarginaliaHelper::ListBodyParams( true );
	}
}

// Load up the logger, if available
$logblock = get_record('block', 'name', 'marginalia_log');
$logger = null;
if ( $logblock )
{
	require_once( $CFG->dirroot.'/blocks/marginalia_log/log.php' );
 	$logger = new marginalia_log();
}

$service = new moodle_annotation_service( isguest() ? null : $USER->id,
	$logger && $logger->is_active() ? $logger : null );
$service->dispatch( );

