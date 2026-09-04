<link rel="stylesheet" href="css/jquery-ui.css">
<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
<link href="css/main.css" rel="stylesheet" type="text/css" />
<link href="css/load_bar.css" rel="stylesheet" type="text/css" />
<style>
.thumb.dropHover {
	outline: 3px solid #2ecc71;
	outline-offset: -3px;
	}
#dropUploadOverlay {
	display: none;
	position: fixed;
	top: 0; left: 0; right: 0; bottom: 0;
	background: rgba(0,0,0,0.65);
	z-index: 999999;
	}
#dropUploadOverlay .dropUploadBox {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	background: #1a1a1a;
	color: #fff;
	padding: 40px 70px;
	border-radius: 6px;
	text-align: center;
	min-width: 260px;
	}
#dropUploadOverlay .dropUploadTitle {
	font-family: myriad_thin;
	font-size: 23px;
	padding-bottom: 20px;
	}
#dropUploadOverlay .dropUploadFilename {
	font-size: 13px;
	color: #ccc;
	padding-bottom: 20px;
	word-break: break-all;
	}
#dropUploadOverlay .dropUploadPercent {
	font-family: myriad_bold;
	font-size: 42px;
	}
#dropUploadOverlay.dropUploadError .dropUploadPercent {
	font-family: myriad_thin;
	font-size: 20px;
	color: #ff6b6b;
	}
</style>
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
 	
// Hybrid workflow has no flatplan stages: its single flatplan IS the
// FINAL one (page_pdf-handler.php coerces every incoming page's stage
// designation to FIN for Hybrid pubs). Force the view to FIN here, before
// either chip block's opt-defaulting logic runs, and remember the fact so
// both blocks (mobile + desktop) can skip rendering the PRE/BASIC/FINAL
// stage chips entirely.
$wfXml = simplexml_load_file( 'xml/'.PMD.'.xml' );
$wfXpath = $wfXml->xpath('/Publications');
foreach( $wfXpath as $wfTemp ) {
	for( $wfI = 0; $wfI < count( $wfTemp->Item ); $wfI++ ) {
		if( $wfTemp->Item[$wfI]->Code == $magazine[0][3] )
			break;
		}
	}
$hybridFP = ( (string) $wfXml->Item[$wfI]->Workflow == "Hybrid" );
// Same "no second stage" case the fin-forcing block below handles for
// $_GET['opt'] - reused further down (see the customMenu accept/reject/
// cancel <li> block) so that gate doesn't have to rely on $_GET['opt']
// ever equalling "FIN" for these jobs, since it deliberately never does.
$stages1 = ( (string) $wfXml->Item[$wfI]->FlatplanStages == "1" and !$hybridFP );
if( $hybridFP ) {
	$_GET['opt'] = "FIN";
	}
