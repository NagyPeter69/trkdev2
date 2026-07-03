<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../../engine/connect.php' );
	include_once('../../lang/hu.php');

	function colorGenerate( $first ) {
		$return = array();
		$f = 220;
		while( $f >= 200 ) {
			$s = 210;
			while( $s >= 150 ) {
				$t = 210;
				while( $t >= 150 ) {
					switch( $first ) {
						case 'red':
							$return[] = str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT);
							break;
						case 'green':
							$return[] = str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT);
							break;
						case 'blue':
							$return[] = str_pad( dechex( $t ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $s ), 2, '0', STR_PAD_LEFT) . str_pad( dechex( $f ), 2, '0', STR_PAD_LEFT);
							break;
						}
					$t-=20;
					}
				$s-=20;
				}
			$f-=3;
			}
		return $return;
		}

	function sql_get( $table, $condition, $collect ) {
			$datas = array();
			$command = 'SELECT '.$collect.' FROM '.$table.'';
		
			if( $condition != '' )
				$command .= ' WHERE '.$condition.'';

			$command = mysql_query( $command );
			
			if( mysql_num_rows( $command ) > 1 ) {
				while( $row = @mysql_fetch_row( $command ) ) {
					$datas[] = $row;
					}
				}
			else {
				$datas[] = @mysql_fetch_row( $command );
				}
			return $datas; 
			}
	
	function calculateSize( $pageInfo, $magazine, $issue ) {
		$dir = sql_get( 'packages', 'id="'.$pageInfo[1].'"', 'name, directory, id' );
		$file = $dir[0][0]."/".str_pad( $pageInfo[5], 3, '0', STR_PAD_LEFT)."_".$dir[0][2]."_preview.jpg";
		$path = "../../packages/".$magazine."/".$issue;
		$w = 90;
		
		if( $pageInfo[0] != "" ) {
			list( $w2, $h2 ) = getimagesize( $path."/".$file );
			if( $w2 >= 90 ) {
				$percent = $w/$w2*100;
				$h = intval( $h2/100*$percent );
				}
			}
		else {
			$h = 110;
			}
		
		return array( $w, $h );
		}

	if( $_GET['op'] == 'loadPage' ) {
		$pageType = "normal";
		$page = $_GET['page'];
		$alters = array();
		$rPalette = colorGenerate( 'red' );
		$gPalette = colorGenerate( 'green' );
		$bPalette = colorGenerate( 'blue' );
		
		$issue = sql_get( 'publications', 'id="'.$_GET['id'].'"', '*' );
		$maxPage = intval( $issue[0][6] ) + 1;
		$magazine = sql_get( 'magazines', 'id="'.$issue[0][2].'"', '*' );

		$path = "../packages/".$magazine[0][3]."/".$issue[0][10];
		if( $pageType == "normal" )
			$fPage = sql_get( 'pageinfo', '(type="ad" OR type="magazine") AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state=""', '*' );
		else 
			$fPage = sql_get( 'pageinfo', 'type="'.$pageType.'" AND page="'.$page.'" AND code="'.$magazine[0][3].'" AND issue="'.$issue[0][10].'" AND state=""', '*' );
		
		if( $fPage != "" ) {
			if( $fPage[0][6] == "ad" ) {
				$file = "_ads/".str_pad( $page, 3, '0', STR_PAD_LEFT)."_".$fPage[0][1]."_ad_preview.jpg";
				$secBg = "background: #E09948 !important;";
				}
			}
		else {

			}
		
		$txt = "";
		
		$txt .= $fPage[0][0];
		
		$result = $txt;
		}
		
print json_encode( $result );
	
?>