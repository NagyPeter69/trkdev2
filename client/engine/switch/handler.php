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
// 2026-10-25: since the 2026-10-23 gateway change Switch arrives un-NATed
// as 192.168.1.8 (every callback here was 403ing); 10.10.30.250 stays in
// case the gateway masquerades it again.
if( !in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '10.10.30.250', '192.168.1.8' ), true ) ) {
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

// Switch's archive flow has been seen calling back with no "event" field at
// all - only userName/password/jobCode/issue/result (DM 2610 on 2026-10-02,
// HYA19 on 2026-10-08) - which used to include "-handler.php" and silently
// drop the callback, leaving the job stuck at "archiving". Until the Switch
// side is fixed: an event-less callback for a job that is currently
// "archiving" can only be that archive's result, so route it there.
// jobCode/issue arrive as CODE/ISSUE for Regular jobs (DM/2610) and as
// CODE/CODE for Adhoc ones (HYA19/HYA19) - the same lookup as
// archive_results-handler.php handles both.
if( empty( $_POST["event"] ) && ( $_POST["result"] ?? '' ) != '' && !empty( $_POST["jobCode"] ) && !empty( $_POST["issue"] ) ) {
	$fbMagazine = sql_aget( 'magazines', "code='".mysqli_real_escape_string( $con, $_POST["jobCode"] )."'", 'id' );
	$fbPub = array();
	if( !empty( $fbMagazine[0]['id'] ) ) {
		$fbPub = sql_aget( 'publications', "magazine_id='".$fbMagazine[0]['id']."' AND code='".mysqli_real_escape_string( $con, $_POST["issue"] )."'", 'status' );
		}
	if( ( $fbPub[0]['status'] ?? '' ) == 'archiving' ) {
		$_POST["event"] = 'archive_results';
		error_log( "handler.php: event-less Switch callback for ".$_POST["jobCode"]."/".$_POST["issue"]." routed to archive_results (job is archiving)" );
		}
	}

// Only ever include a real handler file: an empty or unknown event used to
// produce a failed include and nothing else, and the raw value was spliced
// into an include path.
if( preg_match( '/^[a-z_]+$/', $_POST["event"] ?? '' ) && is_file( __DIR__.'/'.$_POST["event"]."-handler.php" ) ) {
	include( __DIR__.'/'.$_POST["event"]."-handler.php" );
	}
else {
	error_log( "CRITICAL: handler.php: no handler for Switch event '".( $_POST["event"] ?? '' )."' (jobCode '".( $_POST["jobCode"] ?? '' )."', issue '".( $_POST["issue"] ?? '' )."') - callback dropped, raw payload in client/engine/switch/logs/" );
	}

$response = '<?xml version="1.0" encoding="UTF-8"?>';
$response .= '<valami>';
	$response .= '<file_0>Sent</file_0>';
$response .= '</valami>';

echo $response;	
	
?>