# Sala Estrella Manager - GEMINI.md

## Project Overview
**Sala Estrella Manager** is a specialized WordPress plugin designed for "Cine Sala Estrella" to manage cinema listings, showtimes, rooms, and seat reservations. It integrates deeply with **WooCommerce** for the checkout process.

- **Stack**: PHP 7.4+, WordPress 6.0+, MySQL.
- **Dependency**: Requires **WooCommerce** to be active.
- **Environment**: Local development typically uses MAMP.

## Core Architecture
The plugin follows a hook-based modular OOP pattern. The main entry point is `sala-estrella-manager.php`, which initializes the `CNES_Plugin` orchestrator.

### Key Components
- **`CNES_Plugin`**: Central hub that wires together all modules via `CNES_Loader`.
- **`CNES_Peliculas`**: Manages the `pelicula` Custom Post Type and related taxonomies (Generos, Clasificaciones, Sedes).
- **`CNES_Admin_Salas`**: CRUD for cinema rooms and a JSON-based layout editor.
- **`CNES_Admin_Funciones`**: Manages showtimes (funciones) with overlap validation.
- **`CNES_Reservas`**: Core logic for seat blocking, state management, and cron cleanup.
- **`CNES_WooCommerce`**: Handles cart item data injection, price overrides, and order status transitions.
- **`CNES_Ajax`**: Endpoints for the frontend seat picker.

## Database Schema
The plugin uses four custom tables (prefixed with `wp_cnes_`):
- `cnes_salas`: Room definitions and JSON layouts.
- `cnes_asientos`: Individual seat properties (row, number, type).
- `cnes_funciones`: Showtimes linking movies to rooms.
- `cnes_reservas`: Real-time seat reservations (`seleccionado` or `pagado`).

## Booking Flow
1. **Selection**: User visits a page with the `[cnes_seleccion_asientos]` shortcode.
2. **Locking**: JS calls `cnes_bloquear_asiento` AJAX, creating a temporary `seleccionado` record (10-min expiry).
3. **Cart**: User adds seats to the WooCommerce cart; metadata is attached to the cart item.
4. **Checkout**: Upon payment, the reservation state changes to `pagado`, and a unique ticket code is generated.
5. **Cleanup**: A cron job (`cnes_limpiar_reservas_expiradas`) runs every 5 minutes to release expired seats.

## Development Conventions

### Naming Standards
- **Classes**: `CNES_ClassName` in `includes/class-cnes-{name}.php` or `admin/class-cnes-admin-{name}.php`.
- **Prefixes**: Use `cnes_` for functions, option keys, and database tables.
- **Constants**: `CNES_VERSION`, `CNES_PLUGIN_DIR`, `CNES_PLUGIN_URL`.
- **Text Domain**: `sala-estrella-manager`.

### Security & Quality
- **Database**: Always use `$wpdb->prepare()` for queries.
- **Verification**: Check `manage_options` capability and verify nonces for all admin actions and AJAX handlers.
- **Escaping**: Always escape output using `esc_html()`, `esc_url()`, or `esc_attr()`.
- **Translations**: UI strings must be in Spanish and wrapped in `__()` or `_e()`.

## Building and Running
- **Build System**: None. The project uses pure PHP, CSS, and Vanilla JS.
- **Installation**: Upload the folder to `wp-content/plugins/` and activate. Ensure WooCommerce is active first.
- **Testing**: Manual testing via the WordPress admin dashboard and the frontend shortcode pages.
