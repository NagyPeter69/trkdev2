<?php
// PSR-4 autoloader for the vendored ZipStream-PHP sources (src/), since this
// project doesn't use Composer - same manual-vendoring approach already used
// for PHPMailer in ../phpmail (see PHPMailerAutoload.php).
spl_autoload_register(function ( $class ) {
	$prefix = "ZipStream\\";
	if( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
		}

	$relative = substr( $class, strlen( $prefix ) );
	$file = __DIR__."/src/".str_replace( "\\", "/", $relative ).".php";

	if( is_readable( $file ) ) {
		require $file;
		}
	});
