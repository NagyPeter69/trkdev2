#!/bin/bash
# Copies live production data from trk-source into this box's (trk-stage's)
# nyomadake_intra database, on top of the already-migrated/enhanced schema.
#
# This is the "Step B" data-copy half of the cutover plan in SYSTEM_STATE.md /
# the production_release_plan memory - NOT a straight dump/restore:
#   1. Only tables that hold real production business data are replaced;
#      stage-only reference tables the rebuild added (color_standards,
#      calendar_holidays) are left untouched since source has nothing to
#      offer them, and any source table that's currently empty is skipped
#      rather than blindly truncating a stage table that might hold
#      meaningful config (e.g. switch_flows).
#   2. ad_sizes_old (source) maps into ad_sizes (stage) - same columns,
#      renamed + re-keyed table, not a straight name match.
#   3. Known confirmed-orphan chains (the exact ones cleanupPublicationRemnants()
#      in engine/xml_handler.php already treats as garbage: ads/partial_ads,
#      flatplan_articletypes, comments, flatplan_files, flatplan_planner,
#      flatplan_handout/flatplan_handout_hotlink, packages/package_info, parts
#      - all keyed by a pub_id/publication_id/handoutid/ads_id chain back to
#      `publications`) are deleted post-load as a safety net. Nothing else is
#      speculatively pruned - see SYSTEM_STATE.md's explicit note that a
#      broader referential-integrity sweep is a deliberate non-goal; only
#      remove what the app's own delete-flow logic already considers
#      unambiguously orphaned.
#   4. user_groups.accounts_findAccount is a schema delta added on trk-stage
#      after prod's schema was forked - a straight data copy would silently
#      reset it to the column default (0) for every group, disabling the
#      Find Account admin panel. Re-granted to SuperUser after load.
#
# Media/job files (client/packages, client/assets, etc.) are NOT handled by
# this script - trk-stage does not have disk space for the full set. See
# bin/simulate-job-files.sh for the sampling/stub approach used to validate
# file-dependent code paths without a full copy.
#
# Usage:
#   TRKDEV_SOURCE_HOST=10.10.30.64 TRKDEV_SOURCE_DB_PASS=root ./bin/migrate-prod-data.sh
#
# Required env vars (no defaults/hardcoded credentials on purpose):
#   TRKDEV_SOURCE_HOST      - trk-source's IP/hostname (SSH as root)
#   TRKDEV_SOURCE_DB_USER   - MySQL user on trk-source (root's own app uses 'root')
#   TRKDEV_SOURCE_DB_PASS   - MySQL password on trk-source
#
# Assumes: passwordless SSH as root to $TRKDEV_SOURCE_HOST (or sshpass with
# SSHPASS set - not handled here, do that in the calling shell), and
# passwordless local root MySQL access on this box (trk-stage's own .my.cnf).

set -euo pipefail

: "${TRKDEV_SOURCE_HOST:?set TRKDEV_SOURCE_HOST}"
: "${TRKDEV_SOURCE_DB_USER:?set TRKDEV_SOURCE_DB_USER}"
: "${TRKDEV_SOURCE_DB_PASS:?set TRKDEV_SOURCE_DB_PASS}"

DB=nyomadake_intra
STAMP=$(date +%Y%m%d%H%M%S)
BACKUP_DIR=/root/db-backups
WORKDIR=$(mktemp -d)
trap 'rm -rf "$WORKDIR"' EXIT

# Tables that hold real production business data - full-replace candidates.
# (ad_sizes handled separately below, since the source-side name differs.)
TABLES=(
	accounts publishers magazines publications packages package_info
	pageinfo parts assets ads partial_ads image_map comments comment_log
	hotlinks hotlinks_log adhoc_hotlinks ad_hoc ad_hoc_infobox ad_hoc_users
	calendar_events calendar_groups calendar_post calendar_reminder
	calendar_settings calendar_counters deliver_table filetransfer
	filetransfer_log flatplan_articletypes flatplan_files flatplan_handout
	flatplan_handout_hotlink flatplan_planner handout_log jobs jobs_pageinfo
	marquard_calendar tasklist tracker_settings user_groups userLogSettings
	user_log action_log system_log error_log article_colors switch_flows
)

echo "== 1/5: backing up trk-stage's current $DB before touching anything =="
mkdir -p "$BACKUP_DIR"
mysqldump --routines --triggers "$DB" | gzip > "$BACKUP_DIR/pre-migration-$STAMP.sql.gz"
echo "   saved to $BACKUP_DIR/pre-migration-$STAMP.sql.gz"

echo "== 2/5: fetching per-table row counts from trk-source =="
COUNTS_SQL="SELECT table_name, table_rows FROM information_schema.tables WHERE table_schema='$DB';"
ssh "root@$TRKDEV_SOURCE_HOST" "mysql -u'$TRKDEV_SOURCE_DB_USER' -p'$TRKDEV_SOURCE_DB_PASS' -N -e \"$COUNTS_SQL\"" \
	> "$WORKDIR/source_counts.tsv"
# ad_sizes_old isn't in TABLES (it maps to ad_sizes) - fetch its count too.
ssh "root@$TRKDEV_SOURCE_HOST" "mysql -u'$TRKDEV_SOURCE_DB_USER' -p'$TRKDEV_SOURCE_DB_PASS' -N -e \"SELECT 'ad_sizes_old', COUNT(*) FROM $DB.ad_sizes_old\"" \
	>> "$WORKDIR/source_counts.tsv"

