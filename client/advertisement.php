<?php
header('Content-Type: text/html; charset=utf-8');	
if( !$rights['ad_view'] && !$rights['ad_send'] && !$rights['ad_upload'] && !$rights['ad_delete'] ) {
	header( 'Location: ?page=' ) ;
	}
?>
<link href="css/main.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="css/jquery-ui.css">

<script type="text/javascript">
jQuery(document).ready(function(){
    $( document ).tooltip({
        tooltipClass: "floatMenu"
        });

    $("[title]").each(function(){
    	$(this).tooltip({ tooltipClass: "floatMenu", content: $(this).attr("title")} );
		});  
	});
</script>
<?
$finished = false;
if( isset( $_GET['type'] ) && isset( $_GET['id'] ) ) {
	include_once( 'preview.php' );
	}
else {
	if( isset( $_GET['remove_ad_size'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher' );
		$size = sql_get( 'ad_sizes', 'id="'.$_GET['remove_ad_size'].'"', 'magazine_id' );
		$mag = sql_get( 'magazines', 'id="'.$size[0][0].'"', 'publisher_id' );
		
		if( $user[0][0] == $mag[0][0] ) {
			sql_delete( 'ad_sizes', 'id="'.$_GET['remove_ad_size'].'"' );
			}
		}
		
	if( isset( $_POST['size'] ) ) {
		$names = array( 'magazine_id', 'size', 'orient', 'cover', 'width', 'height' );
		$values = array( $_POST['magazine_id'], $_POST['size'], $_POST['orient'], $_POST['cover'], $_POST['width'], $_POST['height'] );
		
		sql_add( 'ad_sizes', $names, $values );
		}


	if( isset( $_GET['del'] ) ) {
		$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*');
		$ad = sql_get( 'ads', 'id="'.$_GET['del'].'"', '*');
		$pub = sql_get( 'publications', 'id="'.$ad[0][1].'"', '*' );

		if( $sql[0][4] == $pub[0][1] ) {
			$array = array();
			$array["client"] = /*$client[0][1]*/'Colorcom';
			$array["jobCode"] = /*$magazine[0][3]*/'TST';
			$array["issue"] = $pub[0][10];
			$array["event"] = 'delete_ad';
			$array["description"] = strtoupper( $ad[0][2] );
			$array["remark"] = str_replace( '/', '_', $ad[0][3] );
	
			$myxml = array_to_xml( $array, 'eventComm' );
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($myxml);
			$dom->formatOutput = true;
			$target = 'uploads/orders';
			$xml_name = 'remove_'.$_GET['del'].'.xml';
			
			if( file_put_contents( $target.'/'.$xml_name , $dom->saveXML() ) ) {
				$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
				switch( $ad[0][3] ) {
					case '2/1':
						$type = 'D';
						break;
					case '1/1':
						$type = 'F';
						break;
					default:
						$type = 'P';
						break;
					}
				$file_name = strtoupper( $ad[0][2] ).'_'.$magazine[0][3].'_'.$pub[0][10].'_'.$type;
				$dir = 'advertisements';
				$dirFiles = load_dir_files( $dir, $file_name );
				for( $y = 0; $y < count( $dirFiles ); $y++ ) {
					$secu = explode( '_', $dirFiles[$y] );
					if( strtoupper( $ad[0][2] ) == strtoupper( $secu[0] ) ) {
						if ( strstr( $dirFiles[$y], $file_name ) ) { 
							@unlink( $dir.'/'.$dirFiles[$y] );
							}
						}
					
					}
				sql_delete( 'ads', 'id="'.$ad[0][0].'"' );
				}			
			}	
		}
	$id = '';
	
	if( !isset( $_GET['id'] ) && $user[0][11] != "" ) {
		$temp = explode( "_", $user[0][11] );
		$tempMag = sql_get( 'magazines', 'code="'.$temp[0].'"', '*' );
		$tempPub = sql_get( 'publications', 'magazine_id="'.$tempMag[0][0].'" AND code="'.$temp[1].'"', '*' );
		if( $tempPub != "" ) {
			$_GET['id'] = $tempPub[0][0];
			$_GET['code'] = $tempPub[0][10];
			}
		else {
			$tempPub = sql_get( 'publications', 'magazine_id="'.$tempMag[0][0].'"', '*' );
			if( $tempPub != "" ) {
				$_GET['id'] = $tempPub[0][0];
				$_GET['code'] = $tempPub[0][10];
				}
			else {
				$tempPub = sql_get( 'publications', 'publisher_id="'.$user[0][4].'"', '*' );
				$_GET['id'] = $tempPub[0][0];
				$_GET['code'] = $tempPub[0][10];
				}
			}
		}

	if( isset( $_GET['id'] ) ) { $id = ' AND id="'.$_GET['id'].'"'; }
		
	$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
	$alter_pubs = anotherPubs( $user, 'publisher_id' );
	$alter = array();
	foreach( $alter_pubs as $a_pubs ) {
		$alter[] = $a_pubs[0];
		}
	
	if( isset( $_GET['pub'] ) ) {
		$pub = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'" AND code="'.$_GET['pub'].'"'.$id.'', '*' );
		if( $pub[0][0] == '' ) {
			for( $i = 0; $i < count( $alter ); $i++ ) {
				if( $pub[0][0] != '' ) break;
				
				$pub = sql_get( 'publications', 'publisher_id="'.$alter[$i].'" AND code="'.$_GET['pub'].'"'.$id.'', '*' );
				}
			}
		}
	else {
		//$pub = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'"'.$id.' ORDER BY `code` ASC', '*' );	
		//echo '1 '.$id.' ORDER BY `code` ASC';
		$pub = sql_get( 'publications', '1 '.$id.' ORDER BY `code` ASC', '*' );	
		if( !isset( $_GET['code'] ) ) {
			$jobs2 = anotherPubs( $user );
			for( $i = 0; $i < count( $jobs2 ); $i++ ) {
				$pub[] = $jobs2[$i];
				}
			usort($pub, querySort(10) );
			}
		if( $pub[0][0] == '' ) {
			for( $i = 0; $i < count( $alter ); $i++ ) {
				if( $pub[0][0] != '' ) break;
				
				$pub = sql_get( 'publications', 'publisher_id="'.$alter[$i].'"'.$id.' ORDER BY `code` ASC', '*' );
				}
			}
					
		$_GET['pub'] = $pub[0][10];
		}
		
	if( !isset( $_GET['id'] ) or $_GET['id'] == '' ) {
		$pub = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'" ORDER BY `code` ASC', '*' );
		$jobs2 = anotherPubs( $user );
		for( $i = 0; $i < count( $jobs2 ); $i++ ) {
			$pub[] = $jobs2[$i];
			}
		usort($pub, querySort(10) );	
		$_GET['id'] = $pub[0][0];
		
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
	$pn = (string) $xml->Item[$x]->PageNumbering;	
	
	$time = strtotime( $pub[0][11] );
	setlocale(LC_ALL,'hungarian');
	$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) );

