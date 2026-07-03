<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["settings"]["remove_Adhocuser"] ?></div>
	<div class='panelControl' style='width: 250px;'>

	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<?
			echo "<tr>";
				echo "<td align='left' style='width: 90px;'>".$lang["settings"]["publisher"]."</td>";
				echo "<td>";
					if( $user[0][4] == 6 ) {
						echo "<select onchange='memberList2( $(this).val() )' style='margin-left: -1px;' name='u_publisher2' id='u_publisher2'>";
						$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
						for( $i = 0; $i < count($publishers); $i++ ) {
							echo "<option ";
							if( $user[0][4] == $publishers[$i][0] ) echo "selected ";
							echo "value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
							}
						echo "</select>";
						}
					else {
						$publisher = sql_get( "publishers", "id='".$user[0][4]."'", "name");
						echo "<input type='text' name='u_publisher' id='u_publisher' value='".$publisher[0][0]."' readonly>";
						}				
				echo "</td>";
			echo "</tr>";
			
			$groups = array();
			$sql = sql_get( "ad_hoc_users", "client='".$user[0][4]."' ORDER BY `name` ASC", "id, name" );

			for( $i = 0; $i < count( $sql ); $i++ ) {
				$groups[ $sql[$i][0] ] = $sql[$i][1];
				}
		
			echo "<tr>";
				echo "<td align='left' style='width: 90px;' >";
					echo $lang['settings']['account_user_remove'];
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
				<td colspan="2" align="center" style="width: 100%; padding-top: 10px;">
					<div onclick="closePanel( 'accounts_delAdhoc', 'back')" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'accounts', 'delAdhoc')" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>		
		</tbody>
	</table>
	</div>	
</div></form>