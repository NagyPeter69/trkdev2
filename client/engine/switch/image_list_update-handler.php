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
$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];

$mag = sql_aget( "magazines", "code='".$jcode."'", "*" );
$pub = sql_aget( "publications", "magazine_id='".$mag[0]["id"]."' AND code='".$issue."'", "*" );
	
$imagemap = array();
$target = time()."-".$_POST["fileName"];

error_log( "másolás: ".$_FILES[0]["tmp_name"]." => ".$target );
error_log( "pub: magazine_id='".$mag[0]["id"]."' AND code='".$issue."'" );
if( move_uploaded_file( $_FILES[0]["tmp_name"], $target ) ) {
	$file = file_get_contents( $target );
	$file = nl2br( $file );
	$imagemap = explode( "\r", $file );
	$pack = "";
	
	for( $i = 0; $i < count( $imagemap ); $i++ ) {
		$imagemap[$i] = str_replace( "<br />", "", $imagemap[$i] );
		if( strpos( $imagemap[$i], ":::" ) ) {
			$temp = explode( " ", $imagemap[$i] );
			if( $temp[0] != $pack ) {
				$pack = $temp[0];
				}
			}
		
		if( substr_count( $imagemap[$i], "\t") > 0 ) {
			$line = explode( "\t", $imagemap[$i] );
			$maszk = ( $line[3] == "*" ) ? "0" : "1";
			$retus = ( $line[4] == "*" ) ? "0" : $line[4];
			$imgname = trim( $line[1] );
			$imgname = mysqli_real_escape_string( $con, $imgname );
			
			$check = sql_aget( "image_map", "pub_id='".$pub[0]["id"]."' AND name='".$imgname."'", "*" );
			if( empty( $check[0]["id"] ) ) {
				$names = array( "pub_id", "name", "maszk", "retus", "pack" );
				$values = array( $pub[0]["id"], $imgname, $maszk, $retus, $pack );
				sql_add( "image_map", $names, $values );
				}
			else {
				sql_update( "image_map", "maszk='".$maszk."', retus='".$retus."'", "id='".$check[0]["id"]."'" );
				}
			}
		}
		
	error_log( "DEBUG: ".$temp );	
	
	unlink( $target );
	}
?>