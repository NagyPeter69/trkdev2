<form id='subForm' method='post' action=''>
<input type='hidden' name='info' value='<?= $_GET['data'] ?>'>
<table>
	<tr>
		<td>Start date</td>
		<td><input type='text' id='begin_date' name='begin_date' class='datepicker'></td>
	<tr>
	<tr>
		<td>End date</td>
		<td><input type='text' id='end_date' name='end_date' class='datepicker'></td>
	<tr>
	<tr>
		<td colspan="2" align="center" style="padding-top: 10px;">
			<div onclick="closePanel( 'logs_export', 'back', 'next_page' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
			<div onclick="menuApply( 'logs', 'export', 'next_page' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
		</td>
	</tr>
</table>
</form>