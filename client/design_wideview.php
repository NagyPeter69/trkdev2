

<link rel="stylesheet" href="css/jquery-ui.css">
<link href="css/design_wideview.css" rel="stylesheet" type="text/css" />
<link href="css/main.css" rel="stylesheet" type="text/css" />
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
<?php 
$_SESSION['intra_ajaxProcess'] = 0;
if( (!isset( $_GET['id'] ) or $_GET['id'] == '' ) && $user[0][11] != "" ) {
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

$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*');
if( isset( $_GET['code'] ) ) {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" AND code="'.$_GET['code'].'"', '*' );
	}
else {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
	$_GET['code'] = $pub[0][10];
	}

$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );

 if( !checkOwner( array( 'publications', 'id', $pub[0][0] ), $user ) ) {
 	header('Location: ' . $_SERVER['HTTP_REFERER']);
 	}
 	
$time = strtotime( $pub[0][11] );
setlocale(LC_ALL,'hungarian');
$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) ); 

$_GET['opt'] = "PLAN";
?>

<?php if( isMobile() ) { ?>
<div id="headerExtraLine">	
	<div style="display: inline-block;">
	<?php
		$temp = sql_aget( 'publications', 'id="'.$_GET['id'].'" AND code="'.$_GET['code'].'"', '*' );
		$mag = sql_aget( "magazines", "id='".$temp[0]["magazine_id"]."'", "*" );
		echo $mag[0]["code"]." ".$temp[0]["code"];
	?>
	</div>
</div>

<? }  else { ?>
<div id="headerExtraLine"></div>
<? } ?>

<div style='display: none;'>
	<div id='downloader' style='width: 125px; height: 16px; position: relative; float: right; margin-top: -2px; padding-right: 23px;'>
		<div id='loading_bar' style='display: none; position: absolute; top: 19px;'>
			<div id="squaresWaveG">
				<div id="squaresWaveG_1" class="squaresWaveG"></div>
				<div id="squaresWaveG_2" class="squaresWaveG"></div>
				<div id="squaresWaveG_3" class="squaresWaveG"></div>
				<div id="squaresWaveG_4" class="squaresWaveG"></div>
				<div id="squaresWaveG_5" class="squaresWaveG"></div>
				<div id="squaresWaveG_6" class="squaresWaveG"></div>
				<div id="squaresWaveG_7" class="squaresWaveG"></div>
				<div id="squaresWaveG_8" class="squaresWaveG"></div>
			</div>
		</div>
	</div>
</div>


<div id='content_box' style='overflow: hidden !important;'>
	<table id="tabla" cellspacing="0" cellpadding="0" style="width: 100%; table-layout:fixed;"><tbody>
		<tr>
			<td id="fp_content" align="left" valign="top" style="margin-right: 10px; overflow-x: hidden; overflow-y: auto; width: 100%; display: block;">
				<div id='fp_wrapper' style='position:relative; overflow: auto; overflow-x: hidden;'>
					<div id="preview" style="float: left; display: inline-block;">
						<div id="fp_holder" style="position: absolute; text-align: left;"></div>
					</div>
				</div>
			</td>
		</tr>
	</tbody></table>
</div>

<script>
var pairNum = parseInt( <?= $pub[0][6] ?> ) / 2 + 1;
var boxWidth = 81;
var boxHeight = 116;

var bwcorr = 6;
var bhcorr = 20;

var tempBoxHeight;
var tempBoxWidth;
var wegesz;
var hegesz;
var surf = {};
var PPair = {};
var px;
var origWidthCount;

function resizePagePairs( we ) {
	var newPairWidth = Math.floor( surf.width / ( we ) );
	var newTempBoxWidth = newPairWidth / 2
	var newBoxWidth = newTempBoxWidth - bwcorr;	
	var arany = newBoxWidth / boxWidth;
	var newBoxOuterHeight = arany * ( boxHeight + bhcorr );
	var newBoxHeight = arany * boxHeight;
	
	return { "width" : newBoxWidth, "outerHeight" : newBoxOuterHeight, "height" : newBoxHeight };
	}

function isInt(value) {
    var er = /^-?[0-9]+$/;
    return er.test(value);
	}

function checkCount( width_count ) {
	var marad = width_count.toString().slice(-2);
	marad = marad.replace(".", "");
	
	if( marad.length == 2 && parseInt( marad ) <= 20 ) {
		return true;
		}
	
	return false;
	}

function decreaseWidth( width, amount ) {		
	var newPairWidth = width - amount;
	var width_count;
	
	width_count = parseFloat( ( surf.width / newPairWidth ).toFixed(2) );
	amount++;
	
	origWidthCount = width_count;
	
	while( !checkCount( width_count ) ) {
		newPairWidth = width - amount;
		width_count = parseFloat( ( surf.width / newPairWidth ).toFixed(2) );
		amount++;
		}
	
	return { "widthCount" : width_count };
	}

