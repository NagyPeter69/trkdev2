<script type="text/javascript" src="js/preview.js"></script>
<link rel="stylesheet" type="text/css" href="css/preview.css">

<?
$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*');

$user[0][15] = "mediabox";
	
$pageSeparate = $user[0][14];

$preview = sql_get( 'ads', 'id=\''.$_GET['id'].'\'', '*' );
$job_data = $preview;
$pub = sql_get( 'publications', 'id="'.$preview[0][1].'"', '*' );
$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
switch( $preview[0][3] ) {
	case '1/1':
		$type = 'F';
		break;
	case '2/1':
		$type = 'D';
		if( $_GET["p"] == "1" ) {
			$type .= "L";
			}
		if( $_GET["p"] == "2" ) {
			$type .= "R";
			}
		break;
	default:
		$type = 'P';
		break;
	}

$file_name = strtoupper( $preview[0][2] ).'_'.$magazine[0][3].'_'.$pub[0][10].'_'.$type;
$file_path = "advertisements/".$file_name.".pdf";

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
var advert = "<?= $path ?>";
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

<div id='fpPages' style="width: 400px; background: rgb( 230, 230, 230 );">
	<div class='ad_preview_title'>
		<?
			$ad = sql_get( 'ads', 'id="'.$_GET['id'].'"', '*' );
			$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
			switch( $ad[0][3] ) {
				case '1/1':
					$type = 'F';
					break;
				case '2/1':
					$type = 'D';
					break;
				default:
					$type = 'P';
					break;
				}
			$path = 'advertisements/'.strtoupper( $ad[0][2].'_'.strtoupper( $magazine[0][3] ).'_'.$pub[0][10].'_'.$type );
			$outer_path = 'advertisements/'.strtoupper( $ad[0][2].'_'.strtoupper( $magazine[0][3] ).'_'.$pub[0][10].'_'.$type );
			if( $type == 'D' ) {
				$xml = simplexml_load_file( $path.'L.xml' );
				$xml2 = simplexml_load_file( $path.'R.xml' );
				$errors['size'] = (string) $xml->results[0]->size;
				$errors['bleed'] = (string) $xml->results[0]->bleed;
				$errors['lowres'] = (string) $xml->results[0]->lowres;
				$errors['fontmissing'] = (string) $xml->results[0]->fontmissing;
				if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
					$okk1 = 1;
					}
				else {
					$okk1 = 0;
					}
				$errors['size'] = (string) $xml2->results[0]->size;
				$errors['bleed'] = (string) $xml2->results[0]->bleed;
				$errors['lowres'] = (string) $xml2->results[0]->lowres;
				$errors['fontmissing'] = (string) $xml2->results[0]->fontmissing;
				if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
					$okk2 = 1;
					}
				else {
					$okk2 = 0;
					}
				if( $okk1 == 1 && $okk2 == 1 ) $okk = 1;
				else $okk = 0;
				}
			else {
				$xml = simplexml_load_file( $path.'.xml' );
				$errors['size'] = (string) $xml->results[0]->size;
				$errors['bleed'] = (string) $xml->results[0]->bleed;
				$errors['lowres'] = (string) $xml->results[0]->lowres;
				$errors['fontmissing'] = (string) $xml->results[0]->fontmissing;
								
				if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
					$okk = 1;		
					}
				else {
					$okk = 0;
					}		
				}

			$finished = 0;
			if( $okk == 1 ) {
				if( $ad[0][8] == 'Feltöltés alatt' ) {}
				elseif( $ad[0][8] == '' ) {}
				elseif( $ad[0][8] == 'error' ) {}
				else {
					$finished = 1;
					}
				}
			if( $okk == 1 ) {
				if( $finished )	$class = 'finished';
				else $class = 'accepted';
				}
			else {
				$class = 'rejected';
				}						
		?>
		<div class='status_color <?= $class ?>' style="float:left">&nbsp;</div>
		<div style="float:left; padding-left: 10px; font-size: 14px;">
			<? echo '<b>'.strtoupper( $ad[0][2] ).'&nbsp;'.$ad[0][3].'</b> - '.$magazine[0][2].' '.$pub[0][10].'' ?> 
		</div>

	</div>
	<div id='preview_info' style='height: auto; font-size: 14px;'>
		<div id='preview_info2' style="text-align: left;">
