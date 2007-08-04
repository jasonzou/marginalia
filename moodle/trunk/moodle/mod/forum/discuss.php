<?php // $Id$

//  Displays a post, and all the posts below it.
//  If no post is given, displays all posts in a discussion

    require_once("../../config.php");
	require_once("../../annotation/marginalia-php/embed.php");
	require_once("../../annotation/AnnotationSummaryQuery.php");
//	require_once("../../annotation/marginalia-config.php");
	
    
    $d      = required_param('d', PARAM_INT);                // Discussion ID
    $parent = optional_param('parent', 0, PARAM_INT);        // If set, then display this post and all children.
    $mode   = optional_param('mode', 0, PARAM_INT);          // If set, changes the layout of the thread
    $move   = optional_param('move', 0, PARAM_INT);          // If set, moves this discussion to another forum
    $fromforum = optional_param('fromforum', 0, PARAM_INT);  // Needs to be set when we want to move a discussion.
    $mark   = optional_param('mark', '', PARAM_ALPHA);       // Used for tracking read posts if user initiated.
    $postid = optional_param('postid', 0, PARAM_INT);        // Used for tracking read posts if user initiated.

    if (!$discussion = get_record("forum_discussions", "id", $d)) {
        error("Discussion ID was incorrect or no longer exists");
    }

    if (!$course = get_record("course", "id", $discussion->course)) {
        error("Course ID is incorrect - discussion is faulty");
    }

    if (!$forum = get_record("forum", "id", $discussion->forum)) {
        notify("Bad forum ID stored in this discussion");
    }

    if (!$cm = get_coursemodule_from_instance('forum', $forum->id, $course->id)) {
        error('Course Module ID was incorrect');
    }
    // move this down fix for MDL-6926
    require_once("lib.php");
    require_course_login($course, true, $cm);

    $modcontext = get_context_instance(CONTEXT_MODULE, $cm->id);
    $canviewdiscussion = has_capability('mod/forum:viewdiscussion', $modcontext);
    
    if ($forum->type == "news") {
        if (!($USER->id == $discussion->userid || (($discussion->timestart == 0
            || $discussion->timestart <= time())
            && ($discussion->timeend == 0 || $discussion->timeend > time())))) {
            error('Discussion ID was incorrect or no longer exists', "$CFG->wwwroot/mod/forum/view.php?f=$forum->id");
        }
    }


    if (!empty($move)) {
        
        if (!$sourceforum = get_record('forum', 'id', $fromforum)) {
            error('Cannot find which forum this discussion is being moved from');
        }
        if ($sourceforum->type == 'single') {
            error('Cannot move discussion from a simple single discussion forum');
        }
        
        require_capability('mod/forum:movediscussions', $modcontext);

        if ($forum = get_record("forum", "id", $move)) {
            if (!forum_move_attachments($discussion, $move)) {
                notify("Errors occurred while moving attachment directories - check your file permissions");
            }
            set_field("forum_discussions", "forum", $forum->id, "id", $discussion->id);
            $discussion->forum = $forum->id;
            if ($cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
                add_to_log($course->id, "forum", "move discussion", "discuss.php?d=$discussion->id", "$discussion->id",
                           $cm->id);
            } else {
                add_to_log($course->id, "forum", "move discussion", "discuss.php?d=$discussion->id", "$discussion->id");
            }
            $discussionmoved = true;
            
            require_once('rsslib.php');
            require_once($CFG->libdir.'/rsslib.php');

            // Delete the RSS files for the 2 forums because we want to force
            // the regeneration of the feeds since the discussions have been
            // moved.
            if (!forum_rss_delete_file($forum) || !forum_rss_delete_file($sourceforum)) {
                notify('Could not purge the cached RSS feeds for the source and/or'.
                       'destination forum(s) - check your file permissionsforums');
            }
        } else {
            error('You can\'t move to that forum - it doesn\'t exist!');
        }
    }

    $logparameters = "d=$discussion->id";
    if ($parent) {
        $logparameters .= "&amp;parent=$parent";
    }

    if ($cm = get_coursemodule_from_instance("forum", $forum->id, $course->id)) {
        add_to_log($course->id, "forum", "view discussion", "discuss.php?$logparameters", "$discussion->id", $cm->id);
    } else {
        add_to_log($course->id, "forum", "view discussion", "discuss.php?$logparameters", "$discussion->id");
    }

    unset($SESSION->fromdiscussion);

    if ($mode) {
        set_user_preference('forum_displaymode', $mode);
    }

    $displaymode = get_user_preferences('forum_displaymode', $CFG->forum_displaymode);

    if ($parent) {
        if (abs($displaymode) == 1) {  // If flat AND parent, then force nested display this time
            $displaymode = 3;
        }
        $navtail = '';
    } else {
        $parent = $discussion->firstpost;
        $navtail = '-> '.format_string($discussion->name);
    }
    
    if (!forum_user_can_view_post($parent, $course, $cm, $forum, $discussion)) {
        error('You do not have permissions to view this post', "$CFG->wwwroot/mod/forum/view.php?f=$forum->id");
    }

    if (! $post = forum_get_post_full($parent)) {
        error("Discussion no longer exists", "$CFG->wwwroot/mod/forum/view.php?f=$forum->id");
    }

    if (forum_tp_can_track_forums($forum) && forum_tp_is_tracked($forum) && 
        $CFG->forum_usermarksread) {
        if ($mark == 'read') {
            forum_tp_add_read_record($USER->id, $postid, $discussion->id, $forum->id);
        } else if ($mark == 'unread') {
            forum_tp_delete_read_records($USER->id, $postid);
        }
    }


    if (empty($navtail)) {
        $navtail = "-> <a href=\"discuss.php?d=$discussion->id\">".
                    format_string($discussion->name,true)."</a> -> ".
                    format_string($post->subject);
    }
    if ($forum->type == 'single') {
        $navforum = '';
    } else {
        $navforum = "<a href=\"../forum/view.php?f=$forum->id\">".
                     format_string($forum->name,true)."</a> ";
    }
    $navmiddle = "<a href=\"../forum/index.php?id=$course->id\">".
                  get_string("forums", "forum").'</a> -> '.$navforum;

    $searchform = forum_search_form($course);

	// Get the users whose annotations are to be shown
	$annotationUser = get_user_preferences( 'annotation_user', null );
	if ( null == $annotationUser )
	{
		$annotationUser = isguest() ? null : $USER->username;
		set_user_preference( 'annotation_user', $annotationUser );
	}
		
