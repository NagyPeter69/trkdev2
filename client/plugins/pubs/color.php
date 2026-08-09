<?
include_once( 'partsRow.php' );
$magazine = sql_get( "magazines", "id='".$_GET['data']."'", "*" );
$newxml = simplexml_load_file( '../xml/'.PMD.'.xml' );
$xpath = $newxml->xpath('/Publications');
foreach($xpath as $temp) {
	for( $x = 0; $x < count( $temp->Item ); $x++ ) {
		if( $temp->Item[$x]->Code == $magazine[0][3] ) {
			break;
			}
		}
	}

if( $magazine[0][10] == "Adhoc" ) {
	$pub = sql_aget( "publications", "code='".$magazine[0][3]."'", "*" );
	}

?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">
<input type="hidden" id="magazine" name="magazine" value="<?= $_GET['data']; ?>">
<input type='hidden' id='code' name='code' value='<?= $magazine[0][3] ?>'>
<input type='hidden' id='pn' name='pn' value='<?= $newxml->Item[$x]->PageNumbering ?>'>
<?php
if( $newxml->Item[$x]->Workflow != "Full" and $newxml->Item[$x]->Workflow != "Hybrid" ) {
	$width = 610;
	}
else {
	$width = 830;
	}
?>

<div>
	<div class='panelTitle'><?= $lang["publications"]["color"] ?> : <?= $magazine[0][2] ?></div>
	<div class='panelControl' style='width: <?= $width ?>px;'>
		<?php if( $magazine[0][10] == "Adhoc" && $newxml->Item[$x]->PageNumbering == "European" ) { ?>
		<table cellspacing='0' cellpadding='0'>
			<tr>
				<td><?= $lang["settings"]["issueLength"] ?></td>
				<td style="padding-left: 15px;"><input readonly title="Az oldalszámot a Parts listában megadott oldaltartományok határozzák meg" type="text" name="numOfPages" value="<?= $pub[0]["pages"] ?>" style="width: 30px;"></td>
			</tr>
		</table>
		<?php } ?>
		
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tbody id="partContent">
				<?php
				$types = PARTTYPES;
				$pageNumbering = (string) $newxml->Item[$x]->PageNumbering;
				$workflow = (string) $newxml->Item[$x]->Workflow;
				$pdfStandard = (string) $newxml->Item[$x]->PDFstandard;

				if( $magazine[0][10] == "Adhoc" ) {
					$pub = sql_aget( "publications", "magazine_id='".$_GET['data']."' AND code='".$magazine[0][3]."'", "*" );
					$parts = sql_aget( "parts", "pub_id='".$pub[0]["id"]."' order by id ASC", "*" );
					}

				if( $magazine[0][10] == "Regular" ) {
					$parts = sql_aget( "parts", "pub_id='0' AND mag_id='".$magazine[0][0]."' order by id ASC", "*" );
					}

				for( $i = 0; $i < count( $parts ); $i++ ) {
					echo partsRowHtml( $parts[$i], $pageNumbering, $workflow, $pdfStandard );
					}
				?>
			</tbody>
			<tfoot>
				<tr>
					<td><img id="newLineIcon" onclick="newLine()" src="images/trk_plus.png" style="cursor: pointer;"></td>
				</tr>
			</tfoot>
		</table>
		
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tr>
				<td>&nbsp;</td>
				<td align="center" style="padding-top: 10px;">
					<div onclick="closePanel( 'pubs_color', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<!-- <div onclick="menuApply( 'pubs', 'color', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Apply</div> -->
					<div onclick="saveParts()" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>
		</table>
	</div>
</div>
</form>

<script>
var maxTypes = parseInt( "<?= count( $types ) ?>" );
var usedTypes = parseInt( "<?= count( $parts ) ?>" );
console.log( "max parts: "+maxTypes );
console.log( "used parts: "+usedTypes );
function saveParts() {
	console.log( $("#subForm").serialize() );
	$('input[name^="position"]').css("background", "#FFF");
	$.ajax	({
		url:"plugins/pubsApply.php?sub=color",
		type: "POST",
		data: { settings: $("#subForm").serialize() },
		dataType: 'json',
		success:function( data ) {	
			console.log( data[0] );
			if( data[0].length > 0 ) {
				for( var i = 0; i < data[0].length; i++ ) {
					if( data[0][i].indexOf("position_") >= 0 ) {
						var temp = data[0][i].split("_");
						var tar = $('input[name^="position"]')[temp[1]];
						$(tar).css("background", "#D14550" );
						}
					}
				}
			else {
				$("#pubs_color").hide(200, function(){
					$(this).remove();
					});
				}
			}
		});
	}

<?= partsSelfcoverJs( '$("#pn").val()' ) ?>

function checkParts() {
	if( usedTypes < maxTypes ) {
		$("#newLineIcon").show(0);
		}
	else {
		$("#newLineIcon").hide(0);
		}

	setParts();
	}

function removeRow( o ) {
	var obj = $(o).parent().parent().parent();
	$(obj).remove();
	usedTypes--;
	checkParts();
	}

function newLine() {
	var have = new Array();
	$("select[name='type[]']").each(function(){
		have.push( $(this).val() );
		});

	var text = '<tr><td style="white-space: nowrap;"><span style="display: inline-block; width: 105px; min-width: 105px;"><select name="type[]">';

	<?php
	for( $i = 0; $i < count( $types ); $i++ ) {
		$temp = array_search( $types[$i], PARTS );
		echo '
			if( jQuery.inArray( "'.$types[$i].'", have ) == -1 ) {
				text += \'<option value="'.$types[$i].'">'.$lang["parts"][$temp].'</option>\';
				}
			';
		}
	echo partsRowJs( $pageNumbering, $workflow, $pdfStandard );
	?>
	$("#partContent").append(text);
	<?= partsTrimPrefillJs() ?>
	usedTypes++;
	checkParts();
	}

</script>