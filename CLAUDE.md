# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

WordPress theme `cine-sala-estrella` based on the `_s` (Underscores) starter theme. Cinema website for Cine Sala Estrella — two locations (Punta Arenas, Puerto Natales). Text domain: `cine-sala-estrella`. All functions prefixed `cine_sala_estrella_`. Theme slug used in handle names: `cine-sala-estrella-`.

## CLI commands

```sh
# PHP
composer install
composer lint:wpcs       # PHP Coding Standards check
composer lint:php        # PHP syntax check
composer make-pot        # Generate languages/_s.pot

# Node
npm install
npm run watch            # Watch sass/ → compile CSS
npm run compile:css      # One-shot SASS compile
npm run compile:rtl      # Generate style-rtl.css from style.css
npm run lint:scss        # SCSS coding standards
npm run lint:js          # JS coding standards
npm run bundle           # Zip for distribution
```

## Architecture

### Entry points

`functions.php` loads `inc/` files (standard _s setup), then at the bottom includes `assets/assets.php`.

`assets/assets.php` is the real asset orchestrator — it includes four files from `assets/includes/` and disables WordPress speculation rules:

| File | Purpose |
|------|---------|
| `assets/includes/fonts-functions.php` | Google Fonts preconnect + enqueue (Bebas Neue, Cinzel, DM Sans) |
| `assets/includes/css-functions.php` | Register/enqueue all theme CSS via `css_function()` on `wp_enqueue_scripts` |
| `assets/includes/js-functions.php` | Register/enqueue Bootstrap JS + custom scripts via `js_functions()` at priority 9999 |
| `assets/includes/menu-functions.php` | Custom logo filter, Bootstrap 5 nav walker (`bootstrap_5_wp_nav_menu_walker`) |

### CSS structure (`assets/librerias/css/`)

Three layers, all individually registered and enqueued (no bundler):

- `base/` — `variables.css`, `reset.css`, `typography.css`
- `components/` — badge, btn, card-pelicula, filtro-generos, footer, hero, modal-pelicula, navbar, sede-selector, timeline
- `pages/` — home, compra-final, contacto, historia, seleccion-asientos, single-pelicula

CDN: Bootstrap 5.3.8 CSS + Bootstrap Icons 1.13.1 loaded before local styles.

### JS (`assets/librerias/js/`)

- `scroll-effects.js` — scroll-based animations
- `sede-selector.js` — sede (branch) switching logic (Punta Arenas / Puerto Natales)
- Bootstrap 5.3.8 bundle from CDN

The `home-page.php` template conditionally localizes a `sedes` script with REST API URL + nonce (not yet enqueued in `js-functions.php` — `wp_register_script('sedes', ...)` call is missing).

### Templates

`home-page.php` is a custom page template (`Template Name: Inicio`) — assign it via WP admin to the front page. It renders `template-parts/content-home-page.php`.

`content-home-page.php` uses ACF fields: `antetitulo`, `frase`, `botones` (repeater: `enlace`, `estilo`, `icono`, `texto`), `ubicaciones` (repeater: `color`, `ciudad`, `direccion`).

`header.php` uses a custom hardcoded navbar (Bootstrap offcanvas for mobile) — the default `wp_nav_menu()` call is commented out. Nav links currently point to anchor hashes and `.html` files (not WP pages).

Standard `inc/` files from _s are present but lightly modified. WooCommerce and Jetpack files load conditionally.

### Sede selector

The sede-banner component (`content-home-page.php`) lets users toggle between Punta Arenas (`data-sede="pa"`) and Puerto Natales (`data-sede="pn"`). `sede-selector.js` drives this behavior and updates `[data-sede-*]` attributes in the DOM.

## Local dev

Site runs via MAMP at `C:\MAMP\htdocs\cine-sala-estrella`. WordPress root is three levels up from this theme directory (`C:\MAMP\htdocs\cine-sala-estrella`).

Requires: ACF (Advanced Custom Fields) plugin for field groups used in templates.
