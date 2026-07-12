<?PHP
header('Content-Type: text/html; charset=utf-8');
include("/var/www/server_constans.php");
include("/var/www/html/engine/constans.php");
include_once("/var/www/html/engine/build_info.php");

function GetHighestPageNumber( $xml, $pub ) {
	$pn = 0;
	
	if( $xml->FlatplanStages == "1" ) {
		$pages = sql_aget( "pageinfo", "code='".$pub[0][10]."' AND fin='0' ORDER BY `page` DESC", "*" );
		$pn = $pages[0]["page"];
		}
	
	return $pn;
	}

function GetSwitchFlows2() {
	$result = array();
	$flows = sql_aget( "switch_flows", "1", "*" );
	
	for( $i = 0; $i < count( $flows ); $i++ ) {
		$result[ $flows[$i]["name"] ] = array(
			"flowid" => $flows[$i]["flowid"],
			"objectid" => $flows[$i]["objectid"],
			);
		}
	
	return $result;
	}
	
function GetSwitchFlows() {
	$result = array();
	$flows = sql_aget( "switch_flows", "1", "*" );
	
	for( $i = 0; $i < count( $flows ); $i++ ) {
		$result[ $flows[$i]["name"] ] = $flows[$i]["flowid"]."_".$flows[$i]["objectid"];
		}
	
	return $result;
	}

function partDetect( $pubid, $page, $return = "color" ) {
	$rval = "";
	$pub = sql_aget( "publications", "id='".$pubid."'", "*" );
	$mag = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
	
	if( $pub[0]["publisher_id"] == 0 ) {
		
		$xml_path = TRKPATH.'/xml/'.$pub[0]["code"].'.xml';
		}
	else {
		$xml_path = TRKPATH.'/xml/'.$mag[0]["code"].'_'.$pub[0]["code"].'.xml';
		}

	$xml = simplexml_load_file( $xml_path );
	
	//echo "<pre>";
	
	if( $page > 0 ) {	
		$xpath = $xml->xpath('/issueData/parts');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->part ); $x++ ) {
				$pages = array();
				//var_dump( $temp->part[$x] );
				
				$places = explode( ",", $temp->part[$x]->place ); 
				//var_dump( $places );
				for( $i = 0; $i < count( $places ); $i++ ) {
					$p = explode( "-", $places[$i] );
					if( strpos( $places[$i], "-" ) !== false ) {
						for( $y = $p[0]; $y <= $p[1]; $y++ ) {
							$pages[] = (string) $y;
							}
						}
					else {
						for( $y = 0; $y < count( $p ); $y++ ) {
							$pages[] = $p[$y];
							}					
						}
					}		
				//var_dump( $pages );
				if( in_array( $page, $pages ) ) {
					$rval = (string) $temp->part[$x]->$return;
					break;
					}
				//echo "<br><br>";
				}
			}	
		//echo "</pre>";
		if( empty( $rval ) ) {
			$rval = "FOGRA_39";
			}
		}
	else {
		$rval = "FOGRA_39";
		}

	return $rval;
	}

// Resolves the actual ICC profile filename R3 should use for a given page:
// partDetect() finds which Part the page belongs to and returns that
// Part's color standard name (e.g. "FOGRA_51"); this then looks up the
// matching file in color_standards, the single source of truth for
// standard-name -> ICC-file mappings (see client/plugins/
// colorstandardsApply.php). Falls back to "<name>.icc" if the standard
// isn't in the table yet, so a Part referencing a not-yet-defined standard
// doesn't hard-fail.
function resolveIccProfile( $pubId, $page ) {
	$colorName = partDetect( $pubId, $page, "color" );
	return resolveIccProfileByName( $colorName );
	}

function resolveIccProfileByName( $colorName ) {
	if( empty( $colorName ) ) {
		$colorName = "FOGRA_39";
		}

	$standard = sql_get( "color_standards", 'name="'.$colorName.'"', "icc_file" );
	if( !empty( $standard[0][0] ) ) {
		return $standard[0][0];
		}

	return $colorName.".icc";
	}

// <option> list for every defined color standard, for the various
// Part/Publication color-standard dropdowns across the app - color_standards
// is the one place new standards get added, so every dropdown should render
// from this instead of keeping its own hardcoded list.
// Double-quoted HTML attributes on purpose: several call sites embed this
// inside a single-quoted JS string literal, and single-quoted attributes
// here would terminate that string early.
function colorStandardOptions( $selected = "", $class = "" ) {
	$txt = "";
	$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "name" );
	for( $i = 0; $i < count( $standards ); $i++ ) {
		$txt .= "<option ";
		if( $class != "" ) $txt .= "class=\"".$class."\" ";
		$txt .= "value=\"".$standards[$i][0]."\"";
		if( $standards[$i][0] == $selected ) $txt .= " selected";
		$txt .= ">".str_replace( "_", " ", $standards[$i][0] )."</option>";
		}
	return $txt;
	}

// Verifies a plaintext password against a stored hash. Stored hashes are
// either a modern password_hash() output, or a legacy unsalted MD5 hash
// (the format used everywhere before this fix). On a successful legacy
// match, the hash is transparently upgraded to password_hash() so accounts
// migrate off MD5 as their users log in, without a separate migration step.
function checkPassword( $plain, $hash, $accountId = null ) {
	if( password_get_info( (string) $hash )['algo'] !== null ) {
		return password_verify( $plain, (string) $hash );
		}

	if( hash_equals( (string) $hash, md5( $plain ) ) ) {
		if( $accountId !== null ) {
			$newHash = password_hash( $plain, PASSWORD_DEFAULT );
			sql_update( 'accounts', "pass='".$newHash."'", "id='".(int) $accountId."'" );
			}
		return true;
		}

	return false;
	}

// Persistent "remember me" login. The intra_user cookie used to hold the
// raw account id, which any client could set unverified to impersonate any
// user. Instead we issue a random unguessable token, store only its hash
// server-side, and put the raw token in the cookie - presenting the cookie
// proves nothing unless it matches a stored hash.
function issueRememberToken( $accountId ) {
	$token = bin2hex( random_bytes( 32 ) );
	sql_update( 'accounts', "remember_token='".hash( 'sha256', $token )."'", "id='".(int) $accountId."'" );
	setcookie( 'intra_user', $token, time() + (106400), "/" );
	}

function resolveRememberToken( $token ) {
	$hash = hash( 'sha256', $token );
	$check = sql_get( 'accounts', "remember_token='".$hash."'", 'id' );
	return !empty( $check[0][0] ) ? $check[0][0] : null;
	}

function clearRememberToken( $accountId ) {
	sql_update( 'accounts', "remember_token=NULL", "id='".(int) $accountId."'" );
	}

function securityAlert( $uname, $pass ) {
	$subject = "Sikertelen Tracker bejelentkezés";
	
	$body = "Sikertelen Tracker bejelentkezés:<br>";
	$body .= "Beírt felhasználónév: ".$uname."<br>";
	$body .= "Beírt jelszó: ".$pass."<br>";
	
	$res = "";
	global $con;
	$check = sql_aget( "accounts", "name='".mysqli_real_escape_string( $con, $uname )."' AND type!='adhoc'", "*" );
	if( !empty( $check[0]["id"] ) ) {
		if( !checkPassword( $pass, $check[0]["pass"] ) ) {
			$res = "Érvénytelen jelszó";
			}
		}
	else {
		$res = "Nem létező felhasználónév";
		}
	$body .= "Eredmény: ".$res."<br>";
	$body .= "<br>";
	$body .= "Egyéb adatok:<br>";
	$body .= "IP cím: ".$_SERVER['HTTP_X_FORWARDED_FOR']."<br>";
	//$body .= "IP cím: ".$_SERVER['REMOTE_ADDR']."<br>";
	$body .= "Böngésző: ".$_SERVER['HTTP_USER_AGENT']."<br>";
	
	$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
	produkcioSendmail( $subject, $body, $to );
	
	$to = "peter@colorcom.hu|peter@colorcom.hu";
	produkcioSendmail( $subject, $body, $to );
	}

function removeTempUsers( $pid ) {
	$users = sql_aget( "accounts", "usertype='Temp' AND temppubid='".$pid."'", "*" );
	for( $u = 0; $u < count( $users ); $u++ ) {
		sql_delete( "accounts", "id='".$users[$u]["id"]."'" );
		sql_delete( "adhoc_hotlinks", "user_id='".$users[$u]["id"]."'" );
		}
	}

function removeUserMailPMD( $email ) {
	$xml_path = TRKPATH.'/xml/'.PMD.'.xml';
	$xml = simplexml_load_file( $xml_path );
	$xpath = $xml->xpath('/Publications');

	foreach($xpath as $temp) {
		for( $i = 0; $i < count( $temp->Item ); $i++ ) {
			$m = explode( ";", $temp->Item[$i]->Mails );
			if( in_array( $email, $m ) ) {
				$key = array_search( $email, $m );
				
				unset( $m[$key] );
				$m = array_values( $m );
				$m = implode( ";", $m );
				
				$temp->Item[$i]->Mails = $m;
				}
			}
		
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
		file_put_contents( $xml_path, $dom->saveXML() );
		XMLUpload2( PMD.'.xml' );
		}
	}

function systemCurl($url, $post_array, $headers=null, $check_ssl=true) {
	if(!is_null($headers)){
		foreach($headers as $key => $val){
			$head .= " -H '".$key.":". $val."' ";
			}
		}
	$cmd = "curl -X POST ".$head;
	$cmd.= " -d ' data=" . json_encode($post_array) . "' '" . $url . "'";
	if(!$check_ssl){
		$cmd.= "'  --insecure";
		}
	$cmd .= " > /dev/null 2>&1 &";

	exec($cmd, $output, $exit);
	return $exit == 0;
}

function codeGen( $word = 3, $number = 2 ) {
	$id = "";
	
	$temp = array();
	$c = "";
	for( $x = 0; $x < $word; $x++ ) {
		$c = randomstring();
		$temp[] = $c;
		}

	for( $x = 0; $x < $number; $x++ ) {
		$c = randomnumber();
		$temp[] = $c;
		}		
	
	$id = implode( "", $temp );

	// Checked against both tables: an Adhoc job's magazine and publication
	// share the same code (see sub=create's Adhoc branch), but a Regular
	// magazine's issue codes only live in publications, not magazines - a
	// generated code needs to avoid colliding with either. The recursive
	// retry used to call codeGen( $length, $word ) - $length was never a
	// real parameter (only $word/$number are), so a retry after a
	// collision silently generated a wrong-shaped code (0 letters, 3
	// digits, not 3 letters + 2 digits) instead of trying again properly.
	$check = sql_aget( "magazines", "code='".$id."' LIMIT 1", "*" );
	$checkPub = sql_aget( "publications", "code='".$id."' LIMIT 1", "*" );
	if( !empty( $check[0]["id"] ) || !empty( $checkPub[0]["id"] ) ) {
		$id = codeGen( $word, $number );
		}
	return $id;
	}

function randomPWN() {
	$characters = '23456789';
	$charactersLength = strlen($characters);
	return $characters[rand(0, $charactersLength - 1)];
	}

function randomPWS() {
	$characters = 'abcdefghjkmnopqrstuvwx';
	$charactersLength = strlen($characters);
	return $characters[rand(0, $charactersLength - 1)];
	}

function randomPWB() {
	$characters = 'ABCDEFGHJKMNOPQRSTUVWX';
	$charactersLength = strlen($characters);
	return $characters[rand(0, $charactersLength - 1)];
	}

function randomnumber() {
	$characters = '0123456789';
	$charactersLength = strlen($characters);
	return $characters[rand(0, $charactersLength - 1)];
	}

function randomstring() {
	$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	return $characters[rand(0, $charactersLength - 1)];
	}

function checkActual( $userID, $returninfo ) {
	$u = sql_aget( "accounts", "id='".$userID."'", "*" );
	$temp = explode( "_", $u[0]["actual"] );
	$job = sql_aget( "magazines", "code='".$temp[0]."'", "*" );
	if( empty( $job[0]["id"] ) ) {
		$tempPub = sql_aget( 'publications', 'magazine_id="'.$job[0]["id"].'"', '*' );
		if( !empty( $tempPub[0]["id"] ) ) {
			sql_update( 'accounts', 'actual="'.$job[0]["id"].'_'.$tempPub[0]["code"].'"', 'id="'.$u[0]["id"].'"' );
			if( $returninfo ) {
				$return = array();
				$return = $job[0];
				}
			else {
				$return = true;
				}
			}
		else {
			$tempPub = sql_aget( 'publications', 'publisher_id="'.$u[0]["publisher"].'"', '*' );
			if( !empty( $tempPub[0]["id"] ) ) {
				sql_update( 'accounts', 'actual="'.$job[0]["id"].'_'.$tempPub[0]["code"].'"', 'id="'.$u[0]["id"].'"' );
				if( $returninfo ) {
					$return = array();
					$return = $job[0];
					}
				else {
					$return = true;
					}
				}
			else {
				$mags = explode(",", $u[0]["showMagazines"] );
				$have = false;
				for( $i = 0; $i < count( $mags ); $i++ ) {
					$job = sql_aget( "magazines", "id='".$mags[$i]."'", "*" );
					if( !empty( $job[0]["id"] ) ) {
						$have = true;
						break;
						}
					}
					
				if( $have ) {
					if( $returninfo ) {
						$return = array();
						$return = $job[0];
						}
					else {
						$return = true;
						}				
					}
				else {
					sql_update( 'accounts', 'actual=""', 'id="'.$u[0]["id"].'"' );
					if( $returninfo ) {
						$return = array();
						}
					else {
						$return = false;
						}
					}
				}
			}
		}
	else {
		if( $returninfo ) {
			$return = array();
			$return = $job[0];
			}
		else {
			$return = false;
			}
		}
		
	return $return;
	}

function checkTransferRight( $uid ) {
	if( !empty( $uid ) ) {
		$u = sql_aget( "accounts", "id='".$uid."'", "*" );
		if( !empty( $_GET["page"] ) ) {
			if( $_GET["page"] == "flatplan" ) {
				if( !empty( $_GET["id"] ) ) {
					$temp = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
					$job = sql_aget( "magazines", "id='".$temp[0]["magazine_id"]."'", "*" );
					}
				else {
					$temp = explode( "_", $u[0]["actual"] );
					$temp = checkActual( $uid, true );
					$job = sql_aget( "magazines", "code='".$temp["code"]."'", "*" );
					}
				}
			else {
				$temp = explode( "_", $u[0]["actual"] );
				$temp = checkActual( $uid, true );
				$job = sql_aget( "magazines", "code='".$temp["code"]."'", "*" );
				}
			}
		else {
			$temp = explode( "_", $u[0]["actual"] );
			$temp = checkActual( $uid, true );
			$job = sql_aget( "magazines", "code='".$temp["code"]."'", "*" );	
		
			}
		
		if( !empty( $job[0]["code"] ) ) {	
			$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $job[0]["code"] ) {
						break;
						}
					}
				}
			
			$process = (string) $xml->Item[$x]->Workflow;
			switch( $process ) {
				case "Full":
					$return = array();
					$return["up"] = true;
					$return["down"] = false;
					break;

				case "Resize":
					$return = array();
					$return["up"] = true;
					$return["down"] = true;
					break;
				}
			
			if( !empty( $_GET["id"] ) ) {	
				$return["id"] = $_GET["id"];
				}
			else {
				$temp = explode( "_", $u[0]["actual"] );
				$tempPub = sql_get( 'publications', 'magazine_id="'.$job[0]["id"].'" AND code="'.$temp[1].'"', '*' );
				$return["id"] = $tempPub[0][0];
				}
				
			$return["type"] = "pub";
			}			
		}

	return $return;
	}

