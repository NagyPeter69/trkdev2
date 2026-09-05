<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( "switchAPI.php" );
	
	include_once('../lang/en.php');
	$rights = array();

	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}

	// See client/plugins/pubsApply.php's 2026-09-05 fix - none of this
	// file's op== handlers checked authentication before running. Same
	// fix: one gate before any op is dispatched.
	if( empty( $user[0][0] ) ) {
		print json_encode( array( array( "Unauthorized" ) ) );
		exit;
		}

	if( $_GET['op'] == 'deliverLoad' ) {
		$delivers = sql_aget( "deliver_table", "pub_id='".$_GET["id"]."'", "*" );
		
		$txt = "";
		for( $i = 0; $i < count( $delivers ); $i++ ) {
			$txt .= "<tr>";
				$txt .= "<td>".date( "Y. m. d. H:i", $delivers[$i]["date"] )."</td>";
			$txt .= "</tr>";
			}
			
		$result = $txt;
		}
	
	if( $_GET['op'] == 'addDeliver' ) {
		$names = array( "pub_id", "date" );
		$values = array( $_GET["pubid"], time() );
		sql_add( "deliver_table", $names, $values );
		}
	
	if( $_GET['op'] == 'searcheIMG' ) {
		$pics = sql_aget( "image_map", "pub_id='".$_GET["id"]."' ".( !empty( $_POST["string"] ) ? " AND name LIKE '%".$_POST["string"]."%'" : "" )." ORDER BY name ASC", "*" );
		
		$txt = "";
		for( $i = 0; $i < count( $pics ); $i++ ) {
			$txt .= "<tr>";
				$txt .= "<td>".$pics[$i]["name"]."</td>";
				$txt .= "<td>
						<div style='float: left;'><input type='text' id='".$pics[$i]["id"]."_retus' name='".$pics[$i]["id"]."_retus' value='".$pics[$i]["retus"]."' style='width: 20px;'></div>
						<div style='float: left; margin-left: 5px;'><i id='".$pics[$i]["id"]."_button' onclick='saveRetus( \"".$pics[$i]["id"]."\" )' class='fas fa-check-square' style='color: #059E00; font-size: 20px; cursor: pointer;'></i></div>
					  </td>";
			$txt .= "</tr>";
			}
			
		$result = $txt;
		}
	
	if( $_GET['op'] == 'retusSave' ) {
		sql_update( "image_map", "retus='".$_GET["value"]."'", "id='".$_GET["imgid"]."'" );
		}
			
	if( $_GET['op'] == 'showerror' ) {
		$txt = "";

		$pack = sql_get( 'packages', 'id="'.$_GET['id'].'"', '*' );
		$pub = sql_get( 'publications', 'id="'.$pack[0][1].'"', '*' );
			
		$mag = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
		$checker = sql_get( 'package_info', 'package_id="'.$_GET['id'].'" AND event="'.$_GET['type'].'"', '*' );
		switch( $_GET['type'] ) {
			case 'lowres':
				$txt = "<div class='panelTitle'>".$lang["publications"]["lowresTitle"]."</div>";
				break;
			case 'missing':
				$txt = "<div class='panelTitle'>".$lang["publications"]["missingTitle"]."</div>";
				break;
			case 'corrupt':
				$txt = "<div class='panelTitle'>".$lang["publications"]["corruptTitle"]."</div>";
				break;
			}
			
		for( $i = 0; $i < count( $checker ); $i++ ) {
			$txt .= "<div class='panelItem'>";
				$txt .= $checker[$i][3];
			$txt .= "</div>";
			}
		$result = $txt;
		}
	
	if( $_GET['op'] == 'modIssue' ) {
		ob_start();
			include('../modIssue.php');
		$result = ob_get_clean();
		}
	
	if( $_GET['op'] == 'delMagazine' ) {
		$mag = sql_get( 'magazines', 'code="'.$_GET['code'].'"', '*' );
		$pubs = sql_get( 'publications', 'magazine_id="'.$mag[0][0].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$mag[0][0].'"', '*' );

		sql_update( "magazines", "`removing`='1'", "id='".$magazine[0][0]."'" );

		// magazines.publisher_id is always "0" for Adhoc jobs regardless of
		// client (see switchAPI.php's resolveJobPublisherName()), so looking
		// the publisher up directly off it - as this used to - resolved to
		// nothing and sent Switch an empty "client" for every Adhoc removal,
		// known-client or not. resolveJobPublisherName() knows to fall back
		// to publications.owner for Adhoc.
		$array = array(
			"event" => "delete_publication",
			"client" => resolveJobPublisherName( $magazine[0][3] ),
			"pubName" => $magazine[0][2],
			"jobCode" => $magazine[0][3],
			);
		
		$error = SwitchSend_TESZT( $array );
		
		/*$array = array();
		$array["client"] = $publisher[0][1];
		$array["jobCode"] = $magazine[0][3];
		$array["event"] = 'delete_publication';
		$array["publication"] = $magazine[0][2];
		$array["user"] = $_SESSION['intra_user'];
		
		$myxml = array_to_xml( $array, 'eventComm' );
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		
		$counter = get_counter('..');
		$saveTo = 'C_Hotfolders/messages/message_'.$counter;
		inc_counter('..');
		
		sql_update( "magazines", "`removing`='1'", "id='".$magazine[0][0]."'" );
		
		$sftp = ftp_conn("../");
		$sftp->put( $saveTo.'.pdf', 'dummy.pdf', NET_SFTP_LOCAL_FILE );
		$sftp->put( $saveTo.'.xml', $dom->saveXML()  );*/

		$result = '';
		}
	
	if( $_GET['op'] == 'deleteIssue' ) {
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );

		sql_update( "publications", "`removing`='1'", "id='".$issue[0][0]."'" );

		// Same Adhoc publisher_id="0" fallback issue as delMagazine above -
		// resolveJobPublisherName() falls back to publications.owner.
		$array = array(
			"event" => "delete_issue",
			"client" => resolveJobPublisherName( $magazine[0][3] ),
			"jobCode" => $magazine[0][3],
			"issue" => $issue[0][10],
			"description" => $magazine[0][3].'_'.$issue[0][10],
			);
		
		error_log( json_encode($array) );
		
		$error = SwitchSend_TESZT( $array );
		
		/*
		$array = array();
		$array["client"] = $publisher[0][1];
		$array["jobCode"] = $magazine[0][3];
		$array["issue"] = $issue[0][10];
		$array["event"] = 'delete_issue';
		$array["description"] = $magazine[0][3].'_'.$issue[0][10];
		$array["remark"] = '';
		$array["user"] = $_SESSION['intra_user'];
		
		$myxml = array_to_xml( $array, 'eventComm' );
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		
		$counter = get_counter('..');
		$saveTo = 'C_Hotfolders/messages/message_'.$counter;
		inc_counter('..');

		$sftp = ftp_conn("../");
		
		
		
		$sftp->put( $saveTo.'.pdf', 'dummy.pdf', NET_SFTP_LOCAL_FILE );
		$sftp->put( $saveTo.'.xml', $dom->saveXML()  );*/
		
		$result = "ok";
		}

	if( $_GET['op'] == 'archiveIssue' ) {
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );

		// No per-job directory to pre-create here: Switch names and creates
		// its own flat archive folder directly under ARCHIVE_PATH (e.g.
		// "PRFU_2633__Palaye_Royale_archived" - see findArchivePath() in
		// engine/engine.php for the exact naming rule), rather than writing
		// into a path Tracker precomputes. ARCHIVE_PATH itself already
		// carries the setgid bit (set up once, alongside the switch-archiver
		// SFTP account), so whatever Switch creates inside it inherits group
		// www-data regardless of which uid wrote it - no chown needed and
		// nothing for Tracker to prepare per-job.

		$array = array(
			"event" => "archive",
			"client" => $publisher[0][1],
			"jobCode" => $magazine[0][3],
			"issue" => $issue[0][10],
			"description" => $magazine[0][3].'_'.$issue[0][10],
			);

		$error = SwitchSend_TESZT( $array );
		/*
		$array = array();
		$array["client"] = $publisher[0][1];
		$array["jobCode"] = $magazine[0][3];
		$array["issue"] = $issue[0][10];
		$array["event"] = 'archive';
		$array["description"] = $magazine[0][3].'_'.$issue[0][10];
		$array["remark"] = '';
		
		$myxml = array_to_xml( $array, 'eventComm' );
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		
		$counter = get_counter('..');
		$saveTo = 'C_Hotfolders/messages/message_'.$counter;
		inc_counter('..');

		$sftp = ftp_conn("../");
		$sftp->put( $saveTo.'.pdf', 'dummy.pdf', NET_SFTP_LOCAL_FILE );
		$sftp->put( $saveTo.'.xml', $dom->saveXML()  );	*/
		
		sql_update( 'publications', 'status="archiving"', 'id="'.$_GET['id'].'"' );

		// Unlike stopIssue/restartIssue/approveIssue, this handler never
		// synced the per-issue Switch snapshot's <status> - confirmed
		// stale via the test protocol (DB said "archiving", the snapshot
		// still said "created"). Same changeIssueStatus() mechanism as
		// the other three, for the same reason: Switch and anyone reading
		// the snapshot should see the current status, not whatever it was
		// when the issue was created.
		if( $magazine[0][10] == "Adhoc" ) {
			changeIssueStatus( $issue[0][10].".xml", "archiving", $_GET['id'] );
			}
		else {
			changeIssueStatus( $magazine[0][3]."_".$issue[0][10].".xml", "archiving", $_GET['id'] );
			}

		$p_id = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_SESSION['intra_user'], 'archivingIssue', $p_id[0][1], $p_id[0][2],  $p_id[0][10], '', time(), '' );
		sql_add( 'action_log', $names, $values );
		
		$result = "ok";
		}

	if( $_GET['op'] == 'approveIssue' ) {
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
		
		/*$array = array();
		$array["client"] = $publisher[0][1];
		$array["jobCode"] = $magazine[0][3];
		$array["issue"] = $issue[0][10];
		$array["event"] = 'approved';
		$array["description"] = $magazine[0][3].'_'.$issue[0][10];
		$array["remark"] = '';
		
		$myxml = array_to_xml( $array, 'eventComm' );
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		
		$counter = get_counter('..');
		$saveTo = 'C_Hotfolders/messages/message_'.$counter;
		inc_counter('..');

		$sftp = ftp_conn("../");
		$sftp->put( $saveTo.'.pdf', 'dummy.pdf', NET_SFTP_LOCAL_FILE );
		$sftp->put( $saveTo.'.xml', $dom->saveXML()  );	*/
		
		sql_update( 'publications', 'status="approved"', 'id="'.$_GET['id'].'"' );
		if( $magazine[0][10] == "Adhoc" ) {
			changeIssueStatus( $issue[0][10].".xml", "approved", $_GET['id'] );
			}
		else {
			changeIssueStatus( $magazine[0][3]."_".$issue[0][10].".xml", "approved", $_GET['id'] );
			}
		
		invoicingTESZT( $_GET["id"] );
		
		$p_id = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_SESSION['intra_user'], 'approvedIssue', $p_id[0][1], $p_id[0][2],  $p_id[0][10], '', time(), '' );
		sql_add( 'action_log', $names, $values );
		
		$result = "ok";
		}

	if( $_GET['op'] == 'restartIssue' ) {
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
		
		/*$array = array();
		$array["client"] = $publisher[0][1];
		$array["jobCode"] = $magazine[0][3];
		$array["issue"] = $issue[0][10];
		$array["event"] = 'restart';
		$array["description"] = $magazine[0][3].'_'.$issue[0][10];
		$array["remark"] = '';
		
		$myxml = array_to_xml( $array, 'eventComm' );
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		
		$counter = get_counter('..');
		$saveTo = 'C_Hotfolders/messages/message_'.$counter;
		inc_counter('..');

		$sftp = ftp_conn("../");
		$sftp->put( $saveTo.'.pdf', 'dummy.pdf', NET_SFTP_LOCAL_FILE );
		$sftp->put( $saveTo.'.xml', $dom->saveXML()  );*/	
					
		$status = '';
		$ads = sql_get( 'ads', 'pub_id="'.$issue[0][0].'"', '*' );
		if( count( $ads ) > 0 ) {
			$status = "active";
			}
		$pack = sql_get( 'packages', 'publication_id="'.$issue[0][0].'"', '*' );
		if( count( $pack ) > 0 ) {
			$status = "active";
			}
		else {
			$status = "created";
			}
			
		sql_update( 'publications', 'status="'.$status.'"', 'id="'.$_GET['id'].'"' );
		
		$xml = simplexml_load_file( XMLPATH.'/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $magazines[$i][3] ) {
					break;
					}
				}
			}
		$cur = $issue[0][10];
		if( $issue[0][10] == $cur ) $stat = "active";
		else $stat = "created";

		if( $magazine[0][10] == "Adhoc" ) {
			changeIssueStatus( $issue[0][10].".xml", $stat, $_GET['id'] );
			}
		else {
			changeIssueStatus( $magazine[0][3]."_".$issue[0][10].".xml", $stat, $_GET['id'] );
			}		
		
		
		$p_id = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_SESSION['intra_user'], 'restartIssue', $p_id[0][1], $p_id[0][2],  $p_id[0][10], '', time(), '' );
		sql_add( 'action_log', $names, $values );
		
		$result = "ok";
		}

	if( $_GET['op'] == 'stopIssue' ) {
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
		
		sql_update( 'publications', 'status="stopped"', 'id="'.$_GET['id'].'"' );
		if( $magazine[0][10] == "Adhoc" ) {
			changeIssueStatus( $issue[0][10].".xml", "stopped", $_GET['id'] );
			}
		else {
			changeIssueStatus( $magazine[0][3]."_".$issue[0][10].".xml", "stopped", $_GET['id'] );
			}			
		
		
		$p_id = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );		
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_SESSION['intra_user'], 'stoppedIssue', $p_id[0][1], $p_id[0][2],  $p_id[0][10], '', time(), '' );
		sql_add( 'action_log', $names, $values );
		
		$xml = simplexml_load_file( XMLPATH.'/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $magazine[0][3] ) {
					break;
					}
				}
			}
		
		$mails = (string) $xml->Item[$x]->Mails;
		$process = (string) $xml->Item[$x]->Workflow;
		
		$to = "ugyvitel@colorcom.hu|ugyvitel@colorcom.hu";
		
		$magname = $magazine[0][2]." – ".$magazine[0][3]." – ".$issue[0][10];
		if( $magazine[0][10] == "Adhoc" ) {
			$magname = $magazine[0][2]." – ".$magazine[0][3];
			}
		
		$subject = "Stopped issue – ".$magname;
		
		if( $issue[0][1] != 0 ) {
			$ugyfel = "Ügyfél: ".$publisher[0][1];
			}
		else {
			$who = sql_aget( "accounts", "id='".$issue[0][24]."'", "*" );
			$ugyfel = "Megrendelő: ".$who[0]["full_name"];
			}
		
		$kepek = sql_aget( "image_map", "pub_id='".$issue[0][0]."'", "*" );
		$retus = sql_aget( "image_map", "pub_id='".$issue[0][0]."' AND retus > 0", "*" );
		$retustime = 0;
		for( $i = 0; $i < count( $retus ); $i++ ) {
			$retustime += intval( $retus[$i]["retus"] );
			}
		
		$maszk = sql_aget( "image_map", "pub_id='".$issue[0][0]."' AND maszk > 0", "*" );
		$img_hide = array( "_fix", "_lowres", "_inferior" );
		
		$imglist = imageList( $issue[0][0] );
		$imglist = $imglist[0];
		if( empty( $imglist ) ) $imglist = 0;
		$body = "
		Munka neve, kódja: ".$magname."<br>	
		Kiértesített e-mail címek: ".$mails."<br><br>
		Feldolgozott képek: ".count( $kepek )." db<br>
		Maszkolt kép: ".count( $maszk )." db<br>
		Retusálás: ".$retustime." perc<br><br>
		Feldolgozott képek:<br>
		".str_replace( "&nbsp;", "", str_replace( $img_hide, "", $imglist ) )."
		";

$txt = "
Munka neve, kódja: ".$magname."
Kiértesített e-mail címek: ".$mails."
Feldolgozott képek: ".count( $kepek )." db
Maszkolt kép: ".count( $maszk )." db
Retusálás: ".$retustime." perc
Feldolgozott képek:
".str_replace( "<br>", "\n", str_replace( "&nbsp;", " ", str_replace( $img_hide, "", $imglist ) ) )."
		";


		produkcioSendmailAttach( $subject, $body, $to, $txt, $magazine[0][2]."_".$issue[0][10].".txt" );

		$result = "ok";
		}

	print json_encode( $result );
	
?>
