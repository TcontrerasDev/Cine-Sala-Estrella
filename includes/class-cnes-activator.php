<?php
/**
 * Plugin activation: create DB tables and generic WC product.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles all tasks that run when the plugin is activated.
 */
class CNES_Activator {

	/** DB schema version stored in wp_options. */
	const DB_VERSION = '1.1.0';

	/**
	 * Run DB migrations when the installed version is behind DB_VERSION.
	 */
	public static function maybe_upgrade() {
		$installed = get_option( 'cnes_db_version', '0' );
		if ( version_compare( $installed, self::DB_VERSION, '<' ) ) {
			self::crear_tablas();
			update_option( 'cnes_db_version', self::DB_VERSION );
		}
	}

	/**
	 * Run activation tasks.
	 */
	public static function activate() {
		self::crear_tablas();
		self::crear_producto_entrada();
		self::insertar_terminos_predeterminados();
		self::programar_cron();
		flush_rewrite_rules();
	}

	/**
	 * Schedule the cleanup cron job.
	 */
	private static function programar_cron() {
		if ( ! wp_next_scheduled( 'cnes_limpiar_reservas_expiradas' ) ) {
			wp_schedule_event( time(), 'cnes_cinco_minutos', 'cnes_limpiar_reservas_expiradas' );
		}
		if ( ! wp_next_scheduled( 'cnes_finalizar_funciones' ) ) {
			wp_schedule_event( time(), 'cnes_cinco_minutos', 'cnes_finalizar_funciones' );
		}
	}

	/**
	 * Insert default terms for taxonomies.
	 */
	private static function insertar_terminos_predeterminados() {
		// We need to ensure taxonomies are registered before inserting terms
		$peliculas = new CNES_Peliculas();
		$peliculas->register();
		$peliculas->insert_default_terms();
	}

	/**
	 * Create or update plugin custom tables using dbDelta.
	 */
	private static function crear_tablas() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// -- cnes_salas ---------------------------------------------------
		$tabla_salas = CNES_Helpers::get_tabla( 'salas' );
		$sql_salas   = "CREATE TABLE {$tabla_salas} (
			id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			nombre     VARCHAR(100) NOT NULL,
			sede       VARCHAR(50) NOT NULL,
			layout     LONGTEXT NOT NULL,
			filas      INT UNSIGNED NOT NULL DEFAULT 0,
			columnas   INT UNSIGNED NOT NULL DEFAULT 0,
			capacidad  INT UNSIGNED NOT NULL DEFAULT 0,
			estado     VARCHAR(20) NOT NULL DEFAULT 'activa',
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) {$charset_collate};";

		// -- cnes_asientos ------------------------------------------------
		$tabla_asientos = CNES_Helpers::get_tabla( 'asientos' );
		$sql_asientos   = "CREATE TABLE {$tabla_asientos} (
			id      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			sala_id BIGINT UNSIGNED NOT NULL,
			fila    VARCHAR(5) NOT NULL,
			numero  INT UNSIGNED NOT NULL,
			tipo    VARCHAR(20) NOT NULL DEFAULT 'normal',
			activo  TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (id),
			KEY sala_id (sala_id),
			UNIQUE KEY sala_asiento (sala_id, fila, numero)
		) {$charset_collate};";

		// -- cnes_funciones -----------------------------------------------
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$sql_funciones   = "CREATE TABLE {$tabla_funciones} (
			id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			pelicula_id     BIGINT UNSIGNED NOT NULL,
			sala_id         BIGINT UNSIGNED NOT NULL,
			fecha           DATE NOT NULL,
			hora_inicio     TIME NOT NULL,
			precio_normal   DECIMAL(10,0) NOT NULL DEFAULT 0,
			precio_vip      DECIMAL(10,0) NOT NULL DEFAULT 0,
			formato_idioma  VARCHAR(20) NOT NULL DEFAULT 'doblada',
			estado          VARCHAR(20) NOT NULL DEFAULT 'programada',
			created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY pelicula_id (pelicula_id),
			KEY sala_id (sala_id),
			KEY fecha_hora (fecha, hora_inicio),
			KEY estado (estado)
		) {$charset_collate};";

		// -- cnes_reservas ------------------------------------------------
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );
		$sql_reservas   = "CREATE TABLE {$tabla_reservas} (
			id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			funcion_id  BIGINT UNSIGNED NOT NULL,
			asiento_id  BIGINT UNSIGNED NOT NULL,
			user_id     BIGINT UNSIGNED DEFAULT NULL,
			session_id  VARCHAR(100) DEFAULT NULL,
			estado      VARCHAR(20) NOT NULL DEFAULT 'seleccionado',
			wc_order_id BIGINT UNSIGNED DEFAULT NULL,
			created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			expires_at  DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY funcion_asiento (funcion_id, asiento_id),
			KEY user_id (user_id),
			KEY session_id (session_id),
			KEY estado (estado),
			KEY expires_at (expires_at),
			KEY wc_order_id (wc_order_id)
		) {$charset_collate};";

		dbDelta( $sql_salas );
		dbDelta( $sql_asientos );
		dbDelta( $sql_funciones );
		dbDelta( $sql_reservas );

		update_option( 'cnes_db_version', self::DB_VERSION );
	}

	/**
	 * Create the generic "Entrada de Cine" WooCommerce product.
	 * Skips creation if a valid product already exists.
	 */
	public static function crear_producto_entrada() {
		$existing_id = get_option( 'cnes_producto_entrada_id', false );

		if ( $existing_id ) {
			$post = get_post( $existing_id );
			if ( $post && 'product' === $post->post_type && 'trash' !== $post->post_status ) {
				return; // Already exists and is valid.
			}
		}

		if ( ! class_exists( 'WC_Product_Simple' ) ) {
			return;
		}

		$producto = new WC_Product_Simple();
		$producto->set_name( __( 'Entrada de Cine', 'sala-estrella-manager' ) );
		$producto->set_status( 'publish' );
		$producto->set_catalog_visibility( 'hidden' );
		$producto->set_price( 0 );
		$producto->set_regular_price( 0 );
		$producto->set_virtual( true );
		$producto->set_sold_individually( false );
		$producto->set_manage_stock( false );
		$producto->set_description(
			__( 'Producto interno del sistema de reserva de asientos. No eliminar.', 'sala-estrella-manager' )
		);
		$producto->set_short_description(
			__( 'Entrada para funciones de Cine Sala Estrella.', 'sala-estrella-manager' )
		);
		$producto->save();

		update_option( 'cnes_producto_entrada_id', $producto->get_id() );
	}
}
