# Hostinger Domains, Subdomains & Websites

Generated from the official Hostinger API via the `hostinger-api-mcp` server
(OAuth-authenticated). Pulled 2026-06-26.

## Files

| File | Rows | What it is |
|------|------|------------|
| `hostinger_domains.csv` | 59 | Every domain in the Hostinger account portfolio (registrations) |
| `hostinger_websites.csv` | 25 | Every hosted website (main / addon / subdomain vhosts) |
| `hostinger_subdomains.csv` | 32 | Subdomains, from hosting panel + DNS zone records |

## `hostinger_domains.csv` columns
- `domain`, `type`, `status` (active / pending_verification), `created_at`, `expires_at`
- `has_website` — yes if the domain appears as a website vhost in the hosting API
- `website_type` — main / addon / subdomain
- `hosting_username` — the Hostinger hosting account that holds it
- `is_wordpress` — yes if a WordPress install exists on it
- `wp_site_title`
- `subdomain_count` — number of subdomains found for this domain

## `hostinger_websites.csv` columns
- `domain`, `vhost_type` (main/addon/subdomain), `parent_domain`, `hosting_username`,
  `is_enabled`, `is_wordpress`, `wp_site_title`, `wp_url`, `root_directory`, `created_at`

## `hostinger_subdomains.csv` columns
- `parent_domain`, `subdomain_fqdn`, `prefix`
- `source` — `hosting` (a real subdomain website in the hosting panel) or `dns` (derived from a DNS A/AAAA/CNAME record)
- `record_type` — A / AAAA / CNAME (DNS rows only)
- `target_or_root` — the hosting directory (hosting rows) or DNS target (dns rows)

## Notes
- `subdomain_count` and `is_wordpress` are the clearest signal of activity. Example:
  `schoolconex.com` shows `has_website=no` but `is_wordpress=yes` with 17 subdomains —
  it is the central hub; its child sites (crm, sis, members, client, pricing, info, ads,
  email, etc.) are listed individually as `subdomain` vhosts.
- DNS-sourced subdomains exclude boilerplate hosting hostnames (www, mail, ftp, cpanel,
  webmail, ns1/2, etc.) and DKIM/DMARC/`_*` service records. Third-party verification
  subdomains (Mailgun, Stripe, Intercom, Google) are kept and are identifiable by their
  `target_or_root` value.
- 3 hosting accounts hold everything: `u881944871`, `u229151323`, `u293494719`.

## Regenerate
Run from the `hostinger/` folder (it has the MCP SDK in `node_modules`); it writes
the three CSVs back into this `domains/` folder via absolute paths:
```
cd ../hostinger
node refresh.mjs
```
Requires valid Hostinger OAuth credentials (`hostinger-api-mcp --login`).