//	$annotationUser = array_key_exists( 'anuser', $_GET ) ? $_GET[ 'anuser' ] : $USER->username;
	
	// Begin Annotation Code
	// refUrl is the relative URL to this resource from the server root (i.e., it should start with '/')
	// urlBase is the portion of the URL preceding refUrl (i.e, it should be of the form http://xxx );
	//$rootpath = parse_url( $CFG->wwwroot );
	//$rootpath = $rootpath[ 'path' ];
	$urlBase = $CFG->wwwroot;
	$x = strpos( $urlBase, '//' ) + 2;
	$x = strpos( $urlBase, '/', $x );
	$urlBase = substr( $urlBase, 0, $x );
	$refUrl = "/mod/forum/discuss.php?d=$d";  // used to start with $rootpath
	// Check whether the annotation show/hide preference is set;  if not, set it (need to do this because the AJAX
	// service lacks permission to set unknown preferences)
	$showAnnotationsPref = get_user_preferences( 'show_annotations', null );
	if ( null == $showAnnotationsPref )
		set_user_preference( 'show_annotations', 'false' );
	// Check whether the smartcopy on/off preference is set;  if not, set it (see above)
	$smartcopyPref = get_user_preferences( 'smartcopy', null );
	if ( null == $smartcopyPref )
		set_user_preference( 'smartcopy', 'false' );
	$showSplashPref = get_user_preferences( 'annotations.splash', null );
	if ( null == $showSplashPref )
	{
		set_user_preference( 'annotations.splash', 'true' );
		$showSplashPref = true;
	}
	if ( null == get_user_preferences( 'annotations.note-edit-mode', null ) )
		set_user_preference( 'annotations.note-edit-mode', 'freeform' );
	

	$meta = "<link rel='stylesheet' type='text/css' href='$CFG->wwwroot/annotation/marginalia/marginalia.css'/>\n"
		. "<link rel='stylesheet' type='text/css' href='$CFG->wwwroot/annotation/annotation-styles.php'/>\n";
	$anScripts = listMarginaliaJavascript( );
	for ( $i = 0;  $i < count( $anScripts );  ++$i )
		$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/marginalia/".$anScripts[$i]."'></script>\n";	
	$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/marginalia/smartcopy.js'></script>\n";	
	$meta .= "<script language='JavaScript' type='text/javascript' src='discuss.js'></script>\n";
	$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/marginalia-config.js'></script>\n";
	$meta .= "<script language='JavaScript' type='text/javascript' src='$CFG->wwwroot/annotation/marginalia-strings.js'></script>\n";
	$meta .= "<script language='JavaScript' type='text/javascript'>\n";
	$meta .= "function myOnload() {\n";
	$meta .= " var moodleRoot = '".htmlspecialchars($CFG->wwwroot)."';\n";
	$meta .= " var url = '".htmlspecialchars($refUrl)."';\n";
	$meta .= ' var username = \''.htmlspecialchars($USER->username)."';\n";
	$meta .= ' discussMarginalia = new DiscussMarginalia( url, moodleRoot, username, {'."\n"
		. '  enableSmartcopy: '.('true'==$smartcopyPref?'true':'false')."\n"
		. '  , showAnnotations: '.('true'==$showAnnotationsPref?'true':'false')."\n"
		. '  , anuser: \''.htmlspecialchars($annotationUser)."'\n";
	if ( $showSplashPref == 'true' )
		$meta .= '  , splash: \''.htmlspecialchars(get_string('splash','marginalia')).'\'';
	$meta .= '  } );'."\n";
	$meta .= "/* showSplashPref=$showSplashPref */\n";
	$meta .= " discussMarginalia.onload();\n";
	$meta .= "}\n";
	$meta .= "addEvent(window,'load',myOnload);\n";
	$meta .= "</script>\n";

	// I'm perverting the meta argument here, but there's no way provided to do this otherwise that I 
	// can see #GEOF#
    if ($course->id != SITEID) {
        print_header("$course->shortname: ".format_string($discussion->name), $course->fullname,
                     "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->
                      $navmiddle $navtail", "", $meta, true, $searchform, navmenu($course, $cm));
    } else {
        print_header("$course->shortname: ".format_string($discussion->name), $course->fullname,
                     "$navmiddle $navtail", "", $meta, true, $searchform, navmenu($course, $cm));
    }


/// Check to see if groups are being used in this forum
/// If so, make sure the current person is allowed to see this discussion
/// Also, if we know they should be able to reply, then explicitly set $canreply

    if ($forum->type == 'news') {
        $capname = 'mod/forum:replynews';
    } else {
        $capname = 'mod/forum:replypost';
    }
    
    $groupmode = groupmode($course, $cm);  
    if ($canreply = has_capability($capname, $modcontext)) {
         
        if ($groupmode && !has_capability('moodle/site:accessallgroups', $modcontext)) {   
            // Groups must be kept separate
            //change this to ismember
            $mygroupid = mygroupid($course->id); //only useful if 0, otherwise it's an array now
            if ($groupmode == SEPARATEGROUPS) {
                require_login();

                if ((empty($mygroupid) and $discussion->groupid == -1) || (ismember($discussion->groupid) || $mygroupid == $discussion->groupid)) {
                    // $canreply = true;
                } elseif ($discussion->groupid == -1) {
                    $canreply = false;
                } else {
                    print_heading("Sorry, you can't see this discussion because you are not in this group");
                    print_footer($course);
                    die;
                }

            } else if ($groupmode == VISIBLEGROUPS) {
                $canreply = ( (empty($mygroupid) && $discussion->groupid == -1) ||
                    (ismember($discussion->groupid) || $mygroupid == $discussion->groupid) );
            }
        }
    } else { // allow guests to see the link
        $coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);
        if (has_capability('moodle/legacy:guest', $coursecontext, NULL, false)) {  // User is a guest here!
            $canreply = true;
        }
    }

