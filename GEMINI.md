# Project Instructions

WordPress theme `cine-sala-estrella` based on the `_s` (Underscores) starter theme. Cinema website for Cine Sala Estrella — two locations (Punta Arenas, Puerto Natales).

## Core Information
- **Text Domain:** `cine-sala-estrella`
- **Function Prefix:** `cine_sala_estrella_`
- **Theme Slug:** `cine-sala-estrella-` (used in handle names)

## CLI Commands

```sh
# PHP
composer install
composer lint:wpcs       # PHP Coding Standards check
composer lint:php        # PHP syntax check
composer make-pot        # Generate languages/cine-sala-estrella.pot

# Node
npm install
npm run compile:rtl      # Generate style-rtl.css from style.css
npm run lint:js          # JS coding standards
npm run bundle           # Zip for distribution
```
*Note: The theme uses plain CSS in `assets/librerias/css/`. SASS scripts in `package.json` are currently unused as the `sass/` directory is not present.*

## Architecture

### Entry Points
`functions.php` loads `inc/` files, then includes `assets/assets.php` at the bottom.
`assets/assets.php` orchestrates theme assets by including files from `assets/includes/`:

| File | Purpose |
|------|---------|
| `assets/includes/fonts-functions.php` | Google Fonts preconnect + enqueue (Bebas Neue, Cinzel, DM Sans) |
| `assets/includes/css-functions.php` | Register/enqueue all theme CSS via `css_function()` |
| `assets/includes/js-functions.php` | Register/enqueue Bootstrap JS + custom scripts (priority 9999) |
| `assets/includes/menu-functions.php` | Custom logo filter, Bootstrap 5 walkers (`bootstrap_5_wp_nav_menu_walker`, `offcanvas_wp_nav_menu_walker`) |
| `assets/includes/widgets-functions.php`| Sidebar and widget registrations |

### CSS Structure (`assets/librerias/css/`)
Styles are organized into layers and enqueued individually:
- `base/`: `variables.css`, `reset.css`, `typography.css`
- `components/`: `badge.css`, `btn.css`, `card-pelicula.css`, `filtro-generos.css`, `footer.css`, `hero.css`, `info-importante.css`, `modal-pelicula.css`, `navbar.css`, `sede-selector.css`, `timeline.css`
- `pages/`: `home.css`, `compra-final.css`, `contacto.css`, `historia.css`, `seleccion-asientos.css`, `single-pelicula.css`

*Bootstrap 5.3.8 and Bootstrap Icons 1.13.1 are loaded from CDN before local styles.*

### JS (`assets/librerias/js/`)
- `scroll-effects.js`: Scroll-based animations.
- `sede-selector.js`: Sede (branch) switching logic. Localized with `Sedes` object (REST URL + nonce).
- `offcanvas-nav.js`: Mobile menu logic.
- `bootstrap.bundle.min.js`: Loaded from CDN.

### Templates
- `home-page.php`: Custom page template ("Inicio"). Renders `template-parts/content-home-page.php`.
- `content-home-page.php`: Uses ACF fields for hero, sede banner, important info, and history teaser.
- `header.php`: Uses `wp_nav_menu()` with custom Bootstrap 5 walkers for both desktop and mobile (offcanvas).

### Sede Selector
The sede-banner component (`content-home-page.php`) allows toggling between Punta Arenas (`pa`) and Puerto Natales (`pn`). `sede-selector.js` handles DOM updates and state based on `[data-sede-*]` attributes.

## Local Development
- **Environment:** MAMP at `C:\MAMP\htdocs\cine-sala-estrella`
- **Plugin Dependencies:** ACF (Advanced Custom Fields) is required for field groups.
