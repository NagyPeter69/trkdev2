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

if( $magazine[0][10] == "Adhoc" ) {
	$pub = sql_aget( "publications", "code='".$magazine[0][3]."'", "*" );
	}

?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">
<input type="hidden" id="magazine" name="magazine" value="<?= $_GET['data']; ?>">
<input type='hidden' id='code' name='code' value='<?= $magazine[0][3] ?>'>
<input type='hidden' id='pn' name='pn' value='<?= $newxml->Item[$x]->PageNumbering ?>'>
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
	<div class='panelTitle'><?= $lang["publications"]["color"] ?> : <?= $magazine[0][2] ?></div>
	<div class='panelControl' style='width: <?= $width ?>px;'>
		<?php if( $magazine[0][10] == "Adhoc" && $newxml->Item[$x]->PageNumbering == "European" ) { ?>
		<table cellspacing='0' cellpadding='0'>
			<tr>
				<td><?= $lang["settings"]["issueLength"] ?></td>
				<td style="padding-left: 15px;"><input type="text" name="numOfPages" value="<?= $pub[0]["pages"] ?>" style="width: 30px;"></td>
			</tr>
		</table>
		<?php } ?>
		
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tbody id="partContent">
				<?php
				$types = PARTTYPES;
				$posname = $lang["parts"]["Position"];
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
						echo '<span style="display: inline-block; width: 105px; min-width: 105px;"><select name="type[]">';
							for( $y = 0; $y < count( $types ); $y++ ) {
								$temp = array_search( $types[$y], PARTS );
								echo '<option '.( $type == $types[$y] ? "selected" : "" ).' value="'.$types[$y].'">'.$lang["parts"][$temp].'</option>';
								}
						echo '</select></span>';
						
						if( $newxml->Item[$x]->PageNumbering == "European" ) {
							echo '<span style="padding-left: 5px;">'.$posname.': <input type="text" name="position[]" value="'.$parts[$i]["place"].'" style="width: 100px;"></span>';
							}
							
						if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) {
							echo '<span><input type="hidden" name="trim_x[]" value="'.$size[0].'" style="width: 35px;"><input type="hidden" name="trim_y[]" value="'.$size[1].'" style="width: 35px;"></span>';
							}
						else {
							echo '<span style="padding-left: 10px;">'.$lang["parts"]["Trimmed"].': <input type="text" name="trim_x[]" value="'.$size[0].'" style="width: 35px;"> x <input type="text" name="trim_y[]" value="'.$size[1].'" style="width: 35px;"> mm</span>';
							}
						echo '<span style="padding-left: 10px; '.( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ).'">'.$lang["parts"]["Color"].': <select name="color[]">'
						.colorStandardOptions( $parts[$i]["color"] ).
						'<option '.( $parts[$i]["color"] == "RGB" ? "selected" : "" ).' value="RGB">RGB</option></select></span>';
						echo '<span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span>';
					echo "</td></tr>";
					}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td><img id="newLineIcon" onclick="newLine()" src="images/trk_plus.png" style="cursor: pointer;"></td>
				</tr>
			</tfoot>
		</table>
		
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tr>
				<td>&nbsp;</td>
				<td align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'pubs_color', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<!-- <div onclick="menuApply( 'pubs', 'color', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Apply</div> -->
					<div onclick="saveParts()" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>
		</table>
	</div>
</div>
</form>

<script>
var posname = "<?= $posname ?>";
var maxTypes = parseInt( "<?= count( $types ) ?>" );
var usedTypes = parseInt( "<?= count( $parts ) ?>" );
console.log( "max parts: "+maxTypes );
console.log( "used parts: "+usedTypes );
function saveParts() {
	console.log( $("#subForm").serialize() );
	$('input[name^="position"]').css("background", "#FFF");
	$.ajax	({
		url:"plugins/pubsApply.php?sub=color",
		type: "POST",
		data: { settings: $("#subForm").serialize() },
		dataType: 'json',
		success:function( data ) {	
			console.log( data[0] );
			if( data[0].length > 0 ) {
				for( var i = 0; i < data[0].length; i++ ) {
					if( data[0][i].indexOf("position_") >= 0 ) {
						var temp = data[0][i].split("_");
						var tar = $('input[name^="position"]')[temp[1]];
						$(tar).css("background", "#D14550" );
						}
					}
				}
			else {
				$("#pubs_color").hide(200, function(){
					$(this).remove();
					});
				}
			}
		});
	}

function setParts() {
	$val = $("#pn").val();
	console.log( $val );
	if( $val == "American" ) {
		$("select option[value='Selfcover']").hide();
		}
	else {
		$("select option[value='Selfcover']").show();
		}	
	}
setParts();

function removeRow( o ) {
	var obj = $(o).parent().parent().parent();
	$(obj).remove();
	}

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

function newLine() {
	var have = new Array();
	$("select[name='type[]']").each(function(){
		have.push( $(this).val() );
		});
		
	<?php if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) { ?>
		var text = '<tr><td><span style="display: inline-block; width: 105px; min-width: 105px;"><select name="type[]">';
		
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
		text += '</select></span><span style="padding-left: 5px;">'+posname+': <input type="text" onkeydown="numberCheck3(event)" name="position[]" style="width: 100px;"></span><span><input type="hidden" name="trim_x[]" value="210" style="width: 35px;"><input type="hidden" name="trim_y[]" value="297"></span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>"><?= $lang["parts"]["Color"] ?>: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option></select></span><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';
		<?php } else { ?>
		text += '</select></span><span><input type="hidden" name="trim_x[]" value="210" style="width: 35px;"><input type="hidden" name="trim_y[]" value="297"></span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>"><?= $lang["parts"]["Color"] ?>: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option></select></span><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';		
		<?php } ?>
	<?php } else { ?>
		var text = '<tr><td><span style="display: inline-block; width: 105px; min-width: 105px;"><select name="type[]">';
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
		text += '</select></span><span style="padding-left: 5px;">'+posname+': <input type="text" onkeydown="numberCheck3(event)" name="position[]" style="width: 100px;"></span><span style="padding-left: 10px;"><?= $lang["parts"]["Trimmed"] ?>: <input type="text" name="trim_x[]" style="width: 35px;"> x <input type="text" name="trim_y[]" style="width: 35px;"> mm</span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>"><?= $lang["parts"]["Color"] ?>: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option></select></span><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';
		<?php } else { ?>
		text += '</select></span><span style="padding-left: 10px;"><?= $lang["parts"]["Trimmed"] ?>: <input type="text" name="trim_x[]" style="width: 35px;"> x <input type="text" name="trim_y[]" style="width: 35px;"> mm</span><span style="padding-left: 10px; <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "display: none;" : "" ) ?>"><?= $lang["parts"]["Color"] ?>: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?><option <?= ( $newxml->Item[$x]->PDFstandard == "Web" ? "selected" : "" ) ?> value="RGB">RGB</option></select></span><span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';		
		<?php } ?>
	<? } ?>
	$("#partContent").append(text);
	usedTypes++;
	checkParts();	
	}


</script>