<?php

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
	$pdf->Rectangle( coord( $kifutomm + $flap + $trim_width / 2 ) - $textWidth/2-3 , $y + $h - coord( $lineShift*$szorzo ) - coord( 6.3 ), $textWidth + coord( 2 ), coord( 5.5 ), dynapdf::fmFill );
	
	$pdf->SetStrokeColor( $pdf->RGB( 0, 0, 0 ) );
	$pdf->SetFillColor( $pdf->RGB( 0, 0, 0 ) );
	$pdf->WriteText( coord( $kifutomm + $flap + $trim_width / 2 ) - $textWidth/2, $y + $h - coord( $lineShift*$szorzo ) - coord( 6.8 ), $width." mm" );	
	}

function drawFullMeasures( $start_x, $start_y ) {
	global $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $flap, $trim_width, $trim_height, $kifutomm, $spine;
	
	//TELJES SZÉLESSÉG
	$x = $start_x - coord( $trim_width + $flap );
	$y = $start_y;
	
	$w = $trim_width * 2 + $flap * 2 + $spine;
	$h = $trim_height;

	drawWidthMeasure( $x, $y, $w, $h, 3 );
	
	//KIFUTÓVAL EGYÜTTI SZÉLESSÉG
	if( $kifutomm != "0" ) {
		$x = $start_x - coord( $trim_width + $flap + $kifutomm );
		$y = $start_y - coord( $kifutomm );
		
		$w = $trim_width * 2 + $flap * 2 + $spine + $kifutomm * 2;
		$h = $trim_height + $kifutomm * 2;
	
		drawWidthMeasure( $x, $y, $w, $h, 6.5 );
		}
		
	//MAGASSÁG
	$x = $start_x - coord( $trim_width ) + coord( $trim_width / 3 ) ;
	$y = $start_y;
	
	$w = 0;
	$h = $trim_height;

	drawHeightMeasure( $x, $y, $w, $h, 3 );	
	
	//TELJES MAGASSÁG
	if( $kifutomm != "0" ) {
		$x = $start_x - coord( $trim_width ) + coord( $trim_width / 10 ) ;
		$y = $start_y - coord( $kifutomm );
		
		$w = 0;
		$h = $trim_height + $kifutomm * 2;
	
		drawHeightMeasure( $x, $y, $w, $h, 3 );
		}
	}

function drawBleed( $x, $y, $w, $h ) {
	global $havePDF, $pdf, $arany;
	
	$pdf->SetFillColor( $pdf->RGB( 235, 235, 235 ) );
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
		$pdf->SetFillColor( $pdf->RGB( 251, 238, 244 ) );
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
	
function drawPage( $x, $y, $w, $h ) {
	global $haveArrow, $havePDF, $pdf, $arany, $fehervastagsag, $lineShift, $trim_width;
	
	$pdf->SetLineWidth( $arany );
	
	if( $havePDF ) {
		$pdf->SetStrokeColor( $pdf->RGB( 41, 163, 41 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );	
		}
	else {
		$pdf->SetFillColor( $pdf->RGB( 236, 245, 252 ) );
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
		$pdf->SetStrokeColor( $pdf->RGB( 41, 163, 41 ) );
		$pdf->Rectangle( $x, $y, $w, $h, dynapdf::fmStroke );
		}
	else {
		$pdf->SetFillColor( $pdf->RGB( 254, 252, 240 ) );
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