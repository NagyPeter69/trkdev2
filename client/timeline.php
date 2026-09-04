
<link rel="stylesheet" href="css/jquery-ui.css">
<link rel="stylesheet" href="css/timeline.css">
<script>
jQuery(document).ready(function(){
    $( document ).tooltip({
        tooltipClass: "floatMenu"
        });

    $("[title]").each(function(){
    	$(this).tooltip({ tooltipClass: "floatMenu", content: $(this).attr("title")} );
		});
	});
</script>
<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
<?

$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
if( isset( $_GET['code'] ) ) {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" AND code="'.$_GET['code'].'"', '*' );
	}
else {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
	$_GET['code'] = $pub[0][10];
	}
$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );

if( !checkOwner( array( 'publications', 'id', $pub[0][0] ), $user ) ) {
 	header("Location: ?page=create_magazine");
 	}

$time = strtotime( $pub[0][11] );
setlocale(LC_ALL,'hungarian');
$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) );

if( $magazine[0][10] == "Adhoc" ) {
	echo '<script>window.location.href = "?page=filetransfer&id='.$pub[0][0].'&type=pub";</script>';
	}

?>

<div id='ad_table_wrapper' style='width: 100%; float: left; overflow: auto;'>
<table id='ad_table' cellspacing='0' cellpadding='0' border='0'>
	<tr>
		<td valign="top" style="background: rgb( 227, 227, 227); width: 229px;">	
			<div id='set' style="background: rgb(227, 227, 227);">	
				<div style="padding: 10px; padding-bottom: 9px !important; text-align: left;">
				<?php if( $user[0][4] != 0 ) { ?>	
					<div style='float: left;'>
						<select style="margin-left: -1px;" onchange="Redirect( $(this).val() )">
						<?
							$pubs = getShortPubs( $user[0] );
			
							for( $i = 0; $i < count( $pubs ); $i++ ) {
								$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code, type' );

								echo "<option value='".$pubs[$i][0]."_".$pubs[$i][10]."' ";
								if( $_GET['code'] == $pubs[$i][10] && $_GET['id'] == $pubs[$i][0] )
									echo "selected";
								echo ">".( $magazine[0][1] == "Regular" ? $magazine[0][0]." ".$pubs[$i][10] : $magazine[0][0] )."</option>";
								}
						?>
						</select>
					</div>
					<div style='float: left; margin-left: 5px; margin-top: 1px;'>
						<?
						$pubf = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
						$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name, id, code' );
						
						$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
						?>
						<img title="<?= $magf[0][0] ?><br/>Issue: <?= $pubf[0][10] ?><br/>Deadline: <?= $pubf[0][11] ?>" src="images/icons/info.png" height="20px">
					</div>
					<div style='clear:both;'></div>	
				<?php } else {
					$pubs = getShortPubs( $user[0] );
					$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code' );
					$pubf = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
					$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name, id, code' );
					$pubf[0][10] = "";
					} ?>
				</div>	
			</div>
			
			<div id="logSettings">
				<div style="float:left; margin-left: 8px;"><?= $lang["flatplan"]["settings"] ?></div>
				<div class="showLogSettingsPanel" style="float:right; margin-right: 13px; margin-top: -3px;">
					<img class="clickIgnore" style="cursor:pointer;" src="images/settings.png">
				</div>
			
				<div id='logSettingsPanel' class="floatMenu">
					<form id='logSettingsForm'>
					<table id="logSettingsPanelTable" width="100%" cellspacing="0" cellpadding="0">

						<?
							echo "<tr><td align='left' colspan='3'>";
								echo "<div class='panelSubTitle'>".$lang["flatplan"]["flatplan"]."</div>";
							echo "</td></tr>";
							$deny = array();
							if( $user[0][4] == 6 ) {
								$deny[] = 'backArticle';
								}
							else {
								$deny[] = 'newArticle';
								}
						
						
						
							$logSettings = sql_aget( 'userLogSettings', 'user="'.$_SESSION['intra_user'].'" LIMIT 1', '*' );
							// See client/flatplan.php's identical guard - accounts
							// with no userLogSettings row (Temp/Adhoc-scoped ones)
							// otherwise fatal here (PHP 8 array_reverse(null)).
							$logSettings = $logSettings[0] ?? array();
							$logSettings = array_reverse($logSettings, true);
							unset( $logSettings['id'] ); unset( $logSettings['user'] );
							$i = $m = 0; $c = 1;
							$title = array( $lang["flatplan"]["comments"], $lang["flatplan"]["article"], $lang["flatplan"]["ads"] );
						
							foreach( $logSettings as $name => $value ) {						
									if( !in_array( $name, $deny ) ) {								
										echo "<tr><td align='left'><input style='margin-left: -1px;' name='logSettingsOption' type='checkbox' ";
										if( $value ) {
											echo "checked ";
											}
										echo "value='".$name."'>".$lang['logSettings'][ $name ]."</td></tr>";
										$i++;
										$c++;
										if( $i == 4 ) {
											if( $c < count( $logSettings ) && $c > 1 && $m < 1 ) {
												echo "<tr><td colspan='3' align='left'>";
													echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
														echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
														echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$title[$m]."</div>";
													echo "</div>";
												echo "</td></tr>";
												$m++;
												$i = 0;
												}
											
											}
											
										if( $i == 3 ) {
											if( $c < count( $logSettings ) && $c > 4 && $m < 2 ) {
												echo "<tr><td colspan='3' align='left'>";
													echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
														echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
														echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$title[$m]."</div>";
													echo "</div>";
												echo "</td></tr>";
												$m++;
												$i = 0;						
												}
											
											}		
										if( $i == 1 ) {
											if( $c < count( $logSettings ) && $c > 6 && $m == 1 ) {
												echo "<tr><td colspan='3' align='left'>";
													echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
														echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
														echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$title[$m]."</div>";
													echo "</div>";
												echo "</td></tr>";
												$m++;
												$i = 0;
												}
											}
										}
								}
						echo "<tr><td colspan='3' align='left'>";
							echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
								echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
								echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$lang["flatplan"]["special"]."</div>";
							echo "</div>";
						echo "</td></tr>";
						echo "<tr><td colspan='3' align='left'>";
							echo $lang["flatplan"]["backward"].":&nbsp;<select name='backward'>";
						
							$options = array( '15 '.$lang["flatplan"]["minute"], '30 '.$lang["flatplan"]["minute"], '1 '.$lang["flatplan"]["hour"], '12 '.$lang["flatplan"]["hours"], '1 '.$lang["flatplan"]["day"], '4 '.$lang["flatplan"]["days"], '8 '.$lang["flatplan"]["days"] );
							$values = array( '15', '30', '60', '360', '1440', '5760', '11520' );
							for( $i = 0; $i < count( $options ); $i++ ) {
								echo "<option value='".$values[$i]."' ";
								if( $user[0][12] == $values[$i] )
									echo "selected";
								echo ">".$options[$i]."</option>";
								}
							echo "</select>";
						echo "</td></tr>";
						?>
					</table>
					</form>
				</div>
			</div>
			<div id="liveLog" style="background: rgb(227, 227, 227); position: absolute; display: block; overflow-x: hidden; overflow-y: auto; width: 229px;"></div>
			<div id="backButton" style='position: absolute; margin-left: 71px; bottom: 10px; font-size: 13px; height: 29px;'>
				<div onclick="window.location.href='?page=create_magazine'" style="margin-left: 1px; margin-top: 4px;" class="panelButton"><?= $lang["publications"]["back"] ?></div>
			</div>	
		</td>
		<td>
			<div id='pack_list' style="overflow: auto; text-align: center; font-size:14px;">
			<?
			$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher' );
			$magazines = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `id` ASC', '*' );
			$magazines = sql_get( 'magazines', 'id="'.$magazines[0][2].'" ORDER BY `id` ASC', '*' );	
			
			$fullmagazine = $pubf[0][6];
			
			$check = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND page>".$fullmagazine." AND type!='PRE' AND fin='1'", "*" );
			$fullmagazine += count( $check );
			
			$check = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND type!='ad' AND type!='magazine' AND type!='PRE' ", "*" );
			$fullmagazine += count( $check );
			
			/*$check = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND type!='ad' AND state!='' AND fin='1'", "*" );
			$fullmagazine += count( $check );*/
			
			$check = sql_aget( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND fin='1' AND width != '1'", "*" );
			for( $x = 0; $x < count( $check ); $x++ ) {
				$fullmagazine -= ( $check[$x]["width"] - 1 );
				}
							
			$txt = '';
			$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );

			$timeline = array();
			switch( $user[0][17] ) {
				case 'en':
					setlocale(LC_ALL,'en_GB');
					break;
				case 'hu':
					setlocale(LC_ALL,'hu_HU');
					break;
				}

			?>				
				<div class='timebox' style='width: 745px; max-width: 560px; float: left; padding-right: 10px;'>
					<div class='pubDetail' style='color: #292377; text-align: left; margin-top: 34px; margin-left: 37px;'>
						<div>
						<?php
						$dl = "";
						if( !empty( $pub[0][11] ) ) {
							$dl = date( " F j, Y, G:i", strtotime( $pub[0][11] ) );
							}
						?>
						<?= 
							"<div><b style='font-size: 17px;'>".$magf[0][0]." • ".$magf[0][2].$pubf[0][10]." ".( !empty( $pubf[0][17] ) ? "• ".$pubf[0][17] : "" )."</b></div>
							<div style='padding-top: 11px; font-size: 14px; color: #555; font-weight: normal; '>".sprintf( $lang["timeline"]["totalpages"], $pubf[0][6] )." • ".$lang['timeline']['deadline'].": ".$dl."</div>";
						?>
						</div>
						<div style='float: left; margin-top: 25px; color: #000;'>
							<div>Status: <b><?
										if( $pub[0][12] == "current" ) {
											echo $lang["publications"]["active"];
											}
										else {
											echo $lang["publications"][ $pub[0][12] ];
											}
									?></b>
							</div>
							<?php
							if( $pub[0][12] == "archived" or $pub[0][12] == "approved" ) {
								if( $user[0][4] == "6" ) {
									echo "<div style='margin-top: 15px;'>";
										echo "<button onclick='downloadCSV(); return false;'>".$lang["timeline"]["downloadcsv"]."</button>";
									echo "</div>";
									}
								}
							?>
						</div>
						<?
						$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
						$xpath = $xml->xpath('/Publications');
						foreach($xpath as $temp) {
							for( $x = 0; $x < count( $temp->Item ); $x++ ) {
								if( $temp->Item[$x]->Code == $magazines[0][3] ) {
									break;
									}
								}
							}
						$cur = (string) $xml->Item[$x]->Current;
						$process = getProcess( $magazines[0][3] );
						$management = getPubButtons( $pub[0][12], $pub[0], $process, $rights );
						/*$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
						$xpath = $xml->xpath('/Publications');
						foreach($xpath as $temp) {
							for( $x = 0; $x < count( $temp->Item ); $x++ ) {
								if( $temp->Item[$x]->Code == $magf[0][2] ) {
									break;
									}
								}
							}
						$cur = (string) $xml->Item[$x]->Current;*/
						?> 
						<div onclick="issueOperation({<?= $management ?>}, '<?= $magf[0][2] ?>', '<?= $pubf[0][10] ?>' )" style='float: left; margin-left: 5px; margin-top: 22px;'>
							<img class="<?= $magf[0][2] ?>_<?= $pubf[0][10] ?>" style="cursor:pointer;" src="images/settingsGray.png">
						</div>
						<div style='clear:both;'></div>
					</div>
					
					<div class='pubDetail' style='margin-left: 37px;'>
						<div style='float: left;'>
							<div><b><?= $lang["timeline"]["pubstatus"] ?></b></div>
						</div>
						<div style='clear:both;'></div>
						<div style=' display: table; width: 100%; height: 130px;'>
							<div style='width: 350px; padding-right: 50px; display: table-cell; vertical-align: middle;'>
								<table class='chartNames' style='width: 100%' cellspacing="0" rowspacing="0">
									<tr>
										<td height="21px;">
											<div class='colorBox' style='background: rgb( 64, 195, 20 ); float:left;'></div>
											<div style='float:left;'><?= $lang["timeline"]["approvedpages"] ?></div>
										</td>
										<td height="21px" width="40px" align='right'>
											<b><?
											$percents = array();
											
											if( $magazine[0][3] == "BAV" or $magazine[0][3] == "ZWE" ) {
												$checker = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND status='2' GROUP BY `page`", "id" );
												}
											else {
												$checker = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND status='2' AND fin='1' GROUP BY `page`", "id" );
												}
											$approved = count( $checker );
											
											echo $approved;
											?></b>
										</td>
										<td height="21px;" width="100px" align='right'>
											<b><?
											$percent = intval( $approved/$fullmagazine*100 );
											echo $percent." %";
											$percents[] = $percent;
											?></b>
										</td>
									</tr>
									<tr>
										<td height="21px;">
											<div class='colorBox' style='background: rgb( 50, 218, 255 ); float:left;'></div>
											<div style='float:left;'><?= $lang["timeline"]["openrequest"] ?></div>
										</td>
										<td height="21px;" width="40px" align='right'>
											<b><?
											$counter = 0;
											$checker = sql_get( "comments", "pub_id='".$pubf[0][0]."' AND status='' AND parent='0' GROUP by `page`", "page, pageVersion" );
											for( $i = 0; $i < count( $checker ); $i++ ) {
												$check = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND page='".$checker[$i][0]."' AND state='".$checker[$i][1]."' AND status!='2' AND status!='3'", "id" );
												if( $check[0][0] != "" ) $counter++;
												}
											
											echo $counter;
											
											$chk = sql_get( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND status='3'", "id" );
											$rejected = count( $chk );
											$rejectedPercent = intval( $rejected/$fullmagazine*100 );
											?></b>
										</td>
										<td height="21px;" width="100px" align='right'>
											<b><?
											$percent = intval( $counter/($fullmagazine)*100 );
											echo $percent." %";
											$percents[] = $percent;
											?></b>
										</td>
									</tr>
									<tr>
										<td height="21px;">
											<div class='colorBox' style='background: rgb( 255, 65, 26 ); float:left;'></div>
											<div style='float:left;'><?= $lang["timeline"]["rejectedpages"] ?></div>
										</td>
										<td height="21px;" width="40px" align='right'>
											<b><?
											echo $rejected;
											?></b>
										</td>
										<td height="21px;" width="100px" align='right'>
											<b><?
											echo $rejectedPercent." %";
											$percents[] = $rejectedPercent;
											?></b>
										</td>
									</tr>
								</table>
							</div>
							<div style='float:right;'>
								<div id='pubPie' class='pieBorder'>
									<?
									$pieRemain = 360;
									$i = $s = 0;
									$bigest = -1;
									$start = $value = array();
									foreach( $percents as $percent ) {
										$slice = intval( 360/100*$percent );	
										$value[] = $slice;
										$start[] = $s;
										$pieRemain -= $slice;
										$s += $slice;
										
										if( $value[$i] > $value[$bigest] ) {
											if( $percents[$i] > 50 ) {
												$bigest = $i;
												}
											}
										
										$i++;
										}
									echo '<link rel="stylesheet" type="text/css" href="css/piechart.php?height=100&width=100&id=pubPie&value='.serialize( $value ).'&start='.serialize( $start ).'&percents='.serialize( $percents ).'">';
									for( $i = 0; $i < count( $percents ); $i++ ) {
										if( $percents[$i] != 0 ) {
											echo "<div class='pie pieChart".$i." "; if( $i == $bigest ) echo " big"; echo "' data-start='".$start[$i]."' data-value='".$value[$i]."'></div>";
											}
										}
									?>

								</div>		
							</div>
						</div>
					</div>
					<div class='fpDetail' style='margin-left: 37px;'>
						<div style=' display: table; width: 100%; height: 100px; margin-top: 20px;'>
							<div style='width: 350px; padding-right: 50px; display: table-cell; vertical-align: middle;'>
								<table class='chartNames' style='width: 100%' cellspacing="0" rowspacing="0">
									<tr>
										<td height="21px;">
											<div class='colorBox' style='background: rgb( 254, 144, 1 ); float:left;'></div>
											<div style='float:left;'><?= $lang["timeline"]["adsflatplan"] ?></div>
										</td>
										<td height="21px;" width="40px" align='right'>
											<b><?
											$percents = array();
											$ads = 0;
											
											$planner = sql_aget( "flatplan_planner", "pub_id='".$pubf[0][0]."' AND type='ad' AND mixed='0'", "*" );
											$ads = count( $planner );
											/*$counter = 0;
											$ads = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2'", "*" );
											$full = count( $ads );
											$uploaded = $waiting = 0;
											for( $i = 0; $i < count( $ads ); $i++ ) {
												if( $ads[$i][8] != '' ) {
													$uploaded++;
													}
												else {
													$waiting++;
													}
												}*/
												
											echo $ads;
											?></b>
										</td>
										<td height="21px;" width="100px" align='right'>&nbsp;</td>
									</tr>
									<tr>
										<td height="21px;">
											<div class='colorBox' style='background: rgb( 254, 184, 0 ); float:left;'></div>
											<div style='float:left;'><?= $lang["timeline"]["adswaiting"] ?></div>
										</td>
										<td height="21px;" width="40px" align='right'>
											<b><?
											
											$filled = 0;
											for( $i = 0; $i < count( $planner ); $i++ ) {
												$check = sql_aget( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND fin='1' AND type='ad' AND page='".$planner[$i]["pos"]."'", "*" );
												if( !empty( $check[0]["id"] ) ) {
													$filled++;
													}
												}
											
											echo $filled;
											?></b>
										</td>
										<td height="21px;" width="100px" align='right'>
											<b><?
											$percent = $filled / $ads * 100;
											if( $ads == "0") {
												$percent = 0;
												}
											
											$percents[] = $percent;										
											echo $percent." %";
											?></b>
										</td>
									</tr>
								</table>
							</div>

							<div style='float:right;'>
								<div id='fpPie' class='fpBorder'>
									<?
									$pieRemain = 360;
									$i = $s = 0;
									$bigest = 0;
									$start = $value = array();
									foreach( $percents as $percent ) {
										$slice = intval( 360/100*$percent );	
										$value[] = $slice;
										if( $value[$i-1] > 0 )
											$start[] = $s;
										else 
											$start[] = 0;
										$pieRemain -= $slice;
										$s += $slice+1;
										
										if( $value[$i] > $value[$bigest] ) {
											if( $percents[$i] > 50 ) {
												$bigest = $i;
												}
											}
										
										$i++;
										}
									if( $pieRemain > 0 ) {
										$value[$bigest] += $pieRemain;
										if( $start[($bigest+1)] != "" )
											$start[($bigest+1)] += $pieRemain;
										}
									echo '<link rel="stylesheet" type="text/css" href="css/piechart.php?big='.$bigest.'&height=100&width=100&id=fpPie&value='.serialize( $value ).'&start='.serialize( $start ).'&percents='.serialize( $percents ).'">';
									for( $i = 0; $i < count( $percents ); $i++ ) {
										if( $percents[$i] != 0 ) {
											echo "<div class='pie pieChart".$i." "; if( $i == $bigest ) echo " big"; echo "' data-start='".$start[$i]."' data-value='".$value[$i]."'></div>";
											}
										}
									?>
								</div>		
							</div>
						</div>
					</div>
					
					<div style='margin-left: 37px; margin-bottom: 0px;'>
						<div class='pubDetail' style='margin-top: 40px !important;'>
							<div class='title' style='text-align: left; color: #292377; font-size: 15px; padding-top: 6px;'><b><?= $lang["timeline"]["history"] ?></b></div>
								<?
								$txt = "<div class='timerow' style='height: 30px; margin-left: 99px;'>";
								$dayBackward = -13;
			
								for( $b = 2; $b > $dayBackward; $b-- ) {
									$calDate = strtotime( $b." days" );
									$day = date( "j", $calDate );
									$customClass = "";
			
									$weekend = $today = 0; $custom = "";
									if( $day == date( "j" ) ) {
										$today = 1;
										}
			
									if( date('N', $calDate ) == 7 ) {
										$b--;
										$day = ($day-1)."/".$day;
										}
										
									$txt .= "<div class='tlHeaderBox' ".$custom.">";					
										$txt .= "<div class='tlNum'>";
											if( !$today ) $txt .= $day;
											else $txt .= $lang["timeline"]["today"];
										$txt .= "</div>";
									$txt .= "</div>";
									}
								$txt .= "</div>";
						
								echo $txt;					
							?>
							</div>
								<?
								if( $process == "Softproof" ) {
									$rows = array( "pdf", "approval" );
									$links = array(	
												"?page=flatplan",	
												);
									}
								else {
									$rows = array( "indd", "pdf", "ads", "approval" );
									$links = array(	
												"?page=publication&id=".$_GET['id']."&code=".$_GET['code'],	
												"?page=flatplan",	
												"?page=advertisement" 
												);
									}
						
								for( $i = 0; $i < count( $rows ); $i++ ) {
						
									echo "<div id='".$rows[$i]."' class='detail'>";
										echo "<div class='link' onclick=\"window.location.href='".$links[$i]."'\" style='float:left; margin-top: 40px; font-size: 12px;'>".$lang['timeline'][ $rows[$i] ]."</div>";
										echo "<div class='timerow' style='margin-left: 86px;'>";
										$ads = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2'", "*" );
										$sum = count( $ads );
										$ads = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2' AND uploaded != ''", "*" );
										$sumFP = count( $ads );
										$approval = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND code='".$magf[0][2]."' AND issue='".$pubf[0][10]."' AND status='2'", "*" );
										$sumAP = count( $approval );
										if( $remain > 0 ) {
											$add = $month_day_left+1;
											for( $b = $remain-1; $b >= 0; $b-- ) {
												$calDate = strtotime( ($b+$add-14)." days" );
												$tempDate = date( "Y-m-d", $calDate );
												$tempGet = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2' AND date LIKE '".$tempDate."%'", "*" );
												$sum -= count($tempGet);
												$tempGet = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2' AND status='2' AND uploaded != '' AND date LIKE '".$tempDate."%'", "*" );
												$sumFP -= count($tempGet);
												$tempGet = sql_get( "action_log", "action='approvePage' AND issue='".$pubf[0][10]."' AND magazine='".$magf[0][1]."'", "*" );
												$approved = 0;
												for( $x = 0; $x < count( $tempGet ); $x++ ) {
													$logDate = date( "Ymd", $tempGet[$x][7] );
													$timeDate = date( "Ymd", $calDate );
													if( $logDate == $timeDate ) {
														$approved++;
														}
													}
												$sumAP -= $approved;
												$graph = dayBreak( $rows[$i], $calDate, $magf[0][1], $pubf[0][10], $lang, $sum, $sumFP, $sumAP, $process );
												$day = date( "j", $calDate );
						
												$today = 0; $custom = "";
												
												if( $calDate > time() ) {
													$custom = "background-color: #FFFFE8;";
													}
													
												if( $day == date( "j" ) ) {
													$today = 1;
													$custom = "background-color: #D1E5FF;";
													}
						
												if( date('N', $calDate ) == 7 ) {
													$b--;
													$day = ($day-1)." / ".$day;
													$custom = "background-color: #FBFF8A";
													}
													
												echo "<div class='detailBox' style='".$custom."'>";								
													echo "<div id='".$rows[$i]."_".$day."' class='tlNum'>";
														echo $graph;
													echo "</div>";
												echo "</div>";
												}
											}
										for( $b = 2; $b > $dayBackward; $b-- ) {
											$calDate = strtotime( $b." days" );
											$tempDate = date( "Y-m-d", $calDate );
											$tempGet = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2' AND date LIKE '".$tempDate."%'", "*" );
											$sum -= count($tempGet);
											$tempGet = sql_get( "ads", "pub_id='".$pubf[0][0]."' AND status='2' AND status='2' AND date LIKE '".$tempDate."%'", "*" );
											$sumFP -= count($tempGet);
											$tempGet = sql_get( "action_log", "action='approvePage' AND issue='".$pubf[0][10]."' AND magazine='".$magf[0][1]."'", "*" );
											$approved = 0;
											for( $x = 0; $x < count( $tempGet ); $x++ ) {
												$logDate = date( "Ymd", $tempGet[$x][7] );
												$timeDate = date( "Ymd", $calDate );
												if( $logDate == $timeDate ) {
													$approved++;
													}
												}
											$sumAP -= $approved;
											$graph = dayBreak( $rows[$i], $calDate, $magf[0][1], $pubf[0][10], $lang, $sum, $sumFP, $sumAP, $process );
											$day = date( "j", $calDate );
											$customClass = "";
					
											$weekend = $today = 0; $custom = "";
											if( $calDate > time() ) {
												$custom = "background-color: #FFFFE8;";
												}

											if( $day == date( "j" ) ) {
												$today = 1;
												$custom = "background-color: #D1E5FF;";
												}
					
											if( date('N', $calDate ) == 7 ) {
												$b--;
												$day = ($day-1)." / ".$day;
												$custom = "background-color: #FBFF8A";
												}
												
											echo "<div class='detailBox' style='".$custom."'>";					
												echo "<div id='".$rows[$i]."_".$day."' class='tlNum'>";
													echo $graph;
												echo "</div>";
											echo "</div>";
											}
										echo "</div>";	
									echo "</div>";
									}
					
							?>		
						</div>
					
					<div class='deliverbox' style='width: 200px;'>
						<div class='pubDetail' style='color: #292377; text-align: left; margin-top: 34px; margin-left: 37px; margin-bottom: 34px;'>
							<div>
								<b style='font-size: 15px;'>Courier log</b>
								<i id="<?= $_GET["id"] ?>_button" onclick="addDeliver( '<?= $_GET["id"] ?>' )" class="fas fa-plus-square" style="font-size: 16px;"></i>
							</div>
	
							<div style="padding-top: 5px;">
								<table class='deliver_table' cellspacing="0" cellpadding="0">
								<?php
								echo "<thead><tr>";
									echo "<td>Date</td>";
								echo "</tr></thead>";
							
								echo "<tbody id='deliverBody'></tbody>";
								?>
								</table>
							</div>
	
							<div style='clear:both;'></div>
						</div>
					</div>
							
				</div>
				
				<?php if( $user[0][4] == "6" ) { ?>			
				<div class='imagebox' style='float: left;'>
					<div class='pubDetail' style='color: #292377; text-align: left; margin-top: 34px; margin-left: 37px;'>
						<div>
							<b style='font-size: 15px;'>Image List</b><input type='text' id='imgSearch' name='imgSearch' style='margin-left: 10px; font-size: 13px;'><button onclick='loadIMG( $("#imgSearch").val() )' style='margin-top: 1px; height: 22px;'><i class="fas fa-search" style='font-size: 12px; padding: 3px 0px 0px 0px; height: 13px;'></i></button>
						</div>

						<div style="padding-top: 0px;">
							<div style="clear:both; padding-bottom: 5px;"></div>
							<table class='image_map_table' cellspacing="0" cellpadding="0">
							<?php
							echo "<thead><tr>";
								echo "<td style='width: 426px;'>Image Name</td>";
								
								if( $process != "Full" && $process != "Hybrid" ) {
									echo "<td style='width: 83px;'>Masked</td>";
									}
									
								echo "<td style='width: ".( ( $process == "Full" or $process == "Hybrid" ) ? "176px" : "80px" )."; ".( ( $process == "Full" or $process == "Hybrid" ) ? "padding-right: 12px !important;" : "" )." '>Retouch</td>";
							echo "</tr></thead>";
						
							echo "<tbody id='imgBody' style='height: 200px; overflow-y:scroll;'></tbody>";
							?>
							</table>
						</div>

						<div style='clear:both;'></div>
					</div>
				</div>
				<?php } ?>
				
				<div class='proofbox' style='float: left;'>
					<div class='pubDetail' style='color: #292377; text-align: left; margin-top: 34px; margin-left: 37px;'>
						<div>
							<b style='font-size: 15px;'>Hardcopy Proof List</b>
						</div>
						<div style="padding-top: 5px;">
							<table class='image_map_table' cellspacing="0" cellpadding="0">
								<thead>
									<tr>
										<td style='width: 426px;'>Page</td>
										<td style='width: 83px;'>Flatplan</td>
										<td style='width: 80px;'>Quantity</td>
									</tr>
								</thead>
								
								<tbody style='height: 200px; overflow-y:scroll;'>
								<?php

								$proofs = sql_aget( "pageinfo", "code='".$magazine[0][3]."' AND issue='".$pubf[0][10]."' AND proofCounter != '0' ORDER BY page ASC", "*" );
								for( $i = 0; $i < count( $proofs ); $i++ ) {
									echo "<tr>";
										if( $proofs[$i]["type"] == "ad" ) {
											$ad = sql_aget( "ads", "id='".$proofs[$i]["pack_id"]."'", "*" );
											echo "<td style='width: 427px; min-width: 427px; max-width: 427px; padding-top: 2px; padding-bottom: 2px;'>".$ad[0]["name"]." ".$ad[0]["size"]."</td>";
											}
										else {
											echo "<td style='width: 427px; min-width: 427px; max-width: 427px; padding-top: 2px; padding-bottom: 2px;'>".$proofs[$i]["page"]."</td>";
											}
											
										if( $proofs[$i]["fin"] == "1" ) {
											echo "<td style='width: 83px; min-width: 83px; max-width: 83px;'>Final</td>";
											}
										else {
											echo "<td style='width: 83px; min-width: 83px; max-width: 83px;''>Basic</td>";
											}
										echo "<td style='width: 78px; min-width: 78px; max-width: 78px;'>".$proofs[$i]["proofCounter"]."</td>";
									echo "</tr>";
									}
								
								?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				
				<div class='imagebox' style='float: left;'>
					<div class='pubDetail' style='color: #292377; text-align: left; margin-top: 34px; margin-left: 37px;'>
						<div>
							<b style='font-size: 15px;'>Other Tasks</b>
							<i id="<?= $_GET["id"] ?>_button" onclick="addOther()" class="fas fa-plus-square" style="font-size: 16px;"></i>
							
							<div id="addNewTask" style="display: none; margin-left: 2px;">
								<input type="text" name="taskname" id="taskname" placeholder="Task name">
								<input type="text" name="tasktime" id="tasktime" placeholder="Time" style="width: 50px;">
								<i onclick="saveTask( '<?= $_GET["id"] ?>' )" class="fas fa-check-square" style="color: #059E00; font-size: 20px; cursor: pointer; float: right; margin-left: 2px;"></i>
							</div>
						</div>

						<div style="padding-top: 5px;">
							<table class='image_map_table' cellspacing="0" cellpadding="0">
								<thead>
									<tr>
										<td style='width: 476px;'>Task name</td>
										<td style='width: 100%;'>Time</td>
									</tr>
								</thead>
								
								<tbody id="otherTaskList" style='height: 200px; overflow-y:scroll;'></tbody>
							</table>
						</div>
					</div>
				</div>				
			</div>
		</td>
	</tr>
</table>
</div>

<script>
function downloadCSV() {
	$.ajax	({
		url:"engine/ajax.php?op=downloadCSV&pubid=<?= $pub[0][0] ?>",
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			var link = 'get_file.php?type=csv&file='+data[0]+'&name='+data[1];
			
			if ($idown) { $idown.attr('src',link); }
			else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }			
			}
		});	
	}

