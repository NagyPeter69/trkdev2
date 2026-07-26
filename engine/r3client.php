<?php
// Single entry point for every r3 invocation across the app. Routes to
// either the local r3 binary (correct in production - see
// r3client_config.php) or the dedicated render-VM over HTTP (correct here
// in dev).
//
// r3run() always returns the raw r3 output as a string - binary bytes for
// RENDER/COMPARE (write it to a file yourself), text for GETDATA/MEASURE/
// AUTOCOMPARE (same shape shell_exec() used to hand back directly). This
// mirrors what every call site already expected, so migrating a call site
// is just: replace the shell_exec(...) that built and ran the command with
// a single r3run(...) call, using the same $params values that used to go
// into the command string.
//
// $inputPath / $input2Path must be absolute local paths to the source
// PDF(s) on THIS machine - r3run() takes care of getting them to wherever
// r3 actually executes.

require_once __DIR__.'/r3client_config.php';

define('R3_LOCAL_DIR', '/var/www/html/r3API/r3');

function r3run($mode, $params, $inputPath, $input2Path = null) {
	if (R3_REMOTE_MODE) {
		return r3run_remote($mode, $params, $inputPath, $input2Path);
	}
	return r3run_local($mode, $params, $inputPath, $input2Path);
}

function r3run_remote($mode, $params, $inputPath, $input2Path = null) {
	if (!is_file($inputPath)) {
		error_log('r3run_remote: input file not found: '.$inputPath);
		return '';
	}

	$post = array(
		'token' => R3_REMOTE_TOKEN,
		'mode' => $mode,
		'params' => json_encode($params),
		'file' => new CURLFile($inputPath),
	);
	if ($input2Path !== null) {
		if (!is_file($input2Path)) {
			error_log('r3run_remote: second input file not found: '.$input2Path);
			return '';
		}
		$post['file2'] = new CURLFile($input2Path);
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, R3_REMOTE_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);

	$response = curl_exec($ch);
	if ($response === false) {
		error_log('r3run_remote: curl error: '.curl_error($ch));
		curl_close($ch);
		return '';
	}
	curl_close($ch);

	$decoded = json_decode($response, true);
	if (!is_array($decoded) || empty($decoded['success'])) {
		error_log('r3run_remote: render-VM returned failure: '.$response);
		return '';
	}

	return base64_decode($decoded['data']);
}

function r3run_local($mode, $params, $inputPath, $input2Path = null) {
	$pv = function($arr, $key) {
		return isset($arr[$key]) ? $arr[$key] : '';
	};

	if ($mode === 'RENDER') {
		$cmd = './r3 -binary -mode:RENDER'
			.' -left:'.escapeshellarg($pv($params, 'left'))
			.' -right:'.escapeshellarg($pv($params, 'right'))
			.' -bottom:'.escapeshellarg($pv($params, 'bottom'))
			.' -top:'.escapeshellarg($pv($params, 'top'))
			.' -width:'.escapeshellarg($pv($params, 'width'))
			.' -height:'.escapeshellarg($pv($params, 'height'));
		if (isset($params['colors']) && $params['colors'] !== '') {
			$cmd .= ' -colors:'.escapeshellarg($params['colors']);
		}
		$cmd .= ' -tprofile:'.escapeshellarg($pv($params, 'tprofile'))
			.' -sprofile:'.escapeshellarg($pv($params, 'sprofile'))
			.' '.escapeshellarg($inputPath);
	} elseif ($mode === 'GETDATA') {
		$cmd = './r3 -mode:GETDATA -metadata '.escapeshellarg($inputPath);
	} elseif ($mode === 'MEASURE') {
		$cmd = './r3 -mode:MEASURE'
			.' -x:'.escapeshellarg($pv($params, 'x'))
			.' -y:'.escapeshellarg($pv($params, 'y'));
		if (isset($params['d']) && $params['d'] !== '') {
			$cmd .= ' -d:'.escapeshellarg($params['d']);
		}
		if (isset($params['r']) && $params['r'] !== '') {
			$cmd .= ' -r:'.escapeshellarg($params['r']);
		}
		$cmd .= ' -tprofile:'.escapeshellarg($pv($params, 'tprofile'))
			.' '.escapeshellarg($inputPath);
	} elseif ($mode === 'COMPARE') {
		$cmd = './r3 -mode:COMPARE'
			.' -other_left:'.escapeshellarg($pv($params, 'other_left'))
			.' -other_bottom:'.escapeshellarg($pv($params, 'other_bottom'))
			.' -left:'.escapeshellarg($pv($params, 'left'))
			.' -bottom:'.escapeshellarg($pv($params, 'bottom'))
			.' -right:'.escapeshellarg($pv($params, 'right'))
			.' -top:'.escapeshellarg($pv($params, 'top'))
			.' -width:'.escapeshellarg($pv($params, 'width'))
			.' -height:'.escapeshellarg($pv($params, 'height'))
			.' '.escapeshellarg($inputPath).' '.escapeshellarg($input2Path);
	} elseif ($mode === 'AUTOCOMPARE') {
		$cmd = './r3 -mode:AUTOCOMPARE '.escapeshellarg($inputPath).' '.escapeshellarg($input2Path);
	} else {
		error_log('r3run_local: unknown mode '.$mode);
		return '';
	}

	$outputTmp = tempnam(sys_get_temp_dir(), 'r3out_');
	$full = 'cd '.escapeshellarg(R3_LOCAL_DIR).' && '.$cmd.' > '.escapeshellarg($outputTmp).' 2>>'.escapeshellarg($outputTmp.'.err');

	// exec() (vs the previous bare shell_exec()) surfaces the process exit
	// code, purely so a genuine r3 failure (crash, malformed/corrupt PDF,
	// license issue) gets logged clearly instead of silently looking
	// identical to "ran fine, produced no output" - every caller up the
	// stack (getPDFBox() and friends) already has to treat an empty
	// return as "nothing usable" regardless, so the external return
	// contract (raw string, '' on any failure) is deliberately left
	// unchanged here - this is strictly a logging improvement.
	exec($full, $execOutput, $exitCode);
	if ($exitCode !== 0) {
		$errContent = @file_get_contents($outputTmp.'.err');
		error_log('r3run_local: r3 exited with status '.$exitCode.' for mode '.$mode.(!empty($errContent) ? ' - stderr: '.trim($errContent) : ''));
	}

	$data = file_exists($outputTmp) ? file_get_contents($outputTmp) : '';
	@unlink($outputTmp);
	@unlink($outputTmp.'.err');

	return $data;
}
