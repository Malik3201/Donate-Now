(function () {
  var root = document;
  var header = root.getElementById('siteHeader');
  var nav = root.getElementById('primaryNav');
  var navToggle = root.getElementById('mobileNavToggle');
  var backTop = root.getElementById('backToTop');
  var hero = root.querySelector('.hero');

  var onScroll = function () {
    var shouldSolid = window.scrollY > 28;
    if (header) {
      header.classList.toggle('is-scrolled', shouldSolid);
    }
    if (backTop) {
      backTop.style.display = window.scrollY > 500 ? 'inline-flex' : 'none';
    }
    if (hero) {
      var offset = Math.min(window.scrollY * 0.18, 90);
      hero.style.backgroundPositionY = offset + 'px';
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      var isOpen = nav.classList.toggle('is-open');
      nav.classList.toggle('open', isOpen);
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    root.addEventListener('click', function (event) {
      if (!nav.contains(event.target) && !navToggle.contains(event.target)) {
        nav.classList.remove('is-open');
        nav.classList.remove('open');
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  if (backTop) {
    backTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  root.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (event) {
      var id = anchor.getAttribute('href');
      if (!id || id === '#' || id.length < 2) {
        return;
      }
      var target = root.querySelector(id);
      if (!target) {
        return;
      }
      event.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      if (nav) {
        nav.classList.remove('is-open');
        nav.classList.remove('open');
      }
      if (navToggle) {
        navToggle.setAttribute('aria-expanded', 'false');
      }
    });
  });

  var revealItems = root.querySelectorAll('.reveal');
  var revealObserver = null;
  if ('IntersectionObserver' in window && revealItems.length) {
    revealObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }

        var target = entry.target;
        var parent = target.closest('.stagger-group');
        if (parent) {
          var children = parent.querySelectorAll('.reveal');
          children.forEach(function (child, index) {
            window.setTimeout(function () {
              child.classList.add('is-visible');
            }, index * 90);
          });
        } else {
          target.classList.add('is-visible');
        }

        observer.unobserve(target);
      });
    }, { threshold: 0.14, rootMargin: '0px 0px -40px 0px' });

    revealItems.forEach(function (item) {
      revealObserver.observe(item);
    });
  } else {
    revealItems.forEach(function (item) {
      item.classList.add('is-visible');
    });
  }

  var animateCounter = function (el) {
    var endValue = parseInt(el.getAttribute('data-counter') || '0', 10);
    if (Number.isNaN(endValue) || endValue < 0) {
      el.textContent = '0';
      return;
    }
    var duration = 1200;
    var startTime = null;

    var frame = function (time) {
      if (!startTime) {
        startTime = time;
      }
      var progress = Math.min((time - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      var current = Math.round(endValue * eased);
      el.textContent = current.toLocaleString();
      if (progress < 1) {
        window.requestAnimationFrame(frame);
      }
    };

    window.requestAnimationFrame(frame);
  };

  var counters = root.querySelectorAll('[data-counter]');
  if ('IntersectionObserver' in window && counters.length) {
    var counterObserver = new IntersectionObserver(function (entries, observer) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.4 });

    counters.forEach(function (counter) {
      counterObserver.observe(counter);
    });
  } else {
    counters.forEach(animateCounter);
  }

  root.querySelectorAll('[data-faq-trigger]').forEach(function (trigger) {
    trigger.addEventListener('click', function () {
      var panelId = trigger.getAttribute('data-faq-trigger');
      if (!panelId) {
        return;
      }
      var panel = root.getElementById(panelId);
      if (!panel) {
        return;
      }
      var expanded = trigger.getAttribute('aria-expanded') === 'true';
      trigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      panel.hidden = expanded;
    });
  });
})();
