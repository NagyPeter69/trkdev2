<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}

if( !empty( $user[0][17] ) ) {	
	include_once('../lang/'.$user[0][17].'.php');	
	}
else {
	include_once('../lang/en.php');	
	}

setlocale(LC_TIME, "hu_HU.utf8");

if( !empty( $_GET["op"] ) ) {
	if( $_GET["op"] == "notificationSave" ) {
		$plannerID = $_POST["mag"];
		
		$data = sql_aget( "calendar_reminder", "postID='".$plannerID."'", "*" );
		
		$adat = array();
		$adat["remindDay"] = $_POST["notiDay"];
		
		for( $i = 0; $i < count( $_POST["users"] ); $i++ ) {
			$adat["users"][] = $_POST["users"][$i];
			}
		$adat = json_encode( $adat );	
		
		if( empty( $data ) ) {
			$names = array( "postID", "data" );
			$values = array( $plannerID, $adat );
			sql_add( "calendar_reminder", $names, $values );
			}
		else {
			sql_update( "calendar_reminder", "data='".$adat."'", "postID='".$plannerID."'" );
			}
					
		}
	
	if( $_GET["op"] == "saveadminplanner" ) {
		sql_update( "accounts", "plannerpub='".$_GET["pid"]."'", "id='".$_GET["uid"]."'" );
		}
	
	if( $_GET["op"] == "savedates" ) {
		$dateID = array();
		$d = explode( ",", $_GET["d"] );
		$dates = sql_aget( "calendar_post", "printDay like '".$d[1]."-%' AND magazine_id='0' AND publisher_id='".$_GET["pub"]."' order by id ASC", "*" );
		for( $i = 0; $i < count( $dates ); $i++ ) {
			$dateID[] = $dates[$i]["id"];
			}
		
		for( $i = 0; $i < count( $_POST["data"] ); $i++ ) {
			if( $_POST["data"][$i]["sqlid"] == "new" ) {
				$names = array( "magazine_id", "salesDay", "printDay", "code", "specificName", "magCode", "publisher_id" );
				$values = array( $_POST["data"][$i]["magid"], $_POST["data"][$i]["salesday"], $_POST["data"][$i]["printday"], $_POST["data"][$i]["color"], $_POST["data"][$i]["name"], "", $_POST["data"][$i]["pubid"] );
				sql_add( "calendar_post", $names, $values );
				}
				
			else {
				sql_update( "calendar_post", "salesDay='".$_POST["data"][$i]["salesday"]."',printDay='".$_POST["data"][$i]["printday"]."', specificName='".$_POST["data"][$i]["name"]."', code='".$_POST["data"][$i]["color"]."'", "id='".$_POST["data"][$i]["sqlid"]."'" );
				
				$dateID = array_diff( $dateID, array( $_POST["data"][$i]["sqlid"] ) );
				}
			}
		
		$dateID = array_values( $dateID );
		for( $i = 0; $i < count( $dateID ); $i++ ) {
			sql_delete( "calendar_post", "id='".$dateID[$i]."'" );
			}		
		
		$result = $dateID;
		}
	
	if( $_GET["op"] == "saveShowDay" ) {
		sql_update( "accounts", "showDays='".$_GET["val"]."'", "id='".$_SESSION['intra_user']."'" );
		}
		
	if( $_GET["op"] == "removeEvent" ) {
		sql_delete( "calendar_events", "id='".$_GET["eid"]."'" );
		}
	
	if( $_GET["op"] == "loadmevents" ) {
		$events = sql_aget( "calendar_events", "magazine_id='".$_GET["mid"]."'", "*" );
		
		if( $rights["calendar_realdates"] ) {
			$txt = "<table cellspacing='0' cellpadding='0' style='margin-top: 4px;'>";
			for( $i = 0; $i < count( $events ); $i++ ) {
				$d1 = date( "m", $events[$i]["start"] );
				$d2 = date( "m", $events[$i]["end"] );
				$year = date( "Y", $events[$i]["end"] );
				
				if( $year == $_GET["year"]) {
					$txt .= "<tr class='mevent' eid='".$events[$i]["id"]."'>";
					if( $d1 == $d2 ) {
						$txt .= "<td style='margin-top: 4px;'>".$events[$i]["name"].": ".strftime( "%b %e" , $events[$i]["start"] )."–".strftime( "%e." , $events[$i]["end"] )."</td>";
						}
					else {
						$txt .= "<td style='margin-top: 4px;'>".$events[$i]["name"].": ".strftime( "%b %e." , $events[$i]["start"] )."–".strftime( "%b %e." , $events[$i]["end"] )."</td>";
						}
					$txt .= "<td align='right'><div onclick='removeEvent( \"".$events[$i]["id"]."\" )' style='cursor: pointer;'><i class='fas fa-minus-circle' style='margin: 0; padding: 0; margin-left: 10px; margin-top: 2px;'></i></div></td>";
					$txt .= "</tr>";
					}
				}
			$txt .= "</table>";
			}
		$result = $txt;
		}
		
	if( $_GET["op"] == "loadmagazines" ) {
		if( $_GET["mode"] == "Internal" ) {
			$txt = "";
			}
		else {		
			$allowed = explode( ",", $user[0][21] );
			$publisher = sql_aget( "publishers", "id='".$_GET["pub"]."'", "*" );

			$txt = "<table cellspacing='0' cellpadding='0' width='100%' height='100%' id='magtable'>";
				$txt .= "<tr>";
					$txt .= "<td valign='top'><div style='padding-bottom: 2px;'><input style='margin: 1px;' type='checkbox' id='selectall' name='selectall' value='all' checked></div></td>";
					$txt .= "<td colspan='2'>";
						$txt .= "<div class='allselect magazines'>".$lang['calendar']['deselectall']."</div>";
						
						$txt .= '<div class="calendarYearBox">';
							$txt .= '<div class="arrow-left"></div>';
							$txt .= '<div class="calendarYear">'.$_GET["year"].'</div>';
							$txt .= '<div class="arrow-right"></div>';
							// Adding a year's Hungarian public holidays used to mean a
							// developer hand-editing the calendarHoliday() array in
							// engine.php and redeploying - this fetches them from
							// date.nager.at into calendar_holidays instead, for
							// whichever year is currently on screen. Admin-only
							// (same convention as other developer-adjacent actions),
							// since it's a state-changing, external-network action.
							if( $user[0][8] == 2 ) {
								$txt .= '<div class="addYearButton" title="'.$lang['calendar']['add_year_title'].'">+ '.$lang['calendar']['add_year'].'</div>';
								}
						$txt .= '</div>';
					$txt .= "</td>";
				$txt .= "</tr>";	
				
				$mags = sql_aget( "magazines", "publisher_id='".$_GET["pub"]."' AND calendarGroup='' order by name ASC", "*" );
				$txt .= "<tr>";
					$txt .= "<td><div><input class='toggleGroup' style='margin: 1px; margin-top: 11px;' type='checkbox' grp='".$publisher[0]["name"]."' name='toggleGroup' value='".$publisher[0]["name"]."' checked></div></td>";
					$txt .= "<td class='magGroupName' colspan='2'><span>".$publisher[0]["name"]."</span></td>";
				$txt .= "</tr>";			
				for( $i = 0; $i < count( $mags ); $i++ ) {
					if( in_array( $mags[$i]["id"], $allowed ) ) {
						$txt .= "<tr>";
							$txt .= "<td><div><input style='margin: 1px; margin-top: 0px; display: block;' type='checkbox' name='showedMagazines[]' value='".$mags[$i]["id"]."' group='Marquard' checked></div></td>";
							$txt .= "<td><div style='' class='draggable magazines' ondragstart='dragStart(event)' ondragend='dragStop(event)' draggable='".(  $rights["calendar_realdates"] ? "true" : "false" )."' magid=".$mags[$i]["id"].">".$mags[$i]["name"]."</div></td>";
							$txt .= "<td><div class='".(  $rights["calendar_realdates"] ? "colorBox" : "altercolorBox" )."' magid='".$mags[$i]["id"]."' style='background-color: #".$mags[$i]["color"]."; margin-top: 4px;'></div></td>";
						$txt .= "</tr>";	
						}
					}
					
				$mags = sql_aget( "magazines", "publisher_id='".$_GET["pub"]."' AND calendarGroup!='' AND calendarGroup!='Hidden' order by calendarGroup ASC, name ASC", "*" );
				$currentGroup = "";
				for( $i = 0; $i < count( $mags ); $i++ ) {
					if( in_array( $mags[$i]["id"], $allowed ) ) {
						if( $mags[$i]["calendarGroup"] != $currentGroup ) {
							$currentGroup = $mags[$i]["calendarGroup"];
							$txt .= "<tr>";
								$txt .= "<td><div><input class='toggleGroup' style='margin: 1px; margin-top: 11px;' type='checkbox' grp='".$mags[$i]["calendarGroup"]."' name='toggleGroup' value='".$mags[$i]["id"]."' checked></div></td>";
								$txt .= "<td class='magGroupName' colspan='2'><span>".$mags[$i]["calendarGroup"]."</span></td>";
							$txt .= "</tr>";
							}

						$txt .= "<tr>";
							$txt .= "<td><div><input style='margin: 1px; margin-top: 0px; display: block;' type='checkbox' group='".$mags[$i]["calendarGroup"]."' name='showedMagazines[]' value='".$mags[$i]["id"]."' checked></div></td>";
							$txt .= "<td><div style='' class='draggable magazines' ondragstart='dragStart(event)' ondragend='dragStop(event)' draggable='".(  $rights["calendar_realdates"] ? "true" : "false" )."' magid=".$mags[$i]["id"].">".$mags[$i]["name"]."</div></td>";
							$txt .= "<td><div class='".(  $rights["calendar_realdates"] ? "colorBox" : "altercolorBox" )."' magid='".$mags[$i]["id"]."' style='background-color: #".$mags[$i]["color"]."; margin-top: 4px;'></div></td>";
						$txt .= "</tr>";					
						}
					}
				
				$specdates = sql_aget( "calendar_post", "publisher_id='".$_GET["pub"]."' AND magazine_id='0' AND printDay like '".$_GET["year"]."-%'", "*" );
				if( count( $specdates ) > 0 ) {
					$calendarGroup = "specDates";
					
					$txt .= "<tr>";
						$txt .= "<td><div><input class='toggleGroup' style='margin: 1px; margin-top: 11px;' type='checkbox' grp='".$calendarGroup."' name='toggleGroup' value='".$mags[$i]["id"]."' checked></div></td>";
						$txt .= "<td class='magGroupName' colspan='2'><span>".$lang["calendar"]["spec_dates_title"]."</span></td>";
					$txt .= "</tr>";
					
					for( $i = 0; $i < count( $specdates ); $i++ ) {
						$txt .= "<tr>";
							$txt .= "<td><div><input style='margin: 1px; margin-top: 0px; display: block;' type='checkbox' group='".$calendarGroup."' name='showedMagazines[]' value='".$specdates[$i]["id"]."' checked></div></td>";
							$txt .= "<td><div style='' class='draggable magazines' ondragstart='dragStart(event)' ondragend='dragStop(event)' magid=".$specdates[$i]["id"].">".$specdates[$i]["specificName"]."</div></td>";
							$txt .= "<td><div class='altercolorBox' plannerid='".$specdates[$i]["id"]."' magid='".$mags[$i]["id"]."' style='background-color: ".$specdates[$i]["code"]."; margin-top: 4px;'></div></td>";
						$txt .= "</tr>";
						}
					}
				
			$txt .= "</table>";
			}
		$result = array( $txt );
		}

	if( $_GET["op"] == "savemagsettings" ) {
		sql_update( "magazines", $_GET["name"]."='".$_GET["val"]."'", "id='".$_GET["mid"]."'" );
		}
	
	if( $_GET["op"] == "loadmsettings" ) {
		$magazine = sql_aget( "magazines", "id='".$_GET["mid"]."'", "*" );
		$planner_post = sql_aget( "calendar_post", "id='".$_GET["pid"]."'", "*" );
		$txt = "";
		if( $rights["calendar_realdates"] ) {
			if( $_GET["mid"] != "0" && $_GET["mid"] != "" && $planner_post[0]["magazine_id"] != "0" ) {
				$txt = "<table cellspacing='0' cellpadding='0'>";
					$txt .= "<tr>";
						$txt .= "<td>".$lang["calendar"]["type"].":&nbsp;</td>";
						$txt .= "<td>";
							$txt .= "<select mid='".$_GET["mid"]."' name='finishtype' onchange='saveCalMagSettings( this )'>";
								$txt .= "<option ".( $magazine[0]["finishtype"] == "sales" ? "selected" : "" )." value='sales'>".$lang["calendar"]["sales_day"]."</option>";
								$txt .= "<option ".( $magazine[0]["finishtype"] == "delivery" ? "selected" : "" )." value='delivery'>".$lang["calendar"]["delivery_day"]."</option>";
							$txt .= "</select>";				
						$txt .= "</td>";
					$txt .= "</tr>";
		
					$txt .= "<tr>";
						$txt .= "<td>".$lang["calendar"]["offset_days"].":&nbsp;</td>";
						$txt .= "<td>";
							$options = array( "0" => "0", "1" => "1", "2" => "2", "3" => "3" );
							
							$txt .= "<select mid='".$_GET["mid"]."' name='dayshift' onchange='saveCalMagSettings( this )'>";
							foreach( $options as $key=>$value ) {
								$txt .= "<option ".( $magazine[0]["dayshift"] == $key ? "selected" : "" )." value='".$key."'>".$value."</option>";
								}
							$txt .= "</select>";	
						$txt .= "</td>";
					$txt .= "</tr>";
		
					$txt .= "<tr>";
						$txt .= "<td>".$lang["calendar"]["group"].":&nbsp;</td>";
						$txt .= "<td>";
							$txt .= "<input mid='".$_GET["mid"]."' type='text' name='calendarGroup' value='".$magazine[0]["calendarGroup"]."' style='width: 89px;' onchange='saveCalMagSettings( this )'>";
						$txt .= "</td>";
					$txt .= "</tr>";
		
					$txt .= "<tr>";
						$txt .= "<td colspan='2'>
							<div class='magEvents'></div>";
							
						if( $rights["calendar_realdates"] ) {	
							$txt .= "<button onclick='newDefine( \"".$_GET["mid"]."\" )' style='margin-top: 4px;'>".$lang["calendar"]["def_spec_days"]."</button>";
							}
						$txt .= "</td>";
					$txt .= "</tr>";
				$txt .= "</table>";
				}
			else {
				$txt = "<table cellspacing='0' cellpadding='0'>";
					$txt .= "<tr>";
						$txt .= "<td>";
							if( $rights["calendar_realdates"] ) {
								$txt .= "<button onclick='setNotification( \"".$_GET["pid"]."\" )' style='margin-top: 4px;'>Define notifications</button>";
								}
						$txt .= "</td>";
					$txt .= "</tr>";
				$txt .= "</table>";
				}
			}		
		
		$result = $txt;
		}
		
	if( $_GET["op"] == "removeOrder" ) {
		$order = sql_aget( "calendar_post", "id='".$_GET["oid"]."'", "*" );
		sql_delete( "calendar_post", "id='".$_GET["oid"]."'" );
		
		$magazine = sql_aget( "magazines", "id='".$order[0]["magazine_id"]."'", "*" );	
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_SESSION['intra_user'], 'removedfromcalendar', $magazine[0]["publisher_id"], $magazine[0]["name"], $order[0]["code"], '', time(), '' );
		sql_add( 'action_log', $names, $values );

		$counter = sql_aget( "calendar_counters", "publisher_id='".$magazine[0]["publisher_id"]."'", "*" );
		sql_update( "calendar_counters", "counter='".( intval( $counter[0]["counter"] ) + 1 )."'", "id='".$counter[0]["id"]."'" );
		}
	
	if( $_GET["op"] == "savedate" ) {
		if( !empty( $_GET["day"] ) ) {
			$order = sql_aget( "calendar_post", "id='".$_GET["id"]."'", "*" );
			sql_update( "calendar_post", $_GET["datetype"]."='".$_GET["day"]."'", "id='".$_GET["id"]."'" );
				
			$magazine = sql_aget( "magazines", "id='".$order[0]["magazine_id"]."'", "*" );	
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
			$values = array( $_SESSION['intra_user'], 'modifycalendar', $magazine[0]["publisher_id"], $magazine[0]["name"], $order[0]["code"], $order[0][$_GET["datetype"]]." => ".$_GET["day"], time(), '', $_GET["datetype"] );
			sql_add( 'action_log', $names, $values );
					
			$counter = sql_aget( "calendar_counters", "publisher_id='".$magazine[0]["publisher_id"]."'", "*" );
			sql_update( "calendar_counters", "counter='".( intval( $counter[0]["counter"] ) + 1 )."'", "id='".$counter[0]["id"]."'" );
			}
		}
	
	if( $_GET["op"] == "savecolor" ) {
		if( !empty( $_GET["color"] ) ) {
			$check = sql_aget( "magazines", "id='".$_GET["id"]."'", "*" );
			if( !empty( $check[0]["id"] ) ) {
				sql_update( "magazines", "color='".$_GET["color"]."'", "id='".$_GET["id"]."'" );
				}
			else {
				$hex = "#".$_GET["color"];
				list($r, $g, $b) = sscanf($hex, "#%02x%02x%02x");

				sql_update( "calendar_post", "code='rgb(".$r.", ".$g.", ".$b.")'", "id='".$_GET["id"]."'" );
				}
			}
		}

	// Populates calendar_holidays for a year not covered by the hardcoded
	// calendarHoliday() array in engine.php, from the free public
	// holidays API at date.nager.at - lets the Planner's "Add Year"
	// button cover a new year without a developer hand-editing that
	// array and redeploying, which was the whole point of this feature.
	// Restricted to admins (user_groups id 2), same convention menuAjax.php
	// uses elsewhere for developer-adjacent actions.
	if( $_GET["op"] == "addYear" ) {
		$result = array( "ok" => false, "message" => "" );

		if( $user[0][8] != 2 ) {
			$result["message"] = "Not allowed.";
			}
		else {
			$year = intval( $_GET["year"] );
			if( $year < 2000 || $year > 2100 ) {
				$result["message"] = "Invalid year.";
				}
			else {
				$ch = curl_init( "https://date.nager.at/api/v3/publicholidays/".$year."/HU" );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 5 );
				curl_setopt( $ch, CURLOPT_TIMEOUT, 15 );
				$response = curl_exec( $ch );
				$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
				$curlError = curl_error( $ch );
				curl_close( $ch );

				if( $response === false || $httpCode != 200 ) {
					$result["message"] = "Could not reach the holiday service (".( $curlError ? $curlError : $httpCode." status" ).").";
					}
				else {
					$holidays = json_decode( $response, true );
					if( !is_array( $holidays ) ) {
						$result["message"] = "The holiday service returned an unexpected response.";
						}
					else {
						$names = array( "holiday_date", "name" );
						$added = 0;
						foreach( $holidays as $h ) {
							if( empty( $h["date"] ) || empty( $h["localName"] ) ) continue;
							// The API is trusted but is still third-party network
							// content - validate the date shape and escape the
							// free-text name rather than trusting it outright,
							// even though sql_add() elsewhere in this app doesn't.
							if( !preg_match( '/^\d{4}-\d{2}-\d{2}$/', $h["date"] ) ) continue;
							$safeName = mysqli_real_escape_string( $con, $h["localName"] );
							if( sql_add( "calendar_holidays", $names, array( $h["date"], $safeName ) ) ) {
								$added++;
								}
							}

						// Dec 24th and Dec 31st aren't official statutory
						// holidays so the API doesn't list them, but nobody
						// here works those days - add them too so new years
						// look consistent with the existing ones.
						$extraHolidays = array(
							$year."-12-24" => "Szenteste",
							$year."-12-31" => "Szilveszter",
							);
						foreach( $extraHolidays as $extraDate => $extraName ) {
							if( sql_add( "calendar_holidays", $names, array( $extraDate, $extraName ) ) ) {
								$added++;
								}
							}

						$result["ok"] = true;
						$result["message"] = $added." dates added for ".$year.".";
						$result["count"] = $added;
						}
					}
				}
			}
		}
	}
	
