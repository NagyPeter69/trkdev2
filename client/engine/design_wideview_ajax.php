<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');
include_once( 'switchAPI.php' );
include_once('../lang/en.php');

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

function calculateSize( $pageInfo, $magazine, $issue ) {
	$dir = sql_get( 'packages', 'id="'.$pageInfo[1].'"', 'name, directory, id' );
	$file = $dir[0][0]."/".str_pad( $pageInfo[5], 3, '0', STR_PAD_LEFT)."_".$dir[0][2]."_preview.jpg";
	$path = "../packages/".$magazine."/".$issue;
	$w = 81;
	$h = 97;
	if( $pageInfo[0] != "" && is_file( $path."/".$file ) ) {
		list( $w2, $h2 ) = getimagesize( $path."/".$file );
		if( $w2 >= 81 ) {
			$percent = $w/$w2*100;
			$h = intval( $h2/100*$percent );
			}
		}
	return array( $w, $h );
	}

function drawPlaceboardPage( $id, $page, $class, $i ) {
	global $currentArticle, $colors, $articleCounter, $counter, $maxcsempe, $currentcsempe, $plans, $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $fPage, $sizes, $path, $fin, $imghash, $issue;
	
	list( $w, $h ) = $sizes;
	
	$check = sql_aget( "flatplan_planner", "id='".$i."'", "*" );
	
	// MIXED CSEMPE GENERÁLÁS
	if( $check[0]["mixed"] == "1" ) {
		$parts = sql_aget( "flatplan_planner", "pub_id='".$_GET['id']."' AND pos = '0' AND template='".$check[0]["template"]."' ORDER BY position ASC", "*" );;
		$mpages = array();	
		
		for( $p = 0; $p < count( $parts ); $p++ ) {		
			// Színek beállítása
			$color = "";
			if( $parts[$p]["type"] == "ad" or $parts[$p]["type"] == "promo" ) {
				$color = "fee5cc";
				}
			else {
				$atype = sql_aget( "flatplan_articletypes", "id='".$parts[$p]["atype"]."'", "*" );
				$color = $atype[0]["color"];
				}
			$mpages[$p]["color"] = $color;
			
			//  Pöttyök beállítása
			$dot = "";
			if( $parts[$p]["text"] == "1" ) {
				$dot .= "<div class='dot_text'>".( $parts[$p]["have_text"] == "0" ? "<div class='dot_required'></div>" : "" )."</div>";
				}
			if( $parts[$p]["image"] == "1" ) {
				$dot .= "<div class='dot_image'>".( $parts[$p]["have_image"] == "0" ? "<div class='dot_required'></div>" : "" )."</div>";
				}
			if( $parts[$p]["other"] == "1" ) {
				$dot .= "<div class='dot_other'>".( $parts[$p]["have_other"] == "0" ? "<div class='dot_required'></div>" : "" )."</div>";
				}	
			$mpages[$p]["dots"] = $dot;		

			// Ember ikon beállítás
			if( $parts[$p]["type"] != "ad" ) {
				$mpages[$p]["worker"] = "<div onclick='settingsPanel(\"flatplan_worker\", undefined, \"".$parts[$p]["id"]."\" )' data-id='".$parts[$p]["id"]."' class='fp-user-icon'>
							<i class='fp-icons fas fa-user' style='".( !empty( $color ) ? "color: ".$color : "" )."'></i>
						</div>";		
				}				
			
			$mpages[$p]["debug"] = "";
			}


		
		$txt .= '<div id="'.$page.'_selector" sub="pasteboard" class="select'.ucfirst($class).'" style="opacity: 0; width:81px; border-right: 6px solid rgb( 75, 0, 183 ); height: 97px; left: '.$check[0]["x"].'px; top: '.$check[0]["y"].'px;"></div>';
		$txt .= '<div class="'.$class.'_page '.$extra_class.''.$articleClass.' board-thumb-box" acolor="'.fontcolor( ( $check[0]["type"] == "article" ? $csempecolor : "rgb( 254, 229, 204 )" ) ).'" a-name="'.$articleName_.'" mixed="yes" aname="'.$articleName.'" style="position: absolute; left: '.$check[0]["x"].'px; top: '.$check[0]["y"].'px; width: 100%; height: 100%; z-index: 0; z-index: 0; border-right: 1px solid #ADADAD;">';
			
			$txt .= '<div class="pageBox '.$page.'_box" style=" position: relative; width: 81px; height: 114px;">';
				$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
				$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
				/*$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="'.$class.'_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="z-index: 10; width: 81px;  color: #; background-color:'.$hc.';">';
					$txt .= '<div style="pointer-events: none; float:'.$class.'; margin-'.$class.': 4px;">'.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
				$txt .= '</div>';*/
				$txt .= '<div id="'.$page.'_thumb" state="" class="board-thumb '.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb '.$w.'" alter="0" page="'.$page.'" style="position: relative; z-index: 10; top: 0px; width: 100%; height: 100%; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
					include( '/var/www/intra/client/images/mixed_thumb/'.$check[0]["template"].'.php' );
				$txt .= '</div>';
			$txt .= '</div>';
			$txt .="<input type='checkbox' pageid='".$check[0]["id"]."' item='".$id."' sub='pasteboard' state='' ptype='mixed' name='pageSelector[]' value='".$page."' style='display: none;'>";			
		$txt .= '</div>';
		}
		
	// MEZEI CSEMPE GENERÁLÁS	
	else {		
		$prev = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page - 1 )."' LIMIT 1", "*" );	
		if( $prev[0]["type"] == "ad" ) {
			$prev = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page - 1 )."' LIMIT 1", "*" );	
			}
			
		$next = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page + 1 )."' LIMIT 1", "*" );
		if( $next[0]["type"] == "ad" ) {
			$next = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page + 2 )."' LIMIT 1", "*" );
			}
		
		$u = sql_aget( "accounts", "id='".$check[0]["workerID"]."'", "*" );	
		$ucolor = $u[0]["color"];
		$csempecolor = "";
		
		if( ( $check[0]["type"] != "ad" ) ) {
			$atype = sql_aget( "flatplan_articletypes", "id='".$check[0]["atype"]."'", "*" );
			
			$csempecolor = "#".$atype[0]["color"];
			}

		$articleClass = "";
		$extra_class = " ";

		if( !empty( $check[0]["id"] ) ) {
			$extra_class .= $check[0]["type"]." ";
			}

		$articleName = "<span class='articleNameBG'>".$check[0]["name"]."</span>";
		$articleName_ = $check[0]["name"];
		
		$hfc = "";
		if( $check[0]["workerName"] != "" ) {
			$w = "haveWorker";
			$hc = "#C2C2C2";
			$hfc = "413e3e";
			}
		else {
			$w = "noWorker";
			$hc = "";
			}

		if( ( $check[0]["type"] != "ad" ) ) {
			if( $counter == 1 && !empty( $check[0]["id"] ) && $class == "left" ) {
				$articleClass .= " articleStart ";
				}
				
			elseif( $maxcsempe == $counter && !empty( $check[0]["id"] ) && $class == "right" ) {
				$articleClass .= " articleEnd ";
				}

			if( empty( $prev ) && !empty( $check[0]["id"] ) ) {
				$prev_ = sql_aget( "flatplan_planner", "pub_id='".$id."' AND pos = '".( $page - 1 )."' LIMIT 1", "*" );
				if( $prev_[0]["type"] == "ad" ) {
					$prev_ = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page - 2 )."' LIMIT 1", "*" );
					if( empty( $prev_[0]["id"] ) ) {
						$articleClass .= "startArrow articleStart ";
						}
					}
				else {
					$articleClass .= "startArrow articleStart ";
					}
				}
			
			if( empty( $next ) && !empty( $check[0]["id"] ) ) {
				$next_ = sql_aget( "flatplan_planner", "pub_id='".$id."' AND pos = '".( $page + 1 )."' LIMIT 1", "*" );
				$debug = $next_[0]["type"];
				if( $next_[0]["type"] == "ad" ) {
					$next_ = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page + 2 )."' LIMIT 1", "*" );
					
					if( empty( $next_[0]["id"] ) ) {
						
						$articleClass .= "endArrow articleEnd";
						}
					}
				else {
					
					$articleClass .= "endArrow articleEnd";
					}
				}
			}	
		
		if( empty( $prev ) && empty( $next ) && !empty( $check[0]["id"] ) && ( $check[0]["type"] != "ad" ) ) {
			if( empty( $prev_ ) && empty( $next_ ) ) {
				$articleClass .= " startArrow articleStart endArrow articleEnd";
				}
			}
								
		$txt .= '<div id="'.$page.'_selector" sub="pasteboard" class="select'.ucfirst($class).'" style="opacity: 0; width:81px; border-right: 6px solid rgb( 75, 0, 183 );  height: 97px; left: '.$check[0]["x"].'px; top: '.$check[0]["y"].'px;"></div>';
		$txt .= '<div';
			$txt .= ' class="ui-draggable ui-draggable-handle '.$class.'_page '.$extra_class.''.$articleClass.' board-thumb-box"';
			$txt .= ' '.( ( !empty( $check[0]["id"] ) && $check[0]["type"] != "ad" ) ? 'aid="'.$articleCounter.'"' : "" ).'';
			$txt .= ' acolor="'.fontcolor( ( $check[0]["type"] == "article" ? $csempecolor : "rgb( 254, 229, 204 )" ) ).'"';
			$txt .= ' a-name="'.$articleName_.'"';
			$txt .= ' aname="'.$articleName.'"';
			$txt .= ' mixed="no"';
			$txt .= ' sql-id="'.$check[0]["id"].'"';
			$txt .= ' a-status="'.$check[0]["status"].'"';
			$txt .= ' sqlid="'.$check[0]["id"].'"';
			$txt .= ' user-color="'.$ucolor.'"';
			
			if( $check[0]["text"] == "1" ) {
				$txt .= ' have-text="'.( $check[0]["have_text"] == "1" ? "true" : "false" ).'"';
				}

			if( $check[0]["image"] == "1" ) {
				$txt .= ' have-image="'.( $check[0]["have_image"] == "1" ? "true" : "false" ).'"';
				}
				
			if( $check[0]["other"] == "1" ) {
				$txt .= ' have-other="'.( $check[0]["have_other"] == "1" ? "true" : "false" ).'"';
				}				
			
			$txt .= ' style="position: absolute; left: '.$check[0]["x"].'px; top: '.$check[0]["y"].'px; width: 100%; height: 100%; z-index: 0;"';
		$txt .= '>';

			$txt .= '<div class="pageBox '.$page.'_box" style="'.( $page == "1" ? "border-left: 1px solid #ADADAD;" : "" ).' width: 100%; height: 100%;">';
				$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
				$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
				/*$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="'.$class.'_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="position: inherit !important; z-index: 10; width: 81px;  color: #'.$hfc.'; background-color:'.$hc.';">';
					$txt .= '<div style="pointer-events: none; float:'.$class.'; margin-'.$class.': 4px;"> '.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
				$txt .= '</div>';*/
				$txt .= '<div id="'.$page.'_thumb" state="" class="board-thumb '.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb '.$w.'" alter="0" page="'.$page.'" style="z-index: 10; width: 100%; height: 100%; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
					$txt .= "<table cellspacing='0' cellpadding='0' style='width: 100%; height: 100%; pointer-events: none;'>";
						$txt .= "<tr><td align='center' valign='middle' style='font-size: 11px;'>";
							$txt .= ( !empty( $check[0]["name"] ) ? $check[0]["name"] : "<i>Unnamed</i>" );
						$txt .= "</td></tr>";
					$txt .= "</table>";
					$txt .= arrowChecker( $id, $page );
				$txt .= '</div>';
			$txt .= '</div>';
			$txt .="<input type='checkbox' pageid='".$check[0]["id"]."' item='".$id."' state='' sub='pasteboard' ptype='simple' name='pageSelector[]' value='".$page."' style='display: none;'>";
		$txt .= '</div>';
		
		if( strpos( $articleClass, "articleEnd" ) !== false ) {
			$articleCounter++;
			}
		}
	
	return $txt;
	}

