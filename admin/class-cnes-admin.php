<?php
/**
 * Admin-specific functionality.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles admin menus and asset enqueueing.
 */
class CNES_Admin {

	/**
	 * Enqueue admin stylesheets.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'cnes-salas' ) !== false ) {
			wp_enqueue_style( 'cnes-admin-salas', CNES_PLUGIN_URL . 'admin/css/cnes-admin-salas.css', array(), CNES_VERSION );
		}

		if ( strpos( $hook_suffix, 'cnes-funciones' ) !== false ) {
			wp_enqueue_style( 'cnes-admin-funciones', CNES_PLUGIN_URL . 'admin/css/cnes-admin-funciones.css', array(), CNES_VERSION );
		}

		if ( strpos( $hook_suffix, 'cnes-' ) !== false ) {
			wp_enqueue_style( 'cnes-public', CNES_PLUGIN_URL . 'public/css/cnes-public.css', array(), CNES_VERSION );
		}
	}

	/**
	 * Enqueue admin scripts.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( strpos( $hook_suffix, 'cnes-salas' ) !== false ) {
			wp_enqueue_script( 'cnes-editor-salas', CNES_PLUGIN_URL . 'admin/js/cnes-editor-salas.js', array( 'jquery' ), CNES_VERSION, true );
			wp_localize_script( 'cnes-editor-salas', 'cnes_admin', array(
				'nonce' => wp_create_nonce( 'cnes_admin_nonce' ),
			) );
		}

		if ( strpos( $hook_suffix, 'cnes-funciones' ) !== false ) {
			wp_enqueue_script( 'cnes-admin-funciones', CNES_PLUGIN_URL . 'admin/js/cnes-admin-funciones.js', array( 'jquery' ), CNES_VERSION, true );
			wp_localize_script( 'cnes-admin-funciones', 'cnesFunciones', array(
				'nonce'            => wp_create_nonce( 'cnes_admin_nonce' ),
				'confirmDeleteMsg' => __( '¿Estás seguro de eliminar %d función(es) seleccionada(s)? Esta acción también eliminará las reservas asociadas y no puede deshacerse.', 'sala-estrella-manager' ),
				'deletingText'     => __( 'Eliminando…', 'sala-estrella-manager' ),
				'deleteText'       => __( 'Eliminar seleccionadas', 'sala-estrella-manager' ),
				'errorText'        => __( 'Error al eliminar. Por favor, recarga la página e intenta nuevamente.', 'sala-estrella-manager' ),
			) );
		}
	}

	/**
	 * Register the top-level admin menu and submenus.
	 */
	public function add_admin_menu() {
		$capability = 'manage_options';
		$slug       = 'sala-estrella-manager';

		add_menu_page(
			__( 'Sala Estrella', 'sala-estrella-manager' ),
			__( 'Sala Estrella', 'sala-estrella-manager' ),
			$capability,
			$slug,
			array( 'CNES_Admin_Dashboard', 'render_page' ),
			'dashicons-tickets-alt',
			26
		);

		add_submenu_page(
			$slug,
			__( 'Dashboard', 'sala-estrella-manager' ),
			__( 'Dashboard', 'sala-estrella-manager' ),
			$capability,
			$slug,
			array( 'CNES_Admin_Dashboard', 'render_page' )
		);

		$admin_salas = new CNES_Admin_Salas();
		add_submenu_page(
			$slug,
			__( 'Salas', 'sala-estrella-manager' ),
			__( 'Salas', 'sala-estrella-manager' ),
			$capability,
			'cnes-salas',
			array( $admin_salas, 'render_salas_page' )
		);

		$admin_funciones = new CNES_Admin_Funciones();
		add_submenu_page(
			$slug,
			__( 'Funciones', 'sala-estrella-manager' ),
			__( 'Funciones', 'sala-estrella-manager' ),
			$capability,
			'cnes-funciones',
			array( $admin_funciones, 'render_funciones_page' )
		);
	}
}
