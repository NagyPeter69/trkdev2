function saveChatPos() {
	$.ajax	({
		url:"engine/chatAjax.php",
		data: 'op=savePos&pos='+orient,
		dataType: 'json',
		success:function( data ) {}
		});
	}

function saveChatSize( o ) {
	$.ajax	({
		url:"engine/chatAjax.php",
		data: 'op=saveSize&size='+o,
		dataType: 'json',
		success:function( data ) {}
		});
	}
	
function chatRightMin() {
	$(".rightChat").css( "width", "0px" );
	$(".rightChat").hide(0);
	
	$(".chatContent").hide(0);
	var height = $(".bottomChat").find(".chatHeader").outerHeight() + 1;
	$(".bottomChat").css( "height", height+"px" );
	$(".bottomChat").show(0);
	$(".chatmin").hide(0);
	$(".chatmax").show(0);
	orient = "bottom";
	
	saveChatPos();
	chatRefitpage();
	}

function switchChat( o ) {
	$(".chatContent").show(0);
	if( o == "bottom" ) {
		$(".rightChat").hide(0);
		$(".rightChat").css( "width", "0px" );
		
		var height = $(".bottomChat").find(".chatHeader").outerHeight() + $(".bottomChat").find(".chatContent").outerHeight() + 1;
		$(".bottomChat").css( "height", height+"px" );
		$(".bottomChat").show(0);
		orient = "bottom";
		}

	if( o == "right" ) {
		$(".bottomChat").css( "height", "0px" );
		$(".bottomChat").hide(0);
		
		var width = $(".rightChat").find(".chatHeader").outerWidth() + 1;
		$(".rightChat").css( "width", width+"px" );
		$(".rightChat").show(0);
		orient = "right";
		}
	
	saveChatPos();
	chatRefitpage();
	}

function chatWindowClose() {
	if( orient == "bottom" ) {
		$(".chatContent").show(0);
		var height = $(".bottomChat").find(".chatHeader").outerHeight() + $(".bottomChat").find(".chatContent").outerHeight() + 1;
		$(".bottomChat").css( "height", height+"px" );
		$(".bottomChat").show(0);
		$(".chatmin").show(0);
		$(".chatmax").hide(0);		
		}
	
	if( orient == "right" ) {
		var width = $(".rightChat").find(".chatHeader").outerWidth() + 1;
		$(".rightChat").css( "width", width+"px" );
		$(".rightChat").show(0);
		}
	
	chatRefitpage();
	}
	
function chatNewWindow() {
	$(".rightChat").css( "width", "0px" );
	$(".rightChat").hide(0);
	
	$(".bottomChat").css( "height", "0px" );
	$(".bottomChat").hide(0);
	window.open( "chat.php", "TRKChatWindow", "width=350,height=600,directories=no,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=no,resizable=no");
	
	chatRefitpage();	
	}
	
function chatToggle( o ) {
	if( o == "min" ) {
		$(".chatContent").hide(0);
		var height = $(".bottomChat").find(".chatHeader").outerHeight() + 1;
		$(".bottomChat").css( "height", height+"px" );
		$(".chatmin").hide(0);
		$(".chatmax").show(0);
		}
		
	if( o == "max" ) {
		$(".chatContent").show(0);
		var height = $(".bottomChat").find(".chatHeader").outerHeight() + $(".bottomChat").find(".chatContent").outerHeight() + 1;
		$(".bottomChat").css( "height", height+"px" );
		$(".chatmin").show(0);
		$(".chatmax").hide(0);		
		}	
	
	saveChatSize( o );	
	chatRefitpage();
	}

function chatRefitpage() {
	fit_page();
	if( urlpage == "flatplan" ) {
		fit_box();
		}
		
	if( urlpage == "flatplan_planner" ) {
		fit_box_planner();
		}

	if( urlpage == "flatplan_preview" ) {
		fit_box_preview();
		}		

	fit_ad_list();
	fit_preview();
	fit_wrapper();		
	}

function fit_box_preview() {
	truewidth = $("#mainPage").width();
	trueheight = $("#mainPage").height();
	winWidth = truewidth;
	
	fpPages = parseInt( $("#fpPages").outerWidth() );
	if( $("#header").css( "display" ) == "block" ) {
		var header = 15+parseInt( $("#header").outerHeight());		
		var ad_height = parseInt( trueheight )-(parseInt( $("#header").outerHeight()) );
		}
	else {
		var header = 0;
		var ad_height = parseInt( trueheight );
		}
	
	//ad_height -= parseInt( $("#headerExtraLine").outerHeight());
	
	$('#content').height( ad_height );
	$('#fpPages, #fpToolBox').height( ad_height );
	$('#content_box').height( ad_height-(parseInt( $("#fpFooter").outerHeight() ) )-parseInt( $("#headerExtraLine").outerHeight()) );
	
	minHeight = ad_height;
	
	if( parseInt( $("#fpPages").css("left") ) < 0 ) {
		if( mobile ) {
			$('#content_box, #fpFooter').width( truewidth );
			}
		else {
			$('#content_box, #fpFooter').width( truewidth-parseInt( $("#fpToolBox").outerWidth() ) );
			}
		//alert( $('#content_box').width() );
		}
	else {
		$('#content_box, #fpFooter').width( truewidth-fpPages-parseInt( $("#fpToolBox").outerWidth() ) );	
		}

	width = $('#content_box').width();
	height = $('#content_box').height();
	var newTop = parseInt( header+(ad_height/2)+40);
	
	previewHeight = parseInt( $("#fpPages").outerHeight()-parseInt( $(".inner").outerHeight()+20 ) );
	$('.innerPreview').height( previewHeight );

	fit_pages();
	var footer = parseInt( $("#fpFooter").outerWidth() );
	var pagesLeft = (footer/2)-(parseInt($(".pages").outerWidth())/2 );	
	if( compareMode == "off" ) {
		$("#leftArrow").css( "left", (pagesLeft-36)+"px" );
		$("#rightArrow").css( "left", (pagesLeft+parseInt($(".pages").outerWidth()))+"px" );
		$("#leftArrow_hover").css( "left", (pagesLeft-36)+"px" );
		$("#rightArrow_hover").css( "left", (pagesLeft+parseInt($(".pages").outerWidth()) )+"px" );
		}
	$(".status1").css("left", ( ((footer-pagesLeft)/2)-(parseInt($(".status1").outerWidth())/2 ) )+"px" );
	var temp = pagesLeft+parseInt($(".pages").outerWidth());
	var maradt = (footer-temp)/2;
	$(".status2").css("left", ( (temp+maradt)-(parseInt($(".status2").outerWidth())/2 ) )+"px" );
		
	fit_toolbar();

	fpBox = {
		'Width': parseInt( $("#content_box").width() ),
		'Height': parseInt( $("#content_box").height() )
		}
	}

