<?php
// Shared Parts-editor row markup for jobsettings.php, newIssue.php, color.php,
// and modIssue.php's Regular branch. These four dialogs used to each
// reimplement the same two rules independently and had drifted out of sync:
// jobsettings.php only CSS-hid the trim fields for Resize/Auto (so hidden
// stale values still got submitted), newIssue.php never hid the American-
// only Selfcover option, and only color.php actually dropped the position
// field for American numbering rather than just relabeling it. Both
// functions below consult partsShowPosition()/partsShowTrim()
// (engine/engine.php) - the single source of truth for rule A/B (page
// sequence is European-only) and rule C/D (trim size is Full/Hybrid-only) -
// instead of re-deriving them.

// One EXISTING part's <tr>, for the PHP-side loop over $parts.
function partsRowHtml( $part, $pageNumbering, $workflow, $pdfStandard = "" ) {
	global $lang;
	$types = PARTTYPES;
	$type = $part["name"];
	$size = explode( "x", $part["size"] );

	$html = '<tr><td style="white-space: nowrap;">';
	$html .= '<span style="display: inline-block; width: 105px; min-width: 105px;"><select name="type[]">';
	foreach( $types as $t ) {
		$label = array_search( $t, PARTS );
		$html .= '<option '.( $type == $t ? "selected" : "" ).' value="'.$t.'">'.$lang["parts"][$label].'</option>';
		}
	$html .= '</select></span>';

	if( partsShowPosition( $pageNumbering ) ) {
		$posname = ( $pageNumbering == "American" ) ? "Pages" : $lang["parts"]["Position"];
		$html .= '<span style="padding-left: 5px;">'.$posname.': <input type="text" name="position[]" value="'.$part["place"].'" style="width: 100px;"></span>';
		}

	if( partsShowTrim( $workflow ) ) {
		$html .= '<span style="padding-left: 10px;">'.$lang["parts"]["Trimmed"].': <input type="text" name="trim_x[]" value="'.$size[0].'" style="width: 35px;"> x <input type="text" name="trim_y[]" value="'.$size[1].'" style="width: 35px;"> mm</span>';
		}
	else {
		$html .= '<span><input type="hidden" name="trim_x[]" value="'.$size[0].'"><input type="hidden" name="trim_y[]" value="'.$size[1].'"></span>';
		}

	$html .= '<span style="padding-left: 10px; '.( $pdfStandard == "Web" ? "display: none;" : "" ).'">'.$lang["parts"]["Color"].': <select name="color[]">'
		.colorStandardOptions( $part["color"] ).
		'<option '.( $part["color"] == "RGB" ? "selected" : "" ).' value="RGB">RGB</option></select></span>';
	$html .= grayscaleCheckbox( $part["grayscale"] == "true" );
	$html .= '<span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span>';
	$html .= '</td></tr>';
	return $html;
	}

// The JS text-building lines for ONE new row, for use inside each dialog's
// own newLine(): callers still build their own "have"-array/type-option
// loop and their own $("#partContent").append(text) call (the dropdown-
// exclusion logic is otherwise identical across dialogs, kept per-file only
// because the surrounding function shape differs slightly); this fills in
// the position/trim/color/grayscale portion in between, as literal PHP
// source emitting JS `text += '...';` statements the same way each dialog
// already did inline - single-quoted PHP throughout so none of the HTML
// attribute double-quotes need escaping, matching the existing convention.
function partsRowJs( $pageNumbering, $workflow, $pdfStandard = "" ) {
	global $lang;
	$js = 'text += \'</select></span>\';';

	if( partsShowPosition( $pageNumbering ) ) {
		$posname = ( $pageNumbering == "American" ) ? "Pages" : $lang["parts"]["Position"];
		$js .= 'text += \'<span style="padding-left: 5px;">'.$posname.': <input type="text" onkeydown="numberCheck3(event)" name="position[]" style="width: 100px;"></span>\';';
		}

	if( partsShowTrim( $workflow ) ) {
		$js .= 'text += \'<span style="padding-left: 10px;">'.$lang["parts"]["Trimmed"].': <input type="text" name="trim_x[]" style="width: 35px;"> x <input type="text" name="trim_y[]" style="width: 35px;"> mm</span>\';';
		}
	else {
		$js .= 'text += \'<span><input type="hidden" name="trim_x[]" value="210"><input type="hidden" name="trim_y[]" value="297"></span>\';';
		}

	$js .= 'text += \'<span style="padding-left: 10px; '.( $pdfStandard == "Web" ? "display: none;" : "" ).'">'.$lang["parts"]["Color"].': <select name="color[]">'
		.colorStandardOptions( "FOGRA_39" ).
		'<option '.( $pdfStandard == "Web" ? "selected" : "" ).' value="RGB">RGB</option></select></span>\';';
	$js .= 'text += \''.grayscaleCheckbox().'\';';
	$js .= 'text += \'<span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>\';';
	return $js;
	}

// The "hide Selfcover for American" JS, identical across dialogs (color.php
// and modIssue.php already had it; create.php has its own equivalent tied to
// its own #PageNumbering select; newIssue.php was missing it entirely).
function partsSelfcoverJs( $selectorForPageNumbering ) {
	return '
function setParts() {
	var pn = '.$selectorForPageNumbering.';
	if( pn == "American" ) {
		$("select option[value=\'Selfcover\']").hide();
		}
	else {
		$("select option[value=\'Selfcover\']").show();
		}
	}
setParts();
';
	}

// Trim size is usually the same across every Part in an issue (Cover and
// Inside are almost always trimmed to the same size) - seeding each newly
// added row from whatever's already in the first row's trim_x/trim_y saves
// re-typing it every time, while leaving both fields fully editable for the
// (uncommon) Part that actually differs. Call this once, right after the
// new row has been appended to the DOM, from each dialog's own newLine().
function partsTrimPrefillJs() {
	return '
	var firstTrimX = $(\'input[name="trim_x[]"]\').first().val();
	var firstTrimY = $(\'input[name="trim_y[]"]\').first().val();
	if( firstTrimX ) { $(\'input[name="trim_x[]"]\').last().val( firstTrimX ); }
	if( firstTrimY ) { $(\'input[name="trim_y[]"]\').last().val( firstTrimY ); }
';
	}
?>
