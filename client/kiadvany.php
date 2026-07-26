<table width='100%' id='job_names' class='pub_listing' cellspacing='0' rowspacing='0' style="display: table !important;">
	<tbody>
		
	</tbody>
</table>

<script>
var def_pubs = '';

//alert( $("#pub_table_wrapper" ).css("display")  );

function jlineHover(e, event) {
	var row = e.attr("row");
	//var showRow = new Array( (parseInt(row)-1), (parseInt(row)+1) );
	if( row > 1 ) {
		var showRow = parseInt(row)-1;
		}
	else {
		var showRow = parseInt(row)+1;
		}
	
	if( $("#row_"+showRow ).parent("div").parent("div").length > 0 ) {
		var i = $("#row_"+showRow).parent("div").parent("div").children(".jline").size();
		if( $("#row_"+showRow).parent("div").parent("div").css("display") == "none" ) {
			if( row > 1 ) {
				showRow -= i;
				}
			else {
				showRow += i;
				}
			}
		}
	
	$("#row_"+showRow ).children(".tlBox").children(".dateNum").each(function(){
		switch( event ) {
			case 'enter':
				$(this).children(".pubTlNum").css("display","block");
				break;
			case 'leave':
				$(this).children(".pubTlNum").css("display","none");
				break;
			}
		});

	}

function magSettingsMenu( info ) {
	id = "line_"+info[1]+"_"+info[2];

	if( $("#"+id+"Float").css("display") == "none" || $("#"+id+"Float").length == 0 ) {
		$.ajax	({
			url:"engine/menuAjax.php?op=generatemagmenu",
			type: "POST",
			data: { info: info },
			dataType: 'json',
			success:function( data ) {
				jQuery('<div/>', {
    				id: id+"Float",
    				class: "floatMenu",
    				style: "position: absolute; text-align: left; display:none; left: 18px; top:23px;"
					}).appendTo( "#line_"+info[1]+"_"+info[2] );
				
				$("#"+id+"Float").html( data );
				
				$(".settingsPanel").hide(200, function(){
					$(this).remove();
					});
				
				setDivPos( id+"Float" );
				$("#"+id+"Float").show(200);
				
				
				$( "#"+id+"Float" ).draggable({ cursor: "move" });
				}
			});
		}
	else {
		$("#"+id+"Float").hide(200);
		}
	}

function issueManagement( op, id, divid ) {
	// The dropdown used to only close inside each ajax call's success
	// handler - fine as long as the request always comes back clean, but
	// a server-side error on the way (a PHP fatal, a slow/blocked Switch
	// call, anything that keeps success from ever firing) left the menu
	// stuck open with no way to dismiss it. A context menu should close
	// the moment its action is chosen regardless of how that action's own
	// request turns out, so it's closed synchronously up front instead -
	// each branch below still fires its own ajax call same as before,
	// just no longer waits on it to dismiss the menu.
	if( op == "deleteIssue" ) {
		var issue = divid.replace('Float','');
		//issue = issue.replace('_',' ');
		//var text = "Are you sure you want to remove the "+issue+" Issue?";
		var text = "<?= $lang['publications']['remove_issue'] ?>";
		text = text.replace( "[MEGJELENES]", issue );

		if( confirm( text ) ) {
			$("#"+divid).hide(200,function(){
				$("#"+divid).remove();
				});
			var iHTML = $("#"+id+"_status").html();
			$("#"+id+"_status").html( iHTML+'<div style="float: right; margin-left: 15px; margin-top: 5px; height:1px;"><div id="floatingBarsG"><div class="blockG" id="rotateG_01"></div><div class="blockG" id="rotateG_02"></div><div class="blockG" id="rotateG_03"></div><div class="blockG" id="rotateG_04"></div><div class="blockG" id="rotateG_05"></div><div class="blockG" id="rotateG_06"></div><div class="blockG" id="rotateG_07"></div><div class="blockG" id="rotateG_08"></div></div></div>' );
			$.ajax	({
				url:"engine/issueManagementAjax.php",
				type: "GET",
				data: 'op='+op+'&id='+id,
				dataType: 'json'
				});
			}
		}

	else if( op == "approveIssue" ) {
		var issue = divid.replace('Float','');
		//var text = "Are you sure you want to approve the "+issue+" Issue?";
		var text = "<?= $lang['publications']['approve_issue'] ?>";
		text = text.replace( "[MEGJELENES]", issue );

		if( confirm( text ) ) {
			$("#"+divid).hide(200,function(){
				$("#"+divid).remove();
				});
			$.ajax	({
				url:"engine/issueManagementAjax.php",
				type: "GET",
				data: 'op='+op+'&id='+id,
				dataType: 'json'
				});
			}
		}

	else {
		$("#"+divid).hide(200,function(){
			$("#"+divid).remove();
			});
		$.ajax	({
			url:"engine/issueManagementAjax.php",
			type: "GET",
			data: 'op='+op+'&id='+id,
			dataType: 'json'
			});
		}
	}

