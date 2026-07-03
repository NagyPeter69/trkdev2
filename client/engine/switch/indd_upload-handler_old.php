<?PHP

$jcode = $_POST["jobCode"];
$issue = $_POST["issue"];
$remark = $_POST["remark"];
$description = $_POST["description"];	

$name = nameCalculator2( $_POST );
$found = 0;

$p_id = sql_get( 'magazines', 'code="'.$jcode.'"', '*' );
$p_code = $p_id[0][3];

$p_id = sql_get( 'publications', 'magazine_id="'.$p_id[0][0].'" AND code="'.$issue.'"', '*' );
$packages = sql_get( 'packages', 'publication_id="'.$p_id[0][0].'"', '*' );
for( $x = 0; $x < count( $packages ); $x++ ) {	
	if( strtolower( $packages[$x][2] ) == strtolower( $name ) ) {
		$found = 1;
		$target = $packages[$x][0];
		break;
		}	
	else {
		$found = 0;
		}
	}

if( $found == 0 ) {
	for( $x = 0; $x < count( $packages ); $x++ ) {	
		if( preg_match( "/".$packages[$x][2]."/i", $name ) && !strstr( $name, "_" ) ) {
			$found = 1;
			$target = $packages[$x][0];
			break;
			}
		else {
			$found = 0;
			}
		}
	}

if( $found == 0 ) {
	for( $x = 0; $x < count( $packages ); $x++ ) {
		$tar = explode( "_", $packages[$x][2] );
		foreach( $tar as $t ) {
			if( preg_match( "/".$name."/i", $t ) ) {
				$found = 1;
				$target = $packages[$x][0];
				break 2;
				}
			}
		}
	}
	
if( $found == 1 ) {
	$names = array( 'status', 'status_changed' );
	$values = array( 1, time() );

	$command = '';
	for( $a = 0; $a < count( $names ); $a++ ) {
		$command .= $names[$a].'=\''.$values[$a].'\'';

		if( $a < count( $names )-1 ) {
			$command .= ', ';
			}
		}
		
	$names = array( 'user', 'action', 'publisher', 'magazine', 'issue', 'target', 'date', 'status' );
	$values = array( '', 'backArticle', $p_id[0][1], $p_id[0][2], $p_id[0][10], $name, time(), '' );
	sql_add( 'action_log', $names, $values );

	sql_update( 'packages', $command, 'id=\''.$target.'\'' );
	}
	
?>