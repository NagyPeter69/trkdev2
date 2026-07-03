<?
include_once('../../engine/connect.php');
include_once('../lang/en.php');
include_once('../../engine/engine.php');
include_once('../../engine/xml_handler.php');

echo "<pre>";
$pages = sql_aget( "pageinfo", "colors =  ''", "*" );

for( $i = 0; $i < count( $pages ); $i++ ) {
//for( $i = 0; $i < 1000; $i++ ) {
	$tag = $pages[$i]["state"];
	$magazine = sql_get( 'magazines', 'code="'.$pages[$i]["code"].'" LIMIT 1', '*' );
	$issue = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'" AND code="'.$pages[$i]["issue"].'" LIMIT 1', '*' );
	
	if( !empty( $issue[0][0] ) ) {
		$dir = TRKPATH."/packages/".$magazine[0][3]."/".$issue[0][10];
		if( $pages[$i]["type"] == "ad" ) {
			$dir .= "/_ads";
			$file = str_pad( $pages[$i]["page"], 3, '0', STR_PAD_LEFT)."_".$pages[$i]["pack_id"]."_".$tag."ad_preview.pdf";
			}
		else {
			$pack = sql_get( 'packages', 'id="'.$pages[$i]["pack_id"].'"', '*' );
			$dir .= "/".$pack[0][4];
			if( $pages[$i]["fin"] == "1" ) {
				$dir .= "/FIN";
				}
			
			$file = str_pad( $pages[$i]["page"], 3, '0', STR_PAD_LEFT)."_".$pages[$i]["pack_id"]."_".$tag."preview.pdf";
			}
		
		if( is_file( $dir."/".$file ) ) {
			$command = './r3 -mode:MEASURE -x:596 -y:760 -d:1 -r:600 -tprofile:ISOcoated_v2_eci.icc '.$dir.'/'.$file.' 2>&1';
			$command = shell_exec('
					cd /var/www/html/r3API/r3 2>&1;
					'.$command.';
					');

			
			var_dump( $command );
			sql_update( "pageinfo", "colors='".$command."'", "id='".$pages[$i]["id"]."'" );
			}
		}
	}

?>
