<?php
	set_include_path(__DIR__);
	chdir(__DIR__);
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );

	$archiving = sql_aget( "publications", "status='archiving'", "*" );
	for( $i = 0; $i < count( $archiving ); $i++ ) {
		$hour = 5;
		$started_archiving = sql_aget("action_log", "action='archivingIssue' AND magazine='".$archiving[$i]["magazine_id"]."' AND issue='".$archiving[$i]["code"]."' ORDER BY id DESC LIMIT 1", "*");
		if( !empty( $started_archiving[0]["id"] ) ) {
			$dl = strtotime( "+".$hour." hours", $started_archiving[0]["date"] );
			
			if( $dl < time() ) {
				sql_update( "publications", "status='archive_failed'", "id='".$archiving[$i]["id"]."'" );
				}
			
			}
		}

	$publications = sql_get( 'publications', 'status="active" OR status="current" OR status="created"', '*' );
	for( $i = 0; $i < count( $publications ); $i++ ) {
		$status = $xml_status = '';
		$ads = sql_get( 'ads', 'pub_id="'.$publications[$i][0].'"', '*' );
		$pack = sql_get( 'packages', 'publication_id="'.$publications[$i][0].'"', '*' );

		if( count( $pack ) > 0 ) {
			$status = "current";
			$xml_status = "active";
			}
		elseif( count( $ads ) > 0 ) {
			$status = "active";
			$xml_status = "active";
			}
		else {
			$status = "created";
			$xml_status = "created";
			}	
		$magazine = sql_get( 'magazines', 'id="'.$publications[$i][2].'"', '*' );
		if( $magazine[0][3] != "" && $publications[$i][10] != "" && $publications[$i][0] != "" ) {
			$check = sql_aget( "publications", "id='".$publications[$i][0]."'", "*" );
			if( $check[0]["status"] == "active" OR $check[0]["status"] == "current" OR $check[0]["status"] == "created" ) {
				if( $check[0]["status"] != $status ) {
					error_log( $check[0]["status"]." => ".$status );
					sql_update( 'publications', 'status="'.$status.'"', 'id="'.$publications[$i][0].'"' );
					changeIssueStatus( $magazine[0][3]."_".$publications[$i][10].".xml", $xml_status, $publications[$i][0] );
					}
				}
			}
		}
	
?>