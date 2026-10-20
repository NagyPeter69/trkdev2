<?php
// Page rendering without R3. Since the Proxmox 8.4 -> 9.2 upgrade on
// 2026-10-17, R3 on the render node rejects its hardware-bound license for
// RENDER and MEASURE, and its vendor can't issue a new one. r3run() (see
// r3client.php) sends those requests here instead:
//
// - RENDER: Ghostscript (render_region()), with DynaPDF
//   (r3_render_fallback()) if Ghostscript fails or times out.
// - GETDATA: DynaPDF reads the page boxes (pdf_getdata()).
// - MEASURE: DynaPDF lists the spot colours, Ghostscript gives their swatch
//   colours (pdf_measure()). Point measurements (the colour picker) and
//   ink coverage aren't available any more.
//
// Why Ghostscript renders rather than DynaPDF: DynaPDF ignores overprint
// entirely (a spot or process object set to overprint knocks out what's
// under it - the "L" of ELLE 2605's cover logo came out white - and a white
// overprint object shows although it never prints). It also either draws
// hairlines along flattened-transparency tiles or, with
// rfDisableAAClipping, thickens text the flattener turned into clip paths.
// Ghostscript gets all of these right, its separation device brings back
// channel toggles for spot colours, and it was 2-2.4x faster on the
// heaviest pages tested (BAV 2687's 100-168 MB pages).
//
// Ghostscript settings and why:
// - -dOverprint=/simulate: overprint as on press, also on an RGB device.
// - -dMaxBitmap big enough for the whole raster: above the default limit
//   gs renders in bands, and banded rendering draws visible lighter shapes
//   along flattened-transparency tiles (BAV 2687 p241 at 560 dpi).
// - -dInterpolateControl=-1: smooth image scaling. gs's default picks
//   pixels when downscaling, which looks sharp but aliases (jagged clock
//   numerals, grainy gilding on BAV 2687 p241).
// - Graphics/TextAlphaBits=4: anti-aliasing.
// - -sDefaultCMYKProfile = the job's colour standard (sprofile), so the
//   PDF's own output intent is ignored, as with R3.
//
// Region contract (same as R3 RENDER): left/bottom/right/top in PDF points
// relative to the CropBox's lower-left corner, scaled to width x height
// pixels. Anything outside the CropBox comes out white, like R3.

if (!defined('GS_BIN')) define('GS_BIN', '/usr/bin/gs');
if (!defined('GS_TIMEOUT')) define('GS_TIMEOUT', 120);
define('GS_PROFILE_DIR', '/var/www/html/r3API/r3');
define('GS_RGB_PROFILE', GS_PROFILE_DIR.'/sRGB_Color_Space_Profile.icc');

// Ghostscript first, DynaPDF if Ghostscript fails. JPEG bytes, or '' if
// both fail.
function render_region($params, $inputPath) {
	$data = gs_render($params, $inputPath);
	if ($data === '') {
		error_log('render_region: Ghostscript failed for '.$inputPath.', trying DynaPDF');
		$data = r3_render_fallback($params, $inputPath);
	}
	return $data;
}

function gs_render($params, $inputPath) {
	$region = gs_region($params);
	if ($region === null || !is_file($inputPath)) {
		error_log('gs_render: unusable input '.$inputPath.' '.json_encode($params));
		return '';
	}
	$sprofile = gs_profile_path($params['sprofile'] ?? '');

	$colors = strtolower((string) ($params['colors'] ?? ''));
	if ($colors !== '') {
		$inks = gs_selected_inks($colors, $inputPath);
		if ($inks !== null) {
			return gs_render_inks($region, $inputPath, $sprofile, $inks);
		}
	}

	$tmp = tempnam(sys_get_temp_dir(), 'gsr_');
	$args = array_merge(
		gs_common_args($region, $sprofile, 1),
		array('-sDEVICE=jpeg', '-dJPEGQ=90', '-sOutputFile='.$tmp),
		gs_offset_args($region, $inputPath)
	);
	$ok = gs_exec($args, $err);
	$data = ($ok && is_file($tmp)) ? (string) file_get_contents($tmp) : '';
	@unlink($tmp);
	if ($data === '') {
		error_log('gs_render: failed for '.$inputPath.': '.gs_brief($err));
	}
	return $data;
}

