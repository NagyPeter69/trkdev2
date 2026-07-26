<link href="plugins/css/rightPanel.css" rel="stylesheet" type="text/css" />
<div id='rightPanel'>
	<?php if( isMobile() ) { ?>	
		<div <?= ( $user[0][35] == 1 ? "title='".$lang["flatplan"]["pagepair_tip"]."'" : "" ) ?> class='rightPanelElement' data-tool="pair" onclick="toggleTool('pair')" func='pair' style='height: 30px;'>
			<div style="pointer-events: none;">
			<?php 
				ob_start();
				include('images/SVG/spreads.svg');
				echo ob_get_clean();
			?>
			</div>
		</div>
		<div <?= ( $user[0][35] == 1 ? "title='".$lang["flatplan"]["pagewide_tip"]."'" : "" ) ?> data-tool="widePage" style='height: 30px; display: block;' id="widePage_div" class='rightPanelElement' onclick="toggleTool('widePage')" func='widePage'>
			<div style="pointer-events: none;">
			<?php 
				ob_start();
				include('images/SVG/singlepage.svg');
				echo ob_get_clean();
			?>
			</div>
		</div>	
		<div <?= ( $user[0][35] == 1 ? "title='".$lang["flatplan"]["pagesingle_tip"]."'" : "" ) ?> data-tool="single" style='height: 31px; display: none; height: 31px !important;' id="single_div" class='rightPanelElement' onclick="toggleTool('single')" func='single'>
			<div style="pointer-events: none;">
			<?php 
				ob_start();
				include('images/SVG/singlepage.svg');
				echo ob_get_clean();
			?>
			</div>
		</div>
		
		<div class='rightPanelSeparator'>
			<div style="pointer-events: none;">
			<?php 
				ob_start();
				include('images/SVG/spacer.svg');
				echo ob_get_clean();
			?>
			</div>
		</div>
		
		<?php
		if( $pdfstand != "Web" ) {
		?>
		<div <?= ( $user[0][35] == 1 ? ( $user[0][15] == trimbox ? "title='".$lang["flatplan"]["crop_tip"]."'" : "title='".$lang["flatplan"]["trim_tip"]."'" ) : "" ) ?> style='height: 33px; display:none;' class='rightPanelElement' onclick="toggleBox( this )" func='boxes'>
			<?
			echo '<img class="normal" id="boxes" src="plugins/images/'.$user[0][15].'.png">';
			?>
		</div>
		<?php
		}
		?>

		<div <?= ( $user[0][35] == 1 ? "title='".$lang["flatplan"]["compare_tip"]."'" : "" ) ?> style='height: 29px;' class='rightPanelElement' onclick="compareToggle()" func='compare'>
			<div style="pointer-events: none;">
			<?php 
				ob_start();
				include('images/SVG/compare.svg');
				echo ob_get_clean();
			?>
			</div>
		</div>
		
		<div class='rightPanelSeparator'>
			<div style="pointer-events: none;">
			<?php 
				ob_start();
				include('images/SVG/spacer.svg');
				echo ob_get_clean();
			?>
			</div>
		</div>

		<? if( $rights['createComment'] ) { ?>		
			<div <?= ( $user[0][35] == 1 ? "title='".$lang["flatplan"]["comment_tip"]."'" : "" ) ?> style='height: 33px;' class='rightPanelElement panelCorrection' onclick='panelElementClick( "dot", "dot" );' func='dot'>
			<?php 
				ob_start();
				include('images/SVG/dot.svg');
				echo ob_get_clean();
			?>
			</div>
		<? } ?>
		<div <?= ( $user[0][35] == 1 ? "title='".$lang["flatplan"]["commenthide_tip"]."'" : "" ) ?> id='showComment' style='height: 17px !important; margin-top: 3px; margin-bottom: 3px;' class='rightPanelElement panelCorrection' onclick='toggleComments( "all" );' func='dot'>
			<div class='commentsOn' style="pointer-events: none; display: none;">
			<?php 
				ob_start();
				include('images/SVG/commentsOn.svg');
				echo ob_get_clean();
			?>
			</div>
			<div class='commentsOff' style="pointer-events: none;">
			<?php 
				ob_start();
				include('images/SVG/commentsOff.svg');
				echo ob_get_clean();
			?>
			</div>			
		</div>	
						
	<?php } else { ?>
		<div class='rightPanelElement' onclick="toggleTool('pair')" func='pair' style='height: 30px;'>
			<img class='normal' id="pair" src='plugins/images/pair.png'>
			<img class='hover' id="pair" src='plugins/images/pair_hover.png'>
		</div>
		<div style='height: 30px; display: block;' id="widePage_div" class='rightPanelElement' onclick="toggleTool('widePage')" func='widePage'>
			<img class='normal' id="widePage" src='plugins/images/widePage.png'>
			<img class='hover' id="widePage" src='plugins/images/widePage_hover.png'>
		</div>	
		<div style='height: 31px; display: none; height: 31px !important;' id="single_div" class='rightPanelElement' onclick="toggleTool('single')" func='single'>
			<img class='normal' id="single" src='plugins/images/single.png'>
			<img class='hover' id="single" src='plugins/images/single_hover.png'>
		</div>	
		<div style='height: 31px;' class='rightPanelElement' onclick="advancedTool('magnify')" func='magnify'>
			<img class='normal' id="magnify" src='plugins/images/magnify.png'>
			<img class='hover' id="magnify" src='plugins/images/magnify_hover.png'>
		</div>
		<div style='height: 33px;' class='rightPanelElement' func='hand'>
			<img class='normal' id="hand" src='plugins/images/hand.png'>
			<img class='hover' id="hand" src='plugins/images/hand_hover.png'>
		</div>
		<div class='rightPanelSeparator'>
			<img class='normal' id="hand" src='plugins/images/spacer.png'>
		</div>

		<div style='height: 33px; display: <?= ( ( $user[0][26] == "1" and $pdfstand != "Web" ) ? "block" : "none" ) ?>;' class='rightPanelElement' onclick="toggleBox( this )" func='boxes'>
			<?
			echo '<img class="normal" id="boxes" src="plugins/images/'.$user[0][15].'.png">';
			echo '<img class="hover" id="boxes" src="plugins/images/'.$user[0][15].'_hover.png">';
			?>
		</div>	
		
		<div style='height: 30px;' class='rightPanelElement' onclick="toggleBar()">
			<img class='normal' id="switch" src='plugins/images/hide.png'>
			<img class='hover' id="switch" src='plugins/images/hide_hover.png'>
		</div>
		<div class='rightPanelSeparator'>
			<img src='plugins/images/spacer.png'>
		</div>
		<div style='height: 20px;' class='rightPanelElement' onclick="advancedTool('measure')" func='measure'>
			<img class='normal' id="measure" src='plugins/images/measure.png'>
			<img class='hover' id="measure" src='plugins/images/measure_hover.png'>
		</div>
		<div style='height: 32px; display: <?= ( $user[0][26] == "1" ? "block" : "none" ) ?>;' class='rightPanelElement' onclick="advancedTool('colorPicker')" func='colorPicker'>
			<img class='normal' id="colorPicker" src='plugins/images/colorPicker.png'>
			<img class='hover' id="colorPicker" src='plugins/images/colorPicker_hover.png'>
		</div>
		
		<div style='height: 29px;' class='rightPanelElement' onclick="compareToggle()" func='compare'>
			<img class='normal' id="compare" src='plugins/images/compare.png'>
			<img class='hover' id="compare" src='plugins/images/compare_hover.png'>
		</div>
		
		<div class='rightPanelSeparator'>
			<img src='plugins/images/spacer.png'>
		</div>
		
		<? if( $rights['createComment'] ) { ?>
		<div style='height: 26px;' class='rightPanelElement panelCorrection' onclick='panelElementClick( "square", "square" );' func='square'>
			<img class='normal' id="square" src='plugins/images/square.png'>
			<img class='hover' id="square" src='plugins/images/square_hover.png'>
		</div>
		<div style='height: 28px;' class='rightPanelElement panelCorrection' onclick='panelElementClick( "circle", "circle" );' func='circle'>
			<img class='normal' id="circle" src='plugins/images/circle.png'>
			<img class='hover' id="circle" src='plugins/images/circle_hover.png'>
		</div>
		<div style='height: 33px;' class='rightPanelElement panelCorrection' onclick='panelElementClick( "dot", "dot" );' func='dot'>
			<img class='normal' id="dot" src='plugins/images/dot.png'>
			<img class='hover' id="dot" src='plugins/images/dot_hover.png'>
		</div>
		<div <?= ( $user[0][35] == 1 ? "title='".$lang["flatplan"]["commenthide_tip"]."'" : "" ) ?> id='showComment' style='height: 17px !important; margin-top: 3px; margin-bottom: 3px;' class='rightPanelElement panelCorrection' onclick='toggleComments( "all" );' func='dot'>
			<img class='normal' id="hidec" src='plugins/images/showcomment.png'>
			<img class='hover' id="hidec" src='plugins/images/showcomment_hover.png'>
		</div>
	
		<? } ?>

		<div style='display: <?= ( ( $user[0][26] == "1" and $pdfstand != "Web" ) ? "block" : "none" ) ?>;'>
			<div class='rightPanelSeparator'>
				<img src='plugins/images/spacer.png'>
			</div>	
			<div style='height: 31px;' class='rightPanelElement' func='cyan' onclick="toggleColor('cyan')">
				<img class='normal' id="cyan" src='plugins/images/cyanOn.png'>
				<img class='hover' id="cyan" src='plugins/images/cyan_hover.png'>
			</div>
			<div style='height: 31px;' class='rightPanelElement' func='magenta' onclick="toggleColor('magenta')">
				<img class='normal' id="magenta" src='plugins/images/magentaOn.png'>
				<img class='hover' id="magenta" src='plugins/images/magenta_hover.png'>
			</div>
			<div style='height: 31px;' class='rightPanelElement' func='yellow' onclick="toggleColor('yellow')">
				<img class='normal' id="yellow" src='plugins/images/yellowOn.png'>
				<img class='hover' id="yellow" src='plugins/images/yellow_hover.png'>
			</div>
			<div style='height: 31px;' class='rightPanelElement' func='kblack' onclick="toggleColor('kblack')">
				<img class='normal' id="kblack" src='plugins/images/kblackOn.png'>
				<img class='hover' id="kblack" src='plugins/images/kblack_hover.png'>
			</div>
			<div id="customColors"></div>
		</div>
 	<?php } ?> 
</div>

<script>
function fit_toolbar() {
	var toolHeight = parseInt( $("#fpToolBox").outerHeight()/2 )-( parseInt( $("#rightPanel").outerHeight()/2 ) );
	$("#rightPanel").css( 'top', toolHeight+"px" );
	}

$("img.normal[id!='single']").on( "load", function() {
	var height = $(this).height();
	if( $(this).attr("id") != "single" ) {
		$(this).parent(".rightPanelElement").css("height", height+"px");
		}
	fit_toolbar();
	});

var compare = {
	"cropbox": {},
	"bleedbox": {},
	"trimbox": {},
	"cBox": {},
	"file": {},
	}

