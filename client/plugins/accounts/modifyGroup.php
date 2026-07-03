<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["settings"]["mod_group"] ?></div>
	<div class='panelControl' style="width: auto !important;">
	<table class='panelTable' style='width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<?
				$groups = array();
				if( $user[0][8] == 2 ) {
					$sql = sql_get( "user_groups", "1 order by `name` ASC", "*" );
					}
				else {
					$sql = sql_get( "user_groups", "publisher='".$user[0][4]."' order by `name` ASC", "*" );
					}
					
				for( $i = 0; $i < count( $sql ); $i++ ) {
					$groups[] = $sql[$i];
					}

				echo "<tr>";
					echo "<td height='23px' align='left' style='width: 185px;'>";
						echo $lang['settings']['account_group_remove'];
					echo "</td>";
					echo "<td height='23px' align='left'>";
						echo "<select id='account_remove' name='account_remove' onchange=\"changeModGroup( $(this).val() )\">";
							echo "<option value=''>".$lang['settings']['ftp_set']."</option>";
							foreach( $groups as $value ) {
								echo "<option value='".$value[0]."'>".$value[1]."</option>";
								}
						echo "</select>";
					echo "</td>";
				echo "</tr>";
			?>
		</tbody>
	</table>
	
	<div id='group_mod_content' style='display:none;'>
	</div>

	<table class='panelTable' style='margin-top: 20px; width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tr>
			<td align='center'>
				<div onclick="closePanel( 'accounts_modifyGroup', 'back')" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton">Cancel</div>
				<div onclick="menuApply( 'accounts', 'modifyGroup')" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Apply</div>
			</td>
		</tr>
	</table>
	
	</div>
</div></form>