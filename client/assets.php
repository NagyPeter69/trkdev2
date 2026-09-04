<?php
$usettings = json_decode( $user[0][39], true );
$temp = explode( "_", $usettings["id"] );
//$tempMag = sql_get( 'magazines', 'code="'.$temp[0].'"', '*' );
$tempPub = sql_get( 'publications', 'id="'.$temp[0].'"', '*' );
if( !empty( $tempPub[0] ) ) {
	$_GET['id'] = $tempPub[0][0];
	$_GET['code'] = $tempPub[0][10];
	}
else {
	if( !empty( $user[0][11] ) ) {
		$temp = explode( "_", $user[0][11] );
		$mag = sql_aget( "magazines", "code='".$temp[0]."'", "*" );
		$tempPub = sql_get( 'publications', 'code="'.$temp[1].'" AND magazine_id="'.$mag[0]["id"].'"', '*' );
		$_GET['id'] = $tempPub[0][0];
		$_GET['code'] = $tempPub[0][10];
		}
	}
	
if( !empty( $_SESSION["visitor_pub"] ) ) {
	$tempPub = sql_get( 'publications', 'id="'.$_SESSION["visitor_pub"].'"', '*' );
	$_GET['id'] = $tempPub[0][0];
	$_GET['code'] = $tempPub[0][10];
	}
	
	
?>
<div id='content_box' style='overflow: hidden !important; width: 100%; position: absolute;'>
	<table id="tabla" cellspacing="0" cellpadding="0" style="width: 100%; table-layout:fixed;"><tbody>
		<tr>
			<td class="fp_left" valign="top" style="position: relative; background: rgb(146, 146, 146); width: 229px;">
				<div class='top_menu' >
					<div style="padding: 10px; text-align: left;">
						<div style='float: left;'>
							<select style="margin-left: -1px;" id="pubs">
							<?
								$pubs = getShortPubs4( $user[0] );
								if( empty( $_GET['code'] ) ) {
									$magazine = sql_get( 'magazines', 'id="'.$pubs[0][2].'"', 'code, type' );
									sql_update( 'accounts', 'actual="'.$magazine[0][0].'_'.$pubs[0][10].'"', 'id="'.$_SESSION['intra_user'].'"' );
									
									if( $magazine[0][1] !== "Adhoc" ) {
										$_GET['id'] = $magazine[0][0];
										$_GET['code'] = $pubs[0][10];
										}
									}
									
								for( $i = 0; $i < count( $pubs ); $i++ ) {
									$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code, type, name' );
									if( empty( $_GET['id'] ) ) {
										$_GET['id'] = $pubs[$i][0]."_".$firstPage[0][0]."_".$firstPage[0][1];
										}
										
									echo "<option class='pub_opt' value='".$pubs[$i][0]."_".$firstPage[0][0]."_".$firstPage[0][1]."' ";
										if( $usettings["id"] == $pubs[$i][0]."_".$firstPage[0][0]."_".$firstPage[0][1] ) {
											echo "selected";
											}
											
										elseif( empty( $usettings["id"] ) && $_GET['code'] == $pubs[$i][10] && $_GET['id'] == $pubs[$i][0] ) {
											echo "selected";
											}
									echo " text-value='".( $magazine[0][1] == "Regular" ? $magazine[0][0]." ".$pubs[$i][10] : $magazine[0][0] )."' long-value=' – ".$magazine[0][2]."'>".( $magazine[0][1] == "Regular" ? $magazine[0][0]." ".$pubs[$i][10] : $magazine[0][0] )."</option>";
									}
							?>
							</select>
						</div>

					</div>
				</div>
				
				<!--
				<div style='clear:both;'></div>
				<div id="types" style="padding: 10px; text-align: left;"></div>
				-->
				
				<div id="pack_inside_utils" style="display: none;">
					<div style='clear:both;'></div>
					
					<div id="pack_buttons">
					</div>
					
					<div id="backButton" style='position: absolute; bottom: 10px; font-size: 13px; height: 29px; left: 50%; -webkit-transform: translateX(-50%); transform: translateX(-50%)'>
						<div onclick="closePackInside()" style="margin-left: 1px; margin-top: 4px;" class="panelButton autoWidth"><?= $lang["ad_preview"]["back"] ?></div>		
					</div>	
				</div>
				
				<div id="assets_utils">
					<div style='clear:both;'></div>
					<div style="padding: 10px;">
						<div style="float: left; color: rgb( 240 240 240 ); font-family: myriad; font-size: 15px;"><?= $lang["assets"]["stripcodes"] ?></div>
						<div style="float: left; margin-top: -1px;"><input type="checkbox" id="strip_code" name="strip_code" value="1" style="margin-left: 10px;"></div>
					</div>
				</div>
			</td>
			<td id="fp_content" align="right" valign="top" style="background: rgb(128, 128, 128); margin-right: 10px; overflow-x: hidden; overflow-y: auto; width: 100%; display: block;">
				<div id="pack_inside" style="display: none; color: #FFF; padding: 11px 15px; text-align: left;">
				</div>

				<div id="assets">
					<table id="assets_table" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<td id="name"><span class="ordering2"><?= $lang["assets"]["asset_name"] ?>&nbsp;</span><i class="ordering fas fa-caret-down"></i></td>
								<td id="type"><span class="ordering2"><?= $lang["assets"]["asset_type"] ?>&nbsp</span><i class="ordering fas fa-caret-down"></i></td>
								<td id="time" class="orderSelected"><span class="ordering2"><?= $lang["assets"]["asset_arrived"] ?>&nbsp;</span><i class="ordering fas fa-caret-down"></i></td>
								<td id="size" align="right"><?= $lang["assets"]["asset_size"] ?></td>
								<td id="color" ><span class="ordering2"><?= $lang["assets"]["asset_label"] ?>&nbsp;</span><i class="ordering fas fa-caret-down"></i></td>
								<td>&nbsp;</td>
							</tr>
						</thead>
						<tbody id="assets_body">
						</tbody>
					</table>
				</div>
			</td>
		</tr>
	</tbody></table>
