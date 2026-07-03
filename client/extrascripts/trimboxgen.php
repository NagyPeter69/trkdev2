<?
include_once('../../engine/connect.php');
include_once('../lang/en.php');
include_once('../../engine/engine.php');
include_once('../../engine/xml_handler.php');

echo "<pre>";

$dir = "/var/www/html/client/packages";

function load( $dir ) {
	$dh = opendir($dir);
	while( ( $file = readdir( $dh ) ) !== false ) {
		if( $file != "." && $file != ".." ) {
			if( is_dir( $dir."/".$file ) ) {
				echo "dir:" . $file . "<br>";
				load( $dir."/".$file );
				}
			else {
				if( strpos( $file , ".pdf" ) ) {
					echo "filename:" . $file . "<br>";
					$terminalPath = "/var/www/intra/client";
					
					$f[0]["Name"] = $dir."/".$file;
					$f[0]["Path"] = substr( $dir, 3 )."/".$file;
					$sizes = getBBox( str_replace( "/var/www/html/client/", "", $f[0]["Name"] ), "" );
					$f[0]["Right"] = $sizes['Right'];
					$f[0]["Top"] = $sizes['Top'];
					$f[0]["Width"] = $sizes['Width'];
					$f[0]["Height"] = $sizes['Height'];
					$f[0]["Left"] = 0;
					$f[0]["Bottom"] = 0;

					$box = getPDFBox_TEMP( "Mediabox Trimbox Cropbox Bleedbox", $f[0]["Name"] );
					$differences = array(
						"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
						"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
						"Right" => ( $box["Mediabox"][2] - $box["Cropbox"][2] ),
						"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
						);	
					
					//Mediabox
					$correctionBox[2] = $correctionBoxTemp = "mediabox";
				
					$sizes = array(
						"Left" => $box["Trimbox"][0] - 28.3464567 - $box["Cropbox"][0],
						"Bottom" => $box["Trimbox"][1] - 28.3464567 - $box["Cropbox"][1],
						"Right" => $box["Trimbox"][2] + 28.3464567 - $box["Cropbox"][0],
						"Top" => $box["Trimbox"][3] + 28.3464567 - $box["Cropbox"][1]
						);
				
					$correctionBox[0] = $differences;
					$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
					$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
					$fullSizes = ( $f[0]["Right"]-$f[0]["Left"] );					
					
					echo( $dir."/".$file ." => ".$dir."/".(str_replace( ".pdf", "-cropbox.jpg", $file ) ) );
					if( !is_file( $dir."/".(str_replace( ".pdf", "-cropbox.jpg", $file ) ) ) ) {
						PDFtoImage_TEMP( $sizes, $dir."/".$file, $dir."/".(str_replace( ".pdf", "-cropbox.jpg", $file ) ), "" );
						}
					
					//Trimbox
					$correctionBox[2] = $correctionBoxTemp = "trimbox";	
					$sizes = array(
						"Left" => $box["Trimbox"][0] - $differences['Left'],
						"Bottom" => $box["Trimbox"][1] - $differences['Bottom'],
						"Right" => $box["Trimbox"][2]-$box["Cropbox"][0],
						"Top" => $box["Trimbox"][3] - $differences['Top']
						);
		
					$differences = array(
						"Left" => ( $box["Cropbox"][0] - $box["Trimbox"][0] ),
						"Bottom" => ( $box["Cropbox"][1] - $box["Trimbox"][1] ),
						"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
						"Top" => ( $box["Cropbox"][3] - $box["Trimbox"][3] )
						);
				
					$correctionBox[0] = $differences;
					$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
					$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
					$fullSizes = ( $f[0]["Right"]-$f[0]["Left"] );
					
					echo( $dir."/".$file ." => ".$dir."/".(str_replace( ".pdf", "-cropbox.jpg", $file ) ) );
					if( !is_file( $dir."/".(str_replace( ".pdf", "-trimbox.jpg", $file ) ) ) ) {
						PDFtoImage_TEMP( $sizes, $dir."/".$file, $dir."/".(str_replace( ".pdf", "-trimbox.jpg", $file ) ), "" );
						}
					}
				}
			}
		}
	echo "<br>";
	}
load( $dir );
?>
