<?PHP
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

.hentry td.content {
	vertical-align: top;
}

/* hack button to allow for annotation creation, a result of problems with Moz positioning */
/* it has to be a button, otherwise clicking it loses the selection */
td.control-margin {
	width: 0; /* was xwidth #geof# */
	height: auto;
}

td.control-margin div {
	display: none;
}

.self-annotated td.control-margin {
	vertical-align: top;
	width: 1em;
	padding: 0 ;
}

.self-annotated td.control-margin div {
	display: block;
	position: relative;
	margin: 0 ;
	padding: 0 ;
	width: 100%;
	height: 100%;
}

/* There's a bug with the button:  its height is set to 100%, but when
 * the annotation notes are added they may increase the height of the table.
 * In that case, the button size does not increase to match.
 */
.self-annotated td.control-margin button {
	height: 100%;
	width: 1em;
	border: none;
	border-left:  #eee 1px dotted;
	border-right:  #eee 1px dotted;
	padding: 0;
	padding-left: 1px;
	margin: 0;
	background: none;
	cursor: pointer;
	position: absolute;
	display: block;
	z-index: 1;
}

.self-annotated td.control-margin button span {
	visibility: hidden;
}

/* the hover class is because of IE cluelessness */
.self-annotated td.control-margin button:hover span,
.self-annotated td.control-margin button.hover span {
	visibility: inherit;
}

.self-annotated td.control-margin button:hover,
.self-annotated td.control-margin button.hover {
	font-weight: bold;
	background: #fdf377;   /*should be from the theme, but I'm not sure where that's set in 1.5 yet */
}


/* notes in sidebar */
.notes a.annotation-summary {
	bottom: .25em ;
	margin: 0 auto;
	text-align: center;
	font-size: 80%;
	width: 100%;
	display: block;
}

.notes {
	width: 0;
}

/* this rigamarole with both changing the column width *and* hiding the elements within it
 * is because IE is a load of steaming horse manure */
.notes ol,
.notes a.annotation-summary,
.notes a.range-mismatch {
	display: none;
}

.annotated .notes {
	width: 30% ;
	position: relative;
	/* unfortunately the background color has been interfering with the rounded corners
	of the default moodle theme, so for now it's disabled */
	/*background-color:  #f8f8f8; <?PHP echo $THEME->cellcontent2; ?>;*/
}

.annotated .notes ol,
.annotated .notes .annotation-summary {
	display: block;
}

/* show the mismatch element if annotation-range-mismatch is flagged */ 
table.annotation-range-mismatch .range-mismatch {
	display: block;
	width: 1em;
	margin: 1em auto;
	text-align: center;
	font-weight: bold;
	color: white;
	background: red;
	border: white 2px solid;
}


/* was .annotations > div, but IE can't handle that */

.notes div {
	position: relative;
	padding: 1px;
}

.notes ol {
	list-style-type: none ;
	width: 100%;
	margin: 0;
	padding: 0 ;
	right: 0;
	margin-bottom: 1.2em;
}

.notes ol li {
	font-size: 80%;
	margin: 0 ;
	margin-bottom: 1ex;
	padding: 0;
	min-height: 1.2em;
	cursor: pointer ;
	clear: both;
	xborder: red 1px solid;
}

.notes ol li.active {
	color: red ;
}

.notes ol li button {
	background: none ;
	border: none ;
	float: right ;	
	font-size: 12px ;
	padding: 0;
}

.notes ol textarea {
	vertical-align: top ;
	/* background: #fdf377; */
	background: white;
	border: none;
	font-family: inherit;
	font-size: inherit;
	width: 94%;
}

.notes ol li button:hover {
	font-weight: bold ;
	cursor: pointer ;
}

.notes ol li.dummy {
	height: 1px;
	min-height: 1px;
	cursor: default;
	background: none;
	margin: 0;
	padding: 0;
	font-size: 1px;
	line-height: 1px;
}
/*
.forumpostmain {
	margin-left: 35px ;
	position:relative;
}

.forumpost .heading {
	position: relative ;
	padding: 3px ;
	height: 2.2em ;
}

.forumpostheadertopic p {
	margin: .5ex 0;
}

.forumpost .heading h1 {
	margin: 0 ;
	padding: 0 ;
	font-size:  medium ;
	font-weight: bold ;
}

.forumpost .heading h2 {
	margin: 0 ;
	padding: 0 ;
	font-size:  small;
}

.forumpostmessage {
	padding: 0 3px ;
	margin-right: 20em;
}

.forumpost {
	border:  none ;
	width:  100%;	
}

.forumpost .forumpost {
	width:  auto ;
}

.forumpost .forumpostpicture {
	background-color:  <?PHP echo $THEME->cellcontent2 ?>;
	width:  35px ;
}

.forumpost .forumpostheadertopic {
	background-color:  <?PHP $THEME->cellheading2 ?>;
	width:  100%;
}

.forumpost .forumpost .forumpostheader {
	background-color:  <?PHP $THEME->cellheading ?>;
	width: 100%;
}
*/

.hentry em.annotation {
	font-style: inherit;
}

/* colors for other users' annotations */
.hentry em.annotation { background-color: #77f3fd ; }
.hentry .content em.annotation em.annotation { background: #70d4ec; }
.hentry .content em.annotation em.annotation em.annotation { background: #66c6d8; }

/* colors for current user's annotations */
.self-annotated .hentry em.annotation { background-color: #fdf377 ; }
.self-annotated .hentry .content em.annotation em.annotation { background: #ecd470; }
.self-annotated .hentry .content em.annotation em.annotation em.annotation { background: #d8c666; }

.hentry .notes li.hover,
.hentry .content em.annotation.hover {
	color: red;
	/* outline: red 1px solid;  - not yet supported, and looks funny on margin notes in Safari */
}


button#hide-all-annotations,
body.annotated button#show-all-annotations {
	display: none ;
}

body.annotated button#hide-all-annotations {
	display: inline;
}

/* This is actually for smartcopy */
.hentry .smart-copy {
	display: none;
}

#smartcopy-status {
	position: fixed;
	right: 1em;
	bottom: 1em;
	z-index: 100;
	background: #ffffcc;
	border: #666 1px solid;
	padding: 1ex;
	width: 13em;
	font-size: small;
	opacity: 1;
}

@media print
{
	.forumpost em.annotation {
		text-decoration: underline ;
	}
}


