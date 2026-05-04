<?php
/**
 * TEMPLATE NAME: Compra Final
 *
 * @package Cine_Sala_Estrella
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Redirección si WooCommerce no está activo
if ( ! class_exists( 'WooCommerce' ) ) {
	wp_redirect( home_url() );
	exit;
}

$cart = WC()->cart->get_cart();

// Validación: si el carrito está vacío, redirigir a la cartelera
if ( empty( $cart ) ) {
	wp_redirect( home_url( '/#cartelera' ) );
	exit;
}

/**
 * Extracción de Metadatos
 */
foreach ( $cart as $cart_item_key => $cart_item ) {
	$cnes = $cart_item['cnes_data'] ?? array();

	$pelicula_nombre        = $cnes['pelicula_nombre'] ?? 'Película';
	$sala_nombre            = $cnes['sala_nombre'] ?? '';
	$sede_info              = $cnes['sede'] ?? '';
	$asientos_seleccionados = $cnes['asientos_nombres'] ?? '';
	$funcion_id             = $cnes['funcion_id'] ?? 0;

	$fecha_raw   = $cnes['fecha'] ?? '';
	$hora_raw    = $cnes['hora'] ?? '';
	$funcion_data = '';
	if ( $fecha_raw ) {
		$funcion_data = date_i18n( get_option( 'date_format' ), strtotime( $fecha_raw ) )
		                . ' — ' . date( 'H:i', strtotime( $hora_raw ) ) . ' hrs';
	}

	$pelicula_id = $cnes['pelicula_id'] ?? 0;
	$poster      = $pelicula_id ? get_the_post_thumbnail_url( $pelicula_id, 'large' ) : '';
	$poster      = $poster ?: 'https://placehold.co/300x450/0D2B1A/27AE60?text=PELICULA';

	// Botón Volver Dinámico
	$url_volver = home_url( '/seleccion-asientos/' );
	if ( $funcion_id ) {
		$url_volver = add_query_arg( 'funcion_id', $funcion_id, $url_volver );
	}

	// Solo procesamos el primer item
	break;
}