</div>

<ul id='customMenu' class='custom-menu floatMenuC'>
  <li data-action="g_voros"><span class="circle-color2 widecolor g_voros"></span></li>
  <li data-action="f_narancs"><span class="circle-color2 widecolor f_narancs"></span></li>
  <li data-action="e_sarga"><span class="circle-color2 widecolor e_sarga"></span></li>
  <li data-action="d_zold"><span class="circle-color2 widecolor d_zold"></span></li>
  <li data-action="c_kek"><span class="circle-color2 widecolor c_kek"></span></li>
  <li data-action="b_lila"><span class="circle-color2 widecolor b_lila"></span></li>
  <li data-action="a_fekete"><span class="circle-color2 widecolor a_fekete"></span></li>
  <li data-action=""><span class="circle-color2 widecolor"></span></li>
</ul>

<script>
var alter = "ALL";
var pub = "<?= $_GET['id'] ?>";
var stripped = "0";
var order = "time";
var orderType = "DESC";
var aid = "";
var multiID = new Array();
var multiPic = new Array();
var $idown;

function selectAll() {
	$(".img_box").each(function(){
		if( !$(this).hasClass("boxSelected") ) {
			$(this).addClass("boxSelected");
			multiPic.push( $(this).attr("data") );
			}
		});
		
	$("#simpleAssetDownload").removeClass("disabled");
	}

function deselectAll() {
	$(".img_box").removeClass("boxSelected");
	multiPic = new Array();
	
	$("#simpleAssetDownload").addClass("disabled");
	}

