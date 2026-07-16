# CHANGELOG archive — 2026-04-19 to 2026-05-05

Entries moved out of CHANGELOG.md on 2026-07-03 to keep it under 500 lines. Append-only; do not edit.

## 2026-04-19T18:45Z — Claude
- session: (single-file mode; no sessions/ subfolder yet)
- decisions_added: [D-001, D-002, D-003]
- failures_added: [F-001]
- files_changed:
  - `/opt/moodle/config.php` (live server): `$CFG->debug = 0; $CFG->debugdisplay = 0;`
  - `/var/www/html/public/blocks/edwiseradvancedblock/lib.php` (inside `moodle-app` container): null-guard added on line 131 via `docker cp`.
  - `/opt/moodle/patches/edwiseradvancedblock_lib.php` (live server): **created** — holds the patched plugin source.
  - `/opt/moodle/Dockerfile` (live server): `COPY patches/edwiseradvancedblock_lib.php /var/www/html/public/blocks/edwiseradvancedblock/lib.php` inserted after line 58 (the `block_edwiseradvancedblock.zip` extraction block).
  - `moodle_servers/www.appcanadaemcs.com/config.php` (repo): debug both to `0` (mirror of live).
  - `moodle_servers/www.appcanadaemcs.com/DEPLOYMENT.md` (repo): §2 follow-up marked resolved; new §17 "Post-deploy fix: edwiseradvancedblock file_exists(null) → session outage" added with verification transcript.
  - `Project_notes_folder/PROJECT_NOTES.md` (repo): **created** — single-file mode, backfilled from this session.
  - `Project_notes_folder/CHANGELOG.md` (repo): **created** — this file.
- next: rotate admin/demo passwords (DEPLOYMENT.md §Operational note 1); confirm Vimeo domain locking for `app.canadaemcs.com` (§3); audit Apache logs for other PHP 8.3 deprecations; decide on upstream bug report to Edwiser. See `PROJECT_NOTES.md` → Open Questions.

## 2026-04-19T18:53Z — Claude
- session: single-file mode
- decisions_added: none
- failures_added: none
- files_changed: [Project_notes_folder/CHANGELOG.md]
- next: continuous-update loop confirmed live; subsequent material changes will trigger fresh invocations.

## 2026-04-19T20:11Z — Claude
- session: single-file mode (PROJECT_NOTES.md at 162 lines, well under 500-line split threshold)
- decisions_added: [D-004, D-005]
- failures_added: [F-002, F-003]
- files_changed:
  - Live server (`5.78.190.143`):
    - `/var/www/html/public/blocks/edwiser_site_monitor/` (inside `moodle-app`): **created** from `block_site_monitor.zip` with dir rename.
    - `/var/www/html/public/blocks/edwiserratingreview/` (inside container): **created**.
    - `/var/www/html/public/mod/edwiservideoactivity/`: **created**.
    - `/var/www/html/public/mod/edwiserform/`: **created**.
    - `/var/www/html/public/course/format/edwiservideoformat/`: **created**.
    - `/var/www/html/public/filter/edwiserformlink/`: **created**.
    - `/var/www/html/public/local/edwiserform/` (+ `edwiserformevents_*` subplugins): **created**.
    - `/var/www/html/public/local/edwiserpagebuilder/`: **created**.
    - `/var/www/html/public/local/edwiserreports/`: **created** (renamed from `moodle-local-edwiserreports`).
    - `/var/www/html/public/local/edwisersiteimporter/`: **created**.
    - `/var/www/html/public/local/sitesync/`: **created** (renamed from `local_sitesync`).
    - `/opt/moodle/Dockerfile`: **+70 lines** between line 65 and the `mod_interactivevideo` block, implementing COPY + RUN steps for each of the 11 plugin zips with dir renames where required.
    - `/opt/moodle/deploy/pre-ghost-fix-20260419-2000.sql.gz`: **created** — 13 MB DB backup before any DB writes.
    - DB: `mdl_config_plugins`, `mdl_external_functions`, `mdl_external_services_functions` + plugin tables updated via `admin/cli/upgrade.php --non-interactive`; `block_xp` and `filter_translations` removed via `--purge-missing --run`; 4 orphan `format_remuiformat` external function rows deleted directly.
  - Repo (`/Users/matthews/antigravity/Moodle_servers/`):
    - `moodle_servers/www.appcanadaemcs.com/DEPLOYMENT.md`: **§18 added** — full narrative, install table, execution steps, verification transcript, known follow-up.
    - `Project_notes_folder/PROJECT_NOTES.md`: updated header, Current State, added D-004/D-005/F-002/F-003, expanded Open Questions, fixed SSH key path throughout (moved from `ssh_keys/` to `Resources/ssh_keys/`), expanded File & Directory Map to include `Resources/moodle_plugins/`.
    - `Project_notes_folder/CHANGELOG.md`: this block.
