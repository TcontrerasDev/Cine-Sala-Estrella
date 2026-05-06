<?php
/**
 * WooCommerce integration for seat bookings.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles all WooCommerce hooks and logic.
 */
class CNES_WooCommerce {

	/**
	 * Attach movie and seat metadata to cart item.
	 */
	public function add_cart_item_data( $cart_item_data, $product_id, $variation_id ) {
		if ( $product_id != CNES_Helpers::get_producto_entrada_id() ) {
			return $cart_item_data;
		}

		// filter_input(INPUT_POST) reads raw SAPI input and ignores $_POST modifications made
		// by cnes_agregar_al_carrito before calling add_to_cart(), so we read $_POST directly.
		$funcion_id = isset( $_POST['cnes_funcion_id'] ) ? absint( $_POST['cnes_funcion_id'] ) : 0;
		$asientos   = isset( $_POST['cnes_asientos'] ) && is_array( $_POST['cnes_asientos'] ) ? array_map( 'absint', $_POST['cnes_asientos'] ) : array();
		$tipo       = isset( $_POST['cnes_tipo_asiento'] ) ? sanitize_text_field( wp_unslash( $_POST['cnes_tipo_asiento'] ) ) : '';

		if ( ! $funcion_id || ! $asientos || ! $tipo ) {
			return $cart_item_data;
		}

		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_salas     = CNES_Helpers::get_tabla( 'salas' );

		$funcion = $wpdb->get_row( $wpdb->prepare(
			"SELECT f.*, p.post_title as pelicula_nombre, s.nombre as sala_nombre, s.sede 
			 FROM {$tabla_funciones} f
			 JOIN {$wpdb->posts} p ON f.pelicula_id = p.ID
			 JOIN {$tabla_salas} s ON f.sala_id = s.id
			 WHERE f.id = %d",
			$funcion_id
		) );

		if ( ! $funcion ) {
			return $cart_item_data;
		}

		$precio = ( 'vip' === $tipo ) ? $funcion->precio_vip : $funcion->precio_normal;
		$total_precio = $precio * count( $asientos );
		$asientos_nombres = $this->get_asientos_compactos( $asientos );

		$cart_item_data['cnes_data'] = array(
			'funcion_id'       => (int) $funcion_id,
			'pelicula_id'      => (int) $funcion->pelicula_id,
			'pelicula_nombre'  => $funcion->pelicula_nombre,
			'sala_id'          => $funcion->sala_id,
			'sala_nombre'      => $funcion->sala_nombre,
			'sede'             => $funcion->sede,
			'asientos'         => $asientos,
			'asientos_nombres' => $asientos_nombres,
			'tipo_asiento'     => $tipo,
			'fecha'            => $funcion->fecha,
			'hora'             => $funcion->hora_inicio,
			'formato_idioma'   => $funcion->formato_idioma,
			'precio_unitario'  => $precio,
			'total_precio'     => $total_precio,
			'unique_key'       => md5( 'cnes_' . $funcion_id . '_' . $tipo . '_' . implode( ',', $asientos ) ),
		);

		// Also inject at top level for easier access if needed
		$cart_item_data['pelicula_id']      = (int) $funcion->pelicula_id;
		$cart_item_data['funcion_id']       = (int) $funcion_id;
		$cart_item_data['asientos_nombres'] = $asientos_nombres;
		$cart_item_data['total_precio']     = $total_precio;

		return $cart_item_data;
	}

	/**
	 * Validate if seats are still available before adding to cart.
	 */
	public function validate_add_to_cart( $passed, $product_id, $quantity, $variation_id = '', $args = array() ) {
		if ( $product_id != CNES_Helpers::get_producto_entrada_id() ) {
			return $passed;
		}

		// Cart is emptied once in cnes_agregar_al_carrito before the add_to_cart loop.
		// Do NOT empty here — this hook fires per add_to_cart() call and would wipe
		// already-added seat-type groups on multi-type selections (normal + VIP).

		$funcion_id = isset( $_POST['cnes_funcion_id'] ) ? absint( $_POST['cnes_funcion_id'] ) : 0;
		$asientos   = isset( $_POST['cnes_asientos'] ) && is_array( $_POST['cnes_asientos'] ) ? array_map( 'absint', $_POST['cnes_asientos'] ) : array();

		if ( ! $funcion_id || ! $asientos ) {
			wc_add_notice( __( 'Error en la selección de función o asientos.', 'sala-estrella-manager' ), 'error' );
			return false;
		}

		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );

