# Production Migration Runbook — trk-source → trk-stage (and beyond)

Written 2026-08-27, distilled from a full dry-run migration exercise performed against disposable
clone VMs (`trk-stage` = a clone of the rebuilt `trkdev2` codebase, `trk-source` = a clone of the
real, untouched legacy production box). Those clones will be destroyed after this exercise — this
document is what survives them. Read this in full before running a real cutover; it corrects and
extends the older migration plan in [SYSTEM_STATE.md](SYSTEM_STATE.md)'s "Deploying to production"
section and the `production_release_plan` memory, which described the *intent* but hadn't actually
been executed end-to-end until this session.

**Bottom line up front**: the procedure below works. Four real jobs spanning every workflow type
we could test (Full/finished, Full/live-mid-proofing, Full/archived-with-ads, Resize) were migrated
and verified working — login, job listings, Flatplan/Pages rendering, proofing-stage awareness, and
image-pack downloads all confirmed against real production data. Two real bugs were found and fixed
along the way (below) — both are already fixed in `bin/migrate-prod-data.sh`, but re-read the
"Things that will burn you" section before touching a real production dataset, because the *reasons*
matter more than the fixes.

## 0. Terminology, so this document doesn't get confused with itself

- **trk-stage**: the box running the *rebuilt* codebase (PHP 8.4, MariaDB 11.8, schema with the
  cutover-era additions). This is the migration *target*.
- **trk-source**: a clone of the *real, live, untouched* legacy production box (PHP 7.4, MariaDB
  10.5, old schema). This is the migration *source*. Never confuse this clone with the actual box
  it was cloned from — that one is still live and must never be touched by any of this.
- **Real production** (`trk.colorcom.hu`): the actual live system real users depend on right now.
  Nothing in this runbook ever writes to it. If a future session runs this runbook against the real
  boxes instead of clones, replace every IP/hostname below with the real ones *deliberately*, not by
  accident — and get explicit sign-off first, since at that point every action becomes irreversible
  in a way none of this dry run was.

## 1. Before touching anything: safety rails

These are **dry-run/testing safety measures**, not permanent production configuration. If this
runbook is ever followed for the *real* cutover, section 1.1's firewall rules must be treated as
scaffolding to remove before go-live (section 7), not something to leave in place.

### 1.1 Firewall: block Switch and mail during testing

Enfocus Switch integration lives at a hardcoded IP (`engine/switchconstant.php`:
`SWITCHURL`/`SWITCHLOGINURL`, currently `192.168.1.8`) and outbound mail goes to
`MAIL_HOST` (`engine/constans.php`, resolves to a separate IP, e.g. `10.10.30.11` for
`mail.colorcom.hu`). Before any data migration or testing against real production data on a
clone, block both so nothing you do can reach the real Switch instance or send real email:

```nginx
# /etc/nftables.conf — add to the existing output chain
ip daddr 192.168.0.0/16 drop     # Switch's subnet
ip daddr 10.10.30.11 drop        # mail server IP — resolve MAIL_HOST fresh, don't assume this IP
tcp dport { 25, 465, 587 } drop  # generic SMTP, in case a different mail path exists
```

