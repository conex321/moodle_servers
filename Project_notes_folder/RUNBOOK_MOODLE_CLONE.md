# Runbook — Clone Moodle to a new domain (repeatable `app.canadaemcs.com`-style rehome)

**Purpose:** bring up a copy of the existing `app.canadaemcs.com` Moodle instance on a new VPS and domain, with every post-deploy fix and integration step from 2026-04-19–20 already baked in — no rediscovery required.
**Target audience:** any future agent or human doing another rehome.
**Estimated duration:** 45–90 min end-to-end (dominated by the ~36 GB `moodledata` transfer).
**Prerequisites:** snapshot of `/opt/moodle/` (including `dbdata/` and `moodledata/`) from a healthy source server; root SSH access to the target VPS; DNS control for the target domain; Cloudflare or Let's Encrypt account for TLS.

---

## 0. Terminology / conventions

In every command below, substitute:
- `NEW_DOMAIN` → e.g. `app.newcustomer.com`
- `NEW_IP` → public IP of the target VPS (e.g. `5.78.X.X`)
- `SSH_KEY` → `/Users/matthews/antigravity/Moodle_servers/Resources/ssh_keys/hetzner_moodle_ed25519` (or a new key per host)
- `SSH` → `ssh -i $SSH_KEY root@$NEW_IP`
- `OLD_IP` / `OLD_DOMAIN` → source server's address (e.g. `5.78.128.44` or `app.canadaemcs.com`)

All DB operations assume the standard container/service layout:
- `moodle-app` — Moodle PHP/Apache container
- `moodle-mariadb` — MariaDB 10.11 container
- `moodle-phpmyadmin` — phpMyAdmin container (bound to 127.0.0.1:8080)
- DB root password in `/opt/moodle/.env` (`MYSQL_ROOT_PASSWORD=...`)

Common shell idiom to get DB root password on the server:
```bash
DB_PW=$($SSH 'grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2')
```

---

## Phase 1 — Provision the target VPS

### 1.1 Host sizing
- 4 GB RAM minimum (8 GB for 100+ concurrent users)
- 80 GB disk minimum (moodledata is ~36 GB and grows)
- 2+ vCPU (3 comfortable, 4+ for production)
- Ubuntu 22.04 LTS (20.04 and 24.04 also work)

### 1.2 Initial OS setup (as root on NEW_IP)
```bash
apt update && apt install -y docker.io docker-compose-plugin rsync git curl unzip
systemctl enable --now docker
```

### 1.3 Firewall (UFW) — allow 22, 80, 443 only
```bash
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable   # answer 'y'
```
**Gotcha from the 2026-04-19 rehome (DEPLOYMENT.md §3):** do not forget `443/tcp`. If only 22+80 are open and you set up TLS, clients will time out — nginx answers but UFW drops the packets.

### 1.4 DNS
Point `NEW_DOMAIN` A record to `NEW_IP`. Wait for propagation (usually ≤ 5 min with Cloudflare, up to 60 min elsewhere):
```bash
dig +short $NEW_DOMAIN  # should return $NEW_IP
```

### 1.5 Host-native MariaDB (if present)
If Ubuntu preinstalled MariaDB as a system service, disable it — it conflicts with the containerised DB if you later expose port 3306:
```bash
systemctl disable --now mariadb 2>/dev/null || true
```
The containerised Moodle talks to MariaDB over the internal Docker network; the native DB is only a potential port conflict, not a dependency.

---

## Phase 2 — Transfer the build context and data

### 2.1 Snapshot the source (recommended path)
If the source VPS is on Hetzner/DigitalOcean etc., use the provider's snapshot + rebuild-to-new-VPS feature. This gives you `/opt/moodle/` (including `dbdata/`, `moodledata/`, `Dockerfile`, `docker-compose.yml`, `config.php`, `patches/`, `plugins-src/`, `moodleplugins/`, `.env`) at the state it was when snapshotted.

Verify post-boot:
```bash
$SSH 'ls -la /opt/moodle/ && docker ps --format "{{.Names}}\t{{.Status}}"'
```
Expected: all 3 containers present (may be Exited until 2.3 below). `/opt/moodle/` tree shows the files listed above.

