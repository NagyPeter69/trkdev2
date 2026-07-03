<?php
session_start();
include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php');
include_once( TRKPATH."/engine/switchAPI.php" );

$_FILES["file"]['name'] = letter_change_fileupload( $_FILES["file"]['name'] );

$target_chunk_path = TRKPATH.'/uploads/blob/chunk/'.$_POST["tempdir"].'/';
$toswitch_path = 'uploads/blob/chunk/'.$_POST["tempdir"];
$target_path = TRKPATH.'/uploads/blob/';

if( !is_dir( TRKPATH.'/uploads/blob/chunk' ) ) {
	$oldmask = umask(0);
	mkdir( TRKPATH.'/uploads/blob/chunk', 0777);
	umask($oldmask);
	}

if( !is_dir( TRKPATH.'/uploads/blob/chunk/'.$_POST["tempdir"] ) ) {
	$oldmask = umask(0);
	mkdir( TRKPATH.'/uploads/blob/chunk/'.$_POST["tempdir"], 0777);
	umask($oldmask);
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
	//unlink( $target_chunk_file.$num );
	
	error_log( $num." == ".$num_chunks );
	
	if ($num === $num_chunks) {
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
					
				$mails = (string) $xml->Item[$x]->Mails;
				error_log( $mails );
				$mails = explode( ";", $mails );
				
				for( $i = 0; $i < count( $mails ); $i++ ) {
					$hash = md5( "adhocuserdownload-".time()."-".$mails[$i] );
					$user = sql_aget( "accounts", "email='".$mails[$i]."' AND showMagazines like '%".$mag[0]["id"]."%' LIMIT 1", "*" );
					if( !empty( $user[0]["id"] ) ) {
						$names = array( "user_id", "hash", "magazine_id", "email", "time", "redirecto" );
						$values = array( $user[0]["id"], $hash, $pub[0]["magazine_id"], $mails[$i], time(), "page=assets" );
						sql_add( "adhoc_hotlinks", $names, $values );		
						
						$to = $mails[$i]."|".$mails[$i];
						//$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
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
			$data = array(
				"Code" => $code,
				"User" => $user[0]["full_name"],
				"Mail" => $user[0]["email"],
				"MailComm" => "Yes",
				"Part" => $_POST["part"],
				"Type" => $_POST["type"],
				"Issue" => $pub[0]["code"],
				"file_path" => $toswitch_path,
				"file_name" => $_FILES["file"]['name']
				);

			$headers = array(
				"Content-Type: multipart/form-data",
				);
			
			$url = "http://".URL."/client/engine/switch/async_send.php";
			systemCurl( $url, $data, $headers=null, $check_ssl=true);
			}
		
		if( !empty( $code ) && !empty( $pub[0]["code"] ) ) {
			sql_update( 'accounts', 'actual="'.$code.'_'.$pub[0]["code"].'"', 'id="'.$_SESSION['intra_user'].'"' );	
			}
			
		print $result;
		}
	}
?>