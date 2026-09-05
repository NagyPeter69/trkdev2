<?
	set_include_path(__DIR__);
	chdir(__DIR__);
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );

	$archiving = sql_aget( "publications", "status='archiving'", "*" );
	for( $i = 0; $i < count( $archiving ); $i++ ) {
		$hour = 5;
		$started_archiving = sql_aget("action_log", "action='archivingIssue' AND magazine='".$archiving[$i]["magazine_id"]."' AND issue='".$archiving[$i]["code"]."' ORDER BY id DESC LIMIT 1", "*");
		if( !empty( $started_archiving[0]["id"] ) ) {
			$dl = strtotime( "+".$hour." hours", $started_archiving[0]["date"] );
			
			if( $dl < time() ) {
				sql_update( "publications", "status='archive_failed'", "id='".$archiving[$i]["id"]."'" );
				}
			
			}
		}

	$publications = sql_get( 'publications', 'status="active" OR status="current" OR status="created"', '*' );
	for( $i = 0; $i < count( $publications ); $i++ ) {
		$status = $xml_status = '';
		$ads = sql_get( 'ads', 'pub_id="'.$publications[$i][0].'"', '*' );
		$pack = sql_get( 'packages', 'publication_id="'.$publications[$i][0].'"', '*' );

		if( count( $pack ) > 0 ) {
			$status = "current";
			$xml_status = "active";
			}
		elseif( count( $ads ) > 0 ) {
			$status = "active";
			$xml_status = "active";
			}
		else {
			$status = "created";
			$xml_status = "created";
			}	
		$magazine = sql_get( 'magazines', 'id="'.$publications[$i][2].'"', '*' );
		if( $magazine[0][3] != "" && $publications[$i][10] != "" && $publications[$i][0] != "" ) {
			$check = sql_aget( "publications", "id='".$publications[$i][0]."'", "*" );
			if( $check[0]["status"] == "active" OR $check[0]["status"] == "current" OR $check[0]["status"] == "created" ) {
				sql_update( 'publications', 'status="'.$status.'"', 'id="'.$publications[$i][0].'"' );
				changeIssueStatus( $magazine[0][3]."_".$publications[$i][10].".xml", $xml_status, $publications[$i][0] );
				}
			}
		}
	$dir = '../message';

	$dirFiles = '';
	
	for( $i = 0; $i < count( $dirFiles ); $i++ ) {
		$files = load_dir_files( $dir.'/'.$dirFiles[$i], '.xml' );
		sort( $files );

		for( $y = 0; $y < count( $files ); $y++ ) {
			if( is_file( $dir.'/'.$dirFiles[$i].'/'.$files[$y] ) ) {
				echo $event."<br>";
				$xml = simplexml_load_file( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
				$event = (string) $xml->event;
				$jcode = (string) $xml->jobCode;
				$issue = (string) $xml->issue;
				$remark = (string) $xml->remark;
				$description = (string) $xml->description;

				if( $event == 'processed_image' ) {
					$name = nameCalculator( $xml, '../xml/'.PMD.'.xml', '0' );
					$range = searchRange( $xml );
					$filename = $remark;
					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
					$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );

					$names = array( 'package_id', 'event', 'value' );
					if( $packages[0][0] != '' ) {
						$pack_id = $packages[0][0];
						}
					else {
						$pack_id = 0;
						}
				
					$msg_pic = load_dir_files( $dir.'/'.$dirFiles[$i], substr( $files[$y], 0, -4 ) );
					for( $p = 0; $p < count( $msg_pic ); $p++ ) {
						if( strpos( $msg_pic[$p], '.xml' ) === false ) {
							$msg_pic = $msg_pic[$p];
							break;
							}
						}

					if( $pack_id == "" ) {
						$names2 = array( 'publication_id', 'name', 'starting_page', 'directory', 'acquired_name', 'status_changed' );
						$values2 = array( $p_id[0][0], $name, $range, $name, $description, time() );
				
						$pack_id = sql_add( 'packages', $names2, $values2 );
					
						$oldmask = umask(0);
						@mkdir("../packages/".$jcode, 0777);
						@mkdir("../packages/".$jcode."/".$issue, 0777);
						@mkdir("../packages/".$jcode."/".$issue."/".$name, 0777);
						umask($oldmask);						
						}

					if( $pack_id != 0 ) {
						if( gettype( $msg_pic ) == 'string' ) {
							$old = $dir.'/'.$dirFiles[$i].'/'.$msg_pic;
							$new = '../packages/'.$jcode.'/'.$issue.'/'.$packages[0][4].'/processed_images/'.$description.".".substr( $msg_pic, -3 );
					
							if( !is_dir( '../packages/'.$jcode.'/'.$issue.'/'.$packages[0][4].'/processed_images' ) ) {
								$oldmask = umask(0);
								mkdir( '../packages/'.$jcode.'/'.$issue.'/'.$packages[0][4].'/processed_images', 0777 );
								umask($oldmask);
								}
							rename( $old, $new );
							@unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
							}
						}				
					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
					$values = array( '', 'processedImages', $p_id[0][1], $p_id[0][2], $p_id[0][10], $pack_id, time(), '' );
					sql_add( 'system_log', $names, $values );
					}
			
				if( $event == 'archived' ) {
					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );

					if( $p_id[0][0] != '' ) {
						$pack_id = $p_id[0][0];
						}
					else {
						$pack_id = 0;
						}

					if( $pack_id != 0 && $pack_id != "" ) {
						sql_update( 'publications', 'status="archived"', 'id="'.$pack_id.'"' );
						changeIssueStatus( $jcode."_".$issue.".xml", "archived", $pack_id );
						@unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
						
						$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
						$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
						$values = array( '0', 'archiveIssue', $p_id[0][1], $p_id[0][2], $p_id[0][10], '', time(), '' );
						sql_add( 'action_log', $names, $values );
						}
					}
			
				if( $event == 'corrupt_image' ) {
					$name = nameCalculator( $xml );
				
					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
				
					$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );

					$names = array( 'package_id', 'event', 'value' );
					if( $packages[0][0] != '' ) {
						$pack_id = $packages[0][0];
						}
					else {
						$pack_id = 0;
						}
				
					if( $pack_id != 0 ) {
						$filename = $remark;
						$check = sql_get( 'package_info', 'package_id="'.$packages[0][0].'" AND event="corrupt" AND value="'.$filename.'"', '*' );
						if( $check[0][0] == '' ) {
					
							$values = array( $pack_id, 'corrupt', $filename );
							sql_add( 'package_info', $names, $values );
							}

						@unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
						}
					$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
					$values = array( '', 'corruptImages', $p_id[0][1], $p_id[0][2], $p_id[0][10], $pack_id, time(), '' );
					sql_add( 'error_log', $names, $values );
					}

				if( $event == 'lowres_image' ) {
					$name = nameCalculator( $xml );
					$filename = $remark;
					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
				
					$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
				
					$names = array( 'package_id', 'event', 'value' );
					if( $packages[0][0] != '' ) {
						$pack_id = $packages[0][0];
						}
					else {
						$pack_id = 0;
						}
					if( $pack_id != 0 ) {
						$check = sql_get( 'package_info', 'package_id="'.$packages[0][0].'" AND event="lowres" AND value="'.$filename.'"', '*' );
						if( $check[0][0] == '' ) {
					
							$values = array( $pack_id, 'lowres', $filename );
							sql_add( 'package_info', $names, $values );

							$old = $dir.'/'.$dirFiles[$i].'/'.substr( $files[$y], 0, -4 ).'.jpg';
							$new = '../packages/'.$jcode.'/'.$issue.'/'.$name.'/lowres_'.substr( $filename, 0, -4 ).'.jpg';
							@rename( $old, $new );
							}
				
						@unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
						@unlink( $dir.'/'.$dirFiles[$i].'/'.substr( $files[$y], 0, -4 ).'.jpg' );
						
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
						$values = array( '', 'lowresImages', $p_id[0][1], $p_id[0][2], $p_id[0][10], $pack_id, time(), '' );
						sql_add( 'error_log', $names, $values );
						}	
					}

				if( $event == 'no_indd_present' ) {
					$name = nameCalculator( $xml );
				
					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
				
					$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
				
					$names = array( 'package_id', 'event', 'value' );
					if( $packages[0][0] != '' ) {
						$pack_id = $packages[0][0];
						}
					else {
						$pack_id = 0;
						}

					if( $pack_id != 0 ) {	
						$check = sql_get( 'package_info', 'package_id="'.$packages[0][0].'" AND event="missing" AND value="'.$remark[$x].'"', '*' );
						if( $check[0][0] == '' ) {
							$values = array( $pack_id, 'no_indd', 'false' );
							sql_add( 'package_info', $names, $values );
							}
					
						@unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
						}			
					}
			
				if( $event == 'missing_images' ) {
					$name = nameCalculator( $xml );
				
					$remark = array();
					foreach( $xml as $key => $val ) {
						if( strstr( $key, "remark" ) ) {
							$remark[] = $val;
							}
						}

					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
				
					$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
				
					$names = array( 'package_id', 'event', 'value' );
					if( $packages[0][0] != '' ) {
						$pack_id = $packages[0][0];
						}
					else {
						$pack_id = 0;
						}
			
					if( $pack_id != 0 ) {	
						for( $x = 1; $x < count( $remark ); $x++ ) {
							$check = sql_get( 'package_info', 'package_id="'.$packages[0][0].'" AND event="missing" AND value="'.$remark[$x].'"', '*' );
							if( $check[0][0] == '' ) {
								$values = array( $pack_id, 'missing', $remark[$x] );
								sql_add( 'package_info', $names, $values );
								}
							}
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
						$values = array( '', 'missingImages', $p_id[0][1], $p_id[0][2], $p_id[0][10], $pack_id, time(), '' );
						sql_add( 'error_log', $names, $values );								
						@unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
						}			
					}
			
				if( $event == 'package_sent_back' or $event == 'indd_sent_back' or $event == 'images_sent_back' ) {
					$name = nameCalculator( $xml );
					$found = 0;
					echo $name;
					//die();
					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_code = $p_id[0][3];
				
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
						
					if( $found == 1 ) {
						$names = array( 'status', 'status_changed' );
						$values = array( 1, time() );
					
						$command = '';
						for( $a = 0; $a < count( $names ); $a++ ) {
							$command .= $names[$a].'=\''.$values[$a].'\'';
		
							if( $a < count( $names )-1 ) {
								$command .= ', ';
								}
							}
							
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
						$values = array( '', 'backArticle', $p_id[0][1], $p_id[0][2], $p_id[0][10], $name, time(), '' );
						sql_add( 'action_log', $names, $values );

						sql_update( 'packages', $command, 'id=\''.$target.'\'' );
						@unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
						}
					}

				if( $event == 'new_package_submitted' or $event == 'incoming_package' or $event == 'new_indd_submitted' ) {
					$name = nameCalculator( $xml );	
					$found = 0;
					$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
					$p_code = $p_id[0][3];
				
					if( $issue == '' ) {
						$xml2 = simplexml_load_file( '../xml/'.PMD.'.xml' );
						$xpath = $xml2->xpath('/Publications');
						foreach($xpath as $temp) {
							for( $i = 0; $i < count( $temp->Item ); $i++ ) {
								if( $temp->Item[$i]->Code == $p_id[0][3] )
									break;
								}
							}
						
						$current = (string) $xml->issue;
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
					$start = searchRange( $xml );
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
					@unlink( $dir.'/'.$dirFiles[$i].'/'.$files[$y] );
					}	
				}
			}		
		}
	
?>