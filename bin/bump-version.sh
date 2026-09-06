#!/usr/bin/env bash
# Bumps VERSION based on the commit message about to be committed, passed
# as $1 - the path git's commit-msg hook gives us to the pending message
# file (see bin/git-hooks/commit-msg, which calls this). Also stages the
# bump (git add VERSION) so it lands IN the commit being created, instead
# of trailing it afterward as a separate uncommitted change - a commit-msg
# hook runs before the commit object exists, so anything staged here is
# picked up by that commit. See VERSIONING.md for the bump rules this
# encodes.
#
# Kept strict (set -e) deliberately: bin/git-hooks/commit-msg is the one
# responsible for making sure a failure here can never block the actual
# commit - this script should fail fast and loud rather than risk writing
# a half-computed VERSION.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

MSG_FILE="${1:?usage: bump-version.sh <commit-message-file>}"

if [[ ! -f VERSION ]]; then
	exit 0
fi

# Merge commits aren't feature work - leave VERSION alone.
HEADER=$(head -n1 "$MSG_FILE")
if [[ "$HEADER" == Merge\ * ]]; then
	exit 0
fi

BODY=$(cat "$MSG_FILE")

MAJOR_RE='^[a-zA-Z]+(\([^)]*\))?!:'
MINOR_RE='^(feat|feature)(\([^)]*\))?:'

BUMP="patch"
if echo "$BODY" | grep -qE '^BREAKING CHANGE:' || [[ "$HEADER" =~ $MAJOR_RE ]]; then
	BUMP="major"
elif [[ "$HEADER" =~ $MINOR_RE ]]; then
	BUMP="minor"
fi

IFS='.' read -r MAJOR MINOR PATCH < VERSION
case "$BUMP" in
	major) MAJOR=$((MAJOR + 1)); MINOR=0; PATCH=0 ;;
	minor) MINOR=$((MINOR + 1)); PATCH=0 ;;
	patch) PATCH=$((PATCH + 1)) ;;
esac

NEW_VERSION="${MAJOR}.${MINOR}.${PATCH}"
echo "$NEW_VERSION" > VERSION
git add VERSION
echo "VERSION bumped ($BUMP): -> ${NEW_VERSION}"
