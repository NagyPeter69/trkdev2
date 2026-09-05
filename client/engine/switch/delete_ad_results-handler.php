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
// Switch calls back here (via handler.php's event dispatcher) once it has
// confirmed an ad's removal/rename on the file server, mirroring the same
// mop-up-on-confirmation pattern as delete_issue_results-handler.php and
// delete_publication_results-handler.php.
//
// NOTE: this file was previously an empty stub - a no-op - so Switch-driven
// ad deletion has never actually cleaned anything up. Unlike the issue/
// publication handlers above (which had a working prior implementation to
// mirror), there is no earlier version of this file to confirm the exact
// POST field names Switch sends back. The fields below are inferred from
// the *outgoing* delete_ad event in client/advertisement.php (which sends
// jobCode/issue/description=ad name/remark=ad size) on the assumption
// Switch echoes the same fields back. Verify this against Switch's actual
// flow configuration before relying on it - if the field names are wrong,
// this will simply do nothing (same as before), not fail loudly.
$result = $_POST["result"];
$code = $_POST["jobCode"];
$issue = $_POST["issue"];
$adName = $_POST["description"];
$adSize = str_replace( '_', '/', $_POST["remark"] );

if( $result == "success" ) {
	$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
	$pub = sql_aget( "publications", "magazine_id='".$magazine[0]["id"]."' AND code='".$issue."'", "*" );

	if( !empty( $pub[0]["id"] ) ) {
		$ad = sql_aget( "ads", "pub_id='".$pub[0]["id"]."' AND name='".$adName."' AND size='".$adSize."'", "*" );

		if( !empty( $ad[0]["id"] ) ) {
			// Same file-matching logic as the immediate/synchronous delete
			// path in client/advertisement.php, so both routes to deleting
			// an ad behave identically.
			switch( $ad[0]["size"] ) {
				case '2/1':
					$type = 'D';
					break;
				case '1/1':
					$type = 'F';
					break;
				default:
					$type = 'P';
					break;
				}

			$file_name = strtoupper( $ad[0]["name"] ).'_'.$magazine[0]["code"].'_'.$pub[0]["code"].'_'.$type;
			$dir = TRKPATH.'/advertisements';
			$dirFiles = load_dir_files( $dir, $file_name );
			for( $y = 0; $y < count( $dirFiles ); $y++ ) {
				$secu = explode( '_', $dirFiles[$y] );
				if( strtoupper( $ad[0]["name"] ) == strtoupper( $secu[0] ) && strstr( $dirFiles[$y], $file_name ) ) {
					@unlink( $dir.'/'.$dirFiles[$y] );
					}
				}

			sql_delete( 'partial_ads', "ads_id='".$ad[0]["id"]."'" );
			sql_delete( 'ads', "id='".$ad[0]["id"]."'" );
			}
		}
	}
?>
