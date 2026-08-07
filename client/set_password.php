<?php
// Reached pre-login via ?page=set_password&token=... (see index2.php's page-dispatch
// allowlist) from a password-reset / welcome email - the token replaces mailing a
// plaintext password. Token is stored hashed (matches the remember-token pattern in
// engine.php's issueRememberToken()); the raw token only ever exists in the emailed link.

$token = $_GET['token'] ?? ( $_POST['token'] ?? '' );
$account = array();
if( $token != '' ) {
	$account = sql_aget( 'accounts', "pwset_token='".hash( 'sha256', $token )."' AND pwset_expires > '".time()."'", '*' );
	}

$done = false;
$formError = '';

if( !empty( $account[0]['id'] ) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	if( empty( $_POST['csrf_token'] ) || empty( $_SESSION['csrf_token'] ) || !hash_equals( $_SESSION['csrf_token'], $_POST['csrf_token'] ) ) {
		$formError = 'Your session expired, please reload the page and try again.';
		}
	elseif( strlen( $_POST['u_pass'] ?? '' ) < 8 ) {
		$formError = $lang['settings']['error_pw_length'] ?? 'Password must be at least 8 characters.';
		}
	elseif( ( $_POST['u_pass'] ?? '' ) !== ( $_POST['u_pass2'] ?? '' ) ) {
		$formError = $lang['settings']['error_pw_match'] ?? 'Passwords do not match.';
		}
	else {
		sql_update( 'accounts',
			"pass='".password_hash( $_POST['u_pass'], PASSWORD_DEFAULT )."', pwset_token=NULL, pwset_expires=NULL",
			"id='".$account[0]['id']."'" );
		$done = true;
		}
	}

$_SESSION['csrf_token'] = bin2hex( random_bytes( 32 ) );
?>
<div class='loginDiv'>
	<div class='floatMenu2'>
		<?php if( empty( $account[0]['id'] ) ) { ?>
			<div style="padding: 10px 0; font-size: 14px; color: #FFF;">
				This link is invalid or has expired. Please ask an administrator to send you a new one.
			</div>
		<?php } elseif( $done ) { ?>
			<div style="padding: 10px 0; font-size: 14px; color: #FFF;">
				Your password has been set. <a href="?page=" style="color: #FFF; text-decoration: underline;">Go to login</a>.
			</div>
		<?php } else { ?>
			<form id='setPassForm' method='post' action='?page=set_password'>
			<input type='hidden' name='token' value='<?= htmlspecialchars( $token ) ?>'>
			<input type='hidden' name='csrf_token' value='<?= $_SESSION['csrf_token'] ?>'>
			<div style="padding-bottom: 10px; font-size: 14px; color: #FFF;">Set your password</div>
			<?php if( $formError != '' ) { ?>
				<div id="login_error"><?= htmlspecialchars( $formError ) ?></div>
			<?php } ?>
			<table id='job_names' cellspacing='0' cellpadding='0'>
				<tr>
					<td align='left' height='25px' style='font-size: 14px; color: #FFF;'>New password</td>
					<td style='padding-left: 20px;'><input type='password' name='u_pass' style='width: 140px;'></td>
				</tr>
				<tr>
					<td align='left' height='25px' style='font-size: 14px; color: #FFF;'>Confirm password</td>
					<td style='padding-left: 20px;'><input type='password' name='u_pass2' style='width: 140px;'></td>
				</tr>
				<tr>
					<td align='center' colspan='2' style='padding-top: 15px;'>
						<button type='submit' style='display: block; box-sizing: content-box; padding-top: 0px; padding-bottom: 0px; font-weight: normal; padding-left: 10px; width: auto !important; float: initial; padding-right: 10px;' class="panelButton">Set password</button>
					</td>
				</tr>
			</table>
			</form>
		<?php } ?>
	</div>
</div>
