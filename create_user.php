<?
	if( isset( $_POST['user_groups'] ) ) {
		$i = 0;
		$names = array();
		$values = array();
		foreach( $_POST as $key => $value ) {
			if( $i > 0 ) {
				$names[] = $key;
				$values[] = $value;
				}
			$i++;
			}
		$group = sql_get( 'user_groups', 'name="'.$_POST['name'].'"', 'id' );
		if( $group[0][0] == '' ) {
			sql_add( 'user_groups', $names, $values );
			}
		}
	
	if( isset( $_POST['creator'] ) ) {
		$names = array( 'name', 'pass', 'publisher', 'group' );
		$values = array( $_POST['u_name'], password_hash($_POST['u_password'], PASSWORD_DEFAULT), $_POST['u_publisher'], $_POST['u_type'] );
		
		sql_add( 'accounts', $names, $values );
		$ok = 1;
		$e_code = 'A felhasználó sikeresen létrehozva.';
		}

?>

<div id="box_wrapper">
	<div id="msg_box"></div>
</div>

<form id='creator' name='creator' method='post' action='?page=create_user' autocomplete="off">
<input type='hidden' name='creator' value='creator'>
<table width='600px' id='job_names' cellspacing='0' cellpadding='0'>
	<thead>
		<tr>
			<td colspan='2' style='padding-left: 10px;' class='left top right bottom2' align="left" height='25px'>Új felhasználó létrehozása</td>
		</tr>	
	</thead>
	<tbody>
 	 	<tr>
 			<td style='padding-left: 10px;' class='two left bottom' align='left' width='50%' height='28px'>Kiadó</td>
 			<td class='two right bottom' align='left'>
				<select name='u_publisher'>
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
			<td style='padding-left: 10px;' class='left bottom' align='left' height='28px'>Felhasználónév</td>
			<td class='right bottom' align='left' style='padding-left: 2px;'><input type='text' autocomplete="off" name='u_name' style='width: 200px;' value=""></td>
		</tr>
		<tr>	
			<td style='padding-left: 10px;' class='two left bottom' align='left' height='28px'>Jelszó</td>
			<td class='two right bottom' align='left' style='padding-left: 2px;'><input type='password' autocomplete="off" name='u_password' style='width: 200px;' value=""></td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='left bottom' align='left' height='28px'>Felhasználói csoport</td>
			<td class='right bottom' align='left' style='padding-top: 0px;'>
				<select name='u_type'>
					<?
						$groups = sql_get( 'user_groups', '1 ORDER BY `name` ASC', 'id, name' );
						for( $i = 0; $i < count( $groups ); $i++ ) {
							echo "<option value='".$groups[$i][0]."'>".$groups[$i][1]."</option>";
							}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td style='background: #E6E8EB;' class='left right bottom' colspan='2' height='34px'><button id='submitter' disabled style='padding: 5px 20px 5px 20px;'>Létrehoz</button></td>
		</tr>
	</tbody>
</table>
</form>

<div id='spacer'></div> 

<form id='user_groups' name='user_groups' method='post' action='?page=create_user' autocomplete="off">
<input type='hidden' name='user_groups' value='user_groups'>
<table width='600px' id='job_names' cellspacing='0' cellpadding='0'>
	<thead>
		<tr>
			<td colspan='2' style='padding-left: 10px;' class='left top right bottom2' align="left" height='25px'>Új felhasználói csoport</td>
		</tr>	
	</thead>
	<tbody>
		<tr>
			<td height='25px' width='55%' class='two' align='left' style='padding-left: 10px;'>Csoport neve</td>
			<td class='two' align='left'>
				<input type='text' name='name'>
			</td>
		</tr>
		<?
			$row_names = sql_get( 'INFORMATION_SCHEMA.COLUMNS', 'TABLE_NAME = "user_groups"', 'COLUMN_NAME' );
			for( $i = 2; $i < count( $row_names ); $i++ ) {
				if( fmod( $i, 2 ) == 0 ) {
					$class = 'one';
					}
				else {
					$class = 'two';
					}				
				echo "<tr>";
					echo "<td height='25px' width='55%' class='".$class."' align='left' style='padding-left: 10px;'>".$lang["user_groups"][$row_names[$i][0]]."</td>";
					echo "<td class='".$class."' align='left' >";
						echo "<input type='checkbox' name='".$row_names[$i][0]."' value='1'>";
					echo "</td>";
				echo "</tr>";
				}
		?>
		<tr>
			<td style='background: #E6E8EB;' class='left right bottom' colspan='2' height='34px'><button style='padding: 5px 20px 5px 20px;'>Létrehoz</button></td>
		</tr>		
	</tbody>
</table>
</form>

<script>
<?PHP
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

$('form :input').keyup(function(){
	var counter = $('input[value!=""]').length;
	if( counter == $('input').length ) {
		$("#submitter").removeAttr("disabled");
		}
	else {
		  $("#submitter").attr("disabled", "disabled");
		}
	});
</script>