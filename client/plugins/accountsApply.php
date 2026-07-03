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
			}
		
		$result = array( $error );
		}
		
	if( $_GET["sub"] == "resetpw" ) {
		$newpw = randomPWB()."".randomPWS()."".randomPWN()."".randomPWS()."".randomPWB()."".randomPWN();
		
		sql_update( "accounts", "pass='".md5( $newpw )."'", "id='".$_GET["id"]."'" );
		
		$u = sql_aget( "accounts", "id='".$_GET["id"]."'", "*" );
		
		$subject = "Tracker belépési adatok";
		$to = $u[0]["email"]."|".$u[0]["email"];
		$body = "
A Tracker rendszer elérése:<br>
<br>
URL: <a href='".PROTOCOL."".URL."'>".PROTOCOL."".URL."</a><br>
Login név: ".$u[0]["name"]."<br>
Jelszó: ".$newpw."<br>
<br>
Üdvözlettel:<br>
<br>
Colorcom Media";
		sendMail( $subject, $body, $to, "" );		
		}
		
	if( $_GET["sub"] == "addMember" ) {
		$error = array();
		if( $_POST[ "u_name" ] == "" ) $error[] = "u_name";
		if( $_POST[ "u_password" ] == "" ) $error[] = "u_password";
		if( $_POST[ "u_password2" ] == "" ) $error[] = "u_password2";
		if( $_POST[ "u_password" ] != $_POST[ "u_password2" ] ) {
			$error[] = "u_password";
			$error[] = "u_password2";
			}
		if( $_POST[ "u_fullname" ] == "" ) $error[] = "u_fullname";
		if( !filter_var( $_POST[ "u_mail" ], FILTER_VALIDATE_EMAIL ) ) $error[] = "u_mail";
		
		$check = sql_aget( "accounts", "name='".$_POST[ "u_name" ]."'", "*" );
		if( !empty( $check[0]["id"] ) ) {
			$error[] = "u_name";
			}
		
		$check = sql_aget( "accounts", "email='".$_POST[ "u_mail" ]."'", "*" );
		if( !empty( $check[0]["id"] ) ) {
			$error[] = "u_mail";
			}
		
		if( count( $error ) == 0 ) {
			$c = "name='".$_POST[ "u_name" ]."' AND publisher='".$_POST[ "u_publisher" ]."'";
			$check = sql_get( "accounts", "name='".$_POST[ "u_name" ]."' AND publisher='".$_POST[ "u_publisher" ]."'", "id" );
			
			if( $check[0][0] == '' ) {
				$showMagazines = implode( ",", $_POST["aMagazines"] );
				if( $_POST["aMagazines"] == "" ) $showMagazines = "";
				else $showMagazines = implode( ",", $_POST["aMagazines"] );
				$names = array( 'name', 'pass', 'publisher', 'group', 'email', 'full_name', 'showMagazines' );
				$values = array( $_POST['u_name'], md5($_POST['u_password']), $_POST['u_publisher'], $_POST['u_type'], $_POST[ "u_mail" ], $_POST[ "u_fullname" ], $showMagazines );
				$id = sql_add( 'accounts', $names, $values );
				
				$names = array( 'user' );
				$values = array( $id );
				sql_add( 'userLogSettings', $names, $values );
				
				
				//MAIL KÜLDÉS
				$subject = "Tracker belépési adatok";
				$to = $_POST[ "u_fullname" ]."|".$_POST[ "u_mail" ];
				$body = "
Üdvözlünk a Colorcom Tracker felhasználói között!<br>
<br>
A Tracker rendszer elérése:<br>
<br>
URL: <a href='".PROTOCOL."".URL."'>".PROTOCOL."".URL."</a><br>
Login név: ".$_POST['u_name']."<br>
Jelszó: ".$_POST['u_password']."<br>
<br>
Üdvözlettel:<br>
<br>
Colorcom Media<br>
				";
				sendMail( $subject, $body, $to, "" );
				}
			else {
				$error[] = "u_name";
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