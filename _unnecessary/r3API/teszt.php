<?php
header('Content-type: text/html; charset=UTF-8');
include( "../engine.php" );

//$_POST["filename"] = "40_nevjegy_1509023395.pdf";

define( "SFOLDER", "/var/www/html/r3API/source");
define( "RFOLDER", "/var/www/html/r3API/rendered");

//file_put_contents( SFOLDER."/raw_".$_POST["filename"], $_POST["pdf"] );

/*$pdf = SFOLDER."/raw_".$_POST["filename"];
$to = RFOLDER."/".$_POST["filename"];

$box = getPDFBox( "Trimbox", $pdf );*/

$terminal = "/var/www/html/r3API";
//$command = "./r3 -left:".$box["Trimbox"][0]." -right:".$box["Trimbox"][2]." -bottom:".$box["Trimbox"][1]." -top:".$box["Trimbox"][3]." -binary: -mode:RENDER -width:520 -height:299 -tprofile:ISOcoated_v2_eci.icc ".$pdf." >a.jpg";
//$command = "./r3 -left:0 -right:864 -bottom:0 -top:828 -binary: -mode:RENDER -width:1200 -height:1656 -tprofile:sRGB_Color_Space_Profile.icc -sprofile:ISOcoated_v2_eci.icc spot.pdf > spot.jpg";
$command = "./r3 -mode:MEASURE -x:596 -y:760 -d:1 -r:600 -tprofile:ISOcoated_v2_eci.icc spot.pdf 2>&1";
echo $command;
$command = shell_exec('
		cd '.$terminal.'/r3;
		'.$command.';
		');
$pantone = preg_split('/[\r\n]+/', $command);
var_dump( $pantone );

//echo $command;
?>