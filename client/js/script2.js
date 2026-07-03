// common variables
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
var loading = '';

function findSize() {
  	 var f = oFile;
     var size = f.size||f.fileSize; // Size returned in bytes.
     var size = (parseInt(size)/1000/1000).toFixed(1);
     
	 return size;
}   

function fireUpload() {
	oFile = files_data[file_counter];
	console.log( oFile );

 	startUploading();
	file_counter++;
	}

function pre_check( load ) {
	loading = load;
	$('#thumbs').html( '' );
	$('#thumbs_load').html( '' );
	var blacklist = [ '"', "'" ];
	var tarea = false;
	if ( tarea ) {
		$("#msg_box").attr("class", "msg_box msg_error");
		$("#msg_box").html( lang[5] );
		$( "#msg_box" ).fadeTo(0, 1);		
		}
	else {
		file_counter = 0;
		file_max = files_data.length;
	
		$('#button_large3').addClass('disabled');
		$( "#button_large3" ).attr("onClick","");
		
		$('#remover').addClass('disabled');
		$( "#remover" ).attr("onClick","");
		
		fireUpload();
		}
	}

function startUploading() {
    // cleanup all temp states
    iPreviousBytesLoaded = 0;
    percent = $("#progress_percent[class='"+(file_counter+1)+"']");
    p_info = $("#progress_info2[class='"+(file_counter+1)+"']");
    
    percent.html('');
   	p_info.css('display', 'block');
    oProgress = $("#progress[class='"+(file_counter+1)+"']");
    oProgress.hide('fast');
	p_info.css('display', 'block');
    oProgress.css('width','0px');

    // get form data for POSTing
    //var vFD = document.getElementById('upload').getFormData(); // for FF3
    var vFD = new FormData(document.getElementById('newprocess'));
	vFD.append( 'file', oFile );
	vFD.append( 'code', jcode );
	
	setTimeout(function() {
    // create XMLHttpRequest object, adding few event listeners, and POSTing our data
	    var oXHR = new XMLHttpRequest();        
    	oXHR.upload.addEventListener('progress', uploadProgress, false);
	    oXHR.addEventListener('load', uploadFinish, false);
    	oXHR.open('POST', 'ad_hoc_upload.php');
	    oXHR.send(vFD);
		console.log( oFile );
    // set inner timer
    	oTimer = setInterval(doInnerUpdates, 300);
    	}, 400);
}

function doInnerUpdates() { // we will use this function to display upload speed
 /*   var iCB = iBytesUploaded;
    var iDiff = iCB - iPreviousBytesLoaded;

    // if nothing new loaded - exit
    if (iDiff == 0)
        return;

    iPreviousBytesLoaded = iCB;
    iDiff = iDiff * 2;
    var iBytesRem = iBytesTotal - iPreviousBytesLoaded;
    var secondsRemaining = iBytesRem / iDiff;

    // update speed info
    var iSpeed = iDiff.toString() + 'B/s';
    if (iDiff > 1024 * 1024) {
        iSpeed = (Math.round(iDiff * 100/(1024*1024))/100).toString() + 'MB/s';
    } else if (iDiff > 1024) {
        iSpeed =  (Math.round(iDiff * 100/1024)/100).toString() + 'KB/s';
    }*/
}

function uploadProgress(e) { // upload process in progress
    if (e.lengthComputable) {
        iBytesUploaded = e.loaded;
        iBytesTotal = e.total;
        var iPercentComplete = Math.round(e.loaded * 100 / e.total);
		
		var R = 227;
		var G = 0
		var B = 11;
		
		R = Math.round( ( 82 - R )/100*iPercentComplete.toString()+R );
		G = Math.round( 192/100*iPercentComplete.toString() );
		B = Math.round( ( 47 - B )/100*iPercentComplete.toString()+B );
		
		percent.css("color", "rgb( "+R+", "+G+", "+B+" )");
        percent.html( iPercentComplete.toString() + '%' );
    }
}

// FELTÖLTÉS KÉSZ

