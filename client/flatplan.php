<link rel="stylesheet" href="css/jquery-ui.css">
<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
<link href="css/main.css" rel="stylesheet" type="text/css" />
<link href="css/load_bar.css" rel="stylesheet" type="text/css" />
<script type="text/javascript">
jQuery(document).ready(function(){
    $( document ).tooltip({
        tooltipClass: "floatMenu"
        });

    $("[title]").each(function(){
    	$(this).tooltip({ tooltipClass: "floatMenu", content: $(this).attr("title")} );
		});
	});
</script>   
<?php 

$_SESSION['intra_ajaxProcess'] = 0;
if( (!isset( $_GET['id'] ) or $_GET['id'] == '' ) && $user[0][11] != "" ) {
	$temp = explode( "_", $user[0][11] );
	$tempMag = sql_get( 'magazines', 'code="'.$temp[0].'"', '*' );
	$tempPub = sql_get( 'publications', 'magazine_id="'.$tempMag[0][0].'" AND code="'.$temp[1].'"', '*' );
	if( $tempPub != "" ) {
		$_GET['id'] = $tempPub[0][0];
		$_GET['code'] = $tempPub[0][10];
		}
	else {
		$tempPub = sql_get( 'publications', 'magazine_id="'.$tempMag[0][0].'"', '*' );
		if( $tempPub != "" ) {
			$_GET['id'] = $tempPub[0][0];
			$_GET['code'] = $tempPub[0][10];
			}
		else {
			$tempPub = sql_get( 'publications', 'publisher_id="'.$user[0][4].'"', '*' );
			$_GET['id'] = $tempPub[0][0];
			$_GET['code'] = $tempPub[0][10];
			}
		}
	}

$sql = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', 'publisher');
$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*');
if( isset( $_GET['code'] ) ) {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" AND code="'.$_GET['code'].'"', '*' );
	}
else {
	$pub = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
	$_GET['code'] = $pub[0][10];
	}

$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
if( !empty( $pub[0][0] ) ) {
	if( !checkOwner( array( 'publications', 'id', $pub[0][0] ), $user ) ) {
		header('Location: ' . $_SERVER['HTTP_REFERER']);
		}
	}
else {
	$pubs = getShortPubs( $user[0] );
	$pub = sql_get( 'publications', 'id="'.$pubs[0][0].'"', '*' );
	$_GET['code'] = $pub[0][10];
	$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
	}
 	
$time = strtotime( $pub[0][11] );
setlocale(LC_ALL,'hungarian');
$time = iconv('ISO-8859-2', 'UTF-8', strftime( "%Y. %B %e. %A, %H:%M" , $time ) ); 	

?>

