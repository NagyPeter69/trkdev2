<!DOCTYPE HTML>
<head>
<?php 
session_start();

//if( $_COOKIE["intra_user"] !=  "1" ) die();

include_once('../engine/connect.php');

include_once('../engine/engine.php');
include_once('../engine/xml_handler.php');

$login_error = "";
if( $_GET['page'] == 'logout' ) {
	sql_update( "accounts", "logged_in='0'", 'id=\''.$_SESSION['intra_user'].'\'' );
	clearRememberToken( $_SESSION['intra_user'] );
  	setcookie('intra_user', null, -1, '/' );
  	unset($_COOKIE['intra_user']);
	unset( $_SESSION['intra_user'] );
	unset( $_SESSION['intra_timer'] );
	unset( $_GET['page'] );
	echo "<script>window.parent.location = 'https://".URL."';</script>";
	}

if( $_GET["hash"] != "" ) {
	$check = sql_get( "hotlinks", "hashtag='".$_GET["hash"]."'", "*" );
	if( $check[0][0] != "" ) {
		$_SESSION["standalone_visitor"] = "1";
		$_SESSION["visitor_lang"] = $check[0][12];
		
		if( $_GET["page"] == "" ) {
			header( 'Location: ?page=vflatplan_preview&hash='.$_GET["hash"] );
			}
		}
	else {
		$check = sql_get( "adhoc_hotlinks", "hash='".$_GET["hash"]."'", "*" );
		if( $check[0][0] != "" ) {
			$_SESSION['intra_user'] = $check[0][1];
			issueRememberToken( $check[0][1] );
			$_SESSION['intra_timer'] = time();
			
			
			if( $check[0][6] == "page=assets" ) {
				$_SESSION['visitor_pub'] = $check[0][7];
				header( 'Location: ?page=assets' );
				}
			else {
				sql_update( "accounts", "logged_in='1'", 'id=\''.$_SESSION['intra_user'].'\'' );
						
				if( !empty( $check[0][6] ) ) {
					header( 'Location: ?'.$check[0][6] );
					die();
					}
				else {
					$temp = sql_aget( "accounts", "id='".$check[0][1]."'", "*" );
					if( $temp[0]["type"] == "adhoc" ) {
						header( 'Location: ?page=filetransfer&id='.$temp[0]["temppubid"].'&type=pub' );
						die();
						}
					}
				}
			}
		}
	}

if( isset( $_COOKIE["intra_user"] ) && $_COOKIE["intra_user"] != "" ) {
	$resolvedId = resolveRememberToken( $_COOKIE["intra_user"] );
	if( $resolvedId !== null ) {
		$_SESSION['intra_user'] = $resolvedId;
		$_SESSION['intra_timer'] = time();
		// Only stamp a new session token if this session doesn't already carry
		// one - an already-active session re-resolving its own cookie on every
		// request must not keep reclaiming ownership from a genuinely newer login.
		if( !isset( $_SESSION['session_token'] ) ) {
			$_SESSION['session_token'] = issueSessionToken( $resolvedId );
			}
		sql_update( "accounts", "logged_in='1'", 'id=\''.$resolvedId.'\'' );
		}
	else {
		setcookie( 'intra_user', null, -1, '/' );
		unset( $_COOKIE['intra_user'] );
		}
	}

elseif( isset($_GET['username'] ) && isset($_GET['password'] ) ) {
	$escUser = mysqli_real_escape_string( $con, $_GET['username'] );
	$user = sql_get( 'accounts', 'name=\''.$escUser.'\'', '*' );
	if( !empty( $user[0][0] ) && checkPassword( $_GET['password'], $user[0][2], $user[0][0] ) ) {
		session_regenerate_id( true );
		$logged_in = 1;
		issueRememberToken( $user[0][0] );
		$_SESSION['intra_user'] = $user[0][0];
		$_SESSION['intra_timer'] = time();
		$_SESSION['session_token'] = issueSessionToken( $user[0][0] );

		sql_update( 'accounts', 'logged_in=\'1\'', 'id=\''.$user[0][0].'\'' );
		sql_update( 'accounts', 'lastlogin='.time().'', 'id=\''.$user[0][0].'\'' );
		}
	}

