<?php
/**
 * TEMPLATE NAME: Selección de Asientos
 *
 * @package Cine_Sala_Estrella
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Load data from URL param when rendered as a page template.
if ( ! isset( $data ) ) {
	$funcion_id = isset( $_GET['funcion_id'] ) ? absint( $_GET['funcion_id'] ) : 0;

	if ( ! $funcion_id ) {
		echo '<div class="alert alert-warning">Por favor, selecciona una función desde la cartelera.</div>';
		return;
	}

	$data = CNES_Public::get_seleccion_data( $funcion_id );

	if ( is_wp_error( $data ) ) {
		echo '<div class="alert alert-danger">' . esc_html( $data->get_error_message() ) . '</div>';
		return;
	}

	// Enqueue assets for this page load.
	$public = new CNES_Public();
	$public->enqueue_selection_assets( $data );
}

$funcion  = $data['funcion'];
$pelicula = $data['pelicula'];
$sede_label = ( 'punta_arenas' === $funcion->sede ) ? 'Punta Arenas' : 'Puerto Natales';

get_header();
?>
<main id="main" class="seleccion-asientos-page">
	<div id="cnes-seleccion-asientos" class="container my-5">
		<!-- Paso de Compra -->
		  <div class="mb-5 text-center">
			<span class="badge bg-success rounded-pill px-3 py-2 mb-5">Paso 1 de 2</span>
			<h2 class="font-display text-white">Selecciona tus asientos</h2>
		  </div>
	
		<div class="funcion-header">
					<?php if ( has_post_thumbnail( $pelicula->ID ) ) : ?>
						<img src="<?php echo get_the_post_thumbnail_url( $pelicula->ID ); ?>" alt="<?php echo esc_attr( $pelicula->post_title ); ?>" class="funcion-poster">
					<?php endif; ?>
				<div class="funcion-info">
					<h1 class="h2 mb-2"><?php echo esc_html( $pelicula->post_title ); ?></h1>
					<div class="funcion-detalles">
						<span><i class="bi bi-geo-alt"></i> <?php echo esc_html( $sede_label ); ?> — <?php echo esc_html( $funcion->sala_nombre ); ?></span>
						<span><i class="bi bi-calendar-event"></i> <?php echo date_i18n( 'j \d\e F, Y', strtotime( $funcion->fecha ) ); ?></span>
						<span><i class="bi bi-clock"></i> <?php echo date( 'H:i', strtotime( $funcion->hora_inicio ) ); ?> hrs</span>
						<span><i class="bi bi-translate"></i> <?php echo esc_html( $funcion->formato_idioma ); ?></span>
					</div>
				</div>
		</div>
	
		<div class="row">
			<!-- Mapa de Asientos -->
			<div class="col-lg-8 mb-4">
				<div class="sala-container">
						<div class="pantalla-wrapper">
							<div class="pantalla mb-2 mx-auto" style="height: 8px; width: 80%; background: #ccc; border-radius: 50% / 100% 100% 0 0; box-shadow: 0 10px 20px rgba(0,0,0,0.1);"></div>
							<small class="pantalla-texto">Pantalla</small>
						</div>
	
						<div id="mapaAsientos" class="mapa-asientos">
							<div class="spinner-border text-primary" role="status">
								<span class="visually-hidden">Cargando mapa...</span>
							</div>
						</div>
	
						<div class="leyenda d-flex justify-content-center flex-wrap gap-4 mt-5 pt-4 border-top">
							<div class="d-flex align-items-center gap-2">
								<div class="asiento" style="width: 24px; height: 24px; background: var(--color-bg-card); border: 1px solid rgba(255, 255, 255, 0.15); cursor: default;"></div>
								<small>Disponible</small>
							</div>
							<div class="d-flex align-items-center gap-2">
								<div class="asiento seleccionado" style="width: 24px; height: 24px; background-color: var(--verde-hover; cursor: default;"></div>
								<small>Tu Selección</small>
							</div>
							<div class="d-flex align-items-center gap-2">
								<div class="asiento ocupado" style="width: 24px; height: 24px; background: ##2c3e50; cursor: default;"></div>
								<small>Ocupado</small>
							</div>
						</div>
				</div>
			</div>
	
			<!-- Resumen de Compra -->
			<div class="col-lg-4">
				<div class="resumen-compra">
					<div class="resumen-titulo">
						<h5 class="mb-0">Resumen de Selección</h5>
					</div>
					<div class="card-body p-4">
						<div id="resumenSeleccion" class="mb-4">
							<p class="text-muted text-center">No has seleccionado ningún asiento aún.</p>
						</div>
	
						<div id="timerContainer" class="alert alert-warning d-none text-center mb-4">
							<i class="bi bi-clock-history"></i> Tiempo restante: <strong id="timer">5:00</strong>
						</div>
	
						<hr>
	
						<div class="resumen-total">
							<span class="total-label">Total</span>
							<span class="total-valor" id="totalPrecio">$0</span>
						</div>

						<div class="resumen-acciones">
							<button id="btnContinuar" class="btn-cine btn-lg btn-pa w-100" disabled>
								<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
								Finalizar Compra
							</button>
		
							<button id="btnCancelar" class="btn-cine btn-ghost w-100">
								Cancelar y Volver
							</button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</main>

<!-- Modal de Expiración -->
<div class="modal fade" id="modalExpiracion" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title text-white">¡Tiempo agotado!</h5>
			</div>
			<div class="modal-body">
				<p class="text-white">Tu sesión de reserva ha expirado. Los asientos han sido liberados para que otros usuarios puedan comprarlos.</p>
			</div>
			<div class="modal-footer">
				<a href="<?php echo esc_url( home_url( '/#cartelera' ) ); ?>" class="btn btn-primary">Volver a Cartelera</a>
			</div>
		</div>
	</div>
</div>
<?php 
get_footer();
?>