		// 1. Check function status.
		$estado_funcion = $wpdb->get_var( $wpdb->prepare(
			"SELECT estado FROM {$tabla_funciones} WHERE id = %d",
			$funcion_id
		) );

		if ( 'en_venta' !== $estado_funcion ) {
			wc_add_notice( __( 'Esta función ya no está disponible para la venta.', 'sala-estrella-manager' ), 'error' );
			return false;
		}

		// 2. Check if seats are free (not paid or selected by someone else).
		$user_id    = get_current_user_id();
		$session_id = WC()->session ? WC()->session->get_customer_id() : '';

		foreach ( $asientos as $asiento_id ) {
			$reserva = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$tabla_reservas} WHERE funcion_id = %d AND asiento_id = %d",
				$funcion_id,
				$asiento_id
			) );

			if ( $reserva ) {
				// If it's selected by someone else or already paid.
				$is_mine = ( $reserva->user_id && $reserva->user_id == $user_id ) || ( $reserva->session_id && $reserva->session_id == $session_id );
				
				if ( ! $is_mine || 'pagado' === $reserva->estado ) {
					wc_add_notice( __( 'Uno o más asientos ya no están disponibles.', 'sala-estrella-manager' ), 'error' );
					return false;
				}
			}
		}

		return $passed;
	}

	/**
	 * Force unique cart item key per function and seat type.
	 */
	public function cart_id( $cart_id, $product_id, $variation_id, $cart_item_data ) {
		if ( isset( $cart_item_data['cnes_data']['unique_key'] ) ) {
			return $cart_item_data['cnes_data']['unique_key'];
		}
		return $cart_id;
	}

	/**
	 * Set dynamic price equal to the sum of all selected seats for the item.
	 * Quantity is always 1; total_precio already holds the aggregate.
	 */
	public function before_calculate_totals( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['cnes_data'] ) ) {
				$cart_item['data']->set_price( $cart_item['cnes_data']['total_precio'] );
			}
		}
	}

	/**
	 * Display movie name and room instead of generic product name.
	 */
	public function cart_item_name( $name, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['cnes_data'] ) ) {
			$data = $cart_item['cnes_data'];
			return sprintf( '%s — %s', $data['pelicula_nombre'], $data['sala_nombre'] );
		}
		return $name;
	}

	/**
	 * Show legible metadata in cart and checkout.
	 */
	public function get_item_data( $item_data, $cart_item ) {
		if ( isset( $cart_item['cnes_data'] ) ) {
			$data = $cart_item['cnes_data'];

			$item_data[] = array( 'name' => __( 'Sede', 'sala-estrella-manager' ), 'value' => $data['sede'] );
			$item_data[] = array( 'name' => __( 'Fecha', 'sala-estrella-manager' ), 'value' => date_i18n( get_option( 'date_format' ), strtotime( $data['fecha'] ) ) );
			$item_data[] = array( 'name' => __( 'Hora', 'sala-estrella-manager' ), 'value' => date( 'H:i', strtotime( $data['hora'] ) ) . ' hrs' );

			$formato_label = ( 'subtitulada' === ( $data['formato_idioma'] ?? '' ) ) ? __( 'Subtitulada (SUB)', 'sala-estrella-manager' ) : __( 'Doblada (DOB)', 'sala-estrella-manager' );
			$item_data[] = array( 'name' => __( 'Formato', 'sala-estrella-manager' ), 'value' => $formato_label );

			$asientos_nombres = $this->get_asientos_legibles( $data['asientos'] );
			$item_data[] = array( 'name' => __( 'Asientos', 'sala-estrella-manager' ), 'value' => $asientos_nombres );
			$item_data[] = array( 'name' => __( 'Tipo', 'sala-estrella-manager' ), 'value' => ucfirst( $data['tipo_asiento'] ) );
		}
		return $item_data;
	}

	/**
	 * Helper to get legible seat names (Row X - Seat Y).
	 */
	private function get_asientos_legibles( $asientos_ids ) {
		global $wpdb;
		$tabla_asientos = CNES_Helpers::get_tabla( 'asientos' );
		
		$ids = implode( ',', array_map( 'intval', $asientos_ids ) );
		$results = $wpdb->get_results( "SELECT sala_id, fila, numero FROM {$tabla_asientos} WHERE id IN ($ids) ORDER BY fila, numero" );

		$nombres = array();
		foreach ( $results as $row ) {
			// Calcular número real (descontando pasillos anteriores en la misma fila)
			$num_pasillos = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla_asientos} WHERE sala_id = %d AND fila = %s AND tipo = 'pasillo' AND numero < %d", $row->sala_id, $row->fila, $row->numero ) );
			$real_numero = $row->numero - $num_pasillos;
			$nombres[] = sprintf( 'Fila %s — %d', $row->fila, $real_numero );
		}

		return implode( ', ', $nombres );
	}

	/**
	 * Save metadata to order line item.
	 */
	public function checkout_create_order_line_item( $item, $cart_item_key, $values, $order ) {
		if ( isset( $values['cnes_data'] ) ) {
			$data = $values['cnes_data'];
			$item->add_meta_data( '_cnes_funcion_id', $data['funcion_id'] );
			$item->add_meta_data( '_cnes_pelicula_id', $data['pelicula_id'] );
			$item->add_meta_data( '_cnes_sala_id', $data['sala_id'] );
			$item->add_meta_data( '_cnes_sede', $data['sede'] );
			$item->add_meta_data( '_cnes_fecha', $data['fecha'] );
			$item->add_meta_data( '_cnes_hora', $data['hora'] );
			$item->add_meta_data( '_cnes_formato_idioma', $data['formato_idioma'] ?? 'doblada' );
			$item->add_meta_data( '_cnes_tipo_asiento', $data['tipo_asiento'] );
			
			// Get detailed seat info for JSON storage.
			global $wpdb;
			$tabla_asientos = CNES_Helpers::get_tabla( 'asientos' );
			$ids = implode( ',', array_map( 'intval', $data['asientos'] ) );
			$asientos_info = $wpdb->get_results( "SELECT id, sala_id, fila, numero, tipo FROM {$tabla_asientos} WHERE id IN ($ids)", ARRAY_A );
			
			// Replace numero with the real visual number
			foreach ( $asientos_info as &$info ) {
				$num_pasillos = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla_asientos} WHERE sala_id = %d AND fila = %s AND tipo = 'pasillo' AND numero < %d", $info['sala_id'], $info['fila'], $info['numero'] ) );
				$info['numero'] = $info['numero'] - $num_pasillos;
				unset( $info['sala_id'] ); // Remove sala_id as it was only needed for the calculation
			}
			unset($info); // break the reference

			$item->add_meta_data( '_cnes_asientos', json_encode( $asientos_info ) );
			$item->add_meta_data( '_cnes_asientos_ids', $data['asientos'] );
		}
	}

	/**
	 * Handle order status changes to 'processing' or 'completed'.
	 */
	public function order_paid( $order_id ) {
		$order = wc_get_order( $order_id );
		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );

		foreach ( $order->get_items() as $item_id => $item ) {
			$funcion_id  = $item->get_meta( '_cnes_funcion_id' );
			$asientos_ids = $item->get_meta( '_cnes_asientos_ids' );

			if ( ! $funcion_id || ! $asientos_ids ) {
				continue;
			}

			// 1. Update reservations state to 'pagado'.
			foreach ( $asientos_ids as $asiento_id ) {
				$wpdb->update(
					$tabla_reservas,
					array(
						'estado'      => 'pagado',
						'wc_order_id' => $order_id,
						'expires_at'  => null,
					),
					array( 'funcion_id' => $funcion_id, 'asiento_id' => $asiento_id ),
					array( '%s', '%d', '%s' ),
					array( '%d', '%d' )
				);
			}

			// Check if función is now sold out.
			$this->verificar_capacidad_funcion( $funcion_id );

			// 2. Generate ticket code.
			$random = strtoupper( wp_generate_password( 4, false ) );
			$ticket_code = sprintf( 'CNES-%d-%d-%s', $order_id, $funcion_id, $random );
			$item->update_meta_data( '_cnes_ticket_code', $ticket_code );
			$item->save();
		}
	}

	/**
	 * Handle order status changes to 'cancelled', 'refunded', or 'failed'.
	 */
	public function order_cancelled( $order_id ) {
		$order = wc_get_order( $order_id );
		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );

		foreach ( $order->get_items() as $item_id => $item ) {
			$funcion_id   = $item->get_meta( '_cnes_funcion_id' );
			$asientos_ids = $item->get_meta( '_cnes_asientos_ids' );

			if ( $funcion_id && $asientos_ids ) {
				foreach ( $asientos_ids as $asiento_id ) {
					$wpdb->delete(
						$tabla_reservas,
						array( 'funcion_id' => $funcion_id, 'asiento_id' => $asiento_id, 'wc_order_id' => $order_id ),
						array( '%d', '%d', '%d' )
					);
				}
				// Revert agotada → en_venta now that seats are freed.
				$this->revertir_funcion_agotada( $funcion_id );
			}
		}
	}

	/**
	 * Add ticket info to emails.
	 */
	public function email_order_details( $order, $sent_to_admin, $plain_text, $email ) {
		if ( $sent_to_admin ) {
			return;
		}

		$has_tickets = false;
		foreach ( $order->get_items() as $item ) {
			if ( $item->get_meta( '_cnes_ticket_code' ) ) {
				$has_tickets = true;
				break;
			}
		}

		if ( ! $has_tickets ) {
			return;
		}

		echo '<h2>' . __( 'Tus Entradas de Cine', 'sala-estrella-manager' ) . '</h2>';

		foreach ( $order->get_items() as $item ) {
			$code = $item->get_meta( '_cnes_ticket_code' );
			if ( ! $code ) continue;

			$pelicula = $item->get_name(); // This was replaced by cart_item_name logic, but in order it might be original or saved.
			// Let's use metadata to be sure.
			$fecha          = $item->get_meta( '_cnes_fecha' );
			$hora           = $item->get_meta( '_cnes_hora' );
			$sede           = $item->get_meta( '_cnes_sede' );
			$formato_raw    = $item->get_meta( '_cnes_formato_idioma' );
			$formato_label  = ( 'subtitulada' === $formato_raw ) ? __( 'Subtitulada (SUB)', 'sala-estrella-manager' ) : __( 'Doblada (DOB)', 'sala-estrella-manager' );
			$asientos_json  = $item->get_meta( '_cnes_asientos' );
			$asientos       = json_decode( $asientos_json, true );

			$asientos_text = array();
			foreach ( $asientos as $a ) {
				$asientos_text[] = sprintf( 'Fila %s - Asiento %d', $a['fila'], $a['numero'] );
			}

			echo '<div style="margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px;">';
			echo '<p><strong>' . esc_html( $pelicula ) . '</strong></p>';
			echo '<p>' . __( 'Sede', 'sala-estrella-manager' ) . ': ' . esc_html( $sede ) . '</p>';
			echo '<p>' . __( 'Fecha', 'sala-estrella-manager' ) . ': ' . esc_html( $fecha ) . ' ' . esc_html( $hora ) . ' hrs</p>';
			echo '<p>' . __( 'Formato', 'sala-estrella-manager' ) . ': ' . esc_html( $formato_label ) . '</p>';
			echo '<p>' . __( 'Asientos', 'sala-estrella-manager' ) . ': ' . esc_html( implode( ', ', $asientos_text ) ) . '</p>';
			echo '<p><strong>' . __( 'CÓDIGO DE TICKET', 'sala-estrella-manager' ) . ': <span style="font-size: 1.2em; color: #d32f2f;">' . esc_html( $code ) . '</span></strong></p>';
			echo '</div>';
		}
	}

	/**
	 * Prevent quantity changes for cinema tickets in the cart.
	 */
	public function cart_item_quantity_input_args( $args, $cart_item_key ) {
		$cart_item = WC()->cart->get_cart_item( $cart_item_key );
		if ( isset( $cart_item['cnes_data'] ) ) {
			$args['readonly'] = true;
		}
		return $args;
	}

	/**
	 * Release seats when item is removed from cart.
	 */
	public function cart_item_removed( $cart_item_key, $cart ) {
		$removed_item = $cart->get_removed_cart_contents();
		if ( isset( $removed_item[ $cart_item_key ]['cnes_data'] ) ) {
			$data = $removed_item[ $cart_item_key ]['cnes_data'];
			$reservas = new CNES_Reservas();
			
			$user_id    = get_current_user_id();
			$session_id = WC()->session ? WC()->session->get_customer_id() : '';

			foreach ( $data['asientos'] as $asiento_id ) {
				$reservas->liberar_asiento( $data['funcion_id'], $asiento_id, $user_id, $session_id );
			}
		}
	}

	/**
	 * Mark función as 'agotada' if paid reservations >= sala capacity.
	 */
	private function verificar_capacidad_funcion( $funcion_id ) {
		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_asientos  = CNES_Helpers::get_tabla( 'asientos' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );

		$sala_id = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT sala_id FROM {$tabla_funciones} WHERE id = %d AND estado = 'en_venta'",
			$funcion_id
		) );
		if ( ! $sala_id ) return;

		$capacidad = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$tabla_asientos} WHERE sala_id = %d AND activo = 1",
			$sala_id
		) );
		if ( $capacidad <= 0 ) return;

		$pagadas = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$tabla_reservas} WHERE funcion_id = %d AND estado = 'pagado'",
			$funcion_id
		) );

		if ( $pagadas >= $capacidad ) {
			$wpdb->update(
				$tabla_funciones,
				array( 'estado' => 'agotada' ),
				array( 'id' => $funcion_id ),
				array( '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Revert función from 'agotada' to 'en_venta' when seats are freed by a cancellation.
	 */
	private function revertir_funcion_agotada( $funcion_id ) {
		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );

		$wpdb->update(
			$tabla_funciones,
			array( 'estado' => 'en_venta' ),
			array( 'id' => $funcion_id, 'estado' => 'agotada' ),
			array( '%s' ),
			array( '%d', '%s' )
		);
	}

	/**
	 * Helper to get compact seat names (e.g. F7, F8).
	 */
	private function get_asientos_compactos( $asientos_ids ) {
		global $wpdb;
		$tabla_asientos = CNES_Helpers::get_tabla( 'asientos' );
		
		$ids = implode( ',', array_map( 'intval', $asientos_ids ) );
		$results = $wpdb->get_results( "SELECT sala_id, fila, numero FROM {$tabla_asientos} WHERE id IN ($ids) ORDER BY fila, numero" );

		$nombres = array();
		foreach ( $results as $row ) {
			$num_pasillos = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla_asientos} WHERE sala_id = %d AND fila = %s AND tipo = 'pasillo' AND numero < %d", $row->sala_id, $row->fila, $row->numero ) );
			$real_numero = $row->numero - $num_pasillos;
			$nombres[] = sprintf( '%s%d', $row->fila, $real_numero );
		}

		return implode( ', ', $nombres );
	}

	/**
	 * Release all seats when cart is emptied.
	 */
	public function cart_emptied() {
		$reservas   = new CNES_Reservas();
		$user_id    = get_current_user_id();
		$session_id = WC()->session ? WC()->session->get_customer_id() : '';
		$reservas->liberar_todos_asientos( $user_id, $session_id );
	}
}
