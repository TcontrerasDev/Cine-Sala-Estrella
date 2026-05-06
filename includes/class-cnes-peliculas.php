<?php
/**
 * Post Type and Taxonomies for Movies (Películas).
 *
 * @package SalaEstrellaManager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles 'pelicula' CPT and 'genero', 'clasificacion' taxonomies.
 */
class CNES_Peliculas {

	/**
	 * Register CPT and Taxonomies.
	 */
	public function register() {
		$this->register_post_type();
		$this->register_taxonomies();
	}

	/**
	 * Register 'pelicula' Custom Post Type.
	 */
	private function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Películas', 'Post Type General Name', 'sala-estrella-manager' ),
			'singular_name'         => _x( 'Película', 'Post Type Singular Name', 'sala-estrella-manager' ),
			'menu_name'             => __( 'Películas', 'sala-estrella-manager' ),
			'name_admin_bar'        => __( 'Película', 'sala-estrella-manager' ),
			'archives'              => __( 'Cartelera', 'sala-estrella-manager' ),
			'attributes'            => __( 'Atributos de Película', 'sala-estrella-manager' ),
			'parent_item_colon'     => __( 'Película Superior:', 'sala-estrella-manager' ),
			'all_items'             => __( 'Todas las Películas', 'sala-estrella-manager' ),
			'add_new_item'          => __( 'Agregar Nueva Película', 'sala-estrella-manager' ),
			'add_new'               => __( 'Agregar Nueva', 'sala-estrella-manager' ),
			'new_item'              => __( 'Nueva Película', 'sala-estrella-manager' ),
			'edit_item'             => __( 'Editar Película', 'sala-estrella-manager' ),
			'update_item'           => __( 'Actualizar Película', 'sala-estrella-manager' ),
			'view_item'             => __( 'Ver Película', 'sala-estrella-manager' ),
			'view_items'            => __( 'Ver Películas', 'sala-estrella-manager' ),
			'search_items'          => __( 'Buscar Película', 'sala-estrella-manager' ),
			'not_found'             => __( 'No se encontraron películas', 'sala-estrella-manager' ),
			'not_found_in_trash'    => __( 'No hay películas en la papelera', 'sala-estrella-manager' ),
			'featured_image'        => __( 'Imagen Destacada (Poster)', 'sala-estrella-manager' ),
			'set_featured_image'    => __( 'Asignar imagen destacada', 'sala-estrella-manager' ),
			'remove_featured_image' => __( 'Quitar imagen destacada', 'sala-estrella-manager' ),
			'use_featured_image'    => __( 'Usar como imagen destacada', 'sala-estrella-manager' ),
			'insert_into_item'      => __( 'Insertar en película', 'sala-estrella-manager' ),
			'uploaded_to_this_item' => __( 'Subido a esta película', 'sala-estrella-manager' ),
			'items_list'            => __( 'Lista de películas', 'sala-estrella-manager' ),
			'items_list_navigation' => __( 'Navegación de lista de películas', 'sala-estrella-manager' ),
			'filter_items_list'     => __( 'Filtrar lista de películas', 'sala-estrella-manager' ),
		);

		$args = array(
			'label'               => __( 'Película', 'sala-estrella-manager' ),
			'description'         => __( 'Películas en cartelera', 'sala-estrella-manager' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
			'taxonomies'          => array( 'genero', 'clasificacion', 'formato_audio' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-video-alt2',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => 'cartelera',
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => array( 'slug' => 'pelicula' ),
			'capability_type'     => 'post',
			'show_in_rest'        => true,
		);

		register_post_type( 'pelicula', $args );
	}

	/**
	 * Register 'genero', 'clasificacion' and 'sede' taxonomies.
	 */
	private function register_taxonomies() {
		// Género
		register_taxonomy( 'genero', array( 'pelicula' ), array(
			'hierarchical'      => true,
			'labels'            => array(
				'name'              => _x( 'Géneros', 'taxonomy general name', 'sala-estrella-manager' ),
				'singular_name'     => _x( 'Género', 'taxonomy singular name', 'sala-estrella-manager' ),
				'search_items'      => __( 'Buscar Géneros', 'sala-estrella-manager' ),
				'all_items'         => __( 'Todos los Géneros', 'sala-estrella-manager' ),
				'parent_item'       => __( 'Género Padre', 'sala-estrella-manager' ),
				'parent_item_colon' => __( 'Género Padre:', 'sala-estrella-manager' ),
				'edit_item'         => __( 'Editar Género', 'sala-estrella-manager' ),
				'update_item'       => __( 'Actualizar Género', 'sala-estrella-manager' ),
				'add_new_item'      => __( 'Agregar Nuevo Género', 'sala-estrella-manager' ),
				'new_item_name'     => __( 'Nombre del Nuevo Género', 'sala-estrella-manager' ),
				'menu_name'         => __( 'Géneros', 'sala-estrella-manager' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'genero' ),
			'show_in_rest'      => true,
		) );

		// Clasificación
		register_taxonomy( 'clasificacion', array( 'pelicula' ), array(
			'hierarchical'      => true,
			'labels'            => array(
				'name'              => _x( 'Clasificaciones', 'taxonomy general name', 'sala-estrella-manager' ),
				'singular_name'     => _x( 'Clasificación', 'taxonomy singular name', 'sala-estrella-manager' ),
				'search_items'      => __( 'Buscar Clasificaciones', 'sala-estrella-manager' ),
				'all_items'         => __( 'Todas las Clasificaciones', 'sala-estrella-manager' ),
				'parent_item'       => __( 'Clasificación Padre', 'sala-estrella-manager' ),
				'parent_item_colon' => __( 'Clasificación Padre:', 'sala-estrella-manager' ),
				'edit_item'         => __( 'Editar Clasificación', 'sala-estrella-manager' ),
				'update_item'       => __( 'Actualizar Clasificación', 'sala-estrella-manager' ),
				'add_new_item'      => __( 'Agregar Nueva Clasificación', 'sala-estrella-manager' ),
				'new_item_name'     => __( 'Nombre de la Nueva Clasificación', 'sala-estrella-manager' ),
				'menu_name'         => __( 'Clasificaciones', 'sala-estrella-manager' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'clasificacion' ),
			'show_in_rest'      => true,
		) );

		// Formato de Audio
		register_taxonomy( 'formato_audio', array( 'pelicula' ), array(
			'hierarchical'      => false,
			'labels'            => array(
				'name'          => _x( 'Formatos de Audio', 'taxonomy general name', 'sala-estrella-manager' ),
				'singular_name' => _x( 'Formato de Audio', 'taxonomy singular name', 'sala-estrella-manager' ),
				'search_items'  => __( 'Buscar Formatos', 'sala-estrella-manager' ),
				'all_items'     => __( 'Todos los Formatos', 'sala-estrella-manager' ),
				'edit_item'     => __( 'Editar Formato', 'sala-estrella-manager' ),
				'update_item'   => __( 'Actualizar Formato', 'sala-estrella-manager' ),
				'add_new_item'  => __( 'Agregar Nuevo Formato', 'sala-estrella-manager' ),
				'new_item_name' => __( 'Nombre del Nuevo Formato', 'sala-estrella-manager' ),
				'menu_name'     => __( 'Formatos de Audio', 'sala-estrella-manager' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'formato-audio' ),
			'show_in_rest'      => true,
		) );

		// Sede
		register_taxonomy( 'sede', array( 'pelicula' ), array(
			'hierarchical'      => true,
			'labels'            => array(
				'name'              => _x( 'Sedes', 'taxonomy general name', 'sala-estrella-manager' ),
				'singular_name'     => _x( 'Sede', 'taxonomy singular name', 'sala-estrella-manager' ),
				'search_items'      => __( 'Buscar Sedes', 'sala-estrella-manager' ),
				'all_items'         => __( 'Todas las Sedes', 'sala-estrella-manager' ),
				'parent_item'       => __( 'Sede Padre', 'sala-estrella-manager' ),
				'parent_item_colon' => __( 'Sede Padre:', 'sala-estrella-manager' ),
				'edit_item'         => __( 'Editar Sede', 'sala-estrella-manager' ),
				'update_item'       => __( 'Actualizar Sede', 'sala-estrella-manager' ),
				'add_new_item'      => __( 'Agregar Nueva Sede', 'sala-estrella-manager' ),
				'new_item_name'     => __( 'Nombre de la Nueva Sede', 'sala-estrella-manager' ),
				'menu_name'         => __( 'Sedes', 'sala-estrella-manager' ),
			),
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'sede' ),
			'show_in_rest'      => true,
		) );
	}

	/**
	 * Insert default terms for taxonomies.
	 */
	public function insert_default_terms() {
		$generos = array(
			'Acción', 'Aventura', 'Animación', 'Comedia', 'Drama', 
			'Documental', 'Familiar', 'Terror', 'Ciencia Ficción', 
			'Suspenso', 'Romance'
		);

		foreach ( $generos as $genero ) {
			if ( ! term_exists( $genero, 'genero' ) ) {
				wp_insert_term( $genero, 'genero' );
			}
		}

		$clasificaciones = array(
			'TE'   => 'Todo Espectador',
			'TE+7' => 'Todo Espectador + 7 años',
			'14'   => 'Mayores de 14 años',
			'18'   => 'Mayores de 18 años'
		);

		foreach ( $clasificaciones as $slug => $nombre ) {
			if ( ! term_exists( $slug, 'clasificacion' ) ) {
				wp_insert_term( $nombre, 'clasificacion', array( 'slug' => $slug ) );
			}
		}

		$sedes = array( 'Punta Arenas', 'Puerto Natales' );

		foreach ( $sedes as $sede ) {
			if ( ! term_exists( $sede, 'sede' ) ) {
				wp_insert_term( $sede, 'sede' );
			}
		}

		$formatos = array(
			'doblada'      => 'Doblada',
			'subtitulada'  => 'Subtitulada',
		);

		foreach ( $formatos as $slug => $nombre ) {
			if ( ! term_exists( $slug, 'formato_audio' ) ) {
				wp_insert_term( $nombre, 'formato_audio', array( 'slug' => $slug ) );
			}
		}

		update_option( 'cnes_terminos_insertados', true );
	}
}
