<?php
if( !isset( $_POST['file'] ) ) die();
header('Content-Type: text/html; charset=utf-8');

include_once( '../engine/connect.php' );
include_once('../engine/engine.php');
include_once('engine/dynaAPI.php');

$zoom = $_GET['zoom'];
$colors = $_POST['colors'];
$cbox = $_POST['cBox'];
$terminalPath = "/var/www/html/client";
$user = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );

// Render cache: every zoom tick and page-nav used to re-run r3/DynaPDF
// from scratch every single time (measured ~0.8-3s+ per render), even
// when returning to a page/zoom/crop state already rendered moments
// ago. This is an approval workflow, so a stale cached render would be
// a real (not just cosmetic) problem - the cache key below is built
// from the ENTIRE request payload (so any different crop/zoom/color
// state is automatically a different key) PLUS each referenced source
// PDF's mtime+filesize (so a new page version - which always changes
// at least the mtime, whether it lands at a new path or, in some
// future code path, the same one - always invalidates old cache
// entries, without depending on knowing/trusting the versioning
// scheme's exact behavior).
function tesztAjaxCacheKey( $terminalPath ) {
	$fileStamps = array();
	foreach( array( 0, 1 ) as $idx ) {
		if( !empty( $_POST['file'][$idx]['Name'] ) ) {
			$name = $_POST['file'][$idx]['Name'];
			$full = ( $name[0] == '/' ) ? $name : $terminalPath.'/'.$name;
			$fileStamps[] = $name.':'.( is_file( $full ) ? filemtime( $full ).':'.filesize( $full ) : 'missing' );
			}
		}

	$keyInput = json_encode( array(
		'get' => $_GET,
		'post' => $_POST,
		'files' => $fileStamps,
		) );

	return md5( $keyInput );
	}

$renderCacheFile = TRKPATH.'/engine/r3/_cache_'.tesztAjaxCacheKey( $terminalPath ).'.jpg';
if( is_file( $renderCacheFile ) ) {
	$imgData = base64_encode( file_get_contents( $renderCacheFile ) );
	$imgData = 'data:image/jpeg;base64,'.$imgData;
	$debug = 'cached';
	$result = $imgData;
	print json_encode( array( $result, $debug, R3_REMOTE_MODE ) );
	exit;
	}

function pixel__( $num, $_zoom = '' ) {
	global $zoom;
	
	$dpi = 72;

	
	if( $_zoom == '' ) $_zoom = $zoom;
	
	return $num * $_zoom / $dpi;
	}

function point__( $num, $_zoom = '' ) {
	global $zoom;
	if( $_zoom == '' ) $_zoom = $zoom;
	
	return $num * 72 / $_zoom;
	}

function PDFtoImage__( $sizes, $to ) {
	global $from, $colors, $col, $pub;
	
	error_log( "PUBID: ".$pub[0]["id"] );
	$page = explode( "/", $from );
	$page = end($page);
	$page = explode( "_", $page );
	$page = intval( $page[0] );
	error_log( "TARFILE: ".$page );
	$col = partDetect( $pub[0]["id"], $page );
	error_log( "PART: ".$col );
	error_log( "PROFIL LÉTEZIK? ".is_file( TRKPATH."/engine/r3/".$col.".icc" ) );
	//$col = "ISOcoated_v2_eci";
	//$col = "ISOnewspaper26v4";
	
	error_log( "R3 DEBUG" );
	
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
			
		$renderParams = array(
			'left' => $sizes["left"], 'right' => $sizes["right"],
			'bottom' => $sizes["bottom"], 'top' => $sizes["top"],
			'width' => $sizes["width"], 'height' => $sizes["height"],
			'colors' => $color,
			'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => $col.'.icc',
			);
		error_log( json_encode( $renderParams ) );
		}
	else {
		$renderParams = array(
			'left' => $sizes["left"], 'right' => $sizes["right"],
			'bottom' => $sizes["bottom"], 'top' => $sizes["top"],
			'width' => $sizes["width"], 'height' => $sizes["height"],
			'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => $col.'.icc',
			);
		}

	$imgData = r3run( 'RENDER', $renderParams, $from );
	file_put_contents( TRKPATH.'/engine/r3/'.$to, $imgData );
	error_log( "wrote ".strlen( $imgData )." bytes to ".$to );

	return true;
	}

