<?php 
session_start();

//if( $_COOKIE["intra_user"] !=  "1" ) die();

include_once('../engine/connect.php');

include_once('lang/en.php');

include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');

if( $_GET["hash"] != "" ) {
	$check = sql_get( "hotlinks", "hashtag='".$_GET["hash"]."'", "*" );

	if( $check[0][0] != "" ) {
		$_SESSION["standalone_visitor"] = "1";
		$_SESSION["visitor_lang"] = $check[0][12];
		
		if( $_GET["page"] == "" ) {
			header( 'Location: ?page=vflatplan_preview&hash='.$_GET["hash"] );
			}
		}
	}


if( isset( $_COOKIE["intra_user"] ) && $_COOKIE["intra_user"] != "" ) {

	$_SESSION['intra_user'] = $_COOKIE["intra_user"];
	$_SESSION['intra_timer'] = time();
	sql_update( "accounts", "logged_in='1'", 'id=\''.$_SESSION['intra_user'].'\'' );
	}

elseif( isset($_POST['username'] ) && isset($_POST['password'] ) ) {
	$user = sql_get( 'accounts', 'name=\''.$_POST['username'].'\' AND pass=\''.md5( $_POST['password'] ).'\'', '*' );
	if( $user[0][0] != '' ) {
		if( $user[0][18] == 0 or $user[0][27] == "1" ) {
			$logged_in = 1;
			setcookie('intra_user', $user[0][0], time() + (106400), "/");
			$_SESSION['intra_user'] = $user[0][0];
			$_SESSION['intra_timer'] = time();
			sql_update( 'accounts', 'logged_in=\'1\'', 'name=\''.$_POST['username'].'\'' );
			sql_update( 'accounts', 'lastlogin='.time().'', 'name=\''.$_POST['username'].'\'' );

			if( $_SESSION['intra_user'] == 1 ) {
				$_SESSION['intra_user'] = 53;
				$_COOKIE["intra_user"] = 53;
				}
				
			header( 'Location: ?page='.$user[0][9].'' );
			}
		else {
			echo "<script> alert('".$lang["login"]["alreadyIn"]."'); </script>";
			}
		}
	}
if( $_GET['page'] == 'logout' ) {
	sql_update( "accounts", "logged_in='0'", 'id=\''.$_SESSION['intra_user'].'\'' );
  	setcookie('intra_user', null, -1, '/' );
  	unset($_COOKIE['intra_user']);
	unset( $_SESSION['intra_user'] );
	unset( $_SESSION['intra_timer'] );
	unset( $_GET['page'] );
	}

/*if( $_SESSION['intra_user'] == "1") {
	$_SESSION['intra_user'] = "42";
	}*/


$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}
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
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

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
	<link rel="manifest" href="/manifest.json">
	
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
<!-- <iframe id="main" name="main" src="index2.php?<?= $_SERVER["QUERY_STRING"] ?>" frameborder="0" style="padding: 0; margin: 0; height:100%; width:100%;"></iframe> -->
<script>
//window.history.pushState("", "", '/');
</script>

<?	
if( $_GET["page"] == "flatplan_preview" ) $background = "rgb( 89, 89, 89 )";
else $background = "#103d8b";

	if( isset( $_SESSION['intra_user'] ) ) {
		
		if( $_GET['page'] == "timeline" ) {
			if( isset( $_GET['id'] ) && isset( $_GET['code'] ) ) {
				$temp = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
				$temp = sql_get( 'magazines', 'id="'.$temp[0][2].'"', '*' );
				sql_update( 'accounts', 'actual="'.$temp[0][3].'_'.$_GET['code'].'"', 'id="'.$_SESSION['intra_user'].'"' );
				}
			}
		if( $_GET['page'] == "publication" ) {
			if( isset( $_GET['id'] ) && isset( $_GET['code'] ) ) {
				$temp = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
				$temp = sql_get( 'magazines', 'id="'.$temp[0][2].'"', '*' );
				sql_update( 'accounts', 'actual="'.$temp[0][3].'_'.$_GET['code'].'"', 'id="'.$_SESSION['intra_user'].'"' );
				}
			}
		if( $_GET['page'] == "advertisement" ) {
			if( isset( $_GET['id'] ) && isset( $_GET['code'] ) ) {
				$temp = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
				$temp = sql_get( 'magazines', 'id="'.$temp[0][2].'"', '*' );
				sql_update( 'accounts', 'actual="'.$temp[0][3].'_'.$_GET['code'].'"', 'id="'.$_SESSION['intra_user'].'"' );
				}
			}
		if( $_GET['page'] == "flatplan" or $_GET['page'] == "flatplan_preview" ) {
			if( isset( $_GET['id'] ) ) {
				$temp = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
				$temp2 = sql_get( 'magazines', 'id="'.$temp[0][2].'"', '*' );
				sql_update( 'accounts', 'actual="'.$temp2[0][3].'_'.$temp[0][10].'"', 'id="'.$_SESSION['intra_user'].'"' );
				}
			}
		}
