<?php
	
$txt .= '<svg class="svgthumb" version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
	 width="119px" height="170px" viewBox="0 0 119 170" style="enable-background:new 0 0 119 170;" xml:space="preserve">
<rect style="fill:#AAAAAA;" width="119" height="170"/>
<rect class="mix_field" boxplace="left" place="fullwidth" x="12" y="13" style="fill:#'.$mpages[0]["color"].';" width="95" height="65"/>
<rect class="mix_field" boxplace="left" place="fullwidth" x="12" y="83" style="fill:#'.$mpages[1]["color"].';" width="95" height="65"/>
</svg>';

$txt .= '<div class="svgdotbox" style="right: 10px; bottom: 52px; width: 60px;">'.$mpages[0]["dots"].'</div>';
$txt .= '<div class="svgdotbox" style="right: 10px; bottom: 4px; width: 60px;">'.$mpages[1]["dots"].'</div>';

$txt .= '<div class="svgworkerbox" style="right: -3px; top: 2px; width: 60px;">'.$mpages[0]["worker"].'</div>';
$txt .= '<div class="svgworkerbox" style="right: -3px; top: 49px; width: 60px;">'.$mpages[1]["worker"].'</div>';

?>