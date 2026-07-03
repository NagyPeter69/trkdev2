<?php
	header("Content-type: text/css");

	$font = 'Arial';
	$font = 'myriad';
	if( strpos( $_SERVER['HTTP_USER_AGENT'], "Mac" ) != 0 ) {
		$font = 'myriad';
		}
?>

@font-face {
	font-family: myriad;
	src: url(../fonts/Roboto-Light.ttf);
	}
@font-face {
	font-family: myriad_bold;
	src: url(../fonts/Roboto-Regular.ttf);
	}
@font-face {
	font-family: myriad_thin;
	src: url(../fonts/Roboto-Thin.ttf);
	}
@font-face {
	font-family: myriad_italic;
	src: url(../fonts/myriad_it.otf);
	}

.selectedAd {
	background: #58e5f5 !important;
	}

.double_portrait_ad_icon {
	width: 23px !important;
	}
	
.negyed_ad_blank_icon {
	width: 10px;
    height: 16px;
    background: #FFF;
    margin-top: -5px;
    cursor: pointer;
	float: left;
    margin-left: 4px;	
	}

.landscape_ad_blank_icon {
	height: 10px;
    width: 29px;
    background: #FFF;
    cursor: pointer;
    margin-left: 4px;
	}

.portrait_ad_blank_icon {
	width: 10px;
    height: 29px;
    background: #FFF;
    margin-top: -5px;
    cursor: pointer;
	float: left;
    margin-left: 4px;
	}

.thumbdragg {
	width: 81px;
	height: 99px;
	background: #58e5f5;
	display: none;
	}

#planner-line {
	height: 50px;
	width: 100%;
	background-color: #757575;
	}

#pasteboard_box {
	position: relative;
	width: 400px;
	display: inline-block;
	//background: #000;
	min-height: 99%;
	}

.dropzone {
	background: #58e5f5 !important;
	}

.plannerDropLineBox {
	z-index: 200;
	position: absolute;
	width: 40px;
	position: absolute;
	height: 118px;
	display: none;
	}

.plannerDropLine {
	z-index: 200;
	box-shadow: 0px 0px 3px 2px rgba(97,237,255,1);
	background-color: #58e5f5;
	display: inline-block;
	float: left;
	height: 115px;
	width: 1px;
	position: relative;
	display: none;
	
	}

.box__file,
.box__button {
	display: none;
	}

.box__dragndrop,
.box__uploading,
.box__success,
.box__error {
  
}

.box.has-advanced-upload {
  background-color: #c1c1c1;
  outline-offset: -2px;
}

.box.has-advanced-upload .box__dragndrop {
  display: inline;
}

.box.is-dragover {
  background-color: grey;
}

.box.is-uploading .box__input {
  visibility: none;
}
.box.is-uploading .box__uploading {
  display: block;
}

.ad_info {
	font-size: 14px !important;
	}

.userSettingsLine {
    display: block;
    height: 1px;
    border: 0;
    border-top: 1px solid #ccc;
    margin: 1em 0;
    padding: 0; 	
	}

.deliver_table thead td, .image_map_table thead td {
	font-weight: normal !important;
	font-size: 14px !important;
	}

.deliver_table tbody td, .image_map_table tbody td {
	font-size: 14px !important;
	}

.deliver_table {
	width: 200px;
	max-width: 200px;
	min-width: 200px;		
	}

.image_map_table {
	width: 600px;
	max-width: 600px;
	min-width: 600px;
	}

.deliver_table thead tr td, .image_map_table thead tr td {
	padding: 1px 1px;
	border-bottom: 1px solid #666;
	}

.deliver_table tbody tr:hover td, .image_map_table tbody tr:hover td {
	background-color: #FFF8CC;
	}

.deliver_table tbody tr td, .image_map_table tbody tr td {
	}

.image_map_table tbody {
    display:block;
    height:500px;
    overflow:auto;	
	}

.deliver_table tbody tr td, .image_map_table tbody tr td {
	padding-top: 4px;
	padding-bottom: 4px;
	border-bottom: 1px solid #CCC;	
	}

.deliver_table tbody tr:last-child td, .image_map_table tbody tr:last-child td {
	padding-top: 4px;
	border: none;
	}

.deliver_table tbody tr:nth-child(1) td, .image_map_table tbody tr:nth-child(1) td {
	padding-top: 4px;
	}

#imgBody tr td:nth-child(2) {
	padding-left: 2px !important;
	}

.image_map_table tbody tr td:nth-child(1) {
	width: 427px;
	min-width: 427px;
	max-width: 427px;
	}

