<?
	if( isset ($_GET['pub']) && $_GET['pub'] != '' ) {
		$user[0][4] = $_GET['pub'];
		}
	else {
		$_GET['pub'] = $user[0][4];
		}
	
	
	if( isset( $_GET['del'] ) && $_GET['del'] != '' ) {
		$node = explode( "_", $_GET['del'] );
		$xml = simplexml_load_file( 'xml/Output_Details.xml' );
		$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );
		
		$xpath = $xml->$pub[0][0]->Outward->$node[1]->$node[0];
		$dom=dom_import_simplexml($xml->$pub[0][0]->Outward->$node[1]->$node[0]);
		$dom->preserveWhiteSpace = false;
		$dom->parentNode->removeChild($dom);
		
		if( $v == 'Content' ) {
			$first = '';
			$xpath = $xml->$pub[0][0]->Outward->Content->children();
			foreach( $xpath as $temp ) {
				if( $temp->getName() != 'Targets' ) {
					$first = $temp->getName();
					break;
					}
				}
			}
			
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;

		file_put_contents( 'xml/Output_Details.xml', $dom->saveXML() );
		$up_to = 'C_Database';
		$sftp = ftp_conn();
		$sftp->put( $up_to.'/Output_Details.xml' , 'xml/Output_Details.xml' , NET_SFTP_LOCAL_FILE);			

		$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
		$values = array( $_SESSION['intra_user'], 'delFTP', $_GET['pub'], '', '', $node[0], time() );
		sql_add( 'action_log', $names, $values );
		
		$ok = 1;
		$e_code_del = $lang["settings"]["success2"];
		}
	
	if( isset( $_POST['ftp_mod'] ) ) {
		$xml = simplexml_load_file( 'xml/Output_Details.xml' );
		$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );
		
		if( isset( $_POST['m_address_url'] ) )
			$_POST['m_address'] = $_POST['m_address_url'];
		else 
			$_POST['m_address'] = $_POST['m_address_1'].'.'.$_POST['m_address_2'].'.'.$_POST['m_address_3'].'.'.$_POST['m_address_4'];
		
		$ok = checkFTP( $_POST['m_address'], $_POST['m_port'], $_POST['m_login'], $_POST['m_pass'] );
		
		if( $ok == '0' ) {
			$ok = 0;
			$e_code4 = $lang["settings"]["ftp_con_error"];
			}
		else {
			$ok = 1;
			$e_code4 = $lang["settings"]["success"];
			if( $ok == 'sftp' ) {
				$_POST['arch_port'] = 22;
				}
				
			$nodes = array( 'Address', 'Port', 'Passive', 'Binary', 'Login', 'Pass', 'Path' );
			$path = explode( "_", $_POST['ftp_mod_v'] );
			foreach( $nodes as $node ) {
				if( $node == 'Pass') {
					$xml->$pub[0][0]->Outward->$path[1]->$path[0]->$node = encrypt_( $_POST['m_'.strtolower($node)] );
					}
				else {
					$xml->$pub[0][0]->Outward->$path[1]->$path[0]->$node = $_POST['m_'.strtolower($node)];
					}
				}
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($xml->asXML());
			$dom->formatOutput = true;
	
			file_put_contents( 'xml/Output_Details.xml', $dom->saveXML() );
			$up_to = 'C_Database';
			$sftp = ftp_conn();
			$sftp->put( $up_to.'/Output_Details.xml' , 'xml/Output_Details.xml' , NET_SFTP_LOCAL_FILE);				

			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
			$values = array( $_SESSION['intra_user'], 'modFTP', $_GET['pub'], '', '', $path[0], time() );
			sql_add( 'action_log', $names, $values );
			}		
		}
	
	if( isset( $_POST['ftp_arch'] ) ) {		
		$xml = simplexml_load_file( 'xml/Output_Details.xml' );
		$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );
		
		$_POST['arch_address'] = $_POST['arch_address_url'];
		$ok = checkFTP( $_POST['arch_address'], $_POST['arch_port'], $_POST['arch_login'], $_POST['arch_pass'] );

		if( $ok == '0' ) {
			$ok = 0;
			$e_code3 = $lang["settings"]["ftp_con_error"];
			}
		else {
			$ok = 1;
			if( $ok == 'sftp' ) {
				$_POST['arch_port'] = 22;
				}
				
			$nodes = array( 'Address', 'Port', 'Passive', 'Binary', 'Login', 'Pass', 'Path' );
			$xml->$pub[0][0]->Outward->Archive = '';
			foreach( $nodes as $node ) {
				if( $node == 'Pass' ) {
					$xml->$pub[0][0]->Outward->Archive->$node = encrypt_( $_POST['arch_'.strtolower($node)] );
					}
				else {
					$xml->$pub[0][0]->Outward->Archive->$node = $_POST['arch_'.strtolower($node)];
					}
				}
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($xml->asXML());
			$dom->formatOutput = true;
	
			file_put_contents( 'xml/Output_Details.xml', $dom->saveXML() );
			$up_to = 'C_Database';
			$sftp = ftp_conn();
			$sftp->put( $up_to.'/Output_Details.xml' , 'xml/Output_Details.xml' , NET_SFTP_LOCAL_FILE);

			$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
			$values = array( $_SESSION['intra_user'], 'changeArchiveFTP', $_GET['pub'], '', '', '', time() );
			sql_add( 'action_log', $names, $values );
			
			$e_code3 = $lang["settings"]["success"];
			}
		}
		
	if( isset( $_POST['ftp_add'] ) ) {
		$xml = simplexml_load_file( 'xml/Output_Details.xml' );
		$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );
		
		$nodes = array( 'INDD', 'Images', 'PDF', 'Packages', 'Softproof' );
		foreach( $nodes as $node ) {
			$xml->$pub[0][0]->Outward->Content->Targets->$node = $_POST['ftp_'.$node];
			}

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
	
		file_put_contents( 'xml/Output_Details.xml', $dom->saveXML() );
		$up_to = 'C_Database';

		$sftp = ftp_conn();
		$sftp->put( $up_to.'/Output_Details.xml' , 'xml/Output_Details.xml' , NET_SFTP_LOCAL_FILE);
		
		$ok = 1;
		$e_code2 = $lang["settings"]["success"];
		}
		
	if( isset( $_POST['ftp_new'] ) ) {
		$host = $_POST['ftp_address_url'];
		$ok = checkFTP( $host, $_POST['ftp_port'], $_POST['ftp_login'], $_POST['ftp_pass'] );
		
		if( $ok == '0' ) {
			$ok = 0;
			$e_code = $lang["settings"]["ftp_con_error"];
			}
		else {
			$ok = 1;
			$e_code = $lang["settings"]["success"];
			$publisher = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
			$publisher = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name');
			
			$xml = simplexml_load_file( 'xml/Output_Details.xml' );
			
			$path = '/FTPdata/'.$publisher[0][0];
			$xpath = $xml->xpath( $path );
			
			if( count( $xpath ) == 0 ) {
				$pub = $xml->addChild( $publisher[0][0] );
				$in = $pub->addChild( 'Inward' );
				$in->addChild( 'Address', '' );
				$in->addChild( 'Port', '' );
				$in->addChild( 'Passive', '' );
				$in->addChild( 'Binary', '' );
				$in->addChild( 'Login', '' );
				$in->addChild( 'Pass', '' );
				$in->addChild( 'Path', '' );
				$out = $pub->addChild( 'Outward' );
				$content = $out->addChild( 'Content' );
				$final = $out->addChild( 'Final' );
				$out->addChild( 'Softproof' );
				$archive = $out->addChild( 'Archive' );
				$archive->addChild( 'Address', '' );
				$archive->addChild( 'Port', '' );
				$archive->addChild( 'Passive', '' );
				$archive->addChild( 'Binary', '' );
				$archive->addChild( 'Login', '' );
				$archive->addChild( 'Pass', '' );
				$archive->addChild( 'Path', '' );
				}
			
			$path = '/FTPdata/'.$publisher[0][0].'/Outward/'.$_POST['ftp_chose'];
			$xpath = $xml->xpath( $path );
			$check = $xml->xpath( $path.'/'.$_POST['ftp_name']  );
			if(  count($check) == 0 ) {
				$encrypt = encrypt_( $_POST['ftp_pass'] );
				$item = $xml->$publisher[0][0]->Outward->$_POST['ftp_chose']->addChild( (string) $_POST['ftp_name'] );
				$item->addChild( 'Address', (string) $host );
				$item->addChild( 'Port', $_POST['ftp_port'] );
				$item->addChild( 'Passive', $_POST['ftp_passive'] );
				$item->addChild( 'Binary', $_POST['ftp_binary'] );
				$item->addChild( 'Login', $_POST['ftp_login'] );
				$item->addChild( 'Pass', "".$encrypt."" );
				$item->addChild( 'Path', $_POST['ftp_path'] );
				
				
				$dom = new DOMDocument();
				$dom->preserveWhiteSpace = false;
				$dom->loadXML($xml->asXML());
				$dom->formatOutput = true;
		
				file_put_contents( 'xml/Output_Details.xml', $dom->saveXML() );
				$up_to = 'C_Database';

				$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date' );
				$values = array( $_SESSION['intra_user'], 'addFTP', $_GET['pub'], '', '', $_POST['ftp_name'], time() );
				sql_add( 'action_log', $names, $values );
		
				$sftp = ftp_conn();
				$sftp->put( $up_to.'/Output_Details.xml' , 'xml/Output_Details.xml' , NET_SFTP_LOCAL_FILE);
				}
			else {
				$ok = 0;
				$e_code = $lang["settings"]["ftp_con_error2"];
				}
			}	
		}