elseif( isset($_POST['username'] ) && isset($_POST['password'] ) ) {
	if( empty( $_POST['csrf_token'] ) || empty( $_SESSION['csrf_token'] ) || !hash_equals( $_SESSION['csrf_token'], $_POST['csrf_token'] ) ) {
		$login_error = "Your session expired, please reload the page and try again";
		}
	else {
	$escUser = mysqli_real_escape_string( $con, $_POST['username'] );
	$user = sql_get( 'accounts', 'name=\''.$escUser.'\'', '*' );
	if( !empty( $user[0][0] ) && checkPassword( $_POST['password'], $user[0][2], $user[0][0] ) ) {
		if( $user[0][18] == 0 or $user[0][27] == "1" ) {
			session_regenerate_id( true );
			$logged_in = 1;
			issueRememberToken( $user[0][0] );
			$_SESSION['intra_user'] = $user[0][0];
			$_SESSION['intra_timer'] = time();
			$_SESSION['session_token'] = issueSessionToken( $user[0][0] );
			sql_update( 'accounts', 'logged_in=\'1\'', 'id=\''.$user[0][0].'\'' );
			sql_update( 'accounts', 'lastlogin='.time().'', 'id=\''.$user[0][0].'\'' );


			if( $_SESSION['intra_user'] == 1 ) {
				$_SESSION['intra_user'] = 229;
				$_COOKIE["intra_user"] = 229;
				}

			header( 'Location: ?page='.$user[0][9].'' );
			}
		else {
			// $lang isn't loaded until later in this file (it depends on the session
			// user, which isn't resolved on this POST) - load the attempting user's
			// own language file here directly, so this message isn't left blank.
			include( !empty( $user[0][17] ) ? 'lang/'.$user[0][17].'.php' : 'lang/en.php' );
			// Not every lang file has alreadyInQuestion translated yet - fall back
			// to the English question rather than leaving the sentence hanging.
			$alreadyInQuestion = $lang["login"]["alreadyInQuestion"] ?? 'Would you like to log off on that machine?';
			$alreadyInMsg = json_encode( $lang["login"]["alreadyIn"]." ".$alreadyInQuestion );
			echo "<script>";
				echo "if( confirm( ".$alreadyInMsg." ) ) {
						window.location.href = 'index.php?username=".urlencode($_POST['username'])."&password=".urlencode($_POST['password'])."';
						}";
			echo "</script>";
			}
		}
	else {
		$check = sql_aget( "accounts", "name='".$escUser."'", "*" );
		if( !empty( $check[0]["id"] ) ) {
			$login_error = "Invalid Password";
			}
		else {
			$login_error = "Invalid Username";
			}
		securityAlert( $_POST['username'], $_POST['password'] );
		}
	}
	}


if( $_SESSION['intra_user'] == "1") {
	//$_SESSION['intra_user'] = "351";
	}


$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );

	// multiplelogin=0 accounts get exactly one live session: if a newer login
	// elsewhere has since stamped a different session_token, this session was
	// the one superseded - kill it here instead of leaving it silently valid.
	if( !empty( $user[0][0] ) && $user[0][27] == "0" && !sessionTokenValid( $_SESSION['intra_user'], $_SESSION['session_token'] ?? '' ) ) {
		session_unset();
		session_destroy();
		echo "<script>window.top.location.reload();</script>";
		exit;
		}

	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}
	
if( !empty( $user[0][17] ) ) {	
	include_once('lang/'.$user[0][17].'.php');	
	}
else {
	include_once('lang/en.php');	
	}	
