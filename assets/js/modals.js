(function () {
  function closeModal(modal) {
    if (modal) {
      modal.classList.remove('open');
    }
  }

  function openModal(modal) {
    if (!modal) {
      return;
    }
    /* Detach from table / overflow:hidden ancestors so overlay + image are full-viewport */
    if (modal.parentElement !== document.body) {
      document.body.appendChild(modal);
    }
    modal.classList.add('open');
  }

  document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-modal-open');
      openModal(document.getElementById(id));
    });
  });

  document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      closeModal(btn.closest('.modal'));
    });
  });

  document.querySelectorAll('.modal').forEach(function (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) {
        closeModal(modal);
      }
    });
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal.open').forEach(function (modal) {
        closeModal(modal);
      });
    }
  });
})();
