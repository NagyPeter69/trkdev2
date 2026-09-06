<?
if( $_GET["hash"] != "" ) {
	$check = getValidHotlink( $_GET["hash"] );
	if( $check[0][0] == "" ) {
		header( 'Location: index.php' );
		}
	$job = sql_get( 'publications', 'id="'.$check[0][1].'"', '*' );
	$_GET['id'] = $job[0][0];
	
	sql_update( "hotlinks", "visited='1'", "id='".$check[0][0]."'" );
	$visitor = sql_get( "ad_hoc_users", "email='".$check[0][10]."'", "*" );
	if( $visitor[0][0] == "" ) {
		$visitor[0][1] = $check[0][10];
		}
	
	$rights = array(
		"viewComment" => $check[0][4],
		"createComment" => $check[0][4],
		"replyComment" => $check[0][4],
		"deleteComment" => $check[0][4],
		"acceptPage" => $check[0][5],
		);
	}
else {
	die();
	}
	
$info = "none";
$info_show = sql_get( "ad_hoc_infobox", "email='".$check[0][10]."'", "id" );
if( $info_show[0][0] == "" ) {
	$info = "block";
	
	$names = array( "email", "info_showed" );
	$values = array( $check[0][10], "1" );
	sql_add( "ad_hoc_infobox", $names, $values );
	}
?>

<div id='info' style='display: <?= $info ?>; position: fixed; left: 0; top: 0; width: 100%; height: 100%; background: rgba( 0, 0, 0, 0.5 ); z-index: 999;'>
	<div id='info_content' class='visitor_settingsPanel visitor_floatMenu' style='z-index: 1000; display: block !important; width: 480px;'>
		<table cellspacing='0' cellpadding='0' width='100%' style='font-size: 15px;'>
			<tr>
				<td id='info_title' colspan='2' align='center' style='padding-left: 15px; font-weight: bold;'><?= $lang["hotlinks"]["info_title"] ?></td>
			</tr>
			<tr>
				<td id='info_text' align='left' colspan='2' style='padding-top: 20px; padding-bottom: 15px;'><?= $lang["hotlinks"]["info_text"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/widePage.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_1"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/magnify.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_2"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/hand.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_3"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/hide.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_4"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/measure.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_5"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/compare.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_6"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/square.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_7"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/circle.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_8"] ?></td>
			</tr>
			<tr>
				<td width='35px' height='37px' valign='center' style='padding-top: 3px; background: rgb( 82, 82, 82 );'><img src='plugins/images/dot.png'></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_line_9"] ?></td>
			</tr>			
		</table>
		<table cellspacing='0' cellpadding='0' width='100%' style='font-size: 15px; padding-top: 15px;'>
			<tr>
				<td width='35px' height='37px' valign='top'><div class='decision greenBox'><?= $lang["flatplan"]["approve_box"] ?></div></td>
				<td align='left' style='padding-left: 15px;'><?= $lang["hotlinks"]["info_approve"] ?></td>
			</tr>
			<tr>
				<td colspan='2' align='center' style='padding-top: 10px;'><div onclick="closeInfo()" class="panelButton" style="display: inline-block; float: initial;"><?= $lang["standard"]["next"] ?></div></td>
			</tr>
		</table>
	</div>
</div>

<?
$_SESSION["fpFilter"] = $_SESSION["pageSeparate"] = $pageSeparate = ( $_SESSION["pageSeparate"] != "" ? $_SESSION["pageSeparate"] : "single" );
$user[0][15] = $_SESSION["cutBox"] = ( $_SESSION["cutBox"] != "" ? $_SESSION["cutBox"] : "trimbox" );

$time = strtotime( $pub[0][11] );
setlocale(LC_ALL,'hungarian');
$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) ); 	

$_GET['alter'] = "FIN";
$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );

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
	$firstPage = explode( "|", $check[0][3] );
	$firstPage = $firstPage[0];
	if( $check[0][8] == "pair" ) {
		if( $firstPage % 2 ) {
			$firstPage--;
			}
		}
	$_GET['clk'] = $_GET['p'] = $firstPage;
	}
	
$path = "packages/".$magazine[0][3]."/".$issue[0][10];
if( $_GET['alter'] == "FIN" ) $path .= "/FIN";

$pages = checkPagePair( $_GET['id'], $_GET["pack_id"], $_GET["p"], $_GET['tag'], $_GET['alter'] );
if( $pageSeparate == 'single' ) {
	$key = array_search( $_GET['clk'], $pages );
	if( $key !== false ) {
		$pages = array( $pages[$key] );
		$_GET['p'] = $pages[0];
		}
	}
sort( $pages );
?>

