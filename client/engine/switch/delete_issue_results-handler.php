<?PHP

// This is an inbound Switch webhook, not a browser session - Switch has no
// Tracker login, so the equivalent check here is verifying the request
// actually comes from the known Switch host rather than a session (see
// client/plugins/pubsApply.php's 2026-09-05 fix for the session-based
// version used everywhere a browser session applies). Confirmed live this
// session: before this, ANYONE reaching this URL could forge a Switch
// event (fake page approvals, fake uploads) with no verification at all.
// 2026-09-05 trk.colorcom.hu cutover: the gateway at 10.10.30.250 now NATs
// all inbound traffic (Switch's included) to its own address before it
// reaches this box - Switch's real 192.168.1.8 is no longer visible here,
// and there's no X-Forwarded-For to recover it (confirmed by direct test:
// a curl from the Switch host and Switch's own callback both arrived as
// 10.10.30.250, same as ordinary browser traffic). Checking for the
// gateway's address is accepted as a deliberately weak stand-in for now -
// it does NOT actually distinguish Switch from any other request that
// reaches this server. Revisit once the network side stops masquerading
// Switch's traffic or adds a forwarding header.
if( ( $_SERVER['REMOTE_ADDR'] ?? '' ) !== '10.10.30.250' ) {
	http_response_code( 403 );
	exit;
	}
	
$status = $_POST["result"];
$code = $_POST["jobCode"];
$issue = $_POST["issue"];
$user = "0";

if( $status == "success" ) {
	$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
	$p_id = $issue = sql_get( "publications", "magazine_id='".$magazine[0]["id"]."' AND code='".$issue."'", "*" );
	$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
	$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
	
	if( $issue[0][0] != "" ) {
		cleanupPublicationRemnants( $issue[0][0], $magazine[0][3], $issue[0][10] );

		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $user, 'deleteIssue', $p_id[0][1], $magazine[0][0],  $p_id[0][10], '', time(), '' );
		sql_add( 'action_log', $names, $values );

		sql_delete( 'publications', 'id="'.$p_id[0][0].'"' );
		removeTempUsers( $p_id[0][0] );
		}
	}
	
else {
	$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
	$pub = sql_aget( "publications", "magazine_id='".$magazine[0]["id"]."' AND code='".$issue."'", "id" );
	if( $pub[0]["id"] != "") {
		sql_update( "publications", "removing='0'", "id='".$pub[0]["id"]."'" );
		}	
	}
?>