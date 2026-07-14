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
behalf of Colorcom and TestCo - this was a deliberate decision, not a regression or a gap,
and it lines up with (rather than duplicates) the application-level allowlist already
enforced by `switchClientAllowed()`/`switchBulkSyncAllowed()` (see below): both the network
rule and the app-level `TRKDEV_ENVIRONMENT` gate now independently restrict the same two
test clients, rather than the network layer blocking everything and the app layer being
the only thing narrowing it down. Do not widen either layer without checking with the
project owner first.

(An earlier version of this document briefly - and incorrectly - described this as an
unexplained safety-boundary regression, written before being told about the intentional
partial lift; corrected here once that context was provided.)

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
`SwitchAnyagSend`, `SwitchSend`, `SwitchSend_Rename` — all in `switchAPI.php`, all still
purely synchronous with no queue fallback), the template above is the one to copy.

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
or some code paths will silently keep using a stale key. All three are tracked in git
(the key is committed in plaintext, same as the previous keys were) — worth reconsidering
if this app ever gets a real secrets-management setup, but not changed here since it
matches how every prior key in this repo's history was already handled.

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
was deployed). Plus the new `switch_sync_worker.php`. See `crontab -l` as root for the
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
