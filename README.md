# Moodle_servers — repository map

This repo coordinates infrastructure, deployment configs, and documentation for the
Hetzner-hosted Moodle fleet (Canada EMCS and sister schools) plus the CodingInABox platform.

Organised **by cloud provider → project → server (labelled by its linked domain) → + costing**.

```
Hetzner/        All Hetzner Cloud infrastructure (the parent for servers, keys, costing)
  README.md       Fleet-wide index: every project, server, IP, domain, cost, SSH key
  ssh_keys/       SSH keys + KEYS.md (names, fingerprints, which servers use them)
  Default-14010860/   Hetzner Cloud project "Default" — the 5 Moodle CPX21 servers
    inventory.md      Live server stats (refreshed from the Hetzner API)
    costing.md        Per-server cost + monthly run-rate
    BUILD_GUIDE.md    How the source/production Moodle image is built
    servers/<domain>/ One folder per server, named after its linked domain
  Hextract-15178002/  Hetzner Cloud project "Hextract" — CodingInABox
    inventory.md / costing.md / servers/www.codinginabox.com/

Hostinger/      Domain registrar data (the source of the domain labels above)
  domains/        Portfolio snapshots (CSV/XLSX) pulled from the Hostinger API
  tools/          refresh.mjs + helpers that regenerate the snapshots

Project_notes_folder/   PROJECT_NOTES.md, CHANGELOG.md, runbooks (cross-session memory)
Resources/moodle_plugins/   Shared vendor plugin library (gitignored archives)
```

Fleet docs moved 2026-07-16 to **`E:\Claude\Hetzner`** (see `Hetzner/MOVED.md`); `Hetzner/` here now
holds only Moodle build/deploy artifacts. Live fleet snapshot: `E:\Claude\Hetzner\README.md`.

> Secrets (`.env`, SSH private keys, `node_modules/`) are gitignored and never committed.
