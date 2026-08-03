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

const SSH_KEY = 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIHRPSVhUDQ++fwQoqyRCij0yW9QVenuvmc1V1yefk8my doctorturn-deploy';

ws.on('open', async () => {
  // Fill the textarea with id="key"
  const fillResult = await send('Runtime.evaluate', {
    expression: `(() => {
      const ta = document.getElementById('key');
      if (!ta) return 'textarea not found';
      const nativeInputValueSetter = Object.getOwnPropertyDescriptor(window.HTMLTextAreaElement.prototype, 'value').set;
      nativeInputValueSetter.call(ta, ${JSON.stringify(SSH_KEY)});
      ta.dispatchEvent(new Event('input', { bubbles: true }));
      ta.dispatchEvent(new Event('change', { bubbles: true }));
      return 'filled: ' + ta.value.substring(0, 50) + '...';
    })()`,
    returnByValue: true,
  });
  console.log('Fill:', fillResult.result?.result?.value);
  
  await new Promise(r => setTimeout(r, 1000));
  
  // Click "Salvar" button
  const saveResult = await send('Runtime.evaluate', {
    expression: `(() => {
      const els = Array.from(document.querySelectorAll('button'));
      const saveBtn = els.find(e => e.textContent.trim() === 'Salvar');
      if (saveBtn) {
        saveBtn.click();
        return 'clicked Salvar';
      }
      return 'Salvar not found';
    })()`,
    returnByValue: true,
  });
  console.log('Save:', saveResult.result?.result?.value);
  
  await new Promise(r => setTimeout(r, 4000));
  
  // Check result - look for success/error message
  const contentResult = await send('Runtime.evaluate', {
    expression: 'document.body.innerText.substring(0, 3000)',
    returnByValue: true,
  });
  console.log('Content after save:', contentResult.result?.result?.value);
  
  // Check for any error/success notifications
  const notifResult = await send('Runtime.evaluate', {
    expression: `(() => {
      const notifs = document.querySelectorAll('[class*="toast"], [class*="notification"], [class*="alert"], [class*="message"], [role="alert"]');
      return Array.from(notifs).map(n => n.textContent.trim().substring(0, 200)).join(' | ');
    })()`,
    returnByValue: true,
  });
  console.log('Notifications:', notifResult.result?.result?.value);
  
  ws.close();
});
