<?PHP

error_log( "PAGEINFO LOG START");

$status = $_POST["result"];
$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];
$description = $_POST["description"];
$event = $_POST["event"];

$file = $_POST["fileName"].".pdf";
//$file = "026_ABC1802.pdf";
$go = 0;
if ( move_uploaded_file( $_FILES[0]["tmp_name"], $file ) ) {
	$go = 1;
	}

error_log( "GO: ".$go );

if( $go ) {
	error_log( "go megy" );
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
	var_dump( $page );
	$pageWidth = intval( $_POST["pageWidth"] );
	if( $event == 'page_thumbnail' ) $ext = "jpg";
	if( $event == 'page_pdf_teszt' ) $ext = "pdf";

	$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
	$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
	error_log( "namecalc elott" );
	$name = nameCalculator2( $_POST );
	echo "CIKKNÉV: ".$name;
	$result = searchArticlePost( $_POST, $description, $page );
	var_dump( $result );
	
	if( count( $result ) == 3 ) {
		$hold = $result[0]; $pack_id = $result[1]; $type = $result[2];
		if( ( $pack_id == NULL or $pack_id == 0 ) and $event == 'page_pdf_teszt' ) {
			error_log("IF: 1");
			$pacakage_page = searchRange_array( $_POST );
			var_dump( "teszt" );
			var_dump( $pacakage_page );
			$checker = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
			echo "check: ".$checker[0][0]."<br>";
			if( $checker[0][0] == "" ) {
				$names = array( 'publication_id', 'name', 'starting_page', 'directory', 'acquired_name', 'status_changed' );
				$values = array( $p_id[0][0], $name, $pacakage_page, $name, str_replace( "'", "", $description ), time() );
		
				if( $pacakage_page == "" )
					$type = "alter";
				else 
					$type = "magazine";
		
				$pack_id = sql_add( 'packages', $names, $values );
				$oldmask = umask(0);
				mkdir(TRKPATH."/packages/".$jcode, 0777);
				mkdir(TRKPATH."/packages/".$jcode."/".$issue, 0777);
				mkdir(TRKPATH."/packages/".$jcode."/".$issue."/".$name, 0777);
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
					
				$pack_id = $checker[0][0];
				$type = "magazine";
				}
			}
	
		if( $hold == 0 && $pack_id > 0 ) {
			error_log("IF: 2");
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
			
			error_log( $path );		
			error_log( is_dir( TRKPATH.'/packages/'.$jcode ) );
			
			if( !is_dir( TRKPATH.'/packages/'.$jcode ) ) {
				error_log( "nem létezik!" );
				$oldmask = umask(0);
				mkdir( TRKPATH.'/packages/'.$jcode, 0777 );
				umask($oldmask);							
				}
			if( !is_dir( $path ) ) {
				$oldmask = umask(0);
				mkdir( $path, 0777 );
				umask($oldmask);							
				}
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
			
			$remove = array();
			$sql_remove = array();

			$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
					
			foreach($xpath as $temp) {
				for( $i = 0; $i < count( $temp->Item ); $i++ ) {
					if( $temp->Item[$i]->Code == $jcode )
						break;
					}
				}
			
			$pn = (string) $xml->Item[$i]->PageNumbering;
			$standard = $xml->Item[$i]->PDFstandard;
			error_log( "page_pdf_teszt DEBUG");
			
			var_dump( $pn );
			var_dump( $type );
			$extra = "";
			if( $pn == "American" ) {
				$extra = "AND part='".$_POST["part"]."'";
				}
			
			if( $type == "alter" ) {
				$pageInfo = sql_get( 'pageinfo', 'type="'.$alter[1].'" AND code="'.$jcode.'" AND issue="'.$issue.'" AND page="'.$page.'" '.$extra.'', '*' );
				}
			else {
				if( $pageState == "FIN" ) {
					$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" AND fin="1" '.$extra.'', '*' );
					}
				else {
					$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" AND fin="0" '.$extra.'', '*' );
					}
				}

			if( $type != "alter" && $tag == "" && $pageState != "FIN" ) {	
				$checker = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND code="'.$jcode.'" AND issue="'.$issue.'" AND pack_id!="'.$pack_id.'" AND page="'.$page.'" AND fin="0"', '*' ); 
				if( $checker[0][0] != "" ) {
					echo $check;
					$remove[] = fileRemove( $checker[0], $path, $ext );
					if( $checker[0][6] == "ad" and $event == 'page_pdf_teszt' ) {
						$sql_remove[count( $remove )-1] = $checker[0][0];
						}
					}
				else {
					if( $pageState == "FIN" ) {
						$checker = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND code="'.$jcode.'" AND issue="'.$issue.'" AND pack_id!="'.$pack_id.'" AND page="'.$page.'" AND fin="1"', '*' ); 
						if( $checker[0][0] != "" ) {
							echo $check;
							$remove[] = fileRemove( $checker[0], $path, $ext );
							if( $checker[0][6] == "ad" and $event == 'page_pdf_teszt' ) {
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
										if( $event == 'page_pdf_teszt' )
											$sql_remove[count( $remove )-1] = $checker[$i][0];
										}
									}
								else {
									$remove[] = fileRemove( $checker[$i], $path, $ext );
									if( $event == 'page_pdf_teszt' )
										$sql_remove[count( $remove )-1] = $checker[$i][0];
									}
								}
							}
						}
					}
				}

			for( $i = 0; $i < count( $remove ); $i++ ) {
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
			
			var_dump( $pageInfo );
			var_dump( $event );
			if( $event == 'page_pdf_teszt' ) {
				if( $pageInfo[0][0] != '' ) {
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
						
						$a = $path.'/_old'.( $pageInfo[0][11] == "1" ? "/FIN" : ( $pageInfo[0][6] == "PRE" ? "/_PRE" : "" ) ).'/'.$oldFile.'_'.$pageInfo[0][3].'.pdf';
						$b = $prevDir.'/'.$oldFile.'.pdf';
						
						$command = './r3 -mode:AUTOCOMPARE '.$a.' '.$b.' | tee /var/www/html/client/tests/B-E.out';
						echo "<br>".$command."<br>";
						$end = shell_exec('
							cd /var/www/html/r3API/r3 2>&1;
							'.$command.';
							');	
						echo $end."<br>";
						sql_update( 'pageinfo', 'lastdifference="'.$end.'"', 'id="'.$pageInfo[0][0].'"' );
						$id = $pageInfo[0][0];
						}															
					}
				else {
					echo "nincs a pageinfoba";
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
					
					$names[] = "part";
					$values[] = $_POST["part"];

					$names[] = "origname";
					$values[] = $_POST["fileName"].".pdf";
					
					echo "pageinfo add";
					var_dump( $names );
					var_dump( $values );
					$id = sql_add( 'pageinfo', $names, $values );
					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'comment' );
					$pT = ( $pageState  == "FIN" ? "FIN" : ( $pageType == "NOR" ? "NOR" : "PRE"  ) );
					$values = array( '0', 'newPage', $p_id[0][1], $p_id[0][2], $p_id[0][10], $pageNum, time(), $pT, $pageVersion );
					sql_add( 'action_log', $names, $values );								
					}
				}
				
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
				if( !is_dir( $path."/".$subDir ) ) {
					$oldmask = umask(0);
					mkdir( $path."/".$subDir, 0777 );
					umask($oldmask);
					}
				}
			
			error_log( $path."/".$subDir );
			
			if( is_dir( $path."/".$subDir ) )
				if( $event == "page_pdf_teszt" ) {
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
						copy( $prevDir."/".$oldFile.".pdf", $path."/_old".( $pageInfo[0][11] == "1" ? "/FIN" : ( $pageInfo[0][6] == "PRE" ? "/_PRE" : "" ) )."/".$oldFile."_".$pageInfo[0][3].".pdf" );
						}
					
					rename( $file, $new );	

					if( $pageState  == "FIN" ) {
						if( (string) $xml->Item[$i]->FlatplanStages == "1" ) {
							$xml->Item[$i]->FlatplanStages = "2";
							
							$dom = new DOMDocument();
							$dom->preserveWhiteSpace = false;
							$dom->loadXML($xml->asXML());
							$dom->formatOutput = true;
						
							file_put_contents( '../../xml/'.PMD.'.xml', $dom->saveXML() );
							$pmdName = pmdDevSafeName( PMD_LONG.'_NT.xml' );
							file_put_contents( "../../xml/".$pmdName, $dom->saveXML() );

							$array = array(
								"event" => "xml_data",
								);

							$file = array(
								"name" => $pmdName,
								"path" => "xml",
								);
							$response = SwitchSend_TESZT( $array, $file );
							}
						}
					
					$color = partDetect( $p_id[0][0], $page );
					
					$hand = sql_aget( "flatplan_handout", "pubid='".$p_id[0][0]."' order by id DESC", "*" );
					if( !empty( $hand[0]["id"] ) ) {
						sql_update( "flatplan_handout", "changed='1'", "id='".$hand[0]["id"]."'" );
						}
					
					error_log( "PDF STANDARD: ".$standard );
					if( $standard == "Web" ) {
						dynaPrework( $new, $pageWidth );
						}
					else {
						thumbCreate2( $new, $pageWidth, $color );	
						PDF_prework( $id );
						}
					
					error_log("PagePDF type: ".$type );
					
					if( $type == "ad" ) {
						$temp = sql_aget( "ads", "name='".$description."' AND pub_id='".$p_id[0][0]."' AND publisher='".$p_id[0][1]."'", "*" );
						error_log( print_r( $temp, true ) );
						if( !empty( $temp[0]["id"] ) ) {
							sql_update( "ads", "uploaded='".$pageNum."'", "id='".$temp[0]["id"]."'" );
							}
						}
					}
			//die();
			}
		}
	}

error_log( "PAGEINFO LOG END");
	
?>