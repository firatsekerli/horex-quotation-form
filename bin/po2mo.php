<?php
/**
 * Compile .po files to .mo, so translations can be rebuilt without gettext installed.
 *
 * Usage:  php bin/po2mo.php [languages-directory]
 *
 * Handles the subset of the PO format this plugin uses: singular entries, multi-line
 * strings and the header. Plural forms are not supported — add msgfmt if they appear.
 *
 * @package Horex
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$dir   = isset( $argv[1] ) ? rtrim( $argv[1], '/' ) : dirname( __DIR__ ) . '/languages';
$files = glob( $dir . '/*.po' );

if ( ! $files ) {
	fwrite( STDERR, "No .po files found in {$dir}\n" );
	exit( 1 );
}

foreach ( $files as $po ) {
	$mo = preg_replace( '/\.po$/', '.mo', $po );
	$entries = horex_parse_po( $po );

	if ( ! $entries ) {
		fwrite( STDERR, "Nothing translated in " . basename( $po ) . ", skipped\n" );
		continue;
	}

	file_put_contents( $mo, horex_build_mo( $entries ) );
	printf( "%s → %s (%d entries)\n", basename( $po ), basename( $mo ), count( $entries ) );
}

/**
 * Parse a .po file into msgid => msgstr pairs, dropping untranslated entries.
 *
 * @param string $path Path to the .po file.
 * @return array
 */
function horex_parse_po( $path ) {
	$entries = array();
	$id      = null;
	$str     = null;
	$current = null;

	$flush = function () use ( &$entries, &$id, &$str ) {
		// An empty msgid carries the headers, which the .mo must keep.
		if ( null !== $id && null !== $str && ( '' === $id || '' !== $str ) ) {
			$entries[ $id ] = $str;
		}

		$id  = null;
		$str = null;
	};

	foreach ( file( $path, FILE_IGNORE_NEW_LINES ) as $line ) {
		$line = trim( $line );

		if ( '' === $line || '#' === substr( $line, 0, 1 ) ) {
			continue;
		}

		if ( 0 === strpos( $line, 'msgid ' ) ) {
			$flush();
			$current = 'id';
			$id      = horex_po_unquote( substr( $line, 6 ) );
			continue;
		}

		if ( 0 === strpos( $line, 'msgstr ' ) ) {
			$current = 'str';
			$str     = horex_po_unquote( substr( $line, 7 ) );
			continue;
		}

		if ( '"' === substr( $line, 0, 1 ) ) {
			if ( 'id' === $current ) {
				$id .= horex_po_unquote( $line );
			} elseif ( 'str' === $current ) {
				$str .= horex_po_unquote( $line );
			}
		}
	}

	$flush();

	return $entries;
}

/**
 * Unquote one quoted PO string fragment.
 *
 * @param string $value Quoted fragment.
 * @return string
 */
function horex_po_unquote( $value ) {
	$value = trim( $value );

	if ( '"' === substr( $value, 0, 1 ) ) {
		$value = substr( $value, 1, -1 );
	}

	return strtr( $value, array( '\\n' => "\n", '\\t' => "\t", '\\"' => '"', '\\\\' => '\\' ) );
}

/**
 * Build the binary .mo payload.
 *
 * @param array $entries msgid => msgstr.
 * @return string
 */
function horex_build_mo( array $entries ) {
	ksort( $entries );

	$ids     = array_keys( $entries );
	$strings = array_values( $entries );
	$count   = count( $entries );

	$ids_offset = 28;
	$str_offset = $ids_offset + ( $count * 8 );
	$data_start = $str_offset + ( $count * 8 );

	$ids_table = '';
	$str_table = '';
	$ids_blob  = '';
	$str_blob  = '';

	foreach ( $ids as $id ) {
		$ids_table .= pack( 'VV', strlen( $id ), $data_start + strlen( $ids_blob ) );
		$ids_blob  .= $id . "\0";
	}

	$str_start = $data_start + strlen( $ids_blob );

	foreach ( $strings as $string ) {
		$str_table .= pack( 'VV', strlen( $string ), $str_start + strlen( $str_blob ) );
		$str_blob  .= $string . "\0";
	}

	// Magic, revision, count, table offsets, then an empty hash table.
	return pack( 'VVVVVVV', 0x950412de, 0, $count, $ids_offset, $str_offset, 0, $data_start )
		. $ids_table . $str_table . $ids_blob . $str_blob;
}
