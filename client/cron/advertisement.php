<?
	set_include_path(__DIR__);
	chdir(__DIR__);
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	
	$dir = '../message';
	$files = load_dir_files( $dir, '.xml' );
	sort( $files );
	//die();
	
	function searchArticle( $xml, $name, $page ) {
		$hold = 0;
		$jcode = (string) $xml->jobCode;
		$issue = (string) $xml->issue;
		$pageVersion = (string) $xml->pageVersion;
		
		$pageType = (string) $xml->pageType;
		$type = 0;
		$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
		
		$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
		$pack_id = 0;
		if( $pageVersion != "-baseversion-" ) {	
			echo "első<br>";
			$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
			echo $packages[0][0];
			if( $packages[0][0] != '' ) {
				$pack_id = $packages[0][0];
				if( $packages[0][3] == "" ) {
					$type = "alter";
					}
				else {
					$type = "magazine";
					}
				}
			return array( $hold, $pack_id, $type );
			}	
				
		$ads = sql_get( 'ads', 'pub_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
		if( $ads[0][0] != "" ) {
			echo "második";
			$type = "ad";
			if( $ads[0][8] == "Feltöltés alatt" ) {
				$hold = 1;
				$pack_id = $ads[0][0];
				}
			else {
				$pack_id = $ads[0][0];
				}
			
			return array( $hold, $pack_id, $type );
			}
		
			
		$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
		if( $packages[0][0] != '' ) {
			$pack_id = $packages[0][0];
			
			if( $pageType == "NOR" ) {
				$newPages = "";
				$start = 0; $end = 0;
				$cPages = explode( "-", $packages[0][3] );
				var_dump( $cPages );
				if( $packages[0][3] == "" ) {
					$newPages = $page;
					}
				else {
					if( $cPages[1] != "" ) {
						if( $page <= intval( $cPages[0] ) ) {
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
						if( gettype( $end ) != "array" && $start != $end )
							$newPages = $start."-".$end;
						else 
							$newPages = $start;
						}
					}
			
				echo "<br>".$newPages;
				sql_update( 'packages', 'starting_page="'.$newPages.'"', 'id="'.$packages[0][0].'"' );
				$type = "magazine";
				}
			else {
				$type = "alter";
				}
			//die();
			return array( $hold, $pack_id, $type );
			}
		else {
			$names = array( 'publication_id', 'name', 'directory', 'acquired_name', 'starting_page' );
			$values = array( $p_id[0][0], $name, $name, $name );
			if( $pageType == "NOR" ) {
				$values[] = intval( $page );
				}
			else {
				$values[] = "";
				}
			$id = sql_add( 'packages', $names, $values );
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
			$values = array( '', 'newArticle', $p_id[0][1], $p_id[0][2], $p_id[0][10], $name, time(), '' );
			sql_add( 'action_log', $names, $values );
								
			$oldmask = umask(0);
			@mkdir("../packages/".$jcode, 0777);
			@mkdir("../packages/".$jcode."/".$issue, 0777);
			@mkdir("../packages/".$jcode."/".$issue."/".$name, 0777);
			umask($oldmask);
			
			$packages = sql_get( 'packages', 'id="'.$id.'"', '*' );
			$pack_id = $packages[0][0];	
			if( $pageType == "NOR" ) {
				$newPages = "";
				$start = 0; $end = 0;
				$cPages = explode( "-", $packages[0][3] );
				var_dump( $cPages );
				if( $packages[0][3] == "" ) {
					$newPages = $page;
					}
				else {
					if( $cPages[1] != "" ) {
						if( $page <= intval( $cPages[0] ) ) {
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
						if( gettype( $end ) != "array" && $start != $end )
							$newPages = $start."-".$end;
						else 
							$newPages = $start;
						}
					}
			
				echo "<br>".$newPages;
				sql_update( 'packages', 'starting_page="'.$newPages.'"', 'id="'.$packages[0][0].'"' );
				$type = "magazine";
				}
			else {
				$type = "alter";
				}
			//die();
			return array( $hold, $pack_id, $type );
			}
		}
	
	for( $y = 0; $y < count( $files ); $y++ ) {
		if( is_file( $dir.'/'.$files[$y] ) && strpos( $files[$y], "_started_" ) === false ) {
			$xml = simplexml_load_file( $dir.'/'.$files[$y] );
			$event = (string) $xml->event;
			$jcode = (string) $xml->jobCode;
			$issue = (string) $xml->issue;
			
			if( $event == 'Approved' ) {
				$page = (string) $xml->remark;
				$pageVersion = (string) $xml->pageVersion;
				$name = nameCalculator( $xml );
				$xml = get_xml_datas( $xml, '//eventComm' );
				if( $pageVersion != "-baseversion-" and $pageVersion != "" ) $tag = $pageVersion."_";
				else $tag = "";
				
				echo "<br><br>";
				$magazine = sql_get( 'magazines', 'code="'.$jcode.'"', 'id' );
				$p_id = sql_get( 'publications', 'code="'.$issue.'" AND magazine_id="'.$magazine[0][0].'"', '*' );
				$checker = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND code="'.$jcode.'" AND issue="'.$issue.'" AND page="'.$page.'" AND state="'.$tag.'"', '*' );
				if( $checker[0][0] != "" ) {
					sql_update( 'pageinfo', 'status="2"', 'id="'.$checker[0][0].'"' );
					unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );

					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
					$values = array( '0', 'approvePage', $p_id[0][1], $p_id[0][2], $p_id[0][10], intval( $page ), time(), '' );
					sql_add( 'action_log', $names, $values );						
					}
				}
			
			if( $event == 'upload_ad_results' ) {
				$publisher = (string) $xml->client;
				$code = (string) $xml->jobCode;
				$name = (string) $xml->description;
				$issue = (string) $xml->issue;
				$remark = (string) $xml->remark;
				$pageNum =  (string) $xml->remark[1];
				$publisher = sql_get( 'publishers', 'name="'.$publisher.'"', '*' );
				$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
				//if( $issue == 'Current' ) {
					$xml2 = simplexml_load_file( '../xml/'.PMD.'.xml' );
					$xpath = $xml2->xpath('/Publications');
					foreach($xpath as $temp) {
						for( $x = 0; $x < count( $temp->Item ); $x++ ) {
							if( $temp->Item[$x]->Code == $magazine[0][3] )
								break;
							}
						}
					
					$current = (string) $xml->issue;
					//$current = (string) $xml2->Item[$x]->Current;
					$pub = sql_get( 'publications', 'code="'.$current.'" AND magazine_id="'.$magazine[0][0].'"', '*' );
				//	}
				$ad = sql_get( 'ads', 'publisher="'.$publisher[0][0].'" AND pub_id="'.$pub[0][0].'" AND name="'.$name.'"', '*' );
				if( $ad[0][0] != '' ) {
					$res = (string) $xml->results->upload;
					if( $res == 'failed' ) {
						$names = array( 'uploaded', 'reason' );
						$values = array( 'error', (string) $xml->results->reason );					
						}
					if( $res == 'successful' ) {
						$check = explode( '-', $pageNum );
						for( $a = 0; $a < count( $check ); $a++ ) {
							$sql = sql_get( 'ads', 'pub_id="'.$ad[0][1].'" AND uploaded LIKE "%'.$check[$a].'%"', '*' );
							for( $b = 0; $b < count( $sql ); $b++ ) {
								if( $ad[0][3] != "2/1" && $ad[0][3] != "1/1" && $sql[$b][3] != "2/1" && $sql[$b][3] != "1/1" ) {
									$p_ads = sql_get( 'partial_ads', 'ads_id="'.$ad[0][0].'" ORDER BY `date` DESC', '*' );
									$p = sql_get( 'partial_ads', 'ads_id="'.$sql[$b][0].'" ORDER BY `date` DESC', '*' );
									for( $c = 0; $c < count( $p ); $c++ ) {
										if( $p_ads[0][2] == $p[$c][2] ) {
											sql_update( 'ads', 'uploaded=""', 'id=\''.$sql[$b][0].'\'' );
											sql_delete( 'partial_ads', 'id="'.$p[$c][0].'"' );
											}
										}
									}
								else {						
									sql_update( 'ads', 'uploaded=""', 'id=\''.$sql[$b][0].'\'' );
									if( $sql[$b][3] != "2/1" && $sql[$b][3] != "1/1" ) {
										$p_ads = sql_get( 'partial_ads', 'ads_id="'.$sql[$b][0].'"', 'id' );
										for( $c = 0; $c < count( $p_ads ); $c++ ) {
											sql_delete( 'partial_ads', 'id="'.$p_ads[$c][0].'"' );
											}
										}
									}
								}
							}
						
						if( $ad[0][3] != "2/1" && $ad[0][3] != "1/1" ) {
							$p_ads = sql_get( 'partial_ads', 'ads_id="'.$ad[0][0].'" ORDER BY `date` ASC', 'id' );
							for( $c = 0; $c < count( $p_ads )-1; $c++ ) {
								sql_delete( 'partial_ads', 'id="'.$p_ads[$c][0].'"' );
								}						
							}
						$names = array( 'uploaded' );
						$values = array( $pageNum );
						if( $ad[0][6] == 0 ) {
							$names[] = "status";
							$values[] = "4";
							}
						}
					$command = '';
					for( $i = 0; $i < count( $names ); $i++ ) {
						$command .= $names[$i].'=\''.$values[$i].'\'';
						if( $i < count( $names )-1 ) {
							$command .= ', ';
							}
						}

					if( sql_update( 'ads', $command, 'id=\''.$ad[0][0].'\'' ) ) {
						@unlink( $dir.'/'.$files[$y] );
						}
					
					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
					$values = array( $_SESSION['intra_user'], 'uploadAD', $publisher[0][0], $magazine[0][0], $current, $pageNum, time(), $res, $ad[0][2] );
					sql_add( 'action_log', $names, $values );					
					}
				}
			
			if( $event == 'page_thumbnail' or $event == 'page_pdf' ) {
				$tempSize = filesize( $dir."/".$files[$y] );		
				sleep( 5 );
				clearstatcache();
				$tempSize2 = filesize( $dir."/".$files[$y] );
				
				$go = false;
				if( $tempSize == $tempSize2 ) {
					$temp = str_replace( ".xml", ".pdf", $files[$y] );
					if( is_file( $dir."/".$temp ) ) {
						$tempSize = filesize( $dir."/".$temp );		
						sleep( 5 );
						clearstatcache();
						$tempSize2 = filesize( $dir."/".$temp );						
						if( $tempSize == $tempSize2 ) {
							$go = true;
							}
						}
					}
				
				
				if( $go ) {
					rename( $dir."/".$files[$y], $dir."/_started_".$files[$y] );
					rename( $dir."/".str_replace( ".xml", ".pdf", $files[$y] ), $dir."/_started_".str_replace( ".xml", ".pdf", $files[$y] ) );

					$files[$y] = "_started_".$files[$y];
					$pageState = (string) $xml->pageState;
					$pageVersion = (string) $xml->pageVersion;

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
					
					$pageType = (string) $xml->pageType;
					$description = (string) $xml->description;
					//$description = letter_change( $description );
					$pageNum = intval( (string) $xml->pageNum );
					$page = intval( (string) $xml->pageNum );
					$pageWidth = intval( (string) $xml->pageWidth );
					if( $event == 'page_thumbnail' ) $ext = "jpg";
					if( $event == 'page_pdf' ) $ext = "pdf";
				
					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
				
					$name = nameCalculator( $xml );
					$result = searchArticle( $xml, $name, $page );
					var_dump( $result );
					//die();
					if( count( $result ) == 3 ) {
						$hold = $result[0]; $pack_id = $result[1]; $type = $result[2];
						if( ( $pack_id == NULL or $pack_id == 0 ) and $event == 'page_pdf' ) {
							$page = searchRange( $xml );
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
							$old = $dir.'/'.substr( $files[$y], 0, -4 ).'.'.$ext;
							$path = '../packages/'.$jcode.'/'.$issue;
							if( !is_dir( '../packages/'.$jcode ) ) {
								$oldmask = umask(0);
								mkdir( '../packages/'.$jcode, 0777 );
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
											//sql_update( 'publications', 'status="current"', 'id="'.$p_id[0][0].'"' );
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
										
										$a = '/var/www/intra/client/message'.$dirFiles[$i].'/'.substr( $files[$y], 0, -4 ).'.pdf';
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
									
									
									thumbCreate( $path."/".$subDir, $dir.'/'.substr( $files[$y], 0, -4 ).'.pdf', $new, $pageWidth );
									}
								if( $event == "page_thumbnail" ) $new .= ".backup";
								if( rename( $old, $new ) )			
									rename( $dir.'/'.$dirFiles[$i].'/'.$files[$y], "../temp/_preSave/".$files[$y] );
							//die();
							}
						}
					}
				}
			}
		}

echo "lefutottam";
?>