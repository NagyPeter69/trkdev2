<?
if (!$rights["sys_log"] ) {
	header("location: ?page=");
	}
?>
<link rel="stylesheet" href="css/jquery-ui.css">
<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
<link href="css/main.css" rel="stylesheet" type="text/css" />
<link href="css/load_bar.css" rel="stylesheet" type="text/css" />

<div id='content_box' style='overflow: hidden !important;'>
	<table id="tabla" cellspacing="0" cellpadding="0" style="width: 100%; table-layout:fixed;"><tbody>
		<tr>
			<td valign="top" align='left' style="font-size: 14px; background: rgb( 227, 227, 227); width: 229px;">
				<div class='log_leftside' style='padding-top: 10px; padding-left: 9px; position: relative;'>
					<div style='margin-bottom: 10px; margin-left: 2px;'>
						<select onchange="log_pubChange()" id="publication">
							<option value='all'>All publications</option>
							<?
							$magazines = explode( ",", $user[0][21] );
							$mags = array();
							for( $i = 0; $i < count( $magazines ); $i++ ) {
								$msql = sql_aget( "magazines", "id='".$magazines[$i]."'", "name" );
								if( $msql[0]["name"] != "" ) {
									$mags["'".$magazines[$i]."'"] = $msql[0]["name"]."-".$magazines[$i];
									}
								}
							usort($mags, querySort(0) );
							foreach( $mags as $value ) {
								$val = explode( "-", $value );
								echo "<option value='".$val[1]."'>".$val[0]."</option>";
								}
							
							?>
						</select><br>
						<select onchange="log_issueChange()" id="issue">
							<option value='all'>All issues</option>
						</select>
					</div>
					<div onclick='selector()' data='deselect' id='alloperator' style='margin-bottom: 5px; cursor: pointer;'>Deselect all</div>
					<?
					$action_log = array(
						"approvePage" => 'Page approved',
						"archiveIssue" => 'Issue archived',
						"archivingIssue" => 'Archiving started',
						"backArticle" => 'Article sent back',
						"cancelApprove" => 'Approval canceled',
						"deleteAD" => 'Ad removed',
						"deleteIssue" => 'Issue deleted',
						"deleteMagazine" => 'Publication deleted',
						"newAD" => 'New ad submitted',
						"newArticle" => 'New article arrived',
						"newPage" => 'New page arrived',
						"rejectPage" => 'Page rejected',
						"renameMagazine" => 'Magazine Renamed',
						"restartIssue" => 'Issue restarted',
						"resultAD" => 'Ad check result',
						"stoppedIssue" => 'Issue stopped',
						"updatePage" => 'New page revision',
						"uploadAD" => 'Ad uploaded to Flatplan',
						"addtocalendar" => 'Added to calendar',
						"modifycalendar" => 'Modified calendar',
						"removedfromcalendar" => 'Removed from calendar'
						);

					$command = array();
		
					foreach( $action_log as $key => $value ) {
						echo "<div><input type='checkbox' name='action_log[]' checked value='".$key."'>".$value."</div>";
						$command[] = "`action`='".$key."'";
						}

					$command = implode( " OR ", $command );
					$test_log = sql_aget( "action_log", $command, "*" );

					?>
					<div style='position: absolute; left: 0; bottom: 20px; width: 100%; text-align: center;'>
						<button onclick='export_log()'>Export system log</button>
					</div>
				</div>
			</td>
			<td id="syslog_content" align="right" valign="top" style="overflow: hidden; width: 100%; display: block; white-space: nowrap;">
				<div id='ad_table_wrapper' style='width: 100%; float: left; overflow: hidden;'>
					<table width='100%' cellspacing="0" cellpadding="0" style='padding-left: 5px; padding-top: 5px;'>
						<tr>
							<td valign='top'>
								<table cellspacing="0" cellpadding="0" style='color: #000; font-size: 14px; width: 100%; width: -webkit-calc(100% - 15px); width: -moz-calc(100% - 15px); width: calc(100% - 15px);'>
									<thead class='logheader'>
										<tr>
											<td width='14%' align="left" style='padding-left: 5px;'>Date</td>
											<td width='15%' align="left">Action</td>
											<td width='16%' align="left">Pub/Issue</td>
											<td width='14%' align="left">Initiator</td>
											<td width='27%' align="left">Subject</td>
											<td width='7%' align="left">Status</td>
											<td width='7%' align="left">Comment</td>
										</tr>
									</thead>
								</table>
								<div class='livelog_div' style='width: 100%; overflow: auto;'>
								<table width='100%' cellspacing="0" cellpadding="0" style='font-size: 14px;'>	
									<tbody id='log_content' class='livelog_table'>
									</tbody>
								</table>
								</div>
								<table width="100%">
									<tr>
										<td height='30px' align='center'>
											<button onclick='jumpbutton("prev")' id='prev_page' style='visibility: hidden;'>Prev</button>
											<span id='page_selector_span'><select id='page_selector' name='page' onchange='jumpto()'>
											<?
											$max = ceil( ( count( $test_log )/50 ) );
											for( $i = 0; $i < $max; $i++ ) {
												echo "<option value='".$i."'>Page ".($i+1)."</option>";
												}
											?>
											</select></span>
											<button onclick='jumpbutton("next")' id='next_page'>Next</button>
										</td>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
	</table>