// A FlatplanStages==1 job (like Hybrid, just via a different XML field)
// has only the one flatplan too - no PRE/BASIC/FIN distinction, even
// though individual submissions still carry a real fin bit (ads are
// conventionally sent FIN regardless of stage count - see
// page_pdf-handler.php's $stages1 handling). Below, the chip-rendering
// blocks already know this (their $flatplans array stays empty unless
// FlatplanStages is "2" or "3"), but without also forcing $_GET['opt']
// here, their *opt-restoring* switch (keyed off the account's stored
// lastOpt) can still resolve to "FIN" behind the user's back - e.g. as
// soon as any pageinfo row picks up status 1/2 (accepted/rejected; an
// uploaded ad's row commonly starts this way) satisfies that switch's
// "does a FIN-worthy row exist" check. loadPagePair then filters its
// whole page list to fin='1', so every real (fin=0) page vanishes from
// the grid while only the ad (fin=1) remains - looking exactly like the
// other pages were deleted, when they were just filtered out of view.
elseif( $stages1 ) {
	$_GET['opt'] = "";
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
			$fpStages = (string) $xml->Item[$x]->FlatplanStages;
			// Hybrid: single flatplan, no stage chips at all (the view is
			// already forced to FIN near the top of this file). Otherwise the
			// chips shown depend on FlatplanStages: 1 stage means no PRE/BASIC/FIN
			// distinction at all (no chips), 2 stages means BASIC+FIN (no PRE), 3
			// stages means all three.
			$flatplans = array();
			if( $process != "Softproof" and $process != "Hybrid" ) {
				if( $fpStages == "3" ) {
					$flatplans = array(
						"PRE" => 'pre',
						"" => 'basic',
						"FIN" => 'final'
						);
					}
				else if( $fpStages == "2" ) {
					$flatplans = array(
						"" => 'basic',
						"FIN" => 'final'
						);
					}
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

<div id='dropUploadOverlay'>
	<div class='dropUploadBox'>
		<div class='dropUploadTitle'>Uploading&hellip;</div>
		<div class='dropUploadFilename'></div>
		<div class='dropUploadPercent'>0%</div>
	</div>
</div>

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
								$fpStages = (string) $xml->Item[$x]->FlatplanStages;
								// Hybrid: single flatplan, no stage chips at all (the
								// view is already forced to FIN near the top of this
								// file). Otherwise the chips shown depend on
								// FlatplanStages: 1 stage means no PRE/BASIC/FIN
								// distinction at all (no chips), 2 stages means BASIC+FIN
								// (no PRE), 3 stages means all three.
								$flatplans = array();
								if( $process != "Softproof" and $process != "Hybrid" ) {
									if( $fpStages == "3" ) {
										$flatplans = array(
											"PRE" => 'pre',
											"" => 'basic',
											"FIN" => 'final',
											);
										}
									else if( $fpStages == "2" ) {
										$flatplans = array(
											"" => 'basic',
											"FIN" => 'final',
											);
										}
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
							// Temp/Adhoc-scoped accounts (job-scoped access-link
							// accounts, see the Users panel) never get a
							// userLogSettings row created for them - array_reverse()
							// on the resulting null was a PHP 8 TypeError that
							// fataled this whole page for exactly those accounts,
							// the one real difference between why Flatplan crashed
							// for them while every other view worked.
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
  <? // Same $stages1 bypass as the accept/reject/cancel gate below: for a
     // FlatplanStages==1 job, $_GET['opt'] is force-blanked above and can
     // never equal "FIN", so without this the hotlink item would silently
     // never render for these jobs even for an admin with sendHotlink rights.
     if( $user[0][0] == "1" && ( $_GET["opt"] == "FIN" || $stages1 ) && $rights["sendHotlink"] ) { ?>
  	<hr style="padding: 0;">
  	<li data-action="hotlink"><?= $lang["flatplan"]["sendHotlink"] ?></li>
  <? } ?>
  <? // $allowedOpt=="FIN" as soon as the job has any fin='1' pageinfo row
     // at all (ad slots routinely do, even outside a real "FIN" stage -
     // see $stages1 above). For a normal 2/3-stage job that's fine: opt
     // genuinely tracks which stage the user is viewing, and it only
     // needs to equal "FIN" to unlock these actions there. But a
     // FlatplanStages==1 job has $_GET['opt'] force-blanked above
     // precisely so it's never "FIN" (no such stage exists for it) - so
     // without the $stages1 bypass here, this condition was permanently
     // false and the whole accept/reject/cancel block silently never
     // rendered for these jobs, even with the right and an active pub.
     if( ( ( $rights["acceptPage"] or $rights["cancelApprove"] ) && ( $_GET['opt'] == $allowedOpt or $stages1 ) ) && ( $pub[0][12] == "created" or $pub[0][12] == "active" or $pub[0][12] == "current" ) ) { ?>
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
	var handoutPollFailures = 0;
	// 5s between retries, so 60 misses in a row is ~5 minutes of nothing
	// but failures - past that it's not a blip anymore, it's genuinely
	// down (or the job's been abandoned), and retrying forever just adds
	// load for no benefit. generateHandout() resets this back to 0 since
	// a freshly requested handout deserves its own full budget.
	var HANDOUT_POLL_FAILURE_LIMIT = 60;
	function loadhandoutmenu() {
		$.ajax	({
			url:"engine/flatplan_ajax.php?op=loadhandoutmenu&id=<?= $pub[0][0] ?>&opt=<?= $_GET['opt'] ?>",
			type: "GET",
			dataType: 'json',
			success:function( data ) {
				handoutPollFailures = 0;
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
				},
			// A failed request (timeout, transient network blip, tab
			// throttled in the background) otherwise breaks the poll chain
			// silently forever, since only the success handler ever
			// reschedules the next check - nothing here to notice
			// completion happened while the tab was open, leaving the icon
			// missing until a manual page reload. Retry on the same 5s
			// cadence instead of giving up.
			error:function() {
				handoutPollFailures++;
				if( handoutPollFailures < HANDOUT_POLL_FAILURE_LIMIT ) {
					setTimeout(function(){ loadhandoutmenu(); }, 5000);
					}
				else {
					// Give up rather than poll forever - stop the spinner so it
					// doesn't look like it's still waiting.
					$("#handoutLoading").hide( 0 );
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
				// nothing left to wait for, or the failure cap above) by the
				// time this new handout was requested - reset the failure
				// budget and kick it off again to pick up the now-pending
				// state and resume watching for it to arrive.
				handoutPollFailures = 0;
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

// Deliberately NOT inside the `if( process == "Full" )` block above -
// Hybrid jobs (the whole reason this marker exists) never enter it, so
// downloadPreflight() would never even be defined for them, and clicking
// the marker would silently no-op (or throw "not defined" in the console).
function downloadPreflight( id ) {
	var link = 'filedownload.php?type=preflight&id='+id;

	if ($idown) { $idown.attr('src',link); }
	else { $idown = $('<iframe>', { id:'idown', src:link }).hide().appendTo('body'); }
	}

// Detailed Warning/Error hover tooltip for the .preflightError marker,
// fetched from preflight_issues (populated from the pdfToolbox XML report,
// see engine/preflightXml.php - empty until Switch actually sends one).
// Delegated from document rather than bound per-marker, same reason as the
// drag-drop handlers above: markers are re-rendered by the flatplan grid's
// poll loop and a direct binding would go stale after the first refresh.
// Click (downloadPreflight(), bound inline via onclick on the marker
// itself) is untouched and keeps downloading the PDF report.
var $preflightTooltip = null;
// Bumped on every mouseenter/mouseleave so a getJSON response belonging to
// an already-left-or-superseded hover can recognize itself as stale and
// skip touching the tooltip, instead of a fragile $tooltip.is(':visible')
// timing check.
var preflightHoverToken = 0;

function ensurePreflightTooltip() {
	// Self-healing: re-create if this is the first hover, or if the
	// previously-created element is ever found detached from the document
	// (defensive - not reproduced, but cheap insurance against exactly the
	// "hover silently stops doing anything" failure mode being chased here).
	if( !$preflightTooltip || !$preflightTooltip.closest( 'body' ).length ) {
		$preflightTooltip = $( "<div class='preflightTooltip ui-tooltip ui-widget ui-widget-content ui-corner-all floatMenu'></div>" ).appendTo( 'body' );
		}
	return $preflightTooltip;
	}

// Flips the tooltip above the marker instead of below it when there isn't
// enough room left in the viewport - re-run after every content change (not
// just once on hover) since the tooltip's height grows/shrinks with the
// number of issues, and that's only known once the ajax response is in.
function positionPreflightTooltip( $marker, $tip ) {
	var off = $marker.offset();
	var margin = 16;
	var viewportBottom = $(window).scrollTop() + $(window).height();
	var tipHeight = $tip.outerHeight();

	if( off.top + margin + tipHeight > viewportBottom ) {
		$tip.css({ top: off.top - tipHeight - margin, left: off.left + margin });
		}
	else {
		$tip.css({ top: off.top + margin, left: off.left + margin });
		}
	}

$(document).on( 'mouseenter', '.preflightError', function( e ) {
	var pageId = $(this).attr( 'data-pageid' );
	if( !pageId ) return;

	var myToken = ++preflightHoverToken;
	var $marker = $(this);
	var $tip = ensurePreflightTooltip();
	$tip.text( 'Loading…' ).show();
	positionPreflightTooltip( $marker, $tip );

	$.getJSON( 'engine/preflight_issues_ajax.php', { pageid: pageId }, function( issues ) {
		if( myToken !== preflightHoverToken ) return;
		$tip = ensurePreflightTooltip();

		if( !issues || !issues.length ) {
			$tip.text( 'Preflight failed - click to download the report' );
			positionPreflightTooltip( $marker, $tip );
			return;
			}

		$tip.empty();
		for( var i = 0; i < issues.length; i++ ) {
			// Message text ultimately comes from the pdfToolbox XML report -
			// treat it as untrusted and let jQuery's text() escape it,
			// rather than building HTML from it directly.
			$( '<div></div>' )
				.toggleClass( 'preflightIssueWarning', issues[i].severity === 'Warning' )
				.text( issues[i].message )
				.appendTo( $tip );
			}
		positionPreflightTooltip( $marker, $tip );
		});
	});

$(document).on( 'mouseleave', '.preflightError', function( e ) {
	preflightHoverToken++;
	if( $preflightTooltip ) $preflightTooltip.hide();
	});

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

if( window.parent.frames[0] && window.parent.frames[0].activeFUpload ) {
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
var hybridFP = <?= $hybridFP ? 'true' : 'false' ?>;
var pubCode = "<?= $magazine[0][3] ?>";
var issueCode = "<?= $pub[0][10] ?>";

if( hybridFP ) {
	// Hybrid publications get a direct drag-and-drop PDF upload onto a
	// Flatplan slot, on top of the whole-issue Upload page path. The grid
	// under #fp_holder/#fp_holder2 is fully replaced by loadPages()'s poll
	// loop every 500ms, so listeners must be delegated from document (a
	// direct .thumb binding would stop working after the first refresh).
	$(document).on( 'dragover drop', function( e ) {
		e.preventDefault();
		});

	$(document).on( 'dragover', '.thumb', function( e ) {
		e.preventDefault();
		e.stopPropagation();
		$('.thumb.dropHover').not( this ).removeClass( 'dropHover' );
		$(this).addClass( 'dropHover' );
		});

	$(document).on( 'dragleave', '.thumb', function( e ) {
		$(this).removeClass( 'dropHover' );
		});

	$(document).on( 'drop', '.thumb', function( e ) {
		e.preventDefault();
		e.stopPropagation();
		$(this).removeClass( 'dropHover' );

		if( dropUploadActive ) return;

		var position = $(this).attr( 'page' );
		var files = e.originalEvent.dataTransfer.files;
		if( !position || files.length !== 1 ) return;

		var file = files[0];
		if( file.name.split('.').pop().toUpperCase() !== 'PDF' ) return;

		uploadPdfToSlot( file, position );
		});
	}

// If the dropped file's own name already carries the pub code and issue
// (a "proper(ish)" name), it's sent unmodified - otherwise it's prefixed
// with the 3-digit slot position, same {position}_{code}_{issue}_...
// convention Switch jobs already use elsewhere in this app (see
// client/filedownload.php, flatplan_ajax.php's drawPage/drawAmericanPage).
// American-numbering (multi-Part) jobs use {position}_{code}_{part}_...
// instead - Issue means nothing extra there (an Adhoc job's issue code is
// just its own Code again, and American pages are scoped by Part, not by
// a document-wide Issue position), while Part (the same raw abbreviation
// the #part dropdown already carries, e.g. "BEL") is what actually
// disambiguates the file for Switch/the render pipeline.
//
// Sent as real chunks (same tempdir/num/num_chunks protocol
// fileupload_ajax.php already expects from filetransfer.php/blob.php) rather
// than one giant POST - a single-POST upload of a large file both exceeds
// nginx/php's body-size limits and gives the browser no per-chunk progress
// events to report. The grid itself is left alone here; loadPages()'s
// existing 500ms poll picks up the new page once Switch processes it.
//
// While a drop upload is running, a full-screen overlay (#dropUploadOverlay)
// blocks the rest of the UI and dropUploadActive gates the drop handler
// above - a large file can take a while even chunked, and without this an
// impatient user dragging a second file mid-upload would interleave two
// uploads writing into the same flow.
var dropUploadSliceSize = 1024 * 1024 * 10; // 10MB, matches filetransfer.php
var dropUploadActive = false;

function uploadPdfToSlot( file, position ) {
	var finalName = file.name;
	var upperName = file.name.toUpperCase();
	var part = $('#part').length ? $('#part').val() : '';
	var pos3 = ( '00' + position ).slice( -3 );

	if( part ) {
		if( upperName.indexOf( pubCode.toUpperCase() ) === -1 || upperName.indexOf( part.toUpperCase() ) === -1 ) {
			finalName = pos3 + '_' + pubCode + '_' + part + '_' + file.name;
			}
		}
	else if( upperName.indexOf( pubCode.toUpperCase() ) === -1 || upperName.indexOf( String( issueCode ) ) === -1 ) {
		finalName = pos3 + '_' + pubCode + '_' + issueCode + '_' + file.name;
		}

	dropUploadActive = true;
	var $overlay = $('#dropUploadOverlay');
	$overlay.removeClass( 'dropUploadError' );
	$overlay.find( '.dropUploadTitle' ).text( 'Uploading…' );
	$overlay.find( '.dropUploadFilename' ).text( file.name );
	$overlay.find( '.dropUploadPercent' ).text( '0%' );
	$overlay.show( 0 );

	var tempdir = Date.now();
	var size = file.size;
	var numChunks = Math.max( Math.ceil( size / dropUploadSliceSize ), 1 );

	sendDropUploadChunk( file, finalName, position, tempdir, 1, numChunks, 0 );
	}

function sendDropUploadChunk( file, finalName, position, tempdir, num, numChunks, start ) {
	var end = Math.min( start + dropUploadSliceSize, file.size );
	var slicer = file.slice ? file.slice : ( file.mozSlice || file.webkitSlice );
	var chunk = slicer.call( file, start, end );

	var fd = new FormData();
	fd.append( 'type', 'pdf_to_flatplan' );
	fd.append( 'jobid', pubID );
	fd.append( 'jtype', 'pub' );
	fd.append( 'part', $('#part').length ? $('#part').val() : '' );
	fd.append( 'tempdir', tempdir );
	fd.append( 'num', String( num ) );
	fd.append( 'num_chunks', String( numChunks ) );
	fd.append( 'file', chunk, finalName );

	var xhr = new XMLHttpRequest();
	xhr.upload.addEventListener( 'progress', function( e ) {
		if( !e.lengthComputable ) return;
		var chunkBytesDone = start + e.loaded;
		var pct = Math.min( 100, Math.round( chunkBytesDone * 100 / file.size ) );
		$('#dropUploadOverlay .dropUploadPercent').text( pct + '%' );
		});

	xhr.addEventListener( 'load', function() {
		var ok = xhr.status >= 200 && xhr.status < 300;
		if( ok ) {
			try { ok = JSON.parse( xhr.responseText ).ok !== false; } catch( err ) { ok = false; }
			}

		if( !ok ) {
			dropUploadFailed();
			return;
			}

		if( num < numChunks ) {
			sendDropUploadChunk( file, finalName, position, tempdir, num + 1, numChunks, end );
			}
		else {
			dropUploadActive = false;
			$('#dropUploadOverlay .dropUploadTitle').text( 'Uploaded' );
			$('#dropUploadOverlay .dropUploadPercent').text( '100%' );
			setTimeout( function() { $('#dropUploadOverlay').hide( 0 ); }, 1000 );
			}
		});

	xhr.addEventListener( 'error', function() { dropUploadFailed(); });

	xhr.open( 'POST', 'engine/fileupload_ajax.php', true );
	xhr.send( fd );
	}

function dropUploadFailed() {
	dropUploadActive = false;
	var $overlay = $('#dropUploadOverlay').addClass( 'dropUploadError' );
	$overlay.find( '.dropUploadTitle' ).text( 'Upload failed' );
	$overlay.find( '.dropUploadPercent' ).text( '' );
	setTimeout( function() { $overlay.hide( 0 ); }, 4000 );
	}

function plannerContextMenu( info, menu ) {
	var cbox = new Array();
	
	$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
		cbox.push( $(this).val() );
		}); 
	
	if( window.parent.frames[0] && window.parent.frames[0].activeFUpload ) {
		pubID = window.parent.frames[0].currentPlannerPubID;
		}

	else if( window.parent.frames[0] ) {
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

function plannerAddCheck() {} 
 	
</script>