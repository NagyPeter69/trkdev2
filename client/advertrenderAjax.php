<?php

if( !isset( $_POST['file'] ) ) die();
session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../engine/connect.php' );
include_once('../engine/engine.php');
include_once('engine/dynaAPI.php');

$zoom = $_GET['zoom'];
$colors = $_POST['colors'];
$cbox = $_POST['cBox'];
$phpPath = "engine/r3";
$terminalPath = "/var/www/html/client";
$user = sql_get( 'accounts', 'id="'.( $_SESSION['intra_user'] ?? '' ).'"', '*' );
// See client/plugins/pubsApply.php's 2026-09-05 fix - this file had no
// authentication check at all. Same fix: gate before doing anything else.
if( empty( $user[0][0] ) ) {
	print json_encode( array( array( "Unauthorized" ) ) );
	exit;
	}
$user[0][15] = "mediabox";

function pixel__( $num, $_zoom = '' ) {
	global $zoom;
	if( $_zoom == '' ) $_zoom = $zoom;
	
	return $num * $_zoom / 72;
	}

function point__( $num, $_zoom = '' ) {
	global $zoom;
	if( $_zoom == '' ) $_zoom = $zoom;
	
	return $num * 72 / $_zoom;
	}

function PDFtoImage__( $sizes, $to ) {
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

	$renderParams = array(
		'left' => $sizes["left"], 'right' => $sizes["right"],
		'bottom' => $sizes["bottom"], 'top' => $sizes["top"],
		'width' => $sizes["width"], 'height' => $sizes["height"],
		'colors' => $color,
		'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => resolveIccProfileByName( "FOGRA_39" ),
		);
	$command = json_encode( $renderParams )." ".$from." -> /var/www/html/client/engine/r3/".$to;
	logToFile( 'pageGenerate.txt' , $command );

	$imgData = r3run( 'RENDER', $renderParams, $from );
	file_put_contents( '/var/www/html/client/engine/r3/'.$to, $imgData );

	return $command;
	}

$from = $terminalPath."/advertisements/".$_POST['file'][0]['Name'];
$to = "ajaxteszt_".$_SESSION['intra_user'].".jpg";
$sizes = $_POST['positions'];
$difference = floatval( str_replace( ",", ".", $sizes['right'] ) ) - ( floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) ) );
if( floatval( str_replace( ",", ".", $sizes['left'] ) ) >= floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) ) ) {
	$debug = $_POST['file'][0];
	$from = $terminalPath."/".$_POST['file'][1]['Name'];
	$temp = pixel__( $difference );
	
	if( $user[0][15] == "trimbox" ) {
		$difference = $sizes['right'] - $sizes['left'];
		$sizes['left'] -= $_POST['file'][0]['Right'];
		$sizes['right'] = ($difference+$sizes['left']);
		$sizes['left'] += $_POST["trimbox"][1]["Left"];
		$sizes['right'] += $_POST["trimbox"][1]["Left"];
		$sizes["bottom"] = $sizes["bottom"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		$sizes["top"] = $sizes["top"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		if( $sizes["width"] > 0 ) {
			$d = PDFtoImage__( $sizes, $to );
			}	
		}
	else {
		$diff = pixel__( ( $sizes['left'] ) - $_POST['file'][0]['Right'] )+1;
		$sizes['left'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Left'] ) );
		$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) )+$_POST["trimbox"][1]["Left"];
		$sizes['width'] = pixel__( floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) ));
		$sizes["bottom"] = $sizes["bottom"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		$sizes["top"] = $sizes["top"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		if( $sizes["width"] > 0 ) {
			$d = PDFtoImage__( $sizes, $to );	
			$img = new Imagick( "engine/r3/".$to );
			$image = new Imagick();
			$image->newImage( ($_POST['positions']['width']-1), $_POST['positions']['height'], new ImagickPixel('rgb( 178, 178, 178 )') );
				$icc_rgb = file_get_contents( "engine/r3/sRGB_Color_Space_Profile.icc" );
				$image->profileImage('icc', $icc_rgb);
				$image->setImageFormat('jpg');
				$image->compositeImage($img, $img->getImageCompose(), "-".$diff, 0); 
			$image->writeImage("engine/r3/".$to);
			}	
		}
	
	if( $sizes["width"] > 0 ) {
		$imgData = base64_encode(file_get_contents( "engine/r3/".$to ) );
		$imgData = 'data:'.mime_content_type( "engine/r3/".$to ).';base64,'.$imgData;
		@unlink( "engine/r3/".$to );	
		}
	else {
		$imgData = "";
		}
	}
elseif( $difference > 0 && $_POST['file'][1]["Name"] != "" ) {
	$temp = pixel__( $difference );
	//$sizes['left'] -= floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
	$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) );
	$sizes['width'] -= $temp;
	if( $sizes['width'] < 0 ) $sizes['width'] = 1;
	$debug = PDFtoImage__( $sizes, "left".$_SESSION['intra_user'].".jpg" );
	if( $_POST['file'][1]['Name'] != "" ) {
		$from = $terminalPath."/".$_POST['file'][1]['Name'];
		$sizesOld = $sizes;
		$sizes['left'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Left'] ) );
		$defWidth = pixel__( ($_POST['file'][0]["Width"]+$_POST['file'][1]["Width"]), 100 );
		$zoomWidth = pixel__( ($_POST['file'][0]["Width"]+$_POST['file'][1]["Width"]) );
		if( $difference < ( floatval( $cbox[1]['Right'] )-floatval( $cbox[1]['Left'] ) ) ) {
			$debug = 1;
			$sizes['right'] = $difference;
			$sizes['width'] = $temp;
			if( $zoomWidth < $_POST['fpBox']['Width'] )
				$_POST['positions']['width'] = $zoomWidth;
			}
		else {
			$debug = 2;
			$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) )+29;
			$sizes['width'] = pixel__( floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) ));
			if( $sizes['small'] == "false" && $user[0][15] == "trimbox" ) {
				//$sizes['width'] += pixel__( floatval( 30 ));
				}
			}
		$sizes["bottom"] = $sizes["bottom"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		$sizes["top"] = $sizes["top"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		$debug = PDFtoImage__( $sizes, "right".$_SESSION['intra_user'].".jpg" );
		$first = new Imagick( "engine/r3/left".$_SESSION['intra_user'].".jpg" );
		$second = new Imagick( "engine/r3/right".$_SESSION['intra_user'].".jpg" );
		$image = new Imagick();
		$defHeight = pixel__( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]), 100 );
		$zoomHeight = pixel__( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]) ); 
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
		@unlink( "engine/r3/right".$_SESSION['intra_user'].".jpg" );
		@unlink( "engine/r3/".$to );
		}
	}