function renderComparePages( file, place, nr ) {
	if( previousCompareOperation != "diff" || nr == 1 || nr == 4 ) {
		$("#"+place+"_img").fadeOut(0).hide(0);
		var boxSize = {
			width: parseInt( pixel( defaultSizes['Width'] ) ),
			height: parseInt( pixel( defaultSizes['Height'] ) )
			}
		if( cMode == "SideBySide" ) {
			boxSize.width = boxSize.width*2+9;
			}
	
		if( pages == "1" ) {
			$(".pagePreview").css({
				"width": boxSize.width+"px",
				"height": boxSize.height+"px"
				});
				
			$("#state_a, #state_b, #left_state, #state_a_img_container").css({
				"width": boxSize.width+"px",
				"height": boxSize.height+"px"
				});
			// Was hardcoded to compare["file"][0] (side A) regardless of
			// which side (nr) this particular call is actually rendering.
			// renderComparePages() runs once per side, and each run both
			// resizes the shared #side_a/#side_b boxes AND reads that box
			// size straight back into positions['width'] a few lines
			// below to build this side's own render request - so side B's
			// request was being sized off side A's page dimensions
			// whenever the two states have different trim sizes (same
			// version normally does, but not guaranteed), stretching/
			// squeezing side B's actual content into the wrong box.
			var width = pixel( (compare["file"][nr]['Right'] - compare["file"][nr]['Left']) );
			var height = pixel( (compare["file"][nr]['Top'] - compare["file"][nr]['Bottom'] ) );

			$("#sidebyside, #side_a, #side_b").css("height", height+"px" );
			$("#sidebyside").css("width", (width*2+9)+"px" );
			$("#side_a, #side_b").css("width", width+"px" );
			$("#side_break").css("left", width+"px");
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

		$('#content_box').kinetic( 'detach' );
			
		var positions = {};		
		if( $(".pagePreview").width() > $("#content_box").width() ) {
			positions['left'] = point( $("#content_box").scrollLeft() )-compare["cBox"][nr]["Left"];
			positions['right'] = positions.left + point( $("#content_box").innerWidth() );
			positions['width'] = $("#content_box").innerWidth();
			if( nr == 3 || nr == 4 ) {
				positions['width'] = $("#state_d").width();
				positions["left"] = compare["trimbox"][nr].Left;
				positions['right'] = compare["file"][nr].Right;
				}
			positions['small'] = "false";
			}
		else {
			positions['left'] = compare["file"][nr].Left;
			positions['right'] = compare["file"][nr].Right;
			positions['width'] = pixel( compare["file"][nr].Right-compare["file"][nr].Left );
			positions['small'] = "true";	
			}

		if( $(".pagePreview").height() > $("#content_box").height() ) {
			positions['bottom'] = parseFloat( compare["file"][nr].Bottom) + point( ( $(".pagePreview").outerHeight(true) )-( $("#content_box").scrollTop() )-( $("#content_box").outerHeight(true) ) ),
			positions['top'] = positions.bottom + point( $("#content_box").innerHeight() );
			positions['height'] = $("#content_box").innerHeight();
			}
		else {
			positions['top'] = compare["file"][nr].Top;
			positions['bottom'] = compare["file"][nr].Bottom;
			positions['height'] = pixel( compare["file"][nr].Top-compare["file"][nr].Bottom );
			}		
		
		if( cMode == "SideBySide" ) {
			positions['left'] = compare["file"][nr].Left;
			positions['right'] = compare["file"][nr].Right;
			positions['width'] = $("#"+place ).width();
			}
		
		correction = {
			0: {
				"Bottom": parseFloat(compare["trimbox"][nr]["Bottom"])-tempBleed-tempKifuto-compare["cropbox"][nr]["Bottom"],
				"Left": parseFloat(compare["trimbox"][nr]["Left"])-tempBleed-tempKifuto
				}
			}

		$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )+1 ) ).trigger("onchange");
		var sendcolors = colors;
		$.ajax	({
			url:"engine/compare.php?op=render&intra_user=<?= $_SESSION['intra_user'] ?>&cMode="+cMode+"&zoom="+zoom+"&nr="+nr+"&to=compare_"+nr,
			type: "POST",
			data: { positions : positions, file: compare["file"][nr], colors: sendcolors, cBox: compare["cBox"], fpBox: fpBox, trimbox: compare["trimbox"], corr: correction },
			dataType: 'json',
			success:function( data ) {
				if( place == "state_a" ) {
					$("#"+place+"_img").css({
						left: $('#content_box').scrollLeft()+"px",
						top: $('#content_box').scrollTop()+"px",
						});
					}
				else if( place == "state_b" ) {
					$("#"+place+"_img").css({
						left: $('#content_box').scrollLeft()+"px",
						top: $('#content_box').scrollTop()+"px",
						});					
					}
				else {
					$("#"+place+"_img").css({
						top: $('#content_box').scrollTop()+"px",
						});				
					}
				
				$("#"+place+"_img").attr('src', data );

				$("#"+place+"_img").fadeIn(0).show(0);
			
				disableZoom = false;
				$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
				//createBoxes();
				},
			// Without this, a failed render (e.g. the ImagickException a
			// corrupt/racing temp file used to throw server-side) left
			// renderCounter incremented forever - the render spinner in
			// the corner never stops, indistinguishable from a render
			// that's still legitimately in progress.
			error:function() {
				disableZoom = false;
				$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
				}
			});
		}
	}

function loadComparePages( file, place, nr, sbsf ) {
	if( previousCompareOperation != "diff" || nr == 1 || nr == 4 ) {
		disableZoom = true;
		$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )+1 ) ).trigger("onchange");
		$.ajax	({
			url:"engine/compare.php?op=loadbg&intra_user=<?= $_SESSION['intra_user'] ?>&page="+page+"&pages="+pages+"&id="+pub+"&pack_id="+pack_id+"&alter="+PageType+"&state="+place,
			type: "POST",
			data: { colors: colors, file: file },
			dataType: 'json',
			success:function( data ) {
				compare["cropbox"][nr] = {
					'Left': data[15][0]["Left"],
					'Bottom': data[15][0]["Bottom"],
					'Right': data[15][0]["Right"],
					'Top': data[15][0]["Top"]
					}
				compare["bleedbox"][nr] = {
					'Left': data[14][0]["Left"],
					'Bottom': data[14][0]["Bottom"],
					'Right': data[14][0]["Right"],
					'Top': data[14][0]["Top"]
					}
				
				compare["trimbox"][nr] = {
					'Left': data[11][0]["Left"],
					'Bottom': data[11][0]["Bottom"],
					'Right': data[11][0]["Right"],
					'Top': data[11][0]["Top"]
					}
				
				compare["cBox"][nr] = {
					'Left': data[2][0]['Left'].toString().replace( ",", "." ),
					'Bottom':data[2][0]['Bottom'].toString().replace( ",", "." ),
					'Right': data[2][0]['Right'].toString().replace( ",", "." ),
					'Top': data[2][0]['Top'].toString().replace( ",", "." )
					}
				compare["cBox"][2] = data[2][2];

				compare["file"][nr] = {
					'Name': data[3][0]["Name"],
					'Right': data[3][0]["Right"].toString().replace( ",", "." ),
					'Top': data[3][0]["Top"].toString().replace( ",", "." ),
					'Left': data[3][0]["Left"].toString().replace( ",", "." ),
					'Bottom': data[3][0]["Bottom"].toString().replace( ",", "." ),
					'Width': parseFloat( data[3][0]["Width"] ),
					'Height': parseFloat( data[3][0]["Height"] )
					}

				if( compare["file"][nr]['Left'] != "0" ) {
					defaultSizesTrim['Width'] = defaultSizes.Width;
					defaultSizesTrim['Height'] = defaultSizes.Height;
					}
				if( compare["file"][nr]['Left'] == "0" ) {
					defaultSizesTrim['Width'] = parseFloat( file[0]['Right'] )-(2*parseFloat( trimbox[0]['Left']));
					defaultSizesTrim['Height'] = parseFloat(file[0]['Top'])-(2*trimbox[0]['Top']);
					}
			
				$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
				$("#"+place).css( 'background-image', 'url('+data[0]+')' );
				//createBoxes();
				
				if( sbsf == 1 && cMode == "SideBySide" ) {
					var version = $("select[name='state_a'] option[value='"+$("select[name='state_a']").val()+"']").text();
					if( version.indexOf( "(" ) != -1 ) {
						version = version.substring( ( version.indexOf( "(" )+1 ), ( version.indexOf( ")" ) ) );
						}
					else {
						version = $(".pv2").html();
						}
					$("#sbs_v1").html( version );
		
					version = $("select[name='state_b'] option[value='"+$("select[name='state_b']").val()+"']").text();
					if( version.indexOf( "(" ) != -1 ) {
						version = version.substring( ( version.indexOf( "(" )+1 ), ( version.indexOf( ")" ) ) );
						}
					else {
						version = $(".pv2").html();
						}
					$("#sbs_v2").html( version );
		
					var width = parseFloat( $(".pagePreview").css("left" ) ) + ( $("#side_a").width()/2 ) - ( $("#sbs_v1").outerWidth() / 2 );
					$("#sbs_v1").css("left", width+"px" );
					
					width = parseFloat( $(".pagePreview").css("left" ) ) + $("#side_a").width() + 10 + ( $("#side_a").width()/2 ) - ( $("#sbs_v2").outerWidth() / 2 );
					$("#sbs_v2").css("left", width+"px" );
					$("#sbs_v1, #sbs_v2").show(0);
					}
				
				setTimeout( function(){ renderComparePages( file, place, nr ); }, 0 );
				},
			// Same reasoning as renderComparePages()'s error handler below -
			// this call chains into renderComparePages() on success, so a
			// failure here left renderCounter incremented with nothing to
			// ever bring it back down.
			error:function() {
				disableZoom = false;
				$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
				}
			});
		}
	}

function loadDiff( a, b, nr, place ) {
	disableZoom = true;
	$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )+1 ) ).trigger("onchange");
	var trim = compare["trimbox"][nr];
	var crop = compare["cropbox"][nr];

	$.ajax	({
		url:"engine/compare.php?op=loaddiff&intra_user=<?= $_SESSION['intra_user'] ?>&a="+a+"&b="+b+"&type="+ compare["cBox"][2],
		type: "POST",
		data: { colors: colors, trimbox: trim, cropbox: crop },
		dataType: 'json',
		success:function( data ) {
			$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
			$("#"+place).css( 'background-image', 'url('+data+')' );
			disableZoom = false;
			}
		});		
	}

function compareToggle( force ) {
	if( force == "force" ) {
		$("#sidebyside").css({
			"width": "0px",
			"height": "0px"
			});
			
		$("#state_a, #state_b, #state_c, #state_d, #side_a, #side_b").css( "background-image", "" );
		$("#sbs_v1, #sbs_v2").hide(0);
		var $image = $('#state_a_img');
		$image.removeAttr('src').replaceWith($image.clone());
				
		var $image = $('#state_b_img');
		$image.removeAttr('src').replaceWith($image.clone());

		var $image = $('#state_c_img');
		$image.removeAttr('src').replaceWith($image.clone());
		
		var $image = $('#state_d_img');
		$image.removeAttr('src').replaceWith($image.clone());

		var $image = $('#side_a_img');
		$image.removeAttr('src').replaceWith($image.clone());	

		var $image = $('#side_b_img');
		$image.removeAttr('src').replaceWith($image.clone());
		
		var boxSize = {
			width: parseInt( pixel( defaultSizes['Width'] ) ),
			height: parseInt( pixel( defaultSizes['Height'] ) )
			}
		
		cMode = "";
		
		$(".pagePreview").css({
			"width": boxSize.width+"px",
			"height": boxSize.height+"px"
			});
		
		var data = posPreview();
		$(".pagePreview").animate({
			top: data[1],
			left: data[0]
			}, 0);
						
		$("#compare.normal").attr("src", "plugins/images/compareOn.png");
		$(".status1, .status2, .pv1, .pv2").css("visibility", "hidden");
		$.ajax	({
			url:"engine/compare.php?op=loadpanel&intra_user=<?= $_SESSION['intra_user'] ?>&page="+page+"&pages="+pages+"&id="+pub+"&pack_id="+pack_id+"&alter="+PageType,
			type: "POST",
			data: { data: data, files: file },
			dataType: 'json',
			success:function( data ) {
				compareMode = "on";
				$("#compare_panel").html( data );
				previousCompareOperation = "simple";

				$( "#compRange" ).slider({
				  max: 100,
				  min: 0,
				  value: 0,
				  slide: function( event, ui ) {
					compareSlide( ui.value );
					},
				  change: function( event, ui ) {
					compareSlide( ui.value );
					},
				  stop: function( event, ui ) {
					compareSlide( ui.value );
					}
				  });

				$("#compare_panel").show( 50 );
	
				loadComparePages( $("select[name='state_a']").val(), "state_a", 0 );
				loadComparePages( $("select[name='state_b']").val(), "state_b", 1 );
				if( pages == 2 ) {
					loadComparePages( $("select[name='state_c']").val(), "state_c", 3 );
					loadComparePages( $("select[name='state_d']").val(), "state_d", 4 );					
					}
				}
			});		
		}
	
	if( force == undefined ) {
		switch( compareMode ) {
			case 'on':
				$("#compare.normal").attr("src", "plugins/images/compare.png")
				$("#compare_panel").hide( 50, function(){
					$("#compare_panel").html( "" );
					});
			
				$("#sidebyside").css({
					"width": "0px",
					"height": "0px"
					});
				$("#state_a, #state_b, #state_c, #state_d, #side_a, #side_b").css( "background-image", "" );
				$("#sbs_v1, #sbs_v2").hide(0);
				var $image = $('#state_a_img');
				$image.removeAttr('src').replaceWith($image.clone());
						
				var $image = $('#state_b_img');
				$image.removeAttr('src').replaceWith($image.clone());

				var $image = $('#state_c_img');
				$image.removeAttr('src').replaceWith($image.clone());
				
				var $image = $('#state_d_img');
				$image.removeAttr('src').replaceWith($image.clone());				

				var $image = $('#side_a_img');
				$image.removeAttr('src').replaceWith($image.clone());	

				var $image = $('#side_b_img');
				$image.removeAttr('src').replaceWith($image.clone());
				
				var boxSize = {
					width: parseInt( pixel( defaultSizes['Width'] ) ),
					height: parseInt( pixel( defaultSizes['Height'] ) )
					}
				
				$(".pagePreview").css({
					"width": boxSize.width+"px",
					"height": boxSize.height+"px"
					});
				
				var data = posPreview();
				$(".pagePreview").animate({
					top: data[1],
					left: data[0]
					}, 0);				
					
				$("#state_a").css({
					"width": boxSize.width+"px",
					"border-right": "0px",
					"-moz-opacity": "1",
					"-khtml-opacity": "1",
					"opacity": "1",
					"-ms-filter": "progid:DXImageTransform.Microsoft.Alpha(Opacity=1000)",
					"filter": "progid:DXImageTransform.Microsoft.Alpha(opacity=100)",
					"filter": "alpha(opacity=100)"	
					});
				if( pages == "2" ){
					$("#state_c").css({
						"width": (boxSize.width/2)+"px",
						"border-right": "0px",
						"-moz-opacity": "1",
						"-khtml-opacity": "1",
						"opacity": "1",
						"-ms-filter": "progid:DXImageTransform.Microsoft.Alpha(Opacity=1000)",
						"filter": "progid:DXImageTransform.Microsoft.Alpha(opacity=100)",
						"filter": "alpha(opacity=100)"	
						});
					}
				compareMode = "off";
				cMode = "";
				disable = 0;
				$(".status1, .status2, .pv1, .pv2").css("visibility", "visible");
				var left = parseInt( $("#rightArrow").css("left") )+$(".pv2").width()+6;
				$("#rightArrow, #rightArrow_hover").css( "left", left+"px" );
				reloadBG();
				break;
		
			case 'off':		
				disable = 1;
				removeAdvancedTool();

				$('.activeGraph').each(function() {
					$(this).removeClass( 'activePanel' );
					$(this).removeClass( 'activeGraph' );
					$(this).attr('src', "plugins/images/"+$(this).attr('id')+".png");
					});

				setState( "" );
				$('#content_box').kinetic( 'attach' );			
				$("#compare.normal").attr("src", "plugins/images/compareOn.png")
				
				compareMode = "on";
				$(".status1, .status2, .pv1, .pv2").css("visibility", "hidden");
				var left = parseInt( $("#rightArrow").css("left") )-$(".pv2").width()-6;
				$("#rightArrow, #rightArrow_hover").css( "left", left+"px" );
				
				$.ajax	({
					url:"engine/compare.php?op=loadpanel&intra_user=<?= $_SESSION['intra_user'] ?>&page="+page+"&pages="+pages+"&id="+pub+"&pack_id="+pack_id+"&alter="+PageType,
					type: "POST",
					data: { data: data, files: file },
					dataType: 'json',
					success:function( data ) {
						
						$("#compare_panel").html( data );
						previousCompareOperation = "simple";

						$( "#compRange" ).slider({
						  max: 100,
						  min: 0,
						  value: 0,
						  slide: function( event, ui ) {
							compareSlide( ui.value );
							},
				  		  change: function( event, ui ) {
							compareSlide( ui.value );
							},
						  stop: function( event, ui ) {
							compareSlide( ui.value );
							}
						  });
  
						$("#compare_panel").show( 50 );
			
						loadComparePages( $("select[name='state_a']").val(), "state_a", 0 );
						loadComparePages( $("select[name='state_b']").val(), "state_b", 1 );
						if( pages == 2 ) {
							loadComparePages( $("select[name='state_c']").val(), "state_c", 3 );
							loadComparePages( $("select[name='state_d']").val(), "state_d", 4 );
							}
						}
					});
				break;
			}
		}
	}

