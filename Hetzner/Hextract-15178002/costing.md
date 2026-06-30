# Hextract project (15178002) — costing

Prices in USD (VAT rate 0%). Live list prices pulled from the Hetzner pricing API on
**2026-06-30**.

| Server | Type | Location | Monthly | Basis |
|---|---|---|---|---|
| codinginabox | CX23 | fsn1 (Falkenstein) | $6.49 | current list |
| **Project total** | | | **$6.49 / mo** | |

CX23 is the cheapest viable shared-vCPU tier (2 vCPU / 4 GB) and Falkenstein (EU) is the
lowest-cost region, so this platform runs at minimal cost.

Refresh prices: `curl -s -H "Authorization: Bearer $HCLOUD_TOKEN" https://api.hetzner.cloud/v1/pricing`
