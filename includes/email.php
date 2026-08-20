<?php
/**
 * Notifications: the measurements table for Hor-Ex, and the customer's copy.
 *
 * The internal mail is written to be printed and carried to the appointment, so the
 * table leads with the room name — that is what someone standing in a hallway needs
 * to match a line to a window.
 *
 * @package Horex
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email Hor-Ex a new request, and the customer a copy.
 *
 * @param int   $post_id Request post ID.
 * @param array $data    Stored request data.
 */
function horex_send_notifications( $post_id, array $data ) {
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$to      = horex_notification_recipients();

	if ( $to ) {
		$subject = trim( (string) horex_get_setting( 'onderwerp', 'Nieuwe offerteaanvraag' ) );

		wp_mail(
			$to,
			$subject . ' — ' . $data['referentienummer'],
			horex_admin_email_body( $post_id, $data ),
			array_merge( $headers, horex_reply_to( $data ) )
		);
	}

	if ( horex_get_setting( 'stuur_klant_kopie' ) && is_email( $data['email'] ) ) {
		wp_mail(
			$data['email'],
			'Uw offerteaanvraag bij Hor-Ex — ' . $data['referentienummer'],
			horex_customer_email_body( $data ),
			$headers
		);
	}
}

/**
 * Who receives new requests.
 *
 * @return array
 */
function horex_notification_recipients() {
	$to = (array) horex_get_setting( 'ontvangers' );
	$to = array_values( array_filter( array_map( 'trim', $to ), 'is_email' ) );

	if ( $to ) {
		return $to;
	}

	$fallback = get_option( 'admin_email' );

	return is_email( $fallback ) ? array( $fallback ) : array();
}

/**
 * Reply straight to the customer from the internal mail.
 *
 * @param array $data Stored request data.
 * @return array
 */
function horex_reply_to( array $data ) {
	if ( ! is_email( $data['email'] ) ) {
		return array();
	}

	$name = $data['naam'] ? $data['naam'] : $data['email'];

	return array( sprintf( 'Reply-To: %s <%s>', $name, $data['email'] ) );
}

/**
 * The internal mail: contact details and the measurements table.
 *
 * @param int   $post_id Request post ID.
 * @param array $data    Stored request data.
 * @return string
 */
function horex_admin_email_body( $post_id, array $data ) {
	$lines = array();

	foreach ( array(
		'Naam'     => $data['naam'],
		'E-mail'   => $data['email'],
		'Telefoon' => $data['telefoon'],
		'Adres'    => trim( $data['adres'] . ' ' . $data['postcode'] . ' ' . $data['plaats'] ),
	) as $label => $value ) {
		if ( '' !== trim( (string) $value ) ) {
			$lines[] = '<tr><th align="left" style="padding:2px 16px 2px 0;font-weight:600;">' . esc_html( $label )
				. '</th><td style="padding:2px 0;">' . esc_html( $value ) . '</td></tr>';
		}
	}

	$body = horex_email_open( 'Nieuwe offerteaanvraag' )
		. '<p style="margin:0 0 4px;color:#857E6C;font-size:13px;">Referentie ' . esc_html( $data['referentienummer'] )
		. ' &middot; ' . esc_html( get_the_date( 'j F Y, H:i', $post_id ) ) . '</p>'
		. '<table style="border-collapse:collapse;margin:16px 0 24px;font-size:15px;">' . implode( '', $lines ) . '</table>';

	if ( '' !== trim( (string) $data['opmerkingen'] ) ) {
		$body .= '<p style="margin:0 0 24px;padding:12px 14px;background:#FFF3D2;border-radius:8px;font-size:15px;">'
			. '<strong>Opmerking van de klant</strong><br />' . nl2br( esc_html( $data['opmerkingen'] ) ) . '</p>';
	}

	$body .= horex_measurements_table( $data['items'] );

	$edit = get_edit_post_link( $post_id, '' );

	if ( $edit ) {
		$body .= '<p style="margin:24px 0 0;font-size:14px;"><a href="' . esc_url( $edit ) . '">Bekijk deze aanvraag in WordPress</a></p>';
	}

	return $body . horex_email_close();
}

/**
 * The customer's copy.
 *
 * @param array $data Stored request data.
 * @return string
 */
function horex_customer_email_body( array $data ) {
	$intro = (string) horex_get_setting( 'intro_tekst' );

	return horex_email_open( 'Uw offerteaanvraag' )
		. '<div style="font-size:15px;line-height:1.6;">' . wp_kses_post( wpautop( $intro ) ) . '</div>'
		. '<p style="margin:16px 0 24px;color:#857E6C;font-size:13px;">Uw referentie is <strong>'
		. esc_html( $data['referentienummer'] ) . '</strong>. Houd deze bij de hand als u contact met ons opneemt.</p>'
		. horex_measurements_table( $data['items'] )
		. '<p style="margin:24px 0 0;font-size:13px;color:#857E6C;">Dit is een aanvraag, geen bestelling. Alles wordt op maat '
		. 'gemaakt en wij meten altijd zelf na op locatie voordat er iets geproduceerd wordt.</p>'
		. horex_email_close();
}

/**
 * The measurements table, printable as it stands.
 *
 * @param array $items Stored measurement rows.
 * @return string
 */
function horex_measurements_table( array $items ) {
	$head = '';

	foreach ( array( 'Ruimte', 'Product', 'Uitvoering', 'Kleur', 'Gaas', 'Breedte', 'Hoogte' ) as $column ) {
		$head .= '<th align="left" style="padding:8px 10px;border-bottom:2px solid #1E1E1E;white-space:nowrap;">'
			. esc_html( $column ) . '</th>';
	}

	$rows = '';

	foreach ( $items as $item ) {
		$cells = array(
			$item['ruimtenaam'],
			$item['product'],
			$item['uitvoering'] ? $item['uitvoering'] : '—',
			$item['kleur'] ? $item['kleur'] : '—',
			$item['gaas'] ? $item['gaas'] : '—',
			$item['breedte_mm'] . ' mm',
			$item['hoogte_mm'] . ' mm',
		);

		$rows .= '<tr>';

		foreach ( $cells as $index => $cell ) {
			$rows .= '<td style="padding:8px 10px;border-bottom:1px solid #EDE4CE;'
				. ( $index >= 5 ? 'white-space:nowrap;font-variant-numeric:tabular-nums;' : '' )
				. ( 0 === $index ? 'font-weight:600;' : '' ) . '">' . esc_html( $cell ) . '</td>';
		}

		$rows .= '</tr>';

		if ( ! empty( $item['buiten_standaard'] ) ) {
			$rows .= '<tr><td colspan="7" style="padding:4px 10px 10px;font-size:13px;color:#A8412F;">'
				. 'Deze maat valt buiten het standaardbereik — even navragen bij de klant.</td></tr>';
		}
	}

	return '<table style="border-collapse:collapse;width:100%;font-size:14px;">'
		. '<thead><tr>' . $head . '</tr></thead><tbody>' . $rows . '</tbody></table>';
}

/**
 * Opening markup shared by both mails.
 *
 * @param string $title Heading.
 * @return string
 */
function horex_email_open( $title ) {
	return '<div style="background:#FFFAED;padding:24px;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;color:#1E1E1E;">'
		. '<div style="max-width:680px;margin:0 auto;background:#FFFFFF;border-radius:14px;padding:28px;">'
		. '<h1 style="margin:0 0 8px;font-size:22px;line-height:1.2;">' . esc_html( $title ) . '</h1>';
}

/**
 * Closing markup shared by both mails.
 *
 * @return string
 */
function horex_email_close() {
	return '</div></div>';
}