<?php if( isMobile() ) { ?>
<div id="headerExtraLine">
	<div style='display: inline-block; margin-left: -1px; font-size: 14px; text-align:left; margin-top: -5px; position: absolute; left: 0px;'>
		<?
			if( !isset( $_GET['opt'] ) ) {
				switch( $user[0][19] ) {
					case "FIN":
						$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
						$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
		
						if( $check[0][0] != "" ) $_GET['opt'] = $user[0][19];
						else $_GET['opt'] = "";
						break;
	
					case "PRE":
						$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
						$check = sql_get( 'pageinfo', 'type="PRE" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
						
						if( $check[0][0] != "" ) $_GET['opt'] = $user[0][19];
						else $_GET['opt'] = "";
						break;
	
					default:
						$_GET['opt'] = "";
						break;
					}
				}
			else {
				sql_update( 'accounts', 'lastOpt="'.$_GET['opt'].'"', 'id="'.$user[0][0].'"' );
				}
			$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
			$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
			$xpath = $xml->xpath('/Publications');
			foreach($xpath as $temp) {
				for( $x = 0; $x < count( $temp->Item ); $x++ ) {
					if( $temp->Item[$x]->Code == $magazine[0][3] ) {
						break;
						}
					}
				}
			$process = (string) $xml->Item[$x]->Workflow;
			if( $process != "Softproof" ) {
				$flatplans = array(
					"PRE" => 'pre',
					"" => 'basic',
					"FIN" => 'final'
					);
				}
			//$_GET['opt'] = $_GET['alter'];

			foreach( $flatplans as $key => $val ) {
				$valid = true;
				if( $key == "FIN" ) {
					$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="1" LIMIT 1', '*' );
					
					if( $check[0][0] == "" ) $valid = false;
					}				
				else if( $key != "" ) {									
					$check = sql_get( 'pageinfo', 'type="'.$key.'" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
	
					if( $check[0][0] == "" ) $valid = false;
					}

				if( $valid ) {
					echo "<div id='alter_".$key."' class='mobileFPchoser ";
					if( $_GET['opt'] == $key )
						echo "alterSelected";

					echo "' onclick='window.location.href=\"?page=flatplan".( $_GET["manage"] != "" ? "&manage=".$_GET["manage"] : "" )."&opt=".$key."\"'>".$val."</div>";
					}	
				}
				
			echo "<div style='clear:both;'></div>";
		?>
	</div>
		
	<div style="display: inline-block;">
	<?php
		$temp = sql_aget( 'publications', 'id="'.$_GET['id'].'" AND code="'.$_GET['code'].'"', '*' );
		$mag = sql_aget( "magazines", "id='".$temp[0]["magazine_id"]."'", "*" );
		echo $mag[0]["code"]." ".$temp[0]["code"];
	?>
	</div>
</div>

<? }  else { ?>
<div id="headerExtraLine"></div>
<? } ?>

<div style='display: none;'>
	<div id='downloader' style='width: 125px; height: 16px; position: relative; float: right; margin-top: -2px; padding-right: 23px;'>
		<div id='loading_bar' style='display: none; position: absolute; top: 19px;'>
			<div id="squaresWaveG">
				<div id="squaresWaveG_1" class="squaresWaveG"></div>
				<div id="squaresWaveG_2" class="squaresWaveG"></div>
				<div id="squaresWaveG_3" class="squaresWaveG"></div>
				<div id="squaresWaveG_4" class="squaresWaveG"></div>
				<div id="squaresWaveG_5" class="squaresWaveG"></div>
				<div id="squaresWaveG_6" class="squaresWaveG"></div>
				<div id="squaresWaveG_7" class="squaresWaveG"></div>
				<div id="squaresWaveG_8" class="squaresWaveG"></div>
			</div>
		</div>
	</div>
</div>

<div id='content_box' style='overflow: hidden !important;'>
	<table id="tabla" cellspacing="0" cellpadding="0" style="width: 100%; table-layout:fixed;"><tbody>
		<tr>
			<td class="fp_left" valign="top" style="background: rgb( 227, 227, 227); width: 229px;">
				<div class='top_menu' style="background: rgb(227, 227, 227);">
					<div style="padding: 10px; text-align: left;">
						<?php if( $user[0][4] != 0 ) { ?>
							<div style='float: left;'>
								<select style="margin-left: -1px;" onchange="Redirect( $(this).val() )">
								<?
									$pubs = getShortPubs( $user[0] );
					
									for( $i = 0; $i < count( $pubs ); $i++ ) {
										$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code, type' );
										
										echo "<option value='".$pubs[$i][0]."_".$firstPage[0][0]."_".$firstPage[0][1]."' ";
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
								?>
								<img title="<?= $magf[0][0] ?><br/>Issue: <?= $pubf[0][10] ?><br/>Deadline: <?= $pubf[0][11] ?>" src="images/icons/info.png" height="20px">
							</div>
						<?php } else {
							$pubf = sql_get( 'publications', 'id="'.$_GET['id'].'" ORDER BY `code` ASC', '*' );
							$magf = sql_get( 'magazines', 'id="'.$pubf[0][2].'"', 'name, id, code' );
							$pubf[0][10] = "";
							} ?>							
						
						<div id="handoutmenubox"></div>
						<div id="handoutLoading" style="display: none; float: left; margin-left: 0px; margin-top: 5px; display: none;">
							<div id="floatingBarsG" style="float: right; left: 7px; top: -2px;"><div class="blockG" id="rotateG_01"></div><div class="blockG" id="rotateG_02"></div><div class="blockG" id="rotateG_03"></div><div class="blockG" id="rotateG_04"></div><div class="blockG" id="rotateG_05"></div><div class="blockG" id="rotateG_06"></div><div class="blockG" id="rotateG_07"></div><div class="blockG" id="rotateG_08"></div></div>							
							
						</div>					
						<div style='clear:both;'></div>
						<?php
						$xml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
						$xpath = $xml->xpath('/Publications');
						
						foreach($xpath as $temp) {
							for( $i = 0; $i < count( $temp->Item ); $i++ ) {
								if( $temp->Item[$i]->Code == $magf[0][2] )
									break;
								}
							}
						
						$pn = (string) $xml->Item[$i]->PageNumbering;
						
						if( $pn == "American" ) {
							$parts = sql_aget( "parts", "pub_id='".$pubf[0][0]."'", "*" );
							
							echo "<select id='part' name='part'>";
							for( $i = 0; $i < count( $parts ); $i++ ) {
								$l = array_search( $parts[$i]["name"],PARTS );
								echo "<option ".( $_SESSION["fp_part"] == $parts[$i]["name"] ? "selected" : "" )." value='".$parts[$i]["name"]."'>".$lang["parts"][$l]."</option>";
								}
							echo "</select>";
							}
						else {
							echo "<select id='part' name='part' style='display: none;'>";
								echo "<option selected value=''></option>";
							echo "</select>";
							}
						?>
						<div style='margin-left: -1px; margin-top: 7px; margin-bottom: 5px; font-size: 14px; text-align:left;'>
							<?
								if( !isset( $_GET['opt'] ) ) {
									switch( $user[0][19] ) {										
										case "FIN":
											$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
											$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
							
											if( $check[0][0] != "" ) $_GET['opt'] = $user[0][19];
											else $_GET['opt'] = "";
											break;
						
										case "PRE":
											
											$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
											$check = sql_get( 'pageinfo', 'type="PRE" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
											
											if( $check[0][0] != "" ) $_GET['opt'] = $user[0][19];
											else $_GET['opt'] = "";
											break;
						
										default:
											$_GET['opt'] = "";
											break;
										}
									}
								else {
									sql_update( 'accounts', 'lastOpt="'.$_GET['opt'].'"', 'id="'.$user[0][0].'"' );
									}
								
								$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
								$xml = simplexml_load_file( 'xml/'.PMD.'.xml' );
								$xpath = $xml->xpath('/Publications');
								foreach($xpath as $temp) {
									for( $x = 0; $x < count( $temp->Item ); $x++ ) {
										if( $temp->Item[$x]->Code == $magazine[0][3] ) {
											break;
											}
										}
									}
								$process = (string) $xml->Item[$x]->Workflow;
								if( $process != "Softproof" ) {
									$flatplans = array(
										"PRE" => 'pre',
										"" => 'basic',
										"FIN" => 'final',
										);
									}
								//$_GET['opt'] = $_GET['alter'];
								foreach( $flatplans as $key => $val ) {
									$valid = true;
									if( $key == "FIN" ) {
										$check = sql_get( 'pageinfo', '( `status`="1" OR `status`="2" ) AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" AND fin="1" LIMIT 1', '*' );
										
										if( $check[0][0] == "" ) $valid = false;
										}				
									else if( $key != "" ) {									
										$check = sql_get( 'pageinfo', 'type="'.$key.'" AND code="'.$magazine[0][3].'" AND issue="'.$pub[0][10].'" LIMIT 1', '*' );
						
										if( $check[0][0] == "" ) $valid = false;
										}
					
									if( $valid ) {
										echo "<div id='alter_".$key."' class='kindButton ".$val." ";
										if( $_GET['opt'] == $key )
											echo "alterSelected";

										echo "' onclick='window.location.href=\"?page=flatplan".( $_GET["manage"] != "" ? "&manage=".$_GET["manage"] : "" )."&opt=".$key."\"'>".$val."</div>";
										}	
									}
									
								echo "<div style='clear:both;'></div>";
							?>
						</div>
						<div style='margin-top: 10px;'>
							<select style="margin-left: -1px;" name='fpView'>
								<?
								$options = array(
									"all" => $lang["flatplan"]["all"],
									"newComments" => $lang["flatplan"]["newComments"],
									"newUploads" => $lang["flatplan"]["newUploads"],
									"waiting" => $lang["flatplan"]["waiting"],
									"approved" => $lang["flatplan"]["approved"]
									);
				
								foreach( $options as $key => $val ) {
									echo "<option value='".$key."' ";
									if( $user[0][13] == $key )
										echo "selected";
									echo ">".$val."</option>";
									}
								?>
							</select>
						</div>
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
							$logSettings = $logSettings[0];
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
									if( $i == 3 ) {
										if( $c < count( $logSettings ) && $c > 1 && $m < 2 ) {
											echo "<tr><td colspan='3' align='left'>";
												echo "<div style='position: relative; width: 100%; margin-top: 5px; margin-bottom: 5px;'>";
													echo "<div style='margin-top: 6px; margin-bottom: 2px; border-top: 1px solid #636363;'></div>";
													echo "<div class='panelSubTitle' style='margin-top: 5px;'>".$title[$m]."</div>";
												echo "</div>";
											echo "</td></tr>";
											$m++;
											}
										$i = 0;
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
											}
										$i = 0;
										}		
									if( $i == 1 ) {
										if( $c < count( $logSettings ) && $c > 1 && $m == 2 ) {
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
			<div id="liveLog" style="background: rgb(227, 227, 227); //position: absolute; display: block; overflow-x: hidden; overflow-y: auto; width: 229px;"></div>
			
<!--
			<?php if( $process == "Full" ) { ?>
			<div id="anyagfeltoltes" style="height: 62px;">
				<form class="box" method="post" action="" enctype="multipart/form-data" style="height: 100%;">
				  <div class="box__title" style="padding-top: 13px; font-size: 13px;">Upload Package</div>
				  <div class="box__input" style="padding-top: 5px; font-size: 13px;">
				    <input class="box__file" type="file" name="file[]" id="file" data-multiple-caption="{count} files selected" multiple />
				    <label for="file"><span style="font-family: myriad_bold;">Choose a file</span><span class="box__dragndrop" style="font-family: myriad_thin;"> or drag it here</span>.</label>
				    <button class="box__button" type="submit">Upload</button>
				  </div>
				  <div class="box__uploading" style="padding-top: 5px; font-size: 13px; display: none;">Uploading (<span id="anyagpercent"></span>)&hellip;</div>
				  <div class="box__success" style="padding-top: 5px; font-size: 13px; display: none;">Done!</div>
				  <div class="box__error" style="padding-top: 5px; font-size: 13px; display: none;">Error! <span></span>.</div>
				</form>					
			</div>
			<? } ?>
-->
			</td>
			<td id="fp_content" align="right" valign="top" style="margin-right: 10px; overflow-x: hidden; overflow-y: auto; width: 100%; display: block;">
				<? if( $_GET["manage"] == "1" && $_GET["opt"] != "PRE" ) { ?>
					<div id='manageFPbox'>
						<div class='manageBlock'>
							<select name='method' id='method' onchange='rebuildManageBox( $(this).val() )'>
								<option value='insert'><?= $lang["flatplan"]["insert"] ?></option>
								<option value='remove'><?= $lang["flatplan"]["remove"] ?></option>
								<option value='removeLastContent'><?= $lang["flatplan"]["removeLastContent"] ?></option>
								<option value='removeBlockContent'><?= $lang["flatplan"]["removeBlockContent"] ?></option>
							</select>
						</div>
						<div class='manageBlock'>
							<input onkeypress="return isNumberKey(event)" type='text' name='slotnumber' id='slotnumber' maxlength="3">
						</div>
						<div id='text1' class='manageBlock'><?= $lang["flatplan"]["slots"] ?></div>
						<div class='manageBlock'>
							<select name='orient' id='orient'>
								<option value='after'><?= $lang["flatplan"]["after"] ?></option>
								<option value='before'><?= $lang["flatplan"]["before"] ?></option>
							</select>
						</div>
						<div id='text2' class='manageBlock'><?= $lang["flatplan"]["slot"] ?></div>
						<div class='manageBlock'>
							<input onkeypress="return isNumberKey(event)" type='text' name='target' id='target' maxlength="3">
						</div>
						<div class='manageBlock' style='margin-left: 20px;'>
							<input type='checkbox' checked name='movecomment' id='movecomment'>
						</div>
						<div id="optiontext" class='manageBlock'><?= $lang["flatplan"]["movecomments"] ?></div>
						<div class='manageBlock' style='margin-left: 20px;'>
							<div onclick='submitManage()' class='button blue'><?= $lang["flatplan"]["apply"] ?></div>
						</div>
						
						<div class='manageBlock' style='float: right;'>
							<div onclick="window.location='?page=flatplan'" class='button green'><?= $lang["flatplan"]["finish"] ?></div>
						</div>
					</div>
				<?	}	?>
				<div id='fp_wrapper' style='position:relative;'>
					<div id="fp_holder" style="position: absolute; overflow: auto; text-align: left;"></div>
					<div id="fp_holder2" style="position: absolute; overflow: auto; text-align: left;"></div>
				</div>
			</td>
		</tr>
	</tbody></table>
</div>
<div id='dummyLog' style='display: none;'></div>

<?

$check = sql_get( "pageinfo", "(type='ad' OR type='magazine') AND code='".$magazine[0][3]."' AND issue='".$pub[0][10]."' AND fin='1'", "id" );
$allowedOpt = ( count( $check) > 0 ? "FIN" : "" );
/*if( $magazine[0][3] == "BAV" ) {
	$allowedOpt = "";
	}*/

?>

<ul id='customMenu' class='custom-menu floatMenu'>
  <li data-action="second"><?= $lang["flatplan"]["pdf"] ?></li>
  <li data-action="first"><?= $lang["flatplan"]["pdfmerged"] ?></li>
  <li data-action="third"><?= $lang["flatplan"]["jpg"] ?></li>
  <? if( $rights["ad-hoc_proof"] ) { ?>
  	<hr style="padding: 0;">
  	<li data-action="proof"><?= $lang["flatplan"]["proof"] ?></li>
  <? } ?>
  <? if( $user[0][0] == "1" && $_GET["opt"] == "FIN" && $rights["sendHotlink"] ) { ?>
  	<hr style="padding: 0;">
  	<li data-action="hotlink"><?= $lang["flatplan"]["sendHotlink"] ?></li>
  <? } ?>
  <? if( ( ( $rights["acceptPage"] or $rights["cancelApprove"] ) && $_GET['opt'] == $allowedOpt ) && ( $pub[0][12] == "created" or $pub[0][12] == "active" or $pub[0][12] == "current" ) ) { ?>
  	<hr style="padding: 0;">
  	 <? if( $rights["acceptPage"] ) { ?>
  	 	 <li data-action="accept"><?= $lang["flatplan"]["accept"] ?></li>
  	 	 <li data-action="reject"><?= $lang["flatplan"]["reject"] ?></li>
  	 <? } ?>
  	 <? if( $rights["cancelApprove"] ) { ?>
  	 	<li data-action="cancel"><?= $lang["flatplan"]["cancel"] ?></li>
  	 <? } ?>
  <? } ?>
</ul>

<ul id='handoutBox' class='handout-menu floatMenu' style="position: fixed;"></ul>

<script>
var process = "<?= $process ?>";

function viewhandout( file ) {
	$('#handoutBox').hide(100);
	window.open("book.php?file="+file+"&id=<?= $pub[0][0] ?>");
	}

if( process == "Full" ) {
	// Cache the last-rendered HTML for each and only touch the DOM when the
	// server's response actually differs from what's already there - a
	// click landing on a menu item at the exact moment its DOM node gets
	// torn down and rebuilt underneath it silently fails (browsers don't
	// fire "click" if the target is removed between mousedown and mouseup),
	// so leaving unchanged elements alone avoids that entirely.
	var lastHandoutIconHTML = null;
	var lastHandoutMenuHTML = null;
	function loadhandoutmenu() {
		$.ajax	({
			url:"engine/flatplan_ajax.php?op=loadhandoutmenu&id=<?= $pub[0][0] ?>&opt=<?= $_GET['opt'] ?>",
			type: "GET",
			dataType: 'json',
			success:function( data ) {
				if( data[0] !== lastHandoutIconHTML ) {
					$("#handoutmenubox").html( data[0] );
					lastHandoutIconHTML = data[0];
					}
				if( data[1] !== lastHandoutMenuHTML ) {
					$("#handoutBox").html( data[1] );
					lastHandoutMenuHTML = data[1];
					}
				if( data[2] ) {
					$("#handoutLoading").show( 0 );
					}
				else {
					$("#handoutLoading").hide( 0 );
					}

				// data[3] (keepPolling): only reschedule while something can
				// still actually change on its own - either the rest of the
				// issue's pages are still being finished elsewhere, or this
				// specific handout is still waiting on Switch and the
				// handout-handler cron job (which only rechecks the
				// filesystem once a minute - see
				// client/cron/handout-handler.php - so polling faster than
				// that can ever resolve buys nothing). Once settled (ready,
				// or nothing was ever requested), stop entirely instead of
				// hitting the server every second for as long as this page
				// happens to stay open - generateHandout() below restarts
				// this the moment a new handout is actually requested.
				if( data[3] ) {
					setTimeout(function(){ loadhandoutmenu(); }, 5000);
					}
				}
			});
		}
	loadhandoutmenu();

	function downloadHandout( id ) {
		$('#handoutBox').hide(100);
		var link = 'get_file.php?type=handout&id='+id;

		if ($idown) { $idown.attr('src',link); }
		else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
		}

	function generateHandout() {
		$('#handoutBox').hide(100);
		$.ajax	({
			url:"engine/flatplan_ajax.php?op=sendHandout&id=<?= $pub[0][0] ?>",
			type: "GET",
			dataType: 'json',
			success:function( data ) {
				// The polling loop may have already stopped (settled state,
				// nothing left to wait for) by the time this new handout was
				// requested - kick it off again to pick up the now-pending
				// state and resume watching for it to arrive.
				loadhandoutmenu();
				}
			});
		}

	function showHandoutMenu() {
		var temp = $("#book-icon").offset();
		$("#handoutBox").css( "top", ( temp.top + 5 )+"px" );
		$("#handoutBox").css( "left", ( temp.left + 10 )+"px" );
		
		$("#handoutBox").show(100);
		}
	}
		
function fpfiledownload( id ) {
	window.open( "filedownload.php?type=fp&id="+id );
	}
	
function loadUploadedFiles( plannerID ) {
	$.ajax	({
		url:"engine/fileupload.php?op=loaduploadedfiles&plannerid="+plannerID,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#fileupload_uploaded").html( data );
			}
		});	
	}
	
function currentFile() {
	window.parent.frames[0].fileVal = $("#afile").val().split('\\').pop();
	
	$("#currentFileName").html( window.parent.frames[0].fileVal );
	if( window.parent.frames[0].fileVal == "" ) {
		$("#targetfile").css("display", "none");
		}
	else {
		$("#targetfile").css("display", "inline-block");
		}
	}

if( window.parent.frames[0].activeFUpload ) {
	$("#select-file").hide(0);
	
	$("#selected-file").html( "Uploading file: "+window.parent.frames[0].fileVal );
	$("#selected-file").show(0);
	
	$(".fp-up-box").css("visibility", "visible");
	$(".fup-bar").css( "width", window.parent.frames[0].FUPercent+"%");
    $(".fup-percent").html( window.parent.frames[0].FUPercent+"%");
	showAssets( window.parent.frames[0].currentPlannerPubID, window.parent.frames[0].currentPlannerID );
	}
	
function loadArticles() {
	$.ajax	({
		url:"engine/flatplan_ajax.php?op=loadarticles&id=<?= $_GET["id"] ?>",
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#articleList").html( data );
			}
		});
	}
	
var pubLength = parseInt( "<?= $pub[0][6] ?>" );
function rebuildManageBox( value ) {
	switch( value ) {
		case 'insert':
		case 'remove':
			$("#text1").html( "<?= $lang['flatplan']['slots'] ?>" );
			$("#orient").show( 0 );
			$("#text2").html( "<?= $lang['flatplan']['slot'] ?>" );
			$('#movecomment').prop('checked', true);
			$('#optiontext').html( "<?= $lang['flatplan']['movecomments'] ?>" );
			break;
		case 'removeLastContent':
			$("#text1").html( "<?= $lang['flatplan']['slotmerged'] ?>" );
			$("#orient").hide( 0 );
			$("#text2").html( "" );
			$('#movecomment').prop('checked', false);
			$('#optiontext').html( "<?= $lang['flatplan']['deletecomments'] ?>" );
			break;
		case 'removeBlockContent':
			$("#text1").html( "<?= $lang['flatplan']['slotmerged'] ?>" );
			$("#orient").hide( 0 );
			$("#text2").html( "" );
			$('#movecomment').prop('checked', true);
			$('#optiontext').html( "<?= $lang['flatplan']['deletecomments'] ?>" );
			break;
		}
	}

function submitManage() {
	var options = {
		"method": $("#method").val(),
		"slotnumber": parseInt( $("#slotnumber").val() ),
		"orient": $("#orient").val(),
		"target": parseInt( $("#target").val() ),
		"movecomment": $("#movecomment").is(':checked')
		}
	var allowed = true;
	switch( options.method ) {
		case 'insert':
			if( options.orient == "after" ) {
				if( options.target == 0 ) allowed = false;
				if( options.target > pubLength ) allowed = false;
				}
			if( options.orient == "before" ) {
				if( options.target < 1 ) allowed = false;
				if( options.target > pubLength ) allowed = false;
				}
			break;
			
		case 'remove':
			if( options.orient == "after" ) {
				if( options.target == 0 ) allowed = false;
				if( options.target > ( pubLength - options.slotnumber ) ) allowed = false;
				}
			if( options.orient == "before" ) {
				if( options.target < options.slotnumber ) allowed = false;
				if( options.target > pubLength ) allowed = false;
				}
			
			if( allowed ) {
				var r = confirm("<?= $lang['flatplan']['removeSlot'] ?>");
				if( r == false ) allowed = false;
				}
			break;		

		case 'removeLastContent':
			if( options.target == 0 ) allowed = false;
			if( options.target > ( pubLength - options.slotnumber +1 ) ) allowed = false;

			if( allowed ) {
				var r = confirm("<?= $lang['flatplan']['restoreVersion'] ?>");
				if( r == false ) allowed = false;
				}
			break;

		case 'removeBlockContent':
			if( options.target == 0 ) allowed = false;
			if( options.target > ( pubLength - options.slotnumber +1 ) ) allowed = false;

			if( allowed ) {
				var r = confirm("<?= $lang['flatplan']['eraseSlot'] ?>");
				if( r == false ) allowed = false;
				}
			break;
		}
	
	console.log( options );
	
	if( $.isNumeric( options.slotnumber ) && $.isNumeric( options.target ) && options.slotnumber > 0 && allowed ) {
		$.ajax	({
			url:"engine/manageFP.php?id=<?= $_GET['id'] ?>",
			type: "POST",
			data: { options : options},
			dataType: 'json',
			success:function( data ) {
				console.log( data[1] );
				cachebreak = 1;
				}
			});
		}
	}

$.fn.preload = function() {
    this.each(function(){
        $('<img/>')[0].src = this;
    });
}

$(['images/icons/arrow_left_hover.png','images/icons/arrow_right_hover.png']).preload();

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

var txt = '';
var maxPage = '<?= $pub[0][6] ?>';

var maxWidth = $(window).width()-80-160;
var maxWidth2 = $(window).width()-80-160;
var row = parseInt( maxWidth / 200 );
var row2 = parseInt( maxWidth2 / 200 );
var divWidth = (row*200);
var winWidth = $(window).width()-20;
var tdWidth = row2*200;

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
$(window).load(function(){
	fit_box();
	});

$(window).resize(function(){
	winWidth = $(window).width();
	maxWidth2 = $(window).width()-80-160;
	row2 = parseInt( maxWidth2 / 200 );
	 
	ad_height = parseInt( $( "#mainPage" ).height() )-(parseInt( $(".content_title").outerHeight())+parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-295;
	fit_box();	
	$('.ad_menu_content').height( ad_height );	
	});

var fpFilter = $("select[name='fpView']").val();

var placeto = "fp_holder";
var currentplace = "fp_holder2";

var cachebreak = 0;
var cache = "<?= time() ?>";
var firstLoad = "true";
var lastPageClick = "0";

$("#part").change(function(){
	loadPages();
	});

function loadPages() {
	if( txt == "" ) {
		$('#fp_holder').html( "<div style='width: 100%; text-align: center;'><br>...<?= $lang['flatplan']['loading'] ?>...<br><img src='images/ajax_loader.gif'></div>" );
		}

	$.ajax	({
		url:"engine/flatplan_ajax.php",
		data: 'op=loadPagePair&filter='+fpFilter+'&opt=<?= $_GET["opt"] ?>&maxWidth='+maxWidth2+'&cache='+cache+'&id=<?= $_GET["id"] ?>&intra_user=<?= $_SESSION['intra_user'] ?>&part='+$("#part").val()+'&pageNumbering=<?= $pn ?>',
		dataType: 'json',
		success:function( data ) {
			//processChecker = false;
			if( txt != data[0] ) {			
				switch( placeto ) {
					case 'fp_holder':	
						$('#fp_holder').html( data[0] );
						$('#fp_holder').show(0);
						$('#fp_holder2').hide(0);
						placeto = 'fp_holder2';
						currentplace = "fp_holder";
						break;

					case 'fp_holder2':	
						$('#fp_holder2').html( data[0] );
						$('#fp_holder2').show(0);
						$('#fp_holder').hide(0);
						placeto = 'fp_holder';
						currentplace = "fp_holder2";
						break;
					}
					
				<?php
				if( $_SESSION["intra_scroll"] != "" && $_SESSION["intra_pubid"] == $_GET["id"] ) {
					echo "$('#'+currentplace ).scrollTop( ".$_SESSION["intra_scroll"]." );";
					}
				?>
				
				txt = data[0];
				$('body').off();

				singleDoubleClick( '.pagenr|.thumb', '<?= $_GET['opt'] ?>' );
				for( var a = 0; a < data[1].length; a++ ) {
					alterHandle( data[1][a] );
					}
				
				$(function(){
					$(".flip_left>img").mouseenter(function(){ $(this).attr("src", "images/icons/arrow_left_hover.png"); });
					$(".flip_left>img").mouseleave(function(){ $(this).attr("src", "images/icons/arrow_left.png"); });
					$(".flip_right>img").mouseenter(function(){ $(this).attr("src", "images/icons/arrow_right_hover.png"); });
					$(".flip_right>img").mouseleave(function(){ $(this).attr("src", "images/icons/arrow_right.png"); });
					});
					
				cachebreak = 0;
				$('#'+currentplace ).scrollTop( "<?= $_SESSION["intra_scroll"] ?>" );
				}
			setTimeout(function(){ loadPages(); }, 500);
			
			}
		});
	}
loadPages();

function articleNames() {
	$( ".articleNameBox" ).remove();

	$("#"+currentplace+" .articleStart").each(function(){
		var id = $(this).attr( "aid" );
		var name = $(this).attr( "aname" );
		
		var start = $(this);
		var start_page = parseInt( $(this).find(".pagenr").find("div").html() );
		
		var end = $( "#"+currentplace+" .articleEnd[aid='"+id+"']" );
		var end_page = parseInt( $(end).find(".pagenr").find("div").html() );

		var boxDB = Math.ceil( ( end_page - start_page ) / 2 );
		var width = boxDB * 175 - 12;
		if( $(this).hasClass("right_page") ) {
			width += 81 + 12;
			}
		
		var aname = id+"_name";
		var arrow = id+"_name_arrow";
		
		jQuery('<div/>', {
			id: aname,
			class: "articleNameBox"
			}).appendTo( "#"+currentplace );

		jQuery('<div/>', {
			id: arrow,
			class: "articleNameBox"
			}).appendTo( "#"+currentplace );
		
		var scrollTop = $("#fp_holder").scrollTop()
			
		var pos = $(start).parent().position();
		var left = pos.left + 10;
		
		if( $(this).hasClass("startArrow") && $(this).hasClass("endArrow") ) {
			if( $(this).hasClass("right_page") ) {
				left += 81;
				}
			
			if( $(end).hasClass("left_page") ) {
				left += 21;
				}
			}
			
		else {
			if( $(this).hasClass("right_page") ) {
				left += 81;
				}
			
			if( $(end).hasClass("left_page") ) {
				left += -40;
				}
			}
		
		var top = pos.top + scrollTop + 8 + 42;
					
		$("#"+aname).html( "<div class='articleName' style='width: "+width+"px;'>"+name+"</div>" );
		$("#"+aname).css("color", $(this).attr( "acolor" ) );
		$("#"+aname).css("left", left+"px");
		$("#"+aname).css("top", top+"px");
		$("#"+aname).css("width", width+"px");

		var pos = $(start).parent().position();
		var left = pos.left + 11;

		if( $(this).hasClass("startArrow") && $(this).hasClass("endArrow") ) {
			if( $(end).hasClass("left_page") ) {
				left += 1;
				width = 78;
				}
				
			if( $(this).hasClass("right_page") ) {
				left += 83;
				width = 78;				
				}
			}
			
		else {
			if( $(this).hasClass("right_page") ) {
				var c = $("div[a-name='"+$(this).attr( "a-name" )+"']").length;
				if( c <= 2 ) {
					left += 124;
					width -= 86;
					}
				else {
					left += 82;
					}
				}
			
			if( $(end).hasClass("left_page") ) {
				left += -40;
				}
			}
		
		var top = pos.top + scrollTop + 5 + 95;
		
		$("#"+arrow).html( "<div class='articleArrow' style='width: "+width+"px;'></div>" );
		$("#"+arrow).css("left", left+"px");
		$("#"+arrow).css("top", top+"px");
		$("#"+arrow).css("width", width+"px");
		
		});

	$("#"+currentplace+" .startArrow").each(function(){
		$(this).html( $(this).html()+"<div class='leftArrowBorder'></div>" );
		});
		
	$("#"+currentplace+" .endArrow").each(function(){
		$(this).html( $(this).html()+"<div class='rightArrowBorder'></div>" );
		});
	}

function Redirect( val ) {
	var temp = val.split("_");
	
	location.href='?page=flatplan<? echo ( $_GET["manage"] != "" ? "&manage=".$_GET["manage"] : "" ); ?>&id='+temp[0];
	}
	
var enableContext = 1;
var contextWidth = $(".custom-menu").width();
$(document).bind("contextmenu", function (event) {
    if( $( "#"+currentplace+" input:checked" ).length > 0 ) {
		event.preventDefault();
		
		if( $("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").length > 1 ) {
			$("li[data-action='first']").show(0);
			}
		else {
			$("li[data-action='first']").hide(0);
			}
		
		$(".custom-menu").fadeOut(100, function(){
			if( enableContext ) {
				var checker = winWidth-contextWidth-5;
				if( event.pageX >= checker ) {
					event.pageX = event.pageX-contextWidth;
					}

				event.pageY = event.pageY-parseFloat( $("#header").height() );
				event.pageX = event.pageX+10;

				if( $( "#"+currentplace+" input:checked" ).length == 0 ) {
					var target = $(event.target).closest(".pageBox");
					if( target.length > 0 ) {
						target = target.children(".pagenr")[0];
						thumbClick( target, 'single' );
						}
					}
			
				$(".custom-menu").css({
					top: event.pageY + "px",
					left: event.pageX + "px"
					});	
				
				$(".custom-menu").show(0);
				var offset = $(".custom-menu").offset();
				var oHeight = event.clientY+$(".custom-menu").outerHeight(true);
				$(".custom-menu").hide(0);

				if( oHeight > $(window).height() ) {
					$(".custom-menu").css("top", (parseInt( $(".custom-menu").css("top") )-$(".custom-menu").outerHeight(true) )+"px");
					} 	
			
				$(".custom-menu").fadeIn(100);			

				}
			});
	   	}
	return false;
	});

$(document).bind("mousedown", function (e) {
    var container = $("#logSettingsPanel");
	if (!container.is(e.target) && container.has(e.target).length === 0) {
		container.hide(250);
		} 
 
    var container = $(".custom-menu");
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.fadeOut(100);
    	} 
    
});

var $idown;
function download(url, type) {
	var link = 'get_file.php?type='+type+'&file='+url;
	if ($idown) { $idown.attr('src',link); }
	else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
	
	setTimeout(function(){
		location.reload();
		}, 500);
	}
var alter = "NOR";
<?PHP
	if( $_GET['opt'] != "" )
		echo "alter = '".strtoupper( $_GET['opt'] )."';";
?>

var pubID = "<?= $pub[0][0] ?>";
function plannerContextMenu( info, menu ) { 
	var cbox = new Array();
	
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).val() );
		}); 
	
	if( window.parent.frames[0].activeFUpload ) {
		pubID = window.parent.frames[0].currentPlannerPubID;
		}
		
	else {
		window.parent.frames[0].currentPlannerPubID = pubID;
		}
	
	$.ajax	({
		url:"engine/menuAjax.php?op=loadmenu&menu="+menu+"&data="+info+"&pubid=<?= $pub[0][0] ?>",
		type: "POST",
		data: {pageselector: cbox },
		dataType: 'json',
		success:function( data ) {
			window.parent.frames[0].currentArticle = data[1];
			var pos = {
				"left": $("#logoMenu").css("left"),
				"top": $("#logoMenu").css("top")
				};			

			jQuery('<div/>', {
				id: menu,
				class: "settingsPanel floatMenu_noclose",
				style: "left: "+pos.left+"; top: "+pos.top+";"
			}).appendTo( "body" );
			if( pos.right != undefined ) {
				$("#"+menu).css("right", pos.right );
				}
				
			$("#"+menu).html( data[0] );
			setDivCenter( menu );
			$("#"+menu).show(200);
			}
		});
	}

