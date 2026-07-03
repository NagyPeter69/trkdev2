<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
<link href="css/load_bar.css" rel="stylesheet" type="text/css" />
<?php 

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

$buttons = array( "" => 'Eredeti', "pre" => 'Pre', "pstr" => 'Poszter' );
?>
<div class='content_title'>
	<div class='c_title'>
		<b><?= $magazine[0][2].', '.$pub[0][10] ?>&nbsp;&nbsp;&nbsp;&bull;&nbsp;&nbsp;&nbsp;</b>
		<span style="color: #444;"><?= $lang['ads']['deadline'] ?>: <?= $time ?></span>
	</div>
	
	<?
	foreach( $buttons as $key => $value ) {
		if( $key != $_GET['opt'] ) {
			echo "<div onclick='window.location.href=\"?page=flatplan&opt=".$key."\"' class='title_button'>".$value."</div>";
			}	
		}
	?>

	<div style='float: right; margin-top: -2px; padding-right: 23px;'>
		<select onchange="Redirect( $(this).val() )">
		<?
			$pubs = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'" ORDER BY `code` ASC', '*' );
			$jobs2 = anotherPubs( $user );
			for( $i = 0; $i < count( $jobs2 ); $i++ ) {
				$pubs[] = $jobs2[$i];
				}
			usort($pubs, querySort(10) );	
				
			for( $i = 0; $i < count( $pubs ); $i++ ) {
				$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'name' );
				echo "<option value='".$pubs[$i][0]."_".$pubs[$i][10]."' ";
				if( $_GET['code'] == $pubs[$i][10] && $_GET['id'] == $pubs[$i][0] )
					echo "selected";
				echo ">".$pubs[$i][10]." ( ".$magazine[0][0]." )</option>";
				}
		?>
		</select>
	</div>
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

<div id='content_box' style='margin-left: -20px;'>
</div>

<ul class='custom-menu'>
  <li data-action="second">Letöltés: PDF</li>
  <li data-action="first">Letöltés: PDF (egyben)</li>
  <li data-action="third">Letöltés: JPEG</li>
  <? if( $rights["proof"] ) { ?>
  	<hr style="padding: 0; margin: 0;">
  	<li data-action="proof">Proof nyomtatás</li>
  <? } ?>
</ul>

<script>
var txt = '';
var maxPage = '<?= $pub[0][6] ?>';

var maxWidth = $(window).width()-80-160;
var row = parseInt( maxWidth / 229 );
var divWidth = (row*229)+15;
var winWidth = $(window).width();

$( document ).ready(function() {
	fit_box();
	//$('#content_box').width( divWidth );
	});

