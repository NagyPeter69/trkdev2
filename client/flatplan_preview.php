<?
$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*');

if( $user[0][26] == 0 ) {
	$user[0][15] = "trimbox";
	}

if( isMobile() ) {
	$user[0][15] = "trimbox";
	}
	
$pageSeparate = $user[0][14];
$_GET["part"] = $_SESSION["fp_part"];

if( $_GET['id'] == '' ) {
	$userCache = explode( "_", $user[0][11] );
	$gen = sql_get( 'magazines', 'code="'.$userCache[0].'"', 'id, code' );
	$gen2 = sql_get( 'publications', 'magazine_id="'.$gen[0][0].'" AND code="'.$userCache[1].'"', '*' );
	
	if( empty( $gen2[0][0] ) ) {
		$gen2 = sql_get('publications', 'magazine_id="'.$gen[0][0].'" ORDER BY id DESC LIMIT 1', '*');
		sql_update( 'accounts', 'actual="'.$gen[0][1].'_'.$gen2[0][10].'"', 'id="'.$_SESSION['intra_user'].'"' );
		}
	
	$_GET['id'] = $gen2[0][0]; 
	}

if( isset( $_GET['code'] ) ) {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" AND code="'.$_GET['code'].'"', '*' );
	}
else {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
	$_GET['code'] = $pub[0][10];
	}

$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );

if( !empty( $pub[0][0] ) ) {
	if( !checkOwner( array( 'publications', 'id', $pub[0][0] ), $user ) ) {
		header('Location: ' . $_SERVER['HTTP_REFERER']);
		}
	}
else {
	$pubs = getShortPubs( $user[0] );
	$pub = sql_get( 'publications', 'id="'.$pubs[0][0].'"', '*' );
	$_GET['code'] = $pub[0][10];
	$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
	}
 
$time = strtotime( $pub[0][11] );
setlocale(LC_ALL,'hungarian');
$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) ); 	

if( !isset( $_GET['alter'] ) ) {	
	switch( $user[0][19] ) {
		case "FIN":
			$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
			$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="1" LIMIT 1', '*' );
		
			if( $check[0][0] != "" ) $_GET['alter'] = $user[0][19];
			else $_GET['alter'] = "";
			break;
	
		case "PRE":
			$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
			$check = sql_get( 'pageinfo', 'type="PRE" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="0" LIMIT 1', '*' );
		
			if( $check[0][0] != "" ) $_GET['alter'] = $user[0][19];
			else $_GET['alter'] = "";
			break;
	
		default:
			$_GET['alter'] = "";
			break;
		}
	}

$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );

$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
$xpath = $xml->xpath('/Publications');
foreach($xpath as $temp) {
	for( $i = 0; $i < count( $temp->Item ); $i++ ) {
		if( $temp->Item[$i]->Code == $magazine[0][3] )
			break;
		}
	}
		
$pn = (string) $xml->Item[$i]->PageNumbering;

if( $magazine[0][10] == "Regular" ) {
	$_GET["part"] = "";
	}

if( $magazine[0][10] == "Adhoc" ) {
	if( $pn == "European" ) {
		$_GET["part"] = "";
		}
	}	

$part = "";
if( $_GET["part"] != "" ) {
	$part = 'AND part="'.$_GET["part"].'"';
	}

if( $_SESSION['intra_user'] == "1") {
	//var_dump( $magazine );
	}
	
if( $_GET['alter'] != '' and $_GET['alter'] != 'FIN' ) {
	$packs = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'"AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" ORDER BY CAST(`page` AS SIGNED) ASC', '*' );
	}
else {
	$fin = ( $_GET['alter'] == 'FIN' ? "1" : "0" );
	$packs = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND fin="'.$fin.'" ORDER BY CAST(`page` AS SIGNED) ASC', '*' );
	}

	
$ad_pages = array();
for( $i = 0; $i < count( $packs ); $i++ ) {
	$pages[] = str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1];
	if( $packs[$i][6] == 'ad' ) {
		$ad_pages[] = $packs[$i][5];
		}
	}

if( $_GET['clk'] == '' or $_GET['p'] == '' ) {
	switch( $user[0][19] ) {
		case "PRE":
			$condition = 'type="PRE" AND code="'.$magazine[0][3].'" AND issue="'.$_GET['code'].'" AND fin="0" '.$part.' ORDER BY `page` ASC';
			break;
		
		case "FIN": 
			if( $magazine[0][3] == "BAV" ) {
				$condition = '( type="ad" OR type="magazine" ) AND code="'.$magazine[0][3].'" AND issue="'.$_GET['code'].'" AND fin="0" '.$part.' ORDER BY `page` ASC';
				}
			else {
				$condition = '( type="ad" OR type="magazine" ) AND code="'.$magazine[0][3].'" AND issue="'.$_GET['code'].'" AND fin="1" '.$part.' ORDER BY `page` ASC';
				}
			break;
		
		default: 
			$condition = '( type="ad" OR type="magazine" ) AND code="'.$magazine[0][3].'" AND issue="'.$_GET['code'].'" AND fin="0" '.$part.' ORDER BY `page` ASC';
			break;
		}
	//echo $condition;

	if( $_SESSION['intra_user'] == "1") {
		//var_dump( $condition );
		}
	$firstPage = sql_get( 'pageinfo', $condition, 'id,page' );
	/*echo "<pre>";
	var_dump( $firstPage );
	echo "</pre>";
	die();*/
	$_GET['clk'] = $_GET['p'] = $firstPage[0][1];
	}
	
$path = "packages/".$magazine[0][3]."/".$issue[0][10];
if( $_GET['alter'] == "FIN" ) $path .= "/FIN";
//var_dump(  $_GET['id'], $_GET["pack_id"], $_GET["p"], $_GET['tag'], $_GET['alter'], null, $issue, $magazine, $_GET['part'], $_GET["pageNumbering"] );
$pages = checkPagePair( $_GET['id'], $_GET["pack_id"], $_GET["p"], $_GET['tag'], $_GET['alter'], null, $issue, $magazine, $_GET['part'], $_GET["pageNumbering"] );

if( $pageSeparate == 'single' ) {
	$key = array_search( $_GET['clk'], $pages );
	if( $key !== false ) {
		$pages = array( $pages[$key] );
		$_GET['p'] = $pages[0];
		}
	}
sort( $pages );
/*	
if( count( $pages ) == 0 ) {
	echo $_GET['id']." ".$_GET["pack_id"]." ".$_GET["p"]." ".$_GET['alter'];
	die();
	//header( "location: ?page=flatplan" );
	}
*/

?>

<link rel="stylesheet" href="https://malihu.github.io/custom-scrollbar/jquery.mCustomScrollbar.min.css" />
<link rel="stylesheet" href="css/jquery-ui.css">
<link rel="stylesheet" href="css/rangeslider.css">
<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
<script src="https://malihu.github.io/custom-scrollbar/jquery.mCustomScrollbar.concat.min.js"></script>
<script type="text/javascript" src="js/preview3.js"></script>
<script type="text/javascript" src="js/jquery.kinetic.js"></script>
<script type="text/javascript" src="js/rangeslider.js"></script>
<script type="text/javascript" src="js/jquery.zclip.min.js"></script>
<script type="text/javascript">

var compareMode = "off";
jQuery(document).ready(function(){
	$("body").css("background", "rgb(178, 178, 178)");
    $( document ).tooltip({
        tooltipClass: "floatMenu"
        });

    $("[title]").each(function(){
    	$(this).tooltip({ tooltipClass: "floatMenu", content: $(this).attr("title")} );
		});

    if(window.addEventListener) {
        document.addEventListener('DOMMouseScroll', moveObject3, false);
		}
    document.onmousewheel = moveObject3;
});	
</script>

<?php if( isMobile() ) { ?>
<div id="headerExtraLine">
	<div style='display: inline-block; margin-left: -1px; font-size: 14px; text-align:left; margin-top: -5px; position: absolute; left: 0px;'>
		<?
			if( !isset( $_GET['opt'] ) ) {
				switch( $user[0][19] ) {
					case "FIN":
						$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
						$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" '.$part.' LIMIT 1', '*' );
		
						if( $check[0][0] != "" ) $_GET['opt'] = $user[0][19];
						else $_GET['opt'] = "";
						break;
	
					case "PRE":
						
						$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
						$check = sql_get( 'pageinfo', 'type="PRE" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" '.$part.' LIMIT 1', '*' );
						
						if( $check[0][0] != "" ) $_GET['opt'] = $user[0][19];
						else $_GET['opt'] = "";
						break;
	
					default:
						$_GET['opt'] = "";
						break;
					}
				}
			else {
				sql_update( 'accounts', 'lastOpt="'.$_GET['opt'].'"', 'id="'.$user[0][0].'"' );
				}
			$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
			$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $magazine[0][3] ) {
						break;
						}
					}
				}
			
			$pdfstand = (string) $xml->Item[$x]->PDFstandard;
			$process = (string) $xml->Item[$x]->Workflow;
			if( $process != "Softproof" ) {
				$flatplans = array(
					"PRE" => 'pre',
					"" => 'basic',
					"FIN" => 'final'
					);
				}
			//$_GET['opt'] = $_GET['alter'];

			foreach( $flatplans as $key => $val ) {
				$valid = true;
				if( $key == "FIN" ) {
					$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="1" '.$part.' LIMIT 1', '*' );
					
					if( $check[0][0] == "" ) $valid = false;
					}				
				else if( $key != "" ) {									
					$check = sql_get( 'pageinfo', 'type="'.$key.'" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" '.$part.' LIMIT 1', '*' );
	
					if( $check[0][0] == "" ) $valid = false;
					}

				if( $valid ) {
					echo "<div id='alter_".$key."' class='mobileFPchoser ";
					if( $_GET['opt'] == $key )
						echo "alterSelected";

					echo "' onclick='window.location.href=\"?page=flatplan".( $_GET["manage"] != "" ? "&manage=".$_GET["manage"] : "" )."&opt=".$key."\"'>".$val."</div>";
					}	
				}
			echo "<div style='clear:both;'></div>";
		?>
	</div>
	
	<div style="display: inline-block;">
	<?php
		echo $magazine[0][3]." ".$issue[0][10];
	?>
	</div>
	
	<div id='loading' style='position: absolute; margin-top: 3px; right: 11px; top: 0px;'>
		<div id="floatingBarsG">
		<div class="blockG" id="rotateG_01">
		</div>
		<div class="blockG" id="rotateG_02">
		</div>
		<div class="blockG" id="rotateG_03">
		</div>
		<div class="blockG" id="rotateG_04">
		</div>
		<div class="blockG" id="rotateG_05">
		</div>
		<div class="blockG" id="rotateG_06">
		</div>
		<div class="blockG" id="rotateG_07">
		</div>
		<div class="blockG" id="rotateG_08">
		</div>
		</div>
	</div>	
</div>

<? }  else { ?>
<div id="headerExtraLine"></div>
<? } ?>

<div id='fpPages'>
	<div class='inner'>
		<?php if( $user[0][4] != 0 ) { ?>
			<div style='float: left; margin-left: -1px;'>
				<select onchange="Redirect( $(this).val() )">
				<?
					$pubs = getShortPubs( $user[0] );
					
					for( $i = 0; $i < count( $pubs ); $i++ ) {
						$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code, type' );

						echo "<option value='".$pubs[$i][0]."_".$firstPage[0][0]."_".$firstPage[0][1]."' ";
						if( $_GET['code'] == $pubs[$i][10] && $_GET['id'] == $pubs[$i][0] )
							echo "selected";
						echo ">".( $magazine[0][1] == "Regular" ? $magazine[0][0]." ".$pubs[$i][10] : $magazine[0][0] )."</option>";
						}
				?>
				</select>
			</div>
			<div style='float: left; margin-left: 5px; margin-top: 1px;'>
				<?
					$pubf = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
					$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name, id, code' );
				?>
				<img title="<?= $magf[0][0] ?><br/>Issue: <?= $pubf[0][10] ?><br/>Deadline: <?= $pubf[0][11] ?>" src="images/icons/info.png" height="20px">
			</div>
			<div style='clear:both;'></div>
		<?php } else {
			$pubf = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
			$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name, id, code' );
		} ?>		

		<?php
		$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $i = 0; $i < count( $temp->Item ); $i++ ) {
				if( $temp->Item[$i]->Code == $magf[0][2] )
					break;
				}
			}
		
		$pdfstand = (string) $xml->Item[$x]->PDFstandard;		
		$pn = (string) $xml->Item[$i]->PageNumbering;

		if( $pn == "American" ) {
			$parts = sql_aget( "parts", "pub_id='".$pubf[0][0]."'", "*" );
							
			echo "<select id='part' name='part'>";
				for( $i = 0; $i < count( $parts ); $i++ ) {
					$l = array_search( $parts[$i]["name"],PARTS );
					echo "<option ".( $_SESSION["fp_part"] == $parts[$i]["name"] ? "selected" : "" )." value='".$parts[$i]["name"]."'>".$lang["parts"][$l]."</option>";
					}
			echo "</select>";
			}
		else {
			echo "<select id='part' name='part' style='display: none;'>";
				echo "<option selected value=''></option>";
			echo "</select>";
			}
		?>
		
		<div style='margin-left: -1px; margin-top: 7px; font-size: 14px; text-align:left;'>
			<?
				if( !isset( $_GET['alter'] ) ) {
					switch( $user[0][19] ) {
						case "FIN":
							$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
							$check = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="1" LIMIT 1', '*' );
							
							if( $check[0][0] != "" ) $_GET['alter'] = $user[0][19];
							else $_GET['alter'] = "";
							break;
						
						case "PRE":
							$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
							$check = sql_get( 'pageinfo', 'type="PRE" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="0" LIMIT 1', '*' );
							
							if( $check[0][0] != "" ) $_GET['alter'] = $user[0][19];
							else $_GET['alter'] = "";
							break;
						
						default:
							$_GET['alter'] = "";
							break;
						}
					}

				$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
				$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $x = 0; $x < count( $temp->Item ); $x++ ) {
						if( $temp->Item[$x]->Code == $magazine[0][3] ) {
							break;
							}
						}
					}
					
				$pdfstand = (string) $xml->Item[$x]->PDFstandard;
				$process = (string) $xml->Item[$x]->Workflow;
				if( $process != "Softproof" ) {
					$flatplans = array(
						"PRE" => 'pre',
						"" => 'basic',
						"FIN" => 'final'
						);
					}			
				$_GET['opt'] = $_GET['alter'];
					
				foreach( $flatplans as $key => $val ) {
					$valid = true;
					if( $key == "FIN" ) {
						$check = sql_get( 'pageinfo', 'code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="1" LIMIT 1', '*' );
						
						if( $check[0][0] == "" ) $valid = false;
						}				
					else if( $key != "" ) {
						$check = sql_get( 'pageinfo', 'type="'.$key.'" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="0" LIMIT 1', '*' );
		
						if( $check[0][0] == "" ) $valid = false;
						}
					
					if( $valid ) {
						echo "<div id='alter_".$key."' class='kindButton ".$val."2 ";
						if( $_GET['opt'] == $key )
							echo "alterSelected";

						echo "' onclick='changeOpt(\"".$key."\")'>".$val."</div>";
						}	
					}
				echo "<div style='clear:both;'></div>";
			?>
		</div>
		
		<div id='compare_panel' style="display: none;"></div>
		
		<div style=' margin-left: -1px; margin-top: 10px; margin-bottom: 10px;'>
			<select name='fpView'>
				<?
				$options = array(
					"all" => $lang["flatplan"]["all"],
					"newComments" => $lang["flatplan"]["newComments"],
					"newUploads" => $lang["flatplan"]["newUploads"],
					"waiting" => $lang["flatplan"]["waiting"],
					"approved" => $lang["flatplan"]["approved"]
					);
				
				foreach( $options as $key => $val ) {
					echo "<option value='".$key."' ";
					if( $user[0][13] == $key )
						echo "selected";
					echo ">".$val."</option>";
					}
				?>
			</select>
		</div>	
	</div>
	<table cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td class="innerPreview" style="background: rgb(178, 178, 178); overflow-x: hidden; overflow-y: auto; width: 194px; display: block; white-space: nowrap; height: 250px;"></td>
		</tr>
	</table>
