import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';
import fs from 'node:fs';

const OUT = 'E:\\Claude\\Moodle_servers\\Hostinger\\domains';
const entry = 'E:\\Claude\\Moodle_servers\\Hostinger\\tools\\node_modules\\hostinger-api-mcp\\src\\servers\\all.js';
const transport = new StdioClientTransport({ command: process.execPath, args: [entry], env: { ...process.env, DEBUG: 'false' } });
const client = new Client({ name: 'gather', version: '1.0.0' }, { capabilities: {} });
await client.connect(transport);

async function call(name, args = {}) {
  try {
    const res = await client.callTool({ name, arguments: args });
    const text = (res.content || []).map(c => c.text || '').join('');
    let data; try { data = JSON.parse(text); } catch { data = text; }
    return { ok: !res.isError, data };
  } catch (e) { return { ok: false, data: e.message }; }
}
const asArray = d => Array.isArray(d) ? d : (Array.isArray(d?.data) ? d.data : []);
function csv(rows, headers) {
  const esc = v => {
    if (v === null || v === undefined) v = '';
    v = String(v);
    return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
  };
  return [headers.join(','), ...rows.map(r => headers.map(h => esc(r[h])).join(','))].join('\r\n') + '\r\n';
}

// 1) base lists
const domains = asArray((await call('domains_getDomainListV1')).data);
const websites = asArray((await call('hosting_listWebsitesV1')).data);
const wp = asArray((await call('hosting_listWordPressInstallationsV1')).data);
console.log(`domains=${domains.length} websites=${websites.length} wp=${wp.length}`);

const wpByDomain = new Map(wp.map(w => [String(w.domain).toLowerCase(), w]));
const siteByDomain = new Map(websites.map(w => [String(w.domain).toLowerCase(), w]));

// 2) hosting-panel subdomains: query every unique (username, main-domain) pair
const subRows = [];
const seenSub = new Set();
const pushSub = (row) => {
  const k = String(row.subdomain_fqdn).toLowerCase();
  if (seenSub.has(k)) return;
  seenSub.add(k); subRows.push(row);
};
// direct subdomain-type website rows are themselves hosted subdomains
for (const w of websites.filter(w => w.vhost_type === 'subdomain')) {
  pushSub({ parent_domain: w.parent_domain || '', subdomain_fqdn: w.domain,
    prefix: (w.parent_domain && w.domain.endsWith('.' + w.parent_domain)) ? w.domain.slice(0, -(w.parent_domain.length + 1)) : '',
    source: 'hosting', record_type: '', target_or_root: w.root_directory || '' });
}
// plus the subdomains endpoint for each unique account+main domain
const pairs = new Map();
for (const w of websites) pairs.set(`${w.username}|${w.parent_domain || w.domain}`, { username: w.username, domain: w.parent_domain || w.domain });
for (const p of pairs.values()) {
  const r = await call('hosting_listWebsiteSubdomainsV1', p);
  for (const s of asArray(r.data)) {
    pushSub({ parent_domain: s.parent_domain || p.domain, subdomain_fqdn: s.domain, prefix: s.subdomain,
      source: 'hosting', record_type: '', target_or_root: s.root_directory || '' });
  }
}
const hostingSubKeys = new Set(subRows.map(s => String(s.subdomain_fqdn).toLowerCase()));

