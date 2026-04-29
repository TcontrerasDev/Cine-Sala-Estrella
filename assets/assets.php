<?php 

include get_template_directory() . '/assets/includes/fonts-functions.php';
include get_template_directory() . '/assets/includes/css-functions.php';
include get_template_directory() . '/assets/includes/js-functions.php';
include get_template_directory() . '/assets/includes/menu-functions.php';
include get_template_directory() . '/assets/includes/widgets-functions.php';


// Deshabilitar speculation-rules
add_filter('wp_speculation_rules_enabled', '__return_false');
add_filter('pl_speculation_rules_enabled', '__return_false');

add_action('wp_enqueue_scripts', function() {
    wp_dequeue_script('speculation-rules');
}, 20);

add_action('init', function() {
    remove_action('wp_footer', 'wp_enqueue_speculation_rules');
    remove_action('wp_footer', 'wp_print_speculation_rules');
}, 20);

// Prefija home_url() a links de menú que empiezan con '#' cuando no estamos en el inicio
add_filter( 'wp_nav_menu_objects', function( $items, $args ) {
    if ( is_front_page() ) {
        return $items;
    }
    foreach ( $items as $item ) {
        if ( isset( $item->url ) && strpos( $item->url, '#' ) === 0 ) {
            $item->url = home_url('/') . $item->url;
        }
    }
    return $items;
}, 10, 2 );

// Forzar target="_blank" en bloques de iconos sociales de Gutenberg
add_filter( 'render_block', function( $block_content, $block ) {
    if ( 'core/social-link' === $block['blockName'] ) {
        $block_content = str_replace( '<a ', '<a target="_blank" rel="noopener noreferrer" ', $block_content );
    }
    return $block_content;
}, 10, 2 );