<link href="css/filetransfer.css?time=<?= time() ?>" rel="stylesheet" type="text/css" />
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<?php
include( "../engine/fileClass.php" );
if( $_GET["type"] == "pub" ) {
	$munka = "normal";
	$job = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
	$mag = sql_aget( "magazines", "id='".$job[0]["magazine_id"]."'", "*" );
	if( $mag[0]["type"] == "Adhoc" ) {
		$munka = "adhoc";
		}
		
	$jobname = $mag[0]["code"]."_".$job[0]["code"];
	$parts = sql_aget( "parts", "pub_id='".$job[0]["id"]."' order by id ASC", "*" );
	}
	
else {
	$munka = "adhoc";
	$job = sql_aget( "jobs", "id='".$_GET["id"]."'", "*" );
	$_GET["type"] = "job";
	$jobname = $job[0]["name"];
	$parts = sql_aget( "parts", "pub_id='".$job[0]["id"]."' order by id ASC", "*" );
	$mag = sql_aget( "magazines", "id='".$job[0]["magazine_id"]."'", "*" );
	}

$background = "#103d8b";
setlocale(LC_ALL,'hu_HU.utf8');
$uploader = new file( $parts, $user[0][0] );

$usettings = json_decode( $user[0][35], true );
if( empty( $usettings["code"] ) ) {
	$usettings["code"] = $job[0]["id"];
	}
	
$magTypes = array();
$mags = explode( ",", $user[0][21] );
for( $i = 0; $i < count( $mags ); $i++ ) {
	$check = sql_aget( "magazines", "id='".$mags[$i]."'", "*" );
	if( !empty( $check[0]["id"] ) ) {
		if( !in_array( $check[0]["type"], $magTypes ) ) {
			$magTypes[] = $check[0]["type"];
			}
		}
	}
	
$forceParam = false;
if( count( $magTypes ) == "1" ) {
	if( $magTypes[0] == "Adhoc" ) {
		$user[0][38] = 1;
		$forceParam = true;
		}
	}
	
$typelist = "filetype";
if( $user[0][4] == "6" ) {
	$typelist = "filetypefull";
	}	
	
?>

<div id="finishedIssue" style="padding-top: 30px; pointer-events: none; display: none; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; background-color: transparent; z-index: 900; margin: auto; vertical-align: middle;">
	<div style="display: table; width: 100%; height: 100%;">
		<div style="display: table-cell; height: 100%; width: 100%; vertical-align: middle; font-size: 16px; color: #FFF;">
			<?= $lang["filetransfer"]["finishedIssue"] ?>
		</div>
	</div>
</div>