function getOtherTaskList( pid ) {
	$.ajax ({
		url:"engine/issueManagementAjax.php?op=gettask&id="+pid,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#otherTaskList").html( data );
			}
		});	
	}
getOtherTaskList( "<?= $_GET["id"] ?>" );

function saveTask( pid ) {
	var data = {
		"name" : $("#taskname").val(),
		"time" : $("#tasktime").val()
		};
	
	if( data.name != "" && data.time != "" ) {	
		$.ajax ({
			url:"engine/issueManagementAjax.php?op=addtask&id="+pid,
			type: "POST",
			data: { data : data },
			dataType: 'json',
			success:function( data ) {
				$("#addNewTask").css("display", "none");
				$("#taskname, #tasktime").val("");
				getOtherTaskList( "<?= $_GET["id"] ?>" );
				}
			});
		}
	}

function addOther() {
	$("#addNewTask").css("display", "inline-block");
	$("#taskname, #tasktime").val("");
	}

		
function issueManagement( op, id, divid ) {
	if( op == "deleteIssue" ) {
		var issue = divid.replace('Float','');
		//issue = issue.replace('_',' ');
		var text = "Are you sure you want to remove the "+issue+" Issue?";
		
		if( confirm( text ) ) {
			var iHTML = $("#"+id+"_status").html();
			$("#"+id+"_status").html( iHTML+'<div style="float: right; margin-left: 15px; margin-top: 5px; height:1px;"><div id="floatingBarsG"><div class="blockG" id="rotateG_01"></div><div class="blockG" id="rotateG_02"></div><div class="blockG" id="rotateG_03"></div><div class="blockG" id="rotateG_04"></div><div class="blockG" id="rotateG_05"></div><div class="blockG" id="rotateG_06"></div><div class="blockG" id="rotateG_07"></div><div class="blockG" id="rotateG_08"></div></div></div>' );
			$.ajax	({
				url:"engine/issueManagementAjax.php",
				type: "GET",
				data: 'op='+op+'&id='+id,
				dataType: 'json',
				success:function( data ) {
					$("#"+divid).hide(200,function(){
						$("#"+divid).remove();
						});
					}
				});
			}
		}
	
	else if( op == "approveIssue" ) {
		var issue = divid.replace('Float','');
		var text = "Are you sure you want to approve the "+issue+" Issue?";
		
		if( confirm( text ) ) {
			$.ajax	({
				url:"engine/issueManagementAjax.php",
				type: "GET",
				data: 'op='+op+'&id='+id,
				dataType: 'json',
				success:function( data ) {	
					$("#"+divid).hide(200,function(){
						$("#"+divid).remove();
						});
					}
				});			
			}
		}
	
	else {
		$.ajax	({
			url:"engine/issueManagementAjax.php",
			type: "GET",
			data: 'op='+op+'&id='+id,
			dataType: 'json',
			success:function( data ) {	
				$("#"+divid).hide(200,function(){
					$("#"+divid).remove();
					});
				}
			});
		}
	}	
	
