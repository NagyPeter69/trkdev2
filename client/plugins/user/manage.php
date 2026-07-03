<?php

function loadG( $g, $groups, $ignore ) {
	global $prev_group;
	
	$t = "<select class='group' name='".$g."_group'>";
	foreach( $groups as $key=>$value ) {
		//if( !in_array( $key, $ignore ) ) {
			$t .= "<option ".( $prev_group == $key ? "selected" : "" )." value='".$key."'>".$value."</option>";
		//	}
		}
	$t .= "</select>";
	
	return $t;
	}

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

$mag = sql_aget( "magazines", "id='".$_GET["data"]."'", "*" );
$pub = $mag[0]["publisher_id"];

//$users = sql_aget( "accounts", "publisher='".$pub."' OR publisher='6' GROUP BY `group` order by `group` ASC, `full_name` ASC", "*" );
$users = array();
$type = $mag[0]["type"];
$known = 1;
$showgreenplus = true;

if( $mag[0]["type"] == "Adhoc" ) {
	$pub = sql_aget( "publications", "code='".$mag[0]["code"]."'", "*" );
	$users = sql_aget( "accounts", "publisher='6' order by `group` ASC, `full_name` ASC", "*" );
	
	if( $pub[0]["user"] !== "0" ) {
		$known = 1;
		$users2 = sql_aget( "accounts", "publisher='".$pub[0]["owner"]."' order by `group` ASC, `full_name` ASC", "*" );
		
		$users3 = array_merge( $users, $users2 );
		$users = $users3;
		}
	elseif( $pub[0]["user"] == "0" ) {
		$known = 1;
		$users2 = sql_aget( "accounts", "( FIND_IN_SET('".$mag[0]["id"]."', showMagazines) > 0 ) order by `group` ASC, `full_name` ASC", "*" );
		//$users3 = array_merge( $users, $users2 );
		//$users3 = array_unique(array_merge($users, $users2), SORT_REGULAR);
		
		$emails = array_column($users, 'email');
		$users3 = $users;
		foreach ($users2 as $u) {
			if (!in_array($u['email'], $emails, true) AND $u["type"] != "adhoc" ) {
				$users3[] = $u;
				}
			}
			
		$users = $users3;
		}
		
	else {
		$known = 1;
		$showgreenplus = false;
		}

	}
else {
	$users = sql_aget( "accounts", "publisher='".$pub."' OR publisher='6' order by `group` ASC, `full_name` ASC", "*" );
	}

$users_ = $users;
$userGroups = array();
for( $i = 0; $i < count( $users ); $i++ ) {
	$userGroups[] = $users[$i]["group"];
	}

if( $user[0][8] == 2 ) {
	$groups = sql_get( 'user_groups', '1 ORDER BY `name` ASC', 'id, name' );
	}
else {
	$groups = sql_get( 'user_groups', 'publisher="'.$user[0][4].'" OR publisher="0" ORDER BY `name` ASC', 'id, name' );
	}
	
$temp = array();
for( $i = 0; $i < count( $groups ); $i++ ) {
	if( in_array(  $groups[$i][0], $userGroups ) ) {
		$temp[ $groups[$i][0] ] = $groups[$i][1];
		}
	}
	
$groups = $temp;
?>

