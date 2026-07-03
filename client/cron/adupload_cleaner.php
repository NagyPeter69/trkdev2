<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

$dir = TRKPATH."/uploads/ads";
$dirs = load_dir_files( $dir, ".pdf" );

for( $i = 0; $i < count( $dirs ); $i++ ) {
	$created = filectime( $dir."/".$dirs[$i] );
	$diff = time() - $created;
	echo $dirs[$i]." => ".$created." diff: ".$diff."  ";
	
	if( $diff >= 86400 ) {
		unlink( $dir."/".$dirs[$i] );
		echo " Több mint 1 napos!!!<br>";
		}
	else {
		
		}
		
	echo "<br>";
	}


?>