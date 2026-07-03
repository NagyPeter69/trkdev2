<?php
$calendar = sql_aget( "calendar_post", "id='".$_GET["data"]."'", "*" );
$users = sql_aget( "accounts", "publisher='".$calendar[0]["publisher_id"]."' order by `full_name` ASC", "*" );
$reminder = sql_aget( "calendar_reminder", "postID='".$_GET["data"]."'", "*" );

$adat = json_decode( $reminder[0]["data"], true );

function loadU( $g, $users, $ignore, $current ) {
	global $prev_group;

	$t = "<select class='userLine' name='users[]' onchange='setUserCheckbox( $(this) )'>";
	
	for( $i = 0; $i < count( $users ); $i++ ) {
		if( $users[$i]["group"] == $g ) { //&& !in_array( $users[$i]["id"], $ignore ) 
			$t .= "<option ".( $current == $users[$i]["id"] ? "selected" : "" )." value='".$users[$i]["id"]."'>".$users[$i]["full_name"]."</option>";
			}
		}
		
	$t .= "</select>";
	
	return $t;
	}

?>

<form id='subForm' method='post' action=''>
<input type='hidden' name='mag' value='<?= $_GET["data"] ?>'>
<div>

	<div class='panelTitle' style="margin-bottom: 10px;">User Management : <?= $calendar[0]["specificName"] ?></div>
		<div>
			Notify users T-
			<select name="notiDay">
				<?php
				for( $i = 1; $i <= 30; $i++ ) {
					echo "<option ".( ($adat['remindDay'] == $i) ? 'selected' : '' )." value='".$i."'>".$i."</option>";
					}
				?>
			</select>
		</div>
		<div style="overflow: auto; max-height: 75vh; overflow-x: hidden; width: 320px;">
			<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>
				<tr>
					<td valign="top" style='padding-top: 3px;'><img onclick='generateUser( this )' style='cursor: pointer;' src="images/trk_plus.png"></td>
					<td id="userLists" valign="top" colspan="2" align="left">
					<?php
					for( $i = 0; $i < count( $adat["users"] ); $i++ ) {
						echo "<tr>";
							echo "<td>User:</td>";
							echo "<td><select class='userLine' name='users[]' onchange='setUserCheckbox( $(this) )'>";
							
							for( $u = 0; $u < count( $users ); $u++ ) {
								echo "<option ".( $users[$u]["id"] == $adat["users"][$i] ? "selected" : "" )." value='".$users[$u]["id"]."'>".$users[$u]["full_name"]."</option>";
								}
							
							echo "</select></td>";
							echo "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'>";
						echo "</tr>";
						}
					?>
					</td>
				</tr>			
			</table>
		</div>
		
		<div style="width: 100%; text-align: center; margin-top: 10px;">
			<div onclick="closePanel( 'mwcalendar_notification', 'back', 'floatMenu' )" style="margin-left: 2px; float:inherit; display: inline-block;" class="panelButton">Cancel</div>
			<div id="applyButt" onclick="applyUsers();" style="margin-left: 10px; float:inherit; display: inline-block;" class="panelButton">Apply</div>			
		</div>
	</div>

</div>
</form>	

<script>
var users = [];

function applyUsers() {
	console.log( $("#subForm").serialize() );
	
	$('#applyButt').addClass('disabled');
	$.ajax	({
		url:"plugins/calendar.php?op=notificationSave",
		type: "POST",
		data: $("#subForm").serialize(),
		dataType: 'json',
		success:function( data ) {	
			$("#mwcalendar_notification").hide(200, function(){
				$(this).remove();
				});
			}
		});
		
	}

<?php
for( $i = 0; $i < count( $users ); $i++ ) {
	echo "users.push( [ '".$users[$i]["id"]."', '0', '".$users[$i]["full_name"]."' ] );";
	}
?>

function checker() {
	$(".userRow").each(function(){
		var u = $(this).find(".userLine");
		u = u.length;

		if( u > 0 ) {
			$(this).find(".group").prop('disabled', 'disabled');
			}
		else {
			$(this).find(".group").prop('disabled', false );
			}
		});
		
	
	}
checker();

function generateUser( obj ) {
	var txt = "<tr>";
	
	var table = $(obj).parent().parent().parent().parent();
	
	txt += "<td>User:</td>";
	txt += "<td><select class='userLine' name='users[]' onchange='setUserCheckbox( $(this) )'>";

	var temp = "";
	jQuery.each( users, function( i, data ) {
		txt += "<option value='"+data[0]+"'>"+data[2]+"</option>";
		}); 
	
	txt += "</select></td>";
	txt += "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'>";
	
	$( table ).append( txt );
	checker();
	}
	
function removeUser( obj ) {
	$(obj).parent().parent().remove();
	checker();
	}
	
</script>				