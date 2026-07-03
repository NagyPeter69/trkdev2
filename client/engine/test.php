<?
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Method, Access-Control-Request-Headers');	
	
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');

if( $_SESSION["intra_user"] != "" ) {
 sql_update( "accounts", "last_response='".time()."'", "id='".$_SESSION["intra_user"]."'" );
}

print json_encode( "done" );
?>