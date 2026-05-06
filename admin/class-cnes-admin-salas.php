<?php
/**
 * Admin logic for Rooms (Salas).
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles rooms listing, creation, editing and visual layout.
 */
class CNES_Admin_Salas {

	/**
	 * Render the Salas admin page.
	 */
	public function render_salas_page() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		switch ( $action ) {
			case 'new':
			case 'edit':
				$this->render_form( $id );
				break;
			case 'delete':
				$this->handle_delete( $id );
				break;
			default:
				$this->render_list();
				break;
		}
	}

	/**
	 * List all rooms.
	 */
	private function render_list() {
		global $wpdb;
		$tabla = CNES_Helpers::get_tabla( 'salas' );
		$salas = $wpdb->get_results( "SELECT * FROM {$tabla} ORDER BY sede ASC, nombre ASC" );

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Salas', 'sala-estrella-manager' ) . '</h1>';
		echo '<a href="' . esc_url( admin_url( 'admin.php?page=cnes-salas&action=new' ) ) . '" class="page-title-action">' . esc_html__( 'Agregar Nueva Sala', 'sala-estrella-manager' ) . '</a>';
		echo '<hr class="wp-header-end">';

		$this->render_notices();

		echo '<table class="wp-list-table widefat striped">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . esc_html__( 'Nombre', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Sede', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Filas × Columnas', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Capacidad', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Estado', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Acciones', 'sala-estrella-manager' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( $salas ) {
			foreach ( $salas as $sala ) {
				$edit_url   = admin_url( 'admin.php?page=cnes-salas&action=edit&id=' . $sala->id );
				$delete_url = wp_nonce_url( admin_url( 'admin.php?page=cnes-salas&action=delete&id=' . $sala->id ), 'cnes_delete_sala_' . $sala->id );

				echo '<tr>';
				echo '<td><strong><a href="' . esc_url( $edit_url ) . '">' . esc_html( $sala->nombre ) . '</a></strong></td>';
				echo '<td>' . esc_html( $sala->sede ) . '</td>';
				echo '<td>' . esc_html( "{$sala->filas} × {$sala->columnas}" ) . '</td>';
				echo '<td>' . esc_html( $sala->capacidad ) . '</td>';
				echo '<td>' . esc_html( ucfirst( $sala->estado ) ) . '</td>';
				echo '<td>';
				echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Editar', 'sala-estrella-manager' ) . '</a> | ';
				echo '<a href="' . esc_url( $delete_url ) . '" class="submitdelete" onclick="return confirm(\'' . esc_js( __( '¿Estás seguro de eliminar esta sala? Esta acción no se puede deshacer.', 'sala-estrella-manager' ) ) . '\');">' . esc_html__( 'Eliminar', 'sala-estrella-manager' ) . '</a>';
				echo '</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="6">' . esc_html__( 'No hay salas creadas.', 'sala-estrella-manager' ) . '</td></tr>';
		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';
	}

	/**
	 * Form for adding/editing a room.
	 *
	 * @param int $id Room ID for editing.
	 */
	private function render_form( $id = 0 ) {
		global $wpdb;
		$sala = null;
		if ( $id ) {
			$tabla = CNES_Helpers::get_tabla( 'salas' );
			$sala  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE id = %d", $id ) );
		}

		$is_edit = (bool) $sala;
		$title   = $is_edit ? __( 'Editar Sala', 'sala-estrella-manager' ) : __( 'Agregar Nueva Sala', 'sala-estrella-manager' );

		if ( isset( $_POST['cnes_save_sala'] ) ) {
			$this->handle_save();
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( $title ) . '</h1>';
		
		$this->render_notices();

		echo '<form method="post" action="">';
		wp_nonce_field( 'cnes_sala_nonce', 'cnes_nonce' );
		if ( $is_edit ) {
			echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
		}

		echo '<table class="form-table">';
		
		// Nombre
		echo '<tr>';
		echo '<th><label for="nombre">' . esc_html__( 'Nombre de la Sala', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td><input name="nombre" type="text" id="nombre" value="' . esc_attr( $is_edit ? $sala->nombre : '' ) . '" class="regular-text" required></td>';
		echo '</tr>';

		// Sede
		$sede = $is_edit ? $sala->sede : '';
		echo '<tr>';
		echo '<th><label for="sede">' . esc_html__( 'Sede', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="sede" id="sede" required>';
		echo '<option value="">' . esc_html__( 'Seleccionar Sede', 'sala-estrella-manager' ) . '</option>';
		echo '<option value="Punta Arenas" ' . selected( $sede, 'Punta Arenas', false ) . '>Punta Arenas</option>';
		echo '<option value="Puerto Natales" ' . selected( $sede, 'Puerto Natales', false ) . '>Puerto Natales</option>';
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Dimensiones (solo si es nueva, o mostrar como informativo si es edición)
		// Las instrucciones dicen que se pueden editar filas/columnas. 
		// Pero si se cambian, habría que regenerar asientos.
		echo '<tr>';
		echo '<th><label for="filas">' . esc_html__( 'Filas', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td><input name="filas" type="number" id="filas" value="' . esc_attr( $is_edit ? $sala->filas : 8 ) . '" min="1" max="30" class="small-text" required>';
		if ( $is_edit ) {
			echo '<p class="description">' . esc_html__( 'Cambiar las dimensiones eliminará el layout actual y regenerará todos los asientos.', 'sala-estrella-manager' ) . '</p>';
		}
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="columnas">' . esc_html__( 'Columnas', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td><input name="columnas" type="number" id="columnas" value="' . esc_attr( $is_edit ? $sala->columnas : 12 ) . '" min="1" max="30" class="small-text" required></td>';
		echo '</tr>';

		// Estado
		$estado = $is_edit ? $sala->estado : 'activa';
		echo '<tr>';
		echo '<th><label for="estado">' . esc_html__( 'Estado', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="estado" id="estado">';
		echo '<option value="activa" ' . selected( $estado, 'activa', false ) . '>' . esc_html__( 'Activa', 'sala-estrella-manager' ) . '</option>';
		echo '<option value="inactiva" ' . selected( $estado, 'inactiva', false ) . '>' . esc_html__( 'Inactiva', 'sala-estrella-manager' ) . '</option>';
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';

		submit_button( __( 'Guardar Sala', 'sala-estrella-manager' ), 'primary', 'cnes_save_sala' );
		echo '</form>';

		// Layout Editor (only if editing)
		if ( $is_edit ) {
			$this->render_layout_editor( $sala );
		}

		echo '</div>';
	}

	/**
	 * Render the visual layout editor.
	 *
	 * @param object $sala Room data.
	 */
	private function render_layout_editor( $sala ) {
		global $wpdb;
		$tabla_asientos = CNES_Helpers::get_tabla( 'asientos' );
		$asientos       = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$tabla_asientos} WHERE sala_id = %d ORDER BY fila ASC, numero ASC", $sala->id ) );

		// Organizar asientos por fila
		$layout_data = array();
		foreach ( $asientos as $asiento ) {
			$layout_data[$asiento->fila][$asiento->numero] = $asiento;
		}

		echo '<hr>';
		echo '<h2>' . esc_html__( 'Editor Visual de Layout', 'sala-estrella-manager' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Haz clic en un asiento para cambiar su tipo. Los pasillos e inactivos no se pueden reservar.', 'sala-estrella-manager' ) . '</p>';

		echo '<div id="cnes-layout-container" data-sala-id="' . esc_attr( $sala->id ) . '" data-filas="' . esc_attr( $sala->filas ) . '" data-columnas="' . esc_attr( $sala->columnas ) . '">';
		
		// Leyenda
		echo '<div class="cnes-layout-legend">';
		echo '<span class="legend-item"><span class="box type-normal"></span> ' . esc_html__( 'Normal', 'sala-estrella-manager' ) . '</span>';
		echo '<span class="legend-item"><span class="box type-vip"></span> ' . esc_html__( 'VIP', 'sala-estrella-manager' ) . '</span>';
		echo '<span class="legend-item"><span class="box type-discapacidad"></span> ' . esc_html__( 'Discapacidad', 'sala-estrella-manager' ) . '</span>';
		echo '<span class="legend-item"><span class="box type-pasillo"></span> ' . esc_html__( 'Pasillo', 'sala-estrella-manager' ) . '</span>';
		echo '<span class="legend-item"><span class="box type-inactivo"></span> ' . esc_html__( 'Inactivo', 'sala-estrella-manager' ) . '</span>';
		echo '</div>';

		echo '<div class="cnes-layout-grid-wrapper">';
		echo '<div class="cnes-screen">' . esc_html__( 'PANTALLA', 'sala-estrella-manager' ) . '</div>';
		echo '<div class="cnes-layout-grid" style="grid-template-columns: 30px repeat(' . esc_attr( $sala->columnas ) . ', 1fr);">';
		
		// Header números
		echo '<div></div>'; // Espina vacía
		for ( $c = 1; $c <= $sala->columnas; $c++ ) {
			echo '<div class="grid-header">' . $c . '</div>';
		}

		for ( $f = 0; $f < $sala->filas; $f++ ) {
			$fila_letra = chr( 65 + $f );
			echo '<div class="grid-header-row">' . $fila_letra . '</div>';
			
			$seat_real_number = 1;
			for ( $c = 1; $c <= $sala->columnas; $c++ ) {
				$asiento = isset( $layout_data[$fila_letra][$c] ) ? $layout_data[$fila_letra][$c] : null;
				$tipo    = $asiento ? $asiento->tipo : 'normal';
				$activo  = $asiento ? $asiento->activo : 1;
				
				$class = "seat type-{$tipo}";
				if ( ! $activo && $tipo !== 'pasillo' ) {
					$class .= ' is-inactive';
				}

				$display_number = $c;
				if ( $tipo === 'pasillo' ) {
					// Pasillos do not increment the seat number
					$display_number = 'Pasillo';
				} else {
					$display_number = $seat_real_number;
					$seat_real_number++;
				}

				echo '<div class="' . esc_attr( $class ) . '" ';
				echo 'data-fila="' . esc_attr( $fila_letra ) . '" ';
				echo 'data-numero="' . esc_attr( $c ) . '" ';
				echo 'data-tipo="' . esc_attr( $tipo ) . '" ';
				if ( $tipo === 'pasillo' ) {
					echo 'title="' . esc_attr( "{$fila_letra} - Pasillo" ) . '">';
					echo ' ';
				} else {
					echo 'title="' . esc_attr( "{$fila_letra}{$display_number} - " . ucfirst( $tipo ) ) . '">';
					echo $fila_letra . $display_number;
				}
				echo '</div>';
			}
		}

		echo '</div>'; // .cnes-layout-grid
		echo '</div>'; // .cnes-layout-grid-wrapper

		echo '<div class="layout-actions">';
		echo '<button type="button" id="cnes-save-layout" class="button button-primary">' . esc_html__( 'Guardar Layout', 'sala-estrella-manager' ) . '</button>';
		echo '<span class="spinner"></span>';
		echo '</div>';

		echo '</div>'; // #cnes-layout-container
	}

	/**
	 * Handle saving room data.
	 */
	private function handle_save() {
		check_admin_referer( 'cnes_sala_nonce', 'cnes_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$id       = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$nombre   = sanitize_text_field( $_POST['nombre'] );
		$sede     = sanitize_text_field( $_POST['sede'] );
		$filas    = absint( $_POST['filas'] );
		$columnas = absint( $_POST['columnas'] );
		$estado   = sanitize_text_field( $_POST['estado'] );

		$tabla = CNES_Helpers::get_tabla( 'salas' );

		$data = array(
			'nombre'   => $nombre,
			'sede'     => $sede,
			'filas'    => $filas,
			'columnas' => $columnas,
			'estado'   => $estado,
		);

		$format = array( '%s', '%s', '%d', '%d', '%s' );

		if ( $id ) {
			// Antes de actualizar, ver si cambiaron dimensiones
			$old_sala = $wpdb->get_row( $wpdb->prepare( "SELECT filas, columnas FROM {$tabla} WHERE id = %d", $id ) );
			$res      = $wpdb->update( $tabla, $data, array( 'id' => $id ), $format, array( '%d' ) );
			
			if ( $old_sala && ( $old_sala->filas != $filas || $old_sala->columnas != $columnas ) ) {
				$this->generate_asientos( $id, $filas, $columnas );
			}
			
			$message = __( 'Sala actualizada correctamente.', 'sala-estrella-manager' );
		} else {
			$data['layout'] = ''; // Will be generated
			$format[]       = '%s';
			$wpdb->insert( $tabla, $data, $format );
			$id      = $wpdb->insert_id;
			$this->generate_asientos( $id, $filas, $columnas );
			$message = __( 'Sala creada correctamente.', 'sala-estrella-manager' );
		}

		set_transient( 'cnes_admin_notice', array( 'type' => 'success', 'message' => $message ), 30 );
		wp_redirect( admin_url( 'admin.php?page=cnes-salas&action=edit&id=' . $id ) );
		exit;
	}

	/**
	 * Generate or regenerate seats for a room.
	 *
	 * @param int $sala_id  Room ID.
	 * @param int $filas    Number of rows.
	 * @param int $columnas Number of columns.
	 */
	private function generate_asientos( $sala_id, $filas, $columnas ) {
		global $wpdb;
		$tabla_asientos = CNES_Helpers::get_tabla( 'asientos' );
		$tabla_salas    = CNES_Helpers::get_tabla( 'salas' );

		// 1. Eliminar asientos existentes
		$wpdb->delete( $tabla_asientos, array( 'sala_id' => $sala_id ), array( '%d' ) );

		// 2. Insertar nuevos asientos
		$asientos_json = array();
		$capacidad     = 0;

		for ( $f = 0; $f < $filas; $f++ ) {
			$fila_letra = chr( 65 + $f );
			for ( $c = 1; $c <= $columnas; $c++ ) {
				$wpdb->insert( $tabla_asientos, array(
					'sala_id' => $sala_id,
					'fila'    => $fila_letra,
					'numero'  => $c,
					'tipo'    => 'normal',
					'activo'  => 1,
				), array( '%d', '%s', '%d', '%s', '%d' ) );

				$asientos_json[] = array(
					'fila'   => $fila_letra,
					'numero' => $c,
					'tipo'   => 'normal',
					'activo' => true
				);
				$capacidad++;
			}
		}

		// 3. Actualizar sala con layout JSON inicial y capacidad
		$layout = json_encode( array(
			'filas'    => $filas,
			'columnas' => $columnas,
			'asientos' => $asientos_json
		) );

		$wpdb->update( $tabla_salas, array(
			'layout'    => $layout,
			'capacidad' => $capacidad
		), array( 'id' => $sala_id ), array( '%s', '%d' ), array( '%d' ) );
	}

	/**
	 * Handle room deletion.
	 */
	private function handle_delete( $id ) {
		check_admin_referer( 'cnes_delete_sala_' . $id );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$tabla_salas     = CNES_Helpers::get_tabla( 'salas' );
		$tabla_asientos  = CNES_Helpers::get_tabla( 'asientos' );
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );

		// Verificar si hay funciones activas
		$funciones_activas = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla_funciones} WHERE sala_id = %d AND estado IN ('programada', 'en_venta')", $id ) );

		if ( $funciones_activas > 0 ) {
			set_transient( 'cnes_admin_notice', array( 'type' => 'error', 'message' => __( 'No se puede eliminar la sala porque tiene funciones activas asociadas.', 'sala-estrella-manager' ) ), 30 );
		} else {
			$wpdb->delete( $tabla_asientos, array( 'sala_id' => $id ), array( '%d' ) );
			$wpdb->delete( $tabla_salas, array( 'id' => $id ), array( '%d' ) );
			set_transient( 'cnes_admin_notice', array( 'type' => 'success', 'message' => __( 'Sala eliminada correctamente.', 'sala-estrella-manager' ) ), 30 );
		}

		wp_redirect( admin_url( 'admin.php?page=cnes-salas' ) );
		exit;
	}

	/**
	 * AJAX endpoint to save room layout.
	 */
	public function ajax_guardar_layout_sala() {
		check_ajax_referer( 'cnes_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permisos insuficientes.', 'sala-estrella-manager' ) );
		}

		$sala_id  = isset( $_POST['sala_id'] ) ? absint( $_POST['sala_id'] ) : 0;
		$asientos = isset( $_POST['asientos'] ) ? $_POST['asientos'] : array();

		if ( ! $sala_id || empty( $asientos ) ) {
			wp_send_json_error( __( 'Datos inválidos.', 'sala-estrella-manager' ) );
		}

		global $wpdb;
		$tabla_asientos = CNES_Helpers::get_tabla( 'asientos' );
		$tabla_salas    = CNES_Helpers::get_tabla( 'salas' );

		$capacidad = 0;
		$layout_asientos = array();

		foreach ( $asientos as $a ) {
			$fila   = sanitize_text_field( $a['fila'] );
			$numero = absint( $a['numero'] );
			$tipo   = sanitize_text_field( $a['tipo'] );
			
			// Activo según tipo
			$activo = ( $tipo === 'pasillo' || $tipo === 'inactivo' ) ? 0 : 1;

			$wpdb->update( $tabla_asientos, 
				array( 'tipo' => $tipo, 'activo' => $activo ),
				array( 'sala_id' => $sala_id, 'fila' => $fila, 'numero' => $numero ),
				array( '%s', '%d' ),
				array( '%d', '%s', '%d' )
			);

			if ( $activo ) {
				$capacidad++;
			}

			$layout_asientos[] = array(
				'fila'   => $fila,
				'numero' => $numero,
				'tipo'   => $tipo,
				'activo' => (bool)$activo
			);
		}

		// Actualizar sala
		$sala_info = $wpdb->get_row( $wpdb->prepare( "SELECT filas, columnas FROM {$tabla_salas} WHERE id = %d", $sala_id ) );
		
		$layout = json_encode( array(
			'filas'    => $sala_info->filas,
			'columnas' => $sala_info->columnas,
			'asientos' => $layout_asientos
		) );

		$wpdb->update( $tabla_salas, 
			array( 'layout' => $layout, 'capacidad' => $capacidad ),
			array( 'id' => $sala_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);

		wp_send_json_success( array( 
			'message'   => __( 'Layout guardado correctamente.', 'sala-estrella-manager' ),
			'capacidad' => $capacidad
		) );
	}

	/**
	 * Display admin notices from transient.
	 */
	private function render_notices() {
		$notice = get_transient( 'cnes_admin_notice' );
		if ( $notice ) {
			echo '<div class="notice notice-' . esc_attr( $notice['type'] ) . ' is-dismissible"><p>' . esc_html( $notice['message'] ) . '</p></div>';
			delete_transient( 'cnes_admin_notice' );
		}
	}
}
