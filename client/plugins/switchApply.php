<?php
session_start();
header('Content-Type: text/html; charset=utf-8');
include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

if( $_GET["sub"] == "flow" ) {
	$error = "";

	$data = explode( "_", $_POST["uploadflow"] );
	sql_update( "switch_flows", "flowid='".$data[0]."', objectid='".$data[1]."'", "name='uploads'" );

	$data = explode( "_", $_POST["commandflow"] );
	sql_update( "switch_flows", "flowid='".$data[0]."', objectid='".$data[1]."'", "name='commands'" );
	
	$result = array( $error );
	}

print json_encode( $result );

?>