else {
	error_log( "nagy 3.");
	$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) );
	//$sizes['top'] = floatval( str_replace( ",", ".", $cbox[0]['Bottom'] ) );
	//$sizes['bottom'] += floatval( str_replace( ",", ".", $cbox[0]['Bottom'] ) );
	$temp = pixel__( $difference );
	if( $sizes['small'] == "false" ) {
		if( $user[0][15] == "trimbox" ) {
			$sizes['width'] -= $temp;
			}
		else {
			$sizes['left'] += floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
			$sizes['right'] += floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
			$sizes['width'] -= $temp;
			/*if( $_POST['corr'][0]["Left"] < 0 ) {
				$sizes["left"] = $sizes["left"]+$_POST['corr'][0]["Left"];
				$sizes["right"] = $sizes["right"]-$_POST['corr'][0]["Left"];	
				}*/
			
			}
		}
	if( $_GET["pdfstand"] == "Web" ) {
		$to = "/var/www/html/client/engine/dyna/".$to;
		$debug = DynaToImage( $sizes, $zoom, $from, $to );
		}
	else {		
		$debug = PDFtoImage__( $sizes, $to );
		$to = "engine/r3/".$to;
		}
		
	$first = new Imagick( $to );
	$image = new Imagick();
	$defHeight = pixel__( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]), 100 );
	$zoomHeight = pixel__( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]) );

	if( $zoomHeight < $_POST['fpBox']['Height'] ) {
		$sizes['height'] = $zoomHeight;
		}
		
	$image->newImage( ($sizes['width']), $sizes['height'], new ImagickPixel('rgb( 178, 178, 178 )') );
		$icc_rgb = file_get_contents( "engine/r3/sRGB_Color_Space_Profile.icc" );
		$image->profileImage('icc', $icc_rgb);
		$image->setImageFormat('jpg');
		$image->compositeImage($first, $first->getImageCompose(), 0, 0); 
	$image->writeImage( $to ); 

	$imgData = base64_encode(file_get_contents( $to ) );
	$imgData = 'data:'.mime_content_type( $to ).';base64,'.$imgData;
	@unlink( $to );
	$debug = $temp;
	}

$width = floatval( str_replace( ",", ".", $cbox[0]['Right'] ) ) - floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );

$result = $imgData;
print json_encode( array( $result, $debug ) );
?>