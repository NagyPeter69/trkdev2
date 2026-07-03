<form id='subForm' method='post' action=''>
<div>
	<div class='panelTitle'><?= $lang["settings"]["del_ftp"] ?></div>
	<div>
		<table class='panelTable' width='100%' cellspacing='0' cellpadding='0'>
		<?
			echo "<tr>";
				echo "<td align='left' style='widtH: 130px;'>".$lang["settings"]["publisher"]."</td>";
				echo "<td>";
					if( $user[0][4] == 6 ) {
						echo "<select onchange='ftpList2( $(this).val() )' style='margin-left: -1px;' name='u_publisher' id='u_publisher'>";
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
				echo "<td align='left'>";
					echo $lang['settings']['ftp_delete'];
				echo "</td>";
				echo "<td align='left'>";
					echo "<select id='ftp_del_v' name='ftp_del_v'>";
					ksort($ftp);
					foreach( $ftp as $key=>$value ) {
						$name = explode( "_", $key );
						echo "<option value='".$key."'>".$name[0]." ( ".$name[1]." )</option>";
						}
					echo "</select>";
				echo "</td>";
			echo "</tr>";					
		?>
			<tr>
				<td>&nbsp;</td>
				<td colspan="2" align="left" style="padding-top: 10px;">
					<div onclick="closePanel( 'ftp_delete', 'back')" style="margin-left: 2px;" class="panelButton"><?= $lang["standard"]["cancel"] ?></div>
					<div onclick="menuApply( 'ftp', 'delete')" style="margin-left: 20px;" class="panelButton"><?= $lang["standard"]["apply"] ?></div>
				</td>
			</tr>
		</table>
	</div>
</div>
</form>