function sendProof( type ) {
	var cbox = new Array();
	var state = new Array();
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).val() );
		state.push( $(this).attr("state") );
		}); 
	
	$.ajax	({
		url:"engine/download_ajax.php?alter="+alter+"&type="+type+"&id=<?= $_GET["id"] ?>&part="+$('#part').val()+"&pageNumbering=<?= $pn ?>",
		type: "POST",
		data: {pageselector: cbox, state: state },
		dataType: 'json',
		success:function( data ) {
			var download2 = new Array( 'one', 'multi', 'jpg' );

			// "one" (PDF Merged) is the only download type that combines
			// pages into a single PDF - so it's the only one where a
			// selection spanning Parts with different color standards is a
			// real problem (a merged PDF can only carry one output intent).
			// "multi" zips separate PDFs and "jpg" normalizes every page to
			// sRGB independently, so mixed standards are fine for both.
			if( type == 'one' && data && data.error == 'colorMismatch' ) {
				alert( "The pages can't be downloaded together because their color standards don't match. Please download publication parts individually." );
				}
			else if( jQuery.inArray( type, download2 ) != -1 ) {
				download(data, type);
				}
			enableContext = 1;
			setTimeout( function() {
				$('body').css({'cursor':'default'});
				}, 870);
			$( "input[type='checkbox'][name='pageSelector[]']:checked" ).each(function(){
				$(this).prop('checked', false);
				});
			$( ".selectRight, .selectLeft" ).each(function(){
				$(this).css({ opacity: '0' });
				});
						
			setTimeout( function() {
				$('#loading_bar').hide( 300 );
				}, 900);
			$('body').off();
			txt += " ";
			}
		});
	}

