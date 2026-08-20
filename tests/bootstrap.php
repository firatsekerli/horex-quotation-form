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
