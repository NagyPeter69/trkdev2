

<link rel="stylesheet" href="css/jquery-ui.css">
<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
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

<div id="planner-line">
	<div style='float: left; margin-top: 12px; margin-left: 10px;'>
		<select style="margin-left: -1px;" onchange="Redirect( $(this).val() )">
		<?
			$pubs = getShortPubs( $user[0] );

			for( $i = 0; $i < count( $pubs ); $i++ ) {
				$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code' );
				
				echo "<option value='".$pubs[$i][0]."_".$firstPage[0][0]."_".$firstPage[0][1]."' ";
					if( $_GET['code'] == $pubs[$i][10] && $_GET['id'] == $pubs[$i][0] )
						echo "selected";
				echo ">".$magazine[0][0]." ".$pubs[$i][10]."</option>";
				}
		?>
		</select>
	</div>
	
	<div onclick="planner_add_line()" class="panelButton planner-add" style="display: none; margin-left: 10px; margin-top: 11px; color: #FFF; font-size: 19px; background-color: rgb( 99, 106, 166); border: 1px solid rgb(137, 138, 173); width: 21px; cursor: pointer;">+</div>
	
	<div id='planner_line_content' style='float: left;'>
	</div>
</div>

<div id='content_box' style='overflow: hidden !important;'>
	<table id="tabla" cellspacing="0" cellpadding="0" style="width: 100%; table-layout:fixed;"><tbody>
		<tr>
			<td class="fp_left" valign="top" style="background: rgb( 227, 227, 227); width: 229px;">

				
				<div id="logSettings" style="padding-left:10px; padding-right: 10px; box-sizing: border-box;">
					<div id="articleList"></div>
					<div id="panels">
						<button onclick='settingsPanel("flatplan_articletypes", undefined, <?= $_GET["id"] ?> );'>Article Types</button>
						
						<table id="tabla" cellspacing="0" cellpadding="0" style="width: 100%; table-layout:fixed;padding-top: 20px;"><tbody>
							<tr>
								<td  valign="top">
									<div class="draggableStuff full" stype="ad">
										<div class=" board-thumb thumb" style="z-index: 10; width: 81px; height: 99px; cursor: pointer; background-repeat:no-repeat; background-color: #f1ac5d; position: relative;"><div style="position: absolute; width: 100%; font-size: 24px; top: 31px; font-weight: bold; text-align: center;"  pointer-events: none;>AD</div></div>
									</div>
								</td>
								
								<td valign="top">
									<div class="draggableStuff half" stype="ad">
										<div class="board-thumb thumb" style="z-index: 10; width: 40px; height: 99px; cursor: pointer; background-repeat:no-repeat; background-color: #f1ac5d; position: relative"><div style="position: absolute; pointer-events: none; width: 100%; font-size: 24px; top: 31px; font-weight: bold; text-align: center;">AD</div>
									</div>
								</td>
							</tr>
							
							<tr>
								<td valign="top" style="padding-top: 10px;">
									<div class="draggableStuff full" stype="promo">
										<div class="ui-draggable ui-draggable-handle board-thumb thumb" style="z-index: 10; width: 81px; height: 99px; cursor: pointer; background-repeat:no-repeat; background-color: #eaa9df; position: relative"><div style="position: absolute; width: 100%; font-size: 24px; top: 31px; font-weight: bold; text-align: center;  pointer-events: none;">PR</div></div>
									</div>
								</td>
								
								<td valign="top" style="padding-top: 10px;">
									<div class="draggableStuff half" stype="promo">
										<div class="ui-draggable ui-draggable-handle board-thumb thumb" style="z-index: 10; width: 40px; height: 99px; cursor: pointer; background-repeat:no-repeat; background-color: #eaa9df; position: relative"><div style="position: absolute; width: 100%; font-size: 24px; top: 31px; font-weight: bold; text-align: center;  pointer-events: none;">PR</div>
									</div>
								</td>
							</tr>							
						</tbody></table>
					</div>
				</div>
			</td>
			<td id="fp_content" align="left" valign="top" style="margin-right: 10px; overflow-x: hidden; overflow-y: auto; width: 100%; display: block;">
				<div id='fp_wrapper' style='position:relative; overflow: auto;'>
					<div id="preview" style="float: left; display: inline-block;">
						<div id="fp_holder" style="position: absolute; text-align: left;"></div>
					</div>
					<div id="pasteboard_box" style=""></div>
				</div>
			</td>
		</tr>
	</tbody></table>
