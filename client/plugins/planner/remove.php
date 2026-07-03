<form id='subForm2' method='post' action=''>
<div>
	<div class='panelTitle'>Csempék törlése</div>
	<div class='panelControl'>
	
	<input type="hidden" id="pubid" name="pubid" value="<?= $_GET["pubid"]; ?>">
	
	<table class='panelTable' width='100%' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td align='center' height='23px'>PLACEHOLDER Biztosan törölni szeretnéd a kiválasztott csempék adatait?</td>
			</tr>
			
			<tr>
				<td align="center" style="padding-top: 20px;">
					<div onclick="closePanel( 'planner_remove', 'back')" style="margin-left: 2px; display: inline-block; float: inherit;" class="panelButton">Cancel</div>
					<div id='plannersave' onclick="removeArticle()" style="margin-left: 20px; display: inline-block; float: inherit;" class="panelButton">Remove</div>
				</td>
			</tr>			
		</tbody>
	</table>
						
	</div>
</div>

<script>
	
function removeArticle() {
	var cbox = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).val() );
		}); 
		
	$("#plannersave").addClass("btn-disabled");	

	$.ajax	({
		url:"plugins/plannerApply.php?sub=removeArticle",
		type: "POST",
		data: { data: cbox, settings: $("#subForm2").serialize() },
		dataType: 'json',
		success:function( data ) {	
			$("#plannersave").removeClass("btn-disabled");	
			$("#planner_remove").hide(200);
			loadArticles();			
			}
		});		
	}	
	
</script>

</form>