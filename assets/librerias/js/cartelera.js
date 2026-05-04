/**
 * cartelera.js
 * Renderiza cards de cartelera desde la API REST de WordPress.
 * Depende de: sede-selector.js
 */

(function () {
  'use strict';

  /* ─── CONFIG ─────────────────────────────────────── */

  const DATA_URL = `${Peliculas.peliculasurl}pelicula?_embed`;
  const STAGGER_DELAY = 60;

  const SEDE_MAP = {
    'punta-arenas': 'pa',
    'puerto-natales': 'pn'
  };

  const CIUDAD_NOMBRE = {
    pa: 'Punta Arenas',
    pn: 'Puerto Natales'
  };

  let peliculasData = [];

  /* ─── HELPERS ────────────────────────────────────── */

  function getSede() {
    return window.SedeSelector ? window.SedeSelector.getSede() : 'pa';
  }

  function slugToLabel(slug) {
    return slug.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
  }

  function buildPosterUrl(poster, titulo) {
    return poster || `https://placehold.co/300x450/0D2B1A/27AE60?text=${encodeURIComponent(titulo)}`;
  }

  /* ─── NORMALIZAR ─────────────────────────────────── */

  function normalizarPelicula(raw) {
    const classList = raw.class_list || [];

    const generos = classList
      .filter(c => c.startsWith('genero-'))
      .map(c => c.replace('genero-', ''));

    const terms = ((raw._embedded && raw._embedded['wp:term']) || []).flat();
    const clasificacion = terms
      .filter(t => t.taxonomy === 'clasificacion')
      .map(t => t.name)[0] || '';

    const sedes = classList
      .filter(c => c.startsWith('sede-'))
      .map(c => SEDE_MAP[c.replace('sede-', '')])
      .filter(Boolean);

    const media = raw._embedded && raw._embedded['wp:featuredmedia'];
    const poster = (media && media[0]) ? media[0].source_url : null;

    const acf = raw.acf || {};

    return {
      id:           raw.id,
      slug:         raw.slug,
      titulo:       raw.title.rendered,
      poster,
      generos,
      clasificacion,
      sedes,
      duracion:     acf.duracion      || '',
      sinopsis:     acf.sinopsis      || '',
      director:     acf.director      || '',
      reparto:      acf.reparto       || '',
      trailer:      acf.embed_youtube || '',
      link:         raw.link
    };
  }

  /* ─── DATA LOADING ───────────────────────────────── */

  async function fetchPeliculas() {
    try {
      const response = await fetch(DATA_URL);
      if (!response.ok) throw new Error('Error al cargar las películas');
      const raw = await response.json();
      peliculasData = raw.map(normalizarPelicula);
      return peliculasData;
    } catch (error) {
      console.error('Fetch error:', error);
      return [];
    }
  }

  /* ─── RENDER CARD ────────────────────────────────── */

  function renderCard(pelicula, index) {
    const badgesCiudad = pelicula.sedes.length
      ? `<div class="card-pelicula__ciudad-badges">
          ${pelicula.sedes.map(s =>
            `<span class="badge-cine badge-${s}">${CIUDAD_NOMBRE[s] || s}</span>`
          ).join('')}
         </div>`
      : '';

    const badgeClasificacion = pelicula.clasificacion
      ? `<span class="badge-cine badge-card-clasificacion">${pelicula.clasificacion}</span>`
      : '';

    const posterUrl   = buildPosterUrl(pelicula.poster, pelicula.titulo);
    const generosLabel = pelicula.generos.map(slugToLabel).join(' · ');

    return `
      <a
        href="${pelicula.link}"
        class="card-pelicula"
        data-id="${pelicula.id}"
        data-generos="${pelicula.generos.join(' ')}"
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

          ${badgesCiudad}
          ${badgeClasificacion}

          <div class="card-pelicula__overlay">
            <p class="card-pelicula__overlay-titulo">${pelicula.titulo}</p>
            <div class="card-pelicula__overlay-meta">
              <span class="card-pelicula__overlay-genero">${generosLabel}</span>
              <span class="card-pelicula__separator">·</span>
              <span class="card-pelicula__overlay-duracion">
                <i class="bi bi-clock"></i> ${pelicula.duracion}
              </span>
            </div>
            <span class="btn-cine btn-sm btn-pa card-pelicula__btn-ver">Ver detalles</span>
          </div>
        </div>

        <div class="card-pelicula__info">
          <p class="card-pelicula__titulo">${pelicula.titulo}</p>
          <div class="card-pelicula__horario-preview">
            <span class="card-pelicula__duracion-text">
              <i class="bi bi-clock"></i> ${pelicula.duracion}
            </span>
          </div>
        </div>
      </a>
    `;
  }

  /* ─── RENDER GRID ────────────────────────────────── */

  function peliculasPorSede() {
    const sedeIndex = getSede();
    if (sedeIndex === 'todas') return peliculasData;
    const sedeData = window.SedeSelector ? window.SedeSelector.getSedeData(sedeIndex) : null;
    if (!sedeData) return peliculasData;
    return peliculasData.filter(p => p.sedes.includes(sedeData.id));
  }

  function renderCartelera() {
    const grid = document.getElementById('carteleraGrid');
    if (!grid) return;

    const peliculas = peliculasPorSede();

    if (peliculas.length === 0) {
      grid.innerHTML = `<div class="cartelera__empty">
        <div class="cartelera__empty-icon"><i class="bi bi-camera-reels"></i></div>
        <p class="cartelera__empty-text">No hay películas en cartelera.</p>
      </div>`;
      renderFiltros([]);
      return;
    }

    grid.innerHTML = peliculas.map((p, i) => renderCard(p, i)).join('');
    scheduleReveal(grid);
    renderFiltros(peliculas);
  }

  /* ─── FILTRO ─────────────────────────────────────── */

  function renderFiltros(peliculas) {
    const container = document.getElementById('filtroGeneros');
    if (!container) return;

    const generosSet = new Set();
    peliculas.forEach(p => p.generos.forEach(g => generosSet.add(g)));
    const generos = Array.from(generosSet).sort();

    if (generos.length === 0) {
      container.innerHTML = '';
      return;
    }

    container.innerHTML = ['todos', ...generos].map(g =>
      `<button
        class="filtro-btn ${g === 'todos' ? 'active' : ''}"
        data-genero="${g}"
        type="button"
      >${g === 'todos' ? 'Todos' : slugToLabel(g)}</button>`
    ).join('');

    container.querySelectorAll('.filtro-btn').forEach(btn => {
      btn.addEventListener('click', () => filtrarGenero(btn.dataset.genero, container));
    });
  }

  function filtrarGenero(genero, container) {
    const cont = container || document.getElementById('filtroGeneros');
    if (cont) {
      cont.querySelectorAll('.filtro-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.genero === genero);
      });
    }

    const grid = document.getElementById('carteleraGrid');
    if (!grid) return;

    const cards = grid.querySelectorAll('.card-pelicula');
    let visibleCount = 0;

    cards.forEach(card => {
      const cardGeneros = (card.dataset.generos || '').split(' ');
      const visible = genero === 'todos' || cardGeneros.includes(genero);

      card.classList.toggle('hidden', !visible);
      if (visible) visibleCount++;
    });

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
    const section = grid.closest('.seccion-cartelera');
    const target = section || grid;

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (!entry.isIntersecting) return;

        const cards = grid.querySelectorAll('.card-pelicula');
        cards.forEach((card, index) => {
          setTimeout(() => {
            card.classList.add('revealed');
            const img = card.querySelector('img[loading="lazy"]');
            if (img) img.classList.add('loaded');
          }, index * STAGGER_DELAY);
        });

        observer.unobserve(target);
      });
    }, { threshold: 0.1, rootMargin: '0px 0px -20px 0px' });

    observer.observe(target);
  }

  /* ─── SEDE CHANGE ────────────────────────────────── */

  document.addEventListener('sede:change', () => {
    renderCartelera();
  });

  /* ─── INIT ───────────────────────────────────────── */

  document.addEventListener('DOMContentLoaded', async () => {
    await fetchPeliculas();
    renderCartelera();
  });

  /* ─── API PÚBLICA ────────────────────────────────── */

  window.Cartelera = {
    renderCartelera,
    filtrarGenero
  };

})();
