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

`bin/git-hooks/post-commit` runs `bin/bump-version.sh` after every commit, which bumps
`VERSION` based on that commit's message:

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

`engine/build_info.php` (`APP_VERSION`/`APP_BUILD`/`APP_BUILD_DATE`, shown at the bottom
of the hamburger menu, admin-only) regenerates from `VERSION` + the current commit right
after the bump, same as it always has.

## The one thing to know: it lands as an uncommitted change

Like `engine/build_info.php` already did before this, the bump modifies `VERSION` in the
working tree but doesn't fold itself into the commit that triggered it (a `post-commit`
hook runs after the commit already exists — that's simpler and safer than rewriting the
commit you just made). It just rides along, uncommitted, until your next commit picks it
up — no action needed unless you're about to switch branches or stash, in which case
commit or stash it first like any other pending change.

## Setup

Hooks live in `bin/git-hooks/` and apply via `core.hooksPath`, which is per-clone (not
tracked by git). Run once per clone:

```bash
bin/install-git-hooks.sh
```

Already active on this box.
