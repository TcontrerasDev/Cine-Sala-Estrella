<?php
/**
 * Admin logic for Functions (Funciones).
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles functions listing, creation, editing and overlap validation.
 */
class CNES_Admin_Funciones {

	/**
	 * Render the Funciones admin page.
	 */
	public function render_funciones_page() {
		$action = isset( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		switch ( $action ) {
			case 'new':
			case 'edit':
				$this->render_form( $id );
				break;
			case 'duplicate':
				$this->handle_duplicate( $id );
				break;
			case 'cancel':
				$this->handle_cancel( $id );
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
	 * List functions split into activas / pasadas tabs.
	 */
	private function render_list() {
		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_salas     = CNES_Helpers::get_tabla( 'salas' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );

		$tab        = ( isset( $_GET['tab'] ) && 'pasadas' === $_GET['tab'] ) ? 'pasadas' : 'activas';
		$es_activas = ( 'activas' === $tab );

		// Filters
		$f_sede     = isset( $_GET['f_sede'] ) ? sanitize_text_field( $_GET['f_sede'] ) : '';
		$f_pelicula = isset( $_GET['f_pelicula'] ) ? absint( $_GET['f_pelicula'] ) : 0;
		$f_desde    = isset( $_GET['f_desde'] ) ? sanitize_text_field( $_GET['f_desde'] ) : '';
		$f_hasta    = isset( $_GET['f_hasta'] ) ? sanitize_text_field( $_GET['f_hasta'] ) : '';
		$f_estado   = isset( $_GET['f_estado'] ) ? sanitize_text_field( $_GET['f_estado'] ) : '';

		$where = array( '1=1' );

		if ( $es_activas ) {
			$where[] = 'f.fecha >= CURDATE()';
		} else {
			$where[] = 'f.fecha < CURDATE()';
		}

		if ( $f_sede ) {
			$where[] = $wpdb->prepare( 's.sede = %s', $f_sede );
		}
		if ( $f_pelicula ) {
			$where[] = $wpdb->prepare( 'f.pelicula_id = %d', $f_pelicula );
		}
		if ( $f_desde ) {
			$where[] = $wpdb->prepare( 'f.fecha >= %s', $f_desde );
		}
		if ( $f_hasta ) {
			$where[] = $wpdb->prepare( 'f.fecha <= %s', $f_hasta );
		}
		if ( $f_estado ) {
			$where[] = $wpdb->prepare( 'f.estado = %s', $f_estado );
		}

		$where_sql = implode( ' AND ', $where );

		// Paginación
		$per_page     = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$offset       = ( $current_page - 1 ) * $per_page;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$tabla_funciones} f JOIN {$tabla_salas} s ON f.sala_id = s.id WHERE {$where_sql}" );
		$num_pages   = ceil( $total_items / $per_page );

		$order = $es_activas ? 'ASC' : 'DESC';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$funciones = $wpdb->get_results( "SELECT f.*, s.nombre as sala_nombre, s.sede, s.capacidad FROM {$tabla_funciones} f JOIN {$tabla_salas} s ON f.sala_id = s.id WHERE {$where_sql} ORDER BY f.fecha {$order}, f.hora_inicio {$order} LIMIT {$offset}, {$per_page}" );

		// Tab badge counts (no extra filters applied)
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_activas = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabla_funciones} f JOIN {$tabla_salas} s ON f.sala_id = s.id WHERE f.fecha >= CURDATE()" );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total_pasadas = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tabla_funciones} f JOIN {$tabla_salas} s ON f.sala_id = s.id WHERE f.fecha < CURDATE()" );

		$estados  = array(
			'programada' => 'Programada',
			'en_venta'   => 'En venta',
			'agotada'    => 'Agotada',
			'finalizada' => 'Finalizada',
			'cancelada'  => 'Cancelada',
		);
		$base_url = admin_url( 'admin.php?page=cnes-funciones' );

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Funciones', 'sala-estrella-manager' ) . '</h1>';
		if ( $es_activas ) {
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=cnes-funciones&action=new' ) ) . '" class="page-title-action">' . esc_html__( 'Agregar Nueva Función', 'sala-estrella-manager' ) . '</a>';
		}
		echo '<hr class="wp-header-end">';

		$this->render_notices();

		// Tabs
		echo '<h2 class="nav-tab-wrapper">';
		echo '<a href="' . esc_url( $base_url . '&tab=activas' ) . '" class="nav-tab ' . ( $es_activas ? 'nav-tab-active' : '' ) . '">';
		echo esc_html__( 'Funciones Activas', 'sala-estrella-manager' );
		echo ' <span class="count">(' . esc_html( $total_activas ) . ')</span>';
		echo '</a>';
		echo '<a href="' . esc_url( $base_url . '&tab=pasadas' ) . '" class="nav-tab ' . ( ! $es_activas ? 'nav-tab-active' : '' ) . '">';
		echo esc_html__( 'Funciones Pasadas', 'sala-estrella-manager' );
		echo ' <span class="count">(' . esc_html( $total_pasadas ) . ')</span>';
		echo '</a>';
		echo '</h2>';

		// Filter form
		echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '">';
		echo '<input type="hidden" name="page" value="cnes-funciones">';
		echo '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
		echo '<div class="tablenav top">';
		echo '<div class="alignleft actions">';

		echo '<select name="f_sede">';
		echo '<option value="">' . esc_html__( 'Todas las Sedes', 'sala-estrella-manager' ) . '</option>';
		echo '<option value="Punta Arenas" ' . selected( $f_sede, 'Punta Arenas', false ) . '>Punta Arenas</option>';
		echo '<option value="Puerto Natales" ' . selected( $f_sede, 'Puerto Natales', false ) . '>Puerto Natales</option>';
		echo '</select>';

		$peliculas = get_posts( array( 'post_type' => 'pelicula', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
		echo '<select name="f_pelicula">';
		echo '<option value="">' . esc_html__( 'Todas las Películas', 'sala-estrella-manager' ) . '</option>';
		foreach ( $peliculas as $p ) {
			echo '<option value="' . esc_attr( $p->ID ) . '" ' . selected( $f_pelicula, $p->ID, false ) . '>' . esc_html( $p->post_title ) . '</option>';
		}
		echo '</select>';

		echo ' <input type="date" name="f_desde" value="' . esc_attr( $f_desde ) . '">';
		echo ' <input type="date" name="f_hasta" value="' . esc_attr( $f_hasta ) . '">';

		echo '<select name="f_estado">';
		echo '<option value="">' . esc_html__( 'Todos los Estados', 'sala-estrella-manager' ) . '</option>';
		foreach ( $estados as $val => $lbl ) {
			echo '<option value="' . esc_attr( $val ) . '" ' . selected( $f_estado, $val, false ) . '>' . esc_html( $lbl ) . '</option>';
		}
		echo '</select>';

		echo '<input type="submit" class="button" value="' . esc_attr__( 'Filtrar', 'sala-estrella-manager' ) . '">';
		echo ' <a href="' . esc_url( $base_url . '&tab=' . $tab ) . '" class="button">' . esc_html__( 'Limpiar', 'sala-estrella-manager' ) . '</a>';
		echo '</div>';

		if ( $num_pages > 1 ) {
			echo '<div class="tablenav-pages">';
			echo '<span class="displaying-num">' . sprintf( _n( '%s elemento', '%s elementos', $total_items, 'sala-estrella-manager' ), $total_items ) . '</span>';
			echo paginate_links( array(
				'base'      => add_query_arg( 'paged', '%#%' ),
				'format'    => '',
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
				'total'     => $num_pages,
				'current'   => $current_page,
			) );
			echo '</div>';
		}

		echo '</div>';
		echo '</form>';

		// Bulk delete bar — both tabs
		if ( $funciones ) {
			echo '<div class="cnes-bulk-bar">';
			echo '<button id="cnes-bulk-delete-btn" class="button button-link-delete" disabled>';
			echo esc_html__( 'Eliminar seleccionadas', 'sala-estrella-manager' );
			echo ' (<span id="cnes-selected-count">0</span>)';
			echo '</button>';
			echo '</div>';
		}

		echo '<table class="wp-list-table widefat striped">';
		echo '<thead><tr>';
		echo '<td class="manage-column column-cb check-column"><input type="checkbox" id="cnes-select-all"></td>';
		echo '<th>' . esc_html__( 'Película', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Sala / Sede', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Fecha / Hora', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Formato', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Precios', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Asientos', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Estado', 'sala-estrella-manager' ) . '</th>';
		echo '<th>' . esc_html__( 'Acciones', 'sala-estrella-manager' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		if ( $funciones ) {
			foreach ( $funciones as $f ) {
				$pelicula_title = get_the_title( $f->pelicula_id );
				$vendidos       = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tabla_reservas} WHERE funcion_id = %d AND estado = 'pagado'", $f->id ) );
				$formato_label  = ( 'subtitulada' === $f->formato_idioma ) ? 'SUB' : 'DOB';
				$formato_class  = ( 'subtitulada' === $f->formato_idioma ) ? 'cnes-badge cnes-badge-sub' : 'cnes-badge cnes-badge-dob';
				$estado_label   = isset( $estados[ $f->estado ] ) ? $estados[ $f->estado ] : $f->estado;
				$row_class      = $es_activas ? 'cnes-row-activa' : 'cnes-row-pasada';

				$delete_url = wp_nonce_url( admin_url( 'admin.php?page=cnes-funciones&action=delete&id=' . $f->id ), 'cnes_delete_funcion_' . $f->id );

				echo '<tr class="' . esc_attr( $row_class ) . '">';
				echo '<th class="check-column"><input type="checkbox" class="cnes-funcion-check" value="' . esc_attr( $f->id ) . '"></th>';

				if ( $es_activas ) {
					$edit_url      = admin_url( 'admin.php?page=cnes-funciones&action=edit&id=' . $f->id );
					$duplicate_url = admin_url( 'admin.php?page=cnes-funciones&action=duplicate&id=' . $f->id );
					$cancel_url    = wp_nonce_url( admin_url( 'admin.php?page=cnes-funciones&action=cancel&id=' . $f->id ), 'cnes_cancel_funcion_' . $f->id );
					echo '<td><strong><a href="' . esc_url( $edit_url ) . '">' . esc_html( $pelicula_title ) . '</a></strong></td>';
				} else {
					echo '<td><strong>' . esc_html( $pelicula_title ) . '</strong></td>';
				}

				echo '<td>' . esc_html( "{$f->sala_nombre} ({$f->sede})" ) . '</td>';
				echo '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $f->fecha ) ) . ' ' . substr( $f->hora_inicio, 0, 5 ) ) . '</td>';
				echo '<td><span class="' . esc_attr( $formato_class ) . '">' . esc_html( $formato_label ) . '</span></td>';
				echo '<td>' . esc_html( '$' . number_format( $f->precio_normal, 0, ',', '.' ) . ' / $' . number_format( $f->precio_vip, 0, ',', '.' ) ) . '</td>';
				echo '<td>' . esc_html( "{$vendidos} / {$f->capacidad}" ) . '</td>';
				echo '<td>' . esc_html( $estado_label ) . '</td>';

				echo '<td>';
				if ( $es_activas ) {
					echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Editar', 'sala-estrella-manager' ) . '</a> | ';
					echo '<a href="' . esc_url( $duplicate_url ) . '">' . esc_html__( 'Duplicar', 'sala-estrella-manager' ) . '</a> | ';
					if ( $f->estado !== 'cancelada' ) {
						echo '<a href="' . esc_url( $cancel_url ) . '" onclick="return confirm(\'' . esc_js( __( '¿Estás seguro de cancelar esta función?', 'sala-estrella-manager' ) ) . '\');">' . esc_html__( 'Cancelar', 'sala-estrella-manager' ) . '</a> | ';
					}
				}
				echo '<a href="' . esc_url( $delete_url ) . '" class="submitdelete" onclick="return confirm(\'' . esc_js( __( '¿Estás seguro de eliminar esta función?', 'sala-estrella-manager' ) ) . '\');">' . esc_html__( 'Eliminar', 'sala-estrella-manager' ) . '</a>';
				echo '</td>';

				echo '</tr>';
			}
		} else {
			$empty_msg = $es_activas
				? __( 'No hay funciones activas.', 'sala-estrella-manager' )
				: __( 'No hay funciones pasadas registradas.', 'sala-estrella-manager' );
			echo '<tr><td colspan="9">' . esc_html( $empty_msg ) . '</td></tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * AJAX handler: bulk delete selected functions and their reservations.
	 */
	public function ajax_eliminar_masivo() {
		check_ajax_referer( 'cnes_admin_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Sin permisos.', 'sala-estrella-manager' ) ) );
		}

		$ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) $_POST['ids'] ) : array();
		$ids = array_values( array_filter( $ids ) );

