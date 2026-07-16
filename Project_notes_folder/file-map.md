# File & Directory Map — Moodle Servers

_Split out of PROJECT_NOTES.md on 2026-07-16. NOTE: repo root moved 2026-07-16 to E:\Claude\SchoolConex\SchoolConex_Active_servers\Moodle_servers (was E:\Claude\Moodle_servers); Hetzner fleet docs extracted to E:\Claude\Hetzner the same day._


Repo root `E:\Claude\Moodle_servers\` — **reorganized 2026-06-30 (see D-013)** into provider → project → server-labelled-by-domain. `git mv` preserved history; `.gitignore` globs + script paths updated.

- `README.md` (root) — repository orientation map.
- `Hetzner/README.md` — fleet index: every project, server, IP, linked domain, cost, SSH key (refreshed from the Hetzner API 2026-06-30).
- `Hetzner/ssh_keys/` — keys + `KEYS.md`. `hetzner_moodle_ed25519.pub` = Hetzner key `emcs-moodle` (for `root@5.78.190.143`; private in gitignored takeout, not in repo). `hetzner_codinginabox_ed25519(.pub)` = Hetzner key `hetzner_codinginabox`. `.gitignore` now protects `Hetzner/ssh_keys/*` with `!*.pub`.
- `Hetzner/Default-14010860/` — Moodle project (`14010860`): `inventory.md`, `costing.md`, `BUILD_GUIDE.md` (was `Context/README.md` — source-server build recipe), and `servers/<domain>/`:
  - `servers/app.canadaemcs.com/` — live prod build context (was `moodle_servers/www.appcanadaemcs.com/`): `DEPLOYMENT.md`, `Dockerfile`, `docker-compose.yml`, `config.php` (`$CFG->debug=0`), `php.ini`, `patches/`, `nginx_moodle.conf`, `canadaemcs.com.txt`, and `prepare-build-context.sh` (REPO_ROOT now `$SCRIPT_DIR/../../../..`; still hardlinks plugin zips from `Resources/moodle_plugins/`).
  - `servers/canadaeacademy.com/`, `servers/agincourt-international-academy/`, `servers/canadavirtualschool.com/`, `servers/source-snapshot-5.78.128.44/` — one `notes.md` each (server stats + DNS/SSL TODO).
- `Hetzner/Hextract-15178002/` — CodingInABox project (`15178002`): `inventory.md`, `costing.md`, `servers/www.codinginabox.com/` (was top-level `servers/www.codinginabox.com/`).
- `Hostinger/domains/` — Hostinger portfolio CSV/XLSX (was `domains/`). `Hostinger/tools/` — `refresh.mjs`, `_vps.mjs`, etc. (was `hostinger/`; the absolute `OUT`/`entry` paths inside were updated to the new locations). `node_modules/` and `.env` are gitignored.
- `Resources/moodle_plugins/` — canonical plugin zip library (unchanged; referenced by `prepare-build-context.sh`). `Resources/screenshots/` — relocated root debug PNGs (gitignored).
- `Project_notes_folder/` — this directory; persistent cross-agent notes.
- `Project_notes_folder/RUNBOOK_MOODLE_CLONE.md` — **start here for any new clone / rehome.** 10-phase runbook (VPS provisioning incl. UFW `443/tcp`, data transfer, domain re-point, TLS, F-001–F-005 verification, WS token, `idnumber` bulk populate, optional `en_local` rebrand, admin-hardening, verification, rollback).

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

