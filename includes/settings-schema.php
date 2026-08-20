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
		'horren'    => __( 'Horren — met uitvoering en gaas, framekleuren', 'horex' ),
		'gordijn'   => __( 'Gordijn — geen uitvoering of gaas, stofkleuren', 'horex' ),
		'zonwering' => __( 'Zonwering — geen uitvoering of gaas, doekkleuren', 'horex' ),
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
			'label'       => __( 'Producten', 'horex' ),
			'description' => __( 'De productkaarten in de configurator, in deze volgorde. Het type bepaalt welke stappen de klant doorloopt: horren krijgen een uitvoering- en gaasstap, gordijnen en zonwering niet.', 'horex' ),
			'fields'      => array(
				'products' => array(
					'type'      => 'repeater',
					'label'     => __( 'Producten', 'horex' ),
					'button'    => __( 'Product toevoegen', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Product', 'horex' ),
					'fields'    => array(
						'naam'         => array(
							'type'  => 'text',
							'label' => __( 'Naam', 'horex' ),
							'width' => 'half',
						),
						'slug'         => array(
							'type'        => 'slug',
							'label'       => __( 'Slug', 'horex' ),
							'width'       => 'half',
							'description' => __( 'Vaste sleutel in aanvragen. Laat leeg om automatisch te vullen; wijzig niet meer zodra er aanvragen zijn.', 'horex' ),
						),
						'type'         => array(
							'type'    => 'select',
							'label'   => __( 'Type', 'horex' ),
							'choices' => horex_product_types(),
							'default' => 'horren',
						),
						'foto'         => array(
							'type'        => 'image',
							'label'       => __( 'Foto', 'horex' ),
							'description' => __( 'De projectfoto op de productkaart. Zonder foto toont de kaart een gekleurd vlak.', 'horex' ),
						),
						'uitvoeringen' => array(
							'type'        => 'repeater',
							'label'       => __( 'Uitvoeringen', 'horex' ),
							'button'      => __( 'Uitvoering toevoegen', 'horex' ),
							'row_label'   => 'naam',
							'singular'    => __( 'Uitvoering', 'horex' ),
							'description' => __( 'Alleen van toepassing op horren. Gordijnen en zonwering slaan deze stap over.', 'horex' ),
							'fields'      => array(
								'naam' => array(
									'type'  => 'text',
									'label' => __( 'Naam', 'horex' ),
									'width' => 'half',
								),
								'slug' => array(
									'type'  => 'slug',
									'label' => __( 'Slug', 'horex' ),
									'width' => 'half',
								),
								'foto' => array(
									'type'  => 'image',
									'label' => __( 'Foto', 'horex' ),
								),
							),
						),
					),
				),
			),
		),
		'framekleuren' => array(
			'label'       => __( 'Framekleuren', 'horex' ),
			'description' => __( 'De kleuren voor producten van het type horren. Gebruik een hexkleur voor egale lakken; kies daarnaast een afbeelding voor structuurlakken, die dan als swatch getoond wordt.', 'horex' ),
			'fields'      => array(
				'frame_colours' => array(
					'type'      => 'repeater',
					'label'     => __( 'Framekleuren', 'horex' ),
					'button'    => __( 'Kleur toevoegen', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Kleur', 'horex' ),
					'fields'    => array(
						'naam'   => array(
							'type'  => 'text',
							'label' => __( 'Naam', 'horex' ),
							'width' => 'half',
						),
						'slug'   => array(
							'type'  => 'slug',
							'label' => __( 'Slug', 'horex' ),
							'width' => 'half',
						),
						'hex'    => array(
							'type'  => 'color',
							'label' => __( 'Kleur', 'horex' ),
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
							'label'       => __( 'Swatch-afbeelding', 'horex' ),
							'description' => __( 'Optioneel, voor structuurlakken. Gaat voor op de hexkleur.', 'horex' ),
						),
					),
				),
			),
		),
		'gaas'         => array(
			'label'       => __( 'Gaas', 'horex' ),
			'description' => __( 'De gaassoorten in de laatste keuzestap van horren.', 'horex' ),
			'fields'      => array(
				'gaas' => array(
					'type'      => 'repeater',
					'label'     => __( 'Gaassoorten', 'horex' ),
					'button'    => __( 'Gaassoort toevoegen', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Gaassoort', 'horex' ),
					'fields'    => array(
						'naam'        => array(
							'type'  => 'text',
							'label' => __( 'Naam', 'horex' ),
							'width' => 'half',
						),
						'slug'        => array(
							'type'  => 'slug',
							'label' => __( 'Slug', 'horex' ),
							'width' => 'half',
						),
						'omschrijving' => array(
							'type'        => 'textarea',
							'label'       => __( 'Omschrijving', 'horex' ),
							'rows'        => 2,
							'description' => __( 'Eén regel uitleg onder de naam, bijvoorbeeld waarvoor deze soort geschikt is.', 'horex' ),
						),
						'foto'        => array(
							'type'  => 'image',
							'label' => __( 'Foto', 'horex' ),
						),
					),
				),
			),
		),
		'stof'         => array(
			'label'       => __( 'Stofkleuren', 'horex' ),
			'description' => __( 'De kleuren voor producten van het type gordijn. Stoffen zijn textuur, dus gebruik hier bij voorkeur een swatch-afbeelding.', 'horex' ),
			'fields'      => array(
				'stof_colours' => array(
					'type'      => 'repeater',
					'label'     => __( 'Stofkleuren', 'horex' ),
					'button'    => __( 'Stofkleur toevoegen', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Stofkleur', 'horex' ),
					'fields'    => horex_fabric_colour_fields(),
				),
			),
		),
		'doek'         => array(
			'label'       => __( 'Doekkleuren', 'horex' ),
			'description' => __( 'De kleuren voor producten van het type zonwering.', 'horex' ),
			'fields'      => array(
				'doek_colours' => array(
					'type'      => 'repeater',
					'label'     => __( 'Doekkleuren', 'horex' ),
					'button'    => __( 'Doekkleur toevoegen', 'horex' ),
					'row_label' => 'naam',
					'singular'  => __( 'Doekkleur', 'horex' ),
					'fields'    => horex_fabric_colour_fields(),
				),
			),
		),
		'maten'        => array(
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
		'meethulp'     => array(
			'label'       => __( 'Meethulp', 'horex' ),
			'description' => __( 'De uitleg achter "Hoe meet ik dit op?" boven de maatvelden. Verkeerde maten zijn de grootste kostenpost bij maatwerk — dit is de goedkoopste plek om ze te voorkomen.', 'horex' ),
			'fields'      => array(
				'meethulp_horren'  => array(
					'type'        => 'group',
					'label'       => __( 'Horren', 'horex' ),
					'description' => __( 'Getoond bij producten van het type horren.', 'horex' ),
					'fields'      => horex_meethulp_fields(),
				),
				'meethulp_gordijn' => array(
					'type'        => 'group',
					'label'       => __( 'Gordijnen en zonwering', 'horex' ),
					'description' => __( 'Getoond bij producten van het type gordijn en zonwering.', 'horex' ),
					'fields'      => horex_meethulp_fields(),
				),
			),
		),
		'email'        => array(
			'label'       => __( 'E-mail', 'horex' ),
			'description' => __( 'Wie krijgt een nieuwe aanvraag binnen, en wat ziet de klant in de bevestigingsmail?', 'horex' ),
			'fields'      => array(
				'ontvangers'        => array(
					'type'        => 'email_list',
					'label'       => __( 'Ontvangers', 'horex' ),
					'rows'        => 4,
					'description' => __( 'Eén e-mailadres per regel. Leeg laten gebruikt het beheerdersadres van de site.', 'horex' ),
				),
				'onderwerp'         => array(
					'type'        => 'text',
					'label'       => __( 'Onderwerp interne mail', 'horex' ),
					'default'     => __( 'Nieuwe offerteaanvraag', 'horex' ),
					'description' => __( 'Het referentienummer wordt hier automatisch achter geplaatst.', 'horex' ),
				),
				'stuur_klant_kopie' => array(
					'type'    => 'checkbox',
					'label'   => __( 'Kopie naar de klant', 'horex' ),
					'toggle'  => __( 'Stuur de klant een kopie van de aanvraag', 'horex' ),
					'default' => true,
				),
				'intro_tekst'       => array(
					'type'        => 'wysiwyg',
					'label'       => __( 'Introtekst klantbevestiging', 'horex' ),
					'default'     => '<p>' . __( 'Bedankt voor uw aanvraag. Hieronder vindt u een overzicht van wat u heeft doorgegeven. We nemen zo snel mogelijk contact met u op om een afspraak in te plannen en de maten ter plaatse op te nemen.', 'horex' ) . '</p>',
					'description' => __( 'Staat boven het overzicht in de mail aan de klant.', 'horex' ),
				),
				'referentie_prefix' => array(
					'type'        => 'text',
					'label'       => __( 'Referentieprefix', 'horex' ),
					'default'     => 'HX-',
					'description' => __( 'Voorvoegsel van het referentienummer, bijvoorbeeld HX-2026-0031.', 'horex' ),
				),
			),
		),
		'branding'     => array(
			'label'       => __( 'Branding', 'horex' ),
			'description' => __( 'Het logo met het witte woordmerk staat op de donkere header van de configurator.', 'horex' ),
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
 * Sub-fields shared by the stof and doek colour repeaters.
 *
 * @return array
 */
function horex_fabric_colour_fields() {
	return array(
		'naam'         => array(
			'type'  => 'text',
			'label' => __( 'Naam', 'horex' ),
			'width' => 'half',
		),
		'slug'         => array(
			'type'  => 'slug',
			'label' => __( 'Slug', 'horex' ),
			'width' => 'half',
		),
		'swatch'       => array(
			'type'        => 'image',
			'label'       => __( 'Swatch-afbeelding', 'horex' ),
			'description' => __( 'Een uitsnede van de stof. Zonder afbeelding wordt de hexkleur gebruikt.', 'horex' ),
		),
		'hex'          => array(
			'type'        => 'color',
			'label'       => __( 'Kleur', 'horex' ),
			'description' => __( 'Terugval wanneer er geen swatch is.', 'horex' ),
		),
		'omschrijving' => array(
			'type'  => 'textarea',
			'label' => __( 'Omschrijving', 'horex' ),
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
			'label'   => __( 'Titel', 'horex' ),
			'default' => __( 'Hoe meet ik dit op?', 'horex' ),
		),
		'diagram'   => array(
			'type'        => 'image',
			'label'       => __( 'Diagram', 'horex' ),
			'description' => __( 'Tekening die laat zien welke maat bedoeld wordt.', 'horex' ),
		),
		'stappen'   => array(
			'type'      => 'repeater',
			'label'     => __( 'Stappen', 'horex' ),
			'button'    => __( 'Stap toevoegen', 'horex' ),
			'row_label' => 'tekst',
			'singular'  => __( 'Stap', 'horex' ),
			'fields'    => array(
				'tekst' => array(
					'type'  => 'text',
					'label' => __( 'Stap', 'horex' ),
					'width' => 'full',
				),
			),
		),
		'video_url' => array(
			'type'        => 'url',
			'label'       => __( 'Video', 'horex' ),
			'description' => __( 'Optioneel. Een korte clip van iemand die daadwerkelijk opmeet werkt beter dan tekst.', 'horex' ),
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
