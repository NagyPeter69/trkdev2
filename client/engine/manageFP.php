<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once('../../engine/engine.php');
	
	include_once('../lang/en.php');
	
	include_once( '../../engine/xml_handler.php' );
	$_POST = $_POST["options"];
	
	if( $_POST["method"] == "removeLastContent" ) {
		$log = "";
		$publication = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
		$publication = $publication[0];
		$issue = $publication["code"];
		$magazine = sql_aget( "magazines", "id='".$publication["magazine_id"]."'", "code" );
		$magazine = $magazine[0]["code"];
		
		$counter = 0;
		for( $i = $_POST["target"]; $i < ($_POST["target"]+$_POST["slotnumber"]) ; $i++ ) {
			$log .= "\n".$i.". slot elemzés:";
			
			$pageinfo = sql_aget( "pageinfo", "(type='magazine' OR type='ad') AND status!='2' AND code='".$magazine."' AND issue='".$issue."' AND page='".$i."' ORDER BY `page` DESC", "*" );
			if( $pageinfo[0]["id"] != "" ) {
				$path = "../packages/".$magazine."/".$issue;
				$log .= "\n   - pageinfoba létezik (futó verzió: ".$pageinfo[0]["version"].")";
				switch( $pageinfo[0]["type"] ) {
					case 'ad':
						$path .= "/_ads";
						$file = str_pad(intval( $pageinfo[0]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[0]["pack_id"]."_ad_preview";			 
						break;
				
					case 'magazine':
						$temp = sql_get( "packages", "id='".$pageinfo[0]["pack_id"]."'", "directory" );
						$path .= "/".$temp[0][0];
						$file = str_pad(intval( $pageinfo[0]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[0]["pack_id"]."_".$pageinfo[0]["state"]."preview";
						break;
					}
				
				$search = intval( $pageinfo[0]["version"] )-1;
				$special_case = 0;
				for( $y = $search; $y > 0; $y-- ) {
					$log .= "\n   - verziószám kutatása: ".$y.")";
					$log .= "\n   - fájl létezés vizsgálata: ".$path."/_old/".$file."_".$y.".jpg";
					if( is_file( $path."/_old/".$file."_".$y.".jpg"  ) ) {
						break;
						}
					else {
						$log .= "\n   - nem létezett így fallback további kutatás";
						
						$fin = "";
						if( $pageinfo[0]["fin"] == "1" ) {
							$fin = "/FIN";
							}
						
						$trypath = TRKPATH."/packages/".$magazine."/".$issue."/_old".$fin;
						$tryname = str_pad(intval( $pageinfo[0]["page"] ), 3, '0', STR_PAD_LEFT)."_";
						
						$oldfiles = load_dir_files( $trypath, $tryname );
						$log .= "\n   - ".$trypath;
						$log .= "\n   - talált fájlok ilyen oldalszámmal: ".count( $oldfiles );
						
						for( $o = 0; $o < count( $oldfiles ); $o++ ) {
							$sfile = explode( ".", $oldfiles[$o] );
							$check = explode( "_", $sfile[0] );
							
							$log .= "\n   - talált fájl neve: ".$sfile[0];
							if( end($check ) == $y ) {
								$log .= "\n   - ez lesz az előző verzió (".$y.")";
								
								if( strpos( $sfile[0], "_ad_" ) !== false ) {
									$log .= "\n   - ez hirdetés volt";
									$special_case = 1;
									$oldversionpath = $trypath;
									$oldversionfile = $sfile[0];
									$oldpath = str_replace( "../", TRKPATH."/", $path );
									$newpath = TRKPATH."/packages/".$magazine."/".$issue."/_ads";
									$ofile = $file;
									$newfile = str_pad(intval( $pageinfo[0]["page"] ), 3, '0', STR_PAD_LEFT)."_".$check[1]."_ad_preview";
									$newpack = $check[1];
									$type = "ad";
									
									$log .= "\n   - talált verzó mappa: ".$oldversionpath."";
									$log .= "\n   - talált verzó fájl: ".$oldversionfile."";
									$log .= "\n   - régi mappa: ".$oldpath."";
									$log .= "\n   - régi fájl: ".$file."";
									$log .= "\n   - új mappa: ".$newpath."";
									$log .= "\n   - új fájl: ".$newfile."";
									$log .= "\n   - új pack id: ".$newpack."";									
									}	
								break;
								}
							}
							
						if( is_file( $oldversionpath."/".$oldversionfile.".jpg"  ) ) {
							$log .= "\n   - létezik a régi verzó fájl";
							break;
							}
						}
					}
				if( $y != 0 ) {
					if( $special_case ) {
						$counter++;
						$log .= "\n      - speciális eset (régi verzió egy hirdetés!)";
						$log .= "\n      - verziószám megtalálva! előző létező verziója: ".$y;
						
						if( $pageinfo[0]["status"] == "2" ) {
							sql_update( "pageinfo", "version='".$y."', view='', status='1'", "id='".$pageinfo[0]["id"]."'" );	
							}
						elseif( $pageinfo[0]["status"] == "0" ) {
							sql_update( "pageinfo", "version='".$y."', view='', status='0'", "id='".$pageinfo[0]["id"]."'" );	
							}
						else {
							sql_update( "pageinfo", "version='".$y."', view='', status='1'", "id='".$pageinfo[0]["id"]."'" );
							}
						
						
						rename( $oldversionpath."/".$oldversionfile.".jpg", $newpath."/".$newfile.".jpg" );
						rename( $oldversionpath."/".$oldversionfile.".pdf", $newpath."/".$newfile.".pdf" );
						
						if( is_file( $oldpath."/".$file.".jpg" ) ) {
							unlink( $oldpath."/".$file.".jpg" );
							}

						if( is_file( $oldpath."/".$file.".pdf" ) ) {
							unlink( $oldpath."/".$file.".pdf" );
							}
						
						sql_update( "pageinfo", "type='ad', pack_id='".$newpack."'", "id='".$pageinfo[0]["id"]."'" );
						
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
						$values = array( $_SESSION['intra_user'], 'revertedPage', $publication["publisher_id"], $publication["magazine_id"], $issue, $i, time(), '', $y );
						sql_add( 'action_log', $names, $values );
						}
						
					else {
						$counter++;
						$log .= "\n      - verziószám megtalálva! előző létező verziója: ".$y;
						if( $pageinfo[0]["status"] == "2" ) {
							sql_update( "pageinfo", "version='".$y."', view='', status='1'", "id='".$pageinfo[0]["id"]."'" );	
							}
						elseif( $pageinfo[0]["status"] == "0" ) {
							sql_update( "pageinfo", "version='".$y."', view='', status='0'", "id='".$pageinfo[0]["id"]."'" );	
							}
						else {
							sql_update( "pageinfo", "version='".$y."', view='', status='1'", "id='".$pageinfo[0]["id"]."'" );
							}
									
						rename( $path."/_old/".$file."_".$y.".jpg", $path."/".$file.".jpg" );
						if( !rename( $path."/_old/".$file."_".$y.".pdf", $path."/".$file.".pdf" ) ) {
							$log .= "\n      - hiba a fájl átnevezésénél!!! ";
							}
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
						$values = array( $_SESSION['intra_user'], 'revertedPage', $publication["publisher_id"], $publication["magazine_id"], $issue, $i, time(), '', $y );
						sql_add( 'action_log', $names, $values );
						}					
					}
				}
			}
		
		$result = array( $counter, $log );
		}
	
	if( $_POST["method"] == "removeBlockContent" ) {
		$log = "";
		$publication = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
		$publication = $publication[0];
		$issue = $publication["code"];
		$magazine = sql_aget( "magazines", "id='".$publication["magazine_id"]."'", "code" );
		$magazine = $magazine[0]["code"];
		
		for( $i = $_POST["target"]; $i < ($_POST["target"]+$_POST["slotnumber"]) ; $i++ ) {
			$log .= "\n".$i.". slot elemzés:";
			
			//Commentek törlése
			if( $_POST["movecomment"] == "true" ) {
				$command = "pub_id='".$publication['id']."' AND page='".$i."'";
				$comments = sql_aget( "comments", $command, "id, page" );
				for( $y = 0; $y < count( $comments ); $y++ ) {
					$log .= "\n  -Comment törlése:".$comments[$y]["id"].", oldal: ".$comments[$y]["page"];
					sql_delete( "comments", "id='".$comments[$y]["id"]."'" );
					}
				}
			
			//Partial Ads kezelés
			$partial = sql_aget( "ads", "size!='1/1' AND size!='2/1' AND pub_id='".$publication["id"]."' AND uploaded='".$i."'", "id, uploaded" );
			for( $y = 0; $y < count( $partial ); $y++ ) {
				$log .= "\n  -Tört hirdetés feltöltés törlése";
				sql_update( "ads", "uploaded=''", "id='".$partial[$y]["id"]."'" );
				}
				
			//Pageinfo ellenörzése
			$pageinfo = sql_aget( "pageinfo", "(type='magazine' OR type='ad') AND code='".$magazine."' AND issue='".$issue."' AND page='".$i."' ORDER BY `page` DESC", "*" );
			for( $y = 0; $y < count( $pageinfo ); $y++ ) {
				sql_delete( "pageinfo", "id='".$pageinfo[$y]["id"]."'" );
				$file = "../packages/".$magazine."/".$issue;
				switch( $pageinfo[$y]["type"] ) {
					case 'ad':
						$file .= "/_ads/".str_pad(intval( $pageinfo[$y]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$y]["pack_id"]."_ad_preview";
						$ad = sql_aget( "ads", "id='".$pageinfo[$y]["pack_id"]."'", "id, uploaded" );
						$ad = $ad[0];
						
						$pages = explode( "-", $ad["uploaded"] );
						if( count( $pages ) == 1 ) {
							$log .= "\n   ( ".count( $pages )." ) Hirdetésből feltöltés törlése ";
							sql_update( "ads", "uploaded=''", "id='".$ad["id"]."'" );
							}
						if( count( $pages ) == 2 ) {
							if( $pages[0] == $pageinfo[$y]["page"] ) $pages[0] = 0;
							if( $pages[1] == $pageinfo[$y]["page"] ) $pages[1] = 0;
							
							$pages = implode( "-", $pages );
							if( $pages == "0-0" ) $pages = "";
							$log .= "\n   ( ".count( $pages )." ) Kétoldalas hirdetés feltöltési pozició modosítása: ".$pages;
							sql_update( "ads", "uploaded='".$pages."'", "id='".$ad["id"]."'" );
							}			 
						break;
				
					case 'magazine':
						$temp = sql_get( "packages", "id='".$pageinfo[$y]["pack_id"]."'", "directory" );
					
						$file .= "/".$temp[0][0]."/".str_pad(intval( $pageinfo[$y]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$y]["pack_id"]."_".$pageinfo[$y]["state"]."preview";
						break;
					}
				$log .= "\n   Pageinfóból törlés: ";
				$log .= "Fájlnév: ".$file;	
				unlink( $file.".jpg" );
				unlink( $file.".pdf" );
				}			
			}

		$names = array( 'type', 'start', 'orient', 'size', 'time', 'user', 'magazine', 'issue' );
		$values = array( $_POST["method"], $_POST["target"], 'start_at', $_POST["slotnumber"], time(), $_SESSION['intra_user'], $magazine, $issue );
		sql_add( 'issueManagement_log', $names, $values );
		
		$result = array( "", $log );
		}
	
	if( $_POST["method"] == "remove" ) {
		$log = "";
		$publication = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
		$publication = $publication[0];
		$issue = $publication["code"];
		$magazine = sql_aget( "magazines", "id='".$publication["magazine_id"]."'", "code" );
		$magazine = $magazine[0]["code"];
		
		// publications.pages gets recomputed from parts.place further down,
		// after the PARTS ELLENŐRZÉS block below has shifted each part's
		// own range - it's no longer set here by hand, since doing both in
		// parallel is exactly how it drifted out of sync with parts before.
		$log .= "\n";
		switch(  $_POST['orient'] ) {
			case 'after':
				$start = (intval( $_POST["target"] )+1);
				$end = ( intval( $_POST["target"] )+intval( $_POST["slotnumber"] ) )+1;
				break;
				
			case 'before':
				$start = ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) );
				$end = intval( $_POST["target"] );
				break;
			}
			
		for( $i = $start; $i < $end; $i++ ) {
			$log .= "\n ".$i.". blokk eltávolítása:";
			
			//Commentek törlése
			$command = "pub_id='".$publication['id']."' AND page='".$i."'";
			$comments = sql_aget( "comments", $command, "id, page" );
			for( $y = 0; $y < count( $comments ); $y++ ) {
				$log .= "\n  -Comment törlése:".$comments[$y]["id"].", oldal: ".$comments[$y]["page"];
				sql_delete( "comments", "id='".$comments[$y]["id"]."'" );
				}
			
			$partial = sql_aget( "ads", "size!='1/1' AND size!='2/1' AND pub_id='".$publication["id"]."' AND uploaded='".$i."'", "id, uploaded" );
			for( $y = 0; $y < count( $partial ); $y++ ) {
				$log .= "\n  -Tört hirdetés feltöltés törlése";
				sql_update( "ads", "uploaded=''", "id='".$partial[$y]["id"]."'" );
				}			
				
			//Pageinfo törlése
			$pageinfo = sql_aget( "pageinfo", "(type='magazine' OR type='ad') AND code='".$magazine."' AND issue='".$issue."' AND page='".$i."' ORDER BY `page` DESC", "*" );
			for( $y = 0; $y < count( $pageinfo ); $y++ ) {
				$log .= "\nPageinfóból törlés: ".$i.". oldal ";
				sql_delete( "pageinfo", "id='".$pageinfo[$y]["id"]."'" );
				$file = "../packages/".$magazine."/".$issue;
				switch( $pageinfo[$y]["type"] ) {
					case 'ad':
						$file .= "/_ads/".str_pad(intval( $pageinfo[$y]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$y]["pack_id"]."_ad_preview";
						break;
				
					case 'magazine':
						$temp = sql_get( "packages", "id='".$pageinfo[$y]["pack_id"]."'", "directory" );
					
						$file .= "/".$temp[0][0]."/".str_pad(intval( $pageinfo[$y]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$y]["pack_id"]."_".$pageinfo[$y]["state"]."preview";
						break;
					}
				
				$log .= "Fájlnév: ".$file;	
				unlink( $file.".jpg" );
				unlink( $file.".pdf" );
				}
			}
		
		// PARTS ELLENÖRZÉS, JAVÍTÁS
		$log .= "\n";
		$parts = sql_aget( "parts", "pub_id='".$publication['id']."'", "id, name, place" );
		for( $i = 0; $i < count( $parts ); $i++ ) {
			$log .= "\nPart ellenőrzés: ".$parts[$i]["name"].", kezdeti oldalszámok: ".$parts[$i]["place"]." ";
			$pages = explode( ", ", $parts[$i]["place"] );
			$newPages = $helper = $helper2 = array(); 
			for( $y = 0; $y < count( $pages ); $y++ ) {
				$helper2 = array(); 
				if( $pages[$y] != "1-2" ) {					
					$temp = explode( "-", $pages[$y] );
					for( $x = 0; $x < count( $temp ); $x++ ) {
						if( $temp[$x] != "3" ) 
							$helper2[] = ( intval( $temp[$x] ) >= intval( $_POST["target"] ) ? ( intval( $temp[$x] ) - intval( $_POST["slotnumber"] ) ) : intval( $temp[$x] ) );
						else 
							$helper2[] = $temp[$x];
						}
					$helper[] = implode( "-", $helper2 );
					}
				else {
					$helper[] = $pages[$y];	
					}
				}
			$newPages = implode( ", ", $helper );
			$log .= " módostás utáni oldalszámmódostás: ".$newPages;
			sql_update( "parts", "place='".$newPages."'", "id='".$parts[$i]["id"]."'" );
			}

		$newPages = syncPublicationPages( $publication["id"] );
		$log .= "\nIssue terjedelme: ".$publication["pages"].", módosítás után: ".$newPages;

		// COMMENTEK ELLENŐRZÉSE, ELTOLÁSA
		$log .= "\n";
		if( $_POST["movecomment"] == "true" ) {
			$log .= "\nCommentek eltolása";

			$command = "pub_id='".$publication['id']."' AND page".( $_POST['orient'] == "after" ? ">" : ">=" )."'".$_POST["target"]."'";
			$comments = sql_aget( "comments", $command, "id, page" );
			for( $y = 0; $y < count( $comments ); $y++ ) {
				$log .= "\nComment ellenőrzés: jelenlegi oldalszáma : ".$comments[$y]["page"]." ";
				$newPage = intval( $comments[$y]["page"] ) - intval( $_POST["slotnumber"] );
				$log .= ", módosítás utáni oldalszám: ".$newPage;
				sql_update( "comments", "page='".$newPage."'", "id='".$comments[$y]["id"]."'" );
				}
			}
		else {
			$log .= "\nCommentek eredeti helyükön maradnak";
			}		
		
		// CIKKEK ELLENŐRZÉSE, ELTOLÁSA, TÖRLÉSE
		$log .= "\n";
		$log .= "\n Cikkek ellenőrzése:";
		$articles = sql_aget( "packages", "publication_id='".$publication["id"]."' AND starting_page!='' ORDER BY `starting_page` ASC", "*" );
		for( $i = 0; $i < count( $articles ); $i++ ) {
			$log .= "\n   -Cikk neve: ".$articles[$i]["name"]." oldalszáma: ".$articles[$i]["starting_page"].", ";
			$pages = explode( "-", $articles[$i]["starting_page"] );
			$helper = array();
			$newPage = "";
			if( count( $pages ) == 1 ) {
				if( $_POST['orient'] == "after" ) {
					if( intval( $pages[0] ) > intval( $_POST["target"] ) && ( intval( $_POST["slotnumber"] )+intval( $_POST["target"] ) ) >= intval( $pages[0] ) ) {
						$helper[0] = "TÖRÖLVE";
						}
					elseif( intval( $pages[0] ) > intval( $_POST["target"] ) ) {
						$helper[0] = intval( $pages[0] ) - intval( $_POST["slotnumber"] );
						}
					else {
						$helper[0] = intval( $pages[0] );
						}
					}
				
				if( $_POST['orient'] == "before" ) {
					if( intval( $pages[0] ) >= ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) ) && intval( $_POST["target"] ) > intval( $pages[0] ) ) {
						$helper[0] = "TÖRÖLVE";
						}
					elseif( intval( $pages[0] ) >= intval( $_POST["target"] ) ) {
						$helper[0] = intval( $pages[0] ) - intval( $_POST["slotnumber"] );
						}
					else {
						$helper[0] = intval( $pages[0] );
						}				
					}
				$newPage = $helper[0];
				}
			elseif( count( $pages ) == 2 ) {
				if( $_POST['orient'] == "after" ) {
					if( intval( $pages[0] ) > intval( $_POST["target"] ) && ( intval( $_POST["slotnumber"] )+intval( $_POST["target"] ) ) >= intval( $pages[1] ) ) {
						$newPage = "TÖRÖLVE";
						}
					elseif( intval( $_POST["target"] ) < intval( $pages[0] ) ) {
						if( ( intval( $pages[0] )-intval( $_POST["slotnumber"] ) ) <= intval( $_POST["target"] ) ) {
							if( ( intval( $_POST["target"] )+1 ) == ( intval( $pages[1] )-intval( $_POST["slotnumber"] ) ) ) {
								$newPage = ( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
								}
							else {
								$newPage = ( intval( $_POST["target"] )+1 )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
								}
							}
						else {
							$newPage =  ( intval( $pages[0] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
							}
						}
					elseif( intval( $_POST["target"] ) < intval( $pages[1] ) ) {
						if( intval( $pages[0] ) < intval( $_POST["target"] ) ) {
							$newPage = intval( $pages[0] )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
							}
						else {
							$newPage = ( intval( $pages[0] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
							}
						}
					else {
						$newPage = implode( "-", $pages );
						}
					}
				
				if( $_POST['orient'] == "before" ) {
					if( intval( $pages[0] ) >= ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) ) && intval( $_POST["target"] ) > intval( $pages[1] ) ) {
						$newPage = "TÖRÖLVE";
						}
					elseif( intval( $_POST["target"] ) >= intval( $pages[0] ) && intval( $_POST["target"] ) < intval( $pages[1] ) ) {
						if( intval( $pages[0] ) == ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) ) ) {
							if( ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) ) == ( intval( $pages[1] )-intval( $_POST["slotnumber"] ) ) ) {
								$newPage = ( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
								}
							else {
								$newPage = ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
								}
							}
						else {
							if( intval( $_POST["target"] ) == intval( $pages[0] ) ) {
								$newPage = ( intval( $pages[0] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
								}
							else {
								$newPage = ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
								}
							}
						}
					elseif( intval( $_POST["target"] ) <= intval( $pages[1] ) ) {
						$newPage = ( intval( $pages[0] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
						}
					else {
						$newPage = implode( "-", $pages );
						}					
					}	
				}
			
			$log .= " új oldalszám: ".$newPage;
			if( $newPage == "TÖRÖLVE" ) {
				sql_delete( "packages", "id='".$articles[$i]["id"]."'" );
				}
			else {
				sql_update( "packages", "starting_page='".$newPage."'", "id='".$articles[$i]["id"]."'" );
				}
			}					

		//ADS ELLENŐRZÉS
		$log .= "\n";
		$command = "page".( $_POST['orient'] == "after" ? ">" : ">=" )."'".$_POST["target"]."'";
		$ads = sql_aget( "ads", "pub_id='".$publication["id"]."' AND uploaded!=''", "*" );
		$log .= "\n Feltöltött hirdetések ellenőrzése:";
		for( $i = 0; $i < count( $ads ); $i++ ) {
			$log .= "\n   -Hirdetés eredeti oldalszáma: ".$ads[$i]["uploaded"].", ";
			$pages = explode( "-", $ads[$i]["uploaded"] );
			$helper = $newPage = array();
			if( count( $pages ) == 1 ) {
				if( $_POST['orient'] == "after" ) {
					if( intval( $pages[0] ) > intval( $_POST["target"] ) && ( intval( $_POST["slotnumber"] )+intval( $_POST["target"] ) ) >= intval( $pages[0] ) ) {
						$helper[0] = "";
						}
					elseif( intval( $pages[0] ) > intval( $_POST["target"] ) ) {
						$helper[0] = intval( $pages[0] ) - intval( $_POST["slotnumber"] );
						}
					else {
						$helper[0] = intval( $pages[0] );
						}
					}
				
				if( $_POST['orient'] == "before" ) {
					if( intval( $pages[0] ) >= ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) ) && intval( $_POST["target"] ) > intval( $pages[0] ) ) {
						$helper[0] = "";
						}
					elseif( intval( $pages[0] ) >= intval( $_POST["target"] ) ) {
						$helper[0] = intval( $pages[0] ) - intval( $_POST["slotnumber"] );
						}
					else {
						$helper[0] = intval( $pages[0] );
						}				
					}
				$newPage = $helper[0];
				}
			
			if( count( $pages ) == 2 ) {
				if( $_POST['orient'] == "after" ) {
					if( intval( $pages[0] ) > intval( $_POST["target"] ) && ( intval( $_POST["slotnumber"] )+intval( $_POST["target"] ) ) >= intval( $pages[1] ) ) {
						$newPage = "";
						}
					elseif( (intval( $pages[0] ) > intval( $_POST["target"] )&& intval( $pages[0] ) <= ( intval( $_POST["target"] )+intval( $_POST["slotnumber"] ) ) ) && intval( $pages[1] ) >= ( intval( $_POST["target"] )+intval( $_POST["slotnumber"] ) ) ) {
						$newPage = "";
						}
					elseif( intval( $pages[0] ) > intval( $_POST["target"] ) ) {
						$newPage = ( intval( $pages[0] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
						}
					else {
						if( intval( $pages[1] ) >= ( intval( $_POST["target"] )+intval( $_POST["slotnumber"] ) ) ) {
							$newPage = $pages[0]."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
							}
						else {
							$newPage = implode( "-", $pages );
							}
						}
					}
				if( $_POST['orient'] == "before" ) {
					if( intval( $pages[0] ) >= ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) ) && intval( $_POST["target"] ) > intval( $pages[1] ) ) {
						$newPage = "";
						}
					elseif( intval( $_POST["target"] ) >= intval( $pages[0] ) ) {
						if( ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) ) == ( intval( $pages[1] )-intval( $_POST["slotnumber"] ) ) ) {
							$newPage = "0-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
							}
						else {
							$newPage = ( intval( $_POST["target"] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
							}
						}
					elseif( intval( $pages[0] ) >= intval( $_POST["target"] ) ) {
						$newPage = ( intval( $pages[0] )-intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )-intval( $_POST["slotnumber"] ) );
						}
					else {
						$newPage = implode( "-", $pages );
						}
					}
				}
				
			$log .= " oldalszám módosítva: ".$newPage.", ";
			sql_update( "ads", "uploaded='".$newPage."'", "id='".$ads[$i]["id"]."'" );
			}

		// PAGEINFO ELLENŐRZÉSE, ELTOLÁSA
		$log .= "\n";
		$command = "page".( $_POST['orient'] == "after" ? ">" : ">=" )."'".$_POST["target"]."'";
		$pageinfo = sql_aget( "pageinfo", "(type='magazine' OR type='ad') AND code='".$magazine."' AND issue='".$issue."' AND ".$command." ORDER BY `page` ASC", "*" );		
		$log .= "\n Pageinfo csesztetése:";
		for( $i = 0; $i < count( $pageinfo ); $i++ ) {
			$log .= "\n   -Eredeti oldalszáma: ".$pageinfo[$i]["page"].", ";
			$newPage = intval( $pageinfo[$i]["page"] ) - intval( $_POST["slotnumber"] );
			$log .= "módostott oldalszám: ".$newPage;
			sql_update( "pageinfo", "page='".$newPage."'", "id='".$pageinfo[$i]["id"]."'" );
			$file = "../packages/".$magazine."/".$issue;
			switch( $pageinfo[$i]["type"] ) {
				case 'ad':
					$file .= "/_ads/".str_pad(intval( $pageinfo[$i]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$i]["pack_id"]."_ad_preview";
					break;
				
				case 'magazine':
					$temp = sql_get( "packages", "id='".$pageinfo[$i]["pack_id"]."'", "directory" );
					
					$file .= "/".$temp[0][0]."/".str_pad(intval( $pageinfo[$i]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$i]["pack_id"]."_".$pageinfo[$i]["state"]."preview";
					break;
				}
			$log .= "\n   -Eredeti fájlnév: ".$file.", ";
			$file2 = "../packages/".$magazine."/".$issue;
			switch( $pageinfo[$i]["type"] ) {
				case 'ad':
					$file2 .= "/_ads/".str_pad(intval( $newPage ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$i]["pack_id"]."_ad_preview";
					break;
				
				case 'magazine':
					$temp = sql_get( "packages", "id='".$pageinfo[$i]["pack_id"]."'", "directory" );
					$file2 .= "/".$temp[0][0]."/".str_pad(intval( $newPage ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$i]["pack_id"]."_".$pageinfo[$i]["state"]."preview";
					break;
				}
			$log .= "\n   -Módostott fájlnév: ".$file2;
			rename( $file.".jpg" , $file2.".jpg" );
			rename( $file.".pdf" , $file2.".pdf" );
			}
		toSwitch( 'new_publication' , 'publications|'.$publication["id"], $magazine.'_'.$issue, 'issueData' );

		$names = array( 'type', 'start', 'orient', 'size', 'time', 'user', 'magazine', 'issue' );
		$values = array( $_POST["method"], $_POST["target"], $_POST["orient"], $_POST["slotnumber"], time(), $_SESSION['intra_user'], $magazine, $issue );
		sql_add( 'issueManagement_log', $names, $values );
		
		$result = array( "", $log );
		}
	
	if( $_POST["method"] == "insert" ) {
		$log = "";
		$publication = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
		$publication = $publication[0];
		$issue = $publication["code"];
		$magazine = sql_aget( "magazines", "id='".$publication["magazine_id"]."'", "code" );
		$magazine = $magazine[0]["code"];
		
		// publications.pages gets recomputed from parts.place further down,
		// after the PARTS ELLENŐRZÉS block below has shifted each part's
		// own range - it's no longer set here by hand, since doing both in
		// parallel is exactly how it drifted out of sync with parts before.

		// PARTS ELLENŐRZÉS, JAVÍTÁS
		$log .= "\n";
		$parts = sql_aget( "parts", "pub_id='".$publication['id']."'", "id, name, place" );
		for( $i = 0; $i < count( $parts ); $i++ ) {
			$log .= "\nPart ellenőrzés: ".$parts[$i]["name"].", kezdeti oldalszámok: ".$parts[$i]["place"]." ";
			$pages = explode( ", ", $parts[$i]["place"] );
			$newPages = $helper = $helper2 = array(); 
			for( $y = 0; $y < count( $pages ); $y++ ) {
				$helper2 = array(); 
				if( $pages[$y] != "1-2" ) {					
					$temp = explode( "-", $pages[$y] );
					for( $x = 0; $x < count( $temp ); $x++ ) {
						if( $temp[$x] != "3" ) 
							$helper2[] = ( intval( $temp[$x] ) >= intval( $_POST["target"] ) ? ( intval( $temp[$x] ) + intval( $_POST["slotnumber"] ) ) : intval( $temp[$x] ) );
						else 
							$helper2[] = $temp[$x];
						}
					$helper[] = implode( "-", $helper2 );
					}
				else {
					$helper[] = $pages[$y];	
					}
				}
			$newPages = implode( ", ", $helper );
			$log .= " módostás utáni oldalszámmódostás: ".$newPages;
			sql_update( "parts", "place='".$newPages."'", "id='".$parts[$i]["id"]."'" );
			}

		$newPages = syncPublicationPages( $publication["id"] );
		$log .= "\nIssue terjedelme: ".$publication["pages"].", módosítás után: ".$newPages;

		// COMMENTEK ELLENŐRZÉSE, ELTOLÁSA
		$log .= "\n";
		if( $_POST["movecomment"] == "true" ) {
			$log .= "\nCommentek eltolása";
			
			$command = "pub_id='".$publication['id']."' AND page".( $_POST['orient'] == "after" ? ">" : ">=" )."'".$_POST["target"]."'";
			$comments = sql_aget( "comments", $command, "id, page" );
			for( $y = 0; $y < count( $comments ); $y++ ) {
				$log .= "\nComment ellenőrzés: jelenlegi oldalszáma : ".$comments[$y]["page"]." ";
				$newPage = intval( $comments[$y]["page"] ) + intval( $_POST["slotnumber"] );
				$log .= ", módosítás utáni oldalszám: ".$newPage;
				sql_update( "comments", "page='".$newPage."'", "id='".$comments[$y]["id"]."'" );
				}
			}
		else {
			$log .= "\nCommentek eredeti helyükön maradnak";
			}
		
		// CIKKEK ELLENŐRZÉSE, ELTOLÁSA
		$log .= "\n";
		$log .= "\n Cikkek ellenőrzése:";
		$articles = sql_aget( "packages", "publication_id='".$publication["id"]."' AND starting_page!=''", "*" );
		for( $i = 0; $i < count( $articles ); $i++ ) {
			$log .= "\n   -Cikk neve: ".$articles[$i]["name"]." oldalszáma: ".$articles[$i]["starting_page"].", ";
			$pages = explode( "-", $articles[$i]["starting_page"] );
			$helper = array();
			$newPage = "";
			if( count( $pages ) == 1 ) {
				if( $_POST['orient'] == "after" ) {
					if( intval( $pages[0] ) > intval( $_POST["target"] ) ) {
						$helper[0] = intval( $pages[0] ) + intval( $_POST["slotnumber"] );
						}
					else {
						$helper[0] = intval( $pages[0] );
						}
					}
				
				if( $_POST['orient'] == "before" ) {
					if( intval( $pages[0] ) >= intval( $_POST["target"] ) ) {
						$helper[0] = intval( $pages[0] ) + intval( $_POST["slotnumber"] );
						}
					else {
						$helper[0] = intval( $pages[0] );
						}				
					}
				$newPage = $helper[0];
				}
			elseif( count( $pages ) == 2 ) {
				if( $_POST['orient'] == "after" ) {
					if( intval( $_POST["target"] ) < intval( $pages[0] ) ) {
						$newPage =  ( intval( $pages[0] )+intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )+intval( $_POST["slotnumber"] ) );
						}
					elseif( intval( $_POST["target"] ) < intval( $pages[1] ) ) {
						$newPage = $pages[0]."-".( intval( $pages[1] )+intval( $_POST["slotnumber"] ) );
						}
					else {
						$newPage = implode( "-", $pages );
						}
					}
				
				if( $_POST['orient'] == "before" ) {
					if( intval( $_POST["target"] ) <= intval( $pages[0] ) ) {
						$newPage =  ( intval( $pages[0] )+intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )+intval( $_POST["slotnumber"] ) );
						}
					elseif( intval( $_POST["target"] ) <= intval( $pages[1] ) ) {
						$newPage = $pages[0]."-".( intval( $pages[1] )+intval( $_POST["slotnumber"] ) );
						}
					else {
						$newPage = implode( "-", $pages );
						}					
					}	
				}
			
			$log .= " új oldalszám: ".$newPage;
			sql_update( "packages", "starting_page='".$newPage."'", "id='".$articles[$i]["id"]."'" );
			}

		//ADS ELLENŐRZÉS
		$log .= "\n";
		$command = "page".( $_POST['orient'] == "after" ? ">" : ">=" )."'".$_POST["target"]."'";
		$ads = sql_aget( "ads", "pub_id='".$publication["id"]."' AND uploaded!=''", "*" );
		$log .= "\n Feltöltött hirdetések ellenőrzése:";
		for( $i = 0; $i < count( $ads ); $i++ ) {
			$log .= "\n   -Hirdetés eredeti oldalszáma: ".$ads[$i]["uploaded"].", ";
			$pages = explode( "-", $ads[$i]["uploaded"] );
			$helper = $newPage = array();
			if( count( $pages ) == 1 ) {
				if( $_POST['orient'] == "after" ) {
					if( intval( $pages[0] ) > intval( $_POST["target"] ) ) {
						$helper[0] = intval( $pages[0] ) + intval( $_POST["slotnumber"] );
						}
					else {
						$helper[0] = intval( $pages[0] );
						}
					}
				
				if( $_POST['orient'] == "before" ) {
					if( intval( $pages[0] ) >= intval( $_POST["target"] ) ) {
						$helper[0] = intval( $pages[0] ) + intval( $_POST["slotnumber"] );
						}
					else {
						$helper[0] = intval( $pages[0] );
						}				
					}
				$newPage = $helper[0];
				}
			
			if( count( $pages ) == 2 ) {
				if( $_POST['orient'] == "after" ) {
					if( intval( $pages[0] ) >= intval( $_POST["target"] ) && intval( $pages[1] ) > intval( $_POST["target"] ) ) {
						$newPage = $pages[0]."-".( intval( $pages[1] )+intval( $_POST["slotnumber"] ) );
						}
					elseif( intval( $pages[0] ) > intval( $_POST["target"] ) ) {
						$newPage = ( intval( $pages[0] )+intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )+intval( $_POST["slotnumber"] ) );
						}
					else {
						$newPage = implode( "-", $pages );
						}
					}
				if( $_POST['orient'] == "before" ) {
					if( intval( $pages[0] ) > intval( $_POST["target"] ) && intval( $pages[1] ) <= intval( $_POST["target"] ) ) {
						$newPage = $pages[0]."-".( intval( $pages[1] )+intval( $_POST["slotnumber"] ) );
						}
					elseif( intval( $pages[0] ) >= intval( $_POST["target"] ) ) {
						$newPage = ( intval( $pages[0] )+intval( $_POST["slotnumber"] ) )."-".( intval( $pages[1] )+intval( $_POST["slotnumber"] ) );
						}
					else {
						$newPage = implode( "-", $pages );
						}
					}
				}
				
			$log .= " oldalszám módosítva: ".$newPage.", ";
			if( $newPage != -1 ) {
				sql_update( "ads", "uploaded='".$newPage."'", "id='".$ads[$i]["id"]."'" );
				}
			}	
		
		// PAGEINFO ELLENŐRZÉSE, ELTOLÁSA
		$log .= "\n";
		$command = "page".( $_POST['orient'] == "after" ? ">" : ">=" )."'".$_POST["target"]."'";
		$pageinfo = sql_aget( "pageinfo", "(type='magazine' OR type='ad') AND code='".$magazine."' AND issue='".$issue."' AND ".$command." ORDER BY `page` DESC", "*" );		
		$log .= "\n Pageinfo csesztetése:";
		for( $i = 0; $i < count( $pageinfo ); $i++ ) {
			$log .= "\n   -Eredeti oldalszáma: ".$pageinfo[$i]["page"].", ";
			$newPage = intval( $pageinfo[$i]["page"] ) + intval( $_POST["slotnumber"] );
			$log .= "módostott oldalszám: ".$newPage;
			sql_update( "pageinfo", "page='".$newPage."'", "id='".$pageinfo[$i]["id"]."'" );
			$file = "../packages/".$magazine."/".$issue;
			switch( $pageinfo[$i]["type"] ) {
				case 'ad':
					$file .= "/_ads/".str_pad(intval( $pageinfo[$i]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$i]["pack_id"]."_ad_preview";
					break;
				
				case 'magazine':
					$temp = sql_get( "packages", "id='".$pageinfo[$i]["pack_id"]."'", "directory" );
					
					$file .= "/".$temp[0][0]."/".str_pad(intval( $pageinfo[$i]["page"] ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$i]["pack_id"]."_".$pageinfo[$i]["state"]."preview";
					break;
				}
			$log .= "\n   -Eredeti fájlnév: ".$file.", ";
			$file2 = "../packages/".$magazine."/".$issue;
			switch( $pageinfo[$i]["type"] ) {
				case 'ad':
					$file2 .= "/_ads/".str_pad(intval( $newPage ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$i]["pack_id"]."_ad_preview";
					break;
				
				case 'magazine':
					$temp = sql_get( "packages", "id='".$pageinfo[$i]["pack_id"]."'", "directory" );
					$file2 .= "/".$temp[0][0]."/".str_pad(intval( $newPage ), 3, '0', STR_PAD_LEFT)."_".$pageinfo[$i]["pack_id"]."_".$pageinfo[$i]["state"]."preview";
					break;
				}
			$log .= "\n   -Módostott fájlnév: ".$file2;
			rename( $file.".jpg" , $file2.".jpg" );
			rename( $file.".pdf" , $file2.".pdf" );
			}
		toSwitch( 'new_publication' , 'publications|'.$publication["id"], $magazine.'_'.$issue, 'issueData' );

		$names = array( 'type', 'start', 'orient', 'size', 'time', 'user', 'magazine', 'issue' );
		$values = array( $_POST["method"], $_POST["target"], $_POST["orient"], $_POST["slotnumber"], time(), $_SESSION['intra_user'], $magazine, $issue );
		sql_add( 'issueManagement_log', $names, $values );
		
		$result = array( "", $log );
		}

print json_encode( $result );
	
?>