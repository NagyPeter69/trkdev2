<?php
$_GET["data"] = explode( "|", $_GET["data"] );

$pub = sql_aget( "publications", "id='".$_GET["data"][0]."'", "*" );
$mag = sql_aget( "magazines", "id='".$pub[0]["magazine_id"]."'", "*" );
$fp = sql_aget( "flatplan_planner", "id='".$_GET["data"][1]."'", "*" );

?>
<form id='subForm2' method='post' action=''>
<div>
	<div class='panelTitle'>Assets – <?= $fp[0]["name"] ?> – <?= $mag[0]["code"] ?> <?= $pub[0]["code"] ?></div>
	<div class='panelControl' style='width: 560px;'>
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tr>
				<td>Fájl kiválasztása: <input type="file" id="file" name="file"><span onclick="fileUpload()">[UPLOAD]</span></td>
				<td align="right" class="fp-up-box" style="visibility: hidden;"><div style="width: 150px; border: 1px solid #CCC; height: 15px;"><div class="fup-bar" style="background-color: #FFF; height: 100%; width: 0px;"></div></div></td>
				<td algin="left" class="fp-up-box" style="visibility: hidden; padding-left: 5px;"><div class="fup-percent"></div></td>
			</tr>
			<tr>
				<td colspan="3" style="padding-top: 15px;">Feltöltött fájlok</td>
			</tr>
			<tr>
				<td>Név</td>
				<td>Feltöltés ideje</td>
				<td>&nbsp;</td>
			</tr>
			
			<tr>
				<td colspan="3" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'flatplan_fileupload', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["close"] ?></div>
				</td>
			</tr>			
		</table>
	</div>
</div>		
		
</form>