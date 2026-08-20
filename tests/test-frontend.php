<?php
/**
 * Exercises the catalogue handed to the front-end, and the step order it implies.
 *
 * The step engine reads nothing but this payload, so a mistake here is a mistake in
 * every screen the customer sees.
 *
 * Run with:  php tests/test-frontend.php
 *
 * @package Horex
 */

require_once __DIR__ . '/bootstrap.php';

horex_maybe_seed();

$config = horex_frontend_config();

/**
 * Work out the step order exactly as assets/js/offerte.js does.
 *
 * @param array $product Product entry from the payload.
 * @param array $config  Whole payload.
 * @return array
 */
function steps_for( array $product, array $config ) {
	$order = array( 'product' );

	if ( $product['uitvoeringen'] ) {
		$order[] = 'uitvoering';
	}

	if ( $config['kleuren'][ $product['kleurType'] ] ) {
		$order[] = 'kleur';
	}

	if ( 'horren' === $product['type'] && $config['gaas'] ) {
		$order[] = 'gaas';
	}

	$order[] = 'maat';

	return $order;
}

$by_slug = array();

foreach ( $config['products'] as $product ) {
	$by_slug[ $product['slug'] ] = $product;
}

/* The payload's shape. */
check( 'five products are published', count( $config['products'] ), 5 );
check( 'three colour lists', array_keys( $config['kleuren'] ), array( 'frame', 'stof', 'doek' ) );
check( 'measurement rules travel with it', $config['maten']['min'], 300 );
check( 'measurement rules carry the max', $config['maten']['max'], 6000 );
check( 'the warning text is included', '' !== $config['maten']['waarschuwing'], true );
check( 'a submit nonce is issued', $config['nonce'], 'nonce-horex_submit' );
check( 'both measuring-help groups travel', array_keys( $config['meethulp'] ), array( 'horren', 'gordijn' ) );
check( 'all five drawings travel', count( $config['tekeningen'] ), 5 );

/* Colour type is derived from product type, never stored twice. */
check( 'insect screens use frame colours', $by_slug['plisse-horren']['kleurType'], 'frame' );
check( 'curtains use fabric colours', $by_slug['wave-gordijnen']['kleurType'], 'stof' );
check( 'sun shading uses canvas colours', $by_slug['veranda-zonwering']['kleurType'], 'doek' );

/* And so is the colour question. */
check( 'frame question', $by_slug['plisse-horren']['kleurVraag'], 'Welke kleur frame?' );
check( 'fabric question', $by_slug['plisse-gordijnen']['kleurVraag'], 'Welke stofkleur?' );
check( 'canvas question', $by_slug['veranda-zonwering']['kleurVraag'], 'Welke doekkleur?' );

/* The step order the brief specifies. */
check(
	'insect screens run all five steps',
	steps_for( $by_slug['plisse-horren'], $config ),
	array( 'product', 'uitvoering', 'kleur', 'gaas', 'maat' )
);
check(
	'fitted insect screens run all five steps',
	steps_for( $by_slug['inzet-horren'], $config ),
	array( 'product', 'uitvoering', 'kleur', 'gaas', 'maat' )
);
check(
	'pleated curtains run three steps',
	steps_for( $by_slug['plisse-gordijnen'], $config ),
	array( 'product', 'kleur', 'maat' )
);
check(
	'wave curtains run three steps',
	steps_for( $by_slug['wave-gordijnen'], $config ),
	array( 'product', 'kleur', 'maat' )
);
check(
	'sun shading runs three steps',
	steps_for( $by_slug['veranda-zonwering'], $config ),
	array( 'product', 'kleur', 'maat' )
);

/* Curtains and sun shading carry no variants at all. */
check( 'curtains have no variants', $by_slug['plisse-gordijnen']['uitvoeringen'], array() );
check( 'insect screens keep theirs', count( $by_slug['plisse-horren']['uitvoeringen'] ), 3 );
check( 'variant subtitles travel', $by_slug['plisse-horren']['uitvoeringen'][0]['omschrijving'], 'Achterdeur, tuindeur of schuifpui' );

