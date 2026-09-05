<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');
include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );
include_once( '../../engine/xml_handler.php' );

$user = sql_get( "accounts", "id='".( $_SESSION['intra_user'] ?? '' )."'", "*" );
include_once('../lang/'.( $user[0][17] != "" ? $user[0][17] : "en" ).'.php');

// See client/plugins/pubsApply.php's 2026-09-05 fix - none of this file's
// sub== handlers checked authentication before running. Same fix: one
// gate before any sub is dispatched.
if( empty( $user[0][0] ) ) {
	print json_encode( array( array( "Unauthorized" ) ) );
	exit;
	}

if( $_GET["sub"] == "movePages" ) {
	parse_str($_POST["settings"], $_POST );
	$txt = $_POST["sequence"];
	
	for( $i = 0; $i < $_POST["sequence"]; $i++ ) {
		sql_update( "flatplan_planner", "pos='".( $_POST["starting"] + $i )."'", "pub_id='".$_POST["pubid"]."' AND pos='".( $_POST["oldstart"] + $i )."'" );
		}
	
	
	$result = array( $txt );
	}

if( $_GET["sub"] == "saveMixed" ) {
	parse_str($_POST["settings"][0], $temp );
	$slots = explode("-", $temp['m_slots'] );
	if( count( $slots ) > 1 ) {
		for( $s = $slots[0]; $s <= $slots[1]; $s++ ) {
			$pos[] = intval( $s );
			}
		}
	else {
		$pos[] = $slots[0];
		}
	for( $i = 0; $i < count( $pos ); $i++ ) {
		$check = sql_aget( "flatplan_planner", "pub_id='".$temp['m_pubid']."' AND pos='".$pos[$i]."'", "*" );
		for( $c = 0; $c < count( $check ); $c++ ) {
			sql_delete( "flatplan_planner", "id='".$check[$c]["id"]."'" );
			}
		}
	
				
	for( $d = 0; $d < count( $_POST["settings"] ); $d++ ) {
		$data = "";
		parse_str($_POST["settings"][$d], $data );
		
		$slots = explode("-", $data['m_slots'] );
		$pos = array();

		if( count( $slots ) > 1 ) {
			for( $s = $slots[0]; $s <= $slots[1]; $s++ ) {
				$pos[] = intval( $s );
				}
			}
		else {
			$pos[] = $slots[0];
			}
			
		for( $i = 0; $i < count( $pos ); $i++ ) {
			$time = time();
			$names = array( "pub_id", "user_id", "date", "name", "type", "pos", "atype", "workerID", "workerName", "tspent", "remark", "status", "template", "position", "mixed" );
			$user = sql_aget( "accounts", "id='".$data["workerID"]."'", "*" );
			$values = array( $data['m_pubid'], $user[0][0], $time, $data['m_name'], $data['m_content_type'], $pos[$i], $data['atype'], $user[0]["id"], $user[0]["full_name"], $data['tspent'], addslashes( $data['remark'] ), $data['m_status'], $data['m_template'], $data['m_position'], "1" );
			
			$names[] = "text";
			$values[] = ( $data['r_text'] == "1" ) ? "1" : "0";

			$names[] = "image";
			$values[] = ( $data['r_image'] == "1" ) ? "1" : "0";
			
			$names[] = "other";
			$values[] = ( $data['r_other'] == "1" ) ? "1" : "0";				

			$names[] = "have_text";
			$values[] = ( $data['have_text'] == "1" ) ? "1" : "0";

			$names[] = "have_image";
			$values[] = ( $data['have_image'] == "1" ) ? "1" : "0";
			
			$names[] = "have_other";
			$values[] = ( $data['have_other'] == "1" ) ? "1" : "0";
			sql_add( "flatplan_planner", $names, $values );
			}
		}

	$debug = $data;
	$result = array( $debug );
	}

