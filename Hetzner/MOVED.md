# MOVED — Hetzner fleet docs now live at `E:\Claude\Hetzner`

**Date:** 2026-07-16

The Hetzner fleet documentation (per-project inventories, costing, server notes,
SSH key registry, cost dashboard) was consolidated into the canonical fleet folder:

**→ `E:\Claude\Hetzner\`** (its own git repo; see its `README.md` for the fleet map)

| What | New location |
|---|---|
| Fleet README (2026-07-13 snapshot) | `E:\Claude\Hetzner\archive\README-fleet-2026-07-13.md` (superseded by the new `README.md`) |
| `Default-14010860` inventory/costing | `E:\Claude\Hetzner\projects\schoolconex-external\` (project renamed "SchoolConex external modals") |
| `Hexstruct-15178002` inventory/costing | `E:\Claude\Hetzner\projects\schoolconex-confidential\` (project renamed "SchoolConex confidential servers") |
| `SchoolConex-Internal-Moodles-15188374` inventory/costing | `E:\Claude\Hetzner\projects\schoolconex-internal\` |
| `Cobionix` inventory | `E:\Claude\Hetzner\projects\cobionix\` |
| Per-server `notes.md` files | `E:\Claude\Hetzner\servers\<name>\notes.md` |
| `ssh_keys\KEYS.md` + `*.pub` | `E:\Claude\Hetzner\ssh_keys\` (private keys live ONLY in `C:\Users\msefa\.ssh\`; the redundant E-drive private-key copy was verified identical and deleted) |
| `cost-dashboard-2026-07-06.html` | `E:\Claude\Hetzner\archive\` |
| API tokens (`HCLOUD_*` in this repo's `.env`) | `E:\Claude\Hetzner\.env` (new per-project tokens, 2026-07-16) |

## What deliberately STAYS here (Moodle build/deploy artifacts)

- `Default-14010860\BUILD_GUIDE.md` — Moodle school-box build guide
- `Default-14010860\servers\app.canadaemcs.com\` — full Docker build context (Dockerfile, config.php, patches, branding)
- `Hexstruct-15178002\servers\www.codinginabox.com\nginx_codinginabox.conf`

These are Moodle-specific deploy artifacts tracked in the `conex321/moodle_servers` repo,
not fleet documentation. The new fleet docs cross-link back to them.
