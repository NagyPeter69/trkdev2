<form id='creator' method='post' action='?page='>

<div class='loginDiv'>
	<form><div class='floatMenu2'>
	<?php if( HOST == "Dev" ) { ?>
		<div style="padding-bottom: 20px; font-size: 18px;">Developer System</div>
	<?php } ?>
	<div id="login_error"><?= $login_error ?></div>
	<input type='hidden' name='querystring' value='<?= ( $_SERVER["QUERY_STRING"] != "" ? "&".$_SERVER["QUERY_STRING"] : "" ) ?>'>

	<table id='job_names' cellspacing='0' cellpadding='0'>
		<tr>
			<td align='left' height='25px' style='font-size: 14px; color: #FFF;'><?= $lang["login"]["username"] ?></td>
			<td style='padding-left: 20px;'><input type='text' name='username' style='width: 100px;'></td>
		</tr>
		<tr>
			<td align='left' height='25px' style='font-size: 14px; color: #FFF;'><?= $lang["login"]["pass"] ?></td>
			<td style='padding-left: 20px;'><input type='password' name='password' style='width: 100px;'></td>
		</tr>
		<tr>
			<td align='center' colspan='2' style='padding-top: 15px;'>
				<button style='display: block; box-sizing: content-box; padding-top: 0px; padding-bottom: 0px; font-weight: normal; padding-left: 10px; width: 55px !important; float: initial; padding-right: 10px;' onclick='$("#creator").submit()' class="panelButton"><?= $lang["login"]["login"] ?></button>
			</td>
		</tr>
	</table>
	</div></form>
</div>
</form>

<div class="mobileLogin" style="display: none;">
	<form id='creator2' method='post' action='?page='>
	<input type='hidden' name='querystring' value='<?= ( $_SERVER["QUERY_STRING"] != "" ? "&".$_SERVER["QUERY_STRING"] : "" ) ?>'>
	<table cellspacing="0" cellpadding="0" style="width: 100vw; height: 85vh;">
		<tr>
			<td align="center" valign="center">
				<div><? include("images/colorcom_trk_logo.svg"); ?></div>
				
				<div class="login_topPadding">Login</div>
				<div class="login_topPadding login_form_title">Name:</div>
				<div><input type='text' name='username' class='login_input <?= ( $usererror ? "inputError" : "" ) ?>'></div>
				<div class="login_topPadding login_form_title">Password:</div>
				<div><input type='password' name='password' class='login_input <?= ( $usererror ? "inputError" : "" ) ?>'></div>
				<div><div class='login_topPadding login_button' onclick='$("#creator2").submit(); return false;'>Log in</div></div>
			</td>
		</tr>
	</table>
	</form>
</div>

<script>

</script>