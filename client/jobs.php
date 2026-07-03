<table width='100%' id='job_names' class='ad_listing' cellspacing='0' rowspacing='0'>
	<tbody>
		
	</tbody>
</table>

<script>
function jobSettingsMenu( info ) {
	id = "line_"+info[1];
	if( $("#"+id+"Float").css("display") == "none" || $("#"+id+"Float").length == 0 ) {
		$.ajax	({
			url:"engine/menuAjax.php?op=generatejobmenu",
			type: "POST",
			data: { info: info },
			dataType: 'json',
			success:function( data ) {
				jQuery('<div/>', {
    				id: id+"Float",
    				class: "floatMenu",
    				style: "position: absolute; text-align: left; display:none; width: 180px; left: 18px; top:23px;"
					}).appendTo( "#"+id );
				$("#"+id+"Float").html( data );
				$(".settingsPanel").hide(200, function(){
					$(this).remove();
					});
				$("#"+id+"Float").show(200);
				
				$( "#"+id+"Float" ).draggable({ cursor: "move" });
				}
			});
		}
	else {
		$("#"+id+"Float").hide(200);
		}
	}

var def_jobs = "";
function load_pubs() {
	$.ajax	({
		url:"engine/job_ajax.php",
		type: "GET",
		data: 'op=load_jobs',
		dataType: 'json',
		success:function( data ) {
			if( data != def_jobs ) {
				def_jobs = data;
				$('.ad_listing tbody').empty();
				$('.ad_listing tbody').append( data );
				if( $("#ad_table_wrapper2").css('display') == 'block' ) {
					var current_row = $("#pub_id").attr("pub_id");
					$(".pub_button").each(function(){
						$(this).hide(0);
						});
					$( "#"+current_row+" > td " ).css( 'background', '#FAF2C5' );
					}
				
				}
			setTimeout(function(){ load_pubs(); }, 1000);	
			}
		});
	}
load_pubs();

</script>