?>
<div id='ad_table_wrapper'>
<table id='ad_table' cellspacing='0' cellpadding='0' border='0'>
	<tr>
		<td id='ad_menu' valign='top' style='width: 229px; font-size: 14px; position: relative;'>
			<div id='magazine_content' style="background: rgb(227, 227, 227);">
				<div style="padding: 10px; padding-bottom: 0px; text-align: left;">
				<?php if( $user[0][4] != 0 ) { ?>
					<div style='float: left;'>
						<select style="margin-left: -1px; margin-top: 2px;" onchange="Redirect( $(this).val() )">
						<?
							$pubs = getShortPubs( $user[0] );
							for( $i = 0; $i < count( $pubs ); $i++ ) {
								$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code, type' );

								echo "<option value='".$pubs[$i][0]."_".$pubs[$i][10]."' ";
								if( $_GET['code'] == $pubs[$i][10] && $_GET['id'] == $pubs[$i][0] )
									echo "selected";
								echo ">".( $magazine[0][1] == "Regular" ? $magazine[0][0]." ".$pubs[$i][10] : $magazine[0][0] )."</option>";
								}

						$pubf = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
						$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name' );

						?>
						</select>
					</div>
					<div style='float: left; margin-left: 5px; margin-top: 1px;'>
						<img title="<?= $magf[0][0] ?><br/>Issue: <?= $pubf[0][10] ?><br/>Deadline: <?= $pubf[0][11] ?>" src="images/icons/info.png" height="20px">
					</div>
					<div style='clear:both;'></div>
				<?php } else {
					$pubf = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
					$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name' );
				} ?>
				
				<?php if( $xml->Item[$x]->Type != "Regular" ) { ?>
					<?php
					$pages = $pubf[0][6];
					if( $xml->Item[$x]->PageNumbering == "American" ) {
						$pages = GetHighestPageNumber(  $xml->Item[$x], $pubf );
						}
					?>
					<div style='margin-top: 4px; color: #292377;'><?= $lang['ads']['pages'].": ".$pages ?></div>
				<?php } ?>
				</div>
			</div>		
			<div id='ad_menu_title' class='ad_menu_title'>
				<div class='ad_menu_titleText' style="float:left; margin-left: 10px;"><?= $lang['ads']['already_uploaded'] ?>:</div>
				<div style="clear:both;"></div>
			</div>
			<div id='ad_menu_content' class='ad_menu_content' style="padding-bottom: 10px;">
				<div class='ad_menu_upload' style="display: none;">&nbsp;</div>
				<div class='ad_menu_already' style='font-size: 13px; color: #292377;'></div>
			</div>
				<?php
				$fin = array( "archived", "approved", "archiving", "stopped" );
				if( in_array( $pub[0][12], $fin ) ) {
					$finished = true;
					}
					
				?>
				<? if( $rights['ad_send'] && $finished == false ) { ?>
				<div id="footer_pos" style="bottom: 0px;">
					<div class='ad_menu_footer'>
						<div style="float:left; margin-left:10px;"><?= $lang['ads']['new_ad'] ?></div>
					</div>
					<div class='ad_menu_footer_content' style='text-align: left;'>
						<div style='padding: 7px; padding-top: 0px;'>
						<form id="ad_upload" name="ad_upload" method="post" enctype="multipart/form-data">
							<input type="hidden" name="pub" id="pub" value="<?= $_GET['pub'] ?>">
							<input type="hidden" name="p_id" id="p_id" value="<?= $_GET['id'] ?>">
							<table collspan="0" rowspan="0">
								<tr>
									<td align="left"><?= $lang['ads']['new_ad_name'] ?></td>
									<td align="left"><input type="text" onkeyup="removeSpace( this )" onchange="removeSpace( this )" onkeypress="return alpha(event, allow_in)" maxlength="16" name="job_code" id="job_code"></td>
								</tr>
								<tr>
									<td align="left"><?= $lang['ads']['new_ad_size'] ?></td>
									<td align="left"><span id='size_spot'></span></td>
								</tr>
								<tr>
									<td align="left"><?= $lang['ads']['file'] ?></td>
									<td align="left">
										<span id="fileLabel" style="cursor: pointer;"><button onclick="$('#file').click(); return false;"><?= $lang['ads']['browse'] ?></button></span>
										<span id="fileSelected" onclick="$('#file').click(); return false;" style="cursor: pointer;"></span>
										<input type="file" name="file" id="file" style="width: 200px; display: none;">
									</td>
								</tr>								
							</table>
							</div>
							<div style='text-align: center'>
								<button id="ad_sender" class="panelButton buttonTag" disabled onclick="pre_check(); return false;"><?= $lang['ads']['new_ad_upload'] ?></button>
								<div id="progress_info"><div id="progress"></div></div>
								<div id="progress_percent">&nbsp;</div>
							</div>
						</form>
					</div>
				</div>
				<? } ?>
		</td>
		<td valign="top">
			<div id="finishedIssue" style="pointer-events: none; display: <?= ( $finished ? "block" : "none" ) ?>; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; background-color: transparent; z-index: 900; margin: auto; vertical-align: middle;">
				<div style="display: table; width: 100%; height: 100%;">
					<div style="display: table-cell; height: 100%; width: 100%; vertical-align: middle; font-size: 20px; padding-left: 229px; pointer-events: none;z-index: 100;">
						<div style="width: 100%; height: 100%; position: relative;">
							<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;  background-color: rgba( 160,160,160, 1); padding: 20px 40px 20px 40px;">
								<?= $lang["filetransfer"]["finishedIssue"] ?>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div id='ad_list' style="overflow: auto; overflow-x: hidden;">
				<table width='100%' id='job_names' class='ad_listing' cellspacing='0' cellpadding='0'>
					<tbody>

					</tbody>
				</table>	
			</div>
		</td>
	</tr>
