<?php

require_once( "/var/www/html/engine/r3client.php" );

$havePDF = true;

$maxwidth = 0;
$maxheight = 0;

$trim_width = $data["width"];
$trim_height = $data["height"];
$flap = $data["flap"];

$kifutomm = $data["bleed"];
$kifuto = coord( $kifutomm );
$spine = $data["spine"];

include( "drawsettings.php" );

if( empty( $filename ) ) {
	$havePDF = false;
		
	$maxwidth = $kifutomm + $flap + $trim_width + $spine + $trim_width + $flap + $kifutomm;
	$maxheight = $kifutomm + $trim_height + $kifutomm;
	}

if( $type == "7" ) {
	$maxwidth -= $trim_width;
	}

$fname = "rendered/teszt".time().".pdf" ;

$pdf = new dynapdf();
include('../../config.inc.php');

$pdf->CreateNewPDF( $fname );			

$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage | dynapdf::ifDocInfo);
$pdf->SetImportFlags2(dynapdf::if2UseProxy);
$pdf->SetPDFVersion( 10 );	
$pdf->SetPageCoords(dynapdf::pcTopDown);

if( $havePDF ) {
	$pdf->OpenImportFile( $filename , dynapdf::ptOpen, NULL);	
	}

$pages = 1;
if( $data["footer"] == "2" ) {
	$pages = $pdf->GetInPageCount();
	}

