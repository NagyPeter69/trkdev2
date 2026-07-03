<?PHP
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );
	include_once( '../../engine/xml_handler.php' );
	
	include_once('../lang/hu.php');
	$rights = array();
	if( isset( $_GET['intra_user'] ) ) {
		$user = sql_get( 'accounts', 'id="'.$_GET['intra_user'].'"', '*' );
		$r = sql_aget( 'user_groups', 'id="'.$user[0][8].'"', '*' );
		foreach( $r[0] as $key => $val ) {
			$rights[$key] = $val;
			}
		}
	
	if( $_GET['op'] == 'refreshComments' ) {
		//error_log( "DEBUG: ".$user[0][4] );
		$myPublisher = sql_get( 'publishers', 'id="'.$user[0][4].'"', '*' );
		$img = array();
		$txt = "";
		$comments = array();
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$pack = sql_get( 'packages', 'id="'.$_GET['pack_id'].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
		$max = intval( $issue[0][6] )+1;
		$checker = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND page="'.( intval( $_GET['p'] )+1 ).'" AND part="'.$_GET["part"].'"', '*' );
		
		if( $_GET['pgs'] == '2') {
			$pages = array( intval( $_GET['p'] ) );
			if( bcmod( intval( $_GET['p'] ), 2 ) == 0 && $checker[0][9] == 1 ) {
				if( $_GET['alter'] != '' ) {
					$pages[] = intval( $_GET['p'] )+1;
					}
				else {
					if( ( intval( $_GET['p'] )+1 ) <= $max ) {
						$pages[] = intval( $_GET['p'] )+1;
						}
					}
				}
			}
		else {
			$pages = array( intval( $_GET['clicked'] ) );
			}
			
		$debug = $_GET['clicked'];
		
		if( $_GET['alter'] == "" ) $_GET['alter'] = "NOR";
		foreach( $pages as $page ) {
			if( $magazine[0][14] == "Yes" ) {
				$sqlComments = sql_get( 'comments', 'pageType="'.$_GET['alter'].'" AND pub_id="'.$_GET['id'].'" AND page="'.$page.'" AND parent="0" AND status!="approved" AND pageVersion="'.$_GET['tag'].'" AND part="'.$_GET["part"].'"', '*' );
				}
			else {
				$sqlComments = sql_get( 'comments', 'pageType="'.$_GET['alter'].'" AND pub_id="'.$_GET['id'].'" AND page="'.$page.'" AND parent="0" AND pageVersion="'.$_GET['tag'].'" AND part="'.$_GET["part"].'"', '*' );
				
				}
			for( $c = 0; $c < count( $sqlComments ); $c++ ) {
				$checkPub = sql_get( 'comments', 'parent="'.$sqlComments[$c][0].'"', '*' );
				if( count($checkPub) > 0 ) {
					$checker = $checkPub[count($checkPub)-1][10];
					}
				else {
					$checker = $sqlComments[$c][10];
					}
					
				$checkPub = sql_get( 'accounts', 'id="'.$checker.'"', '*' );
				$checkPub = sql_get( 'publishers', 'id="'.$checkPub[0][4].'"', '*' );
				if( $checkPub[0][0] != $myPublisher[0][0] && $sqlComments[$c][12] != "approved" )
					$sqlComments[$c][] = "comment3";
				else
					$sqlComments[$c][] = "";
				
				$user = sql_get( "accounts", "id='".$sqlComments[$c][10]."'", "full_name" );

				$haveAuto = false;
				$check_last_comment = sql_get( 'comments', 'parent="'.$sqlComments[$c][0].'" ORDER BY `id` DESC LIMIT 1', '*' );
				if( count( $check_last_comment ) > 0 ) {
					$checker = $check_last_comment[count($check_last_comment)-1][10];
					}
				else {
					$checker = $sqlComments[$c][10];
					}

				$checkPub = sql_get( 'accounts', 'id="'.$checker.'"', 'publisher, id' );
				//error_log( $checkPub[0][0]." != ".$myPublisher[0][0] );
				if( $checkPub[0][0] != $myPublisher[0][0] ) {
					$result["red"]++;
					}
				elseif( $checkPub[0][0] == $myPublisher[0][0] ) {
					$result["green"]++;
					}		
				
				if( $result["red"] > 0 ) {
					$haveAuto = true;
					}
				

				$sqlComments[$c][8] = "<table cellspacing='0' cellpadding='0'><tr><td><div style='margin-bottom:3px;' onclick='selectAllText( this )' class='ctext'>".$sqlComments[$c][8]."</div>
										<div style='font-size:9px; color: rgb( 255, 234, 0 );'><i>".$user[0][0]."</i></div>
										<div style=\"font-size: 9px; color: #889FBF; margin-right: 13px; padding-top: 1px;\">".date( "d F, Y, H:i" , strtotime( $sqlComments[$c][9] ) )."</div>
										</td><td valign='bottom' align='right'>";
										
					if( $haveAuto && $myPublisher[0][1] == "Colorcom" )	{
						$sqlComments[$c][8] .= "<div class='autoReply' onclick=\"autoreply( ".$sqlComments[$c][0]." )\"><i class='fas fa-check'></i></div>";
						}
						
					$sqlComments[$c][8] .= "<div class='commentMenu' onclick=\"myContextMenu('comment', 'comment_".($c+1)."_".$sqlComments[$c][7]."_text' )\"></div>
										</td></tr></table>";
					
				$comments[] = implode( "^", $sqlComments[$c] );			
				}
			}
		$result = array( implode( "|", $comments ), $debug );		
		}
	
	if( $_GET['op'] == 'loadPages' ) {
		$myPublisher = sql_get( 'publishers', 'id="'.$user[0][4].'"', '*' );
		$img = array();
		$txt = "";
		$comments = array();
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$pack = sql_get( 'packages', 'id="'.$_GET['pack_id'].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );
		$max = intval( $issue[0][6] )+1;
		$checker = sql_get( 'pageinfo', 'issue="'.$issue[0][10].'" AND code="'.$magazine[0][3].'" AND page="'.( intval( $_GET['p'] )+1 ).'"', '*' );
		
		$pages = array( intval( $_GET['p'] ) );
		if( bcmod( intval( $_GET['p'] ), 2 ) == 0 && $checker[0][9] == 1 ) {
			if( $_GET['alter'] != '' && $_GET['alter'] != 'FIN' ) {
				$pages[] = intval( $_GET['p'] )+1;
				}
			else {
				if( ( intval( $_GET['p'] )+1 ) <= $max ) {
					$pages[] = intval( $_GET['p'] )+1;
					}
				}
			}
		
		foreach( $pages as $page ) {
			$file2 = $tag = '';
			if( $_GET['alter'] != "" && $_GET['alter'] != 'FIN' ) {
				$dir = "../packages/".$magazine[0][3]."/".$issue[0][10]."/_".strtoupper( $_GET['alter'] );
				if( $_GET['tag'] != "" && $page == $_GET['clicked'] ) {
					$tag = $_GET['tag'];
					$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$page.'"', '*' );
					}
				else {
					$pageinfo = sql_get( 'pageinfo', 'type="'.$_GET['alter'].'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$page.'"', '*' );
					}
				$file2 = str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
				}
			else {
				if( $_GET['tag'] != "" && $page == $_GET['clicked'] ) {
					$tag = $_GET['tag'];
					$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state="'.$_GET['tag'].'" AND page="'.$page.'"', '*' );
					}
				else {
					$pageinfo = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND page="'.$page.'"', '*' );
					}
				if( $pageinfo[0][6] == "ad" ) {
					$dir = "../packages/".$magazine[0][3]."/".$issue[0][10]."/_ads";
					$file2 = str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."ad_preview.pdf";
					}
				else {
					$pack = sql_get( 'packages', 'id="'.$pageinfo[0][1].'"', '*' );
					$dir = "../packages/".$magazine[0][3]."/".$issue[0][10]."/".$pack[0][4];
					$file2 = str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$pageinfo[0][1]."_".$tag."preview.pdf";
					}
				}

			$path = $dir."/".$file2;
			$debug .= $dir."/".$file2."<br>";
			if( is_file( $dir."/".$file2 ) ) {
				$viewed = explode( ",", $pageinfo[0][10] );
				if( !in_array( $_GET['intra_user'], $viewed ) )
					if( $viewed[0] == "" )
						$viewed[0] = $_GET['intra_user'];
					else
						$viewed[] =  $_GET['intra_user'];
					
				$viewed = implode( ",", $viewed );
				sql_update( 'pageinfo', 'view="'.$viewed.'"', 'id="'.$pageinfo[0][0].'"' );
					
				$img[] = PdfToImageRender( $path, "../temp", $file2."_".$_GET['intra_user'] );
				}
			}
			
		if( $img[0] != '' ) {	
			list($width, $height) = getimagesize( $img[0] );
			if( $img[1] != '' ) {
				$newWidth = $width*2;
				}
			else {
				$newWidth = $width;
				}
		
			$dest = imagecreatetruecolor( $newWidth, $height );
			$src = imagecreatefromjpeg( $img[0] );
			imagecopymerge($dest, $src, 0, 0, 0, 0, $width, $height, 100);
		
			if( $img[1] != '' ) {
				$src2 = imagecreatefromjpeg( $img[1] );
				imagecopymerge($dest, $src2, $width, 0, 0, 0, $width, $height, 100);
				}
			
			imagejpeg( $dest, $img[0], 100 );
		
			imagedestroy($dest);
			imagedestroy($src);
			$img[0] = str_replace( "../", "", $img[0] );
		
			$txt .= "<img id='pagePreview' class='preview' src='imgviewer.php?path=".$img[0]."' height='99%'>";
			@unlink( $img[1] );
			}
		else {
			$txt .= "Nincs megjeleníthető eredmény.";
			}
		
		$result = array( $txt, $newWidth , $height, $width, count( $img ) );
		}
		
	print json_encode( $result );
	
?>