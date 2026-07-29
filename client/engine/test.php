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
	$acct = sql_get( "accounts", "id='".$_SESSION["intra_user"]."'", "*" );
	if( !empty( $acct[0][0] ) && $acct[0][27] == "0" && !sessionTokenValid( $_SESSION["intra_user"], $_SESSION['session_token'] ?? '' ) ) {
		session_unset();
		session_destroy();
		print json_encode( "logged_out" );
		exit;
		}
	sql_update( "accounts", "last_response='".time()."'", "id='".$_SESSION["intra_user"]."'" );
}

print json_encode( "done" );
?>