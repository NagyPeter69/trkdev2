<div>
	<div class='panelTitle'><?= $lang["settings"]["colorstandards"] ?></div>
	<div class='panelControl' style="width: auto !important;">
	<table class='panelTable' id='colorstandards_table' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td height='23px' align='left'><b><?= $lang["settings"]["colorstandard_name"] ?></b></td>
				<td align='left'><b><?= $lang["settings"]["colorstandard_profile"] ?></b></td>
				<td align='left'><b><?= $lang["settings"]["colorstandard_icc"] ?></b></td>
				<td></td>
			</tr>
			<?
			$standards = sql_get( "color_standards", "1 ORDER BY `name` ASC", "*" );
			for( $i = 0; $i < count( $standards ); $i++ ) {
				echo "<tr>";
					echo "<td height='23px' align='left'>".htmlspecialchars( $standards[$i][1] )."</td>";
					echo "<td align='left'>".htmlspecialchars( $standards[$i][2] )."</td>";
					echo "<td align='left'>".htmlspecialchars( $standards[$i][3] );
					if( !empty( $standards[$i][4] ) && $standards[$i][4] != $standards[$i][3] ) {
						echo "<br><span style='color: #888; font-size: 11px;'>".$lang["settings"]["colorstandard_original"].": ".htmlspecialchars( $standards[$i][4] )."</span>";
						}
					echo "</td>";
					echo "<td align='right'><div class='panelButton' style='padding: 2px 8px; display: inline-block;' onclick=\"deleteColorStandard(".$standards[$i][0].")\">".$lang["settings"]["colorstandard_delete"]."</div></td>";
				echo "</tr>";
				}
			if( count( $standards ) == 0 ) {
				echo "<tr><td colspan='4' align='left' style='color: #888;'>".$lang["settings"]["colorstandard_none"]."</td></tr>";
				}
			?>
		</tbody>
	</table>

	<div style='margin-top: 10px; border-top: 1px solid #636363; padding-top: 10px;'>
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tbody>
				<tr>
					<td height='23px' align='left' style='width: 185px !important;'><?= $lang["settings"]["colorstandard_name"] ?></td>
					<td align='left'><input type='text' id='cs_name' name='name' placeholder='FOGRA_39'></td>
				</tr>
				<tr>
					<td height='23px' align='left'><?= $lang["settings"]["colorstandard_profile"] ?></td>
					<td align='left'><input type='text' id='cs_profile' name='profile_label' placeholder='ISO Coated v2 300% (basICColor)'></td>
				</tr>
				<tr>
					<td height='23px' align='left'><?= $lang["settings"]["colorstandard_icc"] ?></td>
					<td align='left'><input type='file' id='cs_icc' name='icc_file' accept='.icc,.icm'></td>
				</tr>
				<tr>
					<td colspan='2' id='cs_error' style='color: #D14550;'></td>
				</tr>
				<tr>
					<td colspan="2" align="center" style="padding-top: 10px;">
						<div onclick="closePanel('colorstandards_list','off')" style="display: inline-block;" class="panelButton">Cancel</div>
						<div onclick="addColorStandard()" style="margin-left: 20px; display: inline-block;" class="panelButton"><?= $lang["settings"]["colorstandard_add"] ?></div>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
	</div>
</div>

<script>
function reloadColorStandards() {
	$.ajax({
		url: "engine/menuAjax.php?op=loadmenu&menu=colorstandards_list",
		type: "GET",
		dataType: 'json',
		success: function( data ) {
			$("#colorstandards_list").html( data[0] );
			}
		});
	}

function deleteColorStandard( id ) {
	$.ajax({
		url: "plugins/colorstandardsApply.php?sub=delete&id="+id,
		type: "GET",
		dataType: 'json',
		success: function( data ) {
			reloadColorStandards();
			}
		});
	}

function addColorStandard() {
	$("#cs_error").text("");

	var fd = new FormData();
	fd.append( "name", $("#cs_name").val() );
	fd.append( "profile_label", $("#cs_profile").val() );

	var fileField = document.getElementById("cs_icc");
	if( fileField.files.length > 0 ) {
		fd.append( "icc_file", fileField.files[0] );
		}

	$.ajax({
		url: "plugins/colorstandardsApply.php?sub=add",
		type: "POST",
		data: fd,
		contentType: false,
		processData: false,
		dataType: 'json',
		success: function( data ) {
			if( data.error ) {
				$("#cs_error").text( data.error );
				}
			else {
				reloadColorStandards();
				}
			}
		});
	}
</script>
