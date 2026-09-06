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
include( "../../../engine/fileClass.php" );
$file = new file;

if( $_POST["payload"] == "finished" ) {
	$file->switchFinishedUpload( $_POST );
	}

if( $_POST["payload"] == "cancelled" ) {
	if( !empty( $_POST["id"] ) ) {
		$file->cancelledID( $_POST["id"] );
		echo "Success";
		}
	else {
		echo "Missing ID";
		}
	}

if( $_POST["payload"] == "getID" ) {
	$id = $file->getNextID();

	$response = '<?xml version="1.0" encoding="UTF-8"?>';
	$response .= '<transferID>'.$id.'</transferID>';
	}
	
?>