<?
$magazine = sql_get( "magazines", "id='".$_GET['data']."'", "*" );
$softproofpubs = array( "iPixel" );
$publisher = sql_get( "publishers", "id='".$user[0][4]."'", "name" );

$types = PARTTYPES;

?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">
<input type="hidden" id="magazine" name="magazine" value="<?= $_GET['data']; ?>">
<input type='hidden' id='code' name='code' value='<?= $magazine[0][3] ?>'>

<div>
	<div class='panelTitle'><?= $lang["publications"]["new_mag"] ?></div>
	<div class='panelControl' style='width: 470px;'>
	<div class='panelSubTitle'><?= $lang["publications"]["workflow"] ?></div>
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<tr>
			<td style="width: 188px;">Type</td>
			<td align='left'>
				<select onchange="loadPub()" style='margin-left: -1px;' id='Type' name='Type'>
					<option value="Adhoc">Adhoc</option>
					<option value="Regular">Regular</option>
				</select>
			</td>
		</tr>
	</table>
	
	<table id="pubContent" class='panelTable' cellspacing='0' cellpadding='0'>
	<?php			
			$avaiable = array( 'Name', 'Client', 'Code', 'Mails', 'Workflow', 'Enhance', 'PageNumbering', 'Deadline', 'Language' );
		
			$default = array();
			foreach( $avaiable as $key ) {
				$value = (string) $xml->Item[$i]->$key;
				$temp = array();
				switch( $key ) {
					case 'PageNumbering':
						$temp = array( 'American', 'European' );
						$default[$key] = "European";
						break;
					case 'Workflow':
						$temp = array( 'Full', 'Hybrid', 'Repack', 'Resize', 'Enhance' );
						$default[$key] = "Resize";
						break;
					case 'Enhance':
						$temp = array( 'Skintone', 'Food', 'Jewellery', 'Vivid', 'Paintings', 'Minimal', 'General', 'Resize only', 'Null' );
						$changeName = array( "Only Resize"=>"Null" );
						$default[$key] = "General";
						break;
					case 'Language':
						$temp = array( 'HU', 'EN' );
						$default[$key] = "EN";
						break;
					}		
							
				$txt .= "<tr>";
					if( $key == "Mails" ) $txt .= "<td valign='top' align='left' style='width: 188px;'>".$lang['xml'][$key]."</td>";			
					else $txt .= "<td align='left'>".$lang['xml'][$key]."</td>";
						
					$txt .= "<td align='left'>";
						if( $key == 'Name' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value=''>";
							}
						elseif( $key == 'Code' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value='".codeGen()."' readonly>";
							}
						elseif( $key == 'Client' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value=''>";
							}
						elseif( $key == 'Deadline' ) {
							$txt .= "<input type='text' class='datepicker' id='".$key."' name='".$key."' value=''>";
							}							
						elseif( $key == 'Mails' ) {
							$txt .= "<input type='text' id='".$key."' name='".$key."' value=''>";
							}
						elseif( $temp == '' ) {
							$txt .= "<input id='".$key."' type='text' name='".$key."' value='".$value."'>";
							}
						elseif( $key == 'Enhance' ) {
							$txt .= "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								$tempVal = $t;
								if( !empty( $changeName[$t] ) ) {
									$tempVal = $changeName[$t];
									}
								$txt .= "<option ";
								if( $value == $tempVal ) $txt .= "selected ";
									$txt .= "value='".$tempVal."'>".$t."</option>";
								}
							$txt .= "</select>";
							}						
						else {					
							$txt .= "<select style='margin-left: -1px;' id='".$key."' name='".$key."'>";
							foreach( $temp as $t ) {
								$txt .= "<option ";
								if( $value == $t ) $txt .= "selected ";
								if( $default[$key] == $t ) $txt .= "selected ";
									$txt .= "value='".$t."'>".$t."</option>";
								}
							$txt .= "</select>";
							}
					$txt .= "</td>";
				$txt .= "</tr>";
				}
			
			$txt .= "<input type='hidden' name='Publisher' id='Publisher' value='Adhoc'>";			
			$txt .= "<input type='hidden' name='FlatplanStages' id='FlatplanStages' value='1'>";			
			$txt .= "<input type='hidden' name='Resolution' id='Resolution' value='300'>";			
			$txt .= "<input type='hidden' name='PDFstandard' id='PDFstandard' value='PDFX1A'>";			
			$txt .= "<input type='hidden' name='ArchiveStorage' id='ArchiveStorage' value='Client Area'>";			
			$txt .= "<input type='hidden' name='OutputFormat' id='OutputFormat' value='TIFF'>";			
			$txt .= "<input type='hidden' name='CustomCode' id='CustomCode' value='No'>";			
			$txt .= "<input type='hidden' name='ImageRename' id='ImageRename' value='No'>";			
			$txt .= "<input type='hidden' name='LocalStorage' id='LocalStorage' value='Adhoc'>";			
			$txt .= "<input type='hidden' name='MailComm' id='MailComm' value='No'>";			
			$txt .= "<input type='hidden' name='WebImages' id='WebImages' value='Yes'>";

			echo $txt;
	?>

	</table>
	
	<table id="pubContent" class='panelTable' cellspacing='0' cellpadding='0'>
		<tr>
			<td colspan="2" align="center" align="center" style="padding-top: 10px;">
				<div onclick="closePanel( 'pubs_create', 'back' )" style="margin-left: 130px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div id="applyButt" onclick="$('#applyButt').addClass('disabled'); menuApply( 'pubs', 'create' )" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["create"] ?></div>
			</td>
		</tr>
	</table>	
	</div>
