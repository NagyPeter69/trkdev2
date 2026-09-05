<?
	set_include_path(__DIR__);
	chdir(__DIR__);
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );

	$dir = '../uploads/backup_line';	
	$dirFiles = load_dir_files( $dir, '_' );
	sort($dirFiles);
	
	include_once( 'Net/SFTP.php');
	$sftp = new Net_SFTP('192.168.1.8');
	if (!$sftp->login('admin', 'awareness')) {
		$sftp = new backup;
		}
	
	for( $i = 0; $i < count( $dirFiles ); $i++ ) {
		$file = $dirFiles[$i];
		if( strstr( $file, 'message_' ) ) {
			$to = 'C_Hotfolders/messages/'.$file;
			}
		else {
			$to = 'C_Database/'.$file;
			}
		$from = $dir.'/'.$file;
		$return = $sftp -> put( $to, $from ,NET_SFTP_LOCAL_FILE );
		if( !strstr( $return, substr($from, 2 ) ) ) {
			@unlink( $from );
			}
		}


	$dir = '../uploads/backup_line_xml';	
	$dirFiles = load_dir_files( $dir, '_' );
	sort($dirFiles);
	
	include_once( 'Net/SFTP.php');
	$sftp = new Net_SFTP('192.168.1.8');
	if (!$sftp->login('admin', 'awareness')) {
		$sftp = new backup2;
		}
	
	for( $i = 0; $i < count( $dirFiles ); $i++ ) {
		$file = $dirFiles[$i];
		
		$to = 'C_Database/'.$file;
		$from = $dir.'/'.$file;
		$return = $sftp -> put( $to, $from ,NET_SFTP_LOCAL_FILE );
		if( !strstr( $return, substr($from, 2 ) ) ) {
			@unlink( $from );
			}
		}
	
?>