function drawPlannerPage( $id, $page, $class, $i ) {
	global $currentArticle, $colors, $articleCounter, $counter, $maxcsempe, $currentcsempe, $plans, $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $fPage, $sizes, $path, $fin, $imghash, $issue, $boxwidth, $boxheight;
	
	list( $w, $h ) = $sizes;
	
	if( $page == 0 ) {
		return '<div class="" style="float: left; width: '.$boxwidth.'px; height: '.$boxheight.'px; z-index: 0;"></div>';
		}

	if( $page > intval( $issue[0][6] ) ) {
		return '<div class="" style="float: left; width: '.$boxwidth.'px; height: '.$boxheight.'px; z-index: 0;"></div>';
		}

	$check = sql_aget( "flatplan_planner", "pub_id='".$_GET['id']."' AND pos = '".$page."' LIMIT 1", "*" );
	
	// MIXED CSEMPE GENERÁLÁS
	if( $check[0]["mixed"] == "1" ) {
		$parts = sql_aget( "flatplan_planner", "pub_id='".$_GET['id']."' AND pos = '".$page."' AND template='".$check[0]["template"]."' ORDER BY position ASC", "*" );
		$mpages = array();	
		
		for( $p = 0; $p < count( $parts ); $p++ ) {		
			$mpages[$p]["type"] = $parts[$p]["type"];
			$mpages[$p]["id"] = $parts[$p]["id"];
			
			// Színek beállítása
			$color = "";
			if( $parts[$p]["type"] == "ad" or $parts[$p]["type"] == "promo" ) {
				$color = "fee5cc";
				}
			else {
				$atype = sql_aget( "flatplan_articletypes", "id='".$parts[$p]["atype"]."'", "*" );
				$color = $atype[0]["color"];
				}
			$mpages[$p]["color"] = $color;
			
			//  Pöttyök beállítása
			$dot = "";
			if( $parts[$p]["text"] == "1" ) {
				$dot .= "<div class='dot_text'>".( $parts[$p]["have_text"] == "0" ? "<div class='dot_required'></div>" : "" )."</div>";
				}
			if( $parts[$p]["image"] == "1" ) {
				$dot .= "<div class='dot_image'>".( $parts[$p]["have_image"] == "0" ? "<div class='dot_required'></div>" : "" )."</div>";
				}
			if( $parts[$p]["other"] == "1" ) {
				$dot .= "<div class='dot_other'>".( $parts[$p]["have_other"] == "0" ? "<div class='dot_required'></div>" : "" )."</div>";
				}	
			$mpages[$p]["dots"] = $dot;		

			// Ember ikon beállítás
			if( $parts[$p]["type"] != "ad" ) {
				$mpages[$p]["worker"] = "<div onclick='settingsPanel(\"flatplan_worker\", undefined, \"".$parts[$p]["id"]."\" )' data-id='".$parts[$p]["id"]."' class='fp-user-icon'>
							<i class='fp-icons fas fa-user' style='".( !empty( $color ) ? "color: ".$color : "" )."'></i>
						</div>";		
				}				
			
			$mpages[$p]["debug"] = "";
			}
			
		$article = sql_aget( "flatplan_planner", "pub_id='".$_GET['id']."' AND pos = '".$page."' AND type='article' AND template='".$check[0]["template"]."' ORDER BY position ASC", "*" );
		if( empty( $check[0]["name"] ) ) {
			$check[0]["name"] = "<i>Unnamed</i>";
			}
		
		$articleName = "<span class='articleNameBG'>".$check[0]["name"]."</span>";
		$articleName_ = $check[0]["name"];
		
		$txt .= '<div id="'.$page.'_selector" class="select'.ucfirst($class).'" style="opacity: 0; width:82px; height: 114px;"></div>';
		$txt .= '<div class="'.$class.'_page '.$extra_class.''.$articleClass.'" acolor="'.fontcolor( ( $check[0]["type"] == "article" ? $csempecolor : "rgb( 254, 229, 204 )" ) ).'" a-name="'.$articleName_.'" mixed="yes" aname="'.$articleName.'" style="position: relative; float: left; '.$class.': 0px; z-index: 0; border-right: 1px solid #ADADAD;" sql-id="'.$check[0]["id"].'" sqlid="'.$check[0]["id"].'" a-status="'.$check[0]["status"].'">';
			
			$txt .= '<div class="pageBox '.$page.'_box" style=" position: relative; width: 81px; height: 114px;">';
				$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
				$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
				$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="'.$class.'_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="z-index: 10; color: #; background-color:'.$hc.';">';
					$txt .= '<div style="pointer-events: none; float:'.$class.'; margin-'.$class.': 4px;">'.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
				$txt .= '</div>';
				$txt .= '<div id="'.$page.'_thumb" state="" class="'.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb '.$w.'" alter="0" page="'.$page.'" style="position: relative; z-index: 10; top: 17px; width: 81px; height: 97px; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
					include( '/var/www/intra/client/images/mixed_thumb/'.$check[0]["template"].'.php' );
				$txt .= '</div>';
			$txt .= '</div>';
			$txt .="<input type='checkbox' pageid='".$check[0]["id"]."' item='".$id."' state='' ptype='mixed' name='pageSelector[]' value='".$page."' style='display: none;'>";			
		$txt .= '</div>';
		}
		
	// MEZEI CSEMPE GENERÁLÁS	
	else {		
		$prev = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page - 1 )."' LIMIT 1", "*" );	
		if( $prev[0]["type"] == "ad" ) {
			$prev = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page - 1 )."' LIMIT 1", "*" );	
			}
			
		$next = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page + 1 )."' LIMIT 1", "*" );
		if( $next[0]["type"] == "ad" ) {
			$next = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page + 2 )."' LIMIT 1", "*" );
			}
		
		$u = sql_aget( "accounts", "id='".$check[0]["workerID"]."'", "*" );	
		$ucolor = $u[0]["color"];
		$csempecolor = "";
		
		if( ( $check[0]["type"] != "ad" ) ) {
			$atype = sql_aget( "flatplan_articletypes", "id='".$check[0]["atype"]."'", "*" );
			
			$csempecolor = "#".$atype[0]["color"];
			}

		$articleClass = "";
		$extra_class = " ";

		if( !empty( $check[0]["id"] ) ) {
			$extra_class .= $check[0]["type"]." ";
			}
		
		if( empty( $check[0]["name"] ) ) {
			$check[0]["name"] = "<i>Unnamed</i>";
			}
		
		$articleName = "<span class='articleNameBG'>".$check[0]["name"]."</span>";
		$articleName_ = $check[0]["name"];
		
		$hfc = "";
		if( $check[0]["workerName"] != "" ) {
			$w = "haveWorker";
			$hc = "#C2C2C2";
			$hfc = "413e3e";
			}
		else {
			$w = "noWorker";
			$hc = "";
			}

		if( ( $check[0]["type"] != "ad" ) ) {
			if( $counter == 1 && !empty( $check[0]["id"] ) && $class == "left" ) {
				$articleClass .= " articleStart ";
				}
				
			elseif( $maxcsempe == $counter && !empty( $check[0]["id"] ) && $class == "right" ) {
				$articleClass .= " articleEnd ";
				}

			if( empty( $prev ) && !empty( $check[0]["id"] ) ) {
				$prev_ = sql_aget( "flatplan_planner", "pub_id='".$id."' AND pos = '".( $page - 1 )."' LIMIT 1", "*" );
				if( $prev_[0]["type"] == "ad" ) {
					$prev_ = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page - 2 )."' LIMIT 1", "*" );
					if( empty( $prev_[0]["id"] ) ) {
						$articleClass .= "startArrow articleStart ";
						}
					}
				else {
					$articleClass .= "startArrow articleStart ";
					}
				}
			
			if( empty( $next ) && !empty( $check[0]["id"] ) ) {
				$next_ = sql_aget( "flatplan_planner", "pub_id='".$id."' AND pos = '".( $page + 1 )."' LIMIT 1", "*" );
				$debug = $next_[0]["type"];
				if( $next_[0]["type"] == "ad" ) {
					$next_ = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$check[0]["name"]."' AND pos = '".( $page + 2 )."' LIMIT 1", "*" );
					
					if( empty( $next_[0]["id"] ) ) {
						
						$articleClass .= "endArrow articleEnd";
						}
					}
				else {
					
					$articleClass .= "endArrow articleEnd";
					}
				}
			}	
		
		if( empty( $prev ) && empty( $next ) && !empty( $check[0]["id"] ) && ( $check[0]["type"] != "ad" ) ) {
			if( empty( $prev_ ) && empty( $next_ ) ) {
				$articleClass .= " startArrow articleStart endArrow articleEnd";
				}
			}
								
		$txt .= '<div id="'.$page.'_selector" class="select'.ucfirst($class).'" style="opacity: 0; width:82px; height: 114px;"></div>';
		$txt .= '<div';
			$txt .= ' class="ui-draggable ui-draggable-handle '.$class.'_page '.$extra_class.''.$articleClass.'"';
			$txt .= ' '.( ( !empty( $check[0]["id"] ) && $check[0]["type"] != "ad" ) ? 'aid="'.$articleCounter.'"' : "" ).'';
			$txt .= ' acolor="'.fontcolor( ( $check[0]["type"] == "article" ? $csempecolor : "rgb( 254, 229, 204 )" ) ).'"';
			$txt .= ' a-name="'.$articleName_.'"';
			$txt .= ' aname="'.$articleName.'"';
			$txt .= ' mixed="no"';
			$txt .= ' sql-id="'.$check[0]["id"].'"';
			$txt .= ' a-status="'.$check[0]["status"].'"';
			$txt .= ' sqlid="'.$check[0]["id"].'"';
			$txt .= ' user-color="'.$ucolor.'"';
			
			if( $check[0]["text"] == "1" ) {
				$txt .= ' have-text="'.( $check[0]["have_text"] == "1" ? "true" : "false" ).'"';
				}

			if( $check[0]["image"] == "1" ) {
				$txt .= ' have-image="'.( $check[0]["have_image"] == "1" ? "true" : "false" ).'"';
				}
				
			if( $check[0]["other"] == "1" ) {
				$txt .= ' have-other="'.( $check[0]["have_other"] == "1" ? "true" : "false" ).'"';
				}				
			
			$txt .= ' style="position: relative; float: left; width: '.$boxwidth.'px; height: '.$boxheight.'px; z-index: 0;"';
		$txt .= '>';

			$txt .= '<div class="pageBox '.$page.'_box" style="'.( $page == "1" ? "border-left: 1px solid #ADADAD;" : "" ).' width: '.$boxwidth.'px; height: '.$boxheight.'px; position: relative;">';
				$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
				$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
				$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="'.$class.'_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="position: inherit !important; z-index: 10; color: #'.$hfc.'; background-color:'.$hc.';">';
					$txt .= '<div style="pointer-events: none; float:'.$class.'; margin-'.$class.': 4px;"> '.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
				$txt .= '</div>';
				$txt .= '<div id="'.$page.'_thumb" state="" class="'.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb thumbdraggbox '.$w.'" alter="0" page="'.$page.'" style="z-index: 10; width: 100%; height: 100%; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
					if( !empty( $check[0]["id"] ) ) {
						$txt .= "<div class='thumbdragg' page='".$check[0]["id"]."'></div>";
						}	

					$txt .= arrowChecker( $id, $page, $check[0]["type"] );				
				$txt .= '</div>';
			$txt .= '</div>';
			$txt .="<input type='checkbox' pageid='".$check[0]["id"]."' item='".$id."' state='' ptype='simple' name='pageSelector[]' value='".$page."' style='display: none;'>";
		$txt .= '</div>';
		
		if( strpos( $articleClass, "articleEnd" ) !== false ) {
			$articleCounter++;
			}
		}
	
	return $txt;
	}

