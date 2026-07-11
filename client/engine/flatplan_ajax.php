<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once('../../engine/engine.php');
	include_once( 'switchAPI.php' );
	
	include_once( '../../engine/xml_handler.php' );
	
	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}

	if( !empty( $user[0][17] ) ) {	
		include_once('../lang/'.$user[0][17].'.php');	
		}
	else {
		include_once('../lang/en.php');	
		}

	if( $_GET["part"] == "undefined" ) {
		$_GET["part"] = "";
		}

	/*function pixel_( $num, $_zoom = '' ) {
		global $zoom;
		if( $_zoom == '' ) $_zoom = $zoom;
	
		return $num * $_zoom / 72;
		}

	function point_( $num, $_zoom = '' ) {
		global $zoom;
		if( $_zoom == '' ) $_zoom = $zoom;
	
		return $num * 72 / $_zoom;
		}*/
	
	function addingNumber( $szamok ) {
		$szamok = str_split( $szamok );
		$osszeg = 0;
		
		foreach( $szamok as $szam ) {
			$osszeg += intval( $szam );
			}
		
		return $osszeg;
		}
		
	function calculateSize( $pageInfo, $magazine, $issue ) {
		//error_log( "CALCSIZE DEBUG" );
		$dir = sql_get( 'packages', 'id="'.$pageInfo[1].'"', 'name, directory, id' );
		
		if( $pageInfo[11] == "1" ) {
			$file = $dir[0][0]."/FIN/".str_pad( $pageInfo[5], 3, '0', STR_PAD_LEFT)."_".$dir[0][2]."_preview.jpg";
			}
		else {
			$file = $dir[0][0]."/".str_pad( $pageInfo[5], 3, '0', STR_PAD_LEFT)."_".$dir[0][2]."_preview.jpg";
			}
		
		//error_log( $file );
		
		$path = "../packages/".$magazine."/".$issue;
		//error_log( $path."/".$file );
		$w = 81;
		$h = 97;
		if( $pageInfo[0] != "" && is_file( $path."/".$file ) ) {
			list( $w2, $h2 ) = getimagesize( $path."/".$file );
			if( $w2 >= 81 ) {
				//error_log( "nagyobb" );
				$percent = $w/$w2*100;
				$h = intval( $h2/100*$percent );
				}
			}
		//error_log( "CALCSIZE DEBUG END" );	
			
		return array( $w, $h );
		}

	function commentMark( $pageInfo, $id, $part="" ) {
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
		$comments = sql_get( 'comments', 'pub_id="'.$id.'" AND parent="0" AND page="'.$pageInfo[5].'" AND pageType="'.$pageType.'" AND pageVersion="'.$pageInfo[8].'" AND part="'.$part.'"', '*' );
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

	function drawPlannerPage( $id, $page, $class, $i ) {
		global $currentArticle, $colors, $articleCounter, $counter, $maxcsempe, $currentcsempe, $plans, $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $issue, $sizes, $path, $fin, $imghash;
		
		list( $w, $h ) = $sizes;
		
		if( $page == 0 ) {
			return "<div style='float: left;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w)."px; height: ".($h+30)."px;'></div></div>";
			}

		$check = sql_aget( "flatplan_planner", "pub_id='".$_GET['id']."' AND pos = '".$page."'", "*" );
		$prev = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = ".( $page - 1 )."", "*" );
		$next = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = ".( $page + 1 )."", "*" );
		
		$u = sql_aget( "accounts", "id='".$check[0]["workerID"]."'", "*" );	
		$csempecolor = "";
		
		if( $check[0]["type"] != "ad" ) {
			$atype = sql_aget( "flatplan_articletypes", "id='".$check[0]["atype"]."'", "*" );
			
			$csempecolor = "#".$atype[0]["color"];
			}
		
		switch( $class ) {
			case 'left':
				$extra_class = " ";

				if( !empty( $check[0]["id"] ) ) {
					$extra_class .= $check[0]["type"]." ";
					}
				
				$articleName = "<span class='articleNameBG'>".$check[0]["name"]."</span>";
				$articleName_ = $check[0]["name"];
				
				if( ( !empty( $prev ) or !empty( $next ) ) && $check[0]["type"] != "ad" ) {
					if( empty( $prev ) && !empty( $next ) ) {
						$articleClass .= "startArrow articleStart";
						}
					
					if( !empty( $prev ) && empty( $next ) ) {
						$articleClass .= "endArrow articleEnd";
						}
						
					if( !empty( $prev ) && !empty( $next ) ) {
						if( $counter == 1 ) {
							$articleClass .= " articleStart";
							}
						}
					}

				if( empty( $prev ) && empty( $next ) && !empty( $check[0]["id"] ) && $check[0]["type"] != "ad" ) {
					$articleClass .= " startArrow articleStart endArrow articleEnd";
					}					
				
				if( $check[0]["workerName"] != "" ) {
					$w = "haveWorker";
					$hc = "#C2C2C2";
					}
				else {
					$w = "noWorker";
					$hc = "";
					}
								
				$txt .= '<div id="'.$page.'_selector" class="selectAmerican" style="opacity: 0; width:82px; height: 114px;"></div>';
				$txt .= '<div class="left_page '.$extra_class.''.$articleClass.'" aid="'.$articleCounter.'" acolor="'.fontcolor( ( $check[0]["type"] == "article" ? $csempecolor : "rgb( 254, 229, 204 )" ) ).'" a-name="'.$articleName_.'" aname="'.$articleName.'" style="position: absolute; left: 0px; z-index: 2; border-right: 1px solid #ADADAD;">';
					$txt .= '<div class="pageBox" style=" position: relative; width: 81px; height: 114px;">';
						$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
						$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
						$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="left_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="z-index: 1000; width: 81px;  color: #; background-color:'.$hc.';">';
							$txt .= '<div style="pointer-events: none; float:left; margin-left: 4px;">'.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
						$txt .= '</div>';
						$txt .= '<div id="'.$page.'_thumb" state="" class="'.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb '.$w.'" alter="0" page="'.$page.'" style="position: relative; z-index: 1000; top: 17px; width: 81px; height: 97px; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
							$txt .= arrowChecker( $id, $page );
						$txt .= '</div>';
					$txt .= '</div>';
					$txt .="<input type='checkbox' item='".$id."' state='' name='pageSelector[]' value='".$page."' style='display: none;'>";			
				$txt .= '</div>';
				break;
				
			case 'right':
				$extra_class = " ";

				if( !empty( $check[0]["id"] ) ) {
					$extra_class .= $check[0]["type"]." ";
					}

				$articleName = "<span class='articleNameBG'>".$check[0]["name"]."</span>";
				$articleName_ = $check[0]["name"];
				
				if( $check[0]["pos_start"] != $check[0]["pos_end"] && $check[0]["type"] != "ad" ) {			
					if( $check[0]["pos_start"] == $page ) {
						$articleClass .= "articleStart";
						}
	
					if( $check[0]["pos_end"] == $page ) {
						$articleClass .= "articleEnd";
						}
					elseif( $maxcsempe == $counter ) {
						$articleClass .= " articleEnd";
						}
					}

				if( ( !empty( $prev ) or !empty( $next ) ) && $check[0]["type"] != "ad" ) {
					if( empty( $prev ) && !empty( $next ) ) {
						$articleClass .= "startArrow articleStart";
						}
					
					if( !empty( $prev ) && empty( $next ) ) {
						$articleClass .= "endArrow articleEnd";
						}

					if( !empty( $prev ) && !empty( $next ) ) {
						if( $maxcsempe == $counter ) {
							$articleClass .= " articleEnd";
							}
						}
					}

				if( empty( $prev ) && empty( $next ) && !empty( $check[0]["id"] ) && $check[0]["type"] != "ad" ) {
					$articleClass .= " startArrow articleStart endArrow articleEnd";
					}

				if( $check[0]["workerName"] != "" ) {
					$w = "haveWorker";
					$hc = "#C2C2C2";
					}
				else {
					$w = "noWorker";
					$hc = "";
					}
								
				$txt .= '<div id="'.$page.'_selector" class="selectRight" style="opacity: 0; width:82px; height: 114px;"></div>';
				$txt .= '<div class="right_page '.$extra_class.''.$articleClass.'" aid="'.$articleCounter.'" acolor="'.fontcolor( ( $check[0]["type"] == "article" ? $csempecolor : "rgb( 254, 229, 204 )" ) ).'" a-name="'.$articleName_.'" aname="'.$articleName.'" style="position: absolute; right: 0px; z-index: 2;">';
					$txt .= '<div class="pageBox" style="'.( $page == "1" ? "border-left: 1px solid #ADADAD;" : "" ).' position: relative; width: 81px; height: 114px;">';
						$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
						$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
						$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="right_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="z-index: 1000; width: 81px;  color: #; background-color:'.$hc.';">';
							$txt .= '<div style="pointer-events: none; float:right; margin-right: 4px;">'.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
						$txt .= '</div>';
						$txt .= '<div id="'.$page.'_thumb" state="" class="'.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb '.$w.'" alter="0" page="'.$page.'" style="position: absolute; z-index: 1000; top: 17px; width: 81px; height: 97px; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
							$txt .= arrowChecker( $id, $page );
						$txt .= '</div>';
					$txt .= '</div>';
					$txt .="<input type='checkbox' item='".$id."' state='' name='pageSelector[]' value='".$page."' style='display: none;'>";
				$txt .= '</div>';
				break;
       		}
		
		if( strpos( $articleClass, "articleEnd" ) !== false ) {
			$articleCounter++;
			}
		
		if( empty( $prev ) && empty( $next ) ) {
			$articleCounter++;
			}
		
		return $txt;
		}
	
	function drawPage( $id, $page, $class, $i, $pageType = 'normal' ) {
		global $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $issue, $sizes, $path, $fin, $imghash, $pages, $length;
		
		list( $w, $h ) = $sizes;

		$txt = "";
		if( $pageType == "normal" or $pageType == "FIN" ) {
			$typeSelect = 'type!="PRE" AND type!="PSTR"';
			$acceptType = array( 'ad', 'magazine' );
			}
		else {
			$typeSelect = 'type="'.$pageType.'"';
			$acceptType = array( 'PRE' );
			}
		
		$fPage[0] = $fPages2[ $page ];
		if( $page == 0 ) {
			return "<div style='float: left;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w)."px; height: ".($h+30)."px;'>&nbsp;</div></div>";
			}
		
		
		if( $page > $length and $fPage[0][0] == "" and $pageType != "PRE" ) {
				$txt = "<div style='float: right;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w+2)."px; '></div></div>";

				return $txt;
				}
		
		
		if( $issue[0][6] == 0 ) {	
			if( $page > $pages && $fPages2[ ($page) ][0] == "" ) {
				switch( $class ) {
					case 'left':
						$txt = '<div style="float: left; height: '.($h+31).'px; width: '.($w*2).'px;"></div><div class="left_page" style="-webkit-box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0); -moz-box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0); box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0); border: 0 !important; position: absolute; right: 0px; z-index: 2;"><div class="pageBox" style="position: relative; width: '.($w).'px; height: '.($h+30).'px;"></div></div>';
						break;
						
					case 'right':
						
						$txt = '<div style="float: left; height: '.($h+31).'px; width: '.($w*2).'px;"></div><div class="right_page" style="-webkit-box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0); -moz-box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0); box-shadow: 0px 0px 5px 0px rgba(50, 50, 50, 0); border: 0 !important; position: absolute; right: 0px; z-index: 2;"><div class="pageBox" style="position: relative; width: '.($w).'px; height: '.($h+30).'px;"></div></div>';
						break;
						}
					
				if( $txt != "" ) return $txt;
				}

			if( $page > $pages and $fPage[0][0] == "" ) {
				$txt = "<div style='float: right;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w+2)."px; '>&nbsp;</div></div>";

				return $txt;
				}
			}
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
				$file = $tempPage[0][1]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_preview.jpg";
				}
			elseif( $pageType == "FIN" ) {
				$file = $tempPage[0][1]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_preview.jpg";
				}
			else {
				$file = "_".strtoupper( $pageType )."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1].	"_preview.jpg";
				}
			}
		elseif( $pageType == "normal" ) {
			$textColor = "000000";
			$tempPage = sql_get( 'packages', 'publication_id="'.$issue[0][0].'" AND starting_page!="" ORDER BY `id` ASC', '*' );
			$rowCount = 0;
			$found = 0;
			foreach( $tempPage as $Pack ) {
				$temp = explode( "-", $Pack[3] );
				$start = intval( $temp[0] );
				if( $temp[1] != "" )
					$end = intval( $temp[1] );
				else
					$end = $start;
					
				if( $start <= intval( $page ) && $end >= intval( $page ) ) {
					$found = 1;
					break;
					}
				$rowCount++;
				}
			if( $tempPage[$rowCount][0] != "" && $found == 1 ) {
				$place = addingNumber( $tempPage[$rowCount][0] );
				$secBg = "background: #".$bPalette[$place]." !important;";
				}
				
			if( $found == 0 ) {
				unset( $secBg );
				unset( $textColor );
				}
			}	
		if( $fPage[0][9] > 1 or $page%2 == 0 or $fPages2[ (intval($page)-1) ][0] == "" ) {
			$lPage = $page;
			}
		elseif( $fPage[0][0] != "" ) {
			$lPage = $page-1;
			}
			
		if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
			$commentMark = commentMark( $fPage[0], $id );
			
			if( strpos( $file, "_ads" ) === false ) {
				if( $pageType == "normal" )
					$link = "?page=flatplan_preview&pack_id=".$fPages2[ $lPage ][1]."&clk=".$page."&id=".$id."&p=".$lPage;
				else
					$link = "?page=flatplan_preview&pack_id=".$fPages2[ $lPage ][1]."&clk=".$page."&alter=".$_GET['opt']."&id=".$id."&p=".$lPage;
				}
			else {
				if( $pageType == "normal" )
					$link = "?page=flatplan_preview&pack_id=".$fPages2[ $lPage ][1]."&clk=".$page."&type=ad&id=".$id."&p=".$lPage;
				else
					$link = "?page=flatplan_preview&pack_id=".$fPages2[ $lPage ][1]."&clk=".$page."&alter=".$_GET['opt']."&type=ad&id=".$id."&p=".$lPage;
				}	
			}
		elseif( $fPage[0][0] != "" ) {
			if( $fPage[0][6] == "ad" )
				unset( $secBg );
			unset( $fPage );
			$page_thumb = "";
			$link = '';
			$file = "";
			}
		
		if( $fPage[0][9] > 1 ) {
			if( $page > $issue[0][6] ) {}
			else {
				$w2 = $fPage[0][9] * 84;
				$w = $w2;
				}
			}
		
		$scale = "width: ".$w."px; height: ".$h."px;";
		
		$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
		if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
			if( $page > $issue[0][6] ) {
				if( $w > $h ) {
					$page_thumb .= " background-size:".$w."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."?".$imghash." ); background-position: center; ";
					}
				else {
					$page_thumb .= " background-size:".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."?".$imghash." ); background-position: center; ";
					}
				}
			else {
				$page_thumb .= " background-size:".$w."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."?".$imghash." ); background-position: center; ";
				}
			}
			
		if( $fPage[0][9] < 2 && $page > 0 ) {
			if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
				list( $w2, $h2 ) = getimagesize( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file );
				
				if( $w2 > $h2 ) {
					$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
					if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) )
						$page_thumb .= " background-size: contain; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."); background-position: center;";
					}
				elseif( $w2 < 81 ) {
					$percent = $w2 / $w * 100;
					$h2 = intval( $h / 100 * $percent );

					$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
					if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) )
						$page_thumb .= " background-size:".$w2."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."); background-position: center;";
					}
				}
			}

		$holderWidth += $w;
		//if( $page == 1 ) $holderWidth += $w;
		if( $page == 1 ) $holderWidth += 81;
		if( intval( $page )%2 != "" ) {
			if( $page == 1 && $fPage[0][9] > 1 && $_GET["type"] == "fpPreview" ) {
				$txt .= "";
				}
			else {
				$txt .= "<div style='float: left; height: ".($h+32)."px; width: ".($holderWidth+3)."px;'></div>";
				}
				
			$holderWidth = 0;
			}

		switch( $_GET['filter'] ) {
			case 'all':
				$display = "block";
				break;
			case 'newUploads':
				$viewed = explode( ",", $fPage[0][10] );
				if( in_array( $_GET['intra_user'], $viewed ) ) {
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
				if( $fPage[0][4] == 2 ) {
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
					$txt .= "<div id='".$page."_selector' class='selectLeft' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='position: absolute; left: 0px; z-index: 2; border-right: 1px solid #ADADAD;'>";	
					}
				else {
					$txt .= "<div id='".$page."_selector' class='selectLeft' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='opacity: 0.15; position: absolute; left: 0px; z-index: 2; border-right: 1px solid #ADADAD;'>";					
					}
				break;
			case 'right':
				if( $display == "block" ) {
					$txt .= "<div id='".$page."_selector' class='selectRight' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='position: absolute; right: 0px; z-index: 2;'>";	
					}
				else {
					$txt .= "<div id='".$page."_selector' class='selectRight' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='opacity: 0.15; position: absolute; right: 0px; z-index: 2;'>";	
					}
				break;
			}
			
		if( $page == 1 )
			$txt .= "<div class='pageBox' style='border-left: 1px solid #ADADAD; position: relative; width: ".$w."px; height: ".($h+34)."px;'>";
		else
			$txt .= "<div class='pageBox' style='position: relative; width: ".$w."px; height: ".($h+34)."px;'>";

		$alterPage = array();
		if( $fPage[0][0] != "" ) {
			if( $alterP[$page]!= "" && in_array( $fPage[0][6], $acceptType ) )
				$alterPage = sql_get( 'pageinfo', $typeSelect.' AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!="" AND fin="'.$fin.'"', '*' );
			}
		else {
			if( $alterP[$page]!= "" ) {
				$alterPage = sql_get( 'pageinfo', $typeSelect.' AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!="" AND fin="'.$fin.'"', '*' );
				
				
				}
			}
						
		$Counter = count( $alterPage );
		$txt .= "<input type='hidden' id='".$page."_current' name='".$page."_current' value='0'><input type='hidden' id='".$page."_max' name='".$page."_max' value='".$Counter."'>";
		if( $Counter > 0 ) {
			$alters[] = $page;
			$top = 15+($h/2)-10;
			$txt .= "<div id='".$page."_left' style='top: ".$top."px;' class='flip_left'><img src='images/icons/arrow_left.png' onclick=''></div>";
			$txt .= "<div id='".$page."_right' style='top: ".$top."px;' class='flip_right'><img src='images/icons/arrow_right.png' onclick=''></div>";
			$alt = 0;
			while( $alt < $Counter ) {
				$alterCommentMark = commentMark( $alterPage[$alt], $id );
				if( $fPage[0][6] == "ad" ) {
					$pub = sql_get( "packages", "id='".$alterPage[$alt][1]."' LIMIT 1", "directory" );
					if( $pageType == "normal" )
						$alterFile = $pub[0][0]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					elseif( $pageType == "FIN" )
						$alterFile = $pub[0][0]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					else
						$alterFile = "_".$pageType."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					
					$alterTextColor = "FFFFFF";
					}
				else {
					$alterTextColor = "000000";
					$pub = sql_get( "packages", "id='".$alterPage[$alt][1]."' LIMIT 1", "directory" );
					if( $pageType == "normal" )
						$alterFile = $pub[0][0]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					elseif( $pageType == "FIN" )
						$alterFile = $pub[0][0]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					else
						$alterFile = "_".$pageType."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					
					}
				if( $alterPage[$alt][9] > 1 ) {
					$lPage = $page;
					}
				elseif( $page%2 == 0 ) {
					$lPage = $page;
					}
				else {
					$check = sql_get( 'pageinfo', $typeSelect.' AND page="'.($page-1).'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" LIMIT 1', 'id' );
					if( $check[0][0] != "" ) {
						$lPage = $page-1;
						}
					else {
						$lPage = $page;
						}
					}
				
				//error_log( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$alterFile );
				if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$alterFile ) ) {
					if( $pageType == "normal" )
						$altLink = "?page=flatplan_preview&pack_id=".$alterPage[$alt][1]."&clk=".$page."&id=".$id."&p=".$lPage."&tag=".$alterPage[$alt][8];
					else
						$altLink = "?page=flatplan_preview&pack_id=".$alterPage[$alt][1]."&clk=".$page."&alter=".$_GET['opt']."&id=".$id."&p=".$lPage."&tag=".$alterPage[$alt][8];
				
					$alterThumb = "cursor: pointer; background-repeat:no-repeat; background-size:".$w."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$alterFile.");";
					}
				else {
					$altLink = "";
					$alterThumb = "background-color: #FFFFFF !important;";
					}
			
				$txt .= "<div alter='".($alt+1)."' id='".$alterPage[$alt][1]."_".$page."' state='".$alterPage[$alt][8]."' item='".$alterPage[$alt][1]."' page='".$page."' class='".$class."_pagenr pagenr checking2' style='z-index: ".(1000-($alt+1))."; width: ".($w)."px; ".$secBg." color: #".$alterTextColor."'>";
				if( $alterPage[$alt][3] != "" )
					$altVersion = "v".$alterPage[$alt][3];
				if( $class == 'right' ) {
					$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".$altVersion."</div><div style='pointer-events: none; float:right; margin-right: 2px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div>";
					}
				elseif( $class == 'left' ) {
					$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 2px;'>".$altVersion."</div>";
					}
					$txt .= "<div style='clear:both;'></div>";
				$txt .= "</div>";
				$txt .= "<div  id='".$page."_athumb_".($alt+1)."' state='".$alterPage[$alt][8]."' class='thumb' alter='".($alt+1)."' page='".$page."' ";
				if( $altLink != '' ) {
					if( $_GET['type'] == 'fpPreview' ) $txt .= " onclick='changePic(\"".$altLink."\")'";
					else $txt .= "double='".$altLink."'";
					}
				$txt .= " style='background: url(images/empty_slot.png); position: absolute; z-index: ".(1000-($alt+1))."; top: 17px; ".$scale." ".$alterThumb."'>";
				$txt .= "</div>";

				$title = "";
		
				switch( $alterPage[$alt][4] ) {
					case '3':
						$title = sql_aget( "action_log", "action='rejectPage' AND target='".$page."' AND magazine='".$magazine[0][0]."' AND issue='".$issue[0][10]."' AND info='".$alterPage[$alt][8]."' order by `id` DESC LIMIT 1", "*" );
						if( $title[0]["id"] != "" && ( $title[0]["user"] != "0" or $title[0]["user"] != "" ) ) {
							$u = sql_aget( "accounts", "id='".$title[0]["user"]."'", "*" );
							$title = "Rejected ".( $u[0]["full_name"] != "" ? "by ".$u[0]["full_name"] : ( $u[0]["name"] != "" ? "by ".$u[0]["name"]: "" ) )." at ".date( "Y-m-d H:i", $title[0]["date"] );
							}
						else {
							$title = "";
							}
						break;
				
					case '2':
						$title = sql_aget( "action_log", "action='approvePage' AND target='".$page."' AND magazine='".$magazine[0][0]."' AND issue='".$issue[0][10]."' AND info='".$alterPage[$alt][8]."' order by `id` DESC LIMIT 1", "*" );
						if( $title[0]["id"] != "" && ( $title[0]["user"] != "0" or $title[0]["user"] != "" ) ) {
							$u = sql_aget( "accounts", "id='".$title[0]["user"]."'", "*" );
							$title = "Approved ".( $u[0]["full_name"] != "" ? "by ".$u[0]["full_name"] : ( $u[0]["name"] != "" ? "by ".$u[0]["name"]: "" ) )." at ".date( "Y-m-d H:i", $title[0]["date"] );
							}
						else {
							$title = "";
							}
						break;
					}
			
				$txt .= "<div ".( $title != "" ? "title='".$title."'" : "" )." alter='".($alt+1)."' page='".$page."' style='position: absolute; z-index: ".(1000-($alt+1))."; bottom: 0px; width: ".$w."px;' class='page_footer state_".$alterPage[$alt][4]."'>";
					if( $class == 'right' ) {
						$txt .= "<div id='".$fPage[0][1]."_alterName' style='font-size: 12px; line-height: 16px; color: #000000; float:left; margin-top: 1px; margin-left: 3px;'>".substr( $alterPage[$alt][8], 0, -1 )."</div>";
						$txt .= "<div style='float: right; margin-top: 4px; margin-right: 3px;'>".$alterCommentMark."</div>";
						}
					elseif( $class == 'left' ) {
						$txt .= "<div style='float: left; margin-top: 4px; margin-left: 3px;'>".$alterCommentMark."</div>";
						$txt .= "<div id='".$fPage[0][1]."_alterName' style='font-size: 12px; line-height: 16px; float:right; color: #000000; margin-top: 1px; margin-right: 3px;'>".substr( $alterPage[$alt][8], 0, -1 )."</div>";
						}
					$txt .= "<div style='margin-top:-3px;'><input ";
					if( $altLink == '' )
						$txt .= 'disabled';
					
					$txt .= " type='checkbox' item='".$alterPage[$alt][1]."' state='".$alterPage[$alt][8]."' name='pageSelector[]' value='".$page."' style='display: none;'>";
				$txt .= "</div></div>";
				$alt++;
				}
			}
		$txt .= "<div alter='0' id='".$fPage[0][1]."_".$page."' item='".$fPage[0][1]."' page='".$page."' class='".$class."_pagenr pagenr checking2' style='z-index: 1000; width: ".($w)."px; ".$secBg." color: #".$textColor.";'>";
		
		if( $fPage[0][3] != "" )
			$version = "v".$fPage[0][3];
		
		if( $class == 'right' ) {			
			$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".$version."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div>";
			$commentPlace = "<div style='float: right; margin-top: 3px; margin-right: 3px;'>".$commentMark."</div>";
	
			if( $fPage[0][13] > 0 ) {	
				$proof = "<div class='proof' style='float: right; margin-top: 3px; margin-right: 0px; margin-left: 3px;'></div>";
				}
			//$proof = $check[0]["id"];
			if( $fPage[0][12] != "" ) {		
				$triangle = "<div class='".$fPage[0][12]."' style='float: left; margin-top: 3px; margin-left: 3px;'></div>";
				}
			}
		elseif( $class == 'left' ) {
			$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".$version."</div>";
			$commentPlace = "<div style='float: left; margin-top: 3px; margin-left: 3px;'>".$commentMark."</div>";		

			if( $fPage[0][13] > 0 ) {
				$proof = "<div class='proof' style='float: left; margin-top: 3px; margin-right: 3px; margin-left: 0px;'></div>";
				}
			
			if( $fPage[0][12] != "" ) {		
				$triangle = "<div class='".$fPage[0][12]."' style='float: right; margin-top: 3px; margin-right: 3px;'></div>";
				}
			}

		$txt .= "</div><div id='".$page."_thumb' state='' class='thumb' alter='0' page='".$page."'";
		if( $link != '' ) {
			if( $_GET['type'] == 'fpPreview' ) $txt .= " onclick='changePic(\"".$link."\")'";
			else $txt .= "double='".$link."'";
			}
		$txt .= " style='background-color: #DDD !important; background: url(images/empty_slot.png); position: absolute; z-index: 1000; top: 17px; ".$scale." ".$page_thumb."' ";
		$txt .= "></div>";
		
		$title = "";
		
		/*
		switch( $fPage[0][4] ) {
			case '3':
				$title = sql_aget( "action_log", "action='rejectPage' AND target='".$page."' AND magazine='".$magazine[0][0]."' AND issue='".$issue[0][10]."' AND info='' order by `id` DESC LIMIT 1", "*" );
				if( $title[0]["id"] != "" && ( $title[0]["user"] != "0" or $title[0]["user"] != "" ) ) {
					$u = sql_aget( "accounts", "id='".$title[0]["user"]."'", "*" );
					$title = "Rejected ".( $u[0]["full_name"] != "" ? "by ".$u[0]["full_name"] : ( $u[0]["name"] != "" ? "by ".$u[0]["name"]: "" ) )." at ".date( "Y-m-d H:i", $title[0]["date"] );
					}
				break;
				
			case '2':
				$title = sql_aget( "action_log", "action='approvePage' AND target='".$page."' AND magazine='".$magazine[0][0]."' AND issue='".$issue[0][10]."' AND info='' order by `id` DESC LIMIT 1", "*" );
				if( $title[0]["id"] != "" && ( $title[0]["user"] != "0" or $title[0]["user"] != "" ) ) {
					$u = sql_aget( "accounts", "id='".$title[0]["user"]."'", "*" );
					$title = "Approved ".( $u[0]["full_name"] != "" ? "by ".$u[0]["full_name"] : ( $u[0]["name"] != "" ? "by ".$u[0]["name"]: "" ) )." at ".date( "Y-m-d H:i", $title[0]["date"] );
					}
				break;
			}	
		*/
		
		$txt .= "<div ".( $title != "" ? "title='".$title."'" : "" )." alter='0' page='".$page."' style='position: absolute; z-index: 1000; bottom: 0px; width: ".$w."px;' class='page_footer state_".$fPage[0][4]."'>";
		$txt .= "<div id='".$fPage[0][1]."_alterName' style='float:left; margin-left: 3px;'></div>";
		$txt .= $commentPlace;
		$txt .= $proof;
		$txt .= $triangle;
		$txt .= "<input ";
		if( $link == '' )
			$txt .= 'disabled';
		$txt .= " type='checkbox' item='".$fPage[0][1]."' state='' name='pageSelector[]' value='".$page."' style='display: none;'></div></div></div>";
		
		return $txt;
		}

	function drawAmericanPage( $id, $page, $class, $i, $pageType = 'normal' ) {
		global $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $issue, $sizes, $path, $fin, $imghash;
		
		list( $w, $h ) = $sizes;

		if( $page == 0 ) {
			return "<div style='float: left;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w)."px; height: ".($h+30)."px;'></div></div>";
			}

		$txt = "";
		if( $pageType == "normal" or $pageType == "FIN" ) {
			$typeSelect = 'type!="PRE" AND type!="PSTR"';
			$acceptType = array( 'ad', 'magazine' );
			}
		else {
			$typeSelect = 'type="'.$pageType.'"';
			$acceptType = array( 'PRE' );
			}
		
		//error_log( $page );
		//error_log( print_r( $fPages2[ $page ], true ) );
		$fPage[0] = $fPages2[ $page ];
		//error_log( $fPage[0][0] );
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
				$file = $tempPage[0][1]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_preview.jpg";
				}
			elseif( $pageType == "FIN" ) {
				$file = $tempPage[0][1]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_preview.jpg";
				}
			else {
				$file = "_".strtoupper( $pageType )."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1].	"_preview.jpg";
				}
			}
		elseif( $pageType == "normal" ) {
			$textColor = "000000";
			$tempPage = sql_get( 'packages', 'publication_id="'.$issue[0][0].'" AND starting_page!="" ORDER BY `id` ASC', '*' );
			$rowCount = 0;
			$found = 0;
			foreach( $tempPage as $Pack ) {
				$temp = explode( "-", $Pack[3] );
				$start = intval( $temp[0] );
				if( $temp[1] != "" )
					$end = intval( $temp[1] );
				else
					$end = $start;
					
				if( $start <= intval( $page ) && $end >= intval( $page ) ) {
					$found = 1;
					break;
					}
				$rowCount++;
				}
			if( $tempPage[$rowCount][0] != "" && $found == 1 ) {
				$place = addingNumber( $tempPage[$rowCount][0] );
				$secBg = "background: #".$bPalette[$place]." !important;";
				}
				
			if( $found == 0 ) {
				unset( $secBg );
				unset( $textColor );
				}
			}	
		if( $fPage[0][9] > 1 or $page%2 == 0 or $fPages2[ (intval($page)-1) ][0] == "" ) {
			$lPage = $page;
			}
		elseif( $fPage[0][0] != "" ) {
			$lPage = $page-1;
			}
		
		//error_log( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file );
		
		if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
			$commentMark = commentMark( $fPage[0], $id, $fPage[0][16] );
			
			if( strpos( $file, "_ads" ) === false ) {
				if( $pageType == "normal" )
					$link = "?page=flatplan_preview&pack_id=".$fPages2[ $lPage ][1]."&clk=".$page."&id=".$id."&p=".$lPage;
				else
					$link = "?page=flatplan_preview&pack_id=".$fPages2[ $lPage ][1]."&clk=".$page."&alter=".$_GET['opt']."&id=".$id."&p=".$lPage;
				}
			else {
				if( $pageType == "normal" )
					$link = "?page=flatplan_preview&pack_id=".$fPages2[ $lPage ][1]."&clk=".$page."&type=ad&id=".$id."&p=".$lPage;
				else
					$link = "?page=flatplan_preview&pack_id=".$fPages2[ $lPage ][1]."&clk=".$page."&alter=".$_GET['opt']."&type=ad&id=".$id."&p=".$lPage;
				}	
			}
		elseif( $fPage[0][0] != "" ) {
			if( $fPage[0][6] == "ad" )
				unset( $secBg );
			unset( $fPage );
			$page_thumb = "";
			$link = '';
			$file = "";
			}
		
		if( $fPage[0][9] > 1 ) {
			if( $page > $issue[0][6] ) {}
			else {
				$w2 = $fPage[0][9] * 84;
				$w = $w2;
				}
			}
		
		$scale = "width: ".$w."px; height: ".$h."px;";
		
		$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
		//error_log("../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file);
		if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
			if( $page > $issue[0][6] ) {
				if( $w > $h ) {
					$page_thumb .= " background-size:".$w."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."?".$imghash." ); background-position: center; ";
					}
				else {
					$page_thumb .= " background-size:".$w."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."?".$imghash." ); background-position: center; ";
					}
				}
			else {
				$page_thumb .= " background-size:".$w."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."?".$imghash." ); background-position: center; ";
				}
			}
			
		if( $fPage[0][9] < 2 && $page > 0 ) {
			if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
				list( $w2, $h2 ) = getimagesize( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file );
				
				if( $w2 > $h2 ) {
					$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
					if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) )
						$page_thumb .= " background-size: contain; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."); background-position: center;";
					}
				elseif( $w2 < 81 ) {
					$percent = $w2 / $w * 100;
					$h2 = intval( $h / 100 * $percent );

					$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
					if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) )
						$page_thumb .= " background-size:".$w2."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file."); background-position: center;";
					}
				}
			}
		
		/*
		$holderWidth += $w;
		if( $page == 1 ) $holderWidth += $w;
		if( intval( $page )%2 != "" ) {
			$txt .= "<div style='float: left; height: ".($h+32)."px; width: ".($holderWidth+3)."px;'></div>";
			$holderWidth = 0;
			}
		*/
		
		switch( $_GET['filter'] ) {
			case 'all':
				$display = "block";
				break;
			case 'newUploads':
				$viewed = explode( ",", $fPage[0][10] );
				if( in_array( $_GET['intra_user'], $viewed ) ) {
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
				if( $fPage[0][4] == 2 ) {
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
					$txt .= "<div id='".$page."_selector' class='selectAmerican' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='position: absolute; left: 0px; z-index: 2; border-right: 1px solid #ADADAD;'>";	
					}
				else {
					$txt .= "<div id='".$page."_selector' class='selectAmerican' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='opacity: 0.15; position: absolute; left: 0px; z-index: 2; border-right: 1px solid #ADADAD;'>";					
					}
				break;
			case 'right':
				if( $display == "block" ) {
					$txt .= "<div id='".$page."_selector' class='selectRight' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='position: absolute; right: 0px; z-index: 2;'>";	
					}
				else {
					$txt .= "<div id='".$page."_selector' class='selectRight' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='opacity: 0.15; position: absolute; right: 0px; z-index: 2;'>";	
					}
				break;
			}
			
		$txt .= "<div class='pageBox' style='position: relative; width: ".$w."px; height: ".($h+34)."px;'>";

		$alterPage = array();
		if( $fPage[0][0] != "" ) {
			if( $alterP[$page]!= "" && in_array( $fPage[0][6], $acceptType ) )
				$alterPage = sql_get( 'pageinfo', $typeSelect.' AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!="" AND fin="'.$fin.'"', '*' );
			}
		else {
			if( $alterP[$page]!= "" ) {
				$alterPage = sql_get( 'pageinfo', $typeSelect.' AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!="" AND fin="'.$fin.'"', '*' );
				}
			}

		$Counter = count( $alterPage );
		$txt .= "<input type='hidden' id='".$page."_current' name='".$page."_current' value='0'><input type='hidden' id='".$page."_max' name='".$page."_max' value='".$Counter."'>";
		if( $Counter > 0 ) {
			$alters[] = $page;
			$top = 15+($h/2)-10;
			$txt .= "<div id='".$page."_left' style='top: ".$top."px;' class='flip_left'><img src='images/icons/arrow_left.png' onclick=''></div>";
			$txt .= "<div id='".$page."_right' style='top: ".$top."px;' class='flip_right'><img src='images/icons/arrow_right.png' onclick=''></div>";
			$alt = 0;
			while( $alt < $Counter ) {
				$alterCommentMark = commentMark( $alterPage[$alt], $id, $fPage[0][16] );
				if( $fPage[0][6] == "ad" ) {
					$pub = sql_get( "packages", "id='".$alterPage[$alt][1]."' LIMIT 1", "directory" );
					if( $pageType == "normal" )
						$alterFile = $pub[0][0]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					elseif( $pageType == "FIN" )
						$alterFile = $pub[0][0]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					else
						$alterFile = "_".$pageType."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					
					$alterTextColor = "FFFFFF";
					}
				else {
					$alterTextColor = "000000";
					$pub = sql_get( "packages", "id='".$alterPage[$alt][1]."' LIMIT 1", "directory" );
					if( $pageType == "normal" )
						$alterFile = $pub[0][0]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					elseif( $pageType == "FIN" )
						$alterFile = $pub[0][0]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					else
						$alterFile = "_".$pageType."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
					
					}
				if( $alterPage[$alt][9] > 1 ) {
					$lPage = $page;
					}
				elseif( $page%2 == 0 ) {
					$lPage = $page;
					}
				else {
					$check = sql_get( 'pageinfo', $typeSelect.' AND page="'.($page-1).'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" LIMIT 1', 'id' );
					if( $check[0][0] != "" ) {
						$lPage = $page-1;
						}
					else {
						$lPage = $page;
						}
					}
				
				//error_log( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$alterFile );
				if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$alterFile ) ) {
					if( $pageType == "normal" )
						$altLink = "?page=flatplan_preview&pack_id=".$alterPage[$alt][1]."&clk=".$page."&id=".$id."&p=".$lPage."&tag=".$alterPage[$alt][8];
					else
						$altLink = "?page=flatplan_preview&pack_id=".$alterPage[$alt][1]."&clk=".$page."&alter=".$_GET['opt']."&id=".$id."&p=".$lPage."&tag=".$alterPage[$alt][8];
				
					$alterThumb = "cursor: pointer; background-repeat:no-repeat; background-size:".$w."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$alterFile.");";
					}
				else {
					$altLink = "";
					$alterThumb = "background-color: #FFFFFF !important;";
					}
			
				$txt .= "<div alter='".($alt+1)."' id='".$alterPage[$alt][1]."_".$page."' state='".$alterPage[$alt][8]."' item='".$alterPage[$alt][1]."' page='".$page."' class='".$class."_pagenr pagenr checking2' style='z-index: ".(1000-($alt+1))."; width: ".($w)."px; ".$secBg." color: #".$alterTextColor."'>";
				if( $alterPage[$alt][3] != "" )
					$altVersion = "v".$alterPage[$alt][3];
				if( $class == 'right' ) {
					$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".$altVersion."</div><div style='pointer-events: none; float:right; margin-right: 2px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div>";
					}
				elseif( $class == 'left' ) {
					$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 2px;'>".$altVersion."</div>";
					}
					$txt .= "<div style='clear:both;'></div>";
				$txt .= "</div>";
				$txt .= "<div  id='".$page."_athumb_".($alt+1)."' state='".$alterPage[$alt][8]."' class='thumb' alter='".($alt+1)."' page='".$page."' ";
				if( $altLink != '' ) {
					if( $_GET['type'] == 'fpPreview' ) $txt .= " onclick='changePic(\"".$altLink."\")'";
					else $txt .= "double='".$altLink."'";
					}
				$txt .= " style='background: url(images/empty_slot.png); position: absolute; z-index: ".(1000-($alt+1))."; top: 17px; ".$scale." ".$alterThumb."'>";
				$txt .= "</div>";

				$title = "";
		
				switch( $alterPage[$alt][4] ) {
					case '3':
						$title = sql_aget( "action_log", "action='rejectPage' AND target='".$page."' AND magazine='".$magazine[0][0]."' AND issue='".$issue[0][10]."' AND info='".$alterPage[$alt][8]."' order by `id` DESC LIMIT 1", "*" );
						if( $title[0]["id"] != "" && ( $title[0]["user"] != "0" or $title[0]["user"] != "" ) ) {
							$u = sql_aget( "accounts", "id='".$title[0]["user"]."'", "*" );
							$title = "Rejected ".( $u[0]["full_name"] != "" ? "by ".$u[0]["full_name"] : ( $u[0]["name"] != "" ? "by ".$u[0]["name"]: "" ) )." at ".date( "Y-m-d H:i", $title[0]["date"] );
							}
						else {
							$title = "";
							}
						break;
				
					case '2':
						$title = sql_aget( "action_log", "action='approvePage' AND target='".$page."' AND magazine='".$magazine[0][0]."' AND issue='".$issue[0][10]."' AND info='".$alterPage[$alt][8]."' order by `id` DESC LIMIT 1", "*" );
						if( $title[0]["id"] != "" && ( $title[0]["user"] != "0" or $title[0]["user"] != "" ) ) {
							$u = sql_aget( "accounts", "id='".$title[0]["user"]."'", "*" );
							$title = "Approved ".( $u[0]["full_name"] != "" ? "by ".$u[0]["full_name"] : ( $u[0]["name"] != "" ? "by ".$u[0]["name"]: "" ) )." at ".date( "Y-m-d H:i", $title[0]["date"] );
							}
						else {
							$title = "";
							}
						break;
					}
			
				$txt .= "<div ".( $title != "" ? "title='".$title."'" : "" )." alter='".($alt+1)."' page='".$page."' style='position: absolute; z-index: ".(1000-($alt+1))."; bottom: 0px; width: ".$w."px;' class='page_footer state_".$alterPage[$alt][4]."'>";
					if( $class == 'right' ) {
						$txt .= "<div id='".$fPage[0][1]."_alterName' style='font-size: 12px; line-height: 16px; color: #000000; float:left; margin-top: 1px; margin-left: 3px;'>".substr( $alterPage[$alt][8], 0, -1 )."</div>";
						$txt .= "<div style='float: right; margin-top: 4px; margin-right: 3px;'>".$alterCommentMark."</div>";
						}
					elseif( $class == 'left' ) {
						$txt .= "<div style='float: left; margin-top: 4px; margin-left: 3px;'>".$alterCommentMark."</div>";
						$txt .= "<div id='".$fPage[0][1]."_alterName' style='font-size: 12px; line-height: 16px; float:right; color: #000000; margin-top: 1px; margin-right: 3px;'>".substr( $alterPage[$alt][8], 0, -1 )."</div>";
						}
					$txt .= "<div style='margin-top:-3px;'><input ";
					if( $altLink == '' )
						$txt .= 'disabled';
					
					$txt .= " type='checkbox' item='".$alterPage[$alt][1]."' state='".$alterPage[$alt][8]."' name='pageSelector[]' value='".$page."' style='display: none;'>";
				$txt .= "</div></div>";
				$alt++;
				}
			}
		$txt .= "<div alter='0' id='".$fPage[0][1]."_".$page."' item='".$fPage[0][1]."' page='".$page."' class='".$class."_pagenr pagenr checking2' style='z-index: 1000; width: ".($w)."px; ".$secBg." color: #".$textColor.";'>";
		
		if( $fPage[0][3] != "" )
			$version = "v".$fPage[0][3];
		
		if( $class == 'right' ) {			
			$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".$version."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div>";
			$commentPlace = "<div style='float: right; margin-top: 3px; margin-right: 3px;'>".$commentMark."</div>";
	
			if( $fPage[0][13] > 0 ) {	
				$proof = "<div class='proof' style='float: right; margin-top: 3px; margin-right: 0px; margin-left: 3px;'></div>";
				}
			//$proof = $check[0]["id"];
			if( $fPage[0][12] != "" ) {		
				$triangle = "<div class='".$fPage[0][12]."' style='float: left; margin-top: 3px; margin-left: 3px;'></div>";
				}
			}
		elseif( $class == 'left' ) {
			$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".$version."</div>";
			$commentPlace = "<div style='float: left; margin-top: 3px; margin-left: 3px;'>".$commentMark."</div>";		

			if( $fPage[0][13] > 0 ) {
				$proof = "<div class='proof' style='float: left; margin-top: 3px; margin-right: 3px; margin-left: 0px;'></div>";
				}
			
			if( $fPage[0][12] != "" ) {		
				$triangle = "<div class='".$fPage[0][12]."' style='float: right; margin-top: 3px; margin-right: 3px;'></div>";
				}
			}

		$txt .= "</div><div id='".$page."_thumb' state='' class='thumb' alter='0' page='".$page."'";
		if( $link != '' ) {
			if( $_GET['type'] == 'fpPreview' ) $txt .= " onclick='changePic(\"".$link."\")'";
			else $txt .= "double='".$link."'";
			}
		$txt .= " style='background-color: #DDD !important; background: url(images/empty_slot.png); position: absolute; z-index: 1000; top: 17px; ".$scale." ".$page_thumb."' ";
		$txt .= "></div>";
		
		$title = "";
		
		$txt .= "<div ".( $title != "" ? "title='".$title."'" : "" )." alter='0' page='".$page."' style='position: absolute; z-index: 1000; bottom: 0px; width: ".$w."px;' class='page_footer state_".$fPage[0][4]."'>";
		$txt .= "<div id='".$fPage[0][1]."_alterName' style='float:left; margin-left: 3px;'></div>";
		$txt .= $commentPlace;
		$txt .= $proof;
		$txt .= $triangle;
		$txt .= "<input ";
		if( $link == '' )
			$txt .= 'disabled';
		$txt .= " type='checkbox' item='".$fPage[0][1]."' state='' name='pageSelector[]' value='".$page."' style='display: none;'></div></div></div>";
		
		return $txt;
		}

	if( $_GET['op'] == 'endpage' ) {
		$pub = sql_aget( "publications", "id='".$_GET["pubid"]."'", "*" );
		$cikk = sql_aget( "flatplan_planner", "pub_id='".$_GET["pubid"]."' AND name ='".$_GET["name"]."' AND mixed='0' order by pos asc", "*" );
		$pages = $pub[0]["pages"];

		$txt = "";
		$allowed = array();
		for( $i = $_GET["start"]; $i <= $pages; $i++ ) {
			$check = sql_aget( "flatplan_planner", "pub_id='".$_GET["pubid"]."' AND pos='".$i."'", "*" );
			
			if( empty( $check[0]["id"] ) or $check[0]["name"] == $cikk[0]["name"] ) {
				$allowed[] = $i;
				}
				
			else {
				break;
				}
			}
		
		$cikk = sql_aget( "flatplan_planner", "pub_id='".$_GET["pubid"]."' AND name ='".$cikk[0]["name"]."' AND mixed='0' order by pos DESC", "*" );
		$end = $_GET["start"] + count( $cikk ) - 1;
		for( $i = 0; $i < count( $allowed ); $i++ ) {
			$txt .= "<option ".( $end == $allowed[$i] ? "selected" : "" )." value='".$allowed[$i]."'>".$allowed[$i]."</option>";
			}
		
		$result = $txt;
		}
		
	if( $_GET['op'] == 'loadPagePair' ) {
		$nopages = 0;
		sql_update( 'accounts', 'fpFilter="'.$_GET['filter'].'"', 'id="'.$_GET['intra_user'].'"' );
		
		$alters = array();
		$holderWidth = 0;
		$text = '';
		$imghash = $_GET['cache'];

		$myPublisher = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', 'publisher' );	
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
		$alterP = array();
		$alterSql = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!="" AND fin="'.$fin.'" ORDER BY `page` ASC', 'page, type' );
		foreach( $alterSql as $alterRow ) {
			$alterP[ $alterRow[0] ] = $alterRow[1];
			}
					
		$fPages2 = array();	
		$fPagesSql = sql_get( 'pageinfo', $typeSelect.' AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="" AND fin="'.$fin.'"', '*' );
		foreach( $fPagesSql as $fP ) {
			$fPages2[ intval($fP[5]) ] = $fP;
			}
		
		//error_log( print_r( $fPages2, true ) );
		
		if( $_GET["pageNumbering"] == "American" ) {
			$_SESSION["fp_part"] = $_GET["part"];
			$moreQuery = "";
			if( $_GET["opt"] == "FIN" ) {
				$moreQuery .= " AND fin='1'";
				}
			else {
				$moreQuery .= " AND fin='0'";
				}
			$pages = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$issue[0][10]."' AND part='".$_GET["part"]."' ".$moreQuery." ORDER BY page ASC", "*" );
			$fPages2 = array();	
			foreach( $pages as $fP ) {
				$fPages2[ intval($fP[5]) ] = $fP;
				}
			$first = $pages[0][5];
			$last = $pages[count($pages)-1][5];
			$length = $last;
			$ptype = $_GET["opt"];
			if( empty( $ptype ) ) {
				$ptype = "normal";
				}
			
			//error_log( "code='".$magazine[0][3]."' AND issue='".$issue[0][10]."' AND part='".$_GET["part"]."' ".$moreQuery." ORDER BY page ASC LIMIT 2" );
			$sizes = sql_get( 'pageinfo', "code='".$magazine[0][3]."' AND state='' AND issue='".$issue[0][10]."' AND part='".$_GET["part"]."' ".$moreQuery." LIMIT 2", '*' );
			$sizes = calculateSize( $sizes[0], $magazine[0][3], $issue[0][10] );
			$row = intval( intval($_GET['maxWidth'] )/229 );
			$divWidth = $row*229;
				
			//$text = $_GET["opt"];
			if( count($pages) > 0 ) {
				if( $_GET["part"] == "MELL" or $_GET["part"] == "BEL" ) {
					for( $i = 0; $i <= $last; $i++ ) {
						$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px; height: ".($sizes[1]+28)."px; width: ".(2*$sizes[0])."px;'>";
							$text .= drawAmericanPage( $_GET['id'], $i, 'left', $i, $ptype );
							if( ($i+1) <= $length ) {
								$text .= drawAmericanPage( $_GET['id'], ($i+1), 'right', $i, $ptype );
							}
						$text .= "</div>";
						$counter++;
						$i++;
						}
					}
				else {
					if( count( $pages ) >= 24 ) {}
					if( count( $pages ) < 24 ) {
						$sizes[0] = $sizes[0]*2;
						$sizes[1] = $sizes[1]*2;
						}
					if( count( $pages ) < 24 ) {
						$sizes[0] = $sizes[0]*3.5;
						$sizes[1] = $sizes[1]*3.5;
						}
					
					if( $_GET["type"] == "fpPreview" ) {
						if( $sizes[0] > 186 ) {
							$arany = 186 / $sizes[0];
							$sizes[0] = 186;
							$sizes[1] = $sizes[1] * $arany;
							}
						}
					
					for( $i = $first; $i <= $last; $i++ ) {
						$marad = $i % 2;
						$extracss = "";
						
						$text .= '<div class="americanPage" style="width: '.$sizes[0].'px; height: '.( $sizes[1] + 34 ).'px; '.$extracss.'">';
							$text .= drawAmericanPage( $_GET['id'], $i, 'left', $i, $ptype );
						$text .= '</div>';
						}
					}
				}
			else {
				if( $_GET["type"] == "fpPreview" ) {
					$nopages = 1;
					}
				else {
					$text = "<div class='flatplan_nopage'>".$lang["flatplan"]["nopage"]."</div>";
					}
				}
			}
		else {
			$sizes = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND width="1" AND issue="'.$issue[0][10].'" AND state="" AND fin="'.$fin.'" ORDER BY page ASC LIMIT 2', '*' );
			$sizes = calculateSize( $sizes[1], $magazine[0][3], $issue[0][10] );
			$row = intval( intval($_GET['maxWidth'] )/229 );
			$divWidth = $row*229;
						
			if( $_GET['opt'] == "PLAN" ) {
				$maxcsempe = floor( intval($_GET['maxWidth'] )/175.5 );		
				
				$currentArticle = "";
				$colors = sql_aget( 'article_colors', '1', '*' );
				$articleCounter = 1;
				
				$length = intval( $issue[0][6] );
				$counter = 1;
				$i = 0;
				while( $i <= $length ) {
					if( $counter > $maxcsempe ) $counter = 1;
					$text .= "<div style='position: relative; display: inline-block; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
						$text .= drawPlannerPage( $_GET['id'], $i, 'left', $i );
						
						$text .= '<div style="float: left; height: 115px; width: 165px;"></div>';
						if( ($i+1) <= $length ) {
							$text .= drawPlannerPage( $_GET['id'], ($i+1), 'right', $i );
							}
							
					$text .= "</div>";
					$counter++;
					$i += 2;
					}
				}
				
			elseif( $_GET['opt'] == '' or $_GET['opt'] == 'FIN' ) {
				$length = intval( $issue[0][6] );
				if( $length == 0 ) {
					$moreQuery = "";
					if( $_GET["opt"] == "FIN" ) {
						$moreQuery .= " AND fin='1'";
						}
					else {
						$moreQuery .= " AND fin='0'";
						}
					$pages = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$issue[0][10]."' AND part='' ".$moreQuery." ORDER BY page DESC LIMIT 1", "*" );
					$length = $pages[0][5];
					}
				$counter = 1;
				$i = 0;
				
				if( $_GET["type"] == "fpPreview" ) {
				//if( 0 ) {
					while( $i <= $length ) {
						if( $counter == 5 ) $counter = 1;
						
						$leftPage = sql_aget( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND page="'.$i.'" AND issue="'.$issue[0][10].'" AND state="" AND fin="'.$fin.'" ORDER BY page ASC LIMIT 1', '*' );
						$rightPage = sql_aget( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND page="'.($i+1).'" AND issue="'.$issue[0][10].'" AND state="" AND fin="'.$fin.'" ORDER BY page ASC LIMIT 1', '*' );
						
						if( $leftPage[0]["width"] > 1 ) {
							if( $_GET['opt'] == 'FIN' ) {
								$text .= "<div class='widePage' style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
									$text .= drawPage( $_GET['id'], $i, 'left', $i, "FIN" );
								$text .= "</div>";
								$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: ".( 10 + $sizes[0] )."px; margin-bottom: 6px;'>";
									$text .= drawPage( $_GET['id'], ($i+1), 'left', $i, "FIN" );
								$text .= "</div>";
								}
							else {
								$text .= "<div class='widePage' style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
									$text .= drawPage( $_GET['id'], $i, 'left', $i );
								$text .= "</div>";
								$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: ".( 10 + $sizes[0] )."px; margin-bottom: 6px;'>";
									$text .= drawPage( $_GET['id'], ($i+1), 'left', $i );
								$text .= "</div>";
								}
							}
						
						elseif( $rightPage[0]["width"] > 1 ) {
							if( $_GET['opt'] == 'FIN' ) {
								$text .= "<div class='widePage' style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
									$text .= drawPage( $_GET['id'], ($i+1), 'left', $i, "FIN" );
								$text .= "</div>";
								}
							else {
								$text .= "<div class='widePage' style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
									$text .= drawPage( $_GET['id'], ($i+1), 'left', $i );
								$text .= "</div>";
								}
							}
						
						else {
							if( $_GET['opt'] == 'FIN' ) {
								$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
									$text .= drawPage( $_GET['id'], $i, 'left', $i, "FIN" );
									$text .= drawPage( $_GET['id'], ($i+1), 'right', $i, "FIN" );
								$text .= "</div>";
								}
							else {
								$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
									$text .= drawPage( $_GET['id'], $i, 'left', $i );
									$text .= drawPage( $_GET['id'], ($i+1), 'right', $i );
								$text .= "</div>";
								}
							}
							
						$i += 2;
						$counter++;
						}
					}
				
				while( $i <= $length ) {				
					if( $counter == 5 ) $counter = 1;
					if( $_GET['opt'] == 'FIN' ) {
						$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
							$text .= drawPage( $_GET['id'], $i, 'left', $i, "FIN" );
							$text .= drawPage( $_GET['id'], ($i+1), 'right', $i, "FIN" );
						$text .= "</div>";
						}
					else {
						$text .= "<div style='position: relative; float: left; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
							$text .= drawPage( $_GET['id'], $i, 'left', $i );
							$text .= drawPage( $_GET['id'], ($i+1), 'right', $i );
						$text .= "</div>";
						}
					$i += 2;
					$counter++;
					}
				}
			else {		
				$files = load_dir_files( $path."/_".strtoupper( $_GET['opt'] ), '_preview.jpg' );
				sort( $files );
				
				$start = explode( "_", $files[0] );
				$start = 0;
				
				$end = explode( "_", $files[count($files)-1] );
				$end = intval( $end[0] );
				
				if( $end > 0 ) {
					$i = $start;
					while( $i <= $end ) {
						$text .= "<div style='position: relative; float: left; margin: 5px; margin-bottom: 6px; width: 165px;'>";
							$text .= drawPage( $_GET['id'], $i, 'left', $i,  "PRE" );
							if( $i < $end )
								$text .= drawPage( $_GET['id'], ($i+1), 'right', $i, "PRE" ); 
						$text .= "</div>";
						$i += 2;
						}
					}
				else {
					$text = "<div class='flatplan_nopage'>".$lang["flatplan"]["nopage"]."</div>";
					}
				}
			}
		$result[0] = $text;
		$result[1] = $alters;
		$result[2] = $nopages;
		}
	
	if( $_GET['op'] == 'saveTool' ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		
		if( $user[0] != "" && $_POST['data'] != "" ) {
			$data = explode( "=", $_POST['data'] );
			if( $data[1] != 'widePage' ) {
				sql_update( 'accounts', $data[0].'="'.$data[1].'"', 'id="'.$_SESSION['intra_user'].'"' );
			
				switch( $data[0] ) {
					case 'fpPages':
						break;
					}
				}
			$result = "success";
			}
		else {
			$result = "false";
			}
		}
		
	if( $_GET['op'] == 'updatePageStatus' ) {
		switch( $_GET['value'] ) {
			case 'accept':
				$status = 2;
				$stat = "approvePage";
				break;
			case 'decline':
				$status = 3;
				$stat = "rejectPage";
				break;		
			case 'cancel':
				$status = 0;
				$stat = "cancelApprove";
				break;
			}
		
		$page = sql_get( "pageinfo", "id='".$_GET['pageID']."'", "*" );
		if( $page[0][6] == "ad" ) {
			$pack = sql_get( "ads", "id='".$page[0][1]."'", "pub_id, directory" );
			}
		else {
			$pack = sql_get( "packages", "id='".$page[0][1]."'", "publication_id, directory" );
			}
		
		$publication = sql_get( "publications", "id='".$pack[0][0]."'", "*" );
		$pub = sql_get( "publications", "id='".$pack[0][0]."'", "publisher_id" );
		$pub = sql_get( "publishers", "id='".$pub[0][0]."'", "name" );
		$user = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );
		$date = time();
		if( $status == 2 ) {		
			$file = "packages/".$page[0][7]."/".$page[0][2]."/";
			switch( $page[0][6] ) {
				case 'ad':
					$file .= "_ads/".str_pad( $page[0][5] , 3, '0', STR_PAD_LEFT)."_".$page[0][1]."_ad_preview.pdf";
					break;
				case 'PRE':
					$file .= "_PRE/".str_pad( $page[0][5] , 3, '0', STR_PAD_LEFT)."_".$page[0][1]."_preview.pdf";
					break;
				default:
					$file .= $pack[0][1]."/".( $page[0][11] == "1" ? "FIN/" : "" ).str_pad( $page[0][5] , 3, '0', STR_PAD_LEFT)."_".$page[0][1]."_".$page[0][8]."preview.pdf";
					break;				
				}
								
			$sfile = explode( "/", $file );
			$sfilename = end($sfile);
			array_pop($sfile);
			$sfilepath = implode( "/", $sfile );

			$array = array(
				"event" => "page_approved",
				"client" => $pub[0][0],
				"jobCode" => $page[0][7],
				"issue" => $page[0][2],
				"description" => $page[0][5],
				"remark" => $user[0][7],
				"date" => date( "Y-m-d\TH:i:s", $date ),
				"pageNum" => $page[0][5],
				"pageType" => ( $page[0][11] == "1" ? "FIN" : ( $page[0][6] == "PRE" ? "PRE" : "NOR" ) ),
				"pageVersion" => ( $page[0][8] == "" ? "-baseversion-" : substr( $page[0][8], 0, -1 ) ),
				);
			
			$sfile = array( 
				"name" => $sfilename,
				"path" => $sfilepath,
				);
			
			$newname = $sfilename;
			if( !empty( $page[0][17] ) ) {
				$newname = $page[0][17];
				}
			error_log("File neve a switchen: ".$newname );
			$error = SwitchSend_Rename( $array, $sfile, $newname );

			/*$array["client"] = $pub[0][0];
			$array["jobCode"] = $page[0][7];
			$array["issue"] = $page[0][2];
			$array["event"] = 'page_approved';
			$array["description"] = $page[0][5];
			$array["remark"] = $user[0][7];
			$array["date"] = date( "Y-m-d\TH:i:s", $date );
			$array["pageNum"] = str_pad( $page[0][5], 3, '0', STR_PAD_LEFT);
			$array["pageType"] = ( $page[0][11] == "1" ? "FIN" : ( $page[0][6] == "PRE" ? "PRE" : "NOR" ) );
			$array["pageVersion"] = ( $page[0][8] == "" ? "-baseversion-" : substr( $page[0][8], 0, -1 ) );			
		
			$myxml = array_to_xml( $array, 'eventComm' );
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($myxml);
			$dom->formatOutput = true;
		
			$counter = get_counter('..');
			$saveTo = 'C_Hotfolders/messages/message_'.$counter;
			inc_counter('..');

			$sftp = ftp_conn("../");
			$sftp->put( $saveTo.'.pdf', $file, NET_SFTP_LOCAL_FILE );
			$sftp->put( $saveTo.'.xml', $dom->saveXML()  );*/
			}
			
		sql_update( 'pageinfo', 'status="'.$status.'"', 'id="'.$page[0][0].'"' );
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
		$values = array( $user[0][0], $stat, $publication[0][1], $publication[0][2], $publication[0][10], intval( $page[0][5] ), $date, ( $page[0][11] == "1" ? "FIN" : ( $page[0][6] == "PRE" ? "PRE" : "NOR" ) ), $page[0][8] );
		sql_add( 'action_log', $names, $values );
		
		$result = $debug;
		}
	
	if( $_GET['op'] == 'colorPick' ) {
	    $realX = $_POST['data']['x'];
	    $realy = $_POST['data']['y'];
    
	    $terminalPath = TRKPATH;
	    
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
				$allowed = false;
				if( $_GET['fpver'] == "" && $fpstages == 1 ) $allowed = true;
				if( $_GET['fpver'] == "FIN" && ( $fpstages == 2 or $fpstages == 3 ) ) $allowed = true;

				//$text[] = $fpstages;
				if( ( $status[0][2] == "ad" or $status[0][2] == "magazine" ) && $allowed ) {
					
					$status = $status[0];
					
					switch( $status[0]) {
						case 2:
							if( $rights["cancelApprove"] ) {
								$allowed = ( ( $pub[0]["status"] == "created" or $pub[0]["status"] == "active" or $pub[0]["status"] == "current" ) ? "true" : "false" );
								$t = "<div id='pstatus' style='width: auto; height: 35px;'>";
									$t .= "<div id='".$pageID."_acc' style='display: inline-block; cursor: pointer; margin-top: 0px;'>";
										$t .= "<div style='cursor: pointer; float: left; color: rgb( 1, 188, 0 ) !important; padding-top: 1px;'>".$lang["flatplan"]["approved"]."</div>";
										 if( $allowed == "true" ) {
											$t .= "<div onclick='approvePage( \"".$status[1]."\", \"cancel\" )' class='fpstatusbutton' style='background-color: #000000; margin-left: 5px;'>".$lang["flatplan"]["pages_cancel"]."</div>";
											}
									$t .= "</div>";
								$text[] = $t;
								}
				
							else {
								$text[] = "<div style='color: rgb( 1, 188, 0 ) !important; padding-top: 1px;'>".$lang["flatplan"]["approved"]."</div>";
								}
							break;
						case 3:
							$text[] = "<div style='color: rgb( 254, 0, 3 ) !important; padding-top: 1px;'>".$lang["flatplan"]["rejected"]."</div>";
							break;
						default:
							$text[$i] = "<div id='pstatus' style='width: auto; height: 35px;'>";
								if( $rights["acceptPage"] ) {
									$allowed = ( ( $pub[0]["status"] == "created" or $pub[0]["status"] == "active" or $pub[0]["status"] == "current" ) ? "true" : "false" );
									if( $allowed == "true" ) {
                    					if( isMobile() ) {
	                    					$text[$i] .= "<div onclick='approvePage( \"".$status[1]."\", \"decline\" )' id='".$pageID."_dec' style='cursor: pointer; display: inline-block;'><i class='fas fa-times mobileDecline'></i></div>";
	                    					$text[$i] .= "<div onclick='approvePage( \"".$status[1]."\", \"accept\" )' id='".$pageID."_acc' style='cursor: pointer; display: inline-block;'><i class='fas fa-check mobileAccept'></i></div>";
                    						}
                    					else {
											$text[$i] .= "<div id='".$pageID."_dec' style='display: inline-block; cursor: pointer; margin-top: 0px;'>";
												$text[$i] .= "<div onclick='approvePage( \"".$status[1]."\", \"decline\" )' class='fpstatusbutton' style='background-color: #b60000; z-index: 1000;'>".$lang["flatplan"]["pages_reject"]."</div>";
											$text[$i] .= "</div><div id='".$pageID."_acc' style='display: inline-block; cursor: pointer; margin-left: 5px; margin-top: 0px;'>";
												$text[$i] .= "<div onclick='approvePage( \"".$status[1]."\", \"accept\" )' class='fpstatusbutton' style='background-color: #009700; z-index: 1000;'>".$lang["flatplan"]["pages_approve"]."</div>";
											$text[$i] .= "</div>";
											
											
											$ttext[$i] .= "<div id='".$pageID."_dec' style='cursor: pointer; float: left; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>
													<div id='".$pageID."_dec_hover' style='display: none; position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline_hover.png'></div>
					
													<div id='".$pageID."_acc' style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>
													<div id='".$pageID."_acc_hover' style='display: none; position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept_hover.png'></div>";
												}
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

	if( $_GET['op'] == 'advert_preview_reloadbg' ) {
		$user = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );	
		if( $_POST['switchTo'] != "" && $user[0][26] != 0  ) {
			sql_update( 'accounts', 'cutBox="'.$_POST['switchTo'].'"', 'id="'.$_GET['intra_user'].'"' );
			}

		$preview = sql_get( 'ads', 'id=\''.$_GET['id'].'\'', '*' );
		$pub = sql_get( 'publications', 'id="'.$preview[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
		switch( $preview[0][3] ) {
			case '1/1':
				$type = 'F';
				break;
			case '2/1':
				$type = 'D';
				if( $_GET["p"] == "1" ) {
					$type .= "L";
					}
				if( $_GET["p"] == "2" ) {
					$type .= "R";
					}
				break;
			default:
				$type = 'P';
				break;
			}
		
		$file_name = strtoupper( $preview[0][2] ).'_'.$magazine[0][3].'_'.$pub[0][10].'_'.$type;
		$path = "advertisements/".$file_name.".pdf";

		$user = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );	
		$user[0][15] = "mediabox";
			
		$colors = $_POST['colors'];
		
		if( $user[0][14] == 'pair' && $_GET["p"]%2 == 1 && $_GET["p"] != 1 ) {
			$_GET["p"]--;
			}
		$bgDPI = 72;
		
		$file = array();
		$state = array();
		$ver = array();
		
		if( is_file( "../".$path ) ) {
			$file[0]["Path"] = $path;
			$file[0]["Name"] = $file_name.".pdf";
			$sizes = getBBox( "../".$path, "" );
			$file[0]["Right"] = $sizes['Right'];
			$file[0]["Top"] = $sizes['Top'];
			$file[0]["Width"] = $sizes['Width'];
			$file[0]["Left"] = 0;
			$file[0]["Bottom"] = 0;
			}
			
	    $terminalPath = TRKPATH;
	    list( $dtitles, $dcolors ) = getAllColors( $pageinfo );
	    if( $_POST['from'] == "changePic" ) {
	      for( $i = 0; $i < count( $dcolors ); $i++ ) {
	        $colors[($i+1)] = "true";
	        }
	      }
	     
		$postfix = $_GET['intra_user'];

		$correctionBox[2] = $correctionBoxTemp = $user[0][15];
		$box = getPDFBox2( "Mediabox Trimbox Cropbox Bleedbox", TRKPATH."/".$path );
		$differences = array(
			"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
			"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
			"Right" => ( $box["Mediabox"][2] - $box["Cropbox"][2] ),
			"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
			);
		switch( $correctionBoxTemp ) {
			case 'mediabox';
				$sizes = array(
					"Left" => $box["Cropbox"][0],
					"Bottom" => $box["Cropbox"][1],
					"Right" => $box["Cropbox"][2],
					"Top" => $box["Cropbox"][3]
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
		
		PDFtoImage_( $sizes, $terminalPath."/".$file[0]["Path"], "_bg".$postfix.".jpg", $colors );
		$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );
		
		$first = new Imagick( "r3/_bg".$postfix.".jpg" );
		$image = new Imagick();
		$image->newImage( pixel_( $fullSizes, $bgDPI ), pixel_( $sizes['Top'] , $bgDPI ), new ImagickPixel('rgb( 178, 178, 178 )') );
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
			
	$result = array( $imgData, $newsize, $cbox, $file, $pageID, $text, "", array( $prev_link, $next_link ), $fpPages, $sizes['Top'], $dcolors, $trim, $ver, $dtitles, $bleed, $crop );
	}
	
	if( $_GET['op'] == 'advert_colorPick' ) {
	    $realX = $_POST['data']['x'];
	    $realy = $_POST['data']['y'];
    
	    $terminalPath = TRKPATH."/advertisements";
	    
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
    	
    if( $_GET['op'] == 'loadarticles' ) {
	    $txt = "<table cellspacing='0' cellpadding='0' width='100%' height='100%' id='magtable'>";
	    	
	    $sql = sql_aget( "flatplan_planner", "pub_id='".$_GET["id"]."' AND type='ad' ORDER BY pos ASC", "*" );

		if( count( $sql ) > 0 ) {
			$txt .= "<tr>";
				$txt .= "<td style='font-family: r_bold; padding-top: 10px;'>Ads</td>";
			$txt .= "<tr>";
			}
		
		$current = "";
		$start = "";
		for( $i = 0; $i < count( $sql ); $i++ ) {
			if( $current != $sql[$i]["name"] ) {
				$current = $sql[$i]["name"];
				$start = $sql[$i]["pos"];
				}
			
			if( $current != $sql[($i+1)]["name"] ) {
				$txt .= "<tr>";
					$txt .= "<td>".$sql[$i]["name"]."</td>";
					$txt .= "<td align='right'>".( $start != $sql[$i]["pos"] ? $start."-" : "" )."".$sql[$i]["pos"]."</td>";
					
				$txt .= "</tr>";
				}
			}

	    $sql = sql_aget( "flatplan_planner", "pub_id='".$_GET["id"]."' AND type='article' ORDER BY pos ASC", "*" );

		if( count( $sql ) > 0 ) {
			$txt .= "<tr>";
				$txt .= "<td style='font-family: r_bold; padding-top: 10px;'>Articles</td>";
			$txt .= "<tr>";
			}
		
		$current = "";
		$start = "";
		for( $i = 0; $i < count( $sql ); $i++ ) {
			if( $current != $sql[$i]["name"] ) {
				$current = $sql[$i]["name"];
				$start = $sql[$i]["pos"];
				}
			
			if( $current != $sql[($i+1)]["name"] ) {
				$txt .= "<tr>";
					$txt .= "<td>".$sql[$i]["name"]."</td>";
					$txt .= "<td align='right'>".( $start != $sql[$i]["pos"] ? $start."-" : "" )."".$sql[$i]["pos"]."</td>";
					
				$txt .= "</tr>";
				}
			}
		
		$txt .= "<tr>";
			$txt .= "<td style='font-family: r_bold; padding-top: 10px;'>Workers</td>";
		$txt .= "<tr>";
		
		$mag = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
		$mag = sql_aget( "magazines", "id='".$mag[0]["magazine_id"]."'", "*" );
		
		$acc = sql_aget( "accounts", "publisher='".$mag[0]["publisher_id"]."' ORDER BY full_name ASC", "*" );
		$users = array();
		for( $i = 0; $i < count( $acc ); $i++ ) {
			$check = explode( ",", $acc[$i]["showMagazines"] );
			if( in_array( $mag[0]["id"], $check ) ) {
				$users[] = $acc[$i];
				}
			}
		
		for( $i = 0; $i < count( $users ); $i++ ) {
			$count = sql_aget( "flatplan_planner as fp JOIN flatplan_articletypes fa ON fa.id = fp.atype", "fp.pub_id='".$_GET["id"]."' AND fp.workerID='".$users[$i]["id"]."'", "fp.*, fa.*" );
			
			$time = 0;
			for( $t = 0; $t < count( $count ); $t++ ) {
				$time += $count[$t]["time"];
				}
			
			$time = minFormat( $time, "en" );
			
			
			$txt .= "<tr>";
				$txt .= "<td>".$users[$i]["full_name"]."</td>";
				$txt .= "<td align='right' style='".( count($count) == 0 ? "color: #FF0000;" : "" )."'>".$time." (".count($count)."p)</td>";
			$txt .= "</tr>";			
			}
		
		$txt .= "</table>";
		
		$result = $txt;
	    }
    
    if( $_GET['op'] == 'removeatype' ) {
	    sql_delete( "flatplan_articletypes", "id='".$_GET["id"]."'" );
	    }
    
    if( $_GET['op'] == 'modtypes' ) {
	    sql_update( "flatplan_articletypes", "color='".$_POST["data"]["color"]."', time='".$_POST["data"]["time"]."'", "id='".$_POST["data"]["id"]."'" );
	    }
	    
    if( $_GET['op'] == 'loadtypes' ) {
	    $txt = "";
	    $types = sql_aget( "flatplan_articletypes", "1 order by id ASC", "*" );
	    
	    for( $i = 0; $i < count( $types ); $i++ ) {
		    $txt .= "<tr id='".$types[$i]["id"]."'>";
		    	$txt .= "<td>".$types[$i]["name"]."</td>";
		    	$txt .= "<td><div class='articlecolorBox alreadyin' id='".$types[$i]["id"]."_cbox' style='background: #".$types[$i]["color"].";'></div></td>";
		    	$txt .= "<td>";
		    		$period = array( 15, 30, 45, 60, 75, 90 );
		    		$txt .= "<select name='".$types[$i]["id"]."_time' id='".$types[$i]["id"]."_time'>";
		    		for( $p = 0; $p < count( $period ); $p++ ) {
			    		$txt .= "<option ".( $period[$p] == $types[$i]["time"] ? "selected" : "" )." value='".$period[$p]."'>".$period[$p]." mins</option>";
			    		}	    		
		    		$txt .= "</select>";
		    	$txt .= "</td>";
		    	$txt .= "<td>";
		    		$txt .= "<i id='".$types[$i]["id"]."_save' onclick='modAtype( \"".$types[$i]["id"]."\" )' class='far fa-check-circle' style='cursor: pointer; font-size: 19px; color: #21ed43;'></i>";
		    		$check = sql_aget( "flatplan_planner", "atype='".$types[$i]["id"]."'", "*" );
		    		if( count( $check ) == 0 ) {
			    		$txt .= "&nbsp;<i id='".$types[$i]["id"]."_del' onclick='removeAtype( \"".$types[$i]["id"]."\", \"".$types[$i]["name"]."\" )' class='far fa-times-circle' style='cursor: pointer; font-size: 19px; color: #D22A33;'></i>";
			    		}
		    	$txt .= "</td>";
		    $txt .= "</tr>";
	    	}
	    	
	    $result = $txt;
	    }

    if( $_GET['op'] == 'sendHandout' ) {
	    $pub = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
	    $magazine = sql_get( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
	    $client = sql_get( 'publishers', 'id="'.$pub[0]["publisher_id"].'"', '*' );
	    
		$pages = $pub[0]["pages"];
		if( $pages == 0 ) {
			$pages = sql_aget( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pub[0][10]."' AND state='' AND fin='1'", "*" );
			$pages = count( $pages );
			
			if( $pages == 0 ) {
				$pages = sql_aget( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pub[0][10]."' AND state=''", "*" );
				$pages = count( $pages );
				}
			}
			
			
		
		$array = array(
			"event" => "handout",
			"client" => $client[0][1],
			"jobCode" => $magazine[0][3],
			"issue" => $pub[0]["code"],
			"pubName" => $magazine[0][2],
			"description" => $pages,
			);
		
		$response = SwitchSend( $array );
		
		$names = array( "userid", "pub_id", "filename", "date", "changed" );
		$values = array( $_SESSION["intra_user"], $pub[0]["id"], $magazine[0][3]."_".$pub[0]["code"]."_handout.pdf", time(), "0" );
		sql_add( "flatplan_handout", $names, $values );
		
		$result = $response;    
	    }    
	
	if( $_GET['op'] == 'loadhandoutmenu' ) {
		$txt = "";
		if( $rights["handouts"] ) {
			//echo "HANDOUT TEST";
			$txt .= "<div style='float: left; margin-left: 5px; margin-top: 4px;'>";
			
			$handout = sql_aget( "flatplan_handout", "pub_id='".$_GET["id"]."' ORDER BY id DESC LIMIT 1", "*" );
			
			$txt2 = "";
			$pub = sql_get( 'publications', 'id="'.$_GET["id"].'" ORDER BY `code` ASC', '*' );
			$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
			$haveall = true;
			$allpage = $pub[0][6];
			if( $allpage == 0 ) {
				$checker = sql_get( 'pageinfo', 'issue="'.$pub[0][10].'" AND code="'.$magazine[0][3].'" order by page DESC LIMIT 1', '*' );
				$allpage = intval( $checker[0][5] );
				}
				
			$fpstages = collectFromXml( "../xml/".PMD.".xml", $magazine[0][3], "FlatplanStages", $returnnode = '' );
			$fpstages = $fpstages["FlatplanStages"];
			
			$pages = sql_aget( "pageinfo", "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND page <= ".$allpage." AND fin='1' order by page ASC ", "*" );
			//error_log( "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND page <= ".$allpage." AND fin='1' order by page ASC " );
			//error_log( "PAGES IN SQL: ".count($pages)."" );
			if( count( $pages ) == 0 ) {
				$pages = sql_aget( "pageinfo", "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND page <= ".$allpage." AND fin='0' order by page ASC ", "*" );
				}
			
			for( $i = 1; $i <= $allpage; $i++ ) {				
				if( empty( $pages[ ( $i - 1 ) ]["id"] ) ) {
					$haveall = false;
					break;
					}
				
				if( $pages[ ( $i - 1 ) ]["width"] != "1" ) {
					$allpage -= $pages[ ( $i - 1 ) ]["width"] - 1;
					}
				}
			
			if( $haveall ) {
				if( $handout[0]["arrived"] == "1" ) {
					$icon = file_get_contents( "../images/sc_ready.svg" );
					$txt .= '<span onclick="showHandoutMenu()" id="book-icon" style="cursor: pointer;">'.$icon.'</span>';
					}
				else {
					$icon = file_get_contents( "../images/sc_available.svg" );
					$txt .= '<span onclick="showHandoutMenu()" id="book-icon" style="'.( $handout[0]["arrived"] == "0" ? "pointer-events: none;" : "" ).' cursor: pointer;">'.$icon.'</span>';
					}
				
				
				$loading = false;
				if( $handout[0]["arrived"] == "0" ) {
					$loading = true;
					}
				$txt .= "</div>";				
				
				if( $handout[0]["arrived"] == "1" ) {
					$txt2 .= '<li onclick="downloadHandout(\''.$handout[0]["id"].'\')">'.$lang["flatplan"]["downloadh"].'</li>';
					$check = str_replace( "handout", "stream", $handout[0]["filename"] );
					if( is_file( TRKPATH."/handout/".$check ) ) {
						$txt2 .= '<li onclick="viewhandout(\''.$check.'\')">'.$lang["flatplan"]["viewhandout"].'</li>';
						}					
					$txt2 .= '<li onclick="settingsPanel(\'hotlink_handout\', undefined, \''.$handout[0]["id"].'\'); $(\'#handoutBox\').hide(100);">'.$lang["flatplan"]["handouthotlink"].'</li>';
					//if( $handout[0]["changed"] == 1 ) {
						$txt2 .= '<li onclick="generateHandout()" style="margin-top: 4px;">'.$lang["flatplan"]["generatenewh"].'</li>';
					//	}					
					}
				else {
					$txt2 .= '<li onclick="generateHandout()" style="margin-top: 0px;">'.$lang["flatplan"]["generateh"].'</li>';
					}
				}			
			
			}
			
		$result = array( $txt, $txt2, $loading );		
		}
	
print json_encode( $result );
	
?>