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
$col = partDetect( $pubId, $page );

$command = './r3 -binary -mode:RENDER -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -width:'.$sizes["Width"].'  -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.$col.'.icc '.$sourceFile.' $@ >'.$outputFile.' 2>&1';
shell_exec('
	cd /var/www/html/r3API/r3 2>&1;
	'.$command.';
	');
?>
