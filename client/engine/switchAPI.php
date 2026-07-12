<?php
$token = "";
include_once('/var/www/html/engine/connect.php');
include_once('/var/www/html/engine/engine.php');
include_once('/var/www/html/engine/xml_handler.php');

// DEV-environment gate: while IS_DEV_ENVIRONMENT is true (trkdev2 only -
// see constans.php), no outgoing Switch call is allowed unless it's on
// behalf of Colorcom or TestCo, our two test clients. This exists so the
// hard iptables block on Switch connectivity can be lifted for real-world
// testing without risking a call reaching Switch for a real production
// client/job. Resolves the client from the DB via jobCode/Code rather
// than trusting a caller-supplied "client" string, since at least one
// call site (advertisement.php) hardcodes that string as a placeholder
// rather than deriving it, which makes it unreliable as a source of truth
// for something a safety gate depends on.
// Resolves the real publisher name for a magazine code, from MariaDB only -
// never from the PMD/issue XML, which are one-way exports, not a source of
// truth (see SYSTEM_STATE.md). Regular magazines carry their publisher on
// magazines.publisher_id directly. Adhoc magazines always have
// publisher_id=0 by convention regardless of client - a *known* Adhoc
// client (ClientType=known) is recorded on the matching publications row's
// `owner` column instead, so that's the fallback. A genuinely *unknown*
// Adhoc client (ClientType=unknown, no owner) has no DB-resolvable
// publisher at all, and correctly returns '' - there's nothing to verify,
// so callers should treat that as not-allowed, not as an error.
function resolveJobPublisherName( $code ) {
	$magazine = sql_aget( 'magazines', "code='".$code."'", 'id, publisher_id' );
	if( empty( $magazine[0]['id'] ) ) return '';

	$publisherId = $magazine[0]['publisher_id'];
	if( empty( $publisherId ) || $publisherId == '0' ) {
		$pub = sql_aget( 'publications', "magazine_id='".$magazine[0]['id']."' AND code='".$code."'", 'owner' );
		$publisherId = $pub[0]['owner'] ?? '';
		}

	if( empty( $publisherId ) ) return '';

	$publisher = sql_aget( 'publishers', "id='".$publisherId."'", 'name' );
	return $publisher[0]['name'] ?? '';
	}

function switchClientAllowed( $datas ) {
	if( !IS_DEV_ENVIRONMENT ) return true;

	$allowed = array( 'colorcom', 'testco' );

	// Different callers build this array with different key casing -
	// toSwitch()'s 'new_publication' case (the per-issue snapshot upload)
	// uses lowercase 'code', everything else uses 'jobCode'. Missing this
	// meant every per-issue snapshot upload was silently blocked
	// regardless of client, since $code always stayed empty - confirmed
	// via the test protocol, not a hypothetical.
	$code = '';
	if( !empty( $datas['jobCode'] ) ) $code = $datas['jobCode'];
	elseif( !empty( $datas['Code'] ) ) $code = $datas['Code'];
	elseif( !empty( $datas['code'] ) ) $code = $datas['code'];

	if( $code == '' ) return false;

	$name = resolveJobPublisherName( $code );
	if( $name == '' ) return false;

	return in_array( strtolower( $name ), $allowed );
	}