var lastPageClick = "0";
function boxSelector(event) {
	if( $(event.target)[0].nodeName == "TD" ) {	
		var obj = event.target;
		var par = $(obj).parent().parent().parent().parent();
		var page = $(par).attr("page");
		
		if (event.shiftKey) {
			var start = 0;
			var end = 0;
			var selected = new Array();
			
			$("div").filter('.boxSelected').each(function(){
				selected.push( $(this).attr("page") );
				});
			
			if( jQuery.inArray( lastPageClick, selected ) !== -1 || selected.length == 0 ) {
				if( selected.length == 0 ) {
					start = 0;
					end = parseInt( page );
					}
				
				else {
					if( parseInt( lastPageClick ) < parseInt( page ) ) {
						start = parseInt( lastPageClick )+1;
						end = parseInt( page );
						}
					else {
						start = parseInt( page );
						end = parseInt( lastPageClick )-1;
						}
					}
					
				for( i = start; i <= end; i++ ) {
					$(".img_box[page='"+i+"']").addClass("boxSelected");
					multiPic.push( $(".img_box[page='"+i+"']").attr("data") );			
					}					
				}						
			else {
				if( parseInt( lastPageClick ) < parseInt( page ) ) {
					start = parseInt( lastPageClick )+1;
					end = parseInt( page );
					}
				else {
					start = parseInt( page );
					end = parseInt( lastPageClick )-1;
					}

				for( i = start; i <= end; i++ ) {
					$(".img_box[page='"+i+"']").removeClass("boxSelected");
					multiPic = jQuery.grep( multiPic, function(value) {
						return value != $(".img_box[page='"+i+"']").attr("data");
						});					
					}
				}
			}
		else {
			if( $(par).hasClass("boxSelected") ) {
				$(par).removeClass("boxSelected");
				multiPic = jQuery.grep( multiPic, function(value) {
					return value != $(par).attr("data");
					});
				}
				
			else {
				$(par).addClass("boxSelected");
				multiPic.push( $(par).attr("data") );
				}
			}
		lastPageClick = page;	
		}
		
	if( multiPic.length > 0 ) {
		$("#simpleAssetDownload").removeClass("disabled");
		}
	else {
		$("#simpleAssetDownload").addClass("disabled");
		}
	}

var cachePack = "";
var refreshPack = false;
function loadPack( id ) {
	$.ajax	({
		url:"engine/assets_ajax.php?op=loadPack&id="+id,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			if( cachePack != data[0] ) {
				cachePack = data[0];
				$("#pack_inside").html( data[0] );
				$("#pack_buttons").html( data[1] );
				
				$(".img_box").off();
				$(".img_box").click(function(e){
					boxSelector(e);
					});			
				}
			
			if( refreshPack ) {
				setTimeout(function(){ loadPack( id ); }, 500 );
				}
			}
		});	
	}

<?php
if( !empty( $_GET["packid"] ) ) {
	echo "packInside(".$_GET["packid"].")";
	}
?>

function packInside( id ) {
	$("#assets, #assets_utils").hide(0);
	$("#pack_inside").html("");
	$("#pack_inside, #pack_inside_utils").show(0);
	refreshPack = true;
	loadPack( id );
	cachePack = "";
	}

function closePackInside() {
	refreshPack = false;	
	$("#pack_inside, #pack_inside_utils").hide(0);	
	$("#assets, #assets_utils").show(0);
	}

function rowSelector(event) {
	if( $(event.target)[0].nodeName == "TD" ) {	
		if( !$(event.target).hasClass("noselect") ) {
			var obj = event.target;
			if( $(obj).parent().hasClass("assetSelected") ) {
				$(obj).parent().removeClass("assetSelected");
				multiID = jQuery.grep( multiID, function(value) {
					return value != $(obj).parent().attr("data");
					});
				}
			else {
				$(obj).parent().addClass("assetSelected");
				multiID.push( $(obj).parent().attr("data") );
				}
			}
		}
	}

$(document).mouseup(function(e) {
    var container = $("#customMenu");
    // if the target of the click isn't the container nor a descendant of the container
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.hide(100);
		}
	});

$(".circle-color2").click(function(){
	$.ajax	({
		url:"engine/assets_ajax.php?op=saveColor&aid="+aid+"&color="+$(this).parent().attr("data-action")+"&multi="+JSON.stringify( multiID ),
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#customMenu").hide(100);
			loadAssets();
			}
		});		
	});

function colorMenu( e, id ) {
	e.pageY -= $("#header").height();
	aid = id;
	$("#customMenu").css( {top:e.pageY, left: e.pageX}).show(200);	
	}

$(".ordering, .ordering2").click(function(){
	var id = $(this).parent().attr("id");
	var prev_id = $("#"+order).attr("id");
	
	$(".orderSelected").removeClass("orderSelected");
	if( prev_id != id ) {
		var temp = $("#"+prev_id).find(".ordering");
		if( $(temp).hasClass("fa-caret-up") ) {
			$(temp).removeClass("fa-caret-up").addClass("fa-caret-down");
			}
			
		order = id;
		$("#"+id).addClass("orderSelected");
		orderType = "DESC";
		}
	if( prev_id == id ) {
		var temp = $("#"+id).find(".ordering");
		
		if( $(temp).hasClass("fa-caret-down") ) {
			$(temp).removeClass("fa-caret-down").addClass("fa-caret-up");
			orderType = "ASC";
			}
		else if( $(temp).hasClass("fa-caret-up") ) {
			$(temp).removeClass("fa-caret-up").addClass("fa-caret-down");
			orderType = "DESC";
			}			
		
		$("#"+id).addClass("orderSelected");	
		}
	
	loadAssets();
	
	});

