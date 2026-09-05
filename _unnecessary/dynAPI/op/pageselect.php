<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include( "../../engine.php" );
include( "drawEngine.php" );

$data = $_POST;

// The upload's destination path used to be the client-supplied original
// filename verbatim, with no extension enforced and no character
// sanitization - an uploaded file named e.g. "shell.php" would land
// directly in this web-servable directory, executable over HTTP.
// Generate a safe, server-controlled name instead (this is always a PDF
// import regardless of what the client claims the file is named).
$filename = "";
if( !empty( $_FILES["file"]["name"][0]["file"] ) ) {
	$filename = "upload_".uniqid().".pdf";
	move_uploaded_file( $_FILES["file"]["tmp_name"][0]["file"], $filename );
	}

$havePDF = true;
if( empty( $filename ) ) {
	$havePDF = false;
	}

$fname = "rendered/teszt-select-".time().".pdf" ;

error_log( "pagseselect" );

$pdf = new dynapdf();
include('../../config.inc.php');

$pdf->CreateNewPDF( $fname );			

$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage | dynapdf::ifDocInfo);
$pdf->SetImportFlags2(dynapdf::if2UseProxy);
$pdf->SetPDFVersion( 10 );	
$pdf->SetPageCoords(dynapdf::pcTopDown);

if( $havePDF ) {
	$pdf->OpenImportFile( $filename , dynapdf::ptOpen, NULL);	
	}
	
error_log( $data["page"] );

$pdf->Append();
if( $havePDF ) {
	$pdf->ImportPageEx($data["page"], 1.0, 1.0);
	$pdf->ImportCatalogObjects();
	}
$pdf->EndPage();
$pdf->CloseImportFile();

$pdf->CloseFile();

$path = $fname;
$type = pathinfo($path, PATHINFO_EXTENSION);
$data = file_get_contents($path);
$base64 = base64_encode($data);
$response["pdf"] = $base64;

unlink( $fname );
if( !empty( $filename ) ) {
	unlink( $filename );
	}

echo json_encode( $response );
	
?>