else {
	if( $_GET["mode"] == "Internal" ) {
		$txt = "";
		
		$year = intval( $_GET["year"] );
		$napok = array( "", $lang["calendar"]["mon"], $lang["calendar"]["tue"], $lang["calendar"]["wed"], $lang["calendar"]["thu"], $lang["calendar"]["fri"], $lang["calendar"]["sat"], $lang["calendar"]["sun"] );
		$calendar = array();

		$headline .= "<tr>";
		for( $i = 0; $i < 8; $i++ ) {
			$headline .= "<td>".$napok[$d]."</td>";
			$d++;
			}
		$headline .= "</tr>";		

		$txt = "<div id='calendarTableBox' style='position: relative;'><table class='internalTable' id='calendarTable' cellspacing='0', cellpadding='0' style='border-collapse: collapse;'>";
			$txt .= "<thead>".$headline."</thead>";
			
			$txt .= "<tbody>";
				$txt .= calendarWeeksRow( $magazines );
			$txt .= "</tbody>";
			
			$txt .= "<tfoot>".$headline."</tfoot>";
		$txt .= "</table>";
		
		$result = array( $txt );
		}
	else {
		if( $_GET["ptype"] == "H" ) $rights["calendar_realdates"] = true;
		if( $_GET["ptype"] == "V" ) $rights["calendar_realdates"] = false;
		
		$year = intval( $_GET["year"] );
		$honapok = array( "December", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" );
		$napok = array( "", $lang["calendar"]["mon"], $lang["calendar"]["tue"], $lang["calendar"]["wed"], $lang["calendar"]["thu"], $lang["calendar"]["fri"], $lang["calendar"]["sat"], $lang["calendar"]["sun"] );
		$calendar = array();
		
		$magazines = json_decode( $_GET["magazines"] );
		
		for( $h = 0; $h < count( $honapok ); $h++ ) {
			if( $h == 0 ) {
				$napCount = cal_days_in_month( CAL_GREGORIAN, 12, ($year-1) );
				$start = strftime( "%u", strtotime( "1 ".$honapok[$h]." ".($year-1) ) );
				$end = strftime( "%u", strtotime( $napCount." ".$honapok[$h]." ".($year-1) ) );
				}
			else {
				$napCount = cal_days_in_month( CAL_GREGORIAN, $h, $year);
				$start = strftime( "%u", strtotime( "1 ".$honapok[$h]." ".$year ) );
				$end = strftime( "%u", strtotime( $napCount." ".$honapok[$h]." ".$year ) );
				}
			
			$calendar[$h] = array();
			
			if( $start != 1 ) {
				for( $i = 1; $i < $start; $i++ ) {
					$calendar[$h][] = "";
					}
				}
			
			for( $i = 1; $i <= $napCount; $i++ ) {
				$calendar[$h][] = $i;
				}
			}
		
		$max = calendarLongestMonth( $calendar );
		
		$headline = "";
		$d = 1;
		
		$headline .= "<tr>";
		$headline .= "<td>&nbsp;</td>";
		for( $i = 0; $i < $max; $i++ ) {
			if( $d == 8 ) $d = 1;
			
			$headline .= "<td>".$napok[$d]."</td>";
		
			$d++;
			}
		$headline .= "<td>&nbsp;</td>";
		$headline .= "</tr>";

		$txt = "<div id='calendarTableBox' style='position: relative;'><table id='calendarTable' cellspacing='0', cellpadding='0' style='border-collapse: collapse;'>";
			$txt .= "<thead>".$headline."</thead>";
			
			$txt .= "<tbody>";
				$txt .= calendarMonthsRow( $calendar, $year, $magazines, $rights, $user );
			$txt .= "</tbody>";
			
			$txt .= "<tfoot>".$headline."</tfoot>";
		$txt .= "</table>";
		
		$events = sql_aget( "calendar_events", "1 order by start asc", "*" );
		for( $i = 0; $i < count( $events ); $i++ ) {
			$eyear = date( "Y", $events[$i]["start"] );
			if( $eyear == $year ) {
				$mag = sql_aget( "magazines", "id='".$events[$i]["magazine_id"]."'", "*" );
				$mag[0]["color"] = adjustBrightness( $mag[0]["color"], -30 );
				$days = ( $events[$i]["end"] - $events[$i]["start"] ) / 60 / 60 / 24 + 1;
				$boxWidth = 41;
				$boxHeight = 65;
				
				$day = date( "j", $events[$i]["start"] );
				$month = date( "n", $events[$i]["start"] );
		
				$day2 = date( "j", $events[$i]["end"] );
				$month2 = date( "n", $events[$i]["end"] );
				
				if( $month != $month2 ) {
					$days_ =  date( "t", $events[$i]["start"] ) - $day + 1;
					$left = ( intval( array_search( $day , $calendar[$month] ) ) + 1 ) * 41;
					$top = ( $month ) * $boxHeight + 25 ;
					$l = ( intval( array_search( "1" , $calendar[($month+1)] ) ) + 1 ) * 41;
					if( in_array( $events[$i]["magazine_id"], $magazines ) ) {
						$txt .= "<div class='eventBox' style='color: #".$mag[0]["color"]."; width: ".( ( $days - $days_ ) * $boxWidth - 1)."px; left: ".$l."px; top: ".($top+$boxHeight)."px;'>".$events[$i]["name"]."</div>";
						}
					}
					
				else {
					$days_ = $days;
					$left = ( intval( array_search( $day , $calendar[$month] ) ) + 1 ) * 41;
					$top = ( $month ) * $boxHeight + 25 ;
					}
				if( in_array( $events[$i]["magazine_id"], $magazines ) ) {
					$txt .= "<div class='eventBox' style='color: #".$mag[0]["color"]."; width: ".($days_ * $boxWidth - 1)."px; left: ".$left."px; top: ".$top."px;'>".$events[$i]["name"]."</div>";
					}
				}
			}
		
		$txt .= "</div>";
		
		$ver = sql_aget( "calendar_counters", "publisher_id='".$_GET["pub"]."'", "*" );
		/*$txt .= "<div style='padding-top: 15px; text-align: left;'>";
			$txt .= "&nbsp;Version: ";
			$ver = sql_aget( "calendar_counters", "publisher_id='1'", "*" );
			$txt .= ( $rights["calendar_realdates"] ? "H" : "" )."".$ver[0]["counter"];
		$txt .= "</div>";*/
		
		$result = array( $txt, ( $rights["calendar_realdates"] ? "H" : "V" )."".$ver[0]["counter"] );
		}
	}
	
print json_encode( $result );
?>