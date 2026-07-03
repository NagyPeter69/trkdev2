<script type="text/javascript" src="js/jquery.kinetic.js"></script>
<script type="text/javascript" src="js/preview.js"></script>
<link rel="stylesheet" type="text/css" href="css/preview.css">



<?php
	$historyBack = $_SERVER['HTTP_REFERER'];

	if( $_GET['type'] == 'ad' ) {
		$preview = sql_get( 'ads', 'id=\''.$_GET['id'].'\'', '*' );
		$pub = sql_get( 'publications', 'id="'.$preview[0][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
		switch( $preview[0][3] ) {
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
		
		$file_name = strtoupper( $preview[0][2] ).'_'.$magazine[0][3].'_'.$pub[0][10].'_'.$type;
		$dir = 'advertisements';
		$dirFiles = load_dir_files( $dir, $file_name );
		sort($dirFiles);
		}

	$max = 0;
	for( $i = 0; $i < count( $dirFiles ); $i++ ) { 
		if( strstr( $dirFiles[$i], '.jpg' ) && !strstr( $dirFiles[$i], '_lowres.jpg' ) ) {
			$max++;
			}
		}
	$max = intval( $max / 2 );
	$time = strtotime( $pub[0][11] );
	setlocale(LC_ALL,'hungarian');
	$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) );

	$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
	/*if( isset( $_GET['pub'] ) ) {
		$pub = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'" AND code="'.$_GET['pub'].'"', '*' );
		}
	else {
		$pub = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'" ORDER BY `code` ASC', '*' );
		$_GET['pub'] = $pub[0][10];
		}*/

	$pubs = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'" ORDER BY `code` ASC', '*' );
	$jobs2 = anotherPubs( $user );
	for( $i = 0; $i < count( $jobs2 ); $i++ ) {
		$pubs[] = $jobs2[$i];
		}
	usort($pubs, querySort(10) );
?>


<div id='ad_table_wrapper' style='border-top: 10px solid #FFF;'>
	<table id='ad_preview' width='100%' cellspacing='0' cellpadding='0' border='0'>
		<tr>
			<td valign='top' id="" align='left'>
				<div id='preview_img'><img id='preview_image' src='' height='99%'></div>
			</td>
			<td valign='top' width='90%' align='left' style='position: relative; background: rgb( 230, 230, 230 ); border-bottom: 4px solid #FFF; border-left: 10px solid #FFF;'>
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
					<div style="float:right; padding-right: 10px; font-size: 14px;">
						<div id='preview_close' onclick="window.location.href='<?= $historyBack ?>'"><?= $lang["ad_preview"]["back"] ?></div>
					</div>
				</div>
				<div id='preview_info' style='height: auto; font-size: 14px;'>
					<div id='preview_info2'>&nbsp;</div>

				</div>
				<div id='preview_footer'>
					<div src="" id='preview_previous' onclick="page_turn( 1, '-' )"><?= $lang["ad_preview"]["left"] ?></div>		
					<div src="" id='preview_next' onclick="page_turn( 1, '+' )"><?= $lang["ad_preview"]["right"] ?></div>
				</div>
			</td>

		</tr>
	</table>

<div id='preview2'>
	
	
	<? if( $_GET['type'] != 'ad' ) { ?>
	<div id='preview_zoomin' onclick="zoom( '#preview_image', '+' )"></div>
	<div id='preview_zoomout' onclick="zoom( '#preview_image', '-' )"></div>
	<script type="text/javascript">
		jQuery(document).ready(function(){
			if(window.addEventListener)
        		document.addEventListener('DOMMouseScroll', moveObject, false);
			document.onmousewheel = moveObject;
			});
	</script>
	<? } ?>

	<div id='preview_info'></div>

</div>
</div>
</div>

<script type='text/javascript'>
var pub = '<?= $_GET['pub'] ?>';
fit_ad_list();
function load_ad_already2() {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=load_ad_c&pub='+pub,
		dataType: 'json',
		success:function( data ) {
			if( txt2 != data ) {
				$('.ad_menu_already').html( data );
				txt2 = data;
				}
			setTimeout(function(){ load_ad_already2(); }, 1000);
			}
		});
	}

function load_ad_already() {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=load_ad_c&pub='+pub,
		dataType: 'json',
		success:function( data ) {
			$('.ad_menu_already').html( data );
			txt2 = data;
			setTimeout(function(){ load_ad_already2(); }, 1000);
			}
		});
	}

load_ad_already();

	fit_preview();
	var scale = '<?= $ad[0][3] ?>';
	var image_size = new Array();
	var image_def_height = '';
	var default_bg = '';
	var hiba_tomb = [];
	var footer_html = '';
	var current_page = '<?= $_GET["p"] ?>';
	var max_page = '<?= $max ?>';
	var user_id = '<?= $_SESSION["intra_user"] ?>';
	var job_id = '<?= $_GET["id"] ?>';
	var type = '<?= $_GET["type"] ?>';
	var mheight = $("#ad_preview").css('height');
	
	$( document ).ready(function() {
		load_preview2( current_page );
		$('#preview_img').kinetic();
    	if(window.addEventListener)
        	document.addEventListener('DOMMouseScroll', moveObject2, false);

	    document.onmousewheel = moveObject2;
    });
</script>