.image_map_table tbody tr td:nth-child(2) {
	width: 86px;
	min-width: 86px;
	max-width: 86px;
	}

.image_map_table tbody tr td:nth-child(3) {
	width: 78px;
	min-width: 78px;
	max-width: 78px;
	}

.image_map_table thead {
	width: calc( 100% - 1em ) !important;
	}

.image_map_table thead, .image_map_table tbody tr {
    display:table;
    width:100%;
    table-layout:fixed;
	}

.image_map_table tr td:nth-child(1), .deliver_table tr td:nth-child(1) {
	padding: 0px 5px;
	padding-right: 0px !important;
	padding-left: 0px !important;
	}
	
.image_map_table td, .deliver_table td {
	padding: 0px 5px;
	}

.image_map_table thead td, .deliver_table thead td {
	font-size: 15px;
	padding: 3px 5px !important;
	}

.pub_button {
	float: right;
	margin-right: 20px;
	line-height: 30px;
	color: #AAAAAA;
	cursor: pointer;
	font-size: 14px !important;
   	-webkit-transition: all 0.22s ease-out;
   	-moz-transition:    all 0.22s ease-out;
   	-ms-transition:     all 0.22s ease-out;
   	-o-transition:      all 0.22s ease-out;
	}

.pub_button:hover {
	color: #444;
	}

.xml_name {
	width: 50%;
	text-align: right;
	padding-right: 15px;
	}

.xml_value {
	width: 50%;
	text-align: left;
	padding-left: 15px;
	}

#spacer {
	background: transparent;
	height: 45px;
	}

.job_line {
	padding-top: 10px;
	padding-bottom: 7px;
	}

.top {
	border-top: 1px solid #E3E3E3 !important;
	}

.bottom2 {
	border-bottom: 2px solid #E3E3E3 !important;	
	}
	
.bottom {
	border-bottom: 1px solid #E3E3E3 !important;	
	}
	
.left {
	border-left: 1px solid #E3E3E3 !important;
	}

.right {
	border-right: 1px solid #E3E3E3 !important;
	}
	
.one {
	background: rgb( 245, 245, 245 );
	}

.two {
	background: rgb( 235, 235, 235 ) !important;
	}

.deadline {
	background: rgb(169,3,41); /* Old browsers */
	/* IE9 SVG, needs conditional override of 'filter' to 'none' */
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iI2E5MDMyOSIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjQ0JSIgc3RvcC1jb2xvcj0iIzhmMDIyMiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiM2ZDAwMTkiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top, rgba(169,3,41,1) 0%, rgba(143,2,34,1) 44%, rgba(109,0,25,1) 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(169,3,41,1)), color-stop(44%,rgba(143,2,34,1)), color-stop(100%,rgba(109,0,25,1))); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top, rgba(169,3,41,1) 0%,rgba(143,2,34,1) 44%,rgba(109,0,25,1) 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top, rgba(169,3,41,1) 0%,rgba(143,2,34,1) 44%,rgba(109,0,25,1) 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top, rgba(169,3,41,1) 0%,rgba(143,2,34,1) 44%,rgba(109,0,25,1) 100%); /* IE10+ */
	background: linear-gradient(to bottom, rgba(169,3,41,1) 0%,rgba(143,2,34,1) 44%,rgba(109,0,25,1) 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#a90329', endColorstr='#6d0019',GradientType=0 ); /* IE6-8 */
	}

#job_names {
	margin: 0 auto;
	}

#jobs table td {
	height: 20px;
	}

/*#timeline div {
	overflow: hidden !important;
	}*/
/*
#timeline table td {
	height: 20px;
	width: 32px;
	position: relative;
	}
*/
.calendar_event {
	position: absolute;
	z-index: 10;
	top: -9px;
	width: 39px;
	height: 17px;
	padding-right: 3px;
	padding-left: 3px;
	padding-top: 3px;
	padding-bottom: 3px;
	font-size: 15px;
	color: #ffffff;
	overflow: hidden !important;

	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
	}

img, map, area, input, textarea, select {
	outline: 0;
	}

body {
	margin:0px;
	padding: 0 !important;
	font-family: <?php echo $font; ?>;
	overflow: hidden;
	letter-spacing: 0px;
	outline: 0 !important;
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;	
	}

body a {
	text-decoration: none;
	}

body b {
	font-weight: normal;
	font-family: myriad_bold;
	}

body i {
	text-decoration: none;
	font-weight: normal;
	}

.red {
	color: #BB0000 !important;
	}
	
#timeline table{
	border-collapse: collapse;
	}

.tline:not(#tl) tbody > tr > td:not(:first-child) {
	border-left: 1px solid #E0E0E0 !important;
	}

