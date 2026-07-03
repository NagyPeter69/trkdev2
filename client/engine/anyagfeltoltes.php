<?PHP
session_start();

include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');
include_once( 'switchAPI.php' );
include_once('../lang/en.php');

include_once( '../../engine/xml_handler.php' );

$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}

$target = '../anyagatadas';
$result = "";

for( $i = 0; $i < count( $_FILES["file"]['name'] ); $i++ ) {
	$doc_name = $_FILES["file"]['name'][$i];
	$tmp_name = $_FILES["file"]["tmp_name"][$i];
	
	error_log( $tmp_name ." => ".$target."/".$doc_name );
	
	if ( move_uploaded_file($tmp_name, $target.'/'.$doc_name ) ) {
		$array = array(
			"Code" => $_POST["code"],
			"User" => $user[0][7],
			);	
			
		$file = array( 
			"name" => $doc_name,
			"path" => str_replace( "../", "", $target ),
			);
		
		$response = SwitchAnyagSend( $array, $file );
		
		unlink( $target.'/'.$doc_name );
		$result = "ok";
		}
	else {
		$result = "error";
		}
	}
	
print json_encode( $result );
	
?>