# Session 2026-07-16 — Hetzner fleet consolidation

**Agent:** Claude
**Duration / scope:** Full-day infra session; fleet-wide (all 5 Hetzner projects, 20 servers)
**Related decisions:** D-20260716-2140, D-20260716-2150 (in `decisions.md`)

## What was done
- Created canonical `E:\Claude\Hetzner\` (own git repo, `.gitignore` committed first; `.env` with 5 NEW per-project tokens from Matt; legacy tokens preserved as comments).
- Reconciled tokens→projects by server-set overlap (100% matches): Default→SchoolConex-External, Hexstruct→SchoolConex-Confidential, Internal/Cobionix unchanged, Personal new+empty. Discovered unprotected `atlas-app` (87.99.135.38, created 07-14) → registry + delete-protection + backups applied.
- Moved fleet docs out of this repo (`git rm` + push, `Hetzner/MOVED.md` breadcrumb); stripped `HCLOUD_*`/`HETZNER_CONSOLE_*` from this repo's `.env`.
- `_ops` cutover: refresh-fleet.ps1 v2 (registry-driven 5 projects, per-server EUR cost, ping+SSH health, generated inventory/costing), collector.ps1 env path, FleetRefresh weekly→daily 03:00, dashboard `/api/fleet` + "Fleet detail" UI section. Fixed Hetzner API change: server location is `server.location`, not `server.datacenter.location`.
- SSH: new `hetzner_fleet_ed25519`, audited 20 servers × 6 keys, deployed to 18/20 authorized_keys, `~/.ssh/config.d/hetzner-fleet` aliases (`ssh hz-<name>`), key registered in all 5 projects; gaps in `E:\Claude\Hetzner\SSH-GAPS.md` (3Sixty-Education, Jaswant-Singh-Khalra-Khalsa).
- hcloud contexts recreated: sc-external / sc-confidential / sc-internal / cobionix / personal.

## Files touched
E:\Claude\Hetzner\** (new repo); this repo: .env (stripped), CLAUDE.md, README.md, Hetzner\MOVED.md (+ git rm of moved docs, pushed 10f5cd5+790988f); _ops\registry\{refresh-fleet.ps1,apply-hetzner-protection.ps1,infrastructure.json}; _ops\dashboard\{collector.ps1,server.js,public\index.html}; _ops\backup\{restic-includes.txt,HETZNER-BACKUP-CHECKLIST.md}; C:\Users\msefa\.ssh\{config,config.d\hetzner-fleet,hetzner_fleet_ed25519*}; Task Scheduler FleetRefresh.

## Decisions made
See decisions.md D-20260716-2140 (consolidation layout) and D-20260716-2150 (fleet SSH key strategy).

## Failures encountered
- PS 5.1 wraps native ssh stderr into ErrorRecords under `$ErrorActionPreference='Stop'` → audit script uses `cmd /c ... 2>nul`.
- Hetzner API no longer returns `datacenter` on server objects → all price/location lookups switched to `server.location.name`.
- `hcloud context create` needs `--token-from-env` non-interactively.

## Handoff notes
- Run-rate EUR 882.52/mo; biggest lever = 12 US cpx21 @ 37.49 vs 10.99 EU (~EUR 318/mo premium).
- Tokens transited chat → rotate in console when convenient (README rule).
- A parallel session moved this repo to SchoolConex\SchoolConex_Active_servers\ the same day; all fallback paths were kept in sync.
