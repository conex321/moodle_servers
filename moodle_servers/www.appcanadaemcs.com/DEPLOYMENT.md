# Moodle rehomed to `app.canadaemcs.com`

**Date:** 2026-04-19
**Source:** Hetzner snapshot of production Moodle (`5.78.128.44`)
**Target:** Hetzner VPS `5.78.190.143` (snapshot: `snapshot-377925631-ubuntu-4gb-hil-1`)
**Result:** `https://app.canadaemcs.com` live with valid Let's Encrypt TLS.

---

## Final state

| Item | Value |
|---|---|
| Domain | `app.canadaemcs.com` |
| Public URL | `https://app.canadaemcs.com` |
| Server IP | `5.78.190.143` |
| TLS cert | Let's Encrypt (issuer R13), valid until `2026-07-18` |
| Reverse proxy | nginx 1.18.0 (host) → `http://127.0.0.1:8888` (Moodle container) |
| Moodle | 5.1.3 in Docker (`moodle-app`, `moodle-mariadb`, `moodle-phpmyadmin`) |
| Renewal | certbot.timer active, `authenticator = nginx` (HTTP-01) |
| Firewall | UFW: allow 22, 80, 443 |
| phpMyAdmin | Bound to `127.0.0.1:8080` only (SSH-tunnel to access) |

---

## What the snapshot already had correct

The Hetzner snapshot of `5.78.128.44` came with most of the rehoming already done:

- `/opt/moodle/config.php` had `$CFG->wwwroot = 'https://app.canadaemcs.com'`, `$CFG->sslproxy = true`, `$CFG->lock_factory = "\core\lock\db_record_lock_factory"`.
- `mdl_config.wwwroot` in the DB already read `https://app.canadaemcs.com`.
- nginx site `/etc/nginx/sites-available/moodle` was configured for `app.canadaemcs.com` with Let's Encrypt SSL.
- `/etc/letsencrypt/live/app.canadaemcs.com/` had a valid issued certificate.
- Hostinger DNS `app.canadaemcs.com A 5.78.190.143` was already propagated.

---

## What was broken and how it was fixed

### 1. HTTP 500 — `$CFG->dataroot` not writable

**Cause:** `/opt/moodle/moodledata/` (bind-mounted to `/var/moodledata` in the container) was owned by `daemon:daemon` (uid 1) with mode 0775. The container's Apache runs as `www-data` (uid 33), which was not a member of the `daemon` group, so it could neither write the dataroot nor create session files. The snapshot's `entrypoint.sh` runs `chown -R www-data:www-data /var/moodledata` + `chmod -R 777`, but that had evidently been interrupted on a prior boot and left the tree in an inconsistent state.

**Fix:**
```bash
chown -R www-data:www-data /opt/moodle/moodledata
```
(ran in < 2 seconds across all 76,889 files.)

### 2. HTTP 303 redirect to the old IP `http://5.78.128.44`

**Cause:** The Moodle `/var/www/html/config.php` inside the container is baked in by the `Dockerfile` via `COPY config.php /var/www/html/config.php`. The container image was built against the old source and still contained `$CFG->wwwroot = 'http://5.78.128.44'`. Updating `/opt/moodle/config.php` on the host had no effect because there was no bind mount for it.

**Fix:** Added a host bind mount for `config.php` in `docker-compose.yml` so future edits on the host take effect immediately, without rebuilding the image:
```yaml
volumes:
  - ./moodledata:/var/moodledata
  - ./config.php:/var/www/html/config.php          # added — same source-of-truth as host
  - ../Generated_Content:/data/Generated_Content:ro
  - ./moodle_course_organization:/data/moodle-local/moodle_course_organization:ro
```
Do **not** mount as `:ro` — the container's `entrypoint.sh` runs `chown -R www-data:www-data /var/www/html`, which errors out under a read-only mount.

### 3. External HTTPS unreachable (client-side timeout)

**Cause:** UFW had only `22/tcp` and `80/tcp` allowed; `443/tcp` was blocked. nginx was listening on 443 but the firewall dropped inbound traffic.

**Fix:**
```bash
ufw allow 443/tcp
```

### 4. Cache config error: `Permission denied` on `/var/moodledata/muc/config.php`

**Cause:** After clearing `muc`, `cache`, and `localcache`, the first `docker exec moodle-app php admin/cli/purge_caches.php` ran as the default container user (**root**). This created `muc/config.php` owned by `root:root 0660`, which Apache (`www-data`) could not read.