</div>

<div id='fpToolBox'>
	<? include_once( "plugins/preview_rightPanel.php" ); ?>
</div>

<?

if( $pdfstand == "Web" ) {
	$user[0][15] = "trimbox";
	}
	
$zoom = 100;
$bgDPI = 72;
$file = array();

for( $i = 0; $i < count( $pages ); $i++ ) {
	$dir = "packages/".$magazine[0][3]."/".$issue[0][10];
	$file2 = '';
	$tag = $_GET['tag'];
	if( $_GET['alter'] != "" && $_GET['alter'] != "FIN" ) {
		$dir .= "/_".strtoupper( $_GET['alter'] );

		if( $_GET['tag'] != "" && $pages[$i] == $_GET['clk'] ) {
			$tag = $_GET['tag'];
			$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'" '.$part.'', '*' );
			}
		else {
			$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$pages[$i].'" '.$part.'', '*' );
			}
		$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
		}
	else {
		if( $_GET['tag'] != "" && $pages[$i] == $_GET['clk'] ) {
			$tag = $_GET['tag'];
			$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'" '.$part.'', '*' );
			}
		else {
			if( $_GET['alter'] == "FIN" ) {
				$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="" AND fin="1" AND page="'.$pages[$i].'" '.$part.'', '*' );
				}
			else {
				$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="" AND fin="0" AND page="'.$pages[$i].'" '.$part.'', '*' );
				}
			}
		
		if( $pageinfo[0][6] == "ad" ) {
			$dir .= "/_ads";
			$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."ad_preview.pdf";
			}
		else {
			$pack = sql_get( 'packages', 'id="'.$pageinfo[0][1].'"', '*' );
			$dir .= "/".$pack[0][4];
			if( $_GET['alter'] == "FIN" ) $dir .= "/FIN";
			$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
			}
		}

	if( is_file( $dir."/".$file2 ) ) {
		$viewed = explode( ",", $pageinfo[0][10] );
		if( !in_array( $_SESSION['intra_user'], $viewed ) )
			if( $viewed[0] == "" )
				$viewed[0] = $_SESSION['intra_user'];
			else
				$viewed[] =  $_SESSION['intra_user'];
			
		$viewed = implode( ",", $viewed );
		sql_update( 'pageinfo', 'view="'.$viewed.'"', 'id="'.$pageinfo[0][0].'"' );
			
		$file[$i]["Name"] = $dir."/".$file2;
		$sizes = getBBox( $file[$i]["Name"], "" );
		
		$file[$i]["Right"] = $sizes['Right'];
		$file[$i]["Top"] = $sizes['Top'];
		$file[$i]["Width"] = $sizes['Width'];
		$file[$i]["Left"] = 0;
		$file[$i]["Bottom"] = 0;
		}	
	}

$terminalPath = "/var/www/intra/client";
$postfix = $_SESSION['intra_user'];

if( count( $pages ) > 1 ) {
	$correctionBoxTemp = $user[0][15];
	if( $pdfstand == "Web" ) {
		$correctionBoxTemp = "mediabox";
		}
	
	switch( $correctionBoxTemp ) {
		case 'mediabox':
			$correctionBox[0] = getBBox( $file[0]["Name"], "" );		
			break;
		default:
			$correctionBox[0] = getBBox( $file[0]["Name"], '', $correctionBoxTemp );
			break;
		}

	$sizes = getBBox( $file[0]["Name"], '' );
	$trim[0] = getBBox( $file[0]["Name"], '', 'trimbox' );
	if( empty( $trim ) ) {
		$trim[0] = getBBox( $file[0]["Name"], '', 'mediabox' );
		}

	$sizes['Left'] = $file[0]["Left"] = $correctionBox[0]['Left'];
	$file[0]["Right"] = $trim[0]['Right'];
	$sizes['Right'] = $trim[0]['Right']+1;
	$file[0]["Top"] = $sizes['Top'] = $correctionBox[0]['Top'];
	$file[0]["Bottom"] = $sizes['Bottom'] = $correctionBox[0]['Bottom'];
	$sizes['Width'] = $trim[0]['Right']-$file[0]["Left"];
	$sizes['Width'] = pixel_( $sizes['Width'], $bgDPI );
	$file[0]["Width"] = $sizes['Width'];
	$sizes['Height'] -= 2*$correctionBox[0]['Bottom'];
	$sizes['Height'] = pixel_( $sizes['Height'], $bgDPI );
	
	switch( $correctionBoxTemp ) {
		case 'mediabox':
			$correctionBox[1] = getBBox( $file[1]["Name"], '' );
			break;
		default:
			$correctionBox[1] = getBBox( $file[1]["Name"], '', $correctionBoxTemp );
			break;
		}
	$correctionBox[2] = $user[0][15];
	$sizes = getBBox( $file[1]["Name"], '' );
	$trim[1] = getBBox( $file[1]["Name"], '', 'trimbox' );
	if( empty( $trim ) ) {
		$trim[1] = getBBox( $file[0]["Name"], '', 'mediabox' );
		}	
	$file[1]["Right"] = $sizes['Right'] = $correctionBox[1]['Right'];
	$file[1]["Left"] = $sizes['Left'] = $trim[1]['Left'];
	$sizes['Top'] = $correctionBox[1]['Top'];
	$sizes['Bottom'] = $correctionBox[1]['Bottom'];
	$sizes['Width'] = $sizes['Right']-$trim[1]['Left'];
	$sizes['Width'] = pixel_( $sizes['Width'], $bgDPI );
	$file[1]["Width"] = $sizes['Width'];
	$sizes['Height'] -= 2*$correctionBox[1]['Bottom'];
	$sizes['Height'] = intval( pixel_( $sizes['Height'], $bgDPI ) );
	
	$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] )+( $file[1]["Right"]- $file[1]['Left']);
	}
else {
	$correctionBox[2] = $correctionBoxTemp = $user[0][15];
	$box = getPDFBox( "Mediabox Trimbox Cropbox Bleedbox", $pageinfo );
	if( empty( $box["Bleedbox"] ) ) {
		$box["Bleedbox"] = $box["Trimbox"];
		}
		
	switch( $correctionBoxTemp ) {
		case 'mediabox';
			$differences = array(
				"Left" => ( $box["Cropbox"][0] - $box["Mediabox"][0] ),
				"Bottom" => ( $box["Cropbox"][1] - $box["Mediabox"][1] ),
				"Right" => ( $box["Mediabox"][2] - $box["Cropbox"][2] ),
				"Top" => ( $box["Mediabox"][3] - $box["Cropbox"][3] )
				);
				
			$sizes = array(
				"Left" => ( $box["Cropbox"][0] - $differences['Left'] ),
				"Bottom" => ( $box["Cropbox"][1] - $differences['Bottom'] ),
				"Right" => ( $box["Cropbox"][2] - $differences['Right'] ),
				"Top" => ( $box["Cropbox"][3] - $differences['Top'] )
				);
			$trim[0] = array(
				"Left" => $box["Trimbox"][0],
				"Bottom" => $box["Trimbox"][1],
				"Right" => $box["Trimbox"][2],
				"Top" => $box["Trimbox"][3]
				);
			
			$correctionBox[0] = $differences;	
			$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
			
			$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
			$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );
			break;
		
		case 'trimbox';			
			$differences = array(
				"Left" => ( $box["Trimbox"][0] - $box["Bleedbox"][0] ),
				"Bottom" => ( $box["Trimbox"][1] - $box["Bleedbox"][1] ),
				"Right" => ( $box["Bleedbox"][2] - $box["Trimbox"][2] ),
				"Top" => ( $box["Bleedbox"][3] - $box["Trimbox"][3] )
				);
				
			$sizes = array(
				"Left" => $box["Trimbox"][0]- $differences['Left'],
				"Bottom" => $box["Trimbox"][1] - $differences['Bottom'],
				"Right" => $box["Trimbox"][2] - $differences['Right'],
				"Top" => $box["Trimbox"][3] - $differences['Top']
				);
			$trim[0] = $sizes;

			$correctionBox[0] = $differences;
				
			$sizes['Width'] = $sizes['Right'] - $sizes['Left'];
			$sizes['Height'] = $sizes['Top'] - $sizes['Bottom'];
			$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );
			break;
		}
	$temp = $file[0]["Path"];
	$temp2 = $file[0]["Name"];
	$file[0] = $sizes;
	$file[0]["Path"] = $temp;
	$file[0]["Name"] = $temp2;

// echo "<pre>";
// var_dump( $box );	
// var_dump( $differences );	
// echo "</pre>";
	}

$boxSize = array( 
			"width" => intval( pixel_( $fullSizes, $zoom ) ),
			"height" => intval( pixel_( $sizes['Height'], $zoom ) )
			);
	
$pages2 = array();
$ad_pages = array();
for( $i = 0; $i < count( $packs ); $i++ ) {
	$pages2[] = str_pad( $packs[$i][5] , 3, '0', STR_PAD_LEFT)."_".$packs[$i][1];
	if( $packs[$i][6] == 'ad' ) {
		$ad_pages[] = $packs[$i][5];
		}
	}

sort( $pages2 );

