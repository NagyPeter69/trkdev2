<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');
	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( "../engine/switchAPI.php" );
	
	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}

	if( !empty( $user[0][17] ) ) {
		include_once('../lang/'.$user[0][17].'.php');
		}
	else {
		include_once('../lang/en.php');
		}

	if( $_GET["sub"] == "delAdhoc" ) {
		$error = array();
		
		if( $_POST['account_remove'] != "" ) {
			sql_delete( "ad_hoc_users", "id='".$_POST['account_remove']."'" );
			}
		
		$result = array( $error );
		}

	if( $_GET["sub"] == "modAdhoc" ) {
		$error = array();
		if( $_POST[ "u_fullname" ] == "" ) $error[] = "u_fullname";
		if( !filter_var( $_POST[ "u_mail" ], FILTER_VALIDATE_EMAIL ) ) $error[] = "u_mail";
		
		if( count( $error ) == 0 ) {
			$names = array( 'name', 'email' );
			$values = array(  $_POST['u_fullname'], $_POST['u_mail'] );
			
			for( $i = 0; $i < count( $names ); $i++ ) {
				$command .= '`'.$names[$i].'`="'.$values[$i].'"';
				if( $i < count( $names )-1 ) {
					$command .= ', ';
					}
				}
			sql_update( 'ad_hoc_users', $command, 'id="'.$_POST['id'].'"' );			
			}
			
		$result = array( $error );	
		}

	if( $_GET["sub"] == "addAdhoc" ) {
		$error = array();

		if( $_POST[ "u_fullname" ] == "" ) $error[] = "u_fullname";
		if( !filter_var( $_POST[ "u_mail" ], FILTER_VALIDATE_EMAIL ) ) $error[] = "u_mail";
		
		if( count( $error ) == 0 && $_SESSION['intra_user'] != "" ) {
			$check = sql_get( "ad_hoc_users", "email='".$_POST[ "u_mail" ]."'", "id" );
			// $_POST['u_publisher']
			if( $check[0][0] == '' ) {
				$names = array( 'name', 'email', 'client', 'creator' );
				$values = array( $_POST['u_fullname'], $_POST[ "u_mail" ], $_POST[ "u_publisher" ], $_SESSION['intra_user'] );
				$id = sql_add( 'ad_hoc_users', $names, $values );
				
				$names = array( 'user' );
				$values = array( $id );
				sql_add( 'userLogSettings', $names, $values );
				}
			else {
				$error[] = "u_mail";
				}
			}
		
		$result = array( $error );
		}
	
	if( $_GET["sub"] == "modifyMember" ) {
		$error = array();
		if( $_POST[ "u_fullname" ] == "" ) $error[] = "u_fullname";
		//if( !filter_var( $_POST[ "u_mail" ], FILTER_VALIDATE_EMAIL ) ) $error[] = "u_mail";
		
		if( count( $error ) == 0 ) {
			$showMagazines = implode( ",", $_POST["aMagazines"] );
			if( $_POST["aMagazines"] == "" ) $showMagazines = "";
			else $showMagazines = implode( ",", $_POST["aMagazines"] );
			
			$names = array( 'publisher', 'group', 'email', 'full_name', 'showMagazines' );
			$values = array(  $_POST['u_publisher'], $_POST['u_type'], $_POST[ "u_mail" ], $_POST[ "u_fullname" ], $showMagazines );
			
			for( $i = 0; $i < count( $names ); $i++ ) {
				$command .= '`'.$names[$i].'`="'.$values[$i].'"';
				if( $i < count( $names )-1 ) {
					$command .= ', ';
					}
				}
			sql_update( 'accounts', $command, 'id="'.$_POST['id'].'"' );			
			}
			
		$result = array( $error, $showMagazines );	
		}
	
	if( $_GET["sub"] == "removeMember" ) {
		$error = array();
		
		if( $_POST['account_remove'] != "" ) {
			$acc = sql_aget( "accounts", "id='".$_POST['account_remove']."'", "*" );
			
			sql_delete( "accounts", "id='".$_POST['account_remove']."'" );
			sql_delete( "userLogSettings", "user='".$_POST['account_remove']."'" );

			removeUserMailPMD( $acc[0]["email"] );

			$logTarget = $acc[0]['name'].' ('.$acc[0]['email'].')';

			$adhoc = sql_aget( "ad_hoc_users", "email='".$acc[0]['email']."'", "*" );
			if( !empty( $adhoc[0]["id"] ) ) {
				$activeAdhocJobs = sql_aget(
					"publications p JOIN magazines m ON m.id = p.magazine_id",
					"p.owner = '".$adhoc[0]["client"]."' AND m.type = 'Adhoc' AND p.status IN ('created','active','current','approved','archiving')",
					"p.id"
					);

				if( count( $activeAdhocJobs ) == 0 ) {
					sql_delete( "ad_hoc_users", "id='".$adhoc[0]["id"]."'" );
					$logTarget .= ' [+ removed ad_hoc_users #'.$adhoc[0]["id"].']';
					}
				else {
					$logTarget .= ' [ad_hoc_users #'.$adhoc[0]["id"].' preserved: '.count( $activeAdhocJobs ).' active Adhoc job(s)]';
					}
				}

			sql_add( 'action_log', array('user','action','publisher','magazine','issue','target','date'),
				array($_SESSION['intra_user'], 'delAccount', $acc[0]['publisher'], '', '', $logTarget, time()) );
			}
		
		$result = array( $error );
		}
		
	if( $_GET["sub"] == "resetpw" ) {
		// Locks the account out of its old password immediately, same as before -
		// but instead of mailing a plaintext replacement, mail a link that lets the
		// user set their own new one (see client/set_password.php).
		sql_update( "accounts", "pass='".password_hash( bin2hex( random_bytes( 32 ) ), PASSWORD_DEFAULT )."'", "id='".$_GET["id"]."'" );

		$u = sql_aget( "accounts", "id='".$_GET["id"]."'", "*" );

		$token = bin2hex( random_bytes( 32 ) );
		sql_update( "accounts", "pwset_token='".hash( 'sha256', $token )."', pwset_expires='".( time() + 172800 )."'", "id='".$_GET["id"]."'" );
		$link = PROTOCOL.URL."/client/index.php?page=set_password&token=".$token;

		$subject = "Tracker belépési adatok";
		$to = $u[0]["email"]."|".$u[0]["email"];
		$body = "
Az alábbi linkre kattintva tudsz új jelszót beállítani a Tracker rendszerhez:<br>
<br>
<a href='".$link."'>".$link."</a><br>
<br>
Login név: ".$u[0]["name"]."<br>
<br>
A link 48 óráig érvényes.<br>
<br>
Üdvözlettel:<br>
<br>
Colorcom Media";
		sendMail( $subject, $body, $to, "" );
		}
		
	if( $_GET["sub"] == "addMember" ) {
		$error = array();
		if( $_POST[ "u_name" ] == "" ) $error[] = "u_name";
		if( $_POST[ "u_fullname" ] == "" ) $error[] = "u_fullname";
		if( !filter_var( $_POST[ "u_mail" ], FILTER_VALIDATE_EMAIL ) ) $error[] = "u_mail";
		
		$check = sql_aget( "accounts", "name='".$_POST[ "u_name" ]."'", "*" );
		if( !empty( $check[0]["id"] ) ) {
			$pub = sql_get( "publishers", "id='".$check[0]["publisher"]."'", "name" );
			echo json_encode( array( array( false, sprintf( $lang["settings"]["error8"], $check[0]["id"], $pub[0][0] ?? $check[0]["publisher"] ) ) ) );
			exit;
			}

		$check = sql_aget( "accounts", "email='".$_POST[ "u_mail" ]."'", "*" );
		if( !empty( $check[0]["id"] ) ) {
			$pub = sql_get( "publishers", "id='".$check[0]["publisher"]."'", "name" );
			echo json_encode( array( array( false, sprintf( $lang["settings"]["error9"], $check[0]["id"], $pub[0][0] ?? $check[0]["publisher"] ) ) ) );
			exit;
			}

		if( count( $error ) == 0 ) {
			$c = "name='".$_POST[ "u_name" ]."' AND publisher='".$_POST[ "u_publisher" ]."'";
			$check = sql_get( "accounts", "name='".$_POST[ "u_name" ]."' AND publisher='".$_POST[ "u_publisher" ]."'", "id" );

			if( $check[0][0] == '' ) {
				$showMagazines = empty( $_POST["aMagazines"] ) ? "" : implode( ",", $_POST["aMagazines"] );

				// New accounts start with no usable password at all - the welcome
				// mail carries a secure set-password link (client/set_password.php)
				// instead of an admin-chosen or system-generated plaintext one.
				$token = bin2hex( random_bytes( 32 ) );
				$names = array( 'name', 'pass', 'publisher', 'group', 'email', 'full_name', 'showMagazines', 'pwset_token', 'pwset_expires' );
				$values = array( $_POST['u_name'], password_hash( bin2hex( random_bytes( 32 ) ), PASSWORD_DEFAULT ), $_POST['u_publisher'], $_POST['u_type'], $_POST[ "u_mail" ], $_POST[ "u_fullname" ], $showMagazines, hash( 'sha256', $token ), time() + 172800 );
				$id = sql_add( 'accounts', $names, $values );

				$names = array( 'user' );
				$values = array( $id );
				sql_add( 'userLogSettings', $names, $values );

				sql_add( 'action_log', array('user','action','publisher','magazine','issue','target','date'),
					array($_SESSION['intra_user'], 'addAccount', $_POST['u_publisher'], '', '', $id, time()) );

				$link = PROTOCOL.URL."/client/index.php?page=set_password&token=".$token;

				//MAIL KÜLDÉS
				$subject = "Tracker belépési adatok";
				$to = $_POST[ "u_fullname" ]."|".$_POST[ "u_mail" ];
				$body = "
Üdvözlünk a Colorcom Tracker felhasználói között!<br>
<br>
Az alábbi linkre kattintva tudsz jelszót beállítani a fiókodhoz:<br>
<br>
<a href='".$link."'>".$link."</a><br>
<br>
Login név: ".$_POST['u_name']."<br>
<br>
A link 48 óráig érvényes.<br>
<br>
Üdvözlettel:<br>
<br>
Colorcom Media<br>
				";
				sendMail( $subject, $body, $to, "" );
				}
			else {
				$pub = sql_get( "publishers", "id='".$_POST["u_publisher"]."'", "name" );
				echo json_encode( array( array( false, sprintf( $lang["settings"]["error8"], $check[0][0], $pub[0][0] ?? $_POST["u_publisher"] ) ) ) );
				exit;
				}
			}

		$result = array( $error );
		}

	if( $_GET["sub"] == "modifyGroup" ) {
		$names = $error = $values = array();		
		$checker = array();
		$id = $_POST["id"];
		foreach( $_POST as $key => $value ) {
			$checker[] = $key;
			}
		unset( $_POST["id"] );
		unset( $_POST["name"] );
		unset( $_POST["publisher"] );
		
		$ignore = array( "id", "name", "publisher" );
		$r = $row_names = sql_get( 'INFORMATION_SCHEMA.COLUMNS', 'TABLE_NAME = "user_groups"', 'COLUMN_NAME' );
		$c = array();
		for( $i = 2; $i < count( $row_names ); $i++ ) {
			if( !in_array( $row_names[$i][0], $ignore ) ) {
				$names[] = $row_names[$i][0];
				if( $row_names[$i][0] == "publisher" ) {
					$values[] = $_POST[ $row_names[$i][0] ];
					}
				else {
					if( in_array( $row_names[$i][0], $checker ) ) {
						$values[] = "1";
						}
					else {
						$values[] = "0";
						}
					}
				}
			}
		
		for( $i = 0; $i < count( $names ); $i++ ) {
			$command .= '`'.$names[$i].'`="'.$values[$i].'"';
			if( $i < count( $names )-1 ) {
				$command .= ', ';
				}
			}

		sql_update( 'user_groups', $command, 'id="'.$id.'"' );
		
		$result = array( $error );
		}
	
	if( $_GET["sub"] == "removeGroup" ) {
		$error = array();
		
		if( $_POST['account_remove'] != "" ) {
			sql_delete( "user_groups", "id='".$_POST['account_remove']."'" );
			}
		
		$result = array( $error );
		}
	
	if( $_GET["sub"] == "addGroup" ) {
		$error = array();
		
		if( $_POST[ "name" ] == "" ) {
			$error[] = "name";
			}
		else {
			$names = array();
			$values = array();
			foreach( $_POST as $key => $value ) {
				$names[] = $key;
				$values[] = $value;
				}
			$group = sql_get( 'user_groups', 'name="'.$_POST['name'].'"', 'id' );
			if( $group[0][0] == '' ) {
				sql_add( 'user_groups', $names, $values );
				}
			else {
				$error[] = "name";
				}
			}
		
		$result = array( $error );
		}

	if( $_GET["sub"] == "addPlannerGroup" ) {
		$error = array();
		error_log( "itten" );
		if( $_POST[ "name" ] == "" ) {
			$error[] = "name";
			}
		else {
			$names = array();
			$values = array();
			foreach( $_POST as $key => $value ) {
				$names[] = $key;
				$values[] = $value;
				}	
				
			$group = sql_get( 'calendar_groups', 'name="'.$_POST['name'].'"', 'id' );
			if( $group[0][0] == '' ) {
				sql_add( 'calendar_groups', $names, $values );
				}
			else {
				$error[] = "name";
				}
			}
		
		$result = array( $error );
		}
	
	if( $_GET["sub"] == "removePlannerGroup" ) {
		$error = array();
		
		if( $_POST['account_remove'] != "" ) {
			sql_delete( "calendar_groups", "id='".$_POST['account_remove']."'" );
			}
		
		$result = array( $error );
		}
		
	print json_encode( $result );
	
?>