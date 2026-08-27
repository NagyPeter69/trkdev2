#!/usr/bin/env bash
# Installs and activates the trkdev-detect-render-mode systemd unit, which
# decides once per boot (from the actual CPU fingerprint) whether R3
# rendering should run locally or be forwarded to the render-VM, and
# persists that decision to /etc/trkdev-render-mode for every PHP request
# (web or CLI) to read for the rest of the uptime. See
# bin/detect-render-mode.php and engine/r3client_config.php for the full
# reasoning. Run once per machine (idempotent - safe to re-run):
#   sudo bin/install-render-mode-detector.sh
set -euo pipefail
cd "$(dirname "$0")/.."

if [ "$(id -u)" -ne 0 ]; then
	echo "This installs a systemd unit and writes /etc/trkdev-render-mode - run as root." >&2
	exit 1
fi

chmod +x bin/detect-render-mode.php
ln -sf "$(pwd)/bin/trkdev-detect-render-mode.service" /etc/systemd/system/trkdev-detect-render-mode.service

systemctl daemon-reload
systemctl enable trkdev-detect-render-mode.service
# Run it now too, so the effect is live immediately rather than only after
# the next reboot - matches this box's actual CPU right away.
systemctl start trkdev-detect-render-mode.service

echo
echo "trkdev-detect-render-mode installed and run. Current state:"
cat /etc/trkdev-render-mode 2>/dev/null || echo "  (state file not found - check: systemctl status trkdev-detect-render-mode)"
