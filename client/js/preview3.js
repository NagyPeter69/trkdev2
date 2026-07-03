function moveObject3(event) {
	moveObject2(event);
	}
	
function moveObject2(event) {
    var delta = 0;
 	
    if (!event) event = window.event;
    if (event.wheelDelta) {
        delta = event.wheelDelta / 60;

    } else if (event.detail) {
        delta = -event.detail / 2;
   		}
		
	if( event.clientX > $("#fpPages").outerWidth(true) ) {	
		if( delta > 0  ) {
			_zoom( '+', 'roll' );
			}
		else {
			_zoom( '-', 'roll' );
			}
		}
	}
var image_pos = 'allo';
var image_size = { 'height': 4000 };

function _zoom( opt, source, size ) {
	if( !disableZoom ) {
		disableZoom = true;
		if( size == undefined ) {
			switch( opt ) {
				case '+':
					oldZoom = zoom;
					zoom = parseInt( zoom+( zoom/100*45 ) );
					if( zoom > 1500 ) zoom = 1500;
					break;
	  
				case '-':
					oldZoom = zoom;
					zoom = parseInt( zoom-( zoom/100*45 ) );
					break;
				}
      		}
    	else {
    		oldZoom = zoom;
      		zoom = size;
      		}
      	$("#boxDraw").html("");
		placeBox( 'force', source );
		}
	}