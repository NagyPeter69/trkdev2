<?php
// Single entry point for every r3 invocation across the app. RENDER,
// GETDATA and MEASURE are handled locally by Ghostscript and DynaPDF since
// R3 lost its license (see gs_renderer.php); COMPARE and AUTOCOMPARE go to
// either the local r3 binary (only on a genuine kvm64 CPU) or the dedicated
// render node over HTTP (any other CPU) - see r3client_config.php.
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
require_once __DIR__.'/gs_renderer.php';

define('R3_LOCAL_DIR', '/var/www/html/r3API/r3');

// The render node mounts this host's TRK client/ tree over host-local NFS at
// R3_SHARE_REMOTE (see the render node's run.php "path mode"). When an r3
// input lives under R3_SHARE_LOCAL we send its path instead of uploading the
// (often 100 MB+) PDF, and RENDER output is written into R3_SHARE_SCRATCH,
// which is this host's own disk - so no upload and no base64. Falls back to
// upload automatically when the input is elsewhere or the mount is down.
define('R3_SHARE_LOCAL', '/var/www/html/client');
define('R3_SHARE_REMOTE', '/mnt/trk');
define('R3_SHARE_SCRATCH', '/var/www/html/client/r3shared');

function r3run($mode, $params, $inputPath, $input2Path = null) {
	// R3-first again (restored 2026-10-20, once the render node's licence was
	// fixed): the render node does RENDER/GETDATA/MEASURE/COMPARE. When it is
	// unreachable or returns nothing, fall back to Ghostscript/DynaPDF for the
	// modes that have a local equivalent (see gs_renderer.php) - the same
	// fallback that kept previews coming while R3 was down, kept intact.
	if (R3_REMOTE_MODE) {
		$GLOBALS['r3_used_remote'] = true;
		$out = r3run_remote($mode, $params, $inputPath, $input2Path);
	} else {
		$out = r3run_local($mode, $params, $inputPath, $input2Path);
	}
	if (is_string($out) && $out !== '') {
		return $out;
	}

	// R3 gave nothing - the render shown now comes from here, not the node.
	$GLOBALS['r3_used_remote'] = false;
	if ($mode === 'RENDER') {
		return render_region($params, r3_resolve_input_path($inputPath));
	}
	if ($mode === 'GETDATA') {
		return pdf_getdata(r3_resolve_input_path($inputPath));
	}
	if ($mode === 'MEASURE') {
		// Full-page ink list (d/r) has a DynaPDF/GS equivalent; a point
		// measurement (the colour picker) does not.
		if (!isset($params['d'])) {
			return '';
		}
		return pdf_measure(r3_resolve_input_path($inputPath), $params['tprofile'] ?? '');
	}
	// COMPARE / AUTOCOMPARE: no local equivalent.
	return is_string($out) ? $out : '';
}

// Whether any r3run() call in this request went to the render node over the
// network. Pages View turns its render spinner red for those (see
// updateRenderModeIndicator() in flatplan_preview.php); renders done here
// on this server leave it at its normal colour.
function r3_used_remote() {
	return !empty($GLOBALS['r3_used_remote']);
}