<form id='subForm' method='post' action=''>
<input type='hidden' name='mag' value='<?= $_GET["data"] ?>'>
<div>

	<div class='panelTitle' style="margin-bottom: 10px;">User Management : <?= $magazine[0][2] ?></div>
		<div style="overflow: auto; max-height: 75vh; overflow-x: hidden; width: 320px;">
			<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>
				<tr>
					<td valign="top" style='padding-top: 3px;'><img onclick='generateGroup( this )' style='cursor: pointer;' src="images/trk_plus.png"></td>
					<td id="userLists" valign="top" colspan="2" align="left">
						<?php
						$x = -1;
						$prev_group = 0;
						
						$ignore = array();
						$ignore_user = array();

						$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
						$xpath = $xml->xpath('/Publications');
						foreach($xpath as $temp) {
							for( $i = 0; $i < count( $temp->Item ); $i++ ) {
								if( $temp->Item[$i]->Code == $mag[0]['code'] )
									break;
								}
							}
						
						$emails = (string) $xml->Item[$i]->Mails;
						$emails = explode( ";", $emails );
						
						for( $i = 0; $i < count( $users ); $i++ ) {
							$check = explode( ",", $users[$i]["showMagazines"] );
							if( in_array( $mag[0]["id"], $check ) ) {
								
								if( $prev_group != $users[$i]["group"] ) {
									if( $prev_group != "0") {
										//Ideiglenes felhasználók betöltése START
										if( $mag[0]["type"] == "Adhoc" ) {
											$tempusers = sql_aget( "accounts", "`usertype`='Temp' AND `temppubid`='".$pub[0]["id"]."' AND `group`='".$prev_group."'", "*" );
											for( $t = 0; $t < count( $tempusers ); $t++ ) {
												echo "<tr>";
													echo "<td>User:</td>";
													echo "<td>";
														echo "<input type='hidden' name='tempregusersgroup[]' value='".$prev_group."'><input type='text' value='".$tempusers[$t]["email"]."' class='userLine' name='tempregusers[]' groupid='".$prev_group."' style='width: 125px;'>";
													echo "</td>";
													echo "<td>M <input type='checkbox' name='tempregmailto[]' value='".$tempusers[$t]["id"]."' ".( in_array( $tempusers[$t]["email"], $emails ) ? "checked" : "" )."></td>";
													echo "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'>";
												echo "</tr>";												
												}
											}
										//Ideiglenes felhasználók betöltése END
									
										echo "</tbody>";
											echo "<tfoot>";
											echo "<tr>";
												echo "<td>";
													if( $showgreenplus ) {
														echo "<img class='add' onclick='generateUser( this )' style='cursor: pointer;' src='images/trk_plus.png'>&nbsp;&nbsp;";
														}
														
													echo "<img class='add' onclick='generateUser2( this )' style='cursor: pointer;' src='images/trk_plus_yellow.png'>";
												echo "</td>";
												echo "<td>&nbsp;</td>";
												echo "<td>&nbsp;</td>";
											echo "</tr></tfoot>";										
										echo "</table>";
										}
									
									$x++;
									$ignore[] = $prev_group;
									$prev_group = $users[$i]["group"];
									$ignore_user = array();
									
									echo "<table class='userRow'>";
										echo "<tbody><tr>";
											echo "<td>Group:</td>";
											echo "<td>".loadG( $x, $groups, $ignore )."</td>";
											echo "<td>&nbsp;</td>";
											echo "<td style='padding-left: 5px;'><img onclick='removeGroup( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'></td>";
										echo "</tr>";
									}
								echo "<tr>";
									echo "<td>User:</td>";
									echo "<td>".loadU( $prev_group, $users, $ignore_user, $users[$i]["id"] )."</td>";
									echo "<td>M <input type='checkbox' name='mailto[]' value='".$users[$i]["id"]."' ".( in_array( $users[$i]["email"], $emails ) ? "checked" : "" )." ></td>";
									
									if( $type == "Adhoc" ) {
										if( $users[$i]["id"] != $pub[0]["user"] ) {
											echo "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'></td>";
											}
										}
									else {
										echo "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'></td>";
										}
								echo "</tr>";
								$ignore_user[] = $users[$i]["id"];
								}
							}

						//Ideiglenes felhasználók betöltése START
						if( $mag[0]["type"] == "Adhoc" ) {
							$tempusers = sql_aget( "accounts", "`usertype`='Temp' AND `temppubid`='".$pub[0]["id"]."' AND `group`='".$prev_group."'", "*" );
							for( $t = 0; $t < count( $tempusers ); $t++ ) {
								echo "<tr>";
									echo "<td>User:</td>";
									echo "<td>";
										echo "<input type='hidden' name='tempregusersgroup[]' value='".$prev_group."'><input type='text' value='".$tempusers[$t]["email"]."' class='userLine' name='tempregusers[]' groupid='".$prev_group."' style='width: 125px;'>";
									echo "</td>";
									echo "<td>M <input type='checkbox' name='tempregmailto[]' value='".$tempusers[$t]["id"]."' ".( in_array( $tempusers[$t]["email"], $emails ) ? "checked" : "" )."></td>";
									echo "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'>";
								echo "</tr>";								
								}
							}
						//Ideiglenes felhasználók betöltése END
						//Ideiglenes egyéni groupok és felhasználók betöltései START
						$users = sql_aget( "accounts", "`usertype`='Temp' AND `temppubid`='".$pub[0]["id"]."' GROUP BY `group` ", "*" );
						
						$userGroups = array();
						for( $i = 0; $i < count( $users ); $i++ ) {
							$userGroups[] = $users[$i]["group"];
							}
						
						if( $user[0][8] == 2 ) {
							$groups2 = sql_get( 'user_groups', '1 ORDER BY `name` ASC', 'id, name' );
							}
						else {
							$groups2 = sql_get( 'user_groups', 'publisher="'.$user[0][4].'" OR publisher="0" ORDER BY `name` ASC', 'id, name' );
							}
							
						for( $i = 0; $i < count( $groups ); $i++ ) {
							if( in_array(  $groups2[$i][0], $userGroups ) ) {
								$temp2[ $groups2[$i][0] ] = $groups2[$i][1];
								}
							}
							
						$groups2 = $temp2;
						$groups = array_merge( $groups, $groups2 );
						
						for( $i = 0; $i < count( $users ); $i++ ) {
							$check = explode( ",", $users[$i]["showMagazines"] );
							if( in_array( $mag[0]["id"], $check ) ) {
								if( $prev_group != $users[$i]["group"] ) {
									if( $prev_group != "0") {
										//Ideiglenes felhasználók betöltése START
										if( $mag[0]["type"] == "Adhoc" ) {
											$tempusers = sql_aget( "accounts", "`usertype`='Temp' AND `temppubid`='".$pub[0]["id"]."' AND `group`='".$prev_group."'", "*" );
											for( $t = 0; $t < count( $tempusers ); $t++ ) {
												echo "<tr>";
													echo "<td>User:</td>";
													echo "<td>";
														echo "<input type='hidden' name='tempregusersgroup[]' value='".$prev_group."'><input type='text' value='".$tempusers[$t]["email"]."' class='userLine' name='tempregusers[]' groupid='".$prev_group."' style='width: 125px;'>";
													echo "</td>";
													echo "<td>M <input type='checkbox' name='tempregmailto[]' value='".$tempusers[$t]["id"]."' ".( in_array( $tempusers[$t]["email"], $emails ) ? "checked" : "" )."></td>";
													echo "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'>";
												echo "</tr>";												
												}
											}
										//Ideiglenes felhasználók betöltése END
									
										echo "</tbody>";
											echo "<tfoot>";
											echo "<tr>";
												echo "<td>";
													if( $showgreenplus ) {
														echo "<img class='add' onclick='generateUser( this )' style='cursor: pointer;' src='images/trk_plus.png'>&nbsp;&nbsp;";
														}
														
													echo "<img class='add' onclick='generateUser2( this )' style='cursor: pointer;' src='images/trk_plus_yellow.png'>";
												echo "</td>";
												echo "<td>&nbsp;</td>";
												echo "<td>&nbsp;</td>";
											echo "</tr></tfoot>";										
										echo "</table>";
										}
									
									$x++;
									$ignore[] = $prev_group;
									$prev_group = $users[$i]["group"];
									$ignore_user = array();
									
									echo "<table class='userRow'>";
										echo "<tbody><tr>";
											echo "<td>Group:</td>";
											echo "<td>".loadG( $x, $groups2, $ignore )."</td>";
											echo "<td>&nbsp;</td>";
											echo "<td style='padding-left: 5px;'><img onclick='removeGroup( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'></td>";
										echo "</tr>";
									}
								$ignore_user[] = $users[$i]["id"];
								}
							}						
						//Ideiglenes egyéni groupok és felhasználók betöltései END
						//Ideiglenes felhasználók betöltése START
						if( $mag[0]["type"] == "Adhoc" ) {
							$tempusers = sql_aget( "accounts", "`usertype`='Temp' AND `temppubid`='".$pub[0]["id"]."' AND `group`='".$prev_group."'", "*" );
							for( $t = 0; $t < count( $tempusers ); $t++ ) {
								echo "<tr>";
									echo "<td>User:</td>";
									echo "<td>";
										echo "<input type='hidden' name='tempregusersgroup[]' value='".$prev_group."'><input type='text' value='".$tempusers[$t]["email"]."' class='userLine' name='tempregusers[]' groupid='".$prev_group."' style='width: 125px;'>";
									echo "</td>";
									echo "<td>M <input type='checkbox' name='tempregmailto[]' value='".$tempusers[$t]["id"]."' ".( in_array( $tempusers[$t]["email"], $emails ) ? "checked" : "" )."></td>";
									echo "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'>";
								echo "</tr>";								
								}
							}
						//Ideiglenes felhasználók betöltése END							
						

						echo "</tbody>";
						echo "<tfoot>";
							echo "<tr>";
								echo "<td>";
									if( $showgreenplus ) {
										echo "<img class='add' onclick='generateUser( this )' style='cursor: pointer;' src='images/trk_plus.png'>&nbsp;&nbsp;";
										}
										
									echo "<img class='add' onclick='generateUser2( this )' style='cursor: pointer;' src='images/trk_plus_yellow.png'>";
								echo "</td>";
								echo "<td>&nbsp;</td>";
								echo "<td>&nbsp;</td>";
							echo "</tr>";
						echo "</tfoot>";
						echo "</table>";	
						?>
					</td>
				</tr>			
			</table>
		</div>
		
		<div style="width: 100%; text-align: center; margin-top: 10px;">
			<div onclick="closePanel( 'user_manage', 'back', 'floatMenu' )" style="margin-left: 2px; float:inherit; display: inline-block;" class="panelButton">Cancel</div>
			<div id="applyButt" onclick="applyUsers();" style="margin-left: 10px; float:inherit; display: inline-block;" class="panelButton">Apply</div>			
		</div>
	</div>

