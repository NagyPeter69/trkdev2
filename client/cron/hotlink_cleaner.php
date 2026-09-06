<?php
set_include_path(__DIR__);
chdir(__DIR__);
header('Content-Type: text/html; charset=utf-8');

include_once( '../../engine/connect.php' );
include_once( '../../engine/engine.php' );

// None of the four hotlink-style tables (hotlinks, adhoc_hotlinks,
// flatplan_handout_hotlink, hotlinks_log) had any caretaking at all before
// this - confirmed by grep across every cron script. hotlinks.time_expire
// is even collected and validated at creation time (hotlinkApply.php) but
// never rechecked on access and never used to prune old rows, so it was
// purely cosmetic. adhoc_hotlinks/flatplan_handout_hotlink were never
// given an expiry concept in the first place - just a creation
// timestamp. Found while tracing a former developer's hardcoded
// impersonation shortcuts (2026-09-06): every row in `hotlinks` at the
// time turned out to belong to him, and `adhoc_hotlinks` alone had grown
// to 9000+ rows, ~1600 already orphaned (their magazine long gone) and
// ~1200 more orphaned by account, with the bulk of the rest over a year
// stale.

// 1. hotlinks: honor the expiry the sender actually chose.
$rows = sql_aget( "hotlinks", "time_expire < ".time(), "id" );
for( $i = 0; $i < count( $rows ); $i++ ) {
	sql_delete( "hotlinks", "id='".$rows[$i]["id"]."'" );
	}

// 2. adhoc_hotlinks: no expiry field exists, so staleness is inferred:
//    - the login target account is gone -> the link can never work again.
//    - the magazine itself is gone -> same.
//    - otherwise, fall back to the login account's own last-use date
//      (not this row's creation date - a job can stay legitimately open
//      for a long time) and only prune past a generous one-year mark.
$cutoff = time() - ( 86400 * 365 );
$rows = sql_aget(
	"adhoc_hotlinks a
		LEFT JOIN accounts u ON u.id = a.user_id
		LEFT JOIN magazines m ON m.id = a.magazine_id",
	"u.id IS NULL OR m.id IS NULL OR u.lastlogin < ".$cutoff,
	"a.id"
	);
for( $i = 0; $i < count( $rows ); $i++ ) {
	sql_delete( "adhoc_hotlinks", "id='".$rows[$i]["id"]."'" );
	}

// 3. flatplan_handout_hotlink: same idea as above, one-year backstop plus
//    orphan check against the handout it points at.
$rows = sql_aget(
	"flatplan_handout_hotlink h LEFT JOIN flatplan_handout d ON d.id = h.handoutid",
	"d.id IS NULL OR h.time < ".$cutoff,
	"h.id"
	);
for( $i = 0; $i < count( $rows ); $i++ ) {
	sql_delete( "flatplan_handout_hotlink", "id='".$rows[$i]["id"]."'" );
	}

// hotlinks_log is a small audit trail (tens of rows even after years of
// use, not thousands) - left alone deliberately, same reasoning as
// action_log surviving the account/job it references.

?>
