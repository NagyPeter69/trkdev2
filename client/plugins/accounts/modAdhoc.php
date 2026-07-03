<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["settings"]["mod_Adhocuser"] ?></div>
	<div class='panelControl'>
	
	<table class='panelTable' style='width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
		<?
			echo "<tr>";
				echo "<td align='left' style='width: 90px;'>".$lang["settings"]["publisher"]."</td>";
				echo "<td>";
					if( $user[0][4] == 6 ) {
						echo "<select onchange='AdHocMemberList( $(this).val() )' style='margin-left: -1px;' name='u_publisher2' id='u_publisher2'>";
						$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
						for( $i = 0; $i < count($publishers); $i++ ) {
							echo "<option ";
							if( $user[0][4] == $publishers[$i][0] ) echo "selected ";
							echo "value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
							}
						echo "</select>";
						}
					else {
						$publisher = sql_get( "publishers", "id='".$user[0][4]."'", "id, name");
						echo "<select style='margin-left: -1px;' name='u_publisher' id='u_publisher'>";
              				echo "<option value='".$publisher[0][0]."'>".$publisher[0][1]."</option>";
						echo "</select>";
						}				
				echo "</td>";
			echo "</tr>";

			$groups = array();
			$sql = sql_get( "ad_hoc_users", "client='".$user[0][4]."' ORDER BY `name` ASC", "id, name" );
		
			for( $i = 0; $i < count( $sql ); $i++ ) {
				$groups[ $sql[$i][0] ] = $sql[$i][1];
				}
		
			echo "<tr>";
				echo "<td style='width:90px;' align='left' >";
					echo $lang['settings']['account_user_remove'];
				echo "</td>";
				echo "<td align='left'>";
					echo "<select style='margin-left: -1px;' id='account_remove' name='account_remove' onchange=\"changeModAdhocUser( $(this).val() )\">";
						echo "<option value=''>".$lang['settings']['ftp_set']."</option>";
				
						foreach( $groups as $key=>$value ) {
							$name = explode( "_", $key );
							echo "<option value='".$key."'>".$value."</option>";
							}
					echo "</select>";
				echo "</td>";
			echo "</tr>";		
		?>
		</tbody>
	</table>
	</div>

	<div id='user_mod_content' style='display:none; margin-top: 10px;'>
	</div>
	
	<table class='panelTable' style='margin-top: 5px; width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tr>
			<td align='center'>
				<div onclick="closePanel( 'accounts_modAdhoc', 'back')" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div onclick="menuApply( 'accounts', 'modAdhoc')" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
			</td>
		</tr>
	</table>
	</div>
</div></form>