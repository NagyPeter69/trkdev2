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
	
$status = $_POST["result"];

if( $status == "success" ) {
	$msg = $_POST["result_description"];
	$publisher = $_POST["client"];
	
	$names = array( "name" );
	$values = array( $publisher );
	$id = sql_add( "publishers", $names, $values );
	
	$admins = sql_aget( "accounts", "`group`='2'", "id, advanced_publishers" );
	for( $i = 0; $i < count( $admins ); $i++ ) {
		sql_update( "accounts", "advanced_publishers='".$admins[$i]["advanced_publishers"].",".$id."'", "id='".$admins[$i]["id"]."'" );
		}
	
	$xml = simplexml_load_file( '/var/www/html/client/xml/Output_Details.xml' );
	$path = '/FTPdata/'.$publisher;
	$xpath = $xml->xpath( $path );
	
	
	if( count( $xpath ) == 0 ) {
		$pub = $xml->addChild( $publisher );
		$in = $pub->addChild( 'Inward' );
		$in->addChild( 'Address', '' );
		$in->addChild( 'Port', '' );
		$in->addChild( 'Passive', '' );
		$in->addChild( 'Binary', '' );
		$in->addChild( 'Login', '' );
		$in->addChild( 'Pass', '' );
		$in->addChild( 'Path', '' );
		$out = $pub->addChild( 'Outward' );
		$content = $out->addChild( 'Content' );
		$final = $out->addChild( 'Final' );
		$out->addChild( 'Softproof' );
		$archive = $out->addChild( 'Archive' );
		$archive->addChild( 'Address', '' );
		$archive->addChild( 'Port', '' );
		$archive->addChild( 'Passive', '' );
		$archive->addChild( 'Binary', '' );
		$archive->addChild( 'Login', '' );
		$archive->addChild( 'Pass', '' );
		$archive->addChild( 'Path', '' );
	
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
	
		file_put_contents( '/var/www/html/client/xml/Output_Details.xml', $dom->saveXML() );
		}
	}
?>