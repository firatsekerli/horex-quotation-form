<?php
/**
 * Exercises the admin field renderers: input naming, the depth-tagged repeater
 * templates, and where each field ends up.
 *
 * The template placeholders are the subtlest part of the repeater — a parent
 * substituting its own tokens must leave a nested repeater's template alone.
 *
 * Run with:  php tests/test-render.php
 *
 * @package Horex
 */

require_once __DIR__ . '/bootstrap.php';

update_option( HOREX_OPTION, horex_default_catalogue() );

/**
 * Capture a field's rendered markup.
 *
 * @param string $name  Field name.
 * @param array  $field Schema field.
 * @param mixed  $value Value.
 * @return string
 */
function render( $name, array $field, $value ) {
	ob_start();
	horex_render_field( $name, $field, $value );

	return (string) ob_get_clean();
}

$schema   = horex_settings_schema();
$products = $schema['producten']['fields']['products'];
$html     = render( 'products', $products, horex_get_setting( 'products' ) );

/* Presentation mode is chosen from the visible fields. */
check( 'products render as collapsible cards', horex_repeater_mode( $products ), 'collapsible' );
check(
	'measuring steps render as a simple list',
	horex_repeater_mode( horex_meethulp_fields()['stappen'] ),
	'simple'
);
// Variants carry a name, a subtitle and a photo, so they collapse like products do.
check(
	'variants render as collapsible cards',
	horex_repeater_mode( $products['fields']['uitvoeringen'] ),
	'collapsible'
);

/* Input names. */
check( 'repeater carries its prefix', substr_count( $html, 'data-prefix="horex_settings[products]"' ), 1 );
check( 'first row name is indexed', substr_count( $html, 'name="horex_settings[products][0][naam]"' ), 1 );
check( 'fifth row name is indexed', substr_count( $html, 'name="horex_settings[products][4][naam]"' ), 1 );
check(
	'nested repeater is prefixed by its parent row',
	substr_count( $html, 'data-prefix="horex_settings[products][0][uitvoeringen]"' ),
	1
);
check(
	'nested row name is fully indexed',
	substr_count( $html, 'name="horex_settings[products][0][uitvoeringen][2][naam]"' ),
	1
);
// One per product row, plus the one inside the blank-row template.
check( 'nested repeater is one level deeper', substr_count( $html, 'data-depth="1"' ), 6 );

/* Rows start collapsed; the blank template row does not. */
$seeded  = horex_get_setting( 'products' );
$variants = array_sum( array_map( function ( $product ) { return count( $product['uitvoeringen'] ); }, $seeded ) );

check(
	'every rendered row starts collapsed',
	substr_count( $html, 'horex-row is-collapsed' ),
	count( $seeded ) + $variants
);

/* Keys are tucked behind Advanced, and shown in the header. */
// Exactly one key field and one Advanced block per row, and the key never appears
// before the block that is meant to contain it.
check(
	'every key sits behind an Advanced disclosure',
	substr_count( $html, 'data-horex-slug' ),
	substr_count( $html, '<details class="horex-advanced">' )
);
check(
	'no key is rendered before the first Advanced block',
	strpos( $html, 'data-horex-slug' ) > strpos( $html, '<details class="horex-advanced">' ),
	true
);
check( 'key shown in the row header', substr_count( $html, '<code class="horex-row__key" data-row-key>plisse-horren</code>' ), 1 );

/* Colour rows carry a swatch preview. */
$colours = $schema['framekleuren']['fields']['frame_colours'];
$swatch  = render( 'frame_colours', $colours, horex_get_setting( 'frame_colours' ) );
check( 'colour rows preview their hex', substr_count( $swatch, 'background-color:#383E42' ), 1 );

/* The depth-tagged templates: the heart of adding rows.
 *
 * Rendered from an empty repeater, so the only templates present are the outer one
 * and the nested one it carries — no rendered rows to confuse the extraction. */
$blank = render( 'products', $products, array() );
$open  = strpos( $blank, '<template class="horex-repeater__template">' );
$close = strrpos( $blank, '</template>' );

$template = substr(
	$blank,
	$open + strlen( '<template class="horex-repeater__template">' ),
	$close - $open - strlen( '<template class="horex-repeater__template">' )
);

// The outer repeater's empty state, plus the nested one inside the blank-row template.
check( 'empty repeater shows its empty state', substr_count( $blank, 'horex-repeater__empty">' ), 2 );
// Only the two template rows exist; nothing is rendered as an actual row.
check( 'empty repeater renders no rows', substr_count( $blank, 'data-repeater-row' ), 2 );

check( 'outer template uses depth-0 placeholders', false !== strpos( $template, '__PREFIX0__[__INDEX0__][naam]' ), true );
check( 'outer template carries the nested template', false !== strpos( $template, '__PREFIX1__[__INDEX1__][naam]' ), true );

// What the browser does when "Add product" is clicked.
$added = str_replace(
	array( '__PREFIX0__', '__INDEX0__' ),
	array( 'horex_settings[products]', '0' ),
	$template
);

check( 'adding a row resolves its own placeholders', false === strpos( $added, '__PREFIX0__' ), true );
check( 'new row is named at the next index', false !== strpos( $added, 'name="horex_settings[products][0][naam]"' ), true );
check( 'new row is not collapsed', false === strpos( $added, 'is-collapsed' ), true );
check(
	'nested prefix resolves against the new row',
	false !== strpos( $added, 'data-prefix="horex_settings[products][0][uitvoeringen]"' ),
	true
);
check( 'nested placeholders survive the parent substitution', false !== strpos( $added, '__PREFIX1__[__INDEX1__][naam]' ), true );

// And then adding a variant inside that brand-new row.
$nested = str_replace(
	array( '__PREFIX1__', '__INDEX1__' ),
	array( 'horex_settings[products][0][uitvoeringen]', '0' ),
	$added
);
check( 'nested add resolves cleanly', false === strpos( $nested, '__PREFIX' ), true );
check(
	'nested new row is correctly named',
	false !== strpos( $nested, 'name="horex_settings[products][0][uitvoeringen][0][naam]"' ),
	true
);

/* Markup balance: an unclosed div wrecks the whole screen. */
foreach ( array( 'products' => $html, 'frame_colours' => $swatch ) as $label => $markup ) {
	check(
		"balanced div tags in {$label}",
		substr_count( $markup, '<div' ),
		substr_count( $markup, '</div>' )
	);
}

/* Groups and scalars. */
$meethulp = render( 'meethulp_horren', $schema['meethulp']['fields']['meethulp_horren'], horex_get_setting( 'meethulp_horren' ) );
check( 'group field names are nested', substr_count( $meethulp, 'name="horex_settings[meethulp_horren][titel]"' ), 1 );
check( 'repeater inside a group is named through', substr_count( $meethulp, 'name="horex_settings[meethulp_horren][stappen][0][tekst]"' ), 1 );
check( 'balanced div tags in meethulp', substr_count( $meethulp, '<div' ), substr_count( $meethulp, '</div>' ) );

finish();