<?php			
		$info = '';
		
		$remark = (string) $xml->remark;
		$errors['size'] = (string) $xml->results[0]->size;
		$errors['bleed'] = (string) $xml->results[0]->bleed;
		$errors['lowres'] = (string) $xml->results[0]->lowres;
		$errors['fontmissing'] = (string) $xml->results[0]->fontmissing;
		$e_count = 0;
		if( $errors['size'] == 'wrong_size' or $errors['lowres'] == 'true' or $errors['fontmissing'] == 'true' ) {
			$error = 1;
			$e = "<ul class='list'>";
			}
		else {
			if( $errors['bleed'] == 'no_bleed' ) { 
				$info .= '<br>';
				$error = 0;
				}
			}
		
		if( $errors['size'] == 'wrong_size' ) {
			$e .= "<li>".$lang['ad_preview']['wrong_size'].".</li>";
			$e_count++;
			}

		if( $errors['lowres'] == 'true' ) {
			if( $type == 'D' ) {
				if( $_GET['page'] == '1' ) {
					$img2 = 'advertisements/'.$file_name.'L_lowres.jpg';
					}
				if( $_GET['page'] == '2' ) {
					$img2 = 'advertisements/'.$file_name.'R_lowres.jpg';
					}
				}
			else {
				$img2 = 'advertisements/'.$file_name.'_lowres.jpg';
				}
			$e .= "<li>".$lang['ad_preview']['lowres'].".<span id='hide_low_res' onclick=\"toggle_lowres('hide', '".$img."' )\" style='display:none;'>&nbsp;".$lang['ad_preview']['lowres_hide']."</span><span id='show_low_res' onclick=\"toggle_lowres('show', '".$img2."' )\">&nbsp;".$lang['ad_preview']['lowres_show']."</span></li>";
			$e_count++;
			}
		if( $errors['fontmissing'] == 'true' ) {
			$e .= "<li>".$lang['ad_preview']['no_font'].".<br></li>";
			$e_count++;
			}			

		if( $errors['size'] == 'size_ok' && $errors['lowres'] == 'false' && $errors['fontmissing'] == 'false' ) {
			$okk1 = 1;
			}
		else {
			$okk1 = 0;
			}
		$finished = 0;
		if( $okk1 == 1 ) {
			if( $job_data[0][8] == 'Feltöltés alatt' ) {
				$text = $lang['ads']['uploading'];
				}
			elseif( $job_data[0][8] == '' ) {
				$text = $lang['ads']['check_ok'];
				}
			elseif( $job_data[0][8] == 'error' ) {
				$text = $lang['ads']['upload_failed'];
				}
			else {
				$finished = 1;
				$text = $lang['ads']['upload_ok'];
				}
			}
		else {
			$text = $lang['ads']['check_failed'];
			}
			
		$info .= $lang['ad_preview']['pic_size'].": ".(string) $xml->results[0]->dimensions."<br>";
		$info .= $lang['ad_preview']['upload_date'].": ".$job_data[0][5]."<br>";
		$info .= $lang['ads']['status'].": ".$text;
		if( $finished ) {
			if( $type == 'P' ) {
				$ad_detail = sql_get( 'partial_ads', 'ads_id="'.$job_data[0][0].'"', '*' );
				//$info .= " at page <span class='page_echo'>".$job_data[0][8]." ( ".$ad_detail[0][2]." )</span>";
				$info .= sprintf( $lang["ads"]["uploadresult2"], $job_data[0][8], $ad_detail[0][2] );
				}
			else {
				//$info .= " at page <span class='page_echo'>".$job_data[0][8]."</span>";
				$info .= sprintf( $lang["ads"]["uploadresult"], $job_data[0][8] );
				}
			$info .= "<br>";
			}
		
		if( $e_count > 1 ) { $info .= '<br><br>'.$lang['ad_preview']['errors'].':<br>'; }
		if( $e_count == 1 ) { $info .= '<br><br>'.$lang['ad_preview']['error'].':<br>'; }
		$info .= $e.'</ul>';

		if( $errors['size'] != 'wrong_size' && $errors['lowres'] != 'true' && $errors['fontmissing'] != 'true' ) {
			$info .= "<br><br>".$lang['ad_preview']['accepted'];
			if( $errors['bleed'] != 'bleed_ok' )
				if( $errors['bleed'] == "no_bleed" ) {
					$info .= "<br><span style='color: #CF2727;'>".$lang['ad_preview']['no_bleed']."</span>";
					}
				else {
					$info .= "<br>".$lang['ad_preview']['check_bleed'];
					}
			
			if( $errors['size'] != 'size_ok' ) {
				
				}
			}
		
		$code = sql_get( 'magazines', 'id="'.$pub[0][2].'"', 'code' );
		$ads = collectFromXml( 'xml/'.PMD.'.xml', $code[0][0], 'AdSizes', 'value' );
		$ads = $ads['AdSizes'];
		
		if( $errors['size'] != 'size_ok' ) {
			$info .= $lang["ads"]["supported"].":<br>";
			for( $i = 0; $i < count( $ads ); $i++ ) {
				$temp = explode( " ", $ads[$i] );
			
				if( $remark != '1_1' and $remark != '2_1' ) {
					if( $temp[0] != '1/1' and $temp[0] != '2/1' ) {
						$info .= "- ".$temp[0]." ".$lang["ads"][substr($temp[1], 0, -1)].", ".$lang["ads"][substr($temp[2], 0, -1)].": ".$temp[3]." x ".$temp[5]." mm<br>";
						}
					}
				elseif( $remark == '1_1' ) {
					if( $temp[0] == '1/1' ) {
						$info .= "- ".$temp[0]." ".$lang["ads"][substr($temp[1], 0, -1)].", ".$lang["ads"][substr($temp[2], 0, -1)].": ".$temp[3]." x ".$temp[5]." mm<br>";
						}
					}
				elseif( $remark == '2_1' ) {
					if( $temp[0] == '2/1' ) {
						$info .= "- ".$temp[0]." ".$lang["ads"][substr($temp[1], 0, -1)].", ".$lang["ads"][substr($temp[2], 0, -1)].": ".$temp[3]." x ".$temp[5]." mm<br>";
						}
					}
				}
			}
			
		if( $e_count > 1 ) {
			$footer = $lang['ad_preview']['rejected1'];
			if( $e_count > 1 ) { $footer .= ' '.strtolower( $lang['ad_preview']['errors'] ).' '; }
			else $footer .= ' '.strtolower( $lang['ad_preview']['error'] ).' ';
			$footer .= $lang['ad_preview']['rejected2'].'.';
			if( $errors['bleed'] != 'bleed_ok' )
				$footer .= '<br>'.$lang['ad_preview']['check_bleed'];
			}
		$info .= '<br><br>'.$footer.'';
		echo $info;
		?>			
		</div>
	</div>
	<div id='preview_footer'>
		<?php if( $_GET["p"] == "2" ) { ?>
			<div src="" id='preview_previous' onclick="window.location.href='?page=advertisement_preview&id=<?= $_GET["id"] ?>&p=1'"><?= $lang["ad_preview"]["left"] ?></div>		
		<? } ?>
		<?php if( $_GET["p"] == "1" ) { ?>
			<div src="" id='preview_next' onclick="window.location.href='?page=advertisement_preview&id=<?= $_GET["id"] ?>&p=2'"><?= $lang["ad_preview"]["right"] ?></div>
		<? } ?>
	</div>

	<div id="backButton" style='position: absolute; bottom: 10px; font-size: 13px; height: 29px; left: 50%; -webkit-transform: translateX(-50%); transform: translateX(-50%)'>
		<div onclick="window.location.href='?page=advertisement'" style="margin-left: 1px; margin-top: 4px;" class="panelButton autoWidth"><?= $lang["ad_preview"]["back"] ?></div>
	</div>		
