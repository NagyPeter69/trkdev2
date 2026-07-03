<?PHP
session_start();
include_once( '../../engine/connect.php' );
include_once('../../engine/engine.php');
include_once( TRKPATH."/engine/switchAPI.php" );

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