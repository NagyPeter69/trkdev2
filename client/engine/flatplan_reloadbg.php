<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');
include_once( 'switchAPI.php' );
include_once('../lang/en.php');

include_once( '../../engine/xml_handler.php' );

if( $_GET["part"] == "undefined" ) {
	$_GET["part"] = "";
	}

$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}
	
function addingNumber( $szamok ) {
	$szamok = str_split( $szamok );
	$osszeg = 0;
	
	foreach( $szamok as $szam ) {
		$osszeg += intval( $szam );
		}
	
	return $osszeg;
	}

function commentMark( $pageInfo, $id ) {
	global $myPublisher;
	
	switch( $pageInfo[6] ) {
		case 'ad':
		case 'magazine':
			$pageType = "NOR";
			break;
		default :
			$pageType = $pageInfo[6];
			break;
		} 

	if( $pageInfo[11] == 1) $pageType = "FIN";
	$comments = sql_get( 'comments', 'pub_id="'.$id.'" AND parent="0" AND page="'.$pageInfo[5].'" AND pageType="'.$pageType.'" AND pageVersion="'.$pageInfo[8].'"', '*' );
	if( count( $comments ) == 0 ) return "";
	
	$result = array( "red" => 0, "green" => 0, "blue"=> 0 );
	foreach( $comments as $comment ) {
		if( $comment[12] == "approved" ) {
			$result["blue"]++;
			}
		else {
			$checkPub = sql_get( 'comments', 'parent="'.$comment[0].'" ORDER BY `id` DESC LIMIT 1', '*' );
			if( count($checkPub) > 0 ) {
				$checker = $checkPub[count($checkPub)-1][10];
				}
			else {
				$checker = $comment[10];
				}
			
			$checkPub = sql_get( 'accounts', 'id="'.$checker.'"', 'publisher' );
			if( $checkPub[0][0] != $myPublisher[0][0] ) {
				$result["red"]++;
				}
			else {
				$result["green"]++;
				}
			}
		}
	
	if( $result["red"] > 0 )
		return "<div class='commentRed'></div>";
	elseif( $result["green"] > 0 )
		return "<div class='commentGreen'></div>";
	else
		return "<div class='commentBlue'></div>";
	}