</div>

<div id="detailPanels"></div>
<div id='dummyLog' style='display: none;'></div>

<?
$check = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND code='".$magazine[0][3]."' AND issue='".$pub[0][10]."' AND fin='1'", "id" );
?>

<ul id='customMenu' class='custom-menu floatMenu'>
	<div class="haveCikk" class="planner-advanced" style="clear:both; height: auto !important;">
		<div id="same" style="display: inline-block;">
			<div id="notmixed" style="display: inline-block;">
				<input type="hidden" id="a_id">
				<li id="plan-statuschange" data-action="plan-statuschange">
					Status:
					<select class="menuInput" name="c_status" id="c_status" onchange="setAStatus()">
						<?php
						$status = array( "debug"=>"debug", "defined"=>"Defined", "progress"=>"In progress", "waiting"=>"Waiting", "finished"=>"Finished", "error"=>"Error" );
							
						foreach( $status as $key=>$value ) {
							echo '<option '.( $key == $articles[0]["status"] ? "selected" : "" ).' value="'.$key.'">'.$value.'</option>';
							}
						?>
					</select>
				</li>

				<li id="plan-statuschange" data-action="plan-statuschange">
					Time Spent:
					<select class="menuInput" name="c_time" id="c_time" onchange="setATime()">
						<?php
						$status = array( "0"=>"----", "15"=>"+15", "30"=>"+30", "45"=>"+45", "60"=>"+60", "75"=>"+75", "90"=>"+90", "105"=>"+105", "120"=>"+120" );
							
						foreach( $status as $key=>$value ) {
							echo '<option '.( $key == $articles[0]["status"] ? "selected" : "" ).' value="'.$key.'">'.$value.'</option>';
							}
						?>
					</select>
				</li>
				<hr style="padding: 0;">
			</div>
			
			<li id="plan-modify" data-action="plan-modify">Modify</li>
			<li id="plan-remove" data-action="plan-remove">Remove</li>
			<hr class="noCikk" style="padding: 0;">
		</div>
		
		<div id="notsame" style="display: inline-block;">	
			<li id="NoButton" class="nobutton">Select one Entity</data>
		</div>
	</div>

</ul>

<script>
	
(function ($, document, undefined) {
  $.fn.extend({
    /**
     * @param {number} x The x-coordinate of the Point.
     * @param {number} y The y-coordinate of the Point.
     * @param {Element} until (optional) The element at which traversing should stop. Default is document.body
     * @return {jQuery} A set of all elements visible at the given point.
     */
    elementsFromPoint: function(x, y, until) {
      until = this[0];

      var parents = [];
      var current;
      
      var i = 1;
      do {
        current = document.elementFromPoint(x, y);
        //console.log( current );
        if (current !== until) {
          parents.push(current);
          
          if( $(current).hasClass("plannerDropLineBox") ) {
	        return $(current);
	        current = false;
	        break;
          	}
          else {
	      	if( $(current).attr("id") == "fp_holder" ) {
		      	current = false;
		      	break;
	      		}

	      	if( $(current).attr("id") == "pasteboard_box" ) {
		      	return $(current);
		      	current = false;
		      	break;
	      		}
	      		
	      	current.style.pointerEvents = 'none';	
          	}
          
        } else {
          current = false;
        }
        i++;
      } while (current);

      parents.forEach(function (parent) {
          return parent.style.pointerEvents = 'all';
      });
      return $(parents);
    }
  });
})(jQuery, document);

