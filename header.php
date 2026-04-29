<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Cine_Sala_Estrella
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Cine Sala Estrella — La mejor experiencia cinematográfica en Magallanes. Proyección 4K en 3D, audio de 16 canales. Sedes en Punta Arenas y Puerto Natales. Abierto 365 días al año.">
	<meta name="theme-color" content="#0A1A0F">

	<!-- Open Graph -->
	<meta property="og:title" content="Cine Sala Estrella — La mejor experiencia en Magallanes">
	<meta property="og:description" content="Más de 100 años de historia familiar. Proyección 4K 3D, audio 16 canales. Punta Arenas y Puerto Natales.">
	<meta property="og:type" content="website">	
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site">
	<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'cine-sala-estrella' ); ?></a>

	<header id="masthead" class="site-header">

		<div id="site-navigation" class="navbar-cine" role="navigation" aria-label="Navegación principal">
			<div class="navbar-cine__inner">

				<!-- Logo -->
				<div>
					<?php the_custom_logo(); ?>
					<span class="navbar-cine__logo-text navbar-cine__logo-fallback">CINE SALA <span class="navbar-cine__logo-accent">ESTRELLA</span></span>
				</div>

				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-1',
						'menu_id'        => 'primary-menu',
						'menu_class'	 => 'navbar-cine__nav m-0 p-0',
						'container'		 => 'nav',
						'walker'		 => new bootstrap_5_wp_nav_menu_walker(),
 					)
				);
				?>

				<div class="navbar-cine__spacer"></div>

				<!-- Toggler mobile -->
				<button
					class="navbar-cine__toggler"
					type="button"
					data-bs-toggle="offcanvas"
					data-bs-target="#offcanvasNav"
					aria-controls="offcanvasNav"
					aria-expanded="false"
					aria-label="Abrir menú de navegación"
				>
				<span></span>
				<span></span>
				<span></span>
				</button>
			</div>
		</div>

		<div class="offcanvas offcanvas-start offcanvas-cine" tabindex="-1" id="offcanvasNav" aria-labelledby="offcanvasNavLabel">
			<div class="offcanvas-header">
				<span class="navbar-cine__logo-text" id="offcanvasNavLabel">CINE SALA <span class="navbar-cine__logo-accent">ESTRELLA</span></span>
				<button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"></button>
			</div>
			<div class="offcanvas-body">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'menu-offcanvas',
						'menu_id'        => 'menu-offcanvas',
						'menu_class'	 => 'm-0 p-0',
						'container'		 => false,
						'walker'		 => new offcanvas_wp_nav_menu_walker(),
					)
				);
				?>
			</div>
		</div>

	</header><!-- #masthead -->
