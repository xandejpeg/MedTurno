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
  // Navigate to SSH keys page
  await send('Page.navigate', { url: 'https://hpanel.hostinger.com/vps/1823122/ssh-keys' });
  await new Promise(r => setTimeout(r, 4000));
  
  // Get page content
  const result = await send('Runtime.evaluate', {
    expression: 'document.body.innerText.substring(0, 3000)',
    returnByValue: true,
  });
  console.log('Page content:', result.result?.result?.value);
  
  // Get all buttons and links
  const result2 = await send('Runtime.evaluate', {
    expression: `(() => {
      const els = Array.from(document.querySelectorAll('a, button'));
      return JSON.stringify(els.map(e => ({tag: e.tagName, text: e.textContent.trim().substring(0, 60), href: e.href || ''})).filter(e => e.text.length > 0));
    })()`,
    returnByValue: true,
  });
  console.log('Elements:', result2.result?.result?.value);
  
  ws.close();
});
