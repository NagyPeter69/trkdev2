<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');
include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );
include_once( '../../engine/xml_handler.php' );

include_once('../lang/en.php');

$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}

if( $_GET["sub"] == "resend" ) {
	$error = array();
	$asset = sql_aget( "assets", "id='".$_POST["assetid"]."'", "*" );
	$pub = sql_aget( "publications", "id='".$asset[0]['pub_id']."'", "*" );
	$mag = sql_aget( "magazines", "id='".$pub[0]['magazine_id']."'", "*" );
	$result = "";

	$mails = trim( $_POST["Mails"] );
	if( !empty( $mails ) ) {
		$mails = str_replace( ";", "\r\n", $mails );
		$mails = str_replace( ",", "\r\n", $mails );
		$mails = str_replace( " ", "\r\n", $mails );
		$mails = explode( "\r\n", $mails );
		
		if( count( $mails ) > 0 ) {
			for( $i = 0; $i < count( $mails ); $i++ ) {
				$hash = md5( "adhocuserdownload-".time()."-".$mails[$i] );
				$user = sql_aget( "accounts", "email='".$mails[$i]."' AND showMagazines like '%".$mag[0]["id"]."%'", "*" );
				
				$pubid = "";
				
				if( empty( $user[0]["id"] ) ) {
					$user[0]["id"] = 0;
					$pubid = $pub[0]["id"];
					}
				
				$names = array( "user_id", "hash", "magazine_id", "email", "time", "redirecto", "pubid" );
				$values = array( $user[0]["id"], $hash, $pub[0]["magazine_id"], $mails[$i], time(), "page=assets", $pubid );
				sql_add( "adhoc_hotlinks", $names, $values );
					
				$to = $mails[$i]."|".$mails[$i];
				$link = "https://".URL."/index.php?hash=".$hash;
				$subject = "".$mag[0]["name"]." - Colorcom Tracker feltöltés";
				$body = "Kedves ".$mails[$i].",<br>
				<br>
				a(z) ".$mag[0]["name"]." kiadványhoz új fájlok lettek feltöltve amit a következő linkre kattintva tekinthet meg: <a href='".$link."'>".$link."</a>.<br>
				<br>
				Üdvözlettel:<br>
				Colorcom Media";
					
				produkcioSendmail( $subject, $body, $to );
				error_log( "visszatértem" );
				}
			}
		
		else {
			$error[] = "Mails";
			}
		}
	else {
		$error[] = "Mails";
		}		
		
	$result = array( $error );
	}


print json_encode( $result );
?>