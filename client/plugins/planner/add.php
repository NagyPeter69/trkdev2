<form id='subForm2' method='post' action=''>
<div>
	<div class='panelTitle'>New Article</div>
	<div class='panelControl'>
	
	<input type="hidden" id="pubid" name="pubid" value="<?= $_GET["pubid"]; ?>">
	
	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td align='left' height='23px'>Name</td>
				<td align='left'>
					<input type='text' autocomplete="off" id='name' name='name' style='width: 200px;' value="">
				</td>
			</tr>

			<tr>
				<td align='left' height='23px'>Page Type</td>
				<td align='left'>
					<select id='type' name='type'>
						<option value="article">Article</option>
						<option value="ad">Advertise</option>
					</select>
				</td>
			</tr>

			<tr>
				<td align='left' height='23px'>Article Type</td>
				<td align='left'>
					<select id='atype' name='atype'>
						<?php
						$types = sql_aget( "flatplan_articletypes", "1 order by id ASC", "*" );	
						
						for( $i = 0; $i < count( $types ); $i++ ) {
							echo "<option value='".$types[$i]["id"]."'>".$types[$i]["name"]."</option>";
							}
						?>
					</select>
				</td>
			</tr>
						
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" align="left" style="padding-top: 10px;">
					<div onclick="closePanel( 'planner_add', 'back')" style="margin-left: 2px;" class="panelButton">Cancel</div>
					<div id='plannersave' onclick="saveArticle()" style="margin-left: 20px;" class="panelButton">Save</div>
				</td>
			</tr>			
		</tbody>
	</table>
						
	</div>
</div>

<script>
	
function saveArticle() {
	var cbox = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).val() );
		}); 
		
	$("#plannersave").addClass("btn-disabled");	
	$.ajax	({
		url:"plugins/plannerApply.php?sub=saveArticle",
		type: "POST",
		data: { data: cbox, settings: $("#subForm2").serialize() },
		dataType: 'json',
		success:function( data ) {	
			$("#plannersave").removeClass("btn-disabled");	
			if( data[0].length > 0 ) {
				for( var i = 0; i < data[0].length; i++ ) {
					$("#"+data[0][i]).css("background", "#D14550" );
					}
				}
			if( data[0] == "" ) {
				$("#planner_add").hide(200);
				loadArticles();
				}
			}
		});		
	}	
	
</script>

</form>