if( $_GET['alter'] != '' && $_GET['alter'] != 'FIN' ) {
	//echo "1";
	$_GET['pack_id'] = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'"AND page="'.$_GET['p'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND '.( $_GET["tag"] != "" ? "state='".$_GET["tag"]."'" : "state=''" ).' '.$part.' LIMIT 1', '*' );
	}
else {
	if( $_GET['alter'] == 'FIN' ) {
		//echo "2";
		$_GET['pack_id'] = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$_GET['p'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND fin="1" AND '.( $_GET["tag"] != "" ? "state='".$_GET["tag"]."'" : "state=''" ).' '.$part.' LIMIT 1', '*' );
		}
	else {
		//echo "3";
		$_GET['pack_id'] = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$_GET['p'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND '.( $_GET["tag"] != "" ? "state='".$_GET["tag"]."'" : "state=''" ).' AND fin="0" '.( $pn == "American" ? "" : "" ).' '.$part.' LIMIT 1', '*' );
		}
	}
//echo 'type!="PRE" AND type!="PSTR" AND page="'.$_GET['p'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND '.( $_GET["tag"] != "" ? "state='".$_GET["tag"]."'" : "state=''" ).' LIMIT 1';
$_GET['pack_id'] = $_GET['pack_id'][0][1];

$needle = array_search( str_pad( $_GET['p'] , 3, '0', STR_PAD_LEFT)."_".$_GET['pack_id'], $pages2 );
//var_dump( $pages2 );	
$prev = 0;
$prev_id = 0;
$next = 0;
$next_id = 0;

$prev_link = $next_link = "?page=flatlpan_preview";

if( $needle !== false ) {
	$prev = intval( $pages2[$needle-1] );
	$temp = explode( "_", $pages2[$needle-1] );
	

		
	$tempSql = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND pack_id="'.$temp[1].'"', '*' );

	$clk = intval( $pages2[$needle-1] ); 
	if( $user[0][14] == "pair" ) {
    if( $prev % 2 != 0 && $prev > 1 && $tempSql[0][9] == 1 ) {
      // $pages2 entries are "PPP_packid" strings (e.g. "006_17"), not plain
      // numbers. PHP 7 compared a non-numeric string like this to an int by
      // extracting its leading numeric run, so "006_17" == 6 was true. PHP
      // 8's "saner string to number comparisons" reversed that: it now
      // stringifies the int instead ("006_17" == "6"), which is always
      // false - silently breaking this pair-mode snap-to-even-page check.
      // intval() restores an explicit integer comparison. See the matching
      // fix (and full writeup) in engine/flatplan_reloadbg.php.
      if( intval( $pages2[$needle-2] ) == ( $prev-1 ) ) {
        $prev = intval( $pages2[$needle-2] );
        $temp = explode( "_", $pages2[$needle-2] );
        }
      }
	}
	$prev_id = $temp[1];

	$next = intval( $pages2[$needle+1] );
	$temp = explode( "_", $pages2[$needle+1] );
	$tempSql = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND pack_id="'.$temp[1].'"', '*' );
	$clk2 = intval( $pages2[$needle+1] );
	if( $user[0][14] == "pair" ) {
	    if( $next == ( intval( $pages2[$needle] )+1 ) && $next % 2 != 0 && $tempSql[0][9] == 1 ) {
    		$next = intval( $pages2[$needle+2] );
			$temp = explode( "_", $pages2[$needle+2] );
    		}

    	$next_id = $temp[1];
    	}
    $next_id = $temp[1];
	}



if( $prev != 0 ) {
	$needle = array_search( $prev, $ad_pages );
	if( $needle !== false ) {
		$prev_link .= "&type=ad";
		}
	if( $_GET['alter'] != '' && $_GET['alter'] != 'FIN' )  {
		$prev_link .= "&alter=".$_GET['alter'];
		}
		
	$prev_link .= "&id=".$_GET['id']."&p=".$prev."&clk=".$clk."&pack_id=".$prev_id;


	}
	
if( $next != 0 ) {
	$needle = array_search( $next, $ad_pages );
	if( $needle !== false ) {
		$next_link .= "&type=ad";
		}
	if( $_GET['alter'] != '' && $_GET['alter'] != 'FIN' )  {
		$next_link .= "&alter=".$_GET['alter'];
		}

	$next_link .= "&id=".$_GET['id']."&p=".$next."&clk=".$clk2."&pack_id=".$next_id;
	}

list( $dtitles, $dcolors ) = getAllColors( "../../".$file[0]["Name"] );	

?>
<div id='content_wrapper' style='position: absolute; left: 229px; overflow: hidden;'>
	<div id='content_box' style='background-color: rgb( 128, 128, 128 ); overflow: hidden;'>
		<div id="fpnopages" style="margin-top: -12.5px; pointer-events: none; display: none; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; background-color: transparent; z-index: 900; vertical-align: middle;">
			<div style="display: table; width: 100%; height: 100%;">
				<div style="display: table-cell; height: 100%; width: 100%; vertical-align: middle; font-size: 16px; color: #FFF;">
					<?= $lang["flatplan"]["nopage"] ?>
				</div>
			</div>
		</div>
		<div id="kep" class='pagePreview' style=' background-image: url(<?= $imgData; ?>); background-size:cover; background-repeat:no-repeat; position: relative; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px;'>
			<div id='backgroundIMGDiv' style='width: 100%; height: 100%; position: absolute;'>
				<?php
							
				if( count( $pages ) == "1" ) {
					switch( $user[0][15] ) {
						case "mediabox":
							$imgData = str_replace( ".pdf", "-cropbox.jpg", $file[0]["Name"] );
							break;
							
						case "trimbox":
							$imgData = str_replace( ".pdf", "-trimbox.jpg", $file[0]["Name"] );
							break;
						}
					
					$imgData = file_get_contents($imgData);
					$imgData = base64_encode($imgData);
					}				

				?>
				
				<img id='backgroundImage' src='<?php /* ( !empty( $imgData ) ? "data:image/jpg;base64,".$imgData : "" ) */ ?>' style='width: 100%; height: 100%;'>
			</div>
						
			<div id='renderedIMG1' style='width: 100% !important; height: 100% !important; position: absolute;'>
				<img id="renderedSRC1" src="" style="position: absolute; left: 0px;">
			</div>
			<div id='renderedIMG2' style='width: 100% !important; height: 100% !important; position: absolute;'>
				<img id="renderedSRC2" src="" style="position: absolute; left: 0px;">
			</div>
			
			<div id='left_state' style='position:absolute; left: 0;'>
				<div id='state_a' style='right: 0; overflow: hidden; background-position: right; background-size:cover; background-repeat:no-repeat; z-index: 20 !important; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px; position: absolute;'>
					<div id='state_a_img_container' style='position: absolute; right: 0;'>
						<img id='state_a_img' src="images/blank.png" style='position: absolute;'>
					</div>
				</div>
				<div id='state_b' style='right: 0; overflow: hidden; background-position: right; background-size:cover; background-repeat:no-repeat; z-index: 10 !important; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px; position: absolute;'>
					<img id='state_b_img' src="images/blank.png" style='position: absolute;'>
				</div>
			</div>
			<div id='state_c' style='right: 0; overflow: hidden; background-position: right; background-size:cover; background-repeat:no-repeat; z-index: 20 !important; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px; position: absolute;'>
				<img id='state_c_img' src="images/blank.png" style='position: absolute; right: 0;'>
			</div>
			<div id='state_d' style='right: 0; overflow: hidden; background-position: right; background-size:cover; background-repeat:no-repeat; z-index: 10 !important; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px; position: absolute;'>
				<img id='state_d_img' src="images/blank.png" style='position: absolute; right: 0;'>
			</div>
			
			<div id='sidebyside' style='position: absolute; left: 0;'>
				<div id='side_a' style=' position: absolute; left: 0; background-size:cover; background-repeat:no-repeat; z-index: 24 !important; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px;'>
					<img id='side_a_img' src="images/blank.png" style='position: absolute;'>
				</div>
				<div id='side_b' style='position: absolute; right: 0; background-size:cover; background-repeat:no-repeat; z-index: 26 !important; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px;'>
					<img id='side_b_img' src="images/blank.png" style='position: absolute;'>
				</div>
				<div id='side_break' style='position: absolute; left: 0; background: #848484; width: 10px; height: 100%; z-index: 25 !important;'></div>
			</div>
			
			<div id="state_a_boxDraw" style="z-index: 21 !important; width: 0px; height: 0px;"></div>
			<div id="state_b_boxDraw" style="z-index: 11 !important;width: 0px; height: 0px;"></div>
			<div id="boxDraw" style="width: 0px; height: 0px;"></div>
		</div>		
		<div id="commenDisplay" style="width: 0px; height: 0px;"></div>
		<div id="commenDraw" style="width: 0px; height: 0px;"></div>
		<div id="errorInfo" style="display: none; width: 100%; top: 60px; position: absolute; margin:0px auto;"></div>
	</div>
</div>

<div id='fpFooter'>
	<?php if( !isMobile() ) { ?>
		<div id='loading' style='position: absolute; margin-top: 8px; right: 11px; top: 0px;'>
			<div id="floatingBarsG">
			<div class="blockG" id="rotateG_01">
			</div>
			<div class="blockG" id="rotateG_02">
			</div>
			<div class="blockG" id="rotateG_03">
			</div>
			<div class="blockG" id="rotateG_04">
			</div>
			<div class="blockG" id="rotateG_05">
			</div>
			<div class="blockG" id="rotateG_06">
			</div>
			<div class="blockG" id="rotateG_07">
			</div>
			<div class="blockG" id="rotateG_08">
			</div>
			</div>
		</div>
	<?php } ?>
	
   	<div id='sbs_v1' class='sbs_ver' style='position: absolute; left: 0px; display: none;'></div>
   	<div id='sbs_v2' class='sbs_ver' style='position: absolute; left: 0px; display: none;'></div>
		<div class='footer_content' style='position: relative; left: 15px;'>
	    <div id="zoomdiv">
	      <input id="zoomLevel" onkeypress="return isEnter(event)" class='zoomclass' type='text'>
	      <font color="#FFFFFF" style='font-size: 10px;'>%</font>
	    </div>
		<div id="zoomRange"></div>
			<?php if( !isMobile() ) { ?>
				<div id='mainstatus' class='status1' style='position: absolute; left: 150px;'>
				<?
					$pageID = $text = array();
					$alter = $_GET['alter'];

					$status = checkPageStatus( $pages[0], $_GET['id'], $_GET["pack_id"], $alter, $_GET['tag'], $issue, $magazine, $_GET["part"] );
					$pageID[0] = $status[1];
					if( isMobile() ) {
						
						}
					else {
						switch( $status[0] ) {
							case 2:
								$text[0] = "<div style='color: rgb( 1, 188, 0 ) !important;'>Approved</div>";
								break;
							case 3:
								$text[0] = "<div style='color: rgb( 254, 0, 3 ) !important;'>Rejected</div>";
								break;
							default:
								$text[0] = "<div style='width: 70px;'>";
									if( $rights["acceptPage"] ) {
										$text[0] .= "<div style='cursor: pointer; float: left; margin-top: 2px;'><img onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div><div style='cursor: pointer; float: left;'><img onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
										}
								$text[0] .= "</div>";
								break;
							}
						
						echo $text[0];
						}
				?>
				</div>
			<?php } ?>

		<?php if( isMobile() ) { ?>
			<!--- <div class='mobileToolBox' data-prev="single" data-next="pair" data-tool="widePage" id="widePage_div" onclick="toggleTool('widePage')" func='widePage'>
				<div class="mobilePagesButton" style="pointer-events: none; height: 35px;">
				<?php 
					ob_start();
					include('images/SVG/widepage.svg');
					echo ob_get_clean();
				?>
				</div>
			</div> -->
			
			<div class='mobileToolBox' data-prev="single" data-next="single" data-tool="pair" onclick="toggleTool('pair')" func='pair'>
				<div class="mobilePagesButton" style="pointer-events: none; height: 35px;">
				<?php 
					ob_start();
					include('images/SVG/spreads.svg');
					echo ob_get_clean();
				?>
				</div>
			</div>
			
			<div class='mobileToolBox' data-prev="pair" data-next="pair" data-tool="single" onclick="toggleTool('single')" func='single'>
				<div class="mobilePagesButton" style="pointer-events: none; height: 35px;">
				<?php 
					ob_start();
					include('images/SVG/singlepage.svg');
					echo ob_get_clean();
				?>
				</div>
			</div>	
		<?php } ?>

		<div id='pageInfoBox'>
			<?php if( isMobile() ) { ?>
				<div class='status2' style=''></div>
				<div id='leftArrow' onclick="changePic('<?= $prev_link ?>')" style='display: inline-block; width: 35px; margin-top: -4px;'>
					<i class="fas fa-caret-left" style="font-size: 50px;"></i>
				</div>
			<?php } else { ?>
				<div id='leftArrow' onclick="changePic('<?= $prev_link ?>')" style='cursor: pointer; position: absolute; left: 300px; width: 35px;'>
					<img src='images/icons/leftArrow.png'>
				</div>
				
				<div id='leftArrow_hover' onclick="changePic('<?= $prev_link ?>')" style='display:none; cursor: pointer; position: absolute; left: 300px; width: 35px;'>
					<img src='images/icons/leftArrow_hover.png'>
				</div>
			<?php } ?>
	
			<div class='pages' style='display: inline-block; position: absolute;'>
				<div class='pv1 pageVer'></div>
				<?
					$numb = array();
					for( $i = 0; $i < count( $pages ); $i++ ) {
						$txt = $pages[$i];
						$alterCode = "";
						for( $y = 0; $y < count( $packs ); $y++ ) {
							if( $packs[$y][5] == $pages[$i] ) {
								$alterCode = substr( $packs[$y][8], 0, -1);
								}
							}	
						
						if( $alterCode != "") $txt .= "[".$alterCode."]";
						$numb[] = $txt;
						}
					?>			
				<input type="text" id='pageNr' value='<?= implode( "-", $numb ) ?>' style='padding: 0; border: 0;' onkeypress="return isEnter(event, 'jumpToPage' )" onfocus="this.select();">
				<div class='pv2 pageVer'></div>
			</div>

			<?php if( isMobile() ) { ?>
				<div id='rightArrow' onclick="changePic('<?= $next_link ?>')" style='display: inline-block;  width: 35px; margin-top: -4px;'>
					<i class="fas fa-caret-right" style="font-size: 50px;"></i>
				</div>			
			<?php } else { ?>
				<div id='rightArrow' onclick="changePic('<?= $next_link ?>')" style='z-index: 2; cursor: pointer; position: absolute; left: 300px; width: 35px;'>
					<img src='images/icons/rightArrow.png'>
				</div>
				<div id='rightArrow_hover' onclick="changePic('<?= $next_link ?>')" style='z-index: 2; display:none; cursor: pointer; position: absolute; left: 300px; width: 35px;'>
					<img src='images/icons/rightArrow_hover.png'>
				</div>
			<?php } ?>	
		</div>
		<div id='mainstatus' class='status1' style='position: absolute; left: 150px;'></div>		
		<?php if( !isMobile() ) { ?>
			<div class='status2' style='position: absolute; left: 169px;'>
				<? if( count( $pages ) > 1 ) {
					$alter = $_GET['alter'];
				
					$status = checkPageStatus( $pages[1], $_GET['id'], $_GET["pack_id"], $alter, $_GET['tag'], $issue, $magazine );
					$pageID[1] = $status[1];
					switch( $status[0] ) {
						case 2:
							$text[1] = "<div style='color: rgb( 1, 188, 0 ) !important;'>Approved</div>";
							break;
						case 3:
							$text[1] = "<div style='color: rgb( 254, 0, 3 ) !important;'>Rejected</div>";
							break;
						default:
							$text[1] = "<div style='width: 70px;'>";
								if( $rights["acceptPage"] ) {
									$text[1] .= "<div style='cursor: pointer; float: left; margin-top: 4px;'><img onclick='approvePage( \"".$status[1]."\", \"decline\" )' src='images/decline.png'></div><div style='cursor: pointer; float: left;'><img onclick='approvePage( \"".$status[1]."\", \"accept\" )' src='images/accept.png'></div>";
									}
							$text[1] .= "</div>";
							break;
						}
					echo $text[1];
					} ?>
			</div>				
		<?php } ?>
	</div>
</div>

<div class='custom-menu floatCommentMenu'>
	<div class="ownerOnly" data-action="approve">
		<?
			echo $lang["flatplan"]["approve_comment"];
		?>
	</div>
	<? if( $rights['deleteComment'] or $user[0][8] == "2" ) { ?>
		<div class="ownerOnly" data-action="delete"><? echo $lang["flatplan"]["remove_comment"]; ?></div>
	<? } ?>
	
	<div class="ownerOnly" style="margin-top: 6px; height: 1px !important; margin-bottom: 2px; border-top: 1px solid #636363;"></div>
	<div data-action="comment"><? echo $lang["flatplan"]["reply_comment"] ?></div>
</div>
<input type="hidden" id='renderCounter' value="0" onchange="loadingBar( $(this).val() )">


<script>
var nopages = false;
function jumpToPage( page ) {
	var p = page.split("-");
	var temp = new Array();
	for( var i = 0; i < p.length; i++ ) {
		if( p[i] != "" ) {
			temp.push( p[i] );	
			}
		}
	
	if( fpPages == "pair" ) {
		$("div[page='"+temp[0]+"']").click();
		}
	
	else {
		$("div[page='"+temp[0]+"']").click();
		}
	}

function selectAllText( el ) {
        if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined") {
            var range = document.createRange();
            range.selectNodeContents(el);
            var sel = window.getSelection();
            sel.removeAllRanges();
            sel.addRange(range);
        } else if (typeof document.selection != "undefined" && typeof document.body.createTextRange != "undefined") {
            var textRange = document.body.createTextRange();
            textRange.moveToElementText(el);
            textRange.select();
        }
	}

function loadingBar( val ) {
	console.log( "Rendercounter: "+val );
	if( parseInt( val ) > 0 ) {
		$("#loading").fadeIn( 100 );
		$("#compRange").slider( "disable" );
		
		$('select[name="comp_operation"]').prop('disabled', 'disabled');
		$('#fake').addClass("ui-state-disabled");
		}
	if( parseInt( val ) == 0 ) {
		$("#loading").fadeOut( 100 );
		$("#compRange").slider( "enable" );
		$('select[name="comp_operation"]').prop('disabled', false);		
		$('#fake').removeClass("ui-state-disabled");
		}
		
	if( mobile ) {
		if( $("#renderCounter").val() == 0 ) {
			$('#content_box').kinetic( 'attach' );
			}
		}
	}

var unit = "<?= $user[0][16] ?>";
var zoom = parseInt( '<?= $zoom ?>' );
var cMode = "";

function pixel( number, zoom2 ) {
	if( zoom2 == undefined ) zoom2 = zoom;
	
	return ( ( number * zoom2 ) / 72 );
	}
	
function point( number, zoom2 ) {
	if( zoom2 == undefined ) zoom2 = zoom;
	
	return number * 72 / zoom2;
	}
	
var changer = 2.83464567;
var bleed = parseInt( "<?= $pub[0][15] ?>" );
var si = "<?= $user[0][16] ?>";
var safety_on = "<?= $user[0][25] ?>";
var safety = parseInt( "<?= $user[0][24] ?>" );

var tempBleed =  (bleed*changer);
var tempKifuto = (5*changer);

var oldmaxscroll = {
	"Left" : 0,
	"Top" : 0
	};
var oldZoom = 0;
var lang = {
  'width': '<?= $lang["flatplan"]["width"] ?>',
  'height': '<?= $lang["flatplan"]["height"] ?>',
  'area': '<?= $lang["flatplan"]["area"] ?>',
  'angle': '<?= $lang["flatplan"]["angle"] ?>',
  'percent': '<?= $lang["flatplan"]["percent"] ?>',
  'percent2': '<?= $lang["flatplan"]["percent2"] ?>'
  };
  
var dcolors = {
<?
	for( $i = 0; $i < count( $dcolors ); $i++ ) {
		echo "'".($i+1)."': '".$dcolors[$i]."',";
		}
?>
  };
 <?
	for( $i = 0; $i < count( $dcolors ); $i++ ) {
		echo "colors['".($i+1)."'] = 'true';";
		echo "addPantone( '".($i+1)."', '".$dcolors[$i]."', '".$dtitles[$i]."' );";
		}
?>

$( "#zoomRange" ).slider({
  max: 800,
  min: 60,
  value: zoom,
  slide: function( event, ui ) {
    $( "#zoomLevel" ).val( $( "#zoomRange" ).slider("value") );
    },
  stop: function( event, ui ) {
    var newZoom = parseInt( $( "#zoomRange" ).slider("value") );
    if( newZoom < 1 ) newZoom = 1;
    if( newZoom > 1500 ) newZoom = 1500;
	
    _zoom( '', 'roll', newZoom );
    }
  });
$( "#zoomLevel" ).focusout(function() {
  var newZoom = parseInt( $('#zoomLevel').val() );
  if( newZoom < 1 ) newZoom = 1;
  if( newZoom > 1500 ) newZoom = 1500;

  _zoom( '', 'roll', newZoom ); 
  });
  
$(".innerPreview").mCustomScrollbar({
   theme:"dark"
});

var pageID = new Array();
var statusText = new Array();
<?
	for( $i = 0; $i < count( $pageID ); $i++ ) {
		echo "pageID.push(\"".$pageID[$i]."\");";
		echo "statusText.push(\"".str_replace('"', '\"', $text[$i])."\");";
		}
?>
var txt = "";
var fpFilter = $("select[name='fpView']").val();
var maxWidth = 164;

var pageSeparate = '<?= $user[0][14] ?>';
if( mobile ) {
	$( ".mobileToolBox[data-prev='"+pageSeparate+"']" ).show(0);
	}
else {
	$( "#"+pageSeparate ).attr("src", "plugins/images/"+pageSeparate+"On.png" );
	}

$(function() {
	$("select[name='fpView']").change(function(){
		fpFilter = $(this).val();
		});
	});

var opt = '<?= $alter ?>';
function refreshPageStatus() {
	var Pages = pageID.toString();
	$.ajax	({
		url:"engine/flatplan_ajax.php",
		data: 'op=refreshPageStatus&pagesID='+Pages+'&fpver='+opt+'&intra_user=<?= $_SESSION['intra_user'] ?>&part='+$("#part").val(),
		dataType: 'json',
		success:function( data ) {
			for( var i = 0; i < pageID.length; i++ ) {
				if( data[i] != statusText[i] ) {
					statusText[i] = data[i];
					$( ".status"+(i+1) ).html( data[i] );
					}
				}
			if( i < 2 ) {
				$( ".status2" ).html( '' );
				}
			fit_box();
			$("#pstatus>div>img").mouseenter(function(e) {
				var id = $(this).parent().attr("id");
				var search = id.indexOf("_hover");
				if( search == -1 ) {
					$("#"+id).fadeOut(1);
					$("#"+id+"_hover").fadeIn(1);
					}
				else {
					$("#"+id).fadeIn(1);
					$("#"+id+"_hover").fadeOut(1);
					}
				});
			$("#pstatus>div>img").mouseleave(function(e) {
				var id = $(this).parent().attr("id");
				var search = id.indexOf("_hover");
				if( search == -1 ) {
					$("#"+id).fadeOut(1);
					$("#"+id+"_hover").fadeIn(1);
					}
				else {		
					id = id.substring( 0, search );
					$("#"+id).fadeIn(1);
					$("#"+id+"_hover").fadeOut(1);
					}
				});
			
			setTimeout(function(){ refreshPageStatus(); }, 600);
			}
		});
	}
refreshPageStatus();

function changeOpt( val ) {
	$("#alter_"+opt).removeClass('alterSelected');
	opt = val;
	$("#alter_"+opt).addClass('alterSelected');
	
	$.ajax	({
		url:"engine/flatplan_ajax.php",
		data: 'op=changeOpt&opt='+val+'&intra_user=<?= $_SESSION['intra_user'] ?>',
		dataType: 'json',
		success:function( data ) {
			PageType = val;
			getFirstPage();
			}
		});
	}

$("#part").change(function(){
	loadPages();
	getFirstPage();
	});

function getFirstPage() {
	$.ajax	({
		url:"engine/flatplan_reloadbg.php",
		data: 'op=getFirstPage&intra_user=<?= $_SESSION['intra_user'] ?>&part='+$("#part").val()+'&pageNumbering=<?= $pn ?>&id='+pub+'&alter='+PageType+'',
		dataType: 'json',
		success:function( data ) {
			changePic("?page=flatlpan_preview&type="+data[0][6]+"&alter="+PageType+"&id="+pub+"&p="+data[0][5]+"&clk="+data[0][5]+"&pack_id="+data[0][1]+"");
			}
		});
	}

var firstRun = true;
var realfirstRun = true;
var cache = "<?= time() ?>";
function loadPages() {
	$.ajax	({
		url:"engine/flatplan_ajax.php",
		data: 'op=loadPagePair&type=fpPreview&filter='+fpFilter+'&opt='+opt+'&maxWidth='+maxWidth+'&cache='+cache+'&id=<?= $_GET["id"] ?>&intra_user=<?= $_SESSION['intra_user'] ?>&part='+$("#part").val()+'&pageNumbering=<?= $pn ?>',
		dataType: 'json',
		success:function( data ) {
			//processChecker = false;
			if( txt != data[0] ) {
				$('.innerPreview').html( data[0] );
				
				if( data[2] == "1" ) {
					$("#fpnopages").show(0);
					$(".footer_content, #loading").hide(0);
					nopages = true;
					}
				else {
					$("#fpnopages").hide(0);
					$(".footer_content, #loading").show(0);
					nopages = false;
					}
				
				txt = data[0];
				for( var a = 0; a < data[1].length; a++ ) {
					alterHandle( data[1][a] );
					}
				}

			if( firstRun ) {
				setTimeout(function(){
					if( data[2] == "1" ) {
						top = 0;
						$("#fpnopages").show(0);
						$(".footer_content, #loading").hide(0);
						$("#renderCounter").val(-1);
						nopages = true;
						}
					else {
						var top = ( ( $(".innerPreview").scrollTop() + $("#"+parseInt( page )+"_selector").parent().offset().top )-$(".innerPreview").offset().top ) - ( thumbsScrollCorrection = parseInt( $(".innerPreview").height() )/2 )+$("#"+page+"_selector").parent().height();
						$("#fpnopages").hide(0);
						$(".footer_content, #loading").show(0);
					//	$("#renderCounter").val(-1);
						nopages = false;
						}
					var thumbsScrollCorrection = parseInt( $(".innerPreview").height() )/2;
					
					if( top < 0 ) top = 0;
					
					$('.innerPreview').animate({ scrollTop: ( top ) }, 500);
					firstRun = false;
					if( realfirstRun == true ) {
						reloadBG();
						}
					realfirstRun = false;
					}, 20 );
				}
						
			setTimeout(function(){ loadPages(); }, 1000);
			}
		});
	}
loadPages();

// renderer

var fpBox = {
	'Width': parseInt( $("#content_box").width() ),
	'Height': parseInt( $("#content_box").height() )
	}

var fpPages = '<?= $user[0][14]; ?>';
var pages = '<?= count($pages) ?>';
var viewComment = parseInt( "<?= intval( $rights['viewComment'] ); ?>" );
if( mobile ) {
	viewComment = 0;
	}
	
var replyComment = parseInt( "<?= intval( $rights['replyComment'] ); ?>" );
var trimbox =  {};
var bleedbox =  {};
var cropbox =  {};
var cBox = {};

$('#zoom_in').show( 200 );
$('#zoom_out').show( 200 );

cBox[0] = {<?
	echo "'Left': '".$correctionBox[0]["Left"]."'.replace(',', '.'), ";
	echo "'Bottom': '".$correctionBox[0]["Bottom"]."'.replace(',', '.'), ";
	echo "'Right': '".$correctionBox[0]["Right"]."'.replace(',', '.'), ";
	echo "'Top': '".$correctionBox[0]["Top"]."'.replace(',', '.')";
	?>}
cBox[1] = {<?
	echo "'Left': '".$correctionBox[1]["Left"]."'.replace(',', '.'), ";
	echo "'Bottom': '".$correctionBox[1]["Bottom"]."'.replace(',', '.'), ";
	echo "'Right': '".$correctionBox[1]["Right"]."'.replace(',', '.'), ";
	echo "'Top': '".$correctionBox[1]["Top"]."'.replace(',', '.')";
	?>}
cBox[2] = '<?= $correctionBox[2] ?>';
trimbox[0] = {<?
	echo "'Left': '".$trim[0]["Left"]."'.replace(',', '.'),";
	echo "'Top': '".$trim[0]["Bottom"]."'.replace(',', '.'),";
	?>}
trimbox[1] = {<?
	echo "'Left': '".$trim[1]["Left"]."'.replace(',', '.'),";
	echo "'Top': '".$trim[1]["Bottom"]."'.replace(',', '.'),";
	?>}

trimbox[0] = {<?
	echo "'Left': '".$trim[0]["Left"]."'.replace(',', '.'),";
	echo "'Top': '".$trim[0]["Bottom"]."'.replace(',', '.'),";
	?>}
trimbox[1] = {<?
	echo "'Left': '".$trim[1]["Left"]."'.replace(',', '.'),";
	echo "'Top': '".$trim[1]["Bottom"]."'.replace(',', '.'),";
	?>}

var file = {};
file[0] = {<?
	echo "'Name': '".$file[0]["Name"]."'.replace(',', '.'),";
	echo "'Right': '".$file[0]["Right"]."'.replace(',', '.'),";
	echo "'Top': '".$file[0]["Top"]."'.replace(',', '.'),";
	echo "'Left': '".$file[0]["Left"]."'.replace(',', '.'),";
	echo "'Bottom': '".$file[0]["Bottom"]."'.replace(',', '.'),";
	echo "'Width': pixel( ".$file[0]["Width"]." )";
	?>}

file[1] = {<?
	echo "'Name': '".$file[1]["Name"]."',";
	echo "'Right': '".$file[1]["Right"]."'.replace(',', '.'),";
	echo "'Top': '".$file[1]["Top"]."'.replace(',', '.'),";
	echo "'Left': '".$file[1]["Left"]."'.replace(',', '.'),";
	echo "'Bottom': '".$file[1]["Bottom"]."'.replace(',', '.'),";
	echo "'Width': pixel( ".$file[1]["Width"]." )";
	?>}

var origPic = {
	Width: parseInt( '<?= $fullSizes ?>' ),
	Height: parseInt( '<?= $sizes["Top"] ?>' ),
	};

var defaultSizes = {
	Width: parseInt( '<?= point_( $boxSize["width"], $zoom ) ?>' ),
	Height: parseInt( '<?= point_( $boxSize["height"], $zoom ) ?>' ),
	};

var defaultSizesTrim = {};
if( file[0]['Left'] != "0" ) {
	defaultSizesTrim['Width'] = parseInt( '<?= point_( $boxSize["width"], $zoom ) ?>' );
	defaultSizesTrim['Height'] = parseInt( '<?= point_( $boxSize["height"], $zoom ) ?>' );
	}
if( file[0]['Left'] == "0" ) {

	if( pages > 1 ) {
		defaultSizesTrim['Width'] = (parseFloat(file[0]['Right'])+parseFloat(file[1]['Right']))-trimbox[0]['Left']-trimbox[1]['Left'];
		}
	else {
		defaultSizesTrim['Width'] = parseFloat( file[0]['Right'] )-(2*parseFloat( trimbox[0]['Left']));
		}
	
	defaultSizesTrim['Height'] = parseFloat(file[0]['Top'])-(2*trimbox[0]['Top']);
	}
 
var alphaBoxSize = {
	width: parseInt( pixel( defaultSizes['Width'] ) ),
	height: parseInt( pixel( defaultSizes['Height'] ) )
	}
	
var width, height;
var down = false;
var delayed = 0;
var ajaxDisabled = false;
var img = 1;
var disableZoom = false;
// Safety-net watchdog: disableZoom is the shared busy-guard set true by every
// zoom/nav action (changePic(), _zoom(), reloadBG()) and is meant to be
// cleared by that action's matching render success/error handler. We've had
// reports of it getting permanently stuck true after back-navigation in
// spread/pair mode specifically - no error shown, no recovery short of a full
// page refresh - and despite extensive testing could not pin down and force
// the exact trigger on demand. Rather than leave users stuck, this watchdog
// guarantees recovery regardless of root cause: if the guard has been
// continuously true for longer than any legitimate render takes, force-clear
// it and log the state that led here so a stuck report can be diagnosed from
// the browser console.
var disableZoomWatchdogTicks = 0;
setInterval( function() {
	if( disableZoom ) {
		disableZoomWatchdogTicks++;
		if( disableZoomWatchdogTicks >= 12 ) {
			console.warn( "disableZoom watchdog: forcibly recovering after "+disableZoomWatchdogTicks+"s stuck. page="+page+" clk="+clk+" pack_id="+pack_id+" zoom="+zoom+" fpPages="+fpPages+" renderCounter="+$("#renderCounter").val() );
			disableZoom = false;
			$("#renderCounter").val( 0 ).trigger("onchange");
			$("#boxDraw").css( "display", "none" );
			disableZoomWatchdogTicks = 0;
			}
		}
	else {
		disableZoomWatchdogTicks = 0;
		}
	}, 1000 );
var actualPos = {};
var loadedCommandBindings = false;

var myElement = document.getElementById('kep');

var mc = new Hammer.Manager(myElement);

// create a pinch and rotate recognizer
// these require 2 pointers
var pinch = new Hammer.Pinch();
var rotate = new Hammer.Rotate();

// we want to detect both the same time
pinch.recognizeWith(rotate);

// add to the Manager
mc.add([pinch, rotate]);

$('body').mouseup(function() { 
			if( graphState != "" )
				stopDraw();
			});

$( '#content_box' )
	.mousedown(function() {
		if( graphState == "magnify" )
			startDraw();
		})
	.mouseup(function() { 
		if( graphState == "magnify" )
			stopDraw();
		})
	.mousemove(function(e) { coordinate(e); });

$( '.pagePreview' )
	.mousedown(function() {
		if( graphState != "" )
			startDraw();
		})
	.mouseup(function() { 
		if( graphState != "" )
			stopDraw();
		})
	.mousemove(function(e) { coordinate(e); });

function rendering( command, newZoom ) {
	if( mobile ) {
		//alert( "renderingbe "+pinching );
		}
		
	var currentPos = {
		left: $('#content_box').scrollLeft(),
		top: $('#content_box').scrollTop()
		}
	
	if( currentPos.left != actualPos.left || currentPos.top != actualPos.top || command == "force" ) {	
		ajaxDisabled = true;
		$('#content_box').kinetic( 'detach' );
				
		var boxSize = {
			width: parseInt( pixel( defaultSizes['Width'] ) ),
			height: parseInt( pixel( defaultSizes['Height'] ) )
			}
		
		if( cMode == "SideBySide" ) {
			boxSize.width = boxSize.width*2+9;
			}	
					
		if( boxSize.width != alphaBoxSize.width ) {
			var difW, difH;
			$("#renderedIMG1").hide( 0 );
			$("#renderedIMG2").hide( 0 );	

			setTimeout( function(){
				refreshComments('');
				}, 50);
			
			boxSize = {
				width: parseInt( pixel(  defaultSizes['Width'] ) ),
				height: parseInt( pixel(  defaultSizes['Height'] ) )
				}
						
			if( cMode == "SideBySide" ) {
				boxSize.width = boxSize.width*2+9;
				}					
				
			currentPos = {
				left: $('#content_box').scrollLeft(),
				top: $('#content_box').scrollTop()
				}			
			}	
		var oldScroll = {
			"Left" : $("#content_box").scrollLeft(),
			"Top": $("#content_box").scrollTop()
			};
					
		if( pages == "1" ) {
			$(".pagePreview").css({
				"width": boxSize.width+"px",
				"height": boxSize.height+"px"
				});

			$("#state_a, #state_b, #left_state, #state_a_img_container").css({
				"width": boxSize.width+"px",
				"height": boxSize.height+"px"
				});
			}
		if( pages == "2" ) {
			$(".pagePreview").css({
				"width": boxSize.width+"px",
				"height": boxSize.height+"px"
				});
			$("#state_a, #state_b, #state_c, #state_d, #left_state, #state_a_img_container").css({
				"width": (boxSize.width/2)+"px",
				"height": boxSize.height+"px"
				});					
			}
		console.log( "DEBUG BOXSIZE HEIGHT :"+boxSize.height );	
		$("#compRange").slider( "value", $('#compRange').slider("option", "value") );
								
		switch( newZoom ) {
			case 'button':
			case 'roll':
				var maxScroll = {
					"Left" : parseFloat( $(".pagePreview").width() ) - parseFloat( $("#content_box").width() ),
					"Top" : parseFloat( $(".pagePreview").height() ) - parseFloat( $("#content_box").height() )
					};
				if( maxScroll["Left"] < 0 ) maxScroll["Left"] = 0;
				if( maxScroll["Top"] < 0 ) maxScroll["Top"] = 0;
				var newLeft = parseFloat( maxScroll.Left ) / parseFloat( oldmaxscroll.Left ) * parseFloat( oldScroll.Left );
				var newTop = parseFloat( maxScroll.Top ) / parseFloat( oldmaxscroll.Top ) * parseFloat( oldScroll.Top );
				
				$("#content_box").scrollLeft( newLeft );
				$("#content_box").scrollTop( newTop );
				if( isNaN( newLeft ) && maxScroll.Left > 0 ) {
					$("#content_box").scrollLeft( maxScroll.Left/2 );
					}
				if( isNaN( newTop ) && maxScroll.Top > 0 ) {
					$("#content_box").scrollTop( maxScroll.Top/2 );
					}
				break;
			case 'magnify':
				$("#content_box").scrollLeft( scrollCorrection.left );
				$("#content_box").scrollTop( scrollCorrection.top );
				break;
			}
				
		var positions = {};		
		if( $(".pagePreview").width() > $("#content_box").width() ) {
			positions['left'] = point( $("#content_box").scrollLeft() )-cBox[0]["Left"];
			positions['right'] = positions.left + point( $("#content_box").innerWidth() );
			positions['width'] = $("#content_box").innerWidth();
			
			positions['small'] = "false";
			}
		else {
			if( pages == 1 ) {
				positions['left'] = file[0].Left;
				positions['right'] = file[0].Right;
				positions['width'] = pixel( file[0].Right-file[0].Left );
				positions['small'] = "true";
				}
			if( pages == 2 ) {
				positions['left'] = point( $("#content_box").scrollLeft() )-cBox[0]["Left"];
				positions['right'] = positions.left + point( $("#content_box").innerWidth() );
				positions['width'] = $("#content_box").innerWidth();
				positions['small'] = "false";
				}
			}

		if( $(".pagePreview").height() > $("#content_box").height() ) {
			positions['bottom'] = parseFloat( file[0].Bottom) + point( ( $(".pagePreview").outerHeight(true) )-( $("#content_box").scrollTop() )-( $("#content_box").outerHeight(true) ) ),
			positions['top'] = positions.bottom + point( $("#content_box").innerHeight() );
			positions['height'] = $("#content_box").innerHeight();
			}
		else {
			positions['top'] = file[0].Top;
			positions['bottom'] = file[0].Bottom;
			positions['height'] = pixel( file[0].Top-file[0].Bottom );
			}		
		
		if( pages > 1 )
			correction = {
				0: {
					"Bottom": parseFloat(trimbox[0]["Bottom"])-tempBleed-tempKifuto-cropbox[0]["Bottom"],
					"Left": parseFloat(trimbox[0]["Left"])-tempBleed-tempKifuto-cropbox[0]["Left"]
					},
				1: {
					"Bottom": parseFloat(trimbox[1]["Bottom"])-tempBleed-tempKifuto-cropbox[1]["Bottom"],
					"Left": parseFloat(trimbox[1]["Left"])-tempBleed-tempKifuto-cropbox[1]["Left"]
					}
				}
		else {
			correction = {
				0: {
					"Bottom": parseFloat(trimbox[0]["Bottom"])-tempBleed-tempKifuto-cropbox[0]["Bottom"],
					"Left": parseFloat(trimbox[0]["Left"])-tempBleed-tempKifuto
					}
				}			
			}
			
			
		$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )+1 ) ).trigger("onchange");
		var sendcolors = colors;

		$(".pagePreview").fadeIn( 200 );		
		
		if( !mobile || zoom > 80 ) {
			$("#kep").css("overflow", "hidden");
			if( mobile ) {
				//kep				
				var pan = new Hammer.Pan({ direction: Hammer.DIRECTION_ALL, threshold: 0 });
				mc.remove( pan );
				$('#content_box').kinetic( 'detach' );
				//alert( "bentvagyok" );
				}
			
			$.ajax	({
				url:"tesztAjax.php?zoom="+zoom+"&pubid=<?= $pub[0][0] ?>&intra_user=<?= $_SESSION['intra_user'] ?>&pdfstand=<?= $pdfstand ?>",
				type: "POST",
				data: { positions : positions, file: file, colors: sendcolors, cBox: cBox, fpBox: fpBox, trimbox: trimbox, corr: correction },
				dataType: 'json',
				error:function( data ) {
					console.log( "ez bizony túlcsordult" );
					$.ajax	({
						url:"tesztAjax.php?zoom="+zoom+"&pubid=<?= $pub[0][0] ?>&intra_user=<?= $_SESSION['intra_user'] ?>&pdfstand=Web",
						type: "POST",
						data: { positions : positions, file: file, colors: sendcolors, cBox: cBox, fpBox: fpBox, trimbox: trimbox, corr: correction },
						dataType: 'json',
						timeout: 6000,
						// This fallback (retried after the primary request above
						// already errored/timed out) had no error handler at all -
						// if it also failed, #renderCounter (incremented once before
						// either request was sent) never got decremented, so
						// loadingBar() kept the spinner showing forever with no
						// further render ever completing to clear it. Mirrors the
						// state cleanup the success handlers do, just without an
						// image to show.
						error:function( data ) {
							console.log( "render fallback also failed - clearing renderCounter so the spinner doesn't stick" );
							ajaxDisabled = false;
							$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
							if( graphState == "magnify" ) {
								$( "#zoomRange" ).slider( "option", { disabled: false } );
								}
							disableZoom = false;
							},
						success:function( data ) {
							if( img > 2 ) img = 1;
							
							
							$("#renderedIMG"+img).css({
								left: ( $('#content_box').scrollLeft())+"px",
								top: $('#content_box').scrollTop()+"px",
								});
								
							$("#renderedSRC"+img).attr('src', data[0] );
						
							switch( img ) {
								case 1:
									$("#renderedIMG2").hide( 20 );				
									break;					
								case 2:	
									$("#renderedIMG1").hide( 20 );
									break;
								}
							$("#renderedIMG"+img).fadeIn(0).show(0);
			
							ajaxDisabled = false;
						
							img++;	
			
							$( '.pagePreview' ).mousemove();							
							if( viewComment && !loadedCommandBindings ) {
								setTimeout( function(){
									refreshComments('');
									}, 50);
								$(".commentEnabler").click(function(e){
									toggleComments(e);
									});
								$( document ).bind('keydown', function(e) {
									if( e.keyCode == "49" && !disable ) { $(".commentEnabler")[0].click(); }
									if( e.keyCode == "50" && !disable ) { $(".commentEnabler")[1].click(); }
									if( e.keyCode == "51" && !disable ) { $(".commentEnabler")[2].click(); }
									if( e.keyCode == "52" && !disable ) { $("#square").click(); }
									if( e.keyCode == "53" && !disable ) { $("#circle").click(); }
								
									if( e.keyCode == "32" && !disable ) {;
										e.preventDefault();
										toggleComments( 'all' );
										}
									});
								loadedCommandBindings = true;
								}
							$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
							if( graphState == ""  ) {
								 if( !mobile )
									 $('#content_box').kinetic( 'attach' );
								 }
							if( graphState == "magnify" ) {
								$( "#zoomRange" ).slider( "option", { disabled: false } );
								}
							disableZoom = false;
							setTimeout(function() { refreshComments(''); }, 200 );
							var currentPos = {
								left: $('#content_box').scrollLeft(),
								top: $('#content_box').scrollTop()
								}
								
							actualPos = currentPos;
							alphaBoxSize = boxSize;
							createBoxes();
							}
						});
					},
				timeout: 6000,
				success:function( data ) {
					if( img > 2 ) img = 1;
					
					
					$("#renderedIMG"+img).css({
						left: ( $('#content_box').scrollLeft())+"px",
						top: $('#content_box').scrollTop()+"px",
						});
						
					$("#renderedSRC"+img).attr('src', data[0] );
				
					switch( img ) {
						case 1:
							$("#renderedIMG2").hide( 20 );				
							break;					
						case 2:	
							$("#renderedIMG1").hide( 20 );
							break;
						}
					$("#renderedIMG"+img).fadeIn(0).show(0);
	
					ajaxDisabled = false;
				
					img++;	
	
					$( '.pagePreview' ).mousemove();							
					if( viewComment && !loadedCommandBindings ) {
						setTimeout( function(){
							refreshComments('');
							}, 50);
						$(".commentEnabler").click(function(e){
							toggleComments(e);
							});
						$( document ).bind('keydown', function(e) {
							if( e.keyCode == "49" && !disable ) { $(".commentEnabler")[0].click(); }
							if( e.keyCode == "50" && !disable ) { $(".commentEnabler")[1].click(); }
							if( e.keyCode == "51" && !disable ) { $(".commentEnabler")[2].click(); }
							if( e.keyCode == "52" && !disable ) { $("#square").click(); }
							if( e.keyCode == "53" && !disable ) { $("#circle").click(); }
						
							if( e.keyCode == "32" && !disable ) {;
								e.preventDefault();
								toggleComments( 'all' );
								}
							});
						loadedCommandBindings = true;
						}
					$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
					if( graphState == ""  ) {
						 if( !mobile )
							 $('#content_box').kinetic( 'attach' );
						 }
					if( graphState == "magnify" ) {
						$( "#zoomRange" ).slider( "option", { disabled: false } );
						}
					disableZoom = false;
					setTimeout(function() { refreshComments(''); }, 200 );
					var currentPos = {
						left: $('#content_box').scrollLeft(),
						top: $('#content_box').scrollTop()
						}
						
					actualPos = currentPos;
					alphaBoxSize = boxSize;
					createBoxes();
					}
				});
			}
		else {
					$( '.pagePreview' ).mousemove();							
					if( viewComment && !loadedCommandBindings ) {
						setTimeout( function(){
							refreshComments('');
							}, 50);
						$(".commentEnabler").click(function(e){
							toggleComments(e);
							});
						$( document ).bind('keydown', function(e) {
							if( e.keyCode == "49" && !disable ) { $(".commentEnabler")[0].click(); }
							if( e.keyCode == "50" && !disable ) { $(".commentEnabler")[1].click(); }
							if( e.keyCode == "51" && !disable ) { $(".commentEnabler")[2].click(); }
							if( e.keyCode == "52" && !disable ) { $("#square").click(); }
							if( e.keyCode == "53" && !disable ) { $("#circle").click(); }
						
							if( e.keyCode == "32" && !disable ) {;
								e.preventDefault();
								toggleComments( 'all' );
								}
							});
						loadedCommandBindings = true;
						}
					$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
					if( graphState == ""  ) {
						 $('#content_box').kinetic( 'attach' );
						 }
					if( graphState == "magnify" ) {
						$( "#zoomRange" ).slider( "option", { disabled: false } );
						}
					disableZoom = false;
					setTimeout(function() { refreshComments(''); }, 200 );
					var currentPos = {
						left: $('#content_box').scrollLeft(),
						top: $('#content_box').scrollTop()
						}
						
					actualPos = currentPos;
					alphaBoxSize = boxSize;
					createBoxes();			
			}
		}
	}

