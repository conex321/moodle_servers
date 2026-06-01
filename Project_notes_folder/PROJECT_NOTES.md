# Project Notes — Moodle Servers (app.canadaemcs.com + source of truth 5.78.128.44)

**Last updated:** 2026-05-28
**Last agent:** Codex
**Session summary:** Production Moodle was reported unreachable on 2026-05-28. DNS still points `app.canadaemcs.com` to Hetzner VPS `5.78.190.143`, but HTTP/HTTPS time out and SSH is not reachable from this workstation; recovery now requires Hetzner/provider console access or updated infrastructure credentials. Prior application-level fixes remain documented but could not be inspected live in this session.
**Notes mode:** single-file

---

## Current State

- **Production target:** `https://app.canadaemcs.com` (Hetzner VPS `5.78.190.143`, Moodle 5.1.3 in Docker, PHP 8.3, MariaDB 10.11) is **unreachable as of 2026-05-28T12:50Z**. DNS still resolves to `5.78.190.143`, but `curl` to `80/tcp` and `443/tcp` times out or refuses at TCP connect, `ssh -i Resources/ssh_keys/hetzner_moodle_ed25519 root@5.78.190.143` fails before authentication (`Connection refused`/timeout on port 22), and no Hetzner/Hostinger/API credential is present locally. The last verified healthy state was 2026-05-25. See F-008.
- **Source-of-truth instance:** `5.78.128.44` (original snapshot source). `app.canadaemcs.com` was rehomed from this snapshot on 2026-04-18–19 (see DEPLOYMENT.md).
- **Debug state:** `$CFG->debug = 0; $CFG->debugdisplay = 0;` on both the live server and the repo mirror. Re-enabling debug display is now safe with respect to F-001, but `local_edwiserreports/settings.php:197` still emits a PHP 8.3 deprecation noted in the DEPLOYMENT.md §18 known-follow-up — don't re-enable debug display on production until that one is also null-guarded.
- **Plugin state on disk:** 14 plugins deliberately installed: `theme_remui`, `block_edwiseradvancedblock` (patched), `block_edwiser_site_monitor`, `block_edwiserratingreview`, `filter_edwiserpbf`, `filter_edwiserformlink`, `course/format/edwiservideoformat`, `local/edwiserform` (with `edwiserformevents_*` subplugins), `local/edwiserpagebuilder`, `local/edwiserreports`, `local/edwisersiteimporter`, `local/sitesync`, `mod/edwiserform`, `mod/edwiservideoactivity`, `mod/interactivevideo`. No ghost plugins remain (`admin/cli/uninstall_plugins.php --show-missing` is empty).
- **Webservice state:** REST protocol enabled. Service `schoolconex_api` (id=3) is restricted to authorised users; 10 WS functions attached (including all 5 user-required: `core_user_create_users`, `core_user_get_users_by_field`, `enrol_manual_enrol_users`, `core_course_get_courses_by_field`, `core_webservice_get_site_info`). User `schoolconex_api` (id=4) is sole authorised user and has role `ws_api_schoolconex` (id=10) at system context with 16 capabilities (covers all 6 user-required + extras for a realistic integration). Permanent token `bbd004d7f516adf1e41bc7a4a75a8d07` is live with no IP restriction and no expiry — **store in Supabase as `MOODLE_WS_TOKEN`**; IP-restrict and/or set expiry when the backend IP is known.
- **Course idnumber state:** all 55 non-frontpage courses have `mdl_course.idnumber` populated equal to their `shortname` (e.g. `MAT01`, `LAN07`, `SCI08`, `CFR04`, `GEO07`, `HIS08`). Verified via `core_course_get_courses_by_field` lookups on 9 sample codes. This is the key SchoolConex's `courses.code` matches against.
- **Outstanding follow-ups still open:** admin/demo/manager passwords not rotated (DEPLOYMENT.md §Operational note 1), Vimeo domain-locking for `app.canadaemcs.com` not confirmed (§3), `local/edwiserreports/settings.php:197` PHP 8.3 deprecation noted but unpatched (§18 known follow-up), and three missing About-page images remain cosmetic F-006.
- **Local rebuild state:** `moodle_servers/www.appcanadaemcs.com/prepare-build-context.sh` prepares the local Docker build context by hardlinking required plugin zips from `Resources/moodle_plugins/` into ignored `moodleplugins/`. `php.ini`, `patches/choicelist_fixed.php`, and `patches/edwiseradvancedblock_lib.php` are now mirrored from `/opt/moodle/`; every Dockerfile `COPY` source resolves locally. `docker compose --progress=plain build moodle` completed successfully on 2026-05-25 and produced `wwwappcanadaemcscom-moodle:latest`; image inspection confirmed Interactive Video, the Edwiser null guard, the choicelist patch, the RemUI patch, and required PHP extensions.
- **Clone runbook:** `Project_notes_folder/RUNBOOK_MOODLE_CLONE.md` is the canonical single-source-of-truth for bringing up another cloned Moodle instance on a new domain. Encodes every lesson from the 2026-04-18–20 sessions plus the 2026-05 rebuild-patch lessons (VPS provisioning, data transfer, domain re-point, TLS, F-001/F-002 verification, WS token generation, `idnumber` bulk populate, optional `en_local` rebrand, admin-hardening, verification checklist, rollback). Update it every time a new rehome surfaces a gotcha not already documented there.
- **Site-admin users (non-API):** admin `admin` / `Schoolx2024!` (needs rotation); demo `demo` / `DemoStudent2026!` (needs rotation); **manager `manager` / `Admin123!` (user id=5, email `admin@canadaemcs.com`)** — assigned the custom `courseenroller` role (id=11) at system context. Can create users + enrol into any course (via manual enrol, with permission to assign student/teacher/editingteacher); cannot create/back-up courses, cannot access site:config. UX caveat: because user creation in Moodle lives under Site administration → Users, the manager sees a **filtered** Site administration link that exposes only `Users → Accounts` (Add a new user, Browse list of users, Bulk user actions). Every other admin subsection is hidden. This is the tightest "no admin panel" achievable while still allowing in-UI user creation. See D-007.
- **Brand rename state:** 561 user-facing `$string[...] = '...Moodle...'` values replaced with `EMCS` across 119 component files via the `en_local` mechanism — overrides live at `/var/moodledata/lang/en_local/*.php`. Every page title, breadcrumb tail, help text, config description, and footer link that previously said "Moodle" now says "EMCS". Remaining "Moodle" tokens in rendered HTML are limited to non-user-visible CSS class names (`.moodle-has-zindex`, `.moodle-actionmenu`), JS config paths (`M.cfg.moodle...`), and internal URLs (`/mod/`, `/course/`, `/lib/requirejs.php/...`) — intentional; changing them would break the application. Reversible by `rm /var/moodledata/lang/en_local/*.php` + `admin/cli/purge_caches.php`. See D-008.

## Architecture & Key Decisions

### D-001 — Two-layer fix for the `file_exists(null)` outage (2026-04-19)
**Decided:** Fix the outage on two independent layers: (a) turn off debug display to unblock users immediately, and (b) patch the Edwiser plugin's `edwb_is_plugin_available()` to null-guard before calling `file_exists()`.
**Why:** Layer (a) alone restores the site in seconds but leaves a landmine — any future re-enable of debug display re-triggers the outage. Layer (b) alone is the correct root-cause fix but requires either an image rebuild or an in-container patch plus persistence; it does not by itself stop the user-visible cascade fast enough.
**Alternatives considered:**
- Fix only by turning off debug display → rejected: re-enable would re-break the site.
- Fix only by patching the plugin → rejected: slower to deploy; acute outage persists until the patch lands.
- Replace the upstream `block_edwiseradvancedblock.zip` with a patched zip → rejected: requires re-packaging a vendor plugin, harder to diff and review than a single-file patch, breaks symmetry with the existing `patches/choicelist_fixed.php` pattern.
**Related files:** see F-001, D-002.

### D-002 — Persist plugin patches via Dockerfile `COPY` overlay, not zip modification (2026-04-19)
**Decided:** The patched `lib.php` lives at `/opt/moodle/patches/edwiseradvancedblock_lib.php` on the server, and `/opt/moodle/Dockerfile` has a `COPY patches/edwiseradvancedblock_lib.php /var/www/html/public/blocks/edwiseradvancedblock/lib.php` line inserted immediately after the `block_edwiseradvancedblock.zip` extraction block.
**Why:** This is the exact pattern already used for `patches/choicelist_fixed.php` (which overrides a Moodle core file). It keeps vendor zips untouched, makes the diff obvious, and makes future upgrades easier (to drop the patch, delete the COPY line and the patch file).
**Alternatives considered:** modify the zip contents (too opaque); maintain a fork of the plugin (overkill for a one-line fix).
**Related files:** D-001, F-001.

### D-003 — In-container live patch via `docker cp` (not image rebuild) for the immediate fix (2026-04-19)
**Decided:** The acute fix was applied by `docker cp`-ing a patched `lib.php` into the running `moodle-app` container; the image was not rebuilt.
**Why:** Image rebuild on this server takes minutes; the outage was user-facing. The container will keep the live patch until it is recreated, and the Dockerfile + patches/ edit (D-002) ensure the fix is baked in on the next rebuild.
**Caveat:** `docker compose up -d` without rebuild preserves the container (and its patched filesystem), but `docker compose down && up` or any `docker compose build` invalidates the live patch. The Dockerfile edit must be present before the next rebuild — it is, as of 2026-04-19.

### D-004 — Install the 11 ghost plugins rather than purging all 13 (2026-04-19)
**Decided:** When `admin/webservice/service_functions.php` was broken by 13 ghost plugin registrations (plus 8 subplugin rows), install the 11 for which zips were available at `/opt/moodle/moodleplugins/` (or `Resources/moodle_plugins/` locally), and purge only the 2 for which no zip existed (`block_xp`, `filter_translations`).
**Why:** The source snapshot clearly had these plugins active (DB has configuration rows, block positions, license records for `local_edwiserreports`, etc.). The Dockerfile's minimal 3-plugin install was a conscious trim but the zips were kept in the build context, signalling that full reinstall was the intended fallback. Installing preserves forward compatibility and matches the source environment; purging all 13 would have dropped data and reduced feature parity.
**Alternatives considered:**
- Full purge of all 13 ghosts via `--purge-missing --run` → rejected: loses data in `mdl_block_xp_*`, `mdl_local_edwiserreports_*`, `mdl_local_edwiserform_*`, etc.; reduces feature parity vs. source.
- Surgical delete of only the blocking `mdl_external_functions` rows → rejected: leaves every other admin page exposed to the same class of failure, and the ghost plugins stay registered as "installed" forever.
- Full reinstall PLUS install of non-ghost extras (`edwiser_grader`, `format_remuiformat`) → rejected: scope creep; those weren't registered in the DB so reinstalling them would be adding NEW features, not restoring prior state.
**Related:** D-002 (same persist-via-Dockerfile pattern), F-002, §18 in DEPLOYMENT.md.