function compareGetPages() {
	if( previousCompareOperation == "simple" || cMode == "SideBySide" ) {
		if( cMode == "SideBySide" ) {
			compareZoomCalc();
			var width = pixel( (file[0]['Right'] - file[0]['Left']) );
			var height = pixel( (file[0]['Top'] - file[0]['Bottom'] ) );
				
			$(".pagePreview, #sidebyside, #side_a, #side_b").css("height", height+"px" );
			$(".pagePreview, #sidebyside").css("width", (width*2+9)+"px" );
			$("#side_a, #side_b").css("width", width+"px" );
		
			var data = posPreview2();
			$(".pagePreview").animate({
				top: data[1],
				left: data[0]
				}, 0);
				
			loadComparePages( $("select[name='state_a']").val(), "side_a", 0, 1 );
			loadComparePages( $("select[name='state_b']").val(), "side_b", 1 );
			}
		
		else {
			loadComparePages( $("select[name='state_a']").val(), "state_a", 0 );
			loadComparePages( $("select[name='state_b']").val(), "state_b", 1 );
			if( pages == 2 ) {
				loadComparePages( $("select[name='state_c']").val(), "state_c", 3 );
				loadComparePages( $("select[name='state_d']").val(), "state_d", 4 );
				}
			}
		}
		
	if( previousCompareOperation == "diff" ) {
		var val = $("select[name='comp_operation']").val();
		
		if( val == "AtoB" ) {
			loadDiff( $("select[name='state_b']").val(), $("select[name='state_a']").val(), 1, "state_a" );
			var $image = $('#state_a_img');
			$image.removeAttr('src').replaceWith($image.clone());				
			loadComparePages( $("select[name='state_b']").val(), "state_b", 1 );
			if( pages == 2 ) {
				loadDiff( $("select[name='state_d']").val(), $("select[name='state_c']").val(), 1, "state_c" );
				var $image = $('#state_c_img');
				$image.removeAttr('src').replaceWith($image.clone());				
				loadComparePages( $("select[name='state_d']").val(), "state_d", 4 );					
				}			
			}
		if( val == "BtoA" ) {
			loadDiff( $("select[name='state_a']").val(), $("select[name='state_b']").val(), 0, "state_a" );
			var $image = $('#state_a_img');
			$image.removeAttr('src').replaceWith($image.clone());				
			loadComparePages( $("select[name='state_a']").val(), "state_b", 1 );
			if( pages == 2 ) {
				loadDiff( $("select[name='state_c']").val(), $("select[name='state_d']").val(), 1, "state_c" );
				var $image = $('#state_c_img');
				$image.removeAttr('src').replaceWith($image.clone());				
				loadComparePages( $("select[name='state_c']").val(), "state_d", 4 );					
				}			
			}	
		}
	}

function posPreview2() {
	var atm = {
		"height" : pixel( ( parseFloat( file[0]['Top'] )-parseFloat( file[0]['Bottom'] )) ),
		"width" : pixel( ( ( parseFloat(file[0]['Right']) - parseFloat(file[0]['Left'])) ) )*2+9
		};
		
	var left = 0;
	var top = 0;
	if( atm.height < fpBox.Height ) {
		top = ( fpBox.Height - atm.height ) / 2;
		}
	if( atm.width < fpBox.Width ) {
		left = ( fpBox.Width - atm.width ) / 2;
		}
	
	return new Array( left, top );
	}

var previousCompareOperation = "";
function comp_ophandle( val ) {
	cMode = val;
	if( val == "SideBySide" ) {
		if( pages == 2 ) {
			toggleTool( "single" );
			}
		$("#state_a, #state_b, #state_c, #state_d").css( "background-image", "" );
		var $image = $('#state_a_img');
		$image.removeAttr('src').replaceWith($image.clone());
				
		var $image = $('#state_b_img');
		$image.removeAttr('src').replaceWith($image.clone());

		var $image = $('#state_c_img');
		$image.removeAttr('src').replaceWith($image.clone());
				
		var $image = $('#state_d_img');
		$image.removeAttr('src').replaceWith($image.clone());
				
		compareZoomCalc();
		var width = pixel( (file[0]['Right'] - file[0]['Left']) );
		var height = pixel( (file[0]['Top'] - file[0]['Bottom'] ) );
				
		$(".pagePreview, #sidebyside, #side_a, #side_b").css("height", height+"px" );
		$(".pagePreview, #sidebyside").css("width", (width*2+9)+"px" );
		$("#side_a, #side_b").css("width", width+"px" );
		
		var data = posPreview2();
		$(".pagePreview").animate({
			top: data[1],
			left: data[0]
			}, 0);

		loadComparePages( $("select[name='state_a']").val(), "side_a", 0 );
		loadComparePages( $("select[name='state_b']").val(), "side_b", 1, 1 );

		$("#leftText").html("A");
		$("#rightText").html("B");
		previousCompareOperation = "";
		
		}	
	else {
		$("#sidebyside").css({
			"width": "0px",
			"height": "0px"
			});

		$("#side_a, #side_b").css( "background-image", "" );
		$("#sbs_v1, #sbs_v2").hide(0);
		var $image = $('#side_a_img');
		$image.removeAttr('src').replaceWith($image.clone());	

		var $image = $('#side_b_img');
		$image.removeAttr('src').replaceWith($image.clone());

		var boxSize = {
			width: parseInt( pixel( defaultSizes['Width'] ) ),
			height: parseInt( pixel( defaultSizes['Height'] ) )
			}
		
		$(".pagePreview").css({
			"width": boxSize.width+"px",
			"height": boxSize.height+"px"
			});
		
		var data = posPreview();
		$(".pagePreview").animate({
			top: data[1],
			left: data[0]
			}, 0);	
		
		if( val == "wipe" || val == "fade" ) {
			if( previousCompareOperation == "diff" || previousCompareOperation == "" ) {
				previousCompareOperation = "simple";
				compareGetPages();
				$("#compRange").slider( "value", "0" );
			
				$("#leftText").html("A");
				$("#rightText").html("B");
				}

			previousCompareOperation = "simple";
			$("#compRange").slider( "value", $('#compRange').slider("option", "value") );
			}
		else {
			if( val == "BtoA" ) {
				loadDiff( $("select[name='state_a']").val(), $("select[name='state_b']").val(), 0, "state_a" );
				var $image = $('#state_a_img');
				$image.removeAttr('src').replaceWith($image.clone());				
				loadComparePages( $("select[name='state_a']").val(), "state_b", 1 );
				if( pages == 2 ) {
					loadDiff( $("select[name='state_c']").val(), $("select[name='state_d']").val(), 1, "state_c" );
					var $image = $('#state_c_img');
					$image.removeAttr('src').replaceWith($image.clone());				
					loadComparePages( $("select[name='state_c']").val(), "state_d", 4 );					
					}
				$("#leftText").html("Diff+A");
				$("#rightText").html("A");
				}
			
			if( val == "AtoB" ) {
				loadDiff( $("select[name='state_b']").val(), $("select[name='state_a']").val(), 1, "state_a" );
				var $image = $('#state_a_img');
				$image.removeAttr('src').replaceWith($image.clone());				
				loadComparePages( $("select[name='state_b']").val(), "state_b", 1 );
				if( pages == 2 ) {
					loadDiff( $("select[name='state_d']").val(), $("select[name='state_c']").val(), 1, "state_c" );
					var $image = $('#state_c_img');
					$image.removeAttr('src').replaceWith($image.clone());				
					loadComparePages( $("select[name='state_d']").val(), "state_d", 4 );					
					}
				$("#leftText").html("Diff+B");
				$("#rightText").html("B");
				}

			if( previousCompareOperation == "simple" ) {
				$("#compRange").slider( "value", "0" );
				}				

			previousCompareOperation = "diff";
			}
		}
	}

function compareSlide( val ) {
	var operation = $("select[name='comp_operation']").val();
	var boxSize = {
		width: parseInt( pixel( defaultSizes['Width'] ) ),
		height: parseInt( pixel( defaultSizes['Height'] ) )
		}
	var newWidth = boxSize.width;
	if( pages == "2" ) newWidth = newWidth/2;
	
	if( operation == "fade" || operation == "AtoB" || operation == "BtoA" ) {
		var opacity = 100-( 100/100*parseInt( val ) );
		
		$("#state_a, #state_c").css({
			"width": newWidth+"px",
			"border-left": "0px",
			"-moz-opacity": (opacity/100),
			"-khtml-opacity": (opacity/100),
			"opacity": (opacity/100),
			"-ms-filter": "progid:DXImageTransform.Microsoft.Alpha(Opacity="+opacity+")",
			"filter": "progid:DXImageTransform.Microsoft.Alpha(opacity="+opacity+")",
			"filter": "alpha(opacity="+opacity+")"
			});
		}
	
	if( operation == "wipe" ) {		
		if( val > 0 ) {
			newWidth -= newWidth / 100 * parseInt( val );		
			$("#state_a" ).css({
				"width": newWidth+"px",
				"border-left": "1px solid #7A7A7A",
				"-moz-opacity": "1",
				"-khtml-opacity": "1",
				"opacity": "1",
				"-ms-filter": "progid:DXImageTransform.Microsoft.Alpha(Opacity=1000)",
				"filter": "progid:DXImageTransform.Microsoft.Alpha(opacity=100)",
				"filter": "alpha(opacity=100)"				
				});
			if( pages == 2 ) {
				$("#state_c" ).css({
					"width": newWidth+"px",
					"border-left": "1px solid #7A7A7A",
					"-moz-opacity": "1",
					"-khtml-opacity": "1",
					"opacity": "1",
					"-ms-filter": "progid:DXImageTransform.Microsoft.Alpha(Opacity=1000)",
					"filter": "progid:DXImageTransform.Microsoft.Alpha(opacity=100)",
					"filter": "alpha(opacity=100)"				
					});
				}
			}
		else {
			$("#state_a, #state_c" ).css({
				"width": newWidth+"px",
				"border-left": "0px",
				"border-left": "0px",
				"-moz-opacity": "1",
				"-khtml-opacity": "1",
				"opacity": "1",
				"-ms-filter": "progid:DXImageTransform.Microsoft.Alpha(Opacity=1000)",
				"filter": "progid:DXImageTransform.Microsoft.Alpha(opacity=100)",
				"filter": "alpha(opacity=100)"					
				});			
			}

		}
	}

function posPreview() {
	var def = {
		"height" : pixel( ( parseFloat( file[0]['Top'] )-parseFloat( file[0]['Bottom'] )), 100 ),
		"width" : pixel( ( ( parseFloat(file[0]['Right']) - parseFloat(file[0]['Left'])) + ( parseFloat(file[1]['Right']) - parseFloat(file[1]['Left'])) ), 100 )
		};
	if( isNaN( def.width ) ) {
		def.width = pixel( (parseFloat(file[0]['Right']) - parseFloat(file[0]['Left'])), 100 );
		}
	
	var atm = {
		"height" : pixel( ( parseFloat( file[0]['Top'] )-parseFloat( file[0]['Bottom'] )) ),
		"width" : pixel( ( ( parseFloat(file[0]['Right']) - parseFloat(file[0]['Left'])) + ( parseFloat(file[1]['Right']) - parseFloat(file[1]['Left'])) ) )
		};
	if( isNaN( atm.width ) ) {
		atm.width = pixel( (parseFloat(file[0]['Right']) - parseFloat(file[0]['Left'])) );
		}
		
	var left = 0;
	var top = 0;
	
	if( atm.height < fpBox.Height ) {
		top = ( fpBox.Height - atm.height ) / 2;
		}
	if( atm.width < fpBox.Width ) {
		left = ( fpBox.Width - atm.width ) / 2;
		}
	return new Array( left, top );
	}

