<?PHP

require_once( "/var/www/html/engine/r3client.php" );

$status = $_POST["result"];
$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];
$description = $_POST["description"];
$event = $_POST["event"];

$file = $_POST["fileName"].".pdf";
//$file = "026_ABC1802.pdf";
$go = 1;

if( $go ) {
	$pageState = $_POST["pageState"];
	$pageVersion = $_POST["pageVersion"];

	$handle = fopen( "pageversion.txt", 'a+');
	if( $handle === false ) {
		return false;
		}

	if( fwrite( $handle, $pageVersion . "\n" ) === false ) {
		return false;
		}
	fclose( $handle );
	$handle = fopen( "pagestate.txt", 'a+');
	if( $handle === false ) {
		return false;
		}

	if( fwrite( $handle, $pageState . "\n" ) === false ) {
		return false;
		}
	fclose( $handle );
	
	$pageType = $_POST["pageType"];
	$description = $_POST["description"];
	//$description = letter_change( $description );
	$pageNum = intval( $_POST["pageNum"] );
	$page = intval( $_POST["pageNum"] );
	$pageWidth = intval( $_POST["pageWidth"] );
	if( $event == 'page_thumbnail' ) $ext = "jpg";
	if( $event == 'page_pdf' ) $ext = "pdf";

	$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
	$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );

	$name = nameCalculator2( $_POST );
	$result = searchArticlePost( $_POST, $name, $page );
	var_dump( $result );
	//die();
	if( count( $result ) == 3 ) {
		$hold = $result[0]; $pack_id = $result[1]; $type = $result[2];
		if( ( $pack_id == NULL or $pack_id == 0 ) and $event == 'page_pdf' ) {
			$page = searchRange_array( $_POST );
			$checker = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
			echo "check: ".$checker[0][0]."<br>";
			if( $checker[0][0] == "" ) {
				$names = array( 'publication_id', 'name', 'starting_page', 'directory', 'acquired_name', 'status_changed' );
				$values = array( $p_id[0][0], $name, $page, $name, str_replace( "'", "", $description ), time() );
		
				if( $page == "" )
					$type = "alter";
				else 
					$type = "magazine";
		
				echo "<pre>";
				var_dump( $values );
				echo "</pre>";
				$pack_id = sql_add( 'packages', $names, $values );
				echo "<br>pack:".$pack_id."<br>";
				$oldmask = umask(0);
				@mkdir("../packages/".$jcode, 0777);
				@mkdir("../packages/".$jcode."/".$issue, 0777);
				@mkdir("../packages/".$jcode."/".$issue."/".$name, 0777);
				umask($oldmask);
				}
			else {
				$start = 0; $end = 0;
				$cPages = explode( "-", $checker[0][3] );
				if( $checker[0][3] == "" ) {
					$newPages = $page;
					}
				else {
					if( $cPages[1] != "" ) {
						if( $page <= $cPages[0] ) {
							$start = $page;
							$end = $cPages[1];
							}
						else {
							$start = $cPages[0];
							$end = $page;
							}
						$newPages = $start."-".$end;	
						}
					else {
						if( $page <= intval( $cPages[0] ) ) {
							$start = $page;
							$end = intval( $cPages[0] );
							}
						else {
							$start = intval( $cPages[0] );
							$end = $page;
							}
						if( gettype( $end ) != "array" )
							$newPages = $start."-".$end;
						else 
							$newPages = $start;
						}
					}

				if( $newPages != "" ) {
					sql_update( 'packages', 'starting_page="'.$newPages.'"', 'id="'.$checker[0][0].'"' );	
					}
				$type = "magazine";
				}
			}
	
		if( $hold == 0 && $pack_id > 0 ) {
			if( $type == "ad" )
				$packages = sql_get( 'ads', 'id="'.$pack_id.'"', '*' );
			else
				$packages = sql_get( 'packages', 'id="'.$pack_id.'"', '*' );
			$names = array( "pack_id", "issue", "version", "status", "page", "code", "state" , "type" );
	
			if( $pageState == "FIN" ) $s = 1;
			else $s = 0;
			if( $pageVersion != "-baseversion-" and $pageVersion != "" ) $tag = $pageVersion."_";
			else $tag = "";
						
			$values = array( $pack_id, $issue, '1', $s, $page, $jcode, $tag );
			$old = substr( $file, 0, -4 ).'.'.$ext;
			$path = TRKPATH.'/packages/'.$jcode.'/'.$issue;
			if( !is_dir( TRKPATH.'/packages/'.$jcode ) ) {
				$oldmask = umask(0);
				mkdir( TRKPATH.'/packages/'.$jcode, 0777 );
				umask($oldmask);							
				}
			if( !is_dir( $path ) ) {
				$oldmask = umask(0);
				mkdir( $path, 0777 );
				umask($oldmask);							
				}
			error_log( "type: ".$type );
			switch( $type ) {
				case "alter":
					$alter[1] = $pageType;
					$values[] = $alter[1];
					$subDir = '_'.$alter[1];

					if( !is_dir( $path.'/'.$subDir ) ) {
						$oldmask = umask(0);
						mkdir( $path.'/'.$subDir, 0777 );
						umask($oldmask);								
						}
					
					if( $pageState == "FIN" ) $subDir .= "/FIN";
					$filename = str_pad(intval( $pageNum ), 3, '0', STR_PAD_LEFT).'_'.$packages[0][0].'_'.$tag.'preview';
					$new = $path.'/'.$subDir.'/'.str_pad(intval( $pageNum ), 3, '0', STR_PAD_LEFT).'_'.$packages[0][0].'_'.$tag.'preview.'.$ext;
					break;
				case "ad":
					$values[] = "ad";
					$subDir = '_ads';

					if( !is_dir( $path.'/'.$subDir ) ) {
						$oldmask = umask(0);
						mkdir( $path.'/'.$subDir, 0777 );
						umask($oldmask);								
						}

					$filename = str_pad(intval( $pageNum ), 3, '0', STR_PAD_LEFT).'_'.$packages[0][0].'_'.$tag.'ad_preview';
					$new = $path.'/'.$subDir.'/'.str_pad(intval( $pageNum ), 3, '0', STR_PAD_LEFT).'_'.$packages[0][0].'_'.$tag.'ad_preview.'.$ext;
					break;
				case "magazine":
					$values[] = "magazine";
					$subDir = $packages[0][4];

					if( !is_dir( $path.'/'.$subDir ) ) {
						$oldmask = umask(0);
						mkdir( $path.'/'.$subDir, 0777 );
						umask($oldmask);								
						}

					if( $pageState == "FIN" ) $subDir .= "/FIN";
					$filename = str_pad(intval( $pageNum ), 3, '0', STR_PAD_LEFT).'_'.$packages[0][0].'_'.$tag.'preview';
					$new = $path.'/'.$subDir.'/'.str_pad(intval( $pageNum ), 3, '0', STR_PAD_LEFT).'_'.$packages[0][0].'_'.$tag.'preview.'.$ext;
					break;
				}
			
			if( !is_dir( $path.'/'.$subDir ) ) {
				$oldmask = umask(0);
				mkdir( $path.'/'.$subDir, 0777 );
				umask($oldmask);								
				}
			
			echo "subdir: ".$subDir;
			echo "<br><br>";
			var_dump( $values );
			$remove = array();
			$sql_remove = array();
			//die();
			if( $type == "alter" ) {
				$pageInfo = sql_get( 'pageinfo', 'type="'.$alter[1].'" AND code="'.$jcode.'" AND issue="'.$issue.'" AND page="'.$page.'"', '*' );
				}
			else {
				if( $pageState == "FIN" ) {
					$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" AND fin="1"', '*' );
					}
				else {
					$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" AND fin="0"', '*' );
					}
				}

			if( $type != "alter" && $tag == "" && $pageState != "FIN" ) {	
				$checker = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND code="'.$jcode.'" AND issue="'.$issue.'" AND pack_id!="'.$pack_id.'" AND page="'.$page.'" AND fin="0"', '*' ); 
				if( $checker[0][0] != "" ) {
					echo $check;
					$remove[] = fileRemove( $checker[0], $path, $ext );
					if( $checker[0][6] == "ad" and $event == 'page_pdf' ) {
						$sql_remove[count( $remove )-1] = $checker[0][0];
						}
					}
				else {
					if( $pageState == "FIN" ) {
						$checker = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND code="'.$jcode.'" AND issue="'.$issue.'" AND pack_id!="'.$pack_id.'" AND page="'.$page.'" AND fin="1"', '*' ); 
						if( $checker[0][0] != "" ) {
							echo $check;
							$remove[] = fileRemove( $checker[0], $path, $ext );
							if( $checker[0][6] == "ad" and $event == 'page_pdf' ) {
								$sql_remove[count( $remove )-1] = $checker[0][0];
								}
							}
						}
					else {
						if( $type == "ad" ) {
							$checker = sql_get( 'pageinfo', 'type="ad" AND code="'.$jcode.'" AND issue="'.$issue.'" AND pack_id="'.$pack_id.'"', '*' );
							for( $i = 0; $i < count( $checker ); $i++ ) {
								$ad = sql_get( 'ads', 'id="'.$checker[$i][1].'"', '*' );
								if( $ad[0][3] == "2/1" ) {
									$check = $page-$checker[$i][5];
									if( $check < -1 or $check > 1 ) {
										$remove[] = fileRemove( $checker[$i], $path, $ext );
										if( $event == 'page_pdf' )
											$sql_remove[count( $remove )-1] = $checker[$i][0];
										}
									}
								else {
									$remove[] = fileRemove( $checker[$i], $path, $ext );
									if( $event == 'page_pdf' )
										$sql_remove[count( $remove )-1] = $checker[$i][0];
									}
								}
							}
						}
					}
				}
			echo "<pre>";
			echo "<br>remove:<br>";
			var_dump( $remove );
			echo "<br>".$new;
			//die();
			for( $i = 0; $i < count( $remove ); $i++ ) {
				echo "ciklusba";
				if( is_file( $remove[$i] ) ) {
					unlink( $remove[$i] );
					if( $sql_remove[$i] != "" ) {
						$pageSQL = sql_get( "pageinfo", "id='".$sql_remove[$i]."'", "*" );
						sql_update( 'ads', 'uploaded=""', 'id="'.$pageSQL[0][1].'"' );
						$debug = "removing: ".$sql_remove[$i] . "\n";
						sql_delete( 'pageinfo', 'id="'.$sql_remove[$i].'"' );
						}
					}
				}
			//die();
			echo "event: ".$event;
			if( $event == 'page_pdf' ) {
				echo "<br><br>PageInfo nézés:";
				var_dump( $pageInfo );
				echo "pageinfo első elem:".$pageInfo[0][0];
				if( $pageInfo[0][0] != '' ) {
					echo "bent vagyok";

					echo "<br>sql update:<br>";
					echo 'version="'.( intval( $pageInfo[0][3] )+1 ).'", status="'.$s.'", pack_id="'.$pack_id.'", type="'.$type.'" view=""';
					if( $p_id[0][12] == "approved" ) {
						$enableApprove = approveMagazine( $p_id[0][0] );
						if( !$enableApprove ) {
							sql_update( 'publications', 'status="current"', 'id="'.$p_id[0][0].'"' );
							}
						}
					
					if( !is_dir( $path."/_old" ) ) {
						$oldmask = umask(0);
						mkdir( $path."/_old", 0777 );
						umask($oldmask);								
						}

					if( !is_dir( $path."/_old/FIN" ) ) {
						$oldmask = umask(0);
						mkdir( $path."/_old/FIN", 0777 );
						umask($oldmask);								
						}

					if( !is_dir( $path."/_old/_PRE" ) ) {
						$oldmask = umask(0);
						mkdir( $path."/_old/_PRE", 0777 );
						umask($oldmask);								
						}
				
					if( $pageInfo[0][6] == "ad" ) {
							$prevDir = $path."/_ads";
							}
					elseif( $pageInfo[0][6] == "PRE" ) {
							$prevDir = $path."/_PRE";
							}
					else {
						$oldPack = sql_aget( "packages", "id='".$pageInfo[0][1]."'", "*" );
						$prevDir = $path."/".$oldPack[0]["directory"];
						if( $pageInfo[0][11] == "1" ) $prevDir .= "/FIN";
						}
					$oldFile = str_pad(intval( $pageInfo[0][5] ), 3, '0', STR_PAD_LEFT)."_".$pageInfo[0][1]."_".( $pageInfo[0][6] == "ad" ? "ad_" : "" )."preview";

					if( copy( $prevDir."/".$oldFile.".jpg", $path."/_old".( $pageInfo[0][11] == "1" ? "/FIN" : ( $pageInfo[0][6] == "PRE" ? "/_PRE" : "" ) )."/".$oldFile."_".$pageInfo[0][3].".jpg" ) ) {
						if( $type == "alter" )
							sql_update( 'pageinfo', 'version="'.( intval( $pageInfo[0][3] )+1 ).'", status="'.$s.'", pack_id="'.$pack_id.'", type="'.$alter[1].'", view=""', 'id="'.$pageInfo[0][0].'"' );
						else
							sql_update( 'pageinfo', 'version="'.( intval( $pageInfo[0][3] )+1 ).'", status="'.$s.'", pack_id="'.$pack_id.'", type="'.$type.'", view=""', 'id="'.$pageInfo[0][0].'"' );
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'comment' );
						$pT = ( $pageState  == "FIN" ? "FIN" : ( $pageType == "NOR" ? "NOR" : "PRE"  ) );
						$values = array( '0', 'updatePage', $p_id[0][1], $p_id[0][2], $p_id[0][10], $pageNum, time(), $pT, $pageVersion );
						sql_add( 'action_log', $names, $values );							
						
						$a = substr( $file, 0, -4 ).'.pdf';
						$b = '/var/www/intra/client/'.substr( $prevDir , 3 ).'/'.$oldFile.'.pdf';
						
						echo "<br>AUTOCOMPARE ".$a." ".$b."<br>";
						$end = r3run( 'AUTOCOMPARE', array(), $a, $b );
						file_put_contents( '/var/www/intra/client/tests/B-E.out', $end );
						echo $end."<br>";
						sql_update( 'pageinfo', 'lastdifference="'.$end.'"', 'id="'.$pageInfo[0][0].'"' );
						}															
					}
				else {
					if( $pageWidth > 1 ) {
						$names[] = "width";
						$values[] = $pageWidth;
						}
					if( $pageState == "FIN" ) {
						$names[] = "fin";
						$values[] = "1";
						/*if( $jcode != "BAV" ) {
							$names[] = "fin";
							$values[] = "1";
							}*/
						}
					sql_add( 'pageinfo', $names, $values );
					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'comment' );
					$pT = ( $pageState  == "FIN" ? "FIN" : ( $pageType == "NOR" ? "NOR" : "PRE"  ) );
					$values = array( '0', 'newPage', $p_id[0][1], $p_id[0][2], $p_id[0][10], $pageNum, time(), $pT, $pageVersion );
					sql_add( 'action_log', $names, $values );								
					}
				}
			echo "</pre><br><br>";
			if( $type == "alter" ) {
				if( !is_dir( $path."/".$subDir ) ) {
					$oldmask = umask(0);
					mkdir( $path."/".$subDir, 0777 );
					umask($oldmask);
					}
		
				}
			
			if( $type == 'ad' ) {
				if( !is_dir( $path."/_ads" ) ) {
					$oldmask = umask(0);
					mkdir( $path."/_ads", 0777 );
					umask($oldmask);
					}
				}
			
			if( $pageState == "FIN" ) {
				echo "is dir? ".is_dir( $path."/".$subDir );
				if( !is_dir( $path."/".$subDir ) ) {
					echo "nem";
					$oldmask = umask(0);
					mkdir( $path."/".$subDir, 0777 );
					umask($oldmask);
					}
				}
			//die();
			echo "<br>".$path."/".$subDir."<br>";
			
			if( is_dir( $path."/".$subDir ) )
				if( $event == "page_pdf" ) {
					if( $pageInfo[0][0] != '' ) {
						if( !is_dir( $path."/_old" ) ) {
							$oldmask = umask(0);
							mkdir( $path."/_old", 0777 );
							umask($oldmask);								
							}

						if( !is_dir( $path."/_old/FIN" ) ) {
							$oldmask = umask(0);
							mkdir( $path."/_old/FIN", 0777 );
							umask($oldmask);								
							}

						if( !is_dir( $path."/_old/_PRE" ) ) {
							$oldmask = umask(0);
							mkdir( $path."/_old/_PRE", 0777 );
							umask($oldmask);								
							}
					
						if( $pageInfo[0][6] == "ad" ) {
							$prevDir = $path."/_ads";
							}
						elseif( $pageInfo[0][6] == "PRE" ) {
							$prevDir = $path."/_PRE";
							}	
						else {	
							$oldPack = sql_aget( "packages", "id='".$pageInfo[0][1]."'", "*" );
							$prevDir = $path."/".$oldPack[0]["directory"];
							if( $pageInfo[0][11] == "1" ) $prevDir .= "/FIN";
							}
					
						$oldFile = str_pad(intval( $pageInfo[0][5] ), 3, '0', STR_PAD_LEFT)."_".$pageInfo[0][1]."_".( $pageInfo[0][6] == "ad" ? "ad_" : "" )."preview";
				
						echo "<br>".$prevDir."/".$oldFile.".pdf => ".$path."/_old".( $pageInfo[0][11] == "1" ? "/FIN" : ( $pageInfo[0][6] == "PRE" ? "/_PRE" : "" ) )."/".$oldFile."_".$pageInfo[0][3].".pdf";
						
						copy( $prevDir."/".$oldFile.".pdf", $path."/_old".( $pageInfo[0][11] == "1" ? "/FIN" : ( $pageInfo[0][6] == "PRE" ? "/_PRE" : "" ) )."/".$oldFile."_".$pageInfo[0][3].".pdf" );
						}
						
					copy( $file, $new );		
					thumbCreate2( $new, $pageWidth );
					}
			//die();
			}
		}
	}
	
?>