// DynaPDF renderer, the backup when Ghostscript fails (render_region() in
// gs_renderer.php), so every caller (Flatplan thumbnails, Pages View
// previews and zoom, ad thumbnails and check images) still gets a JPEG.
// It ignores overprint, see gs_renderer.php.
//
// Same contract as R3 RENDER: the region left/bottom/right/top in PDF
// points relative to the CropBox's lower-left corner (all of this app's
// stored pages have CropBox at 0,0), scaled to width x height pixels,
// converted from the source profile (sprofile) to RGB. 'colors' is the
// Pages View channel selection (first letters of the enabled toggles:
// c, m, y, k, digits for spot colours); when it leaves out a process ink,
// the page is rendered as CMYK, the unselected inks are blanked, and the
// result is converted to sRGB. Spot colours can't be toggled here - they
// are rendered into the process inks. Returns '' if DynaPDF fails too.
function r3_render_fallback($params, $inputPath) {
	if (!class_exists('dynapdf')) {
		return '';
	}
	$inputPath = r3_resolve_input_path($inputPath);
	// Cut fractional sizes down, as R3 did (see gs_region()).
	$width = intval(floatval($params['width'] ?? 0));
	$height = intval(floatval($params['height'] ?? 0));
	if (!is_file($inputPath) || $width < 1 || $height < 1) {
		error_log('r3_render_fallback: unusable input '.$inputPath.' ('.$width.'x'.$height.')');
		return '';
	}

	$sprofile = R3_LOCAL_DIR.'/'.basename((string) ($params['sprofile'] ?? ''));
	if (!is_file($sprofile)) {
		$sprofile = R3_LOCAL_DIR.'/FOGRA_39.icc';
	}
	$colors = strtolower((string) ($params['colors'] ?? ''));
	$blankInks = array();
	if ($colors !== '') {
		foreach (array('c', 'm', 'y', 'k') as $ink) {
			if (strpos($colors, $ink) === false) {
				$blankInks[] = $ink;
			}
		}
	}

	$pdf = new dynapdf();
	include('/var/www/html/config.inc.php');
	$pdf->CreateNewPDF(NULL);
	$pdf->InitColorManagement(NULL, NULL, 1);
	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage | dynapdf::ifDocInfo);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);
	$pdf->SetJPEGQuality(90);
	if ($pdf->OpenImportFile($inputPath, dynapdf::ptOpen, NULL) < 0) {
		$pdf->CloseFile();
		error_log('r3_render_fallback: DynaPDF could not open '.$inputPath);
		return '';
	}
	$pdf->ImportPDFFile(1, 1.0, 1.0);
	$pdf->AddOutputIntent($sprofile);
	$pdf->CloseImportFile();

	$pdf->EditPage(1);
		$origin = $pdf->GetBBox(dynapdf::pbCropBox);
		$media = $pdf->GetBBox(dynapdf::pbMediaBox);
		if (empty($origin)) {
			$origin = $media;
		}
		$x0 = empty($origin) ? 0 : $origin['Left'];
		$y0 = empty($origin) ? 0 : $origin['Bottom'];
		$left = $x0 + floatval($params['left']);
		$bottom = $y0 + floatval($params['bottom']);
		$right = $x0 + floatval($params['right']);
		$top = $y0 + floatval($params['top']);

		// Callers may ask for a region reaching past the page (tesztAjax.php
		// adds 29pt beyond the right-hand page of a spread). R3 renders that
		// part blank; DynaPDF clips the region to the MediaBox and would
		// stretch what's left over the full width x height. So render only
		// the part on the page, at the requested scale, and pad it back out
		// with white below.
		$scaleX = $width / max($right - $left, 0.001);
		$scaleY = $height / max($top - $bottom, 0.001);
		$clipL = $left; $clipB = $bottom; $clipR = $right; $clipT = $top;
		if (!empty($media)) {
			$clipL = max($left, $media['Left']);
			$clipB = max($bottom, $media['Bottom']);
			$clipR = min($right, $media['Right']);
			$clipT = min($top, $media['Top']);
		}
		$renderW = max(1, intval(round(($clipR - $clipL) * $scaleX)));
		$renderH = max(1, intval(round(($clipT - $clipB) * $scaleY)));
		$offsetX = intval(round(($clipL - $left) * $scaleX));
		$offsetY = intval(round(($top - $clipT) * $scaleY));
		$pad = ($clipR <= $clipL || $clipT <= $clipB) ? null : ($renderW != $width || $renderH != $height);
		if ($pad !== null) {
			$pdf->SetBBox(dynapdf::pbCropBox, $clipL, $clipB, $clipR, $clipT);
		}
	$pdf->EndPage();

	if ($pad === null) {
		// Nothing of the page inside the requested region: R3 gives a
		// blank image for that, so do the same.
		$pdf->CloseFile();
		error_log('r3_render_fallback: region outside the page for '.$inputPath.', returning a blank '.$width.'x'.$height);
		return r3_fallback_canvas($width, $height, null, 0, 0);
	}

	$tmp = tempnam(sys_get_temp_dir(), 'r3fb_');
	if (empty($blankInks)) {
		$pdf->RenderPageToImage(1, $tmp, 0, $renderW, $renderH, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);
	} else {
		$pdf->RenderPageToImage(1, $tmp, 0, $renderW, $renderH, dynapdf::rfDefault, dynapdf::pxfCMYK, dynapdf::cfFlate, dynapdf::ifmTIFF);
	}
	$pdf->CloseFile();

	$data = is_file($tmp) ? (string) file_get_contents($tmp) : '';
	@unlink($tmp);

	if ($data !== '' && !empty($blankInks)) {
		// Blank the unselected inks in the CMYK raster, then let DynaPDF do
		// the CMYK -> RGB conversion with the same output intent as the
		// full-colour path above. (Imagick's own ICC conversion of DynaPDF's
		// CMYK TIFF comes out as a colour negative.)
		$cmykTmp = tempnam(sys_get_temp_dir(), 'r3fb_').'.tif';
		$rgbTmp = tempnam(sys_get_temp_dir(), 'r3fb_');
		try {
			$channels = array('c' => Imagick::CHANNEL_CYAN, 'm' => Imagick::CHANNEL_MAGENTA, 'y' => Imagick::CHANNEL_YELLOW, 'k' => Imagick::CHANNEL_BLACK);
			$im = new Imagick();
			$im->readImageBlob($data);
			foreach ($blankInks as $ink) {
				$im->evaluateImage(Imagick::EVALUATE_SET, 0, $channels[$ink]);
			}
			$im->setImageFormat('tiff');
			$im->writeImage($cmykTmp);
			$im->clear();

			$pdf = new dynapdf();
			include('/var/www/html/config.inc.php');
			$pdf->CreateNewPDF(NULL);
			$pdf->SetBBox(dynapdf::pbMediaBox, 0, 0, $renderW, $renderH);
			$pdf->InitColorManagement(NULL, NULL, 1);
			$pdf->SetJPEGQuality(90);
			$pdf->Append();
			$pdf->InsertImageEx(0, 0, $renderW, $renderH, $cmykTmp, 1);
			$pdf->EndPage();
			$pdf->AddOutputIntent($sprofile);
			$pdf->RenderPageToImage(1, $rgbTmp, 0, $renderW, $renderH, dynapdf::rfDefault, dynapdf::pxfRGB, dynapdf::cfFlate, dynapdf::ifmJPEG);
			$pdf->CloseFile();
			$data = is_file($rgbTmp) ? (string) file_get_contents($rgbTmp) : '';
		} catch (Exception $e) {
			error_log('r3_render_fallback: channel selection failed for '.$inputPath.': '.$e->getMessage());
			$data = '';
		}
		@unlink($cmykTmp);
		@unlink($rgbTmp);
		@unlink(substr($cmykTmp, 0, -4));
	}

	if ($data !== '' && $pad) {
		$data = r3_fallback_canvas($width, $height, $data, $offsetX, $offsetY);
	}

	error_log('r3_render_fallback: DynaPDF '.($data !== '' ? 'rendered '.strlen($data).' bytes' : 'FAILED').' for '.$inputPath.' ('.$width.'x'.$height.($pad ? ', page part '.$renderW.'x'.$renderH.' at '.$offsetX.','.$offsetY : '').($blankInks ? ', without '.implode('', $blankInks) : '').')');
	return $data;
}