</table>
</div>

<ul id='customMenu' class='custom-menu floatMenu' style='padding: 8px !important; padding-top: 6px !important;'>
  <li data-action="first"><?= $lang["ads"]["remove_ad"] ?></li>

 	<? if( $rights["proof"] ) { ?>
  		<li data-action="proof"><?= $lang["flatplan"]["proof"] ?></li>
  	<? } ?>
  
  <li id="force" data-action="force"><?= $lang["ads"]["force"] ?></li>
 
</ul>

<script>
function removeSpace( obj ) {
	var temp = $( obj ).val();
	temp = temp.replace(" ", "");
	$( obj ).val( temp );
	}
	
function adExtra( type ) {
	var value = parseInt( $("input[name="+type+"]").val() );
	
	if( isNaN( value ) ) {
		value = 0;
		}

	$("input[name="+type+"]").val( value );
	}

var p_id = '<?= $_GET["id"] ?>';
function Redirect( val ) {
	var temp = val.split("_");
	
	location.href='?page=advertisement&id='+temp[0]+'&code='+temp[1];
	}

$('#file').change( function() {
	var myFile = $('#file').prop('files');
	if( myFile.length > 0 ) {
		var temp = myFile[0]["name"];
		var temp2 = temp.split(".");
		temp2 = temp2.slice(0, -1);
		temp = temp2.join(".");
		var origname = temp;
		
		if( temp.length > 22 ) {
			temp = temp.substring(0, 7)+"..."+temp.slice(-7);
			$("#fileSelected").attr( "title", origname );
			}
			
		
		$("#fileSelected").html( temp );
		$("#fileLabel").hide(0);
		$("#fileSelected").show(0);
		}
	else {
		$("#fileSelected").html( "" );
		$("#fileSelected").removeAttr("title");
		$("#fileLabel").show(0);
		$("#fileSelected").hide(0);
		}	
	
	var counter = $('#ad_upload input[value!=""]').length;
	if( counter == ( $('#ad_upload input').length ) && $('#sizer').val() != '' ) {
		$("#ad_sender").removeAttr("disabled");
		}
	else {
		$("#ad_sender").attr("disabled", "disabled");
		}
	});
	