get_header();
?>
  <main id="main" class="compra-final-page">
	<div class="container">
	  
	  <!-- Paso de Compra -->
	  <div class="mb-5 text-center">
		<span class="badge bg-success rounded-pill px-3 py-2 mb-5">Paso 2 de 2</span>
		<h2 class="font-display text-white">Finaliza tu compra</h2>
		
		<div id="compra-timer-container" class="mt-3 text-warning">
		  <i class="bi bi-clock-history me-2"></i>
		  Tiempo para completar tu compra: <strong id="compra-timer">05:00</strong>
		</div>
	  </div>

	  <div class="compra-card">
		<div class="compra-card__header text-center">
		  <h1 class="compra-card__title">Resumen de tu Pedido</h1>
		</div>

		<div class="compra-card__body">
		  
		  <!-- SECCIÓN: Película y Función -->
		  <div class="compra-section">
			<h3 class="compra-section__title">Detalle de la Función</h3>
			<div class="compra-movie-info">
			  <img src="<?php echo esc_url( $poster ); ?>" alt="Poster <?php echo esc_attr( $pelicula_nombre ); ?>" class="movie-poster-mini">
			  <div class="movie-details">
				<h4><?php echo esc_html( $pelicula_nombre ); ?></h4>
				<div class="movie-meta">
				  <span><i class="bi bi-calendar3 me-2"></i> <?php echo esc_html( $funcion_data ); ?></span>
				  <span><i class="bi bi-geo-alt me-2"></i> <?php echo esc_html( $sede_info ); ?></span>
				  <span><i class="bi bi-door-open me-2"></i> <?php echo esc_html( $sala_nombre ); ?></span>
				</div>
			  </div>
			</div>
		  </div>

		  <!-- SECCIÓN: Datos del Comprador -->
		  <div class="compra-section">
			<h3 class="compra-section__title">Datos del Comprador</h3>
			<div class="compra-form-grid">
			  <div class="form-group-static">
				<label for="cnes-correo-cliente" class="form-label-static">Correo Electrónico (Para recibir tus entradas)</label>
				<input type="email" id="cnes-correo-cliente" class="form-control bg-dark text-white border-secondary mt-2" placeholder="ejemplo@correo.com" required>
				<small class="text-secondary">Las entradas se enviarán a esta dirección.</small>
			  </div>
			</div>
		  </div>

		  <!-- SECCIÓN: Detalle de Pago -->
		  <div class="compra-section">
			<h3 class="compra-section__title">Detalle de Pago</h3>
			<table class="compra-table">
			  <tr>
				<td class="label">Asientos seleccionados:</td>
				<td class="value"><?php echo esc_html( $asientos_seleccionados ); ?></td>
			  </tr>
			  <tr class="row-total">
				<td class="total-label">Entradas (Total Carrito)</td>
				<td class="total-value"><?php echo WC()->cart->get_total(); ?></td>
			  </tr>
			</table>
		  </div>

		  <!-- SECCIÓN: Método de Pago -->
		  <div class="compra-section">
			<h3 class="compra-section__title">Método de Pago</h3>
			<div class="pago-container">
			  <div class="pago-info">
				<i class="bi bi-shield-check"></i>
				<div>
				  <p class="m-0 text-white fw-bold">Pago Seguro vía Webpay</p>
				  <p class="m-0 text-secondary" style="font-size: 0.8rem;">Serás redirigido a la plataforma de pago.</p>
				</div>
			  </div>
			  <div class="pago-logos">
				<i class="bi bi-credit-card"></i>
				<i class="bi bi-wallet2"></i>
				<img src="https://placehold.co/80x30/000000/FFFFFF?text=Webpay" alt="Webpay Logo" style="height: 20px;">
			  </div>
			</div>
		  </div>

		  <!-- ACCIONES -->
		  <div class="compra-acciones">
			<a href="<?php echo esc_url( $url_volver ); ?>" class="btn-cine btn-ghost">
			  <i class="bi bi-arrow-left"></i> Volver
			</a>
			<button id="btn-ir-a-pagar" class="btn-cine btn-lg btn-pa" disabled>
			  Ir a Pagar — <?php echo strip_tags( WC()->cart->get_total() ); ?> <i class="bi bi-credit-card-fill ms-2"></i>
			</button>
		  </div>

		  <!-- FOOTER INFORMATIVO -->
		  <div class="mt-4 text-center">
			<p class="text-secondary mb-0" style="font-size: 0.75rem;">
			  Las entradas no son reembolsables. Puedes cambiar horario hasta 2 horas antes de la función.<br>
			  Al hacer clic en "Ir a Pagar" aceptas nuestros términos y condiciones.
			</p>
		  </div>

		</div>
	  </div>

	</div>
  </main>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const emailInput = document.getElementById('cnes-correo-cliente');
	const btnPagar = document.getElementById('btn-ir-a-pagar');
	
	// Validación de Email
	function validateEmail(email) {
		const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}

	emailInput.addEventListener('input', function() {
		if (validateEmail(this.value)) {
			btnPagar.disabled = false;
			this.classList.remove('border-danger');
			this.classList.add('border-success');
		} else {
			btnPagar.disabled = true;
			this.classList.remove('border-success');
			if (this.value.length > 5) {
				this.classList.add('border-danger');
			}
		}
	});

	// Lógica de Envío
	btnPagar.addEventListener('click', function() {
		const email = emailInput.value;
		
		// Guardar email en sessionStorage para uso en checkout
		sessionStorage.setItem('cnes_customer_email', email);
		
		// Desactivar botón para evitar doble click
		btnPagar.disabled = true;
		btnPagar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando...';

		// Redirigir a Checkout de WooCommerce
		// Nota: En una implementación real, se podría enviar vía AJAX si el plugin lo requiere.
		window.location.href = '<?php echo esc_url( wc_get_checkout_url() ); ?>';
	});

	// Timer de 5 minutos
	let timeLeft = 300; 
	const storedTime = sessionStorage.getItem('cnes_timer_end');
	const now = Math.floor(Date.now() / 1000);
	
	if (storedTime) {
		timeLeft = parseInt(storedTime) - now;
		if (timeLeft <= 0) timeLeft = 0;
	} else {
		sessionStorage.setItem('cnes_timer_end', now + 300);
	}

	const timerDisplay = document.getElementById('compra-timer');
	
	function updateTimer() {
		if (timeLeft <= 0) {
			clearInterval(timerInterval);
			timerDisplay.innerText = "00:00";
			sessionStorage.removeItem('cnes_timer_end');
			alert('Tu tiempo de reserva ha expirado. Serás redirigido a la cartelera.');
			window.location.href = '<?php echo home_url( '/#cartelera' ); ?>';
			return;
		}

		let minutes = Math.floor(timeLeft / 60);
		let seconds = timeLeft % 60;
		timerDisplay.innerText = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
		timeLeft--;
	}

	updateTimer();
	const timerInterval = setInterval(updateTimer, 1000);
});
</script>

<?php 
get_footer();
?>
