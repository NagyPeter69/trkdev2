<?php

require_once( "/var/www/html/engine/r3client.php" );

function fontcolor( $hex ) {
	if( strpos( $hex, "rgb" ) !== false ) {
		$c = getBetween( $hex, "(", ")" );
		$c = explode( ",", $c );
		
		$hex = sprintf("#%02x%02x%02x", $c[0], $c[1], $c[2] );
		}
		
	$hex = str_replace('#', '', $hex);
	if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    	}
    	
    $color_parts = str_split($hex, 2);
        
    if ( ( hexdec($color_parts[0])*0.299 + hexdec($color_parts[1])*0.587 + hexdec($color_parts[2])*0.114 ) > 186  ) {
	    return "#000";
    	}
    else {
	    return "#FFF";
    	}
	}

function adjustBrightness($hex, $steps) {
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);

    foreach ($color_parts as $color) {
        $color   = hexdec($color); // Convert to decimal
        $color   = max(0,min(255,$color + $steps)); // Adjust color
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }

    return $return;
}	
	
function hexToRgb($hex, $alpha = false) {
   $hex      = str_replace('#', '', $hex);
   $length   = strlen($hex);
   $rgb['r'] = hexdec($length == 6 ? substr($hex, 0, 2) : ($length == 3 ? str_repeat(substr($hex, 0, 1), 2) : 0));
   $rgb['g'] = hexdec($length == 6 ? substr($hex, 2, 2) : ($length == 3 ? str_repeat(substr($hex, 1, 1), 2) : 0));
   $rgb['b'] = hexdec($length == 6 ? substr($hex, 4, 2) : ($length == 3 ? str_repeat(substr($hex, 2, 1), 2) : 0));
   if ( $alpha ) {
      $rgb['a'] = $alpha;
   }
   return $rgb;
}

function strpos_arr($haystack, $needle) {
    if(!is_array($needle)) $needle = array($needle);
    foreach($needle as $what) {
        if(($pos = strpos($haystack, $what))!==false) return $pos;
    }
    return false;
}
	
function getPDFBox( $box, $file ) {
	$data = array();
	$boxes = explode( " ", $box );
	
	$command = r3run( 'GETDATA', array(), $file );
	
	$command = explode( "\n", $command );
	for( $i = 0; $i < 4; $i++ ) {
		$temp = explode( " = ", $command[$i] );
		if( in_array( ucfirst( strtolower( $temp[0] ) ), $boxes ) ) {
			$temp[1] = explode( " ", $temp[1] );
			for( $y = 1; $y < 5; $y++ ) {
				$data[ ucfirst( strtolower( $temp[0] ) ) ][] = $temp[1][$y];
				}			
			}
		}
	
	return $data;
	}
	
function coord( $mm ) {
	return ( ( 2.83464567 * $mm ) );
	}
	
?>