$('#job_code').keyup( function() {
	var counter = $('#ad_upload input[value!=""]').length;
	if( counter == ( $('#ad_upload input').length ) && $('#sizer').val() != '' ) {
		$("#ad_sender").removeAttr("disabled");
		}
	else {
		$("#ad_sender").attr("disabled", "disabled");
		}
	});

var ad_content_def = '<?= $text ?>';
var txt = '';
var txt2 = '';
var user = '<?= $_SESSION['intra_user'] ?>';
var pub = '<?= $pub[0][0] ?>';
var id = '<?= $_GET['id'] ?>';
var max_page = <?= intval( $pub[0][6] ); ?>;
var allow_in = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
var allowed_chars='BbCc1234567890';
var del_confirm = '<?= $lang["ads"]["confirm_delete"] ?>';

function remove_ad( type, id ) {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=remove_ad&type='+type+'&id='+id,
		dataType: 'json',
		success:function( data ) {}
		});
	}	

function get_sizes() {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=get_sizes&id='+pub+'&finished=<?= (string) $finished ?>',
		dataType: 'json',
		success:function( data ) {
			$('#size_spot').html( data[0] );
			if( data[1] != "" ) {
				$("#ad_list").html( data[1] );
				}
			}
		});
	}

$(window).resize(function(){
	if( !$.browser.device ) {
		ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight());
		$('#ad_list').height( ad_height );	
		}
	fit_adlist();
	get_sizes();	
	});

