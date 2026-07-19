<?php

session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../engine/connect.php' );
include_once('../engine/engine.php');

$from = "zf.pdf";
$to = "zf.jpg";
$sizes = getBBox( $from, "", "trimbox" );

$fromAbs = "/var/www/html/client/engine/r3/".$from;

for( $i = 65; $i < 85; $i++ ) {
	$sizes["Width"] = pixel_( $sizes["Width"], $i );
	$sizes["Height"] = pixel_( $sizes["Height"], $i );
	$to = "zf_".(round($sizes["Height"])).".jpg";
	$renderParams = array(
		'left' => $sizes["Left"], 'right' => $sizes["Right"],
		'bottom' => $sizes["Bottom"], 'top' => $sizes["Top"],
		'width' => $sizes["Width"], 'height' => $sizes["Height"],
		'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => 'ISOcoated_v2_eci.icc',
		);
	$command = json_encode( $renderParams )." ".$fromAbs." -> ".$to;
	error_log( $command );

	$imgData = r3run( 'RENDER', $renderParams, $fromAbs );
	file_put_contents( "/var/www/html/client/engine/r3/".$to, $imgData );

	error_log( "wrote ".strlen( $imgData )." bytes to ".$to );
	}

?>