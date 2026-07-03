var ad_height = 0;

function toggle_lowres( command, img ) {
	if( command == 'show' ) {
		$('#hide_low_res').css('display','initial');
		$('#show_low_res').css('display','none');
		
		$("#lowres").show(0);
		}
	else if ( command == 'hide' ) {
		$('#show_low_res').css('display','initial');
		$('#hide_low_res').css('display','none');
		$("#lowres").hide(0);
		}
	}

function load_preview( page ) {
	current_page = parseInt( current_page );
	max_page = parseInt( max_page );
	$.ajax	({
		url:"load_preview.php",
		data: 'user='+user_id+'&job_id='+job_id+'&page='+page+'&type='+type,
		dataType: 'json',
		success:function( data ) {
			$( '#preview_info' ).html( data['info'] );	
			}
		});	
	}

function page_turn( count, type ) {
	if( type == '+' ) { current_page = parseInt( current_page )+1; }
	if( type == '-' ) { current_page = parseInt( current_page )-1; }
	
	load_preview2( current_page );
	}

var image_pos = 'allo';

function moveObject2(event) {
	
    var delta = 0;
 
    if (!event) event = window.event;
 
    if (event.wheelDelta) {
        delta = event.wheelDelta / 60;
 
    } else if (event.detail) {
        delta = -event.detail / 2;
   		}
	
	if( delta > 0 ) {
		zoom( '#preview_image', '+' );
		}
	else {
		zoom( '#preview_image', '-' );
		}
	}

function zoom( id, opt ) {
	var new_size = 0;
	
	if( image_pos == 'allo' ) {
		var size = parseInt( $( id ).css('height') );
		if( opt == '+' ) {
			new_size = parseInt( parseInt(size)+( parseInt(size)*0.4) );
			if( new_size <= image_size['height'] ) { size = new_size; }
			else { size = image_size['height']; }			
			}
		if( opt == '-' ) {
			new_size = parseInt( parseInt(size)-( parseInt(size)*0.4) );
			if( parseInt( img_orig['height'] ) > parseInt( new_size ) ) {
				size = parseInt( img_orig['height'] );
				}
			else {
				size = new_size;
				}
			}
			
		$( id ).css( 'height', size+'px' );
		
		setTimeout( function() {
		var scaling = parseInt( $( "#preview_image" ).innerWidth() );
	/*	console.log( $( "#preview_image" ).width() );
		if( scaling < 600 ) {
			$('#preview_img').css({
				'max-width': scaling+'px',
				'min-width': scaling+'px',
				'width': scaling+'px'
				});	
			}
		else {
			$('#preview_img').css({
				'max-width': '600px',
				'min-width': '600px',
				'width': '600px'
				});	
			}*/
			}, 100 );
		}
	
	if( image_pos == 'fekvo' ) {
		var size = $( id ).css('width');
		if( opt == '+' ) {
			new_size = parseInt( parseInt(size)+( parseInt(size)*0.4) );
			percent = new_size/parseInt( image_size['width'] )*100;
			if( parseInt( percent ) < 350 ) { size = new_size; }
			}
		if( opt == '-' ) {
			new_size = parseInt( parseInt(size)-( parseInt(size)*0.4) );
			percent = new_size/parseInt( image_size['width'] )*100;
			if( parseInt( percent ) > 20 ) { size = new_size; }
			}
		
		
		$( id ).css( 'width', size+'px' );
		}
			
	setTimeout( function() {
		var top = (parseInt( $( '#preview_content' ).css('height') ) - $( id ).height()) / 2;
		if (top > 0 ) {
			$( id ).css( "margin-top", parseInt(top)+'px' );
			}
		else {
			$( id ).css( "margin-top", '0px' );
			}
		}, 200 );
	}

var img_orig = new Array();
var min= new Array();
var w = 0;

function get_imageSize2( id ) {
	var img = new Image();
	img.src = document.getElementById('preview_image').src;
	img.onload = function() {
		var sc = scale.split('/');
		if( sc[0] == '1' ) {
			if( sc[1] == '2' ) {
				var ad_height2 = ad_height/sc[1];
				}
			else {
				ad_height2 = ad_height;
				}
			}
		else {
			ad_height2 = ad_height;
			}
		
		image_size['width'] = this.width;
		image_size['height'] = this.height;
		
		$( id ).css( "width", 'auto' );
		$( id ).css( "height", (parseInt( mheight )-4)+'px' );
		
		w = parseInt( $('#preview_image').css('width') );
		if( w > 600 ) {
			w = 600;
			}
		
		img_orig['width'] = $( id ).css("width");
		img_orig['height'] = $( id ).css("height");
		$('#preview_img').css({
			'max-width': '800px',
			'max-height': (parseInt( mheight ))+'px',
			'min-height': (parseInt( mheight ))+'px',
			'height': (parseInt( mheight ))+'px'
			});
		$( id ).fadeTo(0, 1);
		}
	}
		
function get_imageSize( id ) {
	var img = new Image();

	img.src = document.getElementById('preview_image').src;
	img.onload = function() {
		$( id ).css( "width", 'auto' );
		$( id ).css( "height", 'auto' );
		image_size['width'] = this.width;
		image_size['height'] = this.height;
  	 	 
		set_image_scale( id );
		}
	}

function set_image_scale( id ) {
	var over_height = parseInt( image_size['height'] )-parseInt( $( '#preview_content' ).css('height') );
	var over_width = parseInt( image_size['width'] )-parseInt( $( '#preview_content' ).css('width') );

	if( over_height >= over_width ) {
		$( id ).css( "height", $( '#preview_content' ).css('height') );
		image_pos = 'allo';
		}	
	else {
		$( id ).css( "width", $( '#preview_content' ).css('width') );
		image_pos = 'fekvo';
		}
	image_def_height = $( id ).height();
	
	var top = parseInt( (parseInt( $( '#preview_content' ).css('height') ) - parseInt(image_def_height)) / 2 );
	if (top > 0 && image_pos == 'fekvo' ) {
		$( id ).css( "margin-top", parseInt(top)+'px' );
		}
	else {
		$( id ).css( "margin-top", '0px' );
		}
	
	$( '#preview_img' ).css( 'max-height', $( '#preview_content' ).css( 'height' ) );
$( id ).fadeTo(0, 1);
	}