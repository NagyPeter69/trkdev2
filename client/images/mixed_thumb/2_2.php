<?php
	
$txt .= '<svg class="svgthumb" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="119px" height="170px" viewBox="0 0 119 170" style="enable-background:new 0 0 119 170;" xml:space="preserve">
<rect style="fill:#AAAAAA;" width="119" height="170"/>
<rect class="mix_field" boxplace="left" place="left" x="12" y="13" style="fill:#'.$mpages[0]["color"].';" width="45" height="135"/>
<rect class="mix_field" boxplace="right" place="right" x="62" y="13" style="fill:#'.$mpages[1]["color"].';" width="45" height="135"/>
</svg>';

$txt .= '<div class="svgdotbox" style="left: 17px; bottom: 4px; width: 19px;">'.$mpages[0]["dots"].'</div>';
$txt .= '<div class="svgdotbox" style="left: 51px; bottom: 4px; width: 19px;">'.$mpages[1]["dots"].'</div>';

$txt .= '<div class="svgworkerbox" style="left: 30px; top: 3px; width: 19px;">'.$mpages[0]["worker"].'</div>';
$txt .= '<div class="svgworkerbox" style="left: 64px; top: 3px; width: 19px;">'.$mpages[1]["worker"].'</div>';
?>
