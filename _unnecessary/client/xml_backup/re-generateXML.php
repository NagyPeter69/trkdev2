<?

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );
include_once( '../../engine/xml_handler.php' );

$pubID = "3102";
$publications = sql_get( "publications", "id='".$pubID."'", "*" );

$magazine = sql_get( "magazines", "id='".$publications[0][2]."'", "*" );

error_log( $magazine[0][3]."_".$publications[0][10].".xml" );
changeIssueStatus( $magazine[0][3]."_".$publications[0][10].".xml", $status, $pubID );

?>