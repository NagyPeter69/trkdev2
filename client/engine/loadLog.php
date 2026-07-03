<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once('../../engine/engine.php');
	
	include_once('../lang/en.php');
	
	$user = sql_get( 'accounts', 'id="'.$_SESSION['intra_user'].'"', '*' );
	$currentTime = time();
	$scale = strtotime( "-".($user[0][12])." min" );
	$div = $id = $logCode = array();
	
	$magazines = explode( ",", $user[0][21] );
	$magCount = count( $magazines );
	
	$names = array();
	$sqlGet = array();
	$columns = sql_aget( 'userLogSettings', 'user="'.$user[0][0].'"', '*' );
	$columns = $columns[0];
	
	if( !empty( $columns ) ) {
		foreach( $columns as $key => $val ) {
			$names[ $key ] = $val;
			$sqlGet[] = $key;
			}
		}
		
	$criteria = "( action='".implode( "' OR action='", $sqlGet )."' )";
	$criteria = $criteria.' AND date > "'.$scale.'" ORDER BY `date` DESC';
	$colors = array( 
		'uploadAD' => '#fe7131',
		'newArticle' => '#cd4495',
		'backArticle' => '#cd4495',
		'newPage' => '#007ce9',
		'updatePage' => '#9a64a1',
		'newComment' => '#00c3ed',
		'newCReply' => '#dd9e55',
		'approveComment' => '#00b75e',
		'approvePage' => '#399C58',
		'rejectedPage' => '#CA4139',
		'revertedPage' => '#FFA3F9'
		);
 
	$return = array();	
	$logs = sql_get( 'action_log', $criteria, '*' );
	$logs2 = sql_get( 'comment_log', $criteria, '*' );
	$logs = array_merge_recursive( $logs, $logs2 );
	$txt = "";
	for( $i = 0; $i < count( $logs ); $i++ ) {
		if( in_array( $logs[$i][4], $magazines ) ) {
			$id[] = "log_".$logs[$i][0];
			if( $names[ $logs[$i][2] ] != '0' ) {
				$display = 'block';
				}
			else {
				$display = 'none';
				}
				
			$txt .= "<div id='log_".$logs[$i][0]."' class='logEntry' style='display: ".$display."; border-left: 5px solid ".$colors[ $logs[$i][2] ].";' logcode='".$logs[$i][2]."'>";
				$txt .= logToString( $logs[$i], $lang, $magCount );
			$txt .= "</div>";
			}
		}
	
print json_encode( array( $txt, $id, $logCode ) );
	
?>
