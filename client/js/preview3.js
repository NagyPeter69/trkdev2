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

var zoomDebounceTimer = null;

function _zoom( opt, source, size ) {
	if( !disableZoom ) {
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

		// Mouse-wheel zoom (source=='roll', no explicit target size) fires
		// this on every single wheel tick with no coalescing - scrolling
		// fast used to queue up one full render (~0.5-3s each, before the
		// tesztAjax.php render cache) per accepted tick, even though only
		// the FINAL zoom level the user settles on actually matters.
		// Debounce just that case; a slider release or any other
		// explicit zoom-to-value call (size is set) still fires
		// immediately, since that's already one deliberate "I'm done"
		// action, not a rapid burst.
		clearTimeout( zoomDebounceTimer );
		if( source == 'roll' && size == undefined ) {
			zoomDebounceTimer = setTimeout( function() {
				disableZoom = true;
				$("#boxDraw").html("");
				placeBox( 'force', source );
				}, 180 );
			}
		else {
			disableZoom = true;
			$("#boxDraw").html("");
			placeBox( 'force', source );
			}
		}
	}