### D-006 — Populate course `idnumber` by bulk copy from `shortname` (2026-04-19)
**Decided:** Set `mdl_course.idnumber = shortname` via one SQL UPDATE on all 55 non-frontpage courses, rather than (a) hand-entering each in the admin UI, (b) looping `core_course_update_courses` over HTTP, or (c) hardcoding a subject-grade mapping table.
**Why:** All 55 course shortnames already follow the Ontario K-8 convention `{SUBJECT}{GRADE}` (e.g. `MAT01`, `LAN07`) that SchoolConex uses in its `courses.code` column. One transactional statement avoids partial-failure states from 55 HTTP round-trips and avoids maintaining a subject-grade lookup table.
**Alternatives considered:**
- `core_course_update_courses` via the WS API — rejected: 55 HTTP round-trips, harder rollback, no meaningful benefit when we have direct DB access.
- Admin UI per-course — rejected: ~30 min of manual clicking, error-prone.
- Hardcoded subject → grade → code mapping table — rejected: duplicates the shortname data that already encodes the same information.
**Override path:** If SchoolConex ever expects a different `idnumber` than the shortname for a specific course, edit that one course in the admin UI (`Course → Settings → General → Course ID number`). The statement is idempotent (the WHERE only targets empty `idnumber`s), so re-running won't clobber a manual override.
**Related:** §18 WS setup in DEPLOYMENT.md, `CHANGELOG.md` 2026-04-19T23:38Z block.

### D-005 — Rename zip-extracted dirs when they don't match the plugin component name (2026-04-19)
**Decided:** For zips whose top-level directory differs from the Moodle component name, the extract script renames after unzip. Specifically: `block_site_monitor.zip`'s `sitemonitor/` → `blocks/edwiser_site_monitor/`; `moodle-local-edwiserreports-1.zip`'s `moodle-local-edwiserreports/` → `local/edwiserreports/`; `local_sitesync.zip`'s `local_sitesync/` → `local/sitesync/`; `local_edwiserform.zip`'s `local_edwiserform/` → `local/edwiserform/`; `mod_edwiserform.zip`'s `mod_edwiserform/` → `mod/edwiserform/`.
**Why:** Moodle determines a plugin's component name from the on-disk directory path, not from `version.php`'s `$plugin->component`. If you drop a zip whose inner dir is `sitemonitor/` into `blocks/`, Moodle will try to register it as `block_sitemonitor` and fail to match the existing DB registration for `block_edwiser_site_monitor`. The rename makes on-disk path match DB component. The pre-existing `install_plugins.sh` script at `/opt/moodle/moodleplugins/` gets this wrong for site_monitor (installs at `blocks/sitemonitor/`) and is NOT the reference implementation — the corrected rename is in the current `Dockerfile` and in this notes entry.

### D-007 — Custom allow-list role for the site manager rather than cloning the "Manager" archetype (2026-04-20)
**Decided:** Created a fresh custom role `courseenroller` (Course Enroller, id=11) with **no archetype** and an explicit 22-capability allow-list at system context. Assigned to user `manager` (id=5) at system context. Role may also be assigned at course-category and course context levels (`set_role_contextlevels(CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE)`). `mdl_role_allow_assign` lets this role assign `student`, `teacher`, and `editingteacher` during enrolment.
**Why:** Starting from an empty role and opting-in to specific caps is safer than cloning the built-in `manager` archetype and trying to strip powers — the Manager archetype has 200+ caps including many that would leak admin access (`moodle/site:backup`, `moodle/course:create`, `moodle/site:configview`). An allow-list makes the security boundary auditable in one glance: if a cap isn't in the 22-item list, the user can't do it.
**Allow-list (22 caps, all `CAP_ALLOW` at system context):** `moodle/user:{create,update,viewdetails,viewhiddendetails,viewalldetails,editprofile,managesyspages}`; `moodle/course:{view,viewhiddencourses,useremail,enrolreview,viewparticipants,manageactivities}`; `moodle/category:{viewcourselist,viewhiddencategories}`; `moodle/role:{assign,review}`; `enrol/manual:{enrol,unenrol,manage}`; `moodle/cohort:{view,assign}`.
**Explicitly NOT granted (verified via `has_capability` probe as user 5):** `moodle/course:create` = deny, `moodle/backup:backupcourse` = deny, `moodle/site:config` = deny.
**Alternatives considered:**
- Clone archetype `manager` then `CAP_PROHIBIT` course:create / site:backup / site:config — rejected: prohibit-based security is fragile (easy to miss a capability granted by the archetype; any new cap added in future Moodle versions inherits ALLOW by default).
- Use the built-in `coursecreator` archetype — rejected: it's *for* creating courses, opposite of the ask, and doesn't grant user-creation.
- Assign role only at course-category level for the top category — rejected: requires per-category bookkeeping; the user explicitly said "access to all the courses," which system-context is the cleanest way to express.
**UX caveat:** In Moodle, Add-User lives under `Site administration → Users → Accounts`. Granting `moodle/user:create` causes Moodle's tree-builder to show a **filtered** Site administration link with only the Users → Accounts subtree populated; all other admin sections (Courses, Plugins, Server, Security, Appearance, Reports) are hidden because the user lacks their gating caps. This is the tightest interpretation of "cannot see the admin panel" that still lets the role create users via the standard UI.
**Implementation artifact:** `/tmp/create_manager_role.php` was scped + docker-cp'd in and run as `www-data`; file removed from host + container + local `/tmp` after verification. If this role ever needs to be recreated or extended, write a fresh idempotent script following the same shape (`create_role` → `set_role_contextlevels` → `assign_capability` loop → `core_role_set_assign_allowed` → `user_create_user` → `role_assign`).
**Related:** see "Current State" bullet on Site-admin users for the probe result and credentials.

