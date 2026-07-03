<?php

function drawMarks() {
	global $type, $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $flap, $trim_width, $trim_height, $kifutomm, $spine, $vjelSize, $vjelHossz, $dif_left, $dif_right, $dif_bottom, $dif_top;
	
	$pdf->SetColorSpace( dynapdf::csDeviceCMYK );
	$pdf->SetFillColorSpace( dynapdf::csDeviceCMYK );
	$pdf->SetStrokeColorSpace( dynapdf::csDeviceCMYK );
	
	$pdf->SetLineWidth( $vjelSize );
	
	$startx = $pdf->GetPageWidth() / 2 - $dif_left / 2;
	$top = $pdf->GetPageHeight() / 2 - coord( $trim_height / 2 + $kifutomm + $vjelHossz );
	$bottom = $pdf->GetPageHeight() / 2 + coord( $trim_height / 2 + $kifutomm + $vjelHossz );
	$pdf->SetStrokeColor( $pdf->CMYK( 255, 255, 255, 255 ) );
	$pdf->SetFillColor( $pdf->CMYK( 255, 255, 255, 255 ) );
	
	//Gerinc Vágójel
	if( $type != "7" ) {
		$x = $startx - coord( $spine / 2 );
		$pdf->Rectangle( $x, $top, $vjelSize, coord( $vjelHossz ), dynapdf::fmFill );
		$pdf->Rectangle( $x, $bottom, $vjelSize, -coord( $vjelHossz ), dynapdf::fmFill );
		$x = $startx + coord( $spine / 2 );
		$pdf->Rectangle( $x, $top, $vjelSize, coord( $vjelHossz ), dynapdf::fmFill );
		$pdf->Rectangle( $x, $bottom, $vjelSize, -coord( $vjelHossz ), dynapdf::fmFill );
		}
	
	//Oldalak vágójel
	if( $type == "7" ) {
		$x = $startx - coord( $trim_width / 2 );
		}
	else {
		$x = $startx - coord( $spine / 2 + $trim_width );
		}
	$pdf->Rectangle( $x, $top, $vjelSize, coord( $vjelHossz ), dynapdf::fmFill );
	$pdf->Rectangle( $x, $bottom, $vjelSize, -coord( $vjelHossz ), dynapdf::fmFill );	
	
	if( $type == "7" ) {
		$x = $startx - coord( $trim_width / 2 + $kifutomm + $vjelHossz );
		}
	else {
		$x = $startx - coord( $spine / 2 + $trim_width + $kifutomm + $vjelHossz + $flap );
		}
	$y = $pdf->GetPageHeight() / 2 - coord( $trim_height / 2 );
	$pdf->Rectangle( $x, $y, coord( $vjelHossz ), $vjelSize, dynapdf::fmFill );
	
	if( $type == "7" ) {
		$x = $startx + coord( $trim_width / 2 + $kifutomm + $vjelHossz );
		}
	else {
		$x = $startx + coord( $spine / 2 + $trim_width + $kifutomm + $vjelHossz + $flap );
		}
	$pdf->Rectangle( $x, $y, -coord( $vjelHossz ), $vjelSize, dynapdf::fmFill );
	
	$y = $pdf->GetPageHeight() / 2 + coord( $trim_height / 2 );
	if( $type == "7" ) {
		$x = $startx - coord( $trim_width / 2 + $kifutomm + $vjelHossz );
		}
	else {
		$x = $startx - coord( $spine / 2 + $trim_width + $kifutomm + $vjelHossz + $flap );
		}
		
	$pdf->Rectangle( $x, $y, coord( $vjelHossz ), $vjelSize, dynapdf::fmFill );

	if( $type == "7" ) {
		$x = $startx + coord( $trim_width / 2 + $kifutomm + $vjelHossz );
		}
	else {
		$x = $startx + coord( $spine / 2 + $trim_width + $kifutomm + $vjelHossz + $flap );
		}
	$pdf->Rectangle( $x, $y, -coord( $vjelHossz ), $vjelSize, dynapdf::fmFill );

	
	if( $type == "7" ) {
		$x = $startx + coord( $trim_width / 2 );
		}
	else {
		$x = $startx + coord( $spine / 2 + $trim_width );
		}
		
	$pdf->Rectangle( $x, $top, $vjelSize, coord( $vjelHossz ), dynapdf::fmFill );
	$pdf->Rectangle( $x, $bottom, $vjelSize, -coord( $vjelHossz ), dynapdf::fmFill );
	
	if( $type != "7" ) {
		if( $flap > 0 ) {
			$x = $startx - coord( $spine / 2 + $trim_width + $flap );
			$pdf->Rectangle( $x, $top, $vjelSize, coord( $vjelHossz ), dynapdf::fmFill );
			$pdf->Rectangle( $x, $bottom, $vjelSize, -coord( $vjelHossz ), dynapdf::fmFill );

			$x = $startx + coord( $spine / 2 + $trim_width + $flap );
			$pdf->Rectangle( $x, $top, $vjelSize, coord( $vjelHossz ), dynapdf::fmFill );
			$pdf->Rectangle( $x, $bottom, $vjelSize, -coord( $vjelHossz ), dynapdf::fmFill );
			}
		}

		$left = $pdf->GetPageWidth() / 2 - coord( $spine / 2 + $trim_width + $kifutomm + $flap );
		$right = $pdf->GetPageWidth() / 2 + coord( $spine / 2 + $trim_width + $kifutomm + $flap );
		$bottom = $pdf->GetPageHeight() / 2 - coord( $trim_height / 2 + $kifutomm );
		$top = $pdf->GetPageHeight() / 2 + coord( $trim_height / 2 + $kifutomm );
		
		$crop = $pdf->GetBBox( dynapdf::pbCropBox );
		
		$dif_left = abs( $left - $crop["Left"] );
		$dif_right = abs( $right - $crop["Right"] );
		$dif_bottom = abs( $bottom - $crop["Bottom"] );
		$dif_top = abs( $top - $crop["Top"] );
		
		$distance = 14.17322835;
		
		if( $dif_left != $distance ) { $left = $left - $distance; }
		if( $dif_bottom != $distance ) { $bottom = $bottom - $distance; }
		if( $dif_top != $distance ) { $top = $top + $distance; }
		if( $dif_right != $distance ) { $right = $right + $distance; }
		
		/*$x_tolas = 0;	
		if( $left < 0 ) {
			$right += abs( $left );
			$left = 0;
			$x_tolas = abs( $left );
			}	
		
		$y_tolas = 0;
		if( $bottom < 0 ) {
			$top += abs( $bottom );
			$bottom = 0;
			$y_tolas = abs( $bottom );
			}*/		
			
		$pdf->SetBBox( dynapdf::pbMediaBox, $left, $bottom, $right, $top );
		$pdf->SetBBox( dynapdf::pbCropBox, $left, $bottom, $right, $top );
		
		/*$box = $pdf->GetBBox( dynapdf::pbBleedBox );
		$left = $box["Left"] + abs( $x_tolas );
		$right = $box["Right"] + abs( $x_tolas );
		$bottom = $box["Bottom"] + abs( $y_tolas );
		$top = $box["Top"] + abs( $y_tolas );	
		$pdf->SetBBox( dynapdf::pbBleedBox, $left, $bottom, $right, $top );

		$box = $pdf->GetBBox( dynapdf::pbTrimBox );
		$left = $box["Left"] + abs( $x_tolas );
		$right = $box["Right"] + abs( $x_tolas );
		$bottom = $box["Bottom"] + abs( $y_tolas );
		$top = $box["Top"] + abs( $y_tolas );	
		$pdf->SetBBox( dynapdf::pbTrimBox, $left, $bottom, $right, $top );*/

	}

