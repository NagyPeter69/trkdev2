<?
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');
	
include_once('../lang/en.php');
	
include_once( '../../engine/xml_handler.php' );

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

function PDFtoImage( $sizes, $to ) {
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
		
	$command = './r3 -binary -mode:RENDER -left:'.$sizes["left"].' -right:'.$sizes["right"].' -bottom:'.$sizes["bottom"].' -top:'.$sizes["top"].' -width:'.$sizes["width"].' -colors:'.$color.' -height:'.$sizes["height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$from.' $@ >'.$to.' 2>&1';
	
	error_log( $command );
	logToFile( 'pageGenerate.txt' , $command );
	shell_exec('
			cd /var/www/html/r3API/r3 2>&1;
			'.$command.';
			');
	return $command;
	}


if( $_GET['op'] == "loaddiff" ) {
	$terminalPath = "/var/www/html/client";
	$a = $terminalPath."/".substr( $_GET["a"], 3 );
	$b = $terminalPath."/".substr( $_GET["b"], 3 );
	$trimbox = $_POST["trimbox"];
	$cropbox = $_POST["cropbox"];
	
	$user = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );
	if( !empty($_GET["visitor"] ) ) {
		$user[0][15] = "trimbox";
		}
		
	$to = TRKPATH."/engine/r3/".$user[0][0]."_diff_".time().".png";
	
	switch( $_GET["type"] ) {
		case 'trimbox':
			$sizes = array(
				"left" => $trimbox["Left"] - $cropbox["Left"],
				"bottom" => $trimbox["Bottom"] - $cropbox["Bottom"],
				"right" => $trimbox["Right"] - $cropbox["Left"],
				"top" => $trimbox["Top"] - $cropbox["Bottom"]
				);
			break;
		case 'mediabox':
			$sizes = array(
				"left" => $trimbox["Left"] - 28.3464567 - $cropbox["Left"],
				"bottom" => $trimbox["Bottom"] - 28.3464567 - $cropbox["Bottom"],
				"right" => $trimbox["Right"] + 28.3464567 - $cropbox["Left"],
				"top" => $trimbox["Top"] + 28.3464567 - $cropbox["Bottom"]
				);
			break;
		}
	$sizes['width'] = ceil( pixel__( $sizes['right'] - $sizes['left'], 100 ) )+1;
	$sizes['height'] = ceil( pixel__($sizes['top'] - $sizes['bottom'], 100 ) );	
	
	$command = './r3 -mode:COMPARE -other_left:'.$sizes["left"].' -other_bottom:'.$sizes["bottom"].' -left:'.$sizes["left"].' -bottom:'.$sizes["bottom"].' -right:'.$sizes["right"].' -top:'.$sizes["top"].' -width:'.$sizes["width"].' -height:'.$sizes["height"].' '.$a.' '.$b.' $@ > '.$to.' 2>&1';
	error_log( "Difference" );
	error_log( $command );
	
	$debug = shell_exec('
			cd /var/www/html/r3API/r3 2>&1;
			'.$command.';
			');
	
	$imgData = base64_encode(file_get_contents( $to ) );
	$imgData = 'data:'.mime_content_type( $to ).';base64,'.$imgData;
	//@unlink( "r3/".$to );

	$result = $imgData;
	}

if( $_GET['op'] == 'render' ) {
	$zoom = $_GET['zoom'];
	$colors = $_POST['colors'];
	$cbox = $_POST['cBox'];
	$terminalPath = "/var/www/html/client";
	$user = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );
	if( !empty($_GET["visitor"] ) ) {
		$user[0][15] = "trimbox";
		}
	
	$from = $_POST['file']['Name'];
	$to = $_GET["to"]."_".$_GET['intra_user'].".jpg";
	$sizes = $_POST['positions'];

	if( $_GET["nr"] <= 1 ) {
		$difference = floatval( str_replace( ",", ".", $sizes['right'] ) ) - ( floatval( str_replace( ",", ".", $_POST['file']['Right'] ) ) );
		$sizes['right'] = floatval( str_replace( ",", ".", $_POST['file']['Right'] ) );
		$temp = pixel__( $difference );
		}
		
	if( $sizes['small'] == "false" ) {
		if( $user[0][15] == "trimbox" ) {
			$sizes['width'] -= $temp;
			}
		else {
			$sizes['left'] += floatval( str_replace( ",", ".", $cbox['Left'] ) );
			$sizes['right'] += floatval( str_replace( ",", ".", $cbox['Left'] ) );	
			if( $_GET["nr"] <= 1 ) {
				$sizes['width'] -= $temp;
		
				}
				
			if( $_POST['corr']["Left"] < 0 ) {
				$sizes["left"] = $sizes["left"]+$_POST['corr']["Left"];
				$sizes["right"] = $sizes["right"]-$_POST['corr']["Left"];	
				}
			}
		}
	
	if( $_GET["cMode"] == "SideBySide" ) {
		$sizes['width'] = $_POST["positions"]["width"];
		$sizes['height'] = $_POST["positions"]["height"];
		}
	
	error_log( "FROM: ".$from );
	$debug = PDFtoImage( $sizes, "/var/www/html/client/engine/r3/".$to );
	$first = new Imagick( "r3/".$to );
	$image = new Imagick();
	$defHeight = pixel__( ($_POST['file']["Top"]-$_POST['file']["Bottom"]), 100 );
	$zoomHeight = pixel__( ($_POST['file']["Top"]-$_POST['file']["Bottom"]) );

	if( $zoomHeight < $_POST['fpBox']['Height'] ) {
		$sizes['height'] = $zoomHeight;
		}

	$image->newImage( ($sizes['width']), $sizes['height'], new ImagickPixel('rgb( 178, 178, 178 )') );
		$icc_rgb = file_get_contents( "r3/sRGB_Color_Space_Profile.icc" );
		$image->profileImage('icc', $icc_rgb);
		$image->setImageFormat('jpg');
		$image->compositeImage($first, $first->getImageCompose(), 0, 0); 
	$image->writeImage("r3/".$to); 

	$imgData = base64_encode(file_get_contents( "r3/".$to ) );
	$imgData = 'data:'.mime_content_type( "r3/".$to ).';base64,'.$imgData;
	//@unlink( "r3/".$to );
	$debug = $temp;

	$width = floatval( str_replace( ",", ".", $cbox['Right'] ) ) - floatval( str_replace( ",", ".", $cbox['Left'] ) );
	$result = $imgData;
	}

if( $_GET['op'] == 'loadbg' ) {
	$user = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );	
	
	if( !empty($_GET["visitor"] ) ) {
		$user[0][15] = "trimbox";
		}
	
	$colors = $_POST['colors'];

	$bgDPI = 72;
	$file = array();
	$pages = 1;
	
	$file2 = $_POST["file"];
	
	if( is_file( $file2 ) ) {
		$file[0]["Name"] = TRKPATH."/".str_replace("../", "", $file2);
		$file[0]["Path"] = substr( $file2, 3 );
		$sizes = getBBox( $file2, "" );
		$file[0]["Right"] = $sizes['Right'];
		$file[0]["Top"] = $sizes['Top'];
		$file[0]["Width"] = $sizes['Width'];
		$file[0]["Height"] = $sizes['Height'];
		$file[0]["Left"] = 0;
		$file[0]["Bottom"] = 0;
		}

    $terminalPath = "/var/www/html/client";
    $dcolors = getColors( $terminalPath."/".$file[0]["Path"] );
	$dtitles = getColorTitles( $terminalPath."/".$file[0]["Path"] );
     
	$postfix = "comp_".$_GET['intra_user'];

	$correctionBox[2] = $correctionBoxTemp = $user[0][15];
	$box = getPDFBox2( "Mediabox Trimbox Cropbox Bleedbox", $file[0]["Name"] );
	$differences = array(
		"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
		"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
		"Right" => ( $box["Mediabox"][2] - $box["Cropbox"][2] ),
		"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
		);
	
	switch( $correctionBoxTemp ) {
		case 'mediabox';
			$sizes = array(
				"Left" => $box["Trimbox"][0] - 28.3464567 - $box["Cropbox"][0],
				"Bottom" => $box["Trimbox"][1] - 28.3464567 - $box["Cropbox"][1],
				"Right" => $box["Trimbox"][2] + 28.3464567 - $box["Cropbox"][0],
				"Top" => $box["Trimbox"][3] + 28.3464567 - $box["Cropbox"][1]
				);
		
			$correctionBox[0] = $differences;
			
			if( $_GET["pages"] == "2" && $_GET['state'] == "state_a" ) {
				$sizes["Right"] = $box["Trimbox"][2] - $box["Cropbox"][0];
				}
			
			$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
			$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
			$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );
			break;
		
		case 'trimbox';
			$sizes = array(
				"Left" => $box["Trimbox"][0] - $box["Cropbox"][0],
				"Bottom" => $box["Trimbox"][1] - $differences['Bottom'] - $box["Cropbox"][1],
				"Right" => $box["Trimbox"][2] - $differences['Right'] - $box["Cropbox"][0],
				"Top" => $box["Trimbox"][3] - $differences['Top'] - $box["Cropbox"][1]
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
			$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );
			break;
		}
	$correctionBox[1] = array(
		"Left" => "",
		"Bottom" => "",
		"Right" => "",
		"Top" => ""
		);

	$crop[0] = array(
		"Left" => $box["Cropbox"][0],
		"Bottom" => $box["Cropbox"][1],
		"Right" => $box["Cropbox"][2],
		"Top" => $box["Cropbox"][3]
		);	
		
	$trim[0] = array(
		"Left" => $box["Trimbox"][0],
		"Bottom" => $box["Trimbox"][1],
		"Right" => $box["Trimbox"][2],
		"Top" => $box["Trimbox"][3]
		);

	$bleed[0] = array(
		"Left" => $box["Bleedbox"][0],
		"Bottom" => $box["Bleedbox"][1],
		"Right" => $box["Bleedbox"][2],
		"Top" => $box["Bleedbox"][3]
		);
			
	$sizes['Width'] = pixel__( $sizes['Width'], $bgDPI );
	$sizes['Height'] = pixel__( $sizes['Height'], $bgDPI );
	
	$temp = $file[0]["Path"];
	$temp2 = $file[0]["Name"];
	$file[0] = $sizes;
	$file[0]["Path"] = $temp;
	$file[0]["Name"] = $temp2;
	
	$debug = PDFtoImage_( $sizes, $terminalPath."/".$file[0]["Path"], "_bg".$postfix.".jpg", $colors );
	$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );
	$first = new Imagick( "r3/_bg".$postfix.".jpg" );
	$image = new Imagick();
	$image->newImage( pixel__( $fullSizes, $bgDPI ), pixel__( $sizes['Top'] , $bgDPI ), new ImagickPixel('rgb( 178, 178, 178 )') );
		$icc_rgb = file_get_contents( "r3/sRGB_Color_Space_Profile.icc" );
		$image->profileImage('icc', $icc_rgb);			
		$image->setImageFormat('jpg');
		$image->compositeImage($first, $first->getImageCompose(), 0, 0);
	$image->writeImage( "r3/bg".$postfix.".jpg" );

	$imgData = base64_encode(file_get_contents( "r3/bg".$postfix.".jpg" ) );
	$imgData = 'data:'.mime_content_type( "r3/bg".$postfix.".jpg" ).';base64,'.$imgData;
	//@unlink( "r3/_bg".$postfix.".jpg" );
	//@unlink( "r3/bg".$postfix.".jpg" );	
	
			
	if( $_POST['switchTo'] != "" ) {
		$newsize = $fullSizes."x".$sizes['Height'];
		$cbox = $correctionBox;
		$file[0]["Width"] = pixel__( $file[0]["Width"], 100 );
		$file[0]["Height"] = pixel__( $file[0]["Height"], 100 );
		if( $file[1] == "" ) {
			$file[1] = array( "Bottom" => '', "Left" => '', "Name" => '', "Path" => '', "Right" => '', "Top" => '', "Width" => '', "Height" => '' );
			}
		else {
			$file[1]["Width"] = pixel__( $file[1]["Width"], 100 );
			$file[1]["Height"] = pixel__( $file[1]["Height"], 100 );
			}
		}
	else {
		$newsize = '';
		$cbox = $correctionBox;
		}
			
	$result = array( $imgData, $newsize, $cbox, $file, $pageID, $text, implode( "-", $numb ), array( $prev_link, $next_link ), $fpPages, $sizes['Top'], $dcolors, $trim, $ver, $dtitles, $bleed, $crop );	
	}

