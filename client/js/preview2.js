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
	
	if( delta > 0 ) {
		zoom( '#pagePreview', '+', 'flatplan' );
		}
	else {
		zoom( '#pagePreview', '-', 'flatplan' );
		}
	
	}
var image_pos = 'allo';
var image_size = { 'height': 4000 };

function zoom( id, opt, alter ) {
	var percent2 = 0;
	var new_size = 0;
	
	if( !disable ) {
		if( image_pos == 'allo' ) {
			var size = $( id ).css('height');
			if( opt == '+' ) {
				new_size = parseInt( parseInt(size)+( parseInt(size)*0.2) );
				percent2 = new_size/parseInt( image_size['height'] )*130;
				if( parseInt( percent2 ) < 100 ) { size = new_size; }
				}
			if( opt == '-' ) {
				new_size = parseInt( parseInt(size)-( parseInt(size)*0.2) );
				percent2 = new_size/parseInt( image_size['height'] )*130;
				if( new_size > minHeight )
					size = new_size;
				else
					size = minHeight;
				}	
		
		
			$( id ).css( 'height', size+'px' );
			}
		
		if( alter == "flatplan" ) {
			commentPlace();
			}
		}
	}