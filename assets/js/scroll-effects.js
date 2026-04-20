/**
 * scroll-effects.js
 * - Navbar: fondo transparente → oscuro al scroll
 * - Reveal: elementos entran con fadeUp al hacer intersección
 * - Parallax suave en el hero
 * - Lazy images: marca como loaded al cargar
 */

(function () {
  'use strict';

  /* ─── NAVBAR SCROLL ──────────────────────────────── */

  function initNavbarScroll() {
    const navbar = document.querySelector('.navbar-cine');
    if (!navbar) return;

    let ticking = false;
    const THRESHOLD = 40;

    function updateNavbar() {
      const scrolled = window.scrollY > THRESHOLD;
      navbar.classList.toggle('navbar-cine--scrolled', scrolled);
      ticking = false;
    }

    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(updateNavbar);
        ticking = true;
      }
    }, { passive: true });

    /* Estado inicial */
    updateNavbar();
  }

  /* ─── REVEAL ENTRIES ─────────────────────────────── */

  function initRevealObserver() {
    const elements = document.querySelectorAll([
      '.reveal-element',
      '.historia-stat',
      '.historia-hoy__feature',
      '.horarios__sede-card',
      '.contacto-sede-card',
      '.footer__brand',
      '.footer__sedes',
      '.footer__links'
    ].join(', '));

    if (!elements.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -60px 0px'
    });

    /* Estado inicial y reveal manejados por CSS (.reveal-element en typography.css) */
    elements.forEach(el => observer.observe(el));
  }

  /* ─── PARALLAX HERO ──────────────────────────────── */

  function initParallax() {
    const heroBg = document.querySelector('.hero__bg');
    const heroBgImg = document.querySelector('.hero__bg-img');
    if (!heroBg && !heroBgImg) return;

    /* Solo en desktop — mobile skip para performance */
    if (window.matchMedia('(max-width: 767px)').matches) return;

    let ticking = false;

    function updateParallax() {
      const scrollY = window.scrollY;
      const factor = scrollY * 0.3;

      /* Setear custom properties — los transforms viven en CSS (hero.css) */
      if (heroBg) {
        heroBg.style.setProperty('--parallax-y', `${factor * 0.15}px`);
      }
      if (heroBgImg) {
        heroBgImg.style.setProperty('--parallax-y', `${factor * 0.12}px`);
      }

      ticking = false;
    }

    window.addEventListener('scroll', () => {
      if (!ticking) {
        requestAnimationFrame(updateParallax);
        ticking = true;
      }
    }, { passive: true });
  }

  /* ─── TIMELINE REVEAL ────────────────────────────── */

  function initTimelineReveal() {
    const items = document.querySelectorAll('.timeline__item');
    if (!items.length) return;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -80px 0px'
    });

    /* Transforms iniciales alternados manejados por CSS (timeline.css nth-child) */
    items.forEach(item => observer.observe(item));
  }

  /* ─── LAZY IMAGES ────────────────────────────────── */

  function initLazyImages() {
    const images = document.querySelectorAll('img[loading="lazy"]');
    if (!images.length) return;

    images.forEach(img => {
      if (img.complete) {
        img.classList.add('loaded');
      } else {
        img.addEventListener('load', () => img.classList.add('loaded'));
        img.addEventListener('error', () => img.classList.add('loaded')); /* no-op fallback */
      }
    });
  }

  /* ─── ACTIVE NAV LINK ────────────────────────────── */

  function initActiveNavLink() {
    const path = window.location.pathname;
    const links = document.querySelectorAll('.navbar-cine__link, .offcanvas-cine .nav-link-mobile');

    links.forEach(link => {
      const href = link.getAttribute('href') || '';
      /* Marca como active si el href coincide con la página actual */
      if (href === path || href === path.split('/').pop()) {
        link.classList.add('active');
      }
    });
  }

  /* ─── SMOOTH ANCHOR SCROLL ───────────────────────── */

  function initSmoothAnchors() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', e => {
        const href = anchor.getAttribute('href');
        if (href === '#') return;

        const target = document.querySelector(href);
        if (!target) return;

        e.preventDefault();
        const offset = 80; /* navbar height aprox */
        const top = target.getBoundingClientRect().top + window.scrollY - offset;

        window.scrollTo({ top, behavior: 'smooth' });
      });
    });
  }

  /* ─── STAGGERED SECTION REVEAL ───────────────────── */

  function initSectionReveal() {
    const sections = document.querySelectorAll('.seccion, section');

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          /* Stagger hijos directos con clase .reveal-child */
          const children = entry.target.querySelectorAll('.reveal-child');
          children.forEach((child, i) => {
            setTimeout(() => {
              child.classList.add('reveal-child--visible');
            }, i * 80);
          });
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.05 });

    sections.forEach(s => observer.observe(s));
  }

  /* ─── INIT ───────────────────────────────────────── */

  document.addEventListener('DOMContentLoaded', () => {
    initNavbarScroll();
    initRevealObserver();
    initParallax();
    initTimelineReveal();
    initLazyImages();
    initActiveNavLink();
    initSmoothAnchors();
    initSectionReveal();
  });

  /* ─── EXPORT ─────────────────────────────────────── */

  window.ScrollEffects = {
    initNavbarScroll,
    initRevealObserver,
    initParallax
  };

})();