		if ( empty( $ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No se seleccionaron funciones.', 'sala-estrella-manager' ) ) );
		}

		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );
		$placeholders    = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tabla_reservas} WHERE funcion_id IN ({$placeholders})", ...$ids ) );
		// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tabla_funciones} WHERE id IN ({$placeholders})", ...$ids ) );

		$count = count( $ids );
		wp_send_json_success( array(
			'message' => sprintf(
				_n( '%d función eliminada correctamente.', '%d funciones eliminadas correctamente.', $count, 'sala-estrella-manager' ),
				$count
			),
		) );
	}

	/**
	 * Form for adding/editing a function.
	 *
	 * @param int $id Function ID for editing.
	 */
	private function render_form( $id = 0 ) {
		global $wpdb;
		$funcion = null;
		if ( $id ) {
			$tabla   = CNES_Helpers::get_tabla( 'funciones' );
			$funcion = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE id = %d", $id ) );
		}

		$is_edit = (bool) $funcion;
		$title   = $is_edit ? __( 'Editar Función', 'sala-estrella-manager' ) : __( 'Agregar Nueva Función', 'sala-estrella-manager' );

		if ( isset( $_POST['cnes_save_funcion'] ) ) {
			$this->handle_save();
			return;
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( $title ) . '</h1>';

		$this->render_notices();

		echo '<form method="post" action="">';
		wp_nonce_field( 'cnes_funcion_nonce', 'cnes_nonce' );
		if ( $is_edit ) {
			echo '<input type="hidden" name="id" value="' . esc_attr( $id ) . '">';
		}

		echo '<table class="form-table">';

		// Película
		$peliculas = get_posts( array( 'post_type' => 'pelicula', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
		echo '<tr>';
		echo '<th><label for="pelicula_id">' . esc_html__( 'Película', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="pelicula_id" id="pelicula_id" required>';
		echo '<option value="">' . esc_html__( 'Seleccionar Película', 'sala-estrella-manager' ) . '</option>';
		foreach ( $peliculas as $p ) {
			echo '<option value="' . esc_attr( $p->ID ) . '" ' . selected( $is_edit ? $funcion->pelicula_id : 0, $p->ID, false ) . '>' . esc_html( $p->post_title ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Sala
		$tabla_salas = CNES_Helpers::get_tabla( 'salas' );
		$salas       = $wpdb->get_results( "SELECT id, nombre, sede FROM {$tabla_salas} WHERE estado = 'activa' ORDER BY sede ASC, nombre ASC" );
		echo '<tr>';
		echo '<th><label for="sala_id">' . esc_html__( 'Sala', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="sala_id" id="sala_id" required>';
		echo '<option value="">' . esc_html__( 'Seleccionar Sala', 'sala-estrella-manager' ) . '</option>';
		foreach ( $salas as $s ) {
			echo '<option value="' . esc_attr( $s->id ) . '" ' . selected( $is_edit ? $funcion->sala_id : 0, $s->id, false ) . '>' . esc_html( "{$s->nombre} ({$s->sede})" ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Fecha
		echo '<tr>';
		echo '<th><label for="fecha">' . esc_html__( 'Fecha', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td><input name="fecha" type="date" id="fecha" value="' . esc_attr( $is_edit ? $funcion->fecha : '' ) . '" min="' . esc_attr( date( 'Y-m-d' ) ) . '" required></td>';
		echo '</tr>';

		// Hora
		echo '<tr>';
		echo '<th><label for="hora_inicio">' . esc_html__( 'Hora de Inicio', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td><input name="hora_inicio" type="time" id="hora_inicio" value="' . esc_attr( $is_edit ? substr( $funcion->hora_inicio, 0, 5 ) : '' ) . '" required></td>';
		echo '</tr>';

		// Precios
		echo '<tr>';
		echo '<th><label for="precio_normal">' . esc_html__( 'Precio Normal ($)', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td><input name="precio_normal" type="number" id="precio_normal" value="' . esc_attr( $is_edit ? (int) $funcion->precio_normal : 0 ) . '" min="0" required></td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th><label for="precio_vip">' . esc_html__( 'Precio VIP ($)', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td><input name="precio_vip" type="number" id="precio_vip" value="' . esc_attr( $is_edit ? (int) $funcion->precio_vip : 0 ) . '" min="0" required>';
		echo '<p class="description">' . esc_html__( 'Si la sala no tiene asientos VIP, este precio se ignorará.', 'sala-estrella-manager' ) . '</p>';
		echo '</td>';
		echo '</tr>';

		// Formato de Idioma
		$formato_idioma = $is_edit ? $funcion->formato_idioma : 'doblada';
		echo '<tr>';
		echo '<th><label for="formato_idioma">' . esc_html__( 'Formato de Idioma', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="formato_idioma" id="formato_idioma">';
		echo '<option value="doblada" ' . selected( $formato_idioma, 'doblada', false ) . '>' . esc_html__( 'Doblada (DOB)', 'sala-estrella-manager' ) . '</option>';
		echo '<option value="subtitulada" ' . selected( $formato_idioma, 'subtitulada', false ) . '>' . esc_html__( 'Subtitulada (SUB)', 'sala-estrella-manager' ) . '</option>';
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		// Estado
		$estado = $is_edit ? $funcion->estado : 'programada';
		echo '<tr>';
		echo '<th><label for="estado">' . esc_html__( 'Estado', 'sala-estrella-manager' ) . '</label></th>';
		echo '<td>';
		echo '<select name="estado" id="estado">';
		$estados = array( 'programada' => 'Programada', 'en_venta' => 'En venta', 'agotada' => 'Agotada', 'finalizada' => 'Finalizada', 'cancelada' => 'Cancelada' );
		foreach ( $estados as $val => $lbl ) {
			echo '<option value="' . esc_attr( $val ) . '" ' . selected( $estado, $val, false ) . '>' . esc_html( $lbl ) . '</option>';
		}
		echo '</select>';
		echo '</td>';
		echo '</tr>';

		echo '</table>';

		submit_button( __( 'Guardar Función', 'sala-estrella-manager' ), 'primary', 'cnes_save_funcion' );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Handle saving function data with overlap validation.
	 */
	private function handle_save() {
		check_admin_referer( 'cnes_funcion_nonce', 'cnes_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$id             = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$pelicula_id    = absint( $_POST['pelicula_id'] );
		$sala_id        = absint( $_POST['sala_id'] );
		$fecha          = sanitize_text_field( $_POST['fecha'] );
		$hora_inicio    = sanitize_text_field( $_POST['hora_inicio'] );
		$precio_normal  = (float) $_POST['precio_normal'];
		$precio_vip     = (float) $_POST['precio_vip'];
		$formato_idioma = in_array( $_POST['formato_idioma'], array( 'doblada', 'subtitulada' ), true ) ? $_POST['formato_idioma'] : 'doblada';
		$estado         = sanitize_text_field( $_POST['estado'] );

		// Obtener duración de la película (ACF field 'duracion' or default 150 min)
		$duracion = 150;
		if ( function_exists( 'get_field' ) ) {
			$acf_duracion = get_field( 'duracion', $pelicula_id );
			if ( $acf_duracion ) {
				$duracion = absint( $acf_duracion );
			}
		}

		// Validar superposición
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );

		$inicio_ts = strtotime( "{$fecha} {$hora_inicio}" );
		$fin_ts    = $inicio_ts + ( $duracion * 60 );

		$query_conflict = $wpdb->prepare(
			"SELECT f.* FROM {$tabla_funciones} f WHERE f.sala_id = %d AND f.fecha = %s AND f.id != %d AND f.estado != 'cancelada'",
			$sala_id, $fecha, $id
		);
		$funciones_dia = $wpdb->get_results( $query_conflict );

		foreach ( $funciones_dia as $f_existente ) {
			$e_inicio_ts = strtotime( "{$f_existente->fecha} {$f_existente->hora_inicio}" );

			$e_duracion = 150;
			if ( function_exists( 'get_field' ) ) {
				$e_acf_duracion = get_field( 'duracion', $f_existente->pelicula_id );
				if ( $e_acf_duracion ) {
					$e_duracion = absint( $e_acf_duracion );
				}
			}
			$e_fin_ts = $e_inicio_ts + ( $e_duracion * 60 );

			if ( $inicio_ts < $e_fin_ts && $fin_ts > $e_inicio_ts ) {
				$pelicula_conflict = get_the_title( $f_existente->pelicula_id );
				$hora_conflict     = substr( $f_existente->hora_inicio, 0, 5 );
				set_transient( 'cnes_admin_notice', array(
					'type'    => 'error',
					'message' => sprintf( __( 'Conflicto de horario: La sala ya está ocupada por "%s" a las %s.', 'sala-estrella-manager' ), $pelicula_conflict, $hora_conflict ),
				), 30 );
				$this->render_form( $id );
				return;
			}
		}

		$data   = array(
			'pelicula_id'    => $pelicula_id,
			'sala_id'        => $sala_id,
			'fecha'          => $fecha,
			'hora_inicio'    => $hora_inicio,
			'precio_normal'  => $precio_normal,
			'precio_vip'     => $precio_vip,
			'formato_idioma' => $formato_idioma,
			'estado'         => $estado,
		);
		$format = array( '%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s' );

		if ( $id ) {
			$wpdb->update( $tabla_funciones, $data, array( 'id' => $id ), $format, array( '%d' ) );
			$message = __( 'Función actualizada correctamente.', 'sala-estrella-manager' );
		} else {
			$wpdb->insert( $tabla_funciones, $data, $format );
			$message = __( 'Función creada correctamente.', 'sala-estrella-manager' );
		}

		set_transient( 'cnes_admin_notice', array( 'type' => 'success', 'message' => $message ), 30 );
		wp_redirect( admin_url( 'admin.php?page=cnes-funciones' ) );
		exit;
	}

	/**
	 * Handle function duplication.
	 */
	private function handle_duplicate( $id ) {
		global $wpdb;
		$tabla   = CNES_Helpers::get_tabla( 'funciones' );
		$funcion = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tabla} WHERE id = %d", $id ) );

		if ( ! $funcion ) {
			wp_die( __( 'Función no encontrada.', 'sala-estrella-manager' ) );
		}

		$data = array(
			'pelicula_id'    => $funcion->pelicula_id,
			'sala_id'        => $funcion->sala_id,
			'fecha'          => '',
			'hora_inicio'    => '',
			'precio_normal'  => $funcion->precio_normal,
			'precio_vip'     => $funcion->precio_vip,
			'formato_idioma' => $funcion->formato_idioma,
			'estado'         => 'programada',
		);

		$wpdb->insert( $tabla, $data, array( '%d', '%d', '%s', '%s', '%f', '%f', '%s', '%s' ) );
		$new_id = $wpdb->insert_id;

		set_transient( 'cnes_admin_notice', array( 'type' => 'success', 'message' => __( 'Función duplicada. Por favor, asigna fecha y hora.', 'sala-estrella-manager' ) ), 30 );
		wp_redirect( admin_url( 'admin.php?page=cnes-funciones&action=edit&id=' . $new_id ) );
		exit;
	}

	/**
	 * Handle function cancellation.
	 */
	private function handle_cancel( $id ) {
		check_admin_referer( 'cnes_cancel_funcion_' . $id );

		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );

		$wpdb->update( $tabla_funciones, array( 'estado' => 'cancelada' ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
		$wpdb->update( $tabla_reservas, array( 'estado' => 'cancelado' ), array( 'funcion_id' => $id ), array( '%s' ), array( '%d' ) );

		set_transient( 'cnes_admin_notice', array( 'type' => 'success', 'message' => __( 'Función y reservas asociadas canceladas correctamente.', 'sala-estrella-manager' ) ), 30 );
		wp_redirect( admin_url( 'admin.php?page=cnes-funciones' ) );
		exit;
	}

	/**
	 * Handle function deletion.
	 */
	private function handle_delete( $id ) {
		check_admin_referer( 'cnes_delete_funcion_' . $id );

		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );

		$wpdb->delete( $tabla_reservas, array( 'funcion_id' => $id ), array( '%d' ) );
		$wpdb->delete( $tabla_funciones, array( 'id' => $id ), array( '%d' ) );

		set_transient( 'cnes_admin_notice', array( 'type' => 'success', 'message' => __( 'Función eliminada correctamente.', 'sala-estrella-manager' ) ), 30 );
		wp_redirect( admin_url( 'admin.php?page=cnes-funciones' ) );
		exit;
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
