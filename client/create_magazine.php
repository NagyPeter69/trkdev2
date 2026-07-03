<?
	/*$pubs = sql_get( "magazines", "publisher_id='".$user[0][4]."'", "id, code" );
	$alters = explode( ",", $user[0][10] );
	foreach( $alters as $alter ) {
		$pubs2 = sql_get( "magazines", "publisher_id='".$alter."'", "id, code" );	
		$pubs = array_merge_recursive( $pubs, $pubs2 );
		}*/

	

	$magID = explode( ",", $user[0][21] );
	$pubs = array();
	for( $i = 0; $i < count( $magID ); $i++ ) {
		$mag = sql_get( 'magazines', 'id="'.$magID[$i].'" ORDER BY `id` ASC', '*' );
		for( $y = 0; $y < count( $mag ); $y++ ) {
			$pubs[] = $mag[$y];
			}
		}		


		
	if( count( $pubs ) == 1 ) {
		$pubs = sql_get( "publications", "publisher_id='".$user[0][4]."' AND magazine_id='".$pubs[0][0]."'", "id, code" );

		if( !empty( $pubs[0][0] ) ) {
			header("Location: ?page=timeline&id=".$pubs[0][0]."&code=".$pubs[0][1] );
			}
		}

	if( isset( $_POST['ki_set'] ) ) {
		$_POST['Mails'] = explode( "\r\n", $_POST['Mails'] );
		$_POST['Mails'] = trim( implode( ";", $_POST['Mails'] ) );
		$_POST['old_code'] = $_GET['code'];
		$_POST['deny'] = 'ki_set';
		
		changeXmlDatabase( 'modify', $_POST, 'xml/'.PMD.'.xml' );
		$e_code = $lang["settings"]["success"];
		}

	if( isset( $_POST['color_set'] ) ) {
		$_POST['old_code'] = $_GET['code'];
		$_POST['deny'] = 'color_set';
		
		changeXmlDatabase( 'modify', $_POST, 'xml/'.PMD.'.xml' );
		$e_code2 = $lang["settings"]["success"];
		}

?>
<div id='pub_fixed'>
</div>

