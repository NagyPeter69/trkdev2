<?php
session_start();
include_once('../engine/connect.php');

include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');

$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}

if( !empty( $user[0][17] ) ) {	
	include_once('lang/'.$user[0][17].'.php');	
	}
else {
	include_once('lang/en.php');	
	}

$mags = explode( ",", $_GET["mags"] );

$_GET["magazines"] = "[".$_GET["mags"]."]";

$printdays = array();
$salesdays = array();

//Előző év decemberi lista
$temp = cal_days_in_month(CAL_GREGORIAN, 12, intval($_GET["year"])-1 );
for( $t = 1; $t <= $temp; $t++ ) {
	$date = ($_GET["year"]-1)."-12-".str_pad( $t, 2, '0', STR_PAD_LEFT );
	
	$orders = sql_aget( "calendar_post JOIN magazines m ON m.id = calendar_post.magazine_id", "calendar_post.printDay='".$date."'", "calendar_post.*, m.color, m.finishtype, m.dayshift" );	
	$printdays[ $date ] = array();
	
	for( $o = 0; $o < count( $orders ); $o++ ) {
		if( in_array( $orders[$o]["magazine_id"] , $mags ) ) {
			if( !$rights["calendar_realdates"] ) {
				$day = date( "D", strtotime( "-".$orders[$o]["dayshift"]." day", strtotime( $date ) ) );
				switch( $day ) {
					case "Sat":
						$corr = 1;
						break;
	
					case "Sun":
						$corr = 2;
						break;
													
					default:
						$corr = 0;
						break;
					}
				$newdate = date( "Y-m-d", strtotime( "-".( $orders[$o]["dayshift"] + $corr )." day", strtotime( $date ) ) );
				
				$printdays[ $newdate ][] = $orders[$o];
				}
			else {
				$printdays[ $date ][] = $orders[$o];
				}	
			}
		}

	$orders = sql_aget( "calendar_post JOIN magazines m ON m.id = calendar_post.magazine_id", "calendar_post.salesDay='".$date."'", "calendar_post.*, m.color, m.finishtype" );	
	$salesdays[ $date ] = array();
	
	for( $o = 0; $o < count( $orders ); $o++ ) {
		if( in_array( $orders[$o]["magazine_id"] , $mags ) ) {
			$salesdays[ $date ][] = $orders[$o];
			}
		}
	
	$specorders = sql_aget( "calendar_post", "salesDay='".$date."' AND publisher_id='".$pub[0]["id"]."' AND magazine_id='0'", "*" );
	for( $o = 0; $o < count( $specorders ); $o++ ) {
		if( in_array( $specorders[$o]["id"], $mags ) ) {
			$tempcolor = substr( $specorders[$o]["code"],4 , -1 );
			$tempcolor = explode( ", ", $tempcolor );
	
			$specorders[$o]["color"] = sprintf("%02x%02x%02x", $tempcolor[0], $tempcolor[1], $tempcolor[2] );
			$specorders[$o]["finishtype"] = "sales";
			$specorders[$o]["code"] = "";
			$salesdays[ $date ][] = $specorders[$o];
			}
		}
	}

