<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<!--
	Marginalia Demo by Geof Glass, www.geof.net/code/annotation
	The comments in this file are here to illustrate how to add annotation to a page.  I
	have included comments about Moodle also, as this is a clearer example than the actual
	Moodle code (which includes all the features of Moodle forums, not just annotations).
	This looks complicated with the comments.  Scroll to the second entry below (id="m2")
	to see how simple this stuff really is in practice.
	
	$Id$
	-->
	<title>Annotation Example</title>
	<!-- These all need to be included.  The order
	for inclusion matters for some of them. -->
	<script type="text/javascript" src="marginalia/3rd-party.js"></script>
	<script type="text/javascript" src="marginalia/3rd-party/cssQuery.js"></script>
	<script type="text/javascript" src="marginalia/3rd-party/cssQuery-level2.js"></script>
	<script type="text/javascript" src="marginalia/3rd-party/cssQuery-standard.js"></script>

	<script type="text/javascript" src="marginalia/log.js"></script>
	<script type="text/javascript" src="marginalia/domutil.js"></script>
	<script type="text/javascript" src="marginalia/prefs.js"></script>
	<script type="text/javascript" src="marginalia/html-model.js"></script>
	<script type="text/javascript" src="marginalia/ranges.js"></script>
	<script type="text/javascript" src="marginalia/SequenceRange.js"></script>
	<script type="text/javascript" src="marginalia/XPathRange.js"></script>
	<script type="text/javascript" src="marginalia/annotation.js"></script>
	<script type="text/javascript" src="marginalia/post-micro.js"></script>
	<script type="text/javascript" src="marginalia/linkable.js"></script>
	<script type="text/javascript" src="marginalia/link-ui-clicktolink.js"></script>
	
	<script type="text/javascript" src="marginalia/marginalia.js"></script>
	<script type="text/javascript" src="marginalia/blockmarker-ui.js"></script>
	<script type="text/javascript" src="marginalia/highlight-ui.js"></script>
	<script type="text/javascript" src="marginalia/note-ui.js"></script>
	<script type="text/javascript" src="marginalia/link-ui.js"></script>
	<script type="text/javascript" src="marginalia/link-ui-simple.js"></script>

	<script type="text/javascript" src="marginalia/smartcopy.js"></script>
	<script type="text/javascript" src="marginalia/RangeInfo.js"></script>
	<script type="text/javascript" src="marginalia/rest-annotate.js"></script>
	<script type="text/javascript" src="marginalia/rest-prefs.js"></script>
	<script type="text/javascript" src="marginalia/rest-keywords.js"></script>
	<script type="text/javascript" src="marginalia/marginalia-direct.js"></script>
	<link rel="stylesheet" type="text/css" href="marginalia/marginalia-direct.css"/>
	
	<!-- These are implementations of how to fetch annotations, set preferences, and
	of localized strings.  They will likely be different on every system. -->
	<script type="text/javascript" src="marginalia-strings.js"></script>
	<script type="text/javascript" src="static-annotate.js"></script>
	<script type="text/javascript" src="static-prefs.js"></script>
	
	<!-- This stylesheet includes styling for the annotation margin.  The code makes heavy
	uses of CSS, so a stylesheet provides extensive controls over the look and feel of
	the interface (e.g. add graphics or icons, change the look and position of the delete
	button, number the annotation notes in the margin). -->
	<link rel="stylesheet" type="text/css" href="marginalia/marginalia.css"/>
	
	<!-- Some of the formatting and layout must be custom -->
	<link rel="stylesheet" type="text/css" href="index.css"/>
	
	<!-- Custom Javascript to set up Marginalia.  See here for essential code: -->
	<script type="text/javascript" src="index.js"></script>
	<script type="text/javascript">
		function myOnLoad( )
		{
			// Comment this out if you don't want the debug tab/window poppping up:
			initLogging();
			// Change the first parameter below to match the location of the demo
			// on your server.  Don't change the second parameter unless you also
			// update the bookmark links in the HTML (a rel="bookmark").  Ideally,
			// it (and they) should match the URL of this page.
			<?php
			require_once 'config.php';
			global $CFG;
			$userid = array_key_exists( "u", $_GET ) ? $_GET[ "u" ] : 'anonymous';
			?>
			demoOnLoad(
				'<?php echo htmlspecialchars($userid);?>',
				'<?php echo htmlspecialchars($CFG->wwwroot);?>',
				'<?php echo htmlspecialchars($CFG->annotatedUrl);?>/#*'
			);
		}
	</script>
