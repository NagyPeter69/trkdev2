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
$mag = sql_aget( "magazines", "code='".$_POST["jobCode"]."'", "*" );
$pub = sql_aget( "publications", "magazine_id='".$mag[0]["id"]."' AND code='".$_POST["issue"]."'", "*" );
$pack = sql_aget( "assets", "pub_id='".$pub[0]["id"]."' AND name='".$_POST["description"].".indd'", "*" );
	
$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
$xpath = $xml->xpath('/Publications');
		
foreach($xpath as $temp) {
	for( $i = 0; $i < count( $temp->Item ); $i++ ) {
		if( $temp->Item[$i]->Code == $_POST['jobCode'] )
			break;
		}
	}

$mails = gatedMailRecipients( $mag[0]["id"], $mag[0]["type"], (string) $xml->Item[$i]->Mails );

for( $i = 0; $i < count( $mails ); $i++ ) {
	$hash = md5( "adhocuserdownload-".time()."-".$mails[$i] );
	$user = sql_aget( "accounts", "email='".$mails[$i]."' AND showMagazines like '%".$mag[0]["id"]."%'", "*" );
	
	error_log( "Sendmail, user: ".$user[0]["id"] );
	if( !empty( $user[0]["id"] ) ) {
		$names = array( "user_id", "hash", "magazine_id", "email", "time", "redirecto" );
		$values = array( $user[0]["id"], $hash, $pub[0]["magazine_id"], $mails[$i], time(), "page=assets" );
		sql_add( "adhoc_hotlinks", $names, $values );		
		
		$to = $mails[$i]."|".$mails[$i];
		$link = "https://".URL."/index.php?hash=".$hash;
		$subject = "".$mag[0]["name"]." - Colorcom Tracker feltöltés";
		$body = "Dear User,<br>
		<br>
		new files has been uploaded to the job ".$mag[0]["name"].". Please see them by clicking the link below:<br>
		<br>
		<a href='".$link."'>".$link."</a><br>
		<br>
		Kind regards,<br>
		Colorcom Media";

		produkcioSendmail( $subject, $body, $to );
		error_log( "Mail sent: ".$mails[$i] );
		}
	}
?>