</div>

<div id='fpToolBox' style="left: 400px;">
	<? include_once( "plugins/advert_rightPanel.php" ); ?>
</div>

<?
$zoom = 100;
$bgDPI = 72;

$file = array();

if( is_file( $file_path ) ) {
	$file[0]["Name"] = $file_path;
	$sizes = getBBox( $file_path, "" );
	//var_dump( $sizes );
	$file[0]["Right"] = $sizes['Right'];
	$file[0]["Top"] = $sizes['Top'];
	$file[0]["Width"] = $sizes['Width'];
	$file[0]["Left"] = 0;
	$file[0]["Bottom"] = 0;
	}

$terminalPath = "/var/www/intra/client";
$postfix = $_SESSION['intra_user'];

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

$fullSizes = ( $file[0]["Right"]-$file[0]["Left"] );

$boxSize = array( 
	"width" => intval( pixel_( $fullSizes, $zoom ) ),
	"height" => intval( pixel_( $sizes['Height'], $zoom ) )
	);

	
$dcolors = getColors( "../../".$file[0]["Name"] );
$dtitles = getColorTitles( "../../".$file[0]["Name"] );

?>
<div id='content_wrapper' style='position: absolute; left: 435px; overflow: hidden;'>
	<?php
	
	if( $errors['lowres'] == 'true' ) {
		echo "<div id='lowres' style='display: none; width: 100%; height: 100%; background: rgb(178, 178, 178); z-index: 999; left: 0; top: 0; position: absolute;'>";
			echo "<div style='padding: 20px; height: 100%; width: 100%; box-sizing: border-box;'>";
				list($w, $h) = getimagesize( $img2 );
				if( $w > $h ) $scale = "width";
				else $scale = "height";
				
				echo "<img src='".$img2."' ".$scale."='100%' >";
			echo "</div>";
		echo "</div>";
		}	
		
	?>
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

