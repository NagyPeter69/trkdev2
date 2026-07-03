<form id='subForm' method='post' action=''>
<div>
	<div class='panelTitle'><?= $lang["settings"]["new_group"] ?></div>
	<div class='panelControl' style="width: auto !important;">
	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td height='23px' align='left' style='width: 185px; !important'>Add New Planner Group</td>
				<td align='left'>
				<?
					if( $user[0][8] == 2 ) {
						$publishers = sql_get( "publishers", "1 order by `name` ASC", "*" );
						echo '<select id="publisher" name="publisher">';
							echo '<option value="0">Global</option>';
							for( $i = 0; $i < count( $publishers ); $i++ ) {
								echo '<option ';
								if( $user[0][4] == $publishers[$i][0] ) echo "selected ";
								echo 'value="'.$publishers[$i][0].'">'.$publishers[$i][1].'</option>';
								}
						echo '</select>';
						}
					else {
						echo '<input type="hidden" id="publisher" name="publisher" value="'.$user[0][4].'">';
						}
				?>
				</td>
			</tr>
			<tr>
				<td height='23px' align='left' style='width: 185px; !important'><?= $lang["settings"]["group_name"] ?></td>
				<td align='left'>
					<input type='text' id='name' name='name'>
				</td>
			</tr>
			<tr>
				<td height='23px' align='left' style='width: 185px; !important'>Day shifting</td>
				<td align='left'>
					<input type="range" onmousemove="$('#dayshiftval').html( $(this).val() )" onchange="$('#dayshiftval').html( $(this).val() )" id='dayShift' name='dayShift' min="-7" max="7">
					<span id="dayshiftval">0</span>
				</td>
			</tr>
						
			<tr>
				<td colspan="2">
					<div class="rightsTitle">Rights</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "plannerView", "plannerModify" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>

			<tr>
				<td colspan="2" align="center" style="width: 210px; padding-top: 20px;">
					<div onclick="closePanel( 'accounts_addPlannerGroup', 'back')" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton">Cancel</div>
					<div onclick="menuApply( 'accounts', 'addPlannerGroup')" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Apply</div>
				</td>
			</tr>
		</tbody>
	</table>
	</div>
</div></form>