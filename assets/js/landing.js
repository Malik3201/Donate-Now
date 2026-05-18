(function () {
  var root = document;
  var header = root.getElementById('siteHeader');
  var nav = root.getElementById('primaryNav');
  var navToggle = root.getElementById('mobileNavToggle');
  var backTop = root.getElementById('backToTop');
  var hero = root.querySelector('.dn-hero');
  var heroFrame = root.querySelector('.dn-hero__frame');
  var isStaticPage = root.body && root.body.classList.contains('static-page');
  var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var lastScrollY = window.scrollY || 0;

  var onScroll = function () {
    var scrollY = window.scrollY || 0;
    var shouldSolid = scrollY > 24;

    if (header) {
      header.classList.toggle('is-scrolled', shouldSolid);
      header.classList.remove('is-nav-hidden');
    }

    if (backTop) {
      backTop.style.display = scrollY > 500 ? 'inline-flex' : 'none';
    }

    if (!prefersReducedMotion && heroFrame) {
      var offset = Math.min(scrollY * 0.06, 36);
      heroFrame.style.transform = 'rotate(2deg) translateY(' + offset + 'px)';
    }

    lastScrollY = scrollY;
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  function setNavOpen(isOpen) {
    if (nav) {
      nav.classList.toggle('is-open', isOpen);
      nav.classList.toggle('open', isOpen);
    }
    if (navToggle) {
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (root.body) {
      root.body.classList.toggle('nav-open', isOpen);
      root.body.style.overflow = isOpen ? 'hidden' : '';
    }
  }

  window.setNavOpen = setNavOpen;

  if (navToggle && nav) {
    navToggle.addEventListener('click', function () {
      setNavOpen(!nav.classList.contains('is-open'));
    });

    root.addEventListener('click', function (event) {
      if (
        root.body.classList.contains('nav-open') &&
        !nav.contains(event.target) &&
        !navToggle.contains(event.target)
      ) {
        setNavOpen(false);
      }
    });

    nav.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        setNavOpen(false);
      });
    });
  }

  if (backTop) {
    backTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
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
      target.scrollIntoView({
        behavior: prefersReducedMotion ? 'auto' : 'smooth',
        block: 'start',
      });
      setNavOpen(false);
    });
  });

  var revealItems = root.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window && revealItems.length && !prefersReducedMotion) {
    var revealObserver = new IntersectionObserver(
      function (entries, observer) {
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
              }, index * 85);
            });
          } else {
            target.classList.add('is-visible');
          }

          observer.unobserve(target);
        });
      },
      { threshold: 0.12, rootMargin: '0px 0px -48px 0px' }
    );

    revealItems.forEach(function (item) {
      revealObserver.observe(item);
    });
  } else {
    revealItems.forEach(function (item) {
      item.classList.add('is-visible');
    });
  }

  var animateCounter = function (el) {
    if (prefersReducedMotion) {
      var endStatic = parseInt(el.getAttribute('data-counter') || '0', 10);
      el.textContent = (Number.isNaN(endStatic) ? 0 : endStatic).toLocaleString();
      return;
    }

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
      el.textContent = Math.round(endValue * eased).toLocaleString();
      if (progress < 1) {
        window.requestAnimationFrame(frame);
      }
    };

    window.requestAnimationFrame(frame);
  };

  var carousels = root.querySelectorAll('.dn-auto-carousel');
  var mobileCarouselMq = window.matchMedia('(max-width: 992px)');

  carousels.forEach(function (carousel) {
    var track = carousel.querySelector('.dn-auto-carousel__track');
    if (!track) {
      return;
    }

    var speed = parseInt(carousel.getAttribute('data-speed') || '42', 10);
    if (!speed || speed < 1) {
      speed = 42;
    }

    var markDuplicates = function () {
      var items = Array.prototype.slice.call(track.children);
      var half = Math.floor(items.length / 2);
      items.forEach(function (item, index) {
        if (index >= half) {
          item.setAttribute('aria-hidden', 'true');
        } else {
          item.removeAttribute('aria-hidden');
        }
      });
      return items.length;
    };

    var setDuration = function () {
      if (!mobileCarouselMq.matches || prefersReducedMotion) {
        track.style.removeProperty('--dn-carousel-duration');
        return;
      }
      var halfWidth = track.scrollWidth / 2;
      if (halfWidth <= 0) {
        return;
      }
      var duration = Math.max(18, Math.min(90, halfWidth / speed));
      track.style.setProperty('--dn-carousel-duration', duration + 's');
    };

    var refreshCarousel = function () {
      if (markDuplicates() < 2) {
        return;
      }
      setDuration();
    };

    refreshCarousel();

    if ('ResizeObserver' in window) {
      var resizeObserver = new ResizeObserver(refreshCarousel);
      resizeObserver.observe(track);
    } else {
      window.addEventListener('resize', refreshCarousel);
    }

    if (typeof mobileCarouselMq.addEventListener === 'function') {
      mobileCarouselMq.addEventListener('change', refreshCarousel);
    } else if (typeof mobileCarouselMq.addListener === 'function') {
      mobileCarouselMq.addListener(refreshCarousel);
    }
  });

  var counters = root.querySelectorAll('[data-counter]');
  if ('IntersectionObserver' in window && counters.length) {
    var counterObserver = new IntersectionObserver(
      function (entries, observer) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.35 }
    );

    counters.forEach(function (counter) {
      if (counter.closest('[aria-hidden="true"]')) {
        return;
      }
      counterObserver.observe(counter);
    });
  } else {
    counters.forEach(function (counter) {
      if (!counter.closest('[aria-hidden="true"]')) {
        animateCounter(counter);
      }
    });
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
      var willExpand = !expanded;

      root.querySelectorAll('[data-faq-trigger]').forEach(function (other) {
        if (other === trigger) {
          return;
        }
        var otherPanelId = other.getAttribute('data-faq-trigger');
        var otherPanel = otherPanelId ? root.getElementById(otherPanelId) : null;
        other.setAttribute('aria-expanded', 'false');
        if (otherPanel) {
          otherPanel.hidden = true;
        }
      });

      trigger.setAttribute('aria-expanded', willExpand ? 'true' : 'false');
      panel.hidden = !willExpand;
    });
  });

  if (hero && !prefersReducedMotion) {
    hero.classList.add('is-ready');
  }
})();
