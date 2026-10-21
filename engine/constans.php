<?PHP
// The one switch between production and dev behaviour. Set per host in
// /etc/trkdev-db.env, never committed: ENVIRONMENT=production on the live
// box, ENVIRONMENT=developer on trkdev2 and any clone. Cron sources that
// file; php-fpm loads it via its systemd EnvironmentFile and passes it on
// with env[ENVIRONMENT] = $ENVIRONMENT (a restart, not a reload, picks up
// a change). Anything else - unset, a typo, the old TRKDEV_ENVIRONMENT -
// counts as developer, so a misconfigured host fails closed instead of
// sending real client jobs to Switch.
// Governs switchClientAllowed()/switchBulkSyncAllowed() in
// client/engine/switchAPI.php (TestCo only on developer) and
// pmdDevSafeName() in engine/xml_handler.php (_DEV suffix on developer).
define( "ENVIRONMENT", getenv( "ENVIRONMENT" ) === "production" ? "production" : "developer" );
define( "IS_DEV_ENVIRONMENT", ENVIRONMENT !== "production" );
if( IS_DEV_ENVIRONMENT && !in_array( getenv( "ENVIRONMENT" ), array( "developer" ), true ) ) {
	error_log( "constans.php: ENVIRONMENT is ".var_export( getenv( "ENVIRONMENT" ), true )." (expected 'production' or 'developer') - treating this host as developer" );
	}

define( "FPUPLOAD_PATH", "/var/www/html/client/flatplan_uploads" );
define( "ARCHIVE_PATH", "/var/www/html/archives" );
define( "PENZUGY", "ugyvitel@colorcom.hu" );
define( "TRKPATH", "/var/www/html/client" );
define( "XMLPATH", "/var/www/html/client/xml" );
// Local on-disk short name (client/xml/pmd.xml) vs. the long name Switch
// itself uses for the same dataset (Publications_Master_Data.xml) - see
// XMLUpload2()/pmdDevSafeName() in engine/xml_handler.php for how the two
// are bridged.
define( "PMD", "pmd" );
define( "PMD_LONG", "Publications_Master_Data" );
define( "DUMMYPDF", "/var/www/html/client/engine/dummy.pdf" );
define( "RENDERIP", "10.10.30.122");
define( "DYNAIP", "10.10.30.60" );
define( "PROTOCOL", "https://" );

//Mail beállítások
define( "MAIL_HOST", "mail.colorcom.hu" );
define( "MAIL_PORT", "465" );
define( "MAIL_USERNAME", "produkcio" );
define( "MAIL_PASS", getenv('TRKDEV_MAIL_PASSWORD') );
define( "MAIL_EMAIL", "produkcio@colorcom.hu" );
define( "MAIL_NAME", "Colorcom Workflow" );

define( "MAIL_WF_USERNAME", "produkcio" );
define( "MAIL_WF_PASS", getenv('TRKDEV_MAIL_WF_PASSWORD') );
define( "MAIL_WF_EMAIL", "produkcio@colorcom.hu" );
define( "MAIL_WF_NAME", "Colorcom Workflow" );
define( "HAVE_PARTS", array( "Full", "Hybrid" ) );
//Magazin beállítások
define( "PARTTYPES", array( "BEL", "BOR", "TAB", "VED", "ELO", "MELL", "Selfcover" ) );
define( "PARTTYPESEN", array( "COV", "CONT", "INS", "END", "BOA", "DUS", "Selfcover" ) );
define( "PARTS", array(
				"Inside" => "BEL",
				"Cover" => "BOR",
				"Board Cover" => "TAB",
				"Dust Jacket" => "VED",
				"Endpaper" => "ELO",
				"Insert" => "MELL",
				"Selfcover" => "Selfcover",
				));


?>