<link rel="stylesheet" href="css/jquery-ui.css">
<script>
jQuery(document).ready(function(){
    $( document ).tooltip({
        tooltipClass: "floatMenu"
        });

    $("[title]").each(function(){
    	$(this).tooltip({ tooltipClass: "floatMenu", content: $(this).attr("title")} );
		});
	});
</script>
<script type="text/javascript" src="js/	jquery-ui-timepicker-addon.js"></script>
<?

$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
if( isset( $_GET['code'] ) ) {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" AND code="'.$_GET['code'].'"', '*' );
	}
else {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
	$_GET['code'] = $pub[0][10];
	}
$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );

 if( !checkOwner( array( 'publications', 'id', $pub[0][0] ), $user ) ) {
 	header("Location: ?page=create_magazine");
 	}

$time = strtotime( $pub[0][11] );
setlocale(LC_ALL,'hungarian');
$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) );

?>
<div id='ad_table_wrapper' style='width: 100%; float: left; overflow: auto;'>
<table id='ad_table' cellspacing='0' cellpadding='0' border='0'>
	<tr>
		<td valign="top" style="background: rgb( 227, 227, 227); width: 229px;">
			<div id='set' style="background: rgb(227, 227, 227);">
				<div style="padding: 10px; text-align: left;">
					<div style='float: left;'>
						<select style="margin-left: -1px;" onchange="Redirect( $(this).val() )">
						<?
							$pubs = getShortPubs( $user[0] );
							for( $i = 0; $i < count( $pubs ); $i++ ) {
								$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code' );

								echo "<option value='".$pubs[$i][0]."_".$pubs[$i][10]."' ";
								if( $_GET['code'] == $pubs[$i][10] && $_GET['id'] == $pubs[$i][0] )
									echo "selected";
								echo ">".$magazine[0][0]." ".$pubs[$i][10]."</option>";
								}
						?>
						</select>
					</div>
					<div style='float: left; margin-left: 5px; margin-top: 1px;'>
						<?
						$pubf = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
						$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name' );
						?>
						<img title="<?= $magf[0][0] ?><br/>Issue: <?= $pubf[0][10] ?><br/>Deadline: <?= $pubf[0][11] ?>" src="images/icons/info.png" height="20px">
					</div>
					<div style='clear:both;'></div>
					<div id='type_buttons' 	style='margin-left: -1px; margin-top: 7px; margin-bottom: 5px; font-size: 14px; text-align:left;'>
						<?
							$flatplans = array(
								"PRE" => 'pre',
								"" => 'basic',
								"FIN" => 'final'
								);
			
							$_GET['opt'] = "";
							foreach( $flatplans as $key => $val ) {
								$valid = true;
								$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
								switch( $key ) {
									case 'PRE':
										$check = sql_get( 'packages', 'publication_id="'.$pubf[0][0].'" AND starting_page = "" LIMIT 1', '*' );
										break;
									case 'FIN':
										$check = sql_get( 'packages', 'publication_id="'.$pubf[0][0].'" AND acquired_name LIKE "FIN_%" LIMIT 1', '*' );
										break;
									default:
										$check = sql_get( 'packages', 'publication_id="'.$pubf[0][0].'" AND starting_page != "" LIMIT 1', '*' );
										break;
									}
									
								if( $check[0][0] == "" ) $valid = false;
				
								if( $valid ) {
									if( $_GET['opt'] != "basic" ) $_GET['opt'] = $val;
									echo "<div id='alter_".$key."' class='kindButton ".$val." ";
									echo "' onclick='changeArcType(\"".$val."\")'>".$val."</div>";
									}	
								}
							echo "<div style='clear:both;'></div>";
						?>
					</div>
													
					<div style='margin-top: 10px;'>
						<select onchange="changeType( $(this).val() )" style="margin-left: -1px;" name='fpView'>
							<?
							$options = array(
								"-1" => $lang["publications"]["all"],
								"0b" => $lang["publications"]["new"],
								"1" => $lang["publications"]["sentback"],
								"0" => $lang["publications"]["delay"]
								);
			
							foreach( $options as $key => $val ) {
								echo "<option value='".$key."' ";
								if( $key == "-1" )
									echo "selected";
								echo ">".$val."</option>";
								}
							?>
						</select>
					</div>			
				</div>
			</div>

			<div id="logSettings">
				<div style="float:left; margin-left: 8px;"><?= $lang["flatplan"]["settings"] ?></div>
				<div class="showLogSettingsPanel" style="float:right; margin-right: 13px; margin-top: -3px;">
					<img class="clickIgnore" style="cursor:pointer;" src="images/settings.png">
				</div>
			
				<div id='logSettingsPanel' class="floatMenu">
					<form id='logSettingsForm'>
					<table id="logSettingsPanelTable" width="100%" cellspacing="0" cellpadding="0">

						<?
							echo "<tr><td align='left' colspan='3'>";
								echo "<div class='panelSubTitle'>".$lang["flatplan"]["flatplan"]."</div>";
							echo "</td></tr>";
							$deny = array();
							if( $user[0][4] == 6 ) {
								$deny[] = 'backArticle';
								}
							else {
								$deny[] = 'newArticle';
								}
						
						
						
							$logSettings = sql_aget( 'userLogSettings', 'user="'.$_SESSION['intra_user'].'" LIMIT 1', '*' );
							$logSettings = $logSettings[0];
							$logSettings = array_reverse($logSettings, true);
							unset( $logSettings['id'] ); unset( $logSettings['user'] );
							$i = $m = 0; $c = 1;
							$title = array( $lang["flatplan"]["comments"], $lang["flatplan"]["article"], $lang["flatplan"]["ads"] );
						
							foreach( $logSettings as $name => $value ) {						
									if( !in_array( $name, $deny ) ) {								
										echo "<tr><td align='left'><input style='margin-left: -1px;' name='logSettingsOption' type='checkbox' ";
										if( $value ) {
											echo "checked ";
											}
										echo "value='".$name."'>".$lang['logSettings'][ $name ]."</td></tr>";
										$i++;
										$c++;
										if( $i == 4 ) {
											if( $c < count( $logSettings ) && $c > 1 && $m < 1 ) {
												echo "<tr><td colspan='3' align='left'>";
													echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
														echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
														echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$title[$m]."</div>";
													echo "</div>";
												echo "</td></tr>";
												$m++;
												$i = 0;
												}
											
											}
											
										if( $i == 3 ) {
											if( $c < count( $logSettings ) && $c > 4 && $m < 2 ) {
												echo "<tr><td colspan='3' align='left'>";
													echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
														echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
														echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$title[$m]."</div>";
													echo "</div>";
												echo "</td></tr>";
												$m++;
												$i = 0;						
												}
											
											}		
										if( $i == 1 ) {
											if( $c < count( $logSettings ) && $c > 6 && $m == 1 ) {
												echo "<tr><td colspan='3' align='left'>";
													echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
														echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
														echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$title[$m]."</div>";
													echo "</div>";
												echo "</td></tr>";
												$m++;
												$i = 0;
												}
											}
										}

								}
						echo "<tr><td colspan='3' align='left'>";
							echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
								echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
								echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$lang["flatplan"]["special"]."</div>";
							echo "</div>";
						echo "</td></tr>";
						echo "<tr><td colspan='3' align='left'>";
							echo $lang["flatplan"]["backward"].":&nbsp;<select name='backward'>";
						
							$options = array( '15 '.$lang["flatplan"]["minute"], '30 '.$lang["flatplan"]["minute"], '1 '.$lang["flatplan"]["hour"], '12 '.$lang["flatplan"]["hours"], '1 '.$lang["flatplan"]["day"], '4 '.$lang["flatplan"]["days"], '8 '.$lang["flatplan"]["days"] );
							$values = array( '15', '30', '60', '360', '1440', '5760', '11520' );
							for( $i = 0; $i < count( $options ); $i++ ) {
								echo "<option value='".$values[$i]."' ";
								if( $user[0][12] == $values[$i] )
									echo "selected";
								echo ">".$options[$i]."</option>";
								}
							echo "</select>";
						echo "</td></tr>";
						?>
					</table>
					</form>
				</div>
			</div>
			<div id="liveLog" style="background: rgb(227, 227, 227); position: absolute; display: block; overflow-x: hidden; overflow-y: auto; width: 229px;"></div>
			<div id="backButton" style='position: absolute; margin-left: 71px; bottom: 10px; text-align: left; font-size: 13px; height: 29px;'>
				<div onclick="history.go(-1);" style="margin-left: 1px; margin-top: 4px;" class="panelButton"><?= $lang["publications"]["back"] ?></div>
			</div>	
		</td>
		<td>
			<div id='pack_list' style="overflow: auto;">
				<table width='100%' id='job_names' class='ad_listing' cellspacing='0' cellpadding='0'>
					<tbody>

					</tbody>
				</table>	
			</div>
		</td>
	</tr>