$('#content_box').on('mousedown', function() {
	down = true;
	});

var norefresh = [ "compareTable", "compare_selector", "comp_operation", "compRange", "inner", "hover", "custom-menu", "ownerOnly", "commentBox", "replyComment", "subCancel", "subSave", "panel_top", "panel", "panelElement", "commentText", "commentEnabler", "square", "circle", "dot", "rightPanel", "rightPanel_top", "cyan", "magenta", "yellow", "kblack", "rightPanelElement", "rightPanel_bottom" ];
window.addEventListener("mouseup", function(event) {
	if( event.button == 0 ) {
		if( jQuery.inArray( event.target.id, norefresh ) == -1 ) {
			if( jQuery.inArray( event.target.className, norefresh ) == -1 ) {
				down = false;
				if( !ajaxDisabled ) {
					setTimeout( function() { 					
						if( graphState != "magnify" || graphState != "colorPicker" ) {
							if( compareMode == "on" && graphState != "magnify" ) {
								if( cMode == "SideBySide" ) {
									renderComparePages( $("select[name='state_a']").val(), "side_a", 0 );
									renderComparePages( $("select[name='state_b']").val(), "side_b", 1 );
									}
								
								else {
									renderComparePages( $("select[name='state_a']").val(), "state_a", 0 );
									renderComparePages( $("select[name='state_b']").val(), "state_b", 1 );
									if( pages == "2" ) {
										renderComparePages( $("select[name='state_c']").val(), "state_c", 3 );
										renderComparePages( $("select[name='state_d']").val(), "state_d", 4 );
										} 
									}							
								}
							
							rendering();
							}
						}, 10 );
					}
				}
			}
		}
	});

