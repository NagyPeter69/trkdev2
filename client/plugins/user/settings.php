<link rel="stylesheet" media="screen" type="text/css" href="css/colorpicker2.css" />
<script type="text/javascript" src="js/colorpicker.js"></script>

<form id='subForm2' method='post' action=''>
<div style="min-width: 400px;">

	<div class='panelTitle'><?= $lang["settings"]["user_settings"] ?></div>
	<div>
		<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>
			<tr>
				<td align='left'>
					<?= $lang['settings']['name'] ?>
				</td>
				<td align='left'>
					<input type="text" id="u_fullname" name="u_fullname" value="<?= $user[0][7] ?>">
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['mail'] ?>
				</td>
				<td align='left'>
					<input type="text" id="u_mail" name="u_mail" value="<?= $user[0][5] ?>">
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['unit'] ?>
				</td>
				<td align='left'>
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
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['safety'] ?>
				</td>
				<td align='left'>
					<input type='checkbox' <?= ( $user[0][25] == "1" ? "checked" : "" ) ?> name='safety_on' value='1' onclick='set_safety()'>
					<input type='text' name='safety_int' value='<?= $user[0][24] ?>' onkeydown='numberCheck2(event)' style='width: 30px; margin-left: 10px;'>	
					<span id='si'><?= $user[0][16] ?></span>
				</td>
			</tr>
			<tr>
				<td align='left' style='height: 22px;'>
					<?= $lang['settings']['prepresstools'] ?>
				</td>
				<td align='left' style='height: 22px;'>
					<input type='checkbox' <?= ( $user[0][26] == "1" ? "checked" : "" ) ?> name='prepresstools_on' value='1' onclick='set_safety()'>
				</td>
			</tr>			
			<tr>
				<td align='left'>
					<?= $lang['settings']['landing'] ?>
				</td>
				<td align='left'>
					<select name='u_landing'>
						<?
							$options = array(
										$lang["menu"]["publications"] => 'create_magazine',
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
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['language'] ?>
				</td>
				<td align='left'>
					<select name='u_lang'>
						<?
						$langselect = array( "en", "hu" );
						
						for( $i = 0; $i < count( $langselect ); $i++ ) {
							echo "<option value='".$langselect[$i]."'";
							if( $user[0][17] == $langselect[$i] ) echo " selected";
							echo ">".$lang["settings"][$langselect[$i]]."</option>";
							}
						?>
					</select>	
				</td>
			</tr>
			<tr>
				<td align='left' style='height: 22px;'>
					<?= $lang['settings']['mycolor'] ?>
				</td>
				<td align='left' style='height: 22px;'>
					<div id="mycolor" style="border: 1px solid #CCC; width: 30px; height: 15px; background-color: <?= $user[0][30] ?>;"></div>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<hr class='userSettingsLine'>
				</td>
			</tr>
		</table>
		
   		<?php
		$magID = explode( ",", $user[0][21] );
		$magazines = array();
		for( $i = 0; $i < count( $magID ); $i++ ) {
			$mag = sql_aget( 'magazines', 'id="'.$magID[$i].'" LIMIT 1', '*' );
			for( $y = 0; $y < count( $mag ); $y++ ) {
				$magazines[] = $mag[$y];
				}
			}
		
		if( count( $magazines ) > 30 ) {
			echo '<div style="-webkit-columns: 3 200px; -moz-columns: 3 200px; columns: 3 200px; -webkit-column-gap: 2em; -moz-column-gap: 2em; column-gap: 2em;";>';
			}
			
		elseif( count( $magazines ) > 10 ) {
			echo '<div style="-webkit-columns: 2 200px; -moz-columns: 2 200px; columns: 2 200px; -webkit-column-gap: 2em; -moz-column-gap: 2em; column-gap: 2em;";>';
			}	
		
		for( $i = 0; $i < count( $magazines ); $i++ ) {
			$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $magazines[$i]["code"] )
						break;
					}
				}
			
			$pmdmails = (string) $xml->Item[$x]->Mails;
			$pmdmails = explode( ";", $pmdmails );
			
			echo "<div style='display: inline-block; width: 150px; padding-top: 2px; padding-bottom: 2px;'>".$magazines[$i]["name"]."</div>";
			echo "<div style='display: inline-block; padding-top: 2px; padding-bottom: 2px;'>Mails&nbsp;&nbsp;<input type='checkbox' name='userMails[]' value='".$magazines[$i]["code"]."' ".( in_array( $user[0][5], $pmdmails ) ? "checked" : "" )."></div>";
			echo "<div style='clear: both;'></div>";
			
			/*echo "<table><tr>";
				echo "<td style='height: 22px;'>".$magazines[$i]["name"]."</td>";
				echo "<td style='height: 22px;'>Mails&nbsp;&nbsp;<input type='checkbox' name='userMails[]' value='".$magazines[$i]["code"]."' ".( in_array( $user[0][5], $pmdmails ) ? "checked" : "" )."></td>";
			echo "</tr></table>";*/
			}

		if( count( $magazines ) > 10 ) {
			echo '</div>';
			}
			
		?>		
		
		<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>
			<tr>
				<td colspan="2">
					<hr class='userSettingsLine'>
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['multiple_logins'] ?>
				</td>
				<td align='left'>
					<input onclick="confMultiLogin()" type="checkbox" value="1" <?= ( $user[0][27] == "1" ? "checked" : "" ) ?> id="multiplelogin" name="multiplelogin">
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['old_pw'] ?>
				</td>
				<td align='left'>
					<input type="password" autocomplete="off" id="o_pass" name="o_pass">
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['new_pw'] ?>
				</td>
				<td align='left'>
					<input type="password" autocomplete="off" id="u_pass" name="u_pass">
				</td>
			</tr>
			<tr>
				<td align='left'>
					<?= $lang['settings']['new_pw2'] ?>
				</td>
				<td align='left'>
					<input type="password" autocomplete="off" id="u_pass2" name="u_pass2">
				</td>
			</tr>
			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'user_settings', 'back', 'floatMenu' )" style="margin-left: 2px; display: inline-block; float: inherit;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'user', 'settings', 'floatMenu')" style="margin-left: 20px; display: inline-block; float: inherit;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>			
		</table>
	</div>