- next: user completes 8-step webservice+token setup (site was previously blocked at step 5 "Add functions"); patch F-003 (`local/edwiserreports/settings.php:197`) when next debug-display re-enable is needed; rotate admin/demo passwords; confirm Vimeo domain locking.

## 2026-04-19T20:35Z — Claude
- session: single-file mode
- decisions_added: none
- failures_added: [F-004, F-005]
- files_changed:
  - `Project_notes_folder/PROJECT_NOTES.md`: Session summary updated to include UX-test pass; added "Session 2026-04-19 (cont.) — End-to-end UX verification via Playwright" accomplishment block; added F-004 (`local_edwiserreports/install.js` 404) and F-005 (`theme_remui/bs4-compat.js` jQuery race) with fix patterns and non-blocking classification.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- verification evidence captured (not in repo but referenced in accomplishments log):
  - `.playwright-mcp/plugins-check.png` — "No plugins require your attention now, All plugins 439"
  - `.playwright-mcp/upgrade-result.png` — `upgrade_noncore() Success (0.44 seconds)`
  - `.playwright-mcp/external-services.png` — External services admin page rendering 3 services
  - `.playwright-mcp/service-functions-schoolconex-api.png` — the originally-broken page now rendering "Add functions to the service 'schoolconex_api'" with no error
  - `.playwright-mcp/add-functions-form.png` — the final form with 855-option Name combobox including the exact function `block_edwiser_site_monitor_get_last_24_hours_usage` from the original error message
- next: user can now complete steps 5-8 of the web-services + token setup (add functions to service, add authorised users, generate token, smoke-test with curl).

## 2026-04-19T23:38Z — Claude
- session: single-file mode
- decisions_added: [D-006]
- failures_added: none
- files_changed:
  - Live server (`5.78.190.143`):
    - `/opt/moodle/deploy/pre-idnumber-20260419-2334.sql.gz`: **created** — 13 MB pre-change DB backup.
    - DB `mdl_course.idnumber`: populated on 55 non-frontpage courses via `UPDATE mdl_course SET idnumber = shortname WHERE id != 1 AND (idnumber = '' OR idnumber IS NULL);` — ROW_COUNT=55. All 55 now have idnumber matching shortname (e.g. MAT01, LAN07, SCI08, CFR04, GEO07, HIS08).
    - Moodle cache purged via `admin/cli/purge_caches.php` as www-data so new idnumbers are visible to the WS API immediately.
  - Repo (`/Users/matthews/antigravity/Moodle_servers/`):
    - `Project_notes_folder/PROJECT_NOTES.md`: (pending in next edit) add D-006 (idnumber = shortname bulk populate), session accomplishments entry, updated Current State.
    - `Project_notes_folder/CHANGELOG.md`: this block.
