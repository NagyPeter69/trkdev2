<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');
include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );
include_once( '../../engine/xml_handler.php' );

$user = sql_get( "accounts", "id='".$_SESSION['intra_user']."'", "*" );

if( $_GET["sub"] == "sendhandout" ) {
	$error = array();
	parse_str($_POST["settings"], $_POST["settings"]);
	include_once('../lang/'.$_POST["settings"]['lang'].'.php');
	
	$mailcim = $_POST["settings"]['mail'];

	if( strpos( $mailcim, "<" ) !== FALSE && strpos( $mailcim, ">" ) ) {
		$start = strpos( $mailcim, "<" ) + 1;
		$end = strpos( $mailcim, ">" ) - $start;
		
		$mailcim = substr( $mailcim, $start, $end );
		error_log( $mailcim );
		}
	
	if( $mailcim == "" or !filter_var( $mailcim, FILTER_VALIDATE_EMAIL ) ) { $error[] = "mail"; }
	if( count( $error ) == 0 ) {
		$hash = md5( "handout-".$mailcim."-".time() );
		
		$names = array( "userid", "handoutid", "hash", "time", "email" );
		$values = array( $user[0][0], $_POST["settings"]['handoutid'], $hash, time(), $mailcim );
		sql_add( "flatplan_handout_hotlink", $names, $values );

		$handout = sql_aget( "flatplan_handout", "id='".$_POST["settings"]['handoutid']."'", "*" );
		$job = sql_aget( "publications", "id='".$handout[0]["pub_id"]."'", "*" );
		$magazine = sql_aget( "magazines", "id='".$job[0]["magazine_id"]."'", "*" );
		$client = sql_aget( "publishers", "id='".$user[0][4]."'", "*" );
		
		$externalContent = "".URL."/html/client";

		if( $_POST["settings"]['htype'] == "downloadpdf" ) {
			$link = "<a href='".PROTOCOL."".$externalContent."/handout.php?hash=".$hash."'>".PROTOCOL."".$externalContent."/handout.php?hash=".$hash."</a>";
			
			$subject = sprintf( $lang["hotlinks"][ "handout_title" ], $client[0]["name"] );
			$to = $mailcim."|".$mailcim;
			$text = sprintf( $lang["hotlinks"][ "handout_text" ], $mailcim, $user[0][7], $link );
			sendMail_( $subject, $text, $to, "" );			
			}
			
		if( $_POST["settings"]['htype'] == "flipbooklink" ) {
			$link = "<a href='".PROTOCOL."".$externalContent."/book.php?file=".$handout[0]["filename"]."&id=".$handout[0]["pub_id"]."'>".PROTOCOL."".$externalContent."/book.php?file=".$handout[0]["filename"]."&id=".$handout[0]["pub_id"]."</a>";
			
			$subject = sprintf( $lang["hotlinks"][ "handout_title" ], $client[0]["name"] );
			$to = $mailcim."|".$mailcim;
			$text = sprintf( $lang["hotlinks"][ "flipbook_text" ], $mailcim, $user[0][7], $link );
			sendMail_( $subject, $text, $to, "" );				
			}
			

		}
		
	$result = array( $error );
	}

if( $_GET["sub"] == "sendhotlink" ) {
	include_once('../lang/'.( $user[0][17] != "" ? $user[0][17] : "en" ).'.php');
	
	$error = array();
	parse_str($_POST["settings"], $_POST["settings"]);
	
	$expire = strtotime( $_POST["settings"]['expire'] );
	$mailcim = $_POST["settings"]['mail'];
	if( strpos( $mailcim, "<" ) !== FALSE && strpos( $mailcim, ">" ) ) {
		$start = strpos( $mailcim, "<" ) + 1;
		$end = strpos( $mailcim, ">" ) - $start;
		
		$mailcim = substr( $mailcim, $start, $end );
		error_log( $mailcim );
		}
	
	if( $mailcim == "" or !filter_var( $mailcim, FILTER_VALIDATE_EMAIL ) ) { $error[] = "mail"; }
	if( $_POST["settings"]['expire'] == "" or $expire < time() ) { $error[] = "expire"; }
	
	if( count( $error ) == 0 ) {
		$job = sql_aget( "publications", "id='".$_GET["job"]."'", "*" );
		$magazine = sql_aget( "magazines", "id='".$job[0]["magazine_id"]."'", "*" );
		
		$hash = md5( "hotlink-".$mailcim."-".time() );
		$rights = explode( "+", $_POST["settings"]['right'] );
		
		$names = array( "publication", "fp_type", "hashtag", "pages", "comment", "accept", "time_expire", "spread", "note", "email", "lang", "creator", "state", "downloadable" );
		$values = array( $job[0]["id"], $_GET["opt"], $hash, implode( "|", $_POST["data"] ), $rights[0], $rights[1], $expire, $_POST["settings"]['spreads'], $_POST["settings"]['note'], $mailcim, $_POST["settings"]['lang'], $user[0][0], implode( "|", $_POST["state"] ), $_POST["settings"]['downloadable'] );
		sql_add( "hotlinks", $names, $values );

		$names = array( "job_id", "action", "info", "time" );
		$values = array( $_GET["job"], "hotlink_sent", $mailcim, time() );
		sql_add( "hotlinks_log", $names, $values );
	
		//$externalContent = file_get_contents('".PROTOCOL."ipecho.net/plain');
		$externalContent = "".URL."/html/client";
		//preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $externalContent, $m);
		//$externalIp = $m[0];
		
		$check = sql_get( "ad_hoc_users", "email='".$mailcim."'", "name" );
		$link = "<a href='".PROTOCOL."".$externalContent."/index.php?hash=".$hash."&visitor=1'>".PROTOCOL."".$externalContent."/index.php?hash=".$hash."&visitor=1</a>";
		
		include_once('../lang/'.$_POST["settings"]['lang'].'.php');
		$subject = sprintf( $lang["hotlinks"][ "mail_title" ], $magazine[0]["name"]." ".$job[0]["code"] );
		if( empty( $_POST["settings"]['note'] ) ) {
			$text = sprintf( $lang["hotlinks"][ "mail_text_nonote" ], $lang["hotlinks"]["invocation"], $link, nl2br($_POST["settings"]['note']) );
			}
		else {
			$text = sprintf( $lang["hotlinks"][ "mail_text" ], $lang["hotlinks"]["invocation"], $link, nl2br($_POST["settings"]['note']) );
			}
			
 		$to = ($check[0][0] != "" ? $check[0][0] : $mailcim )."|".$mailcim;
 		
		sendMail_( $subject, $text, $to, "" );
		}

	$result = array( $error );
	}
	

print json_encode( $result );
	
?>