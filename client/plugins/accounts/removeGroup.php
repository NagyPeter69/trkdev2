<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["settings"]["remove_group"] ?></div>
	<div class='panelControl'>
	
	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
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
				$groups[ $sql[$i][0] ] = $sql[$i][1];
				}
			
			echo "<tr>";
				echo "<td align='left' style='widtH: 130px;' >";
					echo $lang['settings']['account_group_remove'];
				echo "</td>";
				echo "<td align='left'>";
					echo "<select id='account_remove' name='account_remove'>";
						echo "<option value=''>".$lang['settings']['ftp_set']."</option>";
					
						foreach( $groups as $key=>$value ) {
							$name = explode( "_", $key );
							echo "<option value='".$key."'>".$value."</option>";
							}
					echo "</select>";
				echo "</td>";
			echo "</tr>";		
			?>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" align="left" style="width: 210px; padding-top: 10px;">
					<div onclick="closePanel( 'accounts_removeGroup', 'back')" style="margin-left: 2px;" class="panelButton">Cancel</div>
					<div onclick="menuApply( 'accounts', 'removeGroup')" style="margin-left: 20px;" class="panelButton">Apply</div>
				</td>
			</tr>		
		</tbody>
	</table>		
	</div>
</div>
</form>