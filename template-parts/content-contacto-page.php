<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Cine_Sala_Estrella
 */

?>
<!-- ═══════════════════════════════════════════════════════
         HERO CONTACTO
═══════════════════════════════════════════════════════ -->
<section class="hero-contacto" aria-label="Contacto">
	<div class="hero-contacto__inner">
		<span class="label-seccion"><?php the_field('antetitulo_contacto') ?></span>
		<h1 class="hero-contacto__titulo">
			<?= get_the_title() ?>
		</h1>
		<p class="hero-contacto__subtitulo">
			<?php the_field('subtitulo_contacto') ?>
		</p>
	</div>
</section>

<!-- ═══════════════════════════════════════════════════════
         SEDES CONTACTO
═══════════════════════════════════════════════════════ -->
<section class="contacto-main" aria-labelledby="tituloContactoSedes">
	<div class="contacto-main__inner">

		<span class="label-seccion"><?php the_field('antetitulo_sede') ?></span>
		<h2 class="titulo-seccion" id="tituloContactoSedes"><?php the_field('titulo_sede') ?></h2>

		<div class="contacto-sedes__grid">
			
			<?php 
			$get_sedes = get_field('sedes');
			if ($get_sedes) {
				foreach ($get_sedes as $sede) {
					$sede_ciudad = $sede['ciudad_form'];
					$sede_direccion = $sede['direccion_sede'];
					$sede_telefono = $sede['telefono_sede'];
					$sede_email = $sede['mail_sede'];
					$sede_horario = $sede['horario_atencion_sede'];
					$sede_mapa = $sede['mapa_sede'];
					$sede_color = $sede['color_sede'];
			?>
			<article class="contacto-sede-card <?= $sede_color ?> reveal-element" aria-labelledby="contactoPATitulo">
				<div class="contacto-sede-card__header">
					<span class="contacto-sede-card__dot"></span>
					<h3 class="contacto-sede-card__nombre" id="contactoPATitulo"><?= $sede_ciudad ?></h3>
				</div>
				<div class="contacto-sede-card__body">

					<ul class="contacto-info-list m-0 p-0">
						<?php if ($sede_direccion) { ?>
							<li class="contacto-info-item">
								<div class="contacto-info-item__icon">
									<i class="bi bi-geo-alt-fill"></i>
								</div>
								<div class="contacto-info-item__content">
									<p class="contacto-info-item__label">Dirección</p>
									<p class="contacto-info-item__value"><?= $sede_direccion ?></p>
								</div>
							</li>
						<?php } ?>
						<?php if ($sede_telefono) { ?>
						<li class="contacto-info-item">
							<div class="contacto-info-item__icon">
								<i class="bi bi-telephone-fill"></i>
							</div>
							<div class="contacto-info-item__content">
								<p class="contacto-info-item__label">Teléfono</p>
								<p class="contacto-info-item__value">
									<a href="tel:+56612223456"><?= $sede_telefono ?></a>
								</p>
							</div>
						</li>
						<?php } ?>
						<?php if ($sede_email) { ?>
						<li class="contacto-info-item">
							<div class="contacto-info-item__icon">
								<i class="bi bi-envelope-fill"></i>
							</div>
							<div class="contacto-info-item__content">
								<p class="contacto-info-item__label">Email</p>
								<p class="contacto-info-item__value">
									<a href="mailto:puntaarenas@cineestrella.cl"><?= $sede_email ?></a>
								</p>
							</div>
						</li>
						<?php } ?>
						<?php if ($sede_horario) { ?>
						<li class="contacto-info-item">
							<div class="contacto-info-item__icon">
								<i class="bi bi-clock-fill"></i>
							</div>
							<div class="contacto-info-item__content">
								<p class="contacto-info-item__label">Horario de atención</p>
								<p class="contacto-info-item__value"><?= $sede_horario ?></p>
							</div>
						</li>
						<?php } ?>
					</ul>

					<!-- Mapa Punta Arenas -->
					<div class="contacto-mapa mt-3" aria-label="Mapa ubicación Punta Arenas">
						<div class="contacto-mapa__placeholder">
							<?= $sede_mapa ?>
						</div>
					</div>

				</div>
			</article>
			<?php 
				}
			}
			?>	
		</div>
	</div>
</section>

<!-- ═══════════════════════════════════════════════════════
         FORMULARIO
═══════════════════════════════════════════════════════ -->
<section class="contacto-form-section" aria-labelledby="tituloFormulario">
	<div class="contacto-form-section__inner">

		<div class="contacto-form-grid">

			<!-- Columna info -->
			<div>
				<span class="label-seccion"><?php the_field('antetitulo_form') ?></span>
				<h2 class="titulo-seccion" id="tituloFormulario"><?php the_field('titulo_form') ?></h2>
				<p class="contacto-form__intro">
				<?php the_field('texto_form') ?>
				</p>
				<?php 
				$get_caracteristicas = get_field('caracteristicas_form');
				if ($get_caracteristicas) {
					foreach ($get_caracteristicas as $caracteristica) {
						$caracteristica_icono = $caracteristica['icono_caracteristicas'];
						$caracteristica_titulo = $caracteristica['titulo_caracteristicas'];
						$caracteristica_texto = $caracteristica['texto_caracteristicas'];
				?>

					<div class="contacto-features">
						<div class="contacto-feature">
							<div class="contacto-feature__icon">
								<i class="bi <?= $caracteristica_icono ?>"></i>
							</div>
							<div>
								<p class="contacto-feature__title"><?= $caracteristica_titulo ?></p>
								<p class="contacto-feature__desc"><?= $caracteristica_texto ?></p>
							</div>
						</div>
					</div>

				<?php }
				}?>
			</div>

			<!-- Formulario -->
			<div>
				<?php echo do_shortcode('[fluentform id="3"]'); ?>
			</div>
		</div><!-- /.contacto-form-grid -->
	</div>
</section>