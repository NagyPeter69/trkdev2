<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

// client/temp/ is where every "prepare a download, then let the user fetch
// it" flow drops its output (download_ajax.php's per-page zips,
// filetransfer_view.php's ZipStream mass-download) - nothing ever deletes
// the finished file afterward, on the assumption the browser grabs it right
// away. Confirmed via the 2026-08-02 audit: two 1.1GB zips sitting here
// since 2026-07-24, source assets long gone, no process holding them open -
// an abandoned/never-fetched export, not an active one. Same idiom as
// blob_cleaner.php / r3_cleaner.php: age-based sweep rather than trying to
// find and fix every "forgot to unlink after serving" call site individually.
function sweepDir( $dir ) {
	$files = load_dir_files( $dir, "" );
	for( $i = 0; $i < count( $files ); $i++ ) {
		$path = $dir."/".$files[$i];
		if( !is_file( $path ) ) continue;

		$created = filectime( $path );
		$diff = time() - $created;

		if( $diff >= 86400 ) {
			unlink( $path );
			}
		}
	}

sweepDir( TRKPATH."/temp" );

// _zip/ is per-download staging (individual pages/files assembled into the
// zip above) - some call sites already clean up after themselves, others
// (download_ajax.php's "jpg" export type) don't, so sweep it the same way.
sweepDir( TRKPATH."/temp/_zip" );

// asset_size_cache/ is assets_ajax.php's getPackSize() cache, keyed by
// package id and self-invalidating on child-count mismatch - same
// never-actually-deleted pattern as r3/'s _cache_ files.
sweepDir( TRKPATH."/temp/asset_size_cache" );

?>