function myContextMenu( type ) {
	if( $( "#"+currentplace+" input:checked" ).length > 0 ) {
		if( type == "hotlink" ) {
			settingsPanel( "hotlink_prepare", undefined, "<?= $_GET['id'] ?>|<?= $_GET['opt'] ?>" );
			}
		else {
			if( type == "multi" ) {
				var cbox = new Array();
				var state = new Array();
				$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
					cbox.push( $(this).val() );
					state.push( $(this).attr("state") );
					}); 

				var link = "filedownload.php?alter="+alter+"&type=multi&id=<?= $_GET["id"] ?>&pageselector="+JSON.stringify( cbox )+"&state="+JSON.stringify( state )+"";
				
				if ($idown) { $idown.attr('src',link); }
				else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
				}
			else {
				enableContext = 0;
				var gogo = 0;
				
				$('body').css('cursor', 'progress');
				$('#loading_bar').show( 300 );

				if( alter == "PRE" && type == "proof" ) {
					var message = "Proofs printed from the PRE Flatplan are FOGRA 39 certified, and may not match the actual production. Please don’t send them to the printing house!";
					trkDialog(message, function(){ sendProof( type ) }, function(){ enableContext = 1; $('body').css({'cursor':'default'}); $('#loading_bar').hide( 300 ); } );
					}
				else {
					sendProof( type );
					}
				}
			}
		}
	}

