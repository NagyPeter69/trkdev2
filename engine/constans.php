<?PHP
define( "FPUPLOAD_PATH", "/var/www/html/client/flatplan_uploads" );
define( "PENZUGY", "ugyvitel@colorcom.hu" );
define( "TRKPATH", "/var/www/html/client" );
define( "XMLPATH", "/var/www/html/client/xml" );
define( "DUMMYPDF", "/var/www/html/client/engine/dummy.pdf" );
define( "RENDERIP", "10.10.30.122");
define( "DYNAIP", "10.10.30.60" );
define( "PROTOCOL", "https://" );

//Mail beállítások
define( "MAIL_HOST", "mail.colorcom.hu" );
define( "MAIL_PORT", "465" );
define( "MAIL_USERNAME", "produkcio" );
define( "MAIL_PASS", "oickudorp" );
define( "MAIL_EMAIL", "produkcio@colorcom.hu" );
define( "MAIL_NAME", "Colorcom Workflow" );

define( "MAIL_WF_USERNAME", "produkcio" );
define( "MAIL_WF_PASS", "oickudorp" );
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