$(window).resize(function(){
	fit_box();
	winWidth = $(window).width();
	/*maxWidth =$(window).width()-80-160;
	row = parseInt( maxWidth / 229 );
	divWidth = (row*229)+15;
	$('#content_box').width( divWidth );
	console.log( row );*/
	
	ad_height = parseInt( $( window ).height() )-(parseInt( $(".content_title").outerHeight())+parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-295;
	$('.ad_menu_content').height( ad_height );	
	});

function removeRotate( page, current ) {
	$("div [page='"+page+"'][alter='"+current+"']").each(function() {
		$(this).css('-webkit-transform', 'rotateY(90deg)');
		});
	}

function flipPage( page, direction ) {
	var current = parseInt( $("#"+page+"_current").val() );
	if( direction == "+" ) {
		$("#"+page+"_current").val( current+1 );
		}	
	if( direction == "-" ) {
		$("#"+page+"_current").val( current-1 );
		}
		
	current = parseInt( $("#"+page+"_current").val() );
	alterHandle( page );
	$("div [page='"+page+"']").each(function() {
		var alter = parseInt( $(this).attr("alter") );
		if( alter == current ) {
			$(this).css( "z-index", "1200" );
			$(this).flippy({
				duration: "200",
				onFinish: removeRotate( page, current )
			 });
			$(this).css( "opacity", "1" );
			}
		else {
			var zIndex = 1000-alter;
			$(this).css( "opacity", "0" );
			$(this).css( "z-index", zIndex );
			}

		});
	}

function alterHandle( i ) {
	$("div [alter!='0'][page='"+i+"']").each(function() {
		var current = parseInt( $("#"+i+"_current").val() );
		var max = parseInt( $("#"+i+"_max").val() );
		
		if( current > 0 ) {
			$("#"+i+"_left").attr("onclick","flipPage( "+i+", '-' )");
			$("#"+i+"_left").addClass( "flip_active" );
			$("#"+i+"_left").removeClass( "flip_gray" );
			$("#"+i+"_left").css({ cursor: 'pointer' });
			}
		else {
			$("#"+i+"_left").attr("onclick","");
			$("#"+i+"_left").removeClass( "flip_active" );
			$("#"+i+"_left").addClass( "flip_gray" );
			}
		
		if( current < max ) {
			$("#"+i+"_right").attr("onclick","flipPage( "+i+", '+' )");
			$("#"+i+"_right").addClass( "flip_active" );
			$("#"+i+"_right").removeClass( "flip_gray" );
			$("#"+i+"_right").css({ cursor: 'pointer' });				
			}
		else {
			$("#"+i+"_right").attr("onclick","");
			$("#"+i+"_right").removeClass( "flip_active" );
			$("#"+i+"_right").addClass( "flip_gray" );
			}
		});
	}
var maxPage = 0;
var ajaxPage = 1;
var enableAjaxRefresh = true;
function loadPage( page ) {
	$.ajax	({
		url:"engine/parallelProcess/flatplanPage.php",
		data: 'op=loadPage&page='+page+'&id=<?= $_GET["id"] ?>',
		dataType: 'json',
		success:function( data ) {
			//console.log( data );
			if( data != $("#"+page+"_box").html() ) {
				$("#"+page+"_box").html( data );
				}
			}
		});
	ajaxPage++;
	if( ajaxPage > maxPage ) {
		setTimeout(function(){ ajaxPageHold(); }, 20);
		}
	else {
		setTimeout(function(){ loadPage( ajaxPage ); }, 20);
		}
	}

function ajaxPageHold() {
	ajaxPage = 1;
	setTimeout(function(){ loadPage( ajaxPage ); }, 1000);
	}

function loadPageBoxes() {
	if( txt == "" ) {
		$('#content_box').html( "<br>...Flatplan betöltése...<br><img src='images/ajax_loader.gif'>" );
		}
	$.ajax	({
		url:"engine/parallelProcess/flatplan.php",
		data: 'op=loadPagePairBlank&opt=<?= $_GET["opt"] ?>&maxWidth='+maxWidth+'&id=<?= $_GET["id"] ?>',
		dataType: 'json',
		success:function( data ) {
			if( txt != data[0] ) {
				$('#content_box').html( data[0] );
				txt = data[0];
				maxPage = data[1];
				console.log( "maxPage: "+maxPage );
				}
			setTimeout(function(){ loadPage( ajaxPage ); }, 1);
			}
		});
	}
loadPageBoxes();

function fit_box() {
	var ad_height = parseInt( $( window ).height() )-(parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() ) )-110;
	var in_width = parseInt( $( window ).width()) - 60;
	if( in_width < 1220 ) {
		in_width = 1220;
		}

	$('#content_box').height( ad_height );
	}

function Redirect( val ) {
	var temp = val.split("_");
	
	location.href='?page=flatplan&id='+temp[0];
	}
	
var enableContext = 0;

/*var contextWidth = $(".custom-menu").width();
$(document).bind("contextmenu", function (event) {
    event.preventDefault();
    $(".custom-menu").fadeOut(100, function(){
    	if( enableContext ) {
    		var checker = winWidth-contextWidth-5;
    		if( event.pageX >= checker ) {
    			event.pageX = event.pageX-contextWidth;
    			}
    		
			$(".custom-menu").fadeIn(100).css({
				top: event.pageY + "px",
				left: event.pageX + "px"
				});	
	   		}
	   	});
	});*/

$(document).bind("mousedown", function (e) {
    var container = $(".custom-menu");
    if (!container.is(e.target) // if the target of the click isn't the container...
        && container.has(e.target).length === 0) // ... nor a descendant of the container
    {
        container.fadeOut(100);
    }
});

var $idown;
function download(url, type) {
	var link = 'get_file.php?type='+type+'&file='+url;
	
	if ($idown) { $idown.attr('src',link); }
	else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
	}
var alter = "NOR";
<?PHP
	if( $_GET['opt'] != "" )
		echo "alter = '".strtoupper( $_GET['opt'] )."';";
?>

function myContextMenu( type ) {
	if( $( "input:checked" ).length > 0 ) {
		enableContext = 0;
		$('body').css('cursor', 'progress');
		$('#loading_bar').show( 300 );
		$.ajax	({
			url:"engine/download_ajax.php?alter="+alter+"&type="+type+"&id=<?= $_GET["id"] ?>",
			type: "POST",
			data: $("input[type='checkbox']").serialize(),
			dataType: 'json',
			success:function( data ) {
				console.log( data );
				if( type != "proof" ) {
					download(data, type);
					}
				enableContext = 1;
				setTimeout( function() {
					$('body').css({'cursor':'default'});
					}, 870);
				$( "input:checked" ).each(function(){
					$(this).prop('checked', false);
					});
				setTimeout( function() {
					$('#loading_bar').hide( 300 );
					}, 900);
				}
			});
		}
	}

$(".custom-menu li").click(function(){
    switch($(this).attr("data-action")) {
        case "first": myContextMenu('one'); break;
        case "second": myContextMenu('multi'); break;
        case "third": myContextMenu('jpg'); break;
        case "proof": myContextMenu('proof'); break;
    	}
     $(".custom-menu").fadeOut(100);
 	});	
</script>