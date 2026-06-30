# Remote Moodle Server Build Guide

**Current instance:** `5.78.128.44` (Hetzner VPS, Ubuntu 22.04.5, 4 GB RAM, 3 vCPU, 75 GB disk)
**Moodle version:** 5.1.3 (stable, March 2026)
**Deploy date:** 2026-04-18
**Last synced from local:** 2026-04-18

This document describes exactly how the production Moodle server at `5.78.128.44` is built, so that a snapshot can be cloned, rehomed to a new domain, or rebuilt from scratch on a fresh VPS. Every file, port, volume, and fix that was needed is listed.

---

## 1. Host requirements

| Requirement | Value |
|---|---|
| OS | Ubuntu 22.04 LTS (Jammy) — 20.04 and 24.04 also work |
| RAM | **4 GB minimum** (8 GB recommended for 100+ concurrent users) |
| Disk | **80 GB minimum** (local moodledata is ~36 GB, expect growth with new uploads) |
| vCPU | 2+ (3 is comfortable; 4+ for production) |
| Network | Public IP, port 80 open inbound. Port 3306 / 8080 should NOT be exposed publicly. |
| Docker | `docker` ≥ 20.10 + `docker compose` plugin (v2) |
| Root SSH | Required for deploy scripts (`root@5.78.128.44`, via key `~/.ssh/schoolx`) |

### Host-level packages that must exist

```bash
apt update && apt install -y \
  docker.io docker-compose-plugin \
  rsync \
  git \
  curl unzip
```

**Gotcha:** The currently-running production host *also* has a **native MariaDB** listening on `127.0.0.1:3306` (system service). It conflicts with any container trying to bind `0.0.0.0:3306` externally. The containerized Moodle uses internal docker networking to reach MariaDB, so this doesn't break Moodle, but you may want to `systemctl disable --now mariadb` to reclaim ~200 MB RAM if you don't need the host DB.

---

## 2. Directory layout on the host

Everything lives under **`/opt/moodle/`**:

```
/opt/moodle/
├── Dockerfile                          ← Image recipe (Moodle 5.1.3 + plugins + theme)
├── docker-compose.yml                  ← 3-container stack definition
├── config.php                          ← Moodle runtime config (loaded into container)
├── entrypoint.sh                       ← Container startup script (chmod moodledata, start apache)
├── php.ini                             ← PHP overrides (max upload, memory, etc.)
├── .env                                ← DB passwords (NOT committed — generated per-host)
├── patches/
│   └── choicelist_fixed.php            ← Fix for Moodle 5.1.3 PHP warning in choicelist.php
├── plugins-src/
│   └── interactivevideo/               ← mod_interactivevideo v1.7.2 source (from sokunthearithmakara/moodle-mod_interactivevideo)
├── moodleplugins/
│   ├── Edwiser-RemUI-v5.1.2.zip        ← Theme (contains theme_remui.zip inside)
│   ├── block_edwiseradvancedblock.zip  ← Required by Page Builder
│   ├── filter_edwiserpbf.zip           ← Required by Page Builder
│   └── ...other Edwiser plugins
├── moodledata/                         ← Moodle's runtime data storage (~36 GB when populated)
│   ├── filedir/                        ← Uploaded files (SCORMs, DOCX, videos, etc.)
│   ├── cache/
│   ├── localcache/
│   ├── muc/                            ← Moodle Universal Cache
│   ├── sessions/
│   ├── lock/                           ← Lock files (file_lock_factory; we use db_record_lock_factory instead)
│   ├── temp/
│   └── trashdir/
├── dbdata/                             ← MariaDB data volume (persistent DB storage)
└── deploy/
    ├── sync_to_remote.sh               ← Local→remote sync tool (--db, --files, --full)
    ├── moodle_dump.sql.gz              ← Latest DB dump from local (cached)
    ├── moodle_dump_backup.sql.gz       ← Remote's pre-sync DB snapshot (auto-created by sync tool)
    └── post_deploy_remote.sh           ← First-time bootstrap script (run by deploy.sh setup)
```

The `dbdata/` and `moodledata/` directories are the only two pieces of persistent mutable state — everything else is derivable from source (`Dockerfile`, `docker-compose.yml`, `plugins-src/`, `moodleplugins/`, `config.php`, `.env`, `patches/`).

---

