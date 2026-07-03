<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers');	
	
header('Content-Type: text/html; charset=utf-8');	

include_once('../../../../engine/connect.php');
include_once('../../../../engine/engine.php');
include_once('../../../../engine/xml_handler.php');

$_POST = unvar_dump( file_get_contents( "031_DM1804.txt") );

error_log( "include: ".$_POST["event"]."-handler.php" );
include( $_POST["event"]."-handler.php" );
	
?>