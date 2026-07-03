<?php
$data = explode( "|", $_GET["data"] );
$date = explode( "-", $data[1] );
$magazine = sql_get( "magazines", "id='".$data[0]."'", "*" );

$newxml = simplexml_load_file( '../xml/'.PMD.'.xml' );
$xpath = $newxml->xpath('/Publications');
foreach($xpath as $temp) {
	for( $x = 0; $x < count( $temp->Item ); $x++ ) {
		if( $temp->Item[$x]->Code == $magazine[0][3] ) {
			break;
			}
		}
	}

$nextmonth = intval( $date[1] ) + 1;
if( $nextmonth > 12 ) {
	$nextmonth = 1;
	$date[0]++;
	}
?>

<form id='subForm' method='post' action=''>
<input type="hidden" id="magazine" name="magazine" value="<?= $magazine[0][0]; ?>">
<input type="hidden" id="ev" name="ev" value="<?= $date[0] ?>">
<input type="hidden" id="dl" name="dl" value="<?= $data[1] ?>">
<input type="hidden" id="delimiter" name="delimiter" value="">

<div>
	<div class='panelTitle'>Create Issue Sales Day</div>
	<div class='panelControl' style='width: 390px; text-align: left;'>
		<div id="settings">
			<table class='panelTable' cellspacing='0' cellpadding='0'>
				<tr>
					<td align='left' width='50%' height='28px'>Sales Day</td>
					<td align='left'>
						<input readonly class="datepicker" type="text" name='salesday' id='salesday' value='<?= $data[1] ?>'>
					</td>
				</tr>
				<tr>
					<td align='left' width='50%' height='28px'>Print Order Day</td>
					<td align='left'>
						<?php
						$printdate = date( "Y-m-d", strtotime( "-8 days", strtotime( $data[1] ) ) );
						?>
						<input readonly class="datepicker" type="text" name='printorder' id='printorder' value='<?= $printdate ?>'>
					</td>
				</tr>
				<tr>
					<td align='left' width='50%' height='28px'>Issue Code</td>
					<td align='left'>
						<input type='text' id='jobcode' name='jobcode' value='<?= substr( $date[0], -2)."".str_pad( $nextmonth, 2, '0', STR_PAD_LEFT) ?>' style='width: 40px;'>
					</td>
				</tr>
				<tr>
					<td align='left' width='50%' height='28px'><?= $lang["publications"]["customname"] ?></td>
					<td align='left'><input onkeypress="return isAllowedKey2(event)" type='text' id='customname' name='customname'></td>
				</tr>	
				<tr>
					<td align='left' width='50%' height='28px'><?= $lang["publications"]["length"] ?></td>
					<td align='left'><input onkeypress="return isNumberKey(event)" type='text' id='numofpages' name='numofpages' value='<?= $order[0]["numofpages"] ?>'></td>
				</tr>
				<tr>
					<td colspan="2" align="center" style="padding-top: 10px;">
						<div onclick="closePanel( 'mwcalendar_calendar', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton">Cancel</div>
						<div onclick="menuApply( 'mwcalendar', 'calendar', 'calendar' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton">Apply</div>
					</td>
				</tr>				
			</table>
		</div>
	</div>
</div>
</form>