<link rel="stylesheet" media="screen" type="text/css" href="css/colorpicker.css" />
<script type="text/javascript" src="js/colorpicker.js"></script>

<?php	
$page = $_POST["pageselector"][0];
$articles = sql_aget("flatplan_planner", "pub_id='".$_GET["pubid"]."' AND pos='".$page."' order by pos ASC", "*" );
//$articles = sql_aget("flatplan_planner", "pub_id='".$_GET["pubid"]."' AND name='".$articles[0]["name"]."' order by pos ASC", "*" );
$cikk = sql_aget( "flatplan_articletypes", "id='".$articles[0]["atype"]."'", "*" );

$pub = sql_aget("publications", "id='".$_GET["pubid"]."'", "*" );

$start = $articles[0]["pos"];
$end = $articles[ (count($articles)-1) ]["pos"];

if( $_GET["data"] == "create" ) {
	$start = $_POST["pageselector"][0];
	$end = end( $_POST["pageselector"] );
	
	$slots = $start."-".$end;
	}

if( $_GET["data"] == "modify" ) {
	$slots = implode( "|", $_POST["pageselector"] );
	}

$time = $cikk[0]["time"] * ( $end - $start + 1 );
$fpname = $articles[0]["name"];

if( $articles[0]["mixed"] == "1" ) {
	include( "modify-mixed.php" );
	}
else {
	include( "modify-simple.php" );
	}
?>



<script>
function mixedModify() {
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php?op=mixedModify&id=<?= $articles[0]["id"] ?>",
		type: "POST",
		data: {}, 
		dataType: 'json',
		success:function( data ) {
			console.log( "mixed context vissza");
			$("#detailPanels").html( data[1] );
			$("#mixedSelected_content").html( data[0] );
			
			$(".content_pages").html( "<?= $articles[0]["pos"] ?>" );
			
			$("#mixedWindow").hide(0);
			$("#mixedSelectedWindow").show(0);	
	
			var divid = "planner_modify";
			$("#"+divid).show(0);
			var oHeight = 390;
			var oWidth = 466;
	
			var left = ( $(window).width() / 2 )-( oWidth/2 );
			var top = ( $(window).height() / 2 )-( oHeight/2 );

			$("#"+divid).css({
				"left": left+"px",
				"top": top+"px"
				});
			
			$("#mixedSelected_content .mix_field").each(function(i){
				drawPartSettings( $(this), i );		
				});
			
			}
		});	
	}	
<?php 
if( $articles[0]["mixed"] == "1" ) {
	echo 'mixedModify();';
	} 
?>

	
function mixedSave() {
	var datas = new Array();
	var i = 0;
	$(".detailWindow").each( function() {
		var temp = $(this).find("form").serialize();
		temp += "&m_content_type="+$("#"+i+"_type").val();
		datas.push( temp );
		i += 1;
		});
	
	$.ajax	({
		url:"plugins/plannerApply.php?sub=saveMixed",
		type: "POST",
		data: { settings: datas },
		dataType: 'json',
		success:function( data ) {	
			$("#mixedSelectedWindow").hide(1000);
			$("#planner_modify").hide(100);
			}
		});
	}	
	
function closeMixedSettings( i ) {
	$("#planner_modify").show(100);
	$("."+i+"_window").hide( 100 );
	}	
	
function loadDetail( i ) {
	setDivCenter2( $("."+i+"_window") );
	
	$("."+i+"_window").show( 100 );
	$("#planner_modify").hide(100);
	}		
	
function mixedSelect() {
	var form = $("#subForm3").serialize();
	
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php?op=mixedSelect&layout="+$(".mixedLayout_radio:checked").val(),
		type: "POST",
		data: { data : form }, 
		dataType: 'json',
		success:function( data ) {
			$("#mixedSelectedWindow").show(0);
			$("#mixedWindow").hide(0);
			
			$("#detailPanels").html( data[1] );
			$("#mixedSelected_content").html( data[0] );
			
			$("#mixedSelected_content .mix_field").each(function(i){
				drawPartSettings( $(this), i );		
				});
			
			}
		});	
	}	
	
$("#mixed_parts").change( function() {
	loadLayout();
	});	
	