function PDF_prework( $id ) {
	$pageinfo = sql_aget( "pageinfo", "id='".$id."'", "*" );
	$tag = $pageinfo[0]["state"];
	$magazine = sql_get( 'magazines', 'code="'.$pageinfo[0]["code"].'" LIMIT 1', '*' );
	$issue = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'" AND code="'.$pageinfo[0]["issue"].'" LIMIT 1', '*' );
	
	if( !empty( $issue[0][0] ) ) {
		$color = partDetect( $issue[0][0], $pageinfo[0]["page"] );
		$dir = TRKPATH."/packages/".$magazine[0][3]."/".$issue[0][10];
		//error_log( "BENT" );
		if( $pageinfo[0]["type"] == "ad" ) {
			$dir .= "/_ads";
			$file = str_pad( $pageinfo[0]["page"], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0]["pack_id"]."_".$tag."ad_preview.pdf";
			}
		else {
			$pack = sql_get( 'packages', 'id="'.$pageinfo[0]["pack_id"].'"', '*' );
			if( $pageinfo[0]["type"] == "PRE" ) {
				$dir .= "/_PRE";
				}
			else {
				$dir .= "/".$pack[0][4];
				}
			if( $pageinfo[0]["fin"] == "1" ) {
				$dir .= "/FIN";
				}
			
			$file = str_pad( $pageinfo[0]["page"], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0]["pack_id"]."_".$tag."preview.pdf";
			}
		
		//error_log( "HELY: ".$dir."/".$file );
		if( is_file( $dir."/".$file ) ) {
			//Méret lekérése, tárolása
			$command = "./r3 -mode:GETDATA -metadata ".$dir."/".$file;
			//error_log( $command );
			$command = shell_exec('
					cd /var/www/html/r3API/r3 2>&1;
					'.$command.';
					');			
			//error_log( $command );
			$text = "";
			$temp = array();
			$command = explode( "\n", $command );
			for( $t = 0; $t < 4; $t++ ) {
				$temp[] = $command[$t];
				}
			
			$text = implode( "\n", $temp );
			sql_update( "pageinfo", "boxes='".$text."'", "id='".$pageinfo[0]["id"]."'" );
			
			//Színek lekérése, tárolása
			$command = './r3 -mode:MEASURE -x:596 -y:760 -d:1 -r:600 -tprofile:'.$color.'.icc '.$dir.'/'.$file.' 2>&1';
			$command = shell_exec('
					cd /var/www/html/r3API/r3 2>&1;
					'.$command.';
					');

			sql_update( "pageinfo", "colors='".$command."'", "id='".$pageinfo[0]["id"]."'" );
			
			//Preview-ek generálása
			$f[0]["Name"] = $dir."/".$file;
			$f[0]["Path"] = $dir."/".$file;
			$sizes = getBBox( str_replace( "/var/www/html/client/", "", $f[0]["Name"] ), "" );
			$f[0]["Right"] = $sizes['Right'];
			$f[0]["Top"] = $sizes['Top'];
			$f[0]["Width"] = $sizes['Width'];
			$f[0]["Height"] = $sizes['Height'];
			$f[0]["Left"] = 0;
			$f[0]["Bottom"] = 0;
			
			$box = getPDFBox_TEMP( "Mediabox Trimbox Cropbox Bleedbox", $f[0]["Name"] );			
			$differences = array(
				"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
				"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
				"Right" => ( $box["Mediabox"][2] - $box["Cropbox"][2] ),
				"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
				);	

			//Mediabox
			$correctionBox[2] = $correctionBoxTemp = "mediabox";	
			$sizes = array(
				"Left" => $box["Trimbox"][0] - 28.3464567 - $box["Cropbox"][0],
				"Bottom" => $box["Trimbox"][1] - 28.3464567 - $box["Cropbox"][1],
				"Right" => $box["Trimbox"][2] + 28.3464567 - $box["Cropbox"][0],
				"Top" => $box["Trimbox"][3] + 28.3464567 - $box["Cropbox"][1]
				);
		
			$correctionBox[0] = $differences;
			$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
			$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
			$fullSizes = ( $f[0]["Right"]-$f[0]["Left"] );
			PDFtoImage_TEMP( $sizes, $dir."/".$file, $dir."/".(str_replace( ".pdf", "-cropbox.jpg", $file ) ),$color , "" );					

			//Trimbox
			$correctionBox[2] = $correctionBoxTemp = "trimbox";	
			$sizes = array(
				"Left" => $box["Trimbox"][0] - $differences['Left'],
				"Bottom" => $box["Trimbox"][1] - $differences['Bottom'],
				"Right" => $box["Trimbox"][2]-$box["Cropbox"][0],
				"Top" => $box["Trimbox"][3] - $differences['Top']
				);

			$differences = array(
				"Left" => ( $box["Cropbox"][0] - $box["Trimbox"][0] ),
				"Bottom" => ( $box["Cropbox"][1] - $box["Trimbox"][1] ),
				"Right" => ( $box["Mediabox"][2] - $box["Trimbox"][2] ),
				"Top" => ( $box["Cropbox"][3] - $box["Trimbox"][3] )
				);
		
			$correctionBox[0] = $differences;
			$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
			$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
			$fullSizes = ( $f[0]["Right"]-$f[0]["Left"] );
			PDFtoImage_TEMP( $sizes, $dir."/".$file, $dir."/".(str_replace( ".pdf", "-trimbox.jpg", $file ) ),$color , "" );
			}	
		}	
	}

function array_orderby() {
    $args = func_get_args();
    $data = array_shift($args);
    foreach ($args as $n => $field) {
        if (is_string($field)) {
            $tmp = array();
            foreach ($data as $key => $row)
                $tmp[$key] = $row[$field];
            $args[$n] = $tmp;
            }
    	}
    $args[] = &$data;
    call_user_func_array('array_multisort', $args);
    return array_pop($args);
	}

function issueChecker( $name, $code, $type ) {
	switch( $type ) {
		case "pubs":
			$mag = sql_aget( "magazines", "code='".$name."'", "*" );
			$data = sql_aget( "publications", "code='".$code."' AND magazine_id='".$mag[0]["id"]."'" , "*" );
			break;
		
		case "calendar":
			$data = sql_aget( "calendar_post", "code='".$code."' AND magCode='".$name."'" , "*" );
			break;
		}
	
	return $data;
	}

function user_log( $user, $action ) {
	$names = array( "user_id", "action", "date" );
	$values = array( $user, $action, time() );
	sql_add( "user_log", $names, $values );
	}

function minFormat( $min, $l ) {
	$txt = $min;
	
	if( $min >= 60 ) {
		$h = $min / 60;
		$min = $min - ( $h * 60 );
		
		$txt = $h."h";
		if( $min > 0 ) {
			$txt .= " ".$min."m";
			}
		}
	else {
		$txt = $min ."m";
		}
	
	return $txt;
	}

function rutime($ru, $rus, $index) {
    return round(( $ru - $rus)*1000 );
	}

function generateImage( $img, $path ) {
    $folderPath = "images/";

    $image_parts = explode(";base64,", $img);
    $image_type_aux = explode("image/", $image_parts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($image_parts[1]);
    $file = $path;

    file_put_contents($file, $image_base64);
    }

function fpPlannerArticleNameSingle( $id, $page, $color ) {
	$current = sql_aget( "flatplan_planner", "pub_id='".$id."' AND pos = ".$page."", "*" );
	$prev = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$current[0]["name"]."' AND pos = ".( $page - 1 )."", "*" );
	$next = sql_aget( "flatplan_planner", "pub_id='".$id."' AND name='".$current[0]["name"]."' AND pos = ".( $page + 1 )."", "*" );
	
	$color = "";
	$check = sql_aget( "flatplan_files", "pubid='".$id."' AND articlename = '".$current[0]["name"]."'", "*" );
	if( count( $check ) > 0 ) {
		$color = "#28E307";
		}
	
	if( !empty( $current[0]["id"] ) ) {
		if( empty( $prev[0]["id"] ) ) {
			if( empty( $next[0]["id"] ) ) {
				return "<div class='onepage-article-title'><div class='onepage-article' style='width: 81px; text-align: center; height: 51px; vertical-align: bottom; display: table-cell; color: ".fontcolor( $color ).";'><span class='articleNameBG'>".$current[0]["name"]."</span></div></div>
				<div onclick='settingsPanel(\"flatplan_worker\", undefined, \"".$current[0]["id"]."\" )' data-id='".$current[0]["id"]."' class='fp-user-icon'><i class='fp-icons fas fa-user'></i></div>
				<div onclick='showAssets(\"".$id."\", \"".$current[0]["id"]."\" )' data-id='".$current[0]["id"]."' class='fp-cog-icon'><i class='fp-icons fas fa-archive' style='color: ".$color.";'></i></div>
				";
				}
			}
		}
	}

function arrowChecker( $id, $page, $type ) {		
	if( $type == "promo" ) {
		$txt .= "<div class='prThumb'>PR</div>";
		}
	
	return $txt;
	}

function getProcess( $magCode ) {
	$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
	$xpath = $xml->xpath('/Publications');
	foreach($xpath as $temp) {
		for( $x = 0; $x < count( $temp->Item ); $x++ ) {
			if( $temp->Item[$x]->Code == $magCode ) {
				break;
				}
			}
		}
		
	return (string) $xml->Item[$x]->Workflow;	
	}

function imageList( $pubID ) {
	echo $pubID."<br>";
	$pub = sql_aget( "publications", "id='".$pubID."'", "*" );
	$magazine = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
	$hird = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND status='2' AND fin='1' AND issue='".$pub[0]["code"]."' AND type='ad' GROUP BY page", "*" );
	$process = getProcess( $magazine[0]["code"] );
	
	$adProof = 0;
	$proof = 0;
	$retussum = 0;	

	$temp = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND issue='".$pub[0]["code"]."' AND proofCounter != '0'", "*" );
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
	
	if( $process == "Full" or $process == "Hybrid" ) {	
		$kepek = sql_aget( "image_map", "pub_id='".$pub[0]["id"]."' AND retus > 0", "*" );
		}
	else {
		$kepek = sql_aget( "image_map", "pub_id='".$pub[0]["id"]."'", "*" );
		}		
	
	for( $i = 0; $i < count( $kepek ); $i++ ) {
		$txt .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$kepek[$i]["name"]."&nbsp;".( $kepek[$i]["retus"] != "0" ? "–&nbsp;".$kepek[$i]["retus"] : "" )."&nbsp;".( $kepek[$i]["maszk"] == "Maszk: 1" ? "1" : "" )."<br>";
		$csv .= "\t".$kepek[$i]["name"]."\t".( $kepek[$i]["retus"] != "0" ? $kepek[$i]["retus"] : "" )."\t".( $kepek[$i]["maszk"] == "1" ? "1" : "" )."\n";
		
		$retussum += $kepek[$i]["retus"];
		}
		
	if( $retussum > 0 ) {
		$txt .= "Összesen (perc):".$retussum."<br>";
		$csv .= "\tÖsszesen (perc)\t".$retussum."\t\n";
		}
	
	if( $process == "Full" or $process == "Hybrid" ) {
		$txt .= "<br><br>Prooflista:<br>";
		$csv .= "Prooflista\n";	
	
		$txt .= "Hirdetés proof: ".$adProof."<br>";
		$csv .= "Hirdetés proof:\t".$adProof."\n";	
	
		$txt .= "Szerkesztőségi proof: ".$proof."<br>";
		$csv .= "Szerkesztőségi proof:\t".$proof."\n";
		
		$txt .= "Borító proof: ".$coverProof."<br>";
		$csv .= "Borító proof:\t".$coverProof."\n";

		$txt .= "Összesen: ".($adProof + $proof + $coverProof )."<br>";
		$csv .= "Összesen:\t".($adProof + $proof + $coverProof )."\n";
	
		$txt .= "<br>Szerkesztőségi + borító proof-ok: ".( $proof + $coverProof )."<br>";
		$csv .= "Szerkesztőségi + borító proof-ok:\t".( $proof + $coverProof )."\n";	
		
		$proofs = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND issue='".$pub[0]["code"]."' AND proofCounter != '0' AND type != 'ad'", "*" );
		for( $i = 0; $i < count( $proofs ); $i++ ) {
			$txt .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".str_pad( $proofs[$i]["page"], 3, '0', STR_PAD_LEFT)."_".$magazine[0]["code"]."".$pub[0]["code"]." – ".$proofs[$i]["proofCounter"]." db<br>";
			$csv .= "\t".str_pad( $proofs[$i]["page"], 3, '0', STR_PAD_LEFT)."_".$magazine[0]["code"]."".$pub[0]["code"]."\t".$proofs[$i]["proofCounter"]."\n";		
			}
	
		$txt .= "<br>Hirdetési proof-ok: ".$adProof."<br>";
		$csv .= "Hirdetési proof-ok:\t".$adProof."\n";	
		
		$proofs = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND issue='".$pub[0]["code"]."' AND proofCounter != '0' AND type = 'ad'", "*" );
		for( $i = 0; $i < count( $proofs ); $i++ ) {
			$ad = sql_aget( "ads", "id='".$proofs[$i]["pack_id"]."'", "*" );
			
			$txt .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$ad[0]["name"]." ".$ad[0]["size"]." – ".$proofs[$i]["proofCounter"]." db<br>";
			$csv .= "\t".$ad[0]["name"]."\t".$proofs[$i]["proofCounter"]."\n";
			}
		}
		
	return array( $txt, $csv );
	}	

function invoicingTESZT( $pubID ) {
	$pub = sql_aget( "publications", "id='".$pubID."'", "*" );
	$magazine = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
	$hird = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND status='2' AND fin='1' AND issue='".$pub[0]["code"]."' AND type='ad' GROUP BY page", "*" );
	$process = getProcess( $magazine[0]["code"] );
	
	$txt = "";
	$csv = "";
	
	$txt .= "Név: ".$magazine[0]["name"]."<br>";
	$csv .= "Név:\t".$magazine[0]["name"]."\n";
	
	$txt .= "Megjelenés: ".$magazine[0]["code"]."_".$pub[0]["code"]."<br>";
	$csv .= "Megjelenés:\t".$magazine[0]["code"]."_".$pub[0]["code"]."\n";
	
	$txt .= "Lezárva: ".date( "Y-m-d\TH:i:s" , time() )."<br>";
	$csv .= "Lezárva:\t".date( "Y-m-d\TH:i:s" , time() )."\n";

	$txt .= "Terjedelem: ".$pub[0]["pages"]."<br>";
	$csv .= "Terjedelem:\t".$pub[0]["pages"]."\n";
	
	$txt .= "Hirdetési oldalak: ".count( $hird )."<br>";
	$csv .= "Hirdetési oldalak:\t".count( $hird )."\n";	

	$txt .= "Szerkesztőségi oldalak: ".( $pub[0]["pages"] - count( $hird ) )."<br>";
	$csv .= "Szerkesztőségi oldalak:\t".( $pub[0]["pages"] - count( $hird ) )."\n";		
	
	if( $process == "Full" or $process == "Hybrid" ) {	
		$txt .= "<br>Retusált képek:<br>";
		$csv .= "Retusált képek\n";
			
		$kepek = sql_aget( "image_map", "pub_id='".$pub[0]["id"]."' AND retus > 0", "*" );
		}
	else {
		$txt .= "<br>Képlista:<br>";
		$csv .= "Képlista\n";

		$kepek = sql_aget( "image_map", "pub_id='".$pub[0]["id"]."'", "*" );
		}
	
	$result = imageList( $pubID );
	$txt .= $result[0];
	$csv .= $result[1];
	
	$txt .= "<br><br>";
	$csv = iconv( "UTF-8", "UTF-16", $csv );
	file_put_contents( TRKPATH."/csv/".$magazine[0]["code"]."_".$pub[0]["code"].".csv" , $csv );
	$attach = TRKPATH."/csv/".$magazine[0]["code"]."_".$pub[0]["code"].".csv";
	
	$subject = $magazine[0]["name"]." – ".$pub[0]["code"]." segédlet számlázáshoz";
	$body = $txt;
	
	$to = PENZUGY."|".PENZUGY;
	produkcioSendmailAttach( $subject, $body, $to, $attach );
	
	$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
	produkcioSendmailAttach( $subject, $body, $to, $attach );
	unlink($magazine[0]["code"]."_".$pub[0]["code"].".csv");
	
	return $txt;
	}

function invoicing( $pubID ) {
	$pub = sql_aget( "publications", "id='".$pubID."'", "*" );
	$magazine = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
	$hird = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND status='2' AND fin='1' AND issue='".$pub[0]["code"]."' AND type='ad' GROUP BY page", "*" );
	
	$adProof = 0;
	$proof = 0;
	$coverProof = 0;
	$temp = sql_aget( "pageinfo", "code='".$magazine[0]["code"]."' AND issue='".$pub[0]["code"]."' AND proofCounter != '0' GROUP BY page", "*" );
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
	
	$txt = "";
	$csv = "";
	
	$txt .= "Név: ".$magazine[0]["name"]."<br>";
	$csv .= "Név:;".$magazine[0]["name"]."\n";
	
	$txt .= "Megjelenés: ".$magazine[0]["code"]."_".$pub[0]["code"]."<br>";
	$csv .= "Megjelenés:;".$magazine[0]["code"]."_".$pub[0]["code"]."\n";
	
	$txt .= "Lezárva: ".date( "Y-m-d\TH:i:s" , time() )."<br>";
	$csv .= "Lezárva:;".date( "Y-m-d\TH:i:s" , time() )."\n";

	$txt .= "Terjedelem: ".$pub[0]["pages"]."<br>";
	$csv .= "Terjedelem:;".$pub[0]["pages"]."\n";
	
	$txt .= "Hirdetési oldalak: ".count( $hird )."<br>";
	$csv .= "Hirdetési oldalak:;".count( $hird )."\n";	

	$txt .= "Szerkesztőségi oldalak: ".( $pub[0]["pages"] - count( $hird ) )."<br>";
	$csv .= "Szerkesztőségi oldalak:;".( $pub[0]["pages"] - count( $hird ) )."\n";	

	$txt .= "Hirdetés proof: ".$adProof."<br>";
	$csv .= "Hirdetés proof:;".$adProof."\n";	

	$txt .= "Szerkesztőségi proof: ".$proof."<br>";
	$csv .= "Szerkesztőségi proof:;".$proof."\n";
	
	$txt .= "Borító proof: ".$coverProof."<br>";
	$csv .= "Borító proof:;".$coverProof."\n";	

	$txt .= "Retusált képek:<br>";
	$csv .= "Retusált képek\n";
	
	$kepek = sql_aget( "image_map", "pub_id='".$pub[0]["id"]."'", "*" );
	for( $i = 0; $i < count( $kepek ); $i++ ) {
		$txt .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$kepek[$i]["name"]." ".$kepek[$i]["retus"]."<br>";
		$csv .= ";".$kepek[$i]["name"].";".$kepek[$i]["retus"]."\n";
		}
	
	/*
	$result = imageList( $pubID );
	$txt .= $result[0];
	$csv .= $result[1];
	*/
	
	
	$txt .= "<br><br>";
	file_put_contents( $magazine[0]["code"]."_".$pub[0]["code"].".csv" , $csv );
	$to = PENZUGY."|".PENZUGY;
	//$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";	
	$attach = $magazine[0]["code"]."_".$pub[0]["code"].".csv";
	
	$subject = $magazine[0]["name"]." – ".$pub[0]["code"]." segédlet számlázáshoz";
	$body = $txt;
	
	produkcioSendmailAttach( $subject, $body, $to, $attach );
	
	$to = "peter@colorcom.hu|peter@colorcom.hu";
	produkcioSendmailAttach( $subject, $body, $to, $attach );
	unlink($magazine[0]["code"]."_".$pub[0]["code"].".csv");
	
	return $txt;
	}

function getBetween($string, $start = "", $end = ""){
    if (strpos($string, $start)) { // required if $start not exist in $string
        $startCharCount = strpos($string, $start) + strlen($start);
        $firstSubStr = substr($string, $startCharCount, strlen($string));
        $endCharCount = strpos($firstSubStr, $end);
        if ($endCharCount == 0) {
            $endCharCount = strlen($firstSubStr);
        }
        return substr($firstSubStr, 0, $endCharCount);
    } else {
        return '';
    }
}

function fontcolor( $hex ) {
	if( strpos( $hex, "rgb" ) !== false ) {
		$c = getBetween( $hex, "(", ")" );
		$c = explode( ",", $c );
		
		$hex = sprintf("#%02x%02x%02x", $c[0], $c[1], $c[2] );
		}
		
	$hex = str_replace('#', '', $hex);
	if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    	}
    	
    $color_parts = str_split($hex, 2);
             
    if ( ( hexdec($color_parts[0])*0.299 + hexdec($color_parts[1])*0.587 + hexdec($color_parts[2])*0.114 ) > 182  ) {
	    return "#000";
    	}
    else {
	    return "#FFF";
    	}
	}

function adjustBrightness($hex, $steps) {
    // Steps should be between -255 and 255. Negative = darker, positive = lighter
    $steps = max(-255, min(255, $steps));

    // Normalize into a six character long hex string
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex,0,1), 2).str_repeat(substr($hex,1,1), 2).str_repeat(substr($hex,2,1), 2);
    }

    // Split into three parts: R, G and B
    $color_parts = str_split($hex, 2);

    foreach ($color_parts as $color) {
        $color   = hexdec($color); // Convert to decimal
        $color   = max(0,min(255,$color + $steps)); // Adjust color
        $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT); // Make two char hex code
    }

    return $return;
}

function calendarExtraWorkday( $month, $day, $year ) {
	$array = array(
		"2018" => array(
			"0" => array(),
			"1" => array(),
			"2" => array(),
			"3" => array( "10" ),
			"4" => array( "21" ),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array( "13" ),
			"11" => array( "10" ),
			"12" => array( "1" ),
			),

		"2019" => array(
			"0" => array( "1" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array( "10" ),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "21" ),
			),

		"2020" => array(
			"0" => array( "21" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array( "29" ),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array(),
			),			

		"2021" => array(
			"0" => array(),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "11" ),
			),	

		"2022" => array(
			"0" => array(),
			"1" => array(),
			"2" => array(),
			"3" => array( "26" ),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array( "15" ),
			"11" => array(),
			"12" => array(),
			),

		"2023" => array(
			"0" => array(),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array(),
			),

		"2024" => array(
			"0" => array(),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array( "3" ),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "7", "14" ),
			),

		"2025" => array(
			"0" => array( "7", "14" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array( "17"),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array( "18" ),
			"11" => array(),
			"12" => array( "13" ),
			),
 		"2026" => array(
			"0" => array( "13" ),
			"1" => array( "10" ),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array( "8" ),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "12" ),
			),
			
 		"2027" => array(
			"0" => array( "12" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array(),
			),
		);

	// $array only covers years hand-added by a developer (2018-2027 as of
	// this writing) - in_array() against an unset $array[$year][$month]
	// throws a fatal TypeError in PHP 8, crashing the whole calendar grid
	// render for any later year. Hungary's "shifted workday" schedule
	// (which Saturdays become mandatory workdays to compensate for a
	// bridge day off) isn't covered by the public-holiday API the
	// Planner's "Add Year" button uses, so there's no data source to
	// fall back to here yet - just fail safe instead of crashing.
	if( !isset( $array[$year][$month] ) ) {
		return false;
		}

	return ( in_array( $day, $array[$year][$month] ) ? true : false );
	}

function calendarExtraHoliday( $month, $day, $year ) {
	$array = array(
		"2018" => array(
			"0" => array(),
			"1" => array(),
			"2" => array(),
			"3" => array( "16" ),
			"4" => array( "30" ),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),	
			"10" => array( "22" ),
			"11" => array( "2" ),
			"12" => array( "24", "31" ),
			),

		"2019" => array(
			"0" => array( "24", "31" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array( "19" ),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "27", "31" ),
			),

		"2020" => array(
			"0" => array( "27", "31" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array( "21" ),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "27", "31" ),
			),

		"2021" => array(
			"0" => array( "27", "31" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "24" ),
			),

		"2022" => array(
			"0" => array("24"),
			"1" => array(),
			"2" => array(),
			"3" => array("14"),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array( "31" ),
			"11" => array(),
			"12" => array( "24" ),
			),

		"2023" => array(
			"0" => array( "24" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array(),
			),

		"2024" => array(
			"0" => array(),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array( "19" ),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "27" ),
			),

		"2025" => array(
			"0" => array( "27" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array( "2" ),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array( "24" ),
			"11" => array(),
			"12" => array(),
			),
			
 		"2026" => array(
			"0" => array(),
			"1" => array( "2" ),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array( "21" ),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array( "24" ),
			),
			
 		"2027" => array(
			"0" => array( "24" ),
			"1" => array(),
			"2" => array(),
			"3" => array(),
			"4" => array(),
			"5" => array(),
			"6" => array(),
			"7" => array(),
			"8" => array(),
			"9" => array(),
			"10" => array(),
			"11" => array(),
			"12" => array(),
			),
		);

	// Same crash risk and same "no automated data source yet" situation
	// as calendarExtraWorkday() above - see its comment.
	if( !isset( $array[$year][$month] ) ) {
		return false;
		}

	return ( in_array( $day, $array[$year][$month] ) ? true : false );
	}

function calendarHoliday( $month, $day, $year ) {
	$array = array(
		"2018" => array(
			"0" => array( "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15", "30" ),
			"4" => array( "1", "2" ),
			"5" => array( "1", "20", "21" ),
			"6" => array(),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "25", "26" ),
			),

		"2019" => array(
			"0" => array( "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15" ),
			"4" => array( "19", "22" ),
			"5" => array( "1" ),
			"6" => array( "10" ),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "25", "26" ),
			),

		"2020" => array(
			"0" => array( "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15" ),
			"4" => array( "10", "13" ),
			"5" => array( "1" ),
			"6" => array( "1" ),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "25", "26" ),
			),

		"2021" => array(
			"0" => array( "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15" ),
			"4" => array( "2", "4", "5" ),
			"5" => array( "1", "23", "24" ),
			"6" => array(),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "25", "26" ),
			),

		"2022" => array(
			"0" => array( "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15" ),
			"4" => array( "15", "18" ),
			"5" => array( "1" ),
			"6" => array( "6" ),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "25", "26" ),
			),

		"2023" => array(
			"0" => array( "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15" ),
			"4" => array( "7", "9", "10" ),
			"5" => array( "1", "28", "29" ),
			"6" => array(),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "25", "26" ),
			),

		"2024" => array(
			"0" => array( "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15", "29", "31" ),
			"4" => array( "1" ),
			"5" => array( "1", "19", "20" ),
			"6" => array(),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "24", "25", "26" ),
			),
			
 		"2025" => array(
			"0" => array( "24", "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15" ),
			"4" => array( "18", "20", "21" ),
			"5" => array( "1" ),
			"6" => array( "8", "9" ),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "24", "25", "26" ),
			),	
			
 		"2026" => array(
			"0" => array( "24", "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15" ),
			"4" => array( "3", "6" ),
			"5" => array( "1", "25" ),
			"6" => array(),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "25", "26" ),
			),	
			
 		"2027" => array(
			"0" => array( "25", "26" ),
			"1" => array( "1" ),
			"2" => array(),
			"3" => array( "15", "26", "28" ),
			"4" => array(),
			"5" => array( "1", "17" ),
			"6" => array(),
			"7" => array(),
			"8" => array( "20" ),
			"9" => array(),
			"10" => array( "23" ),
			"11" => array( "1" ),
			"12" => array( "25", "26" ),
			),
		);

	// $array only covers years hand-added by a developer (2018-2027 as of
	// this writing) - in_array() against an unset $array[$year][$month]
	// throws a fatal TypeError in PHP 8 (it used to just warn and treat
	// it as not-found in PHP 7), so every year beyond the hardcoded range
	// crashed the whole calendar grid render. Years added via the
	// Planner's "Add Year" button live in calendar_holidays instead of
	// this array; check that first before falling through to it.
	if( !isset( $array[$year][$month] ) ) {
		// $month follows this function's own "0 = December of $year-1"
		// convention (used for the calendar grid's leading days from the
		// prior month), so translate to a real calendar date first.
		if( $month == 0 ) {
			$realYear = $year - 1;
			$realMonth = 12;
			}
		else {
			$realYear = $year;
			$realMonth = $month;
			}
		$dateStr = $realYear.'-'.str_pad( $realMonth, 2, '0', STR_PAD_LEFT ).'-'.str_pad( $day, 2, '0', STR_PAD_LEFT );
		$check = sql_get( 'calendar_holidays', 'holiday_date="'.$dateStr.'"', 'id' );
		return !empty( $check[0][0] );
		}

	return ( in_array( $day, $array[$year][$month] ) ? true : false );
	}

function calendarOrderbox( $order, $bottom, $date, $mags, $boxCount = false, $magsarray = false ) {
	global $rights;
	
	$magcolor = sql_get( "magazines", "id='".$order["magazine_id"]."'", "color" );
	if( $order["magazine_id"] == "0" ) {
		$tempcolor = substr( $order["code"],4 , -1 );
		$tempcolor = explode( ", ", $tempcolor );
		$magcolor[0][0] = sprintf("%02x%02x%02x", $tempcolor[0], $tempcolor[1], $tempcolor[2] );
		}
		
	$magcolor = adjustBrightness( $magcolor[0][0], +20 );
	$fontcolor = fontcolor( $magcolor );
	
	$shorten = false;
	if( $boxCount !== false ) {
		if( $boxCount >= 4 ) {
			$bottom = ( $bottom / 16 ) * 8;
			$shorten = true;
			}
		}
		
	else {
		$check = sql_aget( "calendar_post", "( ".$mags." ) AND ( printDay='".$date."' OR salesDay='".$date."' )", "*" );
		if( count( $check ) >= 4 ) {
			$bottom = ( $bottom / 16 ) * 8;
			$shorten = true;
			}	
		}
	if( $order["magazine_id"] == "0" && in_array( $order["id"], $magsarray ) ) {
		$txt = "<div magid='".$order["magazine_id"]."' class='draggable printOrder orderTile' style='background-color: #".$magcolor."; color: ".$fontcolor."; bottom: ".$bottom."px;' magCode='".$order["magCode"]." ".$order["code"]."' plannerId='".$order["id"]."'>
				<span class='specdate'>".$order["specificName"]."</span>
				<div class='boxFullDate'>".date( "Y. m. d.", strtotime( $date ) )."</div>
			</div>";
		}
		
	elseif( $order["magazine_id"] != "0" ) {
		$txt = "<div ondragstart='dragStart(event)' draggable='".(  $rights["calendar_realdates"] ? "true" : "false" )."' magid='".$order["magazine_id"]."' class='draggable printOrder orderTile' style='background-color: #".$magcolor."; color: ".$fontcolor."; bottom: ".$bottom."px;' magCode='".$order["magCode"]." ".$order["code"]."' plannerId='".$order["id"]."'>
				".( $shorten ? "<div class='boxType orderTitle'>print order</div>" : "<div class='orderTitle'>print order</div>" )."
				<span class='".( strlen( $order["magCode"]." ".$order["code"] ) > 9 ? "thinnerTitle" : "" )."'>".$order["magCode"]." ".$order["code"]."</span>
				".( !empty( $order["specificName"] ) ? "<div class='boxCN'>".$order["specificName"]."</div>" : "" )."
				<div class='boxFullDate'>".date( "Y. m. d.", strtotime( $date ) )."</div>
			</div>";
		}
	return $txt;
	}