// A white width x height JPEG, with $jpeg (if given) placed at x/y - the
// blank margin R3 leaves where a requested region runs past the page.
function r3_fallback_canvas($width, $height, $jpeg, $x, $y) {
	try {
		$canvas = new Imagick();
		$canvas->newImage($width, $height, new ImagickPixel('white'));
		if ($jpeg !== null) {
			$part = new Imagick();
			$part->readImageBlob($jpeg);
			$canvas->compositeImage($part, Imagick::COMPOSITE_OVER, $x, $y);
			$part->clear();
		}
		$canvas->setImageFormat('jpeg');
		$canvas->setImageCompressionQuality(90);
		$data = $canvas->getImageBlob();
		$canvas->clear();
		return $data;
	} catch (Exception $e) {
		error_log('r3_render_fallback: padding failed: '.$e->getMessage());
		return (string) $jpeg;
	}
}

// Some legacy call sites still pass paths relative to the r3 directory (e.g.
// "../../advertisements/x.pdf"), a leftover of the old "cd r3; ./r3 ..."
// shell_exec() calls. r3run_local() resolves those naturally because it runs
// r3 from R3_LOCAL_DIR; the remote path must resolve them the same way rather
// than against PHP's cwd.
function r3_resolve_input_path($path) {
	if (!is_string($path) || $path === '' || $path[0] === '/') {
		return $path;
	}
	return R3_LOCAL_DIR.'/'.$path;
}