// Pixel size, resolution and page offset for the requested region.
// Fractional sizes are cut down, as R3 did: PDF_prework() passes the box
// size in points (e.g. 773.858), and R3 made that 773 px, not 774.
function gs_region($params) {
	$w = intval(floatval($params['width'] ?? 0));
	$h = intval(floatval($params['height'] ?? 0));
	$left = floatval($params['left'] ?? 0);
	$bottom = floatval($params['bottom'] ?? 0);
	$right = floatval($params['right'] ?? 0);
	$top = floatval($params['top'] ?? 0);
	if ($w < 1 || $h < 1 || $right - $left <= 0 || $top - $bottom <= 0) {
		return null;
	}
	return array(
		'w' => $w, 'h' => $h, 'left' => $left, 'bottom' => $bottom,
		'resX' => $w * 72 / ($right - $left),
		'resY' => $h * 72 / ($top - $bottom),
	);
}

function gs_common_args($region, $sprofile, $scale) {
	$w = $region['w'] * $scale;
	$h = $region['h'] * $scale;
	return array(
		'-dSAFER', '-dBATCH', '-dNOPAUSE', '-dQUIET', '-dNOPROMPT',
		'-dUseCropBox', '-dFIXEDMEDIA',
		'-g'.$w.'x'.$h,
		sprintf('-r%.4fx%.4f', $region['resX'] * $scale, $region['resY'] * $scale),
		'-dMaxBitmap='.($w * $h * 4 + 64 * 1024 * 1024),
		'-dOverprint=/simulate',
		'-dInterpolateControl=-1',
		'-dGraphicsAlphaBits=4', '-dTextAlphaBits=4',
		'-sDefaultCMYKProfile='.$sprofile,
		'-sOutputICCProfile='.GS_RGB_PROFILE,
	);
}

// PageOffset is in points: x moves the page left by `left`, y by +bottom
// (gs's y offset runs the other way). Checked against a patch grid at
// several resolutions, with uneven x/y scaling and past the page edge.
function gs_offset_args($region, $inputPath, $extra = '') {
	return array(
		'-c', sprintf('<</PageOffset [%.4f %.4f]%s>> setpagedevice', -$region['left'], $region['bottom'], $extra),
		'-f', $inputPath,
	);
}

function gs_profile_path($sprofile) {
	$name = basename((string) $sprofile);
	$path = GS_PROFILE_DIR.'/'.$name;
	if ($name === '' || !is_file($path)) {
		$path = GS_PROFILE_DIR.'/FOGRA_39.icc';
	}
	return $path;
}

// Pages View channel toggles: 'colors' holds the first letters of the
// enabled process inks (c, m, y, k) and 1-based indexes of the enabled spot
// colours, in pdf_spot_names() order (the order pdf_measure() stores them
// in pageinfo.colors). Returns the separation names to keep, or null when
// everything is enabled (plain render).
function gs_selected_inks($colors, $inputPath) {
	$spots = pdf_spot_names($inputPath);
	$keep = array();
	foreach (array('c' => 'Cyan', 'm' => 'Magenta', 'y' => 'Yellow', 'k' => 'Black') as $letter => $name) {
		if (strpos($colors, $letter) !== false) {
			$keep[] = $name;
		}
	}
	$keptSpots = 0;
	foreach ($spots as $i => $name) {
		if (strpos($colors, (string) ($i + 1)) !== false) {
			$keep[] = $name;
			$keptSpots++;
		}
	}
	if (count($keep) - $keptSpots == 4 && $keptSpots == count($spots)) {
		return null;
	}
	return $keep;
}

