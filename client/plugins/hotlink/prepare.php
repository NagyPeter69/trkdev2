<?php
$data = explode( "|", $_GET["data"]  );
?>

<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["hotlinks"]["title"] ?> <?= $_GET['id'] ?></div>
	<div class='panelControl'>

	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td align='left' height='23px'><?= $lang["hotlinks"]["to"] ?></td>
				<td align='left'>
					<input type='text' autocomplete="off" id='mail' name='mail' style='width: 200px;' value="">
					&nbsp;&nbsp;
					<span id='loadUsers' onclick='add_mail()' style='cursor: pointer;'><img height='13px' src='images/trk_plus.png'></span>
				</td>
			</tr>

			<tr>
				<td align='left' height='23px'><?= $lang["hotlinks"]["rights"] ?></td>
				<td align='left'>
					<select name='right'>
						<option value="0+0"><?= $lang["hotlinks"]["review"] ?></option>
						<option value="1+0"><?= $lang["hotlinks"]["comment"] ?></option>
						<option selected value="1+1"><?= $lang["hotlinks"]["approve"] ?></option>
					</select>
				</td>
			</tr>

			<tr>
				<td align='left' height='23px'><?= $lang["hotlinks"]["spreads"] ?></td>
				<td align='left'>
					<input type='radio' name='spreads' value='pair'>&nbsp;<?= $lang["standard"]["yes"] ?>&nbsp;&nbsp;&nbsp;&nbsp;<input type='radio' checked name='spreads' value='single'>&nbsp;<?= $lang["standard"]["no"] ?>
				</td>
			</tr>

			<tr>
				<td align='left' height='23px'><?= $lang["hotlinks"]["downloadable"] ?></td>
				<td align='left'>
					<input type='checkbox' name='downloadable' value='1'>
				</td>
			</tr>

			<tr>
				<td align='left' height='23px'><?= $lang["hotlinks"]["expiry"] ?></td>
				<td align='left'>
					<input readonly class='link_datepicker' id='expire' type='text' name='expire' value='<?= date( "Y-m-d H:i", strtotime("tomorrow midnight") ) ?>' style='margin-left: 0px;'>
				</td>
			</tr>

			<tr>
				<td align='left' height='23px'><?= $lang['settings']['language'] ?></td>
				<td align='left'>
					<select name='lang' id='lang'>
						<?
						$options = array( 'hu', 'en', 'de', 'pl' );
						foreach( $options as $key ) {
							echo "<option value='".$key."'";
							if( $user[0][17] == $key ) echo " selected";
							echo ">".$lang["settings"][ $key ]."</option>";
							}
						?>
					</select>
				</td>
			</tr>

			<tr>
				<td align='left' valign='top' height='23px'><?= $lang["hotlinks"]["note"] ?></td>
				<td align='left'>
					<textarea name='note' style='resize: none; width: 200px; height: 40px;'></textarea>
				</td>
			</tr>
			
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" align="left" style="padding-top: 10px;">
					<div onclick="closePanel( 'hotlink_prepare', 'back')" style="margin-left: 2px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div id='hotlinksend' onclick="sendHotlink()" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["send"] ?></div>
				</td>
			</tr>			
		</tbody>
	</table>
	
	</div>	
</div></form>

<div id='loadUsersMenu' class='floatMenu3' style='width: 150px; position: fixed; z-index: 1000; display: none;'></div>

<script>

function sendHotlink() {
	var cbox = new Array();
	var state = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).val() );
		state.push( $(this).attr("state") );
		}); 
	
	$("#hotlinksend").addClass("btn-disabled");
	$.ajax	({
		url:"plugins/hotlinkApply.php?sub=sendhotlink&job=<?= $data[0] ?>&opt=<?= $data[1] ?>",
		type: "POST",
		data: { data: cbox, state: state, settings: $("#subForm").serialize() },
		dataType: 'json',
		success:function( data ) {	
			$("#hotlinksend").removeClass("btn-disabled");	
			if( data[0].length > 0 ) {
				for( var i = 0; i < data[0].length; i++ ) {
					$("#"+data[0][i]).css("background", "#D14550" );
					}
				}
			if( data[0] == "" ) {
				$("#loadUsersMenu").hide(200);
				$("#hotlink_prepare").hide(200);
				}
			}
		});
	}


function getmail( address ) {
	$("#mail").val( address );
	$("#loadUsersMenu").hide( 100 );
	}

function add_mail() {
	var publisher = $("#publisher").val();

	$.ajax({
		url:"engine/job_ajax.php?op=addmail&pub="+publisher,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#loadUsersMenu").css({
				'left': ( $("#loadUsers").offset().left+30 )+'px',
				'top': ( $("#loadUsers").offset().top-40 )+'px'
				});
				
			$("#loadUsersMenu").html( data );
			$("#loadUsersMenu").show( 100 );
			}
		});	
	}

</script>