(function ($, document, undefined) {
  $.fn.extend({
    /**
     * @param {number} x The x-coordinate of the Point.
     * @param {number} y The y-coordinate of the Point.
     * @param {Element} until (optional) The element at which traversing should stop. Default is document.body
     * @return {jQuery} A set of all elements visible at the given point.
     */
    elementsFromPoint_: function(x, y, until) {
      until = this[0];

      var parents = [];
      var current;
      
      var i = 1;
      do {
        current = document.elementFromPoint(x, y);
        //console.log( current );
        if (current !== until) {
          parents.push(current);
          
          if( $(current).hasClass("plannerDropLineBox") ) {
	        return $(current);
	        current = false;
	        break;
          	}
          else if( $(current).hasClass("thumbdraggbox") ) {
	        return $(current);
	        current = false;
	        break;
          	} 
          else {
	      	if( $(current).hasClass("fp_left") ) {
		      	current = false;
		      	break;
	      		}
	      		
	      	if( $(current).attr("id") == "fp_holder" ) {
		      	current = false;
		      	break;
	      		}

	      	if( $(current).attr("id") == "pasteboard_box" ) {
		      	return $(current);
		      	current = false;
		      	break;
	      		}
	      		
	      	current.style.pointerEvents = 'none';	
          	}
          
        } else {
          current = false;
        }
        i++;
      } while (current);

      parents.forEach(function (parent) {
          return parent.style.pointerEvents = 'all';
      });
      return $(parents);
	}
  });
})(jQuery, document);

function savePlanner( pid ) {
	var cbox = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		if( $(this).attr("pageid") != "" ) {
			cbox.push( $(this).attr("pageid") );
			}
		else {
			cbox.push( $(this).attr("value") );
			}
		}); 
		
	var data = {
		"type" : "article",
		"name" : $("#name").val(),
		"article" : $("#atype option:selected").val(),
		"worker" : $("#workerID option:selected").val(),
		"text" : $('#r_text').is(':checked') ? $('#r_text').val() : '0',
		"h_text" : $('#have_text').is(':checked') ? $('#have_text').val() : '0',
		"image" : $('#r_image').is(':checked') ? $('#r_image').val() : '0',
		"h_image" : $('#have_image').is(':checked') ? $('#have_image').val() : '0',
		"other" : $('#r_other').is(':checked') ? $('#r_other').val() : '0',
		"h_other" : $('#have_other').is(':checked') ? $('#have_other').val() : '0',
		"tspent" : $("#tspent").val(),
		"remark" : $("#remark").val(),
		};
		
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php?op=savearticle&id=<?= $_GET["id"] ?>&pid="+pid,
		type: "POST",
		data: { pageselector: cbox, data : data },
		dataType: 'json',
		success:function( data ) {
			loadPasteboard();
			}
		});
	}

function updateWorkTime( aname ) {
	var cbox = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).attr("pageid") );
		}); 

	if( aname == undefined ) {
		aname = "";
		}
			
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php?op=worktime&aid="+$("#atype").val()+"&id=<?= $_GET["id"] ?>&aname="+aname,
		type: "POST",
		data: { pageselector: cbox },
		dataType: 'json',
		success:function( data ) {
			$(".work_time").html( data );
			}
		});		
	}

function planner_add_line( pid ) {
	console.log( pid );
	var cbox = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		if( $(this).attr("pageid") != "" ) {
			cbox.push( $(this).attr("pageid") );
			}
		else {
			cbox.push( $(this).attr("value") );
			}
		}); 
	
	if( pid == undefined ) {
		pid = "";
		}
	
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php?op=loadaddbar&id=<?= $_GET["id"] ?>&pid="+pid,
		type: "POST",
		data: { pageselector: cbox },
		dataType: 'json',
		success:function( data ) {
			$("#planner_line_content").html( data[0] );
			$("#planner_line_content").show(0);
			updateWorkTime( data[1] );
			
			$("#remark").click( function() {
				var p = $("#remark").position();
				$("#fake_remark").val( $("#remark").val() );
				$("#fake_remark_box").show(0);
				$("#fake_remark_box").css("left", p.left+"px" );
				$("#fake_remark_box").css("top", (p.top+50)+"px" );
				$("#fake_remark").focus();
				
				$(window).off();	
				$(window).click(function(e) {
					var target = e.target || e.srcElement;
					var id = target.id;
					
					if( id != "fake_remark" && id != "remark" ) {
						$("#remark").val( $("#fake_remark").val() );
						$("#fake_remark_box").hide(0);
						$("#fake_remark").val("");
						}
					});
				});
			}
		});	
	}

