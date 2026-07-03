<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../../engine/connect.php' );
	include_once('../../lang/hu.php');

	function colorGenerate( $first ) {
		$return = array();
		$f = 220;
		while( $f >= 200 ) {
			$s = 210;
			while( $s >= 150 ) {
				$t = 210;
				while( $t >= 150 ) {
					switch( $first ) {
						case 'red':
							$return[] = str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT);
							break;
						case 'green':
							$return[] = str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT);
							break;
						case 'blue':
							$return[] = str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT);
							break;
						}
					$t-=20;
					}
				$s-=20;
				}
			$f-=3;
			}
		return $return;
		}

	function sql_get( $table, $condition, $collect ) {
			$datas = array();
			$command = 'SELECT '.$collect.' FROM '.$table.'';
		
			if( $condition != '' )
				$command .= ' WHERE '.$condition.'';

			$command = mysql_query( $command );
			
			if( mysql_num_rows( $command ) > 1 ) {
				while( $row = @mysql_fetch_row( $command ) ) {
					$datas[] = $row;
					}
				}
			else {
				$datas[] = @mysql_fetch_row( $command );
				}
			return $datas; 
			}
	
	function calculateSize( $pageInfo, $magazine, $issue ) {
		$dir = sql_get( 'packages', 'id="'.$pageInfo[1].'"', 'name, directory, id' );
		$file = $dir[0][0]."/".str_pad( $pageInfo[5], 3, '0', STR_PAD_LEFT)."_".$dir[0][2]."_preview.jpg";
		$path = "../../packages/".$magazine."/".$issue;
		$w = 90;
		
		if( $pageInfo[0] != "" ) {
			list( $w2, $h2 ) = getimagesize( $path."/".$file );
			if( $w2 >= 90 ) {
				$percent = $w/$w2*100;
				$h = intval( $h2/100*$percent );
				}
			}
		else {
			$h = 110;
			}
		
		return array( $w, $h );
		}
	
	function drawPageBlank( $id, $page, $class, $i, $pageType = 'normal' ) {
		global $alters, $rPalette, $gPalette, $bPalette, $sizes, $magazine, $issue;
		$txt = "";
		$path = "../packages/".$magazine[0][3]."/".$issue[0][10];
		$pages = array();
		$files = array();
		$pack_id = array();
	
		if( $pageType == "normal" ) {
			$fPage = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state=""', 'id, pack_id, type' );
			}
		else {
			$fPage = sql_get( 'pageinfo', 'type="'.$pageType.'" AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state=""', 'id, pack_id, type' );
			}
		list( $w, $h ) = $sizes;
			
		if( $fPage[0][2] == "ad" ) {
			$secBg = "background: #E09948 !important;";
			}
		else {
			$tempPage = sql_get( 'packages', 'id="'.$fPage[0][1].'"', 'id' );
			
			if( $tempPage[0][0] == "" ) {
				$tempPack = sql_get( 'packages', 'publication_id="'.$issue[0][0].'" AND starting_page != "" ORDER BY `id` ASC', '*' );
				foreach( $tempPack as $Pack ) {
					$tPages = explode( "-", $Pack[3] );
					$start = $tPages[0];
					if( $tPages[1] != "" )
						$end = $tPages[1];
					else 
						$end = $tPages[0];
					
					if( $page >= $start && $page <= $end ) {
						$tempPage[0][0] = $Pack[0];
						break;
						}
					}
				}

			if( $tempPage[0][0] != "" ) {
				if( $tempPage[0][0]%5 == 0 ) {
					$place = $tempPage[0][0]%5;
					$secBg = "background: #".$gPalette[$place]." !important;";
					}
				elseif( $tempPage[0][0]%3 == 0 ) {
					$place = $tempPage[0][0]%3;
					$secBg = "background: #".$rPalette[$place]." !important;";
					}
				elseif( $tempPage[0][0]%4 == 0 ) {
					$place = $tempPage[0][0]%4;
					$secBg = "background: #".$gPalette[$place]." !important;";
					}
				elseif( $tempPage[0][0]%6 == 0 ) {
					$place = $tempPage[0][0]%6;
					$secBg = "background: #".$rPalette[$place]." !important;";
					}
				elseif( $tempPage[0][0]%7 == 0 ) {
					$place = $tempPage[0][0]%7;
					$secBg = "background: #".$rPalette[$place]." !important;";
					}
				elseif( $tempPage[0][0]%22 == 0 ) {
					$place = $tempPage[0][0]%22;
					$secBg = "background: #".$gPalette[$place]." !important;";
					}
				else {
					$place = intval( ( $page%$tempPage[0][0] )/2 );
					$secBg = "background: #".$bPalette[$place]." !important;";
					}
				}
			}

		if( $page == 0 ) {
			return "<div style='float: left;'>
						<div class='".$class."_pagenr pagenr'>&nbsp;</div>
						<div style='width: ".$w."px; height: ".($h+2)."px;'>&nbsp;</div>
					</div>";
			}
		if( $page > intval( $issue[0][6] ) and $fPage[0][0] == "" ) {
			return "<div style='float: right;'>
						<div class='".$class."_pagenr pagenr'>&nbsp;</div>
						<div style='width: ".$w."px; '>&nbsp;</div>
					</div>";
			}
		
		$txt .= "<div id='".$page."_box' class='".$class."_page' style='float: left;";	
		if( $class == 'left' )
			$txt .= " border-right: 1px dashed #ADADAD;";
		$txt .= "'>";

			if( $page == 1 ) {
				$txt .= "<div style='border-left: 1px solid #ADADAD; position: relative; width: ".$w."px; height: ".($h+30)."px;'>";
				}
			else {
				$txt .= "<div style='position: relative; width: ".$w."px; height: ".($h+30)."px;'>";
				}

			$txt .= "<input type='hidden' id='".$page."_current' name='".$page."_current' value='0'>";
			$txt .= "<input type='hidden' id='".$page."_max' name='".$page."_max' value='0'>";

			$txt .= "<div alter='0' id='".$fPage[0][1]."_".$page."' item='".$fPage[0][1]."' page='".$page."' class='".$class."_pagenr pagenr checking2' style='z-index: 1000; width: ".($w-2)."px; ".$secBg."'>";

			if( $fPage[0][3] != "" )
				$version = "v".$fPage[0][3];
			if( $class == 'right' ) {
				$txt .= $version."&nbsp;&nbsp;&nbsp;".$page."</div>";
				}
			if( $class == 'left' ) {
				$txt .= "".$page."&nbsp;&nbsp;&nbsp;".$version."</div>";
				}
		
			$txt .= "<div alter='0' page='".$page."'";
			if( $link != '' )
				$txt .= "onclick='window.location.href=\"".$link."\"'";
			$txt .= " style='position: absolute; z-index: 1000; top: 15px; ".$scale." ".$page_thumb."'>";
			$txt .= "</div>";

			$txt .= "<div alter='0' page='".$page."' style='position: absolute; z-index: 1000; bottom: 0px; width: ".$w."px;' class='page_footer state_".$fPage[0][4]."'>";
				$txt .= "<div style='margin-top:-3px;'><input ";
				if( $link == '' )
					$txt .= 'disabled';
				$txt .= " type='checkbox' item='".$fPage[0][1]."' name='pageSelector[]' value='".$page."'>";
			$txt .= "</div></div>";
			$txt .= "</div>";
		$txt .= "</div>";


		return $txt;
		}

	if( $_GET['op'] == 'loadPagePairBlank' ) {
		$alters = array();
		$rPalette = colorGenerate( 'red' );
		$gPalette = colorGenerate( 'green' );
		$bPalette = colorGenerate( 'blue' );
		
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$maxPage = intval( $issue[0][6] ) + 1;
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
		$text =  '';
		$row = intval( intval($_GET['maxwidth'] )/229 );
		$divWidth = $row*229;
		$sizes = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="" LIMIT 1', '*' );
		$sizes = calculateSize( $sizes[0], $magazine[0][3], $issue[0][10] );


		if( $_GET['opt'] == '' ) {		
			$length = intval( $issue[0][6] );
			$counter = 1;
			$i = 0;
			while( $i <= $length ) {
				$text .= "<div style='float: left; margin-left: 8px; margin-right: 8px; margin-bottom: 20px;'>";
					$text .= drawPageBlank( $_GET['id'], $i, 'left', $i );
					$text .= drawPageBlank( $_GET['id'], ($i+1), 'right', $i ); 
				$text .= "</div>";
				$i += 2;
				}
			}
		else {
			$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
			$path = "../packages/".$magazine[0][3]."/".$issue[0][10];
			
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
						$text .= "<div style='float: left; margin-left: 8px; margin-right: 8px; margin-bottom: 20px;'>";
							$text .= drawPageBlank( $_GET['id'], $i, 'left', $i, "PRE" );
							if( $i < $end )
								$text .= drawPageBlank( $_GET['id'], ($i+1), $i, 'right', "PRE" ); 
						$text .= "</div>";
						}
					else {
						$text .= "<div style='float: left; margin-left: 8px; margin-right: 8px; margin-bottom: 20px;'>";
							$text .= drawPageBlank( $_GET['id'], $i, 'left', $i,  "PRE" );
							if( $i < $end )
								$text .= drawPageBlank( $_GET['id'], ($i+1), 'right', $i, "PRE" ); 
						$text .= "</div>";
						}
					$i += 2;
					}
				}
			else {
				$text = "<br>Jelenleg még nincs feltöltött oldal.";
				}
			}
		$result[0] = $text;
		$result[1] = $maxPage;		
		}
	
print json_encode( $result );
	
?>