<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );

file_put_contents( "raw_".$_POST["filename"].".pdf", $_POST["pdf"] );

$pdf = new dynapdf();
include('../config.inc.php');
$pdf->CreateNewPDF( "rendered/".$_POST["filename"].".pdf" );			

$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
$pdf->SetImportFlags2(dynapdf::if2UseProxy);
$pdf->SetPDFVersion( 10 );	
$pdf->SetPageCoords(dynapdf::pcTopDown);
	
$pdf->OpenImportFile( "raw_".$_POST["filename"].".pdf", dynapdf::ptOpen, NULL);

$pdf->SetResolution( 300 );
$pdf->Append();
	$pdf->ImportPageEx(1, 1.0, 1.0);
$pdf->EndPage();
$pdf->CloseImportFile();
$pdf->RenderPageToImage(1, "rendered/".$_POST["filename"].".png", 200, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmPNG);
$pdf->CloseFile();
unlink( "raw_".$_POST["filename"].".pdf" );
?>