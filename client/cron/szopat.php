<?php
header('Content-Type: text/html; charset=utf-8');
include_once('../../engine/connect.php');
include_once('../../engine/engine.php');

$file = "teszt.pdf";
$path = 'C_Hotfolders/messages';

$sftp = ftp_conn("../");

for( $i = 1; $i <= 20; $i++ ) {
	$sftp->put( $path."/teszt_".$i.".pdf", $file, NET_SFTP_LOCAL_FILE );
	
	/*$ftp_server = "192.168.3.10";
	$ftp_conn = ftp_connect($ftp_server) or die("Could not connect to $ftp_server");
	$login = ftp_login($ftp_conn, "colorcom", "mocroloc" );
	ftp_put($ftp_conn, 'Teszt/TRK/teszt_'.$i.'.pdf', $file, FTP_BINARY);*/
	}
	
?>