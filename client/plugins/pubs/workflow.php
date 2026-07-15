<?
$magazine = sql_get( "magazines", "id='".$_GET['data']."'", "*" );
$softproofpubs = array( "iPixel" );
$publisher = sql_get( "publishers", "id='".$user[0][4]."'", "name" );
?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">
<input type="hidden" id="magazine" name="magazine" value="<?= $_GET['data']; ?>">
<input type='hidden' id='code' name='code' value='<?= $magazine[0][3] ?>'>

<input type='hidden' id='OutputFormat_hidden' disabled name='OutputFormat' value='TIFF'>
<input type='hidden' id='PDFstandard_hidden' disabled name='PDFstandard' value='PDFX1A'>
<input type='hidden' id='ArchiveStorage_hidden' disabled name='ArchiveStorage' value='Client Area'>
<input type='hidden' id='WebImages_hidden' disabled name='WebImages' value='Yes'>
<?php
// LocalStorage left the UI entirely for Regular publications: every
// Regular magazine stores PubFolder. Adhoc's LocalStorage is chosen at
// creation time instead (known client -> PubFolder, unknown -> Adhoc
// folder - see pubsApply.php's "create" handler), so it's left alone
// here rather than clobbered on every settings save.
if( $magazine[0][10] == "Regular" ) {
	echo "<input type='hidden' name='LocalStorage' value='PubFolder'>";
	}
?>