Apply with `nft -f /etc/nftables.conf`, and `systemctl enable nftables.service` for persistence
across reboots (Debian ships `/etc/nftables.conf` as a template but the service is disabled by
default — check `systemctl is-enabled nftables` before assuming it's active).

**Before checking**: confirm this firewall doesn't also block your own SSH management path or
default gateway — `ip route` and check your `SSH_CONNECTION` source IP aren't inside the range
you're about to block.

### 1.2 Hostname rename — four places, not one

If the box is also being renamed as part of this exercise, `hostnamectl set-hostname` alone is
**not enough**. Four places need to agree, or you get a half-renamed system that silently still
identifies as the old name in some contexts:

1. `hostnamectl set-hostname <new-name>` (sets `/etc/hostname` too)
2. `/etc/hosts` — fix the box's own self-entry to the *real* current IP and new name. We found a
   stale entry pointing at a completely different IP than the box's actual interface address on
   both clone VMs — likely left over from an earlier clone/DHCP reassignment. Don't assume the
   existing entry is even pointed at the right IP; check `ip -4 addr show` first.
3. `/etc/nginx/sites-enabled/<site>` — `server_name` directives. Missed this the first pass; the
   app kept serving fine (nginx doesn't require `server_name` to match to serve *a* response) but
   under the stale name.
4. `/var/www/server_constans.php` — **not in git**, per-machine config. `URL` here is the single
   source of truth every outgoing email link in the app is built from (password resets, welcome
   mail, file-transfer links — see SYSTEM_STATE.md's mail-system section). Get this wrong and every
   password-reset email silently links to the wrong host.

### 1.3 nginx `client_max_body_size` — check it's actually set

trkdev2's `/etc/nginx/sites-available/trkdev` had **no `client_max_body_size` directive at all**
(found 2026-09-04, tracked down as the root cause of a large flatplan PDF drag-drop upload
silently vanishing) — nginx's built-in default is 1MB, well below even a single 10MB chunk of
the app's own chunked-upload protocol (`client/filetransfer.php`, `client/flatplan.php`'s
drag-drop-to-slot path), so any file over ~1MB got a 413 before PHP ever ran, with nothing in the
app to notice or report it. Fixed on trkdev2 by adding `client_max_body_size 5000M;` (matching
`upload_max_filesize` in `/etc/php/8.4/fpm/conf.d/99-trkdev.ini`) to both `server {}` blocks. If
production's nginx config was cloned from a similarly bare template rather than carried forward
from the legacy box, it likely has the exact same gap — check `client_max_body_size` is actually
present in whatever nginx config production ends up running, don't assume it's fine because the
old box "worked" (its uploads may have always been silently size-limited too, or its config
differs from trkdev2's in ways this migration hasn't diffed).

## 2. Schema diff — do this for real, don't trust the checklist

SYSTEM_STATE.md's "Known schema deltas vs. production" section was **explicitly flagged as
non-exhaustive and never actually run** as of 2026-08-02. We ran it for the first time this
session and found real gaps the checklist missed:

```bash
# On trk-source:
mysqldump -uroot -proot --no-data --skip-comments --compact nyomadake_intra > source_schema.sql
# On trk-stage:
mysqldump --no-data --skip-comments --compact nyomadake_intra > stage_schema.sql
# Split both into one file per CREATE TABLE and diff each pair - catches column/index/engine
# changes that a whole-file diff buries in noise (utf8→utf8mb3 renaming, AUTO_INCREMENT drift).
```

**Previously undocumented deltas found this pass** (in addition to the already-known
`accounts.pass` width, `remember_token`/`session_token`/`mailOptOut`/`pwset_token`/`pwset_expires`,
`switch_sync_queue`, `user_groups.accounts_findAccount`):

- `accounts.linked_account_id` — links a Temp job-scoped account back to a real registered account.
- `ads.booked_page`, `ads.booked_part`.
- `pageinfo.preflight_error`, `pageinfo.preflight_report`, `pageinfo.preflight_origname`.
- `parts.grayscale`.
- `comments`/`comment_log` switched `MyISAM` → `InnoDB`, `comments` gained real indexes.
- Three new stage-only tables with **no production equivalent and no migration needed** —
  `color_standards` (ICC profile registry), `calendar_holidays`, and **`ad_sizes`**, which
  *looks* new but is actually a renamed/re-keyed successor to production's `ad_sizes_old`
  (identical columns) and **does** need a mapped data copy, unlike the other two.

**Lesson**: never assume a "some deltas are known" checklist is complete. Run the real diff every
time; new columns get added between when someone last wrote it down and when you actually cut over.

## 3. Data migration — `bin/migrate-prod-data.sh`

This script (already in the repo, already fixed per section 3.1 below) does the actual data copy:

```bash
TRKDEV_SOURCE_HOST=<source-ip> \
TRKDEV_SOURCE_DB_USER=root \
TRKDEV_SOURCE_DB_PASS=<password> \
bash bin/migrate-prod-data.sh
```

What it does, in order: backs up trk-stage's current DB first (always, non-negotiable), copies
every business-data table from source (skipping any table that's empty on source, so it never
truncates a stage table that might hold meaningful config it doesn't have an opinion on, e.g.
`switch_flows`), maps `ad_sizes_old` → `ad_sizes`, re-grants `accounts_findAccount` to `SuperUser`
(a straight data copy would silently reset this schema-delta column to its default and disable the
Find Account admin panel), then runs an orphan-cleanup pass.

Full account/publisher/magazine/publication data, `pageinfo`, `packages`, `assets`, `ads` — this
constitutes what production actually needs. **This part of the migration is data-cheap**: a real
production database, even 15+ years deep, is a few tens of MB. It's the *files* (section 4) that
are actually large.

### 3.1 The bug that will bite you: `pub_id=0` is not an orphan

This codebase uses **`0` as a deliberate sentinel value**, not `NULL`, in several places — the
same convention already documented for `magazines.publisher_id=0` meaning "this is an Adhoc job,
not tied to a registered publisher." We found (the hard way, after a user reported "Parts & Color
is empty for every publication") that this same convention applies to:

- **`parts.pub_id=0`**: a **magazine-level template row** (`mag_id` set, no specific issue) —
  read by `client/plugins/pubs/color.php` for every **Regular**-type magazine
  (`sql_aget("parts", "pub_id='0' AND mag_id='...'")`). This is *the* row the "Parts & Color"
  admin panel displays — not the per-issue `parts` rows keyed by a real `publications.id`.
- **`packages.publication_id=0`**: unassigned/general packages — some of these are **still
  actively referenced by real `pageinfo` rows** (confirmed live: 5 of 31 such packages on a real
  production dataset were genuinely in use).

An orphan-cleanup pass that does `DELETE FROM parts WHERE pub_id NOT IN (SELECT id FROM
publications)` **wipes every Regular magazine's Parts & Color definition DB-wide**, since `0`
trivially never matches a real `publications.id`. This is not a hypothetical — it's exactly what
happened on the first migration run this session, and it silently broke the Parts & Color panel
for every single migrated magazine until diagnosed and fixed.

**The fix, already applied in `bin/migrate-prod-data.sh`**: every orphan-cleanup `DELETE` now
explicitly excludes the FK column being `0` before checking it against the parent table:

```sql
DELETE FROM parts WHERE pub_id != 0 AND pub_id NOT IN (SELECT id FROM publications);
```

**If you ever write a new cleanup/audit query against this schema**: grep for `pub_id`,
`publication_id`, `mag_id`, `magazine_id`, `pack_id` columns and check whether `0` shows up as a
real, non-trivial value before assuming it means "unset." It usually means "not tied to one
issue," not "broken."

We confirmed (via a full source-side recount after the fix) that this was the *only* class of
false-positive orphan across all 8 tables the cleanup touches — `ads`, `flatplan_articletypes`,
`flatplan_files`, and `flatplan_planner` genuinely have zero `0`-sentinel rows on this dataset, so
their full wipes during the original run were correct, not bugs. Don't assume every big "N rows
deleted" number is a mistake — verify it against source, the way we did in section 3.2.

### 3.2 How to verify the cleanup pass didn't over-delete

For every table the cleanup touches, compare against source directly rather than trusting the
script's own log output (which showed some confusing numbers due to `information_schema` row-count
*estimates* being used for progress messages, while the actual `DELETE`/`COUNT(*)` results were
exact — don't debug from the estimates):

```sql
-- On source, per table (adjust column name per table):
SELECT COUNT(*) total,
       SUM(pub_id != 0 AND pub_id NOT IN (SELECT id FROM publications)) AS genuinely_orphaned
FROM <table>;
-- Expected final row count on stage = total - genuinely_orphaned. If it doesn't match, something
-- was wrongly excluded or wrongly kept.
```

### 3.3 `package_info` — a real, pre-existing dead table, not a migration artifact

On one dataset, `package_info` went from 82 loaded rows to 0 after cleanup. Verified directly
against source: only 2 of those 82 rows even had a `package_id` matching a real `packages.id`
(the ID ranges don't overlap at all — `package_info.package_id` values sit far below the current
`packages.id` range, suggesting a historical renumbering event), and even those 2 pointed at a
package with `publication_id=0` that was *itself* dead (not one of the legitimate `0`-sentinel
"still in use" packages from 3.1). **This table appears to be ~100% legacy debris already, on
production itself** — not something the migration broke. Don't panic if it ends at 0; do the same
direct-against-source verification before assuming a bug.

## 4. File migration — the gap the DB-only approach completely misses

**This is the single most important lesson from this whole exercise.** A full data migration is
not just a database copy. Two entirely separate categories of on-disk data also need to move, and
missing either one produces confusing, hard-to-diagnose symptoms that look like DB bugs but aren't.

### 4.1 `client/xml/` — small, and absolutely required

**What it is**: per-issue PMD XML files (`client/xml/{MAGCODE}_{ISSUECODE}.xml`) plus the single
master `client/xml/pmd.xml` (`Publications_Master_Data` — every magazine's `Workflow`,
`PageNumbering`, `FlatplanStages`, ad sizes, mail list, etc.). See SYSTEM_STATE.md's PMD section
for the full architectural context (2026-07-27 incident: this file must stay `www-data:www-data`
owned or writes silently fail).

**Why a DB-only migration breaks without it**: the "Parts & Color" admin panel and the
`Workflow`/`PageNumbering` lookups that drive Flatplan's rendering behavior read from **this XML**,
not reliably from the DB. We migrated the full database, confirmed `parts` rows existed and were
correct, and the Parts & Color panel *still* showed empty for a real migrated magazine — because
its per-issue XML file (`IS_2603.xml`) simply didn't exist on the target box. The DB was fine; the
XML was the actual gap.

**The fix is trivial once you know to do it**: this directory is tiny. On this session's real
production dataset it was **3.4MB across 777 files** — negligible compared to the hundreds of GB
of media below. **Copy the entire `client/xml/` directory wholesale, unconditionally, as part of
every migration.** There is no reason to be selective here the way section 4.2 requires for media.

```bash
# Back up the target's current xml/ first (it may have its own dev-only test files worth keeping)
tar -czf /root/db-backups/stage-xml-backup-$(date +%Y%m%d%H%M%S).tar.gz -C client/xml .
ssh root@<source> 'tar -cf - -C /var/www/html/client/xml .' | tar -xf - -C client/xml
chown -R www-data:www-data client/xml
```

### 4.2 Media files (`packages/`, `assets/`, `advertisements/`, `uploads/`) — huge, and must be sampled or fully budgeted

**Real scale observed**: a real production `client/` media tree was **~356GB** total
(`packages/` 201G, `assets/` 112G, `uploads/` 18G, `temp/` 15G, `advertisements/` 5.6G,
`handout/` 4.3G, `labor/` 594M). A disposable test/staging clone will almost never have this much
disk. **A real cutover must budget real disk space for the real total before attempting a full
copy** — measure it fresh with `du -sh client/*/` on the real source, don't assume this document's
numbers still hold.

**For anything short of a full production cutover (staging tests, previews, dry runs)**: sample
specific real jobs rather than attempting a partial/fake copy of everything. We validated four
real jobs this way, each under ~9GB, well inside a 47GB disk's headroom — enough to prove every
code path works without needing the full 356GB. **A job with no files present is not a
crash risk** — we confirmed the app degrades gracefully (renders the page/job listing, just with
placeholder thumbnails) for jobs that were deliberately left file-less. This makes sampling safe:
pick a few real, representative jobs, copy their files for real, and leave the rest DB-only.

**Path conventions, reverse-engineered from `engine/engine.php` and `client/engine/flatplan_ajax.php`
this session** (useful for picking *which* files a given job actually needs — you don't need to
copy a job's entire package tree if you only care about, say, its Flatplan slots vs. its raw asset
library):

- **Page proofing files** (Full/Hybrid workflow, PDF-based):
  `client/packages/{magazineCode}/{issueCode}/{packages.directory}/{stage}/{page:3-digit}_{pack_id}_preview.{pdf,jpg}`
  where `{stage}` is one of `FIN` (finalized), `_PRE` (early proofing round), or presumably a
  `_BASIC`-style folder for the middle round on a 3-stage job (`FlatplanStages=3` — confirmed `FIN`
  and `_PRE` exist as real, distinct folder names on real data; the exact third-stage folder name
  wasn't directly observed this session and should be confirmed against a job that's mid-BASIC
  before assuming the name). Which stage(s) actually have files depends on `pageinfo.fin` (0 vs 1
  observed; check `pageinfo.state` too, not fully characterized this session) — **a page whose
  round hasn't happened yet is correctly empty, this is not a bug**. Cross-check against
  `pageinfo.fin` before assuming a missing thumbnail means a broken copy.
- **Ads**: `client/advertisements/{ads.name}_preview.jpg` (upload-time preview) and
  `client/advertisements/{STRTOUPPER(ads.name)}_{magazineCode}[_{issueCode}]_*` (processed
  PDF/XML/check/thumb files — the issue-code segment is omitted for Adhoc jobs where
  `magazineCode == issueCode`, see `cleanupPublicationRemnants()`'s own comment on this). The raw
  pre-processing upload (`client/uploads/ads/{ads.file_name}`) may legitimately no longer exist for
  an older/archived job — don't treat its absence as an error.
- **Resize-workflow output** (TIFF/PSD image packs, no PDFs, no Flatplan/Pages at all — gated off
  entirely in `client/menu.php` for this `Workflow` value): stored under
  `client/assets/{publications.id}/{parent_asset_id}/{filename}`, **not** under `packages/` at all
  — a Resize job's `pageinfo` and `packages/{code}` directory are both legitimately empty/absent.
  The "Download" page (`client/assets.php`, `engine/assets_ajax.php?op=loadAssets`) is what
  surfaces these — verify via that page, not Flatplan.

### 4.3 Ownership

Everything copied under `client/` needs `www-data:www-data` ownership afterward (`chown -R`) —
files arrive owned by whatever they were on source (often `root:root` from a prior manual `tar`
extraction or backup restore), and PHP-FPM runs as `www-data` with no write access otherwise.

## 5. Verification checklist

Don't just check the DB loaded — walk through the actual application. We used scripted `curl`
requests (log in via POST to get a session cookie, then hit the real AJAX endpoints) to verify
this without needing GUI access, useful if you're doing this from a session with no browser:

1. **Login**: POST `username`/`password`/`csrf_token` (scrape the token from a fresh GET first) to
   `client/index2.php`. A 302 + a subsequent page containing `Logout` confirms success.
2. **Publisher/client list**: the create-magazine page's publisher `<select>` should show every
   real publisher from the migrated `publishers` table, not a stale dev seed.
3. **Admin panel schema-delta check**: confirm any schema-delta-gated menu item (e.g. "Find
   Account", gated by `user_groups.accounts_findAccount`) actually appears — this is a live check
   that post-load fixups like section 3's `SuperUser` re-grant actually took effect, not just that
   the migration script printed success.
4. **Full/Hybrid workflow**: hit `?page=flatplan&id={pub_id}&code={issueCode}` for a job with real
   files copied — 200, no fatal/parse errors, and the job's real page count. Then hit
   `engine/flatplan_ajax.php?op=loadPagePair&...&id={pub_id}` and check for real thumbnail
   `background-image` URLs (not 100% `empty_slot.png` fallback) on pages you know have real FIN
   (or PRE/BASIC) files.
5. **Resize workflow**: hit `?page=assets&id={pub_id}&code={issueCode}` then
   `engine/assets_ajax.php?op=loadAssets&alter=&pub={pub_id}&stripped=0&order=name&orderType=ASC`
   (note: `alter=` empty string, matching `assets.fp=''` — **not** `alter=0`, which matches nothing
   since `fp` is blank-string-valued, not zero-valued, on real data). Confirm real Image Pack names/
   sizes/dates appear, then `op=loadPack&id={a_top_level_asset_id}` to see the pack's real contents,
   then fetch one real file directly (`client/assets/{pub_id}/{asset_id}/{filename}`) and confirm a
   real, correctly-sized download.
6. **A job with no files copied** should still render its listing/Flatplan page without a fatal
   error — confirms the sampling approach (4.2) is safe for the rest of the dataset.
7. **Auto workflow**: not verified this session — flag as an open gap before relying on this
   runbook for an `Auto`-workflow job.

## 6. Things that will burn you (quick-reference)

- `pub_id=0`/`publication_id=0` is a sentinel, not an orphan marker (section 3.1). Check every FK
  column for this convention before writing a cleanup query, not just the ones already known.
- `client/xml/` is easy to forget entirely since it's not the database and it's tiny — but the
  Parts & Color panel and Workflow/PageNumbering resolution depend on it (section 4.1).
- `information_schema.tables.table_rows` is an **estimate** for InnoDB tables, especially
  right after bulk writes. Don't debug row-count discrepancies against it — use real `COUNT(*)`.
- `alter=0` and `alter=` (empty string) are different values to this app's AJAX layer
  (`fp='0'` vs `fp=''`) — a wrong guess here silently returns zero rows with no error, easy to
  mistake for a real bug.
- A job's proofing-stage folder (`FIN` vs `_PRE` vs presumably a BASIC equivalent) genuinely being
  empty is **correct** if that job hasn't reached that round yet on real production — check
  `pageinfo.fin`/`state` before assuming a copy failed.
- Hitting a page-controller PHP file directly (e.g. `client/assets.php`) instead of through
  `index2.php?page=...` will 500 with `Call to undefined function sql_get()` — this app's files
  are not independently bootstrapped, they rely on being `include()`'d by the router.
- `ad_sizes_old` → `ad_sizes` is a rename+re-key, not a 1:1 name match — a naive "copy every table
  with the same name" approach silently skips this one entirely.

## 7. Before this ever goes live for real

- **Remove the section 1.1 firewall rules** — they were testing-only safety rails. Confirm Switch
  and mail connectivity are restored deliberately, not left permanently blocked by accident.
- **No test-password-reset step is needed for a real cutover** — real users' real (legacy MD5 or
  already-upgraded bcrypt) password hashes carry over intact with the rest of `accounts`; the
  password-reset-for-testing done this session was purely a dry-run necessity so *we* could log in
  without knowing real users' credentials, and should not be part of the real procedure.
- **Full media migration timing is unmeasured** — this session only validated the *method* on
  ~13GB across 4 sampled jobs, not the throughput/duration of moving the real ~356GB+ (production
  has had years since this document's numbers were taken; re-measure). Budget a real transfer
  window and verify checksums for a full copy, not just spot-checks.
- **`trk-source`-style clones' own app config still points at the real production hostname**
  (`server_constans.php`: `HOST=Live`, `URL=trk.colorcom.hu` on the clone we tested against) —
  decide deliberately whether any clone reused for a later dry run needs this changed, especially
  before lifting its mail block.
