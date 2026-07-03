<form id='subForm' method='post' action=''>
<input type="hidden" id="handoutid" name="handoutid" value="<?= $_GET["data"]; ?>">

<div>
	<div class='panelTitle'><?= $lang["hotlinks"]["title"] ?> <?= $_GET['id'] ?></div>
	<div class='panelControl'>

	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td align='left' height='23px'><?= $lang["hotlinks"]["to"] ?></td>
				<td align='left'>
					<input type='text' autocomplete="off" id='mail' name='mail' style='width: 200px;' value="">
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
				<td align='left' height='23px'></td>
				<td align='left'>
					<select name='htype' id='htype'>
						<?
						$options = array( 'downloadpdf', 'flipbooklink' );
						foreach( $options as $key ) {
							echo "<option value='".$key."'>".$lang["flatplan"][ $key ]."</option>";
							}
						?>
					</select>
				</td>
			</tr>
						
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" align="left" style="padding-top: 10px;">
					<div onclick="closePanel( 'hotlink_handout', 'back')" style="margin-left: 2px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
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
	$("#hotlinksend").addClass("btn-disabled");
	$.ajax	({
		url:"plugins/hotlinkApply.php?sub=sendhandout",
		type: "POST",
		data: { settings: $("#subForm").serialize() },
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
				$("#hotlink_handout").hide(200);
				}
			}
		});
	}

</script>