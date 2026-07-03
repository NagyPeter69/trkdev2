<!DOCTYPE HTML>
<head>
	<?php 
	session_start();
	
	include_once('../engine/connect.php');
	include_once('../engine/engine.php');
	include_once('../engine/xml_handler.php');
	
	?>

	<link href="../css/default.php" rel="stylesheet" type="text/css" />
	<link href="../css/menu.php "rel="stylesheet" type="text/css" />
	<link href="css/client.css" rel="stylesheet" type="text/css" />
	<link href="css/fontawesome-all.min.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
	<?php
	if(isMobile()){
    	echo '<link href="css/mobile.css" rel="stylesheet" type="text/css" />';
		}	
		
	?>
	<link rel="stylesheet" href="css/jquery-ui.css" />
    
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.flip.js"></script>
    <script src="../js/jquery.tipTip.js"></script>
    <script src="../js/jquery.tipTip2.js"></script>    
    <script src="js/jquery.ui.touch-punch.min.js"></script>
    <script src="../engine/engine.js"></script>
    <script src="js/script.js"></script>
    <script src="js/jquery.transit.min.js"></script>
    <script src="js/hammer.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	
	<script>
		var mobile = <?= ( isMobile() ? "true" : "false" )  ?>;
	</script>	
</head>

<body>
	<div class='fullchat'>
		<div class='chatHeader'>
			<div style="float: right; margin-right: 15px; margin-top: -2px;"><i onclick="window.opener.chatWindowClose(); window.close();" class="fas fa-window-close"></i></div>
		</div>
		
		<div class='chatContent'>
		</div>
	</div>	
</body>

<script>

var height = $(window).height() - $(".chatHeader").height();
$(".chatContent").css( "height", height+"px" );

</script>