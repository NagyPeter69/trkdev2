<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );
include_once( '../../engine/xml_handler.php' );

include_once('../lang/en.php');
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

if( $_GET['op'] == 'addJob' ) {
	$names = array( "publisher", "name", "code", "deadline", "emails", "workflow", "size", "parts", "created" );
	$values = array( $_POST["data"]["client"], $_POST["data"]["name"], $_POST["data"]["code"], $_POST["data"]["deadline"], nl2br( $_POST["data"]["mails"] ), $_POST["data"]["workflow"], $_POST["data"]["size"], $_POST["data"]["parts"], time() );
	
	sql_add( "jobs", $names, $values );
	}

if( $_GET['op'] == 'checkCode' ) {
	$check = sql_aget( "magazines", "code='".$_POST["data"]["code"]."'", "id" );
	if( empty( $check[0]["id"] ) ) {
		$check = sql_aget( "jobs", "code='".$_POST["data"]["code"]."'", "id" );
		if( empty( $check[0]["id"] ) ) {
			$result = "free";
			}
		else {
			$result = "taken";
			}
		}
	else {
		$result = "taken";
		}
	}

if( $_GET['op'] == 'addmail' ) {
	$txt = "";
	$users = sql_get( "ad_hoc_users", "client='".$_GET['pub']."' ORDER BY `name` ASC", "*" );
	
	$txt .= "<table cellspacing='0' cellpadding='0'>";
	for( $i = 0; $i < count( $users ); $i++ ) {
		$txt .= "<tr>";
			$txt .= "<td width='100%' style='padding-right: 20px;'><span onclick='getmail( \"".$users[$i][2]."\" )' style='cursor: pointer;'>".$users[$i][1]."</span></td>";
		$txt .= "</tr>";
		}
	$txt .= "</table>";
	
	$txt .= '<div style="margin-top: 30px;"><div onclick="$(\'#loadUsersMenu\').hide(100)" style="margin-left: 30px;" class="panelButton">Close</div></div>';
		
	$result = $txt;
	}
		
if( $_GET['op'] == 'delJob' ) {
	$job = sql_aget( "jobs", "id='".$_GET['id']."'", "*" );
	$pub = sql_aget( "publishers", "id='".$job[0]["publisher"]."'", "*" );
	
	$users = sql_aget( "accounts", "`showJobs` LIKE '%".$job[0]["id"]."%'", "id, showJobs" );
	for( $i = 0; $i < count( $users ); $i++ ) {
		$temp = explode( ",", $users[$i]["showJobs"] );
		$index = array_search( $job[0]["id"], $temp );
		if( $index !== FALSE ) array_splice($temp, $index, 1);
		$temp = implode( ",", $temp );
		sql_update( "accounts", "showJobs='".$temp."'", "id='".$users[$i]["id"]."'" );
		}
		
	changeJOBXmlDatabase( 'delete', array( "code" => $job[0]['code'], "publisher" => $pub[0]["name"] ), '../xml/job_data.xml' );			
	}

if( $_GET['op'] == 'load_jobs' ) {
	$txt = '';
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );

	$timeline = array();
	switch( $user[0][17] ) {
		case 'en':
			setlocale(LC_ALL,'en_GB');
			break;
		case 'hu':
			setlocale(LC_ALL,'hu_HU');
			break;
		}
	$anothers = explode( ",", $user[0][10] );
	$alter = array();
	foreach( $anothers as $another ) {
		if( intval( $user[0][4] ) != intval( $another ) ) {
			$alter[] = $another;
			}
		}
	$alter = array_unique( $alter );
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher, showJobs' );
	$magID = explode( ",", $user[0][1] );
	$magazines = array();
	for( $i = 0; $i < count( $magID ); $i++ ) {
		$mag = sql_get( 'jobs', 'id="'.$magID[$i].'" ORDER BY `id` ASC', '*' );
		for( $y = 0; $y < count( $mag ); $y++ ) {
			$magazines[] = $mag[$y];
			}
		}		

	$row = 1;
	for( $i = 0; $i < count( $magazines ); $i++ ) {
		$current = "";
		$management = '';
		$pub = sql_get( 'jobs', 'id="'.$magazines[$i][0].'" ORDER BY `id` ASC', '*' );
		$status = $pub[0][5];
		
		switch( $status ) {
			case 2:
				$color = "#FFCC00";
				break;
			case 1:
				$color = "#FFFF00";
				break;
			case 0:
				$color = "#FF0000";
				break;
			case 3:
				$color = "#33CC33";
				break;
			case 4:
			case 5:
				$color = "#3399FF";
				break;
			}

		$current .= "<div ".$onclick." style='cursor: pointer; float:left; width: 110px;'>";
		
		if( $status != 0 )
			$onclick = "onclick='window.location.href=\"?page=timeline&id=".$pub[0][0]."&code=".$cur."\"'";
		else $onclick = '';
			
		$current .= "</div>";
		$current .= "<div id='".$pub[0][0]."_status' style='float:left; margin-left: 5px; width: 98px;'>".$lang["publications"][$status]."</div>";
		$current .= "</div>";
		$timeLeft = strtotime( $publications[0][11] )-time();
		$day = $hour = "";
		if( $timeLeft > 0 ) {				
			$hour = floor( $timeLeft/60/60 );
			if( $hour > 24 ) {
				$hour = floor( $timeLeft/60/60%24 );
				$day = floor( $timeLeft/60/60/24 );
				if( $day == 0 ) { $day = ""; }
				elseif( $day == 1 ) { $day = sprintf( $lang["timeline"]["day"], $day ); }
				else $day = sprintf( $lang["timeline"]["days"], $day );
				}
				
			if( $hour == 0 ) { $hour = ""; }
			elseif( $hour == 1 ) { $hour = sprintf( $lang["timeline"]["hour"], $hour ); }
			else $hour = sprintf( $lang["timeline"]["hours"], $hour );
		
			$current .= "<div class='pubDeadline'>".$day." ".$hour."</div>";			
			}
		
		$mset = "onclick='jobSettingsMenu({ 0: \"mod\", 1:\"".$magazines[$i][0]."\" })'";
		$txt .= "<tr id='".$magazines[$i][0]."'>";
			$txt .= "<td>";					
				if( $i == 0 ) $txt .= "<div row='".$row."' id='line_".$magazines[$i][0]."' class='jline' style='position: relative;'>";
				else $txt .= "<div row='".$row."' id='line_".$magazines[$i][0]."' class='jline' style='".$border." position: relative; height: 30px;'>".$mask;

				$txt .= "<div ".$mset." style='position:absolute; ";
				if( $i == 0 && count( $magazines ) > 1 ) $txt .= "top:0px !important; padding-bottom:4px !important;";
				$txt .= "padding-bottom:1px; cursor: pointer; float:left; height: 30px; width: 12px;";
				if( $color != "" ) {
					$txt .= "background: ".$color." !important;";
					}
				$txt .= "' class='status_color default2'>&nbsp;</div>";
				$txt .="<div style='float:left; margin-left: 17px; padding-left: 5px; text-align: left; line-height: 30px;' class='ad_info'>";
					$txt .= "<div onclick='".$onclick."' style='cursor: pointer; float:left; width: 190px;'>".$magazines[$i][2]."</div>";
					$txt .= $current;		
				
				$txt .= "</div><div style='clear:both;'></div>";
			$txt .= "</td>";
		$txt .= "</tr>";
		$row++;			
		}
		
	$result = $txt;	
	}	
	
print json_encode( $result );
	
?>