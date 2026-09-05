<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include( "../../engine.php" );
include( "drawEngine.php" );

$data = $_POST;
$img = "thumb_".time().".jpg";

$response = array();

if( !empty( $_FILES["file"]["name"][0]["file"] ) ) {
	// The upload's destination path used to be the client-supplied original
	// filename verbatim, with no extension enforced and no character
	// sanitization - an uploaded file named e.g. "shell.php" would land
	// directly in this web-servable directory, executable over HTTP.
	// Generate a safe, server-controlled name instead (this is always a PDF
	// import regardless of what the client claims the file is named).
	$filename = "upload_".uniqid().".pdf";
	if( move_uploaded_file( $_FILES["file"]["tmp_name"][0]["file"], $filename ) ) {
		$pdf = new dynapdf();
		include('config.inc.php');
		$pdf->CreateNewPDF(NULL);
		$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
		$pdf->SetImportFlags2(dynapdf::if2UseProxy);
		
		$pdf->InitColorManagement( NULL, NULL , 1 );
		
		$pdf->OpenImportFile( $filename, dynapdf::ptOpen, NULL );
		$pdf->ImportPDFFile( 1, 1.0, 1.0 );	
		$pdf->CloseImportfile();
		$pdf->SetJPEGQuality( 100 );
		
		$pdf->AddOutputIntent("/var/www/html/dynAPI/ISOcoated_v2_eci.icc");
		$pdf->RenderPageToImage(1, $img, 40, 1, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfJPEG, dynapdf::ifmJPEG);
		$pdf->CloseFile();

		$path = "./rendered/".$img;
		$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
			
		$response["img"] = $base64;	
		$response["status"] = "success";

		list($width, $height) = getimagesize( $path );
		if( $width > $height)
		    $response["orient"] = "landscape";
		else
		    $response["orient"] = "portrait";
		
		unlink( "./rendered/".$img );
		unlink( $fname );
		if( !empty( $filename ) ) {
			unlink( $filename );
			}
		}
	else {
		$response["status"] = "failed"; 
		$response["reason"] = "can't upload";
		}
	}
else {
	$response["status"] = "failed"; 
	$response["reason"] = "no pdf";
	}
	
echo json_encode( $response );

?>