<?php
// #content (default.php) is `position: relative` with no explicit height -
// its only children on this page are absolutely positioned, so it collapses
// to zero height. `position: absolute; top: 50%` on a zero-height containing
// block can't resolve, so the browser falls back to this element's static
// (in-flow) position - right under the header - and translateY(-50%) then
// shifts it up into the nav bar. `position: fixed` centers against the
// viewport instead, which always has a real height.
?>
<div style="width: 100%; margin: 0; position: fixed; top: 50%; -ms-transform: translateY(-50%); transform: translateY(-50%); text-align: center;">
	<div id="currentMag" class="switchDisplay" style="font-family: myriad_thin; color: #FFF; font-size: 22px; padding-bottom: 15px;"></div>
	
	<table id="transferTable" cellspacing="0" cellpadding="0" style="text-align: center;font-family: myriad">
		<tr class="switchDisplay" id="part_row">
			<td id='adhoclink' align="center" colspan="2" style="text-align: center; user-select: text; -moz-user-select: text; -webkit-user-select: text; -ms-user-select: text;">
			<?php
			if( $user[0][4] == "6" ) {
				if( $mag[0]["type"] == "Adhoc" ) {
					$hotlink = sql_aget( "adhoc_hotlinks", "magazine_id='".$mag[0]["id"]."' order by id DESC Limit 1", "*" );
					$link = "https://".URL."/index.php?hash=".$hotlink[0]["hash"];
					
					echo "<div style='padding-bottom: 20px;'>";
						echo $link."<button onclick='hotlinkresend(\"".$hotlink[0]["id"]."\")' style='margin-left: 10px;'>".$lang["filetransfer"]["resend"]."</button>";
					echo "</div>";
					}
				}
			?>
			</td>
		</tr>
		
		<tr class="switchDisplay">
			<td><?= $lang["filetransfer"]["jobcode"] ?>:</td>
			<td>
				<?php
				if( $_GET["type"] == "pub" ) {
					$munka = "normal";
					$job = sql_aget( "publications", "id='".$_GET["id"]."'", "*" );
					$mag = sql_aget( "magazines", "id='".$job[0]["magazine_id"]."'", "*" );
				
					$jobname = $mag[0]["code"]."_".$job[0]["code"];
				
					$pubs = getShortPubs3( $user[0] );
					echo '<div style="z-index: 950;"><select id="code" style="margin-left: -1px; z-index: 950;">';
					for( $i = 0; $i < count( $pubs ); $i++ ) {
						$magazine = sql_get( 'magazines', 'id="'.$pubs[$i][2].'"', 'code, type, name' );
								
						echo "<option value='".$pubs[$i][0]."' ".( $usettings["code"] == $pubs[$i][0] ? "selected" : "" )." text-value='".( $magazine[0][1] == "Regular" ? $magazine[0][0]." ".$pubs[$i][10] : $magazine[0][0] )."' long-value=' – ".$magazine[0][2]."'>".( $magazine[0][1] == "Regular" ? $magazine[0][0]." ".$pubs[$i][10] : $magazine[0][0] )."</option>";
						}
					echo '</select></div>';
					}
					
				else {
					$munka = "adhoc";
					$job = sql_aget( "jobs", "id='12'", "*" );
					$_GET["type"] = "job";
					echo $job[0]["name"]."<input type='hidden' id='code' value='".$job[0]["id"]."'>";
					}
				?>
			</td>
		</tr>

		<tr class="switchDisplay" id="type_row">
			<td><?= $lang["filetransfer"]["asset"] ?>:</td>
			<td id="typeTD"><?= $uploader->getSelectList( $typelist, "type" ) ?></td>
		</tr>

		<tr class="switchDisplay" id="parts_row">
			<td><?= $lang["filetransfer"]["part"] ?>:</td>
			<td id="partTD"></td>
		</tr>

		<tr class="switchDisplay" id="packname_row" style="display: none;">
			<td style="padding-bottom: 20px;"><?= $lang["filetransfer"]["imgpackname"] ?>:</td>
			<td id="packname" style="padding-bottom: 20px;"><input type="text" name="packname" id="imgpack" onkeyup="checkUploadEligible()"></td>
		</tr>
		
		<tr id="files_row">
			<td colspan="2" style="text-align: center !important; padding-top:20px;">
				<div id="boxWrap">
					<div id="anyagfeltoltes">
						<form class="box" method="post" action="" enctype="multipart/form-data" style="height: 100%;">
						  <div class="box__title" style="padding-top: 67px; font-size: 23px;"><?= $lang["filetransfer"]["dropbox"] ?></div>
						  <div class="box__input" style="padding-top: 5px; font-size: 13px;">
						    <input class="box__file" type="file" name="file[]" id="file" data-multiple-caption="{count} files selected" multiple />
						  </div>
						</form>	
					</div>

					<div id="info" class="height: 100%; overflow: auto;">
						<?= $lang['uploads']['info'] ?>
					</div>
				</div>
			</td>
		</tr>

		<tr id="extInfo" class="switchDisplay">
			<td colspan="2" style="text-align: center !important;">
				<div style="font-size: 13px; padding-bottom: 10px;"><?= $lang["filetransfer"]["accepted"] ?>: <span id="accepted_ext"></span></div>
			</td>
		</tr>

		<tr id="stopButton" style="display: none;">
			<td colspan="2" style="text-align: center !important;">
				<div id="button_large3" onClick='stopUpload()'>Stop</div>
			</td>
		</tr>

		<tr id="funcButtons">
			<td colspan="2" style="text-align: center !important;">
				<div id="button_large3" onClick='setInput( false ); $(".box__file").click();' style="font-size: 14px;"><?= $lang["filetransfer"]["browsefile"] ?></div>&nbsp;&nbsp;&nbsp;&nbsp;
				<!--
				<div id="button_large3" onClick='setInput( true ); $(".box__file").click();' style="font-size: 14px;"><?= $lang["filetransfer"]["browsedir"] ?></div>&nbsp;&nbsp;&nbsp;&nbsp;
				-->
				<div id="button_large5" onClick='$(".box").submit()' style="font-size: 14px;"><?= $lang["filetransfer"]["upload"] ?></div>
			</td>
		</tr>
		<tr id="blank_row" style="display: none;">
			<td colspan="2" style="height: 561px;"></td>
		</tr>
	</table>
	
	<div class="historyBox" style="">	
		<table id="transferHistoryTable" cellspacing="0" cellpadding="0" style="text-align: center; padding-left: 25px; padding-right: 15px; padding-bottom: 15px;">
			<thead>
				<tr>
					<td class="historyTitle" colspan="2" style="font-size: 16px; padding-bottom: 20px; font-weight: inherit; font-family: myriad; text-align: center !important; background: transparent; color: rgb( 220 220 220 ); padding-top: 15px;"><?= $lang["filetransfer"]["history"] ?></td>
				</tr>		
			</thead>
			
			<tbody id="transferHistoryBody" style="height: 161px; overflow-y: auto; display: block; margin-right: -15px; padding-right: 15px;">
			</tbody>
		</table>
	</div>
	<div style="clear: both;"></div>
	
	<?php if( !$forceParam ) { ?>
	<div class="historyBox" style="background: transparent; margin-top: 15px;">	
		<table id="transferHistoryTable" cellspacing="0" cellpadding="0" style="text-align: center; padding-left: 25px; padding-right: 15px; padding-bottom: 15px;">
			<tr>
				<td style="text-align: center;">
					<input type="checkbox" name="uploadParams" id="uploadParams" value="1" <?= ( $user[0][38] == "1" ? "checked" : "" ) ?>>&nbsp;<?= $lang['settings']['upload_params'] ?>
				</td>
			</tr>
		</table>
	</div>
	<?php } ?>
	
