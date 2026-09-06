<?PHP

// This is an inbound Switch webhook, not a browser session - Switch has no
// Tracker login, so the equivalent check here is verifying the request
// actually comes from the known Switch host rather than a session (see
// client/plugins/pubsApply.php's 2026-09-05 fix for the session-based
// version used everywhere a browser session applies). Confirmed live this
// session: before this, ANYONE reaching this URL could forge a Switch
// event (fake page approvals, fake uploads) with no verification at all.
// 2026-09-05 trk.colorcom.hu cutover: the gateway at 10.10.30.250 now NATs
// all inbound traffic (Switch's included) to its own address before it
// reaches this box - Switch's real 192.168.1.8 is no longer visible here,
// and there's no X-Forwarded-For to recover it (confirmed by direct test:
// a curl from the Switch host and Switch's own callback both arrived as
// 10.10.30.250, same as ordinary browser traffic). Checking for the
// gateway's address is accepted as a deliberately weak stand-in for now -
// it does NOT actually distinguish Switch from any other request that
// reaches this server. Revisit once the network side stops masquerading
// Switch's traffic or adds a forwarding header.
if( ( $_SERVER['REMOTE_ADDR'] ?? '' ) !== '10.10.30.250' ) {
	http_response_code( 403 );
	exit;
	}
$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];
$remark = $_POST["remark"];
$description = $_POST["description"];	

$name = nameCalculator( $_POST );
$found = 0;

$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
$p_code = $p_id[0][3];

$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'"', '*' );
for( $x = 0; $x < count( $packages ); $x++ ) {	
	if( strtolower( $packages[$x][2] ) == strtolower( $name ) ) {
		$found = 1;
		$target = $packages[$x][0];
		break;
		}	
	else {
		$found = 0;
		}
	}



if( $found == 0 ) {
	for( $x = 0; $x < count( $packages ); $x++ ) {	
		if( preg_match( "/".$packages[$x][2]."/i", $name ) && !strstr( $name, "_" ) ) {
			$found = 1;
			$target = $packages[$x][0];
			break;
			}
		else {
			$found = 0;
			}
		}
	}

if( $found == 0 ) {
	for( $x = 0; $x < count( $packages ); $x++ ) {
		$tar = explode( "_", $packages[$x][2] );
		foreach( $tar as $t ) {
			if( preg_match( "/".$name."/i", $t ) ) {
				$found = 1;
				$target = $packages[$x][0];
				break 2;
				}
			}
		}
	}
	
if( $found == 1 ) {
	$names = array( 'status', 'status_changed' );
	$values = array( 1, time() );

	$command = '';
	for( $a = 0; $a < count( $names ); $a++ ) {
		$command .= $names[$a].'=\''.$values[$a].'\'';

		if( $a < count( $names )-1 ) {
			$command .= ', ';
			}
		}
		
	$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
	$values = array( '', 'backArticle', $p_id[0][1], $p_id[0][2], $p_id[0][10], $name, time(), '' );
	sql_add( 'action_log', $names, $values );

	sql_update( 'packages', $command, 'id=\''.$target.'\'' );
	}
	
?>