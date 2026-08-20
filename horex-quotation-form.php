<?php
/**
 * Plugin Name:       Hor-Ex Offerteaanvraag
 * Plugin URI:        https://hor-ex.nl/
 * Description:       Stap-voor-stap offerteconfigurator voor horren, gordijnen en zonwering. Voegt de shortcode [horex_offerte] toe en slaat aanvragen op als "Aanvraag".
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Hor-Ex
 * Author URI:        https://hor-ex.nl/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       horex
 * Domain Path:       /languages
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

define( 'HOREX_VERSION', '0.1.0' );
define( 'HOREX_FILE', __FILE__ );
define( 'HOREX_DIR', plugin_dir_path( __FILE__ ) );
define( 'HOREX_URL', plugin_dir_url( __FILE__ ) );

require_once HOREX_DIR . 'includes/class-horex.php';

/**
 * Main plugin instance.
 *
 * @return Horex
 */
function horex() {
	return Horex::instance();
}

horex();

register_activation_hook( __FILE__, array( 'Horex', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Horex', 'deactivate' ) );
