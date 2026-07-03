<?php
	
$txt .= '<svg class="svgthumb" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="119px" height="170px" viewBox="0 0 119 170" style="enable-background:new 0 0 119 170;" xml:space="preserve">
<rect style="fill:#AAAAAA;" width="119" height="170"/>
<rect class="mix_field" boxplace="left" place="fullwidth" x="12" y="13" style="fill:#'.$mpages[0]["color"].';" width="95" height="42"/>
<rect class="mix_field" boxplace="left" place="fullwidth" x="12" y="106" style="fill:#'.$mpages[2]["color"].';" width="95" height="42"/>
<rect class="mix_field" boxplace="right" place="fullwidth" x="12" y="59" style="fill:#'.$mpages[1]["color"].';" width="95" height="43"/>
</svg>';

$txt .= '<div class="svgdotbox" style="right: 9px; bottom: 67px; width: 35px;">'.$mpages[0]["dots"].'</div>';
$txt .= '<div class="svgdotbox" style="right: 9px; bottom: 4px; width: 35px;">'.$mpages[2]["dots"].'</div>';
$txt .= '<div class="svgdotbox" style="right: 9px; bottom: 35px; width: 35px;">'.$mpages[1]["dots"].'</div>';

$txt .= '<div class="svgworkerbox" style="right: -4px; top: 1px; width: 19px;">'.$mpages[0]["worker"].'</div>';
$txt .= '<div class="svgworkerbox" style="right: -4px; top: 32px; width: 19px;">'.$mpages[2]["worker"].'</div>';
$txt .= '<div class="svgworkerbox" style="right: -4px; top: 64px; width: 19px;">'.$mpages[1]["worker"].'</div>';

?>
