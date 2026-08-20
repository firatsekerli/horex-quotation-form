<?php
/**
 * Exercises the catalogue seeder and the migration that fills in fields added after
 * an install was first seeded.
 *
 * The migration runs against live client data, so the important property is not that
 * it adds the new fields — it is that it leaves everything else exactly alone.
 *
 * Run with:  php tests/test-catalogue.php
 *
 * @package Horex
 */

require_once __DIR__ . '/bootstrap.php';

/* The catalogue as it was seeded before the new fields existed. */
$old_catalogue = array(
	'products'      => array(
		array(
			'naam'         => 'Plissé horren',
			'slug'         => 'plisse-horren',
			'type'         => 'horren',
			'foto'         => 0,
			'uitvoeringen' => array(
				array( 'naam' => 'Plissé hordeur', 'slug' => 'plisse-hordeur', 'foto' => 0 ),
				array( 'naam' => 'Plissé horraam', 'slug' => 'plisse-horraam', 'foto' => 0 ),
			),
		),
		array(
			'naam'         => 'Wave gordijnen',
			'slug'         => 'wave-gordijnen',
			'type'         => 'gordijn',
			'foto'         => 0,
			'uitvoeringen' => array(),
		),
		// Something Hor-Ex added themselves: no shipped counterpart.
		array(
			'naam'         => 'Rolhor op maat',
			'slug'         => 'rolhor-op-maat',
			'type'         => 'horren',
			'foto'         => 44,
			'uitvoeringen' => array(),
		),
	),
	'frame_colours' => array(
		// Edited: a different hex and a photo of their own.
		array( 'naam' => 'Antraciet', 'slug' => 'antraciet', 'hex' => '#111111', 'ral' => 'RAL 7016', 'swatch' => 77 ),
		array( 'naam' => 'Structuurlak', 'slug' => 'structuurlak', 'hex' => '#4A4A4A', 'ral' => '', 'swatch' => 0 ),
	),
	'gaas'          => array(
		array( 'naam' => 'Anti-pollen gaas', 'slug' => 'anti-pollen-gaas', 'omschrijving' => 'Eigen tekst', 'foto' => 0 ),
	),
);

update_option( HOREX_OPTION, $old_catalogue );
update_option( HOREX_SEED_FLAG, '0.1.0' );

horex_migrate_catalogue();
$after = get_option( HOREX_OPTION );

/* New fields arrive, matched by key. */
check( 'product gains its subtitle', $after['products'][0]['kort'], 'Schuift open als een harmonica' );
check( 'product gains its preview fill', $after['products'][0]['vulling'], 'gaas' );
check( 'product gains its drawing', $after['products'][0]['illustratie'], 'plisse-hor' );
check( 'wave curtain gets the wave fill, not the mesh one', $after['products'][1]['vulling'], 'wave' );
check( 'variant gains its subtitle', $after['products'][0]['uitvoeringen'][0]['omschrijving'], 'Achterdeur, tuindeur of schuifpui' );
check( 'textured coating is flagged', $after['frame_colours'][1]['textuur'], true );
check( 'plain coating is not', $after['frame_colours'][0]['textuur'], false );
check( 'fine mesh is flagged', $after['gaas'][0]['fijnmazig'], true );

/* Nothing Hor-Ex touched is overwritten. */
check( 'edited hex survives', $after['frame_colours'][0]['hex'], '#111111' );
check( 'uploaded swatch survives', $after['frame_colours'][0]['swatch'], 77 );
check( 'edited description survives', $after['gaas'][0]['omschrijving'], 'Eigen tekst' );
check( 'their own product survives untouched', $after['products'][2]['naam'], 'Rolhor op maat' );
check( 'their own product keeps its photo', $after['products'][2]['foto'], 44 );
check( 'their own product gains no invented fields', array_key_exists( 'vulling', $after['products'][2] ), false );

/* Rows they removed stay removed. */
check( 'deleted products are not resurrected', count( $after['products'] ), 3 );
check( 'deleted colours are not resurrected', count( $after['frame_colours'] ), 2 );
check( 'variants they removed stay removed', count( $after['products'][0]['uitvoeringen'] ), 2 );

/* Empty palettes are filled; the flow needs something to offer. */
check( 'fabric placeholders seeded', count( $after['stof_colours'] ), 6 );
check( 'canvas placeholders seeded', count( $after['doek_colours'] ), 4 );

/* Running it again changes nothing. */
$again = $after;
horex_migrate_catalogue();
check( 'migration is idempotent', get_option( HOREX_OPTION ), $again );

/* A palette they have filled in is left alone. */
update_option( HOREX_CATALOGUE_VERSION, 1 );
update_option( HOREX_OPTION, array_merge( $after, array( 'stof_colours' => array( array( 'naam' => 'Linnen', 'slug' => 'linnen', 'swatch' => 0, 'hex' => '#EEE', 'omschrijving' => '' ) ) ) ) );
horex_migrate_catalogue();
$third = get_option( HOREX_OPTION );
check( 'a filled palette is not replaced by placeholders', count( $third['stof_colours'] ), 1 );

/* A fresh install goes straight to the current shape. */
$GLOBALS['horex_test_options'] = array();
horex_maybe_seed();
$fresh = get_option( HOREX_OPTION );
check( 'fresh install is already migrated', (int) get_option( HOREX_CATALOGUE_VERSION ), HOREX_CATALOGUE_SCHEMA );
check( 'fresh install has all five products', count( $fresh['products'] ), 5 );
check( 'fresh install has fills on every product', count( array_filter( array_column( $fresh['products'], 'vulling' ) ) ), 5 );
check( 'fresh install has subtitles on every product', count( array_filter( array_column( $fresh['products'], 'kort' ) ) ), 5 );

/* The seeded catalogue must satisfy the schema the form renders. */
$clean = horex_sanitize_settings( horex_default_catalogue(), horex_settings_fields() );
check( 'seed survives its own sanitiser', count( $clean['products'] ), 5 );
check( 'seeded fills survive', array_column( $clean['products'], 'vulling' ), array( 'gaas', 'gaas', 'plisse', 'wave', 'doek' ) );
check( 'seeded drawings are valid choices', count( array_diff( array_column( $clean['products'], 'illustratie' ), array_keys( horex_illustrations() ) ) ), 0 );
check( 'seeded fills are valid choices', count( array_diff( array_column( $clean['products'], 'vulling' ), array_keys( horex_preview_fills() ) ) ), 0 );
check( 'seeded variant subtitles survive', $clean['products'][0]['uitvoeringen'][0]['omschrijving'], 'Achterdeur, tuindeur of schuifpui' );

finish();