?>

	<form id='new_ftp' method='post' action='?page=create_magazine&opt=ftp&pub=<?= $_GET['pub'] ?>'>
	<div class='setting_row2'>
		<div class='default' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_row_header'><?= $lang["settings"]["new_ftp"] ?></div>
		<div style='float: left; padding-left: 25px; margin-top: -2px;'>
			<button disabled id='ftp_new' name='ftp_new' value='Mentés' style='margin-top: 10px; padding: 5px 10px 5px 10px;'>Mentés</button>
		</div>
		<div style='float: left; padding-left: 20px; margin-top: 10px;'>
		<?
		if( isset( $ok ) ) {
			if( $ok == 0 ) {
				echo "<span style='color: #BA2727;'>".$e_code."</span>";
				}
			if( $ok == 1 ) {
				echo "<span style='color: #489620;'>".$e_code."</span>";
				}
			}
		?>
		</div>
		
		<div style='clear:both;'></div>
		
		<div class='settings_row_content'>
			<table width='100%' cellspacing='0' cellpadding='0'>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_chose'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<select id='ftp_chose' name='ftp_chose'>
							<option value='Content'>Content</option>
							<option value='Final'>Final</option>
							<option value='Softproof'>Softproof</option>
						</select>
					</td>
				</tr>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_name'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<input type='text' onkeypress="return letterCheckName(event)" id='ftp_name' name='ftp_name' value='<?= $_POST['ftp_name'] ?>'>
					</td>
				</tr>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_address'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<input type='text' id='ftp_address_url' name='ftp_address_url' value='<?= $_POST['ftp_address_url'] ?>'>
					</td>
				</tr>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_port'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<input type='text' onkeypress="return isNumberKey(event)" id='ftp_port' name='ftp_port' value='21'>
					</td>
				</tr>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_passive'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<select id='ftp_passive' name='ftp_passive'>
							<option value='Yes'>Igen</option>
							<option value='No'>Nem</option>
						</select>
					</td>
				</tr>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_binary'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<select id='ftp_binary' name='ftp_binary'>
							<option value='true'>Igen</option>
							<option value='false'>Nem</option>
						</select>
					</td>
				</tr>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_login'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<input type='text' id='ftp_login' name='ftp_login' value='<?= $_POST['ftp_login'] ?>' >
					</td>
				</tr>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_pass'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<input type='password' id='ftp_pass' name='ftp_pass'>
					</td>
				</tr>
				<tr>
					<td width='50%' align='right' valign='top'>
						<?= $lang['settings']['ftp_path'] ?>
					</td>
					<td width='50%' align='left' valign='top'>
						<input type='text' id='ftp_path' name='ftp_path' value='<?= $_POST['ftp_path'] ?>'>
					</td>
				</tr>
			</table>
		</div>
	</div></form>

	<form id='mod_ftp' method='post' action='?page=create_magazine&opt=ftp&pub=<?= $_GET['pub'] ?>'>
	<div class='setting_row2'>
		<div class='default' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_row_header'><?= $lang["settings"]["mod_ftp"] ?></div>
		<div style='float: left; padding-left: 25px; margin-top: -2px;'>
			<button disabled id='ftp_mod' name='ftp_mod' value='Mentés' style='margin-top: 10px; padding: 5px 10px 5px 10px;'>Mentés</button>
		</div>
		<div style='float: left; padding-left: 20px; margin-top: 10px;'>
		<?
		if( isset( $ok ) ) {
			if( $ok == 0 ) {
				echo "<span style='color: #BA2727;'>".$e_code4."</span>";
				}
			if( $ok == 1 ) {
				echo "<span style='color: #489620;'>".$e_code4."</span>";
				}
			}
		?>
		</div>
		
		<div style='clear:both;'></div>
		
		<div class='settings_row_content'>
			<table width='100%' cellspacing='0' cellpadding='0'>
			<?
				$xml = simplexml_load_file( 'xml/Output_Details.xml' );
				$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );
				
				$ftp = array();
				$xpath = $xml->$pub[0][0]->Outward->Content->children();
				foreach( $xpath as $temp ) {
					$node = $temp->getName();
					if( $node != 'Targets' ) {
						$x = $temp->$node;
						$x = $xml->$pub[0][0]->Outward->Content->$node->children();
						foreach( $x as $t ) {
							$ftp[$node."_Content"][$t->getName()] = (string) $t;
							}
						}
					}
				$xpath = $xml->$pub[0][0]->Outward->Final->children();
				foreach( $xpath as $temp ) {
					$node = $temp->getName();
					if( $node != 'Targets' ) {
						$x = $temp->$node;
						$x = $xml->$pub[0][0]->Outward->Final->$node->children();
						foreach( $x as $t ) {
							$ftp[$node."_Final"][$t->getName()] = (string) $t;
							}
						}
					}

				$xpath = $xml->$pub[0][0]->Outward->Softproof->children();
				foreach( $xpath as $temp ) {
					$node = $temp->getName();
					if( $node != 'Targets' ) {
						$x = $temp->$node;
						$x = $xml->$pub[0][0]->Outward->Softproof->$node->children();
						foreach( $x as $t ) {
							$ftp[$node."_Softproof"][$t->getName()] = (string) $t;
							}
						}
					}			
				echo "<tr>";
					echo "<td width='50%' align='right' valign='top'>";
						echo $lang['settings']['ftp_modify'];
					echo "</td>";
					echo "<td width='50%' align='left' valign='top'>";
						echo "<select id='ftp_mod_v' name='ftp_mod_v' onchange='changeFTP( $(this).val() )'>";
							echo "<option value=''>".$lang['settings']['ftp_set']."</option>";
						
							foreach( $ftp as $key=>$value ) {
								$name = explode( "_", $key );
								echo "<option ";
								if( $key == $_POST['ftp_mod_v'] ) echo "selected ";
								echo "value='".$key."'>".$name[0]." ( ".$name[1]." )</option>";
								}
						echo "</select>";
					echo "</td>";
				echo "</tr>";
			?>
			</table>
			<div id='ftp_mod_content' style='display:none;'>
			</div>

		</div>
	</div></form>

	<div class='setting_row2'>
		<div class='default' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_row_header'><?= $lang["settings"]["del_ftp"] ?></div>
		<div style='float: left; padding-left: 25px; margin-top: -2px;'>
			<button onclick='deleteFTP(); return false;' style='margin-top: 10px; padding: 5px 10px 5px 10px;'>Törlés</button>
		</div>
		<div style='float: left; padding-left: 20px; margin-top: 10px;'>
		<?
		if( isset( $ok ) ) {
			if( $ok == 0 ) {
				echo "<span style='color: #BA2727;'>".$e_code_del."</span>";
				}
			if( $ok == 1 ) {
				echo "<span style='color: #489620;'>".$e_code_del."</span>";
				}
			}
		?>
		</div>
				
		<div style='clear:both;'></div>
		
		<div class='settings_row_content'>
			<table width='100%' cellspacing='0' cellpadding='0'>
			<?
				$xml = simplexml_load_file( 'xml/Output_Details.xml' );
				$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );
				
				$ftp = array();
				$xpath = $xml->$pub[0][0]->Outward->Content->children();
				foreach( $xpath as $temp ) {
					$node = $temp->getName();
					if( $node != 'Targets' ) {
						$x = $temp->$node;
						$x = $xml->$pub[0][0]->Outward->Content->$node->children();
						foreach( $x as $t ) {
							$ftp[$node."_Content"][$t->getName()] = (string) $t;
							}
						}
					}
				$xpath = $xml->$pub[0][0]->Outward->Final->children();
				foreach( $xpath as $temp ) {
					$node = $temp->getName();
					if( $node != 'Targets' ) {
						$x = $temp->$node;
						$x = $xml->$pub[0][0]->Outward->Final->$node->children();
						foreach( $x as $t ) {
							$ftp[$node."_Final"][$t->getName()] = (string) $t;
							}
						}
					}

				$xpath = $xml->$pub[0][0]->Outward->Softproof->children();
				foreach( $xpath as $temp ) {
					$node = $temp->getName();
					if( $node != 'Targets' ) {
						$x = $temp->$node;
						$x = $xml->$pub[0][0]->Outward->Softproof->$node->children();
						foreach( $x as $t ) {
							$ftp[$node."_Softproof"][$t->getName()] = (string) $t;
							}
						}
					}	
				echo "<tr>";
					echo "<td width='50%' align='right' valign='top'>";
						echo $lang['settings']['ftp_delete'];
					echo "</td>";
					echo "<td width='50%' align='left' valign='top'>";
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
			</table>
		</div>
	</div>
	
	<form id='arch_ftp' method='post' action='?page=create_magazine&opt=ftp&pub=<?= $_GET['pub'] ?>'>
	<div class='setting_row2'>
		<div class='default' style='float: left; height: 40px; width: 10px;'>&nbsp;</div>
		<div class='settings_row_header'><?= $lang["settings"]["arch_ftp"] ?></div>
		<div style='float: left; padding-left: 25px; margin-top: -2px;'>
			<button disabled id='ftp_arch' name='ftp_arch' value='Mentés' style='margin-top: 10px; padding: 5px 10px 5px 10px;'>Mentés</button>
		</div>
		<div style='float: left; padding-left: 20px; margin-top: 10px;'>
		<?
		if( isset( $ok ) ) {
			if( $ok == 0 ) {
				echo "<span style='color: #BA2727;'>".$e_code3."</span>";
				}
			if( $ok == 1 ) {
				echo "<span style='color: #489620;'>".$e_code3."</span>";
				}
			}
		?>
		</div>
		
		<div style='clear:both;'></div>
		
		<div class='settings_row_content'>
			<table width='100%' cellspacing='0' cellpadding='0'>
				<?
				$xml = simplexml_load_file( 'xml/Output_Details.xml' );
				$pub = sql_get( 'publishers', 'id="'.$user[0][4].'"', 'name' );
				
				$nodes = array( 'Address', 'Port', 'Passive', 'Binary', 'Login', 'Pass', 'Path' );
				foreach( $nodes as $node ) {
					$temp = '';
					$value = $xml->$pub[0][0]->Outward->Archive->$node;
					switch( $node ){
						case 'Address':
							$temp = explode( ".", $value );
							break;
						case 'Binary':
							$temp = array( 'true', 'false' );
							break;
						case 'Passive':
							$temp = array( 'Yes', 'No' );
							break;
						}
				
					echo "<tr>";
						echo "<td width='50%' align='right' valign='top'>";
							echo $lang['settings']['ftp_'.strtolower($node)];
						echo "</td>";
						echo "<td width='50%' align='left' valign='top'>";
							if( $node == 'Address' ) {
								echo "<input type='text' id='arch_address_url' name='arch_address_url' value='".$value."'>";
								}
							elseif( $temp != '' ) {
								echo "<select id='arch_".strtolower($node)."' name='arch_".strtolower($node)."'>";
								foreach( $temp as $t ) {
									echo "<option ";
									if( $value == $t ) echo "selected ";
									echo "value='".$t."'>".$t."</option>";
									}
								echo "</select>";
								}
							elseif( $node == 'Pass' ) {
								echo "<input type='text' id='arch_".strtolower($node)."' name='arch_".strtolower($node)."' value='".decrypt_( $value )."'>";
								}
							else {
								echo "<input type='text' id='arch_".strtolower($node)."' name='arch_".strtolower($node)."' value='".$value."'>";
								}
						echo "</td>";
					echo "</tr>";
					}
				?>
			</table>
		</div>
	</div></form>
	
