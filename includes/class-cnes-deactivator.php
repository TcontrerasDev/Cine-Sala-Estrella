<?php
/**
 * Plugin deactivation: clean up scheduled events.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles tasks that run when the plugin is deactivated.
 * Does NOT delete tables or the WC product — that belongs in uninstall.php.
 */
class CNES_Deactivator {

	/**
	 * Run deactivation tasks.
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'cnes_limpiar_reservas_expiradas' );
		flush_rewrite_rules();
	}
}