if( $_GET["sub"] == "modArticle" ) {
	$error = array();
	parse_str($_POST["settings"], $_POST["settings"]);
	parse_str($_POST["data"], $_POST["data"]);
	
	$time = time();
	
	if( empty( $_POST["settings"]["name"] ) ) {
		$error[] = "name";
		}
	
	if( $_POST["settings"]["content_type"] != "ad" ) {
		if( empty( $_POST["settings"]["atype"] ) ) {
			$error[] = "atype";
			}
		}
	
	if( count( $error ) == 0 ) {
		$slots = explode("-", $_POST["settings"]['slots'] );
		$pos = array();
		
		if( $_POST["settings"]["mod"] == "create") {
			if( count( $slots ) > 1 ) {
				for( $s = $slots[0]; $s <= $slots[1]; $s++ ) {
					$pos[] = intval( $s );
					}
				}
			else {
				$pos[] = $slots[0];
				}
			}
		
		if( $_POST["settings"]["mod"] == "modify") {
			for( $p = $_POST["settings"]['start']; $p <= $_POST["settings"]['end']; $p++ ) {
				$pos[] = $p;
				}
			}

		$check = sql_aget( "flatplan_planner", "pub_id='".$_POST["settings"]['pubid']."' AND name='".$_POST["settings"]['name']."'", "*" );
		for( $c = 0; $c < count( $check ); $c++ ) {
			sql_delete( "flatplan_planner", "id='".$check[$c]["id"]."'" );
			}
			
		for( $i = 0; $i < count( $pos ); $i++ ) {				
			$names = array( "pub_id", "user_id", "date", "name", "type", "pos", "atype", "workerID", "workerName", "tspent", "remark", "status" );
			$time = time();
			$user = sql_aget( "accounts", "id='".$_POST["settings"]["workerID"]."'", "*" );
			
			$values = array( $_POST["settings"]['pubid'], $user[0][0], $time, $_POST["settings"]['name'], $_POST["settings"]['content_type'], $pos[$i], $_POST["settings"]['atype'], $user[0]["id"], $user[0]["full_name"], $_POST["settings"]['tspent'], addslashes( $_POST["settings"]['remark'] ), $_POST["settings"]['a_status'] );
			
			$names[] = "text";
			$values[] = ( $_POST["settings"]['r_text'] == "1" ) ? "1" : "0";

			$names[] = "image";
			$values[] = ( $_POST["settings"]['r_image'] == "1" ) ? "1" : "0";
			
			$names[] = "other";
			$values[] = ( $_POST["settings"]['r_other'] == "1" ) ? "1" : "0";				

			$names[] = "have_text";
			$values[] = ( $_POST["settings"]['have_text'] == "1" ) ? "1" : "0";

			$names[] = "have_image";
			$values[] = ( $_POST["settings"]['have_image'] == "1" ) ? "1" : "0";
			
			$names[] = "have_other";
			$values[] = ( $_POST["settings"]['have_other'] == "1" ) ? "1" : "0";
			
			sql_add( "flatplan_planner", $names, $values );
			}
			
		
		}

	$result = array( $error );
	}

if( $_GET["sub"] == "removeArticle" ) {
	parse_str($_POST["settings"], $_POST["settings"]);
	parse_str($_POST["data"], $_POST["data"]);
	
	for( $i = 0; $i < count( $_POST["data"] ); $i++ ) {
		sql_delete( "flatplan_planner", "pub_id='".$_POST["settings"]['pubid']."' AND pos='".$_POST["data"][$i]."'" );
		}

	}

if( $_GET["sub"] == "saveArticle" ) {
	$error = array();
	parse_str($_POST["settings"], $_POST["settings"]);
	parse_str($_POST["data"], $_POST["data"]);
	
	if( empty( $_POST["settings"]["name"] ) ) {
		$error[] = "name";
		}

	if( empty( $_POST["settings"]["atype"] ) ) {
		$error[] = "atype";
		}
	
	if( count( $error ) == 0 ) {	
		$check = sql_aget( "flatplan_planner", "pub_id='".$_POST["settings"]['pubid']."' AND name='".$_POST["settings"]['name']."'", "*" );
		if( empty( $check[0]["id"] ) ) {
			$names = array( "pub_id", "user_id", "date", "name", "type", "pos", "atype", "remark" );
			$time = time();
			for( $i = 0; $i < count( $_POST["data"] ); $i++ ) {
				$values = array( $_POST["settings"]['pubid'], $user[0][0], $time, $_POST["settings"]['name'], $_POST["settings"]['type'], $_POST["data"][$i], $_POST["settings"]['atype'], "" );
				sql_add( "flatplan_planner", $names, $values );
				}
			}
		else {
			$error[] = "name";
			}
		}

	$result = array( $error );
	}
	

print json_encode( $result );
	
?>