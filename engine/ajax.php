<?PHP
	header('Content-Type: text/html; charset=utf-8');

	include_once( 'connect.php' );
	include_once( 'engine.php' );
	
	if( $_GET['op'] == 'loadFTP' ) {			
		$xml2 = simplexml_load_file( '../client/xml/Output_Details.xml' );						
		$xpath2 = $xml2->$_GET['pub']->Outward->$_GET['type']->children();
		
		$result = "<select name='".$_GET['name']."'>";
		foreach( $xpath2 as $temp2 ) {
			if( $temp2->getName() != 'Targets' ) {
				$result .= "<option ";
				if( $_GET['value'] == $temp2 ) $result .= "selected ";
				$result .= "value='".$temp2->getName()."'>".$temp2->getName()."</option>";
				}
			}
		$result .= "";
		}
	
	if( $_GET['op'] == 'edit_magazine' ) {
		$magazine = sql_get( 'magazines', 'id="'.$_GET['id'].'"', '*' );
		
		$result = array( $magazine[0][2], $magazine[0][3], $magazine[0][4], $magazine[0][5], $magazine[0][6], $magazine[0][0], $magazine[0][1] );
		}
	
	else if( $_GET['op'] == 'refresh_jobs' ) {
		$names = '';
		$lines = '';
		$jobs = sql_get( 'jobs', '1 ORDER BY `id` ASC', '*' );
		for( $i = 0; $i < count( $jobs ); $i++ ) {
			$names .= "<tr>";
				if( fmod( $i, 2 ) == 0 ) { $class = 'one'; }
				else { $class = 'two'; }
				
				$names .= "<td id='job_".$jobs[$i][0]."' class='".$class." left bottom job_line' style='padding-left: 10px; padding-right: 10px;'>".$jobs[$i][2]."</td>";
			$names .= "</tr>";
			}	

		for( $i = 0; $i < count( $jobs ); $i++ ) {
			$lines .= "<tr>";
			$lines .=  generate_job_line( $jobs[$i], $i );
			$lines .=  "</tr>";
			}
		
		$result = array( $names, $lines, $jobs );
		}

	print json_encode( $result );
	
?>
