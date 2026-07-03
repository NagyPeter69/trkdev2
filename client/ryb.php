<?php 
session_start();

include_once('../engine/connect.php');

include_once('lang/en.php');

include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');
?>

<HTML>

<head>
	<title>Colorcom Media :: Tracker</title>
	
	<meta charset="UTF-8">
	<meta http-equiv="cache-control" content="max-age=0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

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

<div id="buttons">
      Generate <input type="number" id="number-colors" min="2" value="120"> colors.
</div>
    
<div id="colors"></div>


<script src="js/ryb.js"></script>
	
</body>

</HTML>