?>
	<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0">	
	<link href="../css/default.php" rel="stylesheet" type="text/css" />
	<link href="../css/menu.php "rel="stylesheet" type="text/css" />
	<link href="css/client.css" rel="stylesheet" type="text/css" />
	<link rel="stylesheet" href="css/all.css" type="text/css" >
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
<?
$chat = true;	
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
<div id="mainPage">
	<div id='header' <?= ( HOST == "Dev" ? "style='background-color: #000;'" : "" ) ?>>
		<div class="logo" style='height: 50px; background: <?= $background ?>;' >
			<?php if( isMobile() ) { ?>
				<div class="logo-in" style="padding: 15px;" onclick='$("#mobile_usermenu").show(150)'>
					<div class="logo-in" style="pointer-events: none; display: inline-block;"><?php include( "images/SVG/TRK.svg" ); ?></div>
					<div class="logo-in" style="pointer-events: none; float: right;"><?php include( "images/SVG/hambi.svg" ); ?></div>
				</div>
			<?php } else { ?>
				<img id='trklogoimage' src='images/ccmlogo.png' usemap="#ccmlogo">
			<?php } ?>
			
			
			<? if( isset( $_SESSION['intra_user'] ) && $user[0][36] != 'Temp' ) { ?>
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
				if( $user[0][4] != 0 ) {
					echo "<div class='clickIgnore mymenu' onmouseover='$(\"#floatMenu\").show(150)' style='cursor:pointer;'>".$user[0][7]."</div>";
						
					echo "<div id='floatMenu' class='floatMenu' style='width: 110px; position: fixed; display: none; top: 40px; right: 20px;'>";
						echo "<div onclick='settingsPanel(\"user_settings\", \"floatMenu\" )' style='cursor:pointer; line-height: 17px;'>".$lang["menu"]["settings"]."</div>";
						echo "<div onclick='window.location.href=\"?page=logout\"' style='cursor:pointer; line-height: 17px;'>".$lang["menu"]["logout"]."</div>";
					echo "</div>";
					}
				else {
					echo "<div class='clickIgnore mymenu' onclick='window.location.href=\"?page=logout\"' style='cursor:pointer;'>".$lang["menu"]["logout"]."</div>";
					}
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
		
		<?php if( ( $_SESSION['intra_user'] == "1" OR $_SESSION['intra_user'] == "7" ) && !isMobile() ) { ?>
			<div id="freespace">
				<?php
					$total = disk_total_space("/var/www/html/client");
					$bytes = disk_free_space("/var/www/html/client");
					$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
					$base = 1024;
					$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
					$percent = $bytes / $total * 100;
					
					if( $percent <= 5 ) {
						$color = "rgba(251, 54, 64, 1)";
						}
					elseif( $percent > 5 && $percent <= 10 ) {
						$color = "rgba(247, 244, 41, 1)";
						}
					else {
						$color = "rgba(2, 174, 14, 1)";
						}
					
					echo "<span>Storage:</span> <span style='color: ".$color.";'>".( sprintf('%1.0f' , floor( $bytes / pow($base,$class) ) ) )." ". $si_prefix[$class]."</span>";
				?>
			</div>
		<?php } ?>	

		<?php
		if( $user[0][4] == "6" && ( empty( $_GET["page"] ) or $_GET["page"] == "create_magazine" ) && !isMobile() ) {
			echo "<div id='menuClientBox'>";
				echo "<select name='menuClient' style='margin-left: 5px;'>";
					echo '<option value="-1">All</option>';
					echo '<option '.( $user[0][33] == "0" ? "selected" : "" ).' value="0">Adhoc</option>';
					
					$pubs = sql_aget( "publishers", "1 order by name ASC", "*" );	
					for( $i = 0; $i < count( $pubs ); $i++ ) {
						echo "<option ".( $user[0][33] == $pubs[$i]["id"] ? "selected" : "" )." value='".$pubs[$i]["id"]."'>".$pubs[$i]["name"]."</option>";
						}
				echo "</select>";
			echo "</div>";
			echo "<div id='publist'>";
				echo "Client:";
			echo "</div>";
			}
		?>
		
		<?php
		if( $_GET["page"] == "design_wideview" ) {
			echo "<div style='float: right; margin-top: 10px; margin-right: 19px;'>";
				echo '<select style="margin-left: -1px;" onchange="Redirect( $(this).val() )">';
					$pubs = getShortPubs( $user[0] );
		
					for( $i = 0; $i < count( $pubs ); $i++ ) {
						$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code' );
						
						echo "<option value='".$pubs[$i][0]."_".$firstPage[0][0]."_".$firstPage[0][1]."' ";
							if( $_GET['code'] == $pubs[$i][10] && $_GET['id'] == $pubs[$i][0] )
								echo "selected";
						echo ">".$magazine[0][0]." ".$pubs[$i][10]."</option>";
						}
				echo "</select>";
			echo "</div>";			
			}			
		?>
		
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
			elseif( $_GET['page'] == "set_password" ) { include_once( $_GET['page'].'.php' ); }
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
</div>
<?php
if( $chat && $_GET["page"] != "filetransfer" ) {
	/*
	echo "<div id='chatUI' class='bottomChat' ". ( $user[0][32] == "min" ? "style='height: 36px;'" : "" ) ." ". ( $user[0][31] != "bottom" ? "style='display: none; height: 0px;'" : "" ) .">";
		echo "<div class='chatHeader'>";	
			echo '<div style="float: right; margin-right: 19px; margin-top: -2px;"><i onclick="switchChat( \'right\' )" class="fas fa-columns"></i></div>';
			echo '<div style="float: right; margin-right: 15px; margin-top: -2px;"><i onclick="chatNewWindow()" class="fas fa-clone"></i></div>';
			echo '<div class="chatmin" style="float: right; margin-right: 15px; margin-top: -2px; '.($user[0][32] == "min" ? "display: none;" : "").'"><i onclick="chatToggle(\'min\')" class="fas fa-window-minimize"></i></div>';
			echo '<div class="chatmax" style="float: right; margin-right: 15px; margin-top: -2px; '.($user[0][32] == "max" ? "display: none;" : "").'"><i onclick="chatToggle(\'max\')" class="fas fa-window-maximize"></i></div>';
		echo "</div>";
		
		echo "<div class='chatContent' ". ( $user[0][32] == "min" ? "style='display: none;'" : "" ) .">";
		echo "</div>";
	echo "</div>";

	echo "<div id='chatUI' class='rightChat' ". ( $user[0][31] != "right" ? "style='display: none; width: 0px;'" : "" ) .">";
		echo "<div class='chatHeader'>";	
			echo '<div style="float: right; margin-right: 19px; margin-top: -2px;"><i onclick="switchChat( \'bottom\' )" class="far fa-window-maximize"></i></div>';
			echo '<div style="float: right; margin-right: 15px; margin-top: -2px;"><i onclick="chatNewWindow()" class="fas fa-clone"></i></div>';
			echo '<div style="float: right; margin-right: 15px; margin-top: -2px;"><i onclick="chatRightMin()" class="fas fa-window-minimize"></i></div>';
		echo "</div>";
		
		echo "<div class='chatContent'>";
		echo "</div>";
	echo "</div>";
	*/
	}
?>

<div id='trkDialog' style="">
    <div class='dialog_title'>
    </div>
    <input type='button' value='Cancel' id='dialogNo' />
	<input type='button' value='Print Proof' id='dialogYes' />
</div>

<div id='trkDialog2' style="">
    <div class='dialog2_title'>
    </div>
    <input type='button' value='Cancel' id='dialogNo2' />
	<input type='button' value='Ok' id='dialogYes2' />
</div>

<script>
var orient = "bottom";
var urlpage = "<?= $_GET['page'] ?>";	
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

function getFreespace() {
	$.ajax({
		url:"engine/ajax.php?op=getfreespace",
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#freespace").html( data );
			setTimeout(function(){ getFreespace(); }, 1000);
			}
		});
	}
