<?php
header( 'Content-type: text/css' );
/*
//if (!isset($themename)) {
//	$themename = NULL;
//}

$nomoodlecookie = true;
require_once("../config.php");
//$themeurl = style_sheet_setup(filemtime("annotation-styles.php"), 300, $themename);
*/
?>

table td#annotation-controls {
	white-space:  nowrap;
}

#annotation-summary-link,
#annotation-editkeywords-link {
	font-size: smaller;
}

.mia_margin .splash {
	font-size: smaller;
}

/* Edit | Delete | Reply links at bottom of post */
.forumpostmessage .commands,
.forumpostmessage .commands a {
	text-align: right ;
	font-size: 80% ;
	color: black ;
	font-weight: normal ;
}

.forumpost {
	position: relative;
}

.forumpost td.content {
	vertical-align: top;
}

.forumpost .commands button.smartquote {
	display: inline;
	background: none;
	border: none;
	padding: 0;
	margin: 0;
	font-family: inherit;
	font-size: inherit;
	color: blue;
	cursor: pointer;
}

.forumpost .commands button.smartquote:hover span {
	text-decoration: underline;
	color: red;
}

.mia_margin li.hover,
.em.mia_annotation.hover,
.em.mia_annotation.hover ins,
.em.mia_annotation.hover del {
	color: red;
}

.mia_margin-td {
	width: 0;
}

.mia_annotated .mia_margin {
	border: #f8f8f8 1px solid;
	cursor: pointer;
}

.mia_annotated .mia_margin:hover {
	border: #aaa 1px dotted;
}

/* this rigamarole with both changing the column width *and* hiding the elements within it
 * is because IE is a load of steaming horse manure */
.mia_margin ol,
.mia_margin a.range-mismatch {
	display: none;
}

.mia_annotated .mia_margin-td {
	width: 30%;
}

.mia_annotated .mia_margin {
	position: relative;
	/* unfortunately the background color has been interfering with the rounded corners
	of the default moodle theme, so for now it's disabled */
	/*background-color:  #f8f8f8; <?PHP echo $THEME->cellcontent2; ?>;*/
}

.mia_annotated .mia_margin ol {
	display: block;
	margin-left: 1ex;
}

.mia_margin {
	position: relative;
	padding: 1px;
}

.mia_margin ol {
	margin: 0;
	padding: 0 ;
	right: 0;
	width: 16em;
	margin-bottom: 1.2em;
	list-style-type: none;
}

.mia_margin ol li {
	clear: both;
	position: relative;
}

.mia_recent:after {
	position: absolute;
	top: .1em;
	right: 100%;
	padding-right: 2px;
	color: red;
	content: '*';
}

.mia_margin ol li.active {
	color: red ;
}

.mia_margin ol li button {
	background: none ;
	font-size: 12px ;
}

.mia_margin ol textarea {
	vertical-align: top ;
	border: none;
	font-family: inherit;
	width: 94%;
}

/* colors for other users' annotations
.hentry em.mia_annotation { background-color: #77f3fd ; }
.hentry .content em.mia_annotation em.mia_annotation { background: #70d4ec; }
.hentry .content em.mia_annotation em.mia_annotation em.mia_annotation { background: #66c6d8; }
*/

button#hide-all-annotations,
body.mia_annotated button#show-all-annotations {
	display: none ;
}

body.mia_annotated button#hide-all-annotations {
	display: inline;
}

@media print
{
	.forumpost em.mia_annotation {
		text-decoration: underline ;
	}
}


