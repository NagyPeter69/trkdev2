<?
	switch( $_GET['opt'] ) {
		case 'pmd':
			if( !$rights['magazine_settings'] ) { $_GET['opt'] = 'personal'; }
			break;
		}
	
	if( isset( $_POST['u_mail'] ) ) {
		$error = 0;
		$e_code = '';

		$pass_change = 0;
		if( $_POST['u_fullname'] == '' ) {
			$error = 1;
			$e_code .= $lang["settings"]["error"];
			goto error;
			}
		if( $_POST['u_mail'] == '' ) {
			$error = 1;
			$e_code .= $lang["settings"]["error2"];
			goto error;
			}
		else {
			if( $user[0][3] != $_POST['u_mail'] ) {
				$mails = sql_get( 'users', '', 'mail' );
				$match = 0;
			
				for( $i = 0; $i < count( $mails ); $i++ ) {
					if( $mails[$i][0] == $_POST['u_mail'] )
						$match = 1;
						}
			
				if (!filter_var( $_POST['u_mail'], FILTER_VALIDATE_EMAIL)) {
					$error = 1;
					$e_code .= $lang["settings"]["error3"];
					}
				elseif( $match == 1 ) {
					$error = 1;
					$e_code .= $lang["settings"]["error4"];
					goto error;
					}
				}				
			}
			
		if( isset( $_POST['o_pass'] ) && ( $_POST['o_pass'] != '' or $_POST['u_pass'] != '' or $_POST['u_pass2'] != '' ) ) {
			if( $_POST['o_pass'] != '' && $_POST['u_pass'] != '' && $_POST['u_pass2'] != '' ) {
				$pass = sql_get( 'accounts', 'id=\''.$_SESSION['intra_user'].'\'', 'pass' );
			
				if( checkPassword( $_POST['o_pass'], $pass[0][0], $_SESSION['intra_user'] ) ) {
					if( $_POST['u_pass'] == $_POST['u_pass2'] ) {
						$pass_change = 1;
						}
					else {
						$error = 1;
						$e_code .= $lang["settings"]["error5"];
						goto error;
						}
					}
				else {
					$error = 1;
					$e_code .= $lang["settings"]["error6"];
					goto error;
					}
				}
			else {
				$error = 1;
				$e_code .= $lang["settings"]["error7"];
				goto error;
				}		
			}
				
		error:
		if( $error == 1 ) {
			$ok = 0;
			}		
		if( $error == 0 ) {
			$table = 'accounts';
			$names = array(  'email', 'full_name', 'landing', 'unit' );
			$datas = array(  $_POST['u_mail'], $_POST['u_fullname'], $_POST['u_landing'], $_POST['u_unit'] );
						$command = '';
			for( $i = 0; $i < count( $names ); $i++ ) {
				$command .= $names[$i].'=\''.$datas[$i].'\'';
		
				if( $i < count( $names )-1 ) {
					$command .= ', ';
					}
				}
				
			if( $pass_change == 1 ) {
				$command .= ', pass=\''.password_hash($_POST['u_pass'], PASSWORD_DEFAULT).'\'';
				}
		
			sql_update( 'accounts', $command, 'id=\''.$_SESSION['intra_user'].'\'' );
			$ok = 1;
			$e_code = $lang["settings"]["success"];
			}
		
		}
	
	if( isset( $_POST['ki_set'] ) ) {
		$_POST['Mails'] = explode( "\r\n", $_POST['Mails'] );
		$_POST['Mails'] = trim( implode( ";", $_POST['Mails'] ) );
		$_POST['old_code'] = $_GET['code'];
		$_POST['deny'] = 'ki_set';
		
		changeXmlDatabase( 'modify', $_POST, 'xml/'.PMD.'.xml' );
		$e_code = $lang["settings"]["success"];
		}

	if( isset( $_POST['color_set'] ) ) {
		$_POST['old_code'] = $_GET['code'];
		$_POST['deny'] = 'color_set';
		
		changeXmlDatabase( 'modify', $_POST, 'xml/'.PMD.'.xml' );
		$e_code2 = $lang["settings"]["success"];
		}
	
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	
	if( !isset( $_GET['opt'] ) or $_GET['opt'] == '' ) {
		$_GET['opt'] = 'personal';
		}

?>

