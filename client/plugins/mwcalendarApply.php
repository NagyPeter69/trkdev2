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

if( $_GET["sub"] == "issueDefine" ) {
	$error = array();
	
	parse_str($_POST["data"], $_POST);

	$newxml = simplexml_load_file( '../xml/'.PMD.'.xml' );
	$xpath = $newxml->xpath('/Publications');
	foreach($xpath as $temp) {
		for( $x = 0; $x < count( $temp->Item ); $x++ ) {
			if( $temp->Item[$x]->Code == $_POST["i_code"] ) {
				break;
				}
			}
		}
	$_POST["dl"] .= "T16:00";
	$_POST["enhance"] = (string) $newxml->Item[$x]->Enhance;
	$_POST["part1_name"] = "Cover";
	$_POST["part1_place"] = "1-2, ".($_POST["page_nr"]-1)."-".$_POST["page_nr"];
	$_POST["part1_color"] = (string) $newxml->Item[$x]->ColorManagement->Cover;
	$_POST["part2_name"] = "Inside";
	$_POST["part2_place"] = "3-".($_POST["page_nr"]-2);
	$_POST["part2_color"] = (string) $newxml->Item[$x]->ColorManagement->Content;
	
	/*ob_flush();
	ob_start();
	var_dump($_POST);
	file_put_contents( "issueDefine.txt", ob_get_flush());	*/
	
	
	$_POST['dl'] = str_replace( " ", "T", $_POST['dl'] );
	$p_id = sql_get( "magazines", "id='".$_POST['m_id']."'", "publisher_id" );
	$internal = $_POST['i_code'].'|'.$_POST['i_base'].'|'.$_POST['i_variable'].'|'.$_POST['i_padding'].'|'.$_POST['i_delimiter'].'|'.$_POST['i_aname'].'|'.$_POST['i_adelimiter'];
	$upload = $_POST['u_code'].'|'.$_POST['u_base'].'|'.$_POST['u_variable'].'|'.$_POST['u_padding'].'|'.$_POST['u_delimiter'].'|'.$_POST['u_var_del'].'|'.$_POST['u_aname'].'|'.$_POST['u_adelimiter'];
	$output = $_POST['o_code'].'|'.$_POST['o_base'].'|'.$_POST['o_variable'].'|'.$_POST['o_padding'].'|'.$_POST['o_delimiter'].'|'.$_POST['o_var_del'].'|'.$_POST['o_aname'].'|'.$_POST['o_adelimiter'];
	$p_id = $p_id[0][0];

	$names = array( 'publisher_id', 'magazine_id', 'internal', 'upload', 'output', 'pages', 'uploadable', 'precounter', 'code', 'deadline', 'specificName', 'created', 'enhance' );
	$values = array( $p_id, $_POST['m_id'], $internal, $upload, $output, $_POST['page_nr'], "false", 0, $_POST['job_code'], $_POST['dl'], $_POST['customname'], time(), $_POST['enhance'] );
	$id = sql_add( 'publications', $names, $values );

	$names = array( 'pub_id', 'name', 'place', 'color' );
	$counter = explode( ',', $_POST['counter'] );
	for( $i = 0; $i < count( $counter ); $i++ ) {
		$values = array( $id, $_POST['part'.$counter[$i].'_name'], $_POST['part'.$counter[$i].'_place'], $_POST['part'.$counter[$i].'_color'] );
		sql_add( 'parts', $names, $values );
		}
		
		$array["client"] = $publisher[0][0];
		$array["jobCode"] = $magazine[0][0];
		$array["issue"] = $job[0][10];
		$array["event"] = 'issue_created';		

	$job = sql_get( "publications", 'id="'.$id.'"', '*'  );
	$publisher = sql_get( 'publishers', 'id="'.$job[0][1].'"', 'name' );
	$magazine = sql_get( 'magazines', 'id="'.$job[0][2].'"', 'code' );			

	$array = array(
		"event" => "issue_created",
		"client" => $publisher[0][0],
		"jobCode" => $magazine[0][0],
		"issue" => $job[0][10],
		);

	$mag = sql_get( 'magazines', 'id="'.$_POST['m_id'].'"', 'code, name' );
	toSwitch( 'new_publication' , 'publications|'.$id, 'C_database/'.$mag[0][0].'_'.$_POST['job_code'], 'issueData' );

	$pubs = sql_get( 'publications', 'magazine_id="'.$_POST['m_id'].'" ORDER BY `id` ASC', '*' );

	$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
	$values = array( $_SESSION['intra_user'], 'newIssue', $p_id, $_POST['m_id'], $_POST['job_code'], '', time() );
	sql_add( 'system_log', $names, $values );

	$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
	$xpath = $xml->xpath('/Publications');
	foreach($xpath as $temp) {
		for( $x = 0; $x < count( $temp->Item ); $x++ ) {
			if( $temp->Item[$x]->Code == $mag[0][0] ) {
				break;
				}
			}
		}
	
	$error = SwitchSend( $array );
		
	$mails = explode( ";", $xml->Item[$x]->Mails );
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
			produkcioSendmail( $subject, $body, $to );
			}			
		}
	
	$result = array( $error );	
	}
	