function sendForm( id ) {
	if( id == 'mag_settings' ) {
		$( "#result" ).html( '<img src="images/ajax-loader.gif">' );
		}
	if( id == 'mag_colors' ) {
		$( "#result2" ).html( '<img src="images/ajax-loader.gif">' );
		}
	if( id == 'mag_ftp' ) {
		$( "#result3" ).html( '<img src="images/ajax-loader.gif">' );
		}
	if( id == 'modIssue' ) {
		$( "#result" ).html( '<img src="images/ajax-loader.gif">' );
		}
	
	$('<input>').attr({
    	type: 'hidden',
    	name: 'counter_parts',
    	value:  $("input[name$='_name']").length
	}).appendTo('#'+id);
	
	$.ajax	({
		url:"engine/sendFormAjax.php",
		type: "POST",
		data: $("#"+id).serialize(),
		dataType: 'json',
		success:function( data ) {
			$("input").each(function(){
				$(this).css({ background: '#FFF', color: '#000' });
				});
			if( $.isArray( data ) ) {
				for( var i = 0; i <data.length; i++ ) {
					$("input[name="+data[i]+"]").css({ background: '#DB2121', color: '#FFF' });
					}
				}
			else if( data == 'ok' ) {
				hide_pubs();
				}
			else {
				var ret = data.split("_");
				$( "#"+ret[1] ).html( ret[0] );
				}
			}
		});
	}

function toggleDeadline() {
	var width = $( window ).width();
	var temp = 0;
	$(".pub_info").each(function() {
		if( temp < $(this).outerWidth(true) ) {
			temp = $(this).outerWidth(true);
			}
		});
	var ttemp = 0;
	$(".timerow").first().children(".tlBox").each(function(){
		ttemp += $(this).width();
		});
	
	width -= temp+ttemp;
	var placeNeed = $(".pubDeadline").first().outerWidth(true);
	if( width > placeNeed ) {
		var display = "inline";
		}
	else {
		var display = "none";
		}
		
	$(".pubDeadline").each(function(){
		$(this).css("display", display );
		});	
	}
	
$(window).resize(function(){
	//toggleDeadline();
	});

var toggled = [];

$("select[name='menuClient']").change(function(){
	load_pubs_asap();
	});

function load_pubs_asap() {

	console.log( toggled );
	$.ajax	({
		url:"engine/pubLoad.php",
		type: "GET",
		data: 'op=load_publications&mc='+$("select[name='menuClient']").val(),
		dataType: 'json',
		success:function( data ) {
			if( data != def_pubs ) {
				def_pubs = data;
				$('.pub_listing tbody').empty();
				$('.pub_listing tbody').append( data );
				
				console.log( toggled );
				$.each( toggled, function( key, value ) {
					$( "#"+value ).css("display", "block" );
					});
				
				if( $("#pub_table_wrapper2").css('display') == 'block' ) {
					var current_row = $("#pub_id").attr("pub_id");
					$(".pub_button").each(function(){
						$(this).hide(0);
						});
					$( "#"+current_row+" > td " ).css( 'background', '#FAF2C5' );
					}
				
				$(".jline").mouseenter(function(e){
					jlineHover( $(this), "enter" );
					});
				$(".jline").mouseleave(function(e){
					jlineHover( $(this), "leave" );
					});
					
				$("#debug").html( data );
				}

			//toggleDeadline();	
			}
		});
	}
	
