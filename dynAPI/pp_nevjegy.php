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
		if( !empty( $_POST["pic"] ) ) {
			$pdf->BeginLayer($oc2);
			$pdf->SetColorSpace( 1 );
			file_put_contents( "raw_img.".$safePicExt, $_POST["pic"] );

			$width = $pdf->GetPageWidth() - coord(208 +$corr1x);			
			$pdf->SetPageCoords(dynapdf::pcBottomUp);
			$pdf->InsertImageEx( coord(39.43 +$corr1x), coord(-3 +$corr1x), coord(67.13 +$corr1x), 0, "raw_img.".$safePicExt, 1 );
			$pdf->EndLayer();
			}
		
		$pdf->BeginLayer($oc3);
		$pdf->SetColorSpace( 1 );
		$pdf->ImportPageEx(1, 1.0, 1.0);
		$pdf->SetPageCoords(dynapdf::pcTopDown);

		$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 9.0, true, dynapdf::cpUnicode);
		$pdf->SetFillColor( $pdf->CMYK( 255,0,217,115 ) );
		
		$pdf->WriteText( coord(5+$corr1x), coord(17+$corr1y), $_POST["first_name"]." ".$_POST["last_name"] );

		$pdf->SetFillColor( $pdf->CMYK( 178, 0, 255, 0 ) );
		$pdf->SetFont('DIN Next W1G Light', dynapdf::fsRegular, 7.0, true, dynapdf::cpUnicode);
		//$pdf->WriteText( coord(5+$corr1x), coord(20.4+$corr1y), $_POST["role"] );
		$pdf->WriteFTextEx( coord(5+$corr1x), coord(20.4+$corr1y), coord(40+$corr1x), coord(43+$corr1x), dynapdf::taLeft, $_POST["role"] );
		
		$pdf->SetFillColor( $pdf->CMYK( 255,0,217,115 ) );
		$pdf->SetFont('DIN Next W1G Light', dynapdf::fsRegular, 7.0, true, dynapdf::cpUnicode);
		
		$y = 43.3;
		
		$pdf->WriteText( coord(5+$corr1x), coord($y+$corr1y), "www.otppp.hu" );
		$y -= 3;

		if( strpos( $_POST["email"], "@otppp.hu" ) or strpos( $_POST["email"], "@otpltp.hu" ) or strpos( $_POST["email"], "@otpip.hu" ) ) {
			$pdf->WriteText( coord(5+$corr1x), coord($y+$corr1y), $_POST["email"] );
			$y -= 3;
			}	
			
		$pdf->WriteText( coord(5+$corr1x), coord($y+$corr1y), "Telefon: ".$_POST["phone"] );	
		$y -= 3;	
			
		if( $_POST["iroda"] != " Nincs,  ." ) {
			$pdf->WriteText( coord(5+$corr1x), coord($y+$corr1y), "Iroda: ".$_POST["iroda"] );
			$y -= 3;
			}				

		$pdf->WriteText( coord(5+$corr1x), coord($y+$corr1y), "Székhely: 1138 Budapest, Váci út 135-139." );
		$y -= 3;
		$pdf->WriteText( coord(5+$corr1x), coord($y+$corr1y), "OTP Pénzügyi Pont Zrt." );
		$y -= 3;

		$pdf->EndLayer();
$pdf->EndPage();
$pdf->CloseImportFile();
$pdf->RenderPageToImage(1, "rendered/".$safeFilename.".png", 200, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmPNG);
$pdf->CloseFile();
unlink( "raw_".$safeFilename.".pdf" );
if( !empty( $_POST["pic"] ) ) {
	unlink( "raw_img.".$safePicExt );
	}
?>