if( $_GET['op'] == 'loadpanel' ) {
	$txt = "<div style='font-size: 12px; margin-top: 12px; color: #FFF;'>Compare</div><table class='compareTable' width='194px' cellspacing='0' cellpadding='0' style='color: #FFF; font-size: 12px; margin-bottom: -1px; margin-left: -10px; padding-left: 10px; padding-right: 10px; padding-top: 4px; padding-bottom: 10px;'>";
	$pub = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
	$mag = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
	
	$page = sql_aget( "pageinfo", "state='".$_POST["files"][0]["State"]."' AND code='".$mag[0]["code"]."' AND issue='".$pub[0]["code"]."' AND type".( $_GET['alter'] == "PRE" ? "='PRE'" : "!= 'PRE'" )." AND `page`='".$_GET['page']."'".( $_GET['alter'] == "FIN" ? " AND fin='1'" : " AND fin='0'" ), "*" );
	$file = str_pad(intval( $_GET['page'] ), 3, '0', STR_PAD_LEFT)."_".$page[0]["pack_id"]."_".( $page[0]["type"] == "ad"? "ad_" :"" )."".( $_POST["files"][0]["State"] != ""? $_POST["files"][0]["State"] :"" )."preview.pdf";
	$pack = sql_aget( "packages", "id='".$page[0]["pack_id"]."'", "*" );
	
	
	$path = "../packages/".$mag[0]["code"]."/".$pub[0]["code"]."/_old".( $_GET['alter'] == "FIN" ? "/FIN" : ( $_GET['alter'] == "PRE" ? "/_PRE" : "" ) );
	if( $page[0]["type"] == "ad" ) {
		$orig_path = "../packages/".$mag[0]["code"]."/".$pub[0]["code"]."/_ads";
		}
	elseif( $page[0]["type"] == "PRE" ) {
		$orig_path = "../packages/".$mag[0]["code"]."/".$pub[0]["code"]."/_PRE";
		}		
	else {
		$orig_path = "../packages/".$mag[0]["code"]."/".$pub[0]["code"]."/".$pack[0]["directory"].( $_GET['alter'] == "FIN" ? "/FIN" : "" );
		}
	
	$files = load_dir_files( $path, str_pad(intval( $_GET['page'] ), 3, '0', STR_PAD_LEFT)."_" );
	$vers = array();
	for( $i = 0; $i < count( $files ); $i++ ) {
		if( strpos( $files[$i], ".pdf" ) !== FALSE ) {
			if( $_POST["files"][0]["State"] != "" && strpos( $files[$i], $_POST["files"][0]["State"] ) !== FALSE ) {
				$vers[] = $files[$i];
				}
			elseif( $_POST["files"][0]["State"] == "" ) {
				$vers[] = $files[$i];
				}
			}		
		}
	sort( $vers );
	
	if( $_GET["pages"] == "2" ) {
		$txt .= "<tr>";
			$txt .= "<td class='compareTable' colspan='2' width='50px'>Left Side</td>";
		$txt .= "</tr>";
		}
	$txt .= "<tr>";
		$txt .= "<td class='compareTable' width='50px' style='color: rgb( 200, 200, 200 );'>State A</td>";
		$txt .= "<td class='compareTable'>";
			$txt .= "<select class='compare_selector' onchange='compareGetPages()' name='state_a' style='font-size: 13px;'>";
				$txt .= "<option selected value='".$orig_path."/".$file."'>Current (v".$page[0]["version"].")</option>";
				$c = 1;
				for( $y = count( $vers )-1; $y >= 0; $y-- ) {
					$txt .= "<option value='".$path."/".$vers[$y]."'>Current -".$c." (v".($y+1).")</option>";
					$c++;
					}
			$txt .= "</select>";
		$txt .= "</td>";
	$txt .= "</tr>";

	$txt .= "<tr>";
		$txt .= "<td class='compareTable' style='color: rgb( 200, 200, 200 );'>State B</td>";
		$txt .= "<td class='compareTable'>";
			$txt .= "<select class='compare_selector' onchange='compareGetPages()' name='state_b' style='font-size: 13px;'>";
				$txt .= "<option value='".$orig_path."/".$file."'>Current (v".$page[0]["version"].")</option>";
				$c = 1;
				for( $y = count( $vers )-1; $y >= 0; $y-- ) {
					$txt .= "<option ".( $y == count( $vers )-1 ? "selected" : "" )." value='".$path."/".$vers[$y]."'>Current -".$c." (v".($y+1).")</option>";
					$c++;
					}
			$txt .= "</select>";
		$txt .= "</td>";
	$txt .= "</tr>";
	
	if( $_GET["pages"] == "2" ) {
		$files = load_dir_files( $path, str_pad( (intval( $_GET['page'] )+1), 3, '0', STR_PAD_LEFT)."_" );
		$vers = array();
		for( $i = 0; $i < count( $files ); $i++ ) {
			if( strpos( $files[$i], ".pdf" ) !== FALSE ) {
				if( $_POST["files"][0]["State"] != "" && strpos( $files[$i], $_POST["files"][0]["State"] ) !== FALSE ) {
					$vers[] = $files[$i];
					}
				elseif( $_POST["files"][0]["State"] == "" ) {
					$vers[] = $files[$i];
					}
				}		
			}
		sort( $vers );
		$txt .= "<tr>";
			$txt .= "<td class='compareTable' colspan='2' width='50px' style='padding-top: 10px;'>Right Side</td>";
		$txt .= "</tr>";
		
		$page = sql_aget( "pageinfo", "state='".$_POST["files"][1]["State"]."' AND code='".$mag[0]["code"]."' AND issue='".$pub[0]["code"]."' AND type".( $_GET['alter'] == "PRE" ? "='PRE'" : "!= 'PRE'" )." AND page='".($_GET['page']+1)."'".( $_GET['alter'] == "FIN" ? " AND fin='1'" : " AND fin='0'" ), "*" );
		$pack = sql_aget( "packages", "id='".$page[0]["pack_id"]."'", "*" );
		
		if( $page[0]["type"] == "ad" ) {
			$orig_path = "../packages/".$mag[0]["code"]."/".$pub[0]["code"]."/_ads";
			}
		elseif( $page[0]["type"] == "PRE" ) {
			$orig_path = "../packages/".$mag[0]["code"]."/".$pub[0]["code"]."/_PRE";
			}
		else {
			$orig_path = "../packages/".$mag[0]["code"]."/".$pub[0]["code"]."/".$pack[0]["directory"].( $_GET['alter'] == "FIN" ? "/FIN" : "" );
			}
		$file = str_pad( (intval( $_GET['page'] )+1), 3, '0', STR_PAD_LEFT)."_".$page[0]["pack_id"]."_".( $page[0]["type"] == "ad" ? "ad_" : "" )."preview.pdf";
		$txt .= "<tr>";
			$txt .= "<td class='compareTable' width='50px' style='color: rgb( 200, 200, 200 );'>State A</td>";
			$txt .= "<td class='compareTable'>";
				$txt .= "<select class='compare_selector' onchange='compareGetPages()' name='state_c' style='font-size: 13px;'>";
					$txt .= "<option selected value='".$orig_path."/".$file."'>Current (v".$page[0]["version"].")</option>";
					$c = 1;
					for( $y = count( $vers )-1; $y >= 0; $y-- ) {
						$txt .= "<option value='".$path."/".$vers[$y]."'>Current -".$c." (v".($y+1).")</option>";
						$c++;
						}
				$txt .= "</select>";
			$txt .= "</td>";
		$txt .= "</tr>";
		
		$txt .= "<tr>";
			$txt .= "<td class='compareTable' style='color: rgb( 200, 200, 200 );'>State B</td>";
			$txt .= "<td class='compareTable'>";
				$txt .= "<select class='compare_selector' onchange='compareGetPages()' name='state_d' style='font-size: 13px;'>";
					$txt .= "<option value='".$orig_path."/".$file."'>Current (v".$page[0]["version"].")</option>";
					$c = 1;
					for( $y = count( $vers )-1; $y >= 0; $y-- ) {
						$txt .= "<option ".( $y == count( $vers )-1 ? "selected" : "" )." value='".$path."/".$vers[$y]."'>Current -".$c." (v".($y+1).")</option>";
						$c++;
						}
				$txt .= "</select>";
			$txt .= "</td>";
		$txt .= "</tr>";
		}
	
	$txt .= "<tr>";
		$txt .= "<td class='compareTable' colspan='2' style='padding-top: 10px;'>";
			$txt .= "<select class='comp_operation' onchange='comp_ophandle( $(this).val() )' name='comp_operation' style='font-size: 11px;'>";
				$txt .= "<option value='wipe'>Wipe between images</option>";
				$txt .= "<option value='fade'>Fade between images</option>";
				$txt .= "<option value='BtoA'>Difference over A</option>";
				$txt .= "<option value='AtoB'>Difference over B</option>";
				$txt .= "<option value='SideBySide'>Side-by-Side</option>";
			$txt .= "</select>";
		$txt .= "</td>";
	$txt .= "</tr>";
	$txt .= "<tr>";
		$txt .= "<td class='compareTable' colspan='2'>";
			$txt .= "<div id='fake' class='comp_range comp_range ui-slider ui-slider-horizontal ui-widget ui-widget-content ui-corner-all' style='margin-bottom: 6px;'>";
				$txt .= "<div class='comp_range' id='compRange' style='width: calc(100% - 9px); width: -o-calc(100% - 9px); width: -webkit-calc(100% - 9px); width: -moz-calc(100% - 9px);'></div>";
			$txt .= "</div>";
		$txt .= "</td>";
	$txt .= "</tr>";

	$txt .= "<tr>";
		$txt .= "<td class='compareTable' id='leftText' align='left' style='color: rgb( 200, 200, 200 );'>A</td>";
		$txt .= "<td class='compareTable' id='rightText' align='right' style='color: rgb( 200, 200, 200 );'>B</td>";
	$txt .= "</tr>";
	
	$txt .= "</table>";
	$txt .= "<div style='font-size: 12px; color: #FFF; margin-bottom: -6px;'>Gallery</div>";
	$result = $txt;
	}
	
print json_encode( $result );
	
?>	