function load_pubs() {
	console.log( toggled );
	$.ajax	({
		url:"engine/pubLoad.php",
		type: "GET",
		data: 'op=load_publications&mc='+$("select[name='menuClient']").val(),
		dataType: 'json',
		success:function( data ) {
			if( data != def_pubs ) {
				def_pubs = data;
				$('.pub_listing tbody').empty();
				$('.pub_listing tbody').append( data );
				
				console.log( toggled );
				$.each( toggled, function( key, value ) {
					$( "#"+value ).css("display", "block" );
					});
				
				if( $("#pub_table_wrapper2").css('display') == 'block' ) {
					var current_row = $("#pub_id").attr("pub_id");
					$(".pub_button").each(function(){
						$(this).hide(0);
						});
					$( "#"+current_row+" > td " ).css( 'background', '#FAF2C5' );
					}
				
				$(".jline").mouseenter(function(e){
					jlineHover( $(this), "enter" );
					});
				$(".jline").mouseleave(function(e){
					jlineHover( $(this), "leave" );
					});
					
				$("#debug").html( data );
				}

			//toggleDeadline();
			setTimeout(function(){ load_pubs(); }, 2000);	
			}
		});
	}
	
load_pubs();

function load_ads() {
	$.ajax	({
		url:"engine/ajax.php",
		type: "GET",
		data: 'op=load_ads&code='+$("[name='code']").val(),
		dataType: 'json',
		success:function( data ) {
			$("#ad_lst").find("tr:gt(0)").remove();
			$('#ad_lst tr:first').after( data );
			}
		});
	}

function remove_ad( nr ) {
	$.ajax	({
		url:"engine/ajax.php",
		type: "GET",
		data: 'op=remove_ad&id='+nr,
		dataType: 'json',
		success:function( data ) {
			load_ads();
			}
		});		
	}

function add_advert() {
	var res = "go";
	
	if( $('#width').val() == "" ) {
		res = "error";
		$('#width').css("background-color", "rgb(209, 69, 80)");
		}
	else {
		$('#width').css("background-color", "white");
		}

	if( $('#height').val() == "" ) {
		res = "error";
		$('#height').css("background-color", "rgb(209, 69, 80)");
		}
	else {
		$('#height').css("background-color", "white");
		}
	
	if( res == "go" ) {
		console.log( 'op=add_ad&code='+$("[name='code']").val()+'&size='+$('#size').val()+'&orient='+$('#orient').val()+'&cover='+$('#cover').val()+'&width='+$('#width').val()+'&height='+$('#height').val() );
		$("#result4").html( '<img src="images/ajax-loader.gif">' );
		$.ajax	({
			url:"engine/ajax.php",
			type: "GET",
			data: 'op=add_ad&code='+$("[name='code']").val()+'&size='+$('#size').val()+'&orient='+$('#orient').val()+'&cover='+$('#cover').val()+'&width='+$('#width').val()+'&height='+$('#height').val(),
			dataType: 'json',
			success:function( data ) {
				if( data = 'ok' ) {
					$("#result4").html( 'A méret hozzáadva' );
					load_ads();
					
					}
				}
			});	
		}
	}
	