<script>
function deleteFTP() {
	if( confirm('Biztosan véglegesen törli a kiválasztott FTP-t?') ) {
		window.location.href = '?page=create_magazine&opt=ftp&pub=<?= $_GET["pub"] ?>&del='+$('#ftp_del_v').val();
		}
	
	}
	
function changeFTP( val ) {
	if( val == '' ) {
		$('#ftp_mod_content').hide('fast', function() {
			$('#ftp_mod_content').html( '' );
			});
		$("#ftp_mod").attr("disabled", "disabled");
		$('#ftp_mod_content').hide('fast');
		}
	else {
		$.ajax	({
			url:"engine/ajax.php",
			data: 'op=get_ftp&pub=<?= $_GET["pub"] ?>&node='+val,
			dataType: 'json',
			success:function( data ) {
				$('#ftp_mod_content').html( data );
				$('#ftp_mod_content').show('fast');
			
				$('#mod_ftp :input').keyup( function() {
					if( $('#ftp_mod_content').css('display') != 'none' ) {
						if( $('#m_name').val() != '' && $('#m_address_1').val() != '' && $('#m_address_2').val() != '' && $('#m_address_3').val() != '' && $('#m_address_4').val() != '' && $('#m_port').val() != '' && $('#m_login').val() != '' && $('#m_pass').val() != '' ) {
							$("#ftp_mod").removeAttr("disabled");
							}
						else {
							$("#ftp_mod").attr("disabled", "disabled");
							}
						}
					else {
						$("#ftp_mod").attr("disabled", "disabled");
						}
					});
				$('#mod_ftp :input').keyup();
				}
			});
		}
	}
