<?php
$asset = sql_aget( "assets", "id='".$_GET["data"]."'", "*" );
$pub = sql_aget( "publications", "id='".$asset[0]['pub_id']."'", "*" );
$magazine = sql_get( "magazines", "id='".$pub[0]['magazine_id']."'", "*" );

$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
$xpath = $xml->xpath('/Publications');

foreach($xpath as $temp) {
	for( $i = 0; $i < count( $temp->Item ); $i++ ) {
		if( $temp->Item[$i]->Code == $magazine[0][3] )
			break;
		}
	}

$mails = trim( $xml->Item[$i]->Mails );

// Default the resend mail's language to the job's own defined language
// (PMD XML Item->Language, 'HU'/'EN' - the same field jobsettings/pubsApply.php
// read for the job's Language dropdown), not the hardcoded Hungarian the mail
// body used to always use.
$jobLang = strtolower( (string) $xml->Item[$i]->Language );
if( !in_array( $jobLang, array( 'hu', 'en' ) ) ) {
	$jobLang = 'en';
	}
?>

<form id='subForm' method='post' action=''>
<input type="hidden" id="assetid" name="assetid" value="<?= $_GET['data']; ?>">

<div>
	<div class='panelTitle'><?= $lang["assets"]["resendlink"] ?></div>
	<div class='panelControl' style='width: <?= $width ?>px; text-align: left;'>
		<table cellspacing='0' cellpadding='0'>
			<tr>
				<td valign="top" align='left' width='50%' height='28px'><?= $lang["assets"]["emails"] ?></td>
				<td valign="top" align='left'>
					<textarea id='Mails' name='Mails' style='width: 170px; height: 60px; resize: none;'><?= str_replace( ";", "\n", $mails ) ?></textarea>
				</td>
			</tr>
			<tr>
				<td valign="top" align='left' width='50%' height='28px'><?= $lang['settings']['language'] ?></td>
				<td valign="top" align='left'>
					<select name='lang' id='lang'>
					<?
					foreach( array( 'hu', 'en' ) as $key ) {
						echo "<option value='".$key."'";
						if( $jobLang == $key ) echo " selected";
						echo ">".$lang["settings"][ $key ]."</option>";
						}
					?>
					</select>
				</td>
			</tr>
		</table>
		
		<table class='panelTable' cellspacing='0' cellpadding='0'>
			<tbody>
				<tr>
					<td colspan="2" align="center" style="padding-top: 10px;">
						<div onclick="closePanel( 'asset_resend', 'back', 'floatMenu' )" style="display: inline-block; float: none;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
						<div onclick="menuApply( 'asset', 'resend', 'floatMenu' )" style="display: inline-block; float: none; margin-left: 20px;" class="panelButton"><?= $lang["standard"]["resend"] ?></div>
					</td>
				</tr>				
					</table>
				</div>
				</td></tr>
			</tbody>
		</table>		
	</div>
</div>