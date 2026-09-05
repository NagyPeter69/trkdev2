<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');
include_once( 'switchAPI.php' );

$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}
// See client/plugins/pubsApply.php's 2026-09-05 fix - none of
// this file's op== handlers checked authentication before
// running. Same fix: one gate before any op is dispatched.
if( empty( $user[0][0] ) ) {
	print json_encode( array( array( "Unauthorized" ) ) );
	exit;
	}

if( !empty( $user[0][17] ) ) {	
	$lng = $user[0][17];
	include_once('../lang/'.$user[0][17].'.php');	
	}
else {
	$lng = "en";
	include_once('../lang/en.php');	
	}

// European-style locale (hu/de/pl) uses a comma for the decimal point;
// English uses a period. Matches how the rest of this file already branches
// display strings on $lng.
function formatAssetSize( $bytes, $decimalSep ) {
	if( $bytes <= 0 ) {
		return "0 KB";
		}
	$units = array( "B", "KB", "MB", "GB", "TB" );
	$i = max( 0, min( floor( log( $bytes, 1024 ) ), count( $units )-1 ) );
	$value = $bytes / pow( 1024, $i );
	return number_format( $value, 2, $decimalSep, "" )." ".$units[$i];
	}

// Jobs here run 80-100 packs deep and loadAssets() re-polls every second, so
// re-summing filesize() over every pack's children on every single poll
// doesn't scale - a pack's images arrive one Switch callback at a time
// (image_upload-handler.php), so it can genuinely still be growing, but
// once its child count stops changing the total is done for good. Cache
// per pack, keyed on that child count: unchanged count is a free cache hit
// (no filesystem calls at all), a changed count means it's still filling up
// and gets re-summed. Self-invalidating - nothing elsewhere has to know
// this cache exists or remember to clear it.
function getPackSize( $pubId, $packId, $childs ) {
	$childCount = count( $childs );
	$cacheFile = TRKPATH."/temp/asset_size_cache/".$packId.".json";

	$cached = is_file( $cacheFile ) ? json_decode( file_get_contents( $cacheFile ), true ) : null;
	if( is_array( $cached ) && ( $cached["count"] ?? null ) === $childCount ) {
		return $cached["size"];
		}

	$size = 0;
	for( $c = 0; $c < $childCount; $c++ ) {
		$size += @filesize( TRKPATH."/assets/".$pubId."/".$packId."/".$childs[$c]["name"] );
		}

	if( !is_dir( TRKPATH."/temp/asset_size_cache" ) ) {
		mkdir( TRKPATH."/temp/asset_size_cache", 0777, true );
		}
	file_put_contents( $cacheFile, json_encode( array( "count" => $childCount, "size" => $size ) ), LOCK_EX );

	return $size;
	}

// Same reasoning as getPackSize() above (archive packages can be many GB,
// loadAssets() polls every second), but there's no DB row count to key the
// cache on here - archives aren't DB-backed. Directory mtime instead: Switch
// writes the whole package in one upload session and the archive is
// immutable afterward, so the top-level dir's mtime stops changing once
// that's done and every poll after that is a free cache hit.
function getArchiveSize( $pubId, $archivePath ) {
	$mtime = @filemtime( $archivePath );
	$cacheFile = TRKPATH."/temp/asset_size_cache/archive_".$pubId.".json";

	$cached = is_file( $cacheFile ) ? json_decode( file_get_contents( $cacheFile ), true ) : null;
	if( is_array( $cached ) && ( $cached["mtime"] ?? null ) === $mtime ) {
		return $cached["size"];
		}

	$size = 0;
	$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $archivePath, FilesystemIterator::SKIP_DOTS ) );
	foreach( $iterator as $fileInfo ) {
		if( $fileInfo->isFile() ) {
			$size += $fileInfo->getSize();
			}
		}

	if( !is_dir( TRKPATH."/temp/asset_size_cache" ) ) {
		mkdir( TRKPATH."/temp/asset_size_cache", 0777, true );
		}
	file_put_contents( $cacheFile, json_encode( array( "mtime" => $mtime, "size" => $size ) ), LOCK_EX );

	return $size;
	}

