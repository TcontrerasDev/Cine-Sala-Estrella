<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Cine_Sala_Estrella
 */

?>
<div class="single-pagina__inner">

      <!-- Volver -->
      <a href="<?php echo home_url('/#cartelera'); ?>" class="single-pagina__back">
        <i class="bi bi-arrow-left"></i>
        Volver a cartelera
      </a>

      <!-- ═══════════════════════════════════════════════════════
           FICHA PELÍCULA
      ═══════════════════════════════════════════════════════ -->
      <article class="single-pelicula" aria-labelledby="peliculaTitulo">

        <div class="modal-pelicula__grid">

          <!-- POSTER -->
          <div class="modal-pelicula__poster-col">
            <?php cine_sala_estrella_post_thumbnail(); ?>
            <div class="modal-pelicula__poster-overlay"></div>
          </div>

          <!-- INFO -->
          <div class="modal-pelicula__info-col">

            <h1 class="modal-pelicula__titulo" id="peliculaTitulo"><?php echo get_the_title() ?></h1>

			<?php
				$clasificacion = get_the_terms( $post->ID, 'clasificacion' );
			?>
            <div class="modal-pelicula__badges">
              <span class="badge-cine badge-clasificacion"><?php echo !empty( $clasificacion ) ? $clasificacion[0]->name : 'Sin clasificación'; ?></span>
            <?php 
			$generos = get_the_terms( $post->ID, 'genero' ); 
			foreach ( $generos as $genero ) {
				echo '<span class="badge-cine badge-genero">' . $genero->name . '</span>';
			}
			?>  
            </div>

            <div class="modal-pelicula__meta-grid">
              <div class="modal-pelicula__meta-item">
                <span class="modal-pelicula__meta-label">Director</span>
                <span class="modal-pelicula__meta-value"><?php the_field('director') ?></span>
              </div>
              <div class="modal-pelicula__meta-item">
                <span class="modal-pelicula__meta-label">Duración</span>
                <span class="modal-pelicula__meta-value">
                  <i class="bi bi-clock"></i>
                  <?php the_field('duracion') ?>
                </span>
              </div>
              <div class="modal-pelicula__meta-item">
                <span class="modal-pelicula__meta-label">Clasificación</span>
                <span class="modal-pelicula__meta-value">
                  <?php echo !empty( $clasificacion ) ? $clasificacion[0]->name : 'Sin clasificación'; ?>
                </span>
              </div>
              <div class="modal-pelicula__meta-item">
                <span class="modal-pelicula__meta-label">Reparto</span>
                <div class="modal-pelicula__sedes-badges">
                  <span class="modal-pelicula__meta-value"><?php the_field('reparto') ?></span>
                </div>
              </div>
            </div>

            <!-- SINOPSIS -->
            <div>
              <p class="modal-pelicula__sinopsis-label">Sinopsis</p>
              <p class="modal-pelicula__sinopsis">
                <?php the_field('sinopsis'); ?>
              </p>
            </div>

            <!-- HORARIOS -->
			<?php 
			$pelicula_id = get_the_ID();
			// Usamos la función que ya agrupa por sede y fecha
			$funciones_agrupadas = CNES_Public::get_funciones_por_pelicula_agrupadas( $pelicula_id );
			?>

			<div class="modal-pelicula__horarios-container">
				<?php if ( ! empty( $funciones_agrupadas ) ) { ?>
					
					<?php foreach ( $funciones_agrupadas as $sede => $fechas ) {?>
						<div class="modal-pelicula__sede-group mb-4">
							
							<p class="modal-pelicula__horarios-label">
								<i class="bi bi-geo-alt-fill"></i>
								Horarios — <?php echo esc_html( $sede ); ?>
							</p>

							<?php foreach ( $fechas as $fecha => $lista_funciones ) { ?>
								<div class="modal-pelicula__fecha-row mb-3">
									
									<span class="modal-pelicula__fecha-label d-block mb-2 small text-uppercase">
										<?php echo date_i18n( 'l d \d\e F', strtotime( $fecha ) ); ?>
									</span>

									<div class="modal-pelicula__horarios-grid d-flex flex-wrap gap-2">
										<?php foreach ( $lista_funciones as $funcion ) { 
											$hora = date( 'H:i', strtotime( $funcion->hora_inicio ) );
                      $idioma = $funcion->formato_idioma;
										?>
											<button class="modal-pelicula__horario-chip" type="button" data-funcion-id="<?php echo esc_attr($funcion->id); ?>">
												<?php echo $hora . " - " . $idioma; ?>
											</button>
										<?php } ?>
									</div>

								</div>
							<?php } ?>

						</div>
					<?php } ?>

				<?php } else {?>
					<p class="text-muted">No hay funciones programadas para los próximos días.</p>
				<?php } ?>
			</div>

            <!-- TRAILER -->
            <div>
              <p class="modal-pelicula__sinopsis-label modal-pelicula__trailer-label">Tráiler oficial</p>
              <div class="modal-pelicula__trailer">
				<?php the_field('embed_youtube') ?>
              </div>
            </div>

            <!-- CTA -->
            <div class="modal-pelicula__cta" id="ctaComprar">
              <a href="#" id="btnComprarEntradas" class="btn-cine btn-lg btn-pa disabled" aria-disabled="true">
                <i class="bi bi-ticket-perforated-fill"></i>
                Selecciona un horario
              </a>
            </div>

          </div><!-- /.modal-pelicula__info-col -->
        </div><!-- /.modal-pelicula__grid -->

      </article>

    </div>

<script>
(function () {
  var chips = document.querySelectorAll('.modal-pelicula__horario-chip');
  var btn   = document.getElementById('btnComprarEntradas');
  var base  = '<?php echo esc_js( home_url('/seleccion-asientos/') ); ?>';

  chips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      chips.forEach(function (c) { c.classList.remove('active'); });
      chip.classList.add('active');

      var id = chip.getAttribute('data-funcion-id');
      btn.setAttribute('href', base + '?funcion_id=' + id);
      btn.classList.remove('disabled');
      btn.removeAttribute('aria-disabled');
      btn.querySelector('i').nextSibling.textContent = ' Comprar entradas';

      document.getElementById('ctaComprar').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });
  });
})();
</script>
