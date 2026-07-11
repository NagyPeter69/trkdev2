<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');
	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );

	$rights = array();
	if( isset( $_SESSION['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}

	// same gate used to show the Color Standards menu item in the first place
	if( empty( $rights["sys_log"] ) ) {
		print json_encode( array( "error" => "Not authorized" ) );
		exit;
		}

	if( $_GET["sub"] == "add" ) {
		$name = trim( $_POST["name"] );
		$profile = trim( $_POST["profile_label"] );

		if( $name == "" || !preg_match( '/^[A-Za-z0-9_]+$/', $name ) ) {
			print json_encode( array( "error" => "Name is required and may only contain letters, numbers and underscores" ) );
			exit;
			}

		$existing = sql_get( "color_standards", "name='".$name."'", "id" );
		if( !empty( $existing[0][0] ) ) {
			print json_encode( array( "error" => "A color standard with that name already exists" ) );
			exit;
			}

		$iccFile = "";
		if( !empty( $_FILES["icc_file"]["name"] ) ) {
			$full_type = explode( '.', $_FILES["icc_file"]["name"] );
			$ext = strtolower( $full_type[ count( $full_type )-1 ] );
			$allowed = array( 'icc', 'icm' );

			if( !in_array( $ext, $allowed ) ) {
				print json_encode( array( "error" => "Only .icc or .icm files are allowed" ) );
				exit;
				}

			// r3API/r3 is where every render command already resolves ICC
			// profile filenames from (relative cwd at render time), so a
			// standard is immediately usable as soon as it's uploaded here.
			$iccFile = $name.".".$ext;
			if( !move_uploaded_file( $_FILES["icc_file"]["tmp_name"], "/var/www/html/r3API/r3/".$iccFile ) ) {
				print json_encode( array( "error" => "Failed to save the ICC file" ) );
				exit;
				}
			}

		$names = array( "name", "profile_label", "icc_file" );
		$values = array( $name, $profile, $iccFile );
		sql_add( "color_standards", $names, $values );

		print json_encode( array( "success" => true ) );
		}

	if( $_GET["sub"] == "delete" ) {
		sql_delete( "color_standards", "id='".$_GET["id"]."'" );
		print json_encode( array( "success" => true ) );
		}
?>
