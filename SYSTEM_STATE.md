# System State — Colorcom Tracker (Print Approval System)

Last updated: 2026-07-03, against commit `3b0da4b`. If you're a fresh session picking this
up, read this whole file before touching anything — it exists so you don't have to
rediscover what's already been learned the hard way.

## What this is

A print-approval / workflow system ("Tracker") for Colorcom, a print media company. Clients
("publishers") submit publications/ads; the system manages proofing, approval workflow,
flatplan (page layout) management, and integrates with **Enfocus Switch** (an external print
workflow automation server) for job routing.

The codebase is legacy procedural PHP with no framework, no templating engine, no Composer
(until this migration — see below), and originally no version control. Business logic, SQL,
and HTML generation are interleaved in the same files. This is not a criticism to fix
wholesale right now — see "Known architectural issues" below for what's worth fixing when,
and what isn't.

## Domain model: Publications, page numbering, and Workflow types

Explained directly by the project owner 2026-07-23, after a debugging session that took far
longer than it should have precisely because this wasn't written down anywhere. Read this
before touching Flatplan/Pages-view/Parts/color-standard code — it's the conceptual model
the rest of this section's bug list only makes sense against.

Three independent axes combine to describe any given job:

**A. Publication type — `magazines.type`: `Regular` vs `Adhoc`**
- **Regular** publications are recurring/periodical — they must have **Issues**
  (`publications` rows), and an Issue can override some of the parent publication's
  parameters slightly (its own per-issue XML, `client/xml/{MAGCODE}_{ISSUECODE}.xml` — see
  `partDetect()` below).
- **Adhoc** publications are one-time jobs. They can relate to an already-defined **Client**
  (a known publisher/account), or be genuinely "cold" — a walk-in job under a generic
  `Adhoc` client with no prior relationship.
- **This field is a business classification, not a page-numbering signal** — don't conflate
  it with axis B below. A real bug this session (`client/flatplan_preview.php`) checked
  `magazine.type == "Regular"` to decide whether to clear the selected Part, when it should
  have been checking `PageNumbering == "European"` instead (see "Known bugs" below) —
  "Regular" here has nothing to do with "Regular/European numbering", despite the
  unfortunately similar name.

**B. Page numbering — PMD XML `PageNumbering`: `European` vs `American`**
- **European**: page numbers are **absolute** across the whole publication. Cover1 == page 1,
  Cover2 == page 2, Cover3 == `pages.length - 2`, Cover4 == `pages.length - 1`; Inside pages
  generally sit between the cover pages. Parts are defined with **strict, absolute page-range
  definitions** (e.g. Inside = pages 3-86, Cover = 1-2 + 87-88) — this is exactly what
  `partDetect()`'s `<place>` page-range matching (`engine/engine.php`) was originally built
  for, and it's correct *for this numbering scheme*.
- **American**: Parts have a **defined length** (a page count), but **page numbers inside a
  Part are irrelevant/not globally meaningful** — every Part's `pageinfo.page` column
  restarts at 1 independently (a Cover Part's page 1 and an Inside Part's page 1 are
  unrelated rows that just happen to share a number). Any code that tries to determine "which
  Part is this page in" by page number alone, or that queries `pageinfo`/builds a file path
  by page number without also filtering by `part`, is **wrong by construction** for this
  numbering scheme — not an edge case, the normal case. This was the root cause of most of
  this session's bugs (see below) and is the single most important fact in this section.