- verification (performed):
  - DB: `ok=55, miss=0, total=55`.
  - WS: `core_course_get_courses_by_field` with `field=idnumber` resolved all 9 sample codes (MAT01, LAN07, SCI08, CFR04, GEO07, HIS08, ART02, HPE05, SST03) to the correct course, each returning `id`, `shortname`, `idnumber`, `fullname` in a single-course `courses[]` array.
  - Existing token still valid: `bbd004d7f516adf1e41bc7a4a75a8d07`.
- next: user runs their audit endpoint with `dry_run: true` to confirm `missing[]` is empty against SchoolConex's `courses.code` list. If any mismatches surface, correct individually via admin UI Course → Settings → Course ID number.

## 2026-04-20T00:12Z — Claude
- session: single-file mode (PROJECT_NOTES.md at 247 lines, still under 500-line split threshold)
- decisions_added: [D-007]
- failures_added: none
- files_changed:
  - `mdl_role` (live DB): new row id=11, shortname=`courseenroller`, name=`Course Enroller`, archetype=`` (none). Context levels: system + coursecat + course.
  - `mdl_role_capabilities` (live DB): 22 `CAP_ALLOW` rows inserted at system context (contextid=1) for role 11. Full list in D-007.
  - `mdl_role_allow_assign` (live DB): 3 rows inserted allowing role 11 to assign student/teacher/editingteacher.
  - `mdl_user` (live DB): new row id=5, username=`manager`, email=`admin@canadaemcs.com`, auth=`manual`, confirmed=1, password set to `Admin123!` (hashed via `update_internal_user_password`).
  - `mdl_role_assignments` (live DB): new row linking role 11 → user 5 → contextid=1 (system).
  - `/tmp/create_manager_role.php` (on host, container, local tmp): created then removed; not persisted.
  - `Project_notes_folder/PROJECT_NOTES.md`: header Last-updated → 2026-04-20, session summary appended (7), Current State gained "Site-admin users" bullet, D-007 added, Accomplishments Log gained 2026-04-20 entry.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- verification (performed):
  - `has_capability` probe as user 5 at system context: `moodle/user:create`=ALLOW, `enrol/manual:enrol`=ALLOW, `moodle/course:view`=ALLOW, `moodle/course:create`=deny, `moodle/backup:backupcourse`=deny, `moodle/site:config`=deny.
  - `admin/cli/purge_caches.php` run as www-data, returned 0.
- next: user verifies login as `manager` / `Admin123!` on https://app.canadaemcs.com/login/index.php and walks through the course → Participants → Enrol-users flow on any one course to confirm the UX works end-to-end. Rotate admin + demo passwords too (DEPLOYMENT.md §Operational note 1, still open).

