<?php
/**
 * The front-end configurator: shortcode, assets and the catalogue handed to JS.
 *
 * The customer-facing interface is Dutch by design, not by locale. Every string the
 * customer reads is a Dutch literal here rather than a translatable one, so a site
 * running in another language still shows the configurator in Dutch.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode tag.
 */
const HOREX_SHORTCODE = 'horex_offerte';

/**
 * Register the shortcode and its assets.
 */
function horex_register_shortcode() {
	add_shortcode( HOREX_SHORTCODE, 'horex_render_shortcode' );
	add_action( 'wp_enqueue_scripts', 'horex_register_frontend_assets' );
}

/**
 * Register — but do not enqueue — the front-end assets.
 *
 * They load only on a page that actually contains the shortcode, so a site built in a
 * page builder does not carry them everywhere.
 */
function horex_register_frontend_assets() {
	if ( horex_get_setting( 'google_fonts' ) ) {
		wp_register_style(
			'horex-fonts',
			'https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Playfair+Display:wght@600;700&display=swap',
			array(),
			null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Google serves its own versioning.
		);
	}

	wp_register_style(
		'horex-offerte',
		HOREX_URL . 'assets/css/offerte.css',
		horex_get_setting( 'google_fonts' ) ? array( 'horex-fonts' ) : array(),
		horex_asset_version( 'assets/css/offerte.css' )
	);

	wp_register_script(
		'horex-offerte',
		HOREX_URL . 'assets/js/offerte.js',
		array(),
		horex_asset_version( 'assets/js/offerte.js' ),
		true
	);
}

/**
 * Render the configurator.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function horex_render_shortcode( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			// The page builder supplies the site header, so the configurator's own bar
			// can be pinned below it or left to scroll away.
			'sticky' => 'ja',
			'offset' => '0',
		),
		is_array( $atts ) ? $atts : array(),
		HOREX_SHORTCODE
	);

	wp_enqueue_style( 'horex-offerte' );
	wp_enqueue_script( 'horex-offerte' );

	wp_localize_script( 'horex-offerte', 'horexConfig', horex_frontend_config() );

	$sticky = in_array( strtolower( $atts['sticky'] ), array( 'ja', 'yes', 'true', '1' ), true );
	$offset = absint( $atts['offset'] );

	ob_start();
	require HOREX_DIR . 'templates/offerte.php';

	return (string) ob_get_clean();
}

/**
 * The catalogue and rules the front-end runs on.
 *
 * @return array
 */
function horex_frontend_config() {
	return array(
		'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
		'action'    => 'horex_submit',
		'nonce'     => wp_create_nonce( 'horex_submit' ),
		'products'  => horex_frontend_products(),
		'kleuren'   => array(
			'frame' => horex_frontend_colours( 'frame_colours' ),
			'stof'  => horex_frontend_colours( 'stof_colours' ),
			'doek'  => horex_frontend_colours( 'doek_colours' ),
		),
		'gaas'      => horex_frontend_gaas(),
		'maten'     => array(
			'min'          => (int) horex_get_setting( 'min_mm' ),
			'max'          => (int) horex_get_setting( 'max_mm' ),
			'waarschuwing' => (string) horex_get_setting( 'waarschuwing_tekst' ),
		),
		'meethulp'  => array(
			'horren'  => horex_frontend_meethulp( 'meethulp_horren', 'horren' ),
			'gordijn' => horex_frontend_meethulp( 'meethulp_gordijn', 'gordijn' ),
		),
		'tekeningen' => horex_drawings(),
	);
}

/**
 * Products, with the step engine's inputs resolved server-side.
 *
 * @return array
 */
