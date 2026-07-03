<form id='subForm' method='post' action=''>
<div>
	<div class='panelTitle'><?= $lang["settings"]["new_ftp"] ?></div>
	<div>
		<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>
			<?
				if( $user[0][4] != 6 ) {
					echo "<input type='hidden' name='pubid' id='pubid' value='".$user[0][4]."'>";
					}
				else {
					echo "<tr><td>";
						echo $lang['settings']['ftp_publisher'];
					echo "</td><td>";
					echo "<select name='pubid' id='pubid'>";
					$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
					for( $i = 0; $i < count($publishers); $i++ ) {
						echo "<option ";
						if( $user[0][4] == $publishers[$i][0] ) echo "selected ";
						echo "value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
						}
					echo "</select>";
					echo "</td></tr>";
					}
			?>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_chose'] ?>
				</td>
				<td align='left'>
					<select id='ftp_chose' name='ftp_chose'>
						<option value='Content'>Content</option>
						<option value='Final'>Final</option>
						<!-- <option value='Softproof'>Softproof</option> -->
					</select>
				</td>
				<td></td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_name'] ?>
				</td>
				<td align='left'>
					<input type='text' onkeypress="return letterCheckName(event)" id='ftp_name' name='ftp_name' value='<?= $_POST['ftp_name'] ?>'>
				</td>
				<td></td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_address'] ?>
				</td>
				<td align='left'>
					<input type='text' id='ftp_address_url' name='ftp_address_url' value='<?= $_POST['ftp_address_url'] ?>'>
				</td>
				<td></td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_port'] ?>
				</td>
				<td align='left'>
					<input type='text' onkeypress="return isNumberKey(event)" id='ftp_port' name='ftp_port' value='21'>
				</td>
				<td></td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_passive'] ?>
				</td>
				<td align='left'>
					<select id='ftp_passive' name='ftp_passive'>
						<option value='Yes'><?= $lang['settings']['yes'] ?></option>
						<option value='No'><?= $lang['settings']['no'] ?></option>
					</select>
				</td>
				<td></td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_binary'] ?>
				</td>
				<td align='left'>
					<select id='ftp_binary' name='ftp_binary'>
						<option value='true'><?= $lang['settings']['yes'] ?></option>
						<option value='false'><?= $lang['settings']['no'] ?></option>
					</select>
				</td>
				<td></td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_login'] ?>
				</td>
				<td align='left'>
					<input type='text' id='ftp_login' name='ftp_login' value='<?= $_POST['ftp_login'] ?>' >
				</td>
				<td></td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_pass'] ?>
				</td>
				<td align='left'>
					<input type='password' id='ftp_pass' name='ftp_pass'>
				</td>
				<td style='padding-right: 30px;'>
					<input onchange='revealPass( this, "ftp_pass")' type='checkbox' name='reveal'>&nbsp;<?= $lang["settings"]["reveal"] ?>
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['ftp_path'] ?>
				</td>
				<td align='left'>
					<input type='text' id='ftp_path' name='ftp_path' value='<?= $_POST['ftp_path'] ?>'>
				</td>
				<td></td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" align="left" style="padding-top: 10px;">
					<div onclick="closePanel( 'ftp_create', 'back')" style="margin-left: 2px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'ftp', 'create')" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>
		</table>
	</div>
</div></form>