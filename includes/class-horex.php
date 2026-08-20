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
	 * Slug of the ACF options page.
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
		add_action( 'admin_notices', array( $this, 'maybe_show_acf_notice' ) );

		horex_register_acf_json_paths();

		add_action( 'acf/init', 'horex_register_options_page' );
	}

	/**
	 * Load the plugin files.
	 */
	private function includes() {
		require_once HOREX_DIR . 'includes/cpt.php';
		require_once HOREX_DIR . 'includes/options-page.php';
	}

	/**
	 * Load translations from /languages.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'horex', false, dirname( plugin_basename( HOREX_FILE ) ) . '/languages' );
	}

	/**
	 * Is ACF Pro active? Options pages are a Pro-only feature.
	 *
	 * @return bool
	 */
	public static function has_acf_pro() {
		return function_exists( 'acf_add_options_page' );
	}

	/**
	 * Warn in the admin when ACF Pro is missing; the plugin needs it for its catalogue.
	 */
	public function maybe_show_acf_notice() {
		if ( self::has_acf_pro() || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s</p></div>',
			esc_html__( 'Hor-Ex Offerteaanvraag:', 'horex' ),
			esc_html__( 'Advanced Custom Fields Pro is vereist. Activeer ACF Pro om de catalogus en de aanvragen te kunnen beheren.', 'horex' )
		);
	}

	/**
	 * Register the post type on activation so permalinks are correct straight away.
	 */
	public static function activate() {
		horex_register_cpt();
		flush_rewrite_rules();
	}

	/**
	 * Clean up rewrite rules on deactivation.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
