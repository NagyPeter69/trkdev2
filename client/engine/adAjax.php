<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( 'switchAPI.php' );
	include_once('../lang/en.php');
	
	$ad = sql_get( 'ads', 'id="'.$_GET['id'].'"', '*' );
	$pub = sql_get( 'publications', 'id="'.$ad[0][1].'"', '*' );
	$issue = $pub[0][10];
	$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
	// Adhoc jobs carry publisher_id="0" by convention - fall back to
	// publications.owner, same as resolveJobPublisherName() in switchAPI.php.
	$publisherId = $pub[0][1];
	if( empty( $publisherId ) || $publisherId == '0' ) {
		$publisherId = $pub[0][23];
		}
	$client = sql_get( 'publishers', 'id="'.$publisherId.'"', '*' );

	if( $_GET['op'] == "force" ) {
		sql_update( "ads", "status='0'", "id='".$ad[0][0]."'" );
		
		//$result = implode( "|", $array );	
		}
	
	if( $_GET['op'] == "proof" ) {
		switch( $ad[0][3] ) {
			case '2/1':
				$type = 'D';
				break;
			case '1/1':
				$type = 'F';
				break;
			default:
				$type = 'P';
				break;	
			}
			
		$file_name = strtoupper( $ad[0][2] ).'_'.$magazine[0][3].'_'.$issue.'_'.$type;	
		$dir = '../advertisements';
		$absdir = 'advertisements';
		$dirFiles = load_dir_files( $dir, $file_name );

		//Új
//		if( $client[0][0] == "35" ) {
			$array = array(
				"event" => "proof",
				"client" => $client[0][1],
				"pubName" => $magazine[0][2],
				"jobCode" => $magazine[0][3],
				"issue" => $issue,
				"description" => strtoupper( $ad[0][2] ),
				"size" => str_replace( "/", "_", strtoupper( $ad[0][3] ) ),
				"pageNum" => "NA",
				"pageType" => "AD",
				);
			
			for( $y = 0; $y < count( $dirFiles ); $y++ ) {
				if( substr( $dirFiles[$y], -4 ) == ".pdf" ) {
					$file = array( 
						"name" => $dirFiles[$y],
						"path" => $absdir,
						);	
					
					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
					$values = array( $user[0][0], "adProof", $pub[0][1], $pub[0][2], $pub[0][10], $ad[0][8], time(), "", "" );
					sql_add( 'action_log', $names, $values );
										
					$error = SwitchSend_TESZT( $array, $file );	
					}
					
				}
//			}
		
/*		else {	
		//Régi
		$array = array();
		$array["client"] = $client[0][1];
		$array["jobCode"] = $magazine[0][3];
		$array["issue"] = $issue;
		$array["event"] = 'proof';
		$array["description"] = strtoupper( $ad[0][2] );
		$array["size"] = str_replace( "/", "_", strtoupper( $ad[0][3] ) );
		$array["pageNum"] = "NA";
		$array["pageType"] = "AD";

		$myxml = array_to_xml( $array, 'eventComm' );
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		
		$sftp = ftp_conn();

		for( $y = 0; $y < count( $dirFiles ); $y++ ) {
			if( substr( $dirFiles[$y], -4 ) == ".pdf" ) {
				$counter = get_counter('..');
				$saveTo = 'C_Hotfolders/messages/message_'.$counter;
				//unlink( $dir.'/'.$dirFiles[$y] );
				$sftp->put( $saveTo.'.pdf', $dir.'/'.$dirFiles[$y], NET_SFTP_LOCAL_FILE );
				$sftp->put( $saveTo.'.xml', $dom->saveXML()  );	
				inc_counter('..');
				}
			}
			}	*/
		sql_update( "ads", "proofCounter='".($ad[0][10]+1)."'", "id='".$ad[0][0]."'" );
		//$result = implode( "|", $array );	
		}
	
print json_encode( $result );
	
?>