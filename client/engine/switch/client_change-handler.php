<?PHP

// This is an inbound Switch webhook, not a browser session - Switch has no
// Tracker login, so the equivalent check here is verifying the request
// actually comes from the known Switch host rather than a session (see
// client/plugins/pubsApply.php's 2026-09-05 fix for the session-based
// version used everywhere a browser session applies). Confirmed live this
// session: before this, ANYONE reaching this URL could forge a Switch
// event (fake page approvals, fake uploads) with no verification at all.
if( ( $_SERVER['REMOTE_ADDR'] ?? '' ) !== '192.168.1.8' ) {
	http_response_code( 403 );
	exit;
	}
error_log( "fájlban vagyok." );	
	
$code = $_POST["jobCode"];
$result = $_POST["result"];		
$desc = $_POST["result_description"];		
$type = $_POST["type"];		

if( $type == "Regular" ) {
	if( $result == "success" ) {
		sql_update( "magazines", "clientChange='2'", "id='".$code."'" );
		}
	else {
		sql_update( "magazines", "clientChange='3', clientChangeResult='".$desc."'", "id='".$code."'" );
		}	
	}
else {
	if( $result == "success" ) {
		sql_update( "publications", "clientChange='2'", "code='".$code."'" );
		}
	else {
		sql_update( "publications", "clientChange='3', clientChangeResult='".$desc."'", "code='".$code."'" );
		}	
	}
?>