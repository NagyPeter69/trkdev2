<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');
	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( "../engine/switchAPI.php" );
	
	if( !empty( $_POST["Language"] ) ) {
		include_once('../lang/'.strtolower( $_POST["Language"] ).'.php');
		}
	else {
		include_once('../lang/en.php');
		}
	
	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}

	if( $_GET["sub"] == "getUsers" ) {
		$pub = sql_aget( "publishers", "name='".$_GET["publisher"]."'", "*" );
		$u = sql_aget( "accounts", "publisher='".$pub[0]["id"]."' OR `group`='2'", "*" );
		$txt = "<select id='adhocUser' name='adhocUser'>";
		for( $i = 0; $i < count( $u ); $i++ ) {
			$txt .= "<option value='".$u[$i]["id"]."'>".$u[$i]["full_name"]."</option>";
			}
		$txt .= "</select>";
		$result = $txt;
		}
		
	if( $_GET["sub"] == "removeClient" ) {
		$client = sql_aget( "publishers", "id='".$_GET["id"]."'", "*" );
		sql_delete( "publishers", "id='".$client[0]["id"]."'" );

		$array = array(
			"event" => "remove_client",
			"client" => $client[0]["name"],
			);
		
		//$error = SwitchSend_TESZT( $array );
		}
		
	if( $_GET["sub"] == "client" ) {
		$error = array();
		$check = sql_get( "publishers", "name='".$_POST["clientname"]."'", "id" );
		if( $check[0][0] == "" && $_POST["clientname"] != "" && $_POST["clientname"] != "Adhoc" ) {
			$array = array(
				"event" => "new_client",
				"client" => $_POST["clientname"],
				);
			
			$error = SwitchSend_TESZT( $array );
			}
		else {
			$error[] = "clientname";
			}
		
		$result = array( $error );
		}
		
	if( $_GET["sub"] == "modIssue" ) {
		$error = array();
		
		if ( array_key_exists( "page_nr", $_POST ) ) {
			if( $_POST['page_nr'] == "" ) $error[] = "page_nr";
			}
		else {
			$_POST['page_nr'] = 0;
			}
			
		if( $_POST['dl'] == "" ) $error[] = "dl";
			
		if( count( $error ) == 0 ) {
			$pub = sql_get( 'publications', 'id="'.$_POST['pub'].'"', '*' );
			error_log( "pubid: ".$_POST['pub'] );
			$mag = sql_get( 'magazines', 'id="'.$pub[0][2].'"', 'code, id' );
			
			$_POST['settings']['dl'] = str_replace( " ", "T", $_POST['settings']['dl'] );
			// pages is deliberately left out of this update - it's derived
			// from the Parts list right below instead of taken from the
			// page_nr field, which is what let the two drift apart before
			// (confirmed live on PRFU_2650: page_nr said 16 while the Parts
			// positions summed to 20).
			sql_update( 'publications', "deadline='".$_POST['dl']."', enhance='".$_POST["enhance"]."', specificName='".$_POST["customname"]."'", 'id="'.$_POST['pub'].'"' );

			// Only touch parts if the form actually submitted any - Adhoc
			// jobs never render a Parts table in Modify Issue at all (see
			// modIssue.php's $showParts), so type[] is legitimately absent
			// for them; deleting unconditionally used to wipe their Parts
			// on every single save with nothing submitted to replace them.
			if( !empty( $_POST["type"] ) ) {
				$names = array( "pub_id", "name", "place", "color", "size", "mag_id", "grayscale" );
				sql_delete( "parts", "pub_id='".$pub[0][0]."'" );
				for( $i = 0; $i < count( $_POST["type"] ); $i++ ) {
					$values = array( $pub[0][0], $_POST["type"][$i], $_POST["position"][$i] ?? "", $_POST["color"][$i], $_POST["trim_x"][$i]."x".$_POST["trim_y"][$i], $_POST['magazine'], $_POST["grayscale"][$i] );
					sql_add( "parts", $names, $values );
					}
				}
			$syncedPages = syncPublicationPages( $pub[0][0] );

			toSwitch( 'new_publication' , 'publications|'.$pub[0][0], 'C_database/'.$mag[0][0].'_'.$pub[0][10].''.ISSUEPOSTFIX.'', 'issueData' );

			$check = issueChecker( $mag[0][0], $pub[0][10], "calendar" );
			if( !empty( $check[0]["id"] ) ) {
				$dl = explode( "T", $_POST['settings']['dl'] );
				sql_update( "calendar_post", "printDay='".$dl[0]."', numofpages='".$syncedPages."'", "id='".$check[0]["id"]."'" );
				}
			
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
			$values = array( $_SESSION['intra_user'], 'modIssue', $pub[0][1], $mag[0][1], $pub[0][10], '', time() );
			sql_add( 'system_log', $names, $values );
			}
			
		$result = array( $error );
		}
	
	if( $_GET["sub"] == "jcreate" ) {
		$error = array();
		if( $_POST["Name"] == "" ) $error[] = "Name";
		if( $_POST["Code"] == "" ) $error[] = "Code";
		if( $_POST["TrimSize_x"] == "" ) $error[] = "TrimSize_x";
		if( $_POST["TrimSize_y"] == "" ) $error[] = "TrimSize_y";
		
		$publisher = sql_get( "publishers", "name='".$_POST["publisher"]."'", "id" );
		$checker = sql_get( "jobs", "code='".$_POST["Code"]."' AND publisher='".$_POST["publisher"]."'", "*" );
		if( $checker[0][0] != "" ) $error[] = "Code";
		
		if( count( $error ) == 0 ) {
			$_POST['Mails'] = explode( "\r\n", $_POST['Mails'] );
			$_POST['Mails'] = trim( implode( ";", $_POST['Mails'] ) );		
			changeJOBXmlDatabase( 'add', $_POST, '../xml/job_data.xml' );
			$names = array( 'publisher', 'name', 'code', 'created' );
			$values = array( $_POST["publisher"], $_POST['Name'], $_POST['Code'], time() );
			$mid = sql_add( 'jobs', $names, $values );
			
			$admins = sql_aget( "accounts", "`group`='2'", "id, showJobs" );
			for( $i = 0; $i < count( $admins ); $i++ ) {
				sql_update( "accounts", "showJobs='".$admins[$i]["showJobs"].",".$mid."'", "id='".$admins[$i]["id"]."'" );
				}	

			$allowedMags = explode( ",", $user[0][23] );
			if( $allowedMags[0]!= "" ) {
				if( !in_array( $mid, $allowedMags ) ) $allowedMags[] = $mid;
				$amags = implode( ",", $allowedMags );
				}
			else {
				$amags = $mid;
				}
			sql_update( "accounts", "showJobs='".$amags."'", "id='".$user[0][0]."'" );
				
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
			$values = array( $_SESSION['intra_user'], 'newJob', $_POST["publisher"], $mid, '', '', time() );
			sql_add( 'system_log', $names, $values );
			}
		$result = array( $error );	
		}
	
	if( $_GET["sub"] == "create" ) {
		$error = array();
		
		if( $_POST["Type"] == "Adhoc" ) {
			//if( $_POST["Deadline"] == "" ) $error[] = "Deadline";
			if( $_POST["ClientType"] == "unknown" ) {
				if( $_POST["Mails"] == "" ) $error[] = "Mails";
				}

			if( $_POST["Name"] == "Adhoc" ) {
				 $error[] = "Adhoc";
				}

			if( empty( $_POST["Name"] ) ) {
				 $error[] = "Name";
				}

			// codeGen() (engine.php) suggests a unique code when this
			// form's panel first opens, but that's a display-time check
			// only - nothing previously re-verified it at submission time,
			// unlike the Regular branch just below. A collision here is a
			// real risk given the code is 3 random letters + 2 random
			// digits (~1 in 6.7 million combinations, small enough for a
			// stale suggestion or two concurrent submissions to collide) -
			// and unlike Regular, Adhoc's magazine and publication rows
			// share this same code, so both tables need checking.
			if( empty( $_POST["Code"] ) ) {
				$error[] = "Code";
				}
			else {
				$checker = sql_get( "magazines", "code='".$_POST["Code"]."'", "*" );
				$checkerPub = sql_get( "publications", "code='".$_POST["Code"]."'", "*" );
				if( $checker[0][0] != "" || $checkerPub[0][0] != "" ) $error[] = "Code";
				}
			}
		
		if( $_POST["Type"] == "Regular" ) {
			if( $_POST["Name"] == "" ) $error[] = "Name";
			if( $_POST["Code"] == "" ) $error[] = "Code";

			$checker = sql_get( "magazines", "code='".$_POST["Code"]."'", "*" );
			if( $checker[0][0] != "" ) $error[] = "Code";

			// The Client dropdown (loadPub's Regular branch) has no default
			// selection on purpose - a blank/unselected/tampered submission
			// must be rejected here rather than silently falling through to
			// whatever sql_aget('publishers', "name=''", ...) would resolve to.
			if( empty( $_POST["Client"] ) ) {
				$error[] = "Client";
				}
			else {
				$validClient = sql_get( "publishers", "name='".$_POST["Client"]."'", "id" );
				if( empty( $validClient[0][0] ) ) $error[] = "Client";
				}
			}

		// European-numbering Parts (Adhoc or Regular) submit a position/
		// page-range here via create.php's newLine(); American-numbering
		// Parts never do, so $_POST["position"] is legitimately absent for
		// them - no format validation is done here either way, matching
		// the Regular branch below (format validation only happens later,
		// in jobsettings.php).

		if( count( $error ) == 0 ) {
			$guide = $lang["publications"]["mail_guide"];
			
			if( $_POST["Type"] == "Adhoc" ) {
				$names = array( "publisher_id", "name", "code", "calendarGroup", "type", "preflight" );
				$values = array( "0", $_POST["Name"], $_POST["Code"], "", $_POST["Type"], $_POST["Preflight"] ?? "Yes" );
				$mid = sql_add( "magazines", $names, $values );
				// sql_add() returns false on a failed INSERT (duplicate code
				// racing past the check above, a DB error, etc.) - nothing
				// downstream checked this before, so a failed magazine insert
				// still went on to create publications/accounts/parts against
				// a bogus magazine_id, send a real email, and write a PMD entry
				// for a magazine that was never actually created. Bail out
				// here instead - this is the "DB row missing, PMD orphan
				// created anyway" half of the PMD/DB consistency the project
				// owner asked to guarantee (2026-09-05).
				if( empty( $mid ) ) {
					$error[] = "Code";
					}
				else {
				$name = array( "publisher_id", "magazine_id", "pages", "uploadable", "code", "deadline", "enhance" );
				$value = array( "0", $mid, "0", "true", $_POST["Code"], $_POST["Deadline"], $_POST["Enhance"] );
				error_log("ADHOC MELÓ: ".$_POST["ClientType"] );
				if( $_POST["ClientType"] == "known" ) {
					$_POST["Client"] = $_POST["Client2"];
					$u = sql_aget( "accounts", "id='".$_POST["adhocUser"]."'", "*" );
					$_POST["Mails"] = $u[0]["email"];

					$c = sql_aget( "publishers", "name='".$_POST["Client2"]."'", "*" );
					$name[] = "owner";
					$value[] = $c[0]["id"];

					$name[] = "user";
					$value[] = $u[0]["id"];
					$id = sql_add( "publications", $name, $value );
					sql_update( "accounts", "showMagazines='".$u[0]["showMagazines"].",".$mid."'", "id='".$u[0]["id"]."'" );
					$_POST["LocalStorage"] = "PubFolder";
					sql_update( "magazines", "pubName='".$c[0]["name"]."'", "id='".$mid."'" );

					// A known/registered contact still gets the same elevated
					// (ArtDirectorElevated / group 14: ad upload+preflight,
					// Flatplan+Pages approve/reject, asset upload/download)
					// access as an unknown one - as a separate job-scoped Temp
					// account linked back to their real one, not by changing
					// their real account's own group, which would silently
					// elevate them on every other job they can already see.
					$names2 = array( "name", "pass", "type", "publisher", "email", "full_name", "group", "actual", "showMagazines", "usertype", "temppubid", "lang", "linked_account_id" );
					$values2 = array( "", "", "adhoc", $c[0]["id"], $u[0]["email"], $u[0]["full_name"], "14", $_POST["Code"]."_".$_POST["Code"], $mid, "Temp", $id, strtolower( $_POST["Language"] ), $u[0]["id"] );
					$aid = sql_add( "accounts", $names2, $values2 );
					}
				
				if( $_POST["ClientType"] == "unknown" ) {
					$name[] = "clientType";
					$value[] = "unknown";
					$id = sql_add( "publications", $name, $value );	
					$names = array( "name", "pass", "type", "publisher", "email", "full_name", "group", "actual", "showMagazines", "usertype", "temppubid", "lang" );
					$values = array( "", "", "adhoc", "0", $_POST["Mails"], "", "14", $_POST["Code"]."_".$_POST["Code"], $mid, "Temp", $id, strtolower( $_POST["Language"] ) );
					$aid = sql_add( "accounts", $names, $values );
					$_POST["LocalStorage"] = "Adhoc";
					sql_update( "magazines", "pubName='".$_POST["Client"]."'", "id='".$mid."'" );
					}
					
				$hash = md5( "adhocmelo-".time()."-".$_POST["Mails"] );
				$names = array( "user_id", "hash", "magazine_id", "email", "time", "pubid" );
				$values = array( $aid, $hash, $mid, $_POST["Mails"], time(), $id );
				sql_add( "adhoc_hotlinks", $names, $values );
				
				$names = array( "pub_id", "name", "place", "color", "size", "mag_id", "grayscale" );
				for( $i = 0; $i < count( $_POST["parttype"] ?? array() ); $i++ ) {
					if( $_POST["Workflow"] != "Full" && $_POST["Workflow"] != "Hybrid" ) {
						$_POST["trim_x"][$i] = "";
						$_POST["trim_y"][$i] = "";
						}

					$values = array( $id, $_POST["parttype"][$i], $_POST["position"][$i] ?? "", $_POST["color"][$i], $_POST["trim_x"][$i]."x".$_POST["trim_y"][$i], $mid, $_POST["grayscale"][$i] );
					sql_add( "parts", $names, $values );
					if( $_POST["parttype"][$i] == "BEL" ) {
						$insideWidth = $_POST["trim_x"][$i];
						$insideHeight = $_POST["trim_y"][$i];
						}
					}
				syncPublicationPages( $id );

				// PMD write happens - and is checked - before the notification
				// mail below, not after. produkcioSendmail() has no timeout
				// (same unbounded-hang risk switchAPI.php's curl calls used to
				// have, see engine's SYSTEM_STATE.md) - with the mail send
				// first, a slow/unreachable mail server could hang the request
				// long enough for nginx's own timeout to kill it before
				// changeXmlDatabase() ever ran, leaving a DB-only magazine
				// with no PMD entry and no chance for the rollback below to
				// even execute. Reproduced live 2026-09-05 testing this fix
				// with the mail server firewalled off (candidate box's
				// deliberate migration-safety block) - confirmed a real,
				// distinct drift vector, not just a hypothetical one.
				$pmdOk = changeXmlDatabase( 'add', $_POST );
				if( !$pmdOk ) {
					// The other direction of the same consistency guarantee:
					// the magazine/publication/account rows above are already
					// committed (no transactions on this MyISAM table), but
					// changeXmlDatabase() just logged a CRITICAL write failure
					// - roll every one of them back rather than leave a DB-only
					// magazine with no PMD entry (this exact class of failure
					// is what caused the 2026-07-27 pmd.xml ownership incident;
					// this time, don't leave the half-created job behind too).
					sql_delete( "adhoc_hotlinks", "magazine_id='".$mid."'" );
					sql_delete( "parts", "mag_id='".$mid."'" );
					if( !empty( $aid ) ) sql_delete( "accounts", "id='".$aid."'" );
					if( !empty( $id ) ) sql_delete( "publications", "id='".$id."'" );
					sql_delete( "magazines", "id='".$mid."'" );
					$error[] = "Code";
					}
				else {
					toSwitch( 'new_publication' , 'publications|'.$id, 'C_database/'.$_POST["Code"].''.ISSUEPOSTFIX.'', 'issueData' );

					if( isset( $insideWidth ) ) {
						syncInsidePartAdSize( $mid, $_POST["Code"], $_POST["Workflow"], $insideWidth, $insideHeight );
						}

					$link = "https://".URL."/index.php?hash=".$hash;
					$subject = sprintf( $lang["publications"]["adhoc_mail_subject"], $_POST["Name"] );
					$to = $_POST["Mails"]."|".$_POST["Mails"];
					if( $_POST["ClientType"] == "known" && !empty( $u[0]["full_name"] ) ) {
						// This is a separate, job-scoped access link for the elevated
						// role - not the recipient's existing Tracker login - so say
						// so explicitly and greet them by name instead of the generic
						// "Dear Customer" used for a genuinely new/unknown contact.
						// Subject/greeting/closing come from $lang (loaded per
						// $_POST["Language"] at the top of this file), same as
						// $guide, so the whole mail is in one language instead of
						// mixing a hardcoded-Hungarian wrapper around it.
						$body = sprintf( $lang["publications"]["adhoc_mail_known"], $u[0]["full_name"], $_POST["Name"], $_POST["Code"], "<a href='".$link."'>".$link."</a>" )."
						<br>
						".$guide."
						<br>
						".$lang["publications"]["adhoc_mail_closing"];
						}
					else {
						$body = sprintf( $lang["publications"]["adhoc_mail_unknown"], $_POST["Name"], $_POST["Code"], "<a href='".$link."'>".$link."</a>" )."
						<br>
						".$guide."
						<br>
						".$lang["publications"]["adhoc_mail_closing"];
						}
					produkcioSendmail( $subject, $body, $to );
					}
				}
				}

			if( $_POST["Type"] == "Regular" ) {
				$publisher = sql_aget( "publishers", "name='".$_POST["Client"]."'", "*" );
				$names = array( "publisher_id", "name", "code", "calendarGroup", "type", "preflight" );
				$values = array( $publisher[0]["id"], $_POST["Name"], $_POST["Code"], "", $_POST["Type"], $_POST["Preflight"] ?? "Yes" );
				$mid = sql_add( "magazines", $names, $values );
				// Same PMD/DB consistency guarantee as the Adhoc branch above:
				// a failed magazine insert must not still get a PMD entry
				// written for it (sql_add() returns false on failure, nothing
				// checked that before).
				if( empty( $mid ) ) {
					$error[] = "Code";
					}
				else {
				$pmdOk = changeXmlDatabase( 'add', $_POST );
				if( !$pmdOk ) {
					// And the other direction: PMD write failed (logged
					// CRITICAL by changeXmlDatabase() itself) - roll back the
					// magazine row rather than leave a DB-only magazine with
					// no PMD entry. Nothing else has been created yet at this
					// point (Parts are created below, after this check), so
					// there's nothing else to undo.
					sql_delete( "magazines", "id='".$mid."'" );
					$mid = false;
					$error[] = "Code";
					}
				else {
					// Whatever Parts the create.php dialog collected need to be
					// saved as this magazine's pub_id="0" template row set -
					// the same convention jobsettings.php and newIssue.php read
					// from ("pub_id='0' AND mag_id=...") to pre-populate the
					// Parts table for the first issue. This was previously
					// discarded entirely: create.php submits under "parttype",
					// not "type", and nothing here ever read it, so page
					// sequences typed in at creation silently vanished and the
					// first New Issue dialog opened with zero Part rows.
					$names = array( "pub_id", "name", "place", "color", "size", "mag_id", "grayscale" );
					for( $i = 0; $i < count( $_POST["parttype"] ?? array() ); $i++ ) {
						$values = array( "0", $_POST["parttype"][$i], $_POST["position"][$i] ?? "", $_POST["color"][$i], $_POST["trim_x"][$i]."x".$_POST["trim_y"][$i], $mid, $_POST["grayscale"][$i] );
						sql_add( "parts", $names, $values );
						}
					}
				}
				}
			
			// Everything below assumes a real, successfully-created magazine
			// (both the magazine row and its PMD entry) - if either branch
			// above bailed out and added an error instead, $mid is empty and
			// none of this notification/bookkeeping tail should run either.
			if( !empty( $mid ) ) {
			$allowedMags = explode( ",", $user[0][21] );
			if( $allowedMags[0]!= "" ) {
				if( !in_array( $mid, $allowedMags ) ) $allowedMags[] = $mid;
				$amags = implode( ",", $allowedMags );
				}
			else {
				$amags = $mid;
				}
			sql_update( "accounts", "showMagazines='".$amags."'", "id='".$user[0][0]."'" );

			$admins = sql_aget( "accounts", "`group`='2'", "id, showMagazines" );
			for( $i = 0; $i < count( $admins ); $i++ ) {
				if( $admins[$i]["id"] != $user[0][0] ) {
					sql_update( "accounts", "showMagazines='".$admins[$i]["showMagazines"].",".$mid."'", "id='".$admins[$i]["id"]."'" );
					}
				}

			$admins = sql_aget( "accounts", "`group`='5'", "id, showMagazines" );
			for( $i = 0; $i < count( $admins ); $i++ ) {
				if( $admins[$i]["id"] != $user[0][0] ) {
					sql_update( "accounts", "showMagazines='".$admins[$i]["showMagazines"].",".$mid."'", "id='".$admins[$i]["id"]."'" );
					}
				}

			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
			$values = array( $_SESSION['intra_user'], 'newMagazine', '0', $mid, '', '', time() );
			sql_add( 'system_log', $names, $values );

			$subject = $_POST['Name']." létrehozva a Trackeren";
			$to = "Colorcom Prepress|produkcio@colorcom.hu";
			$body = "
				Kedves Felhasználónk!<br>
				<br>
				A(z) ".$_POST['Name']." létre lett hozva a Tracker rendszerben.<br>
				<br>
				".$guide."
				<br>
				Üdvözlettel:<br>
				Colorcom Media";
			produkcioSendmail( $subject, $body, $to );

			$array = array(
				"event" => "publication_created",
				"client" => $_POST['Client'],
				"pubName" => $_POST['Name'],
				"jobCode" => $_POST['Code'],
				);

			// Was previously stored back into $error, clobbering the
			// (empty-on-success) validation-errors array with Switch's
			// [status, message] response tuple - menuApply()'s JS never saw
			// the data[0][0]===true / empty-data[0] shape it needs to close
			// the dialog and reload, so every Adhoc creation left the
			// "Create" panel stuck open even though the job was created
			// successfully. Confirmed live 2026-07-29 creating FKB30.
			$switchResponse = SwitchSend_TESZT( $array );
			}
			}

		$result = array( $error );
		}
	
	if( $_GET["sub"] == "ftp" ) {
		$error = array();
		$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		
		foreach($xpath as $temp) {
			for( $i = 0; $i < count( $temp->Item ); $i++ ) {
				if( $temp->Item[$i]->Code == $_POST['code'] )
					break;
				}
			}
		
		$xml->Item[$i]->RemoteStorage = '';
		$nodes = array( 'INDD', 'Images', 'PDF', 'Packages' );
		foreach( $nodes as $node ) {
			if( isset( $_POST['ftp_'.$node] ) && $_POST['ftp_'.$node] != "" )
				$xml->Item[$i]->RemoteStorage->$node = $_POST['ftp_'.$node];
			}
		if( isset( $_POST['Softproof'] ) && $_POST['Softproof'] != "" )
			$xml->Item[$i]->Softproof = $_POST['Softproof'];
		if( isset( $_POST['FinalOutput'] ) && $_POST['FinalOutput'] != "" )
			$xml->Item[$i]->FinalOutput = $_POST['FinalOutput'];
		
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
	
		file_put_contents( '../xml/'.PMD.'.xml', $dom->saveXML() );
		$pmdName = pmdDevSafeName( PMD_LONG.'_NT.xml' );
		file_put_contents( "../xml/".$pmdName, $dom->saveXML() );

		$array = array(
			"event" => "xml_data",
			);

		$file = array(
			"name" => $pmdName,
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );
		
		$result = array( $error );	
		}
		
 	if( $_GET["sub"] == "color" ) {
		$error = array();

		parse_str($_POST['settings'], $_POST);
		
		if( $_POST["pn"] == "European" ) {
			for( $i = 0; $i < count( $_POST["type"] ?? array() ); $i++ ) {
				$pos = explode( ",", $_POST["position"][$i] ?? "" );
				for( $p = 0; $p < count( $pos ); $p++ ) {
					$pages = explode( "-", $pos[$p] );
					if( count($pages) != 2 ) {
						$error[] = "position_".$i;
						break;
						}
						
					if( !preg_match("/^[0-9-]+$/", $pos[$p] ) ) {
						$error[] = "position_".$i;
						}
					}
				}
			}
			
		if( $_POST["pn"] == "American" ) {
			/*
			for( $i = 0; $i < count( $_POST["type"] ); $i++ ) {
				$pos = explode( ",", $_POST["position"][$i] );
				for( $p = 0; $p < count( $pos ); $p++ ) {
					$pages = explode( "-", $pos[$p] );
					if( count($pages) != 1 ) {
						$error[] = "position_".$i;
						break;
						}

					if( !preg_match("/^[0-9]+$/", $pos[$p] ) ) {
						$error[] = "position_".$i;
						}
					else{
						if ( $pos[$p] > 0 && $pos[$p] <= 9999 ) {}
						else {
							$error[] = "position_".$i;
							}
						}
					}
				}	
			*/
			}
		
		error_log("CODE: ".$_POST["code"] );
		$mag = sql_aget( "magazines", "code='".$_POST["code"]."'", "*" );
		error_log( "MAGID: ".$mag[0]["id"] );
		if( empty( $error ) ) {
			$wf = collectFromXml( TRKPATH.'/xml/'.PMD.'.xml', $_POST["code"], 'Workflow' );
			$workflow = (string) $wf['Workflow'];

			if( $mag[0]["type"] == "Adhoc" && !empty( $_POST["type"] ) ) {
				$pub = sql_aget( "publications", "magazine_id='".$_POST["magazine"]."' AND code='".$_POST["code"]."'", "*" );
				sql_delete( "parts", "pub_id='".$pub[0]["id"]."'" );
				$names = array( "pub_id", "name", "place", "color", "size", "mag_id", "grayscale" );

				for( $i = 0; $i < count( $_POST["type"] ); $i++ ) {
					$values = array( $pub[0]["id"], $_POST["type"][$i], $_POST["position"][$i] ?? "", $_POST["color"][$i], $_POST["trim_x"][$i]."x".$_POST["trim_y"][$i], $_POST["magazine"], $_POST["grayscale"][$i] );
					sql_add( "parts", $names, $values );
					if( $_POST["type"][$i] == "BEL" ) {
						syncInsidePartAdSize( $mag[0]["id"], $_POST["code"], $workflow, $_POST["trim_x"][$i], $_POST["trim_y"][$i] );
						}
					}

				// publications.pages is derived from the Parts rows just
				// written (syncPublicationPages()), not the standalone
				// "issueLength" field this dialog used to write directly -
				// that field is display-only now (see color.php).
				syncPublicationPages( $pub[0]["id"] );

				if( $pub[0]["publisher_id"] == "0" ) {
					toSwitch( 'new_publication' , 'publications|'.$pub[0]["id"], 'C_database/'.$_POST["code"].''.ISSUEPOSTFIX.'', 'issueData' );
					}
				}

			if( $mag[0]["type"] == "Regular" && !empty( $_POST["type"] ) ) {
				sql_delete( "parts", "pub_id='0' AND mag_id='".$mag[0]["id"]."'" );

				$names = array( "pub_id", "name", "place", "color", "size", "mag_id", "grayscale" );
				for( $i = 0; $i < count( $_POST["type"] ); $i++ ) {
					$values = array( "0", $_POST["type"][$i], $_POST["position"][$i] ?? "", $_POST["color"][$i], $_POST["trim_x"][$i]."x".$_POST["trim_y"][$i], $_POST["magazine"], $_POST["grayscale"][$i] );
					sql_add( "parts", $names, $values );
					if( $_POST["type"][$i] == "BEL" ) {
						syncInsidePartAdSize( $mag[0]["id"], $_POST["code"], $workflow, $_POST["trim_x"][$i], $_POST["trim_y"][$i] );
						}
					}
				}
			}

		$result = array( $error );
		}

	if( $_GET["sub"] == "checkRegularClientChangeResponse" ) {
		$pub = sql_get( 'magazines', 'code="'.$_POST["settings"].'"', '*' );
		$result = $pub[0];
		}
	
	if( $_GET["sub"] == "checkClientChangeResponse" ) {		
		$pub = sql_get( 'publications', 'code="'.$_POST["settings"].'"', '*' );
		$check = sql_aget( "publications", "id='".$pub[0][0]."'", "*" );
		
		$result = $check[0];
		}
		
	if( $_GET["sub"] == "jobsettings2" ) {
		parse_str($_POST['settings'], $_POST);
		sql_update( "publications", "deadline='".$_POST["Deadline"]."'", "id='".$pub[0][0]."'" );
		sql_update( "magazines", "name='".$_POST["Name"]."'", "id='".$mag[0][0]."'" );
					
		changeXmlDatabase( 'modify', $_POST );
		toSwitch( 'new_publication' , 'publications|'.$pub[0][0], 'C_database/'.$pub[0][10].''.ISSUEPOSTFIX.'', 'issueData' );			
		}
		
	if( $_GET["sub"] == "jobsettings" ) {	
		error_log( "JOBSETTINGS DEBUG" );
		$error = array();
		parse_str($_POST['parts'], $parts );
		parse_str($_POST['settings'], $_POST);
		if( $_POST["Deadline"] == "" ) $error[] = "Deadline";
		if( $_POST["Mails"] == "" ) $error[] = "Mails";

		if( $_POST["pn"] == "European" && in_array( $_POST["Workflow"], HAVE_PARTS) ) {
			for( $i = 0; $i < count( $parts["type"] ); $i++ ) {
				$pos = explode( ",", $parts["position"][$i] );
				for( $p = 0; $p < count( $pos ); $p++ ) {
					$pages = explode( "-", $pos[$p] );
					if( count($pages) != 2 ) {
						$error[] = "position_".$i;
						break;
						}
						
					if( !preg_match("/^[0-9-]+$/", $pos[$p] ) ) {
						$error[] = "position_".$i;
						}
					}
				}
			}
			
		// American Parts no longer carry a position/pages value at all (rule
		// B - colorStandard only, page count is tracked live instead), so
		// there's nothing left to format-validate here.

		if( count( $error ) == 0 ) {
			$_POST['Mails'] = explode( "\r\n", $_POST['Mails'] );
			$_POST['Mails'] = trim( implode( ";", $_POST['Mails'] ) );	
			
			$_POST['old_code'] = $_POST['code'];
			$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			error_log( "XPATH [".$_POST['code']."]" );
			foreach($xpath as $temp) {
				for( $i = 0; $i < count( $temp->Item ); $i++ ) {
					if( $temp->Item[$i]->Code == $_POST['code'] )
						break;
					}
				}
				
			$magazine = (string) $xml->Item[$i]->Code;
			$process = (string) $xml->Item[$i]->Workflow;
			error_log( (string) $xml->Item[$i]->Client );
			$allowed = false;
			
			$mag = sql_aget( 'magazines', 'code="'.$magazine.'"', 'id, publisher_id, type' );	
			$pub = sql_get( 'publications', 'code="'.$magazine.'" AND magazine_id="'.$mag[0]["id"].'"', '*' );

			$allowed = true;			
			if( $allowed ) {
				//$upload = ( $_POST["Uploadable"] == "Yes" ? "true" : "false" );
				//if( $xml->Item[$i]->Client == $_POST["Client"] ) {
				
				error_log( $_GET["saveParts"] );
				if( $_GET["saveParts"] == "true" ) {
					error_log( "bent" );				
					
					for( $p = 0; $p < count( $parts["type"] ); $p++ ) {
						if( $_GET["type"] == "long" ) {
							if( empty( $parts["trim_x"][$p] ) ) {
								$error[] = "trim_x_".$p;
								}
							if( empty( $parts["trim_y"][$p] ) ) {
								$error[] = "trim_y_".$p;
								}
							}

						// American Parts don't submit a position field at all
						// (rule B), so there's nothing to require here for them.
						if( $_POST["pn"] != "American" && empty( $parts["position"][$p] ) ) {
							$error[] = "pos_".$p;
							}
						}
						
					if( empty( $error ) ) {
						error_log( "nincs gáz" );
						error_log( "TYPE: ".$mag[0]["type"] );
						if( $mag[0]["type"] == "Adhoc" ) {
							error_log( "magazine_id='".$mag[0]["id"]."' AND code='".$magazine."'" );
							$pub2 = sql_aget( "publications", "magazine_id='".$mag[0]["id"]."' AND code='".$magazine."'", "*" );
							if( !empty( $parts["type"] ) ) {
								sql_delete( "parts", "pub_id='".$pub2[0]["id"]."'" );
								$names = array( "pub_id", "name", "place", "color", "size", "mag_id", "grayscale" );

								for( $p = 0; $p < count( $parts["type"] ); $p++ ) {
									$values = array( $pub2[0]["id"], $parts["type"][$p], $parts["position"][$p] ?? "", $parts["color"][$p], $parts["trim_x"][$p]."x".$parts["trim_y"][$p], $mag[0]["id"], $parts["grayscale"][$p] );
									sql_add( "parts", $names, $values );
									}
								syncPublicationPages( $pub2[0]["id"] );
								}

							if( $pub2[0]["publisher_id"] == "0" ) {
								toSwitch( 'new_publication' , 'publications|'.$pub2[0]["id"], 'C_database/'.$_POST["code"].''.ISSUEPOSTFIX.'', 'issueData' );
								}
							}

						if( $mag[0]["type"] == "Regular" && !empty( $parts["type"] ) ) {
							sql_delete( "parts", "pub_id='0' AND mag_id='".$mag[0]["id"]."'" );

							$names = array( "pub_id", "name", "place", "color", "size", "mag_id", "grayscale" );
							for( $p = 0; $p < count( $parts["type"] ); $p++ ) {
								$values = array( "0", $parts["type"][$p], $parts["position"][$p] ?? "", $parts["color"][$p], $parts["trim_x"][$p]."x".$parts["trim_y"][$p], $mag[0]["id"], $parts["grayscale"][$p] );
								sql_add( "parts", $names, $values );
								}

							//toSwitch( 'new_publication' , 'publications|'.$pub2[0]["id"], 'C_database/'.$_POST["code"].''.ISSUEPOSTFIX.'', 'issueData' );
							}
						}
					}
				
				
				error_log("Ügyfél ugyanaz");
				error_log( (string) $xml->Item[$i]->Client );
				$_POST["Client"] = (string) $xml->Item[$i]->Client;
				$_POST["Publisher"] = (string) $xml->Item[$i]->Publisher;
				sql_update( "publications", "deadline='".$_POST["Deadline"]."'", "id='".$pub[0][0]."'" );
				//sql_update( "magazines", "name='".$_POST["Name"]."'", "id='".$mag[0]["id"]."'" );
			
				changeXmlDatabase( 'modify', $_POST );
				error_log("toswitch");
				toSwitch( 'new_publication' , 'publications|'.$pub[0][0], 'C_database/'.$pub[0][10].''.ISSUEPOSTFIX.'', 'issueData' );
				
				//	}
				//else {
					/*
					sql_update( "publications", "clientChange='1'", "id='".$pub[0][0]."'"  );
					
					$array = array(
						"event" => "client_change",
						"jobCode" => $pub[0][10],
						"client" => (string) $xml->Item[$i]->Client,
						"description" => $_POST["Client"],
						);
					$send = SwitchSend_TESZT( $array );
					$error[] = "ClientChange";
					*/
					
					
					/*
					$check = sql_aget( "publications", "id='".$pub[0][0]."'", "*" );
					while( $check[0]["clientChange"] == "1" ) {
						sleep(1);
						$check = sql_aget( "publications", "id='".$pub[0][0]."'", "*" );
						}
						
					if( $check[0]["clientChange"] == "3" ) {
						$error["clientChange"] = "failed";
						$error["reason"] = $check[0]["clientChangeResult"];
						}
					
					if( $check[0]["clientChange"] == "2" ) {
						sql_update( "publications", "uploadable='".$_POST["Uploadable"]."', deadline='".$_POST["Deadline"]."'", "id='".$pub[0][0]."'" );
						sql_update( "magazines", "name='".$_POST["Name"]."'", "id='".$mag[0]["id"]."'" );
					
						changeXmlDatabase( 'modify', $_POST );
						toSwitch( 'new_publication' , 'publications|'.$pub[0][0], 'C_database/Adhoc_'.$pub[0][10].''.ISSUEPOSTFIX.'', 'issueData' );						
						}
					*/
				//	}
				}
			}
		
		$result = array( $error );
		}
	
	if( $_GET["sub"] == "workflow2" ) {
		parse_str($_POST['settings'], $_POST);
		$_POST['old_code'] = $_POST['code'];
		$_POST['deny'] = 'ki_set';

		$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		error_log( "XPATH [".$_POST['code']."]" );
		foreach($xpath as $temp) {
			for( $i = 0; $i < count( $temp->Item ); $i++ ) {
				if( $temp->Item[$i]->Code == $_POST['code'] )
					break;
				}
			}
		
		$magazine = (string) $xml->Item[$i]->Code;
		$mag = sql_get( 'magazines', 'code="'.$magazine.'"', 'id, publisher_id, type' );
		$current = (string) $xml->Item[$i]->Current;
		$process = (string) $xml->Item[$i]->Workflow;
		$allowed = false;
		$allowed = true;

		if( $allowed ) {
			sql_update( "magazines", "name='".$_POST["Name"]."'", "id='".$mag[0][0]."'" );		
			changeXmlDatabase( 'modify', $_POST );
			}		
				
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
		$values = array( $_SESSION['intra_user'], 'modMagazine', $mag[0][1], $mag[0][0], '', '', time() );
		sql_add( 'system_log', $names, $values );			
		}
		
	if( $_GET["sub"] == "workflow" ) {
		$error = array();
		parse_str($_POST['settings'], $_POST);
		
		if( $_POST["Name"] == "" ) $error[] = "Name";
		
		if( count( $error ) == 0 ) {
			
			$_POST['old_code'] = $_POST['code'];
			$_POST['deny'] = 'ki_set';
		
			$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			error_log( "XPATH [".$_POST['code']."]" );
			foreach($xpath as $temp) {
				for( $i = 0; $i < count( $temp->Item ); $i++ ) {
					if( $temp->Item[$i]->Code == $_POST['code'] )
						break;
					}
				}
			
			$magazine = (string) $xml->Item[$i]->Code;
			$mag = sql_get( 'magazines', 'code="'.$magazine.'"', 'id, publisher_id, type' );

			sql_update( "magazines", "HideApprovedComments='".$_POST["ApprovedComments"]."'", "id='".$mag[0][0]."'" );
			sql_update( "magazines", "preflight='".( $_POST["Preflight"] ?? "Yes" )."'", "id='".$mag[0][0]."'" );

			// Turning Preflight off mid-production isn't a real workflow
			// (there's normally no reason to disable it once a job is
			// underway), but if it ever happens, existing red markers must
			// disappear immediately rather than linger until each page
			// happens to get a new version resubmitted - the normal trigger
			// that clears them (see page_pdf-handler.php). Scoped to this
			// magazine's own pageinfo rows (Preflight is a magazine-level
			// setting, not per-issue, so this matches every issue ever
			// processed under it, same scope page_pdf-handler.php itself
			// uses for this job).
			if( ( $_POST["Preflight"] ?? "Yes" ) === "No" ) {
				sql_update( 'pageinfo', 'preflight_error="0", preflight_report="", preflight_origname=""', 'code="'.$magazine.'"' );
				sql_delete( 'preflight_issues', 'page_id IN (SELECT id FROM pageinfo WHERE code="'.$magazine.'")' );
				}
			
			if( $xml->Item[$i]->Client == $_POST["Client"] ) {
				error_log("Ügyfél ugyanaz");
				$current = (string) $xml->Item[$i]->Current;
				$process = (string) $xml->Item[$i]->Workflow;
				$allowed = false;
				$allowed = true;

				if( $allowed ) {
					sql_update( "magazines", "name='".$_POST["Name"]."'", "id='".$mag[0][0]."'" );		
					changeXmlDatabase( 'modify', $_POST );
					}		
				
				$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
				$values = array( $_SESSION['intra_user'], 'modMagazine', $mag[0][1], $mag[0][0], '', '', time() );
				sql_add( 'system_log', $names, $values );	
				}
			else {
				sql_update( "magazines", "clientChange='1'", "id='".$mag[0][0]."'"  );
				$array = array(
					"event" => "client_change",
					"jobCode" => $mag[0][0],
					"client" => (string) $xml->Item[$i]->Client,
					"description" => $_POST["Client"],
					"type" => $mag[0][2],
					);
				$send = SwitchSend_TESZT( $array );
				$error[] = "ClientChange";				
				}
			}
		
		$result = array( $error );
		}			
		
	if( $_GET["sub"] == "newIssue" ) {
		$error = array();
		$szamlength = strlen( strval( $_POST['szam'] ) );
		
		if( $szamlength < 2 or $szamlength > 4 ) $error[] = "szam";
		if( $_POST['job_code'] == "" ) $error[] = "job_code";
		
		if ( array_key_exists( "page_nr", $_POST ) ) {
			if( $_POST['page_nr'] == "" ) $error[] = "page_nr";
			}
		else {
			$_POST['page_nr'] = 0;
			}
		
		if( $_POST['dl'] == "" ) $error[] = "dl";
	
		$checker = sql_aget( "publications", "magazine_id='".$_POST['magazine']."' AND code='".$_POST['job_code']."'", "id" );
		if( $checker[0]["id"] != "" ) $error[] = "szam";
		
		if( count( $error ) == 0 ) {				
			$_POST['dl'] = str_replace( " ", "T", $_POST['dl'] );
			$p_id = sql_get( "magazines", "id='".$_POST['magazine']."'", "publisher_id" );
			$p_id = $p_id[0][0];
			
			// 'pages' below is a placeholder, not the form's page_nr field -
			// like modIssue's Parts editor, this form lets page_nr and each
			// Part's own position range be edited/submitted independently,
			// so publications.pages is derived from the Parts rows via
			// syncPublicationPages() right after they land, instead of
			// trusted from page_nr directly (confirmed live on PRFU_2650:
			// page_nr said 16 while the Parts positions summed to 20).
			$names = array( "publisher_id", "magazine_id", "pages", "uploadable", "code", "deadline", "enhance", "specificName" );
			$values = array( $p_id, $_POST['magazine'], 0, "false", $_POST['job_code'], $_POST['dl'], $_POST["enhance"], $_POST["customname"] );
			$id = sql_add( "publications", $names, $values );

			$names = array( "pub_id", "name", "place", "color", "size", "mag_id", "grayscale" );
			$wf = collectFromXml( TRKPATH.'/xml/'.PMD.'.xml', $_POST['mcode'], 'Workflow' );
			$workflow = (string) $wf['Workflow'];

			// A Regular magazine with no parts template configured yet
			// (via jobsettings.php) submits no type[] rows at all, not an
			// empty array - count(null) is a PHP 8 TypeError.
			for( $i = 0; $i < count( $_POST["type"] ?? array() ); $i++ ) {
				$values = array( $id, $_POST["type"][$i], $_POST["position"][$i] ?? "", $_POST["color"][$i], $_POST["trim_x"][$i]."x".$_POST["trim_y"][$i], $_POST['magazine'], $_POST["grayscale"][$i] );
				sql_add( "parts", $names, $values );
				if( $_POST["type"][$i] == "BEL" ) {
					syncInsidePartAdSize( $_POST['magazine'], $_POST['mcode'], $workflow, $_POST["trim_x"][$i], $_POST["trim_y"][$i] );
					}
				}
			syncPublicationPages( $id );

			$mag = sql_get( 'magazines', 'id="'.$_POST['magazine'].'"', 'code, name, type' );
			toSwitch( 'new_publication' , 'publications|'.$id, 'C_database/'.$mag[0][0].'_'.$_POST['job_code'].''.ISSUEPOSTFIX.'', 'issueData' );

			$allowedMags = explode( ",", $user[0][21] );
			if( $allowedMags[0]!= "" ) {
				if( !in_array( $_POST['magazine'], $allowedMags ) ) $allowedMags[] = $_POST['magazine'];
				$amags = implode( ",", $allowedMags );
				}
			else {
				$amags = $_POST['magazine'];
				}
			sql_update( "accounts", "showMagazines='".$amags."'", "id='".$user[0][0]."'" );
			
			$admins = sql_aget( "accounts", "`group`='2'", "id, showMagazines" );
			for( $i = 0; $i < count( $admins ); $i++ ) {
				$allowedMags = explode( ",", $admins[$i]["showMagazines"] );
				if( $admins[$i]["id"] != $user[0][0] ) {
					if( !in_array( $_POST['magazine'], $allowedMags ) ) $allowedMags[] = $_POST['magazine'];
					$amags = implode( ",", $allowedMags );
					sql_update( "accounts", "showMagazines='".$amags."'", "id='".$admins[$i]["id"]."'" );
					}
				}			
			
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
			$values = array( $_SESSION['intra_user'], 'newMagazine', '0', $_POST['magazine'], '', '', time() );
			sql_add( 'system_log', $names, $values );

			$job = sql_get( "publications", 'id="'.$id.'"', '*'  );
			$publisher = sql_get( 'publishers', 'id="'.$job[0][1].'"', 'name' );

			$array = array(
				"event" => "issue_created",
				"client" => $publisher[0][0],
				"jobCode" => $mag[0][0],
				"issue" => $job[0][10],
				);
			$error = SwitchSend( $array );

			$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			error_log( "XPATH [".$_POST['code']."]" );
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $mag[0][0] )
						break;
					}
				}
							
			$mails = gatedMailRecipients( $_POST['magazine'], $mag[0][2], (string) $xml->Item[$x]->Mails );
			error_log( "New issue levelkuldes. Cimzettek: ".$xml->Item[$x]->Mails );
			for( $i = 0; $i < count( $mails ); $i++ ) {
				if( !empty( $mails[$i] ) ) {
					$subject = $mag[0][0]."_".$_POST['job_code']." létrehozva a Trackeren";
					$to = $mails[$i]."|".$mails[$i];
					$body = "
	Kedves Felhasználónk!<br>
	<br>
	A ".$mag[0][1]." kiadvány ".$mag[0][0]."_".$_POST['job_code']." kóddal létre lett hozva a Tracker rendszerben.<br>
	<br>
	Üdvözlettel:<br>
	<br>
	Colorcom Media<br>";
	
					error_log( "Newissue levél küldés ide: ".$mails[$i] );
					produkcioSendmail( $subject, $body, $to );
					}			
				}
			}
		
		$result = array( $error );
		}

	if( $_GET["sub"] == "loadPub" ) {
		$txt = "";
		if( $_GET["Type"] == "Adhoc" ) {
			$avaiable = array( 'Name', 'Client', 'Code', 'Mails', 'Workflow', 'Preflight', 'Enhance', 'PageNumbering', 'Deadline', 'Language' );
		
			$default = array();
			foreach( $avaiable as $key ) {
				$value = (string) $xml->Item[$i]->$key;
				$temp = array();
				switch( $key ) {
					case 'PageNumbering':
						$temp = array( 'American', 'European' );
						$default[$key] = "American";
						break;
					case 'Workflow':
						$temp = array( 'Full', 'Hybrid', 'Resize', 'Auto' );
						$default[$key] = "Resize";
						break;
					case 'Preflight':
						$temp = array( 'Yes', 'No' );
						$default[$key] = "Yes";
						break;
					case 'Enhance':
						$temp = array( 'Skintone', 'Food', 'Jewellery', 'Vivid', 'Paintings', 'Minimal', 'General', 'Resize only', 'Null' );
						$default[$key] = "General";
						break;
					case 'Language':
						$temp = array( 'HU', 'EN' );
						$default[$key] = "EN";
						break;
					case 'Client':
						$pubs = sql_get( 'publishers', '1 ORDER BY `name` ASC ', '*' );
						for( $x = 0; $x < count( $pubs ); $x++ ) {
							$temp[] = $pubs[$x][1];
							}
						break;
					}

				$txt .= "<tr class='".$key."'>";
					if( $key == "Client" ) { $txt .= "<td valign='top' align='left'>".$lang['xml'][$key]."</td>"; }
					elseif( $key == "Mails" ) {
						$txt .= "<td align='left'><span class='mailBox' style='display: none;'>".$lang['xml'][$key]."</span><span class='userBox'>Art Director</span></td>";
						}
					else { $txt .= "<td align='left' style='width: 188px;'>".$lang['xml'][$key]."</td>"; }
						
					$txt .= "<td align='left'>";
						if( $key == 'Name' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value=''>";
							}
						elseif( $key == 'Code' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value='".codeGen()."' readonly>";
							}
						elseif( $key == 'Client' ) {
							$txt .= "<input type='radio' name='ClientType' class='ClientType' value='unknown'>Ad-hoc";
							$txt .= "&nbsp;&nbsp;&nbsp;&nbsp;";
							$txt .= "<input type='radio' name='ClientType' class='ClientType' value='known' checked>Registered";
							$txt .= "<br>";
							// A clientless Adhoc job's Client must always be an empty
							// string, never a free-typed name - Switch has its own
							// hardwired, designated folder for clientless jobs, keyed
							// off that field being empty (see SYSTEM_STATE.md's
							// "Publication ownership model" section). A free-text
							// input here used to make it possible to type a name for
							// a nominally clientless job, which would have leaked
							// into some but not all of the Switch touchpoints a
							// creation triggers. Hidden input keeps $_POST['Client']
							// defined (avoiding an undefined-array-key warning further
							// down) while making "" the only possible value.
							$txt .= "<span id='unknownClient' style='display: none;'><input type='hidden' id='".$key."' name='".$key."' value=''></span>";
							$txt .= "<span id='knownClient'>";
								$txt .= "<select style='margin-left: -1px;' id='".$key."2' name='".$key."2'>";
								$txt .= "<option value='' selected disabled>--- Select Client ---</option>";
								foreach( $temp as $t ) {
									$txt .= "<option value='".$t."'>".$t."</option>";
									}
								$txt .= "</select>";
							$txt .= "</span>";
							}
						elseif( $key == 'Deadline' ) {
							$txt .= "<input type='text' class='datepicker' id='".$key."' name='".$key."' value=''>";
							}							
						elseif( $key == 'Mails' ) {
							$txt .= "<span class='mailBox' style='display: none;'><input type='text' id='".$key."' name='".$key."' value=''></span>";
							
							//$pubs = sql_get( 'publishers', '1 ORDER BY `name` ASC ', '*' );
							$txt .= "<span class='userBox userSelect'>";
								$u = sql_aget( "accounts", "publisher='".$pubs[0][0]."'", "*" );
								$txt .= "<select id='adhocUser' name='adhocUser'>";
									for( $i = 0; $i < count( $u ); $i++ ) {
										$txt .= "<option value='".$u[$i]["id"]."'>".$u[$i]["full_name"]."</option>";
										}
								$txt .= "</select>";
							$txt .= "</span>";
							}
						elseif( $key == 'Enhance' ) {
							$txt .= "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								$tempVal = $t;
								if( !empty( $changeName[$t] ) ) {
									$tempVal = $changeName[$t];
									}
								$txt .= "<option ";
								if( $value == $tempVal ) $txt .= "selected ";
									$txt .= "value='".$tempVal."'>".$t."</option>";
								}
							$txt .= "</select>";
							}							
						elseif( $temp == '' ) {
							$txt .= "<input id='".$key."' type='text' name='".$key."' value='".$value."'>";
							}
						else {					
							$txt .= "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								$txt .= "<option ";
								if( $value == $t ) $txt .= "selected ";
								if( $default[$key] == $t ) $txt .= "selected ";
									$txt .= "value='".$t."'>".$t."</option>";
								}
							$txt .= "</select>";
							}
					$txt .= "</td>";
				$txt .= "</tr>";
				}
			
			$txt .= "<input type='hidden' name='Publisher' id='Publisher' value='Adhoc'>";			
			$txt .= "<input type='hidden' name='FlatplanStages' id='FlatplanStages' value='1'>";			
			$txt .= "<input type='hidden' name='Resolution' id='Resolution' value='300'>";			
			$txt .= "<input type='hidden' name='PDFstandard' id='PDFstandard' value='PDFX1A'>";			
			$txt .= "<input type='hidden' name='OutputFormat' id='OutputFormat' value='TIFF'>";			
			$txt .= "<input type='hidden' name='CustomCode' id='CustomCode' value='No'>";			
			$txt .= "<input type='hidden' name='ImageRename' id='ImageRename' value='No'>";			
			$txt .= "<input type='hidden' name='LocalStorage' id='LocalStorage' value='Adhoc'>";			
			$txt .= "<input type='hidden' name='MailComm' id='MailComm' value='No'>";			
			$txt .= "<input type='hidden' name='WebImages' id='WebImages' value='No'>";			

			$txt .= "<tr><td algin='left' colspan='2'>";
			$txt .= "<table class='panelTable' cellspacing='0' cellpadding='0'>";
				$txt .= "<tbody id='partContent'>";
					$txt .= "<tr>";
						$txt .= "<td colspan='2' style='padding-top: 15px;'>Parts</td>";
					$txt .= "</tr>";
				$txt .= "</tbody>";
				$txt .= "<tfoot>";
					$txt .= "<tr>";
						$txt .= "<td><img onclick='newLine()' src='images/trk_plus.png' style='cursor: pointer;'></td>";
					$txt .= "</tr>";
				$txt .= "</tfoot>";
			$txt .= "</table>";
			
			$txt .= "</td></tr>";
			}
			
		if( $_GET["Type"] == "Regular" ) {
			// LocalStorage left the UI entirely: every Regular publication
			// stores PubFolder (hardwired via the hidden input below).
			$avaiable = array( 'Client', 'Name', 'Code', 'Workflow', 'Preflight', 'Enhance', 'PageNumbering', 'Language' );

			foreach( $avaiable as $key ) {
				$value = (string) $xml->Item[$i]->$key;
				$temp = array();
				switch( $key ) {
					case 'Workflow':
						$temp = array( 'Full', 'Hybrid', 'Resize', 'Auto' );
						$default[$key] = "Resize";
						break;
					case 'Preflight':
						$temp = array( 'Yes', 'No' );
						$default[$key] = "Yes";
						break;
					case 'Enhance':
						$temp = array( 'Skintone', 'Food', 'Jewellery', 'Vivid', 'Paintings', 'Minimal', 'General', 'Resize only', 'Null' );
						$default[$key] = "General";
						break;
					case 'Language':
						$temp = array( 'HU', 'EN' );
						$default[$key] = "EN";
						break;
					case 'Client':
						$pubs = sql_get( 'publishers', '1 ORDER BY `name` ASC ', '*' );
						for( $x = 0; $x < count( $pubs ); $x++ ) {
							$temp[] = $pubs[$x][1];
							}
						break;
					case 'PageNumbering':
						$temp = array( 'American', 'European' );
						$default[$key] = "European";
						break;
					}

				$txt .= "<tr class='".$key."'>";
					$txt .= "<td align='left' style='width: 188px;'>".$lang['xml'][$key]."</td>";
					$txt .= "<td align='left'>";
						if( $key == 'Name' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value=''>";
							}
						elseif( $key == 'Code' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value=''>";
							}						
						elseif( $key == 'Mails' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value=''>";
							}
						elseif( $temp == '' ) {
							$txt .= "<input id='".$key."' type='text' name='".$key."' value='".$value."'>";
							}
						elseif( $key == 'Enhance' ) {
							$txt .= "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								$tempVal = $t;
								if( !empty( $changeName[$t] ) ) {
									$tempVal = $changeName[$t];
									}
								$txt .= "<option ";
								if( $value == $tempVal ) $txt .= "selected ";
									$txt .= "value='".$tempVal."'>".$t."</option>";
								}
							$txt .= "</select>";
							}
						elseif( $key == 'Client' ) {
							// No sensible default exists for who owns a new
							// publication - unlike Workflow/Enhance/Language, this
							// must never silently pre-select a real client (e.g. the
							// browser defaulting to whichever publisher sorts first
							// alphabetically). Leading blank option forces an
							// explicit choice; server-side (pubsApply.php's 'create'
							// handler) rejects submission if it's left unselected.
							$txt .= "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
								$txt .= "<option value=''>-- ".$lang['xml']['SelectClient']." --</option>";
								foreach( $temp as $t ) {
									$txt .= "<option value='".$t."'>".$t."</option>";
									}
							$txt .= "</select>";
							}
						else {
							$txt .= "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								$txt .= "<option ";
								if( $value == $t ) $txt .= "selected ";
								if( $default[$key] == $t ) $txt .= "selected ";
									$txt .= "value='".$t."'>".$t."</option>";
								}
							$txt .= "</select>";
							}
					$txt .= "</td>";
				$txt .= "</tr>";
				}

			$txt .= "<input type='hidden' name='FlatplanStages' id='FlatplanStages' value='1'>";
			$txt .= "<input type='hidden' name='Resolution' id='Resolution' value='300'>";
			$txt .= "<input type='hidden' name='PDFstandard' id='PDFstandard' value='PDFX1A'>";
			$txt .= "<input type='hidden' name='OutputFormat' id='OutputFormat' value='TIFF'>";
			$txt .= "<input type='hidden' name='CustomCode' id='CustomCode' value='No'>";
			$txt .= "<input type='hidden' name='ImageRename' id='ImageRename' value='No'>";
			$txt .= "<input type='hidden' name='WebImages' id='WebImages' value='No'>";
			$txt .= "<input type='hidden' name='ArchiveMode' id='ArchiveMode' value='RGB'>";
			$txt .= "<input type='hidden' name='LocalStorage' id='LocalStorage' value='PubFolder'>";
			}
			
		$result = $txt;
		}
	
	print json_encode( $result );
	
?>	