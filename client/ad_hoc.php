<link href="css/main2.css" rel="stylesheet" type="text/css" />
<script src="js/script2.js"></script>

<div id='ad_table_wrapper' style='width: 100%; float: left; overflow: hidden;'>
	<table class='bigTable' cellspacing='0' cellpadding='0' border='0' style='width: 100%; font-size: 13px;'>
		<tr>
			<td valign="top" style="background: #9AA1D2; width: 229px;">
				<div id='newJobTitle'style='margin-top: 10px; margin-bottom: 4px; text-align: left;'>
					<div style="margin-left:10px;"><?= $lang['adhoc']['newTitle'] ?></div>
				</div>
				<div class='ad_menu_footer_content' style='padding-left: 10px; padding-bottom: 10px;text-align: left;'>
					<form id="newprocess" name="newprocess" method="post" enctype="multipart/form-data">
						<table id="fix"  cellspacing="0" rowspacing="0">
							<tr>
								<td align="left" style='width: 60px; padding: 0 !important; margin: 0 !important;'>
									<div><?= $lang["adhoc"]["process"] ?></div>
								</td>
								<td align="left" style='padding-left: 5px; margin: 0 !important;'>
									<select id='process' name='process'>
										<option value=''><?= $lang["adhoc"]["choose"] ?></option>
										<option value='proof'><?= $lang["adhoc"]["proof"] ?></option>
										<option value='enhance'><?= $lang["adhoc"]["enhance"] ?></option>
									</select>
								</td>
							</tr>
						</table>
						<div id="processOptions" style='display: none;'></div>
						<div style='text-align: center; margin-bottom: 10px; margin-top: 10px;'>
							<button id="ad_sender" disabled="" style="background: #FFF;" class="panelButton buttonTag" onclick=""><?= $lang['adhoc']['upload2'] ?></button>
						</div>
					</form>
				</div>
			</td>
			<td valign="top">
				<div class='bigTable' style='overflow: auto; overflow-x: hidden;'>
				<table id='ad_table' class='ad_listing' cellspacing='0' cellpadding='0' border='0'>
					<tbody>
					</tbody>
				</table>
				</div>
			</td>
		</tr>
	</table>
</div>

<div id='ad_table_wrapper2' style='display: none; width: 600px; height: 500px; margin-left: -20px; margin-right: 20px; float: right; overflow: auto; overflow-x: hidden;'>
</div>

<script>
<?

$name = explode( "_", CreateJobCode( $_SESSION['intra_user'] ) );

//$name = strtoupper($name).'_'.str_pad( count( $u_jobs )+1, 3, '0', STR_PAD_LEFT);

?>

function selectImage( page ) {
	var checkbox = $("#box_"+page+":checked" );
	console.log( $("#box_"+page ) );
	console.log( checkbox.length );
	if( checkbox.length == 0 ) {
		$("#div_"+page).css("border", "5px solid #292377");
		$("#box_"+page).click();
		}
	else {
		$("#div_"+page).css("border", "5px solid transparent");
		$("#box_"+page).click();
		}
	}