function uploadFinish(e) { // upload successfully finished
	var response = e.target.responseText.split('-');
	
    percent.html('100%') ;
   // document.getElementById('progress').style.width = '100%';
	
	if( response[1] == 1 ) {
		$("#div[id='"+(file_counter+1)+"']").css("color", "rgb( 82, 192, 47 )");
		percent.css("color", "rgb( 82, 192, 47 )");
		percent.html( response[0] );
		}
	if( response[1] == 0 ) {
		$("#div[id='"+(file_counter+1)+"']").css("color", "rgb( 227, 0, 11 )");
		percent.css("color", "rgb( 227, 0, 11 )");
		percent.html( response[0] );
		}
		
	if( file_counter < file_max) { fireUpload(); }
	else {
		if( a_type == 'proof' ) {
			$('#button_large3').removeClass('disabled');
			$( "#button_large3" ).attr("onClick","pre_check()");
			$('#remover').removeClass('disabled');
			$( "#remover" ).attr("onClick","removeFromFileList()");
		
			if( response[1] == 1 ) {
				$('#info').removeClass('upload_fileList');
				$('#info').html( "" );
				$('#info_footer').html( "" );
				$('#thumbs_load').html( loading+' <img src="images/ajax-loader.gif">' );
				count = 0;
				files = '';
				files_data = new Array();
				
				$.ajax	({
					url:"engine/ajax.php",
					data: 'op=load_thumbnails&file='+response[0]+'&page=all',
					dataType: 'json',
					success:function( data ) {
						$('#thumbs_load').html( '' );
						
						if( $('#thumbs').length == 0 )
							var height = $(window).height()-parseInt( $(".ad_menu_footer_content").height() )-parseInt( $("#header").height() )-parseInt( $(".ad_menu_footer").height() );
						else 
							var height = $(window).height()+parseInt( $("#thumbs").height() )-parseInt( $(".ad_menu_footer_content").height() )-parseInt( $("#header").height() )-parseInt( $("#newJobTitle").height() );

						$('#thumbs').css("max-height", height+"px");
						
						$('#thumbs').html( data );
						$('#actual_file').html( response[0] );
						$('#s_all').html( "<input type='checkbox' id='selectall'>&nbsp;<span id='s_all_txt'>Összes kijelölése</span>" );

						$('#selectall').click(function () {
							if( $("#selectall").is(':checked') ) {
								var checked_status = true;
								} 
							else {
								var checked_status = false;
								}
							//var checked_status = !$(".p_page").prop('checked');
							//console.log( checked_status );
							$('.p_page').not("[disabled]").each(function () {
								$(this).prop('checked', checked_status ); 		
								});
							$('#ad_hoc_settings :input').keyup();
							}); 
						$('.p_page').click(function () {
							if( $('.p_page:checked').length < $('.p_page').length ) {
								$('#selectall').prop('checked', false ); 
								}
							if( $('.p_page:checked').length == $('.p_page').length ) {
								$('#selectall').prop('checked', true ); 
								}
							$('#ad_hoc_settings :input').keyup();
							});
						$('#selectall').prop('checked', true ); 
						$('.p_page').each(function () {
							$(this).prop('checked', true ); 
							});
						$('input').click(function(){
							buttonValidator();
							});
						$('input[type=checkbox]').click();
						}
					});
				}
			else {
				$('#info').removeClass('upload_fileList');
				$('#info').html( "" );
				$('#info_footer').html( "" );
				count = 0;
				files = '';
				files_data = new Array();
				$('#realFile').val('');
				
				$('#thumbs_load').html( response[0] );
				}
			}
		else {
			$('#info').removeClass('upload_fileList');
			$('#info').html( "" );
			$('#info_footer').html( "" );
			count = 0;
			files = '';
			files_data = new Array();
			$('#realFile').val('');
			
			$('#thumbs_load').html( loading );
			$('#ad_hoc_settings :input').keyup();
			GenerateJobCode();
			}
		}
    clearInterval(oTimer);
}

function uploadError(e) { // upload error
  //  document.getElementById('error2').style.display = 'block';
    clearInterval(oTimer);
}  

function uploadAbort(e) { // upload abort
    //document.getElementById('abort').style.display = 'block';
    clearInterval(oTimer);
}