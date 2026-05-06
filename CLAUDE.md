# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Environment

- **Stack**: WordPress 6.0+, PHP 7.4+, WooCommerce (required hard dependency), MySQL
- **Dev server**: MAMP at `http://localhost/cine-sala-estrella`
- **No build system**: pure PHP/CSS/JS — no npm, composer, or transpilation
- **No test suite**: manual testing via browser and WP admin

## Architecture

Hook-based modular OOP. `sala-estrella-manager.php` bootstraps constants and wires `plugins_loaded` → `CNES_Plugin::run()`. `CNES_Plugin` instantiates all classes and delegates to `CNES_Loader` to register actions/filters in bulk.

```
sala-estrella-manager.php
└── CNES_Plugin (orchestrator, includes/class-cnes-plugin.php)
    ├── CNES_Loader            — collects hooks, registers them in run()
    ├── CNES_Peliculas         — registers `pelicula` CPT + genero/clasificacion/sede taxonomies
    ├── CNES_Reservas          — seat blocking logic + cron cleanup
    ├── CNES_WooCommerce       — all WC cart/order hooks
    ├── CNES_Ajax              — wp_ajax_* handlers for seat picker
    ├── CNES_Admin             — admin menu + asset enqueue
    ├── CNES_Admin_Dashboard   — KPI dashboard page
    ├── CNES_Admin_Salas       — room CRUD + JSON layout editor
    ├── CNES_Admin_Funciones   — showtime CRUD + overlap validation
    ├── CNES_Public            — shortcode [cnes_seleccion_asientos] + public assets
    └── CNES_Helpers           — table names, price formatting, WC product check
```

## Database

Four custom tables created on activation (`CNES_Activator::activate()`). Schema version tracked in `wp_options` as `cnes_db_version`.

| Table | Purpose |
|---|---|
| `wp_cnes_salas` | Rooms — layout stored as JSON, sede field |
| `wp_cnes_asientos` | Individual seats — fila, numero, tipo (normal/vip) |
| `wp_cnes_funciones` | Showings — links pelicula+sala, fecha, hora, prices, estado |
| `wp_cnes_reservas` | Seat reservations — estado: seleccionado (10 min expiry) or pagado |

`CNES_Helpers::get_tabla($name)` returns the full prefixed table name.

## WooCommerce Integration

Single generic "Entrada de Cine" WC product is reused for all bookings. `CNES_Helpers::get_producto_entrada_id()` retrieves it; `verificar_producto_entrada()` recreates it on `init` if missing.

All booking context (película, sala, asientos, tipo, sede, fecha, hora) is injected as cart item data and saved to order line item metadata. Price is overridden in `woocommerce_before_calculate_totals` based on seat type.

Order status transitions drive reservation state:
- `processing` / `completed` → mark reservations `pagado`, generate ticket code
- `cancelled` / `refunded` / `failed` → delete reservations (release seats)

## Booking Flow

1. User sees `[cnes_seleccion_asientos?funcion_id=X]` shortcode page
2. JS calls `cnes_bloquear_asiento` AJAX → `CNES_Reservas::bloquear_asiento()` inserts `seleccionado` row with 10-min expiry
3. User clicks "Agregar al carrito" → `cnes_agregar_al_carrito` AJAX validates seats + adds WC cart item
4. WC checkout saves metadata → order paid → seats marked `pagado` + ticket code generated
5. Cron `cnes_limpiar_reservas_expiradas` (every 5 min) purges expired `seleccionado` rows

## Naming Conventions

- **Classes**: `CNES_ClassName` in `includes/class-cnes-{name}.php` or `admin/class-cnes-admin-{name}.php`
- **DB/option keys**: `cnes_` prefix
- **Constants**: `CNES_VERSION`, `CNES_PLUGIN_DIR`, `CNES_PLUGIN_URL`, `CNES_PREFIX`
- **Nonces**: `cnes_admin_nonce`, `cnes_reserva_nonce`
- **Text domain**: `sala-estrella-manager`
- **Language**: UI strings are Spanish

## Key Constraints

- All `wpdb` queries must use `$wpdb->prepare()` — no string interpolation with user data
- Admin actions require `manage_options` capability check + nonce verification
- AJAX handlers must verify nonce before any DB write
- Output must be escaped (`esc_html()`, `esc_url()`, `esc_attr()`)
- The `UNIQUE(funcion_id, asiento_id)` constraint on `wp_cnes_reservas` is the authoritative seat lock — DB-level, not just application logic