### 2.2 Alternative: tar-over-ssh (no snapshot available)
From a workstation with SSH access to both servers:
```bash
# On the SOURCE machine
ssh -i $SSH_KEY root@$OLD_IP "tar -czf - /opt/moodle/{Dockerfile,docker-compose.yml,config.php,php.ini,entrypoint.sh,patches,plugins-src,moodleplugins,.env} \
  | ssh -i $SSH_KEY root@$NEW_IP 'mkdir -p /opt/moodle && cd /opt/moodle && tar -xzf -'"

# Then the big two separately (parallelised):
ssh -i $SSH_KEY root@$OLD_IP "docker exec moodle-mariadb mariadb-dump -uroot -p\$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) --single-transaction --routines --triggers moodle | gzip" \
  > /tmp/moodle.sql.gz

rsync -avP -e "ssh -i $SSH_KEY" root@$OLD_IP:/opt/moodle/moodledata/ root@$NEW_IP:/opt/moodle/moodledata/
```

### 2.3 Start the stack
```bash
$SSH "cd /opt/moodle && mkdir -p /opt/Generated_Content /opt/moodle/moodle_course_organization && docker compose up -d"
# First boot runs `chmod -R 777 /var/moodledata` on the ~36 GB tree; expect 1–5 min before port 80 answers.
```

Wait for readiness, then verify:
```bash
$SSH 'docker ps --format "{{.Names}}\t{{.Status}}" && curl -sI http://127.0.0.1/login/index.php'
```
Expected: 3 "Up" containers + HTTP 200 (or 303 redirect).

### 2.4 If you used the tar path: restore the DB
```bash
gunzip -c /tmp/moodle.sql.gz \
  | ssh -i $SSH_KEY root@$NEW_IP "docker exec -i moodle-mariadb mariadb -uroot -p\$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) moodle"
```

---

## Phase 3 — Re-point to NEW_DOMAIN

### 3.1 `config.php` — change `$CFG->wwwroot`
`/opt/moodle/config.php` is **bind-mounted** into the container at `/var/www/html/config.php` (added during the 2026-04-19 rehome — DEPLOYMENT.md §2 fix). Edits on the host take effect immediately without rebuild.

```bash
$SSH "sed -i \"s|\\\$CFG->wwwroot\\s*=.*|\\\$CFG->wwwroot = 'https://$NEW_DOMAIN';|\" /opt/moodle/config.php"
```

### 3.2 DB row — `mdl_config.wwwroot`
```bash
$SSH "docker exec moodle-mariadb mariadb -uroot -p\$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) moodle \
  -e \"UPDATE mdl_config SET value='https://$NEW_DOMAIN' WHERE name='wwwroot';\""
```

### 3.3 Purge caches as www-data (CRITICAL — do NOT run as root)
DEPLOYMENT.md §4 gotcha: running `purge_caches.php` as root makes `muc/config.php` root-owned → Apache (www-data) can't read it → cache errors on every page.
```bash
$SSH "find /opt/moodle/moodledata/muc /opt/moodle/moodledata/cache /opt/moodle/moodledata/localcache -mindepth 1 -delete"
$SSH 'docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php'
```

### 3.4 Moodledata ownership (if you rsynced as root from a differently-owned source)
Inside the container, Apache runs as `www-data` (uid 33). If moodledata is owned by `daemon:daemon` or `root:root`, Moodle cannot write sessions or the dataroot:
```bash
$SSH 'chown -R www-data:www-data /opt/moodle/moodledata'
```
This one command completes in <2 seconds on ~77k files (DEPLOYMENT.md §1 fix).

---

## Phase 4 — TLS / reverse proxy

### 4.1 Install certbot + nginx (host, not containerised)
```bash
$SSH "apt install -y nginx certbot python3-certbot-nginx"
```

### 4.2 Create nginx site
```bash
$SSH "cat > /etc/nginx/sites-available/moodle << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name $NEW_DOMAIN;

    location / {
        proxy_pass http://127.0.0.1:8888;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        client_max_body_size 512M;
    }
}
EOF"
$SSH "ln -sf /etc/nginx/sites-available/moodle /etc/nginx/sites-enabled/moodle"
$SSH "nginx -t && systemctl reload nginx"
```

**NOTE:** the container must be bound to `127.0.0.1:8888` (not `0.0.0.0:80`) for the proxy pattern. Check `/opt/moodle/docker-compose.yml` — the `moodle` service should have `ports: - "127.0.0.1:8888:80"` (production pattern) rather than `"80:80"`.

