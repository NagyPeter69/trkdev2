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
error_log("handlerben");
$status = $_POST["result"];
$publisher = $_POST["client"];
$code = $_POST["jobCode"];
$name = $_POST["description"];
$issue = $_POST["issue"];
//$remark = $_POST[""];
$pageNum =  $_POST["pageNum"];

$xml2 = simplexml_load_file( XMLPATH.'/pmd.xml' );
$xpath = $xml2->xpath('/Publications');
foreach($xpath as $temp) {
	for( $x = 0; $x < count( $temp->Item ); $x++ ) {
		if( $temp->Item[$x]->Code == $magazine[0][3] )
			break;
		}
	}
	
$publisher = sql_get( 'publishers', 'name="'.$publisher.'"', '*' );
$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
$current = $_POST["issue"];
$pub = sql_get( 'publications', 'code="'.$current.'" AND magazine_id="'.$magazine[0][0].'"', '*' );	
$ad = sql_get( 'ads', 'publisher="'.$publisher[0][0].'" AND pub_id="'.$pub[0][0].'" AND name="'.$name.'"', '*' );

error_log( $status );

if( $status == "error") {
	error_log( "Hirdetés feltöltés sikertelen: ".$_POST["result_description"] );	
	
	sql_update( "ads", "uploaded='error'", "id='".$ad[0][0]."'" );
	}

if( $status == "success") {
	if( $ad[0][0] != '' ) {
		$res = $_POST["result"];
	
		if( $res == 'success' ) {
			$check = explode( '-', $pageNum );
			for( $a = 0; $a < count( $check ); $a++ ) {
				$sql = sql_get( 'ads', 'pub_id="'.$ad[0][1].'" AND uploaded LIKE "%'.$check[$a].'%"', '*' );
				for( $b = 0; $b < count( $sql ); $b++ ) {
					if( $ad[0][3] != "2/1" && $ad[0][3] != "1/1" && $sql[$b][3] != "2/1" && $sql[$b][3] != "1/1" ) {
						$p_ads = sql_get( 'partial_ads', 'ads_id="'.$ad[0][0].'" ORDER BY `date` DESC', '*' );
						$p = sql_get( 'partial_ads', 'ads_id="'.$sql[$b][0].'" ORDER BY `date` DESC', '*' );
						for( $c = 0; $c < count( $p ); $c++ ) {
							if( $p_ads[0][2] == $p[$c][2] ) {
								sql_update( 'ads', 'uploaded=""', 'id=\''.$sql[$b][0].'\'' );
								sql_delete( 'partial_ads', 'id="'.$p[$c][0].'"' );
								}
							}
						}
					else {						
						sql_update( 'ads', 'uploaded=""', 'id=\''.$sql[$b][0].'\'' );
						if( $sql[$b][3] != "2/1" && $sql[$b][3] != "1/1" ) {
							$p_ads = sql_get( 'partial_ads', 'ads_id="'.$sql[$b][0].'"', 'id' );
							for( $c = 0; $c < count( $p_ads ); $c++ ) {
								sql_delete( 'partial_ads', 'id="'.$p_ads[$c][0].'"' );
								}
							}
						}
					}
				}
			
			if( $ad[0][3] != "2/1" && $ad[0][3] != "1/1" ) {
				$p_ads = sql_get( 'partial_ads', 'ads_id="'.$ad[0][0].'" ORDER BY `date` ASC', 'id' );
				for( $c = 0; $c < count( $p_ads )-1; $c++ ) {
					sql_delete( 'partial_ads', 'id="'.$p_ads[$c][0].'"' );
					}						
				}
			$names = array( 'uploaded' );
			$values = array( $pageNum );
			if( $ad[0][6] == 0 ) {
				$names[] = "status";
				$values[] = "4";
				}
			}
		else {
			$names = array( 'uploaded', 'reason' );
			$values = array( 'error', $_POST["result_description"] );					
			}		
			
		$command = '';
		for( $i = 0; $i < count( $names ); $i++ ) {
			$command .= $names[$i].'=\''.$values[$i].'\'';
			if( $i < count( $names )-1 ) {
				$command .= ', ';
				}
			}
	
		if( sql_update( 'ads', $command, 'id=\''.$ad[0][0].'\'' ) ) {}
		
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status', 'info' );
		$values = array( $_SESSION['intra_user'], 'uploadAD', $publisher[0][0], $magazine[0][0], $current, $pageNum, time(), $res, $ad[0][2] );
		sql_add( 'action_log', $names, $values );					
		}
	}
?>