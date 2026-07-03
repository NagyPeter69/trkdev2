<?
	header('Content-Type: text/html; charset=utf-8');

	include_once( 'connect.php' );
	include_once( 'engine.php' );
	
	function changeIssueStatus( $file, $value, $data ) {	
		if( $value == "remove" ) {
			return true;
			}
		else {
			$xml = simplexml_load_file(  "../xml/".$file );
			$xml->status = $value;

			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($xml->asXML());
			$dom->formatOutput = true;
		
			$newFile = $file;
			file_put_contents(  "../xml/".$newFile, $dom->saveXML() );
			
			toSwitch( 'new_publication' , 'publications|'.$data, 'C_database/'.substr( $file, 0, -4 ), 'issueData' );
			
			if( file_get_contents( $newFile ) == "" )
				return false;
				
			if( file_get_contents( $newFile ) != "" ) {
				$array = array(
					"event" => "xml_data",
					);
					
				$file = array( 
					"name" => $file,
					"path" => "xml",
					);
				$response = SwitchSend_TESZT( $array, $file );				
				}
			return true;
			}
		}
	
	function removeFromXML( $xml, $code, $node, $search ) {
		$xml_path = $xml;
		$xml = simplexml_load_file( $xml_path );
		
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $code ) {
					break;
					}
				}
			}		
		
		$xpath = $xml->xpath( '/Publications/Item[Code = "'.$code.'"]'.$node );
		$i = 0;
		foreach( $search as $key => $val ) {
			foreach($xpath as $temp) {
				for( $y = 0; $y < count( $temp->$key ); $y++ ) {
					if( $i == intval( $val ) ) {
						break;
						}
			
					$i++;
					}
				}
			$pos = intval( $val )+1;
			$path = $xml->xpath( '/Publications/Item[Code = "'.$code.'"]'.$node.'/'.$key.'[position() = '.$pos.']' );
			//$path = $xml->Item[ intval($x) ]->{substr( $node, 1 )}->{$key}[$y];
			
			foreach( $path as $v ) {
				$dom=dom_import_simplexml($v);
				$dom->preserveWhiteSpace = false;
				$dom->parentNode->removeChild($dom);
				}
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($xml->asXML());
			$dom->formatOutput = true;
			file_put_contents( $xml_path, $dom->saveXML() );
			}
		}

	function collectFromXml( $xml, $code, $nodes, $returnnode = '' ) {
		$result = array();
		$xml = simplexml_load_file( $xml );
		$xpath = $xml->xpath('/Publications');
		
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $code ) {
					break;
					}
				}
			}
		
		if( gettype( $nodes ) != 'array' )
			$nodes = array( $nodes );
		
		foreach( $nodes as $node ) {
			$xpath = $xml->xpath('/Publications/Item[Code = "'.$code.'"]/'.$node);
			foreach($xpath as $temp) {
				if( $returnnode == '' ) {
					$result[$node] = $temp;
					}
				else {
					foreach( $temp->$returnnode as $val ) {
						$result[$node][] = $val;
						}
					}
				}
			}
		return $result;
		}

	function changeJOBXmlDatabase( $operation, $values, $xml2 = 'client/xml/job.xml' ) {
		$xml = simplexml_load_file( $xml2 );
		$deny = array( 'addRUser', 'publisher', 'magazine', 'old', 'old_code', 'xml_go', $values['deny'], 'deny', 'type', 'code', 'CustomCode_2', 'counter_parts', 'Client2', 'adhocUser' );
		
		switch( $operation ) {
			case 'delete':
				$code = $values['code'];
				$publisher = $values['publisher'];
				$job = sql_get( 'jobs', 'code="'.$code.'"', '*' );
				
				sql_delete( 'jobs', 'id="'.$job[0][0].'"' );
				if( is_dir( '../client/packages/'.$code ) )
					delTree('../client/packages/'.$code );
				
				$xpath = $xml->xpath('/Job');
				
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $code && $temp->Item[$i]->Publisher == $publisher ) {
							$dom=dom_import_simplexml($temp->Item[$i]);
							$dom->preserveWhiteSpace = false;
							$dom->parentNode->removeChild($dom);
							}
						}
					}
				break;
				
			case 'modify':
				$trimsize = '';
				$xpath = $xml->xpath('/Job');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $values['Code'] && $temp->Item[$i]->Publisher == $values["Publisher"] )
							break;
						}
					}

				foreach( $values as $key => $val ) {
					if( !in_array( $key, $deny ) ) {
						if( strstr( $key, 'TrimSize_' ) ) {
							if( $trimsize == '' ) {
								$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
								}
							}
			
						switch( $key ) {
							case 'TrimSize_x':
								break;
							case 'TrimSize_y':
								$xml->Item[$i]->TrimSize = $trimsize;
								break;
							default:
								$xml->Item[$i]->$key = $val;
								break;
							}	
			
						}
					}
				
				break;
			
			case 'add':
				$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
				$code = $xml->addChild( 'Item' );
				$code->addChild( 'Name', $values['Name'] );
				$code->addChild( 'Code', $values['Code'] );
				$code->addChild( 'Publisher', $values['Publisher'] );
				$code->addChild( 'ColorManagement', $values['ColorManagement'] );
				$code->addChild( 'TrimSize', $trimsize );
				$code->addChild( 'FacingPages', $values['FacingPages'] );
				$code->addChild( 'Deadline', str_replace( " ", "T", $values['Deadline'] ) );
				$remote = $code->addChild( 'RemoteStorage' );
					$remote->addChild( 'Address', '' );
					$remote->addChild( 'Login', '' );
					$remote->addChild( 'Password', '' );
					$remote->addChild( 'Port', '' );
					$remote->addChild( 'Path', '' );
					$remote->addChild( 'Binary', '' );
					$remote->addChild( 'Passive', '' );
				$code->addChild( 'Mails', $values['Mails'] );
				$code->addChild( 'MailComm', $values['MailComm'] );
				break;			
			}

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
	
		file_put_contents( $xml2, $dom->saveXML() );
		}

	function XMLPMDUP( $file ) {
		$array = array(
			"event" => "xml_data",
			);
					
		$file = array( 
			"name" => PMD_LONG.".xml",
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );	
		}
		
	function XMLUpload2( $file ) {
		$array = array(
			"event" => "xml_data",
			);

		// Safety net: never let a non-production system upload the PMD
		// dataset to Switch under the same filename production uses. If
		// the machine's hostname contains "dev" (case-insensitive, e.g.
		// "trkdev2"), tag the uploaded file with a _DEV suffix so Switch
		// can never mistake it for the real dataset.
		if( stripos( gethostname(), 'dev' ) !== false ) {
			$dot = strrpos( $file, '.' );
			if( $dot !== false ) {
				$file = substr( $file, 0, $dot ) . '_DEV' . substr( $file, $dot );
				}
			else {
				$file .= '_DEV';
				}
			}

		$file = array(
			"name" => $file,
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );
		}
	
	function XMLUpload( $xml2 = 'client/xml/'.PMD.'.xml' ) {
		if( is_file( 'xml/'.PMD.'.xml' ) ) {
			$xpath = 'xml/'.PMD.'.xml';
			}
		elseif( is_file( $xml2 ) ) {
			$xpath = $xml2;
			}
		else {
			$xpath = 'client/xml/'.PMD.'.xml';
			}

		$array = array(
			"event" => "xml_data",
			);
					
		$file = array( 
			"name" => "Publications_Master_Data_NT.xml",
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );		
		}

	function changeXmlDatabase_ext( $operation, $values, $xml2 = '/var/www/html/client/xml/'.PMD.'.xml' ) {
		$xml = simplexml_load_file( $xml2 );
		$deny = array( 'addRUser', 'ApprovedComments', 'publisher', 'magazine', 'old', 'old_code', 'xml_go', $values['deny'], 'deny', 'type', 'code', 'CustomCode_2', 'counter_parts', 'Client2', 'adhocUser' );
		
		switch( $operation ) {
			case 'modify':
				$trimsize = '';
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $values['old_code'] )
							break;
						}
					}
					
				foreach( $values as $key => $val ) {
					if( !in_array( $key, $deny ) ) {
						if( strstr( $key, 'TrimSize_' ) ) {
							if( $trimsize == '' ) {
								$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
								}
							}
						
						switch( $key ) {
							case 'CustomCode':
								if( $values['CustomCode'] == 'No' )
									$xml->Item[$i]->CustomCode = 'No';
								else 
									$xml->Item[$i]->CustomCode = $values['CustomCode'];
								break;
							case 'TrimSize_x':
								break;
							case 'TrimSize_y':
								$xml->Item[$i]->TrimSize = $trimsize;
								break;
							case 'Cover':
							case 'Content':
							case 'Insert':
								$xml->Item[$i]->ColorManagement->$key = $val;
								break;
							default:
								$xml->Item[$i]->$key = $val;
								break;
							}	
			
						}
					}
				break;
				
			case 'delete':
				$code = $values['old_code'];
				$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
				
				$temp = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'"', '*' );
				for( $i = 0; $i < count( $temp ); $i++ ) {
					sql_delete( 'ads', 'pub_id="'.$temp[$i][0].'"' );
					sql_delete( 'parts', 'pub_id="'.$temp[$i][0].'"' );
					$packs = sql_get( 'packages', 'publication_id="'.$temp[$i][0].'"', '*' );
					for( $y = 0; $y < count( $packs ); $y++ ) {
						sql_delete( 'package_info', 'package_id="'.$packs[$y][0].'"' );
						}
					sql_delete( 'packages', 'publication_id="'.$temp[$i][0].'"' );
					}
				
				sql_delete( 'magazines', 'id="'.$magazine[0][0].'"' );
				if( is_dir( '../client/packages/'.$code ) )
					delTree('../client/packages/'.$code );
				
				$xpath = $xml->xpath('/Publications');
				
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $code ) {
							$dom=dom_import_simplexml($temp->Item[$i]);
							$dom->preserveWhiteSpace = false;
							$dom->parentNode->removeChild($dom);
							}
						}
					}
				break;
				
			case 'add':
				$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
				$code = $xml->addChild( 'Item' );
				$code->addChild( 'Name', $values['Name'] );
				$code->addChild( 'Code', $values['Code'] );
				$code->addChild( 'Publisher', $values['Publisher'] );
				$code->addChild( 'Language', $values['Language'] );
				$code->addChild( 'Period', $values['Period'] );
				$code->addChild( 'AdAutoProof', $values['AdAutoProof'] );
				$code->addChild( 'Workflow', $values['Workflow'] );
				$code->addChild( 'FlatplanStages', $values['FlatplanStages'] );
				$code->addChild( 'Resolution', $values['Resolution'] );
				$code->addChild( 'IDversion', $values['IDversion'] );
				$code->addChild( 'Enhance', $values['Enhance'] );
	
				$color = $code->addChild( 'ColorManagement' );
				$color->addChild( 'Cover', $values['Cover'] );
				$color->addChild( 'Content', $values['Content'] );
				$color->addChild( 'Insert', $values['Insert'] );
	
				$code->addChild( 'PDFstandard', $values['PDFstandard'] );
				$code->addChild( 'ArchiveStorage', $values['ArchiveStorage'] );
				$code->addChild( 'OutputFormat', $values['OutputFormat'] );
				$code->addChild( 'CustomCode', $values['CustomCode'] );
				$code->addChild( 'ImageRename', $values['ImageRename'] );
				$code->addChild( 'TrimSize', $trimsize );
				$code->addChild( 'LocalStorage', $values['LocalStorage'] );
				$code->addChild( 'RemoteStorage', $values['RemoteStorage'] );
				$code->addChild( 'Parent', $values['Parent'] );
				$code->addChild( 'FinalOutput', $values['FinalOutput'] );
				$code->addChild( 'ArchiveMode', $values['ArchiveMode'] );
				$code->addChild( 'Mails', "" );
				$code->addChild( 'MailComm', "No" );
				$ads = $code->addChild( 'AdSizes' );
				//$ads->addChild( 'value', '1/1 portrait, trim: '.$values['TrimSize_x'].' x '.$values['TrimSize_y'].' mm' );
				$code->addChild( 'WebImages', "No" );
				break;
			}

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
	
		file_put_contents( $xml2, $dom->saveXML() );
		}

	function changeXmlDatabase( $operation, $values, $xml2 = '/var/www/html/client/xml/'.PMD.'.xml' ) {
		error_log( "CHANGEXML DEBUG" );
		error_log( "--".$operation."--" );
		error_log( $xml2 );
		$xml = simplexml_load_file( $xml2 );
		$deny = array( 'addRUser', 'ApprovedComments', 'pn', 'Uploadable', 'Deadline', 'publisher', 'magazine', 'old', 'old_code', 'xml_go', $values['deny'], 'deny', 'type', 'code', 'CustomCode_2', 'counter_parts', 'Client2', 'adhocUser' );
		
		switch( $operation ) {
			case 'modify':
				$trimsize = '';
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $values['old_code'] )
							break;
						}
					}
				foreach( $values as $key => $val ) {
					if( !in_array( $key, $deny ) ) {
						if( strstr( $key, 'TrimSize_' ) ) {
							if( $trimsize == '' ) {
								$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
								}
							}
			
						switch( $key ) {
							case 'CustomCode':
								if( $values['CustomCode'] == 'No' )
									$xml->Item[$i]->CustomCode = 'No';
								else 
									$xml->Item[$i]->CustomCode = $values['CustomCode'];
								break;
							case 'TrimSize_x':
								break;
							case 'TrimSize_y':
								$xml->Item[$i]->TrimSize = $trimsize;
								break;
							case 'Cover':
							case 'Content':
							case 'Insert':
								$xml->Item[$i]->ColorManagement->$key = $val;
								break;
							default:
								error_log( "--".$key." => ".$val."--" );
								$xml->Item[$i]->$key = $val;
								break;
							}	
			
						}
					}
				break;
				
			case 'delete':
				$code = $values['old_code'];
				$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
				
				$temp = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'"', '*' );
				for( $i = 0; $i < count( $temp ); $i++ ) {
					sql_delete( 'ads', 'pub_id="'.$temp[$i][0].'"' );
					sql_delete( 'parts', 'pub_id="'.$temp[$i][0].'"' );
					$packs = sql_get( 'packages', 'publication_id="'.$temp[$i][0].'"', '*' );
					for( $y = 0; $y < count( $packs ); $y++ ) {
						sql_delete( 'package_info', 'package_id="'.$packs[$y][0].'"' );
						}
					sql_delete( 'packages', 'publication_id="'.$temp[$i][0].'"' );
					}
				
				sql_delete( 'magazines', 'id="'.$magazine[0][0].'"' );
				if( is_dir( TRKPATH.'/packages/'.$code ) )
					delTree( TRKPATH.'/packages/'.$code );
				
				$xpath = $xml->xpath('/Publications');
				
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $code ) {
							$dom=dom_import_simplexml($temp->Item[$i]);
							$dom->preserveWhiteSpace = false;
							$dom->parentNode->removeChild($dom);
							}
						}
					}
				break;
				
			case 'add':
				if( $values["Type"] == "Adhoc" ) {
					$code = $xml->addChild( 'Item' );
					$code->addChild( 'Name', $values['Name'] );
					$code->addChild( 'Code', $values['Code'] );
					$code->addChild( 'Publisher', $values['Client'] );
					$code->addChild( 'Language', $values['Language'] );
					$code->addChild( 'Workflow', $values['Workflow'] );
					$code->addChild( 'FlatplanStages', $values['FlatplanStages'] );
					$code->addChild( 'PageNumbering', $values['PageNumbering'] );
					$code->addChild( 'Resolution', $values['Resolution'] );
					$code->addChild( 'Enhance', $values['Enhance'] );
					$code->addChild( 'PDFstandard', $values['PDFstandard'] );
					$code->addChild( 'ArchiveStorage', $values['ArchiveStorage'] );
					$code->addChild( 'OutputFormat', $values['OutputFormat'] );
					$code->addChild( 'CustomCode', $values['CustomCode'] );
					$code->addChild( 'ImageRename', $values['ImageRename'] );
					$code->addChild( 'LocalStorage', $values['LocalStorage'] );
					$code->addChild( 'RemoteStorage', $values['RemoteStorage'] );
					$code->addChild( 'Parent', $values['Parent'] );
					$code->addChild( 'FinalOutput', $values['FinalOutput'] );
					$code->addChild( 'Mails', $values['Mails'] );
					$code->addChild( 'MailComm', "No" );
					$ads = $code->addChild( 'AdSizes' );
					//$ads->addChild( 'value', '1/1 portrait, trim: '.$values['TrimSize_x'].' x '.$values['TrimSize_y'].' mm' );
					$code->addChild( 'WebImages', "No" );
					$code->addChild( 'Type', $values['Type'] );
					$code->addChild( 'Client', $values['Client'] );
					}
					
				if( $values["Type"] == "Regular" ) {
					$code = $xml->addChild( 'Item' );
					$code->addChild( 'Name', $values['Name'] );
					$code->addChild( 'Code', $values['Code'] );
					$code->addChild( 'Publisher', $values['Client'] );
					$code->addChild( 'Language', $values['Language'] );
					$code->addChild( 'Workflow', $values['Workflow'] );
					$code->addChild( 'FlatplanStages', $values['FlatplanStages'] );
					$code->addChild( 'PageNumbering', $values['PageNumbering'] );
					$code->addChild( 'Resolution', $values['Resolution'] );
					$code->addChild( 'Enhance', $values['Enhance'] );	
					$code->addChild( 'PDFstandard', $values['PDFstandard'] );
					$code->addChild( 'ArchiveStorage', $values['ArchiveStorage'] );
					$code->addChild( 'OutputFormat', $values['OutputFormat'] );
					$code->addChild( 'CustomCode', $values['CustomCode'] );
					$code->addChild( 'ImageRename', $values['ImageRename'] );
					$code->addChild( 'LocalStorage', $values['LocalStorage'] );
					$code->addChild( 'RemoteStorage', $values['RemoteStorage'] );
					$code->addChild( 'Parent', $values['Parent'] );
					$code->addChild( 'FinalOutput', $values['FinalOutput'] );
					$code->addChild( 'ArchiveMode', $values['ArchiveMode'] );
					$code->addChild( 'Mails', "" );
					$code->addChild( 'MailComm', "Yes" );
					$ads = $code->addChild( 'AdSizes' );
					//$ads->addChild( 'value', '1/1 portrait, trim: '.$values['TrimSize_x'].' x '.$values['TrimSize_y'].' mm' );
					$code->addChild( 'WebImages', "YES" );
					$code->addChild( 'Type', $values['Type'] );
					$code->addChild( 'Client', $values['Client'] );
					}
				break;
			}
		
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
		file_put_contents( $xml2, $dom->saveXML() );
		file_put_contents( str_replace( PMD.".xml", PMD_LONG.".xml", $xml2 ), $dom->saveXML() );
		//file_put_contents( str_replace( PMD.".xml", "Publications_Master_Data_NT.xml", $xml2 ), $dom->saveXML() );
		
		$array = array(
			"event" => "xml_data",
			);
			
		$file = array( 
			"name" => PMD_LONG.".xml",
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );
		}
	
	function toSwitch( $type, $job, $saveTo, $root ) {
		$array = array();
		$info = explode( '|', $job );
		
		$job = sql_get( $info[0], 'id="'.$info[1].'"', '*'  );
		$magazine = sql_get( "magazines", "id='".$job[0][2]."'", "code" );

		$newxml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
		$xpath = $newxml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $magazine[0][0] ) {
					break;
					}
				}
			}
				
		switch( $type ) {
			case 'deleted':
				$job = sql_get( $info[0], 'id="'.$info[1].'"', '*'  );
				$publisher = sql_get( 'publishers', 'id="'.$job[0][1].'"', 'name' );
				$magazine = sql_get( 'magazines', 'id="'.$job[0][2].'"', 'code' );
				
				$array["client"] = $publisher[0][0];
				$array["jobCode"] = $magazine[0][0];
				$array["issue"] = $job[0][10];
				$array["event"] = 'deleted_issue';
				break;
				
			case 'created':
				$job = sql_get( $info[0], 'id="'.$info[1].'"', '*'  );
				$publisher = sql_get( 'publishers', 'id="'.$job[0][1].'"', 'name' );
				$magazine = sql_get( 'magazines', 'id="'.$job[0][2].'"', 'code' );
				
				$array["client"] = $publisher[0][0];
				$array["jobCode"] = $magazine[0][0];
				$array["issue"] = $job[0][10];
				$array["event"] = 'issue_created';
				break;
				
			case 'new_publication':
				error_log( $info[0].", ".$info[1] );
				$job = sql_get( $info[0], 'id="'.$info[1].'"', '*'  );
				$magazine = sql_get( 'magazines', 'id="'.$job[0][2].'"', 'code' );
				
				if( $job[0][1] == "0" ) {
					$array['numOfPages'] = $job[0][6];
					$parts = sql_get( 'parts', 'pub_id="'.$job[0][0].'"', '*' );
					
					$array['parts'] = array();
					for( $i = 0; $i < count( $parts ); $i++ ) {
						$array['parts']['part|'.$i]['name'] = $parts[$i][2];
						
						if( $temp->Item[$x]->PageNumbering == "American" ) {
							$array['parts']['part|'.$i]['pages'] = $parts[$i][3];
							}
						else {
							$array['parts']['part|'.$i]['place'] = $parts[$i][3];
							}
							
						$array['parts']['part|'.$i]['color'] = $parts[$i][4];
						$array['parts']['part|'.$i]['size'] = $parts[$i][5];
						}
						
					$array['specificName'] = $job[0][17];
					$array['uploadable'] = $job[0][8];
					$array["code"] = $magazine[0][0];
					$array['issueCode'] = $job[0][10];
					$array['deadline'] = str_replace( ' ', 'T', trim( $job[0][11] ) );
					$array['status'] = ($job[0][12] == "current" ? "active" : $job[0][12] );
					}
					
				else {
					$array['numOfPages'] = $job[0][6];
					$parts = sql_get( 'parts', 'pub_id="'.$job[0][0].'"', '*' );
					$array['parts'] = array();
					for( $i = 0; $i < count( $parts ); $i++ ) {
						$array['parts']['part|'.$i]['name'] = $parts[$i][2];
						
						if( $temp->Item[$x]->PageNumbering == "American" ) {
							$array['parts']['part|'.$i]['pages'] = $parts[$i][3];
							}
						else {
							$array['parts']['part|'.$i]['place'] = $parts[$i][3];
							}						

						$array['parts']['part|'.$i]['color'] = $parts[$i][4];
						$array['parts']['part|'.$i]['size'] = $parts[$i][5];
						}
						
					$array['specificName'] = $job[0][17];
					$array['uploadable'] = $job[0][8];
					$array["code"] = $magazine[0][0];
					$array['issueCode'] = $job[0][10];
					$array['deadline'] = str_replace( ' ', 'T', trim( $job[0][11] ) );						
					$array['status'] = ($job[0][12] == "current" ? "active" : $job[0][12] );
					
					}
					
				$name = explode( "/", $saveTo );
				$name = $name[ (count($name)-1) ];					
				break;			
			}
		/*
		if( $job[0][25] == "unknown") {
			$array["client"] = "";
			}
		*/
		$myxml = array_to_xml( $array, $root );
		$dom = new DOMDocument();
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		
		if( $type == 'new_publication' ) {
			file_put_contents( "../xml/".$name.".xml", $dom->saveXML() );	
			}

		$array = array(
			"event" => "xml_data",
			);
			
		$file = array( 
			"name" => $name.".xml",
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );
		}
	
	function remove_from_xml( $xml, $target, $criteria ) {
		$sxe = simplexml_load_file( $xml );
		$sxe->asXML();
		
		$doc = new DOMDocument;
		$doc->preserveWhiteSpace = false;
		$doc->load( $xml );
		$xpath = new DOMXpath($doc);
		foreach($xpath->query('//'.$target.'[. = "'.$criteria.'"]') as $node) {
 			$node->parentNode->removeChild($node);
 			break;
			}
		
		file_put_contents( $xml , $doc->saveXML() );
		
		$sxe = simplexml_load_file( $xml );
		return $sxe->asXML();
		}
	
	function insert_to_xml( $xml, $insert, $target  ) {
		function simplexml_insert_after(SimpleXMLElement $insert, SimpleXMLElement $target) {
			$target_dom = dom_import_simplexml($target);
			$insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml($insert), true);
    			if ($target_dom->nextSibling) {
        			return $target_dom->parentNode->insertBefore($insert_dom, $target_dom->nextSibling);
    				} else {
       				return $target_dom->parentNode->appendChild($insert_dom);
    				}
			}
		$sxe = simplexml_load_file( $xml );
		if( gettype( $insert ) == 'array' ) {
			$insert = array_to_xml( $insert[1], $insert[0] );
			}
		$insert = new SimpleXMLElement( $insert );
		$target = current($sxe->xpath('//'.$target.''));
		simplexml_insert_after($insert, $target);
		
		return $sxe->asXML();
		}

	function array_to_xml2( $array, $root ) {
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$root.'/>');
		xml_generate( $array, $xml );
					
		return $xml;
		}
		
	function array_to_xml( $array, $root ) {
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$root.'/>');
		xml_generate( $array, $xml );
					
		return $xml->asXML();
		}
	
	function magazine_XMLtoSQL( $array ) {
		$names = array();
		$values = array();
		$size_values = array();
		$ad_data = array();
		
		foreach( $array as $name => $value ) {
			$name = explode( '_', $name );
			$name = trim( strtolower( $name[0] ) );
			
			if( $name != 'value' &&  $name != 'mails' &&  $name != 'partial' ) {
				$names[] = $name;
				if( $name == 'trimsize' ) {
					$value = explode( ' ', $value );
					$value = $value[0].'x'.$value[2];
					}
				$values[] = $value;
				}
			if( $name == 'value' ) {
				$ad_data[] = $value;
				}
			}
		
		$index = array_search( "code", $names );
		$check = sql_get( 'magazines', 'code="'.$values[$index].'"', 'id' );
		if( $check[0][0] == '' && count($array) > 0 ) {
			$index = array_search( "publisher", $names );
			$p_id = sql_get( 'publishers', 'name="'.$values[$index].'"', 'id' );
			if( $p_id[0][0] != '' ) {
				$names[$index] = "publisher_id";
				$values[$index] = $p_id[0][0];
				}
			else {
				$p_n = array( 'name' );
				$p_v = array( $values[$index] );
				$id = sql_add( 'publishers', $p_n, $p_v );
				$names[$index] = "publisher_id";
				$values[$index] = $id;			
				}
			$id = sql_add( 'magazines', $names, $values );
			
			$size_names = array( 'magazine_id', 'size', 'orient', 'cover', 'width', 'height' );
		
			foreach( $ad_data as $data ) {
				$data = explode( ' ', $data );
				if( count( $data ) == 6 ) {
					$size_values = array( $id, '1/1', 'álló', $data[0], $data[2], $data[4] );
					}
				else {
					$size_values = array( $id, $data[0], substr( $data[1], 0, -1 ), substr( $data[2], 0, -1 ), $data[3], $data[5] );
					}
			
				sql_add( 'ad_sizes', $size_names, $size_values );	
				}
			}
		}
	
	function get_xml_datas( $xml, $node, $indexer = 0 ) {
		$txt = array();
		$result = $xml->xpath($node);
		if( gettype( $result[0] ) == 'object' ) {
			foreach($result[0] as $key) {
			
				//if( $key != '' && $key != '>' ) {
				if( $key != '>' ) {
					if( count( $key ) == 0 ) {
						$txt[$key->getName().'_'.$indexer] = ''.$key.'';
						}
					else {
						$temp = get_xml_datas( $xml, $node.'/'.$key->getName(), $indexer );
						foreach($temp as $key => $value) {
							$txt[$key] = $value;
							}
						}
					$indexer++;
					}
				}
			}
		return $txt;
		}

	function xml_generate($student_info, &$xml_student_info) {
		foreach($student_info as $key => $value) {
			if( strpos( $key, '|' ) ) {
				$key = explode( '|', $key );
				$key = $key[0];
				}
				
    	   	if(is_array($value)) {
				if(!is_numeric($key)) {
        	        $subnode = $xml_student_info->addChild("$key");
            	    xml_generate($value, $subnode);
        	    	}
	           	else {
					$subnode = $xml_student_info->addChild("item$key");
					xml_generate($value, $subnode);
					}
				}
	       	else {
    	       	$xml_student_info->addChild("$key","$value");
				}
			}
		}

?>