<?php
/**
 * Walks a request the whole way: the payload the browser actually sends, through the
 * endpoint, into storage, out as email, and onto the admin list table.
 *
 * The other suites test each piece. This one tests that they are joined up — that the
 * field names the front-end sends are the ones the back-end reads, and that a rename
 * on either side shows up as a failure here rather than as a silently empty column.
 *
 * Run with:  php tests/test-integration.php
 *
 * @package Horex
 */

require_once __DIR__ . '/bootstrap.php';

horex_maybe_seed();
update_option(
	HOREX_OPTION,
	array_merge(
		get_option( HOREX_OPTION ),
		array( 'ontvangers' => array( 'verkoop@hor-ex.nl' ), 'stuur_klant_kopie' => true )
	)
);

/**
 * Post a request the way assets/js/offerte.js does.
 *
 * @param array  $body  Request body, as the browser builds it.
 * @return array {ok, payload, status}
 */
function post_request( array $body ) {
	$GLOBALS['horex_test_mail'] = array();
	$_POST                      = $body;

	try {
		horex_handle_submit();
	} catch ( Horex_Json_Response $response ) {
		return array(
			'ok'      => $response->ok,
			'payload' => $response->payload,
			'status'  => $response->getCode(),
		);
	}

	return array( 'ok' => null, 'payload' => array(), 'status' => 0 );
}

/**
 * The exact shape assets/js/offerte.js sends.
 *
 * @param array $overrides Replacement keys.
 * @return array
 */
function browser_body( array $overrides = array() ) {
	$aanvraag = array(
		'klant' => array(
			'naam'        => 'Anna de Vries',
			'email'       => 'anna@example.nl',
			'telefoon'    => '06 12345678',
			'adres'       => 'Dorpsstraat 1',
			'postcode'    => '5361 AA',
			'plaats'      => 'Grave',
			'opmerkingen' => 'Graag in de ochtend bellen.',
		),
		'items' => array(
			array(
				'product'    => 'plisse-horren',
				'uitvoering' => 'plisse-hordeur',
				'kleur'      => 'antraciet',
				'gaas'       => 'anti-pollen-gaas',
				'ruimte'     => 'Woonkamer schuifpui',
				'breedte'    => 2100,
				'hoogte'     => 2400,
			),
			array(
				'product'    => 'veranda-zonwering',
				'uitvoering' => null,
				'kleur'      => 'zand',
				'gaas'       => null,
				'ruimte'     => 'Veranda achterzijde',
				'breedte'    => 6200,
				'hoogte'     => 2500,
			),
		),
	);

	return array_merge(
		array(
			'action'   => 'horex_submit',
			'nonce'    => 'nonce-horex_submit',
			'website'  => '',
			'aanvraag' => wp_json_encode( $aanvraag ),
		),
		$overrides
	);
}

/* The happy path, end to end. */
$_SERVER['REMOTE_ADDR'] = '198.51.100.7';
$GLOBALS['horex_test_transients'] = array();

$result = post_request( browser_body() );

check( 'the request is accepted', $result['ok'], true );
check( 'a reference comes back for the confirmation screen', 1, preg_match( '/^HX-\d{4}-\d{4}$/', $result['payload']['referentie'] ) );

$post_id = array_key_last( $GLOBALS['horex_test_posts'] );
$stored  = horex_get_submission( $post_id );

check( 'the request was stored', is_array( $stored ), true );
check( 'the name arrived', $stored['naam'], 'Anna de Vries' );
check( 'the email arrived', $stored['email'], 'anna@example.nl' );
check( 'the phone number arrived', $stored['telefoon'], '06 12345678' );
check( 'the address arrived', $stored['adres'], 'Dorpsstraat 1' );
check( 'the postcode arrived', $stored['postcode'], '5361 AA' );
check( 'the town arrived', $stored['plaats'], 'Grave' );
check( 'the note arrived', $stored['opmerkingen'], 'Graag in de ochtend bellen.' );
check( 'both measurements arrived', count( $stored['items'] ), 2 );

check( 'keys became labels', $stored['items'][0]['product'], 'Plissé horren' );
check( 'the variant became a label', $stored['items'][0]['uitvoering'], 'Plissé hordeur' );
check( 'the colour became a label', $stored['items'][0]['kleur'], 'Antraciet' );
check( 'the mesh became a label', $stored['items'][0]['gaas'], 'Anti-pollen gaas' );
check( 'the room name is intact', $stored['items'][0]['ruimtenaam'], 'Woonkamer schuifpui' );
check( 'the width is a number', $stored['items'][0]['breedte_mm'], 2100 );
check( 'the height is a number', $stored['items'][0]['hoogte_mm'], 2400 );
check( 'an awning stores no mesh', $stored['items'][1]['gaas'], '' );
check( 'an awning stores no variant', $stored['items'][1]['uitvoering'], '' );
check( 'the awning uses the canvas palette', $stored['items'][1]['kleur_type'], 'doek' );
check( 'the oversize row is flagged by the server', $stored['items'][1]['buiten_standaard'], true );
check( 'the in-range row is not', $stored['items'][0]['buiten_standaard'], false );
check( 'the status starts at nieuw', $stored['status'], 'nieuw' );
check( 'the reference matches what the browser was told', $stored['referentienummer'], $result['payload']['referentie'] );

