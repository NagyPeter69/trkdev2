<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');

include( "../../engine.php" );
include( "drawEngine.php" );

$data = $_POST;
$data[0] = json_decode( $_POST[0], true );
$data[1] = json_decode( $_POST[1], true );
$data[2] = json_decode( $_POST[2], true );
$data[3] = json_decode( $_POST[3], true );
$data[4] = json_decode( $_POST[4], true );
$data[5] = json_decode( $_POST[5], true );

/*ob_flush();
ob_start();
var_dump( $data );

file_put_contents("dump.txt", ob_get_flush());
die();*/

$ido = explode( ":", $data[0]["preparation"] );
$fname = "rendered/".$data["pdffile"];

switch( $data[0]["status"] ) {
	case 0:
		$status = "Ajánlatra vár";
		break;
	case 1:
		$status = "Döntésre vár";
		break;
	case 2:
		$status = "Elfogadva";
		break;
	case -1:
		$status = "Elutasítva (lezárva)";
		break;			
	case -2:
		$status = "Meghíusult (lezárva)";
		break;							
	case 3:
		$status = "Elkészült (lezárva)";
		break;
	}

$pdf = new dynapdf();
include('../../config.inc.php');
$pdf->CreateNewPDF( $fname );
$pdf->SetDocInfo(dynapdf::diTitle, 'Riport');
$pdf->SetPageCoords(dynapdf::pcTopDown);
$pdf->AddFontSearchPath( "../fonts" , true );
$pdf->SetUseTransparency( false );

$fontcolor = $pdf->RGB(116, 116, 116);
$pdf->Append();
	$pdf->SetFillColor( $fontcolor );
	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 20.0, true, dynapdf::cpUnicode);
	$pdf->WriteText($pdf->GetPageWidth()/2-135, 10, 'Online Preflight - Javítási Jegy');

	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 60, 80, 'Jegy sorszám: ');
	$pdf->SetFont('Myriad Pro', dynapdf::fsRegular, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 165, 80, "#".$data[0]["id"] );

	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 60, 100, 'Létrehozta: ');
	$pdf->SetFont('Myriad Pro', dynapdf::fsRegular, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 165, 100, $data[0]["creator"] );

	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 60, 120, 'Dátum: ');
	$pdf->SetFont('Myriad Pro', dynapdf::fsRegular, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 165, 120, date( "Y m.d. H:i",  $data[0]["date"] ) );

	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 60, 140, 'Munka neve: ');
	$pdf->SetFont('Myriad Pro', dynapdf::fsRegular, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 165, 140, $data[0]["jobName"] );

	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 60, 160, 'Fájl neve: ');
	$pdf->SetFont('Myriad Pro', dynapdf::fsRegular, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 165, 160, $data[0]["originalName"] );

	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 60, 180, 'Becsült feldolgozási idő: ');
	$pdf->SetFont('Myriad Pro', dynapdf::fsRegular, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 210, 180, $ido[0]." óra ".$ido[1]." perc, ajánlati ár: ".$data[0]["price"]." Ft + ÁFA" );

	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 60, 200, 'Jegy státusza: ');
	$pdf->SetFont('Myriad Pro', dynapdf::fsRegular, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 165, 200, $status );
	
	$pdf->WriteText( 20, 230, "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -" );
	$pdf->SetFont('Myriad Pro', dynapdf::fsBold, 13.0, true, dynapdf::cpUnicode);
	$pdf->WriteText( 60, 260, 'Beszélgetés: ');
	
	$txt = "";
	for( $i = 0; $i < count( $data[5] ); $i++ ) {
		$txt .= "\\ul#".$data[5][$i]["fullName"]." (".date( "Y-m-d H:i", $data[5][$i]["time"] ).")\\ul#:".PHP_EOL;
		$txt .= $data[5][$i]["message"].PHP_EOL;
		$txt .= PHP_EOL;
		}
	$pdf->SetFont('Myriad Pro', dynapdf::fsRegular, 13.0, true, dynapdf::cpUnicode);
	
	$pdf->SetTextRect( 60, 280, $pdf->GetPageWidth()-120 , -1 );
	$pdf->WriteFText( dynapdf::taLeft, $txt );
	
$pdf->EndPage();
$pdf->CloseFile();

$path = $fname;
$type = pathinfo($path, PATHINFO_EXTENSION);
$data = file_get_contents($path);
$base64 = 'data:application/' . $type . ';base64,' . base64_encode($data);
$response["pdf"] = $base64;	

echo json_encode( $response );

?>