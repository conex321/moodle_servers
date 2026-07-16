# File Structure — Moodle_servers

**Project root:** E:\Claude\SchoolConex\SchoolConex_Active_servers\Moodle_servers
**Last updated:** 2026-07-16T22:05Z
**Last agent:** Claude
**Total tracked files:** ~120 tracked (262 on disk excl. `.git`/`node_modules`, incl. gitignored screenshots/takeout/plugin zips)
**Total tracked directories:** ~23

## Top-level layout
Moodle **build/deploy** workspace for the SchoolConex / Canada EMCS school fleet, plus Hostinger
(registrar/DNS) data and plugin/asset resources. **Two 2026-07-16 changes:** (1) the repo root moved
here from `E:\Claude\Moodle_servers` (workspace reorg); (2) all Hetzner FLEET documentation (project
inventories, costing, server notes, ssh-key registry, API tokens) moved to the canonical
**`E:\Claude\Hetzner\`** repo — `Hetzner/` here now holds only Moodle build contexts
(see `Hetzner/MOVED.md`).

## Directory map
```
Moodle_servers/
├── Hetzner/                                   # ONLY Moodle build/deploy artifacts (fleet docs → E:\Claude\Hetzner)
│   ├── MOVED.md                               # what moved where on 2026-07-16
│   ├── Default-14010860/                      # BUILD_GUIDE.md + prod build context
│   │   └── servers/app.canadaemcs.com/        # LIVE prod Moodle build context: Dockerfile, compose, config.php,
│   │       ├── branding-assets-2026-07-07/    #   php.ini, DEPLOYMENT.md, rebrand + branding backups
│   │       ├── moodleplugins/                 #   build-context plugin zips (generated, gitignored)
│   │       └── patches/                       #   choicelist/edwiser/remui overlay patches (7 files)
│   └── Hexstruct-15178002/
│       └── servers/www.codinginabox.com/      # nginx_codinginabox.conf
├── Hostinger/                                 # registrar + DNS (nameservers ns1/ns2.dns-parking.com)
│   ├── domains/                               # hostinger_domains/websites/subdomains CSV + xlsx exports
│   └── tools/                                 # Node Hostinger-API tooling (refresh.mjs, deploy helpers); node_modules ignored
├── Project_notes_folder/                      # persistent cross-agent notes — SPLIT MODE since 2026-07-16
│   ├── PROJECT_NOTES.md                        # index + Current State + Open Questions (entry point)
│   ├── decisions.md / file-map.md / failures.md / context.md
│   ├── CHANGELOG.md (+ CHANGELOG_archive_2026-04_05.md)
│   ├── RUNBOOK_MOODLE_CLONE.md                 # canonical clone/rehome-to-new-VPS runbook
│   └── sessions/                               # INDEX.md + per-session files + pre-split accomplishments log
├── Resources/                                 # build inputs + assets
│   ├── moodle_plugins/                         # canonical plugin zip library (~80 files)
│   ├── screenshots/                            # QA/branding screenshots (26 files, gitignored)
│   └── Resources-20260613T183107Z-3-001/       # Google-takeout copy (gitignored)
├── CLAUDE.md                                  # project context (notes process, pointer to E:\Claude\Hetzner, DNS, secrets)
├── README.md                                  # repo orientation map
└── file_structure.md                          # this map
```

## Notable files at root
- `CLAUDE.md` — project instructions; all fleet matters now point at `E:\Claude\Hetzner`.
- `README.md` — orientation; fleet-snapshot pointer updated 2026-07-16.
- `file_structure.md` — this navigational map (maintained by the `maintain-file-structure` skill).
- `.env` — **gitignored**; now ONLY `MOODLE_GCP_*` + `FINANCE_PG_*` (Hetzner tokens + console creds moved to `E:\Claude\Hetzner\.env` 2026-07-16). Never commit.

## Recent changes
- 2026-07-16T22:05Z [Claude] ~move:repo root E:\Claude\Moodle_servers → E:\Claude\SchoolConex\SchoolConex_Active_servers\Moodle_servers (parallel reorg session)
- 2026-07-16T22:05Z [Claude] -dir:Hetzner fleet docs → E:\Claude\Hetzner (README, 4x inventory/costing, 6x server notes.md, ssh_keys/KEYS.md + *.pub, cost-dashboard html); +file:Hetzner/MOVED.md
- 2026-07-16T22:05Z [Claude] -file:Hetzner/ssh_keys/hetzner_codinginabox_ed25519 (redundant private-key copy; verified identical to ~/.ssh copy, deleted)
- 2026-07-16T22:05Z [Claude] ~split:Project_notes_folder/PROJECT_NOTES.md → decisions.md, file-map.md, failures.md, context.md, sessions/ (500-line limit)
- 2026-07-07T21:40Z [Claude] +file:Hetzner/Default-14010860/servers/app.canadaemcs.com/branding-backup-2026-07-07.json
- 2026-07-07T21:40Z [Claude] +file:Hetzner/Default-14010860/servers/app.canadaemcs.com/rebrand-emcs-2026-07-07.php
- 2026-07-07T21:40Z [Claude] +dir:Hetzner/Default-14010860/servers/app.canadaemcs.com/branding-assets-2026-07-07 (emcs-mark-512.png, emcs-lockup.png, install-logos.php, emcs-logo-source.html)
- 2026-07-06T00:00Z [Claude] ~rename:Hetzner/cost-dashboard-2026-07-03.html → Hetzner/cost-dashboard-2026-07-06.html (refreshed to 15-server live audit, $628.79/mo)
- 2026-07-03T18:20Z [Claude] bootstrap: initial map of ~135 files across ~30 directories
