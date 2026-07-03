<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');
	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	
	include_once('../lang/en.php');
	
	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}

	if( $_GET["sub"] == "ftp" ) {
		$error = array();

		$check = array( 'm_address_url', 'm_login', 'm_password', 'm_port' );
		for( $i = 0; $i < count( $check ); $i++ ) {
			if( $_POST[ $check[$i] ] == "" ) {
				$error[] = $check[$i];
				}
			}
		
		if( count( $error ) == 0 ) {
			$ok = "1";
			if( $ok == '0' ) {
				$error = array( 'm_address_url', 'm_login', 'm_password', 'm_port' );
				}
		
			else {
				$error = "";
				$_POST['m_address'] = $_POST['m_address_url'];
				$xml = simplexml_load_file( '../xml/job_data.xml' );
				$xpath = $xml->xpath('/Job');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $_POST["code"] && $temp->Item[$i]->Publisher == $_POST["publisher"] )
							break;
						}
					}

				$nodes = array( 'Address', 'Port', 'Binary', 'Passive', 'Login', 'Password',  'Path' );
				foreach( $nodes as $node ) {
					if( $node == 'Password') {
						$xml->Item[$i]->RemoteStorage->$node = encrypt_( $_POST['m_'.strtolower($node)] );
						}
					else {
						$xml->Item[$i]->RemoteStorage->$node = $_POST['m_'.strtolower($node)];
						}
					}
				$dom = new DOMDocument();
				$dom->preserveWhiteSpace = false;
				$dom->loadXML($xml->asXML());
				$dom->formatOutput = true;
	
				file_put_contents( '../xml/job_data.xml', $dom->saveXML() );					
				}
			}
			
		$result = array( $error );
		}
	
	if( $_GET["sub"] == "settings" ) {
		$error = array();
		
		if( isset( $_POST["TrimSize_x"] ) && $_POST["TrimSize_x"] == "" ) $error[] = "TrimSize_x";
		if( isset( $_POST["TrimSize_y"] ) && $_POST["TrimSize_y"] == "" ) $error[] = "TrimSize_y";
		
		if( count( $error ) == 0 ) {
			$_POST['Mails'] = explode( "\r\n", $_POST['Mails'] );
			$_POST['Mails'] = trim( implode( ";", $_POST['Mails'] ) );
			$_POST['old_code'] = $_POST['code'];

			changeJOBXmlDatabase( 'modify', $_POST, '../xml/job_data.xml' );
			
			$job = sql_get( 'jobs', 'code="'.$_POST['Code'].'"', 'id, publisher' );	
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
			$values = array( $_SESSION['intra_user'], 'modJob', $job[0][1], $job[0][0], '', '', time() );
			//sql_add( 'jobs_log', $names, $values );
			}	
		
		$result = array( $error );
		}
		
	print json_encode( $result );
?>		