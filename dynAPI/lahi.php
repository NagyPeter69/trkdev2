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
$corr1y = 1;

$oc2 = $pdf->CreateOCG('Kep', false, true, dynapdf::oiAll);
$oc3 = $pdf->CreateOCG('Egyeb', false, true, dynapdf::oiAll);

$pdf->SetResolution( 300 );
$pdf->SetJPEGQuality( 980 );
$pdf->Append();
		if( $_POST["pic"] != "" ) {
			error_log( "DEBUG: név: ".$_POST["first_name"]." ".$_POST["last_name"]." | pic: ".$safeFilename." , ".$safePicExt );
			$pdf->BeginLayer($o.c2);
			$pdf->SetColorSpace( 1 );
			file_put_contents( "raw_img.".$safePicExt, $_POST["pic"] );
			
			$width = $pdf->GetPageWidth() - coord(208 +$corr1x);			
			$pdf->SetPageCoords(dynapdf::pcBottomUp);
			$pdf->InsertImageEx( coord(34.43 +$corr1x), coord(-2 +$corr1y), coord(67.13 +$corr1x), 0, "raw_img.".$safePicExt, 1 );
			$pdf->EndLayer();
			}
			
		$pdf->BeginLayer($oc3);
		$pdf->SetColorSpace( 1 );
		$pdf->ImportPageEx(1, 1.0, 1.0);	
		$pdf->SetPageCoords(dynapdf::pcTopDown);
		$pdf->SetFillColor( $pdf->CMYK( 255,0,217,115 ) );
		$pdf->SetFont('Formata OTP Reg', dynapdf::fsItalic, 15, true, dynapdf::cpUnicode);
		$pdf->WriteText( coord(28.3+$corr1x), coord(6.34+$corr1y), $_POST["nickname"] );
		
		$pdf->SetFillColor( $pdf->CMYK( 255,0,217,115 ) );
		$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 9.0, true, dynapdf::cpUnicode);
		$pdf->WriteText( coord(5+$corr1x), coord(22+$corr1y), $_POST["first_name"]." ".$_POST["last_name"] );

		$pdf->SetFillColor( $pdf->CMYK( 178, 0, 255, 0 ) );
		$pdf->SetFont('DIN Next W1G Light', dynapdf::fsRegular, 7.0, true, dynapdf::cpUnicode);
		$pdf->WriteText( coord(5+$corr1x), coord(25.4+$corr1y), "az Ön személyes tanácsadója" );
		
		$pdf->SetFillColor( $pdf->CMYK( 255,0,217,115 ) );
		$pdf->SetFont('DIN Next W1G Light', dynapdf::fsRegular, 7.0, true, dynapdf::cpUnicode);

		if( empty( $_POST["fiok2"] ) ) {
			$pdf->WriteText( coord(5+$corr1x), coord(31.3+$corr1y), "OTP Bank Nyrt." );
			$pdf->WriteText( coord(5+$corr1x), coord(34.3+$corr1y), $_POST["fiok"] );
			$pdf->WriteText( coord(5+$corr1x), coord(37.3+$corr1y), "Telefon: ".$_POST["phone"] );
			$pdf->WriteText( coord(5+$corr1x), coord(40.3+$corr1y), $_POST["email"] );
			$pdf->WriteText( coord(5+$corr1x), coord(43.3+$corr1y), "www.otpbank.hu" );			
			}
		else {
			$pdf->WriteText( coord(5+$corr1x), coord(29.8+$corr1y), "OTP Bank Nyrt." );
			$pdf->WriteText( coord(5+$corr1x), coord(32.8+$corr1y), $_POST["fiok"] );
			$pdf->WriteText( coord(5+$corr1x), coord(35.8+$corr1y), $_POST["fiok2"] );
			$pdf->WriteText( coord(5+$corr1x), coord(38.8+$corr1y), "Telefon: ".$_POST["phone"] );
			$pdf->WriteText( coord(5+$corr1x), coord(41.8+$corr1y), $_POST["email"] );
			$pdf->WriteText( coord(5+$corr1x), coord(44.8+$corr1y), "www.otpbank.hu" );			
			}
		$pdf->EndLayer();
$pdf->EndPage();
$pdf->CloseImportFile();
$pdf->RenderPageToImage(1, "rendered/".$safeFilename.".png", 200, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);
$pdf->CloseFile();
unlink( "raw_".$safeFilename.".pdf" );
if( $_POST["pic"] != "" ) {
	unlink( "raw_img.".$safePicExt );
	}
?>