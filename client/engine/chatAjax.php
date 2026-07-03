<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once('../../engine/engine.php');
	
	include_once('../lang/en.php');
	
	if( $_GET['op'] == "savePos" ) {
		sql_update( "accounts", "chat_pos='".$_GET["pos"]."'", "id='".$_SESSION["intra_user"]."'" );
		}

	if( $_GET['op'] == "saveSize" ) {
		sql_update( "accounts", "chat_size='".$_GET["size"]."'", "id='".$_SESSION["intra_user"]."'" );
		}
	
print json_encode( $result );
	
?>