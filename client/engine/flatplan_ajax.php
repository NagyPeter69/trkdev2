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
	// See client/plugins/pubsApply.php's 2026-09-05 fix - none of
	// this file's op== handlers checked authentication before
	// running. Same fix: one gate before any op is dispatched.
	if( empty( $user[0][0] ) ) {
		print json_encode( array( array( "Unauthorized" ) ) );
		exit;
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

	// getPartsMaxPage() now lives in engine/engine.php (shared with
	// ajax.php and manageFP.php, which also need it to keep
	// publications.pages in sync - see syncPublicationPages() there).

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

	function preloadCommentMarks( $id ) {
		global $myPublisher, $lang;

		$marks = array();
		$comments = sql_get( 'comments', 'pub_id="'.$id.'"', '*' );
		if( count( $comments ) == 0 ) return $marks;

		$roots = array();
		$latestReplyUser = array();
		foreach( $comments as $c ) {
			if( $c[2] == "0" ) {
				$roots[] = $c;
				}
			elseif( !isset( $latestReplyUser[ $c[2] ] ) || $c[0] > $latestReplyUser[ $c[2] ]['id'] ) {
				$latestReplyUser[ $c[2] ] = array( 'id' => $c[0], 'user' => $c[10] );
				}
			}
		if( count( $roots ) == 0 ) return $marks;

		$rootChecker = array();
		$checkerIds = array();
		foreach( $roots as $r ) {
			if( $r[12] == "approved" ) continue;
			$checker = isset( $latestReplyUser[ $r[0] ] ) ? $latestReplyUser[ $r[0] ]['user'] : $r[10];
			$rootChecker[ $r[0] ] = $checker;
			$checkerIds[ $checker ] = true;
			}

		$checkerPub = array();
		if( count( $checkerIds ) > 0 ) {
			$ids = implode( ',', array_map( function( $v ) { return '"'.$v.'"'; }, array_keys( $checkerIds ) ) );
			$accs = sql_get( 'accounts', 'id IN ('.$ids.')', 'id, publisher' );
			foreach( $accs as $a ) {
				$checkerPub[ $a[0] ] = $a[1];
				}
			}

		$byKey = array();
		foreach( $roots as $r ) {
			$key = $r[7].'|'.$r[13].'|'.$r[14].'|'.$r[16];
			if( !isset( $byKey[ $key ] ) ) {
				$byKey[ $key ] = array( "red" => 0, "green" => 0, "blue" => 0 );
				}
			if( $r[12] == "approved" ) {
				$byKey[ $key ]["blue"]++;
				}
			else {
				$checker = $rootChecker[ $r[0] ];
				$pub = isset( $checkerPub[ $checker ] ) ? $checkerPub[ $checker ] : "";
				if( $pub != $myPublisher[0][0] ) {
					$byKey[ $key ]["red"]++;
					}
				else {
					$byKey[ $key ]["green"]++;
					}
				}
			}

		foreach( $byKey as $key => $result ) {
			if( $result["red"] > 0 )
				$marks[ $key ] = "<div class='commentRed' title='".htmlspecialchars( $lang["flatplan"]["fpcomment_red_tip"] )."'></div>";
			elseif( $result["green"] > 0 )
				$marks[ $key ] = "<div class='commentGreen' title='".htmlspecialchars( $lang["flatplan"]["fpcomment_green_tip"] )."'></div>";
			else
				$marks[ $key ] = "<div class='commentBlue' title='".htmlspecialchars( $lang["flatplan"]["fpcomment_blue_tip"] )."'></div>";
			}

		return $marks;
		}

	function commentMark( $pageInfo, $id, $part="" ) {
		global $commentMarks;

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

		$key = $pageInfo[5].'|'.$pageType.'|'.$pageInfo[8].'|'.$part;
		return isset( $commentMarks[ $key ] ) ? $commentMarks[ $key ] : "";
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
		global $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $issue, $sizes, $path, $fin, $imghash, $pages, $length, $lang, $fpStages;
		
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
		
		$fPage[0] = $fPages2[ $page ] ?? null;
		if( !isset( $fPage[0] ) ) {
			// A page with no pageinfo row at all (covered by a neighbouring
			// wide/gatefold image, or simply not reached yet) used to leave
			// $fPage[0] null, and every one of the ~30 $fPage[0][N] reads
			// below - version, proof/diff/preflight marks, comments, approval
			// state, the row's own checkbox - warned on it individually.
			// Filling in pageinfo's own column count (21) of empty strings
			// matches the "no data" sentinel this codebase already uses
			// everywhere else ($fPage[0][0] == "" is literally how "empty
			// slot" is detected throughout this file) - PHP 8's non-numeric-
			// string comparison rules make every existing == / > / < check
			// against these behave exactly as before, just without the
			// warnings. Surfaced 2026-09-05 fixing QVN78's pages 218-220.
			$fPage[0] = array_fill( 0, 21, "" );
			}
		// Same reasoning as $fPage[0] above, one level up: none of the
		// branches below set $file/$link/$commentMark/$secBg/$textColor for
		// a page that's an ad without its ad flag, isn't in $fPages2, and
		// doesn't match a wide-package fallback either - genuinely nothing
		// to draw. Defaults here match what each branch already treats as
		// "no file"/"no link" elsewhere in this function (empty string,
		// is_file("") is safely false) rather than leaving them undefined
		// for the unconditional reads further down (footer text, checkbox,
		// comment mark, header color).
		$file = ""; $link = ''; $commentMark = ""; $secBg = ""; $textColor = "000000";
		if( $page == 0 ) {
			return "<div style='float: left;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='position: absolute; left: 0px; z-index: 2; width: ".($w)."px; height: ".($h+30)."px;'>&nbsp;</div></div>";
			}
		
		
		if( $page > $length and $fPage[0][0] == "" and $pageType != "PRE" ) {
				// This page number is genuinely beyond the issue's last page:
				// the "right" partner the trailing final page doesn't have.
				//
				// A group's in-flow width doesn't come from its visible page
				// elements (selectors, left_page/right_page, pageBox - all
				// position:absolute); it comes solely from the floated spacer
				// div the RIGHT-slot page emits when it closes out the pair
				// (the $page % 2 != 0 block below, spacer width =
				// accumulated $holderWidth). Returning early here - as this
				// branch always did, in every previous form - meant the
				// trailing group never emitted any spacer at all, so it had
				// literally ZERO in-flow width: its thumbnail just painted
				// wherever the float cursor stopped, occupying no layout
				// space (the "offshot last page" glitch, unfixable by
				// styling the phantom's own contents).
				//
				// Do exactly what a real partner page would do instead:
				// account for this slot's width and emit the closing spacer.
				// The group then occupies a full pair's width in flow and
				// wraps between rows precisely like every other spread -
				// joining the last row when a whole spread fits, starting a
				// fresh row when it doesn't - just with nothing drawn in the
				// right half. (The $issue[0][6] == 0 sibling branch below
				// already reserves $w*2 for this same situation, confirming
				// pair-width reservation was always the intended design.)
				$holderWidth += $w;
				$txt = "<div style='float: left; height: ".($h+32)."px; width: ".($holderWidth+3)."px;'></div>";
				$holderWidth = 0;
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
				
			if( $fpStages == "1" ) {
				// A 1-stage job's rendered file lives wherever
				// page_pdf-handler.php actually stored it based on this
				// row's own fin state (index 11), not wherever the view's
				// currently-selected stage/opt would otherwise imply - see
				// the FlatplanStages==1 handling where $fPages2 is built
				// above, which intentionally mixes fin=0 and fin=1 rows
				// into the same view for these jobs. $pageType only ever
				// becomes "FIN" when $_GET['opt']=='FIN', which such a job
				// can never send (no FIN chip to select it), so without this
				// branch every finished page here always checked the bare
				// (non-FIN) path - reproduced live 2026-09-05 against QVN78.
				// Same fix drawAmericanPage() already has; this function was
				// missing its half.
				if( $fPage[0][11] == "1" ) {
					$file = $tempPage[0][1]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_preview.jpg";
					}
				else {
					$file = $tempPage[0][1]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_preview.jpg";
					}
				}
			elseif( $pageType == "normal" ) {
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
				if( ( $temp[1] ?? "" ) != "" )
					$end = intval( $temp[1] );
				else
					$end = $start;
					
				if( $start <= intval( $page ) && $end >= intval( $page ) ) {
					$found = 1;
					break;
					}
				$rowCount++;
				}
			// $found must be checked first - PHP evaluates && left-to-right,
			// so checking the array access before $found meant it always ran
			// even when the loop above found nothing and left $rowCount one
			// past the last valid index.
			if( $found == 1 && ( $tempPage[$rowCount][0] ?? "" ) != "" ) {
				$place = addingNumber( $tempPage[$rowCount][0] );
				$secBg = "background: #".$bPalette[$place]." !important;";
				}
				
			if( $found == 0 ) {
				// Genuinely nothing here (no wide-package match either) -
				// leave $secBg/$textColor at their safe top-of-function
				// defaults rather than unsetting them, which only pushed the
				// same "undefined" warning onto whichever read comes next.
				$secBg = "";
				$textColor = "000000";
				}
			}	
		if( $fPage[0][9] > 1 or $page%2 == 0 or ( $fPages2[ (intval($page)-1) ][0] ?? "" ) == "" ) {
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
			// Ad slots keep their orange header (secBg, set above) even
			// before the ad's own preview file has landed - the slot being
			// booked/reserved for an ad is the thing being flagged, not
			// whether its content has arrived yet. Only the file-dependent
			// display fields get cleared here - $fPage itself must survive,
			// since everything below (version, proof/diff/preflight marks,
			// comments, approval-state footer class, the row's own checkbox)
			// still needs this page's real pageinfo row. unsetting it here
			// (as this did before) threw "undefined variable $fPage" for
			// every such page, silently losing all of that for the rest of
			// the render - reproduced live 2026-09-05 against QVN78, a
			// finished job with several booked-but-not-yet-filed pages.
			$page_thumb = "";
			$link = '';
			$file = "";
			}
		
		if( $fPage[0][9] > 1 ) {
			if( $page > $length ) {}
			else {
				$w2 = $fPage[0][9] * 84;
				$w = $w2;
				}
			}

		$scale = "width: ".$w."px; height: ".$h."px;";

		$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
		if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
			if( $page > $length ) {
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
		// PHP 7 compared this int to "" by converting "" to 0, so this only
		// fired for odd pages (1%2=1 != 0) - the intended "close out this
		// pair's spacer" trigger. PHP 8's "saner string to number
		// comparisons" stringifies the int instead ("0"/"1" != ""), which is
		// true either way - so this fired on every single page, inserting an
		// extra floated spacer div into a spread's flow on top of the one
		// already correctly added, widening the spread's container beyond
		// the two thumbnails' combined width and leaving a visible gap
		// between them instead of the pages touching at the spine.
		if( intval( $page )%2 != 0 ) {
			if( $page == 1 && $fPage[0][9] > 1 && ( $_GET["type"] ?? "" ) == "fpPreview" ) {
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
			default:
				// No filter selected (the normal starting state - $_GET['filter']
				// is empty until the user picks one) matched none of the cases
				// above, leaving $display undefined and every subsequent
				// "if ($display == 'block')" check silently false. Match 'all'
				// - no filter means show everything, not hide everything.
				$display = "block";
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
			if( ( $alterP[$page] ?? "" ) != "" && in_array( $fPage[0][6], $acceptType ) )
				$alterPage = sql_get( 'pageinfo', $typeSelect.' AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!="" AND fin="'.$fin.'"', '*' );
			}
		else {
			if( ( $alterP[$page] ?? "" ) != "" ) {
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
					if( ( $_GET['type'] ?? '' ) == 'fpPreview' ) $txt .= " onclick='changePic(\"".$altLink."\")'";
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

		// $version/$proof/$triangle/$preflight are only ever assigned inside the
		// conditionals below, but used unconditionally further down - a page
		// missing all of these (no version yet, no proof mark, no diff, no
		// preflight error) left them undefined, throwing PHP warnings on every
		// such page (the common case, not the exception).
		$version = ""; $proof = ""; $triangle = ""; $preflight = "";
		if( $fPage[0][3] != "" )
			$version = "v".$fPage[0][3];
		
		if( $class == 'right' ) {			
			$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".$version."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div>";
			$commentPlace = "<div style='float: right; margin-top: 3px; margin-right: 3px;'>".$commentMark."</div>";
	
			if( $fPage[0][13] > 0 ) {
				$proof = "<div class='proof' title='".htmlspecialchars( $lang["flatplan"]["fpproof_tip"] )."' style='float: right; margin-top: 3px; margin-right: 0px; margin-left: 3px;'></div>";
				}
			//$proof = $check[0]["id"];
			if( $fPage[0][12] != "" ) {
				$diffTip = ( $fPage[0][12] == "different" ) ? $lang["flatplan"]["fpdiff_changed_tip"] : $lang["flatplan"]["fpdiff_nochange_tip"];
				$triangle = "<div class='".$fPage[0][12]."' title='".htmlspecialchars( $diffTip )."' style='float: left; margin-top: 3px; margin-left: 3px;'></div>";
				}
			if( $fPage[0][18] == 1 ) {
				$preflight = "<div onclick='downloadPreflight(\"".$fPage[0][0]."\")' data-pageid='".$fPage[0][0]."' class='preflightError' style='float: left; margin-top: 3px; margin-left: 3px;'></div>";
				}
			}
		elseif( $class == 'left' ) {
			$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".$version."</div>";
			$commentPlace = "<div style='float: left; margin-top: 3px; margin-left: 3px;'>".$commentMark."</div>";

			if( $fPage[0][13] > 0 ) {
				$proof = "<div class='proof' title='".htmlspecialchars( $lang["flatplan"]["fpproof_tip"] )."' style='float: left; margin-top: 3px; margin-right: 3px; margin-left: 0px;'></div>";
				}

			if( $fPage[0][12] != "" ) {
				$diffTip = ( $fPage[0][12] == "different" ) ? $lang["flatplan"]["fpdiff_changed_tip"] : $lang["flatplan"]["fpdiff_nochange_tip"];
				$triangle = "<div class='".$fPage[0][12]."' title='".htmlspecialchars( $diffTip )."' style='float: right; margin-top: 3px; margin-right: 3px;'></div>";
				}
			if( $fPage[0][18] == 1 ) {
				$preflight = "<div onclick='downloadPreflight(\"".$fPage[0][0]."\")' data-pageid='".$fPage[0][0]."' class='preflightError' style='float: right; margin-top: 3px; margin-right: 3px;'></div>";
				}
			}

		$txt .= "</div><div id='".$page."_thumb' state='' class='thumb' alter='0' page='".$page."'";
		if( $link != '' ) {
			if( ( $_GET['type'] ?? '' ) == 'fpPreview' ) $txt .= " onclick='changePic(\"".$link."\")'";
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
		$txt .= $preflight;
		$txt .= "<input ";
		if( $link == '' )
			$txt .= 'disabled';
		$txt .= " type='checkbox' item='".$fPage[0][1]."' state='' name='pageSelector[]' value='".$page."' style='display: none;'></div></div></div>";
		
		return $txt;
		}

	function drawAmericanPage( $id, $page, $class, $i, $pageType = 'normal' ) {
		global $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $issue, $sizes, $path, $fin, $imghash, $fpStages, $lang, $length;
		
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
		$fPage[0] = $fPages2[ $page ] ?? null;
		if( !isset( $fPage[0] ) ) {
			// A page with no pageinfo row at all (covered by a neighbouring
			// wide/gatefold image, or simply not reached yet) used to leave
			// $fPage[0] null, and every one of the ~30 $fPage[0][N] reads
			// below - version, proof/diff/preflight marks, comments, approval
			// state, the row's own checkbox - warned on it individually.
			// Filling in pageinfo's own column count (21) of empty strings
			// matches the "no data" sentinel this codebase already uses
			// everywhere else ($fPage[0][0] == "" is literally how "empty
			// slot" is detected throughout this file) - PHP 8's non-numeric-
			// string comparison rules make every existing == / > / < check
			// against these behave exactly as before, just without the
			// warnings. Surfaced 2026-09-05 fixing QVN78's pages 218-220.
			$fPage[0] = array_fill( 0, 21, "" );
			}
		// Same reasoning as $fPage[0] above, one level up: none of the
		// branches below set $file/$link/$commentMark/$secBg/$textColor for
		// a page that's an ad without its ad flag, isn't in $fPages2, and
		// doesn't match a wide-package fallback either - genuinely nothing
		// to draw. Defaults here match what each branch already treats as
		// "no file"/"no link" elsewhere in this function (empty string,
		// is_file("") is safely false) rather than leaving them undefined
		// for the unconditional reads further down (footer text, checkbox,
		// comment mark, header color).
		$file = ""; $link = ''; $commentMark = ""; $secBg = ""; $textColor = "000000";
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
				
			if( $fpStages == "1" ) {
				// A 1-stage job's rendered file lives wherever
				// page_pdf-handler.php actually stored it based on this
				// row's own fin state (index 11), not wherever the view's
				// currently-selected stage/opt would otherwise imply - see
				// the FlatplanStages==1 handling where $fPages2 is built
				// above, which intentionally mixes fin=0 and fin=1 rows
				// into the same view for these jobs.
				if( $fPage[0][11] == "1" ) {
					$file = $tempPage[0][1]."/FIN/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_preview.jpg";
					}
				else {
					$file = $tempPage[0][1]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_preview.jpg";
					}
				}
			elseif( $pageType == "normal" ) {
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
				if( ( $temp[1] ?? "" ) != "" )
					$end = intval( $temp[1] );
				else
					$end = $start;
					
				if( $start <= intval( $page ) && $end >= intval( $page ) ) {
					$found = 1;
					break;
					}
				$rowCount++;
				}
			// $found must be checked first - PHP evaluates && left-to-right,
			// so checking the array access before $found meant it always ran
			// even when the loop above found nothing and left $rowCount one
			// past the last valid index.
			if( $found == 1 && ( $tempPage[$rowCount][0] ?? "" ) != "" ) {
				$place = addingNumber( $tempPage[$rowCount][0] );
				$secBg = "background: #".$bPalette[$place]." !important;";
				}
				
			if( $found == 0 ) {
				// Genuinely nothing here (no wide-package match either) -
				// leave $secBg/$textColor at their safe top-of-function
				// defaults rather than unsetting them, which only pushed the
				// same "undefined" warning onto whichever read comes next.
				$secBg = "";
				$textColor = "000000";
				}
			}	
		if( $fPage[0][9] > 1 or $page%2 == 0 or ( $fPages2[ (intval($page)-1) ][0] ?? "" ) == "" ) {
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
			// Ad slots keep their orange header (secBg, set above) even
			// before the ad's own preview file has landed - the slot being
			// booked/reserved for an ad is the thing being flagged, not
			// whether its content has arrived yet. Only the file-dependent
			// display fields get cleared here - $fPage itself must survive,
			// since everything below (version, proof/diff/preflight marks,
			// comments, approval-state footer class, the row's own checkbox)
			// still needs this page's real pageinfo row. unsetting it here
			// (as this did before) threw "undefined variable $fPage" for
			// every such page, silently losing all of that for the rest of
			// the render - reproduced live 2026-09-05 against QVN78, a
			// finished job with several booked-but-not-yet-filed pages.
			$page_thumb = "";
			$link = '';
			$file = "";
			}
		
		if( $fPage[0][9] > 1 ) {
			if( $page > $length ) {}
			else {
				$w2 = $fPage[0][9] * 84;
				$w = $w2;
				}
			}

		$scale = "width: ".$w."px; height: ".$h."px;";

		$page_thumb = "cursor: pointer; background-repeat:no-repeat;";
		//error_log("../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file);
		if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
			if( $page > $length ) {
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

		if( !is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
			// No render exists for this slot - let the empty_slot.png
			// placeholder pattern tile across the whole thumbnail box
			// instead of showing a single un-repeated copy in the corner
			// (visible now that the box is sized well beyond the tile's
			// native dimensions).
			$page_thumb .= " background-repeat: repeat;";
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
			default:
				// No filter selected (the normal starting state - $_GET['filter']
				// is empty until the user picks one) matched none of the cases
				// above, leaving $display undefined and every subsequent
				// "if ($display == 'block')" check silently false. Match 'all'
				// - no filter means show everything, not hide everything.
				$display = "block";
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
			if( ( $alterP[$page] ?? "" ) != "" && in_array( $fPage[0][6], $acceptType ) )
				$alterPage = sql_get( 'pageinfo', $typeSelect.' AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!="" AND fin="'.$fin.'"', '*' );
			}
		else {
			if( ( $alterP[$page] ?? "" ) != "" ) {
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
					if( ( $_GET['type'] ?? '' ) == 'fpPreview' ) $txt .= " onclick='changePic(\"".$altLink."\")'";
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

		// $version/$proof/$triangle/$preflight are only ever assigned inside the
		// conditionals below, but used unconditionally further down - a page
		// missing all of these (no version yet, no proof mark, no diff, no
		// preflight error) left them undefined, throwing PHP warnings on every
		// such page (the common case, not the exception).
		$version = ""; $proof = ""; $triangle = ""; $preflight = "";
		if( $fPage[0][3] != "" )
			$version = "v".$fPage[0][3];
		
		if( $class == 'right' ) {			
			$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".$version."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div>";
			$commentPlace = "<div style='float: right; margin-top: 3px; margin-right: 3px;'>".$commentMark."</div>";
	
			if( $fPage[0][13] > 0 ) {
				$proof = "<div class='proof' title='".htmlspecialchars( $lang["flatplan"]["fpproof_tip"] )."' style='float: right; margin-top: 3px; margin-right: 0px; margin-left: 3px;'></div>";
				}
			//$proof = $check[0]["id"];
			if( $fPage[0][12] != "" ) {
				$diffTip = ( $fPage[0][12] == "different" ) ? $lang["flatplan"]["fpdiff_changed_tip"] : $lang["flatplan"]["fpdiff_nochange_tip"];
				$triangle = "<div class='".$fPage[0][12]."' title='".htmlspecialchars( $diffTip )."' style='float: left; margin-top: 3px; margin-left: 3px;'></div>";
				}
			if( $fPage[0][18] == 1 ) {
				$preflight = "<div onclick='downloadPreflight(\"".$fPage[0][0]."\")' data-pageid='".$fPage[0][0]."' class='preflightError' style='float: left; margin-top: 3px; margin-left: 3px;'></div>";
				}
			}
		elseif( $class == 'left' ) {
			$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".$version."</div>";
			$commentPlace = "<div style='float: left; margin-top: 3px; margin-left: 3px;'>".$commentMark."</div>";

			if( $fPage[0][13] > 0 ) {
				$proof = "<div class='proof' title='".htmlspecialchars( $lang["flatplan"]["fpproof_tip"] )."' style='float: left; margin-top: 3px; margin-right: 3px; margin-left: 0px;'></div>";
				}

			if( $fPage[0][12] != "" ) {
				$diffTip = ( $fPage[0][12] == "different" ) ? $lang["flatplan"]["fpdiff_changed_tip"] : $lang["flatplan"]["fpdiff_nochange_tip"];
				$triangle = "<div class='".$fPage[0][12]."' title='".htmlspecialchars( $diffTip )."' style='float: right; margin-top: 3px; margin-right: 3px;'></div>";
				}
			if( $fPage[0][18] == 1 ) {
				$preflight = "<div onclick='downloadPreflight(\"".$fPage[0][0]."\")' data-pageid='".$fPage[0][0]."' class='preflightError' style='float: right; margin-top: 3px; margin-right: 3px;'></div>";
				}
			}

		$txt .= "</div><div id='".$page."_thumb' state='' class='thumb' alter='0' page='".$page."'";
		if( $link != '' ) {
			if( ( $_GET['type'] ?? '' ) == 'fpPreview' ) $txt .= " onclick='changePic(\"".$link."\")'";
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
		$txt .= $preflight;
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
		$commentMarks = preloadCommentMarks( $_GET['id'] );

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

		// A FlatplanStages==1 job has only the one flatplan - the fin bit a
		// submission carries (ads are conventionally sent FIN regardless of
		// stage count - see page_pdf-handler.php's $stages1 handling) isn't
		// a second stage to split this view by, so it must not be used to
		// filter $fPages2/$alterP here either: every page AND every ad for
		// such a job needs to show together in the one grid, not just
		// whichever fin value happens to match the resolved $fin above.
		$fpXmlInfo = collectFromXml( "../xml/".PMD.".xml", $magazine[0][3], array( "FlatplanStages", "Workflow" ) );
		$stages1 = ( (string) $fpXmlInfo["FlatplanStages"] == "1" and (string) $fpXmlInfo["Workflow"] != "Hybrid" );
		$finClause = $stages1 ? '' : ' AND fin="'.$fin.'"';
		// drawPage() needs this too (global $fpStages) - same reason
		// drawAmericanPage() already reads it: a stages1 job's rendered file
		// lives wherever it was actually stored based on each page's own fin
		// state, not wherever $pageType (derived from $_GET['opt'], which
		// never becomes "FIN" for a job with no FIN chip to select) implies.
		$fpStages = (string) $fpXmlInfo["FlatplanStages"];

		$bPalette = colorGenerate( 'blue' );
		$bPalette = colorGenerate( 'red', $bPalette );
		$bPalette = colorGenerate( 'green', $bPalette );
		$alterP = array();
		$alterSql = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!=""'.$finClause.' ORDER BY `page` ASC', 'page, type' );
		foreach( $alterSql as $alterRow ) {
			$alterP[ $alterRow[0] ] = $alterRow[1];
			}

		$fPages2 = array();
		// A FlatplanStages==1 job (see the single-stage rule above) leaves
		// $finClause empty, so this can match BOTH a page's old/basic (fin=0)
		// row and its final (fin=1) resubmission at once. Without an explicit
		// order, whichever row MySQL happens to return last for a given page
		// wins the overwrite below - non-deterministic, and observed picking
		// the stale basic row over the real final one for an otherwise fully
		// finished job (QVN78, reported 2026-09-05). Order fin ascending so
		// the final row - if one exists - is always the one left standing.
		$fPagesSql = sql_get( 'pageinfo', $typeSelect.' AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state=""'.$finClause.' ORDER BY fin ASC', '*' );
		foreach( $fPagesSql as $fP ) {
			$fPages2[ intval($fP[5]) ] = $fP;
			}
		
		//error_log( print_r( $fPages2, true ) );
		
		if( $_GET["pageNumbering"] == "American" ) {
			$_SESSION["fp_part"] = $_GET["part"];

			// A job with FlatplanStages==1 has only one flatplan - the fin
			// bit some of its pages carry (e.g. ads are conventionally
			// submitted FIN while editorial pages arrive NOR - see
			// page_pdf-handler.php's $stages1 handling) isn't a second
			// stage to filter this view by, so every page in the part is
			// shown together regardless of fin.
			$fpStages = collectFromXml( "../xml/".PMD.".xml", $magazine[0][3], "FlatplanStages", $returnnode = '' );
			$fpStages = $fpStages["FlatplanStages"];

			$moreQuery = "";
			if( $fpStages != "1" ) {
				if( $_GET["opt"] == "FIN" ) {
					$moreQuery .= " AND fin='1'";
					}
				else {
					$moreQuery .= " AND fin='0'";
					}
				}
			// Same fin=0/fin=1 ambiguity as the European branch above when
			// $fpStages=="1" leaves $moreQuery empty (both a page's basic and
			// final rows can match) - order fin ascending too so a real final
			// resubmission always wins the per-page overwrite below, not
			// whichever row the DB happens to return last.
			$pages = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$issue[0][10]."' AND part='".$_GET["part"]."' ".$moreQuery." ORDER BY page ASC, fin ASC", "*" );
			$fPages2 = array();
			foreach( $pages as $fP ) {
				$fPages2[ intval($fP[5]) ] = $fP;
				}
			// The part's slot range spans every page that exists for it at
			// ANY stage, not just pages matching the currently selected stage
			// tab ($pages above) - otherwise a slot whose page hasn't reached
			// this stage yet (e.g. an editorial cover still at BASIC while
			// neighbouring ad slots in the same part are already FIN) drops
			// out of the grid entirely instead of rendering as an empty slot,
			// and every slot after it shifts down/renumbers.
			$allPartPages = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$issue[0][10]."' AND part='".$_GET["part"]."' ORDER BY page ASC", "id,page" );
			// A brand-new Part has no pageinfo rows at all yet - indexing
			// [0]/[count-1] unconditionally threw undefined-array-key
			// warnings that (display_errors is on) got printed straight
			// into this endpoint's JSON response, corrupting it before the
			// existing "no pages yet" branch below ever got a chance to
			// run, so the caller's ajax success handler never fired at all.
			$first = !empty( $allPartPages ) ? $allPartPages[0][1] : 0;
			$last = !empty( $allPartPages ) ? $allPartPages[count($allPartPages)-1][1] : 0;
			$length = $last;
			// A brand-new Part with zero uploaded pages yet still needs at
			// least one empty slot in the Flatplan grid (not the Pages/
			// fpPreview view, same distinction the European branch below
			// already makes) - it's the user's drag-and-drop target for the
			// Part's first page, not a "nothing to show" state. Bootstrapping
			// first/last to 1 reuses drawAmericanPage()'s existing
			// empty-slot rendering unchanged, rather than duplicating it.
			$bootstrapEmptyPart = ( count( $allPartPages ) == 0 && ( $_GET["type"] ?? "" ) != "fpPreview" );
			if( $bootstrapEmptyPart ) {
				$first = 1;
				$last = 1;
				$length = 1;
				}
			$ptype = $_GET["opt"];
			if( empty( $ptype ) ) {
				$ptype = "normal";
				}

			//error_log( "code='".$magazine[0][3]."' AND issue='".$issue[0][10]."' AND part='".$_GET["part"]."' ".$moreQuery." ORDER BY page ASC LIMIT 2" );
			$sizes = sql_get( 'pageinfo', "code='".$magazine[0][3]."' AND state='' AND issue='".$issue[0][10]."' AND part='".$_GET["part"]."' ".$moreQuery." LIMIT 2", '*' );
			// Same empty-Part case as $allPartPages above - calculateSize()
			// indexes into whatever row it's given without checking it's
			// non-empty, so skip the call entirely rather than feed it a
			// row that doesn't exist; its own fallback size (81x97) is used
			// directly instead.
			$sizes = !empty( $sizes ) ? calculateSize( $sizes[0], $magazine[0][3], $issue[0][10] ) : array( 81, 97 );
			$row = intval( intval($_GET['maxWidth'] )/229 );
			$divWidth = $row*229;
				
			//$text = $_GET["opt"];
			if( count($allPartPages) > 0 || $bootstrapEmptyPart ) {
				if( $_GET["part"] == "MELL" or $_GET["part"] == "BEL" ) {
					$counter = 1;
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
					if( count( $allPartPages ) < 24 ) {
						$sizes[0] = $sizes[0]*3.5;
						$sizes[1] = $sizes[1]*3.5;
						}
					
					if( ( $_GET["type"] ?? "" ) == "fpPreview" ) {
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
				if( ( $_GET["type"] ?? "" ) == "fpPreview" ) {
					$nopages = 1;
					}
				else {
					$text = "<div class='flatplan_nopage'>".$lang["flatplan"]["nopage"]."</div>";
					}
				}
			}
		else {
			$sizes = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND width="1" AND issue="'.$issue[0][10].'" AND state="" AND fin="'.$fin.'" ORDER BY page ASC LIMIT 2', '*' );
			// A brand-new issue has no pageinfo rows at all yet - same
			// empty-result case as $allPartPages in the American branch
			// above, fixed the same way: calculateSize() indexes into
			// whatever row it's given with no existence check, so
			// $sizes[1] being undefined threw a cascade of PHP warnings
			// that (display_errors is on) got printed straight into this
			// endpoint's JSON response, corrupting it before the
			// box-drawing loop below ever got a chance to run - the
			// caller's ajax success handler (dataType:'json') never fired,
			// so the Flatplan looked empty even with a real page count.
			$sizes = !empty( $sizes[1] ) ? calculateSize( $sizes[1], $magazine[0][3], $issue[0][10] ) : array( 81, 97 );
			$row = intval( intval($_GET['maxWidth'] )/229 );
			$divWidth = $row*229;
						
			if( $_GET['opt'] == "PLAN" ) {
				$maxcsempe = floor( intval($_GET['maxWidth'] )/175.5 );		
				
				$currentArticle = "";
				$colors = sql_aget( 'article_colors', '1', '*' );
				$articleCounter = 1;

				$length = getPartsMaxPage( $issue[0][0] );
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
				$length = getPartsMaxPage( $issue[0][0] );
				if( $length == 0 ) {
					// Adhoc jobs never get a fixed page count - by design their
					// Parts are defined up front but their place ranges are
					// left open-ended and the page count is meant to grow
					// freely as pages are uploaded (unlike Regular jobs, where
					// every Part's place range is fixed at creation, so
					// getPartsMaxPage() above resolves to a real number).
					// Dropping the part='' restriction here matters because
					// Adhoc jobs using American part numbering (e.g. BEL/BOR)
					// never have an empty part on their pageinfo rows, so that
					// condition excluded exactly the jobs this fallback exists
					// for.
					$moreQuery = "";
					if( $_GET["opt"] == "FIN" ) {
						$moreQuery .= " AND fin='1'";
						}
					else {
						$moreQuery .= " AND fin='0'";
						}
					$pagesRow = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$issue[0][10]."' ".$moreQuery." ORDER BY page DESC LIMIT 1", "*" );
					$length = $pagesRow[0][5];
					}

				// drawPage() reads this (global $pages) to tell a genuinely
				// out-of-range page (rendered as an invisible spacer) from one
				// that's simply missing its own pageinfo row because a
				// neighbouring wide/gatefold image already covers it (which
				// still needs its normal slot drawn). Left unset here before,
				// so drawPage()'s "$page > $pages" check compared every real
				// page number against null - always true - misclassifying
				// every such gap page as out-of-range. That emitted a
				// wrongly-doubled-width invisible spacer instead of a normal
				// slot for each one, throwing off the row-wrapping float math
				// for everything after it - reproduced live 2026-09-05
				// against QVN78 (pages 218-220, covered by page 217's wide
				// cover image, breaking the layout for 221 onward).
				$pages = $length;

				// A freshly-created issue's Parts already give $length a
				// real (nonzero) page count long before any page is
				// actually uploaded - the Pages view (type=fpPreview)
				// gains nothing from rendering a slot per planned page in
				// that case, and drawPage() runs a packages query plus
				// several is_file()/getimagesize() filesystem checks per
				// slot, none of which can ever hit for a page that was
				// never uploaded - same fix as the American-numbering
				// branch above already has via $allPartPages, just using
				// an existence check instead (this branch isn't
				// part-scoped). This must NOT touch the Flatplan view
				// (same branch, but no type=fpPreview): its empty slots
				// are cheap and are exactly what the user drags PDFs onto
				// to upload, so an empty grid there is not itself a
				// "nothing to show" state.
				if( ( $_GET["type"] ?? "" ) == "fpPreview" ) {
					$hasAnyPages = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" LIMIT 1', 'id' );
					if( empty( $hasAnyPages[0][0] ) ) {
						$nopages = 1;
						$length = -1;
						}
					}

				$counter = 1;
				$i = 0;

				if( ( $_GET["type"] ?? "" ) == "fpPreview" ) {
				//if( 0 ) {
					while( $i <= $length ) {
						if( $counter == 5 ) $counter = 1;
						
						$leftPage = sql_aget( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND page="'.$i.'" AND issue="'.$issue[0][10].'" AND state="" AND fin="'.$fin.'" ORDER BY page ASC LIMIT 1', '*' );
						$rightPage = sql_aget( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND page="'.($i+1).'" AND issue="'.$issue[0][10].'" AND state="" AND fin="'.$fin.'" ORDER BY page ASC LIMIT 1', '*' );
						
						if( ( $leftPage[0]["width"] ?? 0 ) > 1 ) {
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
						
						elseif( ( $rightPage[0]["width"] ?? 0 ) > 1 ) {
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
	   		// Unlike "normal" mode's file[0]/file[1] (plain relative paths,
	   		// e.g. "packages/.../foo.pdf"), the file sent in compare mode
	   		// comes from compare.php's op=loadbg response, which already
	   		// builds Name as an absolute path (TRKPATH prefixed - see
	   		// compare.php's loadbg handler). Prepending $terminalPath
	   		// again produced a nonexistent doubled-up path
	   		// ("/var/www/html/client//var/www/html/client/packages/...");
	   		// r3 silently failed to open it, so colorPick() got no
	   		// measurable output at all - the color sampler looked like it
	   		// did nothing on either compare image, for every click.
	   		$file = $_POST['file']['Name'];
	    	}

    	$info = colorPick( $file, $realX, $realy );
    	// $_POST['file'] is a single flat object in compare mode (not the
    	// two-element array "normal" mode uses), so ['file'][0] doesn't
    	// exist there - this debug string isn't used for anything beyond
    	// logging, but keeping it mode-aware avoids a spurious "Undefined
    	// array key 0" warning on every compare-mode pick.
    	$debug = $realX." ".( $_GET["mode"] == "compare" ? $_POST['file']['Right'] : $_POST['file'][0]['Right'] );
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
				// A 1-stage job's fpver reflects whatever stage tab the
				// view currently happens to be resolved to (e.g. "FIN"),
				// which is meaningless for these jobs - approvals must be
				// allowed regardless of fpver, not just when it's "".
				// Without this, this endpoint's 600ms poll
				// (refreshPageStatus() in flatplan_preview.php) wipes out
				// the correctly-rendered accept/decline buttons within a
				// second of page load whenever fpver isn't exactly "".
				if( $fpstages == 1 && $fpworkflow != "Hybrid" ) $allowed = true;
				if( $_GET['fpver'] == "" && $fpstages == 1 ) $allowed = true;
				// Hybrid: the single flatplan is FINAL regardless of the
				// stored stage count (which is hidden/meaningless for
				// Hybrid), so FIN-view approvals are always allowed.
				if( $_GET['fpver'] == "FIN" && ( $fpstages == 2 or $fpstages == 3 or $fpworkflow == "Hybrid" ) ) $allowed = true;

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
											
											
											$ttext[$i] .= "<div id='".$pageID."_dec' style='cursor: pointer; float: left; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div>
													<div id='".$pageID."_dec_hover' style='display: none; position: absolute; left: 0px; cursor: pointer; float: left; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline_hover.png'></div>
					
													<div id='".$pageID."_acc' style='position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>
													<div id='".$pageID."_acc_hover' style='display: none; position: absolute; right: 0px; cursor: pointer; float: right; margin-top: 4px;'><img class='approveButton' onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept_hover.png'></div>";
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

		// Adhoc jobs have publications.code == magazines.code (there's no
		// separate issue), so their checked-ad files are named
		// NAME_CODE_TYPE, not NAME_CODE_CODE_TYPE - same distinction already
		// applied in engine/xml_handler.php and client/engine/ajax.php's
		// load_adverts, just missing here. Without it, is_file() below never
		// finds an Adhoc ad's real PDF, so $file[0] never gets populated and
		// the high-res preview's render spinner never resolves.
		$issueSegment = ( $magazine[0][3] == $pub[0][10] ) ? '' : '_'.$pub[0][10];
		$file_name = strtoupper( $preview[0][2] ).'_'.$magazine[0][3].$issueSegment.'_'.$type;
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
		$box = boxOrPlaceholder( $box, $path );
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
	    // Adhoc jobs carry publisher_id="0" by convention - fall back to
	    // publications.owner, same as resolveJobPublisherName() and every
	    // other Switch-facing call site in this codebase. Without this,
	    // the "client" sent to Switch was empty for every Adhoc handout.
	    $publisherId = $pub[0]["publisher_id"];
	    if( empty( $publisherId ) || $publisherId == '0' ) {
		    $publisherId = $pub[0]["owner"];
		    }
	    $client = sql_get( 'publishers', 'id="'.$publisherId.'"', '*' );

		$pages = $pub[0]["pages"];
		if( $pages == 0 ) {
			// $pub was fetched via sql_aget() - purely associative, no
			// numeric keys - so $pub[0][10] below (a leftover from
			// sql_get()-style numeric access elsewhere) always evaluated to
			// "", making both fallback queries run as issue='' and match
			// nothing. That alone was enough to send Switch a "0" page
			// count regardless of pagination scheme.
			$pageNumbering = collectFromXml( "../xml/".PMD.".xml", $magazine[0][3], "PageNumbering", $returnnode = '' );
			$pageNumbering = (string) $pageNumbering["PageNumbering"];

			if( $pageNumbering == "American" ) {
				// American jobs number pages per-Part (Cover, Inside, etc -
				// each its own 1..N sequence), not as one flat range across
				// the whole issue - the handout's page count is the SUM of
				// every Part's own page count, not a single pageinfo
				// row-count query. By the time the Generate Handout icon is
				// even clickable, loadhandoutmenu's $haveall check has
				// already confirmed every Part with pages is a gap-free
				// sequence from its own page 1, so that Part's highest page
				// number IS its page count. Confirmed live 2026-07-30
				// (BQW39: Cover=4 + Inside=16 = 20).
				$pages = 0;
				$parts = sql_aget( 'parts', "pub_id='".$pub[0]['id']."'", "*" );
				foreach( $parts as $partRow ) {
					$partPages = sql_aget( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pub[0]['code']."' AND state='' AND part='".$partRow["name"]."' order by page ASC", "*" );
					if( count( $partPages ) > 0 ) {
						$pages += intval( $partPages[ count($partPages)-1 ]["page"] );
						}
					}
				}
			else {
				$pages = sql_aget( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pub[0]['code']."' AND state='' AND fin='1'", "*" );
				$pages = count( $pages );

				if( $pages == 0 ) {
					$pages = sql_aget( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pub[0]['code']."' AND state=''", "*" );
					$pages = count( $pages );
					}
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

		// Same Adhoc-vs-Regular single/double segment distinction fixed
		// elsewhere this session (ajax.php's load_adverts, __cleaner__.php,
		// cleanupPublicationRemnants()) - Adhoc jobs have publications.code
		// == magazines.code (there's no separate issue), so Switch names
		// the returned handout file NAME_handout.pdf, not
		// NAME_NAME_handout.pdf. Without this, the stored filename never
		// matched the file handout-handler.php's cron was looking for, so
		// `arrived` never got set and the UI kept polling/spinning forever
		// even after the file genuinely arrived. Confirmed live 2026-07-30
		// (BQW39: file arrived as BQW39_handout.pdf, DB expected
		// BQW39_BQW39_handout.pdf).
		$issueSegment = ( $magazine[0][3] == $pub[0]["code"] ) ? '' : '_'.$pub[0]["code"];
		$names = array( "userid", "pub_id", "filename", "date", "changed" );
		$values = array( $_SESSION["intra_user"], $pub[0]["id"], $magazine[0][3].$issueSegment."_handout.pdf", time(), "0" );
		sql_add( "flatplan_handout", $names, $values );
		
		$result = $response;    
	    }    
	
	if( $_GET['op'] == 'loadhandoutmenu' ) {
		$txt = "";
		// $txt2/$loading/$keepPolling used to only ever get assigned inside
		// the $rights/$haveall branches below, leaving them undefined-
		// variable warnings (silently null in the JSON response) whenever
		// rights were missing or not every page was finished yet. Default
		// all three here instead - $keepPolling in particular is read by the
		// client to decide whether to schedule another poll at all, so it
		// needs a real, always-set boolean, not "false because PHP coerced
		// null".
		$txt2 = "";
		$loading = false;
		$keepPolling = false;
		if( $rights["handouts"] ) {
			//echo "HANDOUT TEST";
			$txt .= "<div style='float: left; margin-left: 5px; margin-top: 4px;'>";

			$handout = sql_aget( "flatplan_handout", "pub_id='".$_GET["id"]."' ORDER BY id DESC LIMIT 1", "*" );

			$pub = sql_get( 'publications', 'id="'.$_GET["id"].'" ORDER BY `code` ASC', '*' );
			$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
			$haveall = true;
			$allpage = $pub[0][6];
			if( $allpage == 0 ) {
				$checker = sql_get( 'pageinfo', 'issue="'.$pub[0][10].'" AND code="'.$magazine[0][3].'" order by page DESC LIMIT 1', '*' );
				$allpage = intval( $checker[0][5] );
				}

			$fpstages = collectFromXml( "../xml/".PMD.".xml", $magazine[0][3], array( "FlatplanStages", "Workflow", "PageNumbering" ), $returnnode = '' );
			$fpworkflow = (string) $fpstages["Workflow"];
			$pageNumbering = (string) $fpstages["PageNumbering"];
			$fpstages = $fpstages["FlatplanStages"];
			// A FlatplanStages==1 job has only the one flatplan, so its real
			// content pages routinely stay fin=0 forever (there's no later
			// stage to promote them to), while ads on the very same job are
			// conventionally submitted fin=1 regardless of stage count (see
			// page_pdf-handler.php's $stages1 handling) - a completely normal
			// mix, not a sign anything is unfinished. The fin=1-else-fin=0
			// fallback below assumes a job's pages all share one fin value,
			// so as soon as the job has ANY fin=1 row (just one ad is
			// enough), it locks onto the fin=1 bucket alone and never sees
			// the fin=0 pages at all - "not all slots" forever, even once
			// every slot genuinely has content. For a 1-stage job, drop the
			// fin filter entirely and match on the mix directly instead.
			$stages1 = ( (string) $fpstages == "1" and $fpworkflow != "Hybrid" );

			if( $pageNumbering == "American" ) {
				// American jobs number pages per-Part (each Part - Cover,
				// Interior, etc - is its own independent 1..N sequence), not
				// as one global range across the whole issue like European
				// jobs. The single-sequence check below, run unscoped by
				// part, mixes every Part's page numbers into one query -
				// meaningless for this numbering scheme, and vacuously
				// "complete" (0 of 0 expected pages) for a brand-new job
				// with zero pageinfo rows anywhere, which is exactly what
				// showed the handout icon for a publication with nothing in
				// it yet. Require instead: at least one Part actually has
				// pages, and every Part that does is itself a gap-free
				// sequence from its own page 1. Confirmed live 2026-07-29
				// (BQW39, a brand-new empty Adhoc/American publication).
				$haveall = false;
				$anyPartHasPages = false;
				$allPartsContinuous = true;
				$parts = sql_aget( 'parts', "pub_id='".$pub[0][0]."'", "*" );
				foreach( $parts as $partRow ) {
					$partName = $partRow["name"];
					if( $stages1 ) {
						$partPages = sql_aget( "pageinfo", "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND part='".$partName."' order by page ASC ", "*" );
						}
					else {
						$partPages = sql_aget( "pageinfo", "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND part='".$partName."' AND fin='1' order by page ASC ", "*" );
						if( count( $partPages ) == 0 ) {
							$partPages = sql_aget( "pageinfo", "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND part='".$partName."' AND fin='0' order by page ASC ", "*" );
							}
						}

					if( count( $partPages ) == 0 ) {
						continue;
						}
					$anyPartHasPages = true;

					$partMax = intval( $partPages[ count($partPages)-1 ]["page"] );
					$expected = 1;
					$idx = 0;
					while( $expected <= $partMax ) {
						if( empty( $partPages[ $idx ]["id"] ) || intval( $partPages[ $idx ]["page"] ) != $expected ) {
							$allPartsContinuous = false;
							break 2;
							}
						$expected += max( 1, intval( $partPages[ $idx ]["width"] ) );
						$idx++;
						}
					}
				$haveall = ( $anyPartHasPages && $allPartsContinuous );
				}
			else {
				if( $stages1 ) {
					$pages = sql_aget( "pageinfo", "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND page <= ".$allpage." order by page ASC ", "*" );
					}
				else {
					$pages = sql_aget( "pageinfo", "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND page <= ".$allpage." AND fin='1' order by page ASC ", "*" );
					//error_log( "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND page <= ".$allpage." AND fin='1' order by page ASC " );
					//error_log( "PAGES IN SQL: ".count($pages)."" );
					if( count( $pages ) == 0 ) {
						$pages = sql_aget( "pageinfo", "issue='".$pub[0][10]."' AND code='".$magazine[0][3]."' AND type != 'PRE' AND state='' AND page <= ".$allpage." AND fin='0' order by page ASC ", "*" );
						}
					}

				// Walk $pages in row order, tracking the page NUMBER we expect
				// next, instead of trusting array position to line up with it.
				// $pages has no row at all for a genuinely missing page, and no
				// separate row for a spread's second half - both shrink the
				// array without moving $i, so position and real page number
				// silently drift apart after either one. Checking array-slot
				// emptiness alone can't tell "page really missing" from "array
				// just got shorter than $allpage for a legitimate reason", and
				// two such drifts (e.g. a gap plus a spread) can cancel out and
				// falsely read as complete.
				$expected = 1;
				$idx = 0;
				while( $expected <= $allpage ) {
					if( empty( $pages[ $idx ]["id"] ) || intval( $pages[ $idx ]["page"] ) != $expected ) {
						$haveall = false;
						break;
						}

					$expected += max( 1, intval( $pages[ $idx ]["width"] ) );
					$idx++;
					}
				}

			if( !$haveall ) {
				// Not every page is finished yet, so the book icon isn't
				// shown at all - but that can still change as soon as the
				// remaining pages finish elsewhere in the app, with nobody
				// touching this menu. Keep watching for that.
				$keepPolling = true;
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
				
				
				if( $handout[0]["arrived"] == "0" ) {
					$loading = true;
					// Actively waiting on this specific handout: Switch has
					// to render it, then the handout-handler cron job (runs
					// once a minute - see client/cron/handout-handler.php)
					// has to notice the file landed on disk. Keep polling
					// until it does.
					$keepPolling = true;
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
			
		$result = array( $txt, $txt2, $loading, $keepPolling );
		}

print json_encode( $result );
	
?>