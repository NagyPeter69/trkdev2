<?PHP

$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];
$remark = $_POST["remark"];
$description = $_POST["description"];	

$name = nameCalculator2( $_POST );	
$found = 0;
$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
$type = $p_id[0][10];
$p_code = $p_id[0][3];

if( $issue == '' ) {
	$xml2 = simplexml_load_file( TRKPATH."/xml/".PMD.".xml" );
	$xpath = $xml2->xpath('/Publications');
	foreach($xpath as $temp) {
		for( $i = 0; $i < count( $temp->Item ); $i++ ) {
			if( $temp->Item[$i]->Code == $p_id[0][3] )
				break;
			}
		}
	
	$current = $_POST["issue"];
	$issue = $current;
	}

$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'"', '*' );
for( $x = 0; $x < count( $packages ); $x++ ) {	
	if( strtolower( $packages[$x][2] ) == strtolower( $name ) ) {
		$found = 1;
		$target = $packages[$x][0];
		break;
		}	
	else {
		$found = 0;
		}
	}

if( $found == 0 ) {
	for( $x = 0; $x < count( $packages ); $x++ ) {	
		if( preg_match( "/".$packages[$x][2]."/i", $name ) && !strstr( $name, "_" ) ) {
			$found = 1;
			$target = $packages[$x][0];
			break;
			}
		else {
			$found = 0;
			}
		}
	}

if( $found == 0 ) {
	for( $x = 0; $x < count( $packages ); $x++ ) {
		$tar = explode( "_", $packages[$x][2] );
		foreach( $tar as $t ) {
			if( preg_match( "/".$name."/i", $t ) ) {
				$found = 1;
				$target = $packages[$x][0];
				break 2;
				}
			}
		}
	}
$start = searchRange_array( $_POST );
$gen_dir = $name;
if( $found == 0 ) {
	$names = array( 'publication_id', 'name', 'starting_page', 'directory', 'acquired_name', 'status_changed' );

	$chk = $jcode.'|'.$issue;

	if( $issue == '' ) {
		$issue = date( 'm' ).date( 'y' );
		}

	$values = array( $p_id[0][0], $name, $start, $gen_dir, $description, time() );		
	sql_add( 'packages', $names, $values );

	$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
	$values = array( '', 'newArticle', $p_id[0][1], $p_id[0][2], $p_id[0][10], $name, time(), '' );
	sql_add( 'action_log', $names, $values );

	$oldmask = umask(0);
	@mkdir("../packages/".$jcode, 0777);
	@mkdir("../packages/".$jcode."/".$issue, 0777);
	@mkdir("../packages/".$jcode."/".$issue."/".$gen_dir, 0777);
	umask($oldmask);
	}

if( $found == 1 ) {
	$names = array( 'name', 'starting_page', 'acquired_name', 'status', 'status_changed' );
	$values = array( $name, $start, $description, 0, time() );

	$command = '';
	for( $a = 0; $a < count( $names ); $a++ ) {
		$command .= $names[$a].'=\''.$values[$a].'\'';

		if( $a < count( $names )-1 ) {
			$command .= ', ';
			}
		}
	$oldmask = umask(0);
	@mkdir("../packages/".$jcode, 0777);
	@mkdir("../packages/".$jcode."/".$issue, 0777);
	@mkdir("../packages/".$jcode."/".$issue."/".$gen_dir, 0777);
	umask($oldmask);
	sql_update( 'packages', $command, 'id=\''.$target.'\'' );
	sql_delete( 'package_info', 'id="'.$target.'"' );
	}

foreach( $_POST as $key=>$value ) {
	if( strpos( $key,  "image_" ) !== false ) {
		$names = array( "pub_id", "name", "retus" );
		$values = array( $p_id[0][0], $value, "0" );
		sql_add( "image_map", $names, $values );
		} 
	}

//Levélküldés
$xml = simplexml_load_file( TRKPATH."/xml/".PMD.".xml" );
$xpath = $xml->xpath('/Publications');
foreach($xpath as $temp) {
	for( $i = 0; $i < count( $temp->Item ); $i++ ) {
		if( $temp->Item[$i]->Code == $_POST['jobCode'] )
			break;
		}
	}
	
// $file = $_POST["fileName"].".xml";
// if ( move_uploaded_file( $_FILES[0]["tmp_name"], $file ) ) {
	// $report = simplexml_load_file( $file );
	// $xpath = $report->xpath('/uploadReport');
	
	// $mails = (string) $xml->Item[$i]->Mails;
	// $mails = explode( ";", $mails );
	// $url = URL;
	// $pub = sql_aget( "publisher", "id='".$p_id[0][1]."'", "*" );
	// if( $pub[0]["name"] == "TestCo" ) {
		// $url = "trkdev.colorcom.hu";
		// }

	// for( $i = 0; $i < count( $mails ); $i++ ) {
		// $to = $mails[$i]."|".$mails[$i];
		// $subject = str_replace(".indd", "", $report->docName)." ::: ".$report->subject;
		// $body = "";
		
		// $body .= "Dear User,<br><br>";
		// $body .= "uploading of the document ".str_replace(".indd", "", $report->docName)." was successful, its processing has been started.<br><br>";
		// $body .= "Kind regards,<br><br>";
		// $body .= "Colorcom Media";
		
		// error_log( $body );
		// produkcioSendmail( $subject, $body, $to );
		// }
	// }
?>