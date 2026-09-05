<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );

// filename was written to disk with no sanitization at all (full path
// traversal via ../ sequences, on top of the missing-auth issue tracked
// separately) - restrict to a safe character set before using it in a
// path, regardless of who ends up allowed to call this endpoint.
$safeFilename = preg_replace( '/[^A-Za-z0-9_-]/', '', $_POST["filename"] ?? '' );
file_put_contents( "raw_".$safeFilename.".pdf", $_POST["pdf"] );

$pdf = new dynapdf();
include('../config.inc.php');
$pdf->CreateNewPDF( "rendered/".$safeFilename.".pdf" );			

$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
$pdf->SetImportFlags2(dynapdf::if2UseProxy);
$pdf->SetPDFVersion( 10 );	
$pdf->SetPageCoords(dynapdf::pcTopDown);
	
$pdf->OpenImportFile( "raw_".$safeFilename.".pdf", dynapdf::ptOpen, NULL);

$pdf->SetResolution( 300 );
$pdf->Append();
	$pdf->ImportPageEx(1, 1.0, 1.0);
$pdf->EndPage();
$pdf->CloseImportFile();
$pdf->RenderPageToImage(1, "rendered/".$safeFilename.".png", 200, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmPNG);
$pdf->CloseFile();
unlink( "raw_".$safeFilename.".pdf" );
?>