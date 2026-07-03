<?PHP
 session_start(); 

	include_once( '../engine/connect.php' );
	include_once( '../engine/engine.php' );
	include_once( '../engine/xml_handler.php' );
	
	include_once('lang/en.php');
	
	$user = sql_get( 'users', 'id=\''.$_SESSION['intra_user'].'\'', '*' );
	
	if( $_GET['type'] == 'ad' ) {
		$job_data = sql_get( "ads", "id='".$_GET['job_id']."'", "*");
		$pub = sql_get( 'publications', 'id="'.$job_data[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );		
		switch( $job_data[0][3] ) {
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

		$file_name = strtoupper( $job_data[0][2] ).'_'.$magazine[0][3].'_'.$pub[0][10].'_'.$type;
		
		$dirFiles = load_dir_files( 'advertisements', $file_name );
		sort( $dirFiles );
		
		if( $type == 'D' ) {
			if( $_GET['page'] == '1' ) {
				$img = 'advertisements/'.$file_name.'L_check.jpg';
				$xml = simplexml_load_file( 'advertisements/'.$file_name.'L.xml' );
				}
			if( $_GET['page'] == '2' ) {
				$img = 'advertisements/'.$file_name.'R_check.jpg';
				$xml = simplexml_load_file( 'advertisements/'.$file_name.'R.xml' );
				}
			}
		else {
			$img = 'advertisements/'.$file_name.'_check.jpg';
			$xml = simplexml_load_file( 'advertisements/'.$file_name.'.xml' );
			}
		
		$info = '';
		$info .= $lang['ads']['size'].': ';
		switch( $type ) {
			case 'F':
				$info .= $lang['ad_preview']['full'];
				$type2 = $lang['ad_preview']['full'];
				break;
			case 'D':
				$info .= $lang['ad_preview']['double'];
				$type2 = $lang['ad_preview']['double'];
				break;
			default:
				$info .= $lang['ad_preview']['partial'];
				$type2 = $lang['ad_preview']['partial'];
				break;
			}
		
		$remark = (string) $xml->remark;
		$errors['size'] = (string) $xml->results[0]->size;
		$errors['bleed'] = (string) $xml->results[0]->bleed;
		$errors['lowres'] = (string) $xml->results[0]->lowres;
		$errors['fontmissing'] = (string) $xml->results[0]->fontmissing;
		$e_count = 0;
		if( $errors['size'] == 'wrong_size' or $errors['lowres'] == 'true' or $errors['fontmissing'] == 'true' ) {
			$error = 1;
			$e = "<ul class='list'>";
			}
		else {
			if( $errors['bleed'] == 'no_bleed' ) { 
				$info .= '<br>';
				$error = 0;
				}
			}
		
		if( $errors['size'] == 'wrong_size' ) {
			$e .= "<li>".$lang['ad_preview']['wrong_size'].".</li>";
			$e_count++;
			}

		if( $errors['lowres'] == 'true' ) {
			if( $type == 'D' ) {
				if( $_GET['page'] == '1' ) {
					$img2 = 'advertisements/'.$file_name.'L_lowres.jpg';
					}
				if( $_GET['page'] == '2' ) {
					$img2 = 'advertisements/'.$file_name.'R_lowres.jpg';
					}
				}
			else {
				$img2 = 'advertisements/'.$file_name.'_lowres.jpg';
				}
			$e .= "<li>".$lang['ad_preview']['lowres'].".<span id='hide_low_res' onclick=\"toggle_lowres('hide', '".$img."' )\" style='display:none;'>&nbsp;".$lang['ad_preview']['lowres_hide']."</span><span id='show_low_res' onclick=\"toggle_lowres('show', '".$img2."' )\">&nbsp;".$lang['ad_preview']['lowres_show']."</span></li>";
			$e_count++;
			}
		if( $errors['fontmissing'] == 'true' ) {
			$e .= "<li>".$lang['ad_preview']['no_font'].".<br></li>";
			$e_count++;
			}			

		if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
			$okk1 = 1;
			}
		else {
			$okk1 = 0;
			}
		$finished = 0;
		if( $okk1 == 1 ) {
			if( $job_data[0][8] == 'Feltöltés alatt' ) {
				$text = $lang['ads']['uploading'];
				}
			elseif( $job_data[0][8] == '' ) {
				$text = $lang['ads']['check_ok'];
				}
			elseif( $job_data[0][8] == 'error' ) {
				$text = $lang['ads']['upload_failed'];
				}
			else {
				$finished = 1;
				$text = $lang['ads']['upload_ok'];
				}
			}
		else {
			$text = $lang['ads']['check_failed'];
			}

		$info .= "<br>".$lang['ad_preview']['pic_size'].": ".(string) $xml->results[0]->dimensions."<br>";
		$info .= $lang['ad_preview']['upload_date'].": ".$job_data[0][5]."<br>";
		$info .= $lang['ads']['status'].": ".$text;
		if( $finished ) {
			$info .= " a(z) <span class='page_echo'>".$job_data[0][8].". oldalra";
			if( $type == 'P' ) {
				$ad_detail = sql_get( 'partial_ads', 'ads_id="'.$job_data[0][0].'"', '*' );
				$info .= " ( ".$ad_detail[0][2]." )";
				}
			$info .= "</span><br>";
			}
		
		if( $e_count > 1 ) { $info .= '<br><br>'.$lang['ad_preview']['errors'].':<br>'; }
		if( $e_count == 1 ) { $info .= '<br><br>'.$lang['ad_preview']['error'].':<br>'; }
		$info .= $e.'</ul>';

		if( $errors['size'] != 'wrong_size' && $errors['lowres'] != 'true' && $errors['fontmissing'] != 'true' ) {
			$info .= "<br><br>".$lang['ad_preview']['accepted'];
			if( $errors['bleed'] != 'bleed_ok' )
				if( $errors['bleed'] == "no_bleed" ) {
					$info .= "<br><span style='color: #CF2727;'>".$lang['ad_preview']['no_bleed']."</span>";
					}
				else {
					$info .= "<br>".$lang['ad_preview']['check_bleed'];
					}
			
			if( $errors['size'] != 'size_ok' ) {
				
				}
			}
		
		$code = sql_get( 'magazines', 'id="'.$pub[0][2].'"', 'code' );
		$ads = collectFromXml( 'xml/'.PMD.'.xml', $code[0][0], 'AdSizes', 'value' );
		$ads = $ads['AdSizes'];
		
		if( $errors['size'] != 'size_ok' ) {
			$info .= "Lehetséges ".$type2." hirdetések:<br>";
			for( $i = 0; $i < count( $ads ); $i++ ) {
				$temp = explode( " ", $ads[$i] );
			
				if( $remark != '1_1' and $remark != '2_1' ) {
					if( $temp[0] != '1/1' and $temp[0] != '2/1' ) {
						$info .= "- ".$temp[0]." ".$lang["ads"][substr($temp[1], 0, -1)].", ".$lang["ads"][substr($temp[2], 0, -1)].": ".$temp[3]." x ".$temp[5]." mm<br>";
						}
					}
				elseif( $remark == '1_1' ) {
					if( $temp[0] == '1/1' ) {
						$info .= "- ".$temp[0]." ".$lang["ads"][substr($temp[1], 0, -1)].", ".$lang["ads"][substr($temp[2], 0, -1)].": ".$temp[3]." x ".$temp[5]." mm<br>";
						}
					}
				elseif( $remark == '2_1' ) {
					if( $temp[0] == '2/1' ) {
						$info .= "- ".$temp[0]." ".$lang["ads"][substr($temp[1], 0, -1)].", ".$lang["ads"][substr($temp[2], 0, -1)].": ".$temp[3]." x ".$temp[5]." mm<br>";
						}
					}
				}
			}
			
		if( $e_count > 1 ) {
			$footer = $lang['ad_preview']['rejected1'];
			if( $e_count > 1 ) { $footer .= ' '.strtolower( $lang['ad_preview']['errors'] ).' '; }
			else $footer .= ' '.strtolower( $lang['ad_preview']['error'] ).' ';
			$footer .= $lang['ad_preview']['rejected2'].'.';
			if( $errors['bleed'] != 'bleed_ok' )
				$footer .= '<br>'.$lang['ad_preview']['check_bleed'];
			}
		$info .= '<br><br>'.$footer.'';
		}
		
	$result = array(
		"img" => $img,
		"title" => $title,
		"footer" => $footer,
		"info" => $info,
		"hiba_tomb" => $sub_errors,
		"debug" => $debug
	);
	
	print json_encode( $result );

?>