var lastCikk = "";
function plannerAddCheck() {
	var emptyArticle = true;
	var articleName = "";
	var sameArticle = true;
	var diffArticles = 0;
	var pid;
	
	var length = $( "#"+currentplace+" input:checked, #pasteboard_box input[type='checkbox'][name='pageSelector[]']:checked" ).length;
	$( "#"+currentplace+" input:checked, #pasteboard_box input[type='checkbox'][name='pageSelector[]']:checked" ).each(function(i){
		var val = $(this).val();
		var a = $( "#"+val+"_thumb" ).parent().parent().attr("a-name");	
		
		
		
		if( $( "#"+val+"_thumb" ).parent().parent().attr("sqlid") == "" ) {
			diffArticles++;
			}
		
		//else if( i == 0 || pid != $( "#"+val+"_thumb" ).parent().parent().attr("sqlid") ) {
		else if( i == 0 || articleName != a ) {	
			diffArticles++;
			}
		
		//console.log( i, articleName, a );
		articleName = a;
		pid = $( "#"+val+"_thumb" ).parent().parent().attr("sqlid");
		
		if( pid != "" ) {
			emptyArticle = false;
			}
		article = a;
		});
	
	console.log( diffArticles, emptyArticle, length );
	if( diffArticles == 1 && !emptyArticle && length > 0 ) {
		if( lastCikk != articleName ) {
			planner_add_line( pid );
			lastCikk = articleName;
			}
		}
	else {
		if( emptyArticle && length > 0 ) {
			$(".planner-add").hide(0);
			$(".planner-add").show(0);
			}
		else {
			$(".planner-add, #planner_line_content").hide(0);
			}
			
		updateWorkTime();
		}
		
	if( length == 0 || diffArticles > 1 ) {
		lastCikk = "";
		}
	}
	
var targets;	
var mouseX;
var mouseY;
$(document).mousemove( function(e) {
   mouseX = e.pageX; 
   mouseY = e.pageY;
}); 
var currentDropZone;

var template;
function saveHalfAd() {
	if( $("#ad_pos").val() != "" ) {
		var data = $("#halfadform").serialize();
		data += "&template="+template;
		
		$.ajax	({
			url:"engine/flatplan_planner_ajax.php?op=savehalfarticle",
			type: "POST",
			data: { data : data },
			dataType: 'json',
			success:function( data ) {
				console.log( data );
				}
			});		
		}
	
	else {
		alert("<Please select position>");
		}
	}

function selectAdPos(e) {
	$(".selectedAd").removeClass("selectedAd");
	var pos = $(e).attr("pos");
	$("#ad_pos").val( pos );
	template = $(e).attr("template");
	$(e).addClass("selectedAd");
	}

function switchHalfadPos() {
	var size = $("#size").val().replace( "/", "-" );
	var orient = $("#orient").val().toLowerCase();
	
	$(".pos_icons").hide();
	$("#"+size+"_"+orient).show();
	
	$(".selectedAd").removeClass("selectedAd");
	$("#ad_pos").val("");
	}

