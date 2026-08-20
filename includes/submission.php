<?php
/**
 * Storage and admin screens for incoming quote requests.
 *
 * A request lives in one post meta array, mirroring how the settings are stored. A few
 * values are additionally written to flat meta keys so the list table can sort on them.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Post meta key holding the whole request.
 */
const HOREX_SUBMISSION_META = '_horex_submission';

/**
 * Flat meta keys, denormalised from the request so admin columns can sort.
 */
const HOREX_META_REFERENCE = '_horex_reference';
const HOREX_META_STATUS    = '_horex_status';
const HOREX_META_COUNT     = '_horex_item_count';

/**
 * Read a request, filled out against the schema.
 *
 * @param int $post_id Request post ID.
 * @return array
 */
function horex_get_submission( $post_id ) {
	$stored = get_post_meta( $post_id, HOREX_SUBMISSION_META, true );

	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$data = array();

	foreach ( horex_submission_fields() as $name => $field ) {
		$data[ $name ] = array_key_exists( $name, $stored ) ? $stored[ $name ] : horex_field_default( $field );
	}

	return $data;
}

/**
 * Sanitise and store a request, then bring its title and flat meta back in step.
 *
 * This is the single write path: the admin screen and the front-end submission
 * endpoint both come through here, so they cannot diverge.
 *
 * @param int   $post_id Request post ID.
 * @param array $input   Raw values, keyed by schema field name.
 * @return array The stored data.
 */
function horex_save_submission( $post_id, array $input ) {
	$data = horex_sanitize_subfields( $input, horex_submission_fields() );

	// Never trust a client for this: re-derive it from the measurement rules.
	$data['items'] = horex_flag_out_of_range( $data['items'] );

	if ( '' === $data['referentienummer'] ) {
		// Reuse what this request already carries. Minting a reference is the one
		// side effect here that cannot be undone, so it happens exactly once per
		// request no matter how often the post is saved.
		$existing = (string) get_post_meta( $post_id, HOREX_META_REFERENCE, true );

		$data['referentienummer'] = '' !== $existing ? $existing : horex_generate_reference( $post_id );
	}

	if ( '' === $data['status'] ) {
		$data['status'] = 'nieuw';
	}

	update_post_meta( $post_id, HOREX_SUBMISSION_META, $data );
	update_post_meta( $post_id, HOREX_META_REFERENCE, $data['referentienummer'] );
	update_post_meta( $post_id, HOREX_META_STATUS, $data['status'] );
	update_post_meta( $post_id, HOREX_META_COUNT, count( $data['items'] ) );

	horex_sync_submission_title( $post_id, $data );

	return $data;
}

/**
 * Mark every measurement that falls outside the configured range.
 *
 * Zero is left alone: an unfilled measurement is missing, not out of range.
 *
 * @param array $items Sanitised measurement rows.
 * @return array
 */
function horex_flag_out_of_range( array $items ) {
	$min = (int) horex_get_setting( 'min_mm' );
	$max = (int) horex_get_setting( 'max_mm' );

	foreach ( $items as $index => $item ) {
		$outside = false;

		foreach ( array( 'breedte_mm', 'hoogte_mm' ) as $key ) {
			$value = isset( $item[ $key ] ) ? (int) $item[ $key ] : 0;

			if ( $value > 0 && ( ( $min > 0 && $value < $min ) || ( $max > 0 && $value > $max ) ) ) {
				$outside = true;
			}
		}

		$items[ $index ]['buiten_standaard'] = $outside;
	}

	return $items;
}

/**
 * Build the next reference number, for example HX-2026-0031.
 *
 * The counter restarts each year. Should a number already be taken — two requests
 * landing at once — the next free one is used rather than a duplicate.
 *
 * @param int $post_id Request post ID, used as a last-resort suffix.
 * @return string
 */
