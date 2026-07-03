<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );

define( "SFOLDER", "/var/www/html/r3API/source");
define( "RFOLDER", "/var/www/html/r3API/rendered");
$terminal = "/var/www/html/r3API";

$file = SFOLDER."/".time()."-cp.pdf";
file_put_contents( $file, $_POST["pdf"] );
$from = $file;

$command = './r3 -mode:MEASURE -x:'.$_POST["x"].' -y:'.$_POST["y"].' -tprofile:ISOcoated_v2_eci.icc '.$from.' 2>&1';
$command = shell_exec('
		cd '.$terminal.'/r3;
		'.$command.';
		');

$response["data"] = $command;
$response["status"] = "success";

@unlink( $from );
	
echo json_encode( $response );	
?>