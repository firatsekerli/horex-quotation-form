<?php
/**
 * Shared .po / .mo helpers for the scripts in this directory.
 *
 * Handles the subset of the PO format this plugin uses: singular entries, multi-line
 * strings and the header. Plural forms are not supported — add msgfmt if they appear.
 *
 * @package Horex
 */

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

/**
 * Quote a string for a .po file.
 *
 * @param string $value Raw string.
 * @return string
 */
function horex_po_quote( $value ) {
	return '"' . strtr( $value, array( '\\' => '\\\\', '"' => '\\"', "\n" => '\\n', "\t" => '\\t' ) ) . '"';
}
