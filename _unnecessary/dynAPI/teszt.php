<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );

$pdf = new dynapdf();
include('../config.inc.php');
$pdf->CreateNewPDF( "teszt.pdf" );			

$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
$pdf->SetImportFlags2(dynapdf::if2UseProxy);
$pdf->SetPDFVersion( 10 );	
$pdf->SetPageCoords(dynapdf::pcTopDown);
	
$pdf->AddFontSearchPath( "fonts" , true );

$corr1x = 2;
$corr1y = 1;

$oc3 = $pdf->CreateOCG('Egyeb', false, true, dynapdf::oiAll);
echo $pdf->GetOpacity();

$pdf->Append();
		$pdf->SetColorSpace( 1 );	
		$pdf->SetPageCoords(dynapdf::pcTopDown);
		$pdf->SetFillColor( $pdf->CMYK( 0,0,0,255 ) );
		$pdf->SetFont('DIN Next W1G', dynapdf::fsRegular, 40.0, true, dynapdf::cpUnicode);
		$pdf->WriteText( coord(5+$corr1x), coord(17+$corr1y), "PRÓBA PRÓBA PRÓBA" );

$pdf->EndPage();
$pdf->CloseImportFile();

$pdf->AddOutputIntent("/var/www/html/dynAPI/ISOcoated_v2_eci.icc");

//$pdf->RenderPageToImage(1, "rendered/".$_POST["filename"].".png", 200, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmPNG);
$pdf->CloseFile();

?>