</div>

<script>
function hotlinkresend( hid ) {
	$.ajax({
		url:"engine/ajax.php?op=hotlinkresend&hotlinkid="+hid,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			alert( "<?= $lang['filetransfer']['resendok'] ?>" );
			} 
		});		
	}

var display = parseInt("<?= $user[0][38] ?>");
if( !display ) {
	$(".switchDisplay").hide(0);
	}
	
var dirupload = false;
function setInput( param ) {
	if( param ) {
		$("#file").attr("webkitdirectory","true");
		$("#file").attr("mozdirectory","true");
		}
	else {
		$("#file").removeAttr("webkitdirectory");
		$("#file").removeAttr("mozdirectory");
		}
	
	dirupload = param;
	}

var filetype_array = {
	"indesign_pack": {
		"name" : "<?= $lang["filetransfer"]["zip"] ?>",
		"ext" : [ "ZIP" ],
		},
	"indesign_file": {
		"name" : "<?= $lang["filetransfer"]["indd"] ?>",
		"ext" : [ "ZIP", "INDD" ],
		},
	"pdf": {
		"name" : "<?= $lang["filetransfer"]["pdf"] ?>",
		"ext" : [ "PDF" ],
		},
	"pdf_to_flatplan": {
		"name" : "<?= $lang["filetransfer"]["pdf_to_flatplan"] ?>",
		"ext" : [ "PDF" ],
		},
	"picture": {
		"name" : "<?= $lang["filetransfer"]["images"] ?>",
		"ext" : [ "TIF", "TIFF", "JPG", "JPEG", "PSD", "PNG" ],
		},
	"picture_pack": {
		"name" : "<?= $lang["filetransfer"]["pic_pack"] ?>",
		"ext" : [ "TIF", "TIFF", "JPG", "JPEG", "PNG", "PSD" ],
		},
	}

