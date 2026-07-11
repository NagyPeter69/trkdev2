# Publication lifecycle test protocol

Systematic verification that every publication-related settings change is
correctly written to **MariaDB** (ground truth), reflected in the
**XML** (`pmd.xml`'s `<Item>` for the publication, and/or the per-issue
`{magCode}_{issueCode}.xml` snapshot), and **sent to Switch** — in that
order, since XML and Switch both derive from the DB, never the reverse.

**Status: executed 2026-07-11, all 10 cases + 3b, against real authenticated
HTTP requests (not isolated function calls).** 9 genuine bugs found and
fixed along the way — several only surfaced from actually driving the
endpoints, not from reading the code. See "Findings and fixes" at the
bottom for the full list with commit references. All cases pass as of
`f0d7dca`.

Each test case below identifies the real endpoint (found by reading the
code, not guessed), what each of the three layers should show afterward,
and the exact checks to run. Run them in the numbered order — several
later cases depend on state created by earlier ones (an issue to modify,
a publication to delete).

## How to verify each layer

- **MariaDB**: query the relevant table directly.
- **XML**: read `client/xml/pmd.xml` for the `<Item>` matching the
  publication's `Code`, and/or `client/xml/{magCode}_{issueCode}.xml`
  for the per-issue snapshot (gitignored, regenerated on every issue
  change - check it right after the action, before something else
  regenerates it). Adhoc publications use just `{code}.xml`, no
  underscore.
- **Switch**: check the JSON response captured server-side (every
  `SwitchSend*` call returns `{"status":true,"jobId":"..."}` on
  success), and independently confirm on Switch's own side (job
  history) since that's the only true confirmation Switch received it.

## Testing via real HTTP, not CLI reproduction

Isolated CLI calls (`php -r '...'`) are useful for narrow debugging but
gave at least one false positive during this run: `IS_DEV_ENVIRONMENT` is
set via a php-fpm pool env var, so a bare CLI script silently runs with
the DEV gate *disabled* unless `TRKDEV_ENVIRONMENT` is explicitly
exported first - a "success" from a CLI test doesn't confirm the gate
logic actually works. Drive real endpoints over HTTP with an authenticated
session cookie (login via `client/index2.php`, capture the CSRF token
and session cookie, reuse the cookie jar for subsequent requests) to get
a result that actually reflects what the app does.

Two infrastructure notes if requests fail before you even get to the
PHP logic:
- `opcache.enable` is On - a code change requires at least
  `systemctl reload php8.4-fpm` (a *restart* is the reliable one; reload
  didn't always pick up changes during this run) before it takes effect
  through the web path, even though a fresh CLI process always recompiles.
- nginx's `fastcgi_buffer_size`/`fastcgi_buffers` were raised to 16k
  (`/etc/nginx/sites-enabled/trkdev`) - this codebase's verbose legacy
  warning output was large enough to trip "upstream sent too big header"
  on endpoints that touch `SwitchSend_TESZT()`'s own debug logging,
  blocking those requests entirely until raised from the platform default
  (~4-8k).

## Test cases

### 1. Publication creation — ✅ verified
**Endpoint**: `pubsApply.php?sub=create`
- **DB**: new row in `magazines` (Regular) or `publications` (Adhoc, `publisher_id='0'`).
- **XML**: new `<Item>` in `pmd.xml` with `Code`, `Publisher`, `Client` (both equal), and all workflow fields from the form.
- **Switch**: whole-PMD upload (`xml_data` event, `_DEV`-suffixed filename) via `changeXmlDatabase()`'s `XMLUpload2()` call, **and** a `publication_created` command event via the direct `SwitchSend()` call at the end of the handler.
- Passed cleanly on the first real attempt. `changeXmlDatabase`'s `'add'` case de-dupe-by-`Code` fix confirmed no duplicate `<Item>` when a code was reused.

### 2. Publication property change (workflow type, image enhancement, etc.) — ✅ verified
**Endpoint**: `pubsApply.php?sub=workflow`
- **DB**: `magazines.name` (if changed); no other columns touched by this handler.
- **XML**: matching `<Item>` in `pmd.xml` updated for every submitted field except the `$deny` list (`ApprovedComments`, `Uploadable`, `Deadline`, etc. - check `changeXmlDatabase()`'s `$deny` array for the full list before assuming a field should appear).
- **Switch**: whole-PMD upload via `changeXmlDatabase()` → `XMLUpload2()`.
- **Wire format gotcha, not a bug**: this handler does `parse_str($_POST['settings'], $_POST)`, so it needs the whole form serialized into a single `settings=` POST parameter, not flat top-level fields - sending flat fields makes `parse_str` clobber `$_POST` with an empty result (parsing an absent key), which looks like every field went missing. Matches `menuApply()`'s `#issueparts`-triggered JS branch.
- If you change the **Client** field in this form, the handler takes a completely different, currently-non-functional branch (case 2b) instead of a normal modify - confirm you're testing a same-client edit here.

### 2b. Publication client change (separate from 2 - confirmed non-functional, out of scope)
**Endpoint**: `pubsApply.php?sub=workflow`, else-branch
- **DB**: `magazines.clientChange` set to `'1'`; `publisher_id` is **not** updated by this code path at all, even after Switch confirms.
- **XML**: not touched.
- **Switch**: a `client_change` notification is sent, but the confirmation callback (`client_change-handler.php`) only flips `clientChange` to `2`/`3` - never completes the change.
- Confirmed non-functional per prior discussion and deliberately out of scope for this protocol - included only so a client-change attempt during testing isn't mistaken for a bug in something else. Not exercised this run.

### 3. New issue creation (Regular publication) — ✅ verified (1 bug found + fixed)
**Endpoint**: `pubsApply.php?sub=newIssue`
- **DB**: new row in `publications` (`magazine_id`, `code`, `deadline`, `pages`, etc.), new rows in `parts`.
- **XML**: new `client/xml/{magCode}_{issueCode}.xml` snapshot (`code`, `issueCode`, `deadline`, `status`, `parts`, `client`).
- **Switch**: the per-issue snapshot upload via `toSwitch('new_publication', ...)`, **and** a separate `issue_created` command event via `SwitchSend()`.
- `pmd.xml` itself is not touched by issue creation - only the magazine-level `<Item>` changes, issues don't get their own `<Item>`. Confirmed.
- **Found**: a Regular magazine with no parts template configured yet (via `jobsettings.php`) submits no `type[]` at all - not an empty array, genuinely absent - and `count($_POST["type"])` is a PHP 8 TypeError. Fixed with `?? array()`.

### 3b. New Adhoc publication — ✅ verified (2 bugs found + fixed, both in the DEV gate)
**Endpoint**: `pubsApply.php?sub=create` with `Type=Adhoc`
- **DB**: new `publications` row (`publisher_id='0'` always, regardless of known/unknown client - the real publisher for a *known* client lives on `publications.owner` instead), possibly a new temp `accounts` row (unknown-client case) and an `adhoc_hotlinks` row.
- **XML**: new `<Item>` in `pmd.xml` (Adhoc publications get a magazine-level Item too, via `changeXmlDatabase('add', ...)`'s Adhoc branch); per-issue snapshot named just `{code}.xml`, no underscore.
- **Switch**: whole-PMD upload, plus the per-issue snapshot upload via `toSwitch('new_publication', ...)`.
- **Found (bug 1)**: a bare Adhoc submission (no pre-existing parts config) hit the same unguarded `count($_POST["parttype"])` as case 3's `type[]` issue, in `sub=create`'s own Adhoc branch. Fixed the same way.
- **Found (bug 2, the significant one)**: a *known*-client Adhoc job (`ClientType=known`, e.g. Colorcom) got blocked by the DEV gate exactly like a genuinely unknown one, because `switchClientAllowed()`, `switchBulkSyncAllowed()`, and the per-issue snapshot's `client` field all resolved publisher via `magazines.publisher_id` only - which is always `'0'` for *every* Adhoc magazine regardless of known/unknown. `switchBulkSyncAllowed()`'s version of this was worse than a block: it `continue`d past any magazine with empty/0 `publisher_id`, so Adhoc magazines were silently exempt from the whole-dataset safety check entirely, not blocked - unverified Adhoc data could ride along in a bulk upload. Fixed with a shared `resolveJobPublisherName()` that falls back to `publications.owner` when `publisher_id` is empty. Genuinely unknown-client Adhoc jobs (no owner either) correctly remain unresolvable and blocked - there's nothing in MariaDB to verify them against, so they can only be exercised in production.

### 4. Issue property change (e.g. a part's color standard) — ✅ verified (1 bug found + fixed)
**Endpoint**: `pubsApply.php?sub=modIssue`
- **DB**: `publications` row updated (`pages`, `uploadable`, `deadline`, `enhance`, `specificName`); `parts` rows for this `pub_id` deleted and re-inserted with new values.
- **XML**: `{magCode}_{issueCode}.xml` regenerated via `toSwitch('new_publication', ...)` - new part color/size confirmed present.
- **Switch**: per-issue snapshot re-upload confirmed.
- **Found**: same unguarded `count($_POST["type"])` pattern as case 3. Fixed. (Also found and fixed the identical pattern in `sub=color`'s two branches while sweeping for this - not directly exercised by this protocol, but the same class of bug in a sibling "Parts & Color" handler.)

### 5. Stopping an issue — ✅ verified (2 bugs found + fixed, the second one was the real blocker for cases 3-10)
**Endpoint**: `issueManagementAjax.php?op=stopIssue`
- **DB**: `publications.status='stopped'`; `action_log` entry (`stoppedIssue`).
- **XML**: `{magCode}_{issueCode}.xml`'s `<status>` updated to `stopped` via `changeIssueStatus()`.
- **Switch**: re-upload via `changeIssueStatus()` → `toSwitch()`.
- **Found (bug 1, exactly as flagged pre-execution)**: `changeIssueStatus()` did `simplexml_load_file()` on the per-issue snapshot with no existence check - deliberately deleted the file and reproduced the fatal (`false->status = $value`, PHP 8 TypeError, not a warning). Fixed: now logs and returns `false` instead of crashing.
- **Found (bug 2, not anticipated, the actual reason per-issue Switch uploads never worked)**: `toSwitch()`'s `'new_publication'` case rebuilds `$array` from scratch right before calling `SwitchSend_TESZT()`, discarding all the rich per-issue data - including the job code - and passing only `{"event":"xml_data"}` as `$datas`. `switchClientAllowed()` therefore always saw an empty code and blocked every per-issue snapshot upload, for any client, silently. This affected every case from here on (3, 3b, 4 had already "passed" only because their DB/XML layers don't depend on this call succeeding). Fixed by including `jobCode` in the rebuilt array. Also added a lowercase `'code'` fallback to `switchClientAllowed()` itself, since `toSwitch()` is the one caller using that casing - necessary but not sufficient without the `toSwitch()` fix.

### 6. Re-starting an issue — ✅ verified
**Endpoint**: `issueManagementAjax.php?op=restartIssue`
- **DB**: `publications.status` recomputed (`active` if the issue has `ads` or `packages` rows, else `created`).
- **XML**: same `changeIssueStatus()` mechanism as stopping.
- **Switch**: re-upload confirmed clean, no errors, benefited from case 5's fixes.

### 7. Approving an issue — ✅ verified
**Endpoint**: `issueManagementAjax.php?op=approveIssue`
- **DB**: `publications.status='approved'`; `action_log` entry (`approvedIssue`).
- **XML**: same `changeIssueStatus()` mechanism.
- **Switch**: re-upload confirmed clean.
- **Side effect**: `invoicingTESZT()` builds and emails a billing summary of approved ads - ran cleanly against test data with zero approved ads, no errors.

### 8. Deleting an issue — ✅ verified (1 bug found + fixed)
**Endpoint**: `issueManagementAjax.php?op=deleteIssue`
- **DB**: `publications.removing='1'` (two-phase delete flag), confirmed the row is genuinely deleted (not just flagged) once Switch's confirmation is simulated.
- **XML**: not touched by the initial request, as expected.
- **Switch**: `delete_issue` event sent directly via `SwitchSend_TESZT()` (not through `toSwitch()`, so no snapshot re-upload here - confirmed).
- **Completion**: simulated Switch's `delete_issue_results-handler.php` callback directly (real Switch calling back to this box isn't something testable from here). `cleanupPublicationRemnants()` ran, `parts` cleaned up, `action_log` entry recorded, row genuinely gone.
- **Found**: the per-issue XML snapshot (`{magCode}_{issueCode}.xml`) was left orphaned on disk - `cleanupPublicationRemnants()` cleaned every DB row and asset directory except this one file, which is the one thing actually sent to Switch on every change. Fixed - handles both the Regular (`magCode_issueCode.xml`) and Adhoc (`code.xml`) naming conventions, since this function is shared by both the single-issue and whole-publication delete paths.

### 9. Deleting a publication — ✅ verified
**Endpoint**: `issueManagementAjax.php?op=delMagazine`
- **DB**: `magazines.removing='1'`, confirmed the row is genuinely deleted once Switch's confirmation is simulated.
- **XML**: not touched by the initial request, as expected.
- **Switch**: `delete_publication` event via `SwitchSend_TESZT()`.
- **Completion**: simulated `delete_publication_results-handler.php`'s callback. Confirmed the previously-unverified concern directly: the `<Item>` **is** correctly removed from `pmd.xml` (via `changeXmlDatabase('delete', ...)`, called from within this handler), `showMagazines` correctly spliced for all affected accounts, whole-PMD re-upload succeeded with no gate block.

### 10. Archiving an issue — ✅ verified (1 bug found + fixed)
**Endpoint**: `issueManagementAjax.php?op=archiveIssue`
- **DB**: `publications.status='archiving'`; `action_log` entry (`archivingIssue`). A separate cron-driven timeout (`package_reader.php`) flips this to `archive_failed` after 5 hours if archiving never completes.
- **XML**: confirmed the pre-execution suspicion was correct - this handler never called `changeIssueStatus()`/`toSwitch()`, so the snapshot's `<status>` stayed stale indefinitely after a real archive.
- **Switch**: `archive` event sent via `SwitchSend_TESZT()`.
- **Found**: exactly the gap flagged before execution. Fixed by adding the same `changeIssueStatus()` call stop/restart/approve already use. Verified: DB and snapshot both now show `archiving`, re-upload succeeds.

## Findings and fixes (chronological, with commits)

Nine bugs found by actually running the protocol, not by reading code -
several only reproduce with data shapes (zero pre-configured parts, a
missing snapshot file, a known-client Adhoc job) that a narrower test
wouldn't have hit.

1. **Six unguarded `count($_POST[...])` crashes** (PHP 8 TypeError on a
   missing form field, same class as bugs fixed earlier in this
   engagement, new instances): `sub=newIssue`, `sub=modIssue`, `sub=color`
   (×2 branches), `sub=create`'s Adhoc branch. — `f99e962`
2. **nginx `fastcgi_buffer_size`/`fastcgi_buffers`** too small for this
   codebase's verbose warning output, causing 502s on endpoints that hit
   `SwitchSend_TESZT()`'s debug logging. Raised to 16k
   (`/etc/nginx/sites-enabled/trkdev`, not tracked in this repo).
3. **`changeIssueStatus()` missing-file fatal** - no existence check
   before `simplexml_load_file()`. — `f99e962`
4. **The real reason per-issue Switch uploads always failed**:
   `toSwitch()` discarded the job code when rebuilding its metadata array
   right before the `SwitchSend_TESZT()` call; `switchClientAllowed()`
   also didn't check the lowercase `code` key that call actually uses.
   Both required together. — `f99e962`
5. **`archiveIssue` never synced the per-issue snapshot status**, unlike
   stop/restart/approve. — `bb7e58a`
6. **Orphaned per-issue XML snapshot after delete** -
   `cleanupPublicationRemnants()` missed the one file actually sent to
   Switch. — `c362251`
7. **The DEV gate's Adhoc blind spot**: `switchClientAllowed()`,
   `switchBulkSyncAllowed()`, and the per-issue snapshot's `client` field
   all resolved publisher via `magazines.publisher_id` only, which is
   always `'0'` for Adhoc regardless of known/unknown client. A known
   Colorcom/TestCo Adhoc job was being treated the same as a genuinely
   unknown one. `switchBulkSyncAllowed()`'s version of this bug was a
   safety gap, not just an over-block: it skipped Adhoc magazines from
   the whole-dataset check entirely rather than blocking them. Fixed with
   `resolveJobPublisherName()`, falling back to `publications.owner`. —
   `f0d7dca`

`.gitignore` was also adjusted twice during this work: once to cover
per-issue snapshots at all, and once more to a whitelist approach
(`client/xml/*` with `pmd.xml`/`re-generateXML.php` excepted) after the
pattern-based version missed Adhoc's `{code}.xml` naming (no underscore).

8. **Adhoc job-code collisions were possible**: `codeGen()`'s recursive
   retry called itself with an undefined parameter name (`$length`
   instead of `$word`), so a retry after a collision silently produced a
   malformed code (wrong letter/digit shape) instead of a valid fresh
   one. Its uniqueness check also only looked at `magazines`, missing
   `publications` (Adhoc rows share one code across both tables). Separately,
   `sub=create`'s Adhoc branch never re-validated the suggested `Code` at
   submission time - `codeGen()` only runs once, display-side, when the
   form panel opens, so a stale suggestion or two concurrent submissions
   could collide silently; the Regular branch already had this check,
   Adhoc didn't. Fixed both, verified: a seeded `rand()` collision test
   confirms `codeGen()`'s retry now produces a correctly-shaped code, and
   submitting a known-colliding `Code` is now rejected with an error
   where it was previously accepted. — `3329ddc`

## Open question: Switch-side snapshot deletion (not resolved from this codebase)

Investigated after a remnant issue XML (`AGV.xml`) was found still present
on Switch's own server despite the associated issue/publication having
been deleted on the Tracker side. Confirmed from this side:

- Tracker-host cleanup is correct: `cleanupPublicationRemnants()` deletes
  the local per-issue snapshot file (finding 6 above already fixed the
  general case).
- The `delete_issue`/`delete_publication` event sent to Switch does carry
  the same `jobCode` that was used when the snapshot was originally
  uploaded, so Switch receives everything it needs to correlate the two.
- There is no code path anywhere in this codebase that explicitly tells
  Switch "delete this previously-uploaded file" - `changeIssueStatus()`
  has a `$value=="remove"` branch but nothing ever calls it with that
  value, so it's dead code, not a real mechanism.

Whether Switch's own flow (external, configured in Enfocus Switch
Designer, not part of this repo) correlates an incoming `delete_*` event
with a previously-uploaded snapshot and removes it is outside what can be
verified or fixed from the Tracker side. If snapshots are meant to be
removed from Switch on delete, that logic - if it doesn't already exist -
would need to be added in the Switch flow itself.
