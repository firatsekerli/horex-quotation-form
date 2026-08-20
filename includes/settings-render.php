<?php
/**
 * Field renderers for the settings and request screens.
 *
 * Repeaters render as a list of cards plus an inert <template> holding a blank row.
 * The template uses __PREFIX{depth}__ / __INDEX{depth}__ placeholders that the admin
 * script substitutes when a row is added — depth-tagged so that a nested repeater's
 * own placeholders survive the parent's substitution.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Field types that manage their own full-width layout.
 *
 * @return array
 */
function horex_block_field_types() {
	return array( 'repeater', 'group' );
}

/**
 * How a repeater should present its rows.
 *
 * A list of single text lines wants to look like a list, not like a stack of cards;
 * a row with several fields is better collapsed until someone needs it.
 *
 * @param array $field Schema field definition.
 * @return string One of simple, collapsible, plain.
 */
function horex_repeater_mode( array $field ) {
	$visible = array();

	foreach ( $field['fields'] as $name => $sub ) {
		if ( empty( $sub['advanced'] ) ) {
			$visible[ $name ] = $sub;
		}
	}

	if ( 1 === count( $visible ) ) {
		$only = reset( $visible );

		if ( in_array( $only['type'], array( 'text', 'textarea' ), true ) ) {
			return 'simple';
		}
	}

	return count( $visible ) > 2 ? 'collapsible' : 'plain';
}

/**
 * Render one field control.
 *
 * @param string      $name       Field name, used to build the element id.
 * @param array       $field      Schema field definition.
 * @param mixed       $value      Current value.
 * @param string|null $input_name Explicit input name; defaults to the option array key.
 * @param string|null $id         Explicit element id; pass '' to omit it.
 * @param int         $depth      Nesting depth, used by repeaters.
 */