function getHistory() {
	$.ajax({
		url:"engine/ajax.php?op=filetransfer_history&jname="+$("#code").val()+"&jtype=<?= $_GET["type"] ?>&display="+display,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#transferHistoryBody").html( data[0] );
			$("#adhoclink").html( data[1] );
			}
		});	
	}
getHistory();

function recheckFileList() {
	files = '';
	for( var i = 0; i < files_data.length; i++ ) {
		files += addUploadRow( i, files_data[i].name );
		}
	if( files != '' ) {
		$('#info').html( files );
		}
	else {
		$('#info').html( default_inf );
		$('#info').removeClass('upload_fileList');
		$('#submit_files').fadeOut('fast');
		$('#realFile').val('');
		}
	if( files_data.length < 1 ) {	
		$('#anyagfeltoltes').css("display", "inline-block");
		$('#info').css("display", "none");
		}	
	}

$("#uploadParams").change( function() {
	var temp = 0;
	if( $('#uploadParams').is(':checked') ) {
		temp = 1;
		}
	$.ajax({
		url:"engine/ajax.php?op=uploadparamchange&cb="+temp,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			display = temp;
			if( display ) {
				$(".switchDisplay").show(0);
				}
			else {
				$(".switchDisplay").hide(0);
				}
				
			getHistory();
			recheckFileList();
			finishedHandle();
			

			}
		});		
	});

function accepted_ext() {
	$("#accepted_ext").html( filetype_array[ $("#type").val() ].name );
	}
accepted_ext();
	
function saveUploadSettings() {
	var info = {
		"code" : $("#code option:selected").val(),
		"type" : $("#type option:selected").val(),
		"part" : $("#part option:selected").val(),
		};
		
	$.ajax({
		url:"engine/ajax.php?op=uploadsettingssave",
		type: "POST",
		data: { settings : info },
		dataType: 'json',
		success:function( data ) {}
		});		
	}
saveUploadSettings();

function loadType() {
	$.ajax({
		url:"engine/ajax.php?op=filetransfer_loadtypes&pid="+$("#code").val(),
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#typeTD").html( data[0] );
			var pageNumbering = data[1];

			$("#type").change( function() {
				accepted_ext();
				var temp = $("div[fileid]");
				$("div[fileid]").each(function(){
					var name = $(this).find(".fname").html();
					var ext = name.split( "." );
					ext = ext[ext.length - 1].toUpperCase();

					if( jQuery.inArray( ext, filetype_array[ $("#type").val() ].ext ) == -1 ) {
						$("span:contains('"+name+"')").parent().parent().find(".fas").click();
						}
					});

				console.log( $("#type").val() );

				if( $("#type").val() == "picture_pack" ) {
					console.log( $("#part_row") );
					$("#packname_row").show(0);
					$("#parts_row").hide(0);
					}
				else {
					$("#packname_row").hide(0);
					$("#parts_row").show(0);
					}

				// Parts only exist at all for American (US-style) page
				// numbering - a European/absolute-numbered publication
				// (Hybrid or not) has no Part to assign, so this dropdown
				// has no business showing regardless of asset type.
				if( pageNumbering != "American" || $("#type").val() == "picture" || $("#type").val() == "pdf" ) {
					$("#parts_row").hide(0);
					}
				else {
					$("#parts_row").show(0);
					}

				saveUploadSettings();
				});
			$("#type").change();
			}
		});		
	}

$("#code").change( function(){
	//click1Done = false;
	$("#currentMag").html( $("#code option:selected").text()  );
	loadType();
	loadParts();
	getHistory();
	saveUploadSettings();
	$("#code").blur();
	});
	
$("#code").change();

function getParts() {
	$.ajax({
		url:"engine/ajax.php?op=filetransfer_getparts&pub="+$("#code").val(),
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			//console.log( data );
			}
		});
  }
getParts();	

