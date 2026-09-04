<?php
// Parses a callas pdfToolbox "pi4" preflight report XML (confirmed live
// 2026-09-04 against real reports from the "Magazin Page Check v5 (for
// hybrid)" Switch profile) into a flat list of {severity, message} pairs
// for storage in preflight_issues.
//
// Report shape (namespace http://www.callassoftware.com/namespace/pi4,
// declared as the document's default xmlns - SimpleXML resolves unprefixed
// property access against it without any explicit namespace handling):
//   <report>
//     <profile_info>
//       <rules>
//         <rule id="RUL5">
//           <display_name>An optional content group does not have a name</display_name>
//           ...
//         </rule>
//         ...
//       </rules>
//       ...
//     </profile_info>
//     <results>
//       <hits rule_id="RUL5" severity="Error">
//         <hit type="PageInfo" page="PAG1"> ... </hit>
//         <hit .../>            <!-- one per occurrence; can repeat -->
//       </hits>
//       ...
//       <fixup fixup_id="FIX1" count="6" severity="SUCCESS"></fixup>  <!-- not an issue, ignored -->
//     </results>
//   </report>
//
// <results> only lists rules that actually fired (a clean page has none),
// and only carries the rule id + how many times it fired - the human-
// readable text lives in <profile_info>/rules, keyed by that same id, so
// both have to be read and joined. <fixup> entries are automatic PDF
// fix-ups applied during preflight (not a problem to report - "severity"
// there is a completion status like SUCCESS, not Error/Warning), so only
// <hits> is read.
function extractPreflightIssues( $xmlPath ) {
	$xml = @simplexml_load_file( $xmlPath );
	if( !$xml ) {
		error_log( 'extractPreflightIssues(): failed to parse '.$xmlPath );
		return array();
		}

	$ruleNames = array();
	foreach( $xml->profile_info->rules->rule as $rule ) {
		$ruleNames[ (string) $rule['id'] ] = (string) $rule->display_name;
		}

	$issues = array();
	foreach( $xml->results->hits as $hits ) {
		$severity = (string) $hits['severity'];
		if( $severity !== 'Error' && $severity !== 'Warning' ) continue;

		$ruleId = (string) $hits['rule_id'];
		$message = $ruleNames[ $ruleId ] ?? ( 'Preflight rule '.$ruleId );

		$count = count( $hits->hit );
		if( $count > 1 ) {
			$message .= ' (×'.$count.')';
			}

		$issues[] = array( 'severity' => $severity, 'message' => $message );
		}

	// Errors before Warnings, stable otherwise (PHP's usort is stable since
	// 8.0) - sorted here at the source rather than only where it's
	// displayed, so every consumer of this list sees the same order.
	usort( $issues, function( $a, $b ) {
		if( $a['severity'] === $b['severity'] ) return 0;
		return ( $a['severity'] === 'Error' ) ? -1 : 1;
		});

	return $issues;
	}

// Replaces this page's stored preflight issues with a freshly parsed set.
// Called both from the live "_report" submission and from the retroactive
// pickup path (a report that arrived before the page's own pageinfo row
// existed) - same shape either way once a page_id is known.
function applyPreflightXml( $xmlPath, $pageId ) {
	$issues = extractPreflightIssues( $xmlPath );

	sql_delete( 'preflight_issues', 'page_id="'.$pageId.'"' );

	foreach( $issues as $issue ) {
		sql_add( 'preflight_issues', array( 'page_id', 'severity', 'message', 'time' ), array( $pageId, $issue['severity'], $issue['message'], time() ) );
		}
	}
?>
