<?php
/**
 * Exercises the schema-driven sanitiser without WordPress loaded.
 *
 * The WordPress functions the sanitiser depends on are stubbed with equivalents that
 * are close enough to test our own logic — this checks the schema gate, not core.
 *
 * Run with:  php tests/test-sanitize.php
 */
define( 'ABSPATH', __DIR__ );
define( 'HOREX_VERSION', 'test' );

function __( $t, $d = null ) { return $t; }
function apply_filters( $h, $v ) { return $v; }
function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES ); }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_textarea_field( $v ) { return trim( strip_tags( (string) $v ) ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function sanitize_title( $v ) {
	$v = strtolower( trim( (string) $v ) );
	$v = str_replace( array( 'é', 'è', 'ë', 'ê' ), 'e', $v );
	$v = preg_replace( '/[^a-z0-9]+/', '-', $v );
	return trim( $v, '-' );
}
function sanitize_email( $v ) { return trim( (string) $v ); }
function is_email( $v ) { return (bool) filter_var( $v, FILTER_VALIDATE_EMAIL ); }
function sanitize_hex_color( $v ) { return preg_match( '/^#[0-9a-f]{6}$/i', (string) $v ) ? $v : null; }
function esc_url_raw( $v ) { return (string) $v; }
function wp_kses_post( $v ) { return (string) $v; }
function get_option( $n, $d = false ) { return $d; }
function update_option( $n, $v, $a = null ) { return true; }
require dirname( __DIR__ ) . '/includes/settings-schema.php';
require dirname( __DIR__ ) . '/includes/settings.php';
require dirname( __DIR__ ) . '/includes/defaults.php';

$fails = 0;
function check( $label, $got, $want ) {
	global $fails;
	$ok = $got === $want;
	if ( ! $ok ) { $fails++; }
	printf( "%s  %s\n", $ok ? 'PASS' : 'FAIL', $label );
	if ( ! $ok ) {
		echo "      got:  " . var_export( $got, true ) . "\n";
		echo "      want: " . var_export( $want, true ) . "\n";
	}
}

$schema = horex_settings_schema();

/* 1. A products repeater round-trip, as the browser would post it. */
$posted = array(
	'products' => array(
		'0' => array(
			'naam'         => '  Plissé horren ',
			'slug'         => '',
			'type'         => 'horren',
			'foto'         => '12',
			'uitvoeringen' => array(
				'0' => array( 'naam' => 'Plissé hordeur', 'slug' => '', 'foto' => '0' ),
				'1' => array( 'naam' => '', 'slug' => '', 'foto' => '0' ),
			),
		),
		'1' => array(
			'naam' => '', 'slug' => '', 'type' => 'horren', 'foto' => '0', 'uitvoeringen' => array(),
		),
		'2' => array(
			'naam' => 'Wave gordijnen', 'slug' => '', 'type' => 'gordijn', 'foto' => '0', 'uitvoeringen' => array(),
		),
	),
);
$clean = horex_sanitize_settings( $posted, $schema['producten']['fields'] );
$rows  = $clean['products'];

check( 'empty-named row is dropped', count( $rows ), 2 );
check( 'rows are re-indexed from zero', array_keys( $rows ), array( 0, 1 ) );
check( 'slug derived from name', $rows[0]['slug'], 'plisse-horren' );
check( 'image stored as attachment id', $rows[0]['foto'], 12 );
check( 'nested empty row dropped', count( $rows[0]['uitvoeringen'] ), 1 );
check( 'nested slug derived', $rows[0]['uitvoeringen'][0]['slug'], 'plisse-hordeur' );
check( 'second product survives', $rows[1]['naam'], 'Wave gordijnen' );

/* 2. Unknown keys must not survive the gate. */
$posted = array(
	'products' => array(
		array( 'naam' => 'X', 'slug' => 'x', 'type' => 'horren', 'foto' => 0, 'uitvoeringen' => array(), 'evil' => '<script>' ),
	),
	'stowaway' => 'should not persist',
);
$clean = horex_sanitize_settings( $posted, $schema['producten']['fields'] );
check( 'unknown top-level key dropped', array_keys( $clean ), array( 'products' ) );
check( 'unknown sub-field dropped', array_keys( $clean['products'][0] ), array( 'naam', 'slug', 'type', 'foto', 'uitvoeringen' ) );
check( 'schema sub-fields all kept', isset( $clean['products'][0]['foto'] ), true );

/* 3. Duplicate slugs get suffixed so state keys stay unique. */
$posted = array(
	'frame_colours' => array(
		array( 'naam' => 'Wit', 'slug' => '', 'hex' => '#FFFFFF', 'ral' => '', 'swatch' => 0 ),
		array( 'naam' => 'Wit', 'slug' => '', 'hex' => '#FEFEFE', 'ral' => '', 'swatch' => 0 ),
		array( 'naam' => 'Wit', 'slug' => '', 'hex' => '#FDFDFD', 'ral' => '', 'swatch' => 0 ),
	),
);
$clean = horex_sanitize_settings( $posted, $schema['framekleuren']['fields'] );
check( 'duplicate slugs suffixed', array_column( $clean['frame_colours'], 'slug' ), array( 'wit', 'wit-2', 'wit-3' ) );

/* 4. An existing slug is never rewritten when the name changes. */
$posted = array(
	'frame_colours' => array(
		array( 'naam' => 'Antraciet donker', 'slug' => 'antraciet', 'hex' => '#383E42', 'ral' => '', 'swatch' => 0 ),
	),
);
$clean = horex_sanitize_settings( $posted, $schema['framekleuren']['fields'] );
check( 'existing slug preserved on rename', $clean['frame_colours'][0]['slug'], 'antraciet' );

/* 5. Scalars: numbers clamp, colours validate, emails filter. */
$clean = horex_sanitize_settings(
	array( 'min_mm' => '-50', 'max_mm' => '999999', 'waarschuwing_tekst' => 'Let op' ),
	$schema['maten']['fields']
);
check( 'negative number clamped to min', $clean['min_mm'], 0 );
check( 'negative is not sign-flipped', $clean['min_mm'] === 50, false );
check( 'oversized number clamped to max', $clean['max_mm'], 100000 );

$clean = horex_sanitize_settings(
	array( 'ontvangers' => "info@hor-ex.nl\nnonsense\n\nverkoop@hor-ex.nl\ninfo@hor-ex.nl" ),
	$schema['email']['fields']
);
check( 'invalid addresses dropped and deduped', $clean['ontvangers'], array( 'info@hor-ex.nl', 'verkoop@hor-ex.nl' ) );

$clean = horex_sanitize_settings(
	array( 'frame_colours' => array( array( 'naam' => 'X', 'slug' => 'x', 'hex' => 'javascript:alert(1)', 'ral' => '', 'swatch' => 0 ) ) ),
	$schema['framekleuren']['fields']
);
check( 'non-hex colour rejected', $clean['frame_colours'][0]['hex'], '' );

/* 6. Groups keep their shape, including a nested repeater. */
$clean = horex_sanitize_settings(
	array(
		'meethulp_horren' => array(
			'titel'   => 'Hoe meet ik dit op?',
			'diagram' => '7',
			'stappen' => array( array( 'tekst' => 'Meet de binnenmaat.' ), array( 'tekst' => '' ) ),
		),
	),
	$schema['meethulp']['fields']
);
check( 'group sub-fields kept', $clean['meethulp_horren']['titel'], 'Hoe meet ik dit op?' );
check( 'repeater inside group sanitised', count( $clean['meethulp_horren']['stappen'] ), 1 );
check( 'missing group sub-field defaults', $clean['meethulp_horren']['video_url'], '' );
check( 'untouched second group still present', array_key_exists( 'meethulp_gordijn', $clean ), true );

/* 7. Seed data must match the schema the form renders. */
$defaults = horex_default_catalogue();
$known    = array_keys( horex_settings_fields() );
foreach ( array_keys( $defaults ) as $key ) {
	check( "seed key '$key' exists in schema", in_array( $key, $known, true ), true );
}
$product_fields = array_keys( $schema['producten']['fields']['products']['fields'] );
foreach ( $defaults['products'] as $i => $product ) {
	check( "seeded product $i matches schema fields", array_keys( $product ), $product_fields );
}
$clean = horex_sanitize_settings( $defaults, horex_settings_fields() );
check( 'all five seeded products survive sanitising', count( $clean['products'] ), 5 );
check( 'seeded slugs unchanged by sanitising', array_column( $clean['products'], 'slug' ), array_column( $defaults['products'], 'slug' ) );
check( 'seeded uitvoeringen preserved', count( $clean['products'][0]['uitvoeringen'] ), 3 );
check( 'seeded framekleuren preserved', count( $clean['frame_colours'] ), 6 );
check( 'seeded meethulp steps preserved', count( $clean['meethulp_horren']['stappen'] ), 5 );

echo $fails ? "\n$fails check(s) failed\n" : "\nAll checks passed\n";
exit( $fails ? 1 : 0 );