## 2026-04-20T01:17Z — Claude
- session: single-file mode (PROJECT_NOTES.md at 288 lines post-edit, still under 500-line split threshold)
- decisions_added: [D-008]
- failures_added: none
- files_changed:
  - `/var/moodledata/lang/en_local/` (live server, moodledata volume): **newly created directory**, 119 component override files written. Each contains only the `$string[...]` keys whose vendor values contained "Moodle", with "Moodle" replaced by "EMCS". Total 561 string overrides across: `moodle.php`, `admin.php`, `backup.php`, `cache.php`, `calendar.php`, `completion.php`, `course.php`, `enrol.php`, `grades.php`, `help.php`, `hub.php`, `install.php`, `langconfig.php`, `message.php`, `mimetypes.php`, `mnet.php`, `moodle_org.php`, `notes.php`, `pagetype.php`, `plugin.php`, `portfolio.php`, `privacy.php`, `question.php`, `repository.php`, `rss.php`, `search.php`, `statistics.php`, `tag.php`, `theme.php`, `tool_*.php` (many), `webservice.php`, `workshop.php`, plus per-plugin files for `block_edwiser_*`, `theme_remui`, `mod_interactivevideo`, etc.
  - `/tmp/rebrand_moodle_to_emcs.php` (host, container, local): **created → executed → removed**. Not persisted. Pattern captured in D-008 for future re-runs.
  - `/tmp/moodle_audit.sh` (host, container, local): **created → executed → removed**. Not persisted.
  - `Project_notes_folder/PROJECT_NOTES.md`: session summary appended (8), Current State gained "Brand rename state" bullet, D-008 added to decisions, Accomplishments Log gained rebrand session block.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- verification (performed):
  - Generator self-probe reported: `Files scanned: 119` (with ≥1 override), `Strings overridden: 561`, `Files written: 119`.
  - Sample `get_string()` post-override: `configdocroot` in `admin` renders as "Defines the path to EMCS Docs …", `configenablemobilewebservice` renders as "Mobile web services are required for the EMCS app. …".
  - `admin/cli/purge_caches.php` run as www-data, returned 0.
  - Playwright browser test as `admin`: home, dashboard, course view (id=294), admin mobile settings each return 0 `Moodle` occurrences in `document.body.innerText`. Titles confirmed: `Home | EMCS`, `Dashboard | EMCS`, `Course: Social Studies Grade 1 | EMCS`, `Mobile settings | Mobile app | Administration | EMCS`.
  - Raw-HTML `Moodle` count on homepage: 119 occurrences, all in CSS classes / JS config / module paths / external doc URLs — not user-visible prose.
- next: user can optionally extend the rebrand by (a) uploading a Canada EMCS logo via `theme_remui` filemanager to replace the graduation-cap navbar icon (see D-008 scope-boundary note — F-005 follow-up mentions this too), (b) self-host docs instead of pointing `$CFG->docroot` at docs.moodle.org (would require building/hosting EMCS-branded docs), or (c) accept current state. Admin/demo password rotation still pending.

