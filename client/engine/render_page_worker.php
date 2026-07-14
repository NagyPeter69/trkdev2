<?php
// CLI worker: renders ONE flatplan page to JPEG via r3. Spawned via
// proc_open() by download_ajax.php's "jpg" handler, several at a time,
// instead of that handler shelling out to r3 once per page in a single
// sequential loop - r3 rendering is the actual bottleneck (~2s/page),
// and doing it one at a time for "select many/all pages" (this feature's
// normal use) routinely exceeded php-fpm's request_terminate_timeout and
// got the whole request killed outright.
include_once( __DIR__.'/../../engine/connect.php' );
include_once( __DIR__.'/../../engine/engine.php' );

$sourceFile = $argv[1];
$outputFile = $argv[2];
$pubId = $argv[3];
$page = $argv[4];

$sizes = getBBox( $sourceFile, "", "trimbox" );
$sizes["Width"] = pixel_( $sizes["Width"], 200 );
$sizes["Height"] = pixel_( $sizes["Height"], 200 );
// partDetect() returns the color standard NAME for this specific page (per
// the issue's XML Parts, so different pages in the same batch correctly
// get different standards) - resolveIccProfileByName() then looks up that
// standard's actual icc_file in the color_standards table, same as every
// other r3 render call site in engine.php. Concatenating ".icc" onto the
// name directly (the pattern this was copied from) happens to work today
// only because every standard's icc_file currently equals its own name -
// it would silently point at the wrong (or a nonexistent) profile the
// moment that ever isn't true.
$col = resolveIccProfileByName( partDetect( $pubId, $page ) );

$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].'  -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$col.' '.$sourceFile.' $@ >'.$outputFile.' 2>&1';
shell_exec('
	cd /var/www/html/r3API/r3 2>&1;
	'.$command.';
	');
?>
