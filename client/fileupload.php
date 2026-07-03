<head>
 	<meta charset="UTF-8">
	<meta http-equiv="cache-control" content="max-age=0" />
	<meta http-equiv="cache-control" content="no-cache" />
	<meta http-equiv="expires" content="0" />
	<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
	<meta http-equiv="pragma" content="no-cache" />
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

	<link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	
	<link rel="icon" href="favicon.ico" type="image/x-icon" />
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" href="css/jquery-ui.css">
	<link href="css/flatplan.css" rel="stylesheet" type="text/css" />
	<link href="css/main3.css" rel="stylesheet" type="text/css" />
	<link href="css/load_bar.css" rel="stylesheet" type="text/css" />
	<link href="../css/default.php" rel="stylesheet" type="text/css" />
	<link href="../css/menu.php "rel="stylesheet" type="text/css" />
	<link href="css/client.css" rel="stylesheet" type="text/css" />
		
	<meta name="msapplication-TileColor" content="#ffffff">
	<meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
	<meta name="theme-color" content="#ffffff">
 
    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.flip.js"></script>
    <script src="../js/jquery.tipTip.js"></script>
    <script src="../js/jquery.tipTip2.js"></script>    
    <script src="js/jquery.ui.touch-punch.min.js"></script>
    <script src="../engine/engine.js"></script>
    <script src="js/script.js"></script>
    <script src="js/jquery.transit.min.js"></script>
    <script src="js/hammer.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>

	<style>
#info_footer {
	padding-top: 20px;
	}

.footer_left {
	float: left;
	height: 28px;
	padding-top: 5px;
	padding-left: 1px;
	}

.button_delete {
	text-align: center;
	width: 90px;
	float: right;
	height: 28px;
	color: #fff;
	background: rgb( 227, 0, 11 );
	border: 1px solid rgb( 84, 18 , 19 );
	font-size: 20px;
	font-family: otp;
	padding-top: 5px;
   	-webkit-transition: all 0.22s ease-out;
   	-moz-transition:    all 0.22s ease-out;
   	-ms-transition:     all 0.22s ease-out;
   	-o-transition:      all 0.22s ease-out;
   	cursor: pointer;
   	font-weight: none;
	}

.button_delete:hover {
	font-family: otp;
	background: rgb(207, 0, 11);
	}

#button_large2:hover, #button_large3:hover, #button_large4:hover, #button_large5:hover {
	font-family: otp;
	background: rgb(22, 109, 55);
	}

.disabled {
	background: #777 !important;
	color: #AAA !important;
	}
	
#button_large3, #button_large5 {
	text-align: center;
	width: 180px;
	height:28px;
	color: #fff;
	background: rgb( 80, 174, 47 );
	border: 1px solid rgb( 0, 0 , 0 );
	font-size: 20px;
	font-family: otp;
	padding-top: 5px;
   	-webkit-transition: all 0.22s ease-out;
   	-moz-transition:    all 0.22s ease-out;
   	-ms-transition:     all 0.22s ease-out;
   	-o-transition:      all 0.22s ease-out;
   	cursor: pointer;
   	font-weight: none;
	}	
	</style>

</head>

<?php
include_once('../engine/connect.php');	
include_once('../engine/engine.php');
include_once('../engine/fileClass.php');

$uploader = new file;

echo $uploader->getSelectList( "filetype", "type" );
echo $uploader->getSelectList( "colorprofile", "color" );

?>

<div>
	Elfogadott fájlok: <span id="accepted_ext"></span>
</div>

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

<div id="info" class="">
	<?= $lang['uploads']['info'] ?>
</div>
<div id="info_footer">
</div>
<div id="button_large3" onClick='$(".box").submit()'>Feltöltés</div>

<script>
var filetype_array = {
	"indesign_pack": [ "ZIP" ],
	"indesign_file": [ "ZIP", "INDD" ],
	"pdf": [ "PDF" ],
	"picture": [ "TIFF", "JPEG", "PSD" ],
	}


