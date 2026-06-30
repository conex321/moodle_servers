# Default project (14010860) — server inventory

Hetzner Cloud project **Default** (`14010860`). The Moodle fleet. Refreshed from the API
on **2026-06-30**.

| Server | ID | Linked domain | IPv4 | Type | Location | Created | Status | Del-protect | Labels |
|---|---|---|---|---|---|---|---|---|---|
| emcs-gr1-8-ubuntu-4gb-hil-1 | 127422698 | app.canadaemcs.com | 5.78.190.143 | CPX21 | hil-dc1 (Hillsboro, OR) | 2026-04-19 | running | yes | — |
| ProductionServerGr-1-8-4gb-hil-3 | 125507449 | _(IP-only)_ | 5.78.128.44 | CPX21 | hil-dc1 (Hillsboro, OR) | 2026-04-01 | running | yes | `Do_not_edit` |
| Canada-E-Academy | 145396564 | canadaeacademy.com | 5.161.222.147 | CPX21 | ash-dc1 (Ashburn, VA) | 2026-06-26 | running | no | — |
| Agincourt-International-Academy | 146263759 | _(confirm)_ | 178.156.152.192 | CPX21 | ash-dc1 (Ashburn, VA) | 2026-06-29 | running | no | — |
| Canadian-Virtual-School | 146582071 | canadavirtualschool.com | 87.99.158.52 | CPX21 | ash-dc1 (Ashburn, VA) | 2026-06-30 | running | no | — |

## Per-server folders
- `servers/app.canadaemcs.com/` — live production; full Docker build context + DEPLOYMENT.md.
- `servers/canadaeacademy.com/` — Canada-E-Academy; DNS/SSL pending.
- `servers/agincourt-international-academy/` — domain unconfirmed.
- `servers/canadavirtualschool.com/` — Canadian-Virtual-School; DNS/SSL pending.
- `servers/source-snapshot-5.78.128.44/` — ProductionServerGr standby / snapshot source.

See `costing.md` for monthly cost and `BUILD_GUIDE.md` for the image build.

Refresh: `curl -s -H "Authorization: Bearer $HCLOUD_TOKEN" https://api.hetzner.cloud/v1/servers`