function drawHeightMeasure( $x, $y, $w, $h, $szorzo ) {
	global $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $flap, $trim_width, $trim_height, $kifutomm, $spine;

	$height = $h;
	$textWidth = $pdf->GetTextWidth( $h." mm" );
	$h = coord( $h );
	$w = coord( $w );
	
	$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
	$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );

	$pdf->SetLineWidth( $arany );
	$pdf->Rectangle( $x-$fehervastagsag*2, $y + coord(2), 0 + $fehervastagsag*2, $h - coord( 4 ), dynapdf::fmFill );

	DrawArrow( $x, $y, "up" );
	DrawArrow( $x + $w, $y + $h, "down" );

	$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
	$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );

	$pdf->Rectangle( $x, $y, 0, $h - coord( 2 ), dynapdf::fmStroke );
	
	$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
	$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );
	$pdf->Rectangle( $x + 3, $y + $h - coord( $height / 2 ) - 13, $textWidth+3, 13, dynapdf::fmFill );
	$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
	$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
		
	$pdf->WriteText( $x + 5, $y + $h - coord( $height / 2 ) - 16, $height." mm" );		
	}

function drawWidthMeasure( $x, $y, $w, $h, $szorzo ) {
	global $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $flap, $trim_width, $trim_height, $kifutomm, $spine;
	
	$width = $w;
	$textWidth = $pdf->GetTextWidth( $width." mm" );
	$h = coord( $h );
	$w = coord( $w );
	
	$pdf->SetLineWidth( $arany );
	$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
	$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );

	$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift*$szorzo ) - $fehervastagsag , $w - 2, 0 + $fehervastagsag*2, dynapdf::fmFill );
	
	DrawArrow( $x, $y + $h - coord( $lineShift*$szorzo ), "left" );
	DrawArrow( $x + $w, $y + $h - coord( $lineShift*$szorzo ), "right" );
	
	$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift*$szorzo ), $w - 2, 0, dynapdf::fmStroke );	

	$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
	$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );	
	$pdf->Rectangle( $x + coord( $trim_width / 2 ) - $textWidth/2-3 , $y + $h - coord( $lineShift*$szorzo ) - coord( 6.3 ), $textWidth + coord( 2 ), coord( 5.5 ), dynapdf::fmFill );
	
	$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
	$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
	$pdf->WriteText( $x + coord( $trim_width / 2 ) - $textWidth/2, $y + $h - coord( $lineShift*$szorzo ) - coord( 6.8 ), $width." mm" );	
	}

