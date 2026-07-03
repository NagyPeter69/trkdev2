<?php
session_start();

include_once('../engine/connect.php');
include_once('lang/en.php');
include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');
$background = "#103d8b";
$transfer = sql_aget( "filetransfer", "id='".$_GET["transferid"]."'", "*" );

if( $_GET["op"] == "mass" && !empty( $transfer[0]["id"] ) ) {
	require ( '/composer/vendor/autoload.php' );
	
	$options = new ZipStream\Option\Archive();
	$options->setSendHttpHeaders(true);
	
	//$job = sql_aget( "jobs", "id='".$transfer[0]["jobid"]."'", "*" );
	$files = load_dir_files( TRKPATH."/".$transfer[0]["path"], "" );

	$zip = new ZipStream\ZipStream( $transfer[0]["name"].'.zip', $options);
	for( $i = 0; $i < count( $files ); $i++ ) {
		if( strpos( $files[$i], "THUMB_" ) === false ) {
			$zip->addFileFromPath( $files[$i], TRKPATH."/".$transfer[0]["path"]."/".$files[$i] );
			}
		}
	$zip->finish();	
	}

else {

if( !empty( $_POST["selectedFiles"] ) ) {
	if( count( $_POST["selectedFiles"] ) > 0 ) {
		require ( '/composer/vendor/autoload.php' );

		$options = new ZipStream\Option\Archive();
		$options->setSendHttpHeaders(true);
		
		//$job = sql_aget( "jobs", "id='".$transfer[0]["jobid"]."'", "*" );
		
		$zip = new ZipStream\ZipStream( $transfer[0]["name"].'.zip', $options);
		for( $i = 0; $i < count( $_POST["selectedFiles"] ); $i++ ) {
			$_POST["selectedFiles"][$i] = urldecode( $_POST["selectedFiles"][$i] );
			
			$search = substr( str_replace( "THUMB_", "", $_POST["selectedFiles"][$i] ), 0, -4 );
			$files = load_dir_files( TRKPATH."/".$transfer[0]["path"], $search );
			for( $x = 0; $x < count( $files ); $x++ ) {
				if( strpos( $files[$x], "THUMB_" ) === false ) {
					$zip->addFileFromPath( $files[$x], TRKPATH."/".$transfer[0]["path"]."/".$files[$x] );
					}
				}
			}
		$zip->finish();
		}
	}

?>

<!DOCTYPE HTML>
<head>
	<link href="../css/default.php" rel="stylesheet" type="text/css" />
	<link href="../css/menu.php "rel="stylesheet" type="text/css" />
	<link href="css/client.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css" integrity="sha384-5sAR7xN1Nv6T6+dT2mhtzEpVJvfS3NScPQTrOxhwjIuvcA67KV2R5Jz6kr4abQsz" crossorigin="anonymous">
	<link href="css/filetransfer.css" rel="stylesheet" type="text/css" />
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

<div id="mainPage">
	<div id='header'>
		<div class="logo" style='height: 50px; background: <?= $background ?>;' >
			<?php if( isMobile() ) { ?>
				<div class="logo-in" style="padding: 15px;" onclick='$("#mobile_usermenu").show(150)'>
					<div class="logo-in" style="pointer-events: none; display: inline-block;"><?php include( "images/SVG/TRK.svg" ); ?></div>
					<div class="logo-in" style="pointer-events: none; float: right;"><?php include( "images/SVG/hambi.svg" ); ?></div>
				</div>
			<?php } else { ?>
				<img id='trklogoimage' src='images/ccmlogo.png' usemap="#ccmlogo">
			<?php } ?>
			
			</map>
		</div>
		<div id="menuLine">
			<ul class="nav">
				<li class="planner"><a href="#">Letöltés</a></li>
			</ul>
		</div>
		
		<div style="float: right;">
			<div class="panelButton" onclick="downloadfiles()" style="margin-right: 12px; margin-top: 12px; width: auto; padding-left: 10px; padding-right: 10px;">Letöltés</div>
		</div>
		
		<div id="selectall" style="float: right;">
			<div class="panelButton" onclick="allselect( 'select' )" style="margin-right: 12px; margin-top: 12px; width: auto; padding-left: 10px; padding-right: 10px;">Összes kijelölése</div>
		</div>		
		<div id="deselectall" style="float: right; display: none;">
			<div class="panelButton" onclick="allselect( 'deselect' )" style="margin-right: 12px; margin-top: 12px; width: auto; padding-left: 10px; padding-right: 10px;">Összes kijelölés törlése</div>
		</div>
	</div>
	
	<div id='content' style="overflow: auto;">
	<?php
	if( !empty( $transfer[0]["id"] ) ) {
		$job = sql_aget( "jobs", "id='".$transfer[0]["jobid"]."'", "*" );
		
		$files = load_dir_files( TRKPATH."/".$transfer[0]["path"], "THUMB_" );
		
		echo "<form method='POST' id='pageselect'>";
		for( $i = 0; $i < count( $files ); $i++ ) {
			echo "<div class='previewBox'>";
				echo "<div onclick='selectBox( $(this) )' class='previewWrap'>";
					echo "<div class='preview_thumb' style='background-image:url(".$transfer[0]["path"]."/".str_replace("+", "%20", urlencode( $files[$i] ) ).");'></div>";
					
					$name = substr( str_replace( "THUMB_", "", $files[$i] ), 0, -4 );
					if( strlen( $name ) > 30 ) {
						$name = substr( $name, 0, 13 )."...".substr( $name, -13 );
						}
					
					echo "<div style='pointer-events: none; font-size: 13px; padding-top: 2px;'>".$name."</div>";
					echo "<input type='checkbox' name='selectedFiles[]' value='".urlencode( $files[$i] )."' style='display: none;'>";
				echo "</div>";
			echo "</div>";
			}
		echo "</form>";
		}	
	?>	
	</div>

</body>
<script>
var transferID = "<?= $_GET["transferid"] ?>";
var files = new Array();

function selectBox( obj ) {
	console.log( "bent" );
	if( $(obj).hasClass("selected") ) {
		$(obj).removeClass("selected");
		$(obj).find('input:checkbox').prop('checked', false );
		}
		
	else {
		$(obj).addClass("selected");
		$(obj).find('input:checkbox').prop('checked', true );
		}
	}

function allselect( operation ) {
	switch( operation ) {
		case "select":
			$(".previewWrap").each(function(){
				$(this).addClass("selected");
				$(this).find('input:checkbox').prop('checked', true );
				});
			
			$("#selectall").hide(0);
			$("#deselectall").show(0);
			break;
			
		case "deselect":
			$(".previewWrap").each(function(){
				$(this).removeClass("selected");
				$(this).find('input:checkbox').prop('checked', false );
				});
			
			$("#selectall").show(0);
			$("#deselectall").hide(0);
			break;
		}
	}

function downloadfiles() {
	$("#pageselect").submit();
	}
	
</script>

<?php
}
?>