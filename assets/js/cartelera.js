/**
 * cartelera.js
 * Renderiza las cards de cartelera y gestiona el filtro de géneros.
 * Depende de: peliculas.js, sede-selector.js
 */

(function () {
  'use strict';

  /* ─── CONFIG ─────────────────────────────────────── */

  const STAGGER_DELAY = 60; /* ms entre cards */

  const GENEROS_LABEL = {
    todos:       'Todos',
    accion:      'Acción',
    drama:       'Drama',
    familiar:    'Familiar',
    terror:      'Terror',
    thriller:    'Thriller',
    romance:     'Romance',
    documental:  'Documental'
  };

  /* ─── HELPERS ────────────────────────────────────── */

  function getSede() {
    return window.SedeSelector ? window.SedeSelector.getSede() : 'pa';
  }

  function getGeneroLabel(genero) {
    return GENEROS_LABEL[genero] || genero;
  }

  function getClasificacionClass(clas) {
    if (clas === 'ATP') return 'badge-atp';
    return 'badge-clasificacion';
  }

  function buildPosterUrl(poster, titulo) {
    return poster || `https://placehold.co/300x450/0D2B1A/27AE60?text=${encodeURIComponent(titulo)}`;
  }

  /* ─── RENDER CARD ────────────────────────────────── */

  function renderCard(pelicula, index, isProximamente) {
    const sede = getSede();
    const horarios = pelicula.horarios[sede] || [];
    const horariosHtml = horarios.length > 0
      ? horarios.slice(0, 3).map(h =>
          `<span class="card-pelicula__horario-pill ${sede === 'pn' ? 'card-pelicula__horario-pill--pn' : ''}">${h}</span>`
        ).join('')
      : `<span class="card-pelicula__solo-text">Solo ${sede === 'pa' ? 'Puerto Natales' : 'Punta Arenas'}</span>`;

    const badgesSede = pelicula.sedes.map(s =>
      `<span class="badge-cine badge-card-sede ${s === 'pa' ? 'badge-pa' : 'badge-pn'}">${s === 'pa' ? 'PA' : 'PN'}</span>`
    ).join('');

    const badgeSoloSede = pelicula.sedes.length === 1
      ? `<span class="badge-cine badge-card-sede ${pelicula.sedes[0] === 'pa' ? 'badge-pa' : 'badge-pn'}">${pelicula.sedes[0] === 'pa' ? 'Solo PA' : 'Solo PN'}</span>`
      : ``;

    const badgeEstreno = pelicula.enEstreno
      ? `<span class="badge-cine badge-estreno badge-card-sede">Estreno</span>`
      : '';

    const badgeClasificacion = `<span class="badge-cine badge-card-clasificacion ${getClasificacionClass(pelicula.clasificacion)}">${pelicula.clasificacion}</span>`;

    const proximamenteClass = isProximamente ? 'card-pelicula--proximamente' : '';
    const posterUrl = buildPosterUrl(pelicula.poster, pelicula.titulo);

    return `
      <a
        href="single-pagina.html"
        class="card-pelicula ${proximamenteClass}"
        data-id="${pelicula.id}"
        data-genero="${pelicula.genero}"
        data-index="${index}"
        aria-label="Ver ficha de ${pelicula.titulo}"
      >
        <div class="card-pelicula__poster">
          <img
            src="${posterUrl}"
            alt="Póster de ${pelicula.titulo}"
            class="card-pelicula__img"
            loading="lazy"
          >

          ${pelicula.enEstreno ? badgeEstreno : (pelicula.sedes.length === 1 ? badgeSoloSede : '')}
          ${badgeClasificacion}

          <div class="card-pelicula__overlay">
            <p class="card-pelicula__overlay-titulo">${pelicula.titulo}</p>
            <div class="card-pelicula__overlay-meta">
              <span class="card-pelicula__overlay-genero">${getGeneroLabel(pelicula.genero)}</span>
              <span class="card-pelicula__separator">·</span>
              <span class="card-pelicula__overlay-duracion">
                <i class="bi bi-clock"></i> ${pelicula.duracion}
              </span>
            </div>
            ${!isProximamente ? `<span class="btn-cine btn-sm btn-pa card-pelicula__btn-ver">Ver detalles</span>` : ''}
          </div>
        </div>

        <div class="card-pelicula__info">
          <p class="card-pelicula__titulo">${pelicula.titulo}</p>
          <div class="card-pelicula__horario-preview">
            ${isProximamente
              ? `<span class="card-pelicula__proximamente-text">Próximamente</span>`
              : horariosHtml
            }
          </div>
        </div>
      </a>
    `;
  }

  /* ─── RENDER GRID ────────────────────────────────── */

  function renderCartelera() {
    const grid = document.getElementById('carteleraGrid');
    if (!grid) return;

    const peliculas = (window.PELICULAS || []).filter(p => !p.proximamente);
    if (peliculas.length === 0) {
      grid.innerHTML = `<div class="cartelera__empty">
        <div class="cartelera__empty-icon"><i class="bi bi-camera-reels"></i></div>
        <p class="cartelera__empty-text">No hay películas en cartelera.</p>
      </div>`;
      return;
    }

    grid.innerHTML = peliculas.map((p, i) => renderCard(p, i, false)).join('');
    scheduleReveal(grid);
    renderFiltros();
  }

  function renderProximamente() {
    const grid = document.getElementById('proximamenteGrid');
    if (!grid) return;

    const peliculas = (window.PELICULAS || []).filter(p => p.proximamente);
    if (peliculas.length === 0) return;

    grid.innerHTML = peliculas.map((p, i) => renderCard(p, i, true)).join('');
    scheduleReveal(grid);
  }

  /* ─── FILTRO ─────────────────────────────────────── */

  function renderFiltros() {
    const container = document.getElementById('filtroGeneros');
    if (!container) return;

    const peliculas = (window.PELICULAS || []).filter(p => !p.proximamente);
    const generosSet = new Set(peliculas.map(p => p.genero));
    const generos = ['todos', ...Array.from(generosSet).sort()];

    container.innerHTML = generos.map(g =>
      `<button
        class="filtro-btn ${g === 'todos' ? 'active' : ''}"
        data-genero="${g}"
        type="button"
      >${getGeneroLabel(g)}</button>`
    ).join('');

    container.querySelectorAll('.filtro-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const genero = btn.dataset.genero;
        filtrarGenero(genero, container);
      });
    });
  }

  function filtrarGenero(genero, container) {
    /* Actualizar botones activos */
    container.querySelectorAll('.filtro-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.genero === genero);
    });

    /* Mostrar/ocultar cards con fade */
    const grid = document.getElementById('carteleraGrid');
    if (!grid) return;

    const cards = grid.querySelectorAll('.card-pelicula');
    let visibleCount = 0;

    cards.forEach(card => {
      const cardGenero = card.dataset.genero;
      const visible = genero === 'todos' || cardGenero === genero;

      if (visible) {
        card.classList.remove('hidden');
        visibleCount++;
      } else {
        card.classList.add('hidden');
      }
    });

    /* Estado vacío */
    let emptyEl = grid.querySelector('.cartelera__empty');
    if (visibleCount === 0 && !emptyEl) {
      emptyEl = document.createElement('div');
      emptyEl.className = 'cartelera__empty';
      emptyEl.innerHTML = `
        <div class="cartelera__empty-icon"><i class="bi bi-film"></i></div>
        <p class="cartelera__empty-text">No hay películas en este género actualmente.</p>
      `;
      grid.appendChild(emptyEl);
    } else if (visibleCount > 0 && emptyEl) {
      emptyEl.remove();
    }
  }

  /* ─── STAGGERED REVEAL ───────────────────────────── */

  function scheduleReveal(grid) {
    const cards = grid.querySelectorAll('.card-pelicula');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          const card = entry.target;
          const index = parseInt(card.dataset.index) || 0;
          setTimeout(() => {
            card.classList.add('revealed');
            /* Lazy images */
            const img = card.querySelector('img[loading="lazy"]');
            if (img) img.classList.add('loaded');
          }, index * STAGGER_DELAY);
          observer.unobserve(card);
        }
      });
    }, {
      threshold: 0.08,
      rootMargin: '0px 0px -40px 0px'
    });

    cards.forEach(card => observer.observe(card));
  }

  /* ─── ACTUALIZAR HORARIOS AL CAMBIAR SEDE ────────── */

  document.addEventListener('sede:change', () => {
    const grid = document.getElementById('carteleraGrid');
    if (!grid) return;

    const sede = getSede();
    grid.querySelectorAll('.card-pelicula').forEach(card => {
      const id = parseInt(card.dataset.id);
      const pelicula = (window.PELICULAS || []).find(p => p.id === id);
      if (!pelicula) return;

      const horarios = pelicula.horarios[sede] || [];
      const preview = card.querySelector('.card-pelicula__horario-preview');
      if (!preview) return;

      if (horarios.length > 0) {
        preview.innerHTML = horarios.slice(0, 3).map(h =>
          `<span class="card-pelicula__horario-pill ${sede === 'pn' ? 'card-pelicula__horario-pill--pn' : ''}">${h}</span>`
        ).join('');
      } else {
        preview.innerHTML = `<span class="card-pelicula__solo-text">Solo ${sede === 'pa' ? 'Puerto Natales' : 'Punta Arenas'}</span>`;
      }
    });
  });

  /* ─── INIT ───────────────────────────────────────── */

  document.addEventListener('DOMContentLoaded', () => {
    renderCartelera();
    renderProximamente();
  });

  /* ─── API PÚBLICA ────────────────────────────────── */

  window.Cartelera = {
    renderCartelera,
    renderProximamente,
    filtrarGenero
  };

})();
