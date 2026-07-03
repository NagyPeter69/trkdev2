<?php
include_once('../engine/connect.php');

include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');

$p_id = sql_get( 'publications', 'magazine_id="225" AND code="QMG61"', '*' );
$page = intval( "001" );

$new = TRKPATH."/packages/QMG61/QMG61/undefined/001_57600_preview.pdf";
$pageWidth = "1";
$color = partDetect( $p_id[0][0], $page );


thumbCreate2( $new, $pageWidth, $color );

?>