</div>
</form>

<?php

if( $user[0][8] == 2 ) {
	$groups = sql_get( 'user_groups', '1 ORDER BY `name` ASC', 'id, name' );
	$temp = array();
	for( $i = 0; $i < count( $groups ); $i++ ) {
		$temp[ $groups[$i][0] ] = $groups[$i][1];
		}
		
	$groups = $temp;
	}

?>

<script>
var showgreenplus = <?= ( $showgreenplus ? "true" : "false" ) ?>;
var known = <?= $known ?>;
var users = [];
var groups = [];
var x = parseInt("<?= $x ?>");
var currentGroup;
<?php

foreach( $groups as $key=>$value ) {
	echo "groups.push( [ '".$key."', '".$value."' ] );";
	}

for( $i = 0; $i < count( $users_ ); $i++ ) {
	echo "users.push( [ '".$users_[$i]["id"]."', '".$users_[$i]["group"]."', '".$users_[$i]["full_name"]."' ] );";
	}
	
?>

function applyUsers() {
	$('#applyButt').addClass('disabled');
	$.ajax	({
		url:"plugins/userApply.php?sub=manage",
		type: "POST",
		data: $("#subForm").serialize(),
		dataType: 'json',
		success:function( data ) {	
			$("#user_manage").hide(200, function(){
				$(this).remove();
				});
			}
		});
		
	}

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