var finished = 0;
function finishedHandle() {
	if( $('#uploadParams').is(':checked') ) {
		if( finished == true ) {
			$("#finishedIssue").show(0);
			$("#blank_row").show(0);
			$("#funcButtons").hide(0);
			$("#type_row").hide(0);
			$("#part_row").hide(0);
			$("#parts_row").hide(0);
			$("#packname_row").hide(0);
			$("#files_row").hide(0);
			$("#extInfo").hide(0);
			$("#transferHistoryTable").hide(0);
			}
		else {
			$("#finishedIssue").hide(0);
			$("#blank_row").hide(0);
			$("#funcButtons").show(0);
			$("#type_row").show(0);
			$("#part_row").show(0);
			$("#parts_row").show(0);
			$("#packname_row").show(0);
			$("#files_row").show(0);
			$("#extInfo").show(0);
			$("#transferHistoryTable").show(0);				
			}
			
		$("#type").change();
		}
	else {
		$("#finishedIssue").hide(0);
		$("#files_row").show(0);
		$("#funcButtons").show(0);
		$("#transferHistoryTable").show(0);
		$("#blank_row").hide(0);
		}
	}

function loadParts() {
	$.ajax({
		url:"engine/ajax.php?op=filetransfer_loadparts&jname="+$("#code").val()+"&jtype=<?= $_GET["type"] ?>",
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#partTD").html( data[0] );
			$("#currentMag").html( data[1] );
			finished = data[2];
			finishedHandle();
			$("#part").change( function(){
				saveUploadSettings();
				});
			}
		});		
	}
loadParts();

var $form = $('.box');
$form.addClass('has-advanced-upload');

var $input    = $form.find('input[type="file"]'),
    $label    = $form.find('label'),
    showFiles = function(files) {
      $label.text(files.length > 1 ? ($input.attr('data-multiple-caption') || '').replace( '{count}', files.length ) : files[ 0 ].name);
    };

var files_data = new Array();
var files = '';
var default_inf = $('#info').html();
var accept = true;
var count = 0;

function checkUploadEligible() {
	if( $("#type").val() == "picture_pack" ) {
		if( files_data.length > 0 && $("#imgpack").val() != "" ) {
			$("#button_large5").removeClass("disabled");
			}
		else {
			$("#button_large5").addClass("disabled");
			}		
		}
	else {
		if( files_data.length > 0 ) {
			$("#button_large5").removeClass("disabled");
			}
		else {
			files_data = [];
			$("#button_large5").addClass("disabled");
			}		
		}
	}
checkUploadEligible();

function removeFromFileList( target ) {
	var new_fileData = new Array();
	if( target != undefined ) {
		delete files_data[ target ];
		
		$.each( files_data, function( i ) {
			if( files_data[i] != undefined ) {
				new_fileData.push( files_data[i] );
				}
			});
		
		files_data = new Array();
		files_data = new_fileData;
			
		files = '';
		for( var i = 0; i < files_data.length; i++ ) {
			files += addUploadRow( i, files_data[i].name );
			}
		if( files != '' ) {
			$('#info').html( files );
			}
		else {
			$('#info').html( default_inf );
			$('#info').removeClass('upload_fileList');
			$('#submit_files').fadeOut('fast');
			$('#realFile').val('');
			}
		if( files_data.length < 1 ) {	
			$('#anyagfeltoltes').css("display", "inline-block");
			$('#info').css("display", "none");
			}			
		}
		
	checkUploadEligible();	
	}

$("body").on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
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
	var length = e.originalEvent.dataTransfer.items.length;
	for (var i = 0; i < length; i++) {
		var entry = e.originalEvent.dataTransfer.items[i].webkitGetAsEntry();
		
		//D'n'D Fájl
		if (entry.isFile) {
			accept = true;
			for( var y = 0; y < files_data.length; y++ ) {
				if( e.originalEvent.dataTransfer.files[i].name == files_data[y].name ) {
					accept = false;
					}
				}
			
			if( accept ) {
				var temp = e.originalEvent.dataTransfer.files[i].name.split( "." );
				temp = temp[temp.length - 1].toUpperCase();
				
				if( display ) {
					if( jQuery.inArray( temp, filetype_array[ $("#type").val() ].ext ) !== -1 ) {
						var name = e.originalEvent.dataTransfer.files[i].name;
						files_data.push( e.originalEvent.dataTransfer.files[i] );
						count = parseInt(files_data.length)-1;
						files += addUploadRow( count, name );	
						}
					}
				else {
					var name = e.originalEvent.dataTransfer.files[i].name;
					files_data.push( e.originalEvent.dataTransfer.files[i] );
					count = parseInt(files_data.length)-1;
					files += addUploadRow( count, name );						
					}
				}
			}
		
		//D'n'D Könyvtár
		else if (entry.isDirectory) {
			var entry = e.originalEvent.dataTransfer.items[i].webkitGetAsEntry(); 
			
			}
			
		}
	
	if( files_data.length > 0 ) {	
		$('#info').addClass('upload_fileList');
		$('#info').html( files );
		$('#submit_files').fadeIn('fast');
		
		$('#info').css("display", "inline-block");
		$('#anyagfeltoltes').css("display", "none");
		}
	else {
		$('#anyagfeltoltes').css("display", "inline-block");
		$('#info').css("display", "none");
		}
		
	checkUploadEligible();		
	}); 	