$(function(){
	$("#process").change(function() {
		var html = "";
		switch( $(this).val() ) {
			case 'proof':
				a_type = 'proof';
				html += "<div>";
					html += "<input type='hidden' id='uid' name='uid' value='<?= $_SESSION['intra_user'] ?>'>";
					html += "<input type='hidden' id='type' name='type' value='proof'>";
				
					html += "<div>";
						html += "<table width='100%' cellspacing='0' cellpadding='0' style='padding-bottom: 5px;'>";
							html += "<tr>";
								html += "<td align='left' style='width: 60px; height: 25px;'><?= $lang['adhoc']['name'] ?></td>";
								html += "<td align='left' style='padding-left: 7px;'><div id='jcode'>"+jcode+"</div></td>";
							html += "</tr>";
							html += "<tr>";
								html += "<td align='left' style='width: 60px; height: 25px;'><div style='margin-bottom: 6px;'><?= $lang['adhoc']['color'] ?></div></td>";
								html += "<td align='left' style='padding-left: 5px;'>";
									html += "<select name='proof_color' id='proof_color' style='margin-bottom: 10px;'>";
										<?
										$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "name" );
										for( $s = 0; $s < count( $standards ); $s++ ) {
											echo "\t\t\t\t\t\t\t\t\t\thtml += \"<option value='".$standards[$s][0]."'>".str_replace( "_", " ", $standards[$s][0] )."</option>\";\n";
											}
										?>
									html += "</select>";
								html += "</td>";
							html += "</tr>";
							html += "<tr>";
								html += "<td align='left' valign='top' style='width: 60px; height: 25px;'><div style='margin-top: -2px;'><?= $lang['adhoc']['comment'] ?></div></td>";
								html += "<td align='left' style='padding-left: 7px;'><textarea name='proof_message' id='proof_message' style='width: 130px; height: 45px; resize: none;'></textarea></td>";
							html += "</tr>";
							html += "<tr>";
								html += "<td colspan='2' align='left'>";
									html += "<button onclick=\"$('#realFile').click(); return false;\" style='margin-left: -1px; margin-top: 5px;'><?= $lang['adhoc']['fileChoose'] ?></button>";
									html += '<input id="realFile" name="realFile[]" type="file" accept=".tif, .tiff, .jpg, .jpeg, .pdf, application/pdf, image/tiff, image/jpeg" class="file" style="display: none;">';
								html += "</td>";
							html += "</tr>";
						html += "</table>";
					html += "</div>";
				html += "</div>";
				html += "<div style='margin-bottom: 0px;'>";
					html += "<div>";
						html += "<div id='actual_file' style='display:none;'></div>";
						html += "<div style='float:left;' class='opt_line'></div>";
						html += '<div id="info" class="">&nbsp;</div>';
						html += '<div id="info_footer"></div>';
						html += '<div id="thumbs_load"></div>';
						html += '<div id="thumbs"></div>';
					html += "</div>";
				html += "</div>";
				accept_ext = new Array( 'tif', 'tiff', 'jpg', 'jpeg', 'pdf' );
				
				$("#ad_sender").attr("onclick","send_proof('<?= $lang['adhoc']['print_question'] ?>'); return false;");
				$("#processOptions").html( html );
				$("#processOptions").show( 200 );
				$("#ad_sender").text("<?= $lang['adhoc']['upload'] ?>");
				break;
				
			case 'enhance':
				a_type = 'enhance';
				html += "<div>";
					html += "<input type='hidden' id='uid' name='uid' value='<?= $_SESSION['intra_user'] ?>'>";
					html += "<input type='hidden' id='type' name='type' value='enhance'>";
				
					html += "<div>";
						html += "<table width='100%' cellspacing='0' cellpadding='0' style='padding-bottom: 5px;'>";
							html += "<tr>";
								html += "<td align='left' style='width: 60px; height: 25px;'><?= $lang['adhoc']['name'] ?></td>";
								html += "<td align='left' style='padding-left: 7px;'><div id='jcode'>"+jcode+"</div></td>";
							html += "</tr>";
							html += "<tr>";
								html += "<td align='left' style='width: 60px; height: 25px;'><div style='margin-bottom: 5px;'><?= $lang['adhoc']['type'] ?></div></td>";
								html += "<td align='left' style='padding-left: 5px;'>";
									html += "<select name='typeof' id='typeof' style=' margin-bottom: 9px;'>";
										html += "<option value='enhance'><?= $lang['adhoc']['correction'] ?></option>";
										html += "<option value='masking'><?= $lang['adhoc']['mask'] ?></option>";
										html += "<option value='retouch'><?= $lang['adhoc']['retouch'] ?></option>";
										html += "<option value='full'><?= $lang['adhoc']['pack'] ?></option>";
									html += "</select>";
								html += "</td>";
							html += "</tr>";
							html += "<tr>";
								html += "<td align='left' style='width: 60px; height: 25px;'><div style='margin-bottom: 7px;'><?= $lang['adhoc']['color'] ?></div></td>";
								html += "<td align='left' style='padding-left: 5px;'>";
									html += "<select name='proof_color' id='proof_color' style=' margin-bottom: 11px;'>";
										<?
										$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "name" );
										for( $s = 0; $s < count( $standards ); $s++ ) {
											echo "\t\t\t\t\t\t\t\t\t\thtml += \"<option value='".$standards[$s][0]."'>".str_replace( "_", " ", $standards[$s][0] )."</option>\";\n";
											}
										?>
									html += "</select>";
								html += "</td>";
							html += "</tr>";						
							html += "<tr>";
								html += "<td align='left' valign='top' style='width: 60px; height: 25px;'><div style='margin-top: -2px;'><?= $lang['adhoc']['comment'] ?></div></td>";
								html += "<td align='left' style='padding-left: 7px;'><textarea name='proof_message' id='proof_message' style='width: 130px; height: 45px; resize: none;'></textarea></td>";
							html += "</tr>";
							html += "<tr>";
								html += "<td colspan='2' align='left'>";
									html += "<button onclick=\"$('#realFile').click(); return false;\" style='margin-left: -1px; margin-top: 5px;'><?= $lang['adhoc']['fileChoose'] ?></button>";
									html += '<input id="realFile" name="realFile[]" type="file" multiple accept=".zip,.tif,.tiff,.jpg,.jpeg,.psd,application/zip, image/tiff, image/jpeg, application/photoshop" class="file" style="display: none;">';
								html += "</td>";
							html += "</tr>";
						html += "</table>";
					html += "</div>";
				html += "</div>";
				html += "<div class style='margin-bottom: 0px;'>";
					html += "<div>";
						html += "<div style='float:left;' class='opt_line'></div>";
						html += '<div id="info" class="">&nbsp;</div>';
						html += '<div id="info_footer"></div>';
						html += '<div id="thumbs_load"></div>';
					html += "</div>";
				html += "</div>";
				html += '<div id="progress_info"><div id="progress"></div><div id="progress_percent"></div></div>';
				accept_ext = new Array( 'zip', 'tif', 'tiff', 'jpg', 'jpeg', 'psd' );
				
				$("#ad_sender").attr( "onclick", "pre_check('<?= $lang['adhoc']['processOk'] ?>'); return false;");
				$("#processOptions").html( html );
				$("#processOptions").show( 200 );
				$("#ad_sender").text("<?= $lang['adhoc']['upload2'] ?>");
				break;
				
			default:
				a_type = '';
				$("#processOptions").hide( 200, function(){
					$("#processOptions").html( "" );
					});
				$("#ad_sender").attr("onclick","");
				$("#ad_sender").text("<?= $lang['adhoc']['upload2'] ?>");
				break;
			};

		count = 0;
		files = '';
		files_data = new Array();
		var owidth = document.body.offsetWidth;
		var owidth = parseInt(owidth)-600-20-60.

		activateRealFile();
		});
	});

