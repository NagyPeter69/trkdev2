<?php
include("/var/www/html/client/engine/switchAPI.php");

$file = "t.pdf";

	$username = "web_user_1";
	$password = "!@\$RtLIMoUOyszdyw8aT7zx5sPfZcy69ZnM0JWGELe65B4N6KztFBFawQZ4Gs7t8eWakPeW/z+wLCc0mgtsFCbaCgRirK6sqTh4ajsTGCINHIvbnfJ/RMry4BPeE1xadU5I0KV6Yi1LLSWFN9FDbYoA3eeOpPMAvQ/pf4ha0j50cnk=";
	
	$headers = array(
		"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
		);
		
	$data = "username=".$username."&password=".$password;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://192.168.1.8:51088/login" );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
	
	$login = curl_exec ($ch);
	$response = json_decode($login, true);
	
	$token = "Bearer ".$response["token"];

//var_dump( $token );


$headers = array(
		"Authorization: ".$token."",
		"Content-Type: multipart/form-data",
		"Connection: Keep-Alive",
		);

	$data = array(
		"flowId" => "66",
		"objectId" => "1035",
		"jobName" => $file,
		);
				
	$metas = array(
		"spMF_5" => "event",
		"spMF_1" => "client",
		"spMF_2" => "pubName",
		"spMF_3" => "jobCode",
		"spMF_4" => "issue",
		"spMF_6" => "description",
		"spMF_19" => "position",
		"spMF_20" => "offset1",
		"spMF_21" => "offset2",
		"spMF_8" => "pageVersion",
		"spMF_9" => "color",
		"spMF_10" => "pageNum",
		"spMF_11" => "pageType",
		"spMF_12" => "size",
		"spMF_13" => "caption",
		"spMF_17" => "capColor",
		"spMF_16" => "orientation",
		"spMF_15" => "capPosition",
		"spMF_14" => "state",
		);			
				
	$metadata = array();
	
	foreach( $metas as $key => $value ) {
		$val = ( !empty( $datas[$value] ) or $datas[$value] == "0" ) ? $datas[$value] : "";
		
		$metadata[] = metadata( $key, $value, $val );
		}	
		
	$data["metadata"] = json_encode($metadata);
	
$mime = mime_content_type( realpath( $file ) );
$data["file[0][path]"] = "";
$data["file[0][file]"] = new CurlFile( realpath( $file ), $mime, $file );


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://192.168.1.8:51088/api/v1/job" );
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
curl_setopt($ch, CURLOPT_POST, true );
curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);

curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('curl_log-'.time().'.txt', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
$response = curl_exec ($ch);


var_dump( $response );
?>