function horex_frontend_products() {
	$products = (array) horex_get_setting( 'products' );
	$out      = array();

	$colours = array(
		'horren'    => 'frame',
		'gordijn'   => 'stof',
		'zonwering' => 'doek',
	);

	foreach ( $products as $product ) {
		if ( empty( $product['naam'] ) || empty( $product['slug'] ) ) {
			continue;
		}

		$type        = isset( $product['type'] ) ? $product['type'] : 'horren';
		$kleur_type  = isset( $colours[ $type ] ) ? $colours[ $type ] : 'frame';
		$variants    = array();

		// Only insect screens carry variants and mesh, and only when the catalogue
		// actually holds some — an empty list must not become a dead-end step.
		if ( 'horren' === $type ) {
			foreach ( (array) $product['uitvoeringen'] as $variant ) {
				if ( empty( $variant['naam'] ) ) {
					continue;
				}

				$variants[] = array(
					'slug'         => isset( $variant['slug'] ) ? $variant['slug'] : sanitize_title( $variant['naam'] ),
					'naam'         => $variant['naam'],
					'omschrijving' => isset( $variant['omschrijving'] ) ? $variant['omschrijving'] : '',
					'foto'         => horex_attachment_url( isset( $variant['foto'] ) ? $variant['foto'] : 0 ),
				);
			}
		}

		$out[] = array(
			'slug'        => $product['slug'],
			'naam'        => $product['naam'],
			'kort'        => isset( $product['kort'] ) ? $product['kort'] : '',
			'type'        => $type,
			'vulling'     => isset( $product['vulling'] ) ? $product['vulling'] : 'gaas',
			'illustratie' => isset( $product['illustratie'] ) ? $product['illustratie'] : '',
			'foto'        => horex_attachment_url( isset( $product['foto'] ) ? $product['foto'] : 0, 'medium_large' ),
			'kleurType'   => $kleur_type,
			'kleurVraag'  => horex_colour_question( $kleur_type ),
			'uitvoeringen' => $variants,
			'meethulp'    => 'horren' === $type ? 'horren' : 'gordijn',
		);
	}

	return $out;
}

/**
 * The heading of the colour step, which changes with the product type.
 *
 * @param string $kleur_type One of frame, stof, doek.
 * @return string
 */
function horex_colour_question( $kleur_type ) {
	switch ( $kleur_type ) {
		case 'stof':
			return 'Welke stofkleur?';
		case 'doek':
			return 'Welke doekkleur?';
		default:
			return 'Welke kleur frame?';
	}
}

/**
 * One colour list, ready for the swatch grid.
 *
 * @param string $key Settings field name.
 * @return array
 */
function horex_frontend_colours( $key ) {
	$out = array();

	foreach ( (array) horex_get_setting( $key ) as $colour ) {
		if ( empty( $colour['naam'] ) ) {
			continue;
		}

		$out[] = array(
			'slug'         => isset( $colour['slug'] ) ? $colour['slug'] : sanitize_title( $colour['naam'] ),
			'naam'         => $colour['naam'],
			'hex'          => isset( $colour['hex'] ) && $colour['hex'] ? $colour['hex'] : '#CCCCCC',
			'swatch'       => horex_attachment_url( isset( $colour['swatch'] ) ? $colour['swatch'] : 0 ),
			'textuur'      => ! empty( $colour['textuur'] ),
			'omschrijving' => isset( $colour['omschrijving'] ) ? $colour['omschrijving'] : '',
		);
	}

	return $out;
}

/**
 * The mesh types.
 *
 * @return array
 */
function horex_frontend_gaas() {
	$out = array();

	foreach ( (array) horex_get_setting( 'gaas' ) as $gaas ) {
		if ( empty( $gaas['naam'] ) ) {
			continue;
		}

		$out[] = array(
			'slug'         => isset( $gaas['slug'] ) ? $gaas['slug'] : sanitize_title( $gaas['naam'] ),
			'naam'         => $gaas['naam'],
			'omschrijving' => isset( $gaas['omschrijving'] ) ? $gaas['omschrijving'] : '',
			'fijnmazig'    => ! empty( $gaas['fijnmazig'] ),
			'foto'         => horex_attachment_url( isset( $gaas['foto'] ) ? $gaas['foto'] : 0 ),
		);
	}

	return $out;
}

/**
 * One measuring-help group, falling back to the shipped diagram.
 *
 * @param string $key      Settings field name.
 * @param string $drawing  Diagram key.
 * @return array
 */
function horex_frontend_meethulp( $key, $drawing ) {
	$group   = (array) horex_get_setting( $key );
	$diagram = horex_attachment_url( isset( $group['diagram'] ) ? $group['diagram'] : 0, 'large' );
	$stappen = array();

	foreach ( (array) ( isset( $group['stappen'] ) ? $group['stappen'] : array() ) as $step ) {
		if ( ! empty( $step['tekst'] ) ) {
			$stappen[] = $step['tekst'];
		}
	}

	return array(
		'titel'    => ! empty( $group['titel'] ) ? $group['titel'] : 'Hoe meet ik dit op?',
		'diagram'  => $diagram,
		'tekening' => $diagram ? '' : horex_diagram( $drawing ),
		'stappen'  => $stappen,
		'video'    => ! empty( $group['video_url'] ) ? $group['video_url'] : '',
	);
}

/**
 * URL for an attachment, or an empty string.
 *
 * @param int    $id   Attachment ID.
 * @param string $size Image size.
 * @return string
 */
function horex_attachment_url( $id, $size = 'medium' ) {
	$id = (int) $id;

	if ( ! $id ) {
		return '';
	}

	$url = wp_get_attachment_image_url( $id, $size );

	return $url ? $url : '';
}
