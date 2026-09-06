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
if( ( $_SERVER['REMOTE_ADDR'] ?? '' ) !== '10.10.30.250' ) {
	http_response_code( 403 );
	exit;
	}
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers');	
	
header('Content-Type: text/html; charset=utf-8');	

include_once('../../../engine/connect.php');
include_once('../../../engine/engine.php');
include_once('../../../engine/xml_handler.php');
include_once('../../../engine/xml_handler.php');
include_once( "../dynaAPI.php" );
include_once( "../switchAPI.php" );

/*
if( $_POST["userName"] != "switch" or $_POST["password"] != "1mn0tr0b0t" ) {
	http_response_code(404);
	exit;
	}
*/
//if( $_POST["event"] == "page_pdf" ) {
	ob_flush();
	ob_start();
	var_dump( $_GET );
	var_dump( $_POST );
	var_dump( $_FILES );
	file_put_contents( "logs/".$_POST["fileName"]."_".$_POST["event"]."-".time().".txt", ob_get_flush());
	
	error_log( "Feldolgozás: ".$_POST["fileName"] );
	error_log( "include: ".$_POST["event"]."-handler.php" );
//	}

include( $_POST["event"]."-handler.php" );

$response = '<?xml version="1.0" encoding="UTF-8"?>';
$response .= '<valami>';
	$response .= '<file_0>Sent</file_0>';
$response .= '</valami>';

echo $response;	
	
?>