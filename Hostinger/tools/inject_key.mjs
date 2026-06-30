import { Client } from 'ssh2';
const HOST = process.env.SRC_HOST;
const PW = process.env.SRC_PW;
const PUBKEY = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIN088A14uAJW1M4JYtEjwazWjyB3k1Zmm3P3S6FEFo0n hetzner-moodle';
const cmd = `mkdir -p /root/.ssh && chmod 700 /root/.ssh && touch /root/.ssh/authorized_keys && chmod 600 /root/.ssh/authorized_keys && grep -qF 'AAAAC3NzaC1lZDI1NTE5AAAAIN088A14uAJW1M4JYtEjwazWjyB3k1Zmm3P3S6FEFo0n' /root/.ssh/authorized_keys && echo 'KEY_ALREADY_PRESENT' || (echo '${PUBKEY}' >> /root/.ssh/authorized_keys && echo 'KEY_INSTALLED'); echo "HOST=$(hostname)"; echo "AUTHKEYS_COUNT=$(wc -l < /root/.ssh/authorized_keys)"; echo "-- containers --"; docker ps --format '{{.Names}}: {{.Status}}' 2>/dev/null || echo "docker n/a"`;

const conn = new Client();
conn.on('keyboard-interactive', (name, instr, lang, prompts, finish) => {
  finish(prompts.map(() => PW));
});
conn.on('ready', () => {
  conn.exec(cmd, (err, stream) => {
    if (err) { console.error('EXEC ERR', err.message); conn.end(); process.exitCode=1; return; }
    let out = '';
    stream.on('close', () => { console.log(out.trim()); conn.end(); })
      .on('data', d => out += d).stderr.on('data', d => out += '[stderr] '+d);
  });
}).on('error', e => { console.error('CONN ERR', e.message); process.exitCode=1; })
  .connect({ host: HOST, port: 22, username: 'root', password: PW, tryKeyboard: true, readyTimeout: 20000 });