function activateRealFile() {
	$('#realFile').on("change", function(event) {
		for( var i = 0; i < $('#realFile')[0].files.length; i++ ) {
			accept = true;
			for( var y = 0; y < files_data.length; y++ ) {
				if( $('#typeof').val() == 'full' ) {
					if(  $('#realFile')[0].files[i].name == files_data[y].name || count >= 1 ) {
						accept = false;
						}					
					}
				else {
					if(  $('#realFile')[0].files[i].name == files_data[y].name ) {
						accept = false;
						}
					}
				}
			
			if( accept ) {
				var ext = $('#realFile')[0].files[i].name.substring( parseInt( $('#realFile')[0].files[i].name.length-3 ) ).toLowerCase();
				var put = false;
				if( jQuery.inArray( ext, accept_ext ) != -1 ) {
					put = true;
					files_data.push( $('#realFile')[0].files[i] );
					count = parseInt(files_data.length);
					files += "<div fileid='"+count+"'>";
					if( a_type!= 'proof' ) {
						files += "<div id='"+count+"' style='float:left'><input id='del_select' type='checkbox' name='del_select' value='"+count+"'>";
						files += $('#realFile')[0].files[i].name+"</div>";
						}
					else {
						files += "<div id='"+count+"' style='float:left;'>"+$('#realFile')[0].files[i].name.substring( 0 , parseInt( $('#realFile')[0].files[i].name.length-4 ) )+"</div>";
						}
					files += '<div id="progress_info2" class="'+count+'"><div id="progress" class="'+count+'"></div><div id="progress_percent" class="'+count+'"></div></div></div><div style="clear:both"></div>';
					}
				}
			}
		
		$('#thumbs_load').html( '' );
		if( files != "" ) {
			$('#info').addClass('upload_fileList');
			$('#info').html( files );
			}
			
		$('input').click(function(){
			buttonValidator();
			});
		if( a_type == 'proof' ) {
			pre_check("<?= $lang['adhoc']['loadingPreview'] ?>");
			}
		else {
			if( files != "" ) {
				var footer = "<button id='remover' onclick='removeFromFileList(); return false;' style='margin-left: -1px;'><?= $lang['adhoc']['remove_item']?></button>";
				$('#info_footer').html( footer );
				}
			//$('input[type=checkbox]').click();
			}

		});
	}

