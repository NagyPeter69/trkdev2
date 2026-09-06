<link rel="stylesheet" href="css/jquery-ui.css">
<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
<link href="css/main.css" rel="stylesheet" type="text/css" />
<link href="css/load_bar.css" rel="stylesheet" type="text/css" />
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

if( $_GET["hash"] != "" ) {
	$check = getValidHotlink( $_GET["hash"] );
	if( $check[0][0] == "" ) {
		header( 'Location: index.php' );
		}
	
	if( $check[0][8] ) {
		
		}
		
	sql_update( "hotlinks", "visited='1'", "id='".$check[0][0]."'" );
	$job = sql_get( 'publications', 'id="'.$check[0][1].'"', '*' );
	}
 	
$_GET["id"] = $job[0][0];
$time = strtotime( $pub[0][11] );
setlocale(LC_ALL,'hungarian');
$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) );	

?>
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
			<td valign="top" style="background: rgb( 227, 227, 227); width: 229px;">
				<div class='top_menu' style="background: rgb(227, 227, 227);">
					<div style="padding: 10px; text-align: left;">
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
					</div>
				</div>			
			</td>
			<td id="fp_content" align="right" valign="top" style="margin-right: 10px; overflow-x: hidden; overflow-y: auto; width: 100%; display: block; white-space: nowrap;">
				<div id='fp_wrapper' style='position:relative;'>
					<div id="fp_holder" style="position: absolute; overflow: auto;"></div>
					<div id="fp_holder2" style="position: absolute; overflow: auto;"></div>
				</div>
			</td>
		</tr>
	</tbody></table>

</div>

<div id='dummyLog' style='display: none;'></div>

<?
$approvers = explode( ",", $job[0][18] );
?>

<ul id='customMenu' class='custom-menu floatMenu'>
	<? if( $_SESSION['standalone_visitor'] != "1" ) { ?>
		<li data-action="second"><?= $lang["flatplan"]["pdf"] ?></li>
		<li data-action="first"><?= $lang["flatplan"]["pdfmerged"] ?></li>
		<li data-action="third"><?= $lang["flatplan"]["jpg"] ?></li>
		<? if( $rights["prooff"] ) { ?>
			<hr style="padding: 0;">
			<li data-action="proof"><?= $lang["flatplan"]["proof"] ?></li>
		<? } ?>

		<? if( $rights["sendHotlink"] ) { ?>
			<hr style="padding: 0;">
			<li data-action="hotlink"><?= $lang["flatplan"]["sendHotlink"] ?></li>
	  	<? } ?>
 
	  	<? if( ( in_array( $_SESSION["intra_user"], $approvers ) or $rights["cancelApprove"] ) && $job[0][4] != "2" ) { ?>
			<hr style="padding: 0;">
		 	<? if( in_array( $_SESSION["standalone_user"], $approvers ) ) { ?>
				<li data-action="accept"><?= $lang["flatplan"]["accept"] ?></li>
			 	<li data-action="reject"><?= $lang["flatplan"]["reject"] ?></li>
		 	<? } ?>
		 	
		 	<? if( $rights["cancelApprove"] ) { ?>
				<li data-action="cancel"><?= $lang["flatplan"]["cancel"] ?></li>
		 	<? } ?>
	  	<? } ?>
	<? } ?>
	
	<? if( $_SESSION['standalone_visitor'] == "1" ) { ?> 
	  	<? if( $check[0][15] == "1" ) { ?> 
	  		<li data-action="second"><?= $lang["flatplan"]["pdf"] ?></li>
	  		<li data-action="first"><?= $lang["flatplan"]["pdfmerged"] ?></li>
	  		<li data-action="third"><?= $lang["flatplan"]["jpg"] ?></li>
	  		<hr style="padding: 0;">
	  	<? } ?>
	  	<? 
	  	if( $check[0][5] == "1" && $job[0][4] != "2" ) { ?>		
		 	<li data-action="light_accept"><?= $lang["flatplan"]["accept"] ?></li>
			<li data-action="light_reject"><?= $lang["flatplan"]["reject"] ?></li>
	  	<? } ?>
	<? } ?>
</ul>

<script>

var pubLength = parseInt( "<?= $pub[0][6] ?>" );

$.fn.preload = function() {
    this.each(function(){
        $('<img/>')[0].src = this;
    });
}

$(['images/icons/arrow_left_hover.png','images/icons/arrow_right_hover.png']).preload();

var time = parseInt( '<?= $user[0][12] ?>' );
var refresh = true;
var deny = new Array();
var allowed = new Array();
<?
	foreach( $logSettings as $name => $value ) {
		if( $value == 1 ) {
			echo "allowed.push('".$name."');";
			}
		}
	
	if( $user[0][4] == 6 ) {
		echo "allowed.push('newArticle');";
		echo "deny.push('backArticle');";
		}
	else {
		echo "allowed.push('backArticle');";
		echo "deny.push('newArticle');";
		}
?>

var txt = '-1';
var maxPage = '<?= $pub[0][6] ?>';