function fit_box() {
	var ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight())-parseInt( $("#headerExtraLine").outerHeight());
	var in_width = parseInt( $( "#mainPage" ).width()) - 60;
	if( in_width < 1220 ) {
		in_width = 1220;
		}

	//tdWidth = row2*200;

	$('#content_box').height( ad_height );
	$('#liveLog').height( ad_height-parseInt( $(".top_menu").outerHeight() )-parseInt( $("#logSettings").outerHeight() )-parseInt( $("#flatplanSettings").outerHeight()+2 )-60 );
	$('#fp_holder, #fp_wrapper, #fp_holder2').width( $('#fp_content').width() );
	var corr = 0;
	if( $('#manageFPbox').length > 0 ) {
		corr = parseInt( $('#manageFPbox').height() );
		}
	$('#fp_holder, #fp_wrapper, #fp_holder2').height( ( ad_height-corr ) );
	}

function fit_box_planner() {
	var ad_height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight())-parseInt( $("#headerExtraLine").outerHeight());
	var in_width = parseInt( $( "#mainPage" ).width()) - 60;
	if( in_width < 1220 ) {
		in_width = 1220;
		}

	tdWidth = row2*200;
	$('#content_box, #fp_content').height( ad_height );
	
	var padding = parseInt( $(".fp_left").css("padding-top") );
	ad_height -= 2 * padding;
	$('.fp_left').height( ad_height );
	}
	
function rgb2hex(rgb){
 rgb = rgb.match(/^rgb((d+),s*(d+),s*(d+))$/);
 return "" +
  ("0" + parseInt(rgb[1],10).toString(16)).slice(-2) +
  ("0" + parseInt(rgb[2],10).toString(16)).slice(-2) +
  ("0" + parseInt(rgb[3],10).toString(16)).slice(-2);
}

var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

var mousePos = {
  "x" : "",
  "y" : ""
  };

document.addEventListener('mousemove', function(e){ 
    mousePos["x"] = e.clientX || e.pageX; 
    mousePos["y"] = e.clientY || e.pageY 
}, false);

document.addEventListener('click', function(e){
	var ignore = new Array( "commentMenu", "clickIgnore", "mainmenu", "panelButton", "ui-datepicker-prev", "ui-datepicker-next", "ui-icon", "ui-icon ui-icon-circle-triangle-e", "ui-icon ui-icon-circle-triangle-w", "ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all ui-state-hover", "ui-datepicker-prev ui-corner-all ui-state-hover ui-datepicker-prev-hover", "ui-datepicker-next ui-corner-all ui-state-hover ui-datepicker-next-hover", "logo-in" );
	var ignoreID = new Array( "content_box", "renderedSRC1", "renderedSRC2", "ui-datepicker-div", "mobile_usermenu", "trklogoimage", "book-icon" );
	
   	var classes = e.target.className;
	
   	if( e.which == 1 ) {
	//console.log( e.target );
		if( classes.indexOf( "floatMenu" ) == -1 ) {
			//console.log( classes );
			if( jQuery.inArray( classes, ignore ) == -1 ) {
				if( jQuery.inArray( e.target.id, ignoreID ) == -1 ) {
					if( $(e.target).closest(".floatMenu").length == 0 ) {
						if( $(e.target).closest(".floatCommentMenu").length == 0 ) {
							if( $(e.target).closest("#ui-datepicker-div").length == 0 ) {
								$(".floatMenu").each(function(){
									$(this).hide(100, function(){
										if( $(this).attr("id") != "logSettingsPanel" && $(this).attr("id") != "handoutBox" && $(this).attr("id") != "customMenu" && $(this).attr("id") != "floatMenu" && $(this).attr("id") != "logoMenu" && $(this).attr("id") != "mobile_usermenu" ) { 
											$(this).remove();
											}
										});
									});
								}
							}
						}
					}
				}
			}
   		}
}, false);

function adjustColor(col,amt) {
    var usePound = false;
    if ( col[0] == "#" ) {
        col = col.slice(1);
        usePound = true;
    }

    var num = parseInt(col,16);

    var r = (num >> 16) + amt;

    if ( r > 255 ) r = 255;
    else if  (r < 0) r = 0;

    var b = ((num >> 8) & 0x00FF) + amt;

    if ( b > 255 ) b = 255;
    else if  (b < 0) b = 0;

    var g = (num & 0x0000FF) + amt;

    if ( g > 255 ) g = 255;
    else if  ( g < 0 ) g = 0;

    return (usePound?"#":"") + (g | (b << 8) | (r << 16)).toString(16);
}

