<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );

//ASSET Takarítás
$assets = sql_aget( "assets", "parent='0' order by id ASC", "*" );
for( $i = 0; $i < count( $assets ); $i++ ) {
	//echo "asset id: ".$assets[$i]["id"]."<br>";
	$pubcheck = sql_aget( "publications", "id='".$assets[$i]["pub_id"]."'", "*" );
	if ( empty( $pubcheck[0]["id"] ) ) {
		echo "Nem létező magazin!<br>";
		$dir = TRKPATH."/assets/".$assets[$i]["pub_id"];
		echo "könyvtár: ".$dir."<br>";
		if( is_dir( $dir ) ) {
			echo "Könyvtár törlése<br>";
			delTree($dir);
			}
		
		sql_delete( "assets", "parent='".$assets[$i]["id"]."'" );
		sql_delete( "assets", "id='".$assets[$i]["id"]."'" );
		echo "<br>";
		}
	}
	
$assets = load_dir_files( TRKPATH."/assets", "" );
for( $i = 0; $i < count( $assets ); $i++ ) {
	if( is_dir( TRKPATH."/assets/".$assets[$i] ) ) { 
		echo $assets[$i]."<br>";
		$pubcheck = sql_aget( "publications", "id='".$assets[$i]."'", "*" );
		if( empty( $pubcheck[0]["id"] ) ) {
			echo "Nem létező pub!<br>";
			delTree( TRKPATH."/assets/".$assets[$i] );
			}
		
		echo "<br>";
		}
	}
	
//Nem létező magazinok logjainak kitakarítása	
	$logs = sql_aget( "action_log", "publisher='0'", "*" );
	for( $i = 0; $i < count($logs); $i++ ) {
		sql_delete( "action_log", "id='".$logs[$i]["id"]."'" );
		}
		
	$logs = sql_aget( "action_log", "action != 'deleteIssue'", "*" );
	for( $i = 0; $i < count($logs); $i++ ) {
		$checker = sql_aget( "publications", "publisher_id='".$logs[$i]["publisher"]."' AND magazine_id='".$logs[$i]["magazine"]."' AND code='".$logs[$i]["issue"]."'", "*" );
		if( empty( $checker[0]["id"] ) ) {
			sql_delete( "action_log", "id='".$logs[$i]["id"]."'" );
			}
		}

	$logs = sql_aget( "comment_log", "1", "*" );
	for( $i = 0; $i < count($logs); $i++ ) {
		$checker = sql_aget( "publications", "publisher_id='".$logs[$i]["publisher"]."' AND magazine_id='".$logs[$i]["magazine"]."' AND code='".$logs[$i]["issue"]."'", "*" );
		if( empty( $checker[0]["id"] ) ) {
			sql_delete( "comment_log", "id='".$logs[$i]["id"]."'" );
			//echo "nem létező magazin: ".$logs[$i]["publisher"].", ".$logs[$i]["magazine"].", ".$logs[$i]["issue"]."<br>";
			}
		}

	$logs = sql_aget( "error_log", "1", "*" );
	for( $i = 0; $i < count($logs); $i++ ) {
		$checker = sql_aget( "publications", "publisher_id='".$logs[$i]["publisher"]."' AND magazine_id='".$logs[$i]["magazine"]."' AND code='".$logs[$i]["issue"]."'", "*" );
		if( empty( $checker[0]["id"] ) ) {
			sql_delete( "error_log", "id='".$logs[$i]["id"]."'" );
			//echo "nem létező magazin: ".$logs[$i]["publisher"].", ".$logs[$i]["magazine"].", ".$logs[$i]["issue"]."<br>";
			}
		}

	$logs = sql_aget( "system_log", "1", "*" );
	for( $i = 0; $i < count($logs); $i++ ) {
		$checker = sql_aget( "publications", "publisher_id='".$logs[$i]["publisher"]."' AND magazine_id='".$logs[$i]["magazine"]."' AND code='".$logs[$i]["issue"]."'", "*" );
		if( empty( $checker[0]["id"] ) ) {
			sql_delete( "system_log", "id='".$logs[$i]["id"]."'" );
			//echo "nem létező magazin: ".$logs[$i]["publisher"].", ".$logs[$i]["magazine"].", ".$logs[$i]["issue"]."<br>";
			}
		}
	
//Nem létező magazinok hirdetéseinek kidobása	
	$filter = $remove = array();
	$dir2 = '../advertisements';
	//echo "kezdem<br>";
	function strstr_array($haystack, $arrayNeedles){
		foreach($arrayNeedles as $needle){
			if(strstr($haystack, $needle)) return true;
			}
		return false;    
		}
	
	$pub = sql_get( 'publications', '', '*' );
	for( $x = 0; $x < count( $pub ); $x++ ) {
		$magazine = sql_get( 'magazines', 'id="'.$pub[$x][2].'"', '*' );
		$filter[] = "_".$magazine[0][3]."_".$pub[$x][10]."_";
		}

	$dirFiles2 = load_dir_files( $dir2, "." );
	$db = 0;
	for( $z = 0; $z < count( $dirFiles2 ); $z++ ) {
		if( strstr_array( $dirFiles2[$z], $filter ) === false ) {
			echo "removed magazine ads removing: ".$dir2.'/'.$dirFiles2[$z].'<br>';
			$db++;
			unlink( $dir2.'/'.$dirFiles2[$z] );  
			}
		}

	$ads = sql_get( 'ads', '', '*' );
	for( $x = 0; $x < count( $ads ); $x++ ) {
		$pub = sql_get( 'publications', 'id="'.$ads[$x][1].'"', '*' );
		$magazine = sql_get( 'magazines', 'id="'.$pub[0][2].'"', '*' );
		switch( $ads[$x][3] ) {
			case '2/1':
				$type = 'D';
				break;
			case '1/1':
				$type = 'F';
				break;
			default:
				$type = 'P';
				break;
			}
		
		$checker = strtoupper( $ads[$x][2] ).'_'.$magazine[0][3].'_'.$pub[0][10];
		$file_name = strtoupper( $ads[$x][2] ).'_'.$magazine[0][3].'_'.$pub[0][10].'_'.$type;
		
		$dirFiles2 = load_dir_files( $dir2, $checker );
		for( $z = 0; $z < count( $dirFiles2 ); $z++ ) {
			$secu = explode( '_', $dirFiles2[$z] );
			if( strtoupper( $ads[$x][2] ) == strtoupper( $secu[0] ) ) {
				if ( !strstr( $dirFiles2[$z], $file_name ) ) { 
					echo "removing: ".$dir2.'/'.$dirFiles2[$z].'<br>';
					unlink( $dir2.'/'.$dirFiles2[$z] );        
					} 
				}
			}
		}

?>