var maxWidth = $(window).width()-80-160;
var maxWidth2 = $(window).width()-80-160;
var row = parseInt( maxWidth / 200 );
var row2 = parseInt( maxWidth2 / 200 );
var divWidth = (row*200);
var winWidth = $(window).width()-20;
var tdWidth = row2*200;

fit_box();
$(window).resize(function(){
	winWidth = $(window).width();
	maxWidth2 = $(window).width()-80-160;
	row2 = parseInt( maxWidth2 / 200 );
	 
	ad_height = parseInt( $( window ).height() )-(parseInt( $(".content_title").outerHeight())+parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-295;
	fit_box();	
	$('.ad_menu_content').height( ad_height );	
	});

var fpFilter = $("select[name='fpView']").val();

var placeto = "fp_holder";
var currentplace = "fp_holder2";
var cachebreak = 0;
var firstLoad = "true";
function loadPages() {
	if( firstLoad == "true" ) {
		$('#fp_holder').html( "<div style='width: 100%; text-align: center;'><br>...<?= $lang['flatplan']['loading'] ?>...<br><img src='images/ajax_loader.gif'></div>" );
		}

	$.ajax	({
		url:"engine/vflatplan_ajax.php",
		data: 'op=loadPagePair&filter='+fpFilter+'&opt=FIN&maxWidth='+maxWidth+'&cachebreak='+cachebreak+'&id=<?= $job[0][0] ?>&hash=<?= $_GET["hash"] ?>',
		dataType: 'json',
		success:function( data ) {
			firstLoad = "false";
			//processChecker = false;
			if( txt != data[0] ) {
				switch( placeto ) {
					case 'fp_holder':	
						$('#fp_holder').html( data[0] );
						$('#fp_holder').show(0);
						$('#fp_holder2').hide(0);
						placeto = 'fp_holder2';
						currentplace = "fp_holder";
						break;

					case 'fp_holder2':	
						$('#fp_holder2').html( data[0] );
						$('#fp_holder2').show(0);
						$('#fp_holder').hide(0);
						placeto = 'fp_holder';
						currentplace = "fp_holder2";
						break;
					}
				
				txt = data[0];
				$('body').off();
				singleDoubleClick( '.pagenr|.thumb' );
				for( var a = 0; a < data[1].length; a++ ) {
					alterHandle( data[1][a] );
					}
				
				$(function(){
					$(".flip_left>img").mouseenter(function(){ $(this).attr("src", "images/icons/arrow_left_hover.png"); });
					$(".flip_left>img").mouseleave(function(){ $(this).attr("src", "images/icons/arrow_left.png"); });
					$(".flip_right>img").mouseenter(function(){ $(this).attr("src", "images/icons/arrow_right_hover.png"); });
					$(".flip_right>img").mouseleave(function(){ $(this).attr("src", "images/icons/arrow_right.png"); });
					});
					
				cachebreak = 0;
				<? if( $_GET['upload'] == "drag" ) { ?>
					init_drag();
				<? } ?>
				$("[title]").each(function(){
			    	$(this).tooltip({ tooltipClass: "floatMenu", content: $(this).attr("title")} );
					});
				}
			setTimeout(function(){ loadPages(); }, 500);		
			}
		});
	}
loadPages();

function fit_box() {
	var ad_height = parseInt( $( window ).height() )-parseInt( $("#header").outerHeight());
	var in_width = parseInt( $( window ).width()) - 60;
	if( in_width < 1220 ) {
		in_width = 1220;
		}

	tdWidth = row2*200;

	$('#content_box').height( ad_height );
	$('#content_box').width( $(window).width() );
	$('#dragupload').height( ad_height-parseInt( $(".top_menu").outerHeight() )-parseInt( $("#logSettings").outerHeight() )-parseInt( $("#flatplanSettings").outerHeight()+2 )-$("#dragclose").height()-40 );
	
	$('#fp_holder, #fp_wrapper, #fp_holder2').width( $('#fp_content').width() );
	var corr = 0;
	if( $('#manageFPbox').length > 0 ) {
		corr = parseInt( $('#manageFPbox').height() );
		}
	$('#fp_holder, #fp_wrapper, #fp_holder2').height( ( ad_height-corr ) );
	}

function Redirect( val ) {
	var temp = val.split("_");
	
	location.href='?page=flatplan<? echo ( $_GET["manage"] != "" ? "&manage=".$_GET["manage"] : "" ); ?>&id='+temp[0];
	}
	
var enableContext = 1;
var contextWidth = $(".custom-menu").width();
$(document).bind("contextmenu", function (event) {
    if( $( "#"+currentplace+" input:checked" ).length > 0 ) {
		event.preventDefault();
		$(".custom-menu").fadeOut(100, function(){
			if( enableContext ) {
				var checker = winWidth-contextWidth-5;
				if( event.pageX >= checker ) {
					event.pageX = event.pageX-contextWidth;
					}

				event.pageY = event.pageY-parseFloat( $("#header").height() );
				event.pageX = event.pageX+10;

				if( $( "#"+currentplace+" input:checked" ).length == 0 ) {
					var target = $(event.target).closest(".pageBox");
					if( target.length > 0 ) {
						target = target.children(".pagenr")[0];
						thumbClick( target, 'single' );
						}
					}		
			
				$(".custom-menu").css({
					top: event.pageY + "px",
					left: event.pageX + "px"
					});	

				$(".custom-menu").show(0);
				var offset = $(".custom-menu").offset();
				var oHeight = event.clientY+$(".custom-menu").outerHeight(true);
				$(".custom-menu").hide(0);

				if( oHeight > $(window).height() ) {
					$(".custom-menu").css("top", (parseInt( $(".custom-menu").css("top") )-$(".custom-menu").outerHeight(true) )+"px");
					} 	
			
				$(".custom-menu").fadeIn(100);			

				}
			});
	   	}
	return false;
	});

$(document).bind("mousedown", function (e) {
    var container = $("#logSettingsPanel");
	if (!container.is(e.target) && container.has(e.target).length === 0) {
		container.hide(250);
		} 
 
    var container = $(".custom-menu");
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.fadeOut(100);
    	} 
    
});