function addUploadRow( count, name ) {
	if( display) {
		text = "<div fileid='"+count+"'><div id='"+count+"' ok='1' class='fileNameClass' style='float:left'><span class='fname'>"+name+"</span></div><div id='"+count+"' style='float:right'><div class='progressText' ok='1' fid='"+count+"'></div><div id='progressBarWrap' class='"+count+"'><div id='progressBar'></div><div id='progressPercent'></div></div><div class='fileremoveWrap'><div class='fileremoveBox'><div style='display: inline-block;'></div><i onclick='removeFromFileList("+count+")' class='fas fa-times'></i></div></div></div></div><div style='clear:both'></div>";
		}
	else {
		var text = "";
		$.ajax({
			url:"engine/ajax.php?op=checkFileuploadName&name="+name,
			type: "POST",
			dataType: 'json',
			async: false,
			success:function( data ) {
				//console.log( data );
				text = "<div fileid='"+count+"'><div id='"+count+"' ok='"+data[0]+"' class='fileNameClass' style='float:left'><span class='fname'>"+name+"</span></div><div id='"+count+"' style='float:right'><div class='progressText' ok='"+data[0]+"' fid='"+count+"'></div><div id='progressBarWrap' class='"+count+"'><div id='progressBar'></div><div id='progressPercent'></div></div><div class='fileremoveWrap'><div class='fileremoveBox'><div style='display: inline-block;'>"+data[1]+"</div><i onclick='removeFromFileList("+count+")' class='fas fa-times'></i></div></div></div></div><div style='clear:both'></div>";
				}
			});	
		}

	return text;
	}

$input.on('change', function(e) {
	//console.log( $('#file')[0].files );
	for( var i = 0; i < $('#file')[0].files.length; i++ ) {
		accept = true;
		for( var y = 0; y < files_data.length; y++ ) {
			if(  $('#file')[0].files[i].name == files_data[y].name ) {
				accept = false;
				}
			}
		
		if( accept ) {
			var temp =  $('#file')[0].files[i].name.split( "." );
			temp = temp[temp.length - 1].toUpperCase();
			
			if( dirupload ) {
				var name = $('#file')[0].files[i].name;
				files_data.push( $('#file')[0].files[i] );
				count = parseInt(files_data.length)-1;
				files += addUploadRow( count, name );
				}
			else {
				if( display ) {
					if( jQuery.inArray( temp, filetype_array[ $("#type").val() ].ext ) !== -1 ) {
						var name = $('#file')[0].files[i].name;
						files_data.push( $('#file')[0].files[i] );
						count = parseInt(files_data.length)-1;
						files += addUploadRow( count, name );
						}
					}
				else {
					var name = $('#file')[0].files[i].name;
					files_data.push( $('#file')[0].files[i] );
					count = parseInt(files_data.length)-1;
					files += addUploadRow( count, name );
					}
				}
			}
		}
	
	if( files_data.length > 0 ) {	
		$('#info').addClass('upload_fileList');
		$('#info').html( files );
		$('#submit_files').fadeIn('fast');
		
		$('#info').css("display", "inline-block");
		$('#anyagfeltoltes').css("display", "none");
		}
	else {
		$('#anyagfeltoltes').css("display", "inline-block");
		$('#info').css("display", "none");
		}
		
	checkUploadEligible();
	});

