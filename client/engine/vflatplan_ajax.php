<?PHP
session_start();

ini_set("log_errors", 0);
ini_set("error_log", "error_log");

header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');

	include_once('../lang/en.php');
	
	include_once( '../../engine/xml_handler.php' );
	
	$baseDir = "/var/www/intra/client";
	
	$rights = array();
	if( isset( $_SESSION['standalone_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['standalone_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}

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
	
	function addingNumber( $szamok ) {
		$szamok = str_split( $szamok );
		$osszeg = 0;
		
		foreach( $szamok as $szam ) {
			$osszeg += intval( $szam );
			}
		
		return $osszeg;
		}

	function calculateSize( $pageInfo, $jobid ) {
		$file = str_pad( $pageInfo[3], 3, '0', STR_PAD_LEFT).".jpg";
		$path = "../packages/".$jobid;
		$w = 81;
		$h = 97;
		/*if( $pageInfo[0] != "" && is_file( $path."/".$file ) ) {
			list( $w2, $h2 ) = getimagesize( $path."/".$file );
			if( $w2 >= 81 ) {
				$percent = $w/$w2*100;
				$h = intval( $h2/100*$percent );
				}
			}*/
		return array( $w, $h );
		}

	function commentMark( $pageInfo, $id ) {
		global $myPublisher;

		$comments = sql_get( 'comments', 'job_id="'.$pageInfo[4].'" AND page="'.$pageInfo[3].'"', '*' );
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
	
	function drawPage( $id, $page, $class, $i, $pageType = 'normal' ) {
		global $issue, $state, $length, $lang, $baseDir, $holderWidth, $fPages2, $alterP, $rPalette, $gPalette, $bPalette, $magazine, $issue, $sizes, $path, $fin, $imghash;
		
		$triangle = $secBg = $txt = "";
		
		list( $w, $h ) = $sizes;

		if( $page == 0 ) {
			return "<div style='float: left;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w)."px; height: ".($h+30)."px;'>&nbsp;</div></div>";
			}

		$fPage[0] = $fPages2[ $page."|".$state[ $i ] ];
			
		if( $fPage[0][6] == "ad" ) {
			$file = "_ads/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_ad_preview.jpg";
			$secBg = "background: rgb( 242, 92, 36 ) !important;";
			if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
				$textColor = "FFFFFF";
				}
			else {
				$textColor = "737070";
				}
			}
		elseif( $fPage[0][0] != "" ) {
			$textColor = "000000";
			$tempPage = sql_get( 'packages', 'id="'.$fPage[0][1].'" LIMIT 1', 'id, directory' );
			
			if( $tempPage[0][0] != "" ) {
				$place = addingNumber( $tempPage[0][0] );
				$secBg = "background: #".$bPalette[$place]." !important;";
				}
				
			if( $pageType == "normal" ) {
				$file = $tempPage[0][1]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_".( $fPage[0][8] != "" ? $fPage[0][8] : "" )."preview.jpg";
				}
			elseif( $pageType == "FIN" ) {
				$file = $tempPage[0][1]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_".( $fPage[0][8] != "" ? $fPage[0][8] : "" )."preview.jpg";
				}
			else {
				$file = "_".strtoupper( $pageType )."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_".( $fPage[0][8] != "" ? $fPage[0][8] : "" )."preview.jpg";
				}
			}
			
		if( $fPages2[ (intval($page)-1) ][0] == "" ) {
			$lPage = $page;
			}
		elseif( $fPage[0][0] != "" ) {
			$lPage = $page-1;
			}
			
		if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
			$commentMark = commentMark( $fPage[0], $id );
			$link = "?page=vflatplan_preview&tag=".$state[ $i ]."&clk=".$page."&id=".$issue[0][0]."&p=".$page."&hash=".$_GET["hash"];
			}
		elseif( $fPage[0][0] != "" ) {
			unset( $fPage );
			$page_thumb = "";
			$link = '';
			$file = "";
			}
		
		$scale = "width: ".$w."px; height: ".$h."px;";
		
		$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
		if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) )
			//$page_thumb .= " background-size:".$w."px ".$h."px; background-image: url(packages/".$id."/".$file."?".$imghash." ); background-position: center; ";
			$page_thumb .= " background-size: contain; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."?".$imghash." ); background-position: center; ";

		$holderWidth += $w;
		if( $page == 1 ) $holderWidth += $w;

		if( $page > $length ) {
			if( intval( $page )%2 != "" && ( $issue[0][19] == 1 or $_GET['page'] == "vflatplan_preview" ) ) {
				return "<div style='float: left; height: ".($h+32)."px; width: ".($holderWidth+3)."px;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w)."px; height: ".($h+30)."px;'>&nbsp;</div></div>";
				}
			elseif( $issue[0][19] == 0 && $_GET['page'] != "vflatplan_preview" ) {
				return "<div style='float: left; height: ".($h+32)."px; width: ".$w."px;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w)."px; height: ".($h+30)."px;'>&nbsp;</div></div>";
				}
			}

		if( intval( $page )%2 != "" && ( $issue[0][19] == 1 or $_GET['page'] == "vflatplan_preview" ) ) {
			$txt .= "<div style='float: left; height: ".($h+32)."px; width: ".($holderWidth+3)."px;'></div>";
			$holderWidth = 0;
			}
		elseif( $issue[0][19] == 0 && $_GET['page'] != "vflatplan_preview" ) {
			$txt .= "<div style='float: left; height: ".($h+32)."px; width: ".$w."px;'></div>";
			}

		switch( $_GET['filter'] ) {
			case 'all':
				$display = "block";
				break;
			case 'newUploads':
				$viewed = explode( ",", $fPage[0][10] );
				if( in_array( $_SESSION['intra_user'], $viewed ) ) {
					$display = "none";
					}
				else {
					$display = "block";
					}
				break;
			case 'newComments':
				if( $commentMark == "<div class='commentRed'></div>" ) {
					$display = "block";
					}
				else {
					$display = "none";
					}
				break;
			case 'waiting':
				if( $fPage[0][4] == 1 ) {
					$display = "block";
					}
				else {
					$display = "none";
					}
				break;
			case 'approved':
				if( $fPage[0][2] == 2 ) {
					$display = "block";
					}
				else {
					$display = "none";
					}
				break;
			}
		
		switch( $class ) {
			case 'left':
				if( $display == "block" ) {
					$txt .= "<div id='".$page."_selector' class='selectLeft' style='opacity: 0; width:".($w+1)."px; height: ".($h+34)."px;'></div>";
					$txt .= "<div class='".$class."_page' page='".$page."' style='position: absolute; left: 0px; z-index: 10; border-right: 1px solid #ADADAD;'>";	
					}
				else {
					$txt .= "<div id='".$page."_selector' class='selectLeft' style='opacity: 0; width:".($w+1)."px; height: ".($h+34)."px;'></div>";
					$txt .= "<div class='".$class."_page' page='".$page."' style='position: absolute; left: 0px; z-index: 10; border-right: 1px solid #ADADAD;'>";					
					}
				break;
			case 'right':
				if( $display == "block" ) {
					$txt .= "<div id='".$page."_selector' class='selectRight' style='opacity: 0; width:".($w+1)."px; height: ".($h+34)."px;'></div>";
					$txt .= "<div class='".$class."_page' page='".$page."' style='position: absolute; right: 0px; z-index: 10;'>";	
					}
				else {
					$txt .= "<div id='".$page."_selector' class='selectRight' style='opacity: 0; width:".($w+1)."px; height: ".($h+34)."px;'></div>";
					$txt .= "<div class='".$class."_page' page='".$page."' style='position: absolute; right: 0px; z-index: 10;'>";	
					}
				break;
			}
		
			if( $page == 1  && ( $job[0][15] == 1 or $_GET['page'] == "vflatplan_preview" ) )
				$txt .= "<div class='pageBox' style='border-left: 1px solid #ADADAD; position: relative; width: ".$w."px; height: ".($h+34)."px;'>";
			else
				$txt .= "<div class='pageBox' style='position: relative; width: ".$w."px; height: ".($h+34)."px;'>";
			
				$txt .= "<input type='hidden' id='".$page."_current' name='".$page."_current' value='0'>";
				$txt .= "<div alter='0' id='".$fPage[0][1]."_".$page."' item='".$fPage[0][1]."' page='".$page."' class='".$class."_pagenr pagenr checking2' style='z-index: 1000; width: ".($w)."px; ".$secBg." color: #".$textColor."; ".( $size_error == 1 ? "background: #FF5252 !important;" : "" )."'>";
				
				if( $fPage[0][5] != "" )
					$version = "v".$fPage[0][3];
					
				if( $class == 'right' ) {
					$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".$version."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div>";
					$commentPlace = "<div style='float: right; margin-top: 3px; margin-right: 3px;'>".$commentMark."</div>";
					
					if( $fPage[0][12] != "" ) {		
						$triangle = "<div title='".$lang["flatplan"][trim($fPage[0][12])]."' class='".$fPage[0][12]."' style='float: left; margin-top: 3px; margin-left: 3px;'></div>";
						}
					}
				elseif( $class == 'left' ) {
					$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".$version."</div>";
					$commentPlace = "<div style='float: left; margin-top: 3px; margin-left: 3px;'>".$commentMark."</div>";
					
					if( $fPage[0][12] != "" ) {		
						$triangle = "<div title='".$lang["flatplan"][trim($fPage[0][12])]."' class='".$fPage[0][12]."' style='float: right; margin-top: 3px; margin-right: 3px;'></div>";
						}
					}
		
				$txt .= "</div><div id='".$page."_thumb' state='".$fPage[0][8]."' class='thumb' alter='0' page='".$page."'";
				if( $link != '' ) {
					if( $_GET['type'] == 'fpPreview' ) $txt .= " onclick='changePic(\"".$link."\")'";
					else $txt .= "double='".$link."'";
					}
				$txt .= " style='background-color: #DDD !important; background: url(images/empty_slot.png); position: absolute; z-index: 1000; top: 17px; ".$scale." ".$page_thumb."' ";
				$txt .= "></div>";
				
				$title = "";
				
				switch( $fPage[0][4] ) {
					case '3':
						$title = sql_aget( "action_log", "action='rejectPage' AND target='".$page."' AND magazine='".$magazine[0][0]."' AND issue='".$issue[0][10]."' AND info='' order by `id` DESC LIMIT 1", "*" );
						if( $title[0]["id"] != "" && ( $title[0]["user"] != "0" or $title[0]["user"] != "" ) ) {
							$u = sql_aget( "accounts", "id='".$title[0]["user"]."'", "*" );
							$title = "Rejected ".( $u[0]["full_name"] != "" ? "by ".$u[0]["full_name"] : ( $u[0]["name"] != "" ? "by ".$u[0]["name"]: "" ) )." at ".date( "Y-m-d H:i", $title[0]["date"] );
							}
						else {
							$title = "";
							}
						break;
						
					case '2':
						$title = sql_aget( "action_log", "action='approvePage' AND target='".$page."' AND magazine='".$magazine[0][0]."' AND issue='".$issue[0][10]."' AND info='' order by `id` DESC LIMIT 1", "*" );
						if( $title[0]["id"] != "" && ( $title[0]["user"] != "0" or $title[0]["user"] != "" ) ) {
							$u = sql_aget( "accounts", "id='".$title[0]["user"]."'", "*" );
							$title = "Approved ".( $u[0]["full_name"] != "" ? "by ".$u[0]["full_name"] : ( $u[0]["name"] != "" ? "by ".$u[0]["name"]: "" ) )." at ".date( "Y-m-d H:i", $title[0]["date"] );
							}
						else {
							$title = "";
							}
						break;
					}
				
				if( $fPage[0][4] == 0 or $fPage[0][4] == 1 ) {
					$checker = sql_get( "hotlinks_log", "job_id='".$issue[0][0]."' AND ( action='accept' OR action='reject' ) AND info='".$page."' AND version='".$fPage[0][3]."' ORDER BY `ID` DESC LIMIT 1", "*" );
					if( $checker[0][0] != "" ) {
						$fPage[0][4] = "light_".$checker[0][2];
						$hl = sql_get( "hotlinks", "id='".$checker[0][5]."'", "*" );
						$hl_user = sql_get( "ad_hoc_users", "email='".$hl[0][11]."'", "*" );
						
						$title = sprintf( $lang["flatplan"]["approvedby"], ( $hl_user[0][1] != "" ? sprintf( $lang["flatplan"]["by"], $hl_user[0][1] ) : sprintf( $lang["flatplan"]["by"], $hl[0][11] ) ), date( "Y-m-d H:i", $checker[0][4] ) ); 
						}
					}
				
				$txt .= "<div ".( $title != "" ? "title='".$title."'" : "" )." alter='0' page='".$page."' style='position: absolute; z-index: 1000; bottom: 0px; width: ".$w."px;' class='page_footer state_".$fPage[0][4]."'>";

				if( $class == 'right' ) {
					$txt .= "<div id='".$fPage[0][1]."_Name' style='font-size: 12px; line-height: 16px; float:right; color: #000000; margin-top: 1px; margin-left: 3px;'>".substr( $fPage[0][8], 0, -1 )."</div>";
					}
				elseif( $class == 'left' ) {
					$txt .= "<div id='".$fPage[0][1]."_Name' style='font-size: 12px; line-height: 16px; float:left; color: #000000; margin-top: 1px; margin-right: 3px;'>".substr( $fPage[0][8], 0, -1 )."</div>";
					}
				
				$txt .= $commentPlace;
				$txt .= $triangle;
				$txt .= "<input ";
				if( $link == '' )
					$txt .= 'disabled';
				$txt .= " type='checkbox' item='".$fPage[0][1]."' state='".$fPage[0][8]."' name='pageSelector[]' value='".$page."' style='display: none;'></div></div></div>";
		
		return $txt;
		}
		
	// This whole file had no real access control - $rights/$user above is
	// built from $_SESSION['standalone_user'], which nothing in this
	// codebase ever sets, so that block never actually gated anything, and
	// several op== handlers below (saveTool, colorPick, refreshPageStatus,
	// changeOpt) had no check of any kind, not even the hotlinks-hash lookup
	// a few of the others already do. This file's whole design is external,
	// hash-based access (see client/vflatplan.php, which resolves
	// $_GET['hash'] against the hotlinks table before including this) - so
	// gate the same way here, once, for every op, rather than patching each
	// individually. See client/plugins/pubsApply.php's 2026-09-05 fix for
	// the session-based equivalent used everywhere else in this pass.
	$vHotlink = getValidHotlink( $_GET['hash'] ?? '' );
	if( empty( $vHotlink[0][0] ) ) {
		print json_encode( array( array( "Unauthorized" ) ) );
		exit;
		}

	if( $_GET['op'] == 'loadPagePair' ) {
		$holderWidth = 0;
		$text = '';
		$imghash = ( $_GET['cachebreak'] == 1 ? time() : "" );

		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'" LIMIT 1', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'" LIMIT 1', '*' );		
		$path = "../packages/".$magazine[0][3]."/".$issue[0][10];

		if( $_GET["opt"] == "" or $_GET["opt"] == "FIN" ) {
			$typeSelect = 'type!="PRE" AND type!="PSTR"';
			$acceptType = array( 'ad', 'magazine' );
			}
		else {
			$typeSelect = 'type="'.strtoupper( $_GET["opt"] ).'"';
			$acceptType = array( 'PRE', 'ad', 'magazine' );
			}
		
		if( $_GET['opt'] == 'FIN' ) {
			$fin = 1;
			}
		else {
			$fin = 0;
			}
				
		$bPalette = colorGenerate( 'blue' );
		$bPalette = colorGenerate( 'red', $bPalette );
		$bPalette = colorGenerate( 'green', $bPalette );
					
		$fPages2 = array();	
		$fPagesSql = sql_get( 'pageinfo', $typeSelect.' AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND fin="'.$fin.'"', '*' );
		foreach( $fPagesSql as $fP ) {
			$fPages2[ intval($fP[5])."|".$fP[8] ] = $fP;
			}
		
		
		$sizes = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND width="1" AND issue="'.$issue[0][10].'" AND state="" LIMIT 2', '*' );
		$sizes = calculateSize( $sizes[1], $magazine[0][3], $issue[0][10] );
		$row = intval( intval($_GET['maxwidth'] )/229 );
		$divWidth = $row*229;
		
		$hotlink = sql_get( 'hotlinks', 'hashtag="'.$_GET['hash'].'" LIMIT 1', '*' );
		$length = explode( "|", $hotlink[0][3] );
		$state = explode( "|", $hotlink[0][14] );
		
		$pages = array();
		for( $i = 0; $i < count( $length ); $i++ ) {
			if( $hotlink[0][8] == "pair" ) {
				if( ( $length[$i] % 2 ) != 0 && !in_array( ( $length[$i]-1 ), $pages ) ) {
					$pages[] = $length[$i]-1;
					}
				}
				
			$pages[] = $length[$i];
			
			if( $hotlink[0][8] == "pair" ) {
				if( ( $length[$i] % 2 ) == 0 && !in_array( ( $length[$i]+1 ), $pages ) && !in_array( ( $length[$i]+1 ), $length ) ) {
					$pages[] = $length[$i]+1;
					}
				}
			}
		
		$issue[0][19] = ( $hotlink[0][8] == "pair" ? 1 : 0 );
						
		$counter = 1;
		$i = 0;
		if( count($pages) > 0 ) {
			if( $hotlink[0][8] == "pair" ) {
				while( $i < ( count( $pages ) -1 ) ) {
					if( $i == 0 ) {
						if( ( $pages[$i] % 2 ) == 0 ) {
							$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
								$text .= drawPage( $_GET['id'], $pages[$i], 'left', $i, "FIN" );
								$text .= drawPage( $_GET['id'], $pages[($i+1)], 'right', $i+1, "FIN" );
							$text .= "</div>";
							$i += 2;
							}
							
						else {
							$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
								$text .= drawPage( $_GET['id'], $pages[$i], 'left', $i, "FIN" );
							$text .= "</div>";
							$i++;
							}
						}
						
					else {
						$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
							$text .= drawPage( $_GET['id'], $pages[$i], 'left', $i, "FIN" );
							$text .= drawPage( $_GET['id'], $pages[($i+1)], 'right', $i+1, "FIN" );
						$text .= "</div>";
						$i += 2;
						}
					}
				}
			else {
				for( $i = 0; $i < count( $pages ); $i++ ) {
					$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
						$text .= drawPage( $_GET['id'], $pages[$i], 'left', $i, "FIN" );
					$text .= "</div>";
					}

				}
			}
		
		//$text = implode( " , ", $pages );
			
		$result[0] = $text;
		$result[1] = "";
		}
	
	if( $_GET['op'] == 'reloadbg' ) {
		$_GET["alter"] = "FIN";
		$_SESSION["fpFilter"] = $_GET["fpFilter"];
		if( $_POST['switchTo'] != "" ) {
			$_SESSION["cutBox"] = $_POST['switchTo'];
			}
	
		$colors = $_POST['colors'];
		
		if( $_SESSION["fpFilter"] == 'pair' && $_GET["p"]%2 == 1 && $_GET["p"] != 1 ) {
			$_GET["p"]--;
			}
		
		$hotlink = sql_get( 'hotlinks', 'hashtag="'.$_GET['hash'].'" LIMIT 1', '*' );
		$pages = explode( "|", $hotlink[0][3] );
		$states = explode( "|", $hotlink[0][14] );
		
		
		$issue = sql_get( "publications", "id='".$_GET['id']."'", "*" );
		$magazine = sql_get( "magazines", "id='".$issue[0][2]."'", "*" );
		$pageinfo = sql_get( "pageinfo", '(type="ad" OR type="magazine") AND page="'.$_GET["clk"].'" AND code="'.$magazine[0][3].'" AND width="1" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND fin="1"', "*" );
		
		$pages = checkPagePair( $_GET['id'], $pageinfo[0][1], $_GET["p"], $pageinfo[0][8], $_GET["alter"], "prev" );
		
		if( array_search( $_GET["p"], $pages ) === false ) {
			$_GET["p"]++;
			}
			
		$fpPages = $user[0][14];
		
		if( $_SESSION["fpFilter"] == 'single' && count( $pages ) > 1 ) {
			$needle = array_search( $_GET['clk'], $pages );
			$pages = array( $pages[ $needle ] );
			}

		$bgDPI = 72;
		$file = array();
		$state = array();
		$ver = array();
		$terminalPath = "/var/www/intra/client";
		
		for( $i = 0; $i < count( $pages ); $i++ ) {
			$dir = "../packages/".$magazine[0][3]."/".$issue[0][10];
			$file2 = '';
			$tag = $_GET['tag'];
			
			if( $_GET['alter'] != "" && $_GET['alter'] != 'FIN' ) {
				$dir .= "/_".strtoupper( $_GET['alter'] );
				if( $_GET['tag'] != "" && $pages[$i] == $_GET['clicked'] ) {
					$tag = $_GET['tag'];
					$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'"', '*' );
					}
				else {
					$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$pages[$i].'"', '*' );
					}
				$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
				}
			else {
				$fin = ( $_GET['alter'] == "FIN" ? "1" : "0" );
				if( $_GET['tag'] != "" ) {
					$tag = $_GET['tag'];
					$pageinfo = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'" AND fin="'.$fin.'"', '*' );
					}
				else {
					$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$pages[$i].'" AND state="" AND fin="'.$fin.'"', '*' );
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
				$sizes = getBBox( $file[$i]["Name"], "" );
				$file[$i]["Right"] = $sizes['Right'];
				$file[$i]["Top"] = $sizes['Top'];
				$file[$i]["Width"] = $sizes['Width'];
				$file[$i]["Height"] = $sizes['Height'];
				$file[$i]["Left"] = 0;
				$file[$i]["Bottom"] = 0;
				$state[$i] = $pageinfo[0][8];
				}	
			}
			
   	$dcolors = getColors( $terminalPath."/".$file[0]["Path"] );
	$dtitles = getColorTitles( $terminalPath."/".$file[0]["Path"] );
   	
   	
   	if( $_POST['from'] == "changePic" ) {
      for( $i = 0; $i < count( $dcolors ); $i++ ) {
        $colors[($i+1)] = "true";
        }
      }
    
	$postfix = time();
	if( count( $pages ) > 1 ) {
		$correctionBox[2] = $correctionBoxTemp = $_SESSION["cutBox"];
		$box = getPDFBox( "Mediabox Trimbox Cropbox Bleedbox", $file[0]["Path"], $terminalPath );
		$differences = array(
			"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
			"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
			"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
			"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
			);
		switch( $correctionBoxTemp ) {
			case 'mediabox':
				$differences = array(
					"Left" => ( 0 ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
					"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
					);

				$correctionBox[0] = $differences;	
				
				$sizes = array(
					"Left" => $box["Cropbox"][0],
					"Bottom" => $box["Cropbox"][1],
					"Right" => $box["Trimbox"][2],
					"Top" => $box["Cropbox"][3]
					);
				if( $box["Trimbox"] == "" ) {
					$sizes = array(
						"Left" => $box["Cropbox"][0],
						"Bottom" => $box["Cropbox"][1],
						"Right" => $box["Cropbox"][2],
						"Top" => $box["Cropbox"][3]
						);
					}
					
				if( count( $box["Cropbox"] ) == 0 ) {
					$sizes = array(
						"Left" => $box["Mediabox"][0],
						"Bottom" => $box["Mediabox"][1],
						"Right" => $box["Mediabox"][2],
						"Top" => $box["Mediabox"][3]
						);

					$correctionBox[0] = array(
						"Left" => 0,
						"Bottom" => 0,
						"Right" => 0,
						"Top" => 0
						);
					}
			
				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];			
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
								
				$fullSizes = ( $sizes['Width'] );
				break;
			
			case 'trimbox':
				$differences = array(
					"Left" => ( $box["Cropbox"][0] - $box["Trimbox"][0] ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
					"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
					);
				$correctionBox[0] = $differences;	
				
				$sizes = array(
					"Left" => $box["Trimbox"][0],
					"Bottom" => $box["Trimbox"][1] - $differences['Bottom'],
					"Right" => $box["Trimbox"][2]-$box["Cropbox"][0],
					"Top" => $box["Trimbox"][3] - $differences['Top']
					);
					
				if( $box["Trimbox"][2] == "" ) {
					$sizes = array(
						"Left" => $box["Cropbox"][0],
						"Bottom" => $box["Cropbox"][1],
						"Right" => $box["Cropbox"][2],
						"Top" => $box["Cropbox"][3]
						);
					}

				if( count( $box["Cropbox"] ) == 0 ) {
					$sizes = array(
						"Left" => $box["Mediabox"][0],
						"Bottom" => $box["Mediabox"][1],
						"Right" => $box["Mediabox"][2],
						"Top" => $box["Mediabox"][3]
						);

					$correctionBox[0] = array(
						"Left" => 0,
						"Bottom" => 0,
						"Right" => 0,
						"Top" => 0
						);
					}

				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
				$fullSizes = ( $sizes['Width'] );
				break;
			}

		if( count( $box["Cropbox"] ) == 0 ) {
			$crop[0] = array(
				"Left" => $box["Mediabox"][0],
				"Bottom" => $box["Mediabox"][1],
				"Right" => $box["Mediabox"][2],
				"Top" => $box["Mediabox"][3]
				);

			}
		else {
			$crop[0] = array(
				"Left" => $box["Cropbox"][0],
				"Bottom" => $box["Cropbox"][1],
				"Right" => $box["Cropbox"][2],
				"Top" => $box["Cropbox"][3]
				);
			}
			
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
		$file[0]["State"] = $state[0];
		$file[0]["Path"] = $temp;
		$file[0]["Name"] = $temp2;
			
		PDFtoImage_( $sizes, $terminalPath."/".$file[0]["Path"], "leftbg".$postfix.".jpg", $colors );

		$correctionBox[2] = $correctionBoxTemp = $_SESSION["cutBox"];
		$box = getPDFBox( "Mediabox Trimbox Cropbox Bleedbox", $file[1]["Path"], $terminalPath );
		$differences = array(
			"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
			"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
			"Right" => ( $box["Bleedbox"][2] - $box["Trimbox"][2] ),
			"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
			);
		switch( $correctionBoxTemp ) {
			case 'mediabox':
				$differences = array(
					"Left" => ( 0 ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
					"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Cropbox"][3] - $box["Trimbox"][3] )
					);
				
				$correctionBox[1] = $differences;
				
				$sizes = array(
					"Left" => $box["Trimbox"][0],
					"Bottom" => $box["Cropbox"][1],
					"Right" => $box["Cropbox"][2],
					"Top" => $box["Cropbox"][3]
					);

				if( $box["Trimbox"] == "" ) {
					$sizes = array(
						"Left" => $box["Cropbox"][0],
						"Bottom" => $box["Cropbox"][1],
						"Right" => $box["Cropbox"][2],
						"Top" => $box["Cropbox"][3]
						);					
					}

				if( count( $box["Cropbox"] ) == 0 ) {
					$sizes = array(
						"Left" => $box["Mediabox"][0],
						"Bottom" => $box["Mediabox"][1],
						"Right" => $box["Mediabox"][2],
						"Top" => $box["Mediabox"][3]
						);
					}
	
				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
				
				$fullSizes = ( $sizes['Width'] );
				break;
			
			case 'trimbox':
				$differences = array(
					"Left" => ( $box["Cropbox"][0] - $box["Trimbox"][0] ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
					"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
					);

				$correctionBox[1] = $differences;			
				$sizes = array(
					"Left" => $box["Trimbox"][0],
					"Bottom" => $box["Trimbox"][1] - $differences['Bottom'],
					"Right" => $box["Trimbox"][2] + $differences['Right'],
					"Top" => $box["Trimbox"][3] - $differences['Top']
					);

				if( $box["Trimbox"][2] == "" ) {
					$sizes = array(
						"Left" => $box["Cropbox"][0],
						"Bottom" => $box["Cropbox"][1],
						"Right" => $box["Cropbox"][2],
						"Top" => $box["Cropbox"][3]
						);
					}

				if( count( $box["Cropbox"] ) == 0 ) {
					$sizes = array(
						"Left" => $box["Mediabox"][0],
						"Bottom" => $box["Mediabox"][1],
						"Right" => $box["Mediabox"][2],
						"Top" => $box["Mediabox"][3]
						);

					$correctionBox[1] = array(
						"Left" => 0,
						"Bottom" => 0,
						"Right" => 0,
						"Top" => 0
						);
					}	

				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
				$fullSizes = ( $sizes['Width'] );
				break;
			}

		if( count( $box["Cropbox"] ) == 0 ) {
			$crop[1] = array(
				"Left" => $box["Mediabox"][0],
				"Bottom" => $box["Mediabox"][1],
				"Right" => $box["Mediabox"][2],
				"Top" => $box["Mediabox"][3]
				);

			}
		else {
			$crop[1] = array(
				"Left" => $box["Cropbox"][0],
				"Bottom" => $box["Cropbox"][1],
				"Right" => $box["Cropbox"][2],
				"Top" => $box["Cropbox"][3]
				);
			}

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
			
		$sizes['Width'] = pixel__( $sizes['Width'], $bgDPI );
		$sizes['Height'] = pixel__( $sizes['Height'], $bgDPI );
		
		$temp = $file[1]["Path"];
		$temp2 = $file[1]["Name"];
		$file[1] = $sizes;
		$file[1]["State"] = $state[1];
		$file[1]["Path"] = $temp;
		$file[1]["Name"] = $temp2;
		PDFtoImage_( $sizes,  $terminalPath."/".$file[1]["Path"], "rightbg".$postfix.".jpg", $colors );
		
		$fullSizes = ( $file[0]["Width"] )+( $file[1]["Width"] );
		
		$maxheight = ( $file[0]["Height"] > $file[1]["Height"] ? $file[0]["Height"] : $file[1]["Height"] );
		$first = new Imagick( "r3/leftbg".$postfix.".jpg" );
		$second = new Imagick( "r3/rightbg".$postfix.".jpg" );	
		$image = new Imagick();
		$image->newImage( pixel__( $fullSizes, $bgDPI ), pixel__( $maxheight , $bgDPI ), new ImagickPixel('rgb( 255, 255, 255 )') );
			$icc_rgb = file_get_contents( "r3/sRGB_Color_Space_Profile.icc" );
			$image->profileImage('icc', $icc_rgb);
			$image->setImageFormat('jpg');
			$corr = ( $maxheight - $file[0]["Height"] ) / 2;
			$image->compositeImage($first, $first->getImageCompose(), 0, $corr);
      
      		if( $correctionBoxTemp == "mediabox" ) $left = $file[0]["Right"];
     		else $left = $file[0]["Right"]-$file[1]["Left"];
     		
     		$corr = ( $maxheight - $file[1]["Height"] ) / 2;
			$image->compositeImage($second, $second->getImageCompose(), $left, $corr); 
		$image->writeImage( "r3/bg".$postfix.".jpg" );

		$imgData = base64_encode(file_get_contents( "r3/bg".$postfix.".jpg" ) );
		$imgData = 'data:'.mime_content_type( "r3/bg".$postfix.".jpg" ).';base64,'.$imgData;
		@unlink( "r3/leftbg".$postfix.".jpg" );
		@unlink( "r3/rightbg".$postfix.".jpg" );
		@unlink( "r3/bg".$postfix.".jpg" );
		}		
	else {
		$correctionBox[2] = $correctionBoxTemp = $_SESSION["cutBox"];
		$box = getPDFBox( "Mediabox Trimbox Cropbox Bleedbox", $file[0]["Path"], $terminalPath );
		$differences = array(
			"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
			"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
			"Right" => ( $box["Mediabox"][2] - $box["Cropbox"][2] ),
			"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
			);
		switch( $correctionBoxTemp ) {
			case 'mediabox';
				$correctionBox[0] = $differences;
				if( count( $box["Cropbox"] ) == 0 ) {
					$sizes = array(
						"Left" => $box["Mediabox"][0],
						"Bottom" => $box["Mediabox"][1],
						"Right" => $box["Mediabox"][2],
						"Top" => $box["Mediabox"][3]
						);

					$correctionBox[0] = array(
						"Left" => 0,
						"Bottom" => 0,
						"Right" => 0,
						"Top" => 0
						);
					}
					
				else {
					$sizes = array(
						"Left" => $box["Cropbox"][0],
						"Bottom" => $box["Cropbox"][1],
						"Right" => $box["Cropbox"][2],
						"Top" => $box["Cropbox"][3]
						);
					}
					
				$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
				$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
								
				$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );
				break;
			
			case 'trimbox';
				$differences = array(
					"Left" => ( $box["Cropbox"][0] - $box["Trimbox"][0] ),
					"Bottom" => ( $box["Cropbox"][1] - $box["Trimbox"][1] ),
					"Right" => ( $box["Cropbox"][2] - $box["Trimbox"][2] ),
					"Top" => ( $box["Cropbox"][3] - $box["Trimbox"][3] )
					);

				$correctionBox[0] = $differences;	
				
				$sizes = array(
					"Left" => $box["Trimbox"][0],
					"Bottom" => $box["Trimbox"][1],
					"Right" => $box["Trimbox"][2],
					"Top" => $box["Trimbox"][3]
					);

				if( $box["Trimbox"][2] == "" ) {
					$sizes = array(
						"Left" => $box["Cropbox"][0],
						"Bottom" => $box["Cropbox"][1],
						"Right" => $box["Cropbox"][2],
						"Top" => $box["Cropbox"][3]
						);
					}

				if( count( $box["Cropbox"] ) == 0 ) {
					$sizes = array(
						"Left" => $box["Mediabox"][0],
						"Bottom" => $box["Mediabox"][1],
						"Right" => $box["Mediabox"][2],
						"Top" => $box["Mediabox"][3]
						);

					$correctionBox[0] = array(
						"Left" => 0,
						"Bottom" => 0,
						"Right" => 0,
						"Top" => 0
						);
					}
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
		
		if( count( $box["Cropbox"] ) == 0 ) {
			$crop[0] = array(
				"Left" => $box["Mediabox"][0],
				"Bottom" => $box["Mediabox"][1],
				"Right" => $box["Mediabox"][2],
				"Top" => $box["Mediabox"][3]
				);

			}
		else {
			$crop[0] = array(
				"Left" => $box["Cropbox"][0],
				"Bottom" => $box["Cropbox"][1],
				"Right" => $box["Cropbox"][2],
				"Top" => $box["Cropbox"][3]
				);
			}	
			
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
		$file[0]["State"] = $state[0];
		$file[0]["Path"] = $temp;
		$file[0]["Name"] = $temp2;
		
		PDFtoImage_( $sizes, $terminalPath."/".$file[0]["Path"], "_bg".$postfix.".jpg", $colors );
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
		@unlink( "r3/_bg".$postfix.".jpg" );
		@unlink( "r3/bg".$postfix.".jpg" );
		}
			
	if( $_POST['switchTo'] != "" ) {
		$newsize = $fullSizes."x".( $file[0]["Height"] > $file[1]["Height"] ? $file[0]["Height"] : $file[1]["Height"] );
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
		$cbox = '';
		}

	$pageID = $text = array();
	$status = checkPageStatus( $pages[0], $_GET['id'], $pageinfo[0][1], "FIN", $pageinfo[0][8] );
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
		$status = checkPageStatus( $pages[1], $_GET['id'], $_GET["pack_id"], $_GET['alter'], $_GET['tag'] );
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
		
	if( $_GET['alter'] != '' and $_GET['alter'] != 'FIN' ) {
		$packs = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'"AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" ORDER BY `page` ASC', '*' );
		}
	else {
		$fin = ( $_GET['alter'] == 'FIN' ? "1" : "0" );
		$packs = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND fin="'.$fin.'" ORDER BY `page` ASC', '*' );
		}
	
	$pages2 = array();
	$ad_pages = array();
	for( $i = 0; $i < count( $packs ); $i++ ) {		
		$checker = "../packages/".$magazine[0][3]."/".$issue[0][10];
		if( $_GET['alter'] != '' && $_GET['alter'] != 'FIN' ) {
			$checker .= "/_".$_GET['alter']."/".str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1]."_".$packs[$i][8]."preview.pdf";
			}
		elseif( $packs[$i][6] == "ad" ) {
			$checker .= "/_ads/".str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1]."_ad_".$packs[$i][8]."preview.pdf";
			}
		else {
			$pack = sql_get( "packages", "id='".$packs[$i][1]."'", "directory" );
			if( $_GET['alter'] == 'FIN' ) {
				$checker .= "/".$pack[0][0]."/FIN/".str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1]."_".$packs[$i][8]."preview.pdf";
				}
			else {
				$checker .= "/".$pack[0][0]."/".str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1]."_".$packs[$i][8]."preview.pdf";
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
	
	$needle = array_search( str_pad( $_GET['p'] , 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1], $pages2 );
	
	$prev = 0;
	$prev_id = 0;
	$next = 0;
	$next_id = 0;
	$prev_link = $next_link = "?page=flatlpan_preview";

	if( $needle !== false ) {
		$prev = intval( $pages2[$needle-1] );
		$temp = explode( "_", $pages2[$needle-1] );
	
		$clk = intval( $pages2[$needle-1] ); 
		if( $user[0][14] == "pair" ) {
	    	if( $prev % 2 != 0 && $prev > 1 ) {
				if( $pages2[$needle-2] == ( $prev-1 ) ) {
					$prev = intval( $pages2[$needle-2] );
					$temp = explode( "_", $pages2[$needle-2] );
	        		}
	      		}
			}
		$prev_id = $temp[1];
		
		$next = intval( $pages2[$needle+1] );
		$temp = explode( "_", $pages2[$needle+1] );
		$tempSql = sql_get( 'pageinfo', 'job_id="'.$job[0][0].'"', '*' );
		$clk2 = intval( $pages2[$needle+1] );
		if( $user[0][14] == "pair" ) {
		    if( $next == ( $pages2[$needle]+1 ) && $next % 2 != 0 ) {
	    		$next = intval( $pages2[$needle+2] );
				$temp = explode( "_", $pages2[$needle+2] );
	    		}
	
	    	$next_id = $temp[1];
	    	}
	    $next_id = $temp[1];
	    
	    $hotlink_pages = explode( "|", $hotlink[0][3] );
	    if( !in_array( $prev, $hotlink_pages ) ) {
	    	$prev = 0;
			$prev_id = 0;
			}
	    
	    if( !in_array( $next, $hotlink_pages ) ) {
	    	$next = 0;
			$next_id = 0;
			}
		}
	
	if( $prev != 0 ) {	
		$prev_link .= "&id=".$_GET['id']."&p=".$prev."&clk=".$clk;
		}
		
	if( $next != 0 ) {
		$next_link .= "&id=".$_GET['id']."&p=".$next."&clk=".$clk2;
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
	
	//$result = $debug;
	$result = array( $imgData, $newsize, $cbox, $file, $pageID, $text, implode( "-", $numb ), array( $prev_link, $next_link ), $fpPages, $sizes['Top'], $dcolors, $trim, $ver, $dtitles, $bleed, $crop );	
	}
	
	if( $_GET['op'] == 'saveTool' ) {
		$data = explode( "=", $_POST['data'] );

		switch( $data[0] ) {
			case 'fpPages':
				error_log( "set SESSION['fpPages']: ".$data[1] );
				error_log( "check: ".$_SESSION["fpPages"] );
				$_SESSION["fpPages"] = $data[1];
				error_log( "check: ".$_SESSION["fpPages"] );
				break;
			}
		
		$result = "success";
		}
		
	if( $_GET['op'] == 'updatePageStatus' ) {
		$hotlink = sql_get( 'hotlinks', 'hashtag="'.$_GET['hash'].'" LIMIT 1', '*' );
		$page = sql_get( "jobs_pageinfo", "id='".$_GET['pageID']."'", "*" );
		$job = sql_get( "jobs", "id='".$page[0][4]."'", "*" );
		
		$check = sql_get( "hotlinks_log", "job_id='".$job[0][0]."' AND ( action='accept' OR action='reject' ) AND info='".$page[0][3]."'", "*" );
		$pageinfo = sql_get( "jobs_pageinfo", "job_id='".$job[0][0]."' AND page='".$page[0][3]."' LIMIT 1", "*" );
		if( $check[0][0] == "" && $pageinfo[0][2] == "0" ) {	
			$names = array( 'job_id', 'action', 'info', 'time', 'hotlink_id', 'version' );
			$values = array( $job[0][0], $_GET['value'], $page[0][3], time(), $hotlink[0][0], $page[0][1] );
			sql_add( 'hotlinks_log', $names, $values );

			$names = array( "type", "data1", "data2", "data3", "data4", "date" );
			$values = array( "sendmail", "light".$_GET['value'], $hotlink[0][13], $job[0][0], $page[0][3], time() );
			sql_add( 'shell_commands', $names, $values );
			shell_exec('php /var/www/standalone/cron/sendmail.php > /dev/null &');
			}
		
		$result = $debug;
		}
	
	if( $_GET['op'] == 'colorPick' ) {
	    $realX = $_POST['data']['x'];
	    $realy = $_POST['data']['y'];
    
	    $terminalPath = "/var/www/standalone";
	    
	    if( $_GET["mode"] == "normal" ) {
	   		$file = $terminalPath."/".$_POST['file'][0]['Name'];
	   		if( $realX > $_POST['file'][0]['Right'] ) {
	    		$file = $terminalPath."/".$_POST['file'][1]['Name'];
	    		$realX = ( $_POST['data']['x'] - $_POST['file'][0]['Right'] )+$_POST['file'][1]['Left'];
	    		}
	    	}
 
	    if( $_GET["mode"] == "compare" ) {
	   		$file = $terminalPath."/".$_POST['file']['Name'];
	    	} 
      
    	$info = colorPick( $file, $realX, $realy );
    	$debug = $realX." ".$_POST['file'][0]['Right'];
    	$result = array( $info, $debug );
    	}
		
	if( $_GET['op'] == 'refreshPageStatus' ) {
		$pagesID = explode( ",", $_GET['pagesID'] );
		$text = array();
		$i = 0;
		foreach( $pagesID as $pageID ) {
			if( $pageID != "" ) {
				$status = sql_get( 'pageinfo', 'id="'.$pageID.'"', 'status, id, type, code, issue' );
				$mag = sql_get( 'magazines', 'code="'.$status[0][3].'"', '*' );
				$pub = sql_aget( 'publications', "magazine_id='".$mag[0][0]."' AND code='".$status[0][4]."'", "*" );
				
				$fpstages = collectFromXml( "../xml/".PMD.".xml", $status[0][3], "FlatplanStages", $returnnode = '' );
				$fpstages = $fpstages["FlatplanStages"];
				$fpworkflow = collectFromXml( "../xml/".PMD.".xml", $status[0][3], "Workflow", $returnnode = '' );
				$fpworkflow = $fpworkflow["Workflow"];
				$allowed = false;

				if( $_GET['fpver'] == "" && $fpstages == 1 ) $allowed = true;
				// Hybrid: the single flatplan is FINAL regardless of the
				// stored stage count (which is hidden/meaningless for
				// Hybrid), so FIN-view approvals are always allowed.
				if( $_GET['fpver'] == "FIN" && ( $fpstages == 2 or $fpstages == 3 or $fpworkflow == "Hybrid" ) ) $allowed = true;
				
				if( ( $status[0][2] == "ad" or $status[0][2] == "magazine" ) && $allowed ) {
					$status = $status[0];
					switch( $status[0]) {
						case 2:
							if( $rights["cancelApprove"] ) {
								$allowed = ( ( $pub[0]["status"] == "created" or $pub[0]["status"] == "active" or $pub[0]["status"] == "current" ) ? "true" : "false" );
								$t = "<div id='pstatus' style='width: ".( $allowed == "true" ? "125px" : "65px" )."; height: 35px;'>
									  <div style='position: absolute; left: 0px; cursor: pointer; float: left; color: rgb( 1, 188, 0 ) !important;'>".$lang["flatplan"]["approved"]."</div>";
				                if( $allowed == "true" ) {
                  					$t .= "<div id='".$pageID."_acc' style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove.png'></div>
										   <div id='".$pageID."_acc_hover' style='display: none; position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"cancel\" )' src='images/cancelapprove_hover.png'></div>
				                            </div>";
									}
								$text[] = $t;
								}
				
							else {
								$text[] = "<div style='color: rgb( 1, 188, 0 ) !important;'>".$lang["flatplan"]["approved"]."</div>";
								}
							break;
						case 3:
							$text[] = "<div style='color: rgb( 254, 0, 3 ) !important;'>".$lang["flatplan"]["rejected"]."</div>";
							break;
						default:
							$text[$i] = "<div id='pstatus' style='width: 130px; height: 35px;'>";
								if( $rights["acceptPage"] ) {
									$allowed = ( ( $pub[0]["status"] == "created" or $pub[0]["status"] == "active" or $pub[0]["status"] == "current" ) ? "true" : "false" );
									if( $allowed == "true" ) {
                    					$text[$i] .= "<div id='".$pageID."_dec' style='position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>
												<div id='".$pageID."_dec_hover' style='display: none; position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline_hover.png'></div>
				
												<div id='".$pageID."_acc' style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>
												<div id='".$pageID."_acc_hover' style='display: none; position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept_hover.png'></div>";
											}
									 }
							$text[$i] .= "</div>";
							break;
						}
					}
				else {
					$text[] = "";
					}
				}
			$i++;
			}
		
		$result = $text;
		}
	
	if( $_GET['op'] == 'changeOpt' ) {
		sql_update( 'accounts', 'lastOpt="'.$_GET['opt'].'"', 'id="'.$user[0][0].'"' );
		}
	/*if( $_GET['op'] == 'placeBox' ) {
		$zoom = $_GET['zoom'];
		$defHeight = pixel__( ( $_POST['file'][0]['Top'] - $_POST['file'][0]['Bottom'] ), 100 );
		$defWidth = pixel__( ( ($_POST['file'][0]['Right'] - $_POST['file'][0]['Left'])+($_POST['file'][1]['Right'] - $_POST['file'][1]['Left']) ), 100 );
		$height = pixel__( ( $_POST['file'][0]['Top'] - $_POST['file'][0]['Bottom'] ) );
		$width = pixel__( ( ($_POST['file'][0]['Right'] - $_POST['file'][0]['Left'])+($_POST['file'][1]['Right'] - $_POST['file'][1]['Left']) ) );
		$top = $left = 0;
		$debug = $width.", ".$_POST['fpBox']['Width'];
		if( $height < $_POST['fpBox']['Height'] ) {
			$top = ($_POST['fpBox']['Height'] - $height) / 2;
			}
		if( $width < $_POST['fpBox']['Width'] ) {
			$left = ($_POST['fpBox']['Width'] - $width) / 2;
			}
		
		$result = array( $left, $top, $debug );
		}		*/
	
print json_encode( $result );
	
?>