</table>
</div>
<div id='ad_table_wrapper2' style='display: none; width: 500px; height: 500px; margin-left: -20px; margin-right: 20px; float: right; overflow: auto; overflow-x: hidden;'>
</div>

<script>
var type2 = "-1";
var type = "<?= $_GET['opt'] ?>";
var txt = '';
var user = '<?= $user[0][0] ?>';
var code = '<?= $_GET["code"] ?>';
var p_id = '<?= $_GET["id"] ?>';

function changeType( newType ) {
	type2 = newType;
	}

function changeArcType( newType ) {
	type = newType;
	$(".alterSelected").each(function(){
		$(this).removeClass("alterSelected");
		});
	switch( newType ) {
		case 'basic':
			newType = "";
			break;
		case 'pre':
			newType = "PRE";
			break;
		case 'final':
			newType = "FIN";
			break;
		}
	$("#alter_"+newType).addClass("alterSelected");
	}
changeArcType( type );

function showError( id, type, box ) {
	$.ajax	({
		url:"engine/issueManagementAjax.php",
		type: "GET",
		data: 'op=showerror&id='+id+'&type='+type,
		dataType: 'json',
		success:function( data ) {
			if( $("#"+id+"_"+type+"Float").css("display") == undefined || $("#"+id+"_"+type+"Float").css("display") == "none" ) {
				$(".floatMenu").each(function(){
					$(this).hide( 100 );
					});
				var pos = $("#"+id+"_"+type).position();
				jQuery('<div/>', {
					id: id+"_"+type+"Float",
					class: "settingsPanel floatMenu",
					style: "position: absolute; top: "+pos.top+"px; text-align: left;"
				}).appendTo( "#"+id+">td" );
			
				$("#"+id+"_"+type+"Float").html( data );
				pos.right = $(window).width()-pos.left-$("#liveLog").width()-20;
				$("#"+id+"_"+type+"Float").css( "right", pos.right+"px" );
				$("#"+id+"_"+type+"Float").show( 100 );
				}
			else {
				$("#"+id+"_"+type+"Float").hide( 100, function(){
					$("#"+id+"_"+type+"Float").remove();
					});
				}
			}
		});
	}

