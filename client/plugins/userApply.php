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
	
	if( $_GET["sub"] == "savecolor" ) {
		sql_update( "accounts", "color='#".$_GET["color"]."'", "id='".$_SESSION['intra_user']."'" );
		}
	
	if( $_GET["sub"] == "manage" ) {
		$error = array();

		$mag = sql_aget( "magazines", "id='".$_POST["mag"]."'", "*" );
		if( $mag[0]["type"] == "Adhoc" ) {
			$pub = sql_aget( "publications", "code='".$mag[0]["code"]."'", "*" );
			$mag[0]["publisher_id"] = $pub[0]["owner"];
			}
		
		$pub = $mag[0]["publisher_id"];
		$users = sql_aget( "accounts", "publisher='".$pub."'", "*" );
		
		//Meglévőek teljes eltávolítása
		for( $i = 0; $i < count( $users ); $i++ ) {
			$mags = explode( ",", $users[$i]["showMagazines"] );
			$index = array_search( $mag[0]["id"], $mags );

			if( $index !== false ) {
				array_splice( $mags, $index, 1 );
				}
			sql_update( "accounts", "showMagazines='".(implode( ",", $mags ))."'", "id='".$users[$i]["id"]."'" );
			
			}
		
		//Ideiglenes userek eltávolítása
		if( $mag[0]["type"] == "Adhoc" ) {
			$pub = sql_aget( "publications", "code='".$mag[0]["code"]."'", "*" );
			$users = sql_aget( "accounts", "usertype='Temp' AND temppubid='".$pub[0]["id"]."'", "*" );
			for( $i = 0; $i < count( $users ); $i++ ) {
				sql_update( "accounts", "showMagazines=''", "id='".$users[$i]["id"]."'" );
				}
			}
		
		
		$users = sql_aget( "accounts", "publisher='6'", "*" );
		for( $i = 0; $i < count( $users ); $i++ ) {
			$mags = explode( ",", $users[$i]["showMagazines"] );
			$index = array_search( $mag[0]["id"], $mags );

			if( $index !== false ) {
				array_splice( $mags, $index, 1 );
				}
			sql_update( "accounts", "showMagazines='".(implode( ",", $mags ))."'", "id='".$users[$i]["id"]."'" );
			}
				
		//Látásra Flaggelés
		for( $i = 0; $i < count( $_POST["users"] ); $i++ ) {
			$temp = sql_aget(  "accounts", "id='".$_POST["users"][$i]."'", "*" );
			
			$mags = explode( ",", $temp[0]["showMagazines"] );
			while( $index = array_search( $mag[0]["id"], $mags ) ) {	
				array_splice( $mags, $index, 1 );
				}
			
			$mags[] = $mag[0]["id"];
			
			sql_update( "accounts", "showMagazines='".implode( ",", $mags )."'", "id='".$temp[0]["id"]."'" );
			}

		$emails = array();
		for( $i = 0; $i < count( $_POST["mailto"] ); $i++ ) {
			$temp = sql_aget( "accounts", "id='".$_POST["mailto"][$i]."'", "*" );
			$right_temp = sql_aget( 'user_groups', 'id="'.$temp[0]["group"].'"', '*' );
			
			if( !empty( $temp[0]["email"] ) && $right_temp[0]["magazine_upload"] == "1" ) {
				$emails[] = trim( $temp[0]["email"] );
				}
			}

		for( $i = 0; $i < count( $_POST["tempregmailto"] ); $i++ ) {
			$temp = sql_aget( "accounts", "id='".$_POST["tempregmailto"][$i]."'", "*" );
			$right_temp = sql_aget( 'user_groups', 'id="'.$temp[0]["group"].'"', '*' );
			
			if( !empty( $temp[0]["email"] ) && $right_temp[0]["magazine_upload"] == "1" ) {
				$emails[] = trim( $temp[0]["email"] );
				}
			}
		
		//Ideiglenes felhasználók kezelése
		if( $mag[0]["type"] == "Adhoc" ) {
			//Újjonnan felvittek hozzáadása
			for( $i = 0; $i < count( $_POST["tempusers"] ); $i++ ) {
				$check = sql_aget( "accounts", "email='".$_POST["tempusers"][$i]."' AND temppubid='".$pub[0]["id"]."'", "*" );
				if( empty( $check[0]["id"] ) ) {
					$names = array( "name", "pass", "type", "publisher", "email", "full_name", "group", "actual", "showMagazines", "usertype", "temppubid" );
					$values = array( "", "", "adhoc", $pub[0]["publisher_id"], $_POST["tempusers"][$i], $_POST["tempusers"][$i], $_POST["tempusersgroup"][$i], $pub[0]["code"]."_".$pub[0]["code"], $pub[0]["magazine_id"], "Temp", $pub[0]["id"] );
					$uid = sql_add( "accounts", $names, $values );

					$hash = md5( "adhoctempuser-".time()."-".$_POST["tempusers"][$i] );
					$names = array( "user_id", "hash", "magazine_id", "email", "time" );
					$values = array( $uid, $hash, $pub[0]["magazine_id"], $_POST["tempusers"][$i], time() );
					sql_add( "adhoc_hotlinks", $names, $values );	
					
					$link = "https://".URL."/index.php?hash=".$hash;
					$to = $_POST["tempusers"][$i]."|".$_POST["tempusers"][$i];
					$subject =  "".$mag[0]["name"]." - Colorcom Tracker hozzáférés";
					$body = "Kedves ".$_POST["tempusers"][$i].",<br>
					<br>
					a(z) ".$mag[0]["name"]." kiadvány létrehozásában való közreműködéshez kérjük lépj be a Colorcom Tracker rendszerbe a következő linkre kattintva: <a href='".$link."'>".$link."</a>.<br>
					<br>
					Üdvözlettel:<br>
					Colorcom Media";
					produkcioSendmail( $subject, $body, $to );
					$emails[] = trim( $_POST["tempusers"][$i] );
					}
				}
				
			//Eltávolítottak törlése
			$users = sql_aget( "accounts", "usertype='Temp' AND temppubid='".$pub[0]["id"]."'", "*" );
			$utemp = $users;
			for( $i = 0; $i < count( $users ); $i++ ) {
				if( in_array( $users[$i]["email"], $_POST["tempregusers"] ) ) {
					unset( $utemp );
					}
				}
			sort( $utemp );
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

			//PMD belenyúlás - magazin levél
			$magID = explode( ",", $user[0][21] );
			$magazines = array();
			for( $i = 0; $i < count( $magID ); $i++ ) {
				$mag = sql_aget( 'magazines', 'id="'.$magID[$i].'" LIMIT 1', '*' );
				for( $y = 0; $y < count( $mag ); $y++ ) {
					$magazines[] = $mag[$y];
					}
				}
			//error_log( print_r( $magazines, true ) );
			$xmlChanged = false;
			for( $i = 0; $i < count( $magazines ); $i++ ) {
				error_log( $i );
				$send = array();
				
				$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $x = 0; $x < count( $temp->Item ); $x++ ) {
						if( $temp->Item[$x]->Code == $magazines[$i]["code"] )
							break;
						}
					}
				
				$code = (string) $xml->Item[$x]->Code;	
				$pmdmails = (string) $xml->Item[$x]->Mails;
				if( !empty( $pmdmails ) ) {
					$pmdmails = explode( ";", $pmdmails );
					}
				else {
					$pmdmails = array();
					}
				
				//error_log( print_r( $_POST["userMails"], true ) );
				//error_log( $code );
				if( in_array( $code, $_POST["userMails"] ?? array() ) ) {
					if( !in_array( $user[0][5] , $pmdmails ) ) {
						error_log( "bentvagyok") ;
						$pmdmails[] = trim( $user[0][5] );
						$pmdmails = implode( ";", $pmdmails );
						
						error_log( $user[0][5] );
						error_log( "pmdmails: ".$pmdmails );
						
						$send['Mails'] = $pmdmails;
						$send['MailComm'] = "Yes";
						$send['old_code'] = $code;
						$send['deny'] = 'ki_set';
						changeXmlDatabase_ext( 'modify', $send, '../xml/'.PMD.'.xml' );
						$xmlChanged = true;
						}
					}
				else {
					if( in_array( $user[0][5] , $pmdmails ) ) {
						$key = array_search( $user[0][5] , $pmdmails );
						
						unset( $pmdmails[$key] );
						
						if( count( $pmdmails ) > 0 ) {
							$pmdmails = implode( ";", $pmdmails );
							
							$send['Mails'] = $pmdmails;
							$send['MailComm'] = "Yes";
							$send['old_code'] = $code;
							$send['deny'] = 'ki_set';
							}
							
						else {
							$send['Mails'] = "";
							$send['MailComm'] = "No";
							$send['old_code'] = $code;
							$send['deny'] = 'ki_set';
							}
						changeXmlDatabase_ext( 'modify', $send, '../xml/'.PMD.'.xml' );
						$xmlChanged = true;
						}
					}
				}

			if( $xmlChanged ) {
				XMLUpload2( PMD.'.xml' );
				}
			}
		
		$result = array( $error, $langmod );
		}
	
	print json_encode( $result );
	
?>	