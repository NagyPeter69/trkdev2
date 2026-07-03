<?
$job = sql_get( "jobs", "id='".$_GET['data']."'", "*" );
$publisher = sql_get( "publishers", "id='".$job[0][1]."'", "name" );
?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $publisher[0][0]; ?>">
<input type='hidden' id='code' name='code' value='<?= $job[0][3] ?>'>

<div>
	<div class='panelTitle'><?= $lang["publications"]["ftp"] ?></div>
	<div class='panelControl' style='width: 320px;'>

	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<?
		$xml = simplexml_load_file( '../xml/job_data.xml' );
		$xpath = $xml->xpath('/Job');
		foreach($xpath as $temp) {
			for( $i = 0; $i < count( $temp->Item ); $i++ ) {
				if( $temp->Item[$i]->Code == $job[0][3] && $temp->Item[$i]->Publisher == $publisher[0][0] )
					break;
				}
			}
	
		$nodes = array( 'Address', 'Port', 'Binary', 'Passive', 'Login', 'Password',  'Path' );
		$xpath = $xml->Item[$i]->RemoteStorage;
		foreach( $nodes as $node ) {
			$temp = '';
			$value = (string) $xpath->$node;
			
			switch( $node ){
				case 'Binary':
					$temp = array( 'True', 'False' );
					break;
				case 'Passive':
					$temp = array( 'Yes', 'No' );
					break;
				}

			$txt .= "<tr>";
				$txt .= "<td style='width: 130px;' align='left'>";
					$txt .= $lang['jobs']['ftp_'.strtolower($node)];
				$txt .= "</td>";
				$txt .= "<td align='left' style='width: 150px;'>";
					if( $node == 'Address' ) {
						$txt .= "<input type='text' id='m_address_url' name='m_address_url' value='".$value."'>";
						}
					elseif( $temp != '' ) {
						$txt .= "<select id='m_".strtolower($node)."' name='m_".strtolower($node)."'>";
						foreach( $temp as $t ) {
							$txt .= "<option ";
							if( $value == $t ) $txt .= "selected ";
							$txt .= "value='".$t."'>".$t."</option>";
							}
						$txt .= "</select>";
						}
					elseif( $node == 'Password' ) {
						$txt .= "<input type='password' autocomplete='off' id='m_".strtolower($node)."' name='m_".strtolower($node)."' value='".decrypt_( $value )."'>";
						}
					else {
						$txt .= "<input type='text' autocomplete='off' id='m_".strtolower($node)."' name='m_".strtolower($node)."' value='".$value."'>";
						}
				$txt .= "</td>";
				$txt .= "<td style='width: 95px;'>";
					if( $node == 'Password' ) {
						$txt .= "<input onchange='revealPass( this, \"m_password\")' type='checkbox' name='reveal'>&nbsp;".$lang["settings"]["reveal"];
						}
					else {
						$txt .= "&nbsp;";
						}
				$txt .= "</td>";
			$txt .= "</tr>";
			}
		
		echo $txt;
		?>	
		<tr>
			<td>&nbsp;</td>
			<td colspan="2" align="left" style="padding-top: 10px;">
				<div onclick="closePanel( 'jobs_ftp', 'back', '<?= "line_".$job[0][0]."Float" ?>' )" style="margin-left: 2px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div onclick="menuApply( 'jobs', 'ftp', '<?= "line_".$job[0][0]."Float" ?>' )" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
			</td>
		</tr>	
	</table>
	</div>
</div>
</form>		