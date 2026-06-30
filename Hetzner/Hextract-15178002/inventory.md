# Hextract project (15178002) — server inventory

Hetzner Cloud project **Hextract** (`15178002`). Hosts the CodingInABox platform, isolated
from the Moodle Default project. Refreshed from the API on **2026-06-30**.

| Server | ID | Linked domain | IPv4 | Type | Location | Created | Status | Del-protect |
|---|---|---|---|---|---|---|---|---|
| codinginabox | 146601361 | www.codinginabox.com | 167.233.141.24 | CX23 | fsn1-dc14 (Falkenstein, DE) | 2026-06-30 | running | yes |

## Per-server folder
- `servers/www.codinginabox.com/` — nginx config; DNS mapping + deploy pending.

## Status
The server is **created and running** (this was previously blocked by the account-wide 5/5
server limit; the limit increase has cleared). Next: point `www.codinginabox.com` DNS at
`167.233.141.24`, issue SSL, and deploy.

Refresh: `curl -s -H "Authorization: Bearer $HCLOUD_TOKEN_HEXTRACT" https://api.hetzner.cloud/v1/servers`