function init_fix_drag() {	
	var o_height = 0;
	var o_width = 0;
	var a = 0;
	function handleDragStop( event, ui ) {
		$("#pasteboard_box").removeClass( "dropzone" );
		$(".plannerDropLineBox").hide(0);
		$(".thumbdragg").hide(0);
		$(".board-thumb, .board-thumb-box").css("pointer-events", "all" );
		
		if( currentDropZone != "" ) {			
			if( $(ui.helper).hasClass( "half" ) ) {
				var before = $( currentDropZone ).attr("page");
				
				$.ajax	({
					url:"engine/flatplan_planner_ajax.php?op=loadhalfadbar&stype="+$(this).attr("stype")+"&before="+before+"&id=<?= $_GET["id"] ?>",
					type: "GET",
					dataType: 'json',
					success:function( data ) {
						$("#planner_line_content").html( data );
						$("#planner_line_content").show(0);						
						}
					});
				}			
			
			if( $(ui.helper).hasClass( "full" ) ) {
				if( $( currentDropZone ).hasClass("thumbdragg") ) {
					var before = $( currentDropZone ).attr("page");
					var mod = "update";
					}
				else {
					var before = $( currentDropZone ).attr("before");
					var mod = "new";
					}
				
				$.ajax	({
					url:"engine/flatplan_planner_ajax.php?op=dragsave&type=leftpanel&stype="+$(this).attr("stype")+"&before="+before+"&mod="+mod+"&id=<?= $_GET["id"] ?>",
					type: "GET",
					dataType: 'json',
					success:function( data ) {
						}
					});
				}
			}
		}
	
	$('.draggableStuff').each( function(){
		$(this).draggable({
			cursor: 'move',
			containment: 'document',
			helper: 'clone',	
				
			stop: handleDragStop,
			drag: function(e, ui) {
				targets = $('body').elementsFromPoint_(e.pageX, e.pageY);
				$( currentDropZone ).hide(0);
			
				if( $(ui.helper).hasClass( "full" ) ) {			
					if( $(targets).hasClass("thumbdraggbox") ) {
						currentDropZone = $(targets).find('.thumbdragg');
						$(targets).find('.thumbdragg').show(0);
						}
					else if( $(targets).hasClass("plannerDropLineBox") ) {
						currentDropZone = $(targets).find('.plannerDropLine');
						$(targets).find('.plannerDropLine').show(0);
						}
						
					else {
						$("#pasteboard_box").removeClass( "dropzone" );
						currentDropZone = "";
						}
					}
					
				if( $(ui.helper).hasClass( "half" ) ) {			
					if( $(targets).hasClass("thumbdraggbox") ) {
						currentDropZone = $(targets).find('.thumbdragg');
						$(targets).find('.thumbdragg').show(0);
						}
												
					else {
						$("#pasteboard_box").removeClass( "dropzone" );
						currentDropZone = "";
						}
					}						
				},
				
			start: function(e, ui) {
				$(".plannerDropLineBox").show(0);
				$(".plannerDropLine").hide(0);
				}
			});
		});	
	}	
init_fix_drag();

function init_drag() {	
	var o_height = 0;
	var o_width = 0;
	var a = 0;
	function handleDragStop( event, ui ) {
		$("#pasteboard_box").removeClass( "dropzone" );
		$(".plannerDropLineBox").hide(0);
		$(".board-thumb, .board-thumb-box").css("pointer-events", "all" );
		
		if( currentDropZone != "" ) {
			var cbox = new Array();
			$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked, #pasteboard_box input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
				cbox.push( $(this).attr("pageid") );
				}); 			
			
			if( currentDropZone == "pasteboard" ) {
				//console.log( $("#fp_wrapper").scrollTop() );
				var corr_top = $("#header").outerHeight( true ) + $("#planner-line").outerHeight( true );
				var corr_left = $(".fp_left").outerWidth( true );
				var board = $("#pasteboard_box").position();
				var clone = $(ui.helper).position();
				
				var y = clone["top"] + $("#fp_wrapper").scrollTop() - corr_top;
				if( y < 0 ) { y = 0; }

				var x = clone["left"] - board["left"] - corr_left;
				if( x < 0 ) { x = 0; }
				
				$.ajax	({
					url:"engine/flatplan_planner_ajax.php?op=dragsave&type=pasteboard&x="+x+"&y="+y+"&id=<?= $_GET["id"] ?>",
					type: "POST",
					data: { pageselector: cbox },
					dataType: 'json',
					success:function( data ) {
						loadPasteboard();
						}
					});
				}
			else {
				var before = $( currentDropZone ).attr("before");			
				$.ajax	({
					url:"engine/flatplan_planner_ajax.php?op=dragsave&type=fp&before="+before+"&id=<?= $_GET["id"] ?>",
					type: "POST",
					data: { pageselector: cbox },
					dataType: 'json',
					success:function( data ) {
						loadPasteboard();
						}
					});
				}
			}
		}
	
	$('.left_page, .right_page').each( function(){
		$(this).draggable({
			appendTo: '#mainPage',
			cursor: 'move',
			containment: 'document',
			/*helper: function( event ) {
				return $( "<div id='drag-helper'></div>" );
				},*/
			helper: 'clone',	
				
			stop: handleDragStop,
			drag: function(e, ui) {
				targets = $('body').elementsFromPoint(e.pageX, e.pageY);
				$( currentDropZone ).hide(0);
				if( $(targets).hasClass("plannerDropLineBox") ) {
					currentDropZone = $(targets).find('.plannerDropLine');
					$(targets).find('.plannerDropLine').show(0);
					}
				
				else if( $(targets).attr("id") == "pasteboard_box" ) {
					currentDropZone = "pasteboard";
					$(targets).addClass( "dropzone" );
					}
				else {
					$("#pasteboard_box").removeClass( "dropzone" );
					currentDropZone = "";
					}
							
				},
				
			start: function(e, ui) {
				var length = $("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked, #pasteboard_box input[type='checkbox'][name='pageSelector[]']:checked").length;
				var page_clones = {};
				var helper_html = "";
				if( length == 0 ) {
					var target = $(e.target).find(".pageBox");
					
					if( target.length > 0 ) { 
						target = target.children(".thumb")[0];
						$(target).click();
						length = 1;
						}
					}

				width = 0;
				a = 0;
				$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked, #pasteboard_box input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
					var p = $(this).val();			
					if( a > 0 && p % 2 == 0 ) {
						helper_html += "<div style='float:left; margin-left: 5px;'>"+$("."+p+"_box").html()+"</div>";
						width += 5;
						}
					else {
						helper_html += "<div style='float:left;'>"+$("."+p+"_box").html()+"</div>";
						}
					
					width += 81;					
					a++;				
					});
					
				$(".plannerDropLineBox").show(0);
				
				$(ui.helper).css("width",width+"px");
				$(ui.helper).html( helper_html );
				$(ui.helper).css("background-image","none !important");
				$(".plannerDropLine").hide(0);
				}
			});
		});	
	}	
	