if( $_GET["sub"] == "define" ) {
	$error = array();
	
	if( empty( $_POST["name"] ) ) $error[] = "name";
	if( empty( $_POST["start"] ) ) $error[] = "start";
	if( empty( $_POST["end"] ) ) $error[] = "end";
	
	if( empty( $error ) ) {
		$mag = sql_aget( "magazines", "id='".$_POST["mid"]."'", "*" );
		
		$names = array( "publisher_id", "magazine_id", "name", "start", "end" );
		$values = array( $mag[0]["publisher_id"], $_POST["mid"], $_POST["name"], strtotime( $_POST["start"] ), strtotime($_POST["end"] ) );
		sql_add( "calendar_events", $names, $values );
		}
	
	$result = array( $error );		
	}

if( $_GET["sub"] == "tasklist" ) {
	if( !empty( $_POST["order_paper"] ) && !empty( $_POST["order_paper_value"] ) ) {
		$order_paper = $_POST["order_paper_value"];
		}
	else {
		$order_paper = "-1";
		}
	sql_update( "calendar_settings", "value='".$order_paper."'", "name='order_paper' and publisher_id='".$_POST["pid"]."'" );
		
	if( !empty( $_POST["order_printing"] ) && !empty( $_POST["order_printing_value"] ) ) {
		$order_printing = $_POST["order_printing_value"];
		}
	else {
		$order_printing = "-1";
		}
	sql_update( "calendar_settings", "value='".$order_printing."'", "name='order_printing' and publisher_id='".$_POST["pid"]."'" );
		
	if( !empty( $_POST["define_issue"] ) && !empty( $_POST["define_issue_value"] ) ) {
		$define_issue = $_POST["define_issue_value"];
		}
	else {
		$define_issue = "-1";
		}
	sql_update( "calendar_settings", "value='".$define_issue."'", "name='define_issue' and publisher_id='".$_POST["pid"]."'" );

	if( !empty( $_POST["product_remind"] ) && !empty( $_POST["product_remind_value"] ) ) {
		$product_remind = $_POST["product_remind_value"];
		}
	else {
		$product_remind = "-1";
		}
	sql_update( "calendar_settings", "value='".$product_remind."'", "name='product_remind' and publisher_id='".$_POST["pid"]."'" );	
	
	$error = array();
	$result = array( $error );
	}