## 3. The three containers

Stack defined in `/opt/moodle/docker-compose.yml`:

```yaml
services:
  mariadb:
    image: mariadb:10.11
    container_name: moodle-mariadb
    environment:
      MYSQL_ROOT_PASSWORD: rootpass              # overridden by .env in production
      MYSQL_DATABASE: moodle
      MYSQL_USER: moodleuser
      MYSQL_PASSWORD: moodlepass                 # hardcoded; matches config.php $CFG->dbpass
      MYSQL_CHARACTER_SET_SERVER: utf8mb4
      MYSQL_COLLATION_SERVER: utf8mb4_unicode_ci
    volumes:
      - ./dbdata:/var/lib/mysql                  # persistent DB data
    # NO "ports:" stanza in production — external 3306 would conflict with host mariadb
    healthcheck:
      test: ["CMD", "healthcheck.sh", "--connect", "--innodb_initialized"]
      interval: 10s

  moodle:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: moodle-app
    depends_on:
      mariadb:
        condition: service_healthy
    ports:
      - "80:80"                                  # PRODUCTION. Local dev uses 8888:80
    volumes:
      - ./moodledata:/var/moodledata
      - ../Generated_Content:/data/Generated_Content:ro                          # optional; empty on prod
      - ./moodle_course_organization:/data/moodle-local/moodle_course_organization:ro  # optional; empty on prod
    environment:
      MOODLE_DB_HOST: mariadb
      MOODLE_DB_NAME: moodle
      MOODLE_DB_USER: moodleuser
      MOODLE_DB_PASS: moodlepass

  phpmyadmin:
    image: phpmyadmin:latest
    container_name: moodle-phpmyadmin
    depends_on:
      - mariadb
    ports:
      - "127.0.0.1:8080:80"                      # internal only; SSH-tunnel to reach it
    environment:
      PMA_HOST: mariadb
      PMA_PORT: 3306
      MYSQL_ROOT_PASSWORD: rootpass
```

**Key differences from the local dev compose:**
- `mariadb` has **no external port mapping** in production (local has `3306:3306`)
- `moodle` exposes **port 80** (local uses `8888:80`)
- `phpmyadmin` binds to `127.0.0.1:8080` only (SSH-tunnel to access externally)

The two optional volume mounts (`Generated_Content`, `moodle_course_organization`) are bind mounts referenced by `build_full_course.php` during course deploys. They're **empty on production** (auto-created as empty dirs by Docker) and don't affect runtime Moodle behavior — all student-facing content is stored inside `moodledata/filedir/` via Moodle's file storage API. The mounts exist only so the same `docker-compose.yml` works on both environments without modification.

---

## 4. The Moodle image

Built from `/opt/moodle/Dockerfile`. Key layers:

| Layer | Purpose |
|---|---|
| `FROM php:8.3-apache` | Base image |
| `apt install` | System libs (libpng, libjpeg, libicu, libxml2, libzip, libsodium, libldap2, unzip, wget, cron) |
| `docker-php-ext-install` | PHP extensions: curl, gd, intl, mbstring, soap, sodium, zip, exif, opcache, mysqli, pdo_mysql |
| `pecl install igbinary redis` | Optional caching extensions |
| `a2enmod rewrite` | Apache mod_rewrite for clean URLs |
| `wget moodle-5.1.3.tgz` | Download + extract Moodle source to `/var/www/html/` |
| `COPY moodleplugins/Edwiser-RemUI-v5.1.2.zip` + unzip | Install Edwiser RemUI theme into `/var/www/html/public/theme/remui/` |
| `COPY moodleplugins/block_edwiseradvancedblock.zip` + unzip | Install Edwiser advanced block |
| `COPY moodleplugins/filter_edwiserpbf.zip` + unzip | Install Edwiser Page Builder filter |
| `COPY plugins-src/interactivevideo` | Install `mod_interactivevideo` v1.7.2 into `/var/www/html/public/mod/interactivevideo/` |
| `COPY patches/choicelist_fixed.php` | Patch a Moodle 5.1.3 "undefined none" warning |
| `APACHE_DOCUMENT_ROOT=/var/www/html/public` | Moodle 5.x serves from `public/` |
| `mkdir /var/moodledata && chmod 777` | Runtime data directory |
| `COPY php.ini` | PHP overrides |
| `COPY config.php` | Moodle runtime config at `/var/www/html/config.php` (NOT inside public/) |
| `COPY entrypoint.sh` | Startup script |
| `ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]` | Runs `chmod -R 777 /var/moodledata` + `apache2-foreground` |