<div class='content_title' style=''>
	<div style='float: left; padding-left: 23px; font-size: 20px;'>
		<b><?= $lang["menu"]['settings'] ?>&nbsp;&nbsp;&nbsp;&bull;&nbsp;&nbsp;&nbsp;</b>
		<span style="color: #444;"><?= $lang["settings"][ $_GET['opt'] ] ?></span>
	</div>
	
	<?
		$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
		$pubs = sql_get( 'magazines', 'publisher_id="'.$sql[0][0].'" ORDER BY `code` ASC', '*' );
		$anothers = explode( ",", $user[0][10] );
		foreach( $anothers as $another ) {
			$temp = sql_get( 'magazines', 'publisher_id="'.$another.'" ORDER BY `code` ASC', '*' );
			for( $i = 0; $i < count( $temp ); $i++ ) {
				$pubs[] = $temp[$i];
				}
			}
		
		if( ( !isset( $_GET['code'] ) or $_GET['code'] == '' ) && $_GET['opt'] == 'pmd' ) {
			$_GET['code'] = $pubs[0][3];
			}
		
		if( $_GET['opt'] == 'personal' ) {
	?>
			<form id='settings' method='post' action='?page=settings&opt=<?= $_GET['opt'] ?>'>
			<div style='float: left; padding-left: 25px; margin-top: -2px;'>
				<button id='set_go' name='set_go' value='Mentés' style='padding: 5px 10px 5px 10px;'>Mentés</button>
			</div>
	<?
			}
		if( $_GET['opt'] == 'pmd' ) {
	?>
			<div style='float: right; padding-right: 23px;'>
				<select style="font-size: 20px;" onchange="Redirect( $(this).val() )">
				<?
					for( $i = 0; $i < count( $pubs ); $i++ ) {
						$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'name' );
						echo "<option value='".$pubs[$i][3]."' ";
						if( $_GET['code'] == $pubs[$i][3] )
							echo "selected";
						echo ">".$pubs[$i][2]."</option>";
						}
				?>
				</select>
			</div>
	<?
			}
	?>
	
	<div style='float: left; padding-left: 20px;'>
		<?
		if( isset( $ok ) ) {
			if( $ok == 0 ) {
				echo "<span style='color: #BA2727;'>".$e_code."</span>";
				}
			if( $ok == 1 ) {
				echo "<span style='color: #489620;'>".$e_code."</span>";
				}
			}
		?>
	</div>
</div>

<div id='ad_table_wrapper' style='overflow: auto;'>
<?
	if( $_GET['opt'] == 'personal' ) {
?>
	
	<div class='setting_row'>
		<div class='line accepted' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_name'><?= $lang["settings"]["name"] ?></div>
		<div class='settings_value'><input type="text" id="u_fullname" name="u_fullname" value="<?= $user[0][7] ?>" style='width: 200px;'></div>
		<div class='settings_desc'></div>
		
		<div style='clear:both;'></div>
	</div>
	
	<div class='setting_row'>
		<div class='line accepted' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_name'><?= $lang["settings"]["mail"] ?></div>
		<div class='settings_value'><input type="text" id="u_mail" name="u_mail" value="<?= $user[0][5] ?>" style='width: 200px;'></div>
		<div class='settings_desc'></div>

		
		<div style='clear:both;'></div>
	</div>	

	<div class='setting_row' style='margin-bottom: 25px'>
		<div class='line accepted' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_name'><?= $lang["settings"]["unit"] ?></div>
		<div class='settings_value'>
			<select name='u_unit'>
				<?
					$options = array( 'pt', 'mm', 'cm' );
          foreach( $options as $key ) {
            echo "<option value='".$key."'";
            if( $user[0][16] == $key ) echo " selected";
            echo ">".$lang["settings"][ $key ]."</option>";
					}
				?>
			</select>		
		</div>
		<div class='settings_desc'></div>
		
		<div style='clear:both;'></div>
	</div>
	
	<div class='setting_row' style='margin-bottom: 25px'>
		<div class='line accepted' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_name'><?= $lang["settings"]["landing"] ?></div>
		<div class='settings_value'>
			<select name='u_landing'>
				<?
					$options = array(
								$lang["menu"]["start_page"] => '',
								$lang["menu"]["publications"] => 'create_magazine'
								);
				if( $rights['ad_view'] && $rights['ad_send'] && $rights['ad_upload'] && $rights['ad_delete'] ) {
					$options[ $lang["menu"]["advertisement"] ] = 'advertisement';
					}
				foreach( $options as $key => $value ) {
					echo "<option value='".$value."'";
					if( $user[0][9] == $value ) echo " selected";
					echo ">".$key."</option>";
					}
				?>
			</select>		
		</div>
		<div class='settings_desc'></div>
		
		<div style='clear:both;'></div>
	</div>	

	<div class='setting_row'>
		<div class='line accepted' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_name'><?= $lang["settings"]["old_pw"] ?></div>
		<div class='settings_value'><input autocomplete="off" type="password" id="o_pass" name="o_pass"  style='width: 200px;'></div>
		<div class='settings_desc'></div>
		
		<div style='clear:both;'></div>
	</div>

	<div class='setting_row'>
		<div class='line accepted' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_name'><?= $lang["settings"]["new_pw"] ?></div>
		<div class='settings_value'><input autocomplete="off" type="password" id="u_pass" name="u_pass" style='width: 200px;'></div>
		<div class='settings_desc'></div>
		
		<div style='clear:both;'></div>
	</div>
	
	<div class='setting_row'>
		<div class='line accepted' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_name'><?= $lang["settings"]["new_pw2"] ?></div>
		<div class='settings_value'><input autocomplete="off" type="password" id="u_pass2" name="u_pass2" style='width: 200px;'></div>
		<div class='settings_desc'></div>
		
		<div style='clear:both;'></div>
	</div>
	<?
		}
	?>
</div>
</form>

<script>

