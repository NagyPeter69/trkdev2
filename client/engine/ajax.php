<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');
	
	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	include_once( 'switchAPI.php' );
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

	if( $_GET["op"] == "filetransfer_loadtypes" ) {
		include( "../../engine/fileClass.php" );
		$pub = sql_aget( 'publications', 'id="'.$_GET['pid'].'"', '*' );
		
		$mag = sql_get( 'magazines', 'id="'.$pub[0]['magazine_id'].'"', 'code' );
		$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $mag[0][0] ) {
					break;
					}
				}
			}
		
		$workflow = (string) $xml->Item[$x]->Workflow;
		
		$user = sql_aget( "accounts", "id='".$_SESSION['intra_user']."'", "*" );
		$parts = sql_aget( "parts", "pub_id='".$pub[0]["id"]."' order by id ASC", "*" );
		$uploader = new file( $parts, $user[0]["id"] );
		
		$picturePackAllow = array( "Resize", "Repack", "Enhance" );
		if( in_array( $workflow, $picturePackAllow ) ) {
			$txt = $uploader->getSelectList( "filetypefull", "type" );
			}
		else {
			$txt = $uploader->getSelectList( "filetype", "type" );
			}
		
		
		
		$result = $txt;
		}
		
	if( $_GET["op"] == "getfreespace" ) {
		$total = disk_total_space("/var/www/html/client");
		$bytes = disk_free_space("/var/www/html/client");
		$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
		$base = 1024;
		$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		$percent = $bytes / $total * 100;
				
		if( $percent <= 5 ) {
			$color = "rgba(251, 54, 64, 1)";
			}
		elseif( $percent > 5 && $percent <= 10 ) {
			$color = "rgba(247, 244, 41, 1)";
			}
		else {
			$color = "rgba(2, 174, 14, 1)";
			}
					
		$result = "<span>Storage:</span> <span style='color: ".$color.";'>".( sprintf('%1.0f' , floor( $bytes / pow($base,$class) ) ) )." ". $si_prefix[$class]."</span>";		
		}

	if( $_GET['op'] == 'hotlinkresend' ) {
		$hotlink = sql_aget( "adhoc_hotlinks", "id='".$_GET["hotlinkid"]."'", "*" );
		$magazin = sql_aget( "magazines", "id='".$hotlink[0]["magazine_id"]."'", "*" );
		$link = "https://".URL."/index.php?hash=".$hotlink[0]["hash"];
		
		$subject = $magazin[0]["name"]." létrehozva a Colorcom Trackeren";
		$to = $hotlink[0]["email"]."|".$hotlink[0]["email"];
		//$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
		$body = "Kedves Ügyfelünk!<br>
		<br>
		A Colorcom Tracker rendszerben létrehoztuk a ".$magazin[0]["name"]." munkát ".$magazin[0]["code"]." azonosítóval. Az alábbi linkre kattintva tudja feltölteni feldolgozásra váró anyagát, közvetlenül a Tracker rendszerbe.<br>
		<br>
		<a href='".$link."'>".$link."</a><br>
		<br>
		Üdvözlettel:<br>
		Colorcom Media";
		produkcioSendmail( $subject, $body, $to );
		
		$result = "ok";
		}
		
	if( $_GET['op'] == 'downloadCSV' ) {
		$pub = sql_aget( "publications", "id='".$_GET["pubid"]."'", "*" );
		$magazine = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
		
		$csvName = $magazine[0]["code"]."_".$pub[0]["code"].".csv";
		$csvPath = TRKPATH."/csv/".$csvName;
		
		if( !is_file( $csvPath ) ) {
			$hird = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND status='2' AND fin='1' AND issue='".$pub[0]["code"]."' AND type='ad' GROUP BY page", "*" );
			$process = getProcess( $magazine[0]["code"] );
			
			$adProof = 0;
			$proof = 0;
			$coverProof = 0;
			$temp = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND issue='".$pub[0]["code"]."' AND proofCounter != '0'", "*" );
			error_log( "DEBUG" );
			error_log( count( $temp ) );
			for( $i = 0; $i < count( $temp ); $i++ ) {
				if( $temp[$i]["type"] == "ad" ) {
					$adProof += $temp[$i]["proofCounter"];
					}
				else {
					if( $temp[$i]["page"] == "1" ) {
						$coverProof += $temp[$i]["proofCounter"];
						}
					else {
						$proof += $temp[$i]["proofCounter"];
						}
					}
				}
			
			$adProofTemp = sql_aget( "pageinfo", "action='adProof' AND magazine='".$pub[0]["magazine_id"]."' AND issue='".$pub[0]["code"]."' ", "*" );
			$adProof += count( $adProofTemp );
			
			$csv = "";
			$csv .= "Név:\t".$magazine[0]["name"]."\n";
			$csv .= "Megjelenés:\t".$magazine[0]["code"]."_".$pub[0]["code"]."\n";
			$csv .= "Lezárva:\t".date( "Y-m-d\TH:i:s" , time() )."\n";
			$csv .= "Terjedelem:\t".$pub[0]["pages"]."\n";
			$csv .= "Hirdetési oldalak:\t".count( $hird )."\n";	
			$csv .= "Szerkesztőségi oldalak:\t".( $pub[0]["pages"] - count( $hird ) )."\n";		
			
			if( $process == "Full" or $process == "Hybrid" ) {	
				$csv .= "Retusált képek\n";			
				$kepek = sql_aget( "image_map", "pub_id='".$pub[0]["id"]."' AND retus > 0", "*" );
				}
			else {
				$csv .= "Képlista\n";
				$kepek = sql_aget( "image_map", "pub_id='".$pub[0]["id"]."'", "*" );
				}
			
			$retussum = 0;	
			for( $i = 0; $i < count( $kepek ); $i++ ) {
				$csv .= "\t".$kepek[$i]["name"]."\t".( $kepek[$i]["retus"] != "0" ? $kepek[$i]["retus"] : "" )."\t".( $kepek[$i]["maszk"] == "1" ? "1" : "" )."\n";			
				$retussum += $kepek[$i]["retus"];
				}
				
			if( $retussum > 0 ) {
				$csv .= "\tÖsszesen (perc)\t".$retussum."\t\n";
				}
			
			if( $process == "Full" or $process == "Hybrid" ) {
				$csv .= "Prooflista\n";		
				$csv .= "Hirdetés proof:\t".$adProof."\n";		
				$csv .= "Szerkesztőségi proof:\t".$proof."\n";
				$csv .= "Borító proof:\t".$coverProof."\n";
				$csv .= "Összesen:\t".($adProof + $proof + $coverProof )."\n";
				$csv .= "Szerkesztőségi + borító proof-ok:\t".( $proof + $coverProof )."\n";		
				$proofs = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND issue='".$pub[0]["code"]."' AND proofCounter != '0' AND type != 'ad'", "*" );
				for( $i = 0; $i < count( $proofs ); $i++ ) {
					$csv .= "\t".str_pad( $proofs[$i]["page"], 3, '0', STR_PAD_LEFT)."_".$magazine[0]["code"]."".$pub[0]["code"]."\t".$proofs[$i]["proofCounter"]."\n";		
					}
			
				$csv .= "Hirdetési proof-ok:\t".$adProof."\n";			
				$proofs = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND issue='".$pub[0]["code"]."' AND proofCounter != '0' AND type = 'ad'", "*" );
				for( $i = 0; $i < count( $proofs ); $i++ ) {
					$ad = sql_aget( "ads", "id='".$proofs[$i]["pack_id"]."'", "*" );			
					$csv .= "\t".$ad[0]["name"]."\t".$proofs[$i]["proofCounter"]."\n";
					}
				}
				
			$csv = iconv( "UTF-8", "UTF-16", $csv );
			file_put_contents( TRKPATH."/csv/".$magazine[0]["code"]."_".$pub[0]["code"].".csv" , $csv );
			}
			
		$result = array( $csvPath, $csvName );	
		}

	if( $_GET['op'] == 'uploadparamchange' ) {
		if( !empty( $user[0][0] ) ) {	
			error_log( "DEBUG: ".$_GET["cb"] );
			sql_update( "accounts", "uploadparams='".$_GET["cb"]."'", "id='".$user[0][0]."'" );
			}
		}
		
	if( $_GET['op'] == 'checkFileuploadName' ) {
		$text = "";
		$valid = 1;
		//error_log( "NÉV: ".$_GET["name"] );
		$name = explode(".", $_GET["name"] );
		array_pop($name);
		$name = implode( ".", $name );
		$name = explode( "_", $name );
		//error_log( "CHECK: code='".$name[1]."'" );
		$check = sql_aget( "magazines", "code='".$name[1]."'", "*" );
		if( !empty( $check[0]["id"] ) ) {
			if( $check[0]["type"] == "Regular" ) {
				$check = sql_aget( "publications", "magazine_id='".$check[0]["id"]."' AND code='".$name[2]."'", "*" );
				}
			else {
				$check = sql_aget( "publications", "magazine_id='".$check[0]["id"]."' AND code='".$name[1]."'", "*" );
				}

			if( !empty( $check[0]["id"] ) ) {
				$allowstatus = array( "current", "active", "created" );
				if( in_array( $check[0]["status"], $allowstatus ) ) {
					}
				else {
					$valid = 0;
					$text = "<span class='uploadErrorText'>".$lang["filetransfer"]["error_wrongstatus"]."</span>";
					}
				}
			else {
				$valid = 0;
				$text = "<span class='uploadErrorText'>".$lang["filetransfer"]["error_nopublication"]."</span>";
				}
			}
		else {
			$filter_code = filter_var($name[1], FILTER_SANITIZE_NUMBER_INT);
			$filter_mag = str_replace( $filter_code, "", $name[1] );

			error_log( $filter_mag.", ".$filter_code );
			$check = sql_aget( "magazines", "code='".$filter_mag."'", "*" );
			if( !empty( $check[0]["id"] ) ) {
				if( $check[0]["type"] == "Regular" ) {
					$check = sql_aget( "publications", "magazine_id='".$check[0]["id"]."' AND code='".$filter_code."'", "*" );
					}
				else {
					$check = sql_aget( "publications", "magazine_id='".$check[0]["id"]."' AND code='".$name[1]."'", "*" );
					}

				if( !empty( $check[0]["id"] ) ) {
					$allowstatus = array( "current", "active", "created" );
					if( in_array( $check[0]["status"], $allowstatus ) ) {
						}
					else {
						$valid = 0;
						$text = "<span class='uploadErrorText'>".$lang["filetransfer"]["error_wrongstatus"]."</span>";
						}
					}
				else {
					$valid = 0;
					$text = "<span class='uploadErrorText'>".$lang["filetransfer"]["error_nopublication"]."</span>";
					}
				}
			else {
				$valid = 0;
				$text = "<span class='uploadErrorText'>".$lang["filetransfer"]["error_nomagazine"]."</span>";
				}
			}

		$result = array( $valid, $text );
		}

	if( $_GET['op'] == 'downloadsettingssave' ) {
		$temp = mysqli_real_escape_string( $con, json_encode($_POST["settings"]) );	
		sql_update( "accounts", "downloadsettings='".$temp."'", "id='".$_SESSION['intra_user']."'" );
		}

	if( $_GET['op'] == 'uploadsettingssave' ) {
		$temp = mysqli_real_escape_string( $con, json_encode($_POST["settings"]) );	
		sql_update( "accounts", "uploadsettings='".$temp."'", "id='".$_SESSION['intra_user']."'" );
		}

	if( $_GET['op'] == 'filetransfer_loadparts' ) {
		include( "../../engine/fileClass.php" );
		
		$job = sql_aget( "publications", "id='".$_GET["jname"]."'", "*" );
		$mag = sql_aget( "magazines", "id='".$job[0]["magazine_id"]."'", "*" );
		$parts = sql_aget( "parts", "pub_id='".$job[0]["id"]."' order by id ASC", "*" );
		
		$uploader = new file( $parts, $user[0][0] );
		
		$magazine = $mag[0]["name"]." – ".$job[0]["code"];
		
		$finished = false;
		$fin = array( "archived", "approved", "archiving", "stopped" );
		if( in_array( $job[0]["status"], $fin ) ) {
			$finished = true;
			}
		
		$result = array( $uploader->getSelectList( "parts", "part" ), $magazine, $finished );
		}

	if( $_GET['op'] == 'filetransfer_history' ) {
		$txt = "";
		$user = sql_aget( "accounts", "id='".$_SESSION['intra_user']."'", "*" );
		//error_log( "USERID: ".$user[0]["id"] );
		//error_log( "uploadparams: ".$user[0]["uploadparams"] );
		if( !empty( $user[0]["id"] ) && $user[0]["uploadparams"] == "0" ) {
			//error_log( "userid='".$user[0]["id"]."' ORDER BY time DESC" );
			$uploads = sql_aget( "filetransfer_log", "userid='".$user[0]["id"]."' ORDER BY time DESC", "*" );
			}
		else {
			$uploads = sql_aget( "filetransfer_log", "jobname='".$_GET["jname"]."' AND jtype='".$_GET["jtype"]."' ORDER BY time DESC", "*" );
			}
	
		for( $i = 0; $i < count( $uploads ); $i++ ) {
			$txt .= "<tr>";
				$name = $uploads[$i]["filename"];
				if( strlen( $name ) > 55 ) {
					$name = mb_substr( $name, 0, 26 )."...".mb_substr( $name, -26 );
					}
					
				$txt .= "<td><div class='transferlog_name'>".$name."</div></td>";
				$txt .= "<td>".strftime( "%Y. %B %d. %k:%M", $uploads[$i]["time"] )."</td>";
			$txt .= "</tr>";
			}
		
		$pub = sql_aget( "publications", "id='".$_GET["jname"]."'", "*" );
		$mag = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
		
		$resend = "";
		if( $mag[0]["type"] == "Adhoc" ) {
			$hotlink = sql_aget( "adhoc_hotlinks", "magazine_id='".$mag[0]["id"]."' order by id DESC Limit 1", "*" );
			$link = "https://".URL."/index.php?hash=".$hotlink[0]["hash"];
				
			$resend .= "<div style='padding-bottom: 20px;'>";
				$resend .= $link."<button onclick='hotlinkresend(\"".$hotlink[0]["id"]."\")' style='margin-left: 10px;'>".$lang["filetransfer"]["resend"]."</button>";
			$resend .= "</div>";
			}		
		
		$result = array( $txt, $resend );
		}

	if( $_GET['op'] == 'filetransfer_getparts' ) {
		$txt = '';
		
		$txt = $_GET['pub'];
		
		$result = $txt;
		}

	if( $_GET['op'] == 'magazin_modify' ) {
		ob_start();
			include('../magazin_modify.php');
		$result = ob_get_clean();
		}

	if( $_GET['op'] == 'new_mag' ) {
		ob_start();
			include('../new_magazine.php');
		$result = ob_get_clean();
		}	
		
	if( $_GET['op'] == 'new_pub' ) {
		$magazine = sql_get( 'magazines', 'id="'.$_GET['id'].'"', '*' );
		ob_start();
			include('../new_pub.php');
		$result = ob_get_clean();
		}		
		
	if( $_GET['op'] == 'get_ftp' ) {
		$place = explode( "_", $_GET['node'] );
		$txt = '';
		$pub = sql_get( 'publishers', 'id="'.$_GET['pub'].'"', 'name' );	
		$pub = $pub[0][0];
		
		$xml = simplexml_load_file( '../xml/Output_Details.xml' );
		$v = 'Content';
		
		if( $_GET['node'] == "archive" ) {
			$xpath = $xml->$pub->Outward->Archive;
			}
		else {
			$xpath = $xml->$pub->Outward->$place[1]->$place[0];
			}
			
		$nodes = array( 'Address', 'Port', 'Passive', 'Binary', 'Login', 'Pass', 'Path' );
		
		$txt .= "<input type='hidden' name='m_parent' value='".$v."'>";
		$txt .= "<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>";
		foreach( $nodes as $node ) {
			$temp = '';
			$value = (string) $xpath->$node;
			
			switch( $node ){
				case 'Address':
					$temp = explode( ".", $value );
					break;
				case 'Binary':
					$temp = array( 'true', 'false' );
					break;
				case 'Passive':
					$temp = array( 'Yes', 'No' );
					break;
				}

			$txt .= "<tr>";
				$txt .= "<td style='width: 130px;' align='left'>";
					$txt .= $lang['settings']['ftp_'.strtolower($node)];
				$txt .= "</td>";
				$txt .= "<td align='left'>";
					if( $node == 'Address' ) {
						if( count( $temp ) == 4 )
							$txt .= "<input type='text' onkeypress='return isNumberKey(event)' id='m_address_1' name='m_address_1' value='".$temp[0]."' style='margin-left: 2px; width:25px;'>.<input type='text' onkeypress='return isNumberKey(event)' id='m_address_2' name='m_address_2' value='".$temp[1]."' style='width:25px;'>.<input type='text' onkeypress='return isNumberKey(event)' id='m_address_3' name='m_address_3' value='".$temp[2]."' style='width:25px;'>.<input type='text' onkeypress='return isNumberKey(event)' id='m_address_4' name='m_address_4' value='".$temp[3]."' style='width:25px;'>";
						else
							$txt .= "<input type='text' id='m_address_url' name='m_address_url' value='".$value."'>";
						}
					elseif( $temp != '' ) {
						$txt .= "<select id='m_".strtolower($node)."' name='m_".strtolower($node)."'>";
						foreach( $temp as $t ) {
							$txt .= "<option ";
							if( $value == $t ) $txt .= "selected ";
							$txt .= "value='".$t."'>".$t."</option>";
							}
						$txt .= "</select>";
						}
					elseif( $node == 'Pass' ) {
						$txt .= "<input type='password' autocomplete='off' id='m_".strtolower($node)."' name='m_".strtolower($node)."' value='".decrypt_( $value )."'>";
						}
					else {
						$txt .= "<input type='text' autocomplete='off' id='m_".strtolower($node)."' name='m_".strtolower($node)."' value='".$value."'>";
						}
				$txt .= "</td>";
				$txt .= "<td style='padding-right: 30px;'>";
					if( $node == 'Pass' ) {
						$txt .= "<input onchange='revealPass( this, \"m_pass\")' type='checkbox' name='reveal'>&nbsp;".$lang["settings"]["reveal"];
						}
					else {
						$txt .= "&nbsp;";
						}
				$txt .= "</td>";
			$txt .= "</tr>";
			}
		$txt .= "</table>";
		
		$result = $txt;
		}		
		
	if( $_GET['op'] == 'load_packages' ) {
		$txt = '';
		$sql = sql_get( 'accounts', 'id="'.$_GET['user'].'"', "`publisher`, `group`");
		$r = sql_aget( 'user_groups', 'id="'.$sql[0][1].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		
		$p_id = sql_get( 'publications', 'code="'.$_GET['code'].'" AND id="'.$_GET['p_id'].'"', '*' );
		switch( $_GET['type'] ) {
			case 'pre':
				$packs = sql_aget( 'packages', 'publication_id="'.$p_id[0][0].'" AND starting_page="" ORDER BY `name` ASC', '*' );
				break;
			case 'final':
				$packs = sql_aget( 'packages', 'publication_id="'.$p_id[0][0].'" AND acquired_name LIKE "FIN_%" ORDER BY starting_page+0 ASC', '*' );
				break;
			default:
				$packs = sql_aget( 'packages', 'publication_id="'.$p_id[0][0].'" AND starting_page != "" ORDER BY starting_page+0 ASC', '*' );
				break;
			}
		
		for( $i = 0; $i < count( $packs ); $i++ ) {
			$pages = $packs[$i]['starting_page'];
			
			
			
			//-------        Hibák checkolása      ----------------
			$errors = '';
			$details = "";
			
			$checker = sql_get( 'package_info', 'package_id="'.$packs[$i]['id'].'" AND event="no_indd"', '*' );
			if( count( $checker ) > 0 ) {
				$errors .= "<span class='pack_error'>Hiányzó InDesign csomag</span>";
				}
			
			$checker = sql_get( 'package_info', 'package_id="'.$packs[$i]['id'].'" AND event="missing"', '*' );
			$missing = 0;
			if( count( $checker ) > 0 ) {
				$errors .= "<span id='".$packs[$i]['id']."_missing' style='cursor: pointer;' onclick='showError( ".$packs[$i]['id'].", \"missing\", this )' class='pack_error'>".$lang["publications"]["missing"]."</span>";
				$missing = 1;
				}
			$checker = sql_get( 'package_info', 'package_id="'.$packs[$i]['id'].'" AND event="corrupt"', '*' );
			if( count( $checker ) > 0 ) {
				$errors .= "<span id='".$packs[$i]['id']."_corrupt' style='cursor: pointer;' class='pack_error' onclick='showError( ".$packs[$i]['id'].", \"corrupt\", this )'>".$lang["publications"]["corrupt"]."</span>";
				}

			$checker = sql_get( 'package_info', 'package_id="'.$packs[$i]['id'].'" AND event="lowres"', '*' );
			if( count( $checker ) > 0 ) {
				$pub = sql_get( 'publications', 'id="'.$packs[$i]['publication_id'].'"', '*' );
				$mag = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
				
				$errors .= "<span id='".$packs[$i]['id']."_lowres' style='cursor: pointer;' class='pack_error' onclick='showError( ".$packs[$i]['id'].", \"lowres\", this )'>".$lang["publications"]["lowres"]."</span>";
				}
				
			$class = 'default';
			if( $pages == '0' or $pages == '' ) {
				$pages = 'PRE';
				}
			
			if( $packs[$i]['name'] == '' ) {
				$packs[$i]['name'] = '<i>Névtelen</i>';
				}
			
			$pub = sql_get( 'publications', 'id="'.$packs[$i]['publication_id'].'"', '*' );
			$mag = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );	

			$path = '../packages/'.$mag[0][3].'/'.$pub[0][10].'/'.$packs[$i]['directory'];
			$path2 = 'packages/'.$mag[0][3].'/'.$pub[0][10].'/'.$packs[$i]['directory'];
			$thumbs = '';
			
			//-------        Thumbnail sorakoztatás      ----------------
		/*	$files = load_dir_files( $path, '_preview.jpg' );
			sort($files);	
			for( $f = 0; $f < count( $files ); $f++ ) {
				if( $i <= 4 ) $thumb_class = 'thumb_b';
				else $thumb_class = 'thumb';
				
				$thumbs .= "<div onclick='window.location.href=\"?page=flatplan&id=".$pub[0][0]."\"' class='".$thumb_class."' style='cursor: pointer; float: left; height: 30px; margin-left: 5px;' title=\"<img src='".$path2.'/'.$files[$f]."' height='140px'>\"><img src='".$path2.'/'.$files[$f]."' height='30px'></div>";
				}*/
			
			//-------        Status elemzés      ----------------
			$status = $packs[$i]['status'];
			$s_class = '';
			$elapsed = '';
			$display = false;
			//$status == $_GET["type2"] or $_GET["type2"] == "-1"
			if( $status == 1 ) {
				$s_class = 'color: #399C58;';
				if( $_GET["type2"] == "1" ) $display = true;
				}
			else {
				$line = (60*60*24)*2;
				$currentTime = time();
				if( $packs[$i]['status_changed'] != 0 ) {
					$elapsed = $currentTime-$packs[$i]['status_changed'];
					}
				else {
					$elapsed = ($currentTime)-(strtotime( $packs[$i]['date'] ));
					}
				
				if( $elapsed >= $line ) {
					$s_class = 'color: #CA4139;';
					if( $_GET["type2"] == "0" ) $display = true;
					}
				else {
					if( $_GET["type2"] == "0b" ) $display = true;
					}
				}
			if( $_GET["type2"] == "-1" ) $display = true;
			
			$date = '';
			if( $packs[$i]['status_changed'] != 0 ) {
				$date = date( "Y-m-d H:i:s", $packs[$i]['status_changed'] );
				}
			else {
				$date = $packs[$i]['date'];
				}
			
			//-------        Sor generálás      ----------------
			if( $display ) {
				$txt .= "<tr id='".$packs[$i]['id']."'>";
					$txt .= "<td style='position: relative;'>";
						$txt .= "<div style='float:left; padding-left: 10px; text-align: left; line-height: 30px;' class='ad_info'>";
							$txt .= "<div style='float:left; width: 70px; margin-left:4px;'>".$pages."</div>";
							$txt .= "<div style='float:left; padding-left: 30px; width: 280px; margin-left: 23px;'><span onclick=\"";
							if( $details != '' )
								$txt .= "toggle_div( 'detail_".$packs[$i][0]."' ,0 )";
							$txt .= "\">".$packs[$i]['name']."</span></div>";
							$txt .= "<div style='".$s_class." float:left; padding-left: 10px;'>".$date."</div>";
						$txt .= "</div>";
						if( count( $files ) > 0 )
							$txt .= "<div class='thumb_div' style='float:left; padding-left: 15px;'>".$thumbs."</div>";
						
						if( $errors != '' ) {
							$txt .= "<div style='float:right; line-height: 30px; padding-right: 18px;'>".$errors."</div>";
							if( $errors != "<span class='pack_error'>Hiányzó InDesign csomag</span>" )
								$txt .= "<div class='errorClick' onclick='showError( ".$packs[$i]['id']." )' style='cursor: pointer; float:right; display: none; line-height: 26px; padding-right: 18px;'> >> </div>";
							}
						$txt .= "<div style='clear:both;'></div>";
						$txt .= "<div id='detail_".$packs[$i][0]."' class='details' style='display:none; background: #F5F5F5 !important; width: 100%;'>";
							$txt .= $details;
						$txt .= "</div>";
					$txt .= "</td>";
				$txt .= "</tr>";
				}
			}
			
		$result = $txt;
		}		
		
	if( $_GET['op'] == 'new_jobcode' ) {
		$name = explode( "_", CreateJobCode( $_SESSION['intra_user'] ) );
		
		$result = array( $name[0], $name[1] );
		}		
		
	if( $_GET['op'] == 'send_proof' ) {
		$_GET['page'] = str_replace( "_", ",", $_GET['page'] );
		$name = CreateJobCode( $_SESSION['intra_user'] );

		$array["client"] = $user[0][1];
		$array["pubName"] = $_GET['file'];
		$array["jobCode"] = $name;
		$array["issue"] = '';
		$array["event"] = 'submit';
		$array["description"] = $_GET['type'];
		$array["color"] = $_GET['color'];
		$array["pages"] = $_GET['page'];
		$array["remark"] = $_GET['msg'];
		
		$myxml = array_to_xml( $array, 'eventComm' );
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($myxml);
		$dom->formatOutput = true;
		
		$target = "../uploads/adhoc";
		$counter = get_counter('..');
		$switch_name = 'message_'.$counter;
		inc_counter('..');
		file_put_contents( $target.'/'.$switch_name.'.xml', $dom->saveXML() );
		
		$old = "../temp/".$_GET['file'];
		$ext = explode( '.', $_GET['file'] );
		$ext = strtolower( $ext[count($ext)-1] );
		$r = $_GET['file'];
		if( $ext == 'pdf' ) {
			$page = countpdfpage( "../temp/".$_GET['file'] );
			$r = $_GET['file']."asdf";
			$pdf = new dynapdf();
			include('config.inc.php');
			
			$pdf->CreateNewPDF( "../temp/_".$_GET['file'] );
			$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
			$pdf->SetImportFlags2(dynapdf::if2UseProxy);

			$first        = true;
			$destPage     = 0;
			$haveXFA      = false;
			$isCollection = false;
			if ($pdf->OpenImportFile( $old, dynapdf::ptOpen, NULL) < 0) die('Cannot open file!');
			
			if($first) {
				$first        = false;
				$haveXFA      = $pdf->GetInIsXFAForm();
				$isCollection = $pdf->GetInIsCollection();
				//if( ($destPage = $pdf->ImportPDFFile($destPage + 1, 1.0, 1.0)) ) { break; }
				}
			else {
				if ($isCollection) {
					if ($pdf->GetInIsCollection()) {
						$pdf->SetImportFlags(dynapdf::ifEmbeddedFiles);
						//if (!$pdf->pdfImportCatalogObjects()) break;
						}
					else {
						$pdf->CloseImportFile();
						$pdf->AttachFile( $old, $old, true);
						}
					}
				else {
					//if ($pdf->GetInIsCollection() || (($pdf->GetInIsXFAForm() || $pdf->GetInFieldCount()) && ($pdf->GetFieldCount() > 0 || $haveXFA))) break;
					//if (($destPage = $pdf->ImportPDFFile($destPage + 1, 1.0, 1.0)) < 0) break;
					}
				}
			
			$text = $_GET['file'].' | '.$_GET['color'].' | '.$client[0][1].' | '.$name.' | '.date( 'Y-m-d' ).'T'.date( 'G:i:s' );
			
			for ($i = 1; $i <= $page; $i++) {
				$pdf->EditPage( ($i) );
					$pdf -> LoadFontEx( '../../fonts/Roboto-Thin.ttf', 1, dynapdf::fsRegular, 7, true, dynapdf::cp1252 );
					$pdf->WriteAngleText( $text,90,($pdf->GetPageWidth()+5), 10,24,10 );
				$pdf->EndPage();				
				}
			$pdf->CloseImportFile();
				
			$pdf->SetPDFVersion( 10 );
			
			$pdf->CloseFile();
			unlink( $old );
			$old = "../temp/_".$_GET['file'];
			}
		else {
			list($width, $height, $type, $attr) = getimagesize("../temp/".$_GET['file']);
			$text = $_GET['file'].' | '.$_GET['color'].' | '.$client[0][1].' | '.$name.' | '.date( 'Y-m-d' ).'T'.date( 'G:i:s' );
			
			$pdf = new dynapdf();
			include('config.inc.php');
			
			$pdf->CreateNewPDF( "../temp/".substr($_GET['file'], 0, -4 ).".pdf" );

			$pdf->SetPageCoords(dynapdf::pcTopDown);
			$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
			$pdf->SetImportFlags2(dynapdf::if2UseProxy);
			$pdf->SetPageWidth( intval( $width )+30 );
			$pdf->SetPageHeight( intval( $height )+30 );
			
			$pdf->Append();
				$pdf ->LoadFontEx( '../../fonts/Roboto-Thin.ttf', 1, dynapdf::fsRegular, 7, true, dynapdf::cp1252 );
				$pdf->WriteAngleText( $text,90,(intval( $width )+7), (intval( $height )+10),24,10 );
				//$pdf->SetGStateFlags( dynapdf::gfIgnoreICCProfiles, true );
				$pdf->SetColorSpace( 1 );
				//$pdf->CreateICCBasedColorSpace('ISOcoated_v2_eci.icc');
				
				$pdf->InsertImageEx(15.0, 15.0, 0.0, 0.0,  "../temp/".$_GET['file'], 1);
			$pdf->EndPage();
			
			$pdf->SetPDFVersion( 10 );
			//$pdf->CreateICCBasedColorSpace('ISOcoated_v2_eci.icc');
			$pdf->AddOutputIntent( '/var/www/html/r3API/r3/'.resolveIccProfileByName( "FOGRA_39" ) );
			$pdf->CloseFile();
			
			unlink( "../temp/".$_GET['file'] );
			$old = "../temp/".substr($_GET['file'], 0, -4 ).".pdf";
			$ext = "pdf";
			}
			
		rename( $old, $target.'/'.$switch_name.'.'.$ext );
		
		$names = array( 'user_id', 'type', 'settings', 'gen_name' );
		$settings = 'Color:'.$_GET['color'].'|Comment:'.$_GET['msg'].'|Original_filename:'.$_GET["file"].'|Pages:'.$_GET['page'].'';
		$values = array( $_SESSION['intra_user'], $_GET['type'], $settings, $name );
		sql_add( 'ad_hoc', $names, $values );
		
		$result = $r;
		}		
	
	if( $_GET['op'] == 'load_thumbnails' ) {
		if( $_GET['page'] == 'all' ) {
			$pages = countpdfpage( '../temp/'.$_GET['file'] );
			$txt = '<table width="100%" cellspacing="0" cellpadding="0">';
			$y = 1;
			
			$ext = explode( '.', $_GET['file'] );
			$ext = strtolower( $ext[count($ext)-1] );
			
			if( $ext == 'pdf' ) {
				for( $i = 1; $i <= $pages; $i++ ) {
					if( $y == 1 ) { $txt .= '<tr>'; }
					$file = pdftoimage( '../temp/'.$_GET['file'], $i );
					switch( $y ) {
						case 1:
							$txt .= "<td valign='top' align='left'>";
							$txt .= "<div><img id='div_".$i."' style='border: 5px solid transparent; -webkit-transition: all 0.22s ease-out;' onclick='selectImage(".$i.")' src='".$file."' width='90px;'></div>";
							$txt .= "<div style='width: 90px; text-align: center;'><input style='display:none;' id='box_".$i."' class='p_page' type='checkbox' name='p_page' value='".$i."'>&nbsp;".sprintf( $lang["adhoc"]["page"] , $i )."</div>";
							break;
						case 2:
							if( $pages == 2 ) {
								$txt .= "<td valign='top' align='right'>";
								$txt .= "<div><img id='div_".$i."' style='border: 5px solid transparent; -webkit-transition: all 0.22s ease-out;' onclick='selectImage(".$i.")' src='".$file."' width='90px;'></div>";
								$txt .= "<div style='width: 90px; text-align: center;'><input style='display:none;' id='box_".$i."' class='p_page' type='checkbox' name='p_page' value='".$i."'>&nbsp;".sprintf( $lang["adhoc"]["page"] , $i )."</div>";
								}
							else 
								{
								$txt .= "<td valign='top' align='center'>";
								$txt .= "<div><img id='div_".$i."' style='border: 5px solid transparent; -webkit-transition: all 0.22s ease-out;' onclick='selectImage(".$i.")' src='".$file."' width='90px;'></div>";
								$txt .= "<div style='text-align:center;'><input style='display:none;' id='box_".$i."' class='p_page' type='checkbox' name='p_page' value='".$i."'>&nbsp;".sprintf( $lang["adhoc"]["page"] , $i )."</div>";
								}
							break;
						case 3:
							$txt .= "<td valign='top' align='right'>";
							$txt .= "<div><img id='div_".$i."' style='border: 5px solid transparent; -webkit-transition: all 0.22s ease-out;' onclick='selectImage(".$i.")' src='".$file."' width='90px;'></div>";
							$txt .= "<div style='width: 90px; text-align: center;'><input style='display:none;' id='box_".$i."' class='p_page' type='checkbox' name='p_page' value='".$i."'>&nbsp;".sprintf( $lang["adhoc"]["page"] , $i )."</div>";
							break;
						}
				
					$txt .= "<div>&nbsp;</div></td>";
					$y++;
					if( $y == 3 ) { $txt .= "</tr>"; $y = 1; }
					}
				}
			else {
				$txt .= "<td valign='top' align='left'>";
				$txt .= "<div style='padding-left: 56px;'><input class='p_page' disabled type='checkbox' name='p_page' value='1'></div>";
				$txt .= "<div><img src='temp/".$_GET['file']."' width='130px;'></div>";
				}
			
			$txt .= '</table>';
			
			$result = $txt;
			}
		}
	
	if( $_GET['op'] == 'load_ad_c' ) {
		$text = '';
		$pub = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$debug = 'id="'.$_GET['id'].'"';
		$u_ads = sql_get( 'ads', 'pub_id="'.$pub[0][0].'" ORDER BY cast(`uploaded` as unsigned) ASC', '*' );
		for( $i = 0; $i < count($u_ads); $i++ ) {
			if( $u_ads[$i][8] != '' && $u_ads[$i][8] != '-' && $u_ads[$i][8] != 'Feltöltés alatt' && $u_ads[$i][8] != 'error' ) {
				if( $u_ads[$i][3] == '2/1' ) {
					$pages = explode( '-', $u_ads[$i][8] );
					$text .= '<div style="text-align: left;">';
						$text .= '<div style="float: left; padding-left: 10px;">'.sprintf( "%03d", $pages[0] ).'</div>';
						$text .= '<div style="float: left; padding-left: 10px;">'.strtoupper( $u_ads[$i][2] ).'</div>';
						$text .= '<div style="float: right; padding-right: 10px;">'.$u_ads[$i][3].'</div>';
						$text .= '<div style="clear: both;"></div>';
					$text .= '</div>';
					$text .= '<div style="text-align: left;">';
						$text .= '<div style="float: left; padding-left: 10px;">'.sprintf( "%03d", $pages[1] ).'</div>';
						$text .= '<div style="float: left; padding-left: 10px;">'.strtoupper( $u_ads[$i][2] ).'</div>';
						$text .= '<div style="float: right; padding-right: 10px;">'.$u_ads[$i][3].'</div>';
						$text .= '<div style="clear: both;"></div>';
					$text .= '</div>';								
					}
				else {
					$text .= '<div style="text-align: left;">';
						$text .= '<div style="float: left; padding-left: 10px;">'.sprintf("%03d", $u_ads[$i][8]).'</div>';
						$text .= '<div style="float: left; padding-left: 10px;">'.strtoupper( $u_ads[$i][2] ).'</div>';
						$text .= '<div style="float: right; padding-right: 10px;">'.$u_ads[$i][3].'</div>';
						$text .= '<div style="clear: both;"></div>';
					$text .= '</div>';							
					}
				}
			}
		
		$result = $text;
		}
	
	if( $_GET['op'] == 'set_ad_pages' ) {
		$id = explode( '_', $_GET['id'] );
		$id = $id[ count($id)-1 ];
		$t_sql = sql_get( 'ads', 'id="'.$id.'"', '*');
		$pub = sql_get( 'publications', 'id="'.$t_sql[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
		switch( $t_sql[0][3] ) {
			case '1/1':
				$type = 'F';
				break;
			case '2/1':
				$type = 'D';
				break;
			default:
				$type = 'P';
				break;
			}
			
		$outer_path = 'advertisements/'.strtoupper( $t_sql[0][2].'_'.strtoupper( $magazine[0][3] ).'_'.$pub[0][10].'_'.$type );	
		$txt = '<div><input type="hidden" id="hid" name="hid" value="'.$id.'">';
		if( $t_sql[0][3] == '2/1' ) {
			$txt .= '<input type="hidden" id="type" name="type" value="D">';
			}
		elseif( $t_sql[0][3] == '1/1' ) {
			$txt .= '<input type="hidden" id="type" name="type" value="F">';
			}
		else {
			$txt .= '<input type="hidden" id="type" name="type" value="P">';
			}


		if( $type == 'D' ) {
			$txt .= "<img src='".$outer_path."L_thumb.jpg' height='120px'><img src='".$outer_path."R_thumb.jpg' height='120px'>";
			}
		else {
			$txt .= "<img src='".$outer_path."_thumb.jpg' height='120px'>";
			}
		$txt .= "<table style='margin: auto;' cellspacing='0' cellpadding='0' width='200px'>";
		
		if( $_GET["pageNumbering"] == "American" ) {
			$txt .= "<tr>";
				$txt .= "<td align='left'>".$lang["publications"]["parts"]."</td>";
				$txt .= "<td align='left'>";
					$txt .= "<select name='parts' id='parts' style='margin-left: 10px;'>";
						$parts = sql_aget( "parts", "pub_id='".$pub[0][0]."'", "*" );
						for( $i = 0; $i < count( $parts ); $i++ ) {
							$l = array_search( $parts[$i]["name"],PARTS );
							$txt .= "<option value='".$parts[$i]["name"]."'>".$lang["parts"][$l]."</option>";
							}
					$txt .= "</select'>";
				$txt .= "</td>";
			$txt .= "</tr>";
			}
		else {
			$txt .= "<select name='parts' id='parts' style='visibility: hidden;'><option value='' selected></option></select>";
			}
			
		$txt .= "<tr><td align='left'>";
			$txt .= $lang['ads']['upload_pos'].':';
		$txt .= "</td><td align='left'>";
			$txt .= '<input type="text" name="pages" id="pages" '.( $_GET["pageNumbering"] == "American" ? '' : 'onchange="page_checker()"' ).' style="margin-left: 10px; width: 57px"></div>';
		$txt .= '</td></tr>';
		
		if( $type == 'P' ) {
			$xml = simplexml_load_file( '../'.$outer_path.'.xml' );
			$orient = explode( 'x', (string) $xml->results->dimensions );
			for( $a = 0; $a < count( $orient ); $a++ ) {
				$orient[$a] = intval( $orient[$a] );
				}
			if( intval( $orient[0] ) > intval( $orient[1] ) ) {
				$orient = 'fekvo';
				}
			else {
				$orient = 'allo';
				}
			
			$txt .= '<tr><td align="left">';
				$txt .= $lang['ads']['upload_place'].':';
			$txt .= "</td><td align='left'>";
				$txt .= '<select id="loc" name="loc" style="margin-left: 10px;">';
			
				switch( $t_sql[0][3] ) {
					case '1/2':
						if( $orient == 'fekvo' ) {
							$txt .= '<option value="uploaded_up">'.$lang['ads']['upload_up'].'</option>';
							$txt .= '<option value="uploaded_down">'.$lang['ads']['upload_down'].'</option>';
							}
						else {
							$txt .= '<option value="uploaded_left">'.$lang['ads']['upload_left'].'</option>';
							$txt .= '<option value="uploaded_right">'.$lang['ads']['upload_right'].'</option>';
							}
						break;
					case '1/4':
						$txt .= '<option value="uploaded_left_up">'.$lang['ads']['upload_left_up'].'</option>';
						$txt .= '<option value="uploaded_right_up">'.$lang['ads']['upload_right_up'].'</option>';
						$txt .= '<option value="uploaded_right_down">'.$lang['ads']['upload_right_down'].'</option>';
						$txt .= '<option value="uploaded_left_down">'.$lang['ads']['upload_left_down'].'</option>';
						break;
					case '2/3':
						if( $orient == 'fekvo' ) {
							$txt .= '<option value="uploaded_up">'.$lang['ads']['upload_up'].'</option>';
							$txt .= '<option value="uploaded_down">'.$lang['ads']['upload_down'].'</option>';
							}
						else {
							$txt .= '<option value="uploaded_left">'.$lang['ads']['upload_left'].'</option>';
							$txt .= '<option value="uploaded_right">'.$lang['ads']['upload_right'].'</option>';
							}
						break;
					case '1/3':
						if( $orient == 'fekvo' ) {
							$txt .= '<option value="uploaded_up">'.$lang['ads']['upload_up'].'</option>';
							$txt .= '<option value="uploaded_down">'.$lang['ads']['upload_down'].'</option>';
							}
						else {
							$txt .= '<option value="uploaded_left">'.$lang['ads']['upload_left'].'</option>';
							$txt .= '<option value="uploaded_right">'.$lang['ads']['upload_right'].'</option>';
							}
						break;			
					}
				$txt .= '</select>';
			$txt .= '</td></tr>';
			}
		
		if( $type == 'D' ) {
			$txt .= '</table><table width="100%"><tr><td width="25%" align="right">';
				$txt .= $lang['ads']['x'].":";
			$txt .= "</td><td width='90px' align='center'>";	
				$txt .= "<input type='text' name='x_L' onchange=\"adExtra('x_L')\" style='margin-left: 10px; width: 30px' value='0'>";
			$txt .= "</td><td width='90px' align='center'>";	
				$txt .= "<input type='text' name='x_R' onchange=\"adExtra('x_R')\" style='margin-left: 10px; width: 30px' value='0'>";
			$txt .= "</td><td width='25%' align='left'>";	
				$txt .= "mm";
			$txt .= '</td></tr>';
			
			$txt .= '<tr><td width="25%" align="right">';
				$txt .= $lang['ads']['y'].":";
			$txt .= "</td><td width='90px' align='center'>";	
				$txt .= "<input type='text' name='y_L' onchange=\"adExtra('y_L')\" style='margin-left: 10px; width: 30px' value='0'>";
			$txt .= "</td><td width='90px' align='center'>";	
				$txt .= "<input type='text' name='y_R' onchange=\"adExtra('y_R')\" style='margin-left: 10px; width: 30px' value='0'>";
			$txt .= "</td><td width='25%' align='left'>";	
				$txt .= "mm";
			$txt .= '</td></tr>';
			
			$txt .= '<tr><td width="25%" align="right">';
				$txt .= $lang['ads']['zoom'].":";
			$txt .= "</td><td width='90px' align='center'>";	
				$txt .= "<input type='text' name='zoom_L' onchange=\"adExtra('zoom_L')\" style='margin-left: 10px; width: 30px' value='0'>";
			$txt .= "</td><td width='90px' align='center'>";	
				$txt .= "<input type='text' name='zoom_R' onchange=\"adExtra('zoom_R')\" style='margin-left: 10px; width: 30px' value='0'>";
			$txt .= "</td><td width='25%' align='left'>";	
				$txt .= "mm";
			$txt .= '</td></tr>';		
			}
			
		if( $type == 'F' ) {
			$txt .= '<tr><td align="left">';
				$txt .= $lang['ads']['x'].":";
			$txt .= "</td><td align='left'>";	
				$txt .= "<input type='text' name='x' onchange=\"adExtra('x')\" style='margin-left: 10px; width: 30px' value='0'> mm";
			$txt .= '</td></tr>';	
			$txt .= '<tr><td align="left">';
				$txt .= $lang['ads']['y'].":";
			$txt .= "</td><td align='left'>";	
				$txt .= "<input type='text' name='y' onchange=\"adExtra('y')\" style='margin-left: 10px; width: 30px' value='0'> mm";
			$txt .= '</td></tr>';	
			$txt .= '<tr><td align="left">';
				$txt .= $lang['ads']['zoom'].":";
			$txt .= "</td><td align='left'>";	
				$txt .= "<input type='text' name='zoom' onchange=\"adExtra('zoom')\" style='margin-left: 10px; width: 30px' value='0'> mm";
			$txt .= '</td></tr>';
			}
		
		$txt .= '</table>';	
				
		if( $user[0][4] == "6" ) {
			$txt .= "<table style='margin: auto;' cellspacing='0' cellpadding='0' width='200px'><tr><td width='105px' align='left'>";
				$txt .= $lang["ads"]["caption"].":";
			$txt .= "</td><td align='left'>";	
				$txt .= "<input type='checkbox' name='caption' onchange=\"toggleDiv('caption_settings')\" value='0'>";
			$txt .= '</td></tr>';
			$txt .= '</table><div id="caption_settings" style="display: none;"><table style="margin: auto;" cellspacing="0" cellpadding="0" width="200px">';
			$txt .= '<tr><td width="90px" align="left">';
				$txt .= $lang["ads"]["captionPos"].":";
			$txt .= "</td><td align='left'>";	
				$txt .= "<select name='caption_poz'>";
					$txt .= "<option value='UpperLeft'>".$lang["ads"]["UpperLeft"]."</option>";
					$txt .= "<option value='UpperRight'>".$lang["ads"]["UpperRight"]."</option>";
					$txt .= "<option value='LowerLeft'>".$lang["ads"]["LowerLeft"]."</option>";
					$txt .= "<option value='LowerRight'>".$lang["ads"]["LowerRight"]."</option>";
				$txt .= "</select>";
			$txt .= '</td></tr>';
			$txt .= '<tr><td width="90px" align="left">';
				$txt .= $lang["ads"]["orient"].":";
			$txt .= "</td><td align='left'>";	
				$txt .= "<select name='text_poz'>";
					$txt .= "<option value='horizontal'>".$lang["ads"]["horizont"]."</option>";
					$txt .= "<option selected value='vertical'>".$lang["ads"]["vertical"]."</option>";
				$txt .= "</select>";
			$txt .= '</td></tr>';
			$txt .= '<tr><td width="90px" align="left">';
				$txt .= $lang["ads"]["textcolor"].":";
			$txt .= "</td><td align='left'>";	
				$txt .= "<select name='ccolor'>";
					$txt .= "<option value='black'>".$lang["ads"]["black"]."</option>";
					$txt .= "<option value='white'>".$lang["ads"]["white"]."</option>";
				$txt .= "</select>";
			$txt .= '</td></tr>';
			$txt .= '</table></div>';
			}

		$txt .= '<div style="padding-top: 10px;"><button class="panelButton buttonTag darkerbutton" id="send_ad" '.( $_GET["pageNumbering"] == "American" ? '' : 'disabled' ).' onclick="put_ad()" style="padding: 5px 20px 5px 20px;">'.$lang['ads']['upload_send'].'</button>&nbsp;';
		$txt .= '<button class="panelButton buttonTag darkerbutton" onclick="ad_menu_default()" style="padding: 5px 20px 5px 20px;">'.$lang['ads']['new_ad_cancel'].'</button></div></div>';
		$txt .= '<hr width="85%">';
		
		$result['title'] = sprintf( $lang["ads"]["upload"] , strtoupper( $t_sql[0][2] ) );
		$result['content'] = $txt;
		}
	
	if( $_GET['op'] == 'load_adverts' ) {
		$row = intval( intval($_GET['maxwidth'] )/154 );
		$divWidth = $row*154;
		
		$rights = array();
		$sql = sql_get( 'accounts', 'id="'.$_GET['user'].'"', "`publisher`, `group`, `advanced_publishers`");
		$user = sql_get( 'accounts', 'id="'.$_GET['user'].'"', "*");
		$r = sql_aget( 'user_groups', 'id="'.$sql[0][1].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		$id = '';

		$alter_pubs = anotherPubs( $user, 'publisher_id' );
		$alter = array();
		foreach( $alter_pubs as $a_pubs ) {
			$alter[] = $a_pubs[0];
			}
		
		if( isset( $_GET['id'] ) ) { $id = ' AND id="'.$_GET['id'].'"'; }	
		if( $_GET['pub'] != '' ) {
			$pub = sql_get( 'publications', 'id="'.$_GET['pub'].'"'.$id.'', '*' );
			if( $pub[0][0] == '' ) {
				for( $i = 0; $i < count( $alter ); $i++ ) {
					if( $pub[0][0] != '' ) break;
				
					$pub = sql_get( 'publications', 'publisher_id="'.$alter[$i].'" AND code="'.$_GET['pub'].'"'.$id.'', '*' );
					}
				}
			}
		else {
			$pub = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'"'.$id.' ORDER BY `code` ASC', '*' );
			if( $pub[0][0] == '' ) {
				for( $i = 0; $i < count( $alter ); $i++ ) {
					if( $pub[0][0] != '' ) break;
				
					$pub = sql_get( 'publications', 'publisher_id="'.$alter[$i].'"'.$id.' ORDER BY `code` ASC', '*' );
					}
				}
			}
		$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
		
		$txt = '<div class="loaded_ads" style="float: left; width:'.$divWidth.'px;">';
		if( $rights['ad_view'] ) {
			$tempAds = sql_get( 'ads', 'publisher="'.$pub[0][1].'" AND pub_id="'.$pub[0][0].'" ORDER BY `name` ASC', '*' );
			
			$counter = 1;
			$ads = array();
			$debug = '';
			//$debug = 'csempe / sor: '.$row.'<br>';
			for( $a = 0; $a < count($tempAds); $a++ ) {
				$debug .= "counter: ".$counter." (méret: ".$tempAds[$a][3].")<br>";
				if( $counter == $row ) {
					if( $tempAds[$a][3] == '2/1' ) {
						$debug .= 'Túllógó neve: '.$tempAds[$a][2].'<br>';
						$c = count( $tempAds )-1;
						$search = 1;
						$s = $a+1;
						while( $search ) {
							if( $tempAds[$s][3] != '2/1' ) {
								$search = 0;
								if( $tempAds[$s][2] != "" ) {
									$ads[] = $tempAds[$s];
									$tempAds = array_delete( $s, $tempAds );
									$counter = 2;
									}
								}
							$s++;
							if( $s > $c )
								$search = 0;
							}
						if( $tempAds[$a][2] != "" ) $ads[] = $tempAds[$a];
						}
					else {
						if( $tempAds[$a][2] != "" ) $ads[] = $tempAds[$a];
						}
					}
				else {
					if( $tempAds[$a][2] != "" ) $ads[] = $tempAds[$a];
					if( $tempAds[$a][3] == '2/1' )
						$counter++;
					}
						
				$counter++;			
				if( $counter > $row )
					$counter = 1;
				}
			$result = $debug;
			//$ads = sql_get( 'ads', 'publisher="'.$pub[0][1].'" AND pub_id="'.$pub[0][0].'" ORDER BY `name` ASC', '*' );
			for( $i = 0; $i < count( $ads ); $i++ ) {
				$status = $ads[$i][6];
				switch( $ads[$i][3] ) {
					case '1/1':
						$type = 'F';
						break;
					case '2/1':
						$type = 'D';
						break;
					default:
						$type = 'P';
						break;
					}
				
				$force = 0;
				if( $status > 1 ) {
					$path = '../advertisements/'.strtoupper( $ads[$i][2].'_'.strtoupper( $magazine[0][3] ).'_'.$pub[0][10].'_'.$type );
					$outer_path = 'advertisements/'.strtoupper( $ads[$i][2].'_'.strtoupper( $magazine[0][3] ).'_'.$pub[0][10].'_'.$type );
					if( $type == 'D' ) {
						if( is_file( $path.'L.xml' ) ) {
							$xml = simplexml_load_file( $path.'L.xml' );
							$errors['size'] = (string) $xml->results[0]->size;
							$errors['bleed'] = (string) $xml->results[0]->bleed;
							$errors['lowres'] = (string) $xml->results[0]->lowres;
							$errors['fontmissing'] = (string) $xml->results[0]->fontmissing;
							if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
								$okk1 = 1;
								}
							else {
								$okk1 = 0;
								}
							if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'true' && $errors['fontmissing'] == 'false' ) {
								$force = 1;
								}
							else {
								$force = 0;
								}	
							}
						if( is_file( $path.'R.xml' ) ) {
							$xml2 = simplexml_load_file( $path.'R.xml' );
							$errors['size'] = (string) $xml2->results[0]->size;
							$errors['bleed'] = (string) $xml2->results[0]->bleed;
							$errors['lowres'] = (string) $xml2->results[0]->lowres;
							$errors['fontmissing'] = (string) $xml2->results[0]->fontmissing;
							if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
								$okk2 = 1;
								}
							else {
								$okk2 = 0;
								}
							if( $force == 1 && $errors['size'] == 'size_ok' && $errors['lowres'] == 'true' && $errors['fontmissing'] == 'false' ) {
								$force = 1;
								}
							else {
								$force = 0;
								}								
							}
						if( $okk1 == 1 && $okk2 == 1 ) $okk = 1;
						else $okk = 0;
						}
					else {
						$xml = simplexml_load_file( $path.'.xml' );
						$errors['size'] = (string) $xml->results[0]->size;
						$errors['bleed'] = (string) $xml->results[0]->bleed;
						$errors['lowres'] = (string) $xml->results[0]->lowres;
						$errors['fontmissing'] = (string) $xml->results[0]->fontmissing;
						if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
							$okk = 1;		
							}
						else {
							$okk = 0;
							}		
						if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'true' && $errors['fontmissing'] == 'false' ) {
							$force = 1;
							}
						else {
							$force = 0;
							}
						}
			
					$finished = 0;
					if( $okk == 1 ) {
						if( $ads[$i][8] == 'Removing' ) {
							$text = "Removing...";
							}
						elseif( $ads[$i][8] == 'Feltöltés alatt' ) {
							$text = $lang['ads']['uploading'];
							}
						elseif( $ads[$i][8] == '' ) {
							$text = $lang['ads']['check_ok'];
							}
						elseif( $ads[$i][8] == 'error' ) {
							$text = $lang['ads']['upload_failed'];
							}
						else {
							$finished = 1;
							$text = $lang['ads']['upload_ok'];
							}
						}
					else {
						if( $errors['size'] != 'size_ok' ) {
							$text = $lang['ad_preview']['wrong_size_short'];
							}
						elseif( $errors['lowres'] != 'false' ) {
							$text = $lang['ad_preview']['lowres_short'];
							}
						elseif( $errors['fontmissing'] != 'false' ) {
							$text = $lang['ad_preview']['no_font_short'];
							}
						}
			
					if( $okk == 1 ) {
						if( $finished ) {
							$class = 'finished2';
							$uploaded = $lang['ads']['uploaded'];
							}
						else { 
							$class = 'accepted2';
							$uploaded = 'Beküldve';
							}
						}
					else {
						$class = 'rejected2';
						$uploaded = 'Beküldve';
						}
					
					if( $status == 4 ) {
						$force = 0;
						$okk = 1;
						$class = 'finished2';
						$text = $lang['ads']['force_uploaded'];
						}
					
					}
				elseif( $status == 0 ) {
					$path = '../advertisements/'.strtoupper( $ads[$i][2].'_'.strtoupper( $magazine[0][3] ).'_'.$pub[0][10].'_'.$type );
					$outer_path = 'advertisements/'.strtoupper( $ads[$i][2].'_'.strtoupper( $magazine[0][3] ).'_'.$pub[0][10].'_'.$type );

					$class = 'accepted3';
					$force = 1;
					$text = ( $ads[$i][8] == 'Feltöltés alatt' ? $lang['ads']['force_uploading'] : $lang['ads']['check_force'] );
					if( $type == 'D' ) {
						if( is_file( $path.'L.xml' ) ) {
							$xml = simplexml_load_file( $path.'L.xml' );
							}
						}
					else {
						$xml = simplexml_load_file( $path.'.xml' );
						}		
					}
				else {
					$text = $lang['ads']['checking'];
					$class = 'adChecking2';
					}
				
				$txt .= "<div class='adsTiles' force='".$force."' adid='".$ads[$i][0]."' adname='".$ads[$i][2]."' style='float:left; width: ";
				if( $ads[$i][3] == '2/1' ) 
					$txt .= 298;
				else
					$txt .= 144;
				$txt .= "px; margin-left: 10px; margin-top: 10px;'>";
					$txt .= '<div class="'.$class.'" style="color: #FFF !important; font-size: 12px; padding-left: 4px; padding-top: 3px; padding-bottom: 2px; padding-right: 4px;"><b><a style="color: #FFF !important;" href="?page=advertisement_preview&id='.$ads[$i][0].'&p=1">'.strtoupper( $ads[$i][2] ).'</a></b>&nbsp;&nbsp;'.$ads[$i][3]." </div>";
					$txt .= "<div style='height: 150px; ";
						$test = (string) $xml->description;
						$size = (string) $xml->results[0]->dimensions;
						//error_log( $size.", ".$test );
						if( $status > 1 || $status == 0 ) {
							if( $type == 'D' ) {
								$txt .= ( $ads[$i][10] > 0 ? "background-color: #F0F0F0" : "" )."'><div force='".$force."' style='border-right: 1px solid #ADADAD; border-left: 1px solid #ADADAD; padding-top: 4px;' allapot='".$ads[$i][8]."' status='".$okk."'";
								if( $rights['ad_upload'] ) {
									$txt .= "id='ad_img_".$ads[$i][0]."'";
									}
								$txt .= ">";
								if( is_file( "../".$outer_path."L_thumb.jpg" ) ) {
									$txt .= "<a href='?page=advertisement_preview&id=".$ads[$i][0]."&p=1'>";
										$txt .= "<img style='border: 1px solid #ADADAD; border-right: 0px;' src='".$outer_path."L_thumb.jpg' height='150px'>";
									$txt .= "</a>";
									}
								if( is_file( "../".$outer_path."R_thumb.jpg" ) ) {
									$txt .= "<a href='?page=advertisement_preview&id=".$ads[$i][0]."&p=2'>";
										$txt .= "<img style='border: 1px solid #ADADAD; border-left: 0px;' src='".$outer_path."R_thumb.jpg' height='150px'>";
									$txt .= "</a>";
									}
								$txt .= "</div>";
								}
							else {
								$style2 = '';
								if( $ads[$i][3] == '1/2' or  $ads[$i][3] == '1/3' ) {
									$s = explode( " x ", $size );
									
									if( floatval( $s[0] ) <= floatval( $s[1] ) ) {
										$style = "height: 150px;";
										}
									else {
										$style = "width: 130px;";
										$style2 = "padding-top: 30px; height: 120px !important;";
										}
									}
								else {
									$style = "height: 150px;";
									}
											
								$txt .= $style2."border-right: 1px solid #ADADAD; border-left: 1px solid #ADADAD; ".( $ads[$i][10] > 0 ? "background-color: #F0F0F0" : "" )."'><div style='padding-top: 4px;' allapot='".$ads[$i][8]."' force='".$force."' status='".$okk."' ";
								if( $rights['ad_upload'] ) {
									$txt .= "id='ad_img_".$ads[$i][0]."'";
									}
								$txt .= ">";
								if( is_file( "../".$outer_path."_thumb.jpg" ) ) {
									$txt .= "<a href='?page=advertisement_preview&id=".$ads[$i][0]."'>";
										$txt .= "<img src='".$outer_path."_thumb.jpg' style='".$style." border: 1px solid #ADADAD;'>";
									$txt .= "</a>";
									}
								$txt .= "</div>";
								}
							}
						else {
							$txt .= "'><div style='border-right: 1px solid #ADADAD; border-left: 1px solid #ADADAD; padding-top: 4px; height: 150px;'>";
								if( is_file( TRKPATH.'/advertisements/'.$ads[$i][2].'_preview.jpg' ) ) {
									$txt .= "<img src='advertisements/".$ads[$i][2]."_preview.jpg' style='opacity: 0.25; ".$style." border: 1px solid #ADADAD;'>";
									}
							$txt .= "</div>";
							}
					$txt .= "</div>";
					$txt .= "<div style='clear:both;'></div>";
					$txt .= "<div class='ad_details' style='border-right: 1px solid #ADADAD; border-left: 1px solid #ADADAD; border-bottom: 1px solid #ADADAD;".( $ads[$i][10] > 0 ? "background-color: #F0F0F0" : "" )."'>";
						$time = strtotime( $ads[$i][5] );
						switch( $user[0][17] ) {
							case 'en':
								setlocale(LC_ALL,'en_GB');
								break;
							case 'hu':
								setlocale(LC_ALL,'hu_HU');
								break;
							}
						$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %d." , $time ) );
						if( $status > 1 or $status == 0 ) {
							$txt .= $size.'<br>';
							}
						else {
							$txt .= '<br>';
							}						
						$txt .= $time.'<br>';
						$txt .= ucfirst( $text );
					$txt .= "</div>";
				$txt .= "</div>";
				}
			}
		$txt .= "<div style='clear:both;'></div></div>";
	//	$txt .= "<div style='float:left; width: 200px;';>asdfasdfsad</div>";
		$txt .= "<div style='clear:both;'></div>";
		$result = $txt;
		}

	if( $_GET['op'] == 'push_ad' ) {
		$ad = sql_get( 'ads', 'id="'.$_GET['ad_id'].'"', '*' );
		$issue = sql_get( 'publications', 'id="'.$ad[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
		$client = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
		$array = array();	
		
		$maxpage = $issue[0][6];
		$cover = 0;
		//Új
		//if( $client[0][1] == "Colorcom" ) {
			$array = array(
				"event" => "upload_ad",
				"client" => $client[0][1],
				"pubName" => $magazine[0][2],
				"jobCode" => $magazine[0][3],
				"issue" => $issue[0][10],
				"description" => strtoupper( $ad[0][2] ),
				"size" => str_replace( '/', '_', $ad[0][3] ),
				"pageNum" => $_GET['pages'],
				"offset1" => $_GET['extra'],
				"pageType" => $_GET['part'],
				);
				
			if( $ad[0][3] == '2/1' ) {
				$array["offset2"] = $_GET['extra2'];
				}			

			$array["caption"] = $_GET['caption'];
			$array["capPosition"] = ( $_GET['caption'] == "on" ) ? $_GET['cpos'] : "";
			$array["orientation"] = ( $_GET['caption'] == "on" ) ? $_GET['tpos'] : "";
			$array["capColor"] = ( $_GET['caption'] == "on" ) ? $_GET['ccolor'] : "";
			
			error_log( print_r( $array, true ) );
			
			if( $ad[0][3] == '2/1' or $ad[0][3] == '1/1' ) {
				$error = SwitchSend_TESZT( $array );
				}
			
			else {
				$names = array( 'ads_id', 'orient' );
				$values = array( $ad[0][0], $_GET['orient'] );
				sql_add( 'partial_ads', $names, $values );
				$array["event"] = 'upload_ad_results';
				$array["results"]["upload"] = 'successful';
				
				$counter = get_counter('..');
				$saveTo = '../message/message_'.$counter.'.xml';
				inc_counter('..');
				$myxml = array_to_xml( $array, 'eventComm' );
				$dom = new DOMDocument();
				$dom->preserveWhiteSpace = false;
				$dom->loadXML($myxml);
				$dom->formatOutput = true;
				file_put_contents($saveTo, $dom->saveXML() );
				}
		/*	}
		
		else {	
		//Régi
		$array["client"] = $client[0][1];
		$array["pubName"] = $magazine[0][2];
		$array["jobCode"] = $magazine[0][3];
		$array["issue"] = $issue[0][10];
		$array["event"] = 'upload_ad';
		$array["description"] = strtoupper( $ad[0][2] );
		$array["remark|1"] = str_replace( '/', '_', $ad[0][3] );
		$array["remark|2"] = $_GET['pages'];
		$array["remark|3"] = $_GET['extra'];
		if( $ad[0][3] == '2/1' ) {
			$array["remark|4"] = $_GET['extra2'];
			}
		
		if( $user[0][4] == "6" ) {
			$array["caption"]["state"] = $_GET['caption'];
			$array["caption"]["position"] = ( $_GET['caption'] == "on" ) ? $_GET['cpos'] : "";
			$array["caption"]["orientation"] = ( $_GET['caption'] == "on" ) ? $_GET['tpos'] : "";
			$array["caption"]["color"] = ( $_GET['caption'] == "on" ) ? $_GET['ccolor'] : "";
			}
		
		if( $ad[0][3] == '2/1' or $ad[0][3] == '1/1' ) {
			$counter = get_counter('..');
			$saveTo = 'C_Hotfolders/messages/message_'.$counter;
			inc_counter('..');
	
			$myxml = array_to_xml( $array, 'eventComm' );
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($myxml);
			$dom->formatOutput = true;
		
			$sftp = ftp_conn();
			$sftp->put( $saveTo.'.pdf', 'dummy.pdf', NET_SFTP_LOCAL_FILE );
			$sftp->put( $saveTo.'.xml', $dom->saveXML()  );	
			}
		else {
			$names = array( 'ads_id', 'orient' );
			$values = array( $ad[0][0], $_GET['orient'] );
			sql_add( 'partial_ads', $names, $values );
			$array["event"] = 'upload_ad_results';
			$array["results"]["upload"] = 'successful';
			
			$counter = get_counter('..');
			$saveTo = '../message/message_'.$counter.'.xml';
			inc_counter('..');
			$myxml = array_to_xml( $array, 'eventComm' );
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($myxml);
			$dom->formatOutput = true;
			file_put_contents($saveTo, $dom->saveXML() );
			}
			
			}*/
			
		$names = array( 'uploaded' );
		$values = array( 'Feltöltés alatt' );
		$command = '';
		for( $i = 0; $i < count( $names ); $i++ ) {
			$command .= $names[$i].'=\''.$values[$i].'\'';
			if( $i < count( $names )-1 ) {
				$command .= ', ';
				}
			}		
		sql_update( 'ads', $command, 'id=\''.$_GET['ad_id'].'\'' );		
		$result = 'ok';
		}

	if( $_GET['op'] == 'get_sizes' ) {
		$code = sql_get( 'publications', 'id="'.$_GET['id'].'"', 'magazine_id' );
		$code = sql_get( 'magazines', 'id="'.$code[0][0].'"', 'code' );

		$txt = '<select id="sizer" name="size" onchange="$(\'#job_code\').keyup()">';
		$txt .= "<option selected value=''>".$lang["ads"]["choose"]."</option>";
		$txt .= "<option value='2/1'>2/1</option>";
				
		$ads = collectFromXml( '../xml/'.PMD.'.xml', $code[0][0], 'AdSizes', 'value' );
		$ads = $ads['AdSizes'];
		$sizes = array();
		if( $ads != "" ) {
			foreach( $ads as $val ) {
				$temp = explode( " ", $val );
			
				if( !in_array( $temp[0], $sizes ) ) {
					$sizes[] = $temp[0];
					}
				}
			}
		sort( $sizes );
		
		for( $i = 0; $i < count( $sizes ); $i++ ) {
			$txt .= "<option value='".$sizes[$i]."' ";
			$txt .= ">".$sizes[$i]."</option>";
			}
		$txt .= '</select>';
		
		$noadd = "";
		if( $_GET['finished'] != "1" ) {
			if( empty( $code[0][0] ) ) {
				$noadd = "<div style='font-size: 20px; padding-top: 95px; color: red;'>".$lang["ads"]["noissue"]."</div>";
				}
			elseif( count( $sizes ) == 0 ) {
				$noadd = "<div style='font-size: 20px; padding-top: 95px; color: red;'>".$lang["ads"]["noads"]."</div>";
				}
			}
		$result = array( $txt, $noadd );
		}

	if( $_GET['op'] == 'add_ad' ) {
		// ad_sizes (DB) is the source of truth for a publication's ad sizes -
		// the PMD's <AdSizes> block is a generated projection of it, rebuilt
		// in full by regenerateAdSizesInPmd() rather than hand-patched here,
		// so the file can't drift out of sync with the table the way the old
		// per-value XML mutation could.
		$mag = sql_get( 'magazines', 'code="'.$_GET['code'].'"', 'id' );

		$names = array( 'magazine_id', 'size', 'orient', 'cover', 'width', 'height' );
		$values = array( $mag[0][0], $_GET['size'], $_GET['orient'], $_GET['cover'], $_GET['width'], $_GET['height'] );
		sql_add( 'ad_sizes', $names, $values );

		$pmdName = regenerateAdSizesInPmd( $_GET['code'] );

		$array = array(
			"event" => "xml_data",
			// switchClientAllowed() (the DEV-environment gate) identifies the
			// client from jobCode/Code/code and silently blocks the send if
			// none is present - without this, every ad size added here got
			// written to the local PMD files but never reached the external
			// Switch backend, so its copy of the PMD stayed stale and ad
			// checking kept validating against the old size list.
			"jobCode" => $_GET['code'],
			);

		$file = array(
			"name" => $pmdName,
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );

		$return = 'ok';
		}

	if( $_GET['op'] == 'remove_ad' ) {
		$ad = sql_get( 'ads', 'id="'.$_GET['id'].'"', '*' );
			
		if( $_GET['type'] == 'partial' ) {
			$ad = sql_get( 'ads', 'id="'.$_GET['id'].'"', '*' );
			$p_ad = sql_get( 'partial_ads', 'ads_id="'.$_GET['id'].'"', '*' );
			for( $i = 0; $i < count( $p_ad ); $i++ ) {
				sql_delete( 'partial_ads', 'id="'.$p_ad[$i][0].'"' );
				}
			sql_update( 'ads', 'uploaded=""', 'id="'.$ad[0][0].'"' );
			$result = $ad[0][0];
			}
		if( $_GET['type'] == 'physical' ) {
			$ad = sql_get( 'ads', 'id="'.$_GET['id'].'"', '*' );
			$issue = sql_get( 'publications', 'id="'.$ad[0][1].'"', '*' );
			$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
			$client = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
			$array = array();
	
			$array = array(
				"event" => "delete_ad",
				"client" => $client[0][1],
				"jobCode" => $magazine[0][3],
				"issue" => $issue[0][10],
				"description" => strtoupper( $ad[0][2] ),
				"remark" => str_replace( '/', '_', $ad[0][3] ),
				);
			
			$error = SwitchSend_TESZT( $array );

			$array["client"] = $client[0][1];
			$array["jobCode"] = $magazine[0][3];
			$array["issue"] = $issue[0][10];
			$array["event"] = 'delete_ad';
			$array["description"] = strtoupper( $ad[0][2] );
			$array["remark"] = str_replace( '/', '_', $ad[0][3] );

			$myxml = array_to_xml( $array, 'eventComm' );
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($myxml);
			$dom->formatOutput = true;
			
			$counter = get_counter('..');
			$target = '../uploads/orders/message_'.$counter.'.xml';
			inc_counter('..');
			if( file_put_contents( $target , $dom->saveXML() ) ) {
				$p_ad = sql_get( 'partial_ads', 'ads_id="'.$_GET['id'].'"', '*' );
				for( $i = 0; $i < count( $p_ad ); $i++ ) {
					sql_delete( 'partial_ads', 'id="'.$p_ad[$i][0].'"' );
					}
				sql_delete( 'ads', 'id="'.$ad[0][0].'"' );
				switch( $ad[0][3] ) {
					case '2/1':
						$type = 'D';
						break;
					case '1/1':
						$type = 'F';
						break;
					default:
						$type = 'P';
						break;
					}
				$file_name = strtoupper( $ad[0][2] ).'_'.$magazine[0][3].'_'.$issue[0][10].'_'.$type;
				$dir = '../advertisements';
				$dirFiles = load_dir_files( $dir, $file_name );
				for( $y = 0; $y < count( $dirFiles ); $y++ ) {
					unlink( $dir.'/'.$dirFiles[$y] );
					}
				$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
				$values = array( $_SESSION['intra_user'], 'deleteAD', $issue[0][1], $magazine[0][0], $issue[0][10], $ad[0][2], time() );
				sql_add( 'action_log', $names, $values );					
				}
			$result = $dirFiles;
			}
		if( $_GET['type'] == '' ) {
			// Ad-size removal: $_GET['id'] is a real ad_sizes.id (see
			// load_ads below), not the old "magazineCode_index" compound
			// string used for XML positional removal.
			$size = sql_get( 'ad_sizes', 'id="'.$_GET['id'].'"', 'magazine_id' );
			if( !empty( $size[0][0] ) ) {
				$mag = sql_get( 'magazines', 'id="'.$size[0][0].'"', 'code' );
				sql_delete( 'ad_sizes', 'id="'.$_GET['id'].'"' );

				$pmdName = regenerateAdSizesInPmd( $mag[0][0] );
				if( $pmdName ) {
					$array = array(
						"event" => "xml_data",
						"jobCode" => $mag[0][0],
						);
					$file = array(
						"name" => $pmdName,
						"path" => "xml",
						);
					SwitchSend_TESZT( $array, $file );
					}
				}

			$result = 'ok';
			}
		}

	if( $_GET['op'] == 'load_ads' ) {
		$txt = '';

		$mag = sql_get( 'magazines', 'code="'.$_GET['code'].'"', 'id' );
		$ads = sql_aget( 'ad_sizes', 'magazine_id="'.$mag[0][0].'"', '*' );

		for( $i = 0; $i < count( $ads ); $i++ ) {
			if( fmod( $i, 2 ) == 0 ) { $class = 'one'; }
			else { $class = 'two'; }

			$txt .= "<tr id='ad_size_".$ads[$i]['id']."'><td colspan='2' align='left' height='28px'>";
				$txt .= "<div style='float:left;'>";
					$txt .= $ads[$i]['size']." ".$lang["ads"][$ads[$i]['orient']].", ".$lang["ads"][$ads[$i]['cover']].": ".$ads[$i]['width']." x ".$ads[$i]['height']." mm";
				$txt .= "</div>";
				if( $rights["ad_sizes"] ) {
					$txt .= "<div style='float:right;'>";
						$txt .= "<img onclick=\"remove_ad(".$ads[$i]['id'].")\" style='cursor: pointer;' src='../images/trash.png' height='18px'>";
					$txt .= "</div>";
					}
			$txt .= "</td></tr>";
			}

		$result = $txt;
		}
	
	if( $_GET['op'] == 'modify' ) {
		$names = array( 'pages', 'deadline' );
		$values = array( $_GET['page'], $_GET['dl'] );
		$command = '';
		for( $i = 0; $i < count( $names ); $i++ ) {
			$command .= $names[$i].'=\''.$values[$i].'\'';
			if( $i < count( $names )-1 ) {
				$command .= ', ';
				}
			}		
		sql_update( 'publications', $command, 'id=\''.$_GET['id'].'\'' );
	
		$pub = sql_get( 'publications', 'id="'.$_GET['id'].'"', 'magazine_id' );
		$pubs = sql_get( 'publications', 'magazine_id="'.$pub[0][0].'" ORDER BY `id` ASC', '*' );
		
		$result = $pubs;
		}
	
	if( $_GET['op'] == 'modify_pub' ) {
		$pub = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
	
		$txt = "<form method='POST' action='' id='mod_form'><table width='800px' id='job_names' cellspacing='0' cellpadding='0'>";
		$txt .= "<thead><tr><td colspan='2' style='padding-left: 10px;' class='left top right bottom2' align='left' height='28px'>„".$pub[0][10]."” kódú megjelenés módosítása</td></tr></thead>";
		$txt .= "<tbody>";
			$txt .= "<tr>";
				$txt .= "<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Terjedelem</td>";
				$txt .= "<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress=\"return isNumberKey(event)\" type='text' id='pages' name='pages' value='".$pub[0][6]."'></td>";
			$txt .= "</tr>";
			$txt .= "<tr>";
				$txt .= "<td style='padding-left: 10px;' class='left bottom' align='left' align='left' width='50%' height='28px'>Határidő</td>";
				$txt .= "<td class='right bottom' align='left' style='padding-left: 2px;'><input readonly class='datepicker' type='text' id='deadline' name='deadline' value='".$pub[0][11]."'></td>";
			$txt .= "</tr>";
			$txt .= "<tr>";
				$txt .= "<td style='background: #E6E8EB;' class='left right bottom' colspan='2' height='34px'>";
					$txt .= "<input type='hidden' id='p_ID' value='".$pub[0][0]."'>";
					$txt .= "<button id='mod' onclick='send_mod_pub(); return false;' style='padding: 5px 20px 5px 20px;'>Módosít</button>";
					$txt .= "<button onclick='$(\"#modify\").hide(\"slow\"); return false;' id='close' style='padding: 5px 20px 5px 20px;'>Mégse</button>";
					$txt .= "</td>";
			$txt .= "</tr>";
		$txt .= "</tbody>";
		$txt .= "</table></form>";
		
		
		$result = $txt;
		}
		
	if( $_GET['op'] == 'create_pub' ) {
		$magazine = sql_get( 'magazines', 'id="'.$_GET['m_id'].'"', '*' );	
		$pubs = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'" ORDER BY `id` ASC', '*' );
		
		$_SESSION['M_ID'] = $magazine[0][0];
		
		$result = array( $magazine[0], $pubs );
		}
		
	if( !isset( $_GET['op'] ) ) {
		$internal = $_POST['i_code'].'|'.$_POST['i_base'].'|'.$_POST['i_variable'].'|'.$_POST['i_padding'].'|'.$_POST['i_delimiter'].'|'.$_POST['i_aname'].'|'.$_POST['i_adelimiter'];
		$upload = $_POST['u_code'].'|'.$_POST['u_base'].'|'.$_POST['u_variable'].'|'.$_POST['u_padding'].'|'.$_POST['u_delimiter'].'|'.$_POST['u_var_del'].'|'.$_POST['u_aname'].'|'.$_POST['u_adelimiter'];
		$output = $_POST['o_code'].'|'.$_POST['o_base'].'|'.$_POST['o_variable'].'|'.$_POST['o_padding'].'|'.$_POST['o_delimiter'].'|'.$_POST['o_var_del'].'|'.$_POST['o_aname'].'|'.$_POST['o_adelimiter'];
		$p_id = sql_get( 'magazines', 'id="'.$_POST['m_id'].'"', 'publisher_id' );
		$p_id = $p_id[0][0];
		
		$names = array( 'publisher_id', 'magazine_id', 'internal', 'upload', 'output', 'pages', 'uploadable', 'precounter', 'code', 'deadline' );
		$values = array( $p_id, $_POST['m_id'], $internal, $upload, $output, $_POST['page_nr'], $_POST['uploadable'], 0, $_POST['job_code'], $_POST['dl'] );
		$id = sql_add( 'publications', $names, $values );
		
		$names = array( 'pub_id', 'name', 'place', 'color' );
		$counter = explode( ',', $_POST['counter'] );
		for( $i = 0; $i < count( $counter ); $i++ ) {
			$values = array( $id, $_POST['part'.$counter[$i].'_name'], $_POST['part'.$counter[$i].'_place'], $_POST['part'.$counter[$i].'_color'] );
			sql_add( 'parts', $names, $values );
			}
		
		$counter = get_counter('..');	
		toSwitch( 'created' , 'publications|'.$id, 'C_Hotfolders/messages/message_'.$counter, 'eventComm' );
		inc_counter('..');
		
		$mag = sql_get( 'magazines', 'id="'.$_POST['m_id'].'"', 'code' );
		toSwitch( 'new_publication' , 'publications|'.$id, 'C_database/'.$mag[0][0].'_'.$_POST['job_code'], 'issueData' );
		
		$pubs = sql_get( 'publications', 'magazine_id="'.$_POST['m_id'].'" ORDER BY `id` ASC', '*' );

		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
		$values = array( $_SESSION['intra_user'], 'newIssue', $p_id, $_POST['m_id'], $_POST['job_code'], '', time() );
		sql_add( 'action_log', $names, $values );
		
		$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $mag[0][0] ) {
					break;
					}
				}
			}
				
		$result = array( $pubs  );
		}
	
	print json_encode( $result );
	
?>