function sendForm( id ) {
	if( id == 'partsIssue' ) {
		$( "#result2" ).html( '<img src="images/ajax-loader.gif">' );
		}
	$('<input>').attr({
    	type: 'hidden',
    	id: 'counter_parts',
    	name: 'counter_parts',
    	value:  $("input[name$='_name']").length + $("input[name$='_removed']").length
	}).appendTo('#'+id);
		
	$.ajax	({
		url:"engine/sendFormAjax.php",
		type: "POST",
		data: $("#"+id).serialize(),
		dataType: 'json',
		success:function( data ) {
			var ret = data.split("_");
			$( "#"+ret[1] ).html( ret[0] );
			$("#counter_parts").remove();
			}
		});
	}

function hide_pubs2( id ) {
	$("#ad_table_wrapper2").hide( 400 );
	$("#ad_table_wrapper").animate({ width: '100%' }, 400);
	setTimeout(function(){
		$(".pack_error").each(function(){
			$(this).show();
			});
		$(".thumb_div").each(function(){
			$(this).show();
			});
		$(".errorClick").each(function(){
			$(this).hide();
			});	
		}, 150);
	$( "#"+id+" > td " ).css( 'background', '' );
	}

function hide_pubs() {
	$("#ad_table_wrapper2").hide( 400 );
	$("#ad_table_wrapper").animate({ width: '100%' }, 400);
	setTimeout(function(){
		$(".pack_error").each(function(){
			$(this).show();
			});
		$(".thumb_div").each(function(){
			$(this).show();
			});			
		}, 150);
	}

