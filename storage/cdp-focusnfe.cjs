const WebSocket = require('ws');
const PAGE_ID = '605B0C1BBD43A2DD2740FF6B379AB7ED';
const ws = new WebSocket(`ws://localhost:9223/devtools/page/${PAGE_ID}`);

let msgId = 1;

function send(method, params = {}) {
  return new Promise((resolve) => {
    const id = msgId++;
    const handler = (data) => {
      const r = JSON.parse(data);
      if (r.id === id) {
        ws.removeListener('message', handler);
        resolve(r);
      }
    };
    ws.on('message', handler);
    ws.send(JSON.stringify({ id, method, params }));
  });
}

ws.on('open', async () => {
  // Open new tab with FocusNFe registration
  await send('Target.createTarget', { url: 'https://focusnfe.com.br/cadastro/' });
  console.log('Opened FocusNFe registration page in new tab');
  ws.close();
});
