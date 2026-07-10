<?php
header('Content-type: text/html; charset=UTF-8');
include( "engine/connect.php" );
include( "engine/engine.php" );

define( "SFOLDER", "/var/www/html/r3API/source");
define( "RFOLDER", "/var/www/html/r3API/rendered");
$terminal = "/var/www/html/";

$from = "rendertest.pdf";
$a_size = getPDFBox2( "Trimbox", $terminal.'/'.$from );
$a_size = $a_size["Trimbox"];
$a_size['width'] = ceil( pixel_( $a_size[2] - $a_size[0], 100 ) )+1;
$a_size['height'] = ceil( pixel_( $a_size[3] - $a_size[1], 100 ) );	

$command = './r3 -binary -mode:RENDER -left:'.$a_size[0].' -bottom:'.$a_size[1].' -right:'.$a_size[2].' -top:'.$a_size[3].' -width:'.$a_size["width"].' -height:'.$a_size["height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:ISOcoated_v2_eci.icc '.$terminal.'/'.$from.' > '.$terminal.'/rendertest.jpg';
echo $command."<br>";
$command = shell_exec('
		cd '.$terminal.'r3API//r3;
		'.$command.';
		');

var_dump( $command );

?>