if( $_GET["op"] == "removeAsset") {
	$asset = sql_aget( "assets", "id='".$_GET["id"]."'", "*" );
	
	if( !empty( $asset[0]["id"] ) ) {
		$path = TRKPATH."/assets/".$asset[0]["pub_id"]."/".$asset[0]["id"]."";
		
		if( is_dir( $path ) ) {
			delTree( $path );
			
			sql_delete( "assets", "parent='".$asset[0]["id"]."'" );
			sql_delete( "assets", "id='".$asset[0]["id"]."'" );
			}
		}
	}
	
if( $_GET["op"] == "loadPack") {
	$txt = "";
	$buttons = "";
	$indd = sql_aget( "assets", "id='".$_GET["id"]."'", "*" );	
	
	//KÉPEK GENERÁLÁSA
	$name = explode( ".", $indd[0]["origname"] );
	$files = sql_aget( "assets", "parent='".$indd[0]["id"]."' ORDER BY origname ASC", "*" );
	
	$txt .= "<div style='color: rgb(220 220 220); font-size: 14px;'>".$name[0]." ::: ".$lang["assets"]["content"]." – ".count($files)." ".( count($files) > 1 ? $lang["assets"]["images"] : $lang["assets"]["image"] )."</div>";
	for( $i = 0; $i < count( $files ); $i++ ) {
		$txt .= "<div class='img_box' data='".$files[$i]["id"]."' page='".($i+1)."'>";
			$txt .= "<table width='100%' height='100%' cellspacing='0' cellpadding='0'>";
				$txt.= "<tr>";
					$txt .= "<td class='img_header'>";
						$txt .= "<div class='img_download'><i onclick='downloadAsset( \"".$files[$i]["id"]."\" )' class='fas fa-cloud-download-alt'></i></div>";
					$txt .= "</td>";
				$txt.= "</tr>";
				
				$txt.= "<tr>";
					$txt .= "<td class='img_pic' valign='middle' align='center' style='padding: 0px 20px;'>";
						$temp = explode(".", $files[$i]["name"] );
						array_pop($temp);
						$temp = implode( ".", $temp );
						$bg = $temp."_pre.jpg";
						
						$img = "assets/".$files[$i]["pub_id"]."/".$files[$i]["parent"]."/".$bg;
						if( !is_file( TRKPATH."/".$img ) ) {
							if( strpos( strtolower( $files[$i]["name"] ), ".eps" ) ) {
								$img = "assets/eps.png";
								}

							if( strpos( strtolower( $files[$i]["name"] ), ".ai" ) ) {
								$img = "assets/ai.png";
								}

							if( strpos( strtolower( $files[$i]["name"] ), ".pdf" ) ) {
								$img = "assets/pdf.png";
								}
							}
						
						$txt .= "<div class='img_bg' style='background-image: url(\"".$img."\")'></div>";
					$txt .= "</td>";
				$txt.= "</tr>";
				
				$txt.= "<tr>";
					$txt .= "<td class='img_text' align='center' valign='middle'>";
						$name = $files[$i]["origname"];
						
						if( strlen($name) > 24 ) {
							$name = htmlspecialchars( substr( $files[$i]["origname"], 0, 10) )."...".htmlspecialchars( substr( $files[$i]["origname"], -10 ) );
							}
						
						$txt .= $name;
					$txt .= "</td>";
				$txt.= "</tr>";				
			$txt .= "</table>";
		$txt .= "</div>";
		}
	
	$txt .= "<div style='clear:both;'></div>";
	
	//GOMBOK GENERÁLÁSA
	$longnames = array( "hu" );
	$longbutton = false;
	
	if( in_array( $lng, $longnames) ) {
		$longbutton = true;
		}
	
	if( $longbutton ) {
		$buttons .= "<div onclick='selectAll()' class='simpleButton' style=''>".$lang["assets"]["selectall"]."</div>";
		$buttons .= "<div style='clear:both;'></div>";
		$buttons .= "<div onclick='deselectAll()' class='simpleButton' style='margin-top: 5px;'>".$lang["assets"]["deselectall"]."</div>";		
		}
	else {
		$buttons .= "<div onclick='selectAll()' class='simpleButton' style='display: inline-block;'>".$lang["assets"]["selectall"]."</div>";
		$buttons .= "<div onclick='deselectAll()' class='simpleButton' style='display: inline-block; margin-left: 10px;'>".$lang["assets"]["deselectall"]."</div>";		
		}
	
	$buttons .= "<div style='clear:both;'></div>";
	$buttons .= "<div id='simpleAssetDownload' onclick='downloadPic( \"".$indd[0]["id"]."\" )' class='disabled simpleButton' style='margin-top: 10px;'>".$lang["assets"]["download"]."</div>";
	$buttons .= "<div style='clear:both;'></div>";
	$buttons .= "<div onclick='downloadAsset( \"".$indd[0]["id"]."\" )' class='simpleButton' style='margin-top: 10px; width: 101px;'>".$lang["assets"]["downloadall"]."</div>";
	
	$result = array( $txt, $buttons );
	}

