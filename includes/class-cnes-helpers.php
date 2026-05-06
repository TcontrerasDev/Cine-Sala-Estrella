<?php
/**
 * Utility helpers shared across the plugin.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Static utility methods for Sala Estrella Manager.
 */
class CNES_Helpers {

	/**
	 * Return the full table name for a given plugin table slug.
	 *
	 * @param string $nombre Table slug without prefix, e.g. 'salas'.
	 * @return string Full table name, e.g. 'wp_cnes_salas'.
	 */
	public static function get_tabla( $nombre ) {
		global $wpdb;
		return $wpdb->prefix . 'cnes_' . $nombre;
	}

	/**
	 * Check whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return in_array(
			'woocommerce/woocommerce.php',
			apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) ),
			true
		);
	}

	/**
	 * Get the post ID of the generic "Entrada de Cine" WC product.
	 *
	 * @return int|false Product ID or false if not set.
	 */
	public static function get_producto_entrada_id() {
		return get_option( 'cnes_producto_entrada_id', false );
	}

	/**
	 * Ensure the generic WC product exists; recreate it if missing or trashed.
	 * Only runs in the admin to avoid frontend overhead.
	 */
	public static function verificar_producto_entrada() {
		if ( ! is_admin() ) {
			return;
		}

		$producto_id = self::get_producto_entrada_id();

		if ( $producto_id ) {
			$post = get_post( $producto_id );
			if ( $post && 'product' === $post->post_type && 'trash' !== $post->post_status ) {
				return; // Product still valid.
			}
		}

		// Product missing or trashed — recreate.
		CNES_Activator::crear_producto_entrada();
	}

	/**
	 * Format a numeric price into Chilean Pesos (CLP) style.
	 * e.g. 5000 -> $5.000
	 *
	 * @param float|int $price The numeric price.
	 * @return string Formatted price string.
	 */
	public static function format_price( $price ) {
		return '$' . number_format( (float) $price, 0, ',', '.' );
	}

	/**
	 * Get the 'sede' (location) of a room by its ID.
	 *
	 * @param int $sala_id Room ID.
	 * @return string|false Sede name or false if not found.
	 */
	public static function get_sede_by_sala_id( $sala_id ) {
		global $wpdb;
		$tabla = self::get_tabla( 'salas' );
		return $wpdb->get_var( $wpdb->prepare( "SELECT sede FROM {$tabla} WHERE id = %d", $sala_id ) );
	}
}
