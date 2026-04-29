<?php 

function css_function(){

    //Register style

    //cdn
    wp_register_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css', array(), '5.3.8', 'all');
    wp_register_style('bootstrap_icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css', array(), '1.13.1', 'all');

    //base
    wp_register_style('base-reset', get_template_directory_uri() . '/assets/librerias/css/base/reset.css', array(), '1.0', 'all');
    wp_register_style('base-typography', get_template_directory_uri() . '/assets/librerias/css/base/typography.css', array(), '1.0', 'all');
    wp_register_style('base-variables', get_template_directory_uri() . '/assets/librerias/css/base/variables.css', array(), '1.0', 'all');

    //components
    wp_register_style('components-badge', get_template_directory_uri() . '/assets/librerias/css/components/badge.css', array(), '1.0', 'all');
    wp_register_style('components-btn', get_template_directory_uri() . '/assets/librerias/css/components/btn.css', array(), '1.0', 'all');
    wp_register_style('components-card-pelicula', get_template_directory_uri() . '/assets/librerias/css/components/card-pelicula.css', array(), '1.0', 'all');
    wp_register_style('components-filtro-generos', get_template_directory_uri() . '/assets/librerias/css/components/filtro-generos.css', array(), '1.0', 'all');
    wp_register_style('components-footer', get_template_directory_uri() . '/assets/librerias/css/components/footer.css', array(), '1.0', 'all');
    wp_register_style('components-hero', get_template_directory_uri() . '/assets/librerias/css/components/hero.css', array(), '1.0', 'all');
    wp_register_style('components-modal-pelicula', get_template_directory_uri() . '/assets/librerias/css/components/modal-pelicula.css', array(), '1.0', 'all');
    wp_register_style('components-navbar', get_template_directory_uri() . '/assets/librerias/css/components/navbar.css', array(), '1.0', 'all');
    wp_register_style('components-info-importante', get_template_directory_uri() . '/assets/librerias/css/components/info-importante.css', array(), '1.0', 'all');
    wp_register_style('components-sede-selector', get_template_directory_uri() . '/assets/librerias/css/components/sede-selector.css', array(), '1.0', 'all');
    wp_register_style('components-timeline', get_template_directory_uri() . '/assets/librerias/css/components/timeline.css', array(), '1.0', 'all');

    //pages
    wp_register_style('pages-home', get_template_directory_uri() . '/assets/librerias/css/pages/home.css', array(), '1.0', 'all');
    wp_register_style('pages-compra-final', get_template_directory_uri() . '/assets/librerias/css/pages/compra-final.css', array(), '1.0', 'all');
    wp_register_style('pages-contacto', get_template_directory_uri() . '/assets/librerias/css/pages/contacto.css', array(), '1.0', 'all');
    wp_register_style('pages-historia', get_template_directory_uri() . '/assets/librerias/css/pages/historia.css', array(), '1.0', 'all');
    wp_register_style('pages-seleccion-asientos', get_template_directory_uri() . '/assets/librerias/css/pages/seleccion-asientos.css', array(), '1.0', 'all');
    wp_register_style('pages-single-pelicula', get_template_directory_uri() . '/assets/librerias/css/pages/single-pelicula.css', array(), '1.0', 'all');

    //Enqueue style
    wp_enqueue_style('bootstrap');
    wp_enqueue_style('bootstrap_icons');
    wp_enqueue_style('base-reset');
    wp_enqueue_style('base-typography');
    wp_enqueue_style('base-variables');
    wp_enqueue_style('components-badge');
    wp_enqueue_style('components-btn');
    wp_enqueue_style('components-card-pelicula');
    wp_enqueue_style('components-filtro-generos');
    wp_enqueue_style('components-footer');
    wp_enqueue_style('components-hero');
    wp_enqueue_style('components-modal-peliculas');
    wp_enqueue_style('components-navbar');
    wp_enqueue_style('components-info-importante');
    wp_enqueue_style('components-sede-selector');
    wp_enqueue_style('components-timeline');
    wp_enqueue_style('pages-home');
    wp_enqueue_style('pages-compra-final');
    wp_enqueue_style('pages-contacto');
    wp_enqueue_style('pages-historia');
    wp_enqueue_style('pages-seleccion-asientos');
    wp_enqueue_style('pages-single-pelicula');

}

add_action('wp_enqueue_scripts', 'css_function');