$(window).resize(function(){
	maxWidth = $(window).width()-60-260;
	//ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight()+parseInt( $("#menu").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-295;
	//$('#ad_menu_content').height( ad_height );
	});

function fit_adlist() {
	console.log( "bent" );
	var check = $(window).height()-parseInt( $("#header").outerHeight() );
	var height = 0;
	var footerPos = parseInt( $("#footer_pos").outerHeight() );
	if( isNaN( footerPos ) ) footerPos = 0;
		
	height = parseInt( $(".ad_listing").outerHeight() )-parseInt( $("#magazine_content").outerHeight() )-parseInt( $("#ad_menu_title").outerHeight() );
	if( $(".ad_menu_upload").css("display") == "none" ) {
		if( height < ( parseInt( $(".ad_menu_already").height() ) ) ) {
			height = parseInt( $(".ad_menu_already").height() );
			}
		}
	else {
		if( height < ( parseInt( $(".ad_menu_already").height() )+parseInt( $(".ad_menu_upload").height() ) )+10 ) {
			height = parseInt( $(".ad_menu_already").height() )+parseInt( $(".ad_menu_upload").height() )+10;
			}
		}

	if( parseInt( $(".ad_listing").outerHeight() ) > 240 ) {
		height = 231-parseInt( $("#magazine_content").outerHeight() )-parseInt( $("#ad_menu_title").outerHeight() );
		console.log( "1.b "+height );
		if( $(".ad_menu_upload").css("display") == "none" ) {
			
			if( height < ( parseInt( $(".ad_menu_already").height() ) ) ) {
				height = parseInt( $( "#mainPage" ).height() )-(parseInt( $("#header").outerHeight() )+parseInt( $("#magazine_content").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-footerPos-10;
				}
			}
		else {
			console.log( "1.d "+height );
			if( height < ( parseInt( $(".ad_menu_already").height() )+parseInt( $(".ad_menu_upload").height() ) )+10 ) {
				height = parseInt( $(".ad_menu_already").height() )+parseInt( $(".ad_menu_upload").height() )+10;
				}
			}
		}

	if( (height+footerPos ) > check ) {
		height = parseInt( $( "#mainPage" ).height() )-(parseInt( $("#header").outerHeight() )+parseInt( $("#magazine_content").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-footerPos-10;
		}
	
	console.log( "3. "+height );
	$('#ad_menu_content').css( "height", height+"px" );
	}

$( document ).ready(function() {
	if( !$.browser.device ) {
		ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight());
		$('#ad_list').height( ad_height );	
		}
	fit_adlist();
	get_sizes();
	});

$('#pages').bind('paste', function(e) {
	e.preventDefault();
	});

function ad_menu_default() {
	if( haveUpload ) {
		$(".ad_menu_titleText").html("<?= $lang['ads']['already_uploaded'] ?>:");		
		}
	else {
		$(".ad_menu_titleText").html("<?= $lang['ads']['no_ads'] ?>");
		}

	$('.ad_menu_upload').hide('fast',function(){ 
		$('#footer_pos').fadeIn('fast');
		$('.ad_menu_upload').html("");
		fit_adlist();
		});
	}

function init_drag() {	
	var o_height = 0;
	var o_width = 0;
	
	function handleDragStop( event, ui ) {
		var target = $('#ad_menu_content').offset();
		var t = new Array();
		t["l"] = parseInt(target.left);
		t["t"] = parseInt(target.top);
		t["r"] = parseInt(target.left+$('#ad_menu_content').outerWidth());
		t["b"] = parseInt(target.top+$('#ad_menu_content').outerHeight());
		
		var o = new Array();
		o["l"] = parseInt( ui.offset.left );
		o["t"] = parseInt( ui.offset.top );
		o["r"] = parseInt( ui.offset.left )+o_width;
		o["b"] = parseInt( ui.offset.top )+o_height;
		
		if( mouseX >= t['l']-30 && mouseX <= t['r']+30 && mouseY >= t['t']-60 && mouseY <= t['b']+60 ) {
			//if( $(this).attr('status') == 1 || $(this).attr('status') == 0 ) {
			if( $(this).attr('status') == 1 || $(this).attr('status') == 2 || $(this).attr('force') == 1 ) {
				var id = $(this).attr('id');
				$.ajax	({
					url:"engine/ajax.php",
					data: 'op=set_ad_pages&id='+id+'&pageNumbering=<?= $pn ?>',
					dataType: 'json',
					success:function( data ) {
						//alert( data['title'] );
						$('.ad_menu_titleText').html( "<div>"+data['title']+"</div>" );
						$('.ad_menu_upload').html( data['content'] );
						$('#pages').val('2');
						$('#pages').change();
						$('.ad_menu_upload').show('fast',function(){ fit_adlist(); });
						
						$('#footer_pos').fadeOut('fast');
						}
					});
				}
			}
		}

	$('div[id*="ad_img_"]').each( function(){
		//if( $(this).attr('allapot') != 'Feltöltés alatt' && ( $(this).attr('status') == "1" || $(this).attr('status') == "0" ) ) {
		if( $(this).attr('allapot') != 'Feltöltés alatt' && ( $(this).attr('status') == "1" || $(this).attr('status') == "2" || $(this).attr('force') == "1" ) ) {
			o_width = parseInt( $(this).outerWidth() );
			o_height = parseInt( $(this).outerHeight() );
			$(this).draggable({
				cursor: 'move',
				containment: 'document',
				helper: 'clone',
				stop: handleDragStop,
				start: function(e, ui) {
 					$(ui.helper).addClass("ui-dragged");
 					}
				});
			}
		});	
	}

var mouseX;
var mouseY;
$(document).mousemove( function(e) {
   mouseX = e.pageX; 
   mouseY = e.pageY;
}); 


var haveUpload = false;
function load_ad_already2() {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=load_ad_c&pub='+pub+'&id='+id,
		dataType: 'json',
		success:function( data ) {
			if( txt2 != data ) {
				if( data == "" ) {
					haveUpload = false;
					$(".ad_menu_titleText").html("<?= $lang['ads']['no_ads'] ?>");
					}
				else {
					haveUpload = true;
					$(".ad_menu_titleText").html("<?= $lang['ads']['already_uploaded'] ?>:");
					}
				$('.ad_menu_already').html( data );
				txt2 = data;
				fit_adlist();
				}
			setTimeout(function(){ load_ad_already2(); }, 200);
			}
		});
	}

function load_ad_already() {
	
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=load_ad_c&pub='+pub,
		dataType: 'json',
		success:function( data ) {
			if( data == "" ) {
				haveUpload = false;
				$(".ad_menu_titleText").html("<?= $lang['ads']['no_ads'] ?>");
				}
			else {
				haveUpload = true;
				$(".ad_menu_titleText").html("<?= $lang['ads']['already_uploaded'] ?>:");
				}
			$('.ad_menu_already').html( data );
			txt2 = data;
			fit_adlist();
			setTimeout(function(){ load_ad_already2(); }, 0);
			}
		});
	}

