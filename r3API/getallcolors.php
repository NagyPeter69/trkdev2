<?php
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
	
	$pantone = preg_split('/[\r\n]+/', $command);
	for( $i = 0; $i < count( $pantone )-1; $i++ ) {
		$temp = explode( " ", $pantone[$i] );
		$colors[] = $temp[ count($temp)-3 ].", ".$temp[ count($temp)-2 ].", ".$temp[ count($temp)-1 ];
			
		$temp = explode( " =", $pantone[$i] );
		$titles[] = $temp[0];
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