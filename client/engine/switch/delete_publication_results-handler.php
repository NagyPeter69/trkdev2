<?PHP
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

			sql_delete( 'ads', 'pub_id="'.$issue[0][0].'"' );
			sql_delete( 'parts', 'pub_id="'.$issue[0][0].'"' );
			$packs = sql_get( 'packages', 'publication_id="'.$issue[0][0].'"', '*' );
			for( $y = 0; $y < count( $packs ); $y++ ) {
				sql_delete( 'package_info', 'package_id="'.$packs[$y][0].'"' );
				}
			sql_delete( 'packages', 'publication_id="'.$issue[0][0].'"' );
			if( is_dir( '../packages/'.$magazine[0][3].'/'.$issue[0][10] ) ) {
				delTree('../packages/'.$magazine[0][3].'/'.$issue[0][10] );
				}

			sql_delete( 'publications', 'id="'.$pubs[$p][0].'"' );

			//ideiglenes felhasználók törlése
			removeTempUsers( $pubs[$p][0] );
			}
			
		if( is_dir( '../packages/'.$magazine[0][3] ) )
			delTree('../packages/'.$magazine[0][3] );	

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