//idei évi lista
for( $m = 1; $m <= 12; $m++ ) {
	$temp = cal_days_in_month(CAL_GREGORIAN, $m, intval($_GET["year"]) );
	for( $t = 1; $t <= $temp; $t++ ) {
		$date = ($_GET["year"])."-".str_pad( $m, 2, '0', STR_PAD_LEFT )."-".str_pad( $t, 2, '0', STR_PAD_LEFT );
		
		if( $user[0][29] != "sales" ) {
			$orders = sql_aget( "calendar_post JOIN magazines m ON m.id = calendar_post.magazine_id", "calendar_post.printDay='".$date."'", "calendar_post.*, m.color, m.finishtype, m.dayshift" );	
			$printdays[ $date ] = array();
			
			for( $o = 0; $o < count( $orders ); $o++ ) {
				if( in_array( $orders[$o]["magazine_id"] , $mags ) ) {
					if( !$rights["calendar_realdates"] ) {
						$day = date( "D", strtotime( "-".$orders[$o]["dayshift"]." day", strtotime( $date ) ) );
						switch( $day ) {
							case "Sat":
								$corr = 1;
								break;
	
							case "Sun":
								$corr = 2;
								break;
															
							default:
								$corr = 0;
								break;
							}
						$newdate = date( "Y-m-d", strtotime( "-".( $orders[$o]["dayshift"] + $corr )." day", strtotime( $date ) ) );
						
						$printdays[ $newdate ][] = $orders[$o];
						}
					else {
						$printdays[ $date ][] = $orders[$o];
						}
					}
				}
				
			$specorders = sql_aget( "calendar_post", "printDay='".$date."' AND publisher_id='".$pub[0]["id"]."' AND magazine_id='0'", "*" );
			for( $o = 0; $o < count( $specorders ); $o++ ) {
				if( in_array( $specorders[$o]["id"], $mags ) ) {
					$tempcolor = substr( $specorders[$o]["code"],4 , -1 );
					$tempcolor = explode( ", ", $tempcolor );
			
					$specorders[$o]["color"] = sprintf("%02x%02x%02x", $tempcolor[0], $tempcolor[1], $tempcolor[2] );
					$specorders[$o]["finishtype"] = "sales";
					$specorders[$o]["code"] = "";
					$printdays[ $date ][] = $specorders[$o];
					}
				}
			}
			
		if( $user[0][29] != "print" ) {	
			$orders = sql_aget( "calendar_post JOIN magazines m ON m.id = calendar_post.magazine_id", "calendar_post.salesDay='".$date."'", "calendar_post.*, m.color, m.finishtype" );
			$salesdays[ $date ] = array();
			
			for( $o = 0; $o < count( $orders ); $o++ ) {
				if( in_array( $orders[$o]["magazine_id"] , $mags ) ) {
					$salesdays[ $date ][] = $orders[$o];
					}
				}
				
			$specorders = sql_aget( "calendar_post", "salesDay='".$date."' AND publisher_id='".$pub[0]["id"]."' AND magazine_id='0'", "*" );
			for( $o = 0; $o < count( $specorders ); $o++ ) {
				if( in_array( $specorders[$o]["id"], $mags ) ) {
					$tempcolor = substr( $specorders[$o]["code"],4 , -1 );
					$tempcolor = explode( ", ", $tempcolor );
			
					$specorders[$o]["color"] = sprintf("%02x%02x%02x", $tempcolor[0], $tempcolor[1], $tempcolor[2] );
					$specorders[$o]["finishtype"] = "sales";
					$specorders[$o]["code"] = "";
					$salesdays[ $date ][] = $specorders[$o];
					}
				}				
			}
		}	
	}

/*echo "<pre>";
var_dump( $printdays );
die();*/

$events = sql_aget( "calendar_events", "1 order by start asc", "*" );
$events_array = array();
for( $e = 0; $e <= count( $events ); $e++ ) {
	if( in_array( $events[$e]["magazine_id"] , $mags ) ) {
		$events_array[] = $events[$e];
		}
	}

$magazines = sql_aget( "magazines", "1 order by id asc", "*" );
$magazines_array = array();
for( $e = 0; $e <= count( $magazines ); $e++ ) {
	if( in_array( $magazines[$e]["id"] , $mags ) ) {
		$magazines_array[ $magazines[$e]["id"] ] = $magazines[$e];
		}
	}

$pub = sql_aget( "publishers", "id='".$_GET["pub"]."'", "*" );
$counter = sql_aget( "calendar_counters", "id='".$pub[0]["id"]."'", "*" );
$name = $pub[0]["name"]." ütemezés ".$_GET["year"]." ".( $rights["calendar_realdates"] ? "H" : "" )."".$counter[0]["counter"];

$data = array(
	"fname" => $name,
	"year" => $_GET["year"],
	"magazines" => $_GET["magazines"],
	"printdays" => json_encode( $printdays ),
	"salesdays" => json_encode( $salesdays ),
	"publisher" => $pub[0]["name"],
	"ver" => $_GET["ver"],
	"events" => json_encode( $events_array ),
	"magazines" => json_encode( $magazines_array ),
	"title" => $lang["calendar"]["pdf_title"],
	"gen" => $lang["calendar"]["pdf_gen"],
	);

$headers = array(
	"Content-Type: multipart/form-data",
	"Connection: Keep-Alive",
	);


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://".DYNAIP."/dynAPI/tracker/calendarpdf.php" );
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
curl_setopt($ch, CURLOPT_POST, true );
curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
$response = curl_exec ($ch);
$response = json_decode( $response, true );

$data = explode( ',', $response["pdf"] );
$data = base64_decode( $data[ 1 ] );

header('MIME-Version: 1.0');
header('Content-Type: text/html; charset=utf-8');
header('Expires: Mon, 31 Dec 2012 08:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Content-Type: application/pdf');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: public');
header('Content-Disposition: inline; filename="'.$name.'.pdf"');
echo $data;

?>