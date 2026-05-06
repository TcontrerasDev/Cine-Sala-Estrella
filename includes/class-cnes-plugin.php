<?php
/**
 * Core plugin class — wires together all components.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Orchestrates admin and public hooks through the loader.
 */
class CNES_Plugin {

	/** @var CNES_Loader Hook registrar. */
	protected $loader;

	/**
	 * Initialize loader and register hooks.
	 */
	public function __construct() {
		$this->loader = new CNES_Loader();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_common_hooks();
		new CNES_Ajax();
	}

	/**
	 * Register admin-side hooks.
	 */
	private function define_admin_hooks() {
		$admin       = new CNES_Admin();
		$admin_salas = new CNES_Admin_Salas();
		$wc          = new CNES_WooCommerce();
		
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $this,  'enqueue_dashboard_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu',            $admin, 'add_admin_menu' );

		// WooCommerce Admin Hooks
		$this->loader->add_action( 'woocommerce_order_status_processing', $wc, 'order_paid' );
		$this->loader->add_action( 'woocommerce_order_status_completed',  $wc, 'order_paid' );
		$this->loader->add_action( 'woocommerce_order_status_cancelled',  $wc, 'order_cancelled' );
		$this->loader->add_action( 'woocommerce_order_status_refunded',   $wc, 'order_cancelled' );
		$this->loader->add_action( 'woocommerce_order_status_failed',     $wc, 'order_cancelled' );

		// AJAX
		$this->loader->add_action( 'wp_ajax_cnes_guardar_layout_sala', $admin_salas, 'ajax_guardar_layout_sala' );

		$admin_funciones = new CNES_Admin_Funciones();
		$this->loader->add_action( 'wp_ajax_cnes_eliminar_funciones_masivo', $admin_funciones, 'ajax_eliminar_masivo' );
	}

	/**
	 * Register public-facing hooks.
	 */
	private function define_public_hooks() {
		$public = new CNES_Public();
		$wc     = new CNES_WooCommerce();

		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $public, 'enqueue_scripts' );
		$this->loader->add_action( 'init',               $public, 'register_shortcodes' );

		// WooCommerce Public Hooks
		$this->loader->add_filter( 'woocommerce_add_cart_item_data',          $wc, 'add_cart_item_data', 10, 3 );
		$this->loader->add_filter( 'woocommerce_add_to_cart_validation',      $wc, 'validate_add_to_cart', 10, 5 );
		$this->loader->add_filter( 'woocommerce_cart_id',                     $wc, 'cart_id', 10, 4 );
		$this->loader->add_action( 'woocommerce_before_calculate_totals',     $wc, 'before_calculate_totals', 20 );
		$this->loader->add_filter( 'woocommerce_cart_item_name',              $wc, 'cart_item_name', 10, 3 );
		$this->loader->add_filter( 'woocommerce_get_item_data',               $wc, 'get_item_data', 10, 2 );
		$this->loader->add_filter( 'woocommerce_cart_item_quantity_input_args', $wc, 'cart_item_quantity_input_args', 10, 2 );
		$this->loader->add_action( 'woocommerce_checkout_create_order_line_item', $wc, 'checkout_create_order_line_item', 10, 4 );
		$this->loader->add_action( 'woocommerce_email_order_details',         $wc, 'email_order_details', 10, 4 );
		$this->loader->add_action( 'woocommerce_cart_item_removed',           $wc, 'cart_item_removed', 10, 2 );
		$this->loader->add_action( 'woocommerce_cart_emptied',                $wc, 'cart_emptied' );
	}

	/**
	 * Register hooks shared between admin and frontend.
	 */
	private function define_common_hooks() {
		$peliculas = new CNES_Peliculas();
		$reservas  = new CNES_Reservas();

		$this->loader->add_action( 'init', $peliculas, 'register' );
		$this->loader->add_action( 'init', $peliculas, 'insert_default_terms' );

		// Verify the generic WC product exists on every admin load.
		$this->loader->add_action( 'init', new CNES_Helpers(), 'verificar_producto_entrada' );

		// Cron
		$this->loader->add_filter( 'cron_schedules', $this, 'add_custom_cron_intervals' );
		$this->loader->add_action( 'cnes_limpiar_reservas_expiradas', $reservas, 'limpiar_reservas_expiradas' );
		$this->loader->add_action( 'cnes_finalizar_funciones', $this, 'finalizar_funciones_pasadas' );
	}

	/**
	 * Add custom cron intervals.
	 */
	public function add_custom_cron_intervals( $schedules ) {
		$schedules['cnes_cinco_minutos'] = array(
			'interval' => 300,
			'display'  => esc_html__( 'Cada 5 minutos', 'sala-estrella-manager' ),
		);
		return $schedules;
	}

	/**
	 * Mark past functions as 'finalizada' based on fecha + hora_inicio.
	 */
	public function finalizar_funciones_pasadas() {
		global $wpdb;
		$tabla = CNES_Helpers::get_tabla( 'funciones' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( "UPDATE {$tabla} SET estado = 'finalizada' WHERE estado NOT IN ('cancelada','finalizada') AND TIMESTAMP(fecha, hora_inicio) < NOW()" );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * Enqueue dashboard-specific styles.
	 */
	public function enqueue_dashboard_styles( $hook_suffix ) {
		if ( 'toplevel_page_sala-estrella-manager' === $hook_suffix ) {
			wp_enqueue_style(
				CNES_PREFIX . 'dashboard',
				CNES_PLUGIN_URL . 'admin/css/cnes-admin-dashboard.css',
				array(),
				CNES_VERSION
			);
		}
	}
}
