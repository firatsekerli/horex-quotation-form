<?php
/**
 * The confirmed catalogue, seeded once so a fresh install is usable immediately.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Option flag recording that the catalogue has been seeded.
 */
const HOREX_SEED_FLAG = 'horex_seeded';

/**
 * Option holding the catalogue shape an install has been migrated to.
 */
const HOREX_CATALOGUE_VERSION = 'horex_catalogue_version';

/**
 * Current catalogue shape. Bump when fields are added to the shipped catalogue.
 */
const HOREX_CATALOGUE_SCHEMA = 2;

/**
 * The starting catalogue: the products, colours, gaas and measuring help Hor-Ex
 * confirmed. Everything here is editable in the admin afterwards.
 *
 * Stof- and doekkleuren are deliberately absent — Hor-Ex supplies the real swatch
 * range, and inventing colour names would put options in front of customers that
 * cannot actually be ordered.
 *
 * @return array
 */
function horex_default_catalogue() {
	return array(
		'products'         => array(
			array(
				'naam'         => 'Plissé horren',
				'kort'         => 'Schuift open als een harmonica',
				'slug'         => 'plisse-horren',
				'type'         => 'horren',
				'vulling'      => 'gaas',
				'illustratie'  => 'plisse-hor',
				'foto'         => 0,
				'uitvoeringen' => array(
					array(
						'naam'         => 'Plissé hordeur',
						'omschrijving' => 'Achterdeur, tuindeur of schuifpui',
						'slug'         => 'plisse-hordeur',
						'foto'         => 0,
					),
					array(
						'naam'         => 'Plissé horraam',
						'omschrijving' => 'Voor ramen die u regelmatig opent',
						'slug'         => 'plisse-horraam',
						'foto'         => 0,
					),
					array(
						'naam'         => 'DUO — hor + gordijn',
						'omschrijving' => 'Insectenwering en lichtregulering in één systeem',
						'slug'         => 'duo-hor-gordijn',
						'foto'         => 0,
					),
				),
			),
			array(
				'naam'         => 'Inzet horren',
				'kort'         => 'Klikt in het kozijn, zonder boren',
				'slug'         => 'inzet-horren',
				'type'         => 'horren',
				'vulling'      => 'gaas',
				'illustratie'  => 'inzet-hor',
				'foto'         => 0,
				'uitvoeringen' => array(
					array(
						'naam'         => 'Inzet horraam',
						'omschrijving' => 'Vast frame, klikt in het kozijn',
						'slug'         => 'inzet-horraam',
						'foto'         => 0,
					),
					array(
						'naam'         => 'Inzet plissé horraam',
						'omschrijving' => 'Schuifbaar, open en dicht wanneer u wilt',
						'slug'         => 'inzet-plisse-horraam',
						'foto'         => 0,
					),
				),
			),
			array(
				'naam'         => 'Plissé gordijnen',
				'kort'         => 'Stopt op elke gewenste hoogte',
				'slug'         => 'plisse-gordijnen',
				'type'         => 'gordijn',
				'vulling'      => 'plisse',
				'illustratie'  => 'plisse-gordijn',
				'foto'         => 0,
				'uitvoeringen' => array(),
			),
			array(
				'naam'         => 'Wave gordijnen',
				'kort'         => 'Strakke golven, plafond tot vloer',
				'slug'         => 'wave-gordijnen',
				'type'         => 'gordijn',
				'vulling'      => 'wave',
				'illustratie'  => 'wave-gordijn',
				'foto'         => 0,
				'uitvoeringen' => array(),
			),
			array(
				'naam'         => 'Veranda zonwering',
				'kort'         => 'Schermdoek tegen zon en inkijk',
				'slug'         => 'veranda-zonwering',
				'type'         => 'zonwering',
				'vulling'      => 'doek',
				'illustratie'  => 'veranda-zonwering',
				'foto'         => 0,
				'uitvoeringen' => array(),
			),
		),
		'frame_colours'    => array(
			array(
				'naam'   => 'Wit',
				'slug'   => 'wit',
				'hex'    => '#FFFFFF',
				'ral'    => 'RAL 9016',
				'textuur' => false,
				'naam'   => 'Wit',
				'slug'   => 'wit',
				'hex'    => '#FFFFFF',
				'ral'    => 'RAL 9016',
				'swatch' => 0,
			),
			array(
				'naam'   => 'Gebroken wit',
				'slug'   => 'gebroken-wit',
				'hex'    => '#FDF4E3',
				'ral'    => 'RAL 9001',
				'textuur' => false,
				'naam'   => 'Gebroken wit',
				'slug'   => 'gebroken-wit',
				'hex'    => '#FDF4E3',
				'ral'    => 'RAL 9001',
				'swatch' => 0,
			),
			array(
				'naam'   => 'Antraciet',
				'slug'   => 'antraciet',
				'hex'    => '#383E42',
				'ral'    => 'RAL 7016',
				'textuur' => false,
				'naam'   => 'Antraciet',
				'slug'   => 'antraciet',
				'hex'    => '#383E42',
				'ral'    => 'RAL 7016',
				'swatch' => 0,
			),
			array(
				'naam'   => 'Grijs RAL 7039',
				'slug'   => 'grijs-ral-7039',
				'hex'    => '#6B665E',
				'ral'    => 'RAL 7039',
				'textuur' => false,
				'naam'   => 'Grijs RAL 7039',
				'slug'   => 'grijs-ral-7039',
				'hex'    => '#6B665E',
				'ral'    => 'RAL 7039',
				'swatch' => 0,
			),
			array(
				'naam'   => 'Zwart',
				'slug'   => 'zwart',
				'hex'    => '#1E1E1E',
				'ral'    => 'RAL 9005',
				'textuur' => false,
				'naam'   => 'Zwart',
				'slug'   => 'zwart',
				'hex'    => '#1E1E1E',
				'ral'    => 'RAL 9005',
				'swatch' => 0,
			),
			array(
				'naam'    => 'Structuurlak',
				'slug'    => 'structuurlak',
				'hex'     => '#2E3234',
				'ral'     => '',
				'textuur' => true,
				'swatch'  => 0,
			),
		),
		'gaas'             => array(
			array(
				'naam'         => 'Standaard gaas (extra sterk)',
				'slug'         => 'standaard-gaas',
				'omschrijving' => 'Extra sterk. Houdt insecten buiten, doorkijk blijft helder.',
				'fijnmazig'    => false,
				'foto'         => 0,
			),
			array(
				'naam'         => 'Anti-pollen gaas',
				'slug'         => 'anti-pollen-gaas',
				'omschrijving' => 'Fijnere weving. Houdt ook pollen en stuifmeel tegen — fijn bij hooikoorts.',
				'fijnmazig'    => true,
				'foto'         => 0,
			),
		),
		'stof_colours'     => horex_placeholder_palette(
			array(
				array( 'Gebroken wit', 'gebroken-wit', '#F4F0E6' ),
				array( 'Zand', 'zand', '#DCCFB6' ),
				array( 'Taupe', 'taupe', '#A99884' ),
				array( 'Licht grijs', 'licht-grijs', '#C9C7C1' ),
				array( 'Antraciet', 'antraciet', '#4A4E50' ),
				array( 'Zwart', 'zwart', '#232323' ),
			)
		),
		'doek_colours'     => horex_placeholder_palette(
			array(
				array( 'Gebroken wit', 'gebroken-wit', '#F1EDE2' ),
				array( 'Zand', 'zand', '#D9CBB0' ),
				array( 'Grijs', 'grijs', '#8B8A84' ),
				array( 'Antraciet', 'antraciet', '#3D4245' ),
			)
		),
		'meethulp_horren'  => array(
			'titel'     => 'Hoe meet ik dit op?',
			'diagram'   => 0,
			'stappen'   => array(
				array( 'tekst' => 'Meet de binnenmaat van het kozijn: de opening waar de hor in komt.' ),
				array( 'tekst' => 'Meet de breedte op drie plaatsen — boven, in het midden en onder.' ),
				array( 'tekst' => 'Meet de hoogte op drie plaatsen — links, in het midden en rechts.' ),
				array( 'tekst' => 'Noteer steeds de kleinste maat. Wij houden rekening met de montage.' ),
				array( 'tekst' => 'Noteer de maten in millimeters, dus 1200 in plaats van 120 cm.' ),
			),
			'video_url' => '',
		),
		'meethulp_gordijn' => array(
			'titel'     => 'Hoe meet ik dit op?',
			'diagram'   => 0,
			'stappen'   => array(
				array( 'tekst' => 'Meet de breedte van de rail of roede, niet van het raam.' ),
				array( 'tekst' => 'Meet de hoogte vanaf de bovenkant van de rail tot waar het gordijn moet eindigen.' ),
				array( 'tekst' => 'Tot op de vloer of tot op de vensterbank — geef door welke van de twee u heeft gemeten.' ),
				array( 'tekst' => 'Meet op drie plaatsen en noteer de kleinste maat.' ),
				array( 'tekst' => 'Noteer de maten in millimeters, dus 2400 in plaats van 240 cm.' ),
			),
			'video_url' => '',
		),
	);
}