// Same DEV gate as switchClientAllowed(), for the one Switch call that
// isn't scoped to a single job/client: the whole-PMD-dataset upload
// (SendPmdXmlToSwitch() in engine/xml_handler.php) uploads every magazine
// in one shot, so it's only safe to send while every magazine in the
// local DB belongs to Colorcom or TestCo.
function switchBulkSyncAllowed() {
	if( !IS_DEV_ENVIRONMENT ) return true;

	// Was DISTINCT publisher_id with a skip on empty/0 - which meant every
	// Adhoc magazine (publisher_id is always 0 by convention, known client
	// or not) was silently exempted from this check entirely, regardless
	// of what its real client actually was. Not just a block-Adhoc
	// limitation like switchClientAllowed() had - this let unverified
	// Adhoc data ride along in the whole-dataset upload. Resolving per
	// magazine via resolveJobPublisherName() (which knows to fall back to
	// publications.owner for Adhoc) closes that.
	$allowed = array( 'colorcom', 'testco' );
	$magazines = sql_aget( 'magazines', '1=1', 'code' );
	for( $i = 0; $i < count( $magazines ); $i++ ) {
		$name = resolveJobPublisherName( $magazines[$i]['code'] );
		if( $name == '' || !in_array( strtolower( $name ), $allowed ) ) {
			return false;
			}
		}
	return true;
	}

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

	if( !switchClientAllowed( $datas ) ) {
		error_log( "SwitchASend blocked: DEV environment restricts Switch calls to Colorcom/TestCo (Code='".($datas['Code'] ?? $datas['jobCode'] ?? '')."')" );
		return array( "blocked", "Blocked: DEV environment restricts Switch communication to Colorcom/TestCo test clients." );
		}

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
	// CURLOPT_SAFE_UPLOAD can no longer be set to false as of PHP 8.x
	// (curl_setopt() throws ValueError - "unsafe" @filename uploads were
	// removed outright, not just deprecated). Harmless to drop: this call
	// already builds the upload with CurlFile below, which *is* the safe
	// method, so disabling safe uploads was doing nothing useful here -
	// same conclusion already reached for the other Switch senders in
	// this file, which have this line commented out for the same reason.
	//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$response = curl_exec ($ch);
	
	error_log( $response );
	$response = json_decode($response, true);
	
	return array( $response["status"], "Error. Please try later." );	
	}

function SwitchAnyagSend( $datas, $file = "" ) {
	global $token;

	if( !switchClientAllowed( $datas ) ) {
		error_log( "SwitchAnyagSend blocked: DEV environment restricts Switch calls to Colorcom/TestCo (Code='".($datas['Code'] ?? $datas['jobCode'] ?? '')."')" );
		return array( "blocked", "Blocked: DEV environment restricts Switch communication to Colorcom/TestCo test clients." );
		}

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
	// CURLOPT_SAFE_UPLOAD can no longer be set to false as of PHP 8.x
	// (curl_setopt() throws ValueError - "unsafe" @filename uploads were
	// removed outright, not just deprecated). Harmless to drop: this call
	// already builds the upload with CurlFile below, which *is* the safe
	// method, so disabling safe uploads was doing nothing useful here -
	// same conclusion already reached for the other Switch senders in
	// this file, which have this line commented out for the same reason.
	//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	$response = curl_exec ($ch);
	error_log( $response);
	$response = json_decode($response, true);
	
	return array( $response["status"], "Error. Please try later." );	
	}

function SwitchSend( $datas, $file = "" ) {
	global $token;

	if( !switchClientAllowed( $datas ) ) {
		error_log( "SwitchSend blocked: DEV environment restricts Switch calls to Colorcom/TestCo (jobCode='".($datas['jobCode'] ?? $datas['Code'] ?? '')."')" );
		return array( "blocked", "Blocked: DEV environment restricts Switch communication to Colorcom/TestCo test clients." );
		}

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

	if( !switchClientAllowed( $datas ) ) {
		error_log( "SwitchSend_TESZT blocked: DEV environment restricts Switch calls to Colorcom/TestCo (jobCode='".($datas['jobCode'] ?? $datas['Code'] ?? '')."')" );
		return array( "blocked", "Blocked: DEV environment restricts Switch communication to Colorcom/TestCo test clients." );
		}

	// Several callers (e.g. pubsApply.php's publication_created event) call
	// this with no $file at all, leaving it at its default "". PHP 8 throws
	// a hard TypeError ("Cannot access offset of type string on string") on
	// a raw $file["name"]-style access when $file is a string - unlike
	// !empty($file["name"]), which stays safe either way. Normalizing to an
	// array up front makes every access below behave the same regardless of
	// what the caller passed.
	if( !is_array( $file ) ) $file = array();

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

	if( !switchClientAllowed( $datas ) ) {
		error_log( "SwitchSend_Rename blocked: DEV environment restricts Switch calls to Colorcom/TestCo (jobCode='".($datas['jobCode'] ?? $datas['Code'] ?? '')."')" );
		return array( "blocked", "Blocked: DEV environment restricts Switch communication to Colorcom/TestCo test clients." );
		}

	// Same defensive normalization as SwitchSend_TESZT - a raw $file["name"]
	// access is a hard TypeError in PHP 8 if $file is ever not an array,
	// unlike !empty($file["name"]), which stays safe either way.
	if( !is_array( $file ) ) $file = array();

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