**Fix:** Re-chown and rerun the purge as `www-data`:
```bash
chown -R www-data:www-data /opt/moodle/moodledata/muc /opt/moodle/moodledata/cache /opt/moodle/moodledata/localcache
docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php
```

Future cache purges on this host must use `--user www-data`.

### 5. Security: `phpMyAdmin` exposed on `0.0.0.0:8080`

**Cause:** `docker-compose.yml` declared `ports: - "8080:80"`, binding to all interfaces. README §3 explicitly calls this out as a production security hole.

**Fix:** Changed to `127.0.0.1:8080:80`. Access via SSH tunnel:
```bash
ssh -i hetzner_moodle_ed25519 -L 8080:localhost:8080 root@5.78.190.143
# then open http://localhost:8080
```

---

## Files in this directory

| File | Purpose |
|---|---|
| `DEPLOYMENT.md` | This document |
| `canadaemcs.com.txt` | DNS zone snapshot as of 2026-04-19 (Hostinger) |
| `config.php` | Live Moodle config from `/opt/moodle/config.php` — the source-of-truth now bind-mounted into the container |
| `docker-compose.yml` | Updated compose — adds `config.php` bind mount, moves phpMyAdmin to `127.0.0.1:8080` |
| `docker-compose.yml.before` | Original compose pulled off the server on 2026-04-19 16:07 UTC |
| `nginx_moodle.conf` | `/etc/nginx/sites-available/moodle` as deployed |

---

## Operational notes / follow-ups

1. **Admin credentials carried over from the source snapshot** (README §11):
   - `admin` / `Schoolx2024!` — change these before opening to end users:
     ```bash
     docker exec --user www-data moodle-app php /var/www/html/admin/cli/reset_password.php --username=admin --password='NEW_STRONG_PASSWORD'
     ```
