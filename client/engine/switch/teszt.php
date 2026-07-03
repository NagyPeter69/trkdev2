<?PHP
include_once('../../../engine/connect.php');
include_once('../../../engine/engine.php');
include_once('../../../engine/xml_handler.php');

$_POST["jobCode"] = "BCQ94";
$_POST["issue"] = "BCQ94";
$_POST["description"] = "tokio_etlap_HUN_2021-04-15.indd";
$_POST["fileName"] = "teszt";

$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];
$remark = $_POST["remark"];
$description = $_POST["description"];

$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
$type = $p_id[0][10];
$p_code = $p_id[0][3];

$xml = simplexml_load_file( TRKPATH."/xml/".PMD.".xml" );
$xpath = $xml->xpath('/Publications');
foreach($xpath as $temp) {
	for( $i = 0; $i < count( $temp->Item ); $i++ ) {
		if( $temp->Item[$i]->Code == $_POST['jobCode'] )
			break;
		}
	}
	
$file = $_POST["fileName"].".xml";

	$report = simplexml_load_file( $file );
	$xpath = $report->xpath('/uploadReport');
	
	$mails = (string) $xml->Item[$i]->Mails;
	$mails = explode( ";", $mails );
	$url = URL;
	$pub = sql_aget( "publisher", "id='".$p_id[0][1]."'", "*" );
	if( $pub[0]["name"] == "TestCo" ) {
		$url = "trkdev.colorcom.hu";
		}
		
	$link = "https://".$url."/switchReports/".( $type == "Adhoc" ? $_POST["jobCode"] : $_POST["jobCode"]."/".$_POST["issue"] )."/".$report->reportID.".html";
	error_log( $link );
	for( $i = 0; $i < count( $mails ); $i++ ) {
		//$to = $mails[$i]."|".$mails[$i];
		$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
		$subject = str_replace(".indd", "", $report->docName)." ::: ".$report->subject;
		$body = "";
		
		$body .= $report->intro."<br><br>";
		$body .= $report->text."<br><br>";
		$body .= "<a href='".$link."'>".$link."</a><br><br>";
		$body .= str_replace(",", ",<br><br>", $report->outro);
		
		error_log( $body );
		produkcioSendmail( $subject, $body, $to );
		}

?>