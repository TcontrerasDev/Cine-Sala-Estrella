<?php
/*customizar la clase y el link del logo de wordpress*/
function add_class_to_custom_logo() {
    $custom_logo_id = get_theme_mod( 'custom_logo' );
    $html = sprintf( '<a href="%1$s" class="navbar-cine__logo" aria-label="Cine Sala Estrella — Inicio" rel="home" itemprop="url">%2$s</a>',
    esc_url( home_url( '/' ) ),
    wp_get_attachment_image( $custom_logo_id, 'full', false, array(
    'class' => 'img-fluid',
    ) )
    );
    return $html;
    }
    add_filter( 'get_custom_logo', 'add_class_to_custom_logo' );
/*customizar la clase y el link del logo de wordpress*/


//Crear Menú
if (!function_exists('menu_offcanvas')) {

    // Register Navigation Menus
    function menu_offcanvas()
    {

        $locations = array(
            'menu-offcanvas' => __('menu-offcanvas', 'menu-offcanvas'), //Nombre - Ubicación - id
        );
        register_nav_menus($locations);
    }
    add_action('init', 'menu_offcanvas');
}



/*clases para li item */
// function atg_menu_classes($classes, $item, $args)
// {
//     if ($args->theme_location == 'menu-footer') {
//         $classes[] = 'nav-item';
//     }
//     return $classes;
// }
// add_filter('nav_menu_css_class', 'atg_menu_classes', 1, 3);



/*menu wordpress*/
class bootstrap_5_wp_nav_menu_walker extends Walker_Nav_Menu {

    private $dropdown_icon = '<i class="bi bi-chevron-down"></i>';
  
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat("\t", $depth);
        $submenu = ($depth > 0) ? ' sub-menu' : '';
        $output .= "\n$indent<ul class=\"dropdown-menu$submenu depth_$depth\">\n";
    }
  
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
  
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'nav-item';
        if ($args->walker->has_children) {
            $classes[] = 'dropdown';
        }
        if (in_array('current-menu-item', $classes)) {
            $classes[] = 'active';
        }
        $classes = array_filter($classes);
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
  
        $id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
  
        $output .= $indent . '<li' . $id . $class_names .'>';
  
        $atts = array();
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
        $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
        $atts['href']   = ! empty( $item->url )        ? $item->url        : '';
  
        if ($args->walker->has_children) {
            $atts['data-bs-toggle'] = 'dropdown';
            $atts['class']          = 'nav-link dropdown-toggle';
            $atts['aria-expanded']  = 'false';
        } else {
            $atts['class'] = 'navbar-cine__link';
        }
  
        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args);
        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }
  
        $item_output = $args->before;
        $item_output .= '<a'. $attributes .'>';
        $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
        $item_output .= ($args->walker->has_children) ? ' ' . $this->dropdown_icon : '';
        $item_output .= '</a>';
        $item_output .= $args->after;
  
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
  }
    /*menu wordpress*/

    class offcanvas_wp_nav_menu_walker extends Walker_Nav_Menu {

    private $dropdown_icon = '<i class="bi bi-chevron-down"></i>';
  
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat("\t", $depth);
        $submenu = ($depth > 0) ? ' sub-menu' : '';
        $output .= "\n$indent<ul class=\"dropdown-menu$submenu depth_$depth\">\n";
    }
  
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $indent = ($depth) ? str_repeat("\t", $depth) : '';
  
        $classes = empty($item->classes) ? array() : (array) $item->classes;
        $classes[] = 'nav-item';
        if ($args->walker->has_children) {
            $classes[] = 'dropdown';
        }
        if (in_array('current-menu-item', $classes)) {
            $classes[] = 'active';
        }
        $classes = array_filter($classes);
        $class_names = join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';
  
        $id = apply_filters('nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args);
        $id = $id ? ' id="' . esc_attr($id) . '"' : '';
  
        $output .= $indent . '<li' . $id . $class_names .'>';
  
        $atts = array();
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
        $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
        $atts['href']   = ! empty( $item->url )        ? $item->url        : '';
  
        if ($args->walker->has_children) {
            $atts['data-bs-toggle'] = 'dropdown';
            $atts['class']          = 'nav-link dropdown-toggle';
            $atts['aria-expanded']  = 'false';
        } else {
            $atts['class'] = 'nav-link-mobile';
        }
  
        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args);
        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }
  
        $item_output = $args->before;
        $item_output .= '<a'. $attributes .'>';
        $item_output .= $args->link_before . apply_filters('the_title', $item->title, $item->ID) . $args->link_after;
        $item_output .= ($args->walker->has_children) ? ' ' . $this->dropdown_icon : '';
        $item_output .= '</a>';
        $item_output .= $args->after;
  
        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }
  }