</div>
</form>

<script>
function saveColor( color, magid ) {
	$.ajax	({
		url:"plugins/userApply.php",
		data: 'sub=savecolor&color='+color,
		dataType: 'json',
		success:function( data ) {}
		});	
	}	
	
function rgb2hex(rgb) {
    if (/^#[0-9A-F]{6}$/i.test(rgb)) return rgb;

    rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    function hex(x) {
        return ("0" + parseInt(x).toString(16)).slice(-2);
    }
    return hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
}

$('#mycolor').off();	
$('#mycolor').ColorPicker({
	onBeforeShow: function () {
		$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
	},
	onShow: function (colpkr) {
		console.log( colpkr );
		$(colpkr).fadeIn(500);
		$(".hided").hide(0);
		return false;
	},
	onHide: function (colpkr) {
		$(colpkr).fadeOut(500);
		return false;
	},
	onChange: function (hsb, hex, rgb) {
		saveColor( hex );
		$("#mycolor").css('background-color', '#' + adjustColor( hex, +0 ) );
	}
})
.bind('click', function(){
	$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
	});		
	
function confRedo() {
	$("#multiplelogin").prop('checked', false); 
	}

function confMultiLogin() {
	if( $( "#multiplelogin" ).is( ':checked' ) ) {
		var buttons = "<button onclick='messageBoxHide();' class='yes'>Agree</button>&nbsp;<button onclick='messageBoxHide(); confRedo();' class='no'>Cancel</button>";
		messageBox( "error", "Are you sure?", "<?= $lang['settings']['multiple_logins_conf'] ?>", buttons );
		}
	}

$("select[name='u_unit']").change( function(){
	$("#si").html( $(this).val() );
	$("input[name='safety_int']").keyup();
	});

function set_safety() {
	$("input[name='safety_int']").keyup();
	}

$("input[name='safety_int']").keyup( function(){
	var page_url = getUrlParameter("page");
	if( page_url == "flatplan_preview" ) {
		safety_on = ( $('input[name="safety_on"]:checked').val() == "1" ) ? "1" : "0";
		safety = $(this).val();
		si = $("select[name='u_unit']").val();
		createBoxes();
		}
	});

</script>