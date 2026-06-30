# Default project (14010860) — costing

Prices in USD (VAT rate 0%, so gross = net). Live list prices pulled from the Hetzner
pricing API on **2026-06-30**.

## Monthly cost per server

| Server | Type | Location | Monthly | Basis |
|---|---|---|---|---|
| emcs-gr1-8 | CPX21 | hil | ~$15.53 | grandfathered legacy rate (per Jun 3 invoice) |
| ProductionServerGr | CPX21 | hil | ~$15.53 | grandfathered legacy rate |
| Canada-E-Academy | CPX21 | ash | $37.49 | current list (created after 2026-06-15 re-pricing) |
| Agincourt-International-Academy | CPX21 | ash | $37.49 | current list |
| Canadian-Virtual-School | CPX21 | ash | $37.49 | current list |
| **Project total** | | | **≈ $143.52 / mo** | |

## CPX21 reference list prices (2026-06-30)
| Location | Monthly |
|---|---|
| fsn1 / nbg1 / hel1 (EU) | $10.99 |
| sin (Singapore) | $21.99 |
| ash (Ashburn) / hil (Hillsboro) (US) | $37.49 |

## Invoices / billing
- **Jun 3 2026: $31.05 — status to confirm (was pending).** Covers the 2 Hillsboro servers.
- May 3 2026: $21.85 — settled.
- The 3 Ashburn servers (added Jun 26–30) bill at $37.49 each going forward — expect the next
  invoice to rise to roughly the project total above.

## Risk
The 2 Hillsboro servers are billed at the pre-2026-06-15 rate. If Hetzner re-prices them to
the current $37.49 list, this project's run-rate rises to **$187.45/mo** (+$43.92).

Refresh prices: `curl -s -H "Authorization: Bearer $HCLOUD_TOKEN" https://api.hetzner.cloud/v1/pricing`
