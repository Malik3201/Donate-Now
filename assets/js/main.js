(function () {
  document.querySelectorAll('[data-scroll-to]').forEach(function (el) {
    el.addEventListener('click', function () {
      var target = document.querySelector(el.getAttribute('data-scroll-to'));
      if (target) target.scrollIntoView({ behavior: 'smooth' });
    });
  });

  var counters = document.querySelectorAll('[data-counter]');
  var animateCounter = function (el) {
    var end = parseInt(el.getAttribute('data-counter'), 10) || 0;
    var current = 0;
    var step = Math.max(1, Math.ceil(end / 50));
    var intv = setInterval(function () {
      current += step;
      if (current >= end) { current = end; clearInterval(intv); }
      el.textContent = current.toLocaleString();
    }, 24);
  };
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) { if (entry.isIntersecting) { animateCounter(entry.target); observer.unobserve(entry.target); } });
  }, { threshold: 0.3 });
  counters.forEach(function (c) { observer.observe(c); });

  var backTop = document.getElementById('backToTop');
  if (backTop) {
    window.addEventListener('scroll', function () { backTop.style.display = window.scrollY > 400 ? 'inline-flex' : 'none'; });
    backTop.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
  }

  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var text = btn.getAttribute('data-copy') || '';
      navigator.clipboard.writeText(text);
      alert('Copied to clipboard');
    });
  });

  document.querySelectorAll('[data-faq-toggle]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var id = btn.getAttribute('data-faq-toggle');
      var panel = document.getElementById(id);
      if (panel) panel.classList.toggle('hidden');
    });
  });
})();
