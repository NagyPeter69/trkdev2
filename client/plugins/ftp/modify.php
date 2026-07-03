<form id='subForm' method='post' action='?page=create_magazine&opt=ftp&pub=<?= $_GET['pub'] ?>'>
<div>
	<div class='panelTitle'><?= $lang["settings"]["mod_ftp"] ?></div>
	<div>
		<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>
		<?
			echo "<tr>";
				echo "<td align='left' style='width: 130px;'>".$lang["settings"]["publisher"]."</td>";
				echo "<td>";
					if( $user[0][4] == 6 ) {
						echo "<select onchange='ftpList( $(this).val() )' style='margin-left: -1px;' name='u_publisher' id='u_publisher'>";
						$publishers = sql_get( 'publishers', '1 ORDER BY `name` ASC', '*' );
						for( $i = 0; $i < count($publishers); $i++ ) {
							echo "<option ";
							if( $user[0][4] == $publishers[$i][0] ) echo "selected ";
							echo "value='".$publishers[$i][0]."'>".$publishers[$i][1]."</option>";
							}
						echo "</select>";
						}
					else {
						$publisher = sql_get( "publishers", "id='".$user[0][4]."'", "name");
						$publisher[0][0] = str_replace( " ", "", $publisher[0][0] );
						echo "<input type='text' name='u_publisher' id='u_publisher' value='".$publisher[0][0]."' readonly>";
						}				
				echo "</td>";
			echo "</tr>";
			$xml = simplexml_load_file( '../xml/Output_Details.xml' );
			$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );		
			$ftp = getFTPList( $xml, $pub );
			echo "<tr>";
				echo "<td align='left' style='width: 130px;' >";
					echo $lang['settings']['ftp_modify'];
				echo "</td>";
				echo "<td align='left'>";
					echo "<select id='ftp_mod_v' name='ftp_mod_v' onchange=\"changeFTP( $(this).val(), $('#u_publisher').val() )\">";
						echo "<option value=''>".$lang['settings']['ftp_set']."</option>";
					
						foreach( $ftp as $key=>$value ) {
							$name = explode( "_", $key );
							echo "<option ";
							if( $key == $_POST['ftp_mod_v'] ) echo "selected ";
							echo "value='".$key."'>".$name[0]." ( ".$name[1]." )</option>";
							}
							
						echo "<option value='archive'>Archive</option>";
					echo "</select>";
				echo "</td>";
			echo "</tr>";
		?>
		</table>
		<div id='ftp_mod_content' style='display:none;'>
		</div>

		<table class='panelTable' style='margin-top: 5px; width: 100%' id='job_names' cellspacing='0' cellpadding='0'>
			<tr>
				<td style='width:130px;' align='left'>&nbsp;</td>
				<td align='left'>
					<div onclick="closePanel( 'ftp_modify', 'back')" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'ftp', 'modify')" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>
		</table>

	</div>
</div></form>