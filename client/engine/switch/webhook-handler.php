<?php
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

move_uploaded_file( $_FILES[0]["tmp_name"], $_FILES[0]["name"] )
	
?>