<?php
$token = "";
include_once('/var/www/html/engine/connect.php');
include_once('/var/www/html/engine/engine.php');
include_once('/var/www/html/engine/xml_handler.php');


function SwitchLogin() {
	global $token;
		
	$username = "web_user_1";
	$password = "!@\$RtLIMoUOyszdyw8aT7zx5sPfZcy69ZnM0JWGELe65B4N6KztFBFawQZ4Gs7t8eWakPeW/z+wLCc0mgtsFCbaCgRirK6sqTh4ajsTGCINHIvbnfJ/RMry4BPeE1xadU5I0KV6Yi1LLSWFN9FDbYoA3eeOpPMAvQ/pf4ha0j50cnk=";
	
	$headers = array(
		"Content-Type: application/x-www-form-urlencoded; charset=UTF-8",
		);
		
	$data = "username=".$username."&password=".$password;
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_URL, SWITCHLOGINURL );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
	
	$login = curl_exec ($ch);
	$response = json_decode($login, true);
	$token = isset( $response["token"] ) ? "Bearer ".$response["token"] : "";

	return $response;
	}

function SwitchASend( $datas, $file = "" ) {
	global $token;

	$doc_name = ( !empty( $file["name"] ) ) ? $file["name"] : time().'.pdf';
	$login = SwitchLogin();
	
	$file = ( !empty( $file["path"] ) ) ? TRKPATH."/".$file["path"]."/".$doc_name : DUMMYPDF;
	
	error_log( $file );
	
	$size = filesize( $file );	
	$mime = mime_content_type( realpath( $file ) );
	
	$headers = array(
		"Authorization: ".$token."",
		"Content-Type: multipart/form-data",
		"Connection: Keep-Alive",
		);

	$data = array(
		"flowId" => AQID,
		"objectId" => AQOID,
		"jobName" => $doc_name,
		);
				
	$metas = array(
		"spMF_1" => "Code",
		"spMF_2" => "User",
		"spMF_3" => "Mail",
		"spMF_4" => "MailComm",
		"spMF_5" => "Part",
		"spMF_6" => "Type",
		"spMF_7" => "Issue",
		);			
				
	$metadata = array();
	
	foreach( $metas as $key => $value ) {
		$val = ( !empty( $datas[$value] ) or $datas[$value] == "0" ) ? $datas[$value] : "";
		
		$metadata[] = metadata( $key, $value, $val );
		}	
	
	error_log( json_encode($metadata) );
		
	$data["metadata"] = json_encode($metadata);
	$data["file[0][path]"] = "";
	//$data["file[0][file]"] = "@".realpath( $file ).";filename=".$doc_name.";type=".$mime."";
	$data["file[0][file]"] = new CurlFile( realpath( $file ), $mime, $doc_name );
		 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_URL, SWITCHURL );
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
	curl_setopt($ch, CURLOPT_POST, true );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$response = curl_exec ($ch);
	
	error_log( $response );
	$response = json_decode($response, true);
	
	return array( $response["status"], "Error. Please try later." );	
	}

function SwitchAnyagSend( $datas, $file = "" ) {
	global $token;

	$doc_name = ( !empty( $file["name"] ) ) ? $file["name"] : time().'.pdf';
	$login = SwitchLogin();
	
	$file = ( !empty( $file["path"] ) ) ? TRKPATH."/".$file["path"]."/".$doc_name : DUMMYPDF;
	
	$size = filesize( $file );	
	$mime = mime_content_type( realpath( $file ) );
	
	$headers = array(
		"Authorization: ".$token."",
		"Content-Type: multipart/form-data",
		"Connection: Keep-Alive",
		);

	$data = array(
		"flowId" => "20",
		"objectId" => "1630",
		"jobName" => $doc_name,
		);
				
	$metas = array(
		"spMF_1" => "Code",
		"spMF_2" => "User",
		);			
				
	$metadata = array();
	
	foreach( $metas as $key => $value ) {
		$val = ( !empty( $datas[$value] ) or $datas[$value] == "0" ) ? $datas[$value] : "";
		
		$metadata[] = metadata( $key, $value, $val );
		}	
		
	$data["metadata"] = json_encode($metadata);
	$data["file[0][path]"] = "";
	//$data["file[0][file]"] = "@".realpath( $file ).";filename=".$doc_name.";type=".$mime."";
	$data["file[0][file]"] = new CurlFile( realpath( $file ), $mime, $doc_name );
		 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_URL, SWITCHURL );
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
	curl_setopt($ch, CURLOPT_POST, true );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
	curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$response = curl_exec ($ch);
	error_log( $response);
	$response = json_decode($response, true);
	
	return array( $response["status"], "Error. Please try later." );	
	}