function PlannerPageChecker() {
	global $pageCheck, $havePage, $id, $movingPages;
	
	error_log( "pos='".$pageCheck."' AND pub_id='".$id."'" );
	$check = sql_aget( "flatplan_planner", "pos='".$pageCheck."' AND pub_id='".$id."'", "*" );
	if( !empty( $check[0]["id"] ) ) {
		error_log( count( $check ) );
		for( $i = 0; $i < count( $check ); $i++ ) {
			$movingPages[] = array($pageCheck, $check[$i]["id"] );
			}
			
		$pageCheck += 1;
		$havePage = true;		
		}
	else {
		$havePage = false;
		}
	}

if( $_GET['op'] == 'loadPagePair' ) {
	$boxwidth = $_GET["width"];
	$boxheight = $_GET["height"];
	
	if( $_GET['filter'] == "undefined" ) {
		$_GET['filter'] = "all";
		}
		
	sql_update( 'accounts', 'fpFilter="'.$_GET['filter'].'"', 'id="'.$_SESSION['intra_user'].'"' );
	
	$alters = array();
	$holderWidth = 0;
	$text = '';
	$imghash = ( $_GET['cachebreak'] == 1 ? time() : "" );

	$myPublisher = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher' );	
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
	
	$sizes = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND width="1" AND issue="'.$issue[0][10].'" AND state="" LIMIT 2', '*' );
	$sizes = calculateSize( $sizes[1], $magazine[0][3], $issue[0][10] );
	$row = intval( intval($_GET['maxWidth'] )/229 );
	$divWidth = $row*229;
	
	$maxcsempe = floor( intval($_GET['maxWidth'] )/175.5 );		
	
	$currentArticle = "";
	$colors = sql_aget( 'article_colors', '1', '*' );
	$articleCounter = 1;
	
	$length = intval( $issue[0][6] );
	$counter = 1;
	$i = 0;
	while( $i <= $length ) {
		if( $counter > $maxcsempe ) $counter = 1;
		$text .= "<div style='display: inline-block; position: relative; margin-top: 10px; margin-left: 10px; margin-bottom: 6px;'>";
			if( $i != 0 ) {
				$text .= '<div class="plannerDropLineBox" style="left: -20px;"><div class="plannerDropLine" before="'.$i.'" style="left: 20px;"></div></div>';
				}
			
			$text .= drawPlannerPage( $_GET['id'], $i, 'left', $i );
			$text .= '<div class="plannerDropLineBox" style="left: 62px;"><div class="plannerDropLine" before="'.($i+1).'" style="left: 20px;"></div></div>';
			$text .= drawPlannerPage( $_GET['id'], ($i+1), 'right', $i );
				
		$text .= "</div>";
		$counter++;
		$i += 2;
		}
		
	$result[0] = $text;
	$result[1] = $alters;
	}

