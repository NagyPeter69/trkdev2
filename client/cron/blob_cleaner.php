<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

$dir = TRKPATH."/uploads/blob/chunk";
$dirs = load_dirs( $dir );

for( $i = 0; $i < count( $dirs ); $i++ ) {
	$created = filectime( $dir."/".$dirs[$i] );
	$diff = time() - $created;
	//echo $dirs[$i]." => ".$created." diff: ".$diff;
	
	if( $diff >= 86400 ) {
		delTree( $dir."/".$dirs[$i] );
		}
	
	//echo "<br>";
	}


?>