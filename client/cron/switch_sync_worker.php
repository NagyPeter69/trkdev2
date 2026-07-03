<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );
include_once( '../../engine/xml_handler.php' );
include_once( '../engine/switchAPI.php' );

// Retries jobs that a user-facing request couldn't deliver to Switch
// synchronously (see SendPmdXmlToSwitch / XMLUpload2 in xml_handler.php).
// Runs from cron every minute, same as the other client/cron/*.php jobs.
// Backoff: 1m, 5m, 15m, 1h, then hourly, giving up (status=failed) after
// MAX_ATTEMPTS so a permanently-broken job doesn't retry forever.

define( 'MAX_ATTEMPTS', 10 );
$backoff_seconds = array( 60, 300, 900, 3600 );

$jobs = sql_aget(
	'switch_sync_queue',
	"status='pending' AND (next_attempt_at IS NULL OR next_attempt_at <= NOW()) AND attempts < ".MAX_ATTEMPTS." ORDER BY id ASC LIMIT 20",
	"*"
	);

for( $i = 0; $i < count( $jobs ); $i++ ) {
	$job = $jobs[$i];
	$payload = json_decode( $job["payload"], true );
	$ok = false;
	$error = "";

	switch( $job["job_type"] ) {
		case 'pmd_xml':
			if( !empty( $payload["realFile"] ) && !empty( $payload["switchLabel"] ) ) {
				// Background context - no user waiting, so a much more
				// generous timeout than the fast path is fine here.
				$ok = SendPmdXmlToSwitch( $payload["realFile"], $payload["switchLabel"], 5, 30 );
				if( !$ok ) {
					$error = "Switch delivery failed or timed out";
					}
				}
			else {
				$error = "Malformed payload";
				}
			break;

		default:
			$error = "Unknown job_type: ".$job["job_type"];
			break;
		}

	if( $ok ) {
		sql_update( 'switch_sync_queue', "status='success', last_attempt_at=NOW()", "id='".$job["id"]."'" );
		}
	else {
		$attempts = $job["attempts"] + 1;
		$backoffIndex = min( $attempts - 1, count( $backoff_seconds ) - 1 );
		$nextAttempt = date( 'Y-m-d H:i:s', time() + $backoff_seconds[ $backoffIndex ] );
		$status = ( $attempts >= MAX_ATTEMPTS ) ? 'failed' : 'pending';

		global $con;
		sql_update(
			'switch_sync_queue',
			"status='".$status."', attempts='".$attempts."', last_error='".mysqli_real_escape_string( $con, $error )."', last_attempt_at=NOW(), next_attempt_at='".$nextAttempt."'",
			"id='".$job["id"]."'"
			);
		}
	}
?>
