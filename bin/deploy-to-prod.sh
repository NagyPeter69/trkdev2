#!/usr/bin/env bash
#
# Deploys a specific, committed state of this codebase (CODE ONLY - never
# the database, never disposable media/job data) to a target host's
# webroot over SSH+rsync.
#
# Usage:
#   bin/deploy-to-prod.sh --host <ssh-host> --ref <git-ref> [options]
#   bin/deploy-to-prod.sh --host <ssh-host> --rollback
#
# Run this from anywhere inside the repo; it figures out the repo root
# itself. Must be run from the machine that holds the canonical git repo
# (trkdev2, as of this writing) - it pushes TO the target, it doesn't run
# on the target.
set -euo pipefail

usage() {
	cat <<'USAGE'
Usage:
  bin/deploy-to-prod.sh --host <ssh-host> --ref <git-ref> [options]
  bin/deploy-to-prod.sh --host <ssh-host> --rollback

Required:
  --host <ssh-host>   SSH host/alias for the deploy target (must already
                       have nginx/PHP/MariaDB set up and reachable - this
                       script does not provision a target from scratch).
  --ref <git-ref>      Branch, tag, or commit to deploy. Required unless
                       --rollback. Refuses to run against a dirty working
                       tree or a ref that doesn't exist.

Options:
  --webroot <path>     Target webroot. Default: /var/www/html
  --dry-run            Show what would happen; the rsync itself also runs
                       with --dry-run, so nothing is written on the target.
  --rollback            Restore the most recent pre-deploy snapshot taken
                       on --host by a previous run of this script.
  -h, --help            Show this help.

This script deliberately does NOT:
  - touch the target's database, schema or data. That is a separate,
    manually-reviewed step - see SYSTEM_STATE.md, "Deploying to
    production". Shipping a code change that also needs a schema change
    means: run the schema change yourself, deliberately, first, then
    deploy the code that depends on it.
  - touch any disposable media/job directories on the target (the same
    ones excluded from this dev environment - see .gitignore). Only files
    tracked in git are ever transferred.
  - provision a target that doesn't already have the stack installed.

Example:
  bin/deploy-to-prod.sh --host prod-web-1 --ref v1.1.0
  bin/deploy-to-prod.sh --host prod-web-1 --ref v1.1.0 --dry-run
  bin/deploy-to-prod.sh --host prod-web-1 --rollback
USAGE
}

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

HOST=""
REF=""
WEBROOT="/var/www/html"
DRY_RUN=0
ROLLBACK=0

while [ $# -gt 0 ]; do
	case "$1" in
		--host) HOST="$2"; shift 2 ;;
		--ref) REF="$2"; shift 2 ;;
		--webroot) WEBROOT="$2"; shift 2 ;;
		--dry-run) DRY_RUN=1; shift ;;
		--rollback) ROLLBACK=1; shift ;;
		-h|--help) usage; exit 0 ;;
		*) echo "Unknown argument: $1" >&2; usage; exit 1 ;;
	esac
done

if [ -z "$HOST" ]; then
	echo "ERROR: --host is required." >&2
	usage
	exit 1
fi

RSYNC_DRY=()
if [ "$DRY_RUN" -eq 1 ]; then
	RSYNC_DRY=(--dry-run)
	echo "== DRY RUN: no changes will be made on $HOST =="
fi

SNAPSHOT_DIR_ON_TARGET="/root/deploy-snapshots"

# ---------------------------------------------------------------------------
if [ "$ROLLBACK" -eq 1 ]; then
	echo "== Rolling back $HOST:$WEBROOT to the most recent pre-deploy snapshot =="
	LATEST=$(ssh "$HOST" "ls -1t ${SNAPSHOT_DIR_ON_TARGET}/*.tar.gz 2>/dev/null | head -1")
	if [ -z "$LATEST" ]; then
		echo "ERROR: no snapshot found on $HOST under ${SNAPSHOT_DIR_ON_TARGET}. Nothing to roll back to." >&2
		exit 1
	fi
	echo "Restoring from: $LATEST"
	if [ "$DRY_RUN" -eq 1 ]; then
		echo "(dry run - would extract $LATEST over $WEBROOT)"
		exit 0
	fi
	ssh "$HOST" "rm -rf '${WEBROOT:?}'/* && tar -xzf '$LATEST' -C '$WEBROOT' && chown -R www-data:www-data '$WEBROOT'"
	echo "Rollback complete."
	exit 0
fi

if [ -z "$REF" ]; then
	echo "ERROR: --ref is required (or use --rollback)." >&2
	usage
	exit 1
fi

# ---------------------------------------------------------------------------
echo "== Pre-flight checks =="

if [ -n "$(git status --porcelain)" ]; then
	echo "ERROR: working tree is dirty. Commit or stash before deploying." >&2
	git status --short
	exit 1
fi

