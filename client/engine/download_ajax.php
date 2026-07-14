<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( 'switchAPI.php' );
	include_once('../lang/en.php');

	if( isset( $_GET['type'] ) && $_GET['type'] != '' ) {
		$pages = $_POST['pageselector'];
		$states =  $_POST['state'];
		$pub_id = $_GET['id'];
		$pub = sql_get( 'publications', 'id="'.$pub_id.'"', '*' );
		$issue = $pub[0][10];
		$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
		
		$path = "/var/www/html/client/packages/".$magazine[0][3]."/".$issue;
		$abspath = "packages/".$magazine[0][3]."/".$issue;
		$jpegPath = "packages/".$magazine[0][3]."/".$issue;
		$files = array();
		
		$pack_info = array();
		
		for ($i = 0; $i < count($pages); $i++) {	
			if( $_GET['alter'] == "NOR" )
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
				if( $_GET['alter'] == "FIN" ) {
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
			
				/*$names = array( "type", "data1", "data2", "data3", "data4", "date" );
				$values = array( "sendmail", "lightreject", $hotlink[0][13], $job[0][0], $pages[$i], time() );
				sql_add( 'shell_commands', $names, $values );
				shell_exec('php /var/www/standalone/cron/sendmail.php > /dev/null &');*/
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
				
				/*$names = array( "type", "data1", "data2", "data3", "data4", "date" );
				$values = array( "sendmail", "lightaccept", $hotlink[0][13], $job[0][0], $pages[$i], time() );
				sql_add( 'shell_commands', $names, $values );
				shell_exec('php /var/www/standalone/cron/sendmail.php > /dev/null &');*/		
				}
			} 
		
		$result = $saveTo;		
		}

	elseif( $_GET['type'] == 'accept' ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		for ($i = 0; $i < count($pages); $i++) {
			$fin = ( $_GET["alter"] == "FIN" ? "1" : "0" );
			$check = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND page='".$pages[$i]."' AND code='".$magazine[0][3]."' AND issue='".$issue."' AND (status='0' OR status='1' OR status='3' OR status='2' ) AND fin='".$fin."' AND state='".$states[$i]."' AND part='".$_GET["part"]."'", "*" );
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
				$error = SwitchSend_Rename( $array, $file, $newname );
				
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
		
		$result = $saveTo;
		}
	
	elseif( $_GET['type'] == 'jpg' ) {
		$loc = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".zip";
		$zip2 = new ZipArchive();
		$zip2->open( "../temp/".$loc, ZipArchive::CREATE );
		$terminalPath = "/var/www/html/client";
		$removable = array();
		
		for ($i = 0; $i < count($files); $i++) {			
			$name = $magazine[0][3]."_".$issue."_".$pages[$i].".jpg";
			$sizes = getBBox( $path.'/'.$files[$i], "", "trimbox" );
			$from = $terminalPath."/".$jpegPath."/".$files[$i];
			$to = $terminalPath."/temp/_zip/".$name;
			$generated = "../temp/_zip/".$name;
			
			$sizes["Width"] = pixel_( $sizes["Width"], 200 );
			$sizes["Height"] = pixel_( $sizes["Height"], 200 );
			$col = partDetect( $pub_id, $pages[$i] );
			
			$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].'  -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$col.'.icc '.$from.' $@ >'.$to.' 2>&1';
			//error_log( $command );
			// "cd r3" (relative to this script's own directory,
			// client/engine/) pointed at client/engine/r3/ - a JPG
			// cache/working directory, not where the actual r3 binary
			// lives. Every other r3 invocation in engine.php uses the
			// absolute /var/www/html/r3API/r3 path (see SYSTEM_STATE.md's
			// "R3" section) - this one call site was the only one left
			// on the stale path, so it silently shelled out to a
			// directory with no r3 binary in it and produced nothing.
			$command = shell_exec('
				cd /var/www/html/r3API/r3 2>&1;
				'.$command.';
				');
			
			$zip2->addFile( '../temp/_zip/'.$name, $name );
			$removable[] = $generated;
			}
		$zip2->close();
		for( $i = 0; $i < count( $removable ); $i++ ) {
			//unlink( $removable[$i] );
			}
		$result = $loc;
		}
		
	elseif( $_GET['type'] == 'multi' ) {
		$loc = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".zip";
		$loc2 = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".pdf";
		$zip2 = new ZipArchive();
		$zip2->open( "../temp/".$loc, ZipArchive::CREATE );
		$removable = array();
		for ($i = 0; $i < count($files); $i++) {
			$data = array();
			$data["pdf"] = file_get_contents($path.'/'.$files[$i]);

			$headers = array(
				"Content-Type: multipart/form-data",
				"Connection: Keep-Alive",
				);
				
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://".DYNAIP."/dynAPI/tracker/multi.php" );
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt($ch, CURLOPT_POST, true );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$response = curl_exec ($ch);
			$response = json_decode( $response, true );

			$data = explode( ',', $response["pdf"] );
				
			$name = $magazine[0][3]."_".$issue."_".$pages[$i].".pdf";
			file_put_contents('../temp/_zip/'.$name, base64_decode( $data[ 1 ] ) );
			
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
		if( count( $pages ) > 1 )
			$loc = $magazine[0][3]."_".$issue."_".$pages[0]."-".$pages[count($pages)-1].".pdf";
		else 
			$loc = $magazine[0][3]."_".$issue."_".$pages[0].".pdf";
		
		$data = array();
		$data["loc"] = $loc;
		$data["path"] = $path;
		$data["files"] = $files;

		include( "/var/www/html/dynAPI/tracker/one_local.php" );
		
/*		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://".DYNAIP."/dynAPI/tracker/one.php" );
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($ch);
		$response = json_decode( $response, true );
*/
		$result = $loc;
		}
	
	
print json_encode( $result );
	
?>