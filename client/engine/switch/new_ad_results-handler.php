<?PHP

$publisher = $_POST["client"];
$code = $_POST["jobCode"];
$name = $_POST["description"];
$issue = $_POST["issue"];
$errors['lowres'] = $_POST["result_lowres"];

$publisher = sql_get( 'publishers', 'name="'.$publisher.'"', '*' );
$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
$pub = sql_get( 'publications', 'code="'.$issue.'" AND magazine_id="'.$magazine[0][0].'"', '*' );
$ad = sql_get( 'ads', 'publisher="'.$publisher[0][0].'" AND pub_id="'.$pub[0][0].'" AND name="'.$name.'"', '*' );	
		
$dir = '../message';

$array = array();
$array["client"] = $publisher[0][1];
$array["jobCode"] = $magazine[0][3];
$array["issue"] = $pub[0][10];
$array["event"] = 'ad_check_result';
$array["description"] = strtoupper( $_POST['description'] );
$array["remark"] = str_replace( '/', '_', $_POST['size'] );
$array["results"]["dimensions"] = $_POST["result_dimensions"];
$array["results"]["size"] = $_POST["result_size"];
$array["results"]["bleed"] = $_POST["result_bleed"];
$array["results"]["lowres"] = $_POST["result_lowres"];
$array["results"]["fontmissing"] = $_POST["result_fontmissing"];
$array["results"]["truesize"] = $_POST["result_truesize"];

$myxml = array_to_xml( $array, 'eventComm' );
$dom = new DOMDocument();
$dom->preserveWhiteSpace = false;
$dom->loadXML($myxml);
$dom->formatOutput = true;

