# Moodle_servers — Claude Code project notes

Infrastructure + operations workspace for the SchoolConex / Canada EMCS **Moodle server fleet on
Hetzner Cloud** (plus Hostinger registrar/DNS). Root: `E:\Claude\Moodle_servers`.

## Notes & maps process (keep current every session)
- **`Project_notes_folder/PROJECT_NOTES.md`** — current state, architecture decisions (`D-###`), accomplishments.
  Read this first to orient. **`Project_notes_folder/CHANGELOG.md`** — append-only audit log (one block per change).
  Maintain both via the `update-project-notes` skill after any material change.
- **`file_structure.md`** (root) — navigational file map; maintain via the `maintain-file-structure` skill
  after any file create/delete/move.
- **`Project_notes_folder/RUNBOOK_MOODLE_CLONE.md`** — canonical process for cloning/rehoming a Moodle
  instance onto a new VPS. Reuse it verbatim for new deployments.
- **`E:\Claude\Hetzner\README.md`** — single source of truth for all Hetzner projects, servers, and
  run-rate. **MOVED 2026-07-16** from `Hetzner/README.md` here (see `Hetzner/MOVED.md`); this folder keeps
  only Moodle build/deploy artifacts under `Hetzner/`.

## Hetzner Cloud (see `E:\Claude\Hetzner\README.md` + per-project `projects\<slug>\inventory.md`/`costing.md`)
- Projects & tokens (tokens in gitignored `E:\Claude\Hetzner\.env`, renamed in console 2026-07-16):
  - `SchoolConex external modals` (14010860, formerly `Default`) — Moodle fleet. `HCLOUD_TOKEN_SC_EXTERNAL`.
  - `SchoolConex confidential servers` (15178002, formerly `Hexstruct`) — CodingInABox + `schoolconex-finance` + sclearn + atlas-app. `HCLOUD_TOKEN_SC_CONFIDENTIAL`.
  - `SchoolConex internal moodle` (15188374) — `hs.schoolconex.com` high-school Moodle. `HCLOUD_TOKEN_SC_INTERNAL`.
  - `Cobionix` — PM dashboard + brain box. `HCLOUD_TOKEN_COBIONIX`. `Personal projects` (empty) — `HCLOUD_TOKEN_PERSONAL`.
- **Server limit is account-wide across ALL projects** — an empty slot in one project does NOT bypass it.
  Raising it requires the console/Hetzner support.
- **The Hetzner console browser is blocked by anti-bot ("Heray")** — creating projects/API tokens must be
  done by the user in the console; server creation *inside* an existing project works via `hcloud`/API.
- SSH keys: `E:\Claude\Hetzner\ssh_keys\KEYS.md`. Private keys live at `C:\Users\msefa\.ssh\` (E:-drive
  copies hit OpenSSH permission errors). Fleet-wide aliases: `ssh hz-<server>` via `~/.ssh/config.d/hetzner-fleet`.

## DNS (Hostinger)
- All domains registered at Hostinger (nameservers `ns1/ns2.dns-parking.com`). API token in `Hostinger/tools/.env`
  (`HOSTINGER_API_TOKEN`). DNS API `PUT /api/dns/v1/zones/{domain}` — **requires a browser User-Agent** or
  Cloudflare returns 1010. Domain/DNS exports in `Hostinger/domains/*.csv`.

## Secrets
- Root `.env` is **gitignored** — holds `MOODLE_GCP_*` and `FINANCE_PG_*`. Hetzner tokens + console creds
  MOVED 2026-07-16 to `E:\Claude\Hetzner\.env`. **Never commit either or print secret values.**

## Gotchas
- `curl`/`wget` in the Bash tool are intercepted by the context-mode hook — for HTTP calls use the
  `ctx_execute` MCP tool (Python `urllib`) or fetch keys on-server via `python3` inside an SSH heredoc.
- Docker Desktop on this workstation is the source for local Moodle clones; it can be stopped between sessions.