function messageBox( type, title, content, buttons ) {
	$("#messageBox").removeClass();
	
	$("#messageBox").addClass( type );
	$("#message_title").html( title );
	$("#message_content").html( content );
	if( content != "" ) {
		$("#message_content").show(0);
		}
		
	else {
		$("#message_content").hide(0);		
		}
		
	$("#message_buttons").html( buttons );
	
	setTimeout(function(){
		messageBoxResize();
		messageBoxResize();
	}, 0 );
	
	$("#messageBox").fadeIn( 100 );
	}

function messageBoxResize() {
	var left = $("#messageBox").outerWidth( true );
	var height = $("#messageBox").outerHeight( true );
		
	$("#messageBox").css({
		"left" : $(window).width() / 2 - left / 2,
		"top" : $(window).height() / 2 - height / 2,
		})
	}

function messageBoxHide() {
	$("#messageBox").fadeOut( 100 );
	}
	
function getDPI() {
  return document.getElementById("dpi").offsetHeight;
	}

function changeModAdhocUser( val ) {
	if( val == '' ) {
		$('#user_mod_content').hide('fast', function() {
			$('#user_mod_content').html( '' );
			});
		$('#user_mod_content').hide('fast');
		}
	else {
		$.ajax	({
			url:"engine/menuAjax.php",
			data: 'op=getAdUser&id='+val,
			dataType: 'json',
			success:function( data ) {
				$('#user_mod_content').html( data );
				
				$('#user_mod_content').show(0);
				var newTop = ( $(window).height() / 2 )-( $("#accounts_modifyMember").height()/2 );
				$('#user_mod_content').hide(0);
				
				$("#accounts_modifyMember").animate({
					"top": newTop+"px"
					});
				$('#user_mod_content').show('fast');	
				}
			});		
		}
	}

function setDivCenter_visitor( divid ) {
	var oHeight = $("#"+divid).height();
	var oWidth = $("#"+divid).width();
	
	var left = ( $(window).width() / 2 )-( oWidth/2 );
	var top = ( $(window).height() / 2 )-( oHeight/2 );
	
	$("#"+divid).css({
		"left": left+"px",
		"top": top+"px"
		});
	}

function setDivCenter2( obj ) {
	$(obj).show(0);
	var oHeight = $(obj).height();
	var oWidth = $(obj).width();
	
	//console.log( oHeight+" "+oWidth );
	$(obj).hide(0);
	
	var left = ( $(window).width() / 2 )-( oWidth/2 );
	var top = ( $(window).height() / 2 )-( oHeight/2 );
	
	$(obj).css({
		"left": left+"px",
		"top": top+"px"
		});
	}


function setDivCenter( divid ) {
	$("#"+divid).show(0);
	var oHeight = $("#"+divid).height();
	var oWidth = $("#"+divid).width();
	$("#"+divid).hide(0);
	
	var left = ( $(window).width() / 2 )-( oWidth/2 );
	var top = ( $(window).height() / 2 )-( oHeight/2 );
	
	$("#"+divid).css({
		"left": left+"px",
		"top": top+"px"
		});
	}

function setDivPos( divid ) {
	$("#"+divid).show(0);
	var offset = $("#"+divid).offset();
	var oHeight = $("#"+divid).height()+offset.top;
	$("#"+divid).hide(0);

	if( oHeight > $(window).height() ) {
		$("#"+divid).css("top", (parseInt( $("#"+divid).css("top") )-$("#"+divid).outerHeight() )+"px");
		} 
	}

function ftpList2( value ) {
	$( "#ftp_del_v" ).prop( "disabled", true );
	$.ajax	({
		url:"engine/menuAjax.php?op=ftpList&value="+value,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$("#ftp_del_v").html( data );
			$( "#ftp_del_v" ).prop( "disabled", false );
			}
		});	
	
	}

function memberList2( value ) {
	$( "#account_remove" ).prop( "disabled", true );
	$.ajax	({
		url:"engine/menuAjax.php?op=memberList&value="+value,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$( "#account_remove" ).prop( "disabled", false );
			$( "#account_remove").html( data );
			}
		});
	}

function AdHocMemberList( value ) {
	$("#user_mod_content").hide(200);
	$( "#account_remove" ).prop( "disabled", true );
	$.ajax	({
		url:"engine/menuAjax.php?op=AdHocMemberList&value="+value,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$( "#account_remove" ).prop( "disabled", false );
			$( "#account_remove").html( data );
			}
		});
	}

function memberStatList( value ) {
	$.ajax	({
		url:"engine/menuAjax.php?op=memberStatList&value="+value,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$( "#userStatlist").html( data );
			}
		});
	}

function memberList( value ) {
	$("#user_mod_content").hide(200);
	$( "#account_remove" ).prop( "disabled", true );
	$.ajax	({
		url:"engine/menuAjax.php?op=memberList&value="+value,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$( "#account_remove" ).prop( "disabled", false );
			$( "#account_remove").html( data );
			}
		});
	}

function ftpList( value ) {
	$("#ftp_mod_content").hide(200);
	$( "#ftp_mod_v" ).prop( "disabled", true );
	$.ajax	({
		url:"engine/menuAjax.php?op=ftpList&value="+value,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$( "#ftp_mod_v" ).prop( "disabled", false );
			$("#ftp_mod_v").html( data );
			}
		});	
	
	}

function lMagazines( value ) {
	$.ajax	({
		url:"engine/menuAjax.php?op=lMagazines&value="+value,
		type: "GET",
		dataType: 'json',
		success:function( data ) {
			$( "#loadedMagazines" ).html( data );
			}
		});	
	}