function saveSettings() {
	var info = {
		"id" : $("#pubs option:selected").val()
		};
		
	$.ajax({
		url:"engine/ajax.php?op=downloadsettingssave",
		type: "POST",
		data: { settings : info },
		dataType: 'json',
		success:function( data ) {}
		});		
	}


$("#pubs").change(function(){
	saveSettings();
	pub = $(this).val();
	loadAssets();
	});

$("#strip_code").change(function(){
	if( $(this).is(':checked') ) {
		stripped = "1";
		}
	else {
		stripped = "0";
		}
	//loadAssets();
	});

function downloadPic( id ) {
	console.log( multiPic );
	var link = "filedownload.php?type=asset&id="+id+"&multi="+JSON.stringify( multiPic );

	if ($idown) { $idown.attr('src',link); }
	else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
	}

function resendAsset( id ) {
	settingsPanel( 'asset_resend', "floatMenu", id );
	}

function removeAsset( id ) {
	if( confirm( "Biztosan törölni szerenéd a pakkot?" ) ) {
	$.ajax	({
		url:"engine/assets_ajax.php?op=removeAsset&id="+id,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			
			}
		});		
		}
	}

function downloadAsset( id ) {
	var link = "filedownload.php?type=asset&id="+id+"&multi="+JSON.stringify( multiID );
	console.log( multiID );

	if ($idown) { $idown.attr('src',link); }
	else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
	$(".assetSelected").removeClass("assetSelected");
	multiID = new Array();
	}

function downloadArchive( pubId ) {
	var link = "filedownload.php?type=archive&pub="+pubId;

	if ($idown) { $idown.attr('src',link); }
	else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
	}

function loadTypes() {
	$.ajax	({
		url:"engine/assets_ajax.php?op=loadTypes&pub="+pub,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#types").html(data[0]);
			alter = data[1];
			loadAssets();
			}
		});		
	}
/*	
loadTypes();
*/
function changeAlter( obj ) {
	$(".alterSelected").removeClass("alterSelected");
	$(obj).addClass("alterSelected");
	multiID = new Array();
	alter = $(obj).attr("data");
	loadAssets();
	}

var loadedasset = "";
function loadAssets() {
	console.log( "engine/assets_ajax.php?op=loadAssets&alter="+alter+"&pub="+pub+"&stripped="+stripped+"&order="+order+"&orderType="+orderType );
	$.ajax	({
		url:"engine/assets_ajax.php?op=loadAssets&alter="+alter+"&pub="+pub+"&stripped="+stripped+"&order="+order+"&orderType="+orderType,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			if( data != loadedasset ) {
				loadedasset = data;
				$("#assets_body").html(data);

				$("#assets_body td").off();
				$("#assets_body td").click(function(e){
					rowSelector(e);
					});

				$(".circle-color").off();
				$(".circle-color-box").click(function(e){
					colorMenu( e, $(this).attr("data") );
					});
				}

			setTimeout(function(){ loadAssets(); }, 1000 );
			}
		});
	}
loadAssets();

$(window).load(function(){
	var ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight() );
	$('#content_box, #fp_content').height( ad_height );
	});
	
$(window).resize(function(){
	var ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight() );
	$('#content_box, #fp_content').height( ad_height );
	});

click1Done = false;
$("#pubs").on('click', function() {
	console.log( "click: "+click1Done );
	if( click1Done ) {
		$("#pubs").blur();
		click1Done = false;
		}
	else {
		click1Done = true;
		}
    });

$("#pubs").change( function(){
	//click1Done = false;
	$("#pubs").blur();	
	});

function focus() {
  [].forEach.call(this.options, function(o) {
    o.textContent = o.getAttribute('text-value') + '' + o.getAttribute('long-value') + '';
  });
}
function blur() {
  [].forEach.call(this.options, function(o) {
    o.textContent = o.getAttribute('text-value');
  });
  
  //click1Done = false;
	console.log( "blur: "+click1Done );
}
[].forEach.call(document.querySelectorAll('#pubs'), function(s) {
  s.addEventListener('focus', focus);
  s.addEventListener('blur', blur);
  blur.call(s);
});

</script>