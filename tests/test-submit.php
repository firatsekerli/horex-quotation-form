<?php
/**
 * Exercises the submission gate and the notification emails.
 *
 * The client sends keys, never labels. Everything a request is stored and printed
 * under is resolved here from the catalogue, so this is the boundary that stops a
 * crafted request inventing a product, a colour or a mesh type.
 *
 * Run with:  php tests/test-submit.php
 *
 * @package Horex
 */

require_once __DIR__ . '/bootstrap.php';

horex_maybe_seed();

/* Keys resolve to the labels in the catalogue. */
$items = horex_resolve_items(
	array(
		array(
			'product'    => 'plisse-horren',
			'uitvoering' => 'plisse-hordeur',
			'kleur'      => 'antraciet',
			'gaas'       => 'anti-pollen-gaas',
			'ruimte'     => 'Woonkamer schuifpui',
			'breedte'    => 2100,
			'hoogte'     => 2400,
		),
	)
);

check( 'one row resolves', count( $items ), 1 );
check( 'the product name is resolved', $items[0]['product'], 'Plissé horren' );
check( 'the variant name is resolved', $items[0]['uitvoering'], 'Plissé hordeur' );
check( 'the colour name is resolved', $items[0]['kleur'], 'Antraciet' );
check( 'the colour type is recorded', $items[0]['kleur_type'], 'frame' );
check( 'the mesh name is resolved', $items[0]['gaas'], 'Anti-pollen gaas' );
check( 'the room name is carried', $items[0]['ruimtenaam'], 'Woonkamer schuifpui' );

/* A curtain carries no variant or mesh, whatever the client claims. */
$items = horex_resolve_items(
	array(
		array(
			'product'    => 'wave-gordijnen',
			'uitvoering' => 'plisse-hordeur',
			'gaas'       => 'anti-pollen-gaas',
			'kleur'      => 'zand',
			'ruimte'     => 'Slaapkamer',
			'breedte'    => 1400,
			'hoogte'     => 2600,
		),
	)
);
check( 'a curtain gets no variant', $items[0]['uitvoering'], '' );
check( 'a curtain gets no mesh', $items[0]['gaas'], '' );
check( 'a curtain uses the fabric palette', $items[0]['kleur_type'], 'stof' );
check( 'a fabric colour resolves', $items[0]['kleur'], 'Zand' );

/* Nothing invented reaches storage. */
$items = horex_resolve_items(
	array(
		array( 'product' => 'gratis-hor', 'ruimte' => 'Zolder', 'breedte' => 1, 'hoogte' => 1 ),
		array( 'product' => 'plisse-horren', 'kleur' => '<script>alert(1)</script>', 'uitvoering' => 'onzin', 'gaas' => 'onzin', 'ruimte' => 'Keuken', 'breedte' => 800, 'hoogte' => 1200 ),
		'not-an-array',
	)
);
check( 'an unknown product is dropped entirely', count( $items ), 1 );
check( 'an unknown colour resolves to nothing', $items[0]['kleur'], '' );
check( 'an unknown variant resolves to nothing', $items[0]['uitvoering'], '' );
check( 'an unknown mesh resolves to nothing', $items[0]['gaas'], '' );
check( 'the surviving row is the real one', $items[0]['product'], 'Plissé horren' );

/* End to end: resolve, store, notify. */
update_option( HOREX_OPTION, array_merge( get_option( HOREX_OPTION ), array( 'ontvangers' => array( 'verkoop@hor-ex.nl' ), 'stuur_klant_kopie' => true ) ) );
$GLOBALS['horex_test_mail'] = array();