function connect(div1, div2, color, thickness, status ) {
    var off1 = getOffset(div1);
    var off2 = getOffset(div2);
    // bottom right
    var x1 = off1.left + off1.width;
    var y1 = off1.top + off1.height/2;
    // top right
    var x2 = off2.left;
    var y2 = off2.top + off2.height/2;
    // distance
    var length = Math.sqrt(((x2-x1) * (x2-x1)) + ((y2-y1) * (y2-y1)));
    // center
    var cx = ((x1 + x2) / 2) - (length / 2) + 3;
    var cy = ((y1 + y2) / 2) - (thickness / 2) ;
    // angle
    var angle = Math.atan2((y1-y2),(x1-x2))*(180/Math.PI);
    // make hr
    var htmlLine = "<div dash-data='"+$("#div1").attr("id")+"' class='dashLine' style='display: "+toggle[ status ]+"; border-top: "+thickness+"px dotted rgb( 108, 192, 217 ); left:" + cx + "px; top:" + cy + "px; width:" + (length-7) + "px; -moz-transform:rotate(" + angle + "deg); -webkit-transform:rotate(" + angle + "deg); -o-transform:rotate(" + angle + "deg); -ms-transform:rotate(" + angle + "deg); transform:rotate(" + angle + "deg);' />";
    //
    //document.getElementById('commenDisplay').innerHTML += htmlLine;
	if( $("div[dash-data='"+$("#div1").attr("id")+"']").length > 0 ) {
		var obj = $("div[dash-data='"+$("#div1").attr("id")+"']");
		obj.css({
			"left" : cx + "px",
			"top" : cy + "px",
			"width" : (length-7) + "px",
			"-moz-transform" : "rotate(" + angle + "deg)",
			"-webkit-transform" : "rotate(" + angle + "deg)",
			"-o-transform" : "rotate(" + angle + "deg)",
			"-ms-transform" : "rotate(" + angle + "deg)",
			"transform" : "rotate(" + angle + "deg)",
			});
		}
	else {
		var html = $("#commenDisplay").html();
		$("#commenDisplay").html( html + htmlLine );
		}
	
	}

function getOffset( el ) {
    var _x = parseInt( el.style.left );
    var _y = parseInt( el.style.top )
    var _w = el.offsetWidth|0;
    var _h = el.offsetHeight|0;
    
    return { top: _y, left: _x, width: _w, height: _h };
	}

function issueOperation( menus, magazine, issue ){
	var box = $("."+magazine+"_"+issue).offset();
	box.left += 6;
	box.top += 20;
	var id = magazine+'_'+issue+'Float';
	
	if( $("#"+id).length != 0 ) {
		$("#"+id).hide(200,function(){
			$(this).remove();
			});
		}
	else {
		$.ajax	({
			url:"engine/menuAjax.php?op=getIssueMenu&mag="+magazine+"&issue="+issue,
			type: "POST",
			data: { menus : menus },
			dataType: 'json',
			success:function( data ) {
				jQuery('<div/>', {
					id: id,
					class: "floatMenu",
					style: "position: absolute; display: none; left: "+box.left+"px; top: "+box.top+"px;"
				}).appendTo( "body" );
				$("#"+id).html( data );
				
				setDivPos( id );
				$("#"+id).show(200);
				
				$( "#"+id ).draggable({ cursor: "move" });
				}
			});		
		}
	}

function changeModUser( val ) {
	if( val == '' ) {
		$('#user_mod_content').hide('fast', function() {
			$('#user_mod_content').html( '' );
			});
		$('#user_mod_content').hide('fast');
		$("#regenpw").css("display", "none");
		}
	else {
		$.ajax	({
			url:"engine/menuAjax.php",
			data: 'op=getUser&id='+val,
			dataType: 'json',
			success:function( data ) {
				$('#user_mod_content').html( data );
				
				$('#user_mod_content').show(0);
				var newTop = ( $(window).height() / 2 )-( $("#accounts_modifyMember").height()/2 );
				$('#user_mod_content').hide(0);
				
				$("#accounts_modifyMember").animate({
					"top": newTop+"px"
					});
				$('#user_mod_content').show('fast');
				$("#regenpw").css("display", "inline-block");				
				}
			});		
		}
	}

function changeModGroup( val ) {
	if( val == '' ) {
		$('#group_mod_content').hide('fast', function() {
			$('#group_mod_content').html( '' );
			});
		$('#group_mod_content').hide('fast');
		}
	else {
		$.ajax	({
			url:"engine/menuAjax.php",
			data: 'op=getUserGroup&id='+val,
			dataType: 'json',
			success:function( data ) {
				$('#group_mod_content').html( data );
				
				$('#group_mod_content').show(0);
				var newTop = ( $(window).height() / 2 )-( $("#accounts_modifyGroup").height()/2 );
				$('#group_mod_content').hide(0);
				
				$("#accounts_modifyGroup").animate({
					"top": newTop+"px"
					});
				
				$('#group_mod_content').show('fast');	
				}
			});		
		}
	}

function changeFTP( val, pub ) {
	if( val == '' ) {
		$('#ftp_mod_content').hide('fast', function() {
			$('#ftp_mod_content').html( '' );
			});
		$('#ftp_mod_content').hide('fast');
		}
	else {
		$.ajax	({
			url:"engine/ajax.php",
			data: 'op=get_ftp&pub='+pub+'&node='+val,
			dataType: 'json',
			success:function( data ) {
				$('#ftp_mod_content').html( data );

				$('#ftp_mod_content').show(0);
				var newTop = ( $(window).height() / 2 )-( $("#ftp_modify").height()/2 );
				$('#ftp_mod_content').hide(0);
				$("#ftp_modify").animate({
					"top": newTop+"px"
					});
					
				$('#ftp_mod_content').show('fast');
			
				$('#mod_ftp :input').keyup( function() {
					if( $('#ftp_mod_content').css('display') != 'none' ) {
						if( $('#m_name').val() != '' && $('#m_address_1').val() != '' && $('#m_address_2').val() != '' && $('#m_address_3').val() != '' && $('#m_address_4').val() != '' && $('#m_port').val() != '' && $('#m_login').val() != '' && $('#m_pass').val() != '' ) {
							$("#ftp_mod").removeAttr("disabled");
							}
						else {
							$("#ftp_mod").attr("disabled", "disabled");
							}
						}
					else {
						$("#ftp_mod").attr("disabled", "disabled");
						}
					});
				$('#mod_ftp :input').keyup();
				}
			});
		}
	}

