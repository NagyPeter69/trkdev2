<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

$dir = TRKPATH."/assets";
$dirs = load_dirs( $dir );

for( $i = 0; $i < count( $dirs ); $i++ ) {
	//echo $dirs[$i];
	$check = sql_aget( "publications", "id='".$dirs[$i]."'", "*" );
	if( empty( $check ) ) {
		echo " => nincs hozzá magazin ".$dir."/".$dirs[$i]."<br>";
		delTree( $dir."/".$dirs[$i] );
		}
	
	//echo "<br>";
	}


?>