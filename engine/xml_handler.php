<?
	header('Content-Type: text/html; charset=utf-8');

	include_once( 'connect.php' );
	include_once( 'engine.php' );

	// Full mop-up for one deleted publication/issue: every table and
	// on-disk directory known to reference it. Called from the Switch
	// delete-confirmation handlers (delete_issue_results-handler.php,
	// delete_publication_results-handler.php) at the moment Switch
	// confirms the delete, so space frees up immediately rather than
	// waiting for the daily cron's orphan sweep - that sweep (assets and
	// the log tables) stays in place as a safety net, not a replacement.
	// Must be called before the caller's own `ads` cleanup, since
	// partial_ads has to be looked up by ad id while the ads rows still
	// exist.
	function cleanupPublicationRemnants( $pubId, $magazineCode, $issueCode ) {
		// An ad has files in three places, none of them found by the same
		// key, all confirmed orphaned via the test protocol (pub_id/ads
		// rows gone, every one of these survived a real issue delete):
		//   1. uploads/ads/{ads.file_name} (+ the same base name with a
		//      .xml extension) - the raw upload, staged there by
		//      ad_upload.php before Switch ever sees it. Named exactly by
		//      ads.file_name, so this one's a direct match.
		//   2. advertisements/{ads.name}_preview.jpg - the upload-time
		//      preview, also from ad_upload.php, named from the ad's own
		//      name (exact stored case, not uppercased).
		//   3. advertisements/{STRTOUPPER(ads.name)}_{magazineCode}_
		//      {issueCode}_* (PDF, XML, thumb/check JPEGs) - the
		//      processed/finalized files new_ad_results-handler.php writes
		//      once Switch confirms. ads.file_name is the wrong key here
		//      (it's the upload's original filename, not this derived
		//      base name), so this location needs the ad's name instead.
		// The row has to be read (not just its id) before it's deleted to
		// have any of this available.
		$adRows = sql_aget( 'ads', "pub_id='".$pubId."'", "*" );
		for( $i = 0; $i < count( $adRows ); $i++ ) {
			sql_delete( 'partial_ads', "ads_id='".$adRows[$i]["id"]."'" );

			if( !empty( $adRows[$i]["file_name"] ) ) {
				if( is_file( TRKPATH.'/uploads/ads/'.$adRows[$i]["file_name"] ) ) {
					@unlink( TRKPATH.'/uploads/ads/'.$adRows[$i]["file_name"] );
					}
				$uploadXml = preg_replace( '/\.[^.]+$/', '.xml', $adRows[$i]["file_name"] );
				if( is_file( TRKPATH.'/uploads/ads/'.$uploadXml ) ) {
					@unlink( TRKPATH.'/uploads/ads/'.$uploadXml );
					}
				}

			if( !empty( $adRows[$i]["name"] ) ) {
				if( is_file( TRKPATH.'/advertisements/'.$adRows[$i]["name"].'_preview.jpg' ) ) {
					@unlink( TRKPATH.'/advertisements/'.$adRows[$i]["name"].'_preview.jpg' );
					}
				// Same Adhoc-vs-Regular single/double segment distinction as
				// ajax.php's load_adverts and __cleaner__.php - Adhoc jobs
				// have publications.code == magazines.code (there's no
				// separate issue), so their checked-ad files are named
				// NAME_CODE_TYPE, not NAME_CODE_CODE_TYPE. Without this, the
				// glob below never matches an Adhoc job's real files and
				// they survive every publication delete. Confirmed live
				// 2026-07-29 deleting UXE01 (Adhoc).
				$issueSegment = ( $magazineCode == $issueCode ) ? '' : '_'.$issueCode;
				$adFiles = glob( TRKPATH.'/advertisements/'.strtoupper( $adRows[$i]["name"] ).'_'.$magazineCode.$issueSegment.'_*' );
				for( $y = 0; $y < count( $adFiles ); $y++ ) {
					@unlink( $adFiles[$y] );
					}
				}
			}
		sql_delete( 'ads', "pub_id='".$pubId."'" );

		// Catch-all safety net: the per-ad loop above only removes files
		// matching a name it read from the row it's currently deleting.
		// Confirmed via the 2026-08-02 audit that files can still survive
		// with the ads row already gone (20 orphaned files found across
		// magazines whose ads table was otherwise empty) - a broader sweep
		// on the magazineCode/issueCode segment alone catches anything the
		// name-keyed pass missed, regardless of why.
		$issueSegment = ( $magazineCode == $issueCode ) ? '' : '_'.$issueCode;
		$strayAdFiles = glob( TRKPATH.'/advertisements/*_'.$magazineCode.$issueSegment.'_*' );
		for( $y = 0; $y < count( $strayAdFiles ); $y++ ) {
			@unlink( $strayAdFiles[$y] );
			}

		sql_delete( 'parts', "pub_id='".$pubId."'" );

		// flatplan_articletypes was never wired into this cleanup at all -
		// confirmed 28 orphaned rows via the 2026-08-02 audit (publications
		// table was empty, every one of those pub_ids was already gone).
		sql_delete( 'flatplan_articletypes', "pub_id='".$pubId."'" );

		$packs = sql_get( 'packages', 'publication_id="'.$pubId.'"', '*' );
		for( $i = 0; $i < count( $packs ); $i++ ) {
			sql_delete( 'package_info', 'package_id="'.$packs[$i][0].'"' );
			}
		sql_delete( 'packages', "publication_id='".$pubId."'" );
		if( is_dir( TRKPATH.'/packages/'.$magazineCode.'/'.$issueCode ) ) {
			delTree( TRKPATH.'/packages/'.$magazineCode.'/'.$issueCode );
			}
		// This has been deleting from the wrong path since it was written
		// (git blame: only ever touched once) - reports are actually served
		// from /var/www/html/switchReports/ (see teszt.php / new_indd_package_
		// submitted-handler.php's report link, built as "https://".$url.
		// "/switchReports/..." - that resolves through the web root,
		// /var/www/html/, not /var/www/). /var/www/switchReports/ is a
		// different, essentially-empty directory. Confirmed via the
		// 2026-08-02 audit: 1210 real report files (HTML + JPEGs) sitting
		// there back to 2021, none ever cleaned up on any deletion.
		// Same Adhoc-vs-Regular segment distinction as every other
		// magazineCode/issueCode path in this function - Adhoc reports sit
		// directly in {magazineCode}/, not {magazineCode}/{magazineCode}/,
		// since there's no separate issue segment.
		$issueSegment = ( $magazineCode == $issueCode ) ? '' : '/'.$issueCode;
		if( is_dir( '/var/www/html/switchReports/'.$magazineCode.$issueSegment ) ) {
			delTree( '/var/www/html/switchReports/'.$magazineCode.$issueSegment );
			}

		sql_delete( 'pageinfo', 'issue="'.$issueCode.'" AND code="'.$magazineCode.'"' );
		sql_delete( 'comments', "pub_id='".$pubId."'" );

		// Planner-uploaded files - unlink the actual files before dropping rows.
		$files = sql_aget( 'flatplan_files', "pubid='".$pubId."'", "*" );
		for( $i = 0; $i < count( $files ); $i++ ) {
			if( !empty( $files[$i]["path"] ) && !empty( $files[$i]["filename"] ) ) {
				@unlink( $files[$i]["path"]."/".$files[$i]["filename"] );
				}
			}
		sql_delete( 'flatplan_files', "pubid='".$pubId."'" );

		sql_delete( 'flatplan_planner', "pub_id='".$pubId."'" );

		$handouts = sql_aget( 'flatplan_handout', "pub_id='".$pubId."'", "*" );
		for( $i = 0; $i < count( $handouts ); $i++ ) {
			sql_delete( 'flatplan_handout_hotlink', "handoutid='".$handouts[$i]["id"]."'" );
			if( !empty( $handouts[$i]["filename"] ) ) {
				if( is_file( TRKPATH.'/handout/'.$handouts[$i]["filename"] ) ) {
					@unlink( TRKPATH.'/handout/'.$handouts[$i]["filename"] );
					}
				// flatplan_ajax.php's loadhandoutmenu op derives a second,
				// separate on-disk file for in-browser viewing by swapping
				// "handout" for "stream" in this same filename (e.g.
				// PLRY_2601_handout.pdf -> PLRY_2601_stream.pdf) - only the
				// stored filename above was ever unlinked, so this variant
				// (often the larger of the two) survived every deletion.
				// Confirmed orphaned via the test protocol.
				$streamFile = str_replace( "handout", "stream", $handouts[$i]["filename"] );
				if( $streamFile != $handouts[$i]["filename"] && is_file( TRKPATH.'/handout/'.$streamFile ) ) {
					@unlink( TRKPATH.'/handout/'.$streamFile );
					}
				}
			}
		sql_delete( 'flatplan_handout', "pub_id='".$pubId."'" );

		sql_delete( 'image_map', "pub_id='".$pubId."'" );
		sql_delete( 'deliver_table', "pub_id='".$pubId."'" );

		$hotlinkIds = sql_aget( 'hotlinks', "publication='".$pubId."'", "id" );
		for( $i = 0; $i < count( $hotlinkIds ); $i++ ) {
			sql_delete( 'hotlinks_log', "hotlink_id='".$hotlinkIds[$i]["id"]."'" );
			}
		sql_delete( 'hotlinks', "publication='".$pubId."'" );

		sql_delete( 'adhoc_hotlinks', "pubid='".$pubId."'" );

		// Assets - cleaned immediately here rather than waiting for the
		// daily cron's orphan sweep.
		if( is_dir( TRKPATH.'/assets/'.$pubId ) ) {
			delTree( TRKPATH.'/assets/'.$pubId );
			}
		sql_delete( 'assets', "pub_id='".$pubId."'" );

		// The per-issue Switch snapshot (toSwitch()'s 'new_publication'
		// case writes client/xml/{magazineCode}_{issueCode}.xml on every
		// create/modify/status-change) isn't cleaned up anywhere else -
		// confirmed orphaned via the test protocol after a real issue
		// delete. Regular issues use the magCode_issueCode name; Adhoc
		// publications use just their own code with no separator, since
		// they're their own single "issue" - try both.
		if( is_file( TRKPATH.'/xml/'.$magazineCode.'_'.$issueCode.'.xml' ) ) {
			@unlink( TRKPATH.'/xml/'.$magazineCode.'_'.$issueCode.'.xml' );
			}
		if( is_file( TRKPATH.'/xml/'.$issueCode.'.xml' ) ) {
			@unlink( TRKPATH.'/xml/'.$issueCode.'.xml' );
			}

		// Deliberately NOT touching action_log/comment_log/error_log/
		// system_log here: these are audit trails, not job data, and one
		// of them (action_log) is where the caller just recorded *this
		// deletion itself* - wiping it here would erase the very audit
		// record the deletion was supposed to leave behind. The daily
		// cron's separate orphan sweep already prunes comment_log/
		// error_log/system_log for long-gone publications on its own
		// slower schedule; that's a deliberate, distinct decision from
		// this function's job (freeing disk space), left as-is.
		}

	function changeIssueStatus( $file, $value, $data ) {
		if( $value == "remove" ) {
			return true;
			}
		else {
			// The per-issue snapshot is regenerated wholesale on every
			// issue create/modify, so it can legitimately be missing (not
			// yet created, or removed by a cleanup pass) when a status
			// change comes through a different path. simplexml_load_file()
			// on a missing file returns false with a warning, and false->
			// status = $value is a hard PHP 8 fatal, not a warning -
			// confirmed via the test protocol, not hypothetical.
			if( !is_file( "../xml/".$file ) ) {
				error_log( "changeIssueStatus: missing snapshot ../xml/".$file." - status change to '".$value."' not synced to Switch" );
				return false;
				}

			$xml = simplexml_load_file(  "../xml/".$file );
			$xml->status = $value;

			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($xml->asXML());
			$dom->formatOutput = true;
		
			$newFile = $file;
			file_put_contents(  "../xml/".$newFile, $dom->saveXML() );
			
			toSwitch( 'new_publication' , 'publications|'.$data, 'C_database/'.substr( $file, 0, -4 ), 'issueData' );
			
			if( file_get_contents( $newFile ) == "" )
				return false;
				
			if( file_get_contents( $newFile ) != "" ) {
				$array = array(
					"event" => "xml_data",
					);
					
				$file = array( 
					"name" => $file,
					"path" => "xml",
					);
				$response = SwitchSend_TESZT( $array, $file );				
				}
			return true;
			}
		}
	
	function removeFromXML( $xml, $code, $node, $search ) {
		$xml_path = $xml;
		$xml = simplexml_load_file( $xml_path );
		
		$xpath = $xml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $code ) {
					break;
					}
				}
			}		
		
		$xpath = $xml->xpath( '/Publications/Item[Code = "'.$code.'"]'.$node );
		$i = 0;
		foreach( $search as $key => $val ) {
			foreach($xpath as $temp) {
				for( $y = 0; $y < count( $temp->$key ); $y++ ) {
					if( $i == intval( $val ) ) {
						break;
						}
			
					$i++;
					}
				}
			$pos = intval( $val )+1;
			$path = $xml->xpath( '/Publications/Item[Code = "'.$code.'"]'.$node.'/'.$key.'[position() = '.$pos.']' );
			//$path = $xml->Item[ intval($x) ]->{substr( $node, 1 )}->{$key}[$y];
			
			foreach( $path as $v ) {
				$dom=dom_import_simplexml($v);
				$dom->preserveWhiteSpace = false;
				$dom->parentNode->removeChild($dom);
				}
			$dom = new DOMDocument();
			$dom->preserveWhiteSpace = false;
			$dom->loadXML($xml->asXML());
			$dom->formatOutput = true;
			file_put_contents( $xml_path, $dom->saveXML() );
			}
		}

	function collectFromXml( $xml, $code, $nodes, $returnnode = '' ) {
		$result = array();
		$xml = simplexml_load_file( $xml );
		$xpath = $xml->xpath('/Publications');
		
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $code ) {
					break;
					}
				}
			}
		
		if( gettype( $nodes ) != 'array' )
			$nodes = array( $nodes );
		
		foreach( $nodes as $node ) {
			$xpath = $xml->xpath('/Publications/Item[Code = "'.$code.'"]/'.$node);
			foreach($xpath as $temp) {
				if( $returnnode == '' ) {
					$result[$node] = $temp;
					}
				else {
					foreach( $temp->$returnnode as $val ) {
						$result[$node][] = $val;
						}
					}
				}
			}
		return $result;
		}

	// The ad_sizes DB table is the source of truth for a publication's ad
	// sizes (see the "narrow update" migration note in advertisement.php /
	// engine/ajax.php) - this rebuilds the <AdSizes> block for one magazine
	// entirely from that table and writes both the local PMD file and its
	// Switch-facing long-named counterpart. Callers still own pushing the
	// result to Switch (via SwitchSend_TESZT), matching how add_ad already
	// separates "write the file" from "send the file".
	function regenerateAdSizesInPmd( $magazineCode ) {
		$mag = sql_get( 'magazines', 'code="'.$magazineCode.'"', 'id' );
		if( empty( $mag[0][0] ) ) return false;

		$sizes = sql_aget( 'ad_sizes', 'magazine_id="'.$mag[0][0].'"', '*' );

		$xmlFile = TRKPATH.'/xml/'.PMD.'.xml';
		$newxml = simplexml_load_file( $xmlFile );

		$xpath = $newxml->xpath( '/Publications/Item[Code = "'.$magazineCode.'"]' );
		if( empty( $xpath ) ) return false;
		$item = $xpath[0];

		if( isset( $item->AdSizes ) ) {
			$dom = dom_import_simplexml( $item->AdSizes );
			$dom->parentNode->removeChild( $dom );
			}
		$adSizesNode = $item->addChild( 'AdSizes' );

		foreach( $sizes as $size ) {
			$txt = $size['size'].' '.$size['orient'].', '.$size['cover'].': '.$size['width'].' x '.$size['height'].' mm';
			$adSizesNode->addChild( 'value', $txt );
			}

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML( $newxml->asXML() );
		$dom->formatOutput = true;

		file_put_contents( $xmlFile, $dom->saveXML() );
		$pmdName = pmdDevSafeName( PMD_LONG.'.xml' );
		file_put_contents( TRKPATH.'/xml/'.$pmdName, $dom->saveXML() );

		return $pmdName;
		}

	// The "1/1" Advertisement Size is always the trimmed size of the
	// publication's Inside part (PARTTYPES code "BEL") - rather than making
	// users re-enter a value the Parts & Color data already carries, keep
	// ad_sizes in sync with it directly whenever that part's size becomes
	// known: Regular's Parts & Color save, Regular's new-issue creation,
	// and Adhoc's publication creation. Only applies to Full/Hybrid
	// (HAVE_PARTS) - Resize/Auto parts don't carry a real trimmed size
	// (see color.php's hidden trim_x/trim_y falling back to a fixed A4
	// default), so it isn't safe to treat as sizing info there. Callers
	// pass $workflow explicitly rather than having this look it up via
	// collectFromXml(), since the Adhoc-creation caller runs before the
	// new magazine's PMD <Item> exists to look up.
	function syncInsidePartAdSize( $magazineId, $magazineCode, $workflow, $width, $height ) {
		if( !in_array( $workflow, HAVE_PARTS ) ) return;
		if( empty( $width ) || empty( $height ) ) return;

		$orient = ( $width > $height ) ? 'landscape' : 'portrait';

		$existing = sql_aget( 'ad_sizes', "magazine_id='".$magazineId."' AND size='1/1'", '*' );
		if( !empty( $existing[0]['id'] ) ) {
			if( $existing[0]['width'] == $width && $existing[0]['height'] == $height && $existing[0]['orient'] == $orient ) return;
			sql_update( 'ad_sizes', "orient='".$orient."', cover='trim', width='".$width."', height='".$height."'", "id='".$existing[0]['id']."'" );
			}
		else {
			$names = array( 'magazine_id', 'size', 'orient', 'cover', 'width', 'height' );
			$values = array( $magazineId, '1/1', $orient, 'trim', $width, $height );
			sql_add( 'ad_sizes', $names, $values );
			}

		$pmdName = regenerateAdSizesInPmd( $magazineCode );
		if( $pmdName ) {
			SwitchSend_TESZT( array( "event" => "xml_data", "jobCode" => $magazineCode ), array( "name" => $pmdName, "path" => "xml" ) );
			}
		}

	function changeJOBXmlDatabase( $operation, $values, $xml2 = 'client/xml/job.xml' ) {
		$xml = simplexml_load_file( $xml2 );
		$deny = array( 'addRUser', 'publisher', 'magazine', 'old', 'old_code', 'xml_go', $values['deny'], 'deny', 'type', 'code', 'CustomCode_2', 'counter_parts', 'Client2', 'adhocUser' );
		
		switch( $operation ) {
			case 'delete':
				$code = $values['code'];
				$publisher = $values['publisher'];
				$job = sql_get( 'jobs', 'code="'.$code.'"', '*' );
				
				sql_delete( 'jobs', 'id="'.$job[0][0].'"' );
				if( is_dir( '../client/packages/'.$code ) )
					delTree('../client/packages/'.$code );
				
				$xpath = $xml->xpath('/Job');
				
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $code && $temp->Item[$i]->Publisher == $publisher ) {
							$dom=dom_import_simplexml($temp->Item[$i]);
							$dom->preserveWhiteSpace = false;
							$dom->parentNode->removeChild($dom);
							}
						}
					}
				break;
				
			case 'modify':
				$trimsize = '';
				$xpath = $xml->xpath('/Job');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $values['Code'] && $temp->Item[$i]->Publisher == $values["Publisher"] )
							break;
						}
					}

				foreach( $values as $key => $val ) {
					if( !in_array( $key, $deny ) ) {
						if( strstr( $key, 'TrimSize_' ) ) {
							if( $trimsize == '' ) {
								$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
								}
							}
			
						switch( $key ) {
							case 'TrimSize_x':
								break;
							case 'TrimSize_y':
								$xml->Item[$i]->TrimSize = $trimsize;
								break;
							default:
								$xml->Item[$i]->$key = $val;
								break;
							}	
			
						}
					}
				
				break;
			
			case 'add':
				$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
				$code = $xml->addChild( 'Item' );
				$code->addChild( 'Name', $values['Name'] );
				$code->addChild( 'Code', $values['Code'] );
				$code->addChild( 'Publisher', $values['Publisher'] );
				$code->addChild( 'ColorManagement', $values['ColorManagement'] );
				$code->addChild( 'TrimSize', $trimsize );
				$code->addChild( 'FacingPages', $values['FacingPages'] );
				$code->addChild( 'Deadline', str_replace( " ", "T", $values['Deadline'] ) );
				$remote = $code->addChild( 'RemoteStorage' );
					$remote->addChild( 'Address', '' );
					$remote->addChild( 'Login', '' );
					$remote->addChild( 'Password', '' );
					$remote->addChild( 'Port', '' );
					$remote->addChild( 'Path', '' );
					$remote->addChild( 'Binary', '' );
					$remote->addChild( 'Passive', '' );
				$code->addChild( 'Mails', $values['Mails'] );
				$code->addChild( 'MailComm', $values['MailComm'] );
				break;			
			}

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
	
		file_put_contents( $xml2, $dom->saveXML() );
		}

	// Delivers the PMD XML to Switch. $realFile is the actual filename on
	// disk under xml/ and must never be modified - the fast lookup by
	// SwitchLogin/curl depends on it existing. $switchLabel is only the
	// name Switch is told the upload is - this is where the dev-safety
	// suffix belongs, kept deliberately separate from $realFile so a dev
	// box can never accidentally look up (or fail to find) the wrong file.
	function SendPmdXmlToSwitch( $realFile, $switchLabel, $connectTimeout = 2, $totalTimeout = 5 ) {
		global $token;

		if( !switchBulkSyncAllowed() ) {
			error_log( "SendPmdXmlToSwitch blocked: DEV environment restricts Switch communication to TestCo, and the local dataset currently references another publisher." );
			return false;
			}

		$filePath = TRKPATH.'/xml/'.$realFile;
		if( !is_file( $filePath ) ) {
			return false;
			}

		SwitchLogin();
		if( empty( $token ) ) {
			return false;
			}

		$mime = mime_content_type( realpath( $filePath ) );

		$headers = array(
			"Authorization: ".$token."",
			"Content-Type: multipart/form-data",
			"Connection: Keep-Alive",
			);

		$data = array(
			"flowId" => FLOWID_TESZT,
			"objectId" => OBJECTID_TESZT,
			"jobName" => $switchLabel,
			"metadata" => json_encode( array( metadata( "spMF_5", "event", "xml_data" ) ) ),
			"file[0][path]" => "",
			"file[0][file]" => new CurlFile( realpath( $filePath ), $mime, $switchLabel ),
			);

		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout );
		curl_setopt( $ch, CURLOPT_TIMEOUT, $totalTimeout );
		curl_setopt( $ch, CURLOPT_URL, SWITCHURL );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "POST" );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_exec( $ch );
		$errno = curl_errno( $ch );
		$httpCode = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
		curl_close( $ch );

		return ( $errno === 0 && $httpCode >= 200 && $httpCode < 300 );
		}

	// Durable fallback for when the synchronous attempt above fails or times
	// out. The cron worker (client/cron/switch_sync_worker.php) retries
	// queued jobs with a more generous timeout until they succeed.
	function QueueSwitchRetry( $jobType, $payload ) {
		global $con;
		sql_add(
			'switch_sync_queue',
			array( 'job_type', 'payload', 'next_attempt_at' ),
			array(
				mysqli_real_escape_string( $con, $jobType ),
				mysqli_real_escape_string( $con, json_encode( $payload ) ),
				date( 'Y-m-d H:i:s', time() + 60 ),
				)
			);
		}

	// Confirmed directly against Switch's "commands" flow: on an xml_data
	// event it writes the received file straight to disk under the name
	// it's given - so this suffix is not cosmetic, it's the actual
	// enforcement keeping a dev upload from landing on top of production's
	// live Publications_Master_Data.xml. Every local write and every
	// Switch-facing label derived from PMD_LONG must go through this, so
	// an unsuffixed copy can never be produced (or sent) from a dev host -
	// not "shouldn't", structurally can't, because there's no other path
	// that builds this filename.
	function pmdDevSafeName( $base ) {
		if( stripos( gethostname(), 'dev' ) !== false ) {
			$dot = strrpos( $base, '.' );
			$base = substr( $base, 0, $dot ) . '_DEV' . substr( $base, $dot );
			}
		return $base;
		}

	function XMLUpload2( $file ) {
		$realFile = $file;
		// The name Switch actually uses for this file is PMD_LONG
		// (Publications_Master_Data), not PMD (pmd - that's only the
		// local on-disk short name this app reads/writes for itself), so
		// the label sent to Switch must be built from PMD_LONG regardless
		// of what local filename was passed in as $file.
		$switchLabel = pmdDevSafeName( PMD_LONG.'.xml' );
		// $realFile (what we read from disk) never changes - no
		// _DEV-suffixed copy actually exists locally.

		// Fast path: try synchronously with a short timeout, so the common
		// case (Switch healthy) is exactly as immediate as before. Only on
		// failure/timeout do we fall back to the durable retry queue,
		// rather than blocking the user's request on a slow/dead Switch.
		$ok = SendPmdXmlToSwitch( $realFile, $switchLabel, 2, 5 );
		if( !$ok ) {
			QueueSwitchRetry( 'pmd_xml', array( 'realFile' => $realFile, 'switchLabel' => $switchLabel ) );
			}
		}
	
	function XMLUpload( $xml2 = 'client/xml/'.PMD.'.xml' ) {
		if( is_file( 'xml/'.PMD.'.xml' ) ) {
			$xpath = 'xml/'.PMD.'.xml';
			}
		elseif( is_file( $xml2 ) ) {
			$xpath = $xml2;
			}
		else {
			$xpath = 'client/xml/'.PMD.'.xml';
			}

		$array = array(
			"event" => "xml_data",
			);
					
		$file = array( 
			"name" => "Publications_Master_Data_NT.xml",
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );		
		}

	function changeXmlDatabase_ext( $operation, $values, $xml2 = '/var/www/html/client/xml/'.PMD.'.xml' ) {
		$xml = simplexml_load_file( $xml2 );
		$deny = array( 'addRUser', 'ApprovedComments', 'publisher', 'magazine', 'old', 'old_code', 'xml_go', $values['deny'], 'deny', 'type', 'code', 'CustomCode_2', 'counter_parts', 'Client2', 'adhocUser' );
		
		switch( $operation ) {
			case 'modify':
				$trimsize = '';
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $values['old_code'] )
							break;
						}
					}
					
				foreach( $values as $key => $val ) {
					if( !in_array( $key, $deny ) ) {
						if( strstr( $key, 'TrimSize_' ) ) {
							if( $trimsize == '' ) {
								$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
								}
							}
						
						switch( $key ) {
							case 'CustomCode':
								if( $values['CustomCode'] == 'No' )
									$xml->Item[$i]->CustomCode = 'No';
								else 
									$xml->Item[$i]->CustomCode = $values['CustomCode'];
								break;
							case 'TrimSize_x':
								break;
							case 'TrimSize_y':
								$xml->Item[$i]->TrimSize = $trimsize;
								break;
							case 'Cover':
							case 'Content':
							case 'Insert':
								$xml->Item[$i]->ColorManagement->$key = $val;
								break;
							default:
								$xml->Item[$i]->$key = $val;
								break;
							}	
			
						}
					}
				break;
				
			case 'delete':
				$code = $values['old_code'];
				$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
				
				$temp = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'"', '*' );
				for( $i = 0; $i < count( $temp ); $i++ ) {
					sql_delete( 'ads', 'pub_id="'.$temp[$i][0].'"' );
					sql_delete( 'parts', 'pub_id="'.$temp[$i][0].'"' );
					$packs = sql_get( 'packages', 'publication_id="'.$temp[$i][0].'"', '*' );
					for( $y = 0; $y < count( $packs ); $y++ ) {
						sql_delete( 'package_info', 'package_id="'.$packs[$y][0].'"' );
						}
					sql_delete( 'packages', 'publication_id="'.$temp[$i][0].'"' );
					}
				
				sql_delete( 'magazines', 'id="'.$magazine[0][0].'"' );
				if( is_dir( '../client/packages/'.$code ) )
					delTree('../client/packages/'.$code );
				
				$xpath = $xml->xpath('/Publications');
				
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $code ) {
							$dom=dom_import_simplexml($temp->Item[$i]);
							$dom->preserveWhiteSpace = false;
							$dom->parentNode->removeChild($dom);
							}
						}
					}
				break;
				
			case 'add':
				$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
				$code = $xml->addChild( 'Item' );
				$code->addChild( 'Name', $values['Name'] );
				$code->addChild( 'Code', $values['Code'] );
				$code->addChild( 'Publisher', $values['Publisher'] );
				$code->addChild( 'Language', $values['Language'] );
				$code->addChild( 'Period', $values['Period'] );
				$code->addChild( 'AdAutoProof', $values['AdAutoProof'] );
				$code->addChild( 'Workflow', $values['Workflow'] );
				$code->addChild( 'FlatplanStages', $values['FlatplanStages'] );
				$code->addChild( 'Resolution', $values['Resolution'] );
				$code->addChild( 'IDversion', $values['IDversion'] );
				$code->addChild( 'Enhance', $values['Enhance'] );
	
				$color = $code->addChild( 'ColorManagement' );
				$color->addChild( 'Cover', $values['Cover'] );
				$color->addChild( 'Content', $values['Content'] );
				$color->addChild( 'Insert', $values['Insert'] );
	
				$code->addChild( 'PDFstandard', $values['PDFstandard'] );
				$code->addChild( 'ArchiveStorage', $values['ArchiveStorage'] );
				$code->addChild( 'OutputFormat', $values['OutputFormat'] );
				$code->addChild( 'CustomCode', $values['CustomCode'] );
				$code->addChild( 'ImageRename', $values['ImageRename'] );
				$code->addChild( 'TrimSize', $trimsize );
				$code->addChild( 'LocalStorage', $values['LocalStorage'] );
				$code->addChild( 'RemoteStorage', $values['RemoteStorage'] );
				$code->addChild( 'Parent', $values['Parent'] );
				$code->addChild( 'FinalOutput', $values['FinalOutput'] );
				$code->addChild( 'ArchiveMode', $values['ArchiveMode'] );
				$code->addChild( 'Mails', "" );
				$code->addChild( 'MailComm', "No" );
				$ads = $code->addChild( 'AdSizes' );
				//$ads->addChild( 'value', '1/1 portrait, trim: '.$values['TrimSize_x'].' x '.$values['TrimSize_y'].' mm' );
				$code->addChild( 'WebImages', "No" );
				break;
			}

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;
	
		file_put_contents( $xml2, $dom->saveXML() );
		}

	function changeXmlDatabase( $operation, $values, $xml2 = '/var/www/html/client/xml/'.PMD.'.xml' ) {
		error_log( "CHANGEXML DEBUG" );
		error_log( "--".$operation."--" );
		error_log( $xml2 );
		$xml = simplexml_load_file( $xml2 );
		$deny = array( 'addRUser', 'ApprovedComments', 'pn', 'Uploadable', 'Deadline', 'publisher', 'magazine', 'old', 'old_code', 'xml_go', $values['deny'] ?? '', 'deny', 'type', 'code', 'CustomCode_2', 'counter_parts', 'Client2', 'adhocUser' );
		
		switch( $operation ) {
			case 'modify':
				$trimsize = '';
				$xpath = $xml->xpath('/Publications');
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $values['old_code'] )
							break;
						}
					}
				foreach( $values as $key => $val ) {
					if( !in_array( $key, $deny ) ) {
						if( strstr( $key, 'TrimSize_' ) ) {
							if( $trimsize == '' ) {
								$trimsize = $values['TrimSize_x']." x ".$values['TrimSize_y']." mm";
								}
							}
			
						switch( $key ) {
							case 'CustomCode':
								if( $values['CustomCode'] == 'No' )
									$xml->Item[$i]->CustomCode = 'No';
								else 
									$xml->Item[$i]->CustomCode = $values['CustomCode'];
								break;
							case 'TrimSize_x':
								break;
							case 'TrimSize_y':
								$xml->Item[$i]->TrimSize = $trimsize;
								break;
							case 'Cover':
							case 'Content':
							case 'Insert':
								$xml->Item[$i]->ColorManagement->$key = $val;
								break;
							default:
								error_log( "--".$key." => ".$val."--" );
								$xml->Item[$i]->$key = $val;
								break;
							}

						}
					}

				// Client edits change who "owns" this magazine, which MariaDB
				// tracks separately (magazines.publisher_id) from the PMD XML
				// (this Item's <Client>/<Publisher> nodes) - the loop above only
				// ever touches the XML side. Without this, a Client change here
				// would silently desync MariaDB from what PMD/Switch now say,
				// breaking the three-way consistency (MariaDB -> PMD -> Switch)
				// this file exists to keep.
				if( array_key_exists( 'Client', $values ) && $values['Client'] != '' ) {
					$publisher = sql_get( 'publishers', 'name="'.$values['Client'].'"', 'id' );
					if( !empty( $publisher[0][0] ) ) {
						sql_update( 'magazines', 'publisher_id="'.$publisher[0][0].'"', 'code="'.$values['old_code'].'"' );
						}
					}
				break;

			case 'delete':
				$code = $values['old_code'];
				$magazine = sql_get( 'magazines', 'code="'.$code.'"', '*' );
				
				$temp = sql_get( 'publications', 'magazine_id="'.$magazine[0][0].'"', '*' );
				for( $i = 0; $i < count( $temp ); $i++ ) {
					sql_delete( 'ads', 'pub_id="'.$temp[$i][0].'"' );
					sql_delete( 'parts', 'pub_id="'.$temp[$i][0].'"' );
					$packs = sql_get( 'packages', 'publication_id="'.$temp[$i][0].'"', '*' );
					for( $y = 0; $y < count( $packs ); $y++ ) {
						sql_delete( 'package_info', 'package_id="'.$packs[$y][0].'"' );
						}
					sql_delete( 'packages', 'publication_id="'.$temp[$i][0].'"' );
					}
				
				sql_delete( 'magazines', 'id="'.$magazine[0][0].'"' );
				if( is_dir( TRKPATH.'/packages/'.$code ) )
					delTree( TRKPATH.'/packages/'.$code );
				
				$xpath = $xml->xpath('/Publications');
				
				foreach($xpath as $temp) {
					for( $i = 0; $i < count( $temp->Item ); $i++ ) {
						if( $temp->Item[$i]->Code == $code ) {
							$dom=dom_import_simplexml($temp->Item[$i]);
							$dom->preserveWhiteSpace = false;
							$dom->parentNode->removeChild($dom);
							}
						}
					}
				break;
				
			case 'add':
				// Guarantee at most one <Item> per Code before adding the
				// new one. Without this, a publication removed at the DB
				// level without a matching XML-side delete (e.g. a manual
				// cleanup that only touched the DB) leaves a stale <Item>
				// behind, and a later 'add' for the same Code - reusing
				// that same code, as dev-box testing does - produces two
				// entries for one publication instead of replacing the
				// stale one.
				$xpath = $xml->xpath('/Publications');
				foreach( $xpath as $temp ) {
					for( $i = count( $temp->Item ) - 1; $i >= 0; $i-- ) {
						if( (string) $temp->Item[$i]->Code == $values['Code'] ) {
							$dupDom = dom_import_simplexml( $temp->Item[$i] );
							$dupDom->parentNode->removeChild( $dupDom );
							}
						}
					}

				if( $values["Type"] == "Adhoc" ) {
					$code = $xml->addChild( 'Item' );
					$code->addChild( 'Name', $values['Name'] );
					$code->addChild( 'Code', $values['Code'] );
					$code->addChild( 'Publisher', $values['Client'] );
					$code->addChild( 'Language', $values['Language'] );
					$code->addChild( 'Workflow', $values['Workflow'] );
					$code->addChild( 'FlatplanStages', $values['FlatplanStages'] );
					$code->addChild( 'PageNumbering', $values['PageNumbering'] );
					$code->addChild( 'Resolution', $values['Resolution'] );
					$code->addChild( 'Enhance', $values['Enhance'] );
					$code->addChild( 'PDFstandard', $values['PDFstandard'] );
					// ArchiveStorage/RemoteStorage/Parent/FinalOutput are optional -
					// no Adhoc or Regular creation form actually submits them - so
					// referencing $values[...] directly threw an "Undefined array
					// key" Warning on every single publication creation. Harmless
					// on its own, but display_errors is On in this dev environment
					// (see SYSTEM_STATE.md), so the warning text got printed straight
					// into the ajax response body ahead of the final json_encode(),
					// corrupting the JSON - jQuery's dataType:'json' then silently
					// failed to parse it and never ran the success callback, leaving
					// the Create dialog open even though the row was already saved.
					$code->addChild( 'ArchiveStorage', $values['ArchiveStorage'] ?? '' );
					$code->addChild( 'OutputFormat', $values['OutputFormat'] );
					$code->addChild( 'CustomCode', $values['CustomCode'] );
					$code->addChild( 'ImageRename', $values['ImageRename'] );
					$code->addChild( 'LocalStorage', $values['LocalStorage'] );
					$code->addChild( 'RemoteStorage', $values['RemoteStorage'] ?? '' );
					$code->addChild( 'Parent', $values['Parent'] ?? '' );
					$code->addChild( 'FinalOutput', $values['FinalOutput'] ?? '' );
					$code->addChild( 'Mails', $values['Mails'] );
					$code->addChild( 'MailComm', "No" );
					$ads = $code->addChild( 'AdSizes' );
					//$ads->addChild( 'value', '1/1 portrait, trim: '.$values['TrimSize_x'].' x '.$values['TrimSize_y'].' mm' );
					$code->addChild( 'WebImages', "No" );
					$code->addChild( 'Type', $values['Type'] );
					$code->addChild( 'Client', $values['Client'] );
					}
					
				if( $values["Type"] == "Regular" ) {
					$code = $xml->addChild( 'Item' );
					$code->addChild( 'Name', $values['Name'] );
					$code->addChild( 'Code', $values['Code'] );
					$code->addChild( 'Publisher', $values['Client'] );
					$code->addChild( 'Language', $values['Language'] );
					$code->addChild( 'Workflow', $values['Workflow'] );
					$code->addChild( 'FlatplanStages', $values['FlatplanStages'] );
					$code->addChild( 'PageNumbering', $values['PageNumbering'] );
					$code->addChild( 'Resolution', $values['Resolution'] );
					$code->addChild( 'Enhance', $values['Enhance'] );	
					$code->addChild( 'PDFstandard', $values['PDFstandard'] );
					$code->addChild( 'ArchiveStorage', $values['ArchiveStorage'] ?? '' );
					$code->addChild( 'OutputFormat', $values['OutputFormat'] );
					$code->addChild( 'CustomCode', $values['CustomCode'] );
					$code->addChild( 'ImageRename', $values['ImageRename'] );
					$code->addChild( 'LocalStorage', $values['LocalStorage'] );
					$code->addChild( 'RemoteStorage', $values['RemoteStorage'] ?? '' );
					$code->addChild( 'Parent', $values['Parent'] ?? '' );
					$code->addChild( 'FinalOutput', $values['FinalOutput'] ?? '' );
					// No Regular creation form (create.php) actually submits this -
					// same "Undefined array key" class of bug as the Adhoc fields
					// above, just missed for this one when those were fixed.
					$code->addChild( 'ArchiveMode', $values['ArchiveMode'] ?? '' );
					$code->addChild( 'Mails', "" );
					$code->addChild( 'MailComm', "Yes" );
					$ads = $code->addChild( 'AdSizes' );
					//$ads->addChild( 'value', '1/1 portrait, trim: '.$values['TrimSize_x'].' x '.$values['TrimSize_y'].' mm' );
					$code->addChild( 'WebImages', "YES" );
					$code->addChild( 'Type', $values['Type'] );
					$code->addChild( 'Client', $values['Client'] );
					}
				break;
			}
		
		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->loadXML($xml->asXML());
		$dom->formatOutput = true;

		// This is THE PMD sync point - every publication/magazine create or
		// edit ends up here, and a failed write is otherwise completely
		// silent: file_put_contents() returns false (not an exception), and
		// nothing downstream checks it, so a permissions problem (confirmed
		// to happen in practice - client/xml/pmd.xml was found root:root
		// instead of www-data:www-data for 12 days, silently dropping every
		// single new publication's PMD entry the whole time with no error
		// anywhere) can go completely unnoticed until someone manually
		// diffs the DB against the XML. Logging loudly here is the
		// independent-of-cron safety net; see SYSTEM_STATE.md's "PMD file
		// ownership" section for the cron-based self-heal that's the other
		// half of this fix.
		if( file_put_contents( $xml2, $dom->saveXML() ) === false ) {
			error_log( "CRITICAL: PMD XML WRITE FAILED - '".$xml2."' could not be written (permissions? disk full?). "
				."is_writable=".var_export( is_writable( $xml2 ), true )." running as uid=".getmyuid()
				.". This publication/magazine change was saved to the database but is now OUT OF SYNC with PMD "
				."until this is fixed and the write is retried." );
			}

		// Every call to changeXmlDatabase() changes the on-disk dataset, so
		// every call must push it to Switch - this used to be an inline
		// SwitchSend_TESZT() call here, bypassing both switchBulkSyncAllowed()
		// (it used switchClientAllowed() instead, which requires a jobCode/
		// Code and so silently returned "blocked" for this dataset-wide,
		// no-single-job event - every call here was being dropped) and the
		// dev-hostname _DEV suffix (it hardcoded PMD_LONG.".xml" with no
		// suffix logic at all). XMLUpload2() already has both fixed
		// correctly and already has the durable retry-queue fallback, so
		// route through it instead of maintaining a second, divergent copy
		// of the same upload logic.
		XMLUpload2( PMD.'.xml' );
		}
	
	function toSwitch( $type, $job, $saveTo, $root ) {
		$array = array();
		$info = explode( '|', $job );
		
		$job = sql_get( $info[0], 'id="'.$info[1].'"', '*'  );
		$magazine = sql_get( "magazines", "id='".$job[0][2]."'", "code" );

		$newxml = simplexml_load_file( TRKPATH.'/xml/'.PMD.'.xml' );
		$xpath = $newxml->xpath('/Publications');
		foreach($xpath as $temp) {
			for( $x = 0; $x < count( $temp->Item ); $x++ ) {
				if( $temp->Item[$x]->Code == $magazine[0][0] ) {
					break;
					}
				}
			}
				
		switch( $type ) {
			case 'deleted':
				$job = sql_get( $info[0], 'id="'.$info[1].'"', '*'  );
				$publisher = sql_get( 'publishers', 'id="'.$job[0][1].'"', 'name' );
				$magazine = sql_get( 'magazines', 'id="'.$job[0][2].'"', 'code' );
				
				$array["client"] = $publisher[0][0];
				$array["jobCode"] = $magazine[0][0];
				$array["issue"] = $job[0][10];
				$array["event"] = 'deleted_issue';
				break;
				
			case 'created':
				$job = sql_get( $info[0], 'id="'.$info[1].'"', '*'  );
				$publisher = sql_get( 'publishers', 'id="'.$job[0][1].'"', 'name' );
				$magazine = sql_get( 'magazines', 'id="'.$job[0][2].'"', 'code' );
				
				$array["client"] = $publisher[0][0];
				$array["jobCode"] = $magazine[0][0];
				$array["issue"] = $job[0][10];
				$array["event"] = 'issue_created';
				break;
				
			case 'new_publication':
				error_log( $info[0].", ".$info[1] );
				$job = sql_get( $info[0], 'id="'.$info[1].'"', '*'  );
				$magazine = sql_get( 'magazines', 'id="'.$job[0][2].'"', 'code' );

				// The issue is already tied to a publisher via the magazine's
				// own PMD entry, so this isn't load-bearing - but it doesn't
				// hurt to carry it here too, DB-sourced rather than read back
				// from the XML, so it can't drift the way Publisher/Client
				// can on the whole-dataset Item. resolveJobPublisherName()
				// (in switchAPI.php) falls back to the publication's `owner`
				// for Adhoc jobs, where publisher_id is always "0" by
				// convention even for a known client - using publisher_id
				// alone here used to silently produce an empty client for
				// every known-client Adhoc job, not just genuinely unknown
				// ones.
				$array['client'] = resolveJobPublisherName( $magazine[0][0] );

				if( $job[0][1] == "0" ) {
					$array['numOfPages'] = $job[0][6];
					$parts = sql_get( 'parts', 'pub_id="'.$job[0][0].'"', '*' );
					
					$array['parts'] = array();
					for( $i = 0; $i < count( $parts ); $i++ ) {
						$array['parts']['part|'.$i]['name'] = $parts[$i][2];
						
						if( $temp->Item[$x]->PageNumbering == "American" ) {
							$array['parts']['part|'.$i]['pages'] = $parts[$i][3];
							}
						else {
							$array['parts']['part|'.$i]['place'] = $parts[$i][3];
							}
							
						$array['parts']['part|'.$i]['color'] = $parts[$i][4];
						$array['parts']['part|'.$i]['grayscale'] = $parts[$i][7];
						$array['parts']['part|'.$i]['size'] = $parts[$i][5];
						}
						
					$array['specificName'] = $job[0][17];
					$array['uploadable'] = $job[0][8];
					$array["code"] = $magazine[0][0];
					$array['issueCode'] = $job[0][10];
					$array['deadline'] = str_replace( ' ', 'T', trim( $job[0][11] ) );
					$array['status'] = ($job[0][12] == "current" ? "active" : $job[0][12] );
					}
					
				else {
					$array['numOfPages'] = $job[0][6];
					$parts = sql_get( 'parts', 'pub_id="'.$job[0][0].'"', '*' );
					$array['parts'] = array();
					for( $i = 0; $i < count( $parts ); $i++ ) {
						$array['parts']['part|'.$i]['name'] = $parts[$i][2];
						
						if( $temp->Item[$x]->PageNumbering == "American" ) {
							$array['parts']['part|'.$i]['pages'] = $parts[$i][3];
							}
						else {
							$array['parts']['part|'.$i]['place'] = $parts[$i][3];
							}						

						$array['parts']['part|'.$i]['color'] = $parts[$i][4];
						$array['parts']['part|'.$i]['grayscale'] = $parts[$i][7];
						$array['parts']['part|'.$i]['size'] = $parts[$i][5];
						}
						
					$array['specificName'] = $job[0][17];
					$array['uploadable'] = $job[0][8];
					$array["code"] = $magazine[0][0];
					$array['issueCode'] = $job[0][10];
					$array['deadline'] = str_replace( ' ', 'T', trim( $job[0][11] ) );						
					$array['status'] = ($job[0][12] == "current" ? "active" : $job[0][12] );
					
					}
					
				$name = explode( "/", $saveTo );
				$name = $name[ (count($name)-1) ];					
				break;			
			}
		/*
		if( $job[0][25] == "unknown") {
			$array["client"] = "";
			}
		*/
		$myxml = array_to_xml( $array, $root );
		$dom = new DOMDocument();
		$dom->loadXML($myxml);
		$dom->formatOutput = true;

		if( $type == 'new_publication' ) {
			file_put_contents( "../xml/".$name.".xml", $dom->saveXML() );
			}

		// $array gets rebuilt from scratch here as just the upload's event
		// wrapper - the rich per-issue data above (client, parts, etc.) only
		// ever went into the XML file content, never into what's passed to
		// SwitchSend_TESZT() as $datas. Confirmed via the test protocol:
		// that meant switchClientAllowed() always saw an empty jobCode and
		// blocked every per-issue snapshot upload outright, regardless of
		// client - not a hypothetical, every call was silently dropped.
		// $magazine was resolved at the top of this function from the same
		// job/publication this snapshot is for.
		$array = array(
			"event" => "xml_data",
			"jobCode" => $magazine[0][0],
			);
			
		$file = array(
			"name" => $name.".xml",
			"path" => "xml",
			);
		$response = SwitchSend_TESZT( $array, $file );

		// This return value was previously discarded entirely - a blocked
		// or failed delivery (e.g. the DEV "TestCo only" gate in
		// switchClientAllowed(), which unknown-client Adhoc jobs can never
		// pass since they have no resolvable publisher name) left no trace
		// anywhere: the publication, parts, and accounts all got created
		// successfully and the create dialog closed normally, so nothing
		// indicated Switch never actually received this event.
		// Confirmed live 2026-07-29 (YXY32, an unknown-client Adhoc job).
		if( $response[0] === null || $response[0] === 'blocked' ) {
			error_log( "toSwitch('".$type."') did not reach Switch for jobCode='".$magazine[0][0]."': ".( $response[1] ?? 'no response' ) );
			}
		}
	
	function remove_from_xml( $xml, $target, $criteria ) {
		$sxe = simplexml_load_file( $xml );
		$sxe->asXML();
		
		$doc = new DOMDocument;
		$doc->preserveWhiteSpace = false;
		$doc->load( $xml );
		$xpath = new DOMXpath($doc);
		foreach($xpath->query('//'.$target.'[. = "'.$criteria.'"]') as $node) {
 			$node->parentNode->removeChild($node);
 			break;
			}
		
		file_put_contents( $xml , $doc->saveXML() );
		
		$sxe = simplexml_load_file( $xml );
		return $sxe->asXML();
		}
	
	function insert_to_xml( $xml, $insert, $target  ) {
		function simplexml_insert_after(SimpleXMLElement $insert, SimpleXMLElement $target) {
			$target_dom = dom_import_simplexml($target);
			$insert_dom = $target_dom->ownerDocument->importNode(dom_import_simplexml($insert), true);
    			if ($target_dom->nextSibling) {
        			return $target_dom->parentNode->insertBefore($insert_dom, $target_dom->nextSibling);
    				} else {
       				return $target_dom->parentNode->appendChild($insert_dom);
    				}
			}
		$sxe = simplexml_load_file( $xml );
		if( gettype( $insert ) == 'array' ) {
			$insert = array_to_xml( $insert[1], $insert[0] );
			}
		$insert = new SimpleXMLElement( $insert );
		$target = current($sxe->xpath('//'.$target.''));
		simplexml_insert_after($insert, $target);
		
		return $sxe->asXML();
		}

	function array_to_xml2( $array, $root ) {
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$root.'/>');
		xml_generate( $array, $xml );
					
		return $xml;
		}
		
	function array_to_xml( $array, $root ) {
		$xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><'.$root.'/>');
		xml_generate( $array, $xml );
					
		return $xml->asXML();
		}
	
	function magazine_XMLtoSQL( $array ) {
		$names = array();
		$values = array();
		$size_values = array();
		$ad_data = array();
		
		foreach( $array as $name => $value ) {
			$name = explode( '_', $name );
			$name = trim( strtolower( $name[0] ) );
			
			if( $name != 'value' &&  $name != 'mails' &&  $name != 'partial' ) {
				$names[] = $name;
				if( $name == 'trimsize' ) {
					$value = explode( ' ', $value );
					$value = $value[0].'x'.$value[2];
					}
				$values[] = $value;
				}
			if( $name == 'value' ) {
				$ad_data[] = $value;
				}
			}
		
		$index = array_search( "code", $names );
		$check = sql_get( 'magazines', 'code="'.$values[$index].'"', 'id' );
		if( $check[0][0] == '' && count($array) > 0 ) {
			$index = array_search( "publisher", $names );
			$p_id = sql_get( 'publishers', 'name="'.$values[$index].'"', 'id' );
			if( $p_id[0][0] != '' ) {
				$names[$index] = "publisher_id";
				$values[$index] = $p_id[0][0];
				}
			else {
				$p_n = array( 'name' );
				$p_v = array( $values[$index] );
				$id = sql_add( 'publishers', $p_n, $p_v );
				$names[$index] = "publisher_id";
				$values[$index] = $id;			
				}
			$id = sql_add( 'magazines', $names, $values );
			
			$size_names = array( 'magazine_id', 'size', 'orient', 'cover', 'width', 'height' );
		
			foreach( $ad_data as $data ) {
				$data = explode( ' ', $data );
				if( count( $data ) == 6 ) {
					$size_values = array( $id, '1/1', 'álló', $data[0], $data[2], $data[4] );
					}
				else {
					$size_values = array( $id, $data[0], substr( $data[1], 0, -1 ), substr( $data[2], 0, -1 ), $data[3], $data[5] );
					}
			
				sql_add( 'ad_sizes', $size_names, $size_values );	
				}
			}
		}
	
	function get_xml_datas( $xml, $node, $indexer = 0 ) {
		$txt = array();
		$result = $xml->xpath($node);
		if( gettype( $result[0] ) == 'object' ) {
			foreach($result[0] as $key) {
			
				//if( $key != '' && $key != '>' ) {
				if( $key != '>' ) {
					if( count( $key ) == 0 ) {
						$txt[$key->getName().'_'.$indexer] = ''.$key.'';
						}
					else {
						$temp = get_xml_datas( $xml, $node.'/'.$key->getName(), $indexer );
						foreach($temp as $key => $value) {
							$txt[$key] = $value;
							}
						}
					$indexer++;
					}
				}
			}
		return $txt;
		}

	function xml_generate($student_info, &$xml_student_info) {
		foreach($student_info as $key => $value) {
			if( strpos( $key, '|' ) ) {
				$key = explode( '|', $key );
				$key = $key[0];
				}
				
    	   	if(is_array($value)) {
				if(!is_numeric($key)) {
        	        $subnode = $xml_student_info->addChild("$key");
            	    xml_generate($value, $subnode);
        	    	}
	           	else {
					$subnode = $xml_student_info->addChild("item$key");
					xml_generate($value, $subnode);
					}
				}
	       	else {
    	       	$xml_student_info->addChild("$key","$value");
				}
			}
		}

?>