function letterCheckName(e) {
	var regex = new RegExp("^[a-zA-Z]+$");
	var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
	
	if (regex.test(str)) {
        return true;
    }

    e.preventDefault();
    return false;
	}

function numberCheck(e) {
	var regex = new RegExp("^[0-9]+$");
	var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
	
	if ( ( e.keyCode >= 48 && e.keyCode <= 57 ) || e.keyCode == 192 || ( e.keyCode >= 96 && e.keyCode <= 105 ) || e.keyCode == 9 || e.keyCode == 8 ) {
        return true;
    }

    e.preventDefault();
    return false;
	}

function numberCheck2(e) {
	var regex = new RegExp("^[0-9]+$");
	var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
	
	if ( ( e.keyCode >= 48 && e.keyCode <= 57 ) || e.keyCode == 192 || ( e.keyCode >= 96 && e.keyCode <= 105 ) || e.keyCode == 9 || e.keyCode == 8 || e.keyCode == 188 || e.keyCode == 190 ) {
        return true;
    }

    e.preventDefault();
    return false;
	}

function numberCheck3(e) {
	var regex = new RegExp("^[0-9]+$");
	var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
	
	//console.log( e.keyCode );
	
	if ( ( e.keyCode >= 48 && e.keyCode <= 57 ) || e.keyCode == 192 || ( e.keyCode >= 96 && e.keyCode <= 105 ) || e.keyCode == 9 || e.keyCode == 8 || e.keyCode == 188 || e.keyCode == 190 || e.keyCode == 109 || e.keyCode == 189 ) {
        return true;
    }

    e.preventDefault();
    return false;
	}

function letterCheck(e) {
	var regex = new RegExp("^[a-zA-Z0-9]+$");
	var str = String.fromCharCode(!e.charCode ? e.which : e.charCode);
	
	if (regex.test(str)) {
        return true;
    }

    e.preventDefault();
    return false;
	}

function closePanel( id, type, parent ) {
	switch( type ){
		case 'off':
			$("#"+id).hide(200, function(){
				$(this).remove();
				});
			break;
		case 'back':
			$("#"+id).hide(200, function(){
				$(this).remove();
				});
			/*if( parent == undefined ) {
				$("#logoMenu").show(200);
				}
			else {
				$("#"+parent).show(200);
				}*/
			break;			
		}
	}

function revealPass( from, id ) {
	if( from.checked ) {
		$("#"+id).attr("type", "text");
		}
	else {
		$("#"+id).attr("type", "password");
		}
	}

function toggleDiv( id ) {
	var display = $("#"+id).css("display");
	switch( display ) {
		case 'none':
			$("#"+id).show(200, function(){ 				
				if( id == "caption_settings" ) {
					fit_adlist();
					}			
				});
			break;
		
		default:
			$("#"+id).hide(200, function(){
				if( id == "caption_settings" ) {
					fit_adlist();
					}				
				});
			break;
		}
	}

function precise_round(num,decimals) {
    return Math.round(num * Math.pow(10, decimals)) / Math.pow(10, decimals);
	}

function approvePage( page, value ) {
	if( page != undefined && value != undefined ) {
		$.ajax	({
			url:"engine/flatplan_ajax.php",
			data: 'op=updatePageStatus&pageID='+page+'&value='+value,
			dataType: 'json',
			success:function( data ) {
				}
			});
		}
	}

$.fx.speeds._default = 300;
function flipPage( page, direction ) {
	var current = parseInt( $("#"+page+"_current").val() );
	if( direction == "+" ) {
		$("#"+page+"_current").val( current+1 );
		}	
	if( direction == "-" ) {
		$("#"+page+"_current").val( current-1 );
		}
		
	current = parseInt( $("#"+page+"_current").val() );
	alterHandle( page );
	$("div [page='"+page+"']").each(function() {
		var alter = parseInt( $(this).attr("alter") );
		if( alter == current ) {
			$(this).css( "z-index", "1200" );
			
			if( $(this).hasClass("thumb") ) {
				var xpos = $(this).width()+5;
				$.fx.speeds._default = 0;
				switch( direction ) {
					case '+':
						$(this).transition({ x: '+='+xpos });
						$.fx.speeds._default = 200;
						var This = $(this);
						setTimeout( function(){
							This.transition({ x: '-='+xpos });
							}, 20 );
						break;
						
					case '-':
						$(this).transition({ x: '-='+xpos });
						$.fx.speeds._default = 200;
						var This = $(this);
						setTimeout( function(){
							This.transition({ x: '+='+xpos });
							}, 20 );
						break;
					}
				
				}
			else {
				$(this).transition({});
				}
			
			$(this).css( "opacity", "1" );
			}
		else {
			var zIndex = 1000-alter;
			$(this).css( "opacity", "1" );
			$(this).css( "z-index", zIndex );
			}

		});
	}

function removeRotate( page, current ) {
	$("div [page='"+page+"'][alter='"+current+"']").each(function() {
		//$(this).css('-webkit-transform', '' );
		});
	}