#content tbody td a {
	color: #000;
	}

#content thead td{
	font-weight: bold;
	padding-top: 10px;
	padding-bottom: 7px;
	//background: #ffffff;
	color: #555;
	}

#creator {
	width: 100%;
	height: 100%;
	background-color: rgb( 128, 128, 128 );
	}	
	
#content {
	position: relative;
	background: rgb( 200, 200, 200 );
    text-align: center;
	}

#sub_menu {
	height: 50px;
	background: rgb(229,229,229);
	margin-left: -30px;
	margin-right: 20px;
	margin-top: -20px;
	margin-bottom: 20px;
	}

#menu {
	height: 50px;
	background: rgb(72,72,70); /* Old browsers */
	background: url(data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiA/Pgo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgdmlld0JveD0iMCAwIDEgMSIgcHJlc2VydmVBc3BlY3RSYXRpbz0ibm9uZSI+CiAgPGxpbmVhckdyYWRpZW50IGlkPSJncmFkLXVjZ2ctZ2VuZXJhdGVkIiBncmFkaWVudFVuaXRzPSJ1c2VyU3BhY2VPblVzZSIgeDE9IjAlIiB5MT0iMCUiIHgyPSIwJSIgeTI9IjEwMCUiPgogICAgPHN0b3Agb2Zmc2V0PSIwJSIgc3RvcC1jb2xvcj0iIzQ4NDg0NiIgc3RvcC1vcGFjaXR5PSIxIi8+CiAgICA8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0b3AtY29sb3I9IiM4NTg1ODMiIHN0b3Atb3BhY2l0eT0iMSIvPgogIDwvbGluZWFyR3JhZGllbnQ+CiAgPHJlY3QgeD0iMCIgeT0iMCIgd2lkdGg9IjEiIGhlaWdodD0iMSIgZmlsbD0idXJsKCNncmFkLXVjZ2ctZ2VuZXJhdGVkKSIgLz4KPC9zdmc+);
	background: -moz-linear-gradient(top, rgba(72,72,70,1) 0%, rgba(133,133,131,1) 100%); /* FF3.6+ */
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,rgba(72,72,70,1)), color-stop(100%,rgba(133,133,131,1))); /* Chrome,Safari4+ */
	background: -webkit-linear-gradient(top, rgba(72,72,70,1) 0%,rgba(133,133,131,1) 100%); /* Chrome10+,Safari5.1+ */
	background: -o-linear-gradient(top, rgba(72,72,70,1) 0%,rgba(133,133,131,1) 100%); /* Opera 11.10+ */
	background: -ms-linear-gradient(top, rgba(72,72,70,1) 0%,rgba(133,133,131,1) 100%); /* IE10+ */
	background: linear-gradient(to bottom, rgba(72,72,70,1) 0%,rgba(133,133,131,1) 100%); /* W3C */
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#484846', endColorstr='#858583',GradientType=0 ); /* IE6-8 */
	margin-left: 30px;
	margin-right: 30px;
	}

#menuLine {
	position: absolute;
	left: 229px;
	}

#freespace {
	
	}

#publist {
	margin-right: 6px !important;
	}
	
#menuClientBox {
	margin-top: -1px !important;
	}

#member, #freespace, #publist, #menuClientBox {
	float: right;
	margin-right: 19px;
	font-size: 15px;
	color: rgb( 180, 180, 180 );
	line-height: 50px;
	margin-top: 1px;
	}
	
#header {
	height: 50px;
	width: 100%;
	position: relative;
	font-size: 20px;
	color: #777;
	background: rgb( 89, 89, 89 );
	}	
#header .logo {
	position: absolute;
	}
	
#box_wrapper {
	padding-top: 10px;
	margin: auto;
	width: 430px;
	}
	
#msg_box, #msg_box2 {
	margin-top: -10px;
	padding-top: 10px;
	padding-bottom: 10px;
	margin-bottom: 20px;	
	display: none;
	width: 430px;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;
	border-radius: 5px;
	-webkit-box-shadow: 0px 0px 10px rgba(50, 50, 50, 0.75);
	-moz-box-shadow:    0px 0px 10px rgba(50, 50, 50, 0.75);
	box-shadow:         0px 0px 10px rgba(50, 50, 50, 0.75);
	}

.success {
	background: #B6FF91;
	}
	
.failed {
	background: #FCAEAE;
	}

@media only screen and (max-width: 1435px) {
	.image_map_table tbody {
		height: 200px !important;
		}
	}
	
@media all and (orientation:portrait) {
	.planner {
		display: none;
		}
	}