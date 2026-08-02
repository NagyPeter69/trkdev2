<?php

function dynaPrework( $file, $pageWidth, $colorStd = "FOGRA_39" ) {
	$pdf = new dynapdf();
	include('/var/www/html/config.inc.php');
	$pdf->CreateNewPDF(NULL);

	$pdf->InitColorManagement( NULL, NULL , 1 );
	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage | dynapdf::ifDocInfo);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);
	$pdf->SetPageCoords(dynapdf::pcTopDown);

	$pdf->SetJPEGQuality( 90 );

	$pdf->OpenImportFile( $file, dynapdf::ptOpen, NULL );
	$haveXFA = $pdf->GetInIsXFAForm();
	$isCollection = $pdf->GetInIsCollection();
	$destPage = 1;
	$pdf->ImportPDFFile($destPage, 1.0, 1.0);
	// AddOutputIntent() wants the page's SOURCE profile (the part's real
	// colorStd, e.g. FOGRA_51/FOGRA_52), not the sRGB display target -
	// R3's renders resolve this via partDetect()/resolveIccProfileByName()
	// and this needs to match or the two renders visibly diverge.
	$pdf->AddOutputIntent( "/var/www/html/r3API/r3/".resolveIccProfileByName( $colorStd ) );

	$pdf->CloseImportFile();
		
	$pdf->EditPage(1);
		//$pdf->SetBBox( dynapdf::pbCropBox, $cut["left"], $cut["bottom"], $cut["right"], $cut["top"] );
		$trim = $pdf->GetBBox( dynapdf::pbTrimBox );
		if( empty( $trim ) ) {
			$trim = $pdf->GetBBox( dynapdf::pbMediaBox );
			}
		
	$pdf->EndPage();

	$height = 400;
	$percent = $height/( intval( $trim["Top"] ) - intval( $trim["Bottom"] ) )*100;
	$width = ceil( ( intval( $trim["Right"] ) - intval( $trim["Left"] ) ) / 100 * $percent );
	if( intval( $pageWidth ) > 1 ) $width *= $pageWidth;
	error_log( $width." x ".$height );	
	error_log( $to );
	$to = substr($file, 0, -4).".jpg";
	$pdf->RenderPageToImage(1, $to, 0, $width, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);
	
	$width = ceil( ( intval( $trim["Right"] ) - intval( $trim["Left"] ) ) );
	$to = str_replace( ".pdf", "-cropbox.jpg", $file );
	$pdf->RenderPageToImage(1, $to, 0, $width, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);

	$pdf->EditPage(1);
		$pdf->SetBBox( dynapdf::pbCropBox, $trim["Left"], $trim["Bottom"], $trim["Right"], $trim["Top"] );
	$pdf->EndPage();	
		
	$width = ceil( ( intval( $trim["Right"] ) - intval( $trim["Left"] ) ) );
	$to = str_replace( ".pdf", "-trimbox.jpg", $file );
	$pdf->RenderPageToImage(1, $to, 0, $width, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);

	$pdf->CloseFile();	
	}

function DynaToImage( $sizes, $zoom, $from, $to, $colorStd = "FOGRA_39" ) {
	error_log( $from );
	error_log( $to );
	
	$imgname = $to;
	$scale = $zoom / 100;
	// $sizes = getBBox( $path."/".$file, "" );
	$box = getPDFBox_TEMP( "Mediabox Trimbox Cropbox Bleedbox", $from );		
	// $correction = array(
		// "left" => $sizes["left"],
		// "top" => $sizes["top"],
		// "width" => point_( $sizes["width"], $zoom ),
		// "height" => point_( $sizes["height"], $zoom ),
		// );

	// $cut = array(
		// "left" => $box["Mediabox"][0] + $correction["left"],
		// "top" => $box["Mediabox"][3] - $correction["top"],
		// );
	// $cut["right"] = ( $cut["left"] + point_( $sizes["width"], $zoom ) );
	// $cut["bottom"] = $cut["top"] - point_( $sizes["height"], $zoom );

	$cut = array(
		"left" => $sizes["left"],
		"bottom" => $sizes["bottom"],
		"right" => $sizes["right"],
		"top" => $sizes["top"],
		);
		
	$imgwidth = $sizes["width"];

	$pdf = new dynapdf();
	include('/var/www/html/config.inc.php');
	
	if( empty( $pdfname ) ) {
		$pdf->CreateNewPDF("/var/www/html/client/engine/dyna/test.pdf");
		}
	else {
		$pdf->CreateNewPDF($pdfname);
		}
	
	$pdf->InitColorManagement( NULL, NULL , 1 );
	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage | dynapdf::ifDocInfo);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);
	$pdf->SetPageCoords(dynapdf::pcTopDown);

	$pdf->SetResolution( 300 );
	$pdf->SetJPEGQuality( 100 );

	$pdf->OpenImportFile( $from , dynapdf::ptOpen, NULL );
	$haveXFA = $pdf->GetInIsXFAForm();
	$isCollection = $pdf->GetInIsCollection();
	$destPage = 1;
	$pdf->ImportPDFFile($destPage, 1.0, 1.0);
	$pdf->AddOutputIntent( "/var/www/html/r3API/r3/".resolveIccProfileByName( $colorStd ) );

	$pdf->CloseImportFile();

	$pdf->EditPage(1);
		//$pdf->SetBBox( dynapdf::pbCropBox, $cut["left"], $cut["bottom"], $cut["right"], $cut["top"] );
		$pdf->SetBBox( dynapdf::pbCropBox, $cut["left"], $cut["bottom"], $cut["right"], $cut["top"] );
	$pdf->EndPage();
	
	if( !empty( $imgname ) ) {
		$pdf->RenderPageToImage(1, $imgname, 0, $imgwidth, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);
		}
	$pdf->CloseFile();
	
	return true;
	}

function renderDynaPage( $origfile, $pdfname, $imgname, $imgwidth, $colorStd = "FOGRA_39" ) {
	$pdf = new dynapdf();
	include('/var/www/html/config.inc.php');
	
	if( empty( $pdfname ) ) {
		$pdf->CreateNewPDF(NULL);
		}
	else {
		$pdf->CreateNewPDF($pdfname);
		}
	
	$pdf->InitColorManagement( NULL, NULL , 1 );
	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage | dynapdf::ifDocInfo);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);
	$pdf->SetDocInfo(dynapdf::diTitle, 'Teszt title');
	$pdf->SetDocInfo(dynapdf::diSubject, 'Teszt Subject');
	$pdf->SetPageCoords(dynapdf::pcTopDown);

	$pdf->SetResolution( 300 );
	$pdf->SetJPEGQuality( 980 );

	$pdf->OpenImportFile($origfile, dynapdf::ptOpen, NULL );
	$haveXFA = $pdf->GetInIsXFAForm();
	$isCollection = $pdf->GetInIsCollection();
	$destPage = 1;
	$pdf->ImportPDFFile($destPage, 1.0, 1.0);
	$pdf->AddOutputIntent( "/var/www/html/r3API/r3/".resolveIccProfileByName( $colorStd ) );
	$pdf->CloseImportFile();

	if( !empty( $imgname ) ) {
		$pdf->RenderPageToImage(1, $imgname, 0, $imgwidth, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmPNG);
		}
	$pdf->CloseFile();
	}

?>