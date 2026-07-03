<?
session_start();
include_once('../engine/connect.php');
include_once('../engine/engine.php');

header("Pragma: public");
header("Expires: 0"); 
header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
header("Cache-Control: private",false);

error_log( "------- Flatplan Letöltés log -------" );
error_log( "datum: ".time()." ( ".date( "Y-m-d H:i:s" )." )" );
error_log( "userID: ".$_SESSION["intra_user"] );
error_log( "file: ".$_GET['file'] );
error_log( "tipus: ".$_GET['type'] );

if( $_GET['type'] == 'txt' ) {
	header('Content-Type: text/plain');
	$newname = $_GET['file'];
	}

if( $_GET['type'] == 'csv' ) {
	header('Content-Type: text/csv');
	$newname = $_GET['name'];
	}
	
if( $_GET['type'] == 'jpg' ) {
	header('Content-Type: application/zip');
	$temp = explode( "=", $_GET['file'] );
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
	$temp = explode( "=", $_GET['file'] );
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
	$temp = explode( "=", $_GET['file'] );
	if( !empty( $temp[1] ) ) {
		$type = explode( ".", $temp[1] );
		$newname = $temp[0].".".$type[1];
		}
	else {
		$newname = $temp[0];
		}
	}
	
if( $_GET['type'] == "handout" ) {
	$handout = sql_aget( "flatplan_handout", "id='".$_GET["id"]."'", "*" );
	header('Content-Type: application/pdf');
	
	$_GET['file'] = $handout[0]["filename"];
	$newname = $_GET['file'];
	}	
	
error_log( "file uj neve: ".$newname );
	
header('Content-Disposition: attachment; filename="'.$newname.'"');
header("Content-Transfer-Encoding: binary");

if( $_GET['type'] == 'csv' ) {
	
	header('Content-Length: '.filesize( $_GET['file']) );
	error_log( "Fizikai hely: ".$_GET['file'] );
	error_log( "Letoltes (elvileg) elindult (itt minden rendben lezajlott)" );
	error_log( "--------------------------------" );
	
	readfile( $_GET['file']);
	}

if( $_GET['type'] == 'txt' ) {
	header('Content-Length: '.filesize( "plugins/".$_GET['file']) );
	error_log( "Fizikai hely: plugins/".$_GET['file'] );
	error_log( "Letoltes (elvileg) elindult (itt minden rendben lezajlott)" );
	error_log( "--------------------------------" );
	
	readfile( "plugins/".$_GET['file']);
	unlink( "plugins/".$_GET['file'] );	
	}
	
elseif( $_GET['type'] == 'handout' ) {
	error_log( "Fizikai hely: handout/".$_GET['file'] );
	error_log( "Letoltes (elvileg) elindult (itt minden rendben lezajlott)" );
	error_log( "--------------------------------" );

	$pub = sql_get( "publications", "id='".$_GET["id"]."'", "*" );
	$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
	
	$uid = ( !empty( $_SESSION["intra_user"] ) ? $_SESSION["intra_user"] : "Visitor" );
	$issue = str_replace( "_handout.pdf", "", $_GET['file'] );
	$issue = str_replace( "_", " ", $issue );
	$names = array( "userid", "type", "issue", "date" );
	$values = array( $uid, "Handout Download", $issue, time() );
	sql_add( "handout_log", $names, $values );

	readfile( "handout/".$_GET['file']);
	}

elseif( $_GET['type'] == 'one' ) {
	$chunksize = 5 * (1024 * 1024);
	$size = intval(sprintf("%u", filesize("temp/".$_GET['file'])));
    header('Content-Type: application/octet-stream');
    header('Content-Length: '.$size);
	
	if($size > $chunksize) { 
        $handle = fopen("temp/".$_GET['file'], 'rb'); 

        while (!feof($handle)) { 
			print(@fread($handle, $chunksize));
			ob_flush();
			flush();
			} 

        fclose($handle); 
		}
    else {
		readfile("temp/".$_GET['file']);
		}
	unlink( "temp/".$_GET['file'] );
    exit;	
	}
	
else {
	header('Content-Length: '.filesize( "temp/".$_GET['file']) );
	error_log( "Fizikai hely: temp/".$_GET['file'] );
	error_log( "Letoltes (elvileg) elindult (itt minden rendben lezajlott)" );
	error_log( "--------------------------------" );
	
	readfile( "temp/".$_GET['file']);
	unlink( "temp/".$_GET['file'] );
	}
?>