### 4.3 Issue Let's Encrypt cert
```bash
$SSH "certbot --nginx -d $NEW_DOMAIN --non-interactive --agree-tos -m admin@$NEW_DOMAIN"
$SSH "certbot renew --dry-run"   # sanity-check auto-renewal
```

### 4.4 `$CFG->sslproxy = true` — already set in config.php from prior sessions
Verify:
```bash
$SSH 'grep -E "sslproxy|debug" /opt/moodle/config.php'
# Expected:
#   $CFG->sslproxy = true;
#   $CFG->debug = 0;
#   $CFG->debugdisplay = 0;
```

### 4.5 Final purge + HTTP smoke
```bash
$SSH 'docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php'
curl -sIL -o /dev/null -w 'home:%{http_code} login:%{http_code}\n' https://$NEW_DOMAIN/ https://$NEW_DOMAIN/login/index.php
# Expected: home:200 login:200
```

---

## Phase 5 — Apply known-good fixes (all already baked into snapshot images post-2026-04-19, but verify)

### 5.1 Confirm F-001 patch is in place (`edwiseradvancedblock/lib.php:131`)
```bash
$SSH 'docker exec moodle-app sed -n "126,134p" /var/www/html/public/blocks/edwiseradvancedblock/lib.php'
# Expected: null-guard present: `if ($dir === null || !file_exists($dir))`
```
If the unpatched line is there (`if (!file_exists($dir))`), apply via `docker cp` (see DEPLOYMENT.md §17) OR rebuild the image (which has `COPY patches/edwiseradvancedblock_lib.php ...`).

### 5.2 Confirm ghost plugins are zero
```bash
$SSH 'docker exec --user www-data moodle-app php /var/www/html/admin/cli/uninstall_plugins.php --show-missing'
# Expected: empty output
```
If anything is listed, see DEPLOYMENT.md §18 for the install-or-purge decision tree and the full set of 11 plugin installs that were done on 2026-04-19. The Dockerfile contains the COPY/RUN steps — a rebuild would bake them in automatically if you started from a pre-2026-04-19 image.

### 5.3 Full external-function sanity walk
```bash
$SSH 'docker exec --user www-data moodle-app php -r "
define(\"CLI_SCRIPT\", true);
require_once(\"/var/www/html/config.php\");
global \$DB;
\$rs = \$DB->get_records(\"external_functions\", null, \"id\", \"id,name\");
\$ok=0; \$err=[];
foreach (\$rs as \$r) {
    try { core_external\external_api::external_function_info(\$r->name); \$ok++; }
    catch (Throwable \$e) { \$err[] = \$r->name; }
}
echo \"total=\".count(\$rs).\" ok=\".\$ok.\" errors=\".count(\$err).PHP_EOL;
foreach (\$err as \$n) echo \"  FAIL: \$n\".PHP_EOL;
"'
# Expected (as of 2026-04-19): total=855 ok=855 errors=0
```
A non-zero error count means either another ghost registration survived or a new plugin was installed that shipped a broken class path. Delete the offending `mdl_external_functions` row for an orphan, or find-and-install the missing plugin.

---

## Phase 6 — Integration credentials (WS API for SchoolConex / any backend)

Only needed if the new instance must be callable by an external system. If yes, either reuse the existing token (if cloning for a multi-tenant backend) or generate a fresh one (recommended for a separate tenant).

### 6.1 Confirm Web Services + REST already enabled
```bash
$SSH 'docker exec moodle-mariadb mariadb -uroot -p$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) -D moodle -e "
SELECT name, value FROM mdl_config WHERE name IN (\"enablewebservices\",\"webserviceprotocols\");"'
# Expected:
#   enablewebservices      1
#   webserviceprotocols    rest
```

### 6.2 Generate a fresh token for a new tenant
If you want a separate token (e.g. different backend IP range per tenant), easier than UI-clicking — use the script from 2026-04-19 session 3 (see `PROJECT_NOTES.md` → `Session 2026-04-19 (cont.) — Full WS setup for schoolconex_api`). Adapt to your new user/service name.

