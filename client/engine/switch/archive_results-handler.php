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
error_log( "fájlban vagyok." );	
	
$status = $_POST["result"];
$publisher = $_POST["client"];
$code = $_POST["jobCode"];
$name = $_POST["description"];
$issue = $_POST["issue"];

$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );

// Looked up by magazine + issue code only, not by the "client" name Switch
// echoes back: magazine codes are unique, and Adhoc jobs carry
// publisher_id="0" (client on publications.owner), so a publisher-name
// match never found them - and when Switch sends "client" empty there's
// nothing to match on at all.
$pub = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'" AND code="'.$issue.'"', '*' );

if( $status == "success" ) {
	if( $pub[0][0] != "" ) {
		sql_update( 'publications', 'status="archived"', 'id="'.$pub[0][0].'"' );

		// Switch says the upload succeeded, so the package should already be
		// sitting in its landing directory by now (step 3 of the archiving
		// workflow completes before step 4's success callback). If it isn't,
		// that's exactly the kind of silent-drift bug the PMD ownership
		// incident taught this codebase to log loudly instead of letting
		// slide - status would say "archived" while Download View has
		// nothing to show.
		if( findArchivePath( $magazine[0][3], $issue ) === null ) {
			error_log( "CRITICAL: archive_results-handler received success for ".$magazine[0][3]."/".$issue." but no matching folder was found under ".ARCHIVE_PATH."." );
			}

		// Adhoc snapshots are named after the code alone (same rule as
		// archiveIssue in issueManagementAjax.php).
		if( $magazine[0][10] == "Adhoc" ) {
			$result = changeIssueStatus( $issue.".xml", "archived", $pub[0][0] );
			}
		else {
			$result = changeIssueStatus( $magazine[0][3]."_".$issue.".xml", "archived", $pub[0][0] );
			}
		
		// Logged whenever the status flips to archived, not only when the
		// per-issue snapshot update succeeds - this entry is the archive
		// completion time shown on the job's info page (timeline.php), and
		// the status above has already changed either way.
		if( !$result ) {
			error_log( "archive_results-handler: changeIssueStatus failed for ".$magazine[0][3]."/".$issue." - archive still logged" );
			}
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( '0', 'archiveIssue', $pub[0][1], $pub[0][2], $pub[0][10], '', time(), '' );
		sql_add( 'action_log', $names, $values );
		}	
	}
	
else {
	sql_update( "publications", "status='archive_failed'", "id='".$pub[0][0]."'" );
	}
		
?>