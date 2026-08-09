<?
include_once( 'partsRow.php' );
$magazine = sql_get( "magazines", "id='".$_GET['data']."'", "*" );
$softproofpubs = array( "iPixel" );
$publisher = sql_get( "publishers", "id='".$user[0][4]."'", "name" );

$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
				
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $i = 0; $i < count( $temp->Item ); $i++ ) {
					if( $temp->Item[$i]->Code == $magazine[0][3] )
						break;
					}
				}	
?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">
<input type="hidden" id="magazine" name="magazine" value="<?= $_GET['data']; ?>">
<input type='hidden' id='code' name='code' value='<?= $magazine[0][3] ?>'>
<input type='hidden' id='pn' name='pn' value='<?= $xml->Item[$i]->PageNumbering ?>'>

<input type='hidden' id='OutputFormat_hidden' disabled name='OutputFormat' value='TIFF'>
<input type='hidden' id='PDFstandard_hidden' disabled name='PDFstandard' value='PDFX1A'>
<input type='hidden' id='ArchiveStorage_hidden' disabled name='ArchiveStorage' value='Client Area'>
<input type='hidden' id='WebImages_hidden' disabled name='WebImages' value='Yes'>
<input type='hidden' id='Enhance_hidden' disabled name='Enhance' value='General'>
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
	<div class='panelTitle'><?= $lang["publications"]["job"] ?> : <?= $magazine[0][2] ?></div>
	<div class='panelControl' style='width: 610px;'>

	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<?
			$mag = sql_get( 'magazines', 'id="'.$_GET['data'].'"', "*");
			$pubs = sql_get( 'publications', 'magazine_id="'.$mag[0][0].'" ORDER BY `code` ASC', '*' );
			$temp = array();
			for( $x = 0; $x < count( $pubs ); $x++ ) {
					$temp[] = $pubs[$x][10];
					}


			$avaiable = array( 'Type', 'Name', 'Client', 'Code', 'Mails', 'Workflow', 'Enhance', 'PageNumbering', 'Deadline', 'Language', 'Resolution', 'FlatplanStages', 'PDFstandard', 'ArchiveStorage', 'OutputFormat', 'WebImages' );
			// E-mail only applies to Adhoc publications (Regular pubs have
			// no client mailing list here).
			if( $magazine[0][10] != "Adhoc" ) {
				$avaiable = array_diff( $avaiable, array( 'Mails' ) );
				}

			foreach( $avaiable as $key ) {
				$value = (string) $xml->Item[$i]->$key;
				$temp = array();
				switch( $key ) {
					case 'Type':
						$temp = array( 'Adhoc', 'Regular' );
						break;
					case 'Name':
					case 'Code':
						$temp = array( $value );
						break;
					case 'Workflow':
						$temp = array( 'Full', 'Hybrid', 'Resize', 'Auto' );
						break;
					case 'FlatplanStages':
						$temp = array( '1', '2', '3' );
						break;					
					case 'Enhance':
						$temp = array( 'Skintone', 'Food', 'Jewellery', 'Vivid', 'Paintings', 'Minimal', 'General', 'Resize only', 'Null' );
						$changeName = array( "Only Resize"=>"Null" );
						break;
					case 'PDFstandard':
						$temp = array( 'PDFX1', 'PDFX1A', 'PDFX4' );
						break;
					case 'ArchiveStorage':
						$temp = array( 'Client Area', 'Temporary' );
						break;
					case 'Language':
						$temp = array( 'HU', 'EN' );
						break;
					case 'PageNumbering':
						$temp = array( 'American', 'European' );
						$default[$key] = "European";
						break;
					case 'Resolution':
						$temp = array( '300', '450', '600' );
						break;
					case 'OutputFormat':
						$temp = array( 'TIFF', 'JPEG', 'Original' );
						break;
					case 'WebImages':
						$temp = array( 'Yes', 'No' );
						break;						
					case 'Publisher':
						$pubs = sql_get( 'publishers', '1 ORDER BY `name` ASC ', '*' );
						for( $x = 0; $x < count( $pubs ); $x++ ) {
							$temp[] = $pubs[$x][1];
							}
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
							
						elseif( $key == 'Type' or $key == 'PageNumbering' ) {
							echo "<select disabled style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								echo "<option ";
								if( $value == $t ) echo "selected ";
									echo "value='".$t."'>".$t."</option>";
								}
							echo "</select>";
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
						
						elseif( $key == 'Name' ) {
							echo "<input disabled type='text' id='".$key."' name='".$key."' value='".$value."'>";
							}
						
						elseif( $key == 'Deadline' ) {
							echo "<input type='text' class='datepicker' id='".$key."' name='".$key."' value='".$pubs[0][11]."'>";
							}
						
						elseif( $key == 'Client' ) {
							echo "<input disabled type='text' id='".$key."' name='".$key."' value='".$value."'>";
							}	
						
						elseif( $temp == '' ) {
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
	</table>
	</form>

	<form id='partsForm' method='post' action=''>
	<table cellspacing="0" cellpadding="0" id="pub_parts">
		<?php
		$types = PARTTYPES;
		$pageNumbering = (string) $xml->Item[$i]->PageNumbering;
		$workflow = (string) $xml->Item[$i]->Workflow;
		$pdfStandard = (string) $xml->Item[$i]->PDFstandard;

		if( $magazine[0][10] == "Adhoc" ) {
			$pub = sql_aget( "publications", "magazine_id='".$magazine[0][0]."' AND code='".$magazine[0][3]."'", "*" );
			$parts = sql_aget( "parts", "pub_id='".$pub[0]["id"]."' order by id ASC", "*" );
			}

		if( $magazine[0][10] == "Regular" ) {
			$parts = sql_aget( "parts", "pub_id='0' AND mag_id='".$magazine[0][0]."' order by id ASC", "*" );
			}

		for( $p = 0; $p < count( $parts ); $p++ ) {
			echo partsRowHtml( $parts[$p], $pageNumbering, $workflow, $pdfStandard );
			}
		?>
	</table>
	</form>
	
	<table cellspacing="0" cellpadding="0" width="100%">
		<!--
		<tr>
			<td></td>
			<td id="result" style="color: #ff4343; font-size: 14px; font-weight: bold"></td>
		</tr>
		-->
		<tr>
			<td colspan="2" align="center" style="padding-top: 10px;">
				<div onclick="closePanel( 'pubs_jobsettings', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="float: inherit; display: inline-block; margin-left: 2px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div id="applyButt" onclick="saveJob()" style="float: inherit; display: inline-block; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
			</td>
		</tr>		
	</table>
	</div>
</div>


<script>
var origWorkflow = $("#Workflow option:selected").val();
var miniParts = new Array( "Repack", "Resize", "Enhance", "Auto" );
var longParts = new Array( "Hybrid", "Full" );
var saveParts = false;
console.log( origWorkflow );
if( jQuery.inArray( origWorkflow, miniParts ) !== -1 ) {
	var type = "mini";
	var type2 = "mini";
	}

if( jQuery.inArray( origWorkflow, longParts ) !== -1 ) {
	var type = "long";
	var type2 = "long";
	}

function addEmail() {
	var suser = $("#addRUser option:selected").val();
	var mails = $("#Mails").val();
	mails = mails.trim();
	$("#Mails").val( mails+"\n"+suser );
	}

function setParts() {
	// jobsettings.php already owns the name setParts() for the workflow-
	// category toggle below, unlike the other Parts dialogs (whose
	// same-named function only hides the American-only Selfcover option) -
	// so that rule is folded in here directly instead of via
	// partsSelfcoverJs(), rather than risk one setParts() silently
	// overwriting the other.
	if( $("#PageNumbering").val() == "American" ) {
		$("select option[value='Selfcover']").hide();
		}
	else {
		$("select option[value='Selfcover']").show();
		}

	var temp = $("#Workflow option:selected").val();
	if( jQuery.inArray( temp, miniParts ) !== -1 ) {
		type2 = "mini";
		}

	if( jQuery.inArray( temp, longParts ) !== -1 ) {
		type2 = "long";
		} 
	
	console.log( type+" , "+type2 );
	
	if( type2 != type ) {
		saveParts = true;
		$("#pub_parts").show(0);

		// Trim-size visibility for each row is now decided server-side per
		// row (partsRowHtml()/partsRowJs() in partsRow.php - real hidden
		// inputs for Resize/Auto, not a CSS class toggle), so there's no
		// more live .trimsize element to show/hide here when Workflow
		// changes without a reload - only the panel width still needs it.
		if( temp != "Full" && temp != "Hybrid" ) {
			$(".panelControl").css("width", "610px");
			}
		else {
			$(".panelControl").css("width", "800px");
			}
		}
	else {
		saveParts = false;
		$("#pub_parts").hide(0);
		$(".panelControl").css("width", "610px");
		}
	}

$("#Workflow").change(function(){
	setParts();
	});
setParts();

function newLine() {
	var have = new Array();
	$("select[name='type[]']").each(function(){
		have.push( $(this).val() );
		});

	var text = '<tr><td style="white-space: nowrap;"><span><select name="type[]">';
	<?php
		for( $i = 0; $i < count( $types ); $i++ ) {
			$temp = array_search( $types[$i], PARTS );
			echo '
				if( jQuery.inArray( "'.$types[$i].'", have ) == -1 ) {
					text += \'<option value="'.$types[$i].'">'.$temp.'</option>\';
					}
				';
			}
		echo partsRowJs( $pageNumbering, $workflow, $pdfStandard );
	?>
	$("#partContent").append(text);
	<?= partsTrimPrefillJs() ?>
	setParts();
	}

function checkCCResponse() {
	console.log( $("#Code").val() );
	$.ajax	({
		url:"plugins/pubsApply.php?sub=checkClientChangeResponse",
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
						url:"plugins/pubsApply.php?sub=jobsettings2",
						type: "POST",
						data: { settings: $("#subForm").serialize() },
						dataType: 'json',
						success:function( data ) {
							$("#pubs_jobsettings").hide(200, function(){
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
	console.log("SaveJOB");
	console.log( saveParts );
	console.log( $("#subForm").serialize() );
	$.ajax	({
		url:"plugins/pubsApply.php?sub=jobsettings&saveParts="+saveParts+"&type="+type2,
		type: "POST",
		data: { settings: $("#subForm").serialize(), parts : $("#partsForm").serialize() },
		dataType: 'json',
		success:function( data ) {	
			console.log( data );
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
						$("."+data[0][i]).css("background", "#D14550" );
						}
					}
					
				$("#applyButt").removeClass("disabled");
				}
			
			if( data[0] == "" ) {
				$("#pubs_jobsettings").hide(200, function(){
					$(this).remove();
					});
				}
			}
		});
	}
	
function changeSettings() {
	var wf = $("#Workflow").val();

	// Per-workflow parameter visibility, per the Tracker Workflows matrix
	// (2026-07, "Auto" added): FlatplanStages, PDFstandard, ArchiveStorage
	// and WebImages are Full-only. OutputFormat is shown for Hybrid/Resize
	// only - Full fixes it to TIFF, Auto fixes it to Original (both via the
	// same hidden fallback input, whose value this function sets before
	// disabling the real select). Enhance (Image Enhancement) is shown for
	// everything except Auto, whose processing is fully automatic. The
	// disabled-swap pairs submit their fixed _hidden default when hidden -
	// PDFstandard's is PDFX1A, which the matrix explicitly mandates for
	// Hybrid.
	switch( wf ) {
		case "Full":
			$("tr[class='FlatplanStages']").show(0);
			$("tr[class='PDFstandard']").show(0);
			$("tr[class='ArchiveStorage']").show(0);
			$("tr[class='WebImages']").show(0);
			$("tr[class='Enhance']").show(0);
			$("tr[class='OutputFormat']").hide(0);

			$("#PDFstandard").removeAttr('disabled');
			$("#PDFstandard_hidden").attr('disabled','disabled');
			$("#ArchiveStorage").removeAttr('disabled');
			$("#ArchiveStorage_hidden").attr('disabled','disabled');
			$("#WebImages").removeAttr('disabled');
			$("#WebImages_hidden").attr('disabled','disabled');
			$("#Enhance").removeAttr('disabled');
			$("#Enhance_hidden").attr('disabled','disabled');
			$("#OutputFormat").attr('disabled','disabled');
			$("#OutputFormat_hidden").attr('value','TIFF').removeAttr('disabled');
			break;

		case "Hybrid":
		case "Resize":
			$("tr[class='FlatplanStages']").hide(0);
			$("tr[class='PDFstandard']").hide(0);
			$("tr[class='ArchiveStorage']").hide(0);
			$("tr[class='WebImages']").hide(0);
			$("tr[class='Enhance']").show(0);
			$("tr[class='OutputFormat']").show(0);

			$("#PDFstandard").attr('disabled','disabled');
			$("#PDFstandard_hidden").removeAttr('disabled');
			$("#ArchiveStorage").attr('disabled','disabled');
			$("#ArchiveStorage_hidden").removeAttr('disabled');
			$("#WebImages").attr('disabled','disabled');
			$("#WebImages_hidden").removeAttr('disabled');
			$("#Enhance").removeAttr('disabled');
			$("#Enhance_hidden").attr('disabled','disabled');
			$("#OutputFormat").removeAttr('disabled');
			$("#OutputFormat_hidden").attr('disabled','disabled');
			break;

		case "Auto":
			$("tr[class='FlatplanStages']").hide(0);
			$("tr[class='PDFstandard']").hide(0);
			$("tr[class='ArchiveStorage']").hide(0);
			$("tr[class='WebImages']").hide(0);
			$("tr[class='Enhance']").hide(0);
			$("tr[class='OutputFormat']").hide(0);

			$("#PDFstandard").attr('disabled','disabled');
			$("#PDFstandard_hidden").removeAttr('disabled');
			$("#ArchiveStorage").attr('disabled','disabled');
			$("#ArchiveStorage_hidden").removeAttr('disabled');
			$("#WebImages").attr('disabled','disabled');
			$("#WebImages_hidden").removeAttr('disabled');
			$("#Enhance").attr('disabled','disabled');
			$("#Enhance_hidden").removeAttr('disabled');
			$("#OutputFormat").attr('disabled','disabled');
			$("#OutputFormat_hidden").attr('value','Original').removeAttr('disabled');
			break;
		}

	}
changeSettings();

</script>