$data = horex_save_submission(
	501,
	array(
		'naam'    => 'Anna de Vries',
		'email'   => 'anna@example.nl',
		'plaats'  => 'Grave',
		'items'   => horex_resolve_items(
			array(
				array( 'product' => 'plisse-horren', 'uitvoering' => 'plisse-hordeur', 'kleur' => 'antraciet', 'gaas' => 'standaard-gaas', 'ruimte' => 'Woonkamer schuifpui', 'breedte' => 2100, 'hoogte' => 2400 ),
				array( 'product' => 'veranda-zonwering', 'kleur' => 'zand', 'ruimte' => 'Veranda', 'breedte' => 6200, 'hoogte' => 2500 ),
			)
		),
	)
);

check( 'both rows stored', count( $data['items'] ), 2 );
check( 'the oversize row is flagged server-side', $data['items'][1]['buiten_standaard'], true );
check( 'the in-range row is not', $data['items'][0]['buiten_standaard'], false );

horex_send_notifications( 501, $data );
$mail = $GLOBALS['horex_test_mail'];

check( 'two emails are sent', count( $mail ), 2 );
check( 'the internal mail goes to the configured address', $mail[0]['to'], array( 'verkoop@hor-ex.nl' ) );
check( 'its subject carries the reference', false !== strpos( $mail[0]['subject'], $data['referentienummer'] ), true );
check( 'replies go to the customer', in_array( 'Reply-To: Anna de Vries <anna@example.nl>', $mail[0]['headers'], true ), true );

foreach ( array( 'Ruimte', 'Product', 'Uitvoering', 'Kleur', 'Gaas', 'Breedte', 'Hoogte' ) as $column ) {
	check( "the table has a {$column} column", false !== strpos( $mail[0]['body'], '>' . $column . '<' ), true );
}

check( 'the table lists the room', false !== strpos( $mail[0]['body'], 'Woonkamer schuifpui' ), true );
check( 'the table lists the measurements', false !== strpos( $mail[0]['body'], '2100 mm' ), true );
check( 'a missing value shows as a dash, not blank', false !== strpos( $mail[0]['body'], '—' ), true );
check( 'the oversize row is called out for the visit', false !== strpos( $mail[0]['body'], 'buiten het standaardbereik' ), true );
check( 'no price appears anywhere in the internal mail', preg_match( '/€|prijs per|EUR\b/u', $mail[0]['body'] ), 0 );

check( 'the customer gets their copy', $mail[1]['to'], 'anna@example.nl' );
check( 'their copy carries the reference', false !== strpos( $mail[1]['body'], $data['referentienummer'] ), true );
check( 'their copy repeats the table', false !== strpos( $mail[1]['body'], 'Woonkamer schuifpui' ), true );
check( 'their copy says it is not an order', false !== strpos( $mail[1]['body'], 'geen bestelling' ), true );
check( 'no price appears in their copy', preg_match( '/€|prijs per|EUR\b/u', $mail[1]['body'] ), 0 );

/* The customer copy can be switched off. */
update_option( HOREX_OPTION, array_merge( get_option( HOREX_OPTION ), array( 'stuur_klant_kopie' => false ) ) );
$GLOBALS['horex_test_mail'] = array();
horex_send_notifications( 501, $data );
check( 'only the internal mail is sent when the copy is off', count( $GLOBALS['horex_test_mail'] ), 1 );

/* With no recipients configured, the site admin gets it. */
update_option( HOREX_OPTION, array_merge( get_option( HOREX_OPTION ), array( 'ontvangers' => array(), 'stuur_klant_kopie' => false ) ) );
update_option( 'admin_email', 'beheer@hor-ex.nl' );
$GLOBALS['horex_test_mail'] = array();
horex_send_notifications( 501, $data );
check( 'it falls back to the site admin', $GLOBALS['horex_test_mail'][0]['to'], array( 'beheer@hor-ex.nl' ) );

/* Rate limiting. */
$GLOBALS['horex_test_transients'] = array();
$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
check( 'a first request is allowed', horex_within_rate_limit(), true );

for ( $i = 0; $i < 5; $i++ ) {
	horex_note_submission();
}

check( 'a sixth request in the window is refused', horex_within_rate_limit(), false );

finish();