Summary of what it does (single PHP script run in container):
1. Creates a custom role with 16 capabilities at System context (see list in DEPLOYMENT.md §18 or D-007).
2. Creates a service account user (e.g. `emcs_api`, `tenant2_api`).
3. Assigns the role to the user at System context.
4. Sets the service `restrictedusers=1`.
5. Attaches the 10 WS functions needed for a typical EMCS integration:
   `core_user_create_users`, `core_user_get_users_by_field`, `core_user_update_users`,
   `enrol_manual_enrol_users`, `enrol_manual_unenrol_users`,
   `core_course_get_courses`, `core_course_get_courses_by_field`,
   `core_enrol_get_users_courses`, `core_enrol_get_enrolled_users`,
   `core_webservice_get_site_info`.
6. Adds the user as authorised user of the service.
7. Generates a `md5(uniqid(mt_rand(), true))` token, inserts into `mdl_external_tokens`.
8. Prints the token once — copy immediately.

### 6.3 Smoke test
```bash
TOKEN=<token-from-6.2>
curl -sk "https://$NEW_DOMAIN/webservice/rest/server.php" \
  --data-urlencode "wstoken=$TOKEN" \
  --data-urlencode "wsfunction=core_webservice_get_site_info" \
  --data-urlencode "moodlewsrestformat=json" | python3 -m json.tool | head -40
# Expected: JSON with sitename, username matching the service account, functions[] listing all 10.
```

### 6.4 Course `idnumber` population
SchoolConex resolves course codes via `core_course_get_courses_by_field` with `field=idnumber`. If `idnumber` is empty on any course, that lookup misses.
```bash
$SSH 'docker exec moodle-mariadb mariadb -uroot -p$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) moodle -e "
SELECT COUNT(*) missing FROM mdl_course WHERE id!=1 AND (idnumber=\"\" OR idnumber IS NULL);"'
```
If `missing > 0`, apply the bulk update (per D-006):
```bash
# Backup first
TS=$(date -u +%Y%m%d-%H%M)
$SSH "docker exec moodle-mariadb mariadb-dump -uroot -p\$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) --single-transaction moodle | gzip" \
  > /tmp/pre-idnumber-$TS.sql.gz

$SSH 'docker exec moodle-mariadb mariadb -uroot -p$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) moodle -e "
UPDATE mdl_course SET idnumber = shortname WHERE id != 1 AND (idnumber = \"\" OR idnumber IS NULL);
SELECT ROW_COUNT() AS updated;"'
$SSH 'docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php'
```
Verify with a WS lookup:
```bash
curl -sk "https://$NEW_DOMAIN/webservice/rest/server.php" \
  --data-urlencode "wstoken=$TOKEN" \
  --data-urlencode "wsfunction=core_course_get_courses_by_field" \
  --data-urlencode "moodlewsrestformat=json" \
  --data-urlencode "field=idnumber" \
  --data-urlencode "value=MAT01" | python3 -m json.tool
# Expected: {"courses": [{"id":..., "shortname":"MAT01", "idnumber":"MAT01", ...}], ...}
```

---

## Phase 7 — Branding rename (optional, if cloning for a different tenant)

Only if the new instance is for a different brand (e.g. EMCS → "Ontario Online Academy").

### 7.1 Site name + shortname
Admin UI: `Site admin → General → Site home settings → Full site name` + `Short name for site`.

### 7.2 RemUI theme text
Admin UI: `Site admin → Appearance → RemUI` — sitenamecolor, slider text/CTA, about, headings. See D-008 for the full set changed in the EMCS rename.

### 7.3 Bulk "Moodle" → "$BRAND" in all user-facing strings
The tested-and-verified path is to drop language overrides at `/var/moodledata/lang/en_local/*.php`. Moodle's `en_local` mechanism overrides the vendor lang pack without modifying it — fully reversible.

See D-008 in PROJECT_NOTES.md for the script that generated 561 overrides across 119 component files in the 2026-04-19 EMCS rename. Copy the same script, swap the target string, run, purge caches, verify via Playwright.

**Never modify files under `/var/www/html/public/lang/en/*.php` directly** — they get overwritten on Moodle core upgrades.

### 7.4 Footer / header custom HTML (if the brand needs richer than single-string replacement)
Admin UI: `Site admin → Appearance → Additional HTML → Before BODY is closed`. Inject branded footer HTML as in D-008. Keep CSS inline — outside stylesheets won't load before the Additional-HTML block paints.

