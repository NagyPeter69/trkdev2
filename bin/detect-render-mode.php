#!/usr/bin/env php
<?php
// Runs once at boot (via the trkdev-detect-render-mode systemd unit - see
// bin/trkdev-detect-render-mode.service and bin/install-render-mode-detector.sh)
// to decide, once and for all until the next boot, whether this machine
// should run R3/DynaPDF rendering locally or forward it to the dedicated
// render-VM - then persists that single decision to a state file every
// render call reads for the rest of the uptime.
//
// Why boot-time instead of the old per-request /proc/cpuinfo check
// (pre-2026-08-27): the project owner anticipates this box staying on a
// modern CPU (Broadwell or similar) continuously for extended periods of
// live production-hotfix work, rather than only briefly during dev. A
// per-request check was fine when the CPU model was expected to be
// stable for a whole deployment's lifetime, but re-reading /proc/cpuinfo
// on every single request is redundant work multiplied across every
// PHP-FPM worker, every request, forever - the CPU a machine boots with
// cannot change without an actual reboot, so decide once and read a flat
// file the rest of the time.
//
// Only R3 is switched by this - DynaPDF has no CPU-identity binding (it's
// a licensed extension keyed off engine/config.inc.php's license string,
// already confirmed working locally on this box's own Broadwell CPU - see
// SYSTEM_STATE.md's DynaPDF section) and has no remote-rendering path to
// fall back to even if it did, so it always runs locally regardless of
// this file's contents.

require_once __DIR__.'/../engine/cpu_detect.php';

$stateFile = '/etc/trkdev-render-mode';
$mode = r3_running_on_kvm64() ? 'local' : 'remote';

$written = @file_put_contents($stateFile, $mode."\n");
if ($written === false) {
	fwrite(STDERR, "detect-render-mode: failed to write $stateFile - check it's writable by whoever runs this (root, via the systemd unit)\n");
	exit(1);
}
@chmod($stateFile, 0644);

echo "detect-render-mode: CPU fingerprint says render mode = '$mode' (written to $stateFile)\n";
