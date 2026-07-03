<?PHP
session_start();
header('Content-Type: text/html; charset=utf-8');

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

if( $_GET['op'] == 'removefile' ) {
	$file = sql_aget( "flatplan_files", "id='".$_GET["id"]."'", "*" );
	
	if( !empty( $file[0]["id"] ) ) {
		@unlink( $file[0]["path"]."/".$file[0]["filename"] );
		sql_delete( "flatplan_files", "id='".$file[0]["id"]."'" );
		}
	}
	
if( $_GET['op'] == 'loaduploadedfiles' ) {
	$txt = "";
	$planner = sql_aget( "flatplan_planner", "id='".$_GET["plannerid"]."'", "*" );
	$files = sql_aget( "flatplan_files", "articlename='".$planner[0]["name"]."' ORDER BY 'origname' ASC", "*" );
	
	for( $i = 0; $i < count( $files ); $i++ ) {
		$txt .= "<tr>";
			$txt .= "<td colspan='2' style='padding-left: 0px; font-size: 14px; padding-top: 2px; color: #CCC'>".$files[$i]["origname"]."</td>";
			$txt .= "<td align='right' style='padding-left: 0px; padding-right: 3px; padding-top: 2px; font-size: 16px;'>
						<span onclick='fpfiledownload( \"".$files[$i]["id"]."\" )' style='cursor: pointer;'><i class='fas fa-download'></i></span>
						<span onclick='fpfileremove( \"".$files[$i]["id"]."\", \"".$files[$i]["origname"]."\" )' style='cursor: pointer;'><i class='far fa-times-circle' style='color: #D22A33;'></i></span>
					 </td>";
			$txt .= "</tr>";
		}
		
	$result = $txt;
	}

if( $_GET['op'] == 'getassets' ) {
	$pub = sql_aget( "publications", "id='".$_GET["pubid"]."'", "*" );
	$mag = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
	$fp = sql_aget( "flatplan_planner", "id='".$_GET["plannerid"]."'", "*" );	
	
	$result = array( $fp[0]["name"], $mag[0]["code"], $pub[0]["code"], $fp[0]["name"] );
	}

if( $_GET['op'] == 'uploaded' ) {
	$pub = sql_aget( "publications", "id='".$_POST["pubid"]."'", "*" );
	
	$temp = explode( ".", $_FILES[0]["name"] );
	$origname = $_FILES[0]["name"];
	$name = $pub[0]["id"]."-".time().".".end( $temp );
		
	if ( move_uploaded_file( $_FILES[0]["tmp_name"], FPUPLOAD_PATH.'/'.$name ) ) {
		$names = array( "userid", "pubid", "articlename", "date", "path", "filename", "origname" );
		$values = array( $_SESSION['intra_user'], $pub[0]["id"], $_POST["article"], time(), FPUPLOAD_PATH, $name, $origname );		
		sql_add( "flatplan_files", $names, $values );
		
		$response = "SUCCESS|ok";
		}
	else {
		$response = "FAIL|UploadFailed";
		}
	}

print json_encode( $result );
	
?>