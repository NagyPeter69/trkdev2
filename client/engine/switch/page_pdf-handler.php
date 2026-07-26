<?PHP

require_once( "/var/www/html/engine/r3client.php" );

error_log( "PAGEINFO LOG START");

$status = $_POST["result"];
$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];
$description = str_replace( " ", "", $_POST["description"] );
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

	// Hybrid workflow has no flatplan stages: its single flatplan IS the
	// FINAL one, so whatever stage designation Switch sent with this page
	// is ignored and the page lands as FIN. Everything downstream follows
	// from $pageState alone (the /FIN file subdirectory, fin=1 in pageinfo,
	// status, which stage view shows it), so coercing it here is the whole
	// mechanism. Page TYPE (editorial vs ad) is a separate variable and
	// stays untouched.
	$wfXml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
	$wfXpath = $wfXml->xpath('/Publications');
	foreach( $wfXpath as $wfTemp ) {
		for( $wfI = 0; $wfI < count( $wfTemp->Item ); $wfI++ ) {
			if( $wfTemp->Item[$wfI]->Code == $jcode )
				break;
			}
		}
	if( (string) $wfXml->Item[$wfI]->Workflow == "Hybrid" ) {
		error_log( "Hybrid workflow: pageState '".$pageState."' coerced to FIN" );
		$pageState = "FIN";
		}
	// A job configured with FlatplanStages=1 has only the one, unnamed
	// flatplan (see flatplan_ajax.php's refreshPageStatus approve-control
	// gate, which only allows approvals on fpver=="" when $fpstages==1).
	// Switch still tags individual submissions NOR/FIN on its own terms
	// regardless of our stage count (ad submissions in particular are
	// conventionally always sent as FIN) - that tag is real Switch data
	// and isn't relabelled, but for a 1-stage job it must not be used to
	// split a page's history across two separate fin buckets: every
	// lookup/conflict-check below is scoped to match on the page/part
	// alone, ignoring fin, whenever $stages1 is true.
	$stages1 = ( (string) $wfXml->Item[$wfI]->FlatplanStages == "1" and (string) $wfXml->Item[$wfI]->Workflow != "Hybrid" );

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
	$description = str_replace( " ", "", $_POST["description"] );
	//$description = letter_change( $description );
	$pageNum = intval( $_POST["pageNum"] );
	$page = intval( $_POST["pageNum"] );
	$pageWidth = intval( $_POST["pageWidth"] );
	if( $event == 'page_thumbnail' ) $ext = "jpg";
	if( $event == 'page_pdf' ) $ext = "pdf";

	$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
	$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );

	// Switch reuses this SAME page_pdf event for the pdfToolbox preflight
	// report rather than a dedicated one - the only signal distinguishing
	// it from a real page resubmission is this filename suffix (confirmed
	// live 2026-07-24: the report for 001_PRHE2688 arrived as
	// fileName=001_PRHE2688_report, same event, same
	// jobCode/issue/page/part/state as the real page). Without this check
	// it fell through to the normal replace/version/archive/render path
	// below and got treated as a legitimate new version of the real page -
	// archiving the actual print page to _old and putting the report PDF
	// in its place. Must return before any of that runs.
	if( str_ends_with( $_POST["fileName"], "_report" ) ) {
		$pn = (string) $wfXml->Item[$wfI]->PageNumbering;
		$extra = ( $pn == "American" ) ? ' AND part="'.$_POST["part"].'"' : '';
		$tag = ( $pageVersion != "-baseversion-" and $pageVersion != "" ) ? $pageVersion."_" : "";

		// Store the report unconditionally, even if no matching pageinfo
		// row exists yet - Switch doesn't guarantee the report arrives
		// AFTER the real page (confirmed live 2026-07-24: on a retest, the
		// report for page 1 arrived first, before the page's row even
		// existed - the original version of this code only updated an
		// EXISTING row and silently dropped the flag when one didn't exist
		// yet). The new-pageinfo-row insert path below checks for this same
		// deterministic path and picks it up retroactively either way.
		$dir = TRKPATH.'/packages/'.$jcode.'/'.$issue.'/_preflight';
		if( !is_dir( $dir ) ) {
			$oldmask = umask(0);
			mkdir( $dir, 0777, true );
			umask($oldmask);
			}
		$reportName = str_pad( $page, 3, '0', STR_PAD_LEFT ).'_'.$_POST["part"].'_preflight.pdf';
		rename( $file, $dir.'/'.$reportName );

		if( $stages1 ) {
			$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" '.$extra, '*' );
			}
		elseif( $pageState == "FIN" ) {
			$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" AND fin="1" '.$extra, '*' );
			}
		else {
			$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" AND fin="0" '.$extra, '*' );
			}

		if( $pageInfo[0][0] != '' ) {
			sql_update( 'pageinfo', 'preflight_error="1", preflight_report="'.$reportName.'", preflight_origname="'.$_POST["fileName"].'.pdf"', 'id="'.$pageInfo[0][0].'"' );
			}

		return;
		}

	error_log( "namecalc elott" );
	if( (string) $wfXml->Item[$wfI]->Workflow == "Hybrid" ) {
		// Hybrid packages group by Part (Cover/Inside/etc.), not by a
		// description-derived article name - there are no "articles"
		// once the client just uploads a straight, pre-sliced print PDF.
		// Both $name (this file's own duplicate package-creation
		// fallback below, ~line 101) and $description (what
		// searchArticlePost() actually matches/creates packages by)
		// need to agree on the same key, so both become the Part code
		// Switch already sends. Booked ads (Adverts Page) arrive via a
		// separate Switch event, never through this Hybrid-coerced
		// path, so there's no real ad-name match to preserve here.
		$name = $_POST["part"];
		$description = $_POST["part"];
		}
	else {
		$name = nameCalculator2( $_POST );
		$name = str_replace( " ", "", $name );
		}
	error_log( $name );
	$result = searchArticlePost( $_POST, $description, $page );
	var_dump( $result );
	//die();
	
	if( count( $result ) == 3 ) {
		$hold = $result[0]; $pack_id = $result[1]; $type = $result[2];
		if( ( $pack_id == NULL or $pack_id == 0 ) and $event == 'page_pdf' ) {
			error_log("IF: 1");
			$pacakage_page = searchRange_array( $_POST );
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
			error_log( "PAGE_PDF DEBUG");
			
			error_log( $pn );
			$extra = "";
			if( $pn == "American" ) {
				$extra = "AND part='".$_POST["part"]."'";
				}
			
			if( $type == "alter" ) {
				$pageInfo = sql_get( 'pageinfo', 'type="'.$alter[1].'" AND code="'.$jcode.'" AND issue="'.$issue.'" AND page="'.$page.'" '.$extra.'', '*' );
				}
			// A 1-stage job's fin bit is real Switch-reported data (kept as
			// sent - see the $stages1 comment above) but must not be used to
			// decide which row THIS page's submission matches: matching
			// ignores fin entirely so a page's later submissions always
			// land on the same one row regardless of what fin either one
			// carries, instead of the job accumulating a parallel fin=0 and
			// fin=1 row per page.
			else if( $stages1 ) {
				$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" '.$extra.'', '*' );
				}
			else {
				if( $pageState == "FIN" ) {
					$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" AND fin="1" '.$extra.'', '*' );
					}
				else {
					$pageInfo = sql_get( 'pageinfo', '`type`!="PRE" AND `type`!="PSTR" AND `code`="'.$jcode.'" AND `issue`="'.$issue.'" AND `page`="'.$page.'" AND `state`="'.$tag.'" AND fin="0" '.$extra.'', '*' );
					}
				}

			if( $type != "alter" && $tag == "" && ( $stages1 or $pageState != "FIN" ) ) {
				$finClause = ( $stages1 ? '' : 'AND fin="0" ' );
				$checker = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND code="'.$jcode.'" AND issue="'.$issue.'" AND pack_id!="'.$pack_id.'" AND page="'.$page.'" '.$finClause.$extra, '*' );
				if( $checker[0][0] != "" ) {
					echo $check;
					$remove[] = fileRemove( $checker[0], $path, $ext );
					if( $checker[0][6] == "ad" and $event == 'page_pdf' ) {
						$sql_remove[count( $remove )-1] = $checker[0][0];
						}
					}
				else {
					if( !$stages1 && $pageState == "FIN" ) {
						$checker = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND code="'.$jcode.'" AND issue="'.$issue.'" AND pack_id!="'.$pack_id.'" AND page="'.$page.'" AND fin="1" '.$extra.'', '*' );
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
			
			error_log( $pageInfo[0][0] );
			if( $event == 'page_pdf' ) {
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
					
					sql_update( 'pageinfo', 'status="'.$s.'"', 'id="'.$pageInfo[0][0].'"' );
					
					error_log( "RÉGI HELY: ");
					error_log( $prevDir."/".$oldFile.".jpg" );
					error_log( "ÚJ HELY: ");
					error_log( $path."/_old".( $pageInfo[0][11] == "1" ? "/FIN" : ( $pageInfo[0][6] == "PRE" ? "/_PRE" : "" ) )."/".$oldFile."_".$pageInfo[0][3].".jpg" );
					
					if( copy( $prevDir."/".$oldFile.".jpg", $path."/_old".( $pageInfo[0][11] == "1" ? "/FIN" : ( $pageInfo[0][6] == "PRE" ? "/_PRE" : "" ) )."/".$oldFile."_".$pageInfo[0][3].".jpg" ) ) {
						// A fresh page replaces whatever preflight result the
						// PREVIOUS version of this page had - any past error
						// no longer applies to what's actually on disk now.
						// A "_report" submission for the new version (see the
						// str_ends_with() check above) re-flags this row if it
						// fails too, but until/unless that arrives the marker
						// must not survive a resubmission - otherwise a page
						// the client already fixed would keep showing a stale
						// error forever.
						if( $type == "alter" )
							sql_update( 'pageinfo', 'version="'.( intval( $pageInfo[0][3] )+1 ).'", status="'.$s.'", pack_id="'.$pack_id.'", type="'.$alter[1].'", view="", preflight_error="0", preflight_report="", preflight_origname="", boxes=""', 'id="'.$pageInfo[0][0].'"' );
						else
							sql_update( 'pageinfo', 'version="'.( intval( $pageInfo[0][3] )+1 ).'", status="'.$s.'", pack_id="'.$pack_id.'", type="'.$type.'", view="", preflight_error="0", preflight_report="", preflight_origname="", boxes=""', 'id="'.$pageInfo[0][0].'"' );
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
						$pT = ( $pageState  == "FIN" ? "FIN" : ( $pageType == "NOR" ? "NOR" : "PRE"  ) );
						$values = array( '0', 'updatePage', $p_id[0][1], $p_id[0][2], $p_id[0][10], $pageNum, time(), $pT, $pageVersion );
						sql_add( 'action_log', $names, $values );							
						
						$a = $path.'/_old'.( $pageInfo[0][11] == "1" ? "/FIN" : ( $pageInfo[0][6] == "PRE" ? "/_PRE" : "" ) ).'/'.$oldFile.'_'.$pageInfo[0][3].'.pdf';
						$b = $prevDir.'/'.$oldFile.'.pdf';
						
						echo "<br>AUTOCOMPARE ".$a." ".$b."<br>";
						$end = r3run( 'AUTOCOMPARE', array(), $a, $b );
						file_put_contents( '/var/www/html/client/tests/B-E.out', $end );
						echo $end."<br>";
						sql_update( 'pageinfo', 'lastdifference="'.$end.'"', 'id="'.$pageInfo[0][0].'"' );
						$id = $pageInfo[0][0];
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
					
					$names[] = "part";
					$values[] = $_POST["part"];

					$names[] = "origname";
					$values[] = $_POST["fileName"].".pdf";

					// A preflight "_report" submission for this same page
					// may have already arrived and stored its file before
					// this row even existed (see the str_ends_with() check
					// above) - pick it up here rather than losing it, since
					// that report update had nothing to attach itself to
					// yet at the time it ran.
					$reportName = str_pad( $page, 3, '0', STR_PAD_LEFT ).'_'.$_POST["part"].'_preflight.pdf';
					if( is_file( TRKPATH.'/packages/'.$jcode.'/'.$issue.'/_preflight/'.$reportName ) ) {
						$names[] = "preflight_error";
						$values[] = "1";
						$names[] = "preflight_report";
						$values[] = $reportName;
						// The "_report" submission's own request never had a
						// pageinfo row to read this page's real filename from,
						// so it couldn't record the true original name itself -
						// reconstruct it from THIS request's fileName instead,
						// per the established "<real name>_report" convention.
						$names[] = "preflight_origname";
						$values[] = $_POST["fileName"]."_report.pdf";
						}


					$id = sql_add( 'pageinfo', $names, $values );
					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
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
					
					// American-numbering page numbers are part-relative and
					// collide across Parts, so partDetect()'s page-range
					// matching can't resolve anything meaningful for them
					// (Parts define page numbering by count, not absolute
					// position) - passing the part Switch actually submitted
					// resolves the color standard directly and unambiguously
					// for both numbering schemes; harmless no-op for a
					// European job where $_POST["part"] is empty.
					$color = partDetect( $p_id[0][0], $page, "color", $_POST["part"] );
					
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
						if( empty( $temp[0]["id"] ) ) {
							// Same Hybrid name-corruption gap as
							// searchArticlePost() (engine.php) - $description
							// was already overwritten with the Part code
							// above for Hybrid jobs, so this name match can
							// never succeed for a booked ad. Fall back to
							// matching by the position the ad was most
							// recently booked to (booked_page/booked_part,
							// rewritten on every push_ad in ajax.php).
							$adCondition = "pub_id='".$p_id[0][0]."' AND publisher='".$p_id[0][1]."' AND booked_page='".$pageNum."'";
							if( !empty( $_POST["part"] ) ) {
								$adCondition .= " AND booked_part='".$_POST["part"]."'";
								}
							$temp = sql_aget( "ads", $adCondition, "*" );
							}
						error_log( print_r( $temp, true ) );
						if( !empty( $temp[0]["id"] ) ) {
							if( $temp[0]["size"] == "2/1" ) {
								$upage = $pageNum;
								if( is_numeric( $temp[0]["uploaded"] ) ) {
									if( $temp[0]["uploaded"] % 2 == 0 ) {
										$upage = $temp[0]["uploaded"]."-".$pageNum;
										}
									else {
										$upage = $pageNum."-".$temp[0]["uploaded"];
										}
									}
								
								sql_update( "ads", "uploaded='".$upage."'", "id='".$temp[0]["id"]."'" );
								}								
							else {
								sql_update( "ads", "uploaded='".$pageNum."'", "id='".$temp[0]["id"]."'" );
								}
							}
						}
					}
			//die();
			}
		}
	}

error_log( "PAGEINFO LOG END");
	
?>