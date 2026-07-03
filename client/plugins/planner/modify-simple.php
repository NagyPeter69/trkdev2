<?php

$pub = sql_aget( "publications", "id='".$_GET["pubid"]."'", "*" );
$cikk = sql_aget( "flatplan_planner", "pub_id='".$_GET["pubid"]."' AND name ='".$articles[0]["name"]."' AND mixed='0' order by pos asc", "*" );
$pages = $pub[0]["pages"];
$sequence = count( $cikk );

$freePages = array();
for( $i = 1; $i <= $pages; $i++ ) {
	$allowed = true;
	$check = sql_aget( "flatplan_planner", "pub_id='".$_GET["pubid"]."' AND pos='".$i."'", "*" );

	if( empty( $check[0]["id"] ) or $check[0]["name"] == $cikk[0]["name"] ) {
		for( $x = 1; $x < $sequence; $x++ ) {
			$check2 = sql_aget( "flatplan_planner", "pub_id='".$_GET["pubid"]."' AND pos='".( $x + $i )."'", "*" );
			
			if( !empty( $check2[0]["id"] ) ) {
				if( $check2[0]["name"] != $cikk[0]["name"] ) {
					$allowed = false;
					break;
					}
				}
			else if( ( $x + $i ) > $pages ) {
				$allowed = false;
				break;
				}
			
			// && ( $x + $i ) >= $pages && $check[0]["pos"] != $cikk[0]["pos"]
			}		

		if( $allowed ) {
			$freePages[] = $i;
			}
		}
	}

?>