$("#fp_holder, #fp_holder2").scroll(function() {
	clearTimeout($.data(this, 'scrollTimer'));
	var target = $(this).attr("id");
    $.data(this, 'scrollTimer', setTimeout(function() {
		$.ajax	({
			url:"engine/ajax.php?op=savescroll&scroll="+$("#"+target).scrollTop()+"&id=<?= $_GET["id"] ?>",
			type: "GET",
			dataType: 'json',
			success:function( data ) {}
			});
    	}, 250));
	});

$(".custom-menu li").click(function(){
    switch($(this).attr("data-action")) {
	    case "plan-add": plannerContextMenu('create', 'planner_modify'); break;
	    case "plan-remove": plannerContextMenu('remove', 'planner_remove'); break;
	    case "plan-modify": plannerContextMenu('modify', 'planner_modify'); break;
        case "first": myContextMenu('one'); break;
        case "second": myContextMenu('multi'); break;
        case "third": myContextMenu('jpg'); break;
        case "proof": myContextMenu('proof'); break;
        case "accept": myContextMenu('accept'); break;
        case "reject": myContextMenu('reject'); break;
        case "cancel": myContextMenu('cancel'); break;
        case "hotlink": myContextMenu('hotlink'); break;
    	}
     $(".custom-menu").fadeOut(100);
 	});
 	
