<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');
	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( "../engine/switchAPI.php" );
	
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
	// file's sub== handlers checked authentication before running.
	// Same fix: one gate before any sub is dispatched.
	if( empty( $user[0][0] ) ) {
		print json_encode( array( array( "Unauthorized" ) ) );
		exit;
		}
	
	if( $_GET["sub"] == "savecolor" ) {
		sql_update( "accounts", "color='#".$_GET["color"]."'", "id='".$_SESSION['intra_user']."'" );
		}
	
	if( $_GET["sub"] == "manage" ) {
		$error = array();

		$mag = sql_aget( "magazines", "id='".$_POST["mag"]."'", "*" );
		$pubRow = array();
		if( $mag[0]["type"] == "Adhoc" ) {
			$pubRow = sql_aget( "publications", "code='".$mag[0]["code"]."'", "*" );
			$mag[0]["publisher_id"] = $pubRow[0]["owner"];
			}

		$pub = $mag[0]["publisher_id"];

		// accounts/magazines/publications are all MyISAM, which has no
		// transaction support - BEGIN/COMMIT/ROLLBACK would silently do
		// nothing on them, so a real DB transaction can't protect this
		// request against a crash partway through. The best available
		// substitute: do every read, lookup and bit of computation that
		// could still go wrong FIRST (nothing here writes to the DB yet),
		// then run the "clear this magazine off everyone's showMagazines,
		// then restore it for whoever's selected" pair immediately back to
		// back with nothing else in between. A magazine used to be able to
		// vanish from every account's visibility with no way back if
		// anything failed in that gap - this keeps the gap as close to
		// zero as the storage engine allows.

		$usersUnderPub = sql_aget( "accounts", "publisher='".$pub."'", "*" );
		$staffUsers = sql_aget( "accounts", "publisher='6'", "*" );
		$selectedUsers = $_POST["users"] ?? array();

		$emails = array();
		for( $i = 0; $i < count( $_POST["mailto"] ?? array() ); $i++ ) {
			$temp = sql_aget( "accounts", "id='".$_POST["mailto"][$i]."'", "*" );
			$right_temp = sql_aget( 'user_groups', 'id="'.$temp[0]["group"].'"', '*' );

			if( !empty( $temp[0]["email"] ) && $right_temp[0]["magazine_upload"] == "1" ) {
				$emails[] = trim( $temp[0]["email"] );
				}
			}

		for( $i = 0; $i < count( $_POST["tempregmailto"] ?? array() ); $i++ ) {
			$temp = sql_aget( "accounts", "id='".$_POST["tempregmailto"][$i]."'", "*" );
			$right_temp = sql_aget( 'user_groups', 'id="'.$temp[0]["group"].'"', '*' );

			if( !empty( $temp[0]["email"] ) && $right_temp[0]["magazine_upload"] == "1" ) {
				$emails[] = trim( $temp[0]["email"] );
				}
			}

		// Captured before this submission's own tempusers[] additions run
		// below, so the removal list only ever considers temp users that
		// existed prior to this request - otherwise a user just invited in
		// this same submission would look like a removed one, since it can
		// never appear in tempregusers[] (that field only ever reflects the
		// panel's pre-existing rows).
		$existingTempUsers = array();
		$utemp = array();
		if( $mag[0]["type"] == "Adhoc" ) {
			$existingTempUsers = sql_aget( "accounts", "usertype='Temp' AND temppubid='".$pubRow[0]["id"]."'", "*" );
			for( $i = 0; $i < count( $existingTempUsers ); $i++ ) {
				if( !in_array( $existingTempUsers[$i]["email"], $_POST["tempregusers"] ?? array() ) ) {
					$utemp[] = $existingTempUsers[$i];
					}
				}
			}

		//Meglévőek teljes eltávolítása
		for( $i = 0; $i < count( $usersUnderPub ); $i++ ) {
			$mags = explode( ",", $usersUnderPub[$i]["showMagazines"] );
			$index = array_search( $mag[0]["id"], $mags );

			if( $index !== false ) {
				array_splice( $mags, $index, 1 );
				}
			sql_update( "accounts", "showMagazines='".(implode( ",", $mags ))."'", "id='".$usersUnderPub[$i]["id"]."'" );
			}

		for( $i = 0; $i < count( $staffUsers ); $i++ ) {
			$mags = explode( ",", $staffUsers[$i]["showMagazines"] );
			$index = array_search( $mag[0]["id"], $mags );

			if( $index !== false ) {
				array_splice( $mags, $index, 1 );
				}
			sql_update( "accounts", "showMagazines='".(implode( ",", $mags ))."'", "id='".$staffUsers[$i]["id"]."'" );
			}

		//Látásra Flaggelés
		for( $i = 0; $i < count( $selectedUsers ); $i++ ) {
			$temp = sql_aget(  "accounts", "id='".$selectedUsers[$i]."'", "*" );

			$mags = explode( ",", $temp[0]["showMagazines"] );
			while( ( $index = array_search( $mag[0]["id"], $mags ) ) !== false ) {
				array_splice( $mags, $index, 1 );
				}

			$mags[] = $mag[0]["id"];

			sql_update( "accounts", "showMagazines='".implode( ",", $mags )."'", "id='".$temp[0]["id"]."'" );
			}

		//Ideiglenes userek eltávolítása
		if( $mag[0]["type"] == "Adhoc" ) {
			for( $i = 0; $i < count( $existingTempUsers ); $i++ ) {
				sql_update( "accounts", "showMagazines=''", "id='".$existingTempUsers[$i]["id"]."'" );
				}
			}

		//Ideiglenes felhasználók kezelése
		if( $mag[0]["type"] == "Adhoc" ) {
			//Újjonnan felvittek hozzáadása
			for( $i = 0; $i < count( $_POST["tempusers"] ?? array() ); $i++ ) {
				$check = sql_aget( "accounts", "email='".$_POST["tempusers"][$i]."' AND temppubid='".$pubRow[0]["id"]."'", "*" );
				if( empty( $check[0]["id"] ) ) {
					// If this email already belongs to a real registered account,
					// this job-scoped Temp row is a separate access link for them
					// (their global role is unaffected everywhere else) rather than
					// an unknown one-off contact - link back to it so the panel can
					// show whose invite this is, and greet them by name instead of
					// leaving name/full_name blank.
					$existing = sql_aget( "accounts", "email='".$_POST["tempusers"][$i]."' AND usertype!='Temp'", "*" );
					$linkedId = !empty( $existing[0]["id"] ) ? $existing[0]["id"] : 0;
					$fullName = $linkedId ? $existing[0]["full_name"] : $_POST["tempusers"][$i];

					$names = array( "name", "pass", "type", "publisher", "email", "full_name", "group", "actual", "showMagazines", "usertype", "temppubid", "linked_account_id" );
					$values = array( "", "", "adhoc", $pubRow[0]["publisher_id"], $_POST["tempusers"][$i], $fullName, $_POST["tempusersgroup"][$i], $pubRow[0]["code"]."_".$pubRow[0]["code"], $pubRow[0]["magazine_id"], "Temp", $pubRow[0]["id"], $linkedId );
					$uid = sql_add( "accounts", $names, $values );

					$hash = md5( "adhoctempuser-".time()."-".$_POST["tempusers"][$i] );
					$names = array( "user_id", "hash", "magazine_id", "email", "time", "pubid" );
					$values = array( $uid, $hash, $pubRow[0]["magazine_id"], $_POST["tempusers"][$i], time(), $pubRow[0]["id"] );
					sql_add( "adhoc_hotlinks", $names, $values );

					$link = "https://".URL."/index.php?hash=".$hash;
					$to = $_POST["tempusers"][$i]."|".$_POST["tempusers"][$i];
					$subject =  "".$mag[0]["name"]." - Colorcom Tracker hozzáférés";
					if( $linkedId ) {
						$body = "Kedves ".$fullName.",<br>
						<br>
						a(z) ".$mag[0]["name"]." kiadvány létrehozásában való közreműködéshez ehhez a munkához tartozó, elkülönített hozzáférési linket kaptál (ez nem a meglévő Tracker fiókod, a jogosultságaid ezen a linken csak erre a munkára vonatkoznak): <a href='".$link."'>".$link."</a>.<br>
						<br>
						Üdvözlettel:<br>
						Colorcom Media";
						}
					else {
						$body = "Kedves ".$_POST["tempusers"][$i].",<br>
						<br>
						a(z) ".$mag[0]["name"]." kiadvány létrehozásában való közreműködéshez kérjük lépj be a Colorcom Tracker rendszerbe a következő linkre kattintva: <a href='".$link."'>".$link."</a>.<br>
						<br>
						Üdvözlettel:<br>
						Colorcom Media";
						}
					produkcioSendmail( $subject, $body, $to );
					$emails[] = trim( $_POST["tempusers"][$i] );
					}
				}

			//Eltávolítottak törlése
			for( $i = 0; $i < count( $utemp ); $i++ ) {
				sql_delete( "accounts", "id='".$utemp[$i]["id"]."'" );
				sql_delete( "adhoc_hotlinks", "user_id='".$utemp[$i]["id"]."'" );
				}
			}

		//PMD Módosítás
		
		if( count( $emails ) > 0 ) {
			$emails = implode( ";", $emails );
			
			$send['Mails'] = $emails;
			$send['MailComm'] = "Yes";
			$send['old_code'] =$mag[0]['code'];
			$send['deny'] = 'ki_set';	
			}
		else {
			$emails = "";
			
			$send['Mails'] = "";
			$send['MailComm'] = "No";
			$send['old_code'] =$mag[0]['code'];
			$send['deny'] = 'ki_set';	
			}
		
		$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $i = 0; $i < count( $temp->Item ); $i++ ) {
				if( $temp->Item[$i]->Code == $mag[0]['code'] )
					break;
				}
			}
		
		changeXmlDatabase( 'modify', $send, '../xml/'.PMD.'.xml' );
		
		$result = array( $error );
		}
	
	if( $_GET["sub"] == "settings" ) {
		$error = array();
		$pass_change = 0;
		if( $_POST['u_fullname'] == '' ) $error[] = "u_fullname";
		if( !filter_var( $_POST[ "u_mail" ], FILTER_VALIDATE_EMAIL ) ) $error[] = "u_mail";
		
		if( count($error) == 0 ) {
			if( isset( $_POST['o_pass'] ) && ( $_POST['o_pass'] != '' or $_POST['u_pass'] != '' or $_POST['u_pass2'] != '' ) ) {
				if( $_POST['o_pass'] != '' && $_POST['u_pass'] != '' && $_POST['u_pass2'] != '' ) {
					$pass = sql_get( 'accounts', 'id=\''.$_SESSION['intra_user'].'\'', 'pass' );
					
					if( checkPassword( $_POST['o_pass'], $pass[0][0], $_SESSION['intra_user'] ) ) {
						if( $_POST['u_pass'] == $_POST['u_pass2'] ) {
							$pass_change = 1;
							}
						else {
							$error[] = "u_pass";
							$error[] = "u_pass2";
							}
						}
					else {
						$error[] = "o_pass";
						}
					}
				else {
					if( $_POST['o_pass'] == '' ) $error[] = "o_pass";
					if( $_POST['u_pass'] == '' ) $error[] = "u_pass";
					if( $_POST['u_pass2'] == '' ) $error[] = "u_pass2";
					}
				}
			
			$langmod = ( ( $user[0][26] != $_POST['prepresstools_on'] )  ? "yes" : "no" );
			if( $langmod == "no" ) {
				$langmod = ( ( $user[0][17] != $_POST['u_lang'] )  ? "yes" : "no" );
				}
			
			if( empty( $_POST['prepresstools_on'] ) ) {
				$_POST['prepresstools_on'] = 0;
				}
			// safety_on is an unchecked-by-default checkbox too, and had no
			// equivalent default - an unchecked box sends no value at all, so
			// $_POST['safety_on'] was undefined and coerced to an empty
			// string, which MySQL's strict mode rejects for this integer
			// column. That failure aborted the WHOLE update statement (every
			// field in $names, not just this one), so unchecking this one
			// checkbox silently prevented ANY setting on this form - prepress
			// tools included - from ever saving.
			if( empty( $_POST['safety_on'] ) ) {
				$_POST['safety_on'] = 0;
				}

			$names = array(  'email', 'full_name', 'landing', 'unit', 'safety_int', 'safety_on', 'prepresstools', 'multiplelogin', 'lang' );
			$datas = array(  $_POST['u_mail'], $_POST['u_fullname'], $_POST['u_landing'], $_POST['u_unit'], str_replace( ",", ".", $_POST['safety_int'] ), $_POST['safety_on'], $_POST['prepresstools_on'], ( empty( $_POST['multiplelogin'] ) ? "0" : $_POST['multiplelogin'] ), $_POST['u_lang'] );
			$command = '';

			for( $i = 0; $i < count( $names ); $i++ ) {
				$command .= $names[$i].'=\''.$datas[$i].'\'';

				if( $i < count( $names )-1 ) {
					$command .= ', ';
					}
				}

			if( $pass_change == 1 ) {
				$command .= ', pass=\''.password_hash($_POST['u_pass'], PASSWORD_DEFAULT).'\'';
				}

			sql_update( 'accounts', $command, 'id=\''.$_SESSION['intra_user'].'\'' );

			// Gate B: the user's own per-magazine mail unsubscribe preference
			// (accounts.mailOptOut), independent of the admin's PMD <Mails> "M"
			// checkbox (Gate A) - this handler never touches the PMD XML. Rebuilt
			// from scratch each save against every magazine the user is currently
			// associated with (showMagazines); a magazine missing from the
			// "userMails" checkboxes means the user unchecked "Mails" for it.
			$magID = array_values( array_filter( explode( ",", $user[0][21] ) ) );
			$userMails = $_POST["userMails"] ?? array();

			$optOut = array();
			for( $i = 0; $i < count( $magID ); $i++ ) {
				if( !in_array( $magID[$i], $userMails ) ) {
					$optOut[] = $magID[$i];
					}
				}

			sql_update( 'accounts', "mailOptOut='".implode( ",", $optOut )."'", 'id=\''.$_SESSION['intra_user'].'\'' );
			}

		$result = array( $error, $langmod );
		}
	
	print json_encode( $result );
	
?>	