/// Print the controls across the top

    echo '<table width="100%" class="discussioncontrols"><tr><td>';

    // groups selector not needed here

    echo "</td><td>";
    forum_print_mode_form($discussion->id, $displaymode);
    echo "</td><td>";

    if ($forum->type != 'single'
                && has_capability('mod/forum:movediscussions', $modcontext)) {
        
        // Popup menu to move discussions to other forums. The discussion in a
        // single discussion forum can't be moved.
        if ($forums = get_all_instances_in_course("forum", $course)) {
            if ($course->format == 'weeks') {
                $strsection = get_string("week");
            } else {
                $strsection = get_string("topic");
            }
            $section = -1;
            foreach ($forums as $courseforum) {
                if (!empty($courseforum->section) and $section != $courseforum->section) {
                    $forummenu[] = "-------------- $strsection $courseforum->section --------------";
                }
                $section = $courseforum->section;
                if ($courseforum->id != $forum->id) {
                    $url = "discuss.php?d=$discussion->id&amp;fromforum=$discussion->forum&amp;move=$courseforum->id";
                    $forummenu[$url] = format_string($courseforum->name,true);
                }
            }
            if (!empty($forummenu)) {
                echo "<div style=\"float:right;\">";
                echo popup_form("$CFG->wwwroot/mod/forum/", $forummenu, "forummenu", "",
                                 get_string("movethisdiscussionto", "forum"), "", "", true);
                echo "</div>";
            }
        }
    }
	echo "</td>\n<td id='annotation-controls'>";
	// Show/Hide Annotations buttons
	//echo "<button id='show-all-annotations' onclick='showAllAnnotations(\"$refUrl#*\");window.preferenceService.setPreference(\"show_annotations\",\"true\",null);'>Show Annotations</button>\n";
	//echo "<button id='hide-all-annotations' onclick='hideAllAnnotations(\"$refUrl#*\");window.preferenceService.setPreference(\"show_annotations\",\"false\",null);'>Hide Annotations</button>\n";
	
	// Annotation Help
	$helpTitle = 'Help with Annotations';
    $linkobject = '<span class="helplink"><img class="iconhelp" alt="'.$helpTitle.'" src="'.$CFG->pixpath .'/help.gif" /></span>';
    echo link_to_popup_window ('http://localhost/moodle/help.php?module=forum&amp;file=annotate.html&amp;forcelang=', 'popup',
                                     $linkobject, 400, 500, $helpTitle, 'none', true);

	$summaryQuery = new AnnotationSummaryQuery( $refUrl, null, null, null );
	$userList = get_records_sql( $summaryQuery->listUsersSql( ) );
	
	echo "<select name='anuser' id='anuser' onchange='window.discussMarginalia.changeAnnotationUser(this,\"$refUrl\");'>\n";
	echo " <option ".($showAnnotationsPref!='true'?"selected='selected' ":'')
		."value=''>".get_string('hide_annotations','marginalia')."</option>\n";
	if ( ! isguest() )
	{
		echo " <option ".($showAnnotationsPref=='true'&&$USER->username==$annotationUser?"selected='selected' ":'')
			."value='".htmlspecialchars($USER->username)."'>".get_string('my_annotations','marginalia')."</option>\n";
	}
	if ( $userList )
	{
		foreach ( $userList as $user )
		{
			if ( $user->username != $USER->username )
			{
				echo " <option ".($user->username==$annotationUser?"selected='selected' ":'')
					."value='".htmlspecialchars($user->username)."'>".htmlspecialchars($user->firstname.' '.$user->lastname)."</option>\n";
			}
		}
	}
	echo "</select>\n";
	
	// Show the annotation summary button
	$summaryUrl = $CFG->wwwroot."/annotation/summary.php?user=".urlencode($USER->username)
		."&url=".urlencode( "$CFG->wwwroot/mod/forum/discuss.php?d=$d" );
	echo " <a id='annotation-summary-link' href='".htmlspecialchars($summaryUrl)."'"
		. " title='".htmlspecialchars(get_string('summary_link_title','marginalia'))
		."'>".htmlspecialchars(get_string('summary_link','marginalia'))."</a>\n";
    echo "</td></tr></table>";

    if (!empty($forum->blockafter) && !empty($forum->blockperiod)) {
        $a->blockafter = $forum->blockafter;
        $a->blockperiod = get_string('secondstotime'.$forum->blockperiod);
        notify(get_string('thisforumisthrottled','forum',$a));
    }

    if ($forum->type == 'qanda' && !has_capability('mod/forum:viewqandawithoutposting', $modcontext) &&
                !forum_user_has_posted($forum->id,$discussion->id,$USER->id)) {
        notify(get_string('qandanotify','forum'));
    }

    if (isset($discussionmoved)) {
        notify(get_string("discussionmoved", "forum", format_string($forum->name,true)));
    }


/// Print the actual discussion
    if (!$canviewdiscussion) {
        notice(get_string('noviewdiscussionspermission', 'forum'));
    } else {
        $canrate = has_capability('mod/forum:rate', $modcontext);
        forum_print_discussion($course, $forum, $discussion, $post, $displaymode, $canreply, $canrate);
    }
    
    print_footer($course);
    

?>