function publication_change( type, id ) {
	if( type == 'modIssue' ) {
		$.ajax	({
			url:"engine/issueManagementAjax.php",
			type: "GET",
			data: 'op=modIssue&id='+id,
			dataType: 'json',
			success:function( data ) {
				var owidth = document.body.offsetWidth;
				var owidth = parseInt(owidth)-500-20-60.
				$(".pack_error").each(function(){
					$(this).hide();
					});
				$(".thumb_div").each(function(){
					$(this).hide();
					});
				$("#ad_table_wrapper2").html( data );
				$("#ad_table_wrapper").animate({ width: owidth+'px' }, 400);
				$("#ad_table_wrapper2").show( 400 );
				$(function() {
					$('.datepicker').datetimepicker({
						timeFormat: 'HH:mm',
						stepHour: 2,
						stepMinute: 1
						});
				});
				$('div.ui-datepicker').css({ fontSize: '12px' });
				$('#iLength').change(function(){
					if( parseInt( $('#iLength').val() < 6 ) ) {
						$('#iLength').val( '6' );
						}
					if( parseInt( $('#iLength').val() )%2 != 0 ) {
						if( $('#iLength').val() != '' ) {
							$('#iLength').val(parseInt( $('#iLength').val())+1);
							}
						}
					$('#iLength').keyup();
					});

				$('#iLength').keyup(function(){
					if( parseInt( $('#iLength').val() < 6 ) ) {
								$('#iLength').val( 6 );
								}
					var value = '1-2, '+(parseInt( $('#iLength').val() )-1)+'-'+(parseInt( $('#iLength').val() ));
					if( $('#iLength').val() != '' ) {
						$('#part1_place').val( value );
						}
					else {
						$('#part1_place').val( '' );
						}

					value = '3-'+(parseInt( $('#iLength').val() )-2);
					if( $('#iLength').val() != '' ) {
						$('#part2_place').val( value );
						}
					else {
						$('#part2_place').val( '' );
						}	
					});
				}
			});
		}

	}

function Redirect( val ) {
	var temp = val.split("_");
	location.href='?page=publication&id='+temp[0]+'&code='+temp[1];
	}

function load_packages() {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=load_packages&type='+type+'&type2='+type2+'&code='+code+'&user='+user+'&p_id='+p_id,
		dataType: 'json',
		success:function( data ) {
			if( txt != data ) {
				var hide_det = 0;
				if( $(".pack_error").css( "display" ) == 'none' ) {
					hide_det = 1;
					}
				$('tbody', '.ad_listing').html( data );
				txt = data;
				$('.thumb_b').each( function() {
					$(this).tipTipp({ defaultPosition: 'bottom' });
					});	
				
				$('.thumb').each( function() {
					$(this).tipTipp({ defaultPosition: 'top' });
					});
				
				if( hide_det ) {
					var current_row = $("#pub_id").attr("pub_id");
					
					$(".pack_error").each(function(){
						$(this).hide();
						});
					$(".thumb_div").each(function(){
						$(this).hide();
						});
					$(".errorClick").each(function(){
						$(this).show();
						});
						
					$( "#"+current_row+" > td " ).css( 'background', '#FAF2C5' );
					}			
				}
			setTimeout(function(){ load_packages(); }, 500);
			}
		});
	}

load_packages();