function countDays( $cd, $magazines, $nap ) {
	$count = 0;
	
	for( $c = 0; $c <= count( $cd ); $c++ ) {
		$print_check = sql_aget( "calendar_post", "printDay='".$cd[ $c ]."'", "*" );
		for( $p = 0; $p < count( $print_check ); $p++ ) {
			if( in_array( $print_check[$p]["magazine_id"], $magazines ) ) {
				$mag = sql_aget( "magazines", "id='".$print_check[$p]["magazine_id"]."'", "*" );
				if( $mag[0]["dayshift"] == $c ) {
					if( $nap != "Sat" && $nap != "Sun" ) {
						$count++;
						}
					}
				}		
			}
		}
		
	return $count;
	}

function countDayBoxes( $date, $magazines, $day, $key ) {
	$count = 0;
	
	$cd[0] = $date;
	$cd[1] = date( "Y-m-d" , strtotime( '+1 days', strtotime( $date ) ) );
	$cd[2] = date( "Y-m-d" , strtotime( '+2 days', strtotime( $date ) ) );
	$cd[3] = date( "Y-m-d" , strtotime( '+3 days', strtotime( $date ) ) );
	$nap = date( "D", strtotime( $date ) );
	
	$count += countDays( $cd, $magazines, $nap );
	
	if( $nap == "Fri" && !empty( $day ) ) {
		$cd[0] = date( "Y-m-d" , strtotime( '+1 days', strtotime( $date ) ) );
		$cd[1] = date( "Y-m-d" , strtotime( '+2 days', strtotime( $date ) ) );
		$cd[2] = date( "Y-m-d" , strtotime( '+3 days', strtotime( $date ) ) );
		$cd[3] = date( "Y-m-d" , strtotime( '+4 days', strtotime( $date ) ) );		
		$count += countDays( $cd, $magazines, $nap );

		$cd[0] = date( "Y-m-d" , strtotime( '+2 days', strtotime( $date ) ) );
		$cd[1] = date( "Y-m-d" , strtotime( '+3 days', strtotime( $date ) ) );
		$cd[2] = date( "Y-m-d" , strtotime( '+4 days', strtotime( $date ) ) );
		$cd[3] = date( "Y-m-d" , strtotime( '+5 days', strtotime( $date ) ) );
		$count += countDays( $cd, $magazines, $nap );	
		}
	
	$print_check = sql_aget( "calendar_post", "salesDay='".$date."'", "*" );
	for( $p = 0; $p < count( $print_check ); $p++ ) {
		if( in_array( $print_check[$p]["magazine_id"], $magazines ) ) {
			$count++;
			}
		}
	
	return $count;
	}

function internalOrderbox( $order, $bottom, $date, $boxCount = false ) {
	$magcolor = sql_get( "magazines", "id='".$order["magazine_id"]."'", "color" );
	if( $order["magazine_id"] == "0" ) {
		$tempcolor = substr( $order["code"],4 , -1 );
		$tempcolor = explode( ", ", $tempcolor );
		$magcolor[0][0] = sprintf("%02x%02x%02x", $tempcolor[0], $tempcolor[1], $tempcolor[2] );
		}
		
	$magcolor = adjustBrightness( $magcolor[0][0], +20 );
	$fontcolor = fontcolor( $magcolor );
	
	$shorten = false;
	if( $boxCount !== false ) {
		if( $boxCount >= 4 ) {
			$bottom = ( $bottom / 16 ) * 8;
			$shorten = true;
			}
		}
	
	$mag = sql_aget( "magazines", "id='".$order["magazine_id"]."'", "*" );
	if( $mag[0]["type"] == "Adhoc" ) {
		$code = $order["code"];
		}
	else {
		$code = $mag[0]["code"]." ".$order["code"];
		}
	
	$txt = "<div class='printOrder orderTile' style='background-color: #".$magcolor."; color: ".$fontcolor."; bottom: ".$bottom."px;'>";	
		$txt .= "<span class='".( strlen( $order["magCode"]." ".$order["code"] ) > 9 ? "thinnerTitle" : "" )."'>".$code."</span>";
		$txt .= "<div class='boxFullDate'>".$order["deadline"]."</div>";
	$txt .= "</div>";
	
	return $txt;
	}

function calendarWeeksRow( $magazines ) {
	global $lang;
	
	$row = "";
	$currentDate = time();
	$ddate = date( "Y-m-d", $currentDate );
	$date = new DateTime( $ddate );
	$week = $date->format("W");
	$year = $date->format("Y");
	$max = date("W",strtotime('28th December '.$year ) );
	
	$wcount = 8;
	$weeks = array();
	
	$cyear = $year;
	for( $i = 1; $i <= $wcount; $i++ ) {
		if( $week > $max ) {
			$cyear++;
			$week = 1;
			}
		
		$d = new DateTime();
		$d->setISODate($cyear, $week);
		
		$row .= "<tr>";
			$row .= "<td class='dayBox monthBox'>";
				$row .= sprintf( $lang["calendar"]["weeknr"], $week ); 
			$row .= "</td>";
			
			for( $x = 0; $x < 7; $x++ ) {
				if( $x > 0 ) {
					$d->modify('+1 days');
					}
				
				$holiday = calendarHoliday( $d->format("n"), $d->format('j'), $cyear );
				$eworkday = calendarExtraWorkday( $d->format("n"), $d->format('j'), $cyear );
				$eholiday = calendarExtraHoliday( $d->format("n"), $d->format('j'), $cyear );
				
				$check_date = $d->format("Y-m-d");
				$pubs = sql_aget( "publications", "deadline LIKE '".$check_date."%'", "*" );
				
				$row .= "<td class='".( date( "Y-m-d") == $d->format("Y-m-d") ? "currentDayBox" : "" )." dayBox ".( $x == 5 ? "szombat" : "" )." ".( $x == 6 ? "vasarnap" : "" )." ".( $holiday ? "holiday" : "" )." ".( $eworkday ? "extrawork " : "" )."".( $eholiday ? "extraholiday " : "" )."'>";
					$row .= "<div class='day' date='".$d->format("Y-m-d")."'>".$d->format('j')."</div>";
					
					$counter = 0;
					for( $p = 0; $p < count( $pubs ); $p++ ) {
						$bottom = 16 * $counter;
						$row .= internalOrderbox( $pubs[$p], $bottom, $d->getTimestamp(), count($pubs) );
						$counter++;
						}
				$row .= "</td>";
				}
		$row .= "</tr>";
		
		$week++;
		}
	
	return $row;
	}

function calendarMonthsRow( $monthsArray, $year, $magazines, $rights, $user ) {
	$m = array();
	for($i = 0; $i < count( $magazines ); $i++ ) {
		$m[] = "magazine_id='".$magazines[$i]."'";
		}
	$m = implode( " OR ", $m );

	foreach( $monthsArray as $key => $month ) {
		$row .= "<tr>";
			if( $key == 0 ) {
				$row .= "<td class='dayBox lastMonthBox'>";
					$row .= "12";
					$row .= "<div class='lastYearBox'>".( $year - 1 )."</div>";
				$row .= "</td>"; 
				}
			else {
				$row .= "<td class='monthBox'>".$key."</td>"; 
				}
			
			$sz = 1;
			$v = 1;
			for( $i = 0; $i < count( $month ); $i++ ) {
				if( $sz == 7 ) $sz = 0;
				if( $v == 8 ) $v = 1;
								
				$holiday = calendarHoliday( $key, $month[$i], $year );
				$eworkday = calendarExtraWorkday( $key, $month[$i], $year );
				$eholiday = calendarExtraHoliday( $key, $month[$i], $year );
				
				$workday = true;
				
				if( $key == "0" ) {
					$date = ($year-1)."-12-".str_pad( $month[$i], 2, '0', STR_PAD_LEFT );
					}
				else {
					$date = $year."-".str_pad( $key, 2, '0', STR_PAD_LEFT )."-".str_pad( $month[$i], 2, '0', STR_PAD_LEFT );
					}
				
				$events = sql_aget( "calendar_events", "start <= ".strtotime( $date )." AND end >= ".strtotime( $date )."", "*" );
				$haveEvent = false;
				$eventColor = "";
				if( count( $events ) > 0 ) {
					if( in_array( $events[0]["magazine_id"], $magazines ) ) {
						$haveEvent = true;
						$mag = sql_aget( "magazines", "id='".$events[0]["magazine_id"]."'", "*" );
						$eventColor = adjustBrightness( $mag[0]["color"], +120 );
						}
					}
				
				$row .= "<td valign='center' class='".( date( "Y-m-d") == $date ? "currentDayBox" : "" )." dayBox ".( $sz === 6 ? "szombat " : "" )."".( $v === 7 ? "vasarnap " : "" )."".( $holiday ? "holiday " : "" )."".( $eworkday ? "extrawork " : "" )."".( $eholiday ? "extraholiday " : "" )."".( $workday ? "workday " : "" )."' ".( $workday ? 'ondragenter="dragEnter(event)" ondragleave="dragLeave(event)" ondrop="drop(event)" ondragover="allowDrop(event)"' : '' )." style='".( $haveEvent ? "background-color: #".$eventColor.";" : "" )."'>";
					
					//Print Day Check
					if( $user[0][29] != "sales") {
						if( $key == "0" ) {
							$check_date = ($year-1)."-12-".str_pad( $month[$i], 2, '0', STR_PAD_LEFT );
							if( $rights["calendar_realdates"] == false  ) {
								$current = ($year-1)."-12-".str_pad( $month[$i], 2, '0', STR_PAD_LEFT );
								$cd[0] = $check_date;
								$cd[1] = date( "Y-m-d" , strtotime( '+1 days', strtotime( $check_date ) ) );
								$cd[2] = date( "Y-m-d" , strtotime( '+2 days', strtotime( $check_date ) ) );
								$cd[3] = date( "Y-m-d" , strtotime( '+3 days', strtotime( $check_date ) ) );
								
								if( date( "D", strtotime( '+3 days', strtotime( $check_date ) ) ) == "Sat" ) {
									$cd[3] = date( "Y-m-d" , strtotime( '+5 days', strtotime( $check_date ) ) );
									}
								
								if( date( "D", strtotime( '+2 days', strtotime( $check_date ) ) ) == "Sat" ) {
									$cd[2] = date( "Y-m-d" , strtotime( '+4 days', strtotime( $check_date ) ) );
									$cd[3] = date( "Y-m-d" , strtotime( '+5 days', strtotime( $check_date ) ) );
									}
								if( date( "D", strtotime( '+1 days', strtotime( $check_date ) ) ) == "Sat" ) {
									$cd[1] = date( "Y-m-d" , strtotime( '+3 days', strtotime( $check_date ) ) );
									$cd[2] = date( "Y-m-d" , strtotime( '+4 days', strtotime( $check_date ) ) );
									$cd[3] = date( "Y-m-d" , strtotime( '+5 days', strtotime( $check_date ) ) );
									}
								
								$boxCount = countDayBoxes( $check_date, $magazines, $month[$i], $key );
								$nap = date( "D", strtotime( $check_date ) );
								
								$counter = 0;
								for( $c = 0; $c < 4; $c++ ) {
									$print_check = sql_aget( "calendar_post", "printDay='".$cd[ $c ]."'", "*" );
									for( $p = 0; $p < count( $print_check ); $p++ ) {
										if( in_array( $print_check[$p]["magazine_id"], $magazines ) ) {
											$mag = sql_aget( "magazines", "id='".$print_check[$p]["magazine_id"]."'", "*" );
											if( $mag[0]["dayshift"] == $c && !empty( $month[$i] ) ) {
												$bottom = 16 * $counter;
												if( $nap != "Sat" && $nap != "Sun" ) {
													//$row .= calendarOrderbox( $print_check[$p], $bottom, $cd[ $c ], $m, $boxCount );
													$row .= calendarOrderbox( $print_check[$p], $bottom, $current, $m, $boxCount );
													}
											
												$counter++;
												}
											}
										}
									}
								}
								
							else {
								$print_check = sql_aget( "calendar_post", "printDay='".$check_date."'", "*" );
								$counter = 0;
								for( $p = 0; $p < count( $print_check ); $p++ ) {
									if( in_array( $print_check[$p]["magazine_id"], $magazines ) ) {
										$bottom = 16 * $counter;
										$row .= calendarOrderbox( $print_check[$p], $bottom, $check_date, $m );
										
										$counter++;
										}
									}
								}
							}
						else {
							$check_date = $year."-".str_pad( $key, 2, '0', STR_PAD_LEFT )."-".str_pad( $month[$i], 2, '0', STR_PAD_LEFT );
							if( $rights["calendar_realdates"] == false ) {
								$current = $year."-".str_pad( $key, 2, '0', STR_PAD_LEFT )."-".str_pad( $month[$i], 2, '0', STR_PAD_LEFT );
								$cd[0] = $check_date;
								$cd[1] = date( "Y-m-d" , strtotime( '+1 days', strtotime( $check_date ) ) );
								$cd[2] = date( "Y-m-d" , strtotime( '+2 days', strtotime( $check_date ) ) );
								$cd[3] = date( "Y-m-d" , strtotime( '+3 days', strtotime( $check_date ) ) );

								if( date( "D", strtotime( '+3 days', strtotime( $check_date ) ) ) == "Sat" ) {
									$cd[3] = date( "Y-m-d" , strtotime( '+5 days', strtotime( $check_date ) ) );
									}
								
								if( date( "D", strtotime( '+2 days', strtotime( $check_date ) ) ) == "Sat" ) {
									$cd[2] = date( "Y-m-d" , strtotime( '+4 days', strtotime( $check_date ) ) );
									$cd[3] = date( "Y-m-d" , strtotime( '+5 days', strtotime( $check_date ) ) );
									}
								if( date( "D", strtotime( '+1 days', strtotime( $check_date ) ) ) == "Sat" ) {
									$cd[1] = date( "Y-m-d" , strtotime( '+3 days', strtotime( $check_date ) ) );
									$cd[2] = date( "Y-m-d" , strtotime( '+4 days', strtotime( $check_date ) ) );
									$cd[3] = date( "Y-m-d" , strtotime( '+5 days', strtotime( $check_date ) ) );
									}
								
								$boxCount = countDayBoxes( $check_date, $magazines, $month[$i], $key );
								$nap = date( "D", strtotime( $check_date ) );
								
								$counter = 0;
								for( $c = 0; $c < 4; $c++ ) {
									$print_check = sql_aget( "calendar_post", "printDay='".$cd[ $c ]."'", "*" );
									for( $p = 0; $p < count( $print_check ); $p++ ) {
										if( in_array( $print_check[$p]["magazine_id"], $magazines ) or $print_check[$p]["magazine_id"] == "0" ) {
											$mag = sql_aget( "magazines", "id='".$print_check[$p]["magazine_id"]."'", "*" );
											if( $mag[0]["dayshift"] == $c && !empty( $month[$i] ) ) {
												$bottom = 16 * $counter;
												if( $nap != "Sat" && $nap != "Sun" ) {
													//$row .= calendarOrderbox( $print_check[$p], $bottom, $cd[ $c ], $m, $boxCount );
													$row .= calendarOrderbox( $print_check[$p], $bottom, $current, $m, $boxCount, $magazines );
													}
												
												$counter++;
												}
											}
										}
									}
								}
								
							else {
								$print_check = sql_aget( "calendar_post", "printDay='".$check_date."'", "*" );
								$counter = 0;
								for( $p = 0; $p < count( $print_check ); $p++ ) {
									if( in_array( $print_check[$p]["magazine_id"], $magazines ) or $print_check[$p]["magazine_id"] == "0" ) {
										$bottom = 16 * $counter;
										$row .= calendarOrderbox( $print_check[$p], $bottom, $check_date, $m, false, $magazines );
										
										$counter++;
										}
									}
								}
							}
						}
					else {
						$counter = 0;
						}
					// Nap	
					//$row .= "<div class='day ".( date( "Y-m-d") == $date ? "currentDay" : "" )."' date='".$date."'>".$boxCount."<br>".$month[$i]."</div>";
					$row .= "<div class='day ".( date( "Y-m-d") == $date ? "currentDay" : "" )."' date='".$date."'>".$month[$i]."</div>";

					//Sales Day Check
					if( $user[0][29] != "print") {
						if( $key == "0" ) {
							$check_date = ($year-1)."-12-".str_pad( $month[$i], 2, '0', STR_PAD_LEFT );
							}
						else {
							$check_date = $year."-".str_pad( $key, 2, '0', STR_PAD_LEFT )."-".str_pad( $month[$i], 2, '0', STR_PAD_LEFT );
							}
						$print_check = sql_aget( "calendar_post", "salesDay='".$check_date."'", "*" );
						for( $p = 0; $p < count( $print_check ); $p++ ) {
							if( in_array( $print_check[$p]["magazine_id"], $magazines ) or $print_check[$p]["magazine_id"] == "0" ) {
								$mag = sql_aget( "magazines", "id='".$print_check[$p]["magazine_id"]."'", "*" );
								$magcolor = sql_get( "magazines", "id='".$print_check[$p]["magazine_id"]."'", "color" );
								if( $print_check[$p]["magazine_id"] == "0" ) {
									$tempcolor = substr( $print_check[$p]["code"],4 , -1 );
									$tempcolor = explode( ", ", $tempcolor );
									$magcolor[0][0] = sprintf("%02x%02x%02x", $tempcolor[0], $tempcolor[1], $tempcolor[2] );
									}
																
								$magcolor = adjustBrightness( $magcolor[0][0], +0 );
								$fontcolor = fontcolor( $magcolor );
								$bottom = 16 * $counter;
	
								$shorten = false;
								
								switch( $user[0][29] ) {
									case "print":
										$check = sql_aget( "calendar_post", "( ".$m." ) AND ( printDay='".$print_check[$p]["salesDay"]."') ", "*" );
										break;
										
									case "sales":
										$check = sql_aget( "calendar_post", "( ".$m." ) AND ( salesDay='".$print_check[$p]["salesDay"]."') ", "*" );
										break;
										
									case "both":
										$check = sql_aget( "calendar_post", "( ".$m." ) AND ( printDay='".$print_check[$p]["salesDay"]."' OR salesDay='".$print_check[$p]["salesDay"]."') ", "*" );
										break;
									}
								
								if( empty( $boxCount ) ) {
									if( count( $check ) >= 4 ) {
										$bottom = ( $bottom / 16 ) * 8;
										$shorten = true;
										}
									}
								
								elseif( $boxCount >= 4 ) {
									$bottom = ( $bottom / 16 ) * 8;
									$shorten = true;
									}
								
								if( $print_check[$p]["magazine_id"] == "0" && in_array( $print_check[$p]["id"], $magazines ) ) {
									$row .= "<div magid='".$print_check[$p]["magazine_id"]."' class='draggable salesOrder orderTile' style='background-color: ".$print_check[$p]["code"]."; color: ".$fontcolor."; bottom: ".$bottom."px;' plannerId='".$print_check[$p]["id"]."'>
											<span class='specdate'>".$print_check[$p]["specificName"]."</span>
											<div class='boxFullDate'>".date( "Y. m. d.", strtotime( $print_check[$p]["salesDay"] ) )."</div>
										</div>";
									}
								elseif( $print_check[$p]["magazine_id"] != "0" ) {
									$row .= "<div ondragstart='dragStart(event)' draggable='".(  $rights["calendar_realdates"] ? "true" : "false" )."' magid='".$print_check[$p]["magazine_id"]."' class='draggable salesOrder orderTile' style='background-color: #".$magcolor."; color: ".$fontcolor."; bottom: ".$bottom."px;' magCode='".$print_check[$p]["magCode"]." ".$print_check[$p]["code"]."' plannerId='".$print_check[$p]["id"]."'>
											".( $shorten ? "<div class='boxType orderTitle'>".( $mag[0]["finishtype"] == "sales" ? "sales day" : "delivery day" )."</div>" : "<div class='orderTitle'>".( $mag[0]["finishtype"] == "sales" ? "sales day" : "delivery day" )."</div>" )."
											<span class='".( strlen( $print_check[$p]["magCode"]." ".$print_check[$p]["code"] ) > 9 ? "thinnerTitle" : "" )."'>".$print_check[$p]["magCode"]." ".$print_check[$p]["code"]."</span>
											".( !empty( $print_check[$p]["specificName"] ) ? "<div class='boxCN'>".$print_check[$p]["specificName"]."</div>" : "" )."
											<div class='boxFullDate'>".date( "Y. m. d.", strtotime( $print_check[$p]["salesDay"] ) )."</div>
										</div>";
									}
								$counter++;
								}
							}
						}
				$row .= "</td>";
				
				$sz++;
				$v++;
				}
			
			$left = calendarLongestMonth( $monthsArray ) - count( $month );
			for( $i = $left; $i > 0; $i-- ) {
				if( $sz == 7 ) $sz = 0;
				if( $v == 8 ) $v = 1;
				
				$row .= "<td class='".( $sz === 6 ? "szombat " : "" )."".( $v === 7 ? "vasarnap " : "" )."'>&nbsp;</td>";
				$sz++;
				$v++;
				}
			
			if( $key == 0 ) {
				$row .= "<td class='dayBox lastMonthBox'>";
					$row .= "12";
					$row .= "<div class='lastYearBox'>".( $year - 1 )."</div>";
				$row .= "</td>";  
				}
			else {
				$row .= "<td class='monthBox'>".$key."</td>"; 
				}
		$row .= "</tr>";		
		}
	

	return $row;
	}

function calendarLongestMonth( $array ) {
	$max = 0;
	
	foreach( $array as $key => $value ) {
		if( count( $value ) > $max ) $max = count( $value );
		}
		
	return $max;
	}

function fileRemove( $data, $path, $ext ) {
	$file = "";
	$temp = sql_get( 'pageinfo', 'id="'.$data[0].'"', '*' );
	if( $temp[0][6] == 'ad' ) {
		$file = $path."/_ads/".str_pad(intval( $data[5] ), 3, '0', STR_PAD_LEFT)."_".$data[1]."_ad_preview.".$ext;
		}
	elseif( $temp[0][6] == 'magazine' ) {
		$pack = sql_get( 'packages', 'id="'.$data[1].'"', '*' );
		if( $temp[0][11] == 1 ) {
			$file = $path."/".$pack[0][4]."/FIN/".str_pad(intval( $data[5] ), 3, '0', STR_PAD_LEFT)."_".$data[1]."_ad_preview.".$ext;
			}
		else {
			$file = $path."/".$pack[0][4]."/".str_pad(intval( $data[5] ), 3, '0', STR_PAD_LEFT)."_".$data[1]."_ad_preview.".$ext;
			}
		}
	
	return $file;
	}

