<table id='part_list' class='panelTable' cellspacing='0' cellpadding='0'>
	<tr>
		<td colspan='2' style='cursor: pointer; padding-left: 15px; font-family: myriad_bold;' align="left" colspan="2" height='25px' onclick="toggle_div('part1', jQuery('div', this) );">
			<div id="part1_t" class="toggler" style="width: 70px; padding-left: 26px;">
				<input style='color: #FFF; background: transparent; font-size: 14px; border: 0px;' type='text' readonly id='part1_name' name='part1_name' value='<?= $lang["publications"]["Cover"] ?>'>
			</div>
		</td>
	</tr>
	<tr><td colspan="2">
		<div id="part1" style="display: none;">
			<table class='panelTable' cellspacing='0' cellpadding='0'>
				<tr>
					<td style='padding-left: 15px;' align='left' height='28px'><?= $lang["publications"]["position"] ?></td>
					<td align='left'><input type='text' onchange='check_place( $(this) )' id='part1_place' name='part1_place' style='width: 100px;'><span style='padding-left: 20px; color: #FF0000; font-size: 14px;' id="part1_place_result"></span></td>
				</tr>
				<tr>
					<td style='padding-left: 15px;' align='left' height='28px'><?= $lang["publications"]["color"] ?></td>
					<td align='left'>
						<select name="part1_color">
						<?
						$colors = array( 'FOGRA_39', 'FOGRA_41', 'FOGRA_45', 'FOGRA_46', 'FOGRA_47', 'FOGRA_51', 'FOGRA_52' , 'IFRA_26' );
						$value = $newxml->Item[$x]->ColorManagement->Cover;
						
						for( $i = 0; $i < count( $colors ); $i++ ) {
							echo '<option ';
							if( $value == $colors[$i] ) echo "selected ";
							echo 'class="color_select" value="'.$colors[$i].'">'.$lang["publications"][ $colors[$i] ].'</option>';
							}
						
						?>
						</select>
					</td>
				</tr>
			</table>
		</div>
	</td></tr>
	<tr>
		<td colspan='2' style='cursor: pointer; padding-left: 15px; font-family: myriad_bold;' align="left" colspan="2" height='25px' onclick="toggle_div('part2', jQuery('div', this) );"><div id="part2_t" class="toggler" style="width: 70px; padding-left: 26px;"><input style='color: #FFF; background: transparent; font-size: 14px; border: 0px;' type='text' readonly id='part2_name' name='part2_name' value='<?= $lang["publications"]["Inside"] ?>'></div></td>
	</tr>
	<tr><td colspan="2">
		<div id="part2" style="display: none;">
			<table class='panelTable' cellspacing='0' cellpadding='0'>
				<tr>
					<td style='padding-left: 15px;' align='left' height='28px'><?= $lang["publications"]["position"] ?></td>
					<td align='left'><input type='text' onchange='check_place( $(this) )' id='part2_place' name='part2_place' style='width: 100px;'><span style='padding-left: 20px; color: #FF0000; font-size: 14px;' id="part2_place_result"></span></td>
				</tr>
				<tr>
					<td style='padding-left: 15px;' align='left' height='28px'><?= $lang["publications"]["color"] ?></td>
					<td align='left'>
						<select name="part2_color">
						<?
						$colors = array( 'FOGRA_39', 'FOGRA_41', 'FOGRA_45', 'FOGRA_46', 'FOGRA_47', 'FOGRA_51', 'FOGRA_52' , 'IFRA_26' );
						$value = $newxml->Item[$x]->ColorManagement->Content;
						
						for( $i = 0; $i < count( $colors ); $i++ ) {
							echo '<option ';
							if( $value == $colors[$i] ) echo "selected ";
							echo 'class="color_select" value="'.$colors[$i].'">'.$lang["publications"][ $colors[$i] ].'</option>';
							}
						?>
						</select>
					</td>
				</tr>
			</table>
		</div>
	</td></tr>
	<tr>
		<td colspan='2' style='cursor: pointer; padding-left: 15px; font-family: myriad_bold;' align="left" colspan="2" height='25px' onclick="create_part();"><div id="t6" class="toggler" style="width: 140px;"><?= $lang["publications"]["new_part"] ?><input type='hidden' id='counter' name='counter' value='1,2'></div></td>
	</tr>
