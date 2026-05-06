<?php
/**
 * Handles seat blocking, releasing, and status management.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Logic for managing temporary and permanent seat reservations.
 */
class CNES_Reservas {

	/**
	 * Block a seat temporarily.
	 *
	 * @param int    $funcion_id Function ID.
	 * @param int    $asiento_id Asiento ID.
	 * @param int    $user_id    User ID (optional).
	 * @param string $session_id Session ID (optional).
	 * @return array Response with success status and message.
	 */
	public function bloquear_asiento( $funcion_id, $asiento_id, $user_id = 0, $session_id = '' ) {
		global $wpdb;
		$tabla_asientos  = CNES_Helpers::get_tabla( 'asientos' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );

		// Purge expired rows before checking availability.
		$this->limpiar_expirados( $funcion_id );

		// 1. Verify seat is active.
		$asiento_activo = $wpdb->get_var( $wpdb->prepare(
			"SELECT activo FROM {$tabla_asientos} WHERE id = %d",
			$asiento_id
		) );

		if ( ! $asiento_activo ) {
			return array( 'success' => false, 'message' => __( 'El asiento no está disponible.', 'sala-estrella-manager' ) );
		}

		// 2. Attempt to insert reservation.
		$inserted = $wpdb->insert(
			$tabla_reservas,
			array(
				'funcion_id' => $funcion_id,
				'asiento_id' => $asiento_id,
				'user_id'    => $user_id ?: null,
				'session_id' => $session_id ?: null,
				'estado'     => 'seleccionado',
				'expires_at' => date( 'Y-m-d H:i:s', strtotime( '+5 minutes' ) ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s' )
		);

		if ( false === $inserted ) {
			// UNIQUE KEY violation likely means it's already taken.
			return array( 'success' => false, 'message' => __( 'El asiento ya está reservado o seleccionado por otro usuario.', 'sala-estrella-manager' ) );
		}

		return array( 'success' => true, 'message' => __( 'Asiento bloqueado temporalmente.', 'sala-estrella-manager' ) );
	}

	/**
	 * Release a temporarily blocked seat.
	 *
	 * @param int    $funcion_id Function ID.
	 * @param int    $asiento_id Asiento ID.
	 * @param int    $user_id    User ID.
	 * @param string $session_id Session ID.
	 * @return bool Success.
	 */
	public function liberar_asiento( $funcion_id, $asiento_id, $user_id = 0, $session_id = '' ) {
		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );

		if ( ! $user_id && empty( $session_id ) ) {
			return false; // Must provide owner info.
		}

		$query = $wpdb->prepare(
			"DELETE FROM {$tabla_reservas} 
			 WHERE funcion_id = %d 
			 AND asiento_id = %d
			 AND estado = 'seleccionado'
			 AND ( (user_id > 0 AND user_id = %d) OR (session_id = %s) )",
			$funcion_id,
			$asiento_id,
			$user_id,
			$session_id
		);

		return (bool) $wpdb->query( $query );
	}

	/**
	 * Release all 'seleccionado' seats for a specific function and user/session.
	 *
	 * @param int    $funcion_id Function ID.
	 * @param int    $user_id    User ID.
	 * @param string $session_id Session ID.
	 * @return bool Success.
	 */
	public function liberar_todos_asientos_funcion( $funcion_id, $user_id = 0, $session_id = '' ) {
		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );

		if ( ! $user_id && empty( $session_id ) ) {
			return false;
		}

		$query = $wpdb->prepare(
			"DELETE FROM {$tabla_reservas} 
			 WHERE funcion_id = %d 
			 AND estado = 'seleccionado'
			 AND ( (user_id > 0 AND user_id = %d) OR (session_id = %s) )",
			$funcion_id,
			$user_id,
			$session_id
		);

		return (bool) $wpdb->query( $query );
	}

	/**
	 * Release all 'seleccionado' seats globally for a specific user and/or session.
	 *
	 * @param int    $user_id    User ID.
	 * @param string $session_id Session ID.
	 * @return bool Success.
	 */
	public function liberar_todos_asientos( $user_id = 0, $session_id = '' ) {
		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );

		if ( ! $user_id && empty( $session_id ) ) {
			return false;
		}

		$query = $wpdb->prepare(
			"DELETE FROM {$tabla_reservas} 
			 WHERE estado = 'seleccionado'
			 AND ( (user_id > 0 AND user_id = %d) OR (session_id = %s) )",
			$user_id,
			$session_id
		);

		return (bool) $wpdb->query( $query );
	}

	/**
	 * Delete expired 'seleccionado' rows, optionally scoped to one function.
	 *
	 * @param int|null $funcion_id Limit cleanup to this function, or null for all.
	 */
	public function limpiar_expirados( $funcion_id = null ) {
		global $wpdb;
		$tabla_reservas = CNES_Helpers::get_tabla( 'reservas' );

		if ( $funcion_id ) {
			$wpdb->query( $wpdb->prepare(
				"DELETE FROM {$tabla_reservas} WHERE funcion_id = %d AND estado = 'seleccionado' AND expires_at < NOW()",
				$funcion_id
			) );
		} else {
			$wpdb->query( "DELETE FROM {$tabla_reservas} WHERE estado = 'seleccionado' AND expires_at < NOW()" );
		}
	}

	/**
	 * Cron callback — cleanup all expired reservations globally.
	 */
	public function limpiar_reservas_expiradas() {
		$this->limpiar_expirados();
		error_log( '[CNES Cron] limpiar_reservas_expiradas: ' . current_time( 'mysql' ) );
	}

	/**
	 * Get the current status of all seats for a specific function.
	 *
	 * @param int $funcion_id Function ID.
	 * @return array List of seats with their current state.
	 */
	public function obtener_estado_asientos( $funcion_id ) {
		global $wpdb;
		$tabla_asientos  = CNES_Helpers::get_tabla( 'asientos' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );

		// Get sala_id for this function.
		$sala_id = $wpdb->get_var( $wpdb->prepare(
			"SELECT sala_id FROM {$tabla_funciones} WHERE id = %d",
			$funcion_id
		) );

		if ( ! $sala_id ) {
			return array();
		}

		// Cross seats with reservations.
		$query = $wpdb->prepare(
			"SELECT a.id, a.fila, a.numero, a.tipo, 
			        COALESCE(r.estado, 'libre') as estado
			 FROM {$tabla_asientos} a
			 LEFT JOIN {$tabla_reservas} r ON a.id = r.asiento_id AND r.funcion_id = %d
			 WHERE a.sala_id = %d AND a.activo = 1",
			$funcion_id,
			$sala_id
		);

		return $wpdb->get_results( $query, ARRAY_A );
	}
}
