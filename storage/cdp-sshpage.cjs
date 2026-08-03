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
  // Click on "Chaves SSH" in the sidebar
  const clickResult = await send('Runtime.evaluate', {
    expression: `(() => {
      const els = Array.from(document.querySelectorAll('a, button, [role="button"], [role="tab"]'));
      const sshEl = els.find(e => e.textContent.trim() === 'Chaves SSH');
      if (sshEl) {
        sshEl.click();
        return 'clicked Chaves SSH';
      }
      return 'not found';
    })()`,
    returnByValue: true,
  });
  console.log('Click:', clickResult.result?.result?.value);
  
  await new Promise(r => setTimeout(r, 4000));
  
  // Get current URL
  const urlResult = await send('Runtime.evaluate', {
    expression: 'window.location.href',
    returnByValue: true,
  });
  console.log('URL:', urlResult.result?.result?.value);
  
  // Get page content
  const contentResult = await send('Runtime.evaluate', {
    expression: 'document.body.innerText.substring(0, 3000)',
    returnByValue: true,
  });
  console.log('Content:', contentResult.result?.result?.value);
  
  // Get all buttons/inputs
  const elementsResult = await send('Runtime.evaluate', {
    expression: `(() => {
      const els = Array.from(document.querySelectorAll('button, input, textarea, [role="button"]'));
      return JSON.stringify(els.map(e => ({
        tag: e.tagName,
        type: e.type || '',
        text: e.textContent.trim().substring(0, 60),
        placeholder: e.placeholder || '',
        name: e.name || '',
        id: e.id || ''
      })));
    })()`,
    returnByValue: true,
  });
  console.log('Elements:', elementsResult.result?.result?.value);
  
  ws.close();
});
