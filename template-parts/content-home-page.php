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
		HERO
═══════════════════════════════════════════════════════ -->
<section class="hero" aria-label="Presentación">
	<div class="hero__bg">
		<img
			src="assets/img/hero-bg.jpg"
			alt=""
			class="hero__bg-img"
			aria-hidden="true"
			onerror="this.classList.add('img-error')"
		>
	</div>

	<div class="hero__overlay" aria-hidden="true"></div>

	<div class="hero__content">
		<span class="hero__eyebrow"><?php the_field('antetitulo') ?></span>

		<h1 class="hero__titulo">
			<?= get_the_title() ?>
		</h1>

		<p class="hero__frase">"<?php the_field('frase') ?>"</p>

		<div class="hero__ctas">
			<?php 
			$buttons = get_field('botones');
			if ($buttons) {
				foreach ($buttons as $button) {
			?>
				<a href="<?= $button['enlace'] ?>" class="btn-cine btn-lg <?= $button['estilo'] ?>" id="heroBtnCartelera">
					<i class="bi <?= $button['icono'] ?>"></i>
					<?= $button['texto'] ?>
				</a>
			<?php } 
			}?>
		</div>

		<div class="hero__sedes">
			<?php
			$ubicaciones = get_field('ubicaciones');
			if ($ubicaciones) {
				foreach ($ubicaciones as $sede) { 
			?>
				<div class="hero__sede-info">
					<span class="hero__sede-nombre <?= $sede['color']?>">
						<i class="bi bi-geo-alt-fill"></i>
						<?= $sede['ciudad'] ?>
					</span>
					<span class="hero__sede-direccion"><?= $sede['direccion'] ?></span>
				</div>
			<?php }
			} ?>
		</div>								
	</div>
</section>

<!-- ═══════════════════════════════════════════════════════
		SEDE BANNER
═══════════════════════════════════════════════════════ -->
<div class="sede-banner" role="banner" aria-label="Sede activa" aria-busy="true">
	<div class="sede-banner__inner">
		<div class="sede-banner__info">
			<span class="sede-banner__dot sede-banner__dot--pa" data-sede-dot></span>
			<span class="sede-banner__nombre sede-banner__nombre--pa" data-sede-nombre><span class="sede-banner__skeleton sede-banner__skeleton--nombre" aria-hidden="true"></span></span>
			<span class="sede-banner__divider" aria-hidden="true"></span>
			<span class="sede-banner__address">
				<i class="bi bi-geo-alt sede-banner__icon"></i>
				<span data-sede-direccion><span class="sede-banner__skeleton sede-banner__skeleton--direccion" aria-hidden="true"></span></span>
			</span>
			<span class="sede-banner__divider" aria-hidden="true"></span>
			<span class="sede-banner__address">
				<i class="bi bi-calendar3 sede-banner__icon"></i>
				Abierto 365 días al año
			</span>
		</div>

		<div class="sede-banner__toggle" role="group" aria-label="Cambiar sede">
			<span class="sede-banner__toggle-label">Sede:</span>
			<span class="sede-banner__skeleton sede-banner__skeleton--btn" aria-hidden="true"></span>
			<span class="sede-banner__skeleton sede-banner__skeleton--btn" aria-hidden="true"></span>
		</div>
	</div>
</div>

<!-- ═══════════════════════════════════════════════════════
		CARTELERA
═══════════════════════════════════════════════════════ -->

<!-- ═══════════════════════════════════════════════════════
		PRÓXIMAMENTE
═══════════════════════════════════════════════════════ -->

<!-- ═══════════════════════════════════════════════════════
         INFORMACIÓN IMPORTANTE
═══════════════════════════════════════════════════════ -->
<?php 
$card_condiciones = get_field('condiciones');
if ($card_condiciones) {
?>
<section class="seccion-info-importante" id="informacion" aria-labelledby="tituloInfoImportante">
	<div class="seccion__inner">

		<span class="label-seccion"><?php the_field('antetitulo_condiciones') ?></span>
		<h2 class="titulo-seccion" id="tituloInfoImportante"><?php the_field('titulo_condiciones') ?></h2>
		<p class="info-importante__subtitulo"><?php the_field('subtitulo_condiciones') ?></p>

		<div class="info-importante__grid">
	    <?php foreach ($card_condiciones as $condicion) { ?>
			<!-- Card 1 -->
			<article class="info-importante__card reveal-element" tabindex="0"J>
				<div class="info-importante__card-icon" aria-hidden="true">
					<i class="bi <?= $condicion['icon'] ?>"></i>
				</div>
				<h3 class="info-importante__card-titulo"><?= $condicion['titulo_condicion'] ?></h3>
				<p class="info-importante__card-desc"><?= $condicion['condicion'] ?></p>
				<span class="info-importante__card-tag"><?= $condicion['etiqueta'] ?></span>
			</article>
		<?php } ?>
		</div>

		<div class="info-importante__cierre" role="note">
			<p><?php the_field('texto_cierre') ?></p>
		</div>

		<p class="info-importante__legal"><?php the_field('disclaimer') ?></p>

	</div>
</section>
<?php }?>

<!-- ═══════════════════════════════════════════════════════
         HISTORIA
═══════════════════════════════════════════════════════ -->
<section class="seccion-historia-teaser" aria-labelledby="tituloHistoriaTeaser">
	<div class="seccion__inner">
		<div class="historia-teaser__grid">
			<div class="historia-teaser__content">
				<p class="historia-teaser__year-badge" aria-hidden="true"><?php the_field('ano_fondo') ?></p>
				<span class="label-seccion"><?php the_field('antetitulo_historia') ?></span>
				<h2 class="historia-teaser__titulo" id="tituloHistoriaTeaser">
					<?php the_field('titulo_historia') ?>
				</h2>
				<p class="historia-teaser__texto">
					<?php the_field('resumen') ?>
				</p>
				<div class="historia-teaser__ctas">
					<a href="<?php the_field('enlace_historia') ?>" class="btn-cine btn-outline-pa">
					<i class="bi bi-book-half"></i>
					<?php the_field('texto_boton_historia') ?>
					</a>
				</div>
			</div>

			<div class="historia-teaser__imagen-grid">
				<div class="historia-teaser__foto historia-teaser__foto--tall">
					<?php 
					$img_1 = get_field('imagen_1'); 
						if ($img_1){
					?>
						<img
							src="<?= $img_1['url'] ?>"
							alt="<?= $img_1['alt'] ?>"
							loading="lazy"
						>
					<?php } ?>
				</div>
				<div class="historia-teaser__foto historia-teaser__foto--tall">
					<?php 
					$img_2 = get_field('imagen_2'); 
						if ($img_2){
					?>
						<img
							src="<?= $img_2['url'] ?>"
							alt="<?= $img_2['alt'] ?>"
							loading="lazy"
						>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</section>