function horex_render_field( $name, array $field, $value, $input_name = null, $id = null, $depth = 0 ) {
	$type       = isset( $field['type'] ) ? $field['type'] : 'text';
	$input_name = null === $input_name ? HOREX_OPTION . '[' . $name . ']' : $input_name;
	$id         = null === $id ? 'horex-field-' . $name : $id;
	$id_attr    = '' === $id ? '' : ' id="' . esc_attr( $id ) . '"';

	switch ( $type ) {
		case 'repeater':
			horex_render_repeater( $input_name, $field, (array) $value, $depth );
			break;

		case 'group':
			horex_render_group( $input_name, $field, (array) $value, $depth );
			break;

		case 'number':
			printf(
				'<input type="number"%1$s name="%2$s" value="%3$s" class="small-text"%4$s%5$s step="1" inputmode="numeric" />',
				$id_attr, // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.
				esc_attr( $input_name ),
				esc_attr( (string) $value ),
				isset( $field['min'] ) ? ' min="' . esc_attr( (string) $field['min'] ) . '"' : '',
				isset( $field['max'] ) ? ' max="' . esc_attr( (string) $field['max'] ) . '"' : ''
			);
			break;

		case 'checkbox':
			printf(
				'<label class="horex-checkbox"><input type="checkbox"%1$s name="%2$s" value="1"%3$s /> <span>%4$s</span></label>',
				$id_attr, // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.
				esc_attr( $input_name ),
				checked( (bool) $value, true, false ),
				esc_html( isset( $field['toggle'] ) ? $field['toggle'] : $field['label'] )
			);
			break;

		case 'select':
			printf( '<select%1$s name="%2$s">', $id_attr, esc_attr( $input_name ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.

			foreach ( (array) $field['choices'] as $choice => $label ) {
				printf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( $choice ),
					selected( $value, $choice, false ),
					esc_html( $label )
				);
			}

			echo '</select>';
			break;

		case 'textarea':
			printf(
				'<textarea%1$s name="%2$s" rows="%3$d" class="large-text">%4$s</textarea>',
				$id_attr, // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.
				esc_attr( $input_name ),
				(int) ( isset( $field['rows'] ) ? $field['rows'] : 3 ),
				esc_textarea( (string) $value )
			);
			break;

		case 'email_list':
			printf(
				'<textarea%1$s name="%2$s" rows="%3$d" class="large-text code">%4$s</textarea>',
				$id_attr, // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.
				esc_attr( $input_name ),
				(int) ( isset( $field['rows'] ) ? $field['rows'] : 4 ),
				esc_textarea( implode( "\n", (array) $value ) )
			);
			break;

		case 'email':
		case 'tel':
			printf(
				'<input type="%1$s"%2$s name="%3$s" value="%4$s" class="regular-text" />',
				esc_attr( $type ),
				$id_attr, // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.
				esc_attr( $input_name ),
				esc_attr( (string) $value )
			);
			break;

		case 'url':
			printf(
				'<input type="url"%1$s name="%2$s" value="%3$s" class="regular-text" placeholder="https://" />',
				$id_attr, // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.
				esc_attr( $input_name ),
				esc_url( (string) $value )
			);
			break;

		case 'color':
			horex_render_color_field( $id, $input_name, (string) $value );
			break;

		case 'image':
			horex_render_image_field( $id, $input_name, (int) $value );
			break;

		case 'wysiwyg':
			wp_editor(
				(string) $value,
				str_replace( '-', '_', $id ),
				array(
					'textarea_name' => $input_name,
					'textarea_rows' => (int) ( isset( $field['rows'] ) ? $field['rows'] : 8 ),
					'media_buttons' => false,
					'teeny'         => true,
				)
			);
			break;

		case 'slug':
			printf(
				'<input type="text"%1$s name="%2$s" value="%3$s" class="regular-text code horex-slug" data-horex-slug />',
				$id_attr, // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.
				esc_attr( $input_name ),
				esc_attr( (string) $value )
			);
			break;

		case 'text':
		default:
			printf(
				'<input type="text"%1$s name="%2$s" value="%3$s" class="regular-text"%4$s />',
				$id_attr, // phpcs:ignore WordPress.Security.EscapeOutput -- Escaped above.
				esc_attr( $input_name ),
				esc_attr( (string) $value ),
				isset( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : ''
			);
			break;
	}
}

/**
 * Render a labelled field cell for the grid.
 *
 * @param string $name       Field name.
 * @param array  $field      Schema field definition.
 * @param mixed  $value      Current value.
 * @param string $input_name Input name.
 * @param int    $depth      Nesting depth.
 * @param string $extra      Extra attributes for the wrapper.
 */
function horex_render_field_cell( $name, array $field, $value, $input_name, $depth = 0, $extra = '' ) {
	$is_block = in_array( $field['type'], horex_block_field_types(), true );
	$width    = isset( $field['width'] ) ? $field['width'] : 'full';
	$class    = 'horex-field horex-field--' . ( $is_block ? 'block' : $width );

	printf( '<div class="%1$s"%2$s>', esc_attr( $class ), $extra ); // phpcs:ignore WordPress.Security.EscapeOutput -- Fixed attribute strings.

	if ( 'checkbox' !== $field['type'] ) {
		printf( '<span class="horex-field__label">%s</span>', esc_html( $field['label'] ) );
	}

	horex_render_field( $name, $field, $value, $input_name, '', $depth );

	if ( ! empty( $field['description'] ) ) {
		printf( '<p class="description">%s</p>', esc_html( $field['description'] ) );
	}

	echo '</div>';
}

/**
 * Render a repeater: existing rows, an add button and a blank-row template.
 *
 * @param string $input_name Base input name for the repeater.
 * @param array  $field      Schema field definition.
 * @param array  $rows       Current rows.
 * @param int    $depth      Nesting depth.
 */
function horex_render_repeater( $input_name, array $field, array $rows, $depth = 0 ) {
	$rows = array_values( $rows );
	$mode = horex_repeater_mode( $field );

	?>
	<div
		class="horex-repeater horex-repeater--<?php echo esc_attr( $mode ); ?>"
		data-repeater
		data-prefix="<?php echo esc_attr( $input_name ); ?>"
		data-depth="<?php echo esc_attr( (string) $depth ); ?>"
	>
		<div class="horex-repeater__rows">
			<?php foreach ( $rows as $index => $row ) : ?>
				<?php horex_render_repeater_row( $input_name, $field, (array) $row, (string) $index, $depth, $mode, true ); ?>
			<?php endforeach; ?>
		</div>

		<p class="horex-repeater__empty"<?php echo $rows ? ' hidden' : ''; ?>>
			<?php esc_html_e( 'Nothing added yet.', 'horex' ); ?>
		</p>

		<p class="horex-repeater__footer">
			<button type="button" class="button horex-repeater__add">
				<span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
				<?php echo esc_html( isset( $field['button'] ) ? $field['button'] : __( 'Add row', 'horex' ) ); ?>
			</button>
		</p>

		<template class="horex-repeater__template">
			<?php horex_render_repeater_row( '__PREFIX' . $depth . '__', $field, array(), '__INDEX' . $depth . '__', $depth, $mode, false ); ?>
		</template>
	</div>
	<?php
}

/**
 * Render a single repeater row.
 *
 * @param string $prefix    Base input name for the repeater.
 * @param array  $field     Schema field definition.
 * @param array  $values    Current row values.
 * @param string $index     Row index, or a placeholder token in the template.
 * @param int    $depth     Nesting depth.
 * @param string $mode      Presentation mode.
 * @param bool   $collapsed Start collapsed. New rows always open.
 */
function horex_render_repeater_row( $prefix, array $field, array $values, $index, $depth, $mode, $collapsed ) {
	$label_key = isset( $field['row_label'] ) ? $field['row_label'] : '';
	$singular  = isset( $field['singular'] ) ? $field['singular'] : $field['label'];
	$title     = ( $label_key && ! empty( $values[ $label_key ] ) ) ? $values[ $label_key ] : '';

	$classes = array( 'horex-row' );

	if ( 'collapsible' === $mode && $collapsed ) {
		$classes[] = 'is-collapsed';
	}

	if ( 'simple' === $mode ) {
		horex_render_simple_row( $prefix, $field, $values, $index, $depth );

		return;
	}

	?>
	<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-repeater-row data-index="<?php echo esc_attr( $index ); ?>">
		<div class="horex-row__header">
			<?php if ( 'collapsible' === $mode ) : ?>
				<button
					type="button"
					class="horex-row__toggle"
					aria-expanded="<?php echo $collapsed ? 'false' : 'true'; ?>"
					aria-label="<?php esc_attr_e( 'Show or hide this row', 'horex' ); ?>"
				><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
			<?php endif; ?>

			<?php horex_render_row_preview( $field, $values ); ?>

			<span class="horex-row__index" aria-hidden="true"></span>

			<span class="horex-row__title" data-row-title data-placeholder="<?php echo esc_attr( $singular ); ?>">
				<?php echo esc_html( $title ? $title : $singular ); ?>
			</span>

			<?php if ( isset( $field['fields']['slug'] ) ) : ?>
				<code class="horex-row__key" data-row-key><?php echo esc_html( isset( $values['slug'] ) ? $values['slug'] : '' ); ?></code>
			<?php endif; ?>

			<span class="horex-row__actions">
				<button type="button" class="horex-icon-button horex-repeater__move" data-move="up" aria-label="<?php esc_attr_e( 'Move up', 'horex' ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
				<button type="button" class="horex-icon-button horex-repeater__move" data-move="down" aria-label="<?php esc_attr_e( 'Move down', 'horex' ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
				<button type="button" class="horex-icon-button horex-icon-button--danger horex-repeater__remove" aria-label="<?php esc_attr_e( 'Remove', 'horex' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
			</span>
		</div>

		<div class="horex-row__body">
			<div class="horex-grid">
				<?php horex_render_row_fields( $prefix, $field, $values, $index, $depth, false ); ?>
			</div>

			<?php if ( horex_has_advanced_fields( $field ) ) : ?>
				<details class="horex-advanced">
					<summary><?php esc_html_e( 'Advanced', 'horex' ); ?></summary>
					<div class="horex-grid">
						<?php horex_render_row_fields( $prefix, $field, $values, $index, $depth, true ); ?>
					</div>
				</details>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Render a compact row for repeaters that hold a single line of text.
 *
 * @param string $prefix Base input name.
 * @param array  $field  Schema field definition.
 * @param array  $values Current row values.
 * @param string $index  Row index.
 * @param int    $depth  Nesting depth.
 */
function horex_render_simple_row( $prefix, array $field, array $values, $index, $depth ) {
	$names = array_keys( $field['fields'] );
	$name  = reset( $names );
	$sub   = $field['fields'][ $name ];
	$value = array_key_exists( $name, $values ) ? $values[ $name ] : horex_field_default( $sub );

	?>
	<div class="horex-row horex-row--simple" data-repeater-row data-index="<?php echo esc_attr( $index ); ?>">
		<span class="horex-row__index" aria-hidden="true"></span>

		<?php
		horex_render_field(
			$name,
			$sub,
			$value,
			$prefix . '[' . $index . '][' . $name . ']',
			'',
			$depth + 1
		);
		?>

		<span class="horex-row__actions">
			<button type="button" class="horex-icon-button horex-repeater__move" data-move="up" aria-label="<?php esc_attr_e( 'Move up', 'horex' ); ?>"><span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span></button>
			<button type="button" class="horex-icon-button horex-repeater__move" data-move="down" aria-label="<?php esc_attr_e( 'Move down', 'horex' ); ?>"><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>
			<button type="button" class="horex-icon-button horex-icon-button--danger horex-repeater__remove" aria-label="<?php esc_attr_e( 'Remove', 'horex' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></button>
		</span>
	</div>
	<?php
}

/**
 * Render the fields of a row, either the everyday ones or the advanced ones.
 *
 * @param string $prefix   Base input name.
 * @param array  $field    Schema field definition.
 * @param array  $values   Current row values.
 * @param string $index    Row index.
 * @param int    $depth    Nesting depth.
 * @param bool   $advanced Render the advanced fields instead of the everyday ones.
 */
function horex_render_row_fields( $prefix, array $field, array $values, $index, $depth, $advanced ) {
	$label_key = isset( $field['row_label'] ) ? $field['row_label'] : '';

	foreach ( $field['fields'] as $name => $sub ) {
		if ( ! empty( $sub['advanced'] ) !== $advanced ) {
			continue;
		}

		horex_render_field_cell(
			$name,
			$sub,
			array_key_exists( $name, $values ) ? $values[ $name ] : horex_field_default( $sub ),
			$prefix . '[' . $index . '][' . $name . ']',
			$depth + 1,
			$name === $label_key ? ' data-row-label-field' : ''
		);
	}
}

/**
 * Does this repeater have any fields tucked behind "Advanced"?
 *
 * @param array $field Schema field definition.
 * @return bool
 */
function horex_has_advanced_fields( array $field ) {
	foreach ( $field['fields'] as $sub ) {
		if ( ! empty( $sub['advanced'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Render the little swatch or thumbnail in a row header.
 *
 * @param array $field  Schema field definition.
 * @param array $values Current row values.
 */
function horex_render_row_preview( array $field, array $values ) {
	if ( isset( $field['fields']['hex'] ) ) {
		$hex = isset( $values['hex'] ) ? (string) $values['hex'] : '';

		printf(
			'<span class="horex-row__swatch" data-row-swatch style="background-color:%s"></span>',
			esc_attr( $hex ? $hex : 'transparent' )
		);

		return;
	}

	foreach ( array( 'foto', 'swatch' ) as $key ) {
		if ( ! isset( $field['fields'][ $key ] ) ) {
			continue;
		}

		$id    = isset( $values[ $key ] ) ? (int) $values[ $key ] : 0;
		$thumb = $id ? wp_get_attachment_image_url( $id, 'thumbnail' ) : '';

		printf(
			'<span class="horex-row__thumb"%s></span>',
			$thumb ? ' style="background-image:url(' . esc_url( $thumb ) . ')"' : ''
		);

		return;
	}
}

/**
 * Render a group: a fixed set of sub-fields under one name.
 *
 * @param string $input_name Base input name.
 * @param array  $field      Schema field definition.
 * @param array  $values     Current values.
 * @param int    $depth      Nesting depth.
 */
function horex_render_group( $input_name, array $field, array $values, $depth = 0 ) {
	echo '<div class="horex-grid horex-grid--group">';

	foreach ( $field['fields'] as $sub_name => $sub_field ) {
		horex_render_field_cell(
			$sub_name,
			$sub_field,
			array_key_exists( $sub_name, $values ) ? $values[ $sub_name ] : horex_field_default( $sub_field ),
			$input_name . '[' . $sub_name . ']',
			$depth
		);
	}

	echo '</div>';
}

/**
 * Render a colour field: a native colour input paired with the hex value.
 *
 * @param string $id    Element id, or '' to omit.
 * @param string $name  Input name.
 * @param string $value Current hex value.
 */
function horex_render_color_field( $id, $name, $value ) {
	$hex = $value ? $value : '#ffffff';

	?>
	<span class="horex-color">
		<input
			type="color"
			class="horex-color__picker"
			value="<?php echo esc_attr( $hex ); ?>"
			aria-label="<?php esc_attr_e( 'Choose colour', 'horex' ); ?>"
		/>
		<input
			type="text"
			<?php echo '' === $id ? '' : 'id="' . esc_attr( $id ) . '"'; ?>
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( $value ); ?>"
			class="horex-color__hex code"
			placeholder="#FEC129"
			maxlength="7"
		/>
	</span>
	<?php
}

/**
 * Render an image field: a media-library picker storing an attachment ID.
 *
 * @param string $id    Element id, or '' to omit.
 * @param string $name  Input name.
 * @param int    $value Attachment ID.
 */
function horex_render_image_field( $id, $name, $value ) {
	$thumb = $value ? wp_get_attachment_image_url( $value, 'thumbnail' ) : '';

	?>
	<div class="horex-image<?php echo $thumb ? ' has-image' : ''; ?>" data-horex-image>
		<button type="button" class="horex-image__preview horex-image__select" aria-label="<?php esc_attr_e( 'Choose image', 'horex' ); ?>">
			<?php if ( $thumb ) : ?>
				<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
			<?php else : ?>
				<span class="dashicons dashicons-format-image" aria-hidden="true"></span>
			<?php endif; ?>
		</button>
		<input
			type="hidden"
			<?php echo '' === $id ? '' : 'id="' . esc_attr( $id ) . '"'; ?>
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( (string) $value ); ?>"
			class="horex-image__value"
		/>
		<span class="horex-image__actions">
			<button type="button" class="button button-small horex-image__select">
				<?php echo $value ? esc_html__( 'Replace', 'horex' ) : esc_html__( 'Choose image', 'horex' ); ?>
			</button>
			<button type="button" class="button-link horex-image__remove"<?php echo $value ? '' : ' hidden'; ?>>
				<?php esc_html_e( 'Remove', 'horex' ); ?>
			</button>
		</span>
	</div>
	<?php
}
