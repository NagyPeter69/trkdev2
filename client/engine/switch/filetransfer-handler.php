<?PHP
include( "../../../engine/fileClass.php" );
$file = new file;

if( $_POST["payload"] == "finished" ) {
	$file->switchFinishedUpload( $_POST );
	}

if( $_POST["payload"] == "cancelled" ) {
	if( !empty( $_POST["id"] ) ) {
		$file->cancelledID( $_POST["id"] );
		echo "Success";
		}
	else {
		echo "Missing ID";
		}
	}

if( $_POST["payload"] == "getID" ) {
	$id = $file->getNextID();

	$response = '<?xml version="1.0" encoding="UTF-8"?>';
	$response .= '<transferID>'.$id.'</transferID>';
	}
	
?>