function drawFullMeasures( $start_x, $start_y ) {
	global $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $flap, $trim_width, $trim_height, $kifutomm, $spine, $type;
	
	//TELJES SZÉLESSÉG
	if( $type != "7" ) {
		$x = $start_x - coord( $trim_width + $flap );
		$y = $start_y;
		
		$w = $trim_width * 2 + $flap * 2 + $spine;
		$h = $trim_height;
	
		drawWidthMeasure( $x, $y, $w, $h, 3 );
		}
	
	//KIFUTÓVAL SZÉLESSÉG
	if( $kifutomm != "0" ) {
		$x = $start_x - coord( $trim_width + $flap + $kifutomm );
		$y = $start_y - coord( $kifutomm - ( $trim_height / 2 ) );
		
		$w = coord($kifutomm);
		if( $type == "7" ) {
			$x = $start_x - coord( $trim_width / 2 + $kifutomm );
			$w -= $trim_width;
			}
		$h = $trim_height + $kifutomm * 2;
	
	
		$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
		$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );
		$textWidth = $pdf->GetTextWidth( $kifutomm." mm" );
		
		if( $kifutomm <= 17 ) {	
			$pdf->Rectangle( $x, $y + $h - coord( $lineShift*2 ) - $fehervastagsag , $w + $textWidth + coord( 6 ), 0 + $fehervastagsag*2, dynapdf::fmFill );		
			
			DrawArrow( $x, $y + $h - coord( $lineShift*2 ), "right" );
			DrawArrow( $x + $w, $y + $h - coord( $lineShift*2 ), "left" );
				
			$pdf->Rectangle( $x, $y + $h - coord( $lineShift*2 ), $w + $textWidth + coord( 6 ), 0, dynapdf::fmStroke );
			
			$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
			$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );	
			$pdf->Rectangle( $x + $w + coord(3), $y + $h - coord( $lineShift*2 ) - coord( 6.3 ), $textWidth + coord( 2 ), coord( 5.5 ), dynapdf::fmFill );
			
			$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
			$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
			$pdf->WriteText( $x + $w + coord(4), $y + $h - coord( $lineShift*2 ) - coord( 7 ), $kifutomm." mm" );
			}
			
		else {
			$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift*2 ) - $fehervastagsag , $w - 2, 0 + $fehervastagsag*2, dynapdf::fmFill );
			
			DrawArrow( $x, $y + $h - coord( $lineShift*2 ), "left" );
			DrawArrow( $x + $w, $y + $h - coord( $lineShift*2 ), "right" );
			
			$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift*2 ), $w - 2, 0, dynapdf::fmStroke );
	
			$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
			$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );	
			$pdf->Rectangle( $x + coord( $kifutomm / 2 ) - $textWidth/2-3 , $y + $h - coord( $lineShift*2 ) - coord( 6.3 ), $textWidth + coord( 2 ), coord( 5.5 ), dynapdf::fmFill );
			
			$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
			$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
			$pdf->WriteText( $x + coord( $kifutomm / 2 ) - $textWidth/2, $y + $h - coord( $lineShift*2 ) - coord( 6.8 ), $kifutomm." mm" );
			}
		}
		
	//MAGASSÁG
	$x = $start_x - coord( $trim_width ) + coord( $trim_width / 3 ) ;
	if( $type == "7" ) {
		$x = $start_x - coord( $trim_width / 2 ) + coord( $trim_width / 3 ) ;
		}
	$y = $start_y;
	
	$w = 0;
	$h = $trim_height;

	drawHeightMeasure( $x, $y, $w, $h, 3 );	
	
	//TELJES MAGASSÁG
	if( $kifutomm != "0" ) {
		$x = $start_x - coord( $trim_width ) + coord( $trim_width / 10 ) ;
		if( $type == "7" ) {
			$x = $start_x - coord( $trim_width/2 ) + coord( $trim_width / 10 ) ;
			}
		$y = $start_y - coord( $kifutomm );
		
		$w = 0;
		$h = $trim_height + $kifutomm * 2;
	
		drawHeightMeasure( $x, $y, $w, $h, 3 );
		}
	}

