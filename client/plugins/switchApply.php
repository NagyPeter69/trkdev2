<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

// This file never checked authentication at all - sub=='flow' rewrites
// switch_flows (which Switch flow uploads/commands get routed to for
// every job), unauthenticated, before this fix. See
// client/plugins/pubsApply.php's 2026-09-05 fix for the same gate used
// throughout this pass.
$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}
if( empty( $user[0][0] ) ) {
	print json_encode( array( array( "Unauthorized" ) ) );
	exit;
	}

if( $_GET["sub"] == "flow" ) {
	$error = "";

	$data = explode( "_", $_POST["uploadflow"] );
	sql_update( "switch_flows", "flowid='".$data[0]."', objectid='".$data[1]."'", "name='uploads'" );

	$data = explode( "_", $_POST["commandflow"] );
	sql_update( "switch_flows", "flowid='".$data[0]."', objectid='".$data[1]."'", "name='commands'" );
	
	$result = array( $error );
	}

print json_encode( $result );

?>