#!/usr/bin/env bash
# Points this clone's git hooks at the tracked bin/git-hooks/ directory, so
# engine/build_info.php (APP_VERSION/APP_BUILD/APP_BUILD_DATE - the version
# shown at the bottom of the hamburger menu) regenerates itself after every
# commit, merge/pull, and checkout. Run once per clone:
#   bin/install-git-hooks.sh
set -euo pipefail
cd "$(dirname "$0")/.."

chmod +x bin/git-hooks/*
git config core.hooksPath bin/git-hooks

echo "Git hooks installed (core.hooksPath -> bin/git-hooks)."
