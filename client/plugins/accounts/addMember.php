<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["settings"]["new_member"] ?></div>
	<div class='panelControl'>

	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td align='left' height='23px'><?= $lang["settings"]["publisher"] ?></td>
				<td align='left'>
					<?
					if( $user[0][4] == 6 ) {
						echo "<select onchange='lMagazines( $(this).val() )' style='margin-left: -1px;' name='u_publisher' id='u_publisher'>";
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
					?>
				</td>
			</tr>
			<tr>
				<td align='left' height='23px'><?= $lang["settings"]["username"] ?></td>
				<td align='left'><input type='text' autocomplete="off" id='u_name' name='u_name' style='width: 200px;' value=""></td>
			</tr>
			
			<?php
			
			$newpw = randomPWB()."".randomPWS()."".randomPWN()."".randomPWS()."".randomPWB()."".randomPWN();
			
			?>
			
			<tr>	
				<td align='left' height='23px'><?= $lang["settings"]["pass"] ?></td>
				<td align='left'><input type='password' autocomplete="off" id='u_password' name='u_password' style='width: 200px;' value="<?= $newpw ?>"></td>
			</tr>
			<tr>	
				<td align='left' height='23px'><?= $lang["settings"]["pass2"] ?></td>
				<td align='left'><input type='password' autocomplete="off" id='u_password2' name='u_password2' style='width: 200px;' value="<?= $newpw ?>"></td>
			</tr>
			<tr>
				<td align='left' height='23px'><?= $lang["settings"]["fullname"] ?></td>
				<td align='left'><input type='text' autocomplete="off" id='u_fullname' name='u_fullname' style='width: 200px;' value=""></td>
			</tr>
			<tr>
				<td align='left' height='23px'><?= $lang["settings"]["email"] ?></td>
				<td align='left'><input type='text' autocomplete="off" id='u_mail' name='u_mail' style='width: 200px;' value=""></td>
			</tr>
			<tr>
				<td align='left'><?= $lang["settings"]["userGroup"] ?></td>
				<td align='left'>
					<select style='margin-left: -1px;' id='u_type' name='u_type'>
						<?
							if( $user[0][8] == 2 ) $groups = sql_get( 'user_groups', '1 ORDER BY `name` ASC', 'id, name' );
							else $groups = sql_get( 'user_groups', 'publisher="'.$user[0][4].'" OR publisher="0" ORDER BY `name` ASC', 'id, name' );					
							//$groups = sql_get( 'user_groups', 'publisher="'.$user[0][4].'" ORDER BY `name` ASC', 'id, name' );
							for( $i = 0; $i < count( $groups ); $i++ ) {
								echo "<option value='".$groups[$i][0]."'>".$groups[$i][1]."</option>";
								}
						?>
					</select>
				</td>
			</tr>
			<tr>
			<?php
			echo "<td colspan='2'>";
				echo "<div id='loadedMagazines'>";
				echo "<table width='100%' cellspacing='0' cellpadding='0'>";

				if( $user[0][4] == 6 ) {
					$sqlGet = sql_aget( "magazines", "1 ORDER BY `name`", "id, name" );
					}
				else {
					$sqlGet = sql_aget( "magazines", "publisher_id='".$getUser[0][4]."' ORDER BY `name`", "id, name" );
					}			
				
				for( $i = 0; $i < count( $sqlGet ); $i += 2 ) {
					echo "<tr>";
					
						if( !empty( $sqlGet[$i]["id"] ) ) {
							echo "<td>";
								echo "<input type='checkbox' name='aMagazines[]' id='aMagazines' value='".$sqlGet[$i]["id"]."'>&nbsp;".$sqlGet[$i]["name"]."<br>";
							echo "<td>";
							}

						if( !empty( $sqlGet[$i+1]["id"] ) ) {
							echo "<td>";
								echo "<input type='checkbox' name='aMagazines[]' id='aMagazines' value='".$sqlGet[$i+1]["id"]."'>&nbsp;".$sqlGet[$i+1]["name"]."<br>";
							echo "<td>";
							}							
							
					echo "</tr>";
					}

				echo "</table></div>";
			echo "</td>";				
			?>

			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" align="left" style="padding-top: 10px;">
					<div onclick="closePanel( 'accounts_addMember', 'back')" style="margin-left: 2px;" class="panelButton">Cancel</div>
					<div onclick="menuApply( 'accounts', 'addMember')" style="margin-left: 20px;" class="panelButton">Apply</div>
				</td>
			</tr>			
		</tbody>
	</table>
	
	</div>	
</div></form>