function unvar_dump($str) {
    if (strpos($str, "\n") === false) {
        //Add new lines:
        $regex = array(
            '#(\\[.*?\\]=>)#',
            '#(string\\(|int\\(|float\\(|array\\(|NULL|object\\(|})#',
        );
        $str = preg_replace($regex, "\n\\1", $str);
        $str = trim($str);
    }
    $regex = array(
        '#^\\040*NULL\\040*$#m',
        '#^\\s*array\\((.*?)\\)\\s*{\\s*$#m',
        '#^\\s*string\\((.*?)\\)\\s*(.*?)$#m',
        '#^\\s*int\\((.*?)\\)\\s*$#m',
        '#^\\s*bool\\(true\\)\\s*$#m',
        '#^\\s*bool\\(false\\)\\s*$#m',
        '#^\\s*float\\((.*?)\\)\\s*$#m',
        '#^\\s*\[(\\d+)\\]\\s*=>\\s*$#m',
        '#\\s*?\\r?\\n\\s*#m',
    );
    $replace = array(
        'N',
        'a:\\1:{',
        's:\\1:\\2',
        'i:\\1',
        'b:1',
        'b:0',
        'd:\\1',
        'i:\\1',
        ';'
    );
    $serialized = preg_replace($regex, $replace, $str);
    $func = function ($match) {
        return "s:".strlen($match[1]).":\"".$match[1]."\"";
    };
    $serialized = preg_replace_callback(
        '#\\s*\\["(.*?)"\\]\\s*=>#', 
        $func,
        $serialized
    );
    $func = function ($match) {
        return "O:".strlen($match[1]).":\"".$match[1]."\":".$match[2].":{";
    };
    $serialized = preg_replace_callback(
        '#object\\((.*?)\\).*?\\((\\d+)\\)\\s*{\\s*;#', 
        $func, 
        $serialized
    );
    $serialized = preg_replace(
        array('#};#', '#{;#'), 
        array('}', '{'), 
        $serialized
    );

    return unserialize($serialized);
}

function searchArticlePost( $post, $name, $page ) {
	$hold = 0;
	$jcode = $post["jobCode"];
	$issue = $post["issue"];
	$pageVersion = $post["pageVersion"];	
	$pageType = $post["pageType"];
	
	$type = 0;
	$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
	
	$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
	$pack_id = 0;
	if( $pageVersion != "-baseversion-" ) {	
		echo "első<br>";
		$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
		echo 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"';
		echo $packages[0][0];
		if( $packages[0][0] != '' ) {
			$pack_id = $packages[0][0];
			if( $packages[0][3] == "" ) {
				$type = "alter";
				}
			else {
				$type = "magazine";
				}
			}
		return array( $hold, $pack_id, $type );
		}	
				
	$ads = sql_get( 'ads', 'pub_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
	if( $ads[0][0] != "" ) {
		echo "második";
		$type = "ad";
		if( $ads[0][8] == "Feltöltés alatt" ) {
			$pagenum = $post["pageNum"];
			
			if( $ads[0][3] != "2/1" ) {
				if( $pagenum % 2 == 0 ) {
					$pagenum = $pagenum."-".($pagenum+1);
					}
				else {
					$pagenum = ($pagenum-1)."-".$pagenum;
					}
				}
				
			$names = array( 'uploaded' );
			$values = array( $post["pageNum"] );
			
			$command = '';
			for( $i = 0; $i < count( $names ); $i++ ) {
				$command .= $names[$i].'=\''.$values[$i].'\'';
				if( $i < count( $names )-1 ) {
					$command .= ', ';
					}
				}
		
			if( sql_update( 'ads', $command, 'id=\''.$ads[0][0].'\'' ) ) {}
			
			
			$pack_id = $ads[0][0];
			}
		else {
			$pack_id = $ads[0][0];
			}
		
		return array( $hold, $pack_id, $type );
		}
	
		
	$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'" AND name="'.$name.'"', '*' );
	if( $packages[0][0] != '' ) {
		$pack_id = $packages[0][0];
		
		if( $pageType == "NOR" ) {
			$newPages = "";
			$start = 0; $end = 0;
			$cPages = explode( "-", $packages[0][3] );
			var_dump( $cPages );
			if( $packages[0][3] == "" ) {
				$newPages = $page;
				}
			else {
				if( $cPages[1] != "" ) {
					if( $page <= intval( $cPages[0] ) ) {
						$start = $page;
						$end = $cPages[1];
						}
					else {
						$start = $cPages[0];
						$end = $page;
						}
					$newPages = $start."-".$end;	
					}
				else {
					if( $page <= intval( $cPages[0] ) ) {
						$start = $page;
						$end = intval( $cPages[0] );
						}
					else {
						$start = intval( $cPages[0] );
						$end = $page;
						}
					if( gettype( $end ) != "array" && $start != $end )
						$newPages = $start."-".$end;
					else 
						$newPages = $start;
					}
				}
		
			echo "<br>".$newPages;
			sql_update( 'packages', 'starting_page="'.$newPages.'"', 'id="'.$packages[0][0].'"' );
			$type = "magazine";
			}
		else {
			$type = "alter";
			}
		//die();
		return array( $hold, $pack_id, $type );
		}
	else {
		$names = array( 'publication_id', 'name', 'directory', 'acquired_name', 'starting_page' );
		$values = array( $p_id[0][0], $name, $name, $name );
		if( $pageType == "NOR" ) {
			$values[] = intval( $page );
			}
		else {
			$values[] = "";
			}
		$id = sql_add( 'packages', $names, $values );
		//$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		//$values = array( '', 'newArticle', $p_id[0][1], $p_id[0][2], $p_id[0][10], $name, time(), '' );
		//sql_add( 'action_log', $names, $values );
							
		$oldmask = umask(0);
		@mkdir("../packages/".$jcode, 0777);
		@mkdir("../packages/".$jcode."/".$issue, 0777);
		@mkdir("../packages/".$jcode."/".$issue."/".$name, 0777);
		umask($oldmask);
		
		$packages = sql_get( 'packages', 'id="'.$id.'"', '*' );
		$pack_id = $packages[0][0];	
		if( $pageType == "NOR" ) {
			$newPages = "";
			$start = 0; $end = 0;
			$cPages = explode( "-", $packages[0][3] );
			var_dump( $cPages );
			if( $packages[0][3] == "" ) {
				$newPages = $page;
				}
			else {
				if( $cPages[1] != "" ) {
					if( $page <= intval( $cPages[0] ) ) {
						$start = $page;
						$end = $cPages[1];
						}
					else {
						$start = $cPages[0];
						$end = $page;
						}
					$newPages = $start."-".$end;	
					}
				else {
					if( $page <= intval( $cPages[0] ) ) {
						$start = $page;
						$end = intval( $cPages[0] );
						}
					else {
						$start = intval( $cPages[0] );
						$end = $page;
						}
					if( gettype( $end ) != "array" && $start != $end )
						$newPages = $start."-".$end;
					else 
						$newPages = $start;
					}
				}
		
			echo "<br>".$newPages;
			sql_update( 'packages', 'starting_page="'.$newPages.'"', 'id="'.$packages[0][0].'"' );
			$type = "magazine";
			}
		else {
			$type = "alter";
			}
		//die();
		return array( $hold, $pack_id, $type );
		}
	}

