<?php
/**
 * The submission schema: what an incoming quote request holds.
 *
 * Same shape as the settings schema, and consumed by the same renderers and the same
 * sanitiser. A field added here appears on the request screen, survives saving and is
 * carried into the notification emails without touching four separate places.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Workflow states a request moves through.
 *
 * Keys are stored, so they stay stable; only the labels are translated.
 *
 * @return array
 */
function horex_submission_statuses() {
	return array(
		'nieuw'         => __( 'New', 'horex' ),
		'gecontacteerd' => __( 'Contacted', 'horex' ),
		'ingepland'     => __( 'Scheduled', 'horex' ),
		'afgerond'      => __( 'Completed', 'horex' ),
	);
}

/**
 * Which colour list a chosen colour came from.
 *
 * @return array
 */
function horex_colour_types() {
	return array(
		'frame' => __( 'Frame', 'horex' ),
		'stof'  => __( 'Fabric', 'horex' ),
		'doek'  => __( 'Canvas', 'horex' ),
	);
}

/**
 * Describe a submission, grouped into meta boxes.
 *
 * @return array
 */
function horex_submission_schema() {
	$schema = array(
		'klant'  => array(
			'label'   => __( 'Customer details', 'horex' ),
			'context' => 'normal',
			'fields'  => array(
				'naam'       => array(
					'type'  => 'text',
					'label' => __( 'Name', 'horex' ),
					'width' => 'half',
				),
				'email'      => array(
					'type'  => 'email',
					'label' => __( 'Email address', 'horex' ),
					'width' => 'half',
				),
				'telefoon'   => array(
					'type'  => 'tel',
					'label' => __( 'Phone number', 'horex' ),
					'width' => 'half',
				),
				'adres'      => array(
					'type'  => 'text',
					'label' => __( 'Address', 'horex' ),
					'width' => 'half',
				),
				'postcode'   => array(
					'type'  => 'text',
					'label' => __( 'Postcode', 'horex' ),
					'width' => 'half',
				),
				'plaats'     => array(
					'type'  => 'text',
					'label' => __( 'Town', 'horex' ),
					'width' => 'half',
				),
				'opmerkingen' => array(
					'type'  => 'textarea',
					'label' => __( 'Notes from the customer', 'horex' ),
					'rows'  => 4,
				),
			),
		),
		'items'  => array(
			'label'   => __( 'Products and measurements', 'horex' ),
			'context' => 'normal',
			'fields'  => array(
				'items' => array(
					'type'      => 'repeater',
					'label'     => __( 'Products and measurements', 'horex' ),
					'button'    => __( 'Add measurement', 'horex' ),
					'row_label' => 'ruimtenaam',
					'singular'  => __( 'Measurement', 'horex' ),
					// Never drop a row automatically: this is what the customer submitted.
					'keep_rows' => true,
					'fields'    => array(
						'ruimtenaam'      => array(
							'type'        => 'text',
							'label'       => __( 'Room', 'horex' ),
							'width'       => 'half',
							'description' => __( 'Where this one goes, for example "Woonkamer schuifpui".', 'horex' ),
						),
						'product'         => array(
							'type'  => 'text',
							'label' => __( 'Product', 'horex' ),
							'width' => 'half',
						),
						'uitvoering'      => array(
							'type'        => 'text',
							'label'       => __( 'Variant', 'horex' ),
							'width'       => 'half',
							'description' => __( 'Empty for curtains and sun shading.', 'horex' ),
						),
						'gaas'            => array(
							'type'        => 'text',
							'label'       => __( 'Mesh', 'horex' ),
							'width'       => 'half',
							'description' => __( 'Empty for curtains and sun shading.', 'horex' ),
						),
						'kleur'           => array(
							'type'  => 'text',
							'label' => __( 'Colour', 'horex' ),
							'width' => 'half',
						),
						'kleur_type'      => array(
							'type'    => 'select',
							'label'   => __( 'Colour type', 'horex' ),
							'width'   => 'half',
							'choices' => horex_colour_types(),
							'default' => 'frame',
						),
						'breedte_mm'      => array(
							'type'  => 'number',
							'label' => __( 'Width (mm)', 'horex' ),
							'width' => 'half',
							'min'   => 0,
							'max'   => 100000,
						),
						'hoogte_mm'       => array(
							'type'  => 'number',
							'label' => __( 'Height (mm)', 'horex' ),
							'width' => 'half',
							'min'   => 0,
							'max'   => 100000,
						),
						'buiten_standaard' => array(
							'type'        => 'checkbox',
							'label'       => __( 'Outside standard range', 'horex' ),
							'toggle'      => __( 'This measurement falls outside the standard range', 'horex' ),
							'description' => __( 'Set automatically from the measurement rules when the request is saved.', 'horex' ),
						),
						'foto'            => array(
							'type'        => 'image',
							'label'       => __( 'Photo', 'horex' ),
							'description' => __( 'Optional photo of the window or door.', 'horex' ),
						),
					),
				),
			),
		),
		'beheer' => array(
			'label'   => __( 'Administration', 'horex' ),
			'context' => 'side',
			'fields'  => array(
				'status'           => array(
					'type'    => 'select',
					'label'   => __( 'Status', 'horex' ),
					'choices' => horex_submission_statuses(),
					'default' => 'nieuw',
				),
				'referentienummer' => array(
					'type'        => 'text',
					'label'       => __( 'Reference', 'horex' ),
					'description' => __( 'Generated on saving when empty.', 'horex' ),
				),
			),
		),
	);

	/**
	 * Filter the submission schema.
	 *
	 * @param array $schema Meta boxes and their fields.
	 */
	return apply_filters( 'horex_submission_schema', $schema );
}

/**
 * Flatten the submission schema to a single field map.
 *
 * @return array
 */
function horex_submission_fields() {
	$fields = array();

	foreach ( horex_submission_schema() as $group ) {
		foreach ( $group['fields'] as $name => $field ) {
			$fields[ $name ] = $field;
		}
	}

	return $fields;
}