function checkPairFullHeight( h ) {
	if( h > surf.height ) {
		return false;
		}
	else {
		return true;
		}
	}

function precalc() {
	tempBoxWidth = boxWidth + bwcorr;
	tempBoxHeight = boxHeight + bhcorr;
	
	surf = {
		"width" : $("#preview").width(),
		"height" : $("#preview").height(),
		};
		
	PPair = {
		"width" : tempBoxWidth*2,
		"height" : tempBoxHeight,
		}
	
	var height_count = ( surf.height / PPair.height ).toFixed(2);
	var width_count = ( surf.width / PPair.width ).toFixed(2);
	
	var hmarad = height_count.slice(-2);
	var wmarad = width_count.slice(-2);
	
	hegesz = parseInt( height_count.substr( 0, height_count.indexOf('.') ) );
	wegesz = parseInt( width_count.substr( 0, width_count.indexOf('.') ) );
	var fheight = Math.ceil( pairNum / wegesz );
	var fheightpx = fheight * tempBoxHeight;
	
	if( fheightpx > surf.height ) {
		var result = resizePagePairs( wegesz );
		fheightpx = fheight * result.outerHeight;	
				
		while( !checkPairFullHeight( fheightpx ) ) {
			fheight = Math.ceil( pairNum / wegesz );
			var result = resizePagePairs( wegesz );
			fheightpx = fheight * result.outerHeight;
			
			wegesz++;
			}
		
		var result = resizePagePairs( wegesz );
		}
	else {
		var result = resizePagePairs( wegesz );
		fheightpx = fheight * result.outerHeight;	
		
		fheight = Math.ceil( pairNum / wegesz );
		fheightpx = fheight * result.outerHeight;
		
		if( fheightpx < surf.height ) {
			wegesz--;
			var result = resizePagePairs( wegesz );
			fheight = Math.ceil( pairNum / wegesz );
			fheightpx = fheight * result.outerHeight;
			
			if( fheightpx < surf.height ) {
				wegesz--;
				var result = resizePagePairs( wegesz );
				fheight = Math.ceil( pairNum / wegesz );
				fheightpx = fheight * result.outerHeight;
				
				if( fheightpx > surf.height ) {
					wegesz++;
					var result = resizePagePairs( wegesz );
					}
					
				}
				
			else if( fheightpx > surf.height ) {
				wegesz++;
				var result = resizePagePairs( wegesz );
				}
			}
		}

	boxWidth = result.width;
	boxHeight = result.height;	
	
	loadPages();
	}
	
function loadArticles() {
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php?op=loadarticles&id=<?= $_GET["id"] ?>",
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#articleList").html( data );
			}
		});
	}
loadArticles();

var txt = '';

var maxWidth = $("#content").width()-80-160-400;
var maxWidth2 = $("#content").width()-80-160-400;
var row = parseInt( maxWidth / 200 );
var row2 = parseInt( maxWidth2 / 200 );
var divWidth = (row*200);
var winWidth = $(window).width()-20;
var tdWidth = row2*200;

$(window).load(function(){
	fit_box();
	precalc();
	});