$("#type").change( function() {
	accepted_ext();
	
	var temp = $("div[fileid]");
	$("div[fileid]").each(function(){
		var name = $(this).find(".fname").html();
		name = name.split( "." );
		var ext = name[name.length - 1].toUpperCase();
		
		if( jQuery.inArray( ext, filetype_array[ $("#type").val() ] ) == -1 ) {
			$(this).find("#del_select").prop( "checked", true );
			}
		});
	removeFromFileList();
	});

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

function removeFromFileList() {
	var new_fileData = new Array();
	if( $('#del_select:checked').length > 0 ) {
		$('#del_select:checked').each(function(){
			delete files_data[$(this).val()];
			});
		
		$.each( files_data, function( i ) {
			if( files_data[i] != undefined ) {
				new_fileData.push( files_data[i] );
				}
			});
		
		files_data = new Array();
		files_data = new_fileData;
			
		files = '';
		for( var i = 0; i < files_data.length; i++ ) {
			files += "<div fileid='"+i+"'><div id='"+count+"' style='float:left'><input id='del_select' type='checkbox' name='del_select' value='"+i+"'>"+files_data[i].name.substring( 0 , parseInt( files_data[i].name.length-4 ) )+"</div>";
			files += '<div id="progress_info" class="'+i+'"><div id="progress" class="'+i+'"></div><div id="progress_percent" class="'+i+'"></div></div></div><div style="clear:both"></div>';
			}
		if( files != '' ) {
			$('#info').html( files );
			}
		else {
			$('#info').html( default_inf );
			$('#info').removeClass('upload_fileList');
			$('#info_footer').html('');
			$('#submit_files').fadeOut('fast');
			$('#realFile').val('');
			}
		}
	}

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
				
				if( jQuery.inArray( temp, filetype_array[ $("#type").val() ] ) !== -1 ) {
					var name = e.originalEvent.dataTransfer.files[i].name;
					files_data.push( e.originalEvent.dataTransfer.files[i] );
					count = parseInt(files_data.length)-1;
					files += "<div fileid='"+count+"'><div id='"+count+"' style='float:left'><input id='del_select' type='checkbox' name='del_select' value='"+count+"'><span class='fname'>"+name+"</span></div>";
					files += '<div id="progress_info" class="'+count+'"><div id="progress" class="'+count+'"></div><div id="progress_percent" class="'+count+'"></div></div></div><div style="clear:both"></div>';	
					}
				}
			}
		
		//D'n'D Könyvtár
		else if (entry.isDirectory) {
			var entry = e.originalEvent.dataTransfer.items[i].webkitGetAsEntry(); 
			/*

			accept = true;
			for( var y = 0; y < files_data.length; y++ ) {
				if( entry.name == files_data[y].name ) {
					accept = false;
					}
				}
				
			if( accept ) {
				var name = e.originalEvent.dataTransfer.files[i].name;
				files_data.push( e.originalEvent.dataTransfer.items[i] );
				count = parseInt(files_data.length)-1;
				files += "<div fileid='"+count+"'><div id='"+count+"' style='float:left'><input id='del_select' type='checkbox' name='del_select' value='"+count+"'>"+name+"</div>";
				files += '<div id="progress_info" class="'+count+'"><div id="progress" class="'+count+'"></div><div id="progress_percent" class="'+count+'"></div></div></div><div style="clear:both"></div>';	
				}
			*/				
			}
			
		}
	
	if( files_data.length > 0 ) {	
		$('#info').addClass('upload_fileList');
		$('#info').html( files );
		var footer = "<div class='footer_left'><?= $lang['uploads']['del_info'] ?></div><div class='footer-right'><div id='remover' onclick='removeFromFileList()' class='button_delete'>Törlés</div></div>";
		$('#submit_files').fadeIn('fast');
		$('#info_footer').html( footer );
		}		
	}); 	

