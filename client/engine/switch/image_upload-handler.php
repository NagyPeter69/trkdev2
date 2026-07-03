<?PHP
error_log( "fájlban vagyok." );	
	
$file = $_POST["fileName"];

$mag = sql_aget( "magazines", "code='".$_POST["jobCode"]."'", "*" );
$pub = sql_aget( "publications", "magazine_id='".$mag[0]["id"]."' AND code='".$_POST["issue"]."'", "*" );

$check = sql_aget( "assets", "pub_id='".$pub[0]["id"]."' AND fp='' AND name='".$_POST["packName"].".indd'", "*" );
if( empty( $check[0]["id"] ) ) {
	$names = array( "pub_id", "fp", "parent", "name", "type", "time", "stripped_name", "hide", "origname" );
	//$values = array( $pub[0]["id"], "", "0", mysqli_real_escape_string($con, $_POST["packName"].".indd"), "application/x-indesign", time(), mysqli_real_escape_string($con, $_POST["packName"]), "", mysqli_real_escape_string($con, $_POST["packName"].".indd") );
	$values = array( $pub[0]["id"], "", "0", mysqli_real_escape_string($con, $_POST["packName"].".indd"), "application/x-indesign", time(), mysqli_real_escape_string($con, $_POST["packName"]), "", mysqli_real_escape_string($con, $_POST["packName"].".indd") );
	
	$check = sql_aget( "assets", "pub_id='".$pub[0]["id"]."' AND fp='' AND name='".$_POST["packName"].".indd'", "*" );
	if( empty( $check[0]["id"] ) ) {
		sql_add( "assets", $names, $values );
		}
	}

$parent = sql_aget( "assets", "pub_id='".$pub[0]["id"]."' AND name='".$_POST["packName"].".indd'", "*" );
$dir = TRKPATH."/assets/".$pub[0]["id"]."/".$parent[0]["id"]."";

if( !is_dir( TRKPATH."/assets/".$pub[0]["id"] ) ) {
	$oldmask = umask(0);
	mkdir( TRKPATH."/assets/".$pub[0]["id"], 0777 );
	umask($oldmask);		
	}

if( !empty( $parent[0]["id"] ) ) {
	if( !is_dir( TRKPATH."/assets/".$pub[0]["id"]."/".$parent[0]["id"] ) ) {
		$oldmask = umask(0);
		mkdir( TRKPATH."/assets/".$pub[0]["id"]."/".$parent[0]["id"], 0777 );
		umask($oldmask);		
		}
	}
else {
	if( !is_dir( TRKPATH."/assets/".$pub[0]["id"]."/0" ) ) {
		$oldmask = umask(0);
		mkdir( TRKPATH."/assets/".$pub[0]["id"]."/0", 0777 );
		umask($oldmask);		
		}	
	}
	
move_uploaded_file( $_FILES[0]["tmp_name"], $dir."/".$_FILES[0]["name"] );

if( strpos( $file, "_pre" ) === false ) {
	$names = array( "pub_id", "fp", "parent", "name", "type", "time", "stripped_name", "hide", "origname" );
	$values = array( $pub[0]["id"], "", $parent[0]["id"], $_FILES[0]["name"], $_FILES[0]["type"], time(), $_POST["fileName"], "", mysqli_real_escape_string($con, $_POST["description"]) );
	sql_add( "assets", $names, $values );
	}

?>