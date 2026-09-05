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

// Draws the tbox/sbox/sizes guide rectangles onto an ad PDF for the
// ad-check review overlay. This used to be a separate HTTP POST (with the
// whole PDF base64-encoded into the request body) to
// http://{DYNAIP}/dynAPI/tracker/ad_check.php - a second, now-unreachable
// production box calling itself over the network to do a DynaPDF operation
// it could just as well do in-process. Ported 1:1 from that file's drawing
// logic; only the transport changed (writes straight to $destPdf instead of
// base64-round-tripping the result back over HTTP).
function dynaAdCheckOverlay( $sourcePdf, $destPdf, $sizes, $tbox, $sbox ) {
	$pdf = new dynapdf();
	include('/var/www/html/config.inc.php');
	$pdf->CreateNewPDF( $destPdf );
	$pdf->InitColorManagement( NULL, NULL , 1 );

	$pdf->OpenImportFile( $sourcePdf, dynapdf::ptOpen, NULL );
	$pdf->ImportPDFFile( 1, 1.0, 1.0 );
	$pdf->CloseImportfile();

	$pdf->EditPage(1);
		$width = $pdf->GetPageWidth();
		$height = $pdf->GetPageHeight();

		$pdf->SetLineWidth( 1 );
		$g = $pdf->RGB( 0, 200, 0 );
		$r = $pdf->RGB( 200, 0, 0 );
		$b = $pdf->RGB( 0, 0, 255 );
		$pdf->SetStrokeColor( dynapdf::PDF_WHITE );
		$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
		$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );

		$pdf->SetLineDashPattern( "6", 6 );
		$pdf->SetStrokeColor( $g );
		$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );

		$pdf->SetStrokeColor( $r );
		$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );

		$pdf->SetLineDashPattern( "0", 0 );
		$pdf->SetLineWidth( 2 );
		$pdf->SetStrokeColor( $b );
		$pdf->Rectangle( $sizes["Left"], $sizes["Bottom"], ( $sizes["Right"] - $sizes["Left"] ), ( $sizes["Top"] - $sizes["Bottom"] ), dynapdf::fmStroke );
	$pdf->EndPage();

	$pdf->AddOutputIntent( "/var/www/html/r3API/r3/ISOcoated_v2_eci.icc" );
	$pdf->CloseFile();
	}

// Composites the lowres-area highlight boxes over the already-rendered
// check image and rasterizes the result back to a JPG. Same history as
// dynaAdCheckOverlay() above - used to be a base64 HTTP round trip to
// dynAPI/tracker/ad_lowres.php on the old production box, ported 1:1 here.
// Note: the original file referenced bare $width/$height (not $data['width']/
// $data['height']) in the final border-rectangle and RenderPageToImage calls -
// both were actually undefined there, so that border draw was already a
// no-op and RenderPageToImage fell back to the page's own MediaBox (already
// set from the real width/height via SetBBox above). Kept as-is rather than
// "fixed", since this is a faithful behavior port, not a rewrite.
function dynaAdLowresOverlay( $sourceImg, $width, $height, $tbox, $sbox, $lowresBoxes, $destJpg ) {
	$fname = "/var/www/html/client/temp/ad_lowres-".time().".pdf";

	$pdf = new dynapdf();
	include('/var/www/html/config.inc.php');

	$pdf->CreateNewPDF( $fname );
	$pdf->SetBBox( dynapdf::pbMediaBox, 0, 0, $width, $height );

	$pdf->InitColorManagement( NULL, NULL , 1 );
	$pdf->Append();

	$gs = $pdf->CreateExtGState(array('FillAlpha' => 0.5));
	$pdf->SetExtGState($gs);

	$pdf->InsertImageEx(0, 0, $width, $height, $sourceImg, 1);

	$gs = $pdf->CreateExtGState(array('FillAlpha' => 1));
	$pdf->SetExtGState($gs);

	$gs = $pdf->CreateExtGState(array('FillAlpha' => 1));
	$pdf->SetExtGState($gs);

	$count = count($lowresBoxes);
	$r_ = 25/$count;
	$g_ = 255/$count;
	$b_ = 255/$count;

	$pdf->SetLineDashPattern( "0", 0 );
	for( $x=0; $x < count($lowresBoxes); $x++ ) {
		// array_map("floatval", ...) here is load-bearing, not cosmetic: the
		// DynaPDF extension corrupts explode()'s returned strings by the
		// second loop iteration otherwise (confirmed via a standalone repro
		// with 2+ boxes - $d[0]/$d[1] come back as binary garbage on the
		// 2nd+ pass), which crashes with "Unsupported operand types: string
		// - string" on the subtraction below. Casting to float immediately
		// avoids whatever aliasing the extension is doing with the string
		// zvals. This also explains some pre-existing 0-byte lr-*.jpg files
		// found on the real production box - the old HTTP round-trip just
		// failed silently there instead of crashing.
		$d = array_map( "floatval", explode( "_", $lowresBoxes[$x] ) );
		$r = $pdf->RGB( 230+($r_*($x+1) ), $g_*($x), $b_*($x) );
		$pdf->SetFillColor( $r );
		$pdf->Rectangle( $d[0], $d[1], ($d[2]-$d[0]), ($d[3]-$d[1]), dynapdf::fmFill );
		}

	$pdf->SetLineWidth( 1 );
	$g = $pdf->RGB( 0, 200, 0 );
	$r = $pdf->RGB( 200, 0, 0 );
	$pdf->SetStrokeColor( dynapdf::PDF_WHITE );
	$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
	$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );

	$pdf->SetLineDashPattern( "6", 6 );
	$pdf->SetStrokeColor( $g );
	$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
	$pdf->SetStrokeColor( $r );
	$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );
	$pdf->SetLineDashPattern( "0", 0 );
	$pdf->SetLineWidth( 2 );
	$pdf->SetStrokeColor( $b );
	$pdf->Rectangle( 0, 0, $borderWidth, $borderHeight, dynapdf::fmStroke );

	$pdf->EndPage();

	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);

	$pdf->SetJPEGQuality( 100 );

	$pdf->AddOutputIntent( "/var/www/html/r3API/r3/ISOcoated_v2_eci.icc" );
	$pdf->RenderPageToImage(1, $destJpg, 150, $borderWidth, $borderHeight, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfJPEG, dynapdf::ifmJPEG);

	$pdf->CloseFile();
	unlink( $fname );
	}

// Re-imports a single PDF as its own new single-page PDF (normalizes PDF
// version/structure the same way dynAPI/tracker/one_local.php already does
// per-page for the "merge into one PDF" download). This used to be a
// separate HTTP POST per file to http://{DYNAIP}/dynAPI/tracker/multi.php
// (base64-encoding the whole file into the request body) for the "zip of
// separate PDFs" download - same self-call anti-pattern as the ad-check/
// calendar ones above, ported the same way: straight to a local DynaPDF
// call, one file in, one file out, no network or base64 involved.
function dynaNormalizePdf( $from, $to ) {
	$pdf = new dynapdf();
	include('/var/www/html/config.inc.php');
	$pdf->CreateNewPDF( $to );

	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);

	if ($pdf->OpenImportFile( $from, dynapdf::ptOpen, NULL) < 0) die('Cannot open file!');
	$haveXFA = $pdf->GetInIsXFAForm();
	$isCollection = $pdf->GetInIsCollection();
	if (($destPage = $pdf->ImportPDFFile(1, 1.0, 1.0)) < 0) {};

	$pdf->CloseImportFile();
	$pdf->SetPDFVersion( 10 );

	$pdf->CloseFile();
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