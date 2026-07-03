<?php
include_once('../../../engine/connect.php');
include_once('../../../engine/engine.php');
include_once('../../../engine/xml_handler.php');
include_once( "../switchAPI.php" );

echo "<pre>";

$curl = curl_init();

$body = file_get_contents( "BABAB_TST_1701_F.pdf" );
file_put_contents("webhook-send.pdf", $body );

die();

curl_setopt_array($curl, array(
  CURLOPT_PORT => "51080",
  CURLOPT_URL => "http://192.168.1.8:51080/whtest/request",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 30,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "POST",
  CURLOPT_POSTFIELDS => $body,
  CURLOPT_HTTPHEADER => array(
    "Cache-Control: no-cache",
    "Content-Type: application/pdf",
    "Postman-Token: 4f7116e0-b3b9-0773-6eeb-199d48d63a84",
    "CCM_client: Colorcom",
    "CCM_jobCode: TST",
    "CCM_issue: 1701",
    "CCM_event: new_ad",
    "CCM_description: PROBAHIRD",
    "CCM_remark: 1_1",
    
  ),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
  echo "cURL Error #:" . $err;
} else {
  echo $response;
}
	
?>