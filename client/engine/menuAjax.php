<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
		
	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}
	// See client/plugins/pubsApply.php's 2026-09-05 fix - none of
	// this file's op== handlers checked authentication before
	// running. Same fix: one gate before any op is dispatched.
	if( empty( $user[0][0] ) ) {
		print json_encode( array( array( "Unauthorized" ) ) );
		exit;
		}

	if( !empty( $user[0][17] ) ) {	
		include_once('../lang/'.$user[0][17].'.php');	
		}
	else {
		include_once('../lang/en.php');	
		}

	if( $_GET["op"] == "memberStatList" ) {
		$txt = "";
		$users = sql_aget( "accounts", "publisher='".$_GET["value"]."' order by full_name ASC", "*" );
		for( $i = 0; $i < count( $users ); $i++ ) {
			$txt .= "<tr>";
				$txt .= "<td style='padding-bottom: 1px;'>".$users[$i]["full_name"]."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>".$users[$i]["name"]."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>".$users[$i]["email"]."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>".date( "Y-m-d H:i", $users[$i]["lastlogin"])."</td>";
			$txt .= "</tr>";
			}

		$result = $txt;
		}

	if( $_GET["op"] == "findAccount" ) {
		$txt = "";
		$term = "%".$_GET["value"]."%";
		$users = sql_aget( "accounts", "(name LIKE '".$term."' OR email LIKE '".$term."' OR full_name LIKE '".$term."') ORDER BY full_name ASC", "*" );
		for( $i = 0; $i < count( $users ); $i++ ) {
			$pub = sql_get( "publishers", "id='".$users[$i]["publisher"]."'", "name" );
			$label = htmlspecialchars( $users[$i]["full_name"]." (".$users[$i]["email"].")", ENT_QUOTES );
			$txt .= "<tr>";
				$txt .= "<td style='padding-bottom: 1px;'>".$users[$i]["id"]."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>".$users[$i]["full_name"]."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>".$users[$i]["name"]."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>".$users[$i]["email"]."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>".( $pub[0][0] ?? $users[$i]["publisher"] )."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>".date( "Y-m-d H:i", $users[$i]["lastlogin"])."</td>";
				$txt .= "<td style='padding-bottom: 1px;'>";
				if( $rights['accounts_removeMember'] ) {
					$txt .= "<div class='panelButton' onclick=\"deleteFoundAccount(".$users[$i]["id"].", '".$label."')\">".$lang["standard"]["remove"]."</div>";
					}
				$txt .= "</td>";
			$txt .= "</tr>";
			}

		$result = $txt;
		}

	if( $_GET["op"] == "generatejobmenu" ) {
		$txt = "";
		$head = 0;
		$mag = sql_get( "jobs", "id='".$_POST['info'][1]."'", "*" );
		
		$main = array();

		if( $rights["jobs_modify"] ) {
			$head = 1;
			$txt .= "<div onclick=\"settingsPanel('jobs_settings', 'line_".$_POST['info'][1]."Float', '".$_POST['info'][1]."' )\" class='mainmenu'>";
				$txt .= $lang["publications"]["job"];
			$txt .= "</div>";	
			}
		
/*		
		if( $rights["jobs_ftp"] ) {
			$head = 1;
			$txt .= "<div onclick=\"settingsPanel('jobs_ftp', 'line_".$_POST['info'][1]."Float', '".$_POST['info'][1]."' )\" class='mainmenu'>";
				$txt .= $lang["publications"]["ftp"];
			$txt .= "</div>";	
			}
		*/
		
      	$main = array();
		if( $rights["jobs_delete"] )	$main[] = "delete_job";
		if( $head && count($main) > 0 ) $txt .= "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
      
		for( $i = 0; $i < count( $main ); $i++ ) {
			$show = false;
        	$onclick = "";
        
			switch( $main[$i] ) {
				case 'delete_job':
            		$onclick = "deleteJob( 'job_delete', 'line_".$_POST['info'][1]."Float', '".$_POST['info'][1]."' )";
            		$show = true;
            		break;
          		}
        	if( $show ) {
				$txt .= "<div class='mainmenu' onclick=\"".$onclick."\">";
            		$txt .= $lang["publications"][ $main[$i] ];
          		$txt .= "</div>";
          		}
        	}		
		$result = $txt;
		}

	if( $_GET["op"] == "AdHocMemberList" ) {
		$txt = "";
		$groups = array();
		$sql = sql_get( "ad_hoc_users", "client='".$_GET["value"]."' ORDER BY `name` ASC", "id, name" );
		
		for( $i = 0; $i < count( $sql ); $i++ ) {
			$groups[ $sql[$i][0] ] = $sql[$i][1];
			}
			
		$txt .= "<option value=''>".$lang['settings']['ftp_set']."</option>";
			
		foreach( $groups as $key=>$value ) {
			$name = explode( "_", $key );
			$txt .= "<option value='".$key."'>".$value."</option>";
			}		
			
		$result = $txt;
		}

	if( $_GET["op"] == "memberList" ) {
		$txt = "";
		$groups = array();
		$sql = sql_get( "accounts", "publisher='".$_GET["value"]."' ORDER BY `name` ASC", "id, name, full_name" );
		
		for( $i = 0; $i < count( $sql ); $i++ ) {
			$groups[ $sql[$i][0] ] = $sql[$i][1]." – ".$sql[$i][2];
			}
			
		$txt .= "<option value=''>".$lang['settings']['ftp_set']."</option>";
			
		foreach( $groups as $key=>$value ) {
			$name = explode( "_", $key );
			$txt .= "<option value='".$key."'>".$value."</option>";
			}		
			
		$result = $txt;
		}
	
	if( $_GET["op"] == "ftpList" ) {
		$txt = "";
		$xml = simplexml_load_file( '../xml/Output_Details.xml' );
		$pub = sql_get( 'publishers', 'id="'.$_GET["value"].'"', 'name' );		
		$ftp = getFTPList( $xml, $pub );
		
		$txt .= "<option value=''>".$lang['settings']['ftp_set']."</option>";
		foreach( $ftp as $key=>$value ) {
			$name = explode( "_", $key );
			$txt .= "<option value='".$key."'>".$name[0]." ( ".$name[1]." )</option>";
			}
		$txt .= "<option value='archive'>Archive</option>";
		$result = $txt;
		}
	
	if( $_GET["op"] == "lMagazines" ) {
		$txt = "";
		if( $_GET["value"] == 6 ) {
			$sqlGet = sql_aget( "magazines", "1 ORDER BY `name`", "id, name" );
			}
		else {
			$sqlGet = sql_aget( "magazines", "publisher_id='".$_GET["value"]."' ORDER BY `name`", "id, name" );
			}
			
		for( $i = 0; $i < count( $sqlGet ); $i++ ) {
			$txt .= "<input type='checkbox' name='aMagazines[]' id='aMagazines' value='".$sqlGet[$i]["id"]."'>&nbsp;".$sqlGet[$i]["name"]."<br>";
		 	}	
		$result = $txt;
		}
	
	if( $_GET["op"] == "getIssueMenu" ) {
		$menus = $_POST["menus"];
		
		$magazine = sql_get( 'magazines', 'code="'.$_GET["mag"].'"', 'id' );
		$issue = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'" AND code="'.$_GET['issue'].'"', 'id, status, publisher_id' );
		$issue = $issue[0];
		$enableApprove = approveMagazine( $issue[0] );
		
		$txt = "";
		for( $i = 0; $i < count( $menus ); $i++ ) {
			$onclick = "";
			switch( $menus[$i] ) {
				case 'stop':
					$onclick = "issueManagement( \"stopIssue\", ".$issue[0].", \"".$_GET["mag"]."_".$_GET['issue']."Float\" )";
					break;
					
				case 'delete':
					$onclick = "issueManagement( \"deleteIssue\", ".$issue[0].", \"".$_GET["mag"]."_".$_GET['issue']."Float\" )";
					break;
					
				case 'archive':
					$onclick = "issueManagement( \"archiveIssue\", ".$issue[0].", \"".$_GET["mag"]."_".$_GET['issue']."Float\" )";
					break;
					
				case 'restart':
					$onclick = "issueManagement( \"restartIssue\", ".$issue[0].", \"".$_GET["mag"]."_".$_GET['issue']."Float\" )";
					break;
					
				case 'approve':
					if( $enableApprove ) {
						$onclick = "issueManagement( \"approveIssue\", ".$issue[0].", \"".$_GET["mag"]."_".$_GET['issue']."Float\" )";
						}
					else {
						$onclick = "alert( \"".$lang["publications"]["cantapprove"]."\" )";
						}
					break;
				}
			
			$txt .= "<div onclick='".$onclick."' class='issueMenuLine'><span style='cursor:pointer;'>".$lang["publications"][ $menus[$i] ]."</span></div>";
			}
		
		if( $rights['modIssue'] ) {
			if( $issue[1] != 'archived' ) {
				$floatId = $_GET["mag"]."_".$_GET['issue']."Float";
				// settingsPanel() reads this dropdown's own offset() to
				// position the new panel it opens, so it can't be removed
				// (or display:none'd - that collapses layout, breaking
				// offset()) before that read happens. visibility:hidden
				// dismisses it visually right away while keeping its
				// layout intact for that read; the follow-up remove() a
				// tick later (well after settingsPanel's synchronous
				// offset() call) clears it from the DOM once it's no
				// longer needed for anything.
				$txt .= "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
				$txt .= "<div onclick='$(\"#".$floatId."\").css(\"visibility\",\"hidden\"); settingsPanel(\"pubs_modIssue\", \"".$floatId."\", ".$issue[0]." ); setTimeout(function(){ $(\"#".$floatId."\").remove(); }, 300);' class='issueMenuLine' style='margin-top: 6px;'><span style='cursor:pointer;'>".$lang["publications"]["modify"]."</span></div>";
				}
			}
		
		$result = $txt;
		}

	if( $_GET["op"] == "getAdUser" ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$txt = "<input type='hidden' name='id' id='id' value='".$_GET['id']."'>";
		$txt .= "<table class='panelTable' width='100%' id='job_names' cellspacing='0' cellpadding='0'>";
		
		$getUser = sql_get( "ad_hoc_users", "id='".$_GET['id']."'", "*" );
		$txt .= "<tr>";
			$txt .= "<td style='width:90px;' align='left' height='23px'>".$lang["settings"]["new_publisher"]."</td>";
			$txt .= "<td align='left'>";
				if( $user[0][4] == 6 ) {
					$txt .= "<select onchange='lMagazines( $(this).val() )' style='margin-left: -1px;' name='u_publisher' id='u_publisher'>";
					$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
					for( $i = 0; $i < count($publishers); $i++ ) {
						$txt .= "<option ";
						if( $getUser[0][3] == $publishers[$i][0] ) $txt .= "selected ";
						$txt .= "value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
						}
					$txt .= "</select>";
					}
				else {
					$publisher = sql_get( "publishers", "id='".$getUser[0][3]."'", "id, name");
					$txt .= "<input type='hidden' name='u_publisher' id='u_publisher' value='".$publisher[0][0]."' readonly>";
					$txt .= "<input type='text' name='' id='' value='".$publisher[0][1]."' readonly>";
					}
			$txt .= "</td>";
		$txt .= "</tr>";
		$txt .= "<tr>";
			$txt .= "<td style='width:90px;' align='left' height='23px'>".$lang["settings"]["fullname"]."</td>";
			$txt .= "<td align='left'><input type='text' autocomplete='off' id='u_fullname' name='u_fullname' style='width: 200px;' value='".$getUser[0][1]."'></td>";
		$txt .= "</tr>";
		$txt .= "<tr>";
			$txt .= "<td style='width:90px;' align='left' height='23px'>".$lang["settings"]["email"]."</td>";
			$txt .= "<td align='left'><input type='text' autocomplete='off' id='u_mail' name='u_mail' style='width: 200px;' value='".$getUser[0][2]."'></td>";
		$txt .= "</tr>";
		$txt .= "</table>";
		
		$result = $txt;
		}
	
	if( $_GET["op"] == "getUser" ) {
		$txt = "<input type='hidden' name='id' id='id' value='".$_GET['id']."'>";
		$txt .= "<table class='panelTable' width='100%' id='job_names' cellspacing='0' cellpadding='0'>";
		
		$getUser = sql_get( "accounts", "id='".$_GET['id']."'", "*" );
		$txt .= "<tr>";
			$txt .= "<td style='width:90px;' align='left' height='23px'>".$lang["settings"]["new_publisher"]."</td>";
			$txt .= "<td align='left'>";
				if( $user[0][4] == 6 ) {
					$txt .= "<select onchange='lMagazines( $(this).val() )' style='margin-left: -1px;' name='u_publisher' id='u_publisher'>";
					$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
					for( $i = 0; $i < count($publishers); $i++ ) {
						$txt .= "<option ";
						if( $getUser[0][4] == $publishers[$i][0] ) $txt .= "selected ";
						$txt .= "value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
						}
					$txt .= "</select>";
					}
				else {
					$publisher = sql_get( "publishers", "id='".$getUser[0][4]."'", "id, name");
					$txt .= "<input type='hidden' name='u_publisher' id='u_publisher' value='".$publisher[0][0]."' readonly>";
					$txt .= "<input type='text' name='' id='' value='".$publisher[0][1]."' readonly>";
					}
			$txt .= "</td>";
		$txt .= "</tr>";
		$txt .= "<tr>";
			$txt .= "<td style='width:90px;' align='left' height='23px'>".$lang["settings"]["fullname"]."</td>";
			$txt .= "<td align='left'><input type='text' autocomplete='off' id='u_fullname' name='u_fullname' style='width: 200px;' value='".$getUser[0][7]."'></td>";
		$txt .= "</tr>";
		$txt .= "<tr>";
			$txt .= "<td style='width:90px;' align='left' height='23px'>".$lang["settings"]["email"]."</td>";
			$txt .= "<td align='left'><input type='text' autocomplete='off' id='u_mail' name='u_mail' style='width: 200px;' value='".$getUser[0][5]."'></td>";
		$txt .= "</tr>";
		$txt .= "<tr>";
			$txt .= "<td style='width:90px;' align='left'>".$lang["settings"]["userGroup"]."</td>";
			$txt .= "<td align='left'>";
				$txt .= "<select style='margin-left: -1px;' id='u_type' name='u_type'>";
				if( $user[0][8] == 2 ) $groups = sql_get( 'user_groups', '1 ORDER BY `name` ASC', 'id, name' );
				else $groups = sql_get( 'user_groups', 'publisher="'.$user[0][4].'" OR publisher="0" ORDER BY `name` ASC', 'id, name' );
				
					for( $i = 0; $i < count( $groups ); $i++ ) {
						$txt .= "<option";
						if( $groups[$i][0] == $getUser[0][8] ) $txt .= " selected";
						$txt .= " value='".$groups[$i][0]."'>".$groups[$i][1]."</option>";
						}
				$txt .= "</select>";
			$txt .= "</td>";
		$txt .= "</tr>";
		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div id='loadedMagazines'>";
				$txt .= "<table width='100%' cellspacing='0' cellpadding='0'>";

				$magazines = explode( ",", $getUser[0][21] );
				if( $getUser[0][4] == 6 ) {
					$sqlGet = sql_aget( "magazines", "1 ORDER BY `name`", "id, name" );
					}
				else {
					$publisher = sql_aget( "publishers", "id='".$getUser[0][4]."'", "*" );
					$sqlGet = sql_aget( "magazines", "publisher_id='".$publisher[0]["id"]."' or pubName='".$publisher[0]["name"]."' ORDER BY `name`", "id, name" );
					
					
					}			
				
				for( $i = 0; $i < count( $sqlGet ); $i += 2 ) {
					$txt .= "<tr>";
					
						if( !empty( $sqlGet[$i]["id"] ) ) {
							$txt .= "<td>";
								$txt .= "<input type='checkbox' name='aMagazines[]' id='aMagazines' value='".$sqlGet[$i]["id"]."' ";
								if( in_array( $sqlGet[$i]["id"], $magazines ) ) $txt .= "checked";
								$txt .= ">&nbsp;".$sqlGet[$i]["name"]."<br>";
							$txt .= "<td>";
							}

						if( !empty( $sqlGet[$i+1]["id"] ) ) {
							$txt .= "<td>";
								$txt .= "<input type='checkbox' name='aMagazines[]' id='aMagazines' value='".$sqlGet[$i+1]["id"]."' ";
								if( in_array( $sqlGet[$i+1]["id"], $magazines ) ) $txt .= "checked";
								$txt .= ">&nbsp;".$sqlGet[$i+1]["name"]."<br>";
							$txt .= "<td>";
							}							
							
					$txt .= "</tr>";
					}

				$txt .= "</table></div>";
			$txt .= "</td>";
		
			/*$txt .= "<td align='left'>".$lang["settings"]["allowedMagazines"]."</td>";
				$txt .= "<td>";
					$txt .= "<div id='loadedMagazines'>";
					$magazines = explode( ",", $getUser[0][21] );
					if( $getUser[0][4] == 6 ) {
						$sqlGet = sql_aget( "magazines", "1 ORDER BY `name`", "id, name" );
						}
					else {
						$sqlGet = sql_aget( "magazines", "publisher_id='".$getUser[0][4]."' ORDER BY `name`", "id, name" );
						}
					for( $i = 0; $i < count( $sqlGet ); $i++ ) {
						$txt .= "<input type='checkbox' name='aMagazines[]' id='aMagazines' value='".$sqlGet[$i]["id"]."' ";
						if( in_array( $sqlGet[$i]["id"], $magazines ) ) $txt .= "checked";
						$txt .= ">&nbsp;".$sqlGet[$i]["name"]."<br>";
						}
					$txt .= "</div>";
				$txt .= "</td>";*/
			$txt .= "</tr>";	
		$txt .= "</table>";
		
		$result = $txt;
		}
	
	if( $_GET["op"] == "getUserGroup" ) {
		$txt = "<input type='hidden' name='id' id='id' value='".$_GET['id']."'>";
		$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
		
		$group = sql_aget( "user_groups", "id='".$_GET['id']."'", "*" );
		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div class='rightsTitle'>Advertisement Handling</div>";
				$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
					$a = array( "ad_view", "ad_send", "ad_upload", "ad_delete", "ad_sizes" );
					$rows = ceil( count($a) / 2 );
					
					$x = 0;
					for( $i = 0; $i < $rows; $i++ ) {
						$txt .= "<tr>";
						for( $y = 0; $y < 2; $y++ ) {
							if( $a[$x] != "" ) {
								$txt .= "<td height='23px' align='left' width='33%'>";
									$txt .= "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
									$txt .= "<td align='left'>";
										$txt .= "<input type='checkbox' ".( $group[0][ $a[$x] ] ? "checked" : "" )." name='".$a[$x]."' value='1'>";
									$txt .= "</td>";						
									$txt .= "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
									$txt .= "</tr></table>";
								$txt .= "</td>";
								}
							$x++;
							}
						$txt .= "</tr>";
						}
				$txt .= "</table>";
			$txt .= "</td>";		
		$txt .= "</tr>";
		
		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div class='rightsTitle'>Basic User Handling</div>";
				$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
					$a = array( "magazine_upload", "magazine_itemlist", "magazine_flatplan", "magazine_download", "ad-hoc_proof", "acceptPage", "sendHotlink", "handouts" );
					$rows = ceil( count($a) / 2 );
					
					$x = 0;
					for( $i = 0; $i < $rows; $i++ ) {
						$txt .= "<tr>";
						for( $y = 0; $y < 2; $y++ ) {
							if( $a[$x] != "" ) {
								$txt .= "<td height='23px' align='left' width='33%'>";
									$txt .= "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
									$txt .= "<td align='left'>";
										$txt .= "<input type='checkbox' ".( $group[0][ $a[$x] ] ? "checked" : "" )." name='".$a[$x]."' value='1'>";
									$txt .= "</td>";						
									$txt .= "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
									$txt .= "</tr></table>";
								$txt .= "</td>";
								}
							$x++;
							}
						$txt .= "</tr>";
						}
				$txt .= "</table>";
			$txt .= "</td>";		
		$txt .= "</tr>";		
		
		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div class='rightsTitle'>Issue Administration</div>";
				$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
					$a = array( "newIssue", "delIssue", "acceptIssue", "archiveIssue", "stopIssue", "modIssue", "modDdIssue", "lengthIssue", "cancelApprove", "manageFP" );
					$rows = ceil( count($a) / 2 );
					
					$x = 0;
					for( $i = 0; $i < $rows; $i++ ) {
						$txt .= "<tr>";
						for( $y = 0; $y < 2; $y++ ) {
							if( $a[$x] != "" ) {
								$txt .= "<td height='23px' align='left' width='33%'>";
									$txt .= "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
									$txt .= "<td align='left'>";
										$txt .= "<input type='checkbox' ".( $group[0][ $a[$x] ] ? "checked" : "" )." name='".$a[$x]."' value='1'>";
									$txt .= "</td>";						
									$txt .= "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
									$txt .= "</tr></table>";
								$txt .= "</td>";
								}
							$x++;
							}
						$txt .= "</tr>";
						}
				$txt .= "</table>";
			$txt .= "</td>";		
		$txt .= "</tr>";			
		
		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div class='rightsTitle'>Comment & Markup</div>";
				$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
					$a = array( "viewComment", "createComment", "replyComment", "deleteComment" );
					$rows = ceil( count($a) / 2 );
					
					$x = 0;
					for( $i = 0; $i < $rows; $i++ ) {
						$txt .= "<tr>";
						for( $y = 0; $y < 2; $y++ ) {
							if( $a[$x] != "" ) {
								$txt .= "<td height='23px' align='left' width='33%'>";
									$txt .= "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
									$txt .= "<td align='left'>";
										$txt .= "<input type='checkbox' ".( $group[0][ $a[$x] ] ? "checked" : "" )." name='".$a[$x]."' value='1'>";
									$txt .= "</td>";						
									$txt .= "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
									$txt .= "</tr></table>";
								$txt .= "</td>";
								}
							$x++;
							}
						$txt .= "</tr>";
						}
				$txt .= "</table>";
			$txt .= "</td>";		
		$txt .= "</tr>";
		
		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div class='rightsTitle'>User & Group Administration</div>";
				$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
					$a = array( "accounts_addMember", "accounts_modifyMember", "accounts_removeMember", "accounts_addGroup", "accounts_modifyGroup", "accounts_removeGroup", "accounts_userStat" );
					$rows = ceil( count($a) / 2 );
					
					$x = 0;
					for( $i = 0; $i < $rows; $i++ ) {
						$txt .= "<tr>";
						for( $y = 0; $y < 2; $y++ ) {
							if( $a[$x] != "" ) {
								$txt .= "<td height='23px' align='left' width='33%'>";
									$txt .= "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
									$txt .= "<td align='left'>";
										$txt .= "<input type='checkbox' ".( $group[0][ $a[$x] ] ? "checked" : "" )." name='".$a[$x]."' value='1'>";
									$txt .= "</td>";						
									$txt .= "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
									$txt .= "</tr></table>";
								$txt .= "</td>";
								}
							$x++;
							}
						$txt .= "</tr>";
						}
				$txt .= "</table>";
			$txt .= "</td>";		
		$txt .= "</tr>";		
				
		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div class='rightsTitle'>Ad-hoc Job Administration</div>";
				$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
					$a = array( "jobs_menu", "jobs_create", "jobs_modify", "jobs_delete", "jobs_upload", "jobs_accept", "jobs_archive", "jobs_stop", "jobs_ftp" );
					$rows = ceil( count($a) / 2 );
					
					$x = 0;
					for( $i = 0; $i < $rows; $i++ ) {
						$txt .= "<tr>";
						for( $y = 0; $y < 2; $y++ ) {
							if( $a[$x] != "" ) {
								$txt .= "<td height='23px' align='left' width='33%'>";
									$txt .= "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
									$txt .= "<td align='left'>";
										$txt .= "<input type='checkbox' ".( $group[0][ $a[$x] ] ? "checked" : "" )." name='".$a[$x]."' value='1'>";
									$txt .= "</td>";						
									$txt .= "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
									$txt .= "</tr></table>";
								$txt .= "</td>";
								}
							$x++;
							}
						$txt .= "</tr>";
						}
				$txt .= "</table>";
			$txt .= "</td>";		
		$txt .= "</tr>";	

		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div class='rightsTitle'>Publication Administration</div>";
				$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
					$a = array( "magazine_add", "magazine_settings", "magazine_delete", "calendar_realdates", "task_lists" );
					$rows = ceil( count($a) / 2 );
					
					$x = 0;
					for( $i = 0; $i < $rows; $i++ ) {
						$txt .= "<tr>";
						for( $y = 0; $y < 2; $y++ ) {
							if( $a[$x] != "" ) {
								$txt .= "<td height='23px' align='left' width='33%'>";
									$txt .= "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
									$txt .= "<td align='left'>";
										$txt .= "<input type='checkbox' ".( $group[0][ $a[$x] ] ? "checked" : "" )." name='".$a[$x]."' value='1'>";
									$txt .= "</td>";						
									$txt .= "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
									$txt .= "</tr></table>";
								$txt .= "</td>";
								}
							$x++;
							}
						$txt .= "</tr>";
						}
				$txt .= "</table>";
			$txt .= "</td>";		
		$txt .= "</tr>";
	
		$txt .= "<tr>";
			$txt .= "<td colspan='2'>";
				$txt .= "<div class='rightsTitle'>Superuser Rights</div>";
				$txt .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
					$a = array( "ftp_create", "ftp_modify", "ftp_delete", "pmdallmodify", "reArchive", "sys_log" );
					$rows = ceil( count($a) / 2 );
					
					$x = 0;
					for( $i = 0; $i < $rows; $i++ ) {
						$txt .= "<tr>";
						for( $y = 0; $y < 2; $y++ ) {
							if( $a[$x] != "" ) {
								$txt .= "<td height='23px' align='left' width='33%'>";
									$txt .= "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
									$txt .= "<td align='left'>";
										$txt .= "<input type='checkbox' ".( $group[0][ $a[$x] ] ? "checked" : "" )." name='".$a[$x]."' value='1'>";
									$txt .= "</td>";						
									$txt .= "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
									$txt .= "</tr></table>";
								$txt .= "</td>";
								}
							$x++;
							}
						$txt .= "</tr>";
						}
				$txt .= "</table>";
			$txt .= "</td>";		
		$txt .= "</tr>";	
	

		/*$i = 0;
		foreach( $group[0] as $key => $value ) {
			if( $i > 2 ) {
				$txt .= "<tr>";
					$txt .= "<td height='23px' align='left' style='width: 185px;'>";
						$txt .= $lang['user_groups'][ $key ];
					$txt .= "</td>";
					$txt .= "<td height='23px' align='left'>";
						$txt .= "<input type='checkbox' ";
						if( $value ) $txt .= "checked";
						$txt .= " name='".$key."' value='1'>";
					$txt .= "</td>";
				$txt .= "</tr>";
				}
			$i++;
			}
		$txt .= "</table>";*/
		
		
		$result = $txt;
		}
	
	if( $_GET["op"] == "loadmenu" ) {
		$menu = explode( "_", $_GET["menu"] );
		ob_start();
			include('../plugins/'.$menu[0].'/'.$menu[1].'.php');
		$result = array( ob_get_clean(), $fpname );
		}

	if( $_GET["op"] == "generatemagmenu" ) {
		$txt = "";
		$head = 0;
		$mag = sql_get( "magazines", "id='".$_POST['info'][2]."'", "*" );
		
		if( $rights["newIssue"] && $mag[0][10] != "Adhoc" ) {
			$head = 1;
			$txt .= "<div onclick=\"settingsPanel('pubs_newIssue', 'line_".$_POST['info'][1]."_".$_POST['info'][2]."Float', '".$_POST['info'][2]."' )\" class='mainmenu'>";
				$txt .= $lang["publications"]["new_pub"]." ".$mag[0][2];
			$txt .= "</div>";	
			}
		if( $rights["magazine_delete"] ) {
			$head = 1;
			$txt .= "<div onclick=\"delConfirm('pubs', 'line_".$_POST['info'][1]."_".$_POST['info'][2]."Float', '".$_POST['info'][1]."', '".$mag[0][2]."' )\" class='mainmenu'>";
				$txt .= $lang["publications"]["del_mag"];
			$txt .= "</div>";	
			}
		
		if( $rights["magazine_settings"] ) {
      
			$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $mag[0][3] ) {
						break;
						}
					}
				}
			$process = (string) $xml->Item[$x]->Workflow;      
			if( $process == "Softproof" ) {
	    		 $main = array();     
	    		}
	    	else {
				if( $mag[0][10] == "Adhoc" ) { $main = array( "job", "color", "ad" ); }
				else { $main = array( "magazine", "color", "ad" ); }
				}
			if( $user[0][8] == 2 ) {
				if( $mag[0][10] == "Adhoc" ) { $main = array( "job", "color", "ad" ); }
				else { $main = array( "magazine", "color", "ad" ); }
				}
	      
	      if( $head && count($main) > 0 ) $txt .= "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
	      
	      for( $i = 0; $i < count( $main ); $i++ ) {
	        $show = false;
	        $onclick = "";
	        
	        switch( $main[$i] ) {
	          case 'job':
	            $onclick = "settingsPanel( 'pubs_jobsettings', 'line_".$_POST['info'][1]."_".$_POST['info'][2]."Float', '".$_POST['info'][2]."' )\"";
	            $show = true;
	            break;
				
	          case 'magazine':
	            $onclick = "settingsPanel( 'pubs_workflow', 'line_".$_POST['info'][1]."_".$_POST['info'][2]."Float', '".$_POST['info'][2]."' )\"";
	            $show = true;
	            break;
	          
	          case 'color':
	            $onclick = "settingsPanel( 'pubs_color', 'line_".$_POST['info'][1]."_".$_POST['info'][2]."Float', '".$_POST['info'][2]."' )\"";
	            $show = true;
	            break;
	          
	          case 'ftp':
	            $onclick = "settingsPanel( 'pubs_ftp', 'line_".$_POST['info'][1]."_".$_POST['info'][2]."Float', '".$_POST['info'][2]."' )\"";
	            $show = true;
	            break;
	          
	          case 'ad':
	            $onclick = "settingsPanel( 'pubs_ads', 'line_".$_POST['info'][1]."_".$_POST['info'][2]."Float', '".$_POST['info'][2]."' )\"";
	            $show = true;
	            break;
	          }
	        
	        if( $show ) {
	          $txt .= "<div class='mainmenu' onclick=\"".$onclick."\">";
	            $txt .= $lang["publications"][ $main[$i] ];
	          $txt .= "</div>";
	          if( count( $sub[ $main[ $i ] ] ?? array() ) > 0 ) {
	            $txt .= "<div id='".$main[ $i ]."_sub' class='subContainer'>";
	            for( $y = 0; $y < count( $sub[ $main[ $i ] ] ?? array() ); $y++ ) {
	              if( $rights[ $main[ $i ]."_".$sub[ $main[ $i ] ][$y] ] ) {
	                $txt .= "<div class='submenu' onclick=\"settingsPanel('".$main[ $i ]."_".$sub[ $main[ $i ] ][$y]."')\">";
	                  $txt .= $lang["menu"][ $main[ $i ]."_".$sub[ $main[ $i ] ][$y] ];
	                $txt .= "</div>";
	                }
	              }
	            $txt .= "</div>";
	            }
	          }
	        }
			}
		
		if( $user[0][4] == "6" && $user[0][3] == "producer" ) {
			$txt .= "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
			
			$txt .= "<div class='mainmenu' onclick=\"settingsPanel( 'user_manage', 'line_".$_POST['info'][1]."_".$_POST['info'][2]."Float', '".$_POST['info'][2]."' )\">Users</div>";
			}			
		$result = $txt;
		}
	
	if( $_GET["op"] == "generatemenu" ) {
		$txt = "";
		//$main = array( "accounts", "ftp", "defaultsettings", "systemsettings" );
		$main = array();
		if( $user[0][8] == 2 ) {
			$main[] = "client";
			}		
			
		$main[] = "accounts";
		//$main[] = "ftp";

		if( $rights["manageFP"] ) {
			$main[] = "manageflatplan";
			}
		
		if( $rights["sys_log"] ) {
			$main[] = "colorstandards";
			$main[] = "switchflow";
			$main[] = "syslog";
			}
			
		$sub = array(
				"ftp" => array(	"create", "modify", "delete" ),
				"accounts" => array( "addMember", "modifyMember", "removeMember", "userStat", "findAccount", "addGroup", "modifyGroup", "removeGroup", "addPlannerGroup", "removePlannerGroup", "addAdhoc", "modAdhoc", "delAdhoc" ),
				"client" => array( "create", "modify", "delete" )
				);

		if( $user[0][8] == 2 ) {
			$sub["accounts"][] = "newclient";
			$sub["accounts"][] = "removelient";
			}

		if( $rights["magazine_add"] ) {		
			$txt .= "<div onclick='settingsPanel(\"pubs_create\")' style='cursor: pointer;'>";
				$txt .= $lang["settings"]["newpub"];
			$txt .= "</div>";
			$txt .= "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
			}
/*
		if( $user[0][8] == 2 ) {	
			$txt .= "<div onclick='settingsPanel(\"pubs_client\")' style='margin-bottom: 3px; cursor: pointer;'>";
				$txt .= $lang["settings"]["newclient"];
			$txt .= "</div>";
			
			$txt .= "<div onclick='settingsPanel(\"pubs_removeclient\")' style='margin-bottom: 3px; cursor: pointer;'>";
				$txt .= $lang["settings"]["removelient"];
			$txt .= "</div>";
			}
*/		
/*		
		if( $rights["jobs_create"] ) {		
			$txt .= "<div onclick='settingsPanel(\"pubs_jcreate\")' style='cursor: pointer;'>";
				$txt .= $lang["settings"]["newjob"];
			$txt .= "</div>";
			$txt .= "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
			}
*/
			
		for( $i = 0; $i < count( $main ); $i++ ) {
			$show = false;
			$onclick = "";
			$addLine = false;
			switch( $main[$i] ) {
				case 'colorstandards':
					$onclick = "settingsPanel( 'colorstandards_list' )";
					$show = true;
					break;

				case 'switchflow':
					$onclick = "settingsPanel( 'switch_flow' )";
					$show = true;
					break;
					
				case 'client':
					$onclick = "toggleDiv( '".$main[ $i ]."_sub' )";
					if( $rights['client_create'] or $rights['client_modify'] or $rights['client_delete'] ) {
						$show = true;
						}
					break;
					
				case 'syslog':
					$onclick = "window.location.href='?page=livelog'";
					if( $rights['sys_log'] ) {
						$show = true;
						}
					break;
					
				case 'manageflatplan':
					$onclick = "window.location.href='?page=flatplan&manage=1'";
					if( $rights['manageFP'] ) {
						$show = true;
						}
					$addLine = true;
					break;
					
				case 'accounts':
					$onclick = "toggleDiv( '".$main[ $i ]."_sub' )";
					if( $rights['accounts_removePlannerGroup'] or $rights['accounts_addPlannerGroup'] or $rights['accounts_addMember'] or $rights['accounts_modifyMember'] or $rights['accounts_removeMember'] or $rights['accounts_addGroup'] or $rights['accounts_modifyGroup'] or $rights['accounts_removeGroup'] ) {
						$show = true;
						}
					break;
				
				case 'ftp':
					$onclick = "toggleDiv( '".$main[ $i ]."_sub' )";
					if( $rights['ftp_create'] or $rights['ftp_modify'] or $rights['ftp_delete'] ) {
						$show = true;
						}
						
					$addLine = true;
					break;
				
				case 'defaultsettings':
					$show = true;
					break;
				
				case 'systemsettings':
					$show = true;
					break;
				}
			
			if( $show ) {
				$txt .= "<div class='mainmenu' onclick=\"".$onclick."\">";
					$txt .= $lang["settings"][ $main[$i] ];
				$txt .= "</div>";
				
				if( count( $sub[ $main[ $i ] ] ?? array() ) > 0 ) {
					$txt .= "<div id='".$main[ $i ]."_sub' class='subContainer'>";	
					for( $y = 0; $y < count( $sub[ $main[ $i ] ] ?? array() ); $y++ ) {
						$showsub = false;
						switch( $sub[ $main[ $i ] ][$y] ) {
							case 'newclient':
								$onclick = "settingsPanel('pubs_client')";
								$name = $lang["settings"][ "newclient" ];
								$showsub = true;
								break;

							case 'removelient':
								$onclick = "settingsPanel('pubs_removeclient')";
								$name = $lang["settings"][ "removelient" ];
								$showsub = true;
								break;
									
							default:
								$onclick = "settingsPanel('".$main[ $i ]."_".$sub[ $main[ $i ] ][$y]."')";
								$name = $lang["menu"][ $main[ $i ]."_".$sub[ $main[ $i ] ][$y] ];
								
								if( $rights[ $main[ $i ]."_".$sub[ $main[ $i ] ][$y] ] ) {
									$showsub = true;
									}
								break;
							}
							
						if( $showsub ) {
							$txt .= "<div class='submenu' onclick=\"".$onclick."\">";
								$txt .= $name;
							$txt .= "</div>";
							
							if( $sub[ $main[ $i ] ][$y] == "userStat" or $sub[ $main[ $i ] ][$y] == "removeGroup" ) {
								$txt .= "<div style='margin-top: 6px; margin-bottom: 2px; margin-left: 40px; border-top: 1px solid #838383;'></div>";
								}
							}
						}
					$txt .= "</div>";
					}
					
				if( $addLine ) {
					$txt .= "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
					}
				}
			}

		// Version/build info - admin-only (same gate as System Log above),
		// shown at the bottom of the hamburger menu.
		if( $rights["sys_log"] ) {
			$txt .= "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
			$txt .= "<div style='padding: 4px 0; font-size: 11px; color: #888; cursor: default;'>v".APP_VERSION." (".APP_BUILD.", ".APP_BUILD_DATE.")</div>";
			}

		$result = $txt;
		}
	
	print json_encode( $result );
	
?>