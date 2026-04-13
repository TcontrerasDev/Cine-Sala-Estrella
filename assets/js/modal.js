/**
 * modal.js
 * Modal dinámico de película — carga datos desde PELICULAS[].
 * Usa Bootstrap Modal.
 * Depende de: peliculas.js, sede-selector.js
 */

(function () {
  'use strict';

  /* ─── HELPERS ────────────────────────────────────── */

  function getSede() {
    return window.SedeSelector ? window.SedeSelector.getSede() : 'pa';
  }

  function getSedeNombre(sede) {
    return sede === 'pa' ? 'Punta Arenas' : 'Puerto Natales';
  }

  function getPeliculaById(id) {
    return (window.PELICULAS || []).find(p => p.id === id) || null;
  }

  function buildPosterUrl(poster, titulo) {
    return poster || `https://placehold.co/300x450/0D2B1A/27AE60?text=${encodeURIComponent(titulo)}`;
  }

  /* ─── RENDER MODAL ───────────────────────────────── */

  function renderModal(pelicula) {
    const sede = getSede();
    const horarios = pelicula.horarios[sede] || [];
    const sedeNombre = getSedeNombre(sede);
    const posterUrl = buildPosterUrl(pelicula.poster, pelicula.titulo);

    /* Header */
    const titleEl = document.getElementById('modalPeliculaLabel');
    if (titleEl) titleEl.textContent = pelicula.titulo;

    /* Body */
    const bodyEl = document.querySelector('#modalPelicula .modal-body');
    if (!bodyEl) return;

    const horariosHtml = horarios.length > 0
      ? horarios.map(h =>
          `<button class="modal-pelicula__horario-chip" type="button">${h}</button>`
        ).join('')
      : `<p class="modal-pelicula__no-horarios">No disponible en ${sedeNombre} actualmente.</p>`;

    const badgesSede = pelicula.sedes.map(s =>
      `<span class="badge-cine ${s === 'pa' ? 'badge-pa' : 'badge-pn'}">${s === 'pa' ? 'Punta Arenas' : 'Puerto Natales'}</span>`
    ).join('');

    const badgeClasificacion = `<span class="badge-cine ${pelicula.clasificacion === 'ATP' ? 'badge-atp' : 'badge-clasificacion'}">${pelicula.clasificacion}</span>`;
    const badgeGenero = `<span class="badge-cine badge-genero">${pelicula.genero.charAt(0).toUpperCase() + pelicula.genero.slice(1)}</span>`;
    const badgeEstreno = pelicula.enEstreno
      ? `<span class="badge-cine badge-estreno">Estreno</span>`
      : '';

    const btnSedeCls = sede === 'pa' ? 'btn-pa' : 'btn-pn';

    bodyEl.innerHTML = `
      <div class="modal-pelicula__grid">

        <!-- POSTER -->
        <div class="modal-pelicula__poster-col">
          <img
            src="${posterUrl}"
            alt="Póster de ${pelicula.titulo}"
            class="modal-pelicula__poster"
            loading="lazy"
          >
          <div class="modal-pelicula__poster-overlay"></div>
        </div>

        <!-- INFO -->
        <div class="modal-pelicula__info-col">

          <h2 class="modal-pelicula__titulo">${pelicula.titulo}</h2>

          <div class="modal-pelicula__badges">
            ${badgeClasificacion}
            ${badgeGenero}
            ${badgeEstreno}
          </div>

          <div class="modal-pelicula__meta-grid">
            <div class="modal-pelicula__meta-item">
              <span class="modal-pelicula__meta-label">Director</span>
              <span class="modal-pelicula__meta-value">${pelicula.director}</span>
            </div>
            <div class="modal-pelicula__meta-item">
              <span class="modal-pelicula__meta-label">Duración</span>
              <span class="modal-pelicula__meta-value">
                <i class="bi bi-clock" style="color: var(--color-verde-hover); margin-right: 4px;"></i>
                ${pelicula.duracion}
              </span>
            </div>
            <div class="modal-pelicula__meta-item">
              <span class="modal-pelicula__meta-label">Clasificación</span>
              <span class="modal-pelicula__meta-value">${pelicula.clasificacion}</span>
            </div>
            <div class="modal-pelicula__meta-item">
              <span class="modal-pelicula__meta-label">Sedes</span>
              <div style="display: flex; gap: var(--space-1); flex-wrap: wrap; margin-top: 2px;">
                ${badgesSede}
              </div>
            </div>
          </div>

          <!-- SINOPSIS -->
          <div>
            <p class="modal-pelicula__sinopsis-label">Sinopsis</p>
            <p class="modal-pelicula__sinopsis">${pelicula.sinopsis}</p>
          </div>

          <!-- HORARIOS -->
          <div class="modal-pelicula__horarios">
            <p class="modal-pelicula__horarios-label">
              <i class="bi bi-geo-alt-fill"></i>
              Horarios en ${sedeNombre}
            </p>
            <div class="modal-pelicula__horarios-grid" id="modalHorariosGrid">
              ${horariosHtml}
            </div>
          </div>

          <!-- TRAILER -->
          ${pelicula.trailer ? `
          <div>
            <p class="modal-pelicula__sinopsis-label" style="margin-bottom: var(--space-3);">Tráiler oficial</p>
            <div class="modal-pelicula__trailer">
              <iframe
                src="${pelicula.trailer}"
                title="Tráiler de ${pelicula.titulo}"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
                loading="lazy"
              ></iframe>
            </div>
          </div>
          ` : ''}

          <!-- CTA -->
          <div class="modal-pelicula__cta">
            ${!pelicula.proximamente
              ? `<a
                  href="https://cineestrella.cl"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="btn-cine btn-lg ${btnSedeCls}"
                  data-btn-sede
                >
                  <i class="bi bi-ticket-perforated-fill"></i>
                  Comprar entradas
                </a>`
              : `<button class="btn-cine btn-lg btn-ghost" disabled style="width: 100%; justify-content: center;">
                  <i class="bi bi-clock"></i>
                  Próximamente disponible
                </button>`
            }
          </div>

        </div><!-- /.modal-pelicula__info-col -->
      </div><!-- /.modal-pelicula__grid -->
    `;

    /* Footer — sede activa */
    const footerSede = document.getElementById('modalFooterSede');
    if (footerSede) {
      const dotColor = sede === 'pa' ? 'var(--color-verde-hover)' : 'var(--color-rojo-vivo)';
      footerSede.innerHTML = `
        <div class="modal-sede-badge">
          <span style="width: 7px; height: 7px; border-radius: 50%; background: ${dotColor}; display: inline-block;"></span>
          Viendo horarios para <strong style="color: var(--color-blanco);">${sedeNombre}</strong>
        </div>
      `;
    }
  }

  /* ─── ABRIR MODAL ────────────────────────────────── */

  function abrir(id) {
    const pelicula = getPeliculaById(id);
    if (!pelicula) return;

    renderModal(pelicula);

    const modalEl = document.getElementById('modalPelicula');
    if (!modalEl) return;

    /* Guardar id actual para actualizar al cambiar sede */
    modalEl.dataset.peliculaId = id;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl, {
      backdrop: true,
      keyboard: true
    });
    modal.show();
  }

  /* ─── CERRAR MODAL ───────────────────────────────── */

  function cerrar() {
    const modalEl = document.getElementById('modalPelicula');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
  }

  /* ─── ACTUALIZAR AL CAMBIAR SEDE ─────────────────── */

  document.addEventListener('sede:change', () => {
    const modalEl = document.getElementById('modalPelicula');
    if (!modalEl) return;

    const id = parseInt(modalEl.dataset.peliculaId);
    if (!id) return;

    /* Solo actualizar si el modal está abierto */
    const bsModal = bootstrap.Modal.getInstance(modalEl);
    if (!bsModal) return;

    /* Actualizar horarios */
    const pelicula = getPeliculaById(id);
    if (!pelicula) return;

    const sede = getSede();
    const sedeNombre = getSedeNombre(sede);
    const horarios = pelicula.horarios[sede] || [];

    const horariosGrid = document.getElementById('modalHorariosGrid');
    if (horariosGrid) {
      if (horarios.length > 0) {
        horariosGrid.innerHTML = horarios.map(h =>
          `<button class="modal-pelicula__horario-chip">${h}</button>`
        ).join('');
      } else {
        horariosGrid.innerHTML = `<p class="modal-pelicula__no-horarios">No disponible en ${sedeNombre} actualmente.</p>`;
      }
    }

    /* Actualizar label de sede */
    const horariosLabel = document.querySelector('.modal-pelicula__horarios-label');
    if (horariosLabel) {
      horariosLabel.innerHTML = `<i class="bi bi-geo-alt-fill"></i> Horarios en ${sedeNombre}`;
    }

    /* Actualizar botón CTA */
    const ctaBtn = document.querySelector('#modalPelicula [data-btn-sede]');
    if (ctaBtn) {
      ctaBtn.classList.remove('btn-pa', 'btn-pn');
      ctaBtn.classList.add(sede === 'pa' ? 'btn-pa' : 'btn-pn');
    }

    /* Actualizar footer */
    const footerSede = document.getElementById('modalFooterSede');
    if (footerSede) {
      const dotColor = sede === 'pa' ? 'var(--color-verde-hover)' : 'var(--color-rojo-vivo)';
      footerSede.innerHTML = `
        <div class="modal-sede-badge">
          <span style="width: 7px; height: 7px; border-radius: 50%; background: ${dotColor}; display: inline-block;"></span>
          Viendo horarios para <strong style="color: var(--color-blanco);">${sedeNombre}</strong>
        </div>
      `;
    }
  });

  /* ─── API PÚBLICA ────────────────────────────────── */

  window.ModalPelicula = { abrir, cerrar };

})();
