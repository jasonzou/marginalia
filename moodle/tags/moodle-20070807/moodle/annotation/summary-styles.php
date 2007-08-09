<?PHP
header( 'Content-type: text/css' );
/*
if (!isset($themename)) {
	$themename = NULL;
}

$nomoodlecookie = true;
require_once("../config.php");
$themeurl = style_sheet_setup(filemtime("styles.php"), 300, $themename);
*/
?>

form#annotation-search {
	display: block;
	text-align: center;
	margin: 1em auto;
}

form#annotation-search fieldset {
	border: none;
	margin: 0;
	padding: 0;
	background: none;
}

p.query {
	margin: 2em 0;
}

p.error em.range-error {
	color: white;
	background: red;
	font-weight: bold;
	width: 1em;
	display: block;
	float: left;
	font-style: normal;
	text-align: center;
	margin-right: .5ex;
}

table.annotations {
	/* These aren't really compatible, but for practical purposes they work
	 * (man, I hate the W3C box model): */
	margin: 1em 2em ;
	width: 90%;
}

table.annotations th,
table.annotations td {
	padding:3px ;
	margin: 0 ;
	vertical-align: top ;
}

table.annotations tbody th {
	background: none ;
	font-weight: normal ;
	font-size: 80% ;
	text-align: left ;
}

table.annotations tbody tr th,
table.annotations tbody tr td {
	/*border-top: <?PHP echo $THEME->cellcontent; ?> 1px solid ;*/
	border-top:  white 2px solid;
}

table.annotations tbody tr.fragment.first th,
table.annotations tbody tr.fragment.first td {
	border-top: black 1px solid;
}

table.annotations thead th {
	text-align: left ;
	font-weight: bold ;
	background: none ;
	padding-top: 1em;
}

table.annotations thead th h3 {
	display: inline;
	text-transform: capitalize;
}

table.annotations tbody td.quote {
	background: white ;
	width: 40%;
}

table.annotations tbody td.note {
	font-size: 80% ;
	width: 30%;
}

table.annotations tbody td.controls {
	font-size: 80%;
	white-space: nowrap;
}

table.annotations tbody td.anuser {
	font-size: 80%;
}


/* buttons */
table.annotations button {
	padding-left: .25ex ;
	padding-right: .25ex;
	background: none;
	border: none;
	cursor: pointer;
}

table.annotations button:hover {
	font-weight: bold;
}

/* smartcopy tip */
p#smartcopy-help {
	margin:  3em 4em 0 4em;
	font-size: smaller;
	position: relative;
}

.tip {
	font-weight: bold;
	position: 	absolute;
	width: 3em;
	left: -3em;
	text-transform: uppercase;
}

.tip:after {
	content: ':';
}