$from = $terminalPath."/".$_POST['file'][0]['Name'];
$to = "ajaxteszt_".$_GET['intra_user'].".jpg";
$sizes = $_POST['positions'];
$difference = floatval( str_replace( ",", ".", $sizes['right'] ) ) - ( floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) ) );

$col = "";
$pub = sql_aget( "publications", "id='".$_GET["pubid"]."'", "*" );

error_log( "BEJÖVÖK" );
if( floatval( str_replace( ",", ".", $sizes['left'] ) ) >= floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) ) ) {
	error_log( "nagy 1.");
	$debug = $_POST['file'][0];
	$from = $terminalPath."/".$_POST['file'][1]['Name'];
	$temp = pixel__( $difference );
	
	if( $user[0][15] == "trimbox" ) {
		$difference = $sizes['right'] - $sizes['left'];
		$sizes['left'] -= $_POST['file'][0]['Right'] ;
		$sizes['right'] = ($difference+$sizes['left']);
		$sizes['left'] += $_POST["trimbox"][1]["Left"];
		$sizes['right'] += $_POST["trimbox"][1]["Left"];
		$sizes["bottom"] = $sizes["bottom"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		$sizes["top"] = $sizes["top"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		error_log( "width: ".$sizes["width"] );
		if( $sizes["width"] > 0 ) {
			error_log( "itt");
			if( $_GET["pdfstand"] == "Web" ) {
				$to = "/var/www/html/client/engine/dyna/".$to;
				$debug = DynaToImage( $sizes, $zoom, $from, $to );
				}
			else {
				$d = PDFtoImage__( $sizes, $to );
				$to = "engine/r3/".$to;
				}
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
				$icc_rgb = file_get_contents( "/var/www/html/r3API/r3/sRGB_Color_Space_Profile.icc" );
				$image->profileImage('icc', $icc_rgb);
				$image->setImageFormat('jpg');
				$image->compositeImage($img, $img->getImageCompose(), "-".$diff, 0); 
			$image->writeImage("engine/r3/".$to);
			}	
		}
	
	if( $sizes["width"] > 0 ) {
		$imgData = base64_encode(file_get_contents( $to ) );
		$imgData = 'data:'.mime_content_type( $to ).';base64,'.$imgData;
		@unlink( $to );	
		}
	else {
		$imgData = "";
		}
	}
elseif( $difference > 0 && $_POST['file'][1]["Name"] != "" ) {
	error_log( "nagy 2.");
	$temp = pixel__( $difference );
	//$sizes['left'] -= 1;
	$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) ) ;
	$sizes['width'] -= $temp;
	if( $sizes['width'] < 0 ) $sizes['width'] = 1;
	
	if( $_GET["pdfstand"] == "Web" ) {
		$to_left = "/var/www/html/client/engine/dyna/left".$_GET['intra_user'].".jpg";
		$debug = DynaToImage( $sizes, $zoom, $from, $to_left );
		}
	else {
		$debug = PDFtoImage__( $sizes, "left".$_GET['intra_user'].".jpg" );
		$to_left = "engine/r3/left".$_GET['intra_user'].".jpg";
		}
	
	if( $_POST['file'][1]['Name'] != "" ) {
		$from = $terminalPath."/".$_POST['file'][1]['Name'];
		error_log( $from );
		$sizesOld = $sizes;
		$sizes['left'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Left'] ) );
		$defWidth = pixel__( ($_POST['file'][0]["Width"]+$_POST['file'][1]["Width"]), 100 );
		$zoomWidth = pixel__( ($_POST['file'][0]["Width"]+$_POST['file'][1]["Width"]) );
		if( $difference < ( floatval( $cbox[1]['Right'] )-floatval( $cbox[1]['Left'] ) ) ) {
			$sizes['right'] = $sizes['left'] + $difference;
			$sizes['width'] = $temp;
			if( $zoomWidth < $_POST['fpBox']['Width'] )
				$_POST['positions']['width'] = $zoomWidth;
			}
		else {
			if( $user[0][15] != "trimbox" ) {
				$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) )+29;
				$sizes['width'] = pixel__( floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) ));
				}
				
			if( $sizes['small'] == "false" && $user[0][15] == "trimbox" ) {
				$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) )+29;
				$sizes['width'] = pixel__( floatval( str_replace( ",", ".", $_POST['file'][1]['Right'] ) ));
				}
			}
		$sizes["bottom"] = $sizes["bottom"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		$sizes["top"] = $sizes["top"]-$_POST['corr'][0]["Bottom"]+$_POST['corr'][1]["Bottom"];
		if( $_GET["pdfstand"] == "Web" ) {
			$to_right = "/var/www/html/client/engine/dyna/right".$_GET['intra_user'].".jpg";
			$debug = DynaToImage( $sizes, $zoom, $from, $to_right );
			}
		else {
			$debug = PDFtoImage__( $sizes, "right".$_GET['intra_user'].".jpg" );
			$to_right = "engine/r3/right".$_GET['intra_user'].".jpg";
			}
		
		$first = new Imagick( $to_left );
		$second = new Imagick( $to_right );
		$image = new Imagick();
		$defHeight = pixel__( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]), 100 );
		$zoomHeight = pixel__( ($_POST['file'][0]["Top"]-$_POST['file'][0]["Bottom"]) ); 
		if( $zoomHeight < $_POST['fpBox']['Height'] )
			$_POST['positions']['height'] = $zoomHeight;
			
		$image->newImage( ($_POST['positions']['width']-1 ), $_POST['positions']['height'], new ImagickPixel('rgb( 178, 178, 178 )') );
			$icc_rgb = file_get_contents( "/var/www/html/r3API/r3/sRGB_Color_Space_Profile.icc" );
			$image->profileImage('icc', $icc_rgb);
			$image->setImageFormat('jpg');
			$image->compositeImage($first, $first->getImageCompose(), 0, 0); 
			$image->compositeImage($second, $second->getImageCompose(), $sizesOld['width'], 0); 
		$image->writeImage("engine/r3/".$to); 
		$imgData = base64_encode(file_get_contents( "engine/r3/".$to ) );
		$imgData = 'data:'.mime_content_type( "engine/r3/".$to ).';base64,'.$imgData;		
		@unlink( $to_left );
		@unlink( $to_right );
		@unlink( "engine/r3/".$to );
		}
	}
