<div id="mixedWindow" style="display: none;">
	<div class='panelTitle'>Define Mixed Content Layout for Page <span class="content_pages"></span></div>
	<div id="mixedWindowContent" class='panelControl' style='width: 450px !important; min-width: 450px !important; height: 340px;'>
		<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0' style="height: 100%;">
			<tr>
				<td style="width: 20%;">Parts</td>
				<td>
					<select name="mixed_parts" id="mixed_parts">
						<?php
						$status = array( "2"=>"2", "3"=>"3", "4"=>"4" );
						
						foreach( $status as $key=>$value ) {
							echo '<option '.( $key == $articles[0]["parts"] ? "selected" : "" ).' value="'.$key.'">'.$value.'</option>';
							}
						?>
					</select>					
				</td>
			</tr>

			<tr>
				<td valign="top" style="height: 100%;">Layout Type</td>
				<td align="center" style="height: 100%;"><div id="mixed_layouts"></div></td>
			</tr>
			
			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="returnWindow('mixedWindow')" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton">Back</div>
					<div onclick="mixedSelect()" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Next</div>
				</td>
			</tr>		
		</table>
	</div>	
</div>

<div id="mixedSelectedWindow" style="display: none;">
	<div class='panelTitle'>Define Mixed Content Parts for Page <span class="content_pages"></span></div>
	<div class='panelControl' style='width: 450px !important; min-width: 450px !important; height: 340px;'>
		<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0' style="height: 100%;">
			<tr>
				<td align="center" colspan="2" style="height: 100%;"><div id="mixedSelected_content"></div></td>
			</tr>
			
			<tr>
				<td colspan="2" align="center" style="padding-top: 10px;">
					<div onclick="returnWindow('mixedSelectedWindow')" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton">Back</div>
					<div onclick="mixedSave()" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Next</div>
				</td>
			</tr>		
		</table>
	</div>	
</div>