<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include( "../../engine.php" );
include( "drawEngine.php" );

$data = $_POST;

/*ob_flush();
ob_start();
var_dump( $data );

file_put_contents("dump.txt", ob_get_flush());
die();*/
$type = $data["type"];
$download = false;
$fix = false;
if( $data["operation"] == "download" ) {
	$download = true;
	}	

$haveArrow = false;
if( $data["measure"] == "true" ) {
	$haveArrow = true;
	}

if( $data["width"] == "0" or $data["height"] == "0" ) {
	$haveArrow = false;
	}

if( $data["operation"] == "downloadFix" ) {
	$fix = true;
	$download = true;
	$haveArrow = false;
	$data["width"] = 0;
	$data["height"] = 0;
	$data["flap"] = 0;
	$data["spine"] = 0;
	$data["bleed"] = 0;
	}	

$markOrig = true;
if( $data["draworig"] == "no" ) {
	$markOrig = false;
	}
	
$markDraw = false;
if( $data["drawmark"] == "yes" ) {
	$markDraw = true;
	}

// The upload's destination path used to be the client-supplied original
// filename verbatim, with no extension enforced and no character
// sanitization - an uploaded file named e.g. "shell.php" would land
// directly in this web-servable directory, executable over HTTP.
// Generate a safe, server-controlled name instead (this is always a PDF
// import regardless of what the client claims the file is named).
$filename = "";
if( !empty( $_FILES["file"]["name"][0]["file"] ) ) {
	$filename = "upload_".uniqid().".pdf";
	//file_put_contents( $filename, base64_decode( $data["fileContent"] ) );
	if( move_uploaded_file( $_FILES["file"]["tmp_name"][0]["file"], $filename ) ) {
		include( "coverDraw_.php" );
		}
	else {
		include( "coverDraw_.php" );
		}
	}
else {
	include( "coverDraw_.php" );
	}	

?>