</div>

<script>
var start = 0;
var end = parseInt( "<?= $max ?>" );
var all = parseInt( "<?= count( $test_log ) ?>" );
logHTML = "";

function selector() {
	switch( $("#alloperator").attr("data") ) {
		case 'deselect':
			$("input[name='action_log[]']").prop('checked', false);
			$("#alloperator").attr("data","select");
			break;
			
		case 'select':
			$("input[name='action_log[]']").prop('checked', true);
			$("#alloperator").attr("data","deselect");
			break;
		}
	}

function export_log() {
	var info = new Array();
	var temp = new Array();
	$("input[name='action_log[]']:checked").each(function(){
		temp.push( $(this).val() );
		});
	
	info.push( temp.join(",") );
	info.push( $("#publication").val() );
	info.push( $("#issue").val() );
	
	settingsPanel('logs_export', undefined, info.join( "|" ) );
	}

function log_pubChange() {
	$("#issue").val("all");
	$.ajax	({
		url:"engine/infogather.php?op=livelogissues&mag="+$("#publication").val(),
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			var issueHTML = "<option value='all'>All issues</option>";
			for( var i = 0; i < data.length; i++ ) {
				issueHTML += "<option value='"+data[i]+"'>"+data[i]+"</option>";
				}
				
			$("#issue option").remove();
			$('#issue').html(issueHTML);
			}
		});
		
	$("select, button").prop('disabled', 'disabled');
	loadLiveLog( undefined, "direct");	
	}

function log_issueChange() {		
	$("select, button").prop('disabled', 'disabled');
	loadLiveLog( undefined, "direct");	
	}

function jumpbutton( orient ) {
	switch( orient ) {
		case 'prev':
			start--;
			break;
		
		case 'next':
			start++;
			break;
		}
	
	$("#page_selector").val( start );
	
	if( start <= 0 ) {
		$("#prev_page").css("visibility", "hidden" );
		}
	else {
		$("#prev_page").css("visibility", "visible" );
		}
	
	if( start >= end-1 ) {
		$("#next_page").css("visibility", "hidden" );
		}
	else {
		$("#next_page").css("visibility", "visible" );
		}
	
	$("select, button").prop('disabled', 'disabled');
	loadLiveLog( undefined, "direct");
	}

function jumpto() {
	start = parseInt( $("#page_selector").val() );

	if( start <= 0 ) {
		$("#prev_page").css("visibility", "hidden" );
		}
	else {
		$("#prev_page").css("visibility", "visible" );
		}
	
	if( start >= end-1 ) {
		$("#next_page").css("visibility", "hidden" );
		}
	else {
		$("#next_page").css("visibility", "visible" );
		}
	
	$("select, button").prop('disabled', 'disabled');
	loadLiveLog( undefined, "direct");
	}

function refreshPageList() {
	var txt = "<select id='page_selector' name='page' onchange='jumpto()'>";
	
	var max = Math.ceil( all/50 );
	console.log( max );
	for( var i = 0; i < max; i++ ) {
		txt += "<option value='"+i+"'>Page "+(i+1)+"</option>";
		}
	
	txt += "</select>";
	
	var temp = $("#page_selector").val();
	$("#page_selector_span").html( txt );
	if( temp == null ) temp = 0; 
	$("#page_selector").val(temp);
	if( start <= 0 ) {
		$("#prev_page").css("visibility", "hidden" );
		}
	else {
		$("#prev_page").css("visibility", "visible" );
		}
	
	if( start >= end-1 ) {
		$("#next_page").css("visibility", "hidden" );
		}
	else {
		$("#next_page").css("visibility", "visible" );
		}
	}

function loadLiveLog( s, from ) {
	if( s == undefined ) {
		s = start;
		}
	else {
		start += s;
		}	
	
	var pub = $("#publication").val();
	var issue = $("#issue").val();
	
	$.ajax	({
		url:"engine/livelog_ajax.php?start="+s+"&pub="+pub+"&issue="+issue,
		type: "POST",
		data: { action_log: $("input[name='action_log[]']").serialize() },
		dataType: 'json',
		success:function( data ) {
			if( logHTML != data[0] ) {
				logHTML = data[0];			
				$("#log_content").html( data[0] );
				}
			
			if( from == "direct" ) {
				$("select, button").prop('disabled', false);
				}
			if( from == undefined ) {
				if( parseInt( data[1] ) != all ) {
					all = parseInt( data[1] );
					end = Math.ceil( all/50 );
					refreshPageList();
					}
				
				setTimeout(function() { loadLiveLog(); }, 800 );
				}
			}
		});	
	}
loadLiveLog();

function fit_wrapper2() {
	var ad_height = parseInt( $( window ).height() )-(parseInt( $("#header").outerHeight()) );
	$('#content_box, .log_leftside').height( ad_height );
	ad_height = ad_height-60;
	
	$('.livelog_div').height( ad_height );
	}
	
$( document ).ready(function() {
	fit_wrapper2();
	});

$(window).resize(function(){
	fit_wrapper2();
	});
	
</script>