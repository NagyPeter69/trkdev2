<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );

// filename was written to disk with no sanitization at all (full path
// traversal via ../ sequences, on top of the missing-auth issue tracked
// separately) - restrict to a safe character set before using it in a
// path, regardless of who ends up allowed to call this endpoint.
$safeFilename = preg_replace( '/[^A-Za-z0-9_-]/', '', $_POST["filename"] ?? '' );
$safePicExt = preg_replace( '/[^A-Za-z0-9]/', '', $_POST["pic_ext"] ?? '' );
file_put_contents( "raw_".$safeFilename.".pdf", $_POST["pdf"] );

$pdf = new dynapdf();
include('../config.inc.php');
$pdf->CreateNewPDF( "rendered/".$safeFilename.".pdf" );			

$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
$pdf->SetImportFlags2(dynapdf::if2UseProxy);
$pdf->SetPDFVersion( 10 );	
$pdf->SetPageCoords(dynapdf::pcTopDown);
	
$pdf->OpenImportFile( "raw_".$safeFilename.".pdf", dynapdf::ptOpen, NULL);
$pdf->AddFontSearchPath( "fonts" , false );

$corr1x = 2;
$corr1y = 0.4;

$oc2 = $pdf->CreateOCG('Kep', false, true, dynapdf::oiAll);
$oc3 = $pdf->CreateOCG('Egyeb', false, true, dynapdf::oiAll);
$oc1 = $pdf->CreateOCG('Forditott', false, true, dynapdf::oiAll);
$oc4 = $pdf->CreateOCG('Egyeb2', false, true, dynapdf::oiAll);

$pdf->SetResolution( 300 );
$pdf->SetJPEGQuality( 980 );
$pdf->Append();
	// NORMÁL RÉSZ	
	if( $_POST["user_type"] != "5" ) {
		if( $_POST["pic"] != "" ) {
			$pdf->BeginLayer($oc1);
			$pdf->SetColorSpace( 1 );
			file_put_contents( "raw_img.".$safePicExt, $_POST["pic"] );
				
			$pdf->SetPageCoords(dynapdf::pcBottomUp);
			$pdf->InsertImageEx( coord(140 +$corr1x), coord(-2 +$corr1y), coord(67.131 +$corr1x), 0, "raw_img.".$safePicExt, 1 );
			$pdf->EndLayer();
			}
			
		if( $_POST["pic"] != "" ) {
			$pdf->BeginLayer($oc4);
			$pdf->RotateCoords( 180, coord(214), coord(104) );
			$pdf->SetColorSpace( 1 );
			file_put_contents( "raw_2img.".$safePicExt, $_POST["pic"] );
			$pdf->SetPageCoords(dynapdf::pcBottomUp);	
			$pdf->InsertImageEx( coord(140 +$corr1x), coord(-2+$corr1y), coord(67.131 +$corr1x), 0, "raw_2img.".$safePicExt, 1 );
			$pdf->EndLayer();
			}	
		}
	
	$pdf->BeginLayer($oc3);
	$pdf->SetColorSpace( 1 );
	$pdf->ImportPageEx(1, 1.0, 1.0);
	$pdf->SetPageCoords(dynapdf::pcTopDown);
	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 24, true, dynapdf::cpUnicode);
	$pdf->WriteFTextEx( coord(4.5+$corr1x), coord(77+$corr1y), coord(179+$corr1x), coord(10+$corr1x), dynapdf::taLeft, $_POST["first_name"]." ".$_POST["last_name"] );

	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 12, true, dynapdf::cpUnicode);
	$pdf->SetLeading( 16.2 );
	$pdf->WriteFTextEx( coord(7.5+$corr1x), coord(88.3+$corr1y), coord(179+$corr1x), coord(10+$corr1x), dynapdf::taLeft, $_POST["titulus"] );
	$pdf->EndLayer();

	//FEJTETŐS RÉSZ
	$pdf->RotateCoords( 180, coord(214), coord(104) );
	
	$pdf->BeginLayer($oc2);
	$pdf->SetColorSpace( 1 );
	$pdf->SetPageCoords(dynapdf::pcBottomUp);

	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 24, true, dynapdf::cpUnicode);
	$pdf->WriteFTextEx( coord(4.5+$corr1x), coord(-76.7-$corr1y), coord(179+$corr1x), coord(10+$corr1x), dynapdf::taLeft, $_POST["first_name"]." ".$_POST["last_name"] );
	
	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 12, true, dynapdf::cpUnicode);
	$pdf->SetLeading( 16.2 );
	$pdf->WriteFTextEx( coord(7.5+$corr1x), coord(-89+$corr1y), coord(179+$corr1x), coord(10+$corr1x), dynapdf::taLeft, $_POST["titulus"] );
	$pdf->EndLayer();
	
$pdf->EndPage();
$pdf->CloseImportFile();
$pdf->RenderPageToImage(1, "rendered/".$safeFilename.".png", 110, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmPNG);
$pdf->CloseFile();

unlink( "raw_".$safeFilename.".pdf" );
if( $_POST["pic"] != "" ) {
	unlink( "raw_img.".$safePicExt );
	}
?>