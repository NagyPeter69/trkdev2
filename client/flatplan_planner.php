<?php

$rights = array();
if( isset( $_SESSION['intra_user'] ) ) {
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
	foreach( $r[0] as $key => $val ) {
		$rights[$key] = $val;
		}
	}

?>
<link rel="stylesheet" href="css/jquery-ui.css">
<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
<link href="css/main.css" rel="stylesheet" type="text/css" />
<link href="css/load_bar.css" rel="stylesheet" type="text/css" />
<link href="css/style.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" type="text/css" href="css/calendarPrint.css" media="print">

<link rel="stylesheet" media="screen" type="text/css" href="css/colorpicker.css" />
<script type="text/javascript" src="js/colorpicker.js"></script>

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

<?php if( isMobile() ) { ?>
<div id="headerExtraLine">
	<div style='display: inline-block; margin-left: -1px; font-size: 14px; text-align:left; margin-top: -5px; position: absolute; left: 0px;'>
		<?
			if( !isset( $_GET['opt'] ) ) {
				switch( $user[0][19] ) {
					case "FIN":
						$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
						$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
		
						if( $check[0][0] != "" ) $_GET['opt'] = $user[0][19];
						else $_GET['opt'] = "";
						break;
	
					case "PRE":
						
						$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
						$check = sql_get( 'pageinfo', 'type="PRE" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
						
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
					$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="1" LIMIT 1', '*' );
					
					if( $check[0][0] == "" ) $valid = false;
					}				
				else if( $key != "" ) {									
					$check = sql_get( 'pageinfo', 'type="'.$key.'" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
	
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
	<table id="tabla" cellspacing="0" cellpadding="0" style="width: 100%; height: 100%; table-layout:fixed;"><tbody>
		<tr>
			<td class="fp_left" valign="top" style="background: rgb( 227, 227, 227); width: 229px; padding: 13px; text-align: left; overflow: auto;">
				<?php if( $user[0][4] == "6" ) { ?>
					<div id="selector" style="padding-bottom: 13px;">
						<div id="pubselectBox" style="float: left;">
							<select id="pubselect">
							<?php
							$pubs = sql_aget( "publishers", "1 order by name ASC", "*" );
							for( $i = 0; $i < count( $pubs ); $i++ ) {
								//if( $pubs[$i]["id"] != 6 ) {
									echo "<option ".( $user[0][34] == $pubs[$i]["id"] ? "selected" : "" )." value='".$pubs[$i]["id"]."'>".$pubs[$i]["name"]."</option>";
								//	}
								}
							?>
							</select>
						</div>
						<div style="float: left; font-size: 13px; margin-top: 2px;">
							<input type="radio" checked name="modeSwitch" value="Client"><?= $lang["calendar"]["client"] ?>
							<input type="radio" name="modeSwitch" value="Internal"><?= $lang["calendar"]["internal"] ?>
						</div>
						<div style="clear: both;"></div>
					</div>
				<?php } else { ?>
					<input type="hidden" id="pubselect" value="<?= $user[0][4] ?>">
				<?php } ?>
				<div style="height:1px;" >
					<div id="maglist" style="overflow: auto; overflow-x: hidden;"></div>
					
					<div id="ptools">
						<div style="font-size: 12px; margin-top: 10px;">
							<?= $lang["calendar"]["showdays"] ?>: 
							<select name="showdays" id="showdays" onchange="saveShowDays( $(this).val() )">
								<?php
								$array = array( "print" => $lang["calendar"]["print_days"], "sales" => $lang["calendar"]["sales_days"], "both" => $lang["calendar"]["both"] );
								foreach( $array as $key=>$value ) {
									echo "<option ".( $key == $user[0][29] ? "selected" : "" )." value='".$key."'>".$value."</option>";
									}
								?>
							</select>
						</div>
	
						<div style="display: inline-block;">
							<button onclick='pdfGen()' style='margin-top: 5px;'><?= $lang["calendar"]["generate_pdf"] ?></button>
						</div>
						
						<div style="display: inline-block;">
							<button onclick='SpecDate()' style='margin-top: 5px;'><?= $lang["calendar"]["spec_dates"] ?></button>
						</div>
						
						<?php if( $rights["task_lists"] ) { ?>
							<div style="clear:both;"></div>
							<div style="display: inline-block; margin-left: 0px;">
								<button onclick='showtasks()' style='margin-top: 5px;'><?= $lang["calendar"]["task_lists"] ?></button>
							</div>
						<?php } ?>
						<div id="version" style="display: inline-block; margin-left: 3px; float: right; font-size: 13px; margin-top: 6px;"></div>
					</div>
				</div>
			</td>
			<td id="fp_content" valign="top" style="overflow-x: auto; overflow-y: auto; display: block; white-space: nowrap;">				
				<div id='fp_wrapper' style='position:relative;'></div>
			</td>
		</tr>
	</tbody></table>
</div>
<div id='dummyLog' style='display: none;'></div>

<?

$check = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND code='".$magazine[0][3]."' AND issue='".$pub[0][10]."' AND fin='1'", "id" );
$allowedOpt = ( count( $check) > 0 ? "FIN" : "" );


?>

<ul id='customMenu' class='custom-menu floatMenu'>
  <li data-action="modify"><?= $lang["calendar"]["orderModify"] ?></li>
  <li data-action="remove"><?= $lang["calendar"]["orderRemove"] ?></li>
</ul>

<script>
var year = parseInt( "<?= date( "Y" ) ?>" );
var mode = $("input[name='modeSwitch']:checked").val();
var publisher = $("#pubselect").val();

function saveShowDays( val ) {
	$.ajax	({
		url:"plugins/calendar.php",
		data: 'op=saveShowDay&val='+val+'&pub='+publisher,
		dataType: 'json',
		success:function( data ) {
			loadCalendar( year );
			}
		});
	}

$("input[name='modeSwitch']").change(function(){
	mode = $("input[name='modeSwitch']:checked").val();
	if( mode == "Internal" ) {
		$("#ptools").hide(0);
		$("#pubselectBox").hide(0);
		}
	else {
		$("#pubselectBox").show(0);
		$("#ptools").show(0);
		}
		
	loadMags();
	loadCalendar(year);
	});

function setNotification( pID ) {
	settingsPanel("mwcalendar_notification", undefined, pID );
	}

function newDefine( magID ) {
	settingsPanel("mwcalendar_define", undefined, magID );
	}

function SpecDate() {
	settingsPanel("mwcalendar_specdates", undefined, [ $("#pubselect").val() , year ] );
	}

function showtasks() {
	settingsPanel("mwcalendar_tasklist", undefined, publisher );
	}

function removeEvent( eid ) {
	if( confirm( "Are you sure you want to remove the selected special days?") ) {
		$.ajax	({
			url:"plugins/calendar.php",
			data: 'op=removeEvent&eid='+eid+'&pub='+publisher,
			dataType: 'json',
			success:function( data ) {
				$(".mevent[eid='"+eid+"']").remove();
				loadMags();
				}
			});
		}
	}

function saveCalMagSettings( obj ) {
	var mid = $(obj).attr("mid");
	var val = $(obj).val();
	var name = $(obj).attr("name");
	
	$.ajax	({
		url:"plugins/calendar.php",
		data: 'op=savemagsettings&mid='+mid+'&name='+name+'&val='+val+'&pub='+publisher,
		dataType: 'json',
		success:function( data ) {
			loadMags();
			}
		});		
	}

function loadMags() {
	$.ajax	({
		url:"plugins/calendar.php?op=loadmagazines&year="+year+'&pub='+publisher+'&mode='+mode,
		type: "POST",
		dataType: 'json',
		success:function( data ) {
			$("#maglist").html( data[0] );			
			loadCalendar( year );
			
			$(".arrow-right").off();
			$(".arrow-right").click( function() {
				year += 1;
				changeYear( year );
				});
				
			$(".arrow-left").off();
			$(".arrow-left").click( function() {
				year -= 1;
				changeYear( year );
				});

			// Only rendered for admins (see plugins/calendar.php) - adds the
			// currently-shown year's Hungarian public holidays, so a year
			// beyond whatever a developer last hand-added to
			// calendarHoliday() in engine.php no longer means it shows up
			// with no holidays marked until someone edits and redeploys
			// that array. Safe to click again on an already-added year -
			// the backend just skips dates it already has.
			$(".addYearButton").off();
			$(".addYearButton").click( function() {
				if( !confirm( "Add Hungarian public holidays for "+year+"?" ) ) return;
				$(".addYearButton").addClass("disabled");
				$.ajax	({
					url:"plugins/calendar.php?op=addYear&year="+year,
					dataType: 'json',
					success:function( result ) {
						$(".addYearButton").removeClass("disabled");
						if( result.ok ) {
							loadCalendar( year );
							}
						alert( result.message );
						}
					});
				});

			$('.colorBox, .altercolorBox').off();
			$('.colorBox').ColorPicker({
				onBeforeShow: function () {
					colorField = this;
					$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
				},
				onShow: function (colpkr) {
					currentPCK = colpkr;
					currentMID = $(this).attr("magid");
					$(colpkr).fadeIn(500);
					$(".hided").hide(0);
					
					loadCMS();
					return false;
				},
				onHide: function (colpkr) {
					$(colpkr).fadeOut(500);
					return false;
				},
				onChange: function (hsb, hex, rgb) {
					color_hex = hex;
					var magazineID = $(colorField).attr("magid");
					saveColor( color_hex, magazineID );
					$(colorField).css('background-color', '#' + hex);
					$(".printOrder[magid='"+magazineID+"']").css('background-color', '#' + adjustColor( hex, +20 ) );
					$(".salesOrder[magid='"+magazineID+"']").css('background-color', '#' + adjustColor( hex, -20 ) );
				}
			})
			$('.altercolorBox').ColorPicker({
				onBeforeShow: function () {
					colorField = this;
					$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
				},
				onShow: function (colpkr) {
					currentPCK = colpkr;
					currentMID = $(this).attr("magid");
					//$(colpkr).css("width", "213px");
					$(colpkr).fadeIn(500);
					$(".hided").hide(0);
					
					loadCMS( $(this).attr("plannerid") );
					return false;
				},
				onHide: function (colpkr) {
					$(colpkr).fadeOut(500);
					return false;
				},
				onChange: function (hsb, hex, rgb) {
					color_hex = hex;
					var magazineID = $(colorField).attr("plannerid");
					saveColor( color_hex, magazineID );
					$(colorField).css('background-color', '#' + hex);
					$(".printOrder[plannerid='"+magazineID+"']").css('background-color', '#' + adjustColor( hex, +20 ) );
					$(".salesOrder[plannerid='"+magazineID+"']").css('background-color', '#' + adjustColor( hex, -20 ) );
				}
			})
			.bind('click', function(){
				$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
				});	
				
			$("#selectall").click( function(){
				if( $('#selectall:checked').length > 0 ) {
					$("input[name='showedMagazines[]']").prop('checked', true);
					$(".toggleGroup").prop('checked', true);
					$(".allselect").html( "<?= $lang['calendar']['deselectall'] ?>" );
					}
				else {
					$("input[name='showedMagazines[]']").prop('checked', false);
					$(".toggleGroup").prop('checked', false);
					$(".allselect").html( "<?= $lang['calendar']['selectall'] ?>" );
					}
					
				loadCalendar( year );
				});
				
			$("input[name='showedMagazines[]']").click( function(){
				loadCalendar( year );
				});
				
			$(".toggleGroup").click( function(){
				var grp = $(this).attr("grp");
				
				if( $(this).is(':checked') ) {
					$("input[group='"+grp+"']").prop('checked', true);
					}
				else {
					$("input[group='"+grp+"']").prop('checked', false);
					}
					
				loadCalendar( year );
				});
			}
		});
	}

<?php if( $user[0][4] == "6" ) { ?>

	publisher = $("#pubselect").val();
	$("#pubselect").change(function() {
		publisher = $("#pubselect option:selected").val();
		$.ajax	({
			url:"plugins/calendar.php",
			data: 'op=saveadminplanner&uid=<?= $user[0][0] ?>&pid='+publisher,
			dataType: 'json',
			success:function( data ) {
				loadCalendar( year );
				}
			});

		loadMags();
		});
	
	loadMags();	
	
<?php } else { ?>

	loadMags();	
	
<?php } ?>	
	
var version;



function pdfGen() {
	var mags = $("input[name='showedMagazines[]']:checked").map(function(){
      return $(this).val();
    }).get();
	
	window.open('calendar_to_pdf.php?year='+year+'&ver='+version+'&mags='+mags+'&pub='+publisher+'', '_blank');
	}	
	
var enableContext = 1;
var winWidth = $(window).width()-20;
var found = "";
var contextWidth = $(".custom-menu").width();
$(document).bind("contextmenu", function (event) {
    event.preventDefault();
    found = $(event.target).closest( ".orderTile" );

   	var check = found.children( "div" )[0];
   	if( !$(check).hasClass('adChecking2') ) {
		$(".custom-menu").fadeOut(100, function(){
			if( enableContext && found.length != 0 ) {
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

				$(".custom-menu").fadeIn(100).css({
					top: event.pageY + "px",
					left: event.pageX + "px"
					});	
				}
			});
   		}
	});

$(document).bind("mousedown", function (e) {
    var container = $(".custom-menu");
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.fadeOut(100);
    	}
	});
	
function myContextMenu( operation ) {
	switch( operation ) {
		case 'remove':
			if( confirm( "Biztosan törölni a(z) "+$( found ).attr("magCode")+" nevű megjelenést?" ) ) {
				$.ajax	({
					url:"plugins/calendar.php?op=removeOrder&oid="+$( found ).attr("plannerid")+'&pub='+publisher,
					type: "POST",
					dataType: 'json',
					success:function( data ) {
						loadCalendar( year );
						}
					});
				}
			break;
		}
	}

var currentBox;
$(".custom-menu li").click(function(){
    switch($(this).attr("data-action")) {
		case "remove": myContextMenu('remove'); break;
		case "modify": 
			if( $( currentBox ).hasClass("orderTile") ) {
				var target = $(currentBox);
				}
			else {
				var target = $(currentBox).parent();
				}

			settingsPanel("mwcalendar_modify", "calendar", $(target).attr("plannerid") );
			break;
    	}
     $(".custom-menu").fadeOut(100);
 	});	

var orderHotkeysOn = false;
var code = "";
var orderObj;

$(document).bind("keyup", function (e) {
	var code = e.keyCode || e.which;

	switch( code ) {
		case 8:
			if( orderHotkeysOn ) {
				found = $(orderObj.target).closest( ".orderTile" );
				myContextMenu('remove');
				}
			break;
		}
	});

var currentDrag = "";
function dragChangeDate( id, day, datetype ) {
	$.ajax	({
		url:"plugins/calendar.php",
		data: 'op=savedate&id='+id+'&day='+day+'&datetype='+datetype+'&pub='+publisher,
		dataType: 'json',
		success:function( data ) {
			loadCalendar( year );
			}
		});	
	}

function startDef( magid, day ) {
	settingsPanel("mwcalendar_calendar", "calendar", magid+"|"+day );
	}

function dragStart(event) {
    currentDrag = event.target;
    event.dataTransfer.setData("Text", event.target.id);
    setTimeout( function() {
	    $(".printOrder, .salesOrder").css( "pointer-events", "none" );
	    }, 100 );
}

function dragStop(event) {
    $(".printOrder, .salesOrder").css( "pointer-events", "auto" );
}

function dragEnter(event) {
    if ( $(event.target).hasClass("workday") ) {
        $(event.target).addClass("overDrop");
    }
}

function dragLeave(event) {
    if ( $(event.target).hasClass("workday") ) {
        $(event.target).removeClass("overDrop");
    }
}

function allowDrop(event) {
    event.preventDefault();
}

function drop(event) {
    if( $(currentDrag).hasClass("magazines") ) {
	    event.preventDefault();
	    $(event.target).removeClass("overDrop");
	    if ( $(event.target).hasClass("workday") ) {
		    var day = $(event.target).find(".day").attr("date");
		    var magid = $(currentDrag).attr("magid");
		    
		    startDef( magid, day );
		    }
		}
		
	if( $(currentDrag).hasClass("printOrder") ) {
	    event.preventDefault();
	    $(event.target).removeClass("overDrop");
		if ( $(event.target).hasClass("workday") ) {
			if( confirm( 'Are you sure you want to modify the print order date of the '+$(currentDrag).attr("magCode")+' magazine?' ) ) {
				var day = $(event.target).find(".day").attr("date");
			    var id = $(currentDrag).attr("plannerId");
			    
			    dragChangeDate( id, day, "printDay" );
				}
			}
		}

	if( $(currentDrag).hasClass("salesOrder") ) {
	    event.preventDefault();
	    $(event.target).removeClass("overDrop");
		if ( $(event.target).hasClass("workday") ) {
			if( confirm( 'Are you sure you want to modify the sales day date of the '+$(currentDrag).attr("magCode")+' magazine?' ) ) {
				var day = $(event.target).find(".day").attr("date");
			    var id = $(currentDrag).attr("plannerId");
			    
			    dragChangeDate( id, day, "salesDay" );
				}
			}
		}
		
	$(".printOrder, .salesOrder").css( "pointer-events", "auto" );
	}

var maxWidth = $(window).width()-80-160;
var maxWidth2 = $(window).width()-80-160;	
var row2 = parseInt( maxWidth2 / 200 );

function changeYear( newYear ) {
	$(".calendarYear").html( year );
	loadMags();
	loadCalendar( year );	
	}

function rgb2hex(rgb) {
    if (/^#[0-9A-F]{6}$/i.test(rgb)) return rgb;

    rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    function hex(x) {
        return ("0" + parseInt(x).toString(16)).slice(-2);
    }
    return hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
}

var colorField = "";
var color_hex = "";
var currentPCK;
var currentMID;

function loadMagEvents() {
	$.ajax	({
		url:"plugins/calendar.php",
		data: 'op=loadmevents&mid='+currentMID+'&year='+year+'&pub='+publisher,
		dataType: 'json',
		success:function( data ) {
			$(currentPCK).find(".magEvents").html( data );
			}
		});	
	}

function loadCMS( pid ) {
	$.ajax	({
		url:"plugins/calendar.php",
		data: 'op=loadmsettings&mid='+currentMID+'&pub='+publisher+'&pid='+pid,
		dataType: 'json',
		success:function( data ) {
			console.log( data );
			$(currentPCK).find(".msettings").html( data );
			loadMagEvents();
			}
		});	
	}

function saveColor( color, magid ) {
	$.ajax	({
		url:"plugins/calendar.php",
		data: 'op=savecolor&id='+magid+'&color='+color+'&pub='+publisher,
		dataType: 'json',
		success:function( data ) {}
		});	
	}

function loadCalendar( year ) {
	var magazines = new Array();
	$("input[name='showedMagazines[]']:checked").each(function () {
    	magazines.push(parseInt($(this).val()));
		});
		
	$.ajax	({
		url:"plugins/calendar.php",
		data: 'year='+year+'&magazines='+JSON.stringify(magazines)+'&pub='+publisher+'&mode='+mode,
		dataType: 'json',
		success:function( data ) {
			version = data[1];
			$("#fp_wrapper").html( data[0] );
			$("#version").html( data[1] );
			
			$(".salesOrder").off();
			$(".salesOrder").mouseenter(function() {
				var pair = $(this).attr("plannerid");
				var magid = $(this).attr("magid");
				
				$(this).addClass( "zooming" );
				$(this).find(".boxFullDate").fadeIn(200);
				$(this).find(".boxCN").fadeIn(200);
				$(this).find(".boxType").fadeIn(200);
				
				$(".printOrder[plannerid='"+pair+"']").addClass( "zooming" );
				$(".printOrder[plannerid='"+pair+"']").find(".boxFullDate").fadeIn(200);
				$(".printOrder[plannerid='"+pair+"']").find(".boxCN").fadeIn(200);
				$(".printOrder[plannerid='"+pair+"']").find(".boxType").fadeIn(200);
				
				$(".magazines[magid='"+magid+"']").parent().parent().addClass( "highlight" );
				});
			
			$(".salesOrder").mouseleave(function() {
				var pair = $(this).attr("plannerid");
				var magid = $(this).attr("magid");
				
				$(this).removeClass( "zooming" );
				$(this).find(".boxFullDate").fadeOut(0);
				$(this).find(".boxCN").fadeOut(0);
				$(this).find(".boxType").fadeOut(0);
				
				$(".printOrder[plannerid='"+pair+"']").removeClass( "zooming" );
				$(".printOrder[plannerid='"+pair+"']").find(".boxFullDate").fadeOut(200);
				$(".printOrder[plannerid='"+pair+"']").find(".boxCN").fadeOut(200);
				$(".printOrder[plannerid='"+pair+"']").find(".boxType").fadeOut(200);

				$(".magazines[magid='"+magid+"']").parent().parent().removeClass( "highlight" );
				});
				
			$(".printOrder").off();
			$(".printOrder").mouseenter(function() {
				var pair = $(this).attr("plannerid");
				var magid = $(this).attr("magid");
				
				$(this).addClass( "zooming" );
				$(this).find(".boxFullDate").fadeIn(200);
				$(this).find(".boxCN").fadeIn(200);
				$(this).find(".boxType").fadeIn(200);
				
				$(".salesOrder[plannerid='"+pair+"']").addClass( "zooming" );
				$(".salesOrder[plannerid='"+pair+"']").find(".boxFullDate").fadeIn(200);
				$(".salesOrder[plannerid='"+pair+"']").find(".boxCN").fadeIn(200);
				$(".salesOrder[plannerid='"+pair+"']").find(".boxType").fadeIn(200);
				
				$(".magazines[magid='"+magid+"']").parent().parent().addClass( "highlight" );
				});
			
			$(".printOrder").mouseleave(function() {
				var pair = $(this).attr("plannerid");
				var magid = $(this).attr("magid");
				
				$(this).removeClass( "zooming" );
				$(this).find(".boxFullDate").fadeOut(0);
				$(this).find(".boxCN").fadeOut(0);
				$(this).find(".boxType").fadeOut(0);
				
				$(".salesOrder[plannerid='"+pair+"']").removeClass( "zooming" );
				$(".salesOrder[plannerid='"+pair+"']").find(".boxFullDate").fadeOut(200);
				$(".salesOrder[plannerid='"+pair+"']").find(".boxCN").fadeOut(200);
				$(".salesOrder[plannerid='"+pair+"']").find(".boxType").fadeOut(200);
				
				$(".magazines[magid='"+magid+"']").parent().parent().removeClass( "highlight" );
				});
				
			$(".printOrder, .salesOrder").mouseover(function(){
				orderHotkeysOn = true;
				orderObj = event;
				
				currentBox = event.target;
				});
				
			$(".printOrder, .salesOrder").mouseleave(function(){
				orderHotkeysOn = false;
				});	
			}
		});
	}
	
$(window).load(function(){
	var mheight = $(window).height() - $("#ptools").height() - $("#header").height() - 39;		
	$("#maglist").css("max-height", mheight+"px");
	
	fit_box_planner();
	});

$(window).resize(function(){
	var mheight = $(window).height() - $("#ptools").height() - $("#header").height() - 39;		
	$("#maglist").css("max-height", mheight+"px");
		
	fit_box_planner();	
	});

</script>