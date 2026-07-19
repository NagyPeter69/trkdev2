<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );
require_once( "/var/www/html/engine/r3client.php" );

define( "SFOLDER", "/var/www/html/r3API/source");
define( "RFOLDER", "/var/www/html/r3API/rendered");
$terminal = "/var/www/html/r3API";

$file = SFOLDER."/".time()."-pdftoimg.pdf";
file_put_contents( $file, $_POST["pdf"] );
$from = $file;
$to = RFOLDER."/".$_POST["to"];
error_log( $_POST["colors"] );
$colors = json_decode( $_POST["colors"] );

$renderParams = array(
	'left' => $_POST["Left"], 'right' => $_POST["Right"],
	'bottom' => $_POST["Bottom"], 'top' => $_POST["Top"],
	'width' => $_POST["Width"], 'height' => $_POST["Height"],
	'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => 'ISOcoated_v2_eci.icc',
	);

if( $colors != "" ) {
	$color = "";
	foreach( $colors as $key => $val ) {
		if( $val == 'true' ) {
			if( strlen( $key ) > 1 )
				$color .= $key[0];
			else
				$color .= $key;
			}
		}
	$renderParams['colors'] = $color;
	}

error_log( "--------- -PDF to IMG LOG -------");
error_log( json_encode( $renderParams ) );

$imgData = r3run( 'RENDER', $renderParams, $from );
file_put_contents( $to, $imgData );

error_log( "wrote ".strlen( $imgData )." bytes to ".$to );
error_log( "---------------------------------");

$data = file_get_contents( $to );
$type = pathinfo( $to, PATHINFO_EXTENSION );
$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
$response["img"] = $base64;
$response["status"] = "success";

//error_log( "fájl? ".is_file( $to ) );
//error_log( "img: ".$response["img"] );

@unlink( $from );
@unlink( $to );
	
echo json_encode( $response );	
?>