function alterHandle( i ) {
	$("div [alter!='0'][page='"+i+"']").each(function() {
		var current = parseInt( $("#"+i+"_current").val() );
		var max = parseInt( $("#"+i+"_max").val() );
		
		if( current > 0 ) {
			$("#"+i+"_left").attr("onclick","flipPage( "+i+", '-' )");
			$("#"+i+"_left").addClass( "flip_active" );
			$("#"+i+"_left").removeClass( "flip_gray" );
			$("#"+i+"_left").css({ cursor: 'pointer' });
			}
		else {
			$("#"+i+"_left").attr("onclick","");
			$("#"+i+"_left").removeClass( "flip_active" );
			$("#"+i+"_left").addClass( "flip_gray" );
			}
		
		if( current < max ) {
			$("#"+i+"_right").attr("onclick","flipPage( "+i+", '+' )");
			$("#"+i+"_right").addClass( "flip_active" );
			$("#"+i+"_right").removeClass( "flip_gray" );
			$("#"+i+"_right").css({ cursor: 'pointer' });				
			}
		else {
			$("#"+i+"_right").attr("onclick","");
			$("#"+i+"_right").removeClass( "flip_active" );
			$("#"+i+"_right").addClass( "flip_gray" );
			}
		});
	}

var height = 0;
$.browser = {
	"device" : false
	}
if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {
 	$.browser = {
 		"device": true
 		}
	}
$.strPad = function(i,l,s) {
	var o = i.toString();
	if (!s) { s = '0'; }
	while (o.length < l) {
		o = s + o;
	}
	return o;
	};



var lister = [];
var lvalue = [];

function simulateClick(x, y) {
    jQuery(document.elementFromPoint(x, y)).click();
	}

function thumbClick( e, type, evt ) {
	
	var page = '';
	if( type == "mass" ) {
		window.location.href = e.attr('double');
		}
	if( type == 'single' ) {
		page = e.attr('page');
		
		if( mobile ) {
			window.location.href = e.attr("double");
			}
		else {
			
			if (evt.shiftKey) {
				var start = 0;
				var end = 0;
				var selected = new Array();
				$("#"+currentplace+" input[type='checkbox'][name='pageSelector[]']:checked").each(function(){
					selected.push( $(this).val() );
					});
				
				
				
				if( jQuery.inArray( lastPageClick, selected ) !== -1 || selected.length == 0 ) {
					if( selected.length == 0 ) {
						start = 0;
						end = parseInt( page );
						}
					else {
						if( parseInt( lastPageClick ) < parseInt( page ) ) {
							start = parseInt( lastPageClick )+1;
							end = parseInt( page );
							}
						else {
							start = parseInt( page );
							end = parseInt( lastPageClick )-1;
							}
						}
						
					for( i = start; i <= end; i++ ) {
						$("#"+currentplace+" input:checkbox[value="+i+"][state='"+e.attr("state")+"'] ").each(function() {
							if( !$(this).prop("disabled") ) {
								$(this).prop('checked', true);
								$( "#"+currentplace+" #"+$(this).val()+"_selector" ).css({ opacity: '1' });
								}
							});				
						}	
					}
				else {
					if( parseInt( lastPageClick ) < parseInt( page ) ) {
						start = parseInt( lastPageClick )+1;
						end = parseInt( page );
						}
					else {
						start = parseInt( page );
						end = parseInt( lastPageClick )-1;
						}
	
					for( i = start; i <= end; i++ ) {
						$("#"+currentplace+" input:checkbox[value="+i+"][state='"+e.attr("state")+"'] ").each(function() {
							if( !$(this).prop("disabled") ) {
								$(this).prop('checked', false);
								$( "#"+currentplace+" #"+$(this).val()+"_selector" ).css({ opacity: '0' });
								}
							});				
						}
					}
				}
			
			else {
				$("#"+currentplace+" input:checkbox[value="+page+"][state='"+e.attr("state")+"'] ").each(function() {		
					if( !$(this).prop("disabled") ) {
						if($(this).prop('checked')){
							$(this).prop('checked', false);
							$(this).parent().parent().parent().parent().parent().find("div[id='"+page+"_selector']").css({ opacity: '0' });
							}
						else{
							$(this).prop('checked', true);
							$(this).parent().parent().parent().parent().parent().find("div[id='"+page+"_selector']").css({ opacity: '1' });
							}
		    			}
					});
				}
				
			lastPageClick = page;
			}
		}
	}

function pageSelect( e, type ) {
	var page = '';
	if( type == 'mass' ) {
		item = e.attr('item');
		var needle = jQuery.inArray( item, lister );
		var allow = 0;
		if( needle == -1 ) {
			lister.push( item );
			lvalue.push( 0 );
			needle = parseInt( lister.length )-1;
			}
			
		$("#"+currentplace+" input:checkbox[item="+item+"][state='"+e.attr("state")+"']").each(function() {
   			if( !$(this).prop("disabled") ) {
   				allow = 1;
   				if( lvalue[needle] == 0 ){
       				$(this).prop('checked', true);
       				$( "#"+currentplace+" #"+$(this).val()+"_selector" ).css({ opacity: '1' });
    				}
    			else{
        			$(this).prop('checked', false);
        			$( "#"+currentplace+" #"+$(this).val()+"_selector" ).css({ opacity: '0' });
    				}
				}
			});
		if( allow ) {	
			if( lvalue[needle] == 0 ) {
				lvalue[needle] = 1;
				}
			else {
				lvalue[needle] = 0;
				}
			}
		}
		
	if( type == 'single' ) {
		page = e.attr('page');
		$("input:checkbox[value="+page+"][state='"+e.attr("state")+"']").each(function() {
			if( !$(this).prop("disabled") ) {
				if($(this).prop('checked')){
					$(this).prop('checked', false);
					$( "#"+$(this).val()+"_selector" ).css({ opacity: '0' });
					}
				else{
					$(this).prop('checked', true);
					$( "#"+$(this).val()+"_selector" ).css({ opacity: '1' });
					}
    			}
			});
		}
	}   

