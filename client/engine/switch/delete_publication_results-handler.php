<?PHP

// This is an inbound Switch webhook, not a browser session - Switch has no
// Tracker login, so the equivalent check here is verifying the request
// actually comes from the known Switch host rather than a session (see
// client/plugins/pubsApply.php's 2026-09-05 fix for the session-based
// version used everywhere a browser session applies). Confirmed live this
// session: before this, ANYONE reaching this URL could forge a Switch
// event (fake page approvals, fake uploads) with no verification at all.
if( ( $_SERVER['REMOTE_ADDR'] ?? '' ) !== '192.168.1.8' ) {
	http_response_code( 403 );
	exit;
	}
error_log( "fájlban vagyok." );	
	
$code = $_POST["jobCode"];
$user = $_POST["user"];
$result = $_POST["result"];		

if( $result == "success" ) {
	$mag = sql_get( 'magazines', 'code="'.$code.'"', '*' );
	$pubs = sql_get( 'publications', 'magazine_id="'.$mag[0][0].'"', '*' );
	$magazine = sql_get( 'magazines', 'id="'.$mag[0][0].'"', '*' );
	$publisher = sql_get( 'publishers', 'id="'.$mag[0][1].'"', '*' );	
	
	if( $mag[0][0] != "" ) {
		$users = sql_aget( "accounts", "`showMagazines` LIKE '%".$mag[0][0]."%'", "id, showMagazines" );
		for( $x = 0; $x < count( $users ); $x++ ) {
			$temp = explode( ",", $users[$x]["showMagazines"] );
			$index = array_search( $mag[0][0], $temp );
			if( $index !== FALSE ) array_splice($temp, $index, 1);
			$temp = implode( ",", $temp );
			sql_update( "accounts", "showMagazines='".$temp."'", "id='".$users[$x]["id"]."'" );
			}					
	
		changeXmlDatabase( 'delete', array( "old_code" => $mag[0][3] ), XMLPATH.'/'.PMD.'.xml' );	
		for( $p = 0; $p < count( $pubs ); $p++ ) {
			$issue = sql_get( 'publications', 'id="'.$pubs[$p][0].'"', '*' );
			$publisher = sql_get( 'publishers', 'id="'.$issue[0][1].'"', '*' );

			cleanupPublicationRemnants( $issue[0][0], $magazine[0][3], $issue[0][10] );

			sql_delete( 'publications', 'id="'.$pubs[$p][0].'"' );

			//ideiglenes felhasználók törlése
			removeTempUsers( $pubs[$p][0] );
			}
			
		if( is_dir( '../packages/'.$magazine[0][3] ) )
			delTree('../packages/'.$magazine[0][3] );

		// Magazine-level data that isn't scoped to any single issue, so it
		// can't be reached by cleanupPublicationRemnants()'s per-pub_id
		// deletes above - confirmed orphaned via the 2026-08-02 audit.
		// parts in particular can legitimately carry pub_id='0' for
		// "Regular" magazines (see pubsApply.php) - that's the magazine's
		// part-layout template, created before any issue exists, and only
		// ever cleaned up here, at whole-magazine deletion.
		sql_delete( 'ad_sizes', "magazine_id='".$mag[0][0]."'" );
		sql_delete( 'parts', "mag_id='".$mag[0][0]."'" );
		sql_delete( 'calendar_events', "magazine_id='".$mag[0][0]."'" );
		sql_delete( 'calendar_post', "magazine_id='".$mag[0][0]."'" );
		sql_delete( 'marquard_calendar', "magazine_id='".$mag[0][0]."'" );

		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( $user, 'deleteMagazine', $mag[0][2], $mag[0][2], '', '', time(), '' );
		sql_add( 'action_log', $names, $values );

		sql_delete( 'magazines', 'id="'.$mag[0][0].'"' );
		}
	}
else {
	$magazine = sql_aget( "magazines", "code='".$code."'", "*" );
	if( $magazine[0]["id"] != "") {
		sql_update( "magazines", "removing='0'", "id='".$magazine[0]["id"]."'" );
		}
	}
?>