function drawBleed( $x, $y, $w, $h ) {
	global $havePDF, $pdf, $arany;
	
	$pdf->SetFillColor( $pdf->CMYK( 234, 242, 255, 255 ) );
	$pdf->Rectangle( $x, $y, coord($w), coord($h), dynapdf::fmFillNoClose );
	$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
	$pdf->Rectangle( $x, $y, coord($w), coord($h), dynapdf::fmStroke );
	}

function drawFlap( $x, $y, $w, $h ) {
	global $haveArrow, $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $flap;
	
	$pdf->SetLineWidth( $arany );
	if( $havePDF ) {
		$pdf->SetStrokeColor( $pdf->RGB( 41, 163, 41 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );
		}
	else {
		$pdf->SetFillColor( $pdf->CMYK( 255, 217, 204, 255 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmFillNoClose );
		$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );		
		}
	
	if( $haveArrow ) {
		$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
		$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );
		$textWidth = $pdf->GetTextWidth( $flap." mm" );	
	
		$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift ) - $fehervastagsag , $w - 2, 0 + $fehervastagsag*2, dynapdf::fmFill );
		
		DrawArrow( $x, $y + $h - coord( $lineShift ), "left" );
		DrawArrow( $x + $w, $y + $h - coord( $lineShift ), "right" );
		
		$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift ), $w - 2, 0, dynapdf::fmStroke );
	
		$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
		$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );	
		$pdf->Rectangle( $x + coord( $flap / 2 ) - $textWidth/2-3 , $y + $h - coord( $lineShift ) - coord( 6.3 ), $textWidth + coord( 2 ), coord( 5.5 ), dynapdf::fmFill );
		
		$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
		$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
		$pdf->WriteText( $x + coord( $flap / 2 ) - $textWidth/2, $y + $h - coord( $lineShift ) - coord( 6.8 ), $flap." mm" );		
		}
	}