- Parts always have their own color-standard definition regardless of which numbering scheme
  is in use (`partDetect()`'s job) — only *how* you determine which Part a given page belongs
  to differs between the two schemes.

**C. Workflow — PMD XML `Workflow`: business service depth levels**
- **Full** — sophisticated image enhancement, Ad handling, in-house PDF generation +
  preflight, and Page Approval (the full proofing loop this app's Flatplan/Pages views are
  built around).
- **Hybrid** — image enhancement, Ad handling, PDF preflight + approval, but approval happens
  **via the client's own submission** rather than in-house. Has no real flatplan stages of
  its own — its single flatplan IS the final one (see `page_pdf-handler.php`'s
  `Workflow == "Hybrid"` coercion, predates this session).
- **Resize** — sophisticated image enhancement only, plus Ad handling. No Flatplan/Pages/
  Planner UI access at all (gated in `client/menu.php`).
- **Auto** — fully-automatic image enhancement and Ad handling, no manual proofing loop.

These three axes combine (2 × 2 × 4 = 16 base cases), and **FlatplanStages** (PMD XML field,
`1`/`2`/`3`) is an orthogonal "flavour" on top of whichever Workflow a job uses — how many
proofing rounds (PRE/BASIC/FIN) it goes through before the final approved state. See
[[flatplan_stages_single_stage_rule]] memory (or ask a fresh session to recall it) for the
full FlatplanStages==1 rule: such a job has only one flatplan, and Switch's own per-submission
stage tag (`NOR`/`FIN` — ads are conventionally always submitted `FIN` regardless of the job's
actual stage count) must never be used to split its pages across two views.

### Known bugs from this axis, for a fresh session to avoid re-discovering

All found/fixed 2026-07-23 debugging a Full-workflow, American-numbering, FlatplanStages=1
Cover/Inside job. Full detail in memory (`flatplan_stages_single_stage_rule`,
`flatplan_stage_markers_and_slots`, `flatplan_part_scoping_gaps` — load these into a fresh
session before touching this area again):

- Several places matched a `pageinfo` row by page number **without** also filtering by
  `part` (`engine/engine.php`'s `checkPagePair()`, `client/flatplan_preview.php`'s initial
  page-strip query) — silently "worked" for whichever Part's row won an unordered `LIMIT 1`
  MySQL query, broke for others. Only matters for American numbering (axis B).
- `client/flatplan_preview.php` unconditionally cleared the selected Part for any
  `magazine.type == "Regular"` job — conflating axis A with axis B, as described above.
- `partDetect()` (`engine/engine.php`) assumes the European-numbering `<place>` page-range
  format in the per-issue XML; some issues instead export `<pages>` (a page *count*, American-
  numbering style), which the function can't match against page numbers at all, so it always
  fell back to the `FOGRA_39` default color standard regardless of the Part's real one. Fixed
  by adding an optional part-name-based match, wired through most call sites (see the
  function's own comment for which ones still don't pass a part - the CLI `render_page_worker.php`
  path and a couple of low-traffic/test scripts don't have easy access to it).
- Several `fin`/stage-descriptor mismatches (FlatplanStages==1 handling) - see the dedicated
  memory file, not repeated here.

## Machines involved

- **trk-dev (10.10.30.61)** — the original developer box this was all migrated *from*.
  Debian 11, PHP 7.4, MariaDB 10.5. **Root access to this box was lost** partway through
  this migration (both SSH and hypervisor console) — cause unknown, never resolved. It may
  or may not still exist; if you need something from it, you likely can't get it via login,
  only via whatever file-level access (e.g. sshfs) got you here in the first place.
- **trkdev2 (10.10.30.62, hostname `trkdev2`, `trkdev2.colorcom.hu`)** — the rebuilt dev
  system this document describes. This is what you're almost certainly working on.
- **Production** — a separate, still-live VM running the *old* stack (presumably still
  PHP 7.4-era, unfixed). Not touched by any of this work. Nothing in this migration has
  shipped to it yet. See "Deploying to production" below.

## trkdev2 stack

| Component | Version | Notes |
|---|---|---|
| OS | Debian 13 (trixie) | Fresh install, all packages from Debian's own repos — no third-party PPAs/repos needed anywhere in this stack |
| nginx | 1.26.3 | Config at `/etc/nginx/sites-available/trkdev` |
| PHP | 8.4.21 (FPM + CLI) | See PHP notes below |
| MariaDB | 11.8.6 | DB name `nyomadake_intra` |
| DynaPDF | v5.0 (was v4.0 on the old PHP7 box) | See DynaPDF section below — **licensed as of 2026-07-13** |

Disk: 47G total, ~2.5G used. RAM: 3.8G. Both comfortable headroom for a dev box; do not
assume production has the same headroom.

### PHP config

- `/etc/php/8.4/fpm/conf.d/99-trkdev.ini` and the CLI equivalent hold custom settings
  (memory_limit 512M, upload limits, `output_buffering = 4096` — **this one matters**, see
  below, disable_functions for pcntl_*, etc.). Diff these against Debian defaults if
  something behaves differently than expected.
- `display_errors` is currently **On** for bring-up/testing visibility. **Must be set to
  Off before this ever serves real traffic** — it's flagged with a comment in the ini file
  itself.
- `short_open_tag = On` is required — 165+ files use bare `<?` tags. Don't turn this off.
- `output_buffering = 4096` is required — `client/index2.php` (and likely others) prints
  `<!DOCTYPE HTML><head>` *before* the PHP login logic runs `header('Location: ...')`.
  Without output buffering, that redirect silently fails. This bit me once already
  (spent a long debugging session on a "the Apply button hangs" report that turned out to
  be this).
- DynaPDF extension loads via `/etc/php/8.4/{fpm,cli}/conf.d/99-dynapdf.ini`, deliberately
  named to sort *last* alphabetically. **This matters**: DynaPDF's compiled `.so` doesn't
  declare `libstdc++` as a dependency despite needing its C++ RTTI symbols, so if it loads
  before whatever else in the stack transitively pulls in libstdc++, it fails with an
  "undefined symbol" error. Loading it last works around this. Don't rename it back to
  something that sorts earlier without retesting.

### Git

`/var/www/html` is a git repo (initialized during this migration — there was no prior
history). `.gitignore` excludes the disposable media/job directories (see below). Check
`git log --oneline` for the real changelog; don't rely on this document's summary below
being kept in sync with every commit.

`origin` is `git@github.com:NagyPeter69/trkdev2.git` — confirmed 2026-08-09. The repo's
`.git/config` previously pointed `origin` at `git@github.com:colorcom/trkdev2.git`, which
doesn't exist (`github.com/colorcom` isn't a real org/user) — stale/wrong from whenever
this VM's git config was first set up, never actually reachable. Push access needs an SSH
key on this machine authorized on `NagyPeter69/trkdev2` (as a deploy key with write access,
or a key added to that account); there was none present as of 2026-08-09 until one was
generated locally and added as a deploy key. If a push ever fails with
`Permission denied (publickey)` or `Repository not found`, check `~/.ssh/` has a key and
that `git remote -v` still points at `NagyPeter69/trkdev2`, not the old `colorcom` one.

### Git hooks — keep the hamburger menu's version display in sync automatically

`engine/build_info.php` (defines `APP_VERSION`/`APP_BUILD`/`APP_BUILD_DATE`, shown at the
bottom of the hamburger menu, admin-only — `client/engine/menuAjax.php`) is generated
output, not hand-edited — `bin/update-build-info.sh` derives it from the `VERSION` file plus
the current git HEAD hash/date. It was found stale 2026-08-09 (`VERSION` had been bumped to
`3.0.0` but the generated file still said `1.0.0` from a month-old commit) because nothing
re-ran the generator after the bump.

Fixed by wiring `bin/update-build-info.sh` into git hooks (`bin/git-hooks/post-merge`,
`post-checkout` call it directly; `post-commit` calls `bin/bump-version.sh` first, then it)
so the display self-updates after every commit, pull/merge, and branch switch, with no
manual step. **This is a `core.hooksPath` local git config, not something that travels with
a clone** — run `bin/install-git-hooks.sh` once on any fresh clone (including the eventual
production box, post-cutover) to activate it there too; already active on trkdev2 as of
2026-08-09. See [VERSIONING.md](VERSIONING.md) for what `bin/bump-version.sh` does and the
MAJOR/MINOR/PATCH policy behind it (added 2026-08-10). Production deploys aren't affected by
any of this either way —
`bin/deploy-to-prod.sh` already regenerates `build_info.php` itself from the target ref as
part of every deploy, independent of these hooks.

## What was migrated, and what was deliberately left behind

The original dev box's `/var/www/html` was ~336GB, of which ~332GB was `client/` — almost
entirely uploaded/generated media (PDFs, ad previews, rendered thumbnails). **Per explicit
instruction, none of that was migrated.** Only actual code (~11MB of PHP/JS/CSS across ~815
files) plus small UI assets (icons, fonts, the flipbook viewer library — ~113MB) came across.
If something references a file under `client/packages/`, `client/assets/`, `client/temp/`,
`client/handout/`, `client/labor/`, `client/uploads/`, `client/advertisements/`, or
`dynAPI/tracker/` and it's missing, **that's expected** — those directories don't exist on
trkdev2 at all.

The database was fully copied (49 tables, real production-shaped data) via a one-shot
self-deleting PHP script (the original dev box's own backup mechanism was broken — an empty
gzip — and root SSH access wasn't available; this was necessity, not preference), then
deliberately pruned down to a clean base for continued dev work:

- **Kept**: `accounts` (3 users: `zenda`/`zendax` = Péter Tamás, the original developer,
  kept as both since it was unclear which was canonical; `peter` = Nagy Péter, this
  project's owner), `publishers` (Colorcom + TestCo only), `magazines` (1: "BP hirdető",
  under Colorcom), `user_groups`, `tracker_settings`, `switch_flows`, and a few reference/
  config tables (`article_colors`, `ad_sizes_old`, `flatplan_articletypes`, `userLogSettings`).
- **Wiped**: all job/publication content (`publications`, `packages`, `pageinfo`, `ads`,
  `parts`, `assets`, `image_map`, `ad_hoc*`, `flatplan_*`, `filetransfer*`, `hotlinks*`,
  `comments*`, `calendar_*`, `deliver_table`, `tasklist`).
- **Untouched**: `action_log`, `user_log`, `system_log`, `error_log` — these are audit
  trails, a different category from job data; they were deliberately *not* wiped even
  though the job data was (ask before touching these — they weren't mine to prune).

Backups from each destructive step are on trkdev2 at `/root/db-backups/*.sql.gz` if you
ever need to recover something from before a specific prune.

## PHP 7→8 compatibility fixes applied

The codebase was written for PHP 7.x. These are the actual bug classes found and fixed —
useful to know because **the same classes of bug almost certainly still exist in code
paths nobody has exercised yet**. If you hit a fatal error, check this list first:

1. **`mysqli` exception mode** (PHP 8.1+ default): the entire `sql_*` DB wrapper layer in
   `engine/engine.php` was written assuming `mysqli_query()` returns `false` on error. Fixed
   with `mysqli_report(MYSQLI_REPORT_OFF)` in `engine/connect.php` — this is the single
   highest-leverage line in the whole migration; if it's ever removed, every SQL error
   anywhere in the app becomes an uncaught fatal instead of a graceful failure.
2. **Internal functions throwing `TypeError` on wrong types** — this was the most common
   fatal by far. `count()`, `in_array()`, `mysqli_fetch_assoc()`/`fetch_row()` etc. all now
   throw `TypeError` when handed `null` (an undefined array key, a failed query result)
   instead of the old warn-and-continue behavior. Every instance found so far was patched
   with a `?? array()` / truthiness guard at the call site — but this is exactly the kind
   of bug that only surfaces when a specific code path actually runs, so **expect more of
   these** as untested features get exercised. `menuAjax.php`, `userApply.php`, and the core
   `sql_*` functions have all had at least one of these.
3. **`create_function()`** — removed in PHP 8.0. Fixed in `engine/engine.php` (converted to
   real closures) and in the bundled phpseclib `Crypt/Base.php` (copy-pasted into 4
   locations — fixed via an `eval()`-based polyfill since that code dynamically assembles
   the function body as a string, not a literal one that could become a plain closure).
4. **Curly-brace string/array offsets** (`$var{key}`) — a parse error in PHP 8, not just a
   warning. Found ~30+ instances concentrated in the bundled `htmlfilter.php` (2 copies) and
   SwiftMailer. A naive regex only catches the numeric-offset form; the array-access form
   (`$arr{$key}`) needs a broader pattern — see the fix commit for the exact regex used.
5. **`each()`** — removed. 2 real instances (in bundled `htmlfilter.php`); everything else
   matching `/each(/` in a naive grep was jQuery's `.each()`, a false positive.
6. **`__autoload()`** — as of PHP 8, even an *unreachable* declaration of a function named
   this is a hard compile-time fatal, not just "no longer magically invoked" as the RFC
   might suggest. Found in bundled PHPMailer's autoloader (dead code path, gated by a
   `PHP_VERSION` check that's always true on anything modern — but PHP 8 still refuses to
   even parse the file). Removed the dead branch entirely.
7. **Required params after optional ones** — deprecated, not fatal, but noisy. Two instances
   fixed in `engine.php`.
8. Two **pre-existing bugs unrelated to PHP 8** were found and fixed along the way (a missing
   comma in `_indd_sent_back-handler.php`, a stray semicolon in `tesztAjax2.php`) — both
   would have been parse errors on PHP 7.4 too, meaning those code paths were already dead/
   never exercised. Worth knowing in case "this feature never worked" comes up.

`client/index-old.php` (a dead, unreferenced, older copy of the login page carrying the same
vulnerabilities as the fixed one) was deleted outright rather than patched.

## Security fixes applied

Found via direct code reading and confirmed against real data, not theoretical:

- **SQL injection in the login form itself** (`client/index2.php`) — `$_POST['username']`
  was concatenated unescaped into the WHERE clause. This was the single most severe finding
  of the whole engagement. Fixed by escaping + restructuring to fetch-by-username-then-
  verify-password-in-PHP rather than baking both into one query string.
- **Unsalted MD5 passwords** — confirmed via real stored hashes (32-char hex, no salt).
  Replaced with a `checkPassword()` helper (`engine/engine.php`) that verifies bcrypt
  (`password_verify()`) or falls back to legacy MD5 comparison, **transparently re-hashing
  to bcrypt on successful legacy login** — no forced password reset, no separate migration
  script, accounts upgrade themselves as people log in. Applied consistently across all 7
  places passwords are checked or set (login ×2 paths, `create_user.php`, `settings.php`
  [dead code, see below], `userApply.php`, `tAPI/tAPI.php` ×2) — fixing only the main login
  would have left the others just as broken.
  - **Schema note**: the `accounts.pass` column had to be widened from `varchar(50)` to
    `varchar(255)` — bcrypt hashes are 60 chars, wouldn't fit. If you ever see password
    changes silently failing to save, check this hasn't regressed.
- **CSRF token on login** — none existed anywhere in the codebase (checked all ~800 files).
  Added for the login form specifically (generated in `client/login.php`, verified in
  `index2.php`). Broader CSRF coverage across the rest of the app was *not* done — this was
  scoped to the login form as the highest-value target, not a systemic fix.
- **Session fixation** — `session_regenerate_id(true)` added on successful login. No prior
  session ID rotation existed anywhere.
- **Persistent-login ("remember me") cookie was a forgery vector** — the `intra_user` cookie
  used to hold the *raw account ID*, trusted with zero verification, and that same
  unescaped cookie value flowed straight into a SQL `UPDATE` — a second, independent SQL
  injection vector via the cookie itself, on top of trivial account impersonation (set
  `intra_user=1` in dev tools, you're logged in as user 1). Fixed: the cookie now holds a
  random 256-bit token; only its SHA-256 hash is stored server-side
  (`accounts.remember_token`); presenting the cookie proves nothing unless it matches.
  Revoked on logout. `engine.php`: `issueRememberToken()` / `resolveRememberToken()` /
  `clearRememberToken()`.

**Not done, and worth knowing about**: the entire `sql_*` DB layer (`sql_get`, `sql_add`,
`sql_update`, `sql_delete` in `engine.php`) builds every query via raw string concatenation
with zero escaping or parameterization, everywhere else in the app outside the specific
paths touched above. This is a systemic SQL-injection surface across essentially the whole
application. Fixing the login form and password paths closed the two most severe,
externally-reachable holes, but this is not a comprehensive fix — treat any *other*
user-input-driven query as suspect until proven otherwise.

## The Enfocus Switch integration

`client/engine/switchAPI.php` talks to an external Enfocus Switch server (hardcoded IP,
see `engine/switchconstant.php` for `SWITCHURL`/`SWITCHLOGINURL`) for job routing. This
network path was originally fully blocked from trkdev2 at the network level, specifically
so this dev/refactoring work couldn't accidentally affect the real production Switch
instance. **That block has since been intentionally, partially lifted** by the project
owner: trkdev2 can now reach Switch (confirmed via both a raw TCP connect and an actual
HTTP request to `SWITCHLOGINURL`, both succeeding in under 10ms), but only for jobs on
behalf of **TestCo** - this was a deliberate decision, not a regression or a gap, and it
lines up with (rather than duplicates) the application-level allowlist enforced by
`switchClientAllowed()`/`switchBulkSyncAllowed()` (see below): both the network rule and
the app-level `TRKDEV_ENVIRONMENT` gate independently restrict the same one test client,
rather than the network layer blocking everything and the app layer being the only thing
narrowing it down. Do not widen either layer without checking with the project owner
first.

(An earlier version of this document briefly - and incorrectly - described this as an
unexplained safety-boundary regression, written before being told about the intentional
partial lift; corrected here once that context was provided. A later version then
incorrectly listed Colorcom alongside TestCo as an allowed dev test client - Colorcom is
a real production client, not a test client, and the project owner corrected this
2026-07-22: `$allowed` in both `switchClientAllowed()` and `switchBulkSyncAllowed()`
(`client/engine/switchAPI.php`) now contains only `'testco'`. If you ever find `'colorcom'`
back in either array, that's a regression, not a restore of prior intent.)

Two real bugs were found and fixed in this integration:

1. **No timeout on any of the 7 `curl_exec()` calls in `switchAPI.php`** — an unreachable/
   slow Switch server would hang the request for up to PHP's `max_execution_time` (5
   minutes), holding a PHP-FPM worker the whole time. This is a real production risk too,
   not just a dev-environment symptom — if Switch is ever slow in prod, every user hits this
   same hang. Fixed: all calls now have `CURLOPT_CONNECTTIMEOUT`/`CURLOPT_TIMEOUT` set.
2. **`XMLUpload2()` (the PMD/publications-master-data → Switch sync) fired unconditionally
   on every settings save**, regardless of whether anything relevant actually changed (e.g.
   a pure password change still triggered a full Switch sync). Fixed by gating it on an
   `$xmlChanged` flag, only set when the per-magazine mail-list actually changes.

Beyond the timeout fix, a **synchronous-first-with-durable-async-fallback** pattern was
built for this specific path (as a template — not yet extended to the other Switch-sending
functions, which still block synchronously with no fallback):

- `XMLUpload2()` tries `SendPmdXmlToSwitch()` synchronously with a short (2s/5s) timeout —
  preserves today's immediacy when Switch is healthy.
- On failure, it writes to `switch_sync_queue` (a new table) instead of blocking or losing
  the update.
- `client/cron/switch_sync_worker.php` (new cron job, runs every minute) retries queued
  jobs with a longer timeout and exponential backoff (1m→5m→15m→1h→hourly), dead-lettering
  to `status='failed'` after 10 attempts.
- A `_DEV` filename safety suffix is applied to the Switch-facing label (not the real local
  file path — those are deliberately kept separate, see the code comment in
  `SendPmdXmlToSwitch()`) whenever `gethostname()` contains "dev" — so even if network
  access were ever restored, Switch could never mistake this system's data for production's.

**If you're asked to extend this pattern to the other Switch-send functions** (`SwitchASend`,
`SwitchAnyagSend`, `SwitchSend` — still in `switchAPI.php`, all still purely synchronous with
no queue fallback), the template above is the one to copy.

**`SwitchSend_Rename` was extended this way 2026-07-23** — not the synchronous-first variant
(there's no "try fast, fall back to queue" here, since the trigger is always a *bulk* action
with no reason to ever attempt any of it synchronously): `client/engine/download_ajax.php`'s
`type=accept` (bulk page-approve) handler now writes straight to `switch_sync_queue`
(`job_type='switch_send_rename'`, payload = `{datas, file, newname}` matching
`SwitchSend_Rename()`'s own params) instead of calling it inline per page. The pageinfo
status/action_log update (what the user actually sees) still happens immediately in the same
request; only the Switch notification is deferred. `switch_sync_worker.php` gained a matching
`case 'switch_send_rename'`. Motivation: approving dozens of pages in one bulk action used to
block the whole request on that many sequential `SwitchSend_Rename()` calls (5s connect + 15s
timeout each, worst case a minute+) — this was a direct user complaint, not a hypothetical.
If asked to do the same for `light_accept` (the Adhoc-client hotlink approve flow, same file)
or any of the three functions above, this is now a second worked example alongside
`XMLUpload2()`.

**Two real bugs found and fixed getting this working, both worth knowing about beyond this
one call site:**

1. **Never write a JSON payload to `switch_sync_queue` (or anywhere else) via a raw
   `sql_add()`/`sql_update()` call — always go through `QueueSwitchRetry()`
   (`engine/xml_handler.php`)**, or otherwise `mysqli_real_escape_string()` it yourself first.
   `sql_add()` does zero escaping (see the SQL-injection note earlier in this document); a
   `json_encode()`'d string routinely contains backslash escapes (`\uXXXX` for any non-ASCII
   character, `\/` for path separators), and MySQL's own string-literal parser silently drops
   the backslash on any escape sequence it doesn't itself recognize when the string is
   concatenated in unescaped. First attempt at the `switch_send_rename` job type above did
   exactly this and got bitten immediately in real use: a package directory containing "í"
   (Hungarian is common in this data — "címlap" = cover) got stored as `"cu00edmlap"`, silently
   corrupting the queued file path.
2. **`SwitchSend_Rename()` (`client/engine/switchAPI.php`) used to hard-crash on a missing
   source file** — it already computed `is_file(...)` and logged the result, but never
   actually gated on it: `filesize()`/`mime_content_type()` ran unconditionally, and PHP 8's
   `mime_content_type()` throws a `TypeError` on the `false` a missing file produces (another
   instance of the "internal functions throwing TypeError on wrong types" bug class in the
   PHP 7→8 section above). This is a bigger deal than one bad payload: `switch_sync_worker.php`
   processes queued jobs **in one script run**, `ORDER BY id ASC`; a fatal on the first job in
   the batch kills the whole run before it reaches any of the others — confirmed live, the one
   corrupted `switch_send_rename` job (bug #1 above) silently starved every other queued job
   behind it, of any job_type, on every single cron tick, until this was fixed. Now returns a
   graceful `array(null, "Source file not found: ...")` instead - matches the same
   null-means-retryable-failure convention `switch_sync_worker.php` already used for a genuine
   curl failure, so a transient "file not written yet" race retries normally instead of taking
   the whole worker down.

### PMD file ownership (2026-07-27 incident)

`client/xml/pmd.xml` is the single local source of truth this whole integration reads from
and writes to (`changeXmlDatabase()` in `engine/xml_handler.php`) - every publication/magazine
create or edit goes through it before anything reaches Switch. **It must always be owned
`www-data:www-data`**, same as every other file in `client/xml/` - PHP-FPM runs as `www-data`
and has no write access to a root-owned file.

Found live: `pmd.xml` (and a same-dated `.bak-zqxt9-removal-...` file next to it) was owned
`root:root`, almost certainly left that way by a one-off manual root-run cleanup around
2026-07-15. `changeXmlDatabase()`'s `file_put_contents()` call doesn't throw on a permissions
failure - it just returns `false` and nothing downstream checked that return value - so this
was completely silent: **every new publication/magazine created for 12 days had its PMD entry
silently dropped**, with the database and PMD drifting out of sync the whole time and no error
anywhere to notice it by. It surfaced only because a newly-created job's `Workflow`/
`PageNumbering` lookups (which read from PMD, not the DB) were coming back null, which cascaded
into wrong-branch UI bugs several layers removed from the actual cause (see the Adhoc job
Parts-page-sequence investigation this same day).

Two-part fix, both intentional, don't remove either thinking it's redundant with the other:

1. **Loud failure at the write site**: `changeXmlDatabase()` now checks `file_put_contents()`'s
   return value and `error_log()`s a `CRITICAL:` line (including `is_writable()` and the
   running uid) if it ever fails, for *any* reason (permissions, disk full, etc.) - independent
   of the cron cadence below, so a fresh occurrence is visible immediately in the logs rather
   than only after the next self-heal tick.
2. **Self-healing cron**: `client/cron/enforce_pmd_ownership.php`, added to root's crontab
   (`* * * * *`, see "Cron jobs" below) - checks `pmd.xml`'s owner/group every minute and
   `chown`s it back to `www-data:www-data` if it's ever anything else. This has to be a
   root-run cron job, not an in-app check - `www-data` can never `chown` a root-owned file back
   to itself; only root can.

If you're ever manually editing `pmd.xml` directly (as root, via SSH, for a one-off fix or
cleanup script) - **restore `www-data:www-data` ownership before you're done**, or rely on the
cron job above to do it within a minute. Either way, verify with
`stat -c "%U:%G" client/xml/pmd.xml` before assuming a manual edit "worked."

## DynaPDF

The actual PDF-rendering engine behind print-proof previews (`engine.php`'s `pdftoimage()`
family). A genuine PHP 8.4 Linux build exists (found at `/home/user/php_8.4.zip` on the old
dev box, now installed at `/usr/lib/php/20240924/dynapdf.so` on trkdev2) and has been
**verified working end-to-end** (opened a real PDF, rendered a page to JPEG, matching
engine.php's actual code path). It ran watermarked ("DynaPDF 5.0", unlicensed trial mode)
until 2026-07-13, when a renewed v5 license key was obtained and installed — **re-verified
after the key change**: a fresh render via the real `PdfToImageRender()` code path against
a real sample PDF produced no watermark and logged no license/trial-related warnings.

The key lives in **three** separately-duplicated copies of `config.inc.php` (`engine/`,
`client/engine/`, and the webroot root) — not one shared file, consistent with this app's
general pattern of copy-pasted files rather than shared includes (see "Known architectural
issues" below). All three needed updating together; a future key renewal must do the same
or some code paths will silently keep using a stale key.

**2026-08-07 update**: all three now read the key via `getenv('TRKDEV_DYNAPDF_LICENSE_KEY')`
instead of a hardcoded literal, ahead of this repo's first-ever push to `origin`. The real
value lives in `env[TRKDEV_DYNAPDF_LICENSE_KEY]` in the FPM pool config
(`/etc/php/8.4/fpm/pool.d/www.conf`), same mechanism as `TRKDEV_DB_PASSWORD` below. A future
key renewal now only needs a value change in one place (the FPM pool env) plus a reload,
not three file edits.

## R3 (PDF rendering / color management)

A second, separate PDF-rasterization tool from DynaPDF — a closed-source CLI (`r3`, plus a
worker binary `r3render` that `r3` execs via `/bin/sh -c`) that does color-managed PDF→JPEG
rendering (`-mode:RENDER`), box/metadata extraction (`-mode:GETDATA`), and spot-color
measurement (`-mode:MEASURE`), using ICC profiles for source/target color spaces. It backs
`r3API/*.php` and every `PDFtoImage*()` variant in `engine.php` — including the live
"compare" (redline/proofing) feature, not just the two test scripts below.

**This was not part of the original engine migration** — being a standalone binary +
resource bundle rather than PHP code, it fell outside the "copy the engine, not the media"
split and was simply absent from trkdev2 initially. It was later copied in manually to
`/var/www/html/r3API/r3/` (binaries, ICC profiles, sample PDFs).

**Tests**: `pdftoimage_test.php` (webroot root) is the real one — renders a bundled sample
PDF end-to-end and is safe to hit directly (`GET /pdftoimage_test.php`). `r3API/teszt.php`
is mostly commented-out/WIP and references a `spot.pdf` that was never part of what got
copied in — not a real test, don't read anything into it failing.

**Three things had to be true simultaneously for this to work, all non-obvious and all
required together** — if R3 silently produces empty output again after any system-level
change (OS upgrade, new VM, restoring `/etc/group` or a backup, re-copying the `r3API/r3/`
binaries from an archive), check all three:

1. **Execute bit + ownership** on `r3API/r3/r3` and `r3API/r3/r3render` — `www-data:www-data`,
   mode `755`. Trivial, but the files as originally copied in were `644 root:root`.
2. **`cap_sys_rawio` capability on *both* `r3` and `r3render`** — not just `r3`. `r3`
   internally execs `/bin/sh -c './r3render ...'`, and Linux file capabilities do **not**
   propagate across an exec of a different file that doesn't itself carry them. `r3render`
   is the one that actually does the `open("/dev/mem", O_RDONLY)` call (almost certainly
   reading the motherboard/DMI UUID for a hardware-bound license check — that's the vendor's
   own explanation, not something inferred from the binary). Set via:
   ```
   setcap cap_sys_rawio+ep /var/www/html/r3API/r3/r3
   setcap cap_sys_rawio+ep /var/www/html/r3API/r3/r3render
   ```
   `cap_sys_rawio` bypasses `/dev/mem`'s *driver-level* `capable()` check inside the kernel
   (`open_port()` in `drivers/char/mem.c`) — but does **not** bypass the device node's own
   Unix file permissions. That's the next point.
3. **`www-data` must be a member of the `kmem` group.** `/dev/mem` is `crw-r----- root:kmem`
   — a *stock* Debian udev rule (`/lib/udev/rules.d/50-udev-default.rules:44`,
   `SUBSYSTEM=="mem", KERNEL=="mem|kmem|port", GROUP="kmem", MODE="0640"`), not anything
   custom. Without group membership, the open fails with `EACCES` *before* the kernel ever
   reaches the capability check, regardless of what `cap_sys_rawio` is set to. Set via:
   ```
   usermod -aG kmem www-data
   ```
   then restart php-fpm (existing worker processes keep whatever supplementary groups they
   had at spawn time — a running php-fpm won't pick up a new group membership until it
   restarts): `systemctl restart php8.4-fpm`.

**Persistence across reboot — confirmed empirically, not just in theory** (2026-07-10):
rebooted trkdev2 cold and re-ran `pdftoimage_test.php` over HTTP with zero manual
re-application of anything, and it produced a correct, non-empty rendered JPEG on the first
attempt. This holds together on a fresh boot because none of the three things above are
runtime/kernel state — they're all persistent, on-disk facts: `cap_sys_rawio` is stored in
each binary's `security.capability` extended attribute (survives like any other file
property on ext4), `kmem` group membership is a line in `/etc/group`, and `/dev/mem`'s
permissions are reapplied by the stock udev rule every boot the same way. php-fpm's systemd
unit (`/usr/lib/systemd/system/php8.4-fpm.service`) also has no sandboxing directives
(`PrivateDevices`, `NoNewPrivileges`, `CapabilityBoundingSet`, etc.) that could strip this on
a service restart. **The one way to lose this silently**: re-copying `r3`/`r3render` from a
fresh archive/backup will produce new files without the capability xattr — the two `setcap`
lines above need to be re-run any time the binaries themselves are replaced, this doc's
existence is the only thing that will remind you.

Granting `cap_sys_rawio` to a binary invoked by a web-facing PHP process is a real,
deliberate widening of what that process can do if ever compromised (full physical memory
read, not narrowly scoped to the DMI table this specific binary wants) — accepted here on
the basis that this box sits behind a dedicated firewall gateway (Sophos) and isn't exposed
to raw internet. Worth re-litigating if this system's network exposure ever changes.

### R3's CPU/license conflict with this VM, and the render-VM workaround

The `/dev/mem` read described above isn't just reading arbitrary bytes — R3's license is
bound to the motherboard/CPU identity it finds there, and it only accepts a genuine `kvm64`
QEMU CPU signature. That's a problem on trkdev2 specifically: Claude Code's own tooling
(its Bun-based CLI) hangs silently during install/startup on `kvm64` and needs a real,
modern CPU generation (Broadwell tested and working) with a full instruction set. One VM
can't satisfy both constraints at once — whichever CPU type it runs, one side breaks.

**Tried and deliberately abandoned (fully removed from the system 2026-07-19, no residue
should remain)**: spoofing R3 into accepting Broadwell as `kvm64`. This started as ELF
interpreter-based CPU *feature* masking (an `LD_PRELOAD`-style loader swapped in as R3's
`PT_INTERP`, faulting out SSE/AVX/FMA/BMI instructions so R3 would tolerate a CPU that
lacks them) and escalated into full CPU-identity spoofing — overriding family/model/
stepping/brand string, then byte-for-byte substituting the entire physical memory region
R3 reads via a seccomp+ptrace supervisor. The substitution was verified byte-perfect
against a genuine kvm64 reference capture, and R3's license check *still* failed — the
real check apparently isn't purely a function of those bytes, and R3's obfuscation
resisted further static/dynamic analysis without disproportionate effort. If you ever
find fragments of this (`libcpuidoverride`, `libdevmemspoof`, `r3ptrace`/`memspoof_trace`,
`kvm64_devmem_reference.bin`, `r3API/r3-spoofed/`, a `r3_patched` binary with its
interpreter pointed at `/usr/lib/cpuid.so`) — it's dead, unfinished exploration that
should have been deleted; don't resume from it, the two-VM approach below superseded it
entirely.

**What's actually in place**: a second, dedicated **render-VM (10.10.30.22)** — an old
Debian 8 / PHP 5.6 box kept running on `kvm64` — does nothing but real R3 rendering.
trkdev2 stays on Broadwell so Claude Code's own tooling keeps working, and every R3 call
routes through `engine/r3client.php`'s single `r3run($mode, $params, $inputPath, ...)`
entry point, which replaced ~29 inline `shell_exec('cd .../r3; ./r3 ...')` call sites
across `r3API/*.php`, `engine/engine.php`, and `client/*.php`. `r3run()` picks local vs.
remote automatically via the `R3_REMOTE_MODE` constant in `engine/r3client_config.php` —
a genuine kvm64 signature (`GenuineIntel`, family 15, model 6, per `/proc/cpuinfo`) runs
R3 locally; anything else (trkdev2's own Broadwell included) sends the job to the
render-VM's `r3remote/run.php` over HTTP (multipart file upload + shared-token auth,
base64-encoded JSON response). This means the exact same PHP code path works unmodified
on trkdev2, on the render-VM, and eventually in production — it self-detects which side
it's on. **See "Boot-time render-mode detection" below for how that self-detection
actually happens as of 2026-08-27** — the mechanism changed; the constant and its
downstream behavior didn't.

The shared auth token lives in `engine/r3client_config.php` (trkdev2) and the matching
`r3remote/config.php` (render-VM). Rotate by editing both sides together.

**2026-08-07 correction + update**: `engine/r3client_config.php` was actually tracked in git
this whole time despite the note above — confirmed via `git ls-files` during a pre-push
secrets audit (this repo has never actually been pushed to `origin` yet, so the token never
left this VM, but it would have on the first push). Fixed by switching the token to
`define('R3_REMOTE_TOKEN', getenv('TRKDEV_R3_TOKEN'))`, value now in
`env[TRKDEV_R3_TOKEN]` in the FPM pool config, same as the DB password. `r3remote/config.php`
on the render-VM side is a separate machine and wasn't touched by this pass.

Two small UX consequences of remote rendering being visibly slower (network round-trip on
top of the render itself): the Pages-view spinner turns red instead of the default gray
while a render is being served remotely (`.remote-render` rule in `client/css/client.css`,
toggled by `updateRenderModeIndicator()` in `flatplan_preview.php`/`vflatplan_preview.php`
off the `R3_REMOTE_MODE` flag threaded through the AJAX response); and the stuck-spinner
recovery watchdog and AJAX timeout are tuned per mode (15s local / 30s remote) rather than
one flat ceiling, since a legitimately-slow remote round-trip shouldn't be mistaken for a
lost response.

**Moot in production**: production's own hardware is genuine kvm64, so none of this detour
applies there — `r3run()` will simply always take the local path once this ships. See
"Deploying to production" below.

### Boot-time render-mode detection (2026-08-27)

Before this date, `R3_REMOTE_MODE` was computed live on every single PHP request — each
FPM worker re-read and re-parsed `/proc/cpuinfo` from scratch every time `r3client_config.php`
was included. That was fine when this box's CPU model was expected to be stable for a whole
dev session, but the project owner flagged an upcoming shift: extended stretches of this box
staying on a modern CPU (Broadwell or similar) continuously, specifically so Claude Code can
keep working uninterrupted while handling a steady stream of small production-hotfix-style
corrections, rather than only briefly during isolated dev sessions. A CPU model can't change
without an actual reboot, so re-deriving it on every request was always redundant work — it
just hadn't mattered enough to fix until continuous-uptime dev became the expected mode.

**What changed**: the CPU fingerprint check itself (`r3_running_on_kvm64()`) moved out of
request-time PHP entirely, into `engine/cpu_detect.php` (unchanged logic, just relocated).
`bin/detect-render-mode.php` calls it exactly once, at boot, via a new systemd oneshot unit
(`bin/trkdev-detect-render-mode.service`, ordered `Before=php8.4-fpm.service` so the decision
exists before the app can serve a single request) and writes the single word `local` or
`remote` to `/etc/trkdev-render-mode` (root-owned, world-readable, not in git — same
"per-machine state, not code" category as `/var/www/server_constans.php`). `r3client_config.php`
now just reads that file on every request instead of re-detecting — same constant name
(`R3_REMOTE_MODE`), same downstream behavior, every call site listed above is unchanged.
Falls back to `remote` (the safe default — see the file's own comment) if the state file is
ever missing or unreadable, e.g. before the installer has been run on a fresh clone.

**Install once per machine** (idempotent, safe to re-run): `sudo bin/install-render-mode-detector.sh`
— symlinks the unit into `/etc/systemd/system/`, enables it, and runs it immediately so the
effect is live without waiting for a reboot. `bin/preflight-prod-check.sh`'s "R3 renderer"
section checks both that the service is enabled and that the state file's contents still
match what the CPU fingerprint says *right now* (catches a machine that was rebooted before
the service was ever installed, or a hypervisor-level CPU-model change that hasn't been
re-detected yet).

**DynaPDF was deliberately left out of this switch.** The project owner's original request
raised it as a possible second candidate, but DynaPDF has no CPU-identity binding in this
codebase — it's a licensed PHP extension keyed off `config.inc.php`'s license string (see the
DynaPDF section above), already confirmed working locally on this box's own Broadwell CPU, and
there is no remote-DynaPDF code path anywhere to fall back to even if it did need one. Revisit
only if DynaPDF is ever actually observed failing on a non-kvm64 CPU — nothing in this session
found evidence that it does.

## dynAPI/tracker/, client/temp/, and filedownload.php's zip library

Same shape as R3 above: things absent on trkdev2 because they fell outside the "copy the
engine, not the media" migration split, discovered when the Flatplan context menu's three
Download items turned out to be broken (fixed 2026-07-14).

**`dynAPI/tracker/`** is gitignored and was entirely empty on trkdev2 until manually copied
in from production. Only `one_local.php` is actually needed there — it's `include()`'d
directly by `client/engine/download_ajax.php`'s "one" (PDF Merged) download type, is fully
self-contained (just needs the native `dynapdf` PHP extension, already loaded — check
`php -m | grep dynapdf` — and the already-present `/var/www/html/config.inc.php`), and uses
DynaPDF to merge the selected pages' PDFs into one file. Everything else that came over in
the same copy (`ad_check.php`, `ad_lowres.php`, `calendarpdf.php`, `flatplanpdf.php`,
`functions.php`, `getbbox.php`, `multi.php`, `one.php`, two `.icc` profiles) belongs to a
*separate* remote dynAPI service reached over HTTP (`http://DYNAIP/dynAPI/tracker/...`) by
`calendar_to_pdf.php` and `client/engine/switch/new_ad_results-handler.php` — those two
features call the remote host directly and never touch a local copy, so the extras were
removed rather than kept "just in case".

**`client/temp/`** (+ `client/temp/_zip/`) is also gitignored and was missing entirely —
needed by `one_local.php`'s PDF output, `download_ajax.php`'s JPG-download zip build (which
was *also* separately broken: it shelled out via `cd r3`, relative to its own directory,
landing in `client/engine/r3/` — a JPG cache folder with no `r3` binary in it, instead of
the real `/var/www/html/r3API/r3` — fixed to match every other r3 call site in `engine.php`),
and `filedownload.php`'s zip build. Created manually (`www-data:www-data`, mode `755`,
matching sibling runtime dirs like `client/handout/`).

**`filedownload.php`** (the "PDF" context-menu download, plus the asset-download flows) used
to `require('/composer/vendor/autoload.php')` purely to reach a Composer-installed
`ZipStream` library — no Composer vendor directory, nor a `composer.json`/`composer.lock` to
reinstall one from, exists anywhere on this box, so every request there fataled regardless
of type. Rewritten to use PHP's built-in `ZipArchive` (`php8.4-zip`, the Debian/APT package —
already installed) instead, matching what `download_ajax.php`'s own zip handling already
relied on.

## Request timeouts (raised 2026-07-14, for the JPG download — affects everything)

The JPG context-menu download (`client/engine/download_ajax.php`, `type=jpg`) is normally
used to grab many or all of an issue's pages at once, each page costing ~2s of r3 render
time. Even after parallelizing that render (4-way, via `client/engine/render_page_worker.php`
+ `proc_open()` — matches this box's core count), a full "select all" (84 pages) still takes
~80s. Two timeouts had to be raised to let that complete, **neither lives in this git repo**:

- **`request_terminate_timeout`** in `/etc/php/8.4/fpm/pool.d/www.conf`: `30s` → `90s` (plus
  matching `max_execution_time` in `/etc/php/8.4/fpm/php.ini`). This is the whole `www` pool,
  not scoped to just this endpoint — confirmed via `php8.4-fpm.log` that *other*, unrelated
  scripts (`loadLog.php`, `flatplan_ajax.php`) were also silently hitting the same 30s wall
  and getting killed. Raising it gives every slow request more headroom, for better or worse —
  a genuinely stuck/runaway script now also takes up to 90s to get killed instead of 30s.
- **`fastcgi_read_timeout` / `fastcgi_send_timeout`** in both server blocks of
  `/etc/nginx/sites-enabled/trkdev`: nginx's own default (60s) was cutting the connection
  before php-fpm's raised 90s limit ever had a chance to matter — set to `100s` to stay just
  ahead of it.

If large-selection downloads (or anything else long-running) start failing again after any
infra change (nginx reinstall/config regen, php-fpm pool reset from a template, etc.), check
both of these are still in place — `systemctl reload nginx` / `restart php8.4-fpm` after
editing either.

## SSL

Current cert: `*.colorcom.hu` wildcard, valid through 2026-10-06, installed at
`/etc/nginx/ssl/2025/` (fullchain + key), configured in the nginx `443` server block for
`trkdev2.colorcom.hu`. Source zip had a private key + several older-year certs going back to
2018 on the original dev box too — not migrated, presumably superseded, but check before
assuming the current one is the *only* one that matters if certs ever seem to mismatch.

## Cron jobs

All 7 original jobs from the old box's crontab were re-created here (they hadn't been for a
while during this migration — don't assume they were already running just because the code
was deployed). Plus `switch_sync_worker.php` and `enforce_pmd_ownership.php` (see "PMD file
ownership" above — this one has no DB dependency, so its crontab line skips the
`/etc/trkdev-db.env` sourcing the others need). See `crontab -l` as root for the
authoritative list. **Credentials note**: the DB password needed by these scripts (via
`getenv('TRKDEV_DB_PASSWORD')`, same mechanism `connect.php` uses) is *not* embedded in the
crontab file itself (a persistent, `crontab -l`-inspectable artifact) — it lives in
`/etc/trkdev-db.env` (root-only, `chmod 600`), sourced by each cron line via
`bash -c 'set -a; source /etc/trkdev-db.env; set +a; php ...'`.

## Access / credentials reference

- **SSH to trkdev2**: key at `~/.ssh/trkdev_newvm` (Mac-side), host alias `trkdev-2` in
  `~/.ssh/config`, connects as root.
- **DB**: root has a `.my.cnf` on trkdev2 (passwordless CLI access for root). The app itself
  connects as a dedicated least-privilege user `trkapp` (not root — the old box used
  root:root, deliberately not carried forward). Password is in the FPM/CLI pool env
  (`env[TRKDEV_DB_PASSWORD]`) and in `/etc/trkdev-db.env` for cron — not hardcoded in any
  PHP file. If you need it interactively, check those two places, not the code.
- **Test login**: account `peter` (id 7), password `TestPassw0rd!` (bcrypt-hashed for real,
  this isn't a placeholder sitting in plaintext anywhere) — a deliberately-set known
  credential for testing, not something recovered from production. Accounts `zenda`/`zendax`
  (Péter Tamás) exist but their real passwords are unknown (still whatever legacy MD5 hash
  came from the original dump — nobody has logged in as them since the migration to trigger
  the auto-upgrade-to-bcrypt path).

## Known architectural issues (discussed, not fixed — by design)

Raised directly by the project owner and deliberately *not* addressed as part of this
migration, since they're a much larger, separate body of work:

- **Page-building**: raw string-concatenation "templating" — SQL, business logic, and HTML
  all interleaved in the same functions, no separation of concerns, routing is a giant
  `if/switch` on `$_GET['page']`. The pervasive file duplication (4 copies of
  `Crypt/Base.php`, 2 of `htmlfilter.php`, `engine.php`/`engine-dev.php`/`engine-php5.php`
  as parallel historical forks — the latter two are dead but still present, not deleted)
  is a direct consequence of there being no version control until this migration: with no
  safe way to modify a shared file, people copied it instead.
- **CSS**: served via PHP files (`css/default.php`), most real styling is inline
  `style='...'` attributes baked into generated HTML strings, jQuery 1.10.2 (2013) plus
  matching-era plugins, no build pipeline.
- These are real, valid concerns but scoped as "a quarter-scale rewrite project", not
  something to bundle into ongoing bug fixes. Revisit only if explicitly asked to scope
  that work.

### Pages View footer: deferred perf optimization (small, not urgent)

During the 2026-08-01 Pages View zoom/footer session (`client/flatplan_preview.php`,
`client/plugins/preview_rightPanel.php`, `client/css/flatplan.css`), navigation
(`reloadBG()`) was changed to fetch the real approve/reject status synchronously via a new
`applyPageStatus()` call (a *second* AJAX request to `engine/flatplan_ajax.php`), rather than
waiting on the pre-existing independent 600ms `refreshPageStatus()` poll — this fixed a real
race where the footer visibly repositioned twice per navigation. Project owner would like
this folded into `reloadBG()`'s own existing response instead (`engine/flatplan_reloadbg.php`,
one round-trip instead of two — a guaranteed few-ms win per navigation), but explicitly asked
to defer it and avoid risky changes for now: the status-HTML logic being moved
(`flatplan_ajax.php`'s `op=refreshPageStatus` handler) has real permission checks and separate
Hybrid-workflow/FlatplanStages handling, and duplicating rather than sharing it risks the
"two copies silently drift apart" failure mode this codebase already has plenty of (see
"What was migrated" / duplication note above). Do this as its own scoped session, factoring
the logic into a function both endpoints call — don't just copy-paste it into
`flatplan_reloadbg.php`.

## Phantom-account bug + admin self-service fix (2026-08-06 incident)

A real *production* incident, not a dev-only bug: an employee's `accounts` row was deleted
when she left the company; when she was later rehired as a freelancer, re-creating her
account silently failed — the name/email fields just turned red, no explanation anywhere.
Root cause: `client/plugins/accountsApply.php`'s `addMember` duplicate-check queries are
**global** (search the whole `accounts` table across every publisher), while the
admin-facing user list (`client/plugins/user/manage.php`) is **publisher-scoped**. A
leftover row under a stale/different `publisher` value is invisible in the admin's own list
but still blocks creation — a genuine "phantom" with no in-app way to find or remove it.
Diagnosed and fixed *on production* via direct SQL (no other option existed at the time).
What follows is the dev-side prevention fix — **done and verified only on trkdev2
(`nyomadake_intra`) so far, not yet applied to production.**

Three app-layer changes plus one schema addition:

1. **Better error feedback**: `addMember`'s duplicate checks now return a real explanatory
   message (the conflicting account's id + publisher name) via the `array(false, message)`
   response shape already wired into `menuApply()`'s JS (`client/index2.php`), instead of
   just reddening the field. New `error8`/`error9` keys in `client/lang/en.php` + `hu.php`
   only — not `de.php`/`pl.php`, see [[lang_translation_status]]. Also fixed
   `accountsApply.php` hardcoding `include_once('../lang/en.php')` regardless of the logged-in
   admin's actual language — a latent bug that would've silently defeated the new Hungarian
   strings; it now follows the same `$user[0][17]`-driven include `menuAjax.php`/`index2.php`
   already used.
2. **Global "Find Account" admin panel**: `client/plugins/accounts/findAccount.php` (new), a
   `findAccount` op in `menuAjax.php`, `findAccountList()` in `engine/engine.js` — lets an
   admin search `accounts` by name/email across *all* publishers, closing the exact gap that
   forced the original incident into direct-SQL territory. Read-only for now; deletion of a
   found row still goes through the existing `removeMember` flow using the id it surfaces.
3. **Audit trail**: `addMember`/`removeMember` now both write an `action_log` row (actor,
   action, and — for deletion specifically — the name+email as *text*, not just the id that's
   about to stop existing). Neither wrote anything before, which is exactly why the original
   incident needed DB forensics instead of just reading a log.

**Schema delta**: `user_groups.accounts_findAccount` (`int(11) NOT NULL DEFAULT 0`), gating
the new panel the same way every other `accounts_*` right already does. Added to
`db/schema.sql` and applied live on trkdev2; `SuperUser` (group id 2) is the only group
granted it so far (`UPDATE user_groups SET accounts_findAccount = 1 WHERE id = 2`). **This
column does not exist on production** — it's now one more line item on the schema-delta
checklist in "Deploying to production" below, not a special case to remember separately.

Explicitly out of scope, by design (see conversation history for full reasoning): no
referential-integrity sweep across the other tables that reference a deleted `accounts.id`
(`publications.owner/user`, `flatplan_planner.*`, `comments.user`, etc.) — those are live
historical data, not orphan garbage, same judgment call `cleanupPublicationRemnants()`
already makes for audit-style data. No DB-level `UNIQUE` constraint on `accounts.name`/
`email` yet either — the structurally correct long-term fix, but unsafe to add blind; run a
dedup check first (`SELECT name, COUNT(*) c FROM accounts GROUP BY name HAVING c > 1` and
the same for `email`) before ever adding one, on dev *and* separately on production — they
are different databases with different accumulated data and either could have duplicates the
other doesn't.

## Mail system cleanup (2026-08-07)

The mail subsystem had drifted badly: two vendored libraries (only PHPMailer was ever
actually called - SwiftMailer at `client/plugins/swiftmailer/` had zero live call sites and
was deleted whole), six copy-pasted PHPMailer wrapper functions in `engine/engine.php`, and a
personal address (`peter.tamas@colorcom.hu`, the original developer) hardcoded as a recipient
or silent BCC across a dozen files. All of that was consolidated/removed - see
`git log` around this date for the specifics, not repeated here.

**What's structural and worth knowing for future work:**

- Every send now goes through one internal `_smtpSend()` in `engine/engine.php`; the
  previously-6 public functions (`sendMail`, `wfSendMail`, `produkcioSendmail`,
  `produkcioSendmailAttach`, `sendMail_`) are thin wrappers over it with their original call
  signatures preserved. Two real SMTP accounts still exist on purpose (`MAIL_*` /
  `MAIL_WF_*` in `engine/constans.php`, currently identical credentials but kept as separate
  named accounts) - that's a mailbox choice, not the library duplication that was cleaned up.
  **2026-08-07**: `MAIL_PASS`/`MAIL_WF_PASS` switched from hardcoded literals to
  `getenv('TRKDEV_MAIL_PASSWORD')`/`getenv('TRKDEV_MAIL_WF_PASSWORD')` (values in the FPM pool
  env, same mechanism as `TRKDEV_DB_PASSWORD`), so the two accounts can still diverge
  independently later — same intent as before, just no longer plaintext in the repo. The
  Switch API login password (`switchAPI.php`, `client/engine/switchAPI.php`) was migrated the
  same way, to `getenv('TRKDEV_SWITCH_PASSWORD')` — done together as part of a pre-push
  secrets audit (see the DynaPDF and R3 sections above for the other two).
- **Two independent mail gates**, where there used to be one flag two UIs raced over:
  - **Gate A** (admin, unchanged): the "M" checkbox in a publication's Users dialog
    (`client/plugins/user/manage.php`, applied by `userApply.php?sub=manage`) controls PMD XML
    `<Mails>` membership, same as always.
  - **Gate B** (user, new): `accounts.mailOptOut` - a comma-separated magazine-id **opt-out**
    list (empty = subscribed to everything, so nothing changed for existing users on
    rollout), set from the user's own personal settings panel
    (`client/plugins/user/settings.php`, applied by `userApply.php?sub=settings`). This
    handler no longer touches the PMD XML at all - that used to be a real landmine (a personal
    preference save silently racing the admin's shared PMD edit for the same magazine).
  - Enforced at send time by `gatedMailRecipients( $magazineId, $magazineType, $pmdMailsCsv )`
    in `engine/engine.php`, called right after every `explode(";", ...->Mails)` that drives an
    automatic per-magazine notification. **Adhoc magazines are explicitly exempt** - short
    job-scoped users have no standing subscription concept, per explicit instruction.
    Manual, staff-typed one-off sends (asset resend, hotlink/handout share) are deliberately
    *not* gated - there's no "association + subscription" to check against free-typed
    addresses.
- **No more plaintext passwords in mail.** Password reset (`accountsApply.php?sub=resetpw`),
  new-account welcome mail (`accountsApply.php?sub=addMember`), and the external `tAPI`'s
  `addUser()` all now mail a one-time link to `client/set_password.php` instead
  (`accounts.pwset_token`/`pwset_expires`, same hashed-random-token pattern as
  `remember_token`). `set_password.php` is reached pre-login via `index2.php`'s
  `?page=set_password` route - it's allowlisted there alongside `vflatplan`/
  `vflatplan_preview` as one of the few pages servable without an active session. New
  accounts created via the admin's "Add Member" panel no longer get an admin-chosen password
  at all - they start locked out and set their own via the link.
- `securityAlert()` (failed-login mailer) no longer includes the attempted password in the
  alert body - username, IP, user-agent, and match/no-match result only.

## Deploying to production

See `bin/deploy-to-prod.sh` (and its `--help`) for the actual mechanism. In short: this
deploys **code only**, never touches production's real database data, requires an explicit
git ref, and is designed to be run against a target that doesn't exist yet at the time of
writing this document — production is still the untouched old-stack VM. The recommended
path (discussed with the project owner, not yet executed) is to build a fresh
Debian 13/nginx/PHP 8.4/MariaDB 11.8 twin of trkdev2 as the new production target, validate
thoroughly, then cut over — keeping the current production box untouched as an instant
rollback until the new one's proven. Full production data (including the media/job
directories deliberately excluded from this dev rebuild) would need its own migration plan
at that time — this document's "what was left behind" section is exactly what a real
production migration cannot skip.

### `/var/www/server_constans.php` — every hostname in every outgoing email depends on this

This file is **not in git** (deliberately, like `/etc/trkdev-db.env` — it's per-machine
config, not code) and is not touched by `bin/deploy-to-prod.sh`. On trkdev2 it currently
reads:

```php
define( "HOST", "Dev" );
define( "URL", "trkdev2.colorcom.hu" );
```

Every mail-embedded link in the app (password reset, account welcome, hotlink/asset/handout
notices, file-transfer download links — audited 2026-08-07 as part of the mail-system
cleanup, see the section above) is built from `PROTOCOL.URL` (`PROTOCOL` is `"https://"` from
`engine/constans.php`), never a hardcoded hostname — that audit found and fixed the two
places that still hardcoded a stray literal host (`tAPI/tAPI.php`'s welcome-email link,
`engine/fileClass.php`'s file-transfer-ready link both said `tracker.colorcom.hu`, an old
hostname matching neither dev nor the intended `trk.colorcom.hu` production host) so that
`URL` is now the single source of truth for every one of them.

**Before cutover, production's own `/var/www/server_constans.php` must define
`URL` as `"trk.colorcom.hu"`** — this is a manual step on the new box, not something the
deploy script or a git pull will do for you. Forgetting it means every password-reset and
welcome email in production silently links back to whatever `URL` was left as (or a fatal
"Undefined constant" if the file is missing entirely). Verify after cutover by triggering one
real reset-password send and checking the link in the actual received email, the same way
this was verified on trkdev2 (2026-08-07, using real `test1user@colorcom.hu`/
`test2user@colorcom.hu` mailboxes via IMAP against `mail.colorcom.hu`) — don't just trust the
config file was edited correctly.

### Known schema deltas vs. production — non-exhaustive, a real diff is still required

Per [[production_release_plan]] (stated by the project owner 2026-08-02): copying prod's
database into the cutover clone is explicitly **not** a straight dump/restore — step B of
that plan requires (1) excluding abandoned/orphaned tables and rows accumulated over
production's 15+ year life (never audited — only dev has been), and (2) adding whatever new
tables/columns the rebuilt codebase now needs that prod's old schema doesn't have. That
schema diff **has not been run yet, against either database** — the list below is only what
various unrelated bug fixes happened to surface along the way, not the output of any
systematic comparison. Treat it as a sanity check, never as sufficient by itself:

- `accounts.pass` widened `varchar(50)` → `varchar(255)` — bcrypt hashes (60 chars) don't fit
  the original column (see "Security fixes applied" above).
- `accounts.remember_token`, `accounts.session_token` — new columns backing the rewritten
  persistent-login mechanism (same section).
- `switch_sync_queue` — new table, the durable-retry queue for Switch sync (see "The Enfocus
  Switch integration" above).
- `user_groups.accounts_findAccount` — new column, gates the Find Account admin panel (see
  "Phantom-account bug" above, 2026-08-06).
- `accounts.mailOptOut`, `accounts.pwset_token`, `accounts.pwset_expires` — new columns,
  2026-08-07 mail-system cleanup: `mailOptOut` backs the user's personal per-magazine
  unsubscribe preference (Gate B, independent from the admin's PMD-Mails "M" checkbox — see
  the mail gating section below); `pwset_token`/`pwset_expires` back the secure
  set-your-own-password link that replaced emailing plaintext passwords.
- `magazines.preflight` (`varchar(10) NOT NULL DEFAULT 'Yes'`) — new column, 2026-09-03: mirrors
  the Hybrid-only publication-level "Preflight" Yes/No setting into the DB, same pattern as the
  pre-existing `HideApprovedComments` mirror (the setting's real source of truth is still the
  PMD XML `<Preflight>` node, written/read via `changeXmlDatabase()`). Applied live on this dev
  box's `nyomadake_intra` DB the same day, via a one-time web-triggered admin script (this dev
  shell has no DB credentials directly - `TRKDEV_DB_PASSWORD` is only in the web server's
  environment) - the script was deleted immediately after running. Confirmed present via
  `SHOW COLUMNS FROM magazines LIKE 'preflight'`. Still needs to be applied on staging/production
  separately when this feature ships there. **2026-09-04**: this setting had no actual consumer
  until now - it only fed the admin edit-form dropdown. `page_pdf-handler.php` /
  `page_pdf_teszt-handler.php` now read the PMD XML `<Preflight>` node (not the DB mirror, same as
  every other workflow setting these two files read) at the top of the request and skip all
  preflight work when it's explicitly "No" - the `_report` submission branch returns immediately
  (discarding the temp upload) instead of creating the `_preflight` directory/looking up
  `pageinfo`, and the retroactive `is_file()` pickups on new-page-submission are skipped too.
  Missing/empty still means Yes (same default the admin form and `changeXmlDatabase()` use), so
  jobs that predate this setting keep today's always-on behavior unchanged.
- `preflight_issues` — new table, 2026-09-04: holds the per-page Warning/Error entries parsed
  out of pdfToolbox's XML preflight report (`page_id`, `severity` enum('Error','Warning'),
  `message`, `time`), backing a hover tooltip on the `.preflightError` marker in Flatplan/Pages
  View (`client/engine/preflight_issues_ajax.php`). Separate from the pre-existing
  `pageinfo.preflight_error`/`preflight_report` columns, which stay PDF-report-only and keep
  gating the marker itself and its click-to-download behavior unchanged — this table is purely
  additive. Applied live on this dev box via `CREATE TABLE` against `nyomadake_intra`. The
  actual XML-parsing logic (`extractPreflightIssues()` in `engine/preflightXml.php`) is a stub
  pending a real sample pdfToolbox XML report — the table/endpoint/UI plumbing all work today,
  but nothing populates it until that parser is finished.

Before cutover: run an actual schema diff (`mariadb-dump --no-data` from production vs. this
repo's `db/schema.sql`, or equivalent) and reconcile every difference deliberately — don't
assume this list is complete just because it's the only one written down. And per the
project owner's explicit instruction: whatever the diff produces must be **very thoroughly
tested** on the staging clone before anyone calls the cutover ready — this matches
[[production_release_plan]]'s own step D expectation that untested code paths (most of them,
since dev's DB has been near-empty for months) likely still hide the same class of bug the
PHP 7→8 migration kept finding.

**2026-08-26: the real diff was finally run**, `mariadb-dump --no-data` from trk-source
(10.10.30.64, the untouched-old-stack production clone) against trk-stage's live schema.
Full per-table diff saved at `bin/migrate-prod-data.sh`'s companion notes below; the delta
list above was **not complete** - these were also found, none previously documented:

- `accounts.linked_account_id` (`int(11) NOT NULL DEFAULT 0`) — for `usertype=Temp` job-scoped
  accounts, points back to the real `accounts.id` if the invited email matched an existing
  registered user; 0 means genuinely new/unregistered.
- `ads.booked_page` (`int(11) DEFAULT 0`), `ads.booked_part` (`varchar(255) DEFAULT ''`) — new
  columns, purpose not yet investigated this pass.
- `pageinfo.preflight_error` (`tinyint(1) DEFAULT 0`), `pageinfo.preflight_report`,
  `pageinfo.preflight_origname` (both `varchar(255) DEFAULT ''`) — a preflight-check feature
  added since prod's schema forked; migrated legacy pages will correctly read as "no preflight
  run yet" via the defaults.
- `parts.grayscale` (`varchar(5) DEFAULT 'false'`) — new column.
- `comments`: gained a real primary/secondary index pair (`idx_pub_parent_page`,
  `idx_parent_id`) and switched `MyISAM` → `InnoDB`; `comment_log` also switched to `InnoDB`
  from `MyISAM`.
- Three genuinely new tables with no production equivalent, all self-contained
  reference/config data (not sourced from live jobs, so a data migration should leave them
  alone rather than truncate+reload): `color_standards` (the FOGRA/IFRA ICC-profile registry
  `partDetect()` now reads from), `calendar_holidays`, and `ad_sizes` (**not** a new table —
  a renamed/re-keyed successor to prod's `ad_sizes_old`, same 7 columns, needs a mapped copy
  keyed by `magazine_id`, not a plain skip).
- Everything else in the diff was noise: `utf8` → `utf8mb3` (MariaDB 11's own alias rename,
  not a real difference) and `AUTO_INCREMENT` values (expected, live counters).

**`bin/migrate-prod-data.sh`** (added this session) is the resulting migration script — full
per-table data copy (skipping any table that's empty on the source rather than blindly
truncating a stage table that might hold real config, e.g. `switch_flows`), the `ad_sizes_old`
→ `ad_sizes` mapped copy, a `user_groups.accounts_findAccount` re-grant to `SuperUser` (a data
copy would otherwise silently reset it to the column default and disable the Find Account
panel), and an orphan-cleanup pass grounded in `cleanupPublicationRemnants()`'s own known-
garbage chains (ads/partial_ads, flatplan_articletypes, comments, flatplan_files,
flatplan_planner, flatplan_handout/hotlink, packages/package_info, parts — all keyed back to
`publications`). Does **not** touch `pageinfo`'s orphans — its FK shape (an `issue`+`code`
string pair, not a numeric id) doesn't fit that pattern, deliberately left as report-only.
**Run to completion 2026-08-26/27, on a disposable clone pair (trk-stage/trk-source), and fully
verified working** — login, real publisher/magazine/publication listings, Flatplan/Pages
rendering (Full workflow, multiple proofing stages), and Resize-workflow image-pack downloads all
confirmed against real migrated production data. **See
[PRODUCTION_MIGRATION_RUNBOOK.md](PRODUCTION_MIGRATION_RUNBOOK.md) for the full distilled
procedure, gotchas, and verification steps** — that document supersedes this section and the
older `production_release_plan` memory as the authoritative migration reference; the two
highlights worth knowing even if you never open that file:

1. **`pub_id=0`/`publication_id=0` is a deliberate sentinel** (magazine-level Parts templates,
   unassigned packages — same convention as `magazines.publisher_id=0` for Adhoc), **not an
   orphan**. The script above already excludes it from every cleanup check, but this is a
   codebase-wide convention worth knowing before writing *any* new query against these tables.
2. **`client/xml/` (per-issue PMD XML + master `pmd.xml`) must be migrated too, in full** — it's
   only ~3.4MB, but the Parts & Color panel and Workflow/PageNumbering resolution read from it,
   not reliably from the DB. A DB-only migration looks complete and isn't.

**Job file simulation, not a full copy**: trk-source's `client/` media directory is ~356GB
(confirmed 2026-08-26: 201G packages, 112G assets, 18G uploads, 15G temp, 5.6G advertisements,
4.3G handout, 594M labor); trk-stage has only ~36G free. A real cutover needs its own plan for
this (this document's original "what was left behind" section already flagged that full
production media migration "cannot skip" the exclusions the dev rebuild took for granted) - for
this preflight, only a representative sample of real jobs' files (spanning Full-workflow
finished/live/archived-with-ads and Resize-workflow image packs) was copied for real; the runbook
above documents the on-disk path conventions discovered for each, so a future full migration
knows what it's actually copying rather than rediscovering the directory layout from scratch.

## Full database schema

See `db/schema.sql` in this repo — a `mariadb-dump --no-data` snapshot taken directly from
trkdev2's live database at the time this document was written. Row counts as of the same
moment (post-prune, clean-base state):

| Table | Rows | Table | Rows |
|---|---|---|---|
| accounts | 3 | jobs_pageinfo | 0 |
| action_log | 22761 | magazines | 1 |
| adhoc_hotlinks | 0 | marquard_calendar | 0 |
| ads | 0 | packages | 0 |
| ad_hoc | 0 | package_info | 0 |
| ad_hoc_infobox | 0 | pageinfo | 0 |
| ad_hoc_users | 0 | partial_ads | 0 |
| ad_sizes_old | 287 | parts | 0 |
| article_colors | 128 | publications | 0 |
| assets | 0 | publishers | 2 |
| calendar_counters | 0 | switch_flows | 2 |
| calendar_events | 0 | switch_sync_queue | 0 |
| calendar_groups | 0 | system_log | 35 |
| calendar_post | 0 | tasklist | 0 |
| calendar_reminder | 0 | tracker_settings | 15 |
| calendar_settings | 0 | userLogSettings | 154 |
| comments | 0 | user_groups | 15 |
| comment_log | 0 | user_log | 45466 |
| deliver_table | 0 | | |
| error_log | 0 | | |
| filetransfer | 0 | | |
| filetransfer_log | 0 | | |
| flatplan_articletypes | 28 | | |
| flatplan_files | 0 | | |
| flatplan_handout | 0 | | |
| flatplan_handout_hotlink | 0 | | |
| flatplan_planner | 0 | | |
| handout_log | 0 | | |
| hotlinks | 0 | | |
| hotlinks_log | 0 | | |
| image_map | 0 | | |

`action_log`/`user_log`/`system_log` being non-trivial while everything else is near-zero
is expected — those are the audit tables that were deliberately preserved through the prune.

## Publication ownership model: Regular vs. Adhoc, and the "clientless" case

Clarified directly by the project owner 2026-07-26, while tracing a stuck/undeletable
clientless Adhoc job and an inconsistent `client` field across the Switch integration. Read
this before touching anything that resolves or forwards a publication's client/publisher —
it's a short, load-bearing summary of axis A from the "Domain model" section above, plus the
one rule that section didn't spell out.

- **A.** Every job (`magazines` row) is either **Regular** or **Adhoc** (`magazines.type`).
- **B.** **Regular** publications always have a Client (a registered `publishers` row) and
  must have **Issues** (`publications` rows) — there's no clientless or issue-less Regular job.
- **C.** **Adhoc** publications can be associated with a **Registered** client (a known
  `publishers` row, recorded on `publications.owner` since `magazines.publisher_id` is always
  `"0"` for Adhoc by convention — see `resolveJobPublisherName()` in
  `client/engine/switchAPI.php`), or they can be genuinely **clientless**. There is no third
  option — see D.
- **D.** For a clientless Adhoc job, **the `client` field in every communication to Switch
  must be empty** — never a placeholder, never a free-typed name. Switch has its own
  hardwired, designated folder on the file server for clientless jobs, keyed off that empty
  value; sending anything else risks the job's files landing in the wrong place, or not being
  picked up by that flow at all.

This is why the free-text "Client" input that used to appear under the "Ad-hoc" radio in the
New Adhoc Publication dialog was removed 2026-07-26 — it made it possible to type a client
name for a nominally clientless job, which would have leaked into some but not all of the
three Switch touchpoints a creation triggers (the bulk PMD sync via `changeXmlDatabase()` →
`XMLUpload2()`, the `publication_created` event in `pubsApply.php`, and the per-issue snapshot
upload in `toSwitch()`'s `new_publication` case) inconsistently — some would carry the typed
name, the snapshot would always say empty regardless. A clientless Adhoc job's only two
possible client states now, app-wide, are "a Registered client" or `""` — never free text.