$form.on('submit', function(e) {
	if ($form.hasClass('is-uploading')) return false;
	$form.addClass('is-uploading').removeClass('is-error');

	e.preventDefault();
	
	$(".progressText").each(function() {
		if( $(this).attr("ok") == "1") {
			$(this).html("várakozás");
			$(this).show(0);
			}
		});
	//$(".progressText").html("várakozás");
	//$(".progressText").show(0);
	pre_check();
	});

var iBytesUploaded = 0;
var iBytesTotal = 0;
var iPreviousBytesLoaded = 0;
var iMaxFilesize = 1048576; // 1MB
var oTimer = 0;
var sResultFileSize = '';
var file_counter = 0;
var realfile_counter = 0;
var file_max = 0;
var oFile = '';
var progressBox = '';
var prev_counter = 0;
var size;
var num = 1;
var num_chunks;
var sliceSize = ( 1024 * 1024 * 10 ); // Send Chunks in MB
var file;
var start;
var ending;
var tempdir;
var currentXHR = null;
var uploadActive = false;
var uploadAborted = false;

// Navigating away (menu click, back button, tab close) is a full page
// unload here, not an SPA route change - it kills any in-flight XHR
// instantly with no warning. Ask for confirmation while a chunked upload
// is running, and if the user leaves anyway, tell the server via
// sendBeacon (survives unload, unlike a normal XHR) to delete the
// in-progress tempdir immediately instead of waiting up to 24h for
// cron/blob_cleaner.php to age it out.
window.addEventListener('beforeunload', function( e ) {
	if( uploadActive ) {
		e.preventDefault();
		e.returnValue = '';
		return '';
		}
	});

window.addEventListener('pagehide', function( e ) {
	if( uploadActive && tempdir ) {
		navigator.sendBeacon( 'engine/ajax.php?op=cancelupload&tempdir='+tempdir );
		}
	});

function fireUpload() {
	oFile = files_data[file_counter];
	file = oFile;
	size = file.size;
	iBytesTotal = size;
	start = 0;
	num = 1;
	tempdir = $.now();
	var ok = $("#"+file_counter ).attr("ok");
	
	if( ok == "0" ) {
		file_counter++;
		if( file_counter < file_max) { 		
			fireUpload();
			}
		else {
			finishedUpload();
			}		
		}
	else {
		num_chunks = Math.max(Math.ceil(size / sliceSize), 1);	
		$("#extInfo, #funcButtons").hide(0);
		$("#stopButton").show(0);
		progressBox = $("#progressBarWrap[class='"+file_counter+"']");
		progress(0);
		
		$(progressBox).show(0);
		prev_counter = file_counter;
		
		startUploading( file, 0, sliceSize );
		file_counter++;
		}
	}

function pre_check() {
	file_counter = 0;
	file_max = files_data.length;
	uploadActive = true;
	uploadAborted = false;

	fireUpload();
	}

function finishedUpload() {
	uploadActive = false;
	setTimeout(function() {
		location.reload();
		}, 5000);
	}

function stopUpload() {
	uploadAborted = true;
	uploadActive = false;

	if( currentXHR ) {
		currentXHR.abort();
		}

	if( tempdir ) {
		$.ajax({
			url: "engine/ajax.php?op=cancelupload&tempdir="+tempdir,
			type: "GET",
			dataType: 'json'
			});
		}

	setTimeout(function() {
		location.reload();
		}, 300);
	}