<div id="mainWindow">
	<form id='subForm3' method='post' action=''>

	<div class='panelTitle'><?= ucfirst( $_GET["data"] ) ?> Article</div>
	<div class='panelControl' style='width: 450px !important; min-width: 450px !important;'>
	
	<input type="hidden" id="pubid" name="pubid" value="<?= $_GET["pubid"]; ?>">
	<input type="hidden" id="aname" name="aname" value="<?= $articles[0]["name"] ?>">
	<input type="hidden" id="mod" name="mod" value="<?= $_GET["data"] ?>">
	<?php if( $_GET["data"] == "modify" ) { ?>
		<input type="hidden" id="slots" name="slots" value="<?= $slots ?>">
	<?php } ?>
	
	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr class='planner_table_row planner_row_1'>
				<td align='left' height='23px' colspan="8">
					Content Type
					<select name="content_type" id="content_type">
						<?php
						$status = array( "article"=>"Editorial", "promo"=>"Promotional", "ad"=>"Advertisement", "mixed"=>"Mixed" );
						
						foreach( $status as $key=>$value ) {
							echo '<option '.( $key == $articles[0]["type"] ? "selected" : "" ).' value="'.$key.'">'.$value.'</option>';
							}
						?>
					</select>
				</td>
				 
				<td align='right' height='23px' colspan="2">
					Status
					<select name="a_status" id="a_status">
						<?php
						$status = array( "defined"=>"Defined", "progress"=>"In progress", "waiting"=>"Waiting", "finished"=>"Finished", "error"=>"Error" );
						
						foreach( $status as $key=>$value ) {
							echo '<option '.( $key == $articles[0]["status"] ? "selected" : "" ).' value="'.$key.'">'.$value.'</option>';
							}
						?>
					</select>
				</td>
			</tr>
			
			<tr class='planner_table_row planner_row_2'>
				<td align='left' height='23px' colspan="8">
					Title
					<input type='text' autocomplete="off" id='name' name='name' style='width: 200px;' value="<?= $articles[0]["name"] ?>">
				</td>
				<?php if( $_GET["data"] == "create" ) { ?>
					<td colspan="2" align="right">
						Slots
						<input type='text' autocomplete="off" id='slots' name='slots' style='width: 45px;' value="<?= $slots ?>">
					</td>
				<?php } else { ?>
					<td colspan="2" align="right">
						Slots
						<select id="start" name="start" onchange="endPage()">
						<?php
						for( $i = 0; $i < count( $freePages ); $i++ ) {
							echo "<option ".( $cikk[0]["pos"] == $freePages[$i] ? "selected" : "" )." value='".$freePages[$i]."'>".$freePages[$i]."</option>";
							}
						?>
						</select> –	<select id="end" name="end"></select>
					</td>				
				<?php } ?>
			</tr>

			<tr class='planner_table_row planner_row_3'>
				<td id='atype_box' align='left' height='23px' colspan="6">
					Article Type
					<select id='atype' name='atype'>
						<?php
						$types = sql_aget( "flatplan_articletypes", "pub_id='".$_GET["pubid"]."' order by id ASC", "*" );	
						
						for( $i = 0; $i < count( $types ); $i++ ) {
							echo "<option ".( $articles[0]["atype"] == $types[$i]["id"] ? "selected" : "" )." value='".$types[$i]["id"]."'>".$types[$i]["name"]."</option>";
							}
						?>
					</select>
				</td>

				<td id='workerID_box' align='left' height='23px' colspan="4" style="padding-left: 0px;">
					Designer
					<select name="workerID" id="workerID">
						<option value='0'>---------------</option>
						<?php
						
						$users = array();
						$array = sql_aget( "accounts", "publisher='".$pub[0]["publisher_id"]."' order by full_name ASC", "*" );
						
						for( $i = 0; $i < count( $array ); $i++ ) {
							$temp = explode( ",", $array[$i]["showMagazines"] );
							if( in_array( $pub[0]["magazine_id"], $temp ) ) {
								$users[] = $array[$i];
								}
							}
						for( $i = 0; $i < count( $users ); $i++ ) {
							echo "<option ".( $users[$i]["id"] == $articles[0]["workerID"] ? "selected" : "" )." value='".$users[$i]["id"]."'>".$users[$i]["full_name"]."</option>";
							}
						?>					
					</select>
				</td>
			</tr>
			
			<tr class='planner_table_row planner_row_4'>
				<td align='left' height='23px' colspan="6">
					Projected Time to Complete <?= $time ?> mins
				</td>
				<td align='left' height='23px' colspan="4">
					Time Spent <input type="text" name="tspent" value="<?= $articles[0]["tspent"] ?>" style="width: 30px;"> mins
				</td>
			</tr>

			<tr class='planner_table_row planner_row_5'>
				<td align='left' height='23px' colspan="2">Required Assets</td>
				<td align='left' height='23px' colspan="2">
					Text
					<input onclick="checkAvailable( 'text' )" type="checkbox" name="r_text" id="r_text" value="1" <?= ( $articles[0]["text"] == "1" ? "checked" : "" ) ?> >
				</td>
				<td align='left' height='23px' colspan="2">
					Image
					<input onclick="checkAvailable( 'image' )" type="checkbox" name="r_image" id="r_image" value="1" <?= ( $articles[0]["image"] == "1" ? "checked" : "" ) ?> >
				</td>
				<td align='left' height='23px' colspan="2">
					Other
					<input onclick="checkAvailable( 'other' )" type="checkbox" name="r_other" id="r_other" value="1" <?= ( $articles[0]["other"] == "1" ? "checked" : "" ) ?> >
				</td>
			</tr>

			<tr class='planner_table_row planner_row_6'>
				<td align='left' height='23px' colspan="2">Available Assets</td>
				<td align='left' height='23px' colspan="2">
					<div id="a_text">
						Text
						<input type="checkbox" name="have_text" id="have_text" value="1" <?= ( $articles[0]["have_text"] == "1" ? "checked" : "" ) ?> >
					</div>
				</td>
				<td align='left' height='23px' colspan="2">
					<div id="a_image">
						Image
						<input type="checkbox" name="have_image" id="have_image" value="1" <?= ( $articles[0]["have_image"] == "1" ? "checked" : "" ) ?> >
					</div>
				</td>
				<td align='left' height='23px' colspan="2">
					<div id="a_other">
						Other
						<input type="checkbox" name="have_other" id="have_other	" value="1" <?= ( $articles[0]["have_other"] == "1" ? "checked" : "" ) ?> >
					</div>
				</td>
			</tr>

			<tr class='planner_table_row planner_row_7'>
				<td align='left' height='23px' colspan="10">Remarks</td>
			</tr>
			<tr class='planner_table_row planner_row_8'>
				<td align='left' height='23px' colspan="10">
					<textarea name="remark" id="remark" style="resize: none; width: 444px; height: 60px;"><?= stripslashes( $articles[0]["remark"] ) ?></textarea>
				</td>
			</tr>			
		</tbody>
	</table>

