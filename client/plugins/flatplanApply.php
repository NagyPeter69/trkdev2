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
// See client/plugins/pubsApply.php's 2026-09-05 fix - none of this
// file's sub== handlers checked authentication before running.
// Same fix: one gate before any sub is dispatched.
if( empty( $user[0][0] ) ) {
	print json_encode( array( array( "Unauthorized" ) ) );
	exit;
	}

if( $_GET["sub"] == "articletypes" ) {
	$error = array();
	
	if( !empty( $_POST["name"] ) ) {
		$names = array( "pub_id", "name", "color", "time" );
		$values = array( $_POST["pid"], $_POST["name"], $_POST["articleColor"], $_POST["articleTime"] );
		sql_add( "flatplan_articletypes", $names, $values );
		}
	else {
		$error[] = "name";
		}
	
	$result = array( $error );
	}

if( $_GET["sub"] == "worker" ) {
	$error = array();
	
	error_log( $_POST["workerID"]." ".$_POST["pid"] );
	
	if( empty( $_POST["workerID"] ) ) {
		sql_update( "flatplan_planner", "workerName='', workerID=''", "(pub_id='".$_POST["pid"]."' AND name='".$_POST["fname"]."')" );
		}
	else {
		$u = sql_aget( "accounts", "id='".$_POST["workerID"]."'", "*" );
		sql_update( "flatplan_planner", "workerName='".$u[0]["full_name"]."', workerID='".$u[0]["id"]."'", "(pub_id='".$_POST["pid"]."' AND name='".$_POST["fname"]."')" );
		}
	 
	$result = array( $error );
	}

print json_encode( $result );
?>