<?
$pub = sql_get( "publications", "id='".$_GET['data']."'", "*" );
$magazine = sql_get( "magazines", "id='".$pub[0][2]."'", "*" );

$newxml = simplexml_load_file( '../xml/'.PMD.'.xml' );
$xpath = $newxml->xpath('/Publications');
foreach($xpath as $temp) {
	for( $x = 0; $x < count( $temp->Item ); $x++ ) {
		if( $temp->Item[$x]->Code == $magazine[0][3] ) {
			break;
			}
		}
	}
	
?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">
<input type="hidden" id="magazine" name="magazine" value="<?= $magazine[0][0]; ?>">
<input type="hidden" id="mcode" name="mcode" value="<?= $magazine[0][3]; ?>">
<input type="hidden" id="pub" name="pub" value="<?= $pub[0][0]; ?>">
<?php
if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) {
	echo '<input type="hidden" id="uploadable" name="uploadable" value="false">';
	$width = 610;
	}
else {
	$width = 830;
	}

// Regular publications are identified by magazine code + issue code (e.g.
// TMG_2601); Adhoc publications only ever have the one code (magazine and
// publication share it), so showing it twice would be redundant.
$displayCode = $magazine[0][3];
if( $magazine[0][10] == "Regular" ) {
	$displayCode .= "_".$pub[0][10];
	}
?>

<div>
	<div class='panelTitle'><?= $lang["publications"]["mod_issue"] ?> : <?= $displayCode ?></div>
	<div class='panelControl' style='width: <?= $width ?>px; text-align: left;'>
	
	<table cellspacing='0' cellpadding='0'>
		<tr>
			<td colspan="2"><?= $lang["publications"]["settings"] ?></td>
		</tr>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["customname"] ?></td>
			<td align='left'><input onkeypress="return isAllowedKey2(event)" type='text' id='customname' name='customname' value="<?= $pub[0][17] ?>"></td>
		</tr>
		
		<?php if( $newxml->Item[$x]->PageNumbering != "American" ) { ?>
			<tr>
				<td align='left' width='50%' height='28px'><?= $lang["publications"]["length"] ?></td>
				<td align='left'><input onkeypress="return isNumberKey(event)" type='text' id='page_nr' value="<?= $pub[0][6] ?>" name='page_nr' style='width: 30px; margin-left: 2px;'></td>
			</tr>
		<?php } ?>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["approve_upload"] ?></td>
			<td align='left'>
				<select name='uploadable'>
					<option <? if( $pub[0][8] == 'true' ) echo "selected" ?> value='true'><?= $lang["publications"]["yes"] ?></option>
					<option <? if( $pub[0][8] == 'false' ) echo "selected" ?> value='false'><?= $lang["publications"]["no"] ?></option>
				</select>
			</td>
		</tr>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["deadline"] ?></td>
			<td align='left'>
				<input value="<?= str_replace( "T", " ", $pub[0][11] ) ?>" readonly class="datepicker" type="text" name='dl' id='dl'>
			</td>
		</tr>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["xml"]["Enhance"] ?></td>
			<td align='left'>
				<?php
				$temp = array( 'Skintone', 'Food', 'Jewellery', 'Vivid', 'Paintings', 'Minimal', 'General', 'Resize only', 'Null' );
				$changeName = array( "Only Resize"=>"Null" );
				$value = $newxml->Item[$x]->Enhance;
									
				echo "<select style='margin-left: -1px;' id='enhance' name='enhance'>";
				foreach( $temp as $t ) {
					$tempVal = $t;
					if( !empty( $changeName[$t] ) ) {
						$tempVal = $changeName[$t];
						}
					echo "<option ".( $value == $tempVal ? "selected" : "" )." value='".$tempVal."'>".$t."</option>";
					}
				echo "</select>";								
				?>
			</td>
		</tr>						
	</table>
	
	<?php 
	if( $pub[0][1] != "0" ) {
	?>
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<tbody id="partContent">
			<tr>
				<td colspan="2" style="padding-top: 15px;">Parts</td>
			</tr>
			<?php
			$types = PARTTYPES;
			$posname = "Position";
			if( $newxml->Item[$x]->PageNumbering == "American" ) {
				$posname = "Pages";
				}
				
			if( $magazine[0][10] == "Adhoc" ) {
				$pub = sql_aget( "publications", "magazine_id='".$_GET['data']."' AND code='".$magazine[0][3]."'", "*" );
				$parts = sql_aget( "parts", "pub_id='".$pub[0]["id"]."' order by id ASC", "*" );
				}
			
			if( $magazine[0][10] == "Regular" ) {
				$parts = sql_aget( "parts", "pub_id='".$pub[0][0]."' AND mag_id='".$magazine[0][0]."' order by id ASC", "*" );
				}
			
			
			for( $i = 0; $i < count( $parts ); $i++ ) {			
				$type = $parts[$i]["name"];
				$size = explode("x", $parts[$i]["size"] );
				echo '<tr><td style="white-space: nowrap;">';
					echo '<span style="display: inline-block; width: 105px; min-width: 105px;"><select name="type[]">';
						for( $y = 0; $y < count( $types ); $y++ ) {
							$temp = array_search( $types[$y], PARTS );
							echo '<option '.( $type == $types[$y] ? "selected" : "" ).' value="'.$types[$y].'">'.$lang["parts"][$temp].'</option>';
							}
					echo '</select></span>';				
					echo '<span style="padding-left: 5px;">'.$posname.': <input type="text" name="position[]" value="'.$parts[$i]["place"].'" style="width: 100px;"></span>';
					
					if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) {
						echo '<span><input type="hidden" name="trim_x[]" value="'.$size[0].'" style="width: 35px;"><input type="hidden" name="trim_y[]" value="'.$size[1].'" style="width: 35px;"></span>';
						}
					else {
						echo '<span style="padding-left: 10px;">Trimmed size: <input type="text" name="trim_x[]" value="'.$size[0].'" style="width: 35px;"> x <input type="text" name="trim_y[]" value="'.$size[1].'" style="width: 35px;"> mm</span>';
						}
							
					echo '<span style="padding-left: 10px; '.( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ).'">Color standard: <select name="color[]">'
					.colorStandardOptions( $parts[$i]["color"] ).
					'<option '.( $parts[$i]["color"] == "RGB" ? "selected" : "" ).' value="RGB">RGB</option></select></span>';
					echo grayscaleCheckbox( $parts[$i]["grayscale"] == "true" );
					echo '<span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span>';
				echo "</td></tr>";
				}
			?>
		</tbody>
		<tfoot>
			<tr>
				<td><img onclick="newLine()" src="images/trk_plus.png" style="cursor: pointer;"></td>
			</tr>
		</tfoot>
	</table>
	<?php } ?>
	
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'pubs_modIssue', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'pubs', 'modIssue', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>				
				</table>
			</div>
			</td></tr>
		</tbody>
	</table>
	</div>
