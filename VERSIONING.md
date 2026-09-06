# Versioning

`VERSION` holds the app's version as `MAJOR.MINOR.PATCH` ([SemVer](https://semver.org/)),
adapted for an internal single-deploy app rather than a published library — "breaking"
here means "changes what a deploy of this app needs to handle," not "breaks a public API."

## What moves which digit

- **PATCH (last digit)** — bug fixes only. No schema change, no new behavior, no special
  deploy steps. Safe to ship alone.
- **MINOR (middle digit)** — new features/screens/fields added in a backward-compatible
  way; existing data and workflows keep working untouched. Resets PATCH to 0.
- **MAJOR (first digit)** — changes that need a coordinated/careful deploy: a DB schema
  delta that must run before the code goes live, a workflow change that changes how
  existing jobs behave, or a deliberate release milestone. Resets MINOR and PATCH to 0.

## It's automatic — you don't edit VERSION by hand

`bin/git-hooks/commit-msg` runs `bin/bump-version.sh` against the commit message you're
about to create, which bumps `VERSION` based on it:

| Commit message looks like...                          | Bump  |
|---------------------------------------------------------|-------|
| anything else (`fix: ...`, `chore: ...`, no prefix, ...) | patch |
| starts with `feat:` or `feature:`                        | minor |
| starts with `feat!:` (`!` right after the type/scope)     | major |
| body contains a `BREAKING CHANGE: ...` line               | major |
| starts with `Merge ...` (merge commits)                   | none  |

So the default — writing commits the way you already do — bumps PATCH automatically and
you never have to think about it. Only reach for the `feat:`/`!`/`BREAKING CHANGE`
conventions when you want a bigger bump; skip them entirely and it still works.

If you ever want to set the version yourself for one commit, just edit and `git add
VERSION` before committing — the hook only bumps when *it* changes the file, so a manual
edit made in the same commit is left alone the next time the hook runs (it bumps from
whatever's currently on disk, so your manual value becomes the new baseline).

A `commit-msg` hook runs *before* the commit object exists, which is what lets the bump
land inside the very commit that triggered it (see below) - unlike `engine/build_info.php`,
which still regenerates in `post-commit` afterward, same as it always has.

## The one thing to know: engine/build_info.php still lands as an uncommitted change

`VERSION` itself no longer trails - `bin/git-hooks/commit-msg` runs before the commit is
created and stages its bump (`git add VERSION`), so the new number is committed atomically
with your actual change, same commit, nothing left dangling.

`engine/build_info.php` (`APP_VERSION`/`APP_BUILD`/`APP_BUILD_DATE`, shown at the bottom of
the hamburger menu, admin-only) is the one exception, and structurally can't be fixed the
same way: it embeds the actual commit hash, which doesn't exist yet at `commit-msg` time -
only `post-commit` (after the commit object exists) can read the real one. So it still
regenerates afterward and still rides along uncommitted until your next commit picks it up
- no action needed unless you're about to switch branches or stash, in which case commit or
stash it first like any other pending change. This is a much smaller residue than before
(one purely cosmetic build-metadata file, not the version number itself), and it's the same
lag this file already had even before VERSION bumping existed.

**Fail-safe by design**: `bin/git-hooks/commit-msg` never blocks a commit, even if
`bump-version.sh` itself has a bug - a `commit-msg` hook can abort the commit outright on a
non-zero exit, which would be far worse than a stale version number, so the wrapper always
exits 0 and just logs a warning on failure, falling back to `VERSION` being left as-is
(exactly like it behaved before this hook existed).

## Setup

Hooks live in `bin/git-hooks/` and apply via `core.hooksPath`, which is per-clone (not
tracked by git). Run once per clone:

```bash
bin/install-git-hooks.sh
```

Already active on this box.