<div id='pub_table_wrapper' style='width: 100%; height: 100%; float: left; overflow: auto;'>
<?

	if( $_GET['opt'] == 'ftp' ) {
		include_once( 'ftp_settings.php' );
		}
	if( $_GET['opt'] == 'pmd' ) {
		
?>
	<form method='post' action='?page=create_magazine&opt=pmd&code=<?= $_GET['code'] ?>'>
	<div class='setting_row2'>
		<div class='default' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_row_header'><?= $lang["settings"]["mag_settings"] ?></div>
		<div style='float: left; padding-left: 25px; margin-top: -2px;'>
			<button id='ki_set' name='ki_set' value='Mentés' style='margin-top: 10px; padding: 5px 10px 5px 10px;'>Mentés</button>
		</div>
		<div style='float: left; padding-left: 20px; margin-top: 10px;'>
			<span style='color: #489620;'><?= $e_code ?></span>
		</div>
		
		<div style='clear:both;'></div>
		
		<div class='settings_row_content'>
			<table width='100%' cellspacing='0' cellpadding='0'>
			<?
				$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
				$avaiable = array( 'IDversion', 'Language', 'Period', 'TrimSize', 'RemoteStorage', 'SoftProof', 'MailComm', 'Mails' );
				
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $_GET['code'] )
							break;
						}
					}				
				
				foreach( $avaiable as $key ) {
					$value = (string) $xml->Item[$i]->$key;
					$temp = array();
					switch( $key ) {
						case 'SoftProof':
							$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', "*");
							$mag = sql_get( 'magazines', 'code="'.$_GET['code'].'"', "*");
							$pub = sql_get( 'publishers', 'id="'.$mag[0][1].'"', 'name' );
							
							$xml2 = simplexml_load_file( 'xml/Output_Details.xml' );						
							$xpath2 = $xml2->$pub[0][0]->Outward->Final->children();

							foreach( $xpath2 as $temp2 ) {
								$temp[] = $temp2->getName();
								}
							if( count( $temp ) <= 0 ) {
								$temp = array( '' );
								}
							break;
						case 'RemoteStorage':
							$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', "*");
							$mag = sql_get( 'magazines', 'code="'.$_GET['code'].'"', "*");
							$pub = sql_get( 'publishers', 'id="'.$mag[0][1].'"', 'name' );
							
							$xml2 = simplexml_load_file( 'xml/Output_Details.xml' );
							$xpath2 = $xml2->$pub[0][0]->Outward->Content->children();
							foreach( $xpath2 as $temp2 ) {
								if( $temp2->getName() != 'Targets' )
									$temp[] = $temp2->getName();
								}
							if( count( $temp ) <= 0 ) {
								$temp = array( '' );
								}
								
							break;
						case 'IDversion':
							$temp = array( 'CS3', 'CS4', 'CS5','CS55' ,'CS6' );
							break;
						case 'Language':
							$temp = array( 'HU' );
							break;
						case 'Period':
							$temp = array( '1', '2', '3', '4', 'undefined' );
							break;
						case 'TrimSize':
							$temp = explode( " ", $value );
							break;
						case 'MailComm':
							$temp = array( 'Yes', 'No' );
							break;
						
						}
					
					echo "<tr>";
						echo "<td width='50%' align='right' valign='top' style='padding-right: 10px;'>".$lang['xml'][$key]."</td>";
						echo "<td width='50%' align='left' style='padding-left: 10px;'>";
							if( $key == 'Mails' ) {
								echo "<textarea name='".$key."' style='width: 200px; height: 70px;'>";
									echo str_replace( ";", "\n", $value );
								echo "</textarea>";
								}
							elseif( $temp == '' ) {
								echo "<input type='text' name='".$key."' value='".$value."'>";
								}
							elseif( $key == 'TrimSize' ) {
								echo "<input type='text' name='".$key."_x' value='".$temp[0]."' style='width: 30px;'> x <input type='text' name='".$key."_y' value='".$temp[2]."' style='width: 30px;'> mm";
								}
							else {
								echo "<select name='".$key."'>";
								foreach( $temp as $t ) {
									echo "<option ";
									if( $value == $t ) echo "selected ";
										echo "value='".$t."'>".$t."</option>";
									}
								echo "</select>";
								}
						echo "</td>";
					echo "</tr>";
					}
			?>
			</table>
		</div>
	</div></form>

	<form method='post' action='?page=create_magazine&opt=pmd&code=<?= $_GET['code'] ?>'>
	<div class='setting_row2'>
		<div class='default' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_row_header'><?= $lang["settings"]["mag_color"] ?></div>
		<div style='float: left; padding-left: 25px; margin-top: -2px;'>
			<button id='color_set' name='color_set' value='Mentés' style='margin-top: 10px; padding: 5px 10px 5px 10px;'>Mentés</button>
		</div>
		<div style='float: left; padding-left: 20px; margin-top: 10px;'>
			<span style='color: #489620;'><?= $e_code2 ?></span>
		</div>
		
		<div style='clear:both;'></div>
		
		<div class='settings_row_content'>
			<table width='100%' cellspacing='0' cellpadding='0'>
			<?
				$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
				$avaiable = array( 'Cover', 'Content', 'Insert', 'ArchiveMode', 'OutputFormat', 'Resolution' );
				
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $_GET['code'] )
							break;
						}
					}				
				
				foreach( $avaiable as $key ) {
					if( $key == 'Cover' or $key == 'Content' or $key == 'Insert' ) {
						$value = (string) $xml->Item[$i]->ColorManagement->$key;
						}
					else {
						$value = (string) $xml->Item[$i]->$key;
						}
					$temp = '';
					switch( $key ) {
						case 'ArchiveMode':
							$temp = array( 'RGB', 'CMYK' );
							break;
						case 'Resolution':
							$temp = array( '300', '450', '600' );
							break;
						case 'OutputFormat':
							$temp = array( 'TIFF', 'JPEG', 'Original' );
							break;
						case 'Cover':
						case 'Content':
						case 'Insert':
							$temp = array( 'FOGRA_39', 'FOGRA_45', 'FOGRA_46', 'IFRA_26', 'PSR_LWC_PLUS_V2_PT', 'PSR_LWC_STD_V2_PT', 'PSR_SC_PLUS_V2_PT', 'PSR_SC_STD_V2_PT' );
							break;
						}
					
					echo "<tr>";
						echo "<td width='50%' align='right' valign='top' style='padding-right: 10px;'>".$lang['xml'][$key]."</td>";
						echo "<td width='50%' align='left' style='padding-left: 10px;'>";
							if( $temp == '' ) {
								echo "<input type='text' name='".$key."' value='".$value."'>";
								}
							else {
								echo "<select name='".$key."'>";
								foreach( $temp as $t ) {
									echo "<option ";
									if( $value == $t ) echo "selected ";
										echo "value='".$t."'>".$t."</option>";
									}
								echo "</select>";
								}
						echo "</td>";
					echo "</tr>";
					}
			?>
			</table>
		</div>
	</div></form>