if( $_GET["op"] == "saveColor") {
	$data = json_decode( $_GET['multi'] );
	if( count( $data ) > 0 ) {
		for( $i = 0; $i < count( $data ); $i++ ) {
			sql_update( "assets", "color='".$_GET["color"]."'", "id='".$data[$i]."'" );
			}
		}
	else {
		sql_update( "assets", "color='".$_GET["color"]."'", "id='".$_GET["aid"]."'" );
		}
	}
	
if( $_GET["op"] == "loadTypes") {
	$txt = "";

	$first = "";
	$types = array( "PRE", "BASIC", "FIN" );
	$count = 0;
	
	for( $i = 0; $i < count( $types ); $i++ ) {
		$pre = sql_aget( "assets", "pub_id='".$_GET['pub']."' AND fp='".$types[$i]."' LIMIT 1", "*" );
		if( !empty( $pre[0]["id"] ) ) {
			if( empty( $first ) ) $first = $types[$i];
							
			$txt .= "<div onclick='changeAlter( this )' id='alter_".$types[$i]."' data='".$types[$i]."'  class='kindButton ".( $first == $types[$i] ? "alterSelected" : "" )." ".strtolower( $types[$i] )."'>".strtolower( $types[$i] )."</div>";
			$count++;
			}
		}
					
		if( $count > 1 ) {
			$txt .= "<div onclick='changeAlter( this )' id='alter_".$key."' data='ALL' class='kindButton all'>all</div>";
			}
	
	$result = array( $txt, $first );
	}