**Important:** The Dockerfile bakes IV plugin and RemUI theme IN. If you rebuild the image without these files in the context, Moodle will fail because the DB references plugins that have no code. Always keep:
- `plugins-src/interactivevideo/` with a valid `version.php`
- `moodleplugins/Edwiser-RemUI-v5.1.2.zip`

at the build context root before running `docker compose build moodle`.

### Key gotcha documented in memory

> "`docker compose up -d` recreates containers from the cached image if the image exists. If the Dockerfile has changed since the image was built, the NEW COPY/RUN steps do not run. Any plugins installed via `docker cp` into the running container get wiped." — See [memory/reference_mod_interactivevideo.md](../../memory/reference_mod_interactivevideo.md)

Always run `docker compose build moodle` **before** `docker compose up -d moodle` when the Dockerfile or any file it COPYs has changed.

---

## 5. `config.php` (runtime config)

Lives at `/opt/moodle/config.php` (host), bind-mounted (via `COPY` in Dockerfile) to `/var/www/html/config.php` in the container.

```php
<?php  // Moodle configuration file — PRODUCTION (5.78.128.44)

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'mariadb';            // container name on the docker network
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'moodleuser';
$CFG->dbpass    = 'moodlepass';         // matches MYSQL_PASSWORD in docker-compose.yml
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array(
    'dbpersist'   => 0,
    'dbport'      => 3306,
    'dbsocket'    => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = 'http://5.78.128.44';  // CHANGE FOR NEW DOMAIN
$CFG->dataroot  = '/var/moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0770;

// Lock factory — required; without this, sessions fail with
// "Unable to find a default lock instance"
$CFG->lock_factory = "\core\lock\db_record_lock_factory";

require_once(__DIR__ . '/lib/setup.php');
```

**⚠ CRITICAL — must set `$CFG->lock_factory`** or Moodle will fail on every page load after a fresh DB restore with `<div class='alert-danger'>Unable to find a default lock instance.</div>`. Use `db_record_lock_factory` (preferred) or `file_lock_factory`. See [memory/reference_moodle_lock_factory.md] or the remote-deploy-2026-04-18.md report in `ops/reports/`.

### To change the domain

1. Edit `/opt/moodle/config.php` → change `$CFG->wwwroot` to the new URL (e.g., `https://moodle.example.com`).
2. Also update the `wwwroot` config in the DB:
   ```bash
   docker exec moodle-mariadb mysql -uroot -p<ROOT_PW> moodle -e \
     "UPDATE mdl_config SET value='https://moodle.example.com' WHERE name='wwwroot';"
   ```
3. Purge caches: `docker exec moodle-app php /var/www/html/admin/cli/purge_caches.php`
4. If enabling HTTPS via reverse proxy, uncomment `$CFG->sslproxy = true;` in config.php and purge again.

---

## 6. `.env` (secrets)

Lives at `/opt/moodle/.env`:

```
MYSQL_ROOT_PASSWORD=<auto-generated-24-char>
MYSQL_PASSWORD=<auto-generated-24-char>
```

**NOT committed to git.** Generated once by `deploy/deploy.sh prepare` via `openssl rand -base64 24`. Load it into docker-compose via `env_file:` or by sourcing before `docker compose up`.

**Note:** The app's `$CFG->dbpass` is hardcoded to `'moodlepass'` (plain text), which matches the `MYSQL_PASSWORD` entry for the `moodleuser@%` grant. This means the `.env` `MYSQL_PASSWORD` MUST equal `moodlepass`, OR you must update `$CFG->dbpass` to match. The current setup uses `moodlepass` for both.

---

## 7. Data persistence — what survives container recreation

| Path on host | Contents | Survives `docker compose up -d`? | Survives VPS rebuild? |
|---|---|---|---|
| `/opt/moodle/dbdata/` | MariaDB DB files | ✅ yes (bind mount) | ❌ only if copied |
| `/opt/moodle/moodledata/` | Moodle file storage (SCORMs, DOCX, user uploads) | ✅ yes (bind mount) | ❌ only if copied |
| Container filesystem (ephemeral) | Moodle PHP code, installed plugins | ❌ wiped on recreation | ❌ recreated from image |

