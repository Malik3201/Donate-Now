(function () {
  'use strict';

  function passwordTier(value) {
    var p = value || '';
    var len = p.length;
    var classes = 0;
    if (/[a-z]/.test(p)) classes++;
    if (/[A-Z]/.test(p)) classes++;
    if (/[0-9]/.test(p)) classes++;
    if (/[^A-Za-z0-9]/.test(p)) classes++;
    if (len < 8) return 'weak';
    if (classes >= 3) return 'strong';
    if (classes >= 2) return 'fair';
    return 'weak';
  }

  function updatePasswordMeter(input, meter) {
    if (!meter || !input) return;
    var v = input.value;
    if (!v) {
      meter.hidden = true;
      meter.removeAttribute('data-tier');
      return;
    }
    meter.hidden = false;
    var tier = passwordTier(v);
    meter.setAttribute('data-tier', tier);
    var label = meter.querySelector('.password-meter-label');
    if (label) {
      if (tier === 'weak') label.textContent = 'Weak password';
      else if (tier === 'fair') label.textContent = 'Fair — add another character type';
      else label.textContent = 'Strong password';
    }
  }

  document.querySelectorAll('[data-auth-pw-toggle]').forEach(function (btn) {
    var id = btn.getAttribute('data-auth-pw-toggle');
    var input = id ? document.getElementById(id) : null;
    if (!input) return;
    btn.addEventListener('click', function () {
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.classList.toggle('is-visible', show);
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });

  var strengthInput = document.querySelector('[data-auth-strength-field]');
  var meter = document.querySelector('[data-password-meter]');
  if (strengthInput && meter) {
    strengthInput.addEventListener('input', function () {
      updatePasswordMeter(strengthInput, meter);
    });
    strengthInput.addEventListener('focus', function () {
      if (strengthInput.value) updatePasswordMeter(strengthInput, meter);
    });
  }

  function currentRole() {
    var r = document.querySelector('input[name="role"]:checked');
    return r ? r.value : 'donor';
  }

  function syncRolePanels() {
    var role = currentRole();
    document.querySelectorAll('[data-role-panel]').forEach(function (wrap) {
      var key = wrap.getAttribute('data-role-panel');
      var open = key === role;
      wrap.classList.toggle('is-open', open);
    });
  }

  document.querySelectorAll('input[name="role"]').forEach(function (radio) {
    radio.addEventListener('change', syncRolePanels);
  });
  syncRolePanels();

  document.querySelectorAll('form[data-auth-register]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      var pwField = form.querySelector('[data-auth-strength-field]');
      if (pwField && passwordTier(pwField.value) === 'weak') {
        e.preventDefault();
        alert('Please choose a stronger password: at least 8 characters and two or more types (uppercase, lowercase, numbers, symbols). Three types make it strong.');
        pwField.focus();
        return;
      }
      var role = currentRole();
      document.querySelectorAll('[data-role-panel]').forEach(function (wrap) {
        var key = wrap.getAttribute('data-role-panel');
        var open = key === role;
        wrap.querySelectorAll('input,select,textarea').forEach(function (el) {
          el.disabled = !open;
        });
      });
    });
  });
})();
