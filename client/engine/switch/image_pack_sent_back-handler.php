<?PHP
$mag = sql_aget( "magazines", "code='".$_POST["jobCode"]."'", "*" );
$pub = sql_aget( "publications", "magazine_id='".$mag[0]["id"]."' AND code='".$_POST["issue"]."'", "*" );
$pack = sql_aget( "assets", "pub_id='".$pub[0]["id"]."' AND name='".$_POST["description"].".indd'", "*" );
	
$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
$xpath = $xml->xpath('/Publications');
		
foreach($xpath as $temp) {
	for( $i = 0; $i < count( $temp->Item ); $i++ ) {
		if( $temp->Item[$i]->Code == $_POST['jobCode'] )
			break;
		}
	}

$mails = gatedMailRecipients( $mag[0]["id"], $mag[0]["type"], (string) $xml->Item[$i]->Mails );

for( $i = 0; $i < count( $mails ); $i++ ) {
	$hash = md5( "adhocuserdownload-".time()."-".$mails[$i] );
	$user = sql_aget( "accounts", "email='".$mails[$i]."' AND showMagazines like '%".$mag[0]["id"]."%' order by ID ASC LIMIT 1", "*" );
	
	error_log( "Sendmail, user: ".$user[0]["id"] );
	if( !empty( $user[0]["id"] ) ) {
		$names = array( "user_id", "hash", "magazine_id", "email", "time", "redirecto" );
		//$values = array( $user[0]["id"], $hash, $pub[0]["magazine_id"], $mails[$i], time(), "page=assets&packid=".$pack[0]["id"]."" );
		$values = array( $user[0]["id"], $hash, $pub[0]["magazine_id"], $mails[$i], time(), "page=assets" );
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
		error_log( "Mail sent: ".$mails[$i] );
		}
	}

?>