<form id='subForm' method='post' action=''>
<div>
	<div class='panelTitle'><?= $lang["publications"]["removeClient"] ?></div>
	<div class='panelControl' style='width: 215px;'>
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<tr>
			<td><?= $lang["publications"]["clientname"] ?></td>
			<td>
			<?php
			echo "<select name='remClient' id='remClient'>";
			
			$client = sql_aget( "publishers", "1 order by name ASC", "*" );
			for( $i = 0; $i < count( $client ); $i++ ) {
				echo "<option value='".$client[$i]["id"]."'>".$client[$i]["name"]."</option>";
				}
				
			echo "</select>";
			?>
			</td>
		</tr>
		<tr>
			<td colspan="2" align="center" align="center" style="padding-top: 10px;">
				<div onclick="closePanel( 'pubs_removeclient', 'back' )" style="margin-left: 10px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div onclick="removeClient()" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["remove"] ?></div>
			</td>
		</tr>		
	</table>
</div>
</form>

<script>

function removeClient(){
	var client = $("#remClient option:selected").text();
	
	if( confirm( "Are you sure want to remove the selected client ("+client+") ?") ) {
		var id = $("#remClient option:selected").val();
		$.ajax	({
			url:"plugins/pubsApply.php?sub=removeClient&id="+id,
			type: "GET",
			dataType: 'json',
			success:function( data ) {
				$("#pubs_removeclient").hide(200, function(){
					$(this).remove();
					});										
				}
			});		
		}
	}

</script>