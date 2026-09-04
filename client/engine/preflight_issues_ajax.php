<?PHP
session_start();
header('Content-Type: application/json; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

// Backs the hover tooltip on the .preflightError marker (flatplan.php,
// flatplan_preview.php, engine/flatplan_ajax.php) - click still downloads
// the PDF report via filedownload.php, unchanged. This only ever returns
// what applyPreflightXml() (engine/preflightXml.php) has stored, which is
// empty until Switch actually starts sending an XML preflight report.
if( empty( $_SESSION['intra_user'] ) ) {
	print json_encode( array() );
	exit;
	}

$pageId = intval( $_GET['pageid'] ?? 0 );
if( !$pageId ) {
	print json_encode( array() );
	exit;
	}

$rows = sql_aget( 'preflight_issues', 'page_id="'.$pageId.'"', 'severity, message' );

$issues = array();
foreach( $rows as $row ) {
	if( empty( $row['severity'] ) ) continue;
	$issues[] = array( 'severity' => $row['severity'], 'message' => $row['message'] );
	}

// Errors before Warnings, so the more severe problems are always visible
// first without the caller having to scroll a long list.
usort( $issues, function( $a, $b ) {
	return ( $a['severity'] === $b['severity'] ) ? 0 : ( $a['severity'] === 'Error' ? -1 : 1 );
	});

print json_encode( $issues );
?>
