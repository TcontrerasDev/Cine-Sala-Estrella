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
         HERO HISTORIA
═══════════════════════════════════════════════════════ -->
<section class="hero-historia" aria-label="Historia del cine">
	<div class="hero-historia__bg" aria-hidden="true"></div>
	<div class="hero-historia__overlay" aria-hidden="true"></div>
	<div class="hero-historia__content">
		<span class="hero-historia__eyebrow"><?php the_field('antetitulo_historia'); ?></span>
		<h1 class="hero-historia__titulo">
			<?= get_the_title(); ?>
		</h1>
		<p class="hero-historia__subtitulo">
			<?php the_field('bajada_historia'); ?>
		</p>
	</div>
</section>

 <!-- ═══════════════════════════════════════════════════════
         INTRO ESTADÍSTICAS
═══════════════════════════════════════════════════════ -->
<section class="historia-intro" aria-labelledby="tituloHistoriaIntro">
	<div class="historia-intro__inner">
		<div class="historia-intro__grid">

			<div>
				<span class="label-seccion"><?php the_field('antetitulo_identidad'); ?></span>
				<h2 class="titulo-seccion" id="tituloHistoriaIntro"><?php the_field('titulo_identidad'); ?></h2>
				<p class="historia-intro__text">
					<?php the_field('texto_identidad'); ?>
				</p>

				<div class="historia-intro__stats">
					<?php 
					$stats_cine = get_field('estadisticas_identidad');
					if( $stats_cine ) {
						foreach( $stats_cine as $stat ) { ?>

						<div class="historia-stat <?= $stat['color_estats'] ?> reveal-element">
							<div class="historia-stat__numero"><?= $stat['numero_estats'] ?></div>
							<div class="historia-stat__label"><?= $stat['etiqueta_estats'] ?></div>
						</div>

					<?php }
					}?>
				</div>
			</div>

			<?php 
			$img_identidad = get_field('imagen_identidad');
				if( $img_identidad ) {?>
					<div class="historia-intro__imagen reveal-element">
						<img
							src="<?= esc_url($img_identidad['url']); ?>"
							alt="<?= esc_attr($img_identidad['alt']); ?>"
							loading="lazy"
						>
					</div>
			<?php } ?>

		</div>
	</div>
</section>

<!-- ═══════════════════════════════════════════════════════
         LÍNEA DE TIEMPO
═══════════════════════════════════════════════════════ -->
<section class="historia-timeline" aria-labelledby="tituloTimeline">
	<div class="historia-timeline__inner">

		<div class="historia-timeline__header">
			<span class="label-seccion"><?php the_field('etiqueta_timeline'); ?></span>
			<h2 class="titulo-seccion" id="tituloTimeline"><?php the_field('titulo_timeline'); ?></h2>
		</div>

		<div class="timeline" role="list" aria-label="Hitos históricos del Cine Sala Estrella">

			<?php
			$line_time = get_field( 'linea_tiempo' );
			if ( $line_time ) {
				$total    = count( $line_time );
				$contador = 1;
				foreach ( $line_time as $fila ) {
					$ano      = esc_html( $fila['ano'] );
					$titulo   = esc_html( $fila['titulo_line'] );
					$texto    = esc_html( $fila['texto_line'] );
					$imagen   = $fila['insertar_imagen'];
					$es_ultima = ( $contador === $total );
					$es_impar  = ( $contador % 2 !== 0 );
			?>

			<?php if ( $es_ultima && $es_impar ) { ?>

				<div class="timeline__item timeline__item--hoy" role="listitem">
					<div class="timeline__content-left">
						<div class="timeline__card" tabindex="0">
							<h3 class="timeline__card-titulo"><?= $titulo ?></h3>
							<p class="timeline__card-texto"><?= $texto ?></p>
							<?php if ( $imagen ) { ?>
							<div class="timeline__card-foto">
								<img src="<?= esc_url( $imagen['url'] ) ?>" alt="<?= esc_attr( $imagen['alt'] ) ?>" loading="lazy">
							</div>
							<?php } ?>
						</div>
					</div>
					<div class="timeline__dot" aria-hidden="true"></div>
					<div class="timeline__year-col">
						<span class="timeline__year"><?= $ano ?></span>
					</div>
				</div>

			<?php } elseif ( $es_ultima && !$es_impar ) {?>

				<div class="timeline__item timeline__item--hoy" role="listitem">
					<div class="timeline__year-col">
						<span class="timeline__year"><?= $ano ?></span>
					</div>
					<div class="timeline__dot" aria-hidden="true"></div>
					<div class="timeline__content-right">
						<div class="timeline__card" tabindex="0">
							<h3 class="timeline__card-titulo"><?= $titulo ?></h3>
							<p class="timeline__card-texto"><?= $texto ?></p>
							<?php if ( $imagen ) { ?>
							<div class="timeline__card-foto">
								<img src="<?= esc_url( $imagen['url'] ) ?>" alt="<?= esc_attr( $imagen['alt'] ) ?>" loading="lazy">
							</div>
							<?php } ?>
						</div>
					</div>
				</div>

			<?php } elseif ( $es_impar ) { ?>

				<div class="timeline__item" role="listitem">
					<div class="timeline__content-left">
						<div class="timeline__card" tabindex="0">
							<h3 class="timeline__card-titulo"><?= $titulo ?></h3>
							<p class="timeline__card-texto"><?= $texto ?></p>
							<?php if ( $imagen ) { ?>
							<div class="timeline__card-foto">
								<img src="<?= esc_url( $imagen['url'] ) ?>" alt="<?= esc_attr( $imagen['alt'] ) ?>" loading="lazy">
							</div>
							<?php } ?>
						</div>
					</div>
					<div class="timeline__dot" aria-hidden="true"></div>
					<div class="timeline__year-col">
						<span class="timeline__year"><?= $ano ?></span>
					</div>
				</div>

			<?php } else { ?>

				<div class="timeline__item" role="listitem">
					<div class="timeline__year-col">
						<span class="timeline__year"><?= $ano ?></span>
					</div>
					<div class="timeline__dot" aria-hidden="true"></div>
					<div class="timeline__content-right">
						<div class="timeline__card" tabindex="0">
							<h3 class="timeline__card-titulo"><?= $titulo ?></h3>
							<p class="timeline__card-texto"><?= $texto ?></p>
							<?php if ( $imagen ) { ?>
							<div class="timeline__card-foto">
								<img src="<?= esc_url( $imagen['url'] ) ?>" alt="<?= esc_attr( $imagen['alt'] ) ?>" loading="lazy">
							</div>
							<?php } ?>
						</div>
					</div>
				</div>

			<?php
				};
				$contador++;
				}
			}
			?>
		</div>

		<?php 
		if ( get_field('mostrar_boton_cartelera') ) {
		?> 
		<div class="timeline__cartelera--button">
			<a href="<?= esc_url( home_url( '/#cartelera' ) ); ?>" class="btn-cine btn-lg btn-pa"><i class="bi bi-camera-reels-fill"></i> Ver cartelera</a>
		</div>
		<?php } ?>
	</div>
</section>