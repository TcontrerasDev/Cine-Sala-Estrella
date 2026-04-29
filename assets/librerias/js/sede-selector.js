/**
 * sede-selector.js
 * Gestiona la sede activa (Punta Arenas / Puerto Natales).
 * Datos cargados dinámicamente desde la API REST de WordPress (página id 18).
 * Persiste en localStorage y dispara evento 'sede:change' al cambiar.
 */
const STORAGE_KEY = "sedeActiva";

// Colores hex por ID de sede — fallback #888888 para sedes sin mapeo.
// Solución temporal hasta que ACF exponga un campo color_hex en la API.
const COLOR_MAP = {
  pa: "#27AE60",
  pn: "#C0392B",
};

let sedes      = [];       // [{ id, nombre, direccion, colorClass, dotClass, colorHero, colorHex }, ...]
let sedeActiva = "todas";  // índice numérico en sedes[] o "todas"

/* ─── API ────────────────────────────────────────────── */

async function ApiSede() {
  if (typeof Sedes === "undefined") {
    console.error("SedeSelector: objeto global 'Sedes' no disponible.");
    return [];
  }

  try {
    const url = `${Sedes.sedeurl}pages/18`;
    const response = await fetch(url);
    const data = await response.json();

    const ubicaciones = data.acf.ubicaciones;

    sedes = ubicaciones.map((u) => {
      const id = u.color.split("--").pop();
      return {
        id,
        nombre:     u.ciudad,
        direccion:  u.direccion,
        colorClass: "sede-" + id,
        dotClass:   "badge-" + id,
        colorHero:  u.color,
        colorHex:   COLOR_MAP[id] || "#888888",
      };
    });

    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === "todas") {
      sedeActiva = "todas";
    } else {
      const parsed = parseInt(stored, 10);
      sedeActiva = (Number.isInteger(parsed) && parsed >= 0 && parsed < sedes.length) ? parsed : "todas";
    }

    return sedes;
  } catch (error) {
    console.error("Error fetching sede data:", error);
    return [];
  }
}

/* ─── GETTERS / SETTERS ──────────────────────────────── */

function getSede() {
  return sedeActiva;
}

function getSedeData(index) {
  if (index === "todas") return null;
  return sedes[index] || sedes[0] || null;
}

function getSedeById(id) {
  return sedes.find((s) => s.id === id) || null;
}

function setSede(index) {
  if (index === "todas") {
    sedeActiva = "todas";
  } else {
    if (!sedes[index]) return;
    // Toggle off: clic en sede ya activa → volver a "todas"
    sedeActiva = (sedeActiva === index) ? "todas" : index;
  }

  localStorage.setItem(STORAGE_KEY, String(sedeActiva));

  const isTodas = sedeActiva === "todas";
  document.dispatchEvent(
    new CustomEvent("sede:change", {
      detail: isTodas
        ? { sede: "todas", sedeData: null, todas: true, index: "todas" }
        : {
            index:    sedeActiva,
            sede:     sedes[sedeActiva].id,
            sedeData: sedes[sedeActiva],
          },
      bubbles: true,
    }),
  );

  const banner = document.querySelector(".sede-banner");
  if (banner && banner.classList.contains("sede-banner--loaded")) {
    banner.classList.add("sede-banner--switching");
    setTimeout(() => {
      actualizarUI();
      banner.classList.remove("sede-banner--switching");
    }, 150);
  } else {
    actualizarUI();
  }
}

/* ─── ACTUALIZAR UI ──────────────────────────────────── */

function actualizarUI() {
  const isTodas = sedeActiva === "todas";
  const data = isTodas ? null : sedes[sedeActiva];

  document.querySelectorAll("[data-sede-nombre]").forEach((el) => {
    el.textContent = isTodas ? "Todas las sedes" : data.nombre;
  });

  document.querySelectorAll("[data-sede-direccion]").forEach((el) => {
    el.textContent = isTodas
      ? sedes.map((s) => `${s.nombre}: ${s.direccion}`).join(" | ")
      : data.direccion;
  });

  document
    .querySelectorAll(".sede-toggle-btn, .navbar-cine__sede-btn")
    .forEach((btn) => {
      const isActive = isTodas
        ? btn.dataset.sedeIndex === "todas"
        : btn.dataset.sedeIndex === String(sedeActiva);
      btn.classList.toggle("active", isActive);
      btn.setAttribute("aria-pressed", isActive ? "true" : "false");
    });

  document.querySelectorAll("[data-btn-sede]").forEach((btn) => {
    sedes.forEach((s) => btn.classList.remove("btn-" + s.id));
    if (!isTodas) btn.classList.add("btn-" + data.id);
  });

  document.querySelectorAll("[data-sede-badge]").forEach((el) => {
    sedes.forEach((s) => el.classList.remove(s.dotClass));
    if (!isTodas) el.classList.add(data.dotClass);
  });

  sedes.forEach((s) => document.body.classList.remove(s.colorClass));
  document.body.classList.remove("sede-todas");
  document.body.classList.add(isTodas ? "sede-todas" : data.colorClass);

  document.querySelectorAll("[data-sede-label]").forEach((el) => {
    el.textContent = isTodas ? "Todas las sedes" : data.nombre;
  });
}

