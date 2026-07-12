<?php
	header("Content-type: text/css");

	$font = 'myriad_thin';
	if( strpos( $_SERVER['HTTP_USER_AGENT'], "Mac" ) != 0 ) {
		$font = 'myriad_thin';
		}
?>

@font-face {
	font-family: myriad_reg;
	src: url(../fonts/Roboto-Light.ttf);
	}
@font-face {
	font-family: myriad_thin;
	src: url(../fonts/Roboto-Thin.ttf);
	}

.btn-disabled {
	background: #BFBFBF !important;
	pointer-events: none !important;
	}

.subContainer {
	display: none;
	}

.submenu {
	margin-left: 40px;
	color: #B5B5B5;
	cursor: pointer;
	margin-top: 4px;
	}

.ui-draggable-dragging {
	background-image: url(../client/images/floatBG.png) !important;
	background-color: rgb( 30, 30, 30 ) !important;
	}

.panelControl {
	width: 350px;
	max-height: 85vh;
	overflow: auto;
	}

.panelButton {
	float:left;
	min-width: 60px;
	height: 21px;
	background: rgb( 235, 235, 235 );
	-webkit-border-radius: 3px;
	-moz-border-radius: 3px;
	border-radius: 4px;
	line-height: 21px;
	text-align: center;
	color: #000;
	cursor: pointer;
	border: 1px solid rgb( 150, 150, 150 );
	font-size: 13px;
	font-weight: bold;
	padding-left: 10px;
	padding-right: 10px;
	}

.panelTable > tbody > tr td:not(:first-child) {
	//padding-left: 15px;
	}

.panelSubTitle {
	color: #FFF;
	margin-bottom: 5px;
	}
	
.panelTitle {
	color: #FFF;
	margin-bottom: 20px;
	}

.mainmenu {
	cursor: pointer;
	}

.mainmenu:not(:first-child) {
	margin-top: 4px;
	}

.issueMenuLine {
	
	}

.settingsPanel {
	position: fixed;
	display: none;	
	}

.loginDiv {
	position: absolute;
	left:50%;
    top:40%;
    margin:-96px 0 0 -158px;
	height: 192px;
	width: 316px;
	}

.floatMenu2 {
	width: 300px;
	padding-top: 50px !important;
	padding-bottom: 50px !important;
	}
	
.floatMenu3 {
	background-image: url(../client/images/floatBG2.png) !important;
	}	

.visitor_settingsPanel {
	position: fixed;
	padding: 14px 15px 15px 15px !important;		
	}

.floatMenuC {
	font-size: 13px;
	color: #FFF;
	background-image: url(../client/images/floatBG.png);
	background-color: rgb( 30, 30, 30 );
	padding: 7px 8px 8px 8px;
	z-index: 100;
	-webkit-box-shadow: 7px 7px 15px 0px rgba(50, 50, 50, 0.4);
	-moz-box-shadow:    7px 7px 15px 0px rgba(50, 50, 50, 0.4);
	box-shadow:         7px 7px 15px 0px rgba(50, 50, 50, 0.4);	
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;	
	}

.visitor_floatMenu, .floatMenu, .floatMenu2, .floatMenu3, .floatCommentMenu, .floatMenu_noclose {
	font-size: 13px;
	color: #FFF;
	background-image: url(../client/images/floatBG.png);
	background-color: rgb( 30, 30, 30 );
	padding: 7px 8px 8px 8px;
	z-index: 100;
	-webkit-box-shadow: 7px 7px 15px 0px rgba(50, 50, 50, 0.4);
	-moz-box-shadow:    7px 7px 15px 0px rgba(50, 50, 50, 0.4);
	box-shadow:         7px 7px 15px 0px rgba(50, 50, 50, 0.4);	
	-webkit-border-radius: 4px;
	-moz-border-radius: 4px;
	border-radius: 4px;
	}

.panelItem:not(:first-child) {
	margin-top: 4px;
	}

#logoMenu {
	position: fixed;
	display: none;
	left: 215px;
	top: 40px;
	width: 190px;
	}

.selected3 a {
	color: #444 !important;
	font-family: myriad_reg;
	}

.selected3 {
	background: #D6D6D6;
	}
	
.selected a {
	color: rgb( 255, 255, 255 ) !important;
	}

body a {
	text-decoration: none;
	}

ul.sub {
	height: 50px;
	width:100%;
	float:left;
	padding-left: 0px !important;
	}
	
ul.sub, ul.sub li, ul.sub li ul {
	list-style-type:none;
	margin:0px;
	padding:0px;
	}
	
ul.sub li {
	font-family: <?php echo $font; ?> !important;
	float:left;
	padding-left: 20px;
	padding-right: 20px;
	margin-left: 20px !important;
	font-size: 24px;
	position:relative;
	height: 50px;
	line-height: 50px;
	}

ul.sub a {
	color: #000;
    -webkit-transition: 0.3s;
    -moz-transition: 0.3s;
    -o-transition: 0.3s;
    -ms-transition: 0.3s;
    transition: 0.3s;	
	}

ul.sub a:hover {
	cursor: pointer;
	color: #B3AB39;
	text-shadow: #FFF 0px 0px 2px;
	}

ul.nav {
	width:100%;
	/* flex instead of float: items never wrap onto a second line and
	   reorder themselves - if they don't all fit even at the shrunk
	   sizing below, the row scrolls horizontally (see overflow-x)
	   instead of breaking. */
	display: flex;
	flex-wrap: nowrap;
	overflow-x: auto;
	overflow-y: hidden;
	padding-left: 0px !important;
	}

ul.nav, ul.nav li, ul.nav li ul {
	list-style-type:none;
	margin:0px;
	padding:0px;
	}

ul.nav li:first-child {
	padding-left: 13px !important;
	}

ul.nav li {
	font-family: <?php echo $font; ?> !important;
	flex: 0 0 auto;
	/* Fluid sizing so the whole bar keeps fitting one line as the window
	   narrows from the ~24" design width, instead of the fixed 20px/18px
	   only working above that width - but the two shrink at different
	   rates on purpose: padding does essentially all the work first
	   (18px down to 2px over a wide range), keeping the original 20px
	   letter size intact for as long as possible. font-size only starts
	   giving ground once padding is already near its floor, and even
	   then only over a narrow band down to a 13px minimum - a last
	   resort for the extreme end, not the first thing to shrink. */
	padding-left: clamp( 2px, -7px + 1.4vw, 18px );
	font-size: clamp( 13px, -8px + 2.5vw, 20px );
	margin-left:1px;
	position:relative;
	height: 50px;
	line-height: 53px;
	white-space: nowrap;
	}

ul.nav a {
	color: rgb( 180, 180, 180 );
    -webkit-transition: 0.3s;
    -moz-transition: 0.3s;
    -o-transition: 0.3s;
    -ms-transition: 0.3s;
    transition: 0.3s;	
	}

ul.nav a:hover {
	cursor: pointer;
	color: #DBDBDB;
	text-shadow: #FFF 0px 0px 1px;
	}