To snapshot **everything needed for disaster recovery**:
- `/opt/moodle/dbdata/` (or a `mysqldump`)
- `/opt/moodle/moodledata/`
- `/opt/moodle/` (config, Dockerfile, all the source)
- The Docker image itself (optional — can be rebuilt from source)

---

## 8. Build the server from scratch (fresh VPS)

### 8.1 Bootstrap

```bash
# On a fresh Ubuntu 22.04 VPS, as root:
apt update && apt install -y docker.io docker-compose-plugin rsync unzip curl
systemctl enable --now docker

# Create the tree
mkdir -p /opt/moodle
cd /opt/moodle
```

### 8.2 Transfer build context from local

From your local workstation:

```bash
cd c:/Users/msefa/Documents/Claude/Gr1_8/moodle-local
tar -czf - Dockerfile docker-compose.yml config.php php.ini entrypoint.sh patches plugins-src moodleplugins | \
  ssh root@NEW.SERVER.IP "cd /opt/moodle && tar -xzf -"
```

### 8.3 Generate `.env`

```bash
ssh root@NEW.SERVER.IP bash -c '
  cd /opt/moodle
  MYSQL_ROOT_PASSWORD=$(openssl rand -base64 24)
  MYSQL_PASSWORD=moodlepass   # must match $CFG->dbpass in config.php
  cat > .env <<EOF
MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
MYSQL_PASSWORD=${MYSQL_PASSWORD}
EOF
'
```

### 8.4 Edit `/opt/moodle/config.php` for the new domain

SSH into the new server and set `$CFG->wwwroot` to the new URL (e.g. `http://NEW.SERVER.IP` or `https://yourdomain.com`).

### 8.5 Build image + start

```bash
ssh root@NEW.SERVER.IP "
  cd /opt/moodle
  # Create placeholder dirs so optional bind mounts don't fail
  mkdir -p /opt/Generated_Content /opt/moodle/moodle_course_organization
  docker compose build moodle
  docker compose up -d
  sleep 60   # let chmod -R 777 moodledata finish on first boot
"
```

### 8.6 Restore DB + moodledata from snapshot

```bash
# DB restore
gunzip -c moodle_dump.sql.gz | \
  ssh root@NEW.SERVER.IP "docker exec -i moodle-mariadb mysql -uroot -p<MYSQL_ROOT_PASSWORD> moodle"

# moodledata transfer (36 GB — tar-over-ssh; use rsync if available for incremental)
tar -czf - moodledata/ | ssh root@NEW.SERVER.IP "cd /opt/moodle && tar -xzf -"
ssh root@NEW.SERVER.IP "chown -R 33:33 /opt/moodle/moodledata && chmod -R 770 /opt/moodle/moodledata"
```

### 8.7 Post-restore fixups

```bash
ssh root@NEW.SERVER.IP "
  # Update wwwroot in DB to match the new domain
  docker exec moodle-mariadb mysql -uroot -p<MYSQL_ROOT_PASSWORD> moodle -e \
    \"UPDATE mdl_config SET value='http://NEW.DOMAIN' WHERE name='wwwroot';\"

  # Grant DB permissions to moodleuser (needed after DROP+CREATE restore)
  docker exec moodle-mariadb mysql -uroot -p<MYSQL_ROOT_PASSWORD> moodle -e \
    \"GRANT ALL PRIVILEGES ON moodle.* TO 'moodleuser'@'%' IDENTIFIED BY 'moodlepass'; FLUSH PRIVILEGES;\"

  # Clear caches (crucial — old wwwroot and file hashes are stuck in muc/)
  find /opt/moodle/moodledata/muc /opt/moodle/moodledata/cache /opt/moodle/moodledata/localcache \
    -mindepth 1 -delete
  docker exec moodle-app php /var/www/html/admin/cli/purge_caches.php

  # Reset admin password
  docker exec moodle-app php /var/www/html/admin/cli/reset_password.php --username=admin --password=NEW_ADMIN_PW
"
```

---

## 9. Cloning / snapshot → new domain

If you snapshot the VPS and rehome it to a new IP/domain:

