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
  // Navigate to VPS overview
  await send('Page.navigate', { url: 'https://hpanel.hostinger.com/vps/1823122/overview' });
  await new Promise(r => setTimeout(r, 4000));
  
  // Get all links and buttons with SSH or terminal
  const result = await send('Runtime.evaluate', {
    expression: `(() => {
      const els = Array.from(document.querySelectorAll('a, button, [role="button"], [role="tab"]'));
      return JSON.stringify(els.map(e => ({
        tag: e.tagName,
        text: e.textContent.trim().substring(0, 80),
        href: e.href || '',
        dataQa: e.getAttribute('data-qa') || ''
      })).filter(e => e.text.length > 0));
    })()`,
    returnByValue: true,
  });
  console.log('All elements:', result.result?.result?.value);
  
  // Also get sidebar/nav
  const result2 = await send('Runtime.evaluate', {
    expression: `(() => {
      const nav = document.querySelector('nav, aside, [class*="sidebar"], [class*="menu"]');
      return nav ? nav.innerText.substring(0, 2000) : 'no nav found';
    })()`,
    returnByValue: true,
  });
  console.log('Nav:', result2.result?.result?.value);
  
  ws.close();
});