<?php if( $_GET["data"] !== "create" ) { ?>	
	<table id="assets" class='panelTable' cellspacing='0' cellpadding='0' style='margin-top: 20px;'>
		<thead>
			<tr>
				<td colspan="3" style="background-color: transparent; color: #FFF; padding-bottom: 0px;">Assets</td>
			</tr>
		</thead>
		
		<tbody id="fileupload_uploaded">
		<?php

		$files = sql_aget( "flatplan_files", "articlename='".$articles[0]["name"]."' ORDER BY 'origname' ASC", "*" );
		
		for( $i = 0; $i < count( $files ); $i++ ) {
			$txt .= "<tr>";
				$txt .= "<td colspan='2' style='padding-left: 0px; font-size: 14px; padding-top: 2px; color: #CCC'>".$files[$i]["origname"]."</td>";
				$txt .= "<td align='right' style='padding-left: 0px; padding-right: 3px; padding-top: 2px; font-size: 16px;'>
							<span onclick='fpfiledownload( \"".$files[$i]["id"]."\" )' style='cursor: pointer;'><i class='fas fa-download'></i></span>
							<span onclick='fpfileremove( \"".$files[$i]["id"]."\", \"".$files[$i]["origname"]."\" )' style='cursor: pointer;'><i class='far fa-times-circle' style='color: #D22A33;'></i></span>
						 </td>";
				$txt .= "</tr>";
			}
			
		echo $txt;
		?>
		</tbody>		
	</table>
	
	<table id="assetsTable" class='panelTable' cellspacing='0' cellpadding='0'>
		<tr>
			<td style="padding-top: 7px;">
				<span id="select-file">
					<i onclick="$('#afile').click()" class="fas fa-upload" style='font-size: 16px; cursor: pointer; margin-right: 7px;'></i>
					<span id="targetfile" style="font-size: 20px; display: none;">
						<span id="currentFileName" style="font-size: 13px; margin-right: 5px;"></span><i onclick="window.parent.frames[0].fileUpload()" class="fas fa-file-upload" style="cursor: pointer;"></i>
					</span>
					<input onchange="currentFile()" type="file" id="afile" name="afile" style="visibility: hidden;">
				</span>
				<span id="selected-file" style="display: none;"></span>
				
				
			</td>
			<td align="right" class="fp-up-box" style="visibility: hidden; padding-top: 7px;">
				<div style="width: 150px; border: 1px solid #CCC; height: 15px; text-align: left;">
					<div class="fup-bar" style="background-color: #FFF; height: 100%; width: 0px;"></div>
				</div>
			</td>
			
			<td algin="left" class="fp-up-box" style="visibility: hidden; padding-left: 5px; padding-top: 7px;">
				<div class="fup-percent" style="width: 40px;"></div>
			</td>
		</tr>
	</table>
<?php } ?>
	
	<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tr>
				<td colspan="10" align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'planner_modify', 'back')" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton">Cancel</div>
					<div id='plannersave' onclick="modifyArticle()" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Save</div>
				</td>
			</tr>
	</table>						

						
	</div>
	
	</form>
</div>

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

<script>

function endPage() {
	var start = $("#start").val();
	
	$.ajax ({
		url:"engine/flatplan_ajax.php?op=endpage&start="+start+"&name="+$("#aname").val()+"&pubid=<?= $_GET["pubid"] ?>",
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#end").html( data );
			}
		});		
	}
endPage();

</script>