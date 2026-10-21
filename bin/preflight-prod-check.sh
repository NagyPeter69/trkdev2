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
section "Environment (ENVIRONMENT must be production)"

# One variable decides dev vs production behaviour: ENVIRONMENT in
# /etc/trkdev-db.env, "production" or "developer" (engine/constans.php).
# Anything but "production" counts as developer - TestCo-only Switch
# routing (switchClientAllowed()/switchBulkSyncAllowed()) and a _DEV
# suffix on every PMD XML (pmdDevSafeName()). Cron sources the file;
# php-fpm gets it through its systemd EnvironmentFile plus an
# env[ENVIRONMENT] = $ENVIRONMENT pass-through in every pool.
ENV_FILE=/etc/trkdev-db.env
if [ ! -f "$ENV_FILE" ]; then
	fail "$ENV_FILE not found - cron and php-fpm both read ENVIRONMENT (and the DB credentials) from it"
else
	# Let bash parse it, the way cron does - values may be quoted.
	ENV_VALUE=$(bash -c 'set -a; . "$1" >/dev/null 2>&1; printf "%s" "${ENVIRONMENT-<unset>}"' _ "$ENV_FILE")
	case "$ENV_VALUE" in
		production) pass "$ENV_FILE sets ENVIRONMENT=production" ;;
		developer)  fail "$ENV_FILE sets ENVIRONMENT=developer - Switch routing is TestCo-only and PMD XML gets the _DEV suffix" ;;
		*)          fail "$ENV_FILE sets ENVIRONMENT='$ENV_VALUE' - only production/developer are valid, anything else is treated as developer" ;;
	esac
fi

