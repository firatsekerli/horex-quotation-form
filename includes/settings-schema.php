<?php
/**
 * The catalogue schema: the single source of truth for the settings page.
 *
 * Every setting Hor-Ex can change is described once, here. The admin form, the save
 * handler, the sanitiser and the front-end payload are all generated from this array,
 * so they cannot drift apart — adding a field in one place adds it everywhere.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * The product types that drive the step engine.
 *
 * `horren` runs the full five steps; the other two skip uitvoering and gaas and swap
 * the colour list. Nothing else in the plugin decides step order.
 *
 * @return array
 */
function horex_product_types() {
	return array(
		'horren'    => __( 'Insect screens — with variant and mesh, frame colours', 'horex' ),
		'gordijn'   => __( 'Curtain — no variant or mesh, fabric colours', 'horex' ),
		'zonwering' => __( 'Sun shading — no variant or mesh, canvas colours', 'horex' ),
	);
}

/**
 * Describe the settings page, tab by tab.
 *
 * Each tab has a label and a list of fields. Each field has at minimum a `type` and a
 * `label`; see horex_render_field() for the per-type options.
 *
 * @return array
 */
function horex_settings_schema() {
	$schema = array(
		'producten'    => array(
			'label'       => __( 'Products', 'horex' ),
			'description' => __( 'The product cards in the configurator, in this order. The type decides which steps the customer goes through: insect screens get a variant and mesh step, curtains and sun shading do not.', 'horex' ),
			'fields'      => array(
				'products' => array(
					'type'      => 'repeater',
					'label'     => __( 'Products', 'horex' ),
					'button'    => __( 'Add product', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Product', 'horex' ),
					'fields'    => array(
						'naam'         => array(
							'type'  => 'text',
							'label' => __( 'Name', 'horex' ),
							'width' => 'half',
						),
						'slug'         => array(
							'type'        => 'slug',
							'label'       => __( 'Key', 'horex' ),
							'advanced'    => true,
							'description' => __( 'Stable key used in requests. Filled automatically from the name; changing it breaks the link with requests that already exist.', 'horex' ),
						),
						'type'         => array(
							'type'    => 'select',
							'label'   => __( 'Type', 'horex' ),
							'choices' => horex_product_types(),
							'default' => 'horren',
						),
						'foto'         => array(
							'type'        => 'image',
							'label'       => __( 'Photo', 'horex' ),
							'description' => __( 'The project photo on the product card. Without a photo the card shows a coloured tile.', 'horex' ),
						),
						'uitvoeringen' => array(
							'type'        => 'repeater',
							'label'       => __( 'Variants', 'horex' ),
							'button'      => __( 'Add variant', 'horex' ),
							'row_label'   => 'naam',
							'singular'    => __( 'Variant', 'horex' ),
							'description' => __( 'Only applies to insect screens. Curtains and sun shading skip this step.', 'horex' ),
							'fields'      => array(
								'naam' => array(
									'type'  => 'text',
									'label' => __( 'Name', 'horex' ),
									'width' => 'half',
								),
								'slug' => array(
									'type'     => 'slug',
									'label'    => __( 'Key', 'horex' ),
									'advanced' => true,
								),
								'foto' => array(
									'type'  => 'image',
									'label' => __( 'Photo', 'horex' ),
								),
							),
						),
					),
				),
			),
		),
		'framekleuren' => array(
			'label'       => __( 'Frame colours', 'horex' ),
			'description' => __( 'The colours for products of type insect screen. Use a hex colour for solid coatings; add an image for textured coatings, which is then shown as the swatch.', 'horex' ),
			'fields'      => array(
				'frame_colours' => array(
					'type'      => 'repeater',
					'label'     => __( 'Frame colours', 'horex' ),
					'button'    => __( 'Add colour', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Colour', 'horex' ),
					'fields'    => array(
						'naam'   => array(
							'type'  => 'text',
							'label' => __( 'Name', 'horex' ),
							'width' => 'half',
						),
						'slug'   => array(
							'type'     => 'slug',
							'label'    => __( 'Key', 'horex' ),
							'advanced' => true,
						),
						'hex'    => array(
							'type'  => 'color',
							'label' => __( 'Colour', 'horex' ),
							'width' => 'half',
						),
						'ral'    => array(
							'type'        => 'text',
							'label'       => __( 'RAL', 'horex' ),
							'width'       => 'half',
							'placeholder' => 'RAL 7039',
						),
						'swatch' => array(
							'type'        => 'image',
							'label'       => __( 'Swatch image', 'horex' ),
							'description' => __( 'Optional, for textured coatings. Takes precedence over the hex colour.', 'horex' ),
						),
					),
				),
			),
		),
		'gaas'         => array(
			'label'       => __( 'Mesh', 'horex' ),
			'description' => __( 'The mesh types in the final choice step for insect screens.', 'horex' ),
			'fields'      => array(
				'gaas' => array(
					'type'      => 'repeater',
					'label'     => __( 'Mesh types', 'horex' ),
					'button'    => __( 'Add mesh type', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Mesh type', 'horex' ),
					'fields'    => array(
						'naam'        => array(
							'type'  => 'text',
							'label' => __( 'Name', 'horex' ),
							'width' => 'half',
						),
						'slug'        => array(
							'type'     => 'slug',
							'label'    => __( 'Key', 'horex' ),
							'advanced' => true,
						),
						'omschrijving' => array(
							'type'        => 'textarea',
							'label'       => __( 'Description', 'horex' ),
							'rows'        => 2,
							'description' => __( 'One line of explanation under the name, for example what this type is suitable for.', 'horex' ),
						),
						'foto'        => array(
							'type'  => 'image',
							'label' => __( 'Photo', 'horex' ),
						),
					),
				),
			),
		),
		'stof'         => array(
			'label'       => __( 'Fabric colours', 'horex' ),
			'description' => __( 'The colours for products of type curtain. Fabrics are textured, so prefer a swatch image here.', 'horex' ),
			'fields'      => array(
				'stof_colours' => array(
					'type'      => 'repeater',
					'label'     => __( 'Fabric colours', 'horex' ),
					'button'    => __( 'Add fabric colour', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Fabric colour', 'horex' ),
					'fields'    => horex_fabric_colour_fields(),
				),
			),
		),
		'doek'         => array(
			'label'       => __( 'Canvas colours', 'horex' ),
			'description' => __( 'The colours for products of type sun shading.', 'horex' ),
			'fields'      => array(
				'doek_colours' => array(
					'type'      => 'repeater',
					'label'     => __( 'Canvas colours', 'horex' ),
					'button'    => __( 'Add canvas colour', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Canvas colour', 'horex' ),
					'fields'    => horex_fabric_colour_fields(),
				),
			),
		),
		'maten'        => array(
			'label'       => __( 'Measurements', 'horex' ),
			'description' => __( 'Measurements are always entered in millimetres. Outside this range the customer is warned, but never blocked — a 6.2 metre veranda is a real customer, not a typo.', 'horex' ),
			'fields'      => array(
				'min_mm'             => array(
					'type'        => 'number',
					'label'       => __( 'Minimum size (mm)', 'horex' ),
					'default'     => 300,
					'min'         => 0,
					'max'         => 100000,
					'description' => __( 'Below this size the customer sees a note.', 'horex' ),
				),
				'max_mm'             => array(
					'type'        => 'number',
					'label'       => __( 'Maximum size (mm)', 'horex' ),
					'default'     => 6000,
					'min'         => 0,
					'max'         => 100000,
					'description' => __( 'Above this size the customer sees a note.', 'horex' ),
				),
				'waarschuwing_tekst' => array(
					'type'    => 'textarea',
					'label'   => __( 'Warning text', 'horex' ),
					'default' => 'Deze maat valt buiten ons standaardbereik. Geen probleem — we nemen contact met u op om de mogelijkheden door te nemen.',
					'rows'    => 3,
				),
			),
		),
		'meethulp'     => array(
			'label'       => __( 'Measuring help', 'horex' ),
			'description' => __( 'The explanation behind "How do I measure this?" above the measurement fields. Wrong measurements are the biggest cost in made-to-measure work — this is the cheapest place to prevent them.', 'horex' ),
			'fields'      => array(
				'meethulp_horren'  => array(
					'type'        => 'group',
					'label'       => __( 'Insect screens', 'horex' ),
					'description' => __( 'Shown for products of type insect screen.', 'horex' ),
					'fields'      => horex_meethulp_fields(),
				),
				'meethulp_gordijn' => array(
					'type'        => 'group',
					'label'       => __( 'Curtains and sun shading', 'horex' ),
					'description' => __( 'Shown for products of type curtain and sun shading.', 'horex' ),
					'fields'      => horex_meethulp_fields(),
				),
			),
		),
		'email'        => array(
			'label'       => __( 'Email', 'horex' ),
			'description' => __( 'Who receives a new request, and what does the customer see in the confirmation email?', 'horex' ),
			'fields'      => array(
				'ontvangers'        => array(
					'type'        => 'email_list',
					'label'       => __( 'Recipients', 'horex' ),
					'rows'        => 4,
					'description' => __( 'One email address per line. Leave empty to use the site\'s admin address.', 'horex' ),
				),
				'onderwerp'         => array(
					'type'        => 'text',
					'label'       => __( 'Internal email subject', 'horex' ),
					'default'     => 'Nieuwe offerteaanvraag',
					'description' => __( 'The reference number is appended to this automatically.', 'horex' ),
				),
				'stuur_klant_kopie' => array(
					'type'    => 'checkbox',
					'label'   => __( 'Copy to the customer', 'horex' ),
					'toggle'  => __( 'Send the customer a copy of the request', 'horex' ),
					'default' => true,
				),
				'intro_tekst'       => array(
					'type'        => 'wysiwyg',
					'label'       => __( 'Intro text for the customer confirmation', 'horex' ),
					'default'     => '<p>' . 'Bedankt voor uw aanvraag. Hieronder vindt u een overzicht van wat u heeft doorgegeven. We nemen zo snel mogelijk contact met u op om een afspraak in te plannen en de maten ter plaatse op te nemen.' . '</p>',
					'description' => __( 'Appears above the summary in the email to the customer.', 'horex' ),
				),
				'referentie_prefix' => array(
					'type'        => 'text',
					'label'       => __( 'Reference prefix', 'horex' ),
					'default'     => 'HX-',
					'description' => __( 'Prefix of the reference number, for example HX-2026-0031.', 'horex' ),
				),
			),
		),
		'branding'     => array(
			'label'       => __( 'Branding', 'horex' ),
			'description' => __( 'The logo with the white wordmark sits on the configurator\'s dark header.', 'horex' ),
			'fields'      => array(
				'logo_licht' => array(
					'type'        => 'image',
					'label'       => __( 'Logo (light)', 'horex' ),
					'description' => __( 'Optional. Without a logo the header shows the name Hor-Ex as text.', 'horex' ),
				),
			),
		),
	);

	/**
	 * Filter the settings schema.
	 *
	 * @param array $schema Tabs and their fields.
	 */
	return apply_filters( 'horex_settings_schema', $schema );
}

/**
 * Sub-fields shared by the stof and doek colour repeaters.
 *
 * @return array
 */
function horex_fabric_colour_fields() {
	return array(
		'naam'         => array(
			'type'  => 'text',
			'label' => __( 'Name', 'horex' ),
			'width' => 'half',
		),
		'slug'         => array(
			'type'     => 'slug',
			'label'    => __( 'Key', 'horex' ),
			'advanced' => true,
		),
		'swatch'       => array(
			'type'        => 'image',
			'label'       => __( 'Swatch image', 'horex' ),
			'description' => __( 'A crop of the fabric. Without an image the hex colour is used.', 'horex' ),
		),
		'hex'          => array(
			'type'        => 'color',
			'label'       => __( 'Colour', 'horex' ),
			'description' => __( 'Fallback for when there is no swatch.', 'horex' ),
		),
		'omschrijving' => array(
			'type'  => 'textarea',
			'label' => __( 'Description', 'horex' ),
			'rows'  => 2,
		),
	);
}

/**
 * Sub-fields shared by the two meethulp groups.
 *
 * @return array
 */
function horex_meethulp_fields() {
	return array(
		'titel'     => array(
			'type'    => 'text',
			'label'   => __( 'Title', 'horex' ),
			'default' => 'Hoe meet ik dit op?',
		),
		'diagram'   => array(
			'type'        => 'image',
			'label'       => __( 'Diagram', 'horex' ),
			'description' => __( 'Drawing showing which measurement is meant.', 'horex' ),
		),
		'stappen'   => array(
			'type'      => 'repeater',
			'label'     => __( 'Steps', 'horex' ),
			'button'    => __( 'Add step', 'horex' ),
			'row_label' => 'tekst',
			'singular'  => __( 'Step', 'horex' ),
			'fields'    => array(
				'tekst' => array(
					'type'  => 'text',
					'label' => __( 'Step', 'horex' ),
					'width' => 'full',
				),
			),
		),
		'video_url' => array(
			'type'        => 'url',
			'label'       => __( 'Video', 'horex' ),
			'description' => __( 'Optional. A short clip of someone actually measuring works better than text.', 'horex' ),
		),
	);
}

/**
 * Flatten the schema to a single field map, keyed by field name.
 *
 * @return array
 */
function horex_settings_fields() {
	$fields = array();

	foreach ( horex_settings_schema() as $tab ) {
		foreach ( $tab['fields'] as $name => $field ) {
			$fields[ $name ] = $field;
		}
	}

	return $fields;
}

/**
 * Default value for a single setting, taken from the schema.
 *
 * @param string $name Field name.
 * @return mixed
 */
function horex_setting_default( $name ) {
	$fields = horex_settings_fields();

	if ( ! isset( $fields[ $name ] ) ) {
		return null;
	}

	return horex_field_default( $fields[ $name ] );
}

/**
 * The empty value appropriate to a field type.
 *
 * @param string $type Field type.
 * @return mixed
 */
function horex_empty_value_for_type( $type ) {
	switch ( $type ) {
		case 'checkbox':
			return false;
		case 'number':
		case 'image':
			return 0;
		case 'repeater':
		case 'email_list':
		case 'group':
			return array();
		default:
			return '';
	}
}
