<?php

$betumeret = 15.0;

//Vonalvastagsági arány számítás
$xa = 130;
$ya = 180;

$lineHeight = 1;
$lineShift = 10;

$x = $trim_width / $xa;
$y = $trim_height / $ya;

$arany = ( $x > $y ? $x : $y );
if( $arany < 1 ) $arany = 1;

$fehervastagsag = 0.5 * $arany;

$vjelSize = 0.25;
$vjelHossz = 5;

//Nyilak paraméterei
$nyilmagassag = 7;
$fehernyilmagassag = 7;

$nyilszelesseg = 2.5;
$fehernyilszelesseg = 2.5;

$nyileltolas = 0.5;
$fehernyileltolas = 0.5;
?>