/**
 * Build a fabric or canvas palette from name, key and hex triples.
 *
 * These stand in until Hor-Ex supplies the real swatch range; the settings tabs say
 * so. They exist so the curtain and sun-shading flows can be walked end to end.
 *
 * @param array $rows Triples of name, slug and hex.
 * @return array
 */
function horex_placeholder_palette( array $rows ) {
	$palette = array();

	foreach ( $rows as $row ) {
		$palette[] = array(
			'naam'         => $row[0],
			'slug'         => $row[1],
			'swatch'       => 0,
			'hex'          => $row[2],
			'omschrijving' => '',
		);
	}

	return $palette;
}

/**
 * Seed the catalogue once, without ever overwriting what Hor-Ex has entered.
 *
 * Runs on activation and, for installs that predate the seeder, on the first admin
 * request afterwards. Only keys that are missing or empty are filled, so removing a
 * product and saving does not bring it back.
 */
function horex_maybe_seed() {
	if ( get_option( HOREX_SEED_FLAG ) ) {
		return;
	}

	$stored = get_option( HOREX_OPTION, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	foreach ( horex_default_catalogue() as $key => $value ) {
		if ( empty( $stored[ $key ] ) ) {
			$stored[ $key ] = $value;
		}
	}

	update_option( HOREX_OPTION, $stored, true );
	update_option( HOREX_SEED_FLAG, HOREX_VERSION, true );
	update_option( HOREX_CATALOGUE_VERSION, HOREX_CATALOGUE_SCHEMA, false );
}

/**
 * Fill in catalogue fields added after an install was seeded.
 *
 * Rows are matched to the shipped catalogue by their key, and only fields the stored
 * row does not have at all are added. Nothing Hor-Ex has typed is ever overwritten —
 * a field left deliberately empty stays empty.
 */
function horex_migrate_catalogue() {
	if ( (int) get_option( HOREX_CATALOGUE_VERSION ) >= HOREX_CATALOGUE_SCHEMA ) {
		return;
	}

	$stored = get_option( HOREX_OPTION, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$defaults = horex_default_catalogue();

	foreach ( array( 'products', 'frame_colours', 'gaas' ) as $key ) {
		if ( empty( $stored[ $key ] ) || ! is_array( $stored[ $key ] ) ) {
			$stored[ $key ] = $defaults[ $key ];
			continue;
		}

		$stored[ $key ] = horex_fill_missing_fields( $stored[ $key ], $defaults[ $key ] );
	}

	// The placeholder palettes only appear where nothing has been entered yet.
	foreach ( array( 'stof_colours', 'doek_colours' ) as $key ) {
		if ( empty( $stored[ $key ] ) ) {
			$stored[ $key ] = $defaults[ $key ];
		}
	}

	update_option( HOREX_OPTION, $stored, true );
	update_option( HOREX_CATALOGUE_VERSION, HOREX_CATALOGUE_SCHEMA, false );
}

/**
 * Add absent fields to stored rows from the shipped catalogue, matching on key.
 *
 * @param array $rows     Stored rows.
 * @param array $defaults Shipped rows.
 * @return array
 */
function horex_fill_missing_fields( array $rows, array $defaults ) {
	$by_slug = array();

	foreach ( $defaults as $default ) {
		if ( ! empty( $default['slug'] ) ) {
			$by_slug[ $default['slug'] ] = $default;
		}
	}

	foreach ( $rows as $index => $row ) {
		$slug = ( is_array( $row ) && isset( $row['slug'] ) ) ? $row['slug'] : '';

		if ( ! isset( $by_slug[ $slug ] ) ) {
			continue;
		}

		foreach ( $by_slug[ $slug ] as $field => $value ) {
			if ( 'uitvoeringen' === $field ) {
				if ( ! empty( $row[ $field ] ) && is_array( $row[ $field ] ) && ! empty( $value ) ) {
					$rows[ $index ][ $field ] = horex_fill_missing_fields( $row[ $field ], $value );
				} elseif ( ! array_key_exists( $field, $row ) ) {
					$rows[ $index ][ $field ] = $value;
				}

				continue;
			}

			if ( ! array_key_exists( $field, $row ) ) {
				$rows[ $index ][ $field ] = $value;
			}
		}
	}

	return $rows;
}