function isMobile() {
    return preg_match("/(android|webos|avantgo|iphone|ipad|ipod|blackbe‌​rry|iemobile|bolt|bo‌​ost|cricket|docomo|f‌​one|hiptop|mini|oper‌​a mini|kitkat|mobi|palm|phone|pie|tablet|up\.browser|up\.link|‌​webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
	}

function produkcioSendmailAttach( $subject, $body, $to, $attach, $string = false ) {
	if( is_file( '/var/www/html/client/plugins/phpmail/PHPMailerAutoload.php' ) ) {
		require_once('/var/www/html/client/plugins/phpmail/PHPMailerAutoload.php');
		}
	else {
		require_once('plugins/phpmail/PHPMailerAutoload.php');
		}
		
	$mail = new PHPMailer();

	$mail->IsSMTP();
	$mail->SMTPDebug = 0;

	$mail->SMTPAuth = true;
	$mail->SMTPSecure = "ssl";
	$mail->Host = "mail.colorcom.hu";
	$mail->Port = "465";

	$mail->Username = "tracker";
	$mail->Password = "Akj7l4d";

	$mail->CharSet = 'utf-8';
	
	$mail->ClearReplyTos();
	$mail->AddReplyTo( "produkcio@colorcom.hu", "Colorcom Prepress" );

	$mail->SetFrom ('tracker@colorcom.hu', 'Colorcom Prepress');
	$mail->Subject = $subject;
	$mail->ContentType = 'text/plain'; 
	$mail->IsHTML(true);

	$mail->Body = "<html><body>".$body."</body></html>";
	$to = explode( "|", $to );
	$mail->AddAddress( $to[1], $to[0] );
	
	if( $string === false ) {
		$mail->AddAttachment( $attach );
		}
	else {
		$mail->addStringAttachment( $attach, $string );
		}
	
	if(!$mail->Send()) {
		error_log( "Mailer Error: " . $mail->ErrorInfo );
		return false;
		}	
	else {
		return true;
		}
	}

function produkcioSendmail( $subject, $body, $to ) {
	if( is_file( '/var/www/html/client/plugins/phpmail/PHPMailerAutoload.php' ) ) {
		require_once('/var/www/html/client/plugins/phpmail/PHPMailerAutoload.php');
		}
	else {
		require_once('plugins/phpmail/PHPMailerAutoload.php');
		}
	

	$mail = new PHPMailer();

	$mail->IsSMTP();
	$mail->SMTPDebug = 0;

	$mail->SMTPAuth = true;
	$mail->AuthType = "CRAM-MD5";
	$mail->SMTPSecure = "ssl";
	$mail->Host = "mail.colorcom.hu";
	$mail->Port = "465";

	$mail->Username = "tracker";
	$mail->Password = "Akj7l4d";
	
	$mail->AddReplyTo( "produkcio@colorcom.hu", "Colorcom Prepress" );
	$mail->CharSet = 'utf-8';
	$mail->SetFrom ('tracker@colorcom.hu', 'Colorcom Tracker');
	$mail->Subject = $subject;
	$mail->ContentType = 'text/plain'; 
	$mail->IsHTML(true);

	$mail->Body = "<html><body>".$body."</body></html>";;
	$to = explode( "|", $to );
	error_log("LEVÉL KÜLDÉS: ".$to[1]." , ".$to[0]."" );
	$mail->AddAddress( $to[1], $to[0] );
	
	if(!$mail->Send()) {
		error_log( "Mailer Error: " . $mail->ErrorInfo );
		return false;
		}	
	else {
		return true;
		}
	}

function produkcioSendmail2( $subject, $body, $to ) {
	if( is_file( '/var/www/html/client/plugins/phpmail/PHPMailerAutoload.php' ) ) {
		require_once('/var/www/html/client/plugins/phpmail/PHPMailerAutoload.php');
		}
	else {
		require_once('plugins/phpmail/PHPMailerAutoload.php');
		}
		
	$mail = new PHPMailer();

	$mail->IsSMTP();
	$mail->SMTPDebug = 0;

	$mail->SMTPAuth = true;
	$mail->AuthType = "CRAM-MD5";
	$mail->SMTPSecure = "ssl";
	$mail->Host = "mail.colorcom.hu";
	$mail->Port = "465";

	$mail->Username = "tracker";
	$mail->Password = "Akj7l4d";
	
	$mail->AddReplyTo( "produkcio@colorcom.hu", "Colorcom Prepress" );
	$mail->CharSet = 'utf-8';
	$mail->SetFrom ('tracker@colorcom.hu', 'Colorcom Tracker');
	$mail->Subject = $subject;
	$mail->ContentType = 'text/plain'; 
	$mail->IsHTML(true);
	
	
	$mail->Body = "<html><body>".$body."</body></html>";
	$to = explode( "|", $to );
	$mail->AddAddress( $to[1], $to[0] );
	
	if(!$mail->Send()) {
		error_log( "Mailer Error: " . $mail->ErrorInfo );
		return false;
		}	
	else {
		return true;
		}
	}

function sendMail_( $subject, $body, $to, $attach, $replyto = "" ) {
	if( is_file( '/var/www/html/client/plugins/phpmail/PHPMailerAutoload.php' ) ) {
		require_once('/var/www/html/client/plugins/phpmail/PHPMailerAutoload.php');
		}
	else {
		require_once('plugins/phpmail/PHPMailerAutoload.php');
		}
		
	$mail = new PHPMailer();

	$mail->IsSMTP();
	$mail->SMTPDebug = 0;

	$mail->SMTPAuth = true;
	$mail->SMTPSecure = "ssl";
	$mail->Host = "mail.colorcom.hu";
	$mail->Port = "465";

	$mail->Username = "tracker";
	$mail->Password = "Akj7l4d";
	
	$mail->AddReplyTo( "produkcio@colorcom.hu", "Colorcom Prepress" );
	$mail->CharSet = 'utf-8';
	if( $replyto != "" ) {
		$mail->ClearReplyTos();
		$f = explode( "|", $replyto );
		$mail->AddReplyTo( $f[1], $f[0] );
		}
	$mail->SetFrom ('tracker@colorcom.hu', 'Colorcom Tracker');
	$mail->Subject = $subject;
	$mail->ContentType = 'text/plain'; 
	$mail->IsHTML(true);

	$mail->Body = "<html><body>".$body."</body></html>";;
	$to = explode( "|", $to );
	$mail->AddAddress( $to[1], $to[0] );
	$mail->AddBCC( $f[1], $f[0] );
	$mail->AddAttachment( $attach );
	
	if(!$mail->Send()) {
		error_log( "Mailer Error: " . $mail->ErrorInfo );
		return false;
		}	
	else {
		return true;
		}
	}

function wfSendMail( $subject, $body, $to, $cc ) {
	require_once( TRKPATH.'/plugins/phpmail/PHPMailerAutoload.php');
		
	$mail = new PHPMailer();

	$mail->IsSMTP();
	$mail->SMTPDebug = 0;
	$mail->Timeout = 120;
	$mail->SMTPAuth = true;
	$mail->AuthType = "CRAM-MD5";
	$mail->SMTPSecure = "ssl";
	$mail->Host = MAIL_HOST;
	$mail->Port = MAIL_PORT;

	$mail->Username = MAIL_WF_USERNAME;
	$mail->Password = MAIL_WF_PASS;
	
	$mail->AddReplyTo( "produkcio@colorcom.hu", "Colorcom Prepress" );
	$mail->CharSet = 'utf-8';
	$mail->Encoding = 'base64';
	$mail->SetFrom ( MAIL_WF_EMAIL, MAIL_WF_NAME );
	$mail->Subject = $subject;
	$mail->ContentType = 'text/html'; 
	$mail->IsHTML(true);
	
	//$body = str_replace( "http://", "https://", $body );
	$mail->Body = "<html><body>".$body."</body></html>";;
	$to = explode( "|", $to );
	$mail->AddAddress( $to[1], $to[0] );

	if(!$mail->Send()) {
		error_log( "Mailer Error: " . $mail->ErrorInfo );
		}	
	}

function sendMail( $subject, $body, $to, $cc = "" ) {
	require_once( TRKPATH.'/plugins/phpmail/PHPMailerAutoload.php');
		
	$mail = new PHPMailer();

	$mail->IsSMTP();
	$mail->SMTPDebug = 0;
	$mail->Timeout = 120;
	$mail->SMTPAuth = true;
	$mail->AuthType = "CRAM-MD5";
	$mail->SMTPSecure = "ssl";
	$mail->Host = MAIL_HOST;
	$mail->Port = MAIL_PORT;

	$mail->Username = MAIL_USERNAME;
	$mail->Password = MAIL_PASS;

	$mail->CharSet = 'utf-8';
	$mail->Encoding = 'base64';
	$mail->SetFrom ( MAIL_EMAIL, MAIL_NAME );
	$mail->Subject = $subject;
	$mail->ContentType = 'text/html'; 
	$mail->IsHTML(true);
	
	//$body = str_replace( "http://", "https://", $body );
	$mail->Body = "<html><body>".$body."</body></html>";;
	$to = explode( "|", $to );
	$mail->AddAddress( $to[1], $to[0] );

	if(!$mail->Send()) {
		error_log( "Mailer Error: " . $mail->ErrorInfo );
		}	
	}

function adThumbCreate3( $path, $file, $to ) {
	$terminal = "/var/www/html/client";
	$trim = getPDFBox2( "Trimbox, Mediabox", "/var/www/html/client/".$file );

	if( count( $trim["Trimbox"] ?? array() ) > 0 ) {
		$trim = $trim["Trimbox"];
		}
	else {
		$trim = $trim["Mediabox"];
		}
	
	echo implode( " | ", $trim )."<br>";
	
	$width = intval( $trim[2] ) - intval( $trim[0] );
	$height = intval( $trim[3] ) - intval( $trim[1] );
	
	error_log( $width." = ".intval( $trim[2] )." - ".intval( $trim[0] )." )" );
	error_log( $height." = ".intval( $trim[3] )." - ".intval( $trim[1] )." )" );
	
	if( $width > $height ) {
		$tWidth = 150;
		$percent = $tWidth/( intval( $trim[2] ) - intval( $trim[0] ) )*100;
		$tHeight = ceil( ( intval( $trim[3] ) - intval( $trim[1] ) ) / 100 * $percent );
		}
	else {
		$tHeight = 150;
		$percent = $tHeight/( intval( $trim[3] ) - intval( $trim[1] ) )*100;
		$tWidth = ceil( ( intval( $trim[2] ) - intval( $trim[0] ) ) / 100 * $percent );
		}

	$from = $terminal."/".$file;
	$to = $terminal."/".$path."/".$to;
		
	$command = './r3 -binary -mode:RENDER -left:'.($trim[0]).' -right:'.($trim[2]-14.1732).' -bottom:'.$trim[1].' -top:'.($trim[3]-14.1732).' -width:'.$tWidth.' -height:'.$tHeight.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$from.' $@ >'.$to.' 2>&1';
	
	error_log( $command );
	
	$result = shell_exec('
		cd /var/www/html/r3API/r3 2>&1;
		'.$command.';
		');	
	
	error_log( $command );
	
	$handle = fopen( "r3Thumb.txt", 'a+');
	if( $handle === false ) {
		return false;
		}
	
	if( fwrite( $handle, $command . "\n\n" ) === false ) {
		return false;
		}

	if( fwrite( $handle, $result . "\n\n" ) === false ) {
		return false;
		}

	fclose( $handle );

	echo $result;
	}

function adThumbCreate2( $path, $file, $to ) {
	$terminal = "/var/www/html/client";
	$trim = getPDFBox2( "Trimbox, Mediabox", "/var/www/html/client/engine/switch/".$file );
	if( count( $trim["Trimbox"] ?? array() ) > 0 ) {
		$trim = $trim["Trimbox"];
		}
	else {
		$trim = $trim["Mediabox"];
		}
	
	echo implode( " | ", $trim )."<br>";
	
	$width = intval( $trim[2] ) - intval( $trim[0] );
	$height = intval( $trim[3] ) - intval( $trim[1] );
	
	if( $width > $height ) {
		$tWidth = 300;
		$percent = $tWidth/( intval( $trim[2] ) - intval( $trim[0] ) )*100;
		$tHeight = ceil( ( intval( $trim[3] ) - intval( $trim[1] ) ) / 100 * $percent );
		}
	else {
		$tHeight = 300;
		$percent = $tHeight/( intval( $trim[3] ) - intval( $trim[1] ) )*100;
		$tWidth = ceil( ( intval( $trim[2] ) - intval( $trim[0] ) ) / 100 * $percent );
		}

	$from = $terminal."/engine/switch/".$file;
	$to = $terminal."/".$path."/".$to;
		
	$command = './r3 -binary -mode:RENDER -left:'.($trim[0]).' -right:'.($trim[2]-14.1732).' -bottom:'.$trim[1].' -top:'.($trim[3]-14.1732).' -width:'.$tWidth.' -height:'.$tHeight.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$from.' $@ >'.$to.' 2>&1';
	
	error_log( $command );
	
	$result = shell_exec('
		cd /var/www/html/r3API/r3 2>&1;
		'.$command.';
		');	
	
	$handle = fopen( "r3Thumb.txt", 'a+');
	if( $handle === false ) {
		return false;
		}
	
	if( fwrite( $handle, $command . "\n\n" ) === false ) {
		return false;
		}

	if( fwrite( $handle, $result . "\n\n" ) === false ) {
		return false;
		}

	fclose( $handle );

	echo $result;
	}

function adThumbCreate( $path, $file, $to ) {
	$terminal = "/var/www/html/client";
	$trim = getPDFBox( "Trimbox ", "message/".$file );
	if( count( $trim["Trimbox"] ?? array() ) > 0 ) {
		$trim = $trim["Trimbox"];
		}
	else {
		$trim = $trim["Mediabox"];
		}
	
	echo implode( " | ", $trim )."<br>";
	
	$width = intval( $trim[2] ) - intval( $trim[0] );
	$height = intval( $trim[3] ) - intval( $trim[1] );
	
	if( $width > $height ) {
		$tWidth = 150;
		$percent = $tWidth/( intval( $trim[2] ) - intval( $trim[0] ) )*100;
		$tHeight = ceil( ( intval( $trim[3] ) - intval( $trim[1] ) ) / 100 * $percent );
		}
	else {
		$tHeight = 150;
		$percent = $tHeight/( intval( $trim[3] ) - intval( $trim[1] ) )*100;
		$tWidth = ceil( ( intval( $trim[2] ) - intval( $trim[0] ) ) / 100 * $percent );
		}

	$from = $terminal."/message/".$file;
	
	$to = $terminal."/".$path."/".$to;
	
	$command = './r3 -binary -mode:RENDER -left:'.($trim[0]).' -right:'.($trim[2]-14.1732).' -bottom:'.$trim[1].' -top:'.($trim[3]-14.1732).' -width:'.$tWidth.' -height:'.$tHeight.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$from.' $@ >'.$to.' 2>&1';
	
	echo $command."<br>";
	$result = shell_exec('
		cd /var/www/html/r3API/r3 2>&1;
		'.$command.';
		');	

	$handle = fopen( "r3Thumb.txt", 'a+');
	if( $handle === false ) {
		return false;
		}
	
	if( fwrite( $handle, $command . "\n\n" ) === false ) {
		return false;
		}

	if( fwrite( $handle, $result . "\n\n" ) === false ) {
		return false;
		}

	fclose( $handle );

	echo $result;
	}

function thumbCreate2( $file, $pageWidth, $color = "" ) {
	$trim = getPDFBox2( "Trimbox ", $file );
	$trim = $trim["Trimbox"];

	$height = 400;
	$percent = $height/( intval( $trim[3] ) - intval( $trim[1] ) )*100;
	$width = ceil( ( intval( $trim[2] ) - intval( $trim[0] ) ) / 100 * $percent );
	if( intval( $pageWidth ) > 1 ) $width *= $pageWidth;
	
	
	$from = $file;
	$to = substr($file, 0, -4).".jpg";
	
	if( empty( $color ) ) {
		$color = "FOGRA_39";
		}
	$iccProfile = resolveIccProfileByName( $color );

	error_log( "R3 DEBUG" );
	error_log( './r3 -binary -mode:RENDER -left:'.$trim[0].' -right:'.$trim[2].' -bottom:'.$trim[1].' -top:'.$trim[3].' -width:'.$width.' -height:'.$height.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' $@ >'.$to.' 2>&1' );

	$command = './r3 -binary -mode:RENDER -left:'.$trim[0].' -right:'.$trim[2].' -bottom:'.$trim[1].' -top:'.$trim[3].' -width:'.$width.' -height:'.$height.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' $@ >'.$to.' 2>&1';
	
	error_log( $command );
	
	$result = shell_exec('
		cd /var/www/html/r3API/r3 2>&1;
		'.$command.';
		');	

	$handle = fopen( "r3Thumb.txt", 'a+');
	if( $handle === false ) {
		return false;
		}
	
	if( fwrite( $handle, $command . "\n\n" ) === false ) {
		return false;
		}
	fclose( $handle );
	}
	
function thumbCreate( $path, $file, $to, $pageWidth ) {
	$terminal = "/var/www/html/client";
	$file = substr( $file, 3 );
	$trim = getPDFBox( "Trimbox ", $file );
	$trim = $trim["Trimbox"];

	$height = 125;
	$percent = $height/( intval( $trim[3] ) - intval( $trim[1] ) )*100;
	$width = ceil( ( intval( $trim[2] ) - intval( $trim[0] ) ) / 100 * $percent );
	if( intval( $pageWidth ) > 1 ) $width *= $pageWidth;
	
	$from = $terminal."/".$file;
	
	$to = explode( "/", $to );
	$to = substr($to[( count($to)-1 )], 0, -4).".jpg";
	$to = $terminal."/".substr( $path, 3 )."/".$to;
	
	$command = './r3 -binary -mode:RENDER -left:'.$trim[0].' -right:'.$trim[2].' -bottom:'.$trim[1].' -top:'.$trim[3].' -width:'.$width.' -height:'.$height.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$from.' $@ >'.$to.' 2>&1';
		
	$result = shell_exec('
		cd /var/www/html/r3API/r3 2>&1;
		'.$command.';
		');	

	$handle = fopen( "r3Thumb.txt", 'a+');
	if( $handle === false ) {
		return false;
		}
	
	if( fwrite( $handle, $command . "\n\n" ) === false ) {
		return false;
		}
	fclose( $handle );
	}

function getFTPList( $xml, $pub ) {
	$ftp = array();
	$xpath = $xml->{$pub[0][0]}->Outward->Content->children();
	foreach( $xpath as $temp ) {
		$node = $temp->getName();
		if( $node != 'Targets' ) {
			$x = $temp->$node;
			$x = $xml->{$pub[0][0]}->Outward->Content->$node->children();
			foreach( $x as $t ) {
				$ftp[$node."_Content"][$t->getName()] = (string) $t;
				}
			}
		}
	$xpath = $xml->{$pub[0][0]}->Outward->Final->children();
	foreach( $xpath as $temp ) {
		$node = $temp->getName();
		if( $node != 'Targets' ) {
			$x = $temp->$node;
			$x = $xml->{$pub[0][0]}->Outward->Final->$node->children();
			foreach( $x as $t ) {
				$ftp[$node."_Final"][$t->getName()] = (string) $t;
				}
			}
		}

	$xpath = $xml->{$pub[0][0]}->Outward->Softproof->children();
	foreach( $xpath as $temp ) {
		$node = $temp->getName();
		if( $node != 'Targets' ) {
			$x = $temp->$node;
			$x = $xml->{$pub[0][0]}->Outward->Softproof->$node->children();
			foreach( $x as $t ) {
				$ftp[$node."_Softproof"][$t->getName()] = (string) $t;
				}
			}
		}
					
	return $ftp;
	}

function dayBreak( $type, $time, $magazine, $issue, $lang, $sum = NULL, $sumFP = NULL, $sumAP = NULL, $process = NULL ) {
	$actual = time();
	$txt = "";
	$timeDate = date( "Ymd", $time );
	
	$counter = 0;
	$height = 0;
	$min = 5;
	$max = 60;
	$special = false;
	if( $type == "all-in-one" ) {
		$fullpage = sql_get( "publications", "magazine_id='".$magazine."' AND code='".$issue."'", "pages" );
		$max = 30;
		$pdf = $indd = 0;	
		if( $issue != "" ) {
			if( $process == "Softproof" ) {
				$check = sql_get( "action_log", "( action='newPage' OR action='updatePage' ) AND magazine='".$magazine."' AND issue='".$issue."'", "action, date" );
				}
			else {
				$check = sql_get( "action_log", "( action='backArticle' OR action='newArticle' OR action='newPage' OR action='updatePage' ) AND magazine='".$magazine."' AND issue='".$issue."'", "action, date" );
				}
			for( $i = 0; $i < count( $check ); $i++ ) {
				$logTime = $check[$i][1];
				$logDate = date( "Ymd", $logTime );
				if( $logDate == $timeDate ) {
					if( $check[$i][0] == "backArticle" or $check[$i][0] == "newArticle" ) {
						$indd++;
						}
					if( $check[$i][0] == "newPage" or $check[$i][0] == "updatePage" ) {
						$pdf++;
						}
					}
				}
			}
		$height = ($indd+$pdf)/(70+$fullpage[0][0])*30;
		if( $height > 0 ) {
			if( $height < $min ) $height = $min;
			if( $height > $max ) $height = $max;
			
			$txt .= "<div class='pubTlData' style='height: ".$height."px;'></div>";
			}
		else {
			$txt = "";
			}
		}
	
	if( $type == "approval" ) {		
		$fullpage = sql_get( "publications", "magazine_id='".$magazine."' AND code='".$issue."'", "pages" );
		$check = sql_get( "action_log", "action='approvePage' AND issue='".$issue."' AND magazine='".$magazine."' GROUP BY `target`", "*" );
		if( count( $check ) > count( $fullpage ) ) $maxPack = count( $check );
		else $maxPack = $fullpage[0][0];
		
		$mag = sql_get( "magazines", "id='".$magazine."'", "*" );
		$check = sql_get( "action_log", "action='approvePage' AND issue='".$issue."' AND magazine='".$magazine."' GROUP BY `target`", "*" );
		for( $x = 0; $x < count( $check ); $x++ ) {
			$logDate = date( "Ymd", $check[$x][7] );
			if( $logDate == $timeDate ) {
				$check2 = sql_get( "pageinfo", "(type ='ad' OR type = 'magazine' ) AND status='2' AND code='".$mag[0][3]."' AND issue='".$issue."' AND page='".$check[$x][6]."'", "id" );
				if( $check2[0][0] != "" ) {
					$counter++;
					}
				}
			}
		$counter += $sumAP;
		$actualDate = date( "Ymd", $actual );
		if( $timeDate > $actualDate ) {
			$counter = 0;
			}
		
		if( $counter >= $fullpage[0][0] ) $special = true;
			
		}
	elseif( $type == "ads" ) {
		$pubf = sql_get( "publications", "magazine_id='".$magazine."' AND code='".$issue."'", "id,deadline" );
		$ads = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2'", "*" );
		$maxPack = count( $ads );
		$tempDate = date( "Y-m-d", $time );
		$check = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2' AND date LIKE '".$tempDate."%'", "*" );
		
		$actualDate = date( "Ymd", $actual );
		$deadDate = date( "Ymd", strtotime( $pubf[0][1] ) );
		$deadDate++;
		if( $timeDate <= $actualDate && $timeDate <= $deadDate )
			$counter = count( $check )+$sum;
		}
	elseif( $type == "indd" ) {
		$maxPack = 70;
		$check = sql_get( "action_log", "( action='backArticle' OR action='newArticle' ) AND magazine='".$magazine."' AND issue='".$issue."'", "*" );
		for( $i = 0; $i < count( $check ); $i++ ) {
			$logTime = $check[$i][7];
			$logDate = date( "Ymd", $logTime );
			if( $logDate == $timeDate ) {
				$counter++;
				}
			}
		}	
	elseif( $type == "pdf" ) {
		$fullpage = sql_get( "publications", "magazine_id='".$magazine."' AND code='".$issue."'", "pages" );
		$maxPack = $fullpage[0][0];
		$check = sql_get( "action_log", "( action='newPage' OR action='updatePage' ) AND magazine='".$magazine."' AND issue='".$issue."'", "*" );
		
		for( $i = 0; $i < count( $check ); $i++ ) {
			$logTime = $check[$i][7];
			$logDate = date( "Ymd", $logTime );
			if( $logDate == $timeDate ) {
				$counter++;
				}
			}
			
		}
	
	if( $type != "all-in-one" ) {
		if( $counter > 0 ) {
			if( $counter < $maxPack ) {
				if( $counter == 1 ) {
					$height = $min;
					}
				else {
					$height = intval( (100/$maxPack*$counter)/100*$max );
					if( $height < $min ) $height = $min;
					}
				}
			else {
				$height = $max;

				}
			}
		if( $special ) {
			$counter = $lang["timeline"]["all"];
			}	
	
		$txt = "<div title='".sprintf( $lang["timeline"]["bar_".$type], $counter )."' class='tlBar ".$type."' style='height: ".$height."px;'></div>";
		if( $type == "ads" && $counter > 0 ) {
			$min = 2;
			$max = $height;
			$check = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2' AND uploaded != '' AND date LIKE '".$tempDate."%'", "*" );
			$counter = count( $check )+$sumFP;
			$height = 0;
			if( $counter > 0 ) {
				if( $counter < $maxPack ) {
					if( $counter == 1 ) {
						$height = $min;
						}
					else {
						$height = intval( (100/$maxPack*$counter)/100*$max );
						if( $height < $min ) $height = $min;
						}
					}
				else {
					$height = $max;
					}
				}
		
			$txt .= "<div title='".$counter."' class='tlBar adsFP' style='height: ".$height."px;'></div>";
			}
		}
	return $txt;
	}

function isWeekend($date) {
    return (date('N', $date ) >= 6);
	}

function colorPick( $pdfPath, $x, $y ) {
	$command = './r3 -mode:MEASURE -x:'.$x.' -y:'.$y.' -tprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$pdfPath.' 2>&1';
	$command = shell_exec('
			cd /var/www/html/r3API/r3 2>&1;
			'.$command.';
			');
	
	return preg_split('/[\r\n]+/', $command);
	}

function strposa(string $haystack, array $needles, int $offset = 0): bool 
{
    foreach($needles as $needle) {
        if(strpos($haystack, $needle, $offset) !== false) {
            return true; // stop on first true result
        }
    }

    return false;
}

function getAllColors( $pageinfo ) {
	$colors = array();
	$titles = array();
	
	error_log( "PANTON CHECK:");
	error_log( print_r( $pageinfo[0][15], true ) );
	$defcolors = array( "CYAN", "MAGENTA", "YELLOW", "BLACK" );
	
	$pantone = preg_split('/[\r\n]+/', $pageinfo[0][15] );
	for( $i = 0; $i < count( $pantone )-1; $i++ ) {
		//if( strpos( strtoupper( $pantone[$i] ), "PANTONE" ) !== false ) {
		if( strposa( strtoupper( $pantone[$i] ), $defcolors ) === false ) {
			$temp = explode( " =", $pantone[$i] );
			$titles[] = $temp[0];
			
			$temp = explode( " ", $pantone[$i] );
			$colors[] = $temp[ count($temp)-3 ].", ".$temp[ count($temp)-2 ].", ".$temp[ count($temp)-1 ];		
			}
		}
		
	return array( $titles, $colors );
	}

function getColorTitles( $pdfPath ) {
	$titles = array();
	$command = './r3 -mode:MEASURE -x:596 -y:760 -d:1 -r:600 -tprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$pdfPath.' 2>&1';
	$command = shell_exec('
			cd /var/www/html/r3API/r3 2>&1;
			'.$command.';
			');
	
	$pantone = preg_split('/[\r\n]+/', $command);
	$defcolors = array( "CYAN", "MAGENTA", "YELLOW", "BLACK" );
	for( $i = 0; $i < count( $pantone )-1; $i++ ) {
		if( strpos( strtoupper( $pantone[$i] ), "PANTONE" ) !== false ) {
			$temp = explode( " =", $pantone[$i] );
			$titles[] = $temp[0];
			}
    }
    
	return $titles;  
  }

function getColors( $pdfPath ) {
	$colors = array();
	$command = './r3 -mode:MEASURE -x:596 -y:760 -d:1 -r:600 -tprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$pdfPath.' 2>&1';
	$command = shell_exec('
			cd /var/www/html/r3API/r3 2>&1;
			'.$command.';
			');
	
	$pantone = preg_split('/[\r\n]+/', $command);
	for( $i = 0; $i < count( $pantone )-1; $i++ ) {
    if( strpos( $pantone[$i], "PANTONE" ) !== false ) {
      $temp = explode( " ", $pantone[$i] );
      $colors[] = $temp[ count($temp)-3 ].", ".$temp[ count($temp)-2 ].", ".$temp[ count($temp)-1 ];
      }
    }
    
	return $colors;  
  }

function checkPageStatus( $page, $id, $pack_id, $alter, $state = '', $issue = null, $magazine = null, $part = "" ) {
	if( $alter == "" ) {
		$alter = 'type!="PRE" AND type!="PSTR" AND fin="0"';
		}
	elseif( $alter == "FIN" ) {
		$alter = 'type!="PRE" AND type!="PSTR" AND fin="1"';
		}
	else {
		$alter = 'type="'.$alter.'"';
		}	
		
	$checker = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND '.$alter.' AND state="'.$state.'" AND code="'.$magazine[0][3].'" AND page="'.$page.'" AND part="'.$part.'"', '*' );
	return array( $checker[0][4], $checker[0][0] );
	}

function checkPagePair( $id, $pack_id, $page, $tag, $alter = '', $prefix = '', $issue = null, $magazine = null, $part = null, $pn = null ) {
	switch( $prefix ) {
		case "prev":
			$prefix = ".";
			break;
		}
	$pack = sql_get( 'packages', 'id="'.$pack_id.'"', '*' );
	$max = intval( $issue[0][6] )+1;
	if( $max == 1 ) {
		$checker = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" order by page DESC LIMIT 1', '*' );
		$max = intval( $checker[0][5] );
		}
	
	if( $part != "" ) {
		$part = 'AND part="'.$part.'"';
		}
	
	$checker = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND page="'.( intval( $page )+1 ).'" '.$part.'', '*' );
	if( empty( $checker[0] ) ) {
		$checker = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND page="'.( intval( $page )+1 ).'" AND part=""', '*' );
		}
	//error_log( "DEBUG: ".$checker[0][0] );
	
	$pages = array( intval( $page ) );
	
	if( bcmod( intval( $page ), 2 ) == 0 && $checker[0][9] == 1 ) {
		if( $alter != '' and $alter != "FIN" ) {
			$pages[] = intval( $page )+1;
			}
		else {
			if( ( ( intval( $page )+1 ) <= $max ) or !empty( $checker[0][16] ) ) {
				$pages[] = intval( $page )+1;
				}
			}
		}
	error_log( $pages[0].", ".$pages[1] );
	$i = 0;
	
	foreach( $pages as $page ) {
		$file2 = '';
		if( $alter != "" and $alter != "FIN" ) {
			$dir = $prefix."./packages/".$magazine[0][3]."/".$issue[0][10]."/_".strtoupper( $_GET['alter'] );
			$pageinfo = sql_get( 'pageinfo', 'type="'.$alter.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$page.'"', '*' );
			$file2 = str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_preview.pdf";
			}
		elseif( $tag != "" and $alter != "FIN" ) {	
			$pageinfo = sql_get( 'pageinfo', 'state="'.$tag.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$page.'"', '*' );
			$packinfo = sql_aget( "packages", "id='".$pageinfo[0][1]."'", "name, directory" );
			$dir = $prefix."./packages/".$magazine[0][3]."/".$issue[0][10]."/".$packinfo[0]["directory"];
			if( $pageinfo[0][11] == 1 ) {
				$dir.= "/FIN";
				}
			$file2 = str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
			
			}
		else {
			if( $alter == "FIN" ) $fin = ' AND fin="1"';
			else $fin = " AND fin='0'";
			
			$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND'.( $tag != "" ? ' state="'.$tag.'" AND' : ' state="" AND' ).' code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$page.'"'.$fin, '*' );

			if( $pageinfo[0][6] == "ad" ) {
				$dir = $prefix."./packages/".$magazine[0][3]."/".$issue[0][10]."/_ads";
				$file2 = str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."ad_preview.pdf";
				}
			else {
				$pack = sql_get( 'packages', 'id="'.$pageinfo[0][1].'"', '*' );
				$dir = $prefix."./packages/".$magazine[0][3]."/".$issue[0][10]."/".$pack[0][4];
				if( $alter == "FIN" ) $dir .= "/FIN";
				$file2 = str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
				}
				
			$dir = str_replace( "//", "/",  $dir);
			}
			
		if( !is_file( $dir."/".$file2 ) ) {
			array_splice( $pages, $i, 1);
			}
		$i++;
		}
		
	return $pages;
	}

function pixel_( $num, $zoom ) {
	return $num * $zoom / 72;
	}

function point_( $num, $zoom ) {
	return $num * 72 / $zoom;
	}

function getshortpubs3( $user ) {
	$mags = explode( ",", $user[21] );
	$pubs = array();
	$list = array();
	
	$ignore = array( "archived", "approved", "archiving", "stopped", "archive_failed" );
	for( $i = 0; $i < count( $mags ); $i++ ) {
		$temp = sql_get( "publications", "magazine_id='".$mags[$i]."'", "*" );
		for( $y = 0; $y < count( $temp ); $y++ ) {
			if( !in_array( $temp[$y][12], $ignore ) ) {
				//$pubs[] = $temp[$y];
				
				$mag = sql_aget( "magazines", "id='".$temp[$y][2]."'", "*" );
				if( $mag[0]["type"] == "Regular" ) {
					$list[$temp[$y][0]] = $mag[0]["code"]." ".$temp[$y][10];
					}
				else {
					$list[$temp[$y][0]] = $temp[$y][10];
					}
				}
			}
		}
	
	asort($list);
	foreach( $list as $key=>$val ) {
		$temp = sql_get( "publications", "id='".$key."'", "*" );
		if( !empty( $temp[0][0] ) ) {
			$pubs[] = $temp[0];
			}
		}	
	
	return $pubs;
	}

function getshortpubs2( $user ) {
	$mags = explode( ",", $user[21] );
	$pubs = array();
	$list = array();
	
	//$ignore = array( "archived", "approved", "archiving", "stopped" );
	$ignore = array();
	for( $i = 0; $i < count( $mags ); $i++ ) {
		$temp = sql_get( "publications", "magazine_id='".$mags[$i]."'", "*" );
		for( $y = 0; $y < count( $temp ); $y++ ) {
			if( !in_array( $temp[$y][12], $ignore ) ) {
				//$pubs[] = $temp[$y];

				$mag = sql_aget( "magazines", "id='".$temp[$y][2]."'", "*" );
				if( $mag[0]["type"] == "Regular" ) {
					$list[$temp[$y][0]] = $mag[0]["code"]." ".$temp[$y][10];
					}
				else {
					$list[$temp[$y][0]] = $temp[$y][10];
					}				
				}
			}
		}

	asort($list);
	foreach( $list as $key=>$val ) {
		$temp = sql_get( "publications", "id='".$key."'", "*" );
		if( !empty( $temp[0][0] ) ) {
			$pubs[] = $temp[0];
			}
		}	
		
	return $pubs;
	}

function getshortpubs4( $user ) {
	$mags = explode( ",", $user[21] );
	$pubs = array();
	$list = array();
	
	for( $i = 0; $i < count( $mags ); $i++ ) {
		$temp = sql_get( "publications", "magazine_id='".$mags[$i]."'", "*" );

		for( $y = 0; $y < count( $temp ); $y++ ) {
			//$pubs[] = $temp[$y];
			$mag = sql_aget( "magazines", "id='".$temp[$y][2]."'", "*" );
			if( $mag[0]["type"] == "Regular" ) {
				$list[$temp[$y][0]] = $mag[0]["code"]." ".$temp[$y][10];
				}
			else {
				$list[$temp[$y][0]] = $temp[$y][10];
				}
			}
		}

	asort($list);
	foreach( $list as $key=>$val ) {
		$temp = sql_get( "publications", "id='".$key."'", "*" );
		if( !empty( $temp[0][0] ) ) {
			$pubs[] = $temp[0];
			}
		}	

	return $pubs;
	}

function getshortpubs( $user ) {
	$mags = explode( ",", $user[21] );
	$pubs = array();
	$list = array();

	$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
	$xpath = $xml->xpath('/Publications');
	
	for( $i = 0; $i < count( $mags ); $i++ ) {
		$temp = sql_get( "publications", "magazine_id='".$mags[$i]."'", "*" );
		$magazine = sql_get( 'magazines', 'id="'.$mags[$i].'" LIMIT 1', '*' );
			
		foreach($xpath as $temp2) {
			for( $x = 0; $x < count( $temp2->Item ); $x++ ) {
				if( $temp2->Item[$x]->Code == $magazine[0][3] ) {
					break;
					}
				}
			}
		$workflow = (string) $xml->Item[$x]->Workflow;	
		
		if( $workflow != "Resize" && $workflow != "Enhance" ) {
			for( $y = 0; $y < count( $temp ); $y++ ) {
				//$pubs[] = $temp[$y];

				$mag = sql_aget( "magazines", "id='".$temp[$y][2]."'", "*" );
				if( $mag[0]["type"] == "Regular" ) {
					$list[$temp[$y][0]] = $mag[0]["code"]." ".$temp[$y][10];
					}
				else {
					$list[$temp[$y][0]] = $temp[$y][10];
					}
				}
			}
		}

	asort($list);
	foreach( $list as $key=>$val ) {
		$temp = sql_get( "publications", "id='".$key."'", "*" );
		if( !empty( $temp[0][0] ) ) {
			$pubs[] = $temp[0];
			}
		}	

	return $pubs;
	}

function mmToPsp( $mm ) {
	return ($mm*2.83464567);
	}

function getPDFBox2( $box, $file ) {
	$data = array();
	$boxes = explode( " ", $box );
	$command = "./r3 -mode:GETDATA -metadata ".$file;
	
	//error_log( $command );
	$command = shell_exec('
			cd /var/www/html/r3API/r3 2>&1;
			'.$command.';
			');
	//error_log( $command );
	
	$command = explode( "\n", $command );
	for( $i = 0; $i < 4; $i++ ) {
		$temp = explode( " = ", $command[$i] );
		if( in_array( ucfirst( strtolower( $temp[0] ) ), $boxes ) ) {
			$temp[1] = explode( " ", $temp[1] );
			for( $y = 1; $y < 5; $y++ ) {
				$data[ ucfirst( strtolower( $temp[0] ) ) ][] = $temp[1][$y];
				}			
			}
		}
	
	return $data;
	}

function getPDFBox( $box, $pageinfo ) {
	$data = array();
	$boxes = explode( " ", $box );
	
	//error_log( "Pageinfo ID: ".$pageinfo[0][0] );
	if( !empty( $pageinfo[0][14] ) ) {
		//error_log( "NEM ÜRES" );
		$command = explode( "\n", $pageinfo[0][14] );
		}
	else {
		//error_log( "ÜRES" );
		$pack = sql_get( 'packages', 'id="'.$pageinfo[0][1].'" LIMIT 1', '*' );
		$issue = sql_get( 'publications', 'id="'.$pack[0][1].'" LIMIT 1', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'" LIMIT 1', '*' );		
		
		$dir = TRKPATH."/packages/".$magazine[0][3]."/".$issue[0][10];
		
		if( $pageinfo[0][6] == "ad" ) {
			$dir .= "/_ads";
			$file = str_pad( $pageinfo[0][5], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$pageinfo[0][8]."ad_preview.pdf";
			}
			
		else {
			$pack = sql_get( 'packages', 'id="'.$pageinfo[0][1].'"', '*' );
			if( $pageinfo[0][6] == "PRE" ) {
				$dir .= "/_PRE";
				}
			else {
				$dir .= "/".$pack[0][4];
				}
			
			if( $pageinfo[0][11] == "1" ) $dir .= "/FIN";
			
			
			$file = str_pad( $pageinfo[0][5], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$pageinfo[0][8]."preview.pdf";
			
			}		
		
		$file = $dir."/".$file;
		
		$command = "./r3 -mode:GETDATA -metadata ".$file;
		
		//error_log( $command );
		$command = shell_exec('
				cd /var/www/html/r3API/r3 2>&1;
				'.$command.';
				');
		//error_log( $command );
		
		$command = explode( "\n", $command );	
		for( $t = 0; $t < 4; $t++ ) {
			$temp[] = $command[$t];
			}
					
		$text = implode( "\n", $temp );	
		sql_update( "pageinfo", "boxes='".$text."'", "id='".$pageinfo[0][0]."'" );
		}
		
	for( $i = 0; $i < 4; $i++ ) {
		$temp = explode( " = ", $command[$i] );
		if( in_array( ucfirst( strtolower( $temp[0] ) ), $boxes ) ) {
			$temp[1] = explode( " ", $temp[1] );
			for( $y = 1; $y < 5; $y++ ) {
				$data[ ucfirst( strtolower( $temp[0] ) ) ][] = $temp[1][$y];
				}			
			}
		}
	
	if( empty( $data["Trimbox"] ) ) {
		$data["Trimbox"] = $data["Mediabox"];
		}
	return $data;
	}

function getPDFBox_TEMP( $box, $file ) {
	$data = array();
	$boxes = explode( " ", $box );
	
	$command = "./r3 -mode:GETDATA -metadata ".$file;	
	$command = shell_exec('
			cd /var/www/html/r3API/r3 2>&1;
			'.$command.';
			');
	
	$command = explode( "\n", $command );
	for( $i = 0; $i < 4; $i++ ) {
		$temp = explode( " = ", $command[$i] );
		if( in_array( ucfirst( strtolower( $temp[0] ) ), $boxes ) ) {
			$temp[1] = explode( " ", $temp[1] );
			for( $y = 1; $y < 5; $y++ ) {
				$data[ ucfirst( strtolower( $temp[0] ) ) ][] = $temp[1][$y];
				}			
			}
		}
	
	return $data;
	}

function getBBoxMeasure( $file, $path, $box = 'mediabox' ) {
	$rustart = microtime(true);
	$pdf = new dynapdf();
	
	include('/var/www/html/engine/config.inc.php');
	$pdf->CreateNewPDF( NULL );

	$pdf->OpenImportFile( $file , dynapdf::ptOpen, NULL );
	$pdf->ImportPDFFile( 1, 1.0, 1.0 );	
	$pdf->CloseImportfile();
	
	$pdf->EditPage(1);
		switch( $box ) {
			case 'cropbox':
				$sizes = $pdf->GetBBox( dynapdf::pbCropBox );
				break;
			case 'bleedbox':
				$sizes = $pdf->GetBBox( dynapdf::pbBleedBox );
				break;
			case 'trimbox':
				$sizes = $pdf->GetBBox( dynapdf::pbTrimBox );
				break;
			case 'mediabox':
				$sizes = $pdf->GetBBox( dynapdf::pbMediaBox );
				break;
			}
		$sizes["Width"] = floatval( $pdf->GetPageWidth() );
		$sizes["Height"] = floatval( $pdf->GetPageHeight() );
	$pdf->EndPage();
	$ru = microtime(true);
	echo "Information acquired time: " . rutime($ru, $rustart, "stime") ." ms ";
	
	return $sizes;
	}

function getBBox( $file, $path, $box = 'mediabox' ) {
	//error_log( "DEBUG ");
	//error_log( $file );
	//error_log( strpos( $file, "/var/www/html/client" ) );
	if( strpos( $file, "/var/www/html/client" ) === false ) {
		$file = TRKPATH."/".str_replace( "../", "", $file );
		}
	
	$box = ucfirst( strtolower( $box ) );
	$boxes = explode( " ", $box );
	
	// echo $file."<br>";
	
	$command = "./r3 -mode:GETDATA -metadata ".$file;
	// echo $command."<br>";
	//error_log( $command );
	
	$command = shell_exec('
			cd /var/www/html/r3API/r3 2>&1;
			'.$command.';
			');	
	
	//error_log( $command );
	// echo $command."<br>";
	$command = explode( "\n", $command );
	for( $i = 0; $i < 4; $i++ ) {
		$temp = explode( " = ", $command[$i] );
		if( in_array( ucfirst( strtolower( $temp[0] ) ), $boxes ) ) {
			$temp[1] = explode( " ", $temp[1] );
			
			$data[ ucfirst( strtolower( $temp[0] ) ) ]["Left"] = $temp[1][1];
			$data[ ucfirst( strtolower( $temp[0] ) ) ]["Bottom"] = $temp[1][2];
			$data[ ucfirst( strtolower( $temp[0] ) ) ]["Right"] = $temp[1][3];
			$data[ ucfirst( strtolower( $temp[0] ) ) ]["Top"] = $temp[1][4];
			$data[ ucfirst( strtolower( $temp[0] ) ) ]["Width"] = $temp[1][3] - $temp[1][1];
			$data[ ucfirst( strtolower( $temp[0] ) ) ]["Height"] = $temp[1][4] - $temp[1][2];
			}
		}
	
	$result = $data[ ucfirst( strtolower( $box ) ) ];
	
	return $result;
	}

function nevelo( $string, $lang ) {
	$string = trim( strtolower( $string[0] ) );
	$maganhangzo = array( 'a', 'á', 'e', 'é', 'i', 'í', 'o', 'ó', 'u', 'ú', 'ü', 'ű', 'ö', 'ő' );
	
	if( in_array( $string, $maganhangzo ) ) {
		return $lang["log"]["az"];
		}
	else {
		return $lang["log"]["a"];
		}
	}

function nevelo2( $string, $lang ) {
	$string = trim( strtolower( $string[0] ) );
	$maganhangzo = array( 'a', 'á', 'e', 'é', 'i', 'í', 'o', 'ó', 'u', 'ú', 'ü', 'ű', 'ö', 'ő' );
	
	if( in_array( $string, $maganhangzo ) ) {
		return $lang["log"]["az2"];
		}
	else {
		return $lang["log"]["a2"];
		}
	}

function nevelo3( $string, $lang ) {
	$string = trim( strtolower( $string[0] ) );
	$maganhangzo = array( 'a', 'á', 'e', 'é', 'i', 'í', 'o', 'ó', 'u', 'ú', 'ü', 'ű', 'ö', 'ő' );
	
	if( in_array( $string, $maganhangzo ) ) {
		return $lang["log"]["az3"];
		}
	else {
		return $lang["log"]["a3"];
		}
	}

function logToString( $log, $lang, $pubCount ) { 
	$txt = "";
	$magazine = sql_get( 'magazines', 'id="'.$log[4].'"', '*' );

	$txt .= "<div>";
	switch( $log[2] ) {
		case 'rejectedPage':
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["rejected_page"], $log[6], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["rejected_page_single"], $log[6], $magazine[0][2], $log[5] );
				}		
			break;			
		case 'approvePage':
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["approved_page"], $log[6], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["approved_page_single"], $log[6] );
				}		
			break;
		case 'approveComment':
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["c_approve"], $log[6], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["c_approve_single"], $log[6] );
				}		
			break;
		case 'newCReply':
			$reply = sql_get( 'comments', 'id="'.$log[6].'"', 'page' );	
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["c_reply"], $reply[0][0], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["c_reply_single"], $reply[0][0] );
				}		
			break;
		case 'newComment':
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["c_new"], $log[6], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["c_new_single"], $log[6] );
				}		
			break;
		case 'newPage':
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["new_content"], $log[6], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["new_content_single"], $log[6] );
				}
			break;
		case 'updatePage':
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["new_version"], $log[6], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["new_version_single"], $log[6] );
				}
			break;
		case 'newArticle':
		case 'backArticle':
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["new_layout"], $log[6], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["new_layout_single"], $log[6] );
				}
			break;
		case 'uploadAD':
			if( $pubCount > 1 ) {
				$txt .= sprintf( $lang["log"]["new_ad"], $log[9], $log[6], $magazine[0][2], $log[5] );
				}
			else {
				$txt .= sprintf( $lang["log"]["new_ad_single"], $log[9], $log[6] );
				}
			break;
		}
	$txt .= "</div>";
	
	$date = "";
	$sql = date( "md", $log[7] );
	$current = date("md");
	$past = date( "md", strtotime( "-1 day" ) );
	if( $current == $sql ) {
		$date = $lang["flatplan"]["today"].", ";
		}
	elseif( $past == $sql ) {
		$date = $lang["flatplan"]["yesterday"].", ";
		}
	else {
		$date = date( "m d", $log[7] ).", ";
		}
	
	$date .= date( "H:i" , $log[7] );
	$txt .= "<div><div class='logTime'>".$date."</div>&nbsp;</div>";
	return $txt;
	}