load_ad_already();
var maxWidth = $(window).width()-60-260;

function load_adverts2() {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=load_adverts&finished=<?= (string) $finished ?>&maxwidth='+maxWidth+'&id='+p_id+'&pub='+pub+'&user='+user+'&id='+id,
		dataType: 'json',
		success:function( data ) {	
			if( txt != data ) {
				$('tbody', '.ad_listing').html( data );
				txt = data;
				init_drag();
				fit_adlist();
				}
			setTimeout(function(){ load_adverts2(); }, 500);
			}
		});
	}

function load_adverts() {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=load_adverts&finished=<?= (string) $finished ?>&maxwidth='+maxWidth+'&id='+p_id+'&pub='+pub+'&user='+user,
		dataType: 'json',
		success:function( data ) {
			$('tbody', '.ad_listing').html( data );
			txt = data;
			init_drag();
			fit_adlist();
			setTimeout(function(){ load_adverts2(); }, 500);
			}
		});
	}

load_adverts();

function put_ad() {
	var id = $('#hid' ).val();
	var pages = $('#pages' ).val().toUpperCase();
	var part = $("#parts option:selected").val();
	
	if (typeof part === "undefined") {
		part = "";
		}
	
	var extra2 = '';
	if( $('#type').val() == 'D' ) {
		var extra = $('input[name="x_L"]').val()+"_"+$('input[name="y_L"]').val()+"_"+$('input[name="zoom_L"]').val();
		extra2 = $('input[name="x_R"]').val()+"_"+$('input[name="y_R"]').val()+"_"+$('input[name="zoom_R"]').val();
		}
	else {
		if( $('input[name="x"]').val() != undefined ) {
			var extra = $('input[name="x"]').val()+"_"+$('input[name="y"]').val()+"_"+$('input[name="zoom"]').val();
			}
		else {
			var extra = "0_0_0";
			}
		}
	var orient = $('#loc').val();
	var caption = {
		"state": ( $('input[name="caption"]:checked').length > 0 ? "on" : "off" ),
		"c_pos": $('select[name="caption_poz"]').val(),
		"t_pos": $('select[name="text_poz"]').val(),
		"c_color": $('select[name="ccolor"]').val(),
		};
		
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=push_ad&ad_id='+id+'&part='+part+'&pages='+pages+'&orient='+orient+'&extra='+extra+'&extra2='+extra2+'&caption='+caption["state"]+'&cpos='+caption["c_pos"]+'&tpos='+caption["t_pos"]+'&ccolor='+caption["c_color"],
		dataType: 'json',
		success:function( data ) {
			if( data == 'ok' ) {
				ad_menu_default();
				}
			}
		});
	}