$( document ).ready(function() {
	ad_height = parseInt( $( window ).height() )-(parseInt( $(".content_title").outerHeight())+parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-95;
	$('.ad_menu_content').height( ad_height );
	});

function check_place( current ) {
	console.log( current.val() );
	if( parseInt( current.val() ) == 0 ) {
		current.val( 1 );
		}
	if( parseInt( current.val() ) > parseInt( $("#iLength").val() ) ) {
		current.val( parseInt( $("#iLength").val() ) );
		}
	var c = current.attr("id");
	var got_it = '';
	var checker = current.val().split(',');
	for( var i = 1; i <= parts; i++ ) {
		if( c != 'part'+i+'_place' ) {
			var temp = $('#part'+i+'_place').val().split(',');	
			for( var y = 0; y < temp.length; y++ ) {
				if( temp[y].trim().indexOf('-') > 0 ) {
					var t = temp[y].split('-');
					var searcher = '';
				
					for( var a = parseInt(t[0]); a <= parseInt(t[1]); a++ ) {
						searcher += a+',';
						} 
					}		
				else {
					var searcher = temp[y]+',';
					}		
				searcher = searcher.split(',');
				searcher.splice( (searcher.length-1) , 1 );
				for( var x = 0; x < searcher.length; x++ ) {
					for( var z = 0; z < checker.length; z++ ) {
						if( checker[z].trim().indexOf('-') > 0 ) {
							var check_temp = checker[z].split('-');
							if( ( parseInt( searcher[x] ) >= parseInt( check_temp[0] ) ) && ( parseInt( searcher[x] ) <= parseInt( check_temp[1] ) ) ) {
								got_it = 'part'+i+'_place';
								console.log( searcher[x] +' >= '+ check_temp[0] +' ÉS '+searcher[x]+' <= '+check_temp[1] );
								break;
								}
							}
						else {
							if( parseInt( searcher[x].trim() ) == parseInt( checker[z].trim() ) ) {
								got_it = 'part'+i+'_place';
								break;
								}
							}
					
						if( got_it != '' ) { break; }
						}
					if( got_it != '' ) { break; }
					}
				if( got_it != '' ) { break; }
				}
			if( got_it != '' ) { break; }
			}
		}
		
	if( got_it != '' ) {
		var g = got_it.split('_');
		
		var msg = 'Ütközés: '+$('#'+g[0]+'_name').val();
		console.log( '#'+c+'_result == '+msg  );
		$('#'+c+'_result').html( msg );
		
		temp = current.val();
		current.attr('placeholder', temp );
		current.val('');
		$('#part1_place').keyup();
		}
	else {
		$('#'+c+'_result').html( '' );
		}
	}

function removePart( id ) {
	if( $("#part"+id+"_name").val() == '' ) {
		var html = "<input type='hidden' name='part"+id+"_removed' value='"+$("[name='part"+id+"_id']").val()+"'>";
		$('#p'+id).html( html );
		$('#part'+id).html( html );
		}
	}

function check_input( id ) {
	if( $("#part"+id+"_name").val() == '' ) {
		remove_part( id );
		}
	}

function remove_part( nr ) {	
	$('#p'+nr).remove();
	$('#part'+nr).html('');
	$('#part_list :input').keyup(function(){
		var counter = $('#settings input[value!=""]').length+$('#part_list input[value!=""]').length;
		if( counter == ( $('#settings input').length+$('#part_list input').length) ) {
			$("#create").removeAttr("disabled");
			}
		else {
			$("#create").attr("disabled", "disabled");
			}
		});	
	$('#part1_place').keyup();
	}

function create_part() {
	parts++;
	var event = 'toggle_div(\'part'+parts+'\', jQuery(\'div\', this) )';
	
	var text = "<tr id='p"+parts+"'>";
	text += "<td width='50%' align='right' valign='top' style='padding-right: 10px;'>";
		text += "<input counter='"+parts+"' onchange='check_input( $(this).attr(\"counter\") )' style='text-align: right; background: #FFFFFF; border: 1px solid #000; font-size: 16px; width: 80px; padding-right: 5px; padding-left: 5px; font-family: myriad;' type='text' name='part"+parts+"_name' id='part"+parts+"_name' value='Új rész'>";
	text += "</td>";
	text += "<td width='50%' align='left' style='padding-left: 10px;'>";
		text += "<input onchange='check_place( $(this) )' style='width: 80px;' type='text' name='part"+parts+"_place' id='part"+parts+"_place' value=''>";
		text += "<span style='padding-left: 20px; color: #FF0000; font-size: 14px;' id='part"+parts+"_place_result'></span>";
		text += "<br>";
		text += '<select name="part'+parts+'_color">';
			<?
			// Note: the old hardcoded list here carried a Hungarian
			// tooltip (title=...) per standard - that description isn't
			// captured in color_standards, so it's not reproduced here.
			$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "name" );
			for( $s = 0; $s < count( $standards ); $s++ ) {
				echo "\t\t\ttext += '<option class=\"color_select\" value=\"".$standards[$s][0]."\">".str_replace( "_", " ", $standards[$s][0] )."</option>';\n";
				}
			?>
		text += "</select>";
		text += "</td>";
	text += "</tr>";
	
	var counter = $('#counter').val()+','+parts;
	
	$('#counter').val( counter );
	
	$('#part_list tr:last').before( text );
	
	$('#part_list :input').keyup(function(){
		var counter = $('#settings input[value!=""]').length+$('#part_list input[value!=""]').length;
		if( counter == ( $('#settings input').length+$('#part_list input').length) ) {
			$("#create").removeAttr("disabled");
			}
		else {
			$("#create").attr("disabled", "disabled");
			}
		});	
	
	$('#part'+parts+'_place').keyup();
	}