function toggleBar() {
	enableCommentRefresh = false;
	var bar = $("#fpPages");
	var width = bar.width();
	
	if( parseInt( bar.css("left") ) >= 0 ) {
		//fit_box();
		bar.animate({ left: (0-width)+"px" }, 200 );
		if( parseInt( $(".pagePreview").css("left") ) > 0 ) {
			temp = parseInt( $(".pagePreview").css("left") )+(width/2);		
			$(".pagePreview").animate({ left: temp+"px" }, 200 );
			}
		extraCorrection.Left = (width/2);
		$(".commentDraw, .commentText").each(function(){
			$(this).animate({
				left: (parseFloat($(this).css("left"))+extraCorrection.Left)+"px"
				}, 200 );
			});
		$("#fpToolBox").animate({ left: ( parseInt( $("#fpToolBox").css("left") )-width)+"px" }, 200 );
		$("#fpFooter").animate({ 
			left: ( parseInt( $("#fpFooter").css("left") )-width)+"px",
			width: ( parseInt( $("#fpFooter").css("width") )+width)+"px"
			}, 200 );
		$("#content_box").animate({ width: ( parseInt( $("#content_box").css("width") )+width)+"px" }, 100 );
		$("#content_wrapper").animate({ left: ( parseInt( $("#content_wrapper").css("left") )-width)+"px" }, 200, "linear", function(){
			$("#switch.normal").attr( "src", "plugins/images/show.png" );
			$("#switch.hover").attr( "src", "plugins/images/show_hover.png" );

			fpBox = {
				'Width': parseInt( $("#content_box").width() ),
				'Height': fpBox['Height']
				}

			fit_box();
			// Only safe to measure .pagePreview for the new spine/page-center
			// positions once the panel-toggle animation (and fit_box()'s own
			// resulting resize) has actually settled - reading it mid-animate
			// would target where the render used to be, not where it ends up.
			centerToolbar( true );
			enableCommentRefresh = true;
			});
		}
	else {
		bar.animate({ left: 0+"px" }, 200 );
		extraCorrection.Left = 0;
		$(".commentDraw, .commentText").each(function(){
			$(this).animate({
				left: (parseFloat($(this).css("left"))-(width/2))+"px"
				}, 200 );
			});
		if( parseInt( $(".pagePreview").css("left") ) > 0 ) {
			temp = parseInt( $(".pagePreview").css("left") )-(width/2);		
			$(".pagePreview").animate({ left: temp+"px" }, 200 );
			}
		$("#fpToolBox").animate({ left: ( parseInt( $("#fpToolBox").css("left") )+width)+"px" }, 200 );
		$("#fpFooter").animate({
			left: ( parseInt( $("#fpFooter").css("left") )+width)+"px",
			width: ( parseInt( $("#fpFooter").css("width") )-width)+"px"
			}, 200 );
		$("#content_box").animate({ width: ( parseInt( $("#content_box").css("width") )-width)+"px" }, 100 );
		
		$("#content_wrapper").animate({ left: ( parseInt( $("#content_wrapper").css("left") )+width)+"px" }, 210, "linear", function(){
			$("#switch.normal").attr( "src", "plugins/images/hide.png" );
			$("#switch.hover").attr( "src", "plugins/images/hide_hover.png" );
			
			fpBox = {
				'Width': parseInt( $("#content_box").width() ),
				'Height': fpBox['Height']
				}

			fit_box();
			centerToolbar( true );
			enableCommentRefresh = true;
			});
		}
	}

function addPantone( id, color, title ) {
	jQuery('<div/>', {
		id: id,
    	'class': 'emptyColor',
    	style: 'background-color: rgb( '+color+' )',
   		text: '',
   		'defcolor': color,
		}).appendTo('#customColors');
   
	var html = "<div title='"+title+"' id='"+id+"_normal' style='position:absolute; left: 0px; top: 0px;'><img src='plugins/images/emptyColor.png'></div><div title='"+title+"' id='"+id+"_hover' style='position:absolute; display: none; left: 0px; top: 0px;'><img src='plugins/images/emptyColor_hover.png'></div>";
	$("#"+id).html( html );
	$("#"+id).attr( "onclick", "togglePantone('"+id+"')" );
	fit_toolbar();
	}

$.each(["addClass","removeClass"],function(i,methodname){
	var oldmethod = $.fn[methodname];
    $.fn[methodname] = function(e){
    	oldmethod.apply( this, arguments );
		if( arguments[0] == "kinetic-active" ) {
			switch( methodname ) {
				case 'addClass':
					var state= "On";
					$( "#zoomRange" ).slider( "option", { disabled: false } );
					break;
				case 'removeClass':
					var state= "";
					$( "#zoomRange" ).slider( "option", { disabled: true } );
					break;
				}
			$( "#hand" ).attr("src", "plugins/images/hand"+state+".png" );
			}
		this.trigger(methodname+"change");
		return this;
		}
	});

$(function() {
	$("div[func='hand']").click(function(){
		disableZoom = false;
		ajaxDisabled = false;
		$('#content_box').kinetic( 'attach' );
		graphState = "";
		$( "#magnify" ).attr("src", "plugins/images/magnify.png" );
		
		removeAdvancedTool();
		$('.activeGraph').each(function() {
			$(this).removeClass( 'activePanel' );
			$(this).removeClass( 'activeGraph' );
			$(this).attr('src', "plugins/images/"+$(this).attr('id')+".png");
			});
		document.getElementById("content_box").style.cursor = "hand";
		});

	$('.emptyColor').mouseover(function(){
		var id= $(this).attr('id');
		$("#"+id+"_hover").fadeIn(10);
		$("#"+id+"_normal").fadeOut(10);
		});
	
	$('.emptyColor').mouseleave(function(){
		var id= $(this).attr('id');
		$("#"+id+"_normal").fadeIn(10);		
		$("#"+id+"_hover").fadeOut(10);
		});
	
	$('.rightPanelElement').mouseover(function(){
		$(this).children(".normal").fadeOut(10);
		$(this).children(".hover").fadeIn(10);
		});
	
	$('.rightPanelElement').mouseleave(function(){
		$(this).children(".hover").fadeOut(10);
		$(this).children(".normal").fadeIn(10);
		});
	});
	
var colors = {
	cyan: "true",
	magenta: "true",
	yellow: "true",
	kblack: "true"
	}

var charBindings = true;
var boxDisplay = "none";
var userGrp = "<?= $user[0][8]; ?>";
window.addEventListener("keydown", function(e) {
	if( charBindings &&  $("#replyComment").length == 0 ) {		
		if( e.keyCode == 66 ) {
			var display = $("#boxDraw").css("display");
			switch( display ) {
				case 'none':
					$("#boxDraw").css("display", "block");
					break;
					
				case 'block':
					$("#boxDraw").css("display", "none");
					break;
				}
			boxDisplay = $("#boxDraw").css("display");
			}
			
		if( e.keyCode == 27 ) {
			$("div[func='hand']").click();
			}
		}
	});