### 7.5 Custom nav menu labels (separate from page names)
**Gotcha discovered 2026-04-20:** Moodle's top nav labels are NOT derived from Page Builder `pagename` fields. They live in `mdl_config.custommenuitems` in pipe-delimited format:
```
Label|URL\nLabel|URL\nLabel|URL
```
(`\n` is literal backslash-n inside the value, Moodle parses it as item delimiter.) So renaming a Page Builder page (`mdl_edw_pages.pagename`) changes the page's own `<title>` and heading but does NOT propagate to the nav link label. Both must be updated if the user visibly sees "old label" in the nav but "new label" on the page itself.

To rename a nav label without touching the page:
```bash
$SSH "docker exec moodle-mariadb mariadb -uroot -p\$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) -D moodle -e \"
UPDATE mdl_config SET value = REPLACE(value, 'OldLabel|/target/url', 'NewLabel|/target/url') WHERE name='custommenuitems';\""
$SSH 'docker exec --user www-data moodle-app php /var/www/html/admin/cli/purge_caches.php'
```
Admin UI equivalent: `Site admin → Appearance → Navigation → Custom menu items`.

Note there's a *second* `custommenuitems` row in `mdl_config_plugins` (plugin=`core`) from a prior RemUI or Edwiser config import. It appears unused in the current rendered nav — do not touch it unless you're deliberately reverting to that menu.

---

## Phase 8 — Admin + manager accounts

### 8.1 Rotate inherited admin password (CRITICAL before public traffic)
The snapshot carried `admin / Schoolx2024!`. Rotate immediately:
```bash
$SSH "docker exec --user www-data moodle-app php /var/www/html/admin/cli/reset_password.php --username=admin --password='$(openssl rand -base64 24)'"
```
Record the new password in your secrets store.

### 8.2 Drop or rename the demo account
```bash
# Option A: suspend
$SSH "docker exec moodle-mariadb mariadb -uroot -p\$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) moodle -e \"UPDATE mdl_user SET suspended=1 WHERE username='demo';\""
# Option B: hard delete via CLI
$SSH "docker exec --user www-data moodle-app php /var/www/html/admin/cli/delete_data_for_user.php --username=demo --i-confirm-this-operation-is-irreversible"
```

### 8.3 Create a scoped manager user (optional — see D-007 for rationale)
If the tenant admin should be able to create students and enrol them but NOT site-config, use the `courseenroller` role pattern from D-007. Single PHP script creates role + user, done in 30 seconds.

---

## Phase 9 — Vimeo / external media domain locking

The source instance has 2,017 Interactive Video modules referencing Vimeo videos with domain-locking. If the source was locked to `OLD_DOMAIN`:
- Log into the Vimeo dashboard → add `NEW_DOMAIN` to the allow-list for every affected video OR
- Ask the tenant's Vimeo admin to mirror the allow-list.

Otherwise students see 403 embeds. Not caught by any Moodle-side check — only observable by loading an Interactive Video as a student.

---

## Phase 10 — End-to-end verification checklist

Tick each before declaring the rehome complete:

- [ ] `curl -sIL -o /dev/null -w '%{http_code}\n' https://$NEW_DOMAIN/login/index.php` → `200`
- [ ] Certbot cert valid, chain OK: `echo | openssl s_client -connect $NEW_DOMAIN:443 -servername $NEW_DOMAIN 2>/dev/null | openssl x509 -noout -dates`
- [ ] `curl -sk https://$NEW_DOMAIN/login/index.php | grep -oE '<title>[^<]+</title>'` shows brand-correct title (no "Moodle", if rebranded)
- [ ] `docker exec --user www-data moodle-app php admin/cli/uninstall_plugins.php --show-missing` returns empty
- [ ] External-function walk: `total=N ok=N errors=0`
- [ ] `SELECT COUNT(*) FROM mdl_course WHERE id!=1 AND (idnumber='' OR idnumber IS NULL);` → `0` (if WS integration needed)
- [ ] Admin login via browser works, no console errors that block forms
- [ ] `Site admin → Server → Web services → External services → Functions` loads without `coding_exception`
- [ ] Fresh WS token resolves a known course via `core_course_get_courses_by_field` → returns expected course
- [ ] Admin password rotated; demo account suspended or deleted
- [ ] No "Moodle" visible to students (if rebranded) — sample via Playwright screenshot of Home, Dashboard, Course view
- [ ] Vimeo domain allow-list updated (if curriculum includes Interactive Video)
- [ ] `$CFG->debug=0`, `$CFG->debugdisplay=0` confirmed in live config