function addDeliver( pubid ) {
	$.ajax ({
		url:"engine/issueManagementAjax.php?op=addDeliver&pubid="+pubid,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			loadDeliver();
			$("#"+pubid+"_button").animate({
            	color: "#059E00"
				}, 150, function(){
					$("#"+pubid+"_button").animate({
						color: "#292377"
						}, 150 ); 
					});
			}
		});		
	}

function loadDeliver() {
	$.ajax ({
		url:"engine/issueManagementAjax.php?op=deliverLoad&id=<?= $_GET["id"] ?>",
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#deliverBody").html( data );
			}
		});	
	}
loadDeliver();

function loadIMG( string ) {
	console.log( "<?= $_GET["id"] ?>" );
	
	$.ajax ({
		url:"engine/issueManagementAjax.php?op=searcheIMG&id=<?= $_GET["id"] ?>",
		type: "POST",
		data: { string : string },
		dataType: 'json',
		success:function( data ) {
			console.log( data );
			$("#imgBody").html( data );
			}
		});	
	}
loadIMG("");

function saveRetus( imgID ) {
	var val = $("#"+imgID+"_retus").val();
	var mask = $("#"+imgID+"_maszk:checked").val();
	if( mask == undefined ) {
		mask = 0;
		}
	
	$.ajax ({
		url:"engine/issueManagementAjax.php?op=retusSave&imgid="+imgID+"&value="+val+"&mask="+mask,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#"+imgID+"_button").animate({
            	color: "#00449E"
				}, 150, function(){
					$("#"+imgID+"_button").animate({
						color: "#059E00"
						}, 150 ); 
					});
			}
		});	
	}

