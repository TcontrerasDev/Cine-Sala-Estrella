<?php 

function js_functions(){

    if(!is_admin()) {

        //Register scripts
        wp_register_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js', array(), '5.3.8', true);
        wp_register_script('scroll-effects', get_bloginfo('template_directory') . '/assets/librerias/js/scroll-effects.js', array(), '1.0', true);
        wp_register_script('sede-selector', get_bloginfo('template_directory') . '/assets/librerias/js/sede-selector.js', array(), '1.0', true);
        wp_register_script('offcanvas-nav', get_bloginfo('template_directory') . '/assets/librerias/js/offcanvas-nav.js', array('bootstrap'), '1.0', true);

        //Enqueue scripts
        wp_enqueue_script('bootstrap');
        wp_enqueue_script('scroll-effects');
        wp_enqueue_script('sede-selector');
        wp_enqueue_script('offcanvas-nav');

        //Localize Scripts
        wp_localize_script('sede-selector', 'Sedes', array(
            'sedeurl' => esc_url(rest_url('wp/v2/')),
            'nonce' => wp_create_nonce('sedes-nonce'),
        ));

    }

}

add_action('wp_enqueue_scripts', 'js_functions', 9999);