---

## Rollback (if something irrecoverable)

1. `docker compose down` on NEW_IP (containers stop, volumes preserved).
2. Restore `/opt/moodle/dbdata/` from the snapshot that was taken in Phase 6.4 or from the Hetzner-level VPS snapshot.
3. `docker compose up -d`.
4. Re-apply domain reconfig steps (Phase 3) if the rollback reverted `wwwroot`.

For a total wipe: destroy the VPS, rebuild from the source snapshot again.

---

## Known issues to carry forward (from F-001 through F-006)

Re-read these in PROJECT_NOTES.md before hitting them:

| ID | One-liner | When will it bite you |
|----|-----------|----------------------|
| F-001 | `file_exists(null)` in `edwiseradvancedblock/lib.php:131` | If snapshot pre-dates 2026-04-19 OR if someone re-unzips the plugin without applying the patch. Symptom: white error page with `sessionstarterror`. |
| F-002 | Ghost plugins from snapshot break webservice form | If snapshot pre-dates 2026-04-19 OR if the Dockerfile's ghost-fix COPY block is missing. Symptom: `coding_exception` on `admin/webservice/service_functions.php`. |
| F-003 | `stripos(null, ...)` in `local/edwiserreports/settings.php:197` | If `$CFG->debugdisplay = 1` is set — do NOT re-enable debug display on production until this is patched. |
| F-004 | `local_edwiserreports/install.js` 404 | Cosmetic, every admin page. Harmless. |
| F-005 | `theme_remui/bs4-compat.js` jQuery race | Fixed by D-009 overlays; re-test after RemUI upgrades/rebuilds. |
| F-006 | About-page `section_*.png` images 404 | Cosmetic only; upload the three images or remove the stale `<img>` references. |

---

## Reference: where everything lives

| Artefact | Location on target server |
|---|---|
| Build context | `/opt/moodle/` |
| Dockerfile | `/opt/moodle/Dockerfile` — contains the 11 plugin installs + the F-001 patch COPY line |
| docker-compose | `/opt/moodle/docker-compose.yml` |
| Host-mounted config | `/opt/moodle/config.php` (bind-mounted to `/var/www/html/config.php` — change here, takes effect immediately) |
| DB data volume | `/opt/moodle/dbdata/` |
| Moodle files | `/opt/moodle/moodledata/` |
| DB secrets | `/opt/moodle/.env` |
| Plugin patches | `/opt/moodle/patches/` (`choicelist_fixed.php`, `edwiseradvancedblock_lib.php`) |
| Plugin zips | `/opt/moodle/moodleplugins/` (all 13+ zips) |
| Lang overrides | `/var/moodledata/lang/en_local/*.php` (on-disk override point; persists in moodledata volume) |
| DB backups | `/opt/moodle/deploy/*.sql.gz` |

| Artefact | Location in this repo (`/Users/matthews/antigravity/Moodle_servers/`) |
|---|---|
| Canonical build guide | `Context/README.md` |
| Rehome runbook (per-domain) | `moodle_servers/www.<domain>/DEPLOYMENT.md` |
| Live config mirror | `moodle_servers/www.<domain>/config.php` |
| Live compose mirror | `moodle_servers/www.<domain>/docker-compose.yml` |
| Local build-context prep | `moodle_servers/www.<domain>/prepare-build-context.sh` |
| Live nginx site | `moodle_servers/www.<domain>/nginx_moodle.conf` |
| SSH keys | `Resources/ssh_keys/hetzner_moodle_ed25519` |
| Plugin zips (backup) | `Resources/moodle_plugins/` |
| This runbook | `Project_notes_folder/RUNBOOK_MOODLE_CLONE.md` |

---

## When to update THIS file

Every time a new rehome surfaces a gotcha not in this runbook, add it here AND log a matching D-/F- entry in PROJECT_NOTES.md. Treat this runbook as the single source of truth for "do another clone" — and keep it ruthlessly pruned: if a section is obsolete (e.g. a patch got upstreamed into a plugin release), remove it rather than hedge.
