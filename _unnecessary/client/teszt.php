<?
$path = "bad.pdf";
$ext = pathinfo($path, PATHINFO_EXTENSION);


$path2 = "good.pdf";
$ext2 = pathinfo($path2, PATHINFO_EXTENSION);
echo $ext.", ".$ext2;
die();
?>

<body style='margin: 0; padding: 0;'>
<script src="js/jquery-1.7.1.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/preview3.js"></script>
<script type="text/javascript" src="js/jquery.kinetic2.js"></script>

<?php
header('Content-Type: text/html; charset=utf-8');

function pixel_( $num, $zoom ) {
	return $num * $zoom / 72;
	}

function point_( $num, $zoom ) {
	return $num * 72 / $zoom;
	}

function PDFtoImage( $sizes, $to ) {
	global $from;
	$command = './r3 -left:'.$sizes["Left"].' -right:'.$sizes["Right"].' -bottom:'.$sizes["Bottom"].' -top:'.$sizes["Top"].' -binary: -mode:RENDER -width:'.$sizes["Width"].' -height:'.$sizes["Height"].' -tprofile:ISOcoated_v2_eci.icc '.$from.' >'.$to.' 2>&1';
	shell_exec('
			cd engine/r3 2>&1;
			'.$command.';
			');
	return $command;
	}

function getBBox( $file, $path ) {
	global $pixel;
	$pdf = new dynapdf();
	
	include('../engine/config.inc.php');
	$pdf->CreateNewPDF( NULL );

	$pdf->OpenImportFile( $file , dynapdf::ptOpen, NULL );
	$pdf->ImportPDFFile( 1, 1.0, 1.0 );	
	$pdf->CloseImportfile();
	
	$pdf->EditPage(1);
		$sizes = $pdf->GetBBox( dynapdf::pbMediaBox );
		$sizes["Width"] = intval( intval( $pdf->GetPageWidth() ) );
		$sizes["Height"] = intval( intval( $pdf->GetPageHeight() ) );
	$pdf->EndPage();
	
	return $sizes;
	}
	

$pair = true;

$_SESSION['test_id'] = rand( 1, getrandmax() );

$zoom = 100;
$bgDPI = 72;

$width = 1200;
$height = 750;

$file[0]["Name"] = "036_IS1406.pdf";
$sizes = getBBox( "./engine/r3/".$file[0]["Name"] );
$file[0]["Right"] = $sizes['Right'];
$file[0]["Top"] = $sizes['Top'];

	
if( $pair ) {
	$file[1]["Name"] = "037_IS1406.pdf";
	$sizes2 = getBBox( "./engine/r3/".$file[1]["Name"] );
	$file[1]["Right"] = $sizes2['Right'];
	$file[1]["Top"] = $sizes2['Top'];
	
	$from = $file[0]["Name"];
	$sizes['Width'] = pixel_( $sizes['Width'], $bgDPI );
	$sizes['Height'] = intval( pixel_( $sizes['Height'], $bgDPI ) );
	PDFtoImage( $sizes, "leftBg.jpg" );
	$sizes["Right"] = $sizes['Right'] + $sizes2['Right'];
	
	$from = $file[1]["Name"];
	$sizes2['Width'] = pixel_( $sizes2['Width'], $bgDPI );
	$sizes2['Height'] = intval( pixel_( $sizes2['Height'], $bgDPI ) );	
	PDFtoImage( $sizes2, "rightBg.jpg" );
	$first = new Imagick( "engine/r3/leftBg.jpg" );
	$second = new Imagick( "engine/r3/rightBg.jpg" );	
	$image = new Imagick();
	$image->newImage( pixel_( $sizes['Right'], $bgDPI ), pixel_( $sizes['Top'] , $bgDPI ), new ImagickPixel('red') );
		$image->setImageFormat('jpg');
		$image->compositeImage($first, $first->getImageCompose(), 0, 0); 
		$image->compositeImage($second, $second->getImageCompose(), $sizes['Width'], 0); 
	$image->writeImage("engine/r3/bg.jpg"); 
	}
else {
	$bg = "";
	}
	
$boxSize = array( 
			"width" => intval( pixel_( ($sizes['Right'] + $sizes['Left']), $zoom ) ),
			"height" => intval( pixel_( ($sizes['Top'] + $sizes['Bottom']), $zoom ) )
			);			
?>

<div id="box" style='width: <?= $width ?>px; height: <?= $height ?>px; overflow: hidden; background: #fff;'>
	<div id='imgbox' style='background-image: url(engine/r3/bg.jpg); background-size:cover; background-repeat:no-repeat; position: relative; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px;'>
		<div id='renderedIMG' style='width: <?= $width ?>px; height: <?= $height ?>px; position: absolute;'>
			<img id="renderedSRC" src="">
		</div>
	</div>
</div>

<script>
var file = {};
file[0] = {<?
	echo "'Name': '".$file[0]["Name"]."',";
	echo "'Right': '".$file[0]["Right"]."',";
	echo "'Top': '".$file[0]["Top"]."',";
	?>}
file[1] = {<?
	echo "'Name': '".$file[1]["Name"]."',";
	echo "'Right': '".$file[1]["Right"]."',";
	echo "'Top': '".$file[1]["Top"]."',";
	?>}

var scale = [ 25, 66, 78, 100, 125, 150, 200, 300, 400 ];
var zoom = parseInt( '<?= $zoom ?>' );
var scaleNr = 3;

var defaultSizes = {
	Left: parseInt( '<?= $sizes["Left"] ?>' ),
	Right: parseInt( '<?= $sizes["Right"] ?>' ),
	Top: parseInt( '<?= $sizes["Top"] ?>' ),
	Bottom: parseInt( '<?= $sizes["Bottom"] ?>' ) 
	};
	
var width =  '<?= $width ?>';
var height = '<?= $height ?>';
var down = false;
var delayed = 0;
var ajaxDisabled = false;

function pixel( number ) {
	return number * zoom / 72;
	}
	
function point( number ) {
	return number * 72 / zoom;
	}

function rendering( asap ) {
	if( ajaxDisabled && asap == undefined ) {
		console.log( "delayed...");
		delayed++;
		if( delayed < 2 ) {
			setTimeout( function(){ rendering(); }, 50 );
			}
		}
	else {
		delayed = 0;
		ajaxDisabled = true;
		var boxSize = {
			width: parseInt( ( pixel( defaultSizes['Right'] ) + defaultSizes['Left'] ) ),
			height: parseInt( ( pixel( defaultSizes['Top'] ) + defaultSizes['Bottom'] ) )		
			}
	
		$("#imgbox").css({
			"width": boxSize.width+"px",
			"height": boxSize.height+"px"
			});
		
		var positions = {
			left: point( $("#box").scrollLeft() ),
			bottom: point( ( $("#imgbox").innerHeight() )-( $("#box").scrollTop() )-( $("#box").innerHeight() ) ),
			width: ( width ),
			height: ( height )
			}
		var currentPos = {
			left: $('#box').scrollLeft(),
			top: $('#box').scrollTop()
			}
		positions['right'] = positions.left + point( $("#box").innerWidth() ) ;
		positions['top'] = positions.bottom + point( $("#box").innerHeight() ) ;
	
		$.ajax	({
			url:"tesztAjax.php?zoom="+zoom,
			type: "POST",
			data: { positions : positions, file: file },
			dataType: 'json',
			success:function( data ) {
				var img = new Image();	
				img.onload = function(){
					$("#renderedIMG").css({
						left: currentPos.left,
						top: currentPos.top,
						});
					};
					$("#renderedSRC").attr('src', data[0] );
				img.src = data[0];
				ajaxDisabled = false;
				}
			});
		}
	}

$('#box').on('mousedown', function() {
	down = true;
	});

$('body').mousemove(function(e){
	if( down ) {
		//rendering();
		}
	});

$('body').on('mouseup', function() {
	down = false;
	rendering( "asap" );
	});
rendering();
$('#box').kinetic();

jQuery(document).ready(function(){
    if(window.addEventListener) {
        document.addEventListener('DOMMouseScroll', moveObject3, false);
		}
    document.onmousewheel = moveObject3;
});
</script>
</body>