if( $_GET['op'] == 'reloadbg' ) {
	//$start = microtime(true);
	
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );	
	if( $_POST['switchTo'] != "" && $user[0][26] != 0  ) {
		sql_update( 'accounts', 'cutBox="'.$_POST['switchTo'].'"', 'id="'.$_SESSION['intra_user'].'"' );
		}

	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );	
	if( $user[0][26] == 0 ) {
		$user[0][15] = "trimbox";
		}
	
	if( $_GET["pdfstand"] == "Web" ) {
		$user[0][15] = "trimbox";
		}
	
	$colors = $_POST['colors'];
	
	if( $user[0][14] == 'pair' && $_GET["p"]%2 == 1 && $_GET["p"] != 1 ) {
		$_GET["p"]--;
		}
	$debug = $user[0][15];
	$issue = sql_get( 'publications', 'id="'.$_GET['id'].'" LIMIT 1', '*' );
	$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'" LIMIT 1', '*' );
	
	error_log( "PAGE: ".$_GET["p"] );
	$pages = checkPagePair( $_GET['id'], $_GET["pack_id"], $_GET["p"], $_GET['tag'], $_GET['alter'], "prev", $issue, $magazine, $_GET["part"], $_GET["pageNumbering"] );
	$debug = array_search( $_GET["p"], $pages );
	if( array_search( $_GET["p"], $pages ) === false ) {
		$_GET["p"]++;
		}

	$fpPages = $user[0][14];
	
	if( $user[0][14] == 'single' && count( $pages ) > 1 ) {
		$needle = array_search( $_GET['clk'], $pages );
		$pages = array( $pages[ $needle ] );
		}

	$bgDPI = 72;
	$file = array();
	$state = array();
	$ver = array();
	$pid = array();

	for( $i = 0; $i < count( $pages ); $i++ ) {
		$dir = "../packages/".$magazine[0][3]."/".$issue[0][10];
		$file2 = '';
		$tag = $_GET['tag'];
		if( $_GET['alter'] != "" && $_GET['alter'] != 'FIN' ) {
			$dir .= "/_".strtoupper( $_GET['alter'] );
			if( $_GET['tag'] != "" && $pages[$i] == $_GET['clicked'] ) {
				$tag = $_GET['tag'];
				$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'" AND part="'.$_GET["part"].'"', '*' );
				}
			else {
				$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$pages[$i].'" AND part="'.$_GET["part"].'"', '*' );
				}
			$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
			}
			
		else {
			$fin = ( $_GET['alter'] == "FIN" ? "1" : "0" );
			if( $_GET['tag'] != "" ) {
				$tag = $_GET['tag'];
				$pageinfo = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'" AND fin="'.$fin.'" AND part="'.$_GET["part"].'"', '*' );
				}
			else {
				$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$pages[$i].'" AND state="" AND fin="'.$fin.'" AND part="'.$_GET["part"].'"', '*' );
				}
	
			if( $pageinfo[0][6] == "ad" ) {
				$dir .= "/_ads";
				$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."ad_preview.pdf";
				}
			else {
				$pack = sql_get( 'packages', 'id="'.$pageinfo[0][1].'"', '*' );
				$dir .= "/".$pack[0][4];
				if( $_GET['alter'] == "FIN" ) $dir .= "/FIN";
				$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
				}
			}
		
		$pid[] = $pageinfo[0][0];	
			
		$ver[] = "v".$pageinfo[0][3];
		if( is_file( $dir."/".$file2 ) ) {
			$viewed = explode( ",", $pageinfo[0][10] );
			if( !in_array( $_SESSION['intra_user'], $viewed ) )
				if( $viewed[0] == "" )
					$viewed[0] = $_SESSION['intra_user'];
				else
					$viewed[] =  $_SESSION['intra_user'];
		
			$viewed = implode( ",", $viewed );
			sql_update( 'pageinfo', 'view="'.$viewed.'"', 'id="'.$pageinfo[0][0].'"' );
		
			$file[$i]["Name"] = $dir."/".$file2;
			$file[$i]["Path"] = substr( $dir, 3 )."/".$file2;
			//$sizes = getBBox( $file[$i]["Name"], "" );
			$file[$i]["Right"] = $sizes['Right'];
			$file[$i]["Top"] = $sizes['Top'];
			$file[$i]["Width"] = $sizes['Width'];
			$file[$i]["Height"] = $sizes['Height'];
			$file[$i]["Left"] = 0;
			$file[$i]["Bottom"] = 0;
			$state[$i] = $pageinfo[0][8];
			}
		}
	//$end = microtime(true);	
	//error_log( "Time until for cycle finish: ".rutime($end, $start, "stime")." ms" );	

	$terminalPath = TRKPATH;
	list( $dtitles, $dcolors ) = getAllColors( $pageinfo );
			
	if( $_POST['from'] == "changePic" ) {
	  for( $i = 0; $i < count( $dcolors ); $i++ ) {
	    $colors[($i+1)] = "true";
	    }
	  }
	 
	$postfix = $_SESSION['intra_user'];
	if( count( $pages ) > 1 ) {
		$correctionBox[2] = $correctionBoxTemp = $user[0][15];
		$box = getPDFBox( "Mediabox Trimbox Cropbox Bleedbox", $pageinfo );
		$differences = array(
			"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
			"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
			"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
			"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
			);
		switch( $correctionBoxTemp ) {
			case 'mediabox':
				$sizes = array(
					"Left" => $box["Trimbox"][0] - 28.3464567 - $box["Cropbox"][0],
					"Bottom" => $box["Trimbox"][1] - 28.3464567 - $box["Cropbox"][1],
					"Right" => $box["Trimbox"][2] - $box["Cropbox"][0],
					"Top" => $box["Trimbox"][3] + 28.3464567 - $box["Cropbox"][1]
					);
	
				$differences = array(
					"Left" => ( 0 ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
					"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
					);
	
				$correctionBox[0] = $differences;	
				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
				
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
				$fullSizes = ( $sizes['Width'] );
				break;
			
			case 'trimbox':
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

/*
				$sizes = array(
					"Left" => $box["Trimbox"][0],
					"Bottom" => $box["Trimbox"][1] - $differences['Bottom'],
					"Right" => $box["Trimbox"][2] + $differences['Right'],
					"Top" => $box["Trimbox"][3] - $differences['Top']
					);
	
				$differences = array(
					"Left" => ( $box["Cropbox"][0] - $box["Trimbox"][0] ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
					"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
					);
*/
						
				$correctionBox[0] = $differences;	
	
				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
				$fullSizes = ( $sizes['Width'] );
				break;
			}
			
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
	
		$sizes['Width'] = pixel_( $sizes['Width'], $bgDPI );
		$sizes['Height'] = pixel_( $sizes['Height'], $bgDPI );
		
		$temp = $file[0]["Path"];
		$temp2 = $file[0]["Name"];
		$file[0] = $sizes;
		$file[0]["State"] = $state[0];
		$file[0]["Path"] = $temp;
		$file[0]["Name"] = $temp2;
	
		$correctionBox[2] = $correctionBoxTemp = $user[0][15];
		$box = getPDFBox( "Mediabox Trimbox Cropbox Bleedbox", $pageinfo );
		$differences = array(
			"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
			"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
			"Right" => ( $box["Bleedbox"][2] - $box["Trimbox"][2] ),
			"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
			);
		switch( $correctionBoxTemp ) {
			case 'mediabox':
				$sizes = array(
					"Left" => $box["Trimbox"][0] - $box["Cropbox"][0],
					"Bottom" => $box["Trimbox"][1] - 28.3464567 - $box["Cropbox"][1],
					"Right" => $box["Trimbox"][2] + 28.3464567 - $box["Cropbox"][0],
					"Top" => $box["Trimbox"][3] + 28.3464567 - $box["Cropbox"][1]
					);
	
				$differences = array(
					"Left" => ( 0 ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
					"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Cropbox"][3] - $box["Trimbox"][3] )
					);
				
				$correctionBox[1] = $differences;	
				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
				$fullSizes = ( $sizes['Width'] );
				break;
			
			case 'trimbox':
				$sizes = array(
					"Left" => $box["Trimbox"][0],
					"Bottom" => $box["Trimbox"][1] - $differences['Bottom'],
					"Right" => $box["Trimbox"][2] - $box["Cropbox"][0],
					"Top" => $box["Trimbox"][3] - $differences['Top']
					);
	
				$differences = array(
					"Left" => ( $box["Cropbox"][0] - $box["Trimbox"][0] ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
					"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
					);
	
				$correctionBox[1] = $differences;	
	
				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
				$fullSizes = ( $sizes['Width'] );
				break;
			}
	
		$crop[1] = array(
			"Left" => $box["Cropbox"][0],
			"Bottom" => $box["Cropbox"][1],
			"Right" => $box["Cropbox"][2],
			"Top" => $box["Cropbox"][3]
			);
	
		$trim[1] = array(
			"Left" => $box["Trimbox"][0],
			"Bottom" => $box["Trimbox"][1],
			"Right" => $box["Trimbox"][2],
			"Top" => $box["Trimbox"][3]
			);
	
		$bleed[1] = array(
			"Left" => $box["Bleedbox"][0],
			"Bottom" => $box["Bleedbox"][1],
			"Right" => $box["Bleedbox"][2],
			"Top" => $box["Bleedbox"][3]
			);
			
		$sizes['Width'] = pixel_( $sizes['Width'], $bgDPI );
		$sizes['Height'] = pixel_( $sizes['Height'], $bgDPI );
		
		$temp = $file[1]["Path"];
		$temp2 = $file[1]["Name"];
		$file[1] = $sizes;
		$file[1]["State"] = $state[0];
		$file[1]["Path"] = $temp;
		$file[1]["Name"] = $temp2;
		
		$fullSizes = ( $file[0]["Width"] )+( $file[1]["Width"]) ;
		}		
	else {
		$correctionBox[2] = $correctionBoxTemp = $user[0][15];
		$box = getPDFBox( "Mediabox Trimbox Cropbox Bleedbox", $pageinfo );
		
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
				
		$sizes['Width'] = pixel_( $sizes['Width'], $bgDPI );
		$sizes['Height'] = pixel_( $sizes['Height'], $bgDPI );
		
		$temp = $file[0]["Path"];
		$temp2 = $file[0]["Name"];
		$file[0] = $sizes;
		$file[0]["State"] = $state[0];
		$file[0]["Path"] = $temp;
		$file[0]["Name"] = $temp2;
		
		$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );
		}
			
	if( $_POST['switchTo'] != "" ) {
		$newsize = $fullSizes."x".$sizes['Height'];
		$cbox = $correctionBox;
		$file[0]["Width"] = pixel_( $file[0]["Width"], 100 );
		$file[0]["Height"] = pixel_( $file[0]["Height"], 100 );
		if( $file[1] == "" ) {
			$file[1] = array( "Bottom" => '', "Left" => '', "Name" => '', "Path" => '', "Right" => '', "Top" => '', "Width" => '', "Height" => '' );
			}
		else {
			$file[1]["Width"] = pixel_( $file[1]["Width"], 100 );
			$file[1]["Height"] = pixel_( $file[1]["Height"], 100 );
			}
		}
	else {
		$newsize = '';
		$cbox = '';
		}
	
	$pageID = $text = array();
	$status = checkPageStatus( $pages[0], $_GET['id'], $_GET["pack_id"], $_GET['alter'], $_GET['tag'], $issue, $magazine, $_GET["part"] );
	$pageID[0] = $status[1];
	switch( $status[0] ) {
		case 2:
			if( $rights["cancelApprove"] ) {
				$text[0] = "<div id='pstatus' style='width: 125px; height: 35px;'>
								<div style='position: absolute; left: 0px; cursor: pointer; float: left; color: rgb( 1, 188, 0 ) !important;'>".$lang["flatplan"]["approved"]."</div>
	
								<div id='".$pageID."_acc' style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove.png'></div>
								<div id='".$pageID."_acc_hover' style='display: none; position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove_hover.png'></div>
							</div>";		
				}
				
			else {
				$text[0] = "<div style='color: rgb( 1, 188, 0 ) !important;'>".$lang["flatplan"]["approved"]."</div>";
				}
			break;
		case 3:
			$text[0] = "<div style='color: rgb( 254, 0, 3 ) !important;'>".$lang["flatplan"]["rejected"]."</div>";
			break;
		default:
			$text[0] = "<div id='pstatus' style='width: 130px; height: 35px;'>";
				if( $rights["acceptPage"] ) {
					$text[0] .= "<div style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>";
					$text[0] .= "<div style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>";
				
					$text[0] .= "<div style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
					$text[0] .= "<div style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
					}
			$text[0] .= "</div>";
			break;
		}		
	
	if( count( $pages ) > 1 ) {
		$status = checkPageStatus( $pages[1], $_GET['id'], $_GET["pack_id"], $_GET['alter'], $_GET['tag'], $issue, $magazine, $_GET["part"] );
		$pageID[1] = $status[1];
		switch( $status[0] ) {
			case 2:
				if( $rights["cancelApprove"] ) {
					$text[1] = "<div id='pstatus' style='width: 125px; height: 35px;'>
									<div style='position: absolute; left: 0px; cursor: pointer; float: left; color: rgb( 1, 188, 0 ) !important;'>".$lang["flatplan"]["approved"]."</div>
	
									<div id='".$pageID."_acc' style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove.png'></div>
									<div id='".$pageID."_acc_hover' style='display: none; position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove_hover.png'></div>
								</div>";		
					}
				
				else {
					$text[1] = "<div style='color: rgb( 1, 188, 0 ) !important;'>".$lang["flatplan"]["approved"]."</div>";
					}
				break;
			case 3:
				$text[1] = "<div style='color: rgb( 254, 0, 3 ) !important;'>".$lang["flatplan"]["rejected"]."</div>";
				break;
			default:
				$text[1] = "<div style='width: 130px;'>";
					if( $rights["acceptPage"] ) {
						$text[1] .= "<div style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>";
						$text[1] .= "<div style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>";
				
						$text[1] .= "<div style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
						$text[1] .= "<div style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
						}
				$text[1] .= "</div>";
				break;
			}
		}
	
	//error_log( "PART: ".$_GET["part"] );
	
	if( $_GET['alter'] != '' and $_GET['alter'] != 'FIN' ) {
		//error_log( 'type="'.$_GET['alter'].'"AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" ORDER BY CAST(`page` AS SIGNED) ASC' );
		$packs = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'"AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND part="'.$_GET["part"].'" ORDER BY CAST(`page` AS SIGNED) ASC', '*' );
		}
	else {
		$fin = ( $_GET['alter'] == 'FIN' ? "1" : "0" );
		//error_log( 'type!="PRE" AND type!="PSTR" AND state="'.$_GET['tag'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND fin="'.$fin.'" ORDER BY CAST(`page` AS SIGNED) ASC' );
		$packs = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND state="'.$_GET['tag'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND fin="'.$fin.'" AND part="'.$_GET["part"].'" ORDER BY CAST(`page` AS SIGNED) ASC', '*' );
		}
	
	$pages2 = array();
	$ad_pages = array();
	for( $i = 0; $i < count( $packs ); $i++ ) {		
		$checker = "../packages/".$magazine[0][3]."/".$issue[0][10];
		if( $_GET['alter'] != '' && $_GET['alter'] != 'FIN' ) {
			$checker .= "/_".$_GET['alter']."/".str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1]."_preview.pdf";
			}
		elseif( $packs[$i][6] == "ad" ) {
			$checker .= "/_ads/".str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1]."_ad_preview.pdf";
			}
		else {
			$pack = sql_get( "packages", "id='".$packs[$i][1]."'", "directory" );
			if( $_GET['alter'] == 'FIN' ) {
				$checker .= "/".$pack[0][0]."/FIN/".str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1]."_preview.pdf";
				}
			else {
				$checker .= "/".$pack[0][0]."/".str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1]."_preview.pdf";
				}
			}
		
		
		
		$debugarray[] = $checker;
		if( is_file( $checker ) ) {
			
			$pages2[] = str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1];
			if( $packs[$i][6] == 'ad' ) {
				$ad_pages[] = $packs[$i][5];
				}
			}	
		}
		
	$debug = $debugarray;
	sort( $pages2 );
	//error_log( "DEBUG" );
	//error_log( print_r( $pages2, true) );
	$needle = array_search( str_pad( $_GET['p'] , 3, '0', STR_PAD_LEFT)."_".$_GET['pack_id'], $pages2 );
	//error_log( "needle: ".$needle );
	//error_log( str_pad( $_GET['p'] , 3, '0', STR_PAD_LEFT)."_".$_GET['pack_id']  );
	$prev = 0;
	$prev_id = 0;
	$next = 0;
	$next_id = 0;
	$prev_link = $next_link = "?page=flatlpan_preview";
	if( $needle !== false ) {
		$prev = intval( $pages2[$needle-1] );
		$temp = explode( "_", $pages2[$needle-1] );
		
		$tempSql = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND pack_id="'.$temp[1].'"', '*' );
		if( $user[0][14] == "pair" ) {
			if( $prev % 2 != 0 && $prev > 1 && $tempSql[0][9] == 1 ) {
	  			if( $pages2[$needle-2] == ( $prev-1 ) ) {
					$prev = intval( $pages2[$needle-2] );
					$temp = explode( "_", $pages2[$needle-2] );
					}
				}
			}
		$prev_id = $temp[1];
		
		$next = intval( $pages2[$needle+1] );
		$temp = explode( "_", $pages2[$needle+1] );
		$tempSql = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND pack_id="'.$temp[1].'"', '*' );
		if( $user[0][14] == "pair" ) {
			if( $next == ( $pages2[$needle]+1 ) && $next % 2 != 0 && $tempSql[0][9] == 1 ) {
	  			$next = intval( $pages2[$needle+2] );
	  			$temp = explode( "_", $pages2[$needle+2] );
	  			}
			}
		$next_id = $temp[1];
		}
		
	if( $prev != 0 ) {
		$needle = array_search( $prev, $ad_pages );
		if( $needle !== false ) {
			$prev_link .= "&type=ad";
			}
		if( $_GET['alter'] != '' )  {
			$prev_link .= "&alter=".$_GET['alter'];
			}
	
		$prev_link .= "&id=".$_GET['id']."&p=".$prev."&clk=".$prev."&pack_id=".$prev_id;
		}
		
	if( $next != 0 ) {
		$needle = array_search( $next, $ad_pages );
		if( $needle !== false ) {
			$next_link .= "&type=ad";
			}
		if( $_GET['alter'] != '' )  {
			$next_link .= "&alter=".$_GET['alter'];
			}
	
		$next_link .= "&id=".$_GET['id']."&p=".$next."&clk=".$next."&pack_id=".$next_id;
		}	

	$numb = array();
	for( $i = 0; $i < count( $pages ); $i++ ) {
		$txt = $pages[$i];
		$alterCode = "";
		for( $y = 0; $y < count( $packs ); $y++ ) {
			if( $packs[$y][5] == $pages[$i] ) {
				$alterCode = substr( $packs[$y][8], 0, -1);
				break;
				}
			}	
		
		if( $alterCode != "") $txt .= "[".$alterCode."]";
		$numb[] = $txt;
		}
	
	error_log( "PAGE COUNTER: ".count( $pages ) );
	
	switch( $user[0][15] ) {
		case "mediabox":
			if( count( $pages ) == "1" ) {
				$imgData = str_replace( ".pdf", "-cropbox.jpg", $file[0]["Name"] );
				if( !is_file( $imgData ) ) {
					PDF_prework( $pid[0] );
					}
				
				$imgData = file_get_contents($imgData);
				$imgData = base64_encode($imgData);
				}
			else {
				if( !is_file( str_replace( ".pdf", "-cropbox.jpg", $file[0]["Name"] ) ) ) {
					PDF_prework( $pid[0] );
					}
				$first = new Imagick( str_replace( ".pdf", "-cropbox.jpg", $file[0]["Name"] ) );
				$width = $first->getImageWidth();
				$first->cropImage( $first->getImageWidth() - $trim[0]["Left"], $first->getImageHeight() , 0, 0 );
				$first->resizeImage( $width, $first->getImageHeight(), imagick::FILTER_CATROM, 1, false);
				
				if( !is_file( str_replace( ".pdf", "-cropbox.jpg", $file[1]["Name"] ) ) ) {
					PDF_prework( $pid[1] );
					}				
				$second = new Imagick( str_replace( ".pdf", "-cropbox.jpg", $file[1]["Name"] ) );
				$width = $second->getImageWidth();
				$second->cropImage( $second->getImageWidth() - $trim[1]["Left"], $second->getImageHeight() , $trim[1]["Left"], 0 );
				$second->resizeImage( $width, $second->getImageHeight(), imagick::FILTER_CATROM, 1, false);				
				
				$image = new Imagick();
				$image->newImage( ( $first->getImageWidth() + $second->getImageWidth() ), $first->getImageHeight(), new ImagickPixel('rgb( 178, 178, 178 )') );
					$icc_rgb = file_get_contents( "r3/sRGB_Color_Space_Profile.icc" );
					$image->profileImage('icc', $icc_rgb);
					$image->setImageFormat('jpg');
					$left = $file[0]["Right"]+$file[1]["Left"];
					$image->compositeImage($second, $second->getImageCompose(), $left, 0);
					$image->compositeImage($first, $first->getImageCompose(), 0, 0);
				$image->writeImage( "r3/bg".$postfix.".jpg" );
				$imgData = base64_encode(file_get_contents( "r3/bg".$postfix.".jpg" ) );
				@unlink( "r3/bg".$postfix.".jpg" );		
				error_log( "FIRST: ".$file[0]["Name"] );
				}
			break;
			
		case "trimbox":
			if( count( $pages ) == "1" ) {
				$imgData = str_replace( ".pdf", "-trimbox.jpg", $file[0]["Name"] );
				if( !is_file( $imgData ) ) {
					PDF_prework( $pid[0] );
					}
				$imgData = file_get_contents($imgData);
				$imgData = base64_encode($imgData);
				}
			else {
				if( !is_file( str_replace( ".pdf", "-cropbox.jpg", $file[0]["Name"] ) ) ) {
					PDF_prework( $pid[0] );
					}
				if( !is_file( str_replace( ".pdf", "-cropbox.jpg", $file[1]["Name"] ) ) ) {
					PDF_prework( $pid[1] );
					}	
					
				$first = new Imagick( str_replace( ".pdf", "-trimbox.jpg", $file[0]["Name"] ) );
				$second = new Imagick( str_replace( ".pdf", "-trimbox.jpg", $file[1]["Name"] ) );
				$image = new Imagick();
				$image->newImage( ( $first->getImageWidth() + $second->getImageWidth() ), $first->getImageHeight(), new ImagickPixel('rgb( 178, 178, 178 )') );
					$icc_rgb = file_get_contents( "r3/sRGB_Color_Space_Profile.icc" );
					$image->profileImage('icc', $icc_rgb);
					$image->setImageFormat('jpg');
					$image->compositeImage($first, $first->getImageCompose(), 0, 0);
					$left = $file[0]["Right"]-$file[1]["Left"];
					error_log( "BEILLESZTÉS: ".$left );
					$image->compositeImage($second, $second->getImageCompose(), $left, 0);
				$image->writeImage( "r3/bg".$postfix.".jpg" );
				$imgData = base64_encode(file_get_contents( "r3/bg".$postfix.".jpg" ) );
				//@unlink( "r3/bg".$postfix.".jpg" );
				}
			break;
		}	
	
	//$end = microtime(true);
	//error_log( "time: ".rutime($end, $start, "stime")." ms" );
	
	$result = array( $imgData, $newsize, $cbox, $file, $pageID, $text, implode( "-", $numb ), array( $prev_link, $next_link ), $fpPages, $sizes['Top'], $dcolors, $trim, $ver, $dtitles, $bleed, $crop );	
	}
	
if( $_GET['op'] == 'getFirstPage' ) {
	$issue = sql_get( 'publications', 'id="'.$_GET['id'].'" LIMIT 1', '*' );
	$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'" LIMIT 1', '*' );
	
	switch( $_GET["alter"] ) {
		case "PRE":
			$condition = 'type="PRE" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND fin="0" AND part="'.$_GET["part"].'" ORDER BY `page` ASC LIMIT 1';
			break;
		
		case "FIN": 
			if( $magazine[0][3] == "BAV" ) {
				$condition = '( type="ad" OR type="magazine" ) AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND fin="0" AND part="'.$_GET["part"].'" ORDER BY `page` ASC LIMIT 1';
				}
			else {
				$condition = '( type="ad" OR type="magazine" ) AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND fin="1" AND part="'.$_GET["part"].'" ORDER BY `page` ASC LIMIT 1';
				}
			break;
		
		default: 
			$condition = '( type="ad" OR type="magazine" ) AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND fin="0" AND part="'.$_GET["part"].'" ORDER BY `page` ASC LIMIT 1';
			break;
		}
		
	$firstPage = sql_get( 'pageinfo', $condition, '*' );
	
	$result = $firstPage;
	}
	
print json_encode( $result );
	
?>