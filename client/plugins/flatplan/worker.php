<?php
$article = sql_aget( "flatplan_planner", "id='".$_GET["data"]."'", "*" );
$pub = sql_aget( "publications", "id='".$article[0]["pub_id"]."'", "*" );

?>
<form id='subForm2' method='post' action=''>
<input type="hidden" id="pid" name="pid" value="<?= $article[0]["pub_id"]; ?>">
<input type="hidden" id="fname" name="fname" value="<?= $article[0]["name"]; ?>">
<div>
	<div class='panelTitle'><?= $article[0]["name"] ?> Worker</div>
	<div class='panelControl' style='width: 360px;'>
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tr>
				<td>Select worker: </td>
				<td>
					<select name="workerID" id="workerID">
						<option value='0'>---------------</option>
						<?php
						
						$users = array();
						$array = sql_aget( "accounts", "publisher='".$pub[0]["publisher_id"]."' order by full_name ASC", "*" );
						
						for( $i = 0; $i < count( $array ); $i++ ) {
							$temp = explode( ",", $array[$i]["showMagazines"] );
							if( in_array( $pub[0]["magazine_id"], $temp ) ) {
								$users[] = $array[$i];
								}
							}
						for( $i = 0; $i < count( $users ); $i++ ) {
							echo "<option ".( $users[$i]["id"] == $article[0]["workerID"] ? "selected" : "" )." value='".$users[$i]["id"]."'>".$users[$i]["full_name"]."</option>";
							}
						?>					
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'flatplan_worker', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'flatplan', 'worker', 'worker' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>			
		</table>
	</div>
</div>		
		
</form>