if( $ad[0][0] != '' ) {
	$dir2 = '/var/www/html/client/advertisements';
	$checker = strtoupper( $ad[0][2] ).'_'.$code.'_'.$issue.'_'.$type;

	$ad_files = load_dir_files( $dir2, $checker );
	for( $y = 0; $y < count( $ad_files ); $y++ ) {
		$secu = explode( '_', $ad_files[$y] );
		if( strtoupper( $ad[0][2] ) == strtoupper( $secu[0] ) ) {
			//@unlink( $dir2.'/'.$ad_files[$y] );
			}
		}
	$file_name = substr( $_FILES[0]["name"], 0, -4 );
	$to = '/var/www/html/client/advertisements';
	$target = '/var/www/html/client/engine/switch';
	$name = $_FILES[0]["name"];
	
	if ( move_uploaded_file( $_FILES[0]["tmp_name"], $target.'/'.$name ) ) {
		file_put_contents( $target.'/'.$file_name.".xml" , $dom->saveXML() );

		$lowres = array();
		foreach( $_POST as $key => $value ) {
			if( strpos( $key, "result_lowres_area_" ) !== false ) {
				$lowres[] = $value;
				}
			}

		$pdf = new dynapdf();
		include('../config.inc.php');
		//$pdf->CreateNewPDF( $file_name.'_check.pdf' );
		$pdf->CreateNewPDF( NULL );

		$pdf->InitColorManagement( NULL, NULL , 1 );

		$pdf->OpenImportFile( $target.'/'.$name, dynapdf::ptOpen, NULL );
		$pdf->ImportPDFFile( 1, 1.0, 1.0 );
		$pdf->CloseImportfile();

		$sizes = getBBox( $target.'/'.$name, "", "cropbox" );	
		//error_log( json_encode( $sizes ) );
		$sizes["Width"] = pixel_( ( $sizes["Right"] - $sizes["Left"] ), 100 );
		$sizes["Height"] = pixel_( ( $sizes["Top"] - $sizes["Bottom"] ), 100 );

		$pdf->EditPage(1);
			$width = $pdf->GetPageWidth();
			$height = $pdf->GetPageHeight();
			
			$box = $pdf->GetBBox( dynapdf::pbBleedBox );
			$tbox = $pdf->GetBBox( dynapdf::pbTrimBox );

			$tbox['Width'] = $tbox['Right']-$tbox['Left'];
			$tbox['Height'] = $tbox['Top']-$tbox['Bottom'];
			$tbox['StartX'] = ( $tbox['Left']-$box['Left'] )+$box['Left'];
			$tbox['StartY'] = ( $tbox['Bottom']-$box['Bottom'] )+$box['Bottom'];
			$sbox = array( 
						"StartX"=> $tbox['StartX']+10,
						"StartY"=> $tbox['StartY']+10,
						"Width"=> $tbox['Width']-20,
						"Height"=> $tbox['Height']-20
						);	
		$pdf->EndPage();		
		$pdf->CloseFile();
		
		$data = array();
		$data["width"] = $width;
		$data["height"] = $height;
		$data["sizes"] = array();
		$data["sizes"] = json_encode($sizes);
		$data["tbox"] = array();
		$data["tbox"] = json_encode($tbox);
		$data["sbox"] = array();
		$data["sbox"] = json_encode($sbox);
		$data["pdf"] = file_get_contents($target.'/'.$name);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "http://".DYNAIP."/dynAPI/tracker/ad_check.php" );
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt($ch, CURLOPT_POST, true );
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$response = curl_exec ($ch);
		$response = json_decode( $response, true );

		$data = explode( ',', $response["pdf"] );
		file_put_contents( $file_name.'_check.pdf', base64_decode( $data[ 1 ] ) );

		$terminalPath = "/var/www/html/client";	
		$from_ = $terminalPath."/engine/switch/".$file_name."_check.pdf";
		$to_ = $terminalPath."/engine/switch/".$file_name."_check.jpg";
		
		//echo implode( " | ", $sizes );
		$command = './r3 -binary -mode:RENDER -left:0 -right:'.( $sizes["Right"] - $sizes["Left"] ).' -bottom:0 -top:'.( $sizes["Top"] - $sizes["Bottom"] ).' -width:'.$sizes["Width"].'  -height:'.$sizes["Height"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:'.resolveIccProfileByName( "FOGRA_39" ).' '.$from_.' $@ >'.$to_.' 2>&1';
		
		$command = shell_exec('
			cd /var/www/html/client/engine/r3 2>&1;
			'.$command.';
			');	
	
		if( $errors['lowres'] == 'true' ) {		
			error_log( "VAN LOWRES");
			
			unset( $pdf );
			$data = array();
			$data["width"] = $width;
			$data["height"] = $height;
			$data["lowres"] = array();
			$data["lowres"] = json_encode($lowres);
			$data["tbox"] = array();
			$data["tbox"] = json_encode($tbox);
			$data["sbox"] = array();
			$data["sbox"] = json_encode($sbox);
			
			/*$path = $file_name.'_check.jpg';
			$type = pathinfo($path, PATHINFO_EXTENSION);
			$d = file_get_contents($path);*/
			$data["img"] = file_get_contents($file_name.'_check.jpg');
			
			$headers = array(
				"Content-Type: multipart/form-data",
				"Connection: Keep-Alive",
				);
				
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "http://".DYNAIP."/dynAPI/tracker/ad_lowres.php" );
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST" );
			curl_setopt($ch, CURLOPT_POST, true );
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data );
			curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
			$response = curl_exec ($ch);
			$response = json_decode( $response, true );

			$data = explode( ',', $response["pdf"] );
			file_put_contents( $file_name.'_lowres.jpg', base64_decode( $data[ 1 ] ) );
			}			
		
		unlink( $file_name.'_check.pdf' );
		//die();
		
		@rename( $file_name.'_check.jpg' , $to.'/'.$file_name.'_check.jpg' );
		@rename( $file_name.'_lowres.jpg' , $to.'/'.$file_name.'_lowres.jpg' );
		
		//$color = partDetect( $p_id[0][0], $page );
		adThumbCreate2( "advertisements", $file_name.".pdf", $file_name."_thumb.jpg" );
		//die();
		for( $y = 0; $y < count( $files ); $y++ ) {
			if( $files[$y] == $file_name."_thumb.jpg" ) {
				copy( $from.'/'.$files[$y], $to.'/'.$file_name."_thumb.jpgBackup" );
				}
			else {
				copy( $from.'/'.$files[$y], $to.'/'.$files[$y] );
				}
			unlink( $from.'/'.$files[$y] );
			}

		$errors['size'] = $_POST["result_size"];
		$errors['bleed'] = $_POST["result_bleed"];
	
		$errors['fontmissing'] = $_POST["result_fontmissing"];
	
		if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
			$err = 0;
			$logStatus = 'successful';
			}
		else {
			$err = 1;
			$logStatus = 'failed';
			}

		if( $err == 1 ) sql_update( 'ads', '`status`="3", `reason`="", `uploaded`="", `size`="'. str_replace( '_', '/', $_POST["size"] ) .'"', 'id="'.$ad[0][0].'"' );
		else sql_update( 'ads', '`status`="2", `reason`="", `uploaded`="", `size`="'. str_replace( '_', '/', $_POST["size"] ) .'"', 'id="'.$ad[0][0].'"' );

		@rename( $file_name.".pdf" , $to.'/'.$file_name.'.pdf' );
		@rename( $file_name.".xml" , $to.'/'.$file_name.'.xml' );
		
		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
		$values = array( '0', 'resultAD', $pub[0][1], $pub[0][2], $pub[0][10], $name, time(), $logStatus );
		sql_add( 'action_log', $names, $values );	
		}
	}

?>