$('#content_box').kinetic();

// commentelő
var extraCorrection = {
	"Left" : 0,
	"Top": 0
	}

var user = "<?= $_SESSION['intra_user'] ?>";
var page = "<?= $_GET['p'] ?>";
var clk = "<?= $_GET['clk'] ?>";
var pub = "<?= $_GET['id'] ?>";
var tag = "<?= $tag ?>";
var pack_id = "<?= $_GET['pack_id'] ?>";
var PageType = "<?= $_GET['alter'] ?>";
var toggle = {
	all: 'block',
	comment: 'block',
	comment2: 'block',
	comment3: 'block'
	};
var tempState = "";
var disable = 0; 
var zoom_ = 0;
var divSize = "";
var activeDivPos = "";
var activeDiv = "";
var comments = new Array();
var nextCommentCounter = 0;
var nextAlterCounter = 0;
var nextDraw = 1;
var relativePosition = "";
var minHeight = 0;
var drawing = false;
var graphState = "";
var previewPic = "";

function changePic( data ) {
	removeAdvancedTool();
	// Was "disableZoom = false;" unconditionally, immediately before this
	// very check - which made the guard permanently pass regardless of
	// whether a previous zoom/reload was still in flight. disableZoom is
	// the general busy flag set by reloadBG()/rendering() for both zoom
	// and page-nav (see reloadBG(), which sets it true at its own start),
	// so this let a fast double-click on next/prev fire two overlapping
	// page loads - wasted FPM workers and (before this session's other
	// fixes) races on shared per-user temp render filenames.
	if( !disableZoom ) {
		// Set synchronously, right here, rather than waiting for
		// reloadBG() to set it - reloadBG() only runs after the 200ms
		// fadeOut below completes, which left a real window where a
		// second rapid click still passed this same guard because
		// disableZoom hadn't been flipped true yet.
		disableZoom = true;
		data = data.split( "&" );
		$("#renderedIMG1").hide( 0 ).attr('src', '' );
		$("#renderedIMG2").hide( 0 ).attr('src', '' );
		
		if( data != '?page=flatlpan_preview' ) {
			enableCommentRefresh = false;
			$("#commenDisplay").html('');
			$('.activeGraph').each(function() {
				$(this).removeClass( 'activePanel' );
				$(this).removeClass( 'activeGraph' );
				$(this).attr('src', "plugins/images/"+$(this).attr('id')+".png");
				});		
	
			setState( "" );
			ajaxDisabled = false;
			tag = '';
			PageType = '';
			for( var i = 1; i < data.length; i++ ) {
				var temp = data[i].split( "=" );
				switch( temp[0] ) {
					case 'pack_id':
						pack_id = temp[1];
						break;
					case 'clk':
						clk = parseInt( temp[1] );
						break;
					case 'id':
						pub = temp[1];
						break;
					case 'p':
						page = parseInt( temp[1] );	
						break;
					case 'tag':
						tag = temp[1];	
						break;								
					case 'alter':
						PageType = temp[1];	
						break;	
					}
				}
			if( fpPages == "single" ) page = clk;
			$('.pagePreview').fadeOut( 200, function(){
				reloadBG( undefined, 'changePic' );
				});
			}
		// "data" was reassigned to an array by data.split("&") above, then
		// compared against a string - a single-element array coerces to just
		// its element for == comparisons, so this branch IS reachable: it's
		// what fires when the server had no prev/next page in this direction
		// (the link falls back to the bare "?page=flatlpan_preview" with no
		// params). disableZoom was already set true above, and reloadBG() -
		// the only other place that ever clears it - is never called on this
		// path, so hitting a "no page this way" boundary used to strand the
		// guard true forever, silently deadening every future click with no
		// error shown, until a full page refresh reset the JS state.
		else {
			disableZoom = false;
			}
		}
	}

