<?php
/**
 * Exercises the schema-driven settings sanitiser: the gate that decides what is
 * allowed to persist.
 *
 * Run with:  php tests/test-sanitize.php
 *
 * @package Horex
 */

require_once __DIR__ . '/bootstrap.php';

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

finish();