<link rel="stylesheet" href="css/jquery.mCustomScrollbar.min.css" />
<link rel="stylesheet" href="css/jquery-ui.css">
<link rel="stylesheet" href="css/rangeslider.css">
<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
<script src="js/jquery.mCustomScrollbar.concat.min.js"></script>
<script type="text/javascript" src="js/preview3.js"></script>
<script type="text/javascript" src="js/jquery.kinetic.js"></script>
<script type="text/javascript" src="js/rangeslider.js"></script>
<script type="text/javascript" src="js/jquery.zclip.min.js"></script>
<script type="text/javascript">
setDivCenter_visitor( "info_content" );

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

<div id='fpPages'>
	<div class='inner'>
		<div style='float: left; margin-left: -1px;'>
			<?
			$pubf = sql_get( 'publications', 'id="'.$job[0][0].'" ORDER BY `code` ASC', '*' );
			$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name' );
			
			echo $magf[0][0];
			?>
		</div>
		<div style='float: left; margin-left: 4px; margin-top: -1px;'>
      		<img title="<?= $magf[0][0] ?><br/>Issue: <?= $pubf[0][10] ?><br/>Deadline: <?= $pubf[0][11] ?>" src="images/icons/info.png" height="20px">
		</div>
		<div style='clear:both;'></div>
		<div style='margin-left: -1px; margin-top: 7px; font-size: 14px; text-align:left;'>
			<?
			$_GET['opt'] = $_GET['alter'];
			echo "<div style='clear:both;'></div>";
			?>
		</div>
		
		<div id='compare_panel' style="display: none;"></div>
	</div>
	<table cellspacing="0" cellpadding="0" width="100%">
		<tr>
			<td class="innerPreview" style="background: rgb(178, 178, 178); overflow-x: hidden; overflow-y: auto; width: 198px; display: block; white-space: nowrap; height: 250px;"></td>
		</tr>
	</table>
</div>

<div id='fpToolBox'>
	<? include_once( "plugins/vpreview_rightPanel.php" ); ?>
</div>

