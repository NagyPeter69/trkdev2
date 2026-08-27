<?php
// Local vs remote r3 execution toggle.
//
// r3's license only validates against a genuine kvm64-configured VM (see
// SYSTEM_STATE.md's "R3's CPU/license conflict" section for the full
// history of why this box instead runs Broadwell, and why that means r3
// can't run locally here). Running on kvm64 -> r3 locally, exactly as it
// always did. Running on anything else (Broadwell etc.) -> forward every
// r3 call to the dedicated render-VM over HTTP.
//
// 2026-08-27: the CPU check itself moved out of request-time PHP. It used
// to call r3_running_on_kvm64() (parsing /proc/cpuinfo) fresh on every
// single request; now bin/detect-render-mode.php makes that same decision
// once, at boot (via the trkdev-detect-render-mode systemd unit), and
// writes the result to /etc/trkdev-render-mode - this file just reads
// that decision. The CPU a machine boots with can't change without an
// actual reboot, so there was never a reason to re-derive it per request;
// this also means a request can never observe a "torn" mid-flip state
// (every request in a given boot sees the same answer, by construction -
// per-request detection couldn't guarantee that if the CPU identity check
// itself ever raced anything).
define('TRKDEV_RENDER_MODE_FILE', '/etc/trkdev-render-mode');

function trkdev_render_mode() {
	$raw = @file_get_contents(TRKDEV_RENDER_MODE_FILE);
	if ($raw === false) {
		error_log('trkdev_render_mode: '.TRKDEV_RENDER_MODE_FILE.' unreadable - has bin/install-render-mode-detector.sh been run on this machine? Defaulting to remote (safe to attempt from any host; local execution on a non-kvm64 box is what silently produces r3 license failures, not the other way around).');
		return 'remote';
	}

	$mode = trim($raw);
	if ($mode !== 'local' && $mode !== 'remote') {
		error_log('trkdev_render_mode: unexpected content in '.TRKDEV_RENDER_MODE_FILE.': "'.$mode.'" - defaulting to remote until this is fixed (re-run bin/detect-render-mode.php).');
		return 'remote';
	}

	return $mode;
}

define('R3_REMOTE_MODE', trkdev_render_mode() === 'remote');

define('R3_REMOTE_URL', 'http://10.10.30.22/r3remote/run.php');
define('R3_REMOTE_TOKEN', getenv('TRKDEV_R3_TOKEN'));
