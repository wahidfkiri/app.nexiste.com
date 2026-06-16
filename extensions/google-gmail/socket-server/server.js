const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../../../.env') });

const express = require('express');
const http = require('http');
const cors = require('cors');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

const PORT = Number(process.env.PORT || 6004);
const CORS_ORIGIN = process.env.CORS_ORIGIN || '*';
const EMIT_TOKEN = process.env.GOOGLE_GMAIL_SOCKET_IO_SERVER_TOKEN || '';

app.use(cors({ origin: CORS_ORIGIN }));
app.use(express.json({ limit: '1mb' }));

const io = new Server(server, {
  cors: { origin: CORS_ORIGIN },
  path: process.env.SOCKET_IO_PATH || '/socket.io',
});

io.on('connection', (socket) => {
  socket.on('subscribe', (payload = {}) => {
    const tenantId = Number(payload.tenant_id || 0);
    if (!tenantId) return;

    socket.join(`tenant:${tenantId}`);
    socket.join(`tenant:${tenantId}:google-gmail`);
  });
});

app.get('/health', (_req, res) => {
  res.json({ ok: true, service: 'google-gmail-socket-server' });
});

app.post('/emit', (req, res) => {
  if (EMIT_TOKEN) {
    const auth = req.headers.authorization || '';
    const token = auth.startsWith('Bearer ') ? auth.slice(7).trim() : '';
    if (!token || token !== EMIT_TOKEN) {
      return res.status(401).json({ ok: false, message: 'Unauthorized' });
    }
  }

  const tenantId = Number(req.body.tenant_id || 0);
  const event = String(req.body.event || '').trim();
  const payload = req.body.payload || {};

  if (!tenantId || !event) {
    return res.status(422).json({ ok: false, message: 'tenant_id and event are required.' });
  }

  const packet = {
    tenant_id: tenantId,
    event,
    payload,
    module: 'google-gmail',
    emitted_at: new Date().toISOString(),
  };

  io.to(`tenant:${tenantId}`).emit('google-gmail.event', packet);
  io.to(`tenant:${tenantId}:google-gmail`).emit(`tenant:${tenantId}:google-gmail`, packet);

  return res.json({ ok: true });
});

server.listen(PORT, () => {
  // eslint-disable-next-line no-console
  console.log(`[Google Gmail Socket Server] listening on http://127.0.0.1:${PORT}`);
});
