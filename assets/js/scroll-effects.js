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

    elements.forEach(el => {
      /* Aplicar estado inicial */
      el.style.opacity = '0';
      el.style.transform = 'translateY(20px)';
      el.style.transition = `opacity 0.6s var(--ease-out-strong), transform 0.6s var(--ease-out-strong)`;
      observer.observe(el);
    });

    /* Agregar clase revealed con CSS */
    if (!document.getElementById('reveal-style')) {
      const style = document.createElement('style');
      style.id = 'reveal-style';
      style.textContent = `
        .reveal-element.revealed,
        .historia-stat.revealed,
        .historia-hoy__feature.revealed,
        .horarios__sede-card.revealed,
        .contacto-sede-card.revealed,
        .footer__brand.revealed,
        .footer__sedes.revealed,
        .footer__links.revealed {
          opacity: 1 !important;
          transform: translateY(0) !important;
        }
      `;
      document.head.appendChild(style);
    }
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

      if (heroBg) {
        heroBg.style.transform = `scale(1.03) translateY(${factor * 0.15}px)`;
      }
      if (heroBgImg) {
        heroBgImg.style.transform = `scale(1.06) translateY(${factor * 0.12}px)`;
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

    items.forEach((item, i) => {
      /* Lados alternados: izquierda/derecha */
      const direction = i % 2 === 0 ? -30 : 30;
      item.style.transform = `translateX(${direction}px) translateY(20px)`;
      observer.observe(item);
    });
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
              child.style.opacity = '1';
              child.style.transform = 'translateY(0)';
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
