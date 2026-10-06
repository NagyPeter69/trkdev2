<?php
// Serves (once, then deletes) a preview JPEG that preview_ajax.php rendered
// into client/temp/. 2026-10-06: used to readfile()+unlink() any path from
// the query string with no login - unauthenticated arbitrary file read and
// delete. Now session-gated and confined to temp/*.jpg.
session_start();
if( empty( $_SESSION['intra_user'] ) ) {
	http_response_code( 403 );
	exit;
	}
session_write_close();

$image_file = realpath( __DIR__."/temp/".basename( (string) ( $_GET['path'] ?? '' ) ) );
if( $image_file === false || dirname( $image_file ) !== realpath( __DIR__."/temp" ) || !preg_match( '/\.jpe?g$/i', $image_file ) ) {
	http_response_code( 404 );
	exit;
	}
header ('Content-length: ' .filesize($image_file));
header ('Content-type: image/jpeg');
@readfile ($image_file);
@unlink($image_file);
?>