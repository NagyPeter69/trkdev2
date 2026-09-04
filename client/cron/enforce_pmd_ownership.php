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

function enforceTreeOwnership( $root ) {
	if( !is_dir( $root ) ) {
		return;
		}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
		);

	foreach( $iterator as $fileInfo ) {
		$path = $fileInfo->getPathname();
		$stat = stat( $path );
		$owner = posix_getpwuid( $stat['uid'] )['name'] ?? (string) $stat['uid'];
		$group = posix_getgrgid( $stat['gid'] )['name'] ?? (string) $stat['gid'];

		if( $owner != 'www-data' || $group != 'www-data' ) {
			error_log( "enforce_pmd_ownership: '".$path."' was owned by ".$owner.":".$group." (expected www-data:www-data) - correcting." );
			chown( $path, 'www-data' );
			chgrp( $path, 'www-data' );
			}
		}
	}

// Backstop for archived packages Switch FTPs into ARCHIVE_PATH (see
// getArchivePath() in engine/engine.php). The primary fix is that Switch's
// upload account is a member of group www-data, landing in a setgid
// directory Tracker pre-creates (client/engine/issueManagementAjax.php's
// archiveIssue op) so files are readable/deletable by www-data without ever
// needing a chown - this sweep only matters if that account setup is wrong,
// missing, or a stray upload lands with different permissions.
enforceTreeOwnership( '/var/www/html/archives' );

// Same problem, no equivalent account fix in place: switchReports/ (Switch's
// preflight report delivery) has no documented www-data-group/setgid setup
// like archives got, so it keeps landing root-owned with nothing to correct
// it. Confirmed live 2026-08-05 - cleanupPublicationRemnants()'s switchReports
// delTree() silently failed on a root-owned PRFU/2633 report folder because
// www-data had no write access to delete it, and nothing logged the failure
// (see delTree()'s own fix in engine/engine.php for the logging half of
// this). This sweep is the same stopgap as archives' until switchReports'
// delivery account gets the real fix.
enforceTreeOwnership( '/var/www/html/switchReports' );