function SwitchSend( $datas, $file = "" ) {
	global $token;

	$doc_name = ( !empty( $file["name"] ) ) ? $file["name"] : time().'.pdf';
	error_log( $doc_name );
	$login = SwitchLogin();
	
	$file = ( !empty( $file["path"] ) ) ? TRKPATH."/".$file["path"]."/".$doc_name : DUMMYPDF;
	
	$size = filesize( $file );	
	$mime = mime_content_type( realpath( $file ) );

	error_log( "kapott adat:");
	error_log( gettype( $datas ) );
	error_log( print_r( $datas,true ) );
	
	if( empty( $datas["type"] ) ) {
		error_log( 'PMD Path: '.TRKPATH.'/xml/'.PMD.'.xml' );
		$newxml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
		$xpath = $newxml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				//error_log( $temp->Item[$x]->Code." == ".$datas["jobCode"] );
				if( $temp->Item[$x]->Code == $datas["jobCode"] ) {
					break;
					}
				}
			}
		
		error_log( "Type: ".$newxml->Item[$x]->Type );
		$datas["type"] = $newxml->Item[$x]->Type;
		}
/*
	if( $datas["type"] == "Adhoc" ) {
		$pub = sql_aget( "publications", "code='".(string) $newxml->Item[$x]->Code."'" );
		if( $pub[0]["clientType"] == "unknown" ) {
			$datas["client"] = "";
			}
		}
*/	
	$headers = array(
		"Authorization: ".$token."",
		"Content-Type: multipart/form-data",
		"Connection: Keep-Alive",
		);

	$data = array(
		"flowId" => FLOWID,
		"objectId" => OBJECTID,
		"jobName" => $doc_name,
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
		"spMF_14" => "type",
		);			
				
	$metadata = array();
	
	foreach( $metas as $key => $value ) {
		$val = ( !empty( $datas[$value] ) or $datas[$value] == "0" ) ? $datas[$value] : "";
		
		$metadata[] = metadata( $key, $value, $val );
		}	
		
	$data["metadata"] = json_encode($metadata);
	error_log( $data["metadata"] );
	$data["file[0][path]"] = "";
	//$data["file[0][file]"] = "@".realpath( $file ).";filename=".$doc_name.";type=".$mime."";
	$data["file[0][file]"] = new CurlFile( realpath( $file ), $mime, $doc_name );
		 
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_URL, SWITCHURL );
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
	curl_setopt($ch, CURLOPT_POST, true );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
	//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$response = curl_exec ($ch);			
	error_log( $response);
	$response = json_decode($response, true);
	
	return array( $response["status"], "Error. Please try later." );	
	}

function SwitchSend_TESZT( $datas, $file = "" ) {
	global $token;
	
	error_log("Description: ".$datas["description"] );
	error_log( "switch-send: ".$file["name"] );
	
	$doc_name = ( !empty( $file["name"] ) ) ? $file["name"] : time().'.pdf';
	error_log( $doc_name );
	error_log( $file["path"] );
	error_log( "létezik?".is_file( TRKPATH."/".$file["path"]."/".$doc_name )." (itt: ".TRKPATH."/".$file["path"]."/".$doc_name.")" );
	
	$login = SwitchLogin();
	
	$file = ( !empty( $file["path"] ) ) ? TRKPATH."/".$file["path"]."/".$doc_name : DUMMYPDF;
	
	$size = filesize( $file );	
	$mime = mime_content_type( realpath( $file ) );
	
	error_log( "kapott adat:");
	error_log( gettype( $datas ) );
	error_log( print_r( $datas,true ) );
	
	if( $datas["type"] != "xml_data" ) {
		if( empty( $datas["type"] ) ) {
			error_log( 'PMD Path: '.TRKPATH.'/xml/'.PMD.'.xml' );
			$newxml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
			$xpath = $newxml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					//error_log( $temp->Item[$x]->Code." == ".$datas["jobCode"] );
					if( $temp->Item[$x]->Code == $datas["jobCode"] ) {
						break;
						}
					}
				}
			
			error_log( "Type: ".$newxml->Item[$x]->Type );
			$datas["type"] = (string) $newxml->Item[$x]->Type;
			}
		}
/*		}
	if( $datas["type"] == "Adhoc" ) {
		$pub = sql_aget( "publications", "code='".(string) $newxml->Item[$x]->Code."'", "*" );
		if( $pub[0]["clientType"] == "unknown" ) {
			$datas["client"] = "";
			}
		}
*/	
	error_log( print_r( $datas, true ) );
	
	$headers = array(
		"Authorization: ".$token."",
		"Content-Type: multipart/form-data",
		"Connection: Keep-Alive",
		);
	
	$data = array(
		"flowId" => FLOWID_TESZT,
		"objectId" => OBJECTID_TESZT,
		"jobName" => $doc_name,
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
		"spMF_14" => "type",
		);			
				
	$metadata = array();

	foreach( $metas as $key => $value ) {
		$val = ( !empty( $datas[$value] ) or $datas[$value] == "0" ) ? $datas[$value] : "";	
		$metadata[] = metadata( $key, $value, $val );
		}	
	
	
	error_log( $file );
	$data["metadata"] = json_encode($metadata);
	//error_log( $data["metadata"] );
	error_log( $data["headers"] );
	$data["file[0][path]"] = "";
	//$data["file[0][file]"] = "@".realpath( $file ).";filename=".$doc_name.";type=".$mime."";
	$data["file[0][file]"] = new CurlFile( realpath( $file ), $mime, $doc_name );
		
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_URL, SWITCHURL );
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
	curl_setopt($ch, CURLOPT_POST, true );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
	//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$response = curl_exec ($ch);
	
	error_log( "Hirdetés feltöltő");
	error_log( $response);
	$response = json_decode($response, true);
	
	return array( $response["status"], "Error. Please try later." );	
	}

