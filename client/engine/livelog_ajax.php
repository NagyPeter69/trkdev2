<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );
include_once('../lang/en.php');

$user = sql_get( "accounts", "id='".$_SESSION['intra_user']."'", "*" );

parse_str($_POST['action_log'], $action_log );
if( $_GET["pub"] == "all" ) {
	$magazines = explode( ",", $user[0][21] );
	}
else {
	$magazines = array( $_GET["pub"] );
	}

$issue = "";
if( $_GET["issue"] != "all" ) {
	$issue = " AND issue='".$_GET["issue"]."'";
	}

$command = $command2 = array();
for( $i = 0; $i < count( $action_log["action_log"] ); $i++ ) {
	$command[] = "`action`='".$action_log["action_log"][$i]."'";
	}

for( $i = 0; $i < count( $magazines ); $i++ ) {
	$command2[] = "`magazine`='".$magazines[$i]."'";
	$mag = sql_aget( "magazines", "id='".$magazines[$i]."'", "*" );
	$command2[] = "`magazine`='".$mag[0]["name"]."'";
	}

$command = implode( " OR ", $command );
$command2 = implode( " OR ", $command2 );

$command = "(".$command.") AND (".$command2.")";

$test_log = sql_aget( "action_log", $command, "*" );
$log = sql_aget( "action_log", $command.$issue." ORDER BY date DESC LIMIT ".( $_GET["start"] * 50).", 50", "*" );
//error_log( $command );
//$logtxt = "<table id='livelog_table' width='100%' cellspacing='0' cellpadding='0'>";
$logtxt = "";
for( $i = 0; $i < count( $log ); $i++ ) {
	$logtxt .= "<tr>";
		$logtxt .= "<td align='left' width='14%' style='padding-left: 5px;'>".date( "Y-m-d H:i", $log[$i]["date"] )."</td>";
		$logtxt .= "<td align='left' width='15%'>".$lang["liveLog"][$log[$i]["action"]]."</td>";
		
		if( $log[$i]["magazine"] != "" ) {
			if( is_numeric( $log[$i]["magazine"] ) ) {
				$mag = sql_aget( "magazines", "id='".$log[$i]["magazine"]."'", "name" );
				$logtxt .= "<td align='left' width='16%'>".$mag[0]["name"]." ".$log[$i]["issue"]."</td>";
				}
			else {
				$logtxt .= "<td align='left' width='16%'>".$log[$i]["magazine"]." ".$log[$i]["issue"]."</td>";
				}
			}
		else {
			$logtxt .= "<td align='left' width='16%'>Unknown</td>";
			}
		
		if( $log[$i]["user"] != "" ) {
			if( is_numeric( $log[$i]["user"] ) ) {
				$user = sql_aget( "accounts", "id='".$log[$i]["user"]."'", "name, full_name" );
				$logtxt .= "<td align='left' width='14%'>".( $user[0]["full_name"] != "" ? $user[0]["full_name"]: $user[0]["name"] )."</td>";
				}
			else {
				$logtxt .= "<td align='left' width='14%'>".$log[$i]["user"]."</td>";
				}
			}
		else {
			$logtxt .= "<td align='left' width='14%'>&nbsp;</td>";
			}
		$logtxt .= "<td align='left' width='27%'>".$log[$i]["target"]."</td>";
		$logtxt .= "<td align='left' width='7%'>".$log[$i]["status"]."</td>";
		$logtxt .= "<td align='left' width='7%'>".$log[$i]["info"]."</td>";
		
	$logtxt .= "</tr>";
	}
//$logtxt .= "</table>";

$result = $logtxt;
print json_encode( array( $result, count( $test_log ) ) );

?>