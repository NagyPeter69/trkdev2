<form id='subForm' method='post' action=''>
<input type="hidden" id="mid" name="mid" value="<?= $_GET["data"]; ?>">

<div>
	<div class='panelTitle'><?= $lang["calendar"]["def_spec_days_title"] ?></div>
	<div class='panelControl' style='width: 390px; text-align: left;'>
		<div id="settings">
			<table class='panelTable' cellspacing='0' cellpadding='0'>
				<tr>
					<td align='left' style="height: 23px;"><?= $lang["calendar"]["name"] ?></td>
					<td><input type="text" name="name"></td>
				</tr>
				<tr>
					<td align='left' style="height: 23px;"><?= $lang["calendar"]["start"] ?></td>
					<td><input type="text" name="start" id="from"></td>
				</tr>
				<tr>
					<td align='left' style="height: 23px;"><?= $lang["calendar"]["end"] ?></td>
					<td><input type="text" name="end" id="to"></td>
				</tr>
				<tr>
					<td colspan="2" align="center" style="padding-top: 20px;">
						<div onclick="closePanel( 'mwcalendar_define', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
						<div onclick="menuApply( 'mwcalendar', 'define', 'define' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
					</td>
				</tr>							
			</table>
		</div>
	</div>
</div>
</form>

<script>
  $( function() {
    var dateFormat = "yy-mm-dd",
      from = $( "#from" )
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
      to = $( "#to" ).datepicker({
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