// 3) DNS-derived subdomains for every domain (host records only, skip service/email records)
const SKIP = /(^|\.)_|^\*$|^@$|^_/; // skip _dkim/_dmarc/_domainkey, wildcard, apex
// boilerplate hosting/cPanel auto hostnames -> not meaningful "subdomains"
const INFRA = new Set(['www','mail','ftp','cpanel','webmail','webdisk','whm','autodiscover','autoconfig','autoconf','ns1','ns2','ns3','ns4','server','mta-sts','smtp','imap','pop','localhost']);
const dnsSubByDomain = new Map(); // domain -> Set of fqdn
for (const d of domains) {
  const dom = d.domain;
  const r = await call('DNS_getDNSRecordsV1', { domain: dom });
  if (!r.ok) continue;
  const recs = Array.isArray(r.data) ? r.data : [];
  for (const rec of recs) {
    if (!['A', 'AAAA', 'CNAME'].includes(rec.type)) continue;
    const name = String(rec.name || '');
    if (name === '@' || name === '' || SKIP.test(name) || name.includes('*')) continue;
    const firstLabel = name.replace(/\.$/, '').split('.')[0].toLowerCase();
    if (INFRA.has(firstLabel)) continue;
    const fqdn = name.endsWith('.') ? name.slice(0, -1) : `${name}.${dom}`;
    if (fqdn.toLowerCase() === dom.toLowerCase()) continue;
    if (hostingSubKeys.has(fqdn.toLowerCase())) continue; // already captured via hosting
    if (!dnsSubByDomain.has(dom)) dnsSubByDomain.set(dom, new Map());
    const m = dnsSubByDomain.get(dom);
    const target = (rec.records || []).map(x => x.content).join(' | ');
    if (!m.has(fqdn.toLowerCase())) m.set(fqdn.toLowerCase(), { fqdn, prefix: name.replace(/\.$/, ''), type: rec.type, target });
  }
}
for (const [dom, m] of dnsSubByDomain) {
  for (const s of m.values()) {
    subRows.push({ parent_domain: dom, subdomain_fqdn: s.fqdn, prefix: s.prefix, source: 'dns', record_type: s.type, target_or_root: s.target });
  }
}

// count subdomains per parent domain
const subCount = {};
for (const s of subRows) subCount[String(s.parent_domain).toLowerCase()] = (subCount[String(s.parent_domain).toLowerCase()] || 0) + 1;

// 4) domains.csv
const domainRows = domains.map(d => {
  const key = String(d.domain).toLowerCase();
  const site = siteByDomain.get(key);
  const wpi = wpByDomain.get(key);
  return {
    domain: d.domain,
    type: d.type,
    status: d.status,
    created_at: d.created_at,
    expires_at: d.expires_at || '',
    has_website: site ? 'yes' : 'no',
    website_type: site ? site.vhost_type : '',
    hosting_username: site ? site.username : (wpi ? wpi.username : ''),
    is_wordpress: wpi ? 'yes' : 'no',
    wp_site_title: wpi ? wpi.site_title : '',
    subdomain_count: subCount[key] || 0,
  };
});

// 5) websites.csv
const websiteRows = websites.map(w => {
  const wpi = wpByDomain.get(String(w.domain).toLowerCase());
  return {
    domain: w.domain,
    vhost_type: w.vhost_type,
    parent_domain: w.parent_domain || '',
    hosting_username: w.username,
    is_enabled: w.is_enabled,
    is_wordpress: wpi ? 'yes' : 'no',
    wp_site_title: wpi ? wpi.site_title : '',
    wp_url: wpi ? wpi.url : '',
    root_directory: w.root_directory || '',
    created_at: w.created_at,
  };
});

subRows.sort((a, b) => (a.parent_domain + a.subdomain_fqdn).localeCompare(b.parent_domain + b.subdomain_fqdn));
domainRows.sort((a, b) => a.domain.localeCompare(b.domain));
websiteRows.sort((a, b) => a.domain.localeCompare(b.domain));

fs.writeFileSync(`${OUT}\\hostinger_domains.csv`, csv(domainRows, ['domain','type','status','created_at','expires_at','has_website','website_type','hosting_username','is_wordpress','wp_site_title','subdomain_count']));
fs.writeFileSync(`${OUT}\\hostinger_subdomains.csv`, csv(subRows, ['parent_domain','subdomain_fqdn','prefix','source','record_type','target_or_root']));
fs.writeFileSync(`${OUT}\\hostinger_websites.csv`, csv(websiteRows, ['domain','vhost_type','parent_domain','hosting_username','is_enabled','is_wordpress','wp_site_title','wp_url','root_directory','created_at']));

console.log(`WROTE: domains=${domainRows.length} subdomains=${subRows.length} (hosting=${hostingSubKeys.size}) websites=${websiteRows.length}`);
console.log('domains with a website:', domainRows.filter(r => r.has_website === 'yes').length);
console.log('domains with WordPress:', domainRows.filter(r => r.is_wordpress === 'yes').length);
await client.close();
