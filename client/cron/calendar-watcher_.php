<?PHP
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');
require( '../../engine/connect.php' );
require( '../../engine/engine.php' );
require( '../../engine/xml_handler.php' );

$settings = array();
$s = sql_aget( 'calendar_settings', '1 order by id ASC', '*' );
for( $i = 0; $i < count( $s ); $i++ ) {
	$settings[ $s[$i]["name"] ] = $s[$i]["value"];
	}

echo "<pre>";

$groups = sql_aget( "user_groups", "task_lists='1'", "*" );
$temp = array();
if( count( $groups ) > 1 ) {
	for( $g = 0; $g < count( $groups ); $g++ ) {
		$temp[] = "`group` = '".$groups[$g]["id"]."'";
		}
	
	$grp = "( ".implode( " OR ", $temp )." )";
	}
else {
	$grp = "`group` = '".$groups[0]["id"]."'";
	}

$watch = array( "order_paper", "order_printing", "define_issue", "product_remind" ); 
for( $w = 0; $w < count( $watch ); $w++ ) {
	if( $settings[ $watch[$w] ] == "-1" ) {
		$date = date( "Y-m-d", strtotime( "+".$settings[ $watch[$w] ]." day" ) );
		
		if( $watch[$w] == "product_remind" ) {
			$search = sql_aget( "calendar_post", "salesDay='".$date."'", "*" );
			}
		else {
			$search = sql_aget( "calendar_post", "printDay='".$date."'", "*" );
			}

		for( $s = 0; $s < count( $search ); $s++ ) {
			$accounts = sql_aget( "accounts", "publisher='".$search[$s]["publisher_id"]."' AND ".$grp, "*" );
			$accounts = sql_aget( "accounts", "publisher='".$search[$s]["publisher_id"]."'", "*" );
			$sz = 1;
			for( $a = 0; $a < count( $accounts ); $a++ ) {
				$mags = explode( ",", $accounts[$a]["showMagazines"] );
				
				if( in_array( $search[$s]["magazine_id"], $mags ) ) {
					$magazine = sql_aget( "magazines", "id='".$search[$s]["magazine_id"]."'", "*" );
					//E-mail beállítása küldésre.
					switch( $watch[$w] ) {
						case "product_remind":
							$text = "Kedves ".$accounts[$a]["full_name"].",<br>
							<br>
							Kérjük ne felejtse el megrendelni a nyomdában a(z) ".$search[$s]["specificName"]." nevű termékhez a nyomtatást.<br>
							<br>
							Üdvözlettel:<br>
							Tracker
							";
							$subject = "Nyomtatás rendelése a ".$magazine[0]["name"]." ".$search[$s]["code"]." számú termékhez";
							break;
							
						case "order_paper":
							$text = "Kedves ".$accounts[$a]["full_name"].",<br>
							<br>
							Kérjük ne felejtse el megrendelni a szükséges papír mennyiséget a ".$magazine[0]["name"]." ".$search[$s]["code"]." számú kiadványhoz.<br>
							<br>
							Üdvözlettel:<br>
							Tracker
							";
							$subject = "Papír rendelése a ".$magazine[0]["name"]." ".$search[$s]["code"]." számú kiadványhoz";
							break;
							
						case "order_printing":
							$text = "Kedves ".$accounts[$a]["full_name"].",<br>
							<br>
							Kérjük ne felejtse el megrendelni a nyomdában a ".$magazine[0]["name"]." ".$search[$s]["code"]." számú kiadványhoz a nyomtatást.<br>
							<br>
							Üdvözlettel:<br>
							Tracker
							";
							$subject = "Nyomtatás rendelése a ".$magazine[0]["name"]." ".$search[$s]["code"]." számú kiadványhoz";
							break;
						
						case "define_issue":
							$text = "Kedves ".$accounts[$a]["full_name"].",<br>
							<br>
							Kérjük ne felejtse el létrehozni a ".$magazine[0]["name"]." ".$search[$s]["code"]." számú kiadványt a rendszerben.<br>
							<br>
							Üdvözlettel:<br>
							Tracker
							";
							$subject = $magazine[0]["name"]." ".$search[$s]["code"]." számú kiadvány létrehozása a rendszerben.";
							break;												
						}
						
					$to = "peter.tamas@colorcom.hu|peter.tamas@colorcom.hu";
					sendMail( $subject, $text, $to, "" );
					/*$to = "peter@colorcom.hu|peter@colorcom.hu";
					sendMail( $subject, $text, $to, "" );*/
					}
				}
			}
		}
	}
?>