<?php
/**
 * Rebuild languages/horex.pot from the source, and merge new strings into the
 * existing .po files without losing what is already translated.
 *
 * Usage:  php bin/make-pot.php
 *
 * Prints how many strings are still untranslated per language, so a phase that adds
 * interface text cannot quietly ship half-translated.
 *
 * @package Horex
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require_once __DIR__ . '/lib-po.php';

$root = dirname( __DIR__ );
$dirs = array( $root . '/includes', $root . '/templates' );
$files = array( $root . '/horex-quotation-form.php' );

foreach ( $dirs as $dir ) {
	if ( is_dir( $dir ) ) {
		$files = array_merge( $files, glob( $dir . '/*.php' ) );
	}
}

$strings = horex_extract_strings( $files, $root );

if ( ! $strings ) {
	fwrite( STDERR, "No translatable strings found\n" );
	exit( 1 );
}

$pot = $root . '/languages/horex.pot';
file_put_contents( $pot, horex_render_po( $strings, array(), '' ) );
printf( "languages/horex.pot (%d strings)\n", count( $strings ) );

foreach ( glob( $root . '/languages/*.po' ) as $po ) {
	$locale   = basename( $po, '.po' );
	$locale   = substr( $locale, strpos( $locale, '-' ) + 1 );
	$existing = horex_parse_po( $po );

	file_put_contents( $po, horex_render_po( $strings, $existing, $locale ) );

	$missing = 0;

	foreach ( array_keys( $strings ) as $msgid ) {
		if ( empty( $existing[ $msgid ] ) ) {
			$missing++;
		}
	}

	printf(
		"languages/%s.po (%d strings, %d untranslated)%s\n",
		basename( $po, '.po' ),
		count( $strings ),
		$missing,
		$missing ? '  <- needs attention' : ''
	);
}

/**
 * Extract translatable strings and their source references.
 *
 * @param array  $files PHP files to scan.
 * @param string $root  Plugin root, stripped from the references.
 * @return array msgid => list of file:line references, in source order.
 */
function horex_extract_strings( array $files, $root ) {
	$pattern = '/(?:esc_html__|esc_attr__|esc_html_e|esc_attr_e|__|_e)\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/';
	$strings = array();

	foreach ( $files as $file ) {
		$lines = file( $file, FILE_IGNORE_NEW_LINES );
		$ref   = ltrim( str_replace( $root, '', $file ), '/' );

		foreach ( $lines as $number => $line ) {
			if ( ! preg_match_all( $pattern, $line, $matches ) ) {
				continue;
			}

			foreach ( $matches[1] as $raw ) {
				$msgid = str_replace( array( "\\'", '\\\\' ), array( "'", '\\' ), $raw );

				if ( ! isset( $strings[ $msgid ] ) ) {
					$strings[ $msgid ] = array();
				}

				$strings[ $msgid ][] = $ref . ':' . ( $number + 1 );
			}
		}
	}

	return $strings;
}

/**
 * Render a .pot or .po file.
 *
 * @param array  $strings  msgid => references.
 * @param array  $existing Previously translated msgid => msgstr.
 * @param string $locale   Locale code, or '' for the template.
 * @return string
 */
function horex_render_po( array $strings, array $existing, $locale ) {
	$out = "# Translation file for the Hor-Ex quotation plugin.\n"
		. "#\n"
		. "# Source strings are English. The customer-facing catalogue content — product\n"
		. "# names, measuring steps, email defaults — is stored data, not interface, and\n"
		. "# stays Dutch regardless of the language a WordPress user picks.\n"
		. "#\n"
		. "# Regenerate with: php bin/make-pot.php && php bin/po2mo.php\n"
		. "#\n"
		. "msgid \"\"\nmsgstr \"\"\n"
		. "\"Project-Id-Version: Hor-Ex Offerteaanvraag\\n\"\n"
		. "\"Report-Msgid-Bugs-To: https://github.com/firatsekerli/horex-quotation-form/issues\\n\"\n"
		. "\"MIME-Version: 1.0\\n\"\n"
		. "\"Content-Type: text/plain; charset=UTF-8\\n\"\n"
		. "\"Content-Transfer-Encoding: 8bit\\n\"\n"
		. "\"Plural-Forms: nplurals=2; plural=(n != 1);\\n\"\n";

	if ( $locale ) {
		$out .= "\"Language: {$locale}\\n\"\n";
	}

	$out .= "\"X-Domain: horex\\n\"\n";

	foreach ( $strings as $msgid => $refs ) {
		$out .= "\n";

		foreach ( $refs as $ref ) {
			$out .= '#: ' . $ref . "\n";
		}

		$msgstr = isset( $existing[ $msgid ] ) ? $existing[ $msgid ] : '';

		$out .= 'msgid ' . horex_po_quote( $msgid ) . "\n";
		$out .= 'msgstr ' . horex_po_quote( $msgstr ) . "\n";
	}

	return $out;
}