</div>
</form>

<script>
var posname = "<?= $posname ?>";
var maxTypes = parseInt( "<?= count( $types ) ?>" );
var usedTypes = parseInt( "<?= count( $parts ) ?>" );
function checkParts() {
	if( usedTypes < maxTypes ) {
		$("#newLineIcon").show(0);
		}
	else {
		$("#newLineIcon").hide(0);
		}
		
	setParts();
	}

function removeRow( o ) {
	var obj = $(o).parent().parent().parent();
	$(obj).remove();
	usedTypes--;
	checkParts();
	}

function setParts() {
	$val = "<?= $newxml->Item[$x]->PageNumbering ?>";
	if( $val == "American" ) {
		$("select option[value='Selfcover']").hide();
		}
	else {
		$("select option[value='Selfcover']").show();
		}	
	}
setParts();

function newLine() {
	var have = new Array();
	$("select[name='type[]']").each(function(){
		have.push( $(this).val() );
		});
		
	<?php if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) { ?>
		var text = '<tr><td style="white-space: nowrap;"><span style="display: inline-block; width: 105px; min-width: 105px;"><select name="type[]">';
		
		<?php
		for( $i = 0; $i < count( $types ); $i++ ) {
			$temp = array_search( $types[$i], PARTS );
			echo '
				if( jQuery.inArray( "'.$types[$i].'", have ) == -1 ) {
					text += \'<option value="'.$types[$i].'">'.$lang["parts"][$temp].'</option>\';
					}
				';
			}
		?>
		<?php if( $newxml->Item[$x]->PageNumbering == "European" ) { ?>
		text += '</select></span><span style="padding-left: 5px;">'+posname+': <input type="text" onkeydown="numberCheck3(event)" name="position[]" style="width: 100px;"></span><span><input type="hidden" name="trim_x[]" value="210" style="width: 35px;"><input type="hidden" name="trim_y[]" value="297"></span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>"><?= $lang["parts"]["Color"] ?>: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option></select></span><?= grayscaleCheckbox() ?><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';
		<?php } else { ?>
		text += '</select></span><span><input type="hidden" name="trim_x[]" value="210" style="width: 35px;"><input type="hidden" name="trim_y[]" value="297"></span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>"><?= $lang["parts"]["Color"] ?>: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option></select></span><?= grayscaleCheckbox() ?><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';		
		<?php } ?>
	<?php } else { ?>
		var text = '<tr><td style="white-space: nowrap;"><span style="display: inline-block; width: 105px; min-width: 105px;"><select name="type[]">';
		<?php
		for( $i = 0; $i < count( $types ); $i++ ) {
			$temp = array_search( $types[$i], PARTS );
			echo '
				if( jQuery.inArray( "'.$types[$i].'", have ) == -1 ) {
					text += \'<option value="'.$types[$i].'">'.$lang["parts"][$temp].'</option>\';
					}
				';
			}
		?>
		text += '</select></span><span style="padding-left: 5px;">'+posname+': <input type="text" onkeydown="numberCheck3(event)" name="position[]" style="width: 100px;"></span><span style="padding-left: 10px;">Trimmed size: <input type="text" name="trim_x[]" style="width: 35px;"> x <input type="text" name="trim_y[]" style="width: 35px;"> mm</span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>">Color standard: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option></select></span><?= grayscaleCheckbox() ?><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';
	<? } ?>
	$("#partContent").append(text);
	usedTypes++;
	checkParts();
	}

</script>