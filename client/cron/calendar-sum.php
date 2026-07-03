<?PHP
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');
require( '../../engine/connect.php' );
require( '../../engine/engine.php' );
require( '../../engine/xml_handler.php' );

$pubs = sql_aget( "calendar_post", "1 group by publisher_id", "publisher_id" );
echo "<pre>";

$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
$xpath = $xml->xpath('/Publications');

for( $p = 0; $p < count( $pubs ); $p++ ) {
	$accounts = sql_aget( "accounts", "publisher='".$pubs[$p]["publisher_id"]."'", "*" );
	//$accounts = sql_aget( "accounts", "id='1'", "*" );
	
	for( $a = 0; $a < count( $accounts ); $a++ ) {
		$mags = explode( ",", $accounts[$a]["showMagazines"] );
		$txt = "";
		
		$change = "";		
		for( $m = 0; $m < count( $mags ); $m++ ) {
			$magazines = sql_aget( "magazines", "id='".$mags[$m]."'", "*" );

			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $magazines[0]["code"] )
						break;
					}
				}	
			
			$pmdmails = (string) $xml->Item[$x]->Mails;
			$pmdmails = explode( ";", $pmdmails );
			
			if( in_array( $accounts[$a]["email"], $pmdmails ) ) {	
				$log = sql_aget( "action_log", "magazine='".$magazines[0]["name"]."' AND status='' AND ( action='removedfromcalendar' OR action='modifycalendar' OR action='addtocalendar' ) ORDER BY date ASC", "*" );
				for( $l = 0; $l < count( $log ); $l++ ) {
					switch( $log[$l]["action"] ) {
						case "addtocalendar":
							$change .= date( "Y.m.d. H:i:s", $log[$l]["date"] ).": Új megjelenés lett létrehozva ".$log[$l]["magazine"]." ".$log[$l]["issue"]." névvel<br>";
							break;
							
						case "removedfromcalendar":
							$change .= date( "Y.m.d. H:i:s", $log[$l]["date"] ).": A következő megjelenés törölve lett: ".$log[$l]["magazine"]." ".$log[$l]["issue"]."<br>";
							break;
							
						case "modifycalendar":
							switch( $log[$l]["info"] ) {
								case "printDay":
									$change .= date( "Y.m.d. H:i:s", $log[$l]["date"] ).": A ".$log[$l]["magazine"]." ".$log[$l]["issue"]." nevű megjelenés nyomtatási dátuma módosítva lett (".$log[$l]["target"]."). <br>";
									break;
									
								case "salesDay":
									$change .= date( "Y.m.d. H:i:s", $log[$l]["date"] ).": A ".$log[$l]["magazine"]." ".$log[$l]["issue"]." nevű megjelenés eladási dátuma módosítva lett (".$log[$l]["target"]."). <br>";								break;
								}
							break;
						}
					}
				}
			else {
				$log = array();
				}

			if( count( $log ) > 0 ) {
				$change .= "<br>";
				}
			}
		
		if( !empty( $change ) ) {
			$txt = "Kedves ".$accounts[$a]["full_name"]."!<br>
<br>
Az elmúlt időszakban a következő változás(ok) történtek a Calendar-ban:<br>
<br>
".$change."
<br>
Üdvözlettel:<br>
Tracker
";

			$subject = "Tracker Calendar összesítés";
			$to = $accounts[$a]["full_name"]."|".$accounts[$a]["email"];
			sendMail( $subject, $txt, $to, "" );

			//$to = "peter@colorcom.hu|peter@colorcom.hu";
			//sendMail( $subject, $txt, $to, "" );
			}	
		}
	
	$log = sql_aget( "action_log", "publisher='".$pubs[$p]["publisher_id"]."' AND status='' AND ( action='removedfromcalendar' OR action='modifycalendar' OR action='addtocalendar' ) ORDER BY date ASC", "*" );
	for( $l = 0; $l < count( $log ); $l++ ) {
		sql_update( "action_log", "status='collected'", "id='".$log[$l]["id"]."'" );
		}
	}
	
?>