(function () {
  'use strict';

  var mq = window.matchMedia('(max-width: 992px)');
  var sidebar = document.getElementById('dashboardSidebar');
  var overlay = document.getElementById('dashboardSidebarOverlay');
  var toggle = document.getElementById('sidebarToggle');
  var closeBtn = document.getElementById('sidebarClose');

  function isMobile() {
    return mq.matches;
  }

  function setSidebarOpen(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('is-open', open);
    if (overlay) {
      overlay.classList.toggle('is-visible', open);
      overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
    }
    if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }

  function openSidebar() {
    if (isMobile()) setSidebarOpen(true);
  }

  function closeSidebar() {
    setSidebarOpen(false);
  }

  if (toggle) {
    toggle.addEventListener('click', function () {
      if (!sidebar) return;
      if (isMobile()) {
        var open = !sidebar.classList.contains('is-open');
        setSidebarOpen(open);
      }
    });
  }

  if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
  if (overlay) overlay.addEventListener('click', closeSidebar);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeSidebar();
      document.querySelectorAll('[data-dropdown-toggle]').forEach(function (btn) {
        var id = btn.getAttribute('data-dropdown-toggle');
        var menu = id ? document.getElementById(id) : null;
        if (menu) menu.style.display = 'none';
      });
    }
  });

  if (mq.addEventListener) {
    mq.addEventListener('change', function () {
      if (!isMobile()) closeSidebar();
    });
  } else if (mq.addListener) {
    mq.addListener(function () {
      if (!isMobile()) closeSidebar();
    });
  }

  /* Copy bank / account helpers */
  document.querySelectorAll('[data-copy-account]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var sel = btn.getAttribute('data-copy-account');
      var el = sel ? document.querySelector(sel) : null;
      var text = el ? (el.textContent || '').trim() : '';
      if (!text) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          btn.setAttribute('data-copied', '1');
          setTimeout(function () { btn.removeAttribute('data-copied'); }, 1600);
        });
      }
    });
  });

  /* Image preview for file inputs */
  document.querySelectorAll('input[type="file"][data-preview-target]').forEach(function (input) {
    input.addEventListener('change', function () {
      var id = input.getAttribute('data-preview-target');
      var img = id ? document.getElementById(id) : null;
      if (!img || !input.files || !input.files[0]) return;
      var url = URL.createObjectURL(input.files[0]);
      img.src = url;
      img.hidden = false;
    });
  });
})();