getFreespace();

function setResponse() {
  $.ajax({
			url:"engine/test.php",
			type: "GET",
			dataType: 'json',
			success:function( data ) {
        if( data == "logged_out" ) {
          window.top.location.reload();
          return;
          }
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
						
					$('#applyButt').removeClass('disabled');
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
	
	else if( menu == "flatplan" && sub == "worker" ) {
		$.ajax	({
			url:"plugins/"+menu+"Apply.php?sub="+sub,
			type: "POST",
			data: $("#subForm2").serialize(),
			dataType: 'json',
			success:function( data ) {		
				loadArticles();
				
				if( data[0] == "" ) {
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});
					}				
				}
			});
		}

	else if( menu == "flatplan" &&  sub == "articletypes" ) {
		if( parent == "close" ) {
			$("#"+menu+"_"+sub).hide(200, function(){
				$(this).remove();
				});
			}
		else {
			$.ajax	({
				url:"plugins/"+menu+"Apply.php?sub="+sub,
				type: "POST",
				data: $("#subForm2").serialize(),
				dataType: 'json',
				success:function( data ) {		
					if( data[0][0] == false ) {
						alert( data[0][1] );
						}
					else if( data[0].length > 0 ) {
						for( var i = 0; i < data[0].length; i++ ) {
							$("#"+data[0][i]).css("background", "#D14550" );
							}
						}
					else {
						$("#articleTime").val(15);
						$("#name").val("");
						$("#new_color").css("background","#000000");
						}
					
					loadTypes();	
					loadArticles();							
					}
				});
			}
		}
	
	else {
		console.log( $("#subForm").serialize() );
		$.ajax	({
			url:"plugins/"+menu+"Apply.php?sub="+sub,
			type: "POST",
			data: $("#subForm").serialize(),
			dataType: 'json',
			success:function( data ) {
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
					console.log( data[0] );
					for( var i = 0; i < data[0].length; i++ ) {
						if( data[0][i].indexOf("position_") >= 0 ) {
							var temp = data[0][i].split("_");
							var tar = $('input[name^="position"]')[temp[1]];
							$(tar).css("background", "#D14550" );
							}
						else {
							$("#"+data[0][i]).css("background", "#D14550" );
							}
						}
						
					$('#applyButt').removeClass('disabled');
					}
				if( data[0] == "" ) {
					$("#"+menu+"_"+sub).hide(200, function(){
						$(this).remove();
						});
					}

				if( menu == "pubs" && sub == "create" && data[0] == "" ) {
					// The new publication only exists in the Publications
					// View grid after a fresh page load - nothing else in
					// this dialog's success path re-fetches that list, so
					// without this the freshly created job is invisible
					// until the user manually reloads.
					location.reload();
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
	var text = "<?= $lang['publications']['remove_pub'] ?>";
	text = text.replace( "[KIADVANY]", mag );
	
	var c = confirm( text );
	if( c ) {
		var text = "<?= $lang['publications']['remove_pub2'] ?>";
		text = text.replace( "[KIADVANY]", mag );		
		var d = confirm( text );
		
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
				
				if( menu != "mwcalendar_specdates" ) {
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
					}
									
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
	
function trkDialog(message, yesCallback, noCallback ) {
    $('.dialog_title').html(message);
    var dialog = $('#trkDialog').dialog();

    $('#dialogYes').click(function() {
        dialog.dialog('destroy');
        yesCallback();
		});
    $('#dialogNo').click(function() {
        dialog.dialog('destroy');
		
        noCallback();
		});
	}

function trkDialog2(message, yesCallback, noCallback ) {
    $('.dialog2_title').html(message);
    var dialog = $('#trkDialog2').dialog();

    $('#dialogYes2').click(function() {
        dialog.dialog('destroy');
        yesCallback();
		});
    $('#dialogNo2').click(function() {
        dialog.dialog('destroy');
		console.log( dialog );
        noCallback();
		});
	}

//trkDialog('Are you sure you want to do this?')
</script>

<div id="dpi"></div>

<script>

//alert( getDPI() );

</script>
</body>