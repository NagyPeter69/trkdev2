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
	// See client/plugins/pubsApply.php's 2026-09-05 fix - none of this
	// file's sub== handlers checked authentication before running.
	// Same fix: one gate before any sub is dispatched.
	if( empty( $user[0][0] ) ) {
		print json_encode( array( array( "Unauthorized" ) ) );
		exit;
		}
	
	if( $_GET["sub"] == "modify" ) {
		$error = array();

		if( isset( $_POST['m_address_url'] ) )
			$_POST['m_address'] = $_POST['m_address_url'];
		else 
			$_POST['m_address'] = $_POST['m_address_1'].'.'.$_POST['m_address_2'].'.'.$_POST['m_address_3'].'.'.$_POST['m_address_4'];

		$check = array( 'm_address', 'm_login', 'm_pass' );
		for( $i = 0; $i < count( $check ); $i++ ) {
			if( $_POST[ $check[$i] ] == "" ) {
				$error[] = $check[$i];
				}
			}

		if( count( $error ) == 0 ) {
			$xml = simplexml_load_file( '../xml/Output_Details.xml' );
			$pub = sql_get( 'publishers', 'id="'.$_POST["u_publisher"].'"', 'name' );
			$pub[0][0] = str_replace( " ", "", $pub[0][0] );
				
			$ok = "1";	
			//$ok = checkFTP( $_POST['m_address'], $_POST['m_port'], $_POST['m_login'], $_POST['m_pass'] );
			if( $ok == '0' ) {
				$error = array( 'm_address', 'm_login', 'm_pass', 'm_port' );
				}
		
			else {
				$error = "";
				if( $ok == 'sftp' ) {
					$_POST['arch_port'] = 22;
					}
				
				$nodes = array( 'Address', 'Port', 'Passive', 'Binary', 'Login', 'Pass', 'Path' );
				
				if( $_POST['ftp_mod_v'] == "archive" ) {
					foreach( $nodes as $node ) {
						if( $node == 'Pass') {
							$xml->{$pub[0][0]}->Outward->Archive->{$node} = encrypt_( $_POST['m_'.strtolower($node)] );
							}
						else {
							$xml->{$pub[0][0]}->Outward->Archive->{$node} = $_POST['m_'.strtolower($node)];
							}
						}					
					}
					
				else {
					$path = explode( "_", $_POST['ftp_mod_v'] );
					foreach( $nodes as $node ) {
						if( $node == 'Pass') {
							$xml->{$pub[0][0]}->Outward->$path[1]->$path[0]->{$node} = encrypt_( $_POST['m_'.strtolower($node)] );
							}
						else {
							$xml->{$pub[0][0]}->Outward->$path[1]->$path[0]->{$node} = $_POST['m_'.strtolower($node)];
							}
						}
					}
				$dom = new DOMDocument();
				$dom->preserveWhiteSpace = false;
				$dom->loadXML($xml->asXML());
				$dom->formatOutput = true;
	
				file_put_contents( '../xml/Output_Details.xml', $dom->saveXML() );
				$array = array(
					"event" => "xml_data",
					);
						
				$file = array( 
					"name" => "Output_Details.xml",
					"path" => "xml",
					);
				$response = SwitchSend_TESZT( $array, $file );				

				$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
				$values = array( $_SESSION['intra_user'], 'modFTP', $_GET['pub'], '', '', $path[0], time() );
				sql_add( 'system_log', $names, $values );
				}
			}
		$result = array( $error, $_POST );	
		}
	
	if( $_GET["sub"] == "delete" ) {
		$node = explode( "_", $_POST['ftp_del_v'] );
		$xml = simplexml_load_file( '../xml/Output_Details.xml' );
		$pub = sql_get( 'publishers', 'id="'.$_POST["u_publisher"].'"', 'name' );
		$pub[0][0] = str_replace( " ", "", $pub[0][0] );
		
		$xpath = $xml->{$pub[0][0]}->Outward->{$node[1]}->{$node[0]};
		$dom=dom_import_simplexml($xml->{$pub[0][0]}->Outward->{$node[1]}->{$node[0]});
		$dom->preserveWhiteSpace = false;
		$dom->parentNode->removeChild($dom);
		
		if( $v == 'Content' ) {
			$first = '';
			$xpath = $xml->{$pub[0][0]}->Outward->Content->children();
			foreach( $xpath as $temp ) {
				if( $temp->getName() != 'Targets' ) {
					$first = $temp->getName();
					break;
					}
				}
			}
			
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;

		file_put_contents( '../xml/Output_Details.xml', $dom->saveXML() );
		$up_to = 'C_Database';
		$array = array(
			"event" => "xml_data",
			);
						
		$file = array( 
			"name" => "Output_Details.xml",
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );		

		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
		$values = array( $_SESSION['intra_user'], 'delFTP', $_GET['pub'], '', '', $node[0], time() );
		sql_add( 'system_log', $names, $values );
		
		$error = "";	
		$result = array( $error );	
		}
	
	if( $_GET["sub"] == "create" ) {
		$error = array();
		$check = array( 'ftp_name', 'ftp_address_url', 'ftp_login', 'ftp_pass' );
		for( $i = 0; $i < count( $check ); $i++ ) {
			if( $_POST[ $check[$i] ] == "" ) {
				$error[] = $check[$i];
				}
			}
		
		if( count( $error ) == 0 ) {
			$host = $_POST['ftp_address_url'];
			$ok = checkFTP( $host, $_POST['ftp_port'], $_POST['ftp_login'], $_POST['ftp_pass'] );
			if( $ok == '0' ) {
				$error = array( 'ftp_address_url', 'ftp_login', 'ftp_pass', 'ftp_port' );
				}
			else {
				$publisher = sql_get( 'publishers', 'id="'.$_POST['pubid'].'"', 'name');
				$publisher[0][0] = str_replace( " ", "", $publisher[0][0] );
				$xml = simplexml_load_file( '../xml/Output_Details.xml' );
				$path = '/FTPdata/'.$publisher[0][0];
				$xpath = $xml->xpath( $path );
				if( count( $xpath ) == 0 ) {
					$pub = $xml->addChild( $publisher[0][0] );
					$in = $pub->addChild( 'Inward' );
					$in->addChild( 'Address', '' );
					$in->addChild( 'Port', '' );
					$in->addChild( 'Passive', '' );
					$in->addChild( 'Binary', '' );
					$in->addChild( 'Login', '' );
					$in->addChild( 'Pass', '' );
					$in->addChild( 'Path', '' );
					$out = $pub->addChild( 'Outward' );
					$content = $out->addChild( 'Content' );
					$final = $out->addChild( 'Final' );
					$out->addChild( 'Softproof' );
					$archive = $out->addChild( 'Archive' );
					$archive->addChild( 'Address', '' );
					$archive->addChild( 'Port', '' );
					$archive->addChild( 'Passive', '' );
					$archive->addChild( 'Binary', '' );
					$archive->addChild( 'Login', '' );
					$archive->addChild( 'Pass', '' );
					$archive->addChild( 'Path', '' );
					}
					
				$path = '/FTPdata/'.$publisher[0][0].'/Outward/'.$_POST['ftp_chose'];
				$xpath = $xml->xpath( $path );
				$check = $xml->xpath( $path.'/'.$_POST['ftp_name']  );
				
				error_log( $path.'/'.$_POST['ftp_name'] );
					
				if( count($check) == 0 ) {
					$encrypt = encrypt_( $_POST['ftp_pass'] );
					$item = $xml->{$publisher[0][0]}->Outward->{$_POST['ftp_chose']}->addChild( (string) $_POST['ftp_name'] );
					$item->addChild( 'Address', (string) $host );
					$item->addChild( 'Port', $_POST['ftp_port'] );
					$item->addChild( 'Passive', $_POST['ftp_passive'] );
					$item->addChild( 'Binary', $_POST['ftp_binary'] );
					$item->addChild( 'Login', $_POST['ftp_login'] );
					$item->addChild( 'Pass', "".$encrypt."" );
					$item->addChild( 'Path', $_POST['ftp_path'] );
				
				
					$dom = new DOMDocument();
					$dom->preserveWhiteSpace = false;
					$dom->loadXML($xml->asXML());
					$dom->formatOutput = true;
		
					file_put_contents( '../xml/Output_Details.xml', $dom->saveXML() );
					file_put_contents( '../xml/Output_Details_NT.xml', $dom->saveXML() );

					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
					$values = array( $_SESSION['intra_user'], 'addFTP', $publisher[0][0], '', '', $_POST['ftp_name'], time() );
					sql_add( 'system_log', $names, $values );

					$array = array(
						"event" => "xml_data",
						);
						
					$file = array( 
						"name" => "Output_Details.xml",
						"path" => "xml",
						);
					$response = SwitchSend_TESZT( $array, $file );
					}									
				else {
					$error = array( 'ftp_name' );
					}
				}
			}
		
		$result = array( $error );
		}
	
	print json_encode( $result );
	
?>