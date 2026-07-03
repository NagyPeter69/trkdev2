<?
$job = sql_get( "jobs", "id='".$_GET['data']."'", "*" );
$publisher = sql_get( "publishers", "id='".$job[0][1]."'", "name" );
?>
<form id='subForm' method='post' action=''>
<input type="hidden" id="publisher" name="publisher" value="<?= $user[0][4]; ?>">

<div>
	<div class='panelTitle'><?= $lang["jobs"]["modify_job"] ?></div>
	<div class='panelControl' style='width: 470px;'>
	<div class='panelSubTitle'><?= $lang["jobs"]["settings"] ?></div>
	<table class='panelTable' cellspacing='0' cellpadding='0'>
		<?
			$xml = simplexml_load_file( '../xml/job_data.xml' );
			$all = false;
			if( $rights['pmdallmodify'] ) {
				$avaiable = array( 'Publisher', 'Name', 'Code', 'TrimSize', 'FacingPages', 'ColorManagement', 'Deadline', 'MailComm', 'Mails' );
				$all = true;
				}
				
			else {
				if( in_array( $publisher[0][0], $softproofpubs ) ) {
					$avaiable = array();
					array_push($avaiable, 'Name');
					array_push($avaiable, 'Code');
					array_push($avaiable, 'FacingPages');
					array_push($avaiable, 'MailComm');
					array_push($avaiable, 'Mails');
					}
				else {
					$avaiable = array();
					array_push($avaiable, 'Name');
					array_push($avaiable, 'Code');
					if( $rights['trimIssue'] )
						array_push($avaiable, 'TrimSize');	
					array_push($avaiable, 'FacingPages');
					array_push($avaiable, 'ColorManagement');				
					array_push($avaiable, 'MailComm');
					array_push($avaiable, 'Mails');				
					}
				}	

			$xpath = $xml->xpath('/Job');
			foreach($xpath as $temp) {
				for( $i = 0; $i < count( $temp->Item ); $i++ ) {
					if( $temp->Item[$i]->Publisher == $publisher[0][0] && $temp->Item[$i]->Code == $job[0][3] )
						break;
					}
				}

			foreach( $avaiable as $key ) {
				$value = (string) $xml->Item[$i]->$key;
				$temp = array();
				switch( $key ) {
					case 'FacingPages':
						$temp = array( $lang["settings"]["yes"], $lang["settings"]["no"] );
						break;
					case 'ColorManagement':
						$temp = array( 'FOGRA_39', 'FOGRA_45', 'FOGRA_46', 'IFRA_26' );
						break;
					case 'MailComm':
						$temp = array( 'Yes', 'No' );
						break;
					case 'Publisher':
						$pubs = sql_get( 'publishers', '1 ORDER BY `name` ASC ', '*' );
						$temp = $pubs[0][1];
						break;
					case 'TrimSize':
						$temp = explode( " ", $value );
						break;
					}
				
				echo "<tr>";
					if( $key == "Mails" ) echo "<td valign='top' align='left'>".$lang['xml'][$key]."</td>";
					else echo "<td align='left'>".$lang['xml'][$key]."</td>";
					echo "<td align='left'>";
						if( $key == 'Name' or $key == 'Code' or $key == 'Publisher' ) {
							echo "<input readonly type='text' id='".$key."' name='".$key."' value='".$value."'>";
							}
						elseif( $key == 'Deadline' ) {
							echo "<input readonly class='datepicker' id='".$key."' type='text' name='".$key."' value='".$value."'>";
							}
						elseif( $key == 'TrimSize' ) {
							echo "<input id='".$key."_x' type='text' name='".$key."_x' value='".$temp[0]."' style='width: 30px;'> x <input type='text' id='".$key."_y' name='".$key."_y' value='".$temp[2]."' style='width: 30px;'> mm";
							}
						elseif( $key == 'Mails' ) {
							echo "<textarea id='".$key."' name='".$key."' style='width: 170px; height: 60px; resize: none;'>";
								echo str_replace( ";", "\n", $value );
							echo "</textarea>";
							}
						elseif( $temp == '' ) {
							echo "<input id='".$key."' type='text' name='".$key."' value='".$value."'>";
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
			<td colspan="2" align="center" align="center" style="padding-top: 10px;">
				<div onclick="closePanel( 'jobs_settings', 'back', '<?= "line_".$job[0][0]."Float" ?>' )" style="margin-left: 130px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
				<div onclick="menuApply( 'jobs', 'settings', '<?= "line_".$job[0][0]."Float" ?>' )" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["modify"] ?></div>
			</td>
		</tr>
	</table>	
	</div>
</div>
</form>		