$input.on('change', function(e) {
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
				
			if( jQuery.inArray( temp, filetype_array[ $("#type").val() ] ) !== -1 ) {
				var name = $('#file')[0].files[i].name;
				files_data.push( $('#file')[0].files[i] );
				count = parseInt(files_data.length)-1;
				files += "<div fileid='"+count+"'><div id='"+count+"' style='float:left'><input id='del_select' type='checkbox' name='del_select' value='"+count+"'><span class='fname'>"+name+"</span></div>";
				files += '<div id="progress_info" class="'+count+'"><div id="progress" class="'+count+'"></div><div id="progress_percent" class="'+count+'"></div></div></div><div style="clear:both"></div>';
				}
			}
		}
	
	if( files_data.length > 0 ) {	
		$('#info').addClass('upload_fileList');
		$('#info').html( files );
		var footer = "<div class='footer_left'><?= $lang['uploads']['del_info'] ?></div><div class='footer-right'><div id='remover' onclick='removeFromFileList()' class='button_delete'>Törlés</div></div>";
		$('#submit_files').fadeIn('fast');
		$('#info_footer').html( footer );
		}
	});

$form.on('submit', function(e) {
	if ($form.hasClass('is-uploading')) return false;
	$form.addClass('is-uploading').removeClass('is-error');

	e.preventDefault();
	pre_check();
	});

var iBytesUploaded = 0;
var iBytesTotal = 0;
var iPreviousBytesLoaded = 0;
var iMaxFilesize = 1048576; // 1MB
var oTimer = 0;
var sResultFileSize = '';
var file_counter = 0;
var file_max = 0;
var oFile = '';
var percent = '';
var p_info = '';
var oProgress = '';

function bytesToSize(bytes) {
    var sizes = ['Bytes', 'KB', 'MB'];
    if (bytes == 0) return 'n/a';
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
	}

function fireUpload() {
	oFile = files_data[file_counter];
	
 	startUploading();
	file_counter++;
	}

function pre_check() {
	file_counter = 0;
	file_max = files_data.length;
	
	fireUpload();
	}

function startUploading() {
    iPreviousBytesLoaded = 0;
    percent = $("#progress_percent[class='"+file_counter+"']");
    p_info = $("#progress_info[class='"+file_counter+"']");
    
    percent.html('<img src="images/ajax-loader.gif">');
   	p_info.css('display', 'block');
    oProgress = $("#progress[class='"+file_counter+"']");
    oProgress.hide('fast');
	p_info.css('display', 'block');
    oProgress.css('width','0px');
    
	var ajaxData = new FormData($form.get(0));
	ajaxData.append( "type", $(".type").val() );
	ajaxData.append( "color", $(".color").val() );
	ajaxData.append( 'file', oFile );
  	
    var oXHR = new XMLHttpRequest();        
    oXHR.upload.addEventListener('progress', uploadProgress, false);
    oXHR.addEventListener('load', uploadFinish, false);
    oXHR.open('POST', 'engine/fileupload_ajax.php');
    oXHR.send(ajaxData);
	}

function uploadProgress(e) { // upload process in progress
    if (e.lengthComputable) {
        iBytesUploaded = e.loaded;
        iBytesTotal = e.total;
        var iPercentComplete = Math.round(e.loaded * 100 / e.total);
        var iBytesTransfered = bytesToSize(iBytesUploaded);
		
		if( iPercentComplete.toString() >= '99' || iPercentComplete.toString() >= 99 ) {
			percent.html( '100%' );
			}
		else {
			percent.html( iPercentComplete.toString() + '%' );
			}
       // oProgress.css( 'width', (iPercentComplete).toString() + '%');
       // document.getElementById('b_transfered').innerHTML = iBytesTransfered;
        if (iPercentComplete == 100) {
           // var oUploadResponse = document.getElementById('upload_response');
        }
    }
}

function uploadFinish(e) {
	if( file_counter < file_max) { fireUpload(); }
	}

function accepted_ext() {
	$("#accepted_ext").html( filetype_array[ $("#type").val() ].join(", ") );
	}
accepted_ext();
	
</script>