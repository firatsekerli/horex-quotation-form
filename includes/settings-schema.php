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
 * Describe the settings page, tab by tab.
 *
 * Each tab has a label and a list of fields. Each field has at minimum a `type` and a
 * `label`; see horex_render_field() for the per-type options.
 *
 * @return array
 */
function horex_settings_schema() {
	$schema = array(
		'maten'    => array(
			'label'       => __( 'Maten', 'horex' ),
			'description' => __( 'Maten worden altijd in millimeters ingevoerd. Buiten dit bereik wordt de klant gewaarschuwd, maar nooit geblokkeerd — een veranda van 6,2 meter is een echte klant.', 'horex' ),
			'fields'      => array(
				'min_mm'             => array(
					'type'        => 'number',
					'label'       => __( 'Minimale maat (mm)', 'horex' ),
					'default'     => 300,
					'min'         => 0,
					'max'         => 100000,
					'description' => __( 'Onder deze maat krijgt de klant een opmerking te zien.', 'horex' ),
				),
				'max_mm'             => array(
					'type'        => 'number',
					'label'       => __( 'Maximale maat (mm)', 'horex' ),
					'default'     => 6000,
					'min'         => 0,
					'max'         => 100000,
					'description' => __( 'Boven deze maat krijgt de klant een opmerking te zien.', 'horex' ),
				),
				'waarschuwing_tekst' => array(
					'type'    => 'textarea',
					'label'   => __( 'Waarschuwingstekst', 'horex' ),
					'default' => __( 'Deze maat valt buiten ons standaardbereik. Geen probleem — we nemen contact met u op om de mogelijkheden door te nemen.', 'horex' ),
					'rows'    => 3,
				),
			),
		),
		'email'    => array(
			'label'       => __( 'E-mail', 'horex' ),
			'description' => __( 'Wie krijgt een nieuwe aanvraag binnen, en wat ziet de klant in de bevestigingsmail?', 'horex' ),
			'fields'      => array(
				'ontvangers'         => array(
					'type'        => 'email_list',
					'label'       => __( 'Ontvangers', 'horex' ),
					'rows'        => 4,
					'description' => __( 'Eén e-mailadres per regel. Leeg laten gebruikt het beheerdersadres van de site.', 'horex' ),
				),
				'onderwerp'          => array(
					'type'        => 'text',
					'label'       => __( 'Onderwerp interne mail', 'horex' ),
					'default'     => __( 'Nieuwe offerteaanvraag', 'horex' ),
					'description' => __( 'Het referentienummer wordt hier automatisch achter geplaatst.', 'horex' ),
				),
				'stuur_klant_kopie'  => array(
					'type'    => 'checkbox',
					'label'   => __( 'Kopie naar de klant', 'horex' ),
					'toggle'  => __( 'Stuur de klant een kopie van de aanvraag', 'horex' ),
					'default' => true,
				),
				'intro_tekst'        => array(
					'type'        => 'wysiwyg',
					'label'       => __( 'Introtekst klantbevestiging', 'horex' ),
					'default'     => '<p>' . __( 'Bedankt voor uw aanvraag. Hieronder vindt u een overzicht van wat u heeft doorgegeven. We nemen zo snel mogelijk contact met u op om een afspraak in te plannen en de maten ter plaatse op te nemen.', 'horex' ) . '</p>',
					'description' => __( 'Staat boven het overzicht in de mail aan de klant.', 'horex' ),
				),
				'referentie_prefix'  => array(
					'type'        => 'text',
					'label'       => __( 'Referentieprefix', 'horex' ),
					'default'     => 'HX-',
					'description' => __( 'Voorvoegsel van het referentienummer, bijvoorbeeld HX-2026-0031.', 'horex' ),
				),
			),
		),
		'branding' => array(
			'label'       => __( 'Branding', 'horex' ),
			'description' => __( 'Het logo met de witte woordmerk staat op de donkere header van de configurator.', 'horex' ),
			'fields'      => array(
				'logo_licht' => array(
					'type'        => 'image',
					'label'       => __( 'Logo (licht)', 'horex' ),
					'description' => __( 'Optioneel. Zonder logo toont de header de naam Hor-Ex als tekst.', 'horex' ),
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

	if ( array_key_exists( 'default', $fields[ $name ] ) ) {
		return $fields[ $name ]['default'];
	}

	return horex_empty_value_for_type( $fields[ $name ]['type'] );
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
			return array();
		default:
			return '';
	}
}
