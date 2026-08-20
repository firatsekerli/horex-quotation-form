<?php
/**
 * Exercises the submission write path: sanitising, out-of-range flagging, reference
 * numbering and the generated post title.
 *
 * Run with:  php tests/test-submission.php
 *
 * @package Horex
 */

require_once __DIR__ . '/bootstrap.php';

// The measurement rules the flagging is derived from.
update_option( HOREX_OPTION, array( 'min_mm' => 300, 'max_mm' => 6000, 'referentie_prefix' => 'HX-' ) );

/* A request as the front-end will post it. */
$posted = array(
	'naam'     => '  Anna de Vries ',
	'email'    => 'anna@example.nl',
	'telefoon' => '06 12345678',
	'adres'    => 'Dorpsstraat 1',
	'postcode' => '5361 AA',
	'plaats'   => 'Grave',
	'items'    => array(
		array(
			'ruimtenaam' => 'Woonkamer schuifpui',
			'product'    => 'Plissé horren',
			'uitvoering' => 'Plissé hordeur',
			'gaas'       => 'Standaard gaas (extra sterk)',
			'kleur'      => 'Antraciet',
			'kleur_type' => 'frame',
			'breedte_mm' => '2100',
			'hoogte_mm'  => '2400',
			// The client claims this is fine; the server decides.
			'buiten_standaard' => '',
			'foto'       => '0',
		),
		array(
			'ruimtenaam' => 'Veranda achterzijde',
			'product'    => 'Veranda zonwering',
			'uitvoering' => '',
			'gaas'       => '',
			'kleur'      => 'Ecru',
			'kleur_type' => 'doek',
			'breedte_mm' => '6200',
			'hoogte_mm'  => '2500',
			'buiten_standaard' => '',
			'foto'       => '0',
		),
		// A wholly empty row, as an accidental "add" in the admin produces.
		array(
			'ruimtenaam' => '', 'product' => '', 'uitvoering' => '', 'gaas' => '',
			'kleur' => '', 'kleur_type' => 'frame', 'breedte_mm' => '', 'hoogte_mm' => '',
			'buiten_standaard' => '', 'foto' => '',
		),
	),
	'status'   => '',
	'referentienummer' => '',
	'evil'     => '<script>alert(1)</script>',
);

$saved = horex_save_submission( 101, $posted );

check( 'unknown field dropped', array_key_exists( 'evil', $saved ), false );
check( 'name trimmed', $saved['naam'], 'Anna de Vries' );
check( 'email kept', $saved['email'], 'anna@example.nl' );
check( 'empty row dropped', count( $saved['items'] ), 2 );
check( 'width stored as integer', $saved['items'][0]['breedte_mm'], 2100 );
check( 'in-range measurement not flagged', $saved['items'][0]['buiten_standaard'], false );
check( 'over-range measurement flagged server-side', $saved['items'][1]['buiten_standaard'], true );
check( 'status defaults to nieuw', $saved['status'], 'nieuw' );
check( 'reference generated', $saved['referentienummer'], sprintf( 'HX-%d-0001', (int) gmdate( 'Y' ) ) );
check( 'title generated from reference and name', get_post_field( 'post_title', 101 ), sprintf( 'HX-%d-0001 — Anna de Vries', (int) gmdate( 'Y' ) ) );
check( 'flat reference meta written', get_post_meta( 101, HOREX_META_REFERENCE, true ), $saved['referentienummer'] );
check( 'flat item count written', get_post_meta( 101, HOREX_META_COUNT, true ), 2 );

/* A client claiming a bad measurement is fine must not win. */
$saved = horex_save_submission(
	102,
	array(
		'naam'  => 'Bram Jansen',
		'items' => array(
			array( 'ruimtenaam' => 'Slaapkamer', 'breedte_mm' => '120', 'hoogte_mm' => '900', 'buiten_standaard' => '1', 'kleur_type' => 'frame' ),
		),
	)
);
check( 'under-range measurement flagged', $saved['items'][0]['buiten_standaard'], true );

