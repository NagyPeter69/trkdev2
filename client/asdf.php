<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers');	
	
header('Content-Type: text/html; charset=utf-8');	

include_once('../../../engine/connect.php');
include_once('../../../engine/engine.php');
include_once('../../../engine/xml_handler.php');

error_log( $_POST["userName"] );
error_log( $_POST["password"] );

/*if( $_POST["userName"] != "switch" or $_POST["password"] != "1mn0tr0b0t" ) {
	http_response_code(404);
	exit;
	}*/

//if( $_POST["event"] == "page_pdf" ) {
	ob_flush();
	ob_start();
	error_log( "dumpolas: ");
	var_dump( $_POST );
	var_dump( $_FILES );
	file_put_contents( $_POST["event"]."-".time().".txt", ob_get_flush());
	
	error_log( "Feldolgozás: ".$_POST["fileName"] );
	error_log( "include: ".$_POST["event"]."-handler.php" );
//	}

	
?>