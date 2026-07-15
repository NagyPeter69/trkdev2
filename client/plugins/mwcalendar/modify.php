<?php

$order = sql_aget( "calendar_post", "id='".$_GET["data"]."'", "*" );
$check = issueChecker( $order[0]["magCode"], $order[0]["code"], "pubs" );

?>

<form id='subForm' method='post' action=''>
<input type="hidden" id="pid" name="pid" value="<?= $order[0]["id"]; ?>">

<div>
	<div class='panelTitle'><?= $lang["calendar"]["orderModify"] ?>:</div>
	<div class='panelControl' style='width: 390px; text-align: left;'>
		<div id="settings">
			<table class='panelTable' cellspacing='0' cellpadding='0'>
				<tr>
					<td align='left' width='50%' height='28px'><?= $lang["calendar"]["sales_day"] ?></td>
					<td align='left'>
						<input readonly class="datepicker" type="text" name='salesday' id='salesday' value='<?= $order[0]["salesDay"] ?>'>
					</td>
				</tr>
				<tr>
					<td align='left' width='50%' height='28px'><?= $lang["calendar"]["printorderday"] ?></td>
					<td align='left'>
						<input readonly class="datepicker" type="text" name='printorder' id='printorder' value='<?= $order[0]["printDay"] ?>'>
					</td>
				</tr>
				<?php if( $order[0]["magazine_id"] != 0 ) { ?>
					<tr>
						<td align='left' width='50%' height='28px'><?= $lang["calendar"]["issue_code"] ?></td>
						<td align='left'>
							<input type='text' id='job_code' name='job_code' value='<?= $order[0]["code"] ?>' <?= ( !empty( $check[0]["id"] ) ? "readonly" : "" ) ?> style='width: 40px;'>
						</td>
					</tr>
				<?php } ?>
				<tr>
					<td align='left' width='50%' height='28px'><?= $lang["publications"]["customname"] ?></td>
					<td align='left'><input onkeypress="return isAllowedKey2(event)" type='text' id='customname' name='customname' value='<?= $order[0]["specificName"] ?>' <?= ( !empty( $check[0]["id"] ) ? "readonly" : "" ) ?>></td>
				</tr>	
				<tr>
					<td align='left' width='50%' height='28px'><?= $lang["publications"]["length"] ?></td>
					<td align='left'><input onkeypress="return isNumberKey(event)" type='text' id='numofpages' name='numofpages' value='<?= $order[0]["numofpages"] ?>'></td>
				</tr>
				<tr>
					<td colspan="2" align="center" style="padding-top: 10px;">
						<div onclick="closePanel( 'mwcalendar_modify', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
						<div onclick="menuApply( 'mwcalendar', 'modify', 'modify' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
						<?php
						if( $order[0]["magazine_id"] != 0 ) {
						if( $user[0][8] == "2" or $user[0][8] == "8" ) { ?>

							<?php if( empty( $check[0]["id"]  ) ) { ?>
								<div id='issueDefine' onclick="issueDefine()" style="display: inline-block; float: none; margin-left: 20px; width: 135px;" class="panelButton"><?= $lang["calendar"]["define"] ?></div>
							<?php } ?>
						<?php } ?>
						<?php } ?>
					</td>
				</tr>				
			</table>
		</div>
	</div>
</div>
</form>

<script>
function issueDefine() {
	if( $("#numofpages").val() != "" ) {
		var num = parseInt( $("#numofpages").val() );
		if( num % 2 === 0 ) {
			$("#issueDefine").addClass("btn-disabled");
			var code = $("#job_code").val();
			var ev = code.substr(0, 2);
			var szam = code.substr(code.length - 2);
			
			var text = "publisher=<?= $order[0]["publisher_id"] ?>&magazine=<?= $order[0]["magazine_id"] ?>&ev="+ev+"&szam="+szam+"&job_code="+code+"&delimiter=_&proposed=&customname="+$("#customname").val()+"&page_nr="+$("#numofpages").val()+"&dl="+$("#printorder").val()+"&counter=1%2C2&m_id=<?= $order[0]["magazine_id"] ?>&i_variable=before&i_var_del=_&i_delimiter=&i_code=<?= $order[0]["magCode"] ?>&i_base="+code+"&i_padding=3&i_aname=No&i_adelimiter=&u_variable=before&u_var_del=_&u_delimiter=&u_code=<?= $order[0]["magCode"] ?>&u_base="+code+"&u_padding=3&u_aname=No&u_adelimiter=&o_variable=before&o_var_del=_&o_delimiter=&o_code=<?= $order[0]["magCode"] ?>&o_base="+code+"&o_padding=3&o_aname=No&o_adelimiter=";
			
			console.log( text );
			
			$.ajax({
				url:"plugins/mwcalendarApply.php?sub=issueDefine",
				type: "POST",
				data: { data : text },
				dataType: 'json',
				success:function( data ) {
					$("#issueDefine").remove();
	        		}
				});
			}
		else {
			alert( '"Number of Pages" must be divisible by two' );
			}
		}
	else {
		alert( 'Please fill the "Number of Pages" field' );
		}
	}	
	
  $( function() {
    var dateFormat = "yy-mm-dd",
      from = $( "#printorder" )
        .datepicker({
          defaultDate: "+1w",
          changeMonth: true,
          numberOfMonths: 2,
          dateFormat: "yy-mm-dd",
          firstDay: 1,
        })
        .on( "change", function() {
          to.datepicker( "option", "minDate", getDate( this ) );
        }),
      to = $( "#salesday" ).datepicker({
        defaultDate: "+1w",
        changeMonth: true,
        numberOfMonths: 2,
        dateFormat: "yy-mm-dd",
        firstDay: 1,
      })
      .on( "change", function() {
        from.datepicker( "option", "maxDate", getDate( this ) );
      });
 
    function getDate( element ) {
      var date;
      try {
        date = $.datepicker.parseDate( dateFormat, element.value );
      } catch( error ) {
        date = null;
      }
 
      return date;
    }
  } );
</script>