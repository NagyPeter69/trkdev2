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

$mails = (string) $xml->Item[$i]->Mails;
$cimzettek = $mails;
$mails = explode( ";", $mails );

for( $i = 0; $i < count( $mails ); $i++ ) {
	$hash = md5( "adhocuserdownload-".time()."-".$mails[$i] );
	$user = sql_aget( "accounts", "email='".$mails[$i]."' AND showMagazines like '%".$mag[0]["id"]."%'", "*" );
	
	error_log( "Sendmail, user: ".$user[0]["id"] );
	if( !empty( $user[0]["id"] ) ) {
		$names = array( "user_id", "hash", "magazine_id", "email", "time", "redirecto" );
		$values = array( $user[0]["id"], $hash, $pub[0]["magazine_id"], $mails[$i], time(), "page=assets" );
		sql_add( "adhoc_hotlinks", $names, $values );		
		
		$to = $mails[$i]."|".$mails[$i];
		$link = "https://".URL."/index.php?hash=".$hash;
		$subject = "".$mag[0]["name"]." - Colorcom Tracker feltöltés";
		$body = "Dear User,<br>
		<br>
		new files has been uploaded to the job ".$mag[0]["name"].". Please see them by clicking the link below:<br>
		<br>
		<a href='".$link."'>".$link."</a><br>
		<br>
		Kind regards,<br>
		Colorcom Media";
		
		/*
		$body = "Kedves ".$mails[$i].",<br>
		<br>
		a(z) ".$mag[0]["name"]." kiadványhoz új fájlok lettek feltöltve amit a következő linkre kattintva tekinthet meg: <a href='".$link."'>".$link."</a><br>
		<br>
		Üdvözlettel:<br>
		Colorcom Media";
		*/
		
		produkcioSendmail( $subject, $body, $to );
		/*
		$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
		produkcioSendmail( $subject, $body, $to );
		*/
		error_log( "Mail sent: ".$mails[$i] );
		}
	}
/*	
$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
$body .= "<br>Cimzettek: ".$cimzettek."";
produkcioSendmail( $subject, $body, $to );
error_log( "Mail sent: ".$mails[$i] );	
*/
?>