/* The post title, as the admin list shows it. */
check(
	'the post is titled by reference and name',
	get_post_field( 'post_title', $post_id ),
	$stored['referentienummer'] . ' — Anna de Vries'
);

/* The admin columns read what the endpoint wrote. */
foreach ( array( 'horex_naam', 'horex_referentie', 'horex_aantal', 'horex_status' ) as $column ) {
	ob_start();
	horex_submission_column( $column, $post_id );
	$cell = trim( (string) ob_get_clean() );

	check( "the {$column} column is not blank", '' !== $cell, true );
}

ob_start();
horex_submission_column( 'horex_aantal', $post_id );
check( 'the product count column reads two', trim( strip_tags( (string) ob_get_clean() ) ), '2' );

ob_start();
horex_submission_column( 'horex_status', $post_id );
check( 'the status column reads New', trim( strip_tags( (string) ob_get_clean() ) ), 'New' );

ob_start();
horex_submission_column( 'horex_referentie', $post_id );
check( 'the reference column matches', trim( strip_tags( (string) ob_get_clean() ) ), $stored['referentienummer'] );

/* Both emails went out, carrying the same request. */
$mail = $GLOBALS['horex_test_mail'];
check( 'two emails were sent', count( $mail ), 2 );
check( 'Hor-Ex got theirs', $mail[0]['to'], array( 'verkoop@hor-ex.nl' ) );
check( 'the customer got theirs', $mail[1]['to'], 'anna@example.nl' );
check( 'the table names the room', false !== strpos( $mail[0]['body'], 'Woonkamer schuifpui' ), true );
check( 'the table names the second room', false !== strpos( $mail[0]['body'], 'Veranda achterzijde' ), true );
check( 'the note reached Hor-Ex', false !== strpos( $mail[0]['body'], 'Graag in de ochtend bellen.' ), true );

/* Refusals. */
$GLOBALS['horex_test_transients'] = array();

$bad = post_request( browser_body( array( 'nonce' => 'nonce-something-else' ) ) );
check( 'a bad nonce is refused', $bad['ok'], false );
check( 'and answered with 403', $bad['status'], 403 );

$trap = post_request( browser_body( array( 'website' => 'https://spam.example' ) ) );
check( 'a filled honeypot looks like success to the bot', $trap['ok'], true );
check( 'but nothing was emailed', count( $GLOBALS['horex_test_mail'] ), 0 );

$junk = post_request( browser_body( array( 'aanvraag' => 'not json' ) ) );
check( 'unreadable JSON is refused', $junk['ok'], false );

$empty = post_request( browser_body( array( 'aanvraag' => wp_json_encode( array( 'klant' => array( 'naam' => 'X', 'email' => 'x@example.nl' ), 'items' => array() ) ) ) ) );
check( 'a request with no products is refused', $empty['ok'], false );

$invented = post_request(
	browser_body(
		array(
			'aanvraag' => wp_json_encode(
				array(
					'klant' => array( 'naam' => 'X', 'email' => 'x@example.nl' ),
					'items' => array( array( 'product' => 'gratis-hor-deluxe', 'ruimte' => 'Zolder', 'breedte' => 900, 'hoogte' => 900 ) ),
				)
			),
		)
	)
);
check( 'an invented product leaves nothing to submit', $invented['ok'], false );

$noname = post_request(
	browser_body(
		array(
			'aanvraag' => wp_json_encode(
				array(
					'klant' => array( 'naam' => '', 'email' => 'x@example.nl' ),
					'items' => array( array( 'product' => 'plisse-horren', 'ruimte' => 'Zolder', 'breedte' => 900, 'hoogte' => 900 ) ),
				)
			),
		)
	)
);
check( 'a request without a name is refused', $noname['ok'], false );

$noemail = post_request(
	browser_body(
		array(
			'aanvraag' => wp_json_encode(
				array(
					'klant' => array( 'naam' => 'X', 'email' => 'niet-een-adres' ),
					'items' => array( array( 'product' => 'plisse-horren', 'ruimte' => 'Zolder', 'breedte' => 900, 'hoogte' => 900 ) ),
				)
			),
		)
	)
);
check( 'a request without a usable address is refused', $noemail['ok'], false );

/* Every refusal says something a customer can act on. */
foreach ( array( $bad, $junk, $empty, $invented, $noname, $noemail ) as $refusal ) {
	check( 'the refusal explains itself', ! empty( $refusal['payload']['message'] ), true );
}

/* Two requests in a row both work; the limit is not hair-trigger. */
$GLOBALS['horex_test_transients'] = array();
check( 'a first request works', post_request( browser_body() )['ok'], true );
check( 'a second request works too', post_request( browser_body() )['ok'], true );

finish();
