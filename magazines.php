<?
	if( isset( $_POST['e_name'] ) ) {
		$names = array( 'publisher_id', 'name', 'code', 'workflow', 'period', 'trimsize' );
		$size = $_POST['e_width'].'x'.$_POST['e_height'];
		$values = array( $_POST['e_publisher'], $_POST['e_name'], $_POST['e_code'], $_POST['e_workflow'], $_POST['e_period'], $size );
		
		$command = '';
		for( $i = 0; $i < count( $names ); $i++ ) {
			$command .= $names[$i].'=\''.$values[$i].'\'';
			
			if( $i < count( $names )-1 ) {
				$command .= ', ';
				}
			}	
			
		sql_update( 'magazines', $command, 'id=\''.$_POST['e_id'].'\'' );	
		$ok = 1;
		$e_code = 'A kiadvány sikeresen módosítva.';
		
		}
		
	if( isset( $_GET['remove_mag'] ) ) {
		$magazine = sql_get( 'magazines', 'id="'.$_GET['remove_mag'].'"', '*' );
		
		if( $magazine[0][0] != '' ) {
			sql_delete( 'magazines', 'id="'.$_GET['remove_mag'].'"' );
			$ok = 1;
			$e_code = 'A kiadvány sikeresen eltávolítva.';
			}
		}
		
	if( isset( $_POST['m_name'] ) ) {
		$names = array( 'publisher_id', 'name', 'code', 'workflow', 'period', 'trimsize' );
		$size = $_POST['width'].'x'.$_POST['height'];
		
		$values = array( $_POST['m_publisher'], $_POST['m_name'], $_POST['m_code'], $_POST['m_workflow'], $_POST['m_period'], $size );
		
		sql_add( 'magazines', $names, $values );
		$ok = 1;
		$e_code = 'A kiadvány sikeresen létrehozva.';
		}

?>

<div id="box_wrapper">
	<div id="msg_box"></div>
</div>

<div id='edit_magazine' style='display: none;'>
	<form id='edit_m' method='post' action='?page=magazines'>
	<table width='600px' id='job_names' cellspacing='0' cellpadding='0'>
		<thead>
			<tr>
				<td colspan='2' style='padding-left: 10px;' class='left top right bottom2' align="left" height='28px'>Kiadvány szerkesztése</td>
			</tr>			
		</thead>
		<tbody>
			<tr>
				<td style='padding-left: 10px;' class='left bottom' align='left' align='left' width='50%' height='28px'>Kiadó</td>
				<td class='right bottom' align='left'>
					<select name='e_publisher'>
					<?
					$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
					for( $i = 0; $i < count($publishers); $i++ ) {
						echo "<option value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
						}
					?>
		 			</select>
				</td>
			</tr>
			<tr>
				<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Kiadvány neve</td>
				<td class='two right bottom' align='left' style='padding-left: 2px;'>
					<input type="text" name="e_name" style='width: 200px;'>
				</td>
			</tr>
			<tr>
				<td style='padding-left: 10px;' class='left bottom' align='left' height='28px'>Kiadvány kódja</td>
				<td class='right bottom' align='left' style='padding-left: 2px;'>
					<input type="text" name="e_code" style='width: 200px;'>
				</td>
			</tr>
			<tr>
				<td style='padding-left: 10px;' class='two left bottom' align='left' height='28px'>Feldolgozás típusa</td>
				<td class='two right bottom' align='left'>
					<select name='e_workflow'>
						<option value='Full'>Full</option>
						<option value='Hybrid'>Hybrid</option>
						<option value='Repack'>Repack</option>
						<option value='Resize'>Resize</option>
						<option value='Enhance'>Enhance</option>
					</select>
			</td>
			</tr>
			<tr>
				<td style='padding-left: 10px;' class='left bottom' align='left' height='28px'>Megjelenés gyakorisága</td>
				<td class='right bottom' align='left'>
					<select name='e_period'>
						<option value='1'>1</option>
						<option value='2'>2</option>
						<option value='3'>3</option>
						<option value='undefined'>Egyéb</option>
					</select>
				</td>
			</tr>
			<tr>
				<td style='padding-left: 10px;' class='two left bottom' align='left' height='28px'>Kiadvány vágott mérete</td>
				<td class='two right bottom' align='left' style='padding-left: 2px;'>
					<input onkeypress="return isNumberKey(event)" type='text' id='e_width' name='e_width' style='width: 30px;'> x 
					<input onkeypress="return isNumberKey(event)" type='text' id='e_height' name='e_height' style='width: 30px;'>mm
					<input type='hidden' name='e_id' value=''>
				</td>
			</tr>
			<tr>
				<td style='background: #E6E8EB;' class='left right bottom' colspan='2' height='34px'><button id='submitter2' disabled style='padding: 5px 20px 5px 20px;'>Módosít</button><button onclick='$("#edit_magazine").hide("slow"); return false;' id='close' style='padding: 5px 20px 5px 20px;'>Mégse</button></td>
			</tr>
		</tbody>
	</table>
	</form>
	
	<div id='spacer'>&nbsp;</div>