if( $pages <= 1 ) {	
	$pdf->AddFontSearchPath( "../fonts" , true );
	
	$pdf->SetLineWidth( $lineHeight );
	
	$pdf->Append();
		$pdf->SetFont('Helvetica', dynapdf::fsRegular, $betumeret, false, dynapdf::cp1252);
	
		if( $havePDF ) {
			$pdf->ImportPageEx(1, 1.0, 1.0);
			$pdf->ImportCatalogObjects();
			}
		
		else {
			if( $maxwidth > 0 )	$pdf->SetPageWidth( coord( $maxwidth ) );
			if( $maxheight > 0 ) $pdf->SetPageHeight( coord( $maxheight ) );
			}
		
		if( !$markDraw ) {
			if( !$fix ) {
				
				$bleed = $pdf->GetBBox( dynapdf::pbBleedBox );
				$trim = $pdf->GetBBox( dynapdf::pbTrimBox );
				
				$pdf->SetStrokeColor( $pdf->CMYK( 255, 255, 255, 153 ) );
				$pdf->Rectangle( $trim["Left"], $trim["Top"] , $trim["Right"] - $trim["Left"], $trim["Bottom"] - $trim["Top"], dynapdf::fmStroke );
			
				$pdf->SetStrokeColor( $pdf->CMYK( 255, 255, 255, 255 ) );
				$pdf->Rectangle( $bleed["Left"], $bleed["Top"] , $bleed["Right"] - $bleed["Left"], $bleed["Bottom"] - $bleed["Top"], dynapdf::fmStroke );
				
				
				$crop = $pdf->GetBBox( dynapdf::pbMediaBox );		
				$crop = $pdf->GetBBox( dynapdf::pbCropBox );
				$x = $pdf->GetPageWidth() / 2;
				$x = $x - coord( $spine / 2 );
				
				if( $crop["Left"] != 0 ) {
					$x += $crop["Left"];
					}
				$y = $pdf->GetPageHeight() / 2;
				$y = $y - coord( $trim_height / 2 );
				
				$start_x = $x;
				$start_y = $y;
		
				//KIFUTÓ
				if( !$havePDF ) {	
					drawBleed( $crop["Left"], $crop["Bottom"], $maxwidth, $maxheight );
					}
			
				//OLDALAK
				$x = $start_x - coord( $trim_width);
				if( $type == "7" ) {
					$x = $start_x - coord( $trim_width / 2);
					}
					
				drawPage( $x, $y, coord( $trim_width ), coord( $trim_height ) );
				if( $type != "7" ) {
					$x = $start_x + coord( $spine );
					drawPage( $x, $y, coord( $trim_width ), coord( $trim_height ) );
					}
	
				//NYITÓBIG
				$allowed = array( "0", "1", "2" );
				if( in_array( $type, $allowed ) ) {
					$hely = 5;
					if( $type == "2" ) {
						$hely = 11;
						}
						
					$pdf->SetLineDashPattern( "2 3", 11);
					$x = $start_x - coord( $hely );
					drawBig( $x, $y, 0.1, coord( $trim_height ) );
					
					$x = $start_x + coord( $spine + $hely );
					drawBig( $x, $y, 0.1, coord( $trim_height ) );
					$pdf->SetLineDashPattern( NULL, 0 );
					}
			
				//GERINC
				if( $type != "7" ) {
					$x = $start_x;
					$y = $start_y;
					drawSpine( $x, $y, coord( $spine ), coord( $trim_height ) );
					}
			
				//FÜLEK
				if( $type != "7" ) {
					if( $flap > 0 ) {
						$x = $start_x - coord( $trim_width + $flap );
						drawFlap( $x, $y, coord( $flap ), coord( $trim_height ) );		
				
						$x = $start_x + coord( $trim_width + $spine );
						drawFlap( $x, $y, coord( $flap ), coord( $trim_height ) );	
						}
					}
				
				//KIFUTÓ	
				$x = $start_x - coord( $trim_width + $flap + $kifutomm );		
				$y = $start_y - coord( $kifutomm );
				$w = coord( $trim_width + $trim_width + $flap + $flap + $spine + $kifutomm + $kifutomm );
				$h = coord( $trim_height + $kifutomm + $kifutomm );
				if( $type == "7" ) {
					$x = $start_x - coord( $trim_width / 2 + $flap + $kifutomm );
					$w = coord( $trim_width + $flap + $flap + $spine + $kifutomm + $kifutomm );
					}
				
				$pdf->SetStrokeColor( $pdf->CMYK( 25, 76, 255, 255 ) );
				$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );
				
				//EGYÉB MÉRETEZÉSEK
				if( $haveArrow ) {
					drawFullMeasures( $start_x, $start_y );
					}
				}
			}
	
	if( $markDraw ) {	
		drawMarks();
		}
	
	//$pdf->AddOutputIntent("/var/www/html/dynAPI/ISOcoated_v2_eci.icc");
	//$pdf->SetJPEGQuality(1000);
	
	
	// R3 IMG Render
	
	$img = "teszt_".time().".jpg";
	$crop = $pdf->GetBBox( dynapdf::pbMediaBox );
	$pdf->EndPage();
	$pdf->CloseImportFile();
	
	$pdf->CloseFile();
	unset($pdf);
	
	$from = "/var/www/html/dynAPI/op/".$fname;
	$to = "/var/www/html/dynAPI/op/rendered/".$img;
	
	$w = round( ( $crop["Right"] - $crop["Left"] ) * 100 / 72 );
	$h = round( ( $crop["Top"] - $crop["Bottom"] ) * 100 / 72 );
	
	$renderParams = array(
		'left' => $crop["Left"], 'right' => $crop["Right"],
		'bottom' => $crop["Bottom"], 'top' => $crop["Top"],
		'width' => $w, 'height' => $h,
		'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => 'ISOcoated_v2_eci.icc',
		);
	error_log( json_encode( $renderParams ) );
	$imgData = r3run( 'RENDER', $renderParams, $from );
	file_put_contents( $to, $imgData );
	error_log( "wrote ".strlen( $imgData )." bytes to ".$to );
	//R3 IMG Render vége
	//$pdf->RenderPageToImage(1, "./rendered/".$img, 200, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);	
	
	$response = array();
	if( $download ) {
		$path = $fname;
		$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = 'data:application/' . $type . ';base64,' . base64_encode($data);
		$response["pdf"] = $base64;
		}
		
	if( $markDraw ) {
		$path = $fname;
		$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = 'data:application/' . $type . ';base64,' . base64_encode($data);
		$response["pdf"] = $base64;	
		}
	
	$path = "./rendered/".$img;
	$type = pathinfo($path, PATHINFO_EXTENSION);
	$data = file_get_contents($path);
	$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		
	$response["img"] = $base64;	
	$response["status"] = "success";
	error_log( $path );
	list($width, $height) = getimagesize( $path );
	if( $width > $height)
	    $response["orient"] = "landscape";
	else
	    $response["orient"] = "portrait";
	
	//unlink( "./rendered/".$img );
	//unlink( $fname );
	if( !empty( $filename ) ) {
		unlink( $filename );
		}
	}

else {
	$response["pages"] = $pages;
	$response["status"] = "multiple";
	for( $i = 1; $i <= $pages; $i++ ) {
		$pdf->Append();
			$pdf->ImportPageEx($i, 1.0, 1.0);
			$pdf->ImportCatalogObjects();
		$pdf->EndPage();
		
		$path = "rendered/teszt-".time()."_page-".$i.".png";
		$pdf->RenderPageToImage( $i, $path, 30, 0, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);
		$type = pathinfo($path, PATHINFO_EXTENSION);
		$data = file_get_contents($path);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
		$response["img-".$i] = $base64;
		
		unlink( $path );
		}
		
	//unlink( $fname );
	if( !empty( $_FILES["file"]["name"][0]["file"] ) ) {
		//unlink( $_FILES["file"]["name"][0]["file"] );
		}
	}
	
echo json_encode( $response );

?>