changeFTP( $('#ftp_mod_v').val() );

function letterCheckName(e) {
	var regex = new RegExp("^[a-zA-Z]+$");
	var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
	
	if (regex.test(str)) {
        return true;
    }

    e.preventDefault();
    return false;
	}

function letterCheck(e) {
	var regex = new RegExp("^[a-zA-Z0-9]+$");
	var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
	
	if (regex.test(str)) {
        return true;
    }

    e.preventDefault();
    return false;
	}

$('#sp_ftp :input').keyup( function() {
	if( $('#sp_name').val() != '' && $('#sp_address_1').val() != '' && $('#sp_address_2').val() != '' && $('#sp_address_3').val() != '' && $('#sp_address_4').val() != '' && $('#sp_port').val() != '' && $('#sp_login').val() != '' && $('#sp_pass').val() != '' ) {
		$("#ftp_sp").removeAttr("disabled");
		}
	else {
		$("#ftp_sp").attr("disabled", "disabled");
		}
	});
$('#sp_ftp :input').keyup();	
	
$('#arch_ftp :input').keyup( function() {
	if( $('#arch_name').val() != '' && $('#arch_address_1').val() != '' && $('#arch_address_2').val() != '' && $('#arch_address_3').val() != '' && $('#arch_address_4').val() != '' && $('#arch_port').val() != '' && $('#arch_login').val() != '' && $('#arch_pass').val() != '' ) {
		$("#ftp_arch").removeAttr("disabled");
		}
	else {
		$("#ftp_arch").attr("disabled", "disabled");
		}
	});
$('#arch_ftp :input').keyup();

$('#new_ftp :input').keyup( function() {
	if( $('#ftp_name').val() != '' && $('#ftp_address_1').val() != '' && $('#ftp_address_2').val() != '' && $('#ftp_address_3').val() != '' && $('#ftp_address_4').val() != '' && $('#ftp_port').val() != '' && $('#ftp_login').val() != '' && $('#ftp_pass').val() != '' ) {
		$("#ftp_new").removeAttr("disabled");
		}
	else {
		$("#ftp_new").attr("disabled", "disabled");
		}
	});

</script>	
	