function loadLayout() {
	$.ajax	({
		url:"engine/flatplan_planner_ajax.php?op=loadlayout&parts="+$("#mixed_parts").val(),
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#mixed_layouts").html(data);
			
			$(".mixedLayout_radio:first").attr('checked', true);
			}
		});
	}	
loadLayout();
	
function returnWindow( type ) {
	switch( type ) {
		case "mixedWindow":
			$("#mainWindow").show(0);
			$("#mixedWindow").hide(0);
			break;
			
		case "mixedSelectedWindow":
			$("#mixedWindow").show(0);
			$("#mixedSelectedWindow").hide(0);		
			break;
		}	
	}	
	
$("#content_type").change( function() {
	if( $(this).val() == "mixed" ) {
		$("#mainWindow").hide(0);
		$("#mixedWindow").show(0);
		
		var pages = $("#slots").val();
		pages = pages.split("-");
		if( pages[0] == pages[1] ) {
			var page = pages[0];
			}
		else {
			var page = $("#slots").val();
			}
		
		$(".content_pages").html( page );
		}
		
	else {
		$(".planner_table_row").show(0);
		$("#assets").show(0);
		$("#assetsTable").show(0);
		$("#atype_box").show(0);
		$("#workerID_box").attr("colspan","4");
		
		if( $(this).val() == "promo" ) {
			$("#atype_box").hide(0);
			$("#workerID_box").attr("colspan","10");
			}
		
		if( $(this).val() == "ad" ) {
			$(".planner_row_3").hide(0);
			$(".planner_row_4").hide(0);
			$(".planner_row_5").hide(0);
			$(".planner_row_6").hide(0);
			$(".planner_row_7").hide(0);
			$(".planner_row_8").hide(0);
			$("#assets").hide(0);
			$("#assetsTable").hide(0);
			}
		}
	});
$("#content_type").change();
window.parent.frames[0].currentPlannerID = "<?= $articles[0]["id"] ?>";
	
function fpfileremove( id, name ) {
	if( confirm( "Are you sure you want to remove the following file: "+name ) ) {
		$.ajax	({
			url:"engine/fileupload.php?op=removefile&id="+id,
			type: "GET",
			dataType: 'json',
			success:function( data ) {
				loadUploadedFiles( "<?= $articles[0]["id"] ?>" );
				}
			});
		}
	}

function checkAvailable( type ) {
	var checked = $("#r_"+type).is(':checked');
	
	if( checked ) {
		$("#a_"+type).css("display","table-cell");
		}
	else {
		$("#a_"+type).css("display","none");
		}
	}
checkAvailable( "text" );
checkAvailable( "image" );
checkAvailable( "other" );
	
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
$('.colorBox').ColorPicker({
	onBeforeShow: function () {
		colorField = this;
		$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
	},
	onShow: function (colpkr) {
		$(colpkr).fadeIn(100);
		return false;
	},
	onHide: function (colpkr) {
		$(colpkr).fadeOut(100);
		return false;
	},
	onChange: function (hsb, hex, rgb) {	
		color_hex = hex;
		$(colorField).css('background-color', '#' + hex);
		$("#thumbColor").val( hex );
	}
})
.bind('click', function(){
	$(this).ColorPickerSetColor( rgb2hex( $(this).css("background-color") ) );
	});
	
function modifyArticle() {
	var cbox = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).val() );
		}); 
		
	$("#plannersave").addClass("btn-disabled");	
	console.log( $("#subForm3").serialize() );
	$.ajax	({
		url:"plugins/plannerApply.php?sub=modArticle",
		type: "POST",
		data: { data: cbox, settings: $("#subForm3").serialize() },
		dataType: 'json',
		success:function( data ) {	
			console.log( "ajax end");
			$("#plannersave").removeClass("btn-disabled");
			if( data[0].length > 0 ) {
				for( var i = 0; i < data[0].length; i++ ) {
					$("#"+data[0][i]).css("background", "#D14550" );
					}
				}
			if( data[0] == "" ) {
				$("#planner_modify").hide(200);
				}
			
			loadArticles();		
			}
		});		
	}	
	
</script>