function drawBig( $x, $y, $w, $h ) {
	global $haveArrow, $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $trim_width;
	
	$pdf->SetLineWidth( $arany );
	
	if( $havePDF ) {
		$pdf->SetStrokeColor( $pdf->CMYK( 255, 255, 255, 0 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );	
		}
	else {
		$pdf->SetFillColor( $pdf->RGB( 236, 245, 252 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmFillNoClose );
		$pdf->SetStrokeColor( $pdf->CMYK( 255, 255, 255, 0 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );		
		}	
	}
	
function drawPage( $x, $y, $w, $h ) {
	global $haveArrow, $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $trim_width;
	
	$pdf->SetLineWidth( $arany );
	
	if( $havePDF ) {
		$pdf->SetStrokeColor( $pdf->CMYK( 255, 255, 255, 0 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );	
		}
	else {
		$pdf->SetFillColor( $pdf->CMYK( 255, 242, 217, 255 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmFillNoClose );
		$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );		
		}
		
	if( $haveArrow ) {
		$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
		$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );
		$textWidth = $pdf->GetTextWidth( $trim_width." mm" );	
	
		$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift ) - $fehervastagsag , $w - 2, 0 + $fehervastagsag*2, dynapdf::fmFill );
		
		DrawArrow( $x, $y + $h - coord( $lineShift ), "left" );
		DrawArrow( $x + $w, $y + $h - coord( $lineShift ), "right" );
		
		$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift ), $w - 2, 0, dynapdf::fmStroke );
	
		$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
		$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );	
		$pdf->Rectangle( $x + coord( $trim_width / 2 ) - $textWidth/2-3 , $y + $h - coord( $lineShift ) - coord( 6.3 ), $textWidth + coord( 2 ), coord( 5.5 ), dynapdf::fmFill );
		
		$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
		$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
		$pdf->WriteText( $x + coord( $trim_width / 2 ) - $textWidth/2, $y + $h - coord( $lineShift ) - coord( 6.8 ), $trim_width." mm" );
		}
	}

function drawSpine( $x, $y, $w, $h ) {
	global $haveArrow, $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $spine;
	
	$pdf->SetLineWidth( $arany );
	if( $havePDF ) {
		$pdf->SetStrokeColor( $pdf->CMYK( 255, 255, 255, 0 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );
		}
	else {
		$pdf->SetFillColor( $pdf->CMYK( 255, 204, 178, 255 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmFillNoClose );
		$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );
		}
	
	if( $haveArrow && $spine > 0 ) {
		$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
		$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );
		$textWidth = $pdf->GetTextWidth( $spine." mm" );
		
		if( $spine <= 17 ) {	
			$pdf->Rectangle( $x, $y + $h - coord( $lineShift*2 ) - $fehervastagsag , $w + $textWidth + coord( 6 ), 0 + $fehervastagsag*2, dynapdf::fmFill );		
			
			DrawArrow( $x, $y + $h - coord( $lineShift*2 ), "right" );
			DrawArrow( $x + $w, $y + $h - coord( $lineShift*2 ), "left" );
				
			$pdf->Rectangle( $x, $y + $h - coord( $lineShift*2 ), $w + $textWidth + coord( 6 ), 0, dynapdf::fmStroke );
			
			$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
			$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );	
			$pdf->Rectangle( $x + $w + coord(3), $y + $h - coord( $lineShift*2 ) - coord( 6.3 ), $textWidth + coord( 2 ), coord( 5.5 ), dynapdf::fmFill );
			
			$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
			$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
			$pdf->WriteText( $x + $w + coord(4), $y + $h - coord( $lineShift*2 ) - coord( 7 ), $spine." mm" );
			}
			
		else {
			$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift*2 ) - $fehervastagsag , $w - 2, 0 + $fehervastagsag*2, dynapdf::fmFill );
			
			DrawArrow( $x, $y + $h - coord( $lineShift*2 ), "left" );
			DrawArrow( $x + $w, $y + $h - coord( $lineShift*2 ), "right" );
			
			$pdf->Rectangle( $x + 1, $y + $h - coord( $lineShift*2 ), $w - 2, 0, dynapdf::fmStroke );
	
			$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
			$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );	
			$pdf->Rectangle( $x + coord( $spine / 2 ) - $textWidth/2-3 , $y + $h - coord( $lineShift*2 ) - coord( 6.3 ), $textWidth + coord( 2 ), coord( 5.5 ), dynapdf::fmFill );
			
			$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
			$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
			$pdf->WriteText( $x + coord( $spine / 2 ) - $textWidth/2, $y + $h - coord( $lineShift*2 ) - coord( 6.8 ), $spine." mm" );
			}		
		}
	}
	
