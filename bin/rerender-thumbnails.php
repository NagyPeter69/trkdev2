#!/usr/bin/env php
<?php
// Re-renders page thumbnails that an R3 outage left missing or 0 bytes.
//
// page_pdf-handler.php renders each uploaded page exactly once, on arrival
// (thumbCreate2() for the Flatplan's NNN_PACK_preview.jpg, PDF_prework()
// for the box data and the -cropbox/-trimbox Pages View JPEGs). There is
// no retry: if the render node is down or failing at that moment, the
// page's row and PDF exist but the Flatplan shows an empty slot for it
// forever. First seen 2026-10-19 (GYL 2601's whole FIN delivery, plus
// ELKUL 2603): 10.10.30.23 answered every RENDER with "r3 produced no
// output" while GETDATA still worked.
//
// This finds every pageinfo row of a job whose preview PDF is on disk but
// whose preview JPG (or crop/trim JPEG) is missing or empty, and repeats
// the same two calls the upload handler makes (which use DynaPDF when R3
// RENDER fails). Dry run by default.
//
// Run it as www-data (the web app must be able to overwrite these files
// later), with the DB/R3 environment loaded:
//   sudo -u www-data bash -c 'set -a; . /etc/trkdev-db.env; set +a; php bin/rerender-thumbnails.php --job=GYL_2601'
//   ... --job=GYL_2601 --apply

$opts = getopt( '', array( 'job:', 'apply' ) );
if( empty( $opts['job'] ) || !preg_match( '/^([A-Za-z0-9]+)_([A-Za-z0-9]+)$/', $opts['job'], $m ) ) {
	fwrite( STDERR, "usage: rerender-thumbnails.php --job=CODE_ISSUE [--apply]\n" );
	exit( 1 );
	}
$apply = isset( $opts['apply'] );

// The upload handler runs from here; thumbCreate2() writes its r3Thumb.txt
// log into the cwd, so keep that where it has always been.
chdir( __DIR__.'/../client/engine/switch' );
include_once __DIR__.'/../engine/connect.php';
include_once __DIR__.'/../engine/engine.php';
require_once __DIR__.'/../engine/r3client.php';

$code = mysqli_real_escape_string( $con, $m[1] );
$issueCode = mysqli_real_escape_string( $con, $m[2] );
$magazine = sql_aget( 'magazines', "code='".$code."' LIMIT 1", '*' );
$issue = empty( $magazine[0]['id'] ) ? array() : sql_aget( 'publications', "magazine_id='".$magazine[0]['id']."' AND code='".$issueCode."' LIMIT 1", '*' );
if( empty( $issue[0]['id'] ) ) {
	fwrite( STDERR, "no such job: ".$opts['job']."\n" );
	exit( 1 );
	}

// Same directory/file naming as PDF_prework().
function previewPdfPath( $row ) {
	$dir = TRKPATH."/packages/".$row["code"]."/".$row["issue"];
	if( $row["type"] == "ad" ) {
		return $dir."/_ads/".str_pad( $row["page"], 3, '0', STR_PAD_LEFT )."_".$row["pack_id"]."_".$row["state"]."ad_preview.pdf";
		}
	if( $row["type"] == "PRE" ) {
		$dir .= "/_PRE";
		}
	else {
		$pack = sql_aget( 'packages', "id='".$row["pack_id"]."'", 'directory' );
		if( empty( $pack[0]['directory'] ) ) return '';
		$dir .= "/".$pack[0]['directory'];
		}
	if( $row["fin"] == "1" ) {
		$dir .= "/FIN";
		}
	return $dir."/".str_pad( $row["page"], 3, '0', STR_PAD_LEFT )."_".$row["pack_id"]."_".$row["state"]."preview.pdf";
	}

function needsRender( $pdf ) {
	$base = substr( $pdf, 0, -4 );
	foreach( array( ".jpg", "-cropbox.jpg", "-trimbox.jpg" ) as $suffix ) {
		if( !is_file( $base.$suffix ) || filesize( $base.$suffix ) == 0 ) return true;
		}
	return false;
	}

$rows = sql_aget( 'pageinfo', "code='".$code."' AND issue='".$issueCode."' ORDER BY fin, page+0", '*' );
$todo = array();
foreach( $rows as $row ) {
	$pdf = previewPdfPath( $row );
	if( $pdf == '' || !is_file( $pdf ) ) continue;
	if( needsRender( $pdf ) ) $todo[] = array( $row, $pdf );
	}

echo count( $todo )." of ".count( $rows )." pageinfo rows need a re-render".( $apply ? "" : " (dry run)" )."\n";
foreach( $todo as list( $row, $pdf ) ) {
	echo "  id ".$row["id"]." page ".$row["page"]." fin=".$row["fin"]." ".str_replace( TRKPATH."/", "", $pdf )."\n";
	}
if( !$apply || empty( $todo ) ) exit( 0 );

// If R3 RENDER is still failing, thumbCreate2() and PDF_prework() fall back
// to DynaPDF on their own (dynaFallbackRender()), so no R3 check here.
$failed = 0;
foreach( $todo as list( $row, $pdf ) ) {
	$color = partDetect( $issue[0]['id'], $row["page"], "color", $row["part"] );
	thumbCreate2( $pdf, $row["width"], $color );
	PDF_prework( $row["id"] );
	$ok = !needsRender( $pdf );
	if( !$ok ) $failed++;
	echo ( $ok ? "  ok     " : "  FAILED " )."page ".$row["page"]." fin=".$row["fin"]."\n";
	}
echo ( count( $todo ) - $failed )." re-rendered, ".$failed." failed\n";
exit( $failed ? 3 : 0 );