function horex_generate_reference( $post_id = 0 ) {
	$prefix   = (string) horex_get_setting( 'referentie_prefix', 'HX-' );
	$year     = (int) current_time( 'Y' );
	$counters = get_option( 'horex_reference_counters', array() );

	if ( ! is_array( $counters ) ) {
		$counters = array();
	}

	$next = isset( $counters[ $year ] ) ? (int) $counters[ $year ] : 0;

	for ( $attempt = 0; $attempt < 100; $attempt++ ) {
		$next++;
		$reference = sprintf( '%s%d-%04d', $prefix, $year, $next );

		if ( ! horex_reference_exists( $reference, $post_id ) ) {
			$counters[ $year ] = $next;
			update_option( 'horex_reference_counters', $counters, false );

			return $reference;
		}
	}

	// Pathological case only: fall back to something that cannot collide.
	return sprintf( '%s%d-%d', $prefix, $year, $post_id ? $post_id : time() );
}

/**
 * Is this reference already used by another request?
 *
 * @param string $reference Reference to check.
 * @param int    $exclude   Post ID to ignore.
 * @return bool
 */
function horex_reference_exists( $reference, $exclude = 0 ) {
	$found = get_posts(
		array(
			'post_type'        => Horex::CPT,
			'post_status'      => 'any',
			'meta_key'         => HOREX_META_REFERENCE, // phpcs:ignore WordPress.DB.SlowDBQuery -- Indexed lookup on a small table.
			'meta_value'       => $reference,           // phpcs:ignore WordPress.DB.SlowDBQuery -- Exact match.
			'fields'           => 'ids',
			'posts_per_page'   => 1,
			'post__not_in'     => $exclude ? array( $exclude ) : array(),
			'suppress_filters' => true,
			'no_found_rows'    => true,
		)
	);

	return ! empty( $found );
}

/**
 * Keep the post title as "{reference} — {name}".
 *
 * @param int   $post_id Request post ID.
 * @param array $data    Stored request data.
 */
function horex_sync_submission_title( $post_id, array $data ) {
	static $syncing = false;

	if ( $syncing ) {
		return;
	}

	$reference = trim( (string) $data['referentienummer'] );
	$name      = trim( (string) $data['naam'] );

	$title = $name ? $reference . ' — ' . $name : $reference;

	if ( '' === $title || get_post_field( 'post_title', $post_id ) === $title ) {
		return;
	}

	$syncing = true;

	// Detach our own save handler: wp_update_post fires save_post again, and the
	// nested run would sanitise the same $_POST a second time and mint a second
	// reference number.
	remove_action( 'save_post_' . Horex::CPT, 'horex_handle_submission_save' );

	wp_update_post(
		array(
			'ID'         => $post_id,
			'post_title' => $title,
		)
	);

	add_action( 'save_post_' . Horex::CPT, 'horex_handle_submission_save' );

	$syncing = false;
}

/* Admin screens ------------------------------------------------------------ */

/**
 * Register the request meta boxes.
 */
function horex_register_submission_meta_boxes() {
	foreach ( horex_submission_schema() as $key => $group ) {
		add_meta_box(
			'horex-' . $key,
			$group['label'],
			'horex_render_submission_meta_box',
			Horex::CPT,
			isset( $group['context'] ) ? $group['context'] : 'normal',
			'default',
			array( 'group' => $key )
		);
	}
}

/**
 * Render one request meta box.
 *
 * @param WP_Post $post Current post.
 * @param array   $box  Meta box arguments.
 */
