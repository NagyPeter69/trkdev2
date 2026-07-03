<?PHP
error_log( "fájlban vagyok." );	
	
$status = $_POST["result"];
$publisher = $_POST["client"];
$code = $_POST["jobCode"];
$name = $_POST["description"];
$issue = $_POST["issue"];

$publisher = sql_get( 'publishers', 'name="'.$publisher.'"', '*' );
$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
	
$pub = sql_get( 'publications', 'publisher_id="'.$publisher[0][0].'" AND magazine_id="'.$magazine[0][0].'" AND code="'.$issue.'"', '*' );

if( $status == "success" ) {
	if( $pub[0][0] != "" ) {
		sql_update( 'publications', 'status="archived"', 'id="'.$pub[0][0].'"' );
		$result = changeIssueStatus( $magazine[0][3]."_".$issue.".xml", "archived", $pub[0][0] );
		
		if( $result ) {
			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
			$values = array( '0', 'archiveIssue', $pub[0][1], $pub[0][2], $pub[0][10], '', time(), '' );
			sql_add( 'action_log', $names, $values );
			}		
		}	
	}
	
else {
	sql_update( "publications", "status='archive_failed'", "id='".$pub[0][0]."'" );
	}
		
?>