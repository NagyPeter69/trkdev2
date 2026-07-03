
<div id="box_wrapper">
	<div id="msg_box"></div>
</div>

<form enctype='multipart/form-data' action="" id='ad_upload' name='ad_upload' method='post'>
<table width='700px' id='job_names' cellspacing='0' cellpadding='0'>
	<thead>
		<tr>
			<td colspan='2' style='padding-left: 10px; padding-right: 10px;' class='left top right bottom2' align="left" height='25px'>
				<div style='float:left;'>Új hirdetés feltöltése</div>
			</td>
		</tr>
	</thead>
	<tbody>		
		<tr>
			<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Hirdetés neve</td>
			<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress="return alpha(event,numbers+letters)" type='text' id='job_code' name='job_code' style='width: 200px;'></td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Megjelenés</td>
			<td class='one right bottom' align='left' style='padding-left: 2px;'>
				<select id='pub' name='pub'>
				<?
					$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
					$sql = sql_get( 'publications', 'publisher_id="'.$sql[0][0].'" ORDER BY `code` ASC', '*' );
					for( $i = 0; $i < count( $sql ); $i++ ) {
						$magazine = sql_get( 'magazines', 'id="'.$sql[$i][2].'"', 'name' );
						echo "<option value='".$sql[$i][0]."'>".$sql[$i][10]." ( ".$magazine[0][0]." )</option>";
						}
				?>
				</select>
			</td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Hirdetés mérete</td>
			<td id='size' class='two right bottom' align='left' style='padding-left: 2px;'></td>
		</tr>
		<tr>
			<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Fájl</td>
			<td id='size' class='one right bottom' align='left' style='padding-left: 2px;'>
				<input id='file' name='file' type="file" class="file">
			</td>
		</tr>
		<tr>
			<td style='background: #E6E8EB;' class='left right bottom' colspan='2' height='34px'>
				<button onclick='pre_check(); return false;' id='create' disabled style='padding: 5px 20px 5px 20px;'>Feltölt</button>
			</td>
		</tr>
	</tbody>
</table>
</form>

<div id='spacer'>&nbsp;</div>

<div id="progress_info">
	<div id="progress"></div>
	<div style="display: none;" id="progress_percent">&nbsp;</div>
</div>

<script>
var letters=' ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
var numbers='1234567890';

$('#job_code').bind('paste', function(e) {
 e.preventDefault();
});

function alpha(e,allow) {
	var k;
	k=document.all?parseInt(e.keyCode): parseInt(e.which);
	
	
	if (k != 32) {
		return (allow.indexOf(String.fromCharCode(k))!=-1);
		}
	else {
		return false;
		}
	}

$('#pub').change(function() {
	$.ajax	({
		url:"engine/ajax.php",
		type: "GET",
		data: 'op=get_sizes&id='+$('#pub').val(),
		dataType: 'json',
		success:function( data ) {
			$('#size').html( data );
			}
		});		
	});

$('#pub').change();

$('#ad_upload :input').keyup(function(){
	var counter = $('#ad_upload input[value!=""]').length;
	if( counter == $('#ad_upload input').length ) {
		$("#create").removeAttr("disabled");
		}
	else {
		  $("#create").attr("disabled", "disabled");
		}
	});

$('#ad_upload :input').change(function(){
	var counter = $('#ad_upload input[value!=""]').length;
	if( counter == $('#ad_upload input').length ) {
		$("#create").removeAttr("disabled");
		}
	else {
		  $("#create").attr("disabled", "disabled");
		}
	});

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
</script>