2. ~~**Debug is currently on** in `config.php`~~ **RESOLVED 2026-04-19** — both `$CFG->debug` and `$CFG->debugdisplay` are now `0` in `/opt/moodle/config.php` (and in this directory's mirror). See §17 for why: leaving debug display on caused a cascading session error on every page load because a PHP 8.3 deprecation notice from `blocks/edwiseradvancedblock/lib.php:131` was being emitted into the HTML before `session_start()`.
3. **Vimeo domain locking** — the 2,017 Interactive Video modules reference Vimeo videos with domain-locking tied to the old host. Log into the Vimeo dashboard and add `app.canadaemcs.com` to the allow-list, otherwise embeds will 403 for students (README §15 item 7).
4. **Cert auto-renewal** — `certbot.timer` is enabled and uses the nginx authenticator. Next check `Mon 2026-04-20 01:38 UTC`; real renewal kicks in 30 days before expiry (~2026-06-18). UFW must remain open on 80/tcp for HTTP-01 challenges.
5. **DB root password** — `MYSQL_ROOT_PASSWORD` in `docker-compose.yml` says `rootpass`, but the actual root password the DB accepts is the one from `/opt/moodle/.env` (`yZ7VzTgab8TDMZe1ggFTj8FIGgvZdORy`). This is because the DB was initialized with `.env` values first and `dbdata/` already exists, so the compose environment is ignored on subsequent boots. Use the `.env` value for any manual root DB access:
   ```bash
   docker exec -it moodle-mariadb mariadb -uroot -p$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) moodle
   ```
6. **`opcache MISSING` warning** in the container boot logs is a false positive — `Zend OPcache` is loaded (visible in `php -m`). The entrypoint's grep is strict-matching the module name and misses the Zend-prefixed one. Harmless.

---

## Verification transcript (2026-04-19, after fixes)

```
$ curl -sIL -o /dev/null -w 'final_http:%{http_code} final_url:%{url_effective}\n' https://app.canadaemcs.com/
final_http:200 final_url:https://app.canadaemcs.com/

$ curl -sk https://app.canadaemcs.com/ | grep -oE '<title>[^<]+</title>'
<title>Home | G18VA</title>

$ curl -sk https://app.canadaemcs.com/login/index.php | grep -oE '<title>[^<]+</title>'
<title>Log in to the site | G18VA</title>

$ curl -sk https://app.canadaemcs.com/course/index.php | grep -oE '<title>[^<]+</title>'
<title>G18VA: Course categories | G18VA</title>

$ echo | openssl s_client -connect app.canadaemcs.com:443 -servername app.canadaemcs.com 2>/dev/null \
    | openssl x509 -noout -subject -issuer -dates
subject=CN=app.canadaemcs.com
issuer=C=US, O=Let's Encrypt, CN=R13
notBefore=Apr 19 15:13:02 2026 GMT
notAfter=Jul 18 15:13:01 2026 GMT
```

---

## SSH shortcut

```bash
ssh -i ~/antigravity/Moodle_servers/ssh_keys/hetzner_moodle_ed25519 root@5.78.190.143
```

---

## 17. Post-deploy fix: `edwiseradvancedblock` `file_exists(null)` deprecation → session outage (2026-04-19)

### What users saw
Every page on `https://app.canadaemcs.com/` rendered as a white error page with:
```
Deprecated: file_exists(): Passing null to parameter #1 ($filename) of type string is deprecated
in /var/www/html/public/blocks/edwiseradvancedblock/lib.php on line 131
Warning: ini_set(): Session ini settings cannot be changed after headers have already been sent ...
Error — Couldn't start session. Error code: sessionstarterror
```

### Root cause
`edwb_is_plugin_available($component)` in `blocks/edwiseradvancedblock/lib.php` calls `core_component::get_plugin_directory($type, $name)` and passes the result straight to `file_exists()`. When `$component` names a plugin that is not installed (or an unknown type), `get_plugin_directory()` returns `null`. PHP ≥ 8.1 (the image uses PHP 8.3) emits a deprecation notice when `null` is passed to `file_exists()`. Because `$CFG->debugdisplay = 1` was still on in `config.php` (carried over from the source snapshot), PHP wrote the deprecation notice into the HTML stream before Moodle called `session_start()`, which then failed with `sessionstarterror`.

### The two-layer fix

**Layer 1 — stop the HTML pollution (turn off debug display).**
```bash
sed -i "s|\$CFG->debug = 32767;|\$CFG->debug = 0;|; s|\$CFG->debugdisplay = 1;|\$CFG->debugdisplay = 0;|" /opt/moodle/config.php
docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php
```
This alone restores the site, but leaves the underlying PHP 8.x deprecation live — any future re-enable of debug would break the site again.

**Layer 2 — fix the plugin (root cause).** The plugin source is patched to null-guard the call, and the patched file is persisted in `/opt/moodle/patches/edwiseradvancedblock_lib.php` using the same Dockerfile `COPY` pattern as `patches/choicelist_fixed.php`.

Patched function body (lib.php ~line 126-134):
```php
function edwb_is_plugin_available($component) {
    list($type, $name) = core_component::normalize_component($component);
    $dir = \core_component::get_plugin_directory($type, $name);
    if ($dir === null || !file_exists($dir)) {   // was:  if (!file_exists($dir))
        return false;
    }
    return true;
}
```

### Dockerfile addition
One line was inserted after the `block_edwiseradvancedblock.zip` extraction block in `/opt/moodle/Dockerfile` (~line 59), matching the existing `patches/choicelist_fixed.php` pattern:
```dockerfile
COPY patches/edwiseradvancedblock_lib.php /var/www/html/public/blocks/edwiseradvancedblock/lib.php
```
The image was **not** rebuilt as part of this fix — the running container was patched directly via `docker cp`, and the patch file + Dockerfile edit ensure that the next rebuild (for any unrelated reason) bakes the fix in permanently.

### Verification (2026-04-19, after fix)
```
$ curl -sIL -o /dev/null -w 'home:%{http_code} login:%{http_code}\n' https://app.canadaemcs.com/ https://app.canadaemcs.com/login/index.php
home:200 login:200

$ curl -sk https://app.canadaemcs.com/login/index.php | grep -oE '<title>[^<]+</title>'
<title>Log in to the site | G18VA</title>

$ curl -sk https://app.canadaemcs.com/login/index.php | grep -ciE 'sessionstarterror|Deprecated|Warning:'
0

$ curl -skI https://app.canadaemcs.com/login/index.php | grep -i '^set-cookie:'
Set-Cookie: MoodleSession=...; path=/; secure; HttpOnly

# Deprecation no longer emitted at all (direct CLI test with display_errors=1):
$ docker exec --user www-data moodle-app php -d error_reporting=E_ALL -d display_errors=1 -r '
    define("CLI_SCRIPT", true);
    require_once("/var/www/html/config.php");
    require_once("/var/www/html/public/blocks/edwiseradvancedblock/lib.php");
    var_dump(edwb_is_plugin_available("mod_does_not_exist"));
    var_dump(edwb_is_plugin_available("block_html"));'
bool(false)
bool(true)
```

### Files touched in this repo
- `config.php` — `$CFG->debug` and `$CFG->debugdisplay` set to `0`.
- `DEPLOYMENT.md` — §2 follow-up marked resolved; this §17 added.

### Files touched on the server (`5.78.190.143`)
- `/opt/moodle/config.php` — debug both to `0` (bind-mounted into container).
- `/opt/moodle/patches/edwiseradvancedblock_lib.php` — new, contains the patched plugin source.
- `/opt/moodle/Dockerfile` — one new `COPY patches/edwiseradvancedblock_lib.php …` line after line 58.
- `/var/www/html/public/blocks/edwiseradvancedblock/lib.php` inside `moodle-app` container — line 131 now guards against `$dir === null`.

---

## 18. Post-deploy fix: `admin/webservice/service_functions.php` → coding_exception on ghost plugins (2026-04-19)

### What users saw
Navigating to `Site administration → Server → Web services → External services → (any service) → Functions → Add functions` rendered:
```
Coding error detected, it must be fixed by a programmer:
Cannot find file [dirroot]/blocks/edwiser_site_monitor/classes/externallib.php
with external function implementation for
block_edwiser_site_monitor\externallib::get_last_24_hours_usage
```
The result was that no web service functions could be added to any external service, blocking the entire "Enable REST API + create token" workflow.

### Root cause
The admin form iterates every row in `mdl_external_functions` and calls `core_external\external_api::external_function_info()` on each one. That function validates the target classname resolves to a file on disk. The first row whose plugin is missing from disk throws `coding_exception`, killing the whole form.

The source snapshot of `5.78.128.44` carried `mdl_config_plugins` version rows and `mdl_external_functions` registrations for 13 Edwiser/third-party plugins whose code was deliberately excluded from this image's Dockerfile (only `theme_remui`, `block_edwiseradvancedblock`, `filter_edwiserpbf`, `mod_interactivevideo` were being installed). Moodle's `admin/cli/uninstall_plugins.php --show-missing` confirmed 13 real plugins + 8 `edwiserformevents_*` subplugins registered-but-missing.

### Resolution strategy: install the 11 available plugins, purge the other 2

Plugin zips for 11 of the 13 ghosts were already present at `/opt/moodle/moodleplugins/` on the server (and at `/Users/matthews/antigravity/Moodle_servers/Resources/moodle_plugins/` locally). Only `block_xp` (Level Up XP) and `filter_translations` had no zip — those were purged, not installed.

**The 11 plugins installed** (with component name → on-disk path):

| Component | On-disk path | Source zip | Notes |
|---|---|---|---|
| `block_edwiser_site_monitor` | `blocks/edwiser_site_monitor/` | `block_site_monitor.zip` | Zip's inner dir is `sitemonitor`; renamed to match component |
| `block_edwiserratingreview` | `blocks/edwiserratingreview/` | RemUI bundle / `Plugins/block_edwiserratingreview.zip` | |
| `mod_edwiservideoactivity` | `mod/edwiservideoactivity/` | `edwiservideoactivity.zip` | |
| `format_edwiservideoformat` | `course/format/edwiservideoformat/` | `edwiservideoformat.zip` | depends on `mod_edwiservideoactivity` |
| `filter_edwiserformlink` | `filter/edwiserformlink/` | Forms-Pro / `filter_edwiserformlink.zip` | |
| `local_edwiserform` | `local/edwiserform/` | Forms-Pro / `local_edwiserform.zip` | includes `edwiserformevents_*` subplugins |
| `local_edwiserpagebuilder` | `local/edwiserpagebuilder/` | RemUI bundle / `Plugins/Page Builder Plugins/local_edwiserpagebuilder.zip` | |
| `local_edwiserreports` | `local/edwiserreports/` | `moodle-local-edwiserreports-1.zip` | Zip's inner dir is `moodle-local-edwiserreports`; renamed |
| `local_edwisersiteimporter` | `local/edwisersiteimporter/` | RemUI bundle / `Plugins/local_edwisersiteimporter.zip` | |
| `local_sitesync` | `local/sitesync/` | RemUI bundle / `Plugins/Site Sync Plugin (Experimental)/local_sitesync.zip` | |
| `mod_edwiserform` | `mod/edwiserform/` | Forms-Pro / `mod_edwiserform.zip` | |

**Purged** (no zip available; `admin/cli/uninstall_plugins.php --purge-missing --run`): `block_xp`, `filter_translations`.

**Also cleaned:** 4 stale `mdl_external_functions` rows for `format_remuiformat_*` that had no corresponding version registration (orphaned by a prior uninstall that didn't cascade). Deleted directly via SQL.

### Execution summary
```bash
# 1. DB backup
docker exec moodle-mariadb mariadb-dump -uroot -p"$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2)" \
  --single-transaction --routines --triggers moodle \
  | gzip > /opt/moodle/deploy/pre-ghost-fix-20260419-2000.sql.gz   # 13 MB

# 2. Extract each plugin zip into the running container at its correct component path
#    (see the table above — some required renaming the zip's top-level directory)

# 3. Apply ownership + run upgrade
docker exec moodle-app chown -R www-data:www-data /var/www/html
docker exec --user www-data moodle-app php /var/www/html/admin/cli/upgrade.php --non-interactive

# 4. Purge remaining ghosts (block_xp, filter_translations)
docker exec --user www-data moodle-app php /var/www/html/admin/cli/uninstall_plugins.php --purge-missing --run

# 5. Delete orphaned format_remuiformat rows
docker exec moodle-mariadb mariadb -uroot -p"$ROOT_PW" -D moodle -e \
  "DELETE FROM mdl_external_functions WHERE component='format_remuiformat';"

# 6. Purge caches
docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php
```

### Verification (2026-04-19, after fix)
```
# 855 external functions, all resolve cleanly (was 859 with 4 stale + 200+ throwing):
$ docker exec --user www-data moodle-app php -r '<omitted CLI script from logs; see PROJECT_NOTES.md>'
total=855 ok=855 errors=0

# Webservice form URL — 303 redirect to login (was: HTML error page with coding_exception):
$ curl -skI "https://app.canadaemcs.com/admin/webservice/service_functions.php?id=0" | head -1
HTTP/1.1 303 See Other

# Home + login still healthy:
$ curl -sIL -o /dev/null -w 'home:%{http_code} login:%{http_code}\n' https://app.canadaemcs.com/ https://app.canadaemcs.com/login/index.php
home:200 login:200

# Moodle's own view of missing plugins — now empty:
$ docker exec --user www-data moodle-app php admin/cli/uninstall_plugins.php --show-missing
(empty)
```

### Persisted in Dockerfile
`/opt/moodle/Dockerfile` was updated to COPY the 4 standalone zips + 2 bundle zips after the existing `filter_edwiserpbf` block, with RUN commands to extract each to the correct target path (including the `sitemonitor → edwiser_site_monitor`, `moodle-local-edwiserreports → edwiserreports`, `local_sitesync → sitesync`, `local_edwiserform → edwiserform`, `mod_edwiserform → edwiserform` renames). Next `docker compose build moodle` will reproduce the installed state from scratch.

### Files touched on the server (`5.78.190.143`)
- `/var/www/html/public/blocks/edwiser_site_monitor/` — new, installed from `block_site_monitor.zip`.
- `/var/www/html/public/blocks/edwiserratingreview/` — new.
- `/var/www/html/public/mod/edwiservideoactivity/` — new.
- `/var/www/html/public/mod/edwiserform/` — new.
- `/var/www/html/public/course/format/edwiservideoformat/` — new.
- `/var/www/html/public/filter/edwiserformlink/` — new.
- `/var/www/html/public/local/edwiserform/` — new (with `edwiserformevents_*` subplugins).
- `/var/www/html/public/local/edwiserpagebuilder/` — new.
- `/var/www/html/public/local/edwiserreports/` — new.
- `/var/www/html/public/local/edwisersiteimporter/` — new.
- `/var/www/html/public/local/sitesync/` — new.
- `/opt/moodle/Dockerfile` — 70 new lines inserted between `filter_edwiserpbf` block and `mod_interactivevideo` block (after line 65).
- `/opt/moodle/deploy/pre-ghost-fix-20260419-2000.sql.gz` — pre-fix DB snapshot (13 MB).
- DB: `mdl_config_plugins` + `mdl_external_functions` + plugin-specific tables — 11 plugins installed, 2 purged, format_remuiformat orphans deleted.

### Known follow-up (low priority)
`local/edwiserreports/settings.php:197` emits a PHP 8.3 deprecation (`stripos(): Passing null to parameter #1 ($haystack) of type string`) + an "Undefined array key REQUEST_URI" warning during cron/upgrade. Invisible on the site because debug display is off (DEPLOYMENT.md §17), but noise in container logs. Same shape as F-001; same fix pattern applies (null-guard + `patches/edwiserreports_settings.php` + Dockerfile COPY line). Not urgent — can be deferred until someone re-enables debug or wants clean logs.
