const http = require('http');
const { URL } = require('url');

const PORT = process.env.PORT || 8054;
const SITE_TOKEN = process.env.SITE_TOKEN || 'dev-token';

let poolBalance = 10;
const receipts = [];

function send(res, code, payload) {
  res.writeHead(code, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify(payload));
}

function readBody(req) {
  return new Promise((resolve) => {
    let data = '';
    req.on('data', (chunk) => (data += chunk));
    req.on('end', () => {
      try {
        resolve(data ? JSON.parse(data) : {});
      } catch {
        resolve({});
      }
    });
  });
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://localhost:${PORT}`);
  const auth = req.headers['x-ddns-site-token'];

  if (url.pathname === '/healthz') {
    return send(res, 200, { ok: true });
  }

  if (url.pathname.startsWith('/comments') || url.pathname.startsWith('/node') || url.pathname.startsWith('/site-pool')) {
    if (auth !== SITE_TOKEN) {
      return send(res, 403, { error: 'unauthorized' });
    }
  }

  if (req.method === 'POST' && url.pathname === '/comments/auth/challenge') {
    const body = await readBody(req);
    return send(res, 200, { wallet: body.wallet, challenge: 'mock', expiresAt: Date.now() + 600000 });
  }

  if (req.method === 'POST' && url.pathname === '/comments/auth/verify') {
    return send(res, 200, { ok: true });
  }

  if (req.method === 'POST' && url.pathname === '/comments/hold') {
    return send(res, 200, { ticket_id: 'mock-ticket', expiresAt: Date.now() + 900000 });
  }

  if (req.method === 'POST' && url.pathname === '/comments/submit') {
    return send(res, 200, { ok: true });
  }

  if (req.method === 'POST' && url.pathname === '/comments/finalize') {
    return send(res, 200, { ok: true });
  }

  if (req.method === 'POST' && url.pathname === '/node/verify') {
    return send(res, 200, { verification_id: 'mock-verify' });
  }

  if (req.method === 'POST' && url.pathname === '/node/receipts') {
    receipts.unshift({ ts: Date.now(), type: 'node_receipt', payload: { ok: true } });
    poolBalance += 1;
    return send(res, 200, { ok: true, balance: poolBalance });
  }

  if (req.method === 'GET' && url.pathname === '/site-pool') {
    return send(res, 200, { balance: poolBalance });
  }

  if (req.method === 'GET' && url.pathname === '/site-pool/receipts') {
    return send(res, 200, { receipts });
  }

  return send(res, 404, { error: 'not_found' });
});

server.listen(PORT, () => {
  console.log(`mock coordinator listening on ${PORT}`);
});
