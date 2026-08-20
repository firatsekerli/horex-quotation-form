<?php
/**
 * ACF wiring: local JSON paths and the "Hor-Ex Instellingen" options page.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Absolute path to the plugin's local JSON directory.
 *
 * @return string
 */
function horex_acf_json_dir() {
	return untrailingslashit( HOREX_DIR . 'acf-json' );
}

/**
 * Point ACF at the plugin's acf-json directory for loading and saving.
 *
 * ACF only auto-loads local JSON from the active theme, so the load path has to be
 * added explicitly. Saving is scoped to this plugin's own field groups — returning a
 * blanket save path would silently redirect every other group on the site into here.
 */
function horex_register_acf_json_paths() {
	add_filter( 'acf/settings/load_json', 'horex_acf_load_json_path' );
	add_filter( 'acf/json/save_paths', 'horex_acf_save_json_path', 10, 2 );
}

/**
 * Add the plugin's acf-json directory to ACF's load paths.
 *
 * @param array $paths Existing load paths.
 * @return array
 */
function horex_acf_load_json_path( $paths ) {
	$paths[] = horex_acf_json_dir();

	return $paths;
}

/**
 * Save this plugin's field groups into the plugin, leave everything else alone.
 *
 * @param array $paths Candidate save paths.
 * @param array $post  The field group being saved.
 * @return array
 */
function horex_acf_save_json_path( $paths, $post ) {
	$key = isset( $post['key'] ) ? $post['key'] : '';

	if ( 0 === strpos( $key, 'group_horex_' ) ) {
		return array( horex_acf_json_dir() );
	}

	return $paths;
}

/**
 * Register the settings options page under the Hor-Ex menu.
 */
function horex_register_options_page() {
	if ( ! Horex::has_acf_pro() ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title'      => __( 'Hor-Ex Instellingen', 'horex' ),
			'menu_title'      => __( 'Instellingen', 'horex' ),
			'menu_slug'       => Horex::OPTIONS_SLUG,
			'parent_slug'     => 'edit.php?post_type=' . Horex::CPT,
			'capability'      => 'manage_options',
			'update_button'   => __( 'Opslaan', 'horex' ),
			'updated_message' => __( 'Instellingen opgeslagen', 'horex' ),
			'autoload'        => true,
		)
	);
}
