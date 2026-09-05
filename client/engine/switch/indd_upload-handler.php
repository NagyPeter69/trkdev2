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
	
$file = $_POST["fileName"];

$mag = sql_aget( "magazines", "code='".$_POST["jobCode"]."'", "*" );
$pub = sql_aget( "publications", "magazine_id='".$mag[0]["id"]."' AND code='".$_POST["issue"]."'", "*" );

$check = sql_aget( "assets", "pub_id='".$pub[0]["id"]."' AND parent='0' AND fp='' AND name='".$_POST["fileName"].".indd'", "*" );
if( empty( $check[0]["id"] ) ) {
	if( strpos( $_FILES[0]["name"], '=?UTF-8?') !== false) {
		$_FILES[0]["name"] = $_POST["fileName"].".indd";
		}
	
	$names = array( "pub_id", "fp", "parent", "name", "type", "time", "stripped_name", "hide", "origname" );
	//$values = array( $pub[0]["id"], "", "0", mysqli_real_escape_string($con, $_POST["fileName"].".indd"), $_FILES[0]["type"], time(), mysqli_real_escape_string($con, $_POST["fileName"]), "", mysqli_real_escape_string($con, $_POST["fileName"].".indd") );
	$values = array( $pub[0]["id"], "", "0", mysqli_real_escape_string($con, $_POST["fileName"].".indd"), $_FILES[0]["type"], time(), mysqli_real_escape_string($con, $_POST["fileName"]), "", mysqli_real_escape_string($con, $_POST["fileName"]) );
	sql_add( "assets", $names, $values );
	}

$dir = TRKPATH."/assets/".$pub[0]["id"]."/0";

if( !is_dir( TRKPATH."/assets/".$pub[0]["id"] ) ) {
	$oldmask = umask(0);
	mkdir( TRKPATH."/assets/".$pub[0]["id"], 0777 );
	umask($oldmask);		
	}

if( !is_dir( TRKPATH."/assets/".$pub[0]["id"]."/0" ) ) {
	$oldmask = umask(0);
	mkdir( TRKPATH."/assets/".$pub[0]["id"]."/0", 0777 );
	umask($oldmask);		
	}
	
move_uploaded_file( $_FILES[0]["tmp_name"], $dir."/".$_FILES[0]["name"] );
	
?>