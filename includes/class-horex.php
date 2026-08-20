<?php
/**
 * Main plugin class: wiring, includes and shared helpers.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hor-Ex Offerteaanvraag.
 */
final class Horex {

	/**
	 * Custom post type holding incoming quote requests.
	 */
	const CPT = 'horex_aanvraag';

	/**
	 * Slug of the settings page.
	 */
	const OPTIONS_SLUG = 'horex-instellingen';

	/**
	 * Singleton instance.
	 *
	 * @var Horex|null
	 */
	private static $instance = null;

	/**
	 * Retrieve the singleton instance.
	 *
	 * @return Horex
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load includes and register hooks.
	 */
	private function __construct() {
		$this->includes();

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', 'horex_register_cpt' );
		add_action( 'admin_menu', 'horex_register_settings_page' );
		add_action( 'admin_enqueue_scripts', 'horex_maybe_enqueue_admin_assets' );

		// Seeds installs that were activated before the seeder existed, and fills in
		// catalogue fields added after an install was first seeded.
		add_action( 'admin_init', 'horex_maybe_seed' );
		add_action( 'admin_init', 'horex_migrate_catalogue', 11 );

		add_action( 'add_meta_boxes_' . self::CPT, 'horex_register_submission_meta_boxes' );
		add_action( 'save_post_' . self::CPT, 'horex_handle_submission_save' );
		add_filter( 'manage_' . self::CPT . '_posts_columns', 'horex_submission_columns' );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', 'horex_submission_column', 10, 2 );
		add_filter( 'manage_edit-' . self::CPT . '_sortable_columns', 'horex_submission_sortable_columns' );
		add_action( 'pre_get_posts', 'horex_submission_sort' );

		horex_register_shortcode();
		add_action( 'admin_post_horex_save_settings', 'horex_handle_settings_save' );
	}

	/**
	 * Load the plugin files.
	 */
	private function includes() {
		require_once HOREX_DIR . 'includes/cpt.php';
		require_once HOREX_DIR . 'includes/settings-schema.php';
		require_once HOREX_DIR . 'includes/settings.php';
		require_once HOREX_DIR . 'includes/settings-render.php';
		require_once HOREX_DIR . 'includes/defaults.php';
		require_once HOREX_DIR . 'includes/submission-schema.php';
		require_once HOREX_DIR . 'includes/submission.php';
		require_once HOREX_DIR . 'includes/illustrations.php';
		require_once HOREX_DIR . 'includes/frontend.php';
	}

	/**
	 * Load translations from /languages.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'horex', false, dirname( plugin_basename( HOREX_FILE ) ) . '/languages' );
	}

	/**
	 * Register the post type on activation so permalinks are correct straight away.
	 */
	public static function activate() {
		horex_register_cpt();
		horex_maybe_seed();
		horex_migrate_catalogue();
		flush_rewrite_rules();
	}

	/**
	 * Clean up rewrite rules on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
