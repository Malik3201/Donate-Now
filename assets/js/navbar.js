(function () {
  if (
    document.body &&
    (document.body.classList.contains('landing-body') || document.body.classList.contains('static-page'))
  ) {
    return;
  }

  var toggle = document.getElementById('mobileNavToggle');
  var nav = document.getElementById('primaryNav');

  function setNavOpen(isOpen) {
    if (typeof window.setNavOpen === 'function') {
      window.setNavOpen(isOpen);
      return;
    }
    if (nav) {
      nav.classList.toggle('open', isOpen);
      nav.classList.toggle('is-open', isOpen);
    }
    if (toggle) {
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (document.body) {
      document.body.classList.toggle('nav-open', isOpen);
      document.body.style.overflow = isOpen ? 'hidden' : '';
    }
  }

  if (toggle && nav) {
    toggle.addEventListener('click', function () {
      setNavOpen(!nav.classList.contains('open') && !nav.classList.contains('is-open'));
    });

    document.addEventListener('click', function (e) {
      if (document.body.classList.contains('nav-open') && !nav.contains(e.target) && !toggle.contains(e.target)) {
        setNavOpen(false);
      }
    });

    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        setNavOpen(false);
      });
    });
  }

  var header = document.querySelector('.site-header');
  if (header && !document.body.classList.contains('static-page')) {
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
