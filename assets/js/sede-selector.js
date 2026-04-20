/**
 * sede-selector.js
 * Gestiona la sede activa (Punta Arenas / Puerto Natales).
 * Persiste en localStorage y dispara evento 'sede:change' al cambiar.
 */

(function () {
  'use strict';

  const STORAGE_KEY = 'sedeActiva';
  const SEDES = {
    pa: {
      nombre: 'Punta Arenas',
      color: '#27AE60',
      colorClass: 'sede-pa',
      direccion: 'Mejicana 777',
      dotClass: 'badge-pa'
    },
    pn: {
      nombre: 'Puerto Natales',
      color: '#C0392B',
      colorClass: 'sede-pn',
      direccion: 'Esmeralda 777',
      dotClass: 'badge-pn'
    }
  };

  /* ─── ESTADO ─────────────────────────────────────── */

  let sedeActiva = localStorage.getItem(STORAGE_KEY) || 'pa';

  /* ─── GETTERS / SETTERS ──────────────────────────── */

  function getSede() {
    return sedeActiva;
  }

  function getSedeData(id) {
    return SEDES[id] || SEDES.pa;
  }

  function setSede(nuevaSede) {
    if (!SEDES[nuevaSede]) return;
    sedeActiva = nuevaSede;
    localStorage.setItem(STORAGE_KEY, sedeActiva);

    document.dispatchEvent(new CustomEvent('sede:change', {
      detail: { sede: sedeActiva, sedeData: SEDES[sedeActiva] },
      bubbles: true
    }));

    actualizarUI();
  }

  /* ─── ACTUALIZAR UI ──────────────────────────────── */

  function actualizarUI() {
    const data = SEDES[sedeActiva];

    /* 1. Nombre de sede en todos los [data-sede-nombre] */
    document.querySelectorAll('[data-sede-nombre]').forEach(el => {
      el.textContent = data.nombre;
    });

    /* 2. Dirección en [data-sede-direccion] */
    document.querySelectorAll('[data-sede-direccion]').forEach(el => {
      el.textContent = data.direccion;
    });

    /* 3. Color dot en [data-sede-dot]: gestionado por CSS via clase en body (.sede-pa / .sede-pn) */

    /* 4. Toggle buttons — active state */
    document.querySelectorAll('.sede-toggle-btn, .navbar-cine__sede-btn').forEach(btn => {
      const btnSede = btn.dataset.sede;
      btn.classList.toggle('active', btnSede === sedeActiva);
    });

    /* 5. Botones de compra dinámicos */
    document.querySelectorAll('[data-btn-sede]').forEach(btn => {
      btn.classList.remove('btn-pa', 'btn-pn');
      btn.classList.add(sedeActiva === 'pa' ? 'btn-pa' : 'btn-pn');
    });

    /* 6. Badge coloreado en navbar */
    document.querySelectorAll('[data-sede-badge]').forEach(el => {
      el.classList.remove('badge-pa', 'badge-pn');
      el.classList.add(data.dotClass);
    });

    /* 7. Clase en body para selectores CSS contextuales */
    document.body.classList.remove('sede-pa', 'sede-pn');
    document.body.classList.add(data.colorClass);

    /* 8. Texto en botones de offcanvas */
    document.querySelectorAll('[data-sede-label]').forEach(el => {
      el.textContent = data.nombre;
    });
  }

  /* ─── INIT ───────────────────────────────────────── */

  function init() {
    /* Botones toggle de sede */
    document.querySelectorAll('[data-sede-selector]').forEach(btn => {
      btn.addEventListener('click', () => {
        const nuevaSede = btn.dataset.seda || btn.dataset.sedeSelector;
        setSede(nuevaSede);
      });
    });

    /* Botones con data-sede en la navbar */
    document.querySelectorAll('.navbar-cine__sede-btn, .sede-toggle-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const nuevaSede = btn.dataset.sede;
        if (nuevaSede) setSede(nuevaSede);
      });
    });

    /* Aplicar estado inicial */
    actualizarUI();
  }

  document.addEventListener('DOMContentLoaded', init);

  /* ─── API PÚBLICA ────────────────────────────────── */

  window.SedeSelector = {
    getSede,
    setSede,
    getSedeData,
    SEDES
  };

})();
