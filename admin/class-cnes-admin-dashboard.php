<?php
/**
 * Admin Dashboard page logic and rendering.
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles data retrieval and HTML rendering for the main dashboard.
 */
class CNES_Admin_Dashboard {

	/**
	 * Render the Dashboard admin page.
	 */
	public static function render_page() {
		$indicators = self::get_indicators();
		$upcoming   = self::get_upcoming_functions();
		$sales      = self::get_recent_sales();
		$alerts     = self::get_alerts();
		?>
		<div class="wrap cnes-dashboard-wrap">
			<h1><?php esc_html_e( 'Dashboard — Cine Sala Estrella', 'sala-estrella-manager' ); ?></h1>

			<!-- Indicators Row -->
			<div class="cnes-dashboard-indicators">
				<?php foreach ( $indicators as $key => $data ) : ?>
					<div class="cnes-indicator-card">
						<span class="cnes-indicator-value"><?php echo esc_html( $data['value'] ); ?></span>
						<span class="cnes-indicator-label"><?php echo esc_html( $data['label'] ); ?></span>
						<?php if ( ! empty( $data['breakdown'] ) ) : ?>
							<div class="cnes-indicator-breakdown">
								<?php echo esc_html( $data['breakdown'] ); ?>
							</div>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="cnes-dashboard-columns">
				<!-- Upcoming Functions -->
				<div class="cnes-dashboard-box">
					<h2><?php esc_html_e( 'Funciones próximas', 'sala-estrella-manager' ); ?></h2>
					<div class="inside">
						<?php if ( empty( $upcoming ) ) : ?>
							<div class="cnes-empty-state" style="padding: 20px;">
								<?php esc_html_e( 'No hay funciones programadas próximamente.', 'sala-estrella-manager' ); ?>
							</div>
						<?php else : ?>
							<table>
								<thead>
									<tr>
										<th><?php esc_html_e( 'Película', 'sala-estrella-manager' ); ?></th>
										<th><?php esc_html_e( 'Sede', 'sala-estrella-manager' ); ?></th>
										<th><?php esc_html_e( 'Fecha/Hora', 'sala-estrella-manager' ); ?></th>
										<th><?php esc_html_e( 'Asientos', 'sala-estrella-manager' ); ?></th>
										<th><?php esc_html_e( 'Estado', 'sala-estrella-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $upcoming as $func ) : ?>
										<tr>
											<td><strong><?php echo esc_html( get_the_title( $func->pelicula_id ) ); ?></strong></td>
											<td><?php echo esc_html( $func->sede ); ?></td>
											<td>
												<?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $func->fecha ) ) ); ?><br>
												<small><?php echo esc_html( date( 'H:i', strtotime( $func->hora_inicio ) ) ); ?></small>
											</td>
											<td>
												<?php echo esc_html( "{$func->vendidos} / {$func->capacidad}" ); ?>
											</td>
											<td>
												<span class="cnes-badge cnes-badge-<?php echo esc_attr( $func->estado ); ?>">
													<?php echo esc_html( $func->estado ); ?>
												</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
							<div class="cnes-box-footer">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=cnes-funciones' ) ); ?>" class="button button-link">
									<?php esc_html_e( 'Ver todas las funciones →', 'sala-estrella-manager' ); ?>
								</a>
							</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Recent Sales -->
				<div class="cnes-dashboard-box">
					<h2><?php esc_html_e( 'Ventas recientes', 'sala-estrella-manager' ); ?></h2>
					<div class="inside">
						<?php if ( empty( $sales ) ) : ?>
							<div class="cnes-empty-state" style="padding: 20px;">
								<?php esc_html_e( 'Aún no hay ventas registradas.', 'sala-estrella-manager' ); ?>
							</div>
						<?php else : ?>
							<table>
								<thead>
									<tr>
										<th><?php esc_html_e( 'Orden', 'sala-estrella-manager' ); ?></th>
										<th><?php esc_html_e( 'Película', 'sala-estrella-manager' ); ?></th>
										<th><?php esc_html_e( 'Entradas', 'sala-estrella-manager' ); ?></th>
										<th><?php esc_html_e( 'Total', 'sala-estrella-manager' ); ?></th>
										<th><?php esc_html_e( 'Estado', 'sala-estrella-manager' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $sales as $sale ) : ?>
										<tr>
											<td>
												<a href="<?php echo esc_url( get_edit_post_link( $sale['id'] ) ); ?>">
													#<?php echo esc_html( $sale['id'] ); ?>
												</a>
												<br><small><?php echo esc_html( $sale['fecha'] ); ?></small>
											</td>
											<td><?php echo esc_html( $sale['pelicula'] ); ?></td>
											<td><?php echo esc_html( $sale['cantidad'] ); ?></td>
											<td><?php echo esc_html( CNES_Helpers::format_price( $sale['total'] ) ); ?></td>
											<td>
												<span class="cnes-badge cnes-badge-<?php echo esc_attr( $sale['estado'] ); ?>">
													<?php echo esc_html( $sale['estado'] ); ?>
												</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Alerts -->
			<?php if ( ! empty( $alerts ) ) : ?>
				<div class="cnes-alerts-container">
					<?php foreach ( $alerts as $alert ) : ?>
						<div class="cnes-alert-item cnes-alert-<?php echo esc_attr( $alert['type'] ); ?>">
							<span class="dashicons dashicons-<?php echo esc_attr( $alert['icon'] ); ?>"></span>
							<div class="cnes-alert-content">
								<?php echo wp_kses_post( $alert['message'] ); ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Calculate indicator values for the top row.
	 */
	private static function get_indicators() {
		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );
		$tabla_salas     = CNES_Helpers::get_tabla( 'salas' );
		$tabla_asientos  = CNES_Helpers::get_tabla( 'asientos' );

		$today = current_time( 'Y-m-d' );

		// 1. Funciones hoy
		$func_hoy_q = $wpdb->get_results( $wpdb->prepare(
			"SELECT s.sede, COUNT(f.id) as count 
			 FROM {$tabla_funciones} f 
			 JOIN {$tabla_salas} s ON f.sala_id = s.id 
			 WHERE f.fecha = %s AND f.estado IN ('programada', 'en_venta') 
			 GROUP BY s.sede",
			$today
		) );
		$func_hoy_total = 0;
		$func_hoy_br    = array();
		foreach ( $func_hoy_q as $row ) {
			$func_hoy_total += $row->count;
			$func_hoy_br[] = ( 'punta_arenas' === $row->sede ? 'PA' : 'PN' ) . ": {$row->count}";
		}

		// 2. Entradas vendidas hoy
		$vendidas_hoy_q = $wpdb->get_results( $wpdb->prepare(
			"SELECT s.sede, COUNT(r.id) as count 
			 FROM {$tabla_reservas} r 
			 JOIN {$tabla_funciones} f ON r.funcion_id = f.id 
			 JOIN {$tabla_salas} s ON f.sala_id = s.id 
			 WHERE f.fecha = %s AND r.estado = 'pagado' 
			 GROUP BY s.sede",
			$today
		) );
		$vendidas_hoy_total = 0;
		$vendidas_hoy_br    = array();
		foreach ( $vendidas_hoy_q as $row ) {
			$vendidas_hoy_total += $row->count;
			$vendidas_hoy_br[] = ( 'punta_arenas' === $row->sede ? 'PA' : 'PN' ) . ": {$row->count}";
		}

		// 3. Ingresos del día
		$ingresos_q = $wpdb->get_results( $wpdb->prepare(
			"SELECT s.sede, 
			        SUM(CASE WHEN a.tipo = 'vip' THEN f.precio_vip ELSE f.precio_normal END) as total
			 FROM {$tabla_reservas} r 
			 JOIN {$tabla_funciones} f ON r.funcion_id = f.id 
			 JOIN {$tabla_asientos} a ON r.asiento_id = a.id 
			 JOIN {$tabla_salas} s ON f.sala_id = s.id 
			 WHERE f.fecha = %s AND r.estado = 'pagado' 
			 GROUP BY s.sede",
			$today
		) );
		$ingresos_total = 0;
		$ingresos_br    = array();
		foreach ( $ingresos_q as $row ) {
			$ingresos_total += $row->total;
			$ingresos_br[] = ( 'punta_arenas' === $row->sede ? 'PA' : 'PN' ) . ": " . CNES_Helpers::format_price( $row->total );
		}

		// 4. Funciones semana
		$next_week = date( 'Y-m-d', strtotime( '+7 days', strtotime( $today ) ) );
		$func_semana_q = $wpdb->get_results( $wpdb->prepare(
			"SELECT s.sede, COUNT(f.id) as count 
			 FROM {$tabla_funciones} f 
			 JOIN {$tabla_salas} s ON f.sala_id = s.id 
			 WHERE f.fecha BETWEEN %s AND %s AND f.estado IN ('programada', 'en_venta') 
			 GROUP BY s.sede",
			$today, $next_week
		) );
		$func_semana_total = 0;
		$func_semana_br    = array();
		foreach ( $func_semana_q as $row ) {
			$func_semana_total += $row->count;
			$func_semana_br[] = ( 'punta_arenas' === $row->sede ? 'PA' : 'PN' ) . ": {$row->count}";
		}

		return array(
			'func_hoy' => array(
				'label'     => __( 'Funciones hoy', 'sala-estrella-manager' ),
				'value'     => $func_hoy_total,
				'breakdown' => implode( ' / ', $func_hoy_br ) ?: __( 'Sin funciones hoy', 'sala-estrella-manager' ),
			),
			'vendidas_hoy' => array(
				'label'     => __( 'Entradas vendidas hoy', 'sala-estrella-manager' ),
				'value'     => $vendidas_hoy_total,
				'breakdown' => implode( ' / ', $vendidas_hoy_br ) ?: '0',
			),
			'ingresos_hoy' => array(
				'label'     => __( 'Ingresos del día', 'sala-estrella-manager' ),
				'value'     => CNES_Helpers::format_price( $ingresos_total ),
				'breakdown' => implode( ' / ', $ingresos_br ) ?: '$0',
			),
			'func_semana' => array(
				'label'     => __( 'Funciones esta semana', 'sala-estrella-manager' ),
				'value'     => $func_semana_total,
				'breakdown' => implode( ' / ', $func_semana_br ) ?: '0',
			),
		);
	}

	/**
	 * Get next 10 functions.
	 */
	private static function get_upcoming_functions() {
		global $wpdb;
		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_salas     = CNES_Helpers::get_tabla( 'salas' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );

		$today = current_time( 'Y-m-d' );
		$now   = current_time( 'H:i:s' );

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT f.*, s.nombre as sala_nombre, s.sede, s.capacidad,
			        (SELECT COUNT(*) FROM {$tabla_reservas} r WHERE r.funcion_id = f.id AND r.estado = 'pagado') as vendidos
			 FROM {$tabla_funciones} f
			 JOIN {$tabla_salas} s ON f.sala_id = s.id
			 WHERE f.fecha > %s OR (f.fecha = %s AND f.hora_inicio >= %s)
			 ORDER BY f.fecha ASC, f.hora_inicio ASC
			 LIMIT 10",
			$today, $today, $now
		) );
	}

	/**
	 * Get 10 recent WooCommerce orders with tickets.
	 */
	private static function get_recent_sales() {
		if ( ! class_exists( 'WooCommerce' ) ) return array();

		// We look for orders containing the generic cinema ticket product
		$product_id = CNES_Helpers::get_producto_entrada_id();
		
		$args = array(
			'limit'   => 10,
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => array( 'wc-processing', 'wc-completed', 'wc-cancelled' ),
		);

		$orders = wc_get_orders( $args );
		$results = array();

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				if ( (int) $item->get_product_id() === (int) $product_id ) {
					$results[] = array(
						'id'       => $order->get_id(),
						'pelicula' => $item->get_meta( '_cnes_pelicula_nombre' ) ?: get_the_title( $item->get_meta( '_cnes_pelicula_id' ) ),
						'cantidad' => $item->get_quantity(),
						'total'    => $order->get_total(),
						'estado'   => $order->get_status(),
						'fecha'    => $order->get_date_created()->date_i18n( 'd/m/Y H:i' ),
					);
					break; // Found one line item with tickets, move to next order
				}
			}
			if ( count( $results ) >= 10 ) break;
		}

		return $results;
	}

	/**
	 * Get alerts for the dashboard.
	 */
	private static function get_alerts() {
		global $wpdb;
		$alerts = array();
		$today  = current_time( 'Y-m-d' );
		$tomorrow = date( 'Y-m-d', strtotime( '+1 day', strtotime( $today ) ) );

		$tabla_funciones = CNES_Helpers::get_tabla( 'funciones' );
		$tabla_reservas  = CNES_Helpers::get_tabla( 'reservas' );
		$tabla_salas     = CNES_Helpers::get_tabla( 'salas' );

		// 1. Funciones agotadas hoy
		$agotadas = $wpdb->get_results( $wpdb->prepare(
			"SELECT f.id, f.pelicula_id FROM {$tabla_funciones} f WHERE f.fecha = %s AND f.estado = 'agotada'",
			$today
		) );
		foreach ( $agotadas as $f ) {
			$alerts[] = array(
				'type'    => 'info',
				'icon'    => 'info',
				'message' => sprintf( __( 'La función de <strong>%s</strong> para hoy está agotada.', 'sala-estrella-manager' ), get_the_title( $f->pelicula_id ) ),
			);
		}

		// 2. Funciones sin ventas (hoy/mañana)
		$sin_ventas = $wpdb->get_results( $wpdb->prepare(
			"SELECT f.id, f.pelicula_id, f.fecha, f.hora_inicio 
			 FROM {$tabla_funciones} f 
			 LEFT JOIN {$tabla_reservas} r ON f.id = r.funcion_id AND r.estado = 'pagado'
			 WHERE f.fecha IN (%s, %s) AND f.estado IN ('programada', 'en_venta')
			 GROUP BY f.id
			 HAVING COUNT(r.id) = 0",
			$today, $tomorrow
		) );
		if ( ! empty( $sin_ventas ) ) {
			$alerts[] = array(
				'type'    => 'warning',
				'icon'    => 'warning',
				'message' => sprintf( __( 'Hay %d funciones para hoy/mañana que aún no tienen ninguna entrada vendida.', 'sala-estrella-manager' ), count( $sin_ventas ) ),
			);
		}

		// 3. Películas sin póster
		$sin_poster = new WP_Query( array(
			'post_type'      => 'pelicula',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_thumbnail_id',
					'compare' => 'NOT EXISTS',
				),
			),
		) );
		if ( $sin_poster->found_posts > 0 ) {
			$alerts[] = array(
				'type'    => 'warning',
				'icon'    => 'warning',
				'message' => sprintf( __( 'Hay %d películas publicadas que no tienen imagen de póster configurada.', 'sala-estrella-manager' ), $sin_poster->found_posts ),
			);
		}

		// 4. Funciones sin precio
		$sin_precio = $wpdb->get_results( "SELECT COUNT(*) as count FROM {$tabla_funciones} WHERE precio_normal = 0 AND estado = 'en_venta'" );
		if ( $sin_precio[0]->count > 0 ) {
			$alerts[] = array(
				'type'    => 'error',
				'icon'    => 'error',
				'message' => sprintf( __( '¡Atención! Hay %d funciones "en venta" con precio $0.', 'sala-estrella-manager' ), $sin_precio[0]->count ),
			);
		}

		return $alerts;
	}
}