function r3run_remote($mode, $params, $inputPath, $input2Path = null) {
	$inputPath = r3_resolve_input_path($inputPath);
	$input2Path = r3_resolve_input_path($input2Path);
	if (!is_file($inputPath)) {
		error_log('r3run_remote: input file not found: '.$inputPath);
		return '';
	}
	if ($input2Path !== null && !is_file($input2Path)) {
		error_log('r3run_remote: second input file not found: '.$input2Path);
		return '';
	}

	// Fast lane: if the inputs sit on the shared mount, send paths instead of
	// uploading. r3remote_pathmode() returns false to mean "mount/path
	// unusable, upload instead"; any string (including '' on a real r3
	// failure) is the final result.
	$remoteIn = r3_share_translate($inputPath);
	$remoteIn2 = ($input2Path === null) ? null : r3_share_translate($input2Path);
	if ($remoteIn !== null && ($input2Path === null || $remoteIn2 !== null)) {
		$res = r3remote_pathmode($mode, $params, $remoteIn, $remoteIn2);
		if ($res !== false) {
			return $res;
		}
	}

	return r3remote_upload($mode, $params, $inputPath, $input2Path);
}

// Map a web-host path under R3_SHARE_LOCAL to the render node's view of it
// (R3_SHARE_REMOTE); null when the file isn't on the shared tree.
function r3_share_translate($path) {
	if (!is_string($path) || $path === '') {
		return null;
	}
	$rp = realpath($path);
	if ($rp === false) {
		return null;
	}
	if ($rp !== R3_SHARE_LOCAL && strpos($rp, R3_SHARE_LOCAL.'/') !== 0) {
		return null;
	}
	return R3_SHARE_REMOTE.substr($rp, strlen(R3_SHARE_LOCAL));
}

// Path mode: the render node reads the input off the shared mount and, for
// RENDER/COMPARE, writes the image into the shared tree (this host's own
// disk), so there is no upload and no base64. Returns the output string, ''
// on a genuine r3 failure (so r3run() can fall back to GS), or false to mean
// "mount/path unusable, upload instead".
function r3remote_pathmode($mode, $params, $remoteIn, $remoteIn2) {
	$binary = ($mode === 'RENDER' || $mode === 'COMPARE');
	$post = array(
		'token' => R3_REMOTE_TOKEN,
		'mode' => $mode,
		'params' => json_encode($params),
		'input_path' => $remoteIn,
	);
	if ($remoteIn2 !== null) {
		$post['input_path2'] = $remoteIn2;
	}
	$localOut = null;
	if ($binary) {
		$name = 'out_'.bin2hex(random_bytes(8)).'.bin';
		$localOut = R3_SHARE_SCRATCH.'/'.$name;
		$post['output_path'] = R3_SHARE_REMOTE.'/r3shared/'.$name;
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, R3_REMOTE_URL);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_setopt($ch, CURLOPT_TIMEOUT, 120);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
	$response = curl_exec($ch);
	$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$cerr = curl_error($ch);
	curl_close($ch);

	if ($response === false) {
		error_log('r3remote_pathmode: curl error: '.$cerr);
		if ($localOut !== null) { @unlink($localOut); }
		return false;
	}
	$decoded = json_decode($response, true);
	if (!is_array($decoded) || empty($decoded['success'])) {
		$err = is_array($decoded) ? (string) ($decoded['error'] ?? '') : '';
		// Mount gone (503) or a path problem (400): retry as an upload.
		if ($http === 503 || ($http === 400 && (strpos($err, 'path') !== false || strpos($err, 'not found') !== false || strpos($err, 'shared') !== false))) {
			if ($localOut !== null) { @unlink($localOut); }
			return false;
		}
		// Genuine r3 failure (e.g. 502): '' so r3run() falls back to GS.
		error_log('r3remote_pathmode: render node failure ('.$http.'): '.substr((string) $response, 0, 200));
		if ($localOut !== null) { @unlink($localOut); }
		return '';
	}

	if ($binary) {
		clearstatcache(true, $localOut);
		if (!is_file($localOut) || filesize($localOut) === 0) {
			error_log('r3remote_pathmode: output missing/empty '.$localOut);
			@unlink($localOut);
			return '';
		}
		$data = (string) file_get_contents($localOut);
		@unlink($localOut);
		return $data;
	}
	return base64_decode($decoded['data']);
}

function r3remote_upload($mode, $params, $inputPath, $input2Path = null) {
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
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

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