## 2026-04-20T06:01Z — Claude
- session: single-file mode (PROJECT_NOTES.md at 288 lines post-previous-edits; CHANGELOG.md under threshold)
- decisions_added: none (reference document — no new decisions or failures, just consolidated lessons)
- failures_added: none
- files_changed:
  - `Project_notes_folder/RUNBOOK_MOODLE_CLONE.md`: **created** — ~400-line, 10-phase step-by-step runbook for cloning Moodle to a new domain. Consolidates all lessons from the 2026-04-18–20 rehome of `app.canadaemcs.com`:
    - Phase 1: VPS provisioning (sizing, OS setup, UFW with ALL THREE 22/80/**443**, DNS, native-MariaDB disable).
    - Phase 2: data transfer (snapshot path + tar-over-ssh fallback).
    - Phase 3: domain re-point (bind-mounted `config.php`, `mdl_config.wwwroot`, cache purge AS WWW-DATA, moodledata chown).
    - Phase 4: TLS via certbot/nginx, `$CFG->sslproxy=true`, container on `127.0.0.1:8888`.
    - Phase 5: verify F-001 patch + ghost-plugin zero + full 855-function external-function walk.
    - Phase 6: WS integration (fresh token gen script pattern, smoke test, **`mdl_course.idnumber = shortname` bulk UPDATE**).
    - Phase 7: optional branding (site name, RemUI config, `en_local` language overrides with reversibility).
    - Phase 8: admin password rotation, demo account suspend/delete, scoped manager user per D-007.
    - Phase 9: Vimeo domain allow-list for Interactive Video.
    - Phase 10: 14-point end-to-end verification checklist + rollback procedure.
  - Includes: cross-reference table of all F-001 through F-005 gotchas ("when will it bite you"), file-path reference table (server + repo), conventions for SSH/DB-password lookup, explicit warnings on known traps (cache purge as root, port 443 firewall, bind-mount config.php vs image-baked, lang override vs core file modification).
  - `Project_notes_folder/CHANGELOG.md`: this block.
- rationale: captures the full rehome playbook in one scannable document so future clones don't rediscover 3–4 hours of debugging. Every problem solved in the 2026-04-19–20 sessions has a corresponding verification step or preventative action in the runbook.
- next: on next actual clone (different tenant / disaster-recovery drill), follow the runbook verbatim and append any new gotchas found. Treat this runbook as the canonical single-source-of-truth for "do another clone" — keep it pruned, not hedged.

## 2026-04-20T14:52Z — Claude
- session: single-file mode
- decisions_added: none
- failures_added: none
- files_changed:
  - Live server (`5.78.190.143`):
    - `/opt/moodle/deploy/pre-pagename-id5-20260420-1452.sql`: **created** — 69-line row dump of `mdl_edw_pages.id=5` pre-change.
    - `mdl_edw_pages.id=5.pagename`: `"Home - Official"` → `"About"`; `pagemodified` refreshed. Edwiser Page Builder page at `/local/edwiserpagebuilder/page.php?id=5` now displays title `About | EMCS` and H1 `About`.
    - Moodle cache purged as www-data.
- verification: `curl` on `/local/edwiserpagebuilder/page.php?id=5` returned HTTP 200 with `<title>About | EMCS</title>` and `<h1 class="h2 header-heading">About</h1>`.

## 2026-04-20T15:08Z — Claude
- session: single-file mode
- decisions_added: none
- failures_added: none
- files_changed:
  - Live server (`5.78.190.143`):
    - `mdl_config.custommenuitems` (id=343): first nav label `Home|/local/edwiserpagebuilder/page.php?id=5` → `About|/local/edwiserpagebuilder/page.php?id=5`. Other four items unchanged (My Courses, Explore, Progress, Help).
    - Moodle cache purged as www-data.
  - Repo:
    - `Project_notes_folder/RUNBOOK_MOODLE_CLONE.md`: added §7.5 "Custom nav menu labels (separate from page names)" to document the 2-step pattern discovered here — Page Builder page rename does NOT cascade to `custommenuitems` labels; both must be updated. Includes gotcha on the secondary `mdl_config_plugins` `custommenuitems` row (unused, don't touch).
    - `Project_notes_folder/CHANGELOG.md`: this block.
- verification: Playwright DOM walk of header nav links returned `[Categories, Home (→/, active), About (→page.php?id=5), My Courses, …]`. Site title remains `Home | EMCS` (correct — that's the *site root* page title, set by `$CFG->fullname` via the RemUI theme, distinct from the Page Builder page title).
- lesson captured: Moodle's nav label and Page Builder page title are independent stores. Renaming a Page Builder page touches `mdl_edw_pages.pagename`; renaming a nav link touches `mdl_config.custommenuitems` (pipe-delimited `Label|URL\nLabel|URL\n…`). Always check both when the user reports a label mismatch.
- next: no outstanding items from this fix. Main project backlog still has admin/demo password rotation, Vimeo domain allow-list, F-003 patch, token IP restriction.

## 2026-05-04T15:10Z — Claude
- session: single-file mode (CHANGELOG under split threshold)
- decisions_added: [D-009]
- failures_added: [F-006]
- files_changed:
  - Live server (`5.78.190.143`):
    - `/opt/moodle/deploy/pre-jquery-fix-20260504-150058.mustache`: **created** — backup of original Edwiser Page Builder template (md5 `6f61ae4c…`).
    - `/opt/moodle/deploy/pre-bs4compat-fix-20260504-150058.{min.js,src.js}`: **created** — backups of original RemUI `bs4-compat.{min.,}js` (md5 `589341d8…`, `5d4079e2…`).
    - `/opt/moodle/deploy/pre-loginfix-20260504-150707.mustache`: **created** — backup of original RemUI `login.mustache` (md5 `ac1401c6…`).
    - `/opt/moodle/deploy/pre-blockcontentfix-20260504-150707.mustache`: **created** — backup of original Edwiser Advanced Block `blockcontent.mustache` (md5 `cbb73588…`).
    - In `moodle-app` container, **modified** five files (and persisted in `/opt/moodle/patches/`):
      - `/var/www/html/public/local/edwiserpagebuilder/templates/layout_require_js.mustache` — removed outer `$(document).ready(function(){…})` wrapper from the `school` `{{#js}}` block (the inner `require(['jquery'], function($){…})` calls already manage DOM-ready). md5 `4860bccb…`.
      - `/var/www/html/public/theme/remui/amd/build/bs4-compat.min.js` — added `"jquery"` to the AMD dependency array and `$` to the factory function parameters (positions 1 in both). md5 `7d90a5b2…`.
      - `/var/www/html/public/theme/remui/amd/src/bs4-compat.js` — added `import $ from 'jquery';` after the existing `Tooltip` import (durability for future `grunt amd` rebuilds). md5 `dbb24e03…`.
      - `/var/www/html/public/theme/remui/templates/login.mustache` — changed `require(['theme_remui/loader'], function(){…})` to `require(['theme_remui/loader','jquery'], function(loader, $){…})`. md5 `d5fc4076…`.
      - `/var/www/html/public/blocks/edwiseradvancedblock/templates/blockcontent.mustache` — wrapped the top-level `$(document).ready(…)` block in `require(['jquery'], function($){…})`. md5 `12ac2c4f…`.
    - `/opt/moodle/patches/{layout_require_js.mustache,login.mustache,edwiseradvancedblock_blockcontent.mustache,theme_remui_bs4-compat.js,theme_remui_bs4-compat.min.js}`: **created** — five patch files persisted for container-rebuild durability.
    - `/opt/moodle/Dockerfile`: appended five `COPY patches/<file> /var/www/html/public/...` directives before the `RUN chown -R www-data:www-data /var/www/html` line. Backup: `Dockerfile.bak-20260504-…`.
    - Moodle cache purged twice (after each round of edits) via `admin/cli/purge_caches.php` as `www-data`.
  - Repo (`/Users/matthews/antigravity/Moodle_servers/`):
    - `moodle_servers/www.appcanadaemcs.com/patches/` (**new directory**) with the same five patched files (mirror for version control). Files: `layout_require_js.mustache`, `login.mustache`, `edwiseradvancedblock_blockcontent.mustache`, `theme_remui_bs4-compat.js`, `theme_remui_bs4-compat.min.js`.
    - `Project_notes_folder/CHANGELOG.md`: this block.
- verification (performed via Playwright Chromium 1217 against `https://app.canadaemcs.com`):
  - **Pre-fix:** every page (`/`, `/login/index.php`, `/my/`, `/course/index.php`, `/local/edwiserpagebuilder/page.php?id=5`) emitted exactly 1 `pageerror`: `TypeError: $ is not a function`. Carousel/slick/font-preconnect side-effects on the home page were never executing.
  - **Post-fix:** all five pages emit **0 `pageerror`** events. `typeof window.jQuery === "function"` (v3.7.1) — note `window.$` remains intentionally `undefined` (Moodle AMD pattern; jQuery is only exposed inside `require(['jquery'],…)` callbacks). Home page now has 4 `<link rel="preconnect" href="https://fonts.googleapis.com">` tags injected by the layout JS (was 0 before — proves the school-template require blocks are now executing).
  - HTTP status all 200 / 303 (login redirects). No 5xx anywhere. Three pre-existing 404s on the About page (`/pix/site/section_{interactive,games,teacher}.png`) are unrelated and cosmetic — see F-006.
- next: user can either upload the three `section_*.png` images or have us strip the missing-image references from `mdl_edw_pages.id=5.pagecontent` (see F-006). Reapply Dockerfile/patches verification on next `docker compose up --build` (the COPY directives ensure the fix survives rebuilds).

## 2026-05-05T21:11Z — Claude
- session: single-file mode
- decisions_added: none
- failures_added: none
- files_changed:
  - Live server (`5.78.190.143`):
    - `/opt/moodle/deploy/pre-rebuild-20260505.log`: **created** — captures BEFORE/AFTER image+container IDs for the `docker compose up -d --build moodle` durability test.
    - `moodle-app` container: **rebuilt** from updated Dockerfile.
      - BEFORE image: `sha256:f26ee1e5fe636c94c763d130e95553799f0fdac66215dd80bc3d98ee9d2a943c`
      - AFTER  image: `sha256:fc30e68b012c38b9ddac734d76e34f66c599b777ee2a5a5cf1aadca05354b7ba`
      - BEFORE container: `c969991473023fc9606b61f4c646998b4cbf98fe78adb192d8d0995b50a55446`
      - AFTER  container: `54c7bc5d2c86af312331aad2bc8e5b4ae41f2972c6818e2cd93622a36dcc8235`
  - Repo (`/Users/matthews/antigravity/Moodle_servers/`):
    - `moodle_servers/www.appcanadaemcs.com/Dockerfile`: **created** — pulled from `5.78.190.143:/opt/moodle/Dockerfile` so the canonical build recipe lives in version control.
    - `moodle_servers/www.appcanadaemcs.com/entrypoint.sh`: **created** — pulled from `5.78.190.143:/opt/moodle/entrypoint.sh`.
    - `.gitignore`: **created** — excludes `.playwright-mcp/`, root-level `*.png`, `Resources/ssh_keys/*` (except `*.pub`), `Resources/moodle_plugins/*.{zip,tar.gz,tgz,pdf}`, `*.bak*`, `*.orig`, `pre-*.{sql,sql.gz,mustache,js}`, `.DS_Store`, etc.
    - `.git/`: **created** — `git init -b main`, then initial commit `4a656ef` (100 files, 15260 lines, 940 KB) with Co-Authored-By Claude. SSH private key (`hetzner_moodle_ed25519`) verified excluded; only `.pub` is tracked. No remote configured / no push.
    - `Project_notes_folder/CHANGELOG.md`: this block.
- verification (performed):
  - **Triple-checksum sanity (pre-rebuild)**: all 5 patched files (`layout_require_js.mustache`, `login.mustache`, `edwiseradvancedblock_blockcontent.mustache`, `theme_remui_bs4-compat.{js,min.js}`) match across local repo `patches/`, `/opt/moodle/patches/`, and live container `/var/www/html/public/...` (md5s `4860bccb…`, `d5fc4076…`, `12ac2c4f…`, `7d90a5b2…`, `dbb24e03…`).
  - **Rebuild durability test**: `docker compose up -d --build moodle` ran in ~40 s. Image ID changed (proves rebuild really happened, not no-op). Post-rebuild md5s of all 5 patched files in the new container match the expected values byte-for-byte — Dockerfile `COPY` directives (lines 167–171) are working.
  - **Production downtime measured**: ~25 s of HTTP 502s during container swap (mariadb + nginx stayed up the whole time). HTTP 200 returned at attempt 5 (~25 s after recreate).
  - **Playwright re-run post-rebuild** (Chromium 1217 against `https://app.canadaemcs.com`): 0 `pageerror` events on `/`, `/login/index.php`, `/my/`, `/course/index.php`, `/local/edwiserpagebuilder/page.php?id=5`. 3 unrelated 404s on About page (`/pix/site/section_*.png`) persist as known cosmetic issue.
  - **Caches purged** post-rebuild as `www-data` (`admin/cli/purge_caches.php` returned 0).
- next: pre-existing About-page 404 `<img>` tags still pending user choice (upload three `section_*.png` files OR strip the `<img>` lines from `mdl_edw_pages.id=5.pagecontent`). Repo is now version-controlled — adding a remote (`git remote add origin …`) is a one-line follow-up if/when desired.