function placeBox( force, source, from ) {
	if( from == 'changePic' ) {
		zoomCalc();
		alapzoom = zoom;
		}
	
	if( from == 'widePage' ) {
		var defHeight = pixel( (file[0]['Top'] - file[0]['Bottom'] ), 100 );
		var defWidth = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ), 100 );	
		var width = pixel( ( (file[0]['Right'] - file[0]['Left'])+(file[1]['Right'] - file[1]['Left']) ) );
		
		var widthPer = ( ( fpBox['Width'] ) / defWidth )*100;
		zoom = widthPer;

		actualPos = {
			left: $('#content_box').scrollLeft(),
			top: $('#content_box').scrollTop()
			}
		if( compareMode == "on" ) {
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
		}


		
	if( starting_zoom != zoom || fpPages == "pair" ) {
		$("#widePage_div").hide( 0 );
		$("#single_div").show( 0 );
		if( !mobile ) {
			$("#pair").attr( "src", "plugins/images/"+( fpPages == "pair" ? "pairOn" : "pair" )+".png" );
			$("#single").attr( "src", "plugins/images/"+( fpPages == "pair" ? "single" : "singleOn" )+".png" );
			$("#widePage").attr( "src", "plugins/images/widePage.png" );
			}
		}
	else {
		$("#widePage_div").show( 0 );
		$("#single_div").hide( 0 );
		if( !mobile ) {
			$("#pair").attr( "src", "plugins/images/pair.png" );
			$("#widePage").attr( "src", "plugins/images/widePageOn.png" );
			$("#single").attr( "src", "plugins/images/single.png" );
			}
		}
	
	if( cMode != "SideBySide" ) {
		data = posPreview();
		$(".pagePreview").animate({
			top: data[1],
			left: data[0]
			}, 0);
		}
	else {	
		data = posPreview2();
		$(".pagePreview").animate({
			top: data[1],
			left: data[0]
			}, 0);
		}

	oldmaxscroll["Left"] = parseFloat( $(".pagePreview").width() ) - parseFloat( $("#content_box").width() );
	oldmaxscroll["Top"] = parseFloat( $(".pagePreview").height() ) - parseFloat( $("#content_box").height() );
	if( oldmaxscroll["Left"] < 0 ) oldmaxscroll["Left"] = 0;
	if( oldmaxscroll["Top"] < 0 ) oldmaxscroll["Top"] = 0;

	setTimeout( function(){
		$("#zoomLevel").val( Math.round( zoom ) );
		$('#zoomRange').slider( "value", Math.round( zoom ) );

		if( force != false ) {
			rendering( force, source );	
				if( compareMode == "on" && force == "force" && source != "" ) {
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
					
			else if( compareMode == "on" && source == "magnify" ) {
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
			}		
		}, 100 ); 
	}

function reloadBG( switchTo, from ) {
	$(".pagePreview").css( 'background-image', 'url()' );
	disableZoom = true;
	// changePic() already does this on its own nav-arrow-click path, but
	// toggleTool() (switching single/pair mode) calls reloadBG() directly,
	// bypassing changePic() entirely - this covers that path too. Harmless
	// to repeat here since .css() is idempotent.
	$(".status1, .status2, #colorStdLabel1, #colorStdLabel2").css( "visibility", "hidden" );
	$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )+1 ) ).trigger("onchange");
	if( switchTo == undefined ) {
		switchTo = $('#boxes').attr( 'src' ).split("/").pop(-1).split(".")[0];
		}
  
	if( from == "changePic" ) {
    	for( var i = 4; i < colors.length; i++ ) {
      		delete colors[ i ];
      		}
      		
      	enableCommentRefresh = true;
   		}
	$("#boxDraw").css( "display", "none");
    $("#errorInfo").fadeOut( 100 );
	$("#boxDraw").html("");

	$(".pv1").html("");
	$(".pv2").html("");

	if( nopages == false ) {
	console.log( "engine/flatplan_reloadbg.php?op=reloadbg&intra_user=<?= $_SESSION['intra_user'] ?>&tag="+tag+"&zoom="+zoom+"&clk="+clk+"&id="+pub+"&pack_id="+pack_id+"&p="+page+"&alter="+PageType+"&part="+$('#part').val()+"&pageNumbering=<?= $pn ?>&pdfstand=<?= $pdfstand ?>" );
	$.ajax	({
		url:"engine/flatplan_reloadbg.php?op=reloadbg&intra_user=<?= $_SESSION['intra_user'] ?>&tag="+tag+"&zoom="+zoom+"&clk="+clk+"&id="+pub+"&pack_id="+pack_id+"&p="+page+"&alter="+PageType+"&part="+$('#part').val()+"&pageNumbering=<?= $pn ?>&pdfstand=<?= $pdfstand ?>",
		type: "POST",
		data: { colors: colors, switchTo: switchTo, from: from },
		dataType: 'json',
		// This request had no error handler at all - disableZoom is set true
		// right at the top of reloadBG(), before this call fires, and was only
		// ever cleared inside the success handler below. A failed/malformed
		// response (PHP fatal, timeout, non-2xx) skipped straight past success
		// entirely, leaving disableZoom stuck true forever - every subsequent
		// changePic()/_zoom() call is gated on !disableZoom, so nav silently
		// stopped responding until a full page refresh reset the JS state.
		error:function() {
			$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
			$("#errorInfo").html( "hiba" );
			$("#errorInfo").fadeIn( 100 );
			disableZoom = false;
			},
		success:function( data ) {
			if( data[0] != "error" ) {
				console.log( data );
				
				$("#customColors").html('');
				var tempcolors = data[10];
				dcolors = tempcolors;
				colors = {
					cyan: "true",
					magenta: "true",
					yellow: "true",
					kblack: "true"
					}
					
				for( var i = 0; i < dcolors.length; i++ ) {
					colors[ (i+1) ] = "true";
					addPantone( (i+1), dcolors[i], data[13][i] );
					}

				$("[title]").each(function(){
					$(this).tooltip({
						show: { delay: 1500 },
						tooltipClass: "floatMenu",
						content: $(this).attr("title")} );
					});
 
				$('.emptyColor').mouseover(function(){
					var id= $(this).attr('id');
					color = $(this).attr('defcolor').split(", ");
					color[2] = color[2].substring( 0, 3 );
					
	  				$(this).css({
	  					'background': 'rgb( '+( parseInt(color[0])-40 )+', '+(parseInt(color[1])-40)+', '+(parseInt(color[2])-40)+' )'
	  					});
	  										
					$("#"+id+"_normal").hide(1);
					$("#"+id+"_hover").show(1);
					});
					
				$('.emptyColor').mouseleave(function(){
					var id= $(this).attr('id');
					color = $(this).attr('defcolor').split(", ");
					color[2] = color[2].substring( 0, 3 );

	  				$(this).css({
	  					'background': 'rgb( '+parseInt( color[0] )+', '+parseInt(color[1])+', '+parseInt(color[2])+' )'
	  					});										
					$("#"+id+"_normal").show(1);		
					$("#"+id+"_hover").hide(1);
					});
	  
				$("#renderedIMG1").hide( 0 );
				$("#renderedIMG2").hide( 0 );
				pageID = new Array();
				statusText = new Array();
				for( var i = 0; i < data[4].length; i++ ) {
						pageID.push( data[4] );
						statusText.push( data[5] );
						}
				pages = data[4].length;									
				page = data[6].split("-")[0];
				
				if( from == "changePic" ) firstRun = true;
				fpPages = data[8];
				$("#pageNr").val( data[6] );
				// data[16]: per-page color standard(s), appended after the
				// original 16-element response shape (see
				// engine/flatplan_reloadbg.php) - one entry per displayed
				// page. Only the text is set here; repositioning happens
				// once rendering()'s tesztAjax.php call actually completes
				// and .pagePreview reflects the new page's real geometry
				// (this reloadbg response alone doesn't render anything).
				$("#colorStdLabel1").text( data[16][0] );
				$("#colorStdLabel2").text( data[16].length > 1 ? data[16][1] : "" );

				$('#leftArrow').attr('onclick', 'changePic("'+data[7][0]+'")' );
				$('#leftArrow_hover').attr('onclick', 'changePic("'+data[7][0]+'")' );
				console.log( data[7][1] );
				$('#rightArrow').attr('onclick', 'changePic("'+data[7][1]+'")' );
				$('#rightArrow_hover').attr('onclick', 'changePic("'+data[7][1]+'")' );
				
				$('#boxes.normal').attr( "src", "plugins/images/"+switchTo+".png" );
				$('#boxes.hover').attr( "src", "plugins/images/"+switchTo+"_hover.png" );
				
				//$('#boxes').attr( "src", "plugins/images/"+switchTo+".png" )
				cutBox = "";
				var temp = data[1].split("x");
				
				var boxSize = {
					width: parseInt( pixel( parseFloat( temp[0] ) ) ),
					height: parseInt( pixel( parseFloat( temp[1] ) ) )
					}
				
				if( cMode == "SideBySide" ) {
					boxSize.width = boxSize.width*2+9;
					}				
				
				cropbox[0] = {
					'Left': data[15][0]["Left"],
					'Bottom': data[15][0]["Bottom"],
					'Right': data[15][0]["Right"],
					'Top': data[15][0]["Top"]
					}
				if( data[15][1] != undefined ) {
					cropbox[1] = {
						'Left': data[15][1]["Left"],
						'Bottom': data[15][1]["Bottom"],
						'Right': data[15][1]["Right"],
						'Top': data[15][1]["Top"]
						}
					}
				bleedbox[0] = {
					'Left': data[14][0]["Left"],
					'Bottom': data[14][0]["Bottom"],
					'Right': data[14][0]["Right"],
					'Top': data[14][0]["Top"]
					}
				if( data[14][1] != undefined ) {
					bleedbox[1] = {
						'Left': data[14][1]["Left"],
						'Bottom': data[14][1]["Bottom"],
						'Right': data[14][1]["Right"],
						'Top': data[14][1]["Top"]
						}
					}
					
				trimbox[0] = {
					'Left': data[11][0]["Left"],
					'Bottom': data[11][0]["Bottom"],
					'Right': data[11][0]["Right"],
					'Top': data[11][0]["Top"]
					}
				if( data[11][1] != undefined ) {
					trimbox[1] = {
						'Left': data[11][1]["Left"],
						'Bottom': data[11][1]["Bottom"],
						'Right': data[11][1]["Right"],
						'Top': data[11][1]["Top"]
						}
					}
				// Refreshes both preflight indicators on every AJAX page
				// navigation - previously these were only ever set once in
				// the initial server-rendered HTML (reflecting whichever
				// page loaded first) and never touched again, so the
				// indicator either never appeared or stayed stuck lit for
				// every page navigated to afterward.
				$("#preflightMarker").attr( "data-pageid", data[18][0] ).attr( "onclick", 'downloadPreflight("'+data[18][0]+'")' );
				if( data[17][0] == 1 ) {
					$("#preflightMarker").addClass( "preflightError" );
					}
				else {
					$("#preflightMarker").removeClass( "preflightError" );
					}

				if( data[18][1] != undefined ) {
					$("#preflightMarker2").attr( "data-pageid", data[18][1] ).attr( "onclick", 'downloadPreflight("'+data[18][1]+'")' );
					if( data[17][1] == 1 ) {
						$("#preflightMarker2").addClass( "preflightError" );
						}
					else {
						$("#preflightMarker2").removeClass( "preflightError" );
						}
					}
				else {
					$("#preflightMarker2").removeClass( "preflightError" ).attr( "data-pageid", "" ).attr( "onclick", "" );
					}

				cBox[0] = {
					'Left': data[2][0]['Left'].toString().replace( ",", "." ),
					'Bottom':data[2][0]['Bottom'].toString().replace( ",", "." ),
					'Right': data[2][0]['Right'].toString().replace( ",", "." ),
					'Top': data[2][0]['Top'].toString().replace( ",", "." )
					}
				cBox[1] = {
					'Left': data[2][1]['Left'].toString().replace( ",", "." ),
					'Bottom': data[2][1]['Bottom'].toString().replace( ",", "." ),
					'Right': data[2][1]['Right'].toString().replace( ",", "." ),
					'Top': data[2][1]['Top'].toString().replace( ",", "." )
					}
				cBox[2] = data[2][2];

				file[0] = {
					'Name': data[3][0]["Name"].substring(3),
					'Right': data[3][0]["Right"].toString().replace( ",", "." ),
					'Top': data[3][0]["Top"].toString().replace( ",", "." ),
					'Left': data[3][0]["Left"].toString().replace( ",", "." ),
					'Bottom': data[3][0]["Bottom"].toString().replace( ",", "." ),
					'Width': parseFloat( data[3][0]["Width"] ),
					'Height': parseFloat( data[3][0]["Height"] ),
					'State': data[3][0]["State"],
					}
				file[1] = {
					'Name': data[3][1]["Name"].substring(3),
					'Right': data[3][1]["Right"].toString().replace( ",", "." ),
					'Top': data[3][1]["Top"].toString().replace( ",", "." ),
					'Left': data[3][1]["Left"].toString().replace( ",", "." ),
					'Bottom': data[3][1]["Bottom"].toString().replace( ",", "." ),
					'Width': parseFloat( data[3][1]["Width"] ),
					'Height': parseFloat( data[3][1]["Height"] ),
					'State': data[3][1]["State"],
					}
							
				origPic = {
					Width: parseInt( parseFloat( temp[0] ) ),
					Height: parseInt( parseFloat( data[9] ) ),
					};
				
				defaultSizes = {
					Width: parseInt( parseFloat( temp[0] ) ),
					Height: parseInt( parseFloat( temp[1] ) ),
					};
				
				console.log( "DEBUG");
				console.log( defaultSizes );
				
				if( file[0]['Left'] != "0" ) {
					defaultSizesTrim['Width'] = defaultSizes.Width;
					defaultSizesTrim['Height'] = defaultSizes.Height;
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
				
				alphaBoxSize = {
					width: parseInt( pixel( parseFloat( temp[0] ) ) ),
					height: parseInt( pixel( parseFloat( temp[1] ) ) )
					}
				
				if( data[12].length > 1 ) {
					$(".pv1").html( data[12][0] );
					$(".pv2").html( data[12][1] );
					$(".pv1, .pv2").animate({ opacity: "1" }, "fast" );
					$("#pageNr").css({
							'margin-left': '0px',
							'border-top-left-radius': '0px',
							'border-top-right-radius': '0px',
							'border-bottom-right-radius': '0px',
							'border-bottom-left-radius': '0px',
						});
					$(".pText").css( "margin-left", "0px" );
					}
				else {
					$(".pv2").html( data[12][0] );
					$(".pv2").animate({ opacity: "1" }, "fast" );
					$(".pv1").animate({ opacity: "0" }, 10 );
					$("#pageNr").css({
							'margin-left': '-10px',
							'border-top-left-radius': '2px',
							'border-bottom-left-radius': '2px',
							'border-top-right-radius': '0px',
							'border-bottom-right-radius': '0px',
						});
					$(".pText").css( "margin-left", "6px" );
					}
				$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
				$("#backgroundImage").attr( 'src', 'data:image/jpg;base64,'+data[0] );
				//createBoxes();
				if( compareMode == "on" && from == "changePic" ) {
					compareToggle( "force" );
					}
				else if( compareMode == "on" && ( switchTo == "trimbox" || switchTo == "mediabox" ) ) {
					compareGetPages();
					}
	
				setTimeout( function(){ 
					placeBox('force', '', from ); }, 50 ); 
				}
			else {
				$("#renderCounter").val( ( parseInt( $("#renderCounter").val() )-1 ) ).trigger("onchange");
				$("#errorInfo").html( "hiba" );
				$("#errorInfo").fadeIn( 100 );
				// Same stuck-disableZoom gap as the ajax error: handler above -
				// this branch reported the error to the user but never released
				// the nav guard, so it silently blocked every click after it.
				disableZoom = false;
				}
			}
		});
		}
	}