</div>

<table width='1000px' id='job_names' cellspacing='0' cellpadding='0'>
	<thead>
		<tr>
			<td colspan='7' style='padding-left: 10px; padding-right: 10px;' class='left top right bottom2' align="left" height='28px'>
				<div style='float:left;'>Kiadványok</div>
				<div style='float:right;'>
					Kiadó:&nbsp;
					<select onChange="window.location.href='?page=magazines&p='+$(this).val()+''">
					<?
					$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
					for( $i = 0; $i < count( $publishers ); $i++ ) {
						echo "<option ";
						if( $_GET['p'] == $publishers[$i][0] ) echo ' selected ';
						echo "value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
						}
					?>
					</select>
				</div>
			</td>
		</tr>			
	</thead>
	<tbody>
		<tr>
			<td style='background: #E6E8EB;' width='15%' height='28px' class='left bottom2'><b>Név</b></td>
			<td style='background: #E6E8EB;' width='12%' height='28px' class='bottom2'><b>Kód</b></td>
			<td style='background: #E6E8EB;' width='14%' height='28px' class='bottom2'><b>Kiadó</b></td>
			<td style='background: #E6E8EB;' width='15%' height='28px' class='bottom2'><b>Feldolgozás típusa</b></td>
			<td style='background: #E6E8EB;' width='24%' height='28px' class='bottom2'><b>Megjelenés gyakorisága</b></td>
			<td style='background: #E6E8EB;' width='15%' height='28px' class='bottom2'><b>Vágott méret</b></td>
			<td style='background: #E6E8EB;' height='28px' class='right bottom2'>&nbsp;</td>
		</tr>
		<?
		
		if( isset( $_GET['p'] ) ) {
			$magazines = sql_get( 'magazines', 'publisher_id="'.$_GET['p'].'" ORDER BY `name` ASC', '*' );
			}
		else {
			$magazines = sql_get( 'magazines', 'publisher_id="'.$publishers[0][0].'" ORDER BY `name` ASC', '*' );
			}
		
		for( $i = 0; $i < count( $magazines ); $i++ ) {
			echo "<tr>";
				if( fmod( $i, 2 ) == 0 ) { $class = ' two'; }
				else { $class = ' one'; }				
				
				echo "<td height='28px' class='".$class." left bottom2'>".$magazines[$i][2]."</td>";
				echo "<td height='28px' class='".$class." bottom2'>".$magazines[$i][3]."</td>";
				$publisher = sql_get( 'publishers', 'id="'.$magazines[$i][1].'"', '*' );
				echo "<td height='28px' class='".$class." bottom2'>".$publisher[0][1]."</td>";
				echo "<td height='28px' class='".$class." bottom2'>".$magazines[$i][4]."</td>";
				echo "<td height='28px' class='".$class." bottom2'>".$magazines[$i][5]."</td>";
				echo "<td height='28px' class='".$class." bottom2'>".$magazines[$i][6]."mm</td>";
				echo "<td height='28px' class='".$class." right bottom2'>";
					echo '<img src="images/edit.png" height="18px" onclick="edit_magazine('.$magazines[$i][0].'); $(\'#content\').animate({ scrollTop: 0 }, \'slow\');" style="cursor:pointer; padding-right: 5px;">';
					echo '<a href="?page=magazines&remove_mag='.$magazines[$i][0].'" onclick="return confirm(\'Biztosan törli a kiválasztott kiadványt?\')"><img src="images/trash.png" height="18px"></a>';
				echo "</td>";
			echo "</tr>";
			}
		?>
	</tbody>
