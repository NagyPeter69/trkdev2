<?php
	
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

// Cron only fires this once a minute, so a handout that lands on disk right
// after a run has to wait up to 60s to be noticed - most of a minute for
// something Switch usually renders in seconds. Re-check every 3s for the
// rest of the minute instead of exiting after a single pass, so a file that
// arrives mid-window is picked up almost immediately. Stops at 55s (not the
// full 60) to be clear of the next cron tick, and bails out as soon as
// nothing is left pending so idle minutes don't sit here needlessly.
$deadline = time() + 55;
do {
	$handouts = sql_aget( "flatplan_handout", "arrived='0' order by id DESC", "*" );
	if( count( $handouts ) == 0 ) {
		break;
		}

	for( $i = 0; $i < count( $handouts ); $i++ ) {
		if( is_file( "/var/www/html/client/handout/".$handouts[$i]["filename"] ) ) {
			error_log( "TIMECHECK: ".filemtime( "/var/www/html/client/handout/".$handouts[$i]["filename"] )." >= ".$handouts[$i]["date"] );
			if( filemtime( "/var/www/html/client/handout/".$handouts[$i]["filename"] ) >= $handouts[$i]["date"] ) {
				sql_update( "flatplan_handout", "arrived='1'", "id='".$handouts[$i]["id"]."'" );
				}
			}
		}

	clearstatcache();
	if( time() < $deadline ) {
		sleep( 3 );
		}
	} while( time() < $deadline );

?>