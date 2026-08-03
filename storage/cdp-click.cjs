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
  // Click the second "Gerenciar" button (VPS 179.197.69.198)
  const result = await send('Runtime.evaluate', {
    expression: `(() => {
      const buttons = Array.from(document.querySelectorAll('button[data-qa="vps-list-action-button"]'));
      // Find the one near 179.197.69.198
      const all = Array.from(document.querySelectorAll('button'));
      const gerenciarBtns = all.filter(b => b.textContent.trim() === 'Gerenciar');
      // The second one should be for 179.197.69.198
      if (gerenciarBtns.length >= 2) {
        gerenciarBtns[1].click();
        return 'clicked second Gerenciar';
      } else if (gerenciarBtns.length >= 1) {
        gerenciarBtns[0].click();
        return 'clicked first Gerenciar';
      }
      return 'no Gerenciar button found';
    })()`,
    returnByValue: true,
  });
  console.log('Click result:', result.result?.result?.value);
  
  // Wait for navigation
  await new Promise(r => setTimeout(r, 3000));
  
  // Get current URL
  const urlResult = await send('Runtime.evaluate', {
    expression: 'window.location.href',
    returnByValue: true,
  });
  console.log('Current URL:', urlResult.result?.result?.value);
  
  ws.close();
});