/* An empty colour list must drop its step, not leave a dead end. */
update_option( HOREX_OPTION, array_merge( get_option( HOREX_OPTION ), array( 'doek_colours' => array() ) ) );
$stripped = horex_frontend_config();
$shading  = null;

foreach ( $stripped['products'] as $product ) {
	if ( 'veranda-zonwering' === $product['slug'] ) {
		$shading = $product;
	}
}

check(
	'an empty palette drops the colour step',
	steps_for( $shading, $stripped ),
	array( 'product', 'maat' )
);

/* Measuring help falls back to the shipped diagram when no image is uploaded. */
check( 'shipped diagram is used when none is uploaded', '' !== $config['meethulp']['horren']['tekening'], true );
check( 'the uploaded diagram slot is empty', $config['meethulp']['horren']['diagram'], '' );
check( 'measuring steps travel', count( $config['meethulp']['horren']['stappen'] ), 5 );
check( 'the curtain group has its own steps', count( $config['meethulp']['gordijn']['stappen'] ), 5 );

/* Nameless rows never reach the customer. */
update_option(
	HOREX_OPTION,
	array_merge(
		get_option( HOREX_OPTION ),
		array(
			'products' => array(
				array( 'naam' => '', 'slug' => 'leeg', 'type' => 'horren', 'uitvoeringen' => array() ),
				array( 'naam' => 'Echt product', 'slug' => 'echt', 'type' => 'horren', 'uitvoeringen' => array( array( 'naam' => '', 'slug' => 'x' ) ) ),
			),
		)
	)
);
$filtered = horex_frontend_config();
check( 'a product without a name is skipped', count( $filtered['products'] ), 1 );
check( 'a variant without a name is skipped', $filtered['products'][0]['uitvoeringen'], array() );

/* The shortcode renders the shell. */
$GLOBALS['horex_test_options'] = array();
horex_maybe_seed();
$html = horex_render_shortcode( array() );

check( 'shortcode renders a root element', substr_count( $html, 'data-horex' ) > 0, true );
check( 'shortcode renders the stage', substr_count( $html, 'data-horex-stage' ), 1 );
check( 'shortcode renders the progress bar', substr_count( $html, 'data-horex-progress' ), 1 );
check( 'shortcode sticks by default', substr_count( $html, 'horex--sticky' ), 1 );
check( 'shortcode warns without JavaScript', substr_count( $html, '<noscript>' ), 1 );

/*
 * The catalogue must travel inside the markup. Handing it over through a separate
 * inline script lets any plugin that defers or combines scripts land it after the
 * file that reads it, and the configurator renders blank steps with no error.
 */
check( 'the catalogue is embedded in the markup', substr_count( $html, 'data-horex-config' ), 1 );

preg_match( '#<script type="application/json" data-horex-config>(.*?)</script>#s', $html, $embedded );
check( 'the embedded block is present', isset( $embedded[1] ), true );

$payload = json_decode( trim( $embedded[1] ), true );
check( 'the embedded block is valid JSON', is_array( $payload ), true );
check( 'it carries the products', count( $payload['products'] ), 5 );
check( 'it carries the drawings', count( $payload['tekeningen'] ), 5 );

// JSON_HEX_TAG: an SVG in the payload must not be able to close the script tag.
check( 'no raw angle brackets can close the tag early', false !== strpos( $embedded[1], '<' ), false );
check( 'the drawings survive the escaping', false !== strpos( $payload['tekeningen']['plisse-hor'], '<svg' ), true );
check( 'balanced div tags', substr_count( $html, '<div' ), substr_count( $html, '</div>' ) );

$loose = horex_render_shortcode( array( 'sticky' => 'nee', 'offset' => '80' ) );
check( 'sticky can be turned off', substr_count( $loose, 'horex--sticky' ), 0 );
check( 'an offset is applied', substr_count( $loose, '--horex-offset:80px' ), 1 );
check( 'a bogus offset cannot inject markup', substr_count( horex_render_shortcode( array( 'offset' => '"><script>' ) ), '<script>' ), 0 );

finish();
