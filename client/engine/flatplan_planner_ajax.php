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
		$txt .= '<div class="'.$class.'_page '.$extra_class.''.$articleClass.' board-thumb-box" acolor="'.fontcolor( ( $check[0]["type"] == "article" ? $csempecolor : "rgb( 254, 229, 204 )" ) ).'" a-name="'.$articleName_.'" mixed="yes" aname="'.$articleName.'" style="position: absolute; left: '.$check[0]["x"].'px; top: '.$check[0]["y"].'px; width: 81px; height: 99px; z-index: 0; z-index: 0; border-right: 1px solid #ADADAD;">';
			
			$txt .= '<div class="pageBox '.$page.'_box" style=" position: relative; width: 81px; height: 114px;">';
				$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
				$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
				/*$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="'.$class.'_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="z-index: 10; width: 81px;  color: #; background-color:'.$hc.';">';
					$txt .= '<div style="pointer-events: none; float:'.$class.'; margin-'.$class.': 4px;">'.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
				$txt .= '</div>';*/
				$txt .= '<div id="'.$page.'_thumb" state="" class="board-thumb '.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb '.$w.'" alter="0" page="'.$page.'" style="position: relative; z-index: 10; top: 0px; width: 81px; height: 99px; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
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
			
			$txt .= ' style="position: absolute; left: '.$check[0]["x"].'px; top: '.$check[0]["y"].'px; width: 81px; height: 99px; z-index: 0;"';
		$txt .= '>';

			$txt .= '<div class="pageBox '.$page.'_box" style="'.( $page == "1" ? "border-left: 1px solid #ADADAD;" : "" ).' width: 81px; height: 99px;">';
				$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
				$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
				/*$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="'.$class.'_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="position: inherit !important; z-index: 10; width: 81px;  color: #'.$hfc.'; background-color:'.$hc.';">';
					$txt .= '<div style="pointer-events: none; float:'.$class.'; margin-'.$class.': 4px;"> '.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
				$txt .= '</div>';*/
				$txt .= '<div id="'.$page.'_thumb" state="" class="board-thumb '.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb '.$w.'" alter="0" page="'.$page.'" style="z-index: 10; width: 81px; height: 99px; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
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
	global $currentArticle, $colors, $articleCounter, $counter, $maxcsempe, $currentcsempe, $plans, $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $fPage, $sizes, $path, $fin, $imghash, $issue;
	
	list( $w, $h ) = $sizes;
	
	if( $page == 0 ) {
		return '<div class="" style="float: left; width: 83px; height: 116px; z-index: 0;"></div>';
		}

	if( $page > intval( $issue[0][6] ) ) {
		return '<div class="" style="float: left; width: 83px; height: 116px; z-index: 0;"></div>';
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
				$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="'.$class.'_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="z-index: 10; width: 81px;  color: #; background-color:'.$hc.';">';
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
			
			$txt .= ' style="position: relative; float: left; width: 81px; height: 116px; z-index: 0;"';
		$txt .= '>';

			$txt .= '<div class="pageBox '.$page.'_box" style="'.( $page == "1" ? "border-left: 1px solid #ADADAD;" : "" ).' width: 81px; height: 116px; position: relative;">';
				$txt .= '<input type="hidden" id="'.$page.'_current" name="'.$page.'_current" value="0">';
				$txt .= '<input type="hidden" id="'.$page.'_max" name="'.$page.'_max" value="0">';
				$txt .= '<div alter="0" id="_'.$page.'" item="" page="'.$page.'" class="'.$class.'_pagenr pagenr checking2 a_'.$check[0]["status"].'" style="position: inherit !important; z-index: 10; width: 81px;  color: #'.$hfc.'; background-color:'.$hc.';">';
					$txt .= '<div style="pointer-events: none; float:'.$class.'; margin-'.$class.': 4px;"> '.str_pad( $page, 3, '0', STR_PAD_LEFT).'</div>';
				$txt .= '</div>';
				$txt .= '<div id="'.$page.'_thumb" state="" class="'.( !empty( $check[0]["id"] ) ? "haveArticle " : "" ).'thumb thumbdraggbox '.$w.'" alter="0" page="'.$page.'" style="z-index: 10; width: 81px; height: 99px; cursor: pointer; background-repeat:no-repeat; background-color: '.$csempecolor.';">';
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

if( $_GET["op"] == "removeplannermixedtile" ) {
	$check = sql_aget( "flatplan_planner", "id='".$_GET["pid"]."'", "*" );
	sql_delete( "flatplan_planner", "id='".$_GET["pid"]."'" );
	
	$pages = sql_aget( "flatplan_planner", "pos='".$check[0]["pos"]."' AND pub_id='".$check[0]["pub_id"]."'", "*" );
	if( count( $pages ) == "1" ) {
		sql_update( "flatplan_planner", "mixed='0', template='', position=''", "id='".$pages[0]["id"]."'" );
		}
	}

if( $_GET["op"] == "removeplannertiles" ) {
	for( $i = 0; $i < count( $_POST["pageselector"] ); $i++ ) {
		sql_delete( "flatplan_planner", "id='".$_POST["pageselector"][$i]."'" );
		}
	}

if( $_GET["op"] == "modhalfhadd" ) {
	sql_update( "flatplan_planner", "name='".$_POST["data"]["name"]."'", "id='".$_GET["pid"]."'" );
	}

if( $_GET["op"] == "loadhalfaddbarmod" ) {
	$cikk = sql_aget("flatplan_planner", "id='".$_GET["pid"]."' order by pos ASC", "*" );
	
	$result .= '<div onclick="modHalfAd(\''.$cikk[0]["id"].'\')" class="panelButton planner-add" style="margin-left: 10px; margin-top: 11px; color: #FFF; font-size: 16px; background-color: rgb( 111, 112, 114); border: 1px solid rgb(147, 149, 152); width: 40px; cursor: pointer;">DES</div>';
	
	$result .= '<div style="margin-left: 15px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">TITLE</div>';
	$result .= '<div style="margin-left: 5px; margin-top: 13px; float: left;"><input type="text" id="name" name="name" style="width: 140px;" value="'.$cikk[0]["name"].'"></div>';		
	}

if( $_GET["op"] == "savehalfarticle" ) {
	$data = array();
	parse_str( $_POST["data"], $data );
	
	$page = sql_aget( "flatplan_planner", "id='".$data["page_id"]."'", "*" );
	$samepages = sql_aget( "flatplan_planner", "pub_id='".$data["pub_id"]."' AND pos='".$page[0]["pos"]."'", "*" );
	
	if( $samepages[0]["mixed"] == "1" ) {
		
		}
	else {
		$cpage = 0;
		for( $i = 0; $i < count( $samepages ); $i++ ) {
			if( $cpage == $data["ad_pos"] ) {
				$cpage++;
				}
				
			sql_update( "flatplan_planner", "mixed='1', template='".$data["template"]."', position='".$cpage."'", "id='".$samepages[$i]["id"]."'" );
			$cpage++;
			}
			
		$names = array(
			"pub_id",
			"user_id",
			"date",
			"name",
			"type",
			"pos",
			"workerName",
			"workerID",
			"atype",
			"tspent",
			"remark",
			"mixed",
			"template",
			"position",
			);
			
		$values = array(
			$data["pub_id"],
			$_SESSION['intra_user'],
			time(),
			$data["name"],
			"ad",
			$samepages[0]["pos"],
			"",
			"0",
			"0",
			"0",
			"",
			"1",
			$data["template"],
			$data["ad_pos"],
			);
			
		sql_add( "flatplan_planner", $names, $values );					
		}
	
	$result = $samepages;
	}

if( $_GET["op"] == "savearticle" ) {
	$worker = sql_aget( "accounts", "id='".$_POST["data"]["worker"]."'", "*" );
	
	if( $_GET["pid"] != "" ) {
		$check = sql_aget( "flatplan_planner", "id='".$_GET["pid"]."'", "*" );
		$where = "`id`='".$_GET["pid"]."'";
		
		if( $check[0]["type"] == "article" ) {
			$where = "`name`='".$check[0]["name"]."' AND `pub_id`='".$check[0]["pub_id"]."'";

			sql_update(
				"`flatplan_planner`",
				
				"`name`='".$_POST["data"]["name"]."',
				`type`='".$_POST["data"]["type"]."',
				`atype`='".$_POST["data"]["article"]."',
				`workerName`='".$worker[0]["full_name"]."',
				`workerID`='".( !empty( $worker[0]["id"] ) ? $worker[0]["id"] : "0" )."',
				`tspent`='".$_POST["data"]["tspent"]."',
				`remark`='".$_POST["data"]["remark"]."',
				`text`='".$_POST["data"]["text"]."',
				`have_text`='".$_POST["data"]["h_text"]."',
				`image`='".$_POST["data"]["image"]."',
				`have_image`='".$_POST["data"]["h_image"]."',
				`other`='".$_POST["data"]["other"]."',
				`have_other`='".$_POST["data"]["h_other"]."'",
				
				"".$where.""
				);			
			}
			
		else {
			for( $i = 0; $i < count( $_POST["pageselector"] ); $i++ ) {
				$where = "`id`='".$_POST["pageselector"][$i]."'";
				sql_update(
					"`flatplan_planner`",
					
					"`name`='".$_POST["data"]["name"]."',
					`atype`='".$_POST["data"]["article"]."',
					`workerName`='".$worker[0]["full_name"]."',
					`workerID`='".( !empty( $worker[0]["id"] ) ? $worker[0]["id"] : "0" )."',
					`tspent`='".$_POST["data"]["tspent"]."',
					`remark`='".$_POST["data"]["remark"]."'",
					
					"".$where.""
					);
				}
			}
		}
		
	else {	
		$names = array(
			"pub_id",
			"user_id",
			"date",
			"name",
			"type",
			"pos",
			"workerName",
			"workerID",
			"atype",
			"tspent",
			"remark",
			"text",
			"have_text",
			"image",
			"have_image",
			"other",
			"have_other",
			);
		
		for( $i = 0; $i < count( $_POST["pageselector"] ); $i++ ) { 	
			$values = array(
				$_GET["id"],
				$_SESSION['intra_user'],
				time(),
				$_POST["data"]["name"],
				$_POST["data"]["type"],
				$_POST["pageselector"][$i],
				$worker[0]["full_name"],
				( !empty( $worker[0]["id"] ) ? $worker[0]["id"] : "0" ),
				$_POST["data"]["article"],
				$_POST["data"]["tspent"],
				$_POST["data"]["remark"],
				$_POST["data"]["text"],
				$_POST["data"]["h_text"],
				$_POST["data"]["image"],
				$_POST["data"]["h_image"],
				$_POST["data"]["other"],
				$_POST["data"]["h_other"],
				);
				
			sql_add( "flatplan_planner", $names, $values );	
			}
		}
	}

if( $_GET["op"] == "worktime" ) {
	$types = sql_aget( "flatplan_articletypes", "id='".$_GET["aid"]."' order by id ASC", "*" );
	if( $_GET["aname"] != "" ) {
		$pagecount = sql_aget("flatplan_planner", "pub_id='".$_GET["id"]."' AND type='article' AND pos!='0' AND name='".$_GET["aname"]."' order by pos ASC", "*" );
		$time = $types[0]["time"] * count( $pagecount );
		}
	else {
		$time = $types[0]["time"] * count( $_POST["pageselector"] );
		}
	
	$result = $time;
	}

if( $_GET["op"] == "loadhalfadbar" ) {
	$pub = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
	
	$result .= '<form method="post" action="#" name="halfadform" id="halfadform">';
	$result .= '<div onclick="saveHalfAd()" class="panelButton planner-add" style="margin-left: 10px; margin-top: 11px; color: #FFF; font-size: 16px; background-color: rgb( 111, 112, 114); border: 1px solid rgb(147, 149, 152); width: 40px; cursor: pointer;">DES</div>';
	
	$result .= '<div style="margin-left: 15px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">TITLE</div>';
	$result .= '<div style="margin-left: 5px; margin-top: 13px; float: left;"><input type="text" id="name" name="name" style="width: 140px;" value="'.$cikk[0]["name"].'"></div>';
	
	$result .= '<div style="margin-left: 15px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">SIZE</div>';
	$result .= '<div style="margin-left: 10px; margin-top: 12px; float: left;">';
		$result .= '<select onchange="switchHalfadPos()" id="size" name="size">';
			$types = array( "1/2", "1/3", "1/4" );
				
			for( $i = 0; $i < count( $types ); $i++ ) {
				$result .= "<option ".( $cikk[0]["halfad"] == $types[$i] ? "selected" : "" )." value='".$types[$i]."'>".$types[$i]."</option>";
				}
		$result .= '</select>';
	$result .= '</div>';	

	$result .= '<div style="margin-left: 15px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">ORIENT</div>';
	$result .= '<div style="margin-left: 10px; margin-top: 12px; float: left;">';
		$result .= '<select onchange="switchHalfadPos()" id="orient" name="orient">';
			$types = array( "Portrait", "Landscape" );
				
			for( $i = 0; $i < count( $types ); $i++ ) {
				$result .= "<option ".( $cikk[0]["halfad"] == $types[$i] ? "selected" : "" )." value='".$types[$i]."'>".$types[$i]."</option>";
				}
		$result .= '</select>';
	$result .= '</div>';
	
	$result .= '<div id="1-2_portrait" class="pos_icons" style="margin-left: 10px; margin-top: 14px; float: left; color: #c7c7c7;">';
		$result .= '<div onclick="selectAdPos( this )" template="2_2" pos="0" class="portrait_ad_blank_icon"></div>';
		$result .= '<div onclick="selectAdPos( this )" template="2_2" pos="1" class="portrait_ad_blank_icon"></div>';
	$result .= '</div>';

	$result .= '<div id="1-2_landscape" class="pos_icons" style="display: none; margin-left: 10px; margin-top: 14px; float: left; color: #c7c7c7;">';
		$result .= '<div onclick="selectAdPos( this )" template="2_1" pos="0" class="landscape_ad_blank_icon" style="margin-top: -2px;"></div>';
		$result .= '<div onclick="selectAdPos( this )" template="2_1" pos="1" class="landscape_ad_blank_icon" style="margin-top: 3px;"></div>';
	$result .= '</div>';

	$result .= '<div id="1-3_portrait" class="pos_icons" style="display: none; margin-left: 10px; margin-top: 14px; float: left; color: #c7c7c7;">';
		$result .= '<div onclick="selectAdPos( this )" template="3_7" pos="0" class="portrait_ad_blank_icon"></div>';
		$result .= '<div onclick="selectAdPos( this )" template="3_7" pos="1" class="portrait_ad_blank_icon double_portrait_ad_icon"></div>';

		$result .= '<div onclick="selectAdPos( this )" template="3_6" pos="0" class="portrait_ad_blank_icon double_portrait_ad_icon" style="margin-left: 12px;"></div>';
		$result .= '<div onclick="selectAdPos( this )" template="3_6" pos="1" class="portrait_ad_blank_icon"></div>';
	$result .= '</div>';

	$result .= '<div id="1-3_landscape" class="pos_icons" style="display: none; margin-left: 10px; margin-top: 8px; float: left; color: #c7c7c7;">';
		$result .= '<div onclick="selectAdPos( this )" template="3_5" pos="0" class="landscape_ad_blank_icon" style="margin-top: -2px;"></div>';
		$result .= '<div onclick="selectAdPos( this )" template="3_5" pos="1" class="landscape_ad_blank_icon" style="margin-top: 3px;"></div>';
		$result .= '<div onclick="selectAdPos( this )" template="3_5" pos="2" class="landscape_ad_blank_icon" style="margin-top: 3px;"></div>';
	$result .= '</div>';

	$result .= '<div id="1-4_portrait" class="pos_icons" style="display: none; margin-left: 10px; margin-top: 5px; float: left; color: #c7c7c7;">';
		$result .= '<div onclick="selectAdPos( this )" template="4_5" pos="0" class="negyed_ad_blank_icon" style="margin-top: 3px; height: 24px; width: 29px;"></div>';
		$result .= '<div onclick="selectAdPos( this )" template="4_5" pos="1" class="negyed_ad_blank_icon" style="float: left; height: 8px; width: 29px; margin-top: 3px; clear: both;"></div>';
		
		$result .= '<div style="float: left; top: 5px; position: absolute; margin-left: 39px;">';
			$result .= '<div onclick="selectAdPos( this )" template="4_6" pos="1" class="negyed_ad_blank_icon" style="float: left; height: 8px; width: 29px; margin-top: 3px; clear: both;"></div>';
			$result .= '<div onclick="selectAdPos( this )" template="4_6" pos="2" class="negyed_ad_blank_icon" style="margin-top: 3px; height: 24px; width: 29px; clear: both;"></div>';
		$result .= '</div>';
	$result .= '</div>';
	
	$result .= '<input type="hidden" name="ad_pos" id="ad_pos", value="">';
	$result .= '<input type="hidden" name="page_id" id="ad_pos", value="'.$_GET["before"].'">';
	$result .= '<input type="hidden" name="pub_id" id="pub_id", value="'.$_GET["id"].'">';
	$result .= '</form>';
	}

if( $_GET["op"] == "loadaddbar" ) {
	$pub = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
	$types = sql_aget( "flatplan_articletypes", "pub_id='".$_GET["id"]."' order by id ASC", "*" );
	
	if( $_GET["pid"] != "" ) {
		$cikk = sql_aget("flatplan_planner", "id='".$_GET["pid"]."' order by pos ASC", "*" );
		$time = $types[0]["time"] * count( $cikk );
		}
	else {
		$time = $types[0]["time"] * count( $_POST["pageselector"] );
		}
	
	$result .= '<div onclick="savePlanner(\''.$cikk[0]["id"].'\')" class="panelButton planner-add" style="margin-left: 10px; margin-top: 11px; color: #FFF; font-size: 16px; background-color: rgb( 111, 112, 114); border: 1px solid rgb(147, 149, 152); width: 40px; cursor: pointer;">DES</div>';
	
	if( $cikk[0]["type"] == "article" || empty( $cikk[0]["id"] ) ) {
		$result .= '<div style="margin-left: 10px; margin-top: 12px; float: left;">';
			$result .= '<select onchange="updateWorkTime()" id="atype" name="atype">';
					
				for( $i = 0; $i < count( $types ); $i++ ) {
					$result .= "<option ".( $cikk[0]["atype"] == $types[$i]["id"] ? "selected" : "" )." value='".$types[$i]["id"]."'>".$types[$i]["name"]."</option>";
					}
			$result .= '</select>';
		$result .= '</div>';
		}
			
	$result .= '<div style="margin-left: 15px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">TITLE</div>';
	$result .= '<div style="margin-left: 5px; margin-top: 13px; float: left;"><input type="text" id="name" name="name" style="width: 140px;" value="'.$cikk[0]["name"].'"></div>';
	
	if( $cikk[0]["type"] != "ad" ) {
		$result .= '<div style="margin-left: 10px; margin-top: 12px; float: left;">';
			$result .= '<select id="workerID" name="workerID">';
				$result .= "<option value='0'>---------------</option>";
				$users = array();
				$array = sql_aget( "accounts", "publisher='".$pub[0]["publisher_id"]."' AND ( `group`='6' OR `group`='14' ) order by full_name ASC", "*" );
				
				for( $i = 0; $i < count( $array ); $i++ ) {
					$temp = explode( ",", $array[$i]["showMagazines"] );
					if( in_array( $pub[0]["magazine_id"], $temp ) ) {
						$users[] = $array[$i];
						}
					}
				for( $i = 0; $i < count( $users ); $i++ ) {
					$result .= "<option ".( $users[$i]["id"] == $cikk[0]["workerID"] ? "selected" : "" )." value='".$users[$i]["id"]."'>".$users[$i]["full_name"]."</option>";
					}
			$result .= '</select>';
		$result .= '</div>';
		}
		
	if( $cikk[0]["type"] != "ad" ) {
		$result .= '<div style="margin-left: 15px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">ASSETS</div>';
		$result .= '<div style="margin-left: 5px; margin-top: 12px; float: left; color: #c7c7c7; font-size: 11px;">REQU<br>AVAIL</div>';
		
		$result .= '<div style="margin-left: 10px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">Text</div>';
		$result .= '<div style="margin-left: 2px; margin-top: 7px; float: left; color: #c7c7c7; font-size: 11px;">
						<input type="checkbox" name="r_text" id="r_text" value="1" '.( $cikk[0]["text"] == "1" ? "checked" : "" ).'>
						<br>
						<input type="checkbox" name="have_text" id="have_text" value="1" '.( $cikk[0]["have_text"] == "1" ? "checked" : "" ).'>
					</div>';
	
		$result .= '<div style="margin-left: 10px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">Image</div>';
		$result .= '<div style="margin-left: 2px; margin-top: 7px; float: left; color: #c7c7c7; font-size: 11px;">
						<input type="checkbox" name="r_image" id="r_image" value="1" '.( $cikk[0]["image"] == "1" ? "checked" : "" ).'>
						<br>
						<input type="checkbox" name="have_image" id="have_image" value="1" '.( $cikk[0]["have_image"] == "1" ? "checked" : "" ).'>
					</div>';
					
		$result .= '<div style="margin-left: 10px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">Other</div>';
		$result .= '<div style="margin-left: 2px; margin-top: 7px; float: left; color: #c7c7c7; font-size: 11px;">
						<input type="checkbox" name="r_other" id="r_other" value="1" '.( $cikk[0]["other"] == "1" ? "checked" : "" ).'>
						<br>
						<input type="checkbox" name="have_other" id="have_other" value="1" '.( $cikk[0]["have_other"] == "1" ? "checked" : "" ).'>
					</div>';
		}
	
	if( $cikk[0]["type"] == "article" ) {	
		$result .= '<div style="margin-left: 15px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">TIME <span class="work_time">'.$time.'</span> min /</div>';
		$result .= '<div style="margin-left: 5px; margin-top: 13px; float: left;"><input type="text" id="tspent" name="tspent" style="width: 25px;" value="'.$cikk[0]["tspent"].'"></div>';
		$result .= '<div style="margin-left: 5px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">min</div>';
		}
		
	$result .= '<div style="margin-left: 15px; margin-top: 14px; float: left; color: #c7c7c7; font-weight: bold;">REMARKS</div>';
	$result .= '<div style="margin-left: 5px; margin-top: 13px; float: left;"><input type="text" name="remark" id="remark" style="width: 140px;" value="'.$cikk[0]["remark"].'"></div>';
	
	$result = array( $result, $cikk[0]["name"] );
	}

if( $_GET['op'] == 'loadPasteboard' ) {
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
	$i = 1 + $length;
	
	$paste = sql_aget( "flatplan_planner", "pub_id='".$_GET["id"]."' AND pos='0' order by id ASC", "*" );
	$text = "";
	for( $x = 0; $x < count( $paste ); $x++ ) {
		$text .= drawPlaceboardPage( $_GET['id'], $i, 'left', $paste[$x]["id"] );
		$i++;
		}
	
	$result[0] = $text;
	$result[1] = $alters;	
	}

if( $_GET['op'] == 'dragsave' ) {
	$id = $_GET["id"];
	
	if( $_GET["type"] == "leftpanel" ) {
		$before = $_GET["before"];
		$pageCheck = $before;
		
		$checker = sql_aget( "flatplan_planner", "id='".$before."'", "*" );
		
		if( $_GET["mod"] == "update" ) {
			sql_update( "flatplan_planner", "type='".$_GET["stype"]."', workerName='', workerID='0', tspent='0', remark='', text='0', have_text='0', image='0', have_image='0', other='0', have_other='0'", "pos='".$checker[0]["pos"]."' AND pub_id='".$checker[0]["pub_id"]."'" );
			}
		
		if( $_GET["mod"] == "new" ) {
			$havePage = false;
			
			$movingPages = array();
			do {
				PlannerPageChecker();	
			} while($havePage);	
				
			for( $x = count( $movingPages )-1; $x >= 0; $x-- ) {
				sql_update( "flatplan_planner", "pos='".( $movingPages[$x][0]+1 )."'", "id='".$movingPages[$x][1]."'" );
				}
				
			$names = array(
				"pub_id",
				"user_id",
				"date",
				"name",
				"type",
				"pos",
				"workerName",
				"workerID",
				"atype",
				"tspent",
				"remark",
				"text",
				"have_text",
				"image",
				"have_image",
				"other",
				"have_other",
				);
	
			$values = array(
				$_GET["id"],
				$_SESSION['intra_user'],
				time(),
				"",
				$_GET["stype"],
				$before,
				"",
				"0",
				"0",
				"0",
				"",
				"0",
				"0",
				"0",
				"0",
				"0",
				"0",
				);
				
			sql_add( "flatplan_planner", $names, $values );
			}
		}
	
	if( $_GET["type"] == "fp" ) {
		$before = $_GET["before"];
		$pageCheck = $before;
		$havePage = false;
		
		$checker = sql_aget( "flatplan_planner", "id='".$before."'", "*" );
		
		for( $i = 0; $i < count( $_POST["pageselector"] ); $i++ ) {
			$c = sql_aget( "flatplan_planner", "id='".$_POST["pageselector"][$i]."'", "*" );
			if( $c[0]["mixed"] == "1" ) {
				$where = "pos='".$c[0]["pos"]."' AND template='".$c[0]["template"]."' AND pub_id='".$c[0]["pub_id"]."'";
				$where2 = "pos='-1' AND template='".$c[0]["template"]."' AND pub_id='".$c[0]["pub_id"]."'";
				}
			else {
				$where = "id='".$_POST["pageselector"][$i]."'";
				$where2 = "pos='-1' AND pub_id='".$c[0]["pub_id"]."'";
				}
			
			sql_update( "flatplan_planner", "pos='-1'", $where );
			$movingPages = array();
			do {
				PlannerPageChecker();	
			} while($havePage);	
			
			for( $x = count( $movingPages )-1; $x >= 0; $x-- ) {
				//error_log( $movingPages[$x][0].", ".$movingPages[$x][1] );
				sql_update( "flatplan_planner", "pos='".( $movingPages[$x][0]+1 )."'", "id='".$movingPages[$x][1]."'" );
				}
			
			sql_update( "flatplan_planner", "pos='".$before."'", $where2 );
			$before += 1;
			$pageCheck = $before;
			}
		}
		
	if( $_GET["type"] == "pasteboard" ) {
		$result = "";
		$csempe_width = 81;
		$csempe_height = 118;
		$maxwidth = 399;
		$current_x = $_GET["x"];
		$current_y = $_GET["y"];
		
		for( $i = 0; $i < count( $_POST["pageselector"] ); $i++ ) {
			$c = sql_aget( "flatplan_planner", "id='".$_POST["pageselector"][$i]."'", "*" );
			
			if( $c[0]["mixed"] == "1" ) {
				$where = "pos='".$c[0]["pos"]."' AND template='".$c[0]["template"]."' AND pub_id='".$c[0]["pub_id"]."'";
				}
			else {
				$where = "id='".$_POST["pageselector"][$i]."'";
				}
				
			sql_update( "flatplan_planner", "pos='0', x='".$current_x."', y='".$current_y."'", $where );
			$current_x += 84;
			}	
		}
	}

if( $_GET['op'] == 'loadPagePair' ) {
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

if( $_GET['op'] == 'mixedModify' ) {
	$article = sql_aget("flatplan_planner", "id='".$_GET["id"]."'", "*" );
	$articles = sql_aget("flatplan_planner", "pub_id='".$article[0]["pub_id"]."' AND pos='".$article[0]["pos"]."' order by position ASC", "*" );
	$pub = sql_aget( "publications", "id='".$article[0]["pub_id"]."'", "*" );
	
	$result2 = "<div>";
		$result2 .= file_get_contents( "/var/www/intra/client/images/mixed_preview/".$article[0]["template"].".svg" );
	$result2 .= "</div>";
	
	
	for( $i = 0; $i < count( $articles ); $i++ ) {
		$result .= "<div class='detailWindow ".$i."_window' style='display: none;'>";
			$result .= "<form id='".$i."_subForm' method='post' action=''>";
		
			$result .= "<div class='panelTitle'>Modify Article</div>";
			$result .= "<div class='panelControl' style='width: 450px !important; min-width: 450px !important;'>";
			
			$result .= "<input type='hidden' id='m_ctype' name='m_ctype' value='".$articles[$i]["type"]."'>";
			$result .= "<input type='hidden' id='m_pubid' name='m_pubid' value='".$articles[$i]["pub_id"]."'>";
			$result .= "<input type='hidden' id='m_aname' name='m_aname' value='".$articles[$i]["aname"]."'>";
			$result .= "<input type='hidden' id='m_slots' name='m_slots' value='".$articles[$i]["pos"]."'>";
			$result .= "<input type='hidden' id='m_template' name='m_template' value='".$articles[$i]["template"]."'>";
			$result .= "<input type='hidden' id='m_position' name='m_position' value='".$i."'>";
			
			$result .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
				$result .= "<tbody>";
					$result .= "<tr class='planner_table_row planner_row_1'>";						 
						$result .= "<td align='left' height='23px' colspan='100' style='padding-left: 0px;'>";
							$result .= "Status";
							$result .= "<select name='m_status' id='m_status'>";
								$status = array( "defined"=>"Defined", "progress"=>"In progress", "waiting"=>"Waiting", "finished"=>"Finished", "error"=>"Error" );
								
								foreach( $status as $key=>$value ) {
									$result .= '<option '.( $key == $articles[$i]["status"] ? "selected" : "" ).' value="'.$key.'">'.$value.'</option>';
									}
							$result .= "</select>";
						$result .= "</td>";
					$result .= "</tr>";
					
					$result .= "<tr class='planner_table_row planner_row_2'>";
						$result .= "<td align='left' height='23px' colspan='8'>";
							$result .= "Title";
							$result .= "<input type='text' autocomplete='off' id='m_name' name='m_name' style='width: 250px;' value='".$articles[$i]["name"]."'>";
						$result .= "</td>";
					$result .= "</tr>";
		
					$result .= "<tr class='planner_table_row planner_row_3'>";
						$result .= "<td id='atype_box' align='left' height='23px' colspan='6'>";
							$result .= "Article Type";
							$result .= "<select id='atype' name='atype'>";
								$types = sql_aget( "flatplan_articletypes", "pub_id='".$articles[$i]["pub_id"]."' order by id ASC", "*" );	
								
								for( $x = 0; $x < count( $types ); $x++ ) {
									$result .= "<option ".( $articles[$i]["type"] == $types[$x]["id"] ? "selected" : "" )." value='".$types[$x]["id"]."'>".$types[$x]["name"]."</option>";
									}
							$result .= "</select>";
						$result .= "</td>";
		
						$result .= "<td id='workerID_box' align='left' height='23px' colspan='4' style='padding-left: 0px;'>";
							$result .= "Designer";
							$result .= "<select name='workerID' id='workerID'>";
								$result .= "<option value='0'>---------------</option>";

								$users = array();
								$array = sql_aget( "accounts", "publisher='".$pub[0]["publisher_id"]."' AND `group`='6' order by full_name ASC", "*" );
								
								for( $x = 0; $x < count( $array ); $x++ ) {
									$temp = explode( ",", $array[$x]["showMagazines"] );
									if( in_array( $pub[0]["magazine_id"], $temp ) ) {
										$users[] = $array[$x];
										}
									}
								for( $x = 0; $x < count( $users ); $x++ ) {
									$result .= "<option ".( $users[$x]["id"] == $articles[$i]["workerID"] ? "selected" : "" )." value='".$users[$x]["id"]."'>".$users[$x]["full_name"]."</option>";
									}			
							$result .= "</select>";
						$result .= "</td>";
					$result .= "</tr>";
					
					$result .= "<tr class='planner_table_row planner_row_4'>";
						$result .= "<td align='left' height='23px' colspan='6'>";
							$result .= "Projected Time to Complete ".$time." mins";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='4'>";
							$result .= "Time Spent <input type='text' name='tspent' value='".$articles[$i]["tspent"]."' style='width: 30px;'> mins";
						$result .= "</td>";
					$result .= "</tr>";
		
					$result .= "<tr class='planner_table_row planner_row_5'>";
						$result .= "<td align='left' height='23px' colspan='2'>Required Assets</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "Text";
							$result .= "<input onclick=\"checkAvailable( 'text' )\" type='checkbox' name='r_text' id='r_text' value='1' ".( $articles[$i]["text"] == "1" ? "checked" : "" )." >";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "Image";
							$result .= "<input onclick=\"checkAvailable( 'image' )\" type='checkbox' name='r_image' id='r_image' value='1' ".( $articles[$i]["image"] == "1" ? "checked" : "" ).">";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "Other";
							$result .= "<input onclick=\"checkAvailable( 'other' )\" type='checkbox' name='r_other' id='r_other' value='1' ".( $articles[$i]["other"] == "1" ? "checked" : "" ).">";
						$result .= "</td>";
					$result .= "</tr>";
		
					$result .= "<tr class='planner_table_row planner_row_6'>";
						$result .= "<td align='left' height='23px' colspan='2'>Available Assets</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "<div id='a_text'>";
								$result .= "Text";
								$result .= "<input type='checkbox' name='have_text' id='have_text' value='1' ".( $articles[$i]["have_text"] == "1" ? "checked" : "" ).">";
							$result .= "</div>";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "<div id='a_image'>";
								$result .= "Image";
								$result .= "<input type='checkbox' name='have_image' id='have_image' value='1' ".( $articles[$i]["have_image"] == "1" ? "checked" : "" ).">";
							$result .= "</div>";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "<div id='a_other'>";
								$result .= "Other";
								$result .= "<input type='checkbox' name='have_other' id='have_other	' value='1' ".( $articles[$i]["have_other"] == "1" ? "checked" : "" ).">";
							$result .= "</div>";
						$result .= "</td>";
					$result .= "</tr>";
		
					$result .= "<tr class='planner_table_row planner_row_7'>";
						$result .= "<td align='left' height='23px' colspan='10'>Remarks</td>";
					$result .= "</tr>";
					$result .= "<tr class='planner_table_row planner_row_8'>";
						$result .= "<td align='left' height='23px' colspan='10'>";
							$result .= "<textarea name='remark' id='remark' style='resize: none; width: 444px; height: 60px;'>".stripslashes( $articles[$i]["remark"] )."</textarea>";
						$result .= "</td>";
					$result .= "</tr>";	
				$result .= "</tbody>";
			$result .= "</table>";
		
		if( $_GET["data"] !== "create" ) {
			$result .= "<table id='assets' class='panelTable' cellspacing='0' cellpadding='0' style='margin-top: 20px;'>";
				$result .= "<thead>";
					$result .= "<tr>";
						$result .= "<td colspan='3' style='background-color: transparent; color: #FFF; padding-bottom: 0px;'>Assets</td>";
					$result .= "</tr>";
				$result .= "</thead>";
				
				$result .= "<tbody id='fileupload_uploaded'>";
					$files = sql_aget( "flatplan_files", "articlename='".$articles[0]["name"]."' ORDER BY 'origname' ASC", "*" );		
					for( $x = 0; $x < count( $files ); $x++ ) {
						$txt .= "<tr>";
							$txt .= "<td colspan='2' style='padding-left: 0px; font-size: 14px; padding-top: 2px; color: #CCC'>".$files[$x]["origname"]."</td>";
							$txt .= "<td align='right' style='padding-left: 0px; padding-right: 3px; padding-top: 2px; font-size: 16px;'>
										<span onclick='fpfiledownload( \"".$files[$x]["id"]."\" )' style='cursor: pointer;'><i class='fas fa-download'></i></span>
										<span onclick='fpfileremove( \"".$files[$x]["id"]."\", \"".$files[$x]["origname"]."\" )' style='cursor: pointer;'><i class='far fa-times-circle' style='color: #D22A33;'></i></span>
									 </td>";
							$txt .= "</tr>";
						}
						
					$result .= $txt;
				$result .= "</tbody>";
			$result .= "</table>";
			
			$result .= "<table id='assetsTable' class='panelTable' cellspacing='0' cellpadding='0'>";
				$result .= "<tr>";
					$result .= "<td style='padding-top: 7px;'>";
						$result .= "<span id='select-file'>";
							$result .= "<i onclick=\"$('#afile').click()\" class='fas fa-upload' style='font-size: 16px; cursor: pointer; margin-right: 7px;'></i>";
							$result .= "<span id='targetfile' style='font-size: 20px; display: none;'>";
								$result .= "<span id='currentFileName' style='font-size: 13px; margin-right: 5px;'></span><i onclick='window.parent.frames[0].fileUpload()' class='fas fa-file-upload' style='cursor: pointer;'></i>";
							$result .= "</span>";
							$result .= "<input onchange='currentFile()' type='file' id='afile' name='afile' style='visibility: hidden;'>";
						$result .= "</span>";
						$result .= "<span id='selected-file' style='display: none;'></span>";
						
						
					$result .= "</td>";
					$result .= "<td align='right' class='fp-up-box' style='visibility: hidden; padding-top: 7px;'>";
						$result .= "<div style='width: 150px; border: 1px solid #CCC; height: 15px; text-align: left;'>";
							$result .= "<div class='fup-bar' style='background-color: #FFF; height: 100%; width: 0px;'></div>";
						$result .= "</div>";
					$result .= "</td>";
					
					$result .= "<td algin='left' class='fp-up-box' style='visibility: hidden; padding-left: 5px; padding-top: 7px;'>";
						$result .= "<div class='fup-percent' style='width: 40px;'></div>";
					$result .= "</td>";
				$result .= "</tr>";
			$result .= "</table>";
			}
			
			$result .= "<table class='panelTable' cellspacing='0' cellpadding='0'>";
				$result .= "<tr>";
					$result .= "<td colspan='10' align='center' style='padding-top: 10px;'>";
						$result .= "<div onclick=\"closeMixedSettings( ".$i." )\" style='margin-left: 2px; float: inherit; display: inline-block;' class='panelButton'>Cancel</div>";
						$result .= "<div id='plannersave' onclick='closeMixedSettings( ".$i." )' style='margin-left: 20px; float: inherit; display: inline-block;' class='panelButton'>Save</div>";
					$result .= "</td>";
				$result .= "</tr>";
			$result .= "</table>";					
		
								
			$result .= "</div>";
			
			$result .= "</form>";
		$result .= "</div>";
		}
		
	$result = array( $result2, $result );
	}

if( $_GET['op'] == 'mixedSelect' ) {
	parse_str( $_POST["data"], $data );
	
	$db = explode( "_", $_GET["layout"] );
	$db = $db[0];

	$result2 = "<div>";
		$result2 .= file_get_contents( "/var/www/intra/client/images/mixed_preview/".$_GET["layout"].".svg" );
	$result2 .= "</div>";
	
	$pub = sql_aget( "publications", "id='".$data["pubid"]."'", "*" );
	
	for( $i = 0; $i < $db; $i++ ) {
		$result .= "<div class='detailWindow ".$i."_window' style='display: none;'>";
			$result .= "<form id='".$i."_subForm' method='post' action=''>";
		
			$result .= "<div class='panelTitle'>".ucfirst( $_GET["data"] )." Article</div>";
			$result .= "<div class='panelControl' style='width: 450px !important; min-width: 450px !important;'>";
			
			$result .= "<input type='hidden' id='m_pubid' name='m_pubid' value='".$data["pubid"]."'>";
			$result .= "<input type='hidden' id='m_aname' name='m_aname' value='".$data["aname"]."'>";
			$result .= "<input type='hidden' id='m_slots' name='m_slots' value='".$data["slots"]."'>";
			$result .= "<input type='hidden' id='m_template' name='m_template' value='".$_GET["layout"]."'>";
			$result .= "<input type='hidden' id='m_position' name='m_position' value='".$i."'>";
			
			$result .= "<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>";
				$result .= "<tbody>";
					$result .= "<tr class='planner_table_row planner_row_1'>";						 
						$result .= "<td align='left' height='23px' colspan='100' style='padding-left: 0px;'>";
							$result .= "Status";
							$result .= "<select name='m_status' id='m_status'>";
								$status = array( "defined"=>"Defined", "progress"=>"In progress", "waiting"=>"Waiting", "finished"=>"Finished", "error"=>"Error" );
								
								foreach( $status as $key=>$value ) {
									$result .= '<option '.( $key == $data["a_status"] ? "selected" : "" ).' value="'.$key.'">'.$value.'</option>';
									}
							$result .= "</select>";
						$result .= "</td>";
					$result .= "</tr>";
					
					$result .= "<tr class='planner_table_row planner_row_2'>";
						$result .= "<td align='left' height='23px' colspan='8'>";
							$result .= "Title";
							$result .= "<input type='text' autocomplete='off' id='m_name' name='m_name' style='width: 250px;' value='".$data["name"]."'>";
						$result .= "</td>";
					$result .= "</tr>";
		
					$result .= "<tr class='planner_table_row planner_row_3'>";
						$result .= "<td id='atype_box' align='left' height='23px' colspan='6'>";
							$result .= "Article Type";
							$result .= "<select id='atype' name='atype'>";
								$types = sql_aget( "flatplan_articletypes", "pub_id='".$data["pubid"]."' order by id ASC", "*" );	
								
								for( $x = 0; $x < count( $types ); $x++ ) {
									$result .= "<option ".( $data["atype"] == $types[$x]["id"] ? "selected" : "" )." value='".$types[$x]["id"]."'>".$types[$x]["name"]."</option>";
									}
							$result .= "</select>";
						$result .= "</td>";
		
						$result .= "<td id='workerID_box' align='left' height='23px' colspan='4' style='padding-left: 0px;'>";
							$result .= "Designer";
							$result .= "<select name='workerID' id='workerID'>";
								$result .= "<option value='0'>---------------</option>";

								$users = array();
								$array = sql_aget( "accounts", "publisher='".$pub[0]["publisher_id"]."' AND `group`='6' order by full_name ASC", "*" );
								
								for( $x = 0; $x < count( $array ); $x++ ) {
									$temp = explode( ",", $array[$x]["showMagazines"] );
									if( in_array( $pub[0]["magazine_id"], $temp ) ) {
										$users[] = $array[$x];
										}
									}
								for( $x = 0; $x < count( $users ); $x++ ) {
									$result .= "<option ".( $users[$x]["id"] == $data["workerID"] ? "selected" : "" )." value='".$users[$x]["id"]."'>".$users[$x]["full_name"]."</option>";
									}			
							$result .= "</select>";
						$result .= "</td>";
					$result .= "</tr>";
					
					$result .= "<tr class='planner_table_row planner_row_4'>";
						$result .= "<td align='left' height='23px' colspan='6'>";
							$result .= "Projected Time to Complete ".$time." mins";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='4'>";
							$result .= "Time Spent <input type='text' name='tspent' value='".$data["tspent"]."' style='width: 30px;'> mins";
						$result .= "</td>";
					$result .= "</tr>";
		
					$result .= "<tr class='planner_table_row planner_row_5'>";
						$result .= "<td align='left' height='23px' colspan='2'>Required Assets</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "Text";
							$result .= "<input onclick=\"checkAvailable( 'text' )\" type='checkbox' name='r_text' id='r_text' value='1' ".( $data["r_text"] == "1" ? "checked" : "" )." >";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "Image";
							$result .= "<input onclick=\"checkAvailable( 'image' )\" type='checkbox' name='r_image' id='r_image' value='1' ".( $data["r_image"] == "1" ? "checked" : "" ).">";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "Other";
							$result .= "<input onclick=\"checkAvailable( 'other' )\" type='checkbox' name='r_other' id='r_other' value='1' ".( $data["r_other"] == "1" ? "checked" : "" ).">";
						$result .= "</td>";
					$result .= "</tr>";
		
					$result .= "<tr class='planner_table_row planner_row_6'>";
						$result .= "<td align='left' height='23px' colspan='2'>Available Assets</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "<div id='a_text'>";
								$result .= "Text";
								$result .= "<input type='checkbox' name='have_text' id='have_text' value='1' ".( $data["have_text"] == "1" ? "checked" : "" ).">";
							$result .= "</div>";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "<div id='a_image'>";
								$result .= "Image";
								$result .= "<input type='checkbox' name='have_image' id='have_image' value='1' ".( $data["have_image"] == "1" ? "checked" : "" ).">";
							$result .= "</div>";
						$result .= "</td>";
						$result .= "<td align='left' height='23px' colspan='2'>";
							$result .= "<div id='a_other'>";
								$result .= "Other";
								$result .= "<input type='checkbox' name='have_other' id='have_other	' value='1' ".( $data["have_other"] == "1" ? "checked" : "" ).">";
							$result .= "</div>";
						$result .= "</td>";
					$result .= "</tr>";
		
					$result .= "<tr class='planner_table_row planner_row_7'>";
						$result .= "<td align='left' height='23px' colspan='10'>Remarks</td>";
					$result .= "</tr>";
					$result .= "<tr class='planner_table_row planner_row_8'>";
						$result .= "<td align='left' height='23px' colspan='10'>";
							$result .= "<textarea name='remark' id='remark' style='resize: none; width: 444px; height: 60px;'>".stripslashes( $data["remark"] )."</textarea>";
						$result .= "</td>";
					$result .= "</tr>";	
				$result .= "</tbody>";
			$result .= "</table>";
		
		if( $_GET["data"] !== "create" ) {
			$result .= "<table id='assets' class='panelTable' cellspacing='0' cellpadding='0' style='margin-top: 20px;'>";
				$result .= "<thead>";
					$result .= "<tr>";
						$result .= "<td colspan='3' style='background-color: transparent; color: #FFF; padding-bottom: 0px;'>Assets</td>";
					$result .= "</tr>";
				$result .= "</thead>";
				
				$result .= "<tbody id='fileupload_uploaded'>";
					$files = sql_aget( "flatplan_files", "articlename='".$articles[0]["name"]."' ORDER BY 'origname' ASC", "*" );		
					for( $x = 0; $x < count( $files ); $x++ ) {
						$txt .= "<tr>";
							$txt .= "<td colspan='2' style='padding-left: 0px; font-size: 14px; padding-top: 2px; color: #CCC'>".$files[$x]["origname"]."</td>";
							$txt .= "<td align='right' style='padding-left: 0px; padding-right: 3px; padding-top: 2px; font-size: 16px;'>
										<span onclick='fpfiledownload( \"".$files[$x]["id"]."\" )' style='cursor: pointer;'><i class='fas fa-download'></i></span>
										<span onclick='fpfileremove( \"".$files[$x]["id"]."\", \"".$files[$x]["origname"]."\" )' style='cursor: pointer;'><i class='far fa-times-circle' style='color: #D22A33;'></i></span>
									 </td>";
							$txt .= "</tr>";
						}
						
					$result .= $txt;
				$result .= "</tbody>";
			$result .= "</table>";
			
			$result .= "<table id='assetsTable' class='panelTable' cellspacing='0' cellpadding='0'>";
				$result .= "<tr>";
					$result .= "<td style='padding-top: 7px;'>";
						$result .= "<span id='select-file'>";
							$result .= "<i onclick=\"$('#afile').click()\" class='fas fa-upload' style='font-size: 16px; cursor: pointer; margin-right: 7px;'></i>";
							$result .= "<span id='targetfile' style='font-size: 20px; display: none;'>";
								$result .= "<span id='currentFileName' style='font-size: 13px; margin-right: 5px;'></span><i onclick='window.parent.frames[0].fileUpload()' class='fas fa-file-upload' style='cursor: pointer;'></i>";
							$result .= "</span>";
							$result .= "<input onchange='currentFile()' type='file' id='afile' name='afile' style='visibility: hidden;'>";
						$result .= "</span>";
						$result .= "<span id='selected-file' style='display: none;'></span>";
						
						
					$result .= "</td>";
					$result .= "<td align='right' class='fp-up-box' style='visibility: hidden; padding-top: 7px;'>";
						$result .= "<div style='width: 150px; border: 1px solid #CCC; height: 15px; text-align: left;'>";
							$result .= "<div class='fup-bar' style='background-color: #FFF; height: 100%; width: 0px;'></div>";
						$result .= "</div>";
					$result .= "</td>";
					
					$result .= "<td algin='left' class='fp-up-box' style='visibility: hidden; padding-left: 5px; padding-top: 7px;'>";
						$result .= "<div class='fup-percent' style='width: 40px;'></div>";
					$result .= "</td>";
				$result .= "</tr>";
			$result .= "</table>";
			}
			
			$result .= "<table class='panelTable' cellspacing='0' cellpadding='0'>";
				$result .= "<tr>";
					$result .= "<td colspan='10' align='center' style='padding-top: 10px;'>";
						$result .= "<div onclick=\"closeMixedSettings( ".$i." )\" style='margin-left: 2px; float: inherit; display: inline-block;' class='panelButton'>Cancel</div>";
						$result .= "<div id='plannersave' onclick='closeMixedSettings( ".$i." )' style='margin-left: 20px; float: inherit; display: inline-block;' class='panelButton'>Save</div>";
					$result .= "</td>";
				$result .= "</tr>";
			$result .= "</table>";					
		
								
			$result .= "</div>";
			
			$result .= "</form>";
		$result .= "</div>";
		}
		
	$result = array( $result2, $result );
	}

if( $_GET['op'] == 'loadlayout' ) {
	$svg = load_dir_files( "/var/www/intra/client/images/mixed_preview", $_GET["parts"]."_" );
	
	for( $i = 0; $i < count( $svg ); $i++ ) {
		$file = file_get_contents( "/var/www/intra/client/images/mixed_preview/".$svg[$i] );
		
		$result .= "<div class='mixedLayout_tile'>
				<div><img src='images/mixed_preview/".$svg[$i]."'></div>
				<div class='mixedLayout_cbox'><input class='mixedLayout_radio' type='radio' name='mixed_type_val' value='".substr( $svg[$i], 0, -4 )."'></div>
			</div>";
			
		
		}
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