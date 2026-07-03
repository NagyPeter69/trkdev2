<?php

session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../engine/connect.php' );
include_once('../engine/engine.php');

$from = "zf.pdf";
$to = "zf.jpg";
$sizes = getBBox( $from, "", "trimbox" );

for( $i = 65; $i < 85; $i++ ) {
	$sizes["Width"] = pixel_( $sizes["Width"], $i );
	$sizes["Height"] = pixel_( $sizes["Height"], $i );
	$to = "zf_".(round($sizes["Height"])).".jpg";
	$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].'  -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:ISOcoated_v2_eci.icc '.$from.' $@ >'.$to.' 2>&1';
	error_log( $command );
	$command = shell_exec('
		cd engine/r3 2>&1;
		'.$command.';
		');		
	
	error_log( $command );
	}

?>