<?
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
			$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'"', '*' );
			}
		else {
			$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$pages[$i].'"', '*' );
			}
		$file2 = str_pad( $pages[$i], 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
		}
	else {
		if( $_GET['tag'] != "" && $pages[$i] == $_GET['clk'] ) {
			$tag = $_GET['tag'];
			$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$pages[$i].'"', '*' );
			}
		else {
			$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="" AND page="'.$pages[$i].'"', '*' );
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
	$box = getPDFBox( "Mediabox Trimbox Cropbox Bleedbox", $file[0]["Name"] );
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
	$_GET['pack_id'] = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'"AND page="'.$_GET['p'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND '.( $_GET["tag"] != "" ? "state='".$_GET["tag"]."'" : "state=''" ).' LIMIT 1', '*' );
	}
else {
	if( $_GET['alter'] == 'FIN' ) {
		$_GET['pack_id'] = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$_GET['p'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND fin="1" AND '.( $_GET["tag"] != "" ? "state='".$_GET["tag"]."'" : "state=''" ).' LIMIT 1', '*' );
		}
	else {
		$_GET['pack_id'] = sql_get( 'pageinfo', 'type!="PRE" AND type!="PSTR" AND page="'.$_GET['p'].'" AND issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND '.( $_GET["tag"] != "" ? "state='".$_GET["tag"]."'" : "state=''" ).' LIMIT 1', '*' );
		}
	}

$_GET['pack_id'] = $_GET['pack_id'][0][1];

$needle = array_search( str_pad( $_GET['p'] , 3, '0', STR_PAD_LEFT)."_".$_GET['pack_id'], $pages2 );
	
$prev = 0;
$prev_id = 0;
$next = 0;
$next_id = 0;

$prev_link = $next_link = "?page=flatlpan_preview";

if( $needle !== false ) {
	$prev = intval( $pages2[$needle-1] );
	$temp = explode( "_", $pages2[$needle-1] );

	$clk = intval( $pages2[$needle-1] ); 
	if( $user[0][14] == "pair" ) {
    	if( $prev % 2 != 0 && $prev > 1 ) {
			if( $pages2[$needle-2] == ( $prev-1 ) ) {
				$prev = intval( $pages2[$needle-2] );
				$temp = explode( "_", $pages2[$needle-2] );
        		}
      		}
		}
	$prev_id = $temp[1];
	
	$next = intval( $pages2[$needle+1] );
	$temp = explode( "_", $pages2[$needle+1] );
	$tempSql = sql_get( 'pageinfo', 'job_id="'.$job[0][0].'"', '*' );
	$clk2 = intval( $pages2[$needle+1] );
	if( $user[0][14] == "pair" ) {
	    if( $next == ( $pages2[$needle]+1 ) && $next % 2 != 0 ) {
    		$next = intval( $pages2[$needle+2] );
			$temp = explode( "_", $pages2[$needle+2] );
    		}

    	$next_id = $temp[1];
    	}
    $next_id = $temp[1];
	}

if( $prev != 0 ) {	
	$prev_link .= "&id=".$_GET['id']."&p=".$prev."&clk=".$clk;
	}
	
if( $next != 0 ) {
	$next_link .= "&id=".$_GET['id']."&p=".$next."&clk=".$clk2;
	}
	
$dcolors = getColors( "../../".$file[0]["Name"] );
$dtitles = getColorTitles( "../../".$file[0]["Name"] );
?>

<div id='content_wrapper' style='position: absolute; left: 229px; overflow: hidden;'>
	<div id='content_box' style='background-color: rgb( 178, 178, 178 ); overflow: hidden;'>
		<div class='pagePreview' style=' background-image: url(<?= $imgData; ?>); background-size:cover; background-repeat:no-repeat; position: relative; width: <?= $boxSize['width'] ?>px; height: <?= $boxSize['height'] ?>px;'>
			<div id='renderedIMG1' style='width: <?= $width ?>px; height: <?= $height ?>px; position: absolute;'>
				<img id="renderedSRC1" src="">
			</div>
			<div id='renderedIMG2' style='width: <?= $width ?>px; height: <?= $height ?>px; position: absolute;'>
				<img id="renderedSRC2" src="">
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
   	<div id='sbs_v1' class='sbs_ver' style='position: absolute; left: 0px; display: none;'></div>
   	<div id='sbs_v2' class='sbs_ver' style='position: absolute; left: 0px; display: none;'></div>
	<div id='loading' style='position: absolute; margin-top: 9px; right: 11px;'>
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
	
	<? if( $rights['viewComment'] ) { ?>
	<div id='commentView' style='position: absolute; margin-top: 1px; right: 39px; font-size: 13px;'>
		<?= $lang["flatplan"]["comments"] ?>: <span id='commentdisplay_status'><?= $lang["flatplan"]["on"] ?></span>
	</div>
	<? } ?>

	<div style='position: absolute; left: 15px;'>
    <div id="zoomdiv">
     <input id="zoomLevel" onkeypress="return isEnter(event)" class='zoomclass' type='text'>
      <font color="#FFFFFF" style='font-size: 10px;'>%</font>
    </div>
   	<div id="zoomRange"></div>
		<div class='status1' style='position: absolute; left: 150px;'></div>
		
		<div id='leftArrow' onclick="changePic('<?= $prev_link ?>')" style='cursor: pointer; position: absolute; left: 300px; width: 35px;'>
			<img src='images/icons/leftArrow.png'>
		</div>
		<div id='leftArrow_hover' onclick="changePic('<?= $prev_link ?>')" style='display:none; cursor: pointer; position: absolute; left: 300px; width: 35px;'>
			<img src='images/icons/leftArrow_hover.png'>
		</div>

		<div id='rightArrow' onclick="changePic('<?= $next_link ?>')" style='z-index: 2; cursor: pointer; position: absolute; left: 300px; width: 35px;'>
			<img src='images/icons/rightArrow.png'>
		</div>
		<div id='rightArrow_hover' onclick="changePic('<?= $next_link ?>')" style='z-index: 2; display:none; cursor: pointer; position: absolute; left: 300px; width: 35px;'>
			<img src='images/icons/rightArrow_hover.png'>
		</div>
		
		<div class='pages' style='position: absolute; left: 300px; width: 146px;'>
			<div class='pText' style='float:left; font-size: 14px; margin-top: 1px; margin-right: 7px;'>
				<?	
					if( count( $pages ) < 2 ) echo $lang["flatplan"]["page"];
					else echo $lang["flatplan"]["pages"];
				?>
			</div>
			<div class='pv1 pageVer'></div>
			<div id='pageNr'>
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
					echo implode( "-", $numb );
				?>
			</div>
			<div class='pv2 pageVer'></div>
		</div>
		<div class='status2' style='position: absolute; left: 169px;'></div>
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

<?
$pageID = array();
if( $_GET['alter'] == "" ) $alter = "";
else $alter = $_GET['alter'];

$status = checkPageStatus( $pages[0], $_GET['id'], $_GET["pack_id"], $alter, $_GET['tag'] );
$pageID[0] = $status[1];

$status = checkPageStatus( $pages[1], $_GET['id'], $_GET["pack_id"], $alter, $_GET['tag'] );
$pageID[1] = $status[1];				
?>

<script>
function showInfo() {
	$("#info_title").html( "<?= $lang['hotlinks']['info_title2'] ?>" );
	$("#info_text").html( "<?= $lang['hotlinks']['info_text2'] ?>" );
	$("#info").fadeIn( 200 );
	setDivCenter_visitor( "info_content" );
	}
	
function closeInfo() {
	$("#info").fadeOut( 200 );
	}

var fpFil = "<?= $_SESSION['fpFilter'] ?>";
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

// vtesztAjax.php's response is [imgData, debug, isRemoteRender] - see
// updateRenderModeIndicator() in flatplan_preview.php for why.
function updateRenderModeIndicator( data ) {
	$("#loading").toggleClass( "remote-render", data[2] == true );
	}

// See RENDER_WATCHDOG_MS in flatplan_preview.php for why this exists: a
// lost response (dropped connection, etc.) never reaches the vtesztAjax.php
// success/error handler, leaving renderCounter - and so the spinner - stuck
// on indefinitely. This guarantees recovery regardless of cause.
var renderWatchdogTimer = null;
var RENDER_WATCHDOG_MS = <?php echo R3_REMOTE_MODE ? 30000 : 15000; ?>;

function loadingBar( val ) {
	if( parseInt( val ) > 0 ) {
		$("#loading").fadeIn( 100 );
		$("#compRange").slider( "disable" );
		
		$('select[name="comp_operation"]').prop('disabled', 'disabled');
		$('#fake').addClass("ui-state-disabled");

		if( renderWatchdogTimer ) clearTimeout( renderWatchdogTimer );
		renderWatchdogTimer = setTimeout(function() {
			console.log( "render watchdog fired - renderCounter stuck at "+$("#renderCounter").val()+", forcing spinner off" );
			$("#renderCounter").val( 0 ).trigger( "onchange" );
			}, RENDER_WATCHDOG_MS );
		}
	if( parseInt( val ) == 0 ) {
		$("#loading").fadeOut( 100 );
		$("#compRange").slider( "enable" );
		$('select[name="comp_operation"]').prop('disabled', false);
		$('#fake').removeClass("ui-state-disabled");

		if( renderWatchdogTimer ) {
			clearTimeout( renderWatchdogTimer );
			renderWatchdogTimer = null;
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
var bleed = parseInt( "<?= $job[0][15] ?>" );
var safety = parseInt( "<?= $job[0][16] ?>" );


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
		}
?>
var txt = "";
var fpFilter = $("select[name='fpView']").val();
var maxWidth = 164;

$(function() {
	$("select[name='fpView']").change(function(){
		fpFilter = $(this).val();
		});
	});

var opt = 'FIN';
function refreshPageStatus() {
	var Pages = pageID.toString();
	
	//alert( 'op=refreshPageStatus&pagesID='+Pages+'&hash=<?= $_GET["hash"] ?>' );
	
	$.ajax	({
		url:"engine/vflatplan_ajax.php",
		data: 'op=refreshPageStatus&pagesID='+Pages+'&hash=<?= $_GET["hash"] ?>&fpver='+opt,
		dataType: 'json',
		success:function( data ) {
			for( var i = 0; i < pageID.length; i++ ) {
				if( data[i] != statusText[i] ) {
					statusText[i] = data[i];
					$( ".status"+(i+1) ).html( data[i] );
					fit_box();
					}
				}
			if( i < 2 ) {
				$( ".status2" ).html( '' );
				}
			
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
			
			setTimeout(function(){ refreshPageStatus(); }, 400);
			}
		});
	}
refreshPageStatus();

function changeOpt( val ) {
	$("#alter_"+opt).removeClass('alterSelected');
	opt = val;
	$("#alter_"+opt).addClass('alterSelected');
	
	$.ajax	({
		url:"engine/vflatplan_ajax.php",
		data: 'op=changeOpt&opt='+val,
		dataType: 'json',
		success:function( data ) {}
		});
	}

var firstRun = true;
function loadPages() {
	$.ajax	({
		url:"engine/vflatplan_ajax.php",
		data: 'op=loadPagePair&type=fpPreview&filter='+fpFilter+'&opt=FIN&maxWidth='+maxWidth+'&id=<?= $job[0][0] ?>&hash=<?= $_GET["hash"] ?>',
		dataType: 'json',
		success:function( data ) {
			//processChecker = false;
			if( txt != data[0] ) {
				$('.innerPreview').html( data[0] );
				txt = data[0];
				for( var a = 0; a < data[1].length; a++ ) {
					alterHandle( data[1][a] );
					}
				}

			if( firstRun ) {
				var thumbsScrollCorrection = parseInt( $(".innerPreview").height() )/2;
				var top = ( ( $(".innerPreview").scrollTop() + $("#"+parseInt( page )+"_selector").parent().offset().top )-$(".innerPreview").offset().top ) - ( thumbsScrollCorrection = parseInt( $(".innerPreview").height() )/2 )+$("#"+page+"_selector").parent().height();
				if( top < 0 ) top = 0;
				
				$('.innerPreview').animate({ scrollTop: ( top ) }, 500);
				firstRun = false;
				}
						
			setTimeout(function(){ loadPages(); }, 500);
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
if( file[0]['Left'] == "0" ) {

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
var actualPos = {};
var loadedCommandBindings = false;

$('body').mouseup(function() { 
			if( graphState != "" )
				stopDraw();
			});

$( '#content_box' )
	.mousedown(function() {
		if( graphState == "magnify" )
			startDraw();
		})
	.mouseup(function() { 
		if( graphState == "magnify" )
			stopDraw();
		})
	.mousemove(function(e) { coordinate(e); });

$( '.pagePreview' )
	.mousedown(function() {
		if( graphState != "" )
			startDraw();
		})
	.mouseup(function() { 
		if( graphState != "" )
			stopDraw();
		})
	.mousemove(function(e) { coordinate(e); });

function rendering( command, newZoom ) {
	var currentPos = {
		left: $('#content_box').scrollLeft(),
		top: $('#content_box').scrollTop()
		}
	if( currentPos.left != actualPos.left || currentPos.top != actualPos.top || command == "force" ) {	
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
		
		if( pages > 1 )
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
		$.ajax	({
			url:"vtesztAjax.php?zoom="+zoom,
			type: "POST",
			data: { positions : positions, file: file, colors: sendcolors, cBox: cBox, fpBox: fpBox, trimbox: trimbox, corr: correction },
			dataType: 'json',
			success:function( data ) {
				updateRenderModeIndicator( data );
				if( img > 2 ) img = 1;
				$("#renderedIMG"+img).css({
					left: $('#content_box').scrollLeft()+"px",
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
				if( viewComment && !loadedCommandBindings ) {
					setTimeout( function(){
						refreshComments('');
						}, 50);
					$(".commentEnabler").click(function(e){
						toggleComments(e);
						});
					$( document ).bind('keydown', function(e) {
						if( e.keyCode == "49" && !disable ) { $(".commentEnabler")[0].click(); }
						if( e.keyCode == "50" && !disable ) { $(".commentEnabler")[1].click(); }
						if( e.keyCode == "51" && !disable ) { $(".commentEnabler")[2].click(); }
						if( e.keyCode == "52" && !disable ) { $("#square").click(); }
						if( e.keyCode == "53" && !disable ) { $("#circle").click(); }
					
						if( e.keyCode == "32" && !disable ) {;
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
			});
		}
	}

$('#content_box').on('mousedown', function() {
	down = true;
	});

var norefresh = [ "compareTable", "compare_selector", "comp_operation", "compRange", "inner", "hover", "custom-menu", "ownerOnly", "commentBox", "replyComment", "subCancel", "subSave", "panel_top", "panel", "panelElement", "commentText", "commentEnabler", "square", "circle", "dot", "rightPanel", "rightPanel_top", "cyan", "magenta", "yellow", "kblack", "rightPanelElement", "rightPanel_bottom", "fpstatusbutton", "approveButton" ];
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
var winWidth = $(window).width();
var user = "<?= $visitor[0][1] ?>";
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
	disableZoom = false;
	if( !disableZoom ) {
		data = data.split( "&" );
		$("#renderedIMG1").hide( 0 ).attr('src', '' );
		$("#renderedIMG2").hide( 0 ).attr('src', '' );
		
		if( data != '?page=flatlpan_preview' ) {
			$('.activeGraph').each(function() {
				$(this).removeClass( 'activePanel' );
				$(this).removeClass( 'activeGraph' );
				$(this).attr('src', "plugins/images/"+$(this).attr('id')+".png");
				});		
	
			setState( "" );
			ajaxDisabled = false;
			tag = '';
			PageType = 'FIN';
			for( var i = 1; i < data.length; i++ ) {
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
		// Deferred rather than called inline: this runs the whole
		// stopDraw()->addText() chain (which ends in focusing the new
		// comment's textarea) synchronously inside the mousedown handler,
		// since a "dot" placement has no drag to wait for. Left inline,
		// that focus() call races the browser's own mousedown-driven
		// focus/blur handling for the *same* still-in-flight gesture and
		// reliably loses - the textarea appeared but never actually took
		// focus. rect/oval don't hit this because their addText() only
		// ever runs off the real mouseup, by which point mousedown's own
		// focus handling is long finished. Pushing this to a fresh tick
		// puts the dot tool on the same footing.
		setTimeout( function() {
			$( '.pagePreview' ).mouseup();
			}, 0 );
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
	if( drawing && graphState != "" && graphState != "magnify" && graphState != "measure" && graphState != "colorPicker" ) {
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

function createDiv( options, text, id, parent, callback ) {
	var style = "";
	for( var key in options ) {
		style += key+":"+options[key]+";";
		}

	jQuery('<div/>', {
    	id: id,
    	style: style
	}).appendTo( parent );
	if( id == "divTextBox" ) {
		$( "#"+id ).html( text ).show( 100, callback );
		$( "#"+id ).draggable({ cursor: "move" });
		}
	else {
		$( "#"+id ).html( text );
		if( callback ) callback();
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
	if( cBox[2] != "mediabox" ) {
		if( pages == "2" ) {
			correction.top = parseFloat( correction.top ) + pixel( ( parseFloat( cropbox[0]["Top"] ) - parseFloat( bleedbox[0]["Top"] ) ) ) + pixel( parseFloat( cBox[0].Bottom ) );		
			}
		else {
			correction.top = parseFloat( correction.top ) + pixel( ( parseFloat( cropbox[0]["Top"] ) - parseFloat( bleedbox[0]["Top"] ) ) ) + pixel( parseFloat( cBox[0].Bottom ) );
			}
		}

	var defLeft = 100/ zoom * ( parseFloat( $("#"+div_id+"_text").css("left") ) - correction.left );
	var defTop = 100/ zoom * ( parseFloat( $("#"+div_id+"_text").css("top") ) + correction.top - 2*parseFloat( $(".pagePreview").css("top") ) )+2*pixel( parseFloat( cBox[0]['Top'] ) );
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
		if( pages == "1" && page%2 == 1 ) {
			//sqlLeft -= pixel( parseFloat( cBox[0]['Left'] ) );
			}
			
		zoom = tempZoom;
		var sqlPage = parseInt( page );
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
		data: 'op=saveComment&pageType='+uploadData.pageType+'&PageVersion='+uploadData.PageVersion+'&pub='+uploadData.pub+'&shape='+uploadData.shape+'&parent='+uploadData.parent+'&left='+uploadData.left+'&top='+uploadData.top+'&width='+uploadData.width+'&height='+uploadData.height+'&page='+uploadData.page+'&text='+uploadData.text+'&user='+uploadData.user,
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
		left: ((window.outerWidth/2)-200)+'px',
		top: '130px',
		width: '283px'
		}, innerHTML, 'divTextBox', 'body', function() {
			$("#divText").focus();
			} );

	$("#divTextBox").addClass('floatCommentMenu');
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
	if( drawing && graphState != "" ) {
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
		$("#showComment").children(".normal").attr( "src", "plugins/images/hidecomment.png" );
		$("#showComment").children(".hover").attr( "src", "plugins/images/hidecomment_hover.png" );
		}
	else {
		$("#showComment").children(".normal").attr( "src", "plugins/images/showcomment.png" );
		$("#showComment").children(".hover").attr( "src", "plugins/images/showcomment_hover.png" );	
		}
	
	$("#commentdisplay_status").html( ( toggle[ target ] == "none" ? "<?= $lang['flatplan']['off'] ?>" : "<?= $lang['flatplan']['on'] ?>" ) );
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
		if( cBox[2] != "mediabox" && commentData.Page == page  ) {
			correction.left -= pixel( ( parseFloat( cropbox[0]["Left"] ) - parseFloat( bleedbox[0]["Left"] ) ), zoom_ );
			correction.left -= pixel( ( parseFloat( cropbox[0]["Left"] ) - parseFloat( bleedbox[0]["Left"] ) ), zoom_ );
			//correction.left = parseFloat( correction.left );
			if( page % 2 == 0 ) correction.left = parseFloat( correction.left ) + pixel( parseFloat( cBox[0].Left ) );
				correction.top = parseFloat( correction.top ) - pixel( parseFloat( cBox[0].Bottom ) );
			}
		else if( cBox[2] != "mediabox" ) {
			correction.left += pixel( ( parseFloat( cropbox[0]["Left"] ) - parseFloat( bleedbox[0]["Left"] ) ), 100 );
			correction.top = parseFloat( correction.top ) + pixel( parseFloat( cBox[0].Bottom ) );
			}
		var relPosition = {
			left: $(".pagePreview").offset().left - $("#content_box").offset().left,
			top : $(".pagePreview").offset().top - $("#content_box").offset().top
			};
		
		//alert( correction.left);
		
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
		
		var checker = ( $(".pagePreview").width() > $("#content_box").width() ? ".pagePreview" : "#content_box" );
		if( textLeft + 150 > $(checker).width() ) {
			textLeft = parseFloat( $('#comment_'+nextComment).css("left") ) - 150 - actualPlaceHolder;
			}
	
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
		if( cBox[2] != "mediabox" && commentData.Page == page ) {
			correction.left = parseFloat( correction.left ) + pixel( parseFloat( cBox[0].Left ) );
			correction.top = parseFloat( correction.top ) + pixel( parseFloat( cBox[0].Bottom ) );
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
			data: 'op=refreshComments&pgs='+pages+'&alter='+PageType+'&clicked='+clk+'&tag='+tag+'&type=<?= $_GET["type"] ?>&id='+pub+'&pack_id='+pack_id+'&p='+page,
			dataType: 'json',
			success:function( data ) {
				console.log( data );
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
	
var fpPages_width = parseInt( $("#fpPages").outerWidth() );

function fit_pages() {
	var footer = parseInt( $("#fpFooter").outerWidth() );
	var pagesLeft = (footer/2)-(parseInt($(".pages").outerWidth())/2 );
	$(".pages").css("left", pagesLeft+"px" );
	}

function fit_box() {
	if( $("#header").css( "display" ) == "block" ) {
		var header = 15+parseInt( $("#header").outerHeight());
		var ad_height = parseInt( $( window ).height() )-(parseInt( $("#header").outerHeight()) );
		}
	else {
		var header = 0;
		var ad_height = parseInt( $( window ).height() );
		}
	
	$('#content').height( ad_height );
	$('#fpPages, #fpToolBox').height( ad_height );
	$('#content_box').height( ad_height-(parseInt( $("#fpFooter").outerHeight() ) ) );
	
	minHeight = ad_height;
	
	if( parseInt( $("#fpPages").css("left") ) < 0 ) {
		$('#content_box, #fpFooter').width( $( window ).width()-parseInt( $("#fpToolBox").outerWidth() ) );
		}
	else {
		$('#content_box, #fpFooter').width( $( window ).width()-fpPages_width-parseInt( $("#fpToolBox").outerWidth() ) );
		}
	width = $('#content_box').width();
	height = $('#content_box').height();
	var newTop = parseInt( header+(ad_height/2)+40);
	
	previewHeight = parseInt( $("#fpPages").outerHeight()-parseInt( $(".inner").outerHeight()+15 ) );
	$('.innerPreview').height( previewHeight );

	fit_pages();
	var footer = parseInt( $("#fpFooter").outerWidth() );
	var pagesLeft = (footer/2)-(parseInt($(".pages").outerWidth())/2 );	
	if( compareMode == "off" ) {
		$("#leftArrow").css( "left", (pagesLeft-36)+"px" );
		$("#rightArrow").css( "left", (pagesLeft+parseInt($(".pages").outerWidth()))+"px" );
		$("#leftArrow_hover").css( "left", (pagesLeft-36)+"px" );
		$("#rightArrow_hover").css( "left", (pagesLeft+parseInt($(".pages").outerWidth()) )+"px" );
		}
	//console.log(( ((footer-pagesLeft)/2)-(parseInt($(".status1").outerWidth())/2 ) ) );
	$(".status1").css("left", ( ((footer-pagesLeft)/2)-(parseInt($(".status1").outerWidth())/2 ) )+"px" );
	var temp = pagesLeft+parseInt($(".pages").outerWidth());
	var maradt = (footer-temp)/2;
	$(".status2").css("left", ( (temp+maradt)-(parseInt($(".status2").outerWidth())/2 ) )+"px" );
		
	fit_toolbar();
	
	fpBox = {
		'Width': parseInt( $("#content_box").width() ),
		'Height': parseInt( $("#content_box").height() )
		}
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
	if( !isNaN( parseInt( file[1]["Height"] ) ) ) {
		var maxheight = ( parseInt( file[0]["Height"] ) > parseInt( file[1]["Height"] ) ? ( parseInt(file[0]["Top"])-parseInt(file[0]["Bottom"])) : (file[1]["Top"]-file[1]["Bottom"]) );
		}
	else {
		var maxheight = parseInt( file[0]["Top"] ) - parseInt( file[0]["Bottom"] );
		}
	
	var defHeight = pixel( maxheight, 100 );
	var defWidth = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ), 100 );
	
	if( zoomBox == undefined ) {
		if( defHeight > ( fpBox['Height'] -30 ) ) {
			var heightPer = ( ( fpBox['Height'] -30 ) / defHeight )*100;
			zoom = heightPer;
			var width = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ) );
			if( width > ( fpBox['Width'] -30 ) ) {
				var widthPer = ( ( fpBox['Width'] -30 ) / defWidth )*100;
				zoom = widthPer;
				}
			
			checkWidth =  pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ), zoom );
			}
		else {
			if( defWidth > ( fpBox['Width'] -30 ) ) {
				var widthPer = ( ( fpBox['Width'] -30 ) / defWidth )*100;
				zoom = widthPer;
				}

			else if( defHeight < ( fpBox['Height'] -30 ) ) {
				var heightPer = ( ( fpBox['Height'] -30 ) / defHeight )*100;
				zoom = heightPer;

				var width = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ) );
				if( width > ( fpBox['Width'] -30 ) ) {
					var widthPer = ( ( fpBox['Width'] -30 ) / defWidth )*100;
					zoom = widthPer;
					}				
				}
			}
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

$( document ).ready(function() {
	$(".pagePreview").fadeOut(0);
	fit_box();
	zoomCalc();
	reloadBG();
	});

$(window).resize(function(){
	fit_box();
	//zoomCalc();
	placeBox();
	winWidth = $(window).width();
	setDivCenter_visitor( "info_content" );
	});

function Redirect( val ) {
	var temp = val.split("_");
	
	location.href='?page=flatplan_preview&clk='+temp[2]+'&id='+temp[0]+'&p='+temp[2];
	}

var userGRP = "<?= $user[0][8] ?>";
var enableContext = 0;
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
			data: 'op=getComments&id='+$('#'+refreshTarget).attr('sqlid'),
			dataType: 'json',
			success:function( data ) {
				if( commentTXT != data[0] && !stopRefresh ) {
					$(".commentBox").html( data[0] );
					commentTXT = data[0];
					if( data[1] == true ) {
						var target = $('#'+refreshTarget).attr( "id" ).split( "_" );
						approveComment = $(".custom-menu").attr("selectedComment");
						$("#subApprove").prop("disabled", false );
						$("#subApprove").attr("onclick", "myContextMenu( 'approve', '"+$('#'+refreshTarget).attr( "id" )+"' )" );
						}
					else {
						$("#subApprove").prop("disabled", true );
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
		data: 'op=saveReply&id='+$('#'+target).attr('sqlid')+'&visitor=<?= $visitor[0][1] ?>&txt='+$('#replyComment').val().replace(/\n/g,"<br>"),
		dataType: 'json',
		success:function( data ) {
			$("#replyLoader").html( '' );
			$('#replyComment').val('');
			$("#replyComment").keyup();
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
				var text = "<div class='commentDrag'></div><div class='commentBox'></div><?= $lang['flatplan']['reply'] ?>:<textarea id='replyComment' style='width: 265px; height: 80px; resize: none;'></textarea><br><div class='anim' style='text-align: center;'><span id='subCancel' class='panelButton' style='display: inline-block; float: none; margin-left: 0px; margin-top: 5px;'><?= $lang['flatplan']['close'] ?></span><span id='subSave' class='panelButton' style='display: inline-block; float: none; margin-left: 8px; margin-top: 5px;' disabled style='margin-left: 15px;'><?= $lang['flatplan']['send'] ?></span><span id='subApprove' class='panelButton' style='display: inline-block; float: none; margin-left: 8px; margin-top: 5px;' disabled style='margin-left: 15px;'><?= $lang['flatplan']['approve_comment'] ?></span></div>";
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
				left: ((window.outerWidth/2)-200)+'px',
				top: '130px',
				width: '271px',
				'font-size': '12px',
				'line-height': '15px',
				'max-height': '469px',
				}, text, 'commentComment', 'body' );
			
			$( ".commentDrag" ).parent().draggable({ cursor: "move" });
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
				data: 'op=approveComment&id='+$('#'+target).attr('sqlid'),
				dataType: 'json',
				success:function( data ) {
					if( data == "success" ) {
						var temp = target.split("_");
						$('#'+target).addClass("commentApproved");
						$('#'+temp[0]+'_'+temp[1]+'_'+temp[2]).addClass("commentApproved");
						}

					// Same panel-close cleanup #subCancel's click handler
					// does - approving is just another way of closing this
					// panel, and skipping this (including never removing
					// #commentComment at all here) left `disable` stuck at
					// 1 forever, silently breaking the Space-key comment
					// toggle and the 1/2/3/4/5 shortcut keys.
					stopRefresh = true;
					refreshTarget = "";
					graphState = tempState;
					commentTXT = '';
					disable = 0;
					disableZoom = false;
					$('#commentComment').remove();
					}
				});
			break;
		case "delete":
			if( confirm( "<?= $lang['flatplan']['remove_com'] ?>" ) ) {
				$.ajax	({
					url:"engine/commentAjax.php",
					type: "GET",
					data: 'op=deleteComment&id='+$('#'+target).attr('sqlid'),
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

</script>