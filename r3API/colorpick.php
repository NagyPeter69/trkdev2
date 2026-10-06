<?php
// 2026-10-06: these r3API HTTP endpoints are a leftover - Tracker itself
// renders through r3run() (engine/r3client.php), nothing calls these over
// HTTP - and they accepted uploads/paths from anyone (api.php kept the
// uploaded file name inside a web-reachable folder: remote code execution).
// Local/CLI use only.
if( PHP_SAPI !== 'cli' && !in_array( $_SERVER['REMOTE_ADDR'] ?? '', array( '127.0.0.1', '::1' ), true ) ) {
	http_response_code( 403 );
	exit;
	}
?><?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );
require_once( "/var/www/html/engine/r3client.php" );

define( "SFOLDER", "/var/www/html/r3API/source");
define( "RFOLDER", "/var/www/html/r3API/rendered");
$terminal = "/var/www/html/r3API";

$file = SFOLDER."/".time()."-cp.pdf";
file_put_contents( $file, $_POST["pdf"] );
$from = $file;

$command = r3run( 'MEASURE', array( 'x' => $_POST["x"], 'y' => $_POST["y"], 'tprofile' => 'ISOcoated_v2_eci.icc' ), $from );

$response["data"] = $command;
$response["status"] = "success";

@unlink( $from );
	
echo json_encode( $response );	
?>