function generateGroup( obj ) {
	var txt = "<table class='userRow'><tbody>";
	txt += "<tr>";
		txt += "<td>Group:</td>";
		txt += "<td>";
			txt += "<select class='group' name='"+x+"_group'>";
			
			jQuery.each( groups, function( i, data ) {
				txt += "<option value='"+data[0]+"'>"+data[1]+"</option>";
				});
			
			txt += "</select>";
		txt += "</td>";
		txt += "<td>&nbsp;</td>";
		txt += "<td style='padding-left: 5px;'><img onclick='removeGroup( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'></td>";
		txt += "</tbody><tfoot>";
			txt += "<tr>";
				txt += "<td>";
					if( showgreenplus ) {
						txt += "<img class='add' onclick='generateUser( this )' style='cursor: pointer;' src='images/trk_plus.png'>&nbsp;&nbsp;";
						}
					
					txt += "<img class='add' onclick='generateUser2( this )' style='cursor: pointer;' src='images/trk_plus_yellow.png'>";
				txt += "</td>";
				txt += "<td>&nbsp;</td>";
				txt += "<td>&nbsp;</td>";
			txt += "</tr>";			
		txt += "</tfoot>";
	txt += "</tr>";
	
	x++;
	
	$( "#userLists" ).append( txt );
	}

function setUserCheckbox( obj ) {
	var userID = $(obj).val();
	var target = $(obj).parent().parent().find("input");
	
	$(target).attr("value", userID );
	}

function generateUser2( obj ) {
	var txt = "<tr>";
	
	var group = $(obj).parent().parent().parent().parent().find(".group");
	var table = $(obj).parent().parent().parent().parent();
	group = $(group).val();	
	
	txt += "<td>User:</td>";
	txt += "<td>";
		txt += "<input type='hidden' name='tempusersgroup[]' value='"+group+"'><input type='text' class='userLine' name='tempusers[]' groupid='"+group+"' style='width: 125px;'>";
	txt += "</td>";
	txt += "<td>&nbsp;</td>";
	txt += "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'>";
	
	$( table ).append( txt );
	checker();
	}

function generateUser( obj ) {
	var txt = "<tr>";
	
	var group = $(obj).parent().parent().parent().parent().find(".group");
	var table = $(obj).parent().parent().parent().parent();
	group = $(group).val();	
	
	txt += "<td>User:</td>";
	txt += "<td><select class='userLine' name='users[]' groupid='"+group+"' onchange='setUserCheckbox( $(this) )'>";

	var temp = "";
	jQuery.each( users, function( i, data ) {
		if( group == data[1] ) {
			if( temp == "" ) {
				temp = data[0];
				}
			txt += "<option value='"+data[0]+"'>"+data[2]+"</option>";
			}
		}); 
	
	txt += "</select></td>";
	txt += "<td>M <input type='checkbox' name='mailto[]' value='"+temp+"'></td>";
	txt += "<td style='padding-left: 5px;'><img onclick='removeUser( this )' style='cursor: pointer; vertical-align:middle; padding-left: 3px;' src='images/trk_minus.png'>";
	
	$( table ).append( txt );
	checker();
	}

function removeGroup( obj ) {
	$(obj).parent().parent().parent().parent().remove();
	}

function removeUser( obj ) {
	$(obj).parent().parent().remove();
	checker();
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