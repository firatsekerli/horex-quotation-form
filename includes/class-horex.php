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

		// Seeds installs that were activated before the seeder existed.
		add_action( 'admin_init', 'horex_maybe_seed' );
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
		flush_rewrite_rules();
	}

	/**
	 * Clean up rewrite rules on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