var $form = $('.box');
$form.addClass('has-advanced-upload');

var $input    = $form.find('input[type="file"]'),
    $label    = $form.find('label'),
    showFiles = function(files) {
      $label.text(files.length > 1 ? ($input.attr('data-multiple-caption') || '').replace( '{count}', files.length ) : files[ 0 ].name);
    };

var droppedFiles = false;
$form.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
	e.preventDefault();
	e.stopPropagation();
	})
.on('dragover dragenter', function() {
	$form.addClass('is-dragover');
	})
.on('dragleave dragend drop', function() {
	$form.removeClass('is-dragover');
	})
.on('drop', function(e) {
	droppedFiles = e.originalEvent.dataTransfer.files;
	$form.trigger('submit');
	}); 	

$input.on('change', function(e) {
	$form.trigger('submit');
	});

function uploadProgress(e) { // upload process in progress
    if (e.lengthComputable) {
        iBytesUploaded = e.loaded;
        iBytesTotal = e.total;
        var iPercentComplete = Math.round(e.loaded * 100 / e.total);

		if( iPercentComplete.toString() >= '99' || iPercentComplete.toString() >= 99 ) {
			$("#anyagpercent").html( 'Almost done' );
			}
		else {
			$("#anyagpercent").html( (iPercentComplete).toString() + '%' );
			}
    }
}

