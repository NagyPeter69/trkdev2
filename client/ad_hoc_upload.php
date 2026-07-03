<?php
	include_once( '../engine/connect.php' );
	include_once( '../engine/engine.php' );
	include_once( '../engine/xml_handler.php' );
	
	include_once( 'lang/en.php' );
	
$user_id = $_POST['uid'];
$user = sql_get( 'accounts', 'id=\''.$user_id.'\'', '*' );

/*	if( isset( $user_id ) && $user_id != '' ) {
		$lang = $user[0][12];
		}
	else {
		$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
		}
	switch( $lang ) {
		case 'hu':
			include_once( 'lang/hu.php' );
			break;
		case 'en':
			include_once( 'lang/en.php' );
			break;
		default:
			include_once( 'lang/hu.php' );
			break;
		}*/


function bytesToSize1024($bytes, $precision = 2) {
    $unit = array('B','KB','MB');
    return @round($bytes / pow(1024, ($i = floor(log($bytes, 1024)))), $precision).' '.$unit[$i];
}

$_FILES["file"]['name'] = str_replace( "-", "_", str_replace( " ", "_", letter_change2( $_FILES["file"]['name'] ) ) );	

$full_type = explode( '.', $_FILES["file"]['name'] );
$type = strtoupper( $full_type[count($full_type)-1] );		
$allowed = array( 'PDF', 'TIFF', 'TIF', 'JPG', 'JPEG', 'PSD', 'ZIP' );
	
$tmp_name = $_FILES["file"]["tmp_name"];

$target = 'temp';
$u_jobs = sql_get( 'ad_hoc', 'user_id="'.$user[0][0].'"', '*' );

$doc_name = $_FILES["file"]['name'];

if( in_array( $type, $allowed ) ) {
	if ( move_uploaded_file($tmp_name, $target.'/'.$doc_name ) ) {
		$page = countpdfpage( $target.'/'.$doc_name );
		if( $_POST['type'] == 'proof' ) {
			$result = $doc_name.'-1';
			}
		else {
			$name = CreateJobCode( $user_id );
			$client = sql_get( 'publishers', 'id="'.$user[0][4].'"', '*' );
			
			$array = Array();
			$array["client"] = $client[0][1];
			$array["pubName"] = $doc_name;
			$array["jobCode"] = $_POST['code'];
			$array["issue"] = '';
			$array["event"] = 'submit';
			$array["description"] = $_POST['typeof'];
			$array["color"] = $_POST['proof_color'];
			$array["remark"] = $_POST['proof_message'];

			$myxml = array_to_xml( $array, 'eventComm' );
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($myxml);
			$dom->formatOutput = true;
		
			$target = "uploads/adhoc";
			$counter = get_counter('.');
			$switch_name = 'message_'.$counter;
			inc_counter('.');
			file_put_contents( $target.'/'.$switch_name.'.xml', $dom->saveXML() );

			$old = "temp/".$doc_name;
			$ext = explode( '.', $doc_name );
			$ext = strtolower( $ext[count($ext)-1] );
		
			rename( $old, $target.'/'.$switch_name.'.'.$ext );

			$names = array( 'user_id', 'type', 'settings', 'gen_name' );
			$settings = 'Color:'.$_POST['proof_color'].'|Comment:'.$_POST['typeof'].'|Original_filename:'.$doc_name.'|Type:'.$_POST['typeof'].'';
			$values = array( $user_id, $_POST['type'], $settings, $_POST['code'] );
			sql_add( 'ad_hoc', $names, $values );
			
			$result = 'Feltöltve-1';
			}
		}

	else {
		$result = 'Váratlan hiba-0';
		}
	}
else {
	$result = 'Hibás kiterjesztés ('.ini_get("upload_max_filesize").')-0';
	}
echo $result;