$(window).resize(function(){
	winWidth = $("#content").width();
	maxWidth2 = $("#content").width()-80-160-400;
	row2 = parseInt( maxWidth2 / 200 );
	 
	ad_height = parseInt( $( "#mainPage" ).height() )-(parseInt( $(".content_title").outerHeight())+parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-295;
	fit_box();	
	$('.ad_menu_content').height( ad_height );	
	});

var fpFilter = $("select[name='fpView']").val();

var currentplace = "fp_holder";
var cachebreak = 0;

function loadPages( forced ) {
	if( txt == "" ) {
		$('#fp_holder').html( "<div style='width: 100%; text-align: center;'><br>...<?= $lang['flatplan']['loading'] ?>...<br><img src='images/ajax_loader.gif'></div>" );
		}
			
	$.ajax	({
		url:"engine/design_wideview_ajax.php",
		data: 'op=loadPagePair&filter='+fpFilter+'&opt=<?= $_GET["opt"] ?>&width='+boxWidth+'&height='+boxHeight+'&maxWidth='+maxWidth2+'&cachebreak='+cachebreak+'&id=<?= $_GET["id"] ?>',
		dataType: 'json',
		success:function( data ) {
			//processChecker = false;
			if( txt != data[0] || forced == true ) {
				$('#fp_holder').html( data[0] );
				$('#fp_holder').show(0);

				txt = data[0];
				$('body').off();
				
				loadArticles();
				articleNames();
				fit_box();
				$(function(){
					$(".flip_left>img").mouseenter(function(){ $(this).attr("src", "images/icons/arrow_left_hover.png"); });
					$(".flip_left>img").mouseleave(function(){ $(this).attr("src", "images/icons/arrow_left.png"); });
					$(".flip_right>img").mouseenter(function(){ $(this).attr("src", "images/icons/arrow_right_hover.png"); });
					$(".flip_right>img").mouseleave(function(){ $(this).attr("src", "images/icons/arrow_right.png"); });
					});
					
				cachebreak = 0;
				}
			setTimeout(function(){ loadPages(); }, 500);
			
			}
		});
	}

function articleNames() {
	$( ".articleNameBox" ).remove();

	$("#"+currentplace+" .articleStart").each(function(){
		var id = $(this).attr( "aid" );
		var name = $(this).attr( "aname" );
		
		var start = $(this);
		var start_page = parseInt( $(this).find(".pagenr").find("div").html() );
		
		var end = $( "#"+currentplace+" .articleEnd[aid='"+id+"']" );
		var end_page = parseInt( $(end).find(".pagenr").find("div").html() );
		
		//console.log( start_page+" , "+end_page );
		var boxDB = Math.ceil( ( end_page - start_page ) / 2 );
		var maradek = ( end_page - start_page ) % 2;
		if( maradek == 0 ) {
			boxDB += 0.5;
			}
		var width = boxDB * 175 - 12;
		
		if( start_page == end_page ) {
			width = 84;
			}
		else {
			if( $(this).hasClass("right_page") ) {
				//width += 81 + 12;
				}
			}
		
		var aname = id+"_name";
		var arrow = id+"_name_arrow";
		
		jQuery('<div/>', {
			id: aname,
			class: "articleNameBox"
			}).appendTo( "#"+currentplace );

		jQuery('<div/>', {
			id: arrow,
			class: "articleNameBox"
			}).appendTo( "#"+currentplace );
		
		var scrollTop = $("#fp_holder").scrollTop();
		
		//console.log( $(start).parent().position() );
		var pos = $(start).parent().position();
		var left = pos.left + 10;
		//console.log( left );
		if( $(this).hasClass("startArrow") && $(this).hasClass("endArrow") ) {
			if( $(this).hasClass("right_page") ) {
				left += 81;
				}
			
			if( $(end).hasClass("left_page") ) {
				left += 0;
				}
			}
			
		else {
			//console.log( $(this) );
			if( $(this).hasClass("right_page") ) {
				//console.log( "right" );
				left += 81;
				}
			
			if( $(end).hasClass("left_page") ) {
				//console.log( "left" );
				//left += -40;
				}
			}
		
		//console.log( left );
		var top = pos.top + scrollTop + 8 + 42;
					
		$("#"+aname).html( "<div class='articleName' style='width: "+width+"px;'>"+name+"</div>" );
		$("#"+aname).css("color", $(this).attr( "acolor" ) );
		$("#"+aname).css("left", left+"px");
		$("#"+aname).css("top", top+"px");
		$("#"+aname).css("width", width+"px");

		var pos = $(start).parent().position();
		var left = pos.left + 11;

		if( $(this).hasClass("startArrow") && $(this).hasClass("endArrow") ) {
			if( $(end).hasClass("left_page") ) {
				left += 1;
				width = 78;
				}
				
			if( $(this).hasClass("right_page") ) {
				left += 83;
				width = 78;				
				}
			}
			
		else {
			if( $(this).hasClass("left_page") && $(this).hasClass("articleEnd") ) {
				width -= 3;
				}
				
			if( $(this).hasClass("right_page") ) {
				var c = $("div[a-name='"+$(this).attr( "a-name" )+"']").length;
				if( c <= 2 ) {
					left += 82;
					if( $(this).hasClass("articleEnd") ) {
						width -= 3;
						}
					else {
						width += 2;
						}
					
					}
				else {
					left += 82;
					width += 2;
					}
				}
			
			if( $(end).hasClass("left_page") ) {
				//left += -40;
				}
			}
		
		var top = pos.top + scrollTop + 5 + 95;
		$("#"+arrow).html( "<div class='articleArrow' style='width: "+width+"px;'></div>" );
		$("#"+arrow).css("left", left+"px");
		$("#"+arrow).css("top", top+"px");
		$("#"+arrow).css("width", width+"px");
		
		
		//console.log( "" );
		});

	$("#"+currentplace+" .startArrow").each(function(){
		var pos = $(this).parent().position();
		var id = $(this).attr( "aid" );
		
		if( $(this).hasClass( "left_page" ) ){
			var aname = id+"_leftArrow_start";
			var left = pos.left + 11;
			var top = pos.top + 28;
			
			jQuery('<div/>', {
				id: aname,
				class: "leftArrowBorder"
				}).appendTo( "#"+currentplace );
			}
			
		if( $(this).hasClass( "right_page" ) ) {
			var aname = id+"_rightArrow_start";
			var left = pos.left + 11 + 81;
			var top = pos.top + 28;
			
			jQuery('<div/>', {
				id: aname,
				class: "rightArrowBorder"
				}).appendTo( "#"+currentplace );
			}	
			
		$("#"+aname).css("left", left+"px");
		$("#"+aname).css("top", top+"px");				
		});
		
	$("#"+currentplace+" .endArrow").each(function(){
		var pos = $(this).parent().position();
		var id = $(this).attr( "aid" );

		if( $(this).hasClass( "left_page" ) ){
			var aname = id+"_leftArrow_end";
			var left = pos.left + 11 + 71;
			var top = pos.top + 28;
			
			jQuery('<div/>', {
				id: aname,
				class: "leftArrowBorder"
				}).appendTo( "#"+currentplace );
				
			}

		if( $(this).hasClass( "right_page" ) ) {
			var aname = id+"_rightArrow_end";
			var left = pos.left + 11 + 83 + 71;
			var top = pos.top + 28;
			
			jQuery('<div/>', {
				id: aname,
				class: "rightArrowBorder"
				}).appendTo( "#"+currentplace );
			}	

		$("#"+aname).css("left", left+"px");
		$("#"+aname).css("top", top+"px");
		
		jQuery('<div/>', {
			id: id+"_user-icon",
			class: "fp-user-icon"
			}).appendTo( "#"+currentplace );
		
			
		$("#"+id+"_user-icon").attr("onclick","settingsPanel('flatplan_worker', undefined, '"+$(this).attr("sqlid")+"' )" );
		$("#"+id+"_user-icon").attr("data-id", $(this).attr("sqlid") );
		$("#"+id+"_user-icon").html( "<i class='fp-icons fas fa-user' style='"+$(this).attr("user-color")+"'></i>" );

		top += 3;
		left -= 18;
		$("#"+id+"_user-icon").css("left", left+"px");
		$("#"+id+"_user-icon").css("top", top+"px");

		jQuery('<div/>', {
			id: id+"_dot-box",
			class: "dot-box"
			}).appendTo( "#"+currentplace );
		
		left -= 30;
		top += 80;
		$("#"+id+"_dot-box").css("left", left+"px");
		$("#"+id+"_dot-box").css("top", top+"px");
		
		if( $(this).attr("have-text") != undefined ) {
			jQuery('<div/>', {
				id: id+"_dot-text",
				class: "dot_text"
				}).appendTo( "#"+id+"_dot-box" );
			
			if( $(this).attr("have-text") == "false" ) {
				$("#"+id+"_dot-text").html( "<div class='dot_required'></div>" );
				}
			}

		if( $(this).attr("have-image") != undefined ) {
			jQuery('<div/>', {
				id: id+"_dot-image",
				class: "dot_image"
				}).appendTo( "#"+id+"_dot-box" );
			
			if( $(this).attr("have-image") == "false" ) {
				$("#"+id+"_dot-image").html( "<div class='dot_required'></div>" );
				}
			}

		if( $(this).attr("have-other") != undefined ) {
			jQuery('<div/>', {
				id: id+"_dot-other",
				class: "dot_other"
				}).appendTo( "#"+id+"_dot-box" );
			
			if( $(this).attr("have-other") == "false" ) {
				$("#"+id+"_dot-other").html( "<div class='dot_required'></div>" );
				}
			}
		});	
	}

function fit_box() {
	var ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight())-parseInt( $("#headerExtraLine").outerHeight());
	var in_width = parseInt( $( "#mainPage" ).width()) - 60;
	if( in_width < 1220 ) {
		in_width = 1220;
		}

	tdWidth = row2*200;
	$('#fp_wrapper').width( $('#fp_content').width() );
	var width = $('#fp_content').width() - $("#pasteboard_box").outerWidth(true) - 19;
	$('#content_box').height( ad_height );
	$('#articleList').height( ad_height-parseInt( $(".top_menu").outerHeight() )-parseInt( $("#logSettings").outerHeight() )-parseInt( $("#flatplanSettings").outerHeight()+2 )-parseInt( $("#panels").outerHeight() )-10 );
	$('#fp_holder, #fp_holder2, #preview').width( width );
	var corr = 0;
	if( $('#manageFPbox').length > 0 ) {
		corr = parseInt( $('#manageFPbox').height() );
		}
	$('#fp_wrapper, #fp_holder2, #preview').height( ( ad_height-corr ) );
	$("#pasteboard_box").height( $("#fp_holder").height() );
	}

function Redirect( val ) {
	var temp = val.split("_");
	
	location.href='?page=design_wideview&id='+temp[0];
	}

$(window).keydown(function(evt) {
	//console.log( evt.which );	
	if( evt.which == "46" ) {
		removeTiles();
		}
	});

</script>