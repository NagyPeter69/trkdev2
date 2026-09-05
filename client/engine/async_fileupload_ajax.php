<?PHP
session_start();
include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');
include_once( TRKPATH."/engine/switchAPI.php" );

// This file's actual dispatch call is already commented out below (line 24,
// pre-existing - not something this fix changes), so nothing currently
// happens on an unauthenticated request either way. Gating anyway, cheaply,
// in case that line is ever re-enabled without this being reconsidered -
// see client/plugins/pubsApply.php's 2026-09-05 fix.
if( empty( $_SESSION['intra_user'] ) ) {
	print json_encode( array( array( "Unauthorized" ) ) );
	exit;
	}

$_FILES["file"]['name'] = letter_change3( $_FILES["file"]['name'] );
$tmp_name = $_FILES["file"]["tmp_name"];
$target = TRKPATH.'/uploads/uploadPack';

$data = array(
	"tmp_name" => $tmp_name,
	"path" => $target,
	"filename" => $_FILES["file"]['name'],
	"type" => $_POST["type"],
	"color" => $_POST["color"],
	"jobid" => $_POST["jobid"],
	"jtype" => $_POST["jtype"],
	"part" => $_POST["part"],
	"user" => $_SESSION['intra_user'],
	);

$url = "http://trkdev.colorcom.hu/client/engine/fileupload_ajax.php";
//systemCurl( $url, $data, $headers=null, $check_ssl=true);	
print json_encode( $result );
	
?>