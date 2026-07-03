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
		sql_delete( 'ads', 'pub_id="'.$issue[0][0].'"' );
		sql_delete( 'parts', 'pub_id="'.$issue[0][0].'"' );
		
		$packs = sql_get( 'packages', 'publication_id="'.$issue[0][0].'"', '*' );
		for( $y = 0; $y < count( $packs ); $y++ ) {
			sql_delete( 'package_info', 'package_id="'.$packs[$y][0].'"' );
			}
			
		sql_delete( 'packages', 'publication_id="'.$issue[0][0].'"' );
		if( is_dir( TRKPATH.'/packages/'.$magazine[0][3].'/'.$issue[0][10] ) ) {
			delTree( TRKPATH.'/packages/'.$magazine[0][3].'/'.$issue[0][10] );
			}
		
		if( is_dir( '/var/www/switchReports/'.$magazine[0][3].'/'.$issue[0][10] ) ) {
			delTree( '/var/www/switchReports/'.$magazine[0][3].'/'.$issue[0][10] );
			}
			
		$pages = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'"', '*' );
		for( $y = 0; $y < count( $pages ); $y++ ) {
			sql_delete( 'pageinfo', 'id="'.$pages[$y][0].'"' );
			}
			
		$comments = sql_get( 'comments', 'pub_id="'.$issue[0][0].'"', '*' );
		for( $y = 0; $y < count( $comments ); $y++ ) {
			sql_delete( 'comments', 'id="'.$comments[$y][0].'"' );
			}					
		
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