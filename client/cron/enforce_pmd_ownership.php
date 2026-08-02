<?php
// Self-heal for a real incident: client/xml/pmd.xml was found owned by
// root:root instead of www-data:www-data (every other file in client/xml/
// is www-data:www-data - this was the one exception), most likely left
// behind by a one-off manual root-run cleanup script. PHP-FPM runs as
// www-data and has no write access to a root-owned file, so
// changeXmlDatabase() (engine/xml_handler.php) silently failed to write
// PMD for every single new publication/magazine for 12 days before this
// was noticed - no exception, no visible error, just a database that
// quietly drifted out of sync with PMD the whole time.
//
// www-data itself can never fix this (chown to your own user requires
// already owning the file, or root) - only a root-run process can, hence
// this being a cron job rather than an in-app check. changeXmlDatabase()
// also logs loudly (see its own comment) if a write ever fails, as an
// independent, faster safety net - this cron job is the automatic
// recovery half of that same fix, run every minute like the other
// frequent jobs in this crontab since publication creation is common
// enough that this shouldn't sit broken for a full day (the __cleaner__
// job's cadence) before self-healing.

$targets = array(
	'/var/www/html/client/xml/pmd.xml',
	);

foreach( $targets as $path ) {
	if( !is_file( $path ) ) {
		continue;
		}

	$stat = stat( $path );
	$owner = posix_getpwuid( $stat['uid'] )['name'] ?? (string) $stat['uid'];
	$group = posix_getgrgid( $stat['gid'] )['name'] ?? (string) $stat['gid'];

	if( $owner != 'www-data' || $group != 'www-data' ) {
		error_log( "enforce_pmd_ownership: '".$path."' was owned by ".$owner.":".$group." (expected www-data:www-data) - correcting." );
		chown( $path, 'www-data' );
		chgrp( $path, 'www-data' );
		}
	}
