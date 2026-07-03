<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );
include_once('../lang/en.php');


if( $_GET["op"] == "livelogissues" ) {
	$result = array();
	$issues = sql_aget( "action_log", "magazine='".$_GET["mag"]."' GROUP BY `issue`", "issue" );
	for( $i = 0; $i < count( $issues ); $i++ ) {
		$result[] = $issues[$i]["issue"];
		}
	}

print json_encode( $result );

?>