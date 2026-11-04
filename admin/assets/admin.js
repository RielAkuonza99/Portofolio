(function () {
  var qr = document.getElementById('qr');
  if (qr && window.qrcode) {
    var q = qrcode(0, 'M'); q.addData(qr.getAttribute('data-uri')); q.make();
    qr.innerHTML = q.createSvgTag({ cellSize: 5, margin: 2, scalable: true });
  }
  document.querySelectorAll('.password-toggle').forEach(function (toggle) {
    toggle.addEventListener('click', function () {
      var input = toggle.parentElement.querySelector('input');
      var show = input.type === 'password'; input.type = show ? 'text' : 'password';
      toggle.textContent = show ? 'Sembunyikan' : 'Tampilkan'; toggle.setAttribute('aria-label', toggle.textContent + ' password');
    });
  });
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
      var editor = button.closest('label').querySelector('.rich-editor');
      if (!editor) return;
      editor.focus();
      var command = button.getAttribute('data-command');
      var selection = window.getSelection();
      if (!selection || selection.rangeCount === 0) return;
      var range = selection.getRangeAt(0);
      if (command === 'link') {
        var url = window.prompt('Tautan https:// atau mailto:');
        if (!url || !/^https?:\/\//i.test(url) && !/^mailto:/i.test(url)) return;
        var link = document.createElement('a'); link.href = url; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.textContent = range.toString() || url;
        range.deleteContents(); range.insertNode(link); selection.collapseToEnd(); return;
      }
      var tagName = command === 'bold' ? 'strong' : command === 'italic' ? 'em' : command === 'heading' ? 'h3' : command === 'insertUnorderedList' ? 'ul' : '';
      if (!tagName) return;
      var wrapper = document.createElement(tagName);
      if (tagName === 'ul') { var li = document.createElement('li'); li.textContent = range.toString() || 'Item daftar'; wrapper.appendChild(li); range.deleteContents(); range.insertNode(wrapper); }
      else { wrapper.textContent = range.toString() || (tagName === 'h3' ? 'Judul' : 'Teks'); range.deleteContents(); range.insertNode(wrapper); }
      selection.collapseToEnd();
      editor.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });
  var imageInput = document.querySelector('input[name="image_file"]'); var imagePreview = document.getElementById('project-image-preview'); var imageCrop = document.getElementById('project-image-crop');
  if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function () { var file=imageInput.files && imageInput.files[0]; if(!file)return; imagePreview.src=URL.createObjectURL(file); imagePreview.hidden=false; });
    if(imageCrop) imageCrop.addEventListener('change', function(){var pos=imageCrop.value==='top'?'center top':imageCrop.value==='bottom'?'center bottom':'center';imagePreview.style.objectPosition=pos;});
  }
  var videoInput=document.querySelector('input[name="video_file"]'); var videoPreview=document.getElementById('project-video-preview'); var videoInfo=document.getElementById('project-video-info');
  if(videoInput&&videoPreview){videoInput.addEventListener('change',function(){var file=videoInput.files&&videoInput.files[0];if(!file)return;videoPreview.src=URL.createObjectURL(file);videoPreview.hidden=false;videoPreview.load();videoPreview.addEventListener('loadedmetadata',function(){var seconds=Math.max(0,Math.round(videoPreview.duration));if(videoInfo)videoInfo.textContent='Durasi: '+seconds+' detik';var end=document.querySelector('input[name="video_end"]');if(end&&!Number(end.value))end.value=String(seconds);},{once:true});});}
  document.querySelectorAll('.rich-editor').forEach(function (editor) {
    var hidden = editor.parentElement.querySelector('textarea[name="body_html"]');
    var sync = function () { if (hidden) hidden.value = editor.innerHTML; };
    editor.addEventListener('input', sync); sync();
  });
})();
