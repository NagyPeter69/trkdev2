<form id='subForm' method='post' action=''>

<div style="width: 700px;">
	<div class='panelTitle'><?= $lang["settings"]["find_account"] ?></div>
	<div class='panelControl' style="width: 100%;">

	<table class='panelTable' style='width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td align='left' style='width: 90px;'><?= $lang["settings"]["username"] ?>/<?= $lang["settings"]["email"] ?></td>
				<td>
					<input type='text' autocomplete="off" id='findAccount_term' style='width: 220px;' value="">
					<div class="panelButton" style="display: inline-block; margin-left: 6px;" onclick="findAccountList( $('#findAccount_term').val() )"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>
		</tbody>
	</table>
	<table class='panelTable' style='width: 100%; padding-top: 10px;' id='job_names' cellspacing='0' cellpadding='0'>
		<thead style="font-weight: bold;">
			<tr style="padding-bottom: 1px;">
				<td style='padding-bottom: 1px;'>id</td>
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["fullname"] ?></td>
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["username"] ?></td>
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["email"] ?></td>
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["publisher"] ?></td>
				<td style='padding-bottom: 1px;'><?= $lang["settings"]["lastlogin"] ?></td>
				<td style='padding-bottom: 1px;'></td>
			</tr>
		</thead>
		<tbody id="findAccountlist">
		</tbody>
	</table>

	</div>

	<table class='panelTable' style='margin-top: 20px; width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tr>
			<td align='center'>
				<div onclick="closePanel( 'accounts_findAccount', 'back')" class="panelButton" style="float:inherit; display: inline-block;"><?= $lang["standard"]["cancel"] ?></div>
			</td>
		</tr>
	</table>
	</div>
</div></form>
