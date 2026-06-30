# source-snapshot-5.78.128.44 — ProductionServerGr-1-8

The original snapshot / source-of-truth build server. Kept as a standby and clone source;
**do not edit** (Hetzner label `Do_not_edit`, delete-protection on).

| Field | Value |
|---|---|
| Hetzner server | ProductionServerGr-1-8-4gb-hil-3 (id 125507449) |
| IPv4 | 5.78.128.44 |
| Type / location | CPX21 / hil-dc1 (Hillsboro, OR) |
| Created | 2026-04-01 |
| Status | running; IP-only (no public domain) |
| Linked domain | none (intentional) |
| SSH key / access | historically `~/.ssh/schoolx` (the `emcs-moodle` key is rejected here) |

## Role
- Snapshot source for cloning new Moodle instances (see `RUNBOOK_MOODLE_CLONE.md`).
- The canonical build is documented in `../../BUILD_GUIDE.md`.
