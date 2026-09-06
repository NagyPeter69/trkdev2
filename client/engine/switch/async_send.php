<?php

// This is an inbound Switch webhook, not a browser session - Switch has no
// Tracker login, so the equivalent check here is verifying the request
// actually comes from the known Switch host rather than a session (see
// client/plugins/pubsApply.php's 2026-09-05 fix for the session-based
// version used everywhere a browser session applies). Confirmed live this
// session: before this, ANYONE reaching this URL could forge a Switch
// event (fake page approvals, fake uploads) with no verification at all.
// 2026-09-05 trk.colorcom.hu cutover: the gateway at 10.10.30.250 now NATs
// all inbound traffic (Switch's included) to its own address before it
// reaches this box - Switch's real 192.168.1.8 is no longer visible here,
// and there's no X-Forwarded-For to recover it (confirmed by direct test:
// a curl from the Switch host and Switch's own callback both arrived as
// 10.10.30.250, same as ordinary browser traffic). Checking for the
// gateway's address is accepted as a deliberately weak stand-in for now -
// it does NOT actually distinguish Switch from any other request that
// reaches this server. Revisit once the network side stops masquerading
// Switch's traffic or adds a forwarding header.
// 127.0.0.1 added 2026-09-06: client/engine/fileupload_ajax.php's package-
// upload completion fires a same-box self-call here (systemCurl(), fire-
// and-forget, not a real external request) to trigger the actual Switch
// send without blocking the upload response on it. That self-call now
// targets 127.0.0.1 directly (see its own comment) rather than the public
// hostname, so it always arrives as this exact address regardless of DNS/
// /etc/hosts - unlike the gateway-NAT'd address below, a real external
// caller can never spoof 127.0.0.1 as their source over an actual TCP
// connection, so this doesn't weaken the check against external forgery.
if( !in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '10.10.30.250', '127.0.0.1' ), true ) ) {
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