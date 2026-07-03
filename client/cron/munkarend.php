<?php
set_include_path(__DIR__);
chdir(__DIR__);

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

$dir = "/var/www/html/client/cron";
$file = "munkarend.json";

$munkarend = json_decode( file_get_contents( $dir."/".$file), true );

$key = "c9ba96b7cea5912eff9a813d19896a8699ae7b47105e4cc6356377bb23ca6abb";
$year = intval( date("Y") );
//$year++;
var_dump( $year );

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://szunetnapok.hu/api/".$key."/".$year."/" );
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET" );
curl_setopt($ch, CURLOPT_POST, true );
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
$response = curl_exec ($ch);
$response = json_decode( $response, true );

echo "<pre>";
$days = $response["days"];
for( $i = 0; $i < count( $days ); $i++ ) {
	$date = explode( "-", $days[$i]["date"] );
	$munkarend[ $date[0] ][ intval($date[1]) ][ $date[2] ] = $days[$i]["type"];
	}

var_dump( $munkarend );
$munkarend = json_encode( $munkarend );
var_dump( $munkarend );
var_dump( is_file( $dir."/".$file ) );
file_put_contents( $dir."/".$file, $munkarend );

?>