<div id='fpFooter' style="left: 435px;">
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

	<div style='position: absolute; left: 15px;'>
    <div id="zoomdiv">
      <input id="zoomLevel" onfocus="this.select();" onkeypress="return isEnter(event)" class='zoomclass' type='text' onfocus="this.select();">
      <font color="#FFFFFF" style='font-size: 10px;'>%</font>
    </div>
   	<div id="zoomRange"></div>
	</div>
</div>

<input type="hidden" id='renderCounter' value="0" onchange="loadingBar( $(this).val() )">

<script>
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

var tempSafety = (safety*changer);

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

$(function() {
	$("select[name='fpView']").change(function(){
		fpFilter = $(this).val();
		});
	});

function changeOpt( val ) {
	$("#alter_"+opt).removeClass('alterSelected');
	opt = val;
	$("#alter_"+opt).addClass('alterSelected');
	
	$.ajax	({
		url:"engine/flatplan_ajax.php",
		data: 'op=changeOpt&opt='+val,
		dataType: 'json',
		success:function( data ) {}
		});
	}

var firstRun = true;
function loadPages() {
	$.ajax	({
		url:"engine/flatplan_ajax.php",
		data: 'op=loadPagePair&type=fpPreview&filter='+fpFilter+'&maxWidth='+maxWidth+'&id=<?= $_GET["id"] ?>',
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
				var top = 0;
				
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
	//console.log( zoom+", "+newZoom );
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
				
		$(".pagePreview").css({
			"width": boxSize.width+"px",
			"height": boxSize.height+"px"
			});

		$("#state_a, #state_b, #left_state, #state_a_img_container").css({
			"width": boxSize.width+"px",
			"height": boxSize.height+"px"
			});			
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
			positions['left'] = file[0].Left;
			positions['right'] = file[0].Right;
			positions['width'] = pixel( file[0].Right-file[0].Left );
			positions['small'] = "true";
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
			url:"advertrenderAjax.php?zoom="+zoom+"&p=<?= $_GET['p']?>",
			type: "POST",
			data: { positions : positions, file: file, colors: sendcolors, cBox: cBox, fpBox: fpBox, trimbox: trimbox, corr: correction },
			dataType: 'json',
			success:function( data ) {
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

				$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
				if( graphState == ""  ) {
					 $('#content_box').kinetic( 'attach' );
					 }
				if( graphState == "magnify" ) {
					$( "#zoomRange" ).slider( "option", { disabled: false } );
					}
				disableZoom = false;
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
var winWidth = $(window).width();
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
		$( "#"+id ).draggable({ cursor: "move" });
		}
	else {
		$( "#"+id ).html( text );
		}
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
	
var fpPages = parseInt( $("#fpPages").outerWidth() );

function fit_pages() {
	var footer = parseInt( $("#fpFooter").outerWidth() );
	var pagesLeft = (footer/2)-(parseInt($(".pages").outerWidth())/2 );
	$(".pages").css("left", pagesLeft+"px" );
	}

function fit_box() {
	fpPages = parseInt( $("#fpPages").outerWidth() );
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
	
	$('#content_box, #fpFooter').width( $( window ).width()-fpPages-parseInt( $("#fpToolBox").outerWidth() ) );
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
	//console.log("zoomcalc");
	var defHeight = pixel( (file[0]['Top'] - file[0]['Bottom'] ), 100 );
	var defWidth = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ), 100 );
	
	//console.log( zoomBox );
	if( zoomBox == undefined ) {
		//console.log( defHeight+" > "+fpBox['Height']+" - 30" );
		if( defHeight > ( fpBox['Height'] -30 ) ) {
			var heightPer = ( ( fpBox['Height'] -30 ) / defHeight )*100;
			zoom = heightPer;
			var width = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ) );
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
	winWidth = $(window).width();
	fit_box();
	zoomCalc();
	placeBox();
	});

function Redirect( val ) {
	var temp = val.split("_");
	
	location.href='?page=flatplan_preview&clk='+temp[2]+'&id='+temp[0]+'&p='+temp[2];
	}

var userGRP = "<?= $user[0][8] ?>";
var enableContext = 1;
var contextWidth = $(".custom-menu").width();

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
var refreshTarget = "";

</script>