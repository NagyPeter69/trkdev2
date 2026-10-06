<?
session_start();
include_once('../engine/connect.php');
include_once('../engine/engine.php');

// 2026-10-06: this file had no authentication at all and built every path
// straight from $_GET['file'] - ?type=one&file=../../engine/connect.php read
// (and then unlinked) any file www-data could touch. Same session gate as
// the 2026-09-05 *Ajax.php fixes (see client/engine/issueManagementAjax.php).
// 'handout' stays reachable without a login on purpose: it only ever serves
// a flatplan_handout row's own file, and logs "Visitor" for anonymous
// downloads (same as book.php's public flipbook link).
$_GET['type'] = (string) ( $_GET['type'] ?? '' );
$fileUser = !empty( $_SESSION["intra_user"] ) ? sql_get( 'accounts', 'id="'.intval( $_SESSION["intra_user"] ).'"', 'id' ) : array();
if( empty( $fileUser[0][0] ) && $_GET['type'] != 'handout' ) {
	http_response_code( 403 );
	exit;
	}

// Every branch below only ever serves a bare file name from its own
// directory - no subdirectories, no dotfiles, only the extensions that
// branch's producer actually writes. Returns false for anything else.
function getFileConfined( $dir, $name, $pattern ) {
	$name = basename( (string) $name );
	if( $name == "" || $name[0] == "." || !preg_match( $pattern, $name ) )
		return false;
	$real = realpath( __DIR__."/".$dir."/".$name );
	if( $real === false || !is_file( $real ) || dirname( $real ) !== realpath( __DIR__."/".$dir ) )
		return false;
	return $real;
	}

function getFileNotFound() {
	http_response_code( 404 );
	exit;
	}

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);

error_log( "------- Flatplan Letöltés log -------" );
error_log( "datum: ".time()." ( ".date( "Y-m-d H:i:s" )." )" );
error_log( "userID: ".( $_SESSION["intra_user"] ?? '' ) );
error_log( "file: ".( $_GET['file'] ?? $_GET['name'] ?? '' ) );
error_log( "tipus: ".$_GET['type'] );

if( $_GET['type'] == 'txt' ) {
	// Only logsApply.php's syslog exports land here - without the name
	// check, ?type=txt&file=pubsApply.php would hand out (and delete) code.
	$filePath = getFileConfined( "plugins", $_GET['file'] ?? '', '/^tracker_syslog_[0-9A-Za-z_-]+\.txt$/' );
	if( $filePath === false ) getFileNotFound();
	header('Content-Type: text/plain');
	$newname = basename( $filePath );
	}

if( $_GET['type'] == 'csv' ) {
	// CSV exports (timeline.php's "Segédlet letöltése") are Colorcom-staff
	// only and always live in TRKPATH/csv - the file is looked up by bare
	// name there rather than read from a path the browser sends.
	$csvUser = !empty( $_SESSION["intra_user"] ) ? sql_get( 'accounts', 'id="'.intval( $_SESSION["intra_user"] ).'"', 'publisher' ) : array();
	$csvFile = TRKPATH."/csv/".basename( (string) ( $_GET['name'] ?? '' ) );
	if( ( $csvUser[0][0] ?? '' ) != "6" || substr( $csvFile, -4 ) != ".csv" || !is_file( $csvFile ) ) {
		http_response_code( 404 );
		exit;
		}
	header('Content-Type: text/csv');
	$newname = basename( $csvFile );
	}

if( $_GET['type'] == 'jpg' ) {
	header('Content-Type: application/zip');
	$temp = explode( "=", basename( (string) ( $_GET['file'] ?? '' ) ) );
	if( !empty( $temp[1] ) ) {
		$type = explode( ".", $temp[1] );
		$newname = $temp[0].".".$type[1];
		}
	else {
		$newname = $temp[0];
		}
	}