$('input[name="TrimSize_x"]').keyup( function() {
	if( $('input[name="TrimSize_x"]').val() == '' ) {
		$('input[name="TrimSize_x"]').val('1');
		}
	});
$('input[name="TrimSize_y"]').keyup( function() {
	if( $('input[name="TrimSize_y"]').val() == '' ) {
		$('input[name="TrimSize_y"]').val('1');
		}
	});

function isValidEmailAddress(emailAddress) {
    var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    return pattern.test(emailAddress);
	};

function check_settings() {
	$("#set_go").attr("disabled", "disabled");
	
	if( $("#u_fullname").val() != '' ) {
		$('.line:eq(0)').removeClass("rejected");
		$('.line:eq(0)').addClass("accepted");
		$('.settings_desc:eq(0)').html('');		
		}
	else {
		$('.line:eq(0)').removeClass("accepted");
		$('.line:eq(0)').addClass("rejected");
		$('.settings_desc:eq(0)').html('<?= $lang["settings"]["error"] ?>');
		}

	if( $("#u_mail").val() != '' ) {
		if( isValidEmailAddress( $("#u_mail").val() ) ) {
			$('.line:eq(1)').removeClass("rejected");
			$('.line:eq(1)').addClass("accepted");
			$('.settings_desc:eq(1)').html('');	
			}
		else {
			$('.line:eq(1)').removeClass("accepted");
			$('.line:eq(1)').addClass("rejected");
			$('.settings_desc:eq(1)').html('<?= $lang["settings"]["error3"] ?>');
			}
		}
	else {
		$('.line:eq(1)').removeClass("accepted");
		$('.line:eq(1)').addClass("rejected");
		$('.settings_desc:eq(1)').html('<?= $lang["settings"]["error2"] ?>');
		}
		
	if( $("#o_pass").val() != '' || $("#u_pass").val() != '' || $("#u_pass2").val() != '' ) {
		if(  $("#o_pass").val() != '' ) {
			$('.line:eq(3)').removeClass("rejected");
			$('.line:eq(3)').addClass("accepted");
			$('.settings_desc:eq(3)').html('');
			}
		else {
			$('.line:eq(3)').removeClass("accepted");
			$('.line:eq(3)').addClass("rejected");
			$('.settings_desc:eq(3)').html('<?= $lang["settings"]["error7"] ?>');
			}
			
		if(  $("#u_pass").val() != '' ) {
			$('.line:eq(4)').removeClass("rejected");
			$('.line:eq(4)').addClass("accepted");
			$('.settings_desc:eq(4)').html('');
			}
		else {
			$('.line:eq(4)').removeClass("accepted");
			$('.line:eq(4)').addClass("rejected");
			$('.settings_desc:eq(4)').html('<?= $lang["settings"]["error7"] ?>');
			}
			
		if(  $("#u_pass2").val() != '' ) {
			$('.line:eq(5)').removeClass("rejected");
			$('.line:eq(5)').addClass("accepted");
			$('.settings_desc:eq(5)').html('');
			}
		else {
			$('.line:eq(5)').removeClass("accepted");
			$('.line:eq(5)').addClass("rejected");
			$('.settings_desc:eq(5)').html('<?= $lang["settings"]["error7"] ?>');
			}
		
		if( $("#u_pass").val() == $("#u_pass2").val() && $("#u_pass").val() != '' && $("#u_pass2").val() != '' ) {
			$('.line:eq(5)').removeClass("rejected");
			$('.line:eq(5)').addClass("accepted");
			$('.line:eq(4)').removeClass("rejected");
			$('.line:eq(4)').addClass("accepted");
			$('.settings_desc:eq(4)').html('');
			$('.settings_desc:eq(5)').html('');
			}
		else {
			$('.line:eq(5)').removeClass("accepted");
			$('.line:eq(5)').addClass("rejected");
			$('.line:eq(4)').removeClass("accepted");
			$('.line:eq(4)').addClass("rejected");
			if( $("#u_pass").val() != '' || $("#u_pass2").val() != '' ) {
				$('.settings_desc:eq(4)').html('<?= $lang["settings"]["error5"] ?>');
				$('.settings_desc:eq(5)').html('');
				}
			else {
				$('.settings_desc:eq(4)').html('<?= $lang["settings"]["error7"] ?>');
				$('.settings_desc:eq(5)').html('<?= $lang["settings"]["error7"] ?>');
				}
			}
		}
	else {
		$('.line:eq(3)').removeClass("rejected");
		$('.line:eq(4)').removeClass("rejected");
		$('.line:eq(5)').removeClass("rejected");
		$('.line:eq(3)').addClass("accepted");
		$('.line:eq(4)').addClass("accepted");
		$('.line:eq(5)').addClass("accepted");
		$('.settings_desc:eq(3)').html('');
		$('.settings_desc:eq(4)').html('');
		$('.settings_desc:eq(5)').html('');
		}
	
	if( $('.rejected').length == 0 ) {
		$("#set_go").removeAttr("disabled");
		}
	}

check_settings();
$(':input').keyup( function() {
	check_settings();
	});
	
</script>