function createBoxes() {
	$("#boxDraw").html("");
	$("#boxDraw").css( "display", "none");

	switch( si ) {
		case 'pt':
			safety = safety / 2.834645669;
			break;
			
		case 'cm':
			safety = safety * 10;
			break;
		}
	
	var tempSafety = pixel( (safety*changer) );
	
	var pixelCorrection = 0.65;
	pixelCorrection = ( pixelCorrection / 100 ) * zoom;
	var pixelCorrection2 = 0.95;
	pixelCorrection2 = ( pixelCorrection2 / 100 ) * zoom;
	
	var correction = {};
	if( cBox[2] == "trimbox" && safety_on == "1" ) {
		if( pages > 1 ) {
			var margin = {
				"left": ( tempSafety ),
				"right": tempSafety,
				"top": ( tempSafety-pixelCorrection ),
				"bottom": ( tempSafety-pixelCorrection )
				}
	
			var tempWidth = parseFloat( ( ( file[0]["Width"] / 100 )*zoom ) )-(margin.left+margin.right)-1;
			var tempHeight =  parseFloat( pixel( (file[0]["Top"]-file[0]["Bottom"]) ) )-(margin.top+margin.bottom)-pixelCorrection-pixelCorrection;
			}
		else {
			var margin = {
				"left": ( tempSafety ),
				"right": ( tempSafety ),
				"top": ( tempSafety-pixelCorrection ),
				"bottom": ( tempSafety-pixelCorrection )
				}
			
			var tempWidth = parseFloat( ( ( file[0]["Width"] / 100 )*zoom ) )-(margin.left+margin.right)-1;
			var tempHeight =  parseFloat( pixel( (file[0]["Top"]-file[0]["Bottom"]) ) )-(margin.top+margin.bottom)-pixelCorrection-pixelCorrection;
			}

		jQuery('<div/>', {
			id: 'safety',
			'class': 'safetyBox',
			'standard': safety,
			text: '',
			style: 'margin-left: '+margin.left+'px; margin-right: '+margin.right+'px; margin-top: '+margin.top+'px; margin-bottom: '+margin.bottom+'px; width: '+tempWidth+'px; height: '+tempHeight+'px'
			}).appendTo('#boxDraw');
		
		if( pages > 1 ) {
			margin = {
				"left": ( parseFloat( ( ( file[0]["Width"] / 100 )*zoom ) )+tempSafety ),
				"right": tempSafety,
				"top": ( tempSafety-pixelCorrection ),
				"bottom": ( tempSafety-pixelCorrection )
				}
			tempWidth = parseFloat( ( ( file[1]["Width"] / 100 )*zoom ) )-(tempSafety+margin.right)-1;
			tempHeight =  parseFloat( pixel( file[1]["Top"]-file[1]["Bottom"] ) )-(margin.top+margin.bottom)-pixelCorrection-pixelCorrection;		
			jQuery('<div/>', {
				id: 'safety2',
				'class': 'safetyBox',
				'standard': safety,
				text: '',
				style: 'margin-left: '+margin.left+'px; margin-right: '+margin.right+'px; margin-top: '+margin.top+'px; margin-bottom: '+margin.bottom+'px; width: '+tempWidth+'px; height: '+tempHeight+'px'
				}).appendTo('#boxDraw');			
			}	
		}

	if( cBox[2] == "mediabox" ) {
		if( pages > 1 ) {
			correction = {
				"Bottom": parseFloat(trimbox[0]["Bottom"])-tempBleed-tempKifuto,
				"Left": parseFloat(trimbox[0]["Left"])-tempBleed-tempKifuto
				}
				
			margin = {
				"left": Math.floor( pixel( bleedbox[0]["Left"] - cropbox[0]["Left"] ) ),
				"right": Math.floor( pixel( ( parseFloat(file[0]["Right"])-parseFloat(bleedbox[0]["Right"]) ) ) ),
				"top": Math.floor( pixel( ( parseFloat(file[0]["Top"])+parseFloat(file[0]["Bottom"])-parseFloat(bleedbox[0]["Top"]) ) ) ),
				"bottom": Math.floor( pixel( parseFloat(bleedbox[0]["Bottom"])-parseFloat(cropbox[0]["Bottom"]) ) )
				}
			
				
			if( parseFloat( file[0]["Left"] ) < 0 ) {
				margin.left -= Math.floor( pixel( parseFloat(file[0]["Left"]) ) );
				}	

			tempWidth = Math.floor(pixel( ( parseFloat( trimbox[0]["Right"] )-parseFloat( bleedbox[0]["Left"] ) ) ) )-1;
			tempHeight = Math.floor( pixel( ( parseFloat( bleedbox[0]["Top"] )-parseFloat( bleedbox[0]["Bottom"] ) ) ) )-1;
			}
		else {
			correction = {
				"Bottom": parseFloat(trimbox[0]["Bottom"])-tempBleed-tempKifuto,
				"Left": parseFloat(trimbox[0]["Left"])-tempBleed-tempKifuto
				}
			
			margin = {
				"left": Math.floor( pixel( bleedbox[0]["Left"] - cropbox[0]["Left"] ) ),
				"right": Math.floor( pixel( ( parseFloat(file[0]["Right"])-parseFloat(bleedbox[0]["Right"]) ) ) ),
				"top": Math.floor( pixel( ( parseFloat(file[0]["Top"])+parseFloat(file[0]["Bottom"])-parseFloat(bleedbox[0]["Top"]) ) ) ),
				"bottom": Math.floor( pixel( parseFloat(bleedbox[0]["Bottom"])-parseFloat(cropbox[0]["Bottom"]) ) )
				}
			

			if( parseFloat( file[0]["Left"] ) < 0 ) {
				margin.left -= Math.floor( pixel( parseFloat(file[0]["Left"]) ) );
				}
			
			tempWidth = Math.floor(pixel( ( parseFloat( bleedbox[0]["Right"] )-parseFloat( bleedbox[0]["Left"] ) ) ) )-1;
			tempHeight = Math.floor( pixel( ( parseFloat( bleedbox[0]["Top"] )-parseFloat( bleedbox[0]["Bottom"] ) ) ) )-1;
			}	
		
		jQuery('<div/>', {
			id: 'bleed',
			'class': 'bleedBox',
			'standard': bleed,
			text: '',
			style: 'margin-left: '+margin.left+'px; margin-right: '+margin.right+'px; margin-top: '+margin.top+'px; margin-bottom: '+margin.bottom+'px; width: '+tempWidth+'px; height: '+tempHeight+'px'
			}).appendTo('#boxDraw');

		if( pages > 1 ) {
			correction = {
				"Bottom": parseFloat(trimbox[0]["Bottom"])-tempBleed-tempKifuto,
				"Left": parseFloat(trimbox[0]["Left"])-tempBleed-tempKifuto
				}
				
			margin = {
				"left": Math.floor( pixel( trimbox[0]["Left"] - cropbox[0]["Left"] ) ),
				"right": Math.floor( pixel( ( parseFloat(file[0]["Right"])-parseFloat(trimbox[0]["Right"]) ) ) ),
				"top": Math.floor( pixel( ( parseFloat(file[0]["Top"])-parseFloat(trimbox[0]["Top"])+parseFloat(cropbox[0]["Bottom"]) ) ) ),
				"bottom": Math.floor( pixel( trimbox[0]["Bottom"] - cropbox[0]["Bottom"] ) )
				}


			if( parseFloat( file[0]["Left"] ) < 0 ) {
				margin.left -= Math.floor( pixel( parseFloat(file[0]["Left"]) ) );
				}

			tempWidth = Math.floor( pixel( ( parseFloat( trimbox[0]["Right"] )-parseFloat( trimbox[0]["Left"] ) ) ) )-1;
			tempHeight = Math.floor( pixel( ( parseFloat( trimbox[0]["Top"] )-parseFloat( trimbox[0]["Bottom"] ) ) ) )-1;
			}
		else {
			correction = {
				"Bottom": parseFloat(trimbox[0]["Bottom"])-tempBleed-tempKifuto,
				"Left": parseFloat(trimbox[0]["Left"])-tempBleed-tempKifuto
				}
				
			margin = {
				"left": Math.floor( pixel( trimbox[0]["Left"] - cropbox[0]["Left"] ) ),
				"right": Math.floor( pixel( ( parseFloat(file[0]["Right"])-parseFloat(trimbox[0]["Right"]) ) ) ),
				"top": Math.floor( pixel( ( parseFloat(file[0]["Top"])-parseFloat(trimbox[0]["Top"])+parseFloat(cropbox[0]["Bottom"]) ) ) ),
				"bottom": Math.floor( pixel( trimbox[0]["Bottom"] - cropbox[0]["Bottom"] ) )
				}


			if( parseFloat( file[0]["Left"] ) < 0 ) {
				margin.left -= Math.floor( pixel( parseFloat(file[0]["Left"]) ) );
				}

			tempWidth = Math.floor( pixel( ( parseFloat( trimbox[0]["Right"] )-parseFloat( trimbox[0]["Left"] ) ) ) )-1;
			tempHeight = Math.floor( pixel( ( parseFloat( trimbox[0]["Top"] )-parseFloat( trimbox[0]["Bottom"] ) ) ) )-1;
			}
		jQuery('<div/>', {
			id: 'trim',
			'class': 'trimBox',
			text: '',
			style: 'margin-left: '+margin.left+'px; margin-right: '+margin.right+'px; margin-top: '+margin.top+'px; margin-bottom: '+margin.bottom+'px; width: '+tempWidth+'px; height: '+tempHeight+'px'
			}).appendTo('#boxDraw');
		
		if( safety_on == "1" ) {
			if( pages > 1 ) {		
				correction = {
					"Bottom": parseFloat(trimbox[0]["Bottom"])-tempBleed-tempKifuto,
					"Left": parseFloat(trimbox[0]["Left"])-tempBleed-tempKifuto
					}
				
				margin = {
					"left": ( margin.left + tempSafety ),
					"right": ( margin.right + tempSafety ),
					"top": ( margin.top+tempSafety ),
					"bottom": ( margin.bottom+tempSafety )
					}
				var tempWidth = Math.floor( pixel( ( parseFloat( trimbox[0]["Right"] )-parseFloat( trimbox[0]["Left"] ) ) ) )-(2*tempSafety)-1;
				var tempHeight =  Math.floor( pixel( ( parseFloat( trimbox[0]["Top"] )-parseFloat( trimbox[0]["Bottom"] ) ) ) )-(2*tempSafety)-1;
				}
			else {
				correction = {
					"Bottom": parseFloat(trimbox[0]["Bottom"])-tempBleed-tempKifuto,
					}
				
				margin = {
					"left": ( margin.left + tempSafety ),
					"right": ( margin.right + tempSafety ),
					"top": ( margin.top+tempSafety ),
					"bottom": ( margin.bottom+tempSafety )
					}
				var tempWidth = Math.floor( pixel( ( parseFloat( trimbox[0]["Right"] )-parseFloat( trimbox[0]["Left"] ) ) ) )-(2*tempSafety)-1;
				var tempHeight =  Math.floor( pixel( ( parseFloat( trimbox[0]["Top"] )-parseFloat( trimbox[0]["Bottom"] ) ) ) )-(2*tempSafety)-1;
				}

			jQuery('<div/>', {
				id: 'safety',
				'class': 'safetyBox',
				'standard': safety,
				text: '',
				style: 'margin-left: '+margin.left+'px; margin-right: '+margin.right+'px; margin-top: '+margin.top+'px; margin-bottom: '+margin.bottom+'px; width: '+tempWidth+'px; height: '+tempHeight+'px'
				}).appendTo('#boxDraw');
			}
		if( pages > 1 ) {
			correction = {
				"Bottom": parseFloat(trimbox[1]["Bottom"])-tempBleed-tempKifuto,
				}

			margin = {
				"left": Math.floor( pixel( trimbox[0]["Right"]-parseFloat(cropbox[0]["Left"]) ) ),
				"right": Math.floor( pixel( ( parseFloat(file[1]["Right"]) ) ) ),
				"top": Math.floor( pixel( ( parseFloat(file[1]["Top"])+parseFloat(file[1]["Bottom"])-parseFloat(bleedbox[1]["Top"]) ) ) ),
				"bottom": Math.floor( pixel( parseFloat(bleedbox[1]["Bottom"])-parseFloat(cropbox[1]["Bottom"]) ) )
				}
			tempWidth = Math.floor(pixel( ( parseFloat( bleedbox[1]["Right"] )-parseFloat( trimbox[1]["Left"] ) ) ) )-1;
			tempHeight = Math.floor( pixel( ( parseFloat( bleedbox[1]["Top"] )-parseFloat( bleedbox[1]["Bottom"] ) ) ) )-1;
			jQuery('<div/>', {
				id: 'bleed2',
				'class': 'bleedBox',
				'standard': bleed,
				text: '',
				style: 'margin-left: '+margin.left+'px; margin-right: '+margin.right+'px; margin-top: '+margin.top+'px; margin-bottom: '+margin.bottom+'px; width: '+tempWidth+'px; height: '+tempHeight+'px'
				}).appendTo('#boxDraw');

			margin = {
				"left": ( margin.left ),
				"right": ( pixel( ( parseFloat(file[1]["Right"])-parseFloat(trimbox[1]["Right"]) ) ) ),
				"top": Math.floor( pixel( ( parseFloat(file[1]["Top"])-parseFloat(trimbox[1]["Top"])+parseFloat(cropbox[1]["Bottom"]) ) ) ),
				"bottom": Math.floor( pixel( trimbox[1]["Bottom"] - cropbox[1]["Bottom"] ) )
				}
				
			tempWidth = Math.floor( pixel( ( parseFloat( trimbox[1]["Right"] )-parseFloat( trimbox[1]["Left"] ) ) ) )-1;
			tempHeight = Math.floor( pixel( ( parseFloat( trimbox[1]["Top"] )-parseFloat( trimbox[1]["Bottom"] ) ) ) )-1;
			jQuery('<div/>', {
				id: 'trim2',
				'class': 'trimBox',
				text: '',
				style: 'margin-left: '+margin.left+'px; margin-right: '+margin.right+'px; margin-top: '+margin.top+'px; margin-bottom: '+margin.bottom+'px; width: '+tempWidth+'px; height: '+tempHeight+'px'
				}).appendTo('#boxDraw');
				
			margin = {
				"left": ( margin.left + tempSafety ),
				"right": ( margin.right + tempSafety ),
				"top": ( margin.top+tempSafety ),
				"bottom": ( margin.bottom+tempSafety )
				}
			var tempWidth = Math.floor( pixel( ( parseFloat( trimbox[1]["Right"] )-parseFloat( trimbox[1]["Left"] ) ) ) )-(2*tempSafety)-1;
			var tempHeight =  Math.floor( pixel( ( parseFloat( trimbox[1]["Top"] )-parseFloat( trimbox[1]["Bottom"] ) ) ) )-(2*tempSafety)-1;
			if( safety_on == "1" ) {
				jQuery('<div/>', {
					id: 'safety2',
					'class': 'safetyBox',
					'standard': safety,
					text: '',
					style: 'margin-left: '+margin.left+'px; margin-right: '+margin.right+'px; margin-top: '+margin.top+'px; margin-bottom: '+margin.bottom+'px; width: '+tempWidth+'px; height: '+tempHeight+'px'
					}).appendTo('#boxDraw');
				}
			}
		}	

	$("#boxDraw").css( "display", boxDisplay );
	}

var scrollCorrection = {
	'left': 0,
	'top': 0
	};

function getBoxDegree( boxPosition ) {
  Math.degrees = function(radians) {
    return ( 90-( radians * ( 180 / Math.PI ) ) );
  };
   
  var C = { 'x': boxPosition.right, 'y': boxPosition.bottom };
  var A = { 'x': boxPosition.left, 'y': boxPosition.bottom };
  var B = { 'x': boxPosition.right, 'y': boxPosition.top };
  
  var a = $("#measureBox").width();
  var b = $("#measureBox").height();
  var c = Math.sqrt( (a*a)+(b*b) );
  
  var degree = Math.asin(a/c);
  return ( precise_round( Math.degrees( degree ), 2 ) )
  }