// Render only the given separations: tiffsep at twice the size, box-scaled
// back down here for anti-aliasing (tiffsep has none of its own, and its
// -dDownScaleFactor drops images from the separations in gs 10.05.1 when
// SeparationOrder selects a subset), then turn its CMYK composite into an
// sRGB JPEG through Ghostscript with the same profile, so the colours match
// the plain renders.
function gs_render_inks($region, $inputPath, $sprofile, $inks) {
	$dir = sys_get_temp_dir().'/gsr_'.bin2hex(random_bytes(6));
	if (!mkdir($dir, 0700)) {
		return '';
	}
	$order = '';
	$spotNames = '';
	foreach ($inks as $ink) {
		$order .= gs_ps_string($ink);
		if (!in_array($ink, array('Cyan', 'Magenta', 'Yellow', 'Black'))) {
			$spotNames .= gs_ps_string($ink);
		}
	}
	// No ink selected at all: blank paper.
	$blank = ($order === '');
	if ($blank) {
		$order = gs_ps_string('Black');
	}
	// The separations must carry the file's own ink values: no colour
	// profiles here (an sRGB output profile on tiffsep mixes the inks up),
	// and no AA or overprint switches, which the separation device doesn't
	// take - it overprints natively.
	$sepArgs = array_filter(gs_common_args($region, $sprofile, 2), function ($a) {
		return !preg_match('/^-(dGraphicsAlphaBits|dTextAlphaBits|dOverprint|sDefaultCMYKProfile|sOutputICCProfile)=/', $a);
	});
	$args = array_merge(
		array_values($sepArgs),
		array('-sDEVICE=tiffsep', '-sOutputFile='.$dir.'/p.tif'),
		gs_offset_args($region, $inputPath, ' /SeparationOrder ['.$order.'] /SeparationColorNames ['.$spotNames.']')
	);
	$ok = gs_exec($args, $err);
	$data = '';
	if ($ok && is_file($dir.'/p.tif')) {
		try {
			$im = new Imagick($dir.'/p.tif');
			$im->resizeImage($region['w'], $region['h'], Imagick::FILTER_BOX, 1);
			$im->setImageDepth(8);
			$im->setImageFormat('cmyk');
			$cmyk = $im->getImageBlob();
			$im->clear();
			if ($blank) {
				$cmyk = str_repeat("\0", strlen($cmyk));
			}
			$data = gs_cmyk_pixels_to_image($cmyk, $region['w'], $region['h'], $sprofile);
		} catch (Exception $e) {
			error_log('gs_render_inks: '.$e->getMessage());
		}
	} else {
		error_log('gs_render_inks: tiffsep failed for '.$inputPath.': '.gs_brief($err));
	}
	array_map('unlink', glob($dir.'/*'));
	@rmdir($dir);
	return $data;
}

// Wrap raw 8-bit CMYK pixels (a byte string) in a one-image PDF and render
// it through gs, so the CMYK -> sRGB conversion is the same as for a normal
// render. (Imagick can't apply black point compensation here: its blacks
// come out grey.)
function gs_cmyk_pixels_to_image($cmyk, $w, $h, $sprofile, $format = 'jpeg') {
	$pdf = gs_single_image_pdf(gzcompress($cmyk, 1), $w, $h);
	$in = tempnam(sys_get_temp_dir(), 'gsc_');
	$out = tempnam(sys_get_temp_dir(), 'gsc_');
	file_put_contents($in, $pdf);
	$args = array(
		'-dSAFER', '-dBATCH', '-dNOPAUSE', '-dQUIET', '-dNOPROMPT',
		'-g'.$w.'x'.$h, '-r72', '-dFIXEDMEDIA',
		'-sDefaultCMYKProfile='.$sprofile, '-sOutputICCProfile='.GS_RGB_PROFILE,
		$format === 'png' ? '-sDEVICE=png16m' : '-sDEVICE=jpeg', '-dJPEGQ=90',
		'-sOutputFile='.$out, '-f', $in,
	);
	$ok = gs_exec($args, $err);
	$data = ($ok && is_file($out)) ? (string) file_get_contents($out) : '';
	@unlink($in);
	@unlink($out);
	return $data;
}

function gs_single_image_pdf($flateData, $w, $h) {
	$content = "q $w 0 0 $h 0 0 cm /Im0 Do Q";
	$objs = array(
		'<</Type/Catalog/Pages 2 0 R>>',
		'<</Type/Pages/Kids[3 0 R]/Count 1>>',
		"<</Type/Page/Parent 2 0 R/MediaBox[0 0 $w $h]/Contents 4 0 R/Resources<</XObject<</Im0 5 0 R>>>>>>",
		'<</Length '.strlen($content).">>stream\n".$content."\nendstream",
		"<</Type/XObject/Subtype/Image/Width $w/Height $h/ColorSpace/DeviceCMYK/BitsPerComponent 8/Filter/FlateDecode/Length ".strlen($flateData).">>stream\n".$flateData."\nendstream",
	);
	$buf = "%PDF-1.4\n";
	$offsets = array();
	foreach ($objs as $i => $obj) {
		$offsets[] = strlen($buf);
		$buf .= ($i + 1)." 0 obj\n".$obj."\nendobj\n";
	}
	$xref = strlen($buf);
	$buf .= "xref\n0 ".(count($objs) + 1)."\n0000000000 65535 f \n";
	foreach ($offsets as $o) {
		$buf .= sprintf("%010d 00000 n \n", $o);
	}
	return $buf.'trailer<</Size '.(count($objs) + 1)."/Root 1 0 R>>\nstartxref\n".$xref."\n%%EOF\n";
}

