<?
include_once( 'partsRow.php' );
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
	$width = 610;
	}
else {
	$width = 830;
	}
?>

<div>
	<div class='panelTitle'><?= sprintf( $lang["publications"]["new_issue_for"], $magazine[0][2], $lang["pn"][(string) $newxml->Item[$x]->PageNumbering] ) ?></div>
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
			$pageNumbering = (string) $newxml->Item[$x]->PageNumbering;
			$workflow = (string) $newxml->Item[$x]->Workflow;
			$pdfStandard = (string) $newxml->Item[$x]->PDFstandard;

			if( $magazine[0][10] == "Adhoc" ) {
				$pub = sql_aget( "publications", "magazine_id='".$_GET['data']."' AND code='".$magazine[0][3]."'", "*" );
				$parts = sql_aget( "parts", "pub_id='".$pub[0]["id"]."' order by id ASC", "*" );
				}

			if( $magazine[0][10] == "Regular" ) {
				$parts = sql_aget( "parts", "pub_id='0' AND mag_id='".$magazine[0][0]."' order by id ASC", "*" );
				}

			for( $i = 0; $i < count( $parts ); $i++ ) {
				echo partsRowHtml( $parts[$i], $pageNumbering, $workflow, $pdfStandard );
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
<?= partsSelfcoverJs( '"'.$pageNumbering.'"' ) ?>

function removeRow( o ) {
	var obj = $(o).parent().parent().parent();
	$(obj).remove();
	}

function newLine() {
	var have = new Array();
	$("select[name='type[]']").each(function(){
		have.push( $(this).val() );
		});

	var text = '<tr><td style="white-space: nowrap;"><span><select name="type[]">';

	<?php
	for( $i = 0; $i < count( $types ); $i++ ) {
		$temp = array_search( $types[$i], PARTS );
		echo '
			if( jQuery.inArray( "'.$types[$i].'", have ) == -1 ) {
				text += \'<option value="'.$types[$i].'">'.$lang["parts"][$temp].'</option>\';
				}
			';
		}
	echo partsRowJs( $pageNumbering, $workflow, $pdfStandard );
	?>
	$("#partContent").append(text);
	<?= partsTrimPrefillJs() ?>
	setParts();
	}

</script>