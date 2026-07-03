<?php
include_once( 'switchAPI.php' );
SwitchLogin();
$points = SwitchGetSubmitPonts();

$flows = GetSwitchFlows();
$submits = array();
for( $i = 0; $i < count( $points ); $i++ ) {
	$submits[] = array(
		"flowId" => $points[$i]["flowId"],
		"objectId" => $points[$i]["objectId"],
		"flowName" => $points[$i]["flowName"],
		"name" => $points[$i]["name"],
		);
	}

$flowName = array_column($submits, 'flowName');
array_multisort($flowName, SORT_ASC, $submits);
?>

<form id='subForm' method='post' action=''>
<div>
	<div class='panelTitle'><?= $lang["switch"]["flow_title"] ?></div>
	
	<div>
		<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>
			<tr>
				<td><?= $lang["switch"]["uploadflow"] ?>&nbsp;</td>
				<td>
				<select name='uploadflow' id='uploadflow'>
					<?php
					for( $i = 0; $i < count( $submits ); $i++ ) {
						$val = $submits[$i]["flowId"]."_".$submits[$i]["objectId"];
						echo "<option ".( $flows["uploads"] == $val ? "selected" : "" )." value='".$val."'>".$submits[$i]["flowName"]."(".$submits[$i]["name"].")</option>";
						}
					?>
				</select>
				</td>
			</tr>

			<tr>
				<td><?= $lang["switch"]["commandflow"] ?>&nbsp;</td>
				<td>
				<select name='commandflow' id='commandflow'>
					<?php
					for( $i = 0; $i < count( $submits ); $i++ ) {
						$val = $submits[$i]["flowId"]."_".$submits[$i]["objectId"];
						echo "<option ".( $flows["commands"] == $val ? "selected" : "" )." value='".$val."'>".$submits[$i]["flowName"]."(".$submits[$i]["name"].")</option>";
						}
					?>
				</select>
				</td>
			</tr>
		</table>
	</div>
	
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'switch_flow', 'back', 'floatMenu' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'switch', 'flow', 'floatMenu' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>				
				</table>
			</div>
			</td></tr>
		</tbody>
	</table>	
	
</div>
</form>

<script>

</script>