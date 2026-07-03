<?PHP
error_log( "fájlban vagyok." );	
	
$code = $_POST["jobCode"];
$result = $_POST["result"];		
$desc = $_POST["result_description"];		
$type = $_POST["type"];		

if( $type == "Regular" ) {
	if( $result == "success" ) {
		sql_update( "magazines", "clientChange='2'", "id='".$code."'" );
		}
	else {
		sql_update( "magazines", "clientChange='3', clientChangeResult='".$desc."'", "id='".$code."'" );
		}	
	}
else {
	if( $result == "success" ) {
		sql_update( "publications", "clientChange='2'", "code='".$code."'" );
		}
	else {
		sql_update( "publications", "clientChange='3', clientChangeResult='".$desc."'", "code='".$code."'" );
		}	
	}
?>