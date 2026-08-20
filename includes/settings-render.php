<?php
/**
 * Field renderers for the settings page.
 *
 * Repeaters are rendered as a list of rows plus an inert <template> holding a blank
 * row. The template uses __PREFIX{depth}__ / __INDEX{depth}__ placeholders that the
 * admin script substitutes when a row is added — depth-tagged so that a nested
 * repeater's own placeholders survive the parent's substitution.
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
 * Render one settings field control.
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
				'<label><input type="checkbox"%1$s name="%2$s" value="1"%3$s /> %4$s</label>',
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
				(int) ( isset( $field['rows'] ) ? $field['rows'] : 4 ),
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
 * Render a repeater: existing rows, an add button and a blank-row template.
 *
 * @param string $input_name Base input name for the repeater.
 * @param array  $field      Schema field definition.
 * @param array  $rows       Current rows.
 * @param int    $depth      Nesting depth.
 */
function horex_render_repeater( $input_name, array $field, array $rows, $depth = 0 ) {
	$rows = array_values( $rows );

	?>
	<div
		class="horex-repeater"
		data-repeater
		data-prefix="<?php echo esc_attr( $input_name ); ?>"
		data-depth="<?php echo esc_attr( (string) $depth ); ?>"
		data-singular="<?php echo esc_attr( isset( $field['singular'] ) ? $field['singular'] : $field['label'] ); ?>"
	>
		<div class="horex-repeater__rows">
			<?php foreach ( $rows as $index => $row ) : ?>
				<?php horex_render_repeater_row( $input_name, $field, (array) $row, (string) $index, $depth ); ?>
			<?php endforeach; ?>
		</div>

		<p class="horex-repeater__empty"<?php echo $rows ? ' hidden' : ''; ?>>
			<?php esc_html_e( 'Nog niets toegevoegd.', 'horex' ); ?>
		</p>

		<p class="horex-repeater__footer">
			<button type="button" class="button horex-repeater__add">
				<?php echo esc_html( isset( $field['button'] ) ? $field['button'] : __( 'Rij toevoegen', 'horex' ) ); ?>
			</button>
		</p>

		<template class="horex-repeater__template">
			<?php horex_render_repeater_row( '__PREFIX' . $depth . '__', $field, array(), '__INDEX' . $depth . '__', $depth ); ?>
		</template>
	</div>
	<?php
}

/**
 * Render a single repeater row.
 *
 * @param string $prefix Base input name for the repeater.
 * @param array  $field  Schema field definition.
 * @param array  $values Current row values.
 * @param string $index  Row index, or a placeholder token in the template.
 * @param int    $depth  Nesting depth.
 */
function horex_render_repeater_row( $prefix, array $field, array $values, $index, $depth = 0 ) {
	$row_label_key = isset( $field['row_label'] ) ? $field['row_label'] : '';
	$row_label     = ( $row_label_key && ! empty( $values[ $row_label_key ] ) ) ? $values[ $row_label_key ] : '';
	$singular      = isset( $field['singular'] ) ? $field['singular'] : $field['label'];

	?>
	<div class="horex-repeater__row" data-repeater-row data-index="<?php echo esc_attr( $index ); ?>">
		<div class="horex-repeater__header">
			<span class="horex-repeater__number" aria-hidden="true"></span>
			<span class="horex-repeater__title" data-row-title data-placeholder="<?php echo esc_attr( $singular ); ?>">
				<?php echo esc_html( $row_label ? $row_label : $singular ); ?>
			</span>
			<span class="horex-repeater__tools">
				<button type="button" class="button-link horex-repeater__move" data-move="up" aria-label="<?php esc_attr_e( 'Omhoog verplaatsen', 'horex' ); ?>">&uarr;</button>
				<button type="button" class="button-link horex-repeater__move" data-move="down" aria-label="<?php esc_attr_e( 'Omlaag verplaatsen', 'horex' ); ?>">&darr;</button>
				<button type="button" class="button-link horex-repeater__remove" aria-label="<?php esc_attr_e( 'Verwijderen', 'horex' ); ?>"><?php esc_html_e( 'Verwijderen', 'horex' ); ?></button>
			</span>
		</div>

		<div class="horex-repeater__body">
			<?php foreach ( $field['fields'] as $sub_name => $sub_field ) : ?>
				<?php
				$sub_value = array_key_exists( $sub_name, $values ) ? $values[ $sub_name ] : horex_field_default( $sub_field );
				$sub_input = $prefix . '[' . $index . '][' . $sub_name . ']';
				$is_block  = in_array( $sub_field['type'], horex_block_field_types(), true );
				$width     = isset( $sub_field['width'] ) ? $sub_field['width'] : 'full';
				$is_label  = ( $sub_name === $row_label_key );
				?>
				<div
					class="horex-field horex-field--<?php echo esc_attr( $is_block ? 'block' : $width ); ?>"
					<?php echo $is_label ? 'data-row-label-field' : ''; ?>
				>
					<span class="horex-field__label"><?php echo esc_html( $sub_field['label'] ); ?></span>
					<?php
					horex_render_field(
						$sub_name,
						$sub_field,
						$sub_value,
						$sub_input,
						'',
						$depth + 1
					);
					?>
					<?php if ( ! empty( $sub_field['description'] ) ) : ?>
						<p class="description"><?php echo esc_html( $sub_field['description'] ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
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
	?>
	<div class="horex-group">
		<?php foreach ( $field['fields'] as $sub_name => $sub_field ) : ?>
			<?php
			$sub_value = array_key_exists( $sub_name, $values ) ? $values[ $sub_name ] : horex_field_default( $sub_field );
			$sub_input = $input_name . '[' . $sub_name . ']';
			$is_block  = in_array( $sub_field['type'], horex_block_field_types(), true );
			$width     = isset( $sub_field['width'] ) ? $sub_field['width'] : 'full';
			?>
			<div class="horex-field horex-field--<?php echo esc_attr( $is_block ? 'block' : $width ); ?>">
				<span class="horex-field__label"><?php echo esc_html( $sub_field['label'] ); ?></span>
				<?php horex_render_field( $sub_name, $sub_field, $sub_value, $sub_input, '', $depth ); ?>
				<?php if ( ! empty( $sub_field['description'] ) ) : ?>
					<p class="description"><?php echo esc_html( $sub_field['description'] ); ?></p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
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
			aria-label="<?php esc_attr_e( 'Kleur kiezen', 'horex' ); ?>"
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
	<div class="horex-image" data-horex-image>
		<div class="horex-image__preview">
			<?php if ( $thumb ) : ?>
				<img src="<?php echo esc_url( $thumb ); ?>" alt="" />
			<?php endif; ?>
		</div>
		<input
			type="hidden"
			<?php echo '' === $id ? '' : 'id="' . esc_attr( $id ) . '"'; ?>
			name="<?php echo esc_attr( $name ); ?>"
			value="<?php echo esc_attr( (string) $value ); ?>"
			class="horex-image__value"
		/>
		<p class="horex-image__actions">
			<button type="button" class="button horex-image__select">
				<?php echo $value ? esc_html__( 'Vervangen', 'horex' ) : esc_html__( 'Afbeelding kiezen', 'horex' ); ?>
			</button>
			<button type="button" class="button-link horex-image__remove"<?php echo $value ? '' : ' hidden'; ?>>
				<?php esc_html_e( 'Verwijderen', 'horex' ); ?>
			</button>
		</p>
	</div>
	<?php
}
