<?php

if( !isset( $_POST['file'] ) ) die();
session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../engine/connect.php' );
include_once('../engine/engine.php');

$zoom = $_GET['zoom'];
$colors = $_POST['colors'];
$cbox = $_POST['cBox'];
$phpPath = "engine/r3";
$terminalPath = "/var/www/intra/client";
$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );

function logToFile( $file, $text ) {
	$handle = fopen( $file, 'a+');
	if( $handle === false ) {
		return false;
		}
	
	if( fwrite( $handle, $text . "\n" ) === false ) {
		return false;
		}
	fclose( $handle );
	}

function pixel_( $num, $_zoom = '' ) {
	global $zoom;
	if( $_zoom == '' ) $_zoom = $zoom;
	
	return $num * $_zoom / 72;
	}

function point_( $num, $_zoom = '' ) {
	global $zoom;
	if( $_zoom == '' ) $_zoom = $zoom;
	
	return $num * 72 / $_zoom;
	}

function PDFtoImage( $sizes, $to, $color = "" ) {
	global $from, $colors;

	$color = "";
	foreach( $colors as $key => $val ) {
		if( $val == 'true' ) {
       if( strlen( $key ) > 1 )
         $color .= $key[0];
       else 
         $color .= $key;
			}
		}

	if( empty( $color ) ) {
		$color = "PSO_MFC_Paper_bas";
		}
	
	error_log( "R3 DEBUG" );
	error_log( './r3 -binary -mode:RENDER -left:'.$sizes["left"].' -right:'.$sizes["right"].' -bottom:'.$sizes["bottom"].' -top:'.$sizes["top"].' -width:'.$sizes["width"].' -colors:'.$color.' -height:'.$sizes["height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$color.'.icc '.$from.' $@ >'.$to.' 2>&1' );

	$command = './r3 -binary -mode:RENDER -left:'.$sizes["left"].' -right:'.$sizes["right"].' -bottom:'.$sizes["bottom"].' -top:'.$sizes["top"].' -width:'.$sizes["width"].' -colors:'.$color.' -height:'.$sizes["height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$color.'.icc '.$from.' $@ >'.$to.' 2>&1';
	logToFile( 'pageGenerate.txt' , $command );
	shell_exec('
			cd engine/r3 2>&1;
			'.$command.';
			');
	return $command;
	}

$from = $terminalPath."/".$_POST['file'][0]['Name'];
$to = "ajaxteszt_".$_SESSION['intra_user'].".jpg";
$sizes = $_POST['positions'];
$difference = floatval( str_replace( ",", ".", $sizes['right'] ) ) - ( floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) ) );
if( floatval( str_replace( ",", ".", $sizes['left'] ) ) >= floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) ) ) {
	error_log( "asdF");
	$debug = $_POST['file'][0];
	$difference = $sizes['right'] - $sizes['left'];
	$from = $terminalPath."/".$_POST['file'][1]['Name'];
	if( $user[0][15] == "trimbox" ) {
		$sizes['left'] -= $_POST['file'][0]['Right']-$_POST['file'][1]['Left'];
		}
	else {
		$sizes['left'] -= $_POST['file'][0]['Right']-$_POST['file'][1]['Left']-$cbox[0]['Left'];
		}
	$sizes['right'] = ($difference+$sizes['left']);
	$sizes['top'] += floatval( str_replace( ",", ".", $cbox[1]['Bottom'] ) );
	$sizes['bottom'] += floatval( str_replace( ",", ".", $cbox[1]['Bottom'] ) );

	PDFtoImage( $sizes, $to );
	$imgData = base64_encode(file_get_contents( "engine/r3/".$to ) );
	$imgData = 'data:'.mime_content_type( "engine/r3/".$to ).';base64,'.$imgData;
	@unlink( "engine/r3/".$to );	
	}
