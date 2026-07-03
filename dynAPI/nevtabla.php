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
$pdf->AddFontSearchPath( "fonts" , false );

$corr1x = 2;
$corr1y = 0.4;

$oc2 = $pdf->CreateOCG('Kep', false, true, dynapdf::oiAll);
$oc3 = $pdf->CreateOCG('Egyeb', false, true, dynapdf::oiAll);
$oc1 = $pdf->CreateOCG('Forditott', false, true, dynapdf::oiAll);
$oc4 = $pdf->CreateOCG('Egyeb2', false, true, dynapdf::oiAll);

$dij = $_POST["dij1"]."   ".$_POST["dij2"]."   ".$_POST["dij3"];

$_POST["motto_text"] = trim($_POST["motto_text"])."”";

$motto_size = 15;
$motto_lead = 16.2;
$karakter = strlen($_POST["motto_text"] );
if( $karakter > 50 && $karakter < 110 ) {
	$motto_size = 13;
	$motto_lead = 16;
	}

if( $karakter >= 110 ) {
	$motto_size = 11;
	$motto_lead = 15;
	}

$pdf->SetResolution( 300 );
$pdf->SetJPEGQuality( 980 );
$pdf->Append();
	// NORMÁL RÉSZ	
	if( $_POST["user_type"] != "5" ) {
		if( $_POST["pic"] != "" ) {
			$pdf->BeginLayer($oc1);
			$pdf->SetColorSpace( 1 );
			file_put_contents( "raw_img.".$_POST["pic_ext"], $_POST["pic"] );
				
			$pdf->SetPageCoords(dynapdf::pcBottomUp);
			$pdf->InsertImageEx( coord(129 +$corr1x), coord(-2 +$corr1y), coord(67.131 +$corr1x), 0, "raw_img.".$_POST["pic_ext"], 1 );
			$pdf->EndLayer();
			}
			
		if( $_POST["pic"] != "" ) {
			$pdf->BeginLayer($oc4);
			$pdf->RotateCoords( 180, coord(214), coord(104) );
			$pdf->SetColorSpace( 1 );
			file_put_contents( "raw_2img.".$_POST["pic_ext"], $_POST["pic"] );
			$pdf->SetPageCoords(dynapdf::pcBottomUp);	
			$pdf->InsertImageEx( coord(129 +$corr1x), coord(-2+$corr1y), coord(67.131 +$corr1x), 0, "raw_2img.".$_POST["pic_ext"], 1 );
			$pdf->EndLayer();
			}	
		}
	
	$pdf->BeginLayer($oc3);
	$pdf->SetColorSpace( 1 );
	$pdf->ImportPageEx(1, 1.0, 1.0);	
	$pdf->SetPageCoords(dynapdf::pcTopDown);
	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, $motto_size, true, dynapdf::cpUnicode);
	$pdf->SetLeading( $motto_lead );
	
	if( $_POST["user_type"] == "5" ) {
		$pdf->SetPageCoords(dynapdf::pcBottomUp);
		$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 24, true, dynapdf::cpUnicode);
		$pdf->WriteFTextEx( coord(4.15+$corr1x), coord(46+$corr1y), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, $_POST["first_name"]." ".$_POST["last_name"] );
		$pdf->SetPageCoords(dynapdf::pcTopDown);
		}
	else {
		$pdf->WriteFTextEx( coord(2.8+$corr1x), coord(62.8+$corr1y), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, "„" );
		$pdf->WriteFTextEx( coord(4.75+$corr1x), coord(62.8+$corr1y), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, $_POST["motto_text"] );
		}

	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Light', dynapdf::fsItalic, 9.5, true, dynapdf::cpUnicode);
	if( $_POST["user_type"] != "5" ) {
		$pdf->WriteFTextEx( coord(50+$corr1x), coord(73.8+$corr1y), coord(70+$corr1x), coord(10+$corr1x), dynapdf::taRight, $_POST["first_name"]." ".$_POST["last_name"] );
		}

	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 11, true, dynapdf::cpUnicode);
	$pdf->SetLeading( 16.2 );
	if( $_POST["user_type"] == "5" ) {
		$pdf->WriteFTextEx( coord(7.5+$corr1x), coord(69.1+$corr1y), coord(119+$corr1x), coord(10+$corr1x), dynapdf::taLeft, $dij );
		}
	else {
		$pdf->WriteFTextEx( coord(7.5+$corr1x), coord(82.1+$corr1y), coord(119+$corr1x), coord(10+$corr1x), dynapdf::taLeft, $dij );
		}

	$pdf->EndLayer();

	if( $_POST["user_type"] == "5" ) {
		$pdf->SetFillColor( $pdf->CMYK( 25, 0, 0, 128 ) );
		$pdf->SetPageCoords(dynapdf::pcBottomUp);
		$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 11, true, dynapdf::cpUnicode);
		$pdf->WriteFTextEx( coord(5.5+$corr1x), coord(17.9+$corr1y), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, "„" );
		$pdf->WriteFTextEx( coord(7.5+$corr1x), coord(17.9+$corr1y), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, $_POST["motto_text"] );		
		}	

	//FEJTETŐS RÉSZ
	$pdf->RotateCoords( 180, coord(214), coord(104) );
	
	$pdf->BeginLayer($oc2);
	$pdf->SetColorSpace( 1 );
	$pdf->SetPageCoords(dynapdf::pcBottomUp);
	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, $motto_size, true, dynapdf::cpUnicode);
	$pdf->SetLeading( $motto_lead );

	if( $_POST["user_type"] == "5" ) {
		$pdf->SetPageCoords(dynapdf::pcBottomUp);
		$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 24, true, dynapdf::cpUnicode);
		$pdf->WriteFTextEx( coord(3.8+$corr1x), coord(46+$corr1y), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, $_POST["first_name"]." ".$_POST["last_name"] );
		$pdf->SetPageCoords(dynapdf::pcTopDown);
		}
	else {
		$pdf->WriteFTextEx( coord(2.8+$corr1x), coord( ( -62.8-$corr1y ) ), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, "„" );
		$pdf->WriteFTextEx( coord(4.75+$corr1x), coord( ( -62.8-$corr1y ) ), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, $_POST["motto_text"] );
		}

	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Light', dynapdf::fsItalic, 9.5, true, dynapdf::cpUnicode);
	if( $_POST["user_type"] != "5" ) {
		$pdf->WriteFTextEx( coord(50+$corr1x), coord(-73.8-$corr1y), coord(70+$corr1x), coord(10+$corr1x), dynapdf::taRight, $_POST["first_name"]." ".$_POST["last_name"] );
		}
	
	$pdf->SetFillColor( $pdf->CMYK( 0, 0, 0, 0 ) );
	$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 11, true, dynapdf::cpUnicode);
	$pdf->SetLeading( 16.2 );
	
	if( $_POST["user_type"] == "5" ) {
		$pdf->WriteFTextEx( coord(7.5+$corr1x), coord(69.1+$corr1y), coord(119+$corr1x), coord(10+$corr1x), dynapdf::taLeft, $dij );
		}
	else {
		$pdf->WriteFTextEx( coord(7.5+$corr1x), coord(-83.1+$corr1y), coord(119+$corr1x), coord(10+$corr1x), dynapdf::taLeft, $dij );
		}

	if( $_POST["user_type"] == "5" ) {
		$pdf->SetFillColor( $pdf->CMYK( 25, 0, 0, 128 ) );
		$pdf->SetPageCoords(dynapdf::pcBottomUp);
		$pdf->SetFont('DIN Next W1G Medium', dynapdf::fsRegular, 11, true, dynapdf::cpUnicode);
		$pdf->WriteFTextEx( coord(5.5+$corr1x), coord(17.9+$corr1y), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, "„" );
		$pdf->WriteFTextEx( coord(7.5+$corr1x), coord(17.9+$corr1y), coord(117+$corr1x), coord(20+$corr1x), dynapdf::taLeft, $_POST["motto_text"] );		
		}	

	$pdf->EndLayer();
	
	if( $_POST["user_type"] == "5" ) {	
		if( $_POST["pic"] != "" ) {
			$pdf->BeginLayer($oc1);
			$pdf->SetColorSpace( 1 );
			file_put_contents( "raw_img.".$_POST["pic_ext"], $_POST["pic"] );
				
			$pdf->SetPageCoords(dynapdf::pcBottomUp);
			$pdf->InsertImageEx( coord(115 +$corr1x), coord(-2 +$corr1y), coord(67.131 +$corr1x), 0, "raw_img.".$_POST["pic_ext"], 1 );
			$pdf->EndLayer();
			}
			
		if( $_POST["pic"] != "" ) {
			$pdf->BeginLayer($oc4);
			$pdf->RotateCoords( 180, coord(214), coord(104) );
			$pdf->SetColorSpace( 1 );
			file_put_contents( "raw_2img.".$_POST["pic_ext"], $_POST["pic"] );
			$pdf->SetPageCoords(dynapdf::pcBottomUp);	
			$pdf->InsertImageEx( coord(115 +$corr1x), coord(-2+$corr1y), coord(67.131 +$corr1x), 0, "raw_2img.".$_POST["pic_ext"], 1 );
			$pdf->EndLayer();
			}
		}
	
$pdf->EndPage();
$pdf->CloseImportFile();
$pdf->RenderPageToImage(1, "rendered/".$_POST["filename"].".png", 110, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmPNG);
$pdf->CloseFile();

unlink( "raw_".$_POST["filename"].".pdf" );
if( $_POST["pic"] != "" ) {
	unlink( "raw_img.".$_POST["pic_ext"] );
	}
?>