else {
	error_log( "nagy 3.");
	$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file'][0]['Right'] ) );
	$temp = pixel__( $difference );
	if( $sizes['small'] == "false" ) {
		if( $user[0][15] == "trimbox" ) {
	 		$sizes['width'] -= $temp;
	 		//$sizes['left'] -= 3;
	 		//$sizes['right'] -= 3;
	 		}
	 		
	 	else {
	 	 	$sizes['left'] += floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
	 	 	$sizes['right'] += floatval( str_replace( ",", ".", $cbox[0]['Left'] ) );
	 		$sizes['width'] -= $temp;
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
		$icc_rgb = file_get_contents( "/var/www/html/r3API/r3/sRGB_Color_Space_Profile.icc" );
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

// Persist this render for reuse (see the cache-check near the top of
// this file) - deliberately done here, once, on the single $imgData
// value every branch above converges to, rather than touching any of
// the branches' own internal temp-file handling.
if( !empty( $imgData ) && strpos( $imgData, 'base64,' ) !== false ) {
	$rawBinary = base64_decode( substr( $imgData, strpos( $imgData, 'base64,' )+7 ) );
	if( $rawBinary !== false ) {
		file_put_contents( $renderCacheFile, $rawBinary );
		}
	}

print json_encode( array( $result, $debug, R3_REMOTE_MODE ) );
?>