if ! git rev-parse --verify --quiet "${REF}^{commit}" > /dev/null; then
	echo "ERROR: ref '$REF' does not resolve to a commit in this repo." >&2
	exit 1
fi

RESOLVED_COMMIT=$(git rev-parse "$REF")
echo "Deploying ref '$REF' -> commit $RESOLVED_COMMIT"

if ! ssh -o BatchMode=yes -o ConnectTimeout=5 "$HOST" "true" 2>/dev/null; then
	echo "ERROR: cannot reach $HOST over SSH." >&2
	exit 1
fi

# ---------------------------------------------------------------------------
echo "== Building a clean export of $REF into a scratch checkout =="
SCRATCH=$(mktemp -d)
trap 'rm -rf "$SCRATCH"' EXIT

git archive "$REF" | tar -x -C "$SCRATCH"

# Build info must reflect what's actually being deployed, not whatever was
# last generated in the working tree. Resolve version/hash/date from THIS
# repo (where git actually works) before touching the extracted scratch
# dir, which is a plain file tree with no .git of its own.
BUILD_VERSION=$(git show "$REF:VERSION" 2>/dev/null || echo "0.0.0")
BUILD_HASH=$(git rev-parse --short "$REF")
BUILD_DATE=$(git log -1 --format=%ci "$REF")
cat > "$SCRATCH/engine/build_info.php" << EOF
<?php
// Auto-generated by bin/deploy-to-prod.sh - do not edit by hand.
define( 'APP_VERSION', '${BUILD_VERSION}' );
define( 'APP_BUILD', '${BUILD_HASH}' );
define( 'APP_BUILD_DATE', '${BUILD_DATE}' );
?>
EOF

echo "== Linting every .php file in the export before shipping anything =="
LINT_FAILS=0
while IFS= read -r -d '' f; do
	if ! php -l "$f" > /dev/null 2>&1; then
		echo "PARSE ERROR: $f"
		php -l "$f" || true
		LINT_FAILS=$((LINT_FAILS + 1))
	fi
done < <(find "$SCRATCH" -iname '*.php' -print0)

if [ "$LINT_FAILS" -gt 0 ]; then
	echo "ERROR: $LINT_FAILS file(s) failed to lint locally. Refusing to deploy a build with parse errors." >&2
	exit 1
fi
echo "All PHP files lint clean."

# ---------------------------------------------------------------------------
echo "== Snapshotting current state of $HOST:$WEBROOT for rollback =="
if [ "$DRY_RUN" -eq 0 ]; then
	SNAPSHOT_NAME="pre-deploy-$(date +%Y%m%d_%H%M%S)-$(ssh "$HOST" "cd '$WEBROOT' 2>/dev/null && git rev-parse --short HEAD 2>/dev/null || echo unknown").tar.gz"
	ssh "$HOST" "mkdir -p '$SNAPSHOT_DIR_ON_TARGET' && tar -czf '${SNAPSHOT_DIR_ON_TARGET}/${SNAPSHOT_NAME}' -C '$WEBROOT' . 2>/dev/null || true"
	echo "Snapshot: ${SNAPSHOT_DIR_ON_TARGET}/${SNAPSHOT_NAME}"
else
	echo "(dry run - would snapshot $WEBROOT here)"
fi

# ---------------------------------------------------------------------------
echo "== Syncing code to $HOST:$WEBROOT =="
# --delete only within tracked paths would be ideal, but since $SCRATCH is
# an exact git archive export (no disposable data dirs exist in it at all -
# they're gitignored, never committed), a plain --delete is safe: it can
# only ever remove files that are not tracked in git, which on a correctly
# set up target should just be stale code from a previous deploy. It will
# NOT touch directories that were never part of this repo to begin with
# (e.g. actual media/job uploads living outside any path this repo tracks),
# since rsync --delete only prunes within the source tree's own directory
# structure, not sibling paths absent from the source entirely.
rsync -az "${RSYNC_DRY[@]}" --delete \
	--exclude='.git' \
	"$SCRATCH"/ "$HOST:$WEBROOT"/

if [ "$DRY_RUN" -eq 1 ]; then
	echo "== DRY RUN complete. No changes were made. =="
	exit 0
fi

ssh "$HOST" "chown -R www-data:www-data '$WEBROOT'"

echo "== Verifying on target =="
ssh "$HOST" "find '$WEBROOT' -iname '*.php' -print0 | xargs -0 -n1 -P4 php -l 2>&1 | grep -v 'No syntax errors detected'" && {
	echo "ERROR: parse errors detected on target after deploy. Consider --rollback immediately." >&2
	exit 1
} || true

echo ""
echo "== Deploy complete: $REF ($RESOLVED_COMMIT) is now live on $HOST:$WEBROOT =="
echo "Rollback if needed: bin/deploy-to-prod.sh --host $HOST --rollback"
