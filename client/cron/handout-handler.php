<?php
	
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

$handouts = sql_aget( "flatplan_handout", "arrived='0' order by id DESC", "*" );
for( $i = 0; $i < count( $handouts ); $i++ ) {
	if( is_file( "/var/www/html/client/handout/".$handouts[$i]["filename"] ) ) {
		error_log( "TIMECHECK: ".filemtime( "/var/www/html/client/handout/".$handouts[$i]["filename"] )." >= ".$handouts[$i]["date"] );
		if( filemtime( "/var/www/html/client/handout/".$handouts[$i]["filename"] ) >= $handouts[$i]["date"] ) {
			sql_update( "flatplan_handout", "arrived='1'", "id='".$handouts[$i]["id"]."'" );
			}
		}
	}

?>