function panelElementClick( panel, state ) {
	var same = false;
	if( !disable ) {
		removeAdvancedTool();
		$('.activeGraph').each(function() {
			if( $(this).attr('id') == $("#"+panel).attr('id') ) {
				same = true;
				}
			$(this).removeClass( 'activePanel' );
			$(this).removeClass( 'activeGraph' );
			$(this).attr('src', "plugins/images/"+$(this).attr('id')+".png");
			});
	
		if( same ) {
			setState( "" );
			$('#content_box').kinetic( 'attach' );
			ajaxDisabled = false;
			}
		else {
			ajaxDisabled = true;
			$('#content_box').kinetic( 'detach' );
			$("#"+panel).addClass('activeGraph');
			$("#"+panel).attr( 'src', "plugins/images/"+panel+"On.png" )
			$(".pagePreview").bind('dragstart', function(){
				return false; 
				});
			setState( state );
			}
		}
	}

function divHandle() {
	var correction = $(".pagePreview").position();
	previewPic = {
		Width: $( '.pagePreview' ).width(),
		Height: $( '.pagePreview' ).height()
		};
	zoom_ = zoom;
	
	if( correction.left < 0 ) {	correction.left = 0; }
	if( correction.top < 0 ) {	correction.top = 0; }
	
	if( graphState == "dot" ) {
		var newWidth = ( zoom_ / 100 ) * 30;
		var newHeight = ( zoom_ / 100 ) * 30;
		
		relativePosition.left -= newWidth/2;
		relativePosition.top -= newHeight/2;
		}
	
	jQuery('<div/>', {
    	id: 'drawing_'+nextDraw,
    	'class': 'commentDraw , '+graphState,
    	style: 'width: 0px; height: 0px; left: '+(correction.left + relativePosition.left)+'px; top: '+(correction.top + relativePosition.top)+'px;',
   		text: ''
	}).appendTo('#commenDraw');
	activeDiv = 'drawing_'+nextDraw;
	
	var defLeft = 100/ zoom_ * relativePosition.left;
	var defTop = 100/ zoom_ * relativePosition.top;
	
	$('#drawing_'+nextDraw).attr( "defLeft", defLeft );
	$('#drawing_'+nextDraw).attr( "defTop", defTop );
	$('#drawing_'+nextDraw).attr( "shape", graphState );

	activeDivPos = {
		left: (correction.left + relativePosition.left),
		top: (correction.top + relativePosition.top)
		};
		
	comments.push( 'drawing_'+nextDraw );
	if( graphState == "dot" ) {
		$('#drawing_'+nextDraw).attr( "defWidth", '30' );
		$('#drawing_'+nextDraw).attr( "defHeight", '30' );
		$('#drawing_'+nextDraw).css({
			width: newWidth,
			height: newHeight
			});
		}
	
	nextDraw++;
	if( graphState == "dot" ) {
		$( '.pagePreview' ).mouseup();
		}
	}

function startDraw() {
	drawing = true;
	if( graphState != "" ) {
		if( graphState == "magnify" ) alterZoom( 'magniBox' );
		else if( graphState == "measure" ) {
			$("#measureInfo").remove();
			alterZoom( 'measureBox' );
			}
		else if( graphState == "colorPicker" ) {
      		$("#colorPick").remove();
      		$("#pickerBox").remove();
      		}
		else divHandle();
		}
	}

function stopDraw() {
	if( drawing && graphState != "" && graphState != "magnify" && graphState != "measure" && graphState != "colorPicker" ) {
		addText( 'drawing_'+(nextDraw-1) );
		}
	else if( drawing && graphState == "magnify" ) {
		magnifyHandle();
		}
	else if( drawing && graphState == "measure" ) {
		measureHandle();
		}
	else if( drawing && graphState == "colorPicker" ) {
    	var defLeft = 100/ zoom * relativePosition.left;
    	var defTop = 100/ zoom * relativePosition.top;
    	
    	pickerHandle( defLeft, defTop);
		}
	drawing = false;
	}

function setState( state ) {
	graphState = state;
	}

function createDiv( options, text, id, parent ) {
	var style = "";
	for( var key in options ) {
		style += key+":"+options[key]+";";
		}
	
	jQuery('<div/>', {
    	id: id,
    	style: style
	}).appendTo( parent );
	if( id == "divTextBox" ) {
		$( "#"+id ).html( text ).show( 100 );
		}
	else {
		$( "#"+id ).html( text );
		}
	}

function cancelComment( div_id ) {
	$("#"+div_id).hide(100, function(){
		$("#"+div_id).remove();
		});
		
	$("#divTextBox").hide(100, function(){
		$("#divTextBox").remove();
		});
	
	charBindings = true;
	graphState = tempState;
	disable = 0;
	}

function saveComment( div_id ) {
	var actualPlaceHolder = zoom_/100 * 15;
	var textLeft = ( parseFloat( $("#"+div_id).css("left") )+ parseFloat( $("#"+div_id).css("width") )+actualPlaceHolder);
	
	var textDiv = {
		Left: textLeft,
		Top: ( parseFloat( $("#"+div_id).css("top") ))
		};

	createDiv({
		position: 'absolute',
		display: toggle.comment2,
		left: textDiv.Left+'px',
		top: textDiv.Top+'px',
		background: '#7EFF70',
		}, $("#divText").val().replace(/\n/g,"<br>"), div_id+"_text", '#commenDraw' );
	
	var correction = $(".pagePreview").position();
	if( correction.left < 0 ) {	correction.left = 0; }
	if( correction.top < 0 ) {	correction.top = 0; }
	if( cBox[2] != "mediabox" ) {
		if( pages == "2" ) {			
			correction.top = parseFloat( correction.top ) + pixel( ( parseFloat( cropbox[0]["Top"] ) - parseFloat( bleedbox[0]["Top"] ) ) ) + pixel( parseFloat( cBox[0].Bottom ) );	
			}
		else {
			correction.top = parseFloat( correction.top ) + pixel( ( parseFloat( cropbox[0]["Top"] ) - parseFloat( bleedbox[0]["Top"] ) ) ) + pixel( parseFloat( cBox[0].Bottom ) );
			}
		}

	var defLeft = 100/ zoom * ( parseFloat( $("#"+div_id+"_text").css("left") ) - correction.left );
	var defTop = 100/ zoom * ( parseFloat( $("#"+div_id+"_text").css("top") ) + correction.top - 2*parseFloat( $(".pagePreview").css("top") ) )+2*pixel( parseFloat( cBox[0]['Top'] ) );
	
	if( $('#content_box').scrollTop() > 0 && cBox[2] == "trimbox" ) {
		defTop -= 100/ zoom * $('#content_box').scrollTop() / 4;
		}
	
	$("#"+div_id+"_text").attr( "defLeft", defLeft );
	$("#"+div_id+"_text").attr( "defTop", defTop );
	$("#"+div_id+"_text").attr( "owner", user );
	$("#"+div_id+"_text").addClass( "resizeDiv" );
	$("#"+div_id+"_text").addClass( "commentText" );
	
	$("#"+div_id).css("display", toggle.comment2 );
	
	$("#divTextBox").hide(100, function(){
		$("#divTextBox").remove();
		});	

	if( $("#"+div_id).attr("defLeft") > parseFloat( file[0]['Width'] ) ) {
		var sqlLeft = $("#"+div_id).attr("defLeft")-parseFloat( file[0]['Width'] );
		var sqlPage = parseInt( page ) +1;
		}
	else {
		var tempZoom = zoom;
		zoom = 100;
		var sqlLeft = parseFloat( $("#"+div_id).attr("defLeft") )-pixel( parseFloat( cBox[0]['Left'] ) );
		if( pages == "1" && page%2 == 1 ) {
			//sqlLeft += pixel( parseFloat( cBox[0]['Left'] ) );
			}
			
		zoom = tempZoom;
		var sqlPage = parseInt( page );
		}
	
		if( pages == "2" && sqlPage%2 == 1 ) {
			sqlLeft += pixel( parseFloat( cBox[1]['Right'] ) )
			}

	if( cBox[2] == "trimbox" ) {
		//sqlLeft += pixel( cBox[0]["Left"] ) / 2;
		}
	
	if( fpPages == "single" ) {
		sqlPage = clk;
		}

	var uploadData = {
		pub: pub,
		parent: '0',
		left: sqlLeft,
		top: defTop,
		width: $("#"+div_id).attr("defWidth"),
		height: $("#"+div_id).attr("defHeight"),
		page: sqlPage,
		text: $("#"+div_id+"_text").html(),
		user: user,
		shape: $("#"+div_id).attr("shape"),
		PageVersion: tag,
		pageType: PageType
		}

	$.ajax	({
		url:"engine/commentAjax.php",
		type: "GET",
		data: 'op=saveComment&pageType='+uploadData.pageType+'&PageVersion='+uploadData.PageVersion+'&pub='+uploadData.pub+'&shape='+uploadData.shape+'&parent='+uploadData.parent+'&left='+uploadData.left+'&top='+uploadData.top+'&width='+uploadData.width+'&height='+uploadData.height+'&page='+uploadData.page+'&text='+uploadData.text+'&user='+uploadData.user+'&intra_user=<?= $_SESSION['intra_user'] ?>&part='+$("#part").val(),
		dataType: 'json',
		success:function( data ) {
			$("#"+div_id+"_text").attr( "sqlid", data );
			refreshComments('#commenDraw');	
			}
		});
	
	disable = 0;
	graphState = tempState;
	charBindings = true;
	}

function addText( div_id ) {
	charBindings = false;
	tempState = graphState;
	graphState = "";
	disable = 1;

	var defWidth = 100/ zoom_ * parseFloat( $('#'+div_id).css("width") );
	var defHeight = 100/ zoom_ * parseFloat( $('#'+div_id).css("height") );
	
	var defLeft = 100/ zoom_ * relativePosition.left;
	var defTop = 100/ zoom_ * relativePosition.top;
	
	$('#'+div_id).attr( "defWidth", defWidth );
	$('#'+div_id).attr( "defHeight", defHeight );
	
	var innerHTML = "<div style='font-size: 12px; margin-bottom: 4px;'><?= $lang['flatplan']['com_request'] ?>:</div><textarea id='divText' autofocus style='width: 277px; height: 80px; resize: none; background: #ddd;'></textarea>";
	innerHTML += "<br><div style='text-align: center;'><div class='panelButton' style='margin-left:54px; margin-top: 5px;' onclick=\"cancelComment('"+div_id+"')\"><?= $lang['flatplan']['cancel2'] ?></div><div class='panelButton' style='margin-left: 10px; margin-top: 5px;' id='saveCom' disabled style='margin-left: 15px;' onclick=\"saveComment('"+div_id+"')\"><?= $lang['flatplan']['save'] ?></div></div>";
	
	createDiv({
		position: 'fixed',
		display: 'block',
		left: ((truewidth/2)-130)+'px',
		top: '130px',
		width: '283px'
		}, innerHTML, 'divTextBox', 'body' );
	
	if( mobile ) {
		$("#divTextBox").css("top", "60px");
		}

	$("#divTextBox").addClass('floatCommentMenu');
	$("#divText").focus();
	$("#divText").keyup(function() {
		if( $("#divText").val() != "" ) {
			$("#saveCom").removeAttr('disabled');
			}
		else {
			$("#saveCom").attr('disabled', 'disabled');
			}
		});
	}

function commentPlace() {
	var defLeft, defTop, defWidth, defHeight = "";
	previewPic = {
		Width: $( '.pagePreview' ).width(),
		Height: $( '.pagePreview' ).height()
		};
	zoom_ = zoom;
	var relPosition = {
		left: $(".pagePreview").offset().left - $("#content_box").offset().left,
		top : $(".pagePreview").offset().top - $("#content_box").offset().top
		};
		
	$(".commentDraw, .resizeDiv").each(function(){
		
		defLeft = $(this).attr( "defLeft" );
		defTop = $(this).attr( "defTop" );
		defWidth = $(this).attr( "defWidth" );
		defHeight = $(this).attr( "defHeight" );
		
		if( relPosition.left > 0 ) {
			var newLeft = ( zoom_ / 100 * defLeft) + relPosition.left;
			}
		else {
			var newLeft = ( zoom_ / 100 * defLeft);
			}
			
		if( relPosition.top > 0 ) {
			var newTop = ( zoom_ / 100 * defTop ) + relPosition.top;
			}
		else {
			var newTop = ( zoom_ / 100 * defTop );
			}
		
		if( ! $(this).hasClass('resizeDiv') ) {	
			var newWidth = ( zoom_ / 100 ) * defWidth;
			var newHeight = ( zoom_ / 100 ) * defHeight;
			$(this).css({
				left: newLeft+'px',
				top: newTop+'px',
				width: newWidth+'px',
				height: newHeight+'px'
				});
			}
		else {
			$(this).css({
				left: newLeft+'px',
				top: newTop+'px'
				});			
			}
		});
		
	}

