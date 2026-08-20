<?php
/**
 * Field renderers for the settings page.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render one settings field.
 *
 * @param string $name  Field name.
 * @param array  $field Schema field definition.
 * @param mixed  $value Current value.
 */
function horex_render_field( $name, array $field, $value ) {
	$type      = isset( $field['type'] ) ? $field['type'] : 'text';
	$id        = 'horex-field-' . $name;
	$input_name = HOREX_OPTION . '[' . $name . ']';

	switch ( $type ) {
		case 'number':
			printf(
				'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text"%4$s%5$s step="1" inputmode="numeric" />',
				esc_attr( $id ),
				esc_attr( $input_name ),
				esc_attr( (string) $value ),
				isset( $field['min'] ) ? ' min="' . esc_attr( (string) $field['min'] ) . '"' : '',
				isset( $field['max'] ) ? ' max="' . esc_attr( (string) $field['max'] ) . '"' : ''
			);
			break;

		case 'checkbox':
			printf(
				'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1"%3$s /> %4$s</label>',
				esc_attr( $id ),
				esc_attr( $input_name ),
				checked( (bool) $value, true, false ),
				esc_html( isset( $field['toggle'] ) ? $field['toggle'] : $field['label'] )
			);
			break;

		case 'select':
			printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $input_name ) );

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
				'<textarea id="%1$s" name="%2$s" rows="%3$d" class="large-text">%4$s</textarea>',
				esc_attr( $id ),
				esc_attr( $input_name ),
				(int) ( isset( $field['rows'] ) ? $field['rows'] : 4 ),
				esc_textarea( (string) $value )
			);
			break;

		case 'email_list':
			printf(
				'<textarea id="%1$s" name="%2$s" rows="%3$d" class="large-text code">%4$s</textarea>',
				esc_attr( $id ),
				esc_attr( $input_name ),
				(int) ( isset( $field['rows'] ) ? $field['rows'] : 4 ),
				esc_textarea( implode( "\n", (array) $value ) )
			);
			break;

		case 'url':
			printf(
				'<input type="url" id="%1$s" name="%2$s" value="%3$s" class="regular-text" placeholder="https://" />',
				esc_attr( $id ),
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

		case 'text':
		default:
			printf(
				'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text"%4$s />',
				esc_attr( $id ),
				esc_attr( $input_name ),
				esc_attr( (string) $value ),
				isset( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : ''
			);
			break;
	}
}

/**
 * Render a colour field: a native colour input paired with the hex value.
 *
 * @param string $id    Element id.
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
			id="<?php echo esc_attr( $id ); ?>"
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
 * @param string $id    Element id.
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
			id="<?php echo esc_attr( $id ); ?>"
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