function alpha(e,allow) {
	var k;
	k=document.all?parseInt(e.keyCode): parseInt(e.which);
	
	
	if (k != 32) {
		return (allow.indexOf(String.fromCharCode(k))!=-1);
		}
	else {
		return false;
		}
	}
	
function page_checker() {
	var type = $('#type' ).val();
	var value = $('#pages' ).val().toUpperCase();
	var new_value = '';
	var b = 0;
	var c = 0;
	var real_pages = '';
	
	if( type == 'D' ) {
		b = value.indexOf("B")+1;
		c = value.indexOf("C")+1;
		
		if( b > 0 && c > 0 ) {
			new_value = '';
			}
		else if( b > 0 ) {
			var page = parseInt( value.substring( b ) );
			if( isNaN( page ) ) { page = 2 }
			
			if( page <= 1 ){
				page = 2;
				}
			else if( page >= 3 ) {
				alert( '<?= $lang["ads"]["overload"] ?>' );
				page = max_page-2;
				}
						
			real_pages = page+'-'+(page+1);
			new_value = page+'-'+(page+1);			
			}
		else if( c > 0 ) {
			var page = parseInt( value.substring( c ) );
			if( isNaN( page ) ) { page = 2 }
						
			if( page <= 1 ){
				page = 2;
				}
			else if( page >= 3 ) {
				alert( '<?= $lang["ads"]["overload"] ?>' );
				page = max_page-2;
				}
						
			real_pages = page+'-'+(page+1);
			new_value = page+'-'+(page+1);			
			}
		else {
			var page = parseInt( value );
			if( page%2 == 1 ) { page = page-1; }
			if( isNaN( page ) ) { page = 2 }
			
			if( page > 0 && page < max_page ) {

				}
			else if( page >= max_page ){
				alert( '<?= $lang["ads"]["overload"] ?>' );
				page = max_page-2;
				}
			else if( page < 2 ){
				page = 2;
				}
			real_pages = page+'-'+(page+1);
			new_value = page+'-'+(page+1);
			}
	
		}
	else {
		b = value.indexOf("B")+1;
		c = value.indexOf("C")+1;
		
		if( b > 0 && c > 0 ) {
			new_value = '';
			}
		else if( b > 0 ) {
			var page = parseInt( value.substring( b ) );
			if( isNaN( page ) ) { page = 1 }
			
			if( page <= 1 ) {
				page = 2;
				}
			else if( page == 3 ) {
				page = max_page-1;
				}
			else if( page >= 4 ) {
				if( type == 'F' ) { page = max_page+1; }
				else { page = max_page;	}
				}
			real_pages = page;
			new_value = page;
			}
		else if( c > 0 ) {
			var page = parseInt( value.substring( c ) );
			if( isNaN( page ) ) { page = 1 }
			
			if( page <= 1 ) {
				page = 2;
				}
			else if( page == 3 ) {
				page = max_page;
				}
			else if( page >= 4 ) {
				if( type == 'F' ) { page = max_page+1; }
				else { 
					alert( '<?= $lang["ads"]["overload"] ?>' );
					page = max_page;
					}
				}
			real_pages = page;
			new_value = page;
			}
		else {
			var page = parseInt( value );
			if( isNaN( page ) ) { page = 2 }
			
			if( page > 0 && page < max_page ) {
				}
			else if( page >= max_page ){
				if( type == 'F' ) { page = max_page; }
				else { 
					alert( '<?= $lang["ads"]["overload"] ?>' );
					page = max_page;
					}
				}
			else if( page < 2 ){
				page = 2;
				}
			real_pages = page;
			new_value = page;
			}
		}
	
	$('#pages' ).val( new_value );
	//$('#real_pages').html( real_pages );
	if( real_pages != '' ) {
		$("#send_ad").removeAttr("disabled");
		}
	else {
		  $("#send_ad").attr("disabled", "disabled");
		}
	};