function PlannerPageSelect( e, type ) {
	var page = '';
	if( type == 'mass' ) {
		var classes = $( e.parent().parent() ).attr("class");
		if( classes.indexOf(" ad ") !== false ) {
			item = e.parent().parent().attr('aid');
			var selector = "#"+currentplace+" div[aid="+item+"]";			
			}
		else {
			item = e.parent().parent().attr('aname');
			var selector = "#"+currentplace+" div[aname="+item+"]";
			}

		var needle = jQuery.inArray( item, lister );
		var allow = 0;
		if( needle == -1 ) {
			lister.push( item );
			lvalue.push( 0 );
			needle = parseInt( lister.length )-1;
			}
		
		$(selector).each(function() {
			var page = parseInt( $(this).find(".pagenr").find("div").html() ); 

			$("input:checkbox[value="+page+"]").each(function() {
				if( $(this).prop("checked") ) {
					$(this).prop('checked', false);
        			$( "#"+currentplace+" #"+$(this).val()+"_selector" ).css({ opacity: '0' });
					}
				else {
					$(this).prop('checked', true);
					$( "#"+currentplace+" #"+$(this).val()+"_selector" ).css({ opacity: '1' });
        			}
				});
			});
		}
		
	if( type == 'single' ) {
		page = e.attr('page');
		$("input:checkbox[value="+page+"]").each(function() {
			if( !$(this).prop("disabled") ) {
				if($(this).prop('checked')){
					$(this).prop('checked', false);
					$( "#"+$(this).val()+"_selector" ).css({ opacity: '0' });
					}
				else{
					$(this).prop('checked', true);
					$( "#"+$(this).val()+"_selector" ).css({ opacity: '1' });
					}
    			}
			});
		}
	} 

function PlannerNumberClick( ids, fpType ) {
	var idArray = ids.split("|");
	var clicks = 0;

    for ( var i = 0; i < idArray.length; i++ ) {	
		var id = idArray[i];
		
		$( id ).off();
		$('body')
		.on("click", id, function(e){
			var state = e.target.outerHTML.substring( e.target.outerHTML.indexOf('state') );
			var start_pos = state.indexOf('"') + 1;
			var end_pos = state.indexOf('"',start_pos);
			state = state.substring(start_pos,end_pos);
			
			PlannerPageSelect( $("#"+currentplace+" #"+e.target.id+""), 'mass' );
			})
		.on("dblclick", id, function(e){
			e.preventDefault();
			});
		}
	}

