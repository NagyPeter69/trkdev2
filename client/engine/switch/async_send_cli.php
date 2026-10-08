<?php
// Background sender for package/asset uploads to Switch. Spawned by
// client/engine/fileupload_ajax.php once an upload completes:
//
//   php async_send_cli.php <base64(json job)> > /dev/null 2>&1 &
//
// Replaces the old fire-and-forget HTTP self-call to async_send.php, which
// ran inside php-fpm and so could never outlive the www pool's 300s
// request_terminate_timeout - not enough for a 20-30GB ZIP whatever curl
// itself was told. The CLI has no execution time limit, so SwitchASend()'s
// size-based timeout (SwitchUploadTimeout()) is the only clock here.
// It also removes two holes the HTTP endpoint had: the job was passed
// through an unescaped shell command (a ' in an uploaded file name broke
// out of it), and async_send.php's IP allowlist included the gateway's
// NAT address, i.e. any outside caller could have it send any file on
// this server to Switch.
if( PHP_SAPI !== 'cli' ) {
	http_response_code( 403 );
	exit;
	}

set_include_path( __DIR__ );
chdir( __DIR__ );

// Spawned with stdout/stderr thrown away, so log somewhere findable -
// one place for every send's outcome instead of the nginx error log.
ini_set( 'log_errors', '1' );
ini_set( 'error_log', '/var/log/trk-switch-send.log' );

include_once( '../../../engine/connect.php' );
include_once( '../../../engine/engine.php' );
include_once( TRKPATH."/engine/switchAPI.php" );

$job = json_decode( base64_decode( $argv[1] ?? '', true ) ?: '', true );
if( !is_array( $job ) || empty( $job["file_name"] ) || empty( $job["file_path"] ) ) {
	error_log( "async_send_cli: malformed job argument" );
	exit( 1 );
	}

// Same file-name sanity as anywhere a client-supplied name is joined onto
// a path: the name must stay inside the upload directory it came with.
if( basename( $job["file_name"] ) !== $job["file_name"] || strpos( $job["file_path"], '..' ) !== false ) {
	error_log( "async_send_cli: rejected suspicious path ".$job["file_path"]."/".$job["file_name"] );
	exit( 1 );
	}

$array = array(
	"Code" => $job["Code"] ?? '',
	"User" => $job["User"] ?? '',
	"Mail" => $job["Mail"] ?? '',
	"MailComm" => $job["MailComm"] ?? '',
	"Part" => $job["Part"] ?? '',
	"Type" => $job["Type"] ?? '',
	"Issue" => $job["Issue"] ?? '',
	);

$file = array(
	"name" => $job["file_name"],
	"path" => $job["file_path"],
	);

error_log( "async_send_cli start: ".print_r( $array, true ) );

// Nobody is waiting on this process, so ride out a short Switch hiccup
// instead of dropping the upload: 3 more tries over ~20 minutes. A
// truncated multipart POST never becomes a Switch job, so a retry after a
// failed transfer can't duplicate one.
$retryDelays = array( 60, 300, 900 );
for( $attempt = 0; ; $attempt++ ) {
	$response = SwitchASend( $array, $file );
	if( $response[0] === true || $response[0] === "blocked" ) {
		exit( 0 );
		}
	if( $attempt >= count( $retryDelays ) ) {
		break;
		}
	error_log( "async_send_cli: attempt ".( $attempt + 1 )." failed for ".$job["file_name"]." (".$array["Code"]."), retrying in ".$retryDelays[$attempt]."s" );
	sleep( $retryDelays[$attempt] );
	}

error_log( "async_send_cli: GAVE UP on ".$job["file_path"]."/".$job["file_name"]." (".$array["Code"].") after ".( $attempt + 1 )." attempts - deliver manually" );
exit( 1 );
?>
