<?php
// Single source of truth for "is this machine a genuine kvm64 QEMU VM,
// vs. some other/modern CPU (Broadwell etc.)". Used only by
// bin/detect-render-mode.php at boot - see that file and
// engine/r3client_config.php for why request-time PHP no longer calls
// this directly (2026-08-27: moved from per-request detection to a
// once-per-boot decision, persisted to /etc/trkdev-render-mode).
//
// r3's license only validates against a genuine kvm64-configured VM - see
// SYSTEM_STATE.md's "R3's CPU/license conflict" section for the full
// history of why this dev VM instead runs Broadwell.
//
// Detection reads /proc/cpuinfo directly rather than shelling out to
// lscpu/dmidecode, since it's a single cheap file read and the exact
// three fields identifying kvm64 (vendor/family/model) are already plain
// text there.
function r3_running_on_kvm64() {
	$cpuinfo = @file_get_contents('/proc/cpuinfo');
	if ($cpuinfo === false) {
		// Can't tell - fail toward remote, since that's safe to attempt
		// from anywhere (local execution on a non-kvm64 box is the one
		// that silently produces license failures, not the other way
		// around).
		return false;
	}

	if (!preg_match('/^vendor_id\s*:\s*(.+)$/m', $cpuinfo, $vendorMatch)) {
		return false;
	}
	if (!preg_match('/^cpu family\s*:\s*(\d+)$/m', $cpuinfo, $familyMatch)) {
		return false;
	}
	// Anchored so this doesn't also match the unrelated "model name" line.
	if (!preg_match('/^model\s*:\s*(\d+)$/m', $cpuinfo, $modelMatch)) {
		return false;
	}

	$vendor = trim($vendorMatch[1]);
	$family = (int)$familyMatch[1];
	$model = (int)$modelMatch[1];

	return ($vendor === 'GenuineIntel' && $family === 15 && $model === 6);
}