jQuery(function($){
	$.datepicker.regional['hu'] = {
    	dateFormat: 'yy-mm-dd', firstDay: 1,
        isRTL: false};
        $.datepicker.setDefaults($.datepicker.regional['hu']);
	});

var type2 = "-1";
var type = "<?= $_GET['opt'] ?>";
var txt = '';
var user = '<?= $user[0][0] ?>';
var code = '<?= $_GET["code"] ?>';
var p_id = '<?= $_GET["id"] ?>';

function Redirect( val ) {
	var temp = val.split("_");
	location.href='?page=timeline&id='+temp[0]+'&code='+temp[1];
	}

$( document ).ready(function() {
	ad_height = parseInt( $( window ).height() )-(parseInt( $(".content_title").outerHeight())+parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-95;
	$('.ad_menu_content').height( ad_height );
	});

function fit_wrapper2() {
	var ad_height = parseInt( $( window ).height() )-(parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() ) )-82;
	$('#ad_table_wrapper2').height( ad_height );
	
	ad_height = parseInt( $( window ).height() )-parseInt( $("#header").outerHeight());
	$('#liveLog').height( ad_height-parseInt( $("#logSettings").outerHeight() )-parseInt( $("#set").outerHeight() )-parseInt( $("#backButton").outerHeight() )-15 );

	if( !$.browser.device ) {
		ad_height = parseInt( $( window ).height() )-(parseInt( $("#header").outerHeight()));
		$('#pack_list').height( ad_height );	
		}
	}
	
