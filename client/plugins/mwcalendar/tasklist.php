<?php

$settings = sql_aget( "calendar_settings", "publisher_id='".$_GET["data"]."' order by id ASC", "*" );
	
?>

<form id='subForm' method='post' action=''>
<input type="hidden" id="pid" name="pid" value="<?= $_GET["data"]; ?>">

<div>
	<div class='panelTitle'><?= $lang["calendar"]["task_lists_title"] ?></div>
	<div class='panelControl' style='width: 450px; text-align: left;'>
		<div id="settings">
			<table class='panelTable' cellspacing='0' cellpadding='0'>
				<tr>
					<td align='left' width='100%' height='28px'>
						<div><input type="checkbox" <?= ( $settings[0]["value"] != "-1" ? "checked" : "" ) ?> name="order_paper"><?= $lang["calendar"]["order_paper_1"] ?><input onkeypress="return isNumberKey(event)" type='text' name='order_paper_value' style='padding: 0; width: 17px;' value="<?= ( $settings[0]["value"] != "-1" ? $settings[0]["value"] : "" ) ?>"><?= $lang["calendar"]["order_paper_2"] ?></div>
						<div><input type="checkbox" <?= ( $settings[1]["value"] != "-1" ? "checked" : "" ) ?> name="order_printing" ><?= $lang["calendar"]["order_printing_1"] ?><input onkeypress="return isNumberKey(event)" type='text' name='order_printing_value' style='padding: 0; width: 17px;' value="<?= ( $settings[1]["value"] != "-1" ? $settings[1]["value"] : "" ) ?>"><?= $lang["calendar"]["order_printing_2"] ?></div>
						<div><input type="checkbox" <?= ( $settings[2]["value"] != "-1" ? "checked" : "" ) ?> name="define_issue" ><?= $lang["calendar"]["define_issue_1"] ?><input onkeypress="return isNumberKey(event)" type='text' name='define_issue_value' style='padding: 0; width: 17px;' value="<?= ( $settings[2]["value"] != "-1" ? $settings[2]["value"] : "" ) ?>"><?= $lang["calendar"]["define_issue_2"] ?></div>
						<div><input type="checkbox" <?= ( $settings[3]["value"] != "-1" ? "checked" : "" ) ?> name="product_remind" ><?= $lang["calendar"]["reminder_1"] ?><input onkeypress="return isNumberKey(event)" type='text' name='product_remind_value' style='padding: 0; width: 17px;' value="<?= ( $settings[3]["value"] != "-1" ? $settings[3]["value"] : "" ) ?>"><?= $lang["calendar"]["reminder_2"] ?></div>
					</td>
				</tr>
	
				<tr>
					<td colspan="2" align="center" style="padding-top: 20px;">
						<div onclick="closePanel( 'mwcalendar_tasklist', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
						<div onclick="menuApply( 'mwcalendar', 'tasklist', 'tasklist' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
					</td>
				</tr>				
			</table>
		</div>
	</div>
</div>
</form>