if grep -rqs 'TRKDEV_ENVIRONMENT' "$ENV_FILE" /etc/php/8.4/fpm/pool.d/*.conf /etc/systemd/system/php8.4-fpm.service.d/*.conf; then
	fail "the old TRKDEV_ENVIRONMENT is still set somewhere ($(grep -rls 'TRKDEV_ENVIRONMENT' "$ENV_FILE" /etc/php/8.4/fpm/pool.d/*.conf /etc/systemd/system/php8.4-fpm.service.d/*.conf | tr '\n' ' ')) - nothing reads it any more; remove it"
else
	pass "no leftover TRKDEV_ENVIRONMENT"
fi

if grep -qsP "^\s*EnvironmentFile=-?$ENV_FILE\s*$" /etc/systemd/system/php8.4-fpm.service.d/*.conf; then
	pass "php-fpm loads $ENV_FILE (systemd EnvironmentFile)"
else
	fail "php-fpm's systemd unit doesn't load $ENV_FILE - its pools would see no ENVIRONMENT and act as developer"
fi

for POOL in /etc/php/8.4/fpm/pool.d/*.conf; do
	POOL_LINE=$(grep -oP '^\s*env\[ENVIRONMENT\]\s*=\s*\K\S+' "$POOL" | tail -1)
	if [ "$POOL_LINE" = '$ENVIRONMENT' ]; then
		pass "$(basename "$POOL") passes ENVIRONMENT through from $ENV_FILE"
	elif [ -z "$POOL_LINE" ]; then
		fail "$(basename "$POOL") has no env[ENVIRONMENT] = \$ENVIRONMENT line - PHP in that pool sees no ENVIRONMENT and acts as developer"
	else
		fail "$(basename "$POOL") hardcodes env[ENVIRONMENT] = $POOL_LINE - use \$ENVIRONMENT so $ENV_FILE stays the only place it's set"
	fi
done

# What php-fpm actually sees right now (it only re-reads the file on a
# restart, not a reload). Needs cgi-fcgi (libfcgi-bin); the probe file is
# removed straight after.
if command -v cgi-fcgi >/dev/null; then
	PROBE="$WEBROOT/client/r3shared/preflight_env_$$.php"
	printf '<?php require "%s/engine/constans.php"; echo "ENV=", ENVIRONMENT;' "$WEBROOT" > "$PROBE"
	chmod 644 "$PROBE"
	LIVE_ENV=$(SCRIPT_FILENAME="$PROBE" SCRIPT_NAME=/preflight.php REQUEST_METHOD=GET cgi-fcgi -bind -connect /run/php/php8.4-fpm.sock 2>/dev/null | grep -oP 'ENV=\K\w+')
	rm -f "$PROBE"
	if [ "$LIVE_ENV" = "production" ]; then
		pass "running php-fpm sees ENVIRONMENT=production"
	else
		fail "running php-fpm sees ENVIRONMENT='${LIVE_ENV:-<no answer>}' - restart (not reload) php8.4-fpm after changing $ENV_FILE"
	fi
else
	warn "cgi-fcgi not installed - can't check what the running php-fpm sees (apt install libfcgi-bin)"
fi

# What the web tier sees: php-fpm's own php.ini + conf.d (99-trkdev.ini
# sets it there), then any uncommented pool override, which wins. The CLI
# php.ini doesn't matter here - cron output never reaches a browser.
DISPLAY_ERRORS=$(PHP_INI_SCAN_DIR=/etc/php/8.4/fpm/conf.d php -c /etc/php/8.4/fpm/php.ini -r 'echo ini_get("display_errors");' 2>/dev/null)
POOL_DISPLAY_ERRORS=$(grep -oP '^\s*php(_admin)?_(flag|value)\[display_errors\]\s*=\s*\K\S+' /etc/php/8.4/fpm/pool.d/*.conf 2>/dev/null | tail -1)
[ -n "$POOL_DISPLAY_ERRORS" ] && DISPLAY_ERRORS="$POOL_DISPLAY_ERRORS"
case "${DISPLAY_ERRORS,,}" in
	1|on|true|yes|stdout|stderr)
		fail "display_errors is On in php-fpm - must be Off before real traffic (leaks paths/queries/stack traces to users)" ;;
	*)
		pass "display_errors is Off in php-fpm" ;;
esac

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
# Compare the SetLicenseKey() argument as written - normally all three read
# getenv('TRKDEV_DYNAPDF_LICENSE_KEY'), but a hardcoded key would show up too.
KEY_ARGS=$(for f in "$WEBROOT/engine/config.inc.php" "$WEBROOT/client/engine/config.inc.php" "$WEBROOT/config.inc.php"; do
	grep -oP 'SetLicenseKey\(\s*\K.*?(?=\s*\)\s*;)' "$f" 2>/dev/null
done | sort -u)
KEYS_MATCH=$(printf '%s' "$KEY_ARGS" | grep -c .)
if [ "$KEYS_MATCH" = "1" ]; then
	pass "all three config.inc.php copies pass the same license key ($( [[ "$KEY_ARGS" == getenv* ]] && echo "$KEY_ARGS" || echo "hardcoded" ))"
else
	fail "config.inc.php copies pass DIFFERENT license keys ($KEYS_MATCH distinct values) - a previous key renewal likely missed one of the three files"
fi
if [[ "$KEY_ARGS" == *TRKDEV_DYNAPDF_LICENSE_KEY* ]]; then
	if ! grep -qP "^TRKDEV_DYNAPDF_LICENSE_KEY=['\"]?[^'\"\s]+" /etc/trkdev-db.env 2>/dev/null; then
		fail "TRKDEV_DYNAPDF_LICENSE_KEY is not set in /etc/trkdev-db.env - DynaPDF would run unlicensed (watermarked output)"
	elif ! grep -qP '^\s*env\[TRKDEV_DYNAPDF_LICENSE_KEY\]' /etc/php/8.4/fpm/pool.d/www.conf 2>/dev/null; then
		fail "php-fpm pool config doesn't pass TRKDEV_DYNAPDF_LICENSE_KEY through (env[...] line missing) - web-tier DynaPDF would run unlicensed"
	else
		pass "TRKDEV_DYNAPDF_LICENSE_KEY is set in /etc/trkdev-db.env and passed to php-fpm"
	fi
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
