// common variables
var iBytesUploaded = 0;
var iBytesTotal = 0;
var iPreviousBytesLoaded = 0;
var iMaxFilesize = 1048576; // 1MB
var oTimer = 0;
var sResultFileSize = '';

function findSize() {
     var fileInput = $("#file")[0];
  	 var f = fileInput.files[0];
     var size = f.size||f.fileSize; // Size returned in bytes.
     var size = (parseInt(size)/1000/1000).toFixed(1);
     
	 return size;
}   

function pre_check() {
	$( "#msg_box" ).hide('fast');
	var blacklist = [ '"', "'" ];
	
	$("#ad_sender").hide('fast');
	$('#progress_info').show('fast');
	
	$('#ad_sender').prop('disabled', true);
	startUploading();
	}

function startUploading() {
    // cleanup all temp states
    iPreviousBytesLoaded = 0;
    document.getElementById('progress_percent').innerHTML = '';
    document.getElementById('progress_info').style.display = 'block';
    var oProgress = document.getElementById('progress');
    oProgress.style.display = 'block';
    oProgress.style.width = '0px';

    // get form data for POSTing
    //var vFD = document.getElementById('upload').getFormData(); // for FF3
    var vFD = new FormData(document.getElementById('ad_upload')); 

    // create XMLHttpRequest object, adding few event listeners, and POSTing our data
    var oXHR = new XMLHttpRequest();        
    oXHR.upload.addEventListener('progress', uploadProgress, false);
    oXHR.addEventListener('load', uploadFinish, false);
    oXHR.addEventListener('error', uploadError, false);
    oXHR.addEventListener('abort', uploadAbort, false);
    oXHR.open('POST', 'ad_upload.php');
    oXHR.send(vFD);

    // set inner timer
    oTimer = setInterval(doInnerUpdates, 300);
}

function doInnerUpdates() { // we will use this function to display upload speed
    var iCB = iBytesUploaded;
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
    }
}

function uploadProgress(e) { // upload process in progress
    if (e.lengthComputable) {
        iBytesUploaded = e.loaded;
        iBytesTotal = e.total;
        var iPercentComplete = Math.round(e.loaded * 100 / e.total);

        document.getElementById('progress').style.width = (iPercentComplete).toString() + '%';
    }
}

// FELTÖLTÉS KÉSZ

function uploadFinish(e) { // upload successfully finished
	var response = e.target.responseText.split('-');
    document.getElementById('progress_percent').innerHTML = '100%';
    document.getElementById('progress').style.width = '100%';
    if( response[1] == '0' ) {
		alert( response[0] );
		}
		
	$('#progress_info').hide('fast');
	$("#ad_sender").show('fast');
	if( response[1] == 1 ) {
		$("#msg_box").attr("class", "success");
		}
	else {
		$("#msg_box").attr("class", "failed");
		}
    $("#msg_box").html( response[0] );
    $( "#msg_box" ).show('slow');
   
   	$('#job_code').val('');
   	$("#file").val("");
   	// .val("") on a file input doesn't fire "change", so #fileSelected's
   	// filename label and #fileLabel's visibility were left showing the
   	// just-uploaded file instead of resetting to the "no file chosen"
   	// state the change handler's else-branch produces - the form looked
   	// like it still had the old file selected even though the input was
   	// actually empty.
   	$("#fileSelected").html( "" );
   	$("#fileSelected").removeAttr("title");
   	$("#fileLabel").show(0);
   	$("#fileSelected").hide(0);
   	// #sizer has to be cleared before keyup() runs the enable/disable
   	// check below - it reads #sizer's current value, so clearing it
   	// afterward meant the check still saw the just-uploaded size and
   	// left Upload enabled on an otherwise-empty form.
    $("#sizer").val('');
    $('#job_code').keyup();
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