/* ─── RENDER BUTTONS ─────────────────────────────────── */

function renderSedeButtons() {
  const container = document.querySelector(".sede-banner__toggle");
  if (!container) return;

  container.querySelectorAll(".sede-toggle-btn, .sede-banner__skeleton").forEach((el) => el.remove());

  // Botón "Todas" primero
  const todaBtn = document.createElement("button");
  todaBtn.className = "sede-toggle-btn sede-toggle-btn--todas";
  todaBtn.dataset.sedeIndex = "todas";
  const todaActive = sedeActiva === "todas";
  todaBtn.setAttribute("aria-pressed", todaActive ? "true" : "false");
  if (todaActive) todaBtn.classList.add("active");

  const icon = document.createElement("i");
  icon.className = "bi bi-geo-alt-fill";
  todaBtn.appendChild(icon);
  todaBtn.appendChild(document.createTextNode(" Todas"));
  todaBtn.addEventListener("click", () => setSede("todas"));
  container.appendChild(todaBtn);

  // Botones de sede individual
  sedes.forEach((sede, i) => {
    const btn = document.createElement("button");
    btn.className = "sede-toggle-btn dot-" + sede.id;
    btn.dataset.sedeIndex = i;
    const active = sedeActiva === i;
    btn.setAttribute("aria-pressed", active ? "true" : "false");
    if (active) btn.classList.add("active");

    const dot = document.createElement("span");
    dot.className = "dot-" + sede.id;
    dot.style.cssText = `display:inline-block;width:10px;height:10px;border-radius:50%;background-color:${sede.colorHex};margin-right:6px;flex-shrink:0;`;
    btn.appendChild(dot);

    btn.appendChild(document.createTextNode(sede.nombre));
    btn.addEventListener("click", () => setSede(i));
    container.appendChild(btn);
  });
}

/* ─── INIT ───────────────────────────────────────────── */

async function init() {
  await ApiSede();

  if (sedes.length === 0) return;

  renderSedeButtons();

  document.querySelectorAll("[data-sede-selector]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const id = btn.dataset.seda || btn.dataset.sedeSelector;
      if (id === "todas") { setSede("todas"); return; }
      const sede = getSedeById(id);
      if (sede) setSede(sedes.indexOf(sede));
    });
  });

  // Solo .navbar-cine__sede-btn — los .sede-toggle-btn ya tienen handler de renderSedeButtons()
  document.querySelectorAll(".navbar-cine__sede-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const idx = btn.dataset.sedeIndex;
      if (idx === "todas") { setSede("todas"); return; }
      const index = parseInt(idx, 10);
      if (!isNaN(index)) setSede(index);
    });
  });

  const banner = document.querySelector(".sede-banner");
  if (banner) {
    banner.classList.add("sede-banner--loaded");
    const fadeEls = banner.querySelectorAll("[data-sede-nombre], [data-sede-direccion]");
    setTimeout(() => {
      fadeEls.forEach((el) => {
        el.style.transition = "none";
        el.style.opacity = "0";
      });
      actualizarUI();
      requestAnimationFrame(() => requestAnimationFrame(() => {
        fadeEls.forEach((el) => {
          el.style.transition = "opacity 0.3s ease";
          el.style.opacity = "1";
        });
      }));
      banner.setAttribute("aria-busy", "false");
    }, 200);
  } else {
    actualizarUI();
  }
}

document.addEventListener("DOMContentLoaded", function () {
  init();
});

/* ─── API PÚBLICA ────────────────────────────────────── */

window.SedeSelector = {
  getSede,
  setSede,
  getSedeData,
  getSedeById,
  get sedes() { return sedes; },
};
