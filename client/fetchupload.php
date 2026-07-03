<?php
session_start();
include_once( '../engine/connect.php' );
include_once( '../engine/engine.php');
include_once( TRKPATH."/engine/switchAPI.php" );

$target_chunk_path = 'uploads/blobtest/chunk/'.$_POST["tempdir"].'/';
$target_path = 'uploads/blobtest/';

if( !is_dir( 'uploads/blobtest/chunk' ) ) {
	$oldmask = umask(0);
	mkdir( 'uploads/blobtest/chunk', 0777);
	umask($oldmask);
	}

if( !is_dir( 'uploads/blobtest/chunk/'.$_POST["tempdir"] ) ) {
	$oldmask = umask(0);
	mkdir( 'uploads/blobtest/chunk/'.$_POST["tempdir"], 0777);
	umask($oldmask);
	}

$chunk_prefix = "chunk_";
$tmp_name = $_FILES['file']['tmp_name'];
$filename = $_FILES['file']['name'];

$target_chunk_file = $target_chunk_path.$chunk_prefix.$filename;
$target_chunk_merge = $target_chunk_path.$filename;
$target_file = $target_path.$filename;

$num = $_POST['num'];
$num_chunks = $_POST['num_chunks'];
$result = "";
if( move_uploaded_file($tmp_name, $target_chunk_file.$num) ) {
	$file = fopen($target_chunk_file.$num, 'rb');
	$buff = fread($file, filesize($target_chunk_file.$num) );
	fclose($file);

	$final = fopen($target_chunk_merge, 'ab');
	$write = fwrite($final, $buff);
	fclose($final);	
	unlink( $target_chunk_file.$num );

	if ($num === $num_chunks) {
		$result = $target_chunk_merge;
		print $result;
		}

	/*
	if ($num === $num_chunks) {
		$n = 1;
		while( is_file( $target_file) ) {
			$target_file = $target_path."(".$n.")".$filename;
			$n++;
			}
		
		$file = fopen($target_chunk_file.$num, 'rb');
		$buff = fread($file, filesize($target_chunk_file.$num) );
		fclose($file);
		
		$final = fopen($target_chunk_merge, 'ab');
		$write = fwrite($final, $buff);
		fclose($final);	
		
		copy( $target_chunk_merge, $target_file );
		//delTree( $target_chunk_path );
		
		$result = $target_file;
		print $result;
		}
	else {
		$file = fopen($target_chunk_file.$num, 'rb');
		$buff = fread($file, filesize($target_chunk_file.$num) );
		fclose($file);

		$final = fopen($target_chunk_merge, 'ab');
		$write = fwrite($final, $buff);
		fclose($final);	
		}
	*/

	}
?>