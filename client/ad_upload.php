<?php
	session_start();
	include_once('../engine/connect.php');
	include_once('../engine/engine.php');
	include_once('../engine/xml_handler.php');
	include_once( "engine/switchAPI.php" );

	
	$_FILES["file"]['name'] = letter_change( $_FILES["file"]['name'] );	
	
	$full_type = explode( '.', $_FILES["file"]['name'] );
	$type = strtolower( $full_type[count($full_type)-1] );		
	$allowed = array( 'jpg', 'jpeg', 'tif', 'tiff', 'pdf' );
	
	$tmp_name = $_FILES["file"]["tmp_name"];
				
	$target = 'uploads/ads';
						
	for( $i = 0; $i < count( $full_type )-1; $i++ ) { 
		$merge .= $full_type[$i];
		if( ( $i ) < ( count( $full_type ) -2 ) )
			$merge .= '.';
		}
	$doc_name = $merge.".".strtolower( $type );
	$xml_name = $merge.".xml";

if( in_array( $type, $allowed ) ) {
	if ( @move_uploaded_file($tmp_name, $target.'/'.$doc_name ) ) {
		$issue = sql_get( 'publications', 'id="'.$_POST['p_id'].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
		$client = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
		$array = array();
	
		$array["client"] = $client[0][1];
		$array["jobCode"] = $magazine[0][3];
		$array["issue"] = $issue[0][10];
		$array["event"] = 'new_ad';
		$array["description"] = strtoupper( $_POST['job_code'] );
		$array["remark"] = str_replace( '/', '_', $_POST['size'] );
	
		$myxml = array_to_xml( $array, 'eventComm' );
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		logToFile( 'ad_checking.txt' , '---- JÖTT EGY HIRDETÉS ----' );
		//if( $array["jobCode"] == "TST" ) {
		if( 1 ) {			
			$array = array(
				"event" => "new_ad",
				"client" => $client[0][1],
				"jobCode" => $magazine[0][3],
				"issue" => $issue[0][10],
				"description" => strtoupper( $_POST['job_code'] ),
				"size" => str_replace( '/', '_', $_POST['size'] ),
				);			
			
			$file = array( 
				"name" => $doc_name,
				"path" => $target,
				);
			
			error_log( $target." | ".$doc_name );
			
			$response = SwitchSend_TESZT( $array, $file );
			//$response[0] = "true";
			error_log( $response[0]." ".gettype( $response[0] ) );
				
			if( $response[0] == "true" ) {
				$_POST['job_code'] = strtolower( $_POST['job_code'] );
				$_POST['job_code'] = ucfirst( $_POST['job_code'] );
				
				$check_ad = sql_get( 'ads', 'name="'.$_POST['job_code'].'" AND pub_id="'.$_POST['p_id'].'"', '*' );
				
				logToFile( 'ad_checking.txt' , 'Hirdetés létezésének vizsgálata => name="'.$_POST['job_code'].'" AND pub_id="'.$_POST['p_id'].'"' );
				logToFile( 'ad_checking.txt' , "ha lérezik akkor az ID-je: ".$check_ad[0][0] );
				if( $check_ad[0][0] == '' ) {
					$names = array( 'pub_id', 'name', 'size', 'file_name', 'publisher' );
					$values = array( $issue[0][0], $_POST['job_code'], $_POST['size'], $doc_name, $client[0][0] );

					$command = 'INSERT INTO ads (';
					$command2 = '(';
					
					for( $c = 0; $c < count( $names ); $c++ ) {
						if( $c < count( $names )-1 ) {
							$command .= '`'.$names[ $c ].'`,';
							$command2 .= ' \''.$values[ $c ].'\',';  
							}
						else {
							$command .= '`'.$names[ $c ].'`) VALUES';
							$command2 .= '\''.$values[ $c ].'\'';  
							}
						}
					$command .= $command2 .');';
					logToFile( 'ad_checking.txt' , $command );

					sql_add( 'ads', $names, $values );
					}
				else {
					$p_ads = sql_get( 'partial_ads', 'ads_id="'.$check_ad[0][0].'" ORDER BY `date` ASC', 'id' );
					for( $c = 0; $c < count( $p_ads )-1; $c++ ) {
						sql_delete( 'partial_ads', 'id="'.$p_ads[$c][0].'"' );
						}				
					$names = array( 'uploaded', 'reason', 'status', 'size', 'date' );
					$values = array( '', '', '1', $_POST['size'], date ("Y-m-d H:i:s") );
					$command = '';
					for( $i = 0; $i < count( $names ); $i++ ) {
						$command .= $names[$i].'=\''.$values[$i].'\'';
						if( $i < count( $names )-1 ) {
							$command .= ', ';
							}
						}
					logToFile( 'ad_checking.txt' , $command );	
						
					sql_update( 'ads', $command, 'id=\''.$check_ad[0][0].'\'' );
					}
					
				$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
				$values = array( $_SESSION['intra_user'], 'newAD', $issue[0][1], $issue[0][2], $issue[0][10], $_POST['job_code'], time() );
				sql_add( 'action_log', $names, $values );
				logToFile( 'ad_checking.txt' , "-----------------------------------" );	
				
				if( $type == "pdf" ) {
					adThumbCreate3( "advertisements", $target.'/'.$doc_name, $_POST['job_code']."_preview.jpg" );
					}
				else {
					adThumbCreateIMG( "advertisements", $target.'/'.$doc_name, $_POST['job_code']."_preview.jpg" );
					}
				
				$result = 'A hirdetés sikeresen feltöltve.-1';
				$result = $_POST['job_code'];		
				}
			else {
				$result =  'A feltöltés sikertelen.-0';
				}
		
			}
			
		else {			
			/*$_POST['job_code'] = strtolower( $_POST['job_code'] );
			$_POST['job_code'] = ucfirst( $_POST['job_code'] );
			$check_ad = sql_get( 'ads', 'name="'.$_POST['job_code'].'" AND pub_id="'.$_POST['p_id'].'"', '*' );
			
			// Log írása fájlba
			logToFile( 'ad_checking.txt' , 'Hirdetés létezésének vizsgálata => name="'.$_POST['job_code'].'" AND pub_id="'.$_POST['p_id'].'"' );
			// -------
			if( $check_ad[0][0] == '' ) {
				$names = array( 'pub_id', 'name', 'size', 'file_name', 'publisher' );
				$values = array( $issue[0][0], $_POST['job_code'], $_POST['size'], $doc_name, $client[0][0] );
				sql_add( 'ads', $names, $values );
				}
			else {
				$p_ads = sql_get( 'partial_ads', 'ads_id="'.$check_ad[0][0].'" ORDER BY `date` ASC', 'id' );
				for( $c = 0; $c < count( $p_ads )-1; $c++ ) {
					sql_delete( 'partial_ads', 'id="'.$p_ads[$c][0].'"' );
					}				
				$names = array( 'uploaded', 'reason', 'status', 'size', 'date' );
				$values = array( '', '', '1', $_POST['size'], date ("Y-m-d H:i:s") );
				$command = '';
				for( $i = 0; $i < count( $names ); $i++ ) {
					$command .= $names[$i].'=\''.$values[$i].'\'';
					if( $i < count( $names )-1 ) {
						$command .= ', ';
						}
					}	
				sql_update( 'ads', $command, 'id=\''.$check_ad[0][0].'\'' );
				}
			file_put_contents( $target.'/'.$xml_name , $dom->saveXML() );
		
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
			$values = array( $_SESSION['intra_user'], 'newAD', $issue[0][1], $issue[0][2], $issue[0][10], $_POST['job_code'], time() );
			sql_add( 'action_log', $names, $values );
		
			$result = 'A hirdetés sikeresen feltöltve.-1';
			$result = $_POST['job_code'];*/
			}
		
		}
	else {
		$result =  'A feltöltés sikertelen.-0';
		}
	}
else {
	$result =  'Hibás kiterjesztés. ( '.$type.' )-0';
	}

echo $result;