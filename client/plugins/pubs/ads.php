<?
$magazine = sql_get( "magazines", "id='".$_GET['data']."'", "*" );
?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">
<input type="hidden" id="magazine" name="magazine" value="<?= $_GET['data']; ?>">
<input type='hidden' id='code' name='code' value='<?= $magazine[0][3] ?>'>

<div>
	<div class='panelTitle'><?= $lang["publications"]["ad"] ?></div>
	<div class='panelControl' style='width: 380px;'>

	<table id='ad_lst' style='width: 100%;' class='panelTable' cellspacing='0' cellpadding='0'>
		<tr>
			<? if( $rights["ad_sizes"] ) { ?>
			<td colspan='2' style='font-family: myriad_bold;' align="left" colspan="2" height='35px'>
				<div id="new_ads" style="position: relative; background: none; margin-left: -1px;">
					<select id='size' name='size'>
						<option value='1/1'>1/1</option>
						<option value='2/3'>2/3</option>
						<option value='1/2'>1/2</option>
						<option value='1/3'>1/3</option>
						<option value='1/4'>1/4</option>
						<option value='1/8'>1/8</option>
					</select>
					<select id='orient' name='orient'>
						<option value='portrait'><?= $lang["publications"]["portrait"] ?></option>
						<option value='landscape'><?= $lang["publications"]["landscape"] ?></option>
					</select>
					<select id='cover' name='cover'>
						<option value='satz'><?= $lang["publications"]["non_bleed"] ?></option>
						<option selected value='trim'><?= $lang["publications"]["bleed"] ?></option>
					</select>
						<input onkeypress="return isNumberKey2(event)" type='text' id='width' name='width' style='margin-left: 3px; width: 30px;'> x 
						<input onkeypress="return isNumberKey2(event)" type='text' id='height' name='height' style='width: 30px;'>&nbsp;mm
					<div style='float:right; font-size: 10px; margin-right: 35px; margin-top: 3px;'><?= $lang["publications"]["warn"] ?></div>
					<div style="float:right; position: absolute; right: 0; top: 2px;"><img onclick="add_advert(); return false;" style="cursor: pointer;" src="images/trk_plus.png" height="18px"></div>
				</div>
				
			</td>
			<? } ?>
		</tr>
	</table>
	<table id='ad_lst' style='width: 100%;' class='panelTable' cellspacing='0' cellpadding='0'>
		<tr>
			<td colspan='2' align="center" style="padding-top: 10px;">
				<div onclick="closePanel( 'pubs_ads', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="float: inherit;" class="panelButton">Ok</div>
			</td>
		</tr>
	</table>
	</div>
</div>
</form>

<script>
function load_ads( first ) {
	$.ajax	({
		url:"engine/ajax.php",
		type: "GET",
		data: 'op=load_ads&code='+$("[name='code']").val(),
		dataType: 'json',
		success:function( data ) {
			if( first == undefined )
				$("#ad_lst").find("tr:not(:last)").remove();
				
			$('#ad_lst tr:first').before( data );
			}
		});
	}
load_ads(1);
</script>