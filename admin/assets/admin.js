(function () {
  var qr = document.getElementById('qr');
  if (qr && window.qrcode) {
    var q = qrcode(0, 'M'); q.addData(qr.getAttribute('data-uri')); q.make();
    qr.innerHTML = q.createSvgTag({ cellSize: 5, margin: 2, scalable: true });
  }
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.dataset.submitting === 'true') { e.preventDefault(); return; }
    form.querySelectorAll('input[required], textarea[required]').forEach(function (field) {
      var isText = field.tagName === 'TEXTAREA' || ['text', 'email', 'url', 'search', 'tel'].indexOf(field.type) !== -1;
      field.setCustomValidity(isText && !field.value.trim() ? 'Kolom ini wajib diisi.' : '');
    });
    if (!form.checkValidity()) {
      e.preventDefault();
      form.reportValidity();
      return;
    }

    var submitter = e.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
    var confirmMessage = (submitter && submitter.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
    if (confirmMessage && !window.confirm(confirmMessage)) { e.preventDefault(); return; }

    form.dataset.submitting = 'true';
    form.setAttribute('aria-busy', 'true');
    if (submitter) {
      if (submitter.name) {
        var value = document.createElement('input');
        value.type = 'hidden';
        value.name = submitter.name;
        value.value = submitter.value;
        form.appendChild(value);
      }
      submitter.classList.add('loading');
      submitter.disabled = true;
      submitter.setAttribute('aria-label', submitter.getAttribute('data-loading-label') || 'Memproses...');
    }
  });
  document.addEventListener('change', function (e) {
    var t = e.target;
    if (t.matches && t.matches('[data-autosubmit]') && t.form) t.form.requestSubmit();
  });
  document.querySelectorAll('.rich-toolbar').forEach(function (toolbar) {
    toolbar.addEventListener('mousedown', function (e) { e.preventDefault(); });
    toolbar.addEventListener('click', function (e) {
      var button = e.target.closest('[data-command]'); if (!button) return;
      document.execCommand(button.getAttribute('data-command'), false, null);
    });
  });
  document.querySelectorAll('.rich-editor').forEach(function (editor) {
    var hidden = editor.parentElement.querySelector('textarea[name="body_html"]');
    var sync = function () { if (hidden) hidden.value = editor.innerHTML; };
    editor.addEventListener('input', sync); sync();
  });
})();
