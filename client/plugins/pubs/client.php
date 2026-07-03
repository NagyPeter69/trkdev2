<form id='subForm' method='post' action=''>
<div>
	<div class='panelTitle'><?= $lang["publications"]["addClient"] ?></div>
	<div class='panelControl' style='width: 215px;'>
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<tr>
			<td><?= $lang["publications"]["clientname"] ?></td>
			<td><input type='text' name='clientname' id='clientname'></td>
		</tr>
		<tr>
			<td colspan="2" align="center" align="center" style="padding-top: 10px;">
				<div onclick="closePanel( 'pubs_client', 'back' )" style="margin-left: 10px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div onclick="menuApply( 'pubs', 'client' )" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["create"] ?></div>
			</td>
		</tr>		
	</table>
</div>
</form>