if( $_GET['op'] == 'loadarticles' ) {
    $txt = "<table cellspacing='0' cellpadding='0' width='100%' id='magtable'>";
    
    // HIRDETÉSEK	
    $sql = sql_aget( "flatplan_planner", "pub_id='".$_GET["id"]."' AND type='ad' AND pos!='0' ORDER BY pos ASC", "*" );
	if( count( $sql ) > 0 ) {
		$txt .= "<tr>";
			$txt .= "<td style='font-family: r_bold; padding-top: 10px;'><i onclick='hideList(\"ads\")' id='ads_button' class='far fa-minus-square' style='font-size: 13px;padding-right: 2px;color: #666;'></i>Ads</td>";
		$txt .= "<tr>";
		}
	
	$current = "";
	$start = "";
	for( $i = 0; $i < count( $sql ); $i++ ) {
		if( $sql[$i]["name"] == "" ) {
			$sql[$i]["name"] = "<i>Unnamed</i>";
			}
		
		if( $current != $sql[$i]["name"] ) {
			$current = $sql[$i]["name"];
			$start = $sql[$i]["pos"];
			}
		
		if( $current != $sql[($i+1)]["name"] ) {
			$txt .= "<tr class='ads_list'>";
				$txt .= "<td>".$sql[$i]["name"]."</td>";
				$txt .= "<td align='right'>".( $start != $sql[$i]["pos"] && $sql[$i]["name"] != "<i>Unnamed</i>" ? $start."-" : "" )."".$sql[$i]["pos"]."</td>";
				
			$txt .= "</tr>";
			}
		}
		
	//PROMOK
    $sql = sql_aget( "flatplan_planner", "pub_id='".$_GET["id"]."' AND type='promo' AND pos!='0' ORDER BY pos ASC", "*" );
	if( count( $sql ) > 0 ) {
		$txt .= "<tr>";
			$txt .= "<td style='font-family: r_bold; padding-top: 10px;'><i onclick='hideList(\"promo\")' id='promo_button' class='far fa-minus-square' style='font-size: 13px;padding-right: 2px;color: #666;'></i>Promotions</td>";
		$txt .= "<tr>";
		}	
	
	$current = "";
	$start = "";
	for( $i = 0; $i < count( $sql ); $i++ ) {
		if( $sql[$i]["name"] == "" ) {
			$sql[$i]["name"] = "<i>Unnamed</i>";
			}
		
		if( $current != $sql[$i]["name"] ) {
			$current = $sql[$i]["name"];
			$start = $sql[$i]["pos"];
			}
		
		if( $current != $sql[($i+1)]["name"] ) {
			$txt .= "<tr class='promo_list'>";
				$txt .= "<td>".$sql[$i]["name"]."</td>";
				$txt .= "<td align='right'>".( $start != $sql[$i]["pos"] && $sql[$i]["name"] != "<i>Unnamed</i>" ? $start."-" : "" )."".$sql[$i]["pos"]."</td>";
				
			$txt .= "</tr>";
			}
		}
		
	//CIKKEK
    $sql = sql_aget( "flatplan_planner", "pub_id='".$_GET["id"]."' AND type='article' AND pos!='0' ORDER BY pos ASC", "*" );
	if( count( $sql ) > 0 ) {
		$txt .= "<tr>";
			$txt .= "<td style='font-family: r_bold; padding-top: 10px;'><i onclick='hideList(\"article\")' id='article_button' class='far fa-minus-square' style='font-size: 13px;padding-right: 2px;color: #666;'></i>Editorials</td>";
		$txt .= "<tr>";
		}	
	
	$current = "";
	$start = "";
	for( $i = 0; $i < count( $sql ); $i++ ) {
		if( $sql[$i]["name"] == "" ) {
			$sql[$i]["name"] = "<i>Unnamed</i>";
			}
			
		if( $current != $sql[$i]["name"] ) {
			$current = $sql[$i]["name"];
			$start = $sql[$i]["pos"];
			}
		
		if( $current != $sql[($i+1)]["name"] ) {
			$txt .= "<tr class='article_list'>";
				$txt .= "<td>".$sql[$i]["name"]."</td>";
				$txt .= "<td align='right'>".( $start != $sql[$i]["pos"] ? $start."-" : "" )."".$sql[$i]["pos"]."</td>";
				
			$txt .= "</tr>";
			}
		}
	
	//MELÓSOK
	$txt .= "<tr>";
		$txt .= "<td style='font-family: r_bold; padding-top: 10px;'>Workers</td>";
	$txt .= "<tr>";
	
	$mag = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
	$mag = sql_aget( "magazines", "id='".$mag[0]["magazine_id"]."'", "*" );	
	
	$acc = sql_aget( "accounts", "`publisher`='".$mag[0]["publisher_id"]."' AND ( `group`='6' OR `group`='14' ) ORDER BY full_name ASC", "*" );
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
		
		$users[$i]["count"] = count( $count );
		$users[$i]["time"] = $time;
		}
	
	$users = array_orderby( $users, 'time', SORT_ASC, 'full_name', SORT_ASC);	
	for( $i = 0; $i < count( $users ); $i++ ) {
		//if( $users[$i]["count"] != 0 ) {
			$txt .= "<tr>";
				$txt .= "<td>".$users[$i]["full_name"]."</td>";
				$txt .= "<td align='right' style='".( $users[$i]["count"] == 0 ? "color: #FF0000;" : "" )."'>".$users[$i]["time"]."</td>";
			$txt .= "</tr>";			
		//	}
		}
	
	$txt .= "</table>";
	
	$result = $txt;
    }
		
print json_encode( $result );
	
?>