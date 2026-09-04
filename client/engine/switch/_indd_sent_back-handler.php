<?PHP

$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];
$remark = $_POST["remark"];

$mag = sql_aget( "magazines", "code='".$_POST["jobCode"]."'", "*" );
$pub = sql_aget( "publications", "magazine_id='".$mag[0]["id"]."' AND code='".$_POST["issue"]."'", "*" );

$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
$xpath = $xml->xpath('/Publications');
		
foreach($xpath as $temp) {
	for( $i = 0; $i < count( $temp->Item ); $i++ ) {
		if( $temp->Item[$i]->Code == $jcode )
			break;
		}
	}

$mails = (string) $xml->Item[$i]->Mails;
$mails = explode( ";", $mails );

for( $i = 0; $i < count( $mails ); $i++ ) {
	$hash = md5( "adhocuserflatplan-".time()."-".$mails[$i] );
	$user = sql_aget( "accounts", "email='".$mails[$i]."' AND publisher!='6' AND showMagazines like '%".$mag[0]["id"]."%'", "*" );
	
	if( !empty( $user[0]["id"] ) ) {
		$names = array( "user_id", "hash", "magazine_id", "email", "time", "redirect" );
		$values = array( $user[0]["id"], $hash, $pub[0]["magazine_id"], $mails[$i], time(), "page=flatplan" );
		sql_add( "adhoc_hotlinks", $names, $values );
		}
	}
	
?>