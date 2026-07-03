<?php
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );


//PDF_prework( 189975 );

$new = "/var/www/html/client/packages/TSZKUS/21002/undefined_MELL/002_50709_preview.pdf";
$pageWidth = "1";
thumbCreate2( $new, $pageWidth );
?>