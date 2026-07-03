<!DOCTYPE HTML>

<head>
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
</head>

<body>
<script>
	
	
var FUPercent = "";

progressHandling = function (event) {
    var percent = 0;
    var position = event.loaded || event.position;
    var total = event.total;
    var progress_bar_id = "#progress-wrp";
    if (event.lengthComputable) {
        percent = Math.ceil(position / total * 100);
    	}
    
    FUPercent = percent;
    $(".fup-bar", window.parent.frames[1].document ).css( "width", percent+"%");
    $(".fup-percent", window.parent.frames[1].document ).html( percent+"%");
	}	

var activeFUpload = false;
var fileVal = "";
var currentPlannerPubID = "";
var currentPlannerID = "";
var currentArticle = "";

function fileUpload() {
	if( fileVal != "" ) {
		$(".fp-up-box", window.parent.frames[1].document ).css("visibility", "visible");
		activeFUpload = true;
	
		$("#select-file", window.parent.frames[1].document ).hide(0);
		
		$("#selected-file", window.parent.frames[1].document ).html( "Uploading file: "+fileVal );
		$("#selected-file", window.parent.frames[1].document ).show(0);	
	
		formData = new FormData();
		formData.append('0', $('#afile', window.parent.frames[1].document )[0].files[0]);
		formData.append( "pubid", currentPlannerPubID );
		formData.append( "article", currentArticle );	
		
		$.ajax({
			url: 'engine/fileupload.php?op=uploaded',
			type: 'POST',
			data: formData,
			cache: false,
			contentType: false,
			enctype: 'multipart/form-data',
	        xhr: function() {
	            var myXhr = $.ajaxSettings.xhr();
				if (myXhr.upload) {
					myXhr.upload.addEventListener('progress', progressHandling, false);
				    }
	            return myXhr;
	    		},
			processData: false,
			success: function (response) {
				activeFUpload = false;
				
				fileVal = "";
				$('#afile', window.parent.frames[1].document ).val(null);
				$("#select-file", window.parent.frames[1].document ).show(0);
				$("#selected-file", window.parent.frames[1].document ).hide(0);
				$(".fp-up-box", window.parent.frames[1].document ).css("visibility", "hidden");
				
				window.parent.frames[1].loadUploadedFiles( currentPlannerID );
				window.parent.frames[1].currentFile();
				}
			});
		}	
	}

</script>
</body>