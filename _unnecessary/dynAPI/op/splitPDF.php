<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include( "../../engine.php" );
include( "drawEngine.php" );

$data = $_POST;
$files = array();
/*ob_flush();
ob_start();
var_dump( $_FILES );

file_put_contents("dump.txt", ob_get_flush());*/

function splitting( $file, $type, $start ) {
	global $origname;
	
	$pdf = new dynapdf();
	include('../../config.inc.php');
	
	$fname = "split-".time().".pdf";
	
	$pdf->CreateNewPDF( $fname );
	
	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);
	$pdf->SetPDFVersion( 10 );	
	$pdf->SetPageCoords(dynapdf::pcTopDown);
	
	$pdf->OpenImportFile( $file , dynapdf::ptOpen, NULL);
	$pages = $pdf->GetInPageCount();
	
	$maradek = $pages % 2;
	
	switch( $type ) {
		case "A":
			$start = $start;
			$finish = ( $start + floor($pages / 2) );
			break;
			
		case "B":
			$start = $start + floor($pages / 2);
			$finish = ( $start + floor($pages / 2) );
			if( $maradek !== 0 ) {
				$finish++;
				}
			break;
		}
	
	$end = $start + round($pages / 2) - 1;	
	if( $maradek !== 0 ) {
		$end--;
		}
		
	if( $type == "B" ) {
		$end = $pages;
		}
	
	error_log( $type );
	error_log( $start );
	error_log( $start." + ( ".$pages." / 2 )" );
	error_log( "beillesztendő oldal: ".$finish );
	error_log( "eredetileg a fele: ".($pages / 2) );
	error_log( "maradék: ".$maradek );
	
	for( $i = $start; $i < $finish; $i++ ) {
		$pdf->Append();
			$tmpl = $pdf->ImportPageEx( $i, 1, 1 );
			$pdf->PlaceTemplateEx($tmpl, 0, 0, 0, 0);
		$pdf->EndPage();
		
		error_log( "Oldal beillesztése: ".$i );
		}

	$pdf->CloseImportFile();
	$pdf->CloseFile();
	
	$newname = str_pad( $start, 3, '0', STR_PAD_LEFT)."-".str_pad( $end, 3, '0', STR_PAD_LEFT)."|".$origname;
	rename( $fname, $newname );
	unset( $pdf );
	
	return array( "filename" => $newname, "start" => $start, "end" => $end );
	}

function splitpdf( $file, $start = "1" ) {
	global $files, $response;
	
	//Első szelet
	$file_A = splitting( $file, "A", $start );
	$files[] = $file_A["filename"];
	
	//Második szelet
	$file_B = splitting( $file, "B", $start );
	$files[] = $file_B["filename"];

	//Irány tovább
	/*error_log( json_encode( $file_A ) );
	error_log( json_encode( $file_B ) );
	
	clearstatcache();
	$size = filesize( $file_A["filename"] );
	if( $size > 100000000) {
		splitpdf( $file_A["filename"], $file_A["start"] );
		}

	clearstatcache();
	$size = filesize( $file_B["filename"] );
	if( $size > 100000000) {
		splitpdf( $file_B["filename"], $file_B["start"] );
		}*/
	
	//unlink( $file );
	}

if( $_FILES["file"]["error"][0]["file"] == "0" ) {
	// The upload's destination path used to be the client-supplied original
	// filename verbatim, with no extension enforced and no character
	// sanitization - an uploaded file named e.g. "shell.php" would land
	// directly in this web-servable directory, executable over HTTP.
	// Generate a safe, server-controlled name instead (this is always a PDF
	// import regardless of what the client claims the file is named).
	$filename = "";
	if( !empty( $_FILES["file"]["name"][0]["file"] ) ) {
		$filename = "upload_".uniqid().".pdf";
		if( move_uploaded_file( $_FILES["file"]["tmp_name"][0]["file"], $filename ) ) {
			$origname = $filename;
			splitpdf( $filename );
			
			$return_files = array();
			for( $i = 0; $i < count( $files ); $i++ ) {	
				$return_files[] = array(
					"path" => "dynAPI/op/",
					"name" => $files[$i],
					);
				}
			
			$response = array( "status" => "ok", "files" => $return_files );
			}
		}
	}
else {
	$response = array( "status" => "error", "message" => "Hiba a feltöltés során. Kód: ".$_FILES["file"]["error"][0]["file"] );
	}

unlink( $origname );

echo json_encode( $response );
	
?>