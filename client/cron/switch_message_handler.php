<?PHP
	set_include_path(__DIR__);
	chdir(__DIR__);
	header('Content-Type: text/html; charset=utf-8');
	require( '../../engine/connect.php' );
	require( '../../engine/engine.php' );
	require( '../../engine/xml_handler.php' );
	
	$dir = '../message';
	$dirFiles = load_dir_files( $dir, '.xml' );
	sort( $dirFiles );
	for( $i = 0; $i < count( $dirFiles ); $i++ ) {
		if( is_file( $dir.'/'.$dirFiles[$i] ) ) {
			$xml = simplexml_load_file( $dir.'/'.$dirFiles[$i] );
			$event = (string) $xml->event;
			if( $event == 'delete_results' ) {
				$res = (string) $xml->results->rename;
				if( $res == "successful" ) {
					include( "__cleaner__.php" );
					unlink( $dir.'/'.$dirFiles[$i] );
					}
				}
			
			if( $event == "delete_publication_results" ) {
				$code = (string) $xml->jobCode;
				$user = (string) $xml->user;
				$result = (string) $xml->results->delete;		
				
				echo $code.", ".$user.", ".$result."<br>";
				if( $result == "successful" ) {
					$mag = sql_get( 'magazines', 'code="'.$code.'"', '*' );
					$pubs = sql_get( 'publications', 'magazine_id="'.$mag[0][0].'"', '*' );
					$magazine = sql_get( 'magazines', 'id="'.$mag[0][0].'"', '*' );
					$publisher = sql_get( 'publishers', 'id="'.$mag[0][1].'"', '*' );	
					
					echo $mag[0][0];
					if( $mag[0][0] != "" ) {
						$users = sql_aget( "accounts", "`showMagazines` LIKE '%".$mag[0][0]."%'", "id, showMagazines" );
						for( $x = 0; $x < count( $users ); $x++ ) {
							$temp = explode( ",", $users[$x]["showMagazines"] );
							$index = array_search( $mag[0][0], $temp );
							if( $index !== FALSE ) array_splice($temp, $index, 1);
							$temp = implode( ",", $temp );
							sql_update( "accounts", "showMagazines='".$temp."'", "id='".$users[$x]["id"]."'" );
							}					
					
						changeXmlDatabase( 'delete', array( "old_code" => $mag[0][3] ), '../xml/'.PMD.'.xml' );	
						for( $p = 0; $p < count( $pubs ); $p++ ) {
							$issue = sql_get( 'publications', 'id="'.$pubs[$p][0].'"', '*' );
							$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
		
							sql_delete( 'ads', 'pub_id="'.$issue[0][0].'"' );
							sql_delete( 'parts', 'pub_id="'.$issue[0][0].'"' );
							$packs = sql_get( 'packages', 'publication_id="'.$issue[0][0].'"', '*' );
							for( $y = 0; $y < count( $packs ); $y++ ) {
								sql_delete( 'package_info', 'package_id="'.$packs[$y][0].'"' );
								}
							sql_delete( 'packages', 'publication_id="'.$issue[0][0].'"' );
							if( is_dir( '../packages/'.$magazine[0][3].'/'.$issue[0][10] ) ) {
								delTree('../packages/'.$magazine[0][3].'/'.$issue[0][10] );
								}
							
							
		
							$counter = get_counter('..');
							$saveTo = 'C_Hotfolders/messages/message_'.$counter;
							inc_counter('..');

							$sftp = ftp_conn("../");
		
							$name = "C_Database/".$magazine[0][3]."_".$issue[0][10].".xml";
							unlink( "../xml/".$magazine[0][3]."_".$issue[0][10].".xml" );
							$sftp->delete( $name );
		
							sql_delete( 'publications', 'id="'.$pubs[$p][0].'"' );
							}
							
						if( is_dir( '../packages/'.$magazine[0][3] ) )
							delTree('../packages/'.$magazine[0][3] );	

						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
						$values = array( $user, 'deleteMagazine', $mag[0][2], $mag[0][2], '', '', time(), '' );
						sql_add( 'action_log', $names, $values );
		
						sql_delete( 'magazines', 'id="'.$mag[0][0].'"' );
						unlink( $dir.'/'.$dirFiles[$i] );
						}
					}
				
				if( $result == "failed" ) {
					$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
					if( $magazine[0]["id"] != "") {
						sql_update( "magazines", "removing='0'", "id='".$magazine[0]["id"]."'" );
						unlink( $dir.'/'.$dirFiles[$i] );
						}
					}	
				}
			
			if( $event == "delete_issue_results" ) {
				$code = (string) $xml->jobCode;
				$issue = (string) $xml->issue;
				$user = (string) $xml->user;
				$result = (string) $xml->results->delete;
				
				echo $code.", ".$issue.", ".$user.", ".$result."<br>";
				if( $result == "successful" ) {
					$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
					$p_id = $issue = sql_get( "publications", "magazine_id='".$magazine[0]["id"]."' AND code='".$issue."'", "*" );
					$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
					$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
					
					if( $issue[0][0] != "" ) {
						sql_delete( 'ads', 'pub_id="'.$issue[0][0].'"' );
						sql_delete( 'parts', 'pub_id="'.$issue[0][0].'"' );
						$packs = sql_get( 'packages', 'publication_id="'.$issue[0][0].'"', '*' );
						for( $y = 0; $y < count( $packs ); $y++ ) {
							sql_delete( 'package_info', 'package_id="'.$packs[$y][0].'"' );
							}
						sql_delete( 'packages', 'publication_id="'.$issue[0][0].'"' );
						if( is_dir( '../packages/'.$magazine[0][3].'/'.$issue[0][10] ) ) {
							delTree('../packages/'.$magazine[0][3].'/'.$issue[0][10] );
							}
						$pages = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'"', '*' );
						for( $y = 0; $y < count( $pages ); $y++ ) {
							sql_delete( 'pageinfo', 'id="'.$pages[$y][0].'"' );
							}
						$comments = sql_get( 'comments', 'pub_id="'.$issue[0][0].'"', '*' );
						for( $y = 0; $y < count( $comments ); $y++ ) {
							sql_delete( 'comments', 'id="'.$comments[$y][0].'"' );
							}
				
						$sftp = ftp_conn("../");
		
						$name = "C_Database/".$magazine[0][3]."_".$issue[0][10].".xml";
						
						unlink( "../xml/".$magazine[0][3]."_".$issue[0][10].".xml" );
						$sftp->delete( $name );							
						
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
						$values = array( $user, 'deleteIssue', $p_id[0][1], $magazine[0][0],  $p_id[0][10], '', time(), '' );
						sql_add( 'action_log', $names, $values );
		
						sql_delete( 'publications', 'id="'.$p_id[0][0].'"' );
						unlink( $dir.'/'.$dirFiles[$i] );
						}		
					}
				
				if( $result == "failed" ) {
					$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
					$pub = sql_aget( "publications", "magazine_id='".$magazine[0]["id"]."' AND code='".$issue."'", "id" );
					if( $pub[0]["id"] != "") {
						sql_update( "publications", "removing='0'", "id='".$pub[0]["id"]."'" );
						unlink( $dir.'/'.$dirFiles[$i] );
						}
					}
				}
				
			if( $event == 'delete_ad_results' ) {
				unlink( $dir.'/'.$dirFiles[$i] );
				}

			if( $event == 'archive_successful' ) {
				$publisher = (string) $xml->client;
				$code = (string) $xml->jobCode;
				$name = (string) $xml->description;
				$issue = (string) $xml->issue;		
				
				$publisher = sql_get( 'publishers', 'name="'.$publisher.'"', '*' );
				$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
				
				$pub = sql_get( 'publications', 'publisher_id="'.$publisher[0][0].'" AND magazine_id="'.$magazine[0][0].'" AND code="'.$issue.'"', '*' );
				if( $pub[0][0] != "" ) {
					sql_update( 'publications', 'status="archived"', 'id="'. $pub[0][0].'"' );
					$result = changeIssueStatus( $magazine[0][3]."_".$issue.".xml", "archived", $pub[0][0] );
					
					if( $result ) {
						unlink( $dir.'/'.$dirFiles[$i] );
					
						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
						$values = array( '0', 'archiveIssue', $pub[0][1], $pub[0][2], $pub[0][10], '', time(), '' );
						sql_add( 'action_log', $names, $values );
						}		
					}				
				}
			
			if( $event == 'ad_check_results' ) {
				$publisher = (string) $xml->client;
				$code = (string) $xml->jobCode;
				$name = (string) $xml->description;
				$issue = (string) $xml->issue;
				$file_counter = (string) $xml->remark;
				$errors['lowres'] = (string) $xml->results[0]->lowres;
				
				$file_counter = 2;
			
				$publisher = sql_get( 'publishers', 'name="'.$publisher.'"', '*' );
				$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
				$pub = sql_get( 'publications', 'code="'.$issue.'" AND magazine_id="'.$magazine[0][0].'"', '*' );
				$ad = sql_get( 'ads', 'publisher="'.$publisher[0][0].'" AND pub_id="'.$pub[0][0].'" AND name="'.$name.'"', '*' );
				if( $ad[0][0] != '' ) {
					$dir2 = '../advertisements';
					$checker = strtoupper( $ad[0][2] ).'_'.$code.'_'.$issue.'_'.$type;
				
					$ad_files = load_dir_files( $dir2, $checker );
					for( $y = 0; $y < count( $ad_files ); $y++ ) {
						$secu = explode( '_', $ad_files[$y] );
						if( strtoupper( $ad[0][2] ) == strtoupper( $secu[0] ) ) {
							//@unlink( $dir2.'/'.$ad_files[$y] );
							}
						}
					$file_name = substr( $dirFiles[$i], 0, -4 );
					$from = $dir;
					$to = '../advertisements';
					$files = load_dir_files( $dir, $file_name );

					$tempSize = filesize( $dir."/".$dirFiles[$i] );		
					sleep( 5 );
					clearstatcache();
					$tempSize2 = filesize( $dir."/".$dirFiles[$i] );

					if( count($files) == $file_counter && $tempSize == $tempSize2 ) {			
						$pdf = new dynapdf();

						$xml2 = get_xml_datas( $xml, '//eventComm' );
						$lowres = array();
						foreach( $xml2 as $node => $value ) {
							if( strpos( $node, "lowres_area" ) !== false ) {
								$lowres[] = $value;
								}
							}
			
						include('../engine/config.inc.php');
						error_log( "fájl: ".$file_name );
						$pdf->CreateNewPDF( $file_name.'_check.pdf' );
			
						$pdf->InitColorManagement( NULL, NULL , 1 );
			
						$pdf->OpenImportFile( "../message/".$file_name.".pdf", dynapdf::ptOpen, NULL );
						$pdf->ImportPDFFile( 1, 1.0, 1.0 );
						$pdf->CloseImportfile();

						$sizes = getBBox( "../message/".$file_name.".pdf", "", "cropbox" );
						$sizes["Width"] = pixel_( ( $sizes["Right"] - $sizes["Left"] ), 100 );
						$sizes["Height"] = pixel_( ( $sizes["Top"] - $sizes["Bottom"] ), 100 );

						$pdf->EditPage(1);
							$width = $pdf->GetPageWidth();
							$height = $pdf->GetPageHeight();
							
							$box = $pdf->GetBBox( dynapdf::pbBleedBox );
							$tbox = $pdf->GetBBox( dynapdf::pbTrimBox );

							$tbox['Width'] = $tbox['Right']-$tbox['Left'];
							$tbox['Height'] = $tbox['Top']-$tbox['Bottom'];
							$tbox['StartX'] = ( $tbox['Left']-$box['Left'] )+$box['Left'];
							$tbox['StartY'] = ( $tbox['Bottom']-$box['Bottom'] )+$box['Bottom'];
							$sbox = array( 
										"StartX"=> $tbox['StartX']+10,
										"StartY"=> $tbox['StartY']+10,
										"Width"=> $tbox['Width']-20,
										"Height"=> $tbox['Height']-20
										);
							
							$pdf->SetLineWidth( 1 );			
							$g = $pdf->RGB( 0, 200, 0 );
							$r = $pdf->RGB( 200, 0, 0 );
							$b = $pdf->RGB( 0, 0, 255 );
							$pdf->SetStrokeColor( dynapdf::PDF_WHITE );
							$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
							$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );
				
							$pdf->SetLineDashPattern( "6", 6 );
							$pdf->SetStrokeColor( $g );
							$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
							
							$pdf->SetStrokeColor( $r );
							$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );

						$sizes = getBBox( "../message/".$file_name.".pdf", "", "cropbox" );
						$sizes["Width"] = pixel_( ( $sizes["Right"] - $sizes["Left"] ), 100 );
						$sizes["Height"] = pixel_( ( $sizes["Top"] - $sizes["Bottom"] ), 100 );	

							$pdf->SetLineDashPattern( "0", 0 );
							$pdf->SetLineWidth( 2 );
							$pdf->SetStrokeColor( $b );
							$pdf->Rectangle( $sizes["Left"], $sizes["Bottom"], ( $sizes["Right"] - $sizes["Left"] ), ( $sizes["Top"] - $sizes["Bottom"] ), dynapdf::fmStroke );
							
							echo "Rectangle( ".$sizes["Left"].", ".$sizes["Bottom"].", ".( $sizes["Right"] - $sizes["Left"] ).", ".( $sizes["Top"] - $sizes["Bottom"] )."";
							
						$pdf->EndPage();
						$pdf->AddOutputIntent( "../engine/ISOcoated_v2_eci.icc" );
						$pdf->CloseFile();

						$terminalPath = "/var/www/intra/client";

						
						$from_ = $terminalPath."/cron/".$file_name."_check.pdf";
						$to_ = $terminalPath."/cron/".$file_name."_check.jpg";


						
						echo implode( " | ", $sizes );
						$command = './r3 -binary -mode:RENDER -left:0 -right:'.( $sizes["Right"] - $sizes["Left"] ).' -bottom:0 -top:'.( $sizes["Top"] - $sizes["Bottom"] ).' -width:'.$sizes["Width"].'  -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:ISOcoated_v2_eci.icc '.$from_.' $@ >'.$to_.' 2>&1';
						echo "<br>".$command;
						
						$command = shell_exec('
							cd /var/www/intra/client/engine/r3 2>&1;
							'.$command.';
							');	
						echo "<br>".$command."<br>";
									
						if( $errors['lowres'] == 'true' ) {						
							unset( $pdf );
			
							$pdf = new dynapdf();
							$pdf->CreateNewPDF( null );
							include('../engine/config.inc.php');

							$pdf->SetBBox( dynapdf::pbMediaBox, 0, 0, $width, $height );										
							$pdf->InitColorManagement( NULL, NULL , 1 );
							$pdf->Append();
								$gs = $pdf->CreateExtGState(array('FillAlpha' => 0.5));
								$pdf->SetExtGState($gs);
				
								$pdf->InsertImageEx(0, 0, $width, $height, $file_name.'_check.jpg', 1);
				
								$gs = $pdf->CreateExtGState(array('FillAlpha' => 1));
								$pdf->SetExtGState($gs);
				
								$gs = $pdf->CreateExtGState(array('FillAlpha' => 1));
								$pdf->SetExtGState($gs);
								
								$count = count($lowres);
								$r_ = 25/$count;
								$g_ = 255/$count;
								$b_ = 255/$count;
				
								$pdf->SetLineDashPattern( "0", 0 );
								for( $x=0; $x < count($lowres); $x++ ) {
									$data = explode( "_", $lowres[$x] );
									$r = $pdf->RGB( 230+($r_*($x+1) ), $g_*($x), $b_*($x) );
									$pdf->SetFillColor( $r );
									$pdf->Rectangle( $data[0], $data[1], ($data[2]-$data[0]), ($data[3]-$data[1]), dynapdf::fmFill );
									}
								$pdf->SetLineWidth( 1 );			
								$g = $pdf->RGB( 0, 200, 0 );
								$r = $pdf->RGB( 200, 0, 0 );
								$pdf->SetStrokeColor( dynapdf::PDF_WHITE );
								$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
								$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );
				
								$pdf->SetLineDashPattern( "6", 6 );
								$pdf->SetStrokeColor( $g );
								$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
								$pdf->SetStrokeColor( $r );
								$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );
								$pdf->SetLineDashPattern( "0", 0 );
								$pdf->SetLineWidth( 2 );
								$pdf->SetStrokeColor( $b );
								$pdf->Rectangle( 0, 0, $width, $height, dynapdf::fmStroke );
							$pdf->EndPage();
			
							$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
							$pdf->SetImportFlags2(dynapdf::if2UseProxy);
			
							$pdf->SetJPEGQuality( 100 );	
						
							$pdf->AddOutputIntent( "../engine/ISOcoated_v2_eci.icc" );	
							$pdf->RenderPageToImage(1, $file_name.'_lowres.jpg', 150, $width, $height, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfJPEG, dynapdf::ifmJPEG);
							$pdf->CloseFile();
							}			
						
						unlink( $file_name.'_check.pdf' );
						//die();	
						@rename( $file_name.'_check.jpg' , $to.'/'.$file_name.'_check.jpg' );
						@rename( $file_name.'_lowres.jpg' , $to.'/'.$file_name.'_lowres.jpg' );
						
						adThumbCreate( "advertisements", $file_name.".pdf", $file_name."_thumb.jpg" );
						//die();
						for( $y = 0; $y < count( $files ); $y++ ) {
							if( $files[$y] == $file_name."_thumb.jpg" ) {
								copy( $from.'/'.$files[$y], $to.'/'.$file_name."_thumb.jpgBackup" );
								}
							else {
								copy( $from.'/'.$files[$y], $to.'/'.$files[$y] );
								}
							unlink( $from.'/'.$files[$y] );
							}
				
						$errors['size'] = (string) $xml->results[0]->size;
						$errors['bleed'] = (string) $xml->results[0]->bleed;
					
						$errors['fontmissing'] = (string) $xml->results[0]->fontmissing;
					
						if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
							$err = 0;
							$logStatus = 'successful';
							}
						else {
							$err = 1;
							$logStatus = 'failed';
							}

						if( $err == 1 ) sql_update( 'ads', '`status`="3", `reason`="", `uploaded`="", `size`="'. str_replace( '_', '/', (string) $xml->remark ) .'"', 'id="'.$ad[0][0].'"' );
						else sql_update( 'ads', '`status`="2", `reason`="", `uploaded`="", `size`="'. str_replace( '_', '/', (string) $xml->remark ) .'"', 'id="'.$ad[0][0].'"' );

						$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
						$values = array( '0', 'resultAD', $pub[0][1], $pub[0][2], $pub[0][10], $name, time(), $logStatus );
						sql_add( 'action_log', $names, $values );	
						}
					}
				}
			}
		}
?>