source_count() {
	awk -F'\t' -v t="$1" '$1==t{print $2; found=1} END{if(!found) print 0}' "$WORKDIR/source_counts.tsv"
}

echo "== 3/5: copying data table-by-table (skipping any table empty on source) =="
for t in "${TABLES[@]}"; do
	n=$(source_count "$t")
	if [ "$n" = "0" ] || [ -z "$n" ]; then
		echo "   skip  $t (0 rows on source - leaving trk-stage's own data)"
		continue
	fi
	echo "   copy  $t ($n rows)"
	ssh "root@$TRKDEV_SOURCE_HOST" \
		"mysqldump -u'$TRKDEV_SOURCE_DB_USER' -p'$TRKDEV_SOURCE_DB_PASS' --no-create-info --complete-insert --skip-comments --single-transaction $DB $t" \
		> "$WORKDIR/$t.sql"
	mysql -e "SET FOREIGN_KEY_CHECKS=0; TRUNCATE TABLE $DB.$t;"
	mysql "$DB" < "$WORKDIR/$t.sql"
done

n=$(source_count ad_sizes_old)
if [ "$n" != "0" ] && [ -n "$n" ]; then
	echo "   copy  ad_sizes_old -> ad_sizes ($n rows)"
	ssh "root@$TRKDEV_SOURCE_HOST" \
		"mysqldump -u'$TRKDEV_SOURCE_DB_USER' -p'$TRKDEV_SOURCE_DB_PASS' --no-create-info --complete-insert --skip-comments --single-transaction $DB ad_sizes_old" \
		| sed 's/`ad_sizes_old`/`ad_sizes`/g' > "$WORKDIR/ad_sizes.sql"
	mysql -e "TRUNCATE TABLE $DB.ad_sizes;"
	mysql "$DB" < "$WORKDIR/ad_sizes.sql"
else
	echo "   skip  ad_sizes_old -> ad_sizes (0 rows on source)"
fi

echo "== 4/5: post-load fixups =="
echo "   re-granting accounts_findAccount to SuperUser (schema delta, not in source data)"
mysql "$DB" -e "UPDATE user_groups SET accounts_findAccount = 1 WHERE name = 'SuperUser';"

echo "   orphan cleanup (mirrors cleanupPublicationRemnants()'s own known-garbage chains)"
echo "   NOTE: pub_id/publication_id = 0 is a deliberate sentinel this codebase uses for"
echo "   'not tied to one issue' (magazine-level Parts templates, unassigned packages -"
echo "   same convention as magazines.publisher_id=0 for Adhoc jobs), NOT an orphan -"
echo "   every check below explicitly excludes it."
mysql "$DB" <<'SQL'
SET @before_ads = (SELECT COUNT(*) FROM ads);
DELETE FROM ads WHERE pub_id != 0 AND pub_id NOT IN (SELECT id FROM publications);
SET @after_ads = (SELECT COUNT(*) FROM ads);
SELECT 'ads' AS tbl, @before_ads - @after_ads AS orphans_deleted;

DELETE FROM partial_ads WHERE ads_id NOT IN (SELECT id FROM ads);
SELECT 'partial_ads' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM flatplan_articletypes WHERE pub_id != 0 AND pub_id NOT IN (SELECT id FROM publications);
SELECT 'flatplan_articletypes' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM comments WHERE pub_id != 0 AND pub_id NOT IN (SELECT id FROM publications);
SELECT 'comments' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM flatplan_files WHERE pubid != 0 AND pubid NOT IN (SELECT id FROM publications);
SELECT 'flatplan_files' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM flatplan_planner WHERE pub_id != 0 AND pub_id NOT IN (SELECT id FROM publications);
SELECT 'flatplan_planner' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM flatplan_handout WHERE pub_id != 0 AND pub_id NOT IN (SELECT id FROM publications);
SELECT 'flatplan_handout' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM flatplan_handout_hotlink WHERE handoutid NOT IN (SELECT id FROM flatplan_handout);
SELECT 'flatplan_handout_hotlink' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM packages WHERE publication_id != 0 AND publication_id NOT IN (SELECT id FROM publications);
SELECT 'packages' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM package_info WHERE package_id NOT IN (SELECT id FROM packages);
SELECT 'package_info' AS tbl, ROW_COUNT() AS orphans_deleted;

DELETE FROM parts WHERE pub_id != 0 AND pub_id NOT IN (SELECT id FROM publications);
SELECT 'parts' AS tbl, ROW_COUNT() AS orphans_deleted;
SQL

echo "== 5/5: final row-count report (trk-stage, after migration) =="
mysql "$DB" -N -e "
SELECT table_name, table_rows FROM information_schema.tables
WHERE table_schema='$DB' ORDER BY table_name;" | column -t

echo
echo "Done. Pre-migration backup: $BACKUP_DIR/pre-migration-$STAMP.sql.gz"
echo "NOTE: pageinfo was copied in full - its orphan shape (issue+code string"
echo "pair, not a numeric FK) doesn't fit the pattern above, so it was NOT"
echo "auto-cleaned. Report-only; review manually if needed."
