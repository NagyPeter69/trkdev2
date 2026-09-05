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

$pdf->SetResolution( 300 );
$pdf->SetJPEGQuality( 980 );

$pdf->OpenImportFile( 'szorolap_lmb_szoveg.pdf', dynapdf::ptOpen,  NULL);
$orientation = $pdf->GetInOrientation(1);
$tmpl = $pdf->ImportPage(1);
$pdf->CloseImportFile();
	
$pdf->OpenImportFile( "raw_".$safeFilename.".pdf", dynapdf::ptOpen, NULL);
$pdf->AddFontSearchPath( "fonts" , false );

$corr1x = 2;
$corr1y = 0.4;

$oc2 = $pdf->CreateOCG('Kep', false, true, dynapdf::oiAll);
$oc3 = $pdf->CreateOCG('Egyeb', false, true, dynapdf::oiAll);

$_POST["motto1"] = trim($_POST["motto1"])."”";
$_POST["motto1"] = str_replace( "a ", "a ", $_POST["motto1"] );
$_POST["motto1"] = str_replace( "A ", "A ", $_POST["motto1"] );
$_POST["motto1"] = str_replace( "az ", "az ", $_POST["motto1"] );
$_POST["motto1"] = str_replace( "Az ", "Az ", $_POST["motto1"] );

$motto_size = 14;
$motto_lead = 18;
$text_start = 45;
$karakter = strlen($_POST["motto1"] );
if( $karakter > 100 && $karakter < 115 ) {
	$text_start += 5;
	}

if( $karakter >= 115 ) {
	$text_start += 15;
	}

$pdf->Append();
	if( $_POST["pic"] != "" ) {
		$pdf->BeginLayer($oc2);
		$pdf->SetColorSpace( 1 );
		file_put_contents( "raw_img.".$safePicExt, $_POST["pic"] );
		
		$pdf->SetPageCoords(dynapdf::pcBottomUp);
		$pdf->InsertImageEx( coord(64 +$corr1x), coord(24 +$corr1y), coord(80 +$corr1x), 0, "raw_img.".$safePicExt, 1 );
		$pdf->EndLayer();
		}
	
	$pdf->BeginLayer($oc3);	
	$pdf->SetColorSpace( 1 );
	$pdf->ImportPageEx(1, 1.0, 1.0);
		
	$pdf->SetPageCoords(dynapdf::pcTopDown);
	$pdf->SetFillColor( $pdf->CMYK( 178, 0, 255, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, $motto_size, true, dynapdf::cpUnicode);
	$pdf->SetLeading( $motto_lead );
	$pdf->WriteFTextEx( coord(12+$corr1x), coord(27.5+$corr1y), coord(108+$corr1x), coord(43+$corr1x), dynapdf::taLeft, "„" );
	$pdf->WriteFTextEx( coord(14+$corr1x), coord(27.5+$corr1y), coord(118+$corr1x), coord(43+$corr1x), dynapdf::taLeft, $_POST["motto1"] );

	//SZÖVEG IDE
	$pdf->PlaceTemplateEx($tmpl, 0, coord($text_start+$corr1x), 0, 0);
	
	$pdf->SetWordSpacing( 0 );
	$pdf->SetFillColor( $pdf->CMYK( 255,0,217,115 ) );
	$pdf->SetFont('Formata OTP Reg', dynapdf::fsItalic, 19, true, dynapdf::cpUnicode);
	$pdf->WriteText( coord(43+$corr1x), coord(135+$corr1y), $_POST["nickname"] );
	
	$pdf->SetFillColor( $pdf->CMYK( 255,0,217,115 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 12.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( coord(14+$corr1x), coord(148+$corr1y), $_POST["first_name"]." ".$_POST["last_name"] );
	
	$pdf->SetFillColor( $pdf->CMYK( 255,0,217,115 ) );
	$pdf->SetFont('DIN Next W1G Light', dynapdf::fsRegular, 10.0, true, dynapdf::cpUnicode);
	$pdf->SetLeading( 13 );
	$pdf->WriteText( coord(14+$corr1x), coord(162.4+$corr1y), "OTP Bank Nyrt." );
	$pdf->WriteText( coord(14+$corr1x), coord(166.9+$corr1y), "Telefon: ".$_POST["phone"] );
	$pdf->WriteText( coord(14+$corr1x), coord(171.4+$corr1y), $_POST["email"] );
	$pdf->WriteText( coord(14+$corr1x), coord(175.9+$corr1y), "www.otpbank.hu" );
	$pdf->EndLayer();
	
$pdf->EndPage();
$pdf->CloseImportFile();
$pdf->RenderPageToImage(1, "rendered/".$safeFilename.".png", 150, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmPNG);
$pdf->CloseFile();
unlink( "raw_".$safeFilename.".pdf" );
?>