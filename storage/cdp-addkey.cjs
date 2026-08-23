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
  // Click "Chave SSH" button to open add key form
  const clickResult = await send('Runtime.evaluate', {
    expression: `(() => {
      const els = Array.from(document.querySelectorAll('button'));
      const sshBtn = els.find(e => e.textContent.trim() === 'Chave SSH');
      if (sshBtn) {
        sshBtn.click();
        return 'clicked Chave SSH button';
      }
      return 'not found';
    })()`,
    returnByValue: true,
  });
  console.log('Click:', clickResult.result?.result?.value);
  
  await new Promise(r => setTimeout(r, 3000));
  
  // Get page content to see form
  const contentResult = await send('Runtime.evaluate', {
    expression: 'document.body.innerText.substring(0, 3000)',
    returnByValue: true,
  });
  console.log('Content:', contentResult.result?.result?.value);
  
  // Get all textareas and inputs (for the key field)
  const inputsResult = await send('Runtime.evaluate', {
    expression: `(() => {
      const els = Array.from(document.querySelectorAll('textarea, input[type="text"], input:not([type])'));
      return JSON.stringify(els.map(e => ({
        tag: e.tagName,
        type: e.type || '',
        placeholder: e.placeholder || '',
        name: e.name || '',
        id: e.id || '',
        className: e.className.substring(0, 80)
      })));
    })()`,
    returnByValue: true,
  });
  console.log('Inputs:', inputsResult.result?.result?.value);
  
  // Get all buttons (to find Add/Save button)
  const buttonsResult = await send('Runtime.evaluate', {
    expression: `(() => {
      const els = Array.from(document.querySelectorAll('button'));
      return JSON.stringify(els.map(e => e.textContent.trim().substring(0, 60)).filter(t => t.length > 0));
    })()`,
    returnByValue: true,
  });
  console.log('Buttons:', buttonsResult.result?.result?.value);
  
  ws.close();
});