function PlannerThumbClick( ids, fpType ) {
	var idArray = ids.split("|");
	var clicks = 0;

    for ( var i = 0; i < idArray.length; i++ ) {	
		var id = idArray[i];
		
		$( id ).off();
		$('body')
		.on("click", id, function(e){
			var state = e.target.outerHTML.substring( e.target.outerHTML.indexOf('state') );
			var start_pos = state.indexOf('"') + 1;
			var end_pos = state.indexOf('"',start_pos);
			state = state.substring(start_pos,end_pos);
			var check = e.target;
			
			if( !$(check).hasClass( "fp-icons" ) ) {
				if( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']").hasClass( 'thumb' ) ) {
					thumbClick( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']"), 'single', e );
					}
				else if( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']").hasClass( 'pagenr' ) ) {
					pageSelect( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']"), 'single' );
					}
				}
			})
		.on("dblclick", id, function(e){
			e.preventDefault();
			});
		}
	}
	
function singleDoubleClick( ids, fpType ) {
	var DELAY = 200,
   		clicks = 0,
    	timer = null;
    
    var idArray = ids.split("|");
    
    for ( var i = 0; i < idArray.length; i++ ) {	
		var id = idArray[i];
		
		$( id ).off();
		$('body')
		.on("click", id, function(e){
			var state = e.target.outerHTML.substring( e.target.outerHTML.indexOf('state') );
			var start_pos = state.indexOf('"') + 1;
			var end_pos = state.indexOf('"',start_pos);
			state = state.substring(start_pos,end_pos);

			clicks++;
			if(clicks === 1) {
				
				timer = setTimeout(function() {
					if( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']").hasClass( 'thumb' ) ) {
						thumbClick( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']"), 'single', e );
						}
					else if( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']").hasClass( 'pagenr' ) ) {
						pageSelect( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']"), 'single' );
						}
					clicks = 0;
				}, DELAY);
			} 
			
			else {
				
				if( fpType != "PLAN" ) {
					clearTimeout(timer);
					if( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']").hasClass( 'thumb' ) ) {
						thumbClick( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']"), 'mass', e );
						}
					else if( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']").hasClass( 'pagenr' ) ) {
						pageSelect( $("#"+currentplace+" #"+e.target.id+"[state='"+state+"']"), 'mass' );
						}
					clicks = 0;
					}
				}
			})
		.on("dblclick", id, function(e){
			e.preventDefault();
			});
		}
	}

function fit_wrapper() {
	if( !$.browser.device ) {
		ad_height = parseInt( $( "#mainPage" ).height() )-(parseInt( $("#header").outerHeight()) );
		$('#pub_table_wrapper').height( ad_height );
		}
	}

function fit_preview() {
	if( !$.browser.device ) {
		ad_height = parseInt( $( window ).height() )-(parseInt( $("#header").outerHeight()) );
		$('#ad_preview').height( ad_height );
		}
	}

function fit_ad_list() {
	if( !$.browser.device ) {
		ad_height = parseInt( $( window ).height() )-(parseInt( $(".content_title").outerHeight())+parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() ) )-55;
		$('#ad_list').height( ad_height );
		}
	ad_height = parseInt( $( window ).height() )-(parseInt( $(".content_title").outerHeight())+parseInt( $("#header").outerHeight())+parseInt( $("#menu").outerHeight() )+parseInt( $(".ad_menu_title").outerHeight() ) )-95;
	$('.ad_menu_content').height( ad_height );	
	}

$(document).mouseup(function (e) {
    var container = $("#floatMenu");
    if (!container.is(e.target) && container.has(e.target).length === 0) {
        container.hide('fast');
    	}
	});

function hide_div( target_id, from ) {
	from = from || 0;

	$('#'+target_id).hide('fast');
			
	if( from != 0 ) {
		from.css("background-image", "url(images/plus.png)" );
		} 
	}

function toggle_div2( target_id ) {	
	var display = document.getElementById( target_id ).style.display;
	if( display == 'none' ) {
		$('#'+target_id).show(0);
		toggled.push( target_id );
		}
	else {
		$('#'+target_id).hide(0);
		toggled = jQuery.grep(toggled, function(value) {
			return value != target_id;
			});
		}
	}

function toggle_div( target_id, from ) {	
	from = from || 0;
	var display = document.getElementById( target_id ).style.display;
	if( display == 'none' ) {
		toggled.push( target_id );
		$('#'+target_id).show(0);
		var newTop = ( $(window).height() / 2 )-( $("#pubs_newIssue").height()/2 );
		$('#'+target_id).hide(0);
		$("#pubs_newIssue").animate({
			"top": newTop+"px"
			});

		$('#'+target_id).show('fast');
		if( from != 0 ) {
			from.css("background-image", "url(images/minus.png)" );
			}
		}
	else {
		toggled = jQuery.grep(toggled, function(value) {
			return value != target_id;
			});

		$('#'+target_id).hide(0);
		var newTop = ( $(window).height() / 2 )-( $("#pubs_newIssue").height()/2 );
		$('#'+target_id).show(0);
		$("#pubs_newIssue").animate({
			"top": newTop+"px"
			});
			
		$('#'+target_id).hide('fast');
		if( from != 0 ) {
			from.css("background-image", "url(images/plus.png)" );
			}
		}
	}

function fit_page() {
	if( !$.browser.device ) {
		height = parseInt( $( window ).height() )-parseInt( $(".bottomChat").outerHeight());
		if( isNaN(height) ) {
			height = $( window ).height();
			}
		$( "#mainPage" ).height( height );	

		width = parseInt( $( window ).width() )-parseInt( $(".rightChat").outerWidth());
		if( isNaN(width) ) {
			width = $( window ).width();
			}
		$( "#mainPage" ).width( width );
		
		height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight());
		$('#content').height( height );
		var in_width = parseInt( $( "#mainPage" ).width());
		if( in_width < 1000 ) {
			in_width = 1000;
			}
		
		$("#menu").css("width", in_width+'px');
		$("#content").css("width", in_width+1+'px');
		$("#content").css("width", parseInt($("#content").css("width"))-1+'px');	
		}
	}
$( document ).ready(function() {
	height = parseInt( $( window ).height() )-parseInt( $(".bottomChat").outerHeight());
	if( isNaN(height) ) {
		height = $( window ).height();
		}
	$( "#mainPage" ).height( height );	

	width = parseInt( $( window ).width() )-parseInt( $(".rightChat").outerWidth());
	if( isNaN(width) ) {
		width = $( window ).width();
		}
	$( "#mainPage" ).width( width );
	
	if( !$.browser.device ) {
		height = parseInt( $( "#mainPage" ).height() )-parseInt( $("#header").outerHeight());
		$('#content').height( height );
		fit_page();
		}
	fit_wrapper();
	});

$(window).resize(function(){
	messageBoxResize();
	if( !$.browser.device ) {
		fit_page();
		fit_ad_list();
		fit_preview();
		fit_wrapper();
		}
	});

function isEnter(evt, action){
  var charCode = (evt.which) ? evt.which : event.keyCode;
  
  if( action == "jumpToPage" && charCode == 45) {
	return true;
	}
  
  if( charCode == 13 ) {
    var newZoom = parseInt( $('#zoomLevel').val() );
    if( newZoom < 1 ) newZoom = 1;
    if( newZoom > 1500 ) newZoom = 1500;
    
	if( action == undefined ) {
		_zoom( '', 'roll', newZoom );
		}
	
	else if( action == "jumpToPage") {
		jumpToPage( $('#pageNr').val() );
		}

	
    return false;
    }
  else  {
    if (charCode > 31 && (charCode < 48 || charCode > 57 )) {
      return false;
      }
    else {
      return true;
      }
    }
  }

function isAllowedKey2(evt){
	evt = (evt) ? evt : event;

	var charCode = (evt.charCode) ? evt.charCode : ((evt.keyCode) ? evt.keyCode :
		((evt.which) ? evt.which : 0));

	var allowed = new Array( 246, 32, 252, 243, 250, 337, 369, 225, 233, 237 );

	if (charCode > 31 &&
	   (charCode < 65 || charCode > 90) &&
	   (charCode < 97 || charCode > 122) &&
	   charCode > 31 && (charCode < 48 || charCode > 57) &&
	   allowed.indexOf( charCode ) == -1 
	   ) {
		return false;
		}

    return true;
	}

function isNumberKey2(evt){
    var charCode = (evt.which) ? evt.which : event.keyCode
    if ( charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
	}
	
function isNumberKey(evt){
	//console.log( evt );
    var charCode = (evt.which) ? evt.which : event.keyCode
    if (charCode > 31 && (charCode < 48 || charCode > 57))
        return false;
    return true;
	}
	
function isAllowedKey(evt){
    var charCode = (evt.which) ? evt.which : event.keyCode
   	 //alert( charCode );
    
    if (charCode < 31 || (charCode >= 48 && charCode <= 57) || charCode == 45 || charCode == 95 )
        return true;
    return false;
	}

function pad (str, max) {
	return str.length < max ? pad("0" + str, max) : str;
	}