</head>
<!-- showAllAnnotations must be called to display all annotations on the page.  They can be 
hidden again with a call to hideAllAnnotations.  You may want to create a button or other
control to do this (as in Moodle).  The URL here is the URL of this page, although this 
depends on the back-end implementation. In Moodle, it is the URL of the forum page (or
whatever).  Here, the * is a wildcard.  The wildcarding convention and support are up to 
the back-end implementation;  most applications with only one region of content per web page 
probably have no use for it.  The wildcard matches against the URL for each entry (look for
the entrylink class). -->
<body onload='myOnLoad()'>

	<form method="get">
		<label for="u">Logged in as <?php echo htmlspecialchars($userid)?>.  Change to </label>
		<select name="u" id="u">
			<option value="anonymous"  <?php if ($userid=='anonymous') echo 'selected="selected"';?>>anonymous</option>
			<option value="ashok" <?php if ($userid=='ashok') echo 'selected="selected"';?>>ashok</option>
			<option value="flavio" <?php if ($userid=='flavio') echo 'selected="selected"';?>>flavio</option>
			<option value="geof" <?php if ($userid=='geof') echo 'selected="selected"';?>>geof</option>
			<option value="jean" <?php if ($userid=='jean') echo 'selected="selected"';?>>jean</option>
			<option value="millie" <?php if ($userid=='millie') echo 'selected="selected"';?>>millie</option>
		</select>
		<input type="submit" value="Log in"/>
	</form>

	<!-- I'm using the article list for styling in the DOM.  There's no need for it, and
	no reason your list of entries should be an ol, or that your entries should be li
	elements. -->
	<ol id="articles">
	
		<!-- Each annotatable entry on the page must have a unique ID value and a class name
		of xentry.  It also requires a number of pieces of data inside elements with certain
		class names. -->
		<li id="m1" class="hentry">
			<!-- Every entry must have a field with a class of "title".  This is stored with 
			the annotation in the database and shown on the summary page in Moodle. -->
			<h3 class="entry-title">Web Annotation Demo</h3>
			
			<!-- -->
			<div class="markers">
			</div>
			
			<!-- Every entry must have a content area marked by the "content" class.  The 
			content of this area should not change (so there shouldn't be user controls and 
			stuff in here, just content - unless you instruct _skipContent to avoid it, that
			is), as highlights are located relative to the start of this element.  Arbitrary 
			HTML is permitted within the content element.  I should point out though that 
			the annotation engine uses element ids and class values like annot91 (annot + a 
			number), so don't use those elswhere on the page. It also uses the class 
			"annotation" (and a whole bunch more - look for _CLASS values at the top of
			marginalia.js and post-micro.js). -->
			<div class="entry-content">
				<p>This is a demonstration of <a href="http://www.geof.net/code/annotation/">Marginalia</a>, my web 
				annotation implementation.  You can highlight a passage of text, then type a
				note in the margin to associate with it.  The complete version saves your annotations,
				but for this demo I have not connected up a database:  if you reload this page,
				your changes will disappear.</p>
				
				<p>This version of Marginalia only works in Firefox, as I have not yet updated
				Internet Explorer support.  Other browsers may
				display the annotations, but as far as I know you cannot create them with
				other browsers (this may change as W3C standards are more widely adopted).
				Note that previous versions of this demo would cause IE to crash;  this has long
				been fixed, nonetheless use of this page is <a href="../LICENSE.txt">at your own risk</a>.</p>
				
				<p>In addition to annotation, Firefox users will be able to try smart copy.
				Select some text, then copy it somewhere (e.g. into an email message, a word
				processor document, or the text box at the bottom of this page).  The copied
				text will be prefixed by information about the source of the copy.  This is
				intended as a feature for discussion forums, so that users can more easily refer
				to other messages when they quote them.</p>
			</div>
			
			<!-- The metadata class is something I added for styling.  It isn't used by 
			annotation. -->
			<p class="metadata">
				<!-- The entrylink is the URL for this entry.  It is the key used to look up 
				annotations for this page (see the wildcard match in showAllAnnotations above).  
				In Moodle, this is nothing like the URL passed to showAllAnnotations;  the 
				necessary logic to connect the two is in the AnnotationSummaryQuery class in 
				lib.php.  This stand-alone implementation is simpler.  Note that this URL 
				should always be complete, starting with the protocol (http://), so that users
				can follow the link to find the annotated resource (e.g. by clicking on a link
				in a summary list of annotations, or in the Atom feed emitted by the server).
				For security reasons, only http and https protocols are permitted.  The 
				fragment identifier (#m1) is used here because there is more than one 
				annotatable region on this page (also the case in Moodle). -->
				<a rel="bookmark" href="<?php echo htmlspecialchars($CFG->annotatedUrl);?>/#m1">#</a>
				<!-- The published field is used by smartcopy, but isn't necessary for annotation.
				Note the title attribute and its date format. -->
				Last Updated <abbr class="published" title="2005-07-21T19:00-08:00">21 July 2005</abbr>
				<!-- The author, like the title, is stored with the annotation. -->
				by <span class="author">Geof Glass</span>
			</p>
			
			<!-- There must be an element with a class of "notes", and that element must contain
			exactly one ol element.  The ol is the actual annotation margin.  For note positioning to
			work, it should be horizontally adjacent to the content area.  It doesn't matter how you
			achieve that, whether through a nice CSS layout or a nasty table one. -->
			<div class="notes">
				<!-- Without this button to call createAnnotation there would be no way to make new 
				annotations.  All other annotation controls are automatically added by the Javascript, 
				but this one is under the control of the application (you could call this function 
				from a pop-up menu, a button at the top of the page, whatever you want). -->
				<button class="createAnnotation" onclick="clickCreateAnnotation(event,'m1',SelectActionNoteEditor)" title="Click here to create an annotation">&gt;</button>
				<ol>
					<li></li>
				</ol>
			</div>
		</li>
	
		<li id="m2" class="hentry">
			<h3 class="entry-title">Detailed Instructions</h3>
			<div class="markers">
			</div>
			<div class="entry-content">
				<h4>Annotation</h4>
				
				<table>
					<thead>
						<tr>
							<th>Action</th>
							<th>User Interface</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>Creating an annotation</td>
							<td>Select some text (typically with the mouse), then click on 
							the tall button between the text and the annotations in the margin.
							This will create a new annotation box on the right;  type in text and 
							click elsewhere or type <kbd>Enter</kbd> when you're done.  Note that 
							the selection can start in one paragraph and end in another, contain 
							part of an HTML heading, etc.</td>
						</tr>
						<tr>
							<td>Editing a note</td>
							<td>Click on the note in the margin.  It will turn into an
							edit box.  Make your changes, then click elsewhere or type 
							<kbd>Enter</kbd> to save them.</td>
						</tr>
						<tr>
							<td>Picking a note from a list</td>
							<td>The system allows margin notes to be predefined and chosen from
							a drop down list.  When editing the note, click the minus sign to
							the left of the edit area to pick from a list.  When the list is
							displayed, click the plus sign to return to freeform editing.</td>
						</tr>
						<tr>
							<td>Deleting an annotation</td>
							<td>Click on the <kbd>x</kbd> icon to the right of the note in
							the margin.  The note and the associated highlight will be removed.</td>
						</tr>
						<tr>
							<td>Make an annotation private</td>
							<td>The black diamond or open circle icon to the left of the <kbd>x</kbd> 
							indicates whether the annotation is public or private.  For this demo, that 
							makes no difference, but in a discussion forum (e.g. in Moodle), this can 
							allow you to selectively share your annotations.</td>
						</tr>
						<tr>
							<td>Creating a hyperlink</td>
							<td>Each annotation can include a hyperlink to a URL.  To create the URL,
							click on the strange icon (curretly a sunburst) to the right of the note,
							then type in or paste the URL into the edit box.  Click somewhere else
							or type <kbd>Enter</kbd> to save the link.  The link will appear as a
							sunburst embedded in the document text following the highlight
							associated with the note.</td>
						</tr>
						<tr>
							<td>Editing or deleting a hyperlink</td>
							<td>To edit or delete a hyperlink, click on the link icon (the sunburst).
							You can edit the link in the text box, or click the <kbd>x</kbd> icon to
							delete the link.</td>
						</tr>
					</tbody>
				</table>
				
				<h4>Smart Copy</h4>
				
				<p>Smart copy is a hack which allows copy-paste operations to include additional
				context information, such as the title, author, and date of the post from which
				the copy was made.  Here's what you can try:</p>
				
				<ol>
					<li>Select some text in the body of one of the messages above by
					clicking and dragging with the mouse.  The selection 
					must begin and end within the message body.</li>
					
					<li>Copy the selected text as you normally would (e.g. with <kbd>Ctrl-C</kbd>
					or the <kbd>Copy</kbd> option in your browser's <kbd>Edit</kbd> menu).</li>
					
					<li>Paste the text into the text area below.  If you're
					using Firefox, you will see the additional context information.</li>
					
					<li>Now try copying into a word processor or new email message.  This time,
					the title of the source post will be a link.</li>
					
					<li>Finally, there's a bug:  try copying italicized text, such as the word
					"faculty" in the second mesasge.  The context information will also be
					italicized.  Sorry, I did say it was a hack.</li>
				</ol>
			
			</div>
			
			<p class="metadata">
				<a rel="bookmark" href="<?php echo htmlspecialchars($CFG->annotatedUrl);?>/#m2">#</a>
				Last Updated <abbr class="published" title="2005-07-21T19:00-08:00">21 July 2005</abbr>
				by <span class="author">Geof Glass</span>
			</p>
			
			<div class="notes">
				<button class="createAnnotation" onclick="clickCreateAnnotation(event,'m2')" title="Click here to create an annotation">&gt;</button>
				<ol>
					<li></li>
				</ol>
			</div>
		</li>
		
		<li id="m3" class="hentry">
			<h3 class="entry-title">For Developers</h3>
			<div class="entry-content">
				<p>Please view the HTML source for this page to see how simple it is to add annotation
				support to a page or application.  Comments in the HTML describe what Javascript and
				CSS classes are required to make this work.  For further instructions, download the
				stand-alone source code.</p>
			</div>
			
			<p class="metadata">
				<a rel="bookmark" href="<?php echo htmlspecialchars($CFG->annotatedUrl);?>/#m3">#</a>
				Last Updated <abbr class="published" title="2005-08-24T11:50-08:00">24 August 2005</abbr>
				by <span class="author">Geof Glass</span>
			</p>
			
			<div class="notes">
				<button class="createAnnotation" onclick="clickCreateAnnotation(event,'m3')" title="Click here to create an annotation">&gt;</button>
				<ol>
					<li></li>
				</ol>
			</div>
		</li>
	</ol>
	
	<h2>Text Area for Smart Copy</h2>
	<form id="form" action="#form" method="get">
		<div>
<textarea cols="80" rows="6">Copy some text from one of the above messages and paste it here (Firefox only):

</textarea>
		</div>
	</form>
	<p>Note: Smartcopy can be toggled on and off by pressing <kbd>Shift</kbd>-<kbd>Ctrl</kbd>-<kbd>S</kbd>.</p>

	<div id="footer">
		<p>This software is made possible by funding from <a href="http://bccampus.ca/">BC Campus</a>
		and support from <a href="http://www.sfu.ca/">Simon Fraser University</a> and SFU's Applied Communication
		Technologies Group and the e-Learning Innovation Centre of the Learning
		Instructional Development Centre at SFU.  The code is Copyright (c) 2005 Geoffrey Glass, and
		is available under the terms of the <a href="LICENSE.txt">GNU General Public License</a>.</p>
	</div>
 
<div id="sidebar">
<h3>Annotation</h3>
<ul>
<li>Demo</li>
<li><a href="http://www.geof.net/code/annotation/features">Features</a></li>
<li><a href="http://www.geof.net/code/annotation/download">Download</a></li>
<li><a href="http://www.geof.net/code/annotation/technical">Technical</a></li>
<li><a href="http://www.geof.net/code/gpl">License</a></li>
</ul>
</div>

</body>
</html>