if( $_GET['type'] == 'multi' ) {
	header('Content-Type: application/zip');
	$temp = explode( "=", basename( (string) ( $_GET['file'] ?? '' ) ) );
	if( !empty( $temp[1] ) ) {
		$type = explode( ".", $temp[1] );
		$newname = $temp[0].".".$type[1];
		}
	else {
		$newname = $temp[0];
		}
	}

if( $_GET['type'] == 'one' ) {
	header('Content-Type: application/pdf');
	$temp = explode( "=", basename( (string) ( $_GET['file'] ?? '' ) ) );
	if( !empty( $temp[1] ) ) {
		$type = explode( ".", $temp[1] );
		$newname = $temp[0].".".$type[1];
		}
	else {
		$newname = $temp[0];
		}
	}

if( $_GET['type'] == "handout" ) {
	$handout = sql_aget( "flatplan_handout", "id='".intval( $_GET["id"] ?? 0 )."'", "*" );
	$filePath = getFileConfined( "handout", $handout[0]["filename"] ?? '', '/\.pdf$/i' );
	if( $filePath === false ) getFileNotFound();
	header('Content-Type: application/pdf');

	$_GET['file'] = basename( $filePath );
	$newname = $_GET['file'];
	}

// one/multi/jpg (and the catch-all) all read download_ajax.php's output,
// which is always a .pdf or .zip directly in temp/.
if( !in_array( $_GET['type'], array( 'txt', 'csv', 'handout' ) ) ) {
	$filePath = getFileConfined( "temp", $_GET['file'] ?? '', '/\.(pdf|zip)$/i' );
	if( $filePath === false ) getFileNotFound();
	if( !isset( $newname ) ) $newname = basename( $filePath );
	}

error_log( "file uj neve: ".$newname );

header('Content-Disposition: attachment; filename="'.str_replace( '"', '', $newname ).'"');
header("Content-Transfer-Encoding: binary");

if( $_GET['type'] == 'csv' ) {
	header('Content-Length: '.filesize( $csvFile ) );
	readfile( $csvFile );
	}

// Was a separate if-chain, so a csv request also fell through to the final
// else below (temp/ readfile + unlink of the same name).
elseif( $_GET['type'] == 'txt' ) {
	header('Content-Length: '.filesize( $filePath ) );
	error_log( "Fizikai hely: ".$filePath );
	error_log( "Letoltes (elvileg) elindult (itt minden rendben lezajlott)" );
	error_log( "--------------------------------" );

	readfile( $filePath );
	unlink( $filePath );
	}

elseif( $_GET['type'] == 'handout' ) {
	error_log( "Fizikai hely: ".$filePath );
	error_log( "Letoltes (elvileg) elindult (itt minden rendben lezajlott)" );
	error_log( "--------------------------------" );

	$uid = ( !empty( $_SESSION["intra_user"] ) ? $_SESSION["intra_user"] : "Visitor" );
	$issue = str_replace( "_handout.pdf", "", $_GET['file'] );
	$issue = str_replace( "_", " ", $issue );
	$names = array( "userid", "type", "issue", "date" );
	$values = array( $uid, "Handout Download", $issue, time() );
	sql_add( "handout_log", $names, $values );

	readfile( $filePath );
	}

elseif( $_GET['type'] == 'one' ) {
	$chunksize = 5 * (1024 * 1024);
	$size = intval(sprintf("%u", filesize( $filePath )));
    header('Content-Type: application/octet-stream');
    header('Content-Length: '.$size);

	if($size > $chunksize) {
        $handle = fopen( $filePath, 'rb' );

        while (!feof($handle)) {
			print(@fread($handle, $chunksize));
			ob_flush();
			flush();
			}

        fclose($handle);
		}
    else {
		readfile( $filePath );
		}
	unlink( $filePath );
    exit;
	}

else {
	header('Content-Length: '.filesize( $filePath ) );
	error_log( "Fizikai hely: ".$filePath );
	error_log( "Letoltes (elvileg) elindult (itt minden rendben lezajlott)" );
	error_log( "--------------------------------" );

	readfile( $filePath );
	unlink( $filePath );
	}
?>
