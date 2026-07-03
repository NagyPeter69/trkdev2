<form id='subForm' method='post' action=''>
<div>
	<div class='panelTitle'><?= $lang["settings"]["new_group"] ?></div>
	<div class='panelControl' style="width: auto !important;">
	<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
		<tbody>
			<tr>
				<td height='23px' align='left' style='width: 185px; !important'><?= $lang["settings"]["publisher"] ?></td>
				<td align='left'>
				<?
					if( $user[0][8] == 2 ) {
						$publishers = sql_get( "publishers", "1 order by `name` ASC", "*" );
						echo '<select id="publisher" name="publisher">';
							echo '<option value="0">Global</option>';
							for( $i = 0; $i < count( $publishers ); $i++ ) {
								echo '<option ';
								if( $user[0][4] == $publishers[$i][0] ) echo "selected ";
								echo 'value="'.$publishers[$i][0].'">'.$publishers[$i][1].'</option>';
								}
						echo '</select>';
						}
					else {
						echo '<input type="hidden" id="publisher" name="publisher" value="'.$user[0][4].'">';
						}
				?>
				</td>
			</tr>
			<tr>
				<td height='23px' align='left' style='width: 185px; !important'><?= $lang["settings"]["group_name"] ?></td>
				<td align='left'>
					<input type='text' id='name' name='name'>
				</td>
			</tr>
			
			<tr>
				<td colspan="2">
					<div class="rightsTitle">Advertisement Handling</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "ad_view", "ad_send", "ad_upload", "ad_delete", "ad_sizes" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>

			<tr>
				<td colspan="2">
					<div class="rightsTitle">Basic User Handling</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "magazine_upload", "magazine_itemlist", "magazine_flatplan", "magazine_download", "ad-hoc_proof", "acceptPage", "sendHotlink", "handouts" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>

			<tr>
				<td colspan="2">
					<div class="rightsTitle">Issue Administration</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "newIssue", "delIssue", "acceptIssue", "archiveIssue", "stopIssue", "modIssue", "modDdIssue", "lengthIssue", "cancelApprove", "manageFP" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>

			<tr>
				<td colspan="2">
					<div class="rightsTitle">Comment & Markup</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "viewComment", "createComment", "replyComment", "deleteComment" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>

			<tr>
				<td colspan="2">
					<div class="rightsTitle">User & Group Administration</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "accounts_addMember", "accounts_modifyMember", "accounts_removeMember", "accounts_addGroup", "accounts_modifyGroup", "accounts_removeGroup" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>

			<tr>
				<td colspan="2">
					<div class="rightsTitle">Ad-hoc Job Administration</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "jobs_menu", "jobs_create", "jobs_modify", "jobs_delete", "jobs_upload", "jobs_accept", "jobs_archive", "jobs_stop", "jobs_ftp" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>

			<tr>
				<td colspan="2">
					<div class="rightsTitle">Publication Administration</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "magazine_add", "magazine_settings", "magazine_delete", "calendar_realdates", "task_lists" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>

			<tr>
				<td colspan="2">
					<div class="rightsTitle">Superuser Rights</div>
					<table class='panelTable' id='job_names' cellspacing='0' cellpadding='0'>
					<?
						$a = array( "ftp_create", "ftp_modify", "ftp_delete", "pmdallmodify", "reArchive", "sys_log" );
						$rows = ceil( count($a) / 2 );
						
						$x = 0;
						for( $i = 0; $i < $rows; $i++ ) {
							echo "<tr>";
							for( $y = 0; $y < 2; $y++ ) {
								if( $a[$x] != "" ) {
									echo "<td height='23px' align='left' width='33%'>";
										echo "<table width='100%' cellspacing='0' cellpadding='0'><tr>";
										echo "<td align='left'>";
											echo "<input type='checkbox' name='".$a[$x]."' value='1'>";
										echo "</td>";						
										echo "<td height='23px' width='100%' align='left' style='padding-left: 10px;'>".$lang["user_groups"][ $a[$x] ]."</td>";
										echo "</tr></table>";
									echo "</td>";
									}
								$x++;
								}
							echo "</tr>";
							}
					?>
					</table>
				</td>				
			</tr>
									
			<?
				/*$filter = array( "publisher", "id", "name" );
				
				$row_names = sql_get( 'INFORMATION_SCHEMA.COLUMNS', 'TABLE_NAME = "user_groups" AND table_schema = "nyomdake_intra"', 'COLUMN_NAME' );
				for( $i = 2; $i < count( $row_names ); $i++ ) {
					if( !in_array( $row_names[$i][0], $filter ) ) {
						echo "<tr>";
							echo "<td height='23px' align='left'>".$lang["user_groups"][$row_names[$i][0]]."</td>";
							echo "<td align='left'>";
								echo "<input type='checkbox' name='".$row_names[$i][0]."' value='1'>";
							echo "</td>";
						echo "</tr>";
						}
					}*/
			?>	
			<tr>
				<td colspan="2" align="center" style="width: 210px; padding-top: 20px;">
					<div onclick="closePanel( 'accounts_addGroup', 'back')" style="margin-left: 2px; float: inherit; display: inline-block;" class="panelButton">Cancel</div>
					<div onclick="menuApply( 'accounts', 'addGroup')" style="margin-left: 20px; float: inherit; display: inline-block;" class="panelButton">Apply</div>
				</td>
			</tr>
		</tbody>
	</table>
	</div>
</div></form>