</div>
</form>		

<script>
var posname = "Pages";

function checkParts() {
	$val = $("#PageNumbering option:selected").val();
	if( $val == "American" ) {
		$("select option[value='Selfcover']").hide();
		}
	else {
		$("select option[value='Selfcover']").show();
		}	
	}

setTimeout( function() {
	$("#PageNumbering").change(function(){
		checkParts();
		});
	$("#PageNumbering").change();
	}, 100 );

function removeRow( o ) {
	var obj = $(o).parent().parent().parent();
	$(obj).remove();
	}

function setParts() {
	var temp = $("#Workflow option:selected").val();
	
	if( temp != "Full" && temp != "Hybrid" ) {
		$(".trimsize").hide(0);
		$(".panelControl").css("width", "470px");
		}
	else {
		$(".trimsize").show(0);
		$(".panelControl").css("width", "660px");
		}
	checkParts();
	}
	
setParts();

function newLine() {
	var have = new Array();
	$("select[name='parttype[]']").each(function(){
		have.push( $(this).val() );
		});
	
	var text = '<tr><td><span><select name="parttype[]">';	
	<?php
		for( $i = 0; $i < count( $types ); $i++ ) {
			$temp = array_search( $types[$i], PARTS );
			echo '
				if( jQuery.inArray( "'.$types[$i].'", have ) == -1 ) {
					text += \'<option value="'.$types[$i].'">'.$temp.'</option>\';
					}
				';
			}
	?>
	text += '</select></span>';
	text += '<span style="padding-left: 5px;">'+posname+': <input type="text" onkeydown="numberCheck3(event)" name="position[]" style="width: 100px;"></span>';
	text += '<span class="trimsize" style="padding-left: 10px;">Trimmed size: <input type="text" name="trim_x[]" style="width: 25px;">x<input type="text" name="trim_y[]" style="width: 25px;">mm</span>';
	text += '<span style="padding-left: 10px;">Color standard: <select name="color[]"><?= colorStandardOptions( "FOGRA_39" ) ?></select></span>';
	text += '<span style="padding-left: 5px;"><img onclick="removeRow( $(this) )" src="images/trash.png" style="cursor: pointer; height: 14px;"></span></td></tr>';
	$("#partContent").append(text);
	setParts();
	}

function getUsers() {
	$.ajax	({
		url:"plugins/pubsApply.php?sub=getUsers&publisher="+$("#Client2").val(),
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$(".userSelect").html( data );
			}
		});
	}

function loadPub() {
	$.ajax	({
		url:"plugins/pubsApply.php?sub=loadPub&Type="+$("#Type").val(),
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#pubContent").html( data );
			
			$('.datepicker').datetimepicker({
				dateFormat: 'yy-mm-dd',
				separator: ' ',
				timeFormat: "HH:mm",
				stepHour: 1,
				stepMinute: 1,
				hour: 16
				});

			$("#Workflow").change(function(){
				console.log( "Change" );
				setParts();
				});
			
			$("#Client2").change(function(){
				getUsers();
				});
			
			$(".ClientType").change(function(){
				switch( $(this).val() ) {
					case 'known':
						$("#unknownClient").hide(0);
						$("#knownClient").show(0);
						
						$(".userBox").show(0);
						$(".mailBox").hide(0);
						break;
						
					case 'unknown':
						$("#unknownClient").show(0);
						$("#knownClient").hide(0);

						$(".userBox").hide(0);
						$(".mailBox").show(0);						
						break;
					}
				});
			}
		});	
	}
loadPub();

</script>