function SwitchSend_Rename( $datas, $file, $newname ) {
	global $token;
	
	error_log("Description: ".$datas["description"] );
	error_log( "switch-send: ".$file["name"] );
	
	$doc_name = ( !empty( $file["name"] ) ) ? $file["name"] : time().'.pdf';
	error_log( $doc_name );
	error_log( $file["path"] );
	error_log( "létezik?".is_file( TRKPATH."/".$file["path"]."/".$doc_name )." (itt: ".TRKPATH."/".$file["path"]."/".$doc_name.")" );
	
	$login = SwitchLogin();
	
	$newpath = TRKPATH."/".$file["path"]."/".$newname;
	$file = ( !empty( $file["path"] ) ) ? TRKPATH."/".$file["path"]."/".$doc_name : DUMMYPDF;
	
	$size = filesize( $file );	
	$mime = mime_content_type( realpath( $file ) );
	
	error_log( "kapott adat:");
	error_log( gettype( $datas ) );
	error_log( print_r( $datas,true ) );
	
	if( empty( $datas["type"] ) ) {
		error_log( 'PMD Path: '.TRKPATH.'/xml/'.PMD.'.xml' );
		$newxml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
		$xpath = $newxml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				error_log( $temp->Item[$x]->Code." == ".$datas["jobCode"] );
				if( $temp->Item[$x]->Code == $datas["jobCode"] ) {
					break;
					}
				}
			}
		
		error_log( "Type: ".$newxml->Item[$x]->Type );
		$datas["type"] = (string) $newxml->Item[$x]->Type;
		}
/*
	if( $datas["type"] == "Adhoc" ) {
		$pub = sql_aget( "publications", "code='".(string) $newxml->Item[$x]->Code."'" );
		if( $pub[0]["clientType"] == "unknown" ) {
			$datas["client"] = "";
			}
		}
*/	
	error_log( print_r( $datas, true ) );
	
	$headers = array(
		"Authorization: ".$token."",
		"Content-Type: multipart/form-data",
		"Connection: Keep-Alive",
		);
	
	$data = array(
		"flowId" => FLOWID_TESZT,
		"objectId" => OBJECTID_TESZT,
		"jobName" => $newname,
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
		"spMF_14" => "type",
		);			
				
	$metadata = array();

	foreach( $metas as $key => $value ) {
		$val = ( !empty( $datas[$value] ) or $datas[$value] == "0" ) ? $datas[$value] : "";	
		$metadata[] = metadata( $key, $value, $val );
		}	

	copy( $file, $newpath );
	
	error_log( $newpath );
	$data["metadata"] = json_encode($metadata);
	//error_log( $data["metadata"] );
	error_log( $data["headers"] );
	$data["file[0][path]"] = "";
	//$data["file[0][file]"] = "@".realpath( $file ).";filename=".$doc_name.";type=".$mime."";
	$data["file[0][file]"] = new CurlFile( realpath( $newpath ), $mime, $newname );
		
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_URL, SWITCHURL );
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
	curl_setopt($ch, CURLOPT_POST, true );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
	//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$response = curl_exec ($ch);
	
	error_log( "SwitchSend Rename");
	error_log( $response);
	$response = json_decode($response, true);
	//unlink( $newpath );
	
	return array( $response["status"], "Error. Please try later." );	
	}

function metadata( $id, $name, $value, $parent = "" ) {
	$temp = array();
	
	$temp["dependency"] = $parent;
	$temp["dependencyCondition"] = ( !empty( $parent ) ? "Equals" : "" );
	$temp["dependencyValue"] = "";
	$temp["displayField"] = true;
	$temp["format"] = "";
	$temp["id"] = $id;
	$temp["ignoreDuplicates"] = false;
	$temp["readOnly"] = false;
	$temp["rememberLastValue"] = false;
	$temp["type"] = "string";
	$temp["value"] = $value;
	$temp["valueIsRequired"] = false;
	$temp["name"] = $name;
	$temp["description"] = "";
	
	return $temp;
	}

function SwitchGetSubmitPonts( $target = NULL ) {
	global $token;

	$headers = array(
		"Authorization: ".$token."",
		);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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