<div>
	<div class='panelTitle'><?= $lang["publications"]["workflow"] ?> : <?= $magazine[0][2] ?></div>
	<div class='panelControl' style='width: 440px;'>

	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<?
			$mag = sql_get( 'magazines', 'id="'.$_GET['data'].'"', "*");
			$pubs = sql_get( 'publications', 'magazine_id="'.$mag[0][0].'" ORDER BY `code` ASC', '*' );
			for( $x = 0; $x < count( $pubs ); $x++ ) {
					$temp[] = $pubs[$x][10];
					}
			$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
			
			$avaiable = array( 'Type', 'Client', 'Name', 'Code', 'Mails', 'Workflow', 'Enhance', 'PageNumbering', 'CustomCode', 'Resolution', 'FlatplanStages', 'PDFstandard', 'ArchiveStorage', 'OutputFormat', 'WebImages', 'Language', 'ApprovedComments' );
			// E-mail only applies to Adhoc publications (Regular pubs have
			// no client mailing list here).
			if( $magazine[0][10] != "Adhoc" ) {
				$avaiable = array_diff( $avaiable, array( 'Mails' ) );
				}

			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $i = 0; $i < count( $temp->Item ); $i++ ) {
					if( $temp->Item[$i]->Code == $magazine[0][3] )
						break;
					}
				}				
			
			foreach( $avaiable as $key ) {
				$value = (string) $xml->Item[$i]->$key;
				$temp = array();
				switch( $key ) {
					case 'PageNumbering':
						$temp = array( 'American', 'European' );
						$default[$key] = "European";
						break;
					case 'Type':
						$temp = array( 'Adhoc', 'Regular' );
						break;
					case 'CustomCode':
						$temp = array( '1', '2', '3', 'No' );
						break;
					case 'Workflow':
						$temp = array( 'Full', 'Hybrid', 'Resize' );
						break;				
					case 'FlatplanStages':
						$temp = array( '1', '2', '3' );
						break;					
					case 'Enhance':
						$temp = array( 'Skintone', 'Food', 'Jewellery', 'Vivid', 'Paintings', 'Minimal', 'General', 'Resize only', 'Null' );
						$changeName = array( "Only Resize"=>"Null" );
						break;
					case 'PDFstandard':
						$temp = array( 'PDFX1', 'PDFX1A', 'PDFX4', 'Web' );
						break;
					case 'ArchiveStorage':
						$temp = array( 'Client Area', 'Temporary' );
						break;
					case 'Language':
						$temp = array( 'HU', 'EN' );
						break;
					case 'Period':
						$temp = array( '1', '2', '3', '4', 'undefined' );
						break;
					case 'Client':
						$pubs = sql_get( 'publishers', '1 ORDER BY `name` ASC ', '*' );
						for( $x = 0; $x < count( $pubs ); $x++ ) {
							$temp[] = $pubs[$x][1];
							}
						break;
					case 'Resolution':
						$temp = array( '100', '150', '200', '250', '300', '450', '600' );
						break;
					case 'OutputFormat':
						$temp = array( 'TIFF', 'JPEG', 'Original' );
						break;
					case 'WebImages':
						$temp = array( 'Yes', 'No' );
						break;	
						
					case 'ApprovedComments':
						$temp = array( 'Yes', 'No' );
						$value = $magazine[0][14];
						break;
					}
				
				echo "<tr class='".$key."' style='background-color: transparent;'>";
					if( $key == "Mails" ) echo "<td valign='top' align='left'>".$lang['xml'][$key]."</td>";
					else echo "<td align='left'>".$lang['xml'][$key]."</td>";
					echo "<td align='left'>";
						if( $key == 'Mails' ) {
							echo "<textarea id='".$key."' name='".$key."' style='width: 170px; height: 60px; resize: none;'>";
								echo str_replace( ";", "\n", $value );
							echo "</textarea>";

							$pub = sql_aget( "publishers", "name='".$xml->Item[$i]->Client."'", "*" );
							if( !empty( $pub[0]["id"] ) ) {
								$u = sql_aget( "accounts", "publisher='".$pub[0]["id"]."'", "*" );
								echo "<div>";
									echo "<div style='float: left;'><select id='addRUser' name='addRUser'>";
									for( $a = 0; $a < count( $u ); $a++ ) {
										echo "<option value='".$u[$a]["email"]."'>".$u[$a]["full_name"]."</option>";
										}
									echo "</select></div>";

									echo "<div style='float: left; margin-top: 4px; margin-left: 5px;'><img onclick='addEmail()' style='cursor: pointer;' src='images/trk_plus.png'></div>";
								echo "</div>";
								}
							}

						elseif( $temp == '' ) {
							echo "<input type='text' id='".$key."' name='".$key."' value='".$value."'>";
							}

						elseif( $key == 'Workflow' ) {
							echo "<select onchange='changeSettings()' style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								echo "<option ";
								if( $value == $t ) echo "selected ";
									echo "value='".$t."'>".$t."</option>";
								}
							echo "</select>";
							}
							
						elseif( $key == 'CustomCode' ) {
							$value = explode( "_", $value );
							if( count( $value ) == 1 ) {
								$value[1] = $value[0];
								$value[0] = '';
								}
							echo "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								echo "<option ";
								if( $value[1] == $t ) echo "selected ";
									echo "value='".$t."'>".$t."</option>";
								}
							echo "</select>";
							}

						elseif( $key == 'Type' ) {
							echo "<select disabled style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								echo "<option ";
								if( $value == $t ) echo "selected ";
									echo "value='".$t."'>".$t."</option>";
								}
							echo "</select>";
							}
							
						elseif($key == 'PageNumbering' ) {
							echo "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								echo "<option ";
								if( $value == $t ) echo "selected ";
									echo "value='".$t."'>".$t."</option>";
								}
							echo "</select>";
							}							
							
						elseif( $key == 'Name' ) {
							echo "<input type='text' id='".$key."' name='".$key."' value='".$value."'>";
							}

						elseif( $key == 'Code' ) {
							echo "<input disabled type='text' id='".$key."' name='".$key."' value='".$value."'>";
							}

						elseif( $key == 'Enhance' ) {
							echo "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								$tempVal = $t;
								if( !empty( $changeName[$t] ) ) {
									$tempVal = $changeName[$t];
									}
								echo "<option ";
								if( $value == $tempVal ) echo "selected ";
									echo "value='".$tempVal."'>".$t."</option>";
								}
							echo "</select>";
							}	
							
						else {
							echo "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								echo "<option ";
								if( $value == $t ) echo "selected ";
									echo "value='".$t."'>".$t."</option>";
								}
							echo "</select>";
							}
					echo "</td>";
				echo "</tr>";
				}
		?>
			<tr>
				<td></td>
				<td id="result" style="color: #ff4343; font-size: 14px; font-weight: bold"></td>
			</tr>
			
			<tr>
				<td>&nbsp;</td>
				<td align="left" style="padding-top: 10px;">
					<div onclick="closePanel( 'pubs_workflow', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="margin-left: 2px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div id="applyButt" onclick="saveJob()" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>
	</table>
	</div>
</div>
</form>