function publication_change() {
	$('div.ui-datepicker').css({ fontSize: '12px' });
	
	$('.target').keyup( function() {
		create_preview( 'i_', 'preview1' );
		});	
	$('.target').change( function() {
		create_preview( 'i_', 'preview1' );
		});
	$('.target2').keyup( function() {
		create_preview( 'u_', 'preview2' );
		});
	$('.target2').change( function() {
		create_preview( 'u_', 'preview2' );
		});
	$('.target3').keyup( function() {
		create_preview( 'o_', 'preview3' );
		});
	$('.target3').change( function() {
		create_preview( 'o_', 'preview3' );
		});

	$('#page_nr').change(function(){
		if( parseInt( $('#page_nr').val() )%2 != 0 ) {
			if( $('#page_nr').val() != '' ) {
				$('#page_nr').val(parseInt( $('#page_nr').val())+1);
				}
			}

		$('#page_nr').keyup();
		});

	$('#page_nr').keyup(function(){
		var value = '1-2, '+(parseInt( $('#page_nr').val() )-1)+'-'+(parseInt( $('#page_nr').val() ));
		if( $('#page_nr').val() != '' ) {
			$('#part1_place').val( value );
			}
		else {
			$('#part1_place').val( '' );
			}

		value = '3-'+(parseInt( $('#page_nr').val() )-2);
		if( $('#page_nr').val() != '' ) {
			$('#part2_place').val( value );
			}
		else {
			$('#part2_place').val( '' );
			}	
		});

	$('#settings :input').change(function(){
		var counter = $('#settings input[value!=""]').length+$('#part_list input[value!=""]').length;
		if( counter == ( $('#settings input').length+$('#part_list input').length) ) {
			$("#create").removeAttr("disabled");
			}
		else {
			  $("#create").attr("disabled", "disabled");
			}
		});

	$('#part_list :input').keyup(function(){
		var counter = $('#settings input[value!=""]').length+$('#part_list input[value!=""]').length;
		if( counter == ( $('#settings input').length+$('#part_list input').length) ) {
			$("#create").removeAttr("disabled");
			}
		else {
			  $("#create").attr("disabled", "disabled");
			}
		});

	$('#settings :input').keyup(function(){
		var counter = $('#settings input[value!=""]').length+$('#part_list input[value!=""]').length;
		if( counter == ( $('#settings input').length+$('#part_list input').length) ) {
			$("#create").removeAttr("disabled");
			}
		else {
			  $("#create").attr("disabled", "disabled");
			}
		});

	$('#ev').change(function(){
		var value = $('#ev').val()+""+$('#szam').val();
		$('#job_code').val( value );
		$('#job_code').trigger("change");

		value = $("#mcode").val()+""+$('#delimiter').val()+""+value;
		$('#proposed').val( value );
		});

	$('#szam').keyup(function(){
		var value = $('#ev').val()+""+$('#szam').val();
		$('#job_code').val( value );
		$('#job_code').trigger("change");
		
		value = $("#mcode").val()+""+$('#delimiter').val()+""+value;
		$('#proposed').val( value );
		});

	$('#job_code').change(function(){
		$('#i_base').val( $('#job_code').val() );
		$('#u_base').val( $('#job_code').val() );
		$('#o_base').val( $('#job_code').val() );
		$('.target').keyup();
		$('.target2').keyup();
		$('.target3').keyup();
		});	

	$(function() {	
		$('.datepicker').datetimepicker({
			timeFormat: 'HH:mm',
			stepHour: 1,
			stepMinute: 1,
			});
		});
	//toggle_div('settings', $("#t4") );
	/*if( type == 'mod' ) {
		$.ajax	({
			url:"engine/ajax.php",
			type: "GET",
			data: 'op=magazin_modify&code='+id+'&bg_id='+bg_id,
			dataType: 'json',
			success:function( data ) {
				var owidth = document.body.offsetWidth;
				var owidth = parseInt(owidth)-600-20-60.
				$(".pub_button").each(function(){
					$(this).hide();
					});
				$("#pub_table_wrapper2").html( data );
				$("#pub_table_wrapper2").show( 400 );
				$('#magDel').attr("onclick","removeMagazine( '"+id+"' )");
				$('#magDel').show( 200 );
				$("#pub_table_wrapper").animate({ width: owidth+'px' }, 400);
				
				load_ads();
				$('#new_ads input').keyup(function(){
					var counter = $('#new_ads input[value!=""]').length;
					if( counter == $('#new_ads input').length ) {
						$("#add_ad").removeAttr("disabled");
						}
					else {
						  $("#add_ad").attr("disabled", "disabled");
						}
					});
				}
			});
		}*/
	}
</script>