if( $_GET["op"] == "loadAssets" ) {
	$txt = "";
	$order = " ORDER BY ".$_GET["order"]." ".$_GET["orderType"];
	$assets = sql_aget( "assets", "pub_id='".$_GET["pub"]."' ".( $_GET["alter"] != "ALL" ? "AND fp='".$_GET["alter"]."'" : "" )." AND parent='0' ".$order."", "*" );
	for( $i = 0; $i < count( $assets ); $i++ ) {
		$date = date( "d F Y, H:i", $assets[$i]["time"] );
		$today = date("Y-m-d");
		$yesterday = date("Y-m-d", strtotime("-1 day") );
		
		if( $today == date("Y-m-d", $assets[$i]["time"] ) ) {
			$date = "Today, ".date( "H:i", $assets[$i]["time"] );
			}

		if( $yesterday == date("Y-m-d", $assets[$i]["time"] ) ) {
			$date = "Yesterday, ".date( "H:i", $assets[$i]["time"] );
			}
		
		if( $_GET["stripped"] == "1" ) {
			if( $assets[$i]["type"] == "web_pack" ) {
				$name = $assets[$i]["stripped_name"];
				}
			else {
				$pub = sql_aget( "publications", "id='".$assets[$i]["pub_id"]."'", "*" );
				$mag = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
				
				$deny = array( "borito", "beliv", "tablaborito", "vedoborito", "elozek", "melleklet" );
				
				$temp = explode( ".", $assets[$i]["name"] );
				array_pop($temp);
				$tempname = implode( ".", $temp );		
				$tempname = explode("_", $tempname );
				$stripname = array();
				for( $t = 0; $t <= count( $tempname ); $t++ ) {
					if( $t == 0 ) {
						//unset( $tempname[$t] );
						}
					elseif( $t == count($tempname)-1 ) {
						//unset( $tempname[$t] );
						}
					else {
						if( empty( $tempname[$t] ) ) {
							//unset( $tempname[$t] );
							}
						elseif( is_numeric( $tempname[$t] ) ) {
							//unset( $tempname[$t] );
							}
						
						elseif( in_array( $tempname[$t], PARTTYPES ) ) {
							//unset( $tempname[$t] );
							}

						elseif( in_array( $tempname[$t], PARTTYPESEN ) ) {
							//unset( $tempname[$t] );
							}

						elseif( in_array( $tempname[$t], $deny ) ) {
							//unset( $tempname[$t] );
							}

						elseif( $pub[0]["code"] == $tempname[$t] ) {
							//unset( $tempname[$t] );
							}
							
						elseif( $mag[0]["code"] == $tempname[$t] ) {
							//unset( $tempname[$t] );
							}
							
						else {
							$stripname[] = $tempname[$t];
							}
						}
					}
				
				$tempname = implode( " ", $stripname );
				$name = $tempname;
				}
			}
		else {
			$temp = explode( ".", $assets[$i]["name"] );
			array_pop($temp);
			$name = implode( ".", $temp );
			}
		
		switch( $assets[$i]["type"] ) {
			case "application/binary":
				$type = "Indd file";
				break;
				
			case "application/x-indesign":
			case "web_pack":
				$type = "Image Pack";
				break;
				
			case "application/pdf":
				$type = "PDF";
				break;
				
			default: 
				$type = $assets[$i]["type"];
				break;
			}
		
		$childs = sql_aget( "assets", "parent='".$assets[$i]["id"]."'", "*" );
		$decimalSep = ( $lng == "en" ) ? "." : ",";

		if( empty( $childs[0]["id"] ) ) {
			$size = @filesize( TRKPATH."/assets/".$assets[$i]["pub_id"]."/".$assets[$i]["parent"]."/".$assets[$i]["name"] );
			$sizeLabel = formatAssetSize( $size, $decimalSep );

			$txt .= "<tr data='".$assets[$i]["id"]."'>";
				$txt .= "<td>".$name."</td>";
				$txt .= "<td>".$type."</td>";
				$txt .= "<td>".$date."</td>";
				$txt .= "<td align='right'>".$sizeLabel."</td>";
				$txt .= "<td align='center' data='".$assets[$i]["id"]."' class='noselect circle-color-box'><span class='circle-color ".$assets[$i]["color"]."'></span></td>";
				$txt .= "<td class='noselect' align='right'><span class='assetUtils'>";
					$txt .= "<i onclick='downloadAsset( \"".$assets[$i]["id"]."\" )' class='fas fa-cloud-download-alt'></i>";
					if( $user[0][4] == "6" ) {
						$txt .= "<img onclick='removeAsset( \"".$assets[$i]["id"]."\" )' src='images/x-mark.png' style='float:right; height: 17px; padding-left: 5px; cursor: pointer;'>";
						$txt .= "<img onclick='resendAsset( \"".$assets[$i]["id"]."\" )' src='images/share-arrow.png' style='float:right; height: 17px; padding-left: 5px; cursor: pointer;'>";
						}
				$txt .= "</span></td>";
			$txt .= "</tr>";
			}
		else {
			$size = getPackSize( $assets[$i]["pub_id"], $assets[$i]["id"], $childs );
			$sizeLabel = formatAssetSize( $size, $decimalSep );

			$txt .= "<tr data='".$assets[$i]["id"]."'>";
				$txt .= "<td><span onclick='packInside(\"".$assets[$i]["id"]."\")' style='cursor: pointer;'>".$name."</span></td>";
				$txt .= "<td>".$type."</td>";
				$txt .= "<td>".$date."</td>";
				$txt .= "<td align='right'>".$sizeLabel."</td>";
				$txt .= "<td align='center' data='".$assets[$i]["id"]."' class='noselect circle-color-box'><span class='circle-color ".$assets[$i]["color"]."'></span></td>";
				$txt .= "<td class='noselect' align='right'><span class='assetUtils'>";
					$txt .= "<i onclick='downloadAsset( \"".$assets[$i]["id"]."\" )' class='fas fa-cloud-download-alt'></i>";
					if( $user[0][4] == "6" ) {
						$txt .= "<img onclick='removeAsset( \"".$assets[$i]["id"]."\" )' src='images/x-mark.png' style='float:right; height: 17px; padding-left: 5px; cursor: pointer;'>";
						$txt .= "<img onclick='resendAsset( \"".$assets[$i]["id"]."\" )' src='images/share-arrow.png' style='float:right; height: 17px; padding-left: 5px; cursor: pointer;'>";
						}
				$txt .= "</span></td>";
			$txt .= "</tr>";			
			}
		}

	// Archived package - not a DB row (see cleanupPublicationRemnants()'s
	// on-disk-only handling of it), so it isn't covered by the query above
	// at all. Appended once regardless of the PRE/BASIC/FIN alter tab, since
	// none of those apply to a finished archive. status=="archived" is set
	// by archive_results-handler.php once Switch's upload succeeds; the
	// is_dir() check guards the (should be rare, logged elsewhere) case
	// where that flipped before the directory actually showed up.
	$pub = sql_aget( "publications", "id='".$_GET["pub"]."'", "*" );
	if( !empty( $pub[0]["id"] ) && $pub[0]["status"] == "archived" ) {
		$magazine = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
		$archivePath = findArchivePath( $magazine[0]["code"], $pub[0]["code"] );

		if( $archivePath !== null ) {
			$archiveName = basename( $archivePath );

			$archiveTime = @filemtime( $archivePath );
			$archiveDate = date( "d F Y, H:i", $archiveTime );
			if( date( "Y-m-d" ) == date( "Y-m-d", $archiveTime ) ) {
				$archiveDate = "Today, ".date( "H:i", $archiveTime );
				}
			if( date( "Y-m-d", strtotime( "-1 day" ) ) == date( "Y-m-d", $archiveTime ) ) {
				$archiveDate = "Yesterday, ".date( "H:i", $archiveTime );
				}

			$decimalSep = ( $lng == "en" ) ? "." : ",";
			$sizeLabel = formatAssetSize( getArchiveSize( $pub[0]["id"], $archivePath ), $decimalSep );

			$txt .= "<tr data='".$pub[0]["id"]."'>";
				$txt .= "<td>".$archiveName."</td>";
				$txt .= "<td>Archive</td>";
				$txt .= "<td>".$archiveDate."</td>";
				$txt .= "<td align='right'>".$sizeLabel."</td>";
				$txt .= "<td align='center' data='".$pub[0]["id"]."' class='noselect circle-color-box'><span class='circle-color'></span></td>";
				$txt .= "<td class='noselect' align='right'><span class='assetUtils'>";
					$txt .= "<i onclick='downloadArchive( \"".$pub[0]["id"]."\" )' class='fas fa-cloud-download-alt'></i>";
					// No delete icon for archives - unlike a stray uploaded
					// asset, there's nothing here to accidentally clean up
					// one-off; the whole package is only ever removed as
					// part of the job/publication delete flow
					// (cleanupPublicationRemnants()).
					if( $user[0][4] == "6" ) {
						$txt .= "<img onclick='resendAsset( \"".$pub[0]["id"]."\" )' src='images/share-arrow.png' style='float:right; height: 17px; padding-left: 5px; cursor: pointer;'>";
						}
				$txt .= "</span></td>";
			$txt .= "</tr>";
			}
		}

	//$txt = "pub_id='".$_GET["pub"]."' ".( $_GET["alter"] != "ALL" ? "AND fp='".$_GET["alter"]."'" : "" )." AND parent='0'";
	$result = $txt;
	}

print json_encode( $result );
?>