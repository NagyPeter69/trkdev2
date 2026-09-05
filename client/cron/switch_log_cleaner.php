<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

// client/engine/switch/logs/ is handler.php's per-request debug dump - a
// var_dump() of every inbound Switch webhook's $_GET/$_POST/$_FILES,
// written unconditionally for every event, forever. Nothing anywhere reads
// these back (confirmed by grep across the whole tree) - pure diagnostic
// output, same idiom as r3_cleaner.php/blob_cleaner.php, just never given
// its own cleaner. Confirmed on the real production box (2026-09-05):
// 122,016 files / 489MB going back to October 2023, three years of
// unbounded growth. Keep a 7-day window - long enough to debug a recent
// Switch issue, short enough not to reaccumulate.
$dir = TRKPATH."/engine/switch/logs";
$files = load_dir_files( $dir, "" );

for( $i = 0; $i < count( $files ); $i++ ) {
	$path = $dir."/".$files[$i];
	if( !is_file( $path ) ) continue;

	$created = filectime( $path );
	$diff = time() - $created;

	if( $diff >= ( 86400 * 7 ) ) {
		unlink( $path );
		}
	}

?>
