<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );
require_once( "/var/www/html/engine/r3client.php" );

define( "SFOLDER", "/var/www/html/r3API/source");
define( "RFOLDER", "/var/www/html/r3API/rendered");
$terminal = "/var/www/html/r3API";

$file = SFOLDER."/".time()."-getpdfbox.pdf";
file_put_contents( $file, $_POST["pdf"] );

$command = r3run( 'GETDATA', array(), $file );

$response["data"] = $command;
$response["status"] = "success";	

@unlink( $from );	
echo json_encode( $response );	
?>