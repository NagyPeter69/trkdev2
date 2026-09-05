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

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers');

header('Content-Type: text/html; charset=utf-8');

include_once('../../../engine/connect.php');
include_once('../../../engine/engine.php');
include_once('../../../engine/xml_handler.php');

ob_flush();
ob_start();
var_dump(headers_list());
var_dump($_POST);
var_dump( $_FILES );

file_put_contents("webhook-dump.txt", ob_get_flush());

// The IP gate above closes the "anyone can forge this" hole, but this line
// was additionally a straight arbitrary-file-write: it saved an uploaded
// file under a name the caller fully controls ($_FILES[0]["name"]) into
// this same web-servable directory - a .php-named upload here would be
// directly executable over HTTP. This debug dump has never needed to keep
// the file itself (only the var_dump above, which already captures
// $_FILES's metadata), so stop persisting it under a caller-chosen name.
if( !empty( $_FILES[0]["tmp_name"] ) ) {
	move_uploaded_file( $_FILES[0]["tmp_name"], "webhook-dump-upload.bin" );
	}