if( $_GET["sub"] == "modify" ) {
	$error = array();
	$temp = sql_aget( "calendar_post", "id='".$_POST['pid']."'", "*" );
	
	if( $temp[0]["magazine_id"] == 0 ) {
		sql_update( "calendar_post", "salesDay='".$_POST['salesday']."', printDay='".$_POST['printorder']."', numofpages='".$_POST['numofpages']."', specificName='".$_POST['customname']."'", "id='".$_POST['pid']."'" );
		}
	else {
		sql_update( "calendar_post", "salesDay='".$_POST['salesday']."', printDay='".$_POST['printorder']."', code='".$_POST['job_code']."', numofpages='".$_POST['numofpages']."', specificName='".$_POST['customname']."'", "id='".$_POST['pid']."'" );
		}
		
	$result = array( $error );
	
	$c = sql_aget( "calendar_post", "id='".$_POST['pid']."'", "*" );
	$check = issueChecker( $c[0]["magCode"], $c[0]["code"], "pubs" );

	if( !empty( $check[0]["id"] ) ) {
		$dl = $_POST['printorder']."T16:00";
		sql_update( "publications", "deadline='".$dl."', pages='".$_POST['numofpages']."'", "id='".$check[0]["id"]."'" );
		
		if( ( $temp[0]["printDay"] != $_POST['printorder'] ) or ( $temp[0]["numofpages"] != $_POST['numofpages'] ) ) {		
			$subject = "Változás a következő kiadványban: ".$c[0]["magCode"]."_".$c[0]["code"]."";
			$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
			$body = "
Változás a következő kiadványban: ".$c[0]["magCode"]."_".$c[0]["code"]." <br>
<br>";
if( $temp[0]["printDay"] != $_POST['printorder'] ) {
	$body .= "Módosított megjelenési dátum: ".$_POST['printorder']." (előtte: ".$temp[0]["printDay"].")<br>";
	}

if( $temp[0]["numofpages"] != $_POST['numofpages'] ) {
	$body .= "Kiadvány új oldalszáma: ".$_POST['numofpages']." (előtte ".$temp[0]["numofpages"]." volt)<br>";
	}
	
$body .= "<br>
Üdvözlettel:<br>
<br>
Tracker<br>";

			sendMail( $subject, $body, $to, "" );
			}
		}
	
	$order = sql_aget( "calendar_post", "id='".$_POST['pid']."'", "*" );
	$counter = sql_aget( "calendar_counters", "publisher_id='".$order[0]["publisher_id"]."'", "*" );
	sql_update( "calendar_counters", "counter='".( intval( $counter[0]["counter"] ) + 1 )."'", "id='".$counter[0]["id"]."'" );
	}

if( $_GET["sub"] == "calendar" ) {
	$error = array();
	$szamlength = strlen( strval( $_POST['jobcode'] ) );
	
	if( $szamlength < 4 ) $error[] = "jobcode";
	
	if( count( $error ) == 0 ) {
		$code = $_POST['jobcode'];
		
		$magazine = sql_get( "magazines", "id='".$_POST["magazine"]."'", "*" );
		
		$name = array( "magazine_id", "salesDay", "printday", "code", "specificName", "magCode", "publisher_id", "numofpages" );
		$value = array( $_POST["magazine"], $_POST["salesday"], $_POST["printorder"], $code, $_POST['customname'], $magazine[0][3], $magazine[0][1], $_POST['numofpages'] );	
		$id = sql_add( "calendar_post", $name, $value );
		
		$order = sql_aget( "calendar_post", "id='".$id."'", "*" );
		$magazine = sql_aget( "magazines", "id='".$order[0]["magazine_id"]."'", "*" );	
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $_SESSION['intra_user'], 'addtocalendar', $magazine[0]["publisher_id"], $magazine[0]["name"], $order[0]["code"], '', time(), '' );
		sql_add( 'action_log', $names, $values );
		
		$counter = sql_aget( "calendar_counters", "publisher_id='".$magazine[0]["publisher_id"]."'", "*" );
		sql_update( "calendar_counters", "counter='".( intval( $counter[0]["counter"] ) + 1 )."'", "id='".$counter[0]["id"]."'" );
		}
	
	$result = array( $error );
	}
	
print json_encode( $result );
?>		