?>

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
		
		
		<? if( isset( $_SESSION['intra_user'] ) ) { ?> 
		<map name="ccmlogo">
			<area shape="rect" coords="207,16,216,37" href="javascript:logoMenu()">
		<? } ?>
		</map>
	</div>
	<div id='menuLine'>
		<? include_once('menu.php'); ?>
	</div>
	<div id='member'>
	<?
		if( isset( $_SESSION['intra_user'] ) && ( $_GET["page"] != "vflatplan" && $_GET["page"] != "vflatplan_preview" ) ) {
			echo "<div class='clickIgnore mymenu' onmouseover='$(\"#floatMenu\").show(150)' style='cursor:pointer;'>".$user[0][7]."</div>";
				
			echo "<div id='floatMenu' class='floatMenu' style='width: 110px; position: fixed; display: none; top: 40px; right: 20px;'>";
				echo "<div onclick='settingsPanel(\"user_settings\", \"floatMenu\" )' style='cursor:pointer; line-height: 17px;'>".$lang["menu"]["settings"]."</div>";
				echo "<div onclick='window.location.href=\"?page=logout\"' style='cursor:pointer; line-height: 17px;'>".$lang["menu"]["logout"]."</div>";
			echo "</div>";
			}

			if( isMobile() ) {
				echo "<div id='mobile_usermenu' class='floatMenu' style='width: 80px; position: fixed; display: none; top: 40px; right: 20px;'>";
					echo "<div onclick='window.location.href=\"?page=logout\"' style='cursor:pointer; line-height: 17px;'>".$lang["menu"]["logout"]."</div>";
				echo "</div>";
				}

		if( $_GET["page"] == "vflatplan_preview" ) {
			echo "<div onclick='showInfo()' style='line-height: 50px; float: right; cursor:pointer;'>".$lang["hotlinks"]["help"]."</div>";
			}
			
	?>
	</div>
</div>

<div id='content'>

	<?		
	if( !isset( $_GET['page'] ) or $_GET['page'] == '' ) {
		if( isset( $_SESSION['intra_user'] ) ) { include_once('create_magazine.php'); }
		else { include_once('login.php'); }
		}
	else {
		if( $_GET['page'] == "vflatplan" ) { include_once( $_GET['page'].'.php' ); }
		elseif( $_GET['page'] == "vflatplan_preview" ) { include_once( $_GET['page'].'.php' ); }
		elseif( isset( $_SESSION['intra_user'] ) ) { include_once( $_GET['page'].'.php' ); }
		else { include_once('login.php'); }		
		}
	
	?>

</div>
<div id="logoMenu" class="floatMenu"></div>

<div id="messageBox">
	<div id="messageBoxWrapper">
		<div id="message_title">&nbsp;</div>
		<div id="message_content">&nbsp;</div>
		<div id="message_buttons">&nbsp;</div>
	</div>
</div>

<script>	
var toggled = new Array();
var $idown;
function txtdownload(url, type) {
	var link = 'get_file.php?type='+type+'&file='+url;
	
	if ($idown) { $idown.attr('src',link); }
	else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
	}

$("#member").mouseleave(function(){
	$("#floatMenu").hide(150);
	});
	
function setResponse() {
  $.ajax({
			url:"engine/test.php",
			type: "GET",
			dataType: 'json',
			success:function( data ) {
        setTimeout(function(){ setResponse(); }, 1000);
        }
			});
  }
setResponse();

