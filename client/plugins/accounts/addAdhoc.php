<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["settings"]["new_Adhocuser"] ?></div>
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
				<td align='left' height='23px'><?= $lang["settings"]["fullname"] ?></td>
				<td align='left'><input type='text' autocomplete="off" id='u_fullname' name='u_fullname' style='width: 200px;' value=""></td>
			</tr>
			<tr>
				<td align='left' height='23px'><?= $lang["settings"]["email"] ?></td>
				<td align='left'><input type='text' autocomplete="off" id='u_mail' name='u_mail' style='width: 200px;' value=""></td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'accounts_addAdhoc', 'back')" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'accounts', 'addAdhoc')" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>			
		</tbody>
	</table>
	
	</div>	
</div></form>