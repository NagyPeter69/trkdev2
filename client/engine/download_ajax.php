<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( 'switchAPI.php' );
	include_once( 'dynaAPI.php' );
	include_once('../lang/en.php');

	// See client/plugins/pubsApply.php's 2026-09-05 fix - this file had no
	// authentication check at all despite performing real page-approval
	// writes and Switch pushes.
	$user = sql_get( 'accounts', 'id="'.( $_SESSION['intra_user'] ?? '' ).'"', '*' );
	if( empty( $user[0][0] ) ) {
		print json_encode( array( array( "Unauthorized" ) ) );
		exit;
		}

	if( isset( $_GET['type'] ) && $_GET['type'] != '' ) {
		$pages = $_POST['pageselector'];
		$states =  $_POST['state'];
		$pub_id = $_GET['id'];
		$pub = sql_get( 'publications', 'id="'.$pub_id.'"', '*' );
		$issue = $pub[0][10];
		$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );

		// A job with FlatplanStages==1 has only one flatplan - the fin bit
		// some of its pages carry isn't a second stage to filter these
		// queries by (see page_pdf-handler.php's $stages1 handling and
		// SYSTEM_STATE.md's domain-model section). Without this, bulk
		// actions here (approve/reject/download/...) silently skip any
		// selected page whose fin doesn't match $_GET['alter'] - no error,
		// the page is just quietly left out, which is exactly what made
		// "approve all" on a Cover part (cover page fin=0, its ad slots
		// fin=1) approve the ads but leave the cover page untouched.
		$stages1 = false;
		if( !empty( $magazine[0][3] ) ) {
			$wfXml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
			$wfXpath = $wfXml->xpath('/Publications');
			foreach( $wfXpath as $wfTemp ) {
				for( $wfI = 0; $wfI < count( $wfTemp->Item ); $wfI++ ) {
					if( $wfTemp->Item[$wfI]->Code == $magazine[0][3] )
						break;
					}
				}
			$stages1 = ( (string) $wfXml->Item[$wfI]->FlatplanStages == "1" and (string) $wfXml->Item[$wfI]->Workflow != "Hybrid" );
			}

		$path = "/var/www/html/client/packages/".$magazine[0][3]."/".$issue;
		$abspath = "packages/".$magazine[0][3]."/".$issue;
		$jpegPath = "packages/".$magazine[0][3]."/".$issue;
		$files = array();
		
		$pack_info = array();
		
		for ($i = 0; $i < count($pages); $i++) {
			if( $stages1 && ( $_GET['alter'] == "NOR" or $_GET['alter'] == "FIN" or $_GET['alter'] == "" ) )
				$pack = sql_get('pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$pages[$i].'" AND issue="'.$issue.'" AND code="'.$magazine[0][3].'" AND state="'.$states[$i].'" AND part="'.$_GET["part"].'" LIMIT 1', '*' );
			elseif( $_GET['alter'] == "NOR" )
				$pack = sql_get('pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$pages[$i].'" AND issue="'.$issue.'" AND code="'.$magazine[0][3].'" AND state="'.$states[$i].'" AND part="'.$_GET["part"].'" LIMIT 1', '*' );
			elseif( $_GET['alter'] == "FIN" )
				$pack = sql_get('pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$pages[$i].'" AND issue="'.$issue.'" AND code="'.$magazine[0][3].'" AND state="'.$states[$i].'" AND fin="1" AND part="'.$_GET["part"].'" LIMIT 1', '*' );
			else
				$pack = sql_get('pageinfo', 'type="'.$_GET['alter'].'" AND page="'.$pages[$i].'" AND issue="'.$issue.'" AND code="'.$magazine[0][3].'" AND state="'.$states[$i].'" AND fin="0" AND part="'.$_GET["part"].'" LIMIT 1', '*' );

			if( $pack[0][6] == "ad" ) {
				$files[] = "_ads/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$pack[0][1]."_ad_preview.pdf";
				}
			elseif( $pack[0][6] == "magazine" ) {
				$subDir = sql_get('packages', 'id="'.$pack[0][1].'"', '*' );
				// A 1-stage job's file lives wherever page_pdf-handler.php
				// actually stored it based on this row's own fin (index
				// 11), not wherever the requested alter would imply.
				if( $stages1 ? $pack[0][11] == 1 : $_GET['alter'] == "FIN" ) {
					$files[] = $subDir[0][4]."/FIN/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$pack[0][1]."_".$states[$i]."preview.pdf";
					}
				else {
					$files[] = $subDir[0][4]."/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$pack[0][1]."_".$states[$i]."preview.pdf";
					}
				}
			else {
				$files[] = "_".$pack[0][6]."/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$pack[0][1]."_".$states[$i]."preview.pdf";
				}

			$packs = sql_get('packages', 'publication_id="'.$pub_id.'" AND starting_page != "" ORDER BY `starting_page` ASC', '*' );
			$pack_info[] = $pack[0];
			/*for ($p = 0; $p < count( $packs ); $p++) {
				$check = explode( "-", $packs[$p][3] );
				$start = intval( $check[0] );
				if( $check[1] != '' )
					$end = intval( $check[1] );
				else
					$end = intval( $check[0] );
				
				if( $pages[$i] >= $start && $pages[$i] <= $end ) {
					$files[] = $packs[$p][4]."/".str_pad($pages[$i], 3, '0', STR_PAD_LEFT)."_".$packs[$p][0]."_preview.pdf";
					break;
					} 
				}*/
			}
		}
	
	if( $_GET['type'] == 'proof' ) {
		error_log( count( $pages ) );
		for ($i = 0; $i < count($pages); $i++) {
			error_log( json_encode( $files ) );
			$client = sql_get( 'publishers', 'id="'.$pub[0][1].'"', '*' );
			$file = $path."/".$files[$i];
			
			//ÚJ
		//	if( $client[0][0] == "35" ) {
				$array = array(
					"event" => "proof",
					"client" => $client[0][1],
					"jobCode" => $magazine[0][3],
					"issue" => $issue,
					"pageNum" => str_pad($pages[$i], 3, '0', STR_PAD_LEFT),
					"pageType" => $_GET['alter'],
					"pageVersion" => ( $states[$i] == "" ? "-baseversion-" : substr( $states[$i], 0, -1) ),
					);
				
				error_log( json_encode( $array ) );
				
				$sfile = explode( "/", $files[$i] );
				$sfilename = end($sfile);
				array_pop($sfile);
				$sfilepath = implode( "/", $sfile );
				
				error_log( $abspath."/".$sfilepath."/".$sfilename );
				
				$file = array( 
					"name" => $sfilename,
					"path" => $abspath."/".$sfilepath,
					);
				
				$newname = $sfilename;
				error_log( $pack_info[$i][16] );
				if( !empty( $pack_info[$i][16] ) ) {
					$newname = $pack_info[$i][17];
					}
				
				$error = SwitchSend_Rename( $array, $file, $newname );				
			/*	}
			
			//RÉGI
			else {
			$array["client"] = $client[0][1];
			$array["jobCode"] = $magazine[0][3];
			$array["issue"] = $issue;
			$array["event"] = 'proof';
			//$array["description"] = "NA";
			$array["pageNum"] = str_pad($pages[$i], 3, '0', STR_PAD_LEFT);
			$array["pageType"] = $_GET['alter'];
			$array["pageVersion"] = ( $states[$i] == "" ? "-baseversion-" : substr( $states[$i], 0, -1) );
      
			$myxml = array_to_xml( $array, 'eventComm' );
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($myxml);
			$dom->formatOutput = true;
			
			$counter = get_counter('..');
			$saveTo = 'C_Hotfolders/messages/message_'.$counter;
			$sftp = ftp_conn();
			$sftp->put( $saveTo.'.pdf', $file, NET_SFTP_LOCAL_FILE );
			$sftp->put( $saveTo.'.xml', $dom->saveXML()  );	
			inc_counter('..');
			}*/
			
			sql_update( "pageinfo", "proofCounter='".($pack_info[$i][13]+1)."'", "id='".$pack_info[$i][0]."'" );
			}
		}

	elseif( $_GET['type'] == 'light_reject' ) {
		$hotlink = sql_get( 'hotlinks', 'hashtag="'.$_GET['hash'].'" LIMIT 1', '*' );
		
		for ($i = 0; $i < count( $pages ); $i++) {
			$check = sql_get( "hotlinks_log", "job_id='".$pub[0][0]."' AND ( action='accept' OR action='reject' ) AND info='".$pages[$i]."' AND version", "*" );
			$pageinfo = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND page='".$pages[$i]."' AND code='".$magazine[0][3]."' AND issue='".$issue."' AND state='".$states[$i]."' AND fin='1' AND part='".$_GET["part"]."'", "*" );
			if( $check[0][0] == "" && ( $pageinfo[0][4] == "0" or $pageinfo[0][4] == "1" ) ) {	
				$names = array( 'job_id', 'action', 'info', 'time', 'hotlink_id', 'version' );
				$values = array( $pub[0][0], 'reject', $pages[$i], time(), $hotlink[0][0], $pageinfo[0][3] );
				sql_add( 'hotlinks_log', $names, $values );
				}
			} 
		
		$result = $saveTo;		
		}

	elseif( $_GET['type'] == 'reject' ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		for ($i = 0; $i < count($pages); $i++) {
			$check = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND page='".$pages[$i]."' AND code='".$magazine[0][3]."' AND issue='".$issue."' AND state='".$states[$i]."' AND part='".$_GET["part"]."'", "*" );
			if( $check[0][0] != "" ) {	
				sql_update( 'pageinfo', 'status="3"', 'id="'.$check[0][0].'"' );
				$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
				$values = array( $user[0][0], 'rejectPage', $pub[0][1], $pub[0][2], $pub[0][10], intval( $pages[$i] ), time(), '' );
				sql_add( 'action_log', $names, $values );
				}
			} 
		
		$result = $saveTo;		
		}
		
	elseif( $_GET['type'] == 'cancel' ) {
		for ($i = 0; $i < count($pages); $i++) {
			$check = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND page='".$pages[$i]."' AND code='".$magazine[0][3]."' AND issue='".$issue."' AND status='2' AND state='".$states[$i]."' AND part='".$_GET["part"]."'", "*" );
			if( $check[0][0] != "" ) {		
				sql_update( 'pageinfo', 'status="0"', 'id="'.$check[0][0].'"' );
				$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
				$values = array( $user[0][0], 'cancelApprove', $pub[0][1], $pub[0][2], $pub[0][10], intval( $pages[$i] ), time(), '' );
				sql_add( 'action_log', $names, $values );
				}
			} 
		
		$result = $saveTo;		
		}

	elseif( $_GET['type'] == 'light_accept' ) {
		$hotlink = sql_get( 'hotlinks', 'hashtag="'.$_GET['hash'].'" LIMIT 1', '*' );

		for ($i = 0; $i < count( $pages ); $i++) {
			$check = sql_get( "hotlinks_log", "job_id='".$pub[0][0]."' AND ( action='accept' OR action='reject' ) AND info='".$pages[$i]."'", "*" );
			$pageinfo = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND page='".$pages[$i]."' AND code='".$magazine[0][3]."' AND issue='".$issue."' AND state='".$states[$i]."' AND fin='1' AND part='".$_GET["part"]."'", "*" );

			/*$last_update = sql_get( "action_log", "job='".$pub[0][0]."' AND action='updatePage' AND target='".$pageinfo[0][3]."' ORDER BY `date` DESC LIMIT 1", "*" );
			$date = ( $last_update[0][6] != "" ? $last_update[0][6] : 0 );
			$allowed = true;
			$check_flag = sql_get( "comment_log", "job='".$pub[0][0]."' AND action='newComment' AND info='".$pageinfo[0][3]."' AND date > '".$date."' ORDER BY `ID` DESC LIMIT 1", "*" );
			if( $check_flag[0][0] != "" ) {
				$time = date( "Y-m-d H:i:s" , $check_flag[0][6] );
				logToFile( "monitoring2", "job_id='".$pub[0][0]."' AND time = '".$time."'" );
				$checker = sql_get( "comments", "job_id='".$pub[0][0]."' AND time = '".$time."' AND parent='0'", "*" );
				if( $checker[0][0] != "" ) {
					$allowed = false;
					}
				}*/



			if( $check[0][0] == "" && ( $pageinfo[0][4] == "0" or $pageinfo[0][4] == "1" ) ) {
				$names = array( 'job_id', 'action', 'info', 'time', 'hotlink_id', 'version' );
				$values = array( $pub[0][0], 'accept', $pages[$i], time(), $hotlink[0][0], $pageinfo[0][3] );
				sql_add( 'hotlinks_log', $names, $values );
				}
			} 
		
		$result = $saveTo;		
		}

	elseif( $_GET['type'] == 'accept' ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		for ($i = 0; $i < count($pages); $i++) {
			if( $stages1 ) {
				$check = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND page='".$pages[$i]."' AND code='".$magazine[0][3]."' AND issue='".$issue."' AND (status='0' OR status='1' OR status='3' OR status='2' ) AND state='".$states[$i]."' AND part='".$_GET["part"]."'", "*" );
				}
			else {
				$fin = ( $_GET["alter"] == "FIN" ? "1" : "0" );
				$check = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND page='".$pages[$i]."' AND code='".$magazine[0][3]."' AND issue='".$issue."' AND (status='0' OR status='1' OR status='3' OR status='2' ) AND fin='".$fin."' AND state='".$states[$i]."' AND part='".$_GET["part"]."'", "*" );
				}
			if( $check[0][0] != "" ) {
        		$publisher = sql_get( "publishers", "id='".$pub[0][1]."'", "name" );

				$array = array(
					"event" => "page_approved",
					"client" => $publisher[0][0],
					"jobCode" => $check[0][7],
					"issue" => $check[0][2],
					"description" => $pages[$i],
					"remark" => $user[0][7],
					"date" => date( "Y-m-d\TH:i:s", time() ),
					"pageNum" => $check[0][5],
					//"pageNum" => str_pad($pages[$i], 3, '0', STR_PAD_LEFT),
					"pageType" => $_GET['alter'],
					"pageVersion" => ( $states[$i] == "" ? "-baseversion-" : substr( $states[$i], 0, -1 ) ),
					);
				
				$sfile = explode( "/", $files[$i] );
				$sfilename = end($sfile);
				array_pop($sfile);
				$sfilepath = implode( "/", $sfile );
			
				$file = array( 
					"name" => $sfilename,
					"path" => $abspath."/".$sfilepath,
					);
				
				error_log( "path: ".$file["path"]."/".$sfilename );
				
				$newname = $sfilename;
				if( !empty( $check[0][17] ) ) {
					$newname = $check[0][17];
					}
				error_log("File neve a switchen: ".$newname );
				// Approving a page needs Switch to know about it (it renames
				// the file at Switch's end), but the user shouldn't have to
				// wait on that network round-trip - a bulk approve of dozens
				// of pages used to block the whole request on that many
				// sequential SwitchSend_Rename() calls (5s connect + 15s
				// timeout each, worst case). The pageinfo/action_log update
				// below (what the user actually sees) still happens
				// immediately; the Switch notification itself is queued and
				// delivered in the background by
				// client/cron/switch_sync_worker.php instead - same durable
				// queue+retry pattern already used for
				// XMLUpload2()/SendPmdXmlToSwitch() (see SYSTEM_STATE.md) -
				// QueueSwitchRetry() (xml_handler.php) is that same path's
				// own enqueue helper, reused here rather than writing to
				// switch_sync_queue directly, since it also
				// mysqli_real_escape_string()s both fields - sql_add()
				// itself does zero escaping, and this payload's JSON
				// routinely contains backslash escapes from json_encode()
				// (e.g. í for an accented "í" in a package directory
				// name, \/ for path separators) that MySQL's own string-
				// literal parser silently mangles - it drops the backslash
				// on any escape sequence it doesn't itself recognize -
				// if the string is passed through unescaped, corrupting
				// the stored JSON (confirmed live: a real queued job had
				// "címlap" stored as "cu00edmlap").
				QueueSwitchRetry( 'switch_send_rename', array( 'datas' => $array, 'file' => $file, 'newname' => $newname ) );
				$switchQueued = true;
				
				/*$array["client"] = $publisher[0][0];
				$array["jobCode"] = $check[0][7];
				$array["issue"] = $check[0][2];
				$array["event"] = 'page_approved';
				$array["description"] = $pages[$i];
				$array["remark"] = $user[0][7];
				$array["date"] = date( "Y-m-d\TH:i:s", time() );
				$array["pageNum"] = str_pad($pages[$i], 3, '0', STR_PAD_LEFT);
				$array["pageType"] = $_GET['alter'];
				$array["pageVersion"] = ( $states[$i] == "" ? "-baseversion-" : substr( $states[$i], 0, -1 ) );
		
				$myxml = array_to_xml( $array, 'eventComm' );
				$dom = new DOMDocument();
				$dom->preserveWhiteSpace = false;
				$dom->loadXML($myxml);
				$dom->formatOutput = true;
		
				$counter = get_counter('..');
				$saveTo = 'C_Hotfolders/messages/message_'.$counter;
				inc_counter('..');
				
				$file = $path."/".$files[$i];
				$sftp = ftp_conn("../");
				$sftp->put( $saveTo.'.pdf', $file, NET_SFTP_LOCAL_FILE );
				$sftp->put( $saveTo.'.xml', $dom->saveXML()  );*/
				
				//FTP TESZT
				/*file_put_contents( 'message_'.$counter.'.xml', $dom->saveXML());
				
				$ftp_server = "192.168.3.10";
				$ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
				$login = ftp_login($ftp_conn, "colorcom", "mocroloc" );
				ftp_put($ftp_conn, 'Teszt/TRK/message_'.$counter.'.pdf', $file, FTP_BINARY);
				ftp_put($ftp_conn, 'Teszt/TRK/message_'.$counter.'.xml', 'message_'.$counter.'.xml', FTP_BINARY);*/
				
					
				sql_update( 'pageinfo', 'status="2"', 'id="'.$check[0][0].'"' );
				$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
				$values = array( $user[0][0], 'approvePage', $pub[0][1], $pub[0][2], $pub[0][10], intval( $pages[$i] ), time(), $_GET['alter'], $states[$i] );
				sql_add( 'action_log', $names, $values );
				}
			}

		// Don't make the approving user wait out the next cron tick (up to
		// 60s) for pages queued above to actually reach Switch - kick the
		// worker in the background right away so the common (success) case
		// is near-instant. Fire-and-forget, same pattern as the sendmail
		// kick in vflatplan_ajax.php: if this fails to spawn or the worker
		// is slow, the cron schedule is still the safety net that
		// eventually delivers it.
		if( !empty( $switchQueued ) ) {
			shell_exec( 'php '.__DIR__.'/../cron/switch_sync_worker.php > /dev/null 2>&1 &' );
			}

		$result = $saveTo;
		}
	
	elseif( $_GET['type'] == 'jpg' ) {
		$loc = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".zip";
		$terminalPath = "/var/www/html/client";

		// This feature is normally used to grab many or all of an issue's
		// pages at once. r3 rendering is the bottleneck (~2s/page) - doing
		// it one page at a time in a single request routinely exceeded
		// php-fpm's request_terminate_timeout (30s, i.e. ~15 pages) and got
		// the whole download killed outright with no file ever produced.
		// Render up to $maxConcurrent pages at a time instead, each in its
		// own process (render_page_worker.php via proc_open()) - 4, to
		// match this box's core count (nproc) without oversubscribing a
		// CPU-bound workload. Only once every page has actually finished
		// rendering do we build the zip, exactly as before.
		$jobs = array();
		for ($i = 0; $i < count($files); $i++) {
			$name = $magazine[0][3]."_".$issue."_".$pages[$i].".jpg";
			$jobs[] = array(
				"name" => $name,
				"from" => $terminalPath."/".$jpegPath."/".$files[$i],
				"to"   => $terminalPath."/temp/_zip/".$name,
				"page" => $pages[$i],
				);
			}

		$maxConcurrent = 4;
		$queue = $jobs;
		$running = array();
		while( count( $queue ) > 0 || count( $running ) > 0 ) {
			while( count( $running ) < $maxConcurrent && count( $queue ) > 0 ) {
				$job = array_shift( $queue );
				$cmd = array( "php", __DIR__."/render_page_worker.php", $job["from"], $job["to"], $pub_id, $job["page"] );
				$proc = proc_open( $cmd, array( 1 => array("pipe","w"), 2 => array("pipe","w") ), $pipes );
				$running[] = array( "proc" => $proc, "pipes" => $pipes );
				}

			foreach( $running as $key => $r ) {
				$status = proc_get_status( $r["proc"] );
				if( !$status["running"] ) {
					fclose( $r["pipes"][1] );
					fclose( $r["pipes"][2] );
					proc_close( $r["proc"] );
					unset( $running[$key] );
					}
				}
			$running = array_values( $running );

			if( count( $running ) > 0 ) {
				usleep( 100000 );
				}
			}

		$zip2 = new ZipArchive();
		$zip2->open( "../temp/".$loc, ZipArchive::CREATE );
		foreach( $jobs as $job ) {
			$zip2->addFile( $job["to"], $job["name"] );
			}
		$zip2->close();
		$result = $loc;
		}
		
	elseif( $_GET['type'] == 'multi' ) {
		$loc = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".zip";
		$loc2 = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".pdf";
		$zip2 = new ZipArchive();
		$zip2->open( "../temp/".$loc, ZipArchive::CREATE );
		$removable = array();
		for ($i = 0; $i < count($files); $i++) {
			$name = $magazine[0][3]."_".$issue."_".$pages[$i].".pdf";
			dynaNormalizePdf( $path.'/'.$files[$i], '../temp/_zip/'.$name );

			$zip2->addFile( '../temp/_zip/'.$name, $name );
			$removable[] ='../temp/_zip/'.$name;
			}
		$zip2->close();
		
		foreach( $removable as $remove ) {
			unlink( $remove );
			}
		
		$result = $loc;
		}
		
	elseif( $_GET['type'] == 'one' ) {
		// A merged single PDF can only ever carry one output intent -
		// mixing pages from Parts with different color standards would
		// produce a PDF with a genuinely ambiguous/conflicting output
		// intent at the end. Refuse rather than silently hand back a
		// broken file. "multi" (zip of separate PDFs) and "jpg" (each page
		// independently normalized to sRGB) don't merge pages together, so
		// neither of them has this problem.
		$standards = array();
		for( $i = 0; $i < count( $pages ); $i++ ) {
			$standards[ partDetect( $pub_id, $pages[$i], "color", $_GET["part"] ) ] = true;
			}

		if( count( $standards ) > 1 ) {
			$result = array( "error" => "colorMismatch" );
			}
		else {
			if( count( $pages ) > 1 )
				$loc = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".pdf";
			else
				$loc = $magazine[0][3]."_".$issue."_".$pages[0].".pdf";

			$data = array();
			$data["loc"] = $loc;
			$data["path"] = $path;
			$data["files"] = $files;

			include( "/var/www/html/dynAPI/tracker/one_local.php" );

			$result = $loc;
			}
		}
	
	
print json_encode( $result );
	
?>