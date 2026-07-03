<?php
session_start();

include_once('../engine/connect.php');

include_once('lang/en.php');

include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');

$path = "handout";	
$pub = sql_get( "publications", "id='".$_GET["id"]."'", "*" );
$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );

$uid = ( !empty( $_SESSION["intra_user"] ) ? $_SESSION["intra_user"] : "Visitor" );
$names = array( "userid", "type", "issue", "date" );
$values = array( $uid, "Flipbook", $magazine[0][3]." ".$pub[0][10], time() );
sql_add( "handout_log", $names, $values );

require_once( "/var/www/html/client/plugins/flipbook/index.php" );
?>