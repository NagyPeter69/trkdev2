#!/usr/bin/env php
<?php
// Re-renders ad preview images (NAME_..._thumb.jpg and NAME_..._check.jpg in
// client/advertisements/) that an R3 outage left missing or 0 bytes.
//
// new_ad_results-handler.php renders both once, when Switch delivers the
// checked ad, and never again. During the 2026-10-19 R3 license outage on
// the render node (see engine/r3client.php's r3_render_fallback()) every
// delivered ad got a 0-byte _check.jpg and no _thumb.jpg. This repeats only
// that handler's preview steps from the ad PDF already stored in
// client/advertisements/: the same box math, dynaAdCheckOverlay() and
// adThumbCreate2(). It does not touch the ads table or the action log.
//
// Ads whose Switch result flagged low-res images are skipped: their
// _lowres.jpg overlay needs the low-res areas from Switch's original
// callback, which isn't stored anywhere.
//
// Run it as www-data, with the DB/R3 environment loaded. Dry run by default:
//   set -a; . /etc/trkdev-db.env; set +a
//   runuser -p -u www-data -- php bin/rerender-ad-previews.php --job=GYL_2601 [--apply]

$opts = getopt( '', array( 'job:', 'apply' ) );
if( empty( $opts['job'] ) || !preg_match( '/^[A-Za-z0-9]+_[A-Za-z0-9]+$/', $opts['job'] ) ) {
	fwrite( STDERR, "usage: rerender-ad-previews.php --job=CODE_ISSUE [--apply]\n" );
	exit( 1 );
	}
$apply = isset( $opts['apply'] );

// The handler runs here, and adThumbCreate2() reads its source PDF from here.
$switchDir = realpath( __DIR__.'/../client/engine/switch' );
$adDir = realpath( __DIR__.'/../client/advertisements' );
chdir( $switchDir );
include_once __DIR__.'/../engine/connect.php';
include_once __DIR__.'/../engine/engine.php';
require_once __DIR__.'/../engine/r3client.php';
include_once __DIR__.'/../client/engine/dynaAPI.php';

function previewMissing( $file ) {
	clearstatcache( true, $file );
	return !is_file( $file ) || filesize( $file ) == 0;
	}

$todo = array();
foreach( glob( $adDir.'/*_'.$opts['job'].'_*.pdf' ) as $pdf ) {
	$base = basename( $pdf, '.pdf' );
	if( !previewMissing( $adDir.'/'.$base.'_thumb.jpg' ) && !previewMissing( $adDir.'/'.$base.'_check.jpg' ) ) continue;
	$xml = is_file( $adDir.'/'.$base.'.xml' ) ? (string) file_get_contents( $adDir.'/'.$base.'.xml' ) : '';
	$lowres = preg_match( '#<lowres>\s*true\s*</lowres>#i', $xml );
	$todo[] = array( $base, $lowres );
	}

echo count( $todo )." ad(s) with missing previews".( $apply ? "" : " (dry run)" )."\n";
foreach( $todo as list( $base, $lowres ) ) {
	echo "  ".$base.( $lowres ? "  (low-res flagged - will be skipped)" : "" )."\n";
	}
if( !$apply ) exit( 0 );

$failed = 0;
foreach( $todo as list( $base, $lowres ) ) {
	if( $lowres ) continue;
	$work = $base.'.pdf';
	if( file_exists( $work ) ) {
		echo "  SKIP   ".$base." (".$work." already exists in client/engine/switch - a Switch delivery may be in progress)\n";
		continue;
		}
	copy( $adDir.'/'.$base.'.pdf', $work );

	// Same check-image steps as new_ad_results-handler.php.
	$pdf = new dynapdf();
	include( '../config.inc.php' );
	$pdf->CreateNewPDF( NULL );
	$pdf->InitColorManagement( NULL, NULL , 1 );
	$pdf->OpenImportFile( $switchDir.'/'.$work, dynapdf::ptOpen, NULL );
	$pdf->ImportPDFFile( 1, 1.0, 1.0 );
	$pdf->CloseImportfile();

	$sizes = getBBox( $switchDir.'/'.$work, "", "cropbox" );
	$sizes["Width"] = pixel_( ( $sizes["Right"] - $sizes["Left"] ), 100 );
	$sizes["Height"] = pixel_( ( $sizes["Top"] - $sizes["Bottom"] ), 100 );

	$pdf->EditPage(1);
		$box = $pdf->GetBBox( dynapdf::pbBleedBox );
		$tbox = $pdf->GetBBox( dynapdf::pbTrimBox );
		$tbox['Width'] = $tbox['Right']-$tbox['Left'];
		$tbox['Height'] = $tbox['Top']-$tbox['Bottom'];
		$tbox['StartX'] = ( $tbox['Left']-$box['Left'] )+$box['Left'];
		$tbox['StartY'] = ( $tbox['Bottom']-$box['Bottom'] )+$box['Bottom'];
		$sbox = array(
					"StartX"=> $tbox['StartX']+10,
					"StartY"=> $tbox['StartY']+10,
					"Width"=> $tbox['Width']-20,
					"Height"=> $tbox['Height']-20
					);
	$pdf->EndPage();
	$pdf->CloseFile();

	dynaAdCheckOverlay( $switchDir.'/'.$work, $base.'_check.pdf', $sizes, $tbox, $sbox );
	$imgData = r3run( 'RENDER', array(
		'left' => 0, 'right' => $sizes["Right"] - $sizes["Left"],
		'bottom' => 0, 'top' => $sizes["Top"] - $sizes["Bottom"],
		'width' => $sizes["Width"], 'height' => $sizes["Height"],
		'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => resolveIccProfileByName( "FOGRA_39" ),
		), $switchDir.'/'.$base.'_check.pdf' );
	@unlink( $base.'_check.pdf' );
	if( $imgData != '' ) {
		file_put_contents( $adDir.'/'.$base.'_check.jpg', $imgData );
		}

	// adThumbCreate2() echoes debug text and the image bytes.
	ob_start();
	adThumbCreate2( "advertisements", $work, $base.'_thumb.jpg' );
	ob_end_clean();
	@unlink( $work );

	$ok = !previewMissing( $adDir.'/'.$base.'_thumb.jpg' ) && !previewMissing( $adDir.'/'.$base.'_check.jpg' );
	if( !$ok ) $failed++;
	echo ( $ok ? "  ok     " : "  FAILED " ).$base."\n";
	}
echo ( count( $todo ) - $failed )." done, ".$failed." failed\n";
exit( $failed ? 3 : 0 );
