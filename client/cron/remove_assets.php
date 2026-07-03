<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

$path = "/var/www/html/client/assets";
$checks = sql_aget( "assets", "1 group by pub_id order by pub_id DESC", "*" );

for( $i = 0; $i < count( $checks ); $i++ ) {
	$pubcheck = sql_aget( "publications", "id='".$checks[$i]["pub_id"]."'", "*" );
	
	if( empty( $pubcheck[0]["id"] ) ) {
		echo "Pub ID => ".$checks[$i]["pub_id"]." NEM LÉTEZIK!";
		
		if( is_dir( $path."/".$checks[$i]["pub_id"] ) ) {
			echo " VAN SZEMÉT!";
			}
		
		//sql_delete( "assets", "pub_id='".$checks[$i]["pub_id"]."'" );
		
		echo "<br><br>";
		}
	}
	
?>