function pickerHandle( x, y ) {
	function leftPad(number, targetLength) {
		var output = number + '';
		while (output.length < targetLength) {
			output = '0' + output;
			}
		return output;
		}

	var y2 = point( y, 100 );
	y2 = (parseFloat( file[0].Top ) )-y2;
	var data = {
   		'x' : point( x, 100 )+parseFloat( file[0].Left ),
    	'y' : y2
    	};

	var correction = $(".pagePreview").position();
	if( correction.left < 0 ) {	correction.left = 0; }
	if( correction.top < 0 ) {	correction.top = 0; }
	
	if( cBox[2] != "mediabox" ) {
		if( pages == "2" ) {
			correction.top = parseFloat( correction.top );
			}
		else {
			correction.top = parseFloat( correction.top );
			}
		}
	
	
	
	var pickerBox = {
		"Left" : correction.left + relativePosition.left-9,
		"Top" : correction.top + relativePosition.top-9
		};
	
	jQuery('<div/>', {
		id: 'pickerBox',
		'class': 'pickerBox',
		style: 'display: block; left: '+pickerBox.Left+'px; top: '+pickerBox.Top+'px;',
		text: ''
		}).appendTo('#content_box');
	
	var mode = "normal";
	var cFile = file;
	
	//if( compareID == "state_a_img" || compareID == "state_b_img" ) {
	if( compareMode == "on" ) {
		if( cMode == "SideBySide" ) {
			var compareID = "";
			
			var l = $("#side_a").width();
			var r = $("#side_b").width();
			var b = $("#side_break").width();
			var minus = 100 / zoom * (l+b);
			
			if( relativePosition.left <= l ) compareID = "side_a_img";
			else if( relativePosition.left >= l+b ) compareID = "side_b_img";
			
			mode = "compare";
			
			switch( compareID ) {
				case 'side_a_img':
					cFile = compare["file"][0];
					break;
			
				case 'side_b_img':
					cFile = compare["file"][1];
					data.x = point( (x-minus), 100 )+parseFloat( file[0].Left );
					break;		
				
				case '':
					cFile = "";
				}
			}
		else {
			var compareID = "";
		
			var full = $("#left_state").width();
			var a = $("#state_a").width();
			var y = full-a;
		
			if( relativePosition.left <= full ) {
				if( relativePosition.left > y ) compareID = "state_a_img";
				if( relativePosition.left <= y ) compareID = "state_b_img";
		
				mode = "compare";
				switch( compareID ) {
					case 'state_a_img':
						cFile = compare["file"][0];
						break;
			
					case 'state_b_img':
						cFile = compare["file"][1];
						break;		
					}
				}
			else {
				var minus = $("#left_state").width();
				var minus2 = 100 / zoom * $("#left_state").width();
				full = $("#state_d").width();
				a = $("#state_c").width();
				y = full-a;

				if( ( relativePosition.left - minus ) > y ) compareID = "state_c_img";
				if( ( relativePosition.left - minus ) <= y ) compareID = "state_d_img";
		
				mode = "compare";
				switch( compareID ) {
					case 'state_c_img':
						cFile = compare["file"][3];
						break;
			
					case 'state_d_img':
						cFile = compare["file"][4];
						break;		
					}
				
				data.x = point( (x-minus2), 100 )+parseFloat( file[0].Left );
				}
			}
		}

	if( cFile != "" ) {
		$.ajax	({
			url:"engine/flatplan_ajax.php?op=colorPick&intra_user=<?= $_SESSION['intra_user'] ?>&mode="+mode,
			type: "POST",
			data: { data: data, file: cFile },
			dataType: 'json',
			success:function( data ) {
				data = data[0];
				var infoText = "<table style='width: 175px;' cellspacing='0' cellpadding='0'>";
				var pickColors = new Array();
				var sum = 0;
				var scale = 0;
				var highest = 0;
				var line = 0;
				for( var i = 0; i < data.length; i++ ) {
					if( data[i] != "" ) {
						var temp = data[i].split(" = ");
						var temp2 = temp[1].split(" ");
						var disabledColor = false;
						switch( temp[0] ) {
							case 'Cyan':
								if( colors['cyan'] == "false" ) disabledColor = true;
								break;
							case 'Magenta':
								if( colors['magenta'] == "false" ) disabledColor = true;
								break;
							case 'Yellow':
								if( colors['yellow'] == "false" ) disabledColor = true;
								break;
							case 'Black':
								if( colors['kblack'] == "false" ) disabledColor = true;
								break;
							}
						if( !disabledColor ) {
							var checkColor = temp2[1]+", "+temp2[2]+", "+temp2[3];
							var pantone = $("[defcolor='"+checkColor+"']");
							if( pantone.length > 0 ) {
								if( colors[ pantone.attr("id") ] == "false" ) {
									disabledColor = true;
									}
								}
							}
						
						if( !disabledColor ) {
							pickColors[ i ] = new Array();
							pickColors[ i ]["Name"] = temp[0];
							pickColors[ i ]["Value"] = ( Math.round( ( parseFloat( temp[1].split(" ")[0] )*100 )*100 )/100 ).toFixed(1);

							pickColors[ i ]["Color"] = "rgb( "+temp2[(temp2.length-3)]+", "+temp2[(temp2.length-2)]+", "+temp2[(temp2.length-1)]+" )";
							var temp3 = pickColors[ i ]["Value"].toString().split(".")[1];
							if( temp3 != undefined ) {
								if( highest < temp3.length ) highest = temp3.length;
								}
							sum += Math.round( ( parseFloat( temp[1].split(" ")[0] )*100 )*100 )/100;
							}
						else {
							pickColors[ i ] = new Array();
							pickColors[ i ]["Name"] = temp[0];
							pickColors[ i ]["Value"] = 0.0;
							pickColors[ i ]["Color"] = "rgb( "+temp2[(temp2.length-3)]+", "+temp2[(temp2.length-2)]+", "+temp2[(temp2.length-1)]+" )";
							}
						}
					}
				sum = ( Math.round( sum*100 )/100 ).toFixed(1);
			
				for( var i = 0; i < pickColors.length; i++ ) {
					if( line%2 != 0 ) {
						infoText += "<tr bgcolor='#404040'>";
						}
					else {
						infoText += "<tr>";
						}
					infoText += "<td><div style='float: left; margin-right: 5px; margin-top: 3px; width: 9px; height: 9px; font-size: 20px; background: "+pickColors[ i ]["Color"]+";'></div><div style='float: left; margin-top: 1px;'>"+pickColors[i]["Name"]+"&nbsp;&nbsp;</div></td><td align='right'><div style='float:right; margin-top:1px;'>"+pickColors[i]["Value"]+"</div></td><td><div style='float:left; margin-top:1px;'>&nbsp;%</div></td></tr>";
					line++
					}
			
				if( line%2 != 0 ) {
					infoText += "<tr bgcolor='#404040'>";
					}
				else {
					infoText += "<tr>";
					}		
				infoText += "<td><div style='float:left; margin-top:1px;'>TAC</div></td><td align='right'><div style='float:right; margin-top:1px;'>&nbsp;"+sum+"</div></td><td><div style='float:left; margin-top:1px;'>&nbsp;%</div></td></tr>";
			
				pickerBox = {
					'left' : pickerBox.Left+9+15,
					'top' : pickerBox.Top
					}
			
				jQuery('<div/>', {
					id: 'colorPick',
					'class': 'colorPick',
					style: 'display: none; left: '+pickerBox.left+'px; top: '+pickerBox.top+'px;',
					text: ''
					}).appendTo('#content_box');			
	  
				infoText += "</table>";
				$("#colorPick").html( infoText );
				
				$("#colorPick").show(0);
				var offset = $("#colorPick").offset();
				var oHeight = $("#colorPick").height()+offset.top;
				var widthCheck = pickerBox.left + 9 + 15 + $("#colorPick").width();
				$("#colorPick").hide(0);				

				var max =  $( '#content_box' ).offset().left+ $( '#content_box' ).width() - $("#fpPages").width() - $("#fpToolBox").width() ;		
				var sleft = ( $("#renderedIMG1").css("display") == "block") ? parseInt( $("#renderedIMG1").css("left") ) : parseInt( $("#renderedIMG2").css("left") ) ;
				widthCheck -= sleft;
				
				if( widthCheck > max ) {
					pickerBox['left'] = parseFloat( $("#pickerBox").css("left" ) ) - $("#colorPick").width() - 15;
					}
				
				if( offset.top <= $( '#content_box' ).offset().top ) {
					pickerBox['top'] = parseInt( $("#pickerBox").css("top" ) ) + $("#pickerBox").height() + 5;
					}

				if( oHeight >= parseInt( $( '#content_box' ).offset().top)+parseInt( $( '#content_box' ).height() ) ) {
					pickerBox['top'] = parseFloat( $("#pickerBox").css("top" ) ) - $("#colorPick").outerHeight();
					}	

				$("#colorPick").css("top", pickerBox['top']+"px" );
				$("#colorPick").css("left", pickerBox['left']+"px" );
				$("#colorPick").html();
				$("#colorPick").html( infoText );
			
				$("#colorPick").show( 100 );
				}
			});
		}
  }

function measureHandle() {
	var width = 100 / zoom * $("#measureBox").width();
	var height = 100 / zoom * $("#measureBox").height();

	var boxPosition = {
		'left': point( parseFloat( $("#measureBox").attr("defleft") ), 100 ),
		'top': point( parseFloat( $("#measureBox").attr("deftop") ), 100 ),
		'right': point( ( parseFloat( $("#measureBox").attr("defleft") ) + width ), 100 ),
		'bottom': point( ( parseFloat( $("#measureBox").attr("deftop") ) + height ), 100 )
		}
	
	var pageArea = defaultSizesTrim.Width * defaultSizesTrim.Height;
	var measureInfo = {
		'widthPT': boxPosition.right - boxPosition.left,
		'heightPT': boxPosition.bottom - boxPosition.top,
		'widthCM': ( boxPosition.right - boxPosition.left ) * 0.0352777778,
		'heightCM': ( boxPosition.bottom - boxPosition.top ) * 0.0352777778,
		'areaPT': ( boxPosition.right - boxPosition.left ) * ( boxPosition.bottom - boxPosition.top ),
		'areaCM': ( ( boxPosition.right - boxPosition.left ) * 0.0352777778 ) * ( ( boxPosition.bottom - boxPosition.top ) * 0.0352777778 ),
		'degree': getBoxDegree( boxPosition )
		};
	measureInfo['percent'] = measureInfo.areaPT / pageArea * 100;
	var infoText = "<table width='100%' cellspacing='0' cellpadding='0'>";
	switch( unit ) {
    case 'pt':
      infoText += "<tr><td><div class='measurediv'>"+lang["width"]+":</div></td><td align='right'><div class='measurediv'>"+precise_round( measureInfo.widthPT, 2 )+"&nbsp;</div></td><td width='10px'><div class='measurediv'>pt</div></td></tr>";
      infoText += "<tr bgcolor='#404040'><td><div class='measurediv'>"+lang["height"]+":</div></td><td align='right'><div class='measurediv'>"+precise_round( measureInfo.heightPT, 2 )+"&nbsp;</div></td><td><div class='measurediv'>pt</div></td></tr>";
      infoText += "<tr><td><div class='measurediv'>"+lang["area"]+":</td></div><td align='right'><div class='measurediv'>"+precise_round( measureInfo.areaPT, 2 )+"&nbsp;</div></td><td><div class='measurediv'>pt</div></td></tr>";
      break;
    case 'cm':
      infoText += "<tr><td><div class='measurediv'>"+lang["width"]+":</div></td><td align='right'><div class='measurediv'>"+precise_round( measureInfo.widthCM, 2 )+"&nbsp;</div></td><td width='10px'><div class='measurediv'>cm</div></td></tr>";
      infoText += "<tr bgcolor='#404040'><td><div class='measurediv'>"+lang["height"]+":</div></td><td align='right'><div class='measurediv'>"+precise_round( measureInfo.heightCM, 2 )+"&nbsp;</div></td><td><div class='measurediv'>cm</div></td></tr>";
      infoText += "<tr><td><div class='measurediv'>"+lang["area"]+":</div></td><td align='right'><div class='measurediv'>"+precise_round( measureInfo.areaCM, 2 )+"&nbsp;</div></td><td>cm<sup>2</sup></td></tr>";
      break;
    case 'mm':
      infoText += "<tr><td><div class='measurediv'>"+lang["width"]+":</div></td><td align='right'><div class='measurediv'>"+precise_round( ( measureInfo.widthCM*10 ), 2 )+"&nbsp;</div></td><td width='10px'><div class='measurediv'>mm</div></td></tr>";
      infoText += "<tr bgcolor='#404040'><td><div class='measurediv'>"+lang["height"]+":</div></td><td align='right'><div class='measurediv'>"+precise_round( ( measureInfo.heightCM*10 ), 2 )+"&nbsp;</div></td><td><div class='measurediv'>mm</div></td></tr>";
      infoText += "<tr><td><div class='measurediv'>"+lang["area"]+":</div></td><td align='right'><div class='measurediv'>"+precise_round( ( measureInfo.areaCM*10 ), 2 )+"&nbsp;</div></td><td>mm<sup style='font-size: 8px;'>2</sup></td></tr>";  
      break;
    }
	infoText += "<tr bgcolor='#404040'><td><div class='measurediv'>"+lang["angle"]+":</div></td><td align='right'><div class='measurediv'>"+measureInfo.degree+"&nbsp;</div></td><td><div class='measurediv'>°</div></td></tr>";
	infoText += "</table>";
	infoText += "<div style='padding-top: 1px; padding-left: 3px;'>"+lang['percent']+" "+precise_round( measureInfo.percent, 2 )+" "+lang['percent2']+"</div>";
	
	measureInfo['left'] = parseFloat( $("#measureBox").css("left") ) + $("#measureBox").width() + 10;
	measureInfo['top'] = parseFloat( $("#measureBox").css("top") );

	jQuery('<div/>', {
		id: 'measureInfo',
		'class': 'measureInfo',
		style: 'display: none; left: '+measureInfo['left']+'px; top: '+measureInfo['top']+'px;',
		text: ''
		}).appendTo('#content_box');			
	
	$("#measureInfo").html( infoText );
	var comp = "#content_box";
	if( $(".pagePreview").width() > $("#content_box").width() ) {
		comp = ".pagePreview";
		}
	
	if( $("#content_box").width() <= ( parseFloat( $("#measureInfo").css("left") )-$("#content_box").scrollLeft()+$("#measureInfo").width() ) ) {
		measureInfo['left'] = parseFloat( $("#measureBox").css("left") ) - $("#measureInfo").width() - 10;
		if( ( measureInfo['left']-$("#content_box").scrollLeft() ) < 0 ) {
			measureInfo['left'] = ( parseFloat( $("#measureBox").css("left") ) + ( $("#measureBox").width()/2 ) ) - ( $("#measureInfo").width()/2 );
			measureInfo['top'] = parseFloat( $("#measureBox").css("top") ) + $("#measureBox").height() + 10;
			if( $("#content_box").height() <= ( measureInfo['top']-$("#content_box").scrollTop()+$("#measureInfo").height() ) ) {
				measureInfo['top'] = parseFloat( $("#measureBox").css("top") ) - $("#measureInfo").height() - 22;
				if( ( measureInfo['top']-$("#content_box").scrollTop() ) < 0 ) {
					measureInfo['top'] = ( parseFloat( $("#measureBox").css("top") ) + ( $("#measureBox").height()/2 ) ) - ( $("#measureInfo").height()/2 );
					}
				}
			$("#measureInfo").css("top", measureInfo['top']+"px" );
			}
		$("#measureInfo").css("left", measureInfo['left']+"px" );
		$("#measureInfo").html();
		$("#measureInfo").html( infoText );
		}
	
	$("#measureInfo").show(0);
	var offset = $("#measureInfo").offset();
	var oHeight = $("#measureInfo").height()+offset.top;
	$("#measureInfo").hide(0);	
	
	if( oHeight >= parseInt( $( '#content_box' ).offset().top)+parseInt( $( '#content_box' ).height() ) ) {
		measureInfo['top'] = parseFloat( $("#measureInfo").css("top" ) ) - $("#measureInfo").outerHeight();
		$("#measureInfo").css("top", measureInfo['top']+"px" );
		}		
	
	$("#measureInfo").show( 100 );
	}
	
