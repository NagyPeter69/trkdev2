<?php
session_start();

//if( $_COOKIE["intra_user"] !=  "1" ) die();

include_once('../engine/connect.php');

include_once('lang/en.php');

include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');

?>
<!DOCTYPE HTML>
<HTML>

<head>
	<title>Colorcom Media :: Tracker</title>
	
	<meta charset="UTF-8">
	<meta http-equiv="cache-control" content="max-age=0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta name="viewport" content="width=device-width, height=device-height,initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

	<link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">
		
	<link href="../css/default.php" rel="stylesheet" type="text/css" />
	<link href="../css/menu.php "rel="stylesheet" type="text/css" />
	<link href="css/client.css" rel="stylesheet" type="text/css" />
	<link href="css/fontawesome-all.min.css" rel="stylesheet" type="text/css" />
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
	
<iframe id="fupload" name="fupload" src="fupload.php" frameborder="0" style="visibility: hidden; height: 0px; position: fixed;"></iframe>
<iframe id="main" name="main" src="index2.php?<?= $_SERVER["QUERY_STRING"] ?>" frameborder="0" style="padding: 0; margin: 0; height:100vh; width:100vw;"></iframe>

</body>
</HTML>