var jcode_prefix = '<?= $name[0] ?>';
var jcode_postfix = '<?= $name[1] ?>';
var jcode = jcode_prefix+'_'+jcode_postfix;
var defHistory = '';

var a_type = '';
var default_inf = '';
var accept = true;
var count = 0;
var files = '';
var files_data = new Array();
var accept_ext = new Array();

function buttonValidator() {
	if( a_type == 'proof' ) {
		if( $('.p_page:checked').length >= 1 ) {
			$("#ad_sender").removeAttr("disabled");
			}
		else {
			$("#ad_sender").attr("disabled", "disabled");
			}
		}
	else {
		count = parseInt(files_data.length);
		if( count >= 1 ) {
			$("#ad_sender").removeAttr("disabled");
			}
		else {
			$("#ad_sender").attr("disabled", "disabled");
			}
		}	
	}

function getHistory() {
	$.ajax	({
		url:"engine/adhocAjax.php",
		data: 'op=getHistory',
		dataType: 'json',
		success:function( data ) {
			if( defHistory != data ) {
				defHistory = data;
				$("#ad_table tbody").html( data );
				}
				
			setTimeout(function(){ getHistory(); }, 500);
			}
		});
	
	}
getHistory();

function hide_pubs() {
	$("#ad_table_wrapper2").hide( 400 );
	$("#ad_table_wrapper").animate({ width: '100%' }, 400);
	setTimeout(function(){
		$(".date").each(function(){
			$(this).show();
			});
		}, 100);
	}

function GenerateJobCode() {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=new_jobcode',
		dataType: 'json',
		success:function( data ) {
			jcode_prefix = data[0];
			jcode_postfix = data[1];
			
			jcode = jcode_prefix+'_'+jcode_postfix;
			$('#jcode').html( jcode );
			}
		});
	}

function CheckFiles() {
	files = '';
	var new_fileData = new Array();
	
	for( var i = 0; i < files_data.length; i++ ) {
		var ext = files_data[i].name.substring( parseInt( files_data[i].name.length-3 ) ).toLowerCase();
		if( jQuery.inArray( ext, accept_ext ) != -1 ) {
			new_fileData.push( files_data[i] );
			count = parseInt(new_fileData.length);
			
			files += "<div fileid='"+count+"'>";
			files += "<div id='"+count+"' style='float:left'><input id='del_select' type='checkbox' name='del_select' value='"+count+"'>";
			files += files_data[i].name+"</div>";
			files += '<div id="progress_info2" class="'+count+'"><div id="progress" class="'+count+'"></div><div id="progress_percent" class="'+count+'"></div></div></div><div style="clear:both"></div>';
			
			if( count == 1 ) break;
			}
		}

	files_data = new Array();
	files_data = new_fileData;
	
	if( files != '' ) {
		$('#info').html( files );
		}
	else {
		count = 0;
		files_data = new Array();
		$('#info').html( default_inf );
		$('#info').removeClass('upload_fileList');
		$('#info_footer').html('');
		$('#submit_files').fadeOut('fast');
		$('#realFile').val('');
		$('#ad_hoc_settings :input').keyup();
		}

	}