function startUploading( file, start, end ) {
	ending = end;
	var ajaxData = new FormData();
	var oXHR = new XMLHttpRequest();
	currentXHR = oXHR;
 	iBytesUploaded = start;
	console.log( num+", "+num_chunks );
    if (size - end < 0) { // Uses a closure on size here you could pass this as a param
        end = size;
    }

    if (end < size) {
        oXHR.onreadystatechange = function () {
            if (oXHR.readyState == XMLHttpRequest.DONE) {
				if( uploadAborted ) {
					return;
					}
				num++;
                startUploading( file, start + sliceSize, start + (sliceSize * 2))
            }
        }
    }

	oXHR.open('POST', 'engine/fileupload_ajax.php', true );
	oXHR.upload.addEventListener('progress', uploadProgress, false);
	if( num == num_chunks ) {
		oXHR.addEventListener('load', uploadFinish, false);
		realfile_counter++;
		}
	
	var slicedPart = slice(file, start, end);
	
	if( display ) {
		ajaxData.append( "type", $("#type").val() );
		ajaxData.append( "color", $(".color").val() );
		ajaxData.append( 'part', $("#part").val() );
		ajaxData.append( 'jobid', $("#code").val() );
		ajaxData.append( 'imgpack', $("#imgpack").val() );
		ajaxData.append( 'current', realfile_counter );
		ajaxData.append( 'max', file_max );
		}
	else {
		ajaxData.append( "type", "off" );
		ajaxData.append( "color", "off" );
		ajaxData.append( 'part', "off" );		
		ajaxData.append( 'jobid', "off" );
		}
	ajaxData.append( 'jtype', "<?= $_GET["type"] ?>" );
    ajaxData.append( 'tempdir', tempdir );
    ajaxData.append( 'num', num );
	ajaxData.append( 'num_chunks', num_chunks );
	ajaxData.append( 'file', slicedPart, file.name );
  	
	$(".progressText[fid='"+prev_counter+"']").hide(0);
  	$(".fileremoveWrap").hide(0);
	
    oXHR.send(ajaxData);
	}

function uploadProgress(e) { // upload process in progress
    if (e.lengthComputable) {
		//console.log( "chunk feltoltve: "+e.loaded+", chunk max: "+e.total );
        var comp = iBytesUploaded + e.loaded;
		//console.log( "feltoltve ossz: "+comp );
		//console.log( "teljes: "+iBytesTotal );
        var iPercentComplete = Math.round(comp * 100 / iBytesTotal);
		//console.log( iPercentComplete+"%" );
		
		if( iPercentComplete.toString() >= '99' || iPercentComplete.toString() >= 99 ) {
			progress( "100" );
			}
		else {
			progress( iPercentComplete );
			}
       // oProgress.css( 'width', (iPercentComplete).toString() + '%');
       // document.getElementById('b_transfered').innerHTML = iBytesTransfered;
        if (iPercentComplete == 100) {
           // var oUploadResponse = document.getElementById('upload_response');
        }
    }
}

function slice(file, start, end) {
  var slice = file.mozSlice ? file.mozSlice :
              file.webkitSlice ? file.webkitSlice :
              file.slice ? file.slice : noop;
  
  return slice.bind(file)(start, end);
}

function bytesToSize(bytes) {
    var sizes = ['Bytes', 'KB', 'MB'];
    if (bytes == 0) return 'n/a';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
	}

var progress = function(perc) {
    //perc = Math.round(perc * 100) / 100; // 2 decimal places
	
	$bar = $(progressBox).find("#progressBar");
	$bar.css("width", perc+"%");
	}

function uploadFinish(e) {
	if( uploadAborted ) {
		return;
		}
	$(".progressText[fid='"+prev_counter+"']").css("color", "rgb( 25, 141, 62 )" ).html("sikeres");
	$(".progressText[fid='"+prev_counter+"']").show(0);
	$("#progressBarWrap[class='"+prev_counter+"']").hide(0);
	if( file_counter < file_max) { 		
		fireUpload();
		}
	else {
		finishedUpload();
		}
	}

var click1Done = false;
$("#code").on('click', function(e) {
	console.log( e.target );
	console.log( "click-be: "+click1Done );
	if( click1Done ) {
		click1Done = false;
		$("#code").blur();
		
		}
	else {
		click1Done = true;
		}
    });

function focus() {
  [].forEach.call(this.options, function(o) {
    o.textContent = o.getAttribute('text-value') + '' + o.getAttribute('long-value') + '';
  });
}
function blur() {
  [].forEach.call(this.options, function(o) {
    o.textContent = o.getAttribute('text-value');
  });
  
  //click1Done = false;
	console.log( "blur: "+click1Done );
}
[].forEach.call(document.querySelectorAll('#code'), function(s) {
  s.addEventListener('focus', focus);
  s.addEventListener('blur', blur);
  blur.call(s);
});
	
</script>