1. **Boot the clone** and SSH in as root.
2. **Verify containers come up** (`docker ps` — all 3 should be running; if not, `cd /opt/moodle && docker compose up -d`).
3. **Update the domain everywhere it's referenced:**
   ```bash
   NEW_DOMAIN="https://example.com"     # with scheme!
   # (a) config.php
   sed -i "s|\$CFG->wwwroot   = '[^']*';|\$CFG->wwwroot   = '$NEW_DOMAIN';|" /opt/moodle/config.php
   # (b) DB
   docker exec moodle-mariadb mysql -uroot -p$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) moodle \
     -e "UPDATE mdl_config SET value='$NEW_DOMAIN' WHERE name='wwwroot';"
   # (c) Purge caches
   find /opt/moodle/moodledata/muc /opt/moodle/moodledata/cache /opt/moodle/moodledata/localcache -mindepth 1 -delete
   docker exec moodle-app php /var/www/html/admin/cli/purge_caches.php
   ```
4. **Enable HTTPS (recommended):**
   - Install a reverse proxy (Nginx / Caddy / Traefik) on the host, terminate TLS there, proxy `http://127.0.0.1:80` to the Moodle container.
   - In `config.php`, uncomment `$CFG->sslproxy = true;`.
   - Update `$CFG->wwwroot` to `https://...`.
   - Purge caches again.
5. **Test:** `curl -I http://NEW.DOMAIN/login/index.php` → expect `HTTP/1.1 200 OK`.

---

## 10. Current production state (snapshot as of 2026-04-18)

| Item | Value |
|---|---|
| Moodle version | 5.1.3 (Build 20260216) |
| Docker image tag | `moodle-moodle:latest` |
| Courses | 55 (Grades 1-8 curriculum; MAT, LAN, SCI, ART, SST, HPE, CFR, GEO, HIS) |
| Total course_modules | 9,971 |
| Interactive Videos (mod_interactivevideo v1.7.2) | 2,017 |
| SCORM packages | 2,025 |
| Assignments | 2,618 |
| File resources (binders, teacher docs, outlines) | 703 |
| moodledata filedir size | ~36 GB |
| Active theme | Edwiser RemUI v5.1.2 |
| wwwroot | `http://5.78.128.44` |
| debug / debugdisplay | 0 / 0 (production) |
| lock_factory | `\core\lock\db_record_lock_factory` |

---

## 11. User accounts on the current production instance

| Account | Username | Password | Purpose |
|---|---|---|---|
| Site admin | `admin` | `Schoolx2024!` | Full control |
| Demo student | `demo` | `DemoStudent2026!` | Enrolled in MAT01–MAT08 for demonstration |

**⚠ Change these before going live** to end users. Use `admin/cli/reset_password.php` inside the moodle-app container.

---

## 12. Maintenance commands cheatsheet

```bash
# SSH alias for remote (in your ~/.ssh/config, target = root@5.78.128.44, key = ~/.ssh/schoolx)
ssh schoolx

# --- Health ---
docker ps --format '{{.Names}}\t{{.Status}}'
curl -I http://5.78.128.44/login/index.php

# --- Enable/disable maintenance mode ---
docker exec moodle-app php /var/www/html/admin/cli/maintenance.php --enable
docker exec moodle-app php /var/www/html/admin/cli/maintenance.php --disable

# --- Purge caches ---
docker exec moodle-app php /var/www/html/admin/cli/purge_caches.php

# --- Reset a user password ---
docker exec moodle-app php /var/www/html/admin/cli/reset_password.php --username=USERNAME --password='NEW_PW'

# --- Run Moodle upgrade after code changes ---
docker exec moodle-app php /var/www/html/admin/cli/upgrade.php --non-interactive

# --- DB shell ---
docker exec -it moodle-mariadb mysql -uroot -p$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) moodle

# --- DB backup ---
docker exec moodle-mariadb mysqldump -uroot -p$(grep MYSQL_ROOT_PASSWORD /opt/moodle/.env | cut -d= -f2) \
  --single-transaction --routines --triggers moodle | gzip > /opt/moodle/deploy/backup-$(date +%Y%m%d).sql.gz

# --- Rebuild image after Dockerfile / plugin / theme changes ---
cd /opt/moodle && docker compose build moodle && docker compose up -d moodle

# --- Full container restart (rare) ---
cd /opt/moodle && docker compose down && docker compose up -d

# --- Tail apache logs ---
docker logs -f moodle-app

# --- phpMyAdmin over SSH tunnel (from your workstation) ---
ssh -L 8080:localhost:8080 schoolx
# then open http://localhost:8080 in your browser
```