<?
		}
	elseif( $_GET['opt'] == '' ) {
		include_once( 'kiadvany.php' );
		}
?>

</div>

<div id='pub_table_wrapper2' style='display: none; width: 600px; height: 500px; margin-left: -20px; margin-right: 20px; float: right; overflow: auto; overflow-x: hidden;'>
</div>


<script>
	
function fit_wrapper2() {
	var ad_height = parseInt( $( "#mainPage" ).height() )-(parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight()+parseInt( $("#sub_menu").outerHeight() ) ) )-55;
	$('#pub_table_wrapper2').height( ad_height );
	}
	
$( document ).ready(function() {
	fit_wrapper2();
	});

$(window).resize(function(){
	fit_wrapper2();
	});

function Redirect( val ) {
	location.href='?page=create_magazine&opt=pmd&code='+val;
	}

function Redirect2( val ) {
	location.href='?page=create_magazine&opt=ftp&pub='+val;
	}

function send_mod_pub() {
	$("#mod").attr("disabled", "disabled");
	$.ajax	({
		url:"engine/ajax.php",
		type: "GET",
		data: 'op=modify&id='+$('#p_ID').val()+'&page='+$('#pages').val()+'&dl='+$('#deadline').val(),
		dataType: 'json',
		success:function( data ) {
			all_pubs( data );
			$('#modify').hide('slow');
			}
		});	
	}

function modifyPub( pub_id ) {
	$.ajax	({
		url:"engine/ajax.php",
		type: "GET",
		data: 'op=modify_pub&id='+pub_id,
		dataType: 'json',
		success:function( data ) {
			$('#m_cont').html( data );
			$('.datepicker').datetimepicker({
				timeFormat: 'HH:mm',
				stepHour: 1,
				stepMinute: 1
				});
			$('div.ui-datepicker').css({ fontSize: '12px' });
			$('#modify').show('slow');
			$('#modify :input').keyup(function() {
				var counter = $('#modify input[value!=""]').length;
				if( counter == $('#modify input').length ) {
					$("#mod").removeAttr("disabled");
					}
				else {
					  $("#mod").attr("disabled", "disabled");
					}
				});
			}
		});	
	
	}

function all_pubs( data ) {
	$('#all_pub_div table tbody').html('');
	var dhtml = '';
	
	if( data == null || data == '' ) {
		dhtml = '<tr><td colspan="4" class="two left bottom right" align="center" height="28px"><i>Jelenleg nincs megjelenés.</i></td></tr>';
		$('#all_pub_div table tbody').append( dhtml );
		}
	else {
		for( var i = 0; i < data.length; i++ ) {
			var modify = '<img onclick="modifyPub('+data[i][0]+')" style="cursor: pointer;" src="../images/edit.png" height="20px">';
			var extra_class = '';
			if( i%2 == 0 ) { extra_class = 'two' }
			else { extra_class = '' }
			
			dhtml = '<tr>';
			dhtml += '<td class="'+extra_class+' left bottom" height="28px"><a href="?page=publication&id='+data[i][0]+'&code='+data[i][10]+'">'+data[i][10]+'</a></td>';
			dhtml += '<td class="'+extra_class+' bottom" height="28px">'+data[i][6]+'</td>';
			dhtml += '<td class="'+extra_class+' bottom" height="28px">'+data[i][11]+'</td>';
			dhtml += '<td style="padding-right: 10px;" class="'+extra_class+' bottom right" height="28px" align="right">'+modify+'</td>';
			dhtml += '</tr>';
			$('#all_pub_div table tbody').append( dhtml );
			}
		}
	}

