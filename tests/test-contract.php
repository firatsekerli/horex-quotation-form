<?php
/**
 * Checks that the front-end and the back-end agree on names.
 *
 * Everything crossing the wire is a plain string key. Nothing in PHP or JavaScript
 * fails when one side renames one — the field just arrives empty, and a customer's
 * phone number quietly stops being stored. So this suite reads both source files and
 * compares them.
 *
 * Run with:  php tests/test-contract.php
 *
 * @package Horex
 */

require_once __DIR__ . '/bootstrap.php';

horex_maybe_seed();

$js  = (string) file_get_contents( dirname( __DIR__ ) . '/assets/js/offerte.js' );
$php = (string) file_get_contents( dirname( __DIR__ ) . '/includes/ajax.php' );

/**
 * The keys of an object literal in the script.
 *
 * @param string $source  Script source.
 * @param string $pattern Pattern capturing the literal's body.
 * @return array
 */
function literal_keys( $source, $pattern ) {
	if ( ! preg_match( $pattern, $source, $found ) ) {
		return array();
	}

	preg_match_all( '/(?:^|[{,])\s*(\w+)\s*:/m', $found[1], $keys );

	return array_values( array_unique( $keys[1] ) );
}

/**
 * Every match of a capturing pattern.
 *
 * @param string $source  Source text.
 * @param string $pattern Pattern with one capture group.
 * @return array
 */
function all_matches( $source, $pattern ) {
	preg_match_all( $pattern, $source, $found );

	return array_values( array_unique( $found[1] ) );
}

/* What the browser sends. */
$sent_form  = all_matches( $js, "/body\.append\(\s*'(\w+)'/" );
$sent_klant = literal_keys( $js, '/var customer = \{(.*?)\};/s' );
$sent_item  = literal_keys( $js, '/items\.push\(\s*\{(.*?)\}\s*\)/s' );

/* What the endpoint reads. */
$read_post  = all_matches( $php, "/\\\$_POST\[\s*'(\w+)'\s*\]/" );
$read_klant = all_matches( $php, "/\\\$klant\[\s*'(\w+)'\s*\]/" );
$read_item  = all_matches( $php, "/\\\$row\[\s*'(\w+)'\s*\]/" );

check( 'the browser sends form fields', count( $sent_form ) > 0, true );
check( 'the browser sends customer fields', count( $sent_klant ) > 0, true );
check( 'the browser sends measurement fields', count( $sent_item ) > 0, true );

/* Form fields. The nonce is read through check_ajax_referer, not $_POST. */
$expected_form = array( 'aanvraag', 'action', 'nonce', 'website' );
sort( $sent_form );
check( 'the form fields are the expected four', $sent_form, $expected_form );

foreach ( array( 'aanvraag', 'website' ) as $field ) {
	check( "the endpoint reads the {$field} field", in_array( $field, $read_post, true ), true );
}

check( 'the nonce field name matches the endpoint', false !== strpos( $php, "check_ajax_referer( 'horex_submit', 'nonce'" ), true );
check( 'the action name matches the hook', false !== strpos( $php, "wp_ajax_nopriv_horex_submit" ), true );
check(
	'the action the browser posts is the one registered',
	false !== strpos( (string) file_get_contents( dirname( __DIR__ ) . '/includes/frontend.php' ), "'action'    => 'horex_submit'" ),
	true
);
check(
	'the nonce the browser posts is minted for that action',
	false !== strpos( (string) file_get_contents( dirname( __DIR__ ) . '/includes/frontend.php' ), "wp_create_nonce( 'horex_submit' )" ),
	true
);

/* Customer fields: every one sent must be read, and must exist in the schema. */
$schema_fields = array_keys( horex_submission_fields() );

foreach ( $sent_klant as $field ) {
	check( "the endpoint reads the customer's {$field}", in_array( $field, $read_klant, true ), true );
	check( "the submission schema has a {$field} field", in_array( $field, $schema_fields, true ), true );
}

check( 'the endpoint reads nothing the browser does not send', array_values( array_diff( $read_klant, $sent_klant ) ), array() );

/* Measurement fields. */
foreach ( $sent_item as $field ) {
	check( "the endpoint reads the measurement's {$field}", in_array( $field, $read_item, true ), true );
}

check( 'the endpoint reads no measurement key the browser does not send', array_values( array_diff( $read_item, $sent_item ) ), array() );

/* What the resolver produces must be storable. */
$resolved = horex_resolve_items(
	array(
		array( 'product' => 'plisse-horren', 'uitvoering' => 'plisse-hordeur', 'kleur' => 'antraciet', 'gaas' => 'standaard-gaas', 'ruimte' => 'Zolder', 'breedte' => 900, 'hoogte' => 1200 ),
	)
);

$item_fields = array_keys( horex_submission_fields()['items']['fields'] );

foreach ( array_keys( $resolved[0] ) as $field ) {
	check( "the resolver's {$field} is a field the request can store", in_array( $field, $item_fields, true ), true );
}

/* What the browser reads out of the catalogue must be what PHP puts in it. */
$config    = horex_frontend_config();
$js_config = all_matches( $js, '/config\.(\w+)/' );

foreach ( $js_config as $key ) {
	check( "the catalogue carries config.{$key}", array_key_exists( $key, $config ), true );
}

$product_keys = all_matches( $js, '/product\.(\w+)/' );
$published    = $config['products'][0];

foreach ( $product_keys as $key ) {
	check( "a published product carries {$key}", array_key_exists( $key, $published ), true );
}

/* The step engine's inputs. */
foreach ( array( 'type', 'uitvoeringen', 'kleurType', 'kleurVraag', 'vulling', 'meethulp' ) as $key ) {
	check( "the step engine can read {$key}", array_key_exists( $key, $published ), true );
}

/* The shortcode markup carries the hooks the script looks for. */
$markup = horex_render_shortcode( array() );

foreach ( array( 'data-horex-stage', 'data-horex-step', 'data-horex-progress', 'data-horex-modal', 'data-horex-config' ) as $hook ) {
	check( "the markup provides {$hook}", false !== strpos( $markup, $hook ), true );
	check( "the script looks for {$hook}", false !== strpos( $js, $hook ), true );
}

finish();
