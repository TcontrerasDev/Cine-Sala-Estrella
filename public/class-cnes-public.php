<?php
/**
 * Public-facing functionality.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles frontend asset enqueueing.
 */
class CNES_Public {

	/**
	 * Enqueue public stylesheets.
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			'cnes-public',
			CNES_PLUGIN_URL . 'public/css/cnes-public.css',
			array(),
			CNES_VERSION
		);
	}

	/**
	 * Enqueue public scripts and expose base data via wp_localize_script.
	 */
	public function enqueue_scripts() {
		// Register a main script handle for the public side.
		wp_register_script( 'cnes-public', '', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'cnes-public' );

		wp_localize_script(
			'cnes-public',
			'cnesData',
			array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'nonce_reserva'       => wp_create_nonce( 'cnes_reserva_nonce' ),
				'producto_entrada_id' => get_option( 'cnes_producto_entrada_id' ),
			)
		);
	}

	/**
	 * Register public shortcodes.
	 */
	public function register_shortcodes() {
		add_shortcode( 'cnes_seleccion_asientos', array( $this, 'render_seleccion_asientos' ) );
	}

	/**
	 * Render seat selection page via shortcode.
	 * [cnes_seleccion_asientos]
	 */
	public function render_seleccion_asientos( $atts ) {
		$funcion_id = isset( $_GET['funcion_id'] ) ? absint( $_GET['funcion_id'] ) : 0;

		if ( ! $funcion_id ) {
			return '<div class="alert alert-warning">Por favor, selecciona una función desde la cartelera.</div>';
		}

		$data = self::get_seleccion_data( $funcion_id );
		
		if ( is_wp_error( $data ) ) {
			return '<div class="alert alert-danger">' . $data->get_error_message() . '</div>';
		}

		// Enqueue script and localize data specifically for this page.
		$this->enqueue_selection_assets( $data );

		// Render template.
		ob_start();
		include CNES_PLUGIN_DIR . 'templates/seleccion-asientos.php';
		return ob_get_clean();
	}

	/**
	 * Get all necessary data for seat selection.
	 */
	public static function get_seleccion_data( $funcion_id ) {
		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_salas     = CNES_Helpers::get_tabla( 'salas' );

		$funcion = $wpdb->get_row( $wpdb->prepare(
			"SELECT f.*, s.nombre as sala_nombre, s.sede, s.filas, s.columnas, s.layout
			 FROM {$tabla_funciones} f
			 JOIN {$tabla_salas} s ON f.sala_id = s.id
			 WHERE f.id = %d",
			$funcion_id
		) );

		if ( ! $funcion ) {
			return new WP_Error( 'cnes_error', 'La función solicitada no existe.' );
		}

		if ( 'en_venta' !== $funcion->estado ) {
			$msg = ( 'agotada' === $funcion->estado )
				? __( 'Esta función está agotada.', 'sala-estrella-manager' )
				: __( 'Esta función no está disponible para la venta.', 'sala-estrella-manager' );
			return new WP_Error( 'cnes_error', $msg );
		}

		// Check date
		$ahora = current_time( 'mysql' );
		$fecha_funcion = $funcion->fecha . ' ' . $funcion->hora_inicio;
		if ( $fecha_funcion < $ahora ) {
			return new WP_Error( 'cnes_error', 'Esta función ya ha comenzado o finalizado.' );
		}

		$pelicula = get_post( $funcion->pelicula_id );
		if ( ! $pelicula || 'pelicula' !== $pelicula->post_type ) {
			return new WP_Error( 'cnes_error', 'No se encontró la película asociada.' );
		}

		$reservas = new CNES_Reservas();
		$reservas->limpiar_expirados( $funcion_id );

		// Security Reset: Clean up any previously blocked seats by this session for this function
		$user_id    = get_current_user_id();
		$session_id = ( function_exists( 'WC' ) && WC()->session && WC()->session->has_session() ) ? WC()->session->get_customer_id() : '';
		$reservas->liberar_todos_asientos_funcion( $funcion_id, $user_id, $session_id );

		// Clear from cart as well to maintain consistency, just like cancel action
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item['cnes_data']['funcion_id'] ) && $cart_item['cnes_data']['funcion_id'] == $funcion_id ) {
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}
		}

		$estado_asientos = $reservas->obtener_estado_asientos( $funcion_id );

		// Mark which seats belong to current user
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );
		$query = $wpdb->prepare(
			"SELECT asiento_id FROM {$tabla_reservas} 
			 WHERE funcion_id = %d AND ( (user_id > 0 AND user_id = %d) OR (session_id = %s) )",
			$funcion_id, $user_id, $session_id
		);
		$mis_asientos = $wpdb->get_col( $query );

		foreach ( $estado_asientos as &$asiento ) {
			$asiento['es_mio'] = in_array( $asiento['id'], $mis_asientos );
		}

		return array(
			'funcion'          => $funcion,
			'pelicula'         => $pelicula,
			'asientos_estado'  => $estado_asientos,
		);
	}

	/**
	 * Enqueue and localize assets for seat selection.
	 */
	public function enqueue_selection_assets( $data ) {
		// Enqueue the script
		wp_enqueue_script(
			'cnes-seleccion-asientos',
			CNES_PLUGIN_URL . 'public/js/cnes-seleccion-asientos.js',
			array(),
			CNES_VERSION,
			true
		);

		// Prepare data for JS
		$funcion = $data['funcion'];
		$pelicula = $data['pelicula'];
		
		$js_data = array(
			'ajax_url'            => admin_url( 'admin-ajax.php' ),
			'nonce'               => wp_create_nonce( 'cnes_reserva_nonce' ),
			'funcion_id'          => $funcion->id,
			'producto_entrada_id' => CNES_Helpers::get_producto_entrada_id(),
			'precio_normal'       => $funcion->precio_normal,
			'precio_vip'          => $funcion->precio_vip,
			'sala'                => array(
				'id'       => $funcion->sala_id,
				'nombre'   => $funcion->sala_nombre,
				'filas'    => (int)$funcion->filas,
				'columnas' => (int)$funcion->columnas,
				'layout'   => json_decode( $funcion->layout, true ),
			),
			'asientos_estado'     => $data['asientos_estado'],
			'pelicula'            => array(
				'titulo' => $pelicula->post_title,
				'poster' => get_the_post_thumbnail_url( $pelicula->ID, 'medium' ),
			),
			'funcion_info'        => array(
				'fecha'          => date_i18n( 'j \d\e F, Y', strtotime( $funcion->fecha ) ),
				'hora'           => date( 'H:i', strtotime( $funcion->hora_inicio ) ),
				'sede'           => $funcion->sede,
				'sede_label'     => ( 'punta_arenas' === $funcion->sede ) ? 'Punta Arenas' : 'Puerto Natales',
				'formato_idioma' => $funcion->formato_idioma,
				'formato_badge'  => ( 'subtitulada' === $funcion->formato_idioma ) ? 'SUB' : 'DOB',
			),
			'checkout_url'        => wc_get_checkout_url(),
			'home_url'            => home_url( '/' ),
			'i18n'                => array(
				'error_bloqueo'   => __( 'No se pudo reservar el asiento.', 'sala-estrella-manager' ),
				'error_carrito'   => __( 'Error al agregar al carrito.', 'sala-estrella-manager' ),
				'sesion_expirada' => __( 'Tu sesión ha expirado.', 'sala-estrella-manager' ),
			),
		);

		wp_localize_script( 'cnes-seleccion-asientos', 'cnesAsientos', $js_data );
	}

	/**
	 * Obtiene las funciones activas de una película específica.
	 * 
	 * @param int    $pelicula_id ID del CPT pelicula.
	 * @param string $sede        Opcional. Filtrar por sede.
	 * @return array Lista de objetos de funciones.
	 */
	public static function get_funciones_por_pelicula( $pelicula_id, $sede = '' ) {
		if ( ! $pelicula_id ) return array();

		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_salas     = CNES_Helpers::get_tabla( 'salas' );
		
		$hoy = current_time( 'Y-m-d' );
		$ahora = current_time( 'H:i:s' );

		$where = $wpdb->prepare(
			"f.pelicula_id = %d
			 AND f.estado IN ('en_venta', 'agotada')
			 AND (f.fecha > %s OR (f.fecha = %s AND f.hora_inicio > %s))",
			$pelicula_id, $hoy, $hoy, $ahora
		);

		if ( ! empty( $sede ) ) {
			$where .= $wpdb->prepare( " AND s.sede = %s", $sede );
		}

		$query = "SELECT f.*, s.nombre as sala_nombre, s.sede 
				  FROM {$tabla_funciones} f
				  JOIN {$tabla_salas} s ON f.sala_id = s.id
				  WHERE {$where}
				  ORDER BY f.fecha ASC, f.hora_inicio ASC";

		return $wpdb->get_results( $query );
	}

	/**
	 * Returns HTML badge for a given formato_idioma value.
	 *
	 * @param string $formato_idioma 'doblada' or 'subtitulada'.
	 * @return string Escaped HTML.
	 */
	public static function get_formato_badge( $formato_idioma ) {
		if ( 'subtitulada' === $formato_idioma ) {
			return '<span class="cnes-badge cnes-badge-sub">SUB</span>';
		}
		return '<span class="cnes-badge cnes-badge-dob">DOB</span>';
	}

	/**
	 * Returns HTML badge + disabled state info for a función's estado.
	 * Returns an array: ['badge' => HTML, 'agotada' => bool].
	 *
	 * @param string $estado Función estado value.
	 * @return array
	 */
	public static function get_funcion_estado_info( $estado ) {
		$agotada = ( 'agotada' === $estado );
		$badge   = $agotada
			? '<span class="cnes-badge cnes-badge-agotada">' . esc_html__( 'AGOTADA', 'sala-estrella-manager' ) . '</span>'
			: '';
		return array( 'badge' => $badge, 'agotada' => $agotada );
	}

	/**
	 * Obtiene las funciones de una película agrupadas por sede y fecha.
	 * 
	 * @param int $pelicula_id ID del CPT pelicula.
	 * @return array Array asociativo [ 'Sede' => [ 'YYYY-MM-DD' => [ objeto, ... ], ... ], ... ]
	 */
	public static function get_funciones_por_pelicula_agrupadas( $pelicula_id ) {
		$funciones = self::get_funciones_por_pelicula( $pelicula_id );
		$agrupadas = array();

		foreach ( $funciones as $func ) {
			$sede  = $func->sede;
			$fecha = $func->fecha;

			if ( ! isset( $agrupadas[ $sede ] ) ) {
				$agrupadas[ $sede ] = array();
			}
			if ( ! isset( $agrupadas[ $sede ][ $fecha ] ) ) {
				$agrupadas[ $sede ][ $fecha ] = array();
			}
			$agrupadas[ $sede ][ $fecha ][] = $func;
		}

		return $agrupadas;
	}
}
