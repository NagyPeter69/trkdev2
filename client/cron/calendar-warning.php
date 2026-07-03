<?PHP
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');
require( '../../engine/connect.php' );
require( '../../engine/engine.php' );
require( '../../engine/xml_handler.php' );

$time = strtotime( "+30 days" );
$time = date( "Y-m-d", $time );

//$time = "2019-01-17T16:00";
$ugroup = "2";

$pubs = sql_aget( "calendar_post", "printDay='".$time."'", "*" );
for( $i = 0; $i < count( $pubs ); $i++ ) {
	$mag = sql_aget( "magazines", "id='".$pubs[$i]["magazine_id"]."'", "*" );
	$users = sql_aget( "accounts", "`publisher`='".$pubs[$i]["publisher_id"]."' AND `group`='".$ugroup."'", "*" );

	for( $u = 0; $u < count( $users ); $u++ ) {
		//$to = $users[$u]["full_name"]."|".$users[$u]["email"];
		$to = "Péter Tamás|peter.tamas@colorcom.hu";
		$subject = $mag[0]["code"]."_".$pubs[$i]["code"]." produkció definiálás";
		
		$text = "Tisztelt Felhasználónk!<br>
<br>
30 nap múlva kell nyomdába adni a ".$mag[0]["name"]." kiadvány ".$mag[0]["code"]."_".$pubs[$i]["code"]." jelű megjelenését. Kérjük, definiáld a megjelenést a Tracker megfelelő funkciójával (‘Define in Production’).<br>
<br>
Köszönettel:<br>
<br>
Colorcom Media<br>
<br>
Neki ment volna: ".$users[$u]["email"]."
		";
		sendMail( $subject, $text, $to, "" );
		}
	}
	
?>