var enableContext = 1;
var winWidth = $(window).width()-20;
var found = "";

var contextWidth = $(".custom-menu").width();
$(document).bind("contextmenu", function (event) {
    event.preventDefault();
    found = $(event.target).closest( ".adsTiles" );
   	
   	var check = found.children( "div" )[0];
	
	enableContext = 1;
	if( $(check).hasClass('adChecking2') ) {
		enableContext = 0;
		
		if( "6" == "<?= $user[0][4] ?>") {
			enableContext = 1;
			}
		}
	
	
   	if( enableContext ) {
		if( found.length != 0 ) {
			var checker = winWidth-contextWidth-5;

			if( event.pageX >= checker ) {
				event.pageX = event.pageX-contextWidth;
				}
			event.pageY = event.pageY-parseFloat( $("#header").height() );
			event.pageX = event.pageX+10;
			if( found.attr("force") == "1" ) {
				$("#force").show( 0 );
				}
			else {
				$("#force").hide( 0 );
				}

			// .stop(true, true) clears any fade still queued/running from a
			// previous open/close before starting this one - without it,
			// repeated or rapid-fire contextmenu/mousedown events (e.g. a
			// Ctrl+click, which fires both a click and a contextmenu) queue
			// up multiple fades that fire one after another, so the menu
			// visibly flickers or closes itself moments after opening.
			$(".custom-menu").stop( true, true ).css({
				top: event.pageY + "px",
				left: event.pageX + "px"
				}).fadeIn(100);
			}
   		}
	});

$(document).bind("mousedown", function (e) {
    var container = $(".custom-menu");
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.stop( true, true ).fadeOut(100);
    	}
	});

function myContextMenu( operation ) {
	switch( operation ) {
		case 'force':
			if( confirm( "<?= $lang['ads']['force_question'] ?>" ) ) {
				$.ajax	({
					url:"engine/adAjax.php?op=force&id="+$( found ).attr("adid"),
					type: "POST",
					dataType: 'json',
					success:function( data ) {}
					});
				}
			break;
		case 'proof':
			$.ajax	({
				url:"engine/adAjax.php?op=proof&id="+$( found ).attr("adid"),
				type: "POST",
				dataType: 'json',
				success:function( data ) {}
				});
			break;
		case 'delete':
			if( confirm( "<?= $lang['ads']['confirm_delete'] ?>"+$( found ).attr("adname")+"<?= $lang['ads']['confirm_delete2'] ?>" ) ) {
				remove_ad( "physical", $( found ).attr("adid") );
				}
			break;
		}
	}

$(".custom-menu li").click(function(){
    switch($(this).attr("data-action")) {
        case "first": myContextMenu('delete'); break;
        case "proof": myContextMenu('proof'); break;
        case "force": myContextMenu('force'); break;
    	}
     $(".custom-menu").fadeOut(100);
 	});	
</script>
<?
	}
?>