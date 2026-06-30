import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';
const entry = 'E:\\Claude\\Moodle_servers\\Hostinger\\tools\\node_modules\\hostinger-api-mcp\\src\\servers\\all.js';
const transport = new StdioClientTransport({ command: process.execPath, args: [entry], env: { ...process.env, DEBUG: 'false' } });
const client = new Client({ name: 'vps', version: '1.0.0' }, { capabilities: {} });
await client.connect(transport);
const { tools } = await client.listTools();
const out = [];
// show the VPS list/read tools available
for (const t of tools) {
  if (/VPS_/i.test(t.name) && /(list|getVirtual|getVM|getDataCenters|getMetrics)/i.test(t.name)) {
    out.push(`TOOL ${t.name} req:[${(t.inputSchema?.required||[]).join(',')}] - ${(t.description||'').slice(0,70)}`);
  }
}
async function call(name, args = {}) {
  try { const r = await client.callTool({ name, arguments: args });
    const txt = (r.content||[]).map(c=>c.text||'').join('');
    let d; try { d = JSON.parse(txt); } catch { d = txt; }
    return { ok: !r.isError, d };
  } catch (e) { return { ok:false, d:e.message }; }
}
// try the most likely VM list tool names
for (const n of ['VPS_getVirtualMachineListV1','VPS_getVirtualMachinesV1','VPS_listVirtualMachinesV1']) {
  if (!tools.find(t=>t.name===n)) continue;
  const r = await call(n);
  out.push(`\n=== ${n} ok=${r.ok} ===`);
  out.push(JSON.stringify(r.d, null, 1).slice(0, 2500));
}
console.log('@@@\n' + out.join('\n') + '\n@@@');
await client.close();