var $idown;
function download(url, type) {
	var link = 'get_file.php?type='+type+'&file='+url;
	
	if ($idown) { $idown.attr('src',link); }
	else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
	
	setTimeout(function(){
		location.reload();
		}, 500);
	}
var alter = "NOR";
<?
	if( $_GET['opt'] != "" )
		echo "alter = '".strtoupper( $_GET['opt'] )."';";
?>

function myContextMenu( type ) {
	if( $( "#"+currentplace+" input:checked" ).length > 0 ) {
		
		if( type == "hotlink" ) {
			settingsPanel( "hotlink_prepare", undefined, "<?= $_GET['id'] ?>" );
			}
		
		else {		
			enableContext = 0;
			$('body').css('cursor', 'progress');
			$('#loading_bar').show( 300 );
			var cbox = new Array();
			var state = new Array();
			$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
				cbox.push( $(this).val() );
				state.push( $(this).attr("state") );
				}); 
						
			$.ajax	({
				url:"engine/download_ajax.php?type="+type+"&id=<?= $_GET['id'] ?>&hash=<?= $_GET['hash'] ?>",
				type: "POST",
				data: {pageselector: cbox },
				dataType: 'json',
				success:function( data ) {
					var download2 = new Array( 'one', 'multi', 'jpg' );
				
					if( jQuery.inArray( type, download2 ) != -1 ) {
						download(data, type);
						}
					enableContext = 1;
					setTimeout( function() {
						$('body').css({'cursor':'default'});
						}, 870);
					$( "input[type='checkbox'][name='pageSelector[]']:checked" ).each(function(){
						$(this).prop('checked', false);
						});
					$( ".selectRight, .selectLeft" ).each(function(){
						$(this).css({ opacity: '0' });
						});
					
					setTimeout( function() {
						$('#loading_bar').hide( 300 );
						}, 900);
					$('body').off();
					txt += " ";
					}
				});
			}
		}
	}

$(".custom-menu li").click(function(){
    switch($(this).attr("data-action")) {
        case "first": myContextMenu('one'); break;
        case "second": myContextMenu('multi'); break;
        case "third": myContextMenu('jpg'); break;
        case "proof": myContextMenu('proof'); break;
        case "accept": myContextMenu('accept'); break;
        case "reject": myContextMenu('reject'); break;
        case "light_accept": myContextMenu('light_accept'); break;
        case "light_reject": myContextMenu('light_reject'); break;
        case "cancel": myContextMenu('cancel'); break;
        case "hotlink": myContextMenu('hotlink'); break;
    	}
     $(".custom-menu").fadeOut(100);
 	});	
 	
 <? if( $_GET['upload'] == "drag" ) { ?>

function init_drag() {
	console.log( "init drag" );
	$('.right_page, .left_page').droppable({
		tolerance: "pointer",
		out: function(e, ui) {
		 	$(this).removeClass('dragHover');
		 	},

		over: function(e, ui) {
		 	$(this).addClass('dragHover');
		 	},
		 	
		drop: function(e, ui) {
		 	$(this).removeClass('dragHover');
			$.ajax	({
				url:"engine/dragupload.php?op=uploadthumb&page="+$(this).attr("page")+"&jobid=<?= $_GET['id'] ?>&file="+ui.draggable.context.id,
				data: '',
				dataType: 'json',
				success:function( data ) {}
				});
		 	},			
		});
	
	$('.dragging').draggable({
		cursor: 'move',
		containment: 'document',
		helper: 'clone'
		});
	}

function loadDragThumbs() {
	console.log( "drag ajax elött" );
	$.ajax	({
		url:"engine/dragupload.php?op=loadthumbs&fileid=<?= $_GET['fileid'] ?>",
		data: '',
		dataType: 'json',
		success:function( data ) {
			console.log( data );
			$("#dragupload").html( data );
			init_drag();
			}
		});
	}
loadDragThumbs();

function closeUpload() {
	$.ajax	({
		url:"engine/dragupload.php?op=closeupload&fileid=<?= $_GET['fileid'] ?>",
		data: '',
		dataType: 'json',
		success:function( data ) {
			window.location.href = "?page=flatplan";
			}
		});	
	}

<? } ?>	
 	
</script>