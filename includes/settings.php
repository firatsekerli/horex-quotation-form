<?php
/**
 * The "Hor-Ex Instellingen" settings page: storage, accessors and the save handler.
 *
 * Everything here is driven by horex_settings_schema(). Tabs are server-side, so each
 * save only touches the fields belonging to the tab that was submitted.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Name of the single option row holding every setting.
 */
const HOREX_OPTION = 'horex_settings';

/**
 * All stored settings, merged over the schema defaults.
 *
 * @return array
 */
function horex_get_settings() {
	$stored = get_option( HOREX_OPTION, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$settings = array();

	foreach ( horex_settings_fields() as $name => $field ) {
		$settings[ $name ] = array_key_exists( $name, $stored ) ? $stored[ $name ] : horex_setting_default( $name );
	}

	return $settings;
}

/**
 * A single setting, falling back to its schema default.
 *
 * Unlike ACF options, defaults resolve without the page ever having been saved.
 *
 * @param string $name     Field name.
 * @param mixed  $fallback Returned when the setting is unset and has no default.
 * @return mixed
 */
function horex_get_setting( $name, $fallback = null ) {
	$stored = get_option( HOREX_OPTION, array() );

	if ( is_array( $stored ) && array_key_exists( $name, $stored ) ) {
		$value = $stored[ $name ];

		if ( '' !== $value && array() !== $value ) {
			return $value;
		}

		// An explicitly cleared field stays cleared unless the caller needs a value.
		return null !== $fallback ? $fallback : $value;
	}

	$default = horex_setting_default( $name );

	return ( null === $default && null !== $fallback ) ? $fallback : $default;
}

/**
 * Persist a subset of settings, leaving every other key untouched.
 *
 * @param array $values Field name => sanitised value.
 */
function horex_update_settings( array $values ) {
	$stored = get_option( HOREX_OPTION, array() );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	update_option( HOREX_OPTION, array_merge( $stored, $values ), true );
}

/**
 * Add the settings page under the Hor-Ex menu.
 */
function horex_register_settings_page() {
	$hook = add_submenu_page(
		'edit.php?post_type=' . Horex::CPT,
		__( 'Hor-Ex Instellingen', 'horex' ),
		__( 'Instellingen', 'horex' ),
		'manage_options',
		Horex::OPTIONS_SLUG,
		'horex_render_settings_page'
	);

	if ( $hook ) {
		horex_settings_hook( $hook );
	}
}

/**
 * Remember the settings page hook suffix so assets load only there.
 *
 * @param string|null $set Hook suffix to store, or null to read.
 * @return string
 */
function horex_settings_hook( $set = null ) {
	static $hook = '';

	if ( null !== $set ) {
		$hook = (string) $set;
	}

	return $hook;
}

/**
 * Load the admin assets on the settings page only.
 *
 * @param string $hook_suffix Current admin page.
 */
function horex_maybe_enqueue_admin_assets( $hook_suffix ) {
	if ( $hook_suffix && $hook_suffix === horex_settings_hook() ) {
		horex_enqueue_admin_assets();
	}
}

/**
 * The tab currently being viewed, validated against the schema.
 *
 * @return string
 */
function horex_current_tab() {
	$schema = horex_settings_schema();
	$tabs   = array_keys( $schema );

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection.
	$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

	return in_array( $requested, $tabs, true ) ? $requested : reset( $tabs );
}

/**
 * Render the settings page.
 */
function horex_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'U heeft geen toegang tot deze pagina.', 'horex' ) );
	}

	$schema   = horex_settings_schema();
	$current  = horex_current_tab();
	$tab      = $schema[ $current ];
	$settings = horex_get_settings();
	$base_url = admin_url( 'edit.php?post_type=' . Horex::CPT . '&page=' . Horex::OPTIONS_SLUG );

	?>
	<div class="wrap horex-settings">
		<h1><?php esc_html_e( 'Hor-Ex Instellingen', 'horex' ); ?></h1>

		<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only flag. ?>
		<?php if ( isset( $_GET['horex-updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Instellingen opgeslagen.', 'horex' ); ?></p>
			</div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper horex-tabs">
			<?php foreach ( $schema as $slug => $definition ) : ?>
				<a
					href="<?php echo esc_url( add_query_arg( 'tab', $slug, $base_url ) ); ?>"
					class="nav-tab <?php echo $slug === $current ? 'nav-tab-active' : ''; ?>"
				><?php echo esc_html( $definition['label'] ); ?></a>
			<?php endforeach; ?>
		</nav>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="horex-settings-form">
			<input type="hidden" name="action" value="horex_save_settings" />
			<input type="hidden" name="horex_tab" value="<?php echo esc_attr( $current ); ?>" />
			<?php wp_nonce_field( 'horex_save_settings_' . $current, 'horex_nonce' ); ?>

			<?php if ( ! empty( $tab['description'] ) ) : ?>
				<p class="horex-tab-description"><?php echo esc_html( $tab['description'] ); ?></p>
			<?php endif; ?>

			<table class="form-table" role="presentation">
				<tbody>
				<?php foreach ( $tab['fields'] as $name => $field ) : ?>
					<tr>
						<th scope="row">
							<label for="<?php echo esc_attr( 'horex-field-' . $name ); ?>">
								<?php echo esc_html( $field['label'] ); ?>
							</label>
						</th>
						<td>
							<?php horex_render_field( $name, $field, $settings[ $name ] ); ?>
							<?php if ( ! empty( $field['description'] ) ) : ?>
								<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php submit_button( __( 'Opslaan', 'horex' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Handle the settings form submission.
 */
function horex_handle_settings_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'U heeft geen toegang tot deze pagina.', 'horex' ) );
	}

	$schema = horex_settings_schema();
	$tab    = isset( $_POST['horex_tab'] ) ? sanitize_key( wp_unslash( $_POST['horex_tab'] ) ) : '';

	if ( ! isset( $schema[ $tab ] ) ) {
		wp_die( esc_html__( 'Onbekend tabblad.', 'horex' ) );
	}

	check_admin_referer( 'horex_save_settings_' . $tab, 'horex_nonce' );

	// Raw input is passed straight to the sanitiser, which reads the schema and keeps
	// only the fields belonging to this tab. Anything else is dropped.
	$raw = isset( $_POST[ HOREX_OPTION ] ) && is_array( $_POST[ HOREX_OPTION ] )
		? wp_unslash( $_POST[ HOREX_OPTION ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitised per field below.
		: array();

	horex_update_settings( horex_sanitize_settings( $raw, $schema[ $tab ]['fields'] ) );

	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type'     => Horex::CPT,
				'page'          => Horex::OPTIONS_SLUG,
				'tab'           => $tab,
				'horex-updated' => '1',
			),
			admin_url( 'edit.php' )
		)
	);
	exit;
}