function magnifyHandle() {
	ajaxDisabled = false;
	$('#content_box').kinetic( 'attach' );

	var correction = $(".pagePreview").position();
	if( correction.left < 0 ) {	correction.left = 0; }
	if( correction.top < 0 ) {	correction.top = 0; }
	
	if( cBox[2] != "mediabox" ) {
		if( pages == "2" ) {
			correction.top = parseFloat( correction.top ) + pixel( parseFloat( cBox[1].Bottom ) );
			}
		else {
			correction.top = parseFloat( correction.top ) + pixel( parseFloat( cBox[0].Bottom ) );
			}
		}
	var pageSizes = {
		'width': pixel( defaultSizes.Width, 100 ),
		'height': pixel( defaultSizes.Height, 100 ),
		};

	var magniBoxSizes = {
		'Width' :  parseFloat( $("#magniBox").css("width") ),
		'Height' : parseFloat( $("#magniBox").css("height") )
		};
		
	zoomCalc( magniBoxSizes );
	placeBox( 'force', 'magnify' );
	$("#magniBox").remove();
	}

function alterZoom( div ) {
	var correction = $(".pagePreview").position();
	previewPic = {
		Width: $( '.pagePreview' ).width(),
		Height: $( '.pagePreview' ).height()
		};
	zoom_ = zoom;
	
	if( correction.left < 0 ) {	correction.left = 0; }
	if( correction.top < 0 ) {	correction.top = 0; }
	
	if( $("#"+div).length == 0 ) {
		jQuery('<div/>', {
			id: div,
			'class': 'commentDraw , '+graphState,
			style: 'width: 0px; height: 0px; left: '+(correction.left + relativePosition.left)+'px; top: '+(correction.top + relativePosition.top)+'px;',
			text: ''
			}).appendTo('#commenDraw');
		}
	else {
		$("#"+div).css({
			'width': '0px',
			'height': '0px',
			'left': (correction.left + relativePosition.left)+'px',
			'top': (correction.top + relativePosition.top)+'px'
			});
		}
	activeDiv = div;
	
	var defLeft = 100/ zoom_ * relativePosition.left;
	var defTop = 100/ zoom_ * relativePosition.top;
	
	$('#'+div).attr( "defLeft", defLeft );
	$('#'+div).attr( "defTop", defTop );
	$('#'+div).attr( "shape", graphState );

	activeDivPos = {
		left: (correction.left + relativePosition.left),
		top: (correction.top + relativePosition.top)
		};
	}

function removeAdvancedTool( tool ) {
	if( tool == undefined ) tool = "";

	var s = false;
	document.getElementById("content_box").style.cursor = 'default';
	$('.active').each(function() {
		$(this).removeClass( 'active' );
		if( $(this).attr("id") == tool ) {
			s = true;
			}
		$(this).attr("src", "plugins/images/"+$(this).attr('id')+".png" );
		switch( $(this).attr('id') ) {
			case 'measure':
				$("#measureBox").remove();
				$("#measureInfo").remove();
				break;
			case 'colorPicker':
        $("#colorPick").remove();
        $("#pickerBox").remove();
        break;
			}
		});
	ajaxDisabled = false;
	
	return s;
	}

function advancedTool( tool ) {
	var same = removeAdvancedTool(tool);
	
	$('.activeGraph').each(function() {
		$(this).removeClass( 'activePanel' );
		$(this).removeClass( 'activeGraph' );
		$(this).attr('src', "plugins/images/"+$(this).attr('id')+".png");
		});
	
	if( same ) {
		document.getElementById("content_box").style.cursor = "";
		disableZoom = false;
		graphState = false;
		$('#content_box').kinetic( 'attach' );
		}		
	else {
		disableZoom = true;
		ajaxDisabled = true;
		$('#content_box').kinetic( 'detach' );
		switch( tool ) {
			case 'measure':
				graphState = "measure";
				break;
			case 'magnify':
				graphState = "magnify";
				$( "#zoomRange" ).slider( "option", { disabled: false } );
				break;
			case 'colorPicker':
        		graphState = "colorPicker";
        		break;
			}
		$( "#"+tool ).addClass("active");
		document.getElementById("content_box").style.cursor = "url('plugins/images/cur_"+tool+".png'), auto";
		$( "#"+tool ).attr("src", "plugins/images/"+tool+"On.png" );
		}
	}

function toggleBox( img ) {
	removeAdvancedTool();
	$('.activePanel').each(function() {
		$(this).removeClass( 'activePanel' );
		});
	$('.activeGraph').each(function() {
		$(this).removeClass( 'activePanel' );
		$(this).removeClass( 'activeGraph' );
		$(this).attr('src', "plugins/images/"+$(this).attr('id')+".png");
		});		
	setState( "" );
	var switchTo = "";
	var current = $('img', img).attr( 'src' ).split("/").pop(-1).split(".")[0];
	switch( current ) {
		case 'mediabox':
			switchTo = 'trimbox';
			break;
		case 'trimbox':
			switchTo = 'mediabox';
			break;
		}
	removeAdvancedTool();
	reloadBG( switchTo, 'changePic' );
	}

function toggleTool( tool ) {
	removeAdvancedTool();
	var source = 'changePic';
	$('.activeGraph').each(function() {
		$(this).removeClass( 'activeGraph' );
		$(this).removeClass( 'activePanel' );
		$(this).attr('src', "plugins/images/"+$(this).attr('id')+".png");
		});		
	
	setState( "" );
	
	if( mobile ) {
		var target = $(".mobileToolBox[data-tool='"+tool+"']");
		target.hide(0);
		var next = target.attr("data-next");
		$(".mobileToolBox[data-tool='"+next+"']").show(0);
		}	
	
	if( tool == "widePage" ) {
		source = "widePage";
		placeBox('force', 'roll', source );
		}
	else {	
		$('.pagePreview').fadeOut( 200,function(){
			$("#renderedIMG1").hide( 0 ).attr('src', '' );
			$("#renderedIMG2").hide( 0 ).attr('src', '' );
			$("#renderedSRC1").attr('src', '' );
			$("#renderedSRC2").attr('src', '' );	
			var data = "";
			
			if( mobile ) {
				switch( tool ) {
					case 'single':
						data = "fpPages="+tool;
						break;
						
					case 'pair':
						data = "fpPages="+tool;
						break;
						
					case 'widePage':
						
						break;
					}
				}
			else {
				var pic = $( "#"+tool ).attr("src").split("/").pop(-1).split(".")[0];
				switch( pic ) {
					case tool:
						if( tool == "single" ) {
							$( "#"+tool ).attr("src", "plugins/images/"+tool+"On.png" );
							$( "#pair" ).attr("src", "plugins/images/pair.png" );
							data = "fpPages="+tool;
							}
						else if( tool == "pair" ) {
							$( "#"+tool ).attr("src", "plugins/images/"+tool+"On.png" );
							$( "#single" ).attr("src", "plugins/images/single.png" );
							data = "fpPages="+tool;
							}
						else {
							$( "#"+tool ).attr("src", "plugins/images/"+tool+"On.png" );
							}
						break;
				
					case tool+"On":
						data = "fpPages="+tool;
						break;
				
					default:
						if( tool != "single" && tool != "pair" )
							$( "#"+tool ).attr("src", "plugins/images/"+tool+".png" );
						break;
					}
				}

			$.ajax	({
				url:"engine/flatplan_ajax.php?op=saveTool&intra_user=<?= $_SESSION['intra_user'] ?>",
				type: "POST",
				data: { data: data },
				dataType: 'json',
				success:function( data ) {
					if( data == "success" ) {
						zoomCalc();
						reloadBG( undefined, source );
						}
					}
				});
			});
		}
	}

function changeKey(originalKey, newKey, arr) {
  var newArr = [];
  for(var i = 0; i < arr.length; i++) {
    var obj = arr[i];
    obj[newKey] = obj[originalKey];
    delete(obj[originalKey]);
    newArr.push(obj);
    }
    
  return newArr;
  }

function togglePantone( id ) {
	removeAdvancedTool();
	if( !ajaxDisabled ) {
    	var current = $("#"+id+"_normal > img").attr("src").split("/")[2].split(".")[0];
    	switch( current ) {
			case 'emptyColor':
				$("#"+id+"_normal > img").attr("src", "plugins/images/emptyColorOff.png");
				$("#"+id+"_hover > img").attr("src", "plugins/images/emptyColor_offhover.png");
				colors[ id ] = "false";
				break;
			case 'emptyColorOff':
				$("#"+id+"_normal > img").attr("src", "plugins/images/emptyColor.png");
				$("#"+id+"_hover > img").attr("src", "plugins/images/emptyColor_hover.png");
				colors[ id ] = "true";
				break;
		  }
	  
		$(".pagePreview").css( 'background-image', 'url()' );
		disableZoom = true;
 		$.ajax	({
			url:"engine/flatplan_ajax.php?op=reloadbg&intra_user=<?= $_SESSION['intra_user'] ?>&id="+pub+"&pack_id="+pack_id+"&p="+page+"&alter="+PageType,
			type: "POST",
			data: { colors: colors },
			dataType: 'json',
			success:function( data ) {
				$(".pagePreview").css( 'background-image', 'url('+data[0]+')' );
				}
			});
		setTimeout( function(){ placeBox('force'); }, 10 );
		}
	}

function toggleColor( color ) {
	removeAdvancedTool();
	if( !ajaxDisabled ) {
		switch( colors[ color ] ) {
			case 'true':
				colors[ color ] = "false";
				$( "#"+color+".normal" ).attr( "src", "plugins/images/"+color+"Off.png" );
				$( "#"+color+".hover" ).attr( "src", "plugins/images/"+color+"_offhover.png" );
				break;
			case 'false':
				colors[ color ] = "true";
				$( "#"+color+".normal" ).attr( "src", "plugins/images/"+color+"On.png" );
				$( "#"+color+".hover" ).attr( "src", "plugins/images/"+color+"_hover.png" );
				break;
			}

  		$(".pagePreview").css( 'background-image', 'url()' );
  		disableZoom = true;
		$.ajax	({
			url:"engine/flatplan_ajax.php?op=reloadbg&intra_user=<?= $_SESSION['intra_user'] ?>&id="+pub+"&pack_id="+pack_id+"&p="+page+"&alter="+PageType,
			type: "POST",
			data: { colors: colors },
			dataType: 'json',
			success:function( data ) {
				$(".pagePreview").css( 'background-image', 'url('+data[0]+')' );
				}
			});
		setTimeout( function(){
			placeBox('force');
			if( compareMode == "on" ) {
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
			}, 10 );
		}
	}
</script>