elseif( $difference > 0 && $_POST['file'][1]["Name"] != "" ) {
	error_log( "2.");
	$temp = pixel_( $difference );
	//$sizes['left'] -= floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
	$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) );
	error_log( "width: ".$sizes['width'].", temp: ".$temp );
	$sizes['width'] -= $temp;
	if( $sizes['width'] < 0 ) $sizes['width'] = 1;
	error_log( "width: ".$sizes['width'] );
	PDFtoImage( $sizes, "left".$_SESSION['intra_user'].".jpg" );
	if( $_POST['file'][1]['Name'] != "" ) {
		$from = $terminalPath."/".$_POST['file'][1]['Name'];
		$sizesOld = $sizes;
		error_log( $sizes['width'] );
		$sizes['left'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Left'] ) );
		$defWidth = pixel_( ($_POST['file'][0]["Width"]+$_POST['file'][1]["Width"]), 100 );
		$zoomWidth = pixel_( ($_POST['file'][0]["Width"]+$_POST['file'][1]["Width"]) );
		if( $difference < ( floatval( $cbox[1]['Right'] )-floatval( $cbox[1]['Left'] ) ) ) {
			$debug = 1;
			$sizes['right'] = $difference;
			$sizes['width'] = $temp;
			if( $zoomWidth < $_POST['fpBox']['Width'] )
				$_POST['positions']['width'] = $zoomWidth;
			}
		else {
			$debug = 2;
			$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) );
			$sizes['width'] = pixel_( floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) ));
			if( $sizes['small'] == "false" && $user[0][15] == "trimbox" ) {
				$sizes['width'] -= pixel_( floatval( 30 ));
				}
			}
		$debug = PDFtoImage( $sizes, "right".$_SESSION['intra_user'].".jpg" );
		$first = new Imagick( "engine/r3/left".$_SESSION['intra_user'].".jpg" );
		$second = new Imagick( "engine/r3/right".$_SESSION['intra_user'].".jpg" );
		$image = new Imagick();
		$defHeight = pixel_( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]), 100 );
		$zoomHeight = pixel_( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]) ); 
		if( $zoomHeight < $_POST['fpBox']['Height'] )
			$_POST['positions']['height'] = $zoomHeight;
			
		$image->newImage( ($_POST['positions']['width']-1), $_POST['positions']['height'], new ImagickPixel('rgb( 178, 178, 178 )') );
			$icc_rgb = file_get_contents( "engine/r3/sRGB_Color_Space_Profile.icc" );
			$image->profileImage('icc', $icc_rgb);
			$image->setImageFormat('jpg');
			$image->compositeImage($first, $first->getImageCompose(), 0, 0); 
			$image->compositeImage($second, $second->getImageCompose(), $sizesOld['width'], 0); 
		$image->writeImage("engine/r3/".$to); 
		$imgData = base64_encode(file_get_contents( "engine/r3/".$to ) );
		$imgData = 'data:'.mime_content_type( "engine/r3/".$to ).';base64,'.$imgData;		
		@unlink( "engine/r3/left".$_SESSION['intra_user'].".jpg" );
		//@unlink( "engine/r3/right".$_SESSION['intra_user'].".jpg" );
		@unlink( "engine/r3/".$to );
		}
	}
else {
	error_log( "3");
	$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) );
	//$sizes['top'] = floatval( str_replace( ",", ".", $cbox[0]['Bottom'] ) );
	//$sizes['bottom'] += floatval( str_replace( ",", ".", $cbox[0]['Bottom'] ) );
	$temp = pixel_( $difference );
  error_log( "temp: ". $temp );
  if( $sizes['small'] == "false" ) {
 	if( $user[0][15] == "trimbox" ) {
 		//error_log( $sizes['left']." + ".$cbox[0]['Left'] );
 		//$sizes['left'] += floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
 		$sizes['width'] -= $temp;
 		error_log( $sizes['width'] );
 		}
 	else {
 	 	$sizes['left'] += floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
 	 	$sizes['right'] += floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
 		$sizes['width'] -= $temp;
 		
 		}
   
    }
	$debug = PDFtoImage( $sizes, $to );
	$first = new Imagick( "engine/r3/".$to );
	$image = new Imagick();
	$defHeight = pixel_( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]), 100 );
	$zoomHeight = pixel_( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]) );

	if( $zoomHeight < $_POST['fpBox']['Height'] ) {
		$sizes['height'] = $zoomHeight;
		}
	
	$image->newImage( ($sizes['width']), $sizes['height'], new ImagickPixel('rgb( 178, 178, 178 )') );
		$icc_rgb = file_get_contents( "engine/r3/sRGB_Color_Space_Profile.icc" );
		$image->profileImage('icc', $icc_rgb);
		$image->setImageFormat('jpg');
		$image->compositeImage($first, $first->getImageCompose(), 0, 0); 
	$image->writeImage("engine/r3/".$to); 

	$imgData = base64_encode(file_get_contents( "engine/r3/".$to ) );
	$imgData = 'data:'.mime_content_type( "engine/r3/".$to ).';base64,'.$imgData;
	//@unlink( "engine/r3/".$to );
	$debug = $temp ;
	}

$width = floatval( str_replace( ",", ".", $cbox[0]['Right'] ) ) - floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );

$result = $imgData;
print json_encode( array( $result, $debug ) );
?>