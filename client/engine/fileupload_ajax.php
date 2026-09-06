<?php
session_start();
include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php');
include_once( TRKPATH."/engine/switchAPI.php" );

$_FILES["file"]['name'] = letter_change_fileupload( $_FILES["file"]['name'] );

// tempdir is client-supplied (Date.now()) - strip to digits-only so it can't
// be used for path traversal.
$tempdir = preg_replace( '/[^0-9]/', '', $_POST["tempdir"] );

$target_chunk_path = TRKPATH.'/uploads/blob/chunk/'.$tempdir.'/';
$toswitch_path = 'uploads/blob/chunk/'.$tempdir;
$target_path = TRKPATH.'/uploads/blob/';

// This file had no authentication check at all (see
// client/plugins/pubsApply.php's 2026-09-05 fix) - an upload's ownership
// is deliberately allowed to keep going even if the session times out
// mid-upload (see the .owner comment below), so this can't just require a
// valid session on every chunk without breaking that. Only the *first*
// chunk of a genuinely new upload (no .owner file recorded yet) needs one -
// once ownership is established, later chunks are trusted the same way
// they always were.
$owner_file_check = TRKPATH.'/uploads/blob/chunk/'.$tempdir.'/.owner';
if( !empty( $tempdir ) && !file_exists( $owner_file_check ) && empty( $_SESSION['intra_user'] ) ) {
	print json_encode( array( array( "Unauthorized" ) ) );
	exit;
	}

if( !is_dir( TRKPATH.'/uploads/blob/chunk' ) ) {
	$oldmask = umask(0);
	mkdir( TRKPATH.'/uploads/blob/chunk', 0777);
	umask($oldmask);
	}

if( !is_dir( TRKPATH.'/uploads/blob/chunk/'.$tempdir ) ) {
	$oldmask = umask(0);
	mkdir( TRKPATH.'/uploads/blob/chunk/'.$tempdir, 0777);
	umask($oldmask);
	}

// Record which account owns this chunk folder, written once to disk rather
// than kept in the PHP session, so ownership survives logout/relogin or a
// session timeout mid-upload and cancelupload (ajax.php) can still verify it
// later against whichever account is asking.
$owner_file = TRKPATH.'/uploads/blob/chunk/'.$tempdir.'/.owner';
if( !empty( $tempdir ) && !file_exists( $owner_file ) && !empty( $_SESSION['intra_user'] ) ) {
	file_put_contents( $owner_file, $_SESSION['intra_user'] );
	}

$chunk_prefix = "chunk_";
$tmp_name = $_FILES['file']['tmp_name'];
$filename = $_FILES['file']['name'];

$target_chunk_file = $target_chunk_path.$chunk_prefix.$filename;
$target_chunk_merge = $target_chunk_path.$filename;
$target_file = $target_path.$filename;

$num = $_POST['num'];
$num_chunks = $_POST['num_chunks'];
$result = "";

error_log( $tmp_name." => ".$target_chunk_file.$num );