</table>

<script>
var parts = 2;

function check_place( current ) {
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
		
		var msg = '<?= $lang["publications"]["collision"] ?>: '+$('#'+g[0]+'_name').val();
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

function remove_part( nr ) {
	var prts = $('#counter').val().split(',');
	
	for( var i = 0; i < prts.length; i++ ) {
		if( nr == prts[i] ) {
			break;
			}
		}	
	prts.splice( i,1);

	$('#counter').val( prts.join() );
	$('#part'+nr).hide('fast');
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
	
	var txt = "<tr id='p"+parts+"'><td colspan='2' style='padding-left: 15px; font-family: myriad_bold;' align='left' colspan='2' height='25px' id='"+parts+"'>";
		txt += "<div onclick=\"toggle_div('part"+parts+"', $(this) );\" id='part"+parts+"_t' class='toggler' style='cursor: pointer; float: left; width: 70px;'><input style='background: transparent; color: #FFF; font-size: 14px; border: 0px;' type='text' id='part"+parts+"_name' name='part"+parts+"_name' value='<?= $lang['publications']['new_part2'] ?>'></div>";
		txt += "<div style='float:right;'><img onclick='remove_part("+parts+")' style='cursor: pointer;' src='../images/trash.png' height='18px'></div>";
	txt += "</td></tr>";
	txt += '<tr><td colspan="2"><div id="part'+parts+'" style="display: none;"><table cellspacing="0" cellpadding="0">';
	txt += "<tr>";
	txt += "<td style='padding-left: 15px;' align='left' height='28px'><?= $lang['publications']['position'] ?></td>";
	txt += "<td align='left' style='margin-left: -40px;'><input placeholder='' type='text' onchange='check_place( $(this) )' id='part"+parts+"_place' name='part"+parts+"_place' style='width: 100px;' value=''><span style='padding-left: 20px; color: #FF0000; font-size: 14px;' id=\"part"+parts+"_place_result\"></span></td>";
	txt += "</tr>";
	txt += "<tr>";
		txt += "<td style='padding-left: 15px;' align='left' height='28px'><?= $lang['publications']['color'] ?></td>";
		txt += "<td align='left'>";
			txt += '<select name="part'+parts+'_color">';
				txt += '<option class="color_select" value="FOGRA_39"><?= $lang["publications"]["FOGRA_39"] ?></option>';
				txt += '<option class="color_select" value="FOGRA_41"><?= $lang["publications"]["FOGRA_41"] ?></option>';
				txt += '<option class="color_select" value="FOGRA_45"><?= $lang["publications"]["FOGRA_45"] ?></option>';
				txt += '<option class="color_select" value="FOGRA_46"><?= $lang["publications"]["FOGRA_46"] ?></option>';
				txt += '<option class="color_select" value="FOGRA_47"><?= $lang["publications"]["FOGRA_47"] ?></option>';
				txt += '<option class="color_select" value="FOGRA_51"><?= $lang["publications"]["FOGRA_51"] ?></option>';
				txt += '<option class="color_select" value="FOGRA_52"><?= $lang["publications"]["FOGRA_52"] ?></option>';
				txt += '<option class="color_select" value="IFRA_26"><?= $lang["publications"]["IFRA_26"] ?></option>';
			txt += "</select>";
		txt += "</td>";
	txt += "</tr>";
	txt += '</table></div></td></tr>';
	
	var counter = $('#counter').val()+','+parts;
	
	$('#counter').val( counter );
	
	$('#part_list tr:last').before( txt );
	
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

$('.color_select').each( function() {
	$(this).tipTip();
	});
</script>