$( document ).ready(function() {
	fit_wrapper2();
	});

$(window).resize(function(){
	fit_wrapper2();
	});

var time = parseInt( '<?= $user[0][12] ?>' );
var refresh = true;
var deny = new Array();
var allowed = new Array();
<?
	foreach( $logSettings as $name => $value ) {
		if( $value == 1 ) {
			echo "allowed.push('".$name."');";
			}
		}
	
	if( $user[0][4] == 6 ) {
		echo "allowed.push('newArticle');";
		echo "deny.push('backArticle');";
		}
	else {
		echo "allowed.push('backArticle');";
		echo "deny.push('newArticle');";
		}
?>

$(function() {
	$("select[name='fpView']").change(function(){
		fpFilter = $(this).val();
		});
		
	$("input[name='logSettingsOption'], select[name='backward']").click(function(){
		var cbox = $( "input[name='logSettingsOption']" ).serializeArray();
		var backward = $( "select[name='backward']" ).val();

		refresh = false;
		$.ajax	({
			url:"engine/logSettings.php?op=saveSettings",
			type: "POST",
			data: {cbox : cbox, time : backward},
			dataType: 'json',
			success:function( data ) {
				refresh = true;
				}
			});
		});


	$(".showLogSettingsPanel").click(function(){
		var state = $("#logSettingsPanel").css('display');

		if( state == "none" ) {
			$("#logSettingsPanel").show( 250 );
			}
		else {
			$("#logSettingsPanel").hide( 250 );
			}
		});
	});
	
function loadLog( method ) {
	if( refresh || method == 'reload' ) {
		$.ajax	({
			url:"engine/loadLog.php?method="+method,
			data: '',
			dataType: 'json',
			success:function( data ) {
				$("#liveLog").html( data[0] );
				setTimeout(function(){ loadLog('repeat'); }, 200);
				}
			});
		}
	else {
		setTimeout(function(){ loadLog('repeat'); }, 200);
		}
	}
loadLog();

</script>
