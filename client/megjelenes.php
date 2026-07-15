<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>

<div id="slider_w" style='width: 1100px; margin: auto; display:block;'>
<div id="slider_wrapper" style='display:none;'>
	<div style='float: left;'>
		<div id="current_place" style='-webkit-border-radius: 10px; -moz-border-radius: 10px; border-radius: 10px; line-height: 28px; text-align: left; height: 28px; width: 500px; margin-bottom: 6px; background: #E6E8EB;'>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Kiválasztott magazin: <span id='cm'></span>
		</div>
		<div id="all_pub_div">
			<table width='500px' id='job_names' class='kiadvanyok' cellspacing='0' cellpadding='0'>
				<thead>
					<tr>
						<td class='left bottom' style='padding-top: 0px !important; padding-bottom: 0px !important; background: #E6E8EB !important;' height='28px'>Kód</td>
						<td class='bottom' style='padding-top: 0px !important; padding-bottom: 0px !important; background: #E6E8EB !important;' height='28px'>Terjedelem</td>
						<td class='bottom' style='padding-top: 0px !important; padding-bottom: 0px !important; background: #E6E8EB !important;' height='28px'>Határidő</td>
						<td class='right bottom' style='padding-top: 0px !important; padding-bottom: 0px !important; background: #E6E8EB !important;' height='28px'>&nbsp;</td>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>	
		</div>
	</div>
	
	<div style='float: right;'>
		<div id="mySliderTabs2" style="min-width: 580px !important;">
		  <ul>
			<li><a href="#publication_div">Új megjelenés</a></li>
			<? if( $rights['ad_sizes'] ) { ?>
			<li><a href="#ads_div">Hirdetés méretek</a></li>
			<?	}	?>
		  </ul>

			<div id="publication_div">
			<form id="send_pub" onsubmit="send_pub(); return false;" method="post" action="">
				<table width='580px' id='job_names' cellspacing='0' cellpadding='0'>
					<tbody>
						<tr><td>
						<div id="send_pub_content" style="display: block;">
							<table width='580px' cellspacing='0' cellpadding='0' style='margin: 0px auto;'>
							<tr>
								<td style='cursor: pointer; background: #E6E8EB !important; padding-left: 10px; font-family: myriad_bold;' class='two left right bottom' align="left" colspan="2" height='35px' onclick="toggle_div('settings', jQuery('div', this) );"><div id="t4" class="toggler" style="width: 70px;">Beállítások</div></td>
							</tr>
							<tr><td colspan="2">
								<div id="settings" style="display: none;">
									<table width='580px' cellspacing='0' cellpadding='0' style='margin: 0px auto;'>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Megjelenés kód</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress="" type='text' id='job_code' name='job_code' style='width: 200px;'></td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Terjedelem</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'><input onkeypress="return isNumberKey(event)" type='text' id='page_nr' name='page_nr' style='width: 200px;'></td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Határidő</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'>
												<input readonly class="datepicker" type="text" name='dl' id='dl'>
											</td>
										</tr>
									</table>
								</div>
							</td></tr>
							<tr>
								<td style='cursor: pointer; background: #E6E8EB !important; padding-left: 10px; font-family: myriad_bold;' class='two left right bottom' align="left" colspan="2" height='35px' onclick="toggle_div('parts', jQuery('div', this) );"><div id="t5" class="toggler" style="width: 70px;">Részek</div></td>
							</tr>
							<tr><td colspan="2">
								<div id="parts" style="display: none;">
									<? include_once( 'parts.php' ); ?>
								</div>
							</td></tr>
							<tr>
								<td style='background: #E6E8EB !important; padding-left: 10px;' class='two left right bottom' align="left" colspan="2" height='35px'>
									<div id="t1" class="toggler" style="cursor: pointer; float:left; font-family: myriad_bold;" onclick="toggle_div('internal', $(this) ); toggle_div('internal_preview');">Belső elnevezés</div>
									<div id="internal_preview" style=" display: none; float:right; padding-right: 60px;">Példa: <span id='preview1'></span></div>
									<input type='hidden' id='m_id' name='m_id'>
								</td>
							</tr>
							<tr><td colspan="2">
								<div id="internal" style="display: none;">
									<table width='580px' cellspacing='0' cellpadding='0' style='margin: 0px auto;'>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Kiadványkód</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress="" readonly class='target' type='text' id='i_code' name='i_code' style='width: 200px;'></td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Elválasztás</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'>
												<select class='target' id='i_delimiter' name='i_delimiter'>
													<option value=''> </option>
													<option value='_'>_</option>
												</select>
											</td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Toldat</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress="return isAllowedKey(event)" readonly class='target' type='text' id='i_base' name='i_base' style='width: 200px;'></td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Oldalszám helye</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'>
												<select class='target' id='i_variable' name='i_variable'>
													<option value='before'>Elötte</option>
													<option value='after'>Utána</option>
												</select>
											</td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Számozás típusa</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'>
												<select class='target' id='i_padding' name='i_padding'>
													<option value='1'>1</option>
													<option value='2'>01</option>
													<option selected value='3'>001</option>
													<option value='4'>0001</option>
												</select>
											</td>
										</tr>
									</table>
								</div>
							</td></tr>	
							<tr>
								<td style='background: #E6E8EB !important;padding-left: 10px;' class='two left right bottom' align="left" colspan="2" height='35px'>
									<div id="t2" class="toggler" style="cursor: pointer; float:left; font-family: myriad_bold;" onclick="toggle_div('upload', $(this) ); toggle_div('upload_preview');">Elnevezés a softproof-on</div>
									<div id="upload_preview" style="display: none; float:right; padding-right: 60px;">Példa: <span id='preview2'></span></div>
								</td>
							</tr>
							<tr><td colspan="2">
								<div id="upload" style="display: none;">
									<table width='580px' cellspacing='0' cellpadding='0' style='margin: 0px auto;'>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Kiadványkód</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress="" class='target2' type='text' id='u_code' name='u_code' style='width: 200px;'></td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Elválasztás</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'>
												<select class='target2' id='u_delimiter' name='u_delimiter'>
													<option value=''> </option>
													<option value='_'>_</option>
												</select>
											</td>
										</tr>							
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Toldat</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress="" class='target2' type='text' id='u_base' name='u_base' style='width: 200px;'></td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Oldalszám helye</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'>
												<select class='target2' id='u_variable' name='u_variable'>
													<option value='before'>Elötte</option>
													<option value='after'>Utána</option>
												</select>
											</td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Oldalszám elválasztása</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'>
												<select class='target2' id='u_var_del' name='u_var_del'>
													<option value=''> </option>
													<option selected value='_'>_</option>
												</select>
											</td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Számozás típusa</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'>
												<select class='target2' id='u_padding' name='u_padding'>
													<option value='1'>1</option>
													<option value='2'>01</option>
													<option selected value='3'>001</option>
													<option value='4'>0001</option>
												</select>
											</td>
										</tr>
									</table>
								</div>
							</td></tr>
							<tr>
								<td style='background: #E6E8EB !important;padding-left: 10px;' class='two left right bottom' align="left" colspan="2" height='35px'>
									<div id="t3" class="toggler" style="cursor: pointer; float:left; font-family: myriad_bold;" onclick="toggle_div('output', $(this) ); toggle_div('output_preview');">Jóváhagyott fájlok elnevezése</div>
									<div id="output_preview" style="display:none; float:right; padding-right: 60px;">Példa: <span id='preview3'></span></div>
								</td>
							</tr>
							<tr><td colspan="2">
								<div id="output" style="display: none;">
									<table width='580px' cellspacing='0' cellpadding='0' style='margin: 0px auto;'>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Kiadványkód</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress="" class='target3' type='text' id='o_code' name='o_code' style='width: 200px;'></td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Elválasztás</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'>
												<select class='target3' id='o_delimiter' name='o_delimiter'>
													<option value=''> </option>
													<option value='_'>_</option>
												</select>
											</td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Toldat</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'><input onkeypress="" class='target3' type='text' id='o_base' name='o_base' style='width: 200px;'></td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Oldalszám helye</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'>
												<select class='target3' id='o_variable' name='o_variable'>
													<option value='before'>Elötte</option>
													<option value='after'>Utána</option>
												</select>
											</td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='two left bottom' align='left' align='left' width='50%' height='28px'>Oldalszám elválasztása</td>
											<td class='two right bottom' align='left' style='padding-left: 2px;'>
												<select class='target3' id='o_var_del' name='o_var_del'>
													<option value=''> </option>
													<option selected value='_'>_</option>
												</select>
											</td>
										</tr>
										<tr>
											<td style='padding-left: 10px;' class='one left bottom' align='left' align='left' width='50%' height='28px'>Számozás típusa</td>
											<td class='one right bottom' align='left' style='padding-left: 2px;'>
												<select class='target3' id='o_padding' name='o_padding'>
													<option value='1'>1</option>
													<option value='2'>01</option>
													<option selected value='3'>001</option>
													<option value='4'>0001</option>
												</select>
											</td>
										</tr>
									</table>
								</div>
							</td></tr>
							<tr>
								<td style='background: #E6E8EB;' class='left right bottom' colspan='2' height='34px'><button id='create' disabled style='padding: 5px 20px 5px 20px;'>Létrehoz</button><button onclick='hide_pubs(); return false;' id='close' style='padding: 5px 20px 5px 20px;'>Mégse</button></td>
							</tr>
							</table>
						</div>
						</td></tr>
					</tbody>
				</table>
			</form> 
			</div>
			<div id="ads_div">
				<? include_once( 'ads.php' ); ?>
			</div>
		</div>
	</div>
	
	<div style='clear:both;'></div>
</div>
</div>

<script>
	var slider2 = $("div#mySliderTabs2").sliderTabs({
		  autoplay: false,
		  defaultTab: 1, 
		  mousewheel: false,
		  position: "top",
		  width: 580
			});
			
  $(function() {
    $('.datepicker').datetimepicker({
		timeFormat: 'HH:mm',
		stepHour: 2,
		stepMinute: 1
		});
	$('div.ui-datepicker').css({ fontSize: '12px' });
  });
</script>