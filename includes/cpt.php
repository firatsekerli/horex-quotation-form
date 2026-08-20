<?php
/**
 * Registers the "Aanvraag" post type that stores incoming quote requests.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the horex_aanvraag post type.
 *
 * Requests are private business data: not public, not queryable on the front-end
 * and kept out of the REST API and search.
 */
function horex_register_cpt() {
	$labels = array(
		'name'                  => __( 'Aanvragen', 'horex' ),
		'singular_name'         => __( 'Aanvraag', 'horex' ),
		'menu_name'             => __( 'Hor-Ex', 'horex' ),
		'all_items'             => __( 'Aanvragen', 'horex' ),
		'add_new'               => __( 'Nieuwe aanvraag', 'horex' ),
		'add_new_item'          => __( 'Nieuwe aanvraag toevoegen', 'horex' ),
		'edit_item'             => __( 'Aanvraag bewerken', 'horex' ),
		'new_item'              => __( 'Nieuwe aanvraag', 'horex' ),
		'view_item'             => __( 'Aanvraag bekijken', 'horex' ),
		'search_items'          => __( 'Aanvragen zoeken', 'horex' ),
		'not_found'             => __( 'Geen aanvragen gevonden', 'horex' ),
		'not_found_in_trash'    => __( 'Geen aanvragen in de prullenbak', 'horex' ),
		'items_list'            => __( 'Lijst met aanvragen', 'horex' ),
		'item_published'        => __( 'Aanvraag opgeslagen', 'horex' ),
		'item_updated'          => __( 'Aanvraag bijgewerkt', 'horex' ),
	);

	register_post_type(
		Horex::CPT,
		array(
			'labels'              => $labels,
			'public'              => false,
			'publicly_queryable'  => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'menu_position'       => 26,
			'menu_icon'           => 'dashicons-clipboard',
			'capability_type'     => 'post',
			'map_meta_cap'        => true,
			'supports'            => array( 'title' ),
		)
	);
}
