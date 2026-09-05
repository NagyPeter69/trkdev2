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
				if( $api->perm["accounts_addMember"] ) {
					if( $api->checkParams( "adduserParams" ) ) {
						$api->addUser();
						}
					}
				else {
					$api->response["error"] = true;
					$api->response["message"] = "Permission denied";					
					}
				break;
			
			// MAGAZIN LÉTREHOZÁS
			case "magazine":
				if( $api->perm["magazine_add"] ) {
					if( $api->checkParams( "magParams" ) ) {
						$api->createMagazine();
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