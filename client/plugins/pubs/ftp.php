<?
$magazine = sql_get( "magazines", "id='".$_GET['data']."'", "*" );
?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $magazine[0][1]; ?>">
<input type="hidden" id="magazine" name="magazine" value="<?= $_GET['data']; ?>">
<input type='hidden' id='code' name='code' value='<?= $magazine[0][3] ?>'>

<div>
	<div class='panelTitle'><?= $lang["publications"]["ftp"] ?> : <?= $magazine[0][2] ?></div>
	<div class='panelControl' style='width: 320px;'>

	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<?
		$xml = simplexml_load_file( '../xml/'.PMD.'.xml' );
		$xml2 = simplexml_load_file( '../xml/Output_Details.xml' );
		$mag = sql_get( 'magazines', 'code="'.$magazine[0][3].'"', "*");
		$pub = sql_get( 'publishers', 'id="'.$mag[0][1].'"', 'name' );
		$pub[0][0] = str_replace( " ", "", $pub[0][0] );
		//$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );
		
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $i = 0; $i < count( $temp->Item ); $i++ ) {
				if( $temp->Item[$i]->Code == $magazine[0][3] )
					break;
				}
			}
		
		$workflow = (string) $xml->Item[$i]->Workflow;
		switch( $workflow ) {
			case 'Full':
				$nodes = array( 'INDD' );
					$avaiable = array( 'FinalOutput' );
				break;
			case 'Hybrid':
				$nodes = array( 'INDD', 'PDF' );
				$avaiable = array( 'FinalOutput' );
				break;
			case 'Repack':
				$nodes = array( 'Content' );
				$avaiable = array();
				break;
			case 'Resize':
				$nodes = array( 'Images' );
				$avaiable = array();
				break;
			case 'Enhance':
				$nodes = array( 'Images' );
				$avaiable = array();
				break;
			}			
		
				//$nodes = array( 'INDD', 'Images', 'PDF', 'Packages' );
				//$avaiable = array( 'Softproof', 'FinalOutput' );
		
		foreach( $nodes as $node ) {
			echo "<tr>";
				echo "<td align='left'>";
					echo $lang['settings']['ftp_'.$node];
				echo "</td>";
				echo "<td align='left'>";
					echo "<select id='ftp_".$node."' name='ftp_".$node."'>";
						$xpath = $xml2->{$pub[0][0]}->Outward->Content->children();
						foreach( $xpath as $temp) {
							if( $temp->getName() != 'Targets' ) {
								$actual = (string) $xml->Item[$i]->RemoteStorage->$node;
								echo "<option ";
								if( $actual == $temp->getName() ) echo "selected ";
								echo "value='".$temp->getName()."'>".$temp->getName()."</option>";
								}
							}
					echo "</select>";
				echo "</td>";
			echo "</tr>";
			}
		
		foreach( $avaiable as $key ) {
			$temp = array();
			switch( $key ) {
				case 'Softproof':
					$xml2 = simplexml_load_file( '../xml/Output_Details.xml' );						
					$xpath2 = $xml2->{$pub[0][0]}->Outward->Softproof->children();

					foreach( $xpath2 as $temp2 ) {
						$temp[] = $temp2->getName();
						}
					if( count( $temp ) <= 0 ) {
						$temp = array( '' );
						}
					$value = (string) $xml->Item[$i]->Softproof;
					break;
				case 'FinalOutput':
					$xml2 = simplexml_load_file( '../xml/Output_Details.xml' );						
					$xpath2 = $xml2->{$pub[0][0]}->Outward->Final->children();

					foreach( $xpath2 as $temp2 ) {
						$temp[] = $temp2->getName();
						}
					if( count( $temp ) <= 0 ) {
						$temp = array( '' );
						}
					$value = (string) $xml->Item[$i]->FinalOutput;
					break;
				}
			
			echo "<tr>";
				echo "<td align='left' style='height: 25px;'>".$lang['xml'][$key]."</td>";
				echo "<td align='left'>";
					echo "<select id='".$key."' name='".$key."'>";
						if( $key == 'FinalOutput' ) {
							echo "<option value='".$lang['standard']['none']."'>".$lang['standard']['none']."</option>";
							}

						foreach( $temp as $t ) {	
							echo "<option ";
							if( $value == $t ) echo "selected ";
								echo "value='".$t."'>".$t."</option>";
							}
					echo "</select>";
				echo "</td>";
			echo "</tr>";
			}
		?>
		<tr>
			<td>&nbsp;</td>
			<td align="left" style="padding-top: 10px;">
				<div onclick="closePanel( 'pubs_ftp', 'back', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="margin-left: 2px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div onclick="menuApply( 'pubs', 'ftp', '<?= "line_".$magazine[0][3]."_".$magazine[0][0]."Float" ?>' )" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
			</td>
		</tr>	
	</table>
	</div>
</div>
</form>	