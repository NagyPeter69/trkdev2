<?
	set_include_path(__DIR__);
	chdir(__DIR__);
	header('Content-Type: text/html; charset=utf-8');

	include_once( '../../engine/connect.php' );
	include_once( '../../engine/engine.php' );

	$dir = '../uploads/adhoc';
	$up_to = 'C_Hotfolders/messages';
	
	$dirFiles = load_dir_files( $dir, '.xml' );
	sort( $dirFiles );
	
	include_once( 'Net/SFTP.php');
	$sftp = new Net_SFTP('192.168.1.8');
	if (!$sftp->login('admin', 'awareness')) {
		$sftp = new backup;
		}
	
	$allowed = array( 'tif', 'tiff', 'pdf', 'jpg', 'jpeg', 'zip', 'psd' );
	
	$xml_files = array();
	for( $i = 0; $i < count( $dirFiles ); $i++ ) {
		$ok = 0;
		$name = substr( $dirFiles[$i], 0, -4);
		for( $y = 0; $y < count( $allowed ); $y++ ) {
			if( is_file( $dir.'/'.$name.'.'.$allowed[$y] ) ) {
				$ext = $allowed[$y];
				$ok = 1;
				break;
				}
			}
		
		if( $ok == 1 ) {
			if( $sftp->put( $up_to.'/'.$name.'.'.$ext , $dir.'/'.$name.'.'.$ext, NET_SFTP_LOCAL_FILE) ) {
				unlink( $dir.'/'.$name.'.'.$ext );
				}
			if( $sftp->put( $up_to.'/'.$name.'.xml' , $dir.'/'.$name.'.xml', NET_SFTP_LOCAL_FILE) ) {
				unlink( $dir.'/'.$name.'.xml' );
				}
			}
		}
	
	$dir = '../uploads/ads';
	$up_to = 'C_Hotfolders/messages';
	
	$dirFiles = load_dir_files( $dir, '.xml' );
	sort( $dirFiles );
	
	include_once( 'Net/SFTP.php');
	$sftp = new Net_SFTP('192.168.1.8');
	if (!$sftp->login('admin', 'awareness')) {
		$sftp = new backup;
		}
	
	$allowed = array( 'jpg', 'jpeg', 'tif', 'tiff', 'pdf' );
	
	$xml_files = array();
	for( $i = 0; $i < count( $dirFiles ); $i++ ) {
		$ok = 0;
		$name = substr( $dirFiles[$i], 0, -4);
		for( $y = 0; $y < count( $allowed ); $y++ ) {
			if( is_file( $dir.'/'.$name.'.'.$allowed[$y] ) ) {
				$ext = $allowed[$y];
				$ok = 1;
				break;
				}
			}
		
		if( $ok == 1 ) {
			$counter = get_counter('..');
			if( $sftp->put( $up_to.'/message_'.$counter.'.'.$ext , $dir.'/'.$name.'.'.$ext, NET_SFTP_LOCAL_FILE) ) {
				unlink( $dir.'/'.$name.'.'.$ext );
				}
			if( $sftp->put( $up_to.'/message_'.$counter.'.xml' , $dir.'/'.$name.'.xml', NET_SFTP_LOCAL_FILE) ) {
				unlink( $dir.'/'.$name.'.xml' );
				}
			inc_counter('..');
			}
		}

	$dir = '../uploads/orders';
	$up_to = 'C_Hotfolders/messages';
	
	$dirFiles = load_dir_files( $dir, '.xml' );
	sort( $dirFiles );
	
	include_once( 'Net/SFTP.php');
	$sftp = new Net_SFTP('192.168.1.8');
	if (!$sftp->login('admin', 'awareness')) {
		$sftp = new backup;
		}
		
	for( $i = 0; $i < count( $dirFiles ); $i++ ) {
		$counter = get_counter('..');
		$sftp->put( $up_to.'/message_'.$counter.'.pdf' , '../engine/dummy.pdf', NET_SFTP_LOCAL_FILE);
		if( $sftp->put( $up_to.'/message_'.$counter.'.xml' , $dir.'/'.$dirFiles[$i] , NET_SFTP_LOCAL_FILE) ) {
			@unlink( $dir.'/'.$dirFiles[$i] );
			}
		inc_counter('..');
		}

	/*$dir = '../xml';
	$up_to = 'C_Database';
	
	$dirFiles = load_dir_files( $dir, '.xml' );
	sort( $dirFiles );
	
	$sftp = ftp_conn();
	for( $i = 0; $i < count( $dirFiles ); $i++ ) {
		$sftp->put( $up_to.'/Publications_Master_Data.xml' , $dir.'/'.$dirFiles[$i] , NET_SFTP_LOCAL_FILE);
		}*/

	
?>