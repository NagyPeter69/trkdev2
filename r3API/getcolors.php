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

$colors = array();
$titles = array();

error_log( $_FILES["file"]["name"][0]["file"] );

if( move_uploaded_file( $_FILES["file"]["tmp_name"][0]["file"], SFOLDER."/".$_FILES["file"]["name"][0]["file"] ) ) {
	$from = SFOLDER."/".$_FILES["file"]["name"][0]["file"];
	
	$command = r3run( 'MEASURE', array( 'x' => 596, 'y' => 760, 'd' => 1, 'r' => 600, 'tprofile' => 'ISOcoated_v2_eci.icc' ), $from );
	
	$pantones = array( "Cyan", "Magenta", "Yellow", "Black" );
	$pantone = preg_split('/[\r\n]+/', $command);
	for( $i = 0; $i < count( $pantone )-1; $i++ ) {
		if( strpos_arr( $pantone[$i], $pantones ) === false ) {
			$temp = explode( " ", $pantone[$i] );
			$colors[] = $temp[ count($temp)-3 ].", ".$temp[ count($temp)-2 ].", ".$temp[ count($temp)-1 ];
			
			$temp = explode( " =", $pantone[$i] );
			$titles[] = $temp[0];
			}
		}
		
	$response["titles"] = $titles;
	$response["colors"] = $colors;
	$response["status"] = "success";			
	}
else {
	$response["status"] = "success";
	}

@unlink( $from );
echo json_encode( $response );	
?>