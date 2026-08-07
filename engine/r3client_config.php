<?php
// Local vs remote r3 execution toggle - auto-detected from the CPU this
// code is actually running on, rather than a flag someone has to remember
// to flip when deploying.
//
// r3's license only validates against a genuine kvm64-configured VM (see
// the project history around 2026-07 for why: this dev VM instead runs
// Broadwell so Claude Code can function, and the two can't coexist on one
// machine). So: running on kvm64 -> r3 locally, exactly as it always did.
// Running on anything else (Broadwell here in dev) -> forward every r3
// call to the dedicated render-VM over HTTP.
//
// Detection reads /proc/cpuinfo directly rather than shelling out to
// lscpu/dmidecode, since it's a single cheap file read and the exact
// three fields identifying kvm64 (vendor/family/model) are already
// plain text there.
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

define('R3_REMOTE_MODE', !r3_running_on_kvm64());

define('R3_REMOTE_URL', 'http://10.10.30.22/r3remote/run.php');
define('R3_REMOTE_TOKEN', getenv('TRKDEV_R3_TOKEN'));