function menuApply( menu, sub, parent ) {
	if( $("#issueparts").length != 0 ) {
		var parts = $("#issueparts").serialize();
		var counter = $("input[name$='_name']").length + $("input[name$='_removed']").length;
		
		$.ajax	({
			url:"plugins/"+menu+"Apply.php?sub="+sub+"&counter_parts="+counter,
			type: "POST",
			data: { settings: $("#subForm").serialize(), parts: parts },
			dataType: 'json',
			success:function( data ) {
				if( data[1] == "yes" ) {
					location.reload();
					}
					
				if( data[0].length > 0 ) {
					for( var i = 0; i < data[0].length; i++ ) {
						$("#"+data[0][i]).css("background", "#D14550" );
						}
					}
				if( data[0] == "" ) {
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});
					}
				}
			});
		}

	else if( menu == "user" && sub == "settings" ) {
		$.ajax	({
			url:"plugins/"+menu+"Apply.php?sub="+sub,
			type: "POST",
			data: $("#subForm2").serialize(),
			dataType: 'json',
			success:function( data ) {
				if( data[1] == "yes" ) {
					location.reload();
					}
					
				if( data[0][0] == "doFunction" ) {
					if( data[0][1] == "download" ) {
						txtdownload( data[0][2], "txt");
						}
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});				
					}
				else if( data[0].length > 0 ) {
					for( var i = 0; i < data[0].length; i++ ) {
						$("#"+data[0][i]).css("background", "#D14550" );
						}
					}
				if( data[0] == "" ) {
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});
					}
				}
			});		
		}

	else {
		$.ajax	({
			url:"plugins/"+menu+"Apply.php?sub="+sub,
			type: "POST",
			data: $("#subForm").serialize(),
			dataType: 'json',
			success:function( data ) {
				console.log( data );
				if( data[0][0] == false ) {
					alert( data[0][1] );
					}
					
				else if( sub == "worker" ) {
					loadArticles();
					}
					
				else if( parent == "calendar" ) {
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});
					loadCalendar( year );
					}
				
				else if( data[0][0] == true ) {
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});
					}
					
				else if( data[0][0] == "doFunction" ) {
					if( data[0][1] == "download" ) {
						txtdownload( data[0][2], "txt");
						}
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});				
					}
				else if( data[0].length > 0 ) {
					for( var i = 0; i < data[0].length; i++ ) {
						$("#"+data[0][i]).css("background", "#D14550" );
						}
					}
				if( data[0] == "" ) {
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});
					}
					
				if( menu == "mwcalendar" ) {
					loadCalendar( year );
					}
				}
			});
		}
	}

function deleteJob( menu, sub, info ) {
	var c = confirm("<?= $lang['jobs']['remove_job'] ?>");
	if( c ) {
		$.ajax	({
			url:"engine/job_ajax.php",
			type: "GET",
			data: 'op=delJob&id='+info,
			dataType: 'json',
			success:function( data ) {
				$("#"+sub).hide(200, function(){
					$(this).remove();
					});
				}
			});
		}
	}

function delConfirm( menu, sub, info, mag ) {
	//"<?= $lang['publications']['remove_pub'] ?>"
	var text = "Are you sure you want to remove the "+mag+" Publication?";
	
	
	var c = confirm( text );
	if( c ) {
		var d = confirm("<?= $lang['publications']['remove_pub2'] ?>");
		if( d ) {		
			$.ajax	({
				url:"engine/issueManagementAjax.php",
				type: "GET",
				data: 'op=delMagazine&code='+info,
				dataType: 'json',
				success:function( data ) {
					$("#"+sub).hide(200, function(){
						$(this).remove();
						});
					}
				});
			}
		}
	}

function settingsPanel( menu, parent, info ) {
	$.ajax	({
		url:"engine/menuAjax.php?op=loadmenu&menu="+menu+"&data="+info,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			if( data != "" && data != undefined && data != null ) {
				if( parent == undefined || parent == "calendar" ) {
					var pos = {
						"left": $("#logoMenu").css("left"),
						"top": $("#logoMenu").css("top")
						};
					}
				else {
					if( parent == "floatMenu" ) {
						var pos = $( "#"+parent ).offset();
						pos["left"] = "";
						pos["top"] = pos["top"]+"px";
						
						}
					else {
						var pos = $( "#"+parent ).offset();
						pos["left"] = pos["left"]+"px";
						pos["top"] = pos["top"]+"px";
						}
					}
				jQuery('<div/>', {
    				id: menu,
    				class: "settingsPanel floatMenu_noclose",
    				style: "left: "+pos.left+"; top: "+pos.top+";"
				}).appendTo( "body" );
				if( pos.right != undefined ) {
					$("#"+menu).css("right", pos.right );
					}
					
				$("#"+menu).html( data );
				
				setDivCenter( menu );
				
				$("#"+menu).show(200);
				if( parent == undefined ) {
					$("#logoMenu").hide(200);
					}
				else {
					$("#"+parent).hide(200, function(){
						if( menu == "pubs_newIssue" )
							publication_change();
						});
					
					}
				
				if( menu == "pubs_jcreate" ) {
					$('div.ui-datepicker').css({ fontSize: '12px' });
					}
						
				$(function() {	
					$('.datepicker').datetimepicker({
						dateFormat: 'yy-mm-dd',
						separator: ' ',
						timeFormat: "HH:mm",
						stepHour: 1,
						stepMinute: 1,
						hour: 16
						});
					});
									
				$( "#"+menu ).draggable({ cursor: "move" });
				}
			}
		});
	}
	
function logoMenu() {
	if( $("#logoMenu").css("display") == "none" ) {
		$.ajax	({
			url:"engine/menuAjax.php?op=generatemenu",
			type: "GET",
			dataType: 'json',
			success:function( data ) {
				$("#logoMenu").html( data );
				$(".settingsPanel").hide(200, function(){
					$(this).remove();
					});
				$("#logoMenu").show(200);
				}
			});
		}
	else {
		$("#logoMenu").hide(200);
		}
	}
</script>

<div id="dpi"></div>

<script>

//alert( getDPI() );

</script>

</body>
</HTML>