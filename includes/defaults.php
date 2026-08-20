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
				'slug'         => 'plisse-horren',
				'type'         => 'horren',
				'foto'         => 0,
				'uitvoeringen' => array(
					array(
						'naam' => 'Plissé hordeur',
						'slug' => 'plisse-hordeur',
						'foto' => 0,
					),
					array(
						'naam' => 'Plissé horraam',
						'slug' => 'plisse-horraam',
						'foto' => 0,
					),
					array(
						'naam' => 'DUO — hor + gordijn',
						'slug' => 'duo-hor-gordijn',
						'foto' => 0,
					),
				),
			),
			array(
				'naam'         => 'Inzet horren',
				'slug'         => 'inzet-horren',
				'type'         => 'horren',
				'foto'         => 0,
				'uitvoeringen' => array(
					array(
						'naam' => 'Inzet horraam',
						'slug' => 'inzet-horraam',
						'foto' => 0,
					),
					array(
						'naam' => 'Inzet plissé horraam',
						'slug' => 'inzet-plisse-horraam',
						'foto' => 0,
					),
				),
			),
			array(
				'naam'         => 'Plissé gordijnen',
				'slug'         => 'plisse-gordijnen',
				'type'         => 'gordijn',
				'foto'         => 0,
				'uitvoeringen' => array(),
			),
			array(
				'naam'         => 'Wave gordijnen',
				'slug'         => 'wave-gordijnen',
				'type'         => 'gordijn',
				'foto'         => 0,
				'uitvoeringen' => array(),
			),
			array(
				'naam'         => 'Veranda zonwering',
				'slug'         => 'veranda-zonwering',
				'type'         => 'zonwering',
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
				'swatch' => 0,
			),
			array(
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
				'swatch' => 0,
			),
			array(
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
				'swatch' => 0,
			),
			array(
				'naam'   => 'Structuurlak',
				'slug'   => 'structuurlak',
				'hex'    => '#4A4A4A',
				'ral'    => '',
				'swatch' => 0,
			),
		),
		'gaas'             => array(
			array(
				'naam'         => 'Standaard gaas (extra sterk)',
				'slug'         => 'standaard-gaas',
				'omschrijving' => 'Extra sterk geweven gaas met goed doorzicht. Geschikt voor dagelijks gebruik.',
				'foto'         => 0,
			),
			array(
				'naam'         => 'Anti-pollen gaas',
				'slug'         => 'anti-pollen-gaas',
				'omschrijving' => 'Fijnmazig gaas dat pollen tegenhoudt. Prettig voor wie last heeft van hooikoorts.',
				'foto'         => 0,
			),
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
}
