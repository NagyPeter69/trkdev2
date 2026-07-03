<?php
$data = explode( ",", $_GET["data"] );
$dates = sql_aget( "calendar_post", "magazine_id='0' AND publisher_id='".$data[0]."' AND printDay like '".$data[1]."-%' order by id ASC", "*" );
?>

<form id='subForm' method='post' action=''>
<input type="hidden" id="pid" name="pid" value="<?= $data[0]; ?>">

<div>
	<div class='panelTitle'><?= $lang["calendar"]["spec_dates_title"] ?></div>
	<div class='panelControl' style='width: 390px; text-align: left;'>
		<div id="settings">
			<table class='panelTable' cellspacing='0' cellpadding='0'>
				<tr>
					<td valign="top" style="padding-top: 3px;"><img onclick="generateDate()" style="cursor: pointer;" src="images/trk_plus.png"></td>
					<td id="datelist" align='left' width='100%' height='28px'>
					<?php
					
					for( $i = 0; $i < count( $dates ); $i++ ) {
						echo "<table class='userRow' sqlid='".$dates[$i]["id"]."'>";
							echo "<tr>";
								echo "<td>".$lang["calendar"]["spec_name"].":</td>";
								echo "<td><input type='text' name='name' value='".$dates[$i]["specificName"]."' style='width: 150px;'></td>";
								echo "<td>&nbsp;</td>";
								echo "<td><div class='colorBox' style='background-color: ".$dates[$i]["code"]."; width: 13px; height: 13px; margin-top: 3px; display: inline-block; float: left;'></div><div style='display: inline-block; float: left;'><img onclick='removeDate( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'></div></td>";
							echo "</tr>";
							echo "<tr>";
								echo "<td>".$lang["calendar"]["spec_porder"].":</td>";
								echo "<td><input readonly='' value='".$dates[$i]["printDay"]."' class='datepicker' type='text' name='printDay' style='width: 150px;'></td>";
								echo "<td>&nbsp;</td>";
								echo "<td>&nbsp;</td>";
							echo "</tr>";
							echo "<tr>";
								echo "<td>".$lang["calendar"]["spec_sday"].":</td>";
								echo "<td><input readonly='' value='".$dates[$i]["salesDay"]."' class='datepicker' type='text' name='salesDay' style='width: 150px;'></td>";
								echo "<td>&nbsp;</td>";
								echo "<td>&nbsp;</td>";
							echo "</tr>";
						echo "</table>";
						}
						
					?>						
					</td>
				</tr>
	
				<tr>
					<td colspan="2" align="center" style="padding-top: 20px;">
						<div onclick="closePanel( 'mwcalendar_specdates', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
						<div onclick="saveDates()" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
					</td>
				</tr>				
			</table>
		</div>
	</div>
</div>
</form>

<script>
$('.datepicker:not(.hasDatepicker)').datepicker({
		dateFormat: 'yy-mm-dd',
		separator: ' ',
		});	
		
function colorpick() {
	$("#mwcalendar_specdates .colorBox").off();	
	$("#mwcalendar_specdates .colorBox").ColorPicker({
		onBeforeShow: function () {
			colorField = this;
			$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
		},
		onShow: function (colpkr) {
			currentPCK = colpkr;
			$(colpkr).fadeIn(500);
			$(".hided").hide(0);

			return false;
		},
		onHide: function (colpkr) {
			$(colpkr).fadeOut(500);
			return false;
		},
		onChange: function (hsb, hex, rgb) {
			color_hex = hex;
			$(colorField).css('background-color', '#' + hex);
		}
		
	})
	.bind('click', function(){
		$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
		});	
	}
colorpick();

function removeDate( obj ) {
	$(obj).parent().parent().parent().parent().parent().remove();
	setDivCenter_visitor( "mwcalendar_specdates" );
	}

function generateDate() {
	var html = "<table class='userRow' sqlid='new'><tr><td><?= $lang['calendar']['spec_name']?>:</td><td><input type='text' name='name' style='width: 150px;'></td><td>&nbsp;</td><td><div class='colorBox' style='background-color: #FFF; width: 13px; height: 13px; margin-top: 3px; display: inline-block; float: left;'></div><div style='display: inline-block; float: left;'><img onclick='removeDate( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'></div></td></tr><tr><td><?= $lang['calendar']['spec_porder']?>:</td><td><input readonly='' class='datepicker' type='text' name='printDay' style='width: 150px;'></td><td>&nbsp;</td><td>&nbsp;</td></tr><tr><td><?= $lang['calendar']['spec_sday']?>:</td><td><input readonly='' class='datepicker' type='text' name='salesDay' style='width: 150px;'></td><td>&nbsp;</td><td>&nbsp;</td></tr></table>";
	
	$("#datelist").append( html );
	setDivCenter_visitor( "mwcalendar_specdates" );
	
	$('.datepicker:not(.hasDatepicker)').datepicker({
		dateFormat: 'yy-mm-dd',
		separator: ' ',
		});	

	colorpick();
	}

function saveDates() {
	var data = new Array();
	var row = {};
	var error = false;
	$(".userRow").each(function() {
		row = {};
		row.sqlid = $(this).attr("sqlid");
		row.name = $(this).find("input[name='name']").val();
		row.printday = $(this).find("input[name='printDay']").val();
		row.salesday = $(this).find("input[name='salesDay']").val();
		row.color = $(this).find(".colorBox").css("background-color");
		row.pubid = "<?= $data[0] ?>";
		row.magid = "0";
		
		$(this).find("input[name='name']").css("background-color", "");
		$(this).find("input[name='printDay']").css("background-color", "");
		$(this).find("input[name='salesDay']").css("background-color", "");
		if( row.name == "" ) {
			$(this).find("input[name='name']").css("background-color", "rgb(209, 69, 80)" );
			error = true;
			}

		if( row.printday == "" ) {
			$(this).find("input[name='printDay']").css("background-color", "rgb(209, 69, 80)" );
			error = true;
			}
		
		if( error == false ) {		
			data.push( row );
			}
		});
		
	if( error == false ) {
		console.log( "DEBUG" );
		console.log( row );
		console.log( "plugins/calendar.php?op=savedates&d=<?= $_GET["data"] ?>&pub=<?= $data[0] ?>" );
		$("#mwcalendar_specdates .panelButton").addClass("disabled");
		$.ajax	({
			url:"plugins/calendar.php?op=savedates&d=<?= $_GET["data"] ?>&pub=<?= $data[0] ?>",
			type: "POST",
			data: { data : data },
			dataType: 'json',
			success:function( data ) {
				loadMags();
				loadCalendar( year );
				closePanel( 'mwcalendar_specdates', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' );
				}
			});
		}	
	}

</script>