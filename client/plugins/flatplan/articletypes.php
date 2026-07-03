<?php

//$article = sql_aget( "flatplan_planner", "id='".$_GET["data"]."'", "*" );
//$pub = sql_aget( "publications", "id='".$article[0]["pub_id"]."'", "*" );

?>
<link rel="stylesheet" media="screen" type="text/css" href="css/colorpicker.css" />
<script type="text/javascript" src="js/colorpicker.js"></script>

<form id='subForm2' method='post' action=''>
<input type="hidden" id="pid" name="pid" value="<?= $_GET["data"]; ?>">
<input type="hidden" id="articleColor" name="articleColor" value="000000">
<div>
	<div class='panelTitle'>Define Article Type</div>
	<div class='panelControl' style='width: 360px;'>
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tr>
				<td>
					<table cellspacing='0' cellpadding='0' style="width: 100%;">
						<thead>
							<tr>
								<td width="43%">Name</td>
								<td width="20%">Color</td>
								<td width="20%">Time/page</td>
								<td width="17%">&nbsp;</td>
							</tr>
						</thead>
						<tbody id="articleTypes"></tbody>
						<tfoot>
							<tr id="newArticleTypeRow" style="display: none;">
								<td><input type="text" name="name" id="name" style="width: 90%;"></td>
								<td><div class='articlecolorBox' id='new_color'></div></td>
								<td>
									<select name="articleTime" id="articleTime">
										<option value="15">15 mins</option>
										<option value="30">30 mins</option>
										<option value="45">45 mins</option>
										<option value="60">60 mins</option>
										<option value="75">75 mins</option>
										<option value="90">90 mins</option>
									</select>
								</td>
								<td>
									<i onclick="menuApply( 'flatplan', 'articletypes', undefined )" class="far fa-check-circle" style="cursor: pointer; font-size: 19px; color: #21ed43;"></i>
									<i onclick="hideAdd()" class="far fa-times-circle" style="cursor: pointer; font-size: 19px; color: #D22A33;"></i>
								</td>
							</tr>
							
							<tr>
								<td colspan="4" align="left" style="padding-top: 5px;">
									<i onclick="showAdd()" class="fas fa-plus-circle" style="cursor: pointer; font-size: 19px; color: #21ed43;"></i>
								</td>
							</tr>					
						</tfoot>
					</table>
				</td>	
			</tr>
			<tr>
				<td colspan="2" align="center" style="padding-top: 20px;">
					<div onclick="closePanel( 'flatplan_articletypes', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'flatplan', 'articletypes', 'close' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>								
		</table>
	</div>	
</div>		
		
</form>

<script>
var colorField = "";
var color_hex = "";

function removeAtype( id, name ) {
	if( confirm( "Are you sure you want to remove the "+name+" article type?" ) ) {
		$.ajax	({
			url:"engine/flatplan_ajax.php?op=removeatype&id="+id,
			type: "GET",
			dataType: 'json',
			success:function( data ) {
				loadArticles();
				loadTypes();
				}
			});		
		}
	}

function modAtype( id ) {	
	var data = {
		"id" : id,
		"color" : rgb2hex( $("#"+id+"_cbox").css("background-color") ),
		"time" :$("#"+id+"_time").val(),
		}
		
	$.ajax	({
		url:"engine/flatplan_ajax.php?op=modtypes",
		type: "POST",
		data: { data : data },
		dataType: 'json',
		success:function( data ) {
			loadArticles();
			$("#"+id+"_save").animate({color: '#FFFFFF'}, 150, function() {
				$("#"+id+"_save").animate({color: '#21ed43'}, 150 );
				});
			}
		});
	}

function loadTypes() {
	$.ajax	({
		url:"engine/flatplan_ajax.php?op=loadtypes&pid=<?= $_GET["data"] ?>",
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#articleTypes").html( data );
			
			$('.articlecolorBox').off();
			$('.articlecolorBox').ColorPicker({
				onBeforeShow: function () {
					colorField = this;
					$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
					},
				onShow: function (colpkr) {
					$(colpkr).fadeIn(500);
					
					return false;
					},		
				onHide: function (colpkr) {
					$(colpkr).fadeOut(500);
					return false;
					},			
				onChange: function (hsb, hex, rgb) {
					//saveColor( color_hex, magazineID );
					if( $(colorField).hasClass("alreadyin") === false ) {
						$("#articleColor").val( hex );
						}
						
					$(colorField).css('background-color', '#' + hex);
					}			
				});			
			}
		});
	}
loadTypes();

function hideAdd() {
	$("#newArticleTypeRow").css("display", "none");
	$("#name").val("");
	$(".articlecolorBox").css("background", "#000000");
	$("#articleTime").val( "15" );
	}

function showAdd() {
	$("#newArticleTypeRow").css("display", "table-row");
	}

function rgb2hex(rgb) {
    if (/^#[0-9A-F]{6}$/i.test(rgb)) return rgb;

    rgb = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
    function hex(x) {
        return ("0" + parseInt(x).toString(16)).slice(-2);
    }
    return hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]);
}

</script>