function hideList( type ) {
	if( $("#"+type+"_button").hasClass("fa-minus-square") ) {
		$("."+type+"_list").css("display", "none");
		$("#"+type+"_button").removeClass("fa-minus-square");
		$("#"+type+"_button").addClass("fa-plus-square");
		}
	
	else if( $("#"+type+"_button").hasClass("fa-plus-square") ) {
		$("."+type+"_list").css("display", "table-row");
		$("#"+type+"_button").removeClass("fa-plus-square");
		$("#"+type+"_button").addClass("fa-minus-square");
		}
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

function loadPasteboard() {
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php",
		data: 'op=loadPasteboard&filter='+fpFilter+'&opt=<?= $_GET["opt"] ?>&maxWidth='+maxWidth2+'&cachebreak='+cachebreak+'&id=<?= $_GET["id"] ?>',
		dataType: 'json',
		success:function( data ) {
			$("#pasteboard_box").html( data );
			init_drag();
			}
		});	
	}
loadPasteboard();

function loadPages( forced ) {
	if( txt == "" ) {
		$('#fp_holder').html( "<div style='width: 100%; text-align: center;'><br>...<?= $lang['flatplan']['loading'] ?>...<br><img src='images/ajax_loader.gif'></div>" );
		}
			
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php",
		data: 'op=loadPagePair&filter='+fpFilter+'&opt=<?= $_GET["opt"] ?>&maxWidth='+maxWidth2+'&cachebreak='+cachebreak+'&id=<?= $_GET["id"] ?>',
		dataType: 'json',
		success:function( data ) {
			//processChecker = false;
			if( txt != data[0] || forced == true ) {
				$('#fp_holder').html( data[0] );
				$('#fp_holder').show(0);

				txt = data[0];
				$('body').off();
				
				loadArticles();
				PlannerNumberClick( '.pagenr', '<?= $_GET['opt'] ?>' );
				PlannerThumbClick( '.thumb', '<?= $_GET['opt'] ?>' );
				articleNames();
				fit_box();
				$(function(){
					$(".flip_left>img").mouseenter(function(){ $(this).attr("src", "images/icons/arrow_left_hover.png"); });
					$(".flip_left>img").mouseleave(function(){ $(this).attr("src", "images/icons/arrow_left.png"); });
					$(".flip_right>img").mouseenter(function(){ $(this).attr("src", "images/icons/arrow_right_hover.png"); });
					$(".flip_right>img").mouseleave(function(){ $(this).attr("src", "images/icons/arrow_right.png"); });
					});
					
				cachebreak = 0;
				init_drag();
				plannerAddCheck();
				}
			setTimeout(function(){ loadPages(); }, 500);
			
			}
		});
	}
loadPages();

