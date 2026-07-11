<?
$magazine = sql_get( "magazines", "id='".$_GET['data']."'", "*" );

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
<input type="hidden" id="magazine" name="magazine" value="<?= $_GET['data']; ?>">
<input type="hidden" id="mcode" name="mcode" value="<?= $magazine[0][3]; ?>">
<?php
if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) {
	echo '<input type="hidden" id="uploadable" name="uploadable" value="false">';
	$width = 480;
	}
else {
	$width = 700;
	}
?>

<div>
	<div class='panelTitle'><?= $lang["publications"]["new_issue"] ?></div>
	<div class='panelControl' style='width: <?= $width ?>px; text-align: left;'>
	
	<table cellspacing='0' cellpadding='0'>
		<tr>
			<td colspan="2"><?= $lang["publications"]["settings"] ?></td>
		</tr>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["release"] ?></td>
			<td align='left'>
				<select name='ev' id='ev'>
					<?
					$currentev = strval( date( "Y" ) );
					for( $i = 0; $i <= 5; $i++ ) {
						echo "<option value='".substr( ( intval( $currentev)+$i ), -2 )."'>".( intval( $currentev)+$i )."</option>";
						}
					?>
				</select>
			</td>		
		</tr>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["designation"] ?></td>
			<td align='left'>
				<input maxlength="4" onkeypress="return isNumberKey(event)" type='text' id='szam' name='szam' style='width: 30px; margin-left: 2px;'>
				<input type='hidden' id='job_code' name='job_code'>
			</td>
		</tr>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["proposed"] ?></td>
			<td align='left'><input type='hidden' name='delimiter' id='delimiter' value='_'><input readonly type='text' id='proposed' name='proposed' style='background: transparent; padding: 0; margin: 0; color: #FFF; font-size: 12px;'></td>
		</tr>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["customname"] ?></td>
			<td align='left'><input onkeypress="return isAllowedKey2(event)" type='text' id='customname' name='customname'></td>
		</tr>
		
		<?php if( $newxml->Item[$x]->PageNumbering != "American" ) { ?>
			<tr>
				<td align='left' width='50%' height='28px'><?= $lang["publications"]["length"] ?></td>
				<td align='left'><input onkeypress="return isNumberKey(event)" type='text' id='page_nr' name='page_nr' style='width: 30px; margin-left: 2px;'></td>
			</tr>
		<?php } ?>
		
		<?php if( $newxml->Item[$x]->Workflow == "Full" or $newxml->Item[$x]->Workflow == "Hybrid" ) { ?>
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["approve_upload"] ?></td>
			<td align='left'>
				<select name='uploadable'>
					<option value='true'><?= $lang["publications"]["yes"] ?></option>
					<option selected value='false'><?= $lang["publications"]["no"] ?></option>
				</select>
			</td>
		</tr>
		<?php } ?>
		
		<tr>
			<td align='left' width='50%' height='28px'><?= $lang["publications"]["deadline"] ?></td>
			<td align='left'>
				<input readonly class="datepicker" type="text" name='dl' id='dl'>
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
				$parts = sql_aget( "parts", "pub_id='0' AND mag_id='".$magazine[0][0]."' order by id ASC", "*" );
				}
			
			for( $i = 0; $i < count( $parts ); $i++ ) {
				$type = $parts[$i]["name"];
				$size = explode("x", $parts[$i]["size"] );
				echo "<tr><td>";
					echo '<span><select name="type[]">';
						for( $y = 0; $y < count( $types ); $y++ ) {
							$temp = array_search( $types[$y], PARTS );
							echo '<option '.( $type == $types[$y] ? "selected" : "" ).' value="'.$types[$y].'">'.$lang["parts"][$temp].'</option>';
							}
					echo '</select></span>';
					echo '<span style="padding-left: 5px;">'.$posname.': <input type="text" onkeydown="numberCheck3(event)" name="position[]" value="'.$parts[$i]["place"].'" style="width: 100px;"></span>';

					if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) {
						echo '<span><input type="hidden" name="trim_x[]" value="'.$size[0].'" style="width: 25px;"><input type="hidden" name="trim_y[]" value="'.$size[1].'" style="width: 25px;"></span>';
						}
					else {
						echo '<span style="padding-left: 10px;">Trimmed size: <input type="text" name="trim_x[]" value="'.$size[0].'" style="width: 25px;">x<input type="text" name="trim_y[]" value="'.$size[1].'" style="width: 25px;">mm</span>';
						}
					
					echo '<span style="padding-left: 10px; '.( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ).'">Color standard: <select name="color[]">'
					.colorStandardOptions( $parts[$i]["color"] ).
					'<option '.( $parts[$i]["color"] == "RGB" ? "selected" : "" ).' value="RGB">RGB</option>
					<option '.( $parts[$i]["color"] == "PSO_INP" ? "selected" : "" ).' value="PSO_INP">PSO_INP</option></select></span>';
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
	
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'pubs_newIssue', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'pubs', 'newIssue', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
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

function removeRow( o ) {
	var obj = $(o).parent().parent().parent();
	$(obj).remove();
	}

function newLine() {
	var have = new Array();
	$("select[name='type[]']").each(function(){
		have.push( $(this).val() );
		});
		
	<?php if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) { ?>
		var text = '<tr><td><span><select name="type[]">';
		
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
		text += '</select></span><span style="padding-left: 5px;">'+posname+': <input type="text" onkeydown="numberCheck3(event)" name="position[]" style="width: 100px;"></span><span><input type="hidden" name="trim_x[]" value="210" style="width: 35px;"><input type="hidden" name="trim_y[]" value="297"></span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>"><?= $lang["parts"]["Color"] ?>: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option><option value="PSO_INP">PSO_INP</option></select></span><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';
		<?php } else { ?>
		text += '</select></span><span><input type="hidden" name="trim_x[]" value="210" style="width: 35px;"><input type="hidden" name="trim_y[]" value="297"></span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>"><?= $lang["parts"]["Color"] ?>: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option><option value="PSO_INP">PSO_INP</option></select></span><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';		
		<?php } ?>
	<?php } else { ?>
		var text = '<tr><td><span><select name="type[]">';
		<?php
		for( $i = 0; $i < count( $types ); $i++ ) {
			$temp = array_search( $types[$i], PARTS );
			echo '
				if( jQuery.inArray( "'.$types[$i].'", have ) == -1 ) {
					text += \'<option  value="'.$types[$i].'">'.$lang["parts"][$temp].'</option>\';
					}
				';
			}
		?>
		text += '</select></span><span style="padding-left: 5px;">'+posname+': <input type="text" onkeydown="numberCheck3(event)" name="position[]" style="width: 100px;"></span><span style="padding-left: 10px;">Trimmed size: <input type="text" name="trim_x[]" style="width: 25px;">x<input type="text" name="trim_y[]" style="width: 25px;">mm</span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display:none;" : "" ) ?>">Color standard: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option><option value="PSO_INP">PSO_INP</option></select></span><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';
	<? } ?>
	$("#partContent").append(text);
	}

</script>