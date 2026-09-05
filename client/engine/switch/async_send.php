<?php

// This is an inbound Switch webhook, not a browser session - Switch has no
// Tracker login, so the equivalent check here is verifying the request
// actually comes from the known Switch host rather than a session (see
// client/plugins/pubsApply.php's 2026-09-05 fix for the session-based
// version used everywhere a browser session applies). Confirmed live this
// session: before this, ANYONE reaching this URL could forge a Switch
// event (fake page approvals, fake uploads) with no verification at all.
if( ( $_SERVER['REMOTE_ADDR'] ?? '' ) !== '192.168.1.8' ) {
	http_response_code( 403 );
	exit;
	}
/*
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers');	
*/	
header('Content-Type: text/html; charset=utf-8');	

include_once('../../../engine/connect.php');
include_once('../../../engine/engine.php');
include_once( TRKPATH."/engine/switchAPI.php" );

error_log( "async start");
$_POST = json_decode( $_POST["data"], true );


$array = array(
	"Code" => $_POST["Code"],
	"User" => $_POST["User"],
	"Mail" => $_POST["Mail"],
	"MailComm" => $_POST["MailComm"],
	"Part" => $_POST["Part"],
	"Type" => $_POST["Type"],
	"Issue" => $_POST["Issue"],
	);
	
$file = array( 
	"name" => $_POST["file_name"],
	"path" => $_POST["file_path"],
	);

error_log( print_r($array, TRUE) );
	
$response = SwitchASend( $array, $file );

error_log( "async kesz" );
?>