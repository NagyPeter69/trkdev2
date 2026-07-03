<html>
	<head>
	    <script src="js/jquery-1.10.2.js"></script>
    <script src="js/jquery-ui.js"></script>
    <script src="js/jquery.flip.js"></script>
    <script src="../js/jquery.tipTip.js"></script>
    <script src="../js/jquery.tipTip2.js"></script>    
    <script src="js/jquery.ui.touch-punch.min.js"></script>
    <script src="../engine/engine.js"></script>
    <script src="js/jquery.transit.min.js"></script>
    <script src="js/hammer.min.js"></script>
	<script type="text/javascript" src="js/jquery-ui-timepicker-addon.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
	</head>
	
<body>

<input type="file" name="file" id="file">
<br><br>
<div>Feltöltve: <span id="process"></span></div>

<script>
(function() {
var f = document.getElementById('file');

if (f.files.length)
  processFile();

f.addEventListener('change', processFile, false);

var size;
var num = 1;
var num_chunks;
var sliceSize = ( 1024 * 1024 * 10 ); // Send Chunks in MB
var file;
var start;
var ending;
var iBytesUploaded = 0;
var iBytesTotal = 0;
var iPreviousBytesLoaded = 0;
var tempdir;

function processFile(e) {
	file = f.files[0];
	size = file.size;
	iBytesTotal = size;
	start = 0;
	tempdir = $.now();
	console.log( "tempdir: "+tempdir );
	
	num_chunks = Math.max(Math.ceil(size / sliceSize), 1);	
	//console.log('Sending File of Size: ' + size);
	send(file, 0, sliceSize);
}


function send(file, start, end) {
	ending = end;
    var formdata = new FormData();
    var xhr = new XMLHttpRequest();
	iBytesUploaded = start;
	console.log( "eddig feltöltve: "+iBytesUploaded );
    if (size - end < 0) { // Uses a closure on size here you could pass this as a param
        end = size;
    }
	console.log( end+" < "+size );
    if (end < size) {
        xhr.onreadystatechange = function () {
			console.log( xhr.readyState );
            if (xhr.readyState == XMLHttpRequest.DONE) {
                console.log('Done Sending Chunk');
				num++;
				console.log( num+" , "+num_chunks );
                send(file, start + sliceSize, start + (sliceSize * 2))
            }
        }
    }
	
    xhr.open('POST', 'fetchupload.php', true);
	xhr.upload.addEventListener('progress', uploadProgress, false);
	if( num == num_chunks ) {
		xhr.onreadystatechange = function() {
			if (xhr.readyState == XMLHttpRequest.DONE) {
				console.log(xhr.responseText);
				$("#process").html( "Fájl felkerült a helyére, további teendő nincs! <span style='color: red; cursor: pointer;' onclick='window.open(\"https://trkdev.colorcom.hu/client/"+xhr.responseText+"\")'>[Katt ide]</span>" );
				$("#file").val(null);
				}
			}
		}
    var slicedPart = slice(file, start, end);
	console.log( slicedPart );

    formdata.append('tempdir', tempdir );
    formdata.append('num', num);
	formdata.append('num_chunks', num_chunks );
    formdata.append('file', slicedPart, file.name );
    console.log('Sending Chunk (Start - End): ' + start + ' ' + end);

    xhr.send(formdata);
}
/**
 * Formalize file.slice
 */

function uploadProgress(e) { // upload process in progress
    if (e.lengthComputable) {	
		var comp = iBytesUploaded + e.loaded;
		//console.log( iBytesUploaded+" => "+iBytesTotal );
        var iPercentComplete = Math.round(comp * 100 / iBytesTotal);
		
		if( iPercentComplete.toString() >= '99' || iPercentComplete.toString() >= 99 ) {
			$("#process").html( "100%" );
			}
		else {
			$("#process").html( iPercentComplete+"%" );
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

})();
</script>

</body>

</html>