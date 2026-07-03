<?php
include_once('../../../engine/connect.php');
include_once('../../../engine/engine.php');
include_once('../../../engine/xml_handler.php');
include_once( "../switchAPI.php" );

echo "<pre>";

$_POST['p_id'] = "2141";
$_POST['name'] = "000";
$_POST['size'] = "1_1";

$issue = sql_get( 'publications', 'id="'.$_POST['p_id'].'"', '*' );
$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
$client = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );

$target = "/var/www/intra/client/engine/switch";
$doc_name = "BABAB_TST_1701_F.pdf";

$login = SwitchLogin();

//SUBMIT FILE
$size = filesize( $target.'/'.$doc_name );
$mime = mime_content_type( realpath( $target.'/'.$doc_name ) );

$headers = array(
	"Authorization: ".$token."",
	"Content-Type: multipart/form-data",
	"Connection: Keep-Alive",
	);

$data = array(
	"flowId" => "64",
	"objectId" => "1035",
	"jobName" => $doc_name,
	);
			
$metadata = array();
$metadata[] = metadata( "spMF_5", "event", "new_ad" );
$metadata[] = metadata( "spMF_1", "client", $client[0][1] );
$metadata[] = metadata( "spMF_2", "pubName", "" );
$metadata[] = metadata( "spMF_3", "jobCode", $magazine[0][3] );
$metadata[] = metadata( "spMF_4", "issue", $issue[0][10] );
$metadata[] = metadata( "spMF_6", "description", strtoupper( $_POST['name'] ) );
$metadata[] = metadata( "spMF_19", "position", "" );
$metadata[] = metadata( "spMF_20", "offset1", "" );
$metadata[] = metadata( "spMF_21", "offset2", "" );
$metadata[] = metadata( "spMF_8", "pageVersion", "" );
$metadata[] = metadata( "spMF_9", "color", "" );
$metadata[] = metadata( "spMF_10", "pageNum", "" );
$metadata[] = metadata( "spMF_11", "pageType", "" );
$metadata[] = metadata( "spMF_12", "size", str_replace( '/', '_', $_POST['size'] ) );
$metadata[] = metadata( "spMF_13", "caption", "" );
$metadata[] = metadata( "spMF_17", "color", "" );
$metadata[] = metadata( "spMF_16", "orientation", "" );
$metadata[] = metadata( "spMF_15", "position", "" );
$metadata[] = metadata( "spMF_14", "state", "" );

	
$data["metadata"] = json_encode($metadata);
$data["file[0][path]"] = "";

$data["file[0][file]"] = "@".realpath( $target.'/'.$doc_name ).";filename=".$doc_name.";type=".$mime."";
//$data["file[0][file]"] = new CurlFile( realpath( $target.'/'.$doc_name ), $mime, $doc_name );

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://192.168.1.8:51088/api/v1/job" );
//curl_setopt($ch, CURLOPT_URL, "http://192.168.1.8:51080/test/notify" );
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
curl_setopt($ch, CURLOPT_POST, true );
curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
//curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
$response = curl_exec ($ch);

error_log( $response );
	
?>