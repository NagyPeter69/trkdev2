<?php

$pub = sql_aget( "publications", "id='".$_GET["pubid"]."'", "*" );
$pages = $pub[0]["pages"];
$sequence = count( $_POST["pageselector"] );

$freePages = array();
for( $i = 1; $i <= $pages; $i++ ) {
	$allowed = true;
	$check = sql_aget( "flatplan_planner", "pub_id='".$_GET["pubid"]."' AND pos='".$i."'", "*" );

	if( empty( $check[0]["id"] ) ) {
		for( $x = 1; $x < $sequence; $x++ ) {
			$check2 = sql_aget( "flatplan_planner", "pub_id='".$_GET["pubid"]."' AND pos='".( $x + $i )."'", "*" );
			if( !empty( $check2[0]["id"] ) ) {
				$allowed = false;
				break;
				}
			}		

		if( $allowed ) {
			$freePages[] = $i;
			}
		}
	}

//var_dump( $freePages );
?>

<form id='moveForm' method='post' action=''>
<div>
	<div class='panelTitle'>Move Pages</div>
	<div class='panelControl'>
	
	<input type="hidden" id="pubid" name="pubid" value="<?= $_GET["pubid"]; ?>">
	<input type="hidden" id="sequence" name="sequence" value="<?= $sequence; ?>">
	<input type="hidden" id="oldstart" name="oldstart" value="<?= $_POST["pageselector"][0]; ?>">
	
	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td colspan="2" align="center" style="padding-top: 0px;">
					Move selected pages to the starting position from:
					<select id="starting" name="starting">
					<?php
					for( $i = 0; $i < count( $freePages ); $i++ ) {
						echo "<option value='".$freePages[$i]."'>".$freePages[$i]."</option>";
						}
					?>
					</select>
				</td>
			</tr>

			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'planner_move', 'back')" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton">Cancel</div>
					<div id='plannermove' onclick="saveMove()" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Move</div>
				</td>
			</tr>	
		</tbody>
	</table>
						
	</div>
</div>

<script>
	
function saveMove() {
	$("#plannermove").addClass("btn-disabled");	
	$.ajax	({
		url:"plugins/plannerApply.php?sub=movePages",
		type: "POST",
		data: { settings: $("#moveForm").serialize() },
		dataType: 'json',
		success:function( data ) {	
			console.log( data );
			$("#plannermove").removeClass("btn-disabled");	

			$("#planner_move").hide(200);
			loadArticles();
			}
		});		
	}
	
</script>

</form>