<script>
function checkCCResponse() {
	console.log( $("#Code").val() );
	$.ajax	({
		url:"plugins/pubsApply.php?sub=checkRegularClientChangeResponse",
		type: "POST",
		data: { settings: $("#Code").val() },
		dataType: 'json',
		success:function( data ) {
			console.log( data );
			
			if( data["clientChange"] == 1 ) {
				setTimeout(function(){ checkCCResponse(); }, 1000 );
				}
				
			else {
				if( data["clientChange"] == 3 ) {
					$("#result").html( data["clientChangeResult"] );
					}
				
				if( data["clientChange"] == 2 ) {
					$.ajax ({
						url:"plugins/pubsApply.php?sub=workflow2",
						type: "POST",
						data: { settings: $("#subForm").serialize() },
						dataType: 'json',
						success:function( data ) {
							$("#pubs_workflow").hide(200, function(){
								$(this).remove();
								});						
							}
						});
					}
				}
			}
		});
	}

function saveJob() {
	$("#applyButt").addClass("disabled");
	$.ajax	({
		url:"plugins/pubsApply.php?sub=workflow",
		type: "POST",
		data: { settings: $("#subForm").serialize() },
		dataType: 'json',
		success:function( data ) {	
			console.log( data[0] );
			if( data[0] == "ClientChange" ) {
				checkCCResponse();
				}
				
			if( data[0].length > 0 ) {
				for( var i = 0; i < data[0].length; i++ ) {
					if( data[0][i].indexOf("position_") >= 0 ) {
						var temp = data[0][i].split("_");
						var tar = $('input[name^="position"]')[temp[1]];
						$(tar).css("background", "#D14550" );
						}
					else {
						$("#"+data[0][i]).css("background", "#D14550" );
						}
					}
				}
			
			if( data[0] == "" ) {
				$("#pubs_workflow").hide(200, function(){
					$(this).remove();
					});
				}
			}
		});
	}

function addEmail() {
	var suser = $("#addRUser option:selected").val();
	var mails = $("#Mails").val();
	mails = mails.trim();
	$("#Mails").val( mails+"\n"+suser );
	}

function changeSettings() {
	var wf = $("#Workflow").val();

	// Per-workflow parameter visibility, per the Tracker Workflows matrix
	// (2026-07): CustomCode, FlatplanStages, PDFstandard, ArchiveStorage,
	// WebImages and ApprovedComments are Full-only; OutputFormat is the
	// inverse (Hybrid/Resize only). Rows that are merely hidden keep
	// submitting their stored value; the disabled-swap pairs
	// (PDFstandard/ArchiveStorage/WebImages/OutputFormat) submit their
	// fixed _hidden default instead - PDFstandard's is PDFX1A, which the
	// matrix explicitly mandates for Hybrid.
	switch( wf ) {
		case "Full":
			$("tr[class='FlatplanStages']").show(0);
			$("tr[class='CustomCode']").show(0);
			$("tr[class='ApprovedComments']").show(0);
			$("tr[class='PDFstandard']").show(0);
			$("tr[class='ArchiveStorage']").show(0);
			$("tr[class='WebImages']").show(0);
			$("tr[class='OutputFormat']").hide(0);

			$("#PDFstandard").removeAttr('disabled');
			$("#PDFstandard_hidden").attr('disabled','disabled');
			$("#ArchiveStorage").removeAttr('disabled');
			$("#ArchiveStorage_hidden").attr('disabled','disabled');
			$("#WebImages").removeAttr('disabled');
			$("#WebImages_hidden").attr('disabled','disabled');
			$("#OutputFormat").attr('disabled','disabled');
			$("#OutputFormat_hidden").removeAttr('disabled');
			break;

		case "Hybrid":
		case "Resize":
			$("tr[class='FlatplanStages']").hide(0);
			$("tr[class='CustomCode']").hide(0);
			$("tr[class='ApprovedComments']").hide(0);
			$("tr[class='PDFstandard']").hide(0);
			$("tr[class='ArchiveStorage']").hide(0);
			$("tr[class='WebImages']").hide(0);
			$("tr[class='OutputFormat']").show(0);

			$("#PDFstandard").attr('disabled','disabled');
			$("#PDFstandard_hidden").removeAttr('disabled');
			$("#ArchiveStorage").attr('disabled','disabled');
			$("#ArchiveStorage_hidden").removeAttr('disabled');
			$("#WebImages").attr('disabled','disabled');
			$("#WebImages_hidden").removeAttr('disabled');
			$("#OutputFormat").removeAttr('disabled');
			$("#OutputFormat_hidden").attr('disabled','disabled');
			break;
		}

	}
changeSettings();

</script>