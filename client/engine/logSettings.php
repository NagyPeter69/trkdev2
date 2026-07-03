<?PHP
	session_start();
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once('../../engine/engine.php');
	
	include_once('../lang/en.php');
	
	if( $_GET['op'] == "saveSettings" ) {
		$names = array();
		$columns = mysql_query("SHOW COLUMNS FROM userLogSettings");
		while ( $row = mysql_fetch_assoc( $columns ) ) {
			if( $row['Field'] != 'id' && $row['Field'] != 'user' )
				$names[] = $row['Field'];
			}
		
		$values = array();
		foreach( $_POST['cbox'] as $box ) {
			$values[] = $box['value'];
			}
		
		$command = array();
		foreach( $names as $name ) {
			if( in_array( $name, $values ) ) {
				$command[] = $name.'="1" ';
				}
			else {
				$command[] = $name.'="0" ';
				}
			}
		$command = implode( ", ", $command );
		sql_update( 'userLogSettings', $command, 'user="'.$_SESSION['intra_user'].'"' );
		sql_update( 'accounts', 'logtime="'.$_POST['time'].'"', 'id="'.$_SESSION['intra_user'].'"' );
		
		$logSettings = sql_aget( 'userLogSettings', 'user="'.$_SESSION['intra_user'].'" LIMIT 1', '*' );
		$logSettings = $logSettings[0];
		unset( $logSettings['id'] ); unset( $logSettings['user'] );
		
		$result = array();
		foreach( $logSettings as $name => $value ) {
			if( $value == 1 ) {
				array_push( $result, $name );
				}
			}
		}
	
print json_encode( $result );
	
?>