$saved = horex_save_submission(
	103,
	array(
		'naam'  => 'Chris Bakker',
		'items' => array(
			array( 'ruimtenaam' => 'Keuken', 'breedte_mm' => '800', 'hoogte_mm' => '1200', 'buiten_standaard' => '1', 'kleur_type' => 'frame' ),
		),
	)
);
check( 'client-claimed flag overridden when in range', $saved['items'][0]['buiten_standaard'], false );

/* An unfilled measurement is missing, not out of range. */
$saved = horex_save_submission(
	104,
	array( 'naam' => 'Dana Smit', 'items' => array( array( 'ruimtenaam' => 'Zolder', 'breedte_mm' => '', 'hoogte_mm' => '', 'kleur_type' => 'frame' ) ) )
);
check( 'blank measurement not flagged as out of range', $saved['items'][0]['buiten_standaard'], false );
check( 'row with only a room name is kept', count( $saved['items'] ), 1 );

/* References increment and never collide. */
check( 'second reference increments', get_post_meta( 102, HOREX_META_REFERENCE, true ), sprintf( 'HX-%d-0002', (int) gmdate( 'Y' ) ) );
check( 'fourth reference increments', get_post_meta( 104, HOREX_META_REFERENCE, true ), sprintf( 'HX-%d-0004', (int) gmdate( 'Y' ) ) );

$references = array();
foreach ( array( 101, 102, 103, 104 ) as $id ) {
	$references[] = get_post_meta( $id, HOREX_META_REFERENCE, true );
}
check( 'all references unique', count( array_unique( $references ) ), 4 );

/* A reference already taken is skipped rather than duplicated. */
update_post_meta( 900, HOREX_META_REFERENCE, sprintf( 'HX-%d-0005', (int) gmdate( 'Y' ) ) );
$saved = horex_save_submission( 105, array( 'naam' => 'Eva Mol', 'items' => array() ) );
check( 'taken reference skipped', $saved['referentienummer'], sprintf( 'HX-%d-0006', (int) gmdate( 'Y' ) ) );

/* Saving the same request again must not mint a second reference. */
$first  = horex_save_submission( 108, array( 'naam' => 'Gijs Peters', 'items' => array() ) );
$second = horex_save_submission( 108, array( 'naam' => 'Gijs Peters', 'items' => array() ) );
check( 'resaving keeps the same reference', $second['referentienummer'], $first['referentienummer'] );

$after = horex_save_submission( 109, array( 'naam' => 'Hanna Vos', 'items' => array() ) );
check( 'resaving did not burn a counter value', $after['referentienummer'], sprintf( 'HX-%d-0008', (int) gmdate( 'Y' ) ) );

/* An existing reference is never regenerated. */
$saved = horex_save_submission( 101, array( 'naam' => 'Anna de Vries', 'referentienummer' => 'HX-2024-0099', 'items' => array() ) );
check( 'supplied reference kept', $saved['referentienummer'], 'HX-2024-0099' );
check( 'title follows the reference', get_post_field( 'post_title', 101 ), 'HX-2024-0099 — Anna de Vries' );

/* The prefix is configurable. */
update_option( HOREX_OPTION, array( 'min_mm' => 300, 'max_mm' => 6000, 'referentie_prefix' => 'OFF-' ) );
$saved = horex_save_submission( 106, array( 'naam' => 'Finn Dekker', 'items' => array() ) );
check( 'prefix comes from settings', 0 === strpos( $saved['referentienummer'], 'OFF-' ), true );

/* Every schema field is present after a bare save. */
$saved = horex_save_submission( 107, array() );
check( 'all schema fields present', array_keys( $saved ), array_keys( horex_submission_fields() ) );
check( 'nameless request still titled by reference', get_post_field( 'post_title', 107 ), $saved['referentienummer'] );

finish();
