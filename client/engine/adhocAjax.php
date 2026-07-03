<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	
	include_once('../lang/en.php');

	if( $_GET['op'] == 'getHistory' ) {
		$ret = '';
		$jobs = CreateJobCode( $_SESSION['intra_user'] );
		$jobs = explode( "_", $jobs );
		
		$jobs = sql_get( 'ad_hoc', 'gen_name LIKE "'.$jobs[0].'%" GROUP BY  `gen_name` ORDER BY `date` DESC', '*' );	
		for( $i = 0; $i < count( $jobs ); $i++ ) {		
			$s = explode( "|", $jobs[$i][3] );
			foreach( $s as $key ) {
				$temp = explode( ':', $key );
				$settings[$temp[0]] = $temp[1];
				}
			
			$check = sql_get( 'ad_hoc' ,'gen_name="'.$jobs[$i][4].'"', '*' );
			if( count( $check ) > 1 ) { $file = '<span style="cursor:pointer;" onclick="toggle_div(\'detail_'.$i.'\')"><i>'.$lang["adhoc"]["more"].'</i></span>'; }
			else { $file = $settings['Original_filename']; }
			
			$status_color = "";
			switch( $jobs[$i][10] ) {
				case 0:
					$status_color = "#E8E848";
					break;
				case 1:
					break;
					$status_color = "#292377";
				case 2:
					$status_color = "#399C58";
					break;
				}
			
			$ret .= "<tr>";
				$ret .= "<td>";
					$ret .= "<div class='jline' style='font-size: 13px; position:relative;'>";
						$ret .= "<div style='border-left: 9px solid ".$status_color."; width: 98px; float:left; padding-left: 4px; text-align: left; line-height: 30px; height: 30px;'>".$jobs[$i][4]."</div>";
						$ret .= "<div style='width: 70px; float:left; padding-left: 25px; text-align: left; line-height: 30px; height: 30px;'>".$lang["adhoc"][ $jobs[$i][2] ]."</div>";
						$ret .= "<div style='float:left; padding-left: 17px; text-align: left; line-height: 30px; height: 30px;'>".$file."</div>";
						$ret .= "<div class='date' style='float:right; padding-right: 25px; text-align: left; line-height: 30px; height: 30px;'>".$jobs[$i][5]."</div>";
						$ret .= "<div style='clear:both;'></div>";
					$ret .= "</div>";	
						if( count( $check ) > 1 ) {
							$ret .= "<div id='detail_".$i."' class='details' style='border-left: 9px solid ".$status_color."; text-align: left; display:none; background: #FFF !important; width: 100%;'>";
								$ret .= "<div style='text-align: left; margin-left: 210px;height: 1px;'>&nbsp;</div>";
								$files = sql_get( 'ad_hoc' ,'gen_name="'.$jobs[$i][4].'"', '*' );
								for( $y = 0; $y < count($files); $y++ ) {
									$se = explode( "|", $files[$y][3] );
									foreach( $se as $key ) {
										$temp = explode( ':', $key );
										$setting[$temp[0]] = $temp[1];
										}
										
									$ret .= "<div style='text-align: left; margin-left: 213px; font-size: 13px;'>".$setting['Original_filename']."</div>";
									}
							$ret .= "</div>";
							}
						
					
				$ret .= "</td>";
			$ret .= "</tr>";
			}
			
		$result = $ret;
		}

print json_encode( $result );
	
?>