function coordinate(e) {
	previewPic = {
		Width: $( '.pagePreview' ).width(),
		Height: $( '.pagePreview' ).height()
		};
	zoom_ = zoom;
	relativePosition = {
		left: e.pageX - $(document).scrollLeft() - $('.pagePreview').offset().left ,
		top : e.pageY - $(document).scrollTop() - $('.pagePreview').offset().top
		};
	if( drawing && graphState != "" ) {
		var correction = $(".pagePreview").position();
		if( correction.left < 0 ) {	correction.left = 0; }
		if( correction.top < 0 ) {	correction.top = 0; }
		
		var defLeft = 100/ zoom_ * ( parseFloat( $("#"+activeDiv).css("left") ) - correction.left );
		var defTop = 100/ zoom_ * ( parseFloat( $("#"+activeDiv).css("top") ) - correction.top );
		if( defLeft < parseFloat( $("#"+activeDiv).attr( "defLeft" ) ) ) {
			 $("#"+activeDiv).attr("defLeft", defLeft );
			} 
		if( defTop < parseFloat( $("#"+activeDiv).attr( "defTop" ) ) ) {
			 $("#"+activeDiv).attr("defTop", defTop );
			} 
		
		var correction = $(".pagePreview").position();
		var divPos = $('#'+activeDiv).position(); 
		var Left = activeDivPos.left;
		var Top = activeDivPos.top;
		
		if( $('.pagePreview').width() >= $('.pagePreview').width() ) {
			divSize = {
				Width: (relativePosition.left-( activeDivPos.left )+parseFloat( $(".pagePreview").css("left") ) ),
				Height: (relativePosition.top-( activeDivPos.top )+parseFloat( $(".pagePreview").css("top") ) )
				}
			}
		else {
			divSize = {
				Width: (relativePosition.left-( activeDivPos.left-correction.left ) ),
				Height: (relativePosition.top-( activeDivPos.top-correction.top ) )
				}		
			}

		if( divSize.Width < 0 ) {
			divSize.Width = Math.abs( divSize.Width );
			Left = Left-divSize.Width;
			}
			
		if( divSize.Height < 0 ) {
			divSize.Height = Math.abs( divSize.Height );
			Top = Top-divSize.Height;
			}

		$('#'+activeDiv).css({
			width: divSize.Width+'px',
			height: divSize.Height+'px',
			left: Left+'px',
			top: Top+'px'
			});
		}
	}

function toggleComments(event) {
	if( event == 'all')
		var target = 'all';
	else
		var target = event.target.id;

	if( toggle[ target ] == "none" ) {
		toggle[ target ] = "block";
		$("#"+target).attr("src","images/icons/"+target+".png");
		}
	else {
		toggle[ target ] = "none";
		$("#"+target).attr("src","images/icons/"+target+"_disabled.png");
		}
		
	if( target == 'all' ) {
		for( var key in toggle ) {
			if( key != 'all' ) {
				if( toggle[ key ] == "none" ) {
					toggle[ key ] = "block";
					$("#"+key).attr("src","images/icons/"+key+".png");
					}
				else {
					toggle[ key ] = "none";
					$("#"+key).attr("src","images/icons/"+key+"_disabled.png");
					}
				}
			}
		$(".commentDraw, .resizeDiv, .dashLine").css( "display", toggle[ target ] );
		}
	else {
		$(".status_"+target).css( "display", toggle[ target ] );
		}
		
	if( toggle[ target ] == "none" ) {
		if( mobile ) {
			$(".commentsOn").hide(0);
			$(".commentsOff").show(0);
			}
		else {
			$("#showComment").children(".normal").attr( "src", "plugins/images/hidecomment.png" );
			$("#showComment").children(".hover").attr( "src", "plugins/images/hidecomment_hover.png" );
			}
		}
	else {
		if( mobile ) {
			$(".commentsOn").show(0);
			$(".commentsOff").hide(0);			
			}
		else {
			$("#showComment").children(".normal").attr( "src", "plugins/images/showcomment.png" );
			$("#showComment").children(".hover").attr( "src", "plugins/images/showcomment_hover.png" );	
			}
		}		
	}

function autoreply( comment_id ) {
	$.ajax	({
		url:"engine/commentAjax.php",
		type: "GET",
		data: 'op=autoreply&id='+comment_id,
		dataType: 'json',
		success:function( data ) {
			$("div[sqlid='"+comment_id+"']").find(".autoAnswer").remove();
			$("div[sqlid='"+comment_id+"']").find(".commentMenu").css("right", "0px");
			refreshComments('');
			}
		});
	}


function loadComments( comment ) {
	if( comment != "" )
		var separate = comment.split( "|" );
	else
		var separate = "";
		
	for( var i = 0; i < separate.length; i++ ) {
		var commentLine = separate[i].split( "^" );
		var commentData = {
			Left: parseFloat( commentLine[3].replace(',', '.') ),
			Top: parseFloat( commentLine[4].replace(',', '.') ),
			Width: parseFloat( commentLine[5].replace(',', '.') ),
			Height: parseFloat( commentLine[6].replace(',', '.') ),
			Shape: commentLine[11],
			Page: parseInt( commentLine[7] ),
			Text: commentLine[8],
			Owner: commentLine[10],
			Id: commentLine[0],
			Status: commentLine[12],
			Extra: commentLine[16]
			}
		if( commentData.Page == parseInt( page ) + 1 && fpPages != "single" ) {
			commentData.Left += parseFloat( file[0]['Width'] );
			}
		
		if( commentData.Extra == "comment3" ) {
			commentData.Status = "comment3";
			}
		else {
			if( commentData.Status == "approved" ) {
				commentData.Status = "comment";
				}
			else if( commentData.Status == "" ) {
				commentData.Status = "comment2";
				}
			}
		
		var correction = $(".pagePreview").position();	
		previewPic = {
			Width: $( '.pagePreview' ).width(),
			Height: $( '.pagePreview' ).height()
			};
		zoom_ = zoom;
		if( correction.left <= 0 || correction.left == 'NaN' ) {	correction.left = 0; }
		if( correction.top <= 0 || correction.top == 'NaN' ) {	correction.top = 0; }	
		
		if( cBox[2] != "mediabox" && commentData.Page == page  ) {
			correction.left -= pixel( ( parseFloat( cropbox[0]["Left"] ) - parseFloat( bleedbox[0]["Left"] ) ), 100 );
			correction.left = parseFloat( correction.left ) + pixel( parseFloat( cBox[0].Left ) );
			correction.top = parseFloat( correction.top ) - pixel( parseFloat( cBox[0].Bottom ) );
			}
		else if( cBox[2] != "mediabox" ) {
			correction.left += pixel( ( parseFloat( cropbox[0]["Left"] ) - parseFloat( bleedbox[0]["Left"] ) ), 100 );
			correction.top = parseFloat( correction.top ) - pixel( parseFloat( cBox[0].Bottom ) );
			}
			
		var relPosition = {
			left: $(".pagePreview").offset().left - $("#content_box").offset().left,
			top : $(".pagePreview").offset().top - $("#content_box").offset().top
			};
		
		if( relPosition.left < 0 ) { relPosition.left = 0; }
		if( relPosition.top < 0 ) {	relPosition.top = 0; }

		var newLeft = ( zoom_ / 100 * commentData.Left) + relPosition.left - correction.left + parseFloat( $(".pagePreview").css("left") ) + pixel( parseInt( cBox[0].Left ) );
		var newTop = ( zoom_ / 100 * commentData.Top ) + correction.top + 2*pixel( parseInt( cBox[0].Left ) );
		var newWidth = ( zoom_ / 100 ) * commentData.Width;
		var newHeight = ( zoom_ / 100 ) * commentData.Height;
		
		if( commentData.Page == page ) {
			nextCommentCounter++;
			var nextComment = nextCommentCounter+"_"+commentData.Page;
			}
		else {
			nextAlterCounter++;
			var nextComment = nextAlterCounter+"_"+commentData.Page;
			}
		
		jQuery('<div/>', {
			id: 'comment_'+nextComment,
			'class': 'commentDraw '+commentData.Shape,
			style: 'display: '+toggle[ commentData.Status ]+'; left: '+newLeft+'px; top: '+newTop+'px; width: '+newWidth+'px; height: '+newHeight+'px;',
			text: ''
		}).appendTo('#commenDisplay');

		$('#comment_'+nextComment).attr( "defLeft", commentData.Left );
		$('#comment_'+nextComment).attr( "defTop", commentData.Top );
		$('#comment_'+nextComment).attr( "defWidth", commentData.Width );
		$('#comment_'+nextComment).attr( "defHeight", commentData.Height );
		
		var actualPlaceHolder = zoom_/100 * 15;
		var textLeft = ( parseFloat( $('#comment_'+nextComment).css("left") )+ parseFloat( $('#comment_'+nextComment).css("width") )+actualPlaceHolder);
	
		var textDiv = {
			Left: textLeft,
			Top: ( parseFloat( $('#comment_'+nextComment).css("top") ))
			};
		
		commentData.Text = commentData.Text;
		
		createDiv({
			position: 'absolute',
			display: toggle[ commentData.Status ],
			left: textDiv.Left+'px',
			top: textDiv.Top+'px',
			background: '#7EFF70'
			}, commentData.Text, "comment_"+nextComment+"_text", '#commenDisplay' );
	
		var correction = $(".pagePreview").position();
		if( cBox[2] != "mediabox" && commentData.Page == page ) {
			correction.left = parseFloat( correction.left ) + pixel( parseFloat( cBox[0].Left ) );
			correction.top = parseFloat( correction.top ) - pixel( parseFloat( cBox[0].Bottom ) );
			}	
		if( correction.left < 0 ) {	correction.left = 0; }
		if( correction.top < 0 ) {	correction.top = 0; }
	
		var defLeft = 100/ zoom_ * ( parseFloat( $("#comment_"+nextComment+"_text").css("left") ) - correction.left );
		var defTop = 100/ zoom_ * ( parseFloat( $("#comment_"+nextComment+"_text").css("top") ) - correction.top );
		$("#comment_"+nextComment+"_text").attr( "defLeft", defLeft );
		$("#comment_"+nextComment+"_text").attr( "defTop", defTop );
		$("#comment_"+nextComment+"_text").attr( "owner", commentData.Owner );
		$("#comment_"+nextComment+"_text").attr( "sqlid", commentData.Id );
		$("#comment_"+nextComment+"_text").addClass( "resizeDiv" );
		$("#comment_"+nextComment+"_text").addClass( "commentText" );
		
		switch( commentData.Status ) { 
			case 'comment3':
				$( "#comment_"+nextComment ).addClass( "commentNew" );
				$( "#comment_"+nextComment ).addClass( "status_comment3" );
				$( "#comment_"+nextComment+"_text" ).addClass( "commentNew" );
				$( "#comment_"+nextComment+"_text" ).addClass( "status_comment3" );			
				break;
			case 'comment2':
				$( "#comment_"+nextComment ).addClass( "status_comment2" );
				$( "#comment_"+nextComment+"_text" ).addClass( "status_comment2" );
				break;
			case 'comment':
				$( "#comment_"+nextComment ).addClass( "commentApproved" );
				$( "#comment_"+nextComment ).addClass( "status_comment" );
				$( "#comment_"+nextComment+"_text" ).addClass( "commentApproved" );
				$( "#comment_"+nextComment+"_text" ).addClass( "status_comment" )			
				break;				
			}
		comments.push( 'comment_'+nextComment );
		}
	}

var commenDisplayHTML = "";
var enableCommentRefresh = true;
var commentCache = "";
function refreshComments( suboption ) {
	if( enableCommentRefresh && viewComment ) {
		$.ajax	({
			url:"engine/preview_ajax.php",
			type: "GET",
			data: 'op=refreshComments&pgs='+pages+'&alter='+PageType+'&clicked='+clk+'&tag='+tag+'&type=<?= $_GET["type"] ?>&id='+pub+'&pack_id='+pack_id+'&p='+page+'&intra_user=<?= $_SESSION['intra_user'] ?>&part='+$("#part").val(),
			dataType: 'json',
			success:function( data ) {
				if( enableCommentRefresh ) {
					if( data != "" ) {
						$("#commenDisplay").html('');
						nextCommentCounter = 0;
						nextAlterCounter = 0;
						loadComments( data[0] );
						$(".commentDraw").each(function(){
							var status = "";
							var temp = $(this).attr('id');
							var classList = document.getElementById( temp ).className.split(/\s+/);
							for (var i = 0; i < classList.length; i++) {
								if ( classList[i].indexOf('status_comment') >= 0 ) {
									status = classList[i].split("_")[1];
									break;							 	
  								 	}
  								 }
							
							connect( document.getElementById( temp ), document.getElementById( temp+'_text' ), "#0F0", 3, status, temp+'_dash' );
							$("#"+temp+"_text").draggable({ 
								start: function( event, ui ) {
									$('#content_box').kinetic( 'detach' );
									},
								stop: function( event, ui ) {
       								if( graphState == "" ) {
       									$('#content_box').kinetic( 'attach' );
       									}
									},								
								drag: function( event, ui ) {
									ui.position.top += $("#content_box").scrollTop();
									ui.position.left += $("#content_box").scrollLeft();
									connect( document.getElementById( temp ), document.getElementById( temp+'_text' ), "#0F0", 3, status, temp+'_dash' );
									},
								cursor: "move" 
								});
							});
						commentCache = data[0];
						}

					if( suboption != "" ) {
						$( suboption ).html('');
						}
					}
				//if( suboption == "" ) setTimeout(function() { refreshComments( suboption ); }, 1000 );
				}
			});
		}
	else {
		//if( suboption == "" ) setTimeout(function() { refreshComments( suboption ); }, 1000 );
		}	
	}
	
var fpPages = parseInt( $("#fpPages").outerWidth() );

function fit_pages() {
	var footer = parseInt( $("#fpFooter").outerWidth() );
	var pagesLeft = (footer/2)-(parseInt($(".pages").outerWidth())/2 );
	$(".pages").css("left", pagesLeft+"px" );
	}

var starting_zoom = 0;

function compareZoomCalc() {
	var defHeight = pixel( (file[0]['Top'] - file[0]['Bottom'] ), 100 );
	var defWidth = pixel( ( (file[0]['Right'] - file[0]['Left']) ), 100 )*2+9;
	if( defHeight > ( fpBox['Height'] -30 ) ) {
		var heightPer = ( ( fpBox['Height'] -30 ) / defHeight )*100;
		zoom = heightPer;
		var width = pixel( (file[0]['Right'] - file[0]['Left']) )*2+9;
		if( width > ( fpBox['Width'] -30 ) ) {
			var widthPer = ( ( fpBox['Width'] -30 ) / defWidth )*100;
			zoom = widthPer;
			}
		}
	else {
		if( defWidth > ( fpBox['Width'] -30 ) ) {
			var widthPer = ( ( fpBox['Width'] -30 ) / defWidth )*100;
			zoom = widthPer;
			}		
		}
	starting_zoom = zoom;
	}

