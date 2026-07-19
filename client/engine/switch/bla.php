<?php

require_once( "/var/www/html/engine/r3client.php" );

$imgData = r3run( 'RENDER', array(
	'left' => 0, 'right' => 581.102, 'bottom' => 0, 'top' => 776.693,
	'width' => 807.08611111111, 'height' => 1078.7402777778,
	'tprofile' => 'sRGB_Color_Space_Profile.icc', 'sprofile' => 'ISOcoated_v2_eci.icc',
	), '/var/www/html/client/engine/switch/TESZT_DM_2102_F_check.pdf' );
file_put_contents( '/var/www/html/client/engine/switch/TESZT_DM_2102_F_check.jpg', $imgData );

?>
