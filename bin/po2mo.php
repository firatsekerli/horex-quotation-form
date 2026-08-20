<?php
/**
 * Compile .po files to .mo, so translations can be rebuilt without gettext installed.
 *
 * Usage:  php bin/po2mo.php [languages-directory]
 *
 * @package Horex
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

require_once __DIR__ . '/lib-po.php';

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
