<?php
/**
 * AJAX Handlers for seat selection and booking.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles all AJAX requests from the seat selection frontend.
 */
class CNES_Ajax {

	/**
	 * Register AJAX hooks.
	 */
	public function __construct() {
		$actions = array(
			'cnes_bloquear_asiento',
			'cnes_liberar_asiento',
			'cnes_liberar_asientos_sesion',
			'cnes_cancelar_seleccion',
			'cnes_get_asientos',
			'cnes_agregar_al_carrito',
			'cnes_verificar_disponibilidad',
		);

		foreach ( $actions as $action ) {
			add_action( "wp_ajax_{$action}", array( $this, $action ) );
			add_action( "wp_ajax_nopriv_{$action}", array( $this, $action ) );
		}
	}

	/**
	 * Block a seat temporarily.
	 */
	public function cnes_bloquear_asiento() {
		check_ajax_referer( 'cnes_reserva_nonce', 'nonce' );

		$funcion_id = filter_input( INPUT_POST, 'funcion_id', FILTER_SANITIZE_NUMBER_INT );
		$asiento_id = filter_input( INPUT_POST, 'asiento_id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $funcion_id || ! $asiento_id ) {
			wp_send_json_error( array( 'message' => __( 'ID de función o asiento inválido.', 'sala-estrella-manager' ) ) );
		}

		$user_id    = get_current_user_id();
		$session_id = $this->ensure_wc_session();

		$reservas = new CNES_Reservas();
		$result   = $reservas->bloquear_asiento( $funcion_id, $asiento_id, $user_id, $session_id );

		if ( $result['success'] ) {
			$result['redirect_url'] = home_url( '/compra-final/' );
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}

	/**
	 * Release a temporarily blocked seat.
	 */
	public function cnes_liberar_asiento() {
		check_ajax_referer( 'cnes_reserva_nonce', 'nonce' );

		$funcion_id = filter_input( INPUT_POST, 'funcion_id', FILTER_SANITIZE_NUMBER_INT );
		$asiento_id = filter_input( INPUT_POST, 'asiento_id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $funcion_id || ! $asiento_id ) {
			wp_send_json_error( array( 'message' => __( 'ID de función o asiento inválido.', 'sala-estrella-manager' ) ) );
		}

		$user_id    = get_current_user_id();
		$session_id = $this->ensure_wc_session();

		$reservas = new CNES_Reservas();
		$success  = $reservas->liberar_asiento( $funcion_id, $asiento_id, $user_id, $session_id );

		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'Asiento liberado.', 'sala-estrella-manager' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'No se pudo liberar el asiento.', 'sala-estrella-manager' ) ) );
		}
	}

	/**
	 * Release all seats for the current session/user.
	 */
	public function cnes_liberar_asientos_sesion() {
		// Use a looser check or no check if called via navigator.sendBeacon
		// which sometimes can't send headers perfectly, but we try to send the nonce.
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_SANITIZE_STRING );
		if ( ! wp_verify_nonce( $nonce, 'cnes_reserva_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Nonce invalid.' ) );
		}

		$user_id    = get_current_user_id();
		$session_id = $this->ensure_wc_session();

		$reservas = new CNES_Reservas();
		$reservas->liberar_todos_asientos( $user_id, $session_id );

		wp_send_json_success( array( 'message' => __( 'Asientos de sesión liberados.', 'sala-estrella-manager' ) ) );
	}

	/**
	 * Cancel current selection for a function.
	 */
	public function cnes_cancelar_seleccion() {
		check_ajax_referer( 'cnes_reserva_nonce', 'nonce' );

		$funcion_id = filter_input( INPUT_POST, 'funcion_id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $funcion_id ) {
			wp_send_json_error( array( 'message' => __( 'ID de función inválido.', 'sala-estrella-manager' ) ) );
		}

		$user_id    = get_current_user_id();
		$session_id = $this->ensure_wc_session();

		$reservas = new CNES_Reservas();
		$success  = $reservas->liberar_todos_asientos_funcion( $funcion_id, $user_id, $session_id );

		// Also remove items from cart for this function if they exist.
		if ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item['cnes_data']['funcion_id'] ) && $cart_item['cnes_data']['funcion_id'] == $funcion_id ) {
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			}
		}

		if ( $success ) {
			wp_send_json_success( array( 'message' => __( 'Selección cancelada.', 'sala-estrella-manager' ) ) );
		} else {
			// Even if no rows were deleted, we treat it as success if the goal is to have 0 seats.
			wp_send_json_success( array( 'message' => __( 'No había asientos seleccionados.', 'sala-estrella-manager' ) ) );
		}
	}

	/**
	 * Get current seat status for a function.
	 */
	public function cnes_get_asientos() {
		check_ajax_referer( 'cnes_reserva_nonce', 'nonce' );

		$funcion_id = filter_input( INPUT_POST, 'funcion_id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $funcion_id ) {
			wp_send_json_error( array( 'message' => __( 'ID de función inválido.', 'sala-estrella-manager' ) ) );
		}

		$user_id    = get_current_user_id();
		$session_id = $this->ensure_wc_session();

		$reservas = new CNES_Reservas();
		$asientos = $reservas->obtener_estado_asientos( $funcion_id );

		// Mark which seats belong to the current user/session.
		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );
		
		$query = $wpdb->prepare(
			"SELECT asiento_id FROM {$tabla_reservas} 
			 WHERE funcion_id = %d AND ( (user_id > 0 AND user_id = %d) OR (session_id = %s) )",
			$funcion_id,
			$user_id,
			$session_id
		);
		$mis_asientos = $wpdb->get_col( $query );

		foreach ( $asientos as &$asiento ) {
			if ( in_array( $asiento['id'], $mis_asientos ) ) {
				$asiento['es_mio'] = true;
			} else {
				$asiento['es_mio'] = false;
			}
		}

		wp_send_json_success( array( 'asientos' => $asientos ) );
	}

	/**
	 * Add selected seats to WooCommerce cart.
	 */
	public function cnes_agregar_al_carrito() {
		check_ajax_referer( 'cnes_reserva_nonce', 'nonce' );

		$funcion_id    = filter_input( INPUT_POST, 'funcion_id', FILTER_SANITIZE_NUMBER_INT );
		$asientos      = filter_input( INPUT_POST, 'asientos', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$billing_email = isset( $_POST['cnes_billing_email'] ) ? sanitize_email( wp_unslash( $_POST['cnes_billing_email'] ) ) : '';

		if ( ! $funcion_id || empty( $asientos ) ) {
			wp_send_json_error( array( 'message' => __( 'Selección inválida.', 'sala-estrella-manager' ) ) );
		}

		$product_id = CNES_Helpers::get_producto_entrada_id();
		if ( ! $product_id ) {
			wp_send_json_error( array( 'message' => __( 'El producto de entrada no está configurado.', 'sala-estrella-manager' ) ) );
		}

		// Ensure WooCommerce is available.
		if ( ! function_exists( 'WC' ) ) {
			wp_send_json_error( array( 'message' => __( 'WooCommerce no está activo.', 'sala-estrella-manager' ) ) );
		}

		$user_id    = get_current_user_id();
		$session_id = $this->ensure_wc_session();

		// Purge any expired rows so the count check below is accurate.
		$reservas_cleanup = new CNES_Reservas();
		$reservas_cleanup->limpiar_expirados( $funcion_id );

		// Validate that these seats are indeed blocked by THIS user.
		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );
		$tabla_asientos = CNES_Helpers::get_tabla( 'asientos' );
		
		$ids_placeholder = implode( ',', array_fill( 0, count( $asientos ), '%d' ) );
		$query = $wpdb->prepare(
			"SELECT r.asiento_id, a.tipo 
			 FROM {$tabla_reservas} r
			 JOIN {$tabla_asientos} a ON r.asiento_id = a.id
			 WHERE r.funcion_id = %d 
			 AND r.asiento_id IN ($ids_placeholder)
			 AND ( (r.user_id > 0 AND r.user_id = %d) OR (r.session_id = %s) )
			 AND r.estado = 'seleccionado'",
			array_merge( array( $funcion_id ), $asientos, array( $user_id, $session_id ) )
		);
		
		$asientos_validados = $wpdb->get_results( $query, ARRAY_A );

		if ( count( $asientos_validados ) !== count( $asientos ) ) {
			wp_send_json_error( array( 'message' => __( 'Algunos asientos ya no están reservados para ti. Por favor, revisa tu selección.', 'sala-estrella-manager' ) ) );
		}

		// Group seats by type for CNES_WooCommerce compatibility.
		$by_type = array();
		foreach ( $asientos_validados as $row ) {
			$by_type[ $row['tipo'] ][] = $row['asiento_id'];
		}

		// Empty cart once before the loop so validate_add_to_cart doesn't wipe
		// an already-added seat-type group when processing the next one.
		WC()->cart->empty_cart();

		// Add to cart by type groups. $_POST values are read by add_cart_item_data
		// and validate_add_to_cart via the $_POST superglobal (not filter_input).
		foreach ( $by_type as $tipo => $ids ) {
			$_POST['cnes_funcion_id']   = $funcion_id;
			$_POST['cnes_asientos']     = $ids;
			$_POST['cnes_tipo_asiento'] = $tipo;

			// Quantity = 1; price is set to total_precio (sum of all seats) in before_calculate_totals.
			$cart_item_key = WC()->cart->add_to_cart( $product_id, 1 );

			if ( ! $cart_item_key ) {
				wp_send_json_error( array( 'message' => __( 'No se pudo agregar al carrito.', 'sala-estrella-manager' ) ) );
			}
		}

		if ( $billing_email && is_email( $billing_email ) && WC()->customer ) {
			WC()->customer->set_billing_email( $billing_email );
			WC()->customer->save();
		}

		$referer  = wp_get_referer();
		$back_url = $referer ? add_query_arg( 'funcion_id', $funcion_id, $referer ) : '';

		wp_send_json_success( array(
			'redirect_url' => home_url( '/compra-final/' ),
			'back_url'     => $back_url,
		) );
	}

	/**
	 * Clean expired seats for a function and return updated seat map.
	 * Called by JS polling every 30 s.
	 */
	public function cnes_verificar_disponibilidad() {
		check_ajax_referer( 'cnes_reserva_nonce', 'nonce' );

		$funcion_id = filter_input( INPUT_POST, 'funcion_id', FILTER_SANITIZE_NUMBER_INT );

		if ( ! $funcion_id ) {
			wp_send_json_error( array( 'message' => __( 'ID de función inválido.', 'sala-estrella-manager' ) ) );
		}

		$reservas = new CNES_Reservas();
		$reservas->limpiar_expirados( $funcion_id );

		$asientos = $reservas->obtener_estado_asientos( $funcion_id );

		$user_id    = get_current_user_id();
		$session_id = $this->ensure_wc_session();

		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );
		$mis_asientos   = $wpdb->get_col( $wpdb->prepare(
			"SELECT asiento_id FROM {$tabla_reservas}
			 WHERE funcion_id = %d AND ( (user_id > 0 AND user_id = %d) OR (session_id = %s) )",
			$funcion_id, $user_id, $session_id
		) );

		foreach ( $asientos as &$asiento ) {
			$asiento['es_mio'] = in_array( $asiento['id'], $mis_asientos );
		}

		wp_send_json_success( array( 'asientos' => $asientos ) );
	}

	/**
	 * Ensure WooCommerce session is active and return session ID.
	 */
	private function ensure_wc_session() {
		if ( ! WC()->session ) {
			return '';
		}
		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}
		return WC()->session->get_customer_id();
	}
}