function uploadFinish(e) {
	var response = e.target.responseText;
	
	$(".box__uploading").hide(0);
	
	if( response == "error" ) {
		$(".box__success").hide(0);
		$(".box__error").show(0);
		}
		
	else {
		$(".box__success").show(0);
		$(".box__error").hide(0);
		}
		
	setTimeout( function() {
		$('.box')[0].reset();
		$('.box__file').val("");
		$form.removeClass('is-uploading');
		$(".box__success").hide(0);
		$(".box__error").hide(0);
		$(".box__input").show(0);
		}, 2000);
	}

$form.on('submit', function(e) {
	if ($form.hasClass('is-uploading')) return false;
	$form.addClass('is-uploading').removeClass('is-error');
	
	$(".box__input").hide(0);
	$(".box__uploading").show(0);
	
	e.preventDefault();
	var ajaxData = new FormData($form.get(0));
	ajaxData.append( "code", "<?= $magazine[0][3] ?>" );
	if (droppedFiles) {
    	$.each( droppedFiles, function(i, file) {
			ajaxData.append( $input.attr('name'), file );
    		});
  		}

    var oXHR = new XMLHttpRequest();        
    oXHR.upload.addEventListener('progress', uploadProgress, false);
    oXHR.addEventListener('load', uploadFinish, false);
    oXHR.open('POST', 'engine/anyagfeltoltes.php');
    oXHR.send(ajaxData);
	});
 
function plannerAddCheck() {} 
 	
</script>