<?

include_once('../../engine/connect.php');
include_once('../../engine/engine.php');
include_once('../../engine/xml_handler.php');

$file_name = "ad";
//$errors['lowres'] = 'true';
$pdf = new dynapdf();

include('../engine/config.inc.php');
$pdf->CreateNewPDF( $file_name.'_check.pdf' );

$pdf->InitColorManagement( NULL, NULL , 1 );

$pdf->OpenImportFile( $file_name.".pdf", dynapdf::ptOpen, NULL );
$pdf->ImportPDFFile( 1, 1.0, 1.0 );
$pdf->CloseImportfile();

$pdf->EditPage(1);
	$width = $pdf->GetPageWidth();
	$height = $pdf->GetPageHeight();

	$box = $pdf->GetBBox( dynapdf::pbBleedBox );
	$tbox = $pdf->GetBBox( dynapdf::pbTrimBox );

	$tbox['Width'] = $tbox['Right']-$tbox['Left'];
	$tbox['Height'] = $height-(2*$tbox['Bottom']);
	$tbox['StartX'] = ( $tbox['Left']-$box['Left'] )+$box['Left'];
	$tbox['StartY'] = ( $tbox['Bottom']-$box['Bottom'] )+$box['Bottom'];
	$sbox = array( "StartX"=> $tbox['StartX']+10, "StartY"=> $tbox['StartX']+10, "Width"=> $tbox['Width']-20, "Height"=> $tbox['Height']-20  );
	$newWidth = $tbox['Right']+14;
	$newHeight = $tbox['Top']+14;
	$pdf->SetLineWidth( 1 );			
	$g = $pdf->RGB( 0, 200, 0 );
	$r = $pdf->RGB( 200, 0, 0 );
	$b = $pdf->RGB( 0, 0, 255 );
	$pdf->SetStrokeColor( dynapdf::PDF_WHITE );
	$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
	$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );

	$pdf->SetLineDashPattern( "6", 6 );
	$pdf->SetStrokeColor( $g );
	$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
	$pdf->SetStrokeColor( $r );
	$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );
	$pdf->SetLineDashPattern( "0", 0 );
	$pdf->SetLineWidth( 2 );
	$pdf->SetStrokeColor( $b );
	$pdf->Rectangle( 0, 0, $width, $height, dynapdf::fmStroke );

$pdf->AddOutputIntent( "../engine/ISOcoated_v2_eci.icc" );
$pdf->EndPage();
$pdf->CloseFile();

$terminalPath = "/var/www/intra/client";

$sizes = getBBox( $file_name."_check.pdf", "", "mediabox" );
$from = $terminalPath."/tests/".$file_name."_check.pdf";
$to = $terminalPath."/tests/".$file_name."_check.jpg";

$sizes["Width"] = pixel_( $sizes["Width"], 100 );
$sizes["Height"] = pixel_( $sizes["Height"], 100 );
			
$renderParams = array(
	'left' => $sizes["Left"], 'right' => $sizes["Right"],
	'bottom' => $sizes["Bottom"], 'top' => $sizes["Top"],
	'width' => $sizes["Width"], 'height' => $sizes["Height"],
	'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => 'ISOcoated_v2_eci.icc',
	);
echo json_encode( $renderParams );
$imgData = r3run( 'RENDER', $renderParams, $from );
file_put_contents( $to, $imgData );
echo "<br>";
echo "wrote ".strlen( $imgData )." bytes to ".$to;

if( $errors['lowres'] == 'true' ) {
	unset( $pdf );

	$pdf = new dynapdf();
	$pdf->CreateNewPDF( null );
	include('../engine/config.inc.php');

	$pdf->SetBBox( dynapdf::pbMediaBox, 0, 0, $width, $height );										
	$pdf->InitColorManagement( NULL, NULL , 1 );
	$pdf->Append();
		$gs = $pdf->CreateExtGState(array('FillAlpha' => 0.5));
		$pdf->SetExtGState($gs);

		$pdf->InsertImageEx(0, 0, $width, $height, $file_name.'_check.pdf', 1);

		$gs = $pdf->CreateExtGState(array('FillAlpha' => 1));
		$pdf->SetExtGState($gs);

		$gs = $pdf->CreateExtGState(array('FillAlpha' => 1));
		$pdf->SetExtGState($gs);
		
		$count = count($lowres);
		$r_ = 25/$count;
		$g_ = 255/$count;
		$b_ = 255/$count;

		$pdf->SetLineDashPattern( "0", 0 );
		for( $x=0; $x < count($lowres); $x++ ) {
			$data = explode( "_", $lowres[$x] );
			$r = $pdf->RGB( 230+($r_*($x+1) ), $g_*($x), $b_*($x) );
			$pdf->SetFillColor( $r );
			$pdf->Rectangle( $data[0], $data[1], ($data[2]-$data[0]), ($data[3]-$data[1]), dynapdf::fmFill );
			}
		$pdf->SetLineWidth( 1 );			
		$g = $pdf->RGB( 0, 200, 0 );
		$r = $pdf->RGB( 200, 0, 0 );
		$pdf->SetStrokeColor( dynapdf::PDF_WHITE );
		$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
		$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );

		$pdf->SetLineDashPattern( "6", 6 );
		$pdf->SetStrokeColor( $g );
		$pdf->Rectangle( $tbox['StartX'], $tbox['StartY'], $tbox['Width'], $tbox['Height'], dynapdf::fmStroke );
		$pdf->SetStrokeColor( $r );
		$pdf->Rectangle( $sbox['StartX'], $sbox['StartY'], $sbox['Width'], $sbox['Height'], dynapdf::fmStroke );
		$pdf->SetLineDashPattern( "0", 0 );
		$pdf->SetLineWidth( 2 );
		$pdf->SetStrokeColor( $b );
		$pdf->Rectangle( 0, 0, $width, $height, dynapdf::fmStroke );
	$pdf->EndPage();

	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);

	$pdf->SetJPEGQuality( 100 );	

	$pdf->AddOutputIntent( "../engine/ISOcoated_v2_eci.icc" );	
	$pdf->RenderPageToImage(1, $file_name.'_lowres.jpg', 150, $width, $height, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfJPEG, dynapdf::ifmJPEG);
	}
?>