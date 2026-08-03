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
  // Find the "Gerenciar" link near 179.197.69.198
  const result = await send('Runtime.evaluate', {
    expression: `(() => {
      const all = Array.from(document.querySelectorAll('a, button'));
      const items = all.filter(el => el.textContent.includes('Gerenciar'));
      const results = items.map(el => ({
        text: el.textContent.trim().substring(0, 50),
        href: el.href || '',
        outerHTML: el.outerHTML.substring(0, 200)
      }));
      return JSON.stringify(results);
    })()`,
    returnByValue: true,
  });
  console.log('Gerenciar links:', result.result?.value || result.result?.result?.value);
  
  // Also get all links near the IP
  const result2 = await send('Runtime.evaluate', {
    expression: `(() => {
      const all = Array.from(document.querySelectorAll('a'));
      return JSON.stringify(all.map(a => ({text: a.textContent.trim().substring(0,40), href: a.href})).filter(l => l.href.includes('vps') || l.href.includes('manage') || l.href.includes('srv')));
    })()`,
    returnByValue: true,
  });
  console.log('VPS links:', result2.result?.value || result2.result?.result?.value);
  
  ws.close();
});
