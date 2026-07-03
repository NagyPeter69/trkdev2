<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once('../../engine/engine.php');
	include_once('../lang/hu.php');
	
	function addingNumber( $szamok ) {
		$szamok = str_split( $szamok );
		$osszeg = 0;
		
		foreach( $szamok as $szam ) {
			$osszeg += intval( $szam );
			}
		
		return $osszeg;
		}

	function calculateSize( $pageInfo, $magazine, $issue ) {
		$dir = sql_get( 'packages', 'id="'.$pageInfo[1].'"', 'name, directory, id' );
		$file = $dir[0][0]."/".str_pad( $pageInfo[5], 3, '0', STR_PAD_LEFT)."_".$dir[0][2]."_preview.jpg";
		$path = "../packages/".$magazine."/".$issue;
		$w = 90;
		$h = 110;
		if( $pageInfo[0] != "" && is_file( $path."/".$file ) ) {
			list( $w2, $h2 ) = getimagesize( $path."/".$file );
			if( $w2 >= 90 ) {
				$percent = $w/$w2*100;
				$h = intval( $h2/100*$percent );
				}
			}
		
		return array( $w, $h );
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
	
	function drawPage( $id, $page, $class, $i, $pageType = 'normal' ) {
		global $holderWidth, $fPages2, $alterP, $alters, $rPalette, $gPalette, $bPalette, $magazine, $issue, $sizes, $path;
		
		list( $w, $h ) = $sizes;

		$txt = "";
		if( $pageType == "normal" ) {
			$typeSelect = 'type!="PRE" AND type!="PSTR"';
			$acceptType = array( 'ad', 'magazine' );
			}
		else {
			$typeSelect = 'type="'.$pageType.'"';
			$acceptType = array( 'PRE', 'PSTR' );
			}
		
		$fPage[0] = $fPages2[ $page ];
		if( $page == 0 ) {
			return "<div style='float: left;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='width: ".($w)."px; height: ".($h+30)."px;'>&nbsp;</div></div>";
			}
		if( $page > intval( $issue[0][6] ) and $fPage[0][0] == "" ) {
			return "<div style='float: right;'><div class='".$class."_pagenr pagenr'>&nbsp;</div><div style='width: ".($w+2)."px; '>&nbsp;</div></div>";
			}
					
		if( $fPage[0][6] == "ad" ) {
			$file = "_ads/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_ad_preview.jpg";
			$secBg = "background: rgb( 242, 92, 36 ) !important;";
			$textColor = "FFFFFF";
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
					$link = "?page=flatplan_preview&id=".$id."&p=".$lPage;
				else
					$link = "?page=flatplan_preview&alter=".$_GET['opt']."&id=".$id."&p=".$lPage;
				}
			else {
				if( $pageType == "normal" )
					$link = "?page=flatplan_preview&type=ad&id=".$id."&p=".$lPage;
				else
					$link = "?page=flatplan_preview&alter=".$_GET['opt']."&type=ad&id=".$id."&p=".$lPage;
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
			$w2 = $fPage[0][9] * 84;
			$w = $w2;
			}
		$scale = "width: ".$w."px; height: ".$h."px;";
		$page_thumb = "cursor: pointer; background-repeat:no-repeat; background-size:".$w."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file.");";
		if( $fPage[0][9] < 2 && $page > 0 ) {
			if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file ) ) {
				list( $w2, $h2 ) = getimagesize( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$file );
				if( $w2 < 90 ) {
					$percent = $w2 / $w * 100;
					$h2 = intval( $h / 100 * $percent );

					$page_thumb = "cursor: pointer; background-repeat:no-repeat; background-size:".$w2."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$file.");";
					}
				}
			}

		$holderWidth += $w;
		if( intval( $page )%2 != "" ) {
			$txt .= "<div style='float: left; height: ".($h+32)."px; width: ".($holderWidth+3)."px;'></div>";
			$holderWidth = 0;
			}

		switch( $_GET['filter'] ) {
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
					$txt .= "<div class='".$class."_page' style='position: absolute; left: 0px; z-index: 2; border-right: 1px solid #808080;'>";	
					}
				else {
					$txt .= "<div id='".$page."_selector' class='selectLeft' style='opacity: 0; width:".($w+1)."px; height: ".($h+31)."px;'></div>";
					$txt .= "<div class='".$class."_page' style='opacity: 0.15; position: absolute; left: 0px; z-index: 2; border-right: 1px solid #808080;'>";					
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
				$txt .= "<div style='border-left: 1px solid #808080; position: relative; width: ".$w."px; height: ".($h+31)."px;'>";
			else
				$txt .= "<div style='position: relative; width: ".$w."px; height: ".($h+31)."px;'>";
					
				if( $alterP[$page]!= "" && in_array( $fPage[0][6], $acceptType ) )
					$alterPage = sql_get( 'pageinfo', $typeSelect.' AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!=""', '*' );
				
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
							$alterFile = "_ads/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."ad_preview.jpg";
							$alterTextColor = "FFFFFF";
							}
						else {
							$alterTextColor = "000000";
							$pub = sql_get( "packages", "id='".$alterPage[$alt][1]."' LIMIT 1", "directory" );
							if( $pageType == "normal" )
								$alterFile = $pub[0][0]."/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$alterPage[$alt][1]."_".$alterPage[$alt][8]."preview.jpg";
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
								
						if( is_file( "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$alterFile ) ) {
							if( $pageType == "normal" )
								$altLink = "?page=flatplan_preview&chk=".$page."&id=".$id."&p=".$lPage."&tag=".$alterPage[$alt][8];
							else
								$altLink = "?page=flatplan_preview&chk=".$page."&alter=".$_GET['opt']."&id=".$id."&p=".$lPage."&tag=".$alterPage[$alt][8];
						
							$alterThumb = "cursor: pointer; background-repeat:no-repeat; background-size:".$w."px ".$h."px; background-image: url(packages/".$magazine[0][3]."/".$issue[0][10]."/".$alterFile.");";
							}
						else {
							$altLink = "";
							$alterThumb = "background-color: #FFFFFF !important;";
							}
					
						$txt .= "<div alter='".($alt+1)."' id='".$alterPage[$alt][1]."_".$page."' item='".$alterPage[$alt][1]."' page='".$page."' class='".$class."_pagenr pagenr checking2' style='z-index: ".(1000-($alt+1))."; width: ".($w)."px; ".$secBg." color: #".$alterTextColor."'>";
						if( $alterPage[$alt][3] != "" )
							$altVersion = "v".$alterPage[$alt][3];
						if( $class == 'right' ) {
							$txt .= "<div style='pointer-events: none; float:left; margin-left: 2px;'>".$altVersion."</div><div style='pointer-events: none; float:right; margin-right: 2px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div>";
							}
						elseif( $class == 'left' ) {
							$txt .= "<div style='pointer-events: none; float:left; margin-left: 2px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 2px;'>".$altVersion."</div>";
							}
							$txt .= "<div style='clear:both;'></div>";
						$txt .= "</div>";
						$txt .= "<div  id='".$page."_athumb' class='thumb' alter='".($alt+1)."' page='".$page."' ";
						if( $altLink != '' )
							$txt .= "double='".$altLink."'";
						$txt .= " style='position: absolute; z-index: ".(1000-($alt+1))."; top: 17px; ".$scale." ".$alterThumb."'>";
						$txt .= "</div>";
					
						$txt .= "<div alter='".($alt+1)."' page='".$page."' style='position: absolute; z-index: ".(1000-($alt+1))."; bottom: 0px; width: ".$w."px;' class='page_footer state_".$alterPage[$alt][4]."'>";
							if( $class == 'right' ) {
								$txt .= "<div id='".$fPage[0][1]."_alterName' style='font-size: 12px; line-height: 16px; color: #000000; float:left; margin-top: 1px; margin-left: 3px;'>".substr( $alterPage[$alt][8], 0, -1 )."</div>";
								$txt .= "<div style='float: right; margin-top: 4px; margin-right: 3px;'>".$alterCommentMark."</div>";
								}
							elseif( $class == 'left' ) {
								$txt .= "<div style='float: left; margin-top: 4px; margin-left: 3px;'>".$alterCommentMark."</div>";
								$txt .= "<div id='".$fPage[0][1]."_alterName' style='font-size: 12px; line-height: 16px; float:right; color: #000000; margin-top: 1px; margin-right: 3px;'>".substr( $alterPage[$alt][8], 0, -1 )."</div>";
								}
							$txt .= "<div style='margin-top:-3px;'><input ";
							if( $link == '' )
								$txt .= 'disabled';
							$txt .= " type='checkbox' item='".$alterPage[$alt][1]."' name='pageSelector[]' value='".$page."' style='display: none;'>";
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
					}
				elseif( $class == 'left' ) {
					$txt .= "<div style='pointer-events: none; float:left; margin-left: 4px;'>".str_pad( $page, 3, '0', STR_PAD_LEFT)."</div><div style='pointer-events: none; float:right; margin-right: 4px;'>".$version."</div>";
					$commentPlace = "<div style='float: left; margin-top: 3px; margin-left: 3px;'>".$commentMark."</div>";
					}
		
				$txt .= "</div><div id='".$page."_thumb' class='thumb' alter='0' page='".$page."'";
				if( $link != '' )
					$txt .= "double='".$link."'";
				$txt .= " style='position: absolute; z-index: 1000; top: 17px; ".$scale." ".$page_thumb."'></div>";

				$txt .= "<div alter='0' page='".$page."' style='position: absolute; z-index: 1000; bottom: 0px; width: ".$w."px;' class='page_footer state_".$fPage[0][4]."'>";
				$txt .= "<div id='".$fPage[0][1]."_alterName' style='float:left; margin-left: 3px;'></div>";
				$txt .= $commentPlace;				
				$txt .= "<input ";
				if( $link == '' )
					$txt .= 'disabled';
				$txt .= " type='checkbox' item='".$fPage[0][1]."' name='pageSelector[]' value='".$page."' style='display: none;'></div></div></div>";
		
		return $txt;	
		}
		
	if( $_GET['op'] == 'loadPagePair' ) {
		$alters = array();
		$holderWidth = 0;
		$text = '';

		$myPublisher = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher' );	
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'" LIMIT 1', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'" LIMIT 1', '*' );		
		$path = "../packages/".$magazine[0][3]."/".$issue[0][10];

		if( $_GET["opt"] == "" ) {
			$typeSelect = 'type!="PRE" AND type!="PSTR"';
			$acceptType = array( 'ad', 'magazine' );
			}
		else {
			$typeSelect = 'type="'.strtoupper( $_GET["opt"] ).'"';
			$acceptType = array( 'PRE', 'PSTR' );
			}
				
		$bPalette = colorGenerate( 'blue' );
		$bPalette = colorGenerate( 'red', $bPalette );
		$bPalette = colorGenerate( 'green', $bPalette );
		$alterP = array();
		$alterSql = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state!="" ORDER BY `page` ASC', 'page, type' );
		foreach( $alterSql as $alterRow ) {
			$alterP[ $alterRow[0] ] = $alterRow[1];
			}
					
		$fPages2 = array();	
		$fPagesSql = sql_get( 'pageinfo', $typeSelect.' AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="" ', '*' );
		foreach( $fPagesSql as $fP ) {
			$fPages2[ intval($fP[5]) ] = $fP;
			}
		
		$sizes = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="" LIMIT 1', '*' );
		$sizes = calculateSize( $sizes[0], $magazine[0][3], $issue[0][10] );
		$row = intval( intval($_GET['maxwidth'] )/229 );
		$divWidth = $row*229;
			
		if( $_GET['opt'] == '' ) {		
			$length = intval( $issue[0][6] );
			$counter = 1;
			$i = 0;
			while( $i <= $length ) {
				$text .= "<div style='position: relative; float: left; margin: 5px; margin-bottom: 6px;'>";
					$text .= drawPage( $_GET['id'], $i, 'left', $i );
					$text .= drawPage( $_GET['id'], ($i+1), 'right', $i );
				$text .= "</div>";
				$i += 2;
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
					if( $_GET['opt'] == 'pstr' ) {
						$text .= "<div style='position: relative; float: left; margin: 5px; width: 183px;'>";
							$text .= drawPage( $_GET['id'], $i, 'left', $i, "PSTR" );
							if( $i < $end )
								$text .= drawPage( $_GET['id'], ($i+1), $i, 'right', "PSTR" ); 
						$text .= "</div>";
						}
					else {
						$text .= "<div style='position: relative; float: left; margin: 5px; width: 183px;'>";
							$text .= drawPage( $_GET['id'], $i, 'left', $i,  "PRE" );
							if( $i < $end )
								$text .= drawPage( $_GET['id'], ($i+1), 'right', $i, "PRE" ); 
						$text .= "</div>";
						}
					$i += 2;
					}
				}
			else {
				$text = "<div style='text-align: center;'><br>Jelenleg még nincs feltöltött oldal.</div>";
				}
			}
			
		$result[0] = $text;
		$result[1] = $alters;
		}
	
	
print json_encode( $result );
	
?>