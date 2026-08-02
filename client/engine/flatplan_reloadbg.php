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

	// A job with FlatplanStages==1 has only one flatplan - the fin bit
	// some of its pages carry isn't a second stage to resolve this
	// page's render against (see page_pdf-handler.php's $stages1
	// handling and the matching fix in flatplan_preview.php).
	$stages1 = false;
	if( !empty( $magazine[0][3] ) ) {
		$wfXml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
		$wfXpath = $wfXml->xpath('/Publications');
		foreach( $wfXpath as $wfTemp ) {
			for( $wfI = 0; $wfI < count( $wfTemp->Item ); $wfI++ ) {
				if( $wfTemp->Item[$wfI]->Code == $magazine[0][3] )
					break;
				}
			}
		$stages1 = ( (string) $wfXml->Item[$wfI]->FlatplanStages == "1" and (string) $wfXml->Item[$wfI]->Workflow != "Hybrid" );
		}

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
	$preflightErr = array();

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
				if( $stages1 ) {
					$pageinfo = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'" AND part="'.$_GET["part"].'"', '*' );
					}
				else {
					$pageinfo = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'" AND fin="'.$fin.'" AND part="'.$_GET["part"].'"', '*' );
					}
				}
			else {
				if( $stages1 ) {
					$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$pages[$i].'" AND state="" AND part="'.$_GET["part"].'"', '*' );
					}
				else {
					$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$pages[$i].'" AND state="" AND fin="'.$fin.'" AND part="'.$_GET["part"].'"', '*' );
					}
				}

			if( $pageinfo[0][6] == "ad" ) {
				$dir .= "/_ads";
				$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."ad_preview.pdf";
				}
			else {
				$pack = sql_get( 'packages', 'id="'.$pageinfo[0][1].'"', '*' );
				$dir .= "/".$pack[0][4];
				// A 1-stage job's render lives wherever page_pdf-handler.php
				// actually stored it based on this row's own fin (index 11),
				// not wherever the requested $_GET['alter'] would imply.
				if( $stages1 ? $pageinfo[0][11] == 1 : $_GET['alter'] == "FIN" ) $dir .= "/FIN";
				$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
				}
			}
		
		$pid[] = $pageinfo[0][0];
		$preflightErr[] = intval( $pageinfo[0][18] );

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
		$box = boxOrPlaceholder( $box, 'pageinfo id '.( $pageinfo[0][0] ?? '?' ) );
		$differences = array(
			"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
			"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
			"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
			"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
			);
		switch( $correctionBoxTemp ) {
			case 'mediabox':
				$sizes = array(
					"Left" => $box["Mediabox"][0] - $box["Cropbox"][0],
					"Bottom" => $box["Mediabox"][1] - $box["Cropbox"][1],
					"Right" => $box["Trimbox"][2] - $box["Cropbox"][0],
					"Top" => $box["Mediabox"][3] - $box["Cropbox"][1]
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
		$box = boxOrPlaceholder( $box, 'pageinfo id '.( $pageinfo[0][0] ?? '?' ) );
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
					"Bottom" => $box["Mediabox"][1] - $box["Cropbox"][1],
					"Right" => $box["Mediabox"][2] - $box["Cropbox"][0],
					"Top" => $box["Mediabox"][3] - $box["Cropbox"][1]
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
		$box = boxOrPlaceholder( $box, 'pageinfo id '.( $pageinfo[0][0] ?? '?' ) );
		
		$differences = array(
			"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
			"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
			"Right" => ( $box["Mediabox"][2] - $box["Cropbox"][2] ),
			"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
			);
		switch( $correctionBoxTemp ) {
			case 'mediabox';
				$sizes = array(
					"Left" => $box["Mediabox"][0] - $box["Cropbox"][0],
					"Bottom" => $box["Mediabox"][1] - $box["Cropbox"][1],
					"Right" => $box["Mediabox"][2] - $box["Cropbox"][0],
					"Top" => $box["Mediabox"][3] - $box["Cropbox"][1]
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
	
								<div id='".$pageID."_acc' style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove.png'></div>
								<div id='".$pageID."_acc_hover' style='display: none; position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove_hover.png'></div>
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
					$text[0] .= "<div style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>";
					$text[0] .= "<div style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>";
				
					$text[0] .= "<div style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
					$text[0] .= "<div style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
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
	
									<div id='".$pageID."_acc' style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove.png'></div>
									<div id='".$pageID."_acc_hover' style='display: none; position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove_hover.png'></div>
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
						$text[1] .= "<div style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>";
						$text[1] .= "<div style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>";
				
						$text[1] .= "<div style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
						$text[1] .= "<div style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
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
	elseif( $stages1 ) {
		$packs = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND state="'.$_GET['tag'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND part="'.$_GET["part"].'" ORDER BY CAST(`page` AS SIGNED) ASC', '*' );
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
			// A 1-stage job's render lives wherever page_pdf-handler.php
			// actually stored it based on this row's own fin (index 11),
			// not wherever the requested $_GET['alter'] would imply.
			if( $stages1 ? $packs[$i][11] == 1 : $_GET['alter'] == 'FIN' ) {
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
				// $pages2 entries are "PPP_packid" strings (e.g. "006_17"), not
				// plain numbers. PHP 7 compared a non-numeric string like this
				// to an int by extracting its leading numeric run, so
				// "006_17" == 6 was true. PHP 8's "saner string to number
				// comparisons" reversed that: it now stringifies the int
				// instead ("006_17" == "6"), which is always false. That
				// silently broke this pair-mode snap-to-even-page check, so
				// $prev stayed on the odd, unpaired page (right pack_id for
				// THAT page, but not for the even one two spots back in
				// $pages2). The next click's own separate odd-page decrement
				// then normalized the page number but kept that stale
				// pack_id, producing a page/pack_id combination that
				// doesn't exist in $pages2 at all - so the lookup for it
				// failed outright and both prev/next links collapsed to the
				// dead placeholder simultaneously, with no error anywhere.
				// intval() here restores an explicit integer comparison
				// instead of relying on comparison-operator type coercion.
	  			if( intval( $pages2[$needle-2] ) == ( $prev-1 ) ) {
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
			if( $next == ( intval( $pages2[$needle] )+1 ) && $next % 2 != 0 && $tempSql[0][9] == 1 ) {
				// Mirrors the $prev branch's own verification above (see its
				// comment for the full history) - that one already learned
				// the hard way that trusting $pages2[$needle-2] unconditionally
				// can snap to a page/pack_id combination that doesn't actually
				// exist in $pages2 at all, which then makes THIS lookup fail
				// for a later request built from that bad pack_id, collapsing
				// both prev_link and next_link to the dead placeholder at
				// once. This branch never got the equivalent check.
				if( intval( $pages2[$needle+2] ) == ( $next+1 ) ) {
					$next = intval( $pages2[$needle+2] );
					$temp = explode( "_", $pages2[$needle+2] );
					}
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

	// One entry per displayed page (not deduplicated) - #colorStdLabel1/2 each
	// sit next to their OWN page's approve/reject group, so a spread with two
	// different standards needs both values, not a combined string. Only
	// computed when the account has Prepress Tools enabled (accounts.26) -
	// the label elements themselves don't exist in the DOM otherwise, so
	// there's nothing to send this to, and partDetect() isn't free (it loads
	// and parses the issue's XML on every call).
	$colorStdNames = array();
	if( $user[0][26] == 1 ) {
		for( $i = 0; $i < count( $pages ); $i++ ) {
			$colorStdNames[] = partDetect( $_GET['id'], $pages[$i], "color", $_GET["part"] );
			}
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
				// cropImage() removes the spine-side overhang so this page's
				// raster is left at its real trimmed width - deliberately
				// NOT followed by a resizeImage() back to the pre-crop
				// width (as this used to do): that resize silently undid
				// the crop's size reduction (stretching the trimmed content
				// back out to fill the original width), while the
				// composite offset below was computed from the CORRECTLY
				// trimmed $file[]/$trim[] values - so the two pages'
				// on-canvas positions and their actual (untrimmed) raster
				// widths disagreed, most visibly on the right/second page.
				$first->cropImage( $first->getImageWidth() - $trim[0]["Left"], $first->getImageHeight() , 0, 0 );

				if( !is_file( str_replace( ".pdf", "-cropbox.jpg", $file[1]["Name"] ) ) ) {
					PDF_prework( $pid[1] );
					}
				$second = new Imagick( str_replace( ".pdf", "-cropbox.jpg", $file[1]["Name"] ) );
				$second->cropImage( $second->getImageWidth() - $trim[1]["Left"], $second->getImageHeight() , $trim[1]["Left"], 0 );

				$image = new Imagick();
				$image->newImage( ( $first->getImageWidth() + $second->getImageWidth() ), $first->getImageHeight(), new ImagickPixel('rgb( 178, 178, 178 )') );
					$icc_rgb = file_get_contents( "r3/sRGB_Color_Space_Profile.icc" );
					$image->profileImage('icc', $icc_rgb);
					$image->setImageFormat('jpg');
					// $first's own actual (now correctly trimmed) width -
					// not a separately-derived point value - guarantees no
					// gap/overlap regardless of $file[]/$trim[]'s formulas.
					$image->compositeImage($second, $second->getImageCompose(), $first->getImageWidth(), 0);
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
					// $first's own actual width, same reasoning as the
					// mediabox case above - these -trimbox.jpg rasters are
					// already trimmed at generation time, so no cropImage()
					// is needed here, but the placement should still key off
					// the real raster width rather than a separately-derived
					// point value.
					$image->compositeImage($second, $second->getImageCompose(), $first->getImageWidth(), 0);
				$image->writeImage( "r3/bg".$postfix.".jpg" );
				$imgData = base64_encode(file_get_contents( "r3/bg".$postfix.".jpg" ) );
				//@unlink( "r3/bg".$postfix.".jpg" );
				}
			break;
		}	
	
	//$end = microtime(true);
	//error_log( "time: ".rutime($end, $start, "stime")." ms" );
	
	// Appended at the end (index 16 was colorStdNames; 17/18 added later),
	// not inserted among the existing indices - the JS success handler
	// reads this array by fixed numeric position, so anything added has
	// to go after the existing ones. $preflightErr/$pid are per-page
	// (index 0 = left/single page, index 1 = right page of a spread, if
	// any) so the preflight indicator(s) can be refreshed on every AJAX
	// page navigation instead of only reflecting whichever page happened
	// to be loaded first.
	$result = array( $imgData, $newsize, $cbox, $file, $pageID, $text, implode( "-", $numb ), array( $prev_link, $next_link ), $fpPages, $sizes['Top'], $dcolors, $trim, $ver, $dtitles, $bleed, $crop, $colorStdNames, $preflightErr, $pid );
	}
	
if( $_GET['op'] == 'getFirstPage' ) {
	$issue = sql_get( 'publications', 'id="'.$_GET['id'].'" LIMIT 1', '*' );
	$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'" LIMIT 1', '*' );

	// A job with FlatplanStages==1 has only one flatplan - see the
	// $stages1 comment in the reloadbg op block above.
	$stages1 = false;
	if( !empty( $magazine[0][3] ) ) {
		$wfXml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
		$wfXpath = $wfXml->xpath('/Publications');
		foreach( $wfXpath as $wfTemp ) {
			for( $wfI = 0; $wfI < count( $wfTemp->Item ); $wfI++ ) {
				if( $wfTemp->Item[$wfI]->Code == $magazine[0][3] )
					break;
				}
			}
		$stages1 = ( (string) $wfXml->Item[$wfI]->FlatplanStages == "1" and (string) $wfXml->Item[$wfI]->Workflow != "Hybrid" );
		}

	if( $stages1 ) {
		$condition = '( type="ad" OR type="magazine" ) AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND part="'.$_GET["part"].'" ORDER BY `page` ASC LIMIT 1';
		}
	else {
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
		}

	$firstPage = sql_get( 'pageinfo', $condition, '*' );

	$result = $firstPage;
	}
	
print json_encode( $result );
	
?>