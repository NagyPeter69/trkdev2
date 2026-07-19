<?php
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