</table>

<div id='spacer'>&nbsp;</div>

<form id='new_magazine' method='post' action='?page=magazines'>
<table width='600px' id='job_names' cellspacing='0' cellpadding='0'>
	<thead>
		<tr>
			<td colspan='2' style='padding-left: 10px;' class='left top right bottom2' align="left" height='28px'>Új kiadvány hozzáadása</td>
		</tr>			
	</thead>
	<tbody>
		<tr>
			<td style='padding-left: 10px;' class='left bottom' align='left' align='left' width='50%' height='28px'>Kiadó</td>
			<td class='right bottom' align='left'>
				<select name='m_publisher'>
				<?
				$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
				for( $i = 0; $i < count($publishers); $i++ ) {
					echo "<option value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
					}
				?>
	 			</select>
			</td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Kiadvány neve</td>
			<td class='two right bottom' align='left' style='padding-left: 2px;'>
				<input type="text" name="m_name" style='width: 200px;'>
			</td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='left bottom' align='left' height='28px'>Kiadvány kódja</td>
			<td class='right bottom' align='left' style='padding-left: 2px;'>
				<input type="text" name="m_code" style='width: 200px;'>
			</td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='two left bottom' align='left' height='28px'>Feldolgozás típusa</td>
			<td class='two right bottom' align='left'>
				<select name='m_workflow'>
					<option value='Full'>Full</option>
					<option value='Hybrid'>Hybrid</option>
					<option value='Repack'>Repack</option>
					<option value='Resize'>Resize</option>
					<option value='Enhance'>Enhance</option>
				</select>
			</td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='left bottom' align='left' height='28px'>Megjelenés gyakorisága</td>
			<td class='right bottom' align='left'>
				<select name='m_period'>
					<option value='1'>1</option>
					<option value='2'>2</option>
					<option value='3'>3</option>
					<option value='undefined'>Egyéb</option>
				</select>
			</td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='two left bottom' align='left' height='28px'>Kiadvány vágott mérete</td>
			<td class='two right bottom' align='left' style='padding-left: 2px;'>
				<input onkeypress="return isNumberKey(event)" type='text' id='width' name='width' style='width: 30px;'> x 
				<input onkeypress="return isNumberKey(event)" type='text' id='height' name='height' style='width: 30px;'>mm
			</td>
		</tr>
		<tr>
			<td style='background: #E6E8EB;' class='left right bottom' colspan='2' height='34px'><button id='submitter' disabled style='padding: 5px 20px 5px 20px;'>Létrehoz</button></td>
		</tr>
	</tbody>
</table>
</form>

<br><br>

<script>
function edit_magazine( id ) {
	$.ajax	({
		url:"engine/ajax.php",
		data: 'op=edit_magazine&id='+id,
		dataType: 'json',
		success:function( data ) {
			var size = data[4].split('x');
			$('#edit_m input[name="e_name"]').val( data[0] );
			$('#edit_m input[name="e_code"]').val( data[1] );
			$('#edit_m select[name="e_workflow"]').val( data[2] );
			$('#edit_m select[name="e_period"]').val( data[3] );
			$('#edit_m input[name="e_width"]').val( parseInt( size[0] ) );
			$('#edit_m input[name="e_height"]').val( parseInt( size[1] ) );
			$('#edit_m input[name="e_id"]').val( data[5] );
			$('#edit_m select[name="e_publisher"]').val( data[6] );

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
</script>