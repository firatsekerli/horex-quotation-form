<?php
/**
 * The submission endpoint.
 *
 * The client sends keys, never labels. Every name that reaches storage or an email is
 * resolved here from the catalogue, so nothing a customer types can turn into a
 * product name, a colour or a mesh type.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the AJAX endpoint for logged-in and anonymous visitors alike.
 */
function horex_register_ajax() {
	add_action( 'wp_ajax_horex_submit', 'horex_handle_submit' );
	add_action( 'wp_ajax_nopriv_horex_submit', 'horex_handle_submit' );
}

/**
 * Accept a quote request.
 */
function horex_handle_submit() {
	if ( ! check_ajax_referer( 'horex_submit', 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Uw sessie is verlopen. Ververs de pagina en probeer het opnieuw.' ), 403 );
	}

	// Honeypot: a real customer never sees this field, so anything in it is a bot.
	// Answer as though it worked — telling a bot it failed only invites a retry.
	$trap = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';

	if ( '' !== $trap ) {
		wp_send_json_success( array( 'referentie' => '' ) );
	}

	if ( ! horex_within_rate_limit() ) {
		wp_send_json_error( array( 'message' => 'Er zijn zojuist al aanvragen vanaf dit adres verstuurd. Probeer het over een paar minuten opnieuw.' ), 429 );
	}

	$payload = isset( $_POST['aanvraag'] ) ? (string) wp_unslash( $_POST['aanvraag'] ) : '';
	$raw     = json_decode( $payload, true );

	if ( ! is_array( $raw ) ) {
		wp_send_json_error( array( 'message' => 'De aanvraag kon niet gelezen worden. Probeer het opnieuw.' ), 400 );
	}

	$items = horex_resolve_items( isset( $raw['items'] ) ? (array) $raw['items'] : array() );

	if ( ! $items ) {
		wp_send_json_error( array( 'message' => 'Er staan nog geen producten in uw aanvraag.' ), 400 );
	}

	$klant = isset( $raw['klant'] ) ? (array) $raw['klant'] : array();
	$email = isset( $klant['email'] ) ? sanitize_email( (string) $klant['email'] ) : '';

	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Vul een geldig e-mailadres in, dan kunnen we de prijsopgave versturen.' ), 400 );
	}

	if ( '' === trim( (string) ( isset( $klant['naam'] ) ? $klant['naam'] : '' ) ) ) {
		wp_send_json_error( array( 'message' => 'Vul uw naam in.' ), 400 );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => Horex::CPT,
			'post_status' => 'publish',
			'post_title'  => 'Aanvraag',
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( array( 'message' => 'Uw aanvraag kon niet opgeslagen worden. Probeer het opnieuw of bel ons.' ), 500 );
	}

	// The same write path the admin screen uses: one sanitiser, one place where a
	// measurement is judged out of range, one place a reference is minted.
	$data = horex_save_submission(
		$post_id,
		array(
			'naam'        => isset( $klant['naam'] ) ? $klant['naam'] : '',
			'email'       => $email,
			'telefoon'    => isset( $klant['telefoon'] ) ? $klant['telefoon'] : '',
			'adres'       => isset( $klant['adres'] ) ? $klant['adres'] : '',
			'postcode'    => isset( $klant['postcode'] ) ? $klant['postcode'] : '',
			'plaats'      => isset( $klant['plaats'] ) ? $klant['plaats'] : '',
			'opmerkingen' => isset( $klant['opmerkingen'] ) ? $klant['opmerkingen'] : '',
			'items'       => $items,
			'status'      => 'nieuw',
		)
	);

	horex_note_submission();
	horex_send_notifications( $post_id, $data );

	wp_send_json_success( array( 'referentie' => $data['referentienummer'] ) );
}

/**
 * Turn the keys the client sent into the labels a request is stored under.
 *
 * A key that is not in the catalogue resolves to nothing rather than to itself, so a
 * crafted request cannot invent a product.
 *
 * @param array $rows Raw rows from the client.
 * @return array
 */
function horex_resolve_items( array $rows ) {
	$catalogue = horex_frontend_config();
	$items     = array();

	foreach ( $rows as $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$product = horex_find_by_slug( $catalogue['products'], isset( $row['product'] ) ? $row['product'] : '' );

		if ( ! $product ) {
			continue;
		}

		$colours = isset( $catalogue['kleuren'][ $product['kleurType'] ] ) ? $catalogue['kleuren'][ $product['kleurType'] ] : array();
		$variant = horex_find_by_slug( $product['uitvoeringen'], isset( $row['uitvoering'] ) ? $row['uitvoering'] : '' );
		$colour  = horex_find_by_slug( $colours, isset( $row['kleur'] ) ? $row['kleur'] : '' );
		$gaas    = 'horren' === $product['type']
			? horex_find_by_slug( $catalogue['gaas'], isset( $row['gaas'] ) ? $row['gaas'] : '' )
			: null;

		$items[] = array(
			'ruimtenaam' => isset( $row['ruimte'] ) ? $row['ruimte'] : '',
			'product'    => $product['naam'],
			'uitvoering' => $variant ? $variant['naam'] : '',
			'kleur'      => $colour ? $colour['naam'] : '',
			'kleur_type' => $product['kleurType'],
			'gaas'       => $gaas ? $gaas['naam'] : '',
			'breedte_mm' => isset( $row['breedte'] ) ? $row['breedte'] : 0,
			'hoogte_mm'  => isset( $row['hoogte'] ) ? $row['hoogte'] : 0,
			'foto'       => 0,
		);
	}

	return $items;
}

/**
 * Look a catalogue entry up by its key.
 *
 * @param array  $list List to search.
 * @param string $slug Key.
 * @return array|null
 */
function horex_find_by_slug( $list, $slug ) {
	$slug = sanitize_title( (string) $slug );

	if ( '' === $slug ) {
		return null;
	}

	foreach ( (array) $list as $entry ) {
		if ( isset( $entry['slug'] ) && $entry['slug'] === $slug ) {
			return $entry;
		}
	}

	return null;
}

/**
 * Has this visitor submitted too much, too fast?
 *
 * Deliberately generous: a household sending two requests in an evening is normal,
 * and a false positive costs Hor-Ex a customer.
 *
 * @return bool
 */
function horex_within_rate_limit() {
	$key = horex_rate_limit_key();

	if ( ! $key ) {
		return true;
	}

	$seen = (int) get_transient( $key );

	/**
	 * Filter how many requests one address may send per window.
	 *
	 * @param int $limit Requests allowed.
	 */
	return $seen < (int) apply_filters( 'horex_rate_limit', 5 );
}

/**
 * Count this submission against the rate limit.
 */
function horex_note_submission() {
	$key = horex_rate_limit_key();

	if ( ! $key ) {
		return;
	}

	set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
}

/**
 * A transient key for the requesting address.
 *
 * @return string
 */
function horex_rate_limit_key() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

	return $ip ? 'horex_rl_' . md5( $ip ) : '';
}