function DrawArrow( $x, $y, $direction ) {
	global $havePDF, $pdf, $fehervastagsag, $arany, $nyilmagassag, $nyilszelesseg, $nyileltolas, $fehernyilmagassag, $fehernyilszelesseg, $fehernyileltolas;

	$pdf->SetLineWidth( $arany );
	$pdf->SetStrokeColor( $pdf->RGB( 255, 255, 255 ) );
	$pdf->SetFillColor( $pdf->RGB( 255, 255, 255 ) );	
	
	switch( $direction ) {
		case 'left':
			$pdf->Triangle( $x+$fehernyileltolas-$fehervastagsag, $y, $x+$fehernyilmagassag, $y-$fehernyilszelesseg-$fehervastagsag, $x+$fehernyilmagassag, $y+$fehernyilszelesseg+$fehervastagsag, dynapdf::fmFillNoClose );
			break;
		
		case 'right':
			$pdf->Triangle( $x-$fehernyileltolas+$fehervastagsag, $y, $x-$fehernyilmagassag, $y-$fehernyilszelesseg-$fehervastagsag, $x-$fehernyilmagassag, $y+$fehernyilszelesseg+$fehervastagsag, dynapdf::fmFillNoClose );		
			break;
			
		case 'up':
			$pdf->Triangle( $x, $y+$fehernyileltolas+$fehervastagsag, $x-$fehernyilszelesseg-$fehervastagsag, $y+$fehernyilmagassag, $x+$fehernyilszelesseg, $y+$fehernyilmagassag+$fehervastagsag, dynapdf::fmFillNoClose );
			break;
			
		case 'down':
			$pdf->Triangle( $x, $y-$fehernyileltolas, $x-$fehernyilszelesseg, $y-$fehernyilmagassag, $x+$fehernyilszelesseg,  $y-$fehernyilmagassag, dynapdf::fmFillNoClose );
			break;
		}

	$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
	$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
	
	switch( $direction ) {
		case 'left':
			$pdf->Triangle( $x+$nyileltolas, $y, $x+$nyilmagassag, $y-$nyilszelesseg, $x+$nyilmagassag, $y+$nyilszelesseg, dynapdf::fmFillNoClose );
			break;
		
		case 'right':
			$pdf->Triangle( $x-$nyileltolas, $y, $x-$nyilmagassag, $y-$nyilszelesseg, $x-$nyilmagassag, $y+$nyilszelesseg, dynapdf::fmFillNoClose );		
			break;
			
		case 'up':
			$pdf->Triangle( $x, $y+$nyileltolas, $x-$nyilszelesseg, $y+$nyilmagassag, $x+$nyilszelesseg, $y+$nyilmagassag, dynapdf::fmFillNoClose );
			break;
			
		case 'down':
			$pdf->Triangle( $x, $y-$nyileltolas, $x-$nyilszelesseg, $y-$nyilmagassag, $x+$nyilszelesseg,  $y-$nyilmagassag, dynapdf::fmFillNoClose );
			break;
		}
	}
	
?>