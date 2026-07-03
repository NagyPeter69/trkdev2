<?php
header("Content-type: text/css");
	
$percents = unserialize( $_GET['percents'] );
$start = unserialize( $_GET['start'] );
$value = unserialize( $_GET['value'] );
$id = $_GET['id'];
$bigest = $_GET['big'];

switch( $id ) {
	case 'pubPie':
		$colors = array( '64, 195, 20', '50, 218, 255', '255, 65, 26' );
		break;
	case 'fpPie':
		$colors = array( '254, 144, 1', '254, 184, 0', '254, 234, 0' );
		break;
	}

$width = $_GET['width'];
$height = $_GET['height'];

for( $i = 0; $i < count( $percents ); $i++ ) {
	echo "
		 #".$id." .pieChart".$i.":BEFORE,
		 #".$id." .pieChart".$i.":AFTER {
		 	background-color: rgb( ".$colors[$i]." );
		 	}
		 ";
	
	if( $start[$i] > 0 ) {
		echo "
		#".$id." .pieChart".$i."[data-start='".$start[$i]."'] {
			-moz-transform: rotate(".$start[$i]."deg);
			-ms-transform: rotate(".$start[$i]."deg);
			-webkit-transform: rotate(".$start[$i]."deg);
			-o-transform: rotate(".$start[$i]."deg);
			transform:rotate(".$start[$i]."deg);
			}
		 ";
		 }
	
	if( $bigest == $i && $value[$i] >= '360' ) {
		echo "
			#".$id." .pieChart".$i."[data-value='".$value[$i]."']:BEFORE {
				-moz-transform: rotate(".($value[$i])."deg);
				-ms-transform: rotate(".($value[$i])."deg);
				-webkit-transform: rotate(".($value[$i])."deg);
				-o-transform: rotate(".($value[$i])."deg);
				transform:rotate(".($value[$i])."deg);
				}
			";
		}
	else {	
		echo "
			#".$id." .pieChart".$i."[data-value='".$value[$i]."']:BEFORE {
				-moz-transform: rotate(".($value[$i]+1)."deg);
				-ms-transform: rotate(".($value[$i]+1)."deg);
				-webkit-transform: rotate(".($value[$i]+1)."deg);
				-o-transform: rotate(".($value[$i]+1)."deg);
				transform:rotate(".($value[$i]+1)."deg);
				}
			";
		}
	}

?>

#<?= $id; ?> .pie {
	position:absolute;
	width:<?= ($width/2) ?>px;
	height:<?= $height ?>px;
	overflow:hidden;
	left:<?= ($width/2) ?>px;
	-moz-transform-origin:left center;
	-ms-transform-origin:left center;
	-o-transform-origin:left center;
	-webkit-transform-origin:left center;
	transform-origin:left center;
	}

#<?= $id; ?> .pie.big {
	width:<?= $width ?>px;
	height:<?= $height ?>px;
	left:0px;
	-moz-transform-origin:center center;
	-ms-transform-origin:center center;
	-o-transform-origin:center center;
	-webkit-transform-origin:center center;
	transform-origin:center center;
	}

#<?= $id; ?> .pie:BEFORE {
	content:"";
	position:absolute;
	width:<?= ($width/2) ?>px;
	height:<?= $height ?>px;
	left:-<?= ($width/2) ?>px;
	border-radius:<?= ($width/2) ?>px 0 0 <?= ($width/2) ?>px;
	-moz-transform-origin:right center;
	-ms-transform-origin:right center;
	-o-transform-origin:right center;
	-webkit-transform-origin:right center;
	transform-origin:right center;	
	}
#<?= $id; ?> .pie.big:BEFORE {
	left:0px;
	}
#<?= $id; ?> .pie.big:AFTER {
	content:"";
	position:absolute;
	width:<?= ($width/2) ?>px;
	height:<?= $height ?>px;
	left:<?= ($width/2) ?>px;
	border-radius:0 <?= ($width/2) ?>px <?= ($width/2) ?>px 0;
	}