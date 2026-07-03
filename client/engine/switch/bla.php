<?php

$command = './r3 -binary -mode:RENDER -left:0 -right:581.102 -bottom:0 -top:776.693 -width:807.08611111111  -height:1078.7402777778 -tprofile:sRGB_Color_Space_Profile.icc -sprofile:ISOcoated_v2_eci.icc /var/www/html/client/engine/switch/TESZT_DM_2102_F_check.pdf $@ >/var/www/html/client/engine/switch/TESZT_DM_2102_F_check.jpg 2>&1';
		
		$command = shell_exec('
			cd /var/www/html/client/engine/r3 2>&1;
			'.$command.';
			');

?>