if( move_uploaded_file($tmp_name, $target_chunk_file.$num) ) {
	$file = fopen($target_chunk_file.$num, 'rb');
	$buff = fread($file, filesize($target_chunk_file.$num) );
	fclose($file);

	$final = fopen($target_chunk_merge, 'ab');
	$write = fwrite($final, $buff);
	fclose($final);
	unlink( $target_chunk_file.$num );
	
	error_log( $num." == ".$num_chunks );

	if ($num !== $num_chunks) {
		// Not the last chunk yet - nothing else to do. This used to return
		// an empty body; the chunked-upload callers now all parse the
		// response as JSON on every chunk (not just the last), so an empty
		// body here made JSON.parse() throw and every multi-chunk upload
		// report failure after chunk 1 (confirmed live 2026-09-04: a 350MB
		// drag-drop died here every time despite chunk 1 actually writing
		// to disk fine).
		print json_encode( array( "ok" => true ) );
		}
	elseif ($num === $num_chunks) {
		$user = sql_aget( "accounts", "id='".$_SESSION['intra_user']."'", "*" );
		$names = array( "filename", "publisher", "jobname", "username", "email", "time", "type", "jtype", "userid" );
		$values = array( $_FILES["file"]['name'], $user[0]["publisher"], $_POST["jobid"], $user[0]["full_name"], $user[0]["email"], time(), "upload", $_POST["jtype"], $user[0]["id"] );
		sql_add( "filetransfer_log", $names, $values );
		
		if( $_POST["jobid"] == "off" ) {
			$code = "off";
			$pub[0]["code"] = "off";
			}
		else {
			if( $_POST["jtype"] == "pub" ) {
				$pub = sql_aget( "publications", "id='".$_POST["jobid"]."'", "*" );
				$mag = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
				
				$code = $mag[0]["code"];
				}
			}
		//$part = "";
		
		if( $_POST["type"] == "picture_pack" ) {
			error_log( "PICTURE PACK DEBUG" );
			$pub = sql_aget( "publications", "id='".$_POST["jobid"]."'", "*" );
			$name = $_POST["imgpack"];
			$check = sql_aget( "assets", "name='".$name.".indd'", "*" );
			
			if( empty( $check[0]["id"] ) ) {
				$names = array( "pub_id", "parent", "name", "type", "time", "stripped_name", "origname", "hide" );
				$values = array( $pub[0]["id"], "0", $name.".indd", "web_pack", time(), $name, $name, "" );
				$id = sql_add( "assets", $names, $values );

				if( !is_dir( TRKPATH."/assets/".$pub[0]["id"] ) ) {
					$oldmask = umask(0);
					mkdir( TRKPATH."/assets/".$pub[0]["id"], 0777 );
					umask($oldmask);		
					}
					
				$oldmask = umask(0);
				mkdir( TRKPATH."/assets/".$pub[0]["id"]."/".$id, 0777 );
				umask($oldmask);	
				}
			else {
				$id = $check[0]["id"];
				}
				
			$path = TRKPATH."/".$toswitch_path."/".$_FILES["file"]['name'];
			$to = TRKPATH."/assets/".$pub[0]["id"]."/".$id."/".$_FILES["file"]['name'];
			
			error_log( "from: ".$path );
			error_log( "to: ".$to );
			
			if( copy( $path, $to ) ) {
				$name = explode( ".", $_FILES["file"]['name'] );
				array_pop($name);
				$name = implode( ".", $name );
				
				$names = array( "pub_id", "parent", "name", "type", "time", "stripped_name", "origname", "hide" );
				$values = array( $pub[0]["id"], $id, $_FILES["file"]['name'], mime_content_type( $to ), time(), $name, $name, "" );
				sql_add( "assets", $names, $values );
				
				list($width, $height) = getimagesize( $to );
				$a = $width;
				$b = $height;
				if( $a > $b ) {
					$a = $height;
					$b = $width;
					}
				
				$arany = 150 / $a;
				$width = $width * $arany;
				$height = $height * $arany;
				
				$thumb_name = $_FILES["file"]['name'];
				$thumb_name = explode( ".", $thumb_name );
				array_pop($thumb_name);
				$thumb_name = implode( ".", $thumb_name );
				$thumb_name = TRKPATH."/assets/".$pub[0]["id"]."/".$id."/".$thumb_name."_pre.jpg";
				
				error_log( "DEBUG ".$thumb_name );
				error_log( $width." ; ".$height );
				
				$thumb = new Imagick( $to );
				$thumb->resizeImage( $width, $height, Imagick::FILTER_LANCZOS, 1 );
				$thumb->writeImage( $thumb_name );
				}
			
			error_log( "FILETRANSFER DEBUG" );
			error_log( $_POST["current"]." == ".$_POST["max"] );
			if( $_POST["current"] == ( $_POST["max"] ) ) {
				include( "../../engine/fileClass.php" );
				
				error_log( 'id="'.$pub[0]['magazine_id'].'"' );
				$mag = sql_aget( 'magazines', 'id="'.$pub[0]['magazine_id'].'"', '*' );
				$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $x = 0; $x < count( $temp->Item ); $x++ ) {
						if( $temp->Item[$x]->Code == $mag[0]["code"] ) {
							break;
							}
						}
					}
					
				$mails = gatedMailRecipients( $mag[0]["id"], $mag[0]["type"], (string) $xml->Item[$x]->Mails );
				error_log( print_r( $mails, true ) );
				
				for( $i = 0; $i < count( $mails ); $i++ ) {
					$hash = md5( "adhocuserdownload-".time()."-".$mails[$i] );
					$user = sql_aget( "accounts", "email='".$mails[$i]."' AND showMagazines like '%".$mag[0]["id"]."%' LIMIT 1", "*" );
					if( !empty( $user[0]["id"] ) ) {
						$names = array( "user_id", "hash", "magazine_id", "email", "time", "redirecto", "pubid" );
						$values = array( $user[0]["id"], $hash, $pub[0]["magazine_id"], $mails[$i], time(), "page=assets", $pub[0]["id"] );
						sql_add( "adhoc_hotlinks", $names, $values );
						
						$to = $mails[$i]."|".$mails[$i];
						$link = "https://".URL."/index.php?hash=".$hash;
						$subject = "".$mag[0]["name"]." - Colorcom Tracker feltöltés";
						$body = "Kedves ".$mails[$i].",<br>
						<br>
						a(z) ".$mag[0]["name"]." kiadványhoz új fájlok lettek feltöltve amit a következő linkre kattintva tekinthet meg: <a href='".$link."'>".$link."</a>.<br>
						<br>
						Üdvözlettel:<br>
						Colorcom Media";
						produkcioSendmail( $subject, $body, $to );
						error_log( "Mail sent: ".$mails[$i] );
						}					
					}
				}
			}
		else {
			// MailComm used to be hardcoded "Yes" regardless of the
			// magazine's own MailComm setting or the uploader's personal
			// opt-out (accounts.mailOptOut) - the two gates every other
			// mail-sending path in the app already honors (see
			// gatedMailRecipients() in engine.php). Deliberately NOT
			// checking Gate A (PMD Mails-list membership / the admin's "M"
			// checkbox) here - that list is for broadcasting job events to
			// subscribed staff, not for telling the uploader about
			// problems with their own submission, and most uploaders
			// aren't on it, so applying it here would silently kill the
			// notification for exactly the person who needs it.
			$mailComm = "No";
			if( !empty( $mag[0]["id"] ) && !empty( $mag[0]["code"] ) ) {
				$pmdxml = simplexml_load_file( '../xml/'.PMD.'.xml' );
				$pmdxpath = $pmdxml->xpath('/Publications');
				foreach( $pmdxpath as $pmdtemp ) {
					for( $mc = 0; $mc < count( $pmdtemp->Item ); $mc++ ) {
						if( $pmdtemp->Item[$mc]->Code == $mag[0]["code"] ) {
							break;
							}
						}
					}
				if( (string) $pmdxml->Item[$mc]->MailComm === "Yes" ) {
					$optOut = array_filter( explode( ',', $user[0]["mailOptOut"] ?? '' ) );
					if( !in_array( (string) $mag[0]["id"], $optOut, true ) ) {
						$mailComm = "Yes";
						}
					}
				}

			$data = array(
				"Code" => $code,
				"User" => $user[0]["full_name"],
				"Mail" => $user[0]["email"],
				"MailComm" => $mailComm,
				"Part" => $_POST["part"],
				"Type" => $_POST["type"],
				"Issue" => $pub[0]["code"],
				"file_path" => $toswitch_path,
				"file_name" => $_FILES["file"]['name']
				);

			$headers = array(
				"Content-Type: multipart/form-data",
				);
			
			// Target 127.0.0.1 directly rather than "http://".URL - this is a
			// same-box self-call (fire-and-forget via systemCurl(), not a real
			// external request), and routing it through the public hostname
			// makes it depend entirely on how that hostname happens to resolve
			// (DNS, /etc/hosts) - if it resolves to this box's own LAN address
			// (as it correctly does now, post-cutover) the request arrives with
			// REMOTE_ADDR=<this box's own IP>, which async_send.php's inbound-
			// Switch-webhook IP check (correctly) doesn't recognize, so the
			// actual Switch send silently never happens. Confirmed live
			// 2026-09-06: nginx access log showed this exact call reaching
			// async_send.php from 10.10.30.60 and getting a 403. 127.0.0.1 is
			// unambiguous regardless of what URL resolves to, and matches the
			// second address now allowlisted in async_send.php's own check.
			$url = "http://127.0.0.1/client/engine/switch/async_send.php";
			systemCurl( $url, $data, $headers=null, $check_ssl=true);
			}
		
		if( !empty( $code ) && !empty( $pub[0]["code"] ) ) {
			sql_update( 'accounts', 'actual="'.$code.'_'.$pub[0]["code"].'"', 'id="'.$_SESSION['intra_user'].'"' );
			}

		print json_encode( array( "ok" => true, "result" => $result ) );
		}
	}
else {
	// move_uploaded_file() failed - exceeded a size limit, the connection
	// dropped mid-upload, disk full, etc. Previously this fell through
	// silently: no response body, no status code, and the chunked-upload
	// callers had nothing to react to anyway. Report it so the caller can
	// surface an error instead of the upload just vanishing.
	http_response_code( 500 );
	print json_encode( array( "ok" => false, "error" => "upload failed" ) );
	}
?>