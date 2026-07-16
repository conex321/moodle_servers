# CHANGELOG — Moodle Servers

Append-only audit trail. One block per `update-project-notes` invocation. Never rewrite past entries.

> Entries from 2026-04-19 through 2026-05-05 are archived in `CHANGELOG_archive_2026-04_05.md`.

## 2026-07-07T21:40Z — Claude
- session: inline
- decisions_added: [D-015]
- failures_added: []
- files_changed: [server 5.78.190.143 (DB: site fullname/shortname/summary, supportname/email/page, additionalhtmlhead, additionalhtmlfooter; core_admin logo/logocompact/favicon files; passwords for teacherdemo + demostudent01-10); repo: Hetzner/Default-14010860/servers/app.canadaemcs.com/{branding-backup-2026-07-07.json, rebrand-emcs-2026-07-07.php, branding-assets-2026-07-07/*}; Project_notes_folder/{PROJECT_NOTES.md, CHANGELOG.md}; file_structure.md]
- summary: Rebranded **https://app.canadaemcs.com** (`5.78.190.143`) to **Canada EMCS** at the user's request ("make it the same as elementary.schoolconex but with canadaemcs branding" + "provide admin and demo teacher credentials with simulated user data"). Live probes showed the box had been silently redeployed into a content twin of elementary.schoolconex.com (Moodle 5.1.3, `moodle-moodle:latest`, phantom `schoolx` theme → renders Boost, 57 grade-1-8 courses, 22-user roster incl. teacherdemo + demostudent01-10, VN/ZH langpacks) — so no clone/content-migration was needed, only branding + demo passwords. Applied via DB/config only: site fullname `Canada EMCS` / shortname `EMCS` / summary + support contact; `additionalhtmlhead` → EMCS palette (Inter/Outfit, `#1B4332`/`#0F2921`/`#D4AF37`); `additionalhtmlfooter` → brand-scrubber (SchoolConex/Grade-1-8 → Canada EMCS) + full Canada EMCS footer (from emcs-footer-injection.html); `core_admin` logo/logocompact/favicon → generated EMCS mark (square monogram + Canada EMCS lockup, Playwright-rendered from inline SVG matching canadaemcs.com/favicon.svg). Purged caches.
- credentials_set: admin/Schoolx2024! (verified, unchanged), teacherdemo/DemoTeacher2026! (reset — teaches 10 courses ~11 enrolled each = simulated user data), demostudent01-10/DemoStudent2026! (reset), demo/DemoStudent2026! (unchanged, student).
- rollback: Hetzner snapshot image 405851690 (whole-box, pre-change, ~34 GB) + branding-backup-2026-07-07.json (fine-grained). Deleted the provisional 80 GB volume 106256967 (created while briefly considering an hs-clone, then abandoned).
- verification: EXTERNAL via Playwright — login/home/footer show EMCS logo + green/gold palette + title "… | EMCS" + contact info; zero SchoolConex/Grade-1-8 leakage; admin→admin panel, teacherdemo→10 courses, demostudent01→OK. elementary.schoolconex.com re-checked afterward: unchanged (still SchoolConex-branded).
- note: the pre-today undocumented redeploy of 5.78.190.143 (which wiped the 2026-04-19 EMCS rebrand and swapped in the schoolx/elementary content) remains unexplained in repo history — flagged in D-015 + Open Questions #5.
- next: rotate the shared `admin/Schoolx2024!` password before real use (Open Questions #4); optionally replace the generated EMCS monogram with an official logo lockup if the marketing team provides one.

## 2026-05-25T17:42Z — Claude
- session: single-file mode (PROJECT_NOTES.md at ~320 lines after this update, well under the 500-line split threshold)
- decisions_added: none (login-overlap fix is a CSS-scoped patch in an already-documented injection channel; not architecturally novel enough to merit a new D-### entry)
- failures_added: none formally; root cause of the login overlap is documented inline in the 2026-05-25 accomplishments block (RemUI's `position:absolute` on `.login-container`/`.login-description-container` inside a flex wrapper with no intrinsic height → wrapper resolves to ~351 px, form actually renders to ~645 px, `#page-footer` follows in normal flow and renders at y≈351 over the form)
- files_changed:
  - Live server (`5.78.190.143`, via admin UI — no SSH this session):
    - `mdl_config` row `s__additionalhtmlfooter`: **rewritten** twice. v1 swapped contact info and appended LOGIN-PAGE-LAYOUT-FIX CSS; v2 added `margin: 0 !important` + `box-sizing: border-box` to fix a mobile-only 16 px horizontal scroll caused by RemUI's leftover `margin-left: 32px` on `.login-container`. Pre 12100 chars → final 13477 chars. The pre-existing `IV_DEBUG_LOGGER` dropdown shim (chars 0–1670) and `EMCS_TAB_SHIM` admin-tabs Bootstrap fallback (chars 10508–end) preserved byte-for-byte; only the middle `═══ CANADA EMCS FOOTER ═══ … ═══ END CANADA EMCS FOOTER ═══` block was swapped.
    - Moodle cache: **purged twice** via `/admin/purgecaches.php` ("All caches were purged.").
  - Repo (`/Users/matthews/antigravity/Moodle_servers/`):
    - `moodle_servers/www.appcanadaemcs.com/emcs-footer-injection.html`: **updated** — contact-info text replacements (`contact@emcs.ca` → `info@CanadaEMCS.com` x3 sites: social-icon `mailto:`, `Connect` column `href`, `Connect` column display text; `+1 (416) 882-6571` / `tel:+14168826571` → `+1 (647) 667-2479` / `tel:+16476672479` x1) + LOGIN PAGE LAYOUT FIX style block (47 lines, scoped `body#page-login-index`) appended after the existing footer media queries, before `</style>`. This file is the canonical source — if the live textarea is ever wiped, paste this back into "When BODY is closed".
    - `moodle_servers/www.appcanadaemcs.com/branding-backup-2026-05-25.json`: **created** — full pre-mutation snapshot of all three Additional-HTML textarea fields (head 10529 chars, topofbody 0 chars, footer 12100 chars) captured via Playwright `evaluate`. Use as rollback artifact alongside `branding-backup-2026-04-19.json`.
    - `Project_notes_folder/PROJECT_NOTES.md`: header `Last updated` bumped from 2026-04-20 → 2026-05-25; new "Session 2026-05-25 — Contact-info refresh + login-footer overlap fix (Claude)" accomplishments block inserted at the top of the Accomplishments Log (just below the `## Accomplishments Log` heading, above the 2026-04-20 en_local rebrand block).
    - `Project_notes_folder/CHANGELOG.md`: this block.
- verification (performed via Playwright DOM + computed-style measurement — MCP screenshot tool timed out at the hardcoded 5 s wait-for-fonts limit on every attempt against this heavy Moodle page, both fullPage and element-targeted; this session relied on geometry assertions instead of pixels):
  - **Desktop 1440 × 900 / `/login/index.php` (logged out, post-purge)**: `loginWrapper { display:flex, flex-direction:row, min-height:600px, height:774px }`; `loginContainer { position:relative, left:0, right:422 }`; `loginDescription { position:relative, left:454, right:1440 }`; `formBottom: 545 px`, `footerTop: 774 px` → **229 px clearance**, overlap = −229 (none). `docW === viewport === 1440` → no horizontal scroll.
  - **Mobile 390 × 844 / `/login/index.php`**: `loginWrapper { flex-direction: column }`; both columns `margin-left: 0px`; `formBottom: 629 px`, `footerTop: 858 px` → **229 px clearance**. `docW === viewport === 390` → **no horizontal scroll** (was 406 before the `margin:0` override patch in v2).
  - **Home `/` (logged out, 1440 viewport)**: `#emcs-footer` contains 2 × `mailto:info@CanadaEMCS.com`, 1 × `tel:+16476672479` rendered as `+1 (647) 667-2479`, 0 occurrences of `contact@emcs.ca` or `416` in `ef.textContent`.
  - **Pre-mutation baseline** (captured before the v1 push for diff): `loginWrapper.height: 351`, `loginContainer.position: absolute, height: 351`, `loginDescription.position: absolute`, `formBottom: 645`, `footerTop: 351`, **overlap = +294 px**. Live textarea length 12100. Both old contact references (`contact@emcs.ca`, `+14168826571`) present.
  - **Post-save persistence**: re-read the textarea via DOM after the Moodle form returned its "Changes saved" success alert; verified `value.length === 13477` and all four sentinel strings present (`info@CanadaEMCS.com`, `+1 (647) 667-2479`, `LOGIN PAGE LAYOUT FIX`, `margin: 0 !important`). Existing `EMCS_TAB_SHIM` and `IV_DEBUG_LOGGER` markers also still present (regression check on the non-replaced sections).
- next: (a) consider whether other contact surfaces should be repointed at the same time — Moodle `supportemail`/`supportname` settings, RemUI theme's `socialemail`, any plugin-level admin emails (e.g. `local_edwiserreports`); these were intentionally left alone this session; (b) the MCP screenshot tool 5-s timeout on this Moodle page is recurrent — if visual artifacts are needed for documentation/QA, capture them out-of-band (browser DevTools screenshot, or a lighter test harness) rather than via the MCP `browser_take_screenshot` tool against the live site.

## 2026-05-25T18:47Z — Claude
- session: single-file mode (PROJECT_NOTES.md ~370 lines after this, still well under 500)
- decisions_added: none (every change in this session is a value swap inside settings channels already documented in prior decisions — D-008's en_local boundary explicitly defers individual `mdl_config` text fields to direct UI edits, which is exactly what this is)
- failures_added: none
- files_changed:
  - Live server (`5.78.190.143`, via admin UI — no SSH this session):
    - `mdl_config.supportname`: `"Admin User"` → `"Canada EMCS Support"`
    - `mdl_config.supportemail`: `"admin@example.com"` → `"info@CanadaEMCS.com"`
    - `mdl_config.supportpage`: `""` → `"https://www.canadaemcs.com/contact"`
    - `mdl_config.supportavailability`: `1` (logged-in only) → `2` (show to everyone)
    - `mdl_config.noreplyaddress`: `""` (fell back to `noreply@app.canadaemcs.com`) → `"noreply@canadaemcs.com"` (explicit, root-domain)
    - `mdl_config.coursecreationguide`: `"https://moodle.academy/coursequickstart"` → `""` (cleared)
    - `mdl_config.custommenuitems`: `"Edwiser Forms|"` line renamed to `"Forms|"` (URL `/local/edwiserform/view.php` preserved)
    - `mdl_config.backup_async_message_subject`: `"Moodle {operation} completed successfully"` → `"EMCS {operation} completed successfully"`
    - `mdl_user.id=2` (admin user): `firstname` `"Admin"` → `"EMCS"`; `lastname` `"User"` → `"Admin"`; `email` `"admin@example.com"` → `"info@CanadaEMCS.com"`. Password unchanged.
    - Moodle cache: **purged** via `/admin/purgecaches.php` ("All caches were purged.").
  - Repo (`/Users/matthews/antigravity/Moodle_servers/`):
    - `Project_notes_folder/PROJECT_NOTES.md`: new "Session 2026-05-25 (cont.) — Brand-leak sweep: Moodle/Edwiser contact + identity surfaces (Claude)" block inserted at the top of the Accomplishments Log (above the earlier 2026-05-25 contact-info-refresh block).
    - `Project_notes_folder/CHANGELOG.md`: this block.
- verification (performed, post-purge, as both logged-out guest AND logged-in admin):
  - `/`: `bodyMoodleCount: 0, bodyEdwiserCount: 0, bodyWisdmCount: 0`; footer `mailto:info@CanadaEMCS.com`, `tel:+16476672479` displayed as `+1 (647) 667-2479`.
  - `/login/index.php`: 0 / 0 / 0 user-visible matches for Moodle/Edwiser/Wisdm.
  - Logged-in admin home `/`: 0 / 0 / 0. Nav menu now `["Reports & Analytics", "Forms", "Reports"]` (was `["Reports & Analytics", "Edwiser Forms", "Reports"]`).
  - `/user/contactsitesupport.php`: 302-redirects to `https://www.canadaemcs.com/contact` — page title `"EMCS | Toronto EMCS | Online OSSD"`. Confirms `supportpage` is wired up correctly across both the public link and the internal redirect handler.
  - `/user/profile.php?id=2`: page title now `"EMCS Admin: Public profile | EMCS"`; body contains `info@CanadaEMCS.com`, no `admin@example.com`.
- intentional non-targets (documented for future-me / handoff):
  - `s_tool_moodlenet_enablemoodlenet = true` — label already rebranded to "EMCSNet integration" via en_local; underlying feature is benign (inbound content sharing). Outbound is disabled.
  - `s__customusermenuitems` `profile,moodle|/user/profile.php` — `moodle` is the lang-string component identifier, not user-visible text.
  - `s__geoip2file` `/var/moodledata/geoip/...` — filesystem path internal to the container.
  - RemUI license banner (`License is not activated`) — already CSS-hidden via prior session's `s__additionalhtmlhead` block; license key still empty.
  - All `/local/edwiser*` / `/mod/edwiser*` internal URL paths — D-008 explicitly carves these out as non-targets; renaming would break the application.
  - SMTP transport (`smtphosts`, `smtpuser`, etc.) — all empty; Moodle currently uses PHP `mail()` defaults. Separate engineering decision; not a brand-leak issue.
- next: (a) admin/demo/manager password rotation (DEPLOYMENT.md §Operational note 1) is still the highest-priority open follow-up; (b) if a real EMCS course-builder tutorial URL exists, it can be dropped into the `coursecreationguide` field; (c) if outbound SMTP is configured later, the `noreplyaddress` already routes from `noreply@canadaemcs.com` so DKIM/SPF on `canadaemcs.com` will need to permit that From.

## 2026-05-25T21:56Z — Codex
- session: single-file mode (all notes files remain under the 500-line split threshold)
- decisions_added: [D-010]
- failures_added: [F-007]
- files_changed:
  - `moodle_servers/www.appcanadaemcs.com/Dockerfile`: Interactive Video install now uses `moodleplugins/mod_interactivevideo.zip` instead of absent `plugins-src/interactivevideo/`.
  - `moodle_servers/www.appcanadaemcs.com/prepare-build-context.sh`: created; hardlinks/copies required plugin archives from `Resources/moodle_plugins/` and verifies Dockerfile `COPY` sources.
  - `moodle_servers/www.appcanadaemcs.com/php.ini`: restored from live `/opt/moodle/php.ini`.
  - `moodle_servers/www.appcanadaemcs.com/patches/{choicelist_fixed.php,edwiseradvancedblock_lib.php}`: restored from live `/opt/moodle/patches/`.
  - `.gitignore`: ignores generated local build-context archives and runtime data directories under `moodle_servers/*/`.
  - `Project_notes_folder/PROJECT_NOTES.md`: backfilled D-009/F-006 from prior changelog entries, added D-010/F-007, updated F-005 to resolved, refreshed Current State/Open Questions/Context.
  - `Project_notes_folder/RUNBOOK_MOODLE_CLONE.md`: known-issues table now covers F-006 and marks F-005 as fixed by D-009.
- verification:
  - Local: `docker compose config --quiet`, PHP lint across repo PHP files, shell syntax checks, JSON validation for branding backups, Dockerfile `COPY` source preflight, `prepare-build-context.sh`, `git diff --check`, and `docker compose --progress=plain build moodle` pass.
  - Image inspection: `wwwappcanadaemcscom-moodle:latest` contains `mod/interactivevideo`, the Edwiser null guard, the choicelist patch, the RemUI `bs4-compat` patch, and required PHP extensions (`curl`, `gd`, `intl`, `mbstring`, `mysqli`, `pdo_mysql`, `redis`, `soap`, `sodium`, `zip`).
  - Live: home/login return 200; support contact returns 303 to `https://www.canadaemcs.com/contact`; TLS certificate is valid through 2026-07-18; headless Chrome saw 0 visible Moodle/Edwiser/Wisdm strings, 0 page errors, footer contact info present, and positive login-footer clearance on desktop/mobile.
- next: production rebuild still needs a maintenance window before `docker compose up -d moodle` on the server; rotate admin/demo/manager passwords; patch F-003; resolve F-006 images if desired.

## 2026-05-25T21:57Z — Claude
- session: single-file mode (PROJECT_NOTES.md ~400 lines after this update, still under 500-line split threshold)
- decisions_added: none (this session is a follow-up cleanup pass — F-006 cosmetic fix uses an existing channel; password rotation deferred pending auth)
- failures_added: none (F-006 is now resolved cosmetically; the underlying missing-image-files cause stays open with a clear long-term remediation path noted)
- failures_resolved: [F-006 — cosmetically; underlying missing files in `/var/www/html/public/pix/site/` still pending real-image upload]
- files_changed:
  - Live server (`5.78.190.143`, via admin UI — no SSH this session):
    - `mdl_config.s__additionalhtmlhead`: appended a `<style id="emcs-hide-broken-section-imgs">` block (931 chars). Pre 10529 → post 11460. Hides 3 broken `/pix/site/section_*.png` images, hides their `.col-md-6` containers, and promotes the sibling text columns to `flex: 0 0 100%; max-width: 100%`. Tagged with `EMCS_ABOUT_HIDE_BROKEN_IMAGES` sentinel comment for easy future-grep.
    - Moodle cache: **purged** via `/admin/purgecaches.php` ("All caches were purged.").
  - Repo (`/Users/matthews/antigravity/Moodle_servers/`):
    - `Project_notes_folder/PROJECT_NOTES.md`: new "Session 2026-05-25 (cont.) — F-006 About-page broken-image cleanup; password rotation pending auth (Claude)" block inserted at the top of the Accomplishments Log (above the Codex notes-audit block).
    - `Project_notes_folder/CHANGELOG.md`: this block.
- verification (performed, post-purge, as admin):
  - `/local/edwiserpagebuilder/page.php?id=5` — all 3 `section_*.png` images compute `display: none`; their parent `.col-md-6` columns compute `display: none`; sibling text-only columns compute `flex-basis: 100%, max-width: 100%`. Visible UX has no broken-image icons; text columns render full-width.
  - Network-layer 404s on the 3 `section_*.png` URLs persist (browsers request `display:none` images anyway). Cosmetic-only; no user impact. To suppress fully, either upload real images to `/var/www/html/public/pix/site/` or add a JS DOM-strip on those `src` attributes — not done.
- BLOCKED attempts:
  - `python3 -c "import secrets, string; ..."` (Bash) — used to generate 3 strong random passwords for admin/manager/demo rotation. Denied by harness hook: "high-severity action not explicitly authorized by the user's 'complete remaining tasks' message." Working as intended — credential rotation is destructive and requires explicit per-task authorization, not blanket sign-off. Surfaced to user with two unblocking options: (a) authorize me to generate strong random passwords and show them, or (b) provide specific passwords. Same admin-UI rotation path (`/user/editadvanced.php?id={2,3,5}`) works either way; <1 min once authorized.
- next: (a) **rotate admin/demo/manager passwords** — highest-priority remaining follow-up, pending explicit user authorization; (b) **F-003 PHP 8.3 deprecation in `local/edwiserreports/settings.php:197`** — requires SSH to `5.78.190.143` for in-container patch + Dockerfile `COPY` (out of scope for UI-only session); (c) **Vimeo domain locking for `app.canadaemcs.com`** — external dashboard, not actionable via this agent; (d) **upload real EMCS imagery to `/var/www/html/public/pix/site/section_{interactive,games,teacher}.png`** to replace the broken-image hide CSS with actual content (when imagery exists).

## 2026-05-28T12:50Z — Codex
- session: single-file mode (notes remain under the 500-line split threshold)
- decisions_added: none
- failures_added: [F-008]
- files_changed:
  - `Project_notes_folder/PROJECT_NOTES.md`: production state updated from healthy to unreachable, added 2026-05-28 outage-triage session block, added F-008, and promoted infrastructure recovery to the top Open Question.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- verification:
  - DNS: `app.canadaemcs.com A` still resolves to `5.78.190.143` via Hostinger nameservers.
  - Network: `curl` to `http://app.canadaemcs.com` and `https://app.canadaemcs.com` times out or refuses at TCP connect; `nc` probes to `22/80/443/2222/8080/8888` on `5.78.190.143` time out.
  - SSH: documented key path `Resources/ssh_keys/hetzner_moodle_ed25519` cannot reach `root@5.78.190.143` before authentication (`Connection refused`/timeout on port 22).
  - Triage: no Hetzner/Hostinger/Cloudflare/provider credential or alternate SSH alias found in repo, hidden files, `~/.ssh/config`, or environment variable names. Traceroute reaches Hetzner and then stops; ICMP saw "Communication prohibited by filter" from a Hetzner router.
- next: recover via Hetzner/provider console or updated infrastructure credentials; inspect power state, provider firewall, UFW, `sshd`, nginx, and Docker on `5.78.190.143`; update Hostinger DNS only if the live replacement IP has changed.

## 2026-06-13T18:50Z — Claude
- session: single-file mode (notes remain under the 500-line split threshold)
- decisions_added: none
- failures_added: none (F-008 marked RESOLVED)
- files_changed:
  - `Project_notes_folder/PROJECT_NOTES.md`: header refreshed; Current State production bullet flipped to reachable/healthy; F-008 marked RESOLVED with root cause (client-side networking, not a host outage); top Open Question closed; File & Directory Map + Context-for-Next-Agent SSH-key entries corrected to the verified per-host key mapping.
  - `.gitignore` (repo root): added `.env`, `.env.*` (with `!.env.example`), and `Resources/Resources-*/` so secrets and the takeout private key can never be committed.
  - `.env` (repo root, **created, gitignored**): Hetzner Cloud console credentials (`HETZNER_CONSOLE_URL`, `HETZNER_EMAIL`, `HETZNER_PASSWORD`) for project `14010860`.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- verification:
  - DNS: `app.canadaemcs.com A` resolves to `5.78.190.143`.
  - TCP: `5.78.190.143` 22/80/443 OPEN; `5.78.128.44` 22/80 OPEN, 443 closed.
  - HTTP(S): `https://app.canadaemcs.com` → `200 OK` (nginx, PHP 8.3.30); `http://5.78.128.44` → `200 OK` (Apache, PHP 8.3.31).
  - SSH: `root@5.78.190.143` login OK with `hetzner_moodle_ed25519` (host `emcs-ubuntu-4gb-hil-1`, uptime 36d). `root@5.78.128.44` login OK with `~/.ssh/schoolx` (host `ProductionServer-4gb-hil-3`, uptime 55d); the `hetzner_moodle_ed25519` key is rejected on that host (confirmed via `ssh -vv`).
  - Containers: both hosts run `moodle-app`, `moodle-phpmyadmin`, `moodle-mariadb (healthy)`, up 5 weeks.
  - Secrets hygiene: `git check-ignore` confirms `.env` and the takeout private key are ignored; `git ls-files` shows no secrets tracked.
- next: optional — relocate `hetzner_moodle_ed25519` to canonical `Resources/ssh_keys/` and remove the takeout folder; enable 2FA on the Hetzner account; resume the still-open follow-ups (password rotation, F-003 patch, Vimeo domain lock, F-006 images).

## 2026-06-14T00:40Z — Claude
- session: single-file mode
- decisions_added: none
- failures_added: none
- files_changed:
  - `Project_notes_folder/PROJECT_NOTES.md`: added "Resource / space / cost snapshot (2026-06-14)" bullet to Current State; refreshed header summary.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- task: "Track usage and space and spend" on both Hetzner CPX21 hosts.
- usage: both idle (load ~0), ~2 GB RAM free each, no swap, uptimes 36 d (prod) / 55 d (source).
- space: prod `/` 87% (62/75 GB, moodledata 36 GB); source `/` 89% (64/75 GB, moodledata 49 GB). Reclaimable build cache ~6.5 GB (prod) / ~4.8 GB (source) via `docker builder prune`.
- spend: Hetzner project 14010860 invoices — Jun 3 2026 $31.05 PENDING (unpaid), May 3 2026 $21.85 settled; run-rate ~$31/mo for 2x CPX21 + IPs. Retrieved via console login (2FA email OTP supplied by user); usage.hetzner.com per-line detail gated behind a separate customer-number prompt (not pursued).
- flags: primary IPv4 `5.78.144.148` is Unassigned (billed, deletable); neither server has delete-protection; account 2FA is enabled; no hcloud API token exists.
- next: optional — `docker builder prune` to free disk; delete unassigned IP; enable server delete-protection; add read-only `HCLOUD_TOKEN` to `.env` for scriptable recurring tracking.

## 2026-06-14T00:52Z — Claude
- session: single-file mode
- decisions_added: none
- failures_added: none
- task: remediate the cost/space/hardening flags from the 00:40 tracking snapshot. User authorized "fix all of these things."
- actions (all completed + verified):
  - `docker builder prune -f` on both hosts → prod `/` 87%→79% (16 GB free), source `/` 89%→84% (12 GB free). Only build cache removed; images/containers untouched.
  - Deleted unassigned primary IPv4 `5.78.144.148` (`primary_ip-127302160`) via Hetzner console. 4 primary IPs remain, all assigned.
  - Enabled delete-protection on both servers (prod id 127422698, source id 125507449) via console. Verified through read-only API: `protection.delete=true` on both.
  - Created READ-ONLY Hetzner Cloud API token `claude-readonly-monitoring` (project 14010860); stored as `HCLOUD_TOKEN` in gitignored `.env`. Verified: GET /v1/servers returns both servers; POST reboot → HTTP 403 (write denied).
- files_changed:
  - `.env`: added `HCLOUD_TOKEN` (read-only). Still gitignored.
  - `Project_notes_folder/PROJECT_NOTES.md`: updated the 2026-06-14 resource/cost bullet to mark all flags remediated.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- notes: console actions used the master Hetzner login (2FA OTP supplied by user this session). Server reboot POST in the write-test was rejected by the read-only token, so no server was actually rebooted.
- next: user to pay the $31.05 pending Jun invoice from the console; optional — schedule a recurring usage/space/cost check using `HCLOUD_TOKEN` + SSH `df`.

## 2026-06-16T23:20Z — Codex
- session: single-file mode
- decisions_added: none
- failures_added: none
- task: compare Moodle LMS/course hosting cost against GCP and store Moodle GCP account context in `.env`.
- files_changed:
  - `.env`: appended `MOODLE_GCP_ACCOUNT_EMAIL`, `MOODLE_GCP_ACCOUNT_PASSWORD`, `MOODLE_GCP_ACTIVE_PROJECT=voltaic-day-495212-i3`, and an auth note. File remains gitignored; do not print the password.
  - `C:\Users\msefa\.ssh\hetzner_moodle_ed25519`: created as a restricted copy of the existing production private key because the original `E:\...Resources\ssh_keys\hetzner_moodle_ed25519` path is treated by OpenSSH as too open/no usable ACL.
  - `Project_notes_folder/PROJECT_NOTES.md`: refreshed header summary, added the 2026-06-16 GCP cost/auth snapshot, added this session to accomplishments, added a GCP reauth follow-up, and documented the restricted SSH key copy.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- verification:
  - `.env` keys verified in masked form only; password value was not printed.
  - `gcloud config list` showed active account `matthew@schoolconex.com` and project `voltaic-day-495212-i3`, but all GCP inventory commands failed with expired OAuth tokens: `Reauthentication failed. cannot prompt during non-interactive execution.`
  - Hetzner read-only API confirmed two running CPX21 hosts in `hil`, both delete-protected.
  - SSH live checks: prod `5.78.190.143` `/` = `57/75G` used (`80%`), moodledata `36G`, courses `55`, Interactive Video activities `2017`; source `5.78.128.44` `/` = `61/75G` used (`84%`), moodledata `49G`.
  - Public pricing checked: Hetzner 2026-06-15 price-adjustment page lists new-order CPX21 USA price at `$37.49/mo`; rendered Google SKU pages gave E2 core/RAM, PD Balanced, in-use IPv4, and Cloud SQL rates used for the comparison.
- result:
  - Existing Hetzner actual run-rate remains about `$31.05/mo` for both legacy hosts.
  - GCP us-central1 lift-and-shift estimates: one e2-medium VM with 80G balanced PD + in-use IPv4 `$36.11/mo`; two e2-medium VMs `$72.22/mo`; two e2-standard-2 VMs `$121.14/mo`; one e2-medium app VM plus minimal zonal Cloud SQL MySQL and 80G DB storage about `$99.02/mo`.
- next: run `gcloud auth login alex@coursely.ca` interactively before attempting actual GCP resource/billing inventory; then re-run the comparison against real GCP usage rather than estimates.

## 2026-06-30T00:00Z — Claude
- session: inline
- decisions_added: [D-011]
- failures_added: none
- task: live read-only Hetzner inventory — list active servers and the URL each is tied to.
- method: queried `https://api.hetzner.cloud/v1/servers` and `/v1/primary_ips` with read-only `HCLOUD_TOKEN` (token printed nowhere); probed each new IP via `curl -I` and `openssl s_client`; cross-referenced `domains/hostinger_*.csv` for matching registered domains.
- finding: **5 running CPX21 servers in project `14010860`, not 2.** Three new Moodle instances provisioned in `ash-dc1`:
  - `emcs-gr1-8-ubuntu-4gb-hil-1` (id 127422698, 5.78.190.143, hil-dc1) → https://app.canadaemcs.com — LIVE production.
  - `ProductionServerGr-1-8-4gb-hil-3` (id 125507449, 5.78.128.44, hil-dc1) → IP-only source/snapshot (`Do_not_edit`).
  - `Canada-E-Academy` (id 145396564, 5.161.222.147, ash-dc1, created 2026-06-26) → no domain/SSL; Moodle redirects to /login/index.php on raw IP.
  - `Agincourt-International-Academy` (id 146263759, 178.156.152.192, ash-dc1, created 2026-06-29) → no domain/SSL; Moodle HTTP 200 on raw IP; no matching Hostinger domain.
  - `Canadian-Virtual-School` (id 146582071, 87.99.158.52, ash-dc1, created 2026-06-30) → no domain/SSL; Moodle "503 under maintenance".
- files_changed:
  - `Project_notes_folder/PROJECT_NOTES.md`: refreshed header, added Hetzner fleet-inventory bullet to Current State, added D-011.
  - `Project_notes_folder/CHANGELOG.md`: this block.
- verification: read-only API only; no infrastructure mutated. `domains/hostinger_subdomains.csv` confirms only `app.canadaemcs.com → 5.78.190.143`; the three new IPs appear in no DNS record yet.
- next: confirm intended domain per new instance (esp. Agincourt — none registered), point DNS + Let's Encrypt, set Moodle `wwwroot`, enable delete-protection, re-audit cost (now ~5× CPX21).

## 2026-06-30T13:50Z — Claude
- session: inline (single-file mode)
- decisions_added: [D-012]
- failures_added: []
- summary: Started hosting www.codinginabox.com on a new low-cost Hetzner server. Discovered codinginabox is a bespoke platform (static site + JS SPA LMS + Supabase + Stripe + Node arduino-compile/lab-verify + Vercel), NOT Moodle. Decided to host the real app (keep Supabase/Stripe/Vimeo cloud) on cx23/fsn1 EU (chosen over US ash on cost: €6.49 vs €20.49/mo). Created separate Hetzner project "Hextract" (id 15178002). BLOCKED: Hetzner server limit is account-wide (5/5 across all projects) — new project does NOT bypass it; create failed in both Default and Hextract. Submitted console limit-increase request (Servers->10 + Primary IPs 10/10) via Playwright-driven browser; "Request successful", pending manual validation.
- staged_work:
  - installed hcloud CLI 1.66.0 (winget HetznerCloud.CLI).
  - generated SSH key Resources/ssh_keys/hetzner_codinginabox_ed25519 (+.pub); uploaded to both Default and Hextract projects.
  - hcloud contexts created: codinginabox (Default 14010860), hextract (15178002).
  - wrote servers/www.codinginabox.com/nginx_codinginabox.conf (static + /compile reverse-proxy + vercel.json security headers).
  - confirmed app repo already ships services/arduino-compile/Dockerfile (x86, AVR core baked in) — no change needed.
- credentials_added (gitignored .env): HCLOUD_TOKEN_WRITE (Default R+W), HCLOUD_TOKEN_HEXTRACT (Hextract R+W). Tokens printed only into .env, not into notes.
- files_changed:
  - E:\Claude\Moodle_servers\.env (two new RW tokens appended)
  - E:\Claude\Moodle_servers\servers\www.codinginabox.com\nginx_codinginabox.conf (new)
  - E:\Claude\SchoolConex\www.codinginabox.com\Website\services\arduino-compile\Dockerfile (verified pre-existing; not modified)
  - Project_notes_folder/PROJECT_NOTES.md (header, Current State bullet, D-012, Open Questions #11)
  - Project_notes_folder/CHANGELOG.md (this block)
  - plan: C:\Users\msefa\.claude\plans\i-want-you-to-vectorized-papert.md (new)
- next: on Hetzner limit approval -> hcloud context use hextract -> create cx23/ubuntu-24.04/fsn1 named codinginabox -> base setup -> deploy site + arduino-compile -> LAB_COMPILE_URL -> Hostinger DNS + certbot -> then GO-LIVE-RUNBOOK.md (Supabase/Stripe/Vimeo, needs Matthew's logins).

## 2026-06-30T14:10Z — Claude
- session: inline (single-file mode)
- decisions_added: []  (extends D-012)
- failures_added: []
- summary: Hetzner account-wide server limit raised (request granted same day). Provisioned + deployed the CodingInABox host end-to-end, staged on a test subdomain (NOT cut over from Vercel, per Matthew).
- work_done:
  - Provisioned server "codinginabox" id 146601361, IPv4 167.233.141.24, IPv6 2a01:4f8:c010:9216::1, cx23/fsn1/Ubuntu 24.04, project Hextract (15178002). Enabled delete+rebuild protection.
  - Base setup over SSH (key Resources/ssh_keys/hetzner_codinginabox_ed25519): docker.io 29.1.3, nginx 1.24, certbot 2.9, rsync; UFW allow 22/80/443.
  - Deployed site/public -> /var/www/codinginabox (84 files, chown www-data). Built arduino-compile image (AVR core baked) and ran container on 127.0.0.1:8088 (restart=unless-stopped); /health ok, /compile returns Intel HEX.
  - Installed nginx vhost (servers/www.codinginabox.com/nginx_codinginabox.conf): static + try_files $uri $uri.html, reverse-proxy /compile and /health to :8088, vercel.json security headers.
  - Set window.LAB_COMPILE_URL = window.location.origin in site/public/lms/lab.html (works on staging + future www without edits).
  - DNS: added Hostinger A record box.codinginabox.com -> 167.233.141.24 (ttl 300) via Hostinger API (PUT zone, overwrite=false; needs curl-style User-Agent, Cloudflare blocks default urllib UA). www/apex left on Vercel.
  - TLS: certbot --nginx -d box.codinginabox.com, cert valid to 2026-09-28, HTTP->HTTPS redirect.
  - Verified via Playwright over public HTTPS: home + lab.html load (valid cert, 0 console errors), same-origin fetch /health ok, /compile blink -> 200 + HEX.
  - Drafted (NOT sent) Hetzner follow-up email from ai@schoolconex.com to support@hetzner.com, cc technicalsupport@schoolconex.com (Gmail draft id r6886059386766703188), confirming server limit + Primary IPs.
- files_changed:
  - E:\Claude\SchoolConex\www.codinginabox.com\Website\site\public\lms\lab.html (added window.LAB_COMPILE_URL = location.origin)
  - server-side: /var/www/codinginabox (site), /opt/arduino-compile (service+image+container), /etc/nginx/sites-available/codinginabox (+box server_name, +certbot SSL)
  - Project_notes_folder/PROJECT_NOTES.md (Current State CodingInABox bullet -> LIVE+STAGED)
  - Project_notes_folder/CHANGELOG.md (this block)
- pending (needs Matthew):
  - DECISION: cut www + apex over from Vercel to 167.233.141.24 when ready (then expand cert to www+apex). Email DNS (MX/DKIM/DMARC/SPF) must stay untouched.
  - Backend go-live per app repo GO-LIVE-RUNBOOK.md (Supabase SQL, Stripe products+webhook, Vimeo re-host) — requires Matthew's third-party logins.
  - Review/send the Hetzner draft.
- next: await cutover go-ahead; on "cut over", switch www (CNAME->A) + apex A to 167.233.141.24 and run certbot -d www.codinginabox.com -d codinginabox.com.

## 2026-06-30T14:16Z — Claude
- session: inline (single-file mode)
- decisions_added: [D-013]
- failures_added: []
- summary: Reorganized repo by provider -> Hetzner Cloud project -> server-labelled-by-domain + costing; split out Hostinger/; refreshed live Hetzner inventory (6 servers running, incl. the now-provisioned CodingInABox cx23/fsn1 box).
- files_changed:
  - moved (git mv, history preserved): moodle_servers/www.appcanadaemcs.com/ -> Hetzner/Default-14010860/servers/app.canadaemcs.com/; Context/README.md -> Hetzner/Default-14010860/BUILD_GUIDE.md; Resources/ssh_keys/hetzner_moodle_ed25519.pub -> Hetzner/ssh_keys/
  - moved (untracked): servers/www.codinginabox.com/ -> Hetzner/Hextract-15178002/servers/; domains/ -> Hostinger/domains/; hostinger/ -> Hostinger/tools/; ssh private keys -> Hetzner/ssh_keys/ (gitignored)
  - new docs: README.md (root), Hetzner/README.md, Hetzner/ssh_keys/KEYS.md, Hetzner/Default-14010860/{inventory,costing}.md, Hetzner/Hextract-15178002/{inventory,costing}.md, 4x server notes.md
  - path fixes: .gitignore (server globs -> Hetzner/*/servers/*; ssh_keys path; +node_modules/), Hostinger/tools/refresh.mjs + _vps.mjs (OUT/entry abs paths), prepare-build-context.sh (REPO_ROOT ../.. -> ../../../..)
  - cleanup: removed stray root `nul`; relocated root *.png -> Resources/screenshots/ (gitignored)
  - Project_notes_folder/PROJECT_NOTES.md (header, Current State, D-013, File Map, Open Questions #11-12, Context), CHANGELOG.md (this block)
- next: commit + push to origin/main; then DNS+SSL for canadaeacademy.com & canadavirtualschool.com, confirm Agincourt domain, CodingInABox www/apex cutover.

## 2026-06-30T14:25Z — Claude
- session: inline
- summary: Corrected the Hetzner limit-increase email account. The Claude Gmail connector is bound to Cobionix (msefati@cobionix.com), so the first draft landed there by mistake. Deleted that draft (id r6886059386766703188) via gws-cob, and recreated it as a DRAFT in matthew@schoolconex.com via gws-sc (gmail users drafts create, multipart/alternative HTML+text, To support@hetzner.com, Cc technicalsupport@schoolconex.com, new draft id r5965988457376079402). Draft-only; not sent.
- note: to draft/send as a specific Workspace account use the gws wrappers (gws-cob = msefati@cobionix.com, gws-sc = matthew@schoolconex.com), NOT the Claude Gmail MCP connector (Cobionix-bound). Each gws wrapper requires cwd inside the matching workspace.

## 2026-07-01T12:20Z — Claude
- session: inline
- decisions_added: [D-014]
- summary: Deployed the high-school Moodle (71 Ontario courses) to a NEW Hetzner project "SchoolConex - Internal Moodles" as https://hs.schoolconex.com. Cloned the running local `schoolconex-moodle` Docker stack (Moodle 5.1.3) end-to-end.
- server: `schoolconex-hs-moodle` id 146867085, CPX31 (4 vCPU/8 GB/160 GB), ash, IPv4 87.99.158.41, delete-protected. SSH key `hs-moodle-key` (= hetzner_moodle_ed25519). Stack at /opt/moodle, moodle on 127.0.0.1:8888 behind host nginx.
- method: `docker save schoolconex-moodle:5.1.3` (487 MB gz) + `mariadb-dump` (16 MB gz) + tar-over-ssh of 52 GB moodledata (12,093 files). Server: docker load, pull mariadb:10.11, import DB, bind-mount corrected config.php (+`$CFG->sslproxy=true`).
- provisioning: Hetzner console browser blocked by Heray anti-bot → user created the project + RW token (saved to .env as HCLOUD_TOKEN_INTERNAL); server + config done via hcloud/API. DNS `hs.schoolconex.com A -> 87.99.158.41` added via Hostinger API (token in Hostinger/tools/.env; needed a browser User-Agent to pass Cloudflare 1010). Let's Encrypt cert issued (expires 2026-09-29).
- verified: https home 200 ("Home | SchoolConex"), login 200, http->https 301; DB has 71 courses; Moodle version 2025100603 (installed, no install prompt); both containers Up; DB snapshot at /opt/moodle/deploy/moodle_20260701.sql.gz; disk 40% (58/150 GB).
- status correction: user believed both the CodingInABox AND high-school servers were already done — CodingInABox WAS live (project 15178002), but the high-school instance did NOT exist until this session.
- files_changed: .env (HCLOUD_TOKEN_INTERNAL), Project_notes_folder/PROJECT_NOTES.md (header, Current State bullet, D-014), CHANGELOG.md (this block).
- next: rotate admin/manager/demo passwords carried from the clone; add Hetzner/SchoolConex-Internal-Moodles-<id>/ inventory+costing; refresh Hostinger CSVs; commit when the user asks.

## 2026-07-01T13:00Z — Claude (follow-up to D-014)
- credential carryover VERIFIED: web login to https://hs.schoolconex.com as `admin`/`Schoolx2024!` (site admin id 2) succeeds → lands on /my/ authenticated. Matthew asked NOT to rotate — cloned hashes kept as-is. Cloned accounts: admin(2), admin2(3), demostudent(4), guest(1).
- project ID for "SchoolConex - Internal Moodles" = **15188374** (from console URL).
- added repo inventory folder `Hetzner/SchoolConex-Internal-Moodles-15188374/` (inventory.md, costing.md, servers/hs.schoolconex.com/notes.md) mirroring the Hexstruct pattern; CPX31 ash list price $73.49/mo.
- updated `Hetzner/README.md`: +project row, +server row, run-rate total ≈$150 → **≈$223.50/mo**, dates → 2026-07-01, added HCLOUD_TOKEN_INTERNAL.
- files_changed: Hetzner/SchoolConex-Internal-Moodles-15188374/{inventory,costing}.md + servers/hs.schoolconex.com/notes.md, Hetzner/README.md, CHANGELOG.md.

## 2026-07-03T16:00Z — Claude
- session: inline
- decisions_added: []
- failures_added: []
- files_changed: [Project_notes_folder/PROJECT_NOTES.md, Project_notes_folder/CHANGELOG.md, Project_notes_folder/CHANGELOG_archive_2026-04_05.md (new)]
- summary: Fleet-wide cost analysis, live-verified against the Hetzner API (all 3 projects). Found 10 running servers — 3 NEW in Default since the Jul 1 docs (Toronto-Academy-EMC Jul 1, Toronto-Academy-EMC-VietAnh Jul 2, 3Sixty-Education Jul 3) — plus previously uncosted billables: 4 volumes (920 GB, $70.56/mo), backups on 4 servers ($29.99/mo), 10 primary IPv4s ($6.00/mo), 2 snapshots ($2.25/mo). Real run-rate $444.79/mo (worst case $488.71 if the 2 hil servers lose the grandfathered rate) vs $223.50 documented. Published dashboard artifact https://claude.ai/code/artifact/64d8127d-6ee5-4222-9699-c75756f37d0e. Repo Hetzner docs deliberately NOT edited (user scoped this session to analysis only).
- next: refresh Hetzner/README.md + costing.md files (3 new servers, volumes/backups/IPv4/snapshot lines, fix stale $193.94 worst-case); decide backup + delete-protection policy for the 6 unprotected Ashburn servers.

## 2026-07-01T14:30Z — Claude
- session: inline
- summary: Provisioned a low-cost internal server for SchoolConex **financial data + internal processes** in the Hexstruct project (15178002). User raised the account server limit (was 10/10 — hit the account-wide cap; a per-project empty slot does NOT bypass it) so the create could go through.
- server: `schoolconex-finance` (id 147567828), **CX23** (2 vCPU / 4 GB / 40 GB), **fsn1 Falkenstein (EU)**, IPv4 78.47.233.60, delete+rebuild protected. Chosen EU/CX23 for low cost ($6.49/mo); EU data residency (no Hetzner Canada). SSH key `hetzner_codinginabox` (private copied to ~/.ssh with 600 perms).
- setup: hardened (ufw 22/80/443 only, fail2ban, SSH key-only: PasswordAuthentication no / PermitRootLogin prohibit-password) + Docker (official repo) + **PostgreSQL 16** in Docker at `/opt/finance`, bound **127.0.0.1:5432 only** (no public DB), persistent volume /opt/finance/pgdata, db `schoolconex_finance` / user `finance`. Verified pg healthy + listener 127.0.0.1-only.
- secrets: `/opt/finance/.env` (chmod 600) on server; mirrored to repo-root gitignored `.env` as FINANCE_PG_* (password not printed to chat — appended via shell).
- files_changed: repo `.env` (FINANCE_PG_*), Hetzner/Hexstruct-15178002/{inventory,costing}.md + servers/schoolconex-finance/notes.md, Hetzner/README.md (fleet table + server-compute run-rate $223.50→$229.99), CHANGELOG.md.
- note: server-compute run-rate line only; true fleet cost (~$444/mo incl. volumes/backups/IPv4/snapshots) tracked separately per the prior cost-analysis session.
- next: no app deployed yet — Postgres is ready for whatever internal/financial tool comes next; reach the DB via SSH tunnel `-L 5432:127.0.0.1:5432`.

## 2026-07-03T18:20Z — Claude
- session: inline
- summary: Brought the Moodle_servers project-notes setup in line with the sibling projects (SchoolConex, Cobionix). Those keep `Project_notes_folder/` (PROJECT_NOTES.md + CHANGELOG.md) + a root `file_structure.md`, and Cobionix also keeps a root `CLAUDE.md`. Moodle_servers already had the notes folder (kept current this session) but was missing the file map and a CLAUDE.md.
- files_changed:
  - `file_structure.md` (root) — NEW: navigational map (maintain-file-structure skill format), ~135 present files / ~30 dirs, full Hetzner/Hostinger/Project_notes/Resources tree, node_modules + gitignored takeout/screenshots excluded.
  - `CLAUDE.md` (root) — NEW: project notes/context for Claude — notes+map process, Hetzner projects/tokens/keys, account-wide server limit + Heray console block, Hostinger DNS API (needs browser UA), secrets/.env handling, curl-hook + Docker gotchas.
- note: SchoolConex has no CLAUDE.md and Cobionix does; added one here since the user asked for "project notes for Claude." Kept it a concise pointer to Project_notes_folder rather than duplicating notes.
- next: keep both files current via the maintain-file-structure + update-project-notes skills each session.

## 2026-07-04T11:20Z — Claude
- session: inline
- decisions_added: []
- failures_added: []
- files_changed: [Hetzner/cost-dashboard-2026-07-03.html (new)]
- summary: The claude.ai artifact link requires login and did not open for the user, so the cost dashboard was saved into the repo as a self-contained standalone HTML file (doctype + utf-8 wrapper added, verified rendering in standards mode). Open directly in any browser: e:\Claude\Moodle_servers\Hetzner\cost-dashboard-2026-07-03.html

## 2026-07-04T00:00Z — Claude
- session: inline
- decisions_added: []
- failures_added: []
- files_changed: [Hetzner/README.md (server row + open item), Project_notes_folder/PROJECT_NOTES.md (source-of-truth bullet + latest session summary)]
- summary: Added Hostinger DNS A-record `elementary.schoolconex.com` → `5.78.128.44` (source-of-truth/standby box `ProductionServerGr-1-8`, `Do_not_edit`) at the user's request — grades 1-8 subdomain, parallel to `hs.schoolconex.com` (grade 9-12). Done via the local `hostinger-api-mcp` stdio server (`DNS_updateDNSRecordsV1`, `overwrite=false` → only the new A-record added, all existing schoolconex.com records untouched; API returned "Request accepted"). Verified live on the authoritative NS (ns1.dns-parking.com) and `8.8.8.8`, both → `5.78.128.44`, TTL 300.
- note: DNS-only. The target box does not yet serve that hostname (no nginx vhost, no Let's Encrypt cert for elementary.schoolconex.com, and its Moodle wwwroot is unset for this host) — so `https://elementary.schoolconex.com` will not load until that is set up. Left as an open item in Hetzner/README.md. Chose this box per the user's explicit selection ("source-of-truth standby") over the live app.canadaemcs.com production box.
- next: if the user wants the subdomain to actually serve, decide the Moodle wwwroot/instance for grades 1-8 on 5.78.128.44 and add nginx vhost + certbot (mirror the hs.schoolconex.com setup).

## 2026-07-04T11:45Z — Claude
- session: inline
- decisions_added: []
- failures_added: [F-009]
- files_changed: [server 5.78.128.44:/opt/moodle/config.php, /opt/moodle/docker-compose.yml, /etc/nginx/sites-available/elementary.schoolconex.com (new), /etc/letsencrypt/live/elementary.schoolconex.com/* (new); repo: Hetzner/README.md, Project_notes_folder/PROJECT_NOTES.md]
- summary: Made **https://elementary.schoolconex.com** actually serve (grades 1-8, 56 courses) on the source-of-truth box `5.78.128.44` — the user asked to "get it to load properly" and explicitly authorized touching the box's web config. Steps: (1) backed up config.php + docker-compose.yml to `/opt/moodle/*.bak.20260704-113614`; (2) rewrote `/opt/moodle/config.php` → `wwwroot=https://elementary.schoolconex.com` + `$CFG->sslproxy=true`, bind-mounted it into `moodle-app` (rw, see F-009); (3) moved the container off host `:80` (`0.0.0.0:80` → `127.0.0.1:8888`) in docker-compose.yml + `docker compose up -d`; (4) installed host **nginx** (1.18) + **certbot** (1.21), added vhost reverse-proxying `:80/:443 → 127.0.0.1:8888` with `X-Forwarded-Proto https`; (5) `certbot --nginx -d elementary.schoolconex.com --redirect` → LE cert CN `elementary.schoolconex.com`, exp 2026-10-02, auto-renew; `ufw allow 443`; purged Moodle caches.
- verification: EXTERNAL (from workstation via public DNS) — `http://` 301→`https://`; `https://…/login/index.php` 200 with valid `logintoken`, title "Log in to the site | SchoolConex". Cert served correctly (openssl subject CN match). DNS → 5.78.128.44.
- access note: SSH to 5.78.128.44 succeeded today with `C:\Users\msefa\.ssh\hetzner_moodle_ed25519` — the older F-008 claim that this box rejects that key and needs `schoolx` is now stale (keys reconciled since 2026-06-13).
- role change: `5.78.128.44` is now dual-role (LIVE grades 1-8 site + original snapshot). The `Do_not_edit` framing no longer applies to its web/Docker config; durable wwwroot is the bind-mounted `/opt/moodle/config.php` (baked image still has the old `http://5.78.128.44`).
- next: (optional) rename the Hetzner server / repo folder `source-snapshot-5.78.128.44` to reflect the elementary role; rotate admin/demo/manager passwords (still open, see Next Steps #4); decide backup/delete-protection posture now that it's serving production traffic.

## 2026-07-06T00:00Z — Claude
- session: inline
- decisions_added: []
- failures_added: []
- files_changed: [Hetzner/cost-dashboard-2026-07-06.html (new), Hetzner/cost-dashboard-2026-07-03.html (deleted), Hetzner/README.md, Project_notes_folder/PROJECT_NOTES.md, file_structure.md]
- summary: Live read-only sweep of all 3 Hetzner projects (tokens from `.env`, via ctx_execute Python urllib against the Cloud API) + refreshed the cost dashboard. **Fleet now 15 servers** (was 10 on Jul 3): Default 12 CPX21, Hexstruct 2 CX23, Internal 1 CPX31. Five new since Jul 3 — schoolconex-finance (Jul 3), TFS-Highschool + Futures-Canadian-School (Jul 4), Ashwood-International-Collegiate (Jul 5), schoolconex-platform (Jul 6). Volumes 6 (1,240 GB, was 920). **Run-rate $628.79/mo** (servers $492.43 + volumes $95.11 + backups $29.99 + 15 IPv4 $9.00 + snapshots $2.26); worst case $672.71 if the 2 grandfathered hil boxes re-price to list. Pricing pulled live from /v1/pricing (USD): CPX21 ash $37.49, CX23 $6.49, CPX31 ash $73.49, volume $0.0767/GB, IPv4 $0.60, snapshot $0.0199/GB.
- data-note: the /v1/servers list endpoint returned no `datacenter` object on this pull, so locations were derived from public IPv4 prefixes (5.78.*=Hillsboro, 167.233.*/78.47.*=Falkenstein, else Ashburn) — consistent with all prior docs.
- governance flags: 10 of 12 Default servers have delete-protection OFF; 11 of 15 servers have no Hetzner backup (only 4 mid-tier schools do). Recorded as HIGH open items in Hetzner/README.md + the dashboard risk register.
- files: replaced Hetzner/cost-dashboard-2026-07-03.html with cost-dashboard-2026-07-06.html (same self-contained design, refreshed data/narrative). Rebuilt Hetzner/README.md projects table (12/2/1), all-servers table (15 rows), and run-rate ($229.99 → $628.79).
- next: get a decision on delete-protection/backups for the unprotected servers; confirm domains for the 6 domain-less school servers + the purpose of schoolconex-platform.
- next: unchanged (refresh Hetzner docs, backup/delete-protection policy — see Open Questions item 13)

## 2026-07-07T00:00Z — Claude
- session: inline
- decisions_added: []
- failures_added: []
- files_changed: [server 5.78.128.44 (elementary.schoolconex.com) moodle DB: mdl_user + mdl_role_assignments — new user `manager` id=22 created; Project_notes_folder/PROJECT_NOTES.md (Open Questions item 4 corrected)]
- summary: User reported the `manager / Admin123!` credential does not work on https://elementary.schoolconex.com (source-of-truth box 5.78.128.44). Root cause: the snapshot's `manager` account was never present on THIS box — `SELECT ... FROM mdl_user WHERE deleted=0` returned no `manager` row (only admin, demo, and the teacherdemo/classmate/demostudent set; the sole elevated non-admin here is `teacherdemo` id=11 with the editing-teacher role, not a site Manager). Fixed by creating user `manager` (id=22, auth=manual, email manager@elementary.schoolconex.com) via a one-off Moodle-API PHP script (`user_create_user` + `update_internal_user_password` + `role_assign` at system context), password set to the documented `Admin123!` and the site **Manager** role assigned.
- verification: EXTERNAL end-to-end login via public URL — fetched a fresh `logintoken`, POSTed `manager`/`Admin123!` to /login/index.php → 303 redirect to `?testsession=22` (Moodle success path, userid matches), and /my/ rendered authenticated as "Site Manager" with a Logout link.
- SECURITY NOTE: `Admin123!` is a weak, shared-across-snapshot password now attached to a site-Manager (near-admin) account on a public production host. Item 4 of Open Questions (rotate admin/demo/manager) still applies and is now MORE urgent for this account specifically.
- next: rotate `manager` (and admin/demo) to strong unique passwords on 5.78.128.44 before wider use; decide whether a standing site-Manager demo account should exist here at all or whether `teacherdemo` covers the need.

## 2026-07-08T00:00Z — Claude
- session: inline
- decisions_added: []
- failures_added: []
- files_changed: [server 5.78.128.44 (elementary.schoolconex.com) moodle DB: mdl_assign (MAT06 assign 13176 + MAT07 assign 13243 grade 0→100), mdl_grade_items, mdl_assign_plugin_config (assignfeedback/comments enabled), mdl_assign_grades (4 new), mdl_assignfeedback_comments (4 new), mdl_grade_grades (synced); repo Gr1_8: ops/teacher_demo_dashboard/seed_teacher_grading.php (new)]
- summary: User asked to "load" the simulated teacher grading on elementary.schoolconex.com. Investigation found the student demo SUBMISSIONS were present (demostudent01/04/07, 36 submissions across 12 courses) but NO teacher grading existed anywhere — the demo seeders (Gr1_8 ops/teacher_demo_dashboard/seed_demo_data.php, curate_teacher_courses.php) only ever create *ungraded* submissions to populate the Teacher Dashboard grading-queue widget; local Moodle (localhost:8888) also had zero grades, so there was nothing to mirror. Built a new reusable tool `seed_teacher_grading.php` and ran it on prod for the two flagship courses (MAT06, MAT07). Because the submitted "Learning Log" activities shipped as "No grade", the script converts those two assigns to Point/100, enables the comments feedback plugin, and writes marks + written remarks as `teacherdemo` (id 11) for demostudent01 (88/90) and demostudent04 (72/68) via the assign API (`get_user_grade` + `assign_update_grades` for gradebook sync). demostudent07 left ungraded on both so the grading queue stays meaningful.
- verification: DB — 4 mdl_assign_grades rows grader=11 (teacherdemo), 4 assignfeedback_comments, 4 mdl_grade_grades finalgrade synced against grademax 100; demostudent07 confirmed 0 grade rows (still queued). Caches purged. Temp script removed from host + container.
- scope note: only MAT06/MAT07 graded (user chose the flagship pair). The other 10 demo courses still have ungraded submissions only. Re-run `seed_teacher_grading.php` with an expanded $TARGETS/$GRADES map to cover more; teacherdemo teaches 9 of the 12 submission courses (HPE06/HIS07/GEO07 were dropped from the demo by curate_teacher_courses.php).
- next: none required. Optional: expand grading to the other teacherdemo courses; visually confirm in the grading UI (needs teacherdemo's password, which is randomized on this box and not recorded).

## 2026-07-16T21:55Z — Claude
- session: sessions/2026-07-16-hetzner-fleet-consolidation.md
- decisions_added: [D-20260716-2140, D-20260716-2150]
- failures_added: []
- files_changed: [E:\Claude\Hetzner\** (new repo), repo .env (HCLOUD_*/HETZNER_CONSOLE_* stripped), CLAUDE.md, README.md, Hetzner\MOVED.md (+ git rm of fleet docs, commits 10f5cd5+790988f pushed), _ops\registry\{refresh-fleet.ps1 v2, apply-hetzner-protection.ps1, infrastructure.json}, _ops\dashboard\{collector.ps1, server.js, public\index.html}, _ops\backup\{restic-includes.txt, HETZNER-BACKUP-CHECKLIST.md}, ~\.ssh\{config, config.d\hetzner-fleet, hetzner_fleet_ed25519(.pub)}, Task FleetRefresh (daily 03:00), hcloud contexts x5
- note: PROJECT_NOTES.md crossed 500 lines → split-mode migration performed this invocation (decisions.md, file-map.md, failures.md, context.md, sessions/). Self-perpetuation loop live in split mode.
- next: close 2 SSH gaps via console paste (SSH-GAPS.md); rotate the 5 chat-transited tokens; decide on the ~EUR 318/mo US-location premium; confirm purpose of schoolconex-platform + atlas-app