function horex_render_submission_meta_box( $post, $box ) {
	$schema = horex_submission_schema();
	$key    = $box['args']['group'];

	if ( ! isset( $schema[ $key ] ) ) {
		return;
	}

	$data = horex_get_submission( $post->ID );

	// One nonce for the screen; the first meta box to render emits it.
	static $nonce_done = false;

	if ( ! $nonce_done ) {
		wp_nonce_field( 'horex_save_submission', 'horex_submission_nonce' );
		$nonce_done = true;
	}

	echo '<div class="horex-group horex-group--flush">';

	foreach ( $schema[ $key ]['fields'] as $name => $field ) {
		$is_block = in_array( $field['type'], horex_block_field_types(), true );
		$width    = isset( $field['width'] ) ? $field['width'] : 'full';

		printf( '<div class="horex-field horex-field--%s">', esc_attr( $is_block ? 'block' : $width ) );
		printf( '<span class="horex-field__label">%s</span>', esc_html( $field['label'] ) );

		horex_render_field( $name, $field, $data[ $name ], HOREX_SUBMISSION_META . '[' . $name . ']', '' );

		if ( ! empty( $field['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $field['description'] ) );
		}

		echo '</div>';
	}

	echo '</div>';
}

/**
 * Persist the request when the post is saved in the admin.
 *
 * @param int $post_id Post being saved.
 */
function horex_handle_submission_save( $post_id ) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || Horex::CPT !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['horex_submission_nonce'] ) ) {
		return;
	}

	check_admin_referer( 'horex_save_submission', 'horex_submission_nonce' );

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$raw = isset( $_POST[ HOREX_SUBMISSION_META ] ) && is_array( $_POST[ HOREX_SUBMISSION_META ] )
		? wp_unslash( $_POST[ HOREX_SUBMISSION_META ] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Sanitised against the schema below.
		: array();

	horex_save_submission( $post_id, $raw );
}

/**
 * Columns for the requests list table.
 *
 * @param array $columns Default columns.
 * @return array
 */
function horex_submission_columns( $columns ) {
	$built = array();

	if ( isset( $columns['cb'] ) ) {
		$built['cb'] = $columns['cb'];
	}

	$built['horex_naam']       = __( 'Name', 'horex' );
	$built['horex_referentie'] = __( 'Reference', 'horex' );
	$built['horex_aantal']     = __( 'Products', 'horex' );
	$built['horex_status']     = __( 'Status', 'horex' );
	$built['date']             = __( 'Date', 'horex' );

	return $built;
}

/**
 * Render a request list-table cell.
 *
 * @param string $column  Column key.
 * @param int    $post_id Request post ID.
 */
function horex_submission_column( $column, $post_id ) {
	switch ( $column ) {
		case 'horex_naam':
			$data = horex_get_submission( $post_id );
			$name = trim( (string) $data['naam'] );

			printf(
				'<strong><a class="row-title" href="%1$s">%2$s</a></strong>',
				esc_url( (string) get_edit_post_link( $post_id ) ),
				esc_html( $name ? $name : __( '(no name)', 'horex' ) )
			);

			if ( $data['plaats'] ) {
				printf( '<br /><span class="description">%s</span>', esc_html( $data['plaats'] ) );
			}
			break;

		case 'horex_referentie':
			echo esc_html( (string) get_post_meta( $post_id, HOREX_META_REFERENCE, true ) );
			break;

		case 'horex_aantal':
			echo esc_html( (string) (int) get_post_meta( $post_id, HOREX_META_COUNT, true ) );
			break;

		case 'horex_status':
			$statuses = horex_submission_statuses();
			$status   = (string) get_post_meta( $post_id, HOREX_META_STATUS, true );

			printf(
				'<span class="horex-status horex-status--%1$s">%2$s</span>',
				esc_attr( $status ? $status : 'nieuw' ),
				esc_html( isset( $statuses[ $status ] ) ? $statuses[ $status ] : $statuses['nieuw'] )
			);
			break;
	}
}

/**
 * Make reference and status sortable.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function horex_submission_sortable_columns( $columns ) {
	$columns['horex_referentie'] = 'horex_referentie';
	$columns['horex_status']     = 'horex_status';
	$columns['horex_aantal']     = 'horex_aantal';

	return $columns;
}

/**
 * Translate the sortable columns into meta queries.
 *
 * @param WP_Query $query Current query.
 */
function horex_submission_sort( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || Horex::CPT !== $query->get( 'post_type' ) ) {
		return;
	}

	$map = array(
		'horex_referentie' => array( HOREX_META_REFERENCE, 'meta_value' ),
		'horex_status'     => array( HOREX_META_STATUS, 'meta_value' ),
		'horex_aantal'     => array( HOREX_META_COUNT, 'meta_value_num' ),
	);

	$orderby = $query->get( 'orderby' );

	if ( isset( $map[ $orderby ] ) ) {
		$query->set( 'meta_key', $map[ $orderby ][0] );
		$query->set( 'orderby', $map[ $orderby ][1] );
	}
}