function gs_ps_string($s) {
	return '('.strtr($s, array('\\' => '\\\\', '(' => '\\(', ')' => '\\)')).')';
}

// Run gs without a shell, killing it after GS_TIMEOUT seconds. $stderr
// gets gs's combined output.
function gs_exec($args, &$stderr) {
	$stderr = '';
	$proc = proc_open(array_merge(array(GS_BIN), $args), array(0 => array('file', '/dev/null', 'r'), 1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
	if (!is_resource($proc)) {
		$stderr = 'could not start gs';
		return false;
	}
	stream_set_blocking($pipes[1], false);
	stream_set_blocking($pipes[2], false);
	$deadline = microtime(true) + GS_TIMEOUT;
	$stdout = '';
	while (true) {
		$read = array($pipes[1], $pipes[2]);
		$write = $except = null;
		if (stream_select($read, $write, $except, 1) > 0) {
			foreach ($read as $r) {
				$chunk = fread($r, 65536);
				if ($r === $pipes[1]) $stdout .= $chunk; else $stderr .= $chunk;
			}
		}
		$status = proc_get_status($proc);
		if (!$status['running']) {
			$stdout .= stream_get_contents($pipes[1]);
			$stderr .= stream_get_contents($pipes[2]);
			break;
		}
		if (microtime(true) > $deadline) {
			proc_terminate($proc, 9);
			$stderr .= ' [killed after '.GS_TIMEOUT.'s]';
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($proc);
			return false;
		}
	}
	fclose($pipes[1]);
	fclose($pipes[2]);
	proc_close($proc);
	$stderr = trim($stdout."\n".$stderr);
	return $status['exitcode'] === 0;
}

// The first error line of gs's output, for the log (gs dumps whole
// PostScript stacks on errors).
function gs_brief($err) {
	foreach (preg_split('/[\r\n]+/', $err) as $line) {
		if (stripos($line, 'error') !== false || stripos($line, 'killed') !== false) {
			return substr(trim($line), 0, 300);
		}
	}
	return substr(trim($err), 0, 300);
}

// --- DynaPDF: page boxes and spot names (no image decoding, ~0.05-0.2 s) ---

function pdf_open_dyna($inputPath) {
	if (!class_exists('dynapdf') || !is_file($inputPath)) {
		return null;
	}
	$pdf = new dynapdf();
	include('/var/www/html/config.inc.php');
	$pdf->CreateNewPDF(NULL);
	$pdf->SetImportFlags(dynapdf::ifImportAll | dynapdf::ifImportAsPage);
	$pdf->SetImportFlags2(dynapdf::if2UseProxy);
	if ($pdf->OpenImportFile($inputPath, dynapdf::ptOpen, NULL) < 0) {
		$pdf->CloseFile();
		return null;
	}
	$pdf->ImportPDFFile(1, 1.0, 1.0);
	$pdf->CloseImportFile();
	return $pdf;
}

// Stand-in for R3 GETDATA: the four box lines in R3's format (numbers to 6
// significant digits, e.g. "TrimBox = [ 29.1732 29.1732 666.969 831.378 ]")
// - byte-identical to R3's on every page tested. R3 also printed the XMP
// after them; no caller uses it. A box the page doesn't set falls back as
// the PDF spec says: CropBox to MediaBox, BleedBox and TrimBox to CropBox.
function pdf_getdata($inputPath) {
	$pdf = pdf_open_dyna($inputPath);
	if ($pdf === null) {
		error_log('pdf_getdata: DynaPDF could not open '.$inputPath);
		return '';
	}
	$pdf->EditPage(1);
	$boxes = array();
	foreach (array('MediaBox' => dynapdf::pbMediaBox, 'CropBox' => dynapdf::pbCropBox, 'BleedBox' => dynapdf::pbBleedBox, 'TrimBox' => dynapdf::pbTrimBox) as $name => $type) {
		$b = $pdf->GetBBox($type);
		$boxes[$name] = (empty($b) || $b['Right'] - $b['Left'] <= 0 || $b['Top'] - $b['Bottom'] <= 0) ? null : $b;
	}
	$pdf->EndPage();
	$pdf->CloseFile();
	if ($boxes['MediaBox'] === null) {
		error_log('pdf_getdata: no MediaBox in '.$inputPath);
		return '';
	}
	$boxes['CropBox'] = $boxes['CropBox'] ?? $boxes['MediaBox'];
	$boxes['BleedBox'] = $boxes['BleedBox'] ?? $boxes['CropBox'];
	$boxes['TrimBox'] = $boxes['TrimBox'] ?? $boxes['CropBox'];
	$lines = array();
	foreach ($boxes as $name => $b) {
		$lines[] = $name.' = [ '.implode(' ', array_map(function ($v) { return sprintf('%g', round($v, 4)); }, array($b['Left'], $b['Bottom'], $b['Right'], $b['Top']))).' ]';
	}
	return implode("\n", $lines)."\n";
}

// Spot colours (Separation/DeviceN colorants other than the process inks,
// All and None) in the order they're defined.
function pdf_spot_names($inputPath) {
	static $cache = array();
	$key = $inputPath.'|'.@filemtime($inputPath);
	if (isset($cache[$key])) {
		return $cache[$key];
	}
	$pdf = pdf_open_dyna($inputPath);
	if ($pdf === null) {
		return array();
	}
	$spots = array();
	for ($i = 0, $n = $pdf->GetColorSpaceCount(); $i < $n; $i++) {
		$cs = $pdf->GetColorSpaceObj($i);
		foreach ((array) ($cs['Colorants'] ?? array()) as $name) {
			if (!in_array(strtolower($name), array('cyan', 'magenta', 'yellow', 'black', 'all', 'none')) && !in_array($name, $spots)) {
				$spots[] = $name;
			}
		}
	}
	$pdf->CloseFile();
	return $cache[$key] = $spots;
}

// Stand-in for the R3 MEASURE output stored in pageinfo.colors: one line
// per ink, "Name = coverage R G B". Callers only read the name and the R G B
// swatch; coverage isn't measured and is written as 0.000. The process
// swatches are the fixed values R3 always wrote; spot swatches are the
// spot's alternate CMYK (from gs) converted with the job's profile.
function pdf_measure($inputPath, $sprofile) {
	$lines = array('Cyan = 0.000 0 163 212', 'Magenta = 0.000 225 8 134', 'Yellow = 0.000 251 225 22', 'Black = 0.000 21 22 22');
	$spots = pdf_spot_names($inputPath);
	if (empty($spots)) {
		return implode("\n", $lines)."\n";
	}
	$dir = sys_get_temp_dir().'/gsm_'.bin2hex(random_bytes(6));
	$out = '';
	if (mkdir($dir, 0700)) {
		gs_exec(array('-dSAFER', '-dBATCH', '-dNOPAUSE', '-dNOPROMPT', '-sDEVICE=tiffsep', '-r10', '-dPrintSpotCMYK', '-sOutputFile='.$dir.'/p.tif', '-f', $inputPath), $out);
		array_map('unlink', glob($dir.'/*'));
		@rmdir($dir);
	}
	$alt = array();
	if (preg_match_all('/%%SeparationColor: "(.*)" 100% ink = (\d+) (\d+) (\d+) (\d+) CMYK/', $out, $m, PREG_SET_ORDER)) {
		foreach ($m as $row) {
			$alt[$row[1]] = array_map(function ($v) { return (int) round(min(32760, $v) / 32760 * 255); }, array_slice($row, 2, 4));
		}
	}
	$pixels = '';
	foreach ($spots as $name) {
		$pixels .= pack('C4', ...($alt[$name] ?? array(0, 0, 0, 0)));
	}
	$rgb = array();
	$png = gs_cmyk_pixels_to_image($pixels, count($spots), 1, gs_profile_path($sprofile), 'png');
	if ($png !== '') {
		$im = new Imagick();
		$im->readImageBlob($png);
		foreach ($spots as $i => $name) {
			$c = $im->getImagePixelColor($i, 0)->getColor();
			$rgb[$i] = $c['r'].' '.$c['g'].' '.$c['b'];
		}
	}
	foreach ($spots as $i => $name) {
		// Spot names come from the uploaded PDF and end up in SQL built by
		// string concatenation (PDF_prework) and in the " =" parsing of
		// getAllColors(): keep them to plain characters.
		$safe = preg_replace('/[^\p{L}\p{N} _.+\-\/]/u', '', $name);
		if ($safe === null) {
			// Not valid UTF-8 (PDF names often aren't): ASCII only.
			$safe = preg_replace('/[^A-Za-z0-9 _.+\-\/]/', '', $name);
		}
		$safe = trim($safe);
		$lines[] = ($safe !== '' ? $safe : 'Spot'.($i + 1)).' = 0.000 '.($rgb[$i] ?? '255 255 255');
	}
	return implode("\n", $lines)."\n";
}
