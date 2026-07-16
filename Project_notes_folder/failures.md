# Failures & Resolutions — Moodle Servers

_Split out of PROJECT_NOTES.md on 2026-07-16. Append-only._


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

### F-008 — Production VPS/network unreachable; SSH unavailable (2026-05-28) — RESOLVED 2026-06-13
**Issue:** On 2026-05-28 `https://app.canadaemcs.com` was unreachable from the prior workstation. DNS resolved to `5.78.190.143`, but external TCP connects to `80/tcp` and `443/tcp` timed out or refused, and the documented SSH path failed before authentication on `22/tcp`. At the time no SSH key or provider credential was present in the repo/environment.
**Root cause:** Client-side network reachability, NOT a server failure. Verified 2026-06-13: the host has been continuously up for 36 days (containers up 5 weeks), so it was never down during the 2026-05-28 window. The earlier traceroute/ICMP "Communication prohibited by filter" evidence was consistent with a network path / firewall issue between the old workstation and Hetzner, not a host outage.
**Resolution (2026-06-13):** From a different workstation, both hosts are reachable. SSH root login succeeded on `5.78.190.143` with `Resources/Resources-20260613T183107Z-3-001/Resources/ssh_keys/hetzner_moodle_ed25519` and on `5.78.128.44` with `~/.ssh/schoolx` (the source host requires the separate `schoolx` key — the `hetzner_moodle_ed25519` key is rejected there). HTTPS/HTTP on both return `200 OK`; all three containers on each host are healthy. Hetzner console credentials are now stored in gitignored repo-root `.env` for future provider-level access.
**If it recurs:** First confirm whether it is client-side (test from a second network/host before assuming a server outage). For genuine host issues, use the Hetzner console (`https://console.hetzner.com/projects/14010860/servers`, creds in `.env`) to check power state, provider firewall, UFW (`22`, `80`, `443`), `sshd`, nginx, and the Docker stack; if the IP changed, update Hostinger DNS and re-run `RUNBOOK_MOODLE_CLONE.md` smoke checks.

### F-009 — `moodle-app` crash-loops when config.php is bind-mounted read-only (2026-07-04)
**Issue:** During the `elementary.schoolconex.com` cutover on `5.78.128.44`, adding `- ./config.php:/var/www/html/config.php:ro` to the compose file made the `moodle-app` container restart-loop ("Up Less than a second"), so `127.0.0.1:8888` reset every connection. Logs: `chown: changing ownership of '/var/www/html/config.php': Read-only file system`.
**Root cause:** The image's **baked** entrypoint (from `moodle-local/entrypoint.sh`, distinct from the host `/opt/moodle/entrypoint.sh`) runs `chown -R www-data:www-data /var/www/html` under `set -e`. A read-only bind-mount makes that chown fail → entrypoint exits non-zero → container never reaches `apache2-foreground`.
**Resolution:** Mount config.php **read-write** (`- ./config.php:/var/www/html/config.php`, no `:ro`). The chown then succeeds (harmlessly re-owns the host file to uid 33), Apache starts, `/login` returns 200. Applies to any of these baked images — never mount a file into `/var/www/html` read-only.

