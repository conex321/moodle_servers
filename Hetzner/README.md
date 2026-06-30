# Hetzner Cloud — fleet index

Single source of truth for everything we run on Hetzner. Refreshed live from the Hetzner
Cloud API on **2026-06-30**. Tokens live in the repo-root `.env` (`HCLOUD_TOKEN` read-only,
`HCLOUD_TOKEN_WRITE` / `HCLOUD_TOKEN_HEXTRACT` read-write) — never commit them.

Master console: project owner Matthew Rubio, 2FA (email OTP) enabled. See `.env` for login.

## Projects

| Project | ID | Purpose | Servers |
|---|---|---|---|
| Default | `14010860` | Moodle fleet (Canada EMCS + sister schools) | 5 × CPX21 |
| Hextract | `15178002` | CodingInABox platform | 1 × CX23 |

## All servers (live, 2026-06-30)

| Project | Server | Linked domain | IPv4 | Type | Location | Created | Status | Del-protect |
|---|---|---|---|---|---|---|---|---|
| Default | emcs-gr1-8-ubuntu-4gb-hil-1 | **app.canadaemcs.com** (live, SSL) | 5.78.190.143 | CPX21 | hil-dc1 Hillsboro OR | 2026-04-19 | running | yes |
| Default | ProductionServerGr-1-8-4gb-hil-3 | _IP-only (source/standby, `Do_not_edit`)_ | 5.78.128.44 | CPX21 | hil-dc1 Hillsboro OR | 2026-04-01 | running | yes |
| Default | Canada-E-Academy | **canadaeacademy.com** (registered, DNS pending) | 5.161.222.147 | CPX21 | ash-dc1 Ashburn VA | 2026-06-26 | running | no |
| Default | Agincourt-International-Academy | _no domain registered yet — confirm_ | 178.156.152.192 | CPX21 | ash-dc1 Ashburn VA | 2026-06-29 | running | no |
| Default | Canadian-Virtual-School | **canadavirtualschool.com** (registered, DNS pending) | 87.99.158.52 | CPX21 | ash-dc1 Ashburn VA | 2026-06-30 | running | no |
| Hextract | codinginabox | **www.codinginabox.com** (DNS/deploy pending) | 167.233.141.24 | CX23 | fsn1-dc14 Falkenstein DE | 2026-06-30 | running | yes |

> **Change since last notes:** the CodingInABox server is now **created and running** (cx23,
> Falkenstein) — it was previously logged as blocked on the 5/5 server limit. The limit-increase
> evidently went through.

## Monthly run-rate (current Hetzner list prices, USD, VAT 0%)

| Server | Rate | Note |
|---|---|---|
| emcs-gr1-8 (hil) | ~$15.53 | grandfathered legacy CPX21 (per Jun 3 invoice) |
| ProductionServerGr (hil) | ~$15.53 | grandfathered legacy CPX21 |
| Canada-E-Academy (ash) | $37.49 | CPX21 @ Ashburn, current rate |
| Agincourt (ash) | $37.49 | CPX21 @ Ashburn, current rate |
| Canadian-Virtual-School (ash) | $37.49 | CPX21 @ Ashburn, current rate |
| codinginabox (fsn1) | $6.49 | CX23 @ Falkenstein |
| **Total** | **≈ $150.01 / mo** | see each project's `costing.md` for detail |

Reference list prices pulled 2026-06-30: CPX21 = $37.49 (ash/hil), $10.99 (fsn1/nbg1/hel1),
$21.99 (sin). CX23 = $6.49 (fsn1). The 2 Hillsboro servers predate the 2026-06-15 US
re-pricing and are billed at the older rate; if Hetzner re-prices them to list, the fleet
jumps to ~$193.94/mo.

## SSH keys
See [`ssh_keys/KEYS.md`](ssh_keys/KEYS.md) for names, fingerprints, and which servers use each key.

## Open items
- Map DNS + issue SSL for Canada-E-Academy (`canadaeacademy.com`) and Canadian-Virtual-School (`canadavirtualschool.com`).
- **Confirm the domain for Agincourt-International-Academy** — no `agincourt*` domain is registered in Hostinger.
- Jun 3 2026 invoice ($31.05) status — confirm paid in the Hetzner console.