/**
 * Sanitise submitted values against a set of schema fields.
 *
 * Fields absent from $fields are dropped: the schema is the allow-list.
 *
 * @param array $input  Raw submitted values.
 * @param array $fields Schema fields to accept.
 * @return array
 */
function horex_sanitize_settings( array $input, array $fields ) {
	$clean = array();

	foreach ( $fields as $name => $field ) {
		$value          = isset( $input[ $name ] ) ? $input[ $name ] : null;
		$clean[ $name ] = horex_sanitize_value( $value, $field );
	}

	return $clean;
}

/**
 * Sanitise one value according to its schema field type.
 *
 * @param mixed $value Raw value.
 * @param array $field Schema field definition.
 * @return mixed
 */
function horex_sanitize_value( $value, array $field ) {
	$type = isset( $field['type'] ) ? $field['type'] : 'text';

	switch ( $type ) {
		case 'checkbox':
			return ! empty( $value );

		case 'number':
			$number = is_scalar( $value ) ? absint( $value ) : 0;

			if ( isset( $field['min'] ) ) {
				$number = max( (int) $field['min'], $number );
			}

			if ( isset( $field['max'] ) ) {
				$number = min( (int) $field['max'], $number );
			}

			return $number;

		case 'image':
			return is_scalar( $value ) ? absint( $value ) : 0;

		case 'url':
			return is_scalar( $value ) ? esc_url_raw( trim( (string) $value ) ) : '';

		case 'color':
			$color = is_scalar( $value ) ? sanitize_hex_color( trim( (string) $value ) ) : '';

			return $color ? $color : '';

		case 'select':
			$choices = isset( $field['choices'] ) ? array_keys( $field['choices'] ) : array();
			$choice  = is_scalar( $value ) ? sanitize_key( $value ) : '';

			return in_array( $choice, $choices, true ) ? $choice : (string) horex_field_default( $field );

		case 'textarea':
			return is_scalar( $value ) ? sanitize_textarea_field( (string) $value ) : '';

		case 'wysiwyg':
			return is_scalar( $value ) ? wp_kses_post( (string) $value ) : '';

		case 'email_list':
			if ( ! is_scalar( $value ) ) {
				return array();
			}

			$emails = preg_split( '/[\r\n,;]+/', (string) $value );
			$emails = array_map( 'sanitize_email', array_map( 'trim', (array) $emails ) );

			return array_values( array_unique( array_filter( $emails, 'is_email' ) ) );

		case 'text':
		default:
			return is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
	}
}

/**
 * Default value declared on a schema field.
 *
 * @param array $field Schema field definition.
 * @return mixed
 */
function horex_field_default( array $field ) {
	if ( array_key_exists( 'default', $field ) ) {
		return $field['default'];
	}

	return horex_empty_value_for_type( isset( $field['type'] ) ? $field['type'] : 'text' );
}

/**
 * Enqueue the admin stylesheet and scripts for the settings page.
 */
function horex_enqueue_admin_assets() {
	wp_enqueue_media();

	wp_enqueue_style(
		'horex-admin',
		HOREX_URL . 'assets/css/admin.css',
		array(),
		HOREX_VERSION
	);

	wp_enqueue_script(
		'horex-admin',
		HOREX_URL . 'assets/js/admin.js',
		array( 'jquery' ),
		HOREX_VERSION,
		true
	);

	wp_localize_script(
		'horex-admin',
		'horexAdmin',
		array(
			'chooseImage' => __( 'Afbeelding kiezen', 'horex' ),
			'useImage'    => __( 'Deze afbeelding gebruiken', 'horex' ),
		)
	);
}