function fit_wrapper2() {
	var ad_height = parseInt( $( window ).height() )-(parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() ) )-82;
	$('#ad_table_wrapper2').height( ad_height );
	
	ad_height = parseInt( $( window ).height() )-parseInt( $("#header").outerHeight());
	$('#liveLog').height( ad_height-parseInt( $("#logSettings").outerHeight() )-parseInt( $("#set").outerHeight() )-parseInt( $("#backButton").outerHeight() )-15 );

	if( !$.browser.device ) {
		ad_height = parseInt( $( window ).height() )-(parseInt( $("#header").outerHeight()));
		$('#pack_list').height( ad_height );	
		}
	}
	
$( document ).ready(function() {
	fit_wrapper2();
	});

$(window).resize(function(){
	fit_wrapper2();
	});

var time = parseInt( '<?= $user[0][12] ?>' );
var refresh = true;
var deny = new Array();
var allowed = new Array();
<?
	foreach( $logSettings as $name => $value ) {
		if( $value == 1 ) {
			echo "allowed.push('".$name."');";
			}
		}
	
	if( $user[0][4] == 6 ) {
		echo "allowed.push('newArticle');";
		echo "deny.push('backArticle');";
		}
	else {
		echo "allowed.push('backArticle');";
		echo "deny.push('newArticle');";
		}
?>

$(function() {
	$("select[name='fpView']").change(function(){
		fpFilter = $(this).val();
		});
		
	$("input[name='logSettingsOption'], select[name='backward']").click(function(){
		var cbox = $( "input[name='logSettingsOption']" ).serializeArray();
		var backward = $( "select[name='backward']" ).val();

		refresh = false;
		$.ajax	({
			url:"engine/logSettings.php?op=saveSettings",
			type: "POST",
			data: {cbox : cbox, time : backward},
			dataType: 'json',
			success:function( data ) {
				refresh = true;
				}
			});
		});

	$(".showLogSettingsPanel").click(function(){
		var state = $("#logSettingsPanel").css('display');

		if( state == "none" ) {
			$("#logSettingsPanel").show( 250 );
			}
		else {
			$("#logSettingsPanel").hide( 250 );
			}
		});
	});
	
function loadLog( method ) {
	if( refresh || method == 'reload' ) {
		$.ajax	({
			url:"engine/loadLog.php?method="+method,
			data: '',
			dataType: 'json',
			success:function( data ) {
				$("#liveLog").html( data[0] );
				setTimeout(function(){ loadLog('repeat'); }, 400);
				}
			});
		}
	else {
		setTimeout(function(){ loadLog('repeat'); }, 400);
		}
	}
loadLog();

</script>
