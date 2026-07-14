<?php
ini_set('default_charset', 'utf-8');

include_once('../engine/connect.php');
include_once('lang/en.php');
include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');

$single = true;
// Set instead of $single when a zip needs building - ZipArchive (php-zip,
// the Debian/APT package) builds to a real file on disk rather than
// streaming straight into the HTTP response the way the old Composer
// ZipStream library did, so every branch below just describes what goes
// into the zip and the actual header/readfile/cleanup happens once, at
// the bottom, alongside the existing single-file path.
$zipFile = null;
$zipName = null;

function buildZip( $entries ) {
	$tmpPath = "/var/www/html/client/temp/".uniqid( "zip_", true ).".zip";
	$zip = new ZipArchive();
	$zip->open( $tmpPath, ZipArchive::CREATE );
	foreach( $entries as $entryName => $sourcePath ) {
		$zip->addFile( $sourcePath, $entryName );
		}
	$zip->close();
	return $tmpPath;
	}

if( $_GET["type"] == "fp" ) {
	$file = sql_aget( "flatplan_files", "id='".$_GET["id"]."'", "*" );

	$origname = $file[0]["origname"];
	$path = $file[0]["path"];
	$name = $file[0]["filename"];
	}

if( $_GET["type"] == "asset" ) {
	ini_set('max_execution_time', 0);
	$data = json_decode( $_GET['multi'] );

	$check = sql_aget( "assets", "id='".$_GET["id"]."'", "*" );
	if( $check[0]["type"] == "application/x-indesign" or $check[0]["type"] == "web_pack" ) {
		if( count( $data ) > 0 ) {
			$single = false;
			$entries = array();
			for( $i = 0; $i < count( $data ); $i++ ) {
				$file = sql_aget( "assets", "id='".$data[$i]."'", "*" );
				$temp = explode(".", $file[0]["name"] );
				$entries[ $file[0]["origname"].".".end($temp) ] = "assets/".$file[0]["pub_id"]."/".$file[0]["parent"]."/".$file[0]["name"];
				}
			$zipFile = buildZip( $entries );
			$zipName = "Archive.zip";
			}

		else {
			$single = false;
			if( $check[0]["type"] == "web_pack" ) {
				$check[0]["origname"] .= ".zip";
				}
			$zipName = str_replace( ".indd", ".zip", $check[0]["origname"] );

			$entries = array();
			$files = sql_aget( "assets", "parent='".$check[0]["id"]."'", "*" );
			for( $i = 0; $i < count( $files ); $i++ ) {
				$temp = explode(".", $files[$i]["name"] );
				$entries[ $files[$i]["origname"].".".$temp[1] ] = "assets/".$files[$i]["pub_id"]."/".$files[$i]["parent"]."/".$files[$i]["name"];
				}
			$zipFile = buildZip( $entries );
			}
		}

	else {
		if( count( $data ) > 0 ) {
			if( array_search( $_GET["id"], $data ) === false ) {
				$data[] = $_GET["id"];
				}

			if( count( $data ) > 1 ) {
				$single = false;
				$entries = array();
				for( $i = 0; $i < count( $data ); $i++ ) {
					$file = sql_aget( "assets", "id='".$data[$i]."'", "*" );
					$temp = explode(".", $file[0]["name"] );
					$entries[ $file[0]["origname"].".".end( $temp ) ] = "assets/".$file[0]["pub_id"]."/".$file[0]["parent"]."/".$file[0]["name"];
					}
				$zipFile = buildZip( $entries );
				$zipName = "Archive.zip";
				}

			else {
				$file = sql_aget( "assets", "id='".$data[0]."'", "*" );

				$temp = explode(".", $file[0]["name"] );
				$origname = $file[0]["origname"].".".$temp[1];
				$path = "assets/".$file[0]["pub_id"]."/".$file[0]["parent"];
				$name = $file[0]["name"];
				}
			}
		else {
			$file = sql_aget( "assets", "id='".$_GET["id"]."'", "*" );

			$temp = explode(".", $file[0]["name"] );
			$origname = $file[0]["origname"].".".end( $temp );
			$path = "assets/".$file[0]["pub_id"]."/".$file[0]["parent"];
			$name = $file[0]["name"];
			}
		}
	}

if( $_GET["type"] == "multi" ) {
	$pages = json_decode( $_GET['pageselector'] );
	$states = json_decode( $_GET['state'] );
	$pub_id = $_GET['id'];
	$pub = sql_get( 'publications', 'id="'.$pub_id.'"', '*' );
	$issue = $pub[0][10];
	$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );

	$path = "/var/www/html/client/packages/".$magazine[0][3]."/".$issue;
	$abspath = "packages/".$magazine[0][3]."/".$issue;
	$jpegPath = "packages/".$magazine[0][3]."/".$issue;
	$files = array();

	$pack_info = array();
	for ($i = 0; $i < count($pages); $i++) {
		if( $_GET['alter'] == "NOR" )
			$pack = sql_get('pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$pages[$i].'" AND issue="'.$issue.'" AND code="'.$magazine[0][3].'" AND state="'.$states[$i].'" LIMIT 1', '*' );
		elseif( $_GET['alter'] == "FIN" )
			$pack = sql_get('pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$pages[$i].'" AND issue="'.$issue.'" AND code="'.$magazine[0][3].'" AND state="'.$states[$i].'" AND fin="1" LIMIT 1', '*' );
		else
			$pack = sql_get('pageinfo', 'type="'.$_GET['alter'].'" AND page="'.$pages[$i].'" AND issue="'.$issue.'" AND code="'.$magazine[0][3].'" AND state="'.$states[$i].'" AND fin="0" LIMIT 1', '*' );

		if( $pack[0][6] == "ad" ) {
			$files[] = "_ads/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$pack[0][1]."_ad_preview.pdf";
			}
		elseif( $pack[0][6] == "magazine" ) {
			$subDir = sql_get('packages', 'id="'.$pack[0][1].'"', '*' );
			if( $_GET['alter'] == "FIN" ) {
				$files[] = $subDir[0][4]."/FIN/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$pack[0][1]."_".$states[$i]."preview.pdf";
				}
			else {
				$files[] = $subDir[0][4]."/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$pack[0][1]."_".$states[$i]."preview.pdf";
				}
			}
		else {
			$files[] = "_".$pack[0][6]."/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$pack[0][1]."_".$states[$i]."preview.pdf";
			}
		$packs = sql_get('packages', 'publication_id="'.$pub_id.'" AND starting_page != "" ORDER BY `starting_page` ASC', '*' );
		$pack_info[] = $pack[0];
		}

	$loc = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".zip";

	$single = false;
	$entries = array();
	for ($i = 0; $i < count($files); $i++) {
		$name = $magazine[0][3]."_".$issue."_".$pages[$i].".pdf";
		$entries[$name] = $path.'/'.$files[$i];
		}
	$zipFile = buildZip( $entries );
	$zipName = $loc;
	}

if( $single ) {
	header("Content-Type: application/octet-stream");
	header("Content-Transfer-Encoding: Binary");
	header("Content-disposition: attachment; filename=\"".$origname."\"");
	readfile( $path."/".$name );
	}
elseif( $zipFile !== null ) {
	header("Content-Type: application/zip");
	header("Content-Transfer-Encoding: Binary");
	header('Content-Length: '.filesize( $zipFile ) );
	header("Content-disposition: attachment; filename=\"".$zipName."\"");
	readfile( $zipFile );
	unlink( $zipFile );
	}
?>
