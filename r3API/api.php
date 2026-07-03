<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );

define( "SFOLDER", "/var/www/html/r3API/source");
define( "RFOLDER", "/var/www/html/r3API/rendered");
$terminal = "/var/www/html/r3API";

if( move_uploaded_file( $_FILES["file"]["tmp_name"][0]["file"], SFOLDER."/".$_FILES["file"]["name"][0]["file"] ) ) {
	$from = SFOLDER."/".$_FILES["file"]["name"][0]["file"];
	$to = RFOLDER."/".$_FILES["file"]["name"][0]["file"].".jpg";
	
	$pdf = new dynapdf();
	include('../config.inc.php');
	
	$pdf->CreateNewPDF( "dyna_".$_FILES["file"]["name"][0]["file"] );			
	
	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);
	$pdf->SetPDFVersion( 10 );	
	$pdf->SetPageCoords(dynapdf::pcTopDown);
	$pdf->OpenImportFile( $from , dynapdf::ptOpen, NULL);
	
	$pdf->Append();
		$pdf->ImportPageEx(1, 1.0, 1.0);
		
		$crop = $pdf->GetBBox( dynapdf::pbMediaBox );
	$pdf->EndPage();
	
	$pdf->CloseImportFile();
	$pdf->CloseFile();
	unset($pdf);
	unlink( "dyna_".$_FILES["file"]["name"][0]["file"] );
	
	$w = ( $crop["Right"] - $crop["Left"] ) * 100 / 72;
	$h = ( $crop["Top"] - $crop["Bottom"] ) * 100 / 72;
	
	$command = './r3 -binary -mode:RENDER -left:'.$crop["Left"].' -right:'.$crop["Right"].' -bottom:'.$crop["Bottom"].' -top:'.$crop["Top"].' -width:'.$w.' -height:'.$h.' -colors:'.$_POST["colors"].' -tprofile:sRGB_Color_Space_Profile.icc -sprofile:ISOcoated_v2_eci.icc '.$from.' > '.$to.'';
	
	$command = shell_exec('
		cd '.$terminal.'/r3;
		'.$command.';
		');	
	
	$path = $to;
	$type = pathinfo($path, PATHINFO_EXTENSION);
	$data = file_get_contents($path);
	$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
	
	$response["img"] = $base64;	
	$response["status"] = "success";
	
	unlink( $from );
	unlink( $to );
	}
else {
	$response["status"] = "FAIL";
	}

echo json_encode( $response );
?>