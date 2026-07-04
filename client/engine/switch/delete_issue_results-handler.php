<?PHP
	
$status = $_POST["result"];
$code = $_POST["jobCode"];
$issue = $_POST["issue"];
$user = "0";

if( $status == "success" ) {
	$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
	$p_id = $issue = sql_get( "publications", "magazine_id='".$magazine[0]["id"]."' AND code='".$issue."'", "*" );
	$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );
	$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
	
	if( $issue[0][0] != "" ) {
		cleanupPublicationRemnants( $issue[0][0], $magazine[0][3], $issue[0][10] );

		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $user, 'deleteIssue', $p_id[0][1], $magazine[0][0],  $p_id[0][10], '', time(), '' );
		sql_add( 'action_log', $names, $values );

		sql_delete( 'publications', 'id="'.$p_id[0][0].'"' );
		removeTempUsers( $p_id[0][0] );
		}
	}
	
else {
	$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
	$pub = sql_aget( "publications", "magazine_id='".$magazine[0]["id"]."' AND code='".$issue."'", "id" );
	if( $pub[0]["id"] != "") {
		sql_update( "publications", "removing='0'", "id='".$pub[0]["id"]."'" );
		}	
	}
?>