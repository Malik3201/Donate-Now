(function () {
  if (document.body && document.body.classList.contains('landing-body')) {
    return;
  }

  var toggle = document.getElementById('mobileNavToggle');
  var nav = document.getElementById('primaryNav');
  if (toggle && nav) {
    toggle.addEventListener('click', function () { nav.classList.toggle('open'); });
  }

  var header = document.querySelector('.site-header');
  if (header) {
    window.addEventListener('scroll', function () {
      header.style.background = window.scrollY > 15 ? 'rgba(8,12,29,0.9)' : 'var(--bg-navbar)';
    });
  }

  document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var id = btn.getAttribute('data-dropdown-toggle');
      var menu = document.getElementById(id);
      if (!menu) return;
      var isOpen = menu.style.display === 'block';
      document.querySelectorAll('[id$="Menu"]').forEach(function (el) { el.style.display = 'none'; });
      menu.style.display = isOpen ? 'none' : 'block';
    });
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.action-dropdown')) {
      document.querySelectorAll('[id$="Menu"]').forEach(function (el) { el.style.display = 'none'; });
    }
  });
})();
