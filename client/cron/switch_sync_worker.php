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
// Runs from cron every minute, same as the other client/cron/*.php jobs -
// and also gets kicked directly by download_ajax.php's bulk approve
// handler right after it queues jobs, so a delivery doesn't have to wait
// out the next cron tick.
//
// Because of that second trigger, two instances of this script can now
// legitimately start within moments of each other (the kick landing in
// the same second as a scheduled cron tick isn't rare - a bulk approve
// just has to finish queuing near a minute boundary). Nothing below
// claims a row before working on it (SELECT then, later, UPDATE), so two
// concurrent instances would both select the same pending rows and both
// call SwitchSend_Rename() for the same file at the same time. Confirmed
// live 2026-08-11: a 79-page bulk approve's kick coincided with the
// standing cron tick, both instances raced to upload the same page, and
// whichever of the two uploads lost the race (hit SwitchSend_Rename()'s
// curl timeout mid-transfer) left a truncated file that Switch had
// already picked up before the winning upload's clean copy replaced it -
// the page came out corrupt in Switch even though our own retry
// bookkeeping showed a clean eventual success. A single flock below
// makes any second instance exit immediately instead of racing.
// The cron trigger and the download_ajax.php kick run as different OS
// users (this script's crontab entry is root's; the kick runs inside
// php-fpm as www-data), so whichever of them creates this lock file
// first would otherwise own it at the default 644 and lock the other
// user out permanently (open-for-write would just fail for them every
// time, silently defeating the lock for one side). Force it world-
// writable right after creating/opening it so both users can always
// acquire it.
$lockPath = __DIR__.'/switch_sync_worker.lock';
if( !is_file( $lockPath ) ) {
	touch( $lockPath );
	chmod( $lockPath, 0666 );
	}
$lockHandle = fopen( $lockPath, 'c' );
if( !$lockHandle || !flock( $lockHandle, LOCK_EX | LOCK_NB ) ) {
	exit;
	}

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

		case 'switch_send_rename':
			// Queued by download_ajax.php's "accept" (page approve) handler
			// - see the comment there. Pages can run tens of MB (a bulk
			// approve of 80+ pages here has run ~1GB total); the 5s/15s
			// defaults SwitchSend_Rename() otherwise uses are sized for an
			// interactive fast-fail, not a real transfer of that size, and a
			// timeout mid-transfer isn't a safe no-op - Switch can pick up
			// whatever truncated bytes it already received as if it were
			// the finished file (confirmed live 2026-08-11: exactly this
			// truncated a page and it came out corrupt in Switch even
			// though our own retry bookkeeping showed an eventual clean
			// success). Nobody's waiting on this request, so give it room.
			if( !empty( $payload["datas"] ) && !empty( $payload["file"] ) && !empty( $payload["newname"] ) ) {
				$response = SwitchSend_Rename( $payload["datas"], $payload["file"], $payload["newname"], 10, 90 );
				// SwitchSend_Rename() returns array($response["status"], ...)
				// on a completed call, or array("blocked", ...) if the DEV
				// TestCo-only gate refused it outright - either way that's
				// not something a retry will ever fix, so only a genuine
				// connection/timeout failure (null status - curl_exec()
				// returned nothing decodable) is treated as retryable here.
				if( $response[0] === null ) {
					$error = "Switch delivery failed or timed out";
					}
				else {
					$ok = true;
					}
				}
			else {
				$error = "Malformed payload";
				}
			break;

		case 'upload_ad':
			// Queued by ajax.php's push_ad handler - see the comment there.
			// SwitchSend_TESZT() itself still carries its own fixed 5s/15s
			// curl timeouts; that's fine here since nobody's waiting on
			// this request.
			$response = SwitchSend_TESZT( $payload );
			// Same convention as switch_send_rename: only a genuine
			// connection/timeout failure (null status) is retryable -
			// "blocked" (DEV TestCo-only gate) or any other real response
			// from Switch is a completed attempt either way.
			if( $response[0] === null ) {
				$error = "Switch delivery failed or timed out";
				}
			else {
				$ok = true;
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