function send_proof( question ) {
	if( confirm( question ) ) {
		$('#thumbs_load').html( '<?= $lang["adhoc"]["processing"] ?>... <img src="images/ajax-loader.gif">' );
		var file = $('#actual_file').html();
		var pages = $('.p_page:checked').map(function() {return this.value;}).get().join('_');
		$.ajax	({
			url:"engine/ajax.php",
			data: 'op=send_proof&file='+file+'&page='+pages+'&color='+$('#proof_color').val()+'&msg='+$('#proof_message').val()+'&type='+$('#type').val(),
			dataType: 'json',
			success:function( data ) {
				console.log( data );
				$('#thumbs_load').html( '' );
				$('#actual_file').html('');
				$('#s_all').html('');
				$('#thumbs').html('');
				$('#realFile').val('');
				$('#proof_message').text('');
				$('#thumbs_load').html( '<div class="send_ok"><?= $lang["adhoc"]["processing_complete"] ?></div>' );
				$('#ad_hoc_settings :input').keyup();
				GenerateJobCode();
				}
			});
		}
	}

function removeFromFileList() {
	//console.log( files_data );
	var new_fileData = new Array();
	if( $('#del_select:checked').length > 0 ) {
		$('#del_select:checked').each(function(){
			delete files_data[(parseInt($(this).val())-1)];
			console.log( 'tömb '+(parseInt($(this).val())+1)+'. elem törlése');
			});
		//console.log( files_data );
		$.each( files_data, function( i ) {
			if( files_data[i] != undefined ) {
				new_fileData.push( files_data[i] );
				}
			});
		
		files_data = new Array();
		files_data = new_fileData;
		
		files = '';
		for( var i = 0	; i < files_data.length; i++ ) {
			var nbr = i+1;
			files += "<div fileid='"+nbr+"'><div id='"+nbr+"' style='float:left'><input id='del_select' type='checkbox' name='del_select' value='"+nbr+"'>"+files_data[i].name+"</div>";
			files += '<div id="progress_info2" class="'+nbr+'"><div id="progress" class="'+nbr+'"></div><div id="progress_percent" class="'+nbr+'"></div></div></div><div style="clear:both"></div>';
			}
		if( files != '' ) {
			$('#info').html( files );
			}
		else {
			count = 0;
			files_data = new Array();
			$('#info').html( default_inf );
			$('#info').removeClass('upload_fileList');
			$('#info_footer').html('');
			$('#submit_files').fadeOut('fast');
			$('#realFile').val('');
			$('input').click();
			}
		}
	}

function init_adhoc( type ) {
	a_type = type;
	hide_pubs();
	setTimeout(function(){
		$('.selected3').each(function() {
			$(this).removeClass( 'selected3' );
			});
		
		var html = '';
		if( type=='enhance' ) {
			}

		if( type=='proof' ) {
			}

		count = 0;
		files = '';
		files_data = new Array();
		var owidth = document.body.offsetWidth;
		var owidth = parseInt(owidth)-600-20-60.
		
		$('#ad_table_wrapper2').html( html );
		
		$('#ad_hoc_settings input, textarea').keyup(function(){
			buttonValidator();
			});

		$(".date").each(function(){
			$(this).hide();
			});
		$("#ad_table_wrapper2").show( 400 );
		$("#ad_table_wrapper").animate({ width: owidth+'px' }, 400);
		$('#'+type).addClass('selected3');
	
		$('#ad_hoc_settings').fadeIn( 'fast' );
				
		$('#typeof').change(function(){
			if( $('#typeof').val() == 'full' ) {
				$('#realFile').attr("accept","application/zip");
				accept_ext = new Array( 'zip' );
				CheckFiles();
				}
			else {
				$('#realFile').attr("accept","application/zip, image/tiff, image/jpeg, application/photoshop");
				accept_ext = new Array( 'zip', 'tif', 'tiff', 'jpg', 'jpeg', 'psd' );
				}
			});
		
		$('#button_large2').click(function(){
			$('#realFile').click();
			});
		}, 400 );
	}

function fit_wrapper2() {
	var ad_height = parseInt( $( window ).height() )-parseInt( $("#header").outerHeight() );
	$('.bigTable').height( ad_height );
	
	if( $('#thumbs').length != 0 ) {
		var height = $(window).height()+parseInt( $("#thumbs").height() )-parseInt( $(".ad_menu_footer_content").height() )-parseInt( $("#header").height() )-parseInt( $("#newJobTitle").height() );
		$('#thumbs').css("max-height", height+"px");
		}
	}
	
$( document ).ready(function() {
	fit_wrapper2();
	});

$(window).resize(function(){
	fit_wrapper2();
	});

</script>