function create_preview( prefix, target ) {
	var value = ( $('#'+prefix+'aname').val() == "Yes" ) ? "_" : "";
	$('#'+prefix+'adelimiter').val( value );
	
	if( $('#'+prefix+'var_del' ).val() == undefined ) {
		var var_del = '_';
		}
	else {
		var var_del = $('#'+prefix+'var_del' ).val();
		}
	
	var code = $('#'+prefix+'code').val();
	var base = $('#'+prefix+'base').val();
	var delimiter = $('#'+prefix+'delimiter').val();
	var padding = $('#'+prefix+'padding').val();
	var preview = code+''+delimiter+''+base;
	
	if( $('#'+prefix+'variable').val() == 'after' ) {
		preview = preview+''+var_del+''+pad("12", parseInt(padding) );
		}
	else {
		preview = pad("12", parseInt(padding) )+''+var_del+''+preview;
		}
	
	preview +='.pdf'
	$('#'+target+'').html( preview );
	}
	
var code = '';

function publication( m_id ) {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=create_pub&m_id='+m_id,
		dataType: 'json',
		success:function( data ) {
			$('#m_id').val( data[0][0] );
			$('#i_code').val( data[0][3] );
			$('#u_code').val( data[0][3] );
			$('#o_code').val( data[0][3] );
			$('#cm').html( data[0][2] );
			$('#msg_box').hide('slow');
			$('#edit_magazine').hide('slow');
			$('#mySliderTabs').hide('slow');
			$('#slider_wrapper').show('slow');
			$('#content').animate({ scrollTop: 0 }, 'slow');

			all_pubs( data[1] );
			load_ads();
			
			$('.target').keyup();
			$('.target2').keyup();
			$('.target3').keyup();
			}
		});
	}

function hide_pubs() {
	$( ".pub_listing > tbody > tr > td " ).css( 'background', '' );
	$("#pub_table_wrapper2").hide( 400 );
	$("#pub_table_wrapper").animate({ width: '100%' }, 400);
	setTimeout(function(){
		$(".pub_button").each(function(){
			$(this).show();
			});
		}, 100);
	$('#magDel').attr("onclick","");
	$('#magDel').hide( 200 );
	}

function edit_magazine( id ) {
	$.ajax	({
		url:"../engine/ajax.php",
		data: 'op=edit_magazine&id='+id,
		dataType: 'json',
		success:function( data ) {
			//var size = data[4].split('x');
			$('#edit_m input[name="e_name"]').val( data[0] );
			$('#edit_m input[name="e_code"]').val( data[1] );
			$('#edit_m select[name="e_workflow"]').val( data[2] );
			$('#edit_m select[name="e_period"]').val( data[3] );
			$('#edit_m input[name="e_width"]').val( parseInt( size[0] ) );
			$('#edit_m input[name="e_height"]').val( parseInt( size[1] ) );
			$('#edit_m input[name="e_id"]').val( data[5] );
			
			$('#edit_m input[name="e_name"]').keyup();
			$('#edit_magazine').show('slow');
			}
		});
	}

<?
	if( isset( $ok ) ) {
		if( $ok == 0 ) {
			echo '$("#msg_box").attr("class", "failed");';
			echo '$("#msg_box").html( "'.$e_code .'" );';
			echo '$( "#msg_box" ).show(500);';
			}
		if( $ok == 1 ) {
			echo '$("#msg_box").attr("class", "success");';
			echo '$("#msg_box").html( "'.$e_code .'" );';
			echo '$( "#msg_box" ).show(500);';
			}
		}
?>

$('#edit_magazine :input').keyup(function(){
	var counter = $('#edit_magazine input[value!=""]').length;
	if( counter == $('#edit_magazine input').length ) {
		$("#submitter2").removeAttr("disabled");
		}
	else {
		  $("#submitter2").attr("disabled", "disabled");
		}
	});

$('#new_magazine :input').keyup(function(){
	var counter = $('#new_magazine input[value!=""]').length;
	if( counter == $('#new_magazine input').length ) {
		$("#submitter").removeAttr("disabled");
		}
	else {
		  $("#submitter").attr("disabled", "disabled");
		}
	});
	
jQuery(function($){
        $.datepicker.regional['hu'] = {
                dateFormat: 'yy-mm-dd', firstDay: 1,
                isRTL: false};
        $.datepicker.setDefaults($.datepicker.regional['hu']);
	});

function check_place( current ) {
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
				for( var x = 0; x < searcher.length; x++ ) {
					for( var z = 0; z < checker.length; z++ ) {
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

</script>