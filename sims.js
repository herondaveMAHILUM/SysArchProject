var _toastTimer = null;

function simsToast(msg, ok) {
  var box   = document.getElementById('simsToast');
  var msgEl = document.getElementById('simsToastMsg');
  if (!box || !msgEl) { alert((ok ? '✓ ' : '✗ ') + msg); return; }
  box.style.background = ok ? '#16a34a' : '#dc2626';
  msgEl.textContent = msg;
  box.style.opacity   = '1';
  box.style.transform = 'translateY(0)';
  box.style.pointerEvents = 'auto';
  if (_toastTimer) clearTimeout(_toastTimer);
  _toastTimer = setTimeout(function() { simsToastHide(); }, 4000);
}

function simsToastHide() {
  var box = document.getElementById('simsToast');
  if (!box) return;
  box.style.opacity   = '0';
  box.style.transform = 'translateY(-12px)';
  box.style.pointerEvents = 'none';
}

function simsPost(url, data) {
  var fd;
  if (data instanceof FormData) {
    fd = data;
  } else {
    fd = new FormData();
    Object.keys(data).forEach(function(k) { fd.append(k, data[k]); });
  }
  return fetch(url, { method: 'POST', body: fd })
    .then(function(res) { return res.text(); })
    .then(function(text) {
      try { return JSON.parse(text); }
      catch(e) { throw new Error('Server error: ' + text.substring(0, 200)); }
    });
}