### D-008 — Brand-rename "Moodle" → "EMCS" via `en_local` overrides, not `tool_customlang` UI or vendor-file edits (2026-04-20)
**Decided:** Replace every user-visible `Moodle` string on the live site by writing Moodle's native `en_local` override files — one per component — to `$CFG->dataroot/lang/en_local/*.php`. The generator walks every `lang/en/*.php` file under `/var/www/html/public/` (542 files across core + 14 plugins + RemUI theme + Forms-Pro subplugins), for each `$string[id] = value` where the value contains the case-sensitive token `Moodle`, writes `str_replace('Moodle','EMCS',value)` into the matching en_local file. Result: 561 string overrides across 119 component files. Case-sensitive on purpose — `moodledata`, `moodle.org`, class-name fragments are left untouched.
**Why:** `en_local` is Moodle's documented, first-class override channel for language strings; `string_manager_standard::load_component_strings()` loads it after the vendor file so entries win. It is:
- **non-destructive** — vendor `lang/en/*.php` files are never touched, so `docker compose build` / plugin updates don't clobber the rebrand;
- **reversible in one command** — `rm /var/moodledata/lang/en_local/*.php && php admin/cli/purge_caches.php`;
- **image-independent** — persists in the `moodledata` volume regardless of container rebuilds (survives `docker compose down && up`, does NOT survive a moodledata wipe);
- **comprehensive** — catches page titles, breadcrumbs, config descriptions, error messages, mobile-app help text, footer links, and plugin-specific strings in one pass.
**Alternatives considered:**
- `tool_customlang` UI — rejected: 561 strings would require 561 form submits, and the UI only surfaces strings for the currently-loaded component at a time. Scripted DB-row insertion into `mdl_tool_customlang` still requires a separate "apply changes" step that writes to the same `en_local` directory we're writing to directly — this is the shortcut, not a shortcut around the correct mechanism.
- Editing vendor `lang/en/*.php` files in-place — rejected: (a) lost on `docker compose build` / plugin upgrade, (b) would diverge from upstream and complicate any future Moodle/plugin version bump, (c) clutters diffs.
- Global CSS `display:none` on Moodle branding elements — rejected: CSS can only hide, not replace, so breadcrumb tail "... | Moodle" would either become "... |" (awkward) or require complex `content` tricks on pseudo-elements; leaves strings accessible to screen-readers / view-source inspection.
- `$CFG->sitename`/`$CFG->shortname` changes via admin UI — already done in D-006-adjacent work (see session summary #6); does NOT affect the hundreds of in-product "Moodle" string references.
**Scope boundary (intentional non-targets):**
- CSS class names (`.moodle-actionmenu`, `.moodle-has-zindex`) — changing would break theme/JS selectors.
- JS global `M.cfg.moodle...` — Moodle's core JS bootstrap; renaming breaks module loading.
- Internal URLs (`/admin/`, `/mod/`, `/user/`, `/course/`) — hard-coded across thousands of plugin files.
- `moodle.org`/`docs.moodle.org` links — these go to external Moodle documentation; renaming the link text to "EMCS Docs" was part of the 561 overrides, but the URL itself still points to docs.moodle.org. Acceptable because (a) the rename hides Moodle branding, (b) rewriting URLs would require a separate redirect-proxy feature we haven't built.
**Verified via Playwright (2026-04-20):** Home (`/`), Dashboard (`/my/`), Course view (`/course/view.php?id=294` "Social Studies Grade 1"), and Admin → Mobile Settings (`/admin/settings.php?section=mobilesettings`) each return **0 visible `Moodle` occurrences** in `document.body.innerText`. Titles now terminate `... | EMCS` (previously `... | Moodle`).
**Re-run / extend pattern:** If new plugins are installed that introduce fresh "Moodle" strings, re-run the generator — it is idempotent (merges into existing en_local files, later values win). Generator artifact `/tmp/rebrand_moodle_to_emcs.php` was not persisted; recreate from the pattern in this entry + the one-off artifact in CHANGELOG 2026-04-20T01:17Z block.
**Related:** see "Brand rename state" bullet in Current State.

### D-009 — Patch RemUI/Edwiser bare-jQuery usage via source overlays (2026-05-04)
**Decided:** Fix the recurring `TypeError: $ is not a function` page error by patching five vendor templates/assets and persisting them through `/opt/moodle/patches/` plus Dockerfile `COPY` overlays: `layout_require_js.mustache`, `login.mustache`, `edwiseradvancedblock_blockcontent.mustache`, `theme_remui_bs4-compat.js`, and `theme_remui_bs4-compat.min.js`.
**Why:** The visible failures came from bundled RemUI/Edwiser code using bare `$` outside Moodle's AMD `require(['jquery'], ...)` boundary. Overlaying the exact affected files keeps vendor zips intact and survives rebuilds.
**Verified:** 2026-05-04 browser pass showed 0 `pageerror` events on home, login, dashboard, course index, and About. 2026-05-05 rebuild changed the image/container IDs and all five patched files still matched expected md5s in the recreated container.
**Related:** F-005, F-006, CHANGELOG 2026-05-04T15:10Z and 2026-05-05T21:11Z.

### D-010 — Keep the local domain build context reproducible from archived plugin zips (2026-05-25)
**Decided:** The repo-local `www.appcanadaemcs.com` Docker build now uses `moodleplugins/mod_interactivevideo.zip` instead of an absent `plugins-src/interactivevideo/` tree, and `prepare-build-context.sh` materializes the ignored `moodleplugins/` build-context directory from `Resources/moodle_plugins/`.
**Why:** The live server's `/opt/moodle/` has a complete build context, but the local repo mirror was missing several Dockerfile `COPY` sources. A tracked preparation script avoids committing large plugin archives twice while keeping `docker compose build moodle` reproducible for any local checkout that has `Resources/moodle_plugins/`.
**Caveat:** Run `moodle_servers/www.appcanadaemcs.com/prepare-build-context.sh` before local builds after cleaning ignored files. Full image build requires Docker Desktop/daemon availability.
**Related:** F-007.

## File & Directory Map

Absolute paths from the project root `/Users/matthews/antigravity/Moodle_servers/`:

- `Context/README.md` — Remote Moodle Server Build Guide: the canonical build recipe for the source-of-truth server `5.78.128.44`. Describes Docker stack, plugin install, config, disaster recovery.
- `moodle_servers/www.appcanadaemcs.com/DEPLOYMENT.md` — Runbook for the rehomed `app.canadaemcs.com` instance on `5.78.190.143`. Sections §1–16 cover the rehome; §17 (new in this session) covers the `file_exists(null)` outage fix.
- `moodle_servers/www.appcanadaemcs.com/config.php` — Local mirror of the live Moodle `config.php` bind-mounted into the container at `/var/www/html/config.php`. `$CFG->debug` and `$CFG->debugdisplay` are both `0` here.
- `moodle_servers/www.appcanadaemcs.com/docker-compose.yml` — Current production `docker-compose.yml` for `app.canadaemcs.com`.
- `moodle_servers/www.appcanadaemcs.com/Dockerfile` — Local mirror of the production Moodle image recipe. As of D-010, installs Interactive Video from `moodleplugins/mod_interactivevideo.zip`.
- `moodle_servers/www.appcanadaemcs.com/prepare-build-context.sh` — Recreates ignored local build-context archives by hardlinking/copying required plugin zips from `Resources/moodle_plugins/`, then verifies Dockerfile `COPY` sources.
- `moodle_servers/www.appcanadaemcs.com/php.ini` — PHP overrides mirrored from `/opt/moodle/php.ini`.
- `moodle_servers/www.appcanadaemcs.com/patches/` — Rebuild-persistent source overlays mirrored from `/opt/moodle/patches/`.
- `moodle_servers/www.appcanadaemcs.com/docker-compose.yml.before` — Pre-fix snapshot of the compose, kept for reference.
- `moodle_servers/www.appcanadaemcs.com/nginx_moodle.conf` — `/etc/nginx/sites-available/moodle` on the host (TLS termination, reverse proxy to `127.0.0.1:8888`).
- `moodle_servers/www.appcanadaemcs.com/canadaemcs.com.txt` — Hostinger DNS zone snapshot (2026-04-19).
- `Resources/ssh_keys/hetzner_moodle_ed25519` — SSH private key for `root@5.78.190.143` (and by convention `root@5.78.128.44`). 0600 perms. **Note:** this was moved from `ssh_keys/` into `Resources/ssh_keys/` during the 2026-04-19 session; update any scripts that reference the old path.
- `Resources/ssh_keys/hetzner_moodle_ed25519.pub` — corresponding public key.
- `Resources/moodle_plugins/` — canonical plugin zip library. Mirror of `/opt/moodle/moodleplugins/` on the server, plus extras (`format_remuiformat_v4.1.17 (1).zip` lives here but not on the server). Includes the RemUI bundle (`Edwiser-RemUI-v5.1.2.zip`) and Forms-Pro bundle (`Edwiser-Forms-Pro.zip`) with nested inner zips.
- `Project_notes_folder/` — this directory; persistent cross-agent notes.
- `Project_notes_folder/RUNBOOK_MOODLE_CLONE.md` — **start here for any new clone / rehome.** 10-phase step-by-step runbook consolidating every fix and lesson from the 2026-04-18–20 `app.canadaemcs.com` rehome. Covers VPS provisioning (including UFW `443/tcp` gotcha), data transfer, domain re-point, TLS, all known-patch verifications (F-001 through F-005), WS token generation, `mdl_course.idnumber` bulk populate, optional `en_local` rebrand, admin-hardening, end-to-end verification, and rollback.

Server paths (`5.78.190.143`, `/opt/moodle/`):

- `/opt/moodle/config.php` — live Moodle config, bind-mounted to `/var/www/html/config.php` in `moodle-app`.
- `/opt/moodle/docker-compose.yml` — live compose (mirror of the repo file plus bind mount added on 2026-04-19).
- `/opt/moodle/Dockerfile` — image recipe (PHP 8.3-Apache + Moodle 5.1.3 + plugins + theme + patches).
- `/opt/moodle/patches/choicelist_fixed.php` — existing patch overriding `public/lib/classes/output/choicelist.php` (Moodle 5.1.3 core warning).
- `/opt/moodle/patches/choicelist.php.orig` — original of the above, kept for diffing.
- `/opt/moodle/patches/edwiseradvancedblock_lib.php` — **new in this session**: patched plugin `lib.php`; baked into image via Dockerfile `COPY` on next rebuild.
- `/opt/moodle/moodleplugins/block_edwiseradvancedblock.zip` — vendor plugin zip, extracted then overlaid by the patch.
- `/opt/moodle/moodledata/` — Moodle runtime data (~36 GB filedir; owned by `www-data:www-data`).
- `/opt/moodle/dbdata/` — MariaDB data.
- `/opt/moodle/.env` — DB root password (`MYSQL_ROOT_PASSWORD=...`); not in this repo.

## Accomplishments Log

### Session 2026-05-28 — Production unreachable triage blocked at provider access (Codex)

- Confirmed the user-reported outage from this workstation. `dig +short app.canadaemcs.com A` returns `5.78.190.143`; `curl -Iv http://app.canadaemcs.com` and `curl -Iv https://app.canadaemcs.com` time out or refuse at the TCP connect step. `nc` probes against `5.78.190.143` on `22`, `80`, `443`, `2222`, `8080`, and `8888` also timed out.
- Tried the documented root SSH path with `/Users/matthews/antigravity/Moodle_servers/Resources/ssh_keys/hetzner_moodle_ed25519`; SSH cannot reach a login prompt (`Connection refused`/timeout on port 22). The original source host `5.78.128.44` shows the same web/SSH network shape, so it is not a quick DNS failover target from this environment.
- Checked the repo, hidden files, `~/.ssh/config`, and environment variable names for alternate SSH aliases or Hetzner/Hostinger/Cloudflare/provider credentials. None were present. DNS is hosted at Hostinger (`ns1.dns-parking.com`, `ns2.dns-parking.com`) and still returns the documented A record.
- Traceroute reaches Hetzner (`static.110.7.78.5.clients.your-server.de`) and then stops; an ICMP probe also returned "Communication prohibited by filter" from a Hetzner router. Evidence points to provider-level/network/firewall/host accessibility, not an inspectable Moodle/PHP/app-layer failure.
- No live server mutation was possible this session. Recovery requires Hetzner console/rescue/KVM or fresh provider credentials to inspect power state, provider firewall rules, UFW, `sshd`, nginx, and Docker on `5.78.190.143`.

### Session 2026-05-25 (cont.) — F-006 About-page broken-image cleanup; password rotation pending auth (Claude)

- **F-006 follow-up resolved (cosmetic, CSS-only).** The About page (`/local/edwiserpagebuilder/page.php?id=5`) had 3 hard-coded `<img>` tags pointing to `/pix/site/section_interactive.png`, `/pix/site/section_games.png`, `/pix/site/section_teacher.png` — all 404s, rendering as broken-image icons in 3 `.col-md-6` two-column rows. Edwiser Page Builder stores its content as drag-and-drop widgets (not a single HTML blob exposed by `pageedit.php?id=9` — the `pagecontent[text]` textarea is empty), so editing the inline `<img>` tags directly via UI is impractical. Instead, appended a scoped `<style id="emcs-hide-broken-section-imgs">` block to `Site admin → Appearance → Additional HTML → "Within HEAD"` (textarea `s__additionalhtmlhead`). The block:
  1. Hides each of the three `img[src*="/pix/site/section_*.png"]` selectors with `display:none !important`.
  2. Hides the surrounding `.col-md-6:has(> img[src*="..."])` columns so no empty white space remains.
  3. Promotes the sibling text column to `flex: 0 0 100% !important; max-width: 100% !important` so each row renders as a single full-width text block.
- **Verification (post-purge, logged in as admin):** all 3 broken images compute `display: none`; surrounding `.col-md-6` columns compute `display: none`; sibling text columns compute `flex-basis: 100%, max-width: 100%`. Visible UX is clean. Note: the 404 GET requests still appear in browser DevTools network panel because `display:none` images are still requested by the browser; this is harmless network noise. To suppress fully, either upload real `section_*.png` files to `/var/www/html/public/pix/site/` (preferred long-term fix) or add a small JS snippet that strips the `src` attribute on DOMContentLoaded (extra surface area for a cosmetic-only network gain — not done).
- **Live additional-HTML head textarea:** pre 10529 chars → post 11460 chars. Tagged with `EMCS_ABOUT_HIDE_BROKEN_IMAGES` sentinel comment so future agents (or future-me) can grep and remove the block when proper imagery is uploaded.
- **Password rotation follow-up (DEPLOYMENT.md §Operational note 1) — STILL OPEN, pending explicit user authorization.** Attempted to generate strong random passwords for `admin` / `manager` / `demo` via `python3 -c 'import secrets...'` in Bash; the action was denied by the harness's hook system as a "high-severity action not explicitly authorized by the user's 'complete remaining tasks' message". This is correct behavior — credential rotation is destructive (the old passwords stop working immediately) and the harness requires explicit per-credential authorization rather than blanket "complete the work" approval. To unblock: the user can either (a) reply with "yes, rotate admin/manager/demo passwords and show me the new ones" to authorize me to generate strong random ones, or (b) provide the specific passwords they want set. Either path is a 60-second job once authorized; the same `/user/editadvanced.php?id={2,3,5}` admin UI path works for all three users.
- **Remaining open follow-ups (documented but not actionable from this session's tool surface):**
  - **Vimeo domain locking for `app.canadaemcs.com`** (DEPLOYMENT.md §3) — requires login to the Vimeo dashboard for the EMCS account. No Moodle-side configuration needed; this is purely a Vimeo Pro/Plus domain-allowlist setting. Cannot be done via this agent's tools.
  - **`local/edwiserreports/settings.php:197` PHP 8.3 deprecation** (F-003 in the Failures section, DEPLOYMENT.md §18 known follow-up) — requires either SSH to `5.78.190.143` to patch the file in-container and add a Dockerfile `COPY` directive (mirroring the F-001 fix pattern), or a vendor plugin upgrade. Out of scope for a UI-only session.
  - **`coursecreationguide` URL** — was cleared this session (was leaking `https://moodle.academy/coursequickstart`); can be set to a real EMCS course-builder tutorial URL if/when one exists. No-op for now.
  - **DKIM/SPF for `canadaemcs.com`** — only matters when outbound SMTP is actually configured (currently `smtphosts` is empty, Moodle uses PHP `mail()`). When SMTP is set up later, the `noreply@canadaemcs.com` From-address now in `noreplyaddress` will need DNS records on `canadaemcs.com` permitting it.
- **Files touched:**
  - Live `mdl_config.s__additionalhtmlhead`: 10529 → 11460 chars (+931 chars = the new EMCS_ABOUT_HIDE_BROKEN_IMAGES style block).
  - Moodle cache: purged.
  - Repo: `Project_notes_folder/PROJECT_NOTES.md` (this block) + `Project_notes_folder/CHANGELOG.md` (next block).

### Session 2026-05-25 (cont.) — Notes audit + local rebuild-context repair (Codex)

- Audited `Project_notes_folder/` and found it still in single-file mode: `PROJECT_NOTES.md`, `CHANGELOG.md`, and `RUNBOOK_MOODLE_CLONE.md` are each under the 500-line split threshold. Backfilled missing `PROJECT_NOTES.md` entries for D-009/F-006 from the existing May 4-5 changelog blocks and marked F-005 as resolved by D-009.
- Repaired the local `www.appcanadaemcs.com` build context. Restored `php.ini`, `patches/choicelist_fixed.php`, and `patches/edwiseradvancedblock_lib.php` from live `/opt/moodle`; hardlinked the required plugin archives into ignored `moodleplugins/`; changed the Dockerfile to install Interactive Video from `moodleplugins/mod_interactivevideo.zip`; added `prepare-build-context.sh` so future clean checkouts can recreate the local build inputs from `Resources/moodle_plugins/`.
- Validation completed: `docker compose config --quiet`, PHP lint on every repo PHP file, shell syntax checks, JSON validation for both branding backups, `prepare-build-context.sh`, Dockerfile `COPY` source preflight, `git diff --check`, and `docker compose --progress=plain build moodle` all pass. Image inspection confirmed Interactive Video, the Edwiser null guard, the choicelist patch, the RemUI patch, and required PHP extensions in `wwwappcanadaemcscom-moodle:latest`.
- Live smoke/browser verification completed without mutating production: `https://app.canadaemcs.com/` and `/login/index.php` return 200; `/user/contactsitesupport.php` returns 303 to `https://www.canadaemcs.com/contact`; certificate CN is `app.canadaemcs.com`, Let's Encrypt R13, valid 2026-04-19 through 2026-07-18. Headless Chrome confirmed footer injection, `info@CanadaEMCS.com`, `+1 (647) 667-2479`, 0 visible Moodle/Edwiser/Wisdm strings, no page errors, no horizontal overflow, and positive login-footer clearance on desktop and mobile.

### Session 2026-05-25 (cont.) — Brand-leak sweep: Moodle/Edwiser contact + identity surfaces (Claude)

- **Site Support contact (`/admin/settings.php?section=supportcontact`)** — was leaking Moodle defaults: `supportname = "Admin User"`, `supportemail = "admin@example.com"`, `supportpage = ""`, `supportavailability = 1` ("Logged-in users only"). Rewritten to:
  - `supportname = "Canada EMCS Support"`
  - `supportemail = "info@CanadaEMCS.com"`
  - `supportpage = "https://www.canadaemcs.com/contact"` — Moodle uses this for both the in-product "Contact site support" link AND as the redirect target on `/user/contactsitesupport.php`; **verified** that hitting that URL now 302/3-redirects to `https://www.canadaemcs.com/contact` (the marketing site's contact page).
  - `supportavailability = 2` ("Show to everyone") — so the EMCS contact info is visible to guests too.
- **Outgoing mail `noreplyaddress` (`/admin/settings.php?section=outgoingmailconfig`)** — was empty, which falls back to `noreply@<wwwroot-host>` = `noreply@app.canadaemcs.com` (already EMCS-flavored but app-subdomain). Set explicitly to `noreply@canadaemcs.com` so outbound `From:` headers always use the root domain regardless of which Moodle host is in play. SMTP transport itself is still empty — Moodle uses PHP `mail()` defaults from the container; out of scope for a brand-leak sweep.
- **Site admin user (`id=2`, username `admin`)** — was `Admin User <admin@example.com>` (the original Moodle install default that leaks "@example.com" anytime the admin's contact card is rendered, in user-search results, in course-creator audit trails, etc.). Updated via `/user/editadvanced.php?id=2` to `EMCS Admin <info@CanadaEMCS.com>`. Verified on `/user/profile.php?id=2` — page title now `"EMCS Admin: Public profile"`, body contains `info@CanadaEMCS.com`, no `admin@example.com`. Password unchanged (`Schoolx2024!` per Current State block; still pending rotation as a separate follow-up).
- **`s__custommenuitems` (custom top-nav menu items)** — the menu had a literal `Edwiser Forms` label exposed to every user. Renamed to just `Forms` (URL `/local/edwiserform/view.php` left intact; internal route paths are deliberately out-of-scope per D-008, only user-visible label was changed). Verified post-purge: nav now reads `Reports & Analytics`, `Forms`, `About`, `My Courses`, `Explore`, `Progress`, `Help` with **zero** Edwiser/Wisdm prefixes.
- **`s__coursecreationguide`** — was `https://moodle.academy/coursequickstart` (Moodle's external course-builder tutorial site). Cleared to empty. Affects the "course creation help" link surface; if a replacement EMCS tutorial URL exists, drop it in this field — it's just a free-text URL.
- **`s_backup_backup_async_message_subject`** — was `"Moodle {operation} completed successfully"` (the subject of admin email notifications when an async backup/restore finishes — leaks "Moodle" via email subject on every backup). Updated to `"EMCS {operation} completed successfully"`. The `{operation}` placeholder is preserved.
- **Comprehensive search audit performed via `/admin/search.php?query={email,moodle,edwiser,custommenuitems,async}`** — every form field with a non-default value was inspected against the label. Findings deliberately NOT changed (with reason):
  - `s_tool_moodlenet_enablemoodlenet = true` — label is already rebranded to `"Enable EMCSNet integration (inbound)"` via the en_local override (D-008). No user-visible "Moodle" leak; feature itself is benign (inbound content-sharing). Outbound sharing (`s__enablesharingtomoodlenet`) already `false`.
  - `s__customusermenuitems = "profile,moodle|/user/profile.php\n..."` — the literal `moodle` here is the lang-string COMPONENT identifier (format: `langstring,component|url`), not user-visible text. The displayed label resolves to `$string['profile']` from `lang/en/moodle.php`. Structural; leaving unchanged.
  - `s__geoip2file = "/var/moodledata/geoip/GeoIP-City.mmdb"` — filesystem path internal to the container, never rendered to users.
  - `s_theme_remui_enableusagetracking`, `s_theme_remui_enableedwfeedback` — both already `false` (no Edwiser phone-home).
  - `edd_remui_license_key = ""` — Edwiser license never activated; the "License is not activated" alert banner that this triggers is already CSS-hidden via the EMCS_hide_remui_banners block in `s__additionalhtmlhead` from the 2026-04-20 session.
  - **RemUI v5.1.2 has NO native `socialemail` / `footersocialemail` / contact-info fields** in any admin tab — the entire `Footer` tab pane is empty, and `Information Center` exposes only the `edd_remui_license_key` text input. The user's mention of "RemUI social email" refers to a setting that doesn't exist in this RemUI version; the only email surface in any EMCS-controlled footer is the custom `emcs-footer-injection.html` block (already updated 2026-05-25).
- **Caches purged** via `/admin/purgecaches.php` ("All caches were purged.").
- **Final user-visible verification (logged-out + logged-in admin, post-purge)**:
  - Home `/`: `bodyMoodleCount: 0, bodyEdwiserCount: 0, bodyWisdmCount: 0`. Footer `mailto:info@CanadaEMCS.com`, `tel:+16476672479` rendered as `+1 (647) 667-2479`.
  - Login `/login/index.php`: same — 0/0/0.
  - Logged-in home (admin view): same — 0/0/0. Nav menu `["Reports & Analytics", "Forms", "Reports"]` — no Edwiser/Wisdm prefix on any item.
  - `/user/contactsitesupport.php`: 302-redirects to `https://www.canadaemcs.com/contact` (page-title `"EMCS | Toronto EMCS | Online OSSD"`).
- **Files touched**:
  - Live `mdl_config`: `supportname`, `supportemail`, `supportpage`, `supportavailability`, `noreplyaddress`, `coursecreationguide`, `custommenuitems`, `backup_async_message_subject` (8 rows updated via admin UI).
  - Live `mdl_user.id=2`: `email`, `firstname`, `lastname` (admin user identity).
  - Repo: PROJECT_NOTES.md (this block) + CHANGELOG.md (next).
- **Out of scope (deliberate)**: SMTP transport config (still relies on container's PHP `mail()` defaults — separate engineering decision); rotating admin/demo/manager passwords (DEPLOYMENT.md §Operational note 1, still open); internal URL paths under `/local/edwiser*`, `/mod/edwiser*` (D-008 boundary).

### Session 2026-05-25 — Contact-info refresh + login-footer overlap fix (Claude)

- **Contact info refreshed in the live EMCS footer** at `Site admin → Appearance → Additional HTML → "When BODY is closed"` (textarea `s__additionalhtmlfooter`):
  - email `contact@emcs.ca` → `info@CanadaEMCS.com` (3 occurrences: social-icon `mailto:`, `Connect` column href, `Connect` column display text)
  - phone `+1 (416) 882-6571` / `tel:+14168826571` → `+1 (647) 667-2479` / `tel:+16476672479` (1 occurrence)
- **Diagnosed the long-standing login-page footer overlap.** RemUI ships the login layout with `.login-container` and `.login-description-container` as `position: absolute` inside a `display:flex` `.login-wrapper`. Absolutely positioned children contribute zero to their parent's intrinsic height, so `.login-wrapper` resolved to ~351 px while the form content actually rendered down to ~645 px. `#page-footer` is the next sibling in normal flow, so it rendered at y≈351 and the form (y≈389–645) rendered *over* it. Symptom: bottom of the login button + "Remember username" / "Forgotten password" links + cookie/policy line all hidden beneath the dark-green footer at every viewport.
- **Fix shipped (idempotent CSS in the same Additional HTML textarea, scoped to `body#page-login-index`):**
  - `.login-wrapper`: forced `display:flex; flex-direction:row; min-height:calc(100vh - 200px); padding:2rem 0; gap:2rem;` so the wrapper genuinely contains its children.
  - `.login-container` + `.login-description-container`: forced `position:relative; top/left/right/bottom:auto; height:auto; overflow:visible; margin:0 !important; box-sizing:border-box;` — the `margin:0` override matters because RemUI sets `margin-left:32px` on `.login-container`, which (harmless with absolute positioning) caused a 16 px horizontal scroll on mobile once the column flowed into the wrapper.
  - Desktop: form column `flex:0 0 422px` on the right, description column `flex:1 1 0; min-width:0` on the left.
  - Mobile (`max-width:768px`): wrapper switches to `flex-direction:column; padding:1rem 1rem 2rem;` and both columns go `width:100%; max-width:100%`.
- **Verified live via Playwright DOM + computed-style measurement** (the MCP screenshot tool consistently 5-s-timed-out on this heavy Moodle page; element- and full-page screenshots both failed identically, so this session relied on geometry assertions instead of pixels):
  - **Desktop 1440 × 900 / login**: `formBottom=545 px`, `footerTop=774 px` → 229 px clearance, no overlap; `flex-direction: row`; columns at `left:0` and `left:454`. `docW === viewport === 1440` (no horizontal scroll).
  - **Mobile 390 × 844 / login**: `formBottom=629 px`, `footerTop=858 px` → 229 px clearance; `flex-direction: column`; both column margins `0 px`. `docW === viewport === 390` (no horizontal scroll). Pre-fix mobile had `docW=406` (16 px overflow) from the leftover RemUI `margin-left:32px`.
  - **Home page (logged-out, 1440 viewport)**: `#emcs-footer` `mailto` x2 = `info@CanadaEMCS.com`, `tel` x1 text = `+1 (647) 667-2479` / href = `tel:+16476672479`. `ef.textContent` contains no `contact@emcs.ca`, no `416`.
  - **Login layout assertions pre-fix** (captured before changes for diff): `loginWrapper.height: 351`, `loginContainer.position: absolute`, `formBottom: 645`, `footerTop: 351`, overlap = 294 px.
- **Caches purged** twice (once after the v1 push, once after the v2 margin patch) via `/admin/purgecaches.php` → "All caches were purged." Both writes confirmed in DB via reading the textarea back after submit returned the "Changes saved" notification.
- **Backup of all three Additional-HTML textareas** captured before mutating the live site: `moodle_servers/www.appcanadaemcs.com/branding-backup-2026-05-25.json` (rollback artifact — head/topofbody/footer fields are the verbatim pre-change values).
- **Files touched**:
  - `moodle_servers/www.appcanadaemcs.com/emcs-footer-injection.html` (repo, canonical source) — contact-info text replacements + LOGIN PAGE LAYOUT FIX style block appended.
  - `moodle_servers/www.appcanadaemcs.com/branding-backup-2026-05-25.json` (repo, new) — pre-change snapshot.
  - Live textarea `s__additionalhtmlfooter`: pre 12100 chars → post 13477 chars. Existing `IV_DEBUG_LOGGER` dropdown shim (1671 chars) and `EMCS_TAB_SHIM` Bootstrap-tabs fallback (1600 chars) preserved byte-for-byte; only the middle `═══ CANADA EMCS FOOTER ═══ … ═══ END CANADA EMCS FOOTER ═══` block was replaced.
- **Out-of-scope (deliberately not changed)**: no other contact-info surface (Moodle `supportemail`, `supportname`, RemUI `socialemail`, plugin `local_edwiserreports` admin email, login-page support link) was touched. If contact emails elsewhere in admin settings should also be repointed, that's a follow-up.

### Session 2026-04-20 (cont.) — Moodle→EMCS rebrand via en_local overrides (Claude)

- **Audit:** `grep -h Moodle /var/www/html/public/lang/en/*.php | wc -l` returned 670 total lines across 79 core lang files; the structured pass that inspected `$string[...] = '...'` assignments across every `lang/en/*.php` file beneath `/var/www/html/public/` (542 files including plugins and RemUI) found **276 core + 285 plugin = 561 string values containing "Moodle"** across **119 unique component files**.
- **Generator:** wrote and ran `/tmp/rebrand_moodle_to_emcs.php` as `www-data` inside the `moodle-app` container. The script includes each vendor lang file, reads its `$string` array, filters values that contain the case-sensitive token `Moodle`, runs `str_replace('Moodle','EMCS', value)`, and writes the overrides as a merged `en_local/{file}.php` (with `defined('MOODLE_INTERNAL') || die();` guard, `var_export`-formatted values to preserve escapes/specials). Idempotent: re-running merges into existing `en_local` files.
- **Applied:** 561 string overrides written across 119 en_local files under `/var/moodledata/lang/en_local/`. Then `get_string_manager()->reset_caches()` + `admin/cli/purge_caches.php` as www-data.
- **Scss warnings observed during theme-CSS build** (`admin/cli/build_theme_css.php`): three `Warning: Array to string conversion in /var/www/html/public/lib/scssphp/src/Compiler.php on line 927` — preexisting Moodle 5.1.3 theme compilation warnings, unrelated to our changes; site renders cleanly.
- **Playwright verification (as admin):** home `/` → title `Home | EMCS`, body text `Moodle` count = 0. Login `/login/index.php` → title `Log in to the site | EMCS`, body text `Moodle` count = 0. Dashboard `/my/` → title `Dashboard | EMCS`, body text `Moodle` count = 0. Course view `/course/view.php?id=294` → title `Course: Social Studies Grade 1 | EMCS`, body text `Moodle` count = 0. Admin → Mobile settings `/admin/settings.php?section=mobilesettings` → title `Mobile settings | Mobile app | Administration | EMCS`, body text `Moodle` count = 0.
- **Residual `Moodle` tokens in raw HTML:** ~119 on homepage, all in CSS class names, JS module paths (`M.cfg.moodle...`), data attributes, and external doc links (`docs.moodle.org`). Not user-visible prose. Documented as intentional non-target in D-008.
- **Cleanup:** removed `/tmp/rebrand_moodle_to_emcs.php` from host, container, and local `/tmp`. Also removed the audit helper `/tmp/moodle_audit.sh` from all three.
- **Rollback path (documented in D-008):** `docker exec --user www-data moodle-app rm /var/moodledata/lang/en_local/*.php && docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php`.

### Session 2026-04-20 (cont.) — Admin tabs fix + RemUI license banner hide (Claude)

- **Hid the RemUI license banner** (`.alert.license-notice.alert-dismissible.site-announcement.alert-danger` with "Alert! License is not activated, please activate the license here."). Appended a `<style id="emcs-hide-remui-banners">` block to `Site admin → Appearance → Additional HTML → Within HEAD` targeting `.license-notice`, `.alert.license-notice`, `.license-activation-notice`, `.remui-license-alert`, `.edwiser-license-notification`, `.remui-upgrade-alert`, `.upgrade-remui-notice` with `display:none !important` plus belt-and-suspenders `visibility:hidden` / `height:0`. Verified: on home and `/admin/index.php`, the element is still in DOM but `display:none`, `height:0`. The 8 remaining alerts on the admin Notifications page are all legitimate Moodle core warnings (update check, cron, `display_errors`, mobile web service, EMCSNet shutdown).
- **Fixed broken admin-section tabs** at `/admin/search.php` (Users, Courses, Grades, Plugins, Appearance, Server, Reports, Development). **Symptom:** clicking any of those 8 tabs updated the URL hash but the tab-pane stayed `display:none` — the tabs looked completely unresponsive. User reported it as "admin user doesn't have full access". **Root cause:** Bootstrap tab JS was not firing — matches F-005's RemUI bs4-compat.js class. Tabs use both `data-toggle="tab"` and `data-bs-toggle="tab"` (Bootstrap 4 + 5 dual compatibility) and RemUI's compat shim is not wiring up the click handler in this Moodle 5.1.3 build. **Fix:** added a ~35-line vanilla-JS shim (`EMCS_TAB_SHIM` marker) to Additional HTML → "When BODY is closed" that: (a) listens for clicks on `a[data-toggle="tab"], a[data-bs-toggle="tab"]` and `preventDefault`s, (b) deactivates all sibling tabs + panes inside the same `.nav-tabs` / `[role=tablist]`, (c) adds `active show` classes to the targeted pane and `active` to the clicked tab link, (d) on DOMContentLoaded and `hashchange`, activates the pane matching the current URL hash. **Verified:** all 8 admin tabs now switch correctly (each activates its own pane and clears the others); tested by iterating clicks through `#linkcourses`, `#linkmodules`, `#linkappearance`, `#linkserver`, `#linkreports`, `#linkdevelopment`, `#linkgrades`, `#linkroot` and confirming `tabActive:true, paneActive:true, paneDisplay:"block"` for each.
- **Confirmed admin user has full access at the Moodle level:** user is in the Site administrators list (via `/admin/roles/admins.php`), seen as `Admin User (admin@example.com)`. Site admins bypass all role-based permission checks — no capability is blocking anything. The "restricted" appearance was 100% a UI bug (tab switching), not a permission issue.
- **Files touched**: live textareas `s__additionalhtmlhead` (hide CSS appended) and `s__additionalhtmlfooter` (tab-shim JS appended). Backup of pre-change values is still at `moodle_servers/www.appcanadaemcs.com/branding-backup-2026-04-19.json` from the previous rebrand session — recreate a fresh snapshot if needed before further edits.
- **Residual F-005:** the RemUI `bs4-compat.js` `TypeError: $ is not a function` is still in the browser console on every page. Our shim works around it for admin tabs specifically but does not fix the underlying race. A proper fix would be to either patch `theme_remui/amd/src/bs4-compat.js` to wait for jQuery via `require(['jquery'], function($) {...})`, or upgrade RemUI. Low priority now — the shim handles the visible failure.

### Session 2026-04-20 — Restricted `manager` user + `courseenroller` custom role (Claude)

- Designed the capability allow-list (22 caps across `user`, `course`, `category`, `role`, `enrol/manual`, `cohort` groups) — see D-007 for the full list and rationale.
- Wrote an idempotent PHP bootstrap script that: creates the role if absent, wipes+applies caps at system context, populates `role_allow_assign` so this role can assign student/teacher/editingteacher, creates the user (or resets password if exists), and role-assigns at system context. Includes a `has_capability` probe at the end that prints ALLOW/deny for the 6 critical caps.
- Executed the script inside `moodle-app` as `www-data`: `scp` → `docker cp` → `docker exec --user www-data … php /tmp/create_manager_role.php`. Verified output: `role id=11 shortname=courseenroller`, `caps applied=22 skipped=0`, `user id=5 username=manager`, probe confirmed ALLOW for `user:create`/`manual:enrol`/`course:view` and deny for `course:create`/`backup:backupcourse`/`site:config`.
- Purged caches via `admin/cli/purge_caches.php` as `www-data`.
- Cleaned up the bootstrap script from host `/tmp`, container `/tmp`, and local `/tmp` — not persisted anywhere (the role + user rows in DB are the durable artifact).
- Known UX caveat documented: the manager will see a filtered `Site administration` tree with only the `Users → Accounts` subsection populated — this is the tightest "no admin panel" achievable while still supporting in-UI user creation. See D-007 § UX caveat.

### Session 2026-04-19 (cont.) — Site rebrand to Canada EMCS + footer injection (Claude)

- **Changed site fullname/shortname via `/admin/settings.php?section=frontpagesettings`**: fullname `Grade 1-8 Virtual Academy` → `Canada EMCS`, shortname `G18VA` → `EMCS`, summary updated to `Ontario-accredited online education for students worldwide.`. Verified page titles now render as `... | EMCS`.
- **Mirrored brand identity from `www.canadaemcs.com`** (captured via Playwright DOM inspection of the marketing site): primary `#1B4332`, dark-bg `#0F2921`, accent gold `#D4AF37`, red `#C8102E`, body font Inter, display font Outfit, footer bg `#0F2921`, footer text `#D4D4D4`. Also captured: full footer copy (Programs/About/Connect columns + © 2026 + Privacy/Terms), org address `10 Gurney Crescent, North York, ON M6B 1S8`, phone `+1 (416) 882-6571`, email `contact@emcs.ca`, tagline `Ontario-accredited online education for students worldwide`.
- **Injected a full Canada EMCS footer via `/admin/settings.php?section=additionalhtml` → "When BODY is closed"**: CSS-scoped block (`#emcs-footer`) that replaces the default RemUI footer content (`.footer-dark`, `.footer-inner`, `.logininfo`, `.homelink`, `.tool_dataprivacy` etc. hidden via `display:none`). JS injection waits for `DOMContentLoaded` then appends a `<div id="emcs-footer" role="contentinfo">` to `#page-footer`; idempotent (re-check of existing `#emcs-footer` before insert). SVG EMCS mark inline (no file upload). The existing `IV_DEBUG_LOGGER` dropdown-toggle script was preserved verbatim above the new block.
- **Updated RemUI theme settings via `/admin/settings.php?section=themesettingremui`**: `sitenamecolor` `#0051F9` → `#FFFFFF` (needed for legibility on the dark green navbar); slider1 text `Grade 1-8 Virtual Academy` → `Canada EMCS` with new tagline; `frontpageaboutustext` replaced "Grade 1-8 Virtual Academy" → "Canada EMCS" (two occurrences); `frontpageaboutusheading` `About Our Academy` → `About Canada EMCS`; `frontpageblockheading` `Why Choose Our Academy?` → `Why Choose Canada EMCS?`. Sliders 2 and 3 (generic "Interactive Video Lessons" / "Hands-On Learning Games") left unchanged.
- **Purged caches** via `/admin/purgecaches.php` form submit (user-mode, via the admin UI — not `docker exec`, because prod SSH is sandbox-denied).
- **Verified end-to-end via Playwright**: homepage title `Home | EMCS`, login title `Log in to the site | EMCS`, hero H1 now "Canada EMCS", "Why Choose Canada EMCS?" block heading renders, "About Canada EMCS" heading renders, `#emcs-footer` injected on both home and login with 3 content columns + brand column + bottom bar, bg `rgb(15,41,33)` matches spec. No occurrences of `G18VA` or `Grade 1-8 Virtual Academy` remain in body text on either page.
- **Did NOT change** (out of scope): the RemUI logo filemanager-uploaded assets (`id_s_theme_remui_logo`, `favicon`, `logomini`, dark-mode variants) — still the old Grade 1-8 graduation-cap icon in the navbar. The header brand renders as `[graduation-cap icon] EMCS` because `logoorsitename=iconsitename`. If the user wants the navbar logo replaced with the Canada EMCS rounded-square SVG, that requires uploading a PNG/SVG via the filemanager UI; see F-005 follow-up.
- **Files touched**:
  - `moodle_servers/www.appcanadaemcs.com/branding-backup-2026-04-19.json` — pre-change snapshot of `additionalhtmlhead`, `additionalhtmltopofbody`, `additionalhtmlfooter` values (for rollback).
  - `moodle_servers/www.appcanadaemcs.com/emcs-footer-injection.html` — canonical source of the footer HTML/CSS/JS block; if the live textarea is ever wiped, paste this back into "When BODY is closed".
  - `canadaemcs-home.png`, `moodle-home-before.png`, `moodle-home-with-footer.png`, `moodle-home-after.png`, `moodle-login-after.png`, `moodle-login-logged-out.png`, `moodle-login-top.png` — Playwright screenshots for before/after.

### Session 2026-04-19 — "file_exists(null)" outage fix (Claude)

- Identified root cause: `edwb_is_plugin_available()` in `blocks/edwiseradvancedblock/lib.php:131` was passing `null` from `core_component::get_plugin_directory()` into `file_exists()`, tripping a PHP 8.1+ deprecation.
- Identified cascade mechanism: `$CFG->debugdisplay = 1` caused the deprecation to be emitted into the HTML stream before `session_start()`, which then failed with `sessionstarterror`, yielding a white error page for every request.
- Turned off debug on live server: `sed -i` edit to `/opt/moodle/config.php` setting `$CFG->debug = 0; $CFG->debugdisplay = 0;`.
- Patched running container `lib.php` via `docker cp` round-trip; verified the exact diff (single line change: `if (!file_exists($dir))` → `if ($dir === null || !file_exists($dir))`).
- Purged caches as `www-data` (per DEPLOYMENT.md §4): `docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php`.
- Captured the patched `lib.php` into `/opt/moodle/patches/edwiseradvancedblock_lib.php`.
- Inserted a new `COPY patches/edwiseradvancedblock_lib.php /var/www/html/public/blocks/edwiseradvancedblock/lib.php` line into `/opt/moodle/Dockerfile` after the existing extraction block for `block_edwiseradvancedblock.zip`.
- Verified end-to-end: home and login pages return 200, title is `Log in to the site | G18VA`, session cookie is issued, CLI reproduction confirms no deprecation with `display_errors=1`.
- Mirrored `$CFG->debug`/`$CFG->debugdisplay = 0` into `moodle_servers/www.appcanadaemcs.com/config.php`.
- Added DEPLOYMENT.md §17 with full narrative + verification transcript; marked §2 of "Operational notes / follow-ups" as resolved.

### Session 2026-04-19 (cont.) — Ghost plugin install for webservice form (Claude)

- Diagnosed broken `admin/webservice/service_functions.php` as 13 ghost plugins + 8 subplugin rows + 4 orphan `format_remuiformat` external-function rows. Evidence: `admin/cli/uninstall_plugins.php --show-missing` output.
- Took DB backup: `/opt/moodle/deploy/pre-ghost-fix-20260419-2000.sql.gz` (13 MB) before any writes.
- Mapped each ghost to its source zip by inspecting `version.php` inside each zip; discovered the `sitemonitor → blocks/edwiser_site_monitor` rename requirement (D-005).
- Installed 11 ghost plugins into the running container by `docker cp`ing zips to `/tmp/plugin_install/` then extracting each to its correct component path with per-plugin `mktemp`-d working dirs. Inner zips from `Edwiser-RemUI-v5.1.2.zip` and `Edwiser-Forms-Pro.zip` bundles were extracted on the host first, then individual zips pushed into the container.
- Ran `chown -R www-data:www-data /var/www/html` + `admin/cli/upgrade.php --non-interactive` inside the container as `www-data`. Upgrade completed successfully in under 10 seconds; observed one non-blocking PHP 8.3 deprecation from `local/edwiserreports/settings.php:197` (noted as open follow-up).
- Purged remaining 2 ghosts via `admin/cli/uninstall_plugins.php --purge-missing --run`: `block_xp`, `filter_translations`.
- Deleted 4 orphan `mdl_external_functions` rows for `format_remuiformat` (no matching version registration; `--show-missing` didn't catch them because they're orphan DATA not orphan PLUGIN).
- Purged caches as `www-data`.
- Verified: `total=859 ok=855 errors=0` (drop from 859 to 855 is the 4 deleted format_remuiformat rows); `curl` on `admin/webservice/service_functions.php?id=0` returns 303 → login (was 500 error page); home + login still 200.
- Updated `/opt/moodle/Dockerfile` with 70 new lines (between `filter_edwiserpbf` block and `mod_interactivevideo` block) implementing COPY + RUN steps for each of the 11 plugin zips including the 5 component-renaming cases.
- Added DEPLOYMENT.md §18 documenting the incident, fix, and the updated Dockerfile.

### Session 2026-04-19 (cont.) — End-to-end UX verification via Playwright (Claude)

- Navigated to `https://app.canadaemcs.com/login/index.php`; login page rendered cleanly with correct title, welcome copy, form fields, and 200 status.
- Logged in as `admin` / `Schoolx2024!`; redirected to `/admin/index.php` (Current release information page, shown because cache had been invalidated by the plugin install upgrade).
- Clicked "Continue" → reached Plugins check page showing **"No plugins require your attention now — Plugins requiring attention 0, All plugins 439"**. This is the ground-truth confirmation that every plugin on disk has a valid DB registration and vice-versa.
- Clicked "Upgrade Moodle database now" button to commit the UI-level upgrade acknowledgement. Result: `upgrade_noncore() Success (0.44 seconds)` — no plugin upgrade steps needed beyond what CLI `upgrade.php` already did.
- Navigated to `/admin/settings.php?section=externalservices` (the parent of the originally-broken form). Page rendered with three services: built-in "Moodle mobile web service" + custom "Edwiser sitesync" + custom "schoolconex_api".
- Clicked "Functions" link next to `schoolconex_api` → `/admin/webservice/service_functions.php?id=3` (exactly the URL that threw `coding_exception` before the fix). Page rendered cleanly: "Add functions to the service 'schoolconex_api'", "This service has no functions", "Add functions" link. **No coding_exception. No error banner.**
- Clicked "Add functions" → `/admin/webservice/service_functions.php?sesskey=...&id=3&action=add`. Form rendered with "schoolconex_api" heading, "Name" autocomplete/combobox field, and "Add functions"/"Cancel" buttons.
- Inspected the Name combobox options: **all 855 external functions populated**, including the exact `block_edwiser_site_monitor_get_last_24_hours_usage:Get live status of server` option from the original error message, plus all 4 `block_edwiser_site_monitor_*`, all 8 `block_edwiserratingreview_*`, `block_edwiseradvancedblock_set_block_config`, plus the full standard Moodle function set.
- Captured two pre-existing non-blocking browser console errors as F-004 and F-005 (see Failures section). Both observed on every admin page load; neither blocks form rendering or user interaction.
- Closed browser. No form submission was made — the test confirms the form is reachable and populated, which is everything needed to complete the user's step-5 "Add functions" in their 8-step web-services setup.

### Session 2026-04-19 (cont.) — Full WS setup for schoolconex_api + populated course idnumbers (Claude)

- Verified state of prior-session config via curl smoke-test + DB queries: all 5 user-required WS functions present on the `schoolconex_api` service, all 6 user-required capabilities on role `ws_api_schoolconex`, token `bbd004d7...07` still valid. Step 1 of the user's instructions was already complete from the earlier session — reported as no-op.
- Surveyed `mdl_course` — found all 55 non-frontpage courses with empty `idnumber`. Course shortnames already follow the Ontario K-8 code pattern (`MAT01`…`SST08`, plus `CFR`/`GEO`/`HIS` for middle grades).
- Took DB backup: `/opt/moodle/deploy/pre-idnumber-20260419-2334.sql.gz` (13 MB) before any mutation.
- Applied `UPDATE mdl_course SET idnumber = shortname WHERE id != 1 AND (idnumber = '' OR idnumber IS NULL);` → ROW_COUNT=55. Purged caches as www-data.
- DB verification: `ok=55, miss=0, total=55`.
- WS verification: called `core_course_get_courses_by_field` with `field=idnumber` for 9 sample codes (MAT01, LAN07, SCI08, CFR04, GEO07, HIS08, ART02, HPE05, SST03). Each returned a single-course `courses[]` array with matching `id`/`shortname`/`idnumber`/`fullname`.
- Added D-006 decision and this accomplishment block to project notes.

## Failures & Resolutions

### F-001 — Site-wide outage from PHP 8.3 `file_exists(null)` deprecation (2026-04-19)
**Issue:** Every request to `https://app.canadaemcs.com/` rendered as a white error page with `Deprecated: file_exists(): Passing null to parameter #1 ($filename) of type string is deprecated … sessionstarterror`.
**Root cause:** `blocks/edwiseradvancedblock/lib.php:131` called `file_exists($dir)` where `$dir = core_component::get_plugin_directory(...)` could return `null` for uninstalled/unknown components. PHP ≥ 8.1 (container uses 8.3) emits a deprecation notice for this. With `$CFG->debugdisplay = 1` still on from the snapshot, the notice was emitted as HTML before `session_start()`, breaking session initialization.
**Fix:** Patched the plugin function to null-guard (`if ($dir === null || !file_exists($dir)) { return false; }`). Turned off `$CFG->debug` and `$CFG->debugdisplay` in `config.php` as defense-in-depth. Patch persisted in `/opt/moodle/patches/edwiseradvancedblock_lib.php` + `Dockerfile` `COPY` directive.
**Guardrail preventing recurrence:**
- Plugin patch is in a persistent location (`/opt/moodle/patches/`) and will be re-applied on every image rebuild via the Dockerfile `COPY` line (D-002).
- Debug display defaults to off in both the live `/opt/moodle/config.php` and the repo mirror `moodle_servers/www.appcanadaemcs.com/config.php`.
- For any future `docker compose build moodle` or fresh VPS rebuild: verify the `COPY patches/edwiseradvancedblock_lib.php …` line is still in the Dockerfile (it was inserted after line 58 in this session).
- For any future debug re-enable: first confirm no other PHP 8.x deprecations from the installed plugin set (F-003 below tracks one known remaining case).

### F-002 — Webservice "Add functions" form blocked by 13 ghost plugins (2026-04-19)
**Issue:** `Site administration → Server → Web services → External services → Add functions` threw a `coding_exception`: "Cannot find file [dirroot]/blocks/edwiser_site_monitor/classes/externallib.php with external function implementation for block_edwiser_site_monitor\externallib::get_last_24_hours_usage". This blocked the entire Moodle web-service + token setup the user was trying to complete.
**Root cause:** The snapshot DB carried 13 ghost plugin registrations + 8 subplugin rows in `mdl_config_plugins` and corresponding entries in `mdl_external_functions`. The image's Dockerfile was only installing 3 of the original plugins (minimal trim), so the files for 10 real plugins (plus `block_xp`, `filter_translations`) were absent on disk. The admin form iterates every `mdl_external_functions` row and calls `external_api::external_function_info()` on each — the first missing-file hit throws, killing the form.
**Fix:** (a) Installed 11 ghost plugins from zips kept at `/opt/moodle/moodleplugins/` (see D-004 for the install-vs-purge decision + D-005 for the dir-rename requirement); (b) purged `block_xp` and `filter_translations` (no zip available) via `admin/cli/uninstall_plugins.php --purge-missing --run`; (c) deleted 4 orphan `mdl_external_functions` rows for `format_remuiformat` that had no matching version registration; (d) ran `admin/cli/upgrade.php` to register new plugins; (e) purged caches as www-data.
**Guardrail preventing recurrence:**
- The 11 plugin installs are codified in `/opt/moodle/Dockerfile` (70 new lines between the `filter_edwiserpbf` block and the `mod_interactivevideo` block) — future `docker compose build moodle` will reproduce the installed state.
- DB backup `/opt/moodle/deploy/pre-ghost-fix-20260419-2000.sql.gz` exists as a restore point.
- The programmatic verification script (iterate `mdl_external_functions`, call `external_api::external_function_info()` on each, count errors) is the canonical regression test for this class of bug. Run it after any bulk plugin install/uninstall. Script is preserved in DEPLOYMENT.md §18.
- Reminder for future snapshot-based rehomes: always run `admin/cli/uninstall_plugins.php --show-missing` on first boot and resolve every entry (install or purge) before opening the site.

### F-003 — Deprecation in `local/edwiserreports/settings.php:197` (known, unfixed) (2026-04-19)
**Issue:** `Deprecated: stripos(): Passing null to parameter #1 ($haystack) of type string` + `Warning: Undefined array key "REQUEST_URI"` emitted on every upgrade and every admin page load that touches plugin settings. Same shape as F-001.
**Root cause:** Not investigated in depth; the plugin code at that line calls `stripos($_SERVER['REQUEST_URI'], ...)` without guarding against REQUEST_URI being undefined (in CLI context) or null.
**Current state:** Invisible on the site because `$CFG->debugdisplay = 0` (DEPLOYMENT.md §17 fix). Visible as noise in container logs during cron/upgrade.
**Fix pattern (when prioritised):** Same as F-001 — capture a `/opt/moodle/patches/edwiserreports_settings.php` overlay that null-guards the `stripos` call, add a `COPY` line to `Dockerfile` after the ghost-fix plugin block, rebuild or `docker cp` into running container.
**Guardrail:** Currently only the debug-off state (F-001 guardrail) prevents this from tripping a session cascade. Do NOT re-enable debug display on production until this is patched.

### F-004 — `local_edwiserreports/install.js` 404 on every admin page load (known, low priority) (2026-04-19)
**Issue:** Browser console error on every admin request: `Failed to load resource: 404 … /lib/requirejs.php/-1/local_edwiserreports/install.js` → `Error: Script error for "local_edwiserreports/install"`.
**Root cause:** The `local_edwiserreports` plugin zip (`moodle-local-edwiserreports-1.zip` from the Resources/Dockerfile install path) declares `install.js` as a requirejs module but does not ship it. The module is referenced from the plugin's initialization flow that runs on every admin page. Not caused by our install — the zip shipped this way from Edwiser.
**Current state:** Non-blocking. The plugin's admin reporting dashboards work around it; forms and other admin pages function normally. Confirmed by UX test on 2026-04-19 — admin login, navigation, and the web-service form all complete successfully despite these console errors.
**Fix pattern (when prioritised):** Either (a) obtain a newer `local_edwiserreports` zip from Edwiser that includes the missing `install.js`, or (b) create an empty stub at `local/edwiserreports/amd/src/install.js`, then rebuild requirejs cache via `php admin/cli/purge_caches.php`. Option (b) is a vendor-patch pattern similar to F-001 — persist via `/opt/moodle/patches/edwiserreports_install_js_stub.js` + a Dockerfile COPY line.
**Guardrail:** None needed; page renders and forms work. Track in Apache access logs for unusual 404 volume if browser console is being swept.

### F-005 — `theme_remui/bs4-compat.js` `TypeError: $ is not a function` on every page (pre-existing, low priority) (2026-04-19)
**Issue:** Browser console error `TypeError: $ is not a function at theme_remui/bs4-compat.js:14:2362 at theme_remui/loader.js:9:2454`. Fires on every page including login and admin. Predates this session's work — observable on a freshly-redeployed container.
**Root cause:** The RemUI theme's Bootstrap 4 compatibility shim runs before `$` (jQuery) is loaded by Moodle's requirejs bootstrap, causing a race. This is a known class of issue in the RemUI v5.1.2 bundle against Moodle 5.1.3.
**Current state:** Resolved 2026-05-04 by D-009. Browser verification after the patch and again after a 2026-05-05 image rebuild showed 0 `pageerror` events on home, login, dashboard, course index, and About. 2026-05-25 headless Chrome verification of home/login also saw no page errors.
**Fix:** Five RemUI/Edwiser files are overlaid from `/opt/moodle/patches/` and mirrored locally in `moodle_servers/www.appcanadaemcs.com/patches/`. Dockerfile `COPY` lines make the fix rebuild-durable.
**Guardrail:** Re-run the 5-page browser page-error check after any RemUI upgrade or Dockerfile patch shuffle.

### F-006 — About-page `section_*.png` images 404 (known, cosmetic) (2026-05-04)
**Issue:** `/local/edwiserpagebuilder/page.php?id=5` references `/pix/site/section_interactive.png`, `/pix/site/section_games.png`, and `/pix/site/section_teacher.png`, which return 404.
**Root cause:** The Page Builder content references tenant/site images that were not present in the Moodle file store or theme pix path after rehome.
**Current state:** Cosmetic only. It does not affect home/login/admin functionality and remained the only known browser-console resource issue after D-009.
**Fix options:** Upload the three images to the expected path through Moodle/theme asset handling, or remove/replace those `<img>` references in `mdl_edw_pages.id=5.pagecontent`.
**Guardrail:** Check the About page after any branding asset refresh.

### F-007 — Local repo mirror had missing Dockerfile `COPY` sources (2026-05-25)
**Issue:** Local preflight found missing build-context inputs for `moodle_servers/www.appcanadaemcs.com/Dockerfile`: plugin archives, `php.ini`, `patches/choicelist_fixed.php`, `patches/edwiseradvancedblock_lib.php`, and `plugins-src/interactivevideo`.
**Root cause:** The live server's `/opt/moodle/` build context was complete, but the repo mirror only tracked the Dockerfile/compose and some patches; large plugin archives were intentionally ignored under `Resources/moodle_plugins/`, and the Dockerfile still expected an unpacked Interactive Video source tree.
**Fix:** Added `prepare-build-context.sh`, restored the missing text/patch files from live `/opt/moodle`, hardlinked required plugin zips into ignored `moodleplugins/`, and switched the Dockerfile to install Interactive Video from `moodleplugins/mod_interactivevideo.zip`.
**Current state:** Dockerfile `COPY` source preflight passes locally. After starting Docker Desktop, `docker compose --progress=plain build moodle` completed successfully and a no-start image inspection verified the expected plugin/patch files and PHP extensions.

### F-008 — Production VPS/network unreachable; SSH unavailable (2026-05-28)
**Issue:** `https://app.canadaemcs.com` is unreachable. DNS resolves to `5.78.190.143`, but external TCP connects to `80/tcp` and `443/tcp` time out or refuse. The documented SSH path (`root@5.78.190.143` with `Resources/ssh_keys/hetzner_moodle_ed25519`) also fails before authentication (`Connection refused`/timeout on `22/tcp`).
**Root cause:** Not confirmed because no server shell or provider API/console credential is available in the repo/environment. Evidence points outside Moodle application code: traceroute reaches Hetzner and then stops, ICMP saw "Communication prohibited by filter" from a Hetzner router, and the original source host `5.78.128.44` shows the same web/SSH network shape from this workstation.
**Current state:** Unresolved and blocked on infrastructure access. Local repo/config changes cannot restore service while `22/80/443` are unreachable and DNS still points to the inaccessible VPS.
**Recovery path:** Use Hetzner console/rescue/KVM or provider credentials to check server power state, provider firewall, UFW rules (`22`, `80`, `443`), `sshd`, nginx, and the Moodle Docker stack. If the VPS was deleted/rebuilt or the IP changed, update Hostinger DNS (`app.canadaemcs.com A`) to the live replacement and re-run the `RUNBOOK_MOODLE_CLONE.md` smoke checks.

## Open Questions / Next Steps

1. **URGENT: Restore production infrastructure access for F-008.** Use Hetzner/provider console or updated credentials to inspect `5.78.190.143`; confirm power state, provider firewall, UFW, `sshd`, nginx, and Docker. If the server/IP changed, update Hostinger DNS and re-run the smoke checks from `RUNBOOK_MOODLE_CLONE.md`.
2. ~~**Complete the web services + token setup**~~ **RESOLVED 2026-04-19** — service `schoolconex_api` (id=3) is configured with 10 functions + restricted-users + authorised user `schoolconex_api` + permanent token `bbd004d7f516adf1e41bc7a4a75a8d07`. Course `idnumber` populated on all 55 courses to match `shortname`. See Current State and D-006. **Remaining follow-up tasks:** (a) IP-restrict the token once the Supabase backend IP is known, (b) store the token as `MOODLE_WS_TOKEN` in Supabase secrets, (c) run SchoolConex's audit endpoint with `dry_run: true` to confirm no `courses.code` values mismatch Moodle `idnumber`; fix any individual mismatches via admin UI `Course → Settings → Course ID number`.
3. **Patch F-003** (`local/edwiserreports/settings.php:197` null-to-stripos deprecation) before anyone re-enables debug display. Pattern from F-001/D-002: capture patch file → add `COPY` line to Dockerfile → `docker cp` into running container. Low urgency but necessary for log hygiene + debug-safety.
4. **Rotate admin, demo, and manager passwords on the live server** (DEPLOYMENT.md follow-up §Operational note 1). The snapshot carried `admin / Schoolx2024!`, `demo / DemoStudent2026!`, and `manager / Admin123!` — all still need rotation.
5. **Confirm Vimeo domain locking** allows `app.canadaemcs.com` (DEPLOYMENT.md follow-up §Operational note 3). Interactive Video modules will 403 for students otherwise.
6. **Resolve F-006 About-page image 404s** by uploading the three missing `section_*.png` images or removing their references from `mdl_edw_pages.id=5.pagecontent`.
7. **Monitor for additional PHP 8.3 deprecations** in the installed plugin set. With 11 new plugins installed as of 2026-04-19, the deprecation surface area roughly tripled. Enable debug log (not debug display) in a staging context if possible, and grep Apache logs for `Deprecated:` over a 24h window to catch anything else.
8. **Decide whether to notify the Edwiser plugin upstream** about the `file_exists(null)` bug (F-001), the `stripos(null, ...)` bug (F-003), and the D-009 jQuery/AMD fixes, so local patches can be retired in a future vendor release.
9. **Scheduled production image rebuild** — local image build is verified as of 2026-05-25. When a production maintenance window is available, run the same build on the server and `docker compose up -d moodle`; verify F-001/F-002/D-009 after the rebuilt container starts.

## Context for the Next Agent

- **Two hosts:** `5.78.128.44` is the original / source of truth; `5.78.190.143` is the `app.canadaemcs.com` rehome. Both use the same `/opt/moodle/` layout. `Context/README.md` describes the former; `moodle_servers/www.appcanadaemcs.com/DEPLOYMENT.md` describes the latter.
- **SSH key:** `/Users/matthews/antigravity/Moodle_servers/Resources/ssh_keys/hetzner_moodle_ed25519` for `root@5.78.190.143` (and by convention `root@5.78.128.44`). Use `ssh -i <key> root@<host>` or set up a host-alias in `~/.ssh/config`. **Note:** this was moved from `ssh_keys/` to `Resources/ssh_keys/` during the 2026-04-19 session.
- **Bind-mounted `config.php`:** edits to `/opt/moodle/config.php` on the host take effect in the container immediately — no rebuild needed. This bind mount was added in DEPLOYMENT.md fix §2 on 2026-04-19 exactly because the image used to bake config.php in, which caused a redirect to the old IP after rehome.
- **Cache purges:** always run purges with `--user www-data`, never as root, or `muc/config.php` gets root-owned and then Apache can't read it (DEPLOYMENT.md §4).
- **Patch pattern:** `/opt/moodle/patches/` holds files that overlay extracted vendor/core source. The `Dockerfile` has one `COPY patches/... /var/www/html/public/...` line per patch, placed **after** the zip-extract step for the same plugin. Keep `<name>.php.orig` alongside the patched file when possible (see `choicelist.php.orig`).
- **Local build context:** before running `docker compose build moodle` from `moodle_servers/www.appcanadaemcs.com/`, run `./prepare-build-context.sh`. It recreates ignored `moodleplugins/` entries from `Resources/moodle_plugins/` and fails fast if any Dockerfile `COPY` source is missing.
- **Passwords:** DB root password in `/opt/moodle/.env` (not in repo). Admin and demo user passwords are documented in DEPLOYMENT.md §Operational note 1 — both need rotation before public launch.
- **Quoting over SSH+docker exec:** simple `ssh ... 'docker exec ... sed ...'` fails when the sed pattern contains `$`, because the outer shell + SSH shell + docker exec shell together eat escapes. Reliable patterns: (a) `docker cp` out → edit on host with Python → `docker cp` back, (b) `ssh host bash << 'EOF' ... EOF` with single-quoted heredoc, (c) `docker exec python3 -c '...'`.
- **PHP version:** the image is `php:8.3-apache`. Any code that passes `null` to string-typed functions will emit deprecations. F-001 is one example; more may exist.
- **Project notes discipline:** this `Project_notes_folder/` is the shared state across Claude and Codex. After any material change — file edited, decision made, bug fixed, failure hit — run the `update-project-notes` skill to keep `PROJECT_NOTES.md`, `CHANGELOG.md`, and (in split mode) session files in sync. Never blind-append: merge into the correct section, preserving stable IDs (`D-NNN`, `F-NNN`).
- **File-split threshold:** 500 lines. If this file crosses 500, split into `decisions.md`, `file-map.md`, `failures.md`, `context.md`, and `sessions/YYYY-MM-DD-<slug>.md` per the skill instructions; leave `PROJECT_NOTES.md` as index + Current State + Open Questions.