function articleNames() {
	$( ".articleNameBox" ).remove();

	$("#"+currentplace+" .articleStart").each(function(){
		var id = $(this).attr( "aid" );
		var name = $(this).attr( "aname" );
		//console.log( name );
		
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
	var ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight())-parseInt( $("#planner-line").outerHeight())-parseInt( $("#headerExtraLine").outerHeight());
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
	
	location.href='?page=design&id='+temp[0];
	}

function modHalfAd( pid ) {
	var data = {
		"name" : $("#name").val(),
		};
	
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php?op=modhalfhadd&pid="+$(".rect_select").attr("pid"),
		type: "POST",
		data: { data : data },
		dataType: 'json',
		success:function( data ) {
			loadPages( true );
			}				
		});
	}
	
var enableContext = 1;
var contextWidth = $(".custom-menu").width();

document.addEventListener('click', function(e){
	var check = e.target;
	
	if( $(check).attr("type") == "article" ) {
		$(check).parent().parent().click();
		}
		
	else {
		if( $(check).attr( "class" ).indexOf("rect_select") != "-1" ) {
			$("#planner_line_content").hide(0);
			$(".rect_select").removeClassSVG("rect_select");
			}
		else {				
			if( $(check).attr( "class" ).indexOf("mix_field") != "-1" ) {
				$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
					if( $(this).prop('checked') ) {
						$(this).prop('checked', false);
						$( "#"+$(this).val()+"_selector" ).css({ opacity: '0' });
						};
					});
						
				$(".rect_select").removeClassSVG("rect_select");
				$(check).addClassSVG( "rect_select" );
				
				$.ajax	({
					url:"engine/flatplan_planner_ajax.php?op=loadhalfaddbarmod&pid="+$(".rect_select").attr("pid"),
					type: "GET",
					dataType: 'json',
					success:function( data ) {
						$("#planner_line_content").html( data );
						$("#planner_line_content").show(0);
						}				
					});
				}
			}
		}
	});

$(document).bind("contextmenu", function (event) {
    if( $( "#"+currentplace+" input:checked" ).length == 0 ) {
	    $("#"+event.target.id).click();
	    }
	    
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
								
				var prev = 0;
				var allowMove = true;
				var everyArticle = true;
				var article = "";
				var sameArticle = true;
				$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
					var val = $(this).val();
					if( $( "#"+val+"_thumb" ).hasClass("haveArticle") ) {
						
						}
					else {
						everyArticle = false;
						}
						
					if( prev == 0 ) {
						prev = parseInt( $(this).val() );
						}
					else {
						if( $(this).val() == prev + 1 ) {
							prev = parseInt( $(this).val() );			
							}
						else {
							allowMove = false;
							}
						}
						
					var a = $( "#"+val+"_thumb" ).parent().parent().attr("a-name");
					if( article == "" ) {
						article = a;
						}
					else {
						if( a != article ) {
							sameArticle = false;
							}
						}						
					});
				
				var advanced = false;
				var haveArticle = false;
				var mixed = false;
				var status_check = "";
				var sql_id = "";
				$( "#"+currentplace+" input:checked" ).each(function(){
					var val = $(this).val();
					var check = $( "#"+val+"_thumb" ).parent().parent().attr("aname");
					var check2 = $( "#"+val+"_thumb" ).parent().parent().attr("mixed");
					status_check = $( "#"+val+"_thumb" ).parent().parent().attr("a-status");
					sql_id = $( "#"+val+"_thumb" ).parent().parent().attr("sql-id");
					
					if( check2 == "yes" ) {
						mixed = true;
						}
					else {
						mixed = false;
						}
					
					if( $( "#"+val+"_thumb" ).hasClass("haveArticle") ) {
						haveArticle = true;
						}
					
					if( check != "" ) {
						advanced = true;
						return false;
						}
					});
				$("#a_id").val( sql_id );
				$("#c_status").val( status_check );

				if( mixed ) {
					$("#notmixed").hide(0);
					}
				else {
					$("#notmixed").show(0);						
					}
				
				if( haveArticle ) {
					$(".haveCikk").show(0);
					$(".noCikk").hide(0);
					}
				else {
					$(".haveCikk").hide(0);
					$(".noCikk").show(0);						
					}
				
				if( advanced ) {
					$(".planner- advanced").show(0);
					}
				else {
					$(".planner-advanced").hide(0);
					}
				
				if( allowMove && everyArticle && sameArticle ) {
					$("#same").show(0);
					$("#notsame").hide(0);
					}
				else {
					$("#same").hide(0);	
					$("#notsame").show(0);					
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

var pubID = "<?= $pub[0][0] ?>";
function plannerContextMenu( info, menu ) { 
	var cbox = new Array();
	
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).val() );
		}); 
	
	if( window.parent.frames[0].activeFUpload ) {
		pubID = window.parent.frames[0].currentPlannerPubID;
		}
		
	else {
		window.parent.frames[0].currentPlannerPubID = pubID;
		}

	
	$("#planner_modify").remove();
	$.ajax	({
		url:"engine/menuAjax.php?op=loadmenu&menu="+menu+"&data="+info+"&pubid=<?= $pub[0][0] ?>",
		type: "POST",
		data: {pageselector: cbox },
		dataType: 'json',
		success:function( data ) {
			window.parent.frames[0].currentArticle = data[1];
			var pos = {
				"left": $("#logoMenu").css("left"),
				"top": $("#logoMenu").css("top")
				};			

			jQuery('<div/>', {
				id: menu,
				class: "settingsPanel floatMenu_noclose",
				style: "left: "+pos.left+"; top: "+pos.top+";"
			}).appendTo( "body" );
			if( pos.right != undefined ) {
				$("#"+menu).css("right", pos.right );
				}
				
			$("#"+menu).html( data[0] );
			setDivCenter( menu );
			$("#"+menu).show(200);
			
			loadArticles();
			}
		});
	}

