<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div style="width: 600px;">
	<div class='panelTitle'><?= $lang["settings"]["user_stat"] ?></div>
	<div class='panelControl' style="width: 100%;">
	
	<table class='panelTable' style='width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
		<?
			echo "<tr>";
				echo "<td align='left' style='width: 90px;'>".$lang["settings"]["publisher"]."</td>";
				echo "<td>";
					if( $user[0][4] == 6 ) {
						echo "<select onchange='memberStatList( $(this).val() )' style='margin-left: -1px;' name='u_publisher2' id='u_publisher2'>";
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
				echo "</td>";
			echo "</tr>";
		?>
		</tbody>
	</table>
	<table class='panelTable' style='width: 100%; padding-top: 10px;' id='job_names' cellspacing='0' cellpadding='0'>
		<thead style="font-weight: bold;">
			<tr style="padding-bottom: 1px;">
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["fullname"] ?></td>
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["username"] ?></td>
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["email"] ?></td>
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["lastlogin"] ?></td>
			</tr>
		</thead>
		<tbody id="userStatlist">
		<?php
			$users = sql_aget( "accounts", "publisher='".$publishers[0][0]."' order by full_name ASC", "*" );
			for( $i = 0; $i < count( $users ); $i++ ) {
				echo "<tr>";
					echo "<td style='padding-bottom: 1px;'>".$users[$i]["full_name"]."</td>";
					echo "<td style='padding-bottom: 1px;'>".$users[$i]["name"]."</td>";
					echo "<td style='padding-bottom: 1px;'>".$users[$i]["email"]."</td>";
					echo "<td style='padding-bottom: 1px;'>".date( "Y-m-d H:i", $users[$i]["lastlogin"])."</td>";
				echo "</tr>";
				}
		?>
		</tbody>
	</table>
	
	</div>

	<div id='user_mod_content' style='display:none; margin-top: 10px;'>
	</div>
	
	<table class='panelTable' style='margin-top: 20px; width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tr>
			<td align='center'>
				<div onclick="closePanel( 'accounts_userStat', 'back')" class="panelButton" style="float:inherit; display: inline-block;">Cancel</div>
			</td>
		</tr>
	</table>
	</div>
</div></form>

<script>

function resetPW() {
	$.ajax	({
		url:"plugins/accountsApply.php?sub=resetpw&id="+$( "#account_remove option:selected" ).val(),
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			var temp = $( "#account_remove option:selected" ).text();
			temp = temp.split(" – "); 
			alert("A new password has been issued and a mail notification was sent to user "+temp[0]+".");		
			}
		});		
	}

function regenPW() {
	var temp = $( "#account_remove option:selected" ).text();
	temp = temp.split(" – "); 
	var message = "Do you really want to generate new password for user "+temp[0]+"?";
	trkDialog2(message, function(){ resetPW() }, function(){} );
	}

</script>