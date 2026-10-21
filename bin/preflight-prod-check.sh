#!/usr/bin/env bash
#
# Audits THIS host against the checklist of dev-safety gates and
# production-readiness facts documented in SYSTEM_STATE.md, for use when
# validating a cloned/rebuilt trkdev2 as a production cutover candidate.
#
# Run this ON the candidate host itself (not from trkdev2 pushing to it -
# unlike bin/deploy-to-prod.sh, every check here is a local fact about
# whatever machine this runs on).
#
# Every check prints PASS/FAIL/WARN and a one-line reason. Exit code is
# the number of FAILs (0 = clean). WARNs don't affect the exit code -
# they're things worth a human glance but not hard blockers.
#
# Usage:
#   bin/preflight-prod-check.sh
#
# Deliberately does NOT modify anything - read-only checks only.
set -uo pipefail

WEBROOT="/var/www/html"
FAILS=0
WARNS=0

pass() { printf "  \033[32mPASS\033[0m  %s\n" "$1"; }
fail() { printf "  \033[31mFAIL\033[0m  %s\n" "$1"; FAILS=$((FAILS+1)); }
warn() { printf "  \033[33mWARN\033[0m  %s\n" "$1"; WARNS=$((WARNS+1)); }
section() { printf "\n== %s ==\n" "$1"; }

# ---------------------------------------------------------------------------
section "Dev-safety gates (must be OFF for production)"

HOSTNAME_NOW="$(hostname)"
if echo "$HOSTNAME_NOW" | grep -qi 'dev'; then
	fail "hostname '$HOSTNAME_NOW' contains \"dev\" - pmdDevSafeName() in engine/xml_handler.php will permanently suffix _DEV onto every PMD XML sent to Switch. There is no config flag to override this, only the hostname itself."
else
	pass "hostname '$HOSTNAME_NOW' does not contain \"dev\""
fi

TRKDEV_ENV_POOL=$(grep -oP 'env\[TRKDEV_ENVIRONMENT\]\s*=\s*\K.*' /etc/php/8.4/fpm/pool.d/www.conf 2>/dev/null || true)
if [ "$TRKDEV_ENV_POOL" = "dev" ]; then
	fail "TRKDEV_ENVIRONMENT=dev in php-fpm pool config - switchClientAllowed()/switchBulkSyncAllowed() will keep restricting ALL Switch job routing to Colorcom/TestCo only"
elif [ -z "$TRKDEV_ENV_POOL" ]; then
	pass "TRKDEV_ENVIRONMENT is unset in php-fpm pool config"
else
	warn "TRKDEV_ENVIRONMENT='$TRKDEV_ENV_POOL' in php-fpm pool config (not \"dev\", so IS_DEV_ENVIRONMENT is false - fine, but an unexpected value, worth a glance)"
fi

if [ -f /etc/trkdev-db.env ]; then
	TRKDEV_ENV_CRON=$(grep -oP '^TRKDEV_ENVIRONMENT=\K.*' /etc/trkdev-db.env 2>/dev/null || true)
	if [ "$TRKDEV_ENV_CRON" = "dev" ]; then
		fail "TRKDEV_ENVIRONMENT=dev in /etc/trkdev-db.env - cron-run scripts will see the same dev restriction as the web tier"
	else
		pass "/etc/trkdev-db.env does not set TRKDEV_ENVIRONMENT=dev"
	fi
else
	warn "/etc/trkdev-db.env not found - expected to exist for cron DB credentials"
fi

DISPLAY_ERRORS=$(php -r 'echo ini_get("display_errors");' 2>/dev/null)
if [ "$DISPLAY_ERRORS" = "1" ] || [ "$DISPLAY_ERRORS" = "On" ]; then
	fail "display_errors is On - must be Off before real traffic (leaks paths/queries/stack traces to users)"
else
	pass "display_errors is Off"
fi

# ---------------------------------------------------------------------------
section "DynaPDF"

for f in "$WEBROOT/engine/config.inc.php" "$WEBROOT/client/engine/config.inc.php" "$WEBROOT/config.inc.php"; do
	if [ ! -f "$f" ]; then
		fail "$f missing entirely"
	elif ! grep -q 'SetLicenseKey' "$f"; then
		fail "$f has no SetLicenseKey() call"
	else
		pass "$f has a SetLicenseKey() call present"
	fi
done
KEYS_MATCH=$(for f in "$WEBROOT/engine/config.inc.php" "$WEBROOT/client/engine/config.inc.php" "$WEBROOT/config.inc.php"; do
	grep -oP "SetLicenseKey\('\K[^']*" "$f" 2>/dev/null
done | sort -u | wc -l)
if [ "$KEYS_MATCH" = "1" ]; then
	pass "all three config.inc.php copies carry the same license key"
else
	fail "config.inc.php copies carry DIFFERENT license keys ($KEYS_MATCH distinct values) - a previous key renewal likely missed one of the three files"
fi
warn "this script does not render a test PDF - re-run the manual watermark check (see SYSTEM_STATE.md's DynaPDF section) once, on this host, before go-live"

# ---------------------------------------------------------------------------
section "R3 render node"

# R3 runs only on the render node; this host just calls it (engine/r3client_config.php)
# and falls back to Ghostscript/DynaPDF when it is down.
R3_URL=$(grep -oP "R3_REMOTE_URL',\s*'\K[^']*" "$WEBROOT/engine/r3client_config.php" 2>/dev/null)
if [ -z "$R3_URL" ]; then
	fail "could not extract R3_REMOTE_URL from engine/r3client_config.php"
else
	R3_HEALTH="${R3_URL%%/r3remote/*}/health"
	if [ "$(curl -s -o /dev/null -w '%{http_code}' --connect-timeout 2 --max-time 4 "$R3_HEALTH")" = "200" ]; then
		pass "render node answers at $R3_HEALTH"
	else
		fail "render node not answering at $R3_HEALTH - renders will fall back to Ghostscript/DynaPDF (red Pages View spinner); MEASURE points/COMPARE won't work"
	fi
	R3_HOST=$(echo "$R3_URL" | sed -E 's#^[a-z]+://([^/:]+).*#\1#')
	if exportfs -v 2>/dev/null | grep -q "$R3_HOST"; then
		pass "client/ is NFS-exported to $R3_HOST (render node path mode)"
	else
		warn "client/ is not NFS-exported to $R3_HOST - R3 still works, but every call uploads the PDF (slower)"
	fi
fi

for icc in FOGRA_39.icc sRGB_Color_Space_Profile.icc; do
	if [ -f "$WEBROOT/r3API/r3/$icc" ]; then
		pass "ICC store has $icc (r3API/r3 is the ICC store for Ghostscript/DynaPDF)"
	else
		fail "ICC store r3API/r3/$icc missing - Ghostscript/DynaPDF colour conversion will fail"
	fi
done

if id www-data 2>/dev/null | grep -q '\bkmem\b'; then
	warn "www-data is in the kmem group (/dev/mem read access) - only local R3 needed that; remove: gpasswd -d www-data kmem, then restart php-fpm"
else
	pass "www-data is not in the kmem group"
fi

# ---------------------------------------------------------------------------
section "Switch connectivity"

SWITCH_HOST_PORT=$(grep -oP "SWITCHURL.*http://\K[^/]*" "$WEBROOT/engine/switchconstant.php" 2>/dev/null)
if [ -z "$SWITCH_HOST_PORT" ]; then
	warn "could not extract Switch host:port from engine/switchconstant.php"
else
	if timeout 5 bash -c "cat < /dev/null > /dev/tcp/${SWITCH_HOST_PORT/:/\/}" 2>/dev/null; then
		pass "can reach Switch at $SWITCH_HOST_PORT (TCP connect succeeded)"
	else
		fail "cannot reach Switch at $SWITCH_HOST_PORT - trkdev2 has this deliberately firewalled off; production needs the OPPOSITE rule. Coordinate with whoever owns the Sophos firewall"
	fi
fi

# ---------------------------------------------------------------------------
section "Cron jobs"

EXPECTED_CRON_SCRIPTS=(
	"handout-handler.php" "clear_user.php" "package_reader.php"
	"switch_sync_worker.php" "__cleaner__.php" "blob_cleaner.php"
	"adupload_cleaner.php" "asset_cleaner.php"
)
CURRENT_CRONTAB=$(crontab -l 2>/dev/null || true)
for script in "${EXPECTED_CRON_SCRIPTS[@]}"; do
	if echo "$CURRENT_CRONTAB" | grep -q "$script"; then
		pass "cron job present: $script"
	else
		fail "cron job MISSING: $script"
	fi
done

# ---------------------------------------------------------------------------
section "Database"

if [ -f /etc/trkdev-db.env ]; then
	DB_TEST=$(bash -c 'set -a; source /etc/trkdev-db.env; set +a; mysql -u trkapp -p"$TRKDEV_DB_PASSWORD" -e "SELECT 1;" 2>&1')
	if echo "$DB_TEST" | grep -q '^1$'; then
		pass "trkapp DB user can connect using /etc/trkdev-db.env credentials"
	else
		fail "trkapp DB connection failed: $DB_TEST"
	fi
else
	fail "/etc/trkdev-db.env not found - cannot test DB credentials"
fi

# ---------------------------------------------------------------------------
printf "\n== Summary ==\n"
printf "%d failure(s), %d warning(s)\n" "$FAILS" "$WARNS"
if [ "$FAILS" -gt 0 ]; then
	printf "\nNOT production-ready - fix the FAILs above before cutover.\n"
else
	printf "\nAll hard checks pass. Review any WARNs, then proceed.\n"
fi
exit "$FAILS"
