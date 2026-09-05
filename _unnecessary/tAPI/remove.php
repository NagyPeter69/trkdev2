<?php

include( "/var/www/html/engine/connect.php" );
include( "/var/www/html/engine/engine.php" );
include( "/var/www/html/engine/xml_handler.php" );
include( "/var/www/html/client/engine/switchAPI.php" );
include( "tAPI.php" );

header('Content-Type: text/html; charset=utf-8');
$api = new tAPI( $_POST );

if( empty( $api->response["error"] ) ) {
	if( $api->checkParams() ) {
		switch( $api->post["type"] ) {
			//USER LÉTREHOZÁS
			case "user":
				if( $api->perm["accounts_removeMember"] ) {
					if( $api->checkParams( "removeuserParams" ) ) {
						$api->removeUser();
						}
					}
				else {
					$api->response["error"] = true;
					$api->response["message"] = "Permission denied";					
					}
				break;
				
			// MAGAZIN törlés
			case "magazine":
				if( $api->perm["magazine_delete"] ) {
					if( $api->checkParams( "removemagParams" ) ) {
						$api->removeMagazine();
						}
					}
				else {
					$api->response["error"] = true;
					$api->response["message"] = "Permission denied";					
					}
				break;
				
			default:
				$api->response["error"] = true;
				$api->response["message"] = "Wrong type value";
				break;
			}
		}
	}
	
echo json_encode( $api->response );
	
?>