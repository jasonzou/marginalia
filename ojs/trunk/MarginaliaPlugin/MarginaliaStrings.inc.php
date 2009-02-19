<?php

/*
require('../../../includes/driver.inc.php');
initSystem();
// Localization seems to be spitting out HTTP headers (!?), which conflict with
// this page.  So start by performing a bit of localization.
$t = Locale::translate( '' );
*/

function getMarginaliaStringsJS( )
{
header( 'Content-type: text/javascript' );

echo "/*\n";
echo " * Languages for annotation Javascript\n";
echo " */\n\n";
echo "/*\n";
echo " * Fetch a localized string\n";
echo " * This is a function so that it can be replaced with another source of strings if desired\n";
echo " * (e.g. in a database).  The application uses short English-language strings as keys, so\n";
echo " * that if the language source is lacking the key can be returned instead.\n";
echo " */\n\n";
echo "function getLocalized( s )\n{\n";
echo "\treturn LocalizedAnnotationStrings[ s ];\n}\n\n";

echo "LocalizedAnnotationStrings = {\n";

	
	echo "'public annotation' : '".Locale::translate('plugins.generic.marginalia.public_annotation')."',\n";
	
	echo "'private annotation' : '".Locale::translate('plugins.generic.marginalia.private_annotation')."',\n";
		
	echo "'delete annotation button' : '".Locale::translate('plugins.generic.marginalia.delete_annotation_button')."',\n";
	
	echo "'annotation link button' : '".Locale::translate('plugins.generic.marginalia.annotation_link_button')."',\n";
	
	echo "'annotation link label' : '".Locale::translate('plugins.generic.marginalia.annotation_link_label')."',\n";
	
	echo "'delete annotation link button' : '".Locale::translate('plugins.generic.marginalia.delete_annotation_link_button')."',\n";
	
	
	
	echo "'action annotate button' : '".Locale::translate('plugins.generic.marginalia.action_annotate_button')."',\n";
	
	echo "'action insert before button' : '".Locale::translate('plugins.generic.marginalia.action_insert_before_button')."',\n";
	
	echo "'action insert after button' : '".Locale::translate('plugins.generic.marginalia.action_insert_after_button')."',\n";
	
	echo "'action replace button' : '".Locale::translate('plugins.generic.marginalia.action_replace_button')."',\n";
	
	echo "'action delete button' : '".Locale::translate('plugins.generic.marginalia.action_delete_button')."',\n";
	
	
	
	echo "'browser support of W3C range required for annotation creation' : '".Locale::translate('plugins.generic.marginalia.browser_support_of_W3C_range_required_for_annotation_creation')."',\n";
	
	echo "'select text to annotate' : '".Locale::translate('plugins.generic.marginalia.select_text_to_annotate')."',\n";
	
	echo "'invalid selection' : '".Locale::translate('plugins.generic.marginalia.invalid_selection')."',\n";
	
	echo "'corrupt XML from service' : '".Locale::translate('plugins.generic.marginalia.corrupt_XML_from_service')."',\n";
	
	echo "'note too long' : '".Locale::translate('plugins.generic.marginalia.note_too_long')."',\n";
	
	echo "'quote too long' : '".Locale::translate('plugins.generic.marginalia.quote_too_long')."',\n";
	
	echo "'zero length quote' : '".Locale::translate('plugins.generic.marginalia.zero_length_quote')."',\n";
	
	echo "'quote not found' : '".Locale::translate('plugins.generic.marginalia.quote_not_found')."',\n";
	
	echo "'create overlapping edits' : '".Locale::translate('plugins.generic.marginalia.create_overlapping_edits')."',\n";
	
	echo "'blank quote and note' : '".Locale::translate('plugins.generic.marginalia.blank_quote_and_note')."',\n";
	
	
	
	echo "'warn delete' : '".Locale::translate('plugins.generic.marginalia.warn_delete')."',\n";
	
		
	
	
	
	

	
	
	
	
	

echo "\t'lang' : 'en'\n";
echo "};\n";
}
?>