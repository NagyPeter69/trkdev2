<?php
$token = "";

function SwitchLogin() {
	global $token;
	
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
	
	return $response;
	}

function SwitchGetSubmitPonts( $target = NULL ) {
	global $token;
	
	$headers = array(
		"Authorization: ".$token."",
		);
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://192.168.1.8:51088/api/v1/submitpoints/" );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
	$server_output = curl_exec ($ch);
	
	$response = json_decode($server_output, true);
	$points = $response;
	
	if( $target != NULL ) {
		$flowID = "";
		for( $i = 0; $i < count( $response ); $i++ ) {
			if( $response[$i]["name"] == $target ) {
				$flowID = $response[$i]["flowId"];
				break;
				}
			}
		$points = $response[$i];
		}
		
	return $points;
	}
	
?>