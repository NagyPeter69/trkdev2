<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

$free_users = sql_get( 'accounts', 'logged_in="1" AND multiplelogin="0"', '*' );

for( $i = 0; $i < count($free_users); $i++ ) {
	$diff = time()-$free_users[$i][22];
	if( $diff > 300 ) {
		sql_update( 'accounts', 'logged_in="0"', 'id=\''.$free_users[$i][0].'\'' );
		user_log( $free_users[$i][0], "logout-auto" );
  		}	
	}
	
?>