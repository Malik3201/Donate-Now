(function () {
  var roleInput = document.querySelector('select[name="role"]');
  var ngoFields = document.getElementById('ngoFields');
  var volunteerFields = document.getElementById('volunteerFields');
  function updateRoleFields() {
    if (!roleInput) return;
    var role = roleInput.value;
    if (ngoFields) ngoFields.classList.toggle('hidden', role !== 'ngo');
    if (volunteerFields) volunteerFields.classList.toggle('hidden', role !== 'volunteer');
  }
  if (roleInput) { roleInput.addEventListener('change', updateRoleFields); updateRoleFields(); }

  document.querySelectorAll('[data-password-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.getElementById(btn.getAttribute('data-password-toggle'));
      if (!target) return;
      target.type = target.type === 'password' ? 'text' : 'password';
      btn.textContent = target.type === 'password' ? 'Show' : 'Hide';
    });
  });

  document.querySelectorAll('form[data-loading-button]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('button[type="submit"]');
      if (btn) { btn.disabled = true; btn.dataset.originalText = btn.textContent; btn.textContent = 'Please wait...'; }
    });
  });

  var attachment = document.getElementById('attachment');
  var attachmentLabel = document.getElementById('attachmentLabel');
  var attachmentPreview = document.getElementById('attachmentPreview');
  if (attachment) {
    attachment.addEventListener('change', function () {
      var f = attachment.files && attachment.files[0];
      if (!f) return;
      if (attachmentLabel) attachmentLabel.textContent = f.name;
      if (attachmentPreview) {
        if (f.type.indexOf('image/') === 0) {
          attachmentPreview.src = URL.createObjectURL(f);
          attachmentPreview.classList.remove('hidden');
        } else {
          attachmentPreview.classList.add('hidden');
        }
      }
    });
  }
})();
