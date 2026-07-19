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

$renderParams = array(
	'left' => $a_size[0], 'bottom' => $a_size[1], 'right' => $a_size[2], 'top' => $a_size[3],
	'width' => $a_size["width"], 'height' => $a_size["height"],
	'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => 'ISOcoated_v2_eci.icc',
	);
echo json_encode( $renderParams )."<br>";
$imgData = r3run( 'RENDER', $renderParams, $terminal.'/'.$from );
file_put_contents( $terminal.'/rendertest.jpg', $imgData );

var_dump( strlen( $imgData )." bytes written" );

?>