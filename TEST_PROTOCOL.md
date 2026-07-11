# Publication lifecycle test protocol

Systematic verification that every publication-related settings change is
correctly written to **MariaDB** (ground truth), reflected in the
**XML** (`pmd.xml`'s `<Item>` for the publication, and/or the per-issue
`{magCode}_{issueCode}.xml` snapshot), and **sent to Switch** — in that
order, since XML and Switch both derive from the DB, never the reverse.

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
  regenerates it).
- **Switch**: check the JSON response captured server-side (every
  `SwitchSend*` call returns `{"status":true,"jobId":"..."}` on
  success), and independently confirm on Switch's own side (job
  history) since that's the only true confirmation Switch received it.

## Test cases

### 1. Publication creation
**Endpoint**: `pubsApply.php?sub=create`
- **DB**: new row in `magazines` (Regular) or `publications` (Adhoc, `publisher_id='0'`).
- **XML**: new `<Item>` in `pmd.xml` with `Code`, `Publisher`, `Client` (both equal), and all workflow fields from the form.
- **Switch**: whole-PMD upload (`xml_data` event, `_DEV`-suffixed filename) via `changeXmlDatabase()`'s `XMLUpload2()` call, **and** a `publication_created` command event via the direct `SwitchSend()` call at the end of the handler.
- **Known risk**: `changeXmlDatabase`'s `'add'` case now de-dupes by `Code` (fixed) - confirm no duplicate `<Item>` appears if you reuse a code.

### 2. Publication property change (workflow type, image enhancement, etc.)
**Endpoint**: `pubsApply.php?sub=workflow`
- **DB**: `magazines.name` (if changed); no other columns touched by this handler.
- **XML**: matching `<Item>` in `pmd.xml` updated for every submitted field except the `$deny` list (`ApprovedComments`, `Uploadable`, `Deadline`, etc. - check `changeXmlDatabase()`'s `$deny` array for the full list before assuming a field should appear).
- **Switch**: whole-PMD upload via `changeXmlDatabase()` → `XMLUpload2()`.
- **Known risk**: if you change the **Client** field in this form, the handler takes a completely different, currently-non-functional branch (see below, case 2b) instead of a normal modify - confirm you're testing a same-client edit here.

### 2b. Publication client change (separate from 2 - expected to not work yet)
**Endpoint**: `pubsApply.php?sub=workflow`, else-branch
- **DB**: `magazines.clientChange` set to `'1'`; `publisher_id` is **not** updated by this code path at all, even after Switch confirms.
- **XML**: not touched.
- **Switch**: a `client_change` notification is sent, but the confirmation callback (`client_change-handler.php`) only flips `clientChange` to `2`/`3` - never completes the change.
- This is confirmed non-functional (per earlier discussion) and deliberately out of scope for this protocol - included here only so a client-change attempt during testing isn't mistaken for a bug in something else.

### 3. New issue creation (Regular publication)
**Endpoint**: `pubsApply.php?sub=newIssue`
- **DB**: new row in `publications` (`magazine_id`, `code`, `deadline`, `pages`, etc.), new rows in `parts`.
- **XML**: new `client/xml/{magCode}_{issueCode}.xml` snapshot (`code`, `issueCode`, `deadline`, `status`, `parts`, and now `client` per the recent fix).
- **Switch**: the per-issue snapshot upload via `toSwitch('new_publication', ...)`, **and** a separate `issue_created` command event via `SwitchSend()`.
- **Note**: `pmd.xml` itself is not touched by issue creation - only the magazine-level `<Item>` changes, issues don't get their own `<Item>`.

### 3b. New Adhoc publication (parallel to 3, different trigger)
**Endpoint**: `pubsApply.php?sub=create` with `Type=Adhoc`
- **DB**: new `publications` row (`publisher_id='0'` if unknown client, or a real `owner`/`user` if known), possibly a new temp `accounts` row (unknown-client case) and an `adhoc_hotlinks` row.
- **XML**: new `<Item>` in `pmd.xml` (Adhoc publications get a magazine-level Item too, via `changeXmlDatabase('add', ...)`'s Adhoc branch).
- **Switch**: whole-PMD upload, plus `SwitchSend_TESZT()` via `toSwitch('new_publication', ...)` for the per-issue snapshot.

### 4. Issue property change (e.g. a part's color standard)
**Endpoint**: `pubsApply.php?sub=modIssue`
- **DB**: `publications` row updated (`pages`, `uploadable`, `deadline`, `enhance`, `specificName`); `parts` rows for this `pub_id` deleted and re-inserted with new values.
- **XML**: `{magCode}_{issueCode}.xml` regenerated via `toSwitch('new_publication', ...)` - confirm the new part color/size appears.
- **Switch**: per-issue snapshot re-upload.

### 5. Stopping an issue
**Endpoint**: `issueManagementAjax.php?op=stopIssue`
- **DB**: `publications.status='stopped'`; `action_log` entry (`stoppedIssue`).
- **XML**: `{magCode}_{issueCode}.xml`'s `<status>` updated to `stopped` via `changeIssueStatus()`.
- **Switch**: re-upload via `changeIssueStatus()` → `toSwitch()`.
- **Known risk**: `changeIssueStatus()` does `simplexml_load_file()` on the per-issue snapshot **without checking it exists first** - if that file is missing (e.g. deleted by a cleanup pass, or never created), this will fatal with a PHP 8 TypeError on `false->status = $value`. Worth testing deliberately: delete the snapshot file, then try to stop the issue, and confirm this actually happens.

### 6. Re-starting an issue
**Endpoint**: `issueManagementAjax.php?op=restartIssue`
- **DB**: `publications.status` recomputed (`active` if the issue has `ads` or `packages` rows, else `created`).
- **XML**: same `changeIssueStatus()` mechanism as stopping - same missing-file risk applies.
- **Switch**: re-upload.

### 7. Approving an issue
**Endpoint**: `issueManagementAjax.php?op=approveIssue`
- **DB**: `publications.status='approved'`; `action_log` entry (`approvedIssue`).
- **XML**: same `changeIssueStatus()` mechanism.
- **Switch**: re-upload.
- **Side effect**: `invoicingTESZT()` builds and emails a billing summary of approved ads for this issue - not a DB/XML/Switch consistency concern, but confirm it doesn't error even with no ads present (it will run against test data with zero approved ads).

### 8. Deleting an issue
**Endpoint**: `issueManagementAjax.php?op=deleteIssue`
- **DB**: `publications.removing='1'` (two-phase delete flag - the row itself isn't deleted yet).
- **XML**: **not** touched by this request - the per-issue snapshot still shows the pre-delete state until Switch confirms.
- **Switch**: `delete_issue` event sent directly via `SwitchSend_TESZT()` (not through `toSwitch()`, so no snapshot re-upload here).
- **Completion**: once Switch's callback (`delete_issue_results-handler.php`) confirms, `cleanupPublicationRemnants()` runs and the `publications` row is actually deleted. Verify the full two-phase flow completes, not just that the flag gets set - check the row is really gone afterward, not just flagged.

### 9. Deleting a publication
**Endpoint**: `issueManagementAjax.php?op=delMagazine`
- **DB**: `magazines.removing='1'`.
- **XML**: not touched by this request (same two-phase pattern as issue delete) - the `<Item>` stays in `pmd.xml` until confirmed.
- **Switch**: `delete_publication` event via `SwitchSend_TESZT()`.
- **Completion**: via `delete_publication_results-handler.php` → `cleanupPublicationRemnants()` per-issue, then the magazine row itself. Verify the `<Item>` is actually removed from `pmd.xml` afterward (this needs `changeXmlDatabase('delete', ...)` or equivalent to fire - confirm it actually does; this is exactly the kind of gap the whole `_DEV`-suffix/upload investigation surfaced elsewhere).

### 10. Archiving an issue (not in your list, but the same family - found while tracing the others)
**Endpoint**: `issueManagementAjax.php?op=archiveIssue`
- **DB**: `publications.status='archiving'`; `action_log` entry (`archivingIssue`). A separate cron-driven timeout (`package_reader.php`) flips this to `archive_failed` after 5 hours if archiving never completes.
- **XML**: **not touched at all** - no call to `changeIssueStatus()` or `toSwitch()` in this handler. The per-issue snapshot will show stale status indefinitely.
- **Switch**: `archive` event sent via `SwitchSend_TESZT()`.
- **This looks like a real gap**, not just an untested case - flagging for a decision on whether it should also sync the snapshot, consistent with stop/restart/approve.

## Summary of drift risks found while building this protocol (not yet fixed, pending your call)

1. **`archiveIssue` never updates the per-issue XML snapshot** - the only one of stop/restart/approve/archive that doesn't.
2. **`changeIssueStatus()` has no existence check** before loading the per-issue snapshot - a missing file (plausible, given how easily these have gone missing already) turns stop/restart/approve into a hard PHP 8 fatal instead of a handled error.
3. **Publication deletion's `<Item>` removal from `pmd.xml` is unverified** - worth confirming case 9 actually cleans it up, since the two-phase delete pattern here is the same one that had the Switch-upload gating bug fixed earlier.
