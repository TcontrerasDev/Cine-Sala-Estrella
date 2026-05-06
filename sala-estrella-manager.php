<?php
/**
 * Plugin Name: Sala Estrella Manager
 * Description: Sistema de gestión de cartelera, funciones, salas y reserva de asientos para Cine Sala Estrella.
* Version: 1.1.0
 * Author: Tomas Contreras
 * Text Domain: sala-estrella-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Constants
define( 'CNES_VERSION',         '1.1.0' );
define( 'CNES_PLUGIN_DIR',      plugin_dir_path( __FILE__ ) );
define( 'CNES_PLUGIN_URL',      plugin_dir_url( __FILE__ ) );
define( 'CNES_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'CNES_PREFIX',          'cnes_' );

/**
 * Check WooCommerce is active. Returns true if active.
 */
function cnes_is_woocommerce_active() {
	return in_array(
		'woocommerce/woocommerce.php',
		apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ),
		true
	);
}

/**
 * Admin notice when WooCommerce is missing.
 */
function cnes_woocommerce_missing_notice() {
	echo '<div class="notice notice-error"><p>'
		. esc_html__( 'Sala Estrella Manager requiere WooCommerce activo para funcionar.', 'sala-estrella-manager' )
		. '</p></div>';
}

// Bail early if WooCommerce is not active
if ( ! cnes_is_woocommerce_active() ) {
	add_action( 'admin_notices', 'cnes_woocommerce_missing_notice' );
	return;
}

// Includes
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-loader.php';
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-helpers.php';
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-activator.php';
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-deactivator.php';
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-peliculas.php';
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-reservas.php';
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-woocommerce.php';
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-ajax.php';
require_once CNES_PLUGIN_DIR . 'admin/class-cnes-admin-salas.php';
require_once CNES_PLUGIN_DIR . 'admin/class-cnes-admin-funciones.php';
require_once CNES_PLUGIN_DIR . 'admin/class-cnes-admin-dashboard.php';
require_once CNES_PLUGIN_DIR . 'admin/class-cnes-admin.php';
require_once CNES_PLUGIN_DIR . 'public/class-cnes-public.php';
require_once CNES_PLUGIN_DIR . 'includes/class-cnes-plugin.php';

// Lifecycle hooks
register_activation_hook( __FILE__,   array( 'CNES_Activator',   'activate'   ) );
register_deactivation_hook( __FILE__, array( 'CNES_Deactivator', 'deactivate' ) );

/**
 * Bootstrap plugin after all plugins are loaded.
 */
function cnes_run_plugin() {
	CNES_Activator::maybe_upgrade();
	$plugin = new CNES_Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', 'cnes_run_plugin' );
