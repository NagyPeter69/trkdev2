<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');
	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	
	include_once('../lang/en.php');

	if( $_GET["sub"] == "export" ) {
		$error = array();
		
		
		
		if( $_POST['begin_date'] == "" ) $error[] = "begin_date";
		if( $_POST['end_date'] == "" ) $error[] = "end_date";
		
		if( count( $error ) == 0 ) {
			$begin = strtotime( $_POST['begin_date'] );
			$end = strtotime( $_POST['end_date'] );
			
			if( $end < $begin ) $error[] = "end_date";
			if( count( $error ) == 0 ) {
				$user = sql_get( "accounts", "id='".$_SESSION['intra_user']."'", "*" );
				
				$temp = explode( "|", $_POST['info'] );
				$pub = $temp[1];
				$issues = $temp[2];
				$action_log["action_log"] = explode( ",", $temp[0] );
				
				if( $pub == "all" ) {
					$magazines = explode( ",", $user[0][21] );
					}
				else {
					$magazines = array( $pub );
					}

				$issue = "";
				if( $issues != "all" ) {
					$issue = " AND issue='".$issues."'";
					}

				$command = $command2 = array();
				for( $i = 0; $i < count( $action_log["action_log"] ); $i++ ) {
					$command[] = "`action`='".$action_log["action_log"][$i]."'";
					}

				for( $i = 0; $i < count( $magazines ); $i++ ) {
					$command2[] = "`magazine`='".$magazines[$i]."'";
					}

				$command = implode( " OR ", $command );
				$command2 = implode( " OR ", $command2 );

				$command = "(".$command.") AND (".$command2.")";
				
				$log = sql_aget( "action_log", $command.$issue." AND `date`>=".$begin." AND `date`<=".$end." ORDER BY `date`  DESC", "*" );
				$filename = "tracker_syslog_".date( "ymd\THi", $begin )."-".date( "ymd\THi", $end ).".txt";
				$myfile = fopen( $filename, "w");
				for( $i = 0; $i < count( $log ); $i++ ) {
					$row = "";
					$row .= date( "Y-m-d H:i", $log[$i]["date"] )."\t";
					$row .= $lang["liveLog"][$log[$i]["action"]]."\t";
					
					if( $log[$i]["magazine"] != "" ) {
						if( is_numeric( $log[$i]["magazine"] ) ) {
							$mag = sql_aget( "magazines", "id='".$log[$i]["magazine"]."'", "name" );
							$row .= $mag[0]["name"]." ".$log[$i]["issue"]."\t";
							}
						else {
							$row .= $log[$i]["magazine"]." ".$log[$i]["issue"]."\t";
							}
						}
					else {
						$row .= "Unknown\t";
						}
					
					if( $log[$i]["user"] != "" ) {
						if( is_numeric( $log[$i]["user"] ) ) {
							$user = sql_aget( "accounts", "id='".$log[$i]["user"]."'", "name, full_name" );
							$row .= ( $user[0]["full_name"] != "" ? $user[0]["full_name"] : $user[0]["name"] )."\t";
							}
						else {
							$row .= $log[$i]["user"]."\t";
							}
						}
					else {
						$row .= "\t";
						}
					
					$row .= $log[$i]["target"]."\t";
					$row .= $log[$i]["status"]."\t";
					$row .= $log[$i]["info"]."\t\n";
					
					fwrite($myfile, $row);
					}
				fclose($myfile);
								
				$error = array( "doFunction", "download", $filename );
				}
			}
			
		$result = array( $error );
		}

	
	print json_encode( $result );
	
?>