import http from 'http';

export function startMockSseServer(port = 8089) {
  const sessionsDb = new Map();

  const server = http.createServer((req, res) => {
    // CORS headers
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-CSRF-TOKEN, Accept');

    if (req.method === 'OPTIONS') {
      res.writeHead(204);
      res.end();
      return;
    }

    const url = new URL(req.url, `http://localhost:${port}`);

    // GET /chat/sessions - Lista de sesiones simuladas
    if (req.method === 'GET' && url.pathname === '/chat/sessions') {
      const list = Array.from(sessionsDb.values()).map(s => ({
        id: s.id,
        title: s.title,
        message_count: s.messages.length
      }));
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ success: true, data: list }));
      return;
    }

    // GET /chat/sessions/:id - Detalle de sesión
    if (req.method === 'GET' && url.pathname.startsWith('/chat/sessions/')) {
      const sessionId = decodeURIComponent(url.pathname.replace('/chat/sessions/', ''));
      const sess = sessionsDb.get(sessionId) || {
        id: sessionId,
        title: 'Sesión Simulada',
        messages: []
      };
      res.writeHead(200, { 'Content-Type': 'application/json' });
      res.end(JSON.stringify({ success: true, data: sess }));
      return;
    }

    // POST /chat/stream - Stream SSE
    if (req.method === 'POST' && url.pathname === '/chat/stream') {
      let bodyStr = '';
      req.on('data', chunk => { bodyStr += chunk; });
      req.on('end', async () => {
        let payload = {};
        try { payload = JSON.parse(bodyStr); } catch (e) {}

        const sessionId = payload.session_id || `sess_${Date.now()}`;
        const prompt = payload.prompt || '';
        const isRetry = Boolean(payload.retry_message_id);

        res.writeHead(200, {
          'Content-Type': 'text/event-stream',
          'Cache-Control': 'no-cache',
          'Connection': 'keep-alive',
          'X-Accel-Buffering': 'no'
        });

        // Simulación: Error intencional si el prompt contiene 'PROVOCAR_ERROR'
        if (prompt.includes('PROVOCAR_ERROR')) {
          res.write(`event: session\ndata: ${JSON.stringify({ status: 'processing', session_id: sessionId })}\n\n`);
          await new Promise(r => setTimeout(r, 300));
          res.write(`event: error\ndata: ${JSON.stringify({ detail: 'Error simulado de backend' })}\n\n`);
          res.end();
          return;
        }

        // 1. Emitir evento session
        res.write(`event: session\ndata: ${JSON.stringify({ status: 'processing', session_id: sessionId })}\n\n`);

        // Emitir un ping heartbeat
        await new Promise(r => setTimeout(r, 100));
        res.write(`: ping\n\n`);

        // Tokens simulados con delay de 350ms
        const tokens = ['Respuesta', 'simulada', 'de', 'DataMind', 'AI', 'para:', prompt.substring(0, 15)];
        for (const token of tokens) {
          await new Promise(r => setTimeout(r, 350));
          res.write(`event: token\ndata: ${JSON.stringify({ text: token + ' ' })}\n\n`);
        }

        // Evento done
        await new Promise(r => setTimeout(r, 200));
        res.write(`event: done\ndata: ${JSON.stringify({ session_id: sessionId, latency_ms: 1250 })}\n\n`);

        // Guardar en la BD en memoria del mock
        if (!sessionsDb.has(sessionId)) {
          sessionsDb.set(sessionId, {
            id: sessionId,
            title: prompt.substring(0, 30),
            messages: []
          });
        }
        const current = sessionsDb.get(sessionId);
        if (!isRetry) {
          current.messages.push({ id: `msg_u_${Date.now()}`, role: 'user', content: prompt });
        }
        current.messages.push({
          id: `msg_a_${Date.now()}`,
          role: 'assistant',
          content: tokens.join(' '),
          latency_ms: 1250
        });

        res.end();
      });
      return;
    }

    res.writeHead(404);
    res.end();
  });

  return new Promise((resolve) => {
    server.listen(port, () => {
      resolve({
        server,
        port,
        close: () => new Promise(r => server.close(r))
      });
    });
  });
}