---

## 13. What to snapshot for disaster recovery

Minimum viable backup (can rebuild everything from these):

1. `/opt/moodle/dbdata/` (or a `mysqldump` — preferred for portability)
2. `/opt/moodle/moodledata/` (excluding `cache`, `localcache`, `muc`, `sessions`, `temp`, `trashdir`, `lock`)
3. `/opt/moodle/.env` (so you can keep the same DB root password, or regenerate later)
4. `/opt/moodle/config.php` (has the `wwwroot` + DB creds)

Everything else — `Dockerfile`, `docker-compose.yml`, `plugins-src/`, `moodleplugins/`, `patches/`, `entrypoint.sh`, `php.ini` — can be re-fetched from the git repo at `c:\Users\msefa\Documents\Claude\Gr1_8\moodle-local\`.

---

## 14. Sync from local to remote (ongoing operations)

Use [moodle-local/deploy/sync_to_remote.sh](../deploy/sync_to_remote.sh) from your workstation:

| Mode | What it does | Typical duration |
|---|---|---|
| `--db` | Dump local DB → transfer → drop + reimport on remote → re-grant → fixups | 30-60 sec |
| `--files` | rsync moodledata (incremental) OR tar-over-ssh fallback (full) | 1-30 min depending on rsync availability |
| `--config` | Sync only `mdl_config` + `mdl_config_plugins` tables | 5-10 sec |
| `--full` | `--db` + `--files` | Typically 1-5 min with rsync; 30 min with tar fallback |
| `--verify` | Compare row counts + file hashes between local and remote | 15-20 sec |
| `--dry-run` | Print what would happen without executing | < 1 sec |

Example: `bash moodle-local/deploy/sync_to_remote.sh --db --verify`

**⚠ Install rsync on the Windows host** (via Chocolatey: `choco install rsync`, or via WSL) to avoid the slow tar-over-ssh fallback for file sync.

---

## 15. Known quirks / lessons learned

1. **Lock factory MUST be set** in `config.php` after a fresh DB restore. Default behavior without it is: every session creation fails with "Unable to find a default lock instance."
2. **`chmod -R 777 /var/moodledata` on container boot** can take 1-5 minutes on a populated 36 GB `moodledata/`. The container is not responsive on port 80 during that time. Plan for this delay.
3. **Moodle 5.x serves from `/public/`** — the `APACHE_DOCUMENT_ROOT` env var in the Dockerfile handles this; `config.php` stays at `/var/www/html/config.php` (one level up).
4. **`docker compose up -d` recreates from the cached image** without rebuilding. Any filesystem changes made via `docker cp` or `docker exec` are ephemeral. Always `docker compose build` before `up -d` when you've changed anything in the image.
5. **phpmyadmin on 8080 externally is a security hole** in production — keep it bound to 127.0.0.1 and access via SSH tunnel only.
6. **Port 3306 external is not needed** in production and creates conflicts with host-native mariadb.
7. **Vimeo videos referenced by `interactivevideo` modules are hosted externally** — don't forget to configure Vimeo domain-locking for the new domain (via the Vimeo dashboard) when rehoming.

---

## 16. Related documentation

- [moodle-local/deploy/sync_to_remote.sh](../deploy/sync_to_remote.sh) — local↔remote sync tool
- [moodle-local/deploy/post_deploy_remote.sh](../deploy/post_deploy_remote.sh) — first-time remote bootstrap
- [moodle-local/Dockerfile](../Dockerfile) — Moodle image recipe
- [moodle-local/docker-compose.yml](../docker-compose.yml) — stack definition
- [ops/reports/remote-deploy-2026-04-18.md](../../ops/reports/remote-deploy-2026-04-18.md) — latest deploy narrative + incident fixes
- [ops/reports/final-v3-rollout-2026-04-18.md](../../ops/reports/final-v3-rollout-2026-04-18.md) — course structure + content summary
- [Project_notes/2026-04-18_session_moodle_v3_rollout.md](../../Project_notes/2026-04-18_session_moodle_v3_rollout.md) — session retrospective
