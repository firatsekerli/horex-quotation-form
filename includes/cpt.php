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
		'name'                  => __( 'Requests', 'horex' ),
		'singular_name'         => __( 'Request', 'horex' ),
		'menu_name'             => 'Hor-Ex',
		'all_items'             => __( 'Requests', 'horex' ),
		'add_new'               => __( 'New request', 'horex' ),
		'add_new_item'          => __( 'Add new request', 'horex' ),
		'edit_item'             => __( 'Edit request', 'horex' ),
		'new_item'              => __( 'New request', 'horex' ),
		'view_item'             => __( 'View request', 'horex' ),
		'search_items'          => __( 'Search requests', 'horex' ),
		'not_found'             => __( 'No requests found', 'horex' ),
		'not_found_in_trash'    => __( 'No requests found in Trash', 'horex' ),
		'items_list'            => __( 'Requests list', 'horex' ),
		'item_published'        => __( 'Request saved', 'horex' ),
		'item_updated'          => __( 'Request updated', 'horex' ),
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
			// No editor, no title box: the title is generated from the reference and
			// the customer name, and everything else lives in the meta boxes.
			'supports'            => false,
		)
	);
}