function approveMagazine( $pub_id ) {
	return true;
	
	$pub = sql_get( 'publications', 'id="'.$pub_id.'"', '*' );
	$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
	
	$fincheck = sql_get( 'pageinfo', '( type="ad" OR type="magazine" ) AND issue="'.$pub[0][10].'" AND code="'.$magazine[0][3].'" AND fin="1" LIMIT 1', 'id' );
	
	if( $fincheck[0][0] != "" ) {
    $checker = sql_get( 'pageinfo', '( type="ad" OR type="magazine" ) AND issue="'.$pub[0][10].'" AND code="'.$magazine[0][3].'" AND status < 2 AND fin="1" GROUP BY `page`', '*' );
    }
  else {
    $checker = sql_get( 'pageinfo', '( type="ad" OR type="magazine" ) AND issue="'.$pub[0][10].'" AND code="'.$magazine[0][3].'" AND status < 2 AND fin="0" GROUP BY `page`', '*' );
    }
  
	if ( empty( $checker ) ) {
		return true;
		}
	return false;
	}

function logToFile( $file, $text ) {
	$handle = fopen( "../client/temp/_log/".$file, 'a+');
	if( $handle === false ) {
		return false;
		}
	
	if( fwrite( $handle, $text . "\n" ) === false ) {
		return false;
		}
	fclose( $handle );
	}

function colorGenerate( $first, $array = array() ) {
	$return = $array;
	$f = 240;
	while( $f >= 200 ) {
		$s = 230;
		while( $s >= 150 ) {
			$t = 230;
			while( $t >= 150 ) {
				switch( $first ) {
					case 'red':
						$return[] = "C2C2C2";
						//$return[] = str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT);
						break;
					case 'green':
						$return[] = "C2C2C2";
						//$return[] = str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT);
						break;
					case 'blue':
						$return[] = "C2C2C2";
						//$return[] = str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT);
						break;
					}
				$t-=25;
				}
			$s-=25;
			}
		$f-=20;
		}
	return $return;
	}

function array_delete($idx,$array) {  
    unset($array[$idx]);  
    return (is_array($array)) ? array_values($array) : null;  
	}

function encrypt_( $text ) {
	$text = (string) $text;
	$encrypted_text = '';
	for( $i = 0; $i< strlen( $text ); $i++ ) {
		$encrypted_text .= chr( ord( $text[$i] )-1 );
		}
	return $encrypted_text;
	}

function decrypt_( $text ) {
	$text = (string) $text;
	$decrypted_text = '';
	for( $i = 0; $i< strlen( $text ); $i++ ) {
		$decrypted_text .= chr( ord( $text[$i] )+1 );
		}
	return $decrypted_text;
	}

function getPubButtons ( $status, $issue, $process, $rights ) {
	$magazine = sql_get( 'magazines', 'id="'.$issue[2].'"', 'code, type' );
	$management = array();
	switch( $status ) {
		case 'current':
			if( $rights['stopIssue'] )
				$management[] = "stop";
			if( $rights['acceptIssue'] )
				$management[] = "approve";
			break;
		case 'active':
			if( $rights['stopIssue'] )
				$management[] = "stop";
			break;
		case 'created':
			if( $rights['stopIssue'] )
				$management[] = "stop";
			break;
		case 'stopped':
			if( $rights['delIssue'] && $magazine[0][1] == "Regular" )
				$management[] = "delete";
			/*if( $rights['archiveIssue'] && ( $process == "Full" or $process == "Hybrid" ) )
				$management[] = "archive";*/
			if( $rights['acceptIssue'] )
				$management[] = "approve";
			if( $rights['stopIssue'] )
				$management[] = "restart";
			break;
		case 'approved':
			if( $rights['archiveIssue'] && ( $process == "Full" or $process == "Hybrid" ) )
				$management[] = "archive";
			if( $rights['delIssue'] && $magazine[0][1] == "Regular" )
				$management[] = "delete";
			break;
		case 'archived':
			if( $rights['archiveIssue'] && ( $process == "Full" or $process == "Hybrid" ) )
				$management[] = "archive";
			if( $rights['delIssue'] && $magazine[0][1] == "Regular" )
				$management[] = "delete";
			break;

		case 'archive_failed':
			if( $rights['delIssue'] && $magazine[0][1] == "Regular" )
				$management[] = "delete";
			if( $rights['archiveIssue'] && ( $process == "Full" or $process == "Hybrid" ) )
				$management[] = "archive";
			if( $rights['stopIssue'] )
				$management[] = "restart";
			break;
		}
	
	$temp = "";
	for( $i = 0; $i < count( $management ); $i++ ) {
		$temp .= " ".$i." : '".$management[$i]."'";
		if( $i < count($management)-1 )
			$temp .= ",";
		}
	
	return $temp;
	}

function createPubButtons ( $status, $issue ) {
	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}	
	
	$magazine = sql_get( 'magazines', 'id="'.$issue[2].'"', 'code' );
	$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
	$xpath = $xml->xpath('/Publications');
	foreach($xpath as $temp) {
		for( $x = 0; $x < count( $temp->Item ); $x++ ) {
			if( $temp->Item[$x]->Code == $magazine[0][0] ) {
				break;
				}
			}
		}
	$process = (string) $xml->Item[$x]->Workflow;
		
	$enableApprove = approveMagazine( $issue[0] );
	switch( $status ) {
		case 'current':
			if( $rights['stopIssue'] )
				$management = "<div onclick='issueManagement( \"stopIssue\", ".$issue[0]." )' class='pub_button'>Megállítás</div>";
			if( $rights['acceptIssue'] )
				if( $enableApprove )
					$management .= "<div onclick='issueManagement( \"approveIssue\", ".$issue[0]." )' class='pub_button'>Jóváhagyás</div>";
				else
					$management .= "<div onclick='alert( \"Hiba: Nem minden oldal lett jóváhagyva.\" )' class='pub_button'>Jóváhagyás</div>";
			break;
		case 'active':
			if( $rights['stopIssue'] )
				$management = 	"<div onclick='issueManagement( \"stopIssue\", ".$issue[0]." )' class='pub_button'>Megállítás</div>";
			break;
		case 'created':
			if( $rights['stopIssue'] )
				$management = 	"<div onclick='issueManagement( \"stopIssue\", ".$issue[0]." )' class='pub_button'>Megállítás</div>";
			break;
		case 'stopped':
			if( $rights['delIssue'] )
				$management =  "<div onclick='issueManagement( \"deleteIssue\", ".$issue[0]." )' class='pub_button'>Törlés</div>";
			if( $rights['archiveIssue'] && ( $process == "Full" or $process == "Hybrid" ) )
				$management .= "<div onclick='issueManagement( \"archiveIssue\", ".$issue[0]." )' class='pub_button'>Archiválás</div>";
			if( $rights['acceptIssue'] )
				if( $enableApprove )
					$management .= "<div onclick='issueManagement( \"approveIssue\", ".$issue[0]." )' class='pub_button'>Jóváhagyás</div>";
				else
					$management .= "<div onclick='alert( \"Hiba: Nem minden oldal lett jóváhagyva.\" )' class='pub_button'>Jóváhagyás</div>";
			if( $rights['stopIssue'] )
				$management .= "<div onclick='issueManagement( \"restartIssue\", ".$issue[0]." )' class='pub_button'>Újraindítás</div>";
			break;
		case 'approved':
			if( $rights['archiveIssue'] && ( $process == "Full" or $process == "Hybrid" ) )
				$management = 	"<div onclick='issueManagement( \"archiveIssue\", ".$issue[0]." )' class='pub_button'>Archiválás</div>";
			break;
		case 'archived':
			if( $rights['delIssue'] )
				$management = 	"<div onclick='issueManagement( \"deleteIssue\", ".$issue[0]." )' class='pub_button'>Törlés</div>";
			break;
		}

	return $management;
	}

function querySort ($column = 3 ) {
	return function ($a, $b) use ($column) {
		static $chr = array( 'Á'=>'A', 'É'=>'E', 'Í'=>'I', 'Ó'=>'O', 'Ö'=>'OZ', 'Ő'=>'OZ', 'Ú'=>'U', 'Ü'=>'UZ', 'Ű'=>'UZ', 'á'=>'a', 'é'=>'e', 'í'=>'i', 'ó'=>'o', 'ö'=>'oa', 'ő'=>'ob', 'ú'=>'u', 'ü'=>'ua', 'ű'=>'ub');  
		$a[$column] = strtr( $a[$column], $chr );
		$b[$column] = strtr( $b[$column], $chr );
        return strnatcmp($a[$column], $b[$column]);
    	};
	}

function delTree($dir) { 
   $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
      (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
  } 

function anotherPubs( $user, $spec_rows = '*' ) {
	$anothers = explode( ",", $user[0][10] );
	$jobs = array();
	foreach( $anothers as $another ) {
		$jobs[] = $another;
		/*
		echo 'publisher_id="'.$another.'" ORDER BY `id` ASC<br>';
		$temp = sql_get( 'publications', 'publisher_id="'.$another.'" ORDER BY `id` ASC', $spec_rows );
		var_dump( $temp );
		for( $i = 0; $i < count( $temp ); $i++ ) {
			if( intval( $user[0][4] ) != intval( $another ) ) {
				//var_dump( $temp[$i] );
				$jobs[] = $temp[$i];
				}
			}
		*/
		}
	return $jobs;	
	}

function checkFTP( $host, $port, $user, $pass ) {
	include_once('Net/SFTP.php');
	$ok = '0';
	$local = array( '192.168.1.1', '192.168.3.3' );
	
	if( in_array( $host, $local ) ) {
		$ok = 'ftp';
		}
	elseif( $log = @ftp_connect( $host, $port, 20 ) ) {
		if( @ftp_login( $log , $user, $pass ) ) {
			$ok = 'ftp';
			}
		}
	
	if( $ok == '0' ) {
		$sftp = @new Net_SFTP( $host );
		if ($sftp->login( $user, $pass )) {
			$ok = 'sftp';
			}
		}
		
	return $ok;
	}

function checkOwner( $criteria, $user = '' ) {

	if( $user == '' ) return false;
	
	$allowed = array( $user[0][4] );
	$check = sql_aget( $criteria[0], $criteria[1].'="'.$criteria[2].'"', '*' );
	$publishers = anotherPubs( $user, 'publisher_id' );
	foreach( $publishers as $publisher ) {
		$allowed[] = $publisher[0];
		}
	
	
	if( in_array( $check[0]['publisher_id'], $allowed ) ) {
		return true;
		}
	else {
		if( $user[0][4] == "6" ) {
			return true;
			}
		else {
			$mag = sql_aget( "magazines", "id='".$check[0]["magazine_id"]."'", "*" );
			if( !empty( $mag[0]["id"] ) ) {
				if( $mag[0]["type"] == "Adhoc" ) {
					$client = $mag[0]["pubName"];
					$checkc = sql_aget( "publishers", "name='".$client."'", "*" );
					
					if( $checkc[0]["id"] == $user[0][4] ) {
						return true;
						}
					else {
						return false;
						}
					}
				else {
					return false;
					}
				}
			else {
				return false;
				}
			}
		}
	}

function searchRange3( $array, $specNode = NULL ) {
	$range = '';
	
	$issue = $array["issue"];
	if( $specNode != NULL )
		$temp = explode( "_", $specNode );
	else
		$temp = explode( "_", $array["description"] );
	
	for( $i = count( $temp )-1; $i >=0; $i-- ) {
		if( ( $temp[$i] == '000' ) or ( $temp[$i] == '000-000' ) or ( strtoupper( $temp[$i] ) == 'PRE' ) ) {
			$range = $temp[$i];
			break;
			}
		elseif( strstr( $temp[$i], "-" ) ) {
			$t = explode( "-", $temp[$i] );
			if( count( $t ) == 2 ) {
				$range = intval( $t[0] )."-".intval( $t[1] );
				break;
				}
			}
		elseif( intval( $temp[$i] ) != 0 and $temp[$i] != $issue ) {
			if( $range == '' ) {
				$range = intval( $temp[$i] );
				}
			else {
				if( strstr( $range, "-" ) ) {
					if( intval( $temp[$i+1] ) != $range ) {
						$range = intval( $temp[$i] );
						}
					else {
						$range = intval( $temp[$i] )."_".intval( $temp[$i+1] );
						}
					}
				elseif( intval( $temp[$i] ) < $range and intval( $temp[$i+1] ) == $range ) {
					$range = intval( $temp[$i] )."_".$range;
					}
				else {
					$range = intval( $temp[$i] );
					}
				}
			}
		}

	return $range;
	}

function searchRange2( $xml, $specNode = NULL ) {
	$range = '';
	
	$issue = (string) $xml->issue;
	if( $specNode != NULL )
		$temp = explode( "_", $specNode );
	else
		$temp = explode( "_", (string) $xml->description );
	
	for( $i = count( $temp )-1; $i >=0; $i-- ) {
		if( ( $temp[$i] == '000' ) or ( $temp[$i] == '000-000' ) or ( strtoupper( $temp[$i] ) == 'PRE' ) ) {
			$range = $temp[$i];
			break;
			}
		elseif( strstr( $temp[$i], "-" ) ) {
			$t = explode( "-", $temp[$i] );
			if( count( $t ) == 2 ) {
				$range = intval( $t[0] )."-".intval( $t[1] );
				break;
				}
			}
		elseif( intval( $temp[$i] ) != 0 and $temp[$i] != $issue ) {
			if( $range == '' ) {
				$range = intval( $temp[$i] );
				}
			else {
				if( strstr( $range, "-" ) ) {
					if( intval( $temp[$i+1] ) != $range ) {
						$range = intval( $temp[$i] );
						}
					else {
						$range = intval( $temp[$i] )."_".intval( $temp[$i+1] );
						}
					}
				elseif( intval( $temp[$i] ) < $range and intval( $temp[$i+1] ) == $range ) {
					$range = intval( $temp[$i] )."_".$range;
					}
				else {
					$range = intval( $temp[$i] );
					}
				}
			}
		}

	return $range;
	}

function searchRange( $xml ) {
	$range = '';
	
	$issue = (string) $xml->issue;
	$temp = explode( "_", (string) $xml->description );
	
	for( $i = count( $temp )-2; $i >=0; $i-- ) {
		if( ( $temp[$i] == '000' ) or ( $temp[$i] == '000-000' ) or ( strtoupper( $temp[$i] ) == 'PRE' ) ) {
			$range = '';
			break;
			}
		elseif( strstr( $temp[$i], "-" ) ) {
			$t = explode( "-", $temp[$i] );
			if( count( $t ) == 2 ) {
				$range = intval( $t[0] )."-".intval( $t[1] );
				break;
				}
			}
		elseif( intval( $temp[$i] ) != 0 and $temp[$i] != $issue ) {
			if( $range == '' ) {
				$range = intval( $temp[$i] );
				}
			else {
				if( strstr( $range, "-" ) ) {
					if( intval( $temp[$i+1] ) != $range ) {
						$range = intval( $temp[$i] );
						}
					else {
						$range = intval( $temp[$i] )."-".intval( $temp[$i+1] );
						}
					}
				elseif( intval( $temp[$i] ) < $range and intval( $temp[$i+1] ) == $range ) {
					$range = intval( $temp[$i] )."-".$range;
					}
				else {
					$range = intval( $temp[$i] );
					}
				}
			}
		}

	return $range;
	}

function searchRange_array( $array ) {
	$range = '';
	
	$issue = (string) $array["issue"];
	$temp = explode( "_", $array["description"] );
	
	for( $i = count( $temp )-2; $i >=0; $i-- ) {
		if( ( $temp[$i] == '000' ) or ( $temp[$i] == '000-000' ) or ( strtoupper( $temp[$i] ) == 'PRE' ) ) {
			$range = '';
			break;
			}
		elseif( strstr( $temp[$i], "-" ) ) {
			$t = explode( "-", $temp[$i] );
			if( count( $t ) == 2 ) {
				$range = intval( $t[0] )."-".intval( $t[1] );
				break;
				}
			}
		elseif( intval( $temp[$i] ) != 0 and $temp[$i] != $issue ) {
			if( $range == '' ) {
				$range = intval( $temp[$i] );
				}
			else {
				if( strstr( $range, "-" ) ) {
					if( intval( $temp[$i+1] ) != $range ) {
						$range = intval( $temp[$i] );
						}
					else {
						$range = intval( $temp[$i] )."-".intval( $temp[$i+1] );
						}
					}
				elseif( intval( $temp[$i] ) < $range and intval( $temp[$i+1] ) == $range ) {
					$range = intval( $temp[$i] )."-".$range;
					}
				else {
					$range = intval( $temp[$i] );
					}
				}
			}
		}

	return $range;
	}

function nameCalculator2( $array, $process = '0' ) {
	$pmd = TRKPATH."/xml/".PMD.".xml";
	$description = str_replace( " ", "", $array["description"] );
	$remark = $array["remark"];
	$pageNum = $array["pageNum"];
	$issue = $array["issue"];
	$jobCode = $array["jobCode"];
	$client = $array["client"];
	$part = $array["part"];
	
	error_log( "PART: ".$part );
	
	switch( $process ) {
		case 0:
			$temp_name = explode( ".", $description );
			$temp_name = explode( "_", $temp_name[0] );
			//$temp_name = explode( "_", $description );
			if( count( $temp_name ) == 1 ) {
				if( !empty( $part ) ) {
					$temp_name[0] .= "_".$part;
					}
				
				return $temp_name[0];
				}
				
			if( is_numeric( $temp_name[ ( count($temp_name)-1 ) ] ) ) {
				unset( $temp_name[ ( count($temp_name)-1 ) ] );
				}
				
			$pages = searchRange3( $array, $pageNum );
			break;
		case 1:
			$temp_name = explode( ".", $remark );
			$temp_name = explode( "_", $temp_name[0] );

			if( is_numeric( $temp_name[ ( count($temp_name)-1 ) ] ) ) {
				unset( $temp_name[ ( count($temp_name)-1 ) ] );
				}

			$pages = searchRange3( $array, $remark );
			break;
		}
	$filter = array( 'FIN','Folder', 'submit', $issue, $jobCode, $issue.$jobCode, $jobCode.$issue );
	switch( $jobCode ) {
		case 'BAV':
			$filter[] = "OK";
			break;
		}
		
	if( strpos( $pages, "_" ) ) {
		$pages = explode( "_", $pages );
		$filter[] = (string) $pages[0];
		$filter[] = (string) $pages[1];
		}
	else {
		$filter[] = (string) $pages;
		}
		
	$name = array();
	$p_id = sql_get( 'publishers', 'name="'.$client.'"', '*' );
	
	$pmd = simplexml_load_file( $pmd );
	$xpath = $pmd->xpath('/Publications');
	foreach($xpath as $temp) {
		for( $i = 0; $i < count( $temp->Item ); $i++ ) {
			if( $temp->Item[$i]->Code == $jobCode )
				break;
				}
		}
		
	$custom = (string) $temp->Item[$i]->CustomCode;
	$custom = explode( "_", $custom );
	
	if( $custom[1] == '' ) unset( $custom );
	$x = 1;
	foreach( $temp_name as $t ) {
		if( $t == $jobCode ) break;
		$x++;
		}
		
	if( $x > count( $temp_name ) ) $x = 1;
	
	$checker = intval( $temp_name[count( $temp_name )-1] );
	if( str_pad( $checker, 3, '0', STR_PAD_LEFT) === $temp_name[count( $temp_name )-1] ) {
		$end = count( $temp_name )-1;
		}
	elseif( $checker !== $temp_name[count( $temp_name )-1] ) {
		$end = count( $temp_name );
		}
	else {
		$end = count( $temp_name )-1;
		}
	
	for( $i = $x; $i < $end; $i++ ) {
		if( $i == $x+1 or $i == $x+2 ) {
			//if( intval( $temp_name[$i] ) === 0 ) {
				$name[] = $temp_name[$i];
				//}
			}
		elseif( $temp_name[$i] != 'L' and $temp_name[$i] != 'R' and $temp_name[$i] != 'NaN' and $temp_name[$i] != '' ) { 
			$name[] = $temp_name[$i];
			}
		}
	$temp_name = $name;
	$temp = array();
	//var_dump( $temp_name );
	for( $i = 0; $i < count( $temp_name ); $i++ ) {
		if( !in_array( $temp_name[$i], $filter ) ) {
			$temp[] = $temp_name[$i];
			}
		}
	var_dump( $temp );
	$temp_name = $temp;
	$name = array();
	if( ( $a = array_search( 'submit', $temp_name ) ) > 0 ) {
		for( $i = 0; $i < $a; $i++ ) {
			$name[] = $temp_name[$i];
			}
		$temp_name = $name;
		}
	$version = intval( $temp_name[count($temp_name)-1] );
	if( (string) $version === $temp_name[count($temp_name)-1] ) {
		$temp_name = array_slice($temp_name,0,(count($temp_name)-1));
		}
	
	if( isset( $custom ) ) {
		$name = array();
		if( $custom[0] == 'R' ) {
			$num = intval( $custom[1] );
			$end = count($temp_name)-$num;
			for( $i = 0; $i < $end; $i++ ) {
				$name[] = $temp_name[$i];
				}
			}
		if( $custom[0] == 'L' ) {
			$num = intval( $custom[1] );
			$start = 0+$num;
			for( $i = $start; $i < count($temp_name); $i++ ) {
				$name[] = $temp_name[$i];
				}
			}
		$temp_name = $name;
		}
	$temp = array();
	for( $i = 0; $i < count( $temp_name ); $i++ ) {
		if( !in_array( $temp_name[$i], $filter ) ) {
			$temp[] = $temp_name[$i];
			}
		}
	$temp = implode( "_", $temp );
	if( strpos( $temp, " " ) !== false ) {
		$temp_name = explode( " ", $temp );
		$temp = array();
		for( $i = 0; $i < count( $temp_name ); $i++ ) {
			if( !in_array( $temp_name[$i], $filter ) )
				$temp[] = $temp_name[$i];
			}
		}
	else {
		$temp = explode( "_", $temp );
		}	
	
	$name = implode( "_", $temp );
	
	$name = str_replace( "'", "", $name );
	$name = str_replace( "&", "", $name );
	
	if( !empty( $array["part"] ) ) {
		$name .= "_".$part;
		}
	
	return $name;
	}

function nameCalculator( $xml, $pmd = '../xml/'.PMD.'.xml', $process = '0' ) {
	$description = (string) $xml->description;
	$remark = (string) $xml->remark;
	$pageNum = (string) $xml->pageNum;
	$issue = (string) $xml->issue;
	$jobCode = (string) $xml->jobCode;
	$client = (string) $xml->client;
	
	switch( $process ) {
		case 0:
			$temp_name = explode( " ", $description );
			$temp_name = explode( "_", $temp_name[0] );
			if( count( $temp_name ) == 1 ) {
				return $temp_name[0];
				}
				
			if( is_numeric( $temp_name[ ( count($temp_name)-1 ) ] ) ) {
				unset( $temp_name[ ( count($temp_name)-1 ) ] );
				}			
			$pages = searchRange2( $xml, $pageNum );
			break;
		case 1:
			$temp_name = explode( " ", $remark );
			$temp_name = explode( "_", $temp_name[0] );

			if( is_numeric( $temp_name[ ( count($temp_name)-1 ) ] ) ) {
				unset( $temp_name[ ( count($temp_name)-1 ) ] );
				}

			$pages = searchRange2( $xml, $remark );
			break;
		}
	$filter = array( 'FIN','Folder', 'submit', $issue, $jobCode, $issue.$jobCode, $jobCode.$issue );
	switch( $jobCode ) {
		case 'BAV':
			$filter[] = "OK";
			break;
		}
		
	if( strpos( $pages, "_" ) ) {
		$pages = explode( "_", $pages );
		$filter[] = (string) $pages[0];
		$filter[] = (string) $pages[1];
		}
	else {
		$filter[] = (string) $pages;
		}
		
	$name = '';
	$p_id = sql_get( 'publishers', 'name="'.$client.'"', '*' );
	
	$pmd = simplexml_load_file( $pmd );
	$xpath = $pmd->xpath('/Publications');
	foreach($xpath as $temp) {
		for( $i = 0; $i < count( $temp->Item ); $i++ ) {
			if( $temp->Item[$i]->Code == $jobCode )
				break;
				}
		}
		
	$custom = (string) $temp->Item[$i]->CustomCode;
	$custom = explode( "_", $custom );
	
	if( $custom[1] == '' ) unset( $custom );
	$x = 1;
	foreach( $temp_name as $t ) {
		if( $t == $jobCode ) break;
		$x++;
		}
		
	if( $x > count( $temp_name ) ) $x = 1;
	
	$checker = intval( $temp_name[count( $temp_name )-1] );
	if( str_pad( $checker, 3, '0', STR_PAD_LEFT) === $temp_name[count( $temp_name )-1] ) {
		$end = count( $temp_name )-1;
		}
	elseif( $checker !== $temp_name[count( $temp_name )-1] ) {
		$end = count( $temp_name );
		}
	else {
		$end = count( $temp_name )-1;
		}
	
	for( $i = $x; $i < $end; $i++ ) {
		if( $i == $x+1 or $i == $x+2 ) {
			//if( intval( $temp_name[$i] ) === 0 ) {
				$name[] = $temp_name[$i];
				//}
			}
		elseif( $temp_name[$i] != 'L' and $temp_name[$i] != 'R' and $temp_name[$i] != 'NaN' and $temp_name[$i] != '' ) { 
			$name[] = $temp_name[$i];
			}
		}
	$temp_name = $name;
	$temp = array();
	//var_dump( $temp_name );
	for( $i = 0; $i < count( $temp_name ); $i++ ) {
		if( !in_array( $temp_name[$i], $filter ) ) {
			$temp[] = $temp_name[$i];
			}
		}

	$temp_name = $temp;
	$name = array();
	if( ( $a = array_search( 'submit', $temp_name ) ) > 0 ) {
		for( $i = 0; $i < $a; $i++ ) {
			$name[] = $temp_name[$i];
			}
		$temp_name = $name;
		}
	$version = intval( $temp_name[count($temp_name)-1] );
	if( (string) $version === $temp_name[count($temp_name)-1] ) {
		$temp_name = array_slice($temp_name,0,(count($temp_name)-1));
		}
	
	if( isset( $custom ) ) {
		$name = array();
		if( $custom[0] == 'R' ) {
			$num = intval( $custom[1] );
			$end = count($temp_name)-$num;
			for( $i = 0; $i < $end; $i++ ) {
				$name[] = $temp_name[$i];
				}
			}
		if( $custom[0] == 'L' ) {
			$num = intval( $custom[1] );
			$start = 0+$num;
			for( $i = $start; $i < count($temp_name); $i++ ) {
				$name[] = $temp_name[$i];
				}
			}
		$temp_name = $name;
		}
	$temp = array();
	for( $i = 0; $i < count( $temp_name ); $i++ ) {
		if( !in_array( $temp_name[$i], $filter ) ) {
			$temp[] = $temp_name[$i];
			}
		}
	$temp = implode( "_", $temp );
	if( strpos( $temp, " " ) !== false ) {
		$temp_name = explode( " ", $temp );
		$temp = array();
		for( $i = 0; $i < count( $temp_name ); $i++ ) {
			if( !in_array( $temp_name[$i], $filter ) )
				$temp[] = $temp_name[$i];
			}
		}
	else {
		$temp = explode( "_", $temp );
		}	
	
	$name = implode( "_", $temp );
	
	$name = str_replace( "'", "", $name );
	$name = str_replace( "&", "", $name );
	return $name;
	}

function CreateJobCode( $user_id ) {
	$user = sql_get( 'accounts', 'id=\''.$user_id.'\'', '*' );
	$client = sql_get( 'publishers', 'id="'.$user[0][4].'"', '*' );
	
	$array = array();
	$check = array( 'q', 'w', 'r', 't', 'z', 'p', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'y', 'x', 'c', 'v', 'b', 'n', 'm' );
	$checker = strtolower($client[0][1]);
	
	$name = '';
	for( $i = 0; $i < strlen( $checker ); $i++ ) {
		if( in_array( $checker[$i], $check ) ) {
			$name .= $checker[$i];
			} 
		if( strlen( $name ) == 3 ) {
			break;
			}
		}
	
	$u_jobs = sql_get( 'ad_hoc', 'gen_name LIKE "'.$name.'%" ORDER BY `gen_name` DESC', '*' );	
	$u_jobs = explode( "_", $u_jobs[0][4] );
	$u_jobs = intval( $u_jobs[1] );

	$name = strtoupper($name).'_'.str_pad( ($u_jobs+1), 3, '0', STR_PAD_LEFT);
		
	return $name;
	}

function countpdfpage( $file ) {
	$pdftext = file_get_contents($file);
 	$num_pag = preg_match_all("/\/Page\W/", $pdftext,$dummy);

	return $num_pag;	
	}

function PDFtoImage_TEMP( $sizes, $from, $to, $icc, $colors = "" ) {
	error_log( "R3 PREVIEW: ");
	if( $colors != "" ) {
		$color = "";
		foreach( $colors as $key => $val ) {
			if( $val == 'true' ) {
				if( strlen( $key ) > 1 )
					$color .= $key[0];
				else 
					$color .= $key;
				}
			}
		
		$iccProfile = resolveIccProfileByName( $icc );
		error_log( './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -colors:'.$color.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' > '.$to.'' );

		$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -colors:'.$color.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' > '.$to.'';
		}
	else {
		$iccProfile = resolveIccProfileByName( $icc );
		error_log( './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' > '.$to.'' );

		$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' > '.$to.'';
		}
	
	$command = shell_exec('
			cd /var/www/html/r3API/r3
			'.$command.';
			');	

	//generateImage( $result["img"], TRKPATH."/engine/r3/".$to );
	
	return $command;
	}
	
function PDFtoImage_( $sizes, $from, $to, $colors = "" ) {
	error_log( "WARNING! PDFtoImage_" );
	$iccProfile = resolveIccProfileByName( "FOGRA_39" );
	if( $colors != "" ) {
		$color = "";
		foreach( $colors as $key => $val ) {
			if( $val == 'true' ) {
				if( strlen( $key ) > 1 )
					$color .= $key[0];
				else
					$color .= $key;
				}
			}
		$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -colors:'.$color.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' > '.TRKPATH.'/engine/r3/'.$to.'';
		}
	else {
		$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' > '.TRKPATH.'/engine/r3/'.$to.'';
		}
	$command = shell_exec('
			cd /var/www/html/r3API/r3
			'.$command.';
			');	

	//generateImage( $result["img"], TRKPATH."/engine/r3/".$to );
	
	return $command;
	}

function PDFtoImage_Measure( $sizes, $from, $to, $colors = "" ) {
	$rustart = microtime(true);
	$iccProfile = resolveIccProfileByName( "FOGRA_39" );
	if( $colors != "" ) {
		$color = "";
		foreach( $colors as $key => $val ) {
			if( $val == 'true' ) {
				if( strlen( $key ) > 1 )
					$color .= $key[0];
				else
					$color .= $key;
				}
			}
		$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -colors:'.$color.' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' > '.$to.'';
		}
	else {
		$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$iccProfile.' '.$from.' > '.$to.'';
		}
	
	echo $command."<br>";
	
	$command = shell_exec('
			cd /var/www/html/r3API/r3
			'.$command.';
			');	

	$ru = microtime(true);
	echo "Rendering time: " . rutime($ru, $rustart, "utime") ." ms ";
	
	return $command;
	}

function PdfToImageRender( $file, $temp_path, $tempFile, $colorName = "FOGRA_39" ) {
	$pdf = new dynapdf();

	include('../engine/config.inc.php');
	$pdf->CreateNewPDF( $temp_path."/".$tempFile.".pdf" );
	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);

	$pdf->InitColorManagement( NULL, NULL , 1 );

	$pdf->OpenImportFile( $file, dynapdf::ptOpen, NULL );
	$pdf->ImportPDFFile( 1, 1.0, 1.0 );
	$pdf->CloseImportfile();

	$pdf->EditPage(1);
		$tbox = $pdf->GetBBox( dynapdf::pbTrimBox );
		$newWidth = $tbox['Right'];
		$newHeight = $tbox['Top'];
		$pdf->SetBBox( dynapdf::pbCropBox, $tbox['Left'], $tbox['Bottom'], $newWidth, $newHeight );
	$pdf->EndPage();
	$pdf->SetJPEGQuality( 100 );

	// The old relative path here ("../engine/PSO_MFC_Paper_bas.icc") never
	// resolved to a real file anywhere in this app - every ICC profile
	// actually lives in r3API/r3, so that's used here too now.
	$pdf->AddOutputIntent( "/var/www/html/r3API/r3/".resolveIccProfileByName( $colorName ) );
	$pdf->RenderPageToImage(1, $temp_path."/".$tempFile.".jpg", 180, 1, 0, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfJPEG, dynapdf::ifmJPEG);
	$pdf->CloseFile();

	@unlink( $temp_path."/".$tempFile.".pdf" );
	return $temp_path."/".$tempFile.".jpg";
	}

function pdftoimage_base64( $file, $page, $settings = array() ) {
	$pdf = new dynapdf();
	include('config.inc.php');
	
	$pdf->CreateNewPDF( NULL );

	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);
	
	$first        = true;
	$destPage     = 1;
	$haveXFA      = false;
	$isCollection = false;
	$pdf->AddOutputIntent('../engine/PSO_MFC_Paper_bas.icc');
	if ($pdf->OpenImportFile( $file, dynapdf::ptOpen, NULL) < 0) die('Cannot open file!');	
		if ($first) {
			$first        = false;
			$pdf->EditPage( 1 );
				$pdf->ImportPageEx($page, 1.0, 1.0);
				$box = $pdf->GetBBox( dynapdf::pbTrimBox );
				$width = $pdf->GetPageWidth();
				$height = $pdf->GetPageHeight();
			$pdf->EndPage();
			}
	$pdf->CloseImportFile();
	
	$pdf->AddOutputIntent( "../engine/PSO_MFC_Paper_bas.icc" );	
	$pdf->RenderPageToImage(1, NULL, 0, $width, $height, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfJPEG, dynapdf::ifmJPEG);
	
	$file_name = substr( $file, 0, -4 ).'_'.$page.'.jpg';
	ob_start();
	$pdf->WriteImageBuffer();
	file_put_contents($file_name, ob_get_clean());
	
	$pdf->CloseFile();
	
	list( $w, $h ) = getimagesize( $file_name );
	$im = new ImageManipulator( $file_name );
	$im->crop( $box['Left'], $box['Bottom'], $w-$box['Left'], $h-$box['Bottom']);
	$im->save( $file_name );
	
	$data = file_get_contents($file_name);
	$base64 = 'data:image/png;base64,' . base64_encode($data);
	unlink( $file_name );


	
	return $base64;
	}

class backup {	
	function put( $to, $from, $nothing = 0 ) {
		$to2 = __DIR__;
		$to2 = $to2.'/../client/uploads/backup_line';
		$file = explode( "/", $to );
		$to = $to2.'/'.$file[count($file)-1];
		if( is_file( $from ) ) {
			copy( $from, $to );
			return $to;
			}
		else {
			return file_put_contents($to, $from);
			}
		}
	}

class backup_xml {	
	function put( $to, $from, $nothing = 0 ) {
		$to2 = __DIR__;
		$to2 = $to2.'/../client/uploads/backup_line_xml';
		$file = explode( "/", $to );
		$to = $to2.'/'.$file[count($file)-1];
		if( is_file( $from ) ) {
			copy( $from, $to );
			return $to;
			}
		else {
			return file_put_contents($to, $from);
			}
		}
	}

function ftp_conn( $prefix = "") {
	$temp = get_include_path();
	set_include_path("/var/www/html/client/engine");
	include_once( 'Net/SFTP.php');
	
	$sftp = new Net_SFTP('192.168.1.8');
	$debug = "jó";
	if (!$sftp->login('admin', 'awareness')) {
		$sftp = new backup;
		$debug = "backup line";
		}
	
	set_include_path($temp);
	return $sftp;
	}

function ftp_conn2( $prefix = "") {
	$temp = get_include_path();
	set_include_path("/var/www/html/client/engine");
	include_once( 'Net/SFTP.php');
	
	$sftp = new Net_SFTP('192.168.1.8');
	$debug = "jó";
	if (!$sftp->login('admin', 'awareness')) {
		$sftp = new backup_xml;
		$debug = "backup line";
		}
		
	set_include_path($temp);
	return $sftp;
	}

function array_push_associative(&$arr) {
    $args = func_get_args();
    array_unshift($args); // remove &$arr argument
    foreach ($args as $arg) {
        if (is_array($arg)) {
            foreach ($arg as $key => $value) {
                $arr[$key] = $value;
                $ret++;
            }
        }
    }
    
    return $ret;
}

function generate_hover_description( $event ) {
	$job = sql_get( 'jobs', 'id="'.$event[1].'"', '*' );
	$creator = sql_get( 'users', 'id="'.$job[0][1].'"', '*' );;
	
	$txt = 'Létrehozva:&nbsp;&nbsp;'.date( "Y. m. d.", strtotime( $event[5] ) ).'<br>
			Létrehozta:&nbsp;&nbsp;'.$creator[0][1].'
			';
	
	return $txt;
	}

function post_event( $job, $time ) {
	$ret = '';
	$events = sql_get( 'events', 'job_id="'.$job[0].'"' , '*' );
	
	for( $i = 0; $i < count( $events ); $i++ ) {
		$event_time = strtotime( $events[$i][3] );
		$event_time = strtotime( date( "Y-m-j", $event_time ) );
		
		if( $event_time == $time ) {
			$title = generate_hover_description( $events[$i] );
			$ret .= "<div class='calendar_event ".$events[$i][2]."' title='".$title."'>";
			
			$evnt = strtotime( $events[$i][3] );
			$ret .= date( "G:i" , $evnt);
			$ret .= "</div>";
			}
		}
	
	return $ret;
	}

function generate_job_line( $job, $row_nmbr=null, $stamp=null ) {
	$ret = '';

	$timestamp = strtotime( "-7 days" );
	$time = date( "Y-m", $timestamp );
	$days = cal_days_in_month( CAL_GREGORIAN, date( "n", $timestamp ), date( "Y", $timestamp ) );
	$remain = 21;
	$start = date( 'j' , $timestamp );
	$remain -= $month_day_left = $days-$start;
	for( $y = $start; $y <= $days; $y++ ) {
		$calendar_date = strtotime( $time."-".$y );
		$ret .=  "<td align='center' class='job_line bottom";
		if( fmod( $row_nmbr, 2 ) == 0 ) { $ret .= ' one'; }
		else { $ret .= ' two'; }
		$ret .= "'>";
			$ret .= "<div class='gradient_bg'>";
				$ret .= post_event( $job, $calendar_date );
			$ret .= "</div>";
			
		$ret .= "</td>";
		}
	if( $remain > 0 ) {
		$timestamp = strtotime( "first day of next month", $timestamp );
		$time = date( "Y-m", $timestamp );
		$days = cal_days_in_month( CAL_GREGORIAN, date( "n", $timestamp ), date( "Y", $timestamp ) );
		$start = date( 'j' , $timestamp );
		for( $y = $start; $y <= $remain; $y++ ) {
			$calendar_date = strtotime( $time."-".$y );
			$ret .= "<td align='center' class='job_line bottom";
			if( $y == $remain ) { $ret .= " right"; }
			if( fmod( $row_nmbr, 2 ) == 0 ) { $ret .= ' one'; }
			else { $ret .= ' two'; }
			$ret .= "'>";
				$ret .= "<div class='gradient_bg'>";
					$ret .= post_event( $job, $calendar_date );
				$ret .= "</div>";
			$ret .= "</td>";						
			}
		}	
	
	return $ret;
	}

function generate_tline_header( $stamp=null ) {
	global $lang; 
	
	$stamp = ($stamp==null) ? time() : $stamp;
	$ret = '';

	$timestamp = strtotime( "-7 days", $stamp );
	$time = date( "Y-m", $timestamp );
	$days = cal_days_in_month( CAL_GREGORIAN, date( "n", $timestamp ), date( "Y", $timestamp ) );
	$remain = 21;
	$start = date( 'j' , $timestamp );
	$remain -= $month_day_left = $days-$start;
	for( $i = $start; $i <= $days; $i++ ) {
		$is_week = date( "l", strtotime( $time.'-'.sprintf("%02d", $i) ) );
		$weeks = array( 'Saturday', 'Sunday' );
		$ret .= "<td align='center' class='bottom2 ";
		if( in_array( $is_week, $weeks ) ) {
			$ret .= " red";
			}
		if( $i == $days ) { $ret .= " right"; }
		$ret .= "'>";
		if( $i == date( "j" ) ) $ret .= "<span style='color: #4B51AD !important;'>".$lang['timeline']['today']."</span>";
		else $ret .= "".$i."";
		$ret .= "</td>";
		}
	if( $remain > 0 ) {
		$timestamp = strtotime( "first day of next month", $timestamp );
		$time = date( "Y-m", $timestamp );
		$days = cal_days_in_month( CAL_GREGORIAN, date( "n", $timestamp ), date( "Y", $timestamp ) );
		$start = date( 'j' , $timestamp );
		for( $i = $start; $i <= $remain; $i++ ) {
			$is_week = date( "l", strtotime( $time.'-'.sprintf("%02d", $i) ) );
			$weeks = array( 'Saturday', 'Sunday' );
			$ret .= "<td align='center' class='bottom2";
			if( in_array( $is_week, $weeks ) ) {
				$ret .= " red";
				}
			if( $i == $remain ) { $ret .= " right"; }
			
			$ret .= "'>";
			if( $i == date( "j" ) ) $ret .= "<span style='color: #4B51AD !important;'>".$lang['timeline']['today']."</span>";
			else $ret .= "".$i."";
			$ret .= "</td>";
			}
		}
	
	return $ret;	
	}

function get_counter($path) {
	$file=fopen($path."/message.counter","r+");
	$temp = fgets($file);
	fclose($file);
	
	return $temp;
	}
	
function inc_counter($path) {
	$file=fopen($path."/message.counter","r+");
	$temp = intval( fgets($file) );
	fclose($file);
	
	$temp++;
	if( $temp > 9999 )
		$temp = 1;
	$temp = sprintf("%04d", $temp);
	
	file_put_contents( $path."/message.counter" , $temp );
	}

function load_dirs( $path ) {
	$files = array();
	$handle = @opendir( $path );
	$blacklist = array( '.', '..' );
		
	while ( false !== ( $file = @readdir( $handle ) ) ) {
		if ( !in_array( $file, $blacklist ) ) {
			if( is_dir( $path.'/'.$file ) ) $files[] = $file;
			}
		}
	
	@closedir( $handle );
	return $files;
	}


function load_dir_files_one( $path, $name ) {
	$files = array();
	
	$blacklist = array( '.', '..' );
	
	if( $handle = @opendir( $path ) ) {
		while ( false !== ( $file = @readdir( $handle ) ) ) {
			if ( !in_array( $file, $blacklist ) ) {
				if( $name != '' ) {
					if( strstr( $file, $name ) ) $files[] = $file;
					}
				else {
					$files[] = $file;
					}
				break;
				}
			}
		}
	
	@closedir( $handle );
	return $files;
	}
	
function load_dir_files( $path, $name ) {
	$files = array();
	
	$blacklist = array( '.', '..' );
	
	if( $handle = @opendir( $path ) ) {
		while ( false !== ( $file = @readdir( $handle ) ) ) {
			if ( !in_array( $file, $blacklist ) ) {
				if( $name != '' ) {
					if( strstr( $file, $name ) ) $files[] = $file;
					}
				else {
					$files[] = $file;
					}
				}
			}
		}
	
	@closedir( $handle );
	return $files;
	}

function message( $text, $type_class ) {
		$temp = "<div class='msg_box ".$type_class."'>";
		$temp .= $text."</div>";
		
		return $temp;
		}

function sql_delete( $table, $where, $db = "" ) {
		global $con;
		
		$db = ( $db != "" ) ? $db : $con;
		
		$command = "DELETE FROM ".$table." WHERE ".$where."";
		
		mysqli_query( $db, $command );
		return $command;
		}
		
function sql_update( $table, $condition, $where, $db = "" ) {
		global $con;
		
		$db = ( $db != "" ) ? $db : $con;
		
		$command = "UPDATE ".$table." SET ".$condition." WHERE ".$where."";
		//error_log( $command );
		
		if ( mysqli_query( $db, $command ) ) {
			return true;
			}
		else {
			return false;
			}
			
		}

function sql_rowcount( $table, $condition, $collect, $db = "" ) {
		global $con;
		
		$db = ( $db != "" ) ? $db : $con;
		
		$datas = array();
		$command = 'SELECT '.$collect.' FROM '.$table.'';
		
		if( $condition != '' )
			$command .= ' WHERE '.$condition.'';
			
		$command = mysqli_query( $db, $command );
		return $command ? mysqli_num_rows($command) : 0;
		}

function sql_aget( $table, $condition, $collect, $db = "" ) {
		global $con;
		
		$db = ( $db != "" ) ? $db : $con;
		
		$datas = array();
		$command = 'SELECT '.$collect.' FROM '.$table.'';
		
		if( $condition != '' )
			$command .= ' WHERE '.$condition.'';
		
		$command = mysqli_query( $con, $command );
		if ( $command ) {
			while( $row = mysqli_fetch_assoc( $command ) ) {
				//var_dump( $row );
				$datas[] = $row;
				}
			}

		return $datas;
		}
		
function sql_get( $table, $condition, $collect, $db = "" ) {
		global $con;

		$db = ( $db != "" ) ? $db : $con;
		
		$datas = array();
		$command = 'SELECT '.$collect.' FROM '.$table.'';
		
		if( $condition != '' )
			$command .= ' WHERE '.$condition.'';
		
		//echo $command."<br>";
			
		$command = mysqli_query( $db, $command );

		if ( $command ) {
			while( $row = @mysqli_fetch_row( $command ) ) {
				$datas[] = $row;
				}
			}

		return $datas;
		}

function sql_nextid( $db, $table, $sql_db = "" ) {
	global $con;
	
	$sql_db = ( $sql_db != "" ) ? $sql_db : $con;
	
	$result = mysqli_query( $sql_db, "SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = '".$db."' AND TABLE_NAME = '".$table."'" );
	$id = $result ? mysqli_fetch_row( $result ) : null;

	return $id[0];
	}
	
function sql_add( $table, $names, $datas, $db = "" ) {
		global $con;
		
		$db = ( $db != "" ) ? $db : $con;
		
		$command = 'INSERT INTO '.$table.' (';
		$command2 = '(';
		
		for( $i = 0; $i < count( $names ); $i++ ) {
			if( $i < count( $names )-1 ) {
				$command .= '`'.$names[ $i ].'`,';
				$command2 .= ' \''.$datas[ $i ].'\',';  
				}
			else {
				$command .= '`'.$names[ $i ].'`) VALUES';
				$command2 .= '\''.$datas[ $i ].'\'';  
				}
			}
		
		$command .= $command2 .');';
		
		error_log( $command );
			
		if ( mysqli_query($db, $command) ) {
			return mysqli_insert_id( $db );
			}
		else {
			return false;
			}
		}

function letter_change2( $text ) {
		$a = explode(",","á,é,í,ö,ő,ó,ü,ű,ú,Á,É,Í,Ö,Ő,Ü,Ű,Ó,Ú,',/");
		$b = explode(",","a,e,i,o,o,o,u,u,u,A,E,I,O,O,U,U,O,U,-,-");
		
		$temp = str_replace($a, $b, trim($text) );
		$temp = str_replace( ' ', '_', $temp);
		
		return $temp;
		}

function letter_change( $text ) {
		$a = explode(",","á,é,í,ö,ő,ó,ü,ű,ú,Á,É,Í,Ö,Ő,Ü,Ű,Ó,Ú,(,),\',%22,+,-,*,|,@,%,„,”,`,',/");
		$b = explode(",","a,e,i,o,o,o,u,u,u,A,E,I,O,O,U,U,O,U,-,-,-,-,-,-,-,-,-,-,-,-,-,-,-");
		
		$temp = str_replace($a, $b, trim($text) );
		$temp = str_replace( ' ', '_', $temp);
		
		return $temp;
		}

function letter_change3( $text ) {
		$a = explode(",","\',%22,*,|,%,„,”,`,',/");
		$b = explode(",","-,-,-,-,-,-,-,-,-,-");
		
		$temp = str_replace($a, $b, trim($text) );
		$temp = str_replace( ' ', '_', $temp);
		
		return $temp;
		}

function letter_change_fileupload( $text ) {
		$a = explode(",","\',%22,*,|,%,„,”,`,',/");
		$b = explode(",","-,-,-,-,-,-,-,-,-,-");
		
		$temp = str_replace($a, $b, trim($text) );
		//$temp = str_replace( ' ', '_', $temp);
		
		return $temp;
		}

 $szamok = array();  
 $szamok['0'] = 'nulla';  
 $szamok['1'] = 'egy';  
 $szamok['2'] = 'kettő';  
 $szamok['3'] = 'három';  
 $szamok['4'] = 'négy';  
 $szamok['5'] = 'öt';  
 $szamok['6'] = 'hat';  
 $szamok['7'] = 'hét';  
 $szamok['8'] = 'nyolc';  
 $szamok['9'] = 'kilenc';  
 $szamok['10'] = 'tíz';  
 $szamok['20'] = 'húsz';  
 $szamok['30'] = 'harminc';  
 $szamok['40'] = 'negyven';  
 $szamok['50'] = 'ötven';  
 $szamok['60'] = 'hatvan';  
 $szamok['70'] = 'hetven';  
 $szamok['80'] = 'nyolcvan';  
 $szamok['90'] = 'kilencven';  
 $szamok['100'] = 'száz';  
 $szamok['1000'] = 'ezer';  
 $szamok['1000000'] = 'millió';  
 $szamok['1000000000'] = 'milliárd';  
 $szamok['1000000000000'] = 'trillió';  
 $szamok['1000000000000000'] = 'quadrillió';  
 $szamok['1000000000000000000'] = 'quintillió';  
 $szamok['1000000000000000000000'] = 'sextrillió';  
 $szamok['1000000000000000000000000'] = 'septrillió';  
 $szamok['1000000000000000000000000000'] = 'oktrillió';  

 function szam_1($szam, $darabszam = 0){  
  global $szamok;  
  
  if(!$szam)  
   return($szamok['0']);  
  if(($szam == '2') && ($darabszam & 1))  
   return('két');  
  return($szamok[$szam]);  
 }  
  
 function szam_2($szam, $darabszam = 0){  
  global $szamok;  
  
  if(!$szam[1])  
   return($szamok[$szam[0].'0']);  
  
  switch($szam[0]){  
   case '1': $return = 'tizen'; break;  
   case '2': $return = 'huszon'; break;  
   default: $return = $szamok[$szam[0].'0']; break;  
  }  
  
  if($szam[1])  
   $return .= szam_1($szam[1], $darabszam);  
  return($return);  
 }  
  
 function szam_3($szam, $darabszam = 0){  
  global $szamok;  
  
  if(($szam[0] != '1') || ($darabszam & 2))  
   $return = szam($szam[0], 1);  
  @$return .= $szamok['100'];  
  if($szam = intval($szam[1].$szam[2]))  
   $return .= szam(strval($szam), $darabszam);  
  return($return);  
 }  
  
 function szam_4($szam, $darabszam = 0){  
  global $szamok;  
  
  if((intval($szam) > 1999) || ($darabszam & 4))  
   $return = szam(intval(substr($szam, -strlen($szam), strlen($szam) - 3)), 1);  
  @$return .= $szamok['1000'];  
  if($tmp = intval(substr($szam, -3, 3))){  
   if(intval($szam) > 1999)  
    $return .= ' - ';  
   $return .= szam(strval($tmp), $darabszam);  
  }  
  return($return);  
 }  
  
 function szam_5($szam, $darabszam = 0){return szam_4($szam, $darabszam);}  
 function szam_6($szam, $darabszam = 0){return szam_4($szam, $darabszam);}  

 function _szam($szam, $darabszam = 0){  
  global $szamok;  
  
  $strlen = intval(strlen($szam) / 3) * 3;  
  if($strlen == strlen($szam))  
   $strlen -= 3;  
  if(!($darabszam & 8))  
   $return = szam(intval(substr($szam, 0, strlen($szam) - $strlen)), 1);  
  if(($darabszam & 16) && preg_match('/^[0-9]0+$/', $szam))  
   @$return .= ' ';  
  @$return .= $szamok['1'.str_repeat('0', $strlen)];  
  if(($tmp = szam(substr($szam, -$strlen), $darabszam)) && ($tmp != $szamok['0']))  
   $return .= ' - '.$tmp;  
  return($return);  
 } 

 function szam($szam, $darabszam = 0){  
  if(!strlen($szam = preg_replace('/^0+0/', '0', $szam)))  
   return(null);  
  if($szam[0] == '-')  
   return('mínusz '.szam(substr($szam, 1), $darabszam));  
  if(count($tmp = preg_split('/\./', $szam, 2)) - 1){  
   global $szamok, $tortek;  
  
   $tort = @$tortek[strlen($tmp[1] = preg_replace('/0+$/', '', $tmp[1])) - 1];  
   if(($tmp[1] = szam(intval($tmp[1]))) && ($tmp[1] != $szamok['0']))  
    return szam($tmp[0]).' egész '.$tmp[1].' '.$tort;  
   $szam = $tmp[0];  
  }  
  $szam = preg_replace('/^0+0/', '0', $szam);  
  if(($tmp = strlen($szam)) < 7)  
   return(call_user_func('szam_'.$tmp, $szam, $darabszam));  
  return(_szam($szam,$darabszam));  
 } 
		
class ImageManipulator
{
    /**
     * @var int
     */
    protected $width;
 
    /**
     * @var int
     */
    protected $height;
 
    /**
     * @var resource
     */
    protected $image;
 
    /**
     * Image manipulator constructor
     * 
     * @param string $file OPTIONAL Path to image file or image data as string
     * @return void
     */
    public function __construct($file = null)
    {
        if (null !== $file) {
            if (is_file($file)) {
                $this->setImageFile($file);
            } else {
                $this->setImageString($file);
            }
        }
    }
 
    /**
     * Set image resource from file
     * 
     * @param string $file Path to image file
     * @return ImageManipulator for a fluent interface
     * @throws InvalidArgumentException
     */
    public function setImageFile($file)
    {
        if (!(is_readable($file) && is_file($file))) {
            throw new InvalidArgumentException("Image file $file is not readable");
        }
 
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
 
        list ($this->width, $this->height, $type) = getimagesize($file);
 
        switch ($type) {
            case IMAGETYPE_GIF  :
                $this->image = imagecreatefromgif($file);
                break;
            case IMAGETYPE_JPEG :
                $this->image = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG  :
                $this->image = imagecreatefrompng($file);
                break;
            default             :
                throw new InvalidArgumentException("Image type $type not supported");
        }
 
        return $this;
    }
    
    /**
     * Set image resource from string data
     * 
     * @param string $data
     * @return ImageManipulator for a fluent interface
     * @throws RuntimeException
     */
    public function setImageString($data)
    {
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
 
        if (!$this->image = imagecreatefromstring($data)) {
            throw new RuntimeException('Cannot create image from data string');
        }
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
        return $this;
    }
 
    /**
     * Resamples the current image
     *
     * @param int  $width                New width
     * @param int  $height               New height
     * @param bool $constrainProportions Constrain current image proportions when resizing
     * @return ImageManipulator for a fluent interface
     * @throws RuntimeException
     */
    public function resample($width, $height, $constrainProportions = true)
    {
        if (!is_resource($this->image)) {
            throw new RuntimeException('No image set');
        }
        if ($constrainProportions) {
            if ($this->height >= $this->width) {
                $width  = round($height / $this->height * $this->width);
            } else {
                $height = round($width / $this->width * $this->height);
            }
        }
        $temp = imagecreatetruecolor($width, $height);
        imagecopyresampled($temp, $this->image, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
        return $this->_replace($temp);
    }
    
    /**
     * Enlarge canvas
     * 
     * @param int   $width  Canvas width
     * @param int   $height Canvas height
     * @param array $rgb    RGB colour values
     * @param int   $xpos   X-Position of image in new canvas, null for centre
     * @param int   $ypos   Y-Position of image in new canvas, null for centre
     * @return ImageManipulator for a fluent interface
     * @throws RuntimeException
     */
    public function enlargeCanvas($width, $height, array $rgb = array(), $xpos = null, $ypos = null)
    {
        if (!is_resource($this->image)) {
            throw new RuntimeException('No image set');
        }
        
        $width = max($width, $this->width);
        $height = max($height, $this->height);
        
        $temp = imagecreatetruecolor($width, $height);
        if (count($rgb) == 3) {
            $bg = imagecolorallocate($temp, $rgb[0], $rgb[1], $rgb[2]);
            imagefill($temp, 0, 0, $bg);
        }
        
        if (null === $xpos) {
            $xpos = round(($width - $this->width) / 2);
        }
        if (null === $ypos) {
            $ypos = round(($height - $this->height) / 2);
        }
        
        imagecopy($temp, $this->image, (int) $xpos, (int) $ypos, 0, 0, $this->width, $this->height);
        return $this->_replace($temp);
    }
    
    /**
     * Crop image
     * 
     * @param int|array $x1 Top left x-coordinate of crop box or array of coordinates
     * @param int       $y1 Top left y-coordinate of crop box
     * @param int       $x2 Bottom right x-coordinate of crop box
     * @param int       $y2 Bottom right y-coordinate of crop box
     * @return ImageManipulator for a fluent interface
     * @throws RuntimeException
     */
    public function crop($x1, $y1 = 0, $x2 = 0, $y2 = 0)
    {
        if (!is_resource($this->image)) {
            throw new RuntimeException('No image set');
        }
        if (is_array($x1) && 4 == count($x1)) {
            list($x1, $y1, $x2, $y2) = $x1;
        }
        
        $x1 = max($x1, 0);
        $y1 = max($y1, 0);
        
        $x2 = min($x2, $this->width);
        $y2 = min($y2, $this->height);
        
        $width = $x2 - $x1;
        $height = $y2 - $y1;
        
        $temp = imagecreatetruecolor($width, $height);
        imagecopy($temp, $this->image, 0, 0, $x1, $y1, $width, $height);
        
        return $this->_replace($temp);
    }
    
    /**
     * Replace current image resource with a new one
     * 
     * @param resource $res New image resource
     * @return ImageManipulator for a fluent interface
     * @throws UnexpectedValueException
     */
    protected function _replace($res)
    {
        if (!is_resource($res)) {
            throw new UnexpectedValueException('Invalid resource');
        }
        if (is_resource($this->image)) {
            imagedestroy($this->image);
        }
        $this->image = $res;
        $this->width = imagesx($res);
        $this->height = imagesy($res);
        return $this;
    }
    
    /**
     * Save current image to file
     * 
     * @param string $fileName
     * @return void
     * @throws RuntimeException
     */
    public function save($fileName, $type = IMAGETYPE_JPEG)
    {
        $dir = dirname($fileName);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new RuntimeException('Error creating directory ' . $dir);
            }
        }
        
        try {
            switch ($type) {
                case IMAGETYPE_GIF  :
                    if (!imagegif($this->image, $fileName)) {
                        throw new RuntimeException;
                    }
                    break;
                case IMAGETYPE_PNG  :
                    if (!imagepng($this->image, $fileName)) {
                        throw new RuntimeException;
                    }
                    break;
                case IMAGETYPE_JPEG :
                default             :
                    if (!imagejpeg($this->image, $fileName, 95)) {
                        throw new RuntimeException;
                    }
            }
        } catch (Exception $ex) {
            throw new RuntimeException('Error saving image file to ' . $fileName);
        }
    }
 
    /**
     * Returns the GD image resource
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->image;
    }
 
    /**
     * Get current image resource width
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }
 
    /**
     * Get current image height
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }
}

include("/var/www/html/engine/switchconstant.php");

?>