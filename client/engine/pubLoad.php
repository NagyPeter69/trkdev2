<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	
	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}

	if( !empty( $user[0][17] ) ) {	
		include_once('../lang/'.$user[0][17].'.php');	
		}
	else {
		include_once('../lang/en.php');	
		}
	
if( $_GET['op'] == 'load_publications' ) {
		$txt = '';
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		
		if( $user[0][4] == "6" ) {
			if( $user[0][33] != $_GET["mc"] ) {
				sql_update( "accounts", "clientSelect='".$_GET["mc"]."'", "id='".$user[0][0]."'" );
				}
			}
		
		$timeline = array();
		switch( $user[0][17] ) {
			case 'en':
				setlocale(LC_ALL,'en_GB');
				break;
			case 'hu':
				setlocale(LC_ALL,'hu_HU');
				break;
			}
		$anothers = explode( ",", $user[0][10] );
		$alter = array();
		foreach( $anothers as $another ) {
			if( intval( $user[0][4] ) != intval( $another ) ) {
				$alter[] = $another;
				}
			}
		$alter = array_unique( $alter );
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher, showMagazines' );
		$magID = explode( ",", $user[0][1] );
		$magazines = array();
		
		for( $i = 0; $i < count( $magID ); $i++ ) {
			$mag = sql_get( 'magazines', 'id="'.$magID[$i].'" ORDER BY `id` ASC', '*' );
			for( $y = 0; $y < count( $mag ); $y++ ) {
				if( $_GET["mc"] == "-1" or $_GET["mc"] == "undefined" ) {
					$magazines[] = $mag[$y];
					}
				else {
					if( $_GET["mc"] == $mag[$y][1] ) {
						$magazines[] = $mag[$y];
						}
					}
				}
			}

		if( $_GET["mc"] != "-1" and $_GET["mc"] != "0" AND $_GET["mc"] != "undefined" ) {
			$publisher = sql_aget("publishers", "id='".$_GET["mc"]."'", "*");
			$amags = sql_get( 'magazines', "pubName='".$publisher[0]["name"]."' ORDER BY `id` ASC", '*' );
			for( $i = 0; $i < count( $amags ); $i++ ) {
				$magazines[] = $amags[$i];
				}
			}
		
		usort($magazines, querySort(2) );
		$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		$row = 1;

		$longestname = "";
		$longestname2 = "";
		for( $i = 0; $i < count( $magazines ); $i++ ) {
			if( strlen( $magazines[$i][2] ) > strlen( $longestname ) ) {
				$longestname = $magazines[$i][2];
				}
			}
		
		$pxperletter = 7;
		$namewidth = ceil( strlen( $longestname ) * $pxperletter ) + 42 + 10;

		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				$temp_pub = (string) $temp->Item[$x]->Client;
				//error_log( $temp_pub." , " );
				//error_log( count( $temp->Item ) );
				if( strlen( $temp_pub ) > strlen( $longestname2 ) ) {
					$longestname2 = $temp_pub;
					}
				}
			}
		$pubwidth = ceil( strlen( $longestname2 ) * $pxperletter );		

		for( $i = 0; $i < count( $magazines ); $i++ ) {
			$cur = '';
			$management = '';	
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $magazines[$i][3] ) {
						break;
						}
					}
				}
			$cur = (string) $xml->Item[$x]->Current;
			$process = (string) $xml->Item[$x]->Workflow;
			$client = (string) $xml->Item[$x]->Publisher;
			$pn = (string) $xml->Item[$x]->PageNumbering;
			$standard = (string) $xml->Item[$x]->PDFstandard;
			$color = "";
			$alter = 0;
			$pub = sql_get( 'publications', 'magazine_id="'.$magazines[$i][0].'" AND code="'.$cur.'" ', '*' );
			if( ( $pub[0][0] ?? '' ) == '' ) {
				$pub = sql_get( 'publications', 'magazine_id="'.$magazines[$i][0].'" ORDER BY `deadline` DESC', '*' );
				$cur = $pub[0][10] ?? '';
				$alter = 1;
				}
			// A magazine with zero publications ever created under it (a real,
			// not-hypothetical state - 9 of 37 real magazines on this box have
			// none) leaves $pub empty even after the fallback above, so
			// $pub[0][10]/[12] etc. below were all reading an undefined index -
			// reproduced live 2026-09-05 loading the create_magazine listing.
			// Treat it the same as any other "no current issue" magazine
			// rather than warning on every field read from a row that isn't
			// there.
			if( empty( $pub[0][0] ) ) {
				$cur = '';
				}
			$status = $pub[0][12] ?? '';
			
			switch( $status ) {
				case 'current':
					$color = "#FF6600";
					break;
				case 'active':
					$color = "#FFCC00";
					break;
				case 'created':
					$color = "#FFFF00";
					break;
				case 'stopped':
				case 'archive_failed':
					$color = "#FF0000";
					break;
				case 'approved':
					$color = "#33CC33";
					break;
				case 'archiving':
				case 'archived':
					$color = "#3399FF";
					break;
				}
			$management = getPubButtons( $status, $pub[0] ?? array(), $process, $rights );
			$publications = sql_get( 'publications', 'magazine_id="'.$magazines[$i][0].'" AND code="'.$cur.'" ORDER BY `code` DESC', '*' );
			$publications2 = sql_get( 'publications', 'magazine_id="'.$magazines[$i][0].'" AND code!="'.$cur.'" ORDER BY `code` DESC', '*' );
			if( $status == 'current' ) $status = 'active';
			
			$mask = $border = "";
			if( count( $publications2 ) > 0 ) {
				//$border = "border-bottom: 1px solid rgb( 230, 230, 230 );";
				//$mask = "<div id='mask'></div>";
				}
			
			if( strlen( $cur ) > 0 AND $cur != 'undefined' ) {
				if( isMobile() ) {
					$onclick = "onclick='window.location.href=\"?page=flatplan&id=".$pub[0][0]."\"'";
					}
				else {
					if( $status != 'stopped' )
						$onclick = "onclick='window.location.href=\"?page=timeline&id=".$pub[0][0]."&code=".$cur."\"'";
					else $onclick = '';
					}
				
				$current = "";
				$current .= "<div ".$onclick." style='cursor: pointer; float:left; width: 100px;'>";
				
				if( $alter == 0 )
					$current .= "<b>";
				
				
				if( $magazines[$i][10] == "Adhoc" ) {
					$current .= "<b>".$magazines[$i][3]."</b>";
					}
				else {
					$current .= "<b>".$magazines[$i][3]."_".$cur."</b>";
					}
					
				if( $alter == 0 )
					$current .= "</b>";
					
				$current .= "</div>";
				$hambsvg = file_get_contents( TRKPATH."/images/settingsGray.svg" );
				$current .= "<div class='pubSettingsDot' onclick=\"issueOperation({".$management."}, '".$magazines[$i][3]."', '".$cur."' )\" style='".(  ( $status == "approved" && $magazines[$i][10] == "Adhoc" ) ? "visibility: hidden;" : "" )."'><div class='".$magazines[$i][3]."_".$cur."' style='pointer-events: none;'>".$hambsvg."</div></div>";
				// Width padded beyond the raw status text (e.g. the
				// "archiving" status's own label is itself a compound
				// "Archiving... [spinner]" string, not plain text - see
				// lang/en.php) to leave room for the trailing spinner
				// instead of it being squeezed flush against/past the
				// column's edge.
				$current .= "<div id='".$pub[0][0]."_status' class='pubStatus' style='float:left; margin-left: 5px; width: 140px; height: 1px;'>".$lang["publications"][$status]." ".( $pub[0][18] != 0 ? $ajax_flower : "" )."</div>";

				$timeLeft = strtotime( $publications[0][11] )-time();
				$day = $hour = "";
				if( $timeLeft > 0 ) {				
					$hour = floor( $timeLeft/60/60 );
					
					if( $hour > 24 ) {
						$hour = floor( $timeLeft/60/60/24 );
						$day = floor( $timeLeft/60/60/24 );
						if( $day == 0 ) { $day = ""; }
						elseif( $day == 1 ) { $day = sprintf( $lang["timeline"]["day"], $day ); }
						else $day = sprintf( $lang["timeline"]["days"], $day );
						}
					else {
						
						}
						
					if( $hour == 0 ) { $hour = ""; }
					elseif( $hour == 1 ) { $hour = sprintf( $lang["timeline"]["hour"], $hour ); }
					else $hour = sprintf( $lang["timeline"]["hours"], $hour );
					
					$current .= "<div class='pubDeadline pubExtraInfo'>".$day."".( ( $day != "" && $hour != "" ) ? "," : "" )." ".$hour."&nbsp;</div>";
					}
				else {
					$current .= "<div class='pubDeadline pubExtraInfo'>&nbsp;</div>";
					}
				
				$current .= "<div id='".$pub[0][0]."_client' class='pubExtraInfo pubClient' style='float:left; margin-left: 5px; width: ".$pubwidth."px; height: 1px;'>".$client."</div>";
				$current .= "<div id='".$pub[0][0]."_type' class='pubExtraInfo pubType' style='float:left; margin-left: 5px; width: 70px;'>".$magazines[$i][10]."</div>";		
				$current .= "<div id='".$pub[0][0]."_workflow' class='pubExtraInfo pubWorkflow' style='float:left; margin-left: 5px; width: 65px; height: 1px;'>".$process."</div>";				
				$current .= "<div id='".$pub[0][0]."_pn' class='pubExtraInfo pubPn' style='float:left; margin-left: 5px; width: 35px;'>".$lang["pn"][$pn]."</div>";
				
				$types = PARTTYPES;
				if( $magazines[$i][10] == "Adhoc" ) {
					$pub2 = sql_aget( "publications", "magazine_id='".$magazines[$i][0]."' AND code='".$magazines[$i][3]."'", "*" );
					$parts = sql_aget( "parts", "pub_id='".$pub2[0]["id"]."' order by id ASC", "*" );
					}
				
				else if( $magazines[$i][10] == "Regular" ) {
					$parts = sql_aget( "parts", "pub_id='".$pub[0][0]."' order by id ASC", "*" );
					}
				else {
					$parts = array();
					}
				
				for( $p = 0; $p < count($parts); $p++ ) {
					$temp = array_search( $types[$y], PARTS );
					if( $standard == "Web" ) {
						$parts[$p]["color"] = "sRGB";
						}

					$grayTag = ( $parts[$p]["grayscale"] == "true" ? " K" : "" );
					$current .= "<div class='part_".$p." pubExtraInfo pubParts' style='float:left; margin-left: 5px; width: 155px;'>".$lang["parts_short"][$parts[$p]["name"]].": ".$parts[$p]["color"].$grayTag."</div>";
					}
				}
			else {
				$current = "<div style='float:left; width: 90px; margin-left: 10px;'>".$lang["publications"]["no_current"]."</div>";
				$current .= "<div id='' class='pubStatus' style='float:left; margin-left: 5px; width: 105px; height: 1px;'></div>";
				$current .= "<div id='' class='pubStatus' style='float:left; margin-left: 5px; width: 100px; height: 1px;'></div>";
				$current .= "<div class='pubDeadline pubExtraInfo'>&nbsp;</div>";
				$current .= "<div id='".( $pub[0][0] ?? '' )."_client' class='pubExtraInfo pubClient' style='float:left; margin-left: 5px; width: ".$pubwidth."px; height: 1px;'>".$client."</div>";
				$current .= "<div id='".( $pub[0][0] ?? '' )."_type' class='pubExtraInfo pubType' style='float:left; margin-left: 5px; width: 70px;'>".$magazines[$i][10]."</div>";
				$current .= "<div id='".( $pub[0][0] ?? '' )."_workflow' class='pubExtraInfo pubWorkflow' style='float:left; margin-left: 5px; width: 65px; height: 1px;'>".$process."</div>";
				$current .= "<div id='".( $pub[0][0] ?? '' )."_pn' class='pubExtraInfo pubPn' style='float:left; margin-left: 5px; width: 35px;'>".$lang["pn"][$pn]."</div>";
				$current .= "</div>";				
				}		

			$onclick = '';
			if( count( $publications2 ) > 0 ) {
				$onclick = "toggle_div2(\"issues_".$magazines[$i][0]."\")";
				}
			$txt .= "<tr id='".$magazines[$i][0]."' class='".$temp->Item[$x]->Workflow."'>";
				$txt .= "<td>";
					if( $i == 0 ) $txt .= "<div row='".$row."' id='line_".$magazines[$i][3]."_".$magazines[$i][0]."' class='jline' style='position: relative; height: auto;'>";
					else $txt .= "<div row='".$row."' id='line_".$magazines[$i][3]."_".$magazines[$i][0]."' class='jline' style='".$border." position: relative; height: auto;'>".$mask;
					$mset = "";
					if( 1 ) {
						$mset = "onclick='magSettingsMenu({ 0: \"mod\", 1:\"".$magazines[$i][3]."\", 2:\"".$magazines[$i][0]."\" })'";
						//$mset = "onclick='publication_change( \"mod\", \"".$magazines[$i][3]."\", \"".$magazines[$i][0]."\" )'";
						}
						
					$txt .= "<div ".$mset." style='position:absolute; ";
					// This used to also set padding-bottom:4px !important for the
					// first row - directly contradicted by the unconditional
					// padding-bottom:0px right below, which the comment there
					// says is the actual fix (30px row + 4px padding rendered
					// 34px total, so the swatch stuck out past the row's own
					// bottom edge). !important always wins regardless of source
					// order, so that 4px was never actually overridden for row
					// 0 - it's just been silently reintroducing the exact bug
					// the 0px was added to fix, only for the first row.
					if( $i == 0 && count( $magazines ) > 1 ) $txt .= "top:0px !important;";
					// .jline (the row) is 30px tall in CSS - height:31px plus this
					// padding-bottom used to render 32px total, so the swatch stuck
					// 1-2px past the row's actual bottom border (the separator line
					// between publications), instead of ending flush with it.
					$txt .= "padding-bottom:0px; cursor: pointer; float:left; height: 30px; width: 12px;";
					if( $color != "" ) {
						$txt .= "background: ".$color." !important;";
						}
					$txt .= "' class='status_color default2'>&nbsp;</div>";
					$txt .="<div style='float:left; margin-left: 17px; padding-left: 5px; text-align: left; line-height: 30px;' class='pub_info'>";
						$ajax_flower = '<div style="display: inline-block; margin-left: 5px; margin-right: 15px; height:14px;"><div id="floatingBarsG"><div class="blockG" id="rotateG_01"></div><div class="blockG" id="rotateG_02"></div><div class="blockG" id="rotateG_03"></div><div class="blockG" id="rotateG_04"></div><div class="blockG" id="rotateG_05"></div><div class="blockG" id="rotateG_06"></div><div class="blockG" id="rotateG_07"></div><div class="blockG" id="rotateG_08"></div></div></div>';
						
						$txt .= "<div onclick='".$onclick."' style='cursor: pointer; float:left; width: ".$namewidth."px;'>".$magazines[$i][2]." ".($magazines[$i][4] == 1 ? $ajax_flower : "" )."</div>";
						$txt .= $current;		

					$txt .= "</div><div style='clear:both;'></div>";
					
					if( count( $magID ) < 3 ) {
						$display = "block";
						}
					else {
						$display = "none";
						}
					
					$txt .= "<div id='issues_".$magazines[$i][0]."' class='overflowAllow' style='display: ".$display."'>";
					
					for( $y = 0; $y < count( $publications2 ); $y++ ) {
						$row++;
						if( $cur != $publications2[$y][10] ) {
							$pub = sql_get( 'publications', 'magazine_id="'.$magazines[$i][0].'" AND code="'.$cur.'"', '*' );					
							$status = $publications2[$y][12];
							$color2 = "";
							switch( $status ) {
								case 'current':
									$color2 = "#FF6600";
									break;
								case 'active':
									$color2 = "#FFCC00";
									break;
								case 'created':
									$color2 = "#FFFF00";
									break;
								case 'stopped':
									$color2 = "#FF0000";
									break;
								case 'approved':
									$color2 = "#33CC33";
									break;
								case 'archiving':
								case 'archived':
									$color2 = "#3399FF";
									break;
								}
						
							$txt .= "<div row='".$row."' class='jline issueline' style='position:relative;'>";
								$txt .= "<div ".$mset." style='position:absolute; ";
								// Same 30px-row-vs-taller-swatch mismatch as the primary
							// row's status_color above (height + this padding used to
							// total 32px against a 30px row).
							$txt .= "padding-top:0px; padding-bottom:0px; cursor: pointer; float:left; height: 30px; width: 12px; background: ".$color2." !important;' class='status_color default2'>&nbsp;</div>";
								if( isMobile() ) {
									$onclick = "onclick='window.location.href=\"?page=flatplan&id=".$publications2[$y][0]."\"'";
									}
								else {
									if( $status != 'stopped' )
										$onclick = "onclick='window.location.href=\"?page=timeline&id=".$publications2[$y][0]."&code=".$publications2[$y][10]."\"'";
									else $onclick = '';
									}
								
								$txt .= "<div ".$onclick." class='issueName' style='margin-left: ".($namewidth+22)."px; cursor: pointer; float:left; line-height:30px; width: 100px; text-align: left;'>";
									$txt .= $magazines[$i][3]."_".$publications2[$y][10];
								$txt .= "</div>";
								$submanagemenet = getPubButtons( $status, $publications2[$y], $process, $rights );
								$hambsvg = file_get_contents( TRKPATH."/images/settingsGray.svg" );
								$txt .= "<div class='pubSettingsDot' onclick=\"issueOperation({".$submanagemenet."}, '".$magazines[$i][3]."', '".$publications2[$y][10]."' )\"><div class='".$magazines[$i][3]."_".$publications2[$y][10]."' style='pointer-events: none;'>".$hambsvg."</div></div>";
								// Width padded beyond the raw status text (e.g. the
								// "archiving" status's own label is itself a
								// compound "Archiving... [spinner]" string, not
								// plain text - see lang/en.php) to leave room for
								// the trailing spinner instead of it being
								// squeezed flush against/past the column's edge.
								$txt .= "<div  id='".$publications2[$y][0]."_status' class='pubStatus' style='float:left; margin-left: 5px; line-height:30px; text-align: left;  width: 140px;'>";
									if( $status == 'active' or $status == 'current' ) {
										$txt .= $lang['publications']['active'];
										}
									else {
										$txt .= $lang['publications'][$status];
										}
								$txt .= ( $publications2[$y][18] != 0 ? $ajax_flower : "" );
								$txt .= "</div>";
								$timeLeft = strtotime( $publications2[$y][11] )-time();
								$day = $hour = "";
								if( $timeLeft > 0 ) {				
									$hour = floor( $timeLeft/60/60 );
									if( $hour > 24 ) {
										$hour = floor( $timeLeft/60/60%24 );
										$day = floor( $timeLeft/60/60/24 );
										if( $day == 0 ) { $day = ""; }
										elseif( $day == 1 ) { $day = sprintf( $lang["timeline"]["day"], $day ); }
										else $day = sprintf( $lang["timeline"]["days"], $day );
										}
						
									if( $hour == 0 ) { $hour = ""; }
									elseif( $hour == 1 ) { $hour = sprintf( $lang["timeline"]["hour"], $hour ); }
									else $hour = sprintf( $lang["timeline"]["hours"], $hour );
				
									$txt .= "<div class='pubDeadline'>".$day."".( ( $day != "" && $hour != "" ) ? "," : "" )." ".$hour."</div>";		
									}															
								//$txt .= createPubButtons( $status, $publications2[$y] );
							$txt .= "</div>";
							$txt .= "<div style='clear:both;'></div>";
							}
						}
					$txt .= "</div>";	
				$txt .= "</td>";
			$txt .= "</tr>";
			$row++;
			} 
		
		$result = $txt;
		}	
	
	print json_encode( $result );
	
?>