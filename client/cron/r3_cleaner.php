<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

// client/engine/r3/ collects two kinds of self-invalidating scratch output
// that nothing ever deletes on its own:
//  - tesztAjax.php's "_cache_<hash>.jpg" render cache - content-hash keyed,
//    so an old entry is never looked up again once its source changes, but
//    the file itself just sits there forever.
//  - compare.php / flatplan_reloadbg.php's per-request scratch renders
//    (bg<id>.jpg, compare_<a>_<b>.jpg, bgcomp_<id>*.jpg, <id>_diff_<ts>.png)
//    - their own unlink() calls are commented out in the source, so these
//    accumulate the same way. Confirmed via the 2026-08-02 audit: 79MB /
//    ~300 files, none tied to any job (keyed by staff account id, not
//    pub_id), so cleanupPublicationRemnants() is the wrong place to clean
//    them - this is a straight age-based sweep, same idiom as
//    blob_cleaner.php / adupload_cleaner.php / asset_cleaner.php.
$dir = TRKPATH."/engine/r3";
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

?>
