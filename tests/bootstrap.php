<?php
/**
 * Test bootstrap: enough of WordPress, stubbed, to exercise the plugin's own logic.
 *
 * These stubs are close-enough equivalents of the core functions the plugin calls.
 * They are here to test our code, not WordPress.
 *
 * @package Horex
 */

define( 'ABSPATH', __DIR__ );
define( 'HOREX_VERSION', 'test' );
define( 'HOREX_DIR', dirname( __DIR__ ) . '/' );
define( 'HOREX_URL', 'https://example.test/plugin/' );

/** Minimal stand-in for the plugin's main class. */
final class Horex {
	const CPT          = 'horex_aanvraag';
	const OPTIONS_SLUG = 'horex-instellingen';
}

$GLOBALS['horex_test_options'] = array();
$GLOBALS['horex_test_meta']    = array();
$GLOBALS['horex_test_posts']   = array();
$GLOBALS['horex_test_shortcodes'] = array();

function __( $t, $d = null ) { return $t; }
function _e( $t, $d = null ) { echo $t; }
function esc_html__( $t, $d = null ) { return $t; }
function esc_attr__( $t, $d = null ) { return $t; }
function apply_filters( $h, $v ) { return $v; }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_textarea_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function sanitize_title( $v ) {
	$v = strtolower( trim( (string) $v ) );
	$v = strtr( $v, array( 'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e', 'ï' => 'i', 'ö' => 'o' ) );
	$v = preg_replace( '/[^a-z0-9]+/', '-', $v );
	return trim( $v, '-' );
}
function sanitize_email( $v ) { return is_email( trim( (string) $v ) ) ? trim( (string) $v ) : ''; }
function is_email( $v ) { return (bool) filter_var( $v, FILTER_VALIDATE_EMAIL ); }
function sanitize_hex_color( $v ) { return preg_match( '/^#[0-9a-f]{6}$/i', (string) $v ) ? $v : null; }
function esc_url_raw( $v ) { return (string) $v; }
function wp_kses_post( $v ) { return (string) $v; }
function current_time( $format ) { return gmdate( $format ); }

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['horex_test_options'] )
		? $GLOBALS['horex_test_options'][ $name ]
		: $default;
}

function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['horex_test_options'][ $name ] = $value;
	return true;
}

function get_post_meta( $post_id, $key, $single = false ) {
	return isset( $GLOBALS['horex_test_meta'][ $post_id ][ $key ] )
		? $GLOBALS['horex_test_meta'][ $post_id ][ $key ]
		: ( $single ? '' : array() );
}

function update_post_meta( $post_id, $key, $value ) {
	$GLOBALS['horex_test_meta'][ $post_id ][ $key ] = $value;
	return true;
}

function get_post_field( $field, $post_id ) {
	return isset( $GLOBALS['horex_test_posts'][ $post_id ][ $field ] )
		? $GLOBALS['horex_test_posts'][ $post_id ][ $field ]
		: '';
}

function wp_update_post( $post ) {
	$GLOBALS['horex_test_posts'][ $post['ID'] ]['post_title'] = $post['post_title'];
	return $post['ID'];
}

function esc_url( $v ) { return (string) $v; }
function esc_textarea( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function esc_html_e( $t, $d = null ) { echo $t; }
function esc_attr_e( $t, $d = null ) { echo $t; }
function checked( $a, $b = true, $echo = true ) { $r = $a == $b ? ' checked="checked"' : ''; if ( $echo ) { echo $r; } return $r; }
function selected( $a, $b = true, $echo = true ) { $r = $a == $b ? ' selected="selected"' : ''; if ( $echo ) { echo $r; } return $r; }
function wp_get_attachment_image_url( $id, $size = 'thumbnail' ) { return $id ? 'https://example.test/img/' . (int) $id . '.jpg' : false; }
function wp_editor( $content, $id, $settings = array() ) {
	printf( '<textarea name="%s">%s</textarea>', esc_attr( $settings['textarea_name'] ), esc_textarea( $content ) );
}

function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( $path, '/' ); }
function wp_create_nonce( $action ) { return 'nonce-' . $action; }
function add_shortcode( $tag, $callback ) { $GLOBALS['horex_test_shortcodes'][ $tag ] = $callback; return true; }
function shortcode_atts( $pairs, $atts, $tag = '' ) {
	$out = array();
	foreach ( $pairs as $name => $default ) {
		$out[ $name ] = array_key_exists( $name, (array) $atts ) ? $atts[ $name ] : $default;
	}
	return $out;
}
function wp_register_style() { return true; }
function wp_register_script() { return true; }
function wp_enqueue_style() { return true; }
function wp_enqueue_script() { return true; }
function wp_localize_script() { return true; }
function wp_style_is( $handle, $status = 'enqueued' ) { return true; }
function wp_script_is( $handle, $status = 'enqueued' ) { return true; }
function wp_json_encode( $data, $flags = 0, $depth = 512 ) { return json_encode( $data, $flags, $depth ); }
function wp_enqueue_media() { return true; }
function esc_url_raw_stub() {}

function remove_action( $hook, $callback, $priority = 10 ) { return true; }
function add_action( $hook, $callback, $priority = 10, $args = 1 ) { return true; }
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) { return true; }

function get_posts( $args ) {
	$wanted  = isset( $args['meta_value'] ) ? $args['meta_value'] : null;
	$key     = isset( $args['meta_key'] ) ? $args['meta_key'] : '';
	$exclude = isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array();
	$found   = array();

	foreach ( $GLOBALS['horex_test_meta'] as $post_id => $meta ) {
		if ( in_array( $post_id, $exclude, true ) ) {
			continue;
		}

		if ( isset( $meta[ $key ] ) && $meta[ $key ] === $wanted ) {
			$found[] = $post_id;
		}
	}

	return $found;
}

require_once dirname( __DIR__ ) . '/includes/settings-schema.php';
require_once dirname( __DIR__ ) . '/includes/settings.php';
require_once dirname( __DIR__ ) . '/includes/settings-render.php';
require_once dirname( __DIR__ ) . '/includes/defaults.php';
require_once dirname( __DIR__ ) . '/includes/submission-schema.php';
require_once dirname( __DIR__ ) . '/includes/submission.php';
require_once dirname( __DIR__ ) . '/includes/illustrations.php';
require_once dirname( __DIR__ ) . '/includes/frontend.php';

$GLOBALS['horex_test_failures'] = 0;

/**
 * Assert that a value matches, and record the result.
 *
 * @param string $label Description of the check.
 * @param mixed  $got   Actual value.
 * @param mixed  $want  Expected value.
 */
function check( $label, $got, $want ) {
	$ok = $got === $want;

	if ( ! $ok ) {
		$GLOBALS['horex_test_failures']++;
	}

	printf( "%s  %s\n", $ok ? 'PASS' : 'FAIL', $label );

	if ( ! $ok ) {
		echo '      got:  ' . var_export( $got, true ) . "\n";
		echo '      want: ' . var_export( $want, true ) . "\n";
	}
}

/**
 * Print the tally and exit with a status code.
 */
function finish() {
	$failures = $GLOBALS['horex_test_failures'];

	echo $failures ? "\n{$failures} check(s) failed\n" : "\nAll checks passed\n";

	exit( $failures ? 1 : 0 );
}