function zoomCalc( zoomBox ) {
	jQuery.each( file, function(){

		});
	
	console.log( "DEFHEIGHT = "+file[0]['Top']+" - "+file[0]['Bottom'] );
	var defHeight = pixel( (file[0]['Top'] - file[0]['Bottom'] ), 100 );
	var defWidth = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ), 100 );
	if( zoomBox == undefined ) {
		if( defHeight > ( fpBox['Height'] -30 ) ) {
			console.log( "( ( "+fpBox['Height']+" -30 ) / "+defHeight+" )*100" );
			var heightPer = ( ( fpBox['Height'] -30 ) / defHeight )*100;
			zoom = heightPer;
			var width = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ) );
			if( width > ( fpBox['Width'] -30 ) ) {
				var widthPer = ( ( fpBox['Width'] -30 ) / defWidth )*100;
				zoom = widthPer;
				}
			}
		else {		
			console.log( "2");		
			if( defWidth > ( fpBox['Width'] -30 ) ) {
				var widthPer = ( ( fpBox['Width'] -30 ) / defWidth )*100;
				zoom = widthPer;
				}		
			else {
				var temp_height = (fpBox['Height'] -30 ) - defHeight;
				var temp_width = (fpBox['Width'] -30 ) - defWidth;
				
				if( temp_width < temp_height ) {
					var widthPer = ( ( fpBox['Width'] -30 ) / defWidth )*100;
					zoom = widthPer;
					}
				else {
					var heightPer = ( ( fpBox['Height'] -30 ) / defHeight )*100;
					zoom = heightPer;
					}
				}
			}
			
		console.log( "DEBUG ZOOM: "+zoom );
		starting_zoom = zoom;
		}
	else {		
		fpCanvas = {
			'width': $("#content_wrapper").width(),
			'height': $("#content_wrapper").height(),
			};
		var percent = ( fpCanvas.width / zoomBox.Width ) * zoom;	
		var orient = 'landscape'
		var scaled = {
			'width': fpCanvas.width,
			'height': ( percent / zoom ) * zoomBox.Height
			};
		
		if( scaled.height > fpCanvas.height ) {
			percent = ( fpCanvas.height / zoomBox.Height ) * zoom;
			orient = 'portrait';
			var scaled = {
				'width': ( percent / zoom ) * zoomBox.Width,
				'height': fpCanvas.height
				}
			}
		if( percent > 1500 ) percent = 1500;
		var scalePic = {
			'width': ( percent / zoom ) * $(".pagePreview").width(),
			'height': ( percent / zoom ) * $(".pagePreview").height()
			};
		
		var corr = {
			'left': ( percent / 100 ) * $("#magniBox" ).attr("defleft" ),
			'top': ( percent / 100 ) * $("#magniBox" ).attr("deftop" )
			};
			
		zoom = percent;
		scrollCorrection.left = corr.left - ( ( fpCanvas.width - scaled.width ) / 2 );
		scrollCorrection.top = corr.top - ( ( fpCanvas.height - scaled.height ) / 2 );
		}	
	}

var alapzoom = 0;
var panning = 0;
var pinching = 0;
var truewidth;
var trueheight;
var winWidth;
	
$( document ).ready(function() {
	truewidth = $("#mainPage").width();
	trueheight = $("#mainPage").height();
	winWidth = truewidth;
	
	$(".pagePreview").fadeOut(0);
	fit_box_preview();
	zoomCalc();
	
	alapzoom = zoom;
	
	if( mobile ) {
		var swipe = new Hammer.Swipe();
		var pan = new Hammer.Pan({ direction: Hammer.DIRECTION_ALL, threshold: 0 });
		
		var sx1, sx2;
		
		mc.add( swipe );
		mc.add( pan );
		mc.add( new Hammer.Press({ time: 300 }) );
		mc.add( new Hammer.Tap({ event: 'doubletap', taps: 2, interval: 400, time: 200, threshold: 120 }) );
	    
	    /*mc.on("swipeleft", function(ev) {
			alert("swipe");
			});*/
	        
		mc.on("doubletap press", function(ev) {
			if( !disableZoom ) {
				_zoom( '', 'roll', alapzoom );
				}
			});    
			
		mc.on("panstart", function(ev) {	
			sx1 = ev.deltaX;
	
			panning = 1;
			mc.remove( pinch );
			});
		        	
		mc.on("panend", function(ev) {
			sx2 = ev.deltaX;
			if( pinching == 0 ) {
				//alert( "ezt érzékelem" );
				rendering();
				mc.add( pinch );
				}
			
			//alert( sx1+" => "+sx2 );
			
			if( alapzoom == zoom ) {
				var dist = Math.abs( sx1 - sx2 );
				if( dist > 150 ) {
					if( sx1 > sx2 ) {
						$("#rightArrow").click();
						}
					else {
						$("#leftArrow").click();
						}
					}
				}
			
			setTimeout( function() {
				panning = 0;
				}, 500 );	
			});
			
		mc.on("pinchstart", function(ev) {
			mc.remove( pan );
			pinching = 1;
			
			$('#content_box').kinetic( 'detach' );
			});
			
		mc.on("pinchend", function(ev) {
			if( panning == 0 ) { 
				if( !disableZoom ) {
				    newZoom = zoom * ev.scale;
				    if( newZoom > 800) newZoom = 800;
					_zoom( '', 'roll', newZoom );
					}
				
				$('#content_box').kinetic( 'attach' );
				mc.add( pan );
				}
	
			setTimeout( function() {
				pinching = 0;
				}, 500 );
			});
			
		window.addEventListener("orientationchange", function() {
			//?page=flatplan_preview&pack_id=11229&clk=2&alter=FIN&type=ad&id=2356&p=2
			//?page=flatplan_preview&clk=11&alter=FIN&p=2
			
			window.location.href = "?page=flatplan_preview&clk="+clk+"&alter=<?= $_GET["alter"] ?>&p="+page;
			});
		}			
	});

$(window).resize(function(){
	if( !mobile ) {
		winWidth = $(window).width();
		fit_box_preview();
		zoomCalc();
	
		placeBox();
		}
	});

function Redirect( val ) {
	var temp = val.split("_");
	
	location.href='?page=flatplan_preview&clk='+temp[2]+'&id='+temp[0]+'&p='+temp[2];
	}

var userGRP = "<?= $user[0][8] ?>";
var enableContext = 1;
var contextWidth = $(".custom-menu").width();

function cmenu( event, go_to ) {
	$(".custom-menu").fadeOut(100, function(){
    	if( enableContext ) {
    		var checker = winWidth-contextWidth-5;
    		if( event.pageX >= checker ) {
    			event.pageX = event.pageX-contextWidth;
    			}
    		
			$(".custom-menu").fadeIn(100).css({
				top: (event.pageY-50) + "px",
				left: event.pageX + "px"
				});
			var target = event.target.id;
			if( target == "" ) {
				target = $( event.target ).closest(".commentText").attr("id");
				}
				
			if( go_to == "reply" ) {
				myContextMenu( 'comment', target );
				return "";
				}
				
			$(".custom-menu").attr( "selectedComment", target );
			if( $("#"+target).attr("owner") != user ) {
				$(".ownerOnly").each(function(){
					if( userGRP != "2") $(this).css('display', 'none' );
					});
				}
			else {
				$(".ownerOnly").css('display', 'block' );				
				}
			$('#content_box').kinetic( 'detach' );
	   		}
	   	});
	}

$(document).on("contextmenu", ".resizeDiv", function(event){
    event.preventDefault();
    cmenu( event );
	return false;
	});

$(document).on("dragstart", function(e) {
     if (e.target.nodeName.toUpperCase() == "IMG") {
         return false;
     }
});
	
$(document).mousedown(function (e) {
    var container = $(".custom-menu");
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.fadeOut(100);
        if( disable != 1 && !drawing ) {
       		if( graphState == "" ) {
       			$('#content_box').kinetic( 'attach' );
       			}
       		}
       	}
	});

var stopRefresh = false;
var commentTXT = "";
var refreshTarget = "";
var approveComment = "";

function getComments() {
	if( !stopRefresh ) {
		if( commentTXT == "" ) {
			$(".commentBox").html( '<div class="commentLoad">...<?= $lang["flatplan"]["load_comments"] ?>...<br><img src="images/ajax_loader.gif"></div>' );
			}
		
		$.ajax	({
			url:"engine/commentAjax.php",
			type: "GET",
			data: 'op=getComments&id='+$('#'+refreshTarget).attr('sqlid')+'&intra_user=<?= $_SESSION['intra_user'] ?>',
			dataType: 'json',
			success:function( data ) {
				if( commentTXT != data[0] && !stopRefresh ) {
					$(".commentBox").html( data[0] );
					commentTXT = data[0];
					if( data[1] == false ) {
						$("#subApprove").hide(0);
						}
					}	
				
				setTimeout( function(){ getComments(); }, 250);
				}
			});
		}
	}

function saveSubComment(target ) {
	$("#subSave").attr('disabled', 'disabled');
	$("#replyLoader").html( "<img src='images/ajax-loader.gif'>" );
	$.ajax	({
		url:"engine/commentAjax.php",
		type: "GET",
		data: 'op=saveReply&id='+$('#'+target).attr('sqlid')+'&txt='+$('#replyComment').val().replace(/\n/g,"<br>")+'&intra_user=<?= $_SESSION['intra_user'] ?>',
		dataType: 'json',
		success:function( data ) {
			$("#replyLoader").html( '' );
			$('#replyComment').val('');
			$("#replyComment").keyup();

			$("div[id='"+target+"']").find(".autoAnswer").remove();
			$("div[id='"+target+"']").find(".commentMenu").css("right", "0px");
			refreshComments('');
			}
		});
	}

function myContextMenu( operation, target ) {
	switch( operation ) {	
		case "comment":
			tempState = graphState;
			graphState = "";
			disable = 1;

			if( replyComment )
				var text = "<div class='commentDrag'></div><div class='commentBox'></div><?= $lang['flatplan']['reply'] ?>:<textarea id='replyComment' style='width: 295px; height: 80px; resize: none;'></textarea><br><div class='anim' style='text-align: center;'><span id='subCancel' class='panelButton' style='display: inline-block; float: none; margin-left: 0px; margin-top: 5px;'><?= $lang['flatplan']['close'] ?></span><span id='subSave' class='panelButton' style='display: inline-block; float: none; margin-left: 8px; margin-top: 5px;' disabled style='margin-left: 15px;'><?= $lang['flatplan']['send'] ?></span><span id='subApprove' class='panelButton' style='display: inline-block; float: none; margin-left: 8px; margin-top: 5px;' disabled style='margin-left: 15px;'><?= $lang['flatplan']['approve_comment'] ?></span></div>";
			else
				var text = "<div class='commentBox' style='height: 422px !important;'></div><div class='anim' style='text-align: center;'><div id='subCancel' class='panelButton' style='margin-left: 150px; margin-top: 5px;'><?= $lang['flatplan']['close'] ?></div>";
			
			if( $('#commentComment').length > 0 ) {
				$('#commentComment').remove();
				$('#commentBox').html('');
				commentTXT = "";
				refreshTarget = "";
				}
			createDiv({ 
				position: 'fixed',
				display: 'block',
				'-webkit-box-shadow': '0px 0px 14px 0px rgba(50, 50, 50, 1)',
				'-moz-box-shadow': '0px 0px 14px 0px rgba(50, 50, 50, 1)',
				'box-shadow': '0px 0px 14px 0px rgba(50, 50, 50, 1)',
				left: ((truewidth/2)-130)+'px',
				top: '130px',
				'-webkit-user-select': 'text',
				'-ms-user-select': 'text',
				'user-select': 'text',
				width: '300px',
				'font-size': '12px',
				'line-height': '15px',
				'max-height': '469px',
				}, text, 'commentComment', 'body' );
			
			$( ".commentDrag" ).parent().draggable({
				cursor: "move",
				handle: $(".commentDrag")
				});
			
			if( mobile ) {
				$("#commentComment").css("top", "60px");
				}
				
			disableZoom = true;
			$('#commentComment').addClass('floatMenu');
			$('#replyComment').focus();
			$("#replyComment").keyup(function() {
				if( $("#replyComment").val() != "" ) {
					$("#subSave").removeAttr('disabled');
					}
				else {
					$("#subSave").attr('disabled', 'disabled');
					}
				});
			
			$("#subApprove").attr("onclick", "myContextMenu( 'approve', '"+target+"' )" );	
				
			$('#subSave').click(function(){ 
				saveSubComment(target);				
				});
			$('#subCancel').click(function(){
				stopRefresh = true;
				refreshTarget = "";

				graphState = tempState;
				commentTXT = '';
				disable = 0;
				disableZoom = false;
				$('#commentComment').remove();				
				});
			refreshTarget = target;
			stopRefresh = false;
			getComments();
			break;
		case "approve":
			$.ajax	({
				url:"engine/commentAjax.php",
				type: "GET",
				data: 'op=approveComment&id='+$('#'+target).attr('sqlid')+'&intra_user=<?= $_SESSION['intra_user'] ?>',
				dataType: 'json',
				success:function( data ) {
					if( data == "success" ) {
						var temp = target.split("_");
						
						$('#'+target).addClass("commentApproved");
						$('#'+temp[0]+'_'+temp[1]+'_'+temp[2]).addClass("commentApproved");
						}
						
					$('#commentComment').remove();
					}
				});
			break;
		case "delete":
			if( confirm( "<?= $lang['flatplan']['remove_com'] ?>" ) ) {
				$.ajax	({
					url:"engine/commentAjax.php",
					type: "GET",
					data: 'op=deleteComment&id='+$('#'+target).attr('sqlid')+'&intra_user=<?= $_SESSION['intra_user'] ?>',
					dataType: 'json',
					success:function( data ) {
						if( data == "success" ) {
							var temp = target.split("_");
							$('#'+target).remove();
							$('#'+temp[0]+'_'+temp[1]+'_'+temp[2]).remove();
							$('#'+temp[0]+'_'+temp[1]+'_'+temp[2]+'_dash').remove();
							}
						}
					});
				}
			break;
		}
	}

$(".custom-menu div").click(function(){
    var actual = $(".custom-menu").attr("selectedComment");
    switch($(this).attr("data-action")) {
    	case "approve": myContextMenu('approve', actual); break;
        case "delete": myContextMenu('delete', actual); break;
        case "comment": myContextMenu('comment', actual); break;		
    	}
     $(".custom-menu").fadeOut(100);
 	});	

if( !mobile ) {
	$("#leftArrow").mouseenter(function(){
		$("#leftArrow").fadeOut(1);
		$("#leftArrow_hover").fadeIn(1);
		});
	$("#rightArrow").mouseenter(function(){
		$("#rightArrow").fadeOut(1);
		$("#rightArrow_hover").fadeIn(1);
		});
	$("#leftArrow_hover").mouseleave(function(){
		$("#leftArrow_hover").fadeOut(1);
		$("#leftArrow").fadeIn(1);
		});
	$("#rightArrow_hover").mouseleave(function(){
		$("#rightArrow_hover").fadeOut(1);
		$("#rightArrow").fadeIn(1);
		});
	}
	
function renderCounterCheck() {
	$("#renderCounter").change();
	setTimeout(function(){ renderCounterCheck(); }, 1000);
	}
renderCounterCheck();

</script>