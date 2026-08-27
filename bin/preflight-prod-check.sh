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
section "R3 renderer"

R3_BIN="$WEBROOT/r3API/r3/r3"
R3RENDER_BIN="$WEBROOT/r3API/r3/r3render"
for bin in "$R3_BIN" "$R3RENDER_BIN"; do
	if [ ! -f "$bin" ]; then
		fail "$bin missing"
		continue
	fi
	if [ ! -x "$bin" ]; then
		fail "$bin exists but is not executable"
	else
		pass "$bin is executable"
	fi
	OWNER=$(stat -c '%U:%G' "$bin" 2>/dev/null)
	if [ "$OWNER" != "www-data:www-data" ]; then
		warn "$bin owned by $OWNER, expected www-data:www-data"
	fi
	if command -v getcap >/dev/null 2>&1; then
		CAPS=$(getcap "$bin" 2>/dev/null)
		if echo "$CAPS" | grep -q 'cap_sys_rawio'; then
			pass "$bin has cap_sys_rawio"
		else
			fail "$bin is missing cap_sys_rawio - re-run: setcap cap_sys_rawio+ep $bin (needed on BOTH r3 and r3render, capabilities don't propagate across r3's internal exec of r3render)"
		fi
	else
		warn "getcap not installed - cannot verify capabilities on $bin"
	fi
done

if id www-data 2>/dev/null | grep -q '\bkmem\b'; then
	pass "www-data is a member of the kmem group"
else
	fail "www-data is NOT in the kmem group - /dev/mem is crw-r-----root:kmem by stock udev rule, R3 will fail with EACCES before even reaching the capability check. Fix: usermod -aG kmem www-data, then restart php-fpm (running workers keep their original groups until restart)"
fi

if systemctl is-enabled trkdev-detect-render-mode.service >/dev/null 2>&1; then
	pass "trkdev-detect-render-mode.service is enabled (will re-detect render mode on every boot)"
else
	fail "trkdev-detect-render-mode.service is not enabled - run: sudo bin/install-render-mode-detector.sh (without it, /etc/trkdev-render-mode goes stale after any CPU-model change, e.g. a hypervisor migration)"
fi

if [ -f /etc/trkdev-render-mode ]; then
	RENDER_MODE=$(tr -d '[:space:]' < /etc/trkdev-render-mode)
	CPU_SAYS_KVM64=$(php -r 'require "'"$WEBROOT"'/engine/cpu_detect.php"; echo r3_running_on_kvm64() ? "local" : "remote";' 2>/dev/null || true)
	if [ "$RENDER_MODE" = "$CPU_SAYS_KVM64" ]; then
		pass "/etc/trkdev-render-mode ('$RENDER_MODE') matches this boot's actual CPU fingerprint"
	else
		fail "/etc/trkdev-render-mode says '$RENDER_MODE' but the CPU fingerprint says '$CPU_SAYS_KVM64' right now - stale (rebooted without the service running? re-run bin/detect-render-mode.php or reboot)"
	fi
else
	fail "/etc/trkdev-render-mode does not exist - run: sudo bin/install-render-mode-detector.sh"
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
