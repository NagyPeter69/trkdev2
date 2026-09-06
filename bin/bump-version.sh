#!/usr/bin/env bash
# Bumps VERSION based on the commit message of the commit that was just
# made (HEAD). Called from bin/git-hooks/post-commit, before
# update-build-info.sh regenerates engine/build_info.php from the new
# VERSION. See VERSIONING.md for the bump rules this encodes.
#
# The bump lands in VERSION as an uncommitted working-tree change - same
# lag engine/build_info.php already has (see VERSIONING.md). It rides
# along in your next commit; there's no requirement to commit it on its
# own.
set -euo pipefail
cd "$(git rev-parse --show-toplevel)"

if [[ ! -f VERSION ]]; then
	exit 0
fi

# Merge commits aren't feature work - leave VERSION alone.
HEADER=$(git log -1 --format=%s)
if [[ "$HEADER" == Merge\ * ]]; then
	exit 0
fi

BODY=$(git log -1 --format=%B)

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
echo "VERSION bumped ($BUMP): -> ${NEW_VERSION}"