function setATime() {
	var d = new Array();
	
	d["aid"] = $("#a_id").val();
	d["time"] = $("#c_time").val();
	
	$.ajax	({
		url:"engine/ajax.php?op=set_plan_time&aid="+d["aid"]+"&time="+d["time"]+"",
		type: "GET",
		dataType: 'json',
		success:function( data ) {	
			$(".custom-menu").fadeOut(100);
			}
		});
	}

function setAStatus() {
	var d = new Array();
	
	d["aid"] = $("#a_id").val();
	d["status"] = $("#c_status").val();
	
	$.ajax	({
		url:"engine/ajax.php?op=set_plan_status&aid="+d["aid"]+"&status="+d["status"]+"",
		type: "GET",
		dataType: 'json',
		success:function( data ) {	
			$(".custom-menu").fadeOut(100);
			}
		});
	}

$(".custom-menu li").click(function(e){
    switch($(this).attr("data-action")) {
	    case "plan-remove": plannerContextMenu('remove', 'planner_remove'); break;
	    case "plan-modify": plannerContextMenu('modify', 'planner_modify'); break;
	    case "plan-move": plannerContextMenu('move', 'planner_move'); break;
    	}
		
	var ignore = new Array( "menuInput" );
	var classes = e.target.className;
	if( jQuery.inArray( classes, ignore ) == -1 ) {
		$(".custom-menu").fadeOut(100);
		}
 	});

function removeTiles() {
	var cbox = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked, #pasteboard_box input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).attr("pageid") );
		}); 
		
	if( cbox.length > 0 ) {
		if( confirm( "<placeholder remove tiles>?" ) ) {
			$.ajax	({
				url:"engine/flatplan_planner_ajax.php?op=removeplannertiles&id=<?= $_GET["id"] ?>",
				type: "POST",
				data: { pageselector: cbox },
				dataType: 'json',
				success:function( data ) {
					loadPasteboard();
					}
				});			
			}
		}
		
	else {
		if( $(".rect_select").length == 1 ) {
			if( confirm( "<placeholder remove mixed tile>?" ) ) {
				$.ajax	({
					url:"engine/flatplan_planner_ajax.php?op=removeplannermixedtile&pid="+$(".rect_select").attr("pid"),
					type: "POST",
					data: { pageselector: cbox },
					dataType: 'json',
					success:function( data ) {
						console.log( data );
						loadPasteboard();
